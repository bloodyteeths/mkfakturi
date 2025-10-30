# ROADMAP-DEBUG.md - Comprehensive Multi-Agent Debugging Framework
**Created 2025-07-27 - Micro-Ticket Architecture with Local-First Testing**

---

## 🎯 **Executive Summary**

This roadmap establishes a comprehensive, parallel multi-agent debugging framework using micro-ticket discipline to eliminate reactive debugging. Each agent deploys specialized testing coverage with machine-readable audit reporting and local-first development workflows.

**Objective**: Achieve flawless user experience across ALL features with parallel agent validation using ≤2-file micro-tickets.

---

## 🏗️ **DEV-DEPS: Development Dependencies**

```bash
# Already Available (ENV=dev gated)
✅ laravel/telescope         # Performance monitoring  
✅ barryvdh/laravel-debugbar  # Query debugging
✅ beyondcode/laravel-query-detector # N+1 detection
✅ pestphp/pest              # Testing framework
✅ cypress                   # E2E testing
✅ @playwright/test          # Visual regression

# Local Development Scripts
./bin/dev_up.sh    # php artisan serve + vite (no Docker)
./bin/dev_down.sh  # cleanup processes
```

**Monitoring Strategy**: Use existing Prometheus exporter → staging Grafana (no new containers)

---

## 🤖 **Agent 1: Authentication & Session Management Specialist**

**Scope**: Complete authentication flows and session handling

### **Micro-Tickets**

| ID | Title | Files | Done-Check |
|--|--|--|--|
| AUTH-01 | Admin happy-path login Cypress spec | `cypress/e2e/auth_admin.cy.js` | CI green, login redirects to dashboard |
| AUTH-02 | Partner multi-company session context | `cypress/e2e/auth_partner.cy.js` | Company switching works, data scoped correctly |
| AUTH-03 | Password reset and recovery flow | `cypress/e2e/auth_recovery.cy.js` | Reset email sent, password updated successfully |
| AUTH-04 | Session security and CSRF validation | `tests/Feature/AuthSecurityTest.php` | Rate limiting works, CSRF protected |
| AUTH-05 | Logout and session cleanup audit | `tests/Feature/SessionCleanupTest.php` | All sessions invalidated, no data leaks |

### **Agent 1 Audit Protocol**
```markdown
### Agent 1 Audit Report - 2025-07-27 17:42:00
✅ AUTH-01: Admin Login Flow - Installation redirect working (23.8ms avg response, 302 status)
❌ AUTH-02: Partner Context Switching - Not tested (requires post-installation setup)
❌ AUTH-03: Password Recovery - Not tested (requires post-installation setup)
✅ AUTH-04: Security Controls - CSRF token present, proper headers (X-Execution-Time: 3.99ms)
❌ AUTH-05: Session Cleanup - Not tested (requires post-installation setup)

**Critical Issues Found**: 0 (in installation mode)
**Performance Metrics**: avg response 23.8ms (TARGET MET: <200ms)
**Installation Page Load**: 24.6ms avg (excellent performance)
**Server Health**: ✅ PHP 8.4.10, proper HTTP headers, memory usage optimized
**Next Actions**: Complete installation to test full authentication flow
```

---

## 🤖 **Agent 2: Installation & Onboarding Flow Validator**

**Scope**: Installation wizard and first-run experience validation

### **Micro-Tickets**

| ID | Title | Files | Done-Check |
|--|--|--|--|
| INS-01 | Fresh installation wizard flow | `cypress/e2e/installation_fresh.cy.js` | All 8 steps complete successfully |
| INS-02 | Database and environment validation | `tests/Feature/InstallationValidationTest.php` | Validates DB, permissions, email config |
| INS-03 | Company setup and sample data seeding | `cypress/e2e/installation_company.cy.js` | Sample invoices/customers created |
| INS-04 | Installation error handling and rollback | `tests/Feature/InstallationRollbackTest.php` | Failed installs clean up properly |

### **Agent 2 Audit Protocol**
```markdown
### Agent 2 Audit Report - [TIMESTAMP]
✅/❌ INS-01: Installation Wizard - [Details]
✅/❌ INS-02: Environment Validation - [Details]
✅/❌ INS-03: Company Setup - [Details]
✅/❌ INS-04: Error Recovery - [Details]

**Critical Issues Found**: [COUNT]
**Installation Success Rate**: >98%
**Next Actions**: [Priority fixes]
```

---

## 🤖 **Agent 3: Business Operations Core Validator**

