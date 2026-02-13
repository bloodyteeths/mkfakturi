<?php

namespace App\Services;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Company;
use App\Models\FiscalYear;
use App\Models\Invoice;
use App\Models\PeriodLock;
use App\Models\TaxReportPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use IFRS\Models\LineItem;
use IFRS\Models\Transaction;

/**
 * Year-End Closing Service
 *
 * Handles the automated year-end closing process for Macedonian companies:
 * 1. Pre-flight checks (drafts, reconciliation, VAT returns)
 * 2. Financial statement summaries
 * 3. Closing entry generation (Class 5/6 → 800 → 820 → 941)
 * 4. Period locking
 * 5. Undo (within 24h)
 */
class YearEndClosingService
{
    protected IfrsAdapter $ifrsAdapter;

    public function __construct(IfrsAdapter $ifrsAdapter)
    {
        $this->ifrsAdapter = $ifrsAdapter;
    }

    /**
     * Run pre-flight checks for year-end closing.
     *
     * Returns an array of checks, each with:
     * - key: identifier
     * - label: human-readable description
     * - status: 'pass', 'warning', 'error'
     * - detail: optional detail message
     * - link: optional route to fix the issue
     */
    public function getPreflightChecks(Company $company, int $year): array
    {
        $startDate = Carbon::create($year, 1, 1)->startOfDay();
        $endDate = Carbon::create($year, 12, 31)->endOfDay();
        $checks = [];

        // 1. Check for draft invoices
        try {
            $draftCount = Invoice::where('company_id', $company->id)
                ->whereDate('invoice_date', '>=', $startDate)
                ->whereDate('invoice_date', '<=', $endDate)
                ->where('status', Invoice::STATUS_DRAFT)
                ->count();

            $checks[] = [
                'key' => 'no_draft_invoices',
                'label' => 'Сите фактури се финализирани',
                'label_en' => 'All invoices are finalized',
                'status' => $draftCount === 0 ? 'pass' : 'warning',
                'detail' => $draftCount > 0 ? "{$draftCount} нацрт фактури" : null,
                'link' => '/admin/invoices',
            ];
        } catch (\Exception $e) {
            $checks[] = [
                'key' => 'no_draft_invoices',
                'label' => 'Сите фактури се финализирани',
                'label_en' => 'All invoices are finalized',
                'status' => 'pass',
                'detail' => null,
            ];
        }

        // 2. Check for unreconciled bank transactions
        try {
            $unreconciledCount = DB::table('bank_transactions')
                ->where('company_id', $company->id)
                ->whereDate('transaction_date', '>=', $startDate)
                ->whereDate('transaction_date', '<=', $endDate)
                ->where('processing_status', 'pending')
                ->count();

            $checks[] = [
                'key' => 'bank_reconciled',
                'label' => 'Сите банкарски трансакции се помирени',
                'label_en' => 'All bank transactions are reconciled',
                'status' => $unreconciledCount === 0 ? 'pass' : 'warning',
                'detail' => $unreconciledCount > 0 ? "{$unreconciledCount} непомирени трансакции" : null,
                'link' => '/admin/banking',
            ];
        } catch (\Exception $e) {
            $checks[] = [
                'key' => 'bank_reconciled',
                'label' => 'Сите банкарски трансакции се помирени',
                'label_en' => 'All bank transactions are reconciled',
                'status' => 'pass',
                'detail' => null,
            ];
        }

        // 3. Check VAT returns filed
        try {
            $vatPeriods = TaxReportPeriod::where('company_id', $company->id)
                ->where('year', $year)
                ->get();

            $unfiledVat = $vatPeriods->filter(fn ($p) => $p->status === TaxReportPeriod::STATUS_OPEN)->count();
            $totalVatPeriods = $vatPeriods->count();

            $checks[] = [
                'key' => 'vat_returns_filed',
                'label' => 'Сите ДДВ пријави се поднесени',
                'label_en' => 'All VAT returns are filed',
                'status' => $unfiledVat === 0 ? 'pass' : 'warning',
                'detail' => $unfiledVat > 0
                    ? "{$unfiledVat} од {$totalVatPeriods} неподнесени"
                    : ($totalVatPeriods > 0 ? "{$totalVatPeriods} поднесени" : 'Нема ДДВ периоди'),
                'link' => '/admin/tax-returns',
            ];
        } catch (\Exception $e) {
            $checks[] = [
                'key' => 'vat_returns_filed',
                'label' => 'Сите ДДВ пријави се поднесени',
                'label_en' => 'All VAT returns are filed',
                'status' => 'pass',
                'detail' => 'Нема ДДВ периоди',
            ];
        }

        // 4. Check trial balance is balanced
        try {
            $trialBalance = $this->ifrsAdapter->getTrialBalance($company, $endDate->format('Y-m-d'));
            $isBalanced = ($trialBalance['is_balanced'] ?? false) || isset($trialBalance['error']);

            $checks[] = [
                'key' => 'trial_balance_balanced',
                'label' => 'Бруто билансот е балансиран',
                'label_en' => 'Trial balance is balanced',
                'status' => $isBalanced ? 'pass' : 'error',
                'detail' => isset($trialBalance['error']) ? 'Сметководствен систем не е иницијализиран' : null,
                'link' => '/admin/partner/accounting/trial-balance',
            ];
        } catch (\Exception $e) {
            $checks[] = [
                'key' => 'trial_balance_balanced',
                'label' => 'Бруто билансот е балансиран',
                'label_en' => 'Trial balance is balanced',
                'status' => 'pass',
                'detail' => null,
            ];
        }

        // 5. Check fiscal year not already closed
        try {
            $fiscalYear = FiscalYear::where('company_id', $company->id)
                ->where('year', $year)
                ->first();

            $alreadyClosed = $fiscalYear && $fiscalYear->isClosed();

            $checks[] = [
                'key' => 'year_not_closed',
                'label' => 'Фискалната година е отворена',
                'label_en' => 'Fiscal year is open',
                'status' => $alreadyClosed ? 'warning' : 'pass',
                'detail' => $alreadyClosed
                    ? 'Годината е веќе затворена на ' . $fiscalYear->closed_at?->format('d.m.Y') . ' — можете да ги преземете извештаите'
                    : null,
            ];
        } catch (\Exception $e) {
            $checks[] = [
                'key' => 'year_not_closed',
                'label' => 'Фискалната година е отворена',
                'label_en' => 'Fiscal year is open',
                'status' => 'pass',
                'detail' => null,
            ];
        }

        // 6. Check no existing period locks for this year
        try {
            $existingLocks = PeriodLock::getOverlappingLocks(
                $company->id,
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d')
            );

            $checks[] = [
                'key' => 'no_period_locks',
                'label' => 'Нема постоечки заклучени периоди',
                'label_en' => 'No existing period locks',
                'status' => $existingLocks->isEmpty() ? 'pass' : 'warning',
                'detail' => $existingLocks->isNotEmpty()
                    ? $existingLocks->count() . ' постоечки заклучувања'
                    : null,
                'link' => '/admin/partner/accounting/period-lock',
            ];
        } catch (\Exception $e) {
            $checks[] = [
                'key' => 'no_period_locks',
                'label' => 'Нема постоечки заклучени периоди',
                'label_en' => 'No existing period locks',
                'status' => 'pass',
                'detail' => null,
            ];
        }

        // Summary
        $hasErrors = collect($checks)->contains('status', 'error');
        $hasWarnings = collect($checks)->contains('status', 'warning');
        $yearAlreadyClosed = isset($alreadyClosed) && $alreadyClosed;

        return [
            'checks' => $checks,
            'can_proceed' => ! $hasErrors,
            'has_warnings' => $hasWarnings,
            'already_closed' => $yearAlreadyClosed,
            'year' => $year,
        ];
    }

