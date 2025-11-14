# Paddle Dashboard Setup Guide
## Production Configuration for Facturino Billing

**Last Updated:** 2025-11-14
**Estimated Time:** 45-60 minutes
**Difficulty:** Intermediate

---

## Table of Contents

1. [Prerequisites](#1-prerequisites)
2. [Paddle Account Setup](#2-paddle-account-setup)
3. [Create Products & Prices](#3-create-products--prices)
4. [Configure Webhook](#4-configure-webhook)
5. [Get API Credentials](#5-get-api-credentials)
6. [Environment Configuration](#6-environment-configuration)
7. [Testing](#7-testing)
8. [Go Live](#8-go-live)
9. [Troubleshooting](#9-troubleshooting)

---

## 1. Prerequisites

### Before You Start

- [ ] Paddle account created (https://vendors.paddle.com/signup)
- [ ] Business verified (required for production)
- [ ] Bank account linked for payouts
- [ ] Production domain configured (`app.facturino.mk`)
- [ ] SSL certificate installed (HTTPS required)

### Required Information

| Field | Value | Notes |
|-------|-------|-------|
| **Business Name** | Facturino | As registered |
| **Country** | Macedonia | Business location |
| **Currency** | EUR | Primary billing currency |
| **VAT Number** | MK############ | If applicable |
| **Webhook URL** | `https://app.facturino.mk/api/webhooks/paddle` | Production endpoint |

---

## 2. Paddle Account Setup

### Step 2.1: Create Paddle Account

1. Go to https://vendors.paddle.com/signup
2. Fill in business details:
   - Business Name: **Facturino**
   - Business Email: **billing@facturino.mk**
   - Country: **Macedonia**
3. Verify your email address
4. Complete business verification:
   - Upload business registration documents
   - Provide VAT number (if applicable)
   - Link bank account for payouts

### Step 2.2: Configure Seller Settings

1. Navigate to **Seller Settings** in Paddle Dashboard
2. Set **Seller Name**: `Facturino`
3. Set **Support Email**: `support@facturino.mk`
4. Upload **Company Logo** (recommended size: 200x200px)
5. Set **Default Currency**: `EUR`
6. Configure **Payout Schedule**: Monthly (recommended)

---

## 3. Create Products & Prices

### Step 3.1: Enable Paddle Billing

**IMPORTANT:** Facturino uses **Paddle Billing** (NEW platform), NOT Paddle Classic.

1. Log in to Paddle Dashboard
2. Go to **Paddle Billing** section
3. If not enabled, click **Enable Paddle Billing**
4. Accept terms and conditions

### Step 3.2: Create Starter Plan

1. Navigate to **Catalog > Products**
2. Click **+ Create Product**

**Product Details:**
```
Name: Facturino Starter
Description: Perfect for small businesses and freelancers
Type: Standard
Tax Category: SaaS
```

3. Click **Create Product**
4. Copy the **Product ID** (format: `pro_xxxxxxxxxxxxx`)
5. Click **+ Add Price**

**Price Details:**
```
Amount: €12.00
Billing Cycle: Monthly
Currency: EUR
Trial Period: 14 days (optional)
```

6. Click **Create Price**
7. **IMPORTANT:** Copy the **Price ID** (format: `pri_xxxxxxxxxxxxx`)

**Save this Price ID for .env configuration:**
```
PADDLE_PRICE_STARTER_MONTHLY=pri_xxxxxxxxxxxxx
```

### Step 3.3: Create Professional Plan

Repeat Step 3.2 with these details:

**Product Details:**
```
Name: Facturino Professional
Description: Advanced features for growing businesses
Type: Standard
Tax Category: SaaS
```

**Price Details:**
```
Amount: €29.00
Billing Cycle: Monthly
Currency: EUR
Trial Period: 14 days (optional)
```

**Save Price ID:**
```
PADDLE_PRICE_PROFESSIONAL_MONTHLY=pri_xxxxxxxxxxxxx
```

### Step 3.4: Create Business Plan

Repeat Step 3.2 with these details:

**Product Details:**
```
Name: Facturino Business
Description: Complete solution for established businesses
Type: Standard
Tax Category: SaaS
```

**Price Details:**
```
Amount: €59.00
Billing Cycle: Monthly
Currency: EUR
Trial Period: 14 days (optional)
```

**Save Price ID:**
```
PADDLE_PRICE_BUSINESS_MONTHLY=pri_xxxxxxxxxxxxx
```

---

## 4. Configure Webhook

### Step 4.1: Create Webhook Endpoint

1. Navigate to **Developer Tools > Notifications**
2. Click **+ Add Notification Destination**

**Webhook Configuration:**
```
Destination URL: https://app.facturino.mk/api/webhooks/paddle
Description: Facturino Production Webhook
Status: Active
```

3. Click **Save Destination**

### Step 4.2: Select Events

**IMPORTANT:** Enable ONLY these events to reduce noise:

**Subscription Events:**
- [x] `subscription.created`
- [x] `subscription.updated`
- [x] `subscription.canceled`
- [x] `subscription.past_due`
- [x] `subscription.paused`
- [x] `subscription.resumed`

**Transaction Events:**
- [x] `transaction.completed`
- [x] `transaction.paid`
- [x] `transaction.payment_failed`
- [x] `transaction.updated`

**Customer Events:**
- [x] `customer.created`
- [x] `customer.updated`

### Step 4.3: Get Webhook Secret

1. After creating the destination, click on it
2. Find **Webhook Secret Key**
3. Click **Show** to reveal the secret
4. **IMPORTANT:** Copy this secret (format: `pdl_ntfset_xxxxxxxxxxxxx`)

**Save for .env configuration:**
```
PADDLE_WEBHOOK_SECRET=pdl_ntfset_xxxxxxxxxxxxx
```

### Step 4.4: Test Webhook

1. In the webhook destination page, click **Send Test Event**
2. Select `subscription.created`
3. Click **Send Event**
4. Verify in your application logs that webhook was received

**Expected Response:**
```
HTTP 200 OK
```

If you get an error, check:
- [ ] Webhook URL is accessible from internet
- [ ] HTTPS certificate is valid
- [ ] No firewall blocking Paddle IPs
- [ ] Application is running and healthy

---

## 5. Get API Credentials

### Step 5.1: Create API Key

1. Navigate to **Developer Tools > Authentication**
2. Click **+ Create API Key**

**API Key Configuration:**
```
Name: Facturino Production
Description: Production API key for Facturino backend
Type: Standard
```

3. Click **Create Key**
4. **CRITICAL:** Copy the API key immediately (shown only once!)
   - Format: `live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx`
   - Save securely - cannot be retrieved later

**Save for .env:**
```
PADDLE_API_KEY=live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

### Step 5.2: Get Seller ID

1. Navigate to **Developer Tools > Authentication**
2. Find **Seller ID** at the top of the page
3. Copy the Seller ID (format: `12345` or `123456`)

**Save for .env:**
```
PADDLE_SELLER_ID=12345
```

### Step 5.3: Get Client-Side Token

1. Navigate to **Developer Tools > Authentication**
2. Find **Client-side tokens** section
3. Click **+ Create Token**

**Token Configuration:**
```
Name: Facturino Frontend
Description: Client-side token for Paddle.js
Allowed Domains: app.facturino.mk
```

4. Click **Create Token**
5. Copy the token (format: `live_xxxxxxxxxxxxxxxxxxxxx`)

**Save for .env:**
```
PADDLE_CLIENT_SIDE_TOKEN=live_xxxxxxxxxxxxxxxxxxxxx
```

---

## 6. Environment Configuration

### Step 6.1: Update Production .env

SSH into production server and update `/var/www/facturino/.env`:

```bash
# ==============================================================================
# PADDLE BILLING (NEW PLATFORM)
# ==============================================================================

# Seller & API Configuration
PADDLE_SELLER_ID=12345
PADDLE_API_KEY=live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx
PADDLE_CLIENT_SIDE_TOKEN=live_xxxxxxxxxxxxxxxxxxxxx
PADDLE_WEBHOOK_SECRET=pdl_ntfset_xxxxxxxxxxxxx

# Environment
PADDLE_SANDBOX=false

# Product Price IDs (Monthly)
PADDLE_PRICE_STARTER_MONTHLY=pri_xxxxxxxxxxxxx
PADDLE_PRICE_PROFESSIONAL_MONTHLY=pri_xxxxxxxxxxxxx
PADDLE_PRICE_BUSINESS_MONTHLY=pri_xxxxxxxxxxxxx

# Vite Frontend Variables (for Paddle.js)
VITE_PADDLE_CLIENT_TOKEN="${PADDLE_CLIENT_SIDE_TOKEN}"
VITE_PADDLE_SANDBOX="${PADDLE_SANDBOX}"
```

### Step 6.2: Clear Caches

```bash
cd /var/www/facturino

# Clear Laravel caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild config cache
php artisan config:cache

# Restart queue workers (if using)
php artisan queue:restart
```

### Step 6.3: Rebuild Frontend

```bash
cd /var/www/facturino

# Install dependencies (if needed)
npm install

# Build production assets with new env vars
npm run build

# Verify build contains Paddle credentials
grep -r "VITE_PADDLE" public/build/assets/
```

---

## 7. Testing

### Step 7.1: Verify Configuration

**Run Health Check:**
```bash
curl https://app.facturino.mk/health | jq
```

**Expected Response:**
```json
{
  "status": "healthy",
  "checks": {
    "paddle": true,  // ← Should be true
    "database": true,
    "redis": true,
    ...
  }
}
```

If `paddle: false`, check logs:
```bash
tail -f /var/www/facturino/storage/logs/laravel.log | grep -i paddle
```

### Step 7.2: Test Pricing Page

1. Navigate to `https://app.facturino.mk/admin/pricing`
2. **Verify:**
   - [ ] All 3 plans displayed (Starter, Professional, Business)
   - [ ] Prices shown correctly (€12, €29, €59)
   - [ ] "Subscribe" buttons visible

3. Open browser console (F12)
4. **Verify no JavaScript errors:**
   ```
   Paddle.js loaded successfully
   Environment: production
   ```

### Step 7.3: Test Checkout Flow (Sandbox First)

**Before testing production, test in sandbox:**

1. Temporarily set `PADDLE_SANDBOX=true` in `.env`
2. Clear caches: `php artisan config:clear`
3. Click **Subscribe to Starter**
4. **Verify:**
   - [ ] Paddle checkout overlay appears
   - [ ] Sandbox banner shown
   - [ ] Can complete test transaction

**Paddle Sandbox Test Cards:**
```
Card Number: 4242 4242 4242 4242
Expiry: Any future date (e.g., 12/25)
CVV: Any 3 digits (e.g., 123)
```

5. Complete test checkout
6. **Verify in Dashboard:**
   - Navigate to **Customers** → See test customer
   - Navigate to **Subscriptions** → See active subscription
   - Check webhook logs for `subscription.created`

### Step 7.4: Test Production Checkout

**Only after sandbox test passes:**

1. Set `PADDLE_SANDBOX=false` in production `.env`
2. Clear caches
3. Use REAL card for small test purchase (will be charged!)
4. Complete checkout
5. **Verify:**
   - [ ] Real charge appears in Paddle Dashboard
   - [ ] Customer created in database
   - [ ] Subscription recorded in `paddle_subscriptions` table
   - [ ] Webhook received and processed

**Verify Database:**
```sql
-- Check customer was created
SELECT * FROM paddle_customers ORDER BY created_at DESC LIMIT 1;

-- Check subscription was created
SELECT * FROM paddle_subscriptions ORDER BY created_at DESC LIMIT 1;

-- Check transaction recorded
SELECT * FROM paddle_transactions ORDER BY created_at DESC LIMIT 1;
```

### Step 7.5: Test Webhook Delivery

**Verify webhook logs in Paddle Dashboard:**

1. Navigate to **Developer Tools > Events & Logs**
2. Filter by last 24 hours
3. **Check for successful deliveries:**
   - `subscription.created` → Status: 200 OK
   - `transaction.completed` → Status: 200 OK

**If webhook fails:**
- Check Laravel logs: `tail -f storage/logs/laravel.log`
- Verify webhook secret matches `.env`
- Ensure webhook endpoint is publicly accessible
- Check firewall rules allow Paddle IPs

---

## 8. Go Live

### Step 8.1: Pre-Launch Checklist

- [ ] All 3 products created in Paddle Dashboard
- [ ] All 3 price IDs configured in `.env`
- [ ] Webhook endpoint verified (Status: 200 OK)
- [ ] API credentials configured and tested
- [ ] SSL certificate valid (check: `https://app.facturino.mk`)
- [ ] Sandbox mode disabled (`PADDLE_SANDBOX=false`)
- [ ] Production checkout tested with real card
- [ ] Email notifications configured (`MAIL_FROM_ADDRESS`)
- [ ] Support email set in Paddle (`support@facturino.mk`)
- [ ] Tax configuration verified (VAT handling)

### Step 8.2: Enable Public Access

1. Update navigation to include "Pricing" link (already done in Phase 1)
2. Update homepage to promote subscription plans
3. Send announcement email to waitlist (if applicable)

### Step 8.3: Monitor First Customers

**For the first 48 hours, monitor:**

```bash
# Watch for new subscriptions
watch -n 30 'mysql -e "SELECT COUNT(*) FROM paddle_subscriptions"'

# Watch webhook logs
tail -f storage/logs/laravel.log | grep -i "webhook\|paddle"

# Watch for errors
tail -f storage/logs/laravel.log | grep -i "error\|exception"
```

**Paddle Dashboard Monitoring:**
- Check **Customers** page for new signups
- Check **Subscriptions** for active subscriptions
- Check **Transactions** for successful payments
- Check **Events & Logs** for webhook deliveries

---

## 9. Troubleshooting

### Issue 1: "Paddle configuration check failed" in Health Check

**Symptoms:**
```json
{
  "checks": {
    "paddle": false
  }
}
```

**Diagnosis:**
```bash
# Check .env has all required variables
grep "PADDLE_" .env

# Verify config cache
php artisan config:show cashier
```

**Solutions:**
1. Verify all 4 credentials set: `PADDLE_SELLER_ID`, `PADDLE_API_KEY`, `PADDLE_CLIENT_SIDE_TOKEN`, `PADDLE_WEBHOOK_SECRET`
2. Clear config cache: `php artisan config:clear`
3. Rebuild cache: `php artisan config:cache`
4. Restart PHP-FPM: `sudo systemctl restart php8.2-fpm`

---

### Issue 2: Checkout Overlay Not Appearing

**Symptoms:**
- Click "Subscribe" button
- Nothing happens
- Console shows: `Paddle is not defined`

**Diagnosis:**
```javascript
// Open browser console
console.log(window.Paddle);  // Should be an object, not undefined
```

**Solutions:**
1. Verify `VITE_PADDLE_CLIENT_TOKEN` in `.env`
2. Rebuild frontend: `npm run build`
3. Hard refresh browser: `Ctrl+Shift+R`
4. Check network tab for `paddle.js` loading errors

---

### Issue 3: Webhook Not Received

**Symptoms:**
- Subscription created in Paddle Dashboard
- NOT created in Facturino database
- Paddle Events & Logs show delivery failure

**Diagnosis:**
```bash
# Test webhook endpoint directly
curl -X POST https://app.facturino.mk/api/webhooks/paddle \
  -H "Content-Type: application/json" \
  -d '{"event_type":"subscription.created","data":{}}'

# Should return 200 OK (may fail validation, but endpoint is reachable)
```

**Solutions:**
1. Verify webhook URL is publicly accessible (not localhost)
2. Check firewall allows incoming HTTPS
3. Verify `PADDLE_WEBHOOK_SECRET` matches Paddle Dashboard
4. Check CSRF exemption: `VerifyCsrfToken.php` excludes `/api/webhooks/*`
5. Check Laravel logs for signature validation errors

---

### Issue 4: "Subscription Already Exists" Error

**Symptoms:**
- User tries to subscribe
- Error: "You already have an active subscription"

**Diagnosis:**
```sql
-- Check for existing subscription
SELECT * FROM paddle_subscriptions
WHERE paddle_customer_id = 'ctm_xxxxx'
AND status = 'active';
```

**Solutions:**
1. **If user legitimately has subscription:** Show them billing page instead
2. **If subscription should be canceled:** Cancel in Paddle Dashboard first
3. **If database out of sync:** Manually update status or delete record

---

### Issue 5: Wrong Price Showing on Checkout

**Symptoms:**
- Checkout shows wrong amount (e.g., €0.00 or different price)

**Diagnosis:**
```bash
# Verify price IDs in .env
grep "PADDLE_PRICE_" .env

# Verify price IDs in Paddle Dashboard match
```

**Solutions:**
1. Double-check price IDs copied correctly from Paddle Dashboard
2. Ensure no extra spaces or quotes in `.env` values
3. Clear config cache: `php artisan config:clear`
4. Verify prices are active in Paddle Dashboard (not archived)

---

### Issue 6: Sandbox Mode Still Active in Production

**Symptoms:**
- Checkout shows "SANDBOX" banner
- Test cards work in production

**Diagnosis:**
```bash
# Check env variable
grep "PADDLE_SANDBOX" .env
# Should be: PADDLE_SANDBOX=false

# Check compiled config
php artisan tinker
>>> config('cashier.sandbox');
// Should return: false
```

**Solutions:**
1. Set `PADDLE_SANDBOX=false` in `.env`
2. Clear config: `php artisan config:clear`
3. Restart queue: `php artisan queue:restart`
4. Hard refresh browser

---

## 10. Maintenance

### Monthly Tasks

1. **Review Subscriptions:**
   - Check for failed payments
   - Review churn rate
   - Identify upsell opportunities

2. **Verify Payouts:**
   - Confirm bank payouts received
   - Reconcile with Paddle reports
   - Check for held funds

3. **Monitor Webhooks:**
   - Review webhook delivery success rate
   - Check for any failures
   - Clear old webhook logs

### Quarterly Tasks

1. **Review Pricing:**
   - Analyze conversion rates
   - Consider A/B testing different prices
   - Update plans if needed

2. **Tax Compliance:**
   - Verify VAT collection correct
   - Review tax settings
   - Update business info if changed

3. **Security Audit:**
   - Rotate API keys (if needed)
   - Review access logs
   - Update webhook signatures

---

## 11. Support & Resources

### Paddle Support
- **Dashboard:** https://vendors.paddle.com
- **Documentation:** https://developer.paddle.com
- **Support Email:** support@paddle.com
- **Community:** https://paddle.com/community

### Facturino Internal
- **Health Check:** https://app.facturino.mk/health
- **Admin Dashboard:** https://app.facturino.mk/admin
- **Billing Management:** https://app.facturino.mk/admin/billing

### Emergency Contacts
- **Paddle Account Manager:** (to be assigned after $10k MRR)
- **Facturino DevOps:** devops@facturino.mk
- **Billing Issues:** billing@facturino.mk

---

## 12. Quick Reference

### Essential URLs
```
Paddle Dashboard: https://vendors.paddle.com
Webhook Logs: https://vendors.paddle.com/events-and-logs
Customers: https://vendors.paddle.com/customers
Products: https://vendors.paddle.com/catalog/products
Subscriptions: https://vendors.paddle.com/subscriptions

Facturino Pricing: https://app.facturino.mk/admin/pricing
Facturino Health: https://app.facturino.mk/health
```

### Key Commands
```bash
# Clear caches
php artisan config:clear && php artisan cache:clear

# Check Paddle config
php artisan tinker
>>> config('cashier.seller_id')
>>> config('cashier.sandbox')

# View recent subscriptions
php artisan tinker
>>> \App\Models\PaddleSubscription::latest()->first()

# Test webhook endpoint
curl -I https://app.facturino.mk/api/webhooks/paddle
```

### Important Price IDs
```
Starter:      pri_xxxxxxxxxxxxx  (€12/month)
Professional: pri_xxxxxxxxxxxxx  (€29/month)
Business:     pri_xxxxxxxxxxxxx  (€59/month)
```

---

## Appendix A: Paddle Dashboard Screenshots

*Screenshots to be added showing:*
1. Product creation page
2. Price configuration
3. Webhook setup
4. API key creation
5. Events & Logs page

---

## Appendix B: Configuration Template

**Complete .env Paddle Section:**
```bash
# ==============================================================================
# PADDLE BILLING CONFIGURATION
# ==============================================================================

# Seller & API Authentication
PADDLE_SELLER_ID=
PADDLE_API_KEY=
PADDLE_CLIENT_SIDE_TOKEN=
PADDLE_WEBHOOK_SECRET=

# Environment (false = production, true = sandbox)
PADDLE_SANDBOX=false

# Monthly Plan Price IDs
PADDLE_PRICE_STARTER_MONTHLY=
PADDLE_PRICE_PROFESSIONAL_MONTHLY=
PADDLE_PRICE_BUSINESS_MONTHLY=

# Frontend Variables (automatically derived)
VITE_PADDLE_CLIENT_TOKEN="${PADDLE_CLIENT_SIDE_TOKEN}"
VITE_PADDLE_SANDBOX="${PADDLE_SANDBOX}"
```

---

**Document Version:** 1.0
**Last Updated:** 2025-11-14
**Next Review:** Before production launch

**Setup Status:** ⏳ **PENDING MANUAL CONFIGURATION**

---
