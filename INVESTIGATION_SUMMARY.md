# Invoice Email Issue - Investigation Summary

**Date**: 2025-12-20
**Status**: FIXED - Configuration update required
**Severity**: Production - Email functionality completely broken

---

## Problem Statement

Invoices cannot be sent by email from the production Facturino app at https://app.facturino.mk.

## Root Cause Analysis

### Primary Issue: Missing Mail Configuration

The `.env.railway` file was **completely missing** all mail-related environment variables. The production environment has NO mail configuration set.

**Timeline of Events**:
1. **Dec 13, 2025** (commit 431f8ed3): Email system switched from user-configured SMTP to centralized Postmark
2. **Configuration Update Missed**: The `.env.railway` file was never updated with Postmark credentials
3. **Result**: Production environment cannot send emails at all

### Secondary Issue: URL Generation (ALREADY FIXED)

Previous investigation (INVOICE_EMAIL_FIX.md, DEPLOYMENT_INSTRUCTIONS.md) identified and fixed APP_URL configuration issues:
- `railway-start.sh` now has fallback logic to ensure APP_URL is always set
- This ensures email URLs are generated correctly as: `https://app.facturino.mk/customer/invoices/view/{token}`

---

## Investigation Process

### Files Examined

1. **Email Flow (All Working Correctly)**:
   - `/app/Http/Controllers/V1/Admin/Invoice/SendInvoiceController.php`
     - Controller simply calls `$invoice->send($request->all())`

   - `/app/Models/Invoice.php` (lines 596-612)
     - `send()` method prepares data and calls `Mail::to($data['to'])->send(new SendInvoiceMail($data))`

   - `/app/Mail/SendInvoiceMail.php`
     - Creates EmailLog record
     - Generates URL via `route('invoice', ['email_log' => $log->token])`
     - Configures From/Reply-To headers correctly
     - From: `fakturi@facturino.mk`
     - From Name: `{Company Name} преку Facturino`
     - Reply-To: Company's configured email

   - `/resources/views/emails/send/invoice.blade.php`
     - Markdown template rendering correctly
     - Shows company logo, body text, and "View Invoice" button

2. **Routing (Working Correctly)**:
   - `/routes/web.php` (line 137)
     - Route defined: `Route::get('/customer/invoices/view/{email_log:token}', ...)->name('invoice')`

   - `/app/Http/Controllers/V1/Customer/InvoicePdfController.php`
     - `getPdf()` method handles EmailLog token correctly
     - Updates invoice status to VIEWED
     - Sends "invoice viewed" notification if enabled

3. **Configuration**:
   - `/config/mail.php`
     - Default mailer: `postmark` (line 17)
     - From address: `fakturi@facturino.mk` (line 117)
     - From name: `Facturino` (line 118)

   - `/config/services.php`
     - Postmark token: `env('POSTMARK_TOKEN')` (line 6)

   - `/composer.json`
     - Package installed: `symfony/postmark-mailer` version ^7.4 (line 46)

4. **Environment Files**:
   - `/.env.railway` - **MISSING all mail config** ❌
   - `/.env` (local dev) - Has SMTP config but empty credentials
   - `/railway-start.sh` - Has APP_URL fallback logic ✓

### Git History Analysis

Recent email-related commits:
```
431f8ed3 (Dec 13) - feat: Configure Postmark email with centralized sending
756183fb - fix: Change email footer link from facturino.com to facturino.mk
65d57048 - fix: Allow blob URLs in CSP frame-src for email preview
4a94f64b - fix: Add Postmark API to CSP connect-src for email sending
```

**Key Finding**: Commit 431f8ed3 updated:
- `app/Mail/SendInvoiceMail.php`
- `config/mail.php`
- `config/services.php`
- `composer.json`

But **DID NOT UPDATE** `.env.railway` with required Postmark configuration.

---

## The Fix

### Changes Made

1. **Updated `/.env.railway`**:
   ```env
   # Mail Configuration - Postmark
   # Required: Set POSTMARK_TOKEN in Railway environment variables with your Postmark Server API token
   MAIL_MAILER=postmark
   MAIL_FROM_ADDRESS=fakturi@facturino.mk
   MAIL_FROM_NAME=Facturino
   POSTMARK_TOKEN=${POSTMARK_TOKEN}
   ```

2. **Created `/EMAIL_FIX_GUIDE.md`**:
   - Complete deployment instructions
   - Railway setup steps
   - Testing checklist
   - Troubleshooting guide

3. **Created `/INVESTIGATION_SUMMARY.md`** (this file):
   - Complete investigation findings
   - Root cause analysis
   - File-by-file analysis

### Required Action

**CRITICAL**: Set `POSTMARK_TOKEN` environment variable in Railway:

1. Log in to Railway dashboard
2. Select Facturino project
3. Go to Variables
4. Add: `POSTMARK_TOKEN` = [your Postmark Server API token]
5. Commit and push `.env.railway` changes
6. Railway will auto-deploy

---

## Code Quality Assessment

### What's Working Well ✓

1. **Email Flow Logic**: Clean, well-structured
2. **Route Definition**: Correctly uses route model binding with EmailLog token
3. **Email Template**: Professional Markdown template
4. **From/Reply-To Headers**: Properly configured for centralized sending
5. **URL Generation**: Now reliable with APP_URL fallback logic
6. **Package Integration**: Postmark package correctly installed and configured

### What Was Broken ❌

1. **Environment Configuration**: `.env.railway` missing all mail config
2. **Deployment Process**: Configuration changes not propagated to production

### Recommendations

1. **Environment Variable Checklist**: Create a checklist for production deployments
2. **Health Check**: Add `/health` endpoint that verifies critical config like MAIL_MAILER
3. **Configuration Validation**: Add startup validation that fails early if POSTMARK_TOKEN is missing
4. **Documentation**: Keep `.env.railway` in sync with code changes

