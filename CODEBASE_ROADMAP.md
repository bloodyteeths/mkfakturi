# Facturino Codebase Roadmap
**Version:** 1.1
**Last Updated:** 2026-02-05
**Maps to:** PAYMENT_INFRASTRUCTURE_ROADMAP.md

This technical roadmap maps 1:1 to the investment roadmap phases. Each ticket follows CLAUDE.md conventions.

---

## Ticket ID Conventions

| Prefix | Domain | Example |
|--------|--------|---------|
| **P0-** | Phase 0: CSV Import & Reconciliation | P0-01 |
| **P1-** | Phase 1: PSD2 + Foundation | P1-01 |
| **P2-** | Phase 2: Payment Rails | P2-01 |
| **P3-** | Phase 3: Open Banking Platform | P3-01 |
| **P4-** | Phase 4: E-Invoicing & Compliance | P4-01 |
| **P5-** | Phase 5: Developer Platform | P5-01 |
| **P6-** | Phase 6: Expansion & Scale | P6-01 |
| **NX-** | Blocked/Needs Review | NX-01 |

---

## Current Codebase State

### Existing Infrastructure
| Component | Location | Status |
|-----------|----------|--------|
| Invoicing Core | `app/Models/Invoice.php` | ✅ Production |
| Payments | `app/Models/Payment.php` | ✅ Production |
| Bank Transactions | `app/Models/BankTransaction.php` | ✅ Production |
| **Matcher Service** | `modules/Mk/Services/Matcher.php` | ✅ Production |
| **Reconciliation API** | `modules/Mk/Http/Controllers/ReconciliationController.php` | ✅ Production |
| **Reconciliation UI** | `resources/scripts/admin/views/banking/InvoiceReconciliation.vue` | ✅ Production |
| PSD2 Client | `app/Services/Banking/Psd2Client.php` | 🟡 Sandbox |
| MT940 Parser | `app/Services/Banking/Mt940Parser.php` | ✅ Ready |
| CSV Import | `app/Services/Migration/Parsers/CsvParserService.php` | ✅ Ready |
| CASYS Gateway | Planned | 🔴 Not started |

### File Boundaries (per CLAUDE.md)
```
New PHP code      → app/Services/Reconciliation/**
                  → app/Services/Banking/**
                  → app/Http/Controllers/Api/v1/**
New Vue components → resources/js/pages/reconciliation/**
                   → resources/js/pages/banking/**
Migrations        → database/migrations/2026_02_**.php
```

---

## Cross-Cutting Concerns

### Data Retention & PII Policy

Bank data and PII must be handled carefully for GDPR compliance.

**Retention defaults:**
| Data Type | Retention | Justification |
|-----------|-----------|---------------|
| Bank transactions (raw_data) | 24 months | Tax audit requirements |
| Bank transactions (derived) | 7 years | Accounting records |
| Bank consents | Until revoked + 30 days | Cleanup after user action |
| Webhook payloads | 30 days | Debugging only |
| Import logs | 12 months | Analytics |
| Reconciliation history | 7 years | Audit trail |

**GDPR delete workflow:**
```php
// When company is deleted, cascade:
// 1. bank_transactions (company_id FK with CASCADE)
// 2. bank_consents (revoke tokens at bank, then delete)
// 3. reconciliations, payments (company_id FK)
// 4. webhook_events (no company_id, but sanitize any PII)
// 5. import_logs (company_id FK)
// 6. Audit logs - KEEP but anonymize (legal requirement)
```

**PII fields to protect:**
- `bank_transactions.raw_data` - may contain names, addresses
- `bank_transactions.counterparty_name`
- `bank_consents.access_token_encrypted` - already encrypted
- Webhook payloads - never log full payload

### Concurrency Controls

Prevent race conditions in reconciliation and matching.

**Row-level locking for matching:**
```php
// When matching a transaction, lock it to prevent concurrent matches
DB::transaction(function () use ($transactionId, $invoiceId) {
    // Lock the transaction row
    $tx = BankTransaction::lockForUpdate()->find($transactionId);

    // Check it's not already matched
    if ($tx->reconciliation_id !== null) {
        throw new AlreadyMatchedException();
    }

    // Create reconciliation
    $recon = Reconciliation::create([...]);
    $tx->update(['reconciliation_id' => $recon->id]);
});
```

**One active reconciliation per transaction (app-level enforcement):**

⚠️ **Note:** Partial unique indexes (`WHERE status != 'ignored'`) are not supported in MySQL and require raw SQL in PostgreSQL. Use app-level enforcement instead.

```php
// App-level enforcement (works on MySQL + PostgreSQL):
public function createReconciliation(BankTransaction $tx, Invoice $invoice): Reconciliation
{
    return DB::transaction(function () use ($tx, $invoice) {
        $tx = BankTransaction::lockForUpdate()->find($tx->id);

        // Check for existing active reconciliation
        $existing = Reconciliation::where('bank_transaction_id', $tx->id)
            ->whereNotIn('status', ['ignored', 'deleted'])
            ->exists();

        if ($existing) {
            throw new TransactionAlreadyReconciledException();
        }

        return Reconciliation::create([
            'bank_transaction_id' => $tx->id,
            'invoice_id' => $invoice->id,
            'status' => 'pending',
        ]);
    });
}
```

---

## Phase 0: Ship Value Without PSD2 (Feb-Mar 2026)

**Goal:** CSV import + reconciliation live in 2-4 weeks
**North Star:** 70%+ auto-match rate on pilot datasets

### Pre-Work: Dependencies & Requirements

#### P0-DEP: Required Dependencies
**Priority:** P0 | **Estimate:** 0.5 days | **Status:** 🔴 TODO

Before starting Phase 0, ensure these dependencies are installed:

**PHP Extensions (required):**
```
ext-intl       # Required for Normalizer class (fingerprint diacritics)
ext-mbstring   # Required for League CSV + UTF-8 handling
ext-bcmath     # Required for precise money calculations
```

**Composer Packages (Phase 0):**
```bash
# CSV parsing - DON'T hand-roll, use battle-tested library
composer require league/csv  # MIT license, already in whitelist

# Money handling - eliminates float bugs in payments/reconciliation
composer require brick/money  # MIT license, already in whitelist
```

**Composer Packages (Phase 1 - Webhooks + Observability):**
```bash
# Webhook receiving - replaces custom webhook framework
composer require spatie/laravel-webhook-client  # MIT license

# Webhook sending (if we send outbound webhooks to customers)
composer require spatie/laravel-webhook-server  # MIT license

# Audit logging - compliance-ready activity trail
composer require spatie/laravel-activitylog  # MIT license

# API documentation - auto-generate OpenAPI spec
composer require darkaonline/l5-swagger  # MIT license
# OR: composer require vyuldashev/laravel-openapi  # MIT license

# IBAN validation - MIT licensed alternative (NOT php-iban which is LGPL!)
composer require ixnode/php-iban  # MIT license
```

**Observability Stack (Phase 1):**
```bash
# OpenTelemetry for distributed tracing
composer require open-telemetry/sdk  # Apache 2.0
composer require open-telemetry/exporter-otlp  # Apache 2.0

# Laravel-specific instrumentation
composer require spatie/laravel-ray  # MIT (dev debugging)
```

⚠️ **IBAN Validation Warning - RESOLVED:**
- ✅ Use `ixnode/php-iban` (MIT license) - safe for SaaS
- ❌ AVOID `php-iban/php-iban` (LGPLv3) - copyleft risk
- Always verify license before adding IBAN-related packages

**Verification:**
```bash
# Check extensions
php -m | grep -E "intl|mbstring|bcmath"

# If missing, add to Dockerfile
RUN docker-php-ext-install intl bcmath
```

**Acceptance Criteria:**
- [ ] All required PHP extensions enabled in prod/dev
- [ ] League CSV installed and tested
- [ ] Brick\Money installed and integrated into fingerprint/reconciliation
- [ ] Spatie webhook-client configured (Phase 1)
- [ ] No unapproved dependencies added

---

### Pre-Work: Migration Verification

#### P0-00: Migration Patch + Index Verification
**Priority:** P0 | **Estimate:** 0.5 days | **Status:** 🔴 TODO

Verify existing 2025 migrations and add missing indexes/columns for Phase 0.

**Checklist:**
```php
// 1. Verify bank_transactions table exists with required columns
Schema::hasTable('bank_transactions'); // from 2025_07_25
Schema::hasColumn('bank_transactions', 'company_id');
Schema::hasColumn('bank_transactions', 'transaction_date');
// etc.

// 2. Add missing columns if not present
if (!Schema::hasColumn('bank_transactions', 'fingerprint')) {
    Schema::table('bank_transactions', function ($table) {
        $table->string('fingerprint', 64)->nullable();
        $table->string('external_transaction_id', 100)->nullable();
        $table->unique(['company_id', 'fingerprint']);
        $table->unique(['company_id', 'external_transaction_id']);
    });
}

// 3. Add performance indexes
Schema::table('bank_transactions', function ($table) {
    $table->index(['company_id', 'transaction_date']);
    $table->index(['company_id', 'type', 'status']);
});
```

**Migration file:**
```php
// database/migrations/2026_02_04_000001_phase0_schema_patch.php
public function up()
{
    // Idempotent - only add if missing
    if (!Schema::hasColumn('bank_transactions', 'fingerprint')) {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->string('fingerprint', 64)->nullable()->after('raw_data');
            $table->string('external_transaction_id', 100)->nullable()->after('fingerprint');
        });
    }

    // Add unique constraints (will fail if duplicates exist - handle in seeder)
    try {
        Schema::table('bank_transactions', function (Blueprint $table) {
            $table->unique(['company_id', 'fingerprint'], 'bank_tx_fingerprint_unique');
        });
    } catch (\Exception $e) {
        Log::warning('Fingerprint unique index already exists or has duplicates');
    }

    // Performance indexes - use safe methods that catch duplicates
    Schema::table('bank_transactions', function (Blueprint $table) {
        $this->safeAddIndex($table, ['company_id', 'transaction_date'], 'bank_tx_company_date_idx');
    });
}

/**
 * Safely add index - catches duplicate index errors across MySQL/PostgreSQL.
 * NOTE: We intentionally avoid Doctrine SchemaManager to prevent requiring
 * doctrine/dbal as a dependency. This try/catch approach is simpler and
 * works reliably on both databases.
 */
private function safeAddIndex(Blueprint $table, array $columns, string $indexName): void
{
    try {
        $table->index($columns, $indexName);
    } catch (\Illuminate\Database\QueryException $e) {
        // MySQL: 1061 "Duplicate key name"
        // PostgreSQL: 42P07 "relation already exists"
        if (!str_contains($e->getMessage(), 'Duplicate') &&
            !str_contains($e->getMessage(), 'already exists')) {
            throw $e;
        }
        Log::info("Index {$indexName} already exists, skipping");
    }
}

private function safeAddUnique(Blueprint $table, array $columns, string $indexName): void
{
    try {
        $table->unique($columns, $indexName);
    } catch (\Illuminate\Database\QueryException $e) {
        if (!str_contains($e->getMessage(), 'Duplicate') &&
            !str_contains($e->getMessage(), 'already exists')) {
            throw $e;
        }
        Log::info("Unique constraint {$indexName} already exists, skipping");
    }
}
```

**Acceptance Criteria:**
- [ ] All required columns exist on bank_transactions
- [ ] Fingerprint + external_id unique constraints added
- [ ] Performance indexes added
- [ ] Migration is idempotent (can run multiple times safely)
- [ ] Works on both MySQL and PostgreSQL

---

### Week 1-2: CSV Import Engine

#### P0-01: Bank CSV Parsers
**Priority:** P0 | **Estimate:** 2-3 days | **Status:** 🔴 TODO

Create bank-specific CSV parsers for Macedonian banks.

⚠️ **Use League CSV** - don't hand-roll CSV parsing. It handles encoding, BOM, edge cases.

**Dependency:** `composer require league/csv` (already in whitelist)

**Files to create:**
```
app/Services/Banking/Parsers/
├── BankParserInterface.php
├── AbstractCsvParser.php  # Base class using League CSV
├── NlbCsvParser.php
├── StopanskaСsvParser.php
├── KomercijalnaCsvParser.php
└── GenericCsvParser.php
```

**Base parser using League CSV:**
```php
use League\Csv\Reader;
use League\Csv\Statement;

abstract class AbstractCsvParser implements BankParserInterface
{
    public function parse(string $content): array
    {
        $csv = Reader::createFromString($content);
        $csv->setHeaderOffset($this->getHeaderOffset());

        // Handle encoding issues (common with Macedonian bank exports)
        if (!mb_check_encoding($content, 'UTF-8')) {
            $csv->addStreamFilter('convert.iconv.Windows-1251/UTF-8');
        }

        $records = Statement::create()->process($csv);
        $transactions = [];

        foreach ($records as $record) {
            $transactions[] = $this->mapRecord($record);
        }

        return $transactions;
    }

    abstract protected function getHeaderOffset(): int;
    abstract protected function mapRecord(array $record): array;
}
```

