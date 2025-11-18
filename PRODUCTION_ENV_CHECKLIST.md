# Production Environment Variables Checklist
**Version**: v2.0.0 (AC-08 → AC-18 + FIX PATCH #5)
**Platform**: Railway (app.facturino.mk)
**Last Updated**: 2025-11-18

---

## 📋 Required Environment Variables

### ✅ Core Laravel Settings (REQUIRED)

| Variable | Value | Status | Notes |
|----------|-------|--------|-------|
| `APP_NAME` | "Facturino" | ✅ | Application name |
| `APP_ENV` | production | ✅ | **MUST** be "production" |
| `APP_KEY` | base64:... | ✅ | Generate with `php artisan key:generate` |
| `APP_DEBUG` | false | ✅ | **MUST** be false in production |
| `APP_URL` | https://app.facturino.mk | ✅ | Production domain |
| `APP_TIMEZONE` | Europe/Skopje | ✅ | Macedonia timezone |
| `APP_LOCALE` | mk | ✅ | Default locale |

**Verification**:
```bash
railway vars get APP_ENV
# Expected: production

railway vars get APP_DEBUG
# Expected: false
```

---

### ✅ Database Configuration (REQUIRED)

| Variable | Value | Status | Notes |
|----------|-------|--------|-------|
| `DB_CONNECTION` | mysql | ✅ | Auto-configured from MYSQL_URL |
| `DB_HOST` | mysql-y5el.railway.internal | ✅ | Railway auto-injects |
| `DB_PORT` | 3306 | ✅ | Railway auto-injects |
| `DB_DATABASE` | railway | ✅ | Railway auto-injects |
| `DB_USERNAME` | root | ✅ | Railway auto-injects |
| `DB_PASSWORD` | *** | ✅ | Railway auto-injects |
| `MYSQL_URL` | mysql://... | ✅ | Railway primary variable |

**Verification**:
```bash
railway run php artisan tinker --execute="
  echo 'Connection: ' . DB::connection()->getDatabaseName();
"
# Expected: "Connection: railway"
```

---

### ✅ Cache & Session (REQUIRED for AC-08→AC-18)

| Variable | Value | Status | Notes |
|----------|-------|--------|-------|
| `CACHE_DRIVER` | redis | ⚠️ | **RECOMMENDED** for partner sessions |
| `SESSION_DRIVER` | redis | ⚠️ | **RECOMMENDED** for multi-device support |
| `REDIS_HOST` | redis.railway.internal | ⚠️ | Add Redis service in Railway |
| `REDIS_PASSWORD` | *** | ⚠️ | Railway auto-injects |
| `REDIS_PORT` | 6379 | ⚠️ | Railway auto-injects |

**Alternative** (if Redis not available):
```env
CACHE_DRIVER=database
SESSION_DRIVER=database
```

**Verification**:
```bash
railway run php artisan tinker --execute="
  Cache::put('test_key', 'test_value', 60);
  echo Cache::get('test_key');
"
# Expected: "test_value"
```

---

### ✅ Queue Configuration (REQUIRED for Commission Processing)

| Variable | Value | Status | Notes |
|----------|-------|--------|-------|
| `QUEUE_CONNECTION` | redis | ⚠️ | **CRITICAL** for commission jobs |
| `QUEUE_REDIS_CONNECTION` | queue | ⚠️ | Separate connection for queue |
| `QUEUE_RETRY_AFTER` | 90 | ✅ | Job timeout (seconds) |
| `QUEUE_FAILED_DRIVER` | database | ✅ | Store failed jobs in DB |

**Why Critical**:
- Commission calculations run as background jobs
- Partner invitation emails sent via queue
- Network graph generation queued for large networks

**Verification**:
```bash
railway run php artisan queue:work --once
# Expected: Processes 1 job and exits

railway run php artisan queue:failed
# Expected: Empty list (no failed jobs)
```

---

### ⭐ Commission System Configuration (NEW - REQUIRED)

| Variable | Default | Status | Notes |
|----------|---------|--------|-------|
| `AFFILIATE_DIRECT_RATE` | 0.22 | ❗ **REQUIRED** | 22% first year direct commission |
| `AFFILIATE_DIRECT_RATE_YEAR2` | 0.20 | ❗ **REQUIRED** | 20% after first year |
| `AFFILIATE_UPLINE_RATE` | 0.05 | ❗ **REQUIRED** | 5% upline commission (FIX PATCH #5) |
| `AFFILIATE_SALES_REP_RATE` | 0.05 | ❗ **REQUIRED** | 5% sales rep commission |
| `AFFILIATE_PAYOUT_THRESHOLD` | 50.00 | ✅ | Minimum payout amount (MKD) |
| `AFFILIATE_PAYOUT_SCHEDULE` | monthly | ✅ | Payout frequency |

**Setting These Variables**:
```bash
railway vars set AFFILIATE_DIRECT_RATE=0.22
railway vars set AFFILIATE_DIRECT_RATE_YEAR2=0.20
railway vars set AFFILIATE_UPLINE_RATE=0.05
railway vars set AFFILIATE_SALES_REP_RATE=0.05
```

**Verification**:
```bash
railway vars get AFFILIATE_DIRECT_RATE
# Expected: 0.22

railway run php artisan tinker --execute="
  echo 'Direct rate: ' . config('affiliate.direct_rate') . PHP_EOL;
  echo 'Upline rate: ' . config('affiliate.upline_rate') . PHP_EOL;
"
# Expected:
# Direct rate: 0.22
# Upline rate: 0.05
```

**⚠️ WARNING**: If these variables are not set, commission calculations will fail or use incorrect rates!

---

### ⭐ Mail Configuration (REQUIRED for Invitations)

| Variable | Value | Status | Notes |
|----------|-------|--------|-------|
| `MAIL_MAILER` | smtp | ❗ **REQUIRED** | Email driver |
| `MAIL_HOST` | smtp.example.com | ❗ **REQUIRED** | SMTP server |
| `MAIL_PORT` | 587 | ❗ **REQUIRED** | SMTP port (TLS) |
| `MAIL_USERNAME` | noreply@facturino.mk | ❗ **REQUIRED** | SMTP username |
| `MAIL_PASSWORD` | *** | ❗ **REQUIRED** | SMTP password |
| `MAIL_ENCRYPTION` | tls | ❗ **REQUIRED** | Encryption type |
| `MAIL_FROM_ADDRESS` | noreply@facturino.mk | ❗ **REQUIRED** | From email |
| `MAIL_FROM_NAME` | "Facturino" | ❗ **REQUIRED** | From name |

**Why Critical**:
- Partner→partner invitations send email (AC-15)
- Company→partner invitations send email (AC-11)
- Invitation emails contain referral tokens

**Verification**:
```bash
railway run php artisan tinker --execute="
  Mail::raw('Test email', function(\$message) {
    \$message->to('test@example.com')->subject('Test');
  });
  echo 'Email sent successfully';
"
# Expected: "Email sent successfully" (check SMTP logs)
```

**Alternative** (for testing):
```env
MAIL_MAILER=log
# Emails will be logged instead of sent
```

---

### ✅ Authentication (Sanctum)

| Variable | Value | Status | Notes |
|----------|-------|--------|-------|
| `SANCTUM_STATEFUL_DOMAINS` | app.facturino.mk | ✅ | Production domain |
| `SESSION_DOMAIN` | .facturino.mk | ✅ | Cookie domain |

**Verification**:
```bash
railway vars get SANCTUM_STATEFUL_DOMAINS
# Expected: app.facturino.mk
```

---

### ✅ Logging Configuration

| Variable | Value | Status | Notes |
|----------|-------|--------|-------|
| `LOG_CHANNEL` | stack | ✅ | Use stack for multiple channels |
| `LOG_LEVEL` | warning | ✅ | Production log level |
| `LOG_DEPRECATIONS_CHANNEL` | null | ✅ | Ignore deprecation warnings |
| `LOG_SLACK_WEBHOOK_URL` | (optional) | ⚠️ | Slack alerts for errors |

**Recommended Logging Setup**:
```env
LOG_CHANNEL=stack
LOG_LEVEL=warning
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/YOUR/WEBHOOK/URL
```

---

## 🔒 Security & Encryption

### ✅ Required Security Variables

| Variable | Value | Status | Notes |
|----------|-------|--------|-------|
| `BCRYPT_ROUNDS` | 10 | ✅ | Password hashing rounds |
| `TRUSTED_PROXIES` | * | ✅ | Railway proxy trust |
| `FORCE_HTTPS` | true | ✅ | Redirect HTTP to HTTPS |

**Verification**:
```bash
railway vars get FORCE_HTTPS
# Expected: true

curl -I http://app.facturino.mk
# Expected: HTTP 301 redirect to https://
```

---

## 📊 Optional But Recommended

### Performance Optimization

| Variable | Default | Status | Notes |
|----------|---------|--------|-------|
| `DB_CONNECTION_TIMEOUT` | 60 | ✅ | Connection timeout (seconds) |
| `DB_QUERY_TIMEOUT` | 30 | ✅ | Query timeout (seconds) |
| `MEMORY_LIMIT` | 512M | ✅ | PHP memory limit |
| `MAX_EXECUTION_TIME` | 300 | ✅ | Script timeout (seconds) |

---

### Monitoring & Debugging

| Variable | Default | Status | Notes |
|----------|---------|--------|-------|
| `TELESCOPE_ENABLED` | false | ✅ | **Keep false in production** |
| `QUERY_DEBUGGING` | false | ✅ | **Keep false in production** |
| `SENTRY_DSN` | (optional) | ⚠️ | Error tracking |
| `SENTRY_ENVIRONMENT` | production | ⚠️ | Sentry environment name |

**Recommended for AC-08→AC-18**:
```env
SENTRY_DSN=https://your-sentry-dsn@sentry.io/project
SENTRY_ENVIRONMENT=production
SENTRY_TRACES_SAMPLE_RATE=0.1
```

---

## 🚨 Critical Validation Script

Run this script BEFORE deploying to production:

```bash
#!/bin/bash
# Production environment validation script

echo "🔍 Validating production environment variables..."

# Required variables
REQUIRED_VARS=(
    "APP_ENV"
    "APP_KEY"
    "APP_DEBUG"
    "APP_URL"
    "MYSQL_URL"
    "AFFILIATE_DIRECT_RATE"
    "AFFILIATE_UPLINE_RATE"
    "MAIL_MAILER"
    "MAIL_HOST"
    "MAIL_FROM_ADDRESS"
)

MISSING_VARS=()

for var in "${REQUIRED_VARS[@]}"; do
    value=$(railway vars get "$var" 2>&1)
    if [ -z "$value" ] || [[ "$value" == *"not found"* ]]; then
        MISSING_VARS+=("$var")
    fi
done

if [ ${#MISSING_VARS[@]} -eq 0 ]; then
    echo "✅ All required variables are set"
else
    echo "❌ Missing required variables:"
    for var in "${MISSING_VARS[@]}"; do
        echo "  - $var"
    done
    exit 1
fi

# Validate APP_ENV
APP_ENV=$(railway vars get APP_ENV)
if [ "$APP_ENV" != "production" ]; then
    echo "❌ APP_ENV must be 'production', got: $APP_ENV"
    exit 1
fi

# Validate APP_DEBUG
APP_DEBUG=$(railway vars get APP_DEBUG)
if [ "$APP_DEBUG" != "false" ]; then
    echo "⚠️  WARNING: APP_DEBUG should be 'false' in production, got: $APP_DEBUG"
fi

# Validate commission rates
DIRECT_RATE=$(railway vars get AFFILIATE_DIRECT_RATE)
if (( $(echo "$DIRECT_RATE < 0.10" | bc -l) )) || (( $(echo "$DIRECT_RATE > 0.50" | bc -l) )); then
    echo "⚠️  WARNING: AFFILIATE_DIRECT_RATE seems unusual: $DIRECT_RATE (expected 0.10-0.50)"
fi

echo "✅ Environment validation complete"
```

**Usage**:
```bash
chmod +x validate_production_env.sh
./validate_production_env.sh
```

---

## 📋 Environment Setup Checklist

### Before Deployment

- [ ] All **REQUIRED** variables set in Railway
- [ ] Commission rate variables configured (`AFFILIATE_*`)
- [ ] Mail configuration tested (send test email)
- [ ] Redis service added to Railway (or alternative cache configured)
- [ ] Queue worker running (`railway run php artisan queue:work`)
- [ ] Environment validation script passed
- [ ] `APP_DEBUG=false` confirmed
- [ ] `APP_ENV=production` confirmed

### After Deployment

- [ ] Verify variables loaded: `railway run php artisan config:show`
- [ ] Test commission calculation with rates
- [ ] Test invitation email sending
- [ ] Monitor logs for configuration errors
- [ ] Verify cache is working (no excessive DB queries)

---

## 🔄 Environment Migration Guide

### From .env.staging to Production

```bash
# 1. Export staging variables
railway vars --environment staging > staging_vars.txt

# 2. Review and modify for production
# - Change APP_ENV to "production"
# - Change APP_DEBUG to "false"
# - Update APP_URL to production domain
# - Update mail credentials to production SMTP
# - Update commission rates if different

# 3. Import to production (manually)
railway vars set APP_ENV=production --environment production
railway vars set APP_DEBUG=false --environment production
# ... repeat for all variables
```

---

## 🆘 Troubleshooting

### Issue: Commission calculations returning 0

**Check**:
```bash
railway vars get AFFILIATE_DIRECT_RATE
railway vars get AFFILIATE_UPLINE_RATE
```

**Fix**:
```bash
railway vars set AFFILIATE_DIRECT_RATE=0.22
railway vars set AFFILIATE_UPLINE_RATE=0.05
railway run php artisan config:clear
```

---

### Issue: Invitation emails not sending

**Check**:
```bash
railway vars get MAIL_MAILER
railway vars get MAIL_HOST
railway vars get MAIL_FROM_ADDRESS
```

**Test**:
```bash
railway run php artisan tinker --execute="
  try {
    Mail::raw('Test', function(\$m) { \$m->to('test@example.com')->subject('Test'); });
    echo 'SUCCESS';
  } catch (Exception \$e) {
    echo 'ERROR: ' . \$e->getMessage();
  }
"
```

---

### Issue: Queue jobs not processing

**Check**:
```bash
railway vars get QUEUE_CONNECTION
railway run php artisan queue:work --once
```

**Fix**:
```bash
# Ensure queue worker is running
railway run php artisan queue:restart
railway run php artisan queue:work --daemon
```

---

## 📞 Support

**Environment Variable Issues**: Check Railway dashboard → Variables tab
**Configuration Errors**: Run `railway run php artisan config:show`
**Missing Variables**: Use validation script above

**Emergency**: If deployment fails due to missing variables, set them quickly:
```bash
railway vars set VARIABLE_NAME=value
railway run php artisan config:clear
```

---

**Document Version**: v2.0.0
**Last Reviewed**: 2025-11-18
**Next Review**: After first production deployment

// CLAUDE-CHECKPOINT
