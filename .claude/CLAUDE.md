# Claude Rules – Facturino v1
(Last update 2026-02-09)

## 0 Mission
Build a Macedonian-localised fork of InvoiceShelf with bank-feed,
QES-signed e-Invoice, Paddle billing, CASYS pay-links and partner
commissions—**nothing else**.

---

## 0.1 Architecture overview

### Two frontends
| Frontend | Stack | Domain | Source dir |
|----------|-------|--------|------------|
| Marketing site | Next.js 14 + Tailwind | `www.facturino.mk` | `website/` |
| App (SPA) | Laravel 10 + Vue 3 + Vite | `app.facturino.mk` | `resources/scripts/` |

### Git submodules
* `docker/` — Docker Compose configs (dev, prod, staging)
* `facturino/` — mirror/fork submodule (orphaned, no `.gitmodules`)
* `invoice2data-service/` — standalone FastAPI Python microservice (NOT a submodule)

### Branding
* Company legal name: **Facturino DOOEL** (Latin) / **Facturino ДООЕЛ** (Cyrillic+Latin mix)
* Never use "MK Accounting" or "Факторино" (full Cyrillic) — always **Facturino** in Latin
* Partner commission rates: **20% monthly / 22% annual**

---

## 0.2 Vue Router architecture (CRITICAL — read before touching routes)

* Main router: `resources/scripts/router/index.js`
* Route load order: `PublicRoutes → PartnerRoutes → AdminRoutes → CustomerRoutes`
* **`/signup` and `/partner/signup` are defined in `admin-router.js`** (lines 252-278) with `isPublic: true`
  — do NOT duplicate them in `public/router/index.js`
* `public/router/index.js` only defines `/privacy` and `/terms`
* Admin router has a catch-all at the end: `/:catchAll(.*)` → NotFoundPage
* Auth guard whitelist (name-based): `login, forgot-password, reset-password, signup, partner-signup, privacy, terms`
* Routes with `meta.isPublic: true` also bypass auth — both mechanisms work
* Partner users accessing `/admin/*` are redirected to `/admin/partner/dashboard`
  unless they have a `selectedCompany` in localStorage or the route is `/admin/console`

### Adding a new public page
1. Create Vue component in `resources/scripts/public/views/`
2. Add route in `resources/scripts/public/router/index.js` with `meta: { requiresAuth: false, isPublic: true }`
3. Add Laravel web route in `routes/web.php`: `Route::get('/your-page', fn() => view('app'))`
4. Do NOT add duplicate routes if they already exist in `admin-router.js`

---

## 0.3 Marketing site (Next.js) i18n

* 4 locales: `mk` (Macedonian), `sq` (Albanian), `tr` (Turkish), `en` (English)
* Dictionary-based i18n in `website/src/i18n/dictionaries.ts` for shared UI (nav, footer, etc.)
* Content pages use **inline `copy` objects** with per-locale keys (not dictionaries)
* All pages must have all 4 locale translations
* APP_URL env: `NEXT_PUBLIC_APP_URL` defaults to `https://app.facturino.mk`

---

## 1 Branch & PR
* **Branch** `ticket/<ID>-<slug>`
* **PR title** `[<ID>] <ticket title>`
* Push only when `php artisan test` & `npm run test` are green.

---

## 2 Dependency white-list (install nothing else)

### Core MVP packages
| Package | Ticket | Install cmd |
|---------|--------|-------------|
| laravel/cashier-paddle | B-31 series | composer require laravel/cashier-paddle |
| bojanvmk/laravel-cpay  | C-10 series | composer require bojanvmk/laravel-cpay |
| oak-labs-io/psd2       | F-10 series | composer require oak-labs-io/psd2 |
| num-num/ubl-invoice    | U-10        | composer require num-num/ubl-invoice |
| robrichards/xmlseclibs | U-12        | composer require robrichards/xmlseclibs |
| league/csv (already)   | I-11        | – |

### Competitive add-on packages
| Package | Ticket | Install cmd |
|---------|--------|-------------|
| media24si/eslog2 | PA-01 | composer require media24si/eslog2 |
| ekmungai/eloquent-ifrs | GL-01 | composer require ekmungai/eloquent-ifrs |
| brick/money | CF-01 | composer require brick/money |
| picqer/php-barcode-generator | INV-01 | composer require picqer/php-barcode-generator |
| simple-software-io/simple-qr-code | INV-01 | composer require simple-software-io/simple-qr-code |
| aws/aws-sdk-php | OCR-01 | composer require aws/aws-sdk-php |
| automattic/woocommerce-api-php | WOO-01 | composer require automattic/woocommerce-api-php |
| dhl-api/dhl-php-sdk | SHIP-01 | composer require dhl-api/dhl-php-sdk |
| keithbrink/affiliates-spark | AC-01 | composer require keithbrink/affiliates-spark |

