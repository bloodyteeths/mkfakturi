# Load Testing Infrastructure for Facturino

**Version:** 1.0
**Last Updated:** 2025-11-17
**Maintainer:** QA Team

---

## Quick Start

### 1. Install Artillery

```bash
npm install
```

### 2. Set Environment Variables

```bash
export LOAD_TEST_URL="https://staging.facturino.mk"
export ADMIN_PASSWORD="your_secure_password"
```

### 3. Run Quick Smoke Test

```bash
npm run load:smoke
```

### 4. Run Full Basic Load Test

```bash
npm run load:basic
```

---

## Available Tests

| Test | Duration | Purpose | Command |
|------|----------|---------|---------|
| **Smoke Test** | 2 min | Quick endpoint validation | `npm run load:smoke` |
| **Basic Load** | 10 min | Normal traffic simulation | `npm run load:basic` |
| **Stress Test** | 15 min | Find breaking points | `npm run load:stress` |
| **Spike Test** | 8 min | Sudden traffic spikes | `npm run load:spike` |
| **Critical Endpoints** | 12 min | Business-critical features | `npm run load:critical` |

---

## Test Files

```
tests/load/
├── README.md                              # This file
├── load-test-smoke.yml                    # Quick smoke test (2 min)
├── load-test-basic.yml                    # Normal traffic (10 min)
├── load-test-stress.yml                   # Stress testing (15 min)
├── load-test-spike.yml                    # Spike testing (8 min)
├── load-test-critical-endpoints.yml       # Critical endpoints (12 min)
├── test-data.csv                          # Test user credentials
└── reports/                               # Generated reports directory
    ├── load-report.json                   # Raw JSON results
    └── load-report.html                   # HTML report
```

---

## Test Scenarios Overview

### Smoke Test (`load-test-smoke.yml`)

**Duration:** 2 minutes
**Load:** 1 user/second
**Purpose:** Quick validation before deployment

**Coverage:**
- Health check endpoint
- Authentication flow
- Dashboard access
- Invoice listing
- Customer listing
- Items listing
- IFRS balance sheet
- E-invoice listing
- Payment listing

**Use When:**
- Before each deployment
- After configuration changes
- Quick sanity check

---

### Basic Load Test (`load-test-basic.yml`)

**Duration:** 10 minutes
**Load:** 2-10 users/second

**Phases:**
1. Warm-up: 2 users/sec (2 min)
2. Normal: 5 users/sec (5 min)
3. Peak: 10 users/sec (3 min)
4. Cool-down: 3 users/sec (2 min)

**Scenarios:**
- Authentication Flow (30%)
- Dashboard Usage (25%)
- Invoice Management (20%)
- Customer/Item Browsing (15%)
- Search Operations (10%)

**Use When:**
- Pre-deployment testing
- Weekly regression tests
- After performance optimizations

---

### Stress Test (`load-test-stress.yml`)

**Duration:** 15 minutes
**Load:** 10-150 users/second

**Phases:**
1. Ramp 10→30 users/sec (3 min)
2. Increase 30→60 users/sec (3 min)
3. Push 60→100 users/sec (3 min)
4. Maximum 100→150 users/sec (2 min)
5. Cool-down 150→10 users/sec (3 min)

**Scenarios:**
- Heavy Database Reads (40%)
- Report Generation (30%)
- Complex Searches (20%)
- Authentication Stress (10%)

**Use When:**
- Capacity planning
- Before major releases
- After infrastructure changes

---

### Spike Test (`load-test-spike.yml`)

**Duration:** 8 minutes
**Load:** Sudden 5→200 users/second

**Phases:**
1. Baseline: 5 users/sec (1 min)
2. Spike 1: 100 users/sec (2 min)
3. Recovery: 5 users/sec (1 min)
4. Spike 2: 200 users/sec (2 min)
5. Final recovery: 5 users/sec (1 min)
6. Sustained: 50 users/sec (1 min)

**Scenarios:**
- Quick Dashboard Check (50%)
- View Invoices (30%)
- Health Checks (20%)

**Use When:**
- Before marketing campaigns
- Before month-end periods
- Testing auto-scaling

---

### Critical Endpoints Test (`load-test-critical-endpoints.yml`)

**Duration:** 12 minutes
**Load:** Steady 10 users/second

**Scenarios:**
- E-Faktura Submission (25%)
- IFRS Accounting Reports (20%)
- Payment Processing (20%)
- Import/Export Operations (15%)
- Certificate Management (10%)
- Support Tickets (10%)

**Use When:**
- After critical feature changes
- Pre-release validation
- Compliance testing

---

## Performance Baselines

### Expected Response Times

| Percentile | Target | Critical |
|------------|--------|----------|
| p50 | < 500ms | < 1000ms |
| p95 | < 2000ms | < 3000ms |
| p99 | < 5000ms | < 8000ms |

### Expected Error Rates

| Metric | Target | Critical |
|--------|--------|----------|
| HTTP 5xx | < 0.5% | < 2% |
| HTTP 4xx | < 1% | < 5% |
| Timeouts | < 0.1% | < 1% |

### Endpoint-Specific Baselines

| Endpoint | p95 | p99 |
|----------|-----|-----|
| `/api/v1/auth/login` | < 800ms | < 1500ms |
| `/api/v1/dashboard` | < 1500ms | < 3000ms |
| `/api/v1/invoices` | < 1200ms | < 2500ms |
| `/api/v1/accounting/reports/*` | < 3000ms | < 6000ms |
| `/api/v1/search` | < 1000ms | < 2000ms |

---

## Running Tests

### Basic Commands