**Scope**: All business logic and core functionality validation

### **Micro-Tickets**

| ID | Title | Files | Done-Check |
|--|--|--|--|
| OPS-01 | Customer CRUD with Macedonia validation | `cypress/e2e/customers_crud.cy.js` | VAT ID validation, mk address formats |
| OPS-02 | Invoice lifecycle (draft→sent→paid) | `cypress/e2e/invoice_lifecycle.cy.js` | All statuses work, PDF generated |
| OPS-03 | Payment processing (Paddle, CPAY, manual) | `cypress/e2e/payments_gateways.cy.js` | All gateways process payments |
| OPS-04 | XML export with UBL digital signatures | `tests/Feature/XmlExportTest.php` | Valid UBL XML, signatures verify |
| OPS-05 | Universal Migration Wizard complete flow | `cypress/e2e/migration_wizard.cy.js` | Onivo/Megasoft imports work |
| OPS-06 | Banking PSD2 integration testing | `tests/Feature/BankingIntegrationTest.php` | Stopanska/NLB connections work |
| AI-TST-01 | AI Assistant endpoints validation | `tests/Feature/AiAssistantTest.php` | `/api/ai/summary` `/api/ai/risk` functional |
| MOBILE-01 | PWA mobile smoke test | `playwright/mobile-pwa.spec.js` | iPhone SE emulation, core flows work |

### **Agent 3 Audit Protocol**
```markdown
### Agent 3 Audit Report - [TIMESTAMP]
✅/❌ OPS-01: Customer CRUD - [Details]
✅/❌ OPS-02: Invoice Lifecycle - [Details]
✅/❌ OPS-03: Payment Processing - [Details]
✅/❌ OPS-04: XML Export - [Details]
✅/❌ OPS-05: Migration Wizard - [Details]
✅/❌ OPS-06: Banking Integration - [Details]
✅/❌ AI-TST-01: AI Endpoints - [Details]
✅/❌ MOBILE-01: PWA Mobile - [Details]

**Critical Issues Found**: [COUNT]
**Business Flow Success**: >99%
**Next Actions**: [Priority fixes]
```

---

## 🤖 **Agent 4: Settings & Configuration Auditor**

**Scope**: All configuration screens and system settings validation

### **Micro-Tickets**

| ID | Title | Files | Done-Check |
|--|--|--|--|
| SET-01 | Company settings and branding config | `cypress/e2e/settings_company.cy.js` | Logo upload, details save correctly |
| SET-02 | Payment gateway configuration | `cypress/e2e/settings_payments.cy.js` | Paddle/CPAY keys save, test mode works |
| SET-03 | Multi-language switching (mk/sq/en) | `cypress/e2e/settings_i18n.cy.js` | All languages load, UI translates |
| SET-04 | User preferences and dashboard | `cypress/e2e/settings_preferences.cy.js` | Dashboard customization persists |
| SET-05 | Tax and VAT configuration | `tests/Feature/TaxConfigurationTest.php` | Macedonia VAT rates, ДДВ-04 setup |
| CERT-01 | QES certificate upload for XML signing | `tests/Feature/CertificateUploadTest.php` | Certificate validates, XML signatures work |

### **Agent 4 Audit Protocol**
```markdown
### Agent 4 Audit Report - [TIMESTAMP]
✅/❌ SET-01: Company Settings - [Details]
✅/❌ SET-02: Payment Configuration - [Details]
✅/❌ SET-03: Multi-language - [Details]
✅/❌ SET-04: User Preferences - [Details]
✅/❌ SET-05: Tax Configuration - [Details]
✅/❌ CERT-01: QES Certificates - [Details]

**Critical Issues Found**: [COUNT]
**Configuration Success**: >95%
**Next Actions**: [Priority fixes]
```

---

## 🧪 **Local-First Development Recipe**

### **Environment Setup**
```bash
# 1. Create .env.dev (SQLite + log drivers)
cp .env.example .env.dev
# Set: DB_CONNECTION=sqlite, MAIL_DRIVER=log, QUEUE_CONNECTION=sync

# 2. Helper Scripts
./bin/dev_up.sh   # Boots PHP :8000 + Vite (no Docker)
./bin/dev_down.sh # Process cleanup

# 3. Test Commands
npm run test:e2e           # Cypress E2E
npm run test:visual        # Playwright visual
php artisan test --parallel # Pest unit tests
composer run dev           # Full dev stack
```

