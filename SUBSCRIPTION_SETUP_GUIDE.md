# Subscription Billing Setup Guide (B-31 Series)

## Overview

This implementation provides complete subscription billing functionality for Facturino v1 with support for both Paddle and CPAY payment providers.

## Features Implemented

### Phase 1: Paddle Integration (B-31-00 to B-31-05)

1. **Cashier Paddle Migrations** (B-31-00)
   - Published and customized Laravel Cashier Paddle migrations
   - Added `customers`, `subscriptions`, `subscription_items`, `transactions` tables
   - Added custom columns for Facturino-specific tracking:
     - `companies.paddle_id`, `subscription_tier`, `trial_ends_at`
     - `subscriptions.provider`, `tier`, `monthly_price`, `metadata`
     - `users.paddle_id`, `partner_subscription_tier`, `partner_trial_ends_at`

2. **Billable Trait Integration** (B-31-01)
   - Added Paddle `Billable` trait to `Company` model for company subscriptions
   - Added Paddle `Billable` trait to `User` model for Partner Plus subscriptions
   - Updated fillable fields for subscription tracking

3. **Company Subscription Controller** (B-31-02)
   - File: `/modules/Mk/Billing/Controllers/SubscriptionController.php`
   - Endpoints:
     - `GET /api/companies/{company}/subscription` - View current plan
     - `POST /api/companies/{company}/subscription/checkout` - Create checkout session
     - `GET /api/companies/{company}/subscription/success` - Post-checkout success
     - `GET /api/companies/{company}/subscription/manage` - Manage subscription
     - `POST /api/companies/{company}/subscription/swap` - Upgrade/downgrade plan
     - `POST /api/companies/{company}/subscription/cancel` - Cancel subscription
     - `POST /api/companies/{company}/subscription/resume` - Resume cancelled subscription

4. **Paddle Webhook Handler** (B-31-03)
   - File: `/modules/Mk/Billing/Controllers/PaddleWebhookController.php`
   - Extends Cashier's webhook controller
   - Handles subscription lifecycle events:
     - `subscription.created` - Creates subscription record, updates company tier
     - `subscription.updated` - Updates subscription status
     - `subscription.payment_succeeded` - Triggers partner commission calculation
     - `subscription.canceled` - Downgrades company to free tier
     - `transaction.completed` - Handles one-time payments

5. **Partner Plus Subscription** (B-31-04)
   - File: `/modules/Mk/Partner/Controllers/PartnerSubscriptionController.php`
   - Separate subscription flow for accountants
   - €29/month for enhanced 22% commission rate (vs 18% free)
   - Similar endpoints to company subscriptions but under `/api/partner/subscription`

6. **Pricing Pages UI** (B-31-05)
   - File: `/resources/js/pages/pricing/Companies.vue`
     - 5-tier comparison table (Free, Starter, Standard, Business, Max)
     - Responsive grid layout with feature lists
     - Inline checkout integration
   - File: `/resources/js/pages/pricing/Partners.vue`
     - Partner (Free) vs Partner Plus comparison
     - Commission calculator showing ROI
     - Highlights 4% commission increase benefit

### Phase 2: CPAY Integration (B-31-10 to B-31-12)

1. **CPAY Driver Extensions** (B-31-10)
   - File: `/Modules/Mk/Services/CpayDriver.php`
   - Added methods:
     - `createSubscription()` - Generate recurring payment checkout URL
     - `cancelSubscription()` - Cancel recurring payment agreement
   - Uses CPAY API for subscription management

2. **CPAY Webhook Handler** (B-31-11)
   - File: `/modules/Mk/Billing/Controllers/CpayWebhookController.php`
   - Handles CPAY subscription callbacks:
     - `subscription_created` - Creates subscription using Paddle's polymorphic tables
     - `subscription_payment_succeeded` - Triggers commission calculation
     - `subscription_payment_failed` - Marks subscription as past_due
     - `subscription_cancelled` - Downgrades to free tier

3. **Unified Subscription Service** (B-31-12)
   - File: `/app/Services/SubscriptionService.php`
   - Abstraction layer for both Paddle and CPAY
   - Methods:
     - `createCompanySubscription($company, $tier, $provider)`
     - `createPartnerPlusSubscription($user, $provider)`
     - `swapPlan($subscription, $newTier)`
     - `cancelSubscription($subscription)`
   - Automatic provider detection and routing

## Subscription Tiers & Pricing

### Company Subscriptions
- **Free**: €0/month - Basic invoicing, up to 5 clients
- **Starter**: €12/month - Unlimited clients, estimates, recurring invoices
- **Standard**: €29/month - Expenses, reports, bank sync
- **Business**: €59/month - Multi-currency, custom fields, team collaboration
- **Max**: €149/month - API access, white-label, dedicated support

### Partner Subscriptions
- **Partner (Free)**: 18% commission rate
- **Partner Plus**: €29/month - 22% commission rate (+4% increase)

## Database Schema

### Tables Created
1. `customers` - Paddle customer records (polymorphic billable)
2. `subscriptions` - Subscription records with provider tracking
3. `subscription_items` - Subscription line items
4. `transactions` - Payment transactions

### Custom Columns
- `companies.paddle_id` - Paddle customer ID
- `companies.subscription_tier` - Current tier (free, starter, standard, business, max)
- `companies.trial_ends_at` - Trial expiration
- `subscriptions.provider` - Payment provider (paddle or cpay)
- `subscriptions.tier` - Subscription tier
- `subscriptions.monthly_price` - Monthly price in EUR
- `subscriptions.metadata` - JSON metadata (includes CPAY subscription_ref)

