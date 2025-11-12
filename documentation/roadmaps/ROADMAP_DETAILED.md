# FAKTURINO v1 - COMPLETE FILE-LEVEL IMPLEMENTATION ROADMAP
## 520 Engineering Hours | 18 PR-Sized Milestones | Feature-Flagged Deployment

---

## 📋 EXECUTIVE SUMMARY

**Project:** Fakturino - Macedonian-localized accounting fork of InvoiceShelf
**Timeline:** 13 weeks (1 engineer @ 40 hrs/week)
**Total Hours:** 520 hours across 18 milestones
**Dependencies:** 6 new composer packages, 25+ migrations, 60+ tests
**Safety:** Feature flags for all major features, mocked data preserved until staging validation

**Current State (Per Analysis):**
- ✅ UBL export (generation/validation/signing) - COMPLETE
- ✅ Multi-gateway payments (CPAY/Paddle) - CODE EXISTS, dependencies missing
- ✅ Financial scaffolding (BankTransaction, Commission) - TABLES CREATED
- ❌ Universal Migration Wizard - dependencies missing (league/csv, phpoffice/phpspreadsheet)
- ❌ PSD2 bank sync - oak-labs-io/psd2 package missing, jobs incomplete
- ❌ Monitoring - Prometheus/Telescope disabled
- ❌ Partner portal - UI mocked, NO backend APIs

**Roadmap Structure:**
- **Milestones 0-2:** Foundation (feature flags, accounting backbone)
- **Milestones 3-6:** Core features (PSD2, migration, partner portal, payments)
- **Milestones 7-12:** Advanced features (invoicing, UBL, VAT, reconciliation, MCP, client portal)
- **Milestones 13-18:** Production readiness (monitoring, performance, security, UX, docs, staging)

---

## 🎯 MILESTONE BREAKDOWN

### MILESTONE 0: Foundation & Feature Flag Infrastructure (8 hours) ✅ DETAILED ABOVE

**Dependencies:**
```bash
composer require laravel/pennant:^1.10 \
  laravel/cashier-paddle:^2.8 \
  league/csv:^9.16 \
  phpoffice/phpspreadsheet:^2.3 \
  spatie/laravel-queueable-action:^2.17 \
  symfony/http-client:^7.2
```

**Key Files:**
- `config/features.php` - Feature flag configuration
- `app/Providers/FeatureFlagServiceProvider.php` - Pennant integration
- `app/Helpers/FeatureHelper.php` - Feature flag helpers
- `.env.example` - All new environment variables

**Acceptance:**
- ✅ All packages install successfully
- ✅ Feature flags default to safe values
- ✅ `php artisan test --filter=FeatureFlagTest` passes

---

### MILESTONE 1: Core Accounting Backbone - Phase 1 (Models & Migrations) (24 hours) ✅ DETAILED ABOVE

**Feature Flag:** `accounting-backbone`

**Migrations:**
- `2025_08_01_100000_create_chart_of_accounts_table.php`
- `2025_08_01_100100_create_journal_entries_table.php`
- `2025_08_01_100200_create_journal_entry_lines_table.php`
- `2025_08_01_100300_create_fiscal_periods_table.php`

**Models:**
- `app/Models/ChartOfAccount.php` - COA with hierarchy support
- `app/Models/JournalEntry.php` - Double-entry journal with balance validation
- `app/Models/JournalEntryLine.php` - Journal line items with FX support
- `app/Models/FiscalPeriod.php` - Period close/lock logic

**Seeder:**
- `database/seeders/MkChartOfAccountsSeeder.php` - Macedonian COA (1000-5999 accounts)

**Tests:**
- `tests/Unit/JournalEntryTest.php` - Balance validation, posting logic
- `tests/Feature/ChartOfAccountsTest.php` - Hierarchy, system accounts

**Acceptance:**
- ✅ `php artisan migrate` creates accounting tables
- ✅ Seeder creates 16 standard MK accounts
- ✅ Can create balanced journal entry and post
- ✅ Double-entry validation prevents unbalanced entries

---

### MILESTONE 2: Core Accounting Backbone - Phase 2 (Services & API) (20 hours) ✅ DETAILED ABOVE

**Feature Flag:** `accounting-backbone`

**Services:**
- `app/Services/Accounting/JournalEntryService.php` - Create, post, reverse entries
- `app/Services/Accounting/GeneralLedgerService.php` - TB, GL, account balances

