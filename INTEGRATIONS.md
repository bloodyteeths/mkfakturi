# Fakturino v1 - Third-Party Integrations

**Last Updated:** 2025-11-03
**Purpose:** Track all third-party packages integrated into Fakturino

---

## 📦 INSTALLED PACKAGES

### Foundation & Feature Flags

| Package | Version | License | Purpose | Installed By |
|---------|---------|---------|---------|--------------|
| laravel/pennant | v1.18.3 | MIT | Feature flag management | FlagsAndDocs agent (Step 0) |
| symfony/http-client | v7.3.4 | MIT | HTTP client for PSD2/CPAY | FlagsAndDocs agent (Step 0) |

### Step 1: Accounting Backbone

| Package | Version | License | Purpose | Status |
|---------|---------|---------|---------|--------|
| ekmungai/eloquent-ifrs | v5.0.4 | MIT | Double-entry accounting ledger (IFRS compliant) | ✅ Installed |

**Features Implemented:**
- IfrsAdapter service layer for accounting integration
- Macedonian Chart of Accounts (1000-5999)
- Invoice/Payment observers for automatic ledger posting
- API endpoints: `/accounting/trial-balance`, `/balance-sheet`, `/income-statement`
- Feature flag: `FEATURE_ACCOUNTING_BACKBONE` (default OFF)
- Supports DR Accounts Receivable + CR Revenue on invoice creation
- Supports DR Cash + CR Accounts Receivable on payment
- Payment gateway fee tracking (DR Fee Expense + CR Cash)

### Step 2: Migration Wizard

| Package | Version | License | Purpose | Installed By |
|---------|---------|---------|---------|--------------|
| maatwebsite/excel | v3.1.67 | MIT | CSV/XLSX import with queue support | Migration agent (Step 2) |
| league/csv | v9.27.1 | MIT | CSV streaming and encoding detection | Migration agent (Step 2) |

### Step 3 & 4: Payment Integrations

| Package | Version | License | Purpose | Installed By |
|---------|---------|---------|---------|--------------|
| laravel/cashier-paddle | v2.6.2 | MIT | Paddle payment gateway integration | Paddle agent (Step 3) |
| Custom CPAY Driver | v1.0.0 | AGPL | CPAY (CASYS) payment gateway for Macedonia | CPAY agent (Step 4) |

### Step 5: PSD2 Banking Integration

| Package | Version | License | Purpose | Installed By |
|---------|---------|---------|---------|--------------|
| jejik/mt940 | v0.6.3 | MIT | MT940/CSV parser for bank statements (fallback) | Banking agent (Step 5) |

**Features Implemented:**
- BankToken model with encrypted OAuth token storage
- Psd2Client abstract service class with OAuth2 flow
- StopanskaOAuth and NlbOAuth gateway implementations
- Mt940Parser service for CSV fallback import
- SyncBankTransactions scheduled job (queue: banking)
- BankAuthController with OAuth endpoints
- API endpoints: `/banking/{company}/auth/{bank}`, `/banking/{company}/status/{bank}`, `/banking/{company}/disconnect/{bank}`, `/banking/{company}/import-mt940`
- Feature flag: `FEATURE_PSD2_BANKING` (default OFF)
- Rate limiting: Stopanska 15 req/min, NLB standard limits
- Idempotency by transaction reference
- Automatic token refresh when expiring

### Step 8: Monitoring & Observability

| Package | Version | License | Purpose | Status |
|---------|---------|---------|---------|--------|
| arquivei/laravel-prometheus-exporter | dev-add-laravel-12 | MIT | Prometheus metrics (Laravel 12 compatible) | ✅ Installed |
| laravel/telescope | v5.15+ | MIT | Application debugging and monitoring | ✅ Installed |

**Features Implemented:**
- Prometheus metrics at `/metrics` (feature flag: `FEATURE_MONITORING`)
- Health check endpoint at `/metrics/health`
- Certificate expiry monitoring (`fakturino_signer_cert_expiry_days`)
- Business metrics (invoices, customers, revenue)
- System health metrics (DB, Redis, queues, storage)
- Bank sync monitoring
- Telescope debugging interface at `/telescope` (admin only)

---

## 🔄 PENDING INTEGRATIONS

The following packages will be installed by their respective agents:

### Step 7: MCP AI Tools
- **@modelcontextprotocol/sdk** (v0.5.0, MIT) - MCP TypeScript SDK

---

## 🔍 LICENSE COMPLIANCE

**Status:** ✅ All packages use permissive licenses (MIT/Apache-2.0)

**No GPL dependencies** - Complies with AGPL upstream InvoiceShelf while avoiding GPL contagion

---

## 🔄 SWAP-OUT STRATEGY

If a package needs to be replaced:

1. **Accounting:** Keep `IfrsAdapter` interface unchanged, implement new adapter
2. **Migration:** Import classes are isolated, easy to swap
3. **PSD2:** OAuth flow is custom, can swap HTTP client
4. **MCP:** Stateless tools, can rewrite in different language

---

## 📝 NOTES

- All integrations behind feature flags (default OFF)
- Partner mocked data flag defaults to ON for safety
- Railway deployment validated before enabling features
- Tests required for all integrations

---

**Agents:** When you install a new package, add it to the "INSTALLED PACKAGES" section and remove from "PENDING INTEGRATIONS".

// CLAUDE-CHECKPOINT
