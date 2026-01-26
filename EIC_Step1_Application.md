# EIC Accelerator - Step 1 Short Application

## Facturino: Financial Infrastructure for the Western Balkans

**Applicant:** Facturino DOOEL (to be registered January 2026)
**Location:** Skopje, North Macedonia
**Application Type:** EIC Accelerator Open

---

## SECTION 1: EXECUTIVE SUMMARY

*Maximum: 2,000 characters*

Facturino is building the financial infrastructure layer for the Western Balkans — the Plaid and Stripe that 18 million people need but don't have.

**The Problem:** The Western Balkans (North Macedonia, Serbia, Kosovo, Albania, Montenegro, Bosnia) have zero modern fintech infrastructure. There is no open banking aggregator. No developer-friendly payment API. Banks operate in silos. Fintechs cannot build. 710,000 SMEs waste billions of hours annually on manual financial processes.

**Our Solution:** Facturino provides:
1. **Open Banking API** — Unified access to bank accounts across 50+ Balkan banks
2. **Payment Infrastructure** — Developer-friendly payment initiation and processing
3. **AI-Powered Automation** — Intelligent transaction categorization and financial analysis

**Current Status:** We have live integrations with 3 Macedonian banks (NLB, Stopanska, Komercijalna) via PSD2. Our platform includes 10+ AI-powered financial tools, UBL 2.1 e-invoice support, and a production-ready developer API. Public launch: Q1 2026.

**Market Opportunity:** EUR 355M total addressable market. All 6 Balkan countries are EU candidates (accession 2028-2035). PSD2 adoption is imminent. No competitor exists — Plaid and Stripe do not serve this region.

**Funding Request:** EUR 17.5M total (EUR 2.5M grant + EUR 15M equity) to expand to 4 countries, connect 25+ banks, build full payment infrastructure, and achieve EUR 25M ARR within 3 years.

**Strategic Impact:** Facturino creates EU-controlled financial infrastructure for future EU citizens, prevents US/Chinese fintech dominance in the region, and accelerates digital transformation for EU accession.

---

## SECTION 2: COMPANY DESCRIPTION

### 2.1 Founding Story and Mission

Facturino was founded in 2024 in Skopje, North Macedonia to solve a problem the founder experienced firsthand: the complete absence of modern financial infrastructure in the Balkans.

While building software for local businesses, it became clear that basic financial operations — connecting bank accounts, automating payments, issuing invoices — required manual processes that cost businesses hours every week. The technology that Western companies take for granted (Plaid, Stripe, modern accounting software) simply doesn't exist in this region.

**Mission:** Build the financial infrastructure that enables the Western Balkans to participate in the modern digital economy.

**Vision:** Become the default financial platform for 18 million people and 710,000 businesses, creating the foundation for the region's fintech ecosystem.

### 2.2 Key Achievements

| Achievement | Status |
|-------------|--------|
| Platform development | Complete (12 months) |
| NLB Banka integration | LIVE (PSD2 OAuth + mTLS) |
| Stopanska Banka integration | LIVE (PSD2 OAuth) |
| Komercijalna Banka integration | LIVE (PSD2 OAuth) |
| AI financial tools | LIVE (10+ MCP tools) |
| E-invoice system | LIVE (UBL 2.1, QES) |
| Developer API | Ready for launch |

### 2.3 Legal Structure

- **Current:** US-based development company
- **January 2026:** Macedonian company registration (Facturino DOOEL)
- **Post-funding:** Potential holding structure for regional expansion

---

## SECTION 3: PROBLEM AND MARKET OPPORTUNITY

### 3.1 The Problem

The Western Balkans suffer from severe financial infrastructure fragmentation:

**For Businesses:**
- No way to connect bank accounts to software
- Manual transaction entry wastes 10+ hours/month per business
- Cross-border payments cost 3-5% and take 3-5 days
- No access to modern invoicing or payment collection tools

