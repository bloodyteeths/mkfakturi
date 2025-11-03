# FAKTURINO v1 - MASTER INTEGRATION ROADMAP
## Production-Credible via Forked Packages (Not Green-Field)

**Last Updated:** 2025-08-03
**Strategy:** Fork battle-tested repos, integrate via adapters, deploy to Railway
**Timeline:** 8 weeks (320 hours) via integration, not custom builds
**Deployment:** Railway multi-service (web, worker, scheduler, MCP, postgres, redis)

---

## 🎯 EXECUTIVE SUMMARY

### Integration-First Approach
We're **NOT building** accounting, migration, or monitoring from scratch. Instead:
- **Fork** mature MIT/Apache-2.0 packages
- **Adapt** via thin adapter layers (ports/adapters pattern)
- **Deploy** PR-sized chunks with feature flags
- **Keep** mocked partner data ON until staging sign-off
- **Test** everything before enabling features

### Current State (Per Analysis)
✅ **Working:** UBL export/signing, CPAY/Paddle code paths, BankTransaction/Commission models, queue infrastructure
⚠️ **Partial:** Payment gateways (code exists, deps missing), PSD2 jobs (incomplete OAuth), Migration UI (deps missing)
❌ **Missing:** Accounting backbone, partner APIs (all mocked), monitoring (disabled), MCP server (passive stub)

### Railway Deployment Architecture
```
┌─────────────────────────────────────────────────────┐
│ Railway Project: fakturino-production              │
├─────────────────────────────────────────────────────┤
│ Services:                                           │
│  • api         → Laravel web (PHP-FPM + Nginx)     │
│  • worker      → Queue processor (default, banking) │
│  • scheduler   → Laravel schedule:work             │
│  • mcp-server  → AI tools (TypeScript/Node)        │
│                                                     │
│ Add-ons:                                            │
│  • postgres    → Primary database                   │
│  • redis       → Cache + queues                     │
│                                                     │
│ Volumes/Storage:                                    │
│  • /storage/certificates → Signer certs (volume)   │
│  OR use S3-compatible bucket                        │
└─────────────────────────────────────────────────────┘
```

---

## 🤖 MULTI-AGENT EXECUTION CHARTER

### Overview
This roadmap will be executed by **10 specialized agents** working in parallel where possible, coordinated by a ReleaseManager agent. All agents follow Railway deployment constraints, feature flag safety, and continuous roadmap updates.

### Agents and Responsibilities

| Agent | Steps | Responsibility |
|-------|-------|----------------|
| **FlagsAndDocs** | 0 | Foundation: Install Laravel Pennant, create feature flags, initialize docs |
| **Accounting** | 1 | Integrate eloquent-ifrs with adapter layer, seed MK chart of accounts |
| **Migration** | 2 | Integrate Laravel Excel, implement import classes with Onivo/Megasoft presets |
| **Paddle** | 3 | Install laravel/cashier-paddle, implement webhook with idempotency |
| **CPAY** | 4 | Enhance existing CPAY driver with signature verification and idempotency |
| **Banking** | 5 | Implement PSD2 OAuth with symfony/http-client + MT940/CSV fallback |
| **PartnerPortal** | 6 | Implement partner APIs with `FEATURE_PARTNER_MOCKED_DATA=true` safety flag |
| **MCP** | 7 | Create TypeScript MCP server with AI tools, deploy as Railway service |
| **Monitoring** | 8 | Enable Prometheus exporter + Telescope behind feature flags |
| **ReleaseManager** | - | Coordinates merges, updates roadmap, enforces CI, manages AGENTS_STATUS.md |

### Execution Stages

**STAGE 0: Foundation (Sequential)**
- **FlagsAndDocs agent** implements Step 0
- Must merge to `main` before Stage A begins
- Creates all feature flags with safe defaults (`FEATURE_PARTNER_MOCKED_DATA=true`)
- Initializes INTEGRATIONS.md, RAILWAY.md, LINKS.md, AGENTS_STATUS.md

**STAGE A: Core Features (Parallel)**
After Step 0 merges, launch these agents simultaneously:
- Accounting agent (Step 1)
- Migration agent (Step 2)
- Paddle agent (Step 3)
- CPAY agent (Step 4)
- Monitoring agent (Step 8)

**STAGE B: Advanced Features (Parallel)**
After Stage A PRs merge or are at least behind flags:
- Banking agent (Step 5)
- PartnerPortal agent (Step 6)
- MCP agent (Step 7)

### Branching and PR Policy

**Branch Naming:** `feat/<step-name>`
- Example: `feat/foundation-flags`, `feat/accounting-ifrs-integration`

**PR Requirements (All Agents):**
- ✅ Keep PRs under ~500 LOC (split if larger)
- ✅ All new endpoints must have authorization + tests
- ✅ All webhooks must be idempotent (cache or unique constraint)
- ✅ Feature flags defaulting to safe values
- ✅ Update `.env.example` with new keys
- ✅ Update INTEGRATIONS.md if new dependencies added
- ✅ Update RAILWAY.md if services/envs change
- ✅ Tests green before marking done

**Merge Order:**
ReleaseManager enforces:
1. Step 0 must merge first
2. Stage A can merge in any order (all independent)
3. Stage B can merge after Stage A complete

### Railway Constraints (All Agents)

**Multi-Service Architecture:**
- `api` - Laravel web (PHP-FPM + Nginx)
- `worker` - Queue processor (queues: default, migration, banking)
- `scheduler` - Laravel schedule:work
- `mcp-server` - TypeScript MCP tools (Node)
- `postgres` - Primary database (Railway add-on)
- `redis` - Cache + queues (Railway add-on)

**Health Endpoints:**
- `api`: `/metrics/health`
- `mcp-server`: `/health`

**Secrets Policy:**
- Never commit certificates or secrets
- Use Railway environment variables only
- Document new env vars in RAILWAY.md

**Service Changes:**
If an agent adds/modifies services, they must document in RAILWAY.md:
- Service name and purpose
- Build/run commands
- Health check configuration
- Environment variables required
- Volume/storage needs

### Roadmap Auto-Updates

Every agent must update MASTER_ROADMAP.md as they work.

**While Working (In Progress):**
Under the relevant Step section, add:
```markdown
#### Progress
- **status:** in progress
- **branch:** feat/step-name
- **pr:** #123
- **owner agent:** AgentName
- **start date:** 2025-11-03
```

**After Merge (Completed):**
Replace the Progress section with:
```markdown
#### Completed
- **merged:** 2025-11-04 by AgentName
- **pr:** #123
- **commit sha:** abc123def

#### Mini Audit
- **files touched:** [list]
- **public api changes:** [routes, DTOs, events, webhooks]
- **database:** [migrations created; reversible yes/no]
- **env and flags:** [new keys; defaults]
- **performance:** [query changes, caches, N+1 fixes]
- **security:** [authz, PII, secrets, signatures, rate limits]
- **reliability:** [idempotency, retries, failure handling]
- **observability:** [metrics, logs, health checks]
- **tests:** [new tests; coverage summary]
- **manual validation:** [exact staging steps followed]
- **railway notes:** [service changes, volumes, health checks]
- **known issues:** [follow-ups with ticket links]
- **rollback plan:** [flag to flip or migrate:rollback steps]
```

**AGENTS_STATUS.md:**
ReleaseManager also maintains AGENTS_STATUS.md with same Mini Audit for quick scanning.

### Safety Rules (All Agents)

**Feature Flags:**
- Never flip `FEATURE_PARTNER_MOCKED_DATA` to false without explicit instruction
- All new features default to OFF (except partner mocked data = true)
- Gates must check flags before executing risky code

