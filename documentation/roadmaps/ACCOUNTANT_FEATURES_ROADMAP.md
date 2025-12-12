# Accountant-Requested Features Roadmap
**Created**: 2025-11-30
**Owner**: Multi-Agent System (PM + Backend + Frontend + QA)

## Overview
This roadmap implements features requested by a test accountant for Facturino.
All changes are additive, backward-compatible, and respect existing codebase structure.

---

## PHASE 1 – Low-risk, visible wins (Current Sprint)

### 1.1 Project Dimension (Project follow-up for construction etc.)

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| P1-01 | Create projects table migration | Backend | `database/migrations/2025_11_30_*_create_projects_table.php` | Pending | |
| P1-02 | Add project_id to invoices | Backend | `database/migrations/2025_11_30_*_add_project_id_to_invoices.php` | Pending | |
| P1-03 | Add project_id to expenses | Backend | `database/migrations/2025_11_30_*_add_project_id_to_expenses.php` | Pending | |
| P1-04 | Add project_id to payments | Backend | `database/migrations/2025_11_30_*_add_project_id_to_payments.php` | Pending | |
| P1-05 | Create Project model | Backend | `app/Models/Project.php` | Pending | |
| P1-06 | Update Invoice/Expense/Payment models | Backend | Update relations in existing models | Pending | |
| P1-07 | Create ProjectsController | Backend | `app/Http/Controllers/V1/Admin/Project/ProjectsController.php` | Pending | |
| P1-08 | Create ProjectRequest | Backend | `app/Http/Requests/ProjectRequest.php` | Pending | |
| P1-09 | Create ProjectResource | Backend | `app/Http/Resources/ProjectResource.php` | Pending | |
| P1-10 | Create ProjectPolicy | Backend | `app/Policies/ProjectPolicy.php` | Pending | |
| P1-11 | Add project API routes | Backend | `routes/api.php` | Pending | |
| P1-12 | Create project.js Pinia store | Frontend | `resources/scripts/admin/stores/project.js` | Pending | |
| P1-13 | Create Projects list page | Frontend | `resources/scripts/admin/views/projects/Index.vue` | Pending | |
| P1-14 | Create Project form page | Frontend | `resources/scripts/admin/views/projects/Create.vue` | Pending | |
| P1-15 | Add project dropdown to invoice form | Frontend | Update invoice form | Pending | |
| P1-16 | Add project dropdown to expense form | Frontend | Update expense form | Pending | |
| P1-17 | Add project filter to listings | Frontend | Update index pages | Pending | |
| P1-18 | Create Project Overview report | Backend | `app/Http/Controllers/V1/Admin/Report/ProjectReportController.php` | Pending | |
| P1-19 | Create Project report frontend | Frontend | `resources/scripts/admin/views/reports/ProjectReport.vue` | Pending | |
| P1-20 | Add Macedonian translations | Backend | Update `lang/mk.json` | Pending | |
| P1-21 | Write feature tests | QA | `tests/Feature/ProjectsTest.php` | Pending | |

**Acceptance Criteria:**
- [ ] User can create/edit/delete projects within their company
- [ ] User can link invoices, expenses, payments to a project
- [ ] User can filter documents by project in listings
- [ ] Project Overview report shows income, expenses, net result per project
- [ ] No behavior change for companies not using projects

---

### 1.2 Proforma Invoice

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| PRO-01 | Create proformas table migration | Backend | `database/migrations/2025_11_30_*_create_proformas_table.php` | Pending | |
| PRO-02 | Create proforma_items table migration | Backend | `database/migrations/2025_11_30_*_create_proforma_items_table.php` | Pending | |
| PRO-03 | Create Proforma model | Backend | `app/Models/Proforma.php` | Pending | |
| PRO-04 | Create ProformaItem model | Backend | `app/Models/ProformaItem.php` | Pending | |
| PRO-05 | Create ProformasController | Backend | `app/Http/Controllers/V1/Admin/Proforma/ProformasController.php` | Pending | |
| PRO-06 | Create ConvertProformaController | Backend | `app/Http/Controllers/V1/Admin/Proforma/ConvertProformaController.php` | Pending | |
| PRO-07 | Create ProformaRequest | Backend | `app/Http/Requests/ProformaRequest.php` | Pending | |
| PRO-08 | Create ProformaResource | Backend | `app/Http/Resources/ProformaResource.php` | Pending | |
| PRO-09 | Create ProformaPolicy | Backend | `app/Policies/ProformaPolicy.php` | Pending | |
| PRO-10 | Add proforma API routes | Backend | `routes/api.php` | Pending | |
| PRO-11 | Create proforma.js Pinia store | Frontend | `resources/scripts/admin/stores/proforma.js` | Pending | |
| PRO-12 | Create Proformas index page | Frontend | `resources/scripts/admin/views/proformas/Index.vue` | Pending | |
| PRO-13 | Create Proforma form page | Frontend | `resources/scripts/admin/views/proformas/Create.vue` | Pending | |
| PRO-14 | Create Proforma view page | Frontend | `resources/scripts/admin/views/proformas/View.vue` | Pending | |
| PRO-15 | Add proforma PDF template | Backend | `resources/views/app/pdf/proforma/proforma*.blade.php` | Pending | |
| PRO-16 | Add sidebar menu item | Frontend | Update navigation | Pending | |
| PRO-17 | Add Macedonian translations | Backend | Update `lang/mk.json` | Pending | |
| PRO-18 | Write feature tests | QA | `tests/Feature/ProformasTest.php` | Pending | |

**Acceptance Criteria:**
- [ ] User can create, list, view, print proformas
- [ ] Proforma clearly labeled "Proforma - not a fiscal document" (Macedonian: "Профактура - не е фискален документ")
- [ ] Proforma does NOT affect stock, VAT books, or e-Faktura
- [ ] Convert to Invoice button creates proper invoice from proforma
- [ ] All proforma items and client info copied during conversion

---