**Schema (already exists, verify):**
```php
// database/migrations/2025_07_25_163932_create_bank_transactions_table.php
Schema::create('bank_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->onDelete('cascade');
    $table->foreignId('bank_account_id')->nullable();
    $table->date('transaction_date');
    $table->string('reference')->nullable();
    $table->string('description')->nullable();
    $table->decimal('amount', 15, 2);
    $table->string('currency', 3)->default('MKD');
    $table->enum('type', ['credit', 'debit']);
    $table->string('counterparty_name')->nullable();
    $table->string('counterparty_account')->nullable();
    $table->string('source')->default('manual'); // manual, csv, psd2, email
    $table->json('raw_data')->nullable();
    $table->timestamps();
});
```

**Acceptance Criteria:**
- [ ] Parse NLB CSV format (semicolon-delimited, CP1250 encoding)
- [ ] Parse Stopanska CSV format (comma-delimited, UTF-8)
- [ ] Parse Komercijalna CSV format (tab-delimited)
- [ ] Handle encoding detection automatically
- [ ] 95%+ parse accuracy on 50 real statements

**Test file:** `tests/Feature/Banking/CsvParserTest.php`

---

#### P0-02: CSV Import UI
**Priority:** P0 | **Estimate:** 2 days | **Status:** 🔴 TODO

Create Vue component for bank statement upload.

**Files to create:**
```
resources/js/pages/reconciliation/
├── ImportStatementPage.vue
├── components/
│   ├── BankSelector.vue
│   ├── FileUploader.vue
│   ├── ImportPreview.vue
│   └── ImportProgress.vue
```

**API Endpoints:**
```php
// routes/api.php
Route::prefix('v1/banking')->group(function () {
    Route::post('/import/upload', [BankImportController::class, 'upload']);
    Route::post('/import/preview', [BankImportController::class, 'preview']);
    Route::post('/import/confirm', [BankImportController::class, 'confirm']);
    Route::get('/import/{id}/status', [BankImportController::class, 'status']);
});
```

**Acceptance Criteria:**
- [ ] Drag-drop file upload
- [ ] Bank auto-detection from file format
- [ ] Preview first 10 rows before import
- [ ] Progress indicator for large files
- [ ] Error handling with row-level feedback

---

#### P0-03: Import Logging & Analytics
**Priority:** P1 | **Estimate:** 1 day | **Status:** 🔴 TODO

Track import success/failure for KPI measurement.

**Migration:**
```php
// database/migrations/2026_02_05_000100_create_import_logs_table.php
Schema::create('import_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained();
    $table->foreignId('user_id')->constrained();
    $table->string('bank_code', 20);
    $table->string('file_name');
    $table->integer('total_rows');
    $table->integer('parsed_rows');
    $table->integer('failed_rows');
    $table->json('errors')->nullable();
    $table->integer('parse_time_ms');
    $table->timestamps();
});
```

**Metrics to track:**
- Parse accuracy per bank
- Common error types
- Processing time

---

### Week 2-3: Reconciliation Engine v1

#### P0-04: Matching Algorithm Core
**Priority:** P0 | **Estimate:** 3-4 days | **Status:** ✅ DONE (2026-02-05)

Build the reconciliation matching engine.

**Implemented in:**
```
modules/Mk/Services/Matcher.php          # Core matching service
modules/Mk/Http/Controllers/ReconciliationController.php  # API controller
tests/Feature/MatcherTest.php            # 23 passing tests
```

**Implemented Features:**
- 4-signal weighted confidence scoring:
  - Amount matching (40% weight): exact, tolerance, partial
  - Date proximity (20% weight): days from due date
  - Reference matching (30% weight): invoice number in description/remittance
  - Customer name matching (10% weight): fuzzy name comparison
- Auto-match threshold: 85%+ confidence
- Suggestion threshold: 60%+ confidence
- Auto-creates Payment record and updates Invoice status
- Batch matching for all unmatched transactions

**Acceptance Criteria:**
- [x] Match by exact amount (±1% tolerance for fees)
- [x] Match by invoice reference in bank description
- [x] Match by date proximity (invoice date vs transaction date)
- [x] Match by customer name (fuzzy matching)
- [x] Confidence score 0-100%
- [x] Auto-match threshold: 85%+ confidence
- [x] 23 passing tests covering all scenarios

---

#### P0-05: Reconciliation Database Schema
**Priority:** P0 | **Estimate:** 1 day | **Status:** 🔴 TODO

**Migration:**
```php
// database/migrations/2026_02_06_000100_create_reconciliations_table.php
Schema::create('reconciliations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained();
    $table->foreignId('bank_transaction_id')->constrained('bank_transactions');
    $table->foreignId('invoice_id')->nullable()->constrained();
    $table->foreignId('payment_id')->nullable()->constrained();
    $table->enum('status', ['pending', 'matched', 'partial', 'manual', 'ignored']);
    $table->enum('match_type', ['auto', 'manual', 'rule']);
    $table->decimal('confidence', 5, 2)->nullable();
    $table->json('match_details')->nullable();
    $table->foreignId('matched_by')->nullable()->constrained('users');
    $table->timestamp('matched_at')->nullable();
    $table->timestamps();

    $table->index(['company_id', 'status']);
    $table->index(['bank_transaction_id']);
});

// Track match feedback for ML improvement
Schema::create('reconciliation_feedback', function (Blueprint $table) {
    $table->id();
    $table->foreignId('reconciliation_id')->constrained();
    $table->enum('feedback', ['correct', 'wrong', 'partial']);
    $table->foreignId('correct_invoice_id')->nullable()->constrained('invoices');
    $table->foreignId('user_id')->constrained();
    $table->timestamps();
});
```

---

#### P0-06: Reconciliation UI
**Priority:** P0 | **Estimate:** 3 days | **Status:** ✅ DONE (2026-02-05) - Tested in Production

**Implemented in:**
```
resources/scripts/admin/views/banking/InvoiceReconciliation.vue
```

**Features:**
- Stats dashboard: total, matched, unmatched, match rate
- Unmatched transactions list with suggested matches
- Confidence badges (green >85%, yellow >70%, red <70%)
- Accept/reject match buttons
- Manual matching modal with invoice selection
- Auto-match button for bulk processing
- Link from BankingDashboard.vue

**UI Flow:**
```
┌─────────────────────────────────────────────────────────────────┐
│ Reconciliation Dashboard                           [Import CSV] │
├─────────────────────────────────────────────────────────────────┤
│ Filters: [All] [Pending] [Matched] [Manual]    Period: [Feb 26] │
├─────────────────────────────────────────────────────────────────┤
│ Transaction          │ Amount    │ Match        │ Action        │
│ ─────────────────────│───────────│──────────────│───────────────│
│ NLB 04/02 REF:123    │ +€500.00  │ INV-123 95%  │ [✓] [✗] [?]   │
│ NLB 03/02 TRANSFER   │ +€1,200   │ 2 matches    │ [Select]      │
│ NLB 02/02 FEE        │ -€5.00    │ No match     │ [Ignore] [+]  │
└─────────────────────────────────────────────────────────────────┘
│ Auto-matched: 45/60 (75%)  │  Pending: 12  │  Manual: 3        │
└─────────────────────────────────────────────────────────────────┘
```

**Acceptance Criteria:**
- [x] List unreconciled transactions
- [x] Show match suggestions with confidence %
- [x] One-click approve for high-confidence matches
- [x] Manual match modal with invoice search
- [x] Auto-match button for bulk processing
- [x] Production tested with test seeder (ReconciliationTestSeeder)
- [ ] Time-to-reconcile < 15 minutes for 100 transactions (needs real user testing)

---

#### P0-07: Reconciliation API
**Priority:** P0 | **Estimate:** 2 days | **Status:** ✅ DONE (2026-02-05)

**Implemented in:**
```
modules/Mk/Http/Controllers/ReconciliationController.php
routes/api.php (banking/reconciliation prefix)
```

**API Endpoints (implemented):**
```php
GET  /api/v1/banking/reconciliation           # List unmatched with suggestions
POST /api/v1/banking/reconciliation/auto-match # Run auto-matching
POST /api/v1/banking/reconciliation/manual-match # Manual match
GET  /api/v1/banking/reconciliation/stats     # Matching statistics
GET  /api/v1/banking/reconciliation/unpaid-invoices # For manual selection
```

---

### Week 3-4: Polish & Critical Infrastructure

#### P0-08: Email Bank Statement Parser
**Priority:** P2 (Optional) | **Estimate:** 2-3 days | **Status:** 🔴 DEFERRED

⚠️ **Scope control:** Only start after CSV import + reconciliation has 10+ paid users. Email parsing is a distraction until core flow works flawlessly.

Parse bank notification emails forwarded by users.

**Files to create:**
```
app/Services/Banking/Email/
├── EmailParserService.php
├── Parsers/
│   ├── NlbEmailParser.php
│   ├── StopanskaEmailParser.php
│   └── GenericEmailParser.php
└── Jobs/
    └── ProcessBankEmailJob.php
```

**Webhook endpoint for email forwarding service:**
```php
// routes/api.php
Route::post('/webhooks/email/bank-statement', [EmailWebhookController::class, 'bankStatement']);
```

**Acceptance Criteria:**
- [ ] Parse NLB daily statement emails
- [ ] Parse Stopanska transaction notification emails
- [ ] Extract: date, amount, reference, counterparty
- [ ] Auto-import to bank_transactions table
- [ ] Dedupe against existing transactions

---

#### P0-09: Matching Rules Engine
**Priority:** P1 | **Estimate:** 2 days | **Status:** 🔴 TODO

Let users create custom matching rules.

**Migration:**
```php
// database/migrations/2026_02_10_000100_create_matching_rules_table.php
Schema::create('matching_rules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained();
    $table->string('name');
    $table->json('conditions'); // [{field: 'description', operator: 'contains', value: 'SUBSCRIPTION'}]
    $table->json('actions');    // [{action: 'match_customer', customer_id: 123}]
    $table->integer('priority')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
```

**Example rule:**
```json
{
  "name": "Monthly hosting fee",
  "conditions": [
    {"field": "description", "operator": "contains", "value": "HETZNER"},
    {"field": "amount", "operator": "equals", "value": -49.00}
  ],
  "actions": [
    {"action": "categorize", "category": "hosting"},
    {"action": "match_expense", "expense_pattern": "Hetzner*"}
  ]
}
```

---

#### P0-10: Phase 0 Analytics Dashboard
**Priority:** P1 | **Estimate:** 1 day | **Status:** 🔴 TODO

Track KPIs for Phase 0 acceptance criteria.

**Metrics endpoint:**
```php
// GET /api/v1/reconciliation/analytics
{
  "period": "2026-02",
  "total_transactions": 500,
  "auto_matched": 375,
  "auto_match_rate": 0.75,
  "manual_matched": 100,
  "pending": 25,
  "avg_confidence": 0.82,
  "avg_time_to_reconcile_seconds": 420,
  "parse_accuracy": {
    "nlb": 0.97,
    "stopanska": 0.95,
    "komercijalna": 0.93
  }
}
```

---

### Critical Infrastructure (Week 2-4)

#### P0-15: Bank Accounts Normalization
**Priority:** P0 | **Estimate:** 1 day | **Status:** 🔴 TODO

⚠️ **CRITICAL:** Without this, PSD2 multi-account handling is impossible and dedupe is weak.

The `bank_transactions.bank_account_id` column exists but references no table. PSD2 consents can have multiple accounts per consent. Need normalized `bank_accounts` table.

**Migration:**
```php
// database/migrations/2026_02_05_000050_create_bank_accounts_table.php
Schema::create('bank_accounts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained()->onDelete('cascade');
    $table->foreignId('bank_consent_id')->nullable()->constrained()->onDelete('set null');
    $table->string('iban', 34)->nullable();
    $table->string('account_number', 50)->nullable();
    $table->string('bank_code', 20)->nullable(); // BIC/SWIFT or local code
    $table->string('bank_name', 100)->nullable();
    $table->string('currency', 3)->default('MKD');
    $table->string('account_type', 20)->default('checking'); // checking, savings, etc.
    $table->string('nickname', 100)->nullable(); // User-friendly name
    $table->boolean('is_primary')->default(false);
    $table->enum('status', ['active', 'inactive', 'disconnected'])->default('active');
    $table->string('external_id', 100)->nullable(); // PSD2 account resource ID
    $table->timestamp('last_synced_at')->nullable();
    $table->timestamps();

    // Indexes
    $table->unique(['company_id', 'iban'], 'bank_accounts_company_iban_unique');
    $table->unique(['company_id', 'external_id'], 'bank_accounts_company_external_unique');
    $table->index(['company_id', 'status']);
});

// Update bank_transactions to properly reference bank_accounts
Schema::table('bank_transactions', function (Blueprint $table) {
    // Add foreign key if not exists
    if (!Schema::hasColumn('bank_transactions', 'bank_account_id')) {
        $table->foreignId('bank_account_id')->nullable()->constrained();
    }
});
```

