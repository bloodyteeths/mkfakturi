# Phase 2 Deployment Guide

## Overview
Phase 2 introduces Accounts Payable (Suppliers & Bills), Proforma Invoices, and Audit Log UI capabilities to the Facturino application.

## Prerequisites

### Phase 1 Must Be Deployed
Before deploying Phase 2, ensure Phase 1 is fully deployed and operational:
- All Phase 1 migrations have been run
- Partners and Commissions features are working
- Banking integration is functional
- No pending migrations or errors

### System Requirements
- PHP 8.1+
- MySQL 8.0+ / MariaDB 10.4+
- All Phase 1 dependencies installed
- Application in maintenance mode during deployment

### Pre-Deployment Checklist
- [ ] Verify Phase 1 is fully deployed (`php artisan migrate:status`)
- [ ] Backup database
- [ ] Backup application files
- [ ] Review storage permissions
- [ ] Enable maintenance mode: `php artisan down`

## Deployment Steps

### 1. Pull Latest Code
```bash
git fetch origin
git checkout main
git pull origin main
```

### 2. Install Dependencies
```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

### 3. Run Migrations

Phase 2 includes the following migrations (in order):

#### Accounts Payable Migrations (4 files)
1. `2025_08_01_000001_create_suppliers_table.php`
   - Creates `suppliers` table
   - Adds company_id foreign key
   - Indexes for performance

2. `2025_08_01_000002_create_bills_table.php`
   - Creates `bills` table
   - Links to suppliers and companies
   - Status tracking fields

3. `2025_08_01_000003_create_bill_items_table.php`
   - Creates `bill_items` table
   - Links to bills and items
   - Tax and discount calculations

4. `2025_08_01_000004_create_bill_payments_table.php`
   - Creates `bill_payments` table
   - Payment tracking for bills
   - Links to payment methods

#### Proforma Invoice Migrations (2 files)
5. `2025_08_02_000001_create_proforma_invoices_table.php`
   - Creates `proforma_invoices` table
   - Similar structure to invoices
   - Expiry and validity tracking

6. `2025_08_02_000002_create_proforma_invoice_items_table.php`
   - Creates `proforma_invoice_items` table
   - Line items for proforma invoices

Run all migrations:
```bash
php artisan migrate --force
```

### 4. Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
```

### 5. Seed Default Data (Optional)

If seeding is available:
```bash
php artisan db:seed --class=Phase2Seeder
```

This will create:
- Default supplier categories (if applicable)
- Sample bill statuses
- Sample proforma invoice statuses

### 6. Set Permissions

Ensure storage directories are writable:
```bash
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chown -R www-data:www-data storage/
chown -R www-data:www-data bootstrap/cache/
```

### 7. Disable Maintenance Mode
```bash
php artisan up
```

## Verification Checklist

### Database Verification
- [ ] All 6 Phase 2 migrations are listed in `migrations` table
- [ ] Tables exist: `suppliers`, `bills`, `bill_items`, `bill_payments`, `proforma_invoices`, `proforma_invoice_items`
- [ ] Foreign key constraints are active (check with `SHOW CREATE TABLE`)

### Application Verification
- [ ] Log in successfully
- [ ] Navigate to Suppliers section (if UI is deployed)
- [ ] Navigate to Bills section (if UI is deployed)
- [ ] Navigate to Proforma Invoices section (if UI is deployed)
- [ ] Navigate to Audit Logs section (if UI is deployed)
- [ ] Check for any PHP errors in logs: `tail -f storage/logs/laravel.log`

### API Verification

Test each endpoint:

```bash
# Set your auth token and company ID
TOKEN="your_auth_token"
COMPANY_ID="your_company_id"

# Test Suppliers endpoints
curl -H "Authorization: Bearer $TOKEN" -H "company: $COMPANY_ID" \
  http://yourdomain.com/api/v1/suppliers

# Test Bills endpoints
curl -H "Authorization: Bearer $TOKEN" -H "company: $COMPANY_ID" \
  http://yourdomain.com/api/v1/bills

# Test Proforma Invoices endpoints
curl -H "Authorization: Bearer $TOKEN" -H "company: $COMPANY_ID" \
  http://yourdomain.com/api/v1/proforma-invoices

# Test Audit Logs endpoints
curl -H "Authorization: Bearer $TOKEN" -H "company: $COMPANY_ID" \
  http://yourdomain.com/api/v1/audit-logs
```

### Smoke Tests

#### Test 1: Create Supplier
1. Navigate to Suppliers
2. Click "Add Supplier"
3. Fill in required fields:
   - Name
   - Email
   - Currency
   - Company information
4. Save supplier
5. Verify supplier appears in list
6. Check audit log for "created" event

#### Test 2: Create Bill
1. Navigate to Bills
2. Click "Add Bill"
3. Select a supplier
4. Add line items
5. Set due date
6. Save bill
7. Verify bill appears in list
8. Verify status is "DRAFT"
9. Check audit log for "created" event

#### Test 3: Create Bill Payment
1. Open an existing bill
2. Click "Add Payment"
3. Enter payment amount
4. Select payment method
5. Save payment
6. Verify bill status updates if fully paid
7. Check audit log for payment events

#### Test 4: Create Proforma Invoice
1. Navigate to Proforma Invoices
2. Click "Add Proforma Invoice"
3. Select a customer
4. Add line items
5. Set valid until date
6. Save proforma invoice
7. Verify it appears in list
8. Test "Convert to Invoice" functionality
9. Check audit log for "created" and "converted" events