### 1.3 Expense Duplicate Protection

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| DUP-01 | Add duplicate check to ExpenseService | Backend | `app/Services/ExpenseService.php` or inline | Pending | |
| DUP-02 | Update ExpensesController store method | Backend | Check duplicates, return warning flag | Pending | |
| DUP-03 | Add 'allow_duplicate' field to ExpenseRequest | Backend | `app/Http/Requests/ExpenseRequest.php` | Pending | |
| DUP-04 | Update expense form for duplicate warning | Frontend | Show confirmation modal | Pending | |
| DUP-05 | Add Macedonian translations | Backend | Warning messages | Pending | |
| DUP-06 | Write feature tests | QA | `tests/Feature/ExpenseDuplicateTest.php` | Pending | |

**Acceptance Criteria:**
- [ ] Same supplier + invoice_number triggers warning (not hard-block)
- [ ] User can override and save with explicit confirmation
- [ ] No warnings for different suppliers or invoice numbers
- [ ] Warning message in Macedonian

---

## PHASE 2 – Stock module and profit calculation (Next Sprint)

### 2.1 Stock Foundation

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| STK-01 | Create warehouses table migration | Backend | Migration file | ✅ Complete | Already existed |
| STK-02 | Create stock_movements table migration | Backend | Migration file | ✅ Complete | Already existed |
| STK-03 | Create Warehouse model | Backend | `app/Models/Warehouse.php` | ✅ Complete | Already existed |
| STK-04 | Create StockMovement model | Backend | `app/Models/StockMovement.php` | ✅ Complete | Already existed |
| STK-05 | Create StockService | Backend | `app/Services/StockService.php` | ✅ Complete | Already existed |
| STK-06 | Add FACTURINO_STOCK_V1_ENABLED flag | Backend | `.env`, `config/facturino.php` | ✅ Complete | Added to .env.example |
| STK-07 | Write unit tests for StockService | QA | `tests/Unit/StockServiceTest.php` | ✅ Complete | Tests pass |

### 2.2 Valuation (Weighted Average)

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| VAL-01 | Implement weighted average calculation | Backend | In StockService | ✅ Complete | Already existed |
| VAL-02 | Add valuation_strategy to company settings | Backend | Migration + model update | ✅ Complete | Uses WAC by default |
| VAL-03 | Implement COGS calculation per invoice | Backend | Service method | ✅ Complete | InvoiceProfitService |
| VAL-04 | Write valuation unit tests | QA | Test file | ✅ Complete | 37 tests pass |

### 2.3 Stock Reports

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| SRPT-01 | Item stock card report backend | Backend | Controller | ✅ Complete | StockReportsController |
| SRPT-02 | Warehouse inventory report backend | Backend | Controller | ✅ Complete | StockReportsController |
| SRPT-03 | Inventory list export (PDF/Excel) | Backend | Service | ✅ Complete | CSV export in frontend |
| SRPT-04 | Stock reports frontend | Frontend | Vue pages | ✅ Complete | UI pages created |

### 2.4 Stock UI (Frontend - 2025-12-01)

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| S2-UI-01 | Create stock.js Pinia store | Frontend | `stores/stock.js` | ✅ Complete | |
| S2-UI-02 | Create warehouse.js Pinia store | Frontend | `stores/warehouse.js` | ✅ Complete | |
| S2-UI-03 | Create Inventory.vue page | Frontend | `views/stock/Inventory.vue` | ✅ Complete | |
| S2-UI-04 | Create LowStock.vue page | Frontend | `views/stock/LowStock.vue` | ✅ Complete | |
| S2-UI-05 | Create Warehouse Index page | Frontend | `views/stock/warehouses/Index.vue` | ✅ Complete | |
| S2-UI-06 | Create Warehouse Create page | Frontend | `views/stock/warehouses/Create.vue` | ✅ Complete | |
| S2-UI-07 | Add navigation menu items | Frontend | `config/invoiceshelf.php` | ✅ Complete | |
| S2-UI-08 | Add stock routes | Frontend | `admin-router.js` | ✅ Complete | |
| S2-UI-09 | Add translations (EN/MK) | Frontend | `lang/en.json`, `lang/mk.json` | ✅ Complete | |
| S2-UI-10 | Add WarehouseRequest validation | Backend | `app/Http/Requests/WarehouseRequest.php` | ✅ Complete | |
| S2-UI-11 | Add feature flag to WarehouseController | Backend | `WarehouseController.php` | ✅ Complete | |

### 2.5 Stock Operations & Validation (2025-12-02)

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| S2-OP-01 | Create Stock Adjustments UI | Frontend | `views/stock/Adjustments.vue` | ✅ Complete | Tab-based UI for adjustments & transfers |
| S2-OP-02 | Add adjustment store methods | Frontend | `stores/stock.js` | ✅ Complete | fetchAdjustments, createAdjustment, deleteAdjustment |
| S2-OP-03 | Add transfer store methods | Frontend | `stores/stock.js` | ✅ Complete | fetchTransfers, createTransfer |
| S2-OP-04 | Add adjustments route | Frontend | `admin-router.js` | ✅ Complete | /admin/stock/adjustments |
| S2-OP-05 | Add negative stock prevention | Backend | `InvoicesRequest.php` | ✅ Complete | Stock validation before invoice creation |
| S2-OP-06 | Add allow_negative_stock setting | Both | `PreferencesSetting.vue`, translations | ✅ Complete | Company setting to override validation |
| S2-OP-07 | Create retroactive stock command | Backend | `CreateRetroactiveStockMovements.php` | ✅ Complete | `php artisan stock:create-retroactive` |
| S2-OP-08 | Add initial stock entry to items | Both | `Items Create.vue`, `Item.php` | ✅ Complete | Initial stock when creating items |
| S2-OP-09 | Add View Stock link in items | Frontend | `ItemIndexDropdown.vue`, `ItemCard.vue` | ✅ Complete | Navigate from item list to stock card |
| S2-OP-10 | Add stock translations | Both | `lang/en.json`, `lang/mk.json` | ✅ Complete | EN + MK translations for all new features |

**Acceptance Criteria:**
- [x] User can manually adjust stock (add/remove) with reason tracking
- [x] User can transfer stock between warehouses
- [x] System prevents selling more than available stock (configurable)
- [x] User can set initial stock when creating new items
- [x] User can navigate from item list directly to stock card
- [x] Existing bills can be migrated to create stock movements (artisan command)

---

## PHASE 3 – Period Locking (Next Sprint)

> **Note**: Daily closing was removed from scope (2025-12-12). Period locking provides
> the same audit protection with less complexity and better UX. Monthly close is sufficient
> for Macedonian DDV compliance.

