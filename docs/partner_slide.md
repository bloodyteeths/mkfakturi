# Partner Portfolio Program — Final Plan

> Last updated: 2026-03-04

## Core Principle

**Accountants never pay. Ever.** They are our free salesforce to reach 80,000 companies in Macedonia.

---

## How It Works

```
ACCOUNTANT JOINS FREE → Adds all her companies (e.g., 60)
  ↓
DAY 1: All companies get Standard features (via accountant portfolio)
  ↓
45-DAY GRACE PERIOD starts — big countdown timer on partner portal
  ↓
Accountant's job: convince companies to subscribe (Starter €12+)
  ↓
AFTER 45 DAYS: Sliding scale + Credit Wallet kicks in
  - Each PAYING company covers 1 NON-PAYING company at Standard (1:1)
  - Commission (20%) → Credit Wallet → covers additional uncovered companies
  - If wallet doesn't cover → uncovered companies drop to VIEW-ONLY mode
  - Surplus commission → PAYOUT to accountant's bank account
  ↓
ACCOUNTANT NEVER PAYS. Worst case = some companies on view-only.
```

## Grace Period: 45 Days

- All portfolio companies get Standard features for 45 days
- Partner portal shows a **big countdown timer**: "X days left to bring your companies on board"
- Shows **wallet forecast**: "After grace: your commission covers X companies, Y remain view-only"
- After 45 days: 1:1 sliding scale + credit wallet kicks in automatically
- No extensions, no exceptions

## Sliding Scale (After Grace)

| Paying companies | Example (60 total) | Result |
|---|---|---|
| 0 paying | 0 of 60 | All 60 on view-only |
| 10 paying | 10 of 60 | 10 covered by 1:1 + wallet covers more, rest view-only |
| 30 paying | 30 of 60 | 30 covered by 1:1, wallet covers rest → payout |
| 30+ paying | 35 of 60 | All covered + surplus commission paid to accountant |

**Coverage order**: Oldest companies first (by created_at) get covered at Standard.

## Accountant Basic Tier (Degraded)

For non-paying, non-covered portfolio companies. NOT on pricing page.

**Has**: 15 invoices/mo, expenses, bills, reports, suppliers, basic bookkeeping
**Does NOT have**: e-Faktura, QES signing, bank connections, auto-reconciliation, API, multi-currency

This gives accountants enough to do bookkeeping but locks the features that companies actually need — creating pressure to subscribe.

## View-Only Mode (Uncovered Companies)

When a company is uncovered AND credit wallet doesn't cover it:

**CAN**: View all data (invoices, reports, expenses, bank transactions, everything)
**CAN**: Use AI chatbot (5 queries/month) — lets them ask questions about their data
**CANNOT**: Create, edit, or delete any records
**CANNOT**: Send invoices, generate e-Faktura, sign with QES
**CANNOT**: Connect banks, run reconciliation

### Why view-only (not locked out):
- Company SEES everything the accountant built → they see the VALUE
- They just can't DO anything new → immediate upgrade pressure
- AI chatbot keeps them engaged ("How much do I owe?")
- Better than full lockout which causes churn

## Commission

- 20% recurring monthly commission on each paying company's subscription
- Commission only shows in partner portal (NOT on public website — companies shouldn't see it)
- Commission starts from day 1 for any company that subscribes

## Credit Wallet (Commission → Coverage → Payout)

Commission is NOT directly paid out. Flow:

1. Accountant earns 20% on each paying company's subscription
2. Commission goes into a virtual **credit wallet**
3. **FIRST**: Wallet auto-covers uncovered companies (beyond 1:1 ratio) at Standard tier cost
4. **REMAINDER**: Paid out to accountant's bank account

### Example (60 companies, 20 paying):
- 20 paying × ~€32 avg = €640/month revenue → 20% = **€128/month commission**
- 1:1 covers 20 non-paying at Standard → 20 remain uncovered
- 20 uncovered × €39 Standard = €780/month cost to cover all
- Wallet: €128 commission covers ~3 companies (€128 / €39 = 3.28)
- Result: 23 covered at Standard, 17 on view-only, €0 payout

### Example (60 companies, 35 paying):
- 35 paying × ~€35 avg = €1,225/month revenue → 20% = **€245/month commission**
- 1:1 covers 25 non-paying at Standard → all covered!
- No uncovered companies → full commission is surplus
- Result: all 60 covered, **€245/month payout to accountant** (€2,940/year)

### Why this is sticky:
- Accountant NEEDS features for ALL companies to do their job (e-Faktura, bank, QES)
- To keep features → needs paying companies → commission auto-covers the rest
- More paying = more surplus commission = more payout
- Creates natural flywheel: **sell → earn → cover → profit**

## Day-1 Urgency (RESOLVED)

**Decision: Credit wallet forecast IS the urgency mechanism.**

During the 45-day grace, the partner portal shows:
- Countdown timer: "22 days left"
- Progress: "12/60 companies paying"
- Wallet forecast: "After grace: your commission covers 3 more, 45 remain view-only"
- Projected payout: "€0/month (all commission used for coverage)"

The math makes it self-evident: more paying companies = more payout and more covered companies. The accountant sees EXACTLY what happens when grace ends.

---

## Company Import Feature (DONE)

Implemented 2026-03-04. Accountants can bulk-import companies via CSV/Excel.

- **3-step wizard**: Upload → Preview (valid/duplicate/invalid) → Confirm
- **Template download**: `GET /api/v1/partner/portfolio-companies/template`
- **Flexible column mapping**: Supports Pantheon `.prn` exports with Cyrillic aliases
- **Duplicate detection**: Checks against existing portfolio companies by tax_id
- **Cache-based flow**: Preview cached 15 min, then confirm creates companies

Files:
- `PortfolioCompanyController.php` — template(), importPreview(), importConfirm()
- `PortfolioCompanyImport.vue` — 3-step wizard UI
- Route: `/admin/partner/portfolio/companies/import`

---

## What's Already Implemented (2026-03-04)

- [x] `accountant_basic` tier in config/subscriptions.php
- [x] Portfolio DB fields: partners, companies, partner_company_links
- [x] PortfolioTierService (recalculation logic)
- [x] PortfolioController + PortfolioCompanyController APIs
- [x] Vue dashboard + company creation views
- [x] Daily cron recalculation command
- [x] Partner portal "Portfolio" nav item
- [x] Countdown timer on partner portal (grace period banner with urgency colors)
- [x] Grace period = 45 days
- [x] Company bulk import (CSV/Excel with preview + confirm)
- [x] Template download for accountants
- [x] Partner portal stats visualization (paying vs covered vs uncovered)
- [ ] Credit wallet system (commission → coverage → payout logic)
- [ ] View-only mode enforcement (middleware/gate for uncovered companies)
- [ ] Wallet forecast on partner portal (projected coverage + payout after grace)
- [ ] Email notifications (grace ending soon, companies degraded, payout ready)

## History

- User originally proposed 1-month grace + charge accountant like Pantheon if they fail
- AI proposed 3-month grace + never charge, just degrade features
- User objected: "I don't want to starve during 3 months"
- Agreed: 45 days grace, degrade (never charge), sliding scale
- Commission NOT shown on public website (companies would get angry at accountants)
- Added credit wallet: commission covers uncovered companies first, surplus = payout
- Added view-only mode: uncovered companies can view but not edit (+ AI chatbot)
