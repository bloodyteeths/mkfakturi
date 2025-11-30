# Rollback Strategy: AC-08 → AC-18
## Partner Management & Commission System

**Document Version**: 1.0
**Last Updated**: 2025-11-18
**Prepared By**: Development Team

---

## Executive Summary

This document provides a comprehensive rollback strategy for the Partner Management and Multi-Level Commission System (AC-08 through AC-18). The rollback can be executed at different levels depending on the severity and timing of issues discovered post-deployment.

**Rollback Levels**:
1. **Level 1** - Code-only rollback (no database changes made yet)
2. **Level 2** - Full rollback with database migration reversal
3. **Level 3** - Emergency rollback with data preservation

---

## Pre-Rollback Checklist

Before initiating any rollback:

- [ ] Identify specific issue requiring rollback
- [ ] Document error messages and logs
- [ ] Notify stakeholders of rollback decision
- [ ] Confirm database backup is accessible
- [ ] Verify rollback authority (Production Manager approval required)
- [ ] Alert users of temporary downtime (if required)
- [ ] Prepare incident report template

---

## Level 1: Code-Only Rollback
**When to Use**: Migrations not yet run, or frontend-only issues

### Prerequisites
- [ ] Migrations have NOT been executed on target environment
- [ ] No production data has been created in new tables
- [ ] Issue is isolated to code logic (not schema)

### Rollback Steps

#### 1. Switch to Previous Git Commit
```bash
# Identify the last stable commit before AC-08 deployment
git log --oneline -20

# Note the commit hash (e.g., abc123def)
# Checkout previous stable version
git checkout <previous-stable-commit-hash>

# Alternative: Revert to main branch before merge
git checkout main
git reset --hard <commit-before-ac08-merge>
```

**Time Estimate**: 2 minutes

#### 2. Redeploy Code
```bash
# Install dependencies from clean state
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

**Time Estimate**: 5 minutes

#### 3. Restart Services
```bash
# Restart queue workers
php artisan queue:restart

# Restart PHP-FPM (if using)
sudo systemctl restart php8.2-fpm

# Restart web server
sudo systemctl restart nginx  # or apache2
```

**Time Estimate**: 2 minutes

#### 4. Verify Rollback
```bash
# Check application loads
curl -I https://your-domain.com

# Verify no partner routes accessible
curl https://your-domain.com/partners
# Should return 404 or previous behavior

# Check logs for errors
tail -f storage/logs/laravel.log
```

**Time Estimate**: 3 minutes

**Total Level 1 Rollback Time**: ~15 minutes

---

## Level 2: Full Rollback with Migration Reversal
**When to Use**: Migrations have been run, but no critical production data in new tables

### Prerequisites
- [ ] Migrations have been executed
- [ ] New tables contain only test/minimal data
- [ ] Data loss in new tables is acceptable
- [ ] Database backup completed before deployment

### Rollback Steps

#### 1. Backup Current State (Safety Measure)
```bash
# Create snapshot of current state before rollback
mysqldump -u [user] -p [database] > rollback_snapshot_$(date +%Y%m%d_%H%M%S).sql
```

**Time Estimate**: 2-5 minutes (depending on database size)

#### 2. Rollback Migrations
```bash
# List migrations to rollback (8 migrations for AC-08→AC-18)
php artisan migrate:status

# Rollback the last batch (if all AC-08→AC-18 migrations were in same batch)
php artisan migrate:rollback --step=8

# Verify rollback
php artisan migrate:status
# Should show 8 migrations as not run
```

**Time Estimate**: 3 minutes

**SQL to manually verify migration rollback**:
```sql
-- Verify tables dropped
SHOW TABLES LIKE 'partners';
SHOW TABLES LIKE 'partner_company_links';
SHOW TABLES LIKE 'affiliate_links';
SHOW TABLES LIKE 'affiliate_events';
SHOW TABLES LIKE 'entity_reassignments';
SHOW TABLES LIKE 'partner_referrals';
SHOW TABLES LIKE 'company_referrals';

-- All should return empty result
```

#### 3. Rollback Code (Follow Level 1 Steps 1-3)
```bash
git checkout <previous-stable-commit>
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan config:clear && php artisan cache:clear
php artisan route:clear && php artisan view:clear
php artisan queue:restart
sudo systemctl restart php8.2-fpm nginx
```

**Time Estimate**: 10 minutes

#### 4. Verify Application State
```bash
# Test core application functionality
# - User login
# - Invoice creation
# - Customer management
# - Reports generation

