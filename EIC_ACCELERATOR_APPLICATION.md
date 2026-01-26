# EIC Accelerator Application (draft)

Facturino: AI-powered financial infrastructure for emerging Europe

Applicant: MK Accounting DOOEL (Facturino)
Location: Skopje, North Macedonia
Founded: 2024
Stage: pre-revenue (pilot/beta)
Funding requested: €2,500,000 grant + €5,000,000 equity (blended finance)
Project duration: 24 months

---

## Executive summary

Facturino is building modern financial infrastructure for the Western Balkans: accounting automation for SMEs and accounting offices, bank connectivity through a unified API, and (later) payment/collections rails that link invoices to settlement and ledger events.

The problem is structural. The region is fragmented across six jurisdictions and dozens of banks, with uneven digital maturity and limited pressure on banks to provide reliable, developer-grade APIs. SMEs still rely heavily on paper, Excel, and manual bookkeeping. As a result, (1) SME finance stays manual and error-prone, (2) fintechs struggle to build because data access is inconsistent, and (3) cross‑border trade is slowed by payment friction.

Our approach combines three layers into one platform with one ledger and audit trail:

1) Accounting automation: document capture, reconciliation, categorisation proposals, VAT/DDV logic support, multi‑language workflows.

2) Facturino Connect: bank connectivity and normalized transaction data under a consistent schema, with consent/token handling.

3) Payments/collections (planned): local-first invoice collection and payment events mapped to invoices and accounting entries.

Current status and evidence (pre‑revenue):
- product is built and running in beta; we are onboarding pilot users (no paying customers yet)
- bank connectivity connectors exist for several Macedonian banks (details and proof to be provided as annexes/screenshots)
- e‑invoice capability exists for UBL generation and QES signing; submission integration depends on the availability of official tax authority endpoints and is planned for the project period
- AI features are implemented as assisted workflows (propose + explain + log), not autonomous ledger changes

What we will deliver with EIC support (24 months):
- expand bank coverage and reliability, plus a public Connect API (sandbox, docs, webhooks)
- launch payments/collections for invoice‑to‑cash in supported markets
- security/compliance hardening for cross‑border operations
- validated commercial rollout in North Macedonia and Serbia, with an expansion path to Kosovo and Albania

Why EIC: this is a cross‑border infrastructure play with real integration and regulatory risk. The upside is large, but the path requires patient capital. EIC blended finance fits the risk profile and the scale ambition.

---

## Part 1 — Business case

### 1. Company description

Facturino is developed and operated by MK Accounting DOOEL in Skopje, North Macedonia.

What we do today:
- deliver an accounting and invoicing platform designed for SMEs and accounting offices
- build bank connectivity and normalization into a single internal ledger

What we will commercialize:
- subscription plans for SMEs and accounting offices
- usage-based pricing for the Connect API (for fintechs and platforms)
- transaction-based fees for collections/payments (where legally and operationally feasible)

Team (to be completed with names/CVs in the final package):
- founder/CEO: product + market execution
- lead engineer: platform engineering
- planned hires: compliance lead, senior engineers, BD/sales, country manager(s)

### 2. Problem and opportunity (Excellence)

#### 2.1 Fragmented financial infrastructure

Across the Balkans:
- banking is fragmented (dozens of banks, uneven maturity)
- data access is inconsistent (different standards, patchy APIs)
- cross‑border payments are slow and expensive for SMEs
- accounting remains manual

The result:
- SMEs lose time and visibility
- accountants spend time on low‑value data entry
- fintechs face high integration cost per bank and per country

#### 2.2 SME pain points

Pain points we target first:
- manual bookkeeping and reconciliation
- missing or unreliable bank feeds
- document capture (receipts/invoices) into structured data
- VAT/DDV complexity for everyday operations

#### 2.3 Why now

Key tailwinds:
- EU accession path and gradual regulatory alignment
- increasing SME adoption of cloud tools post‑COVID
- rising expectations from SMEs that finance should be real‑time (bank feeds, instant insights)

### 3. The innovation: solution/product/service (Excellence)

#### 3.1 What is new beyond “AI in accounting”

Facturino’s innovation is the platform architecture and the normalization layer, not a single model.

1) Normalized bank data under a stable contract
- we transform diverse bank transaction formats into a consistent schema that software can depend on
- we treat normalization as an engineering product: monitoring, parsing fallbacks, and reconciliation integrity

2) Assisted automation with auditability
- AI proposes and explains categorizations and document extractions
- user actions (accept/override) are logged so accountants can trust the process

3) One ledger connecting documents, bank transactions, invoices, and (later) payments
- the goal is to remove double entry and reconciliation gaps by design

#### 3.2 Current product scope (beta)

Accounting automation:
- categorisation proposals
- reconciliation workflows
- multi-language UI (Macedonian/Albanian/Serbian/English)

Document intelligence:
- receipt/invoice capture into structured fields
- confidence scoring and manual review

Bank connectivity (current):
- connectors for selected Macedonian banks (consent/token handling, transaction sync)
- MT940 parsing as a fallback where APIs are not available

E‑invoice readiness:
- UBL generation and QES signing implemented
- tax authority submission integration planned when official interfaces are available

#### 3.3 Technical maturity (TRL)

We enter the project at pilot/beta maturity (targeting TRL 6+). The EIC project will fund activities to reach a robust, scalable product (TRL 8) and prepare for rollout (TRL 9 activities mainly supported by equity).

