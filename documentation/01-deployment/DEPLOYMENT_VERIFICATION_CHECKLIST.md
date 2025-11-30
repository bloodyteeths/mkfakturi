# Deployment Verification Checklist
**Version:** 1.0.0
**Target:** Production v1.0.0
**Date:** 2025-11-17

---

## Overview

This checklist must be completed within **2 hours** of production deployment to verify that all critical functionality is working correctly. If any critical items fail, initiate rollback procedure immediately.

**Priority Levels:**
- 🔴 **CRITICAL** - Must pass, triggers rollback if failed
- 🟡 **HIGH** - Should pass, investigate if failed
- 🟢 **MEDIUM** - Nice to have, can be fixed post-deployment

---

## 1. Infrastructure Health Checks

### 1.1 Application Accessibility 🔴 CRITICAL

**Test:** Can users access the application?

```bash
# Test from external network (not localhost)
curl -I https://your-production-domain.com

# Expected: HTTP/2 200 OK
```

**Manual Verification:**
- [ ] Open https://your-production-domain.com in browser
- [ ] Page loads within 5 seconds
- [ ] No 500/502/503 errors
- [ ] CSS and JavaScript load correctly
- [ ] No console errors in browser DevTools

**Pass Criteria:** ✅ Application loads successfully with no errors

**If Failed:** 🚨 **INITIATE ROLLBACK IMMEDIATELY**

---

### 1.2 Railway Service Status 🔴 CRITICAL

```bash
# Check Railway deployment status
railway status

# Expected: "Status: Active"
```

**Verification:**
- [ ] Railway dashboard shows deployment as "Active"
- [ ] No error logs in Railway console
- [ ] All services (app, database) are running
- [ ] Memory usage < 80%
- [ ] CPU usage < 70%

**Pass Criteria:** ✅ All services healthy

**If Failed:** 🚨 Investigate logs, consider rollback if unresolvable

---

### 1.3 Database Connectivity 🔴 CRITICAL

```bash
# Test database connection
railway run -- php artisan tinker
>>> \DB::connection()->getPdo();
>>> \DB::table('users')->count();
>>> exit

# Expected: PDO object returned, user count > 0
```

**Verification:**
- [ ] Database connection successful
- [ ] User table accessible
- [ ] No connection timeout errors
- [ ] Connection pool not exhausted

**Pass Criteria:** ✅ Database queries execute successfully

**If Failed:** 🚨 Check DATABASE_URL, verify PostgreSQL service running

---

### 1.4 Health Endpoint 🟡 HIGH

```bash
# Test health check endpoint
curl https://your-production-domain.com/api/health

# Expected: {"status":"ok","timestamp":"..."}
```

**Verification:**
- [ ] Health endpoint returns 200 OK
- [ ] JSON response is valid
- [ ] Timestamp is current

**Pass Criteria:** ✅ Health endpoint accessible

**If Failed:** ⚠️ Application may still work, but monitoring will be affected

---

## 2. Authentication & Security

### 2.1 Admin Login Flow 🔴 CRITICAL

**Manual Test:**
1. Navigate to: https://your-domain.com/admin/login
2. Enter valid admin credentials
3. Submit form

**Verification:**
- [ ] Login page loads correctly
- [ ] Form submission succeeds (no 419 CSRF errors)
- [ ] Redirects to dashboard after login
- [ ] User is actually logged in (can navigate pages)
- [ ] Logout works correctly

**Pass Criteria:** ✅ Complete login/logout cycle works

**If Failed:** 🚨 **CRITICAL - Users cannot access application**
- Check `SESSION_DRIVER=database` in environment
- Verify sessions table exists
- Check CSRF token generation

---

### 2.2 Session Persistence 🔴 CRITICAL

**Manual Test:**
1. Login to admin panel
2. Navigate to Invoices page
3. Navigate to Customers page
4. Navigate to Settings page
5. Wait 2 minutes
6. Refresh page

**Verification:**
- [ ] No unexpected logouts during navigation
- [ ] Session persists across page refreshes
- [ ] No "Unauthenticated" errors
- [ ] User state maintained

**Pass Criteria:** ✅ Sessions persist for entire browsing session

**If Failed:** 🚨 **CRITICAL - Session persistence broken**
- Verify `SESSION_DRIVER=database`
- Check sessions table migration ran
- Verify database session cleanup job

---