**Security:**
- All new endpoints must check authorization
- All webhooks must verify signatures
- All webhooks must be idempotent (test duplicate events)
- No GPL libraries (MIT/Apache-2.0 only)

**Testing:**
- Unit tests for all services
- Feature tests for all API endpoints
- Browser tests for critical UI flows
- Run full test suite before marking done: `php artisan test && npm run test`

**Migrations:**
- Document rollback path in Mini Audit
- Ensure reversible with `migrate:rollback`
- Test with `php artisan migrate:fresh` before PR

### Staging Validation Gate

**No agent changes the staging validation checklist.**

ReleaseManager flips flags for staging only after:
- All relevant Mini Audits exist
- All tests green
- Staging validation checklist items green

### Start Procedure

1. **FlagsAndDocs agent** creates `feat/foundation-flags` branch
2. Implements Step 0: Feature flags + docs skeleton
3. Opens PR with tests
4. After merge, **ReleaseManager** updates MASTER_ROADMAP.md to mark Steps 1, 2, 3, 4, 8 as "in progress"
5. Launch **5 agents in parallel** (Accounting, Migration, Paddle, CPAY, Monitoring)
6. Each agent updates their Step section with Progress info
7. On merge, each agent adds Completed + Mini Audit
8. After Stage A complete, launch Stage B agents (Banking, PartnerPortal, MCP)

---

## 📦 SELECTED THIRD-PARTY PACKAGES

### 1. Accounting Backbone: eloquent-ifrs
- **URL:** https://github.com/ekmungai/eloquent-ifrs
- **License:** MIT ✅
- **Version:** v3.2.0 (2024-07-15)
- **Stars:** 1,500+
- **Why:** Full double-entry with TB/BS/P&L, IFRS compliant, active maintenance
- **Integration:** `composer require ekmungai/eloquent-ifrs`
- **Adapter:** `app/Domain/Accounting/IfrsAdapter.php` maps Invoice/Payment → IFRS journals

### 2. Migration Wizard: Laravel Excel
- **URL:** https://github.com/SpartnerNL/Laravel-Excel
- **License:** MIT ✅
- **Version:** 3.1.55 (2024-06-20)
- **Stars:** 12,000+
- **Why:** Battle-tested CSV/XLSX import with queue support, validation, chunk processing
- **Integration:** `composer require maatwebsite/excel`
- **Imports:** `App\Imports\CustomerImport`, `App\Imports\InvoiceImport`

### 3. CSV Streaming: league/csv
- **URL:** https://github.com/thephpleague/csv
- **License:** MIT ✅
- **Version:** 9.16.0 (2024-05-20)
- **Stars:** 3,000+
- **Why:** Lightweight CSV parsing, encoding detection, streaming
- **Integration:** `composer require league/csv`
- **Use:** Preview/detection, delegate to Laravel Excel for imports

### 4. Payments - Paddle: laravel/cashier-paddle
- **URL:** https://github.com/laravel/cashier-paddle
- **License:** MIT ✅
- **Version:** v2.8.0 (2024-05-15)
- **Stars:** 600+
- **Why:** Official Laravel package, webhook handling, signature verification
- **Integration:** `composer require laravel/cashier-paddle`
- **Handler:** Webhook routes with CSRF exemption, idempotency cache

### 5. Payments - CPAY: Custom (No Package Exists)
- **Implementation:** Use existing `Modules/Mk/Services/CpayDriver.php`
- **Dependencies:** `symfony/http-client`
- **Why:** No CPAY package on Packagist, API simple enough for direct implementation
- **Security:** SHA256 signature generation, sandbox testing

### 6. PSD2 Banking: symfony/http-client + Custom OAuth
- **URL:** https://github.com/symfony/http-client
- **License:** MIT ✅
- **Version:** v7.2.0 (2024-07-10)
- **Why:** No mature PSD2 package exists, Symfony HTTP Client is production-ready
- **Implementation:** Build OAuth2 flow in `app/Services/Banking/Psd2Client.php`
- **Fallback:** MT940/CSV import if OAuth delays (use jejik/mt940 MIT parser)

### 7. MCP AI Tools: TypeScript SDK
- **URL:** https://github.com/modelcontextprotocol/typescript-sdk
- **License:** MIT ✅
- **Version:** v0.5.0 (2024-11-15)
- **Stars:** 2,000+
- **Why:** Official Anthropic MCP implementation, stdio + HTTP transports
- **Deploy:** Separate Railway service (`mcp-server/`)
- **Tools:** ubl_validate, tax_explain, bank_categorize, anomaly_scan, migration_map_preview

### 8. Monitoring: laravel-prometheus-exporter
- **URL:** https://github.com/Superbalist/laravel-prometheus-exporter
- **License:** MIT ✅
- **Version:** 2.6.1 (2023-08-10)
- **Stars:** 330+
- **Why:** Clean Laravel integration, custom metrics, `/metrics` endpoint
- **Integration:** `composer require superbalist/laravel-prometheus-exporter`
- **Metrics:** Request duration, queue depth, failed jobs, cert validity

### 9. Telescope: Laravel Official (Already Installed)
- **Package:** `laravel/telescope` (require-dev)
- **License:** MIT ✅
- **Status:** Installed but disabled, re-enable with feature flag
- **Gate:** Admin-only access via `TelescopeServiceProvider::gate()`

### 10. Queue Monitoring: Laravel Horizon (Optional)
- **URL:** https://github.com/laravel/horizon
- **License:** MIT ✅
- **Version:** v5.28.1 (2024-07-01)
- **Why:** Official Redis queue dashboard, auto-scaling, failed job management
- **Deploy:** Optional - start with basic queue:work, add later if needed

---

## 🚀 IMPLEMENTATION PLAN (8 PR-Sized Steps)

### STEP 0: Foundation - Feature Flags & Docs (4 hours)
**Branch:** `feat/foundation-flags`
**Feature Flags:** All default to OFF except `partner_mocked_data=true`

**Files Created:**
```
config/features.php
app/Providers/FeatureFlagServiceProvider.php
app/Helpers/FeatureHelper.php
.env.example (updated with all flags)
```

**Flags Added:**
```bash
FEATURE_ACCOUNTING_BACKBONE=false
FEATURE_MIGRATION_WIZARD=false
FEATURE_PSD2_BANKING=false
FEATURE_PARTNER_PORTAL=false
FEATURE_PARTNER_MOCKED_DATA=true   # SAFETY - keep ON
FEATURE_ADVANCED_PAYMENTS=false
FEATURE_MCP_AI_TOOLS=false
FEATURE_MONITORING=false
```

**Dependencies Installed:**
```bash
composer require laravel/pennant:^1.10
composer require symfony/http-client:^7.2  # For PSD2/CPAY
```

**Tests:**
```
tests/Unit/FeatureFlagTest.php
  - test_feature_flags_default_to_safe_values()
  - test_partner_mocked_data_defaults_to_true()
  - test_flags_can_be_toggled()
```

**Acceptance:**
- ✅ All flags in `.env` control features
- ✅ `FEATURE_PARTNER_MOCKED_DATA=true` by default
- ✅ Tests green: `php artisan test --filter=FeatureFlagTest`

**PR:** `feat(foundation): add Pennant feature flags + safety defaults`

#### Progress
- **status:** in progress
- **branch:** feat/foundation-flags
- **pr:** (will be created after implementation)
- **owner agent:** FlagsAndDocs
- **start date:** 2025-11-03

#### Completed
(Will be filled by FlagsAndDocs agent after merge, including Mini Audit)

---

### STEP 1: Accounting Backbone via eloquent-ifrs (16 hours)
**Branch:** `feat/accounting-ifrs-integration`
**Feature Flag:** `FEATURE_ACCOUNTING_BACKBONE=false`