**For Developers/Fintechs:**
- Cannot access bank data (no Plaid equivalent)
- Cannot build payment apps (no Stripe equivalent)
- Must build everything from scratch for each country

**For the Region:**
- Financial inclusion lags Western Europe by 15+ years
- Fintechs cannot emerge without infrastructure
- SME productivity suffers from manual processes

### 3.2 Why This Problem Persists

| Player | Why They Won't Solve It |
|--------|------------------------|
| Plaid | Market too small (18M people vs 330M in US) |
| Stripe | Regulatory complexity across 6 countries |
| Local Banks | No competitive pressure, no incentive |
| Local Startups | Need infrastructure to exist first |

### 3.3 Market Size

| Market | Calculation | Value |
|--------|-------------|-------|
| TAM | 710K SMEs × EUR 500/year | EUR 355M |
| SAM | 400K digital-ready SMEs × EUR 400/year | EUR 160M |
| SOM (5-year) | 100K customers × EUR 400/year | EUR 40M |
| API Revenue | 30M monthly calls × EUR 0.10 | EUR 36M/year |

### 3.4 Market Timing

**Why Now:**

1. **EU Accession:** All 6 countries are EU candidates (2028-2035 timeline). They must modernize financial infrastructure.

2. **PSD2 Adoption:** EU candidate countries will adopt open banking regulations. Banks will be required to provide APIs.

3. **Post-COVID Digitalization:** Accelerated demand for digital financial services.

4. **No Competition:** The window is open. First mover will establish permanent dominance.

---

## SECTION 4: INNOVATION - SOLUTION/PRODUCT/SERVICES

### 4.1 Core Innovation

Facturino's innovation is creating a **unified financial data and payment layer** for a fragmented region where none exists.

**Technical Innovation:**

1. **Universal Bank Adapter:** Single integration point for 50+ banks across 6 countries with different APIs, protocols, and data formats.

2. **MCP (Model Context Protocol) Financial AI:** Novel implementation allowing AI agents to interact with financial data through standardized tools — the first such system for Balkan financial services.

3. **Multi-Provider AI Architecture:** Intelligent routing between Claude, Gemini, and OpenAI for optimal cost/performance across different financial tasks.

### 4.2 Product Components

**1. Facturino Connect (Plaid-like)**
```
POST /connect/v1/link          → Bank account linking
GET  /connect/v1/accounts      → Account aggregation
GET  /connect/v1/transactions  → Transaction history
POST /connect/v1/webhooks      → Real-time notifications
```

**2. Facturino Pay (Stripe-like)**
```
POST /pay/v1/intents           → Payment initiation
POST /pay/v1/transfers         → Bank transfers
POST /pay/v1/invoices          → Invoice payment links
```

**3. Facturino AI**
- Transaction categorization
- Invoice data extraction (OCR)
- Cash flow forecasting
- Anomaly detection
- Multi-language support (Macedonian, Albanian, Serbian, English)

### 4.3 Technology Readiness Level

| Component | TRL | Evidence |
|-----------|-----|----------|
| Bank integrations | TRL 7 | 3 banks live in production |
| AI/MCP system | TRL 7 | Operational with real data |
| E-invoice system | TRL 8 | UBL 2.1 compliant, QES signing |
| Developer API | TRL 6 | Complete, ready for beta |
| Payment initiation | TRL 4 | Designed, implementation Q2 2026 |

### 4.4 Intellectual Property

- **Trade secrets:** Bank integration protocols, AI prompt engineering
- **Proprietary data:** Macedonian merchant categorization database
- **First-mover advantage:** Bank relationships, regulatory knowledge

---

## SECTION 5: MARKET AND COMPETITION ANALYSIS

### 5.1 Target Market Segments

**Primary: SMEs (710,000 across 6 countries)**
- Need: Automated bookkeeping, bank connectivity, invoicing
- Willingness to pay: EUR 20-100/month

