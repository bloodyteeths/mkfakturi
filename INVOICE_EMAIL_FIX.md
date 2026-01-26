# Invoice Email URL Fix

## Problem
Invoices sent via email contain incorrect URLs (e.g., `https://app.facturino.mk/admin/items/15/edit` instead of the customer invoice view link).

## Root Cause
The `APP_URL` environment variable is not being set correctly on Railway, causing the `route()` helper in `SendInvoiceMail.php` to generate URLs with the wrong base.

## Investigation Summary

### Files Analyzed
1. **app/Mail/SendInvoiceMail.php** - Line 48 generates the invoice URL using `route('invoice', ['email_log' => $log->token])`
2. **routes/web.php** - Line 137 defines the route correctly: `Route::get('/customer/invoices/view/{email_log:token}', ...)->name('invoice')`
3. **app/Models/Invoice.php** - Lines 596-612 contain the `send()` method
4. **railway-start.sh** - Line 183 sets APP_URL based on RAILWAY_PUBLIC_DOMAIN

### The Issue
The `railway-start.sh` script attempts to set APP_URL dynamically:
```bash
if [ ! -z "$RAILWAY_PUBLIC_DOMAIN" ]; then
    export APP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
fi
```

However, if `RAILWAY_PUBLIC_DOMAIN` is not set in Railway's environment variables, the APP_URL will not be set, causing Laravel to use a fallback or cached value.

## Solution

### Option 1: Set Environment Variables in Railway (RECOMMENDED)

Add these environment variables in Railway's dashboard:

1. **RAILWAY_PUBLIC_DOMAIN**: `app.facturino.mk`
2. **APP_URL**: `https://app.facturino.mk`
3. **SESSION_DOMAIN**: `.facturino.mk`
4. **SANCTUM_STATEFUL_DOMAINS**: `app.facturino.mk,facturino.mk`

### Option 2: Update .env.railway File

The `.env.railway` file has been updated to include:
```env
APP_URL=https://app.facturino.mk
RAILWAY_PUBLIC_DOMAIN=app.facturino.mk
```

Copy all variables from `.env.railway` to Railway's environment variables dashboard.

### Option 3: Hardcode APP_URL in railway-start.sh

Modify line 183 in `railway-start.sh`:
```bash
# Before
if [ ! -z "$RAILWAY_PUBLIC_DOMAIN" ]; then
    export APP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
fi

# After (with fallback)
if [ ! -z "$RAILWAY_PUBLIC_DOMAIN" ]; then
    export APP_URL="https://${RAILWAY_PUBLIC_DOMAIN}"
else
    export APP_URL="https://app.facturino.mk"
    export RAILWAY_PUBLIC_DOMAIN="app.facturino.mk"
fi
```

## Deployment Steps

1. **Verify Environment Variables in Railway**
   - Go to Railway dashboard > Your Project > Settings > Variables
   - Ensure these are set:
     - `APP_URL=https://app.facturino.mk`
     - `RAILWAY_PUBLIC_DOMAIN=app.facturino.mk`

2. **Clear Cache**
   - SSH into Railway or use the Railway CLI
   - Run: `php artisan config:clear`
   - Run: `php artisan cache:clear`
   - Run: `php artisan route:clear`

3. **Verify the Fix**
   - Send a test invoice
   - Check the email received
   - Click the "View Invoice" button
   - Verify the URL is: `https://app.facturino.mk/customer/invoices/view/{token}`

## Testing

### Test Command
```bash
# Check current APP_URL value
php artisan tinker --execute="echo config('app.url');"

# Generate a test route URL
php artisan tinker --execute="echo route('invoice', ['email_log' => 'test']);"
```

Expected output:
```
https://app.facturino.mk
https://app.facturino.mk/customer/invoices/view/test
```

## Files Modified
- `.env.railway` - Added RAILWAY_PUBLIC_DOMAIN

## Related Files (No Changes Needed)
- `app/Mail/SendInvoiceMail.php` - Email generation (working correctly)
- `routes/web.php` - Route definition (working correctly)
- `app/Models/Invoice.php` - Send method (working correctly)
- `railway-start.sh` - Startup script (working correctly with proper env vars)

## Prevention
To prevent this issue in the future:
1. Always verify environment variables are set in Railway before deployment
2. Add validation in railway-start.sh to fail early if critical variables are missing
3. Consider adding a health check endpoint that verifies APP_URL is set correctly
