# Staging Deployment Checklist: AC-08 → AC-18
## Partner Management & Commission System

**Target Branch**: `main`
**Deployment Date**: TBD
**Engineer**: _____________________

---

## Pre-Deployment Verification

### 1. Code Quality
- [ ] All PHPUnit tests passing (`php artisan test`)
- [ ] All frontend tests passing (`npm run test`)
- [ ] No PHP linting errors (`composer lint` if available)
- [ ] No ESLint errors (`npm run lint`)
- [ ] Code review approved by at least 1 other developer

### 2. Database Migrations
- [ ] Review all 8 new migrations (created 2025-11-18):
  - `2025_11_18_100000_create_partner_referrals_table.php`
  - `2025_11_18_100001_create_company_referrals_table.php`
  - `2025_11_18_085958_create_partners_table.php`
  - `2025_11_18_090000_create_partner_company_links_table.php`
  - `2025_11_18_090001_create_affiliate_links_table.php`
  - `2025_11_18_090002_create_affiliate_events_table.php`
  - `2025_11_18_090003_create_entity_reassignments_table.php`
  - Other partner-related migrations
- [ ] Test migrations on local copy of staging database
- [ ] Verify `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4` on all tables
- [ ] Verify all foreign key constraints with `ON DELETE RESTRICT`
- [ ] Backup production database before migration
- [ ] Run `php artisan migrate:status` to confirm pending migrations

### 3. Environment Configuration
- [ ] Verify `.env` settings for partner system:
  - Commission rates configured
  - Email settings for partner invitations
  - Super admin roles defined
- [ ] No hardcoded credentials in codebase
- [ ] API keys secured in environment variables

---

## Deployment Steps

### 4. Database Backup
```bash
# Backup database before deployment
php artisan db:backup  # Or your backup command
mysqldump -u [user] -p [database] > backup_$(date +%Y%m%d_%H%M%S).sql
```
- [ ] Database backup completed
- [ ] Backup file verified and accessible
- [ ] Backup stored in secure location

### 5. Code Deployment
```bash
# Pull latest code
git fetch origin
git checkout main
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```
- [ ] Code pulled from `main` branch
- [ ] Composer dependencies installed
- [ ] NPM dependencies installed
- [ ] Frontend assets built
- [ ] All caches cleared

### 6. Run Migrations
```bash
# Review migrations one more time
php artisan migrate:status

# Run migrations
php artisan migrate --force

# Verify migration success
php artisan migrate:status
```
- [ ] Migrations executed successfully
- [ ] No migration errors in logs
- [ ] All 8 new tables created
- [ ] Foreign keys validated

### 7. Seed Initial Data (if required)
```bash
# If seeding partner permissions or initial data
php artisan db:seed --class=PartnerPermissionSeeder
```
- [ ] Initial partner permissions seeded (if applicable)
- [ ] Test partner accounts created (if applicable)

### 8. Cache Warming
```bash
# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```
- [ ] Config cached
- [ ] Routes cached
- [ ] Views cached
- [ ] Events cached

---

## Post-Deployment Verification

### 9. Smoke Tests - Core Functionality
- [ ] Super admin can access `/partners` route
- [ ] Partner list loads without errors
- [ ] Create new partner works
- [ ] Assign company to partner works
- [ ] Permission editor displays correctly
- [ ] Partner portal login works
- [ ] Console dashboard loads for partners

### 10. Smoke Tests - Invitation System (AC-11, AC-12, AC-14, AC-15)
- [ ] Company→Partner invitation creates pending link
- [ ] Partner can generate affiliate link
- [ ] Partner→Company invitation generates QR code
- [ ] Company→Company referral generates token
- [ ] Partner→Partner referral generates token
- [ ] Pending invitations display correctly

### 11. Smoke Tests - Reassignment (AC-16)
- [ ] Super admin can access reassignment modal
- [ ] `/companies/{id}/current-partner` returns data
- [ ] `/partners/{id}/upline` returns upline info
- [ ] Company-to-partner reassignment works
- [ ] Partner upline reassignment works
- [ ] Reassignment log records entries

### 12. Smoke Tests - Network Graph (AC-17)
- [ ] Network graph loads without errors
- [ ] Pagination controls work
- [ ] Type filter (partners/companies/all) works
- [ ] Graph displays nodes and edges
- [ ] No JavaScript console errors

### 13. Smoke Tests - Commissions (AC-18)
- [ ] Direct commission (22%) calculated correctly
- [ ] Upline commission (5%) calculated correctly
- [ ] Sales rep commission (5%) calculated correctly (if applicable)
- [ ] Commission events recorded in database
- [ ] No duplicate commission events

### 14. Performance Check
- [ ] Partner list page loads in < 2 seconds
- [ ] Network graph renders in < 3 seconds for 100 nodes
- [ ] Permission caching working (verify in logs)
- [ ] No N+1 query issues in partner queries

### 15. Error Monitoring
- [ ] Check application logs for errors:
  ```bash
  tail -f storage/logs/laravel.log
  ```
- [ ] Check web server error logs
- [ ] Verify no 500 errors in monitoring dashboard
- [ ] Verify no database connection errors

---

## Rollback Preparation

### 16. Rollback Plan Ready
- [ ] Database backup accessible
- [ ] Previous code version tagged in git
- [ ] Rollback SQL script prepared (see ROLLBACK_STRATEGY.md)
- [ ] Rollback procedure documented and tested

---

## Queue & Background Jobs

### 17. Queue Workers
```bash
# Restart queue workers to pick up new code
php artisan queue:restart
```
- [ ] Queue workers restarted
- [ ] No failed jobs in queue
- [ ] Commission processing jobs running

### 18. Scheduled Tasks
- [ ] Verify cron jobs still running
- [ ] Check scheduled commission payouts (if applicable)

---

## Security Verification

### 19. Access Control
- [ ] Super admin middleware protecting partner routes
- [ ] Regular users cannot access partner management
- [ ] Partners can only see their own data
- [ ] CSRF protection working on all forms
- [ ] Sanctum authentication working

### 20. Data Integrity
- [ ] Foreign key constraints enforced
- [ ] No orphaned records in partner_company_links
- [ ] No orphaned records in affiliate_events
- [ ] Permission JSON validation working

---

## Documentation

### 21. Update Documentation
- [ ] API documentation updated (if new endpoints)
- [ ] Admin user guide updated
- [ ] Partner user guide created/updated
- [ ] Commission calculation documented

---

## Sign-Off

**Deployment Completed By**: _____________________
**Date**: _____ / _____ / _____
**Time**: _____:_____

**Issues Encountered**:
-
-
-

**Notes**:
-
-

---

## Emergency Contacts

**Technical Lead**: _____________________
**Database Admin**: _____________________
**DevOps Engineer**: _____________________
**Product Owner**: _____________________

// CLAUDE-CHECKPOINT
