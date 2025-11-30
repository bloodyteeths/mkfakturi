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
| STK-01 | Create warehouses table migration | Backend | Migration file | Pending | |
| STK-02 | Create stock_movements table migration | Backend | Migration file | Pending | |
| STK-03 | Create Warehouse model | Backend | `app/Models/Warehouse.php` | Pending | |
| STK-04 | Create StockMovement model | Backend | `app/Models/StockMovement.php` | Pending | |
| STK-05 | Create StockService | Backend | `app/Services/StockService.php` | Pending | |
| STK-06 | Add FACTURINO_STOCK_V1_ENABLED flag | Backend | `.env`, `config/facturino.php` | Pending | |
| STK-07 | Write unit tests for StockService | QA | `tests/Unit/StockServiceTest.php` | Pending | |

### 2.2 Valuation (Weighted Average)

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| VAL-01 | Implement weighted average calculation | Backend | In StockService | Pending | |
| VAL-02 | Add valuation_strategy to company settings | Backend | Migration + model update | Pending | |
| VAL-03 | Implement COGS calculation per invoice | Backend | Service method | Pending | |
| VAL-04 | Write valuation unit tests | QA | Test file | Pending | |

### 2.3 Stock Reports

| Task ID | Title | Agent | Files | Status | Notes |
|---------|-------|-------|-------|--------|-------|
| SRPT-01 | Item stock card report backend | Backend | Controller | Pending | |
| SRPT-02 | Warehouse inventory report backend | Backend | Controller | Pending | |
| SRPT-03 | Inventory list export (PDF/Excel) | Backend | Service | Pending | |
| SRPT-04 | Stock reports frontend | Frontend | Vue pages | Pending | |

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
| Phase 1.1 - Projects | Backend Complete | 2025-11-30 | 2025-11-30 | Backend, API, translations done. Frontend pages needed. |
| Phase 1.2 - Proforma | Complete | Prior | Prior | Already fully implemented in codebase |
| Phase 1.3 - Duplicate Protection | Backend Complete | 2025-11-30 | 2025-11-30 | Backend logic done. Frontend modal needed. |
| Phase 2.1 - Stock Foundation | Pending | | | |
| Phase 2.2 - Valuation | Pending | | | |
| Phase 2.3 - Stock Reports | Pending | | | |
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
- [ ] Projects list page (`resources/scripts/admin/views/projects/Index.vue`)
- [ ] Project create/edit form (`resources/scripts/admin/views/projects/Create.vue`)
- [ ] Project view page (`resources/scripts/admin/views/projects/View.vue`)
- [ ] Add project dropdown to invoice/expense/payment forms
- [ ] Project Overview report page

### Phase 1.3 - Duplicate Protection (Frontend Modal Needed)
- [ ] Update expense store to handle duplicate warning response
- [ ] Add duplicate warning modal component
- [ ] Update expense form to show modal and allow override

---

_Last updated: 2025-11-30_
