# Invoice Email Sending Fix - Production Guide

## Executive Summary

**Problem**: Invoices cannot be sent by email from the production Facturino app at https://app.facturino.mk

**Root Cause**: The production environment is missing required mail configuration variables. The app was updated to use Postmark for email sending (commit 431f8ed3 on Dec 13, 2025), but the Railway environment variables were never updated.

**Status**: Configuration file `.env.railway` has been updated. Deployment to Railway required.

---

## Investigation Findings

### What Changed
On December 13, 2025 (commit 431f8ed3), the email system was switched from user-configured SMTP to centralized Postmark:

**Before**: Each company configured their own SMTP server
**After**: All emails sent via Postmark from `fakturi@facturino.mk` with company name in "From" header

### Email Flow (Working Correctly)
1. User clicks "Send Invoice" in UI
2. `SendInvoiceController` calls `$invoice->send($data)`
3. `Invoice::send()` method prepares email data and calls `Mail::to()->send(new SendInvoiceMail($data))`
4. `SendInvoiceMail::build()` creates EmailLog record and generates URL via `route('invoice', ['email_log' => $log->token])`
5. Email template renders with "View Invoice" button linking to the generated URL
6. Mail is sent via configured driver (Postmark)

### What's Missing
The `.env.railway` file is missing ALL mail configuration:
- No `MAIL_MAILER` (defaults to 'postmark' in config/mail.php)
- No `MAIL_FROM_ADDRESS`
- No `MAIL_FROM_NAME`
- No `POSTMARK_TOKEN` (CRITICAL - without this, emails cannot be sent)

---

## The Fix

### 1. Updated Files

#### `.env.railway` (UPDATED)
Added mail configuration section:
```env
# Mail Configuration - Postmark
# Required: Set POSTMARK_TOKEN in Railway environment variables with your Postmark Server API token
MAIL_MAILER=postmark
MAIL_FROM_ADDRESS=fakturi@facturino.mk
MAIL_FROM_NAME=Facturino
POSTMARK_TOKEN=${POSTMARK_TOKEN}
```

### 2. Required Railway Environment Variables

You must add ONE critical environment variable in Railway dashboard:

**POSTMARK_TOKEN**: Your Postmark Server API token

To get your Postmark token:
1. Log in to Postmark account
2. Go to Servers → Select your server
3. Copy the "Server API Token"

### 3. Deployment Steps

#### Step 1: Set POSTMARK_TOKEN in Railway

1. Open Railway dashboard: https://railway.app
2. Select the Facturino project
3. Click on the service
4. Go to **Variables** tab
5. Click **+ New Variable**
6. Add:
   - **Variable**: `POSTMARK_TOKEN`
   - **Value**: `[paste your Postmark Server API token]`
7. Click **Add**

#### Step 2: Deploy Updated Configuration

```bash
# Commit the .env.railway changes
git add .env.railway
git commit -m "fix: Add Postmark mail configuration for production email sending"
git push origin main
```

Railway will automatically detect the push and deploy.

#### Step 3: Verify Deployment

Watch the Railway deployment logs. You should see:
```
Final APP_URL: https://app.facturino.mk
Final SESSION_DOMAIN: .facturino.mk
Final SANCTUM_STATEFUL_DOMAINS: app.facturino.mk,facturino.mk
```

After deployment completes, the mail system should be fully functional.

#### Step 4: Test Email Sending

1. Log in to https://app.facturino.mk
2. Navigate to an invoice
3. Click "Send Invoice"
4. Enter a test email address
5. Click Send
6. Check the recipient inbox
7. Verify:
   - Email is received
   - From address shows: "Company Name преку Facturino <fakturi@facturino.mk>"
   - Reply-To is set to company's email
   - "View Invoice" button links to: `https://app.facturino.mk/customer/invoices/view/{token}`
   - PDF attachment is included (if enabled)

---

## Technical Details

### Mail Configuration (config/mail.php)
- **Default mailer**: `postmark` (line 17)
- **From address**: `fakturi@facturino.mk` (line 117)
- **From name**: `Facturino` (line 118)

### Postmark Service (config/services.php)
- **Token**: Reads from `POSTMARK_TOKEN` env var (line 6)

### Email Template
- **Path**: `resources/views/emails/send/invoice.blade.php`
- **Type**: Markdown (Laravel mail component)
- **Dynamic From Name**: `"{Company Name} преку Facturino"`
- **Reply-To**: Company's own email from their settings

### Package Installed
- **Package**: `symfony/postmark-mailer` (version ^7.4)
- **Location**: composer.json line 46

---

## Troubleshooting

### Issue: Emails still not sending after deployment

**Check 1: Verify POSTMARK_TOKEN is set**
```bash
railway run bash -c "echo POSTMARK_TOKEN: \$POSTMARK_TOKEN"
```
Should show your token (not empty).

**Check 2: Verify mail driver**
```bash
railway run php artisan tinker --execute="echo config('mail.default');"
```
Should output: `postmark`

**Check 3: Check Postmark Dashboard**
- Log in to Postmark
- Go to Activity
- Check for failed sends
- Check for API errors

**Check 4: Check Laravel logs**
```bash
railway logs --filter="mail\|email\|postmark" -i
```

### Issue: Email URL is incorrect

This was a separate issue that has already been fixed. The `railway-start.sh` script now ensures `APP_URL` is always set to `https://app.facturino.mk`.