### 3.1 Period Locking Infrastructure (Already Complete)

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| PL-01 | Create period_locks table | Backend | `database/migrations/2025_11_30_200001_create_period_locks_table.php` | ✅ Complete | Already existed |
| PL-02 | Create PeriodLock model | Backend | `app/Models/PeriodLock.php` | ✅ Complete | Already existed |
| PL-03 | Create PeriodLockService | Backend | `app/Services/PeriodLockService.php` | ✅ Complete | Already existed |
| PL-04 | Create PeriodLockController | Backend | `app/Http/Controllers/V1/Admin/Accounting/PeriodLockController.php` | ✅ Complete | Already existed |
| PL-05 | Create PeriodLockedException | Backend | `app/Exceptions/PeriodLockedException.php` | ✅ Complete | HTTP 423 response |
| PL-06 | Add API routes | Backend | `routes/api.php` | ✅ Complete | Lines 744-754 |
| PL-07 | Create period-lock.js store | Frontend | `resources/scripts/admin/stores/period-lock.js` | ✅ Complete | Already existed |
| PL-08 | Create PeriodLockSetting.vue | Frontend | `resources/scripts/admin/views/settings/PeriodLockSetting.vue` | ✅ Complete | Already existed |
| PL-09 | Add settings menu item | Backend | `config/invoiceshelf.php` | ✅ Complete | Line 343-350 |
| PL-10 | Add router configuration | Frontend | `resources/scripts/admin/admin-router.js` | ✅ Complete | Line 658-661 |
| PL-11 | Write feature tests | QA | `tests/Feature/PeriodLockTest.php` | ✅ Complete | Already existed |

### 3.2 Period Lock Enforcement (Completed 2025-12-12)

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| PLE-01 | Add period lock validation to InvoicesRequest | Backend | `app/Http/Requests/InvoicesRequest.php` | ✅ Complete | Block create/edit in locked period |
| PLE-02 | Add period lock validation to ExpenseRequest | Backend | `app/Http/Requests/ExpenseRequest.php` | ✅ Complete | Block create/edit in locked period |
| PLE-03 | Add period lock validation to PaymentRequest | Backend | `app/Http/Requests/PaymentRequest.php` | ✅ Complete | Block create/edit in locked period |
| PLE-04 | Add period lock validation to DeleteInvoiceRequest | Backend | `app/Http/Requests/DeleteInvoiceRequest.php` | ✅ Complete | Block deletion in locked period |
| PLE-05 | Add period lock validation to DeleteExpensesRequest | Backend | `app/Http/Requests/DeleteExpensesRequest.php` | ✅ Complete | Block deletion in locked period |
| PLE-06 | Add period lock validation to DeletePaymentsRequest | Backend | `app/Http/Requests/DeletePaymentsRequest.php` | ✅ Complete | Block deletion in locked period |
| PLE-07 | Add frontend error handling for 423 responses | Frontend | Invoice/Expense/Payment forms | ✅ Complete | Uses standard 422 validation errors |
| PLE-08 | Add period lock translations | Both | `lang/en.json`, `lang/mk.json` | ✅ Complete | EN + MK error messages |
| PLE-09 | Write enforcement tests | QA | `tests/Feature/PeriodLockEnforcementTest.php` | ✅ Complete | 16 test cases |

**Acceptance Criteria:**
- [x] Cannot create invoice/expense/payment with date in locked period
- [x] Cannot edit invoice/expense/payment with date in locked period
- [x] Cannot delete invoice/expense/payment with date in locked period
- [x] Clear error message shown to user (HTTP 422 validation error)
- [x] Error messages in Macedonian
- [x] Existing period lock UI continues to work

---

## PHASE 4 – Accountant Tools (Current Sprint)

> **Architecture Note**: This phase creates a **mapping layer** between Facturino transactions and
> external accounting software (Pantheon, Zonel, etc.). Partners/accountants map customers, suppliers,
> and categories to account codes, then export journal entries that can be directly imported into
> their accounting systems.

### 4.0 Audit Results (2025-12-12)

**Already Implemented (Admin-Only):**
- ✅ `accounts` table with hierarchical structure (parent_id)
- ✅ `account_mappings` table linking entities to accounts
- ✅ `Account` model with full hierarchy support
- ✅ `AccountMapping` model with polymorphic relationships
- ✅ `AccountSuggestionService` with learning algorithm
- ✅ `JournalExportService` with Pantheon/Zonel/CSV formats
- ✅ Admin UI: `ChartOfAccountsSetting.vue`, `JournalExportSetting.vue`

**Critical Bug Found:**
- ⚠️ `JournalExportService.php` line 247 queries `mapping_type` column that DOES NOT EXIST
- Must be fixed before any journal export functionality can work

**Missing for Phase 4:**
- ❌ Partner-facing endpoints (all accounting is admin-only currently)
- ❌ Partner Vue UI pages for accounting features
- ❌ Account mapping workflow for partners
- ❌ Journal entry review/confirmation UI

---

### 4.1 Bug Fixes (Prerequisite - MUST DO FIRST)

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| BUG-01 | Fix JournalExportService mapping_type query | Backend | `app/Services/JournalExportService.php:247` | Pending | Column doesn't exist - breaks all exports |
| BUG-02 | Add missing mapping_type column OR fix query logic | Backend | Migration or Service fix | Pending | Decide: add column or change query approach |
| BUG-03 | Write unit tests for JournalExportService | QA | `tests/Unit/JournalExportServiceTest.php` | Pending | Ensure exports work for all formats |

---

