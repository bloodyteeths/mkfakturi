# Facturino Connect: Complete Roadmap

## Vision Statement

Transform Facturino from a Macedonian accounting platform into the **financial infrastructure layer for the Western Balkans** — combining AI-powered accounting with open banking APIs ("Plaid for Balkans") and developer-friendly payments ("Stripe for Balkans").

---

## Roadmap Overview

```
2026                                    2027                           2028
Q1         Q2         Q3         Q4     Q1         Q2         Q3       Q1
│          │          │          │      │          │          │        │
├──────────┼──────────┼──────────┼──────┼──────────┼──────────┼────────┤
│ PHASE 1  │ PHASE 2  │ PHASE 3  │      │ PHASE 4  │ PHASE 5  │        │
│ API      │ Banks    │ Regional │ EIC  │ Scale    │ EU       │ Series │
│ Launch   │ Expand   │ Expand   │ App  │          │ Licenses │ A      │
└──────────┴──────────┴──────────┴──────┴──────────┴──────────┴────────┘
```

---

## Phase 1: Connect API Foundation (Q1 2026)

**Duration:** 12 weeks
**Goal:** Launch Facturino Connect API for third-party developers

### Milestone 1.1: OAuth2 Server (Weeks 1-3)
- [ ] Implement OAuth2 server for third-party apps
- [ ] Create ApiClient, AccessToken, UserConsent models
- [ ] Build API key generation system (fc_live_xxx format)
- [ ] Implement scope-based permissions

### Milestone 1.2: Public API Endpoints (Weeks 3-6)
- [ ] Account linking flow (Plaid Link equivalent)
- [ ] GET /accounts endpoint with normalization
- [ ] GET /transactions endpoint with filtering
- [ ] GET /institutions endpoint (supported banks)
- [ ] Webhook broadcasting system

### Milestone 1.3: Developer Experience (Weeks 6-10)
- [ ] Developer dashboard (Vue.js)
- [ ] API documentation portal
- [ ] Rate limiting per tier (Free/Starter/Growth/Enterprise)
- [ ] Usage tracking and analytics

### Milestone 1.4: SDKs & Launch (Weeks 10-12)
- [ ] Node.js SDK (npm package)
- [ ] Python SDK (pip package)
- [ ] PHP SDK (composer package)
- [ ] Beta launch with 10 pilot developers

**Deliverables:**
- Working Connect API with 3 banks
- Developer dashboard
- 3 SDKs published
- 10 beta API clients

---

## Phase 2: Bank Expansion - Macedonia Complete (Q2 2026)

**Duration:** 10 weeks
**Goal:** 80%+ coverage of Macedonian banking market

### Milestone 2.1: Additional MK Banks (Weeks 1-6)
- [ ] Sparkasse Banka integration
- [ ] Halkbank integration
- [ ] ProCredit Bank integration
- [ ] TTK Banka integration

### Milestone 2.2: Screen Scraping Fallback (Weeks 4-8)
- [ ] Build scraping infrastructure for banks without APIs
- [ ] Ohridska Banka (scraping)
- [ ] Silk Road Bank (scraping)

### Milestone 2.3: Bank Health Monitoring (Weeks 8-10)
- [ ] Real-time bank status dashboard
- [ ] Automated health checks
- [ ] Error alerting and recovery

**Deliverables:**
- 8-10 Macedonian banks connected
- 80%+ market coverage
- Health monitoring dashboard

---

## Phase 3: Regional Expansion (Q3 2026)

**Duration:** 12 weeks
**Goal:** Expand to Serbia, Kosovo, Albania

### Milestone 3.1: Serbia Entry (Weeks 1-6)
- [ ] UniCredit Serbia integration
- [ ] Raiffeisen Serbia integration
- [ ] Banca Intesa integration
- [ ] RSD currency support
- [ ] Serbian localization

### Milestone 3.2: Kosovo Entry (Weeks 4-8)
- [ ] Raiffeisen Kosovo integration
- [ ] ProCredit Kosovo integration
- [ ] EUR already supported (advantage)
- [ ] Albanian language support

### Milestone 3.3: Albania Pilot (Weeks 8-12)
- [ ] Raiffeisen Albania integration
- [ ] Intesa Sanpaolo Albania integration
- [ ] ALL currency support

### Milestone 3.4: Payment API (Parallel Track)
- [ ] Stripe-like Payment Intents API
- [ ] Checkout Sessions
- [ ] Multi-currency settlement
- [ ] Webhook events for payments

**Deliverables:**
- 15+ banks across 4 countries
- Payment API launched
- 50+ API clients
- 100K+ monthly transactions

---

## Phase 4: EIC Accelerator Application (Q4 2026)

**Duration:** 8 weeks
**Goal:** Submit compelling EIC application

### Milestone 4.1: Metrics & Traction (Weeks 1-4)
- [ ] Document usage metrics
- [ ] Customer testimonials
- [ ] Revenue data (if any)
- [ ] Technical architecture documentation

### Milestone 4.2: Application Writing (Weeks 4-8)
- [ ] Executive summary
- [ ] Technical innovation section (AI + Open Banking)
- [ ] Market analysis
- [ ] Financial projections
- [ ] Team profiles