**Model:**
```php
class BankAccount extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'bank_consent_id', 'iban', 'account_number',
        'bank_code', 'bank_name', 'currency', 'account_type',
        'nickname', 'is_primary', 'status', 'external_id', 'last_synced_at',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    public function consent(): BelongsTo
    {
        return $this->belongsTo(BankConsent::class, 'bank_consent_id');
    }

    // Get masked IBAN for display (e.g., "MK07 **** **** 1234")
    public function getMaskedIbanAttribute(): string
    {
        if (!$this->iban) return 'No IBAN';
        return substr($this->iban, 0, 4) . ' **** **** ' . substr($this->iban, -4);
    }
}
```

**PSD2 Integration:**
```php
// When syncing accounts from PSD2 consent
class Psd2AccountSyncService
{
    public function syncAccountsFromConsent(BankConsent $consent, array $psd2Accounts): void
    {
        foreach ($psd2Accounts as $psd2Account) {
            BankAccount::updateOrCreate(
                [
                    'company_id' => $consent->company_id,
                    'external_id' => $psd2Account['resourceId'],
                ],
                [
                    'bank_consent_id' => $consent->id,
                    'iban' => $psd2Account['iban'] ?? null,
                    'account_number' => $psd2Account['bban'] ?? null,
                    'currency' => $psd2Account['currency'] ?? 'MKD',
                    'bank_name' => $consent->bank_name,
                    'account_type' => $psd2Account['cashAccountType'] ?? 'checking',
                    'status' => 'active',
                ]
            );
        }
    }
}
```

**Acceptance Criteria:**
- [ ] `bank_accounts` table exists with proper schema
- [ ] PSD2 consent → accounts are normalized into `bank_accounts`
- [ ] `bank_transactions.bank_account_id` references `bank_accounts.id`
- [ ] Account list UI shows user's bank accounts
- [ ] Per-account transaction cursor tracking for PSD2 sync
- [ ] CSV import can optionally link to existing bank account

**Test file:** `tests/Feature/Banking/BankAccountsTest.php`

---

#### P0-11: Transaction Fingerprinting + Dedupe
**Priority:** P0 | **Estimate:** 2 days | **Status:** 🔴 TODO

⚠️ **CRITICAL:** Without this, re-imports create duplicates and pilots will hate you.

Unified deduplication strategy across CSV, email, and PSD2 imports.

**Files to create:**
```
app/Services/Banking/
├── TransactionFingerprint.php
└── DeduplicationService.php
```

**Migration update:**
```php
// database/migrations/2026_02_05_000200_add_fingerprint_to_bank_transactions.php
Schema::table('bank_transactions', function (Blueprint $table) {
    // NOT NULL for imported transactions (csv/email/psd2)
    // Manual entries may have null fingerprint initially
    $table->string('fingerprint', 64)->nullable();

    // External transaction ID from bank (PSD2 often provides this)
    $table->string('external_transaction_id', 100)->nullable();

    // COMPOSITE unique - per company, not global!
    // This allows same fingerprint in different companies (correct)
    $table->unique(['company_id', 'fingerprint'], 'bank_tx_company_fingerprint_unique');

    // Also unique on external ID if provided
    $table->unique(['company_id', 'external_transaction_id'], 'bank_tx_company_external_unique');
});
```

**Fingerprint algorithm (robust):**
```php
class TransactionFingerprint
{
    /**
     * Generate a robust fingerprint for deduplication.
     *
     * Includes ALL fields that make a transaction unique:
     * - company_id (implicit in unique constraint)
     * - bank_account_id
     * - transaction_date
     * - amount (signed)
     * - currency
     * - type (credit/debit as backup for sign)
     * - reference (normalized)
     * - description (normalized, first 100 chars)
     * - counterparty_account (if available)
     * - external_transaction_id (if available - highest priority)
     */
    public function generate(array $tx): string
    {
        // If bank provides transaction ID, use it (most reliable)
        if (!empty($tx['external_transaction_id'])) {
            return hash('sha256', $tx['company_id'] . '|' . $tx['external_transaction_id']);
        }

        // Otherwise, build composite fingerprint
        $parts = [
            $tx['company_id'],
            $tx['bank_account_id'] ?? 'default',
            $tx['transaction_date'],
            $this->normalizeAmount($tx['amount']),
            strtoupper($tx['currency'] ?? 'MKD'),
            $tx['type'] ?? 'unknown', // credit/debit
            $this->normalizeText($tx['reference'] ?? ''),
            $this->normalizeText(substr($tx['description'] ?? '', 0, 100)),
            $this->normalizeText($tx['counterparty_account'] ?? ''),
        ];

        return hash('sha256', implode('|', $parts));
    }

    private function normalizeAmount($amount): string
    {
        // Use BCMath for precise decimal handling - NEVER use float for money!
        // Input may be string, int, or float - normalize to string first
        $amountStr = is_string($amount) ? $amount : (string) $amount;

        // Remove any thousand separators and normalize decimal point
        $amountStr = str_replace([',', ' '], ['', ''], $amountStr);

        // Use bcadd with scale=2 to normalize to 2 decimal places
        // bcadd('123.456', '0', 2) returns '123.45'
        return bcadd($amountStr, '0', 2);
    }

    private function normalizeText(string $text): string
    {
        // Lowercase, remove whitespace, strip punctuation, collapse diacritics
        $text = mb_strtolower($text);
        $text = preg_replace('/\s+/', '', $text);
        $text = preg_replace('/[^\p{L}\p{N}]/u', '', $text); // Keep only letters/numbers
        $text = $this->removeDiacritics($text);
        return $text;
    }

    private function removeDiacritics(string $text): string
    {
        // Convert accented chars to ASCII equivalents
        $text = \Normalizer::normalize($text, \Normalizer::FORM_D);
        return preg_replace('/[\x{0300}-\x{036f}]/u', '', $text);
    }
}

class DeduplicationService
{
    public function __construct(
        private TransactionFingerprint $fingerprint
    ) {}

    /**
     * Check if transaction already exists by fingerprint.
     *
     * NOTE: We use fingerprint ONLY, not OR with external_id.
     * Reason: OR queries are slow and the DB unique constraint on (company_id, fingerprint)
     * already guarantees dedupe. The external_id unique constraint is separate and
     * handled by the DB on insert (firstOrCreate will catch it).
     */
    public function isDuplicate(array $tx, int $companyId): bool
    {
        $fingerprint = $this->fingerprint->generate($tx);

        return BankTransaction::forCompany($companyId)
            ->where('fingerprint', $fingerprint)
            ->exists();
    }

    /**
     * Import transactions with deduplication.
     * Uses INSERT IGNORE / ON CONFLICT DO NOTHING for performance.
     *
     * @param string $source Import source: 'csv', 'email', 'psd2', 'manual'
     */
    public function importWithDedupe(array $transactions, int $companyId, string $source): ImportResult
    {
        $imported = 0;
        $skipped = 0;
        $errors = [];

        // APP-LEVEL VALIDATION: fingerprint is REQUIRED for automated imports
        // Manual entries may skip fingerprint, but csv/email/psd2 MUST have it
        $requiresFingerprint = in_array($source, ['csv', 'email', 'psd2'], true);

        foreach ($transactions as $tx) {
            $fingerprint = $this->fingerprint->generate($tx);

            // Validate fingerprint for automated imports
            if ($requiresFingerprint && empty($fingerprint)) {
                $errors[] = [
                    'row' => $tx,
                    'error' => "Fingerprint required for {$source} imports but generation failed"
                ];
                continue;
            }

            // Use firstOrCreate for atomic dedupe
            try {
                $result = BankTransaction::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'fingerprint' => $fingerprint,
                    ],
                    [
                        ...$tx,
                        'fingerprint' => $fingerprint,
                    ]
                );

                if ($result->wasRecentlyCreated) {
                    $imported++;
                } else {
                    $skipped++;
                }
            } catch (UniqueConstraintViolationException $e) {
                // Race condition - another process inserted first
                $skipped++;
            } catch (\Exception $e) {
                $errors[] = ['row' => $tx, 'error' => $e->getMessage()];
            }
        }

        return new ImportResult($imported, $skipped, $errors);
    }
}
```

**Acceptance Criteria:**
- [ ] Fingerprint is NOT NULL for csv/email/psd2 imports
- [ ] Composite unique on (company_id, fingerprint) - not global
- [ ] Same transaction imported twice → skipped (not duplicated)
- [ ] CSV re-upload of same file → only new transactions imported
- [ ] PSD2 sync overlap → no duplicates (uses external_transaction_id if available)
- [ ] Recurring payments (same amount/date monthly) don't collide (description differs)
- [ ] Fingerprint collision rate < 0.001% on test data
- [ ] Performance: 10K transactions dedupe < 5 seconds

**Test file:** `tests/Unit/Services/Banking/DeduplicationServiceTest.php`

---

#### P0-12: Reconciliation Posting Service
**Priority:** P0 | **Estimate:** 2-3 days | **Status:** 🔴 TODO

⚠️ **CRITICAL:** Reconciliation must safely create Payment records and post to ledger.

When a bank transaction is matched to an invoice, create the accounting entries idempotently.

**Files to create:**
```
app/Services/Reconciliation/
├── ReconciliationPostingService.php
├── PostingResult.php
└── Events/
    ├── ReconciliationMatched.php
    └── ReconciliationPosted.php
```

**Migration - Add DB-level idempotency to payments:**
```php
// database/migrations/2026_02_06_000200_add_idempotency_to_payments.php
Schema::table('payments', function (Blueprint $table) {
    // Idempotency key: what created this payment?
    $table->string('source_type', 50)->nullable(); // 'bank_transaction', 'payment_link', 'manual'
    $table->unsignedBigInteger('source_id')->nullable(); // ID of source record

    // DB-level idempotency: one payment per source per company
    $table->unique(['company_id', 'source_type', 'source_id'], 'payments_idempotency_unique');
});
```

**Service (with proper idempotency + amount handling):**
```php
class ReconciliationPostingService extends BaseCompanyService
{
    /**
     * Post a matched reconciliation to Payment + GL (IDEMPOTENT)
     *
     * Uses DB-level unique constraint for true idempotency,
     * not just in-memory checks which fail on retries.
     */
    public function post(Reconciliation $recon): PostingResult
    {
        $tx = $recon->bankTransaction;

        // VALIDATION: Only credit transactions can pay invoices
        if ($tx->type !== 'credit' || $tx->amount < 0) {
            return PostingResult::error('Cannot create payment from debit transaction');
        }

        // VALIDATION: Currency must match invoice (or handle FX explicitly)
        // NOTE: Check InvoiceShelf schema for actual field name - may be 'currency' or 'currency_code'
        $invoiceCurrency = $recon->invoice?->currency ?? $recon->invoice?->currency_code;
        if ($recon->invoice && $tx->currency !== $invoiceCurrency) {
            return PostingResult::error(
                "Currency mismatch: transaction {$tx->currency} vs invoice {$invoiceCurrency}"
            );
        }

        return DB::transaction(function () use ($recon, $tx) {
            // Lock the reconciliation row to prevent concurrent updates
            $recon = Reconciliation::lockForUpdate()->find($recon->id);

            // DB-LEVEL IDEMPOTENCY: firstOrCreate with unique constraint
            // If payment already exists for this source, return it
            $payment = Payment::firstOrCreate(
                [
                    'company_id' => $recon->company_id,
                    'source_type' => 'bank_transaction',
                    'source_id' => $tx->id,
                ],
                [
                    'invoice_id' => $recon->invoice_id,
                    'payment_method_id' => $this->getBankPaymentMethod($recon->company_id),
                    'amount' => abs($tx->amount), // ALWAYS positive for payments
                    'payment_date' => $tx->transaction_date,
                    'reference_number' => $tx->reference,
                    'notes' => "Auto-posted from bank reconciliation #{$recon->id}",
                ]
            );

            // If payment already existed, this is a replay - return early
            if (!$payment->wasRecentlyCreated) {
                return PostingResult::alreadyPosted($payment);
            }

            // Update invoice paid status
            if ($recon->invoice) {
                $recon->invoice->updatePaidStatus();
            }

            // Post to GL (if IFRS module enabled)
            if (config('facturino.ifrs_enabled')) {
                $this->postToLedger($payment, $recon);
            }

            // Link reconciliation to payment
            $recon->update([
                'payment_id' => $payment->id,
                'status' => 'matched',
                'matched_at' => now(),
            ]);

            event(new ReconciliationPosted($recon, $payment));

            return PostingResult::success($payment);
        });
    }

    /**
     * Handle split payments (one transaction pays multiple invoices)
     *
     * Creates reconciliation_splits records for traceability.
     */
    public function postSplit(
        BankTransaction $tx,
        Collection $invoices,
        array $allocations // [{invoice_id: 1, amount: 400}, {invoice_id: 2, amount: 600}]
    ): PostingResult {
        // VALIDATION
        if ($tx->type !== 'credit' || $tx->amount < 0) {
            return PostingResult::error('Cannot split debit transaction');
        }

        $totalAllocated = collect($allocations)->sum('amount');
        if (abs($totalAllocated - abs($tx->amount)) > 0.01) {
            return PostingResult::error(
                "Allocation total ({$totalAllocated}) doesn't match transaction amount ({$tx->amount})"
            );
        }

        return DB::transaction(function () use ($tx, $invoices, $allocations) {
            // Lock transaction row
            $tx = BankTransaction::lockForUpdate()->find($tx->id);

            // Create parent reconciliation for the transaction
            $parentRecon = Reconciliation::firstOrCreate(
                [
                    'company_id' => $tx->company_id,
                    'bank_transaction_id' => $tx->id,
                ],
                [
                    'status' => 'matched',
                    'match_type' => 'split',
                    'matched_at' => now(),
                ]
            );

            $payments = [];
            foreach ($allocations as $allocation) {
                $invoice = $invoices->find($allocation['invoice_id']);

                // Create split record
                $split = ReconciliationSplit::firstOrCreate(
                    [
                        'reconciliation_id' => $parentRecon->id,
                        'invoice_id' => $invoice->id,
                    ],
                    [
                        'allocated_amount' => $allocation['amount'],
                    ]
                );

                // Create payment (idempotent via source_type + source_id)
                $payment = Payment::firstOrCreate(
                    [
                        'company_id' => $tx->company_id,
                        'source_type' => 'reconciliation_split',
                        'source_id' => $split->id,
                    ],
                    [
                        'invoice_id' => $invoice->id,
                        'payment_method_id' => $this->getBankPaymentMethod($tx->company_id),
                        'amount' => abs($allocation['amount']),
                        'payment_date' => $tx->transaction_date,
                        'reference_number' => $tx->reference,
                        'notes' => "Split payment from transaction #{$tx->id}",
                    ]
                );

                // Update split with payment link
                $split->update(['payment_id' => $payment->id]);

                // Update invoice status
                $invoice->updatePaidStatus();
                $payments[] = $payment;
            }

            return PostingResult::success($payments);
        });
    }

    /**
     * Handle partial payment (transaction < invoice total)
     */
    public function postPartial(Reconciliation $recon): PostingResult
    {
        // Same as post() but invoice remains 'partially_paid'
        // The invoice->updatePaidStatus() handles this automatically
        return $this->post($recon);
    }

    private function getBankPaymentMethod(int $companyId): int
    {
        return PaymentMethod::firstOrCreate(
            ['company_id' => $companyId, 'name' => 'Bank Transfer'],
            ['type' => 'bank_transfer']
        )->id;
    }
}
```

