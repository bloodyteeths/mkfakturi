# E-Invoice Queue Worker - Quick Start

## What Was Configured

The e-invoice submission system now uses Laravel queues to process invoice submissions asynchronously. This prevents UI blocking and enables automatic retries on failure.

## Quick Start

### Local Development

1. **Install Redis**
   ```bash
   brew install redis
   brew services start redis
   ```

2. **Update .env**
   ```bash
   QUEUE_CONNECTION=redis
   ```

3. **Start Queue Worker**
   ```bash
   ./start-queue-worker.sh
   ```

### Railway Production

Follow the step-by-step guide: [RAILWAY_QUEUE_DEPLOYMENT.md](RAILWAY_QUEUE_DEPLOYMENT.md)

## Documentation Files

| File | Purpose |
|------|---------|
| [QUEUE_SETUP_SUMMARY.md](QUEUE_SETUP_SUMMARY.md) | Complete summary of all changes |
| [QUEUE_WORKER_SETUP.md](QUEUE_WORKER_SETUP.md) | Full setup documentation |
| [QUEUE_COMMANDS.md](QUEUE_COMMANDS.md) | Quick command reference |
| [RAILWAY_QUEUE_DEPLOYMENT.md](RAILWAY_QUEUE_DEPLOYMENT.md) | Railway deployment guide |

## Scripts

| Script | Purpose |
|--------|---------|
| `start-queue-worker.sh` | Local development |
| `railway-queue-worker.sh` | Railway production |
| `verify-queue-setup.sh` | Verify configuration |

## Configuration Files

| File | Purpose |
|------|---------|
| `config/queue.php` | Queue configuration |
| `supervisor.conf` | Supervisor setup (optional) |
| `.env.example` | Environment template |

## Common Commands

```bash
# Start worker
./start-queue-worker.sh

# Verify setup
./verify-queue-setup.sh

# Monitor queue
php artisan queue:work redis --queue=einvoice --once --verbose

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

## Support

For detailed information, see:
- [QUEUE_SETUP_SUMMARY.md](QUEUE_SETUP_SUMMARY.md) - Complete overview
- [QUEUE_WORKER_SETUP.md](QUEUE_WORKER_SETUP.md) - Full documentation
- [QUEUE_COMMANDS.md](QUEUE_COMMANDS.md) - Command reference

## Status

✅ Configuration complete
✅ Scripts created
✅ Documentation provided
✅ Ready for deployment

// CLAUDE-CHECKPOINT
