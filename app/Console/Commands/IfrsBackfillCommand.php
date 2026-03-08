<?php

namespace App\Console\Commands;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Company;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\StockMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * IFRS Backfill Command
 *
 * Retroactively posts historical records to the IFRS ledger for companies
 * that had data before the accounting backbone was enabled.
 *
 * Usage:
 *   php artisan ifrs:backfill [options]
 *
 * Options:
 *   --company=<id>  Process only a specific company
 *   --type=<type>   Process only one type: invoices, bills, expenses, payments, bill-payments, stock
 *   --dry-run       Show what would be processed without making changes
 *   --force         Skip confirmation prompt
 */
class IfrsBackfillCommand extends Command
{
    protected $signature = 'ifrs:backfill
                            {--company= : Process only a specific company ID}
                            {--type= : Process only one type: invoices, bills, expenses, payments, bill-payments, stock}
                            {--dry-run : Show what would be processed without making changes}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Retroactively post historical records to IFRS ledger';

    protected IfrsAdapter $ifrsAdapter;

    protected array $stats = [
        'invoices_posted' => 0,
        'invoices_skipped' => 0,
        'bills_posted' => 0,
        'bills_skipped' => 0,
        'expenses_posted' => 0,
        'expenses_skipped' => 0,
        'payments_posted' => 0,
        'payments_skipped' => 0,
        'bill_payments_posted' => 0,
        'bill_payments_skipped' => 0,
        'stock_posted' => 0,
        'stock_skipped' => 0,
        'errors' => 0,
    ];

    public function __construct(IfrsAdapter $ifrsAdapter)
    {
        parent::__construct();
        $this->ifrsAdapter = $ifrsAdapter;
    }

    public function handle(): int
    {
        $companyId = $this->option('company');
        $type = $this->option('type');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $validTypes = ['invoices', 'bills', 'expenses', 'payments', 'bill-payments', 'stock'];
        if ($type && ! in_array($type, $validTypes)) {
            $this->error('Invalid type. Valid types: ' . implode(', ', $validTypes));

            return Command::FAILURE;
        }

        $this->info('=== IFRS Ledger Backfill ===');
        $this->newLine();

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
            $this->newLine();
        }

        // Get companies to process
        $companiesQuery = Company::query();
        if ($companyId) {
            $companiesQuery->where('id', $companyId);
        }
        $companies = $companiesQuery->get();

        if ($companies->isEmpty()) {
            $this->error('No companies found.');

            return Command::FAILURE;
        }

        // Show preview
        $this->showPreview($companies, $type);

        // Confirm unless forced or dry-run
        if (! $dryRun && ! $force) {
            if (! $this->confirm('Proceed with posting to IFRS ledger?')) {
                $this->info('Cancelled.');

                return Command::SUCCESS;
            }
        }

        // Process each company
        foreach ($companies as $company) {
            $this->processCompany($company, $type, $dryRun);
        }

        $this->showStats($dryRun);

        return $this->stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    protected function showPreview($companies, ?string $type): void
    {
        $this->info("Companies to process: {$companies->count()}");

        foreach ($companies as $company) {
            $enabled = $this->ifrsAdapter->isEnabled($company->id);
            $status = $enabled ? '<fg=green>ENABLED</>' : '<fg=red>DISABLED</>';
            $this->line("  {$company->name} (ID: {$company->id}) — IFRS: {$status}");

            if (! $enabled) {
                $this->line('    <fg=yellow>Skipping — IFRS not enabled</>');

                continue;
            }

            if (! $type || $type === 'invoices') {
                $count = $this->getUnpostedInvoices($company->id)->count();
                $this->line("    Invoices to post: {$count}");
            }
            if (! $type || $type === 'bills') {
                $count = $this->getUnpostedBills($company->id)->count();
                $this->line("    Bills to post: {$count}");
            }
            if (! $type || $type === 'expenses') {
                $count = $this->getUnpostedExpenses($company->id)->count();
                $this->line("    Expenses to post: {$count}");
            }
            if (! $type || $type === 'payments') {
                $count = $this->getUnpostedPayments($company->id)->count();
                $this->line("    Payments to post: {$count}");
            }
            if (! $type || $type === 'bill-payments') {
                $count = $this->getUnpostedBillPayments($company->id)->count();
                $this->line("    Bill payments to post: {$count}");
            }
            if (! $type || $type === 'stock') {
                $count = $this->getUnpostedStockMovements($company->id)->count();
                $this->line("    Stock movements to post: {$count}");
            }
        }

        $this->newLine();
    }

    protected function processCompany(Company $company, ?string $type, bool $dryRun): void
    {
        if (! $this->ifrsAdapter->isEnabled($company->id)) {
            $this->warn("Skipping {$company->name} (ID: {$company->id}) — IFRS not enabled");

            return;
        }

        $this->info("Processing: {$company->name} (ID: {$company->id})");

        // Processing order preserves GL double-entry integrity:
        // 1. Invoices first (creates AR receivable)
        // 2. Bills (creates AP payable)
        // 3. Expenses (direct expense posting)
        // 4. Payments (settles AR, creates BANK)
        // 5. Bill Payments (settles AP, creates BANK)
        // 6. Stock Movements (inventory entries)

        if (! $type || $type === 'invoices') {
            $this->processType('Invoices', $this->getUnpostedInvoices($company->id), $dryRun, function ($invoice) {
                $this->ifrsAdapter->postInvoice($invoice);
            }, 'invoices');
        }

        if (! $type || $type === 'bills') {
            $this->processType('Bills', $this->getUnpostedBills($company->id), $dryRun, function ($bill) {
                $this->ifrsAdapter->postBill($bill);
            }, 'bills');
        }

        if (! $type || $type === 'expenses') {
            $this->processType('Expenses', $this->getUnpostedExpenses($company->id), $dryRun, function ($expense) {
                $this->ifrsAdapter->postExpense($expense);
            }, 'expenses');
        }

        if (! $type || $type === 'payments') {
            $this->processType('Payments', $this->getUnpostedPayments($company->id), $dryRun, function ($payment) {
                $this->ifrsAdapter->postPayment($payment);
            }, 'payments');
        }

        if (! $type || $type === 'bill-payments') {
            $this->processType('Bill Payments', $this->getUnpostedBillPayments($company->id), $dryRun, function ($bp) {
                $this->ifrsAdapter->postBillPayment($bp);
            }, 'bill_payments');
        }

        if (! $type || $type === 'stock') {
            $this->processType('Stock Movements', $this->getUnpostedStockMovements($company->id), $dryRun, function ($movement) {
                $this->ifrsAdapter->postStockMovement($movement);
            }, 'stock');
        }

        $this->newLine();
    }

