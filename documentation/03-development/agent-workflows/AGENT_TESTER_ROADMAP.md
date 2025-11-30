# AGENT_TESTER_ROADMAP

- Agent: TESTER
- Goal: Validate orchestrator changes, logs, sanity checks, and new Accounts Payable backend.

## Tasks

- Task: Run orchestrator and verify outputs  
  - Subtasks:
    - Run `python3 orchestrator.py` locally.
    - Inspect updated roadmaps and LOG.md for correctness.
  - Status: Pending
  - LastUpdate: 2025-11-15T10:40:00Z
  - Notes: Waits on DEVELOPER handoff for orchestrator changes.

- Task: Verify Bills/Suppliers APIs (AP Automation Phase 1)  
  - Subtasks:
    - Run new `tests/Feature/Admin/SupplierBillTest.php`.  
    - Hit `/api/v1/suppliers` and `/api/v1/bills` endpoints with multiple tenants.  
    - Confirm multi-tenant isolation (no cross-company records).  
    - Validate PDF download endpoint for bills responds without errors.  
    - Run `tools/sanity-check.sh` and a targeted PHPUnit subset for existing modules (Invoices, Expenses) to ensure no regressions.  
  - Status: Completed
  - LastUpdate: 2025-11-15T12:00:00Z
  - Notes: SupplierBillTest executed successfully; multi-tenant headers respected; no regressions detected in targeted test run.

- Task: Verify Email Inbox & Alias Resolution (AP Automation Phase 2)  
  - Subtasks:
    - Run `tests/Feature/Webhooks/InboundMailTest.php`.  
    - Confirm alias → company mapping works and unknown aliases are ignored safely.  
    - Validate that only PDF attachments are accepted and queued, others skipped.  
    - Ensure `ProcessInboundBillEmail` is dispatched correctly with companyId and attachment metadata.  
  - Status: Completed
  - LastUpdate: 2025-11-15T12:25:00Z
  - Notes: Validated alias resolution, inbound email parsing, attachment filtering, and job dispatching; no regressions observed.

- Task: Verify Parser Microservice + ParseInvoicePdfJob Integration (AP Automation Phase 3)  
  - Subtasks:
    - Run `tests/Feature/Admin/ParseInvoicePdfJobTest.php`.  
    - Use `Http::fake` to simulate invoice2data responses and assert Bill/Supplier/BillItems are created correctly.  
    - Verify multi-tenant isolation when going from alias → job → Bill draft.  
    - Confirm no regressions in Invoice/Expense/IFRS/Bank-related tests in targeted runs.  
  - Status: Completed
  - LastUpdate: 2025-11-15T12:55:00Z
  - Notes: Parser pipeline validated with Http::fake; supplier creation and bill/item creation correct; multi-tenant logic verified; no regressions detected in targeted suites.

- Task: Verify Receipt Scanner + QR Pipeline (AP Automation Phase 4)  
  - Subtasks:
    - Run `tests/Unit/FiscalReceiptQrServiceTest.php` for QR parsing.  
    - Run `tests/Feature/Admin/ReceiptScannerTest.php` for image/PDF scans.  
    - Confirm expenses/bills are created correctly from QR payloads with proper company/creator scoping.  
    - Ensure no regressions in Invoice/Expense/IFRS/Bank tests in targeted runs.  
  - Status: Completed
  - LastUpdate: 2025-11-15T13:20:00Z
  - Notes: FiscalReceiptQrService parsing tests, receipt scanner feature tests, expense/bill generation, and multi-tenant isolation validated; no regressions observed.

## Self-Audit Report

- TOKEN_ESTIMATE: 600
- Lessons_for_next_run:
  - For new document types, always test both CRUD and permission boundaries.
  - Include at least one cross-tenant isolation test per new API group.
  - Reuse existing feature-test patterns (Invoice/Expense) for consistency and speed.
