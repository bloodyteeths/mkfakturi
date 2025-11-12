# Queue Worker Setup for E-Invoice Processing

## Overview

The e-invoice submission system uses Laravel's queue system to process invoice submissions asynchronously. This ensures that invoice submissions don't block the user interface and can be retried on failure.

## Queue Configuration

### Queue Name: `einvoice`

- **Driver**: Redis (recommended for production)
- **Timeout**: 120 seconds per job
- **Max Tries**: 3 attempts
- **Retry Backoff**: [60, 300, 900] seconds (1 min, 5 min, 15 min)
- **Retry After**: 150 seconds (queue-level timeout)

### Configuration Files

**config/queue.php**
- Added `einvoice` queue connection with Redis driver
- Configured with appropriate timeout and retry settings

## Local Development

### Prerequisites

For local development, you need Redis running:

```bash
# Install Redis (macOS)
brew install redis

# Start Redis
brew services start redis

# Or run Redis in foreground
redis-server
```

### Update .env for Queue Processing

```bash
# Change from sync to redis
QUEUE_CONNECTION=redis

# Redis configuration (defaults are fine for local)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
```

### Start Queue Worker Locally

```bash
# Start the einvoice queue worker
php artisan queue:work redis --queue=einvoice --tries=3 --timeout=120 --verbose

# Or use the Railway script (works locally too)
bash railway-queue-worker.sh
```

### Monitor Queue Jobs Locally

```bash
# Check failed jobs
php artisan queue:failed

# Retry a specific failed job
php artisan queue:retry <job-id>

# Retry all failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush

# Monitor queue in real-time with Laravel Tinker
php artisan tinker
>>> \Illuminate\Support\Facades\Queue::size('einvoice');
```

## Railway Deployment

### Prerequisites

Railway requires a **Redis service** to run queue workers:

1. Go to your Railway project
2. Click "+ New Service"
3. Select "Redis"
4. Railway will automatically set `REDIS_HOST`, `REDIS_PORT`, etc.

### Option 1: Separate Queue Worker Service (Recommended)

Create a dedicated service for the queue worker:

1. **Create New Service in Railway**
   - Click "+ New Service"
   - Choose "GitHub Repo" (same repo as main app)
   - Name it "Queue Worker" or "E-Invoice Worker"

2. **Configure Service Variables**
   ```
   START_COMMAND=bash railway-queue-worker.sh
   ```

3. **Add Railway Service Configuration**

   Create `railway.queue-worker.json`:
   ```json
   {
     "$schema": "https://railway.app/railway.schema.json",
     "build": {
       "builder": "NIXPACKS",
       "buildCommand": "composer install --no-dev --optimize-autoloader"
     },
     "deploy": {
       "startCommand": "bash railway-queue-worker.sh",
       "restartPolicyType": "ON_FAILURE",
       "restartPolicyMaxRetries": 10
     }
   }
   ```

4. **Link Required Services**
   - Link MySQL database (same as main app)
   - Link Redis service (same as main app)
   - Copy all environment variables from main app

### Option 2: Combined Service with Supervisor

If you prefer running the queue worker alongside the web service:

1. **Install Supervisor** (add to nixpacks.toml or Dockerfile):
   ```toml
   [phases.setup]
   aptPkgs = ["supervisor"]
   ```

2. **Create Supervisor Config** (`supervisor.conf`):
   ```ini
   [supervisord]
   nodaemon=true
   logfile=/dev/null
   logfile_maxbytes=0
   pidfile=/tmp/supervisord.pid

   [program:php-server]
   command=php -S 0.0.0.0:%(ENV_PORT)s -t public
   stdout_logfile=/dev/stdout
   stdout_logfile_maxbytes=0
   stderr_logfile=/dev/stderr
   stderr_logfile_maxbytes=0
   autorestart=true

   [program:queue-worker]
   command=php artisan queue:work redis --queue=einvoice --tries=3 --timeout=120 --sleep=3 --max-jobs=100 --max-time=3600
   stdout_logfile=/dev/stdout
   stdout_logfile_maxbytes=0
   stderr_logfile=/dev/stderr
   stderr_logfile_maxbytes=0
   autorestart=true
   stopwaitsecs=130
   ```

3. **Update Start Command**:
   ```bash
   # In railway-start.sh, replace the final php -S command with:
   supervisord -c supervisor.conf
   ```

### Environment Variables for Railway

Ensure these are set in your Railway environment:

```bash
# Queue Configuration
QUEUE_CONNECTION=redis

# Redis (automatically set by Railway Redis service)
REDIS_HOST=<set-by-railway>
REDIS_PORT=<set-by-railway>
REDIS_PASSWORD=<set-by-railway>

# Database (same as main app)
MYSQL_URL=<set-by-railway>
# or individual vars...
DB_HOST=<set-by-railway>
DB_PORT=<set-by-railway>
DB_DATABASE=<set-by-railway>
DB_USERNAME=<set-by-railway>
DB_PASSWORD=<set-by-railway>
```

