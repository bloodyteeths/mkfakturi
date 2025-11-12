# Queue Worker Configuration Summary

## Overview

Successfully configured the queue worker system for e-invoice processing. The system uses Laravel's queue infrastructure with Redis as the queue driver to process SubmitEInvoiceJob asynchronously.

## Files Modified

### 1. config/queue.php
**Path:** `/Users/tamsar/Downloads/mkaccounting/config/queue.php`

**Changes:**
- Added `einvoice` queue connection configuration
- Set retry_after to 150 seconds (2.5 minutes)
- Configured to use Redis driver
- Added CLAUDE-CHECKPOINT comment

**Configuration:**
```php
'einvoice' => [
    'driver' => 'redis',
    'connection' => env('QUEUE_REDIS_CONNECTION', 'default'),
    'queue' => 'einvoice',
    'retry_after' => 150, // 2.5 minutes (longer than job timeout)
    'block_for' => null,
    'after_commit' => false,
],
```

### 2. .env.example
**Path:** `/Users/tamsar/Downloads/mkaccounting/.env.example`

**Changes:**
- Added queue configuration comments
- Documented sync vs redis modes
- Added Redis configuration documentation
- Noted Railway auto-configuration

**Added:**
```bash
# Queue Configuration
# Use 'sync' for local development without queue workers
# Use 'redis' for production with dedicated queue workers
QUEUE_CONNECTION=sync
# For production with Redis queue workers, change to:
# QUEUE_CONNECTION=redis

# Redis Configuration (required for queue workers)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
# For Railway, these are automatically set by the Redis service
```

## Files Created

### 1. railway-queue-worker.sh
**Path:** `/Users/tamsar/Downloads/mkaccounting/railway-queue-worker.sh`
**Permissions:** Executable (755)

**Purpose:** Production queue worker start script for Railway deployment

**Features:**
- Parses Railway database environment variables
- Sets up Redis connection
- Configures storage directories
- Starts queue worker with optimal settings:
  - Queue: einvoice
  - Tries: 3
  - Timeout: 120 seconds
  - Max jobs: 100 (prevents memory leaks)
  - Max time: 3600 seconds (1 hour)

**Usage:**
```bash
bash railway-queue-worker.sh
```

### 2. start-queue-worker.sh
**Path:** `/Users/tamsar/Downloads/mkaccounting/start-queue-worker.sh`
**Permissions:** Executable (755)

**Purpose:** Local development queue worker start script

**Features:**
- Checks if Redis is installed and running
- Validates .env configuration
- Provides helpful warnings and tips
- Starts queue worker with verbose output

**Usage:**
```bash
./start-queue-worker.sh
```

### 3. supervisor.conf
**Path:** `/Users/tamsar/Downloads/mkaccounting/supervisor.conf`

**Purpose:** Supervisor configuration for running web server and queue workers together

**Features:**
- Manages PHP built-in server
- Manages e-invoice queue worker
- Manages general queue worker for other queues
- Configures automatic restarts
- Proper process management and graceful shutdown

**Usage:**
```bash
supervisord -c supervisor.conf
```

### 4. verify-queue-setup.sh
**Path:** `/Users/tamsar/Downloads/mkaccounting/verify-queue-setup.sh`
**Permissions:** Executable (755)

**Purpose:** Verification script to check queue configuration

**Checks:**
1. Redis availability
2. Queue configuration file
3. Queue worker scripts
4. SubmitEInvoiceJob class
5. Environment configuration
6. Documentation files
7. Supervisor configuration
8. Laravel installation

**Usage:**
```bash
./verify-queue-setup.sh
```

**Output:** Reports errors and warnings with actionable recommendations

### 5. QUEUE_WORKER_SETUP.md
**Path:** `/Users/tamsar/Downloads/mkaccounting/QUEUE_WORKER_SETUP.md`

**Purpose:** Comprehensive documentation for queue worker setup

**Contents:**
- Queue configuration overview
- Local development setup
- Railway deployment options (separate service vs supervisor)
- Environment variables reference
- Testing procedures
- Troubleshooting guide
- Performance tuning
- Monitoring best practices

### 6. QUEUE_COMMANDS.md
**Path:** `/Users/tamsar/Downloads/mkaccounting/QUEUE_COMMANDS.md`