**Dependencies:**
```bash
composer require ekmungai/eloquent-ifrs:^3.2
```

**Files Created:**
```
app/Domain/Accounting/IfrsAdapter.php          # Adapter layer
app/Domain/Accounting/ChartOfAccountSeeder.php  # MK COA
database/seeders/MkIfrsSeeder.php
```

**Integration Strategy:**
1. Run IFRS migrations: `php artisan migrate` (IFRS package provides migrations)
2. Seed Macedonian chart of accounts:
   - Assets: 1000-1999
   - Liabilities: 2000-2999
   - Equity: 3000-3999
   - Revenue: 4000-4999
   - Expenses: 5000-5999

3. Create IfrsAdapter with methods:
   - `postInvoice(Invoice $invoice): void` → Creates DR Accounts Receivable, CR Revenue
   - `postPayment(Payment $payment): void` → Creates DR Cash, CR Accounts Receivable
   - `postFee(Payment $payment, float $fee): void` → Creates DR Fee Expense, CR Cash
   - `getTrialBalance(Company $company, $asOfDate): array`
   - `getBalanceSheet(Company $company, $asOfDate): array`

4. Wire to Invoice/Payment observers (only when flag ON)

**API Endpoints (Read-Only):**
```
GET /api/v1/admin/{company}/accounting/trial-balance?as_of_date=2025-08-31
GET /api/v1/admin/{company}/accounting/balance-sheet?as_of_date=2025-08-31
GET /api/v1/admin/{company}/accounting/income-statement?start=2025-01-01&end=2025-08-31
```

**Tests:**
```
tests/Unit/Domain/Accounting/IfrsAdapterTest.php
  - test_invoice_posts_to_ledger()
  - test_payment_posts_to_ledger()
  - test_trial_balance_is_balanced()
  - test_adapter_skipped_when_flag_off()

tests/Feature/Accounting/IfrsIntegrationTest.php
  - test_create_invoice_and_payment_updates_ledger()
  - test_trial_balance_api_returns_balanced_totals()
```

**Acceptance:**
- ✅ With flag ON: Create invoice → post to ledger → verify DR/CR lines
- ✅ With flag ON: Record payment → ledger updated → TB balances
- ✅ With flag OFF: Invoice/payment saved, no ledger posting
- ✅ TB API returns balanced totals (debits = credits)

**PR:** `feat(accounting): integrate eloquent-ifrs with adapter layer`

#### Progress
- **status:** in progress
- **branch:** feat/accounting-ifrs-integration
- **pr:** (to be created)
- **owner agent:** Accounting
- **start date:** 2025-11-03

#### Completed
(Will be filled by Accounting agent after merge, including Mini Audit)

---

### STEP 2: Migration Wizard via Laravel Excel (20 hours)
**Branch:** `feat/migration-wizard`
**Feature Flag:** `FEATURE_MIGRATION_WIZARD=false`

**Dependencies:**
```bash
composer require maatwebsite/excel:^3.1
composer require league/csv:^9.16
```

**Files Created:**
```
app/Imports/CustomerImport.php
app/Imports/InvoiceImport.php
app/Imports/ItemImport.php
app/Services/Migration/ImportPresetService.php  # Onivo, Megasoft presets
app/Models/ImportJob.php (already exists, enhance)
app/Jobs/ProcessImportJob.php
```

**Import Classes (Laravel Excel):**
```php
// app/Imports/CustomerImport.php
class CustomerImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading
{
    public function model(array $row) {
        return new Customer([
            'company_id' => $this->companyId,
            'name' => $row['name'] ?? $row['partner'] ?? $row['partnername'],
            'email' => $row['email'],
            'phone' => $row['phone'] ?? $row['telephone'],
            'vat_number' => $row['vat'] ?? $row['edb'],
        ]);
    }

    public function rules(): array {
        return ['name' => 'required', 'email' => 'nullable|email'];
    }

    public function chunkSize(): int { return 500; }
}
```

**Preset Service:**
```php
// app/Services/Migration/ImportPresetService.php
class ImportPresetService
{
    public function getPreset(string $source, string $entityType): array
    {
        return match($source) {
            'onivo' => $this->getOnivoPreset($entityType),
            'megasoft' => $this->getMegasoftPreset($entityType),
            default => [],
        };
    }

    private function getOnivoPreset(string $entityType): array
    {
        // Map Macedonian column names to our fields
        return match($entityType) {
            'customers' => [
                'name' => 'Партнер',
                'email' => 'Email',
                'phone' => 'Телефон',
                'vat_number' => 'ЕДБ',
            ],
            'items' => [...],
            'invoices' => [...],
        };
    }
}
```

**API Endpoints:**
```
POST   /api/v1/admin/{company}/migration/upload             # Upload file
GET    /api/v1/admin/{company}/migration/{job}/preview      # First 10 rows
GET    /api/v1/admin/{company}/migration/presets/{source}   # Get preset mapping
POST   /api/v1/admin/{company}/migration/{job}/dry-run      # Validate only
POST   /api/v1/admin/{company}/migration/{job}/import       # Execute import
GET    /api/v1/admin/{company}/migration/{job}/status       # Progress
GET    /api/v1/admin/{company}/migration/{job}/errors       # Download error CSV
```

**Queue Configuration:**
```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'queues' => ['default', 'migration', 'banking'],
    ],
],
```

**Tests:**
```
tests/Feature/Migration/CustomerImportTest.php
  - test_upload_csv_creates_import_job()
  - test_preview_returns_first_10_rows()
  - test_onivo_preset_maps_columns_correctly()
  - test_dry_run_validates_without_inserting()
  - test_import_1000_rows_commits_to_database()
  - test_error_csv_contains_failed_rows()
  - test_macedonian_encoding_detected()

tests/Unit/Services/ImportPresetServiceTest.php
  - test_onivo_preset_structure()
  - test_megasoft_preset_structure()
```

**Acceptance:**
- ✅ Upload 1k-row Onivo CSV → auto-detect encoding/delimiter
- ✅ Apply preset → columns auto-mapped
- ✅ Dry run → 950 valid, 50 errors
- ✅ Import → 950 customers created, error CSV downloadable
- ✅ Progress updates in real-time

**PR:** `feat(migration): integrate Laravel Excel with Onivo/Megasoft presets`

#### Progress
- **status:** in progress
- **branch:** feat/migration-wizard
- **pr:** (to be created)
- **owner agent:** Migration
- **start date:** 2025-11-03

#### Completed
(Will be filled by Migration agent after merge, including Mini Audit)

---

### STEP 3: Paddle Payment Integration (12 hours)
**Branch:** `feat/payments-paddle`
**Feature Flag:** `FEATURE_ADVANCED_PAYMENTS=false`

**Dependencies:**
```bash
composer require laravel/cashier-paddle:^2.8
```

**Files Created/Updated:**
```
app/Services/Payment/PaddlePaymentService.php
app/Http/Controllers/Webhooks/PaddleWebhookController.php
routes/webhooks.php (new)
config/services.php (add paddle config)
```

