# Facturino Payment Infrastructure Roadmap
**Version:** 1.1
**Last Updated:** 2026-02-05
**Vision:** Build the payment infrastructure layer for Emerging Europe (120M people across 13 countries)

---

## One-Page Summary for Investors

### The Opportunity
**€230M base / €3B upside TAM** | **120M people** | **No unified bank-data + payment layer for WB SMB SaaS**

Southeast and Central Europe has no unified, developer-friendly bank-data + payment orchestration layer. Banks have APIs but they're inconsistent, undocumented, and not productized for SMB SaaS. PSPs exist but are fragmented by country. Cross-border settlement is slow and expensive (often multiple days + high fees), especially outside SEPA. ~320K SMBs in Western Balkans (MK+RS+XK+AL) manually reconcile every transaction.

*Base TAM = Western Balkans SaaS + partner PSP routing (0.3% net take). Upside = full region + own acquiring (1.2% take). We plan against base case. See appendix for detailed assumptions.*

### Our Wedge
**Reconciliation is the wedge. Bank aggregation is the moat.**

**Core Product Flow (the "aha moment"):**
```
┌─────────────────────────────────────────────────────────────────┐
│  1. IMPORT          2. MATCH           3. APPROVE    4. DONE   │
│  ─────────────────────────────────────────────────────────────  │
│                                                                  │
│  [Upload CSV]  →  [Suggested      →  [One-click  →  [Ledger    │
│  or forward       matches with       approve or     updated,   │
│  bank email       confidence %]      manual fix]    report     │
│                                                     ready]     │
│                                                                  │
│  Time: 30 sec      Time: auto        Time: 2 min    Total: <5m │
│                                                                  │
│  Phase 2 upgrade: CSV → PSD2 auto-sync (same flow, no upload)  │
└─────────────────────────────────────────────────────────────────┘
```
*Live demo: app.facturino.mk (request sandbox access)*
```
Auto-reconciliation (imports) → PSD2 upgrade (moat) → Payment rails → Regional infrastructure
```

We start with a painful, specific problem: **SMB accountants spend 2-4 hours/week manually matching bank transactions to invoices.**

1. **Week 1-4:** Ship auto-reconciliation with CSV/email imports (value NOW)
2. **Month 2-6:** Add PSD2 as premium "auto-sync" upgrade (moat building)
3. **Month 6+:** Expand geography and add payment initiation

**The wedge is reconciliation. PSD2 is the upgrade that creates lock-in.**

### Traction (Now - Q1 2026)
- Production-ready invoicing platform (live at app.facturino.mk)
- Bank integration status:

| Bank | Status | What Works | What's Next |
|------|--------|------------|-------------|
| NLB Banka | 🟡 Sandbox | OAuth flow, account list | Transaction sync testing |
| Stopanska | 🟡 Sandbox | OAuth flow | Account endpoint integration |
| Komercijalna | 🔴 Research | API docs reviewed | Sandbox access pending |

- CSV import for all 3 banks: ✅ Ready to ship (no PSD2 dependency)
- Q1 2026 public launch target

### Design Partners & Distribution

**Ideal Customer Profile (ICP):**
| Attribute | Phase 1 Target |
|-----------|----------------|
| **Verticals** | E-commerce sellers, agencies, distributors, service providers |
| **Size** | 20-200 invoices/month, 50-500 bank transactions/month |
| **Pain** | 2-4 hrs/week manual reconciliation, multiple bank accounts |
| **Buyer** | Accounting office (pays for clients) or SMB owner directly |
| **Deal size** | €29-49/mo SaaS + transaction fees |

**Distribution hypothesis (testing now):** 1 accountant partner can onboard 20-50 SMBs/year once workflow is proven. If true: 10 partners = 200-500 SMBs without paid acquisition.

**Near-term milestones (dated):**
| Milestone | Target Date | Owner |
|-----------|-------------|-------|
| 10 accountant intro meetings booked | Feb 28, 2026 | Founder |
| 2 pilot accounting offices signed | Mar 15, 2026 | Founder |
| 20 paying SMBs (via pilots + direct) | Mar 31, 2026 | Growth |
| First accountant referral revenue | Apr 15, 2026 | Growth |

**Current status:** Early outreach. Numbers updated weekly.

### Why We Win First 10 Accountant Partners

**The pitch (30 seconds):**
> "Your clients spend 2-4 hours/week matching bank transactions to invoices. We cut that to 15 minutes. You look like a hero, we split the savings. Want to try it with 3 clients this week?"

**Why they say yes:**
| Objection | Our Answer |
|-----------|------------|
| "I already have software" | "This plugs into what you have—it's reconciliation, not replacement" |
| "My clients won't pay" | "You bill it as part of your service. €10-20/client/mo, you keep 20%" |
| "Too busy to learn new tools" | "We do the setup. You just forward bank statements by email" |
| "What if it breaks?" | "We're local. Same timezone, same language, same WhatsApp group" |

**Acquisition channels for first 10:**
1. **Warm intros** (founder network) - 3-5 offices
2. **LinkedIn outreach** to Skopje accountants - 3-5 offices
3. **Accounting association event** (ISOS) - 2-3 offices

**Success metric:** 2 signed pilots by Mar 15, 2026 validates the channel.

**Weekly Execution Scoreboard (Week of Feb 4, 2026 = Baseline):**
| Metric | This Week | Cumulative | Target |
|--------|-----------|------------|--------|
| Accounting offices identified | 0 | 25 (list built) | 50 by Feb 14 |
| Accountant intros booked | 0 | 0 | 10 by Feb 28 |
| Pilot offices signed | 0 | 0 | 2 by Mar 15 |
| SMB trials started | 0 | 0 | 50 by Mar 31 |
| Paid conversions | 0 | 0 | 20 by Mar 31 |
| Weekly active users (reconciliation) | 0 | 0 | 30 by Apr 15 |
| Auto-match % (median) | - | - | >60% |

*Baseline = Feb 4, 2026. Pipeline: 25 accounting offices identified in Skopje metro, outreach starting this week. Scoreboard updated every Monday.*

**Path to 500 Customers:**
```
5 pilot offices → each onboards 10 SMBs = 50 SMBs
    ↓
Weekly product reviews → match-rate improves → NPS tracked
    ↓
Happy pilots refer 2 more offices each = 10 new offices
    ↓
10 offices × 20 SMBs = 200 SMBs (organic)
    ↓
Parallel: content + SEO + direct = 300 more SMBs
    ↓
Total: 500 customers in 6 months
```
This is achievable without paid ads if pilot offices convert to advocates.

### The Ask
**Raising €3M seed to reach Series A milestones**

| Use of Funds | Allocation |
|--------------|------------|
| Engineering (platform + bank integrations) | 50% / €1.5M |
| Market expansion (Serbia, Kosovo, Albania) | 30% / €900K |
| Operations (legal, compliance, support) | 20% / €600K |

*Valuation discussed in conversations, not anchored here.*

### 24-Month Targets (by Q1 2028)
| Metric | Target |
|--------|--------|
| Banks connected | 25+ |
| Countries live | 8 |
| ARR | €5M |
| Transaction volume | €300M+/year |

**ARR Bridge to €5M:**
```
SMB SaaS:     5,000 customers × €50 ARPA = €250K MRR = €3.0M ARR
Payments:     €300M TPV × 0.4% net take = €1.2M ARR
API/Platform: 100 fintechs × €500/mo avg = €50K MRR = €0.6M ARR
Bank sync:    2,000 connected banks × €15/mo = €30K MRR = €0.36M ARR
────────────────────────────────────────────────────────────────
Total:        €5.16M ARR (rounds to €5M target)
```
*This assumes base-case net take on payments. Upside: own acquiring improves payment margin.*

