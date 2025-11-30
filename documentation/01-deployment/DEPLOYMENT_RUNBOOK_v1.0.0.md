# Facturino v1.0.0 Deployment Runbook

**Version:** 1.0.0
**Date:** 2025-11-17
**Target Platform:** Railway
**Deployment Type:** Production Launch

---

## Table of Contents

1. [Pre-Deployment Checklist](#pre-deployment-checklist)
2. [Environment Preparation](#environment-preparation)
3. [Git Tag Creation](#git-tag-creation)
4. [Railway Deployment](#railway-deployment)
5. [Database Migration](#database-migration)
6. [Post-Deployment Verification](#post-deployment-verification)
7. [Rollback Procedure](#rollback-procedure)
8. [Monitoring & Alerts](#monitoring--alerts)

---

## Pre-Deployment Checklist

### Code Quality Verification

```bash
# 1. Verify main branch is clean
cd /Users/tamsar/Downloads/mkaccounting
git status

# Expected: "nothing to commit, working tree clean"
# If not clean, commit or stash changes first

# 2. Run all tests locally
php artisan test
npm run test

# Expected: All tests pass (green)

# 3. Run linters
./vendor/bin/pint --test  # PHP linter
npm run test              # ESLint

# Expected: No errors

# 4. Build frontend assets
npm run build

# Expected: Successful build with no errors

# 5. Clear and optimize caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize

# Expected: All commands succeed
```

### Security Checklist

```bash
# 1. Verify no secrets in repository
git log --all --full-history --source -- .env
# Expected: No results (empty)

# 2. Check .gitignore includes sensitive files
cat .gitignore | grep -E "\.env$|\.env\..*|credentials|secrets"
# Expected: .env and variants are listed

# 3. Verify AGPL compliance
ls -la | grep -E "LICENSE|LEGAL_NOTES"
# Expected: Both files exist

# 4. Check for hardcoded credentials
grep -r "password.*=" app/ config/ | grep -v "env(" | grep -v "config("
# Expected: No results or only config references
```

### Database Readiness

```bash
# 1. Verify all migrations are present
php artisan migrate:status

# Expected: All migrations show "Ran" status

# 2. Check for pending migrations
ls -la database/migrations/ | tail -5

# Expected: Verify 2FA migration exists (2025_11_16_233237_add_two_factor_columns_to_users_table.php)

# 3. Test migration rollback (optional, on staging)
php artisan migrate:rollback --step=1
php artisan migrate
# Expected: No errors
```

### Dependency Verification

```bash
# 1. Check Composer dependencies
composer show --installed | grep -E "laravel|spatie|paddle"

# Expected output includes:
# - laravel/framework
# - laravel/sanctum
# - laravel/cashier-paddle
# - spatie/laravel-backup
# - ekmungai/eloquent-ifrs

# 2. Check npm dependencies
npm list --depth=0 | grep -E "vue|vite|tailwind"

# Expected output includes:
# - vue@3.5
# - vite@6.0
# - tailwindcss@3.4

# 3. Verify Docker images exist (if using Docker)
docker images | grep facturino
# Expected: facturino app image exists
```

---

## Environment Preparation

### Critical Environment Variables

Create a `.env.production` file with the following **CRITICAL** variables:

```bash
# Application Settings (CRITICAL)
APP_ENV=production
APP_DEBUG=false
APP_NAME="Facturino"
APP_URL=https://your-production-domain.com
APP_KEY=base64:YOUR_ACTUAL_APP_KEY_HERE

# Database (CRITICAL)
DB_CONNECTION=pgsql
DB_HOST=your-postgres-host.railway.app
DB_PORT=5432
DB_DATABASE=railway
DB_USERNAME=postgres
DB_PASSWORD=YOUR_SECURE_PASSWORD_HERE

# Session Configuration (CRITICAL)
SESSION_DRIVER=database
SESSION_LIFETIME=1440
SESSION_ENCRYPT=false

# Queue Configuration (CRITICAL)
QUEUE_CONNECTION=database
# Alternative: QUEUE_CONNECTION=redis (if Redis is available)

# Cache Configuration (RECOMMENDED)
CACHE_STORE=database
# Alternative: CACHE_STORE=redis (if Redis is available)

# Security
SANCTUM_STATEFUL_DOMAINS=your-production-domain.com
TRUSTED_PROXIES="*"

# Mail Configuration (REQUIRED for notifications)
MAIL_DRIVER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@facturino.mk
MAIL_PASSWORD=YOUR_SMTP_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_NAME="Facturino"
MAIL_FROM_ADDRESS=noreply@facturino.mk
```

### Optional Performance Variables

```bash
# Redis (RECOMMENDED - 10-50x performance boost)
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_CLIENT=predis
REDIS_HOST=your-redis-host.railway.internal
REDIS_PORT=6379
REDIS_PASSWORD=YOUR_REDIS_PASSWORD
REDIS_PREFIX=facturino_
REDIS_DB=0
REDIS_CACHE_DB=1
REDIS_SESSION_DB=2
REDIS_QUEUE_DB=3
```

### Optional Feature Flags

```bash
# Feature Flags (Optional)
FEATURE_PARTNER_PORTAL=true
FEATURE_MIGRATION_WIZARD=true
FEATURE_MCP_AI_TOOLS=false         # Disable initially
FEATURE_PARTNER_MOCKED_DATA=false  # Real data in production
FEATURE_ACCOUNTING_BACKBONE=false  # Enable after testing
FEATURE_PSD2_BANKING=false         # Enable after PSD2 setup
FEATURE_ADVANCED_PAYMENTS=true
FEATURE_MONITORING=true

# AI Configuration (if FEATURE_MCP_AI_TOOLS=true)
AI_PROVIDER=claude
CLAUDE_API_KEY=sk-ant-YOUR_API_KEY_HERE
```

### Optional Backup Configuration

```bash
# AWS S3 Backups (RECOMMENDED)
AWS_ACCESS_KEY_ID=AKIA_YOUR_KEY_HERE
AWS_SECRET_ACCESS_KEY=YOUR_SECRET_KEY_HERE
AWS_DEFAULT_REGION=eu-central-1
AWS_BACKUP_BUCKET=facturino-backups
AWS_USE_PATH_STYLE_ENDPOINT=false
BACKUP_NOTIFICATION_EMAIL=admin@facturino.mk
```

### Railway Environment Variable Setup

```bash
# Set environment variables in Railway (do NOT execute commands, manual setup):

# Via Railway CLI:
railway variables set APP_ENV=production
railway variables set APP_DEBUG=false
railway variables set SESSION_DRIVER=database
railway variables set QUEUE_CONNECTION=database

# Or via Railway Dashboard:
# 1. Go to your project → Variables tab
# 2. Paste all environment variables
# 3. Click "Deploy" to apply changes
```

---

## Git Tag Creation

### DO NOT EXECUTE - Commands Prepared for Approval

```bash
# Step 1: Ensure you're on main branch
git checkout main

# Step 2: Pull latest changes
git pull origin main

# Step 3: Verify commit hash
git log -1 --oneline
# Note the commit hash (e.g., a5178ee9)

# Step 4: Create annotated tag
git tag -a v1.0.0 -m "Facturino v1.0.0 - Production Release

Major Features:
- Universal Migration Wizard with intelligent field mapping
- Complete Bills module with receipt scanning (OCR)
- Partner/Accountant Console for multi-client management
- Two-Factor Authentication (2FA) with TOTP
- Payment integration (Paddle, CPAY)
- XML export with UBL 2.1 compliance
- S3 backup configuration
- Redis performance optimization

Production Ready: 95%
Release Date: 2025-11-17
License: AGPL-3.0

Built on InvoiceShelf with extensions for Macedonian businesses.

🤖 Generated with Claude Code
Co-Authored-By: Claude <noreply@anthropic.com>"

# Step 5: Verify tag was created
git tag -l -n9 v1.0.0

# Step 6: Push tag to origin (DO NOT EXECUTE YET)
# git push origin v1.0.0

# Step 7: Create GitHub release (DO NOT EXECUTE YET)
# gh release create v1.0.0 \
#   --title "Facturino v1.0.0 - Inaugural Production Release" \
#   --notes-file RELEASE_NOTES_v1.0.0.md \
#   --draft
```

---

## Railway Deployment

### Method 1: Automatic Deployment (Recommended)

```bash
# Railway will automatically deploy when you push the v1.0.0 tag

# Step 1: Push tag to trigger deployment
git push origin v1.0.0

# Step 2: Monitor deployment
railway logs --follow

# Expected: Build starts automatically
# Watch for: "Build successful"
# Watch for: "Deployment live"
```

### Method 2: Manual Deployment via Railway CLI

```bash
# Step 1: Login to Railway
railway login

# Step 2: Link to production project
railway link

# Step 3: Deploy current code
railway up

# Step 4: Monitor logs
railway logs --follow
```

### Method 3: Railway Dashboard Deployment

**Manual Steps (No Commands):**

1. Go to Railway Dashboard: https://railway.app
2. Select your Facturino project
3. Go to "Deployments" tab
4. Click "Deploy" → "Deploy Latest Commit"
5. Wait for build to complete (typically 3-5 minutes)
6. Verify deployment status shows "Active"

### Build Verification

```bash
# Monitor build progress
railway logs --follow | grep -E "Build|Deploy|Error"

# Expected output:
# "Building..."
# "Installing dependencies..."
# "Running migrations..."
# "Build successful"
# "Deployment active"

# Check deployment URL
railway domain

# Expected: Returns your production URL
```

---

## Database Migration

### Step 1: Backup Existing Database (if applicable)

```bash
# Via Railway CLI
railway run -- php artisan backup:run

# Or manual PostgreSQL backup
railway run -- pg_dump $DATABASE_URL > backup_pre_v1.0.0.sql

# Expected: Backup file created successfully
```

### Step 2: Run Migrations

```bash
# Execute migrations on Railway
railway run -- php artisan migrate --force

# Expected output:
# "Running migrations..."
# "Migration: 2025_11_16_233237_add_two_factor_columns_to_users_table"
# "Migrated: 2025_11_16_233237_add_two_factor_columns_to_users_table"

# IMPORTANT: The --force flag is required in production
```

### Step 3: Verify Migrations

```bash
# Check migration status
railway run -- php artisan migrate:status

# Expected: All migrations show "Ran" status

# Verify 2FA columns exist
railway run -- php artisan tinker
>>> \DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'users' AND column_name LIKE 'two_factor%'");
>>> exit

# Expected: Returns two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at
```

---

## Post-Deployment Verification

### Application Health Checks

```bash
# 1. Verify application is accessible
curl -I https://your-production-domain.com

# Expected: HTTP/2 200 OK

# 2. Check health endpoint (if available)
curl https://your-production-domain.com/api/health

# Expected: {"status":"ok","timestamp":"..."}

# 3. Test authentication endpoint
curl https://your-production-domain.com/api/v1/auth/ping

# Expected: {"message":"pong"}
```

### Functional Verification

**Manual Steps (Browser Testing):**

1. **Authentication Flow**
   - Navigate to: https://your-production-domain.com/admin/login
   - Expected: Login page loads with logo
   - Test: Login with admin credentials
   - Expected: Redirects to dashboard
   - Test: Logout
   - Expected: Returns to login page

2. **Session Persistence**
   - Login to admin panel
   - Navigate to multiple pages (invoices, customers, settings)
   - Expected: No unexpected logouts
   - Close browser, reopen, navigate to site
   - Expected: Still logged in (if session hasn't expired)

3. **Two-Factor Authentication**
   - Navigate to: Settings → Security → Two-Factor Authentication
   - Expected: 2FA setup page loads
   - Test: Enable 2FA with Google Authenticator
   - Expected: QR code displays, recovery codes generated
   - Test: Logout and login with 2FA code
   - Expected: 2FA prompt appears, correct code allows login

4. **Core Functionality**
   - Test: Create new invoice
   - Expected: Invoice created successfully
   - Test: Generate PDF
   - Expected: PDF downloads correctly
   - Test: Send invoice email
   - Expected: Email sent (check queue if async)
   - Test: Create new customer
   - Expected: Customer saved successfully
   - Test: Upload receipt (Bills module)
   - Expected: Image uploaded, OCR processes (if enabled)

5. **Queue Worker Status**
   ```bash
   # Check if queue worker is running
   railway run -- php artisan queue:work --once

   # Expected: Processes one job successfully

   # For continuous queue worker (production)
   # This should be running as a separate Railway service
   railway run -- php artisan queue:work --sleep=3 --tries=3
   ```

### Performance Checks

```bash
# 1. Response time test
time curl -s https://your-production-domain.com/admin/dashboard > /dev/null

# Expected: < 1 second (with Redis), < 3 seconds (without Redis)

# 2. Check cache is working
railway run -- php artisan tinker
>>> Cache::put('test_key', 'test_value', 60);
>>> Cache::get('test_key');
>>> exit

# Expected: Returns 'test_value'

# 3. Check queue is working
railway run -- php artisan queue:work --once

# Expected: Processes job successfully
```

### Database Connection Check

```bash
# Test database connection
railway run -- php artisan tinker
>>> \DB::connection()->getPdo();
>>> \DB::table('users')->count();
>>> exit

# Expected: Returns PDO object, returns user count
```

### Backup Verification (if S3 configured)

```bash
# Run backup manually
railway run -- php artisan backup:run

# Expected: Backup uploaded to S3 successfully

# List backups
railway run -- php artisan backup:list

# Expected: Shows recent backup with timestamp
```

---

## Cache Clearing & Optimization

### Post-Deployment Optimization

```bash
# 1. Clear all caches
railway run -- php artisan config:clear
railway run -- php artisan cache:clear
railway run -- php artisan route:clear
railway run -- php artisan view:clear

# 2. Optimize for production
railway run -- php artisan config:cache
railway run -- php artisan route:cache
railway run -- php artisan view:cache
railway run -- php artisan optimize

# Expected: All commands succeed with "cached successfully" messages

# 3. Restart queue workers (if running)
railway run -- php artisan queue:restart

# Expected: "Broadcasting queue restart signal."
```

---

## Rollback Procedure

### If Critical Issues Are Discovered

#### Option 1: Rollback to Previous Deployment (Railway)

**Via Railway Dashboard:**
1. Go to Railway Dashboard → Deployments
2. Find the previous successful deployment
3. Click "..." menu → "Redeploy"
4. Wait for redeployment to complete (2-3 minutes)

**Via Railway CLI:**
```bash
# List recent deployments
railway deployments

# Redeploy a specific deployment
railway redeploy <deployment-id>

# Monitor rollback
railway logs --follow
```

#### Option 2: Rollback Git Tag

```bash
# Step 1: Revert to previous commit
git revert v1.0.0 --no-commit

# Step 2: Commit the revert
git commit -m "Rollback v1.0.0 due to critical issue: [DESCRIPTION]"

# Step 3: Push to trigger new deployment
git push origin main

# Step 4: Tag the rollback version
git tag -a v1.0.0-rollback -m "Rollback of v1.0.0"
git push origin v1.0.0-rollback
```

#### Option 3: Database Rollback (if needed)

```bash
# Only if migrations caused issues

# Step 1: Restore database from backup
railway run -- php artisan backup:restore --latest

# Or manual restore:
# railway run -- psql $DATABASE_URL < backup_pre_v1.0.0.sql

# Step 2: Rollback specific migration
railway run -- php artisan migrate:rollback --step=1

# Step 3: Verify database state
railway run -- php artisan migrate:status
```

### Rollback Decision Criteria

**Trigger rollback if:**
- Application is completely inaccessible (500 errors)
- Authentication is completely broken
- Data loss or corruption is occurring
- Critical security vulnerability is discovered
- Database migrations failed and cannot be fixed forward

**Do NOT rollback for:**
- Minor UI issues
- Non-critical feature bugs
- Performance degradation (investigate first)
- Individual user reports (verify first)

### Post-Rollback Actions

```bash
# 1. Investigate root cause
railway logs --tail=500 > rollback_investigation.log

# 2. Document the issue
echo "Rollback occurred on $(date)" >> ROLLBACK_LOG.md
echo "Reason: [DESCRIBE ISSUE]" >> ROLLBACK_LOG.md
echo "Actions taken: [DESCRIBE ACTIONS]" >> ROLLBACK_LOG.md

# 3. Create hotfix branch
git checkout -b hotfix/v1.0.1-critical-fix

# 4. Fix the issue
# ... make necessary changes ...

# 5. Test thoroughly
php artisan test
npm run test

# 6. Deploy hotfix
git tag -a v1.0.1 -m "Hotfix for v1.0.0 critical issue"
git push origin v1.0.1
```

---

## Monitoring & Alerts

### First 1 Hour - Critical Monitoring

**What to watch:**
- Railway deployment status
- Application response time (< 3 seconds)
- Error rate (< 1% of requests)
- Database connection pool
- Queue worker status
- Memory usage (< 80%)
- CPU usage (< 70%)

**How to monitor:**
```bash
# Continuous log monitoring
railway logs --follow | grep -E "ERROR|CRITICAL|Exception"

# Check metrics every 5 minutes
watch -n 300 'railway status'

# Monitor queue jobs
watch -n 60 'railway run -- php artisan queue:work --once'
```

**Alert thresholds:**
- Response time > 5 seconds → Investigate
- Error rate > 5% → Consider rollback
- Memory usage > 90% → Scale up
- Queue backlog > 100 jobs → Investigate

### First 24 Hours - Active Monitoring

**What to watch:**
- User login patterns (unusual activity?)
- Email delivery rate
- Payment processing success rate
- Backup job completion
- Session expiration issues
- 2FA adoption rate

**Daily checks:**
```bash
# Morning check (9 AM)
railway logs --tail=100 | grep -E "ERROR|CRITICAL"
railway run -- php artisan backup:list

# Midday check (1 PM)
railway status
railway run -- php artisan queue:work --once

# Evening check (6 PM)
railway logs --tail=100 | grep -E "ERROR|CRITICAL"
railway run -- php artisan horizon:status  # if using Horizon

# Night check (10 PM)
railway status
```

### First 48 Hours - Stability Confirmation

**What to watch:**
- Trend analysis (are errors increasing or decreasing?)
- User feedback (support tickets, emails)
- Performance degradation over time
- Database growth rate
- Backup success rate

**Metrics to track:**
```bash
# Generate metrics report
railway run -- php artisan tinker
>>> \DB::table('invoices')->where('created_at', '>', now()->subHours(48))->count();
>>> \DB::table('users')->where('last_login_at', '>', now()->subHours(48))->count();
>>> \DB::table('failed_jobs')->where('failed_at', '>', now()->subHours(48))->count();
>>> exit
```

### UptimeRobot Setup (Manual)

**Configuration:**
1. Go to: https://uptimerobot.com
2. Add New Monitor:
   - Monitor Type: HTTP(s)
   - Friendly Name: Facturino Production
   - URL: https://your-production-domain.com
   - Monitoring Interval: 5 minutes
3. Set up Alerts:
   - Email: admin@facturino.mk
   - Alert Threshold: 2 consecutive failures
4. Enable Status Page (optional)

### Grafana Cloud Setup (Manual)

**Configuration:**
1. Go to: https://grafana.com
2. Create account and start free tier
3. Add Prometheus data source:
   - URL: https://your-production-domain.com/metrics
   - Auth: Basic (admin credentials)
4. Import dashboard:
   - Dashboard ID: 12230 (Laravel Prometheus)
5. Set up alerts:
   - Error rate > 5%
   - Response time > 5s
   - Memory > 90%

---

## Post-Deployment Communication

### Internal Team Notification

**Subject:** ✅ Facturino v1.0.0 Deployed to Production

**Body:**
```
Team,

Facturino v1.0.0 has been successfully deployed to production.

📍 Production URL: https://your-production-domain.com
🏷️ Git Tag: v1.0.0
⏰ Deployment Time: [TIMESTAMP]
🚀 Deployment Status: Active

✅ Verified:
- Application is accessible
- Authentication working
- Database migrations successful
- Queue worker running
- Email notifications functional

📊 Monitoring:
- Logs: railway logs --follow
- Metrics: [Grafana Dashboard URL]
- Uptime: [UptimeRobot Status Page]

⚠️ Known Issues:
- [List any minor issues]

🔍 Action Items:
- [ ] Monitor logs for first 24 hours
- [ ] Test 2FA flow with real users
- [ ] Verify email delivery
- [ ] Check backup completion tomorrow morning

Please report any issues immediately to: tech-team@facturino.mk

Thank you!
```

### User Announcement (if applicable)

**Subject:** 🎉 Facturino v1.0.0 is Now Live!

**Body:**
```
Dear Facturino Users,

We're excited to announce that Facturino v1.0.0 is now live in production!

🆕 New Features:
- Enhanced security with Two-Factor Authentication
- Improved bills management with receipt scanning
- Partner portal for accounting firms
- Performance improvements across the platform

📖 What's Changed:
- Faster page load times
- More reliable session management
- Enhanced email notifications
- Better mobile experience

🔐 Security Enhancement:
We strongly recommend enabling Two-Factor Authentication:
1. Go to Settings → Security
2. Click "Enable Two-Factor Authentication"
3. Scan QR code with Google Authenticator
4. Save your recovery codes

📚 Documentation:
Full release notes: [LINK TO RELEASE NOTES]
User guide: [LINK TO USER GUIDE]

💬 Support:
If you experience any issues, please contact:
support@facturino.mk

Thank you for using Facturino!

The Facturino Team
```

---

## Emergency Contacts

**Technical Team:**
- DevOps Lead: [Contact Info]
- Backend Lead: [Contact Info]
- Frontend Lead: [Contact Info]

**External Services:**
- Railway Support: https://railway.app/help
- AWS Support: [AWS Support Portal]
- Grafana Support: https://grafana.com/support

**Escalation Path:**
1. DevOps Lead (first 30 minutes)
2. Technical Director (30-60 minutes)
3. CTO (60+ minutes or critical data loss)

---

## Checklist Summary

### Pre-Deployment ✅
- [ ] All tests passing
- [ ] Code reviewed and approved
- [ ] Environment variables configured
- [ ] Database backup created
- [ ] Git tag prepared (not pushed)
- [ ] Team notified of deployment window

### Deployment ✅
- [ ] Git tag v1.0.0 pushed
- [ ] Railway deployment triggered
- [ ] Build completed successfully
- [ ] Migrations executed
- [ ] Caches cleared and optimized
- [ ] Queue worker started

### Post-Deployment ✅
- [ ] Application accessible
- [ ] Authentication tested
- [ ] Core functionality verified
- [ ] Performance benchmarks met
- [ ] Monitoring configured
- [ ] Backup verified
- [ ] Team notified of success
- [ ] Users notified (if applicable)

### First 24 Hours ✅
- [ ] No critical errors in logs
- [ ] Response times acceptable
- [ ] Email delivery confirmed
- [ ] 2FA working correctly
- [ ] Queue processing normally
- [ ] Backups completing successfully

### Sign-Off ✅
- [ ] DevOps Lead approval
- [ ] Technical Director approval
- [ ] QA Team approval
- [ ] Product Owner approval

---

**Deployment Runbook Version:** 1.0.0
**Last Updated:** 2025-11-17
**Maintained By:** DevOps Team
**Review Frequency:** Before each major release

🤖 **Generated with Claude Code**
**Co-Authored-By: Claude <noreply@anthropic.com>**