Evidence to include in annexes (final submission):
- live environment screenshots
- integration logs / test certificates (where applicable)
- security controls overview

### 4. Market analysis and competition (Impact)

#### 4.1 Target market and expansion logic

Primary target users:
- SMEs needing invoicing + bookkeeping + bank reconciliation
- accounting offices managing multiple companies
- fintechs/platforms requiring bank data access via a stable API

Geographic rollout:
- North Macedonia (base market)
- Serbia (next, larger SME base)
- Kosovo + Albania (follow-on, strong cross-border SME activity)

#### 4.2 Competition

- global accounting tools: strong features, limited localization and weak regional bank coverage
- local tools: local compliance knowledge, but limited automation and no developer platform
- banks: trusted, but rarely build neutral cross‑bank aggregation

Facturino’s advantage:
- local compliance + language built‑in
- bank normalization + API platform
- integration moat and partner distribution via accounting offices

### 5. Marketing and sales plan (Impact)

Commercialization strategy (pre‑revenue → revenue):

1) Accounting offices as a channel
- partner plan: offices onboard their client companies
- incentives: discounted internal use + revenue share per activated client

2) SME direct acquisition
- simple onboarding: invoices + bank sync + reconciliation
- pricing tiers aligned with company size and accountant involvement

3) Developer adoption for Connect API
- publish sandbox, docs, webhooks
- target platforms that already serve SMEs (ERP, invoicing, expense tools)

Validation plan (what we will measure):
- activation: time to first invoice/bank sync
- retention: month‑over‑month active usage
- automation value: reconciliation time reduction; % transactions auto‑categorized with approval
- revenue: conversion from beta to paid plans

### 6. Team and management (Implementation)

We will finalize a hiring plan that matches the work packages:
- engineering: integrations, data normalization, reliability
- security/compliance: policies, audits, licensing pathway
- BD/sales: accounting office channel + Serbia entry

---

## Part 2 — Implementation (Level of risk, implementation, and need for Union support)

### 7. Work plan and milestones (24 months)

Work package WP1 — Facturino Connect expansion (bank coverage + reliability)
- standardize connector framework, monitoring, and data normalization
- add additional banks (prioritize market coverage)
- deliver public API: auth, rate limits, webhooks, sandbox, documentation

Work package WP2 — Accounting automation hardening
- improve categorization + reconciliation workflows
- document capture QA: accuracy, confidence scoring, review flows
- accountant-grade audit logs and exportability

Work package WP3 — Payments/collections (where feasible)
- invoice-to-cash flows tied to invoices and ledger events
- local payment method support (subject to compliance and partnerships)
- dispute/refund and reconciliation processes

Work package WP4 — Security and compliance readiness
- GDPR-aligned data handling and retention
- penetration testing, incident response process
- licensing roadmap (AISP/PISP pathway per target market)

Work package WP5 — Commercial rollout
- partner onboarding playbook for accounting offices
- Serbia entry: localization, early design partners, first integrations

Milestones (to be finalized with KPIs that we can evidence):
- M1 (month 3): Connect API beta + sandbox + first external pilots
- M2 (month 6): expanded bank coverage in North Macedonia + reliability metrics
- M3 (month 9): Serbia market entry with pilot partners
- M4 (month 12): security/compliance audit milestone and licensing pathway confirmed
- M5 (month 18): API adoption milestone (connected accounts / monthly calls)
- M6 (month 24): paid rollout readiness (pricing, conversion, retention, unit economics)

### 8. Risks and mitigation

Key risks:
- bank integration timelines and changing specs
- regulatory and licensing complexity by country
- trust barrier: finance requires strong security posture
- conversion risk from beta to paid

Mitigation:
- prioritize banks by coverage and integration feasibility
- build connector framework for reuse + monitoring to reduce breakage
- design “assisted automation” with auditability to build accountant trust
- run structured pilots with measurable success criteria and conversion plan

### 9. Use of funds

Grant (€2.5m): innovation activities (engineering, integrations, security/compliance work, validation)
- engineering and infrastructure
- bank integrations and normalization
- security/compliance hardening
- pilot validation and documentation

Equity (€5.0m): scale-up and rollout
- Serbia expansion and cross-border operations
- go-to-market and partner channel growth
- reserves for long integration cycles and regulatory work

### 10. Ethics, data protection, and security

- privacy by design: minimize data collection, strict access controls, audit logs
- encryption in transit and at rest for tokens and sensitive fields
- role-based access for accountants vs SMEs
- documented incident response and security monitoring

---

## Appendix — Technical overview (supporting information)

Platform overview:
- backend: Laravel 11 (PHP 8.3)
- frontend: Vue 3
- database: PostgreSQL
- cache: Redis

AI system approach:
- multi-provider architecture for routing tasks
- assisted workflows (propose + explain + log)

Key modules (internal):
- bank connectors and normalization
- document capture and extraction
- reporting layer (trial balance, P&L, cash flow)

Key file locations (update to match repo reality):
- MCP client: app/Services/McpClient.php
- AI providers: app/Services/AiProvider/
- bank gateways: Modules/Mk/Services/
- AI config: config/ai.php
- MCP routes: routes/mcp.php

---

Contact

Facturino / MK Accounting DOOEL
Skopje, North Macedonia
Website: https://facturino.mk

Last updated: 2025-12-26
