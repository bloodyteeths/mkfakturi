# Railway Debugging Guide - View Laravel Logs

## Method 1: Railway CLI (Recommended)

### Install Railway CLI (if not installed)
```bash
# macOS
brew install railway

# Or using npm
npm i -g @railway/cli
```

### Login to Railway
```bash
railway login
```

### Link to Your Project
```bash
# Run this in your project directory
railway link
```

### View Live Logs
```bash
# View all logs (real-time)
railway logs

# Follow logs (like tail -f)
railway logs --follow

# Filter by service (if you have multiple services)
railway logs --service <service-name>
```

### View Laravel Application Logs
```bash
# SSH into the container
railway shell

# Once inside the container, view Laravel logs
tail -f /var/www/html/storage/logs/laravel.log

# Or view all logs
cat /var/www/html/storage/logs/laravel.log

# Search for specific errors
grep "401\|Unauthenticated\|bootstrap" /var/www/html/storage/logs/laravel.log
```

## Method 2: Railway Dashboard

1. Go to https://railway.app
2. Select your project
3. Click on the service (your Laravel app)
4. Click "Deployments" tab
5. Click on the latest deployment
6. Click "View Logs"

## Method 3: Direct File Access via SSH

```bash
# SSH into Railway container
railway shell

# Navigate to Laravel logs directory
cd storage/logs

# List all log files
ls -lah

# View today's log
tail -100 laravel.log

# Search for authentication errors
grep -A 5 -B 5 "bootstrap\|401\|Unauthenticated" laravel.log

# Check for session errors
grep -i "session" laravel.log | tail -20
```

## Quick Debug Commands

### Check if deployment has latest code
```bash
railway shell
cd /var/www/html
git log --oneline -5
# Should show commit: dd2f9ba0
```

### Check route registration
```bash
railway shell
cd /var/www/html
php artisan route:list | grep bootstrap
# Should show: GET api/v1/bootstrap
```

### Check middleware on bootstrap route
```bash
railway shell
cd /var/www/html
php artisan route:list --path=bootstrap 2>/dev/null | grep -A 2 bootstrap
```

### Test authentication locally on Railway
```bash
railway shell
cd /var/www/html

# Test if auth:sanctum middleware exists
php artisan route:list | grep "auth:sanctum" | head -5
```

### Check session configuration
```bash
railway shell
php artisan tinker
# Then run:
config('session.driver')
config('sanctum.stateful')
exit
```

## Common Issues to Check in Logs

### 1. Session Not Persisting
Look for:
```
Session store not set on request
Session ID mismatch
```

### 2. CSRF Token Issues
Look for:
```
CSRF token mismatch
TokenMismatchException
```

### 3. Authentication Failures
Look for:
```
Unauthenticated
Auth guard [sanctum] is not defined
```

### 4. Middleware Issues
Look for:
```
Middleware not found
Call to undefined method
```

## Real-time Debugging Session

```bash
# Terminal 1: Follow Railway logs
railway logs --follow

# Terminal 2: SSH into container and watch Laravel logs
railway shell
tail -f storage/logs/laravel.log

# Terminal 3: Test login from browser
# Open https://app.facturino.mk/admin/login
# Submit credentials and watch logs in Terminal 1 and 2
```

## Export Logs for Analysis

```bash
# Download logs to local file
railway logs > railway-logs.txt

# SSH and download Laravel logs
railway shell
cat storage/logs/laravel.log > /tmp/laravel.log
exit

# Or use railway run to execute commands
railway run cat storage/logs/laravel.log > laravel-logs.txt
```

## Enable Laravel Query Logging (Temporary)

```bash
railway shell
cd /var/www/html

# Edit .env to enable query logging
echo "DB_LOGGING=true" >> .env

# Or enable via tinker
php artisan tinker
# Then run:
\DB::enableQueryLog();
exit
```

## Check Current Environment Variables

```bash
railway shell
env | grep -E "SESSION|SANCTUM|APP_URL|AUTH"
```

## Most Useful Command for This Issue

```bash
# Start watching logs in real-time, then attempt login
railway logs --follow | grep -E "bootstrap|login|401|session|sanctum|auth"
```

This will show you exactly what's happening when you try to log in.

## Alternative: Add Temporary Debug Logging

You can add temporary debug logging to the BootstrapController to see what's happening:

1. SSH into Railway:
```bash
railway shell
```

2. Add debug logging:
```bash
cd /var/www/html/app/Http/Controllers/V1/Admin/General
nano BootstrapController.php
```

3. Add at the start of the `__invoke` method:
```php
\Log::info('Bootstrap endpoint hit', [
    'user_id' => auth()->id(),
    'guard' => auth()->getDefaultDriver(),
    'authenticated' => auth()->check(),
    'session_id' => session()->getId(),
]);
```

4. Save and exit (Ctrl+X, Y, Enter)

5. Watch logs:
```bash
tail -f /var/www/html/storage/logs/laravel.log | grep Bootstrap
```

6. Try logging in from browser

This will show you if the bootstrap endpoint is even being hit and if auth is working.
