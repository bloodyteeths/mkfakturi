<?php

namespace Modules\Mk\Partner\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use League\Csv\Reader;
use League\Csv\Writer;
use Modules\Mk\Partner\Services\PortfolioTierService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortfolioCompanyController extends Controller
{
    /**
     * List all portfolio-managed companies for the partner.
     */
    public function index(Request $request): JsonResponse
    {
        $partner = $this->getPartner();

        if (! $partner || ! $partner->portfolio_enabled) {
            return response()->json(['error' => 'Portfolio not activated'], 403);
        }

        $query = $partner->portfolioCompanies()->with('subscription');

        // Search filter
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('tax_id', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->query('status')) {
            switch ($status) {
                case 'paying':
                    $query->whereHas('subscription', function ($q) {
                        $q->whereIn('status', ['trial', 'active'])->where('plan', '!=', 'free');
                    });
                    break;
                case 'non_paying':
                    $query->where(function ($q) {
                        $q->whereDoesntHave('subscription')
                            ->orWhereHas('subscription', function ($sq) {
                                $sq->where('plan', 'free')
                                    ->orWhereNotIn('status', ['trial', 'active']);
                            });
                    });
                    break;
            }
        }

        $companies = $query->orderBy('name')->paginate($request->query('per_page', 25));

        // Add portfolio tier info to each company
        $companies->getCollection()->transform(function ($company) {
            $tierOverride = $company->pivot->portfolio_tier_override ?? null;
            $isPaying = $company->subscription
                && in_array($company->subscription->status, ['trial', 'active'])
                && $company->subscription->plan !== 'free';

            $company->portfolio_status = $isPaying ? 'paying' : ($tierOverride === 'standard' ? 'covered' : 'uncovered');
            $company->portfolio_tier = $isPaying ? ($company->subscription->plan ?? 'free') : ($tierOverride ?? 'accountant_basic');

            return $company;
        });

        return response()->json([
            'data' => $companies->items(),
            'meta' => [
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
            ],
            'stats' => $partner->getPortfolioStats(),
        ]);
    }

    /**
     * Create a new company in the partner's portfolio.
     */
    public function store(Request $request): JsonResponse
    {
        $partner = $this->getPartner();

        if (! $partner || ! $partner->portfolio_enabled) {
            return response()->json(['error' => 'Portfolio not activated'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tax_id' => 'required|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:3',
            'language' => 'nullable|string|max:5',
        ]);

        DB::beginTransaction();

        try {
            // Generate unique slug
            $slug = Str::slug($validated['name']);
            $originalSlug = $slug;
            $counter = 1;
            while (Company::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            // Create company
            $company = Company::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'tax_id' => $validated['tax_id'],
                'vat_number' => $validated['vat_number'] ?? null,
                'is_portfolio_managed' => true,
                'managing_partner_id' => $partner->id,
            ]);

            // Setup default data (roles, payment methods, units, settings)
            $company->setupDefaultData();

            // Apply currency/language overrides if provided
            if (! empty($validated['currency'])) {
                $company->settings()->updateOrCreate(
                    ['option' => 'currency'],
                    ['value' => $validated['currency']]
                );
            }
            if (! empty($validated['language'])) {
                $company->settings()->updateOrCreate(
                    ['option' => 'language'],
                    ['value' => $validated['language']]
                );
            }

            // Create partner_company_link
            $partner->companies()->attach($company->id, [
                'is_active' => true,
                'is_portfolio_managed' => true,
                'permissions' => json_encode([\App\Enums\PartnerPermission::FULL_ACCESS->value]),
                'invitation_status' => 'accepted',
                'accepted_at' => now(),
            ]);

            // Add partner's user to user_company (so partner can switch to this company)
            if ($partner->user) {
                $company->users()->syncWithoutDetaching([$partner->user_id]);
            }

            // Create trial subscription
            $trialDays = config('subscriptions.portfolio.company_trial_days', 14);
            $trialPlan = config('subscriptions.portfolio.company_trial_plan', 'standard');

            CompanySubscription::create([
                'company_id' => $company->id,
                'plan' => $trialPlan,
                'status' => 'trial',
                'trial_ends_at' => now()->addDays($trialDays),
                'started_at' => now(),
            ]);

            // Recalculate portfolio tiers
            $tierService = app(PortfolioTierService::class);
            $tierService->recalculate($partner->fresh());

            DB::commit();

            return response()->json([
                'message' => 'Company created successfully',
                'company' => $company->fresh()->load('subscription'),
                'stats' => $partner->fresh()->getPortfolioStats(),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Portfolio company creation failed', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to create company'], 500);
        }
    }

    /**
     * Get details of a specific portfolio company.
     */
    public function show(int $companyId): JsonResponse
    {
        $partner = $this->getPartner();

        if (! $partner || ! $partner->portfolio_enabled) {
            return response()->json(['error' => 'Portfolio not activated'], 403);
        }

        $company = $partner->portfolioCompanies()
            ->where('companies.id', $companyId)
            ->with('subscription')
            ->first();

        if (! $company) {
            return response()->json(['error' => 'Company not found in portfolio'], 404);
        }

        return response()->json(['company' => $company]);
    }

    /**
     * Remove a company from the portfolio.
     */
    public function destroy(int $companyId): JsonResponse
    {
        $partner = $this->getPartner();

        if (! $partner || ! $partner->portfolio_enabled) {
            return response()->json(['error' => 'Portfolio not activated'], 403);
        }

        $exists = DB::table('partner_company_links')
            ->where('partner_id', $partner->id)
            ->where('company_id', $companyId)
            ->where('is_portfolio_managed', true)
            ->exists();

        if (! $exists) {
            return response()->json(['error' => 'Company not found in portfolio'], 404);
        }

        DB::beginTransaction();

        try {
            // Remove portfolio flags
            DB::table('partner_company_links')
                ->where('partner_id', $partner->id)
                ->where('company_id', $companyId)
                ->update([
                    'is_portfolio_managed' => false,
                    'portfolio_tier_override' => null,
                ]);

            Company::where('id', $companyId)->update([
                'is_portfolio_managed' => false,
                'managing_partner_id' => null,
            ]);

            // Recalculate tiers
            $tierService = app(PortfolioTierService::class);
            $tierService->recalculate($partner->fresh());

            DB::commit();

            return response()->json([
                'message' => 'Company removed from portfolio',
                'stats' => $partner->fresh()->getPortfolioStats(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to remove company'], 500);
        }
    }

    /**
     * Download a CSV template for bulk company import.
     */
    public function template(): StreamedResponse
    {
        $csv = Writer::createFromString('');
        $csv->insertOne([
            'company_name', 'tax_id', 'vat_number', 'address', 'city',
            'postal_code', 'email', 'phone', 'currency', 'language',
        ]);
        $csv->insertOne([
            'ДООЕЛ Пример', 'MK4030001234567', 'MK4030001234567',
            'ул. Пример 1', 'Скопје', '1000', 'info@primer.mk',
            '070/123-456', 'MKD', 'mk',
        ]);

        return response()->streamDownload(function () use ($csv) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel compatibility
            echo $csv->toString();
        }, 'facturino-company-template.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Preview a bulk import file (CSV/XLSX). Returns parsed rows + validation.
     * Data is cached for 15 minutes for the confirm step.
     */
    public function importPreview(Request $request): JsonResponse
    {
        $partner = $this->getPartner();
        if (! $partner || ! $partner->portfolio_enabled) {
            return response()->json(['error' => 'Portfolio not activated'], 403);
        }

        $request->validate([
            'file' => 'required|file|max:5120|mimes:csv,txt,xlsx,xls',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        // Parse rows from file
        $rawRows = [];
        if (in_array($extension, ['xlsx', 'xls'])) {
            $rawRows = $this->parseExcel($file->getRealPath());
        } else {
            $rawRows = $this->parseCsv($file->getRealPath());
        }

        if (empty($rawRows)) {
            return response()->json(['error' => 'No data found in file'], 422);
        }

        // Map columns using flexible aliases
        $mappedRows = $this->mapColumns($rawRows);

        // Validate and check duplicates
        $existingTaxIds = DB::table('partner_company_links')
            ->where('partner_id', $partner->id)
            ->join('companies', 'companies.id', '=', 'partner_company_links.company_id')
            ->pluck('companies.tax_id')
            ->filter()
            ->map(fn ($id) => strtoupper(trim($id)))
            ->toArray();

        $valid = [];
        $invalid = [];
        $duplicates = [];
        $seenTaxIds = [];

        foreach ($mappedRows as $i => $row) {
            $row['_row'] = $i + 2; // 1-indexed + header
            $name = trim($row['company_name'] ?? '');
            $taxId = strtoupper(trim($row['tax_id'] ?? ''));

            if (empty($name) && empty($taxId)) {
                continue; // Skip empty rows
            }

            if (empty($name)) {
                $row['_error'] = 'Missing company name';
                $invalid[] = $row;
            } elseif (empty($taxId)) {
                $row['_error'] = 'Missing tax ID';
                $invalid[] = $row;
            } elseif (in_array($taxId, $existingTaxIds)) {
                $row['_error'] = 'Already in portfolio';
                $duplicates[] = $row;
            } elseif (in_array($taxId, $seenTaxIds)) {
                $row['_error'] = 'Duplicate in file';
                $duplicates[] = $row;
            } else {
                $seenTaxIds[] = $taxId;
                $valid[] = $row;
            }
        }

        // Cache for confirm step
        $importId = Str::uuid()->toString();
        Cache::put("portfolio_import:{$partner->id}:{$importId}", $valid, 900);

        return response()->json([
            'import_id' => $importId,
            'total' => count($valid) + count($invalid) + count($duplicates),
            'valid' => count($valid),
            'invalid' => count($invalid),
            'duplicates' => count($duplicates),
            'preview' => [
                'valid' => array_slice($valid, 0, 20),
                'invalid' => array_slice($invalid, 0, 10),
                'duplicates' => array_slice($duplicates, 0, 10),
            ],
        ]);
    }

    /**
     * Confirm a previously previewed import. Creates companies from cached data.
     */
    public function importConfirm(Request $request): JsonResponse
    {
        $partner = $this->getPartner();
        if (! $partner || ! $partner->portfolio_enabled) {
            return response()->json(['error' => 'Portfolio not activated'], 403);
        }

        $request->validate(['import_id' => 'required|string']);

        $cacheKey = "portfolio_import:{$partner->id}:{$request->import_id}";
        $rows = Cache::get($cacheKey);

        if (! $rows) {
            return response()->json(['error' => 'Import expired or not found. Please upload again.'], 404);
        }

        Cache::forget($cacheKey);

        $imported = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $row) {
                try {
                    $this->createPortfolioCompany($partner, $row);
                    $imported++;
                } catch (\Throwable $e) {
                    $errors[] = [
                        'row' => $row['_row'] ?? null,
                        'name' => $row['company_name'] ?? '?',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Recalculate tiers once
            if ($imported > 0) {
                $tierService = app(PortfolioTierService::class);
                $tierService->recalculate($partner->fresh());
            }

            DB::commit();

            return response()->json([
                'imported' => $imported,
                'errors' => $errors,
                'stats' => $partner->fresh()->getPortfolioStats(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Portfolio bulk import failed', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Create a single company from import row data (reused by store() logic).
     */
    protected function createPortfolioCompany(Partner $partner, array $row): Company
    {
        $name = trim($row['company_name']);
        $slug = Str::slug($name);
        $originalSlug = $slug ?: 'company';
        $slug = $originalSlug;
        $counter = 1;
        while (Company::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        $company = Company::create([
            'name' => $name,
            'slug' => $slug,
            'tax_id' => trim($row['tax_id'] ?? ''),
            'vat_number' => trim($row['vat_number'] ?? '') ?: null,
            'is_portfolio_managed' => true,
            'managing_partner_id' => $partner->id,
        ]);

        $company->setupDefaultData();

        // Apply optional fields
        if (! empty($row['address'])) {
            $company->address()->updateOrCreate([], [
                'address_street_1' => trim($row['address']),
                'city' => trim($row['city'] ?? ''),
                'zip' => trim($row['postal_code'] ?? ''),
                'country_id' => 128, // Macedonia
            ]);
        }
        if (! empty($row['email'])) {
            $company->update(['email' => trim($row['email'])]);
        }
        if (! empty($row['phone'])) {
            $company->update(['phone' => trim($row['phone'])]);
        }

        $currency = trim($row['currency'] ?? 'MKD');
        $language = trim($row['language'] ?? 'mk');
        $company->settings()->updateOrCreate(['option' => 'currency'], ['value' => $currency]);
        $company->settings()->updateOrCreate(['option' => 'language'], ['value' => $language]);

        // Link to partner
        $partner->companies()->attach($company->id, [
            'is_active' => true,
            'is_portfolio_managed' => true,
            'permissions' => json_encode([\App\Enums\PartnerPermission::FULL_ACCESS->value]),
            'invitation_status' => 'accepted',
            'accepted_at' => now(),
        ]);

        // Add partner user access
        if ($partner->user) {
            $company->users()->syncWithoutDetaching([$partner->user_id]);
        }

        // Trial subscription
        $trialDays = config('subscriptions.portfolio.company_trial_days', 14);
        $trialPlan = config('subscriptions.portfolio.company_trial_plan', 'standard');
        CompanySubscription::create([
            'company_id' => $company->id,
            'plan' => $trialPlan,
            'status' => 'trial',
            'trial_ends_at' => now()->addDays($trialDays),
            'started_at' => now(),
        ]);

        return $company;
    }

    /**
     * Parse CSV file into array of associative rows.
     */
    protected function parseCsv(string $path): array
    {
        $content = file_get_contents($path);
        // Remove BOM if present
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);

        // Try common encodings for Cyrillic
        if (! mb_check_encoding($content, 'UTF-8') || preg_match('/[\x80-\xFF]/', $content)) {
            foreach (['Windows-1251', 'ISO-8859-5', 'CP866'] as $enc) {
                $converted = @mb_convert_encoding($content, 'UTF-8', $enc);
                if ($converted && mb_check_encoding($converted, 'UTF-8')) {
                    $content = $converted;
                    break;
                }
            }
        }

        $csv = Reader::createFromString($content);

        // Detect delimiter
        $firstLine = strtok($content, "\n");
        if (substr_count($firstLine, "\t") > substr_count($firstLine, ',') && substr_count($firstLine, "\t") > substr_count($firstLine, ';')) {
            $csv->setDelimiter("\t");
        } elseif (substr_count($firstLine, ';') > substr_count($firstLine, ',')) {
            $csv->setDelimiter(';');
        }

        $csv->setHeaderOffset(0);

        $rows = [];
        foreach ($csv->getRecords() as $record) {
            $rows[] = $record;
        }

        return $rows;
    }

    /**
     * Parse Excel file into array of associative rows.
     */
    protected function parseExcel(string $path): array
    {
        $rows = [];
        $data = \Maatwebsite\Excel\Facades\Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\WithHeadingRow {}, $path);

        if (! empty($data[0])) {
            $rows = $data[0];
        }

        return $rows;
    }

    /**
     * Map column names using flexible aliases to standard field names.
     */
    protected function mapColumns(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        $aliases = [
            'company_name' => ['company_name', 'name', 'ime', 'cl_ime', 'компанија', 'naziv', 'firma', 'emri'],
            'tax_id' => ['tax_id', 'edb', 'cl_edb', 'даночен_број', 'embs', 'tax_number'],
            'vat_number' => ['vat_number', 'vat', 'ddv', 'ддв'],
            'address' => ['address', 'adresa', 'cl_adres', 'адреса'],
            'city' => ['city', 'mesto', 'cl_mesto', 'град', 'qytet'],
            'postal_code' => ['postal_code', 'postal', 'post', 'cl_post', 'поштенски', 'zip'],
            'email' => ['email', 'e-mail', 'e_mail', 'емаил', 'mail'],
            'phone' => ['phone', 'tel', 'cl_tel1', 'телефон', 'telefon'],
            'currency' => ['currency', 'valuta', 'валута'],
            'language' => ['language', 'jazik', 'јазик', 'lang'],
        ];

        // Build mapping from actual column names to standard names
        $headers = array_keys($rows[0]);
        $columnMap = [];
        foreach ($aliases as $standard => $alts) {
            foreach ($headers as $header) {
                $normalized = strtolower(trim($header));
                if (in_array($normalized, $alts)) {
                    $columnMap[$header] = $standard;
                    break;
                }
            }
        }

        // Map each row
        return array_map(function ($row) use ($columnMap) {
            $mapped = [];
            foreach ($row as $key => $value) {
                $standard = $columnMap[$key] ?? null;
                if ($standard) {
                    $mapped[$standard] = $value;
                }
            }

            return $mapped;
        }, $rows);
    }

    /**
     * Get partner from authenticated user.
     */
    protected function getPartner(): ?Partner
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if ($user->role === 'super admin') {
            $partnerId = request()->query('partner_id');
            if ($partnerId) {
                return Partner::find($partnerId);
            }

            return null;
        }

        return Partner::where('user_id', $user->id)->first();
    }
}
// CLAUDE-CHECKPOINT
