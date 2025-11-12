# Phase 1 Deployment Guide - Facturino

**Last Updated:** 2025-11-10
**Phase:** Phase 1 - Core E-Invoice, Tax Returns, Credit Notes, and Banking Infrastructure
**Version:** 1.0.0

---

## Overview

This guide covers the deployment of Phase 1 features for Facturino, which includes:

- **Audit Logging System** - Track all critical operations with detailed audit trails
- **E-Invoice System** - UBL XML generation, QES digital signatures, and e-invoice submission tracking
- **Tax Returns** - VAT/DDV return generation with period management
- **Credit Notes** - Complete credit note system with invoice references
- **PSD2 Banking** - Bank provider configuration for Macedonian banks (NLB, Stopanska, Komercijalna)

---

## 1. Prerequisites

### System Requirements

- **PHP:** 8.2 or higher
- **Composer:** 2.x
- **Database:** MySQL 8.0+ or PostgreSQL 13+ (SQLite for development only)
- **Redis:** 6.x or higher (recommended for production queues)
- **Node.js:** 18.x or higher (for frontend assets)
- **NPM:** 9.x or higher

### PHP Extensions Required

```bash
# Verify required extensions are installed
php -m | grep -E "(openssl|pdo|mbstring|tokenizer|xml|ctype|json|bcmath|fileinfo|gd|zip)"
```

Required extensions:
- `openssl` - For certificate encryption and QES signatures
- `pdo_mysql` / `pdo_pgsql` - Database connections
- `mbstring` - String handling
- `xml` - UBL XML generation
- `bcmath` - Precise currency calculations
- `fileinfo` - File type detection
- `gd` or `imagick` - Image processing (optional)
- `zip` - Archive handling

### Queue Worker Requirements

Phase 1 introduces background job processing for:
- E-invoice XML generation and signing
- Tax return calculations
- Backfill operations

**Important:** You MUST run a queue worker in production. Jobs will not process without it.

Supported queue drivers:
- **Redis** (recommended for production) - Requires `predis/predis` package (already installed)
- **Database** (acceptable for small deployments)
- **Sync** (local development only - NOT for production)

---

## 2. Deployment Steps

### Step 1: Backup Current Database

**CRITICAL:** Always backup before running migrations.

```bash
# MySQL backup
mysqldump -u username -p database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# PostgreSQL backup
pg_dump -U username database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# SQLite backup (if using SQLite)
cp database/database.sqlite database/database.sqlite.backup_$(date +%Y%m%d_%H%M%S)
```

### Step 2: Pull Latest Code

```bash
# Pull latest changes
git pull origin main

# Install/update Composer dependencies
composer install --no-dev --optimize-autoloader

# Install/update NPM dependencies
npm install

# Build frontend assets
npm run build
```

### Step 3: Run Migrations

Phase 1 migrations create the following tables:
- `audit_logs` - Audit trail for all operations
- `e_invoices` - E-invoice tracking
- `e_invoice_submissions` - Submission history
- `certificates` - QES certificate storage (encrypted)
- `signature_logs` - Digital signature audit trail
- `tax_report_periods` - Tax reporting periods
- `tax_returns` - VAT/DDV returns
- `credit_notes` - Credit note headers
- `credit_note_items` - Credit note line items
- `bank_providers` - PSD2 bank provider registry
- `bank_connections` - User bank connections
- `bank_consents` - PSD2 consent management

**Run migrations with force flag in production:**

```bash
php artisan migrate --force
```

**Expected output:**
```
Migrating: 2025_11_11_000001_create_audit_logs_table
Migrated:  2025_11_11_000001_create_audit_logs_table (45.67ms)
Migrating: 2025_11_11_100001_create_e_invoices_table
Migrated:  2025_11_11_100001_create_e_invoices_table (52.34ms)
...
(12 migrations total)
```

**Verify migrations:**

```bash
# Check migration status
php artisan migrate:status

# Verify tables exist
php artisan tinker
>>> Schema::hasTable('audit_logs');
=> true
>>> Schema::hasTable('e_invoices');
=> true
>>> Schema::hasTable('tax_returns');
=> true
>>> exit
```

### Step 4: Run Seeders

#### 4.1 Bank Provider Seeder (Required)

