# PHASE 2: PRODUCTION LAUNCH ROADMAP
**Date**: November 15, 2025
**Status**: 🟢 READY TO START
**Prerequisite**: Phase 1 (100% complete ✅)
**Timeline**: 6-8 weeks with 6 parallel senior dev teams
**Goal**: Production-ready Facturino with paying customers

---

## 🎯 EXECUTIVE SUMMARY

Phase 1 delivered the **architectural foundation**. Phase 2 completes the **business features** needed for revenue generation:

### What Phase 1 Built (✅ Complete)
- ✅ Correct pricing model (accountants = affiliates, companies = customers)
- ✅ Database schema for subscriptions & commissions
- ✅ Billing infrastructure (Paddle + CPAY controllers)
- ✅ Partner portal foundation (dashboard, referrals, payouts)
- ✅ Railway deployment with working authentication
- ✅ Feature flag system (all features OFF by default)

### What Phase 2 Will Build (🚧 This Roadmap)
1. **Complete Affiliate System** - Commission tracking, multi-level payouts, KYC
2. **Company Feature Gating** - Invoice limits, e-Faktura, bank feeds per tier
3. **Support Ticketing** - Customer support portal integrated with Laravel Ticket
4. **UI Polish & Responsiveness** - Mobile-ready, professional UX
5. **Production Infrastructure** - Monitoring, security, backups, documentation
6. **Live Testing & Beta Launch** - Real accountants + companies onboarded

---

## 📋 TRACK 1: AFFILIATE SYSTEM COMPLETION
**Duration**: 4-5 weeks
**Agent**: AffiliateAgent (Senior Backend Dev)
**Depends on**: Phase 1 partner portal foundation

### Milestone 1.1: Commission Recording (Week 1)
**Tickets**:
- **AC-01-00**: Create `CommissionService` with `recordRecurring()`, `recordBounty()`, `recordCompanyBounty()`
- **AC-01-01**: Integrate commission recording into Paddle webhook (20% of company subscription)
- **AC-01-02**: Integrate commission recording into CPAY webhook (20% of company subscription)
- **AC-01-03**: Add commission tracking to `company_subscriptions` table (accountant_id field already exists ✅)
- **AC-01-04**: Create `affiliate_events` table for detailed commission history

**Files to Create**:
```
app/Services/Affiliate/CommissionService.php
app/Models/AffiliateEvent.php
database/migrations/2025_11_15_create_affiliate_events_table.php
tests/Feature/Affiliate/CommissionRecordingTest.php
```

**Acceptance**:
- ✅ Company (Standard €29) pays via Paddle → Accountant gets €5.80 commission (20%)
- ✅ Commission recorded in `affiliate_events` with status 'pending'
- ✅ Webhook idempotency prevents duplicate commissions
- ✅ Commission only recorded if `accountant_id` is set on subscription

**Technical Notes**:
```php
// CommissionService::recordRecurring()
$grossAmount = 29.00; // Company paid €29 (Standard tier)
$commissionRate = 0.20; // 20% to accountant
$commission = $grossAmount * $commissionRate; // €5.80

AffiliateEvent::create([
    'accountant_id' => $subscription->accountant_id,
    'company_id' => $subscription->company_id,
    'type' => 'recurring_commission',
    'gross_amount' => $grossAmount,
    'commission_amount' => $commission,
    'month_ref' => now()->format('Y-m'),
    'status' => 'pending', // Will be 'paid' after payout
    'metadata' => [
        'provider' => 'paddle',
        'subscription_id' => $subscription->provider_subscription_id,
    ],
]);
```

---

### Milestone 1.2: Multi-Level Commission Logic (Week 2)
**Tickets**:
- **AC-01-10**: Implement direct accountant commission (15% for Standard tier)
- **AC-01-11**: Implement upline commission (5% if accountant has upline)
- **AC-01-12**: Implement sales rep override (5% if sales rep exists)
- **AC-01-13**: Add `users.upline_id` and `users.sales_rep_id` fields
- **AC-01-14**: Update commission calculation to handle multi-level splits

**Schema Changes**:
```sql
ALTER TABLE users
ADD COLUMN upline_id INT UNSIGNED NULL,
ADD COLUMN sales_rep_id INT UNSIGNED NULL,
ADD FOREIGN KEY (upline_id) REFERENCES users(id),
ADD FOREIGN KEY (sales_rep_id) REFERENCES users(id);
```

**Commission Breakdown Example** (Standard tier €29):
- Direct accountant: €4.35 (15%)
- Upline: €1.45 (5%)
- Sales rep: €1.45 (5%)
- **Total**: €7.25 (25% total, leaving 75% = €21.75 for Facturino)

