# FACTURINO v1 – Final Sprint to 100% (ROADMAP3.md)
**QA Completion + Universal Migration Wizard → Production Ready Platform**

---

## 🎯 **Current Status: ~85% Functional** *(Updated 2025-07-25)*

### ✅ **Infrastructure & Core Features Complete**
- **Docker Stack**: Production-ready with HTTPS, security hardening, health checks
- **Database**: All MK business models (Partner, BankAccount, Commission) operational
- **PSD2 Banking**: Stopanska and NLB bank transaction sync implemented
- **Payment Processing**: Paddle integration with webhook validation
- **UBL XML Export**: Digital signature capability for tax compliance
- **CSV Import**: 3-step wizard for customers, items, expenses
- **Monitoring**: Telescope + Prometheus metrics with business intelligence
- **Testing**: Comprehensive test suites for critical business logic
- **Security**: Docker hardening, secrets scanning, real API endpoints

### ⚠️ **Remaining Critical Gaps** 
- **QA Phase**: End-to-end testing, validation, performance optimization
- **Migration System**: ZERO competitor migration capability (critical market need)

---

## 🚨 **Critical Insight: Migration is Our Competitive Moat**

**Market Reality**: Macedonia businesses won't switch from Onivo/Megasoft/Pantheon without **painless data migration**. Current CSV import handles individual entities - we need **complete business migration**.

**Competitive Advantage**: Universal Migration Wizard will make switching from ANY Macedonia accounting software **trivial** (minutes, not months).

---

## 📋 **Phase 1: QA Completion (Days 1-5) - Foundation First**

| ID | Title | Description | Files (≤2) | Success Criteria |
|----|-------|-------------|------------|------------------|
| **MON-02** | Portainer HTTPS Security | Create secure Portainer stack with HTTPS :9443 | docker/portainer-compose.yml, Caddyfile update | Management UI accessible via HTTPS | ✅ DONE |
| **QA-01** | End-to-End Payment Testing | Paddle → Invoice → Paid flow validation | tests/Feature/PaymentFlowTest.php | Complete payment cycle works |
| **QA-02** | XML Export Integration | UBL → Sign → Download flow testing | tests/Feature/XmlExportTest.php | Signed XML downloads correctly |
| **QA-03** | Language File Standardization | Consolidate mk.json/sq.json to single location | resources/lang/ directory cleanup | i18n works consistently |
| **QA-04** | Performance Optimization | Add caching + query optimization | CacheServiceProvider.php, config/cache.php | Response times <300ms |
| **QA-05** | Error Handling | Improve exceptions + logging | ExceptionHandler.php updates | Clean error pages, comprehensive logs |

---

## 🎯 **Phase 2: Universal Migration Wizard (Days 6-12) - Market Domination**

### **Architecture: Clean Domain-Driven Design**
```
App/Domain/Migration/         # Core business logic
App/Application/Migration/    # Use cases & services  
App/Infrastructure/Migration/ # External adapters
```

### **Core Components**

| Component | Purpose | Files | Implementation |
|-----------|---------|-------|----------------|
| **Domain Models** | Import job tracking + temp storage | ImportJob, ImportTemp*, MappingRule | Database migrations + Eloquent models |
| **API Endpoints** | Upload, mapping, validation, commit | MigrationController | RESTful API with Sanctum auth |
| **Background Jobs** | File parsing, auto-mapping, validation | DetectFileType, Parse, AutoMap, Validate, Commit | Laravel queues on "imports" queue |
| **Field Mapper** | Macedonian language corpus matching | FieldMapper service | Heuristic + AI scoring (0-1 confidence) |
| **Data Transformers** | Clean & convert formats | DateTransformer, DecimalTransformer, CurrencyTransformer | EUR↔MKD, dd.mm.yyyy → Y-m-d |
| **Vue Wizard** | 4-step user interface | ImportWizard.vue + Pinia store | Upload → Mapping → Validation → Commit |
| **OnivoFetcher** | Direct competitor export | Playwright automation script | Headless browser data extraction |

### **Target Competitor Systems**
- **Primary**: Onivo (market leader in Macedonia)
- **Secondary**: Megasoft, Pantheon, Syntegra (major Balkan players)
- **Formats**: CSV, Excel, XML, PDF attachments, direct web scraping

### **Migration Data Coverage**
- **Complete Business**: Customers + Invoices + Items + Payments + Expenses
- **Relationships**: Preserve invoice line items, customer payments, tax calculations
- **Attachments**: PDF storage with S3 linking
- **Audit Trail**: Full transformation logging + 7-year retention

