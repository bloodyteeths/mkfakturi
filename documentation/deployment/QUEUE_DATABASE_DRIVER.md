# Using Database Queue Driver (No Redis Required)

## Why Use Database Queue?

**Advantages over Redis:**
- ✅ No additional service needed (uses existing MySQL)
- ✅ No extra cost on Railway
- ✅ Simpler setup - one less thing to manage
- ✅ Jobs persist in database (survives restarts)
- ✅ Perfect performance for e-invoice volume (100s of jobs/day)
- ✅ All retry logic works exactly the same
- ✅ Same monitoring commands work

**When Redis is better:**
- High volume (1000s of jobs per minute)
- Sub-second job latency required
- Multiple apps sharing same queue

**For Facturino:** Database queue is recommended. E-invoice submissions are not high-volume, and a few seconds of latency is perfectly acceptable.

---

## Setup (Database Queue Driver)

### 1. Update Environment Variable

**In `.env` file:**
```bash
# Change from:
QUEUE_CONNECTION=redis

# To:
QUEUE_CONNECTION=database
```

**On Railway:**
- Go to your service → Variables
- Set `QUEUE_CONNECTION=database`
- No need to add Redis service!

---

### 2. Ensure Migrations Are Run

The jobs table migration already exists at:
`database/migrations/2024_07_17_113702_create_jobs_table.php`

**Run migration:**
```bash
php artisan migrate
```

This creates 3 tables:
- `jobs` - Pending jobs
- `job_batches` - Batch tracking
- `failed_jobs` - Failed jobs for retry

---

### 3. Start Queue Worker

**Local Development:**
```bash
php artisan queue:work --queue=einvoice,default --tries=3 --timeout=120
```

**Railway (Option 1 - Separate Service):**
1. Create new service from same repo: "Queue Worker"
2. Set start command:
   ```bash
   php artisan queue:work --queue=einvoice,default --tries=3 --timeout=120
   ```
3. Connect to same MySQL database as main app
4. Copy environment variables from main service
5. Set `QUEUE_CONNECTION=database`

**Railway (Option 2 - Supervisor):**
1. Update `supervisor.conf`:
   ```ini
   [program:queue-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /app/artisan queue:work database --queue=einvoice,default --tries=3 --timeout=120
   autostart=true
   autorestart=true
   numprocs=1
   redirect_stderr=true
   stdout_logfile=/app/storage/logs/worker.log
   stopwaitsecs=120
   ```

2. Set start command: `supervisord -c supervisor.conf`

---

## Configuration

The database queue is already configured in `config/queue.php`:

```php
'connections' => [
    // ...

    'database' => [
        'driver' => 'database',
        'table' => 'jobs',
        'queue' => 'default',
        'retry_after' => 150,  // 30 seconds longer than job timeout
        'after_commit' => false,
    ],
],
```

No changes needed!

---

## How It Works

### Job Submission

When you dispatch a job:
```php
SubmitEInvoiceJob::dispatch($invoiceId);
```

**What happens:**
1. Job serialized to JSON
2. Inserted into `jobs` table with `queue='einvoice'`
3. Returns immediately (non-blocking)

### Job Processing

Queue worker polls database every second:
```sql
SELECT * FROM jobs
WHERE queue = 'einvoice'
  AND available_at <= NOW()
ORDER BY id ASC
LIMIT 1
FOR UPDATE SKIP LOCKED
```

**What happens:**
1. Worker fetches next available job
2. Locks row (prevents duplicate processing)
3. Executes job
4. Deletes row on success
5. Moves to `failed_jobs` on failure (if max attempts reached)

### Retry Logic

Same exponential backoff as Redis:
- Attempt 1: Immediate
- Attempt 2: After 60 seconds
- Attempt 3: After 300 seconds (5 min)
- Attempt 4: After 900 seconds (15 min)

Failed jobs automatically retry up to 3 times.

---

## Monitoring

All the same commands work:

**Check queue size:**
```bash
php artisan tinker
>>> DB::table('jobs')->where('queue', 'einvoice')->count();
```

**Check failed jobs:**
```bash
php artisan queue:failed
```

**Check oldest job:**
```bash
php artisan tinker
>>> DB::table('jobs')->where('queue', 'einvoice')->orderBy('id')->first();
```

**Retry all failed jobs:**
```bash
php artisan queue:retry all
```