**Acceptance Criteria:**
- [ ] Match → Payment created automatically with abs(amount)
- [ ] Only CREDIT transactions can create payments (validation)
- [ ] Currency validation: tx currency must match invoice
- [ ] DB-LEVEL IDEMPOTENCY: unique(company_id, source_type, source_id)
- [ ] Calling post() twice returns existing payment (no duplicate)
- [ ] Split: creates reconciliation_splits + multiple payments
- [ ] Split: allocations must sum to transaction amount
- [ ] Partial: partial payment leaves invoice in partial state
- [ ] Row-level locking prevents concurrent match conflicts
- [ ] GL entries created if IFRS module enabled

**Test file:** `tests/Feature/Reconciliation/PostingServiceTest.php`

---

#### P0-13: Tenant Scoping Audit
**Priority:** P0 | **Estimate:** 1 day | **Status:** 🔴 TODO

⚠️ **SECURITY:** Every query in banking/reconciliation MUST be scoped by company_id.

⚠️ **WARNING:** Do NOT use runtime `Model::addGlobalScope()` in middleware - it leaks across requests in queue workers and causes hard-to-debug issues.

**Correct approach - Explicit query scopes:**

```php
// 1. Model trait for company scoping
// app/Traits/BelongsToCompany.php
trait BelongsToCompany
{
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where($this->getTable() . '.company_id', $companyId);
    }

    // Boot method adds scope ONLY if defined at model level (not runtime)
    protected static function bootBelongsToCompany()
    {
        // Optional: auto-set company_id on create
        static::creating(function ($model) {
            if (!$model->company_id && auth()->check()) {
                $model->company_id = auth()->user()->company_id;
            }
        });
    }
}

// 2. Use explicit scoping in ALL queries
// ❌ BAD - no tenant scope
BankTransaction::where('id', $id)->first();

// ❌ BAD - runtime global scope (dangerous in queues)
BankTransaction::addGlobalScope('tenant', fn($q) => $q->where('company_id', $id));

// ✅ GOOD - explicit scope
BankTransaction::forCompany($companyId)->where('id', $id)->firstOrFail();

// ✅ GOOD - route model binding with company validation
// routes/api.php
Route::get('/transactions/{transaction}', function (BankTransaction $transaction) {
    // Validate ownership in controller/middleware
    abort_unless($transaction->company_id === auth()->user()->company_id, 403);
    return $transaction;
});
```

**3. Base service class pattern:**
```php
// app/Services/BaseCompanyService.php
abstract class BaseCompanyService
{
    protected int $companyId;

    public function __construct()
    {
        $this->companyId = auth()->user()->company_id
            ?? throw new \RuntimeException('No authenticated company');
    }

    protected function scopedQuery(string $model)
    {
        return $model::forCompany($this->companyId);
    }
}

// Usage in ReconciliationService
class ReconciliationService extends BaseCompanyService
{
    public function getPendingTransactions(): Collection
    {
        return $this->scopedQuery(BankTransaction::class)
            ->whereNull('reconciliation_id')
            ->get();
    }
}
```

**4. Middleware validates but doesn't mutate:**
```php
// app/Http/Middleware/ValidateTenantAccess.php
class ValidateTenantAccess
{
    public function handle($request, Closure $next)
    {
        // Validate user has company_id
        if (!$request->user()?->company_id) {
            return response()->json(['error' => 'No company context'], 403);
        }

        // Store in request for easy access (NOT as global scope)
        $request->attributes->set('company_id', $request->user()->company_id);

        return $next($request);
    }
}
```

**Files to audit:**
- [ ] `app/Services/Banking/Parsers/*.php` - use `forCompany()` scope
- [ ] `app/Services/Banking/DeduplicationService.php` - use `forCompany()` scope
- [ ] `app/Services/Reconciliation/*.php` - extend `BaseCompanyService`
- [ ] `app/Http/Controllers/Api/V1/BankingController.php` - validate ownership
- [ ] `app/Http/Controllers/Api/V1/ReconciliationController.php` - validate ownership

**Acceptance Criteria:**
- [ ] All banking/reconciliation queries use explicit `forCompany()` scope
- [ ] No runtime global scope mutations in middleware
- [ ] Route model binding validates company ownership
- [ ] Queue jobs receive company_id explicitly (not from auth)
- [ ] Unit test: cross-tenant access returns 404/403
- [ ] Code review checklist includes tenant scope check

**Test file:** `tests/Feature/Security/TenantIsolationTest.php`

---

#### P0-14: Partial Payments + Multi-Invoice Settlement
**Priority:** P1 | **Estimate:** 2 days | **Status:** 🔴 TODO

Handle complex real-world reconciliation scenarios.

**Scenarios to support:**

1. **One transaction → Multiple invoices** (split payment)
   - Customer pays €1,000 covering INV-001 (€400) + INV-002 (€600)

2. **Multiple transactions → One invoice** (installment)
   - Invoice for €3,000 paid via 3 × €1,000 transactions

3. **Transaction with fees** (amount mismatch)
   - Invoice €100, transaction €98 (€2 bank fee)
   - Need tolerance rules or explicit fee handling

4. **Chargebacks/reversals**
   - Negative transaction reversing previous payment

**Migration:**
```php
// database/migrations/2026_02_08_000100_create_reconciliation_splits_table.php
Schema::create('reconciliation_splits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('reconciliation_id')->constrained();
    $table->foreignId('invoice_id')->constrained();
    $table->decimal('allocated_amount', 15, 2);
    // Link to the payment created for this split allocation
    // Nullable because payment is created AFTER split is recorded
    $table->foreignId('payment_id')->nullable()->constrained();
    $table->timestamps();

    // Index for finding all splits for a reconciliation
    $table->index(['reconciliation_id']);
});
```

**UI Requirements:**
- [ ] "Split" button when transaction > single invoice
- [ ] Multi-select invoices for split allocation
- [ ] Show running total vs transaction amount
- [ ] Warning when amounts don't balance

**Acceptance Criteria:**
- [ ] Split payment across 2+ invoices
- [ ] Multiple payments to single invoice (installments)
- [ ] Fee tolerance: auto-match if within 2% of invoice
- [ ] UI shows "partial" badge on partially paid invoices

---

### Phase 0 Definition of Done

| Deliverable | Acceptance Criteria | Test |
|-------------|---------------------|------|
| CSV import | 95% parse on 50 real statements | `CsvParserTest` |
| Reconciliation v1 | 60% median auto-match | `ReconciliationTest` |
| Dedupe working | Re-import same file → no duplicates | `DeduplicationServiceTest` |
| Posting service | Match → Payment created idempotently | `PostingServiceTest` |
| Tenant isolation | Cross-tenant queries blocked | `TenantIsolationTest` |
| UI complete | Import → Match → Approve flow | E2E Cypress |
| Time-to-reconcile | <15 min for 100 txns | Manual test |
| Analytics | Dashboard shows all metrics | Feature test |

---

## Phase 1: Foundation + PSD2 (Q1-Q2 2026)

**Goal:** PSD2 as upgrade to CSV reconciliation
**North Star:** 1+ bank live with 97%+ sync success

### P1-01: PSD2 Partner Integration
**Priority:** P0 | **Estimate:** 2-3 weeks | **Status:** 🟡 Sandbox ready

Integrate with AISP partner for bank connectivity.

**Existing code to extend:**
```
app/Services/Banking/Psd2Client.php  ← Extend this
```

**New files:**
```
app/Services/Banking/Psd2/
├── Psd2ServiceInterface.php
├── Providers/
│   ├── NlbPsd2Provider.php
│   ├── StopanskaPsd2Provider.php
│   └── PartnerAispProvider.php  ← For sponsor model
├── Models/
│   ├── BankConsent.php
│   ├── AccountInfo.php
│   └── TransactionBatch.php
└── Jobs/
    ├── RefreshBankTokenJob.php
    ├── SyncTransactionsJob.php
    └── HandleConsentExpiryJob.php
```

**Migration:**
```php
// database/migrations/2026_02_15_000100_create_bank_consents_table.php
Schema::create('bank_consents', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained();
    $table->foreignId('user_id')->constrained();
    $table->string('bank_code', 20);
    $table->string('consent_id');
    $table->string('access_token', 2048)->nullable();
    $table->string('refresh_token', 2048)->nullable();
    $table->timestamp('token_expires_at')->nullable();
    $table->timestamp('consent_expires_at');
    $table->enum('status', ['pending', 'active', 'expired', 'revoked']);
    $table->json('accounts')->nullable();
    $table->json('scope')->nullable();
    $table->timestamps();

    $table->index(['company_id', 'bank_code', 'status']);
});
```

**Acceptance Criteria:**
- [ ] OAuth flow for NLB bank
- [ ] Account list endpoint working
- [ ] Transaction sync endpoint working
- [ ] Token refresh handling
- [ ] Consent expiry notifications
- [ ] Platform uptime 99.9%, bank sync 97%+ daily

---

#### P1-02: PSD2 Transaction Sync Scheduler
**Priority:** P0 | **Estimate:** 3-4 days | **Status:** 🔴 TODO

Background job to sync transactions from connected banks with **per-bank rate limit policies**.

⚠️ **IMPORTANT:** Banks have different rate limits. Some allow 4 calls/day, others allow real-time. A global 15-minute schedule will get you blocked.

**Migration - Bank sync policies:**
```php
// database/migrations/2026_02_15_000200_create_bank_sync_policies_table.php
Schema::create('bank_sync_policies', function (Blueprint $table) {
    $table->id();
    $table->string('bank_code', 20)->unique();
    $table->integer('max_calls_per_day')->default(96); // 4/hour = 96/day
    $table->integer('min_sync_interval_minutes')->default(15);
    $table->integer('backoff_minutes')->default(60); // On 429 error
    $table->boolean('supports_realtime')->default(false);
    $table->json('rate_limit_headers')->nullable(); // Which headers to parse
    $table->timestamps();
});

// Seed default policies
DB::table('bank_sync_policies')->insert([
    ['bank_code' => 'nlb_mk', 'max_calls_per_day' => 96, 'min_sync_interval_minutes' => 15],
    ['bank_code' => 'stopanska_mk', 'max_calls_per_day' => 4, 'min_sync_interval_minutes' => 360], // 4/day = every 6 hours
    ['bank_code' => 'komercijalna_mk', 'max_calls_per_day' => 24, 'min_sync_interval_minutes' => 60],
]);

// Add tracking to bank_consents
Schema::table('bank_consents', function (Blueprint $table) {
    $table->timestamp('last_synced_at')->nullable();
    $table->timestamp('next_sync_allowed_at')->nullable();
    $table->integer('consecutive_failures')->default(0);
    $table->timestamp('backoff_until')->nullable();
});
```

**Scheduler (policy-aware):**
```php
// app/Console/Kernel.php
// Run dispatcher every 5 minutes, but it respects per-bank policies
$schedule->job(new DispatchBankSyncJob)
    ->everyFiveMinutes()
    ->withoutOverlapping();
```

