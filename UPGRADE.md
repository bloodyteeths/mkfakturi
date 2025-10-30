# Upgrade Guide

This document provides comprehensive instructions for upgrading to MK Accounting Platform v1.0.0-rc1.

## Table of Contents

- [Overview](#overview)
- [Pre-Upgrade Checklist](#pre-upgrade-checklist)
- [System Requirements](#system-requirements)
- [Backup Procedures](#backup-procedures)
- [Upgrade Steps](#upgrade-steps)
- [Post-Upgrade Configuration](#post-upgrade-configuration)
- [Macedonia-Specific Setup](#macedonia-specific-setup)
- [Troubleshooting](#troubleshooting)
- [Rollback Procedures](#rollback-procedures)

## Overview

Version 1.0.0-rc1 introduces significant new features and improvements:

- **Universal Migration Wizard** with intelligent field mapping
- **Accountant Console** for multi-client management
- **CPAY Payment Integration** for Macedonia domestic payments
- **XML Export & Digital Signing** for tax compliance
- **AI Financial Assistant** with advanced analytics
- **Enhanced Security** and performance optimizations

⚠️ **IMPORTANT**: This is a major release with breaking changes. Please read this guide completely before upgrading.

## Pre-Upgrade Checklist

### 1. Version Compatibility

✅ **Supported Upgrade Paths:**
- From InvoiceShelf 2.x → MK Accounting 1.0.0-rc1 ✅
- From Onivo/Megasoft/Pantheon → MK Accounting 1.0.0-rc1 ✅ (via Migration Wizard)

❌ **Unsupported Upgrades:**
- Direct upgrade from InvoiceShelf 1.x (upgrade to 2.x first)

### 2. Environment Verification

```bash
# Check PHP version (8.1, 8.2, or 8.3 required)
php -v

# Check database version
mysql --version  # MySQL 8.0+ required
# OR
psql --version   # PostgreSQL 15+ required

# Check available disk space (minimum 5GB recommended)
df -h

# Check memory availability (minimum 2GB recommended)
free -h
```

### 3. Dependencies Check

```bash
# Verify Composer is available
composer --version

# Verify Node.js (version 20+ required)
node --version
npm --version

# Verify Docker (if using containerized deployment)
docker --version
docker-compose --version
```

## System Requirements

### Minimum Requirements

| Component | Requirement | Recommended |
|-----------|-------------|-------------|
| **PHP** | 8.1+ | 8.3 |
| **Database** | MySQL 8.0+ / PostgreSQL 15+ | MySQL 8.0 |
| **Node.js** | 20+ | 20 LTS |
| **Memory** | 512MB | 2GB |
| **Storage** | 1GB | 5GB |
| **Docker** | 20.10+ (optional) | Latest |

### PHP Extensions Required

```
bcmath, curl, dom, gd, imagick, json, libxml, mbstring, 
pcntl, pdo, pdo_mysql, zip, soap, redis, intl, xml, 
ctype, iconv, filter
```

## Backup Procedures

### 1. Database Backup

```bash
# MySQL
mysqldump -u [username] -p [database_name] > backup_$(date +%Y%m%d_%H%M%S).sql

# PostgreSQL
pg_dump -U [username] -h localhost [database_name] > backup_$(date +%Y%m%d_%H%M%S).sql
```

### 2. File System Backup

```bash
# Create full application backup
tar -czf application_backup_$(date +%Y%m%d_%H%M%S).tar.gz /path/to/your/application

# Backup important directories
cp -r storage/ storage_backup_$(date +%Y%m%d_%H%M%S)/
cp -r public/uploads/ uploads_backup_$(date +%Y%m%d_%H%M%S)/
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
```

### 3. Configuration Backup

```bash
# Backup configuration files
cp config/database.php config/database.php.backup
cp config/mail.php config/mail.php.backup
cp config/services.php config/services.php.backup
```

## Upgrade Steps

### Step 1: Download and Extract

```bash
# Download the latest release
wget https://github.com/bloodyteeths/mkaccounting-roadmap3/archive/v1.0.0-rc1.tar.gz

# Extract to temporary directory
tar -xzf v1.0.0-rc1.tar.gz -C /tmp/

# Navigate to extracted directory
cd /tmp/mkaccounting-roadmap3-1.0.0-rc1/
```

### Step 2: Install Dependencies

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install Node.js dependencies
npm ci --only=production

# Build frontend assets
npm run build
```

### Step 3: Update Application Files

```bash
# Stop application services
sudo systemctl stop nginx  # or apache2
sudo systemctl stop php8.2-fpm  # adjust PHP version as needed

# Copy new application files (preserve existing .env and storage)
rsync -av --exclude='.env' --exclude='storage/' /tmp/mkaccounting-roadmap3-1.0.0-rc1/ /path/to/your/application/

# Set proper permissions
sudo chown -R www-data:www-data /path/to/your/application/
sudo chmod -R 755 /path/to/your/application/
sudo chmod -R 775 /path/to/your/application/storage/
sudo chmod -R 775 /path/to/your/application/bootstrap/cache/
```

### Step 4: Database Migration

```bash
# Navigate to application directory
cd /path/to/your/application/

# Run database migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 5: Update Dependencies

```bash
# Install new XML dependencies
composer require num-num/ubl robrichards/xmlseclibs

# Create schemas directory
mkdir -p storage/schemas/

# Download UBL 2.1 XSD schemas (manual step)
# Visit: https://docs.oasis-open.org/ubl/UBL-2.1.html
# Download and extract schemas to storage/schemas/
```

## Post-Upgrade Configuration

### 1. Environment Configuration

Add new environment variables to your `.env` file:

```bash
# CPAY Configuration (Macedonia payments)
CPAY_MERCHANT_ID=your_merchant_id
CPAY_SECRET_KEY=your_secret_key
CPAY_PAYMENT_URL=https://cpay.com.mk/payment
CPAY_SUCCESS_URL=/payment/success
CPAY_CANCEL_URL=/payment/cancel
CPAY_CALLBACK_URL=/payment/callback

# Paddle Configuration (International payments)
PADDLE_VENDOR_ID=your_vendor_id
PADDLE_WEBHOOK_SECRET=your_webhook_secret
PADDLE_ENVIRONMENT=sandbox

# AI Assistant Configuration
AI_MCP_ENABLED=true
AI_MCP_HOST=localhost
AI_MCP_PORT=3002
AI_MOCK_DATA=true

# Performance Monitoring
PERFORMANCE_MONITORING_ENABLED=true
CACHE_PERFORMANCE_METRICS=true
```

### 2. Services Configuration

Update `config/services.php`:

```php
// Add Paddle configuration
'paddle' => [
    'vendor_id' => env('PADDLE_VENDOR_ID'),
    'webhook_secret' => env('PADDLE_WEBHOOK_SECRET'),
    'environment' => env('PADDLE_ENVIRONMENT', 'sandbox'),
],
```

### 3. Queue Configuration

```bash
# Set up queue worker (recommended for production)
php artisan queue:work --daemon --queue=default,migration,high,low

# Or configure supervisor for automatic queue management
sudo nano /etc/supervisor/conf.d/mkaccounting-worker.conf
```

### 4. Cron Jobs

Add to crontab for automated tasks:

```bash
crontab -e

# Add the following line:
* * * * * cd /path/to/your/application && php artisan schedule:run >> /dev/null 2>&1
```

## Macedonia-Specific Setup

### 1. Banking Integration (PSD2)

```bash
# Stopanska Bank
STOPANSKA_CLIENT_ID=your_client_id
STOPANSKA_CLIENT_SECRET=your_client_secret
STOPANSKA_SANDBOX=true

# NLB Bank
NLB_API_KEY=your_api_key
NLB_SANDBOX=true

# Komercijalna Bank
KOMERCIJALNA_CLIENT_ID=your_client_id
KOMERCIJALNA_CLIENT_SECRET=your_client_secret
KOMERCIJALNA_SANDBOX=true
```

### 2. Tax Compliance Setup

```bash
# ДДВ-04 Export Configuration
TAX_AUTHORITY_ENDPOINT=https://ujp.gov.mk/api
TAX_AUTHORITY_CERT_PATH=/path/to/certificate.pem
TAX_AUTHORITY_KEY_PATH=/path/to/private.key
```

### 3. Digital Signature Setup

```bash
# Generate test certificate for development
openssl req -x509 -newkey rsa:2048 -keyout private.key -out certificate.pem -days 365 -nodes

# For production, obtain certificate from Macedonia CA
# Place certificates in storage/certificates/
```

## Troubleshooting

### Common Issues

#### 1. Migration Failures

```bash
# Check migration status
php artisan migrate:status

# Rollback last migration if needed
php artisan migrate:rollback

# Force migration (use with caution)
php artisan migrate --force
```

#### 2. Permission Issues

```bash
# Fix file permissions
sudo chown -R www-data:www-data storage/
sudo chown -R www-data:www-data bootstrap/cache/
sudo chmod -R 775 storage/
sudo chmod -R 775 bootstrap/cache/
```

#### 3. Cache Issues

```bash
# Clear all caches
php artisan optimize:clear

# Rebuild caches
php artisan optimize
```

#### 4. Queue Issues

```bash
# Restart queue workers
php artisan queue:restart

# Clear failed jobs
php artisan queue:flush
```

### Error Diagnostics

#### Enable Debug Mode

```bash
# Temporarily enable debug (disable in production)
echo "APP_DEBUG=true" >> .env
```

#### Check Logs

```bash
# View application logs
tail -f storage/logs/laravel.log

# View web server logs
sudo tail -f /var/log/nginx/error.log  # Nginx
sudo tail -f /var/log/apache2/error.log  # Apache
```

#### Database Connection Test

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo();
```

### Performance Issues

#### Memory Optimization

```bash
# Increase PHP memory limit
echo "memory_limit = 512M" | sudo tee -a /etc/php/8.2/fpm/php.ini

# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

#### Database Optimization

```bash
# Optimize database tables
php artisan db:optimize

# Rebuild search indexes
php artisan scout:import "App\Models\Invoice"
php artisan scout:import "App\Models\Customer"
```

## Rollback Procedures

### Emergency Rollback

If upgrade fails, follow these steps:

#### 1. Restore Database

```bash
# Stop application
sudo systemctl stop nginx php8.2-fpm

# Restore database backup
mysql -u [username] -p [database_name] < backup_YYYYMMDD_HHMMSS.sql
# OR for PostgreSQL:
psql -U [username] -h localhost [database_name] < backup_YYYYMMDD_HHMMSS.sql
```

#### 2. Restore Application Files

```bash
# Restore from backup
tar -xzf application_backup_YYYYMMDD_HHMMSS.tar.gz -C /

# Restore configuration
cp .env.backup.YYYYMMDD_HHMMSS .env
```

#### 3. Restart Services

```bash
sudo systemctl start php8.2-fpm
sudo systemctl start nginx
```

### Partial Rollback

For specific feature rollback:

```bash
# Disable new features in .env
AI_MCP_ENABLED=false
PERFORMANCE_MONITORING_ENABLED=false

# Clear caches
php artisan optimize:clear
```

## Testing After Upgrade

### 1. Functionality Tests

```bash
# Test basic functionality
php artisan test --testsuite=Feature

# Test database integrity
php artisan test --filter=DBInvariantTest

# Test API endpoints
bash tools/run_api_tests.sh
```

### 2. UI Tests

```bash
# Run Cypress E2E tests
npm run test:e2e

# Run visual regression tests
npm run test:visual
```

### 3. Performance Tests

```bash
# Test performance metrics
php artisan test --testsuite=Performance

# Monitor response times
tail -f storage/logs/performance.log
```

### 4. Macedonia-Specific Tests

```bash
# Test CPAY integration
php artisan test --filter=CpayGatewayTest

# Test field mapping
php artisan test --filter=FieldMapperTest

# Test XML export
php artisan test --filter=XmlExportTest
```

## Support

If you encounter issues during upgrade:

1. **Check logs**: `storage/logs/laravel.log`
2. **Review documentation**: `/docs` directory
3. **Test environment**: Set up staging environment first
4. **Community support**: Create GitHub issue with logs
5. **Professional support**: Contact Macedonia technical team

### Emergency Contacts

- **Technical Support**: [Support Flowchart](docs/SupportFlowchart.md)
- **Macedonia Banking**: PSD2 integration specialists
- **Tax Compliance**: ДДВ-04 export specialists

---

## Checklist Summary

- [ ] **Pre-upgrade backup** completed
- [ ] **System requirements** verified
- [ ] **Dependencies** installed
- [ ] **Database migration** successful
- [ ] **Environment variables** configured
- [ ] **File permissions** set correctly
- [ ] **Caches** cleared and rebuilt
- [ ] **Queue workers** configured
- [ ] **Cron jobs** scheduled
- [ ] **Macedonia features** configured
- [ ] **Functionality tests** passed
- [ ] **Performance monitoring** enabled

**🚀 Congratulations!** Your MK Accounting Platform is now upgraded to v1.0.0-rc1 with full Macedonia market capabilities.

---

**🤖 Generated with [Claude Code](https://claude.ai/code)**

**Co-Authored-By: Claude <noreply@anthropic.com>**