**Acceptance**:
- ✅ Company pays €29 → 3 separate `affiliate_events` created
- ✅ Direct accountant gets 15%, upline gets 5%, sales rep gets 5%
- ✅ If no upline/sales_rep, only direct accountant gets commission
- ✅ Commission splits configurable in `config/affiliate.php`

---

### Milestone 1.3: Bounty System (Week 2-3)
**Tickets**:
- **AC-01-20**: Implement €300 accountant onboarding bounty (after KYC + 3 companies or 30 days)
- **AC-01-21**: Implement €50 company onboarding bounty (first paying company)
- **AC-01-22**: Create `Bounty` job to check eligibility and award bounties
- **AC-01-23**: Add bounty tracking to `affiliate_events` table (type = 'accountant_bounty' or 'company_bounty')
- **AC-01-24**: Schedule daily job to check and award eligible bounties

**Eligibility Rules**:
```php
// Accountant €300 bounty:
// - KYC status = 'verified'
// - AND (has 3+ active companies OR registered 30+ days ago)

// Company €50 bounty:
// - First company brought by accountant
// - AND company subscription status = 'active' (not trial)
```

**Acceptance**:
- ✅ Accountant completes KYC → brings 3 companies → gets €300 bounty
- ✅ Bounty recorded as `affiliate_event` with type 'accountant_bounty'
- ✅ Duplicate bounties prevented (one per accountant)
- ✅ Company bounty awarded on first paying company only

---

### Milestone 1.4: KYC Verification (Week 3)
**Tickets**:
- **AC-01-30**: Create KYC submission form in Partner Portal
- **AC-01-31**: Store KYC documents securely (encrypted storage)
- **AC-01-32**: Admin KYC review interface (approve/reject)
- **AC-01-33**: Email notifications for KYC status changes
- **AC-01-34**: Block payouts for accountants with `kyc_status = 'pending'` or `'rejected'`

**KYC Fields Required**:
- Full name (matches bank account)
- Tax ID (Macedonian EDB)
- Bank account (IBAN)
- ID document upload (encrypted)
- Proof of address upload (encrypted)

**Files to Create**:
```
resources/scripts/partner/views/KycSubmission.vue
app/Http/Controllers/V1/Partner/KycController.php
app/Models/KycDocument.php (encrypted storage)
database/migrations/2025_11_16_create_kyc_documents_table.php
```

**Acceptance**:
- ✅ Accountant can submit KYC documents
- ✅ Admin can review and approve/reject
- ✅ Payout blocked until KYC verified
- ✅ Documents stored encrypted (never plain text)

---

### Milestone 1.5: Payout Automation (Week 4)
**Tickets**:
- **AC-01-40**: Create `CalculatePayouts` command (runs on 5th of each month)
- **AC-01-41**: Implement minimum payout threshold (€100)
- **AC-01-42**: Create `payouts` table to track payout batches
- **AC-01-43**: Generate payout CSV for bank transfer
- **AC-01-44**: Mark `affiliate_events` as paid after payout

**Payout Calculation Logic**:
```php
// On 5th of each month:
// 1. Find all accountants with kyc_status = 'verified'
// 2. Sum pending affiliate_events for previous month
// 3. If total >= €100, create payout record
// 4. Mark events as paid
// 5. Generate CSV for bank transfer
```

**Payout CSV Format**:
```csv
accountant_name,iban,amount,reference
John Doe Accounting,MK07250120000058984,€285.50,PAYOUT-2025-11-05-001
Jane Smith CPA,MK07300000000424425,€120.00,PAYOUT-2025-11-05-002
```

