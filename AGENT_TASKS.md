# Agent Tasks Overview

- Owner: PROJECT_MANAGER agent
- Purpose: Track concrete tasks for local multi-agent workflow.
- MAX_TOKENS_HINT: 400

## Agents

1. PROJECT_MANAGER
2. DEVELOPER
3. TESTER

## Workflow

1. PROJECT_MANAGER
   - Finalize requirements (this file and `REQUIREMENTS.md`).
   - Define tasks for DEVELOPER and TESTER.
   - Maintain `AGENT_PROJECT_MANAGER_ROADMAP.md`.
2. DEVELOPER
   - Implement orchestrator and roadmap updates.
   - Maintain `AGENT_DEVELOPER_ROADMAP.md`.
3. TESTER
   - Run sanity checks, verify logs.
   - Maintain `AGENT_TESTER_ROADMAP.md`.

## Task Table

| ID  | Title                                | Owner Agent    | Description                                                      | Status   | Notes |
|-----|--------------------------------------|----------------|------------------------------------------------------------------|----------|-------|
| T1  | Define local multi-agent requirements | PROJECT_MANAGER | Write `REQUIREMENTS.md` and update `AGENT_TASKS.md`.             | Done     |      |
| T2  | Align orchestrator with local-only use | DEVELOPER      | Ensure `orchestrator.py` is local-only and roadmaps are updated. | Pending  |      |
| T3  | Run orchestrator and verify outputs   | TESTER         | Run script, inspect roadmaps and `LOG.md`.                       | Pending  |      |
| T4  | Activate Bills/Suppliers backend      | DEVELOPER      | Implement AP controllers, requests, resources, routes, and tests | Done     | AP_AUTOMATION_PLAN Phase 1 completed |
| T5  | Verify Bills/Suppliers APIs           | TESTER         | Run sanity-check, new AP tests, and regression spot-checks      | Done     | Supplier/Bill feature tests executed; no regressions observed |
| T6  | Email→Bill Inbox Integration          | DEVELOPER      | Implement alias mapping, inbound mail webhook, jobs, and tests  | Done     | AP_AUTOMATION_PLAN Phase 2 completed |
| T7  | Verify Email Inbox & Alias Resolution | TESTER         | Validate alias resolution, email parsing, attachment filtering   | Done     | InboundMail tests executed; no regressions observed |
| T8  | Invoice Parsing Microservice Integration | DEVELOPER   | Implement invoice2data microservice and Laravel parser pipeline | Done     | AP_AUTOMATION_PLAN Phase 3 completed |
| T9  | Verify Parser Microservice + ParseInvoicePdfJob Integration | TESTER | Validate parser pipeline, bill creation, multi-tenant behavior | Done     | Parser job + alias→bill E2E tests passed; no regressions |
| T10 | QR Fiscal Receipt Scanner Backend     | DEVELOPER      | Implement QR decoding service, scanner controller, and tests    | Done     | AP_AUTOMATION_PLAN Phase 4 completed |
| T11 | Verify Receipt Scanner + QR Pipeline  | TESTER         | Validate receipt scans → Expense/Bill drafts, multi-tenant      | Done     | QR unit + feature tests passed; no regressions observed |
| T12 | Bulk CSV Import for Bills Backend     | DEVELOPER      | Implement Bills import presets, importer, controller, and tests | Done     | AP_AUTOMATION_PLAN Phase 5 completed |
| T13 | Verify Bulk Bills Import Pipeline     | TESTER         | Validate CSV/XLSX import → Bills/Suppliers, tenant isolation    | Done     | Bill import feature tests passed; no regressions observed |
| T14 | Accounts Payable UI Integration       | DEVELOPER      | Implement Suppliers/Bills/Inbox/Receipt Scanner frontend        | InProgress | Phase 6 AP UI pages and stores under implementation |

## Hand-off Conditions

- PROJECT_MANAGER → DEVELOPER:
  - `REQUIREMENTS.md` and `AGENT_TASKS.md` updated.
  - `AGENT_PROJECT_MANAGER_ROADMAP.md` created with at least one Done task.
- DEVELOPER → TESTER:
  - `orchestrator.py` updated and runnable via `python3`.
  - `AGENT_DEVELOPER_ROADMAP.md` updated with Self-Audit Report and “Handoff → TESTER”.
- TESTER → PROJECT_MANAGER:
  - `LOG.md` and roadmap files verified.
  - `AGENT_TESTER_ROADMAP.md` updated with Self-Audit Report and “Handoff → PROJECT_MANAGER”.