```bash
# Smoke test (2 minutes)
npm run load:smoke

# Basic load test (10 minutes)
npm run load:basic

# Stress test (15 minutes)
npm run load:stress

# Spike test (8 minutes)
npm run load:spike

# Critical endpoints test (12 minutes)
npm run load:critical

# Run all tests (45+ minutes)
npm run load:all
```

### Custom Target

```bash
# Test against custom URL
LOAD_TEST_URL=https://my-server.com npm run load:basic

# Test with custom credentials
ADMIN_PASSWORD=my_password npm run load:basic
```

### Generate HTML Report

```bash
npm run load:report
open tests/load/reports/load-report.html
```

### Debug Mode

```bash
DEBUG=http npm run load:basic
```

---

## Interpreting Results

### Sample Output

```
Summary report @ 14:23:15(+0000)
  Scenarios launched:  3000
  Scenarios completed: 2995
  Requests completed:  14975
  Mean response/sec: 24.96
  Response time (msec):
    min: 45
    max: 8234
    median: 567
    p95: 1234
    p99: 3456
  Codes:
    200: 14800
    500: 25
```

### What to Look For

✅ **Healthy System:**
- Scenarios completed ≈ Scenarios launched
- Error rate < 1%
- p95 < 2000ms
- Response times stable

⚠️ **Warning Signs:**
- Error rate 1-3%
- p95 2000-3000ms
- Some failed scenarios
- Response times increasing

🔴 **Critical Issues:**
- Error rate > 3%
- p95 > 3000ms
- Many HTTP 500 errors
- System crashes

---

## Pre-Deployment Checklist

Before deploying to production, ensure:

- [ ] Smoke test passes (0% errors)
- [ ] Basic load test passes (< 1% errors)
- [ ] Stress test identifies capacity limits
- [ ] Spike test validates recovery
- [ ] Critical endpoints validated
- [ ] Performance baselines met
- [ ] No critical bugs found
- [ ] Monitoring configured
- [ ] Team sign-off obtained

See full checklist: [`docs/testing/pre-deployment-load-test-checklist.md`](../../docs/testing/pre-deployment-load-test-checklist.md)

---

## Common Issues

### Issue: High Error Rate

**Symptoms:** Many HTTP 500 or 503 errors

**Solutions:**
1. Check `storage/logs/laravel.log`
2. Increase database connection pool
3. Increase PHP memory limit
4. Scale to larger instance

### Issue: Slow Response Times

**Symptoms:** p95 > 5000ms

**Solutions:**
1. Review slow query log
2. Add database indexes
3. Implement caching
4. Optimize N+1 queries

### Issue: Scenarios Not Completing

**Symptoms:** Launched > Completed

**Solutions:**
1. Increase Artillery timeout
2. Check for application crashes
3. Verify network connectivity

### Issue: Authentication Failures

**Symptoms:** Many HTTP 401 errors

**Solutions:**
1. Verify `ADMIN_PASSWORD` variable
2. Check session configuration
3. Disable rate limiting in staging

---

## Monitoring During Tests

### What to Monitor

1. **Application Metrics**
   - Response times
   - Error rates
   - Request rate

2. **Infrastructure**
   - CPU usage (< 80%)
   - Memory usage (stable)
   - Disk I/O
   - Network bandwidth

3. **Database**
   - Connection pool
   - Query time
   - Locks/deadlocks
   - Cache hit rate

4. **Queue**
   - Queue depth
   - Failed jobs
   - Worker utilization

### Monitoring Commands

```bash
# Monitor server resources
htop

# Watch Laravel logs
tail -f storage/logs/laravel.log

# Check database connections
mysql -e "SHOW PROCESSLIST;"

# Monitor queue
php artisan queue:monitor
```

---

## Test Data

### Test Users (`test-data.csv`)

```csv
email,password
admin@facturino.mk,password123
user1@facturino.mk,password123
user2@facturino.mk,password123
user3@facturino.mk,password123
accountant@facturino.mk,password123
```

### Required Test Data in Staging

- ≥ 100 invoices
- ≥ 50 customers
- ≥ 30 items
- ≥ 20 payments
- Active company with IFRS enabled

---

## Documentation

### Full Documentation

- **Load Testing Guide:** [`docs/testing/load-testing.md`](../../docs/testing/load-testing.md)
- **Pre-Deployment Checklist:** [`docs/testing/pre-deployment-load-test-checklist.md`](../../docs/testing/pre-deployment-load-test-checklist.md)

### External Resources

- **Artillery Docs:** https://www.artillery.io/docs
- **Laravel Performance:** https://laravel.com/docs/performance

---

## Support

### Getting Help

- **Documentation:** `docs/testing/load-testing.md`
- **Slack:** #performance-testing
- **Email:** devops@facturino.mk

### Reporting Issues

Create GitHub issue with:
1. Test type that failed
2. Error messages
3. Artillery output
4. Server logs
5. Environment details

---

## Maintenance

### Regular Tasks

**Weekly:**
- [ ] Run smoke test
- [ ] Review baseline metrics

**Monthly:**
- [ ] Run full test suite
- [ ] Update baselines
- [ ] Review performance trends

**Quarterly:**
- [ ] Review test scenarios
- [ ] Update test data
- [ ] Validate monitoring

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2025-11-17 | Initial release with 5 test configurations |

---

**Next Steps:**

1. Install Artillery: `npm install`
2. Configure environment variables
3. Run smoke test: `npm run load:smoke`
4. Review full documentation: `docs/testing/load-testing.md`
5. Complete pre-deployment checklist before production deploy

# CLAUDE-CHECKPOINT
