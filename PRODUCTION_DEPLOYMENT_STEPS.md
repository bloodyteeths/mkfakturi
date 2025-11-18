# Production Deployment Steps: AC-08 → AC-18 + FIX PATCH #5
**Version**: v2.0.0
**Target Environment**: Railway Production (app.facturino.mk)
**Estimated Duration**: 20 minutes (+ 24h monitoring)
**Downtime**: 0 minutes (zero-downtime deployment)

---

## ⚠️ Pre-Deployment Requirements

### 1. Staging Verification ✅ MUST BE COMPLETE

- [ ] Migration blocker resolved (`2025_11_18_100006` deleted or fixed)
- [ ] All 9 UI smoke tests passed
- [ ] API endpoints verified with authentication
- [ ] 24-hour stability monitoring completed
- [ ] No errors in Railway logs
- [ ] User confirmation received: "Go ahead"

### 2. Team Preparation

- [ ] Development team on standby (2 hours post-deployment)
- [ ] Database admin available for rollback if needed
- [ ] Monitoring dashboard open (Railway + Laravel logs)
- [ ] Communication channel ready (Slack/Discord for status updates)

### 3. Backup Preparation

- [ ] Database backup completed and verified
- [ ] Previous deployment commit tagged (`v1.x.x`)
- [ ] Rollback script tested in staging

---

## 📋 Deployment Checklist

### Phase 1: Pre-Deployment Verification (10 min)

#### Step 1.1: Verify Current Production State
```bash
# Check current production commit
git log -1 --oneline

# Check Railway environment
railway status

# Test production health endpoint
curl https://app.facturino.mk/health
# Expected: HTTP 200
```

**Success Criteria**:
- ✅ Production is stable
- ✅ Health endpoint returns 200
- ✅ No active incidents

---

#### Step 1.2: Create Database Backup
```bash
# Option A: Railway automated backup
railway backup create --environment production

# Option B: Manual MySQL dump
railway run mysqldump --single-transaction \
  --routines --triggers \
  railway > backup_pre_v2.0.0_$(date +%Y%m%d_%H%M%S).sql

# Verify backup size
ls -lh backup_pre_v2.0.0_*.sql
# Expected: > 1MB (non-empty backup)
```

**Success Criteria**:
- ✅ Backup file created
- ✅ Backup file size > 1MB
- ✅ Backup stored in secure location

---

#### Step 1.3: Tag Current Production Version
```bash
# Tag current production commit as v1.x.x
git tag -a v1.9.9 -m "Pre-AC-08→AC-18 release (last stable v1)"
git push origin v1.9.9

# Verify tag
git tag -l
```

**Success Criteria**:
- ✅ Tag created and pushed
- ✅ Rollback point established

---

### Phase 2: Code Deployment (5 min)

#### Step 2.1: Final Code Review
```bash
# Review changes since last production deploy
git log v1.9.9..HEAD --oneline

# Review file changes
git diff v1.9.9..HEAD --stat

# Count migrations
ls -1 database/migrations/2025_*.php | wc -l
# Expected: 5-7 new migrations (excluding failed 2025_11_18_100006)
```

**Success Criteria**:
- ✅ All changes reviewed
- ✅ No unexpected file modifications
- ✅ Failed migration file removed

---

#### Step 2.2: Deploy to Production
```bash
# Ensure you're on main branch
git checkout main
git pull origin main

# Push to Railway (triggers auto-deploy)
git push origin main

# Monitor deployment progress
railway logs --follow
```

**Success Criteria**:
- ✅ Railway detects new commit
- ✅ Docker build succeeds
- ✅ Container starts successfully
- ✅ No build errors in logs

**Expected Log Output**:
```
=== Application Startup ===
Railway detected: Parsing MYSQL_URL...
DB configured from MYSQL_URL
Waiting for database at mysql-y5el.railway.internal:3306...
✅ Permissions set for www user
Nginx configured to listen on ports 80 and 8080
Clearing caches...
Running migrations...
✅ Installation complete, all markers in place
Laravel Framework 12.12.0
```

---

#### Step 2.3: Run Database Migrations
```bash
# Migrations run automatically in entrypoint.sh
# Verify migration status
railway run php artisan migrate:status

# Check for new migrations
railway run php artisan migrate:status | grep "2025_11_18"
```

