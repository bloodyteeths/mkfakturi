# Facturino - Frequently Asked Questions (FAQ)

**Last Updated:** November 14, 2025

## Table of Contents

1. [Getting Started](#getting-started)
2. [Account Management](#account-management)
3. [Invoicing](#invoicing)
4. [Payments](#payments)
5. [E-Faktura / Electronic Signatures](#e-faktura--electronic-signatures)
6. [Banking Integration](#banking-integration)
7. [Partner Program](#partner-program)
8. [Billing & Subscriptions](#billing--subscriptions)
9. [Data & Privacy](#data--privacy)
10. [Troubleshooting](#troubleshooting)
11. [Technical Support](#technical-support)

---

## Getting Started

### Q: What is Facturino?
**A:** Facturino is a Macedonian-localized invoicing and accounting platform designed for small businesses and accountants. It provides invoice management, payment processing, e-Faktura generation, banking integration, and partner referrals.

### Q: Is there a free trial?
**A:** Yes! We offer a 14-day free trial with full access to all features. No credit card required for signup.

### Q: What languages does Facturino support?
**A:** Currently:
- Macedonian (Macedonian Cyrillic)
- Albanian (for Kosovo/Albania users)
- English

### Q: Do I need technical knowledge to use Facturino?
**A:** No. Facturino is designed for non-technical users. If you can use email, you can use Facturino. For advanced features (e.g., banking integration), we recommend working with an accountant partner.

### Q: Can I import data from my old system?
**A:** Yes! We support CSV/XLSX import for:
- Customers
- Items/Products
- Invoices
- Payments

Our intelligent import wizard automatically maps fields from popular systems (Megasoft, Onivo, QuickBooks).

---

## Account Management

### Q: How do I create an account?
**A:** Go to https://app.facturino.mk and click "Register". Provide your company details and email. Verify your email to activate.

### Q: I forgot my password. How do I reset it?
**A:** Click "Forgot Password" on the login page. Enter your email, and we'll send a password reset link. The link expires in 60 minutes.

### Q: Can multiple users access the same company account?
**A:** Yes. Admin users can invite team members with different permission levels (Admin, Manager, Staff). Go to **Settings → Team Members**.

### Q: How do I change my company information?
**A:** Navigate to **Settings → Company Profile** and update your:
- Company name
- Tax ID (EMBG/EMDBS)
- Address
- Logo

---

## Invoicing

### Q: How do I create my first invoice?
**A:**
1. Go to **Invoices → New Invoice**
2. Select or create a customer
3. Add items/services
4. Set payment terms and due date
5. Click **Save & Send**

### Q: Can I create recurring invoices?
**A:** Yes! When creating an invoice, toggle **Recurring Invoice** and set:
- Frequency (monthly, quarterly, annually)
- Start date and end date
- Auto-send option

### Q: How do I send an invoice to a customer?
**A:** Click **Send** on any invoice. Enter the customer's email. The invoice PDF will be sent automatically. You can also download the PDF and send it manually.

### Q: Can I customize invoice templates?
**A:** Yes. Go to **Settings → Invoice Templates** to:
- Choose from pre-designed templates
- Add your logo and brand colors
- Customize fields and text
- Preview before saving

### Q: What invoice statuses are available?
**A:**
- **Draft:** Not yet sent
- **Sent:** Sent to customer, awaiting payment
- **Viewed:** Customer opened the invoice
- **Partially Paid:** Some payment received
- **Paid:** Fully paid
- **Overdue:** Past due date, unpaid
- **Cancelled:** Voided invoice

---

## Payments

### Q: How can my customers pay invoices?
**A:** Customers can pay via:
- **CPAY (CASYS):** Credit/debit cards (MKD)
- **Paddle:** International payments (credit card, PayPal)
- **Bank Transfer:** Manual entry after payment received

### Q: How do I record a manual payment?
**A:**
1. Open the invoice
2. Click **Add Payment**
3. Enter amount, date, and method
4. Save

The invoice status will update automatically.

### Q: Can I issue partial refunds?
**A:** Yes. Go to **Payments → [Select Payment] → Refund**. Enter the refund amount and reason.

### Q: How do payment reminders work?
**A:** Enable auto-reminders in **Settings → Notifications**. Facturino will send:
- 7 days before due date
- On due date
- 7 days after due date (overdue notice)

---

## E-Faktura / Electronic Signatures

### Q: What is e-Faktura?
**A:** E-Faktura is Macedonia's electronic invoicing system required for B2B transactions. Invoices must be digitally signed with a Qualified Electronic Signature (QES).

### Q: How do I get a QES certificate?
**A:** Contact a Macedonian Certificate Authority (CA):
- **Makedonski Telekom** - https://ca.telekom.mk/
- **Neotel** - https://neotel.mk/qes

Purchase a QES certificate (usually ~50-100 EUR/year).

### Q: How do I upload my QES certificate to Facturino?
**A:**
1. Go to **Settings → E-Faktura**
2. Upload your .pfx or .p12 certificate file
3. Enter the certificate password
4. Click **Save & Verify**

Facturino will validate the certificate and store it securely.

### Q: My e-Faktura isn't sending. What's wrong?
**A:** Common issues:
- **Expired certificate:** Check expiry date in Settings → E-Faktura
- **Wrong password:** Re-enter certificate password
- **Invalid certificate chain:** Ensure your CA is recognized
- **Network error:** Check your internet connection

If the issue persists, contact support@facturino.mk.

### Q: Can I send regular invoices without e-Faktura?
**A:** Yes. E-Faktura is only required for:
- B2B transactions (company to company)
- Invoices above a certain threshold (check current law)

For B2C (business to consumer), regular PDF invoices are sufficient.

---

## Banking Integration

### Q: Which banks does Facturino support?
**A:** Currently:
- **Stopanska Banka AD Skopje**
- **NLB Banka**
- **Komercijalna Banka** (coming soon)

More banks will be added based on PSD2 API availability.

### Q: How do I connect my bank account?
**A:**
1. Go to **Settings → Banking**
2. Click **Connect Bank**
3. Select your bank
4. Log in with your online banking credentials (via bank's secure portal)
5. Authorize Facturino to access transactions (read-only)

### Q: Is my banking data secure?
**A:** Yes. We use:
- **PSD2 OAuth2** for secure authentication
- **Read-only access** (we cannot initiate payments)
- **Encrypted storage** (AES-256)
- **No credential storage** (tokens expire after 90 days)

### Q: What is automatic reconciliation?
**A:** Facturino matches incoming bank transactions to unpaid invoices based on:
- Amount (±3 day tolerance)
- Customer name
- Invoice reference

Matched transactions automatically mark invoices as paid.

### Q: Can I manually match transactions?
**A:** Yes. Go to **Banking → Transactions** and click **Match** on unmatched transactions. Select the corresponding invoice.

---

## Partner Program

### Q: What is the Partner Program?
**A:** Our affiliate/referral program for accountants and consultants. Earn **5% commission** on every customer you refer (recurring monthly).

### Q: How do I become a partner?
**A:**
1. Apply at https://app.facturino.mk/partner/apply
2. Complete KYC verification (business license, ID)
3. Get approved (usually 2-3 business days)
4. Receive your unique referral link

### Q: How much commission do I earn?
**A:** Default: **5% of monthly subscription fees** from referred customers.

Example:
- Refer 10 customers on €20/month plan
- Monthly revenue: €200
- Your commission: €10/month (ongoing)

### Q: When do I get paid?
**A:** Commissions are paid monthly via bank transfer, 30 days after invoice date (to account for refunds).

Minimum payout: €50 (cumulative).

### Q: How do I track my referrals?
**A:** Partner Dashboard shows:
- Total referrals (active/inactive)
- Monthly commission
- Payment history
- Referral link performance

### Q: Can I give my customers a discount?
**A:** Partners can offer custom discount codes (set in Partner Dashboard). Example: 10% off first 3 months.

---

## Billing & Subscriptions

### Q: What are the pricing plans?
**A:**
- **Starter:** €15/month - 10 customers, 50 invoices/month
- **Professional:** €30/month - 100 customers, unlimited invoices
- **Business:** €60/month - Unlimited customers, multi-user, advanced features
- **Enterprise:** Custom pricing - Dedicated support, custom integrations

### Q: Can I change my plan?
**A:** Yes, anytime. Upgrades take effect immediately. Downgrades apply at the next billing cycle.

### Q: How do I cancel my subscription?
**A:** Go to **Settings → Billing → Cancel Subscription**. You'll retain access until the end of your billing period.

### Q: What happens to my data if I cancel?
**A:** Your data is retained for **90 days** in read-only mode. After 90 days, it's permanently deleted. Export your data before cancelling!

### Q: Do you offer refunds?
**A:** We offer refunds on a case-by-case basis within 14 days of purchase. Contact billing@facturino.mk.

### Q: Can I pay annually?
**A:** Yes. Annual plans get **2 months free** (17% discount). Switch to annual in Settings → Billing.

---

## Data & Privacy

### Q: Where is my data stored?
**A:** Our servers are hosted on **Railway** (GDPR-compliant) with data centers in:
- Primary: EU (Ireland/Germany)
- Backup: USA (with Standard Contractual Clauses)

### Q: Is my data encrypted?
**A:** Yes.
- **In transit:** TLS 1.3
- **At rest:** AES-256 encryption
- **Backups:** Encrypted and stored separately

### Q: Can I export my data?
**A:** Yes. Go to **Settings → Data Export**. Download:
- Invoices (PDF/CSV/Excel)
- Customers (CSV)
- Financial reports (PDF/Excel)

### Q: Do you comply with GDPR?
**A:** Yes. We are fully GDPR-compliant. See our **Privacy Policy** for details: https://app.facturino.mk/legal/privacy-policy

### Q: How do I delete my account and data?
**A:** Email privacy@facturino.mk with the subject "Data Deletion Request". We'll delete all your data within 30 days (subject to legal retention for financial records: 10 years).

---

## Troubleshooting

### Q: I can't log in. What should I do?
**A:**
1. Ensure you're using the correct email
2. Reset your password via "Forgot Password"
3. Clear browser cache and cookies
4. Try a different browser (Chrome/Firefox recommended)
5. Check if your account is suspended (email from us)

If still stuck, contact support@facturino.mk.

### Q: My invoice PDF isn't generating.
**A:** Common fixes:
- Refresh the page and try again
- Ensure all required fields are filled (customer, items, dates)
- Check if your logo is too large (max 2 MB)
- Try a different browser

### Q: Why is my bank connection failing?
**A:**
- **Token expired:** Reconnect your bank (tokens last 90 days)
- **Bank maintenance:** Check your bank's website for outages
- **Incorrect credentials:** Re-enter your online banking login
- **PSD2 not enabled:** Contact your bank to enable PSD2 API access

### Q: Payments aren't auto-reconciling.
**A:** Check:
- Invoice reference matches bank transaction description
- Amount matches exactly (or within tolerance)
- Transaction date is within 7 days of due date
- Bank sync is enabled and up-to-date

### Q: I'm getting a "Subscription Expired" error.
**A:**
- Check Settings → Billing for payment status
- Update your payment method if card expired
- Contact billing@facturino.mk if payment was processed but access denied

---

## Technical Support

### Q: How do I contact support?
**A:**
- **Email:** support@facturino.mk (response within 24 hours)
- **Live Chat:** Available in-app (Mon-Fri, 9 AM - 6 PM CET)
- **Phone:** +389 2 XXX-XXXX (business hours)

### Q: What information should I include in a support request?
**A:**
1. Your account email
2. Description of the issue
3. Steps to reproduce
4. Screenshots (if applicable)
5. Browser/device information

### Q: Do you offer onboarding assistance?
**A:** Yes! New users get:
- Free 30-minute onboarding call
- Email support for first 30 days
- Video tutorials library

Book onboarding: https://calendly.com/facturino/onboarding

### Q: Can you help with accounting questions?
**A:** We provide technical support for Facturino. For accounting/tax advice, we recommend consulting a certified accountant. We can refer you to a partner accountant if needed.

---

## Still Need Help?

**Email:** support@facturino.mk
**Documentation:** https://docs.facturino.mk
**Video Tutorials:** https://youtube.com/@facturino
**Community Forum:** https://community.facturino.mk (coming soon)

---

**Last Updated:** November 14, 2025
**Version:** 1.0