# Verify no references to partner system
grep -r "partner" storage/logs/laravel.log
# Should return no recent errors

# Check database integrity
php artisan migrate:status
# All migrations should match pre-AC-08 state
```

**Time Estimate**: 10 minutes

**Total Level 2 Rollback Time**: ~30 minutes

---

## Level 3: Emergency Rollback with Data Preservation
**When to Use**: Critical production data exists in new tables (commissions, referrals, etc.)

### Prerequisites
- [ ] Migrations have been run
- [ ] New tables contain critical production data
- [ ] Data must be preserved for later re-import
- [ ] Database backup is accessible

### Rollback Steps

#### 1. Export Critical Data from New Tables
```bash
# Export all new table data to SQL files
mysqldump -u [user] -p [database] \
  partners \
  partner_company_links \
  affiliate_links \
  affiliate_events \
  entity_reassignments \
  partner_referrals \
  company_referrals \
  > partner_system_data_backup_$(date +%Y%m%d_%H%M%S).sql

# Alternative: Export to CSV for easier analysis
mysql -u [user] -p [database] -e "SELECT * FROM affiliate_events" \
  > affiliate_events_backup.csv
```

**Time Estimate**: 5 minutes

#### 2. Verify Data Export
```bash
# Check file size (should not be 0 bytes)
ls -lh partner_system_data_backup_*.sql

# Verify row counts match
wc -l affiliate_events_backup.csv

mysql -u [user] -p -e "SELECT COUNT(*) FROM [database].affiliate_events"
# Counts should match
```

**Time Estimate**: 2 minutes

#### 3. Document Data State
```sql
-- Record current state for audit trail
SELECT
  'partners' AS table_name,
  COUNT(*) AS row_count,
  MAX(created_at) AS last_created
FROM partners

UNION ALL

SELECT
  'affiliate_events',
  COUNT(*),
  MAX(created_at)
FROM affiliate_events

UNION ALL

SELECT
  'partner_referrals',
  COUNT(*),
  MAX(created_at)
FROM partner_referrals;

-- Save output to file
```

**Time Estimate**: 2 minutes

#### 4. Rollback Migrations (Destructive)
```bash
# ⚠️ WARNING: This will drop tables and delete data
# Ensure backups are verified before proceeding

php artisan migrate:rollback --step=8

# Verify tables dropped
mysql -u [user] -p [database] -e "SHOW TABLES LIKE 'partners'"
# Should return empty
```

**Time Estimate**: 3 minutes

#### 5. Rollback Code (Follow Level 1 Steps 1-3)
```bash
git checkout <previous-stable-commit>
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan config:clear && php artisan cache:clear
php artisan route:clear && php artisan view:clear
php artisan queue:restart
sudo systemctl restart php8.2-fpm nginx
```

**Time Estimate**: 10 minutes

#### 6. Verify Application State
- [ ] Core application features working
- [ ] No errors in logs
- [ ] Users can log in
- [ ] Invoices, customers, reports functional
- [ ] No references to partner routes

**Time Estimate**: 10 minutes

#### 7. Secure Data Backups
```bash
# Move backups to secure long-term storage
mv partner_system_data_backup_*.sql /secure/backups/
mv *.csv /secure/backups/

# Set appropriate permissions
chmod 600 /secure/backups/partner_system_data_backup_*.sql

# Document backup location in incident report
```

**Time Estimate**: 3 minutes

**Total Level 3 Rollback Time**: ~40 minutes

---

## Post-Rollback Actions

### Immediate (Within 1 Hour)
- [ ] Verify application is stable
- [ ] Monitor error logs for 30 minutes
- [ ] Notify users that issue has been resolved
- [ ] Update status page (if applicable)
- [ ] Create incident report

### Within 24 Hours
- [ ] Root cause analysis meeting
- [ ] Review what went wrong
- [ ] Identify gaps in testing
- [ ] Plan fix strategy
- [ ] Update deployment checklist with lessons learned

### Within 1 Week
- [ ] Fix identified issues
- [ ] Create new test cases for missed scenarios
- [ ] Plan re-deployment timeline
- [ ] Communicate plan to stakeholders

---

## Data Re-Import Strategy (After Rollback Fix)

If Level 3 rollback was performed and data was preserved:

### Step 1: Analyze Exported Data
```bash
# Review backed-up data
mysql -u [user] -p [database] < partner_system_data_backup_[timestamp].sql