**Secondary: Fintechs/Developers**
- Need: Bank data API, payment infrastructure
- Willingness to pay: EUR 0.10-0.50 per API call

**Tertiary: Accountants (serving 10-200 SMEs each)**
- Need: Multi-client financial management
- Willingness to pay: EUR 50-200/month + per-client fees

### 5.2 Traction

| Metric | Status |
|--------|--------|
| Banks integrated | 3 (largest in Macedonia) |
| Platform status | Production-ready |
| AI tools | 10+ operational |
| Launch date | Q1 2026 |

### 5.3 Competitor Analysis

| Competitor | Presence in Balkans | Our Advantage |
|------------|---------------------|---------------|
| Plaid | None | We're here, they're not |
| Stripe | None | We're here, they're not |
| Tink | None | We're here, they're not |
| QuickBooks | Minimal | Local language, local banks |
| Xero | None | Local compliance, local banks |
| Local accounting tools | Yes | No AI, no APIs, no bank feeds |

**Conclusion:** There is no direct competitor. We are creating the category.

### 5.4 Barriers to Entry

1. **Bank relationships:** 6-12 months per bank integration
2. **Regulatory knowledge:** 6 countries, 6 frameworks, 4 languages
3. **Local expertise:** Tax law, business culture, language
4. **Network effects:** More businesses → more developers → more businesses

---

## SECTION 6: BROADER IMPACTS

### 6.1 EU Strategic Alignment

**European Digital Strategy:**
- Creates digital infrastructure for EU candidate countries
- Enables fintech innovation in underserved region
- Accelerates digital transformation for EU accession

**EU Enlargement Policy:**
- Reduces financial infrastructure gap with current EU members
- Prepares candidate countries for PSD2/PSD3 adoption
- Creates EU-controlled alternative to US/Chinese fintech platforms

### 6.2 UN Sustainable Development Goals

| SDG | Contribution |
|-----|--------------|
| SDG 8: Decent Work | Enables SME productivity, job creation |
| SDG 9: Industry & Innovation | Creates fintech infrastructure |
| SDG 10: Reduced Inequalities | Financial inclusion for underserved region |
| SDG 17: Partnerships | Cross-border financial cooperation |

### 6.3 Economic Impact

**Job Creation:**
- Direct: 50+ jobs by Year 3
- Indirect: Enables fintech ecosystem (100+ companies building on platform)

**SME Productivity:**
- Saves 10+ hours/month per business in manual financial work
- 710,000 SMEs × 10 hours × EUR 15/hour = EUR 1.3B annual productivity gain potential

---

## SECTION 7: TEAM AND MANAGEMENT

### 7.1 Founder

**Technical Founder**
- Built entire platform in 12 months as solo developer
- Full-stack expertise: Laravel, Vue.js, PostgreSQL, AI/ML
- Deep knowledge of Macedonian business regulations and tax law
- Integrated 3 banks using PSD2/mTLS authentication
- Implemented multi-provider AI system (Claude, Gemini, OpenAI)

### 7.2 Post-Funding Team Plan

| Role | Timeline | Responsibility |
|------|----------|----------------|
| CTO | Month 1-2 | Technical leadership, architecture |
| Head of Compliance | Month 2-3 | AISP/PISP licensing, regulatory |
| Senior Engineers (4) | Month 2-6 | Platform development |
| Country Manager Serbia | Month 6 | Serbian market entry |
| Country Manager Kosovo/Albania | Month 9 | Regional expansion |
| Sales/BD (2) | Month 3-6 | Customer acquisition |

### 7.3 Advisory Network

To be established post-funding:
- Fintech regulatory expert (EU)
- Banking industry veteran (Balkans)
- Scaling advisor (former Plaid/Stripe executive)

---

## SECTION 8: FUNDING REQUEST

### 8.1 Total Request

