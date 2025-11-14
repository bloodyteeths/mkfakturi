# Backup & Restore Procedures

## Overview

Facturino uses Spatie Laravel Backup for automated database and file backups. This document outlines all backup and restore procedures for system administrators.

## Automated Backup Schedule

The backup system runs automatically on the following schedule:

- **Daily Backup:** 2:00 AM (database + files)
- **Cleanup:** 1:00 AM daily (removes old backups per retention policy)
- **Health Monitor:** Every 6 hours (checks backup status)

## Backup Retention Policy

- **All backups:** Kept for 7 days
- **Daily backups:** Kept for 30 days
- **Weekly backups:** Kept for 12 weeks (3 months)
- **Monthly backups:** Kept for 12 months (1 year)
- **Yearly backups:** Kept for 3 years

## What Gets Backed Up

### Database
- Full PostgreSQL database dump (compressed with gzip)
- All tables, including user data, invoices, payments, and settings

### Files
- QES certificates (`storage/app/certificates/`)
- User uploads (`storage/app/public/uploads/`)
- Environment configuration (`.env`)
- Application logs (`storage/logs/`)
- Configuration files (`config/`)

### What's NOT Backed Up
- Vendor dependencies (`vendor/`, `node_modules/`)
- Cache files (`storage/framework/cache/`, `storage/framework/sessions/`)
- Previous backups (`storage/app/backups/`)

## Manual Backup Commands

### Create Manual Backup

To create a backup immediately (outside the scheduled time):

```bash
php artisan backup:run
```

For database-only backup:

```bash
php artisan backup:run --only-db
```

For files-only backup:

```bash
php artisan backup:run --only-files
```

### List All Backups

To see all available backups:

```bash
php artisan backup:list
```

This displays:
- Backup filename
- File size
- Creation date
- Total storage used

### Check Backup Health

To verify backups are healthy:

```bash
php artisan backup:monitor
```

### Clean Old Backups

To manually trigger cleanup:

```bash
php artisan backup:clean
```

## Backup Storage Locations

### Local Storage
Default location: `storage/app/{APP_NAME}/`

For Facturino: `storage/app/facturino/`

### Remote Storage (Optional)
If configured, backups are also stored on:
- Amazon S3 (configure AWS credentials in `.env`)
- Other cloud storage (configure in `config/backup.php`)

## Restore Procedures

### Prerequisites

Before restoring:
1. Ensure you have SSH/terminal access to the server
2. Have the backup file location ready
3. **IMPORTANT:** Stop the application to prevent data conflicts
4. Verify you have sufficient disk space

### Step 1: Stop Application Services

```bash
# Stop web server
sudo systemctl stop nginx

# Stop queue workers
php artisan queue:restart

# Put application in maintenance mode
php artisan down
```

### Step 2: Download Backup File

Backups are stored in `storage/app/facturino/`

```bash
# List available backups
ls -lh storage/app/facturino/

# Copy the latest backup filename
# Example: facturino-2025-11-14-02-00-00.zip
```

### Step 3: Extract Backup

```bash
# Create restore directory
mkdir -p restore

# Extract backup
unzip storage/app/facturino/facturino-2025-11-14-02-00-00.zip -d restore/
```

### Step 4: Restore Database

```bash
# Navigate to database dump
cd restore/db-dumps/

# Decompress database dump
gunzip pgsql-facturino.sql.gz

# Restore to PostgreSQL
# IMPORTANT: This will DROP and recreate the database
psql -U facturino -d facturino < pgsql-facturino.sql
```

**Alternative: Restore without dropping existing data**

```bash
# Create new database for restore
psql -U postgres -c "CREATE DATABASE facturino_restore;"

# Restore to new database
psql -U facturino -d facturino_restore < pgsql-facturino.sql

# Verify data, then rename databases
psql -U postgres -c "ALTER DATABASE facturino RENAME TO facturino_old;"
psql -U postgres -c "ALTER DATABASE facturino_restore RENAME TO facturino;"
```

### Step 5: Restore Files

```bash
# Navigate back to root
cd ../..

# Restore QES certificates
cp -r restore/certificates/* storage/app/certificates/

# Restore user uploads
cp -r restore/uploads/* storage/app/public/uploads/

# Restore environment file (CAREFUL: Check before overwriting)
cp restore/.env .env.backup
# Review .env.backup and merge changes as needed

# Restore logs (optional)
cp -r restore/logs/* storage/logs/
```

### Step 6: Set Permissions

```bash
# Set correct ownership
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/

# Set correct permissions
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
```

### Step 7: Clear Caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Step 8: Verify Database

```bash
# Check migration status
php artisan migrate:status

# If migrations are out of sync, run:
# php artisan migrate --force
```

### Step 9: Restart Services

```bash
# Bring application back online
php artisan up

# Start queue workers
php artisan queue:restart

# Restart web server
sudo systemctl start nginx

# Restart PHP-FPM (if using)
sudo systemctl restart php8.2-fpm
```

### Step 10: Verification

Run health checks to verify restore:

```bash
# Check application health
curl http://localhost/health

# Check database connectivity
php artisan tinker
>>> DB::connection()->getPdo();

# Check queue workers
php artisan queue:work --once

# Test login
# Visit application URL and log in
```

