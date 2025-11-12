# Phase 3-4 Deployment Guide

## Overview

This guide covers the deployment of Phase 3 (Banking & Reconciliation) and Phase 4 (Approvals, Exports, Webhooks, Recurring Expenses) features for Facturino.

## Prerequisites

- Phase 1 & 2 features deployed and operational
- PHP 8.1+ with required extensions
- MySQL/MariaDB database
- Composer and NPM installed
- Laravel scheduler configured
- Queue worker running (database or Redis)

## Phase 3: Banking & Reconciliation

### 1. PSD2 Gateway Service

The PSD2 Gateway is a separate service that handles OAuth flows and token management for Macedonian banks.

#### Starting the PSD2 Gateway (Docker)

```bash
cd services/psd2-gateway
docker-compose -f docker-compose.psd2.yml up -d
```

#### Environment Variables

Add to your `.env`:

```env
# PSD2 Gateway Service
PSD2_GATEWAY_BASE_URL=http://psd2-gateway:8080
PSD2_REDIRECT_URI=https://your-domain.com/api/v1/bank/oauth/callback

# NLB Bank
NLB_CLIENT_ID=your_nlb_client_id
NLB_CLIENT_SECRET=your_nlb_client_secret

# Stopanska Bank
STOPANSKA_CLIENT_ID=your_stopanska_client_id
STOPANSKA_CLIENT_SECRET=your_stopanska_client_secret

# Komercijalna Bank
KOMERCIJALNA_CLIENT_ID=your_komercijalna_client_id
KOMERCIJALNA_CLIENT_SECRET=your_komercijalna_client_secret

# Reconciliation Settings
RECONCILIATION_DATE_TOLERANCE=3
```

### 2. Database Migrations

Run migrations to create the required tables:

```bash
php artisan migrate --force
```

This creates:
- `bank_providers` - Supported bank providers
- `bank_connections` - User bank account connections
- `bank_consents` - PSD2 consent tracking
- `bank_accounts` - Synchronized bank account data
- `bank_transactions` - Transaction data from banks

### 3. Seed Bank Providers

Populate the supported bank providers:

```bash
php artisan db:seed --class=BankProviderSeeder
```

This seeds:
- NLB Banka AD Skopje
- Stopanska Banka AD Skopje
- Komercijalna Banka AD Skopje

### 4. Test Banking Integration

```bash
# Test OAuth flow
curl -X POST https://your-domain.com/api/v1/bank/oauth/start \
  -H "Authorization: Bearer {token}" \
  -H "company: {company_id}" \
  -d '{"provider_id": 1}'

# Test bank account sync
curl -X POST https://your-domain.com/api/v1/banking/sync/{account_id} \
  -H "Authorization: Bearer {token}" \
  -H "company: {company_id}"
```

## Phase 4: Approvals, Exports, Webhooks

### 1. Database Migrations

Run migrations for Phase 4 features:

```bash
php artisan migrate --force
```

This creates:
- `approval_requests` - Document approval workflow
- `export_jobs` - Export job tracking
- `gateway_webhook_events` - Webhook event logs
- `recurring_expenses` - Recurring expense templates

### 2. Environment Variables

Add to your `.env`:

```env
# Export Configuration
EXPORT_DISK=local
EXPORT_RETENTION_DAYS=7

# Webhook Event Storage
WEBHOOK_RETENTION_DAYS=30

# Recurring Expense Processing
RECURRING_EXPENSE_CHECK_INTERVAL=60
```

### 3. Configure Storage for Exports

Ensure the export storage disk is configured:

```bash
# Create storage directory
mkdir -p storage/app/exports
chmod -R 775 storage/app/exports
```

### 4. Laravel Scheduler

Ensure the Laravel scheduler is running for recurring expense processing:

