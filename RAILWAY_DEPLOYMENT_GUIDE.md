# Railway Deployment Guide: AC-08→AC-18 + FIX PATCH #5

## Pre-Deployment Checklist

### 1. Environment Variables (Railway Dashboard)

Ensure these environment variables are set in Railway:

#### Core Laravel Settings
- `APP_ENV=production`
- `APP_KEY=<your-app-key>` (generate with `php artisan key:generate`)
- `APP_DEBUG=false`
- `APP_URL=<your-railway-domain>`

#### Database
- `DB_CONNECTION=mysql` (or your database type)
- `DB_HOST=<railway-database-host>`
- `DB_PORT=3306`
- `DB_DATABASE=<database-name>`
- `DB_USERNAME=<database-user>`
- `DB_PASSWORD=<database-password>`

#### Cache & Session
- `CACHE_DRIVER=redis` (recommended for production)
- `SESSION_DRIVER=redis`
- `REDIS_HOST=<railway-redis-host>`
- `REDIS_PASSWORD=<redis-password>`
- `REDIS_PORT=6379`

#### Queue
- `QUEUE_CONNECTION=redis` (for background jobs)

#### Mail (for partner invitations)
- `MAIL_MAILER=smtp`
- `MAIL_HOST=<smtp-host>`
- `MAIL_PORT=587`
- `MAIL_USERNAME=<smtp-username>`
- `MAIL_PASSWORD=<smtp-password>`
- `MAIL_ENCRYPTION=tls`
- `MAIL_FROM_ADDRESS=noreply@yourdomain.com`
- `MAIL_FROM_NAME="${APP_NAME}"`

#### Partner System (Optional Custom Config)
- `AFFILIATE_DIRECT_RATE=0.22` (22% for first year)
- `AFFILIATE_DIRECT_RATE_YEAR2=0.20` (20% after first year)
- `AFFILIATE_UPLINE_RATE=0.05` (5% upline commission)
- `AFFILIATE_SALES_REP_RATE=0.05` (5% sales rep commission)

---

## Deployment Steps

### Step 1: Push to Git
```bash
cd /Users/tamsar/Downloads/mkaccounting
git add .
git commit -m "Deploy AC-08→AC-18 + FIX PATCH #5 to Railway"
git push origin main
```

### Step 2: Railway Auto-Deploy
Railway will automatically:
1. Detect new commit on `main` branch
2. Build Docker image using `Dockerfile.mkaccounting`
3. Run entrypoint script (`/entrypoint.sh`)
4. Start application

### Step 3: Manual Migration (If Auto-Migrate Disabled)
If migrations don't run automatically:
```bash
# In Railway CLI or dashboard shell
php artisan migrate --force
```

### Step 4: Clear Caches
```bash
# In Railway CLI
railway run php artisan optimize:clear
railway run php artisan config:cache
railway run php artisan route:cache
railway run php artisan view:cache
```

---

## Post-Deployment Healthchecks

### 1. Application Health
```bash
# Check if app is running
curl https://your-app.railway.app/health

# Expected: 200 OK
```

### 2. Database Connection
```bash
railway run php artisan tinker --execute="DB::connection()->getPdo(); echo 'Database connected!';"
```

### 3. Partner Management Routes
```bash
# Test partner routes (requires authentication token)
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://your-app.railway.app/api/v1/admin/partners

# Expected: 200 OK with JSON response
```

### 4. Commission Service
```bash
# Run commission service smoke test
railway run php artisan tinker --execute="
  \$service = app(\App\Services\CommissionService::class);
  \$partner = \App\Models\Partner::first();
  if (\$partner) {
    \$rate = \$service->calculateCommissionRate(\$partner);
    echo 'Commission rate calculated: ' . \$rate . PHP_EOL;
  } else {
    echo 'No partners found in database' . PHP_EOL;
  }
"
```

### 5. Partner Referrals Table
```bash
# Verify partner_referrals table exists
railway run php artisan tinker --execute="
  \$exists = Schema::hasTable('partner_referrals');
  echo \$exists ? 'partner_referrals table exists' : 'ERROR: table missing';
  echo PHP_EOL;
"
```

---

## Smoke Test Scripts

### Test 1: Create Partner via API
```bash
curl -X POST https://your-app.railway.app/api/v1/admin/partners \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Partner",
    "email": "test@partner.com",
    "company_name": "Test Co",
    "phone": "+38970123456"
  }'

# Expected: 201 Created with partner JSON
```

### Test 2: Generate Partner Invitation
```bash
curl -X POST https://your-app.railway.app/api/v1/invitations/partner-to-partner \
  -H "Authorization: Bearer PARTNER_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "invitee_email": "newpartner@example.com"
  }'

# Expected: 200 OK with signup_link containing referral_token
```

### Test 3: Network Graph Pagination
```bash
curl -X GET "https://your-app.railway.app/api/v1/referral-network/graph?page=1&limit=10" \
  -H "Authorization: Bearer SUPER_ADMIN_TOKEN"

# Expected: 200 OK with nodes, edges, meta
```

---

## Troubleshooting

### Issue: Migrations Not Running
```bash
# Manually run migrations
railway run php artisan migrate --force

# Check migration status
railway run php artisan migrate:status
```

### Issue: 500 Error After Deployment
```bash
# Check logs
railway logs

# Clear all caches
railway run php artisan optimize:clear

# Rebuild config cache
railway run php artisan config:cache
```

### Issue: Partner Routes Return 404
```bash
# Verify routes are registered
railway run php artisan route:list | grep partners

# Clear route cache
railway run php artisan route:clear
railway run php artisan route:cache
```

### Issue: Commission Calculation Errors
```bash
# Check partner_referrals table
railway run php artisan tinker --execute="
  echo 'partner_referrals count: ' . DB::table('partner_referrals')->count() . PHP_EOL;
"

# Verify FIX PATCH #5 code deployed
railway run php artisan tinker --execute="
  \$code = file_get_contents(base_path('app/Services/CommissionService.php'));
  echo strpos(\$code, 'partner_referrals') !== false ? 'FIX PATCH #5 deployed' : 'ERROR: FIX PATCH #5 missing';
  echo PHP_EOL;
"
```

---

## Rollback Procedure

If deployment fails:

```bash
# 1. Revert to previous Railway deployment
# In Railway dashboard: Deployments → Select previous successful deployment → Redeploy

# 2. Or rollback via Git
git revert HEAD
git push origin main
# Railway will auto-deploy the reverted code

# 3. Database rollback (if migrations were run)
railway run php artisan migrate:rollback --step=1
```

---

## Monitoring After Deployment

### Watch Logs in Real-Time
```bash
railway logs --follow
```

### Check Queue Jobs
```bash
railway run php artisan queue:failed

# Retry failed jobs
railway run php artisan queue:retry all
```

### Monitor Commission Events
```bash
railway run php artisan tinker --execute="
  \$events = \App\Models\AffiliateEvent::latest()->limit(10)->get(['id', 'affiliate_partner_id', 'amount', 'created_at']);
  print_r(\$events->toArray());
"
```

---

## Success Criteria

✅ Application responds to HTTP requests
✅ `/health` endpoint returns 200
✅ Database connection successful
✅ All 8 partner-related migrations executed
✅ `partner_referrals` table exists
✅ Partner CRUD endpoints work
✅ Commission service calculates rates correctly
✅ No errors in Railway logs

---

## Emergency Contacts

**Technical Lead**: _____________________
**DevOps**: _____________________
**Railway Support**: https://railway.app/help

---

**Last Updated**: 2025-11-18
**Commit**: e752e94d (FIX PATCH #5 + System Verification Report)