## API Routes

### Company Subscriptions
```
GET    /api/companies/{company}/subscription
POST   /api/companies/{company}/subscription/checkout
GET    /api/companies/{company}/subscription/success
GET    /api/companies/{company}/subscription/manage
POST   /api/companies/{company}/subscription/swap
POST   /api/companies/{company}/subscription/cancel
POST   /api/companies/{company}/subscription/resume
```

### Partner Plus Subscriptions
```
GET    /api/partner/subscription
POST   /api/partner/subscription/checkout
GET    /api/partner/subscription/success
GET    /api/partner/subscription/manage
POST   /api/partner/subscription/cancel
POST   /api/partner/subscription/resume
```

### Webhooks (No Auth)
```
POST   /api/webhooks/paddle/subscription
POST   /api/webhooks/cpay/subscription
```

## Environment Configuration

Add to `.env`:

```bash
# Paddle Billing Configuration
PADDLE_VENDOR_ID=your_vendor_id
PADDLE_API_KEY=your_api_key
PADDLE_WEBHOOK_SECRET=your_webhook_secret
PADDLE_ENVIRONMENT=sandbox  # or production

# Subscription Price IDs (get from Paddle dashboard)
PADDLE_PRICE_STARTER=pri_xxxxx
PADDLE_PRICE_STANDARD=pri_xxxxx
PADDLE_PRICE_BUSINESS=pri_xxxxx
PADDLE_PRICE_MAX=pri_xxxxx
PADDLE_PRICE_PARTNER_PLUS=pri_xxxxx

# CPAY Configuration (optional - for Macedonian market)
# Already configured in mk.payment_gateways.cpay
```

## Setup Instructions

1. **Run Migrations**
   ```bash
   php artisan migrate
   ```

2. **Configure Paddle**
   - Create products and prices in Paddle dashboard
   - Update `.env` with price IDs
   - Configure webhook endpoint: `https://yourapp.com/api/webhooks/paddle/subscription`

3. **Configure CPAY** (optional)
   - Already configured in `config/mk.php`
   - Update merchant credentials in `.env`

4. **Add CSRF Exemptions**
   The webhook routes are already exempt from CSRF in the routes file.

5. **Test Subscriptions**
   - Use Paddle sandbox mode for testing
   - Test checkout flow
   - Verify webhook handling
   - Test plan upgrades/downgrades
   - Test cancellation and resumption

## Commission Integration

When a subscription payment succeeds (via Paddle or CPAY):
1. Webhook handler detects the payment
2. Retrieves active partners for the company
3. Calculates commission based on partner tier:
   - Free partners: 18% commission
   - Partner Plus: 22% commission
4. Creates commission record via `CommissionService` (if available)

## Files Created/Modified

### New Files
- `/database/migrations/2019_05_03_000001_create_customers_table.php`
- `/database/migrations/2019_05_03_000002_create_subscriptions_table.php`
- `/database/migrations/2019_05_03_000003_create_subscription_items_table.php`
- `/database/migrations/2019_05_03_000004_create_transactions_table.php`
- `/database/migrations/2025_08_01_100000_add_paddle_columns_to_companies_table.php`
- `/database/migrations/2025_08_01_100001_add_subscription_metadata_to_subscriptions_table.php`
- `/database/migrations/2025_08_01_100002_add_partner_subscription_columns_to_users_table.php`
- `/modules/Mk/Billing/Controllers/SubscriptionController.php`
- `/modules/Mk/Billing/Controllers/PaddleWebhookController.php`
- `/modules/Mk/Billing/Controllers/CpayWebhookController.php`
- `/modules/Mk/Partner/Controllers/PartnerSubscriptionController.php`
- `/app/Services/SubscriptionService.php`
- `/resources/js/pages/pricing/Companies.vue`
- `/resources/js/pages/pricing/Partners.vue`

### Modified Files
- `/app/Models/Company.php` - Added Billable trait, fillable fields
- `/app/Models/User.php` - Added Billable trait
- `/Modules/Mk/Services/CpayDriver.php` - Added subscription methods
- `/config/services.php` - Added Paddle price configuration
- `/routes/api.php` - Added subscription routes

## Next Steps

1. **Create Paddle Products**: Set up products and prices in Paddle dashboard
2. **Update Price IDs**: Replace placeholder price IDs in `.env`
3. **Test Flows**: Test complete checkout and subscription management flows
4. **Frontend Integration**: Integrate pricing pages into main application
5. **Authorization**: Add proper authorization policies for subscription management
6. **Feature Gating**: Implement feature access control based on subscription tier

## Testing Checklist

- [ ] Paddle sandbox checkout works
- [ ] Subscription is created on successful payment
- [ ] Company tier is updated correctly
- [ ] Webhooks are processed correctly
- [ ] Plan upgrades work with proration
- [ ] Plan downgrades work
- [ ] Subscription cancellation works
- [ ] Subscription resumption works
- [ ] Partner Plus checkout works
- [ ] Commission calculation triggers on payment
- [ ] CPAY integration works (if configured)

## Support

For issues or questions:
- Check Paddle documentation: https://developer.paddle.com/
- Review Laravel Cashier Paddle docs: https://laravel.com/docs/11.x/cashier-paddle
- Check CPAY integration documentation (Macedonian market)

---

**Implementation Status**: Phase 1 Complete ✓ | Phase 2 Complete ✓

**Agent**: AGENT 2 - Subscription Billing Integration
**Ticket Series**: B-31-00 to B-31-12
**Date**: 2025-11-14