**Job logic (respects rate limits):**
```php
class DispatchBankSyncJob implements ShouldQueue
{
    public function handle(BankSyncPolicyService $policyService)
    {
        BankConsent::active()
            ->where('token_expires_at', '>', now())
            ->where(function ($q) {
                // Not in backoff
                $q->whereNull('backoff_until')
                  ->orWhere('backoff_until', '<', now());
            })
            ->where(function ($q) {
                // Sync allowed based on policy
                $q->whereNull('next_sync_allowed_at')
                  ->orWhere('next_sync_allowed_at', '<', now());
            })
            ->each(function ($consent) use ($policyService) {
                // Check if we can sync this bank right now
                if ($policyService->canSync($consent)) {
                    SyncTransactionsJob::dispatch($consent)
                        ->onQueue('bank-sync');
                }
            });
    }
}

class BankSyncPolicyService
{
    public function canSync(BankConsent $consent): bool
    {
        $policy = BankSyncPolicy::where('bank_code', $consent->bank_code)->first();

        if (!$policy) {
            // Default: allow every 15 minutes
            return $consent->last_synced_at === null
                || $consent->last_synced_at->diffInMinutes(now()) >= 15;
        }

        // Check daily call limit
        $callsToday = BankSyncLog::where('bank_consent_id', $consent->id)
            ->whereDate('created_at', today())
            ->count();

        if ($callsToday >= $policy->max_calls_per_day) {
            return false;
        }

        // Check minimum interval
        if ($consent->last_synced_at) {
            $minutesSinceLastSync = $consent->last_synced_at->diffInMinutes(now());
            if ($minutesSinceLastSync < $policy->min_sync_interval_minutes) {
                return false;
            }
        }

        return true;
    }

    public function recordSyncAttempt(BankConsent $consent, SyncResult $result): void
    {
        $policy = BankSyncPolicy::where('bank_code', $consent->bank_code)->first();

        if ($result->isRateLimited()) {
            // Got 429 - apply backoff
            $backoffMinutes = $policy?->backoff_minutes ?? 60;
            $consent->update([
                'backoff_until' => now()->addMinutes($backoffMinutes),
                'consecutive_failures' => $consent->consecutive_failures + 1,
            ]);
        } elseif ($result->isSuccess()) {
            // Reset failures, set next allowed sync time
            $nextSync = now()->addMinutes($policy?->min_sync_interval_minutes ?? 15);
            $consent->update([
                'last_synced_at' => now(),
                'next_sync_allowed_at' => $nextSync,
                'consecutive_failures' => 0,
                'backoff_until' => null,
            ]);
        } else {
            // Other failure - increment counter but don't backoff immediately
            $consent->increment('consecutive_failures');

            // After 3 consecutive failures, apply backoff
            if ($consent->consecutive_failures >= 3) {
                $consent->update([
                    'backoff_until' => now()->addMinutes(30),
                ]);
            }
        }
    }
}
```

**SyncTransactionsJob with rate limit handling:**
```php
class SyncTransactionsJob implements ShouldQueue
{
    use InteractsWithQueue;

    public int $tries = 1; // Don't retry automatically - policy service handles it
    public int $timeout = 60;

    public function handle(Psd2Client $client, BankSyncPolicyService $policyService): void
    {
        try {
            $result = $client->syncTransactions($this->consent);
            $policyService->recordSyncAttempt($this->consent, $result);
        } catch (RateLimitException $e) {
            $policyService->recordSyncAttempt(
                $this->consent,
                SyncResult::rateLimited($e->getRetryAfter())
            );
        } catch (\Exception $e) {
            $policyService->recordSyncAttempt(
                $this->consent,
                SyncResult::failed($e->getMessage())
            );
        }
    }
}
```

**Acceptance Criteria:**
- [ ] Per-bank sync policies configurable (max calls/day, min interval)
- [ ] Respects 429 responses with exponential backoff
- [ ] Never exceeds bank's daily call limit
- [ ] Consecutive failures trigger automatic backoff
- [ ] Sync status visible in admin dashboard
- [ ] Data freshness depends on bank policy (not fixed 5 min)
- [ ] Manual "Sync Now" button respects rate limits (shows error if blocked)

---

#### P1-03: Bank Connection UI
**Priority:** P0 | **Estimate:** 2-3 days | **Status:** 🔴 TODO

**Files to create:**
```
resources/js/pages/banking/
├── ConnectBankPage.vue
├── components/
│   ├── BankList.vue
│   ├── ConsentFlow.vue
│   ├── ConnectedAccounts.vue
│   └── SyncStatus.vue
```

**Flow:**
```
[Select Bank] → [OAuth Redirect] → [Consent] → [Account Selection] → [Connected!]
```

---

#### P1-04: CASYS Payment Gateway Integration
**Priority:** P0 | **Estimate:** 1 week | **Status:** 🔴 TODO

Integrate CASYS for payment link generation.

**Files to create:**
```
app/Services/Payments/
├── PaymentGatewayInterface.php
├── Gateways/
│   ├── CasysGateway.php
│   └── ManualGateway.php
├── Models/
│   └── PaymentLink.php
└── Jobs/
    └── ProcessCasysWebhookJob.php
```

**Migration:**
```php
// database/migrations/2026_02_20_000100_create_payment_links_table.php
Schema::create('payment_links', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained();
    $table->foreignId('invoice_id')->nullable()->constrained();
    $table->string('gateway', 50); // casys, stripe, etc.
    $table->string('external_id')->nullable();
    $table->string('url', 500);
    $table->decimal('amount', 15, 2);
    $table->string('currency', 3);
    $table->enum('status', ['pending', 'paid', 'expired', 'failed']);
    $table->timestamp('expires_at')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->json('gateway_response')->nullable();
    // Webhook handler updates these fields - must exist in migration!
    $table->string('gateway_transaction_id', 100)->nullable(); // TransactionID from webhook
    $table->string('gateway_error_code', 50)->nullable(); // ErrorCode from failed webhook
    $table->timestamps();

    $table->index(['company_id', 'status']);
    $table->index(['external_id']);
    $table->index(['gateway_transaction_id']); // For reconciliation lookups
});
```

**CASYS Integration:**
```php
class CasysGateway implements PaymentGatewayInterface
{
    public function createPaymentLink(Invoice $invoice): PaymentLink
    {
        $uniqueId = $this->generateUniqueId();

        $response = Http::post(config('services.casys.url'), [
            'MerchantId' => config('services.casys.merchant_id'),
            'UniqueID' => $uniqueId,
            'Amount' => $invoice->total,
            'Currency' => 'MKD',
            'OrderDetails' => "Invoice {$invoice->invoice_number}",
            'SuccessURL' => route('payments.success'),
            'FailURL' => route('payments.fail'),
            'CallbackURL' => route('webhooks.casys'),
        ]);

        return PaymentLink::create([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
            'gateway' => 'casys',
            'external_id' => $uniqueId,
            'url' => $response['PaymentURL'],
            'amount' => $invoice->total,
            'currency' => 'MKD',
            'expires_at' => now()->addMinutes(20), // CASYS 20-min expiry
        ]);
    }
}
```

**Webhook handler:**
```php
// routes/api.php
Route::post('/webhooks/casys', [CasysWebhookController::class, 'handle'])
    ->withoutMiddleware(['csrf']);
```

**Acceptance Criteria:**
- [ ] Generate payment links for invoices
- [ ] Handle CASYS webhook callbacks
- [ ] Auto-reconcile paid invoices
- [ ] 95%+ payment success rate
- [ ] <3s redirect time

---

#### P1-05: QES E-Invoice Signing
**Priority:** P1 | **Estimate:** 1 week | **Status:** 🔴 TODO

Qualified Electronic Signature for invoices.

**Files to create:**
```
app/Services/EInvoice/
├── EInvoiceService.php
├── Signers/
│   ├── SignerInterface.php
│   └── XmlSecSigner.php
├── Generators/
│   └── UblGenerator.php
└── Models/
    └── SignedInvoice.php
```

**Dependencies:**
```bash
composer require robrichards/xmlseclibs
composer require num-num/ubl-invoice
```

---

#### P1-06: API v1 Foundation
**Priority:** P1 | **Estimate:** 3-4 days | **Status:** 🔴 TODO

Stable public API with versioning.

**Structure:**
```
app/Http/Controllers/Api/
├── V1/
│   ├── InvoiceController.php
│   ├── PaymentController.php
│   ├── CustomerController.php
│   ├── BankingController.php
│   └── ReconciliationController.php
└── ApiController.php (base)
```

**Versioning middleware:**
```php
// app/Http/Middleware/ApiVersion.php
class ApiVersion
{
    public function handle($request, Closure $next, $version)
    {
        $request->merge(['api_version' => $version]);
        return $next($request);
    }
}
```

**Routes:**
```php
// routes/api.php
Route::prefix('v1')->middleware(['api', 'api.version:1'])->group(function () {
    Route::apiResource('invoices', V1\InvoiceController::class);
    Route::apiResource('payments', V1\PaymentController::class);
    Route::apiResource('customers', V1\CustomerController::class);
    // ...
});
```

---

#### P1-07: Webhook System
**Priority:** P1 | **Estimate:** 2-3 days | **Status:** 🔴 TODO

Reliable webhook delivery for events.

**Dependencies:**
```bash
composer require spatie/laravel-webhook-server
```

**Migration:**
```php
Schema::create('webhook_endpoints', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained();
    $table->string('url', 500);
    $table->string('secret', 64);
    $table->json('events'); // ['invoice.created', 'payment.received', etc.]
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

Schema::create('webhook_deliveries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('webhook_endpoint_id')->constrained();
    $table->string('event');
    $table->json('payload');
    $table->integer('response_code')->nullable();
    $table->text('response_body')->nullable();
    $table->integer('attempts')->default(0);
    $table->timestamp('delivered_at')->nullable();
    $table->timestamps();
});
```

---

### Critical Infrastructure (Phase 1)

#### P1-08: Token Encryption + Secrets Management
**Priority:** P0 | **Estimate:** 3-4 days | **Status:** 🔴 TODO

⚠️ **SECURITY:** Bank tokens (access_token, refresh_token) MUST be encrypted at rest.

**Files to create:**
```
app/Services/Security/
├── TokenEncryptionService.php
├── SecretsManager.php
└── KeyRotationService.php
```

**Migration update:**
```php
// bank_consents table - tokens must be encrypted
// Change from string to text for encrypted values
$table->text('access_token_encrypted')->nullable();
$table->text('refresh_token_encrypted')->nullable();
$table->dropColumn(['access_token', 'refresh_token']); // Remove plaintext
```

**Encryption service (CORRECT Laravel API):**

⚠️ **NOTE:** Laravel's `Crypt::encryptString()` does NOT accept a custom key parameter. It always uses APP_KEY. For custom keys, use a dedicated encrypter instance.

```php
class TokenEncryptionService
{
    private Encrypter $encrypter;

    public function __construct()
    {
        // PHASE 1: Use Laravel's built-in encryption with APP_KEY
        // This is secure and simple - tokens encrypted with AES-256-CBC
        $this->encrypter = app('encrypter');

        // PHASE 2+: Use dedicated key for bank tokens (from Vault/KMS)
        // Uncomment when ready for key separation:
        // $key = config('services.vault.bank_token_key')
        //     ?? throw new \RuntimeException('Bank token encryption key not configured');
        // $this->encrypter = new Encrypter(base64_decode($key), 'AES-256-CBC');
    }

    public function encrypt(string $token): string
    {
        // Laravel's encryptString() - single argument, uses configured key
        return $this->encrypter->encryptString($token);
    }

    public function decrypt(string $encrypted): string
    {
        return $this->encrypter->decryptString($encrypted);
    }
}

// Model with encrypted attributes using Laravel's built-in casting
class BankConsent extends Model
{
    // Option 1: Use Laravel's encrypted cast (simplest, uses APP_KEY)
    protected $casts = [
        'access_token_encrypted' => 'encrypted',
        'refresh_token_encrypted' => 'encrypted',
    ];

    // Option 2: Custom accessor/mutator with dedicated service
    // Use this if you need audit logging or custom key management
    public function getAccessTokenAttribute(): ?string
    {
        if (!$this->attributes['access_token_encrypted']) {
            return null;
        }

        $token = app(TokenEncryptionService::class)->decrypt(
            $this->attributes['access_token_encrypted']
        );

        // Audit log (optional)
        Log::channel('security')->info('Bank token decrypted', [
            'consent_id' => $this->id,
            'user_id' => auth()->id(),
            'action' => 'decrypt',
        ]);

        return $token;
    }

    public function setAccessTokenAttribute(?string $value): void
    {
        $this->attributes['access_token_encrypted'] = $value
            ? app(TokenEncryptionService::class)->encrypt($value)
            : null;
    }

    // IMPORTANT: Never log tokens
    protected $hidden = [
        'access_token_encrypted',
        'refresh_token_encrypted',
    ];
}
```

**Secrets management options:**
- **Phase 1:** Laravel's built-in `encrypted` cast (uses APP_KEY, simplest)
- **Phase 1 alt:** Custom `Encrypter` with same APP_KEY but audit logging
- **Phase 2+:** HashiCorp Vault or AWS KMS with dedicated key rotation