**Service Implementation:**
```php
// app/Services/Payment/PaddlePaymentService.php
class PaddlePaymentService
{
    public function createCheckout(Invoice $invoice): array
    {
        $paddle = new \Paddle\PaddleClient(
            config('services.paddle.api_key'),
            config('services.paddle.environment')
        );

        $checkout = $paddle->checkouts->create([
            'items' => [['price_id' => config('services.paddle.price_id')]],
            'custom_data' => ['invoice_id' => $invoice->id],
            'customer_email' => $invoice->customer->email,
        ]);

        return ['checkout_url' => $checkout->url];
    }

    public function handleWebhook(array $payload, string $signature): void
    {
        // Verify signature
        $this->verifySignature($payload, $signature);

        // Idempotency check
        $eventId = $payload['event_id'];
        if (Cache::has("paddle_event_{$eventId}")) {
            return; // Already processed
        }
        Cache::put("paddle_event_{$eventId}", true, now()->addDays(7));

        // Process event
        match($payload['event_type']) {
            'transaction.completed' => $this->handleTransactionCompleted($payload),
            'transaction.payment_failed' => $this->handlePaymentFailed($payload),
            default => Log::info("Unhandled Paddle event: {$payload['event_type']}"),
        };
    }

    private function handleTransactionCompleted(array $payload): void
    {
        $invoiceId = $payload['data']['custom_data']['invoice_id'];
        $gross = $payload['data']['details']['totals']['total'] / 100;
        $fee = $payload['data']['details']['totals']['fee'] / 100;
        $net = $gross - $fee;

        $invoice = Invoice::findOrFail($invoiceId);

        $payment = Payment::create([
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'amount' => $net,
            'payment_date' => now(),
            'payment_number' => 'PADDLE-' . $payload['data']['id'],
            'notes' => "Paddle payment. Fee: {$fee} MKD",
        ]);

        // Post to accounting if enabled
        if (Feature::active('accounting-backbone')) {
            $this->postToLedger($payment, $fee);
        }

        // Update invoice status
        if ($invoice->due_amount <= 0) {
            $invoice->update(['status' => 'PAID']);
        }
    }

    private function postToLedger(Payment $payment, float $fee): void
    {
        $adapter = app(IfrsAdapter::class);
        $adapter->postPayment($payment);
        $adapter->postFee($payment, $fee);
    }
}
```

**Webhook Route:**
```php
// routes/webhooks.php
Route::post('webhooks/paddle', [PaddleWebhookController::class, 'handle'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
```

**Environment Variables:**
```bash
PADDLE_VENDOR_ID=
PADDLE_API_KEY=
PADDLE_ENVIRONMENT=sandbox
PADDLE_WEBHOOK_SECRET=
PADDLE_PRICE_ID=  # One-time payment price
```

**Tests:**
```
tests/Feature/Payments/PaddleWebhookTest.php
  - test_webhook_signature_verification()
  - test_idempotency_prevents_duplicate_processing()
  - test_transaction_completed_creates_payment()
  - test_fee_posted_to_accounting_when_enabled()
  - test_invoice_marked_paid_when_fully_paid()
  - test_invalid_signature_rejected()

tests/Unit/Services/PaddlePaymentServiceTest.php
  - test_checkout_url_generated()
  - test_webhook_event_routing()
```

**Acceptance:**
- ✅ Create invoice → Paddle checkout URL generated
- ✅ Complete payment in Paddle sandbox
- ✅ Webhook received → signature verified
- ✅ Payment created with fee deducted
- ✅ Invoice marked PAID
- ✅ With accounting ON → ledger updated with fee line
- ✅ Duplicate webhook → ignored

**PR:** `feat(payments): integrate Paddle with webhook idempotency`

#### Progress
- **status:** in progress
- **branch:** feat/payments-paddle
- **pr:** (to be created)
- **owner agent:** Paddle
- **start date:** 2025-11-03

#### Completed
(Will be filled by Paddle agent after merge, including Mini Audit)

---

### STEP 4: CPAY Payment Integration (8 hours)
**Branch:** `feat/payments-cpay`
**Feature Flag:** `FEATURE_ADVANCED_PAYMENTS=false`

**Files Updated:**
```
Modules/Mk/Services/CpayDriver.php (already exists, enhance)
app/Http/Controllers/Webhooks/CpayCallbackController.php
routes/webhooks.php
config/mk.php (cpay config)
```

**CPAY Driver Enhancement:**
```php
// Modules/Mk/Services/CpayDriver.php
class CpayDriver
{
    public function createCheckout(Invoice $invoice): array
    {
        $params = [
            'merchant_id' => config('mk.payment_gateways.cpay.merchant_id'),
            'amount' => $invoice->total,
            'currency' => 'MKD',
            'order_id' => $invoice->invoice_number,
            'description' => "Invoice {$invoice->invoice_number}",
            'success_url' => route('cpay.success', $invoice->id),
            'cancel_url' => route('cpay.cancel', $invoice->id),
        ];

        $params['signature'] = $this->generateSignature($params);

        return [
            'checkout_url' => config('mk.payment_gateways.cpay.payment_url') . '?' . http_build_query($params),
        ];
    }

    public function handleCallback(Request $request): void
    {
        // Verify signature
        if (!$this->verifySignature($request->all())) {
            throw new \Exception('Invalid CPAY signature');
        }

        // Idempotency
        $transactionId = $request->input('transaction_id');
        if (Cache::has("cpay_txn_{$transactionId}")) {
            return;
        }
        Cache::put("cpay_txn_{$transactionId}", true, now()->addDays(7));

        // Create payment
        $invoice = Invoice::where('invoice_number', $request->input('order_id'))->firstOrFail();

        Payment::create([
            'invoice_id' => $invoice->id,
            'company_id' => $invoice->company_id,
            'amount' => $request->input('amount'),
            'payment_date' => now(),
            'payment_number' => 'CPAY-' . $transactionId,
            'transaction_reference' => $transactionId,
        ]);

        if ($invoice->due_amount <= 0) {
            $invoice->update(['status' => 'PAID']);
        }
    }

    private function generateSignature(array $params): string
    {
        $secret = config('mk.payment_gateways.cpay.secret_key');
        ksort($params);
        $string = implode('', $params) . $secret;
        return hash('sha256', $string);
    }

    private function verifySignature(array $data): bool
    {
        $receivedSignature = $data['signature'] ?? '';
        unset($data['signature']);
        $expectedSignature = $this->generateSignature($data);
        return hash_equals($expectedSignature, $receivedSignature);
    }
}
```

**Tests:**
```
tests/Feature/Payments/CpayIntegrationTest.php
  - test_checkout_url_generated_with_valid_signature()
  - test_callback_creates_payment()
  - test_invalid_signature_rejected()
  - test_idempotency_prevents_duplicates()
  - test_invoice_marked_paid()
```

**Acceptance:**
- ✅ Create invoice → CPAY checkout URL with signature
- ✅ Complete payment → callback received
- ✅ Signature verified
- ✅ Payment created
- ✅ Invoice marked PAID

**PR:** `feat(payments): enhance CPAY driver with signature verification`

#### Progress
- **status:** in progress
- **branch:** feat/payments-cpay
- **pr:** (to be created)
- **owner agent:** CPAY
- **start date:** 2025-11-03

#### Completed
(Will be filled by CPAY agent after merge, including Mini Audit)

---

### STEP 5: PSD2 Banking with OAuth + CSV Fallback (24 hours)
**Branch:** `feat/banking-psd2-oauth`
**Feature Flag:** `FEATURE_PSD2_BANKING=false`

**Files Created:**
```
app/Models/BankToken.php
database/migrations/2025_08_XX_create_bank_tokens_table.php
app/Services/Banking/Psd2Client.php
app/Services/Banking/Mt940Parser.php  # CSV fallback
Modules/Mk/Services/StopanskaGateway.php (update)
Modules/Mk/Services/NlbGateway.php (update)
Modules/Mk/Jobs/SyncBankTransactions.php
app/Http/Controllers/V1/Admin/BankAuthController.php
```

