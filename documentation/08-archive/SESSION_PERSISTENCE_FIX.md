# Session Persistence Issue on Railway

## Problem Identified
Login succeeds (HTTP 302) but bootstrap fails (HTTP 401) immediately after.

Deploy logs show:
```
127.0.0.1 -  14/Nov/2025:17:05:58 +0000 "POST /index.php" 302  ✅ Login works
127.0.0.1 -  14/Nov/2025:17:05:59 +0000 "GET /index.php" 401   ❌ Session lost
```

## Root Cause
**Session storage is not persisting between requests in Railway container.**

### Your Current Configuration:
```env
SESSION_DRIVER="file"
```

### Why This Fails on Railway:
1. **File-based sessions** store session data in `storage/framework/sessions/`
2. **Railway containers** may not have persistent filesystem storage
3. **Each request** might hit a different container instance
4. **Session files** created in one request aren't available in the next

## The Solution

Change session driver from `file` to `database` in Railway.

### Step 1: Add This Variable in Railway

Go to Railway → Variables → Add:

```env
SESSION_DRIVER=database
```

### Step 2: Verify Sessions Table Exists

The sessions table should already exist (created during installation). To verify:

```bash
# Check if table exists in Railway MySQL
# In Railway dashboard -> MySQL service -> Connect -> Run:
SHOW TABLES LIKE 'sessions';
```

If it doesn't exist, you'll need to run:

```bash
php artisan session:table
php artisan migrate
```

### Step 3: Redeploy

Railway should auto-redeploy. If not:
```bash
git commit --allow-empty -m "trigger redeploy for session fix"
git push origin main
```

## Why Database Sessions Work Better

### File Sessions (Current - Broken):
```
Request 1 (Login):
Container A → Creates session file in /storage/sessions/abc123
                ↓
Response: Set-Cookie: laravel_session=abc123

Request 2 (Bootstrap):
Load Balancer → Routes to Container B
                Container B → Looks for /storage/sessions/abc123
                              ❌ File doesn't exist (it's in Container A)
                              ❌ Returns 401 Unauthenticated
```

### Database Sessions (Fix):
```
Request 1 (Login):
Container A → Writes session to MySQL database
                ↓
Response: Set-Cookie: laravel_session=abc123

Request 2 (Bootstrap):
Load Balancer → Routes to Container B
                Container B → Reads session from MySQL database
                              ✅ Session found
                              ✅ Returns user data
```

## Alternative: Redis Sessions (Better for Scale)

If you plan to scale beyond 1 container:

### Option A: Use Railway Redis Service

1. Add Redis service in Railway
2. Add these variables:
```env
SESSION_DRIVER=redis
REDIS_HOST=${{Redis.REDIS_HOST}}
REDIS_PASSWORD=${{Redis.REDIS_PASSWORD}}
REDIS_PORT=${{Redis.REDIS_PORT}}
CACHE_DRIVER=redis
```

### Option B: Use External Redis (Upstash)

1. Sign up at https://upstash.com (free tier available)
2. Create Redis database
3. Add variables:
```env
SESSION_DRIVER=redis
REDIS_HOST=your-redis.upstash.io
REDIS_PASSWORD=your-redis-password
REDIS_PORT=6379
REDIS_TLS=true
```

## Testing After Fix

### 1. Clear Browser Cookies
Delete all cookies for `facturino.mk` domain

### 2. Test Login Flow
1. Go to `https://app.facturino.mk/admin/login`
2. Open DevTools → Network tab
3. Submit login credentials
4. Watch for `/api/v1/bootstrap` request
5. Should return HTTP 200 (not 401)

### 3. Verify Session Persistence

Test with curl:
```bash
# Step 1: Login and save cookies
curl -c cookies.txt -X POST https://app.facturino.mk/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "email=your@email.com&password=yourpass"

# Step 2: Use same cookies for bootstrap (should work now)
curl -b cookies.txt https://app.facturino.mk/api/v1/bootstrap

# Expected: JSON with user data (not 401 error)
```

### 4. Check Database Sessions

After login, check if session was created in database:

```sql
-- In Railway MySQL console:
SELECT id, user_id, ip_address, last_activity
FROM sessions
ORDER BY last_activity DESC
LIMIT 5;
```

You should see a session record created within the last minute.

## Expected Timeline

After changing to `SESSION_DRIVER=database`:
- **Immediate:** Railway detects variable change
- **2-3 minutes:** Redeploy completes
- **Testing:** Login should work end-to-end

## If It Still Fails

### Check 1: Verify SESSION_DRIVER Was Applied

```bash
# In Railway logs, look for:
# "Session driver: database" or similar startup message
```

### Check 2: Check Sessions Table Schema

```sql
-- Sessions table should have these columns:
DESCRIBE sessions;

-- Expected output:
-- id (varchar)
-- user_id (bigint, nullable)
-- ip_address (varchar, nullable)
-- user_agent (text, nullable)
-- payload (longtext)
-- last_activity (int)
```

### Check 3: Check for Database Connection Issues

```bash
# In Railway logs during login attempt, look for:
# - "SQLSTATE" errors
# - "Connection refused"
# - "Too many connections"
```

## Why This Wasn't Caught Earlier

- **Local development** uses `SESSION_DRIVER=file` and works fine (single instance)
- **Railway production** likely runs multiple container instances for reliability
- **Load balancing** between instances means session files don't persist

## Recommended Final Configuration

```env
# Primary fix (immediate):
SESSION_DRIVER=database

# Better long-term (when you add Redis):
SESSION_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

## Files to Update (Optional)

If you want to make `database` the default for all environments, update `.env.railway`:

```bash
# In .env.railway, change:
SESSION_DRIVER=database  # was: file
```

This is just documentation - the actual fix is changing the variable in Railway dashboard.

---

**Status:** ⚠️ **ACTION REQUIRED**

**Next Step:** Add `SESSION_DRIVER=database` in Railway environment variables

**Expected Outcome:** Login will persist session and bootstrap will return HTTP 200