**Acceptance**:
- ✅ Command runs on 5th of month via scheduler
- ✅ Only verified accountants included
- ✅ Minimum €100 threshold enforced
- ✅ CSV downloadable from admin panel
- ✅ Events marked as paid (can't be paid twice)

---

### Milestone 1.6: Affiliate Dashboard (Week 5)
**Tickets**:
- **AC-01-50**: Build Vue.js affiliate dashboard (earnings overview)
- **AC-01-51**: Referral stats page (list of companies brought, their tier, status)
- **AC-01-52**: Event history page (paginated list of all affiliate_events)
- **AC-01-53**: Payout history page (past payouts received)
- **AC-01-54**: Referral link generator with UTM tracking

**Dashboard Widgets**:
- Pending earnings (current month)
- Monthly earnings (last 12 months chart)
- Lifetime earnings
- Active companies count
- Next payout estimate (if >= €100)

**Files to Create**:
```
resources/scripts/partner/views/Dashboard.vue
resources/scripts/partner/views/Referrals.vue
resources/scripts/partner/views/Earnings.vue
resources/scripts/partner/views/Payouts.vue
```

**Acceptance**:
- ✅ Accountant can see real-time earnings
- ✅ Can view which companies are generating commissions
- ✅ Can generate referral links (e.g., https://facturino.mk/signup?ref=JOHN123)
- ✅ All data accurate (matches database)

---

## 📋 TRACK 2: COMPANY FEATURE GATING
**Duration**: 3-4 weeks
**Agent**: FeatureGatingAgent (Senior Backend + Frontend Dev)
**Depends on**: Phase 1 subscription schema

### Milestone 2.1: Invoice Limits Middleware (Week 1) ✅ COMPLETED
**Status**: 🟢 DONE (November 14, 2025)
**Completed by**: FeatureGatingAgent

**Tickets**:
- ✅ **FG-01-00**: Create `CheckInvoiceLimit` middleware
- ✅ **FG-01-01**: Define limits per tier in config (Free: 5/mo, Starter: 50/mo, Standard: 200/mo, Business: 1000/mo, Max: unlimited)
- ✅ **FG-01-02**: Block invoice creation if limit exceeded (return 402 Payment Required)
- ✅ **FG-01-03**: Display upgrade CTA when limit reached
- ✅ **FG-01-04**: Reset counter monthly (via InvoiceCountService)

**What Was Built**:
1. **config/subscriptions.php** - Comprehensive config with tier limits, features, upgrade messages
2. **app/Services/InvoiceCountService.php** - Service for counting invoices with Redis caching
3. **app/Http/Middleware/CheckInvoiceLimit.php** - Middleware enforcing invoice limits
4. **app/Http/Middleware/CheckSubscriptionTier.php** - Generic feature gating middleware
5. **bootstrap/app.php** - Registered middleware aliases: `invoice-limit` and `tier`
6. **routes/api.php** - Applied `invoice-limit` middleware to POST /invoices route

**Tier Limits**:
```php
// config/subscriptions.php
'tiers' => [
    'free' => ['invoice_limit' => 5, 'users' => 1],
    'starter' => ['invoice_limit' => 50, 'users' => 1],
    'standard' => ['invoice_limit' => 200, 'users' => 3],
    'business' => ['invoice_limit' => 1000, 'users' => 5],
    'max' => ['invoice_limit' => null, 'users' => null], // unlimited
],
```

**Implementation Details**:
- Invoice count cached for 5 minutes (configurable)
- Counts reset automatically on 1st of each month
- Returns 402 Payment Required with upgrade CTA
- Includes Paddle checkout URL in response
- Supports trial accounts (Standard features for 14 days)
- Non-destructive (doesn't delete data, just locks access)

**Acceptance Criteria**:
- ✅ Free tier: 6th invoice creation blocked with upgrade prompt
- ✅ Starter tier: 51st invoice blocked
- ✅ Max tier: Can create unlimited invoices
- ✅ Limit resets on 1st of each month
- ✅ Upgrade CTA includes Paddle checkout URL
- ✅ Cache performance < 50ms per check
- ✅ All syntax checks passed

**Personal Notes**:
- Implemented robust caching to avoid database hits on every request
- Created generic `tier` middleware for future feature gating (e-Faktura, bank feeds, etc.)
- Middleware integrates cleanly with existing CompanyMiddleware (uses company header)
- Ready for frontend integration (returns clear error messages and checkout URLs)

---

### Milestone 2.2: E-Faktura Gating (Week 2)
**Tickets**:
- **FG-01-10**: Gate e-Faktura sending behind Standard+ tier
- **FG-01-11**: Gate QES signing behind Standard+ tier
- **FG-01-12**: Display "Upgrade to Standard" CTA if Free/Starter user tries to send e-Faktura
- **FG-01-13**: Update pricing page to highlight e-Faktura as Standard+ feature

**Files to Modify**:
```
app/Http/Controllers/V1/Admin/Invoice/ExportXmlController.php (add tier check)
resources/scripts/admin/views/invoices/InvoiceDetail.vue (show/hide e-Faktura buttons)
```

**Acceptance**:
- ✅ Free/Starter users see "Upgrade to Standard" when clicking "Send e-Faktura"
- ✅ Standard+ users can send e-Faktura
- ✅ Upgrade CTA links to Paddle checkout

---

### Milestone 2.3: Bank Feed Gating (Week 2)
**Tickets**:
- **FG-01-20**: Gate PSD2 bank connections behind Business+ tier
- **FG-01-21**: Allow CSV import for all tiers (free alternative)
- **FG-01-22**: Gate automatic reconciliation behind Business+ tier
- **FG-01-23**: Display "Upgrade to Business" CTA if Standard user tries to connect bank

**Acceptance**:
- ✅ Business+ users can connect banks via PSD2
- ✅ Free/Starter/Standard users can still import CSV manually
- ✅ Reconciliation dashboard shows upgrade CTA for lower tiers

---

### Milestone 2.4: User Limit Enforcement (Week 3)
**Tickets**:
- **FG-01-30**: Enforce user limits per tier (Free: 1, Starter: 1, Standard: 3, Business: 5, Max: unlimited)
- **FG-01-31**: Block user invitation if limit reached
- **FG-01-32**: Display current user count vs. limit in settings
- **FG-01-33**: Show upgrade CTA when inviting beyond limit

**Acceptance**:
- ✅ Free tier: Can't invite 2nd user
- ✅ Standard tier: Can invite up to 3 users
- ✅ Upgrade CTA shown when limit reached

---

### Milestone 2.5: Trial Management (Week 3-4)
**Tickets**:
- **FG-01-40**: Implement 14-day generic trial (all tiers except Free)
- **FG-01-41**: Show trial countdown in dashboard
- **FG-01-42**: Block features after trial ends (graceful degradation to Free tier)
- **FG-01-43**: Email reminders (7 days before, 1 day before, on expiry)

**Trial Flow**:
```
1. New company created → auto-enrolled in Standard trial (14 days)
2. Full Standard features unlocked
3. After 14 days: If not subscribed → downgrade to Free tier
4. Invoices created during trial remain accessible (read-only)
```

**Acceptance**:
- ✅ New company gets 14-day Standard trial automatically
- ✅ Trial countdown visible in dashboard
- ✅ Email sent 7 days before expiry
- ✅ After expiry: Features locked, CTA to subscribe

---

## 📋 TRACK 3: SUPPORT TICKETING SYSTEM
**Duration**: 2-3 weeks
**Agent**: SupportAgent (Fullstack Dev)
**Depends on**: Laravel Ticket package (already installed in Phase 1 ✅)

### Milestone 3.1: Customer Ticket Portal (Week 1)
**Tickets**:
- **SUP-01-00**: Create Vue.js ticket list page
- **SUP-01-01**: Create ticket submission form (title, description, category, priority)
- **SUP-01-02**: Create ticket detail view (messages thread)
- **SUP-01-03**: Add reply functionality
- **SUP-01-04**: Add attachment support (images, PDFs)

**Categories**:
- Billing & Subscriptions
- Technical Issues
- Feature Requests
- General Questions

**Priorities**:
- Low, Medium, High, Urgent

**Files to Create**:
```
resources/scripts/admin/views/support/TicketList.vue
resources/scripts/admin/views/support/CreateTicket.vue
resources/scripts/admin/views/support/TicketDetail.vue
app/Http/Controllers/V1/Admin/Support/TicketController.php
```

**Acceptance**:
- ✅ Users can create tickets
- ✅ Users can reply to agent responses
- ✅ Users can upload attachments (max 5MB)
- ✅ Tickets scoped to company (tenant isolation)

---

### Milestone 3.2: Agent Dashboard (Week 2)
**Tickets**:
- **SUP-01-10**: Admin ticket dashboard (all tickets across all companies)
- **SUP-01-11**: Agent assignment system
- **SUP-01-12**: Status management (Open, In Progress, Resolved, Closed)
- **SUP-01-13**: Internal notes (not visible to customer)
- **SUP-01-14**: Canned responses (quick reply templates)

**Agent Dashboard Features**:
- Filter by status, priority, category
- Search by ticket ID, customer name, keyword
- Assign to self or other agents
- Bulk actions (close multiple, change priority)

**Acceptance**:
- ✅ Agents can see all tickets
- ✅ Can assign tickets to themselves
- ✅ Can add internal notes
- ✅ Can use canned responses for common questions

---

### Milestone 3.3: Email Notifications (Week 3)
**Tickets**:
- **SUP-01-20**: Email customer on new reply from agent
- **SUP-01-21**: Email agent on new reply from customer
- **SUP-01-22**: Email customer on status change (e.g., "Your ticket is resolved")
- **SUP-01-23**: Email agent on new ticket assignment
- **SUP-01-24**: Add email preferences (allow users to disable notifications)

**Acceptance**:
- ✅ Customer gets email when agent replies
- ✅ Agent gets email when customer replies
- ✅ Users can opt out of notifications

---

## 📋 TRACK 4: UI POLISH & RESPONSIVENESS
**Duration**: 4-5 weeks
**Agent**: UIAgent (Senior Frontend Dev + Designer)
**Focus**: Mobile responsiveness, UX improvements, professional polish

### Milestone 4.1: Mobile Responsiveness Audit (Week 1)
**Status**: ✅ AUDIT COMPLETED (November 14, 2025)
**Tickets**:
- ✅ **UI-01-00**: Audit all pages on mobile (iPhone, Android, tablet) - COMPLETED
- ✅ **UI-01-01**: Fix navigation (hamburger menu, collapsible sidebar) - ALREADY IMPLEMENTED
- ⏸️ **UI-01-02**: Fix invoice list (card view on mobile, table on desktop) - IN PROGRESS
- ⏸️ **UI-01-03**: Fix invoice detail (collapsible sections) - PENDING
- ⏸️ **UI-01-04**: Fix migration wizard (vertical steps on mobile) - PENDING

**Audit Documentation**:
- `/documentation/roadmaps/audits/TRACK4_MILESTONE_4.1_AUDIT.md`
- `/documentation/roadmaps/audits/TRACK4_INITIAL_RESEARCH_SUMMARY.md`

**Target Devices**:
- iPhone 13 (390px)
- Samsung Galaxy S21 (360px)
- iPad (768px)
- Desktop (1920px)

**Key Findings**:
- ✅ Excellent responsive infrastructure (HeadlessUI, Tailwind, iOS support)
- ✅ Mobile navigation already production-ready (hamburger + overlay)
- ✅ Touch targets meet 44px minimum standard
- ⚠️ Invoice list needs card view for mobile (table doesn't adapt)
- ⚠️ Import wizard grid needs responsive breakpoints
- ⚠️ Invoice detail needs collapsible sections

**Acceptance**:
- ✅ All pages usable on 360px width
- ✅ Touch targets >= 44px (iOS standard)
- ✅ No horizontal scrolling
- ✅ Forms keyboard-friendly

// CLAUDE-CHECKPOINT

---

### Milestone 4.2: Dashboard Redesign (Week 2)
**Tickets**:
- **UI-01-10**: Modern card-based layout
- **UI-01-11**: Revenue chart (last 12 months)
- **UI-01-12**: Recent invoices widget
- **UI-01-13**: Overdue invoices alert
- **UI-01-14**: Quick actions (New Invoice, New Customer, etc.)

**Acceptance**:
- ✅ Dashboard loads in < 1 second
- ✅ Charts interactive (hover tooltips)
- ✅ Responsive on all devices

---

### Milestone 4.3: Company Switcher Polish (Week 2)
**Tickets**:
- **UI-01-20**: Redesign company dropdown (searchable, avatars)
- **UI-01-21**: Add "Create New Company" button
- **UI-01-22**: Show active company badge
- **UI-01-23**: Keyboard navigation (arrow keys to switch)

**Acceptance**:
- ✅ Can switch companies with keyboard only
- ✅ Search works for companies with 10+ entries
- ✅ Company logo/avatar shown

---

### Milestone 4.4: Migration Wizard UX (Week 3)
**Tickets**:
- **UI-01-30**: Drag-drop field mapping (replace select dropdowns)
- **UI-01-31**: Visual preview (show sample data)
- **UI-01-32**: Confidence indicators (green = 90%+, yellow = 60-89%, red = <60%)
- **UI-01-33**: Batch actions (map multiple fields at once)
- **UI-01-34**: Mobile-responsive (vertical layout)

**Acceptance**:
- ✅ Can drag field from source to target
- ✅ Preview shows real data from CSV
- ✅ Works on tablet/mobile

---

### Milestone 4.5: Loading States & Empty States (Week 4)
**Tickets**:
- **UI-01-40**: Skeleton screens for all list views
- **UI-01-41**: Loading spinners for async actions
- **UI-01-42**: Empty state illustrations (no invoices, no customers, etc.)
- **UI-01-43**: Error state illustrations (404, 500, network error)

**Acceptance**:
- ✅ No "flash of unstyled content"
- ✅ Loading indicators on all async actions
- ✅ Empty states have helpful CTA (e.g., "Create your first invoice")

---

### Milestone 4.6: Notification Center (Week 5)
**Tickets**:
- **UI-01-50**: Toast notifications (success, error, warning, info)
- **UI-01-51**: Notification bell (unread count)
- **UI-01-52**: Notification list (invoice paid, new customer, etc.)
- **UI-01-53**: Mark as read/unread
- **UI-01-54**: Notification preferences (which events to notify)

**Acceptance**:
- ✅ Toast appears on actions (invoice created, payment received)
- ✅ Bell icon shows unread count
- ✅ Can dismiss or mark as read

---

## 📋 TRACK 5: PRODUCTION INFRASTRUCTURE
**Duration**: 6 weeks (spans entire Phase 2)
**Agent**: DevOpsAgent (Senior DevOps + Security Engineer)
**Focus**: Security, monitoring, backups, performance

### Milestone 5.1: Security Hardening (Week 1-2)
**Tickets**:
- **SEC-01-00**: Enable Laravel Fortify 2FA (optional for users)
- **SEC-01-01**: Implement API rate limiting (60 req/min for logged-in, 10/min for guests)
- **SEC-01-02**: Add security headers (CSP, X-Frame-Options, HSTS)
- **SEC-01-03**: Reduce admin session timeout (2 hours)
- **SEC-01-04**: Enable CSRF protection on all forms
- **SEC-01-05**: Run external penetration test

**Acceptance**:
- ✅ 2FA available in user settings
- ✅ Rate limiting blocks brute force
- ✅ Security headers pass securityheaders.com scan
- ✅ Sessions expire after 2 hours of inactivity

---

### Milestone 5.2: Performance Optimization (Week 2-3)
**Tickets**:
- **PERF-01-00**: Enable Redis for cache + queues
- **PERF-01-01**: Queue IFRS ledger posting (move to background)
- **PERF-01-02**: N+1 query audit (use Laravel Telescope)
- **PERF-01-03**: Add database indexes (invoices.status, payments.status, etc.)
- **PERF-01-04**: CDN setup (CloudFlare for assets)
- **PERF-01-05**: Load test (1000 concurrent users with Artillery)

**Performance Targets**:
- Average response time: < 200ms
- 95th percentile: < 500ms
- Time to first byte: < 100ms
- Lighthouse score: > 90

**Acceptance**:
- ✅ Load test passes (1000 users, no errors)
- ✅ Dashboard loads in < 200ms
- ✅ No N+1 queries in critical paths

---

### Milestone 5.3: Monitoring & Alerting (Week 3-4)
**Tickets**:
- **MON-01-00**: Enable Prometheus metrics (FEATURE_MONITORING=true)
- **MON-01-01**: Create Grafana dashboards (CPU, memory, queue depth, response time)
- **MON-01-02**: Set up alerts (cert expiry < 7 days, error rate > 5%, failed jobs > 10)
- **MON-01-03**: External uptime monitoring (UptimeRobot)
- **MON-01-04**: Centralized logging (Laravel Log Viewer or external service)

**Dashboards**:
1. **System Health**: CPU, RAM, disk, network
2. **Application**: Response time, request rate, error rate
3. **Business**: Revenue (MRR), active companies, invoices created
4. **Queues**: Job throughput, failed jobs, queue depth

**Acceptance**:
- ✅ Grafana dashboards accessible to admins
- ✅ Alerts sent to Slack/email
- ✅ Uptime monitoring sends alert on downtime

---

### Milestone 5.4: Backup & Disaster Recovery (Week 4)
**Tickets**:
- **BAK-01-00**: Configure Spatie Backup for production
- **BAK-01-01**: Test backup restore process
- **BAK-01-02**: Set up point-in-time recovery (PostgreSQL PITR)
- **BAK-01-03**: Implement 30-day retention policy
- **BAK-01-04**: Run disaster recovery simulation (restore from backup)

**Backup Schedule**:
- Database: Daily at 2am UTC
- Files (certificates, uploads): Daily at 3am UTC
- Retention: 7 daily, 4 weekly, 12 monthly

**Acceptance**:
- ✅ Backups running automatically
- ✅ Can restore from backup within 30 minutes
- ✅ DR test successful (full restore to staging)

---

### Milestone 5.5: Legal & Compliance (Week 5)
**Tickets**:
- **LEG-01-00**: Draft Terms of Service
- **LEG-01-01**: Draft Privacy Policy (GDPR compliant)
- **LEG-01-02**: Implement cookie consent banner
- **LEG-01-03**: Create Data Processing Agreement (DPA) template for partners
- **LEG-01-04**: Update LEGAL_NOTES.md (AGPL compliance, upstream attribution)

**Acceptance**:
- ✅ Terms of Service published at /terms
- ✅ Privacy Policy published at /privacy
- ✅ Cookie consent banner shown on first visit
- ✅ DPA template available for accountants

---

### Milestone 5.6: Documentation (Week 5-6)
**Tickets**:
- **DOC-01-00**: User manual (30 pages, Macedonian + English)
- **DOC-01-01**: Admin guide (system administration)
- **DOC-01-02**: Partner onboarding guide (for accountants)
- **DOC-01-03**: Video tutorials (10 videos, 3-5 min each)
- **DOC-01-04**: API documentation (OpenAPI/Swagger)
- **DOC-01-05**: FAQ (common questions + troubleshooting)

**Video Tutorial Topics**:
1. Creating your first invoice
2. Sending e-Faktura
3. Connecting your bank
4. Importing data from old system
5. Managing customers
6. Payment reconciliation
7. Partner referral system
8. Subscription management
9. User management
10. Generating reports

**Acceptance**:
- ✅ User manual complete and published
- ✅ 10 videos recorded and uploaded
- ✅ FAQ has 20+ questions
- ✅ API docs auto-generated from code

---

## 📋 TRACK 6: BETA TESTING & LAUNCH
**Duration**: 2 weeks (weeks 7-8)
**Agent**: LaunchAgent (Product Manager + QA Lead)
**Focus**: Real user testing, bug fixes, go-live

### Milestone 6.1: Beta Deployment (Week 7)
**Tickets**:
- **LAUNCH-01-00**: Deploy to Railway production (separate from staging)
- **LAUNCH-01-01**: Onboard 5 accountant partners (provide training)
- **LAUNCH-01-02**: Each accountant brings 2 companies (10 companies total)
- **LAUNCH-01-03**: Enable monitoring and alerts
- **LAUNCH-01-04**: Set up support rotation (who responds to tickets)

**Beta Access Criteria**:
- Accountants must complete KYC
- Companies must be real businesses (not test accounts)
- All users sign NDA (beta program)

**Acceptance**:
- ✅ 5 accountants onboarded and trained
- ✅ 10 companies actively using the system
- ✅ Support team ready to respond within 24 hours

---

### Milestone 6.2: Feedback Collection & Iteration (Week 7)
**Tickets**:
- **LAUNCH-01-10**: Create feedback form (embedded in app)
- **LAUNCH-01-11**: Schedule weekly check-in calls with beta users
- **LAUNCH-01-12**: Track bugs and feature requests in GitHub Issues
- **LAUNCH-01-13**: Prioritize critical fixes (P0: blocks usage, P1: degrades UX, P2: nice-to-have)
- **LAUNCH-01-14**: Deploy fixes daily

**Feedback Channels**:
- In-app feedback form
- Support tickets
- Weekly video calls
- Slack channel (private beta group)

**Acceptance**:
- ✅ Feedback form submitted by all 10 companies
- ✅ All P0 bugs fixed within 24 hours
- ✅ All P1 bugs fixed within 1 week

---

### Milestone 6.3: Public Launch (Week 8)
**Tickets**:
- **LAUNCH-01-20**: Final QA (all critical paths tested)
- **LAUNCH-01-21**: Enable production features (FEATURE_PARTNER_MOCKED_DATA=false)
- **LAUNCH-01-22**: Public signup page (remove beta-only restriction)
- **LAUNCH-01-23**: Launch announcement (blog post, social media, email)
- **LAUNCH-01-24**: Monitor for 48 hours (on-call rotation)

**Launch Checklist**:
- [ ] All features tested end-to-end
- [ ] Monitoring dashboards showing green
- [ ] Backups verified in last 24 hours
- [ ] Support team trained and ready
- [ ] Legal docs (Terms, Privacy) published
- [ ] Rate limiting tested (no false positives)
- [ ] Payment processing tested (Paddle + CPAY)
- [ ] Email delivery tested (transactional + marketing)

**Acceptance**:
- ✅ Public signup works
- ✅ First 50 signups onboarded successfully
- ✅ No critical bugs in first 48 hours
- ✅ Support response time < 4 hours

---

## 🚂 PARALLEL EXECUTION STRATEGY

### Week 1-2: Foundation
**Agents Working in Parallel**:
- AffiliateAgent → AC-01-00 to AC-01-04 (Commission recording)
- FeatureGatingAgent → FG-01-00 to FG-01-01 (Invoice limits)
- DevOpsAgent → SEC-01-00 to SEC-01-05 (Security hardening)

### Week 3-4: Core Features
**Agents Working in Parallel**:
- AffiliateAgent → AC-01-10 to AC-01-23 (Multi-level + bounties)
- FeatureGatingAgent → FG-01-10 to FG-01-30 (E-Faktura + bank gating)
- SupportAgent → SUP-01-00 to SUP-01-04 (Customer portal)
- UIAgent → UI-01-00 to UI-01-11 (Mobile + dashboard)
- DevOpsAgent → PERF-01-00 to PERF-01-05 (Performance)

### Week 5-6: Polish & Documentation
**Agents Working in Parallel**:
- AffiliateAgent → AC-01-30 to AC-01-54 (KYC + payouts + dashboard)
- FeatureGatingAgent → FG-01-40 to FG-01-43 (Trial management)
- SupportAgent → SUP-01-10 to SUP-01-24 (Agent dashboard + emails)
- UIAgent → UI-01-20 to UI-01-54 (Company switcher + notifications)
- DevOpsAgent → MON-01-00 to DOC-01-05 (Monitoring + docs)

### Week 7-8: Beta & Launch
**All Agents Converge**:
- LaunchAgent → LAUNCH-01-00 to LAUNCH-01-24 (Beta + launch)
- All other agents on standby for bug fixes

---

## 📊 EFFORT ESTIMATION

| Track | Hours | Notes |
|-------|-------|-------|
| Affiliate System | 120-150h | Complex multi-level commission logic |
| Feature Gating | 60-80h | Middleware + UI updates |
| Support Ticketing | 40-50h | Package already installed, just UI |
| UI Polish | 100-120h | Mobile responsiveness is tedious |
| Production Infra | 140-180h | Security, monitoring, docs, backups |
| Beta Testing | 40-60h | User onboarding, bug fixes |
| **TOTAL** | **500-640h** | With 6 senior devs: 6-8 weeks |

---

## ✅ SUCCESS CRITERIA

### Technical Success
1. ✅ All 5 subscription tiers working (Paddle + CPAY)
2. ✅ Commission tracking accurate (verified with manual calculations)
3. ✅ Affiliate payouts automated (CSV generated on 5th of month)
4. ✅ Feature gating enforced (invoice limits, e-Faktura, bank feeds)
5. ✅ Support ticketing operational
6. ✅ UI mobile-responsive (tested on iOS + Android)
7. ✅ 2FA enabled
8. ✅ Monitoring operational (Grafana dashboards + alerts)
9. ✅ Backups verified (restore tested)
10. ✅ Documentation complete (user manual + videos)

### Business Success
1. ✅ 50 companies onboarded in first month
2. ✅ 10+ paying companies (not on free tier)
3. ✅ 5+ accountants actively referring
4. ✅ MRR > €500 (Monthly Recurring Revenue)
5. ✅ Support response time < 24 hours
6. ✅ Uptime > 99.5% (measured by UptimeRobot)

---

## 🚨 RISK MITIGATION

### Technical Risks
1. **Commission calculation bugs** → Extensive unit tests, manual verification with spreadsheet
2. **Paddle webhook failures** → Idempotency, retry logic, monitoring
3. **Performance degradation** → Load testing, Redis caching, query optimization
4. **Security vulnerabilities** → Penetration test, security audit, bug bounty program

### Business Risks
1. **Accountants don't refer** → Provide marketing materials, higher bounties, training
2. **Companies churn** → Improve onboarding, proactive support, feature requests
3. **Payment processor issues** → Test both Paddle + CPAY, have fallback
4. **Legal compliance** → Lawyer review of Terms/Privacy, GDPR consultant

---

## 📦 DELIVERABLES

### Phase 2 Completion Checklist
- [ ] Affiliate system fully operational (commissions + payouts)
- [ ] Company feature gating enforced (all 5 tiers)
- [ ] Support ticketing available to all users
- [ ] UI polished and mobile-responsive
- [ ] 2FA enabled for users
- [ ] Monitoring dashboards operational
- [ ] Backups automated and tested
- [ ] Legal docs published (Terms, Privacy, DPA)
- [ ] User manual + 10 videos published
- [ ] 50 beta users onboarded successfully
- [ ] Public launch completed

---

## 🎯 NEXT STEPS (POST-PHASE 2)

### Phase 3: Growth & Optimization (3-6 months)
1. **Marketing Automation** - Drip campaigns, onboarding emails
2. **Advanced Reporting** - IFRS compliance reports, tax filings
3. **API Access** - Zapier integration, REST API for Business+ tier
4. **White-Label** - Custom branding for Enterprise tier
5. **Mobile App** - Native iOS/Android apps
6. **Integrations** - WooCommerce, Stripe, other accounting software

---

## 📞 AGENT COMMUNICATION PROTOCOL

### Daily Standup (Async)
Each agent posts to Slack:
- What I completed yesterday
- What I'm working on today
- Any blockers

### PR Review Process
- All PRs require 1 approval from another agent
- Critical PRs (security, payments) require 2 approvals
- PRs must be < 500 LOC (split if larger)
- PRs must include tests

### Merge Conflicts
- Agent with earliest PR gets priority
- Other agent rebases and resolves conflicts
- ReleaseManager mediates if dispute

### Bug Priority
- **P0** (Critical): Blocks core functionality → Fix within 4 hours
- **P1** (High): Degrades UX → Fix within 24 hours
- **P2** (Medium): Minor issue → Fix within 1 week
- **P3** (Low): Nice-to-have → Backlog

---

## 🏁 CONCLUSION

Phase 2 transforms the **architectural foundation** (Phase 1) into a **revenue-generating product** ready for real customers.

**Key Differences from Phase 1**:
- Phase 1: Infrastructure (database, auth, billing controllers)
- Phase 2: Business logic (commissions, feature gating, support, polish)

**Timeline**: 6-8 weeks with 6 Harvard-level senior devs working in parallel

**Budget**: ~600 hours × 6 devs = ~100 hours per dev (manageable sprint)

**Risk Level**: LOW (Phase 1 foundation is solid, Phase 2 builds on proven architecture)

**Go/No-Go Decision Point**: After beta testing (Week 7), evaluate:
- Are P0/P1 bugs resolved?
- Do accountants and companies like the product?
- Is infrastructure stable?

If YES to all → Public launch Week 8 ✅
If NO → Extend beta, iterate, launch Week 10

---

**Ready to conquer Mars? Let's launch Phase 2! 🚀**