Seeds the 3 major Macedonian banks for PSD2 integration:
- NLB Banka AD Skopje
- Stopanska Banka AD Skopje
- Komercijalna Banka AD Skopje

```bash
php artisan db:seed --class=BankProviderSeeder
```

**Expected output:**
```
Seeded 3 Macedonian bank providers (sandbox environment)
```

**Verify:**
```bash
php artisan tinker
>>> \App\Models\BankProvider::count();
=> 3
>>> \App\Models\BankProvider::pluck('name');
=> [
     "NLB Banka AD Skopje",
     "Stopanska Banka AD Skopje",
     "Komercijalna Banka AD Skopje",
   ]
>>> exit
```

#### 4.2 Abilities Seeder (If applicable)

**Note:** As of Phase 1, there is no dedicated RolesSeeder. Abilities are managed in the application code via Bouncer. If you've added new abilities for Phase 1 features, they will be registered automatically on first use.

To verify abilities are working:

```bash
php artisan tinker
>>> Bouncer::allow('admin')->everything();
=> true
>>> exit
```

### Step 5: Configure Environment Variables

Add Phase 1-specific environment variables to your `.env` file:

```bash
# Queue Configuration (REQUIRED for production)
QUEUE_CONNECTION=redis  # Change from 'sync' to 'redis' or 'database'

# Redis Configuration (if using Redis queue)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# E-Invoice Configuration
# Certificate storage path (must be writable)
CERTIFICATE_STORAGE_PATH=storage/app/certificates

# PSD2 Banking Configuration
# Get credentials from bank developer portals
STOPANSKA_CLIENT_ID=your_client_id
STOPANSKA_CLIENT_SECRET=your_client_secret
STOPANSKA_ENVIRONMENT=sandbox  # Change to 'production' when ready

NLB_CLIENT_ID=your_client_id
NLB_CLIENT_SECRET=your_client_secret
NLB_ENVIRONMENT=sandbox

KOMERCIJALNA_CLIENT_ID=your_client_id
KOMERCIJALNA_CLIENT_SECRET=your_client_secret
KOMERCIJALNA_ENVIRONMENT=sandbox

# Rate Limiting
STOPANSKA_RATE_LIMIT_ENABLED=true
STOPANSKA_MAX_TRANSACTIONS_PER_REQUEST=200

# Feature Flags (enable Phase 1 features)
FEATURE_PSD2_BANKING=true  # Enable PSD2 banking integration
FEATURE_ADVANCED_PAYMENTS=false  # Not yet available in Phase 1
FEATURE_MONITORING=true  # Enable audit logging (recommended)
```

**Clear configuration cache:**

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Step 6: Set Directory Permissions

Ensure storage directories are writable:

```bash
# Make storage directories writable
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# If using web server user (e.g., www-data)
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache

# Specifically for certificate storage
mkdir -p storage/app/certificates
chmod 700 storage/app/certificates  # Restrict access to certificates
chown www-data:www-data storage/app/certificates
```

### Step 7: Start Queue Worker

**CRITICAL:** Queue worker MUST be running for e-invoice processing.

#### Option A: Manual Start (Development/Testing)

```bash
# Start queue worker with specific queue
php artisan queue:work --queue=einvoice,default --tries=3 --timeout=120 &

# Verify worker is running
php artisan queue:monitor

# View queue status
php artisan queue:failed
```

#### Option B: Supervisor (Recommended for Production)

Create supervisor configuration file: `/etc/supervisor/conf.d/facturino-worker.conf`

```ini
[program:facturino-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/facturino/artisan queue:work redis --queue=einvoice,default --sleep=3 --tries=3 --max-time=3600 --timeout=120
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/facturino/storage/logs/worker.log
stopwaitsecs=3600
```

**Start supervisor:**

```bash
# Reload supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start workers
sudo supervisorctl start facturino-worker:*

# Check status
sudo supervisorctl status facturino-worker:*
```

#### Option C: Systemd (Alternative for Production)

Create systemd service file: `/etc/systemd/system/facturino-queue.service`

```ini
[Unit]
Description=Facturino Queue Worker
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/facturino
ExecStart=/usr/bin/php /path/to/facturino/artisan queue:work redis --queue=einvoice,default --sleep=3 --tries=3 --timeout=120
Restart=always
RestartSec=5s

[Install]
WantedBy=multi-user.target
```