### 2.3 Two-Factor Authentication Setup 🟡 HIGH

**Manual Test:**
1. Login as admin
2. Navigate to: Settings → Security → Two-Factor Authentication
3. Click "Enable Two-Factor Authentication"
4. Scan QR code with Google Authenticator
5. Enter verification code
6. Download recovery codes

**Verification:**
- [ ] 2FA settings page loads
- [ ] QR code displays correctly
- [ ] Secret key shown as alternative
- [ ] Verification code accepted
- [ ] Recovery codes generated (8 codes)
- [ ] Can disable 2FA after enabling

**Pass Criteria:** ✅ 2FA setup completes successfully

**If Failed:** ⚠️ Feature unavailable but not critical
- Check migrations ran (`two_factor_*` columns exist)
- Verify Fortify configuration

---

### 2.4 Password Reset Flow 🟡 HIGH

**Manual Test:**
1. Logout
2. Click "Forgot Password"
3. Enter email address
4. Check email inbox

**Verification:**
- [ ] Password reset form accessible
- [ ] Email sent successfully (check logs if no email received)
- [ ] Reset link in email works
- [ ] Can set new password
- [ ] Can login with new password

**Pass Criteria:** ✅ Password reset email sent

**If Failed:** ⚠️ Check mail configuration
- Verify MAIL_* environment variables
- Check queue worker is processing jobs
- Test with: `php artisan tinker` → `Mail::raw(...)`

---

## 3. Core Functionality

### 3.1 Invoice Creation 🔴 CRITICAL

**Manual Test:**
1. Navigate to Invoices → Create New
2. Fill in customer details
3. Add line items
4. Save invoice

**Verification:**
- [ ] Invoice creation form loads
- [ ] Customer dropdown populated
- [ ] Line items can be added
- [ ] Invoice saves successfully
- [ ] Invoice appears in list
- [ ] Invoice number generated correctly

**Pass Criteria:** ✅ Invoice created and saved

**If Failed:** 🚨 **CORE FEATURE BROKEN**
- Check database write permissions
- Verify invoice sequence generation
- Check validation rules

---

### 3.2 PDF Generation 🟡 HIGH

**Manual Test:**
1. Open created invoice
2. Click "Download PDF" or "View PDF"

**Verification:**
- [ ] PDF generates without errors
- [ ] PDF contains correct data
- [ ] PDF renders properly (fonts, images)
- [ ] Logo appears if configured
- [ ] Line items display correctly

**Pass Criteria:** ✅ PDF downloads successfully

**If Failed:** ⚠️ Check PDF driver configuration
- Verify DomPDF or Gotenberg configured
- Check storage permissions
- Verify fonts installed

---

### 3.3 Email Invoice Delivery 🟡 HIGH

**Manual Test:**
1. Open an invoice
2. Click "Send Email"
3. Enter recipient email
4. Send

**Verification:**
- [ ] Email form loads
- [ ] Email validation works
- [ ] Send button triggers job
- [ ] Queue job processes successfully
- [ ] Email actually received (check inbox)

**Pass Criteria:** ✅ Email sent and delivered

**If Failed:** ⚠️ Check mail and queue configuration
- Verify MAIL_* variables set correctly
- Check queue worker running
- Test SMTP credentials

---

### 3.4 Customer Management 🟡 HIGH

**Manual Test:**
1. Navigate to Customers
2. Click "Add Customer"
3. Fill in customer details
4. Save

**Verification:**
- [ ] Customer list loads
- [ ] Create form accessible
- [ ] Customer saves successfully
- [ ] Customer appears in list
- [ ] Customer details can be edited

**Pass Criteria:** ✅ Customer CRUD operations work

**If Failed:** ⚠️ Check database and validation

---

### 3.5 Bills/Expense Management 🟡 HIGH

**Manual Test:**
1. Navigate to Bills
2. Create new bill
3. Upload receipt image (optional)
4. Save bill

**Verification:**
- [ ] Bills list loads
- [ ] Create form accessible
- [ ] File upload works (if testing)
- [ ] Bill saves successfully
- [ ] Bill appears in list

**Pass Criteria:** ✅ Basic bill creation works

**If Failed:** ⚠️ Check file storage configuration

---

## 4. Data Integrity

### 4.1 Database Migrations 🔴 CRITICAL