**Any new lib** ⇒ open an `NX-??` ticket in Backlog; do NOT install.

---

## 3 Token efficiency
* Focus on ONE micro-ticket at a time
* Cache tool output; no duplicate searches
* Use code-search before asking language questions
* Exit early if blocked—create NX ticket

---

## 4 Checkpoint comments
After editing each file add `// CLAUDE-CHECKPOINT`.
Resume from the last checkpoint if interrupted.

---

## 5 File boundaries
* new PHP → `modules/Mk/**`
* new Vue → `resources/js/pages/partner/**`
* public Vue pages → `resources/scripts/public/views/**`
* marketing site pages → `website/src/app/[locale]/<page>/page.tsx`
* marketing site components → `website/src/components/`
* migrations under `database/migrations/2025_08_**.php`
* **NO edits** in `vendor/` or core models
* **NO edits** in `docker/` or `facturino/` submodules without committing inside submodule first

---

## 6 Migrations & DB safety
* All schema tables created in **DB-00** only; later tickets are additive
* Every migration `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`
  to avoid collation errors (errno 150)
* Foreign keys `ON DELETE RESTRICT`
* Test migrations with `php artisan migrate:fresh` before marking done
* **IMPORTANT**: Make migrations idempotent (use `Schema::hasTable()` / `Schema::hasColumn()` checks)
  because migrations run automatically on every Railway deployment

---

## 6.1 Deployment (Railway)
* Hosted on **Railway** at `app.facturino.mk`
* Deployments triggered by push to `main` branch
* **IMPORTANT**: The actual entrypoint is `docker-entrypoint.sh` (NOT `railway-start.sh`)
  - `Dockerfile.mkaccounting` line 199: `COPY docker-entrypoint.sh /entrypoint.sh`
  - `Dockerfile.mkaccounting` line 210: `ENTRYPOINT ["/entrypoint.sh"]`
* Migrations and seeders run automatically on deploy via `docker-entrypoint.sh`
* Macedonian Chart of Accounts seeder runs on every deploy (idempotent)
* No manual SSH needed—Railway handles it
* Logs viewable in Railway dashboard or via `logs/` directory

### Railway gotchas
* Railway injects a `PORT` env var — **always** use `${PORT:-8000}` not hardcoded ports
* Railway `startCommand` does NOT run through a shell — wrap in `sh -c '...'` for variable expansion
* Use **private networking** (`*.railway.internal`) for service-to-service calls, not public URLs
* `railway.json` paths are relative to the service's **root directory** setting in Railway dashboard
  — if root dir is `invoice2data-service/`, then `dockerfilePath` should be `Dockerfile` not `invoice2data-service/Dockerfile`

## 6.2 invoice2data-service (OCR microservice)
* **Stack**: Python 3.11 + FastAPI + Tesseract OCR + invoice2data
* **Source**: `invoice2data-service/` (standalone dir, not a submodule)
* **Railway root dir**: `invoice2data-service`
* **Private URL**: `http://invoice2data-service.railway.internal`
* **Endpoints**: `GET /health`, `POST /parse` (PDF→structured data), `POST /ocr` (image→text)
* **OCR languages**: `mkd+eng+srp` (Macedonian + English + Serbian)
* **Laravel integration**: `Invoice2DataClient.php` calls the service, `ParseInvoicePdfJob` with 3 retries + exponential backoff
* **Timeout**: 90s request timeout, 10s connect timeout
* **Connection errors**: Wrapped in `Invoice2DataServiceException` for graceful degradation
* **Env vars on Laravel app**: `INVOICE2DATA_URL`, `INVOICE2DATA_TIMEOUT=90`
* **Env vars on service**: `OCR_LANGS=mkd+eng+srp`, `PORT=8000`

---

## 7 External API surprises
* **PSD2** rate-limit 15 req/min → sleep 60 s in cron
* **CASYS** UniqueID expires after 20 min
* **xmlsec** PFX chain errors—catch & log
* **Paddle** webhooks need CSRF exemption in VerifyCsrfToken

---

## 8 AGPL compliance
Keep upstream copyright headers;
link public fork in app footer; add `/LEGAL_NOTES.md`.