#### Test 5: View Audit Logs
1. Navigate to Audit Logs
2. Verify all previous actions are logged
3. Filter by:
   - User
   - Date range
   - Document type (supplier, bill, proforma_invoice)
4. View individual audit log details
5. Verify PII fields are encrypted/masked

### Performance Tests
- [ ] List view loads within 2 seconds for 100+ records
- [ ] PDF generation works for bills and proforma invoices
- [ ] Audit log queries are performant (< 1 second for 1000+ records)
- [ ] Email sending works for bills (if enabled)

## Rollback Plan

If issues occur during deployment, follow these steps to rollback:

### 1. Enable Maintenance Mode
```bash
php artisan down
```

### 2. Restore Database Backup
```bash
# Stop application server
sudo service nginx stop  # or apache2

# Restore database
mysql -u username -p database_name < backup_before_phase2.sql

# Or using mysqldump format
gunzip < backup_before_phase2.sql.gz | mysql -u username -p database_name
```

### 3. Rollback Migrations (Alternative to DB Restore)
```bash
# Rollback all Phase 2 migrations
php artisan migrate:rollback --step=6
```

This will rollback in reverse order:
1. Drop `proforma_invoice_items` table
2. Drop `proforma_invoices` table
3. Drop `bill_payments` table
4. Drop `bill_items` table
5. Drop `bills` table
6. Drop `suppliers` table

### 4. Restore Code
```bash
# Checkout previous release tag (Phase 1)
git checkout phase-1-release

# Reinstall dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize
```

### 5. Verify Rollback
- [ ] Application loads without errors
- [ ] Phase 1 features still work
- [ ] Database is intact
- [ ] No Phase 2 tables exist

### 6. Disable Maintenance Mode
```bash
php artisan up
```

### 7. Investigate Issues
- Review logs: `storage/logs/laravel.log`
- Check migration errors
- Verify database constraints
- Test in staging environment before retry

## Post-Deployment Tasks

### 1. Update User Permissions
Grant users access to new features through the Roles & Permissions UI:
- Suppliers (view, create, edit, delete)
- Bills (view, create, edit, delete, send)
- Proforma Invoices (view, create, edit, delete, send, convert)
- Audit Logs (view)

### 2. Configure Email Templates (if applicable)
- Bill notification template
- Proforma invoice template
- Customize branding and messaging

### 3. Set Up Document Numbering
Configure automatic numbering sequences for:
- Bills (e.g., BILL-{YYYY}-{SEQUENCE})
- Proforma Invoices (e.g., PI-{YYYY}-{SEQUENCE})

### 4. Monitor System
- Check error logs daily for first week: `tail -f storage/logs/laravel.log`
- Monitor database growth
- Review audit log size and consider archival strategy
- Monitor API response times

### 5. Train Users
- Provide documentation for new features
- Conduct training sessions
- Create video tutorials (optional)
- Update help documentation

### 6. Set Up Automated Backups
Ensure backups include new Phase 2 data:
```bash
# Example backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u username -p database_name > backup_phase2_$DATE.sql
gzip backup_phase2_$DATE.sql
```

### 7. Configure Audit Log Retention
Consider implementing audit log archival:
- Keep 90 days in primary database
- Archive older logs to separate table/storage
- Implement cleanup job if needed

## Known Issues & Limitations

### Current Limitations
- Bill payments cannot be edited after creation (by design)
- Proforma invoices must be in DRAFT status to convert to invoice
- Audit logs cannot be deleted (compliance requirement)
- PDF templates for bills and proforma invoices use invoice template (temporary)

### Workarounds
- To "edit" a bill payment, delete and recreate it
- For custom PDF templates, customize in settings after deployment
- For audit log cleanup, contact support for manual intervention

## Support & Troubleshooting

### Common Issues

**Issue: Migration fails with "errno 150" foreign key error**
- Cause: Collation mismatch between tables
- Solution: Check all migrations use `utf8mb4` charset
- Fix: Add `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4` to migrations

**Issue: Routes not found (404 errors)**
- Cause: Route cache not cleared
- Solution: Run `php artisan route:clear && php artisan optimize`

**Issue: Abilities not appearing in Roles UI**
- Cause: Config cache not cleared
- Solution: Run `php artisan config:clear && php artisan cache:clear`

**Issue: Audit logs showing encrypted PII**
- Cause: Expected behavior for security
- Solution: Use `getDecryptedOldValues()` and `getDecryptedNewValues()` methods

**Issue: Bill status not updating after payment**
- Cause: Observer not registered
- Solution: Verify `BillPaymentObserver` is registered in `AppServiceProvider`

### Getting Help
- Check application logs: `storage/logs/laravel.log`
- Review migration status: `php artisan migrate:status`
- Verify database structure: `SHOW CREATE TABLE suppliers;`
- Contact development team with error details

## Version Information
- Phase 2 Version: 1.0.0
- Deployment Date: [To be filled during deployment]
- Deployed By: [To be filled during deployment]
- Server Environment: [Production/Staging]

## Sign-Off

- [ ] Database migrations successful
- [ ] All verification tests passed
- [ ] Smoke tests completed
- [ ] User permissions configured
- [ ] Documentation updated
- [ ] Team notified of deployment

**Deployed By:** ___________________
**Date:** ___________________
**Time:** ___________________
**Sign-off:** ___________________

---

**Note:** This deployment guide is for Phase 2 only. For Phase 1 deployment, refer to `DEPLOYMENT_PHASE1.md`.

// CLAUDE-CHECKPOINT
