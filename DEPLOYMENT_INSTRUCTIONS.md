# Deployment Instructions for Invoice Email Fix

## Quick Summary
Invoice emails were generating incorrect URLs because the `APP_URL` environment variable was not properly configured on Railway. This fix ensures that `APP_URL` is always set correctly.

## Changes Made

### 1. Updated `.env.railway`
Added explicit `RAILWAY_PUBLIC_DOMAIN` variable:
```env
RAILWAY_PUBLIC_DOMAIN=app.facturino.mk
```

### 2. Updated `railway-start.sh`
Added fallback logic to ensure `APP_URL` is always set, even if `RAILWAY_PUBLIC_DOMAIN` is missing:
```bash
# Lines 178-202
# Now includes fallback to app.facturino.mk if RAILWAY_PUBLIC_DOMAIN is not set
# Logs final values for debugging
```

### 3. Created Verification Script
Added `verify-app-url.sh` to verify the configuration is correct after deployment.

## Deployment Steps

### Step 1: Set Environment Variables in Railway

1. Log in to Railway dashboard
2. Navigate to your Facturino project
3. Go to **Settings** → **Variables**
4. Add or verify these variables:

```env
APP_URL=https://app.facturino.mk
RAILWAY_PUBLIC_DOMAIN=app.facturino.mk
SESSION_DOMAIN=.facturino.mk
SANCTUM_STATEFUL_DOMAINS=app.facturino.mk,facturino.mk
```

**Note**: Even though `railway-start.sh` now has fallbacks, it's best practice to set these explicitly in Railway.

### Step 2: Deploy the Changes

1. Commit the changes:
```bash
git add .env.railway railway-start.sh
git commit -m "fix: Ensure APP_URL is always set correctly for invoice emails"
git push origin main
```

2. Railway will automatically detect the push and deploy

### Step 3: Verify the Deployment

After deployment, you can verify in two ways:

**Option A: Using Railway CLI**
```bash
railway run bash verify-app-url.sh
```

**Option B: Check Railway logs**
Look for these lines in the deployment logs:
```
Final APP_URL: https://app.facturino.mk
Final SESSION_DOMAIN: .facturino.mk
Final SANCTUM_STATEFUL_DOMAINS: app.facturino.mk,facturino.mk
```

### Step 4: Clear Cache (Important!)

The app may have cached the old configuration. Clear it:

**Via Railway CLI:**
```bash
railway run php artisan config:clear
railway run php artisan cache:clear
railway run php artisan route:clear
```

**Via Railway Dashboard:**
Add a temporary environment variable to force cache clear:
1. Add: `CLEAR_CACHE=true`
2. Wait for deployment
3. Remove the variable

### Step 5: Test Invoice Email

1. Log in to Facturino at https://app.facturino.mk
2. Create or select an invoice
3. Click "Send Invoice"
4. Send it to a test email address
5. Check the email received
6. Click the "View Invoice" button
7. Verify the URL is: `https://app.facturino.mk/customer/invoices/view/{token}`

## Expected Results

### Before Fix
Email contained URL like:
- `https://app.facturino.mk/admin/items/15/edit` ❌
- Or `http://localhost:8000/customer/invoices/view/{token}` ❌

### After Fix
Email should contain:
- `https://app.facturino.mk/customer/invoices/view/{token}` ✓

## Troubleshooting

### Issue: URLs still incorrect after deployment

**Check 1: Verify environment variables**
```bash
railway run bash -c "echo APP_URL: \$APP_URL"
```

**Check 2: Verify Railway domain mapping**
- Go to Railway dashboard → Settings → Domains
- Ensure `app.facturino.mk` is set as a custom domain
- Check that it's not using the Railway-generated domain

**Check 3: Check for cached config**
```bash
railway run php artisan config:cache
railway run php artisan config:clear
```

**Check 4: Review logs**
```bash
railway logs
```
Look for the "Final APP_URL" line during startup

### Issue: Emails not sending at all

This is a different issue. Check:
1. MAIL_* environment variables are set
2. Mail driver is configured correctly
3. Check Railway logs for mail errors

## Rollback Plan

If something goes wrong:

1. **Revert the changes:**
```bash
git revert HEAD
git push origin main
```

2. **Or manually fix in Railway:**
- Go to Variables
- Set `APP_URL=https://app.facturino.mk` directly
- Redeploy

## Files Modified

1. `.env.railway` - Added RAILWAY_PUBLIC_DOMAIN
2. `railway-start.sh` - Added fallback logic for APP_URL
3. `INVOICE_EMAIL_FIX.md` - Documentation of the issue
4. `verify-app-url.sh` - Verification script
5. `DEPLOYMENT_INSTRUCTIONS.md` - This file

## No Changes Needed

These files were analyzed but work correctly:
- `app/Mail/SendInvoiceMail.php`
- `app/Models/Invoice.php`
- `routes/web.php`
- `app/Http/Controllers/V1/Admin/Invoice/SendInvoiceController.php`
- `resources/views/emails/send/invoice.blade.php`

## Post-Deployment Monitoring

Monitor these metrics for 24-48 hours:
1. Check email logs for successful deliveries
2. Monitor Railway logs for any APP_URL related warnings
3. Ask users to verify invoice email links work correctly

## Support

If issues persist after following these steps:
1. Check Railway logs: `railway logs --follow`
2. Run verification script: `railway run bash verify-app-url.sh`
3. Contact the development team with logs and error messages
