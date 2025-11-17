# Load Testing Guide for Facturino

**Last Updated:** 2025-11-17
**Version:** 1.0
**Owner:** QA Team

---

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Installation](#installation)
4. [Test Configurations](#test-configurations)
5. [Running Load Tests](#running-load-tests)
6. [Performance Baselines](#performance-baselines)
7. [Interpreting Results](#interpreting-results)
8. [Monitoring During Tests](#monitoring-during-tests)
9. [Troubleshooting](#troubleshooting)
10. [Best Practices](#best-practices)

---

## Overview

This guide covers load testing for the Facturino application using Artillery, a modern, powerful load testing toolkit. Load testing helps ensure the application can handle expected traffic and identify performance bottlenecks before they affect users.

### What is Load Testing?

Load testing simulates real-world usage patterns to:
- Verify system performance under expected load
- Identify breaking points and capacity limits
- Detect performance regressions
- Validate infrastructure scaling

### Test Types Available

1. **Basic Load Test** (`load-test-basic.yml`) - Simulates normal production traffic
2. **Stress Test** (`load-test-stress.yml`) - Finds application breaking points
3. **Spike Test** (`load-test-spike.yml`) - Tests behavior under sudden traffic spikes
4. **Critical Endpoints** (`load-test-critical-endpoints.yml`) - Focuses on business-critical features

---

## Prerequisites

### Required Software

- Node.js 18+ and npm
- Access to staging/test environment
- Admin credentials for test accounts

### Environment Setup

1. **Staging Environment**: Never run load tests against production
2. **Database**: Use production-like data volume
3. **Configuration**: Match production resource allocation

### Test Data

Ensure the following test data exists in your staging environment:

- Test user accounts (see `tests/load/test-data.csv`)
- Sample invoices (at least 100)
- Sample customers (at least 50)
- Sample items (at least 30)
- Active company with IFRS enabled

---

## Installation

### Install Artillery

```bash
cd /path/to/facturino
npm install
```

This installs Artillery (version 2.0.0) and all dependencies as specified in `package.json`.

### Verify Installation

```bash
npx artillery --version
```

Expected output: `2.0.0` or higher

---

## Test Configurations

### 1. Basic Load Test (`load-test-basic.yml`)

**Purpose:** Simulate normal production traffic patterns

**Duration:** 10 minutes

**Load Profile:**
- Warm-up: 2 users/sec for 2 minutes
- Normal: 5 users/sec for 5 minutes
- Peak: 10 users/sec for 3 minutes
- Cool-down: 3 users/sec for 2 minutes

**Scenarios:**
- Authentication Flow (30%)
- Dashboard Usage (25%)
- Invoice Management (20%)
- Customer/Item Browsing (15%)
- Search Operations (10%)

**When to Use:**
- Before each deployment
- After performance optimizations
- Weekly regression testing

---

### 2. Stress Test (`load-test-stress.yml`)

**Purpose:** Find the breaking point of the application

**Duration:** 15 minutes

**Load Profile:**
- Ramp from 10 to 30 users/sec (3 minutes)
- Increase from 30 to 60 users/sec (3 minutes)
- Push to 100 users/sec (3 minutes)
- Maximum 150 users/sec (2 minutes)
- Cool-down to 10 users/sec (3 minutes)

**Scenarios:**
- Heavy Database Reads (40%)
- Report Generation (30%)
- Complex Searches (20%)
- Authentication Stress (10%)

**When to Use:**
- Before major releases
- After infrastructure changes
- Capacity planning exercises

**Expected Behavior:**
- Response times increase linearly initially
- Some degradation at 100+ users/sec is acceptable
- System should recover gracefully during cool-down

---

### 3. Spike Test (`load-test-spike.yml`)

**Purpose:** Test behavior under sudden traffic spikes

**Duration:** 8 minutes

**Load Profile:**
- Baseline: 5 users/sec (1 minute)
- Spike 1: 100 users/sec (2 minutes)
- Recovery: 5 users/sec (1 minute)
- Spike 2: 200 users/sec (2 minutes)
- Final recovery: 5 users/sec (1 minute)
- Sustained: 50 users/sec (1 minute)

**Scenarios:**
- Quick Dashboard Check (50%)
- View Invoices (30%)
- Health Checks (20%)

**When to Use:**
- Before marketing campaigns
- Before month-end periods
- Testing auto-scaling configurations

**Expected Behavior:**
- Temporary performance degradation during spikes is acceptable
- System should handle spikes without crashing
- Recovery to normal performance within 30 seconds after spike ends

---

### 4. Critical Endpoints Test (`load-test-critical-endpoints.yml`)

**Purpose:** Validate business-critical features

**Duration:** 12 minutes

**Load Profile:**
- Steady 10 users/sec throughout

**Scenarios:**
- E-Faktura Submission (25%)
- IFRS Accounting Reports (20%)
- Payment Processing (20%)
- Import/Export Operations (15%)
- Certificate Management (10%)
- Support Tickets (10%)

**When to Use:**
- After changes to critical features
- Before regulatory compliance audits
- Pre-release smoke testing

---

## Running Load Tests

### Setting Environment Variables

Before running tests, set the target URL and credentials:

```bash
# For staging environment
export LOAD_TEST_URL="https://staging.facturino.mk"
export ADMIN_PASSWORD="your_secure_password"
```

### Basic Load Test

```bash
npm run load:basic
```

### Stress Test

```bash
npm run load:stress
```

### Spike Test

```bash
npm run load:spike
```

### Critical Endpoints Test

```bash
npx artillery run tests/load/load-test-critical-endpoints.yml
```

### Run All Tests

```bash
npm run load:all
```

**Warning:** This takes approximately 45 minutes to complete.

---

## Generating HTML Reports

Artillery can generate beautiful HTML reports:

```bash
# Run test with report output
npm run load:report

# This creates:
# - tests/load/reports/load-report.json (raw data)
# - tests/load/reports/load-report.html (HTML report)
```

Open the HTML report in your browser:

```bash
open tests/load/reports/load-report.html
```

---

## Performance Baselines

These are expected performance characteristics for Facturino running on recommended infrastructure (Hetzner CPX11 or equivalent).

### Response Time Targets

| Metric | Target | Critical Threshold |
|--------|--------|-------------------|
| **p50 (Median)** | < 500ms | < 1000ms |
| **p95** | < 2000ms | < 3000ms |
| **p99** | < 5000ms | < 8000ms |
| **Max** | < 10000ms | < 15000ms |

### Throughput Targets

| Metric | Target | Critical Threshold |
|--------|--------|-------------------|
| **Requests/sec** | 50+ | 30+ |
| **Concurrent Users** | 100+ | 50+ |

### Error Rates

| Metric | Target | Critical Threshold |
|--------|--------|-------------------|
| **HTTP 5xx** | < 0.5% | < 2% |
| **HTTP 4xx** | < 1% | < 5% |
| **Timeouts** | < 0.1% | < 1% |

### Endpoint-Specific Baselines

#### Authentication (`/api/v1/auth/login`)
- **p95:** < 800ms
- **p99:** < 1500ms
- **Success Rate:** > 99.5%

#### Dashboard (`/api/v1/dashboard`)
- **p95:** < 1500ms
- **p99:** < 3000ms
- **Success Rate:** > 99%

#### Invoice List (`/api/v1/invoices`)
- **p95:** < 1200ms
- **p99:** < 2500ms
- **Success Rate:** > 99%

#### IFRS Reports (`/api/v1/accounting/reports/*`)
- **p95:** < 3000ms
- **p99:** < 6000ms
- **Success Rate:** > 98%

#### Search (`/api/v1/search`)
- **p95:** < 1000ms
- **p99:** < 2000ms
- **Success Rate:** > 99%

---

## Interpreting Results

### Artillery Output Explained

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
  Scenario counts:
    Authentication Flow: 900 (30%)
    Dashboard Usage: 750 (25%)
    Invoice Management: 600 (20%)
  Codes:
    200: 14800
    201: 150
    500: 25
```

### Key Metrics to Watch

#### 1. Scenarios Launched vs Completed
```
Scenarios launched:  3000
Scenarios completed: 2995
```
- **Good:** Completed ≈ Launched (difference < 1%)
- **Warning:** Difference 1-5%
- **Critical:** Difference > 5% (indicates crashes or timeouts)

#### 2. Response Times
```
Response time (msec):
  median: 567
  p95: 1234
  p99: 3456
```
- **p50 (median):** Half of requests are faster than this
- **p95:** 95% of requests are faster than this
- **p99:** 99% of requests are faster than this

**What to Look For:**
- Median should be very fast (< 500ms)
- p95 should be acceptable (< 2000ms)
- Large gap between p95 and p99 indicates performance outliers

#### 3. HTTP Status Codes
```
Codes:
  200: 14800  ← Success
  500: 25     ← Server errors (investigate!)
```
- **200-299:** Success (should be 99%+)
- **400-499:** Client errors (usually test data issues)
- **500-599:** Server errors (critical issues)

#### 4. Error Rate Calculation
```
Error Rate = (Failed Requests / Total Requests) × 100
Error Rate = (25 / 14975) × 100 = 0.17%
```
- **Good:** < 1%
- **Acceptable:** 1-3%
- **Critical:** > 3%

---

## What Results Mean for Your System

### Green: System is Healthy
- Error rate < 1%
- p95 within baseline targets
- No failed scenarios
- Response times stable throughout test

**Action:** No action needed. System is performing well.

---

### Yellow: Performance Degradation
- Error rate 1-3%
- p95 slightly above baseline (2000-3000ms)
- Some failed scenarios (< 5%)
- Response times increasing over time

**Action:**
1. Check database query performance
2. Review application logs for warnings
3. Monitor resource usage (CPU, memory, database connections)
4. Consider scaling vertically or horizontally

---

### Red: Critical Issues
- Error rate > 3%
- p95 well above baseline (> 3000ms)
- Many failed scenarios (> 5%)
- High number of HTTP 500 errors

**Action:**
1. **Immediately investigate** server logs and error traces
2. Check for database deadlocks or connection pool exhaustion
3. Review recent code changes
4. Consider rolling back recent deployments
5. Scale infrastructure immediately if capacity issue

---

## Monitoring During Tests

### What to Monitor

While load tests run, monitor these system metrics:

#### 1. Application Metrics
- **Response Times:** Should increase linearly with load
- **Error Rates:** Should remain flat and low
- **Request Rate:** Should match Artillery's arrival rate

**Tool:** `/api/v1/metrics` endpoint (Prometheus format)

#### 2. Infrastructure Metrics
- **CPU Usage:** Should stay below 80%
- **Memory Usage:** Should be stable (no memory leaks)
- **Disk I/O:** Watch for bottlenecks
- **Network Bandwidth:** Ensure no saturation

**Tool:** `htop`, Railway dashboard, or monitoring platform

#### 3. Database Metrics
- **Connection Pool:** Watch for pool exhaustion
- **Query Time:** Slow queries increase under load
- **Locks/Deadlocks:** Should remain zero
- **Cache Hit Rate:** Should be high (> 90%)

**Tool:** Database monitoring (MariaDB slow query log)

#### 4. Queue Metrics
- **Queue Depth:** Should process faster than arrival
- **Failed Jobs:** Should remain zero
- **Worker Utilization:** Should be balanced

**Tool:** Laravel Horizon (if using Redis) or database jobs table

---

### Monitoring Commands

#### Watch Artillery Progress
```bash
npm run load:basic | tee tests/load/reports/load-output.log
```

#### Monitor Server Resources (SSH to staging)
```bash
htop
```

#### Watch Laravel Logs
```bash
tail -f storage/logs/laravel.log | grep -E "(ERROR|WARNING)"
```

#### Monitor Database Connections
```bash
mysql -u root -p -e "SHOW PROCESSLIST;"
```

#### Check Queue Status
```bash
php artisan queue:monitor
```

---

## Troubleshooting

### Common Issues and Solutions

#### Issue: High Error Rate (> 5%)

**Symptoms:**
```
Codes:
  500: 750
  503: 50
```

**Causes:**
- Database connection pool exhaustion
- Application crashes
- Memory limits exceeded

**Solutions:**
1. Check `storage/logs/laravel.log` for stack traces
2. Increase database connection pool: `DB_CONNECTION_POOL=20`
3. Increase PHP memory limit: `memory_limit=512M`
4. Scale to larger instance

---

#### Issue: Slow Response Times (p95 > 5000ms)

**Symptoms:**
```
Response time (msec):
  p95: 8234
  p99: 12456
```

**Causes:**
- Slow database queries
- N+1 query problems
- Unoptimized code paths
- Insufficient caching

**Solutions:**
1. Enable Laravel Debugbar in staging
2. Review slow query log
3. Add database indexes where needed
4. Implement query result caching
5. Use eager loading for relationships

---

#### Issue: Scenarios Not Completing

**Symptoms:**
```
Scenarios launched:  1000
Scenarios completed: 750
```

**Causes:**
- Request timeouts
- Application crashes mid-request
- Network issues

**Solutions:**
1. Increase Artillery timeout: `http.timeout: 60`
2. Check for application crashes in logs
3. Verify network connectivity to staging

---

#### Issue: Authentication Failures

**Symptoms:**
```
Codes:
  401: 300
```

**Causes:**
- Wrong test credentials
- Session issues
- Rate limiting

**Solutions:**
1. Verify `ADMIN_PASSWORD` environment variable
2. Check session driver configuration
3. Disable rate limiting in staging: `RATE_LIMIT_ENABLED=false`

---

#### Issue: Memory Leaks

**Symptoms:**
- Memory usage continuously increases
- Application crashes after several minutes
- Response times degrade over time

**Solutions:**
1. Monitor memory: `watch -n 1 free -m`
2. Check for circular references in code
3. Review queue jobs for memory issues
4. Restart workers periodically

---

## Best Practices

### Before Running Tests

1. **Use Dedicated Environment**
   - Never test against production
   - Use staging with production-like configuration
   - Ensure staging has similar data volume

2. **Coordinate with Team**
   - Announce test schedule
   - Ensure no one else is using staging
   - Block automated jobs during tests

3. **Prepare Test Data**
   - Verify test accounts exist
   - Ensure sufficient sample data
   - Clear old test data to avoid noise

4. **Baseline Monitoring**
   - Record normal performance metrics
   - Document infrastructure configuration
   - Know your current capacity

### During Tests

1. **Monitor Continuously**
   - Watch Artillery output
   - Monitor server resources
   - Check application logs

2. **Take Notes**
   - Record observations
   - Note any errors or warnings
   - Screenshot interesting metrics

3. **Don't Interrupt**
   - Let tests complete fully
   - Avoid making changes mid-test
   - Record any external factors (deployments, etc.)

### After Tests

1. **Analyze Results**
   - Compare against baselines
   - Identify performance regressions
   - Document findings

2. **Generate Reports**
   - Create HTML reports
   - Share with team
   - Archive for future comparison

3. **Take Action**
   - Fix critical issues immediately
   - Create tickets for optimizations
   - Update baselines if needed

4. **Clean Up**
   - Remove test artifacts
   - Reset staging environment
   - Archive test results

---

## Continuous Integration

### Automated Load Testing

Add load testing to your CI/CD pipeline:

```yaml
# .github/workflows/load-test.yml
name: Load Test

on:
  schedule:
    - cron: '0 2 * * 0'  # Weekly on Sunday at 2 AM
  workflow_dispatch:      # Manual trigger

jobs:
  load-test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: 18

      - name: Install dependencies
        run: npm ci

      - name: Run basic load test
        env:
          LOAD_TEST_URL: ${{ secrets.STAGING_URL }}
          ADMIN_PASSWORD: ${{ secrets.STAGING_ADMIN_PASSWORD }}
        run: npm run load:basic

      - name: Upload results
        uses: actions/upload-artifact@v3
        with:
          name: load-test-results
          path: tests/load/reports/
```

---

## Performance Optimization Checklist

If load tests reveal performance issues, work through this checklist:

### Database Optimization
- [ ] Add indexes to frequently queried columns
- [ ] Optimize slow queries (use EXPLAIN)
- [ ] Implement query result caching
- [ ] Use eager loading to avoid N+1 queries
- [ ] Consider read replicas for reporting

### Application Optimization
- [ ] Enable OPcache for PHP
- [ ] Implement Redis for cache and sessions
- [ ] Optimize asset loading (CSS/JS minification)
- [ ] Use queue workers for slow operations
- [ ] Implement API response caching

### Infrastructure Scaling
- [ ] Increase server resources (CPU/RAM)
- [ ] Add application servers (horizontal scaling)
- [ ] Use CDN for static assets
- [ ] Implement load balancer
- [ ] Scale database instance

### Code Optimization
- [ ] Profile code with Xdebug or Blackfire
- [ ] Remove unused middleware
- [ ] Optimize service providers
- [ ] Lazy load heavy dependencies
- [ ] Review and optimize algorithms

---

## Support and Resources

### Internal Resources
- **Monitoring Dashboard:** https://monitoring.facturino.mk
- **Logs:** `storage/logs/laravel.log`
- **Metrics Endpoint:** `/api/v1/metrics`

### External Resources
- **Artillery Documentation:** https://www.artillery.io/docs
- **Laravel Performance:** https://laravel.com/docs/performance
- **Database Optimization:** https://dev.mysql.com/doc/refman/8.0/en/optimization.html

### Getting Help
- **Slack Channel:** #performance-testing
- **Email:** devops@facturino.mk
- **Emergency:** On-call rotation

---

## Appendix: Sample Commands

### Quick Reference

```bash
# Install Artillery
npm install

# Basic load test
npm run load:basic

# Stress test
npm run load:stress

# Spike test
npm run load:spike

# Critical endpoints test
npx artillery run tests/load/load-test-critical-endpoints.yml

# Generate HTML report
npm run load:report

# Run with custom target
LOAD_TEST_URL=https://my-server.com npm run load:basic

# Run with debug output
DEBUG=http npm run load:basic

# Run and save raw output
npm run load:basic > tests/load/reports/output.log 2>&1
```

---

**Document Version:** 1.0
**Last Updated:** 2025-11-17
**Next Review:** 2025-12-17

# CLAUDE-CHECKPOINT