---

## 📊 **Phase 3: Accountant-System Integrations (After QA Complete)**

| ID      | Title                              | OSS repo / URL                                   | Pull-cmd / Copy                                             | Files (≤2)                                   | Done-check |
|---------|------------------------------------|--------------------------------------------------|-------------------------------------------------------------|----------------------------------------------|------------|
| **AI-01** | MiniMax token migration            | –                                                | artisan make:migration `minimax_tokens`                      | migration, Model `MiniMaxToken.php`          | `php artisan migrate` green |
| **AI-02** | Install MiniMax API client         | https://github.com/minimaxapi/MinimaxAPISamplePHP | **copy** `ApiClient.php` → `modules/Mk/Services`            | ApiClient.php                                | class resolves in Tinker |
| **AI-03** | Push invoice → MiniMax             | –                                                | create `MiniMaxSyncService.php` + feature test               | service, tests/Feature/MiniMaxSyncTest.php   | API 201 Created |
| **AI-04** | PANTHEON eSlog generator           | https://github.com/media24si/eslog2              | `composer require media24si/eslog2`                          | composer.*, `PantheonExportJob.php`          | XML validates against eSlog XSD |
| **AI-05** | Nightly eSlog export cron          | –                                                | update `app/Console/Kernel.php` scheduler                    | Kernel.php                                   | XML file lands in `storage/exports/` nightly |
| **AI-06** | (opt) PANTHEON Web-services push   | –                                                | `PantheonSyncService.php` + unit test                        | service, tests/Unit/PantheonSyncTest.php     | API HTTP 200 |

---

## 🔧 **Container / Dependency Fixes**

| ID        | Title                                           | Pull-cmd / Action                            | Files (≤2)                | Done-check |
|-----------|-------------------------------------------------|----------------------------------------------|---------------------------|------------|
| **CR-04a** | Test laravel-cpay on Laravel 12 (SOAP ext)      | run `composer test-cpay` script inside container | `tests/Unit/CpayCompatTest.php` | Passes ✔ / fails ✘ |
| **CR-04b** | Install alt library *only if* 04a fails         | `composer require idrinth/laravel-cpay-bridge` | composer.json / lock      | class resolves |

---

## 🔒 **Migration Safety**

| ID          | Title                                         | Pull-cmd / Copy                               | Files                     | Done-check |
|-------------|-----------------------------------------------|-----------------------------------------------|---------------------------|------------|
| **DB-SAFE-01** | New migration for `bank_transactions`        | artisan make:migration `add_bank_transactions` | 2025_09_01_add_bank_transactions.php | `php artisan migrate` ok |

---

## 🚀 **CI Pipeline**

| ID    | Title                                 | Pull-cmd / Copy                              | Files                             | Done-check |
|-------|---------------------------------------|----------------------------------------------|-----------------------------------|------------|
| **CI-01** | GitHub Actions CI pipeline          | copy sample → `.github/workflows/ci.yml`     | ci.yml                            | GH Actions status ✔ |

---

## 🐳 **Verify Prod Compose / TLS** 

| ID        | Title                       | Pull-cmd / Copy                    | Files                                      | Done-check |
|-----------|-----------------------------|------------------------------------|--------------------------------------------|------------|
| **DEP-01** | Production docker stack     | copy from InvoiceShelf/docker repo | docker-compose-prod.yml                    | `curl https://<domain>/health` returns OK |
| **DEP-02** | TLS via Caddy               | create/update `docker/Caddyfile`   | docker/Caddyfile                           | Browser SSL padlock |

---

## 🏗️ **Implementation Timeline (7 Days)**

