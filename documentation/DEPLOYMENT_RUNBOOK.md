# Facturino Deployment Runbook

**Version:** 1.0
**Last Updated:** November 14, 2025
**Target Platform:** Railway.app (Production)

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Environment Setup](#environment-setup)
3. [Database Migration](#database-migration)
4. [Deployment Steps](#deployment-steps)
5. [Post-Deployment Verification](#post-deployment-verification)
6. [Rollback Procedure](#rollback-procedure)
7. [Monitoring & Health Checks](#monitoring--health-checks)
8. [Common Issues](#common-issues)

---

## Prerequisites

### Required Access
- [ ] Railway project admin access
- [ ] GitHub repository access (for code deployment)
- [ ] PostgreSQL database credentials
- [ ] Redis instance access
- [ ] Production environment variables

### Tools Required
- [ ] Railway CLI (`npm install -g @railway/cli`)
- [ ] Git
- [ ] PostgreSQL client (`psql`)
- [ ] PHP 8.4+ (for local testing)
- [ ] Composer
- [ ] Node.js 18+

### Pre-Deployment Checklist
- [ ] All tests passing (`php artisan test`)
- [ ] Code reviewed and approved
- [ ] Database backup completed
- [ ] Rollback plan ready
- [ ] Stakeholders notified
- [ ] Maintenance window scheduled (if downtime expected)

---

## Environment Setup

### 1. Railway Environment Variables

**Critical Variables:**

```bash
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.facturino.mk
APP_KEY=base64:XXXXXXXXXXXXXXXXXXXXXXXXXXXX

# Database (automatically set by Railway PostgreSQL service)
DB_CONNECTION=pgsql
DB_HOST=${PGHOST}
DB_PORT=${PGPORT}
DB_DATABASE=${PGDATABASE}
DB_USERNAME=${PGUSER}
DB_PASSWORD=${PGPASSWORD}

# Redis (automatically set by Railway Redis service)
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=${REDIS_HOST}
REDIS_PORT=${REDIS_PORT}
REDIS_PASSWORD=${REDIS_PASSWORD}

# Session Security (SEC-01-03)
SESSION_LIFETIME=120
SESSION_SECURE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# Payment Gateways
PADDLE_VENDOR_ID=XXXXX
PADDLE_API_KEY=XXXXX
PADDLE_ENVIRONMENT=production
CPAY_MERCHANT_ID=XXXXX
CPAY_SECRET_KEY=XXXXX

# Feature Flags
FEATURE_ACCOUNTING_BACKBONE=true
FEATURE_MIGRATION_WIZARD=true
FEATURE_PSD2_BANKING=true
FEATURE_PARTNER_PORTAL=true
FEATURE_PARTNER_MOCKED_DATA=false  # IMPORTANT: false in production!
FEATURE_ADVANCED_PAYMENTS=true
FEATURE_MCP_AI_TOOLS=false  # Enable after validation
FEATURE_MONITORING=true

# Monitoring
PROMETHEUS_ENABLED=true
TELESCOPE_ENABLED=false  # Only enable for debugging
```

**Set via Railway CLI:**

```bash
railway login
railway link [project-id]

# Set environment variables
railway variables set APP_ENV=production
railway variables set APP_DEBUG=false
railway variables set FEATURE_PARTNER_MOCKED_DATA=false
# ... (set all variables)

# Verify
railway variables
```

### 2. Domain Configuration

**DNS Settings:**

```
Type: A
Name: app
Value: [Railway IP or CNAME target]

Type: CNAME
Name: www
Value: app.facturino.mk
```

**SSL Certificate:**
- Railway automatically provisions SSL certificates via Let's Encrypt
- Verify HTTPS: https://app.facturino.mk

---

## Database Migration

### 1. Backup Current Database

```bash
# Connect to Railway PostgreSQL
railway run psql

# Or use pg_dump locally
pg_dump -h [PGHOST] -U [PGUSER] -d [PGDATABASE] > backup_$(date +%Y%m%d_%H%M%S).sql
```

**Upload to S3 or Railway Volume for safekeeping:**

```bash
aws s3 cp backup_*.sql s3://facturino-backups/$(date +%Y%m%d)/
```

### 2. Test Migrations Locally

```bash
# Clone production database to local
pg_restore -d facturino_local backup_*.sql

# Run migrations
php artisan migrate --pretend

# Check for errors
php artisan migrate:status
```

### 3. Run Migrations in Production

**During Maintenance Window:**

```bash
# Connect to Railway
railway link [project-id]

# Enable maintenance mode (optional, to prevent user access)
railway run php artisan down --message="Scheduled maintenance" --retry=60

# Run migrations
railway run php artisan migrate --force

# Verify
railway run php artisan migrate:status

# Disable maintenance mode
railway run php artisan up
```

**Zero-Downtime Migration (if applicable):**

- Use database feature flags to toggle new features
- Deploy code first, then run migrations
- Ensure backward compatibility

---

## Deployment Steps

### Method 1: Git Push (Recommended)

Railway auto-deploys from GitHub on push to `main` branch.

```bash
# Ensure you're on main branch
git checkout main
git pull origin main

# Merge your feature branch
git merge feature/your-branch

# Tag the release
git tag -a v1.0.5 -m "Release v1.0.5: Added security headers, legal docs"

# Push to GitHub
git push origin main --tags
```

**Railway will automatically:**
1. Detect changes
2. Build Docker image (via `nixpacks.toml`)
3. Run migrations (if configured)
4. Deploy to production
5. Health check and rollback if failed

**Monitor Deployment:**

```bash
railway logs --follow
```

### Method 2: Manual Deployment via Railway CLI

```bash
railway link [project-id]
railway up
railway logs --follow
```

### Post-Build Commands

Configured in `railway.json` or `nixpacks.toml`:

```bash
# Cache optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Queue worker restart (if using supervisor)
php artisan queue:restart
```

---

## Post-Deployment Verification

### 1. Health Checks

**Application Health:**

```bash
curl https://app.facturino.mk/up
# Expected: HTTP 200 OK
```

**Database Connectivity:**

```bash
railway run php artisan db:show
# Should show PostgreSQL connection info
```

**Redis Connectivity:**

```bash
railway run php artisan tinker
>>> Cache::put('deployment_test', now());
>>> Cache::get('deployment_test');
# Should return current timestamp
```

### 2. Feature Testing

| Feature | Test | Expected Result |
|---------|------|----------------|
| Login | Visit `/admin/login` | Login page loads |
| Create Invoice | Create test invoice | Invoice saved & PDF generated |
| E-Faktura | Sign invoice with QES | XML generated, signature valid |
| Banking | Trigger bank sync | Transactions fetched |
| Payment | Test Paddle checkout | Payment flow works |
| Partner | Access partner dashboard | Commission data loads |

### 3. Performance Checks

```bash
# Average response time (should be <200ms)
curl -w "@curl-format.txt" -o /dev/null -s https://app.facturino.mk/admin/dashboard

# Queue processing
railway run php artisan queue:work --once
# Should process job without errors
```

### 4. Monitoring Dashboard

**Access Prometheus Metrics:**

```bash
curl https://app.facturino.mk/metrics
# Should return Prometheus-formatted metrics (admin auth required)
```

**Check Grafana (if configured):**
- Response time: <200ms avg
- Error rate: <1%
- Queue depth: <100 jobs

---

## Rollback Procedure

### Scenario 1: Bad Deployment (Application Error)

```bash
# Find previous deployment in Railway dashboard
# Click "Redeploy" on last known good deployment

# Or via CLI
railway status
railway rollback [deployment-id]
```

### Scenario 2: Database Migration Failure

```bash
# Restore from backup
railway run psql < backup_YYYYMMDD_HHMMSS.sql

# Rollback migrations
railway run php artisan migrate:rollback --step=1

# Verify
railway run php artisan migrate:status
```

### Scenario 3: Critical Bug in Production

1. **Immediate:** Enable maintenance mode
   ```bash
   railway run php artisan down
   ```

2. **Revert:** Rollback to last stable version (see Scenario 1)

3. **Fix:** Debug locally, create hotfix branch

4. **Deploy:** Fast-track hotfix through testing

5. **Resume:** Disable maintenance mode
   ```bash
   railway run php artisan up
   ```

---

## Monitoring & Health Checks

### Automated Monitoring

**UptimeRobot** (or similar):
- URL: https://app.facturino.mk/up
- Interval: 5 minutes
- Alert if down >2 minutes

**Prometheus Alerts:**

```yaml
# Alert: High error rate
- alert: HighErrorRate
  expr: rate(http_requests_total{status=~"5.."}[5m]) > 0.05
  annotations:
    summary: "Error rate above 5%"

# Alert: Slow response time
- alert: SlowResponseTime
  expr: http_request_duration_seconds{quantile="0.95"} > 1
  annotations:
    summary: "95th percentile response time > 1s"

# Alert: Queue backlog
- alert: QueueBacklog
  expr: queue_jobs_pending > 1000
  annotations:
    summary: "Queue has >1000 pending jobs"
```

**Send alerts to:**
- Email: ops@facturino.mk
- Slack: #facturino-alerts

### Manual Health Checks

**Daily:**
- [ ] Check error logs: `railway logs --tail 100`
- [ ] Verify backups ran: `railway run php artisan backup:list`
- [ ] Check queue status: `railway run php artisan queue:monitor`

**Weekly:**
- [ ] Review Grafana dashboards
- [ ] Check disk usage: `railway run df -h`
- [ ] Test backup restore procedure

---

## Common Issues

### Issue: Deployment Timeout

**Symptom:** Railway deployment stuck at "Building..."

**Solution:**
```bash
# Check build logs
railway logs --build

# Common causes:
# - Composer install timeout -> increase memory in nixpacks.toml
# - NPM install hanging -> clear node_modules cache
```

### Issue: 500 Internal Server Error

**Symptom:** Application returns HTTP 500

**Debug:**
```bash
# Check logs
railway logs --follow

# Common causes:
# - Missing .env variable
# - Database connection failed
# - Permissions error (storage/ directory)

# Fix permissions
railway run chmod -R 775 storage bootstrap/cache
```

### Issue: Queue Not Processing

**Symptom:** Jobs stuck in queue

**Solution:**
```bash
# Restart queue worker
railway run php artisan queue:restart

# Check supervisor status (if using supervisor)
railway run supervisorctl status

# Manually process queue
railway run php artisan queue:work --once
```

### Issue: Database Migration Failed

**Symptom:** `php artisan migrate` errors

**Debug:**
```bash
# Check current migration status
railway run php artisan migrate:status

# View failed migration
railway run cat database/migrations/[failed_migration].php

# Rollback and retry
railway run php artisan migrate:rollback --step=1
railway run php artisan migrate --force
```

---

## Emergency Contacts

| Role | Name | Contact |
|------|------|---------|
| DevOps Lead | TBD | ops@facturino.mk |
| Database Admin | TBD | dba@facturino.mk |
| Security | TBD | security@facturino.mk |
| On-Call (24/7) | TBD | +389 XX XXX XXX |

---

## Appendix: Railway Configuration Files

### `nixpacks.toml`

```toml
[phases.setup]
nixPkgs = ["php84", "php84Packages.composer", "nodejs_18"]

[phases.install]
cmds = [
  "composer install --no-dev --optimize-autoloader",
  "npm ci --production"
]

[phases.build]
cmds = [
  "npm run build",
  "php artisan config:cache",
  "php artisan route:cache",
  "php artisan view:cache"
]

[start]
cmd = "sh railway-start.sh"
```

### `railway-start.sh`

```bash
#!/bin/bash

# Run migrations
php artisan migrate --force

# Start PHP-FPM and Nginx
php-fpm -D
nginx -g 'daemon off;'
```

---

**END OF RUNBOOK**

**Version Control:** Store this runbook in Git and update after each deployment.

**Last Deployment:** [DATE]
**Deployed By:** [NAME]
**Deployment Notes:** [NOTES]