**Controllers:**
- `app/Http/Controllers/V1/Admin/Accounting/ChartOfAccountsController.php`
- `app/Http/Controllers/V1/Admin/Accounting/JournalEntriesController.php`
- `app/Http/Controllers/V1/Admin/Accounting/ReportsController.php`

**API Endpoints:**
- `GET /api/v1/admin/{company}/accounting/accounts` - List COA
- `POST /api/v1/admin/{company}/accounting/accounts` - Create account
- `POST /api/v1/admin/{company}/accounting/journal-entries` - Create entry
- `POST /api/v1/admin/{company}/accounting/journal-entries/{id}/post` - Post entry
- `POST /api/v1/admin/{company}/accounting/journal-entries/{id}/reverse` - Reverse entry
- `GET /api/v1/admin/{company}/accounting/reports/trial-balance` - TB report
- `GET /api/v1/admin/{company}/accounting/reports/general-ledger` - GL report

**Tests:**
- `tests/Feature/Accounting/JournalEntryApiTest.php`

**Acceptance:**
- ✅ Can create journal entry via API
- ✅ Can post entry and verify status change
- ✅ TB report returns balanced totals
- ✅ Feature flag OFF returns 404

---

### MILESTONE 3: PSD2 Banking Infrastructure & OAuth Token Storage (16 hours) ✅ DETAILED ABOVE

**Feature Flag:** `psd2-banking`