### **Day 1-2: Foundation**
- Database migrations (import_jobs, import_temp_*, mapping_rules, import_logs)
- Domain models with relationships
- API route skeleton (/api/imports/*)

### **Day 3-4: Core Logic** 
- File parsers (CSV, Excel, XML) with league/csv + phpoffice/phpspreadsheet
- Heuristic field mapper with Macedonian corpus:
  ```
  naziv→name, embs→tax_id, iznos→amount, 
  skladište→warehouse, količina→quantity, 
  pdv_stapka→vat_rate, kupac→customer_name
  ```
- Background job queue with progress tracking

### **Day 5: Frontend**
- Vue 3 wizard with FilePond uploads
- Pinia store for state management
- Real-time progress polling

### **Day 6: Competitor Integration**
- Playwright OnivoFetcher (/tools/onivo-fetcher/)
- Automated login → export → zip workflow
- ONIVO_EMAIL, ONIVO_PASS environment configuration

### **Day 7: Testing & Polish**
- Feature tests for complete migration flows
- Unit tests for field mapping accuracy
- Error handling and rollback capabilities

---

## 📦 **Required Dependencies**

### **Backend Packages**
```bash
composer require league/csv              # Stream CSV reading
composer require phpoffice/phpspreadsheet # Excel processing  
composer require spatie/laravel-queueable-actions # Modular jobs
```

### **Frontend Packages**
```bash
npm install filepond pinia inertia-progress
```

### **Tools**
```bash
cd tools/onivo-fetcher && npm install playwright
```

---

## 🧪 **Testing Strategy**

### **Critical Test Coverage**
- `tests/Feature/Import/UploadTest.php` - File upload validation
- `tests/Unit/FieldMapperTest.php` - Macedonian field mapping accuracy
- `tests/Feature/CommitImportTest.php` - Rollback → commit integrity
- `tests/Playwright/OnivoFetcher.test.ts` - Competitor data extraction

### **Business Logic Validation**
- Complete invoice chains (customer → invoice → line items → payments)
- Tax calculation preservation (18% standard, 5% reduced VAT)
- Currency conversion accuracy (EUR ↔ MKD via fixer.io)
- Duplicate detection and conflict resolution

---

## 🔒 **Security & Compliance**

### **Data Protection**
- Encrypt original files in `storage/private/imports/{job_id}/source`
- Complete audit logging to `import_logs` table (JSON transformations)
- 7-year retention policy with `artisan import:purge --days=365`
- GDPR-compliant data removal on request

### **Access Control**
- Sanctum API authentication for all endpoints
- Company-scoped imports (multi-tenant isolation)
- Admin-only migration management interface

---

## 🎉 **Success Criteria & Market Impact**

### **Technical Milestones**
- ✅ Complete business migration in <10 minutes
- ✅ >95% field mapping accuracy for Macedonia accounting formats
- ✅ Zero data loss during migration process
- ✅ Automatic rollback on validation failures
- ✅ Real-time progress tracking with error reporting

### **Business Impact**
- **Customer Acquisition**: Remove switching friction from Onivo/competitors
- **Market Position**: Only Macedonia platform with universal migration
- **Sales Cycle**: Convert prospects in demo calls with live migration
- **Competitive Moat**: Establish switching costs for our customers

---

## 🔄 **Execution Priority**

### **Immediate (This Week)**
1. Complete QA phase (MON-02 → QA-05)
2. Begin Universal Migration Wizard implementation
3. Test with real Onivo export data

### **Next Phase (After Migration Complete)**
- UI/UX improvements (ROADMAP-UI.md)
- Advanced features (recurring billing, multi-currency)
- Mobile app integration

---

## 📝 **Migration Notes for Future Claude**

### **Key Architecture Decisions**
- **Clean Architecture**: Domain/Application/Infrastructure separation
- **Queue-Based**: All heavy operations in background jobs
- **Stateful Process**: ImportJob tracks progress through states
- **Audit Everything**: Complete transformation trail for debugging

### **Macedonia-Specific Considerations**
- **Language Corpus**: Macedonian/Serbian field name variations
- **Currency Handling**: EUR primary, MKD conversion via fixer.io
- **Tax Rates**: 18% standard, 5% reduced, 0% exempt
- **Date Formats**: dd.mm.yyyy input → Y-m-d storage
- **Decimal Separators**: Comma → dot conversion

### **Critical Integration Points**
- **Existing Models**: Extend Customer, Invoice, Item, Payment
- **Authentication**: Use existing Sanctum + company scoping
- **File Storage**: S3-compatible with existing media library
- **Queue System**: Redis-backed with horizon monitoring

---

## 🏁 **The Finish Line**

**Before Migration Wizard**: 85% functional platform, missing market entry tool
**After Migration Wizard**: 100% production-ready platform with competitive moat

**Market Reality**: Macedonia businesses WILL switch if migration is painless. This wizard makes switching from Onivo as easy as uploading a file.

**Next Action**: Complete MON-02 → QA phase → Launch Universal Migration Wizard

---

## 📋 **Post-QA Plan Reminder**

**After QA-05 is ✅**
- Execute AI-01 … AI-06 (Accountant integrations)
- Then bring in ROADMAP-UI.md tickets (UI-01 … UI-15)

---

*Each phase respects ≤2 files per micro-ticket where possible. Migration wizard spans 7 days but follows incremental progress tracking.*