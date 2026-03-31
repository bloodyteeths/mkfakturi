<?php

namespace App\Http\Controllers\V1\Admin\Accounting;

use App\Domain\Accounting\IfrsAdapter;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Accounting Reports Controller
 *
 * Provides API endpoints for IFRS-compliant financial reports:
 * - Trial Balance
 * - Balance Sheet
 * - Income Statement
 */
class AccountingReportsController extends Controller
{
    protected IfrsAdapter $ifrsAdapter;

    public function __construct(IfrsAdapter $ifrsAdapter)
    {
        $this->ifrsAdapter = $ifrsAdapter;
    }

    /**
     * Get Trial Balance Report
     *
     * @OA\Get(
     *   path="/api/v1/accounting/trial-balance",
     *   tags={"Accounting"},
     *   summary="Get trial balance report",
     *
     *   @OA\Parameter(
     *     name="as_of_date",
     *     in="query",
     *     required=false,
     *
     *     @OA\Schema(type="string", format="date")
     *   ),
     *
     *   @OA\Response(response=200, description="Trial balance data"),
     *   @OA\Response(response=403, description="Feature disabled"),
     *   @OA\Response(response=404, description="Company not found")
     * )
     *
     * GET /api/v1/accounting/trial-balance?as_of_date=2025-08-31
     */
    public function trialBalance(Request $request): JsonResponse
    {
        // Check feature flag
        if (! $this->isFeatureEnabled()) {
            return response()->json([
                'error' => 'Accounting backbone feature is disabled',
                'message' => 'Please enable FEATURE_ACCOUNTING_BACKBONE to access accounting reports',
            ], 403);
        }

        // Get company from header (set by company middleware)
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json([
                'error' => 'Missing company header',
                'message' => 'The company header is required for accounting reports',
            ], 400);
        }