### 4.2 Chart of Accounts (Infrastructure - Already Complete)

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| COA-01 | Create accounts table | Backend | `database/migrations/2025_*_create_accounts_table.php` | ✅ Complete | Hierarchical with parent_id |
| COA-02 | Create account_mappings table | Backend | `database/migrations/2025_*_create_account_mappings_table.php` | ✅ Complete | Polymorphic mapping |
| COA-03 | Create Account model | Backend | `app/Models/Account.php` | ✅ Complete | Full hierarchy support |
| COA-04 | Create AccountMapping model | Backend | `app/Models/AccountMapping.php` | ✅ Complete | Entity-to-account links |
| COA-05 | Create AccountController (Admin) | Backend | `app/Http/Controllers/V1/Admin/Accounting/AccountController.php` | ✅ Complete | CRUD operations |
| COA-06 | Create AccountMappingController (Admin) | Backend | `app/Http/Controllers/V1/Admin/Accounting/AccountMappingController.php` | ✅ Complete | Mapping management |
| COA-07 | Create AccountSuggestionService | Backend | `app/Services/AccountSuggestionService.php` | ✅ Complete | AI suggestion with learning |
| COA-08 | Create JournalExportService | Backend | `app/Services/JournalExportService.php` | ✅ Complete | Pantheon/Zonel/CSV export |
| COA-09 | Create Admin Chart of Accounts UI | Frontend | `resources/scripts/admin/views/settings/ChartOfAccountsSetting.vue` | ✅ Complete | Admin settings page |
| COA-10 | Create Admin Journal Export UI | Frontend | `resources/scripts/admin/views/settings/JournalExportSetting.vue` | ✅ Complete | Admin settings page |

---

### 4.3 Partner Accounting Backend (Parallel Track A)

> **Agent**: Backend
> **Dependency**: BUG-01, BUG-02 must be complete first
> **Can run parallel with**: 4.4 (Frontend), 4.5 (QA)

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| PAB-01 | Create PartnerAccountController | Backend | `app/Http/Controllers/V1/Partner/PartnerAccountController.php` | Pending | Partner-scoped account CRUD |
| PAB-02 | Create PartnerAccountMappingController | Backend | `app/Http/Controllers/V1/Partner/PartnerAccountMappingController.php` | Pending | Partner-scoped mappings |
| PAB-03 | Create PartnerJournalExportController | Backend | `app/Http/Controllers/V1/Partner/PartnerJournalExportController.php` | Pending | Export for linked companies |
| PAB-04 | Create PartnerJournalEntryController | Backend | `app/Http/Controllers/V1/Partner/PartnerJournalEntryController.php` | Pending | Review/confirm entries |
| PAB-05 | Add partner accounting routes | Backend | `routes/api.php` | Pending | `/v1/partner/accounts/*` |
| PAB-06 | Create PartnerAccountRequest | Backend | `app/Http/Requests/PartnerAccountRequest.php` | Pending | Validation for accounts |
| PAB-07 | Create PartnerAccountMappingRequest | Backend | `app/Http/Requests/PartnerAccountMappingRequest.php` | Pending | Validation for mappings |
| PAB-08 | Create PartnerExportRequest | Backend | `app/Http/Requests/PartnerExportRequest.php` | Pending | Validation for exports |
| PAB-09 | Extend JournalExportService for partner context | Backend | `app/Services/JournalExportService.php` | Pending | Support partner-company filtering |
| PAB-10 | Add account/mapping abilities to PartnerPolicy | Backend | `app/Policies/PartnerPolicy.php` | Pending | Authorization rules |

**API Endpoints to Create:**
```
GET    /v1/partner/accounts                    - List chart of accounts
POST   /v1/partner/accounts                    - Create account
PUT    /v1/partner/accounts/{id}               - Update account
DELETE /v1/partner/accounts/{id}               - Delete account
POST   /v1/partner/accounts/import             - Import accounts from CSV

GET    /v1/partner/mappings                    - List account mappings
POST   /v1/partner/mappings                    - Create mapping (customer/supplier/category → account)
PUT    /v1/partner/mappings/{id}               - Update mapping
DELETE /v1/partner/mappings/{id}               - Delete mapping
POST   /v1/partner/mappings/auto-suggest       - Get AI suggestions for entity

GET    /v1/partner/journal-entries             - List journal entries for date range
GET    /v1/partner/journal-entries/{id}        - Get single entry details
PUT    /v1/partner/journal-entries/{id}        - Confirm/adjust entry
POST   /v1/partner/journal/export              - Export to Pantheon/Zonel/CSV
```

---

### 4.4 Partner Accounting Frontend (Parallel Track B)

> **Agent**: Frontend
> **Dependency**: Can start immediately (mock API responses initially)
> **Can run parallel with**: 4.3 (Backend), 4.5 (QA)

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| PAF-01 | Create partner accounting store | Frontend | `resources/scripts/admin/stores/partner-accounting.js` | Pending | Pinia store for all accounting |
| PAF-02 | Create PartnerChartOfAccounts.vue | Frontend | `resources/scripts/admin/views/partner/accounting/ChartOfAccounts.vue` | Pending | Account tree with CRUD |
| PAF-03 | Create PartnerAccountMappings.vue | Frontend | `resources/scripts/admin/views/partner/accounting/AccountMappings.vue` | Pending | Entity-to-account mapping UI |
| PAF-04 | Create PartnerJournalEntries.vue | Frontend | `resources/scripts/admin/views/partner/accounting/JournalEntries.vue` | Pending | Entry list with filters |
| PAF-05 | Create PartnerJournalExport.vue | Frontend | `resources/scripts/admin/views/partner/accounting/JournalExport.vue` | Pending | Export wizard UI |
| PAF-06 | Create AccountTreeComponent.vue | Frontend | `resources/scripts/admin/components/accounting/AccountTreeComponent.vue` | Pending | Reusable tree view |
| PAF-07 | Create MappingWizard.vue | Frontend | `resources/scripts/admin/components/accounting/MappingWizard.vue` | Pending | Step-by-step mapping helper |
| PAF-08 | Create AccountSuggestionBadge.vue | Frontend | `resources/scripts/admin/components/accounting/AccountSuggestionBadge.vue` | Pending | AI suggestion indicator |
| PAF-09 | Add partner accounting routes | Frontend | `resources/scripts/admin/admin-router.js` | Pending | Router configuration |
| PAF-10 | Add partner accounting navigation | Frontend | `config/invoiceshelf.php` | Pending | Sidebar menu items |
| PAF-11 | Add accounting translations (EN) | Frontend | `lang/en.json` | Pending | ~50 new translation keys |
| PAF-12 | Add accounting translations (MK) | Frontend | `lang/mk.json` | Pending | Macedonian translations |

**UI Pages to Create:**

1. **Chart of Accounts** (`/partner/accounting/chart-of-accounts`)
   - Hierarchical tree view with expand/collapse
   - Inline editing of account codes and names
   - Import from CSV button
   - Filter by account type (asset, liability, equity, income, expense)