**Enable and start service:**

```bash
sudo systemctl daemon-reload
sudo systemctl enable facturino-queue
sudo systemctl start facturino-queue

# Check status
sudo systemctl status facturino-queue
```

### Step 8: (Optional) Run Backfill Jobs

If you have existing invoices or tax return XML files, you can backfill them into the new Phase 1 tables.

**IMPORTANT:** Always run in dry-run mode first to preview changes.

#### 8.1 Backfill E-Invoices

Migrates existing invoices to the e-invoice system.

```bash
php artisan tinker

# Dry run first (no changes made)
>>> \App\Jobs\BackfillEInvoicesJob::dispatch(dryRun: true);
=> Dispatched job to queue

# Check logs to review what would be created
>>> exit

# View logs
tail -f storage/logs/laravel.log

# If dry run looks good, run actual backfill
php artisan tinker
>>> \App\Jobs\BackfillEInvoicesJob::dispatch(dryRun: false);
=> Dispatched job to queue
>>> exit

# Monitor job progress
php artisan queue:monitor
```

**Backfill Logic:**
- Only processes invoices with status: SENT, VIEWED, or COMPLETED
- Old invoices (>30 days + COMPLETED) → marked as `ACCEPTED` (assumed filed)
- Recent invoices → marked as `DRAFT` (need processing)
- Creates initial `EInvoiceSubmission` record if status is `ACCEPTED`
- Processes in chunks of 100 for memory efficiency
- Idempotent: safe to run multiple times (skips existing e-invoices)

**Backfill Parameters:**

```php
// Backfill specific company
BackfillEInvoicesJob::dispatch(companyId: 123, dryRun: false);

// Change "old invoice" threshold (default: 30 days)
BackfillEInvoicesJob::dispatch(
    companyId: null,
    oldInvoiceDays: 60,
    dryRun: false
);
```

#### 8.2 Backfill Tax Returns

Migrates historical DDV (VAT) XML files from storage to tax returns system.

```bash
php artisan tinker

# Dry run first
>>> \App\Jobs\BackfillTaxReturnsJob::dispatch(dryRun: true);
=> Dispatched job to queue

# Review logs, then run actual backfill
>>> \App\Jobs\BackfillTaxReturnsJob::dispatch(dryRun: false);
=> Dispatched job to queue
>>> exit
```

**Storage Paths Scanned:**
- `storage/app/tax/ddv/*.xml`
- `storage/app/tax/vat/*.xml`
- `storage/app/tax/returns/*.xml`
- `storage/app/company/{id}/tax/*.xml` (if company_id specified)

**Backfill Logic:**
- Scans for DDV XML files in known storage locations
- Parses XML to extract period info (year, month/quarter)
- Creates `TaxReportPeriod` if doesn't exist
- Creates `TaxReturn` marked as FILED with receipt number
- Idempotent: skips if periods/returns already exist

**Backfill Parameters:**

```php
// Backfill specific company
BackfillTaxReturnsJob::dispatch(companyId: 123, dryRun: false);

// Backfill all companies
BackfillTaxReturnsJob::dispatch(companyId: null, dryRun: false);
```

---

## 3. Verification Checklist

After deployment, verify everything is working correctly:

### 3.1 Database Verification

```bash
php artisan tinker

# Check all Phase 1 tables exist
>>> Schema::hasTable('audit_logs');
=> true
>>> Schema::hasTable('e_invoices');
=> true
>>> Schema::hasTable('e_invoice_submissions');
=> true
>>> Schema::hasTable('certificates');
=> true
>>> Schema::hasTable('signature_logs');
=> true
>>> Schema::hasTable('tax_report_periods');
=> true
>>> Schema::hasTable('tax_returns');
=> true
>>> Schema::hasTable('credit_notes');
=> true
>>> Schema::hasTable('credit_note_items');
=> true
>>> Schema::hasTable('bank_providers');
=> true
>>> Schema::hasTable('bank_connections');
=> true
>>> Schema::hasTable('bank_consents');
=> true

# Verify bank providers seeded (should be 3)
>>> \App\Models\BankProvider::count();
=> 3

>>> exit
```

### 3.2 Queue Worker Verification

