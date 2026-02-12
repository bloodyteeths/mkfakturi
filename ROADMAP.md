# Facturino v1 — Gap Closure Roadmap

> Generated 2026-02-11 from Opus 4.6 market research audit.
> Continues from existing phases P0–P6 (see `CODEBASE_ROADMAP.md`).

---

## Overview

| Phase | Theme | Tickets | Est. Effort | Priority |
|-------|-------|---------|-------------|----------|
| **P7** | Compliance Critical | P7-01 … P7-05 | 9–13 days | MUST — blocks revenue / legal compliance |
| **P8** | Bureau Distribution | P8-01 … P8-03 | 6–9 days | SHOULD — blocks accountant GTM channel |
| **P9** | Data Accuracy | P9-01 | 1–2 days | SHOULD — replaces interim solution |
| **P10** | Mobile & Hardware | P10-01 … P10-02 | 8–12 days | COULD — competitive differentiator |

**Total estimated effort: 24–36 days**

### Dependency Graph

```
P7-01 (10% VAT)          ─── independent, start immediately
P7-02 (Incoming e-invoice) ─── independent, start immediately
P7-03 (Leave management)  ─── independent, start immediately
P7-04 (Overtime)           ─── depends on P7-03 (leave affects gross before overtime calc)
P7-05 (Contribution caps)  ─── depends on P7-04 (caps apply after overtime gross)
P8-01 (Client doc portal)  ─── independent, start after P7 stabilises
P8-02 (Deadline tracking)  ─── independent
P8-03 (Bulk reporting)     ─── independent
P9-01 (NBRM rates)         ─── independent
P10-01 (PWA mobile)        ─── independent
P10-02 (Fiscal devices)    ─── deferred until UJP publishes device API specs
```

---

## Phase 7 — Compliance Critical

### P7-01: Add 10% Restaurant VAT Rate

**Why:** Macedonian VAT has three tiers — 18% standard, 10% restaurant services, 5% reduced.
The 10% rate is missing from config, seeder, and UBL mapper. Restaurants using Facturino
will generate non-compliant invoices.

**Effort:** 1–2 hours

#### Files to modify

| File | Change |
|------|--------|
| `config/mk.php` (line ~96) | Add `'restaurant_vat_rate' => 10` inside `tax_authority` array |
| `database/seeders/MkVatSeeder.php` | Add entry: `['name' => 'ДДВ 10% (угостителство)', 'percent' => 10.00, 'type' => TaxType::TYPE_GENERAL, 'description' => 'Даночна стапка за угостителски услуги']` |
| `Modules/Mk/Services/MkUblMapper.php` (line ~388) | Add `elseif ($percent == 10) { return 'S'; }` in `getTaxCategoryId()` — 10% is standard-rate category in UBL with reduced percent |

#### DB changes
None — `TaxType.percent` is already a float column. Seeder handles data insertion idempotently.

#### API changes
None — existing tax type CRUD endpoints already support any percent value.

#### Vue changes
None — tax type selector already renders all seeded rates dynamically.

#### Test plan
1. Run `php artisan db:seed --class=MkVatSeeder` — verify 10% rate appears in `tax_types` table
2. Create invoice with restaurant line item at 10% — verify XML output has `<cbc:Percent>10.00</cbc:Percent>` and `<cbc:ID>S</cbc:ID>`
3. Unit test: `MkUblMapperTest::it_maps_10_percent_to_standard_category`

---

### P7-02: Incoming E-Invoice Acceptance Workflow

**Why:** The e-Faktura mandate requires businesses to both **send and receive** e-invoices.
Current implementation is outbound-only. When mandatory B2B e-invoicing goes live (Q3 2026),
companies must accept/reject incoming invoices from suppliers via the UJP portal.

**Effort:** 3–5 days

#### Files to modify

| File | Change |
|------|--------|
| `app/Models/EInvoice.php` (line ~55) | Add constants: `STATUS_RECEIVED = 'RECEIVED'`, `STATUS_UNDER_REVIEW = 'UNDER_REVIEW'`, `STATUS_ACCEPTED_INCOMING = 'ACCEPTED_INCOMING'`, `STATUS_REJECTED_INCOMING = 'REJECTED_INCOMING'` |
| `app/Models/EInvoice.php` | Add column: `direction` enum (`outbound`, `inbound`) — default `outbound` |
| `app/Models/EInvoice.php` | Add methods: `markAsReceived()`, `markUnderReview()`, `acceptIncoming()`, `rejectIncoming(?string $reason)` |
| `app/Models/EInvoice.php` | Add scopes: `inbound()`, `outbound()`, `pendingReview()` |
| `app/Http/Controllers/V1/Admin/EInvoice/EInvoiceController.php` | Add methods (after `resubmit()` at line ~561): `listIncoming()`, `showIncoming()`, `acceptIncoming()`, `rejectIncoming()`, `pollPortalInbox()` |
| `app/Jobs/PollEInvoiceInboxJob.php` | **New file.** Queued job that polls UJP portal for new incoming invoices. Pattern: `SubmitEInvoiceJob.php` (3 retries, exponential backoff). Parses received UBL XML → creates EInvoice record with `direction=inbound`, `status=RECEIVED`. |
| `tools/efaktura_download.php` | **New file.** Standalone PHP tool (mirrors `efaktura_upload.php`) for downloading incoming invoices from portal. Portal endpoints: `/invoice/inbox.seam`, `/invoice/download.seam`. |
| `routes/api.php` | Add routes inside existing `e-invoices` prefix group |
| `resources/scripts/admin/views/invoices/partials/EInvoiceInboxTab.vue` | **New file.** Tab on invoice list showing received e-invoices with accept/reject buttons, UBL preview, and supplier info extraction. |