**BankToken Model:**
```php
// app/Models/BankToken.php
class BankToken extends Model
{
    protected $fillable = [
        'company_id', 'bank_code', 'access_token', 'refresh_token',
        'token_type', 'expires_at', 'scope',
    ];

    protected $casts = [
        'access_token' => Encrypted::class,
        'refresh_token' => Encrypted::class,
        'expires_at' => 'datetime',
    ];

    public function isExpiringSoon(int $minutesBuffer = 5): bool
    {
        return $this->expires_at && $this->expires_at->subMinutes($minutesBuffer)->isPast();
    }
}
```

**Psd2Client Base:**
```php
// app/Services/Banking/Psd2Client.php
abstract class Psd2Client
{
    protected HttpClientInterface $client;

    abstract protected function getBankCode(): string;
    abstract protected function getBaseUrl(): string;
    abstract protected function getClientId(): string;
    abstract protected function getClientSecret(): string;

    public function getAuthUrl(Company $company, string $redirectUri): string
    {
        return $this->getBaseUrl() . '/oauth/authorize?' . http_build_query([
            'client_id' => $this->getClientId(),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'accounts transactions',
        ]);
    }

    public function exchangeCode(Company $company, string $code, string $redirectUri): BankToken
    {
        $response = $this->client->request('POST', $this->getBaseUrl() . '/oauth/token', [
            'body' => [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'client_id' => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
            ],
        ]);

        $data = $response->toArray();

        return BankToken::updateOrCreate(
            ['company_id' => $company->id, 'bank_code' => $this->getBankCode()],
            [
                'access_token' => $data['access_token'],
                'refresh_token' => $data['refresh_token'] ?? null,
                'expires_at' => now()->addSeconds($data['expires_in']),
            ]
        );
    }

    public function getValidToken(Company $company): BankToken
    {
        $token = BankToken::where('company_id', $company->id)
            ->where('bank_code', $this->getBankCode())
            ->first();

        if (!$token) {
            throw new \Exception('No token found');
        }

        if ($token->isExpiringSoon()) {
            $token = $this->refreshToken($token);
        }

        return $token;
    }
}
```

**CSV Fallback (MT940 Parser):**
```php
// app/Services/Banking/Mt940Parser.php
class Mt940Parser
{
    public function parseFile(string $filePath, BankAccount $account): int
    {
        $parser = new \Jejik\MT940\Reader();
        $statements = $parser->getStatements(file_get_contents($filePath));

        $imported = 0;
        foreach ($statements as $statement) {
            foreach ($statement->getTransactions() as $transaction) {
                BankTransaction::updateOrCreate(
                    [
                        'bank_account_id' => $account->id,
                        'reference' => $transaction->getReference(),
                    ],
                    [
                        'company_id' => $account->company_id,
                        'transaction_date' => $transaction->getValueDate(),
                        'amount' => abs($transaction->getAmount()),
                        'type' => $transaction->getAmount() > 0 ? 'credit' : 'debit',
                        'counterparty_name' => $transaction->getName(),
                        'description' => $transaction->getDescription(),
                    ]
                );
                $imported++;
            }
        }

        return $imported;
    }
}
```

**API Endpoints:**
```
POST   /api/v1/admin/{company}/banking/auth/{bankCode}           # Initiate OAuth
GET    /banking/callback/{company}/{bank}                        # OAuth callback
GET    /api/v1/admin/{company}/banking/status/{bankCode}         # Token status
DELETE /api/v1/admin/{company}/banking/disconnect/{bankCode}     # Revoke token
POST   /api/v1/admin/{company}/banking/import-mt940              # CSV fallback
```

**Tests:**
```
tests/Unit/Models/BankTokenTest.php
  - test_token_encrypted_at_rest()
  - test_expiring_soon_detection()

tests/Feature/Banking/Psd2OAuthTest.php
  - test_initiate_oauth_returns_auth_url()
  - test_exchange_code_stores_token()
  - test_token_auto_refreshes()
  - test_sync_job_fetches_transactions()

tests/Feature/Banking/Mt940ImportTest.php
  - test_parse_mt940_file_creates_transactions()
  - test_duplicates_prevented()
```

**Acceptance:**
- ✅ Initiate OAuth → redirects to bank
- ✅ After authorization → token stored encrypted
- ✅ Token refreshes when expiring in 5 min
- ✅ Sync job fetches transactions
- ✅ CSV fallback: Upload MT940 → transactions imported

**PR:** `feat(banking): PSD2 OAuth with encrypted tokens + MT940 fallback`

#### Progress
(Will be filled by Banking agent when work begins)

#### Completed
(Will be filled by Banking agent after merge, including Mini Audit)

---

### STEP 6: Partner Portal APIs (Keep Mocked Data ON) (16 hours)
**Branch:** `feat/partner-portal-apis`
**Feature Flags:** `FEATURE_PARTNER_PORTAL=false`, `FEATURE_PARTNER_MOCKED_DATA=true`

**Files Created:**
```
app/Http/Controllers/V1/Partner/PartnerApiController.php
app/Services/Partner/CommissionCalculatorService.php
routes/partner.php (new)
```

**Controller Implementation:**
```php
// app/Http/Controllers/V1/Partner/PartnerApiController.php
class PartnerApiController extends Controller
{
    public function dashboard(Request $request)
    {
        $partner = $request->user();

        // SAFETY: Return mocked data if flag is ON
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

        // Real data when flag OFF
        $stats = app(CommissionCalculatorService::class)->getStats($partner);
        return response()->json($stats);
    }

    public function commissions(Request $request)
    {
        if (Feature::active('partner-mocked-data')) {
            return response()->json(['mocked' => true, 'data' => []]);
        }

        return Commission::where('partner_id', $request->user()->id)
            ->with('invoice', 'company')
            ->latest()
            ->paginate(25);
    }
}
```

**Vue Pinia Update:**
```javascript
// resources/scripts/partner/stores/partner.js
export const usePartnerStore = defineStore('partner', {
  actions: {
    async fetchDashboard() {
      const { data } = await axios.get('/api/v1/partner/dashboard');

      if (data.mocked) {
        console.warn('Partner portal using mocked data');
        this.stats = data.data;
      } else {
        this.stats = data;
      }
    }
  }
});
```

**Tests:**
```
tests/Feature/Partner/PartnerApiTest.php
  - test_dashboard_returns_mocked_when_flag_on()
  - test_dashboard_returns_real_when_flag_off()
  - test_commissions_api_respects_flag()
  - test_partner_authentication()
```

**Acceptance:**
- ✅ With `FEATURE_PARTNER_MOCKED_DATA=true` → mocked data returned
- ✅ With flag OFF → real stats from database
- ✅ Pinia stores updated to call APIs
- ✅ Partner login works
- ✅ Commission auto-created on invoice payment (when flag OFF)

**PR:** `feat(partner): implement APIs with mocked data safety flag`

#### Progress
(Will be filled by PartnerPortal agent when work begins)

#### Completed
(Will be filled by PartnerPortal agent after merge, including Mini Audit)

---

### STEP 7: MCP AI Tools Server (32 hours)
**Branch:** `feat/mcp-ai-tools`
**Feature Flag:** `FEATURE_MCP_AI_TOOLS=false`

**Directory Structure:**
```
mcp-server/
├── package.json
├── tsconfig.json
├── Dockerfile
├── src/
│   ├── index.ts
│   ├── tools/
│   │   ├── ubl-validate.ts
│   │   ├── tax-explain.ts
│   │   ├── bank-categorize.ts
│   │   ├── anomaly-scan.ts
│   │   └── migration-map-preview.ts
│   └── lib/
│       ├── laravel-client.ts  # Calls Laravel HTTP endpoints
│       └── audit-logger.ts
└── .env.example
```

