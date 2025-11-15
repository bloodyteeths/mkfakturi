# PHASE 0 — FULL AUDIT, ARCHITECTURE MAP, ROADMAP

## 0.1 Codebase Audit Summary

### Existing Accounts Payable Foundations
- **Migrations present** (Phase 2, completed per `roadmap_nov10.md` and `documentation/deployment/DEPLOYMENT_PHASE2.md`):
  - `suppliers` / `bills` / `bill_items` / `bill_payments` tables with company scoping and FK constraints.
- **Models implemented**:
  - `App\Models\Supplier`: company-scoped, soft-deleted, has-many `bills`, standard `whereCompany` scope.
  - `App\Models\Bill`: soft-deleted, media support, approval-ready, IFRS-posting hooks via `BillObserver`, manual `whereCompany` scope using `request()->header('company')`.
  - `App\Models\BillItem` / `BillPayment`: line items and payments for bills, wired to `Bill` and IFRS adapter.
- **Observers and Policies**:
  - `BillObserver` and `BillPaymentObserver` registered in `App\Providers\AppServiceProvider` (validated via `tools/sanity-check.sh`).
  - `BillPolicy`, `SupplierPolicy` and `ApprovalPolicy` registered in `App\Providers\AuthServiceProvider`.
- **Routes**:
  - `routes/api.php` includes a **disabled section** under the admin v1 group:
    - Commented routes for:
      - `SuppliersController` (`/suppliers`, delete endpoint).
      - `BillsController` (`/bills`, send/mark/download/delete).
      - `BillPaymentsController` (`/bills/{bill}/payments` CRUD).
  - Comment indicates: "Controllers not yet implemented - uncomment when ready".
- **Controllers / Resources / Requests**:
  - No `AccountsPayable` controllers exist yet under `app/Http/Controllers/V1/Admin`.
  - No dedicated `BillRequest` / `SupplierRequest` / `BillPaymentRequest` or `BillResource` / `SupplierResource` yet.
  - Patterns for how to implement them exist via:
    - Invoices: `app/Http/Controllers/V1/Admin/Invoice/InvoicesController.php`, `InvoicesRequest`, `InvoiceResource`.
    - Expenses: `app/Http/Controllers/V1/Admin/Expense/ExpensesController.php`, `ExpenseRequest`, `ExpenseResource`.

### Existing Multi-Tenancy and Security
- **TenantScope trait**:
  - `App\Traits\TenantScope` adds a global `company` scope using `request()->header('company')` and provides `whereCompany`/`withoutCompanyScope` helpers.
  - Used on e.g. `EInvoice`, `ApprovalRequest`, `TaxReturn`, `TaxReportPeriod`, etc.
- **Manual company scoping**:
  - `Bill`, `Expense`, `Supplier` use explicit `scopeWhereCompany` methods referencing `request()->header('company')`.
  - Controllers consistently call `->whereCompany()` for tenant isolation in list endpoints (e.g. `ExpensesController@index`).
- **Approval & IFRS hooks**:
  - `ApprovalRequest` supports `TYPE_BILL` and is tenant-scoped.
  - `App\Domain\Accounting\IfrsAdapter` contains `postBill()` and `postBillPayment()` methods that:
    - Resolve IFRS entity for the company.
    - Post to Accounts Payable and VAT receivable accounts.
    - Mark bills as posted (`posted_to_ifrs`, `ifrs_transaction_id`) with logging.
- **Security considerations**:
  - File uploads in `ExpenseRequest` enforce mime + max size and use MediaLibrary; we should mirror these patterns for bill attachments.
  - Mail is configured via `config/mail.php` and `.env.example` with SMTP, but no inbound mail processing is implemented yet.

### Existing Email and SMTP Configuration
- **Outgoing email**:
  - Mailable classes for invoices, estimates, payments, credit notes (`SendInvoiceMail`, `SendEstimateMail`, `SendPaymentMail`, `SendCreditNoteMail`).
  - Used in models: `Invoice::send()`, `Payment::send()`, etc., via `\Mail::to(...)->send(...)`.
  - Test mail endpoint: `App\Http\Controllers\V1\Admin\Settings\MailConfigurationController@test` sends a `TestMail`.
- **SMTP config**:
  - `config/mail.php` uses `MAIL_DRIVER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`.
  - `.env.example` includes `MAIL_FROM_NAME` and `MAIL_FROM_ADDRESS`.