### Milestone 4.3: AISP License Preparation
- [ ] Regulatory consultation
- [ ] Capital requirements analysis
- [ ] Compliance framework draft

**Deliverables:**
- Submitted EIC application
- AISP license roadmap
- Investor pitch deck

---

## Phase 5: Scale & Licensing (2027)

### Q1 2027: Post-EIC Scaling
- [ ] EIC funding received (target: EUR 2.5M grant + EUR 5M equity)
- [ ] Team expansion (10→25 people)
- [ ] AISP license application filed
- [ ] Montenegro and Bosnia expansion

### Q2-Q3 2027: EU Preparation
- [ ] AISP license obtained
- [ ] PISP license application
- [ ] Peppol network integration
- [ ] Bulgaria, Romania pilot

### Q4 2027 - Q1 2028: Series A
- [ ] 500K+ monthly API calls
- [ ] EUR 500K+ MRR
- [ ] Series A fundraise (EUR 15-20M)
- [ ] EU market entry preparation

---

## Resource Requirements

### Team (Current → Target)

| Role | Now | Phase 2 | Phase 5 |
|------|-----|---------|---------|
| Founder/CEO | 1 | 1 | 1 |
| Backend Engineers | 1 | 3 | 8 |
| Frontend Engineers | 0 | 1 | 3 |
| DevOps | 0 | 1 | 2 |
| Country Managers | 0 | 0 | 4 |
| Compliance/Legal | 0 | 1 | 2 |
| Sales/BD | 0 | 1 | 3 |
| **Total** | **2** | **8** | **23** |

### Budget (EUR)

| Phase | Duration | Cost |
|-------|----------|------|
| Phase 1 | 3 months | 50K |
| Phase 2 | 3 months | 80K |
| Phase 3 | 3 months | 120K |
| Phase 4 | 2 months | 30K |
| **Pre-EIC Total** | **11 months** | **280K** |
| Phase 5 (with EIC) | 12 months | 2.5M |

---

## Key Milestones Summary

| Date | Milestone | Success Metric |
|------|-----------|----------------|
| Mar 2026 | Connect API Beta | 10 developers |
| Jun 2026 | Macedonia Complete | 8 banks, 80% coverage |
| Sep 2026 | Regional Launch | 4 countries, 50 developers |
| Dec 2026 | EIC Application | Submitted |
| Mar 2027 | EIC Funding | EUR 2.5M+ received |
| Jun 2027 | AISP License | Approved |
| Dec 2027 | Series A Ready | EUR 500K MRR |

---

## Technical Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                    FACTURINO CONNECT                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────┐    ┌─────────────────┐    ┌─────────────┐  │
│  │ Third-Party     │    │ Developer       │    │ Facturino   │  │
│  │ Fintech Apps    │    │ Dashboard       │    │ Accounting  │  │
│  └────────┬────────┘    └────────┬────────┘    └──────┬──────┘  │
│           │                      │                     │         │
│           ▼                      ▼                     ▼         │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │              Connect API Layer (routes/connect.php)       │   │
│  │  - OAuth2 Server       - Rate Limiting                    │   │
│  │  - API Authentication  - Usage Tracking                   │   │
│  └──────────────────────────────────────────────────────────┘   │
│                              │                                   │
│  ┌───────────────────────────┼───────────────────────────────┐  │
│  │                    Service Layer                           │  │
│  │  ┌─────────────┐  ┌─────────────┐  ┌─────────────────┐    │  │
│  │  │ LinkService │  │ Normalizer  │  │ WebhookBroadcast│    │  │
│  │  └─────────────┘  └─────────────┘  └─────────────────┘    │  │
│  └───────────────────────────────────────────────────────────┘  │
│                              │                                   │
│  ┌───────────────────────────┼───────────────────────────────┐  │
│  │              Existing PSD2 Infrastructure                  │  │
│  │  ┌─────────┐  ┌──────────┐  ┌───────────┐  ┌──────────┐   │  │
│  │  │ NLB     │  │ Stopanska│  │ Komer     │  │ + More   │   │  │
│  │  │ Gateway │  │ Gateway  │  │ Gateway   │  │ Banks    │   │  │
│  │  └─────────┘  └──────────┘  └───────────┘  └──────────┘   │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Success Metrics

| Metric | MVP Target | 6-Month Target | 12-Month Target |
|--------|------------|----------------|-----------------|
| Banks Connected | 3 | 8 | 15 |
| API Clients | 10 | 50 | 100 |
| Monthly API Calls | 10K | 100K | 500K |
| Countries | 1 (MK) | 2 (MK, RS) | 4 (MK, RS, XK, AL) |
| Revenue | EUR 0 | EUR 10K MRR | EUR 50K MRR |

---

## Risk Mitigation

| Risk | Probability | Impact | Mitigation |
|------|-------------|--------|------------|
| Bank API access denied | Medium | High | Screen scraping fallback |
| Regulatory changes | Low | High | Flexible architecture |
| Competition from Plaid | Low | Medium | First-mover advantage |
| EIC rejection | Medium | High | Alternative funding sources |
| Technical debt | Medium | Medium | Code review, testing |

---

*Last updated: December 2025*
