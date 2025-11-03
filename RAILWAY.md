# Fakturino v1 - Railway Deployment Guide

**Last Updated:** 2025-11-03
**Purpose:** Multi-service Railway deployment configuration

---

## 🚂 SERVICES ARCHITECTURE

```
Railway Project: fakturino-production
├── api         → Laravel web (PHP-FPM + Nginx)
├── worker      → Queue processor (queues: default, migration, banking)
├── scheduler   → Laravel schedule:work (cron jobs)
├── mcp-server  → AI tools (TypeScript/Node) [Step 7]
├── postgres    → Primary database (Railway add-on)
└── redis       → Cache + queues (Railway add-on)
```

---

## 📋 SERVICE CONFIGURATIONS

### 1. API Service (Laravel Web)

**Build Command:**
```bash
npm install && npm run build && composer install --optimize-autoloader --no-dev
```

**Start Command:**
```bash
php artisan config:cache && php artisan route:cache && php artisan migrate --force && php-fpm & nginx -g 'daemon off;'
```

**Environment Variables:**
```bash
APP_ENV=production
APP_KEY=${APP_KEY}
APP_URL=${RAILWAY_PUBLIC_DOMAIN}

DATABASE_URL=${DATABASE_URL}  # Auto-set by Railway Postgres
REDIS_URL=${REDIS_URL}        # Auto-set by Railway Redis

# Feature Flags (see .env.example for full list)
FEATURE_ACCOUNTING_BACKBONE=false
FEATURE_MIGRATION_WIZARD=false
FEATURE_PSD2_BANKING=false
FEATURE_PARTNER_PORTAL=false
FEATURE_PARTNER_MOCKED_DATA=true  # SAFETY
FEATURE_ADVANCED_PAYMENTS=false
FEATURE_MCP_AI_TOOLS=false
FEATURE_MONITORING=false
```

**Health Check:**
```
Path: /health
Interval: 30s
Timeout: 10s
Success: 200 status
```

**Note:** The `/health` endpoint is always available. When `FEATURE_MONITORING=true`, additional monitoring endpoints are available:
- `/metrics` - Prometheus metrics (text format)
- `/metrics/health` - Detailed health checks with all subsystems
- `/telescope` - Telescope debugging interface (admin only)

---

### 2. Worker Service (Queue Processor)

**Start Command:**
```bash
php artisan queue:work redis --queue=default,migration,banking --sleep=3 --tries=3 --max-time=3600
```

**Environment Variables:**
```bash
# Same as API service
DATABASE_URL=${DATABASE_URL}
REDIS_URL=${REDIS_URL}
```