- **Inbound email (missing)**:
  - No controllers, console commands, or jobs currently consume inbound email.
  - No "company email alias" mechanism or mapping table exists yet.
  - No mail parsing or attachment processing pipelines exist.

### Existing Expense Module (for reuse)
- **Model**: `App\Models\Expense`
  - Uses `RequiresApproval` and HasCustomFields.
  - Has `createExpense()` and `updateExpense()` helpers that:
    - Use `ExpenseRequest::getExpensePayload()` to enrich payload with `company_id`, `creator_id`, `exchange_rate`, `base_amount`.
    - Handle receipt uploads via MediaLibrary (`receipts` collection).
    - Write exchange-rate logs via `ExchangeRateLog::addExchangeRateLog`.
- **Request**: `App\Http\Requests\ExpenseRequest`
  - Validates `expense_date`, `expense_category_id`, `amount`, `currency_id`, `attachment_receipt` (mimes + max size).
  - Dynamically requires `exchange_rate` when company currency differs from expense currency.
  - Provides `getExpensePayload()` that merges validated input with tenant context and base currency calculations.
- **Resource**: `App\Http\Resources\ExpenseResource`
  - Returns core fields, formatted dates, receipt URLs, and related models (customer, company, currency, payment method, fields).
- **Controller**: `App\Http\Controllers\V1\Admin\Expense\ExpensesController`
  - Index: `Expense::with(...)->whereCompany()->applyFilters(...)->paginateData($limit)` plus a total-count meta field.
  - Store & update use `ExpenseRequest` and `Expense::createExpense` / `updateExpense`.
  - Delete uses `DeleteExpensesRequest` and authorizes `'delete multiple expenses'`.
- **Lesson for Bills**:
  - We should follow the same pattern: a `BillRequest` with `getBillPayload()`, `BillResource`, and a `BillsController` with `index/store/show/update/delete` plus actions like send/mark-as-viewed/mark-as-completed.

### Commented "Suppliers & Bills" Module
- **Routes** (admin API v1, currently commented):
  - Suppliers:
    - `POST /suppliers/delete`
    - `apiResource('suppliers', SuppliersController::class)`
  - Bills:
    - `POST /bills/{bill}/send`
    - `POST /bills/{bill}/mark-as-viewed`
    - `POST /bills/{bill}/mark-as-completed`
    - `GET /bills/{bill}/download-pdf`
    - `POST /bills/delete`
    - `apiResource('bills', BillsController::class)`
  - Bill payments:
    - `GET /bills/{bill}/payments`
    - `POST /bills/{bill}/payments`
    - `GET /bills/{bill}/payments/{payment}`
    - `PUT /bills/{bill}/payments/{payment}`
    - `DELETE /bills/{bill}/payments/{payment}`
- **Roadmap status**:
  - `documentation/roadmaps/roadmap_nov10.md` flags "Accounts Payable" milestone as:
    - Migrations, models, observers, IFRS integration **done**.
    - **BillsController and tests deferred/pending**.
- **UI**:
  - No dedicated Vue pages for Suppliers/Bills in `js/pages` yet.
  - Existing patterns for list/create/edit pages exist for Invoices, Expenses, Customers, Proforma Invoices.

### Existing Import / CSV Engine
- **Import infrastructure**:
  - `app/Services/Migration/Parsers/ExcelParserService.php` supports entity types including `'invoices', 'bills'`.
  - Intelligent field mapping: `app/Services/Import/Intelligent/IntelligentFieldMapper.php` understands `'bills' => MappingRule::ENTITY_INVOICE` and normalizes incoming entity types.
  - Migrations and docs for the migration wizard exist (`IMPORT_WIZARD_DEPENDENCIES.md`, `COMPETITOR_MIGRATION_GUIDE.md`).
- **Current limitation**:
  - Bulk CSV import for bills may be partially wired via the migration wizard, but there is no dedicated admin-facing "Upload Bills CSV" endpoint or UI yet.

### Existing QR / OCR / Banking Integrations
- **Banking**:
  - PSD2 integrations for Stopanska, NLB, Komercijalna are present (see `.env.example`, `services/psd2-gateway`), used for bank transaction imports and reconciliation.
  - These can serve as reference for how to structure external integrations and background jobs.
