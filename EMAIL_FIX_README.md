# Email Fix - Quick Reference

## TL;DR

**Problem**: Invoices cannot be sent by email
**Cause**: Missing Postmark configuration in production
**Fix**: Add mail config to .env.railway + set POSTMARK_TOKEN in Railway
**Status**: Ready to deploy

---

## What's Broken

Production environment at https://app.facturino.mk cannot send emails because `.env.railway` is missing all mail configuration.

---

## The Fix (3 Steps)

### Step 1: Set POSTMARK_TOKEN in Railway
1. Log in to Railway dashboard
2. Go to your Facturino project → Variables
3. Add: `POSTMARK_TOKEN` = `[your-postmark-server-api-token]`

### Step 2: Deploy Changes
```bash
git add .env.railway
git commit -m "fix: Add Postmark mail configuration for production"
git push origin main
```

### Step 3: Test
1. Go to https://app.facturino.mk
2. Send a test invoice
3. Verify email is received

---

## Files Changed

- `.env.railway` - Added mail configuration (MAIL_MAILER, MAIL_FROM_ADDRESS, MAIL_FROM_NAME, POSTMARK_TOKEN)

---

## Documentation

**Quick Start**: Read this file
**Deployment Guide**: `EMAIL_FIX_GUIDE.md` (comprehensive instructions)
**Investigation Details**: `INVESTIGATION_SUMMARY.md` (technical deep-dive)
**Previous Fixes**: `INVOICE_EMAIL_FIX.md`, `DEPLOYMENT_INSTRUCTIONS.md` (URL issues)

---

## What Changed in .env.railway

```diff
+ RAILWAY_PUBLIC_DOMAIN=app.facturino.mk
+
+ # Mail Configuration - Postmark
+ # Required: Set POSTMARK_TOKEN in Railway environment variables
+ MAIL_MAILER=postmark
+ MAIL_FROM_ADDRESS=fakturi@facturino.mk
+ MAIL_FROM_NAME=Facturino
+ POSTMARK_TOKEN=${POSTMARK_TOKEN}
```

---

## Verification

After deployment, test:
- [ ] Email sends without errors
- [ ] Email is received
- [ ] From shows: "Company Name преку Facturino"
- [ ] URL is: https://app.facturino.mk/customer/invoices/view/{token}
- [ ] PDF is attached (if enabled)

---

## Get Your Postmark Token

Don't have a Postmark token yet?

1. Go to https://postmarkapp.com
2. Sign up / Log in
3. Create a server (or select existing)
4. Copy the "Server API Token"
5. Add sender signature for `fakturi@facturino.mk`

---

## Rollback

If something breaks:
```bash
git revert HEAD
git push origin main
```

Or set `MAIL_MAILER=log` in Railway to disable sending temporarily.

---

## Support

**Issue**: Email still not working?
**Check**: Railway logs for error messages
**Guide**: See `EMAIL_FIX_GUIDE.md` troubleshooting section

---

## Why This Happened

On Dec 13, 2025 (commit 431f8ed3), the email system was switched from SMTP to Postmark. The code was updated but `.env.railway` was not updated with the new Postmark configuration.

---

## Priority

**CRITICAL** - Email is core functionality for invoicing app
**Risk**: LOW - Configuration-only change, easily reversible
**Downtime**: NONE - Non-disruptive deployment