#### DB changes

**Migration:** `database/migrations/2026_02_15_000100_add_incoming_einvoice_support.php`

```php
// Add to e_invoices table (idempotent)
if (!Schema::hasColumn('e_invoices', 'direction')) {
    Schema::table('e_invoices', function (Blueprint $table) {
        $table->enum('direction', ['outbound', 'inbound'])->default('outbound')->after('status');
        $table->timestamp('received_at')->nullable()->after('submitted_at');
        $table->timestamp('reviewed_at')->nullable()->after('received_at');
        $table->string('sender_vat_id', 20)->nullable()->after('direction');
        $table->string('sender_name', 255)->nullable()->after('sender_vat_id');
        $table->string('portal_inbox_id', 100)->nullable()->after('sender_name');
        $table->index(['company_id', 'direction', 'status']);
    });
}
```

#### API endpoints

```
GET    /api/v1/e-invoices/incoming              → listIncoming()        (paginated, filterable)
GET    /api/v1/e-invoices/incoming/{id}          → showIncoming()        (detail + UBL preview)
POST   /api/v1/e-invoices/incoming/{id}/accept   → acceptIncoming()      (creates supplier bill)
POST   /api/v1/e-invoices/incoming/{id}/reject   → rejectIncoming()      (sends rejection to portal)
POST   /api/v1/e-invoices/incoming/poll          → pollPortalInbox()     (trigger manual poll)
```

Middleware: `tier:standard` (same as outbound e-invoicing)

#### Vue components

| Component | Location | Description |
|-----------|----------|-------------|
| `EInvoiceInboxTab.vue` | `resources/scripts/admin/views/invoices/partials/` | Inbox list with status badges, accept/reject actions |
| `EInvoicePreviewModal.vue` | `resources/scripts/admin/components/modal-components/` | Renders UBL XML as human-readable invoice preview |

#### Test plan
1. Feature test: `tests/Feature/EInvoiceIncomingTest.php`
   - Poll returns mock UBL XML → creates RECEIVED record
   - Accept transitions to ACCEPTED_INCOMING → creates expense/bill
   - Reject transitions to REJECTED_INCOMING with reason
   - Direction filter returns only inbound
2. Unit test: UBL XML parser extracts sender info correctly
3. Manual: Upload sample UBL XML via API → verify preview renders

---

### P7-03: Leave Management (Annual, Sick, Maternity)

**Why:** Macedonian labor law mandates 20-day minimum annual leave, sick leave at 70–100% of gross
for first 30 days (employer-funded), and 9-month maternity leave at full pay. Current payroll
has no leave tracking — gross salary doesn't account for leave deductions.

**Effort:** 3–4 days

#### Files to create

| File | Description |
|------|-------------|
| `app/Models/LeaveType.php` | Model: id, company_id, name, name_mk, code (ANNUAL/SICK/MATERNITY/UNPAID), max_days_per_year, pay_percentage (100/70/0), is_active |
| `app/Models/LeaveRequest.php` | Model: id, company_id, employee_id, leave_type_id, start_date, end_date, business_days, status (pending/approved/rejected/cancelled), reason, approved_by, approved_at, rejection_reason |
| `app/Http/Controllers/V1/Admin/Payroll/LeaveRequestController.php` | CRUD + approve/reject workflow |
| `app/Http/Requests/LeaveRequestRequest.php` | Validation: no overlapping leaves, min 1 day, employee is active |
| `app/Policies/LeaveRequestPolicy.php` | Authorization: viewAny, create, approve (manager only), reject |
| `database/seeders/LeaveTypesSeeder.php` | Seeds: Годишен одмор (20d, 100%), Боледување (30d, 70%), Породилно отсуство (270d, 100%), Неплатено (30d, 0%) |
| `resources/scripts/admin/views/payroll/leave/Index.vue` | Leave request list with status filters, calendar view |
| `resources/scripts/admin/views/payroll/leave/Create.vue` | Create/edit leave request form with date picker, day counter |
| `Modules/Mk/Payroll/Services/LeaveCalculationService.php` | Calculates leave deductions: (daily_rate * leave_days * (1 - pay_percentage)) |