- **QR / OCR**:
  - No QR-code decoder or OCR pipeline is currently present for receipts in the PHP app.
  - AI-related tools (MCP server, AI_DOCUMENT endpoints) are separate and not directly used for AP automation yet.

## 0.2 Target Architecture — Accounts Payable Automation

### High-Level Flow Overview

1. **Suppliers & Bills core (manual operations)**
   - REST API (admin v1) for suppliers, bills, and bill payments.
   - Vue UI pages for:
     - Suppliers: list/create/edit/view.
     - Bills: list/create/edit/view/pay/download PDF.
   - Multi-tenant isolation via `company` header and policies.
   - IFRS posting via existing `BillObserver` / `BillPaymentObserver`.

2. **Email-to-Bill Inbox**
   - Each company gets a unique inbound email address, e.g. `bills+{company_hash}@facturino.mk`.
   - Inbound email is processed by:
     - Either an external MTA (e.g. Postfix, SES, Mailgun) forwarding to an HTTP endpoint.
     - Or a POP3/IMAP polling job (infrastructure choice).
   - In Laravel:
     - A controller or console command receives email payload (including attachments).
     - It dispatches a `ProcessInboundBillEmail` job onto a queue.
   - Job responsibilities:
     - Validate sender and attachment types.
     - Resolve tenant from recipient address.
     - Store original PDF in MediaLibrary or storage.
     - Call the invoice parser microservice (see below) to extract structured data.
     - Map parsed data → `Supplier` + `Bill` + `BillItem` records in DRAFT status.

3. **PDF Invoice Parsing Microservice**
   - Separate Python service (Dockerized) exposing a REST API endpoint, e.g. `POST /parse` that:
     - Accepts a PDF file upload (multipart/form-data) or a storage path reference.
     - Uses `invoice2data` with:
       - Built-in generic templates.
       - Custom templates for local suppliers (Macedonian layout).
     - Returns **normalized JSON**:
       - Supplier: name, tax_id, address, email.
       - Invoice: number, date, due date, currency, total, tax breakdown.
       - Line items: description, quantity, unit price, tax rate, line total.
   - This service is called by a Laravel `PdfInvoiceParserService` (or similar) via HTTP:
     - Service injected via interface + binding to support testing and future providers.
     - Errors and timeouts handled gracefully (fallback to manual entry).

4. **Fiscal Receipt QR Reader**
   - New Laravel endpoint for uploading receipt images/PDFs.
   - Flow:
     - Upload file → Laravel validates and stores temporarily.
     - QR reader (e.g. php-qrcode-detector-decoder or zbar) scans the image:
       - For Macedonian "fiskalna smetka" QR code, decode the standardized content into:
         - Issuer (tax ID, name).
         - Date/time.
         - Total amount, VAT, fiscal receipt id.
     - Based on configuration:
       - Create a draft `Expense` (for small cash receipts), or
       - Create a draft `Bill` (for supplier invoices) with minimal fields pre-filled.
     - Store original image as attachment (`receipts` or new `bills` media collection).

5. **Bulk CSV Import for Bills**
   - Extend the existing import wizard to include:
     - A "Bills" import type (if not already exposed).
     - Mapping of CSV columns → `Supplier` / `Bill` / `BillItem` fields.
   - Flow:
     - User uploads CSV/XLSX of incoming invoices.
     - Background import job reads rows, resolves or creates suppliers, and creates bills/items.
     - Errors per row are logged and reported via the existing import logs UI.

6. **Multi-Tenant Isolation and Security Layers**
   - Tenant resolution:
     - For API/UI requests: `company` header is required and enforced by policies.
     - For Email-to-Bill: extract company ID from the inbound email address (mapping table).
   - Access control:
     - `BillPolicy`, `SupplierPolicy`, `BillPaymentPolicy` will be enforced on controllers.
   - Data storage:
     - All bill/expense records include `company_id`.
     - MediaLibrary paths are per-tenant and not shared.
   - Queue & jobs:
     - All AP jobs must include `company_id` in payload and rehydrate tenant context explicitly.

### Component Diagram (Textual)

- **Frontend (Vue)**:
  - `SuppliersIndex.vue`, `SuppliersForm.vue`, `BillsIndex.vue`, `BillForm.vue`, `BillView.vue`, `BillPayments.vue`, `ReceiptUpload.vue`.
  - Communicate with new API endpoints under `/api/v1`.