2. **Account Mappings** (`/partner/accounting/mappings`)
   - Three tabs: Customers, Suppliers, Categories
   - Table with entity name, current mapping, AI suggestion
   - Bulk mapping actions
   - "Apply AI Suggestions" button

3. **Journal Entries** (`/partner/accounting/journal-entries`)
   - Date range filter
   - Company filter (for multi-company partners)
   - Status filter: All, Suggested, Confirmed
   - Click to expand entry details
   - Confirm/Edit/Skip actions

4. **Journal Export** (`/partner/accounting/export`)
   - Step 1: Select date range and companies
   - Step 2: Review unconfirmed entries (optional)
   - Step 3: Choose format (Pantheon XML, Zonel CSV, Generic CSV)
   - Step 4: Download or send to email

---

### 4.5 Testing & QA (Parallel Track C)

> **Agent**: QA
> **Dependency**: None (can write tests before implementation)
> **Can run parallel with**: 4.3 (Backend), 4.4 (Frontend)

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| QA-01 | Write unit tests for AccountSuggestionService | QA | `tests/Unit/AccountSuggestionServiceTest.php` | Pending | Test learning algorithm |
| QA-02 | Write unit tests for JournalExportService | QA | `tests/Unit/JournalExportServiceTest.php` | Pending | Test all export formats |
| QA-03 | Write feature tests for partner accounts | QA | `tests/Feature/PartnerAccountsTest.php` | Pending | CRUD + authorization |
| QA-04 | Write feature tests for partner mappings | QA | `tests/Feature/PartnerAccountMappingsTest.php` | Pending | CRUD + suggestions |
| QA-05 | Write feature tests for partner export | QA | `tests/Feature/PartnerJournalExportTest.php` | Pending | Export formats + filtering |
| QA-06 | Write E2E tests for mapping workflow | QA | `tests/Browser/PartnerMappingWorkflowTest.php` | Pending | Full user flow |
| QA-07 | Create test fixtures for accounting data | QA | `tests/fixtures/accounting/` | Pending | Sample accounts, mappings |

---

### 4.6 Integration & Documentation (Final)

> **Agent**: Backend + Frontend
> **Dependency**: 4.3 and 4.4 must be complete
> **Sequential after**: All parallel tracks complete

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| INT-01 | Integration testing of full workflow | QA | Manual testing | Pending | End-to-end verification |
| INT-02 | Performance testing for large exports | QA | Load testing | Pending | Test with 10k+ entries |
| INT-03 | Create partner accounting documentation | Backend | `docs/api/partner-accounting.md` | Pending | API docs for partners |
| INT-04 | Create user guide for accountants | Frontend | TBD | Pending | How-to guide |

---

### Phase 4 Acceptance Criteria

**Chart of Accounts:**
- [ ] Partner can create/edit/delete accounts in hierarchical structure
- [ ] Partner can import accounts from CSV
- [ ] Account codes follow Macedonian accounting standards (optional)
- [ ] Accounts are company-scoped (each linked company has own chart)

**Account Mappings:**
- [ ] Partner can map customers to accounts (e.g., "Customer X → Account 1200")
- [ ] Partner can map suppliers to accounts (e.g., "Supplier Y → Account 4100")
- [ ] Partner can map item categories to accounts
- [ ] AI suggests accounts based on entity name/history
- [ ] Partner can accept or override AI suggestions
- [ ] Suggestions improve over time (learning)

**Journal Export:**
- [ ] Partner can view journal entries for any linked company
- [ ] Partner can filter by date range, status, company
- [ ] Partner can export to Pantheon XML format
- [ ] Partner can export to Zonel CSV format
- [ ] Partner can export to generic CSV format
- [ ] Export includes proper account codes from mappings

**Authorization:**
- [ ] Partners can only access linked companies' data
- [ ] Regular users cannot access partner accounting features
- [ ] Admin can still manage global chart of accounts

---

### Multi-Agent Parallel Implementation Plan

```
Week 1 (Parallel Execution):
├── Agent A (Backend): BUG-01, BUG-02, PAB-01 through PAB-05
├── Agent B (Frontend): PAF-01 through PAF-06 (with mock data)
└── Agent C (QA): QA-01, QA-02, QA-07 (test fixtures)

Week 2 (Parallel Execution):
├── Agent A (Backend): PAB-06 through PAB-10
├── Agent B (Frontend): PAF-07 through PAF-12
└── Agent C (QA): QA-03, QA-04, QA-05

Week 3 (Integration):
├── Agent A + B: Connect frontend to real API
├── Agent C (QA): QA-06, INT-01
└── All: Bug fixes from integration testing

Week 4 (Polish):
├── INT-02: Performance testing
├── INT-03, INT-04: Documentation
└── Final QA sign-off
```

**Task Dependencies Graph:**
```
BUG-01 ──┬──> PAB-03 (JournalExportController needs fixed service)
BUG-02 ──┘

PAF-01 ──> PAF-02, PAF-03, PAF-04, PAF-05 (store needed for all pages)

PAB-05 ──> PAF-09 (routes must exist before frontend routes)

QA-07 ──> QA-03, QA-04, QA-05 (fixtures needed for tests)
```

---

## PHASE 5 – Zonel Integration (Optional/Future)

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| ZON-01 | Discover Zonel/Helix API | Backend | Research | Pending | |
| ZON-02 | Implement export/sync | Backend | Service | Pending | |

---

## QA Cross-Cutting Requirements

For every phase completion:
- [ ] Document manual UI test steps
- [ ] Verify existing core flows (invoices, payments, expenses) still work
- [ ] Ensure migrations run cleanly
- [ ] Test with Macedonian locale
- [ ] Check mobile responsiveness

---

## Implementation Notes

### Database Conventions
- Engine: InnoDB
- Charset: utf8mb4, Collation: utf8mb4_unicode_ci
- Foreign keys: ON DELETE RESTRICT
- All new tables scoped to company_id

### File Location Conventions
- New PHP: `app/` or `modules/Mk/` for MK-specific
- New Vue: `resources/scripts/admin/views/` for admin pages
- New stores: `resources/scripts/admin/stores/`
- Migrations: `database/migrations/2025_11_30_*.php`

### Feature Flags
- New features behind config flags until tested
- Example: `config('facturino.features.projects', false)`