**Clear all jobs:**
```bash
php artisan queue:clear database
```

---

## Performance Comparison

### Database Queue
- **Throughput:** 50-100 jobs/second (more than enough for e-invoices)
- **Latency:** 1-2 seconds from dispatch to processing
- **Cost:** $0 (uses existing MySQL)
- **Maintenance:** Zero additional services

### Redis Queue
- **Throughput:** 1000s of jobs/second
- **Latency:** <100ms from dispatch to processing
- **Cost:** ~$5-10/month on Railway for small instance
- **Maintenance:** Additional service to monitor

**For Facturino volume (estimate: 100-500 e-invoices per day):**
- Database queue can handle this easily
- Redis would be overkill

---

## Migration Path (If You Ever Need Redis)

If your volume grows and you need Redis later:

1. **Add Redis to Railway**
2. **Update `.env`:**
   ```bash
   QUEUE_CONNECTION=redis
   REDIS_HOST=your-railway-redis-host
   REDIS_PASSWORD=your-redis-password
   REDIS_PORT=6379
   ```
3. **Restart queue workers**

That's it! Jobs automatically route to Redis. The database queue stays as a fallback.

---

## Troubleshooting

### Jobs Not Processing

**Check worker is running:**
```bash
# On Railway
railway run ps aux | grep queue:work

# Locally
ps aux | grep queue:work
```

**Check jobs table:**
```bash
php artisan tinker
>>> DB::table('jobs')->get();
```

If jobs exist but aren't processing:
- Worker might be stopped
- Worker might be listening to wrong queue
- Job timeout might be too short

**Restart worker:**
```bash
# Find process ID
ps aux | grep queue:work

# Kill
kill <PID>

# Restart
php artisan queue:work --queue=einvoice,default --tries=3 --timeout=120
```

### Jobs Failing Immediately

**Check failed_jobs table:**
```bash
php artisan queue:failed
```

**View exception:**
```bash
php artisan queue:failed --id=<job-id>
```

Common causes:
- Missing environment variables
- Database connection issues
- Timeout too short (increase to 180s if needed)

**Retry specific failed job:**
```bash
php artisan queue:retry <job-id>
```

### Database Lock Timeouts

If you see "Lock wait timeout exceeded":

**Solution 1: Increase MySQL timeout**
```sql
SET GLOBAL innodb_lock_wait_timeout = 120;
```

**Solution 2: Reduce worker count**
```bash
# Instead of 2 workers, use 1
php artisan queue:work --queue=einvoice,default
```

---

## Best Practices

### 1. Monitor Failed Jobs

Set up daily Slack notification:
```bash
# In Laravel scheduler (app/Console/Kernel.php)
$schedule->command('queue:failed-jobs-notify')->daily();
```

### 2. Clean Up Old Jobs

Jobs are automatically deleted on success. But clean up old failed jobs:
```bash
# Delete failed jobs older than 7 days
php artisan queue:prune-failed --hours=168
```

### 3. Index Jobs Table

If queue gets slow with high volume, add index:
```sql
ALTER TABLE jobs ADD INDEX idx_queue_available (queue, available_at);
```

### 4. Separate Critical Queues

Use queue priorities:
```bash
# Process einvoice before default
php artisan queue:work --queue=einvoice,default
```

Critical jobs go to `einvoice` queue, others to `default`.

---

## Summary

✅ **Recommended for Facturino:** Use database queue driver
- Zero additional services
- No extra cost
- Perfect for your volume
- Simple to maintain

❌ **Not recommended:** Redis unless you hit 1000s of jobs/day

---

## Quick Start Commands

```bash
# 1. Update .env
echo "QUEUE_CONNECTION=database" >> .env

# 2. Run migration (already done)
php artisan migrate

# 3. Start worker locally
php artisan queue:work --queue=einvoice,default --tries=3 --timeout=120

# 4. Test with a job
php artisan tinker
>>> SubmitEInvoiceJob::dispatch(1);  # Dispatch test job
>>> DB::table('jobs')->count();       # Should be 0 after processing
>>> DB::table('failed_jobs')->count(); # Should be 0

# 5. Monitor
php artisan queue:failed  # Check failed jobs
tail -f storage/logs/laravel.log | grep SubmitEInvoiceJob
```

---

**Last Updated:** 2025-11-10
**Recommended:** Database Queue Driver ✅
**Alternative:** Redis (only if needed for high volume)