**Expected Output**:
```
| Ran | Migration | Batch |
|-----|-----------|-------|
| Yes | 2025_11_18_100000_create_partner_referrals_table | 2 |
| Yes | 2025_11_18_100001_create_company_referrals_table | 2 |
```

**Success Criteria**:
- ✅ All AC-08→AC-18 migrations applied
- ✅ No migration errors
- ✅ `partner_referrals` table exists

---

### Phase 3: Post-Deployment Verification (5 min)

#### Step 3.1: Health Check
```bash
# Test application health
curl https://app.facturino.mk/health
# Expected: HTTP 200

# Test database connection
railway run php artisan tinker --execute="
  echo 'Database: ' . DB::connection()->getDatabaseName() . PHP_EOL;
  echo 'Connection: OK' . PHP_EOL;
"
# Expected: "Connection: OK"
```

**Success Criteria**:
- ✅ Health endpoint returns 200
- ✅ Database connection successful

---

#### Step 3.2: Verify FIX PATCH #5 Deployment
```bash
# Check CommissionService has partner_referrals logic
railway run php artisan tinker --execute="
  \$code = file_get_contents(base_path('app/Services/CommissionService.php'));
  echo strpos(\$code, 'partner_referrals') !== false ? '✅ FIX PATCH #5 DEPLOYED' : '❌ MISSING';
  echo PHP_EOL;
"
# Expected: "✅ FIX PATCH #5 DEPLOYED"
```

**Success Criteria**:
- ✅ FIX PATCH #5 code deployed
- ✅ CommissionService uses partner_referrals table

---

#### Step 3.3: Verify New Tables
```bash
# Check partner_referrals table
railway run php artisan tinker --execute="
  echo 'partner_referrals: ' . (Schema::hasTable('partner_referrals') ? 'EXISTS' : 'MISSING') . PHP_EOL;
  echo 'company_referrals: ' . (Schema::hasTable('company_referrals') ? 'EXISTS' : 'MISSING') . PHP_EOL;
  echo 'partners: ' . (Schema::hasTable('partners') ? 'EXISTS' : 'MISSING') . PHP_EOL;
  echo 'affiliate_events: ' . (Schema::hasTable('affiliate_events') ? 'EXISTS' : 'MISSING') . PHP_EOL;
"
```

**Expected Output**:
```
partner_referrals: EXISTS
company_referrals: EXISTS
partners: EXISTS
affiliate_events: EXISTS
```

**Success Criteria**:
- ✅ All 4 core tables exist
- ✅ Schema matches expected structure

---

#### Step 3.4: Test API Endpoints
```bash
# Set admin token (get from Railway environment variables)
export ADMIN_TOKEN=$(railway vars get SANCTUM_ADMIN_TOKEN)

# Test partners endpoint
curl -H "Authorization: Bearer $ADMIN_TOKEN" \
  https://app.facturino.mk/api/v1/admin/partners
# Expected: HTTP 200 with JSON array

# Test network graph endpoint
curl -H "Authorization: Bearer $ADMIN_TOKEN" \
  "https://app.facturino.mk/api/v1/admin/referral-network/graph?page=1&limit=10"
# Expected: HTTP 200 with {nodes, edges, meta}
```

**Success Criteria**:
- ✅ Partners endpoint returns 200
- ✅ Network graph endpoint returns 200
- ✅ JSON responses valid

---

#### Step 3.5: Clear Production Caches
```bash
# Clear all Laravel caches
railway run php artisan optimize:clear

# Rebuild optimized caches
railway run php artisan config:cache
railway run php artisan route:cache
railway run php artisan view:cache

# Restart queue workers
railway run php artisan queue:restart
```

**Success Criteria**:
- ✅ Caches cleared successfully
- ✅ Optimized caches rebuilt
- ✅ Queue workers restarted

---

### Phase 4: Smoke Testing (Manual, 10 min)

#### Step 4.1: Admin Dashboard Test
1. Visit `https://app.facturino.mk/admin/login`
2. Login as super admin
3. Verify dashboard loads without errors
4. Check for new "Partners" menu item

**Success Criteria**:
- ✅ Dashboard loads
- ✅ No JavaScript errors in console
- ✅ Partners menu visible