```cron
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### 5. Webhook Endpoints

The following webhook endpoints are now available (no authentication required):

- `POST /api/v1/webhooks/paddle` - Paddle payment webhooks
- `POST /api/v1/webhooks/cpay` - CPAY payment webhooks
- `POST /api/v1/webhooks/bank/nlb` - NLB bank webhooks
- `POST /api/v1/webhooks/bank/stopanska` - Stopanska bank webhooks

#### CSRF Exemption

Add webhook routes to `app/Http/Middleware/VerifyCsrfToken.php`:

```php
protected $except = [
    'api/v1/webhooks/*',
];
```

### 6. Test Phase 4 Features

#### Test Approvals

```bash
# Request approval
curl -X POST https://your-domain.com/api/v1/approvals \
  -H "Authorization: Bearer {token}" \
  -H "company: {company_id}" \
  -d '{"document_type": "invoice", "document_id": 123}'
```

#### Test Exports

```bash
# Create export
curl -X POST https://your-domain.com/api/v1/exports \
  -H "Authorization: Bearer {token}" \
  -H "company: {company_id}" \
  -d '{"type": "invoices", "format": "csv"}'
```

#### Test Recurring Expenses

```bash
# Create recurring expense
curl -X POST https://your-domain.com/api/v1/recurring-expenses \
  -H "Authorization: Bearer {token}" \
  -H "company: {company_id}" \
  -d '{"frequency": "monthly", "amount": 1000, "category_id": 1}'
```

## Permissions & Abilities

### New Bouncer Abilities

The following abilities have been added:

**Banking:**
- `connect-bank` - Connect bank accounts
- `view-bank-transactions` - View bank transactions

**Reconciliation:**
- `view-reconciliation` - View reconciliation data
- `approve-reconciliation` - Approve reconciliations

**Approvals:**
- `request-approval` - Request document approvals
- `approve-document` - Approve documents
- `view-all-approvals` - View all approval requests

**Exports:**
- `create-export` - Create data exports

**Recurring Expenses:**
- `view-recurring-expense` - View recurring expenses
- `create-recurring-expense` - Create recurring expenses
- `edit-recurring-expense` - Edit recurring expenses
- `delete-recurring-expense` - Delete recurring expenses

### Assign Abilities to Roles

```php
// Example: Assign to admin role
$admin = Role::where('name', 'admin')->first();
$admin->allow('connect-bank');
$admin->allow('approve-reconciliation');
$admin->allow('approve-document');
$admin->allow('create-export');
```

## Monitoring & Troubleshooting

### Check PSD2 Gateway Health

```bash
curl http://psd2-gateway:8080/health
```

### View Recent Webhooks

```bash
php artisan tinker
>>> \App\Models\GatewayWebhookEvent::latest()->take(10)->get()
```

### Check Recurring Expense Queue

```bash
php artisan queue:work --queue=recurring-expenses
```

### View Export Jobs

```bash
php artisan tinker
>>> \App\Models\ExportJob::latest()->get()
```

## Cleanup & Maintenance

### Cleanup Old Exports

Exports are automatically cleaned up based on `EXPORT_RETENTION_DAYS`. To manually clean up:

```bash
php artisan exports:cleanup
```

### Cleanup Old Webhook Events

```bash
php artisan webhooks:cleanup
```

## Rollback Plan

If issues occur, rollback migrations in reverse order:

```bash
# Rollback Phase 4
php artisan migrate:rollback --step=4

# Rollback Phase 3
php artisan migrate:rollback --step=1
```

## Security Considerations

1. **PSD2 Tokens**: Bank access tokens are encrypted in the database
2. **Webhook Signatures**: All webhooks should verify signatures
3. **CSRF Exemption**: Only exempt the specific webhook routes
4. **Rate Limiting**: Apply rate limiting to webhook endpoints if needed
5. **Export Access**: Export download URLs should be time-limited

## Performance Optimization

1. **Queue Configuration**: Use Redis for high-volume deployments
2. **Bank Sync**: Sync transactions in background jobs
3. **Export Generation**: Generate large exports asynchronously
4. **Webhook Processing**: Process webhooks in queued jobs

## Support & Documentation

- Banking API: See `docs/banking-integration.md`
- Reconciliation: See `docs/reconciliation-guide.md`
- Webhooks: See `docs/webhook-integration.md`
- Exports: See `docs/export-formats.md`

---

**Deployment Status**: ✅ Ready for Production

**Last Updated**: 2025-11-10

**Version**: Phase 3-4 Integration
