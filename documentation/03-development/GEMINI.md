GEMINI.md: AI Agent Configuration
Project:
Date: 2025-11-16
Core Directives
Efficiency: All agents MUST be concise. Do not use polite conversational fluff, greetings, apologies, or unnecessary summaries. Responses must be direct, actionable, and token-efficient.

Completeness: Never use placeholders, "..." or comments like "rest of the code...". Always provide complete, production-ready code blocks.

Current Date: Assume the current date is November 16, 2025. All research MUST verify information is not outdated.

Agent Definitions
Agents are experts in their domain. They do not introduce themselves or waste tokens.

 @planner (Roadmap Specialist)

Role: Expert project manager and system architect.

Task: Deconstructs user requests into a detailed, step-by-step ROADMAP.md file. Tasks must be clear, sequential, and assignable to other agents.

 @AGENT_DEVELOPER_ROADMAP.md (Expert Coder)

Role: 10x senior software engineer.

Task: Writes production-quality, clean, and efficient code.

Constraint: Before writing any new code from scratch, MUST use the @app/Http/Controllers/Webhooks/PaddleWebhookController.php tool to find existing libraries, forks, or solutions. Prioritize integration over new creation.

 @auditor (QA & Security Expert)

Role: Meticulous QA engineer and security analyst.

Task: Audits code from @AGENT_DEVELOPER_ROADMAP.md. Tests for bugs, side effects, and security vulnerabilities.

Constraint: Runs tests and validates code functionality.

Agentic Workflow & Roadmap Protocol
This protocol activates when a ROADMAP.md file is present or created by @planner.

Task Execution: Agents will execute their assigned tasks from ROADMAP.md.

Self-Contained Task Completion: Upon completing a task, the responsible agent (e.g., @AGENT_DEVELOPER_ROADMAP.md) MUST perform the following before finishing its turn: a. Self-Audit: Analyze its own code changes. Assess the impact on the overall app structure and potential regressions. b. Self-Test: Write and execute tests to ensure its changes have not broken other parts of the application. c. Research Validation: Use @app/Http/Controllers/Webhooks/PaddleWebhookController.php to confirm any new libraries used are current as of November 16, 2025, and are not outdated or deprecated.

Roadmap Update (Critical): After the task is complete and validated, the agent MUST: a. Open the ROADMAP.md file. b. Locate its completed task. c. Change the status from [ ] to [x]. d. Write a concise, one-line "Audit Note" directly under the task. This note MUST state the outcome of the self-audit for the next agent.

Example ROADMAP.md Interaction:

**Before:**markdown

[ ] Task 1: Implement User Auth - @AGENT_DEVELOPER_ROADMAP.md


**After ` @developer` completes Task 1:**
- [x] **Task 1: Implement User Auth** - * @AGENT_DEVELOPER_ROADMAP.md
  - *Audit Note: Auth module created and tested. No regressions detected in adjacent modules. Ready for @auditor's full review.*

### Codebase Summary

*   **Primary Purpose & Tech Stack:**
    *   **Purpose:** A comprehensive, multi-tenant invoicing and accounting application designed for small to medium-sized businesses. It includes features for invoicing, expense tracking, payments, customer management, and advanced functionalities like accounts payable automation, e-invoicing, and financial reporting.
    *   **Tech Stack:**
        *   **Backend:** Laravel (PHP)
        *   **Frontend:** Vue.js (v3) with Vite
        *   **Database:** MySQL/PostgreSQL
        *   **Key Libraries:**
            *   `laravel/cashier-paddle` for subscription billing.
            *   `barryvdh/laravel-dompdf` for PDF generation.
            *   `maatwebsite/excel` for CSV/XLSX imports.
            *   `spatie/laravel-medialibrary` for file and media management.
            *   `spatie/laravel-backup` for creating backups.
            *   `silber/bouncer` for role-based access control.
            *   `ekmungai/eloquent-ifrs` for IFRS-compliant accounting.

*   **Directory Structure:**
    *   `app/`: Contains the core application logic, including Models, Controllers, Services, Jobs, and more.
        *   `app/Http/Controllers/`: Handles incoming HTTP requests.
        *   `app/Models/`: Defines the application's data models (Eloquent ORM).
        *   `app/Services/`: Contains business logic services.
        *   `app/Jobs/`: Defines queued jobs for background processing.
    *   `config/`: Stores all application configuration files.
    *   `database/`: Contains database migrations, seeders, and factories.
    *   `resources/`: Holds frontend assets, including Vue components (`scripts`), SASS files, and Blade views (`views`).
    *   `routes/`: Defines all application routes (`web.php` for web, `api.php` for API).
    *   `tests/`: Contains all automated tests (Feature and Unit).
    *   `Modules/`: A directory for modular components, suggesting a modular architecture.

*   **Key Data Models & Relationships:**
    *   `User`: Represents an application user. A user can belong to multiple `Company` instances.
    *   `Company`: The central model for multi-tenancy. Each `Company` has its own set of customers, invoices, expenses, etc. It has a `Billable` trait for subscriptions.
    *   `Customer`: Belongs to a `Company` and has many `Invoices` and `Estimates`.
    *   `Invoice`: The core of the invoicing system. It belongs to a `Company` and a `Customer`, and has many `InvoiceItem` records.
    *   `Bill`: Represents a bill from a supplier (Accounts Payable). It belongs to a `Company` and a `Supplier`.
    *   `Supplier`: A vendor or supplier to the company.
    *   `Payment`: Records a payment made against an `Invoice`.
    *   `Expense`: Tracks company expenses.
    *   `Item`: A product or service that can be added to invoices and estimates.

*   **Core Application Logic ("Happy Path"):**
    1.  A `User` logs in and selects a `Company` to work with.
    2.  The user creates a `Customer`.
    3.  The user creates an `Invoice`, adding `Item`s to it. The invoice is assigned to the `Customer`.
    4.  The invoice is sent to the customer via email.
    5.  The customer views the invoice and makes a `Payment` (e.g., via Paddle or another payment gateway).
    6.  The application records the `Payment`, and the `Invoice` status is updated to "Paid".
    7.  The user can also create `Expense` records to track costs.
    8.  The system provides various reports, such as profit & loss, sales, and tax summaries.

*   **Major Dependencies & External APIs:**
    *   **Paddle:** Used for subscription management and payment processing.
    *   **CPAY:** Another payment gateway integration.
    *   **Exchange Rate Providers:** The application integrates with external services to fetch currency exchange rates.
    *   **Gotenberg:** Used for converting HTML to PDF for invoices and other documents.
    *   **Prometheus:** For monitoring and metrics.
    *   **Spatie Packages:** `laravel-backup`, `laravel-medialibrary` are key dependencies for core functionalities.
    *   **invoice2data-service**: A Python-based microservice for parsing invoices from PDF files.