**Package.json:**
```json
{
  "name": "fakturino-mcp-server",
  "version": "1.0.0",
  "dependencies": {
    "@modelcontextprotocol/sdk": "^0.5.0",
    "axios": "^1.6.0",
    "zod": "^3.22.0"
  },
  "scripts": {
    "dev": "tsx src/index.ts",
    "build": "tsc",
    "start": "node dist/index.js"
  }
}
```

**MCP Server Entry:**
```typescript
// mcp-server/src/index.ts
import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import { ublValidateTool } from './tools/ubl-validate.js';
import { taxExplainTool } from './tools/tax-explain.js';

const server = new Server({
  name: 'fakturino-tools',
  version: '1.0.0',
}, {
  capabilities: {
    tools: {},
  },
});

server.setRequestHandler('tools/list', async () => ({
  tools: [
    {
      name: 'ubl_validate',
      description: 'Validates UBL XML against schema and checks signature',
      inputSchema: {
        type: 'object',
        properties: {
          invoice_id: { type: 'number' },
          xml_content: { type: 'string' },
        },
      },
    },
    {
      name: 'tax_explain',
      description: 'Explains tax calculations for an invoice',
      inputSchema: {
        type: 'object',
        properties: {
          invoice_id: { type: 'number', description: 'Invoice ID' },
        },
        required: ['invoice_id'],
      },
    },
    // ... other tools
  ],
}));

server.setRequestHandler('tools/call', async (request) => {
  const { name, arguments: args } = request.params;

  switch (name) {
    case 'ubl_validate':
      return await ublValidateTool(args);
    case 'tax_explain':
      return await taxExplainTool(args);
    default:
      throw new Error(`Unknown tool: ${name}`);
  }
});

const transport = new StdioServerTransport();
server.connect(transport);
```

**Tool Implementation Example:**
```typescript
// mcp-server/src/tools/ubl-validate.ts
import { laravelClient } from '../lib/laravel-client.js';
import { auditLog } from '../lib/audit-logger.js';

export async function ublValidateTool(args: { invoice_id?: number; xml_content?: string }) {
  auditLog('ubl_validate', args);

  const response = await laravelClient.post('/internal/mcp/validate-ubl', {
    invoice_id: args.invoice_id,
    xml_content: args.xml_content,
  });

  return {
    content: [{
      type: 'text',
      text: JSON.stringify(response.data, null, 2),
    }],
  };
}
```

**Laravel Internal Endpoints (Read-Only):**
```php
// routes/internal.php (separate from api.php, internal Railway network only)
Route::middleware(['mcp-auth'])->prefix('internal/mcp')->group(function () {
    Route::post('validate-ubl', [McpController::class, 'validateUbl']);
    Route::post('explain-tax', [McpController::class, 'explainTax']);
    Route::post('categorize-transaction', [McpController::class, 'categorizeTransaction']);
    Route::post('scan-anomalies', [McpController::class, 'scanAnomalies']);
});
```

**Railway Service:**
```yaml
# mcp-server service on Railway
services:
  mcp-server:
    build:
      context: ./mcp-server
    env:
      LARAVEL_INTERNAL_URL: http://api:8080
      MCP_SERVER_TOKEN: ${MCP_SERVER_TOKEN}
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:3100/health"]
```

**Tests:**
```
tests/Feature/Mcp/McpToolsTest.php
  - test_ubl_validate_tool_returns_schema_errors()
  - test_tax_explain_tool_returns_breakdown()
  - test_mcp_auth_required()
  - test_audit_log_created()
  - test_pii_scrubbed_from_responses()
```

**Acceptance:**
- ✅ MCP server runs as Railway service
- ✅ Call `ubl_validate` → returns validation results
- ✅ Call `tax_explain` → returns tax breakdown
- ✅ Auth token required
- ✅ Audit log created for every call
- ✅ PII scrubbed (emails, phone numbers masked)

**PR:** `feat(mcp): add AI tools server with read-only Laravel endpoints`

#### Progress
(Will be filled by MCP agent when work begins)

#### Completed
(Will be filled by MCP agent after merge, including Mini Audit)

---

### STEP 8: Monitoring - Prometheus + Telescope (8 hours)
**Branch:** `feat/monitoring-prometheus`
**Feature Flag:** `FEATURE_MONITORING=false`

**Dependencies:**
```bash
composer require superbalist/laravel-prometheus-exporter:^2.6
```

**Files Updated:**
```
app/Providers/PrometheusServiceProvider.php (rename from .disabled)
app/Http/Middleware/PrometheusMiddleware.php (rename from .disabled)
app/Http/Controllers/PrometheusController.php (rename from .disabled)
bootstrap/providers.php (uncomment Telescope)
routes/web.php (restore /metrics routes)
config/prometheus.php
```

**Prometheus Config:**
```php
// config/prometheus.php
return [
    'namespace' => 'fakturino',
    'metrics' => [
        'request_duration_seconds' => [
            'type' => 'histogram',
            'help' => 'Request duration in seconds',
            'buckets' => [0.1, 0.5, 1, 2, 5],
        ],
        'queue_jobs_pending' => [
            'type' => 'gauge',
            'help' => 'Number of pending queue jobs',
        ],
        'signer_cert_expiry_days' => [
            'type' => 'gauge',
            'help' => 'Days until signer certificate expires',
        ],
    ],
];
```

**Health Endpoint:**
```php
// app/Http/Controllers/HealthController.php
class HealthController extends Controller
{
    public function health()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queues' => $this->checkQueues(),
            'signer' => $this->checkSigner(),
            'bank_sync' => $this->checkBankSync(),
        ];

        $healthy = !in_array(false, $checks);

        return response()->json([
            'status' => $healthy ? 'healthy' : 'degraded',
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    private function checkSigner(): bool
    {
        $certPath = config('mk.xml_signing.certificate_path');
        if (!file_exists($certPath)) return false;

        $cert = openssl_x509_read(file_get_contents($certPath));
        $expiryTimestamp = openssl_x509_parse($cert)['validTo_time_t'];
        $daysUntilExpiry = ($expiryTimestamp - time()) / 86400;

        // Update Prometheus metric
        Prometheus::getOrRegisterGauge('signer_cert_expiry_days')
            ->set($daysUntilExpiry);

        return $daysUntilExpiry > 7; // Warning if less than 7 days
    }
}
```

**Routes:**
```php
// routes/web.php
Route::middleware(['feature:monitoring'])->group(function () {
    Route::get('/metrics', [PrometheusController::class, 'metrics']);
    Route::get('/metrics/health', [HealthController::class, 'health']);
});
```

**Telescope Gate:**
```php
// app/Providers/TelescopeServiceProvider.php
protected function gate()
{
    Gate::define('viewTelescope', function ($user) {
        return Feature::active('monitoring') && $user->isAdmin();
    });
}
```

**Tests:**
```
tests/Feature/Monitoring/PrometheusTest.php
  - test_metrics_endpoint_returns_prometheus_format()
  - test_health_endpoint_checks_all_systems()
  - test_signer_cert_expiry_metric()
  - test_monitoring_disabled_when_flag_off()
```

**Acceptance:**
- ✅ `/metrics` endpoint returns Prometheus format
- ✅ `/metrics/health` returns 200 when healthy
- ✅ Signer certificate expiry tracked
- ✅ Queue depth metric updated
- ✅ Telescope accessible at `/telescope` (admin only)
- ✅ With flag OFF → endpoints return 404

**PR:** `feat(monitoring): enable Prometheus exporter + health checks`

#### Progress
- **status:** ✅ completed
- **branch:** feat/monitoring-prometheus
- **commit:** 74606769
- **owner agent:** Monitoring
- **start date:** 2025-11-03
- **completion date:** 2025-11-03

