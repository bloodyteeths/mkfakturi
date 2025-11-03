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

---

## 🔄 PENDING INTEGRATIONS

The following packages will be installed by their respective agents:

### Step 2: Migration Wizard
- **maatwebsite/excel** (v3.1.55, MIT) - CSV/XLSX import with queue support
- **league/csv** (v9.16.0, MIT) - CSV streaming and encoding detection

### Step 3: Paddle Payments
- **laravel/cashier-paddle** (v2.8.0, MIT) - Paddle payment gateway integration

### Step 4: CPAY Payments
- Custom driver enhancement (no package exists)
- Uses existing `Modules/Mk/Services/CpayDriver.php`

### Step 5: PSD2 Banking
- **jejik/mt940** (MIT) - Optional MT940/CSV parser for banks without OAuth

### Step 7: MCP AI Tools
- **@modelcontextprotocol/sdk** (v0.5.0, MIT) - MCP TypeScript SDK

### Step 8: Monitoring
- **superbalist/laravel-prometheus-exporter** (v2.6.1, MIT) - Prometheus metrics

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