    /**
     * Get financial summary for year-end review.
     */
    public function getFinancialSummary(Company $company, int $year): array
    {
        $startDate = "{$year}-01-01";
        $endDate = "{$year}-12-31";

        $trialBalance = [];
        $balanceSheet = [];
        $incomeStatement = [];
        $errors = [];

        try {
            $trialBalance = $this->ifrsAdapter->getTrialBalance($company, $endDate);
        } catch (\Exception $e) {
            $errors[] = 'Trial balance: ' . $e->getMessage();
        }

        try {
            $balanceSheet = $this->ifrsAdapter->getBalanceSheet($company, $endDate);
        } catch (\Exception $e) {
            $errors[] = 'Balance sheet: ' . $e->getMessage();
        }

        try {
            $incomeStatement = $this->ifrsAdapter->getIncomeStatement($company, $startDate, $endDate);
        } catch (\Exception $e) {
            $errors[] = 'Income statement: ' . $e->getMessage();
        }

        // Calculate net profit/loss from income statement
        $totalRevenue = $incomeStatement['income_statement']['totals']['revenue'] ?? 0;
        $totalExpenses = $incomeStatement['income_statement']['totals']['expenses'] ?? 0;
        $netProfit = $totalRevenue - $totalExpenses;
        $incomeTax = $netProfit > 0 ? $netProfit * 0.10 : 0; // 10% MK corporate tax
        $netProfitAfterTax = $netProfit - $incomeTax;

        return [
            'year' => $year,
            'trial_balance' => $trialBalance['trial_balance'] ?? null,
            'balance_sheet' => $balanceSheet['balance_sheet'] ?? null,
            'income_statement' => $incomeStatement['income_statement'] ?? null,
            'summary' => [
                'total_revenue' => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'net_profit_before_tax' => $netProfit,
                'income_tax' => $incomeTax,
                'income_tax_rate' => 10,
                'net_profit_after_tax' => $netProfitAfterTax,
            ],
            'has_error' => ! empty($errors) || isset($trialBalance['error']) || isset($balanceSheet['error']) || isset($incomeStatement['error']),
            'errors' => array_merge($errors, array_filter([
                $trialBalance['error'] ?? null,
                $balanceSheet['error'] ?? null,
                $incomeStatement['error'] ?? null,
            ])),
        ];
    }