---

## Progress Tracking

| Phase | Status | Started | Completed | Notes |
|-------|--------|---------|-----------|-------|
| Phase 1.1 - Projects | Complete | 2025-11-30 | 2025-11-30 | Backend + Frontend complete |
| Phase 1.2 - Proforma | Complete | Prior | 2025-12-01 | Backend existed, Frontend completed 2025-12-01 |
| Phase 1.3 - Duplicate Protection | Complete | 2025-11-30 | 2025-11-30 | Backend + Frontend complete |
| Phase 2.1 - Stock Foundation | Complete | Prior | 2025-12-01 | Backend existed, Frontend UI completed 2025-12-01 |
| Phase 2.2 - Valuation | Complete | Prior | 2025-12-01 | WAC valuation implemented |
| Phase 2.3 - Stock Reports | Complete | Prior | 2025-12-01 | Backend + Frontend complete |
| Phase 2.4 - Stock UI | Complete | 2025-12-01 | 2025-12-01 | All Vue pages + stores created |
| Phase 2.5 - Stock Operations | Complete | 2025-12-02 | 2025-12-02 | Adjustments, transfers, validation, retroactive |
| Phase 3.1 - Period Lock Infra | Complete | Prior | Prior | Infrastructure already existed |
| Phase 3.2 - Period Lock Enforce | Complete | 2025-12-12 | 2025-12-12 | All 9 tasks done |
| Phase 4.1 - Bug Fixes | Pending | | | Critical: JournalExportService broken |
| Phase 4.2 - COA Infrastructure | Complete | Prior | Prior | Admin-only, already implemented |
| Phase 4.3 - Partner Backend | Pending | | | 10 tasks for partner API |
| Phase 4.4 - Partner Frontend | Pending | | | 12 tasks for partner Vue UI |
| Phase 4.5 - Testing | Pending | | | 7 test tasks |
| Phase 4.6 - Integration | Pending | | | Final polish + docs |
| Phase 5 - Zonel | Pending | | | |

---

## Completed Work Summary (2025-11-30)

### Phase 1.1 - Projects
**Files Created:**
- `database/migrations/2025_11_30_120001_create_projects_table.php`
- `database/migrations/2025_11_30_120002_add_project_id_to_documents.php`
- `app/Models/Project.php`
- `app/Http/Controllers/V1/Admin/Project/ProjectsController.php`
- `app/Http/Requests/ProjectRequest.php`
- `app/Http/Requests/DeleteProjectsRequest.php`
- `app/Http/Resources/ProjectResource.php`
- `app/Policies/ProjectPolicy.php`
- `resources/scripts/admin/stores/project.js`

**Files Modified:**
- `routes/api.php` - Added project routes
- `app/Models/Invoice.php` - Added project() relation
- `app/Models/Expense.php` - Added project() relation
- `app/Models/Payment.php` - Added project() relation
- `lang/en.json` - Added project translations
- `lang/mk.json` - Added Macedonian project translations

### Phase 1.2 - Proforma Invoice
**Status:** Already fully implemented
- Model, Controller, Request, Resource, Policy all exist
- Routes configured at `/api/v1/proforma-invoices`
- Convert to invoice functionality works

### Phase 1.3 - Expense Duplicate Protection
**Files Created:**
- `database/migrations/2025_11_30_120003_add_expense_duplicate_fields.php`

**Files Modified:**
- `app/Models/Expense.php` - Added supplier relation, duplicate check methods
- `app/Http/Controllers/V1/Admin/Expense/ExpensesController.php` - Added duplicate checking
- `app/Http/Requests/ExpenseRequest.php` - Added supplier_id, invoice_number, allow_duplicate fields
- `lang/en.json` - Added duplicate warning translations
- `lang/mk.json` - Added Macedonian duplicate warning translations

---

## Remaining Frontend Work

### Phase 1.1 - Projects (Vue Pages Needed)
- [x] Projects list page (`resources/scripts/admin/views/projects/Index.vue`) - DONE
- [x] Project create/edit form (`resources/scripts/admin/views/projects/Create.vue`) - DONE
- [x] Project view page (`resources/scripts/admin/views/projects/View.vue`) - DONE
- [x] Add project dropdown to invoice/expense/payment forms - DONE
- [ ] Project Overview report page

### Phase 1.3 - Duplicate Protection (Frontend Modal Needed)
- [x] Update expense store to handle duplicate warning response - DONE
- [x] Add duplicate warning modal component - DONE
- [x] Update expense form to show modal and allow override - DONE

---

## Additional Files Created (Frontend - 2025-11-30)

### Phase 1.1 - Projects (Vue Frontend)
**Files Created:**
- `resources/scripts/admin/views/projects/Index.vue` - Projects list page with filtering
- `resources/scripts/admin/views/projects/Create.vue` - Project create/edit form
- `resources/scripts/admin/views/projects/View.vue` - Project detail page with financial summary
- `resources/scripts/admin/components/dropdowns/ProjectIndexDropdown.vue` - Actions dropdown
- `resources/scripts/components/base/BaseProjectSelectInput.vue` - Reusable project select component

**Files Modified:**
- `resources/scripts/admin/stub/abilities.js` - Added PROJECT abilities
- `resources/scripts/admin/admin-router.js` - Added project routes
- `resources/scripts/admin/stub/invoice.js` - Added project_id field
- `resources/scripts/admin/stub/expense.js` - Added project_id field
- `resources/scripts/admin/stub/payment.js` - Added project_id field
- `resources/scripts/admin/views/invoices/create/InvoiceCreateBasicFields.vue` - Added project dropdown
- `resources/scripts/admin/views/expenses/Create.vue` - Added project dropdown + duplicate warning
- `resources/scripts/admin/views/payments/Create.vue` - Added project dropdown
- `config/invoiceshelf.php` - Added Projects navigation menu item

### Phase 1.3 - Expense Duplicate Protection (Vue Frontend)
**Files Created:**
- `resources/scripts/admin/components/modal-components/ExpenseDuplicateWarningModal.vue` - Warning modal

**Files Modified:**
- `resources/scripts/admin/stores/expense.js` - Added duplicate handling in addExpense/updateExpense
- `resources/scripts/admin/views/expenses/Create.vue` - Added duplicate warning modal integration

