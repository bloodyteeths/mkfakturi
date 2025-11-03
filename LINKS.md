# Fakturino v1 - Package Links & Commit Hashes

**Last Updated:** 2025-11-03
**Purpose:** Track all third-party package URLs, licenses, and pinned commit hashes

---

## 📦 INSTALLED PACKAGES (Foundation)

### laravel/pennant
- **URL:** https://github.com/laravel/pennant
- **Packagist:** https://packagist.org/packages/laravel/pennant
- **License:** MIT ✅
- **Version:** v1.18.3
- **Commit:** (to be pinned after composer.lock update)
- **Installed:** 2025-11-03 by FlagsAndDocs agent
- **Purpose:** Feature flag management
- **Swap Difficulty:** LOW (standard Laravel package)

### symfony/http-client
- **URL:** https://github.com/symfony/http-client
- **Packagist:** https://packagist.org/packages/symfony/http-client
- **License:** MIT ✅
- **Version:** v7.3.4
- **Commit:** (to be pinned after composer.lock update)
- **Installed:** 2025-11-03 by FlagsAndDocs agent
- **Purpose:** HTTP client for PSD2 OAuth and CPAY requests
- **Swap Difficulty:** MEDIUM (well-defined interface)

---

## 🔄 PENDING PACKAGES (To Be Installed)

### Step 1: ekmungai/eloquent-ifrs
- **URL:** https://github.com/ekmungai/eloquent-ifrs
- **Packagist:** https://packagist.org/packages/ekmungai/eloquent-ifrs
- **License:** MIT ✅
- **Target Version:** v3.2.0
- **Purpose:** Double-entry accounting backbone
- **Stars:** 1,500+
- **Last Release:** 2024-07-15
- **Swap Difficulty:** MEDIUM (adapter layer isolates)

### Step 2: maatwebsite/excel
- **URL:** https://github.com/SpartnerNL/Laravel-Excel
- **Packagist:** https://packagist.org/packages/maatwebsite/excel
- **License:** MIT ✅
- **Target Version:** v3.1.55
- **Purpose:** CSV/XLSX import with queue support
- **Stars:** 12,000+
- **Last Release:** 2024-06-20
- **Swap Difficulty:** LOW (isolated import classes)

### Step 2: league/csv
- **URL:** https://github.com/thephpleague/csv
- **Packagist:** https://packagist.org/packages/league/csv
- **License:** MIT ✅
- **Target Version:** v9.16.0
- **Purpose:** CSV streaming and encoding detection
- **Stars:** 3,000+
- **Last Release:** 2024-05-20
- **Swap Difficulty:** LOW (lightweight helper)

### Step 3: laravel/cashier-paddle
- **URL:** https://github.com/laravel/cashier-paddle
- **Packagist:** https://packagist.org/packages/laravel/cashier-paddle
- **License:** MIT ✅
- **Target Version:** v2.8.0
- **Purpose:** Paddle payment gateway integration
- **Stars:** 600+
- **Last Release:** 2024-05-15
- **Swap Difficulty:** LOW (official Laravel package)

### Step 5: jejik/mt940
- **URL:** https://github.com/jekyll/mt940
- **Packagist:** https://packagist.org/packages/jejik/mt940
- **License:** MIT ✅
- **Target Version:** Latest stable
- **Purpose:** MT940/CSV bank statement parser (fallback for banks without OAuth)
- **Swap Difficulty:** LOW (optional fallback)

### Step 7: @modelcontextprotocol/sdk (NPM)
- **URL:** https://github.com/modelcontextprotocol/typescript-sdk
- **NPM:** https://www.npmjs.com/package/@modelcontextprotocol/sdk
- **License:** MIT ✅
- **Target Version:** v0.5.0
- **Purpose:** MCP AI tools server SDK
- **Stars:** 2,000+
- **Last Release:** 2024-11-15
- **Swap Difficulty:** MEDIUM (stateless tools, can rewrite)

### Step 8: superbalist/laravel-prometheus-exporter
- **URL:** https://github.com/Superbalist/laravel-prometheus-exporter
- **Packagist:** https://packagist.org/packages/superbalist/laravel-prometheus-exporter
- **License:** MIT ✅
- **Target Version:** v2.6.1
- **Purpose:** Prometheus metrics exporter
- **Stars:** 330+
- **Last Release:** 2023-08-10
- **Swap Difficulty:** LOW (standard metrics interface)

---

## 📋 LICENSE COMPLIANCE MATRIX

| Package | License | Commercial Use | Derivative Works | Source Disclosure |
|---------|---------|----------------|------------------|-------------------|
| laravel/pennant | MIT | ✅ Yes | ✅ Yes | ❌ No |
| symfony/http-client | MIT | ✅ Yes | ✅ Yes | ❌ No |
| ekmungai/eloquent-ifrs | MIT | ✅ Yes | ✅ Yes | ❌ No |
| maatwebsite/excel | MIT | ✅ Yes | ✅ Yes | ❌ No |
| league/csv | MIT | ✅ Yes | ✅ Yes | ❌ No |
| laravel/cashier-paddle | MIT | ✅ Yes | ✅ Yes | ❌ No |
| jejik/mt940 | MIT | ✅ Yes | ✅ Yes | ❌ No |
| @modelcontextprotocol/sdk | MIT | ✅ Yes | ✅ Yes | ❌ No |
| superbalist/laravel-prometheus-exporter | MIT | ✅ Yes | ✅ Yes | ❌ No |

**Status:** ✅ All packages use permissive MIT license
**No GPL dependencies** - Safe for commercial deployment

---

## 🔒 COMMIT HASH PINNING

After each package installation, the agent must:
1. Run `composer show <package> --format=json | jq -r '.source.reference'`
2. Update this file with the commit SHA
3. Document in INTEGRATIONS.md

**Purpose:** Reproducible builds, security auditing, rollback safety

---

## 🔄 SWAP-OUT DECISION MATRIX

### When to consider swapping a package:
- 🔴 **Critical:** Security vulnerability with no patch
- 🟠 **High:** Package abandoned (no updates >1 year)
- 🟡 **Medium:** Better alternative available
- 🟢 **Low:** Performance optimization opportunity

### Current Risk Assessment:
- laravel/pennant: 🟢 Official Laravel package, actively maintained
- symfony/http-client: 🟢 Symfony core component, stable
- ekmungai/eloquent-ifrs: 🟡 Active but small team, adapter layer isolates
- maatwebsite/excel: 🟢 Battle-tested, large community
- league/csv: 🟢 League project, well-maintained
- laravel/cashier-paddle: 🟢 Official Laravel package
- jejik/mt940: 🟡 Optional fallback, low risk
- @modelcontextprotocol/sdk: 🟡 New but backed by Anthropic
- superbalist/laravel-prometheus-exporter: 🟠 Last update 2023, but stable

---

## 📝 AGENT INSTRUCTIONS

When installing a package:
1. Add to "INSTALLED PACKAGES" section
2. Pin commit hash from composer.lock
3. Remove from "PENDING PACKAGES"
4. Update INTEGRATIONS.md
5. Test swap-out path if risk 🟠 or above

---

**License Audit:** ✅ PASSED (All MIT, no GPL)
**Security Audit:** ⏳ PENDING (run `composer audit` after all installations)

// CLAUDE-CHECKPOINT