```bash
# Verify all migrations ran
railway run -- php artisan migrate:status

# Check for pending migrations
railway run -- php artisan migrate:status | grep -i pending
```

**Verification:**
- [ ] No pending migrations
- [ ] All migrations show "Ran"
- [ ] 2FA migration included (2025_11_16_*)

**Pass Criteria:** ✅ All migrations complete

**If Failed:** 🚨 Run migrations immediately
```bash
railway run -- php artisan migrate --force
```

---

### 4.2 Database Relationships 🟡 HIGH

```bash
# Test key relationships
railway run -- php artisan tinker
>>> $invoice = \App\Models\Invoice::first();
>>> $invoice->customer;  # Should load customer
>>> $invoice->items;     # Should load line items
>>> $invoice->payments;  # Should load payments
>>> exit
```

**Verification:**
- [ ] Invoice → Customer relationship works
- [ ] Invoice → Items relationship works
- [ ] User → Company relationship works
- [ ] No N+1 query issues

**Pass Criteria:** ✅ Relationships load correctly

**If Failed:** ⚠️ May indicate data corruption

---

### 4.3 Data Seeding (Fresh Install Only) 🟢 MEDIUM

```bash
# Only if this is a fresh installation
railway run -- php artisan db:seed
```

**Verification:**
- [ ] Demo data created (if seeding)
- [ ] Default admin user exists
- [ ] Default company created
- [ ] Sample invoices present (optional)

**Pass Criteria:** ✅ Seed data created

**If Failed:** ⚠️ Run manually if needed for demo

---

## 5. Performance Benchmarks

### 5.1 Response Times 🟡 HIGH

```bash
# Test dashboard load time
time curl -s https://your-domain.com/admin/dashboard > /dev/null

# Test API response time
time curl -s https://your-domain.com/api/v1/invoices > /dev/null
```

**Target Benchmarks:**
- **With Redis:**
  - Dashboard: < 500ms ✅
  - API calls: < 200ms ✅
- **Without Redis:**
  - Dashboard: < 2000ms ⚠️
  - API calls: < 1000ms ⚠️

**Verification:**
- [ ] Dashboard loads in acceptable time
- [ ] API responses are responsive
- [ ] No timeouts
- [ ] Page feels snappy (subjective but important)

**Pass Criteria:** ✅ Response times meet targets

**If Failed:** ⚠️ Performance degraded but not critical
- Consider enabling Redis
- Check slow query log
- Verify caches are enabled

---

### 5.2 Cache Effectiveness 🟢 MEDIUM

```bash
# Test cache is working
railway run -- php artisan tinker
>>> Cache::put('deployment_test', now(), 60);
>>> Cache::get('deployment_test');
>>> Cache::forget('deployment_test');
>>> exit
```

**Verification:**
- [ ] Cache writes succeed
- [ ] Cache reads succeed
- [ ] Cache deletes succeed
- [ ] No cache errors in logs

**Pass Criteria:** ✅ Cache operations work

**If Failed:** ⚠️ Performance will be degraded
- Check CACHE_STORE setting
- Verify Redis connection (if using Redis)

---

### 5.3 Queue Processing 🟡 HIGH

```bash
# Test queue is processing
railway run -- php artisan queue:work --once

# Check failed jobs
railway run -- php artisan queue:failed
```

**Verification:**
- [ ] Queue worker processes jobs
- [ ] No stuck jobs
- [ ] Failed jobs count is reasonable (< 5%)
- [ ] Queue is not backing up

**Pass Criteria:** ✅ Jobs process successfully

**If Failed:** ⚠️ Async tasks won't work
- Verify queue worker is running
- Check QUEUE_CONNECTION setting
- Investigate failed jobs

---

## 6. Integration Tests

### 6.1 Payment Gateway (Paddle) 🟢 MEDIUM

**Manual Test (Optional):**
1. Navigate to Billing/Subscription page
2. Attempt to create checkout session
3. Verify Paddle overlay loads

**Verification:**
- [ ] Paddle checkout loads (if testing)
- [ ] Webhook endpoint accessible: `/api/webhooks/paddle`
- [ ] Sandbox mode configured correctly

**Pass Criteria:** ✅ Integration configured

**If Failed:** ⚠️ Payment features unavailable
- Verify PADDLE_* environment variables
- Check webhook signature verification

---

### 6.2 File Storage 🟡 HIGH

