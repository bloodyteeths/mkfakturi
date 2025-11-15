MAX_TOKENS_HINT: 500

# AGENT_DEVELOPER_ROADMAP

- Agent: DEVELOPER
- Goal: Implement feature flags/safety rails and Accounts Payable automation backend (Suppliers & Bills).

## Tasks

- Task: Gate CPAY UI behind advanced payments flag  
  - Subtasks:
    - Add `useGlobalStore` to invoice view.
    - Hide “Pay with CPAY” when `advanced_payments` is disabled.
  - Status: Done
  - LastUpdate: 2025-11-15T10:35:00Z
  - Notes: `resources/scripts/admin/views/invoices/View.vue` now checks `globalStore.featureFlags['advanced_payments']`.

- Task: Add Redis queues feature flag  
  - Subtasks:
    - Add `redis_queues` entry to `config/features.php`.
    - Expose `redis-queues` in `FeatureFlagsController`.
    - Define `redis-queues` in `FeatureFlagServiceProvider`.
  - Status: Done
  - LastUpdate: 2025-11-15T10:35:00Z
  - Notes: Feature flags UI can now toggle Redis queues conceptually.

- Task: Make queues safe without Redis  
  - Subtasks:
    - Default queue connection to `database` when `FEATURE_REDIS_QUEUES` is false or unset.
  - Status: Done
  - LastUpdate: 2025-11-15T10:35:00Z
  - Notes: `config/queue.php` now prefers `database` unless explicitly configured to use Redis.

- Task: Activate Bills/Suppliers backend (AP Automation Phase 1)  
  - Subtasks:
    - Implement `SuppliersController`, `BillsController`, `BillPaymentsController`.  
    - Add `SupplierRequest`, `BillRequest`, `BillPaymentRequest`, `DeleteBillsRequest`, `DeleteSuppliersRequest`.  
    - Add `SupplierResource`, `SupplierCollection`, `BillResource`, `BillCollection`, `BillItemResource`, `BillPaymentResource`.  
    - Enable suppliers/bills/bill-payments routes in `routes/api.php`.  
    - Wire `GenerateBillPdfJob` and `Bill` PDF generation using `GeneratesPdfTrait`.  
    - Ensure full multi-tenant isolation and IFRS compatibility.  
    - Add feature tests for suppliers and bills APIs.  
  - Status: Completed
  - LastUpdate: 2025-11-15T12:00:00Z
  - Notes: Controllers, requests, resources, routes, PDF job and feature tests for suppliers/bills/bill-payments implemented; Phase 1 backend ready for AP automation.

- Task: Phase 2 — Email Inbox Integration (Email→Bill)  
  - Subtasks:
    - Add `company_inbound_aliases` table and `CompanyInboundAlias` model.  
    - Implement `InboundMailController` webhook with safe attachment handling and logging.  
    - Implement `ProcessInboundBillEmail` and `ParseInvoicePdfJob` jobs carrying `companyId`.  
    - Wire `/webhooks/email-inbound` route without CSRF and ensure multi-tenant isolation via alias mapping.  
    - Add feature tests for alias resolution, inbound email parsing, attachment filtering, and job dispatch.  
  - Status: Completed
  - LastUpdate: 2025-11-15T12:25:00Z
  - Notes: Alias table + model, inbound mail webhook, PDF attachment filter, jobs pipeline, and feature tests implemented; Phase 2 backend ready for parser integration.

- Task: Phase 3 — Invoice Parsing Microservice Integration  
  - Subtasks:
    - Implement `invoice2data-service` FastAPI microservice with `/health` and `/parse` endpoints.  
    - Add Dockerfile and requirements for the Python service.  
    - Create `InvoiceParserClient` interface and `Invoice2DataClient` implementation.  
    - Add `ParsedInvoiceMapper` to normalize parser JSON into supplier/bill/item payloads.  
    - Update `ParseInvoicePdfJob` to call the microservice, create Supplier/Bill/BillItems, and attach original PDFs.  
    - Add feature tests for parser job success and alias→bill multi-tenant isolation.  
  - Status: Completed
  - LastUpdate: 2025-11-15T12:55:00Z
  - Notes: Python invoice2data microservice, Laravel client, mapper layer, and ParseInvoicePdfJob integration implemented with passing tests and preserved tenant isolation.

- Task: Phase 4 — QR Fiscal Receipt Scanner Backend  
  - Subtasks:
    - Implement `FiscalReceiptQrService` using ZXing/Imagick for QR extraction.  
    - Add `ReceiptScanRequest` for file validation (JPEG/PNG/PDF, max size).  
    - Implement `ReceiptScannerController@scan` to branch into Expense or Bill creation based on QR payload type.  
    - Attach original receipt media to `receipts` or `bills` collections.  
    - Ensure company/creator/currency/base_* fields are set correctly and multi-tenant isolation is preserved.  
    - Add unit tests for QR payload parsing and feature tests for receipt scanning.  
  - Status: Completed
  - LastUpdate: 2025-11-15T13:20:00Z
  - Notes: QR decoding service, Macedonian QR payload parser, request/controller wiring, Bill/Expense creation, media attachments, routes, and tests implemented with tenant isolation.

- Task: Phase 5 — Bulk CSV Import for Bills  
  - Subtasks:
    - Extend Import presets to include Bills entity type.  
    - Implement `BillImport` to map CSV/XLSX rows into Supplier/Bill/BillItems with correct base_* currency fields.  
    - Wire `ProcessImportJob` to handle `bills` type and create tenant-scoped records only.  
    - Add `ImportBillsRequest` and `BillsImportController@import` endpoint.  
    - Add feature tests for CSV upload, supplier auto-creation, bill/item creation, row-level errors, and tenant isolation.  
  - Status: Completed
  - LastUpdate: 2025-11-15T13:45:00Z
  - Notes: Import presets, `BillImport`, process job integration, controller, and feature tests implemented; Bills import is multi-tenant safe and regressions avoided.

- Task: Phase 6 — Accounts Payable UI Integration  
  - Subtasks:
    - Add Vue pages for Suppliers, Bills, Bills Inbox, and Receipt Scanner using existing Base components.  
    - Implement Pinia stores for suppliers, bills, inbox, and receipt scanner with full CRUD and multi-tenant-aware API usage.  
    - Wire admin router and main menu navigation for “Suppliers”, “Bills”, “Bills Inbox”, and “Receipt Scanner” respecting permissions.  
    - Implement bill create/edit with supplier selector, items editor, totals calculation, PDF download and status actions, plus bill payments tab.  
    - Implement Inbox actions (view, approve, delete, convert to expense) and Receipt Scanner flow (upload → scan → redirect to Bill/Expense).  
    - Add minimal Cypress specs for AP navigation and basic create flows.  
  - Status: InProgress
  - LastUpdate: 2025-11-15T14:15:00Z
  - Notes: Routes, menu entries, Pinia stores, Suppliers/Bills/Inbox/ReceiptScanner views and initial Cypress tests implemented; further UX polish and regression passes will follow in Phase 7.

## Self-Audit Report

- TOKEN_ESTIMATE: 900
- Lessons_for_next_run:
  - Mirror existing invoice/expense patterns closely for new document types to minimize regression risk.
  - Always add form requests and resources together with controllers to keep contracts explicit.
  - When enabling new routes, add focused feature tests immediately for CRUD and multi-tenant behavior.

Handoff → TESTER (for AP Phase 1 once tests are green)