#### Files to modify

| File | Change |
|------|--------|
| `Modules/Mk/Payroll/Services/PayrollCalculationService.php` (line ~70) | Before tax calculation, call `LeaveCalculationService::calculateLeaveDeduction()` to adjust gross for unpaid/partial-pay leave days in the period |
| `app/Models/PayrollRunLine.php` (fillable array) | Add: `leave_days_taken`, `leave_deduction_amount` |
| `resources/scripts/admin/admin-router.js` | Add leave routes under `/admin/payroll/leave` |
| `resources/scripts/partner/partner-router.js` | Add leave routes for partner access |
| `routes/api.php` | Add leave endpoints inside `tier:payroll` middleware group |

#### DB changes

**Migration:** `database/migrations/2026_02_15_000200_create_leave_management_tables.php`

```php
// leave_types table
Schema::create('leave_types', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('company_id');
    $table->string('name', 100);
    $table->string('name_mk', 100);
    $table->string('code', 20); // ANNUAL, SICK, MATERNITY, UNPAID
    $table->unsignedSmallInteger('max_days_per_year')->default(20);
    $table->decimal('pay_percentage', 5, 2)->default(100.00); // 100%, 70%, 0%
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
    $table->unique(['company_id', 'code']);
}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

// leave_requests table
Schema::create('leave_requests', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('company_id');
    $table->unsignedBigInteger('employee_id');
    $table->unsignedBigInteger('leave_type_id');
    $table->date('start_date');
    $table->date('end_date');
    $table->unsignedSmallInteger('business_days');
    $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
    $table->text('reason')->nullable();
    $table->unsignedBigInteger('approved_by')->nullable();
    $table->timestamp('approved_at')->nullable();
    $table->text('rejection_reason')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
    $table->foreign('employee_id')->references('id')->on('payroll_employees')->onDelete('restrict');
    $table->foreign('leave_type_id')->references('id')->on('leave_types')->onDelete('restrict');
    $table->foreign('approved_by')->references('id')->on('users')->onDelete('restrict');
    $table->index(['company_id', 'employee_id', 'status']);
    $table->index(['company_id', 'start_date', 'end_date']);
}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

// Add leave columns to payroll_run_lines (idempotent)
if (!Schema::hasColumn('payroll_run_lines', 'leave_days_taken')) {
    Schema::table('payroll_run_lines', function (Blueprint $table) {
        $table->unsignedSmallInteger('leave_days_taken')->default(0)->after('worked_days');
        $table->integer('leave_deduction_amount')->default(0)->after('leave_days_taken');
    });
}
```

#### API endpoints

```
GET    /api/v1/leave-requests                    → index()    (paginated, filter by employee/status/date)
POST   /api/v1/leave-requests                    → store()    (create leave request)
GET    /api/v1/leave-requests/{id}               → show()
PATCH  /api/v1/leave-requests/{id}               → update()   (edit pending only)
DELETE /api/v1/leave-requests/{id}               → destroy()  (cancel pending only)
POST   /api/v1/leave-requests/{id}/approve       → approve()
POST   /api/v1/leave-requests/{id}/reject        → reject()
GET    /api/v1/leave-requests/balance/{employee}  → balance()  (remaining days per type)
GET    /api/v1/leave-types                        → listTypes()
```

Middleware: `tier:payroll` (Business tier minimum, same as payroll)

#### Test plan
1. Feature test: `tests/Feature/LeaveManagementTest.php`
   - Create leave request → status = pending
   - Approve → status = approved, approved_by set
   - Reject → status = rejected, rejection_reason set
   - Overlap detection: two leaves same dates → 422
   - Balance calculation: 20 - 5 used = 15 remaining
2. Unit test: `tests/Unit/LeaveCalculationServiceTest.php`
   - 5 sick days at 70% → deduction = 30% * 5 * daily_rate
   - 10 annual days at 100% → deduction = 0
   - 5 unpaid days at 0% → deduction = 100% * 5 * daily_rate
3. Integration: Run payroll with employee who has 3 approved sick days → verify gross reduced by 30% for those days

---

### P7-04: Overtime Calculations (135–150%)

**Why:** Macedonian labor law requires overtime at 135% for regular overtime and 150% for
holidays/night work. Current payroll has no overtime tracking.

**Effort:** 1–2 days | **Depends on:** P7-03 (leave days affect working days before overtime)

#### Files to create