- **Laravel API Layer**:
  - `SuppliersController`, `BillsController`, `BillPaymentsController`, `ReceiptScannerController`, `InboundMailController` (or console command).
  - Uses:
    - Requests: `SupplierRequest`, `BillRequest`, `BillPaymentRequest`, `ScanReceiptRequest`, `ImportBillsRequest`.
    - Resources: `SupplierResource`, `SupplierCollection`, `BillResource`, `BillCollection`, `BillPaymentResource`.
    - Services: `PdfInvoiceParserService`, `FiscalReceiptQrService`, `InboundMailTenantResolver`.
    - Jobs: `ProcessInboundBillEmail`, `ParseInvoicePdfJob`, `CreateBillFromParsedDataJob`.

- **Python Microservice**:
  - Containerized app (FastAPI or Flask) with:
    - `/health` endpoint for readiness checks.
    - `/parse` endpoint receiving PDF and returning normalized invoice JSON.
  - Uses `invoice2data` plus custom templates in a `/templates` directory.

- **Infrastructure & Integration**:
  - `docker-compose.yml` extended with a service like `invoice2data-service`.
  - Laravel env variables:
    - `INVOICE2DATA_URL` (base URL).
    - `INVOICE2DATA_TIMEOUT`, `INVOICE2DATA_API_KEY` (if needed).
  - Queues:
    - All parsing jobs run on existing `database` queue connection by default.
    - Optional dedicated queue `ap-automation` if needed later.

## 0.3 Revised Roadmap (Zero-Miss)

### Phase 0: Audit, Architecture, Test Plan (CURRENT PHASE)
- **Goals**:
  - Confirm status of Accounts Payable models/migrations/observers/policies.
  - Confirm multi-tenant and security patterns to reuse.
  - Design end-to-end architecture for:
    - Manual Bills module.
    - Email-to-Bill pipeline.
    - PDF parser microservice integration.
    - Fiscal QR reader.
    - Bulk CSV import for bills.
  - Define test and deployment strategy.
- **Outputs**:
  - This document: `documentation/roadmaps/AP_AUTOMATION_PLAN.md`.
  - Updated `LOG.md` entry for Phase 0.
  - Updated `tools/sanity-check.sh` (later phases) checklist items for AP automation verification.

### Phase 1: Bills Module Activation
- **Status:** ✅ Completed (backend APIs, PDF job, and feature tests implemented)
- **Backend**:
  - Implement:
    - `SuppliersController`, `BillsController`, `BillPaymentsController` under `App\Http\Controllers\V1\Admin\AccountsPayable`.
    - Requests: `SupplierRequest`, `BillRequest`, `BillPaymentRequest`, `DeleteBillsRequest`, `DeleteSuppliersRequest`.
    - Resources: `SupplierResource`, `SupplierCollection`, `BillResource`, `BillCollection`, `BillPaymentResource`.
  - Wire policies and authorization checks.
  - Handle PDF generation for bills via `GeneratesPdfTrait` and existing templates or temporary reuse of invoice templates.
  - Uncomment and validate `suppliers/bills/bill-payments` routes in `routes/api.php`.
- **UI**:
  - Add Vue pages under `js/pages/admin`:
    - `Suppliers/Index.vue`, `Suppliers/Form.vue`.
    - `Bills/Index.vue`, `Bills/Form.vue`, `Bills/View.vue`, `Bills/Payments.vue`.
  - Use existing table/filter/forms patterns from Invoices/Expenses.
- **Tests**:
  - Feature tests for suppliers and bills API (CRUD, pagination, filters).
  - Policy tests for `BillPolicy`/`SupplierPolicy`.
  - Multi-tenant tests: ensure company A cannot see company B bills.