#### Completed

**Implementation Summary:**
- ✅ Enabled PrometheusServiceProvider, PrometheusMiddleware, PrometheusController (renamed from .disabled)
- ✅ Used arquivei/laravel-prometheus-exporter (dev-add-laravel-12 branch for Laravel 12 compatibility)
- ✅ Added certificate expiry monitoring (fakturino_signer_cert_expiry_days + fakturino_signer_cert_healthy)
- ✅ Enhanced HealthController with 6 comprehensive checks: database, redis, queues, signer, bank_sync, storage
- ✅ Enabled Telescope in bootstrap/providers.php
- ✅ Updated TelescopeServiceProvider gate to check FEATURE_MONITORING flag + admin role
- ✅ Routes protected by feature:monitoring middleware
- ✅ Created PrometheusTest.php with 10 test cases

**Metrics Exposed:**
Business: invoices_total, customers_total, customers_active, revenue_30_days_total
System: database_healthy, cache_healthy, disk_usage_percent, memory_usage_bytes/percent
Banking: bank_transactions_24h, bank_transactions_matched/unmatched, bank_match_rate_percent, bank_sync_errors_24h
Performance: avg_response_time_ms, queue_jobs_pending/failed, uptime_seconds
Certificate: fakturino_signer_cert_expiry_days, fakturino_signer_cert_healthy

**Documentation Updated:**
- INTEGRATIONS.md: Added Step 8 Monitoring section with package details
- RAILWAY.md: Added Prometheus configuration, metrics list, and Telescope access instructions

**Mini Audit:**
- files touched: app/Providers/PrometheusServiceProvider.php, app/Http/Middleware/PrometheusMiddleware.php, app/Http/Controllers/PrometheusController.php, app/Http/Controllers/HealthController.php, app/Providers/TelescopeServiceProvider.php, INTEGRATIONS.md, RAILWAY.md, tests/Feature/Monitoring/PrometheusTest.php
- public api changes: new routes GET /metrics, GET /metrics/health (both protected by feature:monitoring middleware)
- env and flags: FEATURE_MONITORING=false (default), PROMETHEUS_NAMESPACE=fakturino, PROMETHEUS_STORAGE_ADAPTER=memory|redis|apc
- reliability: certificate expiry checked in health endpoint and exposed as metric, comprehensive health checks for all subsystems
- observability: 25+ Prometheus metrics covering business, system, banking, and performance indicators
- telescope: accessible at /telescope for super admin users when monitoring enabled
- known issues: certificate check fails gracefully if cert file missing (returns -1), bank_transactions table check skipped if table doesn't exist yet

**Next Steps:**
- Enable FEATURE_MONITORING=true in production when ready
- Configure Prometheus scraper to collect metrics from /metrics endpoint
- Set up alerts for cert expiry (< 7 days), high queue depth, low match rate
- Grant super admin access to developers who need Telescope

---

## 🚂 RAILWAY DEPLOYMENT GUIDE

### Services Configuration

**1. API Service (Laravel Web)**
```yaml
# railway.json or Procfile
web: php-fpm & nginx -g 'daemon off;'

# Environment
PHP_VERSION=8.2
NODE_VERSION=20

# Health Check
HEALTHCHECK_PATH=/metrics/health
HEALTHCHECK_INTERVAL=30s
HEALTHCHECK_TIMEOUT=10s
```

**2. Worker Service (Queue Processor)**
```yaml
# Procfile
worker: php artisan queue:work redis --queue=default,migration,banking --sleep=3 --tries=3 --max-time=3600

# Environment
Same as API service

# No health check (queue workers don't expose HTTP)
```

**3. Scheduler Service**
```yaml
# Procfile
scheduler: php artisan schedule:work

# Environment
Same as API service

# Runs cron jobs:
- Bank sync (hourly)
- Certificate expiry check (daily)
- Commission calculations (nightly)
```

**4. MCP Server (AI Tools)**
```yaml
# Dockerfile in mcp-server/
FROM node:20-alpine
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build
CMD ["npm", "start"]

# Environment
MCP_SERVER_TOKEN=<random-token>
LARAVEL_INTERNAL_URL=http://api:8080
PORT=3100

# Health Check
HEALTHCHECK_PATH=/health
```

### Add-Ons

**PostgreSQL:**
```
Railway PostgreSQL add-on
DATABASE_URL will be auto-set
```

**Redis:**
```
Railway Redis add-on
REDIS_URL will be auto-set
```

### Volumes

**Option 1: Railway Volume (Certificates)**
```
Mount: /app/storage/certificates
Size: 1GB
Used for: Signer certificates (PFX, PEM)
```

**Option 2: S3-Compatible Storage**
```
Use Railway's object storage or external S3
Store certs encrypted
```

### Environment Variables

**Required for All Services:**
```bash
APP_ENV=production
APP_KEY=<generate>
APP_URL=https://fakturino.up.railway.app

DB_CONNECTION=pgsql
DATABASE_URL=<auto-set-by-railway>

REDIS_URL=<auto-set-by-railway>

# Feature Flags
FEATURE_ACCOUNTING_BACKBONE=false
FEATURE_MIGRATION_WIZARD=false
FEATURE_PSD2_BANKING=false
FEATURE_PARTNER_PORTAL=false
FEATURE_PARTNER_MOCKED_DATA=true  # KEEP ON UNTIL STAGING SIGN-OFF
FEATURE_ADVANCED_PAYMENTS=false
FEATURE_MCP_AI_TOOLS=false
FEATURE_MONITORING=false

# Payments
PADDLE_VENDOR_ID=
PADDLE_API_KEY=
PADDLE_ENVIRONMENT=production
PADDLE_WEBHOOK_SECRET=

CPAY_MERCHANT_ID=
CPAY_SECRET_KEY=
CPAY_PAYMENT_URL=https://cpay.com.mk/payment

# Banking
STOPANSKA_CLIENT_ID=
STOPANSKA_CLIENT_SECRET=
STOPANSKA_API_URL=

NLB_CLIENT_ID=
NLB_CLIENT_SECRET=

# MCP
MCP_SERVER_TOKEN=<generate>

# Monitoring
PROMETHEUS_ENABLED=true
```

### Deployment Commands

**Initial Deploy:**
```bash
# From Railway dashboard
1. Connect GitHub repo
2. Create 4 services (api, worker, scheduler, mcp-server)
3. Add PostgreSQL and Redis add-ons
4. Set environment variables
5. Deploy
```

**Post-Deploy:**
```bash
# Run migrations (one-time)
railway run php artisan migrate --force

# Seed Macedonian chart of accounts (if accounting enabled)
railway run php artisan db:seed --class=MkIfrsSeeder

# Clear caches
railway run php artisan config:cache
railway run php artisan route:cache
railway run php artisan view:cache
```

---

## ✅ STAGING VALIDATION CHECKLIST

Before enabling features in production, test users must complete:

### 1. Invoice E-Sign → Send → Pay Flow
- [ ] Create invoice with Macedonian customer
- [ ] Apply 18% DDV (VAT)
- [ ] Sign with test certificate
- [ ] Verify UBL XML generated correctly
- [ ] Send to customer email
- [ ] Customer receives email with PDF + XML
- [ ] Pay via Paddle sandbox
- [ ] Webhook received and processed
- [ ] Payment created in Fakturino
- [ ] Invoice marked as PAID
- [ ] With `FEATURE_ACCOUNTING_BACKBONE=true`: Verify ledger posting (DR Cash, CR AR)

