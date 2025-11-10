# FACTURINO ROADMAP - November 10, 2025
## Comprehensive Audit & Implementation Plan

**Audit Date:** 2025-11-10
**Project:** Facturino - Macedonian Accounting Application
**Base:** InvoiceShelf Fork
**Target:** MK-localized invoicing with IFRS, PSD2, e-Faktura, DDV compliance

---

## EXECUTIVE SUMMARY

### What We've Built ✅

**Strong Foundations (Production-Ready):**
- ✅ Core invoicing system (Invoice, Estimate, Payment, Expense)
- ✅ IFRS double-entry accounting via `ekmungai/eloquent-ifrs` package
- ✅ UBL 2.1 XML generation (Macedonia-compliant)
- ✅ QES digital signature support (X.509 certificates)
- ✅ Payment gateways (CPAY custom driver + Paddle one-time payments)
- ✅ Macedonia VAT/DDV infrastructure (18% + 5% rates)
- ✅ Multi-company/multi-tenant isolation
- ✅ PDF generation with 4 invoice templates (including invoice-mk)
- ✅ Import wizard (customers, invoices, items, payments, expenses)
- ✅ Partner commission tracking

**Partial Implementations (50-80% Complete):**
- ⚠️ E-invoice submission (services ready, no database tracking)
- ⚠️ Certificate management (UI ready, file-based only)
- ⚠️ Banking integration (models exist, PSD2 not integrated)
- ⚠️ Tax reporting (can generate DDV-04 XML, no return tracking)

### What's Missing ❌

**Critical Gaps (High Priority):**
- ❌ E-invoice submission tracking (EInvoice, EInvoiceSubmission models)
- ❌ Database-backed certificate management
- ❌ Tax return filing history (TaxReturn, TaxReportPeriod models)
- ❌ Credit notes and debit notes
- ❌ Supplier bills / Accounts Payable
- ❌ PSD2 bank feed integration
- ❌ Audit logging system
- ❌ Document approval workflows

**Business Impact:**
- Cannot track which invoices have been e-filed with tax authority
- No audit trail for compliance
- No accounts payable (only accounts receivable)
- No automatic bank transaction matching
- No credit note support (VAT correction issues)

---

## DETAILED AUDIT FINDINGS

### 1. IFRS/ACCOUNTING BACKBONE

#### ✅ IMPLEMENTED (Via Package)

**Architecture Decision:** Using `ekmungai/eloquent-ifrs` package instead of custom models.

**What Exists:**
- Package: `ekmungai/eloquent-ifrs` v5.0.4 installed
- Adapter: `app/Domain/Accounting/IfrsAdapter.php` (699 lines)
- Observers: InvoiceObserver, PaymentObserver (auto-posting to ledger)
- API: Trial balance, balance sheet, income statement endpoints
- Tests: IfrsIntegrationTest, MultiTenantAccountingTest
- Seeders: MkIfrsSeeder (Macedonian chart of accounts)

**Accounting Flow:**
```
Invoice SENT → InvoiceObserver → IfrsAdapter::postInvoice()
  DR 1200 Accounts Receivable
  CR 4000 Sales Revenue
  CR 2100 Tax Payable

Payment COMPLETED → PaymentObserver → IfrsAdapter::postPayment()
  DR 1000 Cash and Bank
  CR 1200 Accounts Receivable

Payment Fee → IfrsAdapter::postFee()
  DR 5100 Payment Processing Fees
  CR 1000 Cash and Bank
```

**Company Scoping:**
- Each Company → one IFRS Entity (1:1 relationship)
- Field: `companies.ifrs_entity_id`
- Auto-created via `IfrsAdapter::getOrCreateEntityForCompany()`

**What's NOT Custom:**
- ❌ No custom AccountingEntity model (using IFRS\Models\Entity)
- ❌ No custom JournalEntry model (using IFRS\Models\Transaction)
- ❌ No custom LedgerEntry model (using IFRS\Models\Ledger)
- ❌ No DocumentPosting model (using FK: invoices.ifrs_transaction_id)
- ❌ No NumberSeries model (using SerialNumberFormatter service)

**Missing Functionality:**
- ❌ Expense posting (no ExpenseObserver)
- ❌ Manual journal entries (no UI/API)
- ❌ Account management UI
- ❌ Period close workflow
- ❌ Credit note posting

**Recommendation:** ✅ KEEP package-based approach. Add missing observers and UI.

---

### 2. E-INVOICE & QUALIFIED SIGNATURES

#### ✅ SERVICES PRODUCTION-READY

**UBL Generation:**
- Service: `Modules/Mk/Services/MkUblMapper.php` (432 lines)
- Package: `num-num/ubl-invoice` v1.21
- Compliance: UBL 2.1 with Macedonia VAT (18%, 5%)
- Features: Cyrillic support, payment terms, tax breakdowns
- Schemas: `/storage/schemas/maindoc/UBL-Invoice-2.1.xsd`

**Digital Signatures:**
- Service: `Modules/Mk/Services/MkXmlSigner.php` (373 lines)
- Package: `robrichards/xmlseclibs` v3.1
- Algorithm: RSA-SHA256
- Features: Enveloped/detached signatures, verification, cert extraction