# Verify data integrity
SELECT COUNT(*) FROM affiliate_events_backup;
```

### Step 2: Re-Deploy Fixed Version
- Follow deployment checklist with fixes applied
- Run migrations to recreate tables

### Step 3: Re-Import Historical Data
```sql
-- Re-import affiliate events
INSERT INTO affiliate_events
SELECT * FROM affiliate_events_backup
ON DUPLICATE KEY UPDATE id=id;  -- Skip duplicates

-- Verify counts
SELECT COUNT(*) FROM affiliate_events;
SELECT COUNT(*) FROM affiliate_events_backup;
-- Should match

-- Repeat for all tables
```

### Step 4: Reconciliation
- [ ] Compare row counts before/after
- [ ] Verify foreign key relationships
- [ ] Run data integrity checks
- [ ] Test commission calculations on historical data

---

## Rollback Decision Matrix

| Scenario | Rollback Level | Data Loss | Downtime | Approval Required |
|----------|---------------|-----------|----------|------------------|
| Frontend bug, no migrations run | Level 1 | None | 0-5 min | Tech Lead |
| Backend bug, migrations run, no production data | Level 2 | Test data only | 15-30 min | Tech Lead |
| Critical bug, production commission data exists | Level 3 | None (preserved) | 30-60 min | Production Manager |
| Database corruption | Restore from backup | Depends on backup age | 1-2 hours | Production Manager + DBA |

---

## Emergency Contacts

**Technical Lead**: _____________________
**Database Administrator**: _____________________
**DevOps Engineer**: _____________________
**Production Manager**: _____________________
**On-Call Developer**: _____________________

---

## Rollback Execution Log

**Rollback Performed**: [ ] YES [ ] NO
**Date/Time**: _____ / _____ / _____ at _____:_____
**Level Executed**: [ ] Level 1 [ ] Level 2 [ ] Level 3
**Performed By**: _____________________
**Reason for Rollback**:
-
-

**Issues Encountered During Rollback**:
-
-

**Verification Completed**: [ ] YES [ ] NO
**Application Stable**: [ ] YES [ ] NO
**Data Preserved**: [ ] YES [ ] NO [ ] N/A

**Sign-Off**: _____________________
**Date**: _____ / _____ / _____

---

## Testing Rollback Procedure (Recommended)

To ensure this rollback strategy works when needed:

### On Staging Environment
1. Deploy AC-08→AC-18 to staging
2. Create sample production-like data
3. Execute Level 2 rollback
4. Verify all steps work as documented
5. Document any deviations
6. Update this document

**Last Rollback Drill**: _____ / _____ / _____
**Drill Result**: [ ] PASS [ ] FAIL
**Notes**:
-
-

---

## Appendix: Manual SQL Rollback Scripts

If `php artisan migrate:rollback` fails, use these manual scripts:

### Drop Tables (⚠️ DESTRUCTIVE)
```sql
-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Drop tables in reverse dependency order
DROP TABLE IF EXISTS entity_reassignments;
DROP TABLE IF EXISTS affiliate_events;
DROP TABLE IF EXISTS affiliate_links;
DROP TABLE IF EXISTS partner_company_links;
DROP TABLE IF EXISTS company_referrals;
DROP TABLE IF EXISTS partner_referrals;
DROP TABLE IF EXISTS partners;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
```

### Verify Tables Dropped
```sql
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'your_database_name'
AND TABLE_NAME IN (
  'partners',
  'partner_company_links',
  'affiliate_links',
  'affiliate_events',
  'entity_reassignments',
  'partner_referrals',
  'company_referrals'
);
-- Should return 0 rows
```

### Remove Migration Records
```sql
-- Remove migration entries
DELETE FROM migrations
WHERE migration LIKE '%partner%'
OR migration LIKE '%affiliate%'
OR migration LIKE '%reassignment%';

-- Verify removal
SELECT * FROM migrations WHERE migration LIKE '%partner%';
-- Should return 0 rows
```

---

**Document Control**

**Version**: 1.0
**Status**: APPROVED
**Next Review Date**: _____ / _____ / _____
**Approved By**: _____________________

// CLAUDE-CHECKPOINT
