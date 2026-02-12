# Facturino v1 — Gap Closure Roadmap

> Generated 2026-02-11 from Opus 4.6 market research audit.
> Continues from existing phases P0–P6 (see `CODEBASE_ROADMAP.md`).

---

## Overview

| Phase | Theme | Tickets | Est. Effort | Priority | Status |
|-------|-------|---------|-------------|----------|--------|
| **P7** | Compliance Critical | P7-01 … P7-05 | 9–13 days | MUST — blocks revenue / legal compliance | ✅ COMPLETED |
| **P8** | Bureau Distribution | P8-01 … P8-03 | 6–9 days | SHOULD — blocks accountant GTM channel | ✅ COMPLETED |
| **P9** | Data Accuracy | P9-01 | 1–2 days | SHOULD — replaces interim solution | ✅ COMPLETED |
| **P10** | Mobile & Hardware | P10-01 … P10-02 | 8–12 days | COULD — competitive differentiator | ✅ COMPLETED |
| **P11** | Integration Quick Wins | P11-01 … P11-03 | 4–6 days | MUST — bank coverage + marketing | ✅ COMPLETED |
| **P12** | External API Integrations | P12-01 … P12-04 | 13–17 days | SHOULD — new integration channels | ✅ COMPLETED |
| **P13** | Bank & Auth Expansion | P13-01 … P13-03 | 11–15 days | SHOULD — requires sandbox credentials | Pending |
| **P14** | Hardware & Deferred | P14-01 | 7–14 days | COULD — blocked by UJP specs | Deferred |

**Phases P7–P12 completed: 41–59 days** ✅
**Remaining effort (P13–P14): 18–29 days**

### Dependency Graph

```
P7-01 (10% VAT)            ─── ✅ COMPLETED
P7-02 (Incoming e-invoice)  ─── ✅ COMPLETED
P7-03 (Leave management)    ─── ✅ COMPLETED
P7-04 (Overtime)             ─── ✅ COMPLETED
P7-05 (Contribution caps)   ─── ✅ COMPLETED
P8-01 (Client doc portal)   ─── ✅ COMPLETED
P8-02 (Deadline tracking)   ─── ✅ COMPLETED
P8-03 (Bulk reporting)      ─── ✅ COMPLETED
P9-01 (NBRM rates)          ─── ✅ COMPLETED
P10-01 (PWA mobile)         ─── ✅ COMPLETED
P10-02 (Fiscal devices)     ─── ✅ COMPLETED (stubs — awaiting UJP device API specs)
P11-01 (Bank CSV parsers)   ─── ✅ COMPLETED
P11-02 (CaSys refund)       ─── ✅ COMPLETED
P11-03 (Integrations page)  ─── ✅ COMPLETED
P12-01 (Central Registry)   ─── ✅ COMPLETED
P12-02 (Viber notifications)─── ✅ COMPLETED
P12-03 (WooCommerce sync)   ─── ✅ COMPLETED
P12-04 (Incoming e-invoice) ─── ✅ COMPLETED
P13-01 (Komercijalna PSD2)  ─── needs sandbox credentials
P13-02 (UJP e-Invoice API)  ─── needs UJP API credentials
P13-03 (eID/OneID login)    ─── needs eID registration
P14-01 (Fiscal devices)     ─── deferred until UJP publishes device API specs
```

---

## Phase 7 — Compliance Critical ✅ COMPLETED

### P7-01: Add 10% Restaurant VAT Rate ✅

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

### P7-02: Incoming E-Invoice Acceptance Workflow ✅

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

### P7-03: Leave Management (Annual, Sick, Maternity) ✅

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

### P7-04: Overtime Calculations (135–150%) ✅

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

### P7-05: Minimum/Maximum Contribution Base Caps ✅

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

## Phase 8 — Bureau Distribution (GTM-Critical) ✅ COMPLETED

### P8-01: Client Document Upload Portal ✅

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

### P8-02: Deadline Tracking Dashboard ✅

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

### P8-03: Bulk Reporting Across Clients ✅

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

## Phase 9 — Data Accuracy ✅ COMPLETED

### P9-01: NBRM Official Exchange Rates ✅

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

## Phase 10 — Mobile & Hardware ✅ COMPLETED

### P10-01: PWA Mobile Experience ✅

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

### P10-02: Fiscal Device Integration (DEFERRED) ✅ (stubs completed)

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
| NX-22 | ~~WooCommerce integration~~ | ✅ Completed in P12-03 |
| NX-23 | Macedonian Credit Bureau integration | Receivables risk scoring |
| NX-24 | Micro-business CIT exemptions (3M/6M) | Tax engine enhancement |
| NX-25 | Pillar Two QDMTT (15% minimum tax) | Only affects qualifying multinationals |
| NX-26 | SAF-T specific export format | When UJP publishes SAF-T spec |
| NX-27 | SEPA Direct Debit for subscriptions | After Oct 2025 SEPA launch |
| NX-28 | Native mobile app (React Native) | If PWA proves insufficient |

