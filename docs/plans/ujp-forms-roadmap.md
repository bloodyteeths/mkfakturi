# UJP Tax Forms Implementation Roadmap

## Executive Summary

Facturino currently generates 4 UJP-compliant XML forms (ДДВ-04, ДБ-ВП, МПИН, е-фактура UBL) plus 3 AOP-coded financial statements (Образец 36/37/38). This roadmap covers the **16 missing forms** needed for full Macedonian tax compliance, organized into 4 phases over ~12 weeks.

**Goal**: Accountants should be able to generate ALL mandatory UJP filings directly from Facturino, eliminating manual re-keying into etax.ujp.gov.mk.

---

## Architecture: Unified Form Generation Pattern

All new forms will follow the same pattern as existing `VatXmlService` and `CitXmlService`:

```
┌─────────────────────────────────────────────────┐
│  Controller (API endpoint)                       │
│  POST /api/v1/tax-forms/{form-code}/generate    │
│  GET  /api/v1/tax-forms/{form-code}/preview     │
├─────────────────────────────────────────────────┤
│  Form Service (XML/PDF generator)                │
│  - Collects data from IFRS/Invoice/Payroll       │
│  - Maps to official AOP codes                    │
│  - Generates XML for e-tax upload                │
│  - Generates PDF for print/archive               │
├─────────────────────────────────────────────────┤
│  Form Config (field definitions)                 │
│  config/ujp_forms/{form-code}.php               │
│  - AOP codes, field labels, formulas             │
│  - Validation rules                              │
│  - Legal references (Службен весник)             │
├─────────────────────────────────────────────────┤
│  Blade Template (PDF output)                     │
│  resources/views/app/pdf/tax-forms/             │
│  - Matches official UJP layout exactly           │
│  - Cyrillic StobiSans font                       │
│  - Purple/teal UJP color scheme                  │
├─────────────────────────────────────────────────┤
│  Vue UI (preview & download)                     │
│  resources/scripts/admin/views/tax-forms/       │
│  - Form parameter selection (year, period)       │
│  - Live preview with calculated values           │
│  - Download XML / PDF buttons                    │
│  - Filing status tracking                        │
└─────────────────────────────────────────────────┘
```

### Shared Infrastructure (build first):

1. **`TaxFormService`** base class — common header/footer (ЕДБ, company info, составувач/потписник sections)
2. **`config/ujp_forms/`** directory — form field definitions keyed by AOP code
3. **`tax_form_filings`** table — tracks which forms were generated/filed per company/period
4. **PDF template** — shared layout matching UJP's purple/teal design with StobiSans font
5. **Vue `TaxFormsDashboard.vue`** — unified tax forms hub with calendar view

### Database Migration:

```sql
CREATE TABLE tax_form_filings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_id INT UNSIGNED NOT NULL,
    form_code VARCHAR(20) NOT NULL,        -- 'DB', 'DDV-04', 'DD-I', etc.
    tax_year INT NOT NULL,
    tax_period VARCHAR(10) NULL,           -- '2025-01' for monthly, '2025-Q1' for quarterly, NULL for annual
    status ENUM('draft','generated','filed','accepted','rejected') DEFAULT 'draft',
    xml_path VARCHAR(500) NULL,
    pdf_path VARCHAR(500) NULL,
    filed_at TIMESTAMP NULL,
    filed_by INT UNSIGNED NULL,
    data JSON NULL,                        -- cached form field values
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_company_form (company_id, form_code, tax_year),
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Phase 1: Annual Profit Tax Package (Weeks 1-3)
*Deadline-driven: filed by Feb 28 / Mar 15 every year*

### 1.1 ДБ — Даночен биланс за оданочување на добивка
**Priority**: CRITICAL — every ДООЕЛ/ДОО files this annually
**Legal basis**: Службен весник бр. 6/2020, valid from 11.01.2020

**Structure** (from actual form — 70 AOP codes):
```
HEADER:
  - Посебен даночен статус (checkboxes: Заштитни друштва, ТИРЗ, Казнено-поправни)
  - Единствен даночен број (ЕДБ)
  - Скратен назив и адреса
  - Даночен период (од/до)
  - Телефон, е-пошта
  - Исправка на ДБ (checkbox + број)

