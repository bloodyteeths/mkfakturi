# Facturino Feature Gap Roadmap: Pantheon Parity + Cloud Advantage

> Generated 2026-03-07 from parallel codebase audit + web research (QuickBooks, Xero, Pantheon, Expensify, Chaser, Fathom, etc.)

---

## Executive Summary

12 features needed to reach full Pantheon parity and beyond. Organized in 4 priority tiers across ~6 months.

| # | Feature | Priority | Effort | Tier Req | AI Features |
|---|---------|----------|--------|----------|-------------|
| **F1** | Compensation (Kompenzacija) | **P0 CRITICAL** | 2 weeks | Standard+ | Auto-match suggestions |
| **F2** | Payment Orders (Nalozi) | **P0 CRITICAL** | 3 weeks | Standard+ | Optimal payment timing |
| **F3** | Cost Centers / Departments | **P0 CRITICAL** | 3 weeks | Standard+ | Auto-assign from rules + AI |
| **F4** | Late Interest Calculation | **P1 HIGH** | 1.5 weeks | Standard+ | Payment prediction |
| **F5** | Collections / Reminders | **P1 HIGH** | 1.5 weeks | Starter+ | Optimal send timing |
| **F6** | Purchase Orders | **P1 HIGH** | 3 weeks | Starter+ | Reorder predictions |
| **F7** | Budgeting & Planning | **P1 HIGH** | 3 weeks | Standard+ | AI budget generation |
| **F8** | Travel Expenses (Patni Nalozi) | **P2 MEDIUM** | 2.5 weeks | Standard+ | Receipt OCR + categorize |
| **F9** | BI Dashboards | **P2 MEDIUM** | 2 weeks | Business+ | Natural language queries |
| **F10** | Batch Operations (Partner) | **P2 MEDIUM** | 1 week | Partner | Queue-based bulk actions |
| **F11** | Custom Report Builder | **P3 LOW** | 2 weeks | Standard+ | - |
| **F12** | Consolidation (Enhanced) | **P3 LOW** | 1.5 weeks | Max/Partner | Auto intercompany detection |

**Total: ~26 weeks across 4 phases**

---

## Phase 1: Dealbreaker Fixes (Weeks 1-8) -- F1, F2, F3

These 3 features are why accountants say "I can't switch from Pantheon yet."

---

### F1: Compensation / Mutual Offset (Kompenzacija)

**Why critical:** Every MK business does kompenzacija weekly. It's how companies offset mutual receivables/payables. Without it, accountants literally cannot operate.

**Legal requirements (MK Закон за облигациони односи, Art. 325-334):**
- Bilateral (договорна) or unilateral (изјава) compensation
- Both claims must be due and payable
- Must be documented with: parties, invoice references, amounts, date, signatures
- VAT is NOT affected -- compensation is a payment method, not a new transaction
- Legal document must show: company names, EDB numbers, invoice references per side, offset amount, remaining balances

**Current state:** Zero -- no compensation models, controllers, or UI exist.

**Integration points:**
- `Invoice.php` (receivables) + `Bill.php` (payables) are the source documents
- `IfrsAdapter::subLedger()` provides open items per counterparty (IOS) -- perfect foundation
- `Payment.php` model handles invoice payments -- compensation creates a special payment type
- Partner `IOSStatement.vue` already shows open items -- add "Create Compensation" button

#### Database Schema

```sql
CREATE TABLE compensations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    compensation_number VARCHAR(50) NOT NULL,       -- KOMP-2026-000001
    compensation_date DATE NOT NULL,
    counterparty_type ENUM('customer','supplier','both') NOT NULL,
    customer_id BIGINT UNSIGNED NULL,
    supplier_id BIGINT UNSIGNED NULL,
    type ENUM('bilateral','unilateral') DEFAULT 'bilateral',
    status ENUM('draft','confirmed','cancelled') DEFAULT 'draft',
    total_amount BIGINT UNSIGNED NOT NULL DEFAULT 0,  -- offset amount in cents
    currency_id INT UNSIGNED NULL,
    notes TEXT NULL,
    -- Our receivables (invoices the counterparty owes us)
    receivables_total BIGINT UNSIGNED DEFAULT 0,
    -- Our payables (bills we owe the counterparty)
    payables_total BIGINT UNSIGNED DEFAULT 0,
    -- Remaining after offset
    receivables_remaining BIGINT UNSIGNED DEFAULT 0,
    payables_remaining BIGINT UNSIGNED DEFAULT 0,
    -- IFRS
    ifrs_transaction_id BIGINT UNSIGNED NULL,
    -- Metadata
    created_by INT UNSIGNED NULL,
    confirmed_by INT UNSIGNED NULL,
    confirmed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
    INDEX idx_comp_company_date (company_id, compensation_date),
    INDEX idx_comp_customer (customer_id),
    INDEX idx_comp_supplier (supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE compensation_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    compensation_id BIGINT UNSIGNED NOT NULL,
    side ENUM('receivable','payable') NOT NULL,
    document_type ENUM('invoice','bill','credit_note') NOT NULL,
    document_id BIGINT UNSIGNED NOT NULL,
    document_number VARCHAR(100) NULL,
    document_date DATE NULL,
    document_total BIGINT UNSIGNED NOT NULL,         -- original amount
    amount_offset BIGINT UNSIGNED NOT NULL,          -- amount being offset
    remaining_after BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (compensation_id) REFERENCES compensations(id) ON DELETE CASCADE,
    INDEX idx_ci_comp (compensation_id),
    INDEX idx_ci_doc (document_type, document_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Backend

```
Modules/Mk/Models/Compensation.php
Modules/Mk/Models/CompensationItem.php
Modules/Mk/Services/CompensationService.php
Modules/Mk/Http/Controllers/CompensationController.php
Modules/Mk/Http/Controllers/Partner/PartnerCompensationController.php
```

**Key service methods:**
- `getEligibleDocuments(company, counterpartyId)` -- returns unpaid invoices + bills for the counterparty
- `create(company, data)` -- create draft compensation with selected documents
- `confirm(compensation)` -- post to GL: debit payable account (2200), credit receivable account (1200)
- `generatePdf(compensation)` -- legal format PDF with both parties' details
- `aiSuggestMatches(company, counterpartyId)` -- auto-match invoices/bills by amount proximity
- `getCompensationOpportunities(company)` -- scan all counterparties for offsettable balances

**GL Posting (on confirm):**
```
Debit:  2200 Payables (reduce our obligation)     amount_offset
Credit: 1200 Receivables (reduce their debt)       amount_offset
```

#### Frontend

```
resources/scripts/admin/views/compensations/
  Index.vue         -- List with status filters + "Compensation Opportunities" AI badge
  Create.vue        -- Wizard: select counterparty -> see open items -> match -> confirm
  View.vue          -- Detail + PDF preview