**Certificate Management:**
- UI: `CertUploadController.php` (372 lines) + `CertUpload.vue`
- Upload: P12/PFX certificate with password
- Storage: File-based (storage/app/certificates/)
- Validation: OpenSSL certificate checks
- Tests: `CertificateUploadTest.php` (17 test cases, 432 lines)

**E-Faktura Portal:**
- Tool: `tools/efaktura_upload.php` (788 lines CLI)
- Portal: https://e-ujp.ujp.gov.mk/
- Methods: Portal form upload (current), API upload (prepared)
- Features: Batch upload, status checking, receipt extraction

#### ❌ MISSING DATABASE LAYER (0% Complete)

**No Migrations:**
- ❌ `e_invoices` table
- ❌ `e_invoice_submissions` table
- ❌ `certificates` table (db-backed)
- ❌ `signature_logs` table

**No Models:**
- ❌ EInvoice (link invoice → UBL XML → signed XML → submission)
- ❌ EInvoiceSubmission (track portal uploads, receipts, status)
- ❌ Certificate (multi-company, expiry tracking, rotation)
- ❌ SignatureLog (audit trail)

**Business Impact:**
- Cannot track which invoices submitted to tax authority
- Cannot store submission receipts/confirmation numbers
- Cannot retry failed submissions
- Cannot manage multiple certificates (one per company)
- No audit trail for signature operations
- Manual CLI tool invocation required

**Required Models:**

```sql
-- E-Invoice Master
CREATE TABLE e_invoices (
    id BIGINT PRIMARY KEY,
    invoice_id BIGINT FOREIGN KEY,
    company_id BIGINT FOREIGN KEY,
    ubl_xml TEXT,
    ubl_xml_signed TEXT,
    status ENUM('draft', 'signed', 'submitted', 'accepted', 'rejected'),
    created_at TIMESTAMP
);

-- Submission Tracking
CREATE TABLE e_invoice_submissions (
    id BIGINT PRIMARY KEY,
    e_invoice_id BIGINT FOREIGN KEY,
    submitted_at TIMESTAMP,
    portal_url VARCHAR(255),
    receipt_number VARCHAR(100),
    status ENUM('pending', 'accepted', 'rejected', 'error'),
    response_data JSON,
    retry_count INT DEFAULT 0,
    next_retry_at TIMESTAMP
);

-- Multi-Company Certificates
CREATE TABLE certificates (
    id BIGINT PRIMARY KEY,
    company_id BIGINT FOREIGN KEY,
    name VARCHAR(255),
    serial_number VARCHAR(100),
    fingerprint VARCHAR(100) UNIQUE,
    valid_from TIMESTAMP,
    valid_to TIMESTAMP,
    private_key_path VARCHAR(255),
    certificate_path VARCHAR(255),
    is_active BOOLEAN DEFAULT true
);

-- Audit Trail
CREATE TABLE signature_logs (
    id BIGINT PRIMARY KEY,
    certificate_id BIGINT FOREIGN KEY,
    action ENUM('sign', 'verify', 'upload', 'delete'),
    signable_type VARCHAR(100),
    signable_id BIGINT,
    user_id BIGINT FOREIGN KEY,
    success BOOLEAN,
    created_at TIMESTAMP
);
```

---

### 3. TAX/DDV COMPLIANCE

#### ✅ IMPLEMENTED

**Core Tax Models:**
- TaxType model (percentage + fixed amount support)
- Tax model (invoice/estimate/item taxes)
- Migrations: Full history from 2019-2024
- Calculation: Per-item OR global tax
- Multi-currency support with exchange rates

**Macedonia VAT:**
- Seeder: `MkVatSeeder.php` (creates DDV 18% + DDV 5%)
- Service: `VatXmlService.php` (generates DDV-04 XML)
- Controller: `VatReturnController.php` (preview + generate)
- Schema: `/storage/schemas/mk_ddv04.xsd`

**Company/Customer Fields:**
- companies.vat_id, companies.tax_id
- customers.vat_number, customers.tax_id

**VAT Return Generation:**
```php
// Current implementation
POST /api/v1/companies/{id}/vat-return/preview
  → Calculates VAT by period
  → Groups by rate (18%, 5%, 0%)
  → Generates DDV-04 XML

// What it does:
- Queries paid invoices in date range
- Calculates output VAT (sales)
- Formats Macedonia VAT number
- Validates period (monthly/quarterly)
- Generates XML for submission
```

#### ❌ MISSING MODELS

**No Tax Administration Tracking:**
- ❌ TaxScheme model (VAT vs other tax types)
- ❌ TaxReportPeriod model (period management, deadlines)
- ❌ TaxReturn model (filing history, amendments)
- ❌ CustomerTaxProfile model (B2B/B2C, reverse charge, exempt)

**No Advanced Features:**
- ❌ Reverse charge handling
- ❌ Zero-rated vs exempt distinction (both use 0%)
- ❌ VAT number validation (format check)
- ❌ Exemption reason tracking (Article 29, etc.)
- ❌ Tax return submission history
- ❌ Period close/lock mechanism

**Business Impact:**
- Cannot track filed tax returns
- Cannot prevent duplicate filing
- No audit trail of submissions
- Cannot manage tax periods properly
- No reverse charge for B2B EU transactions