| File | Description |
|------|-------------|
| `Modules/Mk/Payroll/Services/OvertimeCalculationService.php` | Calculates overtime premium: `overtime_amount = (hourly_rate * overtime_hours * multiplier) - (hourly_rate * overtime_hours)`. Hourly rate = gross / (working_days * 8). Multipliers: 1.35 regular, 1.50 holiday/night. |

#### Files to modify

| File | Change |
|------|--------|
| `app/Models/PayrollRunLine.php` (fillable) | Add: `overtime_hours` (decimal 5,2), `overtime_multiplier` (decimal 3,2 default 1.35), `overtime_amount` (integer) |
| `Modules/Mk/Payroll/Services/PayrollCalculationService.php` (line ~70) | After leave deduction, before tax calc: call `OvertimeCalculationService::calculate()` → add overtime_amount to gross |
| `Modules/Mk/Payroll/DTOs/PayrollCalculationResult.php` | Add fields: `overtimeHours`, `overtimeAmount`, `overtimeMultiplier` |
| `config/mk.php` | Add in payroll section: `'overtime_regular_multiplier' => 1.35, 'overtime_holiday_multiplier' => 1.50` |
| `resources/scripts/admin/views/payroll/runs/Show.vue` | Add overtime columns to payroll run detail table |

#### DB changes

**Migration:** `database/migrations/2026_02_15_000300_add_overtime_to_payroll_run_lines.php`

```php
if (!Schema::hasColumn('payroll_run_lines', 'overtime_hours')) {
    Schema::table('payroll_run_lines', function (Blueprint $table) {
        $table->decimal('overtime_hours', 5, 2)->default(0)->after('leave_deduction_amount');
        $table->decimal('overtime_multiplier', 3, 2)->default(1.35)->after('overtime_hours');
        $table->integer('overtime_amount')->default(0)->after('overtime_multiplier');
    });
}
```

#### Test plan
1. Unit test: `tests/Unit/OvertimeCalculationServiceTest.php`
   - Gross 60,000 MKD, 22 working days, 10 overtime hours at 1.35 → overtime_amount = 10 * (60000/176) * 0.35
   - Holiday overtime at 1.50 → higher premium
   - 0 overtime hours → 0 amount
2. Integration: Payroll run with overtime → verify gross includes overtime premium, taxes calculated on adjusted gross

---

### P7-05: Minimum/Maximum Contribution Base Caps

**Why:** Macedonian social contributions have a minimum base of MKD 31,577/month (50% of national
average MKD 63,154) and maximum base of MKD 1,010,464. Current payroll applies flat percentages
without caps. Very low or very high earners get incorrect calculations.

**Effort:** 0.5–1 day | **Depends on:** P7-04 (caps apply to final gross after overtime)

#### Files to modify

| File | Change |
|------|--------|
| `Modules/Mk/Payroll/Services/MacedonianPayrollTaxService.php` (line ~36) | Add constants: `MIN_CONTRIBUTION_BASE = 3157700` (in cents), `MAX_CONTRIBUTION_BASE = 101046400`. Before calculating each contribution, clamp gross to [MIN, MAX]: `$contributionBase = max($minBase, min($maxBase, $gross))` |
| `config/mk.php` | Add in payroll section: `'min_contribution_base' => 31577, 'max_contribution_base' => 1010464, 'national_avg_salary' => 63154` (in MKD, not cents) |
| `Modules/Mk/Payroll/DTOs/PayrollCalculationResult.php` | Add: `contributionBaseClamped` (boolean — true if capping was applied) |

#### DB changes
None.

#### Test plan
1. Unit test: `tests/Unit/MacedonianPayrollTaxServiceTest.php` (extend existing)
   - Gross 20,000 MKD (below min) → contributions calculated on 31,577 base
   - Gross 1,200,000 MKD (above max) → contributions calculated on 1,010,464 base
   - Gross 60,000 MKD (in range) → contributions calculated on actual gross (unchanged)
2. Regression: Re-run all existing payroll tests — no breakage for normal salary ranges

---

## Phase 8 — Bureau Distribution (GTM-Critical)

### P8-01: Client Document Upload Portal

**Why:** Accounting bureaus need clients to digitally upload invoices, receipts, and contracts
instead of delivering paper. Currently only partner KYC document upload exists. No client-facing
upload mechanism.

**Effort:** 2–3 days

#### Files to create