**Dependencies:** `symfony/http-client:^7.2` (oak-labs-io/psd2 doesn't exist)

**Migration:**
- `2025_08_02_100000_create_bank_tokens_table.php` - Encrypted token storage

**Models:**
- `app/Models/BankToken.php` - OAuth tokens with expiry detection

**Services:**
- `app/Services/Banking/Psd2Client.php` - Base PSD2 OAuth client
- `Modules/Mk/Services/StopanskaGateway.php` (updated) - Full OAuth implementation
- `Modules/Mk/Services/NlbGateway.php` (updated) - NLB OAuth
- `Modules/Mk/Services/KomerGateway.php` (updated) - Komer OAuth

**Controllers:**
- `Modules/Mk/Http/BankAuthController.php` (updated) - OAuth flow, token status, disconnect

**Jobs:**
- `Modules/Mk/Jobs/SyncStopanska.php` (updated) - Uses BankToken model
- `Modules/Mk/Jobs/SyncNlb.php` (updated)
- `Modules/Mk/Jobs/SyncKomer.php` (updated)

**API Endpoints:**
- `POST /api/v1/admin/{company}/banking/auth/{bankCode}` - Initiate OAuth
- `GET /banking/callback/{company}/{bank}` - OAuth callback
- `GET /api/v1/admin/{company}/banking/status/{bankCode}` - Token status
- `DELETE /api/v1/admin/{company}/banking/disconnect/{bankCode}` - Disconnect bank

**Tests:**
- `tests/Unit/BankTokenTest.php` - Token encryption, expiry detection
- `tests/Feature/Banking/StopanskaIntegrationTest.php` - OAuth flow, token refresh

**Acceptance:**
- ✅ Can initiate OAuth and receive authorization URL
- ✅ Token stored encrypted in database
- ✅ Token auto-refreshes when expiring within 5 minutes
- ✅ Sync job fetches transactions without duplicates
- ✅ Manual test: Connect → authorize → sync → verify transactions

**Rollback:**
```bash
php artisan migrate:rollback --step=1
# .env: FEATURE_PSD2_BANKING=false
```

---

### MILESTONE 4: Migration Wizard - CSV/Excel Import Infrastructure (20 hours) ✅ DETAILED ABOVE

**Feature Flag:** `migration-wizard`

**Dependencies:** Already added in M0 (league/csv, phpoffice/phpspreadsheet)

**Models:**
- `app/Models/ImportJob.php` - Import job tracking with progress

**Services:**
- `app/Services/Migration/Parsers/CsvParserService.php` (updated) - League CSV integration
- `app/Services/Migration/Parsers/ExcelParserService.php` (updated) - PhpSpreadsheet integration
- `app/Services/Migration/Mappers/OnivoMapper.php` - Onivo preset mappings
- `app/Services/Migration/Mappers/MegasoftMapper.php` - Megasoft preset mappings

**Controllers:**
- `app/Http/Controllers/V1/Admin/MigrationController.php` (updated) - Full wizard flow

**Jobs:**
- `app/Jobs/Migration/ProcessImportJob.php` - Async import processing

**API Endpoints:**
- `POST /api/v1/admin/{company}/migration/upload` - Upload file
- `GET /api/v1/admin/{company}/migration/imports/{job}/preview` - Preview data
- `GET /api/v1/admin/{company}/migration/presets/{entityType}/{source}` - Get presets
- `POST /api/v1/admin/{company}/migration/imports/{job}/mapping` - Save mapping
- `POST /api/v1/admin/{company}/migration/imports/{job}/dry-run` - Validate only
- `POST /api/v1/admin/{company}/migration/imports/{job}/import` - Execute import
- `GET /api/v1/admin/{company}/migration/imports/{job}/status` - Check progress
- `GET /api/v1/admin/{company}/migration/imports/{job}/errors` - Download error CSV

**Tests:**
- `tests/Feature/Migration/CsvImportTest.php`

**Acceptance:**
- ✅ Upload CSV/Excel file
- ✅ Auto-detect delimiter, encoding, headers
- ✅ Apply Onivo/Megasoft presets
- ✅ Dry run validates without inserting
- ✅ Actual import processes and commits
- ✅ Error CSV contains row-level failures
- ✅ Macedonian decimals (1.234,56) converted correctly
- ✅ Manual: 100-row CSV → map → dry run → import → verify data

**Rollback:**
```bash
# .env: FEATURE_MIGRATION_WIZARD=false
DB::table('import_temp_customers')->truncate();
\App\Models\ImportJob::where('status', '!=', 'completed')->delete();
```

---

### MILESTONE 5: Partner Portal APIs (Replace Mocked Data) (32 hours)

**Feature Flag:** `partner-portal` (backend), `partner-mocked-data` (safety flag)

**Models:**
- `app/Models/Partner.php` (already exists) - Add relationships
- `app/Models/Commission.php` (already exists) - Add calculation helpers
- `app/Models/PartnerCompanyLink.php` (already exists) - Client attribution

**Services:**
```php
// app/Services/Partner/CommissionCalculatorService.php
<?php
namespace App\Services\Partner;

use App\Models\Partner;
use App\Models\Invoice;
use App\Models\Commission;

class CommissionCalculatorService
{
    public function calculateInvoiceCommission(Invoice $invoice): ?Commission
    {
        // Find partner attribution
        $link = \App\Models\PartnerCompanyLink::where('company_id', $invoice->company_id)
            ->whereNotNull('partner_id')
            ->first();

        if (!$link || !$link->partner) {
            return null;
        }

        $partner = $link->partner;
        $rate = $partner->commission_rate ?? 0;

        if ($rate <= 0) {
            return null;
        }

        // Calculate commission
        $baseAmount = $invoice->total;
        $commissionAmount = ($baseAmount * $rate) / 100;

        return Commission::create([
            'partner_id' => $partner->id,
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'type' => 'invoice',
            'base_amount' => $baseAmount,
            'rate' => $rate,
            'amount' => $commissionAmount,
            'status' => 'pending',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ]);
    }

    public function getPartnerDashboardStats(Partner $partner, $startDate, $endDate): array
    {
        $links = \App\Models\PartnerCompanyLink::where('partner_id', $partner->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $commissions = Commission::where('partner_id', $partner->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $invoices = \App\Models\Invoice::whereIn('company_id', function ($query) use ($partner) {
            $query->select('company_id')
                ->from('partner_company_links')
                ->where('partner_id', $partner->id);
        })
        ->whereBetween('invoice_date', [$startDate, $endDate])
        ->count();

        return [
            'active_clients' => $links,
            'processed_invoices' => $invoices,
            'total_commissions' => $commissions->sum('amount'),
            'pending_commissions' => $commissions->where('status', 'pending')->sum('amount'),
            'paid_commissions' => $commissions->where('status', 'paid')->sum('amount'),
        ];
    }
}
```

**Controllers:**
```php
// app/Http/Controllers/V1/Partner/PartnerApiController.php
<?php
namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Services\Partner\CommissionCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Laravel\Pennant\Feature;

class PartnerApiController extends Controller
{
    public function __construct(protected CommissionCalculatorService $commissionService)
    {
        if (!Feature::active('partner-portal')) {
            abort(404, 'Partner portal not enabled');
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $partner = Partner::where('email', $request->email)->first();

        if (!$partner || !Hash::check($request->password, $partner->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        $token = $partner->createToken('partner-token')->plainTextToken;

        return response()->json([
            'partner' => $partner,
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out']);
    }

    public function dashboard(Request $request)
    {
        $partner = $request->user();

        // Check if mocked data is enabled (safety flag)
        if (Feature::active('partner-mocked-data')) {
            return response()->json([
                'mocked' => true,
                'data' => [
                    'active_clients' => 12,
                    'monthly_commissions' => 85000,
                    'processed_invoices' => 234,
                ],
            ]);
        }

        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $stats = $this->commissionService->getPartnerDashboardStats($partner, $startDate, $endDate);

        return response()->json($stats);
    }

    public function commissions(Request $request)
    {
        $partner = $request->user();

        if (Feature::active('partner-mocked-data')) {
            return response()->json([
                'mocked' => true,
                'data' => [],
            ]);
        }

        $commissions = \App\Models\Commission::where('partner_id', $partner->id)
            ->with('invoice', 'company')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($request->get('per_page', 25));

        return response()->json($commissions);
    }

    public function clients(Request $request)
    {
        $partner = $request->user();

        if (Feature::active('partner-mocked-data')) {
            return response()->json([
                'mocked' => true,
                'data' => [],
            ]);
        }

        $links = \App\Models\PartnerCompanyLink::where('partner_id', $partner->id)
            ->with('company')
            ->latest()
            ->paginate($request->get('per_page', 25));

        return response()->json($links);
    }

    public function profile(Request $request)
    {
        return response()->json($request->user());
    }

    public function updateProfile(Request $request)
    {
        $partner = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:50',
        ]);

        $partner->update($validated);

        return response()->json($partner);
    }
}
```

**Routes:**
```php
// routes/api.php (add)
use App\Http\Controllers\V1\Partner\PartnerApiController;

Route::prefix('v1/partner')->group(function () {
    Route::post('auth/login', [PartnerApiController::class, 'login']);

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::post('auth/logout', [PartnerApiController::class, 'logout']);
        Route::get('dashboard', [PartnerApiController::class, 'dashboard']);
        Route::get('commissions', [PartnerApiController::class, 'commissions']);
        Route::get('clients', [PartnerApiController::class, 'clients']);
        Route::get('profile', [PartnerApiController::class, 'profile']);
        Route::put('profile', [PartnerApiController::class, 'updateProfile']);
    });
});
```

**Vue/Pinia Updates:**
```javascript
// resources/scripts/partner/stores/partner.js (update)
import { defineStore } from 'pinia';
import axios from 'axios';
import { Feature } from '@/utils/features';

export const usePartnerStore = defineStore('partner', {
  state: () => ({
    stats: null,
    loading: false,
  }),

  actions: {
    async fetchDashboard(startDate, endDate) {
      this.loading = true;

      try {
        const { data } = await axios.get('/api/v1/partner/dashboard', {
          params: { start_date: startDate, end_date: endDate },
        });

        // Check if mocked data is returned
        if (data.mocked) {
          console.warn('Partner portal using mocked data');
          this.stats = data.data;
        } else {
          this.stats = data;
        }
      } catch (error) {
        console.error('Failed to fetch dashboard:', error);
      } finally {
        this.loading = false;
      }
    },
  },
});
```

**Tests:**
```php
// tests/Feature/Partner/PartnerApiTest.php
<?php
namespace Tests\Feature\Partner;

use Tests\TestCase;
use App\Models\Partner;
use App\Models\Commission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class PartnerApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['features.partner_portal' => true]);
    }

    public function test_partner_can_login(): void
    {
        $partner = Partner::factory()->create([
            'email' => 'partner@test.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/api/v1/partner/auth/login', [
            'email' => 'partner@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['partner', 'token']);
    }

    public function test_partner_dashboard_returns_stats(): void
    {
        config(['features.partner_mocked_data' => false]);

        $partner = Partner::factory()->create();
        Commission::factory()->count(5)->create(['partner_id' => $partner->id]);

        Sanctum::actingAs($partner, [], 'partner');

        $response = $this->getJson('/api/v1/partner/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'active_clients',
            'processed_invoices',
            'total_commissions',
        ]);
    }

    public function test_mocked_data_flag_returns_mock(): void
    {
        config(['features.partner_mocked_data' => true]);

        $partner = Partner::factory()->create();
        Sanctum::actingAs($partner, [], 'partner');

        $response = $this->getJson('/api/v1/partner/dashboard');

        $response->assertStatus(200);
        $response->assertJson(['mocked' => true]);
    }
}
```

**Acceptance Criteria:**
- ✅ Partner can login and receive Sanctum token
- ✅ Dashboard API returns real stats when `partner-mocked-data` OFF
- ✅ Dashboard returns mocked data when flag ON (safety)
- ✅ Commissions API lists partner's commissions with pagination
- ✅ Clients API lists attributed companies
- ✅ Profile update works
- ✅ Vue Pinia store updated to call real APIs
- ✅ Manual: Login → dashboard shows real data → create commission → verify in dashboard

**Rollback:**
```bash
# .env: FEATURE_PARTNER_PORTAL=false
# OR keep mocked data: FEATURE_PARTNER_MOCKED_DATA=true
```

---

### MILESTONE 6: Paddle/CPAY Payment Completion (16 hours)

**Feature Flag:** `advanced-payments` (new)

**Dependencies:** Already added in M0 (laravel/cashier-paddle, bojanvmk/laravel-cpay if exists)

**Services:**
```php
// app/Services/Payment/PaddlePaymentService.php
<?php
namespace App\Services/Payment;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class PaddlePaymentService
{
    public function createCheckoutSession(Invoice $invoice, array $customerData): array
    {
        $paddle = new \Paddle\SDK(
            config('services.paddle.vendor_id'),
            config('services.paddle.api_key'),
            config('services.paddle.environment')
        );

        $checkout = $paddle->checkouts->create([
            'items' => [
                [
                    'price_id' => config('services.paddle.price_id'),
                    'quantity' => 1,
                ],
            ],
            'custom_data' => [
                'invoice_id' => $invoice->id,
                'company_id' => $invoice->company_id,
            ],
            'customer' => [
                'email' => $customerData['email'],
                'name' => $customerData['name'],
            ],
            'success_url' => config('app.frontend_url') . "/invoices/{$invoice->id}/payment/success",
            'cancel_url' => config('app.frontend_url') . "/invoices/{$invoice->id}/payment/cancel",
        ]);

        return [
            'checkout_id' => $checkout->id,
            'checkout_url' => $checkout->url,
        ];
    }

    public function handleWebhook(array $payload, string $signature): void
    {
        // Verify signature
        $secret = config('services.paddle.webhook_secret');
        $expectedSignature = hash_hmac('sha1', json_encode($payload), $secret);

        if (!hash_equals($expectedSignature, $signature)) {
            throw new \Exception('Invalid webhook signature');
        }

        // Check for duplicate webhook (idempotency)
        $eventId = $payload['event_id'] ?? null;
        if (!$eventId) {
            throw new \Exception('Missing event_id');
        }

        $exists = \Cache::has("paddle_webhook_{$eventId}");
        if ($exists) {
            Log::info('Duplicate Paddle webhook ignored', ['event_id' => $eventId]);
            return;
        }

        \Cache::put("paddle_webhook_{$eventId}", true, now()->addDays(7));

        // Process based on event type
        $eventType = $payload['alert_name'] ?? $payload['event_type'];

        match ($eventType) {
            'payment_succeeded' => $this->handlePaymentSucceeded($payload),
            'payment_failed' => $this->handlePaymentFailed($payload),
            'subscription_payment_succeeded' => $this->handleSubscriptionPayment($payload),
            default => Log::warning("Unhandled Paddle event: {$eventType}"),
        };
    }

    protected function handlePaymentSucceeded(array $payload): void
    {
        $customData = $payload['custom_data'] ?? [];
        $invoiceId = $customData['invoice_id'] ?? null;

        if (!$invoiceId) {
            Log::error('Paddle payment succeeded but no invoice_id', ['payload' => $payload]);
            return;
        }

        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            Log::error("Invoice {$invoiceId} not found for Paddle payment");
            return;
        }

        // Extract fee
        $gross = (float) ($payload['payment_gross'] ?? 0);
        $fee = (float) ($payload['payment_fee'] ?? 0);
        $net = $gross - $fee;

        // Create payment
        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'customer_id' => $invoice->customer_id,
            'payment_method_id' => $this->getPaddlePaymentMethodId(),
            'payment_number' => 'PADDLE-' . $payload['order_id'],
            'payment_date' => now(),
            'amount' => $net,
            'transaction_reference' => $payload['checkout_id'] ?? $payload['subscription_payment_id'],
            'notes' => "Paddle payment. Gross: {$gross}, Fee: {$fee}",
        ]);

        // Update invoice status
        if ($invoice->due_amount <= 0) {
            $invoice->update(['status' => 'PAID']);
        }

        // Post to accounting if enabled
        if (\Feature::active('accounting-backbone')) {
            $this->postPaymentToAccounting($payment, $fee);
        }

        Log::info('Paddle payment recorded', [
            'invoice_id' => $invoice->id,
            'payment_id' => $payment->id,
            'amount' => $net,
            'fee' => $fee,
        ]);
    }

    protected function postPaymentToAccounting(Payment $payment, float $fee): void
    {
        $journalService = app(\App\Services\Accounting\JournalEntryService::class);

        // DR Cash (net amount)
        // DR Payment Processing Fee (fee)
        // CR Accounts Receivable (gross)

        $journalService->createEntry($payment->company, [
            'entry_date' => $payment->payment_date->toDateString(),
            'type' => 'payment',
            'reference' => $payment->payment_number,
            'description' => "Paddle payment for invoice {$payment->invoice->invoice_number}",
            'lines' => [
                [
                    'account_id' => $this->getCashAccountId($payment->company_id),
                    'type' => 'debit',
                    'amount' => $payment->amount,
                    'currency_id' => $payment->invoice->currency_id,
                ],
                [
                    'account_id' => $this->getFeeAccountId($payment->company_id),
                    'type' => 'debit',
                    'amount' => $fee,
                    'currency_id' => $payment->invoice->currency_id,
                ],
                [
                    'account_id' => $this->getARAccountId($payment->company_id),
                    'type' => 'credit',
                    'amount' => $payment->amount + $fee,
                    'currency_id' => $payment->invoice->currency_id,
                ],
            ],
        ], $payment->company->owner);
    }

    protected function getPaddlePaymentMethodId(): int
    {
        return \App\Models\PaymentMethod::firstOrCreate([
            'name' => 'Paddle',
        ])->id;
    }

    protected function getCashAccountId(int $companyId): int
    {
        return \App\Models\ChartOfAccount::where('company_id', $companyId)
            ->where('code', '1020') // Bank account
            ->first()->id;
    }

    protected function getFeeAccountId(int $companyId): int
    {
        return \App\Models\ChartOfAccount::where('company_id', $companyId)
            ->where('code', '5200') // Administrative expenses
            ->first()->id;
    }

    protected function getARAccountId(int $companyId): int
    {
        return \App\Models\ChartOfAccount::where('company_id', $companyId)
            ->where('code', '1100') // Accounts receivable
            ->first()->id;
    }
}
```

**Controllers:**
```php
// Update Modules/Mk/Http/PaddleWebhookController.php
public function handle(Request $request, PaddlePaymentService $paddleService)
{
    $signature = $request->header('Paddle-Signature');
    $payload = $request->all();

    try {
        $paddleService->handleWebhook($payload, $signature);

        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        Log::error('Paddle webhook processing failed', [
            'error' => $e->getMessage(),
            'payload' => $payload,
        ]);

        return response()->json(['error' => 'Webhook processing failed'], 422);
    }
}
```

**Tests:**
```php
// tests/Feature/Payment/PaddlePaymentTest.php
<?php
namespace Tests\Feature\Payment;

use Tests\TestCase;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Payment\PaddlePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

class PaddlePaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_idempotency_prevents_duplicates(): void
    {
        $invoice = Invoice::factory()->create();
        $service = new PaddlePaymentService();

        $payload = [
            'event_id' => 'evt_123',
            'alert_name' => 'payment_succeeded',
            'order_id' => '12345',
            'payment_gross' => 1000,
            'payment_fee' => 50,
            'custom_data' => ['invoice_id' => $invoice->id],
        ];

        // First webhook
        $service->handleWebhook($payload, 'valid_signature');
        $this->assertDatabaseHas('payments', ['invoice_id' => $invoice->id]);

        $paymentCount = Payment::where('invoice_id', $invoice->id)->count();

        // Duplicate webhook
        $service->handleWebhook($payload, 'valid_signature');

        // Should not create duplicate payment
        $this->assertEquals($paymentCount, Payment::where('invoice_id', $invoice->id)->count());
    }

    public function test_payment_fee_posted_to_accounting(): void
    {
        config(['features.accounting_backbone' => true]);

        $invoice = Invoice::factory()->create();
        $service = new PaddlePaymentService();

        $payload = [
            'event_id' => 'evt_456',
            'alert_name' => 'payment_succeeded',
            'order_id' => '12345',
            'payment_gross' => 1000,
            'payment_fee' => 50,
            'custom_data' => ['invoice_id' => $invoice->id],
        ];

        $service->handleWebhook($payload, 'valid_signature');

        // Check journal entry created
        $this->assertDatabaseHas('journal_entries', [
            'company_id' => $invoice->company_id,
            'type' => 'payment',
        ]);

        // Verify fee line exists
        $entry = \App\Models\JournalEntry::where('company_id', $invoice->company_id)
            ->where('type', 'payment')
            ->first();

        $feeLine = $entry->lines->firstWhere('amount', 50);
        $this->assertNotNull($feeLine);
        $this->assertEquals('debit', $feeLine->type);
    }
}
```

**Acceptance Criteria:**
- ✅ Paddle checkout session created with invoice data
- ✅ Webhook signature verified
- ✅ Duplicate webhooks ignored (idempotency via Cache)
- ✅ Payment created with fee deducted
- ✅ Invoice marked as PAID when fully paid
- ✅ Journal entry posted with fee line (if accounting enabled)
- ✅ Manual: Create invoice → pay via Paddle sandbox → webhook received → payment recorded

**Rollback:**
```bash
# .env: FEATURE_ADVANCED_PAYMENTS=false
```

---

## MILESTONES 7-18 SUMMARY (Condensed for Space)

Due to extensive detail above, I'll provide **structured summaries** for remaining milestones and then create the complete `roadmap.md` file.

### MILESTONE 7: Advanced Invoicing - Document Lifecycle (24 hours)
- **Migrations:** `invoice_transitions`, `numbering_sequences`
- **Models:** Enhanced Invoice with state machine
- **Services:** `InvoiceLifecycleService` (quote→proforma→invoice→credit note)
- **Tests:** State transition validation, numbering locks
- **Acceptance:** Can convert quote to invoice, generate credit note, numbering never duplicates

### MILESTONE 8: UBL Hardening - Certificate Management (16 hours)
- **Migrations:** `certificates` table
- **Controllers:** `CertificateController` (upload PFX, validate, check expiry)
- **Services:** `CertificateStorageService` with encryption
- **Health Check:** `/api/health/signer` endpoint
- **Acceptance:** Upload cert → sign invoice → verify signature → cert expires warning

### MILESTONE 9: VAT/DDV Compliance (20 hours)
- **Reports:** Sales/Purchase books, DDV return draft
- **Services:** `VatReturnService`, `FxRateProviderService` (NBRM API)
- **Tests:** Tax calculations, reverse charge, FX rounding
- **Acceptance:** Generate monthly DDV return with totals matching invoices

### MILESTONE 10: Bank Reconciliation Auto-Match (24 hours)
- **Enhanced Matcher:** Tolerance ranges, manual review queue
- **UI:** Match approval screen, categorization rules
- **Tests:** Auto-match scenarios, duplicate prevention
- **Acceptance:** Bank transaction auto-matched to invoice at 85% confidence

### MILESTONE 11: MCP AI Tools Server (40 hours)
- **Directory:** `app/Mcp/Tools/*`
- **Tools:** `UblValidateTool`, `BankCategorizeTool`, `TaxExplainTool`, `AnomalyScanTool`
- **Server:** HTTP/stdio transport with auth tokens
- **UI:** "Ask AI" chat panel
- **Tests:** Tool contracts, PII scrubbing, rate limits
- **Acceptance:** Ask AI "explain this invoice" → receives UBL breakdown

### MILESTONE 12: Client Portal (24 hours)
- **Controllers:** `CustomerPortalController`
- **Features:** View invoices, make payment, upload PO, submit disputes
- **Tests:** Authorization, payment flow
- **Acceptance:** Customer logs in → views invoices → pays → dispute submitted

### MILESTONE 13: Monitoring Re-enablement (12 hours)
- **Files:** Rename `.disabled` → `.php`
- **Routes:** Restore `/metrics`, `/metrics/health`
- **Dashboard:** Queue depth, failed jobs, signer validity
- **Acceptance:** Prometheus scrapes /metrics successfully

### MILESTONE 14: Performance Optimization (20 hours)
- **N+1 Fixes:** Invoice list, payment list
- **Caching:** Tax rates, settings, FX rates
- **Async Jobs:** PDF/UBL generation
- **Load Tests:** 1000 invoices, 10k transactions
- **Acceptance:** Invoice list <200ms, no N+1 queries

### MILESTONE 15: Security Hardening (16 hours)
- **Secrets:** Move certs/keys to encrypted storage
- **RBAC:** Policy review on all controllers
- **Audit Log:** Immutable log table
- **Tests:** Authz failures, audit writes
- **Acceptance:** Unauthorized access returns 403, audit log immutable

### MILESTONE 16: UX Polish & i18n (16 hours)
- **Translations:** Complete MK/EN
- **UI:** Empty states, skeletons, toasts
- **Accessibility:** ARIA labels, keyboard nav
- **Acceptance:** All strings translated, keyboard navigable

### MILESTONE 17: Documentation & Runbooks (12 hours)
- **Files:** `docs/runbooks/*`, `docs/api/*`
- **Guides:** Bank setup, cert upload, migration wizard, MCP usage
- **Acceptance:** Non-technical user can follow bank setup guide

### MILESTONE 18: Staging Validation & Production Prep (24 hours)
- **Test Plan:** Invoice e-sign/send, bank import, migration 1k rows, partner attribution, MCP
- **Load Tests:** Realistic data volumes
- **Checklist:** Pre-flight checks, rollback procedures
- **Acceptance:** All critical paths work end-to-end on staging

---

## 📊 SUMMARY METRICS

| Metric | Value |
|--------|-------|
| Total Engineering Hours | 520 hours |
| Total Milestones | 18 |
| Feature Flags | 9 flags |
| New Composer Packages | 6 |
| New Migrations | ~30 |
| New Models | ~15 |
| New Services | ~20 |
| New Controllers | ~18 |
| New Tests | ~65 test files |
| New API Endpoints | ~80 endpoints |

---

## 🚀 DEPLOYMENT SEQUENCE

**Week 1-2:** M0-M2 (Foundation + Accounting)
**Week 3-5:** M3-M6 (PSD2 + Migration + Partner + Payments)
**Week 6-9:** M7-M12 (Advanced features + MCP)
**Week 10-12:** M13-M16 (Production readiness)
**Week 13:** M17-M18 (Docs + Staging validation)

---

## ⚠️ CRITICAL DEPENDENCIES

1. **Verify Package Names Before Install:**
   - ✅ `laravel/cashier-paddle` - exists
   - ❓ `bojanvmk/laravel-cpay` - **verify on Packagist**
   - ❌ `oak-labs-io/psd2` - **does not exist** (use symfony/http-client)

2. **Environment Setup:**
   - All `.env` keys documented in `.env.example`
   - Sandbox credentials for Paddle, Stopanska, NLB before starting

3. **Test Data:**
   - Create test company with 100 invoices
   - Upload test CSV with 1000 rows
   - Prepare bank sandbox account

---

## 🔄 ROLLBACK MATRIX

| Milestone | Rollback Command | Risk Level |
|-----------|------------------|------------|
| M0 | `composer remove laravel/pennant` | LOW |
| M1-M2 | `php artisan migrate:rollback --step=4` | MEDIUM |
| M3 | `FEATURE_PSD2_BANKING=false` | LOW |
| M4 | `FEATURE_MIGRATION_WIZARD=false` | LOW |
| M5 | `FEATURE_PARTNER_MOCKED_DATA=true` | LOW |
| M6 | `FEATURE_ADVANCED_PAYMENTS=false` | MEDIUM |
| M13 | Comment out Prometheus routes | LOW |

---

## 📝 NEXT STEPS

1. **Review this roadmap with team**
2. **Verify all package names on Packagist**
3. **Set up staging environment**
4. **Create GitHub project board with 18 milestones**
5. **Start with Milestone 0**

---

**Last Updated:** 2025-08-03
**Version:** 1.0
**Author:** Claude Code Roadmap Generator
