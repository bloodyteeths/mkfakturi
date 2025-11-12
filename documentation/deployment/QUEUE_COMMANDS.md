# Queue Worker Quick Reference

## Starting Queue Workers

### Local Development
```bash
# Simple start (uses default settings)
./start-queue-worker.sh

# Or manually with custom options
php artisan queue:work redis --queue=einvoice --tries=3 --timeout=120 --verbose
```

### Production/Railway
```bash
# Railway queue worker script
./railway-queue-worker.sh

# Or with supervisor
supervisord -c supervisor.conf
```

## Monitoring Queues

### Check Queue Size
```bash
# Via Artisan
php artisan queue:monitor

# Via Tinker
php artisan tinker
>>> Queue::size('einvoice');
```

### Check Failed Jobs
```bash
# List all failed jobs
php artisan queue:failed

# Show details of specific failed job
php artisan queue:failed-show <job-id>
```

### Watch Queue Logs
```bash
# Tail Laravel logs
tail -f storage/logs/laravel.log | grep SubmitEInvoiceJob

# Or all queue activity
tail -f storage/logs/laravel.log | grep -E "(queue|job)"
```

## Managing Jobs

### Retry Failed Jobs
```bash
# Retry specific job
php artisan queue:retry <job-id>

# Retry all failed jobs
php artisan queue:retry all

# Retry failed jobs from last hour
php artisan queue:retry --hours=1
```

### Clear Queues
```bash
# Clear all jobs from einvoice queue
php artisan queue:clear redis --queue=einvoice

# Clear failed jobs table
php artisan queue:flush

# Forget specific failed job (don't retry)
php artisan queue:forget <job-id>
```

### Control Workers
```bash
# Pause queue processing (current jobs finish, new jobs wait)
php artisan queue:pause

# Resume paused queue
php artisan queue:resume

# Gracefully restart all workers (finish current jobs, then restart)
php artisan queue:restart
```

## Testing & Debugging

### Process One Job (Debug Mode)
```bash
# Process exactly one job from queue
php artisan queue:work redis --queue=einvoice --once --verbose

# Useful for debugging job failures
```

### Dispatch Test Job
```bash
php artisan tinker
>>> $invoice = \App\Models\Invoice::first();
>>> $eInvoice = \App\Models\EInvoice::create([
...   'invoice_id' => $invoice->id,
...   'company_id' => $invoice->company_id,
...   'status' => 'draft',
... ]);
>>> \App\Jobs\SubmitEInvoiceJob::dispatch($eInvoice->id);
>>> Queue::size('einvoice'); // Should be 1
```

### Check Job Status
```bash
php artisan tinker
>>> $submission = \App\Models\EInvoiceSubmission::latest()->first();
>>> $submission->status; // 'pending', 'accepted', 'rejected', 'error'
>>> $submission->response_data; // Full response details
```

## Redis Commands

### Check Redis Connection
```bash
# Ping Redis
redis-cli ping

# Check Redis info
redis-cli info

# Monitor Redis commands in real-time
redis-cli monitor
```

### View Queue Data in Redis
```bash
# List all queue keys
redis-cli KEYS "queues:*"

# Get queue length
redis-cli LLEN "queues:einvoice"

# Peek at next job (don't remove)
redis-cli LINDEX "queues:einvoice" 0
```

## Common Issues

### Queue Worker Not Processing
```bash
# 1. Check if Redis is running
redis-cli ping

# 2. Check queue size
php artisan tinker
>>> Queue::size('einvoice');

# 3. Try processing one job manually
php artisan queue:work redis --queue=einvoice --once --verbose

# 4. Check for failed jobs
php artisan queue:failed
```

### Jobs Failing Immediately
```bash
# 1. Check Laravel logs
tail -f storage/logs/laravel.log

# 2. Check job class exists
php artisan tinker
>>> class_exists(\App\Jobs\SubmitEInvoiceJob::class);

# 3. Check database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Memory Issues
```bash
# Restart worker with memory limit
php artisan queue:work redis \
    --queue=einvoice \
    --tries=3 \
    --timeout=120 \
    --memory=512 \
    --max-jobs=50

# Or adjust in supervisor.conf
```

## Production Monitoring

### Health Check Script
```bash
#!/bin/bash
# Check if queue worker is running
if ! pgrep -f "queue:work" > /dev/null; then
    echo "ERROR: No queue workers running!"
    # Send alert
fi

# Check queue size
QUEUE_SIZE=$(php artisan tinker --execute="echo Queue::size('einvoice');" 2>/dev/null | tail -1)
if [ "$QUEUE_SIZE" -gt 100 ]; then
    echo "WARNING: Queue size is $QUEUE_SIZE (threshold: 100)"
    # Send alert
fi

# Check failed jobs
FAILED_COUNT=$(php artisan queue:failed | grep -c "^|")
if [ "$FAILED_COUNT" -gt 10 ]; then
    echo "WARNING: $FAILED_COUNT failed jobs (threshold: 10)"
    # Send alert
fi
```

### Log Monitoring
```bash
# Count errors in last hour
grep "SubmitEInvoiceJob.*Exception" storage/logs/laravel.log | \
    grep "$(date -u +%Y-%m-%d -d '1 hour ago')" | wc -l

# Find most common errors
grep "SubmitEInvoiceJob.*error" storage/logs/laravel.log | \
    grep -oP '"error":"[^"]*"' | sort | uniq -c | sort -rn | head -10
```

## Environment Variables

```bash
# Queue configuration
QUEUE_CONNECTION=redis      # Use Redis queue driver
REDIS_HOST=127.0.0.1       # Redis host
REDIS_PORT=6379            # Redis port
REDIS_PASSWORD=null        # Redis password (if required)
```

## Files

- `/Users/tamsar/Downloads/mkaccounting/start-queue-worker.sh` - Local development script
- `/Users/tamsar/Downloads/mkaccounting/railway-queue-worker.sh` - Railway production script
- `/Users/tamsar/Downloads/mkaccounting/supervisor.conf` - Supervisor configuration
- `/Users/tamsar/Downloads/mkaccounting/config/queue.php` - Queue configuration
- `/Users/tamsar/Downloads/mkaccounting/QUEUE_WORKER_SETUP.md` - Full documentation

// CLAUDE-CHECKPOINT