| File | Description |
|------|-------------|
| `app/Models/ClientDocument.php` | Model: id, company_id, uploaded_by (user_id), partner_id (managing accountant), category (invoice/receipt/contract/bank_statement/other), original_filename, file_path, file_size, mime_type, status (pending_review/reviewed/rejected), reviewer_id, reviewed_at, notes, metadata (JSON). Pattern: `KycDocument.php`. Uses SoftDeletes. |
| `app/Http/Controllers/V1/Client/ClientDocumentController.php` | Client-side: upload, list own, delete pending. Uses Spatie MediaLibrary (pattern from `UploadReceiptController.php`). Max 10 MB per file. Accepted: PDF, PNG, JPEG, XLSX, CSV. |
| `app/Http/Controllers/V1/Partner/PartnerClientDocumentController.php` | Partner-side: list all docs for managed company, mark reviewed, reject with reason, download, bulk download ZIP. |
| `app/Http/Requests/ClientDocumentRequest.php` | Validation: file max 10MB, allowed mimetypes, category enum |
| `app/Policies/ClientDocumentPolicy.php` | Client can CRUD own; Partner can read/review for managed companies |
| `resources/scripts/admin/views/documents/ClientDocuments.vue` | Client upload UI: drag-and-drop zone, category selector, file list with status badges |
| `resources/scripts/partner/views/clients/ClientDocumentsTab.vue` | Partner review UI: document list per client, review/reject actions, preview modal |

#### DB changes

**Migration:** `database/migrations/2026_02_15_000400_create_client_documents_table.php`

```php
Schema::create('client_documents', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('company_id');
    $table->unsignedInteger('uploaded_by');
    $table->unsignedBigInteger('partner_id')->nullable();
    $table->enum('category', ['invoice', 'receipt', 'contract', 'bank_statement', 'other'])->default('other');
    $table->string('original_filename', 255);
    $table->string('file_path', 500);
    $table->unsignedInteger('file_size')->default(0);
    $table->string('mime_type', 100)->nullable();
    $table->enum('status', ['pending_review', 'reviewed', 'rejected'])->default('pending_review');
    $table->unsignedInteger('reviewer_id')->nullable();
    $table->timestamp('reviewed_at')->nullable();
    $table->text('notes')->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
    $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('restrict');
    $table->foreign('partner_id')->references('id')->on('partners')->onDelete('restrict');
    $table->index(['company_id', 'status']);
    $table->index(['partner_id', 'status']);
}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### API endpoints

**Client-facing:**
```
POST   /api/v1/client-documents/upload           → upload()       (multipart form)
GET    /api/v1/client-documents                   → index()        (own company docs)
GET    /api/v1/client-documents/{id}              → show()
DELETE /api/v1/client-documents/{id}              → destroy()      (pending only)
```

**Partner-facing:**
```
GET    /api/v1/partner/clients/{company}/documents         → index()
GET    /api/v1/partner/clients/{company}/documents/{id}    → show()
POST   /api/v1/partner/clients/{company}/documents/{id}/review  → markReviewed()
POST   /api/v1/partner/clients/{company}/documents/{id}/reject  → reject()
GET    /api/v1/partner/clients/{company}/documents/download-all → bulkDownload()
```

#### Test plan
1. Feature test: `tests/Feature/ClientDocumentTest.php`
   - Upload PDF → stored, status = pending_review
   - Partner reviews → status = reviewed
   - Client cannot delete reviewed doc → 403
   - File size > 10MB → 422
   - Partner without access to company → 403
2. Manual: Upload 3 docs from client, switch to partner view → see all 3 with review buttons

---

### P8-02: Deadline Tracking Dashboard

**Why:** Accounting bureaus managing 10–130+ clients need centralized deadline tracking.
Key deadlines: VAT returns (25th monthly), MPIN payroll (10th monthly), CIT advance (15th monthly),
annual financial statements (March 15). Missing a deadline = fines for the client.

**Effort:** 2–3 days

#### Files to create

| File | Description |
|------|-------------|
| `app/Models/Deadline.php` | Model: id, company_id, partner_id (nullable), title, title_mk, description, deadline_type (vat_return/mpin/cit_advance/annual_fs/custom), due_date, status (upcoming/due_today/overdue/completed), completed_at, completed_by, reminder_days_before (JSON array: [7, 3, 1]), last_reminder_sent_at, is_recurring, recurrence_rule (monthly_25/monthly_10/monthly_15/annual_03_15/custom), metadata (JSON). |
| `app/Http/Controllers/V1/Partner/DeadlineController.php` | Partner dashboard: list all deadlines across clients, filter by type/status/date range, mark complete, create custom deadlines. |
| `app/Http/Controllers/V1/Admin/DeadlineController.php` | Company view: own deadlines only. |
| `app/Console/Commands/GenerateRecurringDeadlines.php` | Monthly cron: generates next month's deadline instances from recurring rules for all active companies. Pattern: `CheckCertificateExpiry.php`. |
| `app/Console/Commands/SendDeadlineReminders.php` | Daily cron at 09:00: sends email/notification for deadlines due within reminder window. |
| `app/Notifications/DeadlineReminderNotification.php` | Email + database notification with deadline details, days remaining. |
| `database/seeders/DeadlineTemplatesSeeder.php` | Seeds recurring deadlines: ДДВ пријава (25th), МПИН (10th), Аконтација ДД (15th), Годишна сметка (Mar 15). |
| `resources/scripts/partner/views/deadlines/DeadlinesDashboard.vue` | Calendar/list view with color-coded status, company grouping, quick-complete actions. KPI cards: overdue count, due this week, due this month. |
| `resources/scripts/admin/views/deadlines/CompanyDeadlines.vue` | Company-level deadline list (subset of partner view). |

#### DB changes

**Migration:** `database/migrations/2026_02_15_000500_create_deadlines_table.php`

```php
Schema::create('deadlines', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('company_id');
    $table->unsignedBigInteger('partner_id')->nullable();
    $table->string('title', 200);
    $table->string('title_mk', 200)->nullable();
    $table->text('description')->nullable();
    $table->enum('deadline_type', ['vat_return', 'mpin', 'cit_advance', 'annual_fs', 'custom'])->default('custom');
    $table->date('due_date');
    $table->enum('status', ['upcoming', 'due_today', 'overdue', 'completed'])->default('upcoming');
    $table->timestamp('completed_at')->nullable();
    $table->unsignedInteger('completed_by')->nullable();
    $table->json('reminder_days_before')->default('[7, 3, 1]');
    $table->timestamp('last_reminder_sent_at')->nullable();
    $table->boolean('is_recurring')->default(false);
    $table->string('recurrence_rule', 50)->nullable();
    $table->json('metadata')->nullable();
    $table->timestamps();
    $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
    $table->foreign('partner_id')->references('id')->on('partners')->onDelete('restrict');
    $table->index(['company_id', 'due_date', 'status']);
    $table->index(['partner_id', 'due_date', 'status']);
    $table->index(['status', 'due_date']);
}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### API endpoints