**Purpose:** Quick reference guide for common queue commands

**Contents:**
- Starting queue workers
- Monitoring queues
- Managing jobs (retry, clear, forget)
- Testing and debugging
- Redis commands
- Production monitoring scripts
- Common issues and solutions

### 7. RAILWAY_QUEUE_DEPLOYMENT.md
**Path:** `/Users/tamsar/Downloads/mkaccounting/RAILWAY_QUEUE_DEPLOYMENT.md`

**Purpose:** Step-by-step guide for deploying queue workers on Railway

**Contents:**
- Prerequisites checklist
- Redis service setup
- Main service configuration
- Queue worker service creation (two options)
- Verification procedures
- Monitoring setup
- Scaling strategies
- Troubleshooting
- Cost optimization
- Success checklist

### 8. QUEUE_SETUP_SUMMARY.md
**Path:** `/Users/tamsar/Downloads/mkaccounting/QUEUE_SETUP_SUMMARY.md`

**Purpose:** This file - summary of all changes and files

## Configuration Details

### Queue: einvoice

**Driver:** Redis
**Timeout:** 120 seconds per job
**Max Tries:** 3 attempts
**Retry After:** 150 seconds (queue-level timeout)
**Backoff:** [60, 300, 900] seconds (1 min, 5 min, 15 min)

### Job: SubmitEInvoiceJob

**Location:** `app/Jobs/SubmitEInvoiceJob.php`
**Queue:** einvoice
**Tries:** 3
**Timeout:** 120 seconds
**Backoff:** [60, 300, 900]

**Workflow:**
1. Load EInvoice model
2. Check if already submitted
3. Generate UBL XML
4. Sign XML with certificate
5. Create submission record
6. Submit to tax authority portal
7. Parse response
8. Update submission status
9. Update e-invoice status
10. Log results

## Local Development Setup

### Prerequisites
```bash
# Install Redis
brew install redis

# Start Redis
brew services start redis
```

### Update .env
```bash
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### Start Queue Worker
```bash
./start-queue-worker.sh
```

## Railway Deployment Setup

### Prerequisites
1. Railway project with main web service
2. MySQL service
3. Redis service (add via Railway UI)

### Option 1: Separate Queue Worker Service (Recommended)

1. Add Redis service to Railway project
2. Create new service from same GitHub repo
3. Name it "Queue Worker"
4. Set start command: `bash railway-queue-worker.sh`
5. Connect MySQL and Redis services
6. Copy all environment variables from main service
7. Set `QUEUE_CONNECTION=redis`
8. Deploy

### Option 2: Combined Service with Supervisor

1. Add Redis service to Railway project
2. Update main service start command: `supervisord -c supervisor.conf`
3. Set `QUEUE_CONNECTION=redis`
4. Redeploy

## Testing Queue Workers

### Dispatch Test Job
```php
php artisan tinker

$invoice = \App\Models\Invoice::first();
$eInvoice = \App\Models\EInvoice::create([
    'invoice_id' => $invoice->id,
    'company_id' => $invoice->company_id,
    'status' => 'draft',
]);

\App\Jobs\SubmitEInvoiceJob::dispatch($eInvoice->id);
Queue::size('einvoice'); // Should be 1
```

### Monitor Processing
```bash
# Watch logs
tail -f storage/logs/laravel.log | grep SubmitEInvoiceJob

# Check queue size
php artisan tinker
>>> Queue::size('einvoice');

# Check failed jobs
php artisan queue:failed
```

## Monitoring Queue Workers

### Check Queue Size
```bash
php artisan tinker
>>> Queue::size('einvoice');
```

### Check Failed Jobs
```bash
php artisan queue:failed
```

### View Job Logs
```bash
tail -f storage/logs/laravel.log | grep SubmitEInvoiceJob
```

### Retry Failed Jobs
```bash
php artisan queue:retry all
```

## Troubleshooting

### Queue Worker Not Starting
- Check Redis is running: `redis-cli ping`
- Check environment variables are set
- Check script permissions: `chmod +x *.sh`

### Jobs Not Being Processed
- Verify QUEUE_CONNECTION=redis
- Check queue name matches: 'einvoice'
- Restart queue worker

### Jobs Failing
- Check Laravel logs: `storage/logs/laravel.log`
- Check failed jobs: `php artisan queue:failed`
- Verify certificate configuration
- Check database connections

## Performance Considerations

### Queue Worker Settings

**Max Jobs:** 100
- Restarts worker after processing 100 jobs
- Prevents memory leaks

**Max Time:** 3600 seconds (1 hour)
- Restarts worker after 1 hour
- Ensures fresh worker processes

**Sleep:** 3 seconds
- When no jobs available
- Reduces CPU usage

### Scaling

**Vertical Scaling:**
- Increase memory/CPU in Railway settings
- Adjust --max-jobs and --max-time

**Horizontal Scaling:**
- Create multiple queue worker services
- All workers process from same queue
- Requires Railway Pro plan

## Environment Variables

### Required for Queue Processing

```bash
# Queue
QUEUE_CONNECTION=redis