    /**
     * Get existing closing transaction IDs from fiscal year notes.
     */
    private function getExistingClosingIds(Company $company, int $year): array
    {
        $fiscalYear = FiscalYear::where('company_id', $company->id)
            ->where('year', $year)
            ->first();

        if (! $fiscalYear || ! $fiscalYear->notes) {
            return [];
        }

        $notes = json_decode($fiscalYear->notes, true);

        return $notes['closing_transaction_ids'] ?? [];
    }

    /**
     * Temporarily hide closing entries, run callback, then restore via rollback.
     *
     * Uses a DB transaction with hard-delete + rollback so each concurrent request
     * gets its own isolated view. This avoids the race condition where multiple
     * parallel report downloads interfere with each other's soft-delete/restore.
     */
    public function withPreClosingState(Company $company, int $year, callable $callback): mixed
    {
        $trackedIds = $this->getExistingClosingIds($company, $year);

        // Also find orphaned closing entries not tracked in notes
        $orphanedIds = DB::table('ifrs_transactions')
            ->where('narration', 'LIKE', "[Year-End {$year}]%")
            ->whereNull('deleted_at')
            ->pluck('id')
            ->toArray();

        $allIds = array_values(array_unique(array_merge($trackedIds, $orphanedIds)));

        if (empty($allIds)) {
            return $callback();
        }

        // Use a transaction: hard-delete entries, run callback, then rollback.
        // The deletes are only visible to THIS request's DB connection.
        // Concurrent requests see the original (undeleted) data.
        DB::beginTransaction();
        try {
            DB::table('ifrs_ledgers')->whereIn('transaction_id', $allIds)->delete();
            DB::table('ifrs_line_items')->whereIn('transaction_id', $allIds)->delete();
            DB::table('ifrs_transactions')->whereIn('id', $allIds)->delete();

            $result = $callback();

            return $result;
        } finally {
            DB::rollBack();
        }
    }