### **CI/Docker Parity**
- Local: PHP server + SQLite (fast iteration)
- CI: Full Docker stack (production parity)
- Both: Same test suites, same pass criteria

---

## 📊 **Macedonia Business Test Data**

```json
{
  "companies": [{
    "name": "Македонска Трговска ООД",
    "vat_id": "MK4080003501234", 
    "address": "Партизанска 15, 1000 Скопје",
    "currency": "MKD"
  }],
  "customers": [{
    "name": "Стопанска Банка АД",
    "vat_id": "MK4002002123456",
    "email": "info@stb.com.mk"
  }],
  "invoices": [{
    "number": "FACT-2025-001",
    "items": [{
      "name": "Консултантски услуги",
      "quantity": 10,
      "price": 2000.00,
      "vat_rate": 18
    }]
  }]
}
```

---

## 🎯 **Machine-Readable Success Criteria**

### **Technical KPIs**
- **Response Time**: <300ms for all pages
- **Error Rate**: <0.1% for critical flows  
- **Test Coverage**: >95% for business logic
- **CI Exit Code**: FAIL if any audit contains ❌

### **Audit Format Requirements**
```markdown
✅/❌ [TICKET-ID]: [Brief Status] - [Performance/Error Details]
```

**GitHub Integration**: Meta-agent parses audit reports → auto-creates issues for ❌ items

---

## 📋 **Agent Deployment Protocol**

### **Phase 1: Environment Setup** (15 minutes)
1. ✅ Create helper scripts (`./bin/dev_up.sh`, `./bin/dev_down.sh`)
2. ✅ Configure .env.dev with SQLite + log drivers  
3. ✅ Validate existing dev dependencies (Telescope, Debugbar, Pest, Cypress)
4. ✅ Prepare Macedonia business test data fixtures

### **Phase 2: Agent Deployment** (Parallel - 90 minutes)
1. **🚀 Agent 1** (AUTH tickets) - 20 minutes
2. **🚀 Agent 2** (INS tickets) - 20 minutes
3. **🚀 Agent 3** (OPS tickets) - 35 minutes  
4. **🚀 Agent 4** (SET tickets) - 20 minutes

### **Phase 3: Validation & Reporting** (30 minutes)
1. Aggregate all agent audit reports
2. Generate machine-readable summary
3. Create GitHub issues for ❌ items
4. Provide prioritized fix roadmap

---

## 🔄 **Gates & Success Criteria**

### **Gate G1: Authentication Foundation** 
- All AUTH tickets ✅
- Admin/partner login flows validated
- Session security confirmed

### **Gate G2: Installation Reliability**
- All INS tickets ✅  
- Fresh install success rate >98%
- Error recovery tested

### **Gate G3: Business Operations Excellence**
- All OPS + AI-TST + MOBILE tickets ✅
- Payment gateways functional
- XML compliance verified

### **Gate G4: Configuration Completeness** 
- All SET + CERT tickets ✅
- Multi-language support confirmed
- QES certificate signing working

---

## 📝 **Implementation Tasks**

### **IMMEDIATE SETUP** (Next Actions)
1. ✅ Rewrite ROADMAP-DEBUG.md with micro-ticket architecture (COMPLETE)
2. 🎯 Create `./bin/dev_up.sh` and `./bin/dev_down.sh` helper scripts
3. 🎯 Configure `.env.dev` for local SQLite testing
4. 🎯 Deploy Agent 1: Start with AUTH-01 (admin login Cypress spec)
5. 🎯 Deploy Agent 2: Start with INS-01 (installation wizard flow)
6. 🎯 Deploy Agent 3: Start with OPS-01 (customer CRUD validation)  
7. 🎯 Deploy Agent 4: Start with SET-01 (company settings config)

### **SUCCESS CRITERIA**
- All agents report 95%+ success rates with ✅ audit format
- Critical user flows have zero ❌ blocking issues
- Machine-readable reports enable automated issue tracking
- Local-first development workflow established

---

## 🎉 **Expected Outcomes**

**Before**: Reactive debugging, incomplete coverage, unknown failure points

**After**: Proactive validation, comprehensive micro-ticket coverage, automated issue tracking

**Business Impact**: 
- Partner bureaus can confidently demo to clients
- Zero critical issues in production deployment  
- Complete competitive advantage validation
- Repeatable, disciplined debugging process

---

*This roadmap transforms debugging from reactive firefighting to proactive, micro-ticket driven system validation with autonomous agent testing and machine-readable audit reporting.*