# Redis (set by Railway Redis service)
REDIS_HOST=<set-by-railway>
REDIS_PORT=<set-by-railway>
REDIS_PASSWORD=<set-by-railway>

# Database (set by Railway MySQL service)
MYSQL_URL=<set-by-railway>
DB_CONNECTION=mysql
```

## Verification Results

Running `./verify-queue-setup.sh` shows:

✅ Checks Passed:
- E-invoice queue configured
- Queue worker scripts exist and executable
- SubmitEInvoiceJob implements ShouldQueue
- Job configured for 'einvoice' queue
- Documentation files exist
- Supervisor configuration correct
- Laravel installation working

⚠️  Warnings (Expected in some environments):
- Redis not installed locally (not required for development with sync driver)
- QUEUE_CONNECTION set to sync (default, change to redis for production)

## Next Steps

1. **For Local Development:**
   - Install Redis: `brew install redis`
   - Update .env: `QUEUE_CONNECTION=redis`
   - Start worker: `./start-queue-worker.sh`

2. **For Railway Deployment:**
   - Follow [RAILWAY_QUEUE_DEPLOYMENT.md](RAILWAY_QUEUE_DEPLOYMENT.md)
   - Add Redis service
   - Create queue worker service
   - Configure environment variables
   - Deploy and verify

3. **Testing:**
   - Dispatch test e-invoice job
   - Monitor queue worker logs
   - Verify job completion
   - Check failed jobs (should be empty)

4. **Production:**
   - Set up monitoring/alerts
   - Configure log aggregation
   - Test failure scenarios
   - Document runbook procedures

## Support Resources

- **Full Setup Guide:** [QUEUE_WORKER_SETUP.md](QUEUE_WORKER_SETUP.md)
- **Command Reference:** [QUEUE_COMMANDS.md](QUEUE_COMMANDS.md)
- **Railway Guide:** [RAILWAY_QUEUE_DEPLOYMENT.md](RAILWAY_QUEUE_DEPLOYMENT.md)
- **Laravel Docs:** https://laravel.com/docs/10.x/queues
- **Redis Docs:** https://redis.io/docs/

## Files Summary

| File | Type | Purpose |
|------|------|---------|
| config/queue.php | Modified | Added einvoice queue configuration |
| .env.example | Modified | Added queue configuration documentation |
| railway-queue-worker.sh | Created | Production queue worker script for Railway |
| start-queue-worker.sh | Created | Local development queue worker script |
| supervisor.conf | Created | Supervisor configuration for combined deployment |
| verify-queue-setup.sh | Created | Configuration verification script |
| QUEUE_WORKER_SETUP.md | Created | Comprehensive setup documentation |
| QUEUE_COMMANDS.md | Created | Quick reference for queue commands |
| RAILWAY_QUEUE_DEPLOYMENT.md | Created | Railway deployment guide |
| QUEUE_SETUP_SUMMARY.md | Created | This summary document |

## Conclusion

The queue worker system is now fully configured for e-invoice processing:

✅ Queue configuration added to Laravel
✅ Production and development scripts created
✅ Supervisor configuration for combined deployment
✅ Comprehensive documentation provided
✅ Verification script to check setup
✅ Railway deployment guide included

The system is ready for:
- Local development with sync or redis queues
- Railway deployment with dedicated queue worker service
- Combined deployment using supervisor
- Production monitoring and scaling

**Status:** Ready for deployment and testing

// CLAUDE-CHECKPOINT