resources/scripts/admin/views/partner/accounting/
  Compensations.vue -- Partner view of client compensations
```

**Create Wizard UX (3 steps):**
1. **Select Counterparty** -- Search customers/suppliers. Show badge: "3 compensation opportunities" (AI-detected)
2. **Match Documents** -- Two columns: left = our receivables (invoices they owe us), right = our payables (bills we owe them). AI pre-selects best matches. User adjusts amounts. Running total shows offset amount.
3. **Review & Confirm** -- Summary, PDF preview, confirm button. On confirm: GL posting + mark partial payments on invoices/bills.

**AI Feature:** `CompensationSuggestionService` scans all counterparties monthly, finds those with both open receivables and payables > 1000 MKD, suggests compensations. Shows as notification badge on dashboard.

#### i18n Keys

```json
{
  "compensations": {
    "title": { "mk": "Компензации", "en": "Compensations", "tr": "Mahsuplasmalar", "sq": "Kompensime" },
    "create": { "mk": "Нова компензација", "en": "New Compensation", "tr": "Yeni Mahsuplasma", "sq": "Kompensim i Ri" },
    "number": { "mk": "Број", "en": "Number", "tr": "Numara", "sq": "Numri" },
    "date": { "mk": "Датум", "en": "Date", "tr": "Tarih", "sq": "Data" },
    "counterparty": { "mk": "Комитент", "en": "Counterparty", "tr": "Karsi Taraf", "sq": "Pala Tjetra" },
    "bilateral": { "mk": "Договорна", "en": "Bilateral", "tr": "Iki Tarafli", "sq": "Dypalesh" },
    "unilateral": { "mk": "Еднострана", "en": "Unilateral", "tr": "Tek Tarafli", "sq": "Njeanesh" },
    "our_receivables": { "mk": "Наши побарувања", "en": "Our Receivables", "tr": "Alacaklarimiz", "sq": "Arketueshmet Tona" },
    "our_payables": { "mk": "Наши обврски", "en": "Our Payables", "tr": "Borclarimiz", "sq": "Detyrimet Tona" },
    "offset_amount": { "mk": "Износ на компензација", "en": "Offset Amount", "tr": "Mahsup Tutari", "sq": "Shuma e Kompensimit" },
    "remaining_receivable": { "mk": "Остаток побарување", "en": "Remaining Receivable", "tr": "Kalan Alacak", "sq": "Arketueshme e Mbetur" },
    "remaining_payable": { "mk": "Остаток обврска", "en": "Remaining Payable", "tr": "Kalan Borc", "sq": "Detyrim i Mbetur" },
    "confirm": { "mk": "Потврди компензација", "en": "Confirm Compensation", "tr": "Mahsuplamayi Onayla", "sq": "Konfirmo Kompensimin" },
    "opportunities": { "mk": "Можности за компензација", "en": "Compensation Opportunities", "tr": "Mahsuplasma Firsatlari", "sq": "Mundesi Kompensimi" },
    "generate_pdf": { "mk": "Генерирај документ", "en": "Generate Document", "tr": "Belge Olustur", "sq": "Gjenero Dokumentin" },
    "status_draft": { "mk": "Нацрт", "en": "Draft", "tr": "Taslak", "sq": "Draft" },
    "status_confirmed": { "mk": "Потврдена", "en": "Confirmed", "tr": "Onaylandi", "sq": "Konfirmuar" },
    "status_cancelled": { "mk": "Откажана", "en": "Cancelled", "tr": "Iptal", "sq": "Anuluar" }
  }
}
```

---

### F2: Payment Orders (Nalozi za Plakjanje)

**Why critical:** Accountants create bank payment orders from bills every single day. Without this, they must manually type PP30 forms in the bank's portal for each bill.

**MK bank payment formats:**
- **PP30** -- Domestic MKD transfer (mandatory fields: debtor name/account/bank, creditor name/account/bank, amount, purpose code, payment reference)
- **PP50** -- Public revenue payments (taxes, social contributions)
- **SEPA SCT** -- pain.001.001.03 XML for EUR/foreign currency

**Current state:** `BankPaymentFileService.php` already generates SEPA XML for payroll. We need to extend this for general AP payments.

**Integration points:**
- `Bill.php` model has `due_date`, `status`, `due_amount` -- select unpaid bills
- `BankPaymentFileService.php` (payroll SEPA) -- extract shared `SepaXmlBuilder`
- `ReconciliationController.php` -- extend to match outgoing bank transactions to payment batch items
- Supplier model needs IBAN/BIC fields (migration to add)

#### Database Schema

```sql
CREATE TABLE payment_batches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    batch_number VARCHAR(50) NOT NULL,
    batch_date DATE NOT NULL,                        -- execution date
    bank_account_id BIGINT UNSIGNED NULL,           -- company bank account
    format ENUM('pp30','pp50','sepa_sct','csv') DEFAULT 'pp30',
    status ENUM('draft','pending_approval','approved','exported','sent_to_bank','confirmed','cancelled') DEFAULT 'draft',
    total_amount BIGINT UNSIGNED DEFAULT 0,
    item_count INT UNSIGNED DEFAULT 0,
    currency_id INT UNSIGNED NULL,
    exported_at TIMESTAMP NULL,
    exported_file_path VARCHAR(500) NULL,
    notes TEXT NULL,
    created_by INT UNSIGNED NULL,
    approved_by INT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
    INDEX idx_pb_company_status (company_id, status),
    INDEX idx_pb_date (batch_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payment_batch_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_batch_id BIGINT UNSIGNED NOT NULL,
    bill_id INT UNSIGNED NULL,
    creditor_name VARCHAR(255) NOT NULL,
    creditor_iban VARCHAR(34) NULL,
    creditor_bic VARCHAR(11) NULL,
    creditor_bank_name VARCHAR(255) NULL,
    amount BIGINT UNSIGNED NOT NULL,
    currency_code VARCHAR(3) DEFAULT 'MKD',
    purpose_code VARCHAR(10) NULL,                   -- PP30 purpose codes
    payment_reference VARCHAR(50) NULL,
    description VARCHAR(140) NULL,
    status ENUM('pending','exported','confirmed','failed') DEFAULT 'pending',
    reconciled_at TIMESTAMP NULL,
    bank_transaction_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (payment_batch_id) REFERENCES payment_batches(id) ON DELETE CASCADE,
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE SET NULL,
    INDEX idx_pbi_batch (payment_batch_id),
    INDEX idx_pbi_bill (bill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add banking fields to suppliers
ALTER TABLE suppliers
    ADD COLUMN iban VARCHAR(34) NULL AFTER phone,
    ADD COLUMN bic VARCHAR(11) NULL AFTER iban,
    ADD COLUMN bank_name VARCHAR(255) NULL AFTER bic;
```

#### Backend

```
Modules/Mk/Models/PaymentBatch.php
Modules/Mk/Models/PaymentBatchItem.php
Modules/Mk/Services/PaymentOrderService.php       -- Core logic
Modules/Mk/Services/Pp30FileBuilder.php            -- PP30 format
Modules/Mk/Services/Pp50FileBuilder.php            -- PP50 format
Modules/Mk/Services/SepaXmlBuilder.php             -- Extracted from BankPaymentFileService
Modules/Mk/Services/PaymentBatchReconciler.php     -- Match bank txns to batch items
Modules/Mk/Http/Controllers/PaymentOrderController.php
Modules/Mk/Http/Controllers/Partner/PartnerPaymentOrderController.php
```

**Key workflow:**
1. Select unpaid bills (filter by due date, supplier, amount)
2. System pre-fills creditor details from supplier IBAN
3. Choose format (PP30 for domestic MKD, SEPA for EUR)
4. Review batch -> approve -> export file
5. Upload file to bank portal (or future: API push)
6. When bank statement arrives, auto-match debits to batch items -> confirm payments

**AI Feature:** `PaymentTimingOptimizer` -- analyzes cash flow projection + bill due dates, suggests optimal payment date to maximize cash position while avoiding late fees. "Pay these 3 bills on the 15th, these 5 on the 25th."

#### Frontend

```
resources/scripts/admin/views/payment-orders/
  Index.vue         -- List batches + status pipeline
  Create.vue        -- Bill selector + batch builder
  View.vue          -- Batch detail + export + reconciliation status
  Calendar.vue      -- Calendar view of upcoming payments

resources/scripts/admin/views/partner/accounting/
  PaymentOrders.vue -- Partner view
```

**Create Flow (no-brainer UX):**
1. Click "Pay Bills" button (or "Pay All Due" shortcut)
2. See all unpaid bills sorted by due date. Overdue = red, due this week = yellow
3. Check boxes to select. Running total updates.
4. Click "Create Payment Order" -- auto-fills bank details from suppliers
5. Choose format, review, export. Done in < 2 minutes.

#### i18n Keys

```json
{
  "payment_orders": {
    "title": { "mk": "Налози за плаќање", "en": "Payment Orders", "tr": "Odeme Emirleri", "sq": "Urdherat e Pagesave" },
    "new_batch": { "mk": "Нов налог", "en": "New Payment Order", "tr": "Yeni Odeme Emri", "sq": "Urdher i Ri" },
    "pay_all_due": { "mk": "Плати ги сите доспеани", "en": "Pay All Due", "tr": "Tum Vadesi Gecenleri Ode", "sq": "Paguaj te Gjitha" },
    "select_bills": { "mk": "Изберете сметки", "en": "Select Bills", "tr": "Faturalari Secin", "sq": "Zgjidhni Faturat" },
    "export_file": { "mk": "Експортирај датотека", "en": "Export File", "tr": "Dosyayi Aktar", "sq": "Eksporto Dosjen" },
    "pp30": { "mk": "ПП30 (домашен)", "en": "PP30 (Domestic)", "tr": "PP30 (Yurt Ici)", "sq": "PP30 (Vendas)" },
    "pp50": { "mk": "ПП50 (јавни приходи)", "en": "PP50 (Public Revenue)", "tr": "PP50 (Kamu)", "sq": "PP50 (Publike)" },
    "sepa": { "mk": "SEPA трансфер", "en": "SEPA Transfer", "tr": "SEPA Transferi", "sq": "Transfer SEPA" },
    "batch_number": { "mk": "Број на налог", "en": "Batch Number", "tr": "Parti No", "sq": "Nr Grupit" },
    "execution_date": { "mk": "Датум на извршување", "en": "Execution Date", "tr": "Islem Tarihi", "sq": "Data Ekzekutimit" },
    "creditor": { "mk": "Доверител", "en": "Creditor", "tr": "Alacakli", "sq": "Kreditor" },
    "purpose": { "mk": "Цел на дознака", "en": "Purpose", "tr": "Odeme Amaci", "sq": "Qellimi" },
    "calendar": { "mk": "Календар на плаќања", "en": "Payment Calendar", "tr": "Odeme Takvimi", "sq": "Kalendar Pagesash" },
    "confirm_payment": { "mk": "Потврди плаќање", "en": "Confirm Payment", "tr": "Odemeyi Onayla", "sq": "Konfirmo Pagesen" },
    "ai_suggestion": { "mk": "AI предлог за плаќање", "en": "AI Payment Suggestion", "tr": "AI Odeme Onerisi", "sq": "Sugjerim AI" }
  }
}
```

---

### F3: Cost Centers / Departments

**Why critical:** Any company with >5 employees tracks costs by department. Without this, P&L and management reports are flat -- accountants can't answer "how much does the sales department cost?"

**Best practice (Xero model):** "Tracking Categories" -- up to 2 dimensions, assignable per line item. Simple, not over-engineered.

**Current state:** `eloquent-ifrs` has NO built-in cost center support. The `ifrs_ledgers` table has no dimension column. We need to add it.

**Integration approach:** Add nullable `cost_center_id` to `ifrs_ledgers` table (post-INSERT UPDATE since vendor package doesn't pass custom columns). Filter all IfrsAdapter report methods by optional `cost_center_id`.

#### Database Schema

```sql
CREATE TABLE cost_centers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    parent_id BIGINT UNSIGNED NULL,                  -- hierarchical
    name VARCHAR(150) NOT NULL,
    code VARCHAR(20) NULL,                           -- e.g., "SALES", "OPS", "ADMIN"
    color VARCHAR(7) NULL DEFAULT '#6366f1',         -- for UI badges
    description TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
    FOREIGN KEY (parent_id) REFERENCES cost_centers(id) ON DELETE SET NULL,
    INDEX idx_cc_company (company_id),
    UNIQUE INDEX idx_cc_company_code (company_id, code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE cost_center_rules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    cost_center_id BIGINT UNSIGNED NOT NULL,
    match_type ENUM('vendor','account','description','item') NOT NULL,
    match_value VARCHAR(255) NOT NULL,               -- vendor_id, account prefix, keyword
    priority INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
    FOREIGN KEY (cost_center_id) REFERENCES cost_centers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add to IFRS ledger for filtering
ALTER TABLE ifrs_ledgers ADD COLUMN cost_center_id BIGINT UNSIGNED NULL;
ALTER TABLE ifrs_ledgers ADD INDEX idx_ledger_cc (cost_center_id);

-- Add to documents (header-level default)
ALTER TABLE invoices ADD COLUMN cost_center_id BIGINT UNSIGNED NULL;
ALTER TABLE bills ADD COLUMN cost_center_id BIGINT UNSIGNED NULL;
ALTER TABLE expenses ADD COLUMN cost_center_id BIGINT UNSIGNED NULL;
```

#### Backend

```
Modules/Mk/Models/CostCenter.php
Modules/Mk/Models/CostCenterRule.php
Modules/Mk/Services/CostCenterService.php          -- CRUD + rule matching
Modules/Mk/Services/CostCenterSuggestionService.php -- AI auto-assign
Modules/Mk/Http/Controllers/CostCenterController.php
Modules/Mk/Http/Controllers/Partner/PartnerCostCenterController.php
```

**Key integration:** Modify `IfrsAdapter` methods (trialBalance, generalLedger, subLedger, incomeStatement, balanceSheet, cashFlow) to accept optional `$costCenterId` parameter and add `WHERE cost_center_id = ?` to ledger queries.

**AI Feature:** On document creation, `CostCenterSuggestionService` checks rules first (vendor X always = "Marketing"), then falls back to AI: "Given expense 'Facebook Ads - March campaign' at vendor 'Meta', suggest cost center from [Sales, Marketing, Operations, Admin]." Auto-fills with subtle "AI suggested" badge.

#### i18n Keys

```json
{
  "cost_centers": {
    "title": { "mk": "Центри на трошоци", "en": "Cost Centers", "tr": "Maliyet Merkezleri", "sq": "Qendrat e Kostove" },
    "name": { "mk": "Назив", "en": "Name", "tr": "Ad", "sq": "Emri" },
    "code": { "mk": "Шифра", "en": "Code", "tr": "Kod", "sq": "Kodi" },
    "color": { "mk": "Боја", "en": "Color", "tr": "Renk", "sq": "Ngjyra" },
    "select": { "mk": "Избери центар на трошоци", "en": "Select Cost Center", "tr": "Maliyet Merkezi Secin", "sq": "Zgjidhni Qendren" },
    "all": { "mk": "Сите центри", "en": "All Centers", "tr": "Tum Merkezler", "sq": "Te Gjitha" },
    "filter_by": { "mk": "Филтрирај по центар", "en": "Filter by Center", "tr": "Merkeze Gore Filtrele", "sq": "Filtro sipas Qendres" },
    "rules": { "mk": "Правила за доделување", "en": "Assignment Rules", "tr": "Atama Kurallari", "sq": "Rregullat" },
    "auto_assigned": { "mk": "Автоматски доделено", "en": "Auto-assigned", "tr": "Otomatik Atandi", "sq": "Caktuar Automatik" },
    "bulk_assign": { "mk": "Масовно доделување", "en": "Bulk Assign", "tr": "Toplu Atama", "sq": "Caktim ne Mase" }
  }
}
```

---

## Phase 2: Competitive Advantage (Weeks 9-16) -- F4, F5, F6, F7

---

### F4: Late Interest Calculation (Камати)

**MK legal basis:** NBRM reference rate + 8% markup for commercial debt (Закон за облигациони односи, Art. 266). Current NBRM reference rate: ~5.25%.

**Current state:** Zero -- no interest calculation exists. `UnpaidSummaryWidget` shows overdue amounts but doesn't compute interest.

#### Database Schema

```sql
CREATE TABLE interest_calculations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    invoice_id INT UNSIGNED NULL,
    calculation_date DATE NOT NULL,
    principal_amount BIGINT UNSIGNED NOT NULL,        -- overdue amount (cents)
    days_overdue INT NOT NULL,
    annual_rate DECIMAL(5,2) NOT NULL,               -- e.g., 13.25 (5.25 + 8.00)
    interest_amount BIGINT UNSIGNED NOT NULL,         -- calculated interest (cents)
    status ENUM('calculated','invoiced','paid','waived') DEFAULT 'calculated',
    interest_invoice_id INT UNSIGNED NULL,            -- FK to invoice (if interest note generated)
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
    INDEX idx_ic_company (company_id),
    INDEX idx_ic_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Service:** `InterestCalculationService` -- batch calculates interest across all overdue invoices. Formula: `principal * (annual_rate/365) * days_overdue`. Generates interest notes (special invoice type). Auto-updates when NBRM publishes new rates.

**AI Feature:** Predict which customers will pay late based on historical payment patterns. Flag on creation: "This customer pays 15 days late on average -- consider shorter payment terms."

#### i18n Keys

```json
{
  "interest": {
    "title": { "mk": "Камати", "en": "Interest", "tr": "Faiz", "sq": "Interesi" },
    "calculate": { "mk": "Пресметај камата", "en": "Calculate Interest", "tr": "Faiz Hesapla", "sq": "Llogarit Interesin" },
    "annual_rate": { "mk": "Годишна стапка", "en": "Annual Rate", "tr": "Yillik Oran", "sq": "Norma Vjetore" },
    "days_overdue": { "mk": "Денови задоцнување", "en": "Days Overdue", "tr": "Gecikme Gunu", "sq": "Dite Vonese" },
    "interest_amount": { "mk": "Износ на камата", "en": "Interest Amount", "tr": "Faiz Tutari", "sq": "Shuma e Interesit" },
    "generate_note": { "mk": "Генерирај каматна белешка", "en": "Generate Interest Note", "tr": "Faiz Notu Olustur", "sq": "Gjenero Shenim Interesi" },
    "nbrm_rate": { "mk": "Референтна стапка НБРМ", "en": "NBRM Reference Rate", "tr": "NBRM Referans Orani", "sq": "Norma Referuese BPRM" },
    "waive": { "mk": "Откажи камата", "en": "Waive Interest", "tr": "Faizden Vazgec", "sq": "Hiq Interesin" }
  }
}
```

---

### F5: Collections / Payment Reminders

**Current state:** `OverdueInvoicesWidget` shows overdue amounts. `CheckInvoiceStatus` command marks invoices overdue. But NO reminder sending, NO escalation workflow.

**Best practice (Chaser model):** Graduated escalation with configurable templates and auto-send schedules.

#### Database Schema

```sql
CREATE TABLE reminder_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    escalation_level ENUM('friendly','firm','final','legal') NOT NULL,
    days_after_due INT NOT NULL,                     -- send X days after due date
    subject_mk TEXT, subject_en TEXT, subject_tr TEXT, subject_sq TEXT,
    body_mk TEXT, body_en TEXT, body_tr TEXT, body_sq TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    auto_send BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reminder_history (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    invoice_id INT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    template_id BIGINT UNSIGNED NULL,
    escalation_level VARCHAR(20) NOT NULL,
    sent_at TIMESTAMP NOT NULL,
    sent_via ENUM('email','sms') DEFAULT 'email',
    opened_at TIMESTAMP NULL,
    paid_at TIMESTAMP NULL,
    amount_due BIGINT UNSIGNED NOT NULL,
    notes TEXT NULL,
    INDEX idx_rh_invoice (invoice_id),
    INDEX idx_rh_customer (customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Workflow:** Scheduler command (`reminders:send`) runs daily. Checks overdue invoices against reminder templates. Sends via Postmark `broadcast` stream. Tracks opens (Postmark webhook). When invoice is paid, records payment date for analytics.

**AI Feature:** Analyze payment history to suggest optimal reminder timing per customer. "Customer X pays within 3 days of second reminder -- skip friendly, go to firm."

#### i18n Keys

```json
{
  "collections": {
    "title": { "mk": "Наплата и потсетници", "en": "Collections & Reminders", "tr": "Tahsilat ve Hatirlatmalar", "sq": "Arketim dhe Kujtesa" },
    "send_reminder": { "mk": "Испрати потсетник", "en": "Send Reminder", "tr": "Hatirlatma Gonder", "sq": "Dergo Kujtese" },
    "friendly": { "mk": "Пријателски", "en": "Friendly", "tr": "Dostca", "sq": "Miqesor" },
    "firm": { "mk": "Решителен", "en": "Firm", "tr": "Kararli", "sq": "I Vendosur" },
    "final": { "mk": "Последно предупредување", "en": "Final Notice", "tr": "Son Uyari", "sq": "Njoftim i Fundit" },
    "legal": { "mk": "Правна постапка", "en": "Legal Action", "tr": "Hukuki Islem", "sq": "Veprim Ligjor" },
    "auto_reminders": { "mk": "Автоматски потсетници", "en": "Auto Reminders", "tr": "Otomatik Hatirlatma", "sq": "Kujtesa Automatike" },
    "reminder_sent": { "mk": "Испратен", "en": "Sent", "tr": "Gonderildi", "sq": "Derguar" },
    "reminder_opened": { "mk": "Отворен", "en": "Opened", "tr": "Acildi", "sq": "Hapur" },
    "paid_after": { "mk": "Платено по потсетник", "en": "Paid After Reminder", "tr": "Hatirlatmadan Sonra", "sq": "Paguar pas Kujteses" }
  }
}
```

---

### F6: Purchase Orders

**Current state:** We have Bills (AP) and Estimates (quotes to customers) but NO purchase orders (orders to suppliers). The inventory system (`StockController`, `WarehouseController`, `InventoryDocumentController`) exists and handles priemnica/izdatnica/prenosnica but without PO linkage.

#### Database Schema

```sql
CREATE TABLE purchase_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    supplier_id BIGINT UNSIGNED NULL,
    po_number VARCHAR(50) NOT NULL,
    po_date DATE NOT NULL,
    expected_delivery_date DATE NULL,
    status ENUM('draft','sent','acknowledged','partially_received','fully_received','billed','closed','cancelled') DEFAULT 'draft',
    sub_total BIGINT UNSIGNED DEFAULT 0,
    tax BIGINT UNSIGNED DEFAULT 0,
    total BIGINT UNSIGNED DEFAULT 0,
    currency_id INT UNSIGNED NULL,
    warehouse_id BIGINT UNSIGNED NULL,
    converted_bill_id INT UNSIGNED NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
    INDEX idx_po_company_status (company_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE purchase_order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    item_id INT UNSIGNED NULL,
    name VARCHAR(255) NOT NULL,
    quantity DECIMAL(15,4) NOT NULL,
    received_quantity DECIMAL(15,4) DEFAULT 0,
    price BIGINT UNSIGNED NOT NULL,
    tax BIGINT UNSIGNED DEFAULT 0,
    total BIGINT UNSIGNED NOT NULL,
    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE goods_receipts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    purchase_order_id BIGINT UNSIGNED NULL,
    receipt_number VARCHAR(50) NOT NULL,
    receipt_date DATE NOT NULL,
    warehouse_id BIGINT UNSIGNED NULL,
    status ENUM('draft','confirmed','cancelled') DEFAULT 'draft',
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE goods_receipt_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    goods_receipt_id BIGINT UNSIGNED NOT NULL,
    purchase_order_item_id BIGINT UNSIGNED NULL,
    item_id INT UNSIGNED NULL,
    quantity_received DECIMAL(15,4) NOT NULL,
    quantity_accepted DECIMAL(15,4) NULL,
    quantity_rejected DECIMAL(15,4) DEFAULT 0,
    FOREIGN KEY (goods_receipt_id) REFERENCES goods_receipts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Lifecycle:** Draft -> Sent to Supplier (email+PDF) -> Goods Received (partial/full) -> Convert to Bill -> 3-Way Match (PO vs Receipt vs Bill)

**AI Features:**
- `ReorderSuggestionService` -- analyze stock consumption patterns, suggest when and how much to reorder
- Auto-suggest supplier based on past PO pricing history
- Create PO directly from "Low Stock" alerts

#### i18n Keys

```json
{
  "purchase_orders": {
    "title": { "mk": "Набавки", "en": "Purchase Orders", "tr": "Satin Alma", "sq": "Porosi Blerje" },
    "new_po": { "mk": "Нова набавка", "en": "New PO", "tr": "Yeni Siparis", "sq": "Porosi e Re" },
    "receive_goods": { "mk": "Прими стока", "en": "Receive Goods", "tr": "Mal Teslim Al", "sq": "Prano Mallra" },
    "convert_to_bill": { "mk": "Претвори во сметка", "en": "Convert to Bill", "tr": "Faturaya Donustur", "sq": "Konverto ne Fature" },
    "three_way_match": { "mk": "Тристрано совпаѓање", "en": "3-Way Match", "tr": "3 Yonlu Eslesme", "sq": "Perputhje 3-Drejtimeshe" },
    "quantity_ordered": { "mk": "Порачано", "en": "Ordered", "tr": "Siparis Edilen", "sq": "Porositur" },
    "quantity_received": { "mk": "Примено", "en": "Received", "tr": "Teslim Alinan", "sq": "Pranuar" },
    "reorder_suggestions": { "mk": "Предлози за нарачка", "en": "Reorder Suggestions", "tr": "Siparis Onerileri", "sq": "Sugjerime Riporositi" }
  }
}
```

---

### F7: Budgeting & Planning

**Current state:** Zero -- no Budget models exist. `eloquent-ifrs` has no budgeting concept.

**Best practice (Xero budgets + Adaptive Insights):** Monthly budget grid by account type. Pre-fill from prior year actuals. Budget vs Actual comparison with variance analysis.

#### Database Schema

```sql
CREATE TABLE budgets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    period_type ENUM('monthly','quarterly','yearly') DEFAULT 'monthly',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('draft','approved','locked','archived') DEFAULT 'draft',
    cost_center_id BIGINT UNSIGNED NULL,
    scenario ENUM('expected','optimistic','pessimistic') DEFAULT 'expected',
    created_by INT UNSIGNED NULL,
    approved_by INT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
    INDEX idx_budget_company (company_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE budget_lines (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    budget_id BIGINT UNSIGNED NOT NULL,
    account_type VARCHAR(50) NOT NULL,               -- IFRS account_type
    ifrs_account_id BIGINT UNSIGNED NULL,
    cost_center_id BIGINT UNSIGNED NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    amount DECIMAL(15,2) DEFAULT 0,
    notes TEXT NULL,
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE,
    INDEX idx_bl_budget_period (budget_id, period_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Budget vs Actual:** Query `ifrs_ledgers` for actual amounts per account_type per period, join with budget_lines, compute variance.

**AI Features:**
- **AI Budget Generation:** Send 12 months of actuals + growth trends to LLM, get projected budget with reasoning per line
- **AI Variance Commentary:** "Operating expenses are 15% over budget -- primarily driven by a one-time equipment purchase in March"
- **Scenario Comparison:** Side-by-side expected/optimistic/pessimistic

**Frontend UX (spreadsheet-feel):**
- Grid: rows = account categories, columns = months
- Tab to navigate, inline edit amounts
- One-click "Pre-fill from last year (+5% growth)"
- One-click "AI Generate" (sends historicals, returns suggestions)
- Variance report: color-coded bars (green = under, red = over)

#### i18n Keys

```json
{
  "budgets": {
    "title": { "mk": "Буџети", "en": "Budgets", "tr": "Butceler", "sq": "Buxhetet" },
    "create": { "mk": "Креирај буџет", "en": "Create Budget", "tr": "Butce Olustur", "sq": "Krijo Buxhet" },
    "vs_actual": { "mk": "Буџет наспроти реално", "en": "Budget vs Actual", "tr": "Butce ve Gercek", "sq": "Buxheti vs Aktual" },
    "variance": { "mk": "Отстапување", "en": "Variance", "tr": "Sapma", "sq": "Devijimi" },
    "scenario_expected": { "mk": "Очекувано", "en": "Expected", "tr": "Beklenen", "sq": "Pritur" },
    "scenario_optimistic": { "mk": "Оптимистично", "en": "Optimistic", "tr": "Iyimser", "sq": "Optimist" },
    "scenario_pessimistic": { "mk": "Песимистично", "en": "Pessimistic", "tr": "Kotumser", "sq": "Pesimist" },
    "prefill_actuals": { "mk": "Пополни од реални", "en": "Pre-fill from Actuals", "tr": "Gerceklerden Doldur", "sq": "Ploteso nga Realet" },
    "ai_generate": { "mk": "AI генерирање", "en": "AI Generate", "tr": "AI Olustur", "sq": "Gjenerim AI" },
    "under_budget": { "mk": "Под буџет", "en": "Under Budget", "tr": "Butce Altinda", "sq": "Nen Buxhet" },
    "over_budget": { "mk": "Над буџет", "en": "Over Budget", "tr": "Butce Ustunde", "sq": "Mbi Buxhet" }
  }
}
```

---

## Phase 3: Differentiators (Weeks 17-22) -- F8, F9, F10

---

### F8: Travel Expense Management (Патни налози)

**MK legal requirements:**
- Domestic per-diem: 8% of average monthly net salary (currently ~2,600 MKD/day)
- Foreign per-diem: varies by country (Government Decision table, ~50-80 EUR/day for EU countries)
- Meal deductions: 50% if accommodation provided, 20% if meals provided
- Tax-exempt within legal limits
- Must produce standardized Патен налог document

**Current state:** We have Expenses (CRUD + categories + OCR) and Recurring Expenses, but no travel order workflow with per-diem calculation.

#### Database Schema

```sql
CREATE TABLE travel_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    employee_id BIGINT UNSIGNED NULL,                -- FK to payroll_employees
    travel_number VARCHAR(50) NOT NULL,
    type ENUM('domestic','foreign') NOT NULL,
    purpose TEXT NOT NULL,
    departure_date DATETIME NOT NULL,
    return_date DATETIME NOT NULL,
    status ENUM('draft','pending_approval','approved','settled','rejected') DEFAULT 'draft',
    advance_amount BIGINT UNSIGNED DEFAULT 0,
    total_per_diem BIGINT UNSIGNED DEFAULT 0,
    total_expenses BIGINT UNSIGNED DEFAULT 0,
    total_mileage_cost BIGINT UNSIGNED DEFAULT 0,
    grand_total BIGINT UNSIGNED DEFAULT 0,
    reimbursement_amount BIGINT NULL DEFAULT 0,      -- positive = company owes, negative = employee owes
    cost_center_id BIGINT UNSIGNED NULL,
    ifrs_transaction_id BIGINT UNSIGNED NULL,
    approved_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE travel_segments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    travel_order_id BIGINT UNSIGNED NOT NULL,
    from_city VARCHAR(150) NOT NULL,
    to_city VARCHAR(150) NOT NULL,
    country_code VARCHAR(2) NULL,                    -- for foreign travel
    departure_at DATETIME NOT NULL,
    arrival_at DATETIME NOT NULL,
    transport_type ENUM('car','bus','train','plane','other') DEFAULT 'car',
    distance_km DECIMAL(10,2) NULL,
    accommodation_provided BOOLEAN DEFAULT FALSE,
    meals_provided BOOLEAN DEFAULT FALSE,
    per_diem_rate DECIMAL(10,2) NULL,                -- per-day rate
    per_diem_days DECIMAL(5,2) NULL,
    per_diem_amount BIGINT UNSIGNED DEFAULT 0,
    FOREIGN KEY (travel_order_id) REFERENCES travel_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE travel_expenses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    travel_order_id BIGINT UNSIGNED NOT NULL,
    category ENUM('transport','accommodation','meals','other') NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount BIGINT UNSIGNED NOT NULL,
    currency_code VARCHAR(3) DEFAULT 'MKD',
    receipt_path VARCHAR(500) NULL,                  -- uploaded receipt image
    ocr_data JSON NULL,                              -- AI-extracted data
    FOREIGN KEY (travel_order_id) REFERENCES travel_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**AI Features:**
- Receipt OCR: snap photo on phone -> extract vendor/amount/date (existing `ReceiptScannerController`)
- Auto-calculate per-diem from segment dates + country
- Distance estimation via geocoding API
- Auto-categorize expenses from receipt description

#### i18n Keys

```json
{
  "travel": {
    "title": { "mk": "Патни налози", "en": "Travel Orders", "tr": "Seyahat Emirleri", "sq": "Urdhrat e Udhetimit" },
    "create": { "mk": "Нов патен налог", "en": "New Travel Order", "tr": "Yeni Seyahat Emri", "sq": "Urdher i Ri" },
    "per_diem": { "mk": "Дневница", "en": "Per Diem", "tr": "Gunluk Harclik", "sq": "Dieta Ditore" },
    "domestic": { "mk": "Домашно", "en": "Domestic", "tr": "Yurt Ici", "sq": "Brendshme" },
    "foreign": { "mk": "Странство", "en": "Foreign", "tr": "Yurt Disi", "sq": "Jashte" },
    "mileage": { "mk": "Километража", "en": "Mileage", "tr": "Kilometre", "sq": "Kilometrazhi" },
    "advance": { "mk": "Аванс", "en": "Advance", "tr": "Avans", "sq": "Paradhenie" },
    "settlement": { "mk": "Пресметка", "en": "Settlement", "tr": "Hesap Kapama", "sq": "Llogaritja" },
    "scan_receipt": { "mk": "Скенирај сметка", "en": "Scan Receipt", "tr": "Fis Tara", "sq": "Skano Faturen" }
  }
}
```

---

### F9: BI Dashboards

**Current state:** AI Insights exist (`AiInsightsService`), 8 dashboard widgets, but no financial ratios, no OLAP, no trend analysis beyond AI chat.

**Implementation:** Pre-computed financial ratios + chart widgets + AI natural language queries.

**Financial Ratios (from IFRS ledger):**
- Liquidity: current ratio, quick ratio, cash ratio
- Profitability: gross margin, net margin, ROE, ROA
- Solvency: debt-to-equity, interest coverage
- Activity: receivable days, payable days, inventory turnover
- Altman Z-score (bankruptcy predictor)

**Service:** `FinancialRatioService` queries IFRS accounts by category (assets = 1xxx, liabilities = 2xxx, equity = 3xxx, revenue = 4xxx, expenses = 5-7xxx), computes ratios, caches monthly.

**AI Feature:** Natural language queries: "Which expenses grew the most this quarter?" -> AI queries ratio data + IFRS ledger, returns chart + explanation.

**Tier gating:** Business+ gets full dashboard. Standard gets 3 basic widgets.

#### i18n Keys

```json
{
  "bi": {
    "title": { "mk": "Бизнис интелигенција", "en": "Business Intelligence", "tr": "Is Zekasi", "sq": "Inteligjence Biznesi" },
    "financial_ratios": { "mk": "Финансиски показатели", "en": "Financial Ratios", "tr": "Finansal Oranlar", "sq": "Raportet Financiare" },
    "current_ratio": { "mk": "Тековна ликвидност", "en": "Current Ratio", "tr": "Cari Oran", "sq": "Raporti Aktual" },
    "profit_margin": { "mk": "Маргина на профит", "en": "Profit Margin", "tr": "Kar Marji", "sq": "Marzhi i Fitimit" },
    "altman_z": { "mk": "Алтман Z-скор", "en": "Altman Z-Score", "tr": "Altman Z-Skoru", "sq": "Altman Z-Skori" },
    "revenue_trend": { "mk": "Тренд на приходи", "en": "Revenue Trend", "tr": "Gelir Trendi", "sq": "Trendi i te Ardhurave" },
    "ask_ai": { "mk": "Прашајте за финансиите", "en": "Ask about finances", "tr": "Finanslar hakkinda sorun", "sq": "Pyetni per financat" }
  }
}
```

---

### F10: Batch Operations (Partner)

**Current state:** `BulkReportController` provides multi-company trial balance/P&L/balance sheet. But no batch close, batch VAT, or batch export.

**New batch operations:**
1. Batch daily close across N companies
2. Batch generate VAT returns (DDV-04) for all companies in period
3. Batch export trial balances (ZIP of PDFs)
4. Batch period lock
5. Batch journal export

**Implementation:** Laravel job queue (`batch_operations` queue). `BatchOperationService` creates a `batch_jobs` record, dispatches N sub-jobs (one per company), tracks progress via DB polling or broadcasting.

```sql
CREATE TABLE batch_jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id BIGINT UNSIGNED NOT NULL,
    operation_type VARCHAR(50) NOT NULL,
    company_ids JSON NOT NULL,
    parameters JSON NULL,
    status ENUM('queued','running','completed','failed') DEFAULT 'queued',
    total_items INT DEFAULT 0,
    completed_items INT DEFAULT 0,
    failed_items INT DEFAULT 0,
    results JSON NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### i18n Keys

```json
{
  "batch": {
    "title": { "mk": "Збирни операции", "en": "Batch Operations", "tr": "Toplu Islemler", "sq": "Operacione ne Grup" },
    "daily_close": { "mk": "Дневно затворање", "en": "Daily Close", "tr": "Gunluk Kapanma", "sq": "Mbyllja Ditore" },
    "vat_return": { "mk": "ДДВ пријава", "en": "VAT Return", "tr": "KDV Beyannamesi", "sq": "Kthimi TVSH" },
    "select_companies": { "mk": "Изберете компании", "en": "Select Companies", "tr": "Sirketleri Secin", "sq": "Zgjidhni Kompanitie" },
    "start_batch": { "mk": "Започни", "en": "Start Batch", "tr": "Baslat", "sq": "Fillo" },
    "progress": { "mk": "Напредок", "en": "Progress", "tr": "Ilerleme", "sq": "Progresi" }
  }
}
```

---

## Phase 4: Polish & Advanced (Weeks 23-26) -- F11, F12

---

### F11: Custom Report Builder

**Current state:** Fixed report formats only (trial balance, GL, P&L, etc.). No customization.

**Implementation:** NOT a full IDE. A guided wizard:
1. Select accounts (by range, category, or specific codes)
2. Choose columns (opening, debit, credit, closing, budget, variance, %)
3. Set period (month/quarter/year/custom)
4. Group by (none, month, quarter, cost center)
5. Compare (vs previous year, vs budget)
6. Preview -> Save template -> Optional email schedule

```sql
CREATE TABLE custom_report_templates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    name VARCHAR(150) NOT NULL,
    account_filter JSON NOT NULL,            -- { type: "range", from: "1000", to: "1999" }
    columns JSON NOT NULL,                   -- ["code","name","opening","debit","credit","closing"]
    period_type VARCHAR(20) NULL,            -- "month", "quarter", "year", "custom"
    group_by VARCHAR(20) NULL,               -- "month", "quarter", "cost_center"
    comparison VARCHAR(30) NULL,             -- "previous_year", "budget"
    schedule_cron VARCHAR(50) NULL,          -- "0 8 1 * *" = 1st of month at 8am
    schedule_emails JSON NULL,               -- ["cfo@company.mk"]
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

### F12: Financial Consolidation (Enhanced)

**Current state:** `BulkReportingService` sums assets/liabilities across companies -- simple aggregation, NO intercompany elimination.

**Enhancement:** Add intercompany transaction detection + elimination entries.

```sql
CREATE TABLE consolidation_groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id BIGINT UNSIGNED NULL,
    name VARCHAR(150) NOT NULL,
    parent_company_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE consolidation_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id BIGINT UNSIGNED NOT NULL,
    company_id INT UNSIGNED NOT NULL,
    ownership_pct DECIMAL(5,2) DEFAULT 100.00,
    FOREIGN KEY (group_id) REFERENCES consolidation_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Service:** `ConsolidationService` -- detect intercompany invoices (company A invoiced company B, both in group), generate elimination entries (debit intercompany payable, credit intercompany receivable), produce consolidated trial balance/P&L/BS.

---

## Implementation Timeline

```
PHASE 1: DEALBREAKERS (Weeks 1-8)
├── Week 1-2:   F1 Compensation (Kompenzacija)
├── Week 3-5:   F2 Payment Orders (Nalozi)
└── Week 6-8:   F3 Cost Centers

PHASE 2: COMPETITIVE (Weeks 9-16)
├── Week 9-10:  F4 Late Interest + F5 Collections (parallel, share overdue infra)
├── Week 11-13: F6 Purchase Orders
└── Week 14-16: F7 Budgeting & Planning

PHASE 3: DIFFERENTIATORS (Weeks 17-22)
├── Week 17-19: F8 Travel Expenses
├── Week 20-21: F9 BI Dashboards
└── Week 22:    F10 Batch Operations

PHASE 4: POLISH (Weeks 23-26)
├── Week 23-24: F11 Custom Report Builder
└── Week 25-26: F12 Consolidation Enhanced
```

## Dependency Graph

```
F3 (Cost Centers) ──────────────────> F7 (Budgets, needs cost center filter)
F3 (Cost Centers) ──────────────────> F9 (BI, needs cost center breakdown)
F4 (Interest) ──────────────────────> F5 (Collections, interest feeds reminders)
F6 (Purchase Orders) ──────────────> F2 (Payment Orders, PO->Bill->Pay flow)
F9 (BI) ───────────────────────────> F11 (Custom Reports, shares ratio infra)
F10 (Batch) ───────────────────────> F12 (Consolidation, extends bulk ops)
```

## Subscription Tier Mapping

| Feature | Free | Starter | Standard | Business | Max | Partner |
|---------|------|---------|----------|----------|-----|---------|
| F1 Compensation | - | - | Yes | Yes | Yes | All clients |
| F2 Payment Orders | - | - | 10/mo | 50/mo | Unlimited | All clients |
| F3 Cost Centers | - | - | 10 | 50 | Unlimited | All clients |
| F4 Late Interest | - | - | Yes | Yes | Yes | All clients |
| F5 Collections | - | 10/mo | 50/mo | Unlimited | Unlimited | All clients |
| F6 Purchase Orders | - | 5/mo | 30/mo | 100/mo | Unlimited | All clients |
| F7 Budgets | - | - | 3 | 20 | Unlimited | All clients |
| F8 Travel | - | - | 10/mo | 50/mo | Unlimited | All clients |
| F9 BI Dashboard | - | - | 3 widgets | Full | Full | Full |
| F10 Batch Ops | - | - | - | - | - | Partner only |
| F11 Custom Reports | - | - | 3 saved | 10 saved | Unlimited | Unlimited |
| F12 Consolidation | - | - | - | - | Yes | Yes |

---

## What This Gives Us vs Pantheon

After all 12 features:

| Capability | Pantheon | Facturino | Winner |
|-----------|----------|-----------|--------|
| GL + Reports | Desktop, fast SQL | Cloud, any device | **Tie** |
| Cost Centers | Per-document tagging | Per-document + **AI auto-assign** | **Facturino** |
| Budgeting | Manual entry only | Pre-fill + **AI generation** + variance AI commentary | **Facturino** |
| Compensation | Basic document | Wizard + **AI opportunity detection** | **Facturino** |
| Payment Orders | PP30 export | PP30/PP50/SEPA + **AI timing optimization** | **Facturino** |
| Purchase Orders | Full PO lifecycle | PO + 3-way match + **AI reorder suggestions** | **Facturino** |
| Interest/Collections | Manual calc + reminders | Auto-calc + **auto-send + AI timing** | **Facturino** |
| Travel Expenses | Manual form | **Mobile receipt OCR** + auto per-diem | **Facturino** |
| BI | ZEUS OLAP (powerful) | Ratios + trends + **natural language queries** | **Tie** (different strengths) |
| Batch Operations | Console across DBs | Queue-based across companies | **Tie** |
| Custom Reports | ARES IDE (powerful) | Guided wizard (simpler, more accessible) | **Pantheon** (power), **Facturino** (ease) |
| Consolidation | Full IFRS | Intercompany elimination | **Match** |
| Cloud/Mobile | Desktop only | **Cloud + PWA + any device** | **Facturino** |
| E-Faktura | eSlog (Slovenia) | **UBL 2.1 + QES (MK native)** | **Facturino** |
| PSD2 Bank Feeds | Statement import | **Live PSD2 + auto-reconciliation** | **Facturino** |
| Price | EUR 1,699 + EUR 374/yr | **Free for accountants + 20% commission** | **Facturino** |

**Result: Full Pantheon parity + cloud advantage + AI features that Pantheon cannot match.**