**Partner:**
```
GET    /api/v1/partner/deadlines                  → index()       (all clients, filterable)
GET    /api/v1/partner/deadlines/summary           → summary()     (KPIs: overdue, due this week, etc.)
POST   /api/v1/partner/deadlines                   → store()       (custom deadline)
PATCH  /api/v1/partner/deadlines/{id}              → update()
POST   /api/v1/partner/deadlines/{id}/complete     → complete()
DELETE /api/v1/partner/deadlines/{id}              → destroy()     (custom only)
```

**Company:**
```
GET    /api/v1/deadlines                           → index()       (own company)
POST   /api/v1/deadlines/{id}/complete             → complete()
```

#### Scheduled commands
```php
// routes/console.php or Kernel.php
$schedule->command('deadlines:generate-recurring')->monthlyOn(1, '00:00');
$schedule->command('deadlines:send-reminders')->dailyAt('09:00');
```

#### Test plan
1. Feature test: `tests/Feature/DeadlineTrackingTest.php`
   - Generate recurring deadlines → creates correct due dates (25th for VAT, 10th for MPIN)
   - Mark complete → status = completed, completed_at set
   - Overdue detection: due_date < today AND status != completed → auto-update to overdue
   - Reminder notification: deadline due in 3 days → notification sent
   - Partner sees deadlines for all managed companies
2. Unit test: Recurrence rule parser generates correct dates

---

### P8-03: Bulk Reporting Across Clients

**Why:** Partners managing 50+ clients need aggregated views — not one-by-one reports.
Current reports are per-company. Partners need: multi-company comparison, consolidated
balance sheet, bulk export.

**Effort:** 2–3 days

#### Files to create

| File | Description |
|------|-------------|
| `app/Services/BulkReportingService.php` | Aggregation service: takes array of company IDs + date range, calls `IfrsAdapter` per company, returns combined data. Methods: `multiCompanyReport()`, `consolidatedReport()`, `comparativeReport()`. |
| `app/Http/Controllers/V1/Partner/BulkReportController.php` | Endpoints for multi-company, consolidated, and export reports. Validates partner access to all requested companies. |
| `resources/scripts/partner/views/reports/BulkReports.vue` | UI: company multi-select, date range, report type selector, results table with per-company breakdown. Export buttons (CSV, PDF). |

#### Files to modify

| File | Change |
|------|--------|
| `app/Http/Controllers/V1/Partner/PartnerAccountingReportsController.php` | Add `multiCompanyTrialBalance()`, `multiCompanyProfitLoss()` methods that delegate to BulkReportingService |
| `resources/scripts/partner/partner-router.js` | Add route: `{ path: 'reports', name: 'partner.reports', component: BulkReports }` |
| `routes/api.php` | Add bulk report routes in partner group |

#### DB changes
None — uses existing IFRS tables. Aggregation is compute-only.

#### API endpoints