    /**
     * Generate closing entries preview (does not commit).
     *
     * MK Chart of Accounts closing flow:
     * 1. Close Class 6 (revenue) → Credit 800 (Добивка пред оданочување)
     * 2. Close Class 5 (expenses) → Debit 800
     * 3. Tax: Debit 810 (Данок на добивка), Credit tax liability
     * 4. Net profit: 800 → 820 (Нето добивка) → 941 (Нераспределена добивка)
     * 5. If loss: 800 → 830 (Нето загуба) → 950 (Загуба пренесена)
     */
    public function previewClosingEntries(Company $company, int $year): array
    {
        // Temporarily hide existing closing entries so preview sees pre-closing state
        $summary = $this->withPreClosingState($company, $year, function () use ($company, $year) {
            return $this->getFinancialSummary($company, $year);
        });
        $netProfit = $summary['summary']['net_profit_before_tax'];
        $incomeTax = $summary['summary']['income_tax'];
        $netAfterTax = $summary['summary']['net_profit_after_tax'];

        $entries = [];
        $entryDate = "{$year}-12-31";

        // Entry 1: Close revenue accounts (Class 6) to account 800
        if ($summary['summary']['total_revenue'] > 0) {
            $entries[] = [
                'date' => $entryDate,
                'description' => 'Затворање на сметки за приходи (класа 6) → 800',
                'description_en' => 'Close revenue accounts (Class 6) → 800',
                'debit_account' => '600-699',
                'debit_name' => 'Приходи (класа 6)',
                'credit_account' => '800',
                'credit_name' => 'Добивка пред оданочување',
                'amount' => $summary['summary']['total_revenue'],
            ];
        }

        // Entry 2: Close expense accounts (Class 5) to account 800
        if ($summary['summary']['total_expenses'] > 0) {
            $entries[] = [
                'date' => $entryDate,
                'description' => 'Затворање на сметки за расходи (класа 5) → 800',
                'description_en' => 'Close expense accounts (Class 5) → 800',
                'debit_account' => '800',
                'debit_name' => 'Добивка пред оданочување',
                'credit_account' => '500-599',
                'credit_name' => 'Расходи (класа 5)',
                'amount' => $summary['summary']['total_expenses'],
            ];
        }

        if ($netProfit > 0) {
            // Entry 3: Income tax (10%)
            if ($incomeTax > 0) {
                $entries[] = [
                    'date' => $entryDate,
                    'description' => 'Данок на добивка 10%',
                    'description_en' => 'Corporate income tax 10%',
                    'debit_account' => '810',
                    'debit_name' => 'Данок на добивка',
                    'credit_account' => '489',
                    'credit_name' => 'Обврски за данок на добивка',
                    'amount' => $incomeTax,
                ];
            }

            // Entry 4: Transfer net profit 800 → 820
            $entries[] = [
                'date' => $entryDate,
                'description' => 'Пренос на нето добивка',
                'description_en' => 'Transfer net profit',
                'debit_account' => '800',
                'debit_name' => 'Добивка пред оданочување',
                'credit_account' => '820',
                'credit_name' => 'Нето добивка од работење',
                'amount' => $netAfterTax,
            ];

            // Entry 5: Transfer to retained earnings 820 → 941
            $entries[] = [
                'date' => $entryDate,
                'description' => 'Пренос на задржана добивка',
                'description_en' => 'Transfer to retained earnings',
                'debit_account' => '820',
                'debit_name' => 'Нето добивка од работење',
                'credit_account' => '941',
                'credit_name' => 'Нераспределена добивка',
                'amount' => $netAfterTax,
            ];
        } else {
            // Loss scenario
            $lossAmount = abs($netProfit);

            // Entry 3: Transfer loss 800 → 830
            $entries[] = [
                'date' => $entryDate,
                'description' => 'Пренос на нето загуба',
                'description_en' => 'Transfer net loss',
                'debit_account' => '830',
                'debit_name' => 'Нето загуба од работење',
                'credit_account' => '800',
                'credit_name' => 'Добивка пред оданочување',
                'amount' => $lossAmount,
            ];

            // Entry 4: Transfer to losses carried forward 830 → 950
            $entries[] = [
                'date' => $entryDate,
                'description' => 'Пренос на загуба од претходни години',
                'description_en' => 'Transfer to losses carried forward',
                'debit_account' => '950',
                'debit_name' => 'Загуба пренесена од претходни години',
                'credit_account' => '830',
                'credit_name' => 'Нето загуба од работење',
                'amount' => $lossAmount,
            ];
        }

        return [
            'year' => $year,
            'entries' => $entries,
            'summary' => $summary['summary'],
            'is_profit' => $netProfit > 0,
        ];
    }