### Why Macedonia First
Macedonia is the perfect beachhead:
- **Small market = fast iteration** (2M people, 70K SMBs, 15 banks)
- **Concentrated ecosystem** - 80% of SMBs use ~10 accounting offices
- **Platform operational** - invoicing platform running at app.facturino.mk, bank sandbox validated
- **Regulatory clarity** - NBRM has clear PSD2-style framework
- **No Plaid/Stripe-class incumbents** - fragmented local PSP/ERP landscape, no unified developer platform

Dominate MK → prove the playbook → expand to Serbia (7M) → rest of region.

### Why Now
1. **Regulatory tailwind:** E-invoicing mandates expanding across region (already live in several countries; more coming 2026-2027)
2. **PSD2 adoption:** Banks finally opening APIs
3. **Fragmented competition:** Global players don't localize for Balkans, local ERPs are desktop-only
4. **EU accession path:** Western Balkans harmonizing with EU standards

### Why Us
- **Local expertise:** Team from the region, understand the banks and regulations
- **First mover:** Building reusable adapter framework; each new bank takes 3-12 months depending on API maturity
- **Distribution:** Accountant network = 1 partner serves 20-50 SMBs

---

## Full Roadmap

### Executive Summary (Detailed)

Facturino is building **full-stack payment infrastructure** for Southeast and Central Europe. We are the "Plaid + Stripe" equivalent for a region with €3-5B TAM and fragmented infrastructure with no unified bank-data + payment orchestration layer.

---

## The Wedge: How We Win

### Starting Point (The Wedge)
```
┌─────────────────────────────────────────────────────────────────┐
│                    FACTURINO WEDGE STRATEGY                      │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  WEDGE PRODUCT: Invoicing + Bank Reconciliation for MK SMBs     │
│  ─────────────────────────────────────────────────────────────  │
│                                                                  │
│  Why this wedge works:                                          │
│  • 320K SMBs in Western Balkans (MK+RS+XK+AL) with no modern tools│
│  • Mandatory e-invoicing coming (2025-2026)                     │
│  • Pain point: manual bank reconciliation takes 2-4 hrs/week    │
│  • Distribution: accountants (1 accountant = 20-50 SMBs)        │
│                                                                  │
│  Expansion path:                                                 │
│  ─────────────────────────────────────────────────────────────  │
│                                                                  │
│  Invoicing → Bank Sync → Payment Links → Payouts → API Platform │
│      │           │            │             │           │        │
│      └───────────┴────────────┴─────────────┴───────────┘        │
│                         │                                        │
│              Each step = new revenue stream                      │
│              Each step = deeper lock-in                          │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### Go-to-Market: First 12 Months

| Month | Focus | Target | Channel | Milestone |
|-------|-------|--------|---------|-----------|
| 1-3 | Macedonia SMBs | 100 businesses | Direct + Accountant partners | Product-market fit signal |
| 4-6 | Accountant network | 20 accountant partners | Referral program (20% rev share) | 500 businesses via partners |
| 7-9 | Serbia soft launch | 50 businesses | Same playbook | First cross-border traction |
| 10-12 | API early access | 5 fintech customers | Developer outreach | Platform revenue starts |

### Customer Acquisition Funnel
```
Awareness (Content + Accountant referrals)
    │
    ▼ 10% conversion
Trial (Free 14-day, no CC required)
    │
    ▼ 25% conversion
Paid (€29/mo starter)
    │
    ▼ 40% expand to Growth
Expansion (€99/mo + payment fees)
    │
    ▼ 15% become API customers