**Key rotation strategy (Phase 2+):**
```php
// When rotating keys:
// 1. Add new key as BANK_TOKEN_KEY_NEW in env
// 2. Re-encrypt all tokens: old_key decrypt -> new_key encrypt
// 3. Switch BANK_TOKEN_KEY to new key
// 4. Remove old key after validation period
```

**Acceptance Criteria:**
- [ ] Bank tokens encrypted at rest in database
- [ ] Tokens decrypted only when needed (not logged)
- [ ] Key rotation possible without re-auth
- [ ] Audit log for token access (who, when, why)
- [ ] No plaintext tokens in logs or error messages

**Test file:** `tests/Unit/Services/Security/TokenEncryptionTest.php`

---

#### P1-09: Consent Lifecycle UX
**Priority:** P0 | **Estimate:** 2-3 days | **Status:** 🔴 TODO

Handle real-world bank connection failures gracefully.

**States to handle:**
```
┌─────────────────────────────────────────────────────────────────┐
│                    CONSENT LIFECYCLE                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  [Not Connected] → [Connecting] → [Active] → [Expiring Soon]   │
│        │                │             │            │            │
│        │                │             │            ▼            │
│        │                │             │      [Expired]          │
│        │                │             │            │            │
│        │                ▼             ▼            ▼            │
│        │         [Auth Failed]  [Revoked]   [Reconnect]        │
│        │                │             │            │            │
│        └────────────────┴─────────────┴────────────┘            │
│                         All → [Not Connected]                    │
└─────────────────────────────────────────────────────────────────┘
```

**Files to create:**
```
resources/js/pages/banking/components/
├── ConsentStatus.vue
├── ReconnectBanner.vue
├── ExpiryWarning.vue
└── AuthErrorModal.vue
```

**Notifications:**
```php
// app/Notifications/BankConsentExpiring.php
class BankConsentExpiring extends Notification
{
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Bank connection expiring soon')
            ->line("Your {$this->consent->bank_name} connection expires in {$this->daysLeft} days.")
            ->action('Reconnect Now', route('banking.reconnect', $this->consent));
    }
}

// Schedule: check expiring consents daily
$schedule->job(new CheckExpiringConsentsJob)->daily();
```