---

## Testing Verification

### Before Fix
- ❌ Emails cannot be sent
- ❌ No mail driver configured
- ❌ Application likely showing errors when attempting to send

### After Fix (Expected Results)
- ✓ Email sends successfully
- ✓ From: "Company Name преку Facturino <fakturi@facturino.mk>"
- ✓ Reply-To: Company's email
- ✓ View Invoice URL: `https://app.facturino.mk/customer/invoices/view/{token}`
- ✓ PDF attachment included (if enabled)
- ✓ Invoice status changes to "Sent"
- ✓ EmailLog record created in database

---

## Technical Insights

### Email Architecture

The email system uses a **centralized sending model**:

**Traditional Approach** (Before):
```
User's Company → Their SMTP → Customer
```
Problems: Each company must configure SMTP, deliverability issues, support burden

**Facturino Approach** (Current):
```
User's Company → Postmark (fakturi@facturino.mk) → Customer
           ↓
    Reply-To: Company Email
```
Benefits:
- No SMTP configuration needed
- Better deliverability (Postmark's reputation)
- Company branding preserved via From name
- Replies go to company via Reply-To
- Centralized monitoring and logs

### EmailLog Token System

The app uses a smart token-based system:
1. EmailLog created when email is sent
2. Token generated using Hashids (line 45: `Hashids::connection(EmailLog::class)->encode($log->id)`)
3. Token embedded in email URL
4. Customer clicks link → token decoded → EmailLog looked up → Invoice displayed
5. Bonus: Tracks when invoice was viewed

### APP_URL Configuration

Multiple layers of fallback:
1. Railway env var `APP_URL` (if set explicitly)
2. Generated from `RAILWAY_PUBLIC_DOMAIN` (if set)
3. Hardcoded fallback in `railway-start.sh` (if neither set)
4. Result: Always `https://app.facturino.mk`

---

## Files Modified

### Production Impact Files
1. `/.env.railway` - **MODIFIED** - Added mail configuration

### Documentation Files (New)
1. `/EMAIL_FIX_GUIDE.md` - Deployment guide
2. `/INVESTIGATION_SUMMARY.md` - This file

### Previous Files (Already Modified)
1. `/railway-start.sh` - APP_URL fallback logic (previous fix)
2. `/DEPLOYMENT_INSTRUCTIONS.md` - APP_URL fix documentation (previous)
3. `/INVOICE_EMAIL_FIX.md` - Initial investigation (previous)

---

## Rollback Plan

If deployment causes issues:

### Option 1: Git Revert
```bash
git revert HEAD
git push origin main
```

### Option 2: Manual Fix
Set environment variables directly in Railway dashboard:
- `MAIL_MAILER=postmark`
- `MAIL_FROM_ADDRESS=fakturi@facturino.mk`
- `MAIL_FROM_NAME=Facturino`
- `POSTMARK_TOKEN=[token]`

### Option 3: Disable Email Temporarily
Set in Railway:
- `MAIL_MAILER=log`

This will log emails instead of sending them.

---

## Deployment Checklist

- [ ] POSTMARK_TOKEN obtained from Postmark dashboard
- [ ] POSTMARK_TOKEN set in Railway variables
- [ ] `.env.railway` committed with mail configuration
- [ ] Changes pushed to main branch
- [ ] Railway deployment started
- [ ] Deployment logs checked for errors
- [ ] APP_URL verification in logs (should show https://app.facturino.mk)
- [ ] Application accessible at https://app.facturino.mk
- [ ] Test invoice email sent
- [ ] Email received successfully
- [ ] Email From/Reply-To headers correct
- [ ] View Invoice link works
- [ ] PDF attachment present (if enabled)
- [ ] Invoice status updated to "Sent"
- [ ] Monitor for 24 hours for any issues

---

## Lessons Learned

1. **Configuration Management**: Production env files must be updated alongside code changes
2. **Deployment Documentation**: Major changes (like mail system overhaul) need deployment guides
3. **Testing**: Need staging environment to catch production config issues
4. **Health Checks**: Critical services should be validated on startup
5. **Change Communication**: When changing core services (mail), ensure ops team is informed

---

## Next Steps

### Immediate (Required)
1. Set POSTMARK_TOKEN in Railway
2. Deploy .env.railway changes
3. Test email sending
4. Monitor for issues

### Short Term (Recommended)
1. Create staging environment with Railway Review Apps
2. Add health check for mail configuration
3. Document all required Railway env vars
4. Create deployment checklist

### Long Term (Nice to Have)
1. Automated env var validation
2. E2E tests for email sending
3. Email sending metrics/monitoring
4. Postmark webhook integration for bounce handling

---

## Support Information

**Production URL**: https://app.facturino.mk
**Railway Project**: Facturino
**Database**: Railway MySQL
**Mail Service**: Postmark (fakturi@facturino.mk)
**Mail Package**: symfony/postmark-mailer ^7.4

**Key Contacts**:
- Postmark Dashboard: https://account.postmarkapp.com
- Railway Dashboard: https://railway.app
- Application Logs: Railway → Service → Deployments → Logs

---

## Conclusion

The invoice email functionality is currently **broken in production** due to missing mail configuration in the `.env.railway` file. The code itself is working correctly - the issue is purely configuration-based.

**Fix Confidence**: HIGH - This is a straightforward configuration addition with no code changes needed.

**Risk Level**: LOW - Adding environment variables is non-destructive and easily reversible.

**Estimated Downtime**: None - Service continues running, email functionality will start working immediately after deployment.

**Priority**: CRITICAL - Email is core functionality for an invoicing system.
