# Railway Queue Worker Deployment Guide

## Overview

This guide walks you through setting up a dedicated queue worker service on Railway for processing e-invoice submissions. The queue worker runs separately from the web service for better reliability and scalability.

## Prerequisites

1. Existing Railway project with the main Facturino web service
2. MySQL service connected to your project
3. Redis service (we'll add this)

## Step 1: Add Redis Service

1. **Open your Railway project**
   - Go to [railway.app](https://railway.app)
   - Select your Facturino project

2. **Add Redis**
   - Click "+ New Service"
   - Select "Redis" from the database options
   - Railway will automatically provision and connect Redis
   - Note: Redis will automatically set environment variables:
     - `REDIS_HOST`
     - `REDIS_PORT`
     - `REDIS_PASSWORD` (if authentication enabled)

3. **Verify Redis variables**
   - Go to the Redis service
   - Click "Variables" tab
   - You should see `REDIS_HOST`, `REDIS_PORT`, etc.

## Step 2: Update Main Web Service

1. **Add Redis connection to main service**
   - Go to your main web service
   - Click "Variables" tab
   - Click "Add Variable"
   - Add: `QUEUE_CONNECTION=redis`
   - Click "Add"

2. **Connect Redis service**
   - In your main service settings
   - Click "Connect" or "Reference" tab
   - Select your Redis service
   - This shares Redis environment variables with the web service

3. **Redeploy main service**
   - Railway will automatically redeploy
   - Verify deployment succeeds

## Step 3: Create Queue Worker Service

### Option A: Separate Queue Worker Service (Recommended)

1. **Create new service from same repo**
   - In your Railway project, click "+ New Service"
   - Select "GitHub Repo"
   - Choose the same repository as your main service
   - Name it: "Queue Worker" or "E-Invoice Worker"

2. **Configure build settings**
   - Railway will auto-detect and use the same build settings
   - Or manually set:
     - Builder: NIXPACKS
     - Build Command: `composer install --no-dev --optimize-autoloader`

3. **Set start command**
   - Go to service Settings
   - Find "Deploy" section
   - Set Start Command: `bash railway-queue-worker.sh`

4. **Connect services**
   - Click "Connect" or link services:
     - MySQL database (same as main service)
     - Redis service (same as main service)

5. **Copy environment variables from main service**

   Required variables:
   ```
   APP_KEY=<copy-from-main>
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=<your-app-url>
   QUEUE_CONNECTION=redis

   # All feature flags from main service
   FEATURE_ACCOUNTING_BACKBONE=<copy>
   FEATURE_PSD2_BANKING=<copy>
   # ... etc
   ```

6. **Add queue-specific variables**
   ```
   QUEUE_CONNECTION=redis
   ```

7. **Deploy queue worker**
   - Click "Deploy"
   - Monitor logs to ensure it starts correctly
   - Look for: "Starting E-Invoice Queue Worker"

### Option B: Combined Service with Supervisor

If you prefer running both web and queue worker in one service:

1. **Update main service start command**
   - Go to main service Settings
   - Change Start Command to: `supervisord -c supervisor.conf`

2. **Verify supervisor.conf exists**
   - File is included in repo: `supervisor.conf`
   - Contains both web server and queue worker processes

3. **Ensure QUEUE_CONNECTION is set**
   - Add variable: `QUEUE_CONNECTION=redis`

4. **Redeploy**
   - Railway will restart with supervisor managing both processes

## Step 4: Verify Queue Worker

### Check Deployment Logs

1. **View queue worker logs**
   - Go to Queue Worker service (or main service if using supervisor)
   - Click "Deployments" tab
   - Select latest deployment
   - View logs

2. **Look for success indicators**
   ```
   ✅ Redis is running
   Starting E-Invoice Queue Worker
   Queue: einvoice
   [date] Processing: App\Jobs\SubmitEInvoiceJob
   ```

3. **Check for errors**
   - Connection errors: Check Redis/MySQL connections
   - Class not found: Check composer install completed
   - Permission errors: Check file permissions

### Test Queue Processing

1. **Dispatch a test job from main service**

   SSH into main service or use Railway CLI:
   ```bash
   php artisan tinker
   ```

   Then in Tinker:
   ```php
   // Find an invoice
   $invoice = \App\Models\Invoice::first();

   // Create e-invoice
   $eInvoice = \App\Models\EInvoice::create([
       'invoice_id' => $invoice->id,
       'company_id' => $invoice->company_id,
       'status' => 'draft',
   ]);

   // Dispatch job
   \App\Jobs\SubmitEInvoiceJob::dispatch($eInvoice->id);

   // Check queue size
   Queue::size('einvoice'); // Should return 1
   ```

2. **Monitor queue worker logs**
   - Watch the Queue Worker service logs
   - You should see: "Processing: App\Jobs\SubmitEInvoiceJob"
   - Job should complete successfully

3. **Check database**
   ```php
   // In Tinker
   $submission = \App\Models\EInvoiceSubmission::latest()->first();
   $submission->status; // Should be 'accepted' or show error
   ```

## Step 5: Configure Monitoring (Optional)

### Set up health checks

1. **Create monitoring script**
   ```bash
   # Add to your repo
   php artisan tinker --execute="
   \$queueSize = Queue::size('einvoice');
   \$failedCount = DB::table('failed_jobs')->count();
   echo json_encode(['queue_size' => \$queueSize, 'failed_jobs' => \$failedCount]);
   "
   ```

2. **Use Railway metrics**
   - Railway provides CPU, memory, network metrics
   - Monitor CPU usage of queue worker
   - Set up alerts if CPU > 80%

### Enable logging

1. **Set log level**
   ```
   LOG_LEVEL=info
   LOG_CHANNEL=stack
   ```

2. **View logs in Railway**
   - Go to Queue Worker service
   - Click "Logs" tab
   - Filter by "queue" or "SubmitEInvoiceJob"

## Scaling Queue Workers

### Vertical Scaling (More Resources)

1. **Increase service resources**
   - Go to Queue Worker service Settings
   - Adjust memory/CPU allocation
   - Redeploy

### Horizontal Scaling (Multiple Workers)

1. **Create additional queue worker services**
   - Repeat Step 3 for each additional worker
   - Name them: "Queue Worker 2", "Queue Worker 3", etc.
   - All workers connect to same Redis and process from same queue

2. **Railway Pro required**
   - Multiple services from same repo requires Railway Pro
   - Alternative: Use supervisor with multiple workers (Option B)

## Troubleshooting

### Queue Worker Won't Start

**Error: "Redis connection refused"**
```
Solution:
1. Check Redis service is running in Railway
2. Verify Redis variables are connected to queue worker
3. Check REDIS_HOST, REDIS_PORT are set
```

**Error: "Class App\Jobs\SubmitEInvoiceJob not found"**
```
Solution:
1. Verify composer install completed successfully
2. Check build logs for errors
3. Ensure autoload is optimized: composer dump-autoload
```

### Jobs Not Being Processed

**Queue worker running but jobs staying in queue**
```
Solution:
1. Check queue name matches: 'einvoice'
2. Verify QUEUE_CONNECTION=redis on both services
3. Check worker logs for errors
4. Restart queue worker service
```

### Jobs Failing Repeatedly

**Check failed jobs table**
```bash
php artisan tinker
>>> DB::table('failed_jobs')->latest()->first();
```

**Common issues:**
- Certificate not found: Check certificate_path in database
- Portal timeout: Increase timeout in config
- Database connection: Check MySQL connection

### Memory Issues

**Worker consuming too much memory**
```
Solution:
1. Reduce --max-jobs in railway-queue-worker.sh
2. Reduce --max-time to restart workers more frequently
3. Increase service memory in Railway settings
4. Check for memory leaks in job code
```

## Cost Optimization

### Railway Pricing Considerations

1. **Hobby Plan ($5/month)**
   - Includes $5 credit
   - Pay for what you use beyond credit
   - Suitable for low-volume queue processing

2. **Pro Plan ($20/month)**
   - Includes $20 credit
   - Better for production workloads
   - Allows multiple services from same repo

3. **Cost Factors**
   - Redis service: ~$1-2/month
   - Queue worker service: ~$2-5/month (depending on usage)
   - Main web service: ~$5-10/month

### Reduce Costs

1. **Use single service with supervisor**
   - Saves cost of separate queue worker service
   - Slightly less reliable than separate service

2. **Scale down during off-hours**
   - Use Railway API to pause queue worker overnight
   - Resume during business hours

3. **Optimize job processing**
   - Reduce timeout if jobs complete faster
   - Batch multiple operations per job
   - Use queue priorities efficiently

## Environment Variables Reference

### Required for Queue Worker Service

```bash
# Application
APP_KEY=<copy-from-main>
APP_ENV=production
APP_DEBUG=false
APP_URL=<your-railway-url>

# Database (automatically set by Railway)
MYSQL_URL=<set-by-railway>
DB_CONNECTION=mysql

# Queue
QUEUE_CONNECTION=redis

# Redis (automatically set by Railway)
REDIS_HOST=<set-by-railway>
REDIS_PORT=<set-by-railway>
REDIS_PASSWORD=<set-by-railway>

# Feature Flags (copy from main service)
FEATURE_ACCOUNTING_BACKBONE=false
FEATURE_MIGRATION_WIZARD=false
FEATURE_PSD2_BANKING=false
FEATURE_PARTNER_PORTAL=false
FEATURE_ADVANCED_PAYMENTS=false
FEATURE_MCP_AI_TOOLS=false
FEATURE_MONITORING=false

# Certificates (if using PSD2)
NLB_MTLS_CERT_BASE64=<copy-from-main>
NLB_MTLS_KEY_BASE64=<copy-from-main>
STOPANSKA_MTLS_CERT_BASE64=<copy-from-main>
STOPANSKA_MTLS_KEY_BASE64=<copy-from-main>
```

## Success Checklist

- [ ] Redis service added to Railway project
- [ ] Main web service updated with QUEUE_CONNECTION=redis
- [ ] Queue worker service created (or supervisor configured)
- [ ] All environment variables copied to queue worker
- [ ] Services connected to Redis and MySQL
- [ ] Queue worker deployed successfully
- [ ] Test job dispatched and processed
- [ ] Logs show successful job processing
- [ ] Failed jobs table checked (should be empty)
- [ ] Monitoring/alerts configured (optional)

## Additional Resources

- [QUEUE_WORKER_SETUP.md](QUEUE_WORKER_SETUP.md) - Full queue worker documentation
- [QUEUE_COMMANDS.md](QUEUE_COMMANDS.md) - Quick reference for queue commands
- [Railway Documentation](https://docs.railway.app/)
- [Laravel Queue Documentation](https://laravel.com/docs/10.x/queues)
- [Redis Documentation](https://redis.io/docs/)

## Support

If you encounter issues:

1. Check Railway logs for both main and queue worker services
2. Review Laravel logs: `storage/logs/laravel.log`
3. Check failed jobs: `php artisan queue:failed`
4. Verify environment variables are correct
5. Test Redis connection: `redis-cli ping`

// CLAUDE-CHECKPOINT