---

## 9 NX-ticket flow
Stuck on a ticket?  
1. create draft PR `[NX-##]`  
2. append row to Backlog in ROADMAP.md  
3. stop—wait for human review

---

## 10 Testing protocol
* Unit tests for all services
* Feature tests for all API endpoints
* Browser tests for critical UI flows
* Run full test suite before marking ticket done

---

## 11 Code conventions
* Follow InvoiceShelf's existing patterns
* PSR-12 for PHP
* Vue 3 Composition API
* Use existing UI components from InvoiceShelf
* No new CSS frameworks—use existing Tailwind setup

---

## 12 Documentation
* PHPDoc for all public methods
* README updates only when explicitly requested
* API documentation in `/docs/api/` if new endpoints added

---

## 13 Key file locations

### Laravel app (Vue SPA)
| Purpose | Path |
|---------|------|
| Main Vue router | `resources/scripts/router/index.js` |
| Admin router (incl. signup, partner-signup) | `resources/scripts/admin/admin-router.js` |
| Partner router (/admin/partner/*) | `resources/scripts/partner/partner-router.js` |
| Public router (privacy, terms only) | `resources/scripts/public/router/index.js` |
| Login layout | `resources/scripts/admin/layouts/LayoutLogin.vue` |
| Signup layout (shared by signup, partner-signup, legal) | `resources/scripts/public/views/signup/SignupLayout.vue` |
| Partner signup form | `resources/scripts/public/views/partner-signup/PartnerSignup.vue` |
| Legal pages (Vue) | `resources/scripts/public/views/legal/PrivacyPolicy.vue`, `TermsOfService.vue` |
| Laravel web routes | `routes/web.php` |
| invoice2data client | `app/Services/InvoiceParsing/Invoice2DataClient.php` |
| invoice2data job | `app/Jobs/ParseInvoicePdfJob.php` |

### Marketing site (Next.js)
| Purpose | Path |
|---------|------|
| Layout + metadata | `website/src/app/layout.tsx`, `website/src/app/[locale]/layout.tsx` |
| Navbar | `website/src/components/Navbar.tsx` |
| Footer | `website/src/components/Footer.tsx` |
| Hero | `website/src/components/Hero.tsx` |
| i18n dictionaries | `website/src/i18n/dictionaries.ts` |
| Content pages | `website/src/app/[locale]/{features,pricing,how-it-works,for-accountants,e-faktura,security,contact}/page.tsx` |
| Legal pages (Next.js) | `website/src/app/[locale]/{privacy,terms}/page.tsx` |
| SEO | `website/public/sitemap.xml`, `website/public/robots.txt` |

### Infrastructure
| Purpose | Path |
|---------|------|
| Main app Dockerfile | `Dockerfile.mkaccounting` |
| App entrypoint | `docker-entrypoint.sh` |
| invoice2data Dockerfile | `invoice2data-service/Dockerfile` |
| invoice2data Railway config | `invoice2data-service/railway.json` |
| Docker Compose (dev) | `docker/docker-compose.dev.yml` |
| Docker Compose (prod) | `docker/docker-compose-prod.yml` |

---

## 14 Subscription tier enforcement (audit 2026-02-10)

### Tiers
| Tier | Price/mo | Invoices/mo | Users | Key features |
|------|----------|-------------|-------|--------------|
| Free | €0 | 5 | 1 | Basic invoicing only |
| Starter | €12 | 50 | 1 | Recurring invoices, full expenses/estimates |
| Standard | €29 | 200 | 3 | E-Faktura, QES signing, PSD2 bank connections |
| Business | €59 | 1,000 | 5 | Multi-currency, API access, payroll (50 employees) |
| Max | €149 | Unlimited | Unlimited | Everything + 100 AI queries/month |

Config: `config/subscriptions.php` (409 lines)

### Three-layer enforcement
1. **Middleware** (`app/Http/Middleware/`):
   - `CheckInvoiceLimit` → `POST /invoices` only → 402
   - `CheckUserLimit` → `POST /users` only → 402
   - `CheckSubscriptionTier` → `tier:standard`, `tier:business`, `tier:payroll` → 402
2. **Service layer** (`app/Services/`):
   - `InvoiceCountService` — monthly invoice count with 5-min cache
   - `UserCountService` — user count per company
   - `UsageLimitService` — expenses, estimates, custom fields, recurring invoices, AI queries
3. **Controller level** — `canUse()` checks in Expenses, Estimates, CustomFields, RecurringInvoices, AiInsights controllers

### Usage tracking
- DB table: `usage_tracking` (company_id, feature, count, period)
- Monthly features reset on 1st of month
- Cache keys: `subscription:invoice_count:{company_id}:{YYYY-MM}`, `subscription:user_count:{company_id}`

### Bypasses
- Super admins bypass all checks
- Partners bypass `CheckSubscriptionTier` (they have their own billing)
- Accountants with `partner_tier='plus'` + `kyc_status='verified'` get full access
- Trial: 14-day Standard tier for new signups

### Known gaps (not critical)
- Invoice edits (`PUT /invoices/{id}`) have no limit check — only creation does
- Usage limits for expenses/estimates/etc are in controllers, not middleware — relies on developer discipline
- No per-endpoint API rate limiting for Business tier "API access" feature

### Key files
| Purpose | Path |
|---------|------|
| Tier config + limits | `config/subscriptions.php` |
| Invoice limit middleware | `app/Http/Middleware/CheckInvoiceLimit.php` |
| User limit middleware | `app/Http/Middleware/CheckUserLimit.php` |
| Tier gate middleware | `app/Http/Middleware/CheckSubscriptionTier.php` |
| Invoice count service | `app/Services/InvoiceCountService.php` |
| User count service | `app/Services/UserCountService.php` |
| Usage limit service | `app/Services/UsageLimitService.php` |
| Subscription service | `app/Services/SubscriptionService.php` |
| Company subscription model | `app/Models/CompanySubscription.php` |
| Usage tracking migration | `database/migrations/2025_12_14_120001_create_usage_tracking_table.php` |

---

## 15 Backup system (Cloudflare R2)

### Architecture
* **Storage**: Cloudflare R2 bucket `facturino-backups` via `r2backup` filesystem disk
* **Dump tool**: PHP PDO-based (`docker-mysqldump-php.sh` → `/usr/local/bin/mysqldump` in container)
  — Alpine only has MariaDB client which can't auth to MySQL 8.4's `caching_sha2_password`
* **Env vars**: `R2_BACKUP_ENDPOINT`, `R2_BACKUP_KEY`, `R2_BACKUP_SECRET`, `R2_BACKUP_REGION`, `R2_BACKUP_BUCKET`
  — separate from `S3_COMPAT_*` vars (those are for media on `facturino-media` bucket)

### Schedule (`routes/console.php`)
| Command | Frequency | Purpose |
|---------|-----------|---------|
| `backup:run --only-db` | Every 6 hours | DB dump → R2 + local |
| `backup:clean` | Daily at 01:00 | Prune old backups per retention policy |
| `backup:monitor` | Every 6 hours | Alert if backup older than 1 day |

### Testing backup commands via Railway SSH
**IMPORTANT**: Railway SSH does not inherit the container's env vars.
You must source them from PID 1 and manually set the DB connection:
```sh
railway ssh --project=68289408-e121-49d3-b4ee-c24529e57641 \
  --environment=04d5e439-c700-4502-987f-812546b2c3c1 \
  --service=8c6e11da-cc83-4f27-bcec-99325a203892 \
  -- 'export $(cat /proc/1/environ | tr "\0" "\n" | grep -v "^$" | grep "=" | grep -v "+" | grep -v "(" | grep -v " " | head -100) 2>/dev/null; export DB_CONNECTION=mysql DB_HOST=$MYSQLHOST DB_PORT=${MYSQLPORT:-3306} DB_DATABASE=$MYSQLDATABASE DB_USERNAME=${MYSQLUSER:-root} DB_PASSWORD=$MYSQLPASSWORD; php artisan backup:run --only-db 2>&1'
```
Replace `backup:run --only-db` with `backup:clean` or `backup:monitor` to test the other commands.

### Key files
| Purpose | Path |
|---------|------|
| Backup config | `config/backup.php` |
| R2 backup disk | `config/filesystems.php` → `r2backup` |
| mysqldump dump options | `config/database.php` → `mysql.dump` |
| PHP mysqldump replacement | `docker-mysqldump-php.sh` |
| Cron schedule | `routes/console.php` (lines 46–64) |

### Retention policy (`config/backup.php`)
| Period | Kept |
|--------|------|
| Last 7 days | All backups |
| 8–30 days | 1 per day |
| 1–3 months | 1 per week |
| 3–12 months | 1 per month |
| 1–3 years | 1 per year |
| Hard cap | 5 GB total |

---

# End rules