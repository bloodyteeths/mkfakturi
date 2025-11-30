# Quick Fix Guide: Bootstrap 401 Error

## Current Status
✅ Code fixes deployed:
- Bootstrap endpoint uses `auth:sanctum` middleware
- Website builds successfully
- Health checks passing

❌ Still failing:
- Bootstrap endpoint returns 401 after login
- Session cookies not being recognized

## The Problem
Your deploy logs show:
```
127.0.0.1 -  14/Nov/2025:17:05:58 +0000 "POST /index.php" 302  ← Login succeeds
127.0.0.1 -  14/Nov/2025:17:05:59 +0000 "GET /index.php" 401   ← Bootstrap fails
```

This means login is working, but the session cookie isn't being accepted by Sanctum.

## The Solution: Add Environment Variables in Railway

### Step 1: Go to Railway Dashboard
1. Open https://railway.app
2. Navigate to your Facturino project
3. Click on your **main Laravel service** (not the website service)
4. Go to **Variables** tab

### Step 2: Add These Variables

Click **"+ New Variable"** and add each of these:

```bash
SANCTUM_STATEFUL_DOMAINS=app.facturino.mk,facturino.mk
```

```bash
SESSION_DOMAIN=.facturino.mk
```

```bash
APP_URL=https://app.facturino.mk
```

### Step 3: Verify Current Values

Before adding, check if these variables already exist with wrong values:
- If `SANCTUM_STATEFUL_DOMAINS` exists with different domains (like `localhost`), **update it** instead of creating new
- If `APP_URL` is set to old Railway URL, **update it** to `https://app.facturino.mk`
- If `SESSION_DOMAIN` doesn't exist, **create it** with value `.facturino.mk`

### Step 4: Redeploy

Railway should auto-redeploy when you change environment variables. If not:
1. Go to **Deployments** tab
2. Click **"Redeploy"** on the latest deployment

OR just trigger a new deployment:
```bash
git commit --allow-empty -m "trigger redeploy"
git push origin main
```

### Step 5: Test Login Flow

After deployment completes:
1. **Clear browser cookies** for `facturino.mk` domain
2. Go to `https://app.facturino.mk/admin/login`
3. Enter credentials and login
4. **Check browser console** - should NOT see 401 error
5. You should be redirected to dashboard

### Step 6: Verify Session Cookie

Open browser DevTools:
1. Go to **Application** tab
2. Click **Cookies** → `https://app.facturino.mk`
3. Look for cookie named `laravel_session` or `facturino_session`
4. **Domain should be:** `.facturino.mk` (with leading dot)

If domain is `app.facturino.mk` (without dot), the `SESSION_DOMAIN` variable wasn't applied.

## Why This Happens

### How Sanctum Stateful Authentication Works:
1. User logs in → Laravel creates session
2. Server sends `Set-Cookie: laravel_session=...` header
3. Browser stores cookie for domain specified in `SESSION_DOMAIN`
4. Next request (bootstrap) → Browser sends cookie
5. **Sanctum checks:** Is the request domain in `SANCTUM_STATEFUL_DOMAINS`?
   - ✅ YES → Accept session cookie → Return user data
   - ❌ NO → Reject session cookie → Return 401

### Your Current State:
- Login creates session ✅
- Cookie is created ✅
- Cookie is sent with bootstrap request ✅
- **Sanctum rejects it because `app.facturino.mk` is NOT in `SANCTUM_STATEFUL_DOMAINS`** ❌

### After Adding Variables:
- Login creates session ✅
- Cookie domain set to `.facturino.mk` ✅
- Cookie sent with bootstrap request ✅
- **Sanctum accepts it because `app.facturino.mk` IS in `SANCTUM_STATEFUL_DOMAINS`** ✅

## Common Mistakes to Avoid

### ❌ Wrong: Including Protocol
```bash
SANCTUM_STATEFUL_DOMAINS=https://app.facturino.mk  # WRONG!
```

### ✅ Correct: Just the Domain
```bash
SANCTUM_STATEFUL_DOMAINS=app.facturino.mk,facturino.mk
```

### ❌ Wrong: No Leading Dot in SESSION_DOMAIN
```bash
SESSION_DOMAIN=facturino.mk  # WRONG - cookies won't work on subdomains
```

### ✅ Correct: Leading Dot
```bash
SESSION_DOMAIN=.facturino.mk  # CORRECT - works on all *.facturino.mk
```

## Troubleshooting

### If login still fails after adding variables:

#### 1. Check if deployment actually updated
```bash
curl -s https://app.facturino.mk/health | jq '.environment'
# Should show: "production"
```

#### 2. Check session cookie domain in browser
1. Login (even if it fails)
2. DevTools → Application → Cookies
3. Look at `laravel_session` cookie
4. Check **Domain** column - should be `.facturino.mk`

#### 3. Test with curl (simulating browser)
```bash
# Step 1: Get CSRF token
curl -c cookies.txt https://app.facturino.mk/admin/login

# Step 2: Login
curl -b cookies.txt -c cookies.txt -X POST https://app.facturino.mk/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=your@email.com&password=yourpassword"

# Step 3: Test bootstrap (should return user data, not 401)
curl -b cookies.txt https://app.facturino.mk/api/v1/bootstrap
```

If this works, but browser doesn't, it's a CORS or cookie domain issue.

#### 4. Check Railway logs for Sanctum errors
```bash
# In Railway dashboard, check logs for:
# - "Unauthenticated"
# - "CSRF token mismatch"
# - "Invalid stateful domain"
```

## Expected Timeline

After adding variables in Railway:
- **1-2 minutes:** Railway detects variable change
- **2-3 minutes:** Rebuild/redeploy starts
- **3-5 minutes:** New deployment live
- **Total: ~5 minutes** from adding variables to login working

## Need More Help?

If login still fails after following all steps:

1. Check Railway logs during login attempt
2. Check browser Network tab for:
   - Login POST response headers (should have `Set-Cookie`)
   - Bootstrap GET request headers (should have `Cookie`)
3. Share the exact error message and timestamps

## Files Changed

All code changes are already deployed:
- ✅ `routes/web.php` - Uses `auth:sanctum` for bootstrap
- ✅ `website/` - TypeScript build error fixed
- ✅ `.env.railway` - Template updated (just a reference file)

**Only thing remaining: Add environment variables in Railway dashboard**

---

**Expected outcome:** After adding the 3 environment variables and redeploying, login should work without 401 errors.