#### Phase 1 — Completion Audit Notes
- **Controllers:** Suppliers, Bills, and BillPayments controllers follow existing Invoice/Expense patterns, enforce policies, and consistently apply `whereCompany` scopes; manual checks and automated tests confirm no cross-company data leakage.
- **Requests:** `SupplierRequest`, `BillRequest`, `BillPaymentRequest`, `DeleteBillsRequest`, and `DeleteSuppliersRequest` enforce per-company uniqueness for `bill_number`/`email`, validate item/tax arrays, and keep base-currency plus exchange-rate calculations aligned with existing invoice/expense logic.
- **Resources:** New Supplier/Bill/BillItem/BillPayment resources mirror existing API shapes (Invoice/Expense) with consistent field naming, formatted dates, and nested relations, ensuring frontend and API consumers see a coherent structure.
- **Model Integration:** Bills reuse existing IFRS hooks via `BillObserver`/`BillPaymentObserver` and plug into the shared PDF generation system through `GeneratesPdfTrait`, without altering Invoice behavior or numbering sequences.
- **Routes:** Activated suppliers/bills/bill-payments routes in `routes/api.php` mirror existing admin API conventions, sit behind authorization policies, and respect company header–based tenant isolation.
- **Tests:** New Pest feature tests cover CRUD flows, bulk delete, bill item creation, and form-request wiring for suppliers and bills; multi-tenant scoping is exercised via seeded users and `company` headers, with all assertions passing.
- **Safety:** Targeted test runs and manual inspection showed no impact on Invoices, Expenses, Customers, Bank integrations, or IFRS posting; tenant isolation and authorization rules continue to behave as before.

### Phase 2: Email Inbox Integration (Email → Bill Draft)
- **Status:** ✅ Completed (alias mapping, webhook, jobs, tests)
- **Backend**:
  - Design a `company_inbound_aliases` mechanism (table or derived alias) for mapping inbound email addresses → `company_id`.
  - Implement an inbound-email entrypoint:
    - HTTP webhook controller (e.g. `InboundMailController`) for providers like Mailgun/Postmark/SES, **or**
    - A POP3/IMAP polling console command that reads messages and pushes them into jobs.
  - Implement job `ProcessInboundBillEmail` to:
    - Parse provider payload, validate sender/recipient.
    - Identify company via alias.
    - Store attachments (original PDFs).
    - Dispatch `ParseInvoicePdfJob` for each valid attachment.
- **Security**:
  - Strict whitelisting for attachment types and sizes.
  - Sender validation rules (optional).
  - Avoid loading remote resources in HTML emails.
- **Tests**:
  - Unit tests for alias resolution and email payload parsing.
  - Feature tests that simulate provider webhook payloads and assert `Bill` drafts are created for the correct tenant.

#### Phase 2 — Completion Audit Notes
- **Alias Mapping:** `company_inbound_aliases` provides a company-scoped alias → company_id mapping with a unique alias per row, ensuring inbound emails are always resolved to a single tenant with no cross-company leakage.
- **InboundMailController:** The webhook controller validates to/from fields, extracts the local-part of the recipient, filters attachments by PDF MIME type, stores them under a company-specific path, and uses defensive logging plus benign 200 responses for unknown aliases or invalid payloads.
- **Jobs Pipeline:** `ProcessInboundBillEmail` and `ParseInvoicePdfJob` both carry `companyId` explicitly, queue cleanly, and prepare a multi-tenant-safe pipeline for later parsing, without side effects when no valid attachments exist.
- **Tests:** Feature tests cover valid and unknown aliases, PDF vs non-PDF attachments, and correct dispatch of `ProcessInboundBillEmail` with expected metadata, providing high-signal validation of the new webhook behavior.
- **Safety:** All changes are additive to the webhook layer and job queue; no behavior in Invoices, Expenses, Bank integrations, or IFRS posting was altered, and existing tests continue to pass.

### Phase 3: Python PDF Parser Microservice
- **Status:** ✅ Completed (Python microservice, Laravel integration, mapping layer, tests)
- **Microservice**:
  - Implement a Python service (FastAPI preferred for typing and testing) in a new folder, e.g. `invoice2data-service/`.
  - Install `invoice2data` and dependencies.
  - Implement `/parse` endpoint:
    - Accepts file upload, runs invoice2data with available templates.
    - Normalizes result into a stable JSON schema.
  - Add Dockerfile for the Python service.
- **Laravel Integration**:
  - Create `App\Services\InvoiceParsing\Invoice2DataClient` (or similar).
  - Bind interface in a service provider (e.g. `InvoiceParsingServiceProvider`).
  - Add envs: `INVOICE2DATA_URL`, timeout, retry count.
- **Tests**:
  - Contract tests:
    - Mock Python service in Laravel tests using HTTP fake.
    - Unit tests for JSON → `Bill`/`BillItem` mapping.
  - Microservice tests:
    - Python unit tests around template matching and normalization.