```
POST   /api/v1/partner/reports/multi-company     → multiCompany()
  Body: { company_ids: [1, 2, 3], from_date, to_date, report_type: "trial_balance|profit_loss|balance_sheet" }
  Response: { companies: [ { id, name, report_data: {...} }, ... ] }

POST   /api/v1/partner/reports/consolidated      → consolidated()
  Body: { company_ids: [1, 2, 3], from_date, to_date }
  Response: { consolidated: { total_assets, total_liabilities, total_revenue, total_expenses, ... } }

POST   /api/v1/partner/reports/export            → export()
  Body: { company_ids, from_date, to_date, format: "csv|pdf" }
  Response: File download
```

#### Test plan
1. Feature test: `tests/Feature/BulkReportingTest.php`
   - Multi-company: 3 companies with known balances → returns separate reports
   - Consolidated: sums match individual report totals
   - Partner without access to company → 403 for that company
   - Empty company list → 422
2. Performance: 20 companies report < 10 seconds response time

---

## Phase 9 — Data Accuracy

### P9-01: NBRM Official Exchange Rates

**Why:** Current implementation uses Frankfurter (ECB) for exchange rates. For legal compliance,
Macedonian businesses should use official NBRM (National Bank of Macedonia) rates.
NBRM publishes daily rates at `https://www.nbrm.mk/kursna_lista.nspx`.

**Effort:** 1–2 days

#### Files to create

| File | Description |
|------|-------------|
| `app/Services/NbrmExchangeRateService.php` | NBRM API client. Fetches daily exchange rate list from NBRM. Parses XML/JSON response. Caches rates for 24 hours (rates published once daily). Methods mirror `FrankfurterExchangeRateService.php`: `getRate()`, `fetchRate()`, `getMultipleRates()`, `getSupportedCurrencies()`. Base currency: MKD. |
| `app/Contracts/ExchangeRateProvider.php` | **New interface.** Methods: `getRate(string $from, string $to, ?Carbon $date): float`, `getMultipleRates(array $pairs): array`, `getSupportedCurrencies(): array`. Both Frankfurter and NBRM implement this. |

#### Files to modify

| File | Change |
|------|--------|
| `app/Services/FrankfurterExchangeRateService.php` | Implement `ExchangeRateProvider` interface (no logic changes needed, just add `implements ExchangeRateProvider`) |
| `app/Services/CurrencyExchangeService.php` (line ~138) | Replace hardcoded `fetchFromExternalAPI()` with provider injection. Constructor accepts `ExchangeRateProvider`. Default: resolve from config. |
| `app/Providers/AppServiceProvider.php` | Bind `ExchangeRateProvider` → `NbrmExchangeRateService` or `FrankfurterExchangeRateService` based on config |
| `config/mk.php` | Add: `'exchange_rate_provider' => env('EXCHANGE_RATE_PROVIDER', 'nbrm')` with options `nbrm` and `frankfurter` |

#### DB changes
None — `exchange_rate_logs` table already exists and supports any provider.

#### API changes
None — existing exchange rate endpoints work with either provider transparently.

#### Environment variables
```
EXCHANGE_RATE_PROVIDER=nbrm    # or 'frankfurter' as fallback
NBRM_API_URL=https://www.nbrm.mk/kursna_lista.nspx
NBRM_CACHE_TTL=86400          # 24 hours (rates are daily)
```

#### Test plan
1. Unit test: `tests/Unit/NbrmExchangeRateServiceTest.php`
   - Mock NBRM response → parses EUR/MKD rate correctly
   - Cache hit → no API call on second request within 24h
   - NBRM down → falls back to cached rate or throws exception
2. Integration: Set provider to NBRM → create invoice in EUR → verify MKD conversion uses NBRM rate
3. Feature test: Verify both providers implement `ExchangeRateProvider` interface

---

## Phase 10 — Mobile & Hardware

### P10-01: PWA Mobile Experience

**Why:** 32% digital skills rate in Macedonia demands radically simple mobile access.
No competitor has mobile. A PWA (Progressive Web App) gives install-to-homescreen,
offline support, and push notifications without a native app.

**Effort:** 3–5 days

#### Files to create

| File | Description |
|------|-------------|
| `public/manifest.json` | PWA manifest: `name: "Facturino"`, `short_name: "Facturino"`, `start_url: "/admin/dashboard"`, `display: "standalone"`, `lang: "mk"`, `theme_color: "#1f2937"`, icons at 192/512px |
| `public/service-worker.js` | Workbox-based SW: cache-first for static assets (JS/CSS/images), network-first for API calls, offline fallback page, background sync for failed POST requests |
| `resources/scripts/admin/components/mobile/MobileMenuToggle.vue` | Hamburger menu with `data-cy="mobile-menu-toggle"`, slide-out navigation with `data-cy="mobile-menu"` |
| `resources/scripts/admin/components/mobile/PwaInstallPrompt.vue` | Listens to `beforeinstallprompt`, shows install banner with `data-cy="pwa-install"` |
| `resources/scripts/admin/components/mobile/OfflineBanner.vue` | Detects `navigator.onLine` changes, shows banner with `data-cy="offline-message"` |
| `resources/scripts/admin/components/mobile/PullToRefresh.vue` | Touch gesture: pull down to refresh current route data |