**Required Models:**

```sql
-- Tax Report Periods
CREATE TABLE tax_report_periods (
    id BIGINT PRIMARY KEY,
    company_id BIGINT FOREIGN KEY,
    period_type ENUM('monthly', 'quarterly', 'annual'),
    start_date DATE,
    end_date DATE,
    status ENUM('open', 'closed', 'filed'),
    due_date DATE,
    created_at TIMESTAMP
);

-- Tax Returns (Filed Reports)
CREATE TABLE tax_returns (
    id BIGINT PRIMARY KEY,
    company_id BIGINT FOREIGN KEY,
    period_id BIGINT FOREIGN KEY,
    submitted_at TIMESTAMP,
    status ENUM('draft', 'filed', 'accepted', 'rejected', 'amended'),
    xml_path VARCHAR(255),
    receipt_number VARCHAR(100),
    response_data JSON
);

-- Customer Tax Profiles
CREATE TABLE customer_tax_profiles (
    id BIGINT PRIMARY KEY,
    customer_id BIGINT FOREIGN KEY,
    is_reverse_charge BOOLEAN DEFAULT false,
    is_exempt BOOLEAN DEFAULT false,
    exemption_reason VARCHAR(255),
    customer_type ENUM('b2b', 'b2c'),
    tax_zone ENUM('domestic', 'eu', 'export')
);
```

---

### 4. PSD2 BANK FEEDS

#### ⚠️ PARTIAL IMPLEMENTATION (Models Exist, No Integration)

**What Exists:**
- Migration: `2025_07_25_163932_create_bank_transactions_table.php`
- Models: BankAccount, BankTransaction
- Sync Jobs: SyncStopanska, SyncNlb, SyncKomer (in app/Jobs/)
- Matcher: `Modules/Mk/Services/Matcher.php`
- Token Storage: `bank_tokens` table

**What's Missing:**
- ❌ No BankProvider model (NLB, Stopanska, Komercijalna configs)
- ❌ No BankConnection model (OAuth consent tracking)
- ❌ No BankConsent model (scope, expiry)
- ❌ No PSD2 package integration (`oak-labs-io/psd2` NOT installed)
- ❌ No OAuth flow implementation
- ❌ No automatic transaction sync
- ❌ No reconciliation UI

**Business Impact:**
- Manual bank statement import only
- No real-time balance checking
- No automatic payment matching
- Cannot initiate SEPA payments

**Expected Package:** `oak-labs-io/psd2` (from CLAUDE.md whitelist)

**Required Implementation:**

```sql
-- Bank Providers
CREATE TABLE bank_providers (
    id BIGINT PRIMARY KEY,
    key VARCHAR(50), -- 'nlb', 'stopanska', 'komercijalna'
    name VARCHAR(255),
    base_url VARCHAR(255),
    type ENUM('sandbox', 'production'),
    supports_ais BOOLEAN,
    supports_pis BOOLEAN
);

-- Bank Connections
CREATE TABLE bank_connections (
    id BIGINT PRIMARY KEY,
    company_id BIGINT FOREIGN KEY,
    bank_provider_id BIGINT FOREIGN KEY,
    status ENUM('pending', 'active', 'expired', 'revoked'),
    created_by BIGINT FOREIGN KEY
);

-- OAuth Consents
CREATE TABLE bank_consents (
    id BIGINT PRIMARY KEY,
    bank_connection_id BIGINT FOREIGN KEY,
    scope VARCHAR(255), -- 'accounts', 'balances', 'transactions'
    consent_id VARCHAR(100), -- from bank
    expires_at TIMESTAMP,
    status ENUM('pending', 'active', 'expired')
);
```

---

### 5. SUBSCRIPTION BILLING

#### ⚠️ INVOICE-BASED PAYMENTS, NOT SAAS

**What Exists:**
- Package: `laravel/cashier-paddle` v2.6 (installed but not used for subscriptions)
- Payment Model: Extended with gateway fields (cpay, paddle, bank_transfer, manual)
- CPAY Driver: `Modules/Mk/Services/CpayDriver.php` (custom implementation)
- Paddle Webhook: `Modules/Mk/Http/PaddleWebhookController.php` (one-time payments only)
- Commission System: Partner model, Commission model, CommissionCalculatorService

**Architecture:**
```
CURRENT: B2B Invoice Payments
Company → creates Invoice → Customer pays via CPAY/Paddle → Payment record
  ↓
Partner earns commission on invoice payment
```

**NOT Implemented:**
```
SAAS: Recurring Subscriptions
User → subscribes to Plan → Paddle recurring billing → Subscription record
```