**No Health Check** (queue workers don't expose HTTP)

**Restart Policy:** Always restart on failure

---

### 3. Scheduler Service (Laravel Cron)

**Start Command:**
```bash
php artisan schedule:work
```

**Environment Variables:**
```bash
# Same as API service
DATABASE_URL=${DATABASE_URL}
REDIS_URL=${REDIS_URL}
```

**Scheduled Jobs:**
- Bank sync (hourly) when FEATURE_PSD2_BANKING=true
- Certificate expiry check (daily)
- Commission calculations (nightly)

**No Health Check** (scheduler doesn't expose HTTP)

---

### 4. MCP Server Service (AI Tools) [Step 7]

**Status:** ⏸️ Not yet implemented (pending Step 7)

**Build Command:**
```bash
npm ci && npm run build
```

**Start Command:**
```bash
npm start
```

**Environment Variables:**
```bash
MCP_SERVER_TOKEN=${MCP_SERVER_TOKEN}  # Random token for auth
LARAVEL_INTERNAL_URL=http://api:8080  # Internal Railway network
PORT=3100
```

**Health Check:**
```
Path: /health
Interval: 30s
Timeout: 10s
Success: 200 status
```

---

## 🔌 ADD-ONS

### PostgreSQL
```
Provider: Railway PostgreSQL
Connection: Automatic via DATABASE_URL
Version: 15
Storage: 10GB (adjustable)
```

### Redis
```
Provider: Railway Redis
Connection: Automatic via REDIS_URL
Version: 7
Memory: 256MB (adjustable)
Eviction: allkeys-lru
```

---

## 💾 VOLUMES AND STORAGE

### Option 1: Railway Volume (Certificates)
```
Service: api
Mount Path: /app/storage/certificates
Size: 1GB
Purpose: Store signer certificates (PFX, PEM)
```

### Option 2: S3-Compatible Storage
```
Use Railway's object storage or external S3
Store certificates encrypted
Configure in app/config/filesystems.php
```

---

## 🔐 SECRETS MANAGEMENT

**Never Commit:**
- Signer certificates (.pfx, .pem)
- OAuth client secrets
- Payment gateway keys
- Database credentials

**Use Railway Environment Variables:**
```bash
# Payment Gateways
PADDLE_API_KEY=
PADDLE_WEBHOOK_SECRET=
CPAY_SECRET_KEY=

# Banking
STOPANSKA_CLIENT_SECRET=
NLB_CLIENT_SECRET=

# MCP
MCP_SERVER_TOKEN=
```

---

## 🚀 DEPLOYMENT CHECKLIST

### Initial Setup
- [ ] Create Railway project
- [ ] Add PostgreSQL add-on
- [ ] Add Redis add-on
- [ ] Configure 3 services (api, worker, scheduler)
- [ ] Set environment variables (all feature flags OFF except partner_mocked_data=true)
- [ ] Deploy

### Post-Deploy
```bash
# Run migrations (one-time)
railway run php artisan migrate --force

# Clear caches
railway run php artisan config:cache
railway run php artisan route:cache
railway run php artisan view:cache
```

### Enable Features (After Staging Validation)
1. Test feature in staging with flag ON
2. Verify staging validation checklist green
3. Flip flag in production Railway environment
4. Monitor health checks and logs
5. Rollback flag if issues detected

---

## 📊 MONITORING

### Health Endpoints
```
api:       GET /metrics/health
mcp-server: GET /health
```

### Metrics (when FEATURE_MONITORING=true)
```
api:       GET /metrics (Prometheus format)
```

**Available Metrics:**
- `fakturino_signer_cert_expiry_days` - Days until XML signing certificate expires
- `fakturino_signer_cert_healthy` - Certificate health status (1=healthy, 0=expiring soon)
- `invoiceshelf_invoices_total{status}` - Total invoices by status
- `invoiceshelf_customers_total` - Total customers
- `invoiceshelf_customers_active` - Active customers (invoiced in last 90 days)
- `invoiceshelf_revenue_30_days_total` - Total revenue in last 30 days
- `invoiceshelf_database_healthy` - Database connection health
- `invoiceshelf_cache_healthy` - Cache connection health
- `invoiceshelf_queue_jobs_pending` - Number of pending queue jobs
- `invoiceshelf_queue_jobs_failed` - Number of failed queue jobs
- `invoiceshelf_bank_transactions_24h` - Bank transactions synced in last 24 hours
- `invoiceshelf_bank_match_rate_percent` - Bank transaction match rate

**Prometheus Configuration:**
Set these environment variables to customize Prometheus behavior:
```bash
PROMETHEUS_NAMESPACE=fakturino                    # Metric namespace
PROMETHEUS_STORAGE_ADAPTER=redis                  # memory|redis|apc
PROMETHEUS_REDIS_HOST=${REDIS_HOST}              # Auto-set by Railway
PROMETHEUS_REDIS_PORT=${REDIS_PORT}              # Auto-set by Railway
PROMETHEUS_REDIS_DATABASE=2                       # Separate from cache
PROMETHEUS_METRICS_ROUTE_ENABLED=false           # Let web.php handle routes
```

**Telescope Access:**
- URL: `https://your-app.railway.app/telescope`
- Access: Super admin users only when `FEATURE_MONITORING=true`
- Storage: Database tables (telescope_entries, telescope_*)

### Logs
```bash
# View logs for each service
railway logs --service api
railway logs --service worker
railway logs --service scheduler
```

---

## 🔄 ROLLBACK PROCEDURES

### Flag Rollback
1. Set feature flag to false in Railway environment
2. Redeploy service (automatic with Railway)
3. Verify health checks green

### Migration Rollback
```bash
railway run php artisan migrate:rollback --step=1
```

### Full Service Rollback
1. Railway dashboard → Deployments
2. Select previous working deployment
3. Click "Redeploy"

---

## 📝 AGENT NOTES

**Agents:** When you add/modify services or environment variables, update this file with:
- Service name and purpose
- Build/run commands
- Health check configuration
- Environment variables required
- Volume/storage needs

---

## 🚨 KNOWN ISSUES

*None yet. Agents will document issues here if encountered.*

---

**Ready for Multi-Service Deployment** 🚀

// CLAUDE-CHECKPOINT
