# Railway Session Authentication Fix

## Problem
After successful login, the `/api/v1/bootstrap` endpoint returns HTTP 401 (Unauthenticated), causing users to be redirected back to the login page.

## Root Causes Identified

### 1. Bootstrap Endpoint Using Wrong Auth Guard ✅ FIXED
**File:** `routes/web.php:60`

**Before:**
```php
Route::get('/api/v1/bootstrap', BootstrapController::class)
    ->middleware(['install', 'auth', 'company', 'bouncer']);
```

**After:**
```php
Route::get('/api/v1/bootstrap', BootstrapController::class)
    ->middleware(['install', 'auth:sanctum', 'company', 'bouncer']);
```

**Status:** ✅ Fixed in commit `84af6062`

---

### 2. Missing Sanctum Stateful Domain Configuration ⚠️ REQUIRES RAILWAY CONFIG

**Problem:** Railway environment is missing critical Sanctum configuration for stateful SPA authentication.

**Required Environment Variables:**

```bash
# Set these in Railway's environment variables:

SANCTUM_STATEFUL_DOMAINS=app.facturino.mk,facturino.mk
SESSION_DOMAIN=.facturino.mk
APP_URL=https://app.facturino.mk
```

**Why These Are Needed:**

1. **`SANCTUM_STATEFUL_DOMAINS`**: Tells Laravel Sanctum which domains are allowed to make stateful (cookie-based) API requests. Without this, Sanctum rejects session cookies from `app.facturino.mk`.

2. **`SESSION_DOMAIN=.facturino.mk`**: Sets the session cookie domain to work across all `*.facturino.mk` subdomains. The leading dot is important for subdomain sharing.

3. **`APP_URL=https://app.facturino.mk`**: Ensures Laravel generates correct URLs and CSRF tokens for the production domain.

---

## How Sanctum Stateful Authentication Works

### Normal Flow (When Working):
1. User submits login form → POST `/login`
2. Laravel authenticates user and creates session
3. Server returns `Set-Cookie` header with session cookie
4. Browser stores cookie for domain `.facturino.mk`
5. Vue SPA makes GET `/api/v1/bootstrap` with `credentials: true`
6. Browser sends session cookie with request
7. `auth:sanctum` middleware validates session cookie
8. Bootstrap endpoint returns user data → Success! ✅

### Broken Flow (Current State):
1. User submits login form → POST `/login`
2. Laravel authenticates user and creates session
3. Server returns `Set-Cookie` header (but domain might be wrong)
4. Browser stores cookie
5. Vue SPA makes GET `/api/v1/bootstrap` with `credentials: true`
6. Browser sends session cookie
7. **`auth:sanctum` middleware rejects cookie** because `app.facturino.mk` is not in `SANCTUM_STATEFUL_DOMAINS` ❌
8. Returns HTTP 401 → Redirect to login

---

## Configuration Files

### `config/sanctum.php` (Fallback Values)
```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS')
    ?: 'localhost,127.0.0.1,127.0.0.1:8000,::1,app.facturino.mk'),
```

This provides a **fallback** that includes `app.facturino.mk`, but:
- The fallback only applies if `SANCTUM_STATEFUL_DOMAINS` environment variable is **completely unset**
- If Railway has `SANCTUM_STATEFUL_DOMAINS` set to a different value (like local domains), the fallback is ignored
- **Best practice:** Always set this explicitly in Railway environment variables

### `bootstrap/app.php:70` (Sanctum Enabled)
```php
$middleware->statefulApi();
```

This enables Sanctum's stateful middleware, which is correct. ✅

---

## How to Fix in Railway

### Step 1: Add Environment Variables
Go to Railway project → Variables → Add the following:

```bash
SANCTUM_STATEFUL_DOMAINS=app.facturino.mk,facturino.mk
SESSION_DOMAIN=.facturino.mk
APP_URL=https://app.facturino.mk
```

### Step 2: Redeploy
Railway should auto-redeploy when environment variables change. If not:
```bash
git push origin main
```

### Step 3: Test Authentication Flow
1. Clear browser cookies for `facturino.mk`
2. Visit `https://app.facturino.mk/admin/login`
3. Enter credentials and login
4. Check Network tab → `/api/v1/bootstrap` should return HTTP 200
5. You should be redirected to dashboard (not back to login)

### Step 4: Verify Session Cookies
In browser DevTools → Application → Cookies:
- Should see a cookie for domain `.facturino.mk`
- Cookie name will be like `laravel_session` or `XSRF-TOKEN`
- Domain should show `.facturino.mk` (with leading dot)

---

## Files Changed in This Fix

### ✅ `routes/web.php`
- Changed bootstrap endpoint from `'auth'` to `'auth:sanctum'`
- Commit: `84af6062`

### ✅ `website/src/app/health/route.ts` (Deleted)
- Removed conflicting Next.js route
- Fixed website build failure
- Commit: `b198d761`

### ✅ `.env.railway`
- Updated with correct production configuration
- Added `SANCTUM_STATEFUL_DOMAINS` and `SESSION_DOMAIN`
- This commit

---

## Reference: User's Error Log

```javascript
{
    "message": "Request failed with status code 401",
    "name": "AxiosError",
    "code": "ERR_BAD_REQUEST",
    "status": 401,
    "config": {
        "method": "get",
        "url": "/api/v1/bootstrap",
        "headers": {
            "X-Requested-With": "XMLHttpRequest",
            "company": "2"
        },
        "withCredentials": true  // ← Correct: sending cookies
    }
}
```

The request configuration is correct (`withCredentials: true`), but the server is rejecting the session cookie because the domain isn't whitelisted in `SANCTUM_STATEFUL_DOMAINS`.

---

## Testing Checklist

After applying Railway environment variables:

- [ ] Website builds successfully (no Next.js conflicts)
- [ ] Login page loads
- [ ] Login form submits successfully
- [ ] Bootstrap endpoint returns HTTP 200 (not 401)
- [ ] User is redirected to dashboard
- [ ] Session persists across page refreshes
- [ ] Logout works correctly

---

## Additional Notes

### Why Not Just Use Token-Based Auth?
- This is a **SPA (Single Page Application)** using Vue 3
- SPAs on the same domain as the API should use **stateful authentication** (session cookies)
- Token-based auth is for **mobile apps** or **third-party integrations**
- Sanctum supports both, we're using the stateful approach

### Why `.facturino.mk` Instead of `app.facturino.mk`?
- The leading dot (`.facturino.mk`) allows cookies to work across **all subdomains**:
  - `app.facturino.mk` ✅
  - `api.facturino.mk` ✅ (if needed in future)
  - `www.facturino.mk` ✅
- Without the dot, cookies only work on exact domain match
- This follows Laravel's recommendations for multi-domain setups

---

**Status:** ⚠️ **WAITING FOR RAILWAY ENVIRONMENT VARIABLE UPDATE**

**Expected Outcome:** After setting environment variables in Railway, login should work end-to-end without 401 errors.
