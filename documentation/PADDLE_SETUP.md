# Paddle Dashboard Setup Guide

**Facturino Billing Configuration**

This guide walks you through setting up your Paddle account, configuring products, prices, webhooks, and API credentials for the Facturino billing system.

**Last Updated:** November 14, 2025
**Version:** 1.0
**Prerequisites:** Admin access to Paddle Dashboard
**Estimated Time:** 45-60 minutes

---

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Product Catalog Setup](#product-catalog-setup)
3. [Price ID Configuration](#price-id-configuration)
4. [Webhook Configuration](#webhook-configuration)
5. [API Credentials](#api-credentials)
6. [Environment Variables](#environment-variables)
7. [Testing](#testing)
8. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### 1. Create Paddle Account

1. Visit [https://vendors.paddle.com/signup](https://vendors.paddle.com/signup)
2. Complete the registration form:
   - Business email address
   - Company name: **Your Legal Entity Name**
   - Country: **Macedonia** (or your business location)
   - Tax ID: **Your Macedonian Tax ID (ЕМБС/ЕМБГ)**
3. Verify your email address
4. Complete the onboarding questionnaire

`[Screenshot: Paddle Sign Up Page]`

### 2. Business Verification Requirements

Paddle requires business verification before you can accept live payments:

**Required Documents:**
- ✅ Company registration certificate (Решение за регистрација)
- ✅ Tax registration document (ЕМБС)
- ✅ Bank account statement (showing company name and IBAN)
- ✅ Director's ID or passport
- ✅ Proof of business address (utility bill or lease agreement)

**Verification Process:**
1. Navigate to **Settings → Business Details**
2. Upload required documents
3. Wait 2-5 business days for review
4. Check email for approval or additional requests

`[Screenshot: Paddle Dashboard → Settings → Business Details]`

### 3. Tax Settings for EU/Macedonia

**Important for Macedonian Businesses:**

Macedonia is **not** part of the EU VAT system, but you may need to handle EU VAT for customers in EU countries.

1. Go to **Settings → Tax Settings**
2. Configure:
   - **Business Country:** Macedonia
   - **VAT Number:** Enter your Macedonian VAT number if registered
   - **EU VAT Handling:** Enable if selling to EU customers
   - **Reverse Charge:** Enable for B2B EU sales
3. Set up tax thresholds:
   - Macedonia: 0% (or local VAT rate if applicable)
   - EU Countries: Automatic VAT calculation based on customer location
4. Enable **Paddle Tax** for automatic tax calculation

`[Screenshot: Paddle Dashboard → Settings → Tax Settings]`

---

## Product Catalog Setup

You need to create **6 products** in Paddle:
- 5 Company Plans (Free, Starter, Standard, Business, Max)
- 1 Partner Plan (Partner Plus)

### Creating Company Products

#### Product 1: Free Plan

1. Go to **Catalog → Products → + New Product**
2. Fill in details:

```
Product Name: Facturino Free
Product Description: Perfect for freelancers and solopreneurs just getting started

Features:
• 1 company account
• 10 invoices per month
• Basic templates
• Email support
• Macedonian language support
• Basic reporting

Type: Standard
Tax Category: SaaS (Software as a Service)
```

3. Click **Save Product**
4. **Copy the Product ID** (e.g., `pro_01h1234abcd`) → You'll need this for `.env`

`[Screenshot: Paddle Dashboard → Products → New Product Form]`

#### Product 2: Starter Plan (€12/month)

```
Product Name: Facturino Starter
Product Description: Ideal for small businesses with growing invoicing needs

Features:
• 1 company account
• Unlimited invoices
• Custom templates
• Email & chat support
• Bank feed integration (1 account)
• Advanced reporting
• Multi-currency support
• Recurring invoices

Type: Standard
Tax Category: SaaS (Software as a Service)
```

**Copy Product ID:** `PADDLE_PRODUCT_STARTER`

#### Product 3: Standard Plan (€29/month)

```
Product Name: Facturino Standard
Product Description: Complete invoicing and accounting solution for SMEs

Features:
• Up to 3 company accounts
• Unlimited invoices & estimates
• Priority support
• Bank feed integration (3 accounts)
• Expense tracking
• Inventory management
• Client portal access
• QES-signed e-Invoices
• CASYS payment links

Type: Standard
Tax Category: SaaS (Software as a Service)
```

**Copy Product ID:** `PADDLE_PRODUCT_STANDARD`

#### Product 4: Business Plan (€59/month)

```
Product Name: Facturino Business
Product Description: Enterprise features for growing businesses

Features:
• Up to 10 company accounts
• All Standard features
• Multi-user access (5 users)
• Advanced automation
• Custom workflows
• API access
• Dedicated account manager
• White-label options
• Advanced reporting & analytics

Type: Standard
Tax Category: SaaS (Software as a Service)
```

**Copy Product ID:** `PADDLE_PRODUCT_BUSINESS`

#### Product 5: Max Plan (€149/month)

```
Product Name: Facturino Max
Product Description: Ultimate solution for accountants and large enterprises

Features:
• Unlimited company accounts
• All Business features
• Unlimited users
• Priority API access
• Custom integrations
• SLA guarantees
• On-premise deployment option
• Advanced security features
• Bulk operations & batch processing

Type: Standard
Tax Category: SaaS (Software as a Service)
```

**Copy Product ID:** `PADDLE_PRODUCT_MAX`

### Creating Partner Product

#### Product 6: Partner Plus (€29/month)

```
Product Name: Facturino Partner Plus
Product Description: Enhanced dashboard and tools for accounting partners

Features:
• Partner referral dashboard
• Commission tracking
• Multi-client management
• Bulk client onboarding
• Partner training resources
• Co-branded materials
• Priority partner support
• Advanced analytics

Type: Standard
Tax Category: SaaS (Software as a Service)
```

**Copy Product ID:** `PADDLE_PRODUCT_PARTNER_PLUS`

`[Screenshot: Paddle Dashboard → Products → List of All Products]`

---

## Price ID Configuration

For each product, you need to create **Price IDs** for different billing intervals.

### Creating Prices

#### Example: Starter Plan Prices

1. Go to **Catalog → Products**
2. Click on **Facturino Starter**
3. Navigate to **Prices** tab
4. Click **+ Add Price**

**Monthly Price:**
```
Description: Starter Monthly
Billing Interval: Monthly
Amount: €12.00
Currency: EUR
Trial Period: 14 days (optional)
```

5. Click **Save**
6. **Copy the Price ID** (e.g., `pri_01h5678efgh`)
   - This is your `PADDLE_PRICE_STARTER_MONTHLY`

**Annual Price:**
```
Description: Starter Annual
Billing Interval: Yearly
Amount: €120.00 (€10/month equivalent - 2 months free)
Currency: EUR
Trial Period: 14 days (optional)
```

7. **Copy the Price ID** → `PADDLE_PRICE_STARTER_ANNUAL`

`[Screenshot: Paddle Dashboard → Product → Prices Tab]`

### Complete Price Matrix

Repeat the above process for all products:

| Product | Monthly Price | Annual Price | Monthly Price ID | Annual Price ID |
|---------|---------------|--------------|------------------|-----------------|
| Free | €0 | €0 | N/A (no billing) | N/A |
| Starter | €12 | €120 | `PADDLE_PRICE_STARTER_MONTHLY` | `PADDLE_PRICE_STARTER_ANNUAL` |
| Standard | €29 | €290 | `PADDLE_PRICE_STANDARD_MONTHLY` | `PADDLE_PRICE_STANDARD_ANNUAL` |
| Business | €59 | €590 | `PADDLE_PRICE_BUSINESS_MONTHLY` | `PADDLE_PRICE_BUSINESS_ANNUAL` |
| Max | €149 | €1490 | `PADDLE_PRICE_MAX_MONTHLY` | `PADDLE_PRICE_MAX_ANNUAL` |
| Partner Plus | €29 | N/A | `PADDLE_PRICE_PARTNER_PLUS_MONTHLY` | N/A |

**Pro Tip:** Annual prices typically offer a discount (e.g., 2 months free = 12 months for the price of 10).

---

## Webhook Configuration

Webhooks notify Facturino when billing events occur (subscription created, payment succeeded, etc.).

### Setting Up Webhooks

1. Go to **Developer Tools → Notifications**
2. Click **+ New Notification Destination**
3. Configure:

```
Type: Webhook
Description: Facturino Production Webhook
Webhook URL: https://app.facturino.mk/api/webhooks/paddle
```

4. **Active Events to Subscribe:**

**Subscription Events:**
- ✅ `subscription.created`
- ✅ `subscription.updated`
- ✅ `subscription.activated`
- ✅ `subscription.canceled`
- ✅ `subscription.paused`
- ✅ `subscription.resumed`
- ✅ `subscription.past_due`

**Transaction Events:**
- ✅ `transaction.completed`
- ✅ `transaction.paid`
- ✅ `transaction.payment_failed`
- ✅ `transaction.updated`

**Customer Events:**
- ✅ `customer.created`
- ✅ `customer.updated`

5. Click **Save**

`[Screenshot: Paddle Dashboard → Developer Tools → Notifications → New Webhook]`

### Webhook Secret

After creating the webhook:

1. Click on the created webhook destination
2. Locate **Webhook Secret Key**
3. Click **Show** to reveal the secret
4. **Copy the secret** (e.g., `pdl_ntfset_01h...`)
   - This is your `PADDLE_WEBHOOK_SECRET`

⚠️ **Security Note:** Keep this secret confidential. Facturino uses it to verify webhook authenticity.

`[Screenshot: Paddle Dashboard → Webhook Details → Secret Key]`

### Testing Webhooks (Sandbox)

1. Create a **separate webhook** for sandbox testing:
   ```
   Webhook URL: https://app.facturino.mk/api/webhooks/paddle
   Environment: Sandbox
   ```
2. Use the **Webhook Simulator** in Developer Tools to send test events
3. Check Facturino logs at `/storage/logs/laravel.log` for webhook receipt

---

## API Credentials

### 1. Seller ID

Your **Seller ID** (previously called Vendor ID) identifies your Paddle account.

1. Go to **Developer Tools → Authentication**
2. Locate **Seller ID** (e.g., `12345` or `sel_01h...`)
3. **Copy the ID** → This is your `PADDLE_SELLER_ID`

`[Screenshot: Paddle Dashboard → Developer Tools → Authentication → Seller ID]`

### 2. API Key

1. In **Developer Tools → Authentication**
2. Click **+ Generate API Key**
3. Enter description: `Facturino Production`
4. Click **Generate**
5. **Copy the API Key** (e.g., `live_abc123...`)
   - This is your `PADDLE_API_KEY`

⚠️ **Important:** API keys are shown only once. Store securely immediately.

`[Screenshot: Paddle Dashboard → Generate API Key Modal]`

### 3. Client-Side Token

The client-side token is used for Paddle.js in the frontend checkout.

1. In **Developer Tools → Authentication**
2. Locate **Client-Side Tokens** section
3. **Default token** is auto-generated
4. **Copy the token** (e.g., `live_abc123xyz...`)
   - This is your `PADDLE_CLIENT_SIDE_TOKEN`

If you need a new token:
1. Click **+ Create Token**
2. Description: `Facturino Frontend Checkout`
3. Domain: `app.facturino.mk` (optional restriction)
4. Click **Create**

`[Screenshot: Paddle Dashboard → Client-Side Tokens]`

### 4. Retain Key (Optional)

Paddle Retain helps reduce churn with smart dunning and recovery flows.

1. Go to **Retain → Settings**
2. Locate **API Key**
3. **Copy the key** → This is your `PADDLE_RETAIN_KEY`

**Note:** Only required if using Paddle Retain features.

### 5. Sandbox vs Production Toggle

Paddle operates in two environments:

**Sandbox (Testing):**
- Use for development and testing
- Separate product catalog and API keys
- No real money transactions
- Test card numbers work

**Production (Live):**
- Real customer payments
- Requires business verification
- Real payment methods only

**Switching Environments:**

1. Top-right of Paddle Dashboard shows current environment
2. Click **Sandbox** or **Production** to toggle
3. Each environment has separate:
   - Product IDs
   - Price IDs
   - API Keys
   - Webhook Secrets

⚠️ **Critical:** Use `.env` variable `PADDLE_SANDBOX=true` for development, `PADDLE_SANDBOX=false` for production.

`[Screenshot: Paddle Dashboard → Environment Toggle]`

---

## Environment Variables

After gathering all credentials, update your Facturino `.env` file:

### Example Configuration

```bash
# ==================================================
# PADDLE BILLING CONFIGURATION
# ==================================================

# Get credentials from: https://vendors.paddle.com/ → Developer Tools → Authentication
# Documentation: https://developer.paddle.com/

# API Credentials
PADDLE_SELLER_ID=12345                              # Your Paddle Seller ID
PADDLE_API_KEY=live_abc123...                       # API Key (live_ or test_)
PADDLE_CLIENT_SIDE_TOKEN=live_xyz789...             # Client-side token for Paddle.js
PADDLE_WEBHOOK_SECRET=pdl_ntfset_01h...             # Webhook signing secret
PADDLE_RETAIN_KEY=                                  # Optional: Paddle Retain API key
PADDLE_SANDBOX=false                                # Set to 'true' for testing, 'false' for production

# Product IDs (from Catalog → Products)
PADDLE_PRODUCT_FREE=                                # Free plan product ID (no billing)
PADDLE_PRODUCT_STARTER=pro_01h1234abcd              # Starter plan product ID
PADDLE_PRODUCT_STANDARD=pro_01h5678efgh             # Standard plan product ID
PADDLE_PRODUCT_BUSINESS=pro_01h9101ijkl             # Business plan product ID
PADDLE_PRODUCT_MAX=pro_01h1121mnop                  # Max plan product ID

# Partner Product
PADDLE_PRODUCT_PARTNER_PLUS=pro_01h1314qrst         # Partner Plus (€29/mo) product ID

# Price IDs - Monthly Billing (from Product → Prices)
PADDLE_PRICE_STARTER_MONTHLY=pri_01h...             # €12/month price ID
PADDLE_PRICE_STANDARD_MONTHLY=pri_01h...            # €29/month price ID
PADDLE_PRICE_BUSINESS_MONTHLY=pri_01h...            # €59/month price ID
PADDLE_PRICE_MAX_MONTHLY=pri_01h...                 # €149/month price ID

# Price IDs - Annual Billing (from Product → Prices)
PADDLE_PRICE_STARTER_ANNUAL=pri_01h...              # €120/year price ID
PADDLE_PRICE_STANDARD_ANNUAL=pri_01h...             # €290/year price ID
PADDLE_PRICE_BUSINESS_ANNUAL=pri_01h...             # €590/year price ID
PADDLE_PRICE_MAX_ANNUAL=pri_01h...                  # €1490/year price ID

# Partner Plus Price
PADDLE_PRICE_PARTNER_PLUS_MONTHLY=pri_01h...        # €29/month price ID

# Webhook URLs
PADDLE_RETURN_URL="${APP_URL}/billing/success"      # Redirect after successful checkout
PADDLE_WEBHOOK_URL="${APP_URL}/api/webhooks/paddle" # Webhook endpoint
```

### Validation Checklist

Before going live, verify:

- [ ] `PADDLE_SANDBOX=false` for production
- [ ] All product IDs match your Paddle dashboard
- [ ] All price IDs are correct for each plan
- [ ] Webhook secret matches the dashboard secret
- [ ] Webhook URL is publicly accessible (test with Paddle simulator)
- [ ] API key starts with `live_` (not `test_`)
- [ ] Client-side token is valid and not expired

---

## Testing

### Using Paddle Sandbox

**Sandbox Testing Flow:**

1. Set `PADDLE_SANDBOX=true` in `.env`
2. Use **sandbox credentials** (separate from production)
3. Create test products in **Sandbox environment**
4. Test checkout flow with test cards

### Test Card Numbers

Paddle provides test cards for sandbox:

| Card Number | Result |
|-------------|--------|
| `4242 4242 4242 4242` | Successful payment |
| `4000 0000 0000 0002` | Card declined |
| `4000 0000 0000 0341` | Fraud check failure |

**CVV:** Any 3 digits
**Expiry:** Any future date
**Postal Code:** Any valid format

`[Screenshot: Paddle Checkout Modal with Test Card]`

### Verifying Webhook Delivery

1. Go to **Developer Tools → Events**
2. Filter by **Recent Events**
3. Check for your webhook events (e.g., `subscription.created`)
4. Click on an event to see:
   - Event ID
   - Timestamp
   - Payload (JSON)
   - Delivery status to your webhook URL

If webhooks fail:
- Check **Retry History** for error messages
- Verify your webhook URL is publicly accessible
- Check Facturino logs: `/storage/logs/laravel.log`
- Ensure `PADDLE_WEBHOOK_SECRET` matches

`[Screenshot: Paddle Dashboard → Events → Event Details with Delivery Status]`

### End-to-End Testing Checklist

- [ ] Create account on Facturino
- [ ] Click "Upgrade Plan" button
- [ ] Select plan (e.g., Starter)
- [ ] Complete checkout with test card
- [ ] Verify subscription created in Paddle dashboard
- [ ] Check webhook received in Facturino logs
- [ ] Confirm plan upgraded in Facturino user profile
- [ ] Test subscription cancellation
- [ ] Verify cancellation webhook received

---

## Troubleshooting

### Common Issues

#### 1. Webhook Signature Verification Failed

**Error:** `Invalid webhook signature`

**Solution:**
- Verify `PADDLE_WEBHOOK_SECRET` matches the dashboard secret
- Check webhook was created for the correct environment (sandbox vs production)
- Ensure no whitespace or extra characters in the secret

#### 2. Product ID Not Found

**Error:** `Product not found: pro_01h...`

**Solution:**
- Confirm `PADDLE_SANDBOX` matches the environment where product was created
- Copy product ID from correct environment in Paddle dashboard
- Sandbox and production have separate product catalogs

#### 3. Price ID Invalid

**Error:** `Invalid price ID: pri_01h...`

**Solution:**
- Ensure price belongs to the correct product
- Check currency is EUR (Facturino expects EUR)
- Verify billing interval matches (monthly vs annual)

#### 4. Webhook URL Not Reachable

**Error:** `Failed to deliver webhook: Connection timeout`

**Solution:**
- Test webhook URL manually: `curl -X POST https://app.facturino.mk/api/webhooks/paddle`
- Ensure server firewall allows Paddle IPs
- Check Laravel routes: `php artisan route:list | grep webhook`
- Verify CSRF exemption for webhook route in `app/Http/Middleware/VerifyCsrfToken.php`

#### 5. Seller ID vs Vendor ID Confusion

**Note:** Paddle renamed "Vendor ID" to "Seller ID" in newer versions.

**Solution:**
- Use `PADDLE_SELLER_ID` (new terminology)
- If using older Paddle SDK, map to `PADDLE_VENDOR_ID`
- Check your SDK version: `composer show laravel/cashier-paddle`

### Support Resources

**Paddle Documentation:**
- Main Docs: [https://developer.paddle.com/](https://developer.paddle.com/)
- API Reference: [https://developer.paddle.com/api-reference](https://developer.paddle.com/api-reference)
- Webhooks Guide: [https://developer.paddle.com/webhooks/overview](https://developer.paddle.com/webhooks/overview)

**Paddle Support:**
- Dashboard Support Chat (bottom-right icon)
- Email: support@paddle.com
- Response Time: 24-48 hours

**Facturino Support:**
- Check `/documentation/FAQ.md`
- Email: support@facturino.mk
- GitHub Issues: (if applicable)

---

## Quick Reference

### Key Dashboard Locations

| Task | Dashboard Location |
|------|--------------------|
| Create Products | **Catalog → Products → + New Product** |
| Add Prices | **Catalog → Products → [Product] → Prices** |
| Get Seller ID | **Developer Tools → Authentication** |
| Generate API Key | **Developer Tools → Authentication → + Generate API Key** |
| Setup Webhooks | **Developer Tools → Notifications → + New Notification Destination** |
| View Events | **Developer Tools → Events** |
| Test Webhooks | **Developer Tools → Notifications → [Webhook] → Test** |
| Switch Environment | **Top-right dropdown (Sandbox/Production)** |
| Business Verification | **Settings → Business Details** |
| Tax Settings | **Settings → Tax Settings** |

### Environment Variable Mapping

| Paddle Dashboard | .env Variable |
|------------------|---------------|
| Seller ID | `PADDLE_SELLER_ID` |
| API Key | `PADDLE_API_KEY` |
| Client-Side Token | `PADDLE_CLIENT_SIDE_TOKEN` |
| Webhook Secret | `PADDLE_WEBHOOK_SECRET` |
| Product ID | `PADDLE_PRODUCT_*` |
| Price ID | `PADDLE_PRICE_*` |

---

## Next Steps

After completing Paddle setup:

1. ✅ **Test in Sandbox:** Perform full checkout flow with test cards
2. ✅ **Verify Webhooks:** Confirm all events are received correctly
3. ✅ **Business Verification:** Complete verification to enable live payments
4. ✅ **Switch to Production:** Update `.env` with production credentials
5. ✅ **Monitor Transactions:** Watch first few live transactions closely
6. ✅ **Enable Paddle Retain:** Reduce churn with smart recovery (optional)
7. ✅ **Review Tax Settings:** Ensure compliance for all customer regions

**For Partner Program Configuration:**
- See `/documentation/PARTNER_GUIDE.md`

**For Deployment:**
- See `/documentation/DEPLOYMENT_RUNBOOK.md`

---

**Last Updated:** November 14, 2025
**Maintained by:** Facturino Team
**Questions?** support@facturino.mk