### Phase 1.2 - Proforma Invoice (Vue Frontend - Completed 2025-12-01)
**Files Created:**
- `resources/scripts/admin/stub/proforma-invoice.js` - Default proforma invoice data structure
- `resources/scripts/admin/stores/proforma-invoice.js` - Pinia store with CRUD actions + convert to invoice
- `resources/scripts/admin/views/proforma-invoices/Index.vue` - List page with filtering
- `resources/scripts/admin/views/proforma-invoices/View.vue` - Detail page with sidebar navigation
- `resources/scripts/admin/views/proforma-invoices/create/ProformaInvoiceCreate.vue` - Create/edit form
- `resources/scripts/admin/views/proforma-invoices/create/ProformaInvoiceCreateBasicFields.vue` - Form fields component
- `resources/scripts/admin/components/dropdowns/ProformaInvoiceIndexDropdown.vue` - Actions dropdown

**Files Modified:**
- `resources/scripts/admin/admin-router.js` - Added proforma invoice routes
- `config/invoiceshelf.php` - Added Proforma Invoices navigation menu item
- `lang/en.json` - Added proforma_invoices translations (navigation + section)
- `lang/mk.json` - Added Macedonian proforma_invoices translations

---

## Completed Work Summary (2025-12-01) - Phase 2 Stock Module

### Phase 2 - Stock Module Implementation

**Backend (Already Existed - Verified Working):**
- `app/Models/Warehouse.php` - Warehouse model with company scoping
- `app/Models/StockMovement.php` - Stock movement tracking
- `app/Services/StockService.php` - Core stock service with WAC valuation
- `app/Services/InvoiceProfitService.php` - COGS calculation for invoices
- `app/Http/Controllers/V1/Admin/Stock/StockReportsController.php` - Item card, inventory reports
- `app/Http/Controllers/V1/Admin/Stock/WarehouseController.php` - Warehouse CRUD
- `app/Observers/StockBillItemObserver.php` - Auto-create stock movements from bills
- `app/Observers/StockInvoiceItemObserver.php` - Auto-create stock movements from invoices

**Frontend (Created 2025-12-01):**
- `resources/scripts/admin/stores/stock.js` - Pinia store for stock data
- `resources/scripts/admin/stores/warehouse.js` - Pinia store for warehouses
- `resources/scripts/admin/views/stock/Inventory.vue` - Stock inventory dashboard
- `resources/scripts/admin/views/stock/LowStock.vue` - Low stock alerts page
- `resources/scripts/admin/views/stock/warehouses/Index.vue` - Warehouse list
- `resources/scripts/admin/views/stock/warehouses/Create.vue` - Warehouse form
- `resources/scripts/admin/components/dropdowns/WarehouseIndexDropdown.vue` - Actions dropdown

**Configuration & Fixes Applied:**
- Added `FACTURINO_STOCK_V1_ENABLED=false` to `.env.example`
- Fixed `StockService::isEnabled()` to use `config()` instead of `env()`
- Added feature flag check to all `WarehouseController` methods
- Fixed test files to use `config()` for feature flag overrides
- Added stock/warehouse translations to `lang/en.json` and `lang/mk.json`
- Added navigation menu items for Stock and Warehouses

**Tests:**
- 37 stock-related tests passing (225 assertions)
- Feature flag tests working correctly
- Authorization tests passing

---

## Completed Work Summary (2025-12-02) - Phase 2.5 Stock Operations

### Phase 2.5 - Stock Operations & Validation

**Files Created:**
- `resources/scripts/admin/views/stock/Adjustments.vue` - Stock adjustments and transfers UI page (~450 lines)
- `app/Console/Commands/CreateRetroactiveStockMovements.php` - Artisan command for migrating existing bills/invoices

**Files Modified:**

*Backend:*
- `app/Http/Requests/InvoicesRequest.php` - Added stock validation to prevent overselling
- `app/Models/Item.php` - Added initial stock entry support in createItem()

*Frontend:*
- `resources/scripts/admin/stores/stock.js` - Added adjustment/transfer API methods:
  - `fetchAdjustments(params)` - List stock adjustments
  - `createAdjustment(data)` - Create manual stock adjustment
  - `deleteAdjustment(id)` - Reverse a stock adjustment
  - `fetchTransfers(params)` - List warehouse transfers
  - `createTransfer(data)` - Create warehouse transfer
  - `createInitialStock(data)` - Record initial stock
  - `getItemStock(itemId, warehouseId)` - Get item stock for validation
- `resources/scripts/admin/admin-router.js` - Added stock adjustments route
- `resources/scripts/admin/views/items/Create.vue` - Added initial stock fields for new items
- `resources/scripts/admin/components/dropdowns/ItemIndexDropdown.vue` - Added "View Stock" action
- `resources/scripts/admin/views/stock/ItemCard.vue` - Added query param support for item_id
- `resources/scripts/admin/views/settings/PreferencesSetting.vue` - Added allow_negative_stock toggle

*Translations (lang/en.json & lang/mk.json):*
- Added ~40 new translation keys for stock operations

**Key Features Implemented:**

1. **Stock Adjustments UI** (`/admin/stock/adjustments`)
   - Tab-based interface for Adjustments and Transfers
   - Create adjustments with quantity (+/-), reason, notes
   - View current stock when selecting item/warehouse
   - Reverse adjustments functionality
   - Create transfers between warehouses
   - Paginated lists with date filtering

2. **Negative Stock Prevention**
   - Validates stock availability before invoice creation
   - Per-company setting: `allow_negative_stock` (YES/NO)
   - Shows specific error messages per item with available vs requested quantities
   - Handles invoice updates correctly (accounts for existing item quantities)

3. **Initial Stock Entry**
   - When creating new items with track_quantity enabled
   - Fields: warehouse, quantity, unit_cost
   - Creates initial stock movement automatically

4. **View Stock from Items**
   - "View Stock" action in item list dropdown
   - Navigates directly to Item Card page with item pre-selected

5. **Retroactive Stock Movements Command**
   - `php artisan stock:create-retroactive`
   - Options: `--company=ID`, `--type=bills|invoices`, `--dry-run`, `--force`
   - Creates stock movements for bills/invoices created before stock module enabled
   - Shows preview summary before processing
   - Progress bars and detailed statistics

**Testing Instructions:**