**UI Requirements:**
- [ ] Banner: "Bank connection expiring in X days - Reconnect"
- [ ] Banner: "Bank connection failed - Reconnect required"
- [ ] Modal: Explain why reconnect needed + button
- [ ] Email: 7 days before expiry, 1 day before expiry
- [ ] Disable sync gracefully when expired (don't error)

**Acceptance Criteria:**
- [ ] User notified 7 days before consent expiry
- [ ] Clear "Reconnect" UI when expired/failed
- [ ] Token refresh failure triggers reconnect state
- [ ] Bank downtime shows friendly message (not error)
- [ ] Graceful degradation: CSV import still works

---

#### P1-10: Bank Sync Observability + Alerts
**Priority:** P0 | **Estimate:** 2 days | **Status:** 🔴 TODO

⚠️ **OPERATIONAL:** You need to know when bank sync breaks before users complain.

**Files to create:**
```
app/Services/Monitoring/
├── BankSyncMonitor.php
├── AlertService.php
└── MetricsCollector.php
```

**Migration:**
```php
// database/migrations/2026_02_18_000100_create_bank_sync_logs_table.php
Schema::create('bank_sync_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('bank_consent_id')->constrained();
    $table->string('correlation_id', 36); // UUID for tracing
    $table->enum('status', ['started', 'success', 'partial', 'failed']);
    $table->integer('transactions_fetched')->default(0);
    $table->integer('transactions_imported')->default(0);
    $table->integer('duplicates_skipped')->default(0);
    $table->integer('duration_ms');
    $table->text('error_message')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();

    $table->index(['bank_consent_id', 'created_at']);
    $table->index(['status', 'created_at']);
});
```

**Metrics to track:**
```php
class BankSyncMonitor
{
    public function recordSync(BankConsent $consent, SyncResult $result): void
    {
        BankSyncLog::create([
            'bank_consent_id' => $consent->id,
            'correlation_id' => request()->header('X-Correlation-ID') ?? Str::uuid(),
            'status' => $result->status,
            'transactions_fetched' => $result->fetched,
            'transactions_imported' => $result->imported,
            'duplicates_skipped' => $result->skipped,
            'duration_ms' => $result->durationMs,
            'error_message' => $result->error,
        ]);

        // Check alert thresholds
        $this->checkAlerts($consent, $result);
    }

    private function checkAlerts(BankConsent $consent, SyncResult $result): void
    {
        // Alert if sync failed
        if ($result->status === 'failed') {
            $this->alertService->sendAlert(
                "Bank sync failed for {$consent->bank_code}",
                AlertLevel::ERROR
            );
        }

        // Alert if sync lag > 1 hour
        if ($consent->last_synced_at < now()->subHour()) {
            $this->alertService->sendAlert(
                "Bank sync lag > 1 hour for {$consent->bank_code}",
                AlertLevel::WARNING
            );
        }
    }
}
```

**Alert channels:**
```php
// config/monitoring.php
return [
    'alerts' => [
        'channels' => ['slack', 'email'],
        'slack_webhook' => env('MONITORING_SLACK_WEBHOOK'),
        'email' => env('MONITORING_EMAIL', 'ops@facturino.mk'),
    ],
    'thresholds' => [
        'sync_lag_minutes' => 60,
        'error_rate_percent' => 5,
        'consecutive_failures' => 3,
    ],
];
```

**Dashboard (Horizon + custom):**
- Failed jobs count
- Sync success rate (24h rolling)
- Average sync latency
- Consents expiring soon

**Acceptance Criteria:**
- [ ] All sync jobs logged with correlation ID
- [ ] Slack/email alert on sync failure
- [ ] Alert on sync lag > 1 hour
- [ ] Dashboard showing sync health
- [ ] Structured logs for debugging (JSON format)

---

#### P1-11: API Product Boundaries + OpenAPI Spec
**Priority:** P1 | **Estimate:** 2-3 days | **Status:** 🔴 TODO

Define API auth, rate limits, and generate OpenAPI spec.

**API Auth scheme:**
```php
// Using Laravel Sanctum with API tokens
// Token format: fct_live_xxxx or fct_test_xxxx

// app/Http/Middleware/ApiKeyAuth.php
class ApiKeyAuth
{
    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token || !Str::startsWith($token, ['fct_live_', 'fct_test_'])) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        $apiKey = PersonalAccessToken::findToken($token);
        if (!$apiKey) {
            return response()->json(['error' => 'Invalid API key'], 401);
        }

        // Set authenticated user and company
        $request->setUserResolver(fn() => $apiKey->tokenable);

        return $next($request);
    }
}
```

**Rate limiting:**
```php
// app/Providers/RouteServiceProvider.php
RateLimiter::for('api', function (Request $request) {
    $key = $request->user()?->id ?: $request->ip();

    return [
        Limit::perMinute(60)->by($key),      // 60 req/min standard
        Limit::perMinute(10)->by($key . ':heavy'), // 10 req/min for heavy ops
    ];
});

// Heavy operations: bank sync, bulk imports
Route::post('/banking/sync', ...)->middleware('throttle:heavy');
```

**OpenAPI spec:**
```yaml
# openapi/facturino-v1.yaml
openapi: 3.0.0
info:
  title: Facturino API
  version: 1.0.0
  description: Payment infrastructure API for Southeast Europe

servers:
  - url: https://api.facturino.mk/v1
    description: Production
  - url: https://sandbox.facturino.mk/v1
    description: Sandbox

security:
  - bearerAuth: []

paths:
  /invoices:
    get:
      summary: List invoices
      # ...
  /payments:
    post:
      summary: Create payment
      # ...
  /banking/accounts:
    get:
      summary: List connected bank accounts
      # ...
```

**Webhook signature verification:**
```php
// Outgoing webhooks - sign with HMAC
class WebhookSigner
{
    public function sign(array $payload, string $secret): string
    {
        $body = json_encode($payload);
        return hash_hmac('sha256', $body, $secret);
    }
}

// Headers sent:
// X-Facturino-Signature: sha256=xxxxx
// X-Facturino-Timestamp: 1234567890
```

**Acceptance Criteria:**
- [ ] API key auth working (fct_live_*, fct_test_*)
- [ ] Rate limiting: 60/min standard, 10/min heavy
- [ ] OpenAPI spec generated and validated
- [ ] Webhook signatures documented
- [ ] API versioning header (Accept: application/vnd.facturino.v1+json)

---

#### P1-12: CASYS Webhook Hardening
**Priority:** P0 | **Estimate:** 2 days | **Status:** 🔴 TODO

⚠️ **PAYMENT CRITICAL:** Webhooks must be idempotent and secure.

**Consider:** `spatie/laravel-webhook-client` provides job-based processing, signature verification framework, and retry handling. However, CASYS doesn't provide signatures, so our custom field whitelist + DB idempotency approach is appropriate.

**Files to update:**
```
app/Http/Controllers/Webhooks/CasysWebhookController.php
app/Services/Payments/Gateways/CasysGateway.php
```

**Migration - DB-level idempotency (NOT cache):**
```php
// database/migrations/2026_02_20_000300_create_webhook_events_table.php
Schema::create('webhook_events', function (Blueprint $table) {
    $table->id();
    $table->string('provider', 50); // 'casys', 'stripe', etc.
    $table->string('event_id', 100); // UniqueID from webhook
    $table->string('event_type', 50); // 'payment.success', 'payment.failed'
    $table->enum('status', ['received', 'processing', 'processed', 'failed']);
    $table->text('error_message')->nullable();
    $table->integer('attempts')->default(1);
    $table->timestamp('processed_at')->nullable();
    $table->timestamps();

    // DB-level idempotency - one event per provider+event_id
    $table->unique(['provider', 'event_id'], 'webhook_events_idempotency');
});
```

**Secure webhook processing (NO PII logging, DB idempotency):**
```php
class CasysWebhookController
{
    // Explicit whitelist of allowed CASYS webhook fields
    private const ALLOWED_FIELDS = [
        'UniqueID', 'Status', 'Amount', 'Currency',
        'TransactionID', 'ErrorCode', 'Timestamp',
        // Add other CASYS-documented fields here
    ];

    public function handle(Request $request)
    {
        // 1. REJECT UNEXPECTED FIELDS (Laravel validate() doesn't do this!)
        // This prevents payload injection attacks
        $unexpectedFields = array_diff(array_keys($request->all()), self::ALLOWED_FIELDS);
        if (!empty($unexpectedFields)) {
            Log::warning('CASYS webhook unexpected fields', [
                'fields' => $unexpectedFields,
                // Don't log values - may contain PII
            ]);
            return response('Unexpected fields in payload', 400);
        }

        // 2. Validate required fields
        $validated = $request->validate([
            'UniqueID' => 'required|string|max:100',
            'Status' => 'required|string|in:SUCCESS,FAILED,PENDING',
            'Amount' => 'required|numeric',
            'Currency' => 'required|string|size:3',
            'TransactionID' => 'nullable|string|max:100',
            'ErrorCode' => 'nullable|string|max:50',
            'Timestamp' => 'nullable|date',
        ]);

        $uniqueId = $validated['UniqueID'];

        // 2. DB-LEVEL IDEMPOTENCY (not cache - cache can be cleared)
        try {
            $webhookEvent = WebhookEvent::create([
                'provider' => 'casys',
                'event_id' => $uniqueId,
                'event_type' => 'payment.' . strtolower($validated['Status']),
                'status' => 'processing',
            ]);
        } catch (UniqueConstraintViolationException $e) {
            // Already processed - return success to stop retries
            Log::info('CASYS webhook duplicate', [
                'unique_id' => $uniqueId,
                // ⚠️ NO payload logging - may contain PII
            ]);
            return response('OK', 200);
        }

        // 3. Log safely (NO full payload, NO PII)
        Log::info('CASYS webhook received', [
            'unique_id' => $uniqueId,
            'status' => $validated['Status'],
            'amount' => $validated['Amount'],
            // ⚠️ NEVER log: card numbers, customer names, addresses
        ]);

        // 4. Process payment
        try {
            DB::transaction(function () use ($validated, $uniqueId, $webhookEvent) {
                $paymentLink = PaymentLink::where('external_id', $uniqueId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($validated['Status'] === 'SUCCESS') {
                    // Store only necessary fields, not full payload
                    $paymentLink->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                        'gateway_transaction_id' => $validated['TransactionID'] ?? null,
                    ]);

                    // Auto-reconcile: create payment for linked invoice
                    if ($paymentLink->invoice_id) {
                        $this->createPaymentFromLink($paymentLink);
                    }
                } else {
                    $paymentLink->update([
                        'status' => 'failed',
                        'gateway_error_code' => $validated['ErrorCode'] ?? null,
                    ]);
                }

                $webhookEvent->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                ]);
            });

            return response('OK', 200);

        } catch (\Exception $e) {
            $webhookEvent->update([
                'status' => 'failed',
                'error_message' => Str::limit($e->getMessage(), 500),
                'attempts' => $webhookEvent->attempts + 1,
            ]);

            Log::error('CASYS webhook processing failed', [
                'unique_id' => $uniqueId,
                'error' => $e->getMessage(),
                // ⚠️ NO stack trace with payload data
            ]);

            // Return 500 so CASYS will retry
            return response('Processing failed', 500);
        }
    }

    /**
     * Create payment from successful payment link (idempotent)
     */
    private function createPaymentFromLink(PaymentLink $paymentLink): void
    {
        // Idempotent: check if payment already exists for this source
        Payment::firstOrCreate(
            [
                'company_id' => $paymentLink->company_id,
                'source_type' => 'payment_link',
                'source_id' => $paymentLink->id,
            ],
            [
                'invoice_id' => $paymentLink->invoice_id,
                'amount' => $paymentLink->amount,
                'payment_date' => now(),
                'payment_method_id' => $this->getCasysPaymentMethod($paymentLink->company_id),
                'notes' => "Paid via CASYS payment link",
            ]
        );

        // Update invoice status
        if ($paymentLink->invoice) {
            $paymentLink->invoice->updatePaidStatus();
        }
    }
}
```

**Security notes:**
- ⚠️ **NO full payload logging** - CASYS payloads may contain card details, customer PII
- ⚠️ **NO IP whitelist reliance** - IPs can change; use schema validation instead
- ⚠️ **DB idempotency, not cache** - cache can be cleared, causing duplicate payments
- ⚠️ **Explicit field whitelist** - Laravel validate() does NOT reject extra fields; use array_diff() check

**Replay protection (if CASYS provides timestamp):**
```php
// Only if CASYS sends a reliable timestamp
if ($validated['Timestamp'] ?? null) {
    $eventTime = Carbon::parse($validated['Timestamp']);
    if ($eventTime->lt(now()->subMinutes(10))) {
        Log::warning('CASYS webhook too old', ['unique_id' => $uniqueId]);
        return response('Event too old', 400);
}
```

**Acceptance Criteria:**
- [ ] Explicit field whitelist: reject payloads with unexpected keys (array_diff check)
- [ ] Required field validation: UniqueID, Status, Amount, Currency
- [ ] DB-level idempotency: unique(provider, event_id) prevents duplicates
- [ ] Duplicate webhooks don't create duplicate payments (return 200 OK)
- [ ] Failed webhooks logged for debugging (NO PII in logs)
- [ ] Replay attacks mitigated via timestamp check (if CASYS provides timestamp)
- [ ] Auto-reconcile: payment link paid → invoice marked paid
- [ ] Row-level locking on PaymentLink during processing

⚠️ **Note:** CASYS does NOT provide webhook signatures. Security relies on:
1. Explicit field whitelist (ALLOWED_FIELDS constant + array_diff check)
2. DB idempotency (prevent replay via unique constraint)
3. UniqueID lookup (validates payment link exists in our DB)
4. IP whitelist is optional but CASYS IPs can change - don't rely solely on this

---

#### P1-13: Metered Billing for Bank Connections
**Priority:** P1 | **Estimate:** 2 days | **Status:** 🔴 TODO

Implement +€15/bank/mo pricing from investor roadmap.

**Migration:**
```php
// database/migrations/2026_02_20_000200_create_billing_meters_table.php
Schema::create('billing_meters', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained();
    $table->string('meter_type'); // 'connected_banks', 'api_calls', 'sync_volume'
    $table->integer('quantity');
    $table->date('period_start');
    $table->date('period_end');
    $table->decimal('unit_price', 10, 2);
    $table->decimal('total_amount', 10, 2);
    $table->boolean('invoiced')->default(false);
    $table->timestamps();

    $table->index(['company_id', 'meter_type', 'period_start']);
});
```

**Metering service:**
```php
class BillingMeterService
{
    public function recordConnectedBanks(Company $company): void
    {
        $connectedBanks = BankConsent::where('company_id', $company->id)
            ->where('status', 'active')
            ->count();

        BillingMeter::updateOrCreate(
            [
                'company_id' => $company->id,
                'meter_type' => 'connected_banks',
                'period_start' => now()->startOfMonth(),
            ],
            [
                'quantity' => $connectedBanks,
                'period_end' => now()->endOfMonth(),
                'unit_price' => 15.00, // €15/bank/mo
                'total_amount' => $connectedBanks * 15.00,
            ]
        );
    }
}

// Scheduled job: update meters daily
$schedule->job(new UpdateBillingMetersJob)->daily();
```

**Acceptance Criteria:**
- [ ] Track connected bank count per company
- [ ] Calculate monthly bank connection fees
- [ ] Integrate with Paddle subscription (add-on)
- [ ] Show bank connection cost in billing UI

---

#### P1-14: Queue + Job Orchestration Hardening
**Priority:** P0 | **Estimate:** 2 days | **Status:** 🔴 TODO

⚠️ **RELIABILITY:** Jobs fail silently without proper idempotency, retries, and dead-letter handling.

**Critical jobs requiring hardening:**
1. Bank sync jobs (PSD2 transaction fetch)
2. Webhook handlers (CASYS + future gateways)
3. Reconciliation batch matching

**Files to create:**
```
app/Jobs/
├── Concerns/
│   └── IdempotentJob.php  # Trait for idempotent job handling
├── Banking/
│   └── SyncBankTransactionsJob.php
├── Webhooks/
│   └── ProcessCasysWebhookJob.php
└── Reconciliation/
    └── BatchMatchJob.php
```

**Idempotent job trait:**
```php
trait IdempotentJob
{
    public string $idempotencyKey;

    public function ensureIdempotent(): bool
    {
        // Use DB-level lock with unique constraint
        try {
            JobExecution::create([
                'job_class' => static::class,
                'idempotency_key' => $this->idempotencyKey,
                'status' => 'processing',
            ]);
            return true;
        } catch (UniqueConstraintViolationException $e) {
            Log::info('Job already processed', ['key' => $this->idempotencyKey]);
            return false; // Skip - already processed
        }
    }

    public function markCompleted(): void
    {
        JobExecution::where('idempotency_key', $this->idempotencyKey)
            ->update(['status' => 'completed', 'completed_at' => now()]);
    }
}
```

**Dead-letter queue strategy:**
```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'retry_after' => 90,
        'block_for' => 5,
    ],
],

// For failed jobs
'failed' => [
    'driver' => 'database-uuids',
    'database' => 'mysql',
    'table' => 'failed_jobs',
],
```

**Job retry configuration:**
```php
class SyncBankTransactionsJob implements ShouldQueue
{
    use IdempotentJob;

    public int $tries = 3;
    public int $backoff = 60; // 1 min, then 2 min, then 4 min
    public int $maxExceptions = 2;
    public int $timeout = 300; // 5 min max

    public function backoff(): array
    {
        return [60, 120, 300]; // 1 min, 2 min, 5 min
    }

    public function failed(Throwable $exception): void
    {
        // Alert on permanent failure
        AlertService::bankSyncFailed($this->consentId, $exception);
    }
}
```

**Acceptance Criteria:**
- [ ] All bank sync jobs have idempotency keys (consent_id + date)
- [ ] Webhook jobs have idempotency keys (event_id)
- [ ] Retry with exponential backoff (1m → 2m → 5m)
- [ ] Dead-letter queue captures permanently failed jobs
- [ ] Failed job alerts fire to Slack/email
- [ ] Job execution table tracks status for debugging
- [ ] Horizon dashboard shows job health metrics

**Test file:** `tests/Unit/Jobs/IdempotentJobTest.php`

---

#### P1-15: Audit Logging for Compliance
**Priority:** P1 | **Estimate:** 1 day | **Status:** 🔴 TODO

Use `spatie/laravel-activitylog` for compliance-ready audit trail.

**Install:**
```bash
composer require spatie/laravel-activitylog
php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"
php artisan migrate
```

**Events to log:**
```php
// 1. Bank consent actions
activity('bank_consent')
    ->performedOn($consent)
    ->withProperties(['bank' => $consent->bank_name, 'action' => 'connected'])
    ->log('Bank account connected');

// 2. Reconciliation approvals
activity('reconciliation')
    ->performedOn($reconciliation)
    ->withProperties([
        'bank_transaction_id' => $tx->id,
        'invoice_id' => $invoice->id,
        'amount' => $tx->amount,
    ])
    ->log('Reconciliation approved');

// 3. Payment actions
activity('payment')
    ->performedOn($payment)
    ->withProperties(['amount' => $payment->amount, 'method' => 'casys'])
    ->log('Payment created from payment link');

// 4. Admin actions
activity('admin')
    ->causedBy(auth()->user())
    ->withProperties(['target_user' => $user->email])
    ->log('User account suspended');
```

**Retention policy:**
```php
// Clean old logs (keep 7 years for compliance)
$schedule->command('activitylog:clean --days=2555')->daily();
```

**Acceptance Criteria:**
- [ ] Bank consent connect/disconnect logged
- [ ] Reconciliation approve/reject logged
- [ ] Payment create/refund logged
- [ ] Admin impersonation logged
- [ ] Logs retained 7 years (configurable)
- [ ] Audit log viewer in admin panel

---

### Phase 1 Definition of Done (System Readiness)

| Deliverable | Acceptance Criteria | System KPI |
|-------------|---------------------|------------|
| PSD2 1+ bank | OAuth + tx sync working | 97%+ sync success rate |
| CASYS live | Payment links generating | 95% payment success |
| Token encryption | Bank tokens encrypted at rest | Security audit pass |
| Consent lifecycle | Reconnect UX working | <1% consent failures |
| Observability | Alerts firing on failures | <5min detection time |
| API v1 | Stable endpoints + OpenAPI | <500ms p95 latency |
| Webhooks hardened | Idempotent + DB-level dedupe | 0 duplicate payments |
| **Scalability** | Supports 500 active companies | No degradation |
| **Import throughput** | 10K transactions import | <30 seconds |
| **Queue capacity** | Bank sync jobs | >100 jobs/min |
| **Reconciliation** | Auto-match processing | <5s per 100 txns |

*Note: Customer/revenue metrics (500 customers, €15K MRR) are in PAYMENT_INFRASTRUCTURE_ROADMAP.md, not codebase DoD.*

---

## Phase 2: Payment Rails (Q3-Q4 2026)

**Goal:** Payment API v1, Serbia IPS integration
**North Star:** Multi-PSP routing with 99% aggregate success rate

*Business targets (€30K MRR, 1,000 customers) tracked in PAYMENT_INFRASTRUCTURE_ROADMAP.md*

### P2-01: Payment Orchestration Layer
**Priority:** P0 | **Estimate:** 2-3 weeks | **Status:** 🔴 TODO

Build multi-PSP routing capability.

**Files to create:**
```
app/Services/Payments/
├── PaymentOrchestrator.php
├── Router/
│   ├── PaymentRouter.php
│   ├── Rules/
│   │   ├── CurrencyRule.php
│   │   ├── AmountRule.php
│   │   └── CountryRule.php
│   └── Strategies/
│       ├── LowestFeeStrategy.php
│       └── HighestSuccessStrategy.php
├── Gateways/
│   ├── CasysGateway.php (existing)
│   ├── IpsGateway.php (Serbia)
│   └── BankTransferGateway.php
└── Models/
    └── PaymentAttempt.php
```

**Orchestrator logic:**
```php
class PaymentOrchestrator
{
    public function processPayment(PaymentRequest $request): PaymentResult
    {
        $gateway = $this->router->selectGateway($request);

        $attempt = PaymentAttempt::create([
            'request_id' => $request->id,
            'gateway' => $gateway->getName(),
            'status' => 'processing',
        ]);

        try {
            $result = $gateway->process($request);
            $attempt->markSuccessful($result);
            return $result;
        } catch (PaymentException $e) {
            $attempt->markFailed($e);

            // Try fallback gateway
            if ($fallback = $this->router->selectFallback($request, $gateway)) {
                return $this->processWithGateway($request, $fallback);
            }

            throw $e;
        }
    }
}
```

---

#### P2-02: Serbia IPS Integration
**Priority:** P0 | **Estimate:** 2 weeks | **Status:** 🔴 TODO

Integrate Serbian Instant Payment System.

**Files:**
```
app/Services/Payments/Gateways/Serbia/
├── IpsGateway.php
├── IpsClient.php
└── IpsWebhookHandler.php
```

---

#### P2-03: Payment API Endpoints
**Priority:** P0 | **Estimate:** 1 week | **Status:** 🔴 TODO

**Endpoints:**
```php
Route::prefix('v1/payments')->group(function () {
    Route::post('/create', [PaymentApiController::class, 'create']);
    Route::get('/{id}', [PaymentApiController::class, 'show']);
    Route::post('/{id}/capture', [PaymentApiController::class, 'capture']);
    Route::post('/{id}/refund', [PaymentApiController::class, 'refund']);
    Route::post('/link', [PaymentApiController::class, 'createLink']);
});
```

**Request/Response:**
```php
// POST /v1/payments/create
{
    "amount": 5000,
    "currency": "MKD",
    "customer_id": "cus_123",
    "description": "Invoice INV-001",
    "metadata": {"invoice_id": "inv_456"}
}

// Response
{
    "id": "pay_789",
    "status": "pending",
    "amount": 5000,
    "currency": "MKD",
    "payment_url": "https://pay.facturino.mk/pay_789",
    "created_at": "2026-06-15T10:30:00Z"
}
```

---

### Phase 2 Definition of Done (System Readiness)

| Deliverable | Acceptance Criteria | System KPI |
|-------------|---------------------|------------|
| Serbia IPS integration | IPS payment rails working | 97%+ success rate |
| Multi-PSP routing | CASYS + IPS failover working | 99% aggregate success |
| Payment API v1 | All endpoints live + documented | <500ms p95 latency |
| Payment orchestration | Retry logic + routing rules | <0.1% failed payments lost |
| **Scalability** | Handle 10K payments/day | No degradation |
| **Reliability** | PSP health monitoring | <5min detection time |
| **Reconciliation** | Cross-border payments reconcile | 95%+ auto-match rate |

*Note: Customer/revenue metrics (1,000 customers, €30K MRR) are tracked in PAYMENT_INFRASTRUCTURE_ROADMAP.md, not codebase DoD.*

---

## Phase 3: Open Banking Platform (Q1-Q2 2027)

### P3-01: Unified Bank API
**Priority:** P0 | **Estimate:** 4-6 weeks | **Status:** 🔴 TODO

Single API across all connected banks.

**API Design:**
```php
// GET /v1/bank/accounts
{
    "accounts": [
        {
            "id": "acc_123",
            "bank": "nlb",
            "iban": "MK07***1234",
            "name": "Business Account",
            "balance": {"available": 50000, "currency": "MKD"},
            "last_synced": "2027-01-15T10:00:00Z"
        }
    ]
}

// GET /v1/bank/accounts/{id}/transactions
{
    "transactions": [
        {
            "id": "tx_456",
            "date": "2027-01-15",
            "amount": -500,
            "currency": "MKD",
            "description": "HETZNER HOSTING",
            "counterparty": {"name": "Hetzner GmbH", "iban": "DE89***"},
            "category": "hosting",
            "reconciliation_status": "matched"
        }
    ],
    "pagination": {"next_cursor": "..."}
}
```

---

#### P3-02: Bank Adapter Framework
**Priority:** P0 | **Estimate:** 2-3 weeks | **Status:** 🔴 TODO

Pluggable adapter system for new banks.

**Structure:**
```
app/Services/Banking/Adapters/
├── BankAdapterInterface.php
├── BaseBankAdapter.php
├── Macedonia/
│   ├── NlbAdapter.php
│   ├── StopanskaAdapter.php
│   └── KomercijalnaAdapter.php
├── Serbia/
│   ├── OtpAdapter.php
│   ├── RaiffeisenAdapter.php
│   └── UniCreditAdapter.php
└── Kosovo/
    ├── TebAdapter.php
    └── ProCreditAdapter.php
```

**Interface:**
```php
interface BankAdapterInterface
{
    public function getAuthUrl(Company $company): string;
    public function exchangeCode(string $code): BankConsent;
    public function refreshToken(BankConsent $consent): BankConsent;
    public function getAccounts(BankConsent $consent): Collection;
    public function getTransactions(BankConsent $consent, string $accountId, Carbon $from, Carbon $to): Collection;
}

// PISP is SEPARATE interface - not all adapters need to implement
// Only implement when bank supports PISP AND we have PISP license/partner
interface PispCapableAdapterInterface extends BankAdapterInterface
{
    public function initiatePayment(BankConsent $consent, PaymentRequest $request): PaymentInitiation;
    public function getPaymentStatus(string $paymentId): PaymentStatus;
}
```

---

### Phase 3 Definition of Done

| Deliverable | Acceptance Criteria | KPI |
|-------------|---------------------|-----|
| 8 banks connected | MK(3) + RS(3) + XK/AL(2) | 99.5% uptime |
| Unified Bank API | Single API across banks | <1s account fetch |
| Kosovo + Albania | Legal entity + 1 bank each | 25 customers each |
| First API customer | External fintech using API | €2K MRR API |

---

## Phase 4: E-Invoicing & Compliance (Q2-Q3 2027)

### P4-01: Multi-Country E-Invoice Support
**Priority:** P0 | **Estimate:** 6-8 weeks | **Status:** 🔴 TODO

**Structure:**
```
app/Services/EInvoice/
├── EInvoiceServiceFactory.php
├── Countries/
│   ├── Macedonia/
│   │   └── MkEInvoiceService.php
│   ├── Serbia/
│   │   └── SefService.php (Serbian e-Faktura)
│   ├── Slovenia/
│   │   └── ESlogService.php
│   └── Croatia/
│       └── FiskalService.php
└── Standards/
    ├── Ubl21Generator.php
    ├── ESlog2Generator.php
    └── PeppolGenerator.php
```

**Dependencies:**
```bash
composer require media24si/eslog2  # Slovenian e-SLOG
```

---

#### P4-02: Digital Archive Service
**Priority:** P1 | **Estimate:** 2 weeks | **Status:** 🔴 TODO

10-year compliant document storage.

**Migration:**
```php
Schema::create('document_archives', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained();
    $table->morphs('archivable'); // invoice, payment, etc.
    $table->string('storage_path');
    $table->string('hash_sha256', 64);
    $table->timestamp('archived_at');
    $table->timestamp('retention_until');
    $table->timestamps();

    $table->index(['hash_sha256']);
});
```

---

### Phase 4 Definition of Done

| Deliverable | Acceptance Criteria | KPI |
|-------------|---------------------|-----|
| 4 country e-invoice | MK, Serbia, Slovenia, Croatia | 100% tax acceptance |
| Digital signature | Multi-country QES | <2s signing |
| Document archive | 10-year compliant | 99.99% durability |
| SOC2 Type I | Audit completed | Certificate |

---

## Phase 5: Developer Platform (Q3-Q4 2027)

### P5-01: Developer Portal
**Priority:** P0 | **Estimate:** 4-6 weeks | **Status:** 🔴 TODO

**Stack:** Docusaurus or custom Vue app

**Structure:**
```
docs/
├── getting-started/
│   ├── quickstart.md
│   ├── authentication.md
│   └── webhooks.md
├── api-reference/
│   ├── invoices.md
│   ├── payments.md
│   ├── banking.md
│   └── reconciliation.md
├── sdks/
│   ├── php.md
│   ├── nodejs.md
│   └── python.md
└── guides/
    ├── payment-links.md
    ├── bank-sync.md
    └── e-invoicing.md
```

---

#### P5-02: SDK Development
**Priority:** P1 | **Estimate:** 2-3 weeks per SDK | **Status:** 🔴 TODO

**PHP SDK:**
```php
// Usage example
$facturino = new Facturino\Client('api_key_xxx');

$invoice = $facturino->invoices->create([
    'customer_id' => 'cus_123',
    'items' => [
        ['description' => 'Consulting', 'amount' => 500],
    ],
]);

$paymentLink = $facturino->payments->createLink($invoice->id);
```

**Repositories:**
- `facturino/facturino-php`
- `facturino/facturino-node`
- `facturino/facturino-python`

---

#### P5-03: Sandbox Environment
**Priority:** P0 | **Estimate:** 2 weeks | **Status:** 🔴 TODO

Full test environment with mock data.

**Config:**
```php
// config/facturino.php
return [
    'sandbox' => [
        'enabled' => env('FACTURINO_SANDBOX', false),
        'mock_banks' => true,
        'mock_payments' => true,
        'test_card' => '4111111111111111',
    ],
];
```

---

### Phase 5 Definition of Done

| Deliverable | Acceptance Criteria | KPI |
|-------------|---------------------|-----|
| Developer portal | docs.facturino.com live | <5min to first call |
| SDKs released | PHP, Node.js, Python | >100 GitHub stars |
| Sandbox | Full test mode | 99.9% uptime |
| 20 API customers | External devs/fintechs | €50K MRR from API |

---

## Phase 6: Expansion & Scale (2028)

### P6-01: Multi-Region Deployment
**Priority:** P0 | **Status:** 🔴 TODO

**Infrastructure:**
```
┌─────────────────────────────────────────────────────────┐
│                    LOAD BALANCER                         │
│              (Cloudflare / AWS ALB)                      │
└─────────────────────────────────────────────────────────┘
                          │
         ┌────────────────┼────────────────┐
         │                │                │
    ┌────▼────┐     ┌────▼────┐     ┌────▼────┐
    │ EU-WEST │     │EU-CENTRAL│    │ EU-EAST │
    │ (Ireland)│    │(Frankfurt)│   │ (Warsaw) │
    └─────────┘     └──────────┘    └─────────┘
```

---

#### P6-02: Horizontal Scaling
**Priority:** P0 | **Status:** 🔴 TODO

- Kubernetes deployment
- Database read replicas
- Redis cluster
- Queue workers auto-scaling

---

### Phase 6 Definition of Done

| Deliverable | Acceptance Criteria | KPI |
|-------------|---------------------|-----|
| 25+ banks | Production integrations | 99.9% uptime |
| 8 countries | Revenue in each | €50K+ MRR per country |
| €5M ARR | Verified | 15% MoM growth |
| Series A ready | Metrics, team, position | Term sheets |

---

## Testing Strategy

### Test Coverage Requirements

| Phase | Unit Tests | Feature Tests | E2E Tests |
|-------|------------|---------------|-----------|
| P0 | 80%+ | All API endpoints | Import → Reconcile flow |
| P1 | 80%+ | All API endpoints | Bank connect → Sync flow |
| P2 | 80%+ | All API endpoints | Payment flow |
| P3+ | 80%+ | All API endpoints | Critical paths |

### Test File Structure
```
tests/
├── Unit/
│   ├── Services/
│   │   ├── Banking/
│   │   │   ├── CsvParserTest.php
│   │   │   └── Psd2ClientTest.php
│   │   ├── Reconciliation/
│   │   │   ├── MatcherTest.php
│   │   │   └── ReconciliationServiceTest.php
│   │   └── Payments/
│   │       └── PaymentOrchestratorTest.php
│   └── Models/
├── Feature/
│   ├── Api/
│   │   ├── InvoiceApiTest.php
│   │   ├── PaymentApiTest.php
│   │   └── ReconciliationApiTest.php
│   └── Banking/
│       ├── CsvImportTest.php
│       └── Psd2SyncTest.php
└── E2E/
    └── cypress/
        ├── reconciliation.cy.js
        └── bank-connect.cy.js
```

---

## Immediate Next Steps (Week 1)

### Day 1-2
- [ ] **P0-01:** Create bank CSV parser structure
- [ ] **P0-05:** Create reconciliation migration

### Day 3-4
- [ ] **P0-01:** Implement NLB CSV parser
- [ ] **P0-02:** Create ImportStatementPage.vue

### Day 5
- [ ] **P0-04:** Start matching algorithm
- [ ] Write tests for parsers

### Week 1 Exit Criteria
- [ ] CSV import working for 1 bank
- [ ] Basic reconciliation UI visible
- [ ] 10+ transactions imported in dev

---

---

## Critical Infrastructure Summary

These tickets are **MUST COMPLETE** before shipping to pilots:

### Phase 0 Critical (Must ship in Week 1-4)

| Ticket | Description | Why Critical |
|--------|-------------|--------------|
| **P0-DEP** | Dependencies + PHP extensions | intl/bcmath missing = runtime crashes |
| **P0-15** | Bank accounts normalization | PSD2 multi-account impossible without this |
| **P0-11** | Transaction fingerprinting + dedupe | Re-imports create duplicates without this |
| **P0-12** | Reconciliation posting service | Match → Payment must be atomic + idempotent |
| **P0-13** | Tenant scoping audit | Data leaks between companies without this |
| **P0-14** | Partial/split payments | Real-world reconciliation requires this |

### Phase 1 Critical (Must complete before PSD2 live)

| Ticket | Description | Why Critical |
|--------|-------------|--------------|
| **P1-08** | Token encryption | Bank tokens in plaintext = security breach |
| **P1-09** | Consent lifecycle UX | Users confused when bank disconnects |
| **P1-10** | Observability + alerts | Won't know sync is broken until users complain |
| **P1-11** | API product boundaries | Rate limits + auth scheme prevent abuse |
| **P1-12** | CASYS webhook hardening | Duplicate payments = angry customers |
| **P1-13** | Metered billing | Can't charge for bank connections without this |
| **P1-14** | Queue + job hardening | Silent job failures = lost data + angry users |
| **P1-15** | Audit logging | Compliance requires immutable action trail |

### Deferred (Scope Control)

| Ticket | Status | When to Start |
|--------|--------|---------------|
| P0-08 Email parser | DEFERRED | After 10 paid users on CSV import |
| PISP interface | SEPARATE | Only when we have PISP license/partner |

---

## Schema Alignment with Investor Doc

The following table names are used (matching investor roadmap evidence plan):

| Investor Doc Reference | Actual Table | Purpose |
|------------------------|--------------|---------|
| `import_logs` | `import_logs` | Track import success/fail per row |
| `reconciliation_results` | `reconciliations` | Track auto vs manual matches |
| `reconciliation_feedback` | `reconciliation_feedback` | User corrections for ML improvement |

---

*This roadmap is updated as tickets are completed. Check off items as you go.*
