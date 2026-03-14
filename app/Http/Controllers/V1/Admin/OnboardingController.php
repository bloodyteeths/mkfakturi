<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Supplier;
use App\Services\Banking\BankStatementOcrService;
use App\Services\Onboarding\BankDataAnalyzer;
use App\Services\Onboarding\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function __construct(
        protected OnboardingService $onboardingService,
        protected BankDataAnalyzer $bankDataAnalyzer,
    ) {}

    /**
     * GET /onboarding/progress
     *
     * Returns the onboarding checklist progress for the current company.
     */
    public function progress(Request $request): JsonResponse
    {
        $company = Company::find($request->header('company'));
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        return response()->json($this->onboardingService->getProgress($company));
    }

    /**
     * POST /onboarding/dismiss
     *
     * Dismisses the onboarding checklist widget on the dashboard.
     */
    public function dismiss(Request $request): JsonResponse
    {
        $company = Company::find($request->header('company'));
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $this->onboardingService->dismiss($company);

        return response()->json(['success' => true]);
    }

    /**
     * POST /onboarding/source
     *
     * Saves the selected migration source software.
     */
    public function source(Request $request): JsonResponse
    {
        $request->validate([
            'source' => 'required|string|in:pantheon,zonel,ekonomika,astral,b2b,excel,fresh',
        ]);

        $company = Company::find($request->header('company'));
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $this->onboardingService->saveSource($company, $request->source);

        return response()->json(['success' => true, 'source' => $request->source]);
    }

    /**
     * POST /onboarding/analyze-bank
     *
     * Analyzes uploaded bank statement transactions to extract counterparties.
     */
    public function analyzeBank(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:20480|mimes:csv,txt,pdf,jpg,jpeg,png',
        ]);

        $company = Company::find($request->header('company'));
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        $transactions = [];

        // For PDF/images, use OCR service
        if (in_array($extension, ['pdf', 'jpg', 'jpeg', 'png'])) {
            $ocrService = app(BankStatementOcrService::class);
            $result = $ocrService->parse(
                $file->getRealPath(),
                $file->getClientOriginalName()
            );
            $transactions = $result['transactions'] ?? [];
        }
        // For CSV/TXT, parse as bank statement CSV
        elseif (in_array($extension, ['csv', 'txt'])) {
            $transactions = $this->parseBankCsv($file->getRealPath());
        }

        if (empty($transactions)) {
            return response()->json([
                'success' => false,
                'message' => __('onboarding.no_transactions_found'),
                'suggested_suppliers' => [],
                'suggested_customers' => [],
            ]);
        }

        $analysis = $this->bankDataAnalyzer->analyzeTransactions($transactions);

        return response()->json([
            'success' => true,
            'transaction_count' => count($transactions),
            'suggested_suppliers' => $analysis['suggested_suppliers'],
            'suggested_customers' => $analysis['suggested_customers'],
        ]);
    }

    /**
     * POST /onboarding/confirm-entities
     *
     * Creates customers/suppliers from the bank analysis suggestions.
     */
    public function confirmEntities(Request $request): JsonResponse
    {
        $request->validate([
            'entities' => 'required|array|min:1',
            'entities.*.name' => 'required|string|max:255',
            'entities.*.type' => 'required|in:customer,supplier',
        ]);

        $company = Company::find($request->header('company'));
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $created = ['customers' => 0, 'suppliers' => 0];

        $currencyId = $company->settings()->where('option', 'currency')->value('value') ?? 1;

        foreach ($request->entities as $entity) {
            if ($entity['type'] === 'supplier') {
                $exists = Supplier::where('company_id', $company->id)
                    ->where('name', $entity['name'])
                    ->exists();
                if (!$exists) {
                    Supplier::create([
                        'company_id' => $company->id,
                        'name' => $entity['name'],
                        'currency_id' => $currencyId,
                    ]);
                    $created['suppliers']++;
                }
            } else {
                $exists = Customer::where('company_id', $company->id)
                    ->where('name', $entity['name'])
                    ->exists();
                if (!$exists) {
                    Customer::create([
                        'company_id' => $company->id,
                        'name' => $entity['name'],
                        'currency_id' => $currencyId,
                    ]);
                    $created['customers']++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'created' => $created,
        ]);
    }

    /**
     * POST /onboarding/complete
     *
     * Marks onboarding as completed.
     */
    public function complete(Request $request): JsonResponse
    {
        $company = Company::find($request->header('company'));
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $this->onboardingService->markCompleted($company);

        return response()->json(['success' => true]);
    }

    /**
     * GET /onboarding/migration-progress
     *
     * Returns per-step completion status for the MigrationHub.
     */
    public function migrationProgress(Request $request): JsonResponse
    {
        $company = Company::find($request->header('company'));
        if (!$company) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        return response()->json([
            'success' => true,
            'steps' => $this->onboardingService->getMigrationProgress($company),
        ]);
    }

    /**
     * Parse a CSV bank statement file into transaction arrays.
     */
    protected function parseBankCsv(string $filePath): array
    {
        $transactions = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [];
        }

        // Read header row
        $header = fgetcsv($handle, 0, $this->detectDelimiter($filePath));
        if (!$header) {
            fclose($handle);
            return [];
        }

        // Normalize headers
        $header = array_map(fn ($h) => mb_strtolower(trim($h)), $header);

        // Find column indices
        $amountIdx = $this->findColumn($header, ['amount', 'iznos', 'износ', 'suma', 'сума']);
        $nameIdx = $this->findColumn($header, ['counterparty_name', 'counterparty', 'name', 'ime', 'име', 'partner', 'партнер', 'nalogodavatel', 'налогодавател', 'primalac', 'примач']);
        $descIdx = $this->findColumn($header, ['description', 'opis', 'опис', 'cel', 'цел', 'purpose', 'namena', 'намена']);
        $debitIdx = $this->findColumn($header, ['debit', 'dolguva', 'должува', 'rashod', 'расход']);
        $creditIdx = $this->findColumn($header, ['credit', 'pobaruva', 'побарува', 'prihod', 'приход']);

        while (($row = fgetcsv($handle, 0, $this->detectDelimiter($filePath))) !== false) {
            if (empty(array_filter($row))) {
                continue;
            }

            $amount = 0;
            if ($amountIdx !== null) {
                $amount = $this->parseDecimal($row[$amountIdx] ?? '0');
            } elseif ($debitIdx !== null && $creditIdx !== null) {
                $debit = $this->parseDecimal($row[$debitIdx] ?? '0');
                $credit = $this->parseDecimal($row[$creditIdx] ?? '0');
                $amount = $credit > 0 ? $credit : -$debit;
            }

            $transactions[] = [
                'counterparty_name' => $nameIdx !== null ? trim($row[$nameIdx] ?? '') : '',
                'description' => $descIdx !== null ? trim($row[$descIdx] ?? '') : '',
                'amount' => $amount,
            ];
        }

        fclose($handle);

        return $transactions;
    }

    /**
     * Find a column index by trying multiple header name variants.
     */
    protected function findColumn(array $headers, array $variants): ?int
    {
        foreach ($variants as $variant) {
            $idx = array_search($variant, $headers);
            if ($idx !== false) {
                return $idx;
            }
        }

        return null;
    }

    /**
     * Detect CSV delimiter from file content.
     */
    protected function detectDelimiter(string $filePath): string
    {
        $sample = file_get_contents($filePath, false, null, 0, 8192);
        $delimiters = [';' => 0, ',' => 0, "\t" => 0, '|' => 0];

        foreach ($delimiters as $d => &$count) {
            $count = substr_count($sample, $d);
        }

        arsort($delimiters);

        return array_key_first($delimiters);
    }

    /**
     * Parse European-style decimal numbers (1.234,56 → 1234.56).
     */
    protected function parseDecimal(string $value): float
    {
        $value = trim($value);
        if (empty($value)) {
            return 0;
        }

        // European format: 1.234,56
        if (preg_match('/^\d{1,3}(\.\d{3})*(,\d{1,2})?$/', $value)) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }
        // Simple comma decimal: 1234,56
        elseif (str_contains($value, ',') && !str_contains($value, '.')) {
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }
}
// CLAUDE-CHECKPOINT
