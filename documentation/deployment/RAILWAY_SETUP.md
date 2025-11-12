# Railway Environment Setup

## Problem
Railway is passing DATABASE_URL into one of the DB_ variables, causing "Identifier name too long" error.

## Solution

### Step 1: Remove ALL database variables from Railway
Go to Railway project → Variables tab and **DELETE** these variables if they exist:
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `DATABASE_PRIVATE_URL`
- `DATABASE_PUBLIC_URL`
- `DB_DATABASE_URL`

**Keep only:**
- `DATABASE_URL` (provided automatically by MySQL service)

### Step 2: Add these application variables

```
APP_NAME=Facturino
APP_ENV=production
APP_DEBUG=true
APP_KEY=base64:TwsFXWYswsbp/pzhTRRX7kuM3uDNELjgIpH7ytnPv48=
APP_URL=https://web-production-5f60.up.railway.app
APP_TIMEZONE=UTC
APP_LOCALE=mk
APP_FALLBACK_LOCALE=en
CACHE_STORE=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
LOG_CHANNEL=stack
LOG_LEVEL=debug
BROADCAST_DRIVER=log
FILESYSTEM_DISK=local
SESSION_LIFETIME=120
DB_CONNECTION=mysql
```

### Step 3: Redeploy

The `railway-start.sh` script will:
1. Parse the `DATABASE_URL` automatically
2. Extract DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
3. Run migrations
4. Start the server

## How it works

Railway provides: `DATABASE_URL=mysql://root:password@mysql.railway.internal:3306/railway`

Our script parses this into:
- `DB_HOST=mysql.railway.internal`
- `DB_PORT=3306`
- `DB_DATABASE=railway`
- `DB_USERNAME=root`
- `DB_PASSWORD=password`

Then Laravel uses these individual variables correctly.