SECTION I: Финансиски резултат во Биланс на успех (AOP 01)
  → Source: Income Statement net result (profit/loss before tax)

SECTION II: Непризнаени расходи (AOP 02 = sum of AOP 03-39)
  AOP 03: Расходи не поврзани со дејноста
  AOP 04: Надоместоци над утврден износ
  AOP 05: Трошоци на вработени (неутврдени)
  AOP 06: Исхрана и превоз над закон
  AOP 07: Хотелско сместување над 6000 ден/ден
  AOP 08: Ноќна исхрана над закон
  AOP 09: Надоместоци на органи на управување
  AOP 10: Доброволни пензиски придонеси над закон
  AOP 11: Премии за осигурување живот над закон
  AOP 12: Волонтери/јавни работи над закон
  AOP 13: Скриени исплати на добивки
  AOP 14: Кусоци (не од вонредни настани)
  AOP 15: Репрезентација (90%)
  AOP 16: Донации над 5% од вкупен приход
  AOP 17: Спонзорства над 3% од вкупен приход
  AOP 18: Донации во спорт (чл. 30-а)
  AOP 19: Камати по кредити (не за дејност)
  AOP 20: Осигурителни премии за управување/вработени
  AOP 21: Даноци по задршка (за трети лица)
  AOP 22: Казни, пенали, казнени камати
  AOP 23: Стипендии
  AOP 24: Кало, растур, крш
  AOP 25: Траен отпис на побарувања
  AOP 26: Деловна успешност над придонеси
  AOP 27: Практикантска работа над закон
  AOP 28: Практична обука над 8000 ден/мес
  AOP 29: Амортизација на ревалоризирана вредност
  AOP 30: Амортизација над пропишани стапки
  AOP 31: Амортизација на неупотребувани средства
  AOP 32: Исправка на ненаплатени побарувања
  AOP 33: Ненаплатени побарувања од заем
  AOP 34: Трансферна цена (расходи) — разлика vs пазарна
  AOP 35: Трансферна цена (приходи) — разлика vs пазарна
  AOP 36: Камати од поврзано лице (над arm's length)
  AOP 37: Затезни камати (поврзано лице)
  AOP 38: Камати од акционери-нерезиденти (≥20%)
  AOP 39: Други усогласувања

SECTION III: Даночна основа = I + II (AOP 40)

SECTION IV: Намалување (AOP 41 = sum of AOP 42-48)
  AOP 42: Наплатени побарувања (претходно зголемени)
  AOP 43: Вратен заем (претходно зголемен)
  AOP 44: Амортизација (претходно зголемена)
  AOP 45: Неисплатени надоместоци (искажани како приход)
  AOP 46: Дивиденди (оданочени кај исплатувач)
  AOP 47: Пренесена загуба (намалена за непризнаени)
  AOP 48: Реинвестирана добивка

SECTION V: Даночна основа по намалување = III - IV (AOP 49)
SECTION VI: Пресметан данок = V × 10% (AOP 50)

SECTION VII: Намалување на данок (AOP 51 = sum of AOP 52-55)
  AOP 52: Фискални системи (до 10)
  AOP 53: Withholding tax (странство)
  AOP 54: Данок на подружница во странство
  AOP 55: Даночно олеснување за донација (чл. 30-а)

SECTION VIII: Пресметан данок по намалување = VI - VII (AOP 56)
  AOP 57: Платени аконтации
  AOP 58: Повеќе платен данок (пренесен)
  AOP 59: Доплата / повеќе платен износ = 56 - 57 - 58

SECTION IX: ПОСЕБНИ ПОДАТОЦИ (AOP 60-70)
  AOP 60: Реинвестирана добивка (вкупно)
  AOP 61: Загуби (право на покритие 3 год)
  AOP 62: Тековна загуба за пренос
  AOP 63: Неискористено намалување (чл. 30)
  AOP 64: Неискористен данок платен во странство
  AOP 65: Вкупен приход во годината
  AOP 66-69: Донации/спонзорства (со/без намалување)
  AOP 70: Донации во спорт (чл. 30-а)

FOOTER:
  Составувач: Назив, ЕДБ/ЕМБГ, Датум, Својство, Потпис
  Потписник: Име, ЕМБГ, Датум, Својство, Потпис
```

**Implementation**:
- Service: `app/Services/Tax/DbFormService.php`
- Config: `config/ujp_forms/db.php` (all 70 AOP codes with formulas)
- Data source: `IfrsAdapter` (income statement), `CorporateIncomeTaxService` (adjustments)
- Output: XML (etax.ujp.gov.mk namespace) + PDF (matching official layout)
- Manual override: Accountant can edit any AOP value before generation
- **Effort**: 5 days (largest form, 70 fields with complex logic)

### 1.2 И-ПД — Извод за плаќање на данок на добивка
**Priority**: CRITICAL — required attachment with every ДБ
**Structure**: Simple table showing monthly advance payments vs final tax
```
FIELDS:
  - Company info (ЕДБ, name)
  - Tax year
  - Monthly advances paid (Jan-Dec) — 12 rows
  - Total advances paid
  - Calculated tax from ДБ (AOP 56)
  - Difference (overpaid / underpaid)
```
**Data source**: Bank transactions tagged as CIT advances + ДБ AOP 56
**Effort**: 1 day

### 1.3 МДБ — Месечна аконтација на данок на добивка
**Priority**: HIGH — monthly filing for all profitable companies
**Structure**:
```
FIELDS:
  - Company info
  - Month/Year
  - Total revenue for the month
  - UJP coefficient (published monthly, e.g., 3.2%)
  - Calculated advance = revenue × coefficient
  - Payment amount
```
**Implementation**:
- Need to store/update monthly UJP coefficients (admin setting or auto-fetch)
- Data source: Monthly revenue from invoices/income
- **Effort**: 2 days

### 1.4 ДД-01 — Барање за пренесување на загуба
**Priority**: MEDIUM — companies with tax losses
**Structure**: Simple form requesting loss carryforward (up to 3 years)
```
FIELDS:
  - Company info
  - Year of loss
  - Gross loss amount
  - Non-deductible expenses
  - Net loss for carryforward
  - Remaining years
```
**Effort**: 1 day

**Phase 1 Total: ~9 days**

---

## Phase 2: Withholding Tax & Dividends (Weeks 4-5)
*Critical for companies paying non-residents or distributing profits*

### 2.1 ДД-И — Годишен извештај за данок по задршка
**Priority**: HIGH — annual, filed by Feb 15
**Structure**:
```
HEADER: Company info, tax year
TABLE (per payment):
  - Реден број (sequential #)
  - Назив на примателот (recipient name)
  - Земја (country)
  - Вид на приход (income type: dividends/interest/royalties/services/etc.)
  - Износ на исплата (payment amount)
  - Стапка (WHT rate: 10% standard or treaty rate)
  - Пресметан данок (calculated WHT)
  - Уплатен данок (paid WHT)
  - Датум на исплата (payment date)
  - Број на ДБГД/ДТТ одобрение (treaty approval number)
TOTALS: Sum of payments, WHT calculated, WHT paid
FOOTER: Составувач/Потписник
```
**Implementation**:
- New model: `WithholdingTaxPayment` — tracks each payment to non-residents
- Service: `app/Services/Tax/WithholdingTaxService.php`
- UI: Vue form for recording non-resident payments with auto-WHT calculation
- Annual report aggregates all payments for the year
- **Effort**: 4 days (new model + CRUD + report generation)

### 2.2 ДД-ИД — Пресметка за данок на дивиденда
**Priority**: HIGH — filed per dividend distribution event
**Structure**:
```
FIELDS:
  - Company info
  - Date of distribution decision
  - Period profits relate to
  - Gross dividend amount
  - Tax rate (10%)
  - Calculated tax
  - Recipient info (ЕМБГ/ЕДБ, name)
  - Payment date
```
**Implementation**:
- Triggered from a new "Distribute Dividend" action in the system
- Auto-calculates 10% WHT
- Generates payment slip for UJP bank account
- **Effort**: 2 days

### 2.3 ДД-ПЗ — Данок на добивка за покривање загуби
**Priority**: LOW — rare event
**Structure**: Similar to ДД-ИД but for profit used to cover prior losses
**Effort**: 1 day (reuse ДД-ИД pattern)

### 2.4 ДДД/П — Дополнителен данок на добивка
**Priority**: LOW — specific industries only
**Effort**: 1 day (simple form)

**Phase 2 Total: ~8 days**

---

## Phase 3: Annual Accounts Package (Weeks 6-8)
*Filed to Central Register by Feb 28 / Mar 15*

### 3.1 ДЕ — Дополнителни податоци за државна евиденција
**Priority**: CRITICAL — every company files this
**Structure**:
```
SECTIONS:
  1. Основни податоци (company identification)
     - ЕДБ, ЕМБС, назив, адреса, општина
     - Шифра на дејност (НКД код)
     - Правна форма
  2. Податоци за вработени
     - Број на вработени (крај на година)
     - Просечен број на вработени
     - Вкупни трошоци за плати
  3. Податоци за основни средства
     - Набавна вредност
     - Амортизација
     - Сегашна вредност
  4. Финансиски податоци
     - Вкупни приходи
     - Вкупни расходи
     - Добивка/загуба
     - Извоз, увоз
```
**Data source**: Company settings, payroll stats, fixed assets, IFRS income statement
**Effort**: 3 days

### 3.2 СПД — Структура на приходи по дејности
**Priority**: CRITICAL — every company files this
**Structure**:
```
TABLE:
  - Реден број
  - Шифра на дејност (НКД код)
  - Назив на дејност
  - Приход од дејност (amount)
  - % од вкупен приход
TOTAL: Вкупен приход
```
**Data source**: Invoices grouped by activity code (need to add activity code to invoice items or categories)
**Implementation note**: May need a new field `activity_code` on expense categories or items
**Effort**: 2 days

### 3.3 Official Биланс на состојба (full AOP — Образец 36 enhancement)
**Priority**: HIGH — our Образец 36 has simplified AOP codes
**Gap**: Official CRM balance sheet has ~120 AOP codes vs our ~15
```
NEEDED:
  - Expand config/ujp_aop.php obrazec_36 to full official AOP list
  - Add sub-rows for all asset/liability categories
  - Current year + Previous year columns
  - Generate in CRM-compatible format (not just PDF)
```
**Effort**: 3 days (AOP mapping expansion + testing)

### 3.4 Official Биланс на успех (full AOP — Образец 37 enhancement)
**Priority**: HIGH — same issue as Образец 36
**Effort**: 2 days

### 3.5 Образец 38 — Cash Flow Statement (official AOP)
**Priority**: MEDIUM — config exists but no service yet
**Effort**: 2 days (service + PDF template using existing config)

**Phase 3 Total: ~12 days**

---

## Phase 4: VAT & Payroll Supplements (Weeks 9-12)

### 4.1 ДДВ-04 Enhancement — match official form exactly
**Priority**: HIGH — we generate XML but not matching the official PDF layout
**Current state**: `VatXmlService` generates XML. Missing: PDF that matches the official form.

**Official ДДВ-04 structure** (32 fields from actual form):
```
ПРОМЕТ НА ДОБРА И УСЛУГИ (Output):
  01/02: Оданочив промет по општа стапка (18%) — base / ДДВ
  03/04: Повластена стапка 10% — base / ДДВ
  05/06: Повластена стапка 5% — base / ДДВ
  07: Извоз
  08: Ослободен (со право одбивка)
  09: Ослободен (без право одбивка)
  10: Промет кон нерезиденти (не оданочив)
  11: Промет со пренесена обврска (чл. 32-а)
  12/13: Примен од нерезидент (18%) — base / ДДВ
  14/15: Примен од нерезидент (повластена) — base / ДДВ
  16/17: Примен со пренесена обврска (18%) — base / ДДВ
  18/19: Примен со пренесена обврска (повластена) — base / ДДВ
  20: Вкупен ДДВ = 02+04+06+13+15+17+19

ВЛЕЗНИ ИСПОЛНУВАЊА (Input):
  21/22: Влезен промет — base / ДДВ
  23/24: Влезен (примател пресметува, чл. 32) — base / ДДВ
  25/26: Влезен во земја (примател, чл. 32-а) — base / ДДВ
  27/28: Увоз — base / ДДВ
  29: Претходни даноци = 22+24+26+28
  30: Останати даноци/усогласувања

РЕЗУЛТАТ:
  31: Даночен долг/побарување = 20-29±30
  32: Отстапување на побарување

ПРИЛОЗИ:
  - Извештај за извршени промети (чл. 32-а)
  - Извештај за примени промети (чл. 32-а)
  - ДДВ во залихи пред регистрација (table)
```
**Enhancement needed**: Our VatXmlService may not handle fields 10-19 (reverse charge). Verify and fix.
**Effort**: 3 days (PDF template + reverse charge fields + attachments)

### 4.2 ДДВ-ИПДО — Изјава за промет со пренесена обврска
**Priority**: MEDIUM — for companies importing services (reverse charge)
**Structure**: Attachment to ДДВ-04, lists reverse-charge transactions
**Effort**: 2 days

### 4.3 ДДВ-ЕПФ — Евиденција на примени фактури (ослободени)
**Priority**: LOW — quarterly, only for VAT-exempt purchasers
**Effort**: 1 day

### 4.4 МПИН Enhancement — match official XML schema
**Priority**: MEDIUM — verify our XML matches current UJP MPIN schema
**Effort**: 1 day (validation + testing)

### 4.5 е-ППД Integration Notes
**Priority**: LOW for v1 — this is done through UJP's own e-pdd.ujp.gov.mk portal
**Future**: API integration if UJP exposes endpoints
**Effort**: 0 days (defer to future)

### 4.6 Donation/Sponsorship Reports (УЈП-ИДС/ДВ, УЈП-ИДС/ПР)
**Priority**: LOW — event-driven, only companies making donations
**Effort**: 2 days

**Phase 4 Total: ~9 days**

---

## Phase 5: Tax Forms Dashboard & Calendar (Week 12)

### 5.1 Tax Forms Hub (Vue)
**Path**: `resources/scripts/admin/views/tax-forms/TaxFormsDashboard.vue`

```
Features:
  - Calendar view showing upcoming deadlines
  - Status badges per form (pending/generated/filed)
  - Quick-generate buttons for each form type
  - Filing history with download links
  - Auto-reminders before deadlines (email notifications)
  - Partner view: bulk generate across portfolio companies
```

### 5.2 Tax Calendar with Notifications
- Cron job checks upcoming deadlines (7 days, 3 days, 1 day before)
- Sends email reminders to company owner + accountant
- Dashboard widget showing next 3 due forms

### 5.3 Partner Bulk Filing
- Generate ДБ/ДДВ-04/МПИН for all portfolio companies at once
- Download as ZIP with organized folders
- Status tracking per company

**Phase 5 Total: ~5 days**

---

## Summary Timeline

| Phase | Description | Forms | Effort | Weeks |
|-------|-------------|-------|--------|-------|
| **0** | Shared infrastructure (base class, table, config) | - | 3 days | Week 1 |
| **1** | Annual Profit Tax (ДБ, И-ПД, МДБ, ДД-01) | 4 | 9 days | Weeks 1-3 |
| **2** | Withholding & Dividends (ДД-И, ДД-ИД, ДД-ПЗ, ДДД/П) | 4 | 8 days | Weeks 4-5 |
| **3** | Annual Accounts (ДЕ, СПД, expanded АОП 36/37/38) | 5 | 12 days | Weeks 6-8 |
| **4** | VAT & Payroll supplements (ДДВ-04 PDF, ИПДО, ЕПФ, МПИН) | 4+ | 9 days | Weeks 9-11 |
| **5** | Dashboard, Calendar, Partner Bulk | UI | 5 days | Week 12 |
| | **TOTAL** | **16+ forms** | **~46 days** | **~12 weeks** |

---

## File Structure

```
app/Services/Tax/
  ├── TaxFormService.php          (base class)
  ├── DbFormService.php           (ДБ)
  ├── DbVpFormService.php         (ДБ-ВП — already exists as CitXmlService, refactor)
  ├── MdbFormService.php          (МДБ monthly advance)
  ├── IpdFormService.php          (И-ПД payment statement)
  ├── DdIFormService.php          (ДД-И withholding report)
  ├── DdIdFormService.php         (ДД-ИД dividend tax)
  ├── Dd01FormService.php         (ДД-01 loss carryforward)
  ├── DdDoFormService.php         (ДД-ДО cessation)
  ├── DddPFormService.php         (ДДД/П supplementary)
  ├── DdPzFormService.php         (ДД-ПЗ loss coverage)
  ├── DeFormService.php           (ДЕ supplementary data)
  ├── SpdFormService.php          (СПД revenue structure)
  └── DdvIpdoFormService.php      (ДДВ-ИПДО reverse charge)

config/ujp_forms/
  ├── db.php                       (70 AOP codes + formulas)
  ├── db_vp.php                    (total income balance)
  ├── mdb.php                      (monthly coefficients)
  ├── dd_i.php                     (WHT report fields)
  ├── dd_id.php                    (dividend tax fields)
  ├── de.php                       (supplementary data fields)
  ├── spd.php                      (revenue structure fields)
  └── ddv_ipdo.php                 (reverse charge fields)

resources/views/app/pdf/tax-forms/
  ├── _ujp-header.blade.php        (shared UJP header: logo, purple band, company fields)
  ├── _ujp-footer.blade.php        (shared: составувач + потписник sections)
  ├── db.blade.php
  ├── mdb.blade.php
  ├── ipd.blade.php
  ├── dd-i.blade.php
  ├── dd-id.blade.php
  ├── de.blade.php
  └── spd.blade.php

resources/scripts/admin/views/tax-forms/
  ├── TaxFormsDashboard.vue         (main hub)
  ├── DbForm.vue                    (ДБ preview/edit/generate)
  ├── MdbForm.vue                   (МДБ monthly)
  ├── DdIForm.vue                   (ДД-И withholding)
  ├── DdIdForm.vue                  (ДД-ИД dividend)
  ├── DeForm.vue                    (ДЕ annual)
  └── SpdForm.vue                   (СПД annual)

database/migrations/
  └── 2026_03_XX_000001_create_tax_form_filings_table.php
  └── 2026_03_XX_000002_create_withholding_tax_payments_table.php
```

---

## Dependencies & Prerequisites

1. **IFRS Accounts must exist** — forms pull from GL/income statement. Companies without IFRS accounts need journal import first.
2. **Company settings** — ЕДБ, legal form, НКД activity code must be filled in company profile.
3. **Monthly UJP coefficients** — need admin UI or auto-import for МДБ calculation.
4. **StobiSans font** — already in our PDF pipeline (used by DB_Bilans.pdf).
5. **etax.ujp.gov.mk XML schemas** — need to verify against actual UJP XSD for each form.

---

## Success Metrics

- Accountant can generate annual tax package (ДБ + И-ПД + ДЕ + СПД + Образец 36/37) in < 5 minutes per company
- Partner can bulk-generate for 50+ companies in one batch
- Zero manual re-keying needed for monthly ДДВ-04, МПИН, МДБ
- Tax calendar alerts prevent missed deadlines
- All generated forms match official UJP layout pixel-for-pixel

---

## Notes

- **ДЛД-ГДП** (annual PIT return): Deferred — UJP pre-populates this for individuals, not generated by accounting software
- **е-ППД**: Deferred — electronic-only portal at e-pdd.ujp.gov.mk, no standard PDF/XML form
- **ДДВ-01/01Б** (VAT registration): Deferred — one-time administrative form, not accounting workflow
- **ФА-01/02/03** (fiscal devices): Deferred — we have fiscal device drivers, but UJP registration forms are paper-only
- **П-ТЦ** (transfer pricing): Deferred to Phase 6 — complex documentation, low frequency, usually prepared by tax consultants