1. **Enable Stock Module:**
   ```bash
   # Add to .env
   FACTURINO_STOCK_V1_ENABLED=true
   ```

2. **Test Stock Adjustments:**
   - Navigate to Stock > Adjustments
   - Create a manual adjustment (positive or negative)
   - Try to reverse an adjustment
   - Create a warehouse transfer

3. **Test Negative Stock Prevention:**
   - Create an invoice with an item that tracks quantity
   - Try to invoice more than available stock
   - Should see validation error with available quantity
   - Toggle "Allow Negative Stock" in Settings > Preferences to override

4. **Test Initial Stock Entry:**
   - Create a new item with "Track Inventory" enabled
   - Fill in initial stock fields (warehouse, qty, cost)
   - Check Item Card to verify stock was recorded

5. **Test Retroactive Stock Movements:**
   ```bash
   # Dry run first
   php artisan stock:create-retroactive --dry-run

   # Execute for real
   php artisan stock:create-retroactive --force
   ```

---

---

## Roadmap Update (2025-12-12) - Phase 3 Restructured

### Changes Made
- **Removed**: Phase 3.1 Daily Closing (5 tasks) - deemed unnecessary for modern SaaS
- **Discovered**: Period locking infrastructure already fully built (11 components)
- **Remaining**: Only enforcement validation needs implementation (9 tasks)

### Rationale
Daily closing is an outdated concept from paper ledger systems. Modern cloud accounting
software (Xero, QuickBooks, FreshBooks, Wave) uses period locking instead:
- Single "lock date" setting vs daily ritual
- Less friction for users who need to correct mistakes
- Monthly close aligns with Macedonian DDV (VAT) reporting
- Same audit protection, simpler UX

### Current Task Summary
| Category | Tasks Done | Tasks Remaining |
|----------|------------|-----------------|
| Phase 1 (Projects, Proforma, Duplicates) | 45 | 0 |
| Phase 2 (Stock Module) | 36 | 0 |
| Phase 3 (Period Locking) | 20 | 0 |
| Phase 4.1 (Bug Fixes) | 0 | 3 |
| Phase 4.2 (COA Infrastructure) | 10 | 0 |
| Phase 4.3 (Partner Backend) | 0 | 10 |
| Phase 4.4 (Partner Frontend) | 0 | 12 |
| Phase 4.5 (Testing) | 0 | 7 |
| Phase 4.6 (Integration) | 0 | 4 |
| Phase 5 (Zonel) | 0 | 2 |
| **Total** | **111** | **38** |

---

## Completed Work Summary (2025-12-12) - Phase 3 Period Lock Enforcement

### Phase 3.2 - Period Lock Enforcement

**Files Modified:**
- `app/Http/Requests/InvoicesRequest.php` - Added `validatePeriodLock()` method
- `app/Http/Requests/ExpenseRequest.php` - Added `validatePeriodLock()` method
- `app/Http/Requests/PaymentRequest.php` - Added `validatePeriodLock()` method
- `app/Http/Requests/DeleteInvoiceRequest.php` - Added period lock check for deletions
- `app/Http/Requests/DeleteExpensesRequest.php` - Added period lock check for deletions
- `app/Http/Requests/DeletePaymentsRequest.php` - Added period lock check for deletions
- `lang/en.json` - Added 3 new translation keys for period lock errors
- `lang/mk.json` - Added Macedonian translations for period lock errors

**Files Created:**
- `tests/Feature/PeriodLockEnforcementTest.php` - 16 test cases for enforcement

**Key Features Implemented:**

1. **Create Blocking**: Cannot create invoices/expenses/payments with dates in locked periods
2. **Update Blocking**: Cannot update documents to locked dates, or edit documents with original dates in locked periods
3. **Delete Blocking**: Cannot delete documents with dates in locked periods
4. **Error Messages**: Clear validation errors in English and Macedonian
5. **Company Isolation**: Period locks only affect the company they belong to

**Translation Keys Added:**
- `period_lock.date_is_locked` - Error when trying to save to a locked date
- `period_lock.original_date_locked` - Error when trying to edit a document with locked original date
- `period_lock.cannot_delete_locked` - Error when trying to delete a document in locked period

---

---

## Phase 4 Audit Summary (2025-12-12)

### Existing Accounting Infrastructure (Admin-Only)

**Models:**
- `app/Models/Account.php` - Hierarchical accounts with parent_id, type, code, name
- `app/Models/AccountMapping.php` - Polymorphic mapping (mappable_type/id → account_id)

**Services:**
- `app/Services/AccountSuggestionService.php` - Learning-based AI suggestions
- `app/Services/JournalExportService.php` - Multi-format export (HAS BUG)

**Controllers (Admin):**
- `app/Http/Controllers/V1/Admin/Accounting/AccountController.php`
- `app/Http/Controllers/V1/Admin/Accounting/AccountMappingController.php`
- `app/Http/Controllers/V1/Admin/Accounting/JournalExportController.php`

**Vue Components (Admin):**
- `resources/scripts/admin/views/settings/ChartOfAccountsSetting.vue`
- `resources/scripts/admin/views/settings/JournalExportSetting.vue`

### Critical Bug Detail

**File:** `app/Services/JournalExportService.php`
**Line:** ~247
**Issue:** Query references `mapping_type` column that does not exist in `account_mappings` table

```php
// Current broken code
->where('mapping_type', $type)

// Table schema (from migration)
Schema::create('account_mappings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id');
    $table->foreignId('account_id');
    $table->morphs('mappable');  // Creates mappable_type, mappable_id
    // NO mapping_type column!
});
```

**Fix Options:**
1. Change query to use `mappable_type` instead of `mapping_type`
2. Add `mapping_type` column via migration (not recommended - redundant)

### Partner Portal Gap Analysis

Partners currently have access to:
- ✅ Company list (`/v1/partner/companies`)
- ✅ Commission tracking (`/v1/partner/commissions`)
- ✅ Basic dashboard

Partners currently CANNOT access:
- ❌ Chart of Accounts
- ❌ Account Mappings
- ❌ Journal Entries
- ❌ Journal Export
- ❌ Invoices/Expenses of linked companies
- ❌ AI Account Suggestions

This is the core gap Phase 4 addresses.

---

_Last updated: 2025-12-12 (Phase 4 Plan Added)_