#### Phase 3 — Completion Audit Notes
- **Microservice:** The FastAPI service in `invoice2data-service` exposes `/health` and `/parse`, loads templates when available, and returns a normalized invoice schema that has proven stable across tests.
- **Dockerization:** The Python Dockerfile and requirements produce a container that boots the service on port 8000 with all dependencies installed via `requirements.txt`.
- **Laravel client:** `Invoice2DataClient` uses configurable URL and timeouts, performs a robust HTTP call with `Http::timeout()`, and works with storage to attach the raw PDF path to the parsing request.
- **Mapper layer:** `ParsedInvoiceMapper` correctly normalizes supplier, bill, and item data, including computing base_* monetary fields based on the company’s functional currency.
- **Job chain:** `ProcessInboundBillEmail` and `ParseInvoicePdfJob` propagate `companyId` end-to-end, creating predictable draft Bills and related Suppliers/Items without crossing tenant boundaries.
- **Tests:** Parser job feature tests and inbound alias→bill E2E tests pass using `Http::fake`, validating supplier creation, bill/item creation, and tenant isolation.
- **Safety:** All changes in Phase 3 are additive; Invoice, Expense, Bank, and IFRS flows remain untouched and targeted test runs show no regressions.

### Phase 4: Fiscal Receipt QR Scanner
- **Status:** ✅ Completed (QR scanner, normalized parsing, bill/expense creation, tests)
- **Backend**:
  - Add a new controller (e.g. `ReceiptScannerController`) with endpoint for uploading a receipt image/PDF.
  - Integrate a QR decoder library (php-qrcode-detector-decoder or ZBar binding) to extract data from Macedonian fiscal QR codes.
  - Implement `FiscalReceiptQrService` that:
    - Knows the Macedonian QR payload format.
    - Maps values into a normalized receipt DTO (issuer, date/time, total, VAT, fiscal ID).
  - Decide mapping rules:
    - Small cash receipts → `Expense` draft.
    - Large B2B invoices → `Bill` draft with minimal fields.
- **Tests**:
  - Unit tests for QR parsing using sample QR strings.
  - Feature tests to ensure an uploaded sample image leads to a draft Expense/Bill for the correct company.

#### Phase 4 — Completion Audit Notes
- **QR service:** `FiscalReceiptQrService` uses the ZXing-based `Zxing\QrReader` and, when available, Imagick to render the first page of PDFs to images, enabling QR decoding from both images and PDFs.
- **Payload parser:** The QR payload parser enforces the Macedonian-format `MK|` prefix and validates that TIN, TOTAL, and DATETIME are present before normalizing values into a fiscal receipt DTO.
- **Controller flow:** `ReceiptScannerController` safely stores uploaded files, cleans up on QR decode failures, and branches into Expense or Bill draft creation based on receipt type, always attaching the original document via MediaLibrary. QR decoding now includes enhanced retry logic (high-contrast Imagick/GD preprocessing, configurable via `FISCAL_QR_MAX_RETRIES`) and falls back to OCR-based invoice parsing when QR decoding fails, ensuring Macedonian fiscal receipts are handled robustly.
- **Multi-tenant:** All receipt scanning operations derive `company_id` from the `company` header and apply company scoping consistently on Expense, Bill, Supplier, and ExpenseCategory queries.
- **Tests:** Dedicated unit tests validate QR payload parsing edge cases, and feature tests verify that JPEG/PNG receipt uploads create appropriate Expense/Bill drafts with correct scoping and behavior.
- **Safety:** The QR scanner and related services are additive; existing Invoice, Expense, Bank, and IFRS functionality remains unchanged, with targeted suites confirming no regressions.

### Phase 5: Bulk CSV Import for Bills
- **Status:** ✅ Completed (import presets, BillImport, ProcessImportJob integration, tests)
- **Backend**:
  - Extend import wizard to expose "Bills" as an import type in UI.
  - Implement mapping configuration for Bills in `ImportPresetService` and `IntelligentFieldMapper`.
  - Ensure import jobs respect company scoping and IFRS posting rules.
- **Tests**:
  - Import job tests with sample CSV files covering:
    - New supplier creation.
    - Existing supplier matching.
    - Line items and tax mapping.
    - Error reporting for invalid rows.