---

#### Step 4.2: Partner Management Test
1. Navigate to `/admin/partners`
2. Click "Add Partner"
3. Fill form and submit
4. Verify partner appears in list

**Success Criteria**:
- ✅ Partners list page loads
- ✅ Add partner form submits successfully
- ✅ New partner visible in list

---

#### Step 4.3: Commission Calculation Test
```bash
# Create test commission event
railway run php artisan tinker --execute="
  \$partner = App\Models\Partner::first();
  if (\$partner) {
    \$service = app(App\Services\CommissionService::class);
    echo 'Partner ID: ' . \$partner->id . PHP_EOL;
    echo 'Commission rate: ' . \$service->calculateCommissionRate(\$partner) . PHP_EOL;
  } else {
    echo 'No partners found - create one first' . PHP_EOL;
  }
"
```

**Success Criteria**:
- ✅ CommissionService instantiates
- ✅ Commission rate calculated
- ✅ No errors thrown

---

### Phase 5: Monitoring Setup (Ongoing)

#### Step 5.1: Enable Real-Time Log Monitoring
```bash
# Terminal 1: Railway logs
railway logs --follow | tee production_deploy_logs_$(date +%Y%m%d_%H%M%S).log

# Terminal 2: Laravel specific errors
railway logs --follow | grep -i "error\|exception\|fatal"

# Terminal 3: Commission-related logs
railway logs --follow | grep -i "commission\|partner\|affiliate"
```

**Monitor For**:
- ❌ PHP fatal errors
- ❌ SQL errors (especially commission calculations)
- ❌ 500 errors in nginx logs
- ⚠️ Slow query warnings
- ℹ️ Commission calculation events

---

#### Step 5.2: Set Up Alert Thresholds
```bash
# Check error rate (should be < 1%)
railway logs | grep "ERROR" | wc -l

# Check response times (should be < 500ms)
railway logs | grep "response_time" | awk '{sum+=$NF; count++} END {print sum/count}'
```

**Alert Triggers**:
- Error rate > 1% → Investigate immediately
- Response time > 1000ms → Performance issue
- Database connection failures → Critical alert

---

## 🔄 Rollback Procedure

### When to Rollback

**Immediate Rollback Triggers**:
- ❌ Migration failures (table creation errors)
- ❌ Application won't start (boot errors)
- ❌ Database corruption detected
- ❌ > 5% error rate in first 15 minutes

**Delayed Rollback Triggers**:
- ⚠️ Commission calculations incorrect (verify with test data)
- ⚠️ Performance degradation > 50%
- ⚠️ Memory leaks detected

---

### Rollback Steps (10 min)

#### Step 1: Stop New Deployments
```bash
# Revert to previous commit
git reset --hard v1.9.9

# Force push (triggers Railway rollback)
git push origin main --force
```

---

#### Step 2: Rollback Database (if needed)
```bash
# Only if migrations caused data corruption
# Restore from backup
railway run mysql railway < backup_pre_v2.0.0_YYYYMMDD_HHMMSS.sql

# Verify restoration
railway run php artisan migrate:status
```

**⚠️ WARNING**: Database rollback will lose all data created after deployment (partners, referrals, commissions)

---

#### Step 3: Verify Rollback Success
```bash
# Check production health
curl https://app.facturino.mk/health
# Expected: HTTP 200

# Verify old version deployed
railway run php artisan --version
# Expected: Previous version number

# Check no new tables exist
railway run php artisan tinker --execute="
  echo Schema::hasTable('partner_referrals') ? 'STILL EXISTS' : 'REMOVED';
"
# Expected: REMOVED (if migrations were rolled back)
```

---

#### Step 4: Notify Stakeholders
```
Subject: [INCIDENT] Production Rollback Executed - AC-08→AC-18

Rollback completed at: [TIMESTAMP]
Reason: [SPECIFIC ISSUE]
Previous version restored: v1.9.9
Impact: [DESCRIBE USER IMPACT]
Next steps: [INVESTIGATION PLAN]

Status: Production stable on v1.9.9
```

---

## 📊 Post-Deployment Monitoring (24 Hours)

### Hour 1: Critical Monitoring