---

## Phase 11 — Integration Quick Wins ✅ COMPLETED

> Bank coverage expansion, payment completeness, and marketing visibility.
> All items use publicly exposed APIs — zero vendor permission required.

### P11-01: Add 6 Missing Bank CSV Parsers ✅

**Why:** Current CSV import supports 3 banks (NLB, Stopanska, Komercijalna = ~55% market share).
Adding 6 more banks covers ~95%+ of Macedonian businesses. Each parser is a single PHP file
extending the existing `AbstractCsvParser` — pure copy-paste pattern.

**Effort:** 1–2 days

#### Files to create

| File | Description |
|------|-------------|
| `app/Services/Banking/Parsers/SparkasseCsvParser.php` | Шпаркасе Банка (12.7% market share). Extends `AbstractCsvParser`. |
| `app/Services/Banking/Parsers/HalkCsvParser.php` | Халк Банка (12.3%). Extends `AbstractCsvParser`. |
| `app/Services/Banking/Parsers/ProCreditCsvParser.php` | ПроКредит Банка (5.2%). Extends `AbstractCsvParser`. |
| `app/Services/Banking/Parsers/TtkCsvParser.php` | ТТК Банка (3.8%). Extends `AbstractCsvParser`. |
| `app/Services/Banking/Parsers/SilkRoadCsvParser.php` | Силк Роуд Банка (2.1%). Extends `AbstractCsvParser`. |
| `app/Services/Banking/Parsers/OhridskaCsvParser.php` | Охридска Банка (1.9%). Extends `AbstractCsvParser`. |

Each parser implements: `getBankCode()`, `getBankName()`, `getDelimiter()`, `getEncoding()`,
`getRequiredColumns()`, `canParse()`, `mapRecord()`.

#### Files to modify

| File | Change |
|------|--------|
| `app/Services/Banking/Parsers/CsvParserFactory.php` | Register 6 new parsers in `$parsers` array (before GenericCsvParser) |

#### Reuse

- **Template:** `app/Services/Banking/Parsers/KomercijalnaCsvParser.php` (most recent, cleanest)
- **Base class:** `app/Services/Banking/Parsers/AbstractCsvParser.php`
- **Factory:** `app/Services/Banking/Parsers/CsvParserFactory.php` (`getSupportedBanks()` auto-populates UI)

#### DB changes
None.

#### Vue changes
None — banking dashboard bank selector auto-populates from `CsvParserFactory::getSupportedBanks()`.

#### Prerequisites
Need sample CSV export from each bank to identify: column names (Macedonian/English),
delimiter (comma vs semicolon), encoding (UTF-8 vs Windows-1251), date format, amount format.

#### Test plan
1. Unit test per parser with 5-10 row CSV fixture
2. Test `canParse()` auto-detection returns correct parser for each bank format
3. Test `mapRecord()` returns normalized transaction data with correct debit/credit signs

---

### P11-02: CaSys/cPay Refund Method ✅

**Why:** CaSys payment gateway integration is complete for checkout, webhooks, and subscriptions
but has no refund capability. The CaSys API supports refunds via the same POST endpoint with
`tran_type=REFUND` and HMAC signing.

**Effort:** 0.5 day

#### Files to modify

| File | Change |
|------|--------|
| `Modules/Mk/Services/CpayDriver.php` | Add `refund(string $transactionId, int $amount): array` method. Same POST endpoint, `tran_type=REFUND`, HMAC-signed. |
| `Modules/Mk/Billing/Controllers/CpayWebhookController.php` | Add `REFUND` event type handling in webhook switch statement. Update payment record status. |

#### Vue changes

| Component | Change |
|-----------|--------|
| Payment detail dropdown (invoice view) | Add "Refund Payment" action to existing `BaseDropdown`. Follow `FiscalDeviceIndexDropdown.vue` pattern. |
| New: `RefundConfirmationModal.vue` | Confirmation modal: shows original amount, refund amount input, reason dropdown, warning text. Uses `BaseModal` + `BaseInput`. |