#### Phase 5 — Completion Audit Notes
- **Import presets:** The migration presets service now exposes `bills` as a first-class entity type, with generic mappings for bill_number, supplier fields, dates, totals, and optional item columns.
- **BillImport:** The Bill importer maps columns, ensures required fields, creates or updates suppliers, enforces per-company bill_number uniqueness, parses amounts into cents, and sets base_* currency fields based on company settings.
- **Multi-tenant pipeline:** The import job passes company_id from ImportJob to BillImport, and all Supplier/Bill/BillItem records are created with the correct company_id, preventing cross-company data creation.
- **ProcessImportJob integration:** The job factory now supports a `bills` type that instantiates BillImport cleanly alongside existing customer/item/invoice importers.
- **Tests:** Feature tests validate the Bills import controller, job dispatch, supplier and bill creation, and that imports under one company do not affect another.
- **Safety:** All changes are additive to the migration system; existing Invoice, Expense, Bank, and IFRS behavior remains intact, with targeted import tests showing no regressions.

### Phase 6: UI Integration & UX Polish
- **UI**:
  - Integrate AP features into navigation (e.g. "Suppliers", "Bills" under a "Purchases" or "Expenses" section).
  - Add an "Inbox" view summarizing:
    - New email-based bill drafts.
    - New QR-based receipt/expense drafts.
  - Show parsing confidence / warnings and allow easy editing before approval.
- **Permissions & Feature Flags**:
  - Add feature flags if needed (e.g. `FEATURE_ACCOUNTS_PAYABLE_AUTOMATION`).
  - Ensure roles/abilities cover new screens and actions.

### Phase 7: Tests (Unit, Feature, Regression, E2E)
- **New tests**:
  - Multi-tenant tests: concurrent access by two companies across:
    - Bills, Suppliers, BillPayments.
    - Email-to-Bill, QR scanner, CSV import.
  - Email parsing tests: fixture payloads from chosen provider.
  - PDF parsing tests: mocked invoice2data responses and error paths.
  - QR decoder tests: known Macedonian fiscal QR samples.
  - Bill workflow tests: create → approval → IFRS posting → payment.
  - CSV import E2E: upload → job run → bills created.
- **Regression tests**:
  - Ensure no regressions in:
    - Invoices, Expenses, Customers, Payments.
    - Bank connections and reconciliations.
    - IFRS adapter functions and numbering sequences.
    - Permissions/policies for all document types.
- **E2E Scenario** (Email → Bill → Expense):
  - Simulated inbound email with attached invoice PDF.
  - Parser creates bill draft for Company A.
  - User finalizes bill and records payment/expense.
  - IFRS posting and VAT reporting remain correct.

### Phase 8: Deployment & Smoke Tests
- **Deployment artifacts**:
  - Dockerfile for invoice2data microservice.
  - Updated `docker-compose.yml` including the microservice.
  - Laravel service provider & bindings for the parser client.
  - Mail-inbound integration (documented per chosen provider).
- **Pre-deploy checks**:
  - Run `tools/sanity-check.sh` extended with:
    - Presence of AP controllers and routes.
    - Presence of invoice2data service configuration.
    - Basic route checks for AP endpoints.
  - Run full test suite (`phpunit`, JS tests if present).
  - Build + compile UI assets (`npm ci && npm run build`).
- **Smoke tests**:
  - Create supplier + bill manually in UI.
  - Attach PDF to bill and generate PDF.
  - Trigger Email-to-Bill for a sample company.
  - Run a QR-based receipt upload.
  - Upload a Bills CSV.
  - Verify no errors in logs and that data is tenant-isolated.

## 0.4 Handoff Conditions (PM → Dev → Tester)

- **PM → Dev**:
  - This AP automation plan exists and covers:
    - Audit findings.
    - Target architecture.
    - Phase-by-phase roadmap.
  - AGENT roadmaps and `AGENT_TASKS.md` aligned with new AP automation phases (to be updated in Phase 1).

- **Dev → Tester**:
  - Bills/Suppliers module implemented and routes enabled without breaking existing modules.
  - Email-to-Bill, PDF parser integration, QR scanner, and CSV import implemented behind necessary flags/config.
  - New tests authored and passing locally.
  - Sanity script updated with AP automation checks.

- **Tester → PM**:
  - Full test and regression report written (including multi-tenant and E2E scenarios).
  - All new features verified against the roadmap.
  - Final sign-off for production deployment documented.