```bash
# Check queue worker is running
php artisan queue:monitor

# For supervisor
sudo supervisorctl status facturino-worker:*

# For systemd
sudo systemctl status facturino-queue

# Dispatch a test job to verify queue is processing
php artisan tinker
>>> \Illuminate\Support\Facades\Log::info('Queue test job dispatched');
>>> dispatch(function() { \Illuminate\Support\Facades\Log::info('Queue test job executed!'); });
>>> exit

# Check logs to confirm job ran
tail -n 20 storage/logs/laravel.log
```

### 3.3 Permissions Verification

```bash
# Verify storage directories are writable
touch storage/app/test.txt && rm storage/app/test.txt && echo "Storage writable: OK"

# Verify certificate directory permissions
ls -la storage/app/certificates
# Should show: drwx------ (700)
```

### 3.4 Configuration Verification

```bash
# Verify queue connection is NOT 'sync' in production
php artisan tinker
>>> config('queue.default');
=> "redis"  // Should be 'redis' or 'database', NOT 'sync'

# Verify feature flags
>>> config('feature.psd2_banking');
=> true
>>> config('feature.monitoring');
=> true

>>> exit
```

### 3.5 Abilities Verification

```bash
php artisan tinker

# Check admin has necessary abilities
>>> $admin = \App\Models\User::where('email', 'admin@example.com')->first();
>>> $admin->can('view-audit-logs');
=> true
>>> $admin->can('manage-e-invoices');
=> true
>>> $admin->can('manage-tax-returns');
=> true
>>> $admin->can('manage-credit-notes');
=> true
>>> $admin->can('manage-banking');
=> true

>>> exit
```

**Checklist Summary:**

- [ ] All 12 Phase 1 migrations applied successfully
- [ ] Audit logs table exists
- [ ] E-invoice tables exist (e_invoices, e_invoice_submissions, certificates, signature_logs)
- [ ] Tax return tables exist (tax_report_periods, tax_returns)
- [ ] Credit note tables exist (credit_notes, credit_note_items)
- [ ] Bank tables exist (bank_providers, bank_connections, bank_consents)
- [ ] Bank provider seeder ran successfully (3 banks)
- [ ] Queue worker is running (not 'sync' in production)
- [ ] Queue connection is Redis or Database (NOT sync)
- [ ] Storage directories are writable
- [ ] Certificate directory has correct permissions (700)
- [ ] Abilities seeded correctly for admin users
- [ ] Feature flags enabled (PSD2_BANKING, MONITORING)

---

## 4. Railway-Specific Deployment

Railway provides a Platform-as-a-Service (PaaS) for deploying Laravel applications.

### 4.1 Environment Variables

Add the following to your Railway project environment variables:

```bash
# Database (Railway provides these automatically)
# DATABASE_URL is automatically parsed by Laravel 11+

# Queue Configuration
QUEUE_CONNECTION=redis
REDIS_URL=${REDIS_URL}  # Railway provides this if Redis add-on enabled

# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.railway.app

# Phase 1 Features
FEATURE_PSD2_BANKING=true
FEATURE_MONITORING=true

# Bank Credentials (add your actual credentials)
STOPANSKA_CLIENT_ID=your_client_id
STOPANSKA_CLIENT_SECRET=your_client_secret
STOPANSKA_ENVIRONMENT=sandbox
```

### 4.2 Enable Redis Add-on

1. Open your Railway project
2. Click "New" → "Database" → "Add Redis"
3. Railway automatically sets `REDIS_URL` environment variable
4. Verify: `QUEUE_CONNECTION=redis` in environment variables

### 4.3 Run Migrations on Railway

**Option A: Using Railway CLI**

```bash
# Install Railway CLI
npm install -g @railway/cli

# Login to Railway
railway login

# Link to your project
railway link

# Run migrations
railway run php artisan migrate --force

# Run seeders
railway run php artisan db:seed --class=BankProviderSeeder
```

**Option B: Using Railway Dashboard**

1. Go to your Railway project
2. Navigate to "Deployments" tab
3. Find the latest successful deployment
4. Click "View Logs"
5. Use the terminal icon to open a shell
6. Run migration commands:
   ```bash
   php artisan migrate --force
   php artisan db:seed --class=BankProviderSeeder
   ```

**Option C: Automatic Migrations on Deploy**

