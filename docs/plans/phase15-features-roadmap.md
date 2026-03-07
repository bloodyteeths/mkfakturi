# Phase 15 -- Advanced Features Roadmap

> Generated 2026-03-07 from web research + codebase audit.
> Continues from phases P7--P14 (all completed).

---

## Table of Contents

1. [Overview](#overview)
2. [Codebase Audit Summary](#codebase-audit-summary)
3. [P15-01: Travel Expense Management](#p15-01-travel-expense-management)
4. [P15-02: Collections / Payment Reminders](#p15-02-collections--payment-reminders)
5. [P15-03: BI Dashboards](#p15-03-bi-dashboards)
6. [P15-04: Financial Consolidation (Enhanced)](#p15-04-financial-consolidation-enhanced)
7. [P15-05: Batch Operations (Partner)](#p15-05-batch-operations-partner)
8. [P15-06: Custom Report Builder](#p15-06-custom-report-builder)
9. [i18n Keys](#i18n-keys)
10. [Implementation Order & Dependencies](#implementation-order--dependencies)

---

## Overview

| Ticket | Feature | Est. Effort | Tier | Priority |
|--------|---------|-------------|------|----------|
| **P15-01** | Travel Expense Management (Patni Nalozi) | 12--16 days | Standard+ | SHOULD |
| **P15-02** | Collections / Payment Reminders | 6--8 days | Starter+ | MUST |
| **P15-03** | BI Dashboards | 8--12 days | Business+ | SHOULD |
| **P15-04** | Financial Consolidation (Enhanced) | 6--10 days | Max / Partner | COULD |
| **P15-05** | Batch Operations (Partner) | 5--7 days | Partner | SHOULD |
| **P15-06** | Custom Report Builder | 8--12 days | Standard+ | COULD |

**Total estimated: 45--65 days**

---

## Codebase Audit Summary

### What already exists

| Area | Existing Infrastructure | Files |
|------|------------------------|-------|
| **Expenses** | Full CRUD with categories, vendors, media attachments, approval workflow, recurring expenses, GL posting via `ifrs_transaction_id` | `app/Models/Expense.php`, `app/Models/RecurringExpense.php`, `app/Http/Controllers/V1/Admin/ExpenseController.php` |
| **Receipt OCR** | Azure Document Intelligence + invoice2data FastAPI; ReceiptScannerController handles photo upload, PDF conversion, and OCR parsing | `app/Services/InvoiceParsing/AzureDocumentIntelligenceClient.php`, `app/Http/Controllers/V1/Admin/AccountsPayable/ReceiptScannerController.php` |
| **Overdue tracking** | `CheckInvoiceStatus` command marks invoices overdue; `McpDataProvider` has aging buckets (1-30, 31-60, 61-90, 90+); `UnpaidSummaryWidget` shows due today / overdue / upcoming | `app/Console/Commands/CheckInvoiceStatus.php`, `app/Services/McpDataProvider.php` |
| **AI infrastructure** | AiInsightsService with Claude/OpenAI/Gemini providers, chat with conversation history, streaming SSE, risk detection, usage limits per tier | `app/Services/AiInsightsService.php`, `app/Http/Controllers/V1/Admin/AiInsightsController.php` |
| **Dashboard widgets** | 8 widgets: Stats, Chart, AI Insights, AI Chat, Quick Actions, Unpaid Summary, Recent Payments, Deadlines, Stock Summary | `resources/scripts/admin/views/dashboard/widgets/` |
| **Bulk reporting** | BulkReportController + BulkReportingService: multi-company trial balance, P&L, balance sheet + consolidated view with CSV/JSON export | `app/Http/Controllers/V1/Partner/BulkReportController.php`, `app/Services/BulkReportingService.php` |
| **Consolidation** | Basic aggregation in BulkReportingService: sums assets/liabilities/equity/revenue/expenses across companies -- NO intercompany elimination | `app/Services/BulkReportingService.php` |
| **Email infrastructure** | Postmark with `broadcast` and `outreach` streams; drip campaign system; SendDeadlineReminders command | `app/Console/Commands/SendDeadlineReminders.php`, drip commands |
| **Subscription tiers** | 6 tiers with feature flags and usage limits; three-layer enforcement (middleware, service, controller) | `config/subscriptions.php`, `app/Services/UsageLimitService.php` |
| **Partner portfolio** | Partner scope middleware, portfolio tier service, 1:1 sliding scale, bulk import, credit wallet, view-only mode | Multiple files in `Modules/Mk/Partner/` |

### What does NOT exist (confirmed by grep)

- No `travel_orders`, `travel_expenses`, `per_diem`, `dnevnica`, `paten` tables or models
- No `reminder_templates`, `reminder_history`, `collection_cases` tables
- No `dunning` or `chase` logic
- No financial ratio calculations (current ratio, quick ratio, ROE, etc.)
- No intercompany elimination or consolidation adjustments
- No custom report builder or saved report templates
- No batch close, batch VAT return, or batch operations beyond bulk reporting

---

## P15-01: Travel Expense Management

### Macedonian Legal Context

**Governing law:** Zakon za rabotni odnosi (Law on Labor Relations), Art. 113; General Collective Agreement for the Private Sector (Opst kolektiven dogovor za privatniot sektor).

**Travel Order (Paten nalog) -- mandatory document** containing:
- Employee name, position
- Purpose and destination (city/country)
- Departure and return date/time
- Transport type and vehicle details
- Per-diem calculation
- Advance payment amount
- Authorized signature

**Domestic per-diem (dnevnica):**
- Rate: 8% of base amount (prosecna mesecna neto plata vo RSM za poslednite 3 meseci)
- Base published quarterly by State Statistical Office (Drzaven zavod za statistika)
- As of late 2025: base ~33,370 MKD -> dnevnica ~2,670 MKD/day
- Half-day (6-12 hours): 50% of daily rate
- Full day (12+ hours or overnight): 100% of daily rate

**Foreign per-diem:**
- Rates per country set by Government Decision (Odluka za najvisoki iznosi na dnevnici za sluzbeni patuvanja vo stranstvo)
- Approximately 150 countries listed in USD
- Adjustments: 50% if lodging receipt provided and lodging included in per-diem base; reduced rates for long stays (30+ days)
- Tax-free under Art. 12/27 of Personal Income Tax Law

**Mileage allowance:**
- 30% of fuel price per km when using personal vehicle (regulated by Collective Agreement)

### Database Schema

```sql
-- Migration: 2026_xx_xx_000001_create_travel_orders_table.php

CREATE TABLE travel_orders (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id       BIGINT UNSIGNED NOT NULL,
    employee_id      BIGINT UNSIGNED NULL,           -- FK users.id (optional, may be non-employee)
    employee_name    VARCHAR(255) NOT NULL,
    employee_position VARCHAR(255) NULL,
    travel_number    VARCHAR(50) NOT NULL,            -- Auto-generated: PN-2026-001

    -- Purpose
    purpose          TEXT NOT NULL,                   -- Cel na patuvanjeto
    travel_type      ENUM('domestic','foreign') NOT NULL DEFAULT 'domestic',

    -- Dates
    departure_at     DATETIME NOT NULL,
    return_at        DATETIME NOT NULL,
    actual_return_at DATETIME NULL,                   -- Filled on settlement

    -- Transport
    transport_type   ENUM('company_vehicle','personal_vehicle','bus','train','airplane','other') NOT NULL,
    vehicle_plate    VARCHAR(20) NULL,                -- Reg. tablica
    vehicle_make     VARCHAR(100) NULL,

    -- Per-diem
    per_diem_base    DECIMAL(10,2) NOT NULL DEFAULT 0,  -- Base rate used
    per_diem_total   DECIMAL(10,2) NOT NULL DEFAULT 0,  -- Calculated total
    currency_id      BIGINT UNSIGNED NOT NULL,

    -- Mileage
    total_km         DECIMAL(8,1) NULL,
    km_rate          DECIMAL(6,2) NULL,               -- Rate per km (30% of fuel)
    mileage_total    DECIMAL(10,2) NOT NULL DEFAULT 0,

    -- Advance
    advance_amount   DECIMAL(10,2) NOT NULL DEFAULT 0,
    advance_paid_at  DATETIME NULL,

    -- Settlement
    total_expenses   DECIMAL(10,2) NOT NULL DEFAULT 0,  -- Sum of all expense lines
    settlement_total DECIMAL(10,2) NOT NULL DEFAULT 0,  -- per_diem + mileage + expenses
    reimbursement    DECIMAL(10,2) NOT NULL DEFAULT 0,  -- settlement - advance (can be negative)
    settled_at       DATETIME NULL,

    -- Workflow
    status           ENUM('draft','pending_approval','approved','traveling','settled','rejected','cancelled') NOT NULL DEFAULT 'draft',
    approved_by      BIGINT UNSIGNED NULL,
    approved_at      DATETIME NULL,
    rejected_reason  TEXT NULL,

    -- GL
    ifrs_transaction_id BIGINT UNSIGNED NULL,         -- Posted journal entry

    created_by       BIGINT UNSIGNED NOT NULL,
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,
    deleted_at       TIMESTAMP NULL,

    INDEX idx_travel_company_status (company_id, status),
    INDEX idx_travel_dates (departure_at, return_at),
    CONSTRAINT fk_travel_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT,
    CONSTRAINT fk_travel_employee FOREIGN KEY (employee_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_travel_currency FOREIGN KEY (currency_id) REFERENCES currencies(id) ON DELETE RESTRICT,
    CONSTRAINT fk_travel_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_travel_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE travel_order_segments (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    travel_order_id  BIGINT UNSIGNED NOT NULL,

    -- Segment details
    segment_order    INT UNSIGNED NOT NULL DEFAULT 1,
    from_city        VARCHAR(255) NOT NULL,
    from_country     CHAR(2) NOT NULL DEFAULT 'MK',   -- ISO 3166-1 alpha-2
    to_city          VARCHAR(255) NOT NULL,
    to_country       CHAR(2) NOT NULL DEFAULT 'MK',

    -- Dates for this segment
    departure_at     DATETIME NOT NULL,
    arrival_at       DATETIME NOT NULL,

    -- Per-diem for this segment
    per_diem_rate    DECIMAL(10,2) NOT NULL,           -- Country-specific rate
    per_diem_days    DECIMAL(4,2) NOT NULL DEFAULT 1,  -- Can be 0.5 for half-day
    per_diem_amount  DECIMAL(10,2) NOT NULL DEFAULT 0,

    -- Accommodation
    accommodation_provided BOOLEAN NOT NULL DEFAULT FALSE,
    meals_provided         BOOLEAN NOT NULL DEFAULT FALSE,
    per_diem_reduction_pct DECIMAL(5,2) NOT NULL DEFAULT 0,  -- 0, 20, 50 per MK law

    -- Distance (for mileage)
    distance_km      DECIMAL(8,1) NULL,

    -- Google Maps reference (optional)
    maps_polyline    TEXT NULL,
    maps_distance_m  INT UNSIGNED NULL,
    maps_duration_s  INT UNSIGNED NULL,

    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,

    INDEX idx_segment_order (travel_order_id, segment_order),
    CONSTRAINT fk_segment_travel FOREIGN KEY (travel_order_id) REFERENCES travel_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE travel_expenses (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    travel_order_id  BIGINT UNSIGNED NOT NULL,

    -- Expense details
    expense_date     DATE NOT NULL,
    category         ENUM('transport','accommodation','meals','parking','toll','taxi','other') NOT NULL,
    description      VARCHAR(500) NOT NULL,
    amount           DECIMAL(10,2) NOT NULL,
    currency_id      BIGINT UNSIGNED NOT NULL,
    exchange_rate    DECIMAL(10,6) NOT NULL DEFAULT 1,
    base_amount      DECIMAL(10,2) NOT NULL,           -- Amount in company currency

    -- Receipt
    has_receipt      BOOLEAN NOT NULL DEFAULT FALSE,
    receipt_path     VARCHAR(500) NULL,                 -- S3/R2 path

    -- OCR data (from AI scan)
    ocr_vendor       VARCHAR(255) NULL,
    ocr_amount       DECIMAL(10,2) NULL,
    ocr_date         DATE NULL,
    ocr_confidence   DECIMAL(3,2) NULL,                 -- 0.00-1.00
    ocr_raw_json     JSON NULL,

    -- GL mapping
    account_code     VARCHAR(20) NULL,                  -- e.g., 6250 (transport), 6260 (accommodation)

    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,

    INDEX idx_travelexp_order (travel_order_id),
    CONSTRAINT fk_travelexp_order FOREIGN KEY (travel_order_id) REFERENCES travel_orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_travelexp_currency FOREIGN KEY (currency_id) REFERENCES currencies(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Per-diem rate lookup tables
CREATE TABLE per_diem_domestic_rates (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    valid_from       DATE NOT NULL,
    valid_to         DATE NULL,
    base_salary      DECIMAL(10,2) NOT NULL,   -- Average net salary (DZS published)
    daily_rate       DECIMAL(10,2) NOT NULL,   -- 8% of base
    half_day_rate    DECIMAL(10,2) NOT NULL,   -- 50% of daily
    source_reference VARCHAR(255) NULL,         -- DZS publication reference
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,
    UNIQUE INDEX idx_domestic_valid (valid_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE per_diem_foreign_rates (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    country_code     CHAR(2) NOT NULL,          -- ISO 3166-1 alpha-2
    country_name_mk  VARCHAR(255) NOT NULL,
    daily_rate_usd   DECIMAL(10,2) NOT NULL,    -- Rate per Government Decision (in USD)
    valid_from       DATE NOT NULL,
    valid_to         DATE NULL,
    source_reference VARCHAR(255) NULL,
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,
    INDEX idx_foreign_country (country_code, valid_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Backend: Laravel

**New files to create:**

| File | Purpose |
|------|---------|
| `Modules/Mk/Http/Controllers/TravelOrderController.php` | CRUD + workflow (approve, reject, settle) |
| `Modules/Mk/Models/TravelOrder.php` | Model with segments/expenses relations |
| `Modules/Mk/Models/TravelOrderSegment.php` | Segment model |
| `Modules/Mk/Models/TravelExpense.php` | Individual expense line |
| `Modules/Mk/Models/PerDiemDomesticRate.php` | Domestic rate lookup |
| `Modules/Mk/Models/PerDiemForeignRate.php` | Foreign rate lookup |
| `Modules/Mk/Services/TravelOrderService.php` | Business logic: per-diem calculation, settlement, GL posting |
| `Modules/Mk/Services/PerDiemCalculator.php` | Rate lookup + calculation with MK law rules |
| `Modules/Mk/Services/TravelReceiptOcrService.php` | Wraps AzureDocumentIntelligenceClient for receipt scanning |
| `Modules/Mk/Services/MileageCalculator.php` | Google Maps Distance Matrix API integration |
| `database/migrations/2026_xx_xx_000001_create_travel_orders_table.php` | All tables above |
| `database/seeders/PerDiemRatesSeeder.php` | Seed domestic (historical) + foreign rates from Government Decision |
| `app/Jobs/TravelReceiptOcrJob.php` | Queue job for async OCR processing |

**Files to modify:**

| File | Change |
|------|--------|
| `routes/api.php` | Add `travel-orders` resource routes under `auth:sanctum` middleware |
| `config/subscriptions.php` | Add `travel_management` feature flag (Standard+), `travel_orders_per_month` limit |
| `config/mk.php` | Add `travel` section with mileage rate, fuel price, default account codes |
| `app/Services/UsageLimitService.php` | Add `travel_orders_per_month` check |
| `app/Domain/Accounting/IfrsAdapter.php` | Add `postTravelSettlement()` method for GL entries |

**API Endpoints:**

```
GET     /api/v1/travel-orders                    -- List (filterable by status, date range)
POST    /api/v1/travel-orders                    -- Create draft
GET     /api/v1/travel-orders/{id}               -- Show with segments + expenses
PUT     /api/v1/travel-orders/{id}               -- Update draft
DELETE  /api/v1/travel-orders/{id}               -- Delete draft only
POST    /api/v1/travel-orders/{id}/segments      -- Add segment
PUT     /api/v1/travel-orders/{id}/segments/{segId}  -- Update segment
DELETE  /api/v1/travel-orders/{id}/segments/{segId}  -- Remove segment
POST    /api/v1/travel-orders/{id}/expenses      -- Add expense line
PUT     /api/v1/travel-orders/{id}/expenses/{expId}  -- Update expense
DELETE  /api/v1/travel-orders/{id}/expenses/{expId}  -- Remove expense
POST    /api/v1/travel-orders/{id}/expenses/{expId}/scan -- Upload receipt for OCR
POST    /api/v1/travel-orders/{id}/submit         -- Submit for approval
POST    /api/v1/travel-orders/{id}/approve         -- Approve (manager)
POST    /api/v1/travel-orders/{id}/reject          -- Reject with reason
POST    /api/v1/travel-orders/{id}/settle          -- Settlement calculation + GL post
GET     /api/v1/travel-orders/{id}/pdf             -- Generate travel order PDF
GET     /api/v1/per-diem/domestic/current          -- Current domestic rate
GET     /api/v1/per-diem/foreign/{countryCode}     -- Foreign rate for country
POST    /api/v1/travel-orders/{id}/calculate-route -- Google Maps distance calc
```

**Per-diem calculation logic (PerDiemCalculator.php):**

```php
public function calculate(TravelOrder $order): PerDiemResult
{
    $total = 0;
    foreach ($order->segments as $segment) {
        if ($segment->from_country === 'MK' && $segment->to_country === 'MK') {
            // Domestic: 8% of base salary
            $rate = $this->getDomesticRate($segment->departure_at);
            $hours = $segment->departure_at->diffInHours($segment->arrival_at);
            $days = $hours >= 12 ? 1.0 : ($hours >= 6 ? 0.5 : 0);
            $amount = $rate->daily_rate * $days;
        } else {
            // Foreign: country-specific rate from Government Decision
            $rate = $this->getForeignRate(
                $segment->to_country,
                $segment->departure_at
            );
            $days = $this->calculateForeignDays($segment);
            $amount = $rate->daily_rate_usd * $days;

            // Apply reductions per MK law
            if ($segment->accommodation_provided && $segment->meals_provided) {
                $amount *= 0.20; // Only 20% when everything provided
            } elseif ($segment->accommodation_provided) {
                $amount *= 0.50; // 50% when lodging receipt provided
            }
        }
        $segment->update([
            'per_diem_rate' => $rate->daily_rate ?? $rate->daily_rate_usd,
            'per_diem_days' => $days,
            'per_diem_amount' => $amount,
        ]);
        $total += $amount;
    }
    return new PerDiemResult($total, $order->segments);
}
```

### Frontend: Vue 3

**New files:**

| File | Purpose |
|------|---------|
| `resources/scripts/admin/views/travel/Index.vue` | Travel orders list with status filters |
| `resources/scripts/admin/views/travel/Create.vue` | Multi-step form: details -> segments -> expenses -> review |
| `resources/scripts/admin/views/travel/Show.vue` | Travel order detail with timeline, expenses, PDF |
| `resources/scripts/admin/views/travel/components/SegmentEditor.vue` | Add/edit segment with city autocomplete |
| `resources/scripts/admin/views/travel/components/ExpenseLineEditor.vue` | Expense line with receipt upload + OCR result |
| `resources/scripts/admin/views/travel/components/PerDiemCalculation.vue` | Real-time per-diem breakdown display |
| `resources/scripts/admin/views/travel/components/RouteMap.vue` | Optional Google Maps visualization |
| `resources/scripts/admin/views/travel/components/ReceiptCapture.vue` | Mobile camera capture + upload |
| `resources/scripts/admin/stores/travel.js` | Pinia store for travel orders |

**Files to modify:**

| File | Change |
|------|--------|
| `resources/scripts/admin/admin-router.js` | Add travel routes under expenses section |
| `resources/scripts/admin/layouts/partials/TheSiteSidebar.vue` | Add "Paten nalozi" nav item under Expenses |

**Mobile-first receipt capture UX:**
1. User taps "Add Receipt" on expense line
2. Camera opens (via `navigator.mediaDevices.getUserMedia`)
3. Photo taken -> auto-crop via CSS `object-fit`
4. Upload to `/travel-orders/{id}/expenses/{expId}/scan`
5. OCR job processes in background (queue `high`)
6. When done, auto-fills vendor, amount, date fields
7. User confirms or edits -> save

### GL Posting (Settlement)

When a travel order is settled, post these journal entries:

| Account | Debit | Credit | Description |
|---------|-------|--------|-------------|
| 6250 - Transport costs | X | | Transport segment expenses |
| 6260 - Accommodation | X | | Hotel/lodging expenses |
| 6270 - Per-diem allowance | X | | Calculated per-diem |
| 6280 - Mileage reimbursement | X | | km * rate |
| 2210 - Employee advances | | X | Advance amount (if given) |
| 2420 - Employee reimbursements | | X | Net reimbursement due |

### PDF Output (Paten nalog format)

Generate PDF matching the standard Macedonian travel order form (A4):
- Header: Company name, address, tax ID
- Section 1: Employee details
- Section 2: Travel details (purpose, destination, dates)
- Section 3: Transport details
- Section 4: Per-diem calculation breakdown
- Section 5: Expense listing with receipt references
- Section 6: Settlement summary (total - advance = reimbursement)
- Footer: Signatures (traveler, approved by, accountant)

Use existing PDF generation infrastructure (DomPDF via `barryvdh/laravel-dompdf`).

### AI Integration

Reuse existing `AzureDocumentIntelligenceClient` for receipt scanning with a new `prebuilt-receipt` model (more accurate for receipts than `prebuilt-invoice`). The `TravelReceiptOcrService` wraps this:

```php
class TravelReceiptOcrService
{
    public function scanReceipt(UploadedFile $file): OcrResult
    {
        // 1. Upload to Azure with prebuilt-receipt model
        // 2. Extract: merchant, total, date, items, currency
        // 3. Suggest expense category based on merchant name
        // 4. Return structured OcrResult
    }

    public function suggestCategory(string $merchantName): string
    {
        // AI-powered: "Shell" -> transport/fuel, "Hotel Park" -> accommodation
        // Uses simple keyword matching first, AI fallback
    }
}
```

### Test Plan

1. Unit: `PerDiemCalculatorTest` -- domestic rate lookups, foreign rate lookups, half-day calc, reduction rules
2. Unit: `TravelOrderServiceTest` -- settlement calculation, GL posting
3. Feature: `TravelOrderTest` -- full CRUD + workflow transitions
4. Feature: `TravelOcrTest` -- receipt upload + OCR result mapping
5. Browser: Cypress -- create travel order, add segments, upload receipt, submit, approve, settle

---

## P15-02: Collections / Payment Reminders

### Industry Best Practices (from Chaser, GoCardless, YayPay research)

- **Graduated escalation**: friendly (pre-due) -> firm (7-14 days overdue) -> final (30+ days) -> legal notice
- **Customizable templates** per company with merge fields (customer name, invoice #, amount, due date)
- **Automated scheduling**: configurable per-company (e.g., Day -3 courtesy, Day +7 first reminder, Day +14 second, Day +30 final)
- **Track engagement**: sent, delivered, opened, clicked, paid
- **Payment link**: include direct payment link in reminder email
- **Smart timing**: AI suggests optimal send time based on payment history
- **Escalation to phone**: after 3 email reminders, flag for manual follow-up

### Database Schema

```sql
-- Migration: 2026_xx_xx_000002_create_collections_tables.php

CREATE TABLE reminder_templates (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id       BIGINT UNSIGNED NOT NULL,

    name             VARCHAR(255) NOT NULL,            -- "Friendly Reminder", "Final Notice"
    escalation_level INT UNSIGNED NOT NULL DEFAULT 1,  -- 1=friendly, 2=firm, 3=final, 4=legal

    -- Content
    subject_mk       VARCHAR(500) NOT NULL,
    body_mk          TEXT NOT NULL,
    subject_en       VARCHAR(500) NULL,
    body_en          TEXT NULL,

    -- Merge fields: {{customer_name}}, {{invoice_number}}, {{amount}}, {{due_date}},
    --               {{days_overdue}}, {{company_name}}, {{payment_link}}

    -- Channel
    channel          ENUM('email','sms','viber') NOT NULL DEFAULT 'email',

    is_default       BOOLEAN NOT NULL DEFAULT FALSE,
    is_active        BOOLEAN NOT NULL DEFAULT TRUE,

    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,

    INDEX idx_template_company (company_id, escalation_level),
    CONSTRAINT fk_template_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE reminder_schedules (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id       BIGINT UNSIGNED NOT NULL,

    name             VARCHAR(255) NOT NULL DEFAULT 'Default Schedule',

    -- Schedule definition (JSON array of steps)
    -- [{"days_offset": -3, "template_id": 1, "action": "courtesy"},
    --  {"days_offset": 7,  "template_id": 2, "action": "first_reminder"},
    --  {"days_offset": 14, "template_id": 3, "action": "second_reminder"},
    --  {"days_offset": 30, "template_id": 4, "action": "final_notice"},
    --  {"days_offset": 60, "template_id": null, "action": "flag_for_review"}]
    steps            JSON NOT NULL,

    is_active        BOOLEAN NOT NULL DEFAULT TRUE,
    apply_to_all     BOOLEAN NOT NULL DEFAULT TRUE,   -- Apply to all customers

    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,

    CONSTRAINT fk_schedule_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE reminder_history (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id       BIGINT UNSIGNED NOT NULL,
    invoice_id       BIGINT UNSIGNED NOT NULL,
    customer_id      BIGINT UNSIGNED NOT NULL,
    template_id      BIGINT UNSIGNED NULL,
    schedule_id      BIGINT UNSIGNED NULL,

    -- Reminder details
    escalation_level INT UNSIGNED NOT NULL,
    channel          ENUM('email','sms','viber') NOT NULL DEFAULT 'email',

    -- Content (snapshot at time of send)
    subject          VARCHAR(500) NOT NULL,
    body             TEXT NOT NULL,

    -- Tracking
    status           ENUM('queued','sent','delivered','opened','clicked','bounced','failed') NOT NULL DEFAULT 'queued',
    sent_at          DATETIME NULL,
    delivered_at     DATETIME NULL,
    opened_at        DATETIME NULL,
    clicked_at       DATETIME NULL,

    -- Postmark
    postmark_message_id VARCHAR(100) NULL,
    postmark_stream     VARCHAR(50) NOT NULL DEFAULT 'broadcast',

    -- Result
    paid_after_reminder BOOLEAN NOT NULL DEFAULT FALSE,
    paid_at             DATETIME NULL,

    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,

    INDEX idx_reminder_invoice (invoice_id, escalation_level),
    INDEX idx_reminder_customer (customer_id),
    INDEX idx_reminder_company_date (company_id, created_at),
    CONSTRAINT fk_reminder_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_reminder_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_reminder_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE collection_cases (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id       BIGINT UNSIGNED NOT NULL,
    customer_id      BIGINT UNSIGNED NOT NULL,

    -- Case details
    case_number      VARCHAR(50) NOT NULL,             -- COL-2026-001
    status           ENUM('open','in_progress','escalated','resolved','written_off') NOT NULL DEFAULT 'open',

    -- Aggregated from invoices
    total_outstanding DECIMAL(12,2) NOT NULL DEFAULT 0,
    oldest_overdue_date DATE NULL,
    max_days_overdue INT UNSIGNED NOT NULL DEFAULT 0,
    invoice_count    INT UNSIGNED NOT NULL DEFAULT 0,

    -- Actions taken
    reminder_count   INT UNSIGNED NOT NULL DEFAULT 0,
    last_reminder_at DATETIME NULL,
    last_contact_at  DATETIME NULL,

    -- Notes
    notes            TEXT NULL,

    -- Assignment
    assigned_to      BIGINT UNSIGNED NULL,

    -- Resolution
    resolved_at      DATETIME NULL,
    resolution_notes TEXT NULL,

    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,

    UNIQUE INDEX idx_case_company_customer (company_id, customer_id),
    CONSTRAINT fk_case_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_case_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Backend

**New files:**

| File | Purpose |
|------|---------|
| `Modules/Mk/Http/Controllers/ReminderController.php` | Template CRUD, schedule config, manual send |
| `Modules/Mk/Http/Controllers/CollectionController.php` | Collection case management |
| `Modules/Mk/Models/ReminderTemplate.php` | Template model with merge field rendering |
| `Modules/Mk/Models/ReminderSchedule.php` | Schedule model |
| `Modules/Mk/Models/ReminderHistory.php` | History/tracking model |
| `Modules/Mk/Models/CollectionCase.php` | Collection case model |
| `Modules/Mk/Services/ReminderService.php` | Core service: send reminders, merge fields, track delivery |
| `Modules/Mk/Services/CollectionService.php` | Create/update collection cases from overdue invoices |
| `Modules/Mk/Services/ReminderSchedulerService.php` | Evaluate which invoices need reminders today |
| `app/Console/Commands/SendPaymentReminders.php` | Artisan command, run hourly 08:00-18:00 Skopje time |
| `app/Mail/PaymentReminder.php` | Mailable using Postmark broadcast stream |
| `resources/views/emails/reminders/payment-reminder.blade.php` | Blade template |

**Files to modify:**

| File | Change |
|------|--------|
| `routes/api.php` | Add reminder + collection routes |
| `routes/console.php` | Schedule `reminders:send` hourly 08-18 Skopje |
| `config/subscriptions.php` | Add `payment_reminders` feature (Starter+), `reminders_per_month` limit |
| `config/mk.php` | Add `reminders` section with default escalation days, Postmark stream |

**API Endpoints:**

```
-- Templates
GET     /api/v1/reminders/templates              -- List templates
POST    /api/v1/reminders/templates              -- Create template
PUT     /api/v1/reminders/templates/{id}         -- Update
DELETE  /api/v1/reminders/templates/{id}         -- Delete
POST    /api/v1/reminders/templates/seed-defaults -- Create default MK templates

-- Schedule
GET     /api/v1/reminders/schedule               -- Get company schedule
PUT     /api/v1/reminders/schedule               -- Update schedule steps

-- Manual actions
POST    /api/v1/reminders/send                   -- Send reminder for specific invoice(s)
POST    /api/v1/reminders/send-bulk              -- Send to all overdue invoices matching criteria

-- History
GET     /api/v1/reminders/history                -- History with filters (customer, invoice, date range)
GET     /api/v1/reminders/history/{invoiceId}    -- History for specific invoice

-- Collection cases
GET     /api/v1/collections                      -- List open cases
GET     /api/v1/collections/{id}                 -- Case detail with invoice list + reminder history
PUT     /api/v1/collections/{id}                 -- Update case status, notes
POST    /api/v1/collections/refresh              -- Recalculate cases from current overdue data

-- Partner (cross-company)
GET     /api/v1/partner/collections/overview      -- Cross-company overdue dashboard
```

**Scheduler logic (daily cron):**

```php
// SendPaymentReminders command
public function handle()
{
    $companies = Company::whereHas('reminderSchedule', fn($q) => $q->where('is_active', true))->get();

    foreach ($companies as $company) {
        $schedule = $company->reminderSchedule;

        $overdueInvoices = Invoice::where('company_id', $company->id)
            ->where('status', 'SENT')
            ->where('due_date', '<', now())
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = now()->diffInDays($invoice->due_date);
            $nextStep = $this->getNextStep($schedule, $invoice, $daysOverdue);

            if ($nextStep) {
                $this->reminderService->sendReminder($invoice, $nextStep);
            }
        }
    }
}
```

**Email sending (CRITICAL -- uses broadcast stream):**

```php
// PaymentReminder mailable
public function build()
{
    return $this->from(config('mail.from.address'), $this->company->name)
        ->subject($this->subject)
        ->view('emails.reminders.payment-reminder')
        ->withSymfonyMessage(function ($message) {
            $message->getHeaders()->addTextHeader(
                'X-PM-Message-Stream', 'broadcast'
            );
        });
}
```

### Frontend

**New files:**

| File | Purpose |
|------|---------|
| `resources/scripts/admin/views/collections/Index.vue` | Collections overview with case list + stats |
| `resources/scripts/admin/views/collections/CaseDetail.vue` | Single case: invoices, timeline, actions |
| `resources/scripts/admin/views/collections/ReminderTemplates.vue` | Template editor with merge field preview |
| `resources/scripts/admin/views/collections/ReminderSchedule.vue` | Visual schedule editor (timeline) |
| `resources/scripts/admin/views/collections/ReminderHistory.vue` | History log with delivery status |
| `resources/scripts/admin/views/collections/components/ReminderPreview.vue` | Preview merged email |
| `resources/scripts/admin/views/collections/components/EscalationTimeline.vue` | Visual escalation path |
| `resources/scripts/admin/stores/collections.js` | Pinia store |

**Modifications to existing files:**

| File | Change |
|------|--------|
| `resources/scripts/admin/views/invoices/Index.vue` | Add "Send Reminder" action button on overdue invoices |
| `resources/scripts/admin/views/customers/View.vue` | Add "Collection Status" tab showing reminder history |
| `resources/scripts/admin/views/dashboard/Dashboard.vue` | Add `CollectionsWidget` showing overdue summary + action |
| `resources/scripts/admin/admin-router.js` | Add collections routes |
| `resources/scripts/admin/layouts/partials/TheSiteSidebar.vue` | Add "Collections" nav item |

### Default Templates (seeded on first use)

**Template 1 - Courtesy (3 days before due):**
Subject: `Потсетник: Фактура {{invoice_number}} доспева за плаќање`
Body: Friendly reminder that invoice is coming due.

**Template 2 - First Reminder (7 days overdue):**
Subject: `Фактура {{invoice_number}} е доспеана - потсетник за плаќање`
Body: Invoice is overdue, please pay at your earliest convenience.

**Template 3 - Second Reminder (14 days overdue):**
Subject: `Второ потсетување: Неплатена фактура {{invoice_number}}`
Body: More firm tone, mention of potential consequences.

**Template 4 - Final Notice (30 days overdue):**
Subject: `Последно известување: Фактура {{invoice_number}} - {{days_overdue}} дена доспеана`
Body: Final notice before escalation to legal proceedings.

### AI Integration

Reuse existing `AiInsightsService` to add:
- **Payment prediction**: Based on customer's payment history, predict likelihood of payment
- **Optimal timing**: Suggest best day/time to send reminder (learn from `opened_at` + `paid_at` data)
- **Tone suggestion**: Given customer relationship and overdue days, suggest email tone

### Test Plan

1. Unit: `ReminderServiceTest` -- merge field rendering, schedule evaluation, escalation logic
2. Unit: `CollectionServiceTest` -- case creation from overdue invoices
3. Feature: `ReminderApiTest` -- template CRUD, schedule config, manual send
4. Feature: `ReminderCronTest` -- verify correct invoices get reminders at correct escalation
5. Integration: Postmark webhook for delivery tracking

---

## P15-03: BI Dashboards

### Industry Research (from Fathom, Spotlight, Jirav)

- **Fathom**: Pre-built KPI dashboards, trend analysis, benchmarking, AI Commentary Writer
- **Spotlight**: Branded reporting, budgeting integration, advisory-focused
- **Jirav**: FP&A modeling, driver-based forecasting, scenario planning
- **Key insight**: Most SMBs want pre-built dashboards with minimal setup, not build-your-own

### Database Schema

```sql
-- Migration: 2026_xx_xx_000003_create_bi_dashboards_tables.php

CREATE TABLE dashboard_configs (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id       BIGINT UNSIGNED NOT NULL,
    user_id          BIGINT UNSIGNED NOT NULL,

    name             VARCHAR(255) NOT NULL DEFAULT 'Default Dashboard',
    layout           JSON NOT NULL,     -- Widget positions and sizes
    -- Example: [{"widget":"revenue_trend","col":0,"row":0,"w":6,"h":4},
    --           {"widget":"expense_breakdown","col":6,"row":0,"w":6,"h":4}]

    is_default       BOOLEAN NOT NULL DEFAULT FALSE,

    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,

    INDEX idx_dashboard_user (company_id, user_id),
    CONSTRAINT fk_dashboard_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_dashboard_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE financial_snapshots (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id       BIGINT UNSIGNED NOT NULL,
    snapshot_date    DATE NOT NULL,
    period_type      ENUM('daily','monthly','quarterly','yearly') NOT NULL DEFAULT 'monthly',

    -- Balance sheet items (from IFRS adapter)
    total_assets         DECIMAL(14,2) NULL,
    current_assets       DECIMAL(14,2) NULL,
    fixed_assets         DECIMAL(14,2) NULL,
    total_liabilities    DECIMAL(14,2) NULL,
    current_liabilities  DECIMAL(14,2) NULL,
    long_term_liabilities DECIMAL(14,2) NULL,
    total_equity         DECIMAL(14,2) NULL,
    cash_and_equivalents DECIMAL(14,2) NULL,
    accounts_receivable  DECIMAL(14,2) NULL,
    inventory            DECIMAL(14,2) NULL,
    accounts_payable     DECIMAL(14,2) NULL,

    -- Income statement items
    total_revenue        DECIMAL(14,2) NULL,
    total_expenses       DECIMAL(14,2) NULL,
    operating_income     DECIMAL(14,2) NULL,
    net_income           DECIMAL(14,2) NULL,
    cost_of_goods_sold   DECIMAL(14,2) NULL,

    -- Ratios (pre-calculated for fast rendering)
    current_ratio        DECIMAL(8,4) NULL,   -- current_assets / current_liabilities
    quick_ratio          DECIMAL(8,4) NULL,   -- (current_assets - inventory) / current_liabilities
    debt_to_equity       DECIMAL(8,4) NULL,   -- total_liabilities / total_equity
    return_on_equity     DECIMAL(8,4) NULL,   -- net_income / total_equity
    return_on_assets     DECIMAL(8,4) NULL,   -- net_income / total_assets
    gross_profit_margin  DECIMAL(8,4) NULL,   -- (revenue - COGS) / revenue
    net_profit_margin    DECIMAL(8,4) NULL,   -- net_income / revenue
    asset_turnover       DECIMAL(8,4) NULL,   -- revenue / total_assets

    -- Solvency scores
    altman_z_score       DECIMAL(8,4) NULL,
    kralicek_quicktest   DECIMAL(8,4) NULL,

    -- Operational metrics
    invoice_count        INT UNSIGNED NULL,
    paid_invoice_count   INT UNSIGNED NULL,
    overdue_invoice_count INT UNSIGNED NULL,
    average_payment_days DECIMAL(6,2) NULL,
    customer_count       INT UNSIGNED NULL,
    new_customer_count   INT UNSIGNED NULL,

    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,

    UNIQUE INDEX idx_snapshot_date (company_id, snapshot_date, period_type),
    CONSTRAINT fk_snapshot_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Backend

**New files:**

| File | Purpose |
|------|---------|
| `Modules/Mk/Http/Controllers/BiDashboardController.php` | Dashboard config CRUD + KPI data endpoints |
| `Modules/Mk/Services/FinancialRatioService.php` | Calculate all ratios from IFRS data |
| `Modules/Mk/Services/FinancialSnapshotService.php` | Capture snapshots, trend data |
| `Modules/Mk/Services/SolvencyScoreService.php` | Altman Z-score, Kralicek Quicktest |
| `app/Console/Commands/CaptureFinancialSnapshots.php` | Monthly snapshot command |
| `app/Jobs/CaptureSnapshotJob.php` | Queue job per company |

**Financial ratio calculations:**

```php
class FinancialRatioService
{
    public function calculateAll(Company $company, string $asOfDate): array
    {
        $bs = $this->ifrsAdapter->getBalanceSheet($company, $asOfDate);
        $is = $this->ifrsAdapter->getIncomeStatement($company,
            Carbon::parse($asOfDate)->startOfYear()->toDateString(), $asOfDate);

        $ca = $this->extractCurrentAssets($bs);
        $cl = $this->extractCurrentLiabilities($bs);
        $inv = $this->extractInventory($bs);
        $ta = $bs['balance_sheet']['totals']['assets'] ?? 0;
        $tl = $bs['balance_sheet']['totals']['liabilities'] ?? 0;
        $te = $bs['balance_sheet']['totals']['equity'] ?? 0;
        $rev = $is['income_statement']['totals']['revenue'] ?? 0;
        $exp = $is['income_statement']['totals']['expenses'] ?? 0;
        $ni = $rev - $exp;

        return [
            'current_ratio' => $cl > 0 ? round($ca / $cl, 4) : null,
            'quick_ratio' => $cl > 0 ? round(($ca - $inv) / $cl, 4) : null,
            'debt_to_equity' => $te > 0 ? round($tl / $te, 4) : null,
            'return_on_equity' => $te > 0 ? round($ni / $te, 4) : null,
            'return_on_assets' => $ta > 0 ? round($ni / $ta, 4) : null,
            'net_profit_margin' => $rev > 0 ? round($ni / $rev, 4) : null,
            'asset_turnover' => $ta > 0 ? round($rev / $ta, 4) : null,
        ];
    }
}
```

**Altman Z-Score (for non-public companies, Z''-Score model):**

```php
// Z'' = 6.56*X1 + 3.26*X2 + 6.72*X3 + 1.05*X4
// X1 = Working Capital / Total Assets
// X2 = Retained Earnings / Total Assets
// X3 = EBIT / Total Assets
// X4 = Book Value of Equity / Total Liabilities
// Safe > 2.6, Grey 1.1-2.6, Distress < 1.1
```

**API Endpoints:**

```
GET     /api/v1/bi/dashboard                     -- Get dashboard config + current KPIs
PUT     /api/v1/bi/dashboard                     -- Save dashboard layout
GET     /api/v1/bi/ratios                        -- Current financial ratios
GET     /api/v1/bi/ratios/trend?months=12        -- Ratio trends over time
GET     /api/v1/bi/revenue-trend?months=12       -- Monthly revenue/expense chart data
GET     /api/v1/bi/expense-breakdown             -- Expense by category (pie chart data)
GET     /api/v1/bi/cash-flow-trend?months=12     -- Cash flow trend
GET     /api/v1/bi/customer-metrics              -- Top customers, concentration, churn
GET     /api/v1/bi/solvency                      -- Z-score + Kralicek assessment
GET     /api/v1/bi/aging-receivables             -- AR aging buckets with drill-down
GET     /api/v1/bi/aging-payables                -- AP aging buckets
POST    /api/v1/bi/ai-query                      -- Natural language financial question
GET     /api/v1/bi/snapshots?from=2025-01&to=2026-03 -- Historical snapshots
```

### Frontend

**New files:**

| File | Purpose |
|------|---------|
| `resources/scripts/admin/views/bi/Dashboard.vue` | Main BI dashboard with widget grid |
| `resources/scripts/admin/views/bi/components/RatioCard.vue` | Single ratio with gauge/trend sparkline |
| `resources/scripts/admin/views/bi/components/RevenueTrendChart.vue` | 12-month revenue/expense bar+line chart |
| `resources/scripts/admin/views/bi/components/ExpenseBreakdownChart.vue` | Donut/pie chart by category |
| `resources/scripts/admin/views/bi/components/CashFlowChart.vue` | Cash flow waterfall chart |
| `resources/scripts/admin/views/bi/components/AgingChart.vue` | Stacked bar for AR/AP aging |
| `resources/scripts/admin/views/bi/components/SolvencyScoreCard.vue` | Z-score gauge with zone indicators |
| `resources/scripts/admin/views/bi/components/CustomerConcentration.vue` | Top 10 customers bar chart |
| `resources/scripts/admin/views/bi/components/AiQueryInput.vue` | Natural language query box |
| `resources/scripts/admin/views/bi/components/WidgetPicker.vue` | Add/remove widgets from dashboard |
| `resources/scripts/admin/stores/bi-dashboard.js` | Pinia store |

**Charting library**: Use `chart.js` (already present in package.json via `vue-chartjs`) or add `apexcharts` via `vue3-apexcharts` for richer visualizations (waterfall, gauge, sparkline).

**Dashboard layout**: Use `vue-grid-layout` for drag-and-drop widget positioning. Each widget is a Vue component with standardized props (`companyId`, `dateRange`, `isLoading`).

**Pre-built dashboard templates:**

1. **Financial Overview** (default): Revenue trend + Expense breakdown + Ratios + Cash flow
2. **Receivables Focus**: AR aging + Top overdue customers + Collection rate + Days sales outstanding
3. **Profitability Analysis**: Margin trends + Expense categories + Revenue per customer + Break-even
4. **Solvency Check**: Z-score + Kralicek + Debt-to-equity trend + Cash runway

### AI Integration

Extend `AiInsightsService.answerQuestion()` to handle BI-specific queries:

```
User: "Show me which expenses grew the most this quarter"
-> System fetches expense data by category for current vs previous quarter
-> AI analyzes and returns structured response with chart data

User: "Am I at risk of insolvency?"
-> System calculates Z-score, Kralicek, cash runway
-> AI interprets results in context of MK market

User: "Why did my profit margin drop in February?"
-> System fetches monthly P&L breakdown
-> AI identifies largest expense increases and revenue changes
```

### Test Plan

1. Unit: `FinancialRatioServiceTest` -- all ratio calculations with known inputs
2. Unit: `SolvencyScoreServiceTest` -- Z-score + Kralicek formulas
3. Feature: `BiDashboardTest` -- dashboard config CRUD, KPI data endpoints
4. Feature: `SnapshotCommandTest` -- verify monthly snapshot capture
5. Browser: Cypress -- load dashboard, interact with charts, change date range

---

## P15-04: Financial Consolidation (Enhanced)

### Current State

The existing `BulkReportingService` does simple aggregation: sum assets, liabilities, equity, revenue, expenses across companies. It does NOT handle:
- Intercompany transaction identification
- Elimination entries
- Multi-currency translation adjustments
- Minority interest
- Consolidation journal entries

### Enhanced Consolidation Design

```sql
-- Migration: 2026_xx_xx_000004_create_consolidation_tables.php

CREATE TABLE consolidation_groups (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id       BIGINT UNSIGNED NULL,             -- Partner who owns the group

    name             VARCHAR(255) NOT NULL,
    parent_company_id BIGINT UNSIGNED NOT NULL,         -- The parent/holding company

    -- Settings
    reporting_currency_id BIGINT UNSIGNED NOT NULL,
    elimination_method    ENUM('full','proportional') NOT NULL DEFAULT 'full',

    is_active        BOOLEAN NOT NULL DEFAULT TRUE,

    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,

    CONSTRAINT fk_consol_parent FOREIGN KEY (parent_company_id) REFERENCES companies(id) ON DELETE RESTRICT,
    CONSTRAINT fk_consol_currency FOREIGN KEY (reporting_currency_id) REFERENCES currencies(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE consolidation_group_members (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id         BIGINT UNSIGNED NOT NULL,
    company_id       BIGINT UNSIGNED NOT NULL,

    ownership_pct    DECIMAL(5,2) NOT NULL DEFAULT 100, -- Ownership percentage
    is_parent        BOOLEAN NOT NULL DEFAULT FALSE,

    -- Currency translation
    functional_currency_id BIGINT UNSIGNED NOT NULL,
    translation_method     ENUM('current_rate','temporal') NOT NULL DEFAULT 'current_rate',

    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,

    UNIQUE INDEX idx_member_unique (group_id, company_id),
    CONSTRAINT fk_member_group FOREIGN KEY (group_id) REFERENCES consolidation_groups(id) ON DELETE CASCADE,
    CONSTRAINT fk_member_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE intercompany_rules (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id         BIGINT UNSIGNED NOT NULL,

    -- Match criteria
    company_a_id     BIGINT UNSIGNED NOT NULL,
    company_b_id     BIGINT UNSIGNED NOT NULL,

    -- Account mapping for elimination
    -- When company_a has receivable from company_b in account X,
    -- and company_b has payable to company_a in account Y,
    -- eliminate both during consolidation
    account_a_code   VARCHAR(20) NOT NULL,             -- e.g., 1200 (receivable)
    account_b_code   VARCHAR(20) NOT NULL,             -- e.g., 2200 (payable)

    elimination_type ENUM('ar_ap','revenue_expense','investment_equity','interco_profit') NOT NULL,

    is_active        BOOLEAN NOT NULL DEFAULT TRUE,
    auto_detect      BOOLEAN NOT NULL DEFAULT TRUE,    -- Auto-detect from customer/supplier links

    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,

    CONSTRAINT fk_rule_group FOREIGN KEY (group_id) REFERENCES consolidation_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE consolidation_runs (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id         BIGINT UNSIGNED NOT NULL,

    as_of_date       DATE NOT NULL,
    from_date        DATE NOT NULL,

    -- Results
    status           ENUM('running','completed','failed') NOT NULL DEFAULT 'running',

    -- Aggregated totals
    consolidated_assets      DECIMAL(14,2) NULL,
    consolidated_liabilities DECIMAL(14,2) NULL,
    consolidated_equity      DECIMAL(14,2) NULL,
    consolidated_revenue     DECIMAL(14,2) NULL,
    consolidated_expenses    DECIMAL(14,2) NULL,
    consolidated_net_income  DECIMAL(14,2) NULL,

    -- Eliminations applied
    total_eliminations       DECIMAL(14,2) NULL,
    elimination_entries      JSON NULL,   -- Array of elimination journal entries

    -- Translation adjustments
    translation_adjustments  JSON NULL,

    -- Full report data
    report_data      JSON NULL,

    created_by       BIGINT UNSIGNED NOT NULL,
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,

    INDEX idx_run_group_date (group_id, as_of_date),
    CONSTRAINT fk_run_group FOREIGN KEY (group_id) REFERENCES consolidation_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Backend

**New files:**

| File | Purpose |
|------|---------|
| `Modules/Mk/Http/Controllers/ConsolidationController.php` | Group CRUD, run consolidation, view results |
| `Modules/Mk/Models/ConsolidationGroup.php` | Group model with members relation |
| `Modules/Mk/Models/ConsolidationGroupMember.php` | Member model |
| `Modules/Mk/Models/IntercompanyRule.php` | Elimination rule model |
| `Modules/Mk/Models/ConsolidationRun.php` | Run result model |
| `Modules/Mk/Services/ConsolidationService.php` | Core consolidation engine |
| `Modules/Mk/Services/IntercompanyDetector.php` | Auto-detect intercompany transactions |
| `Modules/Mk/Services/EliminationEngine.php` | Apply elimination entries |
| `Modules/Mk/Services/CurrencyTranslationService.php` | Multi-currency translation |
| `app/Jobs/RunConsolidationJob.php` | Queue job for async consolidation |

**Intercompany detection logic:**

```php
class IntercompanyDetector
{
    /**
     * Detect intercompany transactions between group members.
     *
     * Strategy: For each pair of companies in the group,
     * check if Company A is a customer of Company B (and vice versa)
     * by matching tax_identification_number (EDB) in customer/supplier records.
     */
    public function detect(ConsolidationGroup $group): array
    {
        $members = $group->members()->with('company')->get();
        $pairs = [];

        foreach ($members as $a) {
            foreach ($members as $b) {
                if ($a->company_id >= $b->company_id) continue;

                // Check if A's company appears as a customer in B's records
                $aAsCustomerInB = Customer::where('company_id', $b->company_id)
                    ->where('tax_id', $a->company->tax_id)
                    ->first();

                if ($aAsCustomerInB) {
                    $pairs[] = [
                        'company_a' => $a->company_id,
                        'company_b' => $b->company_id,
                        'type' => 'ar_ap',
                        'a_account' => '1200', // AR in A
                        'b_account' => '2200', // AP in B
                    ];
                }
            }
        }

        return $pairs;
    }
}
```

**Elimination engine:**

```php
class EliminationEngine
{
    public function eliminate(ConsolidationRun $run, array $memberData, array $rules): array
    {
        $eliminations = [];

        foreach ($rules as $rule) {
            // Get balances from both companies
            $balanceA = $this->getAccountBalance($memberData, $rule->company_a_id, $rule->account_a_code);
            $balanceB = $this->getAccountBalance($memberData, $rule->company_b_id, $rule->account_b_code);

            // Eliminate the lesser of the two (in case of discrepancy)
            $eliminationAmount = min(abs($balanceA), abs($balanceB));

            if ($eliminationAmount > 0) {
                $eliminations[] = [
                    'type' => $rule->elimination_type,
                    'company_a' => $rule->company_a_id,
                    'company_b' => $rule->company_b_id,
                    'amount' => $eliminationAmount,
                    'debit_account' => $rule->account_b_code,  // Eliminate payable
                    'credit_account' => $rule->account_a_code, // Eliminate receivable
                    'discrepancy' => abs($balanceA) - abs($balanceB),
                ];
            }
        }

        return $eliminations;
    }
}
```

**API Endpoints:**

```
-- Groups
GET     /api/v1/consolidation/groups              -- List groups
POST    /api/v1/consolidation/groups              -- Create group
PUT     /api/v1/consolidation/groups/{id}         -- Update
DELETE  /api/v1/consolidation/groups/{id}         -- Delete
POST    /api/v1/consolidation/groups/{id}/members -- Add member
DELETE  /api/v1/consolidation/groups/{id}/members/{memberId} -- Remove member

-- Rules
GET     /api/v1/consolidation/groups/{id}/rules   -- List intercompany rules
POST    /api/v1/consolidation/groups/{id}/rules   -- Add rule
POST    /api/v1/consolidation/groups/{id}/detect  -- Auto-detect intercompany

-- Run consolidation
POST    /api/v1/consolidation/groups/{id}/run     -- Run consolidation
GET     /api/v1/consolidation/runs/{runId}        -- Get run results
GET     /api/v1/consolidation/runs/{runId}/export -- Export as PDF/CSV
```

### Frontend

**New files:**

| File | Purpose |
|------|---------|
| `resources/scripts/partner/views/consolidation/Groups.vue` | Consolidation group management |
| `resources/scripts/partner/views/consolidation/GroupDetail.vue` | Members, rules, run history |
| `resources/scripts/partner/views/consolidation/RunResult.vue` | Consolidated statements + eliminations |
| `resources/scripts/partner/views/consolidation/components/MemberSelector.vue` | Add companies to group |
| `resources/scripts/partner/views/consolidation/components/RuleEditor.vue` | Edit elimination rules |
| `resources/scripts/partner/views/consolidation/components/EliminationSummary.vue` | Show what was eliminated |

**Tier restriction:** Max tier or partner portfolio only. Enforced via `CheckSubscriptionTier` middleware.

### Test Plan

1. Unit: `EliminationEngineTest` -- AR/AP elimination, revenue/expense elimination, discrepancy handling
2. Unit: `IntercompanyDetectorTest` -- detect via tax ID matching
3. Feature: `ConsolidationApiTest` -- group CRUD, run consolidation, verify totals
4. Feature: `CurrencyTranslationTest` -- multi-currency scenarios

---

## P15-05: Batch Operations (Partner)

### Current State

- `BulkReportController` does multi-company reporting (trial balance, P&L, BS)
- `BulkReports.vue` provides UI for selecting companies and generating reports
- No batch operations for daily closings, VAT returns, or other workflows

### Design

```sql
-- Migration: 2026_xx_xx_000005_create_batch_operations_table.php

CREATE TABLE batch_operations (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id       BIGINT UNSIGNED NOT NULL,

    operation_type   ENUM(
        'daily_close',
        'vat_return_generate',
        'trial_balance_export',
        'journal_export',
        'period_lock',
        'snapshot_capture'
    ) NOT NULL,

    -- Scope
    company_ids      JSON NOT NULL,                    -- Array of company IDs
    parameters       JSON NULL,                        -- Operation-specific params
    -- e.g., {"date": "2026-03-07", "format": "csv"} for daily_close
    -- e.g., {"period": "2026-03", "vat_type": "ddv04"} for vat_return

    -- Progress
    status           ENUM('queued','processing','completed','failed','partial') NOT NULL DEFAULT 'queued',
    total_companies  INT UNSIGNED NOT NULL,
    completed_count  INT UNSIGNED NOT NULL DEFAULT 0,
    failed_count     INT UNSIGNED NOT NULL DEFAULT 0,

    -- Results per company
    results          JSON NULL,
    -- [{"company_id": 1, "status": "success", "message": "..."},
    --  {"company_id": 2, "status": "error", "message": "No transactions"}]

    -- Output
    output_file_path VARCHAR(500) NULL,                -- ZIP/CSV download path

    -- Timing
    started_at       DATETIME NULL,
    completed_at     DATETIME NULL,

    created_by       BIGINT UNSIGNED NOT NULL,
    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,

    INDEX idx_batch_partner (partner_id, status),
    CONSTRAINT fk_batch_partner FOREIGN KEY (partner_id) REFERENCES partners(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Backend

**New files:**

| File | Purpose |
|------|---------|
| `Modules/Mk/Http/Controllers/BatchOperationController.php` | Start, monitor, download results |
| `Modules/Mk/Models/BatchOperation.php` | Model |
| `Modules/Mk/Services/BatchOperationService.php` | Orchestrator |
| `app/Jobs/BatchDailyCloseJob.php` | Process daily close for one company |
| `app/Jobs/BatchVatReturnJob.php` | Generate VAT return for one company |
| `app/Jobs/BatchTrialBalanceExportJob.php` | Export trial balance for one company |
| `app/Jobs/BatchJournalExportJob.php` | Export journals for one company |
| `app/Jobs/BatchPeriodLockJob.php` | Lock period for one company |
| `app/Jobs/BatchOrchestratorJob.php` | Dispatch individual jobs and track progress |

**Orchestration pattern (fan-out + tracking):**

```php
class BatchOperationService
{
    public function startBatch(Partner $partner, string $type, array $companyIds, array $params): BatchOperation
    {
        $this->validateAccess($partner, $companyIds);

        $batch = BatchOperation::create([
            'partner_id' => $partner->id,
            'operation_type' => $type,
            'company_ids' => $companyIds,
            'parameters' => $params,
            'total_companies' => count($companyIds),
            'status' => 'queued',
            'created_by' => auth()->id(),
        ]);

        // Dispatch orchestrator on the 'background' queue
        BatchOrchestratorJob::dispatch($batch)->onQueue('background');

        return $batch;
    }
}

class BatchOrchestratorJob implements ShouldQueue
{
    public function handle()
    {
        $this->batch->update(['status' => 'processing', 'started_at' => now()]);

        $jobClass = match ($this->batch->operation_type) {
            'daily_close' => BatchDailyCloseJob::class,
            'vat_return_generate' => BatchVatReturnJob::class,
            'trial_balance_export' => BatchTrialBalanceExportJob::class,
            'journal_export' => BatchJournalExportJob::class,
            'period_lock' => BatchPeriodLockJob::class,
            'snapshot_capture' => CaptureSnapshotJob::class,
        };

        // Dispatch one job per company
        $laravelBatch = Bus::batch(
            collect($this->batch->company_ids)->map(fn($id) =>
                new $jobClass($this->batch->id, $id, $this->batch->parameters)
            )->toArray()
        )
        ->name("batch-{$this->batch->id}")
        ->onQueue('background')
        ->allowFailures()
        ->then(fn() => $this->batch->markCompleted())
        ->catch(fn() => $this->batch->markPartial())
        ->dispatch();

        $this->batch->update(['laravel_batch_id' => $laravelBatch->id]);
    }
}
```

**API Endpoints:**

```
POST    /api/v1/partner/batch                     -- Start batch operation
GET     /api/v1/partner/batch                     -- List recent operations
GET     /api/v1/partner/batch/{id}                -- Get status + progress
GET     /api/v1/partner/batch/{id}/download       -- Download results (ZIP/CSV)
DELETE  /api/v1/partner/batch/{id}                -- Cancel (if still queued)
```

### Frontend

**New files:**

| File | Purpose |
|------|---------|
| `resources/scripts/partner/views/batch/BatchOperations.vue` | Main batch operations page |
| `resources/scripts/partner/views/batch/components/BatchWizard.vue` | Step-by-step: select operation -> select companies -> confirm -> run |
| `resources/scripts/partner/views/batch/components/BatchProgress.vue` | Real-time progress tracker with per-company status |
| `resources/scripts/partner/views/batch/components/BatchHistory.vue` | Past operations with download links |

**Real-time progress:** Use polling (every 3 seconds) via `GET /api/v1/partner/batch/{id}`. WebSocket is overkill for this use case given the existing infrastructure.

**Modifications:**

| File | Change |
|------|--------|
| `resources/scripts/partner/partner-router.js` | Add batch operations route |
| `resources/scripts/partner/views/reports/BulkReports.vue` | Add "Batch Export" button that opens BatchWizard |

### Operations Detail

**1. Batch Daily Close:**
For each company: run daily closing entries via IfrsAdapter, verify GL balanced, log result.

**2. Batch VAT Return Generate:**
For each company: generate DDV-04 XML for specified period via VatXmlService, package into ZIP.

**3. Batch Trial Balance Export:**
For each company: generate trial balance via IfrsAdapter, export as CSV rows, combine into single file with company column.

**4. Batch Journal Export:**
For each company: export journal entries for date range, CSV format.

**5. Batch Period Lock:**
For each company: lock accounting period (prevent future journal entries before date).

### Test Plan

1. Unit: `BatchOperationServiceTest` -- batch creation, access validation
2. Feature: `BatchApiTest` -- start, poll, download
3. Feature: `BatchJobTest` -- individual job execution + error handling
4. Integration: 5+ companies, verify partial failure handling (3 succeed, 2 fail)

---

## P15-06: Custom Report Builder

### Industry Research (Zoho Books, QuickBooks)

- **Zoho Books**: Report Builder lets users select modules (invoices, expenses, payments), choose columns, apply filters, group by fields, and save as templates
- **QuickBooks**: 80+ pre-built reports with customization via filter bar (date range, accounts, customers, tags)
- **Key insight**: Keep it simple. Most accountants want to select accounts + time period + grouping, NOT a full SQL builder

### Database Schema

```sql
-- Migration: 2026_xx_xx_000006_create_custom_reports_table.php

CREATE TABLE custom_report_templates (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id       BIGINT UNSIGNED NOT NULL,

    name             VARCHAR(255) NOT NULL,
    description      TEXT NULL,

    -- Report definition
    report_type      ENUM('account_based','transaction_based','comparison','budget_vs_actual') NOT NULL DEFAULT 'account_based',

    -- Account selection
    account_filter   JSON NOT NULL,
    -- {"mode": "range", "from": "1000", "to": "1999"}
    -- {"mode": "specific", "codes": ["1200", "1210", "1220"]}
    -- {"mode": "category", "categories": ["assets", "liabilities"]}
    -- {"mode": "all"}

    -- Columns
    columns          JSON NOT NULL,
    -- ["account_code", "account_name", "opening_balance", "debit", "credit",
    --  "closing_balance", "budget", "variance", "variance_pct"]

    -- Grouping
    group_by         ENUM('none','month','quarter','year','account_category','cost_center','department') NOT NULL DEFAULT 'none',

    -- Period
    period_type      ENUM('custom','current_month','current_quarter','current_year',
                          'last_month','last_quarter','last_year','ytd','trailing_12') NOT NULL DEFAULT 'custom',
    custom_from      DATE NULL,
    custom_to        DATE NULL,

    -- Comparison
    compare_with     ENUM('none','previous_period','previous_year','budget') NOT NULL DEFAULT 'none',

    -- Formatting
    show_zero_balances BOOLEAN NOT NULL DEFAULT FALSE,
    show_subtotals     BOOLEAN NOT NULL DEFAULT TRUE,
    decimal_places     TINYINT UNSIGNED NOT NULL DEFAULT 2,

    -- Scheduling
    schedule_enabled   BOOLEAN NOT NULL DEFAULT FALSE,
    schedule_frequency ENUM('daily','weekly','monthly') NULL,
    schedule_day       TINYINT UNSIGNED NULL,           -- Day of week/month
    schedule_recipients JSON NULL,                      -- Email addresses
    last_scheduled_at  DATETIME NULL,

    -- Access
    is_shared        BOOLEAN NOT NULL DEFAULT FALSE,     -- Shared with all company users
    created_by       BIGINT UNSIGNED NOT NULL,

    created_at       TIMESTAMP NULL,
    updated_at       TIMESTAMP NULL,

    INDEX idx_report_company (company_id),
    CONSTRAINT fk_report_company FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    CONSTRAINT fk_report_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Backend

**New files:**

| File | Purpose |
|------|---------|
| `Modules/Mk/Http/Controllers/CustomReportController.php` | Template CRUD, generate, export, schedule |
| `Modules/Mk/Models/CustomReportTemplate.php` | Model |
| `Modules/Mk/Services/CustomReportService.php` | Generate report from template definition |
| `Modules/Mk/Services/ReportDataBuilder.php` | Build data matrix from IFRS accounts |
| `app/Console/Commands/SendScheduledReports.php` | Artisan command for scheduled delivery |
| `app/Mail/ScheduledReport.php` | Mailable with PDF/CSV attachment |

**Report generation logic:**

```php
class CustomReportService
{
    public function generate(CustomReportTemplate $template, ?string $fromDate = null, ?string $toDate = null): array
    {
        $company = $template->company;
        [$from, $to] = $this->resolvePeriod($template, $fromDate, $toDate);

        // 1. Get accounts matching filter
        $accounts = $this->filterAccounts($company, $template->account_filter);

        // 2. Get balances for each account
        $data = [];
        foreach ($accounts as $account) {
            $row = $this->dataBuilder->buildRow($company, $account, $from, $to, $template->columns);

            // Skip zero balances if configured
            if (!$template->show_zero_balances && $this->isZeroRow($row)) continue;

            $data[] = $row;
        }

        // 3. Apply grouping
        if ($template->group_by !== 'none') {
            $data = $this->applyGrouping($data, $template->group_by, $company, $from, $to);
        }

        // 4. Add comparison columns if configured
        if ($template->compare_with !== 'none') {
            $data = $this->addComparison($data, $template, $company);
        }

        // 5. Calculate subtotals
        if ($template->show_subtotals) {
            $data = $this->addSubtotals($data, $template->columns);
        }

        return [
            'template_name' => $template->name,
            'period' => ['from' => $from, 'to' => $to],
            'columns' => $template->columns,
            'data' => $data,
            'totals' => $this->calculateTotals($data, $template->columns),
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
```

**API Endpoints:**

```
GET     /api/v1/custom-reports                    -- List saved templates
POST    /api/v1/custom-reports                    -- Create template
GET     /api/v1/custom-reports/{id}               -- Get template definition
PUT     /api/v1/custom-reports/{id}               -- Update template
DELETE  /api/v1/custom-reports/{id}               -- Delete template
POST    /api/v1/custom-reports/{id}/generate      -- Generate report data
POST    /api/v1/custom-reports/{id}/export        -- Export as PDF/CSV/XLSX
POST    /api/v1/custom-reports/preview            -- Preview without saving
PUT     /api/v1/custom-reports/{id}/schedule      -- Enable/update schedule
GET     /api/v1/custom-reports/available-columns   -- List available column types
GET     /api/v1/custom-reports/available-accounts  -- List accounts for filter picker
```

### Frontend

**New files:**

| File | Purpose |
|------|---------|
| `resources/scripts/admin/views/reports/CustomReportBuilder.vue` | Main builder page |
| `resources/scripts/admin/views/reports/CustomReportList.vue` | List saved reports |
| `resources/scripts/admin/views/reports/CustomReportView.vue` | View generated report |
| `resources/scripts/admin/views/reports/components/AccountSelector.vue` | Account range/specific picker |
| `resources/scripts/admin/views/reports/components/ColumnPicker.vue` | Drag-and-drop column selection |
| `resources/scripts/admin/views/reports/components/PeriodSelector.vue` | Period type + custom date range |
| `resources/scripts/admin/views/reports/components/GroupBySelector.vue` | Grouping option |
| `resources/scripts/admin/views/reports/components/ReportPreview.vue` | Live preview of report |
| `resources/scripts/admin/views/reports/components/ScheduleConfig.vue` | Email schedule setup |
| `resources/scripts/admin/stores/custom-reports.js` | Pinia store |

**Builder UX (step-by-step, NOT drag-and-drop IDE):**

1. **Name & Type**: Give the report a name, select type (account-based, comparison, etc.)
2. **Select Accounts**: Choose by range (1000-1999), by specific codes, or by category (all assets)
3. **Choose Columns**: Checkboxes: Account Code, Account Name, Opening Balance, Debit, Credit, Closing Balance, Budget, Variance, Variance %
4. **Period**: Select preset (Current Month, YTD, etc.) or custom date range
5. **Grouping**: None, by Month, by Quarter, by Account Category
6. **Comparison**: None, vs Previous Period, vs Previous Year, vs Budget
7. **Preview**: Show live preview with real data
8. **Save**: Save template + optionally set up email schedule

### Test Plan

1. Unit: `CustomReportServiceTest` -- account filtering, period resolution, grouping, comparison
2. Unit: `ReportDataBuilderTest` -- build row data from IFRS accounts
3. Feature: `CustomReportApiTest` -- template CRUD, generate, export
4. Feature: `ScheduledReportTest` -- verify email delivery with attachment
5. Browser: Cypress -- build a report step-by-step, preview, save, generate

---

## i18n Keys

All features need translation in 4 locales: MK (Macedonian), EN (English), TR (Turkish), SQ (Albanian).

### Travel Expense Management

```json
{
  "travel": {
    "title": {
      "mk": "Патни налози",
      "en": "Travel Orders",
      "tr": "Seyahat Emirleri",
      "sq": "Urdhrat e Udhetimit"
    },
    "create": {
      "mk": "Нов патен налог",
      "en": "New Travel Order",
      "tr": "Yeni Seyahat Emri",
      "sq": "Urdher i Ri Udhetimi"
    },
    "travel_number": {
      "mk": "Број на налог",
      "en": "Order Number",
      "tr": "Emir Numarasi",
      "sq": "Numri i Urdhrit"
    },
    "purpose": {
      "mk": "Цел на патување",
      "en": "Travel Purpose",
      "tr": "Seyahat Amaci",
      "sq": "Qellimi i Udhetimit"
    },
    "domestic": {
      "mk": "Домашно",
      "en": "Domestic",
      "tr": "Yurt Ici",
      "sq": "Brendshme"
    },
    "foreign": {
      "mk": "Странство",
      "en": "Foreign",
      "tr": "Yurt Disi",
      "sq": "Jashte"
    },
    "departure": {
      "mk": "Поаѓање",
      "en": "Departure",
      "tr": "Kalkis",
      "sq": "Nisja"
    },
    "return": {
      "mk": "Враќање",
      "en": "Return",
      "tr": "Donus",
      "sq": "Kthimi"
    },
    "per_diem": {
      "mk": "Дневница",
      "en": "Per Diem",
      "tr": "Gunluk Harclik",
      "sq": "Dieta Ditore"
    },
    "per_diem_domestic": {
      "mk": "Дневница за домашно патување",
      "en": "Domestic Per Diem",
      "tr": "Yurt Ici Gunluk Harclik",
      "sq": "Dieta Ditore Brendshme"
    },
    "per_diem_foreign": {
      "mk": "Дневница за странство",
      "en": "Foreign Per Diem",
      "tr": "Yurt Disi Gunluk Harclik",
      "sq": "Dieta Ditore Jashte"
    },
    "mileage": {
      "mk": "Километража",
      "en": "Mileage",
      "tr": "Kilometre",
      "sq": "Kilometrazhi"
    },
    "advance": {
      "mk": "Аванс",
      "en": "Advance",
      "tr": "Avans",
      "sq": "Paradhenie"
    },
    "settlement": {
      "mk": "Пресметка",
      "en": "Settlement",
      "tr": "Hesap Kapama",
      "sq": "Llogaritja"
    },
    "reimbursement": {
      "mk": "Враќање на средства",
      "en": "Reimbursement",
      "tr": "Geri Odeme",
      "sq": "Rimbursim"
    },
    "segment": {
      "mk": "Делница",
      "en": "Segment",
      "tr": "Bolum",
      "sq": "Segmenti"
    },
    "from_city": {
      "mk": "Од град",
      "en": "From City",
      "tr": "Sehirden",
      "sq": "Nga Qyteti"
    },
    "to_city": {
      "mk": "До град",
      "en": "To City",
      "tr": "Sehire",
      "sq": "Deri Qyteti"
    },
    "transport_type": {
      "mk": "Вид на превоз",
      "en": "Transport Type",
      "tr": "Ulasim Turu",
      "sq": "Lloji i Transportit"
    },
    "receipt_scan": {
      "mk": "Скенирај сметка",
      "en": "Scan Receipt",
      "tr": "Fis Tara",
      "sq": "Skano Faturen"
    },
    "accommodation_provided": {
      "mk": "Обезбедено сместување",
      "en": "Accommodation Provided",
      "tr": "Konaklama Saglanmis",
      "sq": "Akomodimi i Siguruar"
    },
    "meals_provided": {
      "mk": "Обезбедена исхрана",
      "en": "Meals Provided",
      "tr": "Yemek Saglanmis",
      "sq": "Ushqimi i Siguruar"
    },
    "status_draft": {
      "mk": "Нацрт",
      "en": "Draft",
      "tr": "Taslak",
      "sq": "Draft"
    },
    "status_pending": {
      "mk": "Чека одобрение",
      "en": "Pending Approval",
      "tr": "Onay Bekliyor",
      "sq": "Ne Pritje te Miratimit"
    },
    "status_approved": {
      "mk": "Одобрен",
      "en": "Approved",
      "tr": "Onaylandi",
      "sq": "I Miratuar"
    },
    "status_settled": {
      "mk": "Затворен",
      "en": "Settled",
      "tr": "Kapatildi",
      "sq": "I Mbyllur"
    },
    "status_rejected": {
      "mk": "Одбиен",
      "en": "Rejected",
      "tr": "Reddedildi",
      "sq": "I Refuzuar"
    },
    "generate_pdf": {
      "mk": "Генерирај PDF",
      "en": "Generate PDF",
      "tr": "PDF Olustur",
      "sq": "Gjenero PDF"
    }
  }
}
```

### Collections / Reminders

```json
{
  "collections": {
    "title": {
      "mk": "Наплата и потсетници",
      "en": "Collections & Reminders",
      "tr": "Tahsilat ve Hatirlatmalar",
      "sq": "Arketim dhe Kujtesa"
    },
    "reminder_templates": {
      "mk": "Шаблони за потсетници",
      "en": "Reminder Templates",
      "tr": "Hatirlatma Sablonlari",
      "sq": "Shabllone Kujtesash"
    },
    "schedule": {
      "mk": "Распоред на испраќање",
      "en": "Send Schedule",
      "tr": "Gonderim Programi",
      "sq": "Orari i Dergimit"
    },
    "send_reminder": {
      "mk": "Испрати потсетник",
      "en": "Send Reminder",
      "tr": "Hatirlatma Gonder",
      "sq": "Dergo Kujtese"
    },
    "escalation_friendly": {
      "mk": "Пријателски",
      "en": "Friendly",
      "tr": "Dostca",
      "sq": "Miqesor"
    },
    "escalation_firm": {
      "mk": "Решителен",
      "en": "Firm",
      "tr": "Kararli",
      "sq": "I Vendosur"
    },
    "escalation_final": {
      "mk": "Последно предупредување",
      "en": "Final Notice",
      "tr": "Son Uyari",
      "sq": "Njoftim i Fundit"
    },
    "escalation_legal": {
      "mk": "Правна постапка",
      "en": "Legal Action",
      "tr": "Hukuki Islem",
      "sq": "Veprim Ligjor"
    },
    "days_overdue": {
      "mk": "Денови задоцнување",
      "en": "Days Overdue",
      "tr": "Gecikme Gunu",
      "sq": "Dite Vonese"
    },
    "collection_case": {
      "mk": "Случај за наплата",
      "en": "Collection Case",
      "tr": "Tahsilat Dosyasi",
      "sq": "Rast Arketimi"
    },
    "case_open": {
      "mk": "Отворен",
      "en": "Open",
      "tr": "Acik",
      "sq": "I Hapur"
    },
    "case_resolved": {
      "mk": "Решен",
      "en": "Resolved",
      "tr": "Cozuldu",
      "sq": "I Zgjidhur"
    },
    "case_written_off": {
      "mk": "Отпишан",
      "en": "Written Off",
      "tr": "Silindi",
      "sq": "I Fshire"
    },
    "reminder_sent": {
      "mk": "Потсетник испратен",
      "en": "Reminder Sent",
      "tr": "Hatirlatma Gonderildi",
      "sq": "Kujtesa u Dergua"
    },
    "reminder_opened": {
      "mk": "Потсетник отворен",
      "en": "Reminder Opened",
      "tr": "Hatirlatma Acildi",
      "sq": "Kujtesa u Hap"
    },
    "paid_after_reminder": {
      "mk": "Платено по потсетник",
      "en": "Paid After Reminder",
      "tr": "Hatirlatmadan Sonra Odendi",
      "sq": "Paguar pas Kujteses"
    },
    "total_outstanding": {
      "mk": "Вкупно неплатено",
      "en": "Total Outstanding",
      "tr": "Toplam Odenmemis",
      "sq": "Totali i Papaguar"
    },
    "auto_reminders_enabled": {
      "mk": "Автоматски потсетници",
      "en": "Auto Reminders",
      "tr": "Otomatik Hatirlatmalar",
      "sq": "Kujtesa Automatike"
    }
  }
}
```

### BI Dashboards

```json
{
  "bi": {
    "title": {
      "mk": "Бизнис интелигенција",
      "en": "Business Intelligence",
      "tr": "Is Zekasi",
      "sq": "Inteligjence Biznesi"
    },
    "financial_ratios": {
      "mk": "Финансиски показатели",
      "en": "Financial Ratios",
      "tr": "Finansal Oranlar",
      "sq": "Raportet Financiare"
    },
    "current_ratio": {
      "mk": "Коефициент на тековна ликвидност",
      "en": "Current Ratio",
      "tr": "Cari Oran",
      "sq": "Raporti Aktual"
    },
    "quick_ratio": {
      "mk": "Брз коефициент на ликвидност",
      "en": "Quick Ratio",
      "tr": "Asit Test Orani",
      "sq": "Raporti i Shpejte"
    },
    "debt_to_equity": {
      "mk": "Однос долг/капитал",
      "en": "Debt to Equity",
      "tr": "Borc/Ozkaynak Orani",
      "sq": "Borxh ndaj Kapitalit"
    },
    "return_on_equity": {
      "mk": "Принос на капитал (ROE)",
      "en": "Return on Equity",
      "tr": "Ozkaynak Karliligi",
      "sq": "Kthimi ne Kapital"
    },
    "return_on_assets": {
      "mk": "Принос на средства (ROA)",
      "en": "Return on Assets",
      "tr": "Aktif Karliligi",
      "sq": "Kthimi ne Aktive"
    },
    "profit_margin": {
      "mk": "Маргина на профит",
      "en": "Profit Margin",
      "tr": "Kar Marji",
      "sq": "Marzhi i Fitimit"
    },
    "revenue_trend": {
      "mk": "Тренд на приходи",
      "en": "Revenue Trend",
      "tr": "Gelir Trendi",
      "sq": "Trendi i te Ardhurave"
    },
    "expense_breakdown": {
      "mk": "Распределба на трошоци",
      "en": "Expense Breakdown",
      "tr": "Gider Dagilimi",
      "sq": "Ndarja e Shpenzimeve"
    },
    "cash_flow_trend": {
      "mk": "Тренд на готовински тек",
      "en": "Cash Flow Trend",
      "tr": "Nakit Akis Trendi",
      "sq": "Trendi i Fluksit te Parase"
    },
    "aging_receivables": {
      "mk": "Старосна структура на побарувања",
      "en": "Aging Receivables",
      "tr": "Alacak Vade Analizi",
      "sq": "Moshat e Arketueshme"
    },
    "aging_payables": {
      "mk": "Старосна структура на обврски",
      "en": "Aging Payables",
      "tr": "Borc Vade Analizi",
      "sq": "Moshat e Pagueshme"
    },
    "solvency_score": {
      "mk": "Оцена на солвентност",
      "en": "Solvency Score",
      "tr": "Odeme Gucu Skoru",
      "sq": "Rezultati i Aftesise Paguese"
    },
    "altman_z_score": {
      "mk": "Алтман Z-скор",
      "en": "Altman Z-Score",
      "tr": "Altman Z-Skoru",
      "sq": "Altman Z-Skori"
    },
    "zone_safe": {
      "mk": "Безбедна зона",
      "en": "Safe Zone",
      "tr": "Guvenli Bolge",
      "sq": "Zona e Sigurt"
    },
    "zone_grey": {
      "mk": "Сива зона",
      "en": "Grey Zone",
      "tr": "Gri Bolge",
      "sq": "Zona Gri"
    },
    "zone_distress": {
      "mk": "Зона на ризик",
      "en": "Distress Zone",
      "tr": "Risk Bolgesi",
      "sq": "Zona e Rrezikut"
    },
    "ai_query_placeholder": {
      "mk": "Прашајте за вашите финансии...",
      "en": "Ask about your finances...",
      "tr": "Finanslariniz hakkinda sorun...",
      "sq": "Pyetni per financat tuaja..."
    },
    "dashboard_template": {
      "mk": "Шаблон за табла",
      "en": "Dashboard Template",
      "tr": "Kontrol Paneli Sablonu",
      "sq": "Shablloni i Paneles"
    }
  }
}
```

### Consolidation

```json
{
  "consolidation": {
    "title": {
      "mk": "Консолидација",
      "en": "Consolidation",
      "tr": "Konsolidasyon",
      "sq": "Konsolidimi"
    },
    "group": {
      "mk": "Група за консолидација",
      "en": "Consolidation Group",
      "tr": "Konsolidasyon Grubu",
      "sq": "Grupi i Konsolidimit"
    },
    "parent_company": {
      "mk": "Матична компанија",
      "en": "Parent Company",
      "tr": "Ana Sirket",
      "sq": "Kompania Meme"
    },
    "member_company": {
      "mk": "Членка компанија",
      "en": "Member Company",
      "tr": "Uye Sirket",
      "sq": "Kompania Antare"
    },
    "ownership_pct": {
      "mk": "Процент на сопственост",
      "en": "Ownership %",
      "tr": "Sahiplik %",
      "sq": "Perqindja e Pronesise"
    },
    "elimination": {
      "mk": "Елиминација",
      "en": "Elimination",
      "tr": "Eliminasyon",
      "sq": "Eliminimi"
    },
    "intercompany": {
      "mk": "Интер-компаниски",
      "en": "Intercompany",
      "tr": "Sirketler Arasi",
      "sq": "Ndermjet Kompanive"
    },
    "run_consolidation": {
      "mk": "Изврши консолидација",
      "en": "Run Consolidation",
      "tr": "Konsolidasyonu Calistir",
      "sq": "Ekzekuto Konsolidimin"
    },
    "auto_detect": {
      "mk": "Автоматско препознавање",
      "en": "Auto Detect",
      "tr": "Otomatik Tespit",
      "sq": "Zbulim Automatik"
    },
    "translation_adjustment": {
      "mk": "Курсна разлика",
      "en": "Translation Adjustment",
      "tr": "Ceviri Duzeltmesi",
      "sq": "Rregullimi i Perkthimit"
    }
  }
}
```

### Batch Operations

```json
{
  "batch": {
    "title": {
      "mk": "Збирни операции",
      "en": "Batch Operations",
      "tr": "Toplu Islemler",
      "sq": "Operacione ne Grup"
    },
    "daily_close": {
      "mk": "Дневно затворање",
      "en": "Daily Close",
      "tr": "Gunluk Kapanma",
      "sq": "Mbyllja Ditore"
    },
    "vat_return": {
      "mk": "ДДВ пријава",
      "en": "VAT Return",
      "tr": "KDV Beyannamesi",
      "sq": "Kthimi i TVSH-se"
    },
    "trial_balance_export": {
      "mk": "Извоз на пробен биланс",
      "en": "Trial Balance Export",
      "tr": "Mizan Ihracati",
      "sq": "Eksporti i Bilancit Provor"
    },
    "journal_export": {
      "mk": "Извоз на дневник",
      "en": "Journal Export",
      "tr": "Yevmiye Ihracati",
      "sq": "Eksporti i Ditarit"
    },
    "period_lock": {
      "mk": "Заклучување на период",
      "en": "Period Lock",
      "tr": "Donem Kilitleme",
      "sq": "Kyci Periudhen"
    },
    "select_companies": {
      "mk": "Изберете компании",
      "en": "Select Companies",
      "tr": "Sirketleri Secin",
      "sq": "Zgjidhni Kompanitie"
    },
    "start_batch": {
      "mk": "Започни операција",
      "en": "Start Batch",
      "tr": "Toplu Islemi Baslat",
      "sq": "Fillo Operacionin"
    },
    "progress": {
      "mk": "Напредок",
      "en": "Progress",
      "tr": "Ilerleme",
      "sq": "Progresi"
    },
    "completed_of": {
      "mk": "завршени од",
      "en": "completed of",
      "tr": "tamamlanan",
      "sq": "perfunduar nga"
    },
    "download_results": {
      "mk": "Преземи резултати",
      "en": "Download Results",
      "tr": "Sonuclari Indir",
      "sq": "Shkarko Rezultatet"
    }
  }
}
```

### Custom Reports

```json
{
  "custom_reports": {
    "title": {
      "mk": "Прилагодени извештаи",
      "en": "Custom Reports",
      "tr": "Ozel Raporlar",
      "sq": "Raporte te Personalizuara"
    },
    "create_report": {
      "mk": "Нов извештај",
      "en": "New Report",
      "tr": "Yeni Rapor",
      "sq": "Raport i Ri"
    },
    "select_accounts": {
      "mk": "Изберете сметки",
      "en": "Select Accounts",
      "tr": "Hesaplari Secin",
      "sq": "Zgjidhni Llogarite"
    },
    "account_range": {
      "mk": "Опсег на сметки",
      "en": "Account Range",
      "tr": "Hesap Araligi",
      "sq": "Gama e Llogarive"
    },
    "choose_columns": {
      "mk": "Изберете колони",
      "en": "Choose Columns",
      "tr": "Sutunlari Secin",
      "sq": "Zgjidhni Kolonat"
    },
    "opening_balance": {
      "mk": "Почетно салдо",
      "en": "Opening Balance",
      "tr": "Acilis Bakiyesi",
      "sq": "Bilanci Fillestar"
    },
    "closing_balance": {
      "mk": "Крајно салдо",
      "en": "Closing Balance",
      "tr": "Kapanis Bakiyesi",
      "sq": "Bilanci Perfundimtar"
    },
    "variance": {
      "mk": "Отстапување",
      "en": "Variance",
      "tr": "Sapma",
      "sq": "Devijimi"
    },
    "group_by_month": {
      "mk": "Групирај по месец",
      "en": "Group by Month",
      "tr": "Aya Gore Grupla",
      "sq": "Grupo sipas Muajit"
    },
    "group_by_quarter": {
      "mk": "Групирај по квартал",
      "en": "Group by Quarter",
      "tr": "Ceyrek Donemden Grupla",
      "sq": "Grupo sipas Tremujorit"
    },
    "compare_previous_year": {
      "mk": "Споредба со претходна година",
      "en": "Compare with Previous Year",
      "tr": "Onceki Yil ile Karsilastir",
      "sq": "Krahaso me Vitin e Kaluar"
    },
    "compare_budget": {
      "mk": "Споредба со буџет",
      "en": "Compare with Budget",
      "tr": "Butce ile Karsilastir",
      "sq": "Krahaso me Buxhetin"
    },
    "save_template": {
      "mk": "Зачувај шаблон",
      "en": "Save Template",
      "tr": "Sablonu Kaydet",
      "sq": "Ruaj Shabllonin"
    },
    "schedule_delivery": {
      "mk": "Закажи испраќање",
      "en": "Schedule Delivery",
      "tr": "Gonderimi Planla",
      "sq": "Planifiko Dergimin"
    },
    "preview_report": {
      "mk": "Преглед на извештај",
      "en": "Preview Report",
      "tr": "Rapor Onizleme",
      "sq": "Parapamja e Raportit"
    },
    "no_data": {
      "mk": "Нема податоци за избраниот период",
      "en": "No data for selected period",
      "tr": "Secilen donem icin veri yok",
      "sq": "Nuk ka te dhena per periudhen e zgjedhur"
    }
  }
}
```

---

## Implementation Order & Dependencies

### Recommended order (based on effort, value, and dependencies)

```
Phase 15.1 (Sprint 1-2, ~2 weeks):
  P15-02: Collections / Payment Reminders     <- Highest revenue impact, uses existing overdue infra

Phase 15.2 (Sprint 3-4, ~2 weeks):
  P15-03: BI Dashboards                       <- Builds on existing AI + IFRS infrastructure

Phase 15.3 (Sprint 5-7, ~3 weeks):
  P15-01: Travel Expense Management            <- Largest feature, self-contained

Phase 15.4 (Sprint 8, ~1 week):
  P15-05: Batch Operations (Partner)           <- Extends existing bulk reporting

Phase 15.5 (Sprint 9-10, ~2 weeks):
  P15-06: Custom Report Builder                <- Depends on BI ratios infrastructure

Phase 15.6 (Sprint 11, ~1 week):
  P15-04: Financial Consolidation (Enhanced)   <- Depends on batch + reporting
```

### Dependency Graph

```
P15-02 (Collections) ──────────────────────────────── Standalone
  |
P15-03 (BI Dashboards) ────────────────────────────── Standalone
  |                                                      |
P15-01 (Travel Expenses) ──────────────────────────── Standalone
  |                                                      |
P15-05 (Batch Operations) ─── extends BulkReports ──── |
  |                                                      |
P15-06 (Custom Reports) ──── uses FinancialRatioSvc ── depends on P15-03
  |                                                      |
P15-04 (Consolidation) ──── extends BulkReportingSvc ── depends on P15-05
```

### Subscription Tier Mapping

| Feature | Free | Starter | Standard | Business | Max | Partner |
|---------|------|---------|----------|----------|-----|---------|
| P15-01 Travel Orders | - | - | 10/mo | 50/mo | Unlimited | All clients |
| P15-02 Reminders | - | 10/mo | 50/mo | Unlimited | Unlimited | All clients |
| P15-03 BI Dashboard | - | - | Basic (3 widgets) | Full | Full | Full |
| P15-04 Consolidation | - | - | - | - | Yes | Yes |
| P15-05 Batch Ops | - | - | - | - | - | Yes |
| P15-06 Custom Reports | - | - | 3 saved | 10 saved | Unlimited | Unlimited |

### New config/subscriptions.php additions

```php
// Add to each tier's 'features' array:
'travel_management' => false,       // true for Standard+
'payment_reminders' => false,       // true for Starter+
'bi_dashboard' => false,            // 'basic' for Standard, 'full' for Business+
'consolidation' => false,           // true for Max + partner
'batch_operations' => false,        // true for partner only
'custom_reports' => false,          // true for Standard+

// Add to each tier's 'limits' array:
'travel_orders_per_month' => 0,     // 0/10/50/null per tier
'reminders_per_month' => 0,         // 0/10/50/null per tier
'bi_widgets' => 0,                  // 0/3/null per tier
'custom_reports_saved' => 0,        // 0/3/10/null per tier
```

---

## Research Sources

- [Knigoprima - Per-diem rates for business travel](https://knigoprima.com.mk/2019/11/06/%D0%9D%D0%B0%D0%B4%D0%BE%D0%BC%D0%B5%D1%81%D1%82%D0%BE%D1%82%D0%B8-%D0%B7%D0%B0-%D0%B4%D0%BD%D0%B5%D0%B2%D0%BD%D0%B8%D1%86%D0%B8-%D0%B7%D0%B0-%D1%81%D0%BB%D1%83%D0%B6%D0%B1%D0%B5%D0%BD%D0%BE-%D0%BF/)
- [Nova Konsalting - Foreign per-diem rates](https://novakonsalting.mk/b/187/dnevnici-za-sluzbeni-patuvanja-vo-stranstvo)
- [Euro Konsalt Plus - Domestic per-diem rates 2013-2025](https://eurokonsaltplus.com.mk/%D0%B4%D0%BD%D0%B5%D0%B2%D0%BD%D0%B8%D1%86%D0%B8-%D0%B7%D0%B0-%D1%81%D0%BB%D1%83%D0%B6%D0%B1%D0%B5%D0%BD%D0%B8-%D0%BF%D0%B0%D1%82%D1%83%D0%B2%D0%B0%D1%9A%D0%B0-%D0%B2%D0%BE-%D0%B7%D0%B5%D0%BC%D1%98/)
- [MSFI Consulting - Foreign per-diem maximum amounts](https://msfi.com.mk/najvisoki-iznosi-na-dnevnici-za-sluzhbeni-patuvanja-vo-stranstvo/)
- [Expensify vs Concur Comparison](https://use.expensify.com/resource-center/guides/expensify-vs-concur-comparison)
- [Ramp AI Expense Management](https://ramp.com/blog/ai-expense-management)
- [Zero-Touch Expense Reporting 2026](https://expenseanywhere.com/zero-touch-expense-reporting-ai-automated-expense-management-2026/)
- [Emburse AI-Powered Expense Compliance](https://www.cpapracticeadvisor.com/2026/02/17/emburse-releases-ai-powered-expense-compliance-solution/178189/)
- [Chaser - Automated Payment Reminders](https://www.chaserhq.com/blog/automated-payment-reminders)
- [GoCardless - What is Dunning](https://gocardless.com/en-us/guides/posts/what-is-dunning/)
- [Fathom HQ Features](https://www.fathomhq.com/features)
- [Jirav vs Fathom Comparison](https://www.jirav.com/blog/jirav-vs-fathom-deep-fpa-vs-financial-reporting)
- [Sage Intacct Consolidation](https://www.sage.com/en-au/sage-business-cloud/intacct/additional-financial-management-modules/consolidation-accounting/)
- [Xero Consolidation Feature Request](https://productideas.xero.com/forums/967133-reports-tax/suggestions/44960413-reporting-ability-to-consolidate-multiple-xero-o)
- [Joiin - Consolidation for Xero](https://www.joiin.co/connect-with-xero/)
- [Zoho Books Custom Reports](https://www.zoho.com/us/books/accounting-software/customization/)
- [Xero Practice Manager + Karbon](https://karbonhq.com/integrations/xpm/)
- [Top Accounting Practice Management Software 2026](https://getuku.com/articles/uks-best-accounting-practice-management-software)