Platform (Custom pricing)
```

### Moat: Why Competitors Can't Just Copy

"Can't someone else use the same PSD2 APIs?" - Yes, but:

1. **Normalization layer** - We build categories, counterparty matching, reference parsing across banks (not just raw data)
2. **Reliability engineering** - Failure handling, retry orchestration, token refresh, downtime alerts (banks break APIs constantly)
3. **Enrichment** - Merchant identification, invoice linking, payment purpose detection
4. **Reconciliation intelligence** - Feedback loop dataset improves matching over time
5. **Distribution lock-in** - Accountant partners embedded in their workflows, switching cost is training + migration

**The API is commodity. The reliability + enrichment + distribution is the moat.**

### Why We Win vs Alternatives

| Competitor Type | Examples | Their Weakness | Our Advantage |
|-----------------|----------|----------------|---------------|
| **Global Players** | Xero, QuickBooks | No Balkan localization, no local banks | Native MK/RS/AL support, local bank APIs |
| **Local ERPs** | Helix, Pantheon | Desktop-only, no API, ugly UX | Cloud-native, modern UX, API-first |
| **Bank Solutions** | NLB e-banking | Single bank, no cross-bank view | Multi-bank aggregation |
| **Regional PSPs** | CASYS, PaySera | Payment only, no invoicing | Full stack: invoice → pay → reconcile |
| **Open Banking Aggregators** | Plaid, Tink, Salt Edge | Not localized for WB SMB workflows | First with SMB-specific reconciliation |

### Competitive Map: Why CASYS is a Partner, Not a Competitor

**Common investor question:** "What about CASYS? They're the biggest PSP in Macedonia."

**Answer:** CASYS is our **payment infrastructure partner**, not a competitor. Here's why:

| Dimension | CASYS (PSP) | Facturino (Platform) |
|-----------|-------------|---------------------|
| **What they do** | Process card payments, generate payment links | Invoice-to-cash workflow: create invoice → send → collect → reconcile → report |
| **Customer relationship** | Merchant account holder | Business software subscriber |
| **Data model** | Transaction logs | Full business context: customers, invoices, bank accounts, reconciliation |
| **Value prop** | "Accept card payments" | "Save 4 hours/week on financial admin" |
| **Our integration** | We route payment links through CASYS | CASYS has no reason to build invoicing/reconciliation |

**Why CASYS won't compete with us:**
1. They're a payment processor, not a business software company
2. Building invoicing/accounting is outside their core competency
3. We're a distribution channel for them (more merchants = more volume)
4. We may eventually be their largest merchant in the SMB segment

**Why we use CASYS (and will continue to):**
1. They have the card acquiring relationships we'd take 18+ months to build
2. Local regulatory compliance is their problem, not ours
3. We focus on the workflow layer, they focus on payment rails
4. In Phase 3+, we add IPS/SEPA/alternatives for routing, but CASYS remains an option

**The right mental model:** CASYS is to Facturino as Stripe is to Shopify. Shopify uses Stripe (among others) to process payments, but Shopify's value is the commerce platform, not payment processing.

---

## Bank Integration Status Definitions

| Status | Meaning | Typical Timeline |
|--------|---------|------------------|
| 🔴 **Research** | API docs reviewed, no access yet | - |
| 🟡 **Sandbox** | Test environment access, OAuth working | 2-6 weeks |
| 🟢 **Pilot** | Production credentials, limited users | 4-12 weeks |
| ✅ **Production** | Stable tx sync + monitoring + retries | 8-20+ weeks |

*Timeline varies significantly by bank API maturity and access model. "Production" means stable transaction sync with error handling, not just OAuth working.*

---

## Current State (Q1 2026)

### What We Have
| Component | Status | Description |
|-----------|--------|-------------|
| Invoicing Core | ✅ Production | Full invoicing, customers, payments tracking |
| Macedonian Localization | ✅ Production | MK language, tax rules, chart of accounts |
| **Bank Statement Import** | ✅ Production | CSV/MT940 parsing for NLB, Stopanska, Komercijalna |
| **Reconciliation Engine** | ✅ Production | 4-signal matching (amount, date, ref, customer) with confidence scoring |
| **Reconciliation UI** | ✅ Production | Auto-match, manual-match, statistics dashboard |
| PSD2 Bank Connectivity | 🟡 Sandbox | OAuth working; tx sync in progress |
| CASYS Payment Gateway | 🟡 Beta | Local MK payment acceptance |
| E-Invoicing (UBL) | 🟡 Beta | UBL 2.1 invoice generation |
| Double-Entry Accounting | 🟡 Beta | IFRS-compliant ledger |
| Partner Portal | 🟡 Alpha | Affiliate/reseller system |

### Tech Stack
- **Backend:** Laravel 12, PHP 8.3
- **Frontend:** Vue 3, Vite, Tailwind CSS
- **Database:** MySQL 8 / PostgreSQL
- **Queue:** Redis + Laravel Horizon
- **Deployment:** Docker, Railway

---

## Phase 0: Ship Value Without PSD2 (Feb-Mar 2026) ← START HERE
**Goal:** Deliver reconciliation value NOW while PSD2 builds in parallel

### Why Phase 0 Exists
PSD2 bank integration is high-leverage but high-risk:
- Banks are slow, political, bureaucratic
- AISP licensing takes time
- If PSD2 slips 6-12 months, you have zero momentum

**Solution:** Ship reconciliation with manual imports first, add PSD2 as upgrade.

### Phase 0 Deliverables (2-4 weeks)

| Deliverable | Description | Status |
|-------------|-------------|--------|
| **CSV/MT940 bank import** | Upload bank statements, parse transactions | ✅ Done |
| **Email forwarding** | Forward bank notifications, auto-parse | 🔴 Plan |
| **Manual transaction entry** | Fallback for any bank | ✅ Exists |
| **Reconciliation engine v1** | Match invoices to imported transactions | ✅ Done |
| **Reconciliation UI** | Dashboard for matching and manual review | ✅ Done |
| **"Connected banks" pricing** | Charge for auto-sync when PSD2 ready | 🔴 Plan |

**✅ Completed Feb 5, 2026:**
- `Matcher` service with 4-signal confidence scoring (amount, date, reference, customer)
- Auto-matching at 85%+ confidence, suggestions at 60%+
- Payment auto-creation and invoice status updates
- Reconciliation UI at `/admin/banking/reconciliation`
- API endpoints for auto-match, manual-match, and statistics
- 23 passing tests covering all matching scenarios

### Phase 0 Architecture
```
┌─────────────────────────────────────────────────────────┐
│              RECONCILIATION ENGINE                       │
│         (same engine, multiple data sources)            │
├─────────────────────────────────────────────────────────┤
│                         │                               │
│    ┌────────────────────┼────────────────────┐         │
│    │                    │                    │         │
│    ▼                    ▼                    ▼         │
│ ┌──────────┐      ┌──────────┐      ┌──────────┐      │
│ │  CSV     │      │  Email   │      │  PSD2    │      │
│ │  Import  │      │  Parser  │      │  API     │      │
│ │  (NOW)   │      │  (NOW)   │      │ (LATER)  │      │
│ └──────────┘      └──────────┘      └──────────┘      │
│                                                         │
│  Week 1-2: Ship       Week 3-4: Add      Month 2+:     │
│  CSV import           email parsing      PSD2 upgrade  │
└─────────────────────────────────────────────────────────┘
```

### Phase 0 KPIs & Definition of Done

| Deliverable | Acceptance Criteria | Minimum | Target |
|-------------|---------------------|---------|--------|
| CSV import working | NLB, Stopanska, Komercijalna formats | 90% parse | 95% parse |
| Reconciliation v1 | Auto-match by amount + date + ref | 60% match | 70% match |
| Customer traction | Using import + reconciliation | 50 trials / 20 paid | 100 trials / 50 paid |
| Revenue | MRR from paid customers | €600 MRR | €1.5K MRR |
| PSD2 partner identified | AISP sponsor in discussions | LOI signed | Agreement signed |

**Phase 0 Exit Criteria:** Minimum thresholds met, customers paying for reconciliation, PSD2 track progressing.

*Minimum = we continue. Target = we're ahead of plan.*

---

## Phase 1: Foundation + PSD2 (Q1-Q2 2026)
**Goal:** Add PSD2 as upgrade to existing reconciliation product

### Phase 1 KPIs & Definition of Done

| Deliverable | Acceptance Criteria | KPI Target |
|-------------|---------------------|------------|
| PSD2: 1+ MK bank live | OAuth working, tx sync <5min delay | Platform 99.9%; bank sync 97%+ daily |
| PSD2: Partner/sponsor model | Legal AISP access without own license | Signed agreement |
| Reconciliation upgrade | PSD2 customers auto-sync vs import | 70-80% auto-match, <15% manual review |
| Payment links polished | CASYS integration, <3s redirect | 95% success rate |
| 500 customers | Mix of import + PSD2 users | €15K MRR (ARPA €30) |

**Phase 1 Exit Criteria:** PSD2 working for at least 1 bank, reconciliation is the hero product, ready for Serbia.

### Phase 1 Strategy: Parallel Tracks

| Track | Focus | Team |
|-------|-------|------|
| **Track A: Product** | Reconciliation UX, matching algorithms | Frontend + Product |
| **Track B: PSD2** | Bank integrations, partner negotiations | Backend + BD |
| **Track C: Growth** | Accountant partnerships, onboarding | Growth + Support |

**Key insight:** Don't block Track A waiting for Track B. Ship value with imports, upgrade to PSD2.

### 1.1 Complete Macedonia Infrastructure
| Task | Priority | Open Source to Leverage |
|------|----------|------------------------|
| Production PSD2 for 3 banks | P0 | - |
| CASYS payment links live | P0 | - |
| QES e-invoice signing | P1 | `robrichards/xmlseclibs` |
| Bank statement reconciliation | P1 | `jejik/mt940` |
| Multi-currency support | P1 | `brick/money` |

### 1.2 Core Platform Hardening
| Task | Priority | Description |
|------|----------|-------------|
| API versioning (v1) | P0 | Stable public API |
| Webhook system | P0 | Reliable event delivery |
| Rate limiting | P0 | Per-tenant throttling |
| Audit logging | P1 | Compliance-ready logs |
| Multi-tenancy improvements | P1 | Data isolation, per-tenant DBs |

### 1.3 Open Source Components to Integrate
| Package | Purpose | License |
|---------|---------|---------|
| `laravel/sanctum` | API authentication | MIT |
| `spatie/laravel-webhook-server` | Webhook delivery | MIT |
| `spatie/laravel-activitylog` | Audit trail | MIT |
| `stancl/tenancy` | Multi-tenancy | MIT |

---

## Phase 2: Payment Rails (Q3-Q4 2026)
**Goal:** Build proprietary payment infrastructure

### Phase 2 KPIs & Definition of Done

| Deliverable | Acceptance Criteria | KPI Target |
|-------------|---------------------|------------|
| 1,000 total customers | Across MK + Serbia | €30K MRR total |
| Serbia launch | 1 bank integrated, 50 customers | €5K MRR Serbia |
| Multi-PSP routing | CASYS + 1 alternative | 99% payment success |
| Reconciliation engine | Median auto-match 80-90% | 5-10% manual review* |
| Payment API v1 | Create, status, list endpoints live | <500ms p95 |

*Top-quartile customers (clean invoice references) may reach 95%+ auto-match. Global KPI reflects real-world messy data.*

**Phase 2 Exit Criteria:** Payment API in production, Serbia generating revenue.

### 2.1 Facturino Payment API
Build our own payment processing layer (not just gateway integrations).

```
┌─────────────────────────────────────────────────────────┐
│           FACTURINO PAYMENT ORCHESTRATION API            │
├─────────────────────────────────────────────────────────┤
│  /v1/payments/create     - Initiate payment (via PSP)   │
│  /v1/payments/{id}       - Get payment status           │
│  /v1/payments/link       - Generate payment link        │
│  /v1/bank/accounts       - List connected bank accounts │
│  /v1/bank/transactions   - Fetch transactions (via AISP)│
│                                                         │
│  DEFERRED (Phase 4+):                                   │
│  /v1/payouts/create      - Send money (needs PISP)      │
│  /v1/fx/*                - Currency exchange (2027+)    │
└─────────────────────────────────────────────────────────┘
```

### 2.2 Payment Methods by Country
| Country | Local Payment Methods | Integration Approach |
|---------|----------------------|---------------------|
| Macedonia | CASYS, bank transfer | Direct API |
| Serbia | IPS (Instant Payments Serbia), bank transfer | Partner bank |
| Kosovo | Bank transfer, cards | Partner bank |
| Albania | Bank transfer, cards | Partner bank |

### 2.3 Cross-Border Payments (Deferred to Phase 3+)

⚠️ **Not Phase 2 scope.** Cross-border payments require:
- PISP licensing or partnerships in multiple countries
- SEPA/SWIFT messaging infrastructure
- FX engine and correspondent banking relationships

**Revisit when:** Serbia is live + €5K MRR from international customers asking for cross-border.

### 2.4 Open Source to Leverage (Phase 2)
| Project | Purpose | License |
|---------|---------|---------|
| `moov-io/iso8583` | Card transaction messages | Apache 2.0 |

*Note: SEPA/SWIFT libraries (iso20022, rafiki) deferred to Phase 3+ with cross-border.*

---

## Phase 3: Open Banking Platform (Q1-Q2 2027)
**Goal:** Become the PSD2 aggregator for the region

### Phase 3 KPIs & Definition of Done

| Deliverable | Acceptance Criteria | KPI Target |
|-------------|---------------------|------------|
| 8 banks connected | MK(3) + Serbia(3) + Kosovo/Albania(2) | 99.5% uptime |
| Unified Bank API | Single API across all banks | <1s account fetch |
| Kosovo + Albania launch | Legal entity, 1 bank each | 25 customers each |
| First API customer | External fintech using our API | €2K MRR API revenue |
| Transaction volume | Total processed via platform | €5M/month |

**Phase 3 Exit Criteria:** Multi-country operations, first platform revenue.

### 3.1 Bank Integration Hub
```
┌─────────────────────────────────────────────────────────┐
│              FACTURINO BANKING HUB                       │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌─────────┐  ┌─────────┐  ┌─────────┐  ┌─────────┐    │
│  │   NLB   │  │Stopanska│  │Komerc.  │  │  OTP    │    │
│  └────┬────┘  └────┬────┘  └────┬────┘  └────┬────┘    │
│       │            │            │            │          │
│       └────────────┴─────┬──────┴────────────┘          │
│                          │                              │
│              ┌───────────▼───────────┐                  │
│              │   Unified Bank API    │                  │
│              │   - Account info      │                  │
│              │   - Transactions      │                  │
│              │   - Balance check     │                  │
│              │   (PISP: Phase 3+)    │                  │
│              └───────────┬───────────┘                  │
│                          │                              │
│              ┌───────────▼───────────┐                  │
│              │   Developer API       │                  │
│              │   (Plaid equivalent)  │                  │
│              └───────────────────────┘                  │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### 3.2 Bank Integration Targets
| Phase | Country | Banks | Population |
|-------|---------|-------|------------|
| 1 (NOW) | Macedonia | NLB, Stopanska, Komercijalna | 2M |
| 1 | Serbia | OTP, Raiffeisen, UniCredit | 7M |
| 1 | Kosovo | TEB, Raiffeisen, ProCredit | 2M |
| 1 | Albania | Raiffeisen, BKT, OTP | 3M |
| 2 | Bulgaria | UniCredit, DSK, UBB | 7M |
| 2 | Romania | BRD, BCR, ING | 19M |
| 2 | Croatia | Erste, PBZ, Raiffeisen | 4M |
| 2 | Slovenia | NLB, SKB, UniCredit | 2M |
| 2 | Greece | Alpha, Eurobank, NBG, Piraeus | 10M |
| 3 | Poland | PKO, Santander, mBank, ING | 38M |
| 3 | Czech | CSOB, Ceska, KB | 10M |
| 3 | Hungary | OTP, Erste, K&H | 10M |
| 3 | Slovakia | Tatra, SLSP, VUB | 5M |

### 3.3 Open Source Banking Components
| Project | Purpose | License |
|---------|---------|---------|
| `openbanking-toolkit/openbanking-aspsp` | PSD2 ASPSP reference | Apache 2.0 |
| `wso2/financial-open-banking` | Open banking platform | Apache 2.0 |

---

## Phase 4: E-Invoicing & Compliance (Q2-Q3 2027)
**Goal:** Mandatory e-invoicing compliance across region

### Phase 4 KPIs & Definition of Done

| Deliverable | Acceptance Criteria | KPI Target |
|-------------|---------------------|------------|
| 4 country e-invoice support | MK, Serbia, Slovenia, Croatia | 100% tax authority acceptance |
| Digital signature service | Multi-country QES certificates | <2s signing time |
| Document archive | 10-year compliant storage | 99.99% durability |
| SOC2 Type I | Audit completed | Certificate received |
| 2,000 customers | Across all markets | €80K MRR |

**Phase 4 Exit Criteria:** E-invoicing compliance leader in region, SOC2 certified.

### 4.1 E-Invoicing Standards by Country
| Country | Standard | Mandatory | Status |
|---------|----------|-----------|--------|
| Macedonia | UBL 2.1 | 2026 | 🟡 Building |
| Serbia | UBL 2.1 (SEF) | 2023 (live) | Planned |
| Slovenia | e-SLOG 2.0 | 2026 | Planned |
| Croatia | UBL 2.1 | 2024 (live) | Planned |
| Romania | RO e-Factura | 2024 (live) | Planned |
| Poland | KSeF | 2026-2027 | Planned |
| Italy | FatturaPA | 2019 (live) | Planned |

### 4.2 Open Source E-Invoicing
| Project | Purpose | License |
|---------|---------|---------|
| `num-num/ubl-invoice` | UBL 2.1 generation | MIT |
| `media24si/eslog2` | Slovenian e-SLOG | MIT |
| `fatturaelettronicaphp/FatturaElettronica` | Italian e-invoice | MIT |
| `itplr-kosern/xrechnung-visualization` | X-Rechnung (DE) | Apache 2.0 |
| `OpenPEPPOL/peppol-bis-invoice-3` | PEPPOL BIS 3.0 | MPL 2.0 |

### 4.3 Compliance Infrastructure
| Component | Description |
|-----------|-------------|
| Digital Signature Service | QES signing with country certificates |
| Document Archive | 10-year legally compliant storage |
| Audit Trail | Immutable transaction logs |
| Tax Authority Connectors | Real-time reporting to tax offices |

---

## Phase 5: Developer Platform (Q3-Q4 2027)
**Goal:** API-first platform for third-party developers

### Phase 5 KPIs & Definition of Done

| Deliverable | Acceptance Criteria | KPI Target |
|-------------|---------------------|------------|
| Developer portal | docs.facturino.com live | <5min to first API call |
| SDKs released | PHP, Node.js, Python | >100 GitHub stars |
| Sandbox environment | Full test mode, no real money | 99.9% uptime |
| 20 API customers | External developers/fintechs | €50K MRR from API |
| 15 banks connected | Across 5+ countries | 99.5% aggregate uptime |

**Phase 5 Exit Criteria:** Self-service developer onboarding, platform revenue >20% of total.

### 5.1 Developer Experience
| Feature | Description |
|---------|-------------|
| Developer Portal | docs.facturino.com |
| API Playground | Interactive API testing |
| SDKs | PHP, Node.js, Python, Java, Go |
| Webhooks | Real-time event notifications |
| Sandbox | Full test environment |

### 5.2 API Products
```
┌─────────────────────────────────────────────────────────┐
│                  FACTURINO API PRODUCTS                  │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │   CONNECT   │  │   PAYMENTS  │  │  INVOICING  │     │
│  │  (Banking)  │  │  (Rails)    │  │  (Billing)  │     │
│  └─────────────┘  └─────────────┘  └─────────────┘     │
│                                                          │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │   VERIFY    │  │   REPORTS   │  │   COMPLY    │     │
│  │  (KYC/AML)  │  │ (Analytics) │  │ (E-invoice) │     │
│  └─────────────┘  └─────────────┘  └─────────────┘     │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

### 5.3 Pricing Model

**Phase 0-1 Pricing (Launch):**
| Plan | Price | Includes |
|------|-------|----------|
| **Free** | €0/mo | 10 invoices/mo, manual reconciliation |
| **Starter** | €19/mo | Unlimited invoices, CSV import, basic matching |
| **Growth** | €49/mo | + Email parsing, advanced matching, reports |
| **Auto-Sync** | +€15/bank/mo | PSD2 connected bank (when available) |
| **Payment Links** | 1.9% + €0.20 (merchant fee) | CASYS-powered; Facturino net: 0.2-0.4% rev share |

**Future Pricing (Phase 3+):**
| Plan | Target | Pricing |
|------|--------|---------|
| Business | Mid-market | €199-499/mo + lower fees |
| Enterprise | Large companies | Custom |
| Platform/API | Fintechs | Revenue share |

---

## Phase 6: Expansion & Scale (2028)
**Goal:** 25 banks, 8 countries, €5M ARR

### Phase 6 KPIs & Definition of Done

| Deliverable | Acceptance Criteria | KPI Target |
|-------------|---------------------|------------|
| 25+ banks connected | Production integrations | 99.9% platform uptime |
| 8 countries live | Revenue-generating in each | €50K+ MRR per country |
| €5M ARR | Verified recurring revenue | 15% MoM growth |
| €300M+ TPV | Total payment volume/year | ~2K customers × €150K avg annual volume |
| Series A ready | Metrics, team, market position | Term sheets received |
| 100+ API customers | Platform ecosystem | >40% revenue from API |

**Phase 6 Exit Criteria:** Clear market leader in region, Series A fundraise.

### 6.1 Geographic Expansion Timeline
```
2026 Q1-Q2: Macedonia (3 banks) ████████████░░░░░░░░  ← NOW
2026 Q3-Q4: Serbia, Kosovo, Albania (+6 banks) ████████████████░░░░
2027 Q1-Q2: Bulgaria, Romania, Croatia (+8 banks) ████████████████████
2027 Q3-Q4: Slovenia, Greece (+5 banks)
2028 Q1-Q2: Poland, Czech, Hungary (+8 banks)
```

### 6.2 Key Metrics Targets
| Metric | 2026 | 2027 | 2028 |
|--------|------|------|------|
| Banks Connected | 5 | 15 | 25+ |
| Countries Live | 2 | 5 | 8+ |
| **SMB Customers** | 500 | 2,000 | 5,000 |
| **External API Customers (fintechs)** | 5 | 20 | 100 |
| Monthly API Calls | 100K | 5M | 50M |
| ARR | €200K | €1.5M | €5M |
| Transaction Volume | €10M | €100M | €300M+ |

---

## Technical Architecture

### System Architecture
```
┌─────────────────────────────────────────────────────────────────┐
│                         LOAD BALANCER                            │
└─────────────────────────────────┬───────────────────────────────┘
                                  │
┌─────────────────────────────────▼───────────────────────────────┐
│                         API GATEWAY                              │
│  - Rate limiting    - Auth    - Routing    - Logging            │
└─────────────────────────────────┬───────────────────────────────┘
                                  │
        ┌─────────────────────────┼─────────────────────────┐
        │                         │                         │
┌───────▼───────┐  ┌──────────────▼──────────────┐  ┌──────▼──────┐
│   CONNECT     │  │        PAYMENTS             │  │  INVOICING  │
│   SERVICE     │  │        SERVICE              │  │  SERVICE    │
│               │  │                             │  │             │
│ - Bank auth   │  │ - Payment initiation        │  │ - Create    │
│ - Account API │  │ - Status tracking           │  │ - Send      │
│ - Tx sync     │  │ - Reconciliation            │  │ - E-sign    │
└───────┬───────┘  └──────────────┬──────────────┘  └──────┬──────┘
        │                         │                         │
        └─────────────────────────┼─────────────────────────┘
                                  │
┌─────────────────────────────────▼───────────────────────────────┐
│                      MESSAGE QUEUE (Redis)                       │
└─────────────────────────────────┬───────────────────────────────┘
                                  │
        ┌─────────────────────────┼─────────────────────────┐
        │                         │                         │
┌───────▼───────┐  ┌──────────────▼──────────────┐  ┌──────▼──────┐
│  BANK         │  │     PAYMENT PROCESSOR       │  │  DOCUMENT   │
│  ADAPTERS     │  │     ADAPTERS                │  │  PROCESSOR  │
│               │  │                             │  │             │
│ - NLB         │  │ - CASYS                     │  │ - UBL gen   │
│ - Stopanska   │  │ - SEPA                      │  │ - PDF gen   │
│ - OTP         │  │ - SWIFT                     │  │ - QES sign  │
│ - Raiffeisen  │  │ - IPS (Serbia)              │  │ - Archive   │
└───────────────┘  └─────────────────────────────┘  └─────────────┘
```

### Data Architecture
```
┌─────────────────────────────────────────────────────────────────┐
│                     PRIMARY DATABASE (PostgreSQL)                │
│  - Tenants, Users, API Keys                                     │
│  - Accounts, Transactions (encrypted)                           │
│  - Invoices, Payments                                           │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                     TIME-SERIES DB (TimescaleDB)                 │
│  - API metrics, Latency tracking                                │
│  - Transaction analytics                                        │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                     DOCUMENT STORE (S3/MinIO)                    │
│  - Invoice PDFs, Bank statements                                │
│  - Signed documents, Audit logs                                 │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                     CACHE (Redis Cluster)                        │
│  - Session data, API responses                                  │
│  - Rate limit counters, Queue jobs                              │
└─────────────────────────────────────────────────────────────────┘
```

---

## Open Source Strategy

### Philosophy
We build on open-source foundations to move fast, then add proprietary value on top.

### Core Principles
1. **Use permissive licenses** (MIT, Apache 2.0, MPL) where possible
2. **Avoid copyleft licenses** (AGPL/GPL) in core services - AGPL triggers obligations on network use, not just distribution. If unavoidable, isolate behind service boundary and get legal review.
3. **Contribute back** - fixes and improvements to upstream
4. **Build proprietary moat** - bank integrations, compliance, network effects

### Key Open Source Projects to Leverage

**FORK THESE** (big building blocks we'll customize):
| Project | Purpose | License | Notes |
|---------|---------|---------|-------|
| **juspay/hyperswitch** | Payment orchestration | Apache 2.0 | ✅ Best choice for payment rails |
| **wso2/financial-services-accelerator** | Open banking patterns | Apache 2.0 | Reference + selective reuse |

**USE AS DEPENDENCIES** (don't fork, just integrate):
| Project | Purpose | License |
|---------|---------|---------|
| UBL/e-invoice libs | Invoice generation | MIT |
| jejik/mt940 | Bank statement parsing | MIT |
| brick/money | Money handling | MIT |
| keycloak | Identity management | Apache 2.0 |

**AVOID** (license or maintenance issues):
| Project | Issue | Alternative |
|---------|-------|-------------|
| OpenBankProject/OBP-API | AGPL license (copyleft risk) | WSO2 or custom |
| moov-io/paygate | Archived/unmaintained | Hyperswitch |

### Hyperswitch - Payment Infrastructure Fork (Phase 2+)
**Repository:** github.com/juspay/hyperswitch
**License:** Apache 2.0 ✅
**When to fork:** Phase 2 (Q3-Q4 2026) - when we need multi-PSP routing beyond CASYS

⚠️ **Do NOT touch Hyperswitch in Phase 0-1.** It's irrelevant to reconciliation wedge and will burn engineering focus.

**Fork triggers (any of these = start Hyperswitch work):**
- Expanding beyond Macedonia (need Serbian IPS, etc.)
- CASYS reliability issues requiring fallback PSP
- Volume justifies lower payment fees via routing

**When we fork, our customizations:**
- Add Balkan PSPs (CASYS, Serbian IPS, Albanian banks)
- Regional compliance rules
- Localized error handling

---

## Investment Allocation (€3M Raise)

### Use of Funds
| Category | Allocation | Amount | Purpose |
|----------|------------|--------|---------|
| Engineering | 50% | €1.5M | Core platform, bank integrations |
| Market Expansion | 30% | €900K | Country launches, partnerships |
| Operations | 20% | €600K | Legal, compliance, support |

### Engineering Breakdown
| Area | Amount | Hires |
|------|--------|-------|
| Backend (Payment Rails) | €600K | 3 senior engineers |
| Bank Integrations | €400K | 2 integration specialists |
| Frontend/DevEx | €250K | 2 frontend engineers |
| Infrastructure/DevOps | €150K | 1 SRE |
| QA/Security | €100K | 1 security engineer |

---

## Regulatory Path by Country

### Licensing Strategy: Partner First, License Later

We don't need our own license to start. Strategy:
1. **Phase 1 (2026):** Operate under partner/sponsor licenses
2. **Phase 2 (2026):** Apply for own EMI/PI license in one EU country
3. **Phase 3 (2027+):** Passport license across EU/EEA

### Country-by-Country Approach

| Country | Regulatory Body | Approach | Timeline | Cost Est. |
|---------|-----------------|----------|----------|-----------|
| **Macedonia** | NBRM | Partner with licensed EMI (shortlist in progress) | Immediate | €0 (rev share) |
| **Serbia** | NBS | Partner with local bank (shortlist in progress) | 3-6 months | €50K setup |
| **Kosovo** | CBK | Partner with licensed bank | 3-6 months | €30K setup |
| **Albania** | BoA | Partner with local bank | 3-6 months | €30K setup |
| **EU (Bulgaria/Romania/Croatia/Slovenia)** | Local + ECB | Own EMI license via Lithuania or Malta | 12-18 months | €150-300K |
| **Poland/Czech/Hungary** | Local regulators | Passport from EU EMI license | 1-3 months after EU | €10K per country |

### PSD2 Access Strategy

| Access Type | Description | When to Use |
|-------------|-------------|-------------|
| **AISP (Account Info)** | Read-only bank data | Start here - lower barrier |
| **PISP (Payment Initiation)** | Initiate payments | Phase 2 - requires more compliance |
| **Own License** | Full control | Phase 3 - when volume justifies |
| **Partner/Sponsor** | Operate under partner's license | Phase 1 - fastest to market |

### What We Can Do at Each Licensing Stage

| Stage | What's Allowed | What's NOT Allowed |
|-------|----------------|-------------------|
| **No AISP/partner (now)** | CSV import, email parsing, manual entry, invoicing, reconciliation | We do not access bank APIs directly |
| **With partner AISP** | Read-only bank data under partner's license and compliance program: account list, balances, transaction history | Payment initiation, money movement |
| **With partner PISP** | Above + initiate payments via partner's regulated service | Hold funds, issue cards |
| **Own EMI license** | Full control: payments, wallets, potentially card issuing | Lending, deposits (need banking license) |

**Phase 0-1 operates entirely in "No AISP" + "Partner AISP" - no regulatory blockers.**

### Compliance Team Structure

| Role | When to Hire | Responsibility |
|------|--------------|----------------|
| **Compliance Lead** | Now (contractor) | Regulatory strategy, partner negotiations |
| **AML Officer** | With EMI license | KYC/AML program, SAR filing |
| **DPO** | Now (part-time) | GDPR compliance, data mapping |
| **External Counsel** | Per country | Local regulatory filings |

---

## Security & Compliance Baseline

### Security Checklist (SOC2 / ISO 27001 Aligned)

| Category | Requirement | Status | Target |
|----------|-------------|--------|--------|
| **Encryption at Rest** | AES-256 for all PII/financial data | 🟡 Partial | Q2 2026 |
| **Encryption in Transit** | TLS 1.3 for all connections | ✅ Done | - |
| **Key Management** | HashiCorp Vault or AWS KMS | 🔴 Planned | Q2 2026 |
| **Tokenization** | Card/bank account tokenization | 🔴 Planned | Q3 2026 |
| **Access Control** | RBAC + MFA for all admin access | ✅ Done | - |
| **Audit Logging** | Immutable logs, 7-year retention | 🟡 Partial | Q2 2026 |
| **Monitoring** | SIEM, anomaly detection | 🔴 Planned | Q3 2026 |
| **Incident Response** | Documented IR plan, <4hr response | 🔴 Planned | Q2 2026 |
| **Pen Testing** | Annual third-party pentest | 🔴 Planned | Q3 2026 |
| **SAST/DAST** | Automated code scanning in CI | 🟡 Partial | Q2 2026 |
| **Vendor Risk** | Third-party security assessment | 🔴 Planned | Q3 2026 |

### Compliance Certifications Timeline

| Certification | Purpose | Target Date | Cost Est. |
|---------------|---------|-------------|-----------|
| **SOC2 Type I** | US customer requirement | Q4 2026 | €30-50K |
| **SOC2 Type II** | Full audit | Q2 2027 | €50-80K |
| **ISO 27001** | EU enterprise requirement | Q4 2027 | €40-60K |
| **PCI DSS** | If handling card data directly | Q1 2027 | €80-120K |

### Data Residency Strategy

| Region | Data Location | Approach |
|--------|---------------|----------|
| EU/EEA | Frankfurt (AWS eu-central-1) | Default for EU customers |
| Western Balkans | Frankfurt or local | Depends on regulations |
| Serbia | Local option available | Serbian data law compliance |

---

## Must-Win Priorities & Deferred Items

### MUST WIN (Next 12 Months)
These 3 things determine success or failure:

| Priority | Why Critical | Success Metric |
|----------|--------------|----------------|
| **1. Reconciliation product live** | Delivers value NOW (imports first, PSD2 upgrade later) | 70%+ auto-match, 50 customers in 4 weeks |
| **2. 500 paying customers** | Proves product-market fit | €15K MRR, <5% monthly churn |
| **3. At least 1 PSD2 bank live** | Proves moat is buildable | 99.9% uptime, partner model working |

**Critical insight:** Priority 1 does NOT depend on Priority 3. Ship reconciliation with imports first.

### EXPLICITLY DEFERRED (Not Now)
| Item | Why Deferred | When to Revisit |
|------|--------------|-----------------|
| Cross-border FX | Complex, needs volume first | 2027 Q3+ |
| Own EMI license | Expensive, partner approach works | When >€1M ARR |
| Mobile app | Web-first is fine for SMBs | 2027 Q2+ |
| AI features | Nice-to-have, not core | 2027+ |
| Western Europe | Different market dynamics | 2028+ |

### WHAT WE ARE NOT BUILDING (Scope Boundaries)
| We Are NOT | Why |
|------------|-----|
| A bank | No deposit-taking, no lending, no banking license needed |
| A core banking system | We connect to banks, we don't replace them |
| A wallet/stored value provider | Payments flow through, we don't hold funds |
| A lending platform | No credit risk, no collections |
| A crypto/blockchain company | Traditional payment rails only |

**We are infrastructure for bank data + payments, not a bank.**

### Dependency Map
```
                    ┌─────────────────┐
                    │ Bank OAuth Done │
                    └────────┬────────┘
                             │
              ┌──────────────┼──────────────┐
              │              │              │
              ▼              ▼              ▼
      ┌───────────┐  ┌───────────┐  ┌───────────┐
      │ Account   │  │Transaction│  │ Balance   │
      │ Info API  │  │ Sync API  │  │ Check API │
      └─────┬─────┘  └─────┬─────┘  └─────┬─────┘
            │              │              │
            └──────────────┼──────────────┘
                           │
                           ▼
                 ┌─────────────────┐
                 │ Reconciliation  │
                 │ Engine          │
                 └────────┬────────┘
                          │
           ┌──────────────┼──────────────┐
           │              │              │
           ▼              ▼              ▼
    ┌───────────┐  ┌───────────┐  ┌───────────┐
    │ Payment   │  │ Invoice   │  │ Cash Flow │
    │ Links     │  │ Matching  │  │ Forecast  │
    └─────┬─────┘  └───────────┘  └───────────┘
          │
          ▼
    ┌───────────┐
    │ Payouts   │ (Requires PI license/partner)
    └─────┬─────┘
          │
          ▼
    ┌───────────┐
    │ Cross-    │ (Requires FX partner)
    │ Border FX │
    └───────────┘
```

---

## Unit Economics & Pricing

### Revenue Model
| Revenue Stream | Merchant Pays | Facturino Net Take | % of Revenue (2027) |
|----------------|---------------|-------------------|---------------------|
| **SaaS Subscription** | €29-499/mo | €29-499/mo (100%) | 40% |
| **Payment Processing** | 1.5% + €0.20 (processor fee) | 0.2-0.6% (partner routing) | 35% |
| **Bank API Calls** | €0.10-0.50/call | €0.10-0.50/call (100%) | 15% |
| **FX Spread** | 0.5-1.0% (future) | 0.3-0.5% net (future) | 10% |

*Payment processing: We route to PSP (CASYS/etc), merchant pays processor fee, we earn rev share. Own acquiring (Phase 3+) improves net take.*

### Example Customer Economics

**Typical SMB Customer (Growth Plan)**
```
Monthly Profile:
- 50 invoices sent
- 30 payments received via payment links
- 200 bank transactions synced
- Average invoice: €500

Revenue per Customer (Year 1 vs Year 2+):
┌─────────────────────────────────────────────────────────┐
│                              Year 1      Year 2+        │
│                           (partner PSP) (own acquiring) │
├─────────────────────────────────────────────────────────┤
│ SaaS Fee (Growth Plan)      €99/mo       €99/mo        │
│ Payment Processing          €45/mo       €225/mo       │
│   (30 × €500 × 0.3% net)    (referral)   (1.5% own)    │
│ Bank API (200 × €0.10)      €20/mo       €20/mo        │
├─────────────────────────────────────────────────────────┤
│ Total Revenue               €164/mo      €344/mo       │
│ Gross Margin (~70%/60%)     €115/mo      €206/mo       │
└─────────────────────────────────────────────────────────┘
```
*Year 1: We route to CASYS/partner, take referral fee (0.2-0.4% net).*
*Year 2+: Improved net take rate via better routing, volume discounts, and direct PSP contracts (dependent on licenses/partners in each market).*

**Customer Acquisition Cost (CAC)**
| Channel | CAC | Payback Period |
|---------|-----|----------------|
| Accountant referral | €50 | 1 month |
| Content/SEO | €100 | 2 months |
| Paid ads | €200 | 4 months |
| Direct sales | €500 | 6 months |

**Unit Economics Scenarios**

| Scenario | Blended CAC | Monthly Churn | 24-mo LTV | LTV:CAC |
|----------|-------------|---------------|-----------|---------|
| **Bear** | €200 | 8% | €1,800 | 9:1 |
| **Base** | €150 | 5% | €3,200 | 21:1 |
| **Bull** | €100 | 3% | €5,500 | 55:1 |

*Early stage assumption: We target Base case. Bear case still viable (>3:1 threshold).*

### Platform/API Customer Economics

**Fintech API Customer**
```
Monthly Profile:
- 10,000 API calls (bank data)
- 500 payment initiations
- Average payment: €200

Revenue per Customer:
┌─────────────────────────────────────────────┐
│ API Calls (10K × €0.15)        €1,500/mo    │
│ Payment Init (500 × €0.50)     €250/mo      │
│ Platform fee                   €500/mo      │
├─────────────────────────────────────────────┤
│ Total Revenue                  €2,250/mo    │
│ Gross Margin (~65%)            €1,460/mo    │
└─────────────────────────────────────────────┘
```

---

## Risk Mitigation

### Technical Risks
| Risk | Mitigation |
|------|------------|
| Bank API changes | Abstract adapters, version pinning |
| Scale issues | Horizontal scaling, load testing |
| Security breach | SOC2 compliance, pen testing, encryption |

### Business Risks
| Risk | Mitigation |
|------|------------|
| Slow bank partnerships | Start with PSD2 (regulated access) |
| Regulatory changes | Modular compliance, local legal counsel |
| Competition | Speed to market, local expertise moat |

### Licensing Triggers (When We Need What)

| Activity | License Required | Our Approach |
|----------|------------------|--------------|
| Read-only bank data (AISP) | AISP via partner or own | Partner-first (Phase 1) |
| Initiate payments (PISP) | PISP via partner or own | Partner-first (Phase 2-3) |
| Hold funds / wallets | EMI/PI license | Only if business requires (Phase 4+) |
| Issue cards | EMI with card program | Not planned |

*We do NOT need our own EMI license in Phase 0-2. Partner model covers all planned activities.*

---

## Immediate Next Steps (Next 30 Days)

### 30-Day Outcome (by Mar 6, 2026)

**Engineering Acceptance Criteria:**
| Deliverable | Metric | Pass Threshold |
|-------------|--------|----------------|
| CSV import working | Parse accuracy on 50 real statements | ≥95% |
| Reconciliation v1 | Median auto-match on 10 pilot datasets | ≥60% |
| Time-to-reconcile | Pilot SMB monthly reconciliation time | <15 minutes |
| System reliability | Uptime during pilot period | >99% |

**GTM Acceptance Criteria:**
| Deliverable | Metric | Pass Threshold |
|-------------|--------|----------------|
| Accountant outreach | Intro meetings completed | ≥10 |
| Pilot offices | Offices actively using product | ≥2 |
| SMB trials | Trial accounts created | ≥20 |
| Paid conversions | Paying customers | ≥10 |
| PSD2 track | Partner discussions initiated | ≥1 LOI |

**If we hit these, we have product-market fit signal. If we miss, we learn why fast.**

**Evidence Plan (how we measure, not just what):**
| Metric | Source of Truth | Sampling Method |
|--------|-----------------|-----------------|
| Parse accuracy | `import_logs` table: success/fail per row | 100% of imports, auto-logged |
| Auto-match % | `reconciliations` table: auto vs manual | Per-statement, median across pilots |
| Time-to-reconcile | Session analytics: import → final approve timestamp | Median of 10 pilot datasets |
| Paid conversions | Stripe/Paddle dashboard | Exact count, weekly snapshot |
| Accountant meetings | CRM (Notion) + calendar | Manual log, verified by founder |

*All metrics auditable. Investors can request raw data export.*

---

### Week 1-2: Ship Reconciliation (No PSD2 Dependency)
- [x] CSV import for NLB, Stopanska, Komercijalna bank statements ✅
- [x] Reconciliation engine v1 (match by amount + date + reference + customer name) ✅
- [x] "Import transactions" UI in dashboard ✅
- [x] Reconciliation dashboard with confidence scoring ✅
- [ ] First 10 beta customers using imports

### Week 3-4: Polish + Monetize
- [ ] Email forwarding for bank notifications (parse automatically)
- [ ] Reconciliation improvements (rules + confidence scoring + manual review UI)
- [ ] Pricing tier: "Auto-sync" (for when PSD2 ready)
- [ ] 50 trials / 20 paid customers target

### Month 2: PSD2 Track (Parallel)
- [ ] AISP partner/sponsor agreement signed
- [ ] First bank OAuth flow working in sandbox
- [ ] Begin Serbia market research
- [ ] Accountant partnership program launch

### The Rule
**Never block product shipping waiting for PSD2.**
Import-based reconciliation ships NOW. PSD2 is an upgrade path.

---

## Success Metrics

### North Star Metric (by Phase)

| Phase | North Star | Why |
|-------|------------|-----|
| **Phase 0-1** | % of transactions auto-reconciled | Directly tied to wedge value prop |
| **Phase 2-3** | Hours saved per business/month | Willingness to pay metric |
| **Phase 4+** | Total Payment Volume (TPV) | When we control payment rails |

### Supporting Metrics
| Metric | Description |
|--------|-------------|
| Auto-match rate | % transactions reconciled without manual review |
| Time to reconcile | Minutes from bank sync to matched invoices |
| Banks Connected | Number of live bank integrations |
| API Customers | Businesses using our API |
| Net Revenue Retention | Customer expansion |

---

## Appendix: Open Source Safe List

### ✅ SAFE TO FORK (Apache 2.0 / MIT)

**Payment Infrastructure**
| Repo | Purpose | License | Status |
|------|---------|---------|--------|
| `juspay/hyperswitch` | Payment orchestration | Apache 2.0 | ✅ Phase 2+ fork target |
| `moov-io/iso8583` | Card messaging | Apache 2.0 | ✅ Phase 2 |
| `interledger/rafiki` | Cross-border payments | Apache 2.0 | ⏸️ Deferred to Phase 3+ |

**Banking & Open Banking**
| Repo | Purpose | License | Status |
|------|---------|---------|--------|
| `wso2/financial-services-accelerator` | FAPI/open banking | Apache 2.0 | ✅ Reference impl |

**E-Invoicing**
| Repo | Purpose | License | Status |
|------|---------|---------|--------|
| `num-num/ubl-invoice` | UBL generation | MIT | ✅ Use as dependency |
| `OpenPEPPOL/peppol-bis-invoice-3` | PEPPOL standard | MPL 2.0 | ✅ Reference |
| `media24si/eslog2` | Slovenian e-SLOG | MIT | ✅ Use as dependency |

**Security & Compliance**
| Repo | Purpose | License | Status |
|------|---------|---------|--------|
| `keycloak/keycloak` | Identity management | Apache 2.0 | ✅ Self-host |
| `hashicorp/vault` | Secrets management | MPL 2.0 | ✅ Self-host |

### ⚠️ AVOID (License or Maintenance Issues)

| Repo | Issue | Alternative |
|------|-------|-------------|
| `OpenBankProject/OBP-API` | AGPL (copyleft to network use) | WSO2 accelerator |
| `moov-io/paygate` | Archived, unmaintained | Hyperswitch |

### IP Hygiene Checklist
- [ ] Keep LICENSE + NOTICE files when forking Apache projects
- [ ] Maintain THIRD_PARTY_LICENSES.md in repo
- [ ] Don't use upstream trademarks in marketing
- [ ] Document all forks with version + date
- [ ] Regular license audit (quarterly)

---

## Appendix: TAM Assumptions

### Market Sizing Methodology

**Total Addressable Market: €500M–3B (range reflects take-rate assumptions)**

| Revenue Stream | Base Case (conservative) | Upside Case (own acquiring) |
|----------------|--------------------------|----------------------------|
| **SMB SaaS** | 320K WB SMBs × €200/yr = €64M | 1.75M region × €200/yr = €350M |
| **Payment Processing** | €50B vol × 0.3% net take = €150M | €200B vol × 1.2% take = €2.4B |
| **E-invoicing Compliance** | 320K SMBs × €50/yr = €16M | 1.75M × €50/yr = €88M |
| **Open Banking API** | 500 fintechs × €5K/yr = €2.5M | 5,000 × €10K/yr = €50M |
| **Cross-border FX** | Not in base case | €20B vol × 0.5% = €100M |
| **Total** | **~€230M** | **~€3B** |

*Base case = Western Balkans only, partner PSP routing (0.2-0.4% net after PSP fees).*
*Upside case = Full region, own acquiring license, direct bank relationships.*
*We plan against base case and celebrate upside.*

### Country-by-Country Data

**Western Balkans (Phase 1 focus):**
| Country | Population | SMBs (est.) | Banks | GDP/capita |
|---------|------------|-------------|-------|------------|
| Macedonia | 2.1M | 70K | 15 | €6,500 |
| Serbia | 6.9M | 150K | 25 | €8,800 |
| Kosovo | 1.8M | 40K | 10 | €4,400 |
| Albania | 2.8M | 60K | 12 | €6,200 |
| **WB Subtotal** | **13.6M** | **320K** | **62** | - |

**Southeast/Central Europe (Phase 2-3):**
| Bulgaria | 6.9M | 120K | 20 | €12,200 |
| Romania | 19.0M | 250K | 30 | €14,800 |
| Croatia | 3.9M | 80K | 20 | €17,400 |
| Slovenia | 2.1M | 50K | 15 | €28,000 |
| Greece | 10.4M | 180K | 15 | €20,200 |
| Poland | 37.7M | 400K | 35 | €17,800 |
| Czech Rep. | 10.5M | 150K | 25 | €26,800 |
| Hungary | 9.6M | 120K | 20 | €18,700 |
| Slovakia | 5.4M | 80K | 15 | €21,100 |
| **Total** | **119M** | **~1.75M** | **257** | - |

### Serviceable Addressable Market (SAM)

**Phase 1-2 (Western Balkans): €100-200M**
- 320K SMBs × €250/yr SaaS = €80M
- €30B payment volume × 0.3% net take = €90M (base)
- Upside: €30B × 1.2% = €360M (with own acquiring)

**Phase 3+ (Full Region): €500M–3B**
- Base: SaaS + partner PSP routing across 13 countries
- Upside: Own acquiring + API platform revenue

*Sources: World Bank, Eurostat, national statistical offices, industry reports.*

**Important:** SMB counts are directional estimates. We don't need 100% TAM accuracy—we need 2,000 customers in first 24 months. Our bottom-up SAM for Macedonia alone (70K SMBs × 3% penetration = 2,100) validates the target.

---

*This roadmap is a living document. Updated as we learn and grow.*