```bash
# Test file upload
railway run -- php artisan tinker
>>> Storage::disk('public')->put('test.txt', 'test');
>>> Storage::disk('public')->exists('test.txt');
>>> Storage::disk('public')->delete('test.txt');
>>> exit
```

**Verification:**
- [ ] Files can be written
- [ ] Files can be read
- [ ] Files can be deleted
- [ ] Storage link created: `php artisan storage:link`

**Pass Criteria:** ✅ File operations work

**If Failed:** ⚠️ Uploads will fail
- Check storage permissions
- Verify disk configuration

---

### 6.3 Backup System 🟢 MEDIUM

```bash
# Test backup (only if S3 configured)
railway run -- php artisan backup:run

# List backups
railway run -- php artisan backup:list
```

**Verification:**
- [ ] Backup job executes (if S3 configured)
- [ ] Backup uploaded to S3 (if configured)
- [ ] Backup contains database dump
- [ ] No backup errors

**Pass Criteria:** ✅ Backup created successfully

**If Failed:** ⚠️ Disaster recovery compromised
- Verify AWS credentials
- Check S3 bucket exists
- Verify IAM permissions

---

## 7. Monitoring & Logging

### 7.1 Application Logs 🟡 HIGH

```bash
# Check for critical errors
railway logs --tail=100 | grep -E "CRITICAL|ERROR|Exception"

# Monitor logs in real-time
railway logs --follow
```

**Verification:**
- [ ] No critical errors in recent logs
- [ ] No unhandled exceptions
- [ ] Log level appropriate (error/warning in prod)
- [ ] Logs are readable and informative

**Pass Criteria:** ✅ No critical errors logged

**If Failed:** ⚠️ Investigate errors
- Check stack traces
- Fix critical issues
- Consider rollback if severe

---

### 7.2 Metrics Endpoint 🟢 MEDIUM

```bash
# Test Prometheus metrics
curl https://your-domain.com/metrics

# Expected: Prometheus-formatted metrics
```

**Verification:**
- [ ] Metrics endpoint accessible
- [ ] Returns valid Prometheus format
- [ ] Shows application metrics

**Pass Criteria:** ✅ Metrics available

**If Failed:** ⚠️ Monitoring will be limited
- Verify FEATURE_MONITORING=true
- Check endpoint authentication

---

### 7.3 Error Tracking 🟢 MEDIUM

**Manual Test:**
1. Trigger a 404 error (navigate to non-existent page)
2. Check logs for error

**Verification:**
- [ ] 404 errors logged appropriately
- [ ] Custom error pages displayed (not Laravel debug)
- [ ] No sensitive info leaked in errors

**Pass Criteria:** ✅ Errors handled gracefully

**If Failed:** ⚠️ User experience degraded

---

## 8. User Acceptance Spot Checks

### 8.1 Mobile Responsiveness 🟡 HIGH

**Manual Test:**
1. Open application on mobile device or resize browser
2. Test login, dashboard, invoice list

**Verification:**
- [ ] Login page is mobile-friendly
- [ ] Dashboard is usable on mobile
- [ ] Lists are scrollable
- [ ] Buttons are tap-friendly

**Pass Criteria:** ✅ Basic mobile usability

**If Failed:** ⚠️ Mobile UX degraded (known issue for v1.0.0)

---

### 8.2 Cross-Browser Compatibility 🟢 MEDIUM

**Manual Test:**
Test in Chrome, Firefox, Safari (if available)

**Verification:**
- [ ] Application loads in all browsers
- [ ] No JavaScript errors
- [ ] Styling consistent
- [ ] Core functionality works

**Pass Criteria:** ✅ Works in major browsers

**If Failed:** ⚠️ Document browser-specific issues

---

## 9. Security Verification

### 9.1 HTTPS Configuration 🔴 CRITICAL

```bash
# Verify HTTPS redirect
curl -I http://your-domain.com

# Expected: 301 redirect to https://
```

**Verification:**
- [ ] HTTP redirects to HTTPS
- [ ] SSL certificate valid
- [ ] No mixed content warnings
- [ ] Secure cookies set

**Pass Criteria:** ✅ HTTPS enforced

**If Failed:** 🚨 **SECURITY RISK**
- Railway should handle automatically
- Check domain configuration

---

### 9.2 Debug Mode Disabled 🔴 CRITICAL

