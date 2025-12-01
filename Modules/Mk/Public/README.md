# Public Signup API

Backend API endpoints for public company signup with referral tracking.

## Endpoints

### 1. Validate Referral Code

**POST** `/api/v1/public/signup/validate-referral`

Validates a referral code and returns partner information.

**Request:**
```json
{
  "code": "PARTNER123"
}
```

**Response (Success):**
```json
{
  "success": true,
  "data": {
    "partner_id": 1,
    "partner_name": "John Doe",
    "partner_company": "Accounting Pro",
    "affiliate_link_id": 5
  }
}
```

**Response (Invalid):**
```json
{
  "success": false,
  "message": "Invalid or inactive referral code."
}
```

**Rate Limit:** 30 requests/minute per IP

---

### 2. Get Available Plans

**GET** `/api/v1/public/signup/plans`

Returns available Stripe subscription plans with pricing.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": "starter",
      "name": "Starter",
      "description": "Perfect for small businesses",
      "price_monthly": "price_xxx",
      "price_yearly": "price_yyy",
      "currency": "MKD",
      "features": [
        "Up to 100 invoices per month",
        "Basic estimates and payments",
        "Email support"
      ]
    },
    {
      "id": "standard",
      "name": "Standard",
      "description": "For growing businesses",
      "price_monthly": "price_xxx",
      "price_yearly": "price_yyy",
      "currency": "MKD",
      "features": [
        "Up to 500 invoices per month",
        "E-Invoice (UBL XML)",
        "Recurring invoices",
        "Priority support"
      ]
    }
  ]
}
```

**Rate Limit:** 30 requests/minute per IP

---

### 3. Register Company

**POST** `/api/v1/public/signup/register`

Creates a new company with admin user and redirects to Stripe Checkout.

**Request:**
```json
{
  "company_name": "My Business Ltd",
  "vat_number": "MK12345678",
  "tax_id": "1234567890",
  "name": "John Smith",
  "email": "john@mybusiness.com",
  "password": "SecurePass123!",
  "password_confirmation": "SecurePass123!",
  "plan": "starter",
  "billing_period": "monthly",
  "referral_code": "PARTNER123",
  "accept_terms": true,
  "accept_privacy": true
}
```

**Response (Success):**
```json
{
  "success": true,
  "message": "Registration successful. Redirecting to checkout...",
  "data": {
    "company_id": 42,
    "company_slug": "my-business-ltd",
    "user_id": 123,
    "checkout_url": "https://checkout.stripe.com/c/pay/cs_xxx",
    "checkout_session_id": "cs_xxx"
  }
}
```

**Rate Limit:** 10 requests/minute per IP (strict)

---

## Database Relationships

### Companies → Partners

Companies are linked to partners through the `partner_company_links` pivot table:

```php
// When a company signs up with a referral code:
$company->partners()->attach($partnerId, [
    'is_primary' => true,
    'is_active' => true,
    'permissions' => json_encode(['view_reports', 'manage_invoices']),
]);
```

### Affiliate Links

The `affiliate_links` table tracks referrals:

- `partner_id`: Foreign key to partners table
- `code`: Unique referral code (e.g., "PARTNER123")
- `clicks`: Number of times the link was clicked/validated
- `conversions`: Number of successful signups
- `is_active`: Whether the link is currently active

---

## Stripe Integration

The API uses the raw Stripe PHP SDK (NOT Laravel Cashier-Paddle):

```php
\Stripe\Stripe::setApiKey(config('services.stripe.secret'));

$session = \Stripe\Checkout\Session::create([
    'mode' => 'subscription',
    'customer_email' => $email,
    'line_items' => [...],
    'success_url' => $successUrl,
    'cancel_url' => $cancelUrl,
    'metadata' => [
        'company_id' => $companyId,
        'partner_id' => $partnerId,
        'affiliate_link_id' => $affiliateLinkId,
    ],
    'subscription_data' => [
        'trial_period_days' => 14,
    ],
]);
```

### Configuration

Stripe configuration is in `config/services.php`:

```php
'stripe' => [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'prices' => [
        'starter' => [
            'monthly' => env('STRIPE_PRICE_STARTER_MKD_MONTHLY'),
            'yearly' => env('STRIPE_PRICE_STARTER_MKD_YEARLY'),
        ],
        // ... other plans
    ],
    'currency' => 'mkd',
],
```

---

## Validation Rules

### Password Requirements

Passwords must meet the following criteria:
- Minimum 8 characters
- Mixed case (uppercase and lowercase)
- At least one number
- At least one symbol
- Not compromised (checked against breach database)

### VAT Number Format

VAT numbers must follow EU format: `[A-Z]{2}[0-9]{8,12}`

Example: `MK12345678`

---

## Files Created

1. **SignupController.php** - Handles the HTTP request/response cycle
   - `validateReferral()` - POST endpoint to validate referral codes
   - `getPlans()` - GET endpoint to fetch available plans
   - `register()` - POST endpoint to create company + user + Stripe session

2. **SignupRequest.php** - Form validation rules for registration

3. **SignupService.php** - Business logic for signup flow
   - Validates referral codes from `affiliate_links` table
   - Creates company with partner link via pivot table
   - Creates admin user for company
   - Increments `affiliate_links.clicks` when validated
   - Increments `affiliate_links.conversions` after successful signup
   - Creates Stripe Checkout session for subscription

---

## Security Features

- **Rate Limiting:**
  - Public endpoints: 30 req/min per IP
  - Registration endpoint: 10 req/min per IP (strict)

- **Input Validation:**
  - All inputs validated via FormRequest
  - Password strength requirements enforced
  - Email uniqueness checked

- **CSRF Protection:**
  - Not required (API endpoints, stateless)

- **Database Transactions:**
  - All registration operations wrapped in DB transaction
  - Automatic rollback on failure

---

## Error Handling

All endpoints return consistent JSON responses:

**Success:**
```json
{
  "success": true,
  "data": { ... }
}
```

**Error:**
```json
{
  "success": false,
  "message": "Error description",
  "errors": { ... } // Validation errors if applicable
}
```

HTTP Status Codes:
- 200: Success
- 201: Created (registration)
- 404: Not Found (invalid referral code)
- 422: Validation Error
- 500: Server Error

---

## Testing

Example cURL requests:

### Validate Referral
```bash
curl -X POST http://localhost/api/v1/public/signup/validate-referral \
  -H "Content-Type: application/json" \
  -d '{"code": "PARTNER123"}'
```

### Get Plans
```bash
curl http://localhost/api/v1/public/signup/plans
```

### Register
```bash
curl -X POST http://localhost/api/v1/public/signup/register \
  -H "Content-Type: application/json" \
  -d '{
    "company_name": "Test Company",
    "name": "John Doe",
    "email": "john@test.com",
    "password": "SecurePass123!",
    "password_confirmation": "SecurePass123!",
    "plan": "starter",
    "billing_period": "monthly",
    "accept_terms": true,
    "accept_privacy": true
  }'
```

---

## CLAUDE-CHECKPOINT