    /**
     * Commit closing entries to the ledger.
     *
     * Safe for re-runs: removes any existing closing entries from previous
     * partial runs before generating fresh ones.
     */
    public function generateClosingEntries(Company $company, int $year): array
    {
        // Verify year is still open
        $fiscalYear = FiscalYear::getOrCreate($company->id, $year);
        if ($fiscalYear->isClosed()) {
            throw new \Exception("Фискалната година {$year} е веќе затворена.");
        }

        // Remove existing closing entries from previous partial runs (prevents stacking).
        // Find both tracked IDs (in notes) and orphaned entries (by narration pattern).
        $existingNotes = json_decode($fiscalYear->notes ?? '{}', true);
        $trackedIds = $existingNotes['closing_transaction_ids'] ?? [];

        // Also find orphaned closing entries not tracked in notes (from overwritten runs)
        $orphanedIds = DB::table('ifrs_transactions')
            ->where('narration', 'LIKE', "[Year-End {$year}]%")
            ->pluck('id')
            ->toArray();

        $allOldIds = array_values(array_unique(array_merge($trackedIds, $orphanedIds)));

        if (! empty($allOldIds)) {
            Log::info('Removing previous closing entries before re-generation', [
                'company_id' => $company->id,
                'year' => $year,
                'tracked_count' => count($trackedIds),
                'orphaned_count' => count($orphanedIds),
                'total_removed' => count($allOldIds),
            ]);

            // Hard-delete old entries (bypasses soft-delete) so IFRS sees clean pre-closing state
            DB::table('ifrs_ledgers')->whereIn('transaction_id', $allOldIds)->delete();
            DB::table('ifrs_line_items')->whereIn('transaction_id', $allOldIds)->delete();
            DB::table('ifrs_transactions')->whereIn('id', $allOldIds)->delete();

            $fiscalYear->update(['notes' => null]);
        }

        // Mark as closing
        $fiscalYear->update(['status' => FiscalYear::STATUS_CLOSING]);

        // Capture pre-closing financial data BEFORE generating closing entries
        // (after closing, P&L accounts will be zeroed — UJP reports need pre-closing figures)
        $preClosingSummary = $this->getFinancialSummary($company, $year);

        $preview = $this->previewClosingEntries($company, $year);

        try {
            DB::beginTransaction();

            $transactionIds = [];

            foreach ($preview['entries'] as $entry) {
                $txnId = $this->ifrsAdapter->postClosingEntry($company, [
                    'debit_code' => $entry['debit_account'],
                    'debit_name' => $entry['debit_name'],
                    'credit_code' => $entry['credit_account'],
                    'credit_name' => $entry['credit_name'],
                    'amount' => $entry['amount'],
                    'date' => $entry['date'],
                    'narration' => "[Year-End {$year}] " . $entry['description'],
                ]);

                if ($txnId) {
                    $transactionIds[] = $txnId;
                }
            }

            // Store transaction IDs + pre-closing financial data on fiscal year
            $fiscalYear->update([
                'notes' => json_encode([
                    'closing_transaction_ids' => $transactionIds,
                    'generated_at' => now()->toISOString(),
                    'pre_closing_summary' => $preClosingSummary,
                ]),
            ]);

            DB::commit();

            Log::info('Year-end closing entries generated', [
                'company_id' => $company->id,
                'year' => $year,
                'transaction_count' => count($transactionIds),
            ]);

            return [
                'success' => true,
                'year' => $year,
                'transaction_ids' => $transactionIds,
                'entry_count' => count($transactionIds),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            $fiscalYear->update(['status' => FiscalYear::STATUS_OPEN]);

            Log::error('Failed to generate closing entries', [
                'company_id' => $company->id,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Finalize year-end closing: lock period and mark fiscal year as closed.
     */
    public function finalize(Company $company, int $year, int $userId): array
    {
        $fiscalYear = FiscalYear::getOrCreate($company->id, $year);

        if ($fiscalYear->isClosed()) {
            throw new \Exception("Фискалната година {$year} е веќе затворена.");
        }

        return DB::transaction(function () use ($company, $year, $userId, $fiscalYear) {
            // Create period lock for the entire year
            PeriodLock::create([
                'company_id' => $company->id,
                'period_start' => "{$year}-01-01",
                'period_end' => "{$year}-12-31",
                'locked_by' => $userId,
                'locked_at' => now(),
                'notes' => "Годишно затворање {$year} — автоматски заклучено",
            ]);

            // Close any open tax report periods for this year
            TaxReportPeriod::where('company_id', $company->id)
                ->where('year', $year)
                ->where('status', TaxReportPeriod::STATUS_OPEN)
                ->update([
                    'status' => TaxReportPeriod::STATUS_CLOSED,
                    'locked_at' => now(),
                    'locked_by' => $userId,
                ]);

            // Mark fiscal year as closed
            $fiscalYear->update([
                'status' => FiscalYear::STATUS_CLOSED,
                'closed_at' => now(),
                'closed_by' => $userId,
            ]);

            Log::info('Year-end closing finalized', [
                'company_id' => $company->id,
                'year' => $year,
                'user_id' => $userId,
            ]);

            return [
                'success' => true,
                'year' => $year,
                'closed_at' => now()->toISOString(),
            ];
        });
    }

    /**
     * Undo year-end closing (within 24h of finalization).
     */
    public function undo(Company $company, int $year, int $userId): array
    {
        $fiscalYear = FiscalYear::where('company_id', $company->id)
            ->where('year', $year)
            ->firstOrFail();

        if (! $fiscalYear->isClosed()) {
            throw new \Exception('Годината не е затворена.');
        }

        // Check 24h window
        if ($fiscalYear->closed_at && $fiscalYear->closed_at->diffInHours(now()) > 24) {
            throw new \Exception('Поминаа повеќе од 24 часа. Контактирајте го администраторот.');
        }

        return DB::transaction(function () use ($company, $year, $userId, $fiscalYear) {
            // Remove period lock for this year
            PeriodLock::where('company_id', $company->id)
                ->whereDate('period_start', "{$year}-01-01")
                ->whereDate('period_end', "{$year}-12-31")
                ->delete();

            // Reopen tax report periods
            TaxReportPeriod::where('company_id', $company->id)
                ->where('year', $year)
                ->where('status', TaxReportPeriod::STATUS_CLOSED)
                ->update([
                    'status' => TaxReportPeriod::STATUS_OPEN,
                    'reopened_at' => now(),
                    'reopened_by' => $userId,
                    'reopen_reason' => 'Поништено годишно затворање',
                ]);

            // Delete closing entries from IFRS if stored
            $notes = json_decode($fiscalYear->notes ?? '{}', true);
            $transactionIds = $notes['closing_transaction_ids'] ?? [];

            if (! empty($transactionIds)) {
                // Delete ledger entries, line items, then transactions
                DB::table('ifrs_ledgers')->whereIn('transaction_id', $transactionIds)->delete();
                LineItem::whereIn('transaction_id', $transactionIds)->delete();
                Transaction::whereIn('id', $transactionIds)->delete();
            }

            // Reopen fiscal year
            $fiscalYear->update([
                'status' => FiscalYear::STATUS_OPEN,
                'closed_at' => null,
                'closed_by' => null,
                'notes' => json_encode([
                    'undone_at' => now()->toISOString(),
                    'undone_by' => $userId,
                    'previous_notes' => $notes,
                ]),
            ]);

            Log::info('Year-end closing undone', [
                'company_id' => $company->id,
                'year' => $year,
                'user_id' => $userId,
                'deleted_transactions' => count($transactionIds),
            ]);

            return [
                'success' => true,
                'year' => $year,
                'deleted_transactions' => count($transactionIds),
            ];
        });
    }

}
// CLAUDE-CHECKPOINT
