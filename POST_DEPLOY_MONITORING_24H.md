# 24-Hour Post-Deployment Monitoring Plan
**Version**: v2.0.0 (AC-08 → AC-18 + FIX PATCH #5)
**Target**: Production (app.facturino.mk)
**Duration**: 24 hours from deployment
**Purpose**: Ensure stability and catch early issues

---

## 🎯 Monitoring Objectives

### Critical Success Metrics (Must Be Green)
- ✅ Zero fatal errors (HTTP 500, PHP fatal, database connection failures)
- ✅ Application uptime: 99.9% (< 1 minute downtime allowed)
- ✅ Commission calculations: 100% accuracy
- ✅ Database integrity: No corrupted records
- ✅ Email delivery: > 95% success rate

### Performance Targets
- Average response time: < 500ms
- P95 response time: < 1000ms
- Database query time: < 100ms average
- Memory usage: < 80% of limit
- CPU usage: < 70% average

---

## 📊 Hour-by-Hour Monitoring Schedule

### Hour 0-1: CRITICAL MONITORING (Every 5 Minutes)

**Priority**: 🔴 **HIGHEST** - Development team on standby

#### Automated Checks
```bash
# Terminal 1: Real-time logs
railway logs --follow | tee logs_hour_0-1.log

# Terminal 2: Error monitoring
watch -n 30 'railway logs | grep -i "error\|exception\|fatal" | tail -20'

# Terminal 3: Commission events
watch -n 60 'railway logs | grep -i "commission" | tail -10'
```

#### Manual Checks (Every 5 minutes)

**1. Application Health** (2 min)
```bash
curl https://app.facturino.mk/health
# Expected: HTTP 200
# Alert if: Non-200 response for 2 consecutive checks
```

**2. Database Connection** (1 min)
```bash
railway run php artisan tinker --execute="
  echo 'DB: ' . DB::connection()->getPdo() ? 'OK' : 'FAIL';
"
# Expected: "DB: OK"
# Alert if: Any failure
```

**3. Error Rate** (2 min)
```bash
# Count errors in last 5 minutes
railway logs | grep -i "ERROR" | grep "$(date +%Y-%m-%d)" | tail -50 | wc -l
# Expected: < 5 errors per 5 minutes
# Alert if: > 10 errors in 5 minutes
```

#### Key Metrics to Track

| Metric | Target | Alert Threshold | Action If Exceeded |
|--------|--------|----------------|-------------------|
| HTTP 500 errors | 0 | > 1 | Investigate immediately |
| Response time | < 500ms | > 1000ms | Check database queries |
| Memory usage | < 60% | > 80% | Check for memory leaks |
| Database connections | < 20 | > 50 | Check connection pool |
| Queue depth | < 10 jobs | > 100 jobs | Check queue worker |

#### Rollback Trigger Conditions

**Immediate rollback if**:
- ❌ > 10 HTTP 500 errors in 5 minutes
- ❌ Application unresponsive for > 2 minutes
- ❌ Database corruption detected
- ❌ Commission calculation errors > 3 instances

---

### Hour 1-4: ENHANCED MONITORING (Every 15 Minutes)

**Priority**: 🟠 **HIGH** - Development team on call

#### Automated Checks
```bash
# Aggregate error monitoring
*/15 * * * * railway logs | grep -i "error" | tail -100 > /tmp/error_log_$(date +%H%M).txt

# Performance monitoring
*/15 * * * * railway logs | grep "response_time" | awk '{sum+=$NF; count++} END {print sum/count}' > /tmp/perf_$(date +%H%M).txt
```

#### Manual Checks (Every 15 minutes)

**1. Commission Calculation Verification** (3 min)
```bash
railway run php artisan tinker --execute="
  // Check last 10 commission events
  \$events = DB::table('affiliate_events')
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get(['id', 'affiliate_partner_id', 'amount', 'upline_amount', 'created_at']);

  foreach (\$events as \$event) {
    echo 'Event ' . \$event->id . ': Direct=' . \$event->amount . ', Upline=' . \$event->upline_amount . PHP_EOL;
  }
"
```

**Expected**: Commission amounts match rates (22% direct, 5% upline)
**Alert if**: Any commission amount = 0 when upline exists

**2. Partner Referral Integrity** (2 min)
```bash
railway run php artisan tinker --execute="
  \$count = DB::table('partner_referrals')->count();
  \$accepted = DB::table('partner_referrals')->where('status', 'accepted')->count();
  echo 'Total referrals: ' . \$count . PHP_EOL;
  echo 'Accepted: ' . \$accepted . PHP_EOL;
"
```

**Expected**: Count only increases (never decreases)
**Alert if**: Count decreases (data loss detected)

**3. Queue Processing** (2 min)
```bash
railway run php artisan queue:failed
# Expected: 0 failed jobs (or < 3 failed jobs per hour)

railway run php artisan tinker --execute="
  \$count = DB::table('jobs')->count();
  echo 'Pending jobs: ' . \$count . PHP_EOL;
"
# Expected: < 50 pending jobs
```

**Alert if**: > 50 pending jobs (queue backlog)

#### Performance Baselines

Establish baselines during hour 1-4 for comparison:

```bash
# Save baseline metrics
railway run php artisan tinker --execute="
  // Average response time
  echo 'Response time baseline: ' . collect(DB::getQueryLog())->avg('time') . 'ms' . PHP_EOL;

  // Memory usage
  echo 'Memory usage: ' . memory_get_usage(true) / 1024 / 1024 . 'MB' . PHP_EOL;

  // Database connections
  echo 'DB connections: ' . DB::connection()->selectOne('SHOW STATUS LIKE \"Threads_connected\"')->Value . PHP_EOL;
" | tee baseline_metrics.txt
```

---

### Hour 4-12: STANDARD MONITORING (Every Hour)

**Priority**: 🟡 **MEDIUM** - Development team available

#### Automated Checks
```bash
# Hourly summary report
0 * * * * /path/to/hourly_summary.sh
```

**hourly_summary.sh**:
```bash
#!/bin/bash
HOUR=$(date +%H)
echo "=== Hourly Summary: Hour $HOUR ==="

# Error count
ERROR_COUNT=$(railway logs | grep -i "ERROR" | grep "$(date +%Y-%m-%d $HOUR)" | wc -l)
echo "Errors: $ERROR_COUNT"

# Commission events
COMMISSION_COUNT=$(railway logs | grep -i "commission" | grep "$(date +%Y-%m-%d $HOUR)" | wc -l)
echo "Commission events: $COMMISSION_COUNT"

# Partner creations
PARTNER_COUNT=$(railway logs | grep -i "partner.*created" | grep "$(date +%Y-%m-%d $HOUR)" | wc -l)
echo "Partners created: $PARTNER_COUNT"

# Invitation emails
EMAIL_COUNT=$(railway logs | grep -i "invitation.*email" | grep "$(date +%Y-%m-%d $HOUR)" | wc -l)
echo "Invitation emails: $EMAIL_COUNT"

echo "Status: $([ $ERROR_COUNT -lt 10 ] && echo 'HEALTHY' || echo 'NEEDS ATTENTION')"
```

#### Manual Checks (Every hour)

**1. FIX PATCH #5 Verification** (5 min)
```bash
railway run php artisan tinker --execute="
  // Check upline commission detection
  \$eventsWithUpline = DB::table('affiliate_events')
    ->whereNotNull('upline_partner_id')
    ->where('created_at', '>', now()->subHour())
    ->count();

  echo 'Events with upline (last hour): ' . \$eventsWithUpline . PHP_EOL;

  // Verify partner_referrals table is being used
  \$referrals = DB::table('partner_referrals')
    ->where('status', 'accepted')
    ->count();

  echo 'Accepted referrals: ' . \$referrals . PHP_EOL;

  if (\$referrals > 0 && \$eventsWithUpline == 0) {
    echo '⚠️  WARNING: Referrals exist but no upline commissions detected!' . PHP_EOL;
  } else {
    echo '✅ FIX PATCH #5 working correctly' . PHP_EOL;
  }
"
```

**Expected**: If accepted referrals exist AND subscriptions occur, upline commissions should be calculated
**Alert if**: Accepted referrals > 0 but upline commissions = 0 (FIX PATCH #5 failure)

**2. Data Consistency Check** (5 min)
```bash
railway run php artisan tinker --execute="
  // Check for orphaned records
  \$orphanedLinks = DB::table('partner_company_links')
    ->leftJoin('partners', 'partners.id', '=', 'partner_company_links.partner_id')
    ->whereNull('partners.id')
    ->count();

  echo 'Orphaned partner_company_links: ' . \$orphanedLinks . PHP_EOL;

  // Check for duplicate primary partners
  \$duplicatePrimary = DB::table('partner_company_links')
    ->select('company_id', DB::raw('COUNT(*) as count'))
    ->where('is_primary', true)
    ->groupBy('company_id')
    ->having('count', '>', 1)
    ->count();

  echo 'Companies with multiple primary partners: ' . \$duplicatePrimary . PHP_EOL;

  if (\$orphanedLinks > 0 || \$duplicatePrimary > 0) {
    echo '⚠️  Data integrity issues detected!' . PHP_EOL;
  } else {
    echo '✅ Data integrity OK' . PHP_EOL;
  }
"
```

**Alert if**: Orphaned links > 0 OR duplicate primary partners > 0

---

### Hour 12-24: RELAXED MONITORING (Every 4 Hours)

**Priority**: 🟢 **LOW** - Development team on call

#### Automated Checks
```bash
# 4-hour summary
0 */4 * * * /path/to/4hour_summary.sh | mail -s "Production Status" team@example.com
```

#### Manual Checks (Every 4 hours)

**1. Cumulative Metrics Review** (10 min)
```bash
# Generate 24-hour report
railway run php artisan tinker --execute="
  echo '=== 24-Hour Deployment Report ===' . PHP_EOL;

  // Total commission events
  \$totalEvents = DB::table('affiliate_events')
    ->where('created_at', '>', now()->subDay())
    ->count();
  echo 'Commission events (24h): ' . \$totalEvents . PHP_EOL;

  // Total commission amount
  \$totalAmount = DB::table('affiliate_events')
    ->where('created_at', '>', now()->subDay())
    ->sum('amount');
  echo 'Total commissions (24h): $' . number_format(\$totalAmount, 2) . PHP_EOL;

  // Upline commission events (FIX PATCH #5)
  \$uplineEvents = DB::table('affiliate_events')
    ->whereNotNull('upline_partner_id')
    ->where('created_at', '>', now()->subDay())
    ->count();
  echo 'Upline commission events (24h): ' . \$uplineEvents . PHP_EOL;

  // Partner creations
  \$newPartners = DB::table('partners')
    ->where('created_at', '>', now()->subDay())
    ->count();
  echo 'New partners (24h): ' . \$newPartners . PHP_EOL;

  // Partner referrals
  \$newReferrals = DB::table('partner_referrals')
    ->where('created_at', '>', now()->subDay())
    ->count();
  echo 'New referrals (24h): ' . \$newReferrals . PHP_EOL;

  echo PHP_EOL . '✅ 24-hour monitoring complete' . PHP_EOL;
"
```

**2. Performance Comparison** (5 min)
```bash
# Compare current metrics to baseline
railway run php artisan tinker --execute="
  echo 'Current memory: ' . memory_get_usage(true) / 1024 / 1024 . 'MB' . PHP_EOL;
  echo 'Baseline memory: [FROM baseline_metrics.txt]' . PHP_EOL;

  echo 'Current DB connections: ' . DB::connection()->selectOne('SHOW STATUS LIKE \"Threads_connected\"')->Value . PHP_EOL;
  echo 'Baseline DB connections: [FROM baseline_metrics.txt]' . PHP_EOL;
"
```

**Alert if**:
- Memory usage increased > 50% from baseline
- Database connections increased > 100% from baseline

---

## 📈 Metrics Dashboard

### Real-Time Metrics (Update Every Minute)

```bash
# Create a simple monitoring dashboard
watch -n 60 '
  clear
  echo "======================================"
  echo "  Production Monitoring Dashboard"
  echo "  $(date)"
  echo "======================================"
  echo ""
  echo "📊 Application Status:"
  curl -s https://app.facturino.mk/health > /dev/null && echo "  ✅ Application: UP" || echo "  ❌ Application: DOWN"
  echo ""
  echo "🔢 Last Hour Metrics:"
  echo "  Errors: $(railway logs | grep -i ERROR | grep "$(date +%Y-%m-%d | awk -v h=$(date +%H) '"'"'{print $0, h}'"'"')" | wc -l)"
  echo "  Commissions: $(railway logs | grep -i commission | grep "$(date +%Y-%m-%d | awk -v h=$(date +%H) '"'"'{print $0, h}'"'"')" | wc -l)"
  echo "  Partners: $(railway logs | grep -i "partner.*created" | grep "$(date +%Y-%m-%d | awk -v h=$(date +%H) '"'"'{print $0, h}'"'"')" | wc -l)"
  echo ""
  echo "⚡ Performance:"
  echo "  Memory: $(free -m | awk '"'"'NR==2{printf \"%.0f%%\", $3*100/$2}'"'"')"
  echo "  CPU: $(top -bn1 | grep '"'"'Cpu(s)'"'"' | sed '"'"'s/.*, *\([0-9.]*\)%* id.*/\1/'"'"' | awk '"'"'{print 100 - $1}'"'"')%"
  echo "======================================"
'
```

---

## 🚨 Alert Thresholds & Actions

### Critical Alerts (Immediate Action Required)

| Alert | Threshold | Action |
|-------|-----------|--------|
| Application Down | Health check fails 2x | 1. Check Railway logs<br>2. Restart container if needed<br>3. Prepare rollback |
| HTTP 500 Spike | > 10 errors/5min | 1. Check error logs<br>2. Identify failing endpoint<br>3. Consider rollback |
| Database Connection Lost | Any failure | 1. Check MYSQL_URL variable<br>2. Check Railway database service<br>3. Contact Railway support |
| Memory Exhaustion | > 95% usage | 1. Restart container<br>2. Check for memory leaks<br>3. Increase memory limit |

### Warning Alerts (Action Required Within 1 Hour)

| Alert | Threshold | Action |
|-------|-----------|--------|
| Slow Response Times | P95 > 2000ms | 1. Check slow query log<br>2. Review database indexes<br>3. Enable query caching |
| Queue Backlog | > 100 pending jobs | 1. Check queue worker status<br>2. Increase worker count<br>3. Investigate failing jobs |
| Email Delivery Failure | > 10% failure rate | 1. Check SMTP credentials<br>2. Review email logs<br>3. Test email sending |
| Commission Calculation Errors | Any error | 1. Review error details<br>2. Check commission rates<br>3. Verify FIX PATCH #5 deployed |

---

## 📝 Incident Response Procedure

### Step 1: Detection (0-5 minutes)
- Monitor detects issue
- Alert sent to development team
- Team acknowledges alert

### Step 2: Assessment (5-10 minutes)
- Review logs: `railway logs | grep ERROR | tail -100`
- Check metrics dashboard
- Determine severity (Critical, High, Medium, Low)
- Estimate user impact

### Step 3: Mitigation (10-30 minutes)
**For Critical Issues**:
- Execute rollback if necessary (see PRODUCTION_DEPLOYMENT_STEPS.md Section 8)
- Notify stakeholders

**For Non-Critical Issues**:
- Apply hotfix if available
- Monitor for escalation
- Document issue for post-mortem

### Step 4: Communication
```
Subject: [INCIDENT] Production Issue Detected - AC-08→AC-18

Severity: [CRITICAL / HIGH / MEDIUM / LOW]
Detected: [TIMESTAMP]
Issue: [DESCRIPTION]
Impact: [USER IMPACT]
Status: [INVESTIGATING / MITIGATING / RESOLVED]
ETA: [ESTIMATED RESOLUTION TIME]

Updates will be provided every 15 minutes.
```

---

## ✅ 24-Hour Completion Checklist

At the end of 24 hours, verify:

- [ ] Zero critical incidents
- [ ] < 0.1% error rate
- [ ] All commission calculations correct
- [ ] FIX PATCH #5 verified working (upline commissions calculated)
- [ ] No data integrity issues
- [ ] Performance within targets
- [ ] Email delivery > 95% success rate
- [ ] Queue processing healthy
- [ ] No memory leaks detected
- [ ] Database connections stable

**If all checks pass**: ✅ **Deployment deemed successful**

---

## 📊 Final Report Template

```
=== 24-HOUR POST-DEPLOYMENT REPORT ===
Deployment: AC-08 → AC-18 + FIX PATCH #5
Version: v2.0.0
Deployed: [TIMESTAMP]
Report Date: [TIMESTAMP + 24h]

=== METRICS SUMMARY ===
Total Requests: [COUNT]
Error Rate: [PERCENTAGE]
Average Response Time: [MS]
P95 Response Time: [MS]
Uptime: [PERCENTAGE]

=== BUSINESS METRICS ===
Commission Events: [COUNT]
Total Commissions: $[AMOUNT]
Upline Commissions (FIX PATCH #5): [COUNT]
New Partners: [COUNT]
Partner Referrals: [COUNT]
Invitation Emails: [COUNT]

=== INCIDENTS ===
Critical: [COUNT] ([DETAILS])
High: [COUNT] ([DETAILS])
Medium: [COUNT] ([DETAILS])
Low: [COUNT] ([DETAILS])

=== FIX PATCH #5 VERIFICATION ===
Status: [VERIFIED / ISSUES FOUND]
Details: [DESCRIPTION]

=== RECOMMENDATIONS ===
1. [RECOMMENDATION]
2. [RECOMMENDATION]
3. [RECOMMENDATION]

=== OVERALL STATUS ===
[✅ SUCCESS / ⚠️ SUCCESS WITH CONCERNS / ❌ FAILURE]

Next Steps: [ACTIONS REQUIRED]

Report By: [NAME]
```

---

**Monitoring Start**: [TO BE FILLED AFTER DEPLOYMENT]
**Monitoring End**: [TIMESTAMP + 24 hours]
**Responsible Team**: Development + DevOps

// CLAUDE-CHECKPOINT