If URLs are still wrong:
1. Check Railway logs for "Final APP_URL" line
2. Should show: `Final APP_URL: https://app.facturino.mk`
3. If not, verify `RAILWAY_PUBLIC_DOMAIN=app.facturino.mk` is set in Railway variables

### Issue: "From" name not showing correctly

The "From" name should be: `{Company Name} преку Facturino`

If showing incorrectly:
1. Check company settings in database
2. Verify company has a name set
3. Check `SendInvoiceMail.php` line 51-52

---

## Environment Variable Summary

### Required Variables (MUST be set in Railway)
1. `POSTMARK_TOKEN` - Your Postmark Server API token

### Important Variables (Already set via .env.railway)
1. `APP_URL` - https://app.facturino.mk
2. `RAILWAY_PUBLIC_DOMAIN` - app.facturino.mk
3. `MAIL_MAILER` - postmark
4. `MAIL_FROM_ADDRESS` - fakturi@facturino.mk
5. `MAIL_FROM_NAME` - Facturino
6. `SESSION_DOMAIN` - .facturino.mk
7. `SANCTUM_STATEFUL_DOMAINS` - app.facturino.mk,facturino.mk

### Database Variables (Already set)
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

---

## Testing Checklist

After deployment, verify:

- [ ] POSTMARK_TOKEN is set in Railway variables
- [ ] Deployment completed successfully
- [ ] Railway logs show correct APP_URL
- [ ] Can log in to https://app.facturino.mk
- [ ] Can navigate to an invoice
- [ ] "Send Invoice" button is clickable
- [ ] Email send dialog appears
- [ ] Email is sent without errors
- [ ] Email is received in recipient inbox
- [ ] Email "From" shows company name + " преку Facturino"
- [ ] Email "Reply-To" is set to company email
- [ ] "View Invoice" button works
- [ ] Invoice URL is correct: https://app.facturino.mk/customer/invoices/view/{token}
- [ ] PDF attachment is included (if enabled in settings)
- [ ] Invoice status changes to "Sent" after sending

---

## Postmark Setup (If Not Already Configured)

If you don't have a Postmark account yet:

### 1. Create Postmark Account
1. Go to https://postmarkapp.com
2. Sign up for an account
3. Verify your email

### 2. Add Sender Signature
1. Go to Sender Signatures
2. Add: `fakturi@facturino.mk`
3. Verify the email address

### 3. Create Server
1. Go to Servers
2. Click "Create Server"
3. Name: "Facturino Production"
4. Type: Transactional
5. Copy the Server API Token

### 4. Configure DKIM/SPF
For `facturino.mk` domain:
1. Go to Sender Signatures → facturino.mk
2. Follow DKIM setup instructions
3. Add DNS records to your domain registrar
4. Wait for verification (can take 24-48 hours)

---

## Files Modified

1. **/.env.railway** - Added Postmark mail configuration
2. **/EMAIL_FIX_GUIDE.md** (this file) - Complete fix documentation

## Files Analyzed (No Changes Needed)

1. **/app/Mail/SendInvoiceMail.php** - Email generation (working correctly)
2. **/app/Models/Invoice.php** - Send method (working correctly)
3. **/app/Http/Controllers/V1/Admin/Invoice/SendInvoiceController.php** - Controller (working correctly)
4. **/routes/web.php** - Route definition (working correctly)
5. **/resources/views/emails/send/invoice.blade.php** - Email template (working correctly)
6. **/config/mail.php** - Mail configuration (correct, uses Postmark)
7. **/config/services.php** - Postmark service config (correct)
8. **/composer.json** - Postmark package installed (correct)
9. **/railway-start.sh** - Startup script with APP_URL fallback (working correctly)

---

## Previous Fixes Already Applied

The following issues were already fixed in previous commits:

1. **APP_URL Configuration** (DEPLOYMENT_INSTRUCTIONS.md / INVOICE_EMAIL_FIX.md)
   - `railway-start.sh` now has fallback logic for APP_URL
   - Ensures URLs in emails are always correct

2. **Postmark Integration** (commit 431f8ed3)
   - Added `symfony/postmark-mailer` package
   - Updated `SendInvoiceMail.php` to use centralized sending
   - Updated config files

3. **Email Footer Link** (commit 756183fb)
   - Changed from facturino.com to facturino.mk

---

## Support

If issues persist after following this guide:

1. Check Railway logs: `railway logs --follow`
2. Check Postmark Activity dashboard for API errors
3. Review Laravel logs in `/storage/logs/laravel.log`
4. Verify all environment variables are set correctly
5. Contact development team with:
   - Railway deployment logs
   - Postmark error messages (if any)
   - Laravel error logs (if any)
   - Steps to reproduce the issue

---

## Commit Message Template

```
fix: Add Postmark mail configuration for production email sending

- Add MAIL_MAILER=postmark to .env.railway
- Add MAIL_FROM_ADDRESS and MAIL_FROM_NAME
- Add POSTMARK_TOKEN variable (requires Railway env var)
- Create EMAIL_FIX_GUIDE.md with deployment instructions

Root cause: Production environment was missing mail configuration
after Postmark integration (commit 431f8ed3).

Required Railway action:
- Set POSTMARK_TOKEN environment variable

Testing:
- Send invoice via email
- Verify email received with correct URL
- Verify From/Reply-To headers correct
```

---

## Next Steps

1. Set `POSTMARK_TOKEN` in Railway variables
2. Commit and push `.env.railway` changes
3. Verify deployment completes successfully
4. Test invoice email sending
5. Monitor for any issues over next 24-48 hours
6. Clean up temporary documentation files after verification