#### Files to modify

| File | Change |
|------|--------|
| `vite.config.js` | Add `vite-plugin-pwa` to plugins array with manifest and workbox config |
| `resources/views/app.blade.php` | Add `<link rel="manifest" href="/manifest.json">`, `<meta name="theme-color" content="#1f2937">` |
| `resources/scripts/admin/layouts/LayoutBasic.vue` | Import and mount MobileMenuToggle, OfflineBanner, PwaInstallPrompt |
| `resources/scripts/admin/layouts/partials/TheSiteHeader.vue` | Add responsive hamburger button for mobile, hide desktop nav on small screens |
| `package.json` | Add dev dependency: `vite-plugin-pwa` |

#### DB changes
None.

#### Test plan
1. Existing test suite: `tests/visual/mobile-pwa.spec.js` (557 lines already written)
   - Run Playwright mobile tests — they validate all `data-cy` selectors
   - PWA manifest detection, service worker registration, install prompt
   - Mobile navigation, touch gestures, offline functionality
   - Performance: FCP < 1.5s, DOM Content Loaded < 2s
2. Manual: Open app on Android Chrome → verify "Add to Home Screen" prompt appears
3. Manual: Enable airplane mode → verify offline banner and cached pages still load

---

### P10-02: Fiscal Device Integration (DEFERRED)

**Why:** Macedonian fiscal devices (Daisy FX 1300, Synergy PF-500, Expert SX, Severec)
require direct hardware protocol integration. UJP has not published standardized device
APIs — each vendor has proprietary protocols. Current implementation handles DataMatrix
barcode scanning from printed receipts only.

**Effort:** 5–7 days (when specs become available)

**Status:** DEFERRED until UJP publishes device API specifications or vendor partnerships
are established.

#### Preliminary design (for future implementation)

| File | Description |
|------|-------------|
| `app/Contracts/FiscalDeviceDriver.php` | Interface: `connect()`, `sendInvoice()`, `getStatus()`, `getLastReceipt()`, `disconnect()` |
| `Modules/Mk/Services/FiscalDevices/DaisyDriver.php` | Daisy FX 1300 protocol implementation |
| `Modules/Mk/Services/FiscalDevices/SynergyDriver.php` | Synergy PF-500 protocol implementation |
| `Modules/Mk/Services/FiscalDevices/FiscalDeviceManager.php` | Factory: resolves driver by device type config |
| `app/Http/Controllers/V1/Admin/FiscalDeviceController.php` | Register device, send invoice, check status, reconcile daily totals |

#### DB changes (future)
```
fiscal_devices: id, company_id, device_type, serial_number, ip_address, port, is_active
fiscal_receipts: id, company_id, device_id, receipt_number, amount, vat_amount, fiscal_id, raw_data, created_at
```

#### Blocking dependency
- UJP device API specification publication
- Vendor SDK access (Daisy, Synergy)
- Physical test devices for development

---

## Backlog / Future Considerations

These items surfaced during the audit but are not gaps — they are enhancement opportunities:

| ID | Item | Notes |
|----|------|-------|
| NX-20 | UJP e-Tax portal filing (VAT/CIT) | Beyond e-Faktura, direct tax return submission |
| NX-21 | Central Register annual FS submission | Automate March 15 filing |
| NX-22 | WooCommerce integration | Install `automattic/woocommerce-api-php`, ticket WOO-01 |
| NX-23 | Macedonian Credit Bureau integration | Receivables risk scoring |
| NX-24 | Micro-business CIT exemptions (3M/6M) | Tax engine enhancement |
| NX-25 | Pillar Two QDMTT (15% minimum tax) | Only affects qualifying multinationals |
| NX-26 | SAF-T specific export format | When UJP publishes SAF-T spec |
| NX-27 | SEPA Direct Debit for subscriptions | After Oct 2025 SEPA launch |
| NX-28 | Native mobile app (React Native) | If PWA proves insufficient |

---

## Summary

| Phase | Tickets | Est. Days | Key Outcome |
|-------|---------|-----------|-------------|
| P7 | 5 tickets | 9–13 | Full legal compliance (VAT, payroll, e-Faktura) |
| P8 | 3 tickets | 6–9 | Bureau distribution channel unblocked |
| P9 | 1 ticket | 1–2 | Official exchange rates for MKD |
| P10 | 2 tickets | 8–12 | Mobile access + fiscal device readiness |
| **Total** | **11 tickets** | **24–36 days** | **Codebase → 95%+ market research coverage** |
