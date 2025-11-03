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

### Payment Integrations

| Package | Version | License | Purpose | Installed By |
|---------|---------|---------|---------|--------------|
| laravel/cashier-paddle | v2.6.2 | MIT | Paddle payment gateway integration | Paddle agent (Step 3) |
| Custom CPAY Driver | v1.0.0 | AGPL | CPAY (CASYS) payment gateway for Macedonia | CPAY agent (Step 4) |

---

## 🔄 PENDING INTEGRATIONS

The following packages will be installed by their respective agents:

### Step 1: Accounting Backbone
- **ekmungai/eloquent-ifrs** (v3.2.0, MIT) - Double-entry accounting ledger

### Step 2: Migration Wizard
- **maatwebsite/excel** (v3.1.55, MIT) - CSV/XLSX import with queue support
- **league/csv** (v9.16.0, MIT) - CSV streaming and encoding detection

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