```bash
# Verify debug is disabled
railway run -- php artisan tinker
>>> config('app.debug');
>>> exit

# Expected: false
```

**Manual Test:**
1. Trigger 404 error
2. Verify no stack trace visible

**Verification:**
- [ ] APP_DEBUG=false confirmed
- [ ] Error pages don't show stack traces
- [ ] No sensitive info in error messages

**Pass Criteria:** ✅ Debug mode disabled

**If Failed:** 🚨 **CRITICAL SECURITY RISK - FIX IMMEDIATELY**

---

### 9.3 Environment File Protection 🔴 CRITICAL

```bash
# Attempt to access .env file
curl https://your-domain.com/.env

# Expected: 404 Not Found or 403 Forbidden
```

**Verification:**
- [ ] `.env` file not accessible via web
- [ ] No secrets exposed in HTML source
- [ ] No secrets in JavaScript bundles

**Pass Criteria:** ✅ Environment protected

**If Failed:** 🚨 **CRITICAL SECURITY BREACH**

---

## 10. Post-Deployment Tasks

### 10.1 Cache Optimization 🟡 HIGH

```bash
# Clear and rebuild caches
railway run -- php artisan config:clear
railway run -- php artisan cache:clear
railway run -- php artisan route:clear
railway run -- php artisan view:clear

# Rebuild caches
railway run -- php artisan config:cache
railway run -- php artisan route:cache
railway run -- php artisan view:cache
railway run -- php artisan optimize
```

**Verification:**
- [ ] All cache commands succeed
- [ ] Application still works after cache clear
- [ ] Performance improved after optimization

**Pass Criteria:** ✅ Caches optimized

---

### 10.2 Queue Worker Status 🟡 HIGH

```bash
# Verify queue worker is running
# This should be a separate Railway service

railway run -- php artisan queue:work --once
```

**Verification:**
- [ ] Queue worker service deployed
- [ ] Worker processing jobs
- [ ] No memory leaks
- [ ] Auto-restart on failure

**Pass Criteria:** ✅ Queue worker operational

**If Failed:** ⚠️ Async tasks won't process
- Deploy queue worker service
- Monitor for job backlog

---

## Verification Summary

### Critical Checks (Must Pass)
- [ ] Application accessible
- [ ] Database connectivity
- [ ] Admin login works
- [ ] Session persistence
- [ ] Invoice creation
- [ ] Migrations complete
- [ ] HTTPS enforced
- [ ] Debug mode disabled
- [ ] Environment protected

### High Priority (Should Pass)
- [ ] Health endpoint
- [ ] 2FA setup
- [ ] PDF generation
- [ ] Email delivery
- [ ] Response times acceptable
- [ ] Queue processing
- [ ] Application logs clean

### Medium Priority (Nice to Have)
- [ ] Backup system
- [ ] Metrics endpoint
- [ ] Mobile responsiveness
- [ ] Cross-browser compatibility

---

## Decision Matrix

### ✅ DEPLOY - All Critical Checks Pass
- All 9 critical checks passed
- Most high-priority checks passed
- Minor issues documented for post-deploy fixes
- **Action:** Monitor for 24 hours, fix non-critical issues

### ⚠️ CONDITIONAL DEPLOY - Some High-Priority Failures
- All critical checks passed
- Some high-priority failures present
- Failures have workarounds
- **Action:** Deploy with monitoring, prioritize fixes

### 🚨 ROLLBACK - Any Critical Check Fails
- One or more critical checks failed
- No workaround available
- User experience severely impacted
- **Action:** Initiate rollback procedure immediately

---

## Sign-Off

**Deployment Date:** ________________
**Deployment Time:** ________________
**Deployed By:** ____________________

**Verification Results:**
- Critical Checks: _____ / 9 passed
- High Priority: _____ / 8 passed
- Medium Priority: _____ / 6 passed

**Overall Status:** ☐ PASS ☐ CONDITIONAL ☐ FAIL

**Verified By:** ____________________
**Date/Time:** ______________________

**Notes:**
```
[Add any observations, issues found, or follow-up actions needed]
```

**Decision:** ☐ PROCEED ☐ ROLLBACK

**Approved By:** ____________________
**Date/Time:** ______________________

---

**Document Version:** 1.0.0
**Last Updated:** 2025-11-17

🤖 **Generated with Claude Code**
**Co-Authored-By: Claude <noreply@anthropic.com>**
