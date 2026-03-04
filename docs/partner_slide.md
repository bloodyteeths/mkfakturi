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
AFTER 45 DAYS: Sliding scale kicks in
  - Each PAYING company covers 1 NON-PAYING company at Standard
  - Uncovered non-paying companies → drop to "Accountant Basic" (degraded)
  ↓
ACCOUNTANT NEVER PAYS. Worst case = some companies on Basic tier.
```

## Grace Period: 45 Days

- All portfolio companies get Standard features for 45 days
- Partner portal shows a **big countdown timer**: "X days left to bring your companies on board"
- After 45 days: 1:1 sliding scale kicks in automatically
- No extensions, no exceptions

## Sliding Scale (After Grace)

| Paying companies | Example (60 total) | Result |
|---|---|---|
| 0 paying | 0 of 60 | All 60 on accountant_basic |
| 10 paying | 10 of 60 | 10 covered at Standard, 40 on accountant_basic |
| 30 paying | 30 of 60 | 30 covered at Standard, 0 on accountant_basic |
| 30+ paying | 35 of 60 | All covered + accountant earns commission on 35 |

**Coverage order**: Oldest companies first (by created_at) get covered at Standard.

## Accountant Basic Tier (Degraded)

For non-paying, non-covered portfolio companies. NOT on pricing page.

**Has**: 15 invoices/mo, expenses, bills, reports, suppliers, basic bookkeeping
**Does NOT have**: e-Faktura, QES signing, bank connections, auto-reconciliation, API, multi-currency

This gives accountants enough to do bookkeeping but locks the features that companies actually need — creating pressure to subscribe.

## Commission

- 20% recurring monthly commission on each paying company's subscription
- Commission only shows in partner portal (NOT on public website — companies shouldn't see it)
- Commission starts from day 1 for any company that subscribes

## Open Question

> **FROM DAY 1: What pressure does the accountant feel to sell?**
>
> During the 45-day grace, everything works great. The accountant has no urgency.
> Options discussed but not finalized:
>
> - **Option A**: Show progress bar + timer only ("22/60 companies paying — 38 days left!")
> - **Option B**: Reduce features gradually (e.g., after 14 days some features start locking)
> - **Option C**: Companies get 14-day trial individually, then drop to free — accountant sees the pain and sells harder
> - **Option D**: Something else?
>
> **Decision needed**: What happens during the 45 days to make the accountant feel urgency?

---

## Company Import Feature (TODO)

Accountants need to bulk-import companies from their existing software (Pantheon, etc.). Two options:

1. **Upload file** — Accept `.prn` (Pantheon export), `.csv`, `.xlsx` with columns:
   - Company name, Tax ID (EDB), Registration number (MB), Address, City, Postal code, Email, Phone, Activity code
2. **Template download** — Provide a blank Excel/CSV template accountants can fill in

**Reference file**: `logs/Clienti.prn` (Pantheon export from М КОНСАЛТИНГ ТП, 53 companies)

**Implementation**:
- Add "Import Companies" button to PortfolioDashboard.vue
- New `PortfolioImportController` to parse uploaded file
- Auto-create companies using `Company::setupDefaultData()`
- Auto-link to partner portfolio with `is_portfolio_managed = 1`
- Show preview table before confirming import
- Downloadable template at `/api/v1/partner/portfolio-companies/template`

---

## What's Already Implemented (2026-03-04)

- [x] `accountant_basic` tier in config/subscriptions.php
- [x] Portfolio DB fields: partners, companies, partner_company_links
- [x] PortfolioTierService (recalculation logic)
- [x] PortfolioController + PortfolioCompanyController APIs
- [x] Vue dashboard + company creation views (basic)
- [x] Daily cron recalculation command
- [x] Partner portal "Portfolio" nav item
- [ ] Countdown timer on partner portal
- [ ] Grace period = 45 days (currently coded as 3 months — needs update)
- [ ] Day-1 urgency mechanism (open question above)
- [ ] Email notifications (grace ending soon, companies degraded)
- [ ] Partner portal stats visualization (paying vs uncovered)

## History

- User originally proposed 1-month grace + charge accountant like Pantheon if they fail
- AI proposed 3-month grace + never charge, just degrade features
- User objected: "I don't want to starve during 3 months"
- Final agreement: 45 days grace, degrade (never charge), sliding scale
- Commission NOT shown on public website (companies would get angry at accountants)