**Every 5 minutes**:
- Check error logs: `railway logs | grep ERROR`
- Monitor response times
- Verify commission calculations (if any subscriptions occur)

**Metrics to Track**:
- HTTP 5xx errors: Should be 0
- Database connection pool: Should be < 80%
- Memory usage: Should be stable (not increasing)

---

### Hour 2-24: Normal Monitoring

**Every hour**:
- Review aggregated error logs
- Check commission calculation accuracy
- Monitor partner creation events
- Verify invitation emails sending

**Daily Summary Report**:
```bash
# Generate daily metrics
railway logs --since 24h | grep "commission" | wc -l  # Commission events
railway logs --since 24h | grep "ERROR" | wc -l       # Error count
railway logs --since 24h | grep "partner" | wc -l     # Partner events
```

---

## ✅ Deployment Success Criteria

### Immediate Success (First 15 minutes)
- [x] Application starts without errors
- [x] All migrations applied successfully
- [x] Health endpoint returns 200
- [x] Database connection stable
- [x] No 500 errors in logs
- [x] Admin dashboard accessible

### Short-Term Success (First 24 hours)
- [x] Error rate < 0.1%
- [x] No commission calculation errors
- [x] Partner CRUD operations working
- [x] Invitation emails sending
- [x] Network graph rendering
- [x] No performance degradation

### Long-Term Success (First Week)
- [x] No data inconsistencies
- [x] Commission calculations verified accurate
- [x] Partner→partner referrals working
- [x] Upline commissions calculated correctly (FIX PATCH #5)
- [x] No unexpected database growth
- [x] User feedback positive

---

## 🔒 Security Verification

### Post-Deployment Security Checks

```bash
# 1. Verify environment variables are set
railway vars | grep -E "AFFILIATE|COMMISSION|PADDLE"

# 2. Check API authentication
curl https://app.facturino.mk/api/v1/admin/partners
# Expected: HTTP 401 Unauthorized (without token)

# 3. Verify database encryption
railway run php artisan tinker --execute="
  \$partner = App\Models\Partner::first();
  echo 'Bank account encrypted: ' . (strpos(\$partner->bank_account, 'enc:') === 0 ? 'YES' : 'NO');
"
# Expected: YES (if encryption enabled)

# 4. Check CORS configuration
curl -H "Origin: https://malicious.com" \
  https://app.facturino.mk/api/v1/admin/partners
# Expected: CORS error or 403
```

---

## 📞 Emergency Contacts

**During Deployment Window**:
- **Lead Developer**: atilla tanrikulu
- **Database Admin**: [NAME]
- **DevOps**: Railway Support (https://railway.app/help)
- **Incident Channel**: [SLACK/DISCORD LINK]

**Escalation Path**:
1. Check logs: `railway logs | grep ERROR`
2. Attempt rollback (see Rollback Procedure)
3. Contact Lead Developer if rollback fails
4. Contact Railway Support for platform issues

---

## 📝 Deployment Log Template

```
=== DEPLOYMENT LOG: AC-08 → AC-18 + FIX PATCH #5 ===
Date: YYYY-MM-DD HH:MM:SS
Version: v2.0.0
Deployed By: [NAME]
Environment: Production (app.facturino.mk)

Pre-Deployment:
[ ] Database backup completed (YYYYMMDD_HHMMSS.sql)
[ ] Previous version tagged (v1.9.9)
[ ] Team notified

Deployment:
[ ] Code pushed to Railway
[ ] Migrations applied (X new migrations)
[ ] Health check passed
[ ] FIX PATCH #5 verified

Post-Deployment:
[ ] API endpoints tested
[ ] Caches cleared
[ ] Smoke tests passed
[ ] Monitoring enabled

Issues:
[NONE / LIST ANY ISSUES ENCOUNTERED]

Rollback:
[NOT REQUIRED / EXECUTED AT HH:MM:SS]

Status: ✅ SUCCESS / ❌ FAILED / ⏳ IN PROGRESS

Notes:
[ANY ADDITIONAL OBSERVATIONS]
```

---

**Deployment Window**: [TO BE SCHEDULED]
**Expected Completion**: [TIMESTAMP + 20 minutes]
**Next Review**: [TIMESTAMP + 24 hours]

// CLAUDE-CHECKPOINT
