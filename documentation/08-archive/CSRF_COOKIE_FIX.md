# CSRF Cookie 404 Fix

## Problem

Partner login (and all logins) were failing with a 404 error:
```
Failed to load resource: the server responded with a status of 404 ()
api/v1/sanctum/csrf-cookie:1
```

## Root Cause

The frontend was trying to access `/api/v1/sanctum/csrf-cookie` but Laravel Sanctum registers the CSRF cookie endpoint at `/sanctum/csrf-cookie` (without the `/api/v1` prefix).

This happened because:
1. Axios is configured with `baseURL = '/api/v1'` in `resources/scripts/plugins/axios.js:7`
2. Auth stores were calling `axios.get('/sanctum/csrf-cookie')`
3. Axios was combining these to make a request to `/api/v1/sanctum/csrf-cookie` (which doesn't exist)

## Solution

Updated all auth stores to use absolute URLs for the CSRF cookie request, bypassing the axios baseURL:

```javascript
// Before:
axios.get('/sanctum/csrf-cookie')

// After:
axios.get(window.location.origin + '/sanctum/csrf-cookie')
```

## Files Changed

1. **`resources/scripts/admin/stores/auth.js:26`**
   - Fixed admin login CSRF cookie request

2. **`resources/scripts/customer/stores/auth.js:24`**
   - Fixed customer login CSRF cookie request

3. **`resources/scripts/admin/stores/installation.js:172`**
   - Fixed installation login CSRF cookie request

4. **Frontend assets rebuilt**
   - `npm run build` executed to include the fixes

## Testing

After deploying the updated build:

1. Navigate to your Railway app URL
2. Go to login page
3. Try logging in with partner credentials:
   - Email: `partner@demo.mk`
   - Password: `Partner2025!`
4. Login should now work without 404 errors

## Verification

Check browser DevTools Network tab - you should see:
- ✅ `GET /sanctum/csrf-cookie` → 204 No Content (success)
- ✅ `POST /api/v1/auth/login` → 200 OK

## Why This Approach

Alternative solutions considered:
1. ❌ Remove axios baseURL - would break all API calls
2. ❌ Add route `/api/v1/sanctum/csrf-cookie` - duplicates functionality
3. ✅ **Use absolute URL for CSRF only** - minimal change, surgical fix

This approach ensures the CSRF cookie request goes to the correct Sanctum endpoint while keeping all other API calls working with the `/api/v1` prefix.