## Testing Queue Workers

### Test E-Invoice Submission

```php
// In Laravel Tinker or a test script
php artisan tinker

// Dispatch a test job
$invoice = \App\Models\Invoice::first();
$eInvoice = \App\Models\EInvoice::create([
    'invoice_id' => $invoice->id,
    'company_id' => $invoice->company_id,
    'status' => 'draft',
]);

\App\Jobs\SubmitEInvoiceJob::dispatch($eInvoice->id, auth()->id());

// Check queue
\Illuminate\Support\Facades\Queue::size('einvoice');
```

### Monitor Job Processing

```bash
# Watch the queue worker logs
tail -f storage/logs/laravel.log

# Or in Railway, view the Queue Worker service logs
```

## Troubleshooting

### Queue Worker Not Processing Jobs

1. **Check Redis Connection**
   ```bash
   php artisan tinker
   >>> Redis::ping();
   # Should return "PONG"
   ```

2. **Check Queue Size**
   ```bash
   php artisan queue:work redis --queue=einvoice --once --verbose
   # Process one job to see errors
   ```

3. **Check Failed Jobs**
   ```bash
   php artisan queue:failed
   # List all failed jobs with errors
   ```

### Job Failing Repeatedly

1. **Check Job Logs**
   ```bash
   tail -f storage/logs/laravel.log | grep SubmitEInvoiceJob
   ```

2. **Check E-Invoice Submission Records**
   ```sql
   SELECT * FROM e_invoice_submissions
   WHERE status = 'error'
   ORDER BY created_at DESC
   LIMIT 10;
   ```

3. **Verify Certificate Configuration**
   - Ensure company has an active certificate
   - Check certificate is not expired
   - Verify certificate file path is accessible

### Redis Connection Issues

1. **Check Redis Service Status** (Railway)
   - Go to Railway Redis service
   - Check "Deployments" tab for errors
   - Verify service is running

2. **Check Redis Environment Variables**
   ```bash
   echo $REDIS_HOST
   echo $REDIS_PORT
   ```

3. **Test Redis Connection**
   ```bash
   php artisan tinker
   >>> \Illuminate\Support\Facades\Redis::connection()->ping();
   ```

## Performance Tuning

### Adjust Worker Count

For high-volume processing, run multiple workers:

```bash
# Run 3 workers for einvoice queue
php artisan queue:work redis --queue=einvoice --tries=3 --timeout=120 &
php artisan queue:work redis --queue=einvoice --tries=3 --timeout=120 &
php artisan queue:work redis --queue=einvoice --tries=3 --timeout=120 &
```

Or use Supervisor to manage multiple workers:

```ini
[program:queue-worker]
command=php artisan queue:work redis --queue=einvoice --tries=3 --timeout=120
process_name=%(program_name)s_%(process_num)02d
numprocs=3
autorestart=true
```

### Adjust Job Timeout

If jobs are timing out, increase the timeout:

```bash
# In railway-queue-worker.sh or command
php artisan queue:work redis --queue=einvoice --tries=3 --timeout=180
```

And update the queue configuration in `config/queue.php`:

```php
'einvoice' => [
    'retry_after' => 210, // 3.5 minutes (longer than job timeout)
    // ...
],
```

## Monitoring Best Practices

1. **Set up Failed Job Alerts**
   - Monitor `failed_jobs` table
   - Set up alerts when count increases

2. **Monitor Queue Size**
   - Track `Queue::size('einvoice')` metric
   - Alert if queue grows too large

3. **Check Worker Health**
   - Ensure workers restart after failures
   - Monitor worker memory usage
   - Restart workers periodically (--max-time flag)

4. **Review Job Logs**
   - Regularly check logs for warnings
   - Monitor submission success rate
   - Track average job processing time

## Additional Commands

```bash
# List all queues
php artisan queue:monitor

# Clear specific queue
php artisan queue:clear redis --queue=einvoice

# Pause queue processing
php artisan queue:pause

# Resume queue processing
php artisan queue:resume

# Restart all queue workers gracefully
php artisan queue:restart
```

## Files Modified/Created

1. **config/queue.php** - Added `einvoice` queue configuration
2. **railway-queue-worker.sh** - Queue worker start script for Railway
3. **QUEUE_WORKER_SETUP.md** - This documentation file

## Related Documentation

- [Laravel Queue Documentation](https://laravel.com/docs/10.x/queues)
- [Railway Redis Service](https://docs.railway.app/databases/redis)
- [Supervisor Documentation](http://supervisord.org/)

// CLAUDE-CHECKPOINT
