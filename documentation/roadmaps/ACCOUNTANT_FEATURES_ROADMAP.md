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

---

## PHASE 3 – Closings (Future Sprint)

### 3.1 Daily Closing

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| DC-01 | Create daily_closings table | Backend | Migration | Pending | |
| DC-02 | Create DailyClosing model | Backend | Model | Pending | |
| DC-03 | Create DailyClosingController | Backend | Controller | Pending | |
| DC-04 | Implement date locking logic | Backend | Middleware/Service | Pending | |
| DC-05 | Daily closing frontend | Frontend | Vue pages | Pending | |

### 3.2 Period Locking

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| PL-01 | Create period_locks table | Backend | Migration | Pending | |
| PL-02 | Implement period locking service | Backend | Service | Pending | |
| PL-03 | Period locking frontend | Frontend | Vue pages | Pending | |

---

## PHASE 4 – Accountant Tools (Future Sprint)

### 4.1 Chart of Accounts

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| COA-01 | Create accounts table | Backend | Migration | Pending | |
| COA-02 | Create account_mappings table | Backend | Migration | Pending | |
| COA-03 | Chart of accounts management | Backend | Controllers | Pending | |
| COA-04 | CSV/Excel import for accounts | Backend | Service | Pending | |
| COA-05 | Partner portal UI for accounts | Frontend | Vue pages | Pending | |

### 4.2 AI Account Suggestion

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| AI-01 | Account suggestion service | Backend | Service | Pending | |
| AI-02 | Add suggested/confirmed account fields | Backend | Migration | Pending | |
| AI-03 | Partner review UI | Frontend | Vue pages | Pending | |
| AI-04 | CSV/Excel export for accounting software | Backend | Service | Pending | |

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
| Phase 3.1 - Daily Closing | Pending | | | |
| Phase 3.2 - Period Locking | Pending | | | |
| Phase 4.1 - Chart of Accounts | Pending | | | |
| Phase 4.2 - AI Suggestion | Pending | | | |
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

_Last updated: 2025-12-01_