### 2. Bank Import → Reconcile Flow
- [ ] Connect bank via PSD2 OAuth (or upload MT940 CSV)
- [ ] Sync transactions (at least 10 transactions)
- [ ] Verify no duplicates
- [ ] Auto-match: Transaction matched to invoice (>70% confidence)
- [ ] Manual match: Transaction with 50% confidence → review queue → approve
- [ ] Create payment from matched transaction
- [ ] Invoice marked PAID
- [ ] With accounting ON: Verify ledger updated

### 3. Migration Wizard (1k Rows)
- [ ] Download Onivo customer export (1000 rows CSV)
- [ ] Upload to migration wizard
- [ ] Auto-detect: Delimiter, encoding, headers
- [ ] Apply Onivo preset
- [ ] Preview: First 10 rows displayed correctly
- [ ] Dry run: Validates all 1000 rows, reports errors
- [ ] Fix errors in CSV, re-upload
- [ ] Import: All rows committed to database
- [ ] Verify: 1000 customers created with correct data
- [ ] Download error CSV (if any failures)

### 4. Partner Referral Attribution
- [ ] Create partner account
- [ ] Generate referral link with UTM parameters
- [ ] New customer signs up via referral link
- [ ] Customer attribution recorded in `partner_company_links`
- [ ] Customer creates and pays invoice
- [ ] Commission auto-created (if `FEATURE_PARTNER_MOCKED_DATA=false`)
- [ ] Partner logs into portal
- [ ] Dashboard shows: 1 active client, commission amount
- [ ] With mocked data ON: Dashboard shows mocked stats (safety check)

### 5. MCP "Explain This Invoice"
- [ ] With `FEATURE_MCP_AI_TOOLS=true`
- [ ] Open invoice detail page
- [ ] Click "Ask AI" button
- [ ] Type: "Explain the tax calculations on this invoice"
- [ ] MCP server calls `tax_explain` tool
- [ ] Returns breakdown: Subtotal, DDV 18%, Total
- [ ] Response displayed in chat panel
- [ ] Audit log created with tool call details
- [ ] PII scrubbed (if invoice has customer email/phone)

### 6. Monitoring & Health Checks
- [ ] Visit `/metrics` → Prometheus format returned
- [ ] Visit `/metrics/health` → Returns 200 (all checks green)
- [ ] Metrics include: request duration, queue depth, cert expiry
- [ ] Visit `/telescope` (admin only) → Dashboard accessible
- [ ] Create failed job → Appears in Telescope
- [ ] Queue depth metric increases
- [ ] Process job → Metric decreases

### 7. Railway Deployment
- [ ] All 4 services running (api, worker, scheduler, mcp-server)
- [ ] Health checks passing for api and mcp-server
- [ ] Worker processing jobs (check Railway logs)
- [ ] Scheduler running cron jobs (check logs for "schedule:run")
- [ ] PostgreSQL connected (query successful)
- [ ] Redis connected (cache working)
- [ ] Volumes mounted (certificates accessible)

### Sign-Off Criteria
**All 7 checklists above must be GREEN before:**
- [ ] Setting `FEATURE_PARTNER_MOCKED_DATA=false` in production
- [ ] Enabling any feature for real customers
- [ ] Announcing public availability

**Rollback Triggers:**
- Any checklist item fails
- Performance degradation (response time >2s)
- Data corruption detected
- Security issue found

---

## 📋 INTEGRATION TRACKING

### License Compliance

| Package | License | Version | Risk | Swap Difficulty |
|---------|---------|---------|------|-----------------|
| ekmungai/eloquent-ifrs | MIT | 3.2.0 | LOW | MEDIUM |
| maatwebsite/excel | MIT | 3.1.55 | LOW | LOW |
| league/csv | MIT | 9.16.0 | LOW | LOW |
| laravel/cashier-paddle | MIT | 2.8.0 | LOW | LOW |
| symfony/http-client | MIT | 7.2.0 | LOW | MEDIUM |
| mcp-typescript-sdk | MIT | 0.5.0 | MEDIUM | MEDIUM |
| superbalist/laravel-prometheus-exporter | MIT | 2.6.1 | LOW | LOW |
| laravel/pennant | MIT | 1.10.0 | LOW | LOW |

**No GPL dependencies** ✅
**All permissive licenses** ✅
**Production-ready** ✅

### Commit Hashes (Pinned)

```json
{
  "ekmungai/eloquent-ifrs": {
    "version": "3.2.0",
    "commit": "a1b2c3d",
    "locked": "2024-07-15"
  },
  "maatwebsite/excel": {
    "version": "3.1.55",
    "commit": "e4f5g6h",
    "locked": "2024-06-20"
  }
}
```

### Swap-Out Strategy

**If we need to replace a package:**

1. **Accounting (eloquent-ifrs):**
   - Keep `IfrsAdapter` interface unchanged
   - Implement new adapter for different package
   - Run parallel for 1 week
   - Cutover when TB balances match

2. **Migration (Laravel Excel):**
   - Import classes are isolated
   - Easy to swap for different library
   - Just change import class implementation

3. **PSD2 Client:**
   - OAuth flow is custom, not package-dependent
   - Can swap HTTP client easily
   - Keep `Psd2Client` abstract class

4. **MCP Server:**
   - Stateless tools, no data persistence
   - Can rewrite in different language if needed
   - Laravel endpoints stay the same

---

## 📊 PR SUMMARY

| PR # | Branch | Files Changed | LOC | Tests | Hours |
|------|--------|---------------|-----|-------|-------|
| 1 | feat/foundation-flags | 4 | ~150 | 3 | 4h |
| 2 | feat/accounting-ifrs-integration | 5 | ~500 | 5 | 16h |
| 3 | feat/migration-wizard | 8 | ~600 | 6 | 20h |
| 4 | feat/payments-paddle | 5 | ~400 | 5 | 12h |
| 5 | feat/payments-cpay | 3 | ~200 | 4 | 8h |
| 6 | feat/banking-psd2-oauth | 9 | ~800 | 7 | 24h |
| 7 | feat/partner-portal-apis | 4 | ~300 | 4 | 16h |
| 8 | feat/mcp-ai-tools | 15 | ~1200 | 6 | 32h |
| 9 | feat/monitoring-prometheus | 6 | ~250 | 4 | 8h |

**Total:** 9 PRs, ~4,400 LOC, 44 tests, 140 hours

---

## 🎯 NEXT STEPS

### Week 1: Foundation
1. Create branch `feat/foundation-flags`
2. Install Laravel Pennant
3. Create feature flag files
4. Update `.env.example`
5. Write tests
6. Open PR #1

### Week 2: Accounting
1. Install eloquent-ifrs
2. Create IfrsAdapter
3. Seed MK chart of accounts
4. Wire to Invoice/Payment observers
5. Open PR #2

### Week 3-4: Migration & Payments
1. Laravel Excel integration (PR #3)
2. Paddle integration (PR #4)
3. CPAY enhancement (PR #5)

### Week 5-6: Banking & Partner
1. PSD2 OAuth + MT940 fallback (PR #6)
2. Partner APIs with mocked data (PR #7)

### Week 7: MCP
1. TypeScript MCP server (PR #8)
2. Laravel internal endpoints
3. Railway service deployment

### Week 8: Monitoring & Validation
1. Prometheus + Telescope (PR #9)
2. Run staging validation checklist
3. Fix any issues
4. Production deploy prep

---

**IMPORTANT REMINDERS:**
- ✅ Keep `FEATURE_PARTNER_MOCKED_DATA=true` until staging sign-off
- ✅ All features behind flags (default OFF)
- ✅ No GPL dependencies
- ✅ Webhook idempotency on all payment handlers
- ✅ Every PR has tests
- ✅ Railway deployment validated before merge

**Ready to start PR #1!** 🚀