        $company = Company::find($companyId);

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
                'message' => 'The specified company does not exist',
            ], 404);
        }

        // Authorize user can access this company
        $this->authorize('view', $company);

        $asOfDate = $request->query('as_of_date', now()->toDateString());

        $trialBalance = $this->ifrsAdapter->getTrialBalance($company, $asOfDate);

        if (isset($trialBalance['error'])) {
            return response()->json($trialBalance, 400);
        }

        return response()->json([
            'success' => true,
            'trial_balance' => $trialBalance,
        ]);
    }

    /**
     * Get Balance Sheet Report
     *
     * @OA\Get(
     *   path="/api/v1/accounting/balance-sheet",
     *   tags={"Accounting"},
     *   summary="Get balance sheet report",
     *
     *   @OA\Parameter(
     *     name="as_of_date",
     *     in="query",
     *     required=false,
     *
     *     @OA\Schema(type="string", format="date")
     *   ),
     *
     *   @OA\Response(response=200, description="Balance sheet data"),
     *   @OA\Response(response=403, description="Feature disabled"),
     *   @OA\Response(response=404, description="Company not found")
     * )
     *
     * GET /api/v1/accounting/balance-sheet?as_of_date=2025-08-31
     */
    public function balanceSheet(Request $request): JsonResponse
    {
        // Check feature flag
        if (! $this->isFeatureEnabled()) {
            return response()->json([
                'error' => 'Accounting backbone feature is disabled',
                'message' => 'Please enable FEATURE_ACCOUNTING_BACKBONE to access accounting reports',
            ], 403);
        }

        // Get company from header (set by company middleware)
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json([
                'error' => 'Missing company header',
                'message' => 'The company header is required for accounting reports',
            ], 400);
        }

        $company = Company::find($companyId);

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
                'message' => 'The specified company does not exist',
            ], 404);
        }

        // Authorize user can access this company
        $this->authorize('view', $company);

        $asOfDate = $request->query('as_of_date', now()->toDateString());

        $balanceSheet = $this->ifrsAdapter->getBalanceSheet($company, $asOfDate);

        if (isset($balanceSheet['error'])) {
            return response()->json($balanceSheet, 400);
        }

        return response()->json([
            'success' => true,
            'balance_sheet' => $balanceSheet,
        ]);
    }

    /**
     * Get Income Statement Report
     *
     * @OA\Get(
     *   path="/api/v1/accounting/income-statement",
     *   tags={"Accounting"},
     *   summary="Get income statement report",
     *
     *   @OA\Parameter(
     *     name="start",
     *     in="query",
     *     required=true,
     *
     *     @OA\Schema(type="string", format="date")
     *   ),
     *
     *   @OA\Parameter(
     *     name="end",
     *     in="query",
     *     required=true,
     *
     *     @OA\Schema(type="string", format="date")
     *   ),
     *
     *   @OA\Response(response=200, description="Income statement data"),
     *   @OA\Response(response=403, description="Feature disabled"),
     *   @OA\Response(response=404, description="Company not found"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     *
     * GET /api/v1/accounting/income-statement?start=2025-01-01&end=2025-08-31
     */
    public function incomeStatement(Request $request): JsonResponse
    {
        // Check feature flag
        if (! $this->isFeatureEnabled()) {
            return response()->json([
                'error' => 'Accounting backbone feature is disabled',
                'message' => 'Please enable FEATURE_ACCOUNTING_BACKBONE to access accounting reports',
            ], 403);
        }

        // Get company from header (set by company middleware)
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json([
                'error' => 'Missing company header',
                'message' => 'The company header is required for accounting reports',
            ], 400);
        }

        $company = Company::find($companyId);

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
                'message' => 'The specified company does not exist',
            ], 404);
        }

        // Authorize user can access this company
        $this->authorize('view', $company);

        // Validate date parameters
        $validated = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date|after_or_equal:start',
        ]);

        $incomeStatement = $this->ifrsAdapter->getIncomeStatement(
            $company,
            $validated['start'],
            $validated['end']
        );

        if (isset($incomeStatement['error'])) {
            return response()->json($incomeStatement, 400);
        }

        return response()->json([
            'success' => true,
            'income_statement' => $incomeStatement,
        ]);
    }

    /**
     * Backfill existing invoices to the ledger
     *
     * POST /api/v1/accounting/backfill-invoices?dry_run=1
     */
    public function backfillInvoices(Request $request): JsonResponse
    {
        // Check feature flag
        if (! $this->isFeatureEnabled()) {
            return response()->json([
                'error' => 'Accounting backbone feature is disabled',
                'message' => 'Please enable FEATURE_ACCOUNTING_BACKBONE to backfill invoices',
            ], 403);
        }

        // Get company from header
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json([
                'error' => 'Missing company header',
                'message' => 'The company header is required',
            ], 400);
        }

        $company = Company::find($companyId);

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
            ], 404);
        }

        // Authorize user can manage this company
        $this->authorize('manage', $company);

        $dryRun = $request->boolean('dry_run', false);
        $stats = $this->ifrsAdapter->backfillInvoices($company, $dryRun);

        if (isset($stats['error'])) {
            return response()->json($stats, 400);
        }

        return response()->json([
            'success' => true,
            'dry_run' => $dryRun,
            'message' => $dryRun
                ? "Found {$stats['posted']} invoices to post"
                : "Posted {$stats['posted']} invoices, {$stats['failed']} failed",
            'stats' => $stats,
        ]);
    }

    /**
     * Get General Ledger Report
     *
     * @OA\Get(
     *   path="/api/v1/accounting/general-ledger",
     *   tags={"Accounting"},
     *   summary="Get general ledger report for a specific account",
     *
     *   @OA\Parameter(
     *     name="account_id",
     *     in="query",
     *     required=true,
     *
     *     @OA\Schema(type="integer")
     *   ),
     *
     *   @OA\Parameter(
     *     name="start_date",
     *     in="query",
     *     required=true,
     *
     *     @OA\Schema(type="string", format="date")
     *   ),
     *
     *   @OA\Parameter(
     *     name="end_date",
     *     in="query",
     *     required=true,
     *
     *     @OA\Schema(type="string", format="date")
     *   ),
     *
     *   @OA\Response(response=200, description="General ledger data"),
     *   @OA\Response(response=403, description="Feature disabled"),
     *   @OA\Response(response=404, description="Company not found"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     *
     * GET /api/v1/accounting/general-ledger?account_id=1&start_date=2025-01-01&end_date=2025-12-31
     */
    public function generalLedger(Request $request): JsonResponse
    {
        // Check feature flag
        if (! $this->isFeatureEnabled()) {
            return response()->json([
                'error' => 'Accounting backbone feature is disabled',
                'message' => 'Please enable FEATURE_ACCOUNTING_BACKBONE to access accounting reports',
            ], 403);
        }

        // Get company from header (set by company middleware)
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json([
                'error' => 'Missing company header',
                'message' => 'The company header is required for accounting reports',
            ], 400);
        }

        $company = Company::find($companyId);

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
                'message' => 'The specified company does not exist',
            ], 404);
        }

        // Authorize user can access this company
        $this->authorize('view', $company);

        // Validate parameters - accept either account_id (IFRS ID) or account_code
        $validated = $request->validate([
            'account_id' => 'required_without:account_code|integer',
            'account_code' => 'required_without:account_id|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $generalLedger = $this->ifrsAdapter->getGeneralLedger(
            $company,
            $validated['account_id'] ?? null,
            $validated['start_date'],
            $validated['end_date'],
            $validated['account_code'] ?? null
        );

        if (isset($generalLedger['error'])) {
            return response()->json($generalLedger, 400);
        }

        return response()->json([
            'success' => true,
            'data' => $generalLedger,
        ]);
    }

    /**
     * Get Journal Entries Report
     *
     * @OA\Get(
     *   path="/api/v1/accounting/journal-entries",
     *   tags={"Accounting"},
     *   summary="Get journal entries report",
     *
     *   @OA\Parameter(
     *     name="start_date",
     *     in="query",
     *     required=true,
     *
     *     @OA\Schema(type="string", format="date")
     *   ),
     *
     *   @OA\Parameter(
     *     name="end_date",
     *     in="query",
     *     required=true,
     *
     *     @OA\Schema(type="string", format="date")
     *   ),
     *
     *   @OA\Parameter(
     *     name="page",
     *     in="query",
     *     required=false,
     *
     *     @OA\Schema(type="integer")
     *   ),
     *
     *   @OA\Parameter(
     *     name="per_page",
     *     in="query",
     *     required=false,
     *
     *     @OA\Schema(type="integer")
     *   ),
     *
     *   @OA\Response(response=200, description="Journal entries data"),
     *   @OA\Response(response=403, description="Feature disabled"),
     *   @OA\Response(response=404, description="Company not found"),
     *   @OA\Response(response=422, description="Validation error")
     * )
     *
     * GET /api/v1/accounting/journal-entries?start_date=2025-01-01&end_date=2025-12-31
     */
    public function journalEntries(Request $request): JsonResponse
    {
        // Check feature flag
        if (! $this->isFeatureEnabled()) {
            return response()->json([
                'error' => 'Accounting backbone feature is disabled',
                'message' => 'Please enable FEATURE_ACCOUNTING_BACKBONE to access accounting reports',
            ], 403);
        }

        // Get company from header (set by company middleware)
        $companyId = $request->header('company');

        if (! $companyId) {
            return response()->json([
                'error' => 'Missing company header',
                'message' => 'The company header is required for accounting reports',
            ], 400);
        }

        $company = Company::find($companyId);

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
                'message' => 'The specified company does not exist',
            ], 404);
        }

        // Authorize user can access this company
        $this->authorize('view', $company);

        // Validate parameters
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'account_id' => 'nullable|integer',
            'account_code' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $journalEntries = $this->ifrsAdapter->getJournalEntries(
            $company,
            $validated['start_date'],
            $validated['end_date']
        );

        if (isset($journalEntries['error'])) {
            return response()->json($journalEntries, 400);
        }

        $entries = $journalEntries['entries'];

        // Filter by account if specified
        $filterAccountCode = $validated['account_code'] ?? null;
        if (! $filterAccountCode && ! empty($validated['account_id'])) {
            $account = \App\Models\Account::find($validated['account_id']);
            $filterAccountCode = $account?->code;
        }

        if ($filterAccountCode) {
            $entries = array_values(array_filter($entries, function ($entry) use ($filterAccountCode) {
                foreach ($entry['lines'] as $line) {
                    if ($line['account_code'] === $filterAccountCode) {
                        return true;
                    }
                }
                return false;
            }));
        }

        // Apply pagination if requested
        $page = $validated['page'] ?? 1;
        $perPage = $validated['per_page'] ?? 25;
        $total = count($entries);
        $offset = ($page - 1) * $perPage;
        $paginatedEntries = array_slice($entries, $offset, $perPage);

        return response()->json([
            'success' => true,
            'data' => $paginatedEntries,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage),
                'from' => $offset + 1,
                'to' => min($offset + $perPage, $total),
            ],
        ]);
    }

    /**
     * Export General Ledger to CSV
     */
    public function generalLedgerExport(Request $request)
    {
        if (! $this->isFeatureEnabled()) {
            return response()->json(['error' => 'Accounting backbone feature is disabled'], 403);
        }

        $companyId = $request->header('company');
        $company = Company::find($companyId);

        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $this->authorize('view', $company);

        $validated = $request->validate([
            'account_id' => 'required_without:account_code|integer',
            'account_code' => 'required_without:account_id|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $generalLedger = $this->ifrsAdapter->getGeneralLedger(
            $company,
            $validated['account_id'] ?? null,
            $validated['start_date'],
            $validated['end_date'],
            $validated['account_code'] ?? null
        );

        if (isset($generalLedger['error'])) {
            return response()->json($generalLedger, 400);
        }

        // Build CSV
        $csv = "Date,Reference,Description,Debit,Credit,Balance\n";

        $csv .= "Opening Balance,,,,," . number_format($generalLedger['opening_balance'], 2) . "\n";

        foreach ($generalLedger['entries'] as $entry) {
            $csv .= sprintf(
                "%s,%s,\"%s\",%s,%s,%s\n",
                $entry['date'],
                $entry['reference'],
                str_replace('"', '""', $entry['description']),
                $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '',
                $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '',
                number_format($entry['running_balance'], 2)
            );
        }

        $csv .= "Closing Balance,,,,," . number_format($generalLedger['closing_balance'], 2) . "\n";

        $accountCode = $generalLedger['account']['code'] ?? 'unknown';
        $filename = "general_ledger_{$accountCode}_{$validated['start_date']}_{$validated['end_date']}.csv";

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * Export Journal Entries to CSV
     */
    public function journalEntriesExport(Request $request)
    {
        if (! $this->isFeatureEnabled()) {
            return response()->json(['error' => 'Accounting backbone feature is disabled'], 403);
        }

        $companyId = $request->header('company');
        $company = Company::find($companyId);

        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $this->authorize('view', $company);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $journalEntries = $this->ifrsAdapter->getJournalEntries(
            $company,
            $validated['start_date'],
            $validated['end_date']
        );

        if (isset($journalEntries['error'])) {
            return response()->json($journalEntries, 400);
        }

        // Build CSV
        $csv = "Date,Reference,Type,Description,Account Code,Account Name,Debit,Credit\n";

        foreach ($journalEntries['entries'] as $entry) {
            foreach ($entry['lines'] as $line) {
                $csv .= sprintf(
                    "%s,%s,%s,\"%s\",%s,\"%s\",%s,%s\n",
                    $entry['date'],
                    $entry['reference'] ?? '',
                    $entry['transaction_type'],
                    str_replace('"', '""', $entry['narration']),
                    $line['account_code'],
                    str_replace('"', '""', $line['account_name']),
                    $line['debit'] > 0 ? number_format($line['debit'] / 100, 2) : '',
                    $line['credit'] > 0 ? number_format($line['credit'] / 100, 2) : ''
                );
            }
        }

        $filename = "journal_entries_{$validated['start_date']}_{$validated['end_date']}.csv";

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * MK voucher type labels (Правилник 75/2024).
     * Maps IFRS transaction types to Macedonian accounting voucher classifications.
     */
    private const MK_VOUCHER_TYPES = [
        'Invoice'       => ['code' => 'ИФ', 'label' => 'Излезна фактура'],
        'Bill'          => ['code' => 'УКФ', 'label' => 'Улазна (книговодствена) фактура'],
        'Credit Note'   => ['code' => 'КН', 'label' => 'Кредитно известување'],
        'Receipt'       => ['code' => 'ПР', 'label' => 'Приход (наплата)'],
        'Payment'       => ['code' => 'ИЗВ', 'label' => 'Извод (плаќање)'],
        'Cash Purchase' => ['code' => 'БЛ', 'label' => 'Благајна (готовинско плаќање)'],
        'Journal Entry' => ['code' => 'ИНТ', 'label' => 'Интерен налог (рачно книжење)'],
    ];

    /**
     * Download a single journal entry (налог за книжење) as PDF.
     *
     * MK-compliant: voucher type classification, amount in words,
     * source document reference, balance verification, 3 signatures.
     */
    public function journalEntryPdf(Request $request, int $transaction)
    {
        if (! $this->isFeatureEnabled()) {
            abort(403, 'Accounting backbone feature is disabled');
        }

        $companyId = $request->header('company');
        $company = Company::find($companyId);
        if (! $company) {
            abort(404, 'Company not found');
        }

        $this->authorize('view', $company);
        $company->load('address');

        $entry = $this->ifrsAdapter->getJournalEntry($company, $transaction);
        if (! $entry) {
            abort(404, 'Journal entry not found');
        }

        // Voucher type classification
        $txType = $entry['transaction_type'];
        $voucherInfo = self::MK_VOUCHER_TYPES[$txType] ?? ['code' => 'ИНТ', 'label' => 'Интерен налог'];
        $voucherTypeCode = $voucherInfo['code'];
        $voucherTypeLabel = $voucherInfo['label'];

        // Amount in words (Macedonian)
        $amountWords = '—';
        try {
            $amountToWords = app(\Modules\Mk\Services\AmountToWordsService::class);
            $amountWords = $amountToWords->convert($entry['total_debit'], 'MKD');
        } catch (\Throwable $e) {
            // AmountToWordsService not available — fall back to numeric
            $amountWords = number_format($entry['total_debit'] / 100, 2, ',', '.') . ' МКД';
        }

        // Source document reference (extract from narration if it contains invoice/bill refs)
        $sourceDocument = $this->extractSourceDocument($entry['narration']);

        // Company logo
        $companyLogo = null;
        if ($company->logo) {
            $logoPath = storage_path('app/public/' . $company->logo);
            if (file_exists($logoPath)) {
                $companyLogo = $logoPath;
            }
        }

        view()->share([
            'company' => $company,
            'company_logo' => $companyLogo,
            'entry' => $entry,
            'voucher_type_code' => $voucherTypeCode,
            'voucher_type_label' => $voucherTypeLabel,
            'amount_words' => $amountWords,
            'source_document' => $sourceDocument,
            'report_period' => \Carbon\Carbon::parse($entry['date'])->format('d.m.Y'),
        ]);

        $pdf = \PDF::loadView('app.pdf.reports.journal-entry');
        $ref = str_replace('/', '-', $entry['reference'] ?? $transaction);

        return $pdf->download("nalog_{$ref}.pdf");
    }

    /**
     * Extract source document reference from narration text.
     *
     * Looks for patterns like: Invoice INV-00123, Bill #456, Фактура ФАК-789, etc.
     */
    private function extractSourceDocument(string $narration): ?string
    {
        // Match common document reference patterns (MK and EN)
        $patterns = [
            '/(?:Invoice|Фактура|Влезна фактура|Bill|Сметка)\s*#?\s*[\w\-\/]+/iu',
            '/(?:INV|FAK|ФАК|BIL|REC|PAY|ИЗВ|ПР)[\-\s]*\d[\w\-\/]*/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $narration, $matches)) {
                return $matches[0];
            }
        }

        return null;
    }

    /**
     * Download ALL journal entries for a date range as a single multi-page PDF.
     *
     * Each налог за книжење gets its own page.
     */
    public function journalEntriesPdf(Request $request)
    {
        if (! $this->isFeatureEnabled()) {
            abort(403, 'Accounting backbone feature is disabled');
        }

        $companyId = $request->header('company');
        $company = Company::find($companyId);
        if (! $company) {
            abort(404, 'Company not found');
        }

        $this->authorize('view', $company);
        $company->load('address');

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $journalEntries = $this->ifrsAdapter->getJournalEntries(
            $company,
            $validated['start_date'],
            $validated['end_date']
        );

        if (isset($journalEntries['error']) || empty($journalEntries['entries'])) {
            abort(404, 'No journal entries found for this period');
        }

        $entries = $journalEntries['entries'];

        // Prepare each entry with voucher type, amount in words, source doc
        $amountToWords = null;
        try {
            $amountToWords = app(\Modules\Mk\Services\AmountToWordsService::class);
        } catch (\Throwable $e) {
            // fallback below
        }

        $companyLogo = null;
        if ($company->logo) {
            $logoPath = storage_path('app/public/' . $company->logo);
            if (file_exists($logoPath)) {
                $companyLogo = $logoPath;
            }
        }

        $preparedEntries = [];
        foreach ($entries as $entry) {
            $txType = $entry['transaction_type'];
            $voucherInfo = self::MK_VOUCHER_TYPES[$txType] ?? ['code' => 'ИНТ', 'label' => 'Интерен налог'];

            $words = '—';
            if ($amountToWords && $entry['total_debit'] > 0) {
                try {
                    $words = $amountToWords->convert($entry['total_debit'], 'MKD');
                } catch (\Throwable $e) {
                    $words = number_format($entry['total_debit'] / 100, 2, ',', '.') . ' МКД';
                }
            }

            $preparedEntries[] = [
                'entry' => $entry,
                'voucher_type_code' => $voucherInfo['code'],
                'voucher_type_label' => $voucherInfo['label'],
                'amount_words' => $words,
                'source_document' => $this->extractSourceDocument($entry['narration']),
            ];
        }

        view()->share([
            'company' => $company,
            'company_logo' => $companyLogo,
            'prepared_entries' => $preparedEntries,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ]);

        $pdf = \PDF::loadView('app.pdf.reports.journal-entries-bulk');

        $from = str_replace('-', '', $validated['start_date']);
        $to = str_replace('-', '', $validated['end_date']);

        return $pdf->download("nalozi_{$from}_{$to}.pdf");
    }

    /**
     * Reverse (storno) a posted journal entry.
     */
    public function reverseJournalEntry(Request $request, int $transaction): JsonResponse
    {
        if (! $this->isFeatureEnabled()) {
            return response()->json(['error' => 'Accounting backbone feature is disabled'], 403);
        }

        $companyId = $request->header('company');
        $company = Company::find($companyId);
        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $this->authorize('view', $company);

        $reversalId = $this->ifrsAdapter->reverseJournalEntry($company, $transaction);

        if (! $reversalId) {
            return response()->json(['error' => 'Failed to reverse entry'], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Сторно книжење е креирано',
            'reversal_transaction_id' => $reversalId,
        ]);
    }

    /**
     * Cash Flow Statement (indirect method)
     * GET /api/v1/accounting/cash-flow?start_date=2025-01-01&end_date=2025-12-31
     */
    public function cashFlow(Request $request): JsonResponse
    {
        if (! $this->isFeatureEnabled()) {
            return response()->json(['error' => 'Accounting backbone feature is disabled'], 403);
        }

        $companyId = $request->header('company');
        $company = Company::find($companyId);

        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $this->authorize('view', $company);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $result = $this->ifrsAdapter->getCashFlowStatement($company, $validated['start_date'], $validated['end_date']);

        if (isset($result['error'])) {
            return response()->json($result, 400);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Statement of Changes in Equity
     * GET /api/v1/accounting/equity-changes?year=2025
     */
    public function equityChanges(Request $request): JsonResponse
    {
        if (! $this->isFeatureEnabled()) {
            return response()->json(['error' => 'Accounting backbone feature is disabled'], 403);
        }

        $companyId = $request->header('company');
        $company = Company::find($companyId);

        if (! $company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $this->authorize('view', $company);

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2099',
        ]);

        $result = $this->ifrsAdapter->getEquityChanges($company, (int) $validated['year']);

        if (isset($result['error'])) {
            return response()->json($result, 400);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Cash Book — GL entries for cash accounts (class 10x).
     */
    public function cashBook(Request $request): JsonResponse
    {
        $company = Company::find($request->header('company'));
        $this->authorize('view', $company);

        $start = $request->input('start_date', now()->startOfYear()->toDateString());
        $end = $request->input('end_date', now()->toDateString());
        $accountCode = $request->input('account_code', '100');

        $result = $this->ifrsAdapter->getGeneralLedger($company->id, $accountCode, $start, $end);

        if (isset($result['error'])) {
            return response()->json($result, 400);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Cash Book CSV/PDF export.
     */
    public function cashBookExport(Request $request)
    {
        $company = Company::find($request->header('company'));
        $this->authorize('view', $company);

        $start = $request->input('start_date', now()->startOfYear()->toDateString());
        $end = $request->input('end_date', now()->toDateString());
        $accountCode = $request->input('account_code', '100');
        $format = $request->input('format', 'pdf');

        $result = $this->ifrsAdapter->getGeneralLedger($company->id, $accountCode, $start, $end);

        if ($format === 'csv') {
            $csv = "Account,Date,Document,Description,Debit,Credit,Balance\n";
            foreach ($result['entries'] ?? [] as $entry) {
                $csv .= implode(',', [
                    $accountCode,
                    $entry['date'] ?? '',
                    '"' . str_replace('"', '""', $entry['reference'] ?? '') . '"',
                    '"' . str_replace('"', '""', $entry['narration'] ?? '') . '"',
                    $entry['debit'] ?? 0,
                    $entry['credit'] ?? 0,
                    $entry['balance'] ?? 0,
                ]) . "\n";
            }
            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=cash-book-{$start}-to-{$end}.csv",
            ]);
        }

        $locale = \App\Models\CompanySetting::getSetting('language', $company->id) ?? 'mk';
        app()->setLocale($locale);

        $pdf = \PDF::loadView('app.pdf.reports.cash-book', [
            'company' => $company,
            'account_code' => $accountCode,
            'account_name' => $result['account_name'] ?? 'Каса',
            'from_date' => $start,
            'to_date' => $end,
            'opening_balance' => $result['opening_balance'] ?? 0,
            'entries' => $result['entries'] ?? [],
            'closing_balance' => $result['closing_balance'] ?? 0,
        ]);

        return $pdf->stream("cash-book-{$start}-to-{$end}.pdf");
    }

    /**
     * VAT Books — input/output invoices with VAT breakdown.
     */
    public function vatBooks(Request $request): JsonResponse
    {
        $company = Company::find($request->header('company'));
        $this->authorize('view', $company);

        $start = $request->input('start_date', now()->startOfYear()->toDateString());
        $end = $request->input('end_date', now()->toDateString());

        // Output VAT (invoices)
        $invoices = \App\Models\Invoice::where('company_id', $company->id)
            ->whereBetween('invoice_date', [$start, $end])
            ->where('status', '!=', 'DRAFT')
            ->select('id', 'invoice_number', 'invoice_date', 'sub_total', 'tax', 'total', 'due_amount')
            ->with(['customer:id,name'])
            ->orderBy('invoice_date')
            ->get();

        // Input VAT (bills)
        $bills = \App\Models\Bill::where('company_id', $company->id)
            ->whereBetween('bill_date', [$start, $end])
            ->where('status', '!=', 'DRAFT')
            ->select('id', 'bill_number', 'bill_date', 'sub_total', 'tax', 'total', 'due_amount')
            ->without(['supplier', 'currency', 'company'])
            ->with(['supplier:id,name'])
            ->orderBy('bill_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'output' => $invoices,
                'input' => $bills,
                'period' => ['start' => $start, 'end' => $end],
            ],
        ]);
    }

    /**
     * VAT Books CSV/PDF export.
     */
    public function vatBooksExport(Request $request)
    {
        $company = Company::find($request->header('company'));
        $this->authorize('view', $company);

        $start = $request->input('start_date', now()->startOfYear()->toDateString());
        $end = $request->input('end_date', now()->toDateString());
        $format = $request->input('format', 'pdf');

        $invoices = \App\Models\Invoice::where('company_id', $company->id)
            ->whereBetween('invoice_date', [$start, $end])
            ->where('status', '!=', 'DRAFT')
            ->with(['customer:id,name'])
            ->orderBy('invoice_date')
            ->get();

        $bills = \App\Models\Bill::where('company_id', $company->id)
            ->whereBetween('bill_date', [$start, $end])
            ->where('status', '!=', 'DRAFT')
            ->without(['supplier', 'currency', 'company'])
            ->with(['supplier:id,name'])
            ->orderBy('bill_date')
            ->get();

        if ($format === 'csv') {
            $csv = "Type,Number,Date,Party,SubTotal,Tax,Total\n";
            foreach ($invoices as $inv) {
                $csv .= "Output,{$inv->invoice_number},{$inv->invoice_date},\"{$inv->customer->name}\",{$inv->sub_total},{$inv->tax},{$inv->total}\n";
            }
            foreach ($bills as $bill) {
                $csv .= "Input,{$bill->bill_number},{$bill->bill_date},\"{$bill->supplier->name}\",{$bill->sub_total},{$bill->tax},{$bill->total}\n";
            }
            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=vat-books-{$start}-to-{$end}.csv",
            ]);
        }

        $locale = \App\Models\CompanySetting::getSetting('language', $company->id) ?? 'mk';
        app()->setLocale($locale);

        $pdf = \PDF::loadView('app.pdf.reports.vat-books', [
            'company' => $company,
            'from_date' => $start,
            'to_date' => $end,
            'output_invoices' => $invoices,
            'input_bills' => $bills,
        ]);

        return $pdf->stream("vat-books-{$start}-to-{$end}.pdf");
    }

    /**
     * Check if accounting backbone feature is enabled
     */
    protected function isFeatureEnabled(): bool
    {
        // Check Laravel Pennant feature flag or config
        if (function_exists('feature')) {
            return feature('accounting-backbone');
        }

        return config('ifrs.enabled', false) ||
               env('FEATURE_ACCOUNTING_BACKBONE', false);
    }
}