## Backup Notifications

Email notifications are sent to `BACKUP_NOTIFICATION_EMAIL` for:

- **Backup Failed:** When backup process fails
- **Unhealthy Backup:** When backup doesn't meet health checks
- **Cleanup Failed:** When old backup cleanup fails
- **Backup Successful:** When backup completes successfully (if enabled)

Configure in `.env`:

```env
BACKUP_NOTIFICATION_EMAIL=admin@facturino.mk
```

## Troubleshooting

### Backup Command Fails

**Issue:** `backup:run` command fails with permission error

**Solution:**
```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage/
chmod -R 775 storage/
```

**Issue:** Database dump fails

**Solution:**
```bash
# Verify database credentials
php artisan tinker
>>> config('database.connections.pgsql');

# Test database connection
psql -U facturino -d facturino -c "SELECT version();"
```

### Backup Size Too Large

**Issue:** Backups exceed 5GB limit

**Solution:**
1. Review `config/backup.php` exclusions
2. Exclude large log files or temporary data
3. Increase limit in `config/backup.php`:

```php
'keep_all_backups_for_days' => 5, // Reduce from 7
'delete_oldest_backups_when_using_more_megabytes_than' => 10000, // Increase limit
```

### Restore Fails

**Issue:** Database restore fails with encoding errors

**Solution:**
```bash
# Restore with encoding specified
psql -U facturino -d facturino -c "SET client_encoding TO 'UTF8';"
psql -U facturino -d facturino < pgsql-facturino.sql
```

**Issue:** File permissions wrong after restore

**Solution:**
```bash
# Reset all permissions
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/
chmod -R 775 storage/ bootstrap/cache/
```

## Testing Restore Procedures

It's critical to test restore procedures regularly (recommended: monthly).

### Test Restore Checklist

1. **Create test backup**
   ```bash
   php artisan backup:run --only-db
   ```

2. **Restore to staging environment**
   - Use separate staging server or Docker container
   - Follow restore procedures above

3. **Verify functionality**
   - Can users log in?
   - Can invoices be created?
   - Can payments be processed?
   - Are all files accessible?

4. **Document issues**
   - Note any problems encountered
   - Update this documentation
   - Fix any configuration issues

5. **Time the process**
   - Record how long restore takes
   - Plan downtime windows accordingly

## Disaster Recovery Plan

### Scenario: Complete Server Failure

1. **Provision new server** (same specs as original)
2. **Install dependencies** (PHP, PostgreSQL, Nginx)
3. **Clone application code** from GitHub
4. **Download latest backup** from remote storage (S3)
5. **Follow restore procedures** (Steps 1-10 above)
6. **Update DNS** to point to new server
7. **Monitor application** for 24 hours

### Scenario: Database Corruption

1. **Stop all writes** to database
2. **Identify last known good backup**
3. **Restore database** to separate database name
4. **Compare with corrupted database**
5. **Merge any critical recent data** manually
6. **Switch to restored database**
7. **Resume operations**

### Scenario: Accidental Data Deletion

1. **Identify deletion time** from logs
2. **Find backup** from before deletion
3. **Restore to temporary database**
4. **Extract deleted records** with SQL queries
5. **Import records** to production database
6. **Verify data integrity**

## Security Considerations

### Backup Encryption

To encrypt backups, set password in `.env`:

```env
BACKUP_ARCHIVE_PASSWORD=your-strong-password-here
```

**IMPORTANT:** Store this password securely! Without it, backups cannot be restored.

### Access Control

Restrict access to backup files:

```bash
# Limit backup directory access
chmod 700 storage/app/facturino/

# Only www-data user can access
chown www-data:www-data storage/app/facturino/
```

### Remote Storage

For production, always use remote storage (S3) as secondary backup location:

```env
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=eu-central-1
AWS_BUCKET=facturino-backups
```

Update `config/backup.php`:

```php
'disks' => [
    'local',
    's3',
],
```

## Monitoring & Alerting

### Health Check Integration

The backup system integrates with the application health check endpoint (`/health`).

Health check monitors:
- Backup age (alerts if > 1 day old)
- Backup size (alerts if > 5GB)
- Backup failures

### Manual Health Check

```bash
# Check backup health
php artisan backup:monitor

# View health status
php artisan backup:list
```

### Notification Configuration

Configure email notifications in `config/backup.php`:

```php
'mail' => [
    'to' => env('BACKUP_NOTIFICATION_EMAIL', 'admin@facturino.mk'),
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'noreply@facturino.mk'),
        'name' => env('MAIL_FROM_NAME', 'Facturino Backups'),
    ],
],
```

## Additional Resources

- **Spatie Backup Documentation:** https://spatie.be/docs/laravel-backup
- **PostgreSQL Backup Guide:** https://www.postgresql.org/docs/current/backup.html
- **Facturino Deployment Runbook:** `documentation/DEPLOYMENT_RUNBOOK.md`

## Support

For backup/restore issues, contact:
- **Email:** admin@facturino.mk
- **Documentation:** https://docs.facturino.mk
- **GitHub Issues:** https://github.com/facturino/facturino/issues

---

**Last Updated:** 2025-11-14
**Version:** 1.0
**Maintained by:** Facturino DevOps Team