    protected function processType(string $label, $records, bool $dryRun, callable $postFn, string $statKey): void
    {
        if ($records->isEmpty()) {
            $this->line("  {$label}: 0 to process");

            return;
        }

        $this->line("  {$label}: {$records->count()} to process");

        $bar = $this->output->createProgressBar($records->count());
        $bar->start();

        foreach ($records as $record) {
            try {
                if (! $dryRun) {
                    $postFn($record);
                }
                $this->stats[$statKey . '_posted']++;
            } catch (\Exception $e) {
                $this->stats['errors']++;
                $this->stats[$statKey . '_skipped']++;
                Log::error("IFRS backfill error [{$label}]", [
                    'id' => $record->id,
                    'error' => $e->getMessage(),
                ]);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    // ---- Query builders ----

    protected function getUnpostedInvoices(int $companyId)
    {
        return Invoice::where('company_id', $companyId)
            ->whereNull('ifrs_transaction_id')
            ->where('status', '<>', Invoice::STATUS_DRAFT)
            ->with(['customer', 'company'])
            ->orderBy('invoice_date', 'asc')
            ->get();
    }

    protected function getUnpostedBills(int $companyId)
    {
        return Bill::where('company_id', $companyId)
            ->whereNull('ifrs_transaction_id')
            ->where('status', Bill::STATUS_COMPLETED)
            ->with(['supplier', 'company'])
            ->orderBy('bill_date', 'asc')
            ->get();
    }

    protected function getUnpostedExpenses(int $companyId)
    {
        return Expense::where('company_id', $companyId)
            ->whereNull('ifrs_transaction_id')
            ->with(['category', 'company'])
            ->orderBy('expense_date', 'asc')
            ->get();
    }

    protected function getUnpostedPayments(int $companyId)
    {
        return Payment::where('company_id', $companyId)
            ->whereNull('ifrs_transaction_id')
            ->where(function ($q) {
                $q->where('status', Payment::STATUS_COMPLETED)
                    ->orWhere('gateway_status', Payment::GATEWAY_STATUS_COMPLETED);
            })
            ->with(['customer', 'company', 'invoice', 'paymentMethod'])
            ->orderBy('payment_date', 'asc')
            ->get();
    }

    protected function getUnpostedBillPayments(int $companyId)
    {
        return BillPayment::where('company_id', $companyId)
            ->whereNull('ifrs_transaction_id')
            ->with(['bill', 'bill.supplier', 'company', 'paymentMethod'])
            ->orderBy('payment_date', 'asc')
            ->get();
    }

    protected function getUnpostedStockMovements(int $companyId)
    {
        return StockMovement::where('company_id', $companyId)
            ->whereNull('ifrs_transaction_id')
            ->whereNotIn('source_type', [
                StockMovement::SOURCE_TRANSFER_IN,
                StockMovement::SOURCE_TRANSFER_OUT,
            ])
            ->where('total_cost', '>', 0)
            ->with(['item', 'company'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    protected function showStats(bool $dryRun): void
    {
        $this->newLine();
        $this->info('=== Summary ===');

        $prefix = $dryRun ? 'Would post' : 'Posted';

        $types = [
            'Invoices' => ['invoices_posted', 'invoices_skipped'],
            'Bills' => ['bills_posted', 'bills_skipped'],
            'Expenses' => ['expenses_posted', 'expenses_skipped'],
            'Payments' => ['payments_posted', 'payments_skipped'],
            'Bill Payments' => ['bill_payments_posted', 'bill_payments_skipped'],
            'Stock Movements' => ['stock_posted', 'stock_skipped'],
        ];

        foreach ($types as $label => [$postedKey, $skippedKey]) {
            $posted = $this->stats[$postedKey];
            $skipped = $this->stats[$skippedKey];
            if ($posted > 0 || $skipped > 0) {
                $this->line("  {$label}: {$prefix} {$posted}" . ($skipped > 0 ? ", errors {$skipped}" : ''));
            }
        }

        $totalPosted = $this->stats['invoices_posted'] + $this->stats['bills_posted']
            + $this->stats['expenses_posted'] + $this->stats['payments_posted']
            + $this->stats['bill_payments_posted'] + $this->stats['stock_posted'];

        $this->newLine();
        $this->info("Total: {$prefix} {$totalPosted} records, {$this->stats['errors']} errors");

        if ($this->stats['errors'] > 0) {
            $this->error('Check logs for error details: storage/logs/laravel.log');
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('This was a dry run. Run with --force to execute.');
        }
    }
}

// CLAUDE-CHECKPOINT
