# UsageLimitService Implementation

This document describes the UsageLimitService implementation for tracking and enforcing subscription tier limits in Facturino.

## Files Created

### 1. Service: `/app/Services/UsageLimitService.php`
Main service class that provides methods to:
- Check if a company can use a feature (`canUse()`)
- Get current usage statistics (`getUsage()`)
- Increment usage counters (`incrementUsage()`)
- Decrement usage counters (`decrementUsage()`)
- Get company subscription tier (`getCompanyTier()`)
- Get all usage data for a company (`getAllUsage()`)

### 2. Model: `/app/Models/UsageTracking.php`
Eloquent model for the `usage_tracking` table with:
- Relationships to Company model
- Scopes for filtering by month, feature, and company
- Helper method to check if tracking is monthly

### 3. Migration: `/database/migrations/2025_12_14_120001_create_usage_tracking_table.php`
Creates the `usage_tracking` table with:
- Idempotent design using `Schema::hasTable()` check
- InnoDB engine with utf8mb4 charset (MySQL only)
- Foreign key to companies table with cascade delete
- Unique constraint on company_id + feature + period
- Indexes for performance

### 4. Tests: `/tests/Unit/Services/UsageLimitServiceTest.php`
Comprehensive test suite with 12 tests covering:
- Tier detection
- Usage increment/decrement
- Limit checking
- Monthly vs total feature tracking
- Unlimited tier handling
- Real-time counting for custom fields and recurring invoices

### 5. Examples: `/app/Services/UsageLimitServiceExample.php`
Documentation file showing usage patterns for:
- Checking limits before creating resources
- Incrementing/decrementing usage
- Building usage dashboards
- Controller integration examples

## Database Schema

```sql
CREATE TABLE usage_tracking (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id BIGINT UNSIGNED NOT NULL,
    feature VARCHAR(100) NOT NULL,
    count INT UNSIGNED DEFAULT 0,
    period VARCHAR(20) NOT NULL,  -- 'YYYY-MM' or 'total'
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    UNIQUE KEY usage_tracking_unique (company_id, feature, period),
    KEY usage_tracking_company_period (company_id, period)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## Supported Features

The service tracks the following features from `config/subscriptions.php`:

### Monthly Limits (reset each month)
- `expenses_per_month` - Number of expenses created per month
- `estimates_per_month` - Number of estimates created per month
- `ai_queries_per_month` - Number of AI queries per month

### Total Limits (cumulative)
- `custom_fields` - Total custom fields (counted from database)
- `recurring_invoices_active` - Active recurring invoices (counted from database)

## Usage Examples

### Check if company can create an expense
```php
$usageService = app(UsageLimitService::class);
$company = auth()->user()->currentCompany();

if (!$usageService->canUse($company, 'expenses_per_month')) {
    // Show upgrade prompt
    return response()->json([
        'error' => 'Monthly expense limit reached',
        'usage' => $usageService->getUsage($company, 'expenses_per_month')
    ], 403);
}
```

### Increment usage after creating resource
```php
// After creating expense
$usageService->incrementUsage($company, 'expenses_per_month');
```

### Get usage statistics
```php
$usage = $usageService->getUsage($company, 'expenses_per_month');
// Returns: [
//     'used' => 3,
//     'limit' => 5,
//     'remaining' => 2
// ]
```

### Get all usage for dashboard
```php
$allUsage = $usageService->getAllUsage($company);
// Returns array with usage for all tracked features
```

## Configuration

Feature limits are defined in `/config/subscriptions.php` under each tier's `limits` array:

```php
'tiers' => [
    'free' => [
        'limits' => [
            'expenses_per_month' => 5,
            'custom_fields' => 2,
            'recurring_invoices_active' => 1,
            'estimates_per_month' => 3,
            'ai_queries_per_month' => 3,
        ],
    ],
    'starter' => [
        'limits' => [
            'expenses_per_month' => 50,
            'custom_fields' => 5,
            'recurring_invoices_active' => 5,
            'estimates_per_month' => 20,
        ],
    ],
    // ... other tiers
]
```

Use `null` for unlimited features.

## Testing

Run the test suite:
```bash
php artisan test --filter=UsageLimitServiceTest
```

All 12 tests should pass.

## Migration

The migration is idempotent and safe to run multiple times:
```bash
php artisan migrate
```

It will only create the table if it doesn't exist.

## Company Model Integration

The `Company` model has been updated with a relationship:
```php
public function usageTracking(): HasMany
{
    return $this->hasMany(UsageTracking::class);
}
```

## Notes

1. **Monthly Limits**: Usage resets automatically at the start of each month (tracked by period 'YYYY-MM')
2. **Total Limits**: Custom fields and recurring invoices are counted in real-time from the database
3. **Unlimited Tiers**: When a limit is `null`, `canUse()` always returns `true`
4. **Performance**: Indexes on `company_id` and `period` ensure fast lookups
5. **Data Integrity**: Foreign key cascade ensures cleanup when companies are deleted

## Future Enhancements

Consider adding:
- Scheduled job to clean up old monthly tracking data (optional, for storage optimization)
- Events dispatched when limits are reached
- Notification system for approaching limits
- Admin dashboard to view usage across all companies