Add to your `railway.json` (or create if doesn't exist):

```json
{
  "build": {
    "builder": "NIXPACKS"
  },
  "deploy": {
    "startCommand": "php artisan migrate --force && php artisan config:cache && php artisan serve --host=0.0.0.0 --port=$PORT",
    "restartPolicyType": "ON_FAILURE",
    "restartPolicyMaxRetries": 10
  }
}
```

**Note:** Auto-migrations can be risky. Only use if you're confident in your migration scripts.

### 4.4 Configure Queue Worker on Railway

Railway requires a separate service for queue workers.

**Method 1: Add Queue Worker Service (Recommended)**

1. In Railway dashboard, click "New" → "Empty Service"
2. Name it: "facturino-queue-worker"
3. Connect same GitHub repo
4. Set custom start command:
   ```bash
   php artisan queue:work redis --queue=einvoice,default --tries=3 --timeout=120 --sleep=3 --max-time=3600
   ```
5. Set environment variables (same as main app)
6. Deploy

**Method 2: Use Procfile**

Create `Procfile` in project root:

```
web: php artisan serve --host=0.0.0.0 --port=$PORT
worker: php artisan queue:work redis --queue=einvoice,default --tries=3 --timeout=120 --sleep=3 --max-time=3600
```

Railway will automatically detect and run both processes.

### 4.5 Monitor Jobs on Railway

```bash
# Using Railway CLI
railway run php artisan queue:monitor
railway run php artisan queue:failed

# View queue worker logs
railway logs --service facturino-queue-worker

# Clear failed jobs
railway run php artisan queue:flush
```

### 4.6 Railway Health Checks

Add health check endpoint for Railway monitoring:

1. Create route in `routes/web.php`:
   ```php
   Route::get('/health', function () {
       return response()->json([
           'status' => 'ok',
           'queue_connection' => config('queue.default'),
           'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
       ]);
   });
   ```

2. Configure in Railway dashboard:
   - Health Check Path: `/health`
   - Health Check Interval: 60 seconds

---

## 5. Rollback Plan

If something goes wrong during deployment, follow this rollback procedure.

### 5.1 Stop Queue Workers

**CRITICAL:** Stop workers BEFORE rolling back migrations to prevent job processing errors.

```bash
# If using supervisor
sudo supervisorctl stop facturino-worker:*

# If using systemd
sudo systemctl stop facturino-queue

# If manual process
pkill -f "queue:work"

# On Railway
railway service stop facturino-queue-worker
```

### 5.2 Rollback Migrations

Phase 1 includes 12 migrations. Rollback all of them:

```bash
# Rollback last 12 migrations
php artisan migrate:rollback --step=12

# Verify rollback
php artisan migrate:status
```

### 5.3 Restore Database Backup

If rollback fails or data is corrupted:

```bash
# MySQL restore
mysql -u username -p database_name < backup_YYYYMMDD_HHMMSS.sql

# PostgreSQL restore
psql -U username database_name < backup_YYYYMMDD_HHMMSS.sql

# SQLite restore
cp database/database.sqlite.backup_YYYYMMDD_HHMMSS database/database.sqlite
```

### 5.4 Revert Code Changes

```bash
# Find commit before Phase 1 deployment
git log --oneline

# Reset to previous commit (replace COMMIT_HASH)
git reset --hard COMMIT_HASH

# Or revert specific merge/commit
git revert COMMIT_HASH
```

### 5.5 Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# If using Redis for cache
redis-cli FLUSHDB
```

### 5.6 Restart Services

```bash
# Restart queue workers (if not using Phase 1 features anymore)
sudo supervisorctl start facturino-worker:*
# OR
sudo systemctl start facturino-queue

# Restart web server
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
# OR
sudo systemctl restart apache2
```

### 5.7 Verify Rollback

```bash
php artisan tinker

# Verify Phase 1 tables are removed
>>> Schema::hasTable('audit_logs');
=> false
>>> Schema::hasTable('e_invoices');
=> false
>>> Schema::hasTable('tax_returns');
=> false

>>> exit
```

---

## 6. Testing After Deployment

Perform these smoke tests to verify Phase 1 features are working correctly.

### 6.1 Test Audit Logging

Create an invoice and verify audit log is created:

```bash
php artisan tinker

# Create a test invoice
>>> $company = \App\Models\Company::first();
>>> $customer = \App\Models\Customer::first();
>>> $invoice = \App\Models\Invoice::create([
...     'company_id' => $company->id,
...     'customer_id' => $customer->id,
...     'invoice_number' => 'TEST-' . now()->format('YmdHis'),
...     'invoice_date' => now(),
...     'due_date' => now()->addDays(30),
...     'status' => 'DRAFT',
...     'total' => 1000,
...     'tax' => 180,
...     'sub_total' => 820,
... ]);

# Check audit log was created
>>> \App\Models\AuditLog::where('auditable_type', 'App\Models\Invoice')
...     ->where('auditable_id', $invoice->id)
...     ->where('event', 'created')
...     ->exists();
=> true

>>> exit
```

### 6.2 Test E-Invoice Creation

Create an e-invoice and verify UBL XML generation:

```bash
php artisan tinker

# Get an invoice
>>> $invoice = \App\Models\Invoice::where('status', 'SENT')->first();

# Create e-invoice (this will dispatch a job)
>>> $eInvoice = \App\Models\EInvoice::create([
...     'invoice_id' => $invoice->id,
...     'company_id' => $invoice->company_id,
...     'status' => 'DRAFT',
... ]);

# Verify e-invoice created
>>> $eInvoice->exists;
=> true

# Check if job dispatched (requires queue worker running)
>>> exit

# Monitor queue
php artisan queue:monitor

# Check e-invoice status after job runs
php artisan tinker
>>> $eInvoice->refresh();
>>> $eInvoice->status;
=> "READY_TO_SIGN" // or "DRAFT" if UBL generation pending

>>> exit
```

### 6.3 Test Certificate Upload

Upload a test certificate and verify encryption:

**Note:** You need a valid PKCS#12 (.pfx/.p12) certificate for QES signing.

```bash
php artisan tinker

# Create test certificate record
>>> $company = \App\Models\Company::first();
>>> $cert = \App\Models\Certificate::create([
...     'company_id' => $company->id,
...     'certificate_type' => 'QES',
...     'file_name' => 'test-cert.pfx',
...     'file_path' => 'certificates/test-cert.pfx',
...     'valid_from' => now(),
...     'valid_until' => now()->addYear(),
...     'subject_name' => 'Test Company',
...     'issuer_name' => 'Test CA',
...     'serial_number' => '1234567890',
...     'status' => 'ACTIVE',
... ]);

# Verify certificate encrypted (should NOT be able to read plaintext)
>>> $cert->exists;
=> true

>>> exit
```

### 6.4 Test Tax Return Creation

Create a tax return and verify period management:

```bash
php artisan tinker

# Create tax report period
>>> $company = \App\Models\Company::first();
>>> $period = \App\Models\TaxReportPeriod::create([
...     'company_id' => $company->id,
...     'period_type' => 'MONTHLY',
...     'year' => now()->year,
...     'month' => now()->month,
...     'start_date' => now()->startOfMonth(),
...     'end_date' => now()->endOfMonth(),
...     'status' => 'OPEN',
... ]);

# Create tax return
>>> $taxReturn = \App\Models\TaxReturn::create([
...     'company_id' => $company->id,
...     'period_id' => $period->id,
...     'return_type' => 'VAT',
...     'status' => 'DRAFT',
...     'return_data' => ['test' => true],
... ]);

# Verify created
>>> $taxReturn->exists;
=> true
>>> $taxReturn->period->period_type;
=> "MONTHLY"

>>> exit
```

### 6.5 Test Credit Note Creation

Create a credit note referencing an invoice:

```bash
php artisan tinker

# Get an invoice to credit
>>> $invoice = \App\Models\Invoice::where('status', 'SENT')->first();
>>> $company = $invoice->company;
>>> $customer = $invoice->customer;

# Create credit note
>>> $creditNote = \App\Models\CreditNote::create([
...     'company_id' => $company->id,
...     'customer_id' => $customer->id,
...     'invoice_id' => $invoice->id,
...     'credit_note_number' => 'CN-TEST-' . now()->format('YmdHis'),
...     'credit_note_date' => now(),
...     'reason' => 'Testing credit note creation',
...     'status' => 'DRAFT',
...     'total' => 100,
...     'tax' => 18,
...     'sub_total' => 82,
... ]);

# Create credit note item
>>> $creditNoteItem = \App\Models\CreditNoteItem::create([
...     'credit_note_id' => $creditNote->id,
...     'company_id' => $company->id,
...     'name' => 'Test Item',
...     'description' => 'Test credit note item',
...     'quantity' => 1,
...     'price' => 82,
...     'total' => 82,
...     'tax' => 18,
... ]);

# Verify created and linked to invoice
>>> $creditNote->exists;
=> true
>>> $creditNote->invoice_id === $invoice->id;
=> true

>>> exit
```

### 6.6 Test Bank Provider Configuration

Verify bank providers were seeded correctly:

```bash
php artisan tinker

# Check all 3 banks exist
>>> \App\Models\BankProvider::count();
=> 3

# Get NLB bank
>>> $nlb = \App\Models\BankProvider::where('key', 'nlb')->first();
>>> $nlb->name;
=> "NLB Banka AD Skopje"
>>> $nlb->supports_ais;
=> true
>>> $nlb->environment;
=> "sandbox"

# Get Stopanska bank
>>> $stopanska = \App\Models\BankProvider::where('key', 'stopanska')->first();
>>> $stopanska->name;
=> "Stopanska Banka AD Skopje"

# Get Komercijalna bank
>>> $komercijalna = \App\Models\BankProvider::where('key', 'komercijalna')->first();
>>> $komercijalna->name;
=> "Komercijalna Banka AD Skopje"

>>> exit
```

---

## 7. Monitoring and Maintenance

### 7.1 Log Files to Monitor

```bash
# Application logs
tail -f storage/logs/laravel.log

# Queue worker logs (if using supervisor)
tail -f storage/logs/worker.log

# Web server logs
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log
```

### 7.2 Queue Monitoring Commands

```bash
# Monitor active jobs
php artisan queue:monitor

# List failed jobs
php artisan queue:failed

# Retry failed job
php artisan queue:retry {job-id}

# Retry all failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush

# Restart queue workers (picks up new code)
php artisan queue:restart
```

### 7.3 Database Maintenance

```bash
# Check table sizes (MySQL)
php artisan tinker
>>> DB::select("SELECT table_name, ROUND((data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
...     FROM information_schema.TABLES
...     WHERE table_schema = DATABASE()
...     ORDER BY (data_length + index_length) DESC
...     LIMIT 10;");

# Optimize tables (MySQL)
>>> DB::statement('OPTIMIZE TABLE audit_logs');
>>> DB::statement('OPTIMIZE TABLE e_invoices');

>>> exit
```

### 7.4 Performance Monitoring

Key metrics to track:
- Queue job processing time
- Failed job rate
- Database query performance
- Storage disk usage (certificates, audit logs)
- API response times (PSD2 bank connections)

### 7.5 Scheduled Maintenance Tasks

Consider adding to Laravel scheduler (`app/Console/Kernel.php`):

```php
protected function schedule(Schedule $schedule)
{
    // Cleanup old audit logs (older than 2 years)
    $schedule->command('app:cleanup-audit-logs --days=730')->monthly();

    // Retry failed queue jobs
    $schedule->command('queue:retry all')->daily();

    // Database backup
    $schedule->command('backup:run')->daily();
}
```

---

## 8. Troubleshooting

### Issue: Migrations fail with "errno 150" (foreign key error)

**Cause:** MySQL collation mismatch between tables.

**Solution:**
```bash
# Check table collations
php artisan tinker
>>> DB::select("SHOW TABLE STATUS WHERE Name = 'invoices'");

# All Phase 1 migrations use utf8mb4 charset
# Verify .env database charset:
>>> config('database.connections.mysql.charset');
=> "utf8mb4"

>>> exit
```

If tables have different charsets, you may need to rebuild:
```bash
php artisan migrate:fresh --seed
```

### Issue: Queue jobs not processing

**Cause:** Queue worker not running or QUEUE_CONNECTION set to 'sync'.

**Solution:**
```bash
# Check queue connection
php artisan tinker
>>> config('queue.default');
=> Should be 'redis' or 'database', NOT 'sync'

# Check queue worker is running
ps aux | grep "queue:work"

# Restart queue workers
php artisan queue:restart
sudo supervisorctl restart facturino-worker:*
```

### Issue: Certificate encryption fails

**Cause:** Missing `openssl` PHP extension or incorrect file permissions.

**Solution:**
```bash
# Check openssl extension
php -m | grep openssl

# Check certificate directory permissions
ls -la storage/app/certificates
# Should be: drwx------ (700)

# Fix permissions
chmod 700 storage/app/certificates
```

### Issue: PSD2 bank connection fails

**Cause:** Invalid credentials or sandbox environment misconfiguration.

**Solution:**
```bash
# Verify environment variables are set
php artisan tinker
>>> config('services.stopanska.client_id');
=> "your_client_id" (should not be null)

>>> config('services.stopanska.environment');
=> "sandbox"

# Check bank provider is active
>>> \App\Models\BankProvider::where('key', 'stopanska')->value('is_active');
=> true

>>> exit

# Test API connectivity
curl https://sandbox-api.stb.kibs.mk/xs2a/v1/
```

### Issue: Backfill jobs timeout

**Cause:** Large dataset taking too long to process.

**Solution:**
```bash
# Increase job timeout in BackfillEInvoicesJob.php (already set to 3600s)
# Process by company in smaller batches:

php artisan tinker
>>> $companies = \App\Models\Company::pluck('id');
>>> foreach ($companies as $companyId) {
...     \App\Jobs\BackfillEInvoicesJob::dispatch(companyId: $companyId, dryRun: false);
... }
>>> exit
```

### Issue: Railway deployment fails

**Cause:** Missing environment variables or Redis not provisioned.

**Solution:**
1. Check all environment variables are set in Railway dashboard
2. Ensure Redis add-on is provisioned
3. Verify `QUEUE_CONNECTION=redis` in Railway environment
4. Check deployment logs: `railway logs`

---

## 9. Support and Documentation

### Internal Documentation

- **Phase 1 Migration Files:** `/database/migrations/2025_11_11_*.php`
- **Seeder Files:** `/database/seeders/BankProviderSeeder.php`
- **Job Files:** `/app/Jobs/Backfill*.php`
- **Model Files:** `/app/Models/{AuditLog,EInvoice,TaxReturn,CreditNote,BankProvider}.php`

### External Resources

- **PSD2 Banking API Docs:**
  - Stopanska: https://ob.stb.kibs.mk/docs/
  - NLB: https://developer-ob.nlb.mk/

- **UBL XML Standard:** https://docs.oasis-open.org/ubl/UBL-2.1.html
- **Laravel Queue Docs:** https://laravel.com/docs/11.x/queues
- **Railway Deployment:** https://docs.railway.app/

### Getting Help

If you encounter issues not covered in this guide:

1. Check application logs: `storage/logs/laravel.log`
2. Check queue worker logs: `storage/logs/worker.log`
3. Review failed jobs: `php artisan queue:failed`
4. Enable debug mode temporarily: `APP_DEBUG=true` (NEVER in production)
5. Contact development team with:
   - Error message
   - Steps to reproduce
   - Relevant log excerpts
   - Environment details (PHP version, database version, etc.)

---

## 10. Post-Deployment Checklist

**Final verification before announcing to users:**

- [ ] All migrations applied successfully (12 total)
- [ ] All seeders ran successfully (BankProviderSeeder)
- [ ] Queue worker running and processing jobs
- [ ] Redis connection working (if using Redis)
- [ ] Storage directories writable (especially `storage/app/certificates`)
- [ ] Environment variables configured correctly
- [ ] Feature flags enabled (FEATURE_PSD2_BANKING, FEATURE_MONITORING)
- [ ] Audit logging tested (create invoice → check audit log)
- [ ] E-invoice creation tested
- [ ] Tax return creation tested
- [ ] Credit note creation tested
- [ ] Bank providers verified (3 banks seeded)
- [ ] Backfill jobs completed successfully (if applicable)
- [ ] No failed queue jobs (`php artisan queue:failed`)
- [ ] Application accessible and responsive
- [ ] SSL certificate valid (if production)
- [ ] Monitoring/logging configured and working
- [ ] Backup strategy confirmed and tested
- [ ] Rollback plan tested in staging environment

---

**Deployment completed successfully! Phase 1 features are now live.**

For Phase 2 deployment (Partner Portal, Advanced Payments, etc.), refer to `DEPLOYMENT_PHASE2.md` (when available).

---

**Document Version:** 1.0.0
**Last Updated:** 2025-11-10
**Maintained By:** Facturino Development Team