**What's Missing for SaaS:**
- ❌ Plan model (subscription plans)
- ❌ Price model (pricing tiers)
- ❌ Subscription model (Paddle provides but User/Company don't use Billable trait)
- ❌ Billable trait on User or Company
- ❌ Subscription management UI
- ❌ Paddle migrations not published

**What's Missing for Complete Payments:**
- ❌ Gateway model (centralized gateway configs)
- ❌ GatewayWebhookEvent model (audit trail)
- ❌ Payout model (batch partner commission payments)
- ❌ Refund model (refund tracking)

**Business Impact:**
- Cannot offer SaaS subscriptions
- No recurring billing
- No webhook event audit trail
- Partner payouts are manual

**Recommendation:** If SaaS is NOT the goal, document that this is an invoice-based system and add missing audit models (GatewayWebhookEvent, Payout, Refund).

---

### 6. DOCUMENT MODELS

#### ✅ FULLY IMPLEMENTED

**Core Documents:**
- Invoice (11 status constants, UBL export, QES signing, multi-template)
- Estimate (6 status, convert to invoice)
- Payment (gateway integration, PDF receipts)
- Expense (receipt attachments, categories)
- RecurringInvoice (cron-based automation)

**Supporting Infrastructure:**
- ImportJob (comprehensive import wizard with 6 temp tables)
- PDF generation (GeneratesPdfTrait, 4 invoice templates)
- Email system (EmailLog, preview, attachments)
- Number sequencing (SerialNumberFormatter service)
- Custom fields (invoice-level + line-level)

#### ❌ MISSING DOCUMENT TYPES

**Critical for Compliance:**
- ❌ CreditNote model (VAT corrections)
- ❌ DebitNote model (post-invoice adjustments)
- ❌ ProformaInvoice model (quote → proforma → invoice flow)

**Accounts Payable:**
- ❌ Bill/SupplierInvoice model (purchase invoices)
- ❌ BillLine model
- ❌ BillPayment model
- ❌ Supplier/Vendor model

**Advanced Features:**
- ❌ RecurringExpense model
- ❌ DocumentTemplate model (db-backed WYSIWYG editor)
- ❌ CompanyStamp model (digital seals)
- ❌ ExportJob model (background exports)
- ❌ AuditLog model (compliance trail)
- ❌ DocumentApproval model (maker-checker workflows)

**Business Impact:**
- Cannot issue credit notes (VAT compliance issue)
- No accounts payable tracking
- No purchase ledger
- No approval workflows
- No audit trail for compliance

**Estimated Implementation:** 132 hours for missing core features

---

## IMPLEMENTATION ROADMAP

### PHASE 1: CRITICAL COMPLIANCE (Weeks 1-4) ⚠️ REVISED

**Priority:** Audit trail from day 1, VAT compliance, e-invoice tracking with safety guards

#### Milestone 1.1: Audit Logging + Entity Guards (Week 1) 🔒 NEW
**Tasks:**
- [ ] Create `audit_logs` migration with before/after snapshots
- [ ] Create AuditLog model with polymorphic relationships
- [ ] Create HasAuditing trait (auto-log created_by/updated_by)
- [ ] Create AuditObserver for Invoice, Payment, Estimate, Expense
- [ ] Create EntityGuard domain service (throws if no IFRS entity resolved)
- [ ] Add TenantScope trait to all new models
- [ ] Add entity null checks to IfrsAdapter methods
- [ ] Add unit tests for entity guard failures
- [ ] Add feature test: cross-tenant audit log isolation
- [ ] Add PII encryption for VAT IDs, IBANs in audit logs
- [ ] Write tests

**Deliverables:**
- Immutable audit trail for all document changes
- Entity null guards prevent multi-tenant leakage
- Who/when/what-changed tracking from day 1

**Acceptance Criteria:**
- ✅ Every change to invoice/payment/certificate has audit row
- ✅ Attempting to post without entity throws DomainException
- ✅ Audit logs encrypted for PII fields (VAT IDs, IBANs)
- ✅ Cross-tenant audit log query returns 0 results

#### Milestone 1.2: E-Invoice Database Layer (Week 2)
**Tasks:**
- [ ] Create `e_invoices` migration
- [ ] Create `e_invoice_submissions` migration with retry logic
- [ ] Create `certificates` migration (db-backed, encrypted blob storage)
- [ ] Create `signature_logs` migration
- [ ] Create EInvoice model with relationships + TenantScope
- [ ] Create EInvoiceSubmission model with idempotency keys
- [ ] Create Certificate model (encrypted, no raw keys in DB)
- [ ] Create SignatureLog model
- [ ] Refactor CertUploadController to use Certificate model
- [ ] Add certificate expiry alerts (30-day warning)
- [ ] Add dry-run verify endpoint (validate chain before enabling)
- [ ] Add queued SubmitEInvoiceJob with retry + backoff
- [ ] Add health ping for e-ujp portal with warning banner
- [ ] Add "simulate submission" endpoint (sign + validate, no submit)
- [ ] Update Invoice model with `eInvoice()` relationship
- [ ] Write tests

**Deliverables:**
- Database persistence for e-invoice workflow
- Multi-company certificate management (encrypted)
- Submission tracking with automatic retry
- Queued background submission jobs

**Acceptance Criteria:**
- ✅ 10 sample invoices each have EInvoice + EInvoiceSubmission with final status
- ✅ Failed submissions auto-retry with exponential backoff
- ✅ Certificate private key encrypted at rest, never logged
- ✅ Expired certificates disabled automatically
- ✅ Stored signed XML and receipt number for each submission

#### Milestone 1.3: Tax Return Tracking with Period Locking (Week 3)
**Tasks:**
- [ ] Create `tax_report_periods` migration with lock_status
- [ ] Create `tax_returns` migration with exact_xml_submitted
- [ ] Create TaxReportPeriod model + TenantScope
- [ ] Create TaxReturn model + TenantScope
- [ ] Add period close job (locks all source docs in window)
- [ ] Add "reopen period with reason" workflow
- [ ] Extend VatReturnController to save exact XML + receipt
- [ ] Add double-filing prevention (check existing TaxReturn for period)
- [ ] Add period management UI
- [ ] Add tax return history view
- [ ] Add "amend return" workflow
- [ ] Write tests

**Deliverables:**
- Track filed DDV returns with exact XML
- Period locking prevents backdated changes
- Double-filing prevention

**Acceptance Criteria:**
- ✅ Period can be opened/closed; closed periods block invoice edits
- ✅ Submitting persists exact XML and receipt number
- ✅ Attempting to re-file same period is blocked without explicit "amend"
- ✅ Reopening period requires reason and creates audit log

#### Milestone 1.4: Credit Notes as First-Class Documents (Week 4)
**Tasks:**
- [ ] Create `credit_notes` migration with separate number series
- [ ] Create `credit_note_items` migration
- [ ] Create CreditNote model + TenantScope
- [ ] Create CreditNoteItem model
- [ ] Create CreditNoteController
- [ ] Create PDF templates (3 variants)
- [ ] Create UBL CreditNote mapper (separate from Invoice)
- [ ] Add to IFRS posting (reference original transaction_id, reverse entries)
- [ ] Add CreditNoteObserver for auto-posting
- [ ] Add to VAT calculations (reduce output VAT in correct buckets)
- [ ] Add immutability: once posted, only void via new credit note
- [ ] Add MK-specific credit note template with legal footer
- [ ] Write tests

**Deliverables:**
- Issue credit notes for returns/cancellations
- VAT-compliant corrections with UBL export
- IFRS journal reversal with audit trail

**Acceptance Criteria:**
- ✅ Credit note has own number series (CN-2025-0001)
- ✅ UBL CreditNote XML validates against schema
- ✅ VAT totals reduce correct DDV buckets (18%, 5%)
- ✅ Reports reflect negative amounts correctly
- ✅ Posted credit notes are immutable (can only void)

#### Milestone 1.5: Backfill & Observer Parity (Week 4)
**Tasks:**
- [ ] Create ExpenseObserver for IFRS posting
- [ ] Create backfill job: generate EInvoice records for existing invoices
- [ ] Create backfill job: generate TaxReturn records from existing DDV XMLs
- [ ] Add number series immutability guards (no renumber in closed period)
- [ ] Add concurrency tests for duplicate number prevention
- [ ] Add cross-tenant number leakage tests
- [ ] Review MK invoice layout with accountant (required fields, VAT notes, Cyrillic)
- [ ] Write tests

**Deliverables:**
- Existing data migrated to new tracking tables
- Observer parity for all document types
- Number series safety guardrails

**Acceptance Criteria:**
- ✅ All existing invoices have EInvoice records with correct state
- ✅ Expenses auto-post to IFRS ledger
- ✅ Sent invoices cannot be renumbered
- ✅ Concurrent invoice creation never generates duplicate numbers

---

### PHASE 2: BUSINESS OPERATIONS (Weeks 4-6)

#### Milestone 2.1: Accounts Payable (Week 4-5)
**Tasks:**
- [ ] Create `suppliers` migration
- [ ] Create `bills` migration
- [ ] Create `bill_items` migration
- [ ] Create `bill_payments` migration
- [ ] Create Supplier model
- [ ] Create Bill model
- [ ] Create BillItem model
- [ ] Create BillPayment model
- [ ] Create BillsController
- [ ] Create PDF templates
- [ ] Add to IFRS posting (DR Expense, CR A/P)
- [ ] Add payment tracking (DR A/P, CR Cash)
- [ ] Write tests

**Deliverables:**
- Track supplier invoices
- Accounts payable ledger
- Complete double-entry accounting

#### Milestone 2.2: Audit Logging (Week 5)
**Tasks:**
- [ ] Create `audit_logs` migration
- [ ] Create AuditLog model
- [ ] Create AuditLogObserver (for all models)
- [ ] Create AuditLogController (read-only API)
- [ ] Create audit log UI component
- [ ] Add IP address and user agent tracking
- [ ] Add before/after snapshots
- [ ] Write tests

**Deliverables:**
- Compliance audit trail
- Track all document changes
- User activity reporting

#### Milestone 2.3: Proforma Invoices (Week 6)
**Tasks:**
- [ ] Create `proforma_invoices` migration
- [ ] Create `proforma_invoice_items` migration
- [ ] Create ProformaInvoice model
- [ ] Create ProformaInvoiceItem model
- [ ] Create ProformaInvoiceController
- [ ] Create PDF templates
- [ ] Add convert-to-invoice flow
- [ ] Write tests

**Deliverables:**
- Estimate → Proforma → Invoice lifecycle
- Professional quote workflow

---

### PHASE 3: BANKING AUTOMATION (Weeks 7-9)

#### Milestone 3.1: PSD2 Thin AIS Slice (Week 7-8) ⚠️ REVISED
**Tasks:**
- [ ] Install `oak-labs-io/psd2` package
- [ ] Create `bank_providers` migration (seed ONE bank: NLB or Stopanska)
- [ ] Create `bank_connections` migration
- [ ] Create `bank_consents` migration
- [ ] Create BankProvider model + TenantScope
- [ ] Create BankConnection model + TenantScope
- [ ] Create BankConsent model + TenantScope
- [ ] Implement OAuth flow for ONE bank (consent → redirect → callback)
- [ ] Create PSD2 service wrapper (single bank, AIS only)
- [ ] Add "fetch accounts + last 90 days transactions" endpoint
- [ ] Create BankConnectionController
- [ ] Add consent management UI (single bank)
- [ ] Keep MT940/CSV importer as fallback
- [ ] Write tests

**Deliverables:**
- OAuth consent with ONE Macedonian bank (NLB or Stopanska)
- Automatic account discovery
- 90-day transaction fetch (AIS only, NO PIS)

**Acceptance Criteria:**
- ✅ Single bank OAuth flow works end-to-end
- ✅ Accounts fetched and stored
- ✅ Last 90 days transactions synced
- ✅ MT940 import still works as fallback

**DEFERRED to Phase 4:**
- ❌ PIS (payment initiation) - too risky for Phase 3
- ❌ Additional banks (Komercijalna) - add incrementally after NLB works

#### Milestone 3.2: Transaction Reconciliation with Confidence Scoring (Week 8-9)
**Tasks:**
- [ ] Enhance Matcher service for auto-reconciliation
- [ ] Add confidence scoring (exact match, partial match, fuzzy)
- [ ] Create ReconciliationController
- [ ] Create reconciliation UI with three buckets: auto-matched, suggested, manual
- [ ] Add manual match interface
- [ ] Add manual override queue for low-confidence matches
- [ ] Add bulk match operations
- [ ] Add daily scheduled sync job (rate-limited to avoid PSD2 throttling)
- [ ] Write tests

**Deliverables:**
- Smart payment matching with confidence scores
- Reconciliation dashboard with manual override
- Daily automatic transaction import

**Acceptance Criteria:**
- ✅ High-confidence matches (>90%) auto-reconcile
- ✅ Medium-confidence (50-90%) go to suggestion queue
- ✅ Low-confidence (<50%) go to manual queue
- ✅ Manual override preserves audit trail

---

### PHASE 4: ADVANCED FEATURES (Weeks 10-12)

#### Milestone 4.1: Document Approvals (Week 10)
**Tasks:**
- [ ] Create `document_approvals` migration
- [ ] Create `approval_workflows` migration
- [ ] Create DocumentApproval model
- [ ] Create ApprovalWorkflow model
- [ ] Create ApprovalController
- [ ] Add approval UI to invoices/estimates/expenses
- [ ] Add email notifications
- [ ] Add delegation support
- [ ] Write tests

**Deliverables:**
- Multi-level approval workflows
- Amount-based thresholds
- Maker-checker controls

#### Milestone 4.2: Payment Gateway Enhancements (Week 11)
**Tasks:**
- [ ] Create `gateway_webhook_events` migration
- [ ] Create `payouts` migration
- [ ] Create `refunds` migration
- [ ] Create GatewayWebhookEvent model
- [ ] Create Payout model
- [ ] Create Refund model
- [ ] Extend PaddleWebhookController to log all events
- [ ] Create PayoutController (batch partner commissions)
- [ ] Create RefundController
- [ ] Write tests

**Deliverables:**
- Webhook event audit trail
- Batch commission payouts
- Refund tracking

#### Milestone 4.3: Recurring Expenses & Export Jobs (Week 12)
**Tasks:**
- [ ] Create `recurring_expenses` migration
- [ ] Create `export_jobs` migration
- [ ] Create RecurringExpense model
- [ ] Create ExportJob model
- [ ] Create cron job for recurring expenses
- [ ] Create background export jobs (CSV, Excel, PDF, UBL)
- [ ] Add export queue UI
- [ ] Write tests

**Deliverables:**
- Automated recurring costs
- Background export processing
- Download history

---

## PRIORITY MATRIX

### MUST HAVE (Compliance & Legal)
1. ✅ E-invoice submission tracking (Phase 1.1)
2. ✅ Tax return history (Phase 1.2)
3. ✅ Credit notes (Phase 1.3)
4. ✅ Audit logging (Phase 2.2)

### SHOULD HAVE (Business Operations)
5. ✅ Accounts payable (Phase 2.1)
6. ✅ Proforma invoices (Phase 2.3)
7. ✅ PSD2 bank feeds (Phase 3.1)
8. ✅ Transaction reconciliation (Phase 3.2)

### NICE TO HAVE (Efficiency)
9. ⚪ Document approvals (Phase 4.1)
10. ⚪ Gateway audit trail (Phase 4.2)
11. ⚪ Recurring expenses (Phase 4.3)
12. ⚪ Background exports (Phase 4.3)

---

## DEFERRED / OUT OF SCOPE

### Not Implementing (Architecture Decisions)

**1. Custom IFRS Models**
- **Decision:** Use `ekmungai/eloquent-ifrs` package
- **Reason:** Well-tested, IFRS-compliant, saves 40+ hours
- **Action:** Add missing observers (Expense, CreditNote, Bill)

**2. SaaS Subscription Platform**
- **Decision:** This is an invoice-based payment system, NOT SaaS
- **Reason:** Business model is B2B invoicing with partner commissions
- **Action:** Document architecture, add webhook audit trail

**3. WYSIWYG Template Editor**
- **Decision:** Defer to future release
- **Reason:** Blade templates sufficient, complex feature
- **Action:** Keep file-based templates for now

---

## DEPENDENCIES & CONSTRAINTS

### External Package Decisions

**Install Required:**
- `oak-labs-io/psd2` - For PSD2 bank integration (Phase 3.1)

**Already Installed:**
- ✅ `num-num/ubl-invoice` v1.21
- ✅ `robrichards/xmlseclibs` v3.1
- ✅ `ekmungai/eloquent-ifrs` v5.0.4
- ✅ `laravel/cashier-paddle` v2.6

**NOT Installing (Custom Implementation):**
- ❌ `bojanvmk/laravel-cpay` - Custom CpayDriver.php works well

### Technical Constraints

**Database:**
- All tables must use `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`
- Foreign keys `ON DELETE RESTRICT` (prevent orphans)
- Test migrations with `php artisan migrate:fresh`

**Multi-Tenancy:**
- All new models need `company_id` field (except global reference data)
- Use `whereCompany()` scope pattern
- Test with MultiTenantTest pattern

**Testing:**
- Minimum 80% code coverage for new features
- Feature tests for all API endpoints
- Unit tests for all services
- Browser tests for critical UI flows

---

## RISK ASSESSMENT

### High Risk

**1. PSD2 Bank Integration (Phase 3.1)**
- **Risk:** Banks may have rate limits, undocumented APIs, sandbox issues
- **Mitigation:** Start with one bank (NLB), add others incrementally
- **Fallback:** MT940 file import already works

**2. E-Invoice Portal Submission (Phase 1.1)**
- **Risk:** Tax authority portal may change, no official API
- **Mitigation:** Use robust parsing, monitor for changes
- **Fallback:** Manual upload via portal

### Medium Risk

**3. Credit Note VAT Calculations (Phase 1.3)**
- **Risk:** Complex VAT reversal logic, edge cases
- **Mitigation:** Extensive unit tests, accountant review
- **Fallback:** Manual journal entry correction

**4. Transaction Auto-Matching (Phase 3.2)**
- **Risk:** False positive matches, edge cases
- **Mitigation:** Confidence scoring, manual review queue
- **Fallback:** Manual matching

### Low Risk

**5. Approval Workflows (Phase 4.1)**
- **Risk:** Complex state machine
- **Mitigation:** Use proven patterns from other projects

---

## SUCCESS METRICS

### Phase 1 (Compliance)
- [ ] 100% of invoices can be tracked through e-filing
- [ ] Zero duplicate VAT return submissions
- [ ] Credit notes reduce output VAT correctly

### Phase 2 (Operations)
- [ ] Suppliers can be invoiced and paid
- [ ] Full audit trail for 100% of documents
- [ ] Estimate → Proforma → Invoice conversion <30 seconds

### Phase 3 (Automation)
- [ ] Daily bank sync for 3 major banks
- [ ] 80%+ automatic transaction matching
- [ ] Reconciliation time reduced by 75%

### Phase 4 (Efficiency)
- [ ] Invoice approval cycle <24 hours
- [ ] Partner commission payout processing automated
- [ ] Large export jobs <5 minutes

---

## ESTIMATED EFFORT

**Total Implementation:** 12 weeks (3 months)

**By Phase:**
- Phase 1 (Compliance): 3 weeks, 120 hours
- Phase 2 (Operations): 3 weeks, 120 hours
- Phase 3 (Banking): 3 weeks, 120 hours
- Phase 4 (Advanced): 3 weeks, 120 hours

**Total:** 480 hours (60 developer-days)

**Team Size:** 1-2 developers

**Timeline:**
- Start: Week of Nov 11, 2025
- Phase 1 Complete: Dec 2, 2025
- Phase 2 Complete: Dec 23, 2025
- Phase 3 Complete: Jan 13, 2026
- Phase 4 Complete: Feb 3, 2026

---

## NEXT ACTIONS

### This Week (Nov 11-17)
1. Review and approve roadmap with stakeholders
2. Set up development branch: `dev/phase-1-compliance`
3. Create tickets in issue tracker for Phase 1.1
4. Begin Milestone 1.1: E-Invoice Database Layer

### This Month (November)
- Complete Phase 1.1 (E-Invoice tracking)
- Complete Phase 1.2 (Tax return tracking)
- Start Phase 1.3 (Credit notes)

### This Quarter (Q4 2025)
- Complete Phase 1 (Compliance)
- Complete Phase 2 (Operations)
- Start Phase 3 (Banking)

---

## APPENDIX: FILE REFERENCES

### Existing Infrastructure
- IFRS Adapter: `app/Domain/Accounting/IfrsAdapter.php`
- UBL Mapper: `Modules/Mk/Services/MkUblMapper.php`
- XML Signer: `Modules/Mk/Services/MkXmlSigner.php`
- CPAY Driver: `Modules/Mk/Services/CpayDriver.php`
- VAT Service: `app/Services/VatXmlService.php`
- Commission Calculator: `app/Services/Partner/CommissionCalculatorService.php`

### Models
- Core: Invoice, Estimate, Payment, Expense, RecurringInvoice
- Tax: TaxType, Tax
- Accounting: Company (with ifrsEntity relationship)
- Partner: Partner, Commission, PartnerCompany
- Banking: BankAccount, BankTransaction
- Import: ImportJob (+ 6 temp models)

### Controllers
- Invoices: 7 controllers
- Estimates: 7 controllers
- Payments: 4 controllers
- Tax: VatReturnController
- Webhooks: PaddleWebhookController, CpayCallbackController

### Tests
- `tests/Feature/Accounting/IfrsIntegrationTest.php`
- `tests/Feature/CertificateUploadTest.php` (17 test cases)
- `tests/Feature/PaddleWebhookTest.php` (586 lines)
- `tests/Feature/Partner/PartnerApiTest.php`

---

## ROADMAP REVISION NOTES (Based on Stakeholder Feedback)

### Key Changes from Original Plan

**1. Audit Logging Moved to Phase 1, Week 1** 🔒
- **Rationale:** Need immutable audit trail from day 1 for compliance
- **Impact:** Phase 1 extended from 3 to 4 weeks
- **Benefit:** If anything misfiled in Week 2-4, we have logs immediately

**2. Entity Null Guards Added Throughout**
- **Rationale:** Prevent multi-tenant leakage bugs seen in other projects
- **Implementation:** EntityGuard domain service throws on null entity
- **Testing:** Unit tests for guard failures + cross-tenant isolation tests

**3. Certificate Storage Encrypted, No Raw Keys**
- **Rationale:** Security best practice - private keys never in plaintext DB
- **Implementation:** Encrypted blob storage, password per-session
- **Rotation:** Expiry alerts (30-day), dry-run verify endpoint

**4. Credit Notes as First-Class Documents**
- **Rationale:** Not just flag on Invoice - needs own UBL CreditNote XML
- **Implementation:** Separate number series, own PDF templates, UBL mapper
- **IFRS:** Reference original transaction_id, reverse entries properly

**5. Tax Return Period Locking**
- **Rationale:** Prevent backdated changes after filing
- **Implementation:** Close period locks all source docs, reopen requires reason
- **Double-Filing:** Prevention via TaxReturn duplicate check

**6. PSD2: Thin AIS Slice Only**
- **Rationale:** Start with ONE bank, defer PIS to reduce risk
- **Implementation:** NLB or Stopanska OAuth, 90-day fetch, keep MT940 fallback
- **Incremental:** Add other banks after first one works

**7. Backfill Jobs for Existing Data**
- **Rationale:** Migrate existing invoices to new EInvoice tracking
- **Implementation:** Week 4 backfill jobs for EInvoice + TaxReturn
- **Safety:** Dry-run mode, rollback capability

**8. Number Series Immutability**
- **Rationale:** Prevent renumbering sent invoices, ensure uniqueness
- **Implementation:** Guards check document status before allowing number change
- **Concurrency:** Tests for duplicate prevention under load

**9. Observer Parity**
- **Rationale:** All document types must auto-post to IFRS
- **Implementation:** ExpenseObserver, CreditNoteObserver added in Phase 1

**10. PII Encryption**
- **Rationale:** VAT IDs, IBANs are sensitive
- **Implementation:** Encrypt at rest, mask in logs, decrypt in memory only

### Phase 1 Acceptance Criteria (All Must Pass)

**Week 1 (Audit + Guards):**
- [ ] Every document change creates audit log entry
- [ ] Null entity throws DomainException
- [ ] Cross-tenant queries return 0 results
- [ ] PII fields encrypted in audit logs

**Week 2 (E-Invoice):**
- [ ] 10 invoices tracked end-to-end (draft → signed → submitted → accepted)
- [ ] Failed submissions retry automatically
- [ ] Certificate private key encrypted
- [ ] Signed XML + receipt stored

**Week 3 (Tax Returns):**
- [ ] Period close blocks invoice edits
- [ ] Exact XML persisted on submission
- [ ] Double-filing prevented
- [ ] Reopen period requires reason + audit log

**Week 4 (Credit Notes + Backfill):**
- [ ] Credit note reduces VAT correctly
- [ ] UBL CreditNote validates against schema
- [ ] Posted credit notes immutable
- [ ] Existing invoices have EInvoice records

### Additional Safety Measures

**Security:**
- API tokens per company (prevent cross-tenant API access)
- Certificate key storage uses OS keychain or KMS
- All PII fields encrypted at rest
- Signature logs include IP address + user agent

**Performance:**
- Queued jobs for UBL generation + signing + submission
- Idempotency keys prevent duplicate submissions
- Ops dashboard: queue depth, failed jobs, last sync per company

**UX:**
- Health ping for e-ujp portal with warning banner
- "Simulate submission" (validate without submitting)
- Print preview with sample MK data for accountant review

**Testing:**
- 80% minimum code coverage for Phase 1
- Multi-tenant isolation tests for all new models
- Concurrency tests for number series
- Entity guard failure tests

---

**Document Version:** 2.0 (Revised based on stakeholder feedback)
**Last Updated:** 2025-11-10
**Revision Date:** 2025-11-10
**Next Review:** 2025-11-17 (after Phase 1.1 kickoff)
