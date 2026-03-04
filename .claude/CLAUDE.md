# Claude Rules – Facturino v1 (2026-02-21)

## Mission
Macedonian-localised fork of InvoiceShelf: bank-feed, QES e-Invoice, Paddle billing, CASYS pay-links, partner commissions.

## Architecture
- **App**: Laravel 10 + Vue 3 + Vite at `app.facturino.mk` (`resources/scripts/`)
- **Marketing**: Next.js 14 + Tailwind at `www.facturino.mk` (`website/`)
- **OCR**: FastAPI microservice (`invoice2data-service/`, private URL `invoice2data-service.railway.internal`)
- **Submodules**: `docker/`, `facturino/` (orphaned). Do NOT edit without committing inside first.
- **Branding**: Always **Facturino** (Latin), never "MK Accounting" or "Факторино". Legal: Facturino DOOEL / ДООЕЛ.
- **Commissions**: 20% monthly / 22% annual.

## Vue Router (CRITICAL)
- `/signup` and `/partner/signup` defined in `admin-router.js` with `isPublic: true` — do NOT duplicate in `public/router/index.js`
- Auth guard whitelist: `login, forgot-password, reset-password, signup, partner-signup, privacy, terms`
- New public page: Vue in `resources/scripts/public/views/`, route in `public/router/index.js`, Laravel route in `routes/web.php`

## Marketing i18n
4 locales: `mk`, `sq`, `tr`, `en`. Dictionaries in `website/src/i18n/dictionaries.ts`. Content pages use inline `copy` objects. All pages need all 4 locales.

## Rules
- **Branch**: `ticket/<ID>-<slug>`, PR: `[<ID>] <ticket title>`
- **Dependencies**: Only install from whitelist (see `composer.json`). New lib → NX-ticket.
- **Files**: PHP → `Modules/Mk/**`, Vue → `resources/scripts/`, migrations → `database/migrations/2025_08_**`
- **No edits** in `vendor/`, core models, `docker/`, `facturino/` submodules
- **Checkpoint**: Add `// CLAUDE-CHECKPOINT` after editing each file
- **Migrations**: `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`, FKs `ON DELETE RESTRICT`, idempotent (`Schema::hasTable()`) — auto-run on Railway deploy
- **Testing**: Unit + feature + browser tests. Run full suite before done.
- **Code**: PSR-12 PHP, Vue 3 Composition API, existing Tailwind/UI components
- **AGPL**: Keep upstream headers, link fork in footer, `/LEGAL_NOTES.md`

## Deployment (Railway)
- Push to `main` → auto-deploy at `app.facturino.mk`
- Entrypoint: `docker-entrypoint.sh` (NOT `railway-start.sh`)
- Migrations + seeders run automatically on deploy
- Supervisor runs: nginx, php-fpm, scheduler, **queue worker** (2 processes: default,high,banking,einvoice,background)
- Railway SSH: `railway ssh --project=68289408-e121-49d3-b4ee-c24529e57641 --environment=04d5e439-c700-4502-987f-812546b2c3c1 --service=8c6e11da-cc83-4f27-bcec-99325a203892`
- SSH env sourcing: `export $(cat /proc/1/environ | tr "\0" "\n" | grep -v "^$" | grep "=" | grep -v "+" | grep -v "(" | grep -v " " | head -100) 2>/dev/null; export DB_CONNECTION=mysql DB_HOST=$MYSQLHOST DB_PORT=${MYSQLPORT:-3306} DB_DATABASE=$MYSQLDATABASE DB_USERNAME=${MYSQLUSER:-root} DB_PASSWORD=$MYSQLPASSWORD;`
- Railway gotchas: use `${PORT:-8000}`, private networking `*.railway.internal`, wrap startCommand in `sh -c`

## Email (Postmark)
- **CRITICAL**: Default `outbound` stream silently drops messages. Always use `broadcast` or `outreach` stream.
- Company emails from `config('mail.from.address')` = `fakturi@facturino.mk`
- Partner emails from `partners@facturino.mk`
- Set stream via: `->withSymfonyMessage(fn($m) => $m->getHeaders()->addTextHeader('X-PM-Message-Stream', 'broadcast'))`
- Or via API: `'MessageStream' => 'broadcast'`
- Welcome drip series: 10 emails (5 company + 5 partner), Day 0/2/5/10/14, cron `welcome:send-drip` hourly 08-18 Skopje
- Outreach uses `PostmarkOutreachService` with direct API calls on `outreach` stream
- Blade templates: use `<p>` tags + `<br>` for formatting (not raw Markdown)

## External APIs
- PSD2: rate-limit 15 req/min → sleep 60s in cron
- CASYS: UniqueID expires 20 min
- xmlsec: PFX chain errors → catch & log
- Paddle: webhooks need CSRF exemption

## Subscriptions
6 tiers: Free/Starter(€12)/Standard(€39)/Business(€59)/Max(€149) + Accountant Basic (internal, portfolio-only). Config: `config/subscriptions.php`.
Bank connections and auto-reconciliation require Business tier. Portfolio program: accountants free, 1:1 sliding scale, 3-month grace.
Three-layer enforcement: middleware (`CheckInvoiceLimit`, `CheckUserLimit`, `CheckSubscriptionTier`), services (`InvoiceCountService`, `UsageLimitService`), controller-level `canUse()`.
Bypasses: super admins, partners, verified accountants. Trial: 14 days Standard.

## Backup (R2)
R2 bucket `facturino-backups`. DB dump every 6h, cleanup daily 01:00, monitor every 6h.
Env: `R2_BACKUP_*` (separate from `S3_COMPAT_*` media vars).
Retention: 7d all, 30d daily, 3mo weekly, 12mo monthly, 3yr yearly, 5GB cap.