#### Reuse
- **Reference:** [`c0nevski/CaSys-php-implementation`](https://github.com/c0nevski/CaSys-php-implementation) (GitHub) for refund flow

#### DB changes
None — existing payment records table supports status updates.

#### Test plan
1. Unit test: `CpayDriver::refund()` generates correct HMAC signature and POST body
2. Feature test: Webhook with `tran_type=REFUND` updates payment status
3. Manual: Issue test refund via CaSys sandbox

---

### P11-03: Marketing Website — Integrations Page ✅

**Why:** Facturino's marketing site has no integrations page. Potential customers cannot see what
banks, government systems, and e-commerce platforms are supported. This is the #1 way SaaS
companies signal maturity and reduce sales friction.

**Effort:** 2–3 days

#### Files to create

| File | Description |
|------|-------------|
| `website/src/app/[locale]/integrations/page.tsx` | Full integrations page with 5 sections (see below). Inline `copy` object with all 4 locales (mk, sq, tr, en). |

#### Page sections

**Section 1 — Hero:** Gradient background, headline "Connected to Everything Macedonian Businesses Need",
subtitle "9 banks, government systems, e-commerce, and more", CTA button, animated logo strip.

**Section 2 — Category Grid (5 cards):**

| Category | Icon | Items |
|----------|------|-------|
| Banking & Payments | BanknotesIcon | 9 MK banks (NLB, Stopanska, Komercijalna, Шпаркасе, Халк, ПроКредит, ТТК, Силк Роуд, Охридска), PSD2 Open Banking, CaSys/cPay, Paddle, MT940 & CSV |
| Government & Tax | BuildingLibraryIcon | UJP e-Faktura (QES-signed), DDV-04 VAT returns, NBRM exchange rates, Central Registry lookup, KIBS certificates |
| E-Commerce | ShoppingCartIcon | WooCommerce order sync, "Coming soon": Shopify, Magento, Ananas.mk |
| Notifications | ChatBubbleIcon | Viber Business, Email (SMTP/Mailgun/SES), "Coming soon": SMS |
| AI & Automation | SparklesIcon | AI insights (GPT-4/Claude/Gemini), OCR scanning, intelligent import, fiscal receipt DataMatrix |

**Section 3 — Comparison Table:** "With Facturino" (green checks) vs "Without Facturino" (red X).
Reuse pattern from `e-faktura/page.tsx`.

**Section 4 — Status Timeline:** LIVE (green) / COMING SOON (yellow) / PLANNED (gray).
Reuse timeline pattern from `e-faktura/page.tsx`.

**Section 5 — Bottom CTA:** "Ready to Connect Your Business?", two buttons.

#### Files to modify

| File | Change |
|------|--------|
| `website/src/i18n/dictionaries.ts` | Add `nav.integrations` key to all 4 locale objects (mk: "Интеграции", sq: "Integrimet", tr: "Entegrasyonlar", en: "Integrations") |
| `website/src/components/Navbar.tsx` | Add "Integrations" link after "Features" in nav links array |
| `website/src/components/MobileMenu.tsx` | Add "Integrations" link to mobile menu |
| `website/src/components/Footer.tsx` | Add "Integrations" link to footer product section |
| `website/public/sitemap.xml` | Add `<url>` blocks for `/mk/integrations`, `/sq/integrations`, `/tr/integrations`, `/en/integrations` with hreflang alternates |

#### Assets needed
- Bank logos: already have `bank_logos.webp` in `website/public/assets/images/`
- Government logos: UJP, NBRM, KIBS (source from official websites, convert to WebP)
- Category icons: Heroicons (already used throughout)

#### SEO
```
title: "Integrations | Facturino"
description: "Connect Facturino to 9 Macedonian banks, UJP e-Faktura, NBRM exchange rates, WooCommerce, Viber, and more."
```

#### Test plan
1. Verify page loads at all 4 locale URLs: `/mk/integrations`, `/sq/integrations`, `/tr/integrations`, `/en/integrations`
2. All nav links, footer links, and sitemap entries point to correct URLs
3. Mobile responsive: all sections stack correctly on mobile
4. `npm run build` passes without errors

---

## Phase 12 — External API Integrations ✅ COMPLETED

> New integrations using publicly exposed APIs. Self-service registration only.

### P12-01: Central Registry Company Auto-Lookup (crm.com.mk) ✅

**Why:** When creating a customer/vendor, Macedonian accountants manually look up company data
(EMBS, tax ID, legal name, address) on crm.com.mk, then type it into the form. Auto-lookup
eliminates manual entry errors and saves 2-3 minutes per customer.

**Effort:** 3 days

#### Files to create

| File | Description |
|------|-------------|
| `app/Services/CentralRegistryService.php` | HTTP client for crm.com.mk public search. `lookup(string $query): array` — searches by company name or EMBS. Parses HTML response with DOMDocument + XPath. Returns: EMBS, legal name, tax ID (EDB), address, city, activity code, status. 5-minute cache per query. Rate-limited: max 2 req/sec. |
| `app/Http/Controllers/V1/Admin/CompanyLookupController.php` | `lookup(Request $request)` — validates query, calls service, returns JSON. |
| `resources/scripts/admin/composables/useCentralRegistryLookup.js` | Vue composable: debounced search (300ms), result formatting, keyboard navigation (arrow keys + enter). |

#### Files to modify

| File | Change |
|------|--------|
| `routes/api.php` | Add `GET /api/v1/company-lookup?q={query}` inside authenticated group |
| Customer create/edit Vue component | Add search bar above form: "Search Central Registry". When country=MK, show typeahead. Selecting a result auto-fills: company name, tax ID, address, city, activity code. Green checkmark badge on auto-filled fields. |

#### DB changes
None.

#### Vue wireframe

```
┌─── Search Central Registry ──────────┐
│ 🔍 Type company name or EMBS...      │
│  ┌─────────────────────────────────┐  │
│  │ 1234567 │ Фактурино ДООЕЛ│Скопје│  │
│  │ 1234568 │ Фактурино ДОО  │Битола│  │
│  └─────────────────────────────────┘  │
└───────────────────────────────────────┘
Company Name: [Фактурино ДООЕЛ     ] ✓ Central Registry
Tax ID (EDB): [4030012345678       ] ✓ Central Registry
Address:      [ул. Македонија 12   ] ✓ Central Registry
```

#### API
No auth required — crm.com.mk is public data (Open Government Partnership).

#### Test plan
1. Unit test: Mock HTML response → service parses EMBS, name, address correctly
2. Feature test: `GET /api/v1/company-lookup?q=facturino` returns structured JSON
3. Unit test: crm.com.mk unreachable → graceful fallback (empty result, no error)
4. Manual: Type real company name → verify auto-fill works

---

### P12-02: Viber Business Notifications ✅

**Why:** Viber is the #1 messaging app in Macedonia (far bigger than WhatsApp). Invoice
notifications and payment reminders via Viber have much higher open rates than email.
No Macedonian accounting software offers this.

**Effort:** 3–4 days

#### Package
`composer require alserom/viber-php` — needs NX ticket per whitelist rules (or add to whitelist)

#### Files to create

| File | Description |
|------|-------------|
| `app/Services/ViberNotificationService.php` | Wraps `alserom/viber-php`. Methods: `sendMessage(string $phone, string $text)`, `sendInvoiceNotification(Invoice $invoice)`, `sendPaymentConfirmation(Payment $payment)`, `sendOverdueReminder(Invoice $invoice)`. |
| `app/Notifications/Channels/ViberChannel.php` | Custom Laravel notification channel. Implements `send()` method that delegates to ViberNotificationService. |
| `app/Notifications/ViberInvoiceSent.php` | Laravel notification: "Invoice #INV-2024-042 for MKD 12,500 has been sent" |
| `app/Notifications/ViberPaymentReceived.php` | Laravel notification: "Payment of MKD 12,500 received for Invoice #INV-2024-042" |
| `app/Notifications/ViberOverdueReminder.php` | Laravel notification: "Invoice #INV-2024-042 for MKD 12,500 is overdue by 7 days" |

#### Files to modify

| File | Change |
|------|--------|
| `config/mk.php` | Add `viber` section: `auth_token`, `sender_name` (default "Facturino"), `sender_avatar` (logo URL) |
| `routes/api.php` | Add `POST /api/v1/webhooks/viber` (CSRF exempt) for Viber webhook callbacks |
| `resources/scripts/admin/views/settings/NotificationsSetting.vue` | Add "Viber Notifications" card with: master toggle, auth token input (masked), sender name input, "Test Connection" button, per-event toggles (invoice sent, payment received, overdue reminder with days config) |
| Customer create/edit Vue component | Add "Viber Phone" field next to existing phone. Helper text: "Used for invoice delivery notifications" |
| Invoice send modal (`SendInvoiceModal.vue`) | Add "Send via Viber" checkbox alongside existing email option |

#### Vue wireframe (Settings)

```
┌── Viber Notifications ───────────────┐
│ Send invoice notifications via Viber  │
├───────────────────────────────────────┤
│ Enable Viber     ○───────●  ON       │
│                                       │
│ Auth Token: [••••••••••••••] 👁️       │
│ Sender Name: [Facturino        ]     │
│ [Test Connection]  ✅ Connected      │
│                                       │
│ ── Notification Events ──            │
│ Invoice Sent        ○──●  ON         │
│ Payment Received    ○──●  ON         │
│ Overdue Reminder    ●──○  OFF        │
│   Remind after: [7 days ▼]          │
│                                       │
│                      [Save Settings] │
└───────────────────────────────────────┘
```

#### API
- Registration: Self-service at `https://partners.viber.com/` (free)
- Auth: Bearer token
- Format: REST API with JSON payloads

#### Env
```
VIBER_AUTH_TOKEN=
VIBER_SENDER_NAME=Facturino
VIBER_SENDER_AVATAR=https://app.facturino.mk/logo.png
```

#### Test plan
1. Unit test: `ViberNotificationService::sendMessage()` calls correct API endpoint with correct payload
2. Feature test: Viber webhook endpoint handles incoming message events
3. Unit test: Notification channel formats message correctly per notification type
4. Manual: Send test Viber message from settings page

---

### P12-03: WooCommerce Order-to-Invoice Sync ✅

**Why:** 2,160+ Macedonian online stores use WooCommerce. Auto-syncing orders into Facturino
as invoices, with DDV calculations and e-Faktura submission, serves thousands of online sellers.
No MK accounting software offers this.

**Effort:** 4–5 days

#### Package
`composer require automattic/woocommerce-api-php` — already in CLAUDE.md whitelist (WOO-01)

#### Files to create

| File | Description |
|------|-------------|
| `app/Services/WooCommerce/WooCommerceClient.php` | Wraps REST API SDK. Connection test, order fetching with pagination, status push. |
| `app/Services/WooCommerce/WooCommerceOrderMapper.php` | Maps WC order → Facturino invoice: products → line items, WC tax classes → MK tax types, customer → Facturino customer (create if new), shipping → line item. |
| `app/Services/WooCommerce/WooCommerceSyncService.php` | Orchestrates pull/push: fetch new orders since last sync, create invoices, update WC order status. Idempotent (tracks `wc_order_id` to prevent duplicates). |
| `app/Jobs/SyncWooCommerceOrdersJob.php` | Queued job: 3 retries, backoff [60, 300, 900]. Calls WooCommerceSyncService. |
| `app/Http/Controllers/V1/Admin/Integration/WooCommerceController.php` | CRUD for connection settings, manual sync trigger, sync history. |
| `app/Models/WooCommerceConnection.php` | Model: company_id, store_url, consumer_key (encrypted), consumer_secret (encrypted), is_active, last_synced_at, sync_frequency, tax_mapping (JSON), default_payment_method_id. |
| `resources/scripts/admin/views/settings/WooCommerceSetting.vue` | Full settings page (see wireframe below). |
| `resources/scripts/admin/stores/woocommerce.js` | Pinia store for WooCommerce state management. |

#### Files to modify

| File | Change |
|------|--------|
| `config/mk.php` | Add `woocommerce` section: `sync_frequencies` array, `default_tax_mapping` |
| `routes/api.php` | Add WooCommerce endpoints inside authenticated group |
| `resources/scripts/admin/stores/global.js` | Add WooCommerce entry to `settingMenu` |
| `resources/scripts/admin/admin-router.js` | Add route for WooCommerce settings page |

#### DB changes

**Migration:** `database/migrations/2026_02_15_001000_create_woocommerce_connections_table.php`

```php
Schema::create('woocommerce_connections', function (Blueprint $table) {
    $table->id();
    $table->unsignedInteger('company_id');
    $table->string('store_url', 500);
    $table->text('consumer_key'); // encrypted
    $table->text('consumer_secret'); // encrypted
    $table->boolean('is_active')->default(true);
    $table->timestamp('last_synced_at')->nullable();
    $table->string('sync_frequency', 20)->default('1h'); // 15m, 1h, 4h, manual
    $table->json('tax_mapping')->nullable(); // { "standard": 18, "reduced": 5 }
    $table->unsignedBigInteger('default_payment_method_id')->nullable();
    $table->unsignedBigInteger('last_order_id')->default(0); // for incremental sync
    $table->timestamps();
    $table->foreign('company_id')->references('id')->on('companies')->onDelete('restrict');
    $table->unique('company_id');
}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

Schema::create('woocommerce_sync_logs', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('connection_id');
    $table->enum('status', ['success', 'partial', 'failed'])->default('success');
    $table->unsignedInteger('orders_fetched')->default(0);
    $table->unsignedInteger('invoices_created')->default(0);
    $table->unsignedInteger('errors')->default(0);
    $table->json('error_details')->nullable();
    $table->timestamps();
    $table->foreign('connection_id')->references('id')->on('woocommerce_connections')->onDelete('cascade');
    $table->index(['connection_id', 'created_at']);
}) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Vue wireframe (Settings)

```
┌── WooCommerce Integration ───────────┐
│ Sync orders from your WooCommerce     │
├───────────────────────────────────────┤
│ ┌── Connection ────────────────────┐  │
│ │ Store URL:    [https://myshop.mk]│  │
│ │ Consumer Key: [ck_••••••••••••]  │  │
│ │ Consumer Sec: [cs_••••••••••••]  │  │
│ │ [Test Connection] ✅ Connected   │  │
│ └──────────────────────────────────┘  │
│                                       │
│ ┌── Sync Settings ─────────────────┐  │
│ │ Auto-sync      ○──●  ON         │  │
│ │ Frequency: [Every hour  ▼]      │  │
│ │ Tax Mapping:                     │  │
│ │   WC "Standard" → [ДДВ 18%  ▼] │  │
│ │   WC "Reduced"  → [ДДВ 5%   ▼] │  │
│ │ Payment: [CaSys/cPay ▼]        │  │
│ └──────────────────────────────────┘  │
│                                       │
│ ┌── Sync History ──────────────────┐  │
│ │ Time     │ Orders │ Status       │  │
│ │ 14:30    │ 3      │ ✅ Success   │  │
│ │ 13:30    │ 0      │ ✅ No new    │  │
│ │ 12:30    │ 1      │ ⚠️ 1 error   │  │
│ └──────────────────────────────────┘  │
│                                       │
│    [Sync Now]        [Save Settings] │
└───────────────────────────────────────┘
```

#### API endpoints

```
GET    /api/v1/integrations/woocommerce              → show()        (connection details)
POST   /api/v1/integrations/woocommerce              → store()       (create/update connection)
DELETE /api/v1/integrations/woocommerce              → destroy()     (disconnect)
POST   /api/v1/integrations/woocommerce/test         → testConnection()
POST   /api/v1/integrations/woocommerce/sync         → syncNow()     (trigger manual sync)
GET    /api/v1/integrations/woocommerce/logs         → logs()        (sync history)
```

#### Test plan
1. Unit test: `WooCommerceOrderMapper` maps WC order JSON to invoice data correctly
2. Unit test: Idempotency — same `wc_order_id` does not create duplicate invoice
3. Feature test: `POST /sync` creates invoices from mock WC API response
4. Feature test: Connection test endpoint validates credentials
5. Manual: Connect real WooCommerce sandbox store → verify orders sync

---

### P12-04: Incoming E-Invoice Workflow (extends P7-02) ✅

**Why:** Mandatory for B2B e-invoicing compliance (Q3 2026). `PollEInvoiceInboxJob.handle()`
is currently an empty no-op. Businesses need to receive, review, accept, and reject supplier
invoices through the UJP portal.

**Effort:** 3–5 days

See P7-02 above for full specification. This ticket covers the implementation
using existing UJP portal credentials.

#### Key additions beyond P7-02

| File | Description |
|------|-------------|
| `resources/scripts/admin/components/modal-components/EInvoicePreviewModal.vue` | Renders UBL XML as human-readable invoice: supplier info, line items table, tax breakdown, totals. Uses existing `BaseModal`. |

#### Vue wireframe (Inbox Tab)

```
[Invoices] [Incoming E-Invoices] [2 new]
──────────────────────────────────────────
[Poll Now 🔄]          Filter: [All ▼]

Sender        │ Invoice# │ Amount  │ ⋮
Status        │ Date     │         │
──────────────┼──────────┼─────────┼──
АД Фирма      │ F-2024-42│ 12,500  │ Review
🔵 RECEIVED   │ 12.02.26 │         │ Accept
              │          │         │ Reject
──────────────┼──────────┼─────────┼──
ДОО Сервис    │ S-1234   │ 5,800   │
🟢 ACCEPTED   │ 11.02.26 │         │
```

---

## Phase 13 — Bank & Authentication Expansion

> Requires external sandbox credentials or registration.

### P13-01: Komercijalna Banka PSD2 Activation

**Why:** Komercijalna Banka has 22% market share — the largest bank in Macedonia.
The gateway implementation already exists (`KomerGateway.php`, 29KB) and is registered
in `Psd2GatewayClient::getGateway()`. Just needs sandbox testing and config wiring.

**Effort:** 3 days

#### Files to modify

| File | Change |
|------|--------|
| `config/mk.php` | Add `komercijalna` section: `client_id`, `client_secret`, `sandbox_base_url`, `production_base_url`, `mtls_cert_path`, `mtls_key_path` |
| `config/mk.php` | Update `banks.supported` array to include `'komercijalna'` |
| `Modules/Mk/Services/KomerGateway.php` | Fix any issues found during sandbox testing |

#### Requires
Self-service sandbox registration at Komercijalna developer portal.

#### Vue changes
None — `ConnectBank.vue` dynamically reads bank providers from config.

#### Test plan
1. Integration test: OAuth2 flow with Komercijalna sandbox credentials
2. Integration test: Fetch accounts, fetch transactions for date range
3. Verify rate limiting (15 req/min) is respected

---

### P13-02: UJP E-Invoice Official API

**Why:** Current e-invoice submission uses portal scraping (`tools/efaktura_upload.php`)
which is fragile — `portalCheckStatus()` returns hardcoded success on non-200 HTTP codes.
UJP launched an official API pilot (Jan 2026, mandatory July 2026). Config already has
`efaktura.mode => 'portal' | 'api'` with API key placeholders.

**Effort:** 1 week

#### Files to create

| File | Description |
|------|-------------|
| `app/Services/EFaktura/UjpApiClient.php` | REST API client for UJP e-Faktura. Methods: `submitInvoice()`, `checkStatus()`, `pollInbox()`, `acceptIncoming()`, `rejectIncoming()`. HMAC/signature auth. |
| `app/Services/EFaktura/UjpPortalClient.php` | Refactor `tools/efaktura_upload.php` into proper Laravel service class. Same portal-scraping logic but testable and injectable. |
| `app/Jobs/CheckEInvoiceDeliveryStatusJob.php` | Queued job: polls delivery status for submitted invoices. Updates `EInvoiceSubmission` status. |

#### Files to modify

| File | Change |
|------|--------|
| `app/Jobs/SubmitEInvoiceJob.php` | Use service class based on `config('mk.efaktura.mode')`: `'api'` → UjpApiClient, `'portal'` → UjpPortalClient |
| `app/Jobs/PollEInvoiceInboxJob.php` | Implement `handle()` using same mode-based dispatch |
| `routes/api.php` | Add `POST /api/v1/webhooks/ujp` (CSRF exempt) for delivery status callbacks |
| `config/mk.php` | Add delivery status check config: poll interval, retry count |

#### Requires
API credentials from UJP developer portal.

#### Vue changes

| Component | Change |
|-----------|--------|
| E-Faktura settings page | Add "Submission Mode" toggle: Portal (legacy) / API (recommended). Show connection status badge (green/red). API key input fields (masked). |

#### Test plan
1. Unit test: `UjpApiClient::submitInvoice()` generates correct request payload
2. Feature test: Mode toggle correctly dispatches to Portal vs API client
3. Integration test (when API available): Submit test invoice → verify EUID received
4. Manual: Toggle mode in settings → verify correct submission path used

---

### P13-03: eID/OneID Login

**Why:** eID is Macedonia's national electronic identity system. Integrating it as an
alternative login method eliminates password management for businesses already using eID
for government services. OneID uses standard OpenID Connect (OIDC).

**Effort:** 1 week

#### Files to create

| File | Description |
|------|-------------|
| `app/Services/Auth/OneIdProvider.php` | Custom Laravel Socialite provider implementing OIDC protocol. Handles: authorize URL generation, callback token exchange, user info fetching. |
| `app/Http/Controllers/Auth/OneIdAuthController.php` | `redirect()` — redirects to OneID login page. `callback()` — handles OIDC callback, finds or creates user, logs in. First-time login: prompt to link to existing account or create new. |

#### Files to modify

| File | Change |
|------|--------|
| `routes/web.php` | Add `GET /auth/oneid/redirect` and `GET /auth/oneid/callback` |
| `config/services.php` | Add OneID OIDC config: `client_id`, `client_secret`, `redirect_uri`, `authorize_url`, `token_url`, `userinfo_url` |
| `resources/scripts/admin/layouts/LayoutLogin.vue` | Add "Sign in with eID" button below email/password form. Government blue style (`bg-blue-700`). Divider: "──── or ────" |

#### Requires
Registration at eid.mk or via eID Easy (eideasy.com) as OIDC wrapper.

#### No new packages needed
Laravel Socialite already installed — just needs custom provider.

#### DB changes

**Migration:** `database/migrations/2026_02_15_001100_add_oneid_to_users.php`

```php
if (!Schema::hasColumn('users', 'oneid_sub')) {
    Schema::table('users', function (Blueprint $table) {
        $table->string('oneid_sub', 100)->nullable()->after('email'); // OIDC subject identifier
        $table->unique('oneid_sub');
    });
}
```

#### Vue wireframe (Login)

```
          FACTURINO
    Sign in to your account

Email:    [                         ]
Password: [                         ]
[Forgot password?]

        [    Sign In    ]

        ──── or ────

 [🏛️ Sign in with eID / OneID]

Don't have an account? [Sign up]
```

#### Test plan
1. Unit test: OneIdProvider generates correct authorize URL
2. Feature test: OIDC callback creates user with `oneid_sub`
3. Feature test: Second login with same `oneid_sub` finds existing user
4. Manual: Click eID button → redirect → callback → logged in

---

## Phase 14 — Hardware & Deferred

> Items blocked by external dependencies.

### P14-01: Fiscal Device Protocols via ErpNet.FP Sidecar

**Why:** 7 fiscal device drivers exist as architecture-only stubs (all `sendInvoice()` throw
"not yet implemented"). Full UI exists (`FiscalDevicesSetting.vue`, `FiscalDeviceController.php`).
Need actual protocol implementation.

**Effort:** 1–2 weeks

**Status:** DEFERRED per P10-02 — awaiting UJP device API specs.

#### Fastest path when unblocked

Deploy [`erpnet/ErpNet.FP`](https://github.com/erpnet/ErpNet.FP) as Docker sidecar.
ErpNet.FP is a .NET application that communicates with all major fiscal printers
(Daisy, David, Expert) and exposes a REST API.

**Alternative:** Fork [`tinodj/mk-fiscal-printer-driver`](https://github.com/tinodj/mk-fiscal-printer-driver)
(PHP-native, tested with Synergy PF-500 + Expert SX). Covers 2-3 devices without Docker.

#### Files to create

| File | Description |
|------|-------------|
| `app/Services/FiscalDevices/ErpNetFpClient.php` | HTTP client to sidecar REST API: `POST /printers/{id}/receipt`, `GET /printers/{id}/status` |
| `Modules/Mk/Services/FiscalDevices/ErpNetFpDriver.php` | Implements existing `FiscalDeviceDriver` interface, wraps ErpNetFpClient |
| `docker/erpnet-fp/Dockerfile` | ErpNet.FP Docker image |

#### Files to modify

| File | Change |
|------|--------|
| `Modules/Mk/Services/FiscalDevices/FiscalDeviceManager.php` | Add `'erpnet-fp' => ErpNetFpDriver::class` to driver registry |
| `config/mk.php` | Add `fiscal_devices.erpnet_fp.base_url` (default `http://erpnet-fp:8001`) |
| `docker/docker-compose-prod.yml` | Add erpnet-fp service |

#### Blocking dependency
- UJP device API specification publication
- Physical test devices or ErpNet.FP emulator mode
- Vendor SDK access (Daisy, Synergy)

---

## Backlog / Future Considerations

These items surfaced during the audit but are not gaps — they are enhancement opportunities:

| ID | Item | Notes |
|----|------|-------|
| NX-20 | UJP e-Tax portal filing (VAT/CIT) | Beyond e-Faktura, direct tax return submission |
| NX-21 | Central Register annual FS submission | Automate March 15 filing |
| NX-22 | ~~WooCommerce integration~~ | ✅ Completed in P12-03 |
| NX-23 | Macedonian Credit Bureau integration | Receivables risk scoring |
| NX-24 | Micro-business CIT exemptions (3M/6M) | Tax engine enhancement |
| NX-25 | Pillar Two QDMTT (15% minimum tax) | Only affects qualifying multinationals |
| NX-26 | SAF-T specific export format | When UJP publishes SAF-T spec |
| NX-27 | SEPA Direct Debit for subscriptions | After Oct 2025 SEPA launch |
| NX-28 | Native mobile app (React Native) | If PWA proves insufficient |
| NX-29 | Ananas.mk marketplace | No public API exists — wait for release |
| NX-30 | MPIN payroll auto-submission | Requires UJP e-Tax portal API |
| NX-31 | Fiscal receipt UJP validation | API key not publicly available |
| NX-32 | DHL Shipping integration | Low priority for accounting app |
| NX-33 | SMS gateway notifications | After Viber integration proves demand |

---

## Summary

| Phase | Tickets | Est. Days | Key Outcome | Status |
|-------|---------|-----------|-------------|--------|
| P7 | 5 tickets | 9–13 | Full legal compliance (VAT, payroll, e-Faktura) | ✅ COMPLETED |
| P8 | 3 tickets | 6–9 | Bureau distribution channel unblocked | ✅ COMPLETED |
| P9 | 1 ticket | 1–2 | Official exchange rates for MKD | ✅ COMPLETED |
| P10 | 2 tickets | 8–12 | Mobile access + fiscal device readiness | ✅ COMPLETED |
| P11 | 3 tickets | 4–6 | Bank coverage 95%+, payment refunds, marketing visibility | ✅ COMPLETED |
| P12 | 4 tickets | 13–17 | Central Registry, Viber, WooCommerce, incoming e-invoice | ✅ COMPLETED |
| P13 | 3 tickets | 11–15 | Komercijalna PSD2, UJP API, eID login | Pending |
| P14 | 1 ticket | 7–14 | Fiscal device protocols (deferred) | Deferred |
| **Total** | **22 tickets** | **59–88 days** | **Full Macedonian integration ecosystem** | **18/22 done** |