**EUR 17,500,000**
- Grant component: EUR 2,500,000
- Equity component: EUR 15,000,000

### 8.2 Grant Allocation (EUR 2.5M)

| Category | Amount | Purpose |
|----------|--------|---------|
| Bank Integration R&D | EUR 800K | 25+ banks, universal adapter |
| Security & Compliance | EUR 500K | AISP/PISP licensing, SOC2 |
| AI/ML Development | EUR 400K | Multi-language financial models |
| API Infrastructure | EUR 400K | Scale to 1M+ calls/day |
| Developer Tools | EUR 400K | SDKs, documentation, sandbox |

### 8.3 Equity Allocation (EUR 15M)

| Category | Amount | Purpose |
|----------|--------|---------|
| Team Expansion | EUR 4M | 30+ hires across functions |
| Market Entry | EUR 4M | 4-country expansion |
| Payment Infrastructure | EUR 3M | Full Stripe-like capabilities |
| Bank Partnerships | EUR 2M | Integration fees, deals |
| Working Capital | EUR 2M | 24-month runway buffer |

### 8.4 Why EIC Funding?

1. **Risk Capital:** Banks and traditional VCs don't fund infrastructure in emerging markets
2. **Patient Capital:** Infrastructure requires 3-5 years to achieve network effects
3. **Strategic Alignment:** EU interest in controlling financial infrastructure for candidate countries
4. **Signal Value:** EIC approval validates project for banks, partners, future investors

### 8.5 Financial Projections

| Year | Businesses | Banks | ARR | Team |
|------|------------|-------|-----|------|
| 2026 | 5,000 | 8 | EUR 500K | 15 |
| 2027 | 30,000 | 20 | EUR 8M | 25 |
| 2028 | 100,000 | 35 | EUR 25M | 40 |
| 2029 | 200,000 | 50 | EUR 50M | 60 |
| 2030 | 350,000 | 60+ | EUR 100M | 80 |

### 8.6 Key Risks and Mitigations

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Bank refuses integration | Medium | High | Multiple bank relationships, screen scraping fallback |
| Regulatory delay | Medium | Medium | Early engagement with regulators, legal counsel |
| Slow SME adoption | Medium | Medium | Accountant channel partnerships, freemium model |
| Competition emerges | Low | High | First-mover advantage, network effects, speed |
| Technical scaling issues | Low | Medium | Cloud infrastructure, proven architecture |

---

## SECTION 9: MILESTONES

### 9.1 Post-Funding Milestones

| Month | Milestone | Success Metric |
|-------|-----------|----------------|
| 3 | Team scaled | 15 people |
| 3 | API public launch | 100 developers |
| 6 | Macedonia dominated | 10,000 businesses, 10 banks |
| 9 | Serbia market entry | 5 banks, 5,000 businesses |
| 12 | AISP/PISP licenses | Regulatory approval |
| 18 | Payment API launched | EUR 50M/month processed |
| 24 | 4 countries operational | 50,000 businesses |
| 36 | Regional dominance | EUR 25M ARR |

---

## SECTION 10: CONCLUSION

Facturino represents a unique opportunity to create fundamental financial infrastructure for an underserved region of 18 million people.

**What makes this compelling:**

1. **Proven technology:** 3 banks live, AI working, platform ready
2. **Zero competition:** No Plaid, no Stripe, no alternatives
3. **Perfect timing:** EU accession, PSD2 adoption, market window open
4. **Clear path to scale:** Trojan horse strategy, network effects
5. **Strategic EU value:** Infrastructure for future EU citizens

**The ask is clear:** EUR 17.5M to build the financial infrastructure that the Western Balkans desperately needs — before anyone else does.

**The return is compelling:** First-mover in a winner-take-all market, path to EUR 1B+ valuation, strategic asset for EU.

---

*Facturino | Skopje, North Macedonia | facturino.mk*

*"The Balkans don't need another app. They need infrastructure. We're building it."*
