# Invoice Migration Data Investigation Report
**Date:** 2025-11-14  
**Focus:** Why reports show zero amounts for migrated invoices

---

## 1. DATABASE SCHEMA: INVOICE AMOUNT COLUMNS

### Original Creation (2017_04_12_090759_create_invoices_table.php)
```php
$table->unsignedBigInteger('sub_total');    // NOT nullable, default NULL (unsafe)
$table->unsignedBigInteger('total');        // NOT nullable, default NULL (unsafe)
$table->unsignedBigInteger('tax');          // NOT nullable, default NULL (unsafe)
$table->unsignedBigInteger('due_amount');   // NOT nullable, default NULL (unsafe)
```

### Base Currency Columns (2021_07_16_072458_add_base_columns_into_invoices_table.php)
```php
$table->decimal('exchange_rate', 19, 6)->nullable();
$table->unsignedBigInteger('base_discount_val')->nullable();
$table->unsignedBigInteger('base_sub_total')->nullable();
$table->unsignedBigInteger('base_total')->nullable();
$table->unsignedBigInteger('base_tax')->nullable();
$table->unsignedBigInteger('base_due_amount')->nullable();
```

**CRITICAL ISSUE:** All base_* columns are NULLABLE but main columns are NOT nullable (yet default to NULL).

### SUMMARY - Invoice Amount Columns:
| Column | Type | Nullable | Default | Used In Reports |
|--------|------|----------|---------|-----------------|
| `sub_total` | unsignedBigInteger | NO | NULL | Yes (sums) |
| `total` | unsignedBigInteger | NO | NULL | Yes (primary) |
| `tax` | unsignedBigInteger | NO | NULL | Yes |
| `due_amount` | unsignedBigInteger | NO | NULL | Yes |
| `base_sub_total` | unsignedBigInteger | YES | NULL | Yes (reports use this) |
| `base_total` | unsignedBigInteger | YES | NULL | **YES - Primary** |
| `base_tax` | unsignedBigInteger | YES | NULL | Yes |
| `base_due_amount` | unsignedBigInteger | YES | NULL | Yes |

---

## 2. CSV IMPORT LOGIC: WHICH FIELDS ARE POPULATED?

### InvoiceImport.php (App\Imports\InvoiceImport) - Lines 75-119

**Fields populated from CSV:**
```php
$invoice = new Invoice([
    'company_id' => $this->companyId,
    'creator_id' => $this->creatorId,
    'customer_id' => $customer->id,
    'invoice_number' => $mappedRow['invoice_number'] ?? null,      // ✓ FROM CSV
    'invoice_date' => $this->parseDate($mappedRow['invoice_date']),  // ✓ FROM CSV
    'due_date' => $this->parseDate($mappedRow['due_date'] ?? null),  // ✓ FROM CSV
    'sub_total' => $this->parseAmount($mappedRow['sub_total'] ?? 0), // ✓ FROM CSV
    'tax' => $this->parseAmount($mappedRow['tax'] ?? 0),             // ✓ FROM CSV
    'total' => $this->parseAmount($mappedRow['total'] ?? 0),         // ✓ FROM CSV
    'discount' => $mappedRow['discount'] ?? 0,                        // ✓ FROM CSV
    'discount_type' => $mappedRow['discount_type'] ?? 'fixed',        // ✓ FROM CSV
    'discount_val' => $this->parseAmount($mappedRow['discount_val'] ?? 0), // ✓ FROM CSV
    'notes' => $mappedRow['notes'] ?? null,                           // ✓ FROM CSV
    'status' => $mappedRow['status'] ?? Invoice::STATUS_DRAFT,        // ✓ FROM CSV
]);
```

**Fields NOT populated (critical gap):**
```
❌ currency_id    - NOT SET (will be NULL or default)
❌ exchange_rate  - NOT SET (will be NULL, defaults to NULL)
❌ base_sub_total - NOT SET (will be NULL)
❌ base_total     - NOT SET (will be NULL) <-- REPORT USES THIS!
❌ base_tax       - NOT SET (will be NULL)
❌ base_due_amount - NOT SET (will be NULL)
```

### Exchange Rate Impact:
In Invoice Model (line 521-529):
```php
public static function createItems($invoice, $invoiceItems)
{
    $exchange_rate = $invoice->exchange_rate;  // If NULL, will be NULL!
    
    foreach ($invoiceItems as $invoiceItem) {
        $invoiceItem['base_price'] = $invoiceItem['price'] * $exchange_rate; // NULL * price = NULL
        $invoiceItem['base_tax'] = $invoiceItem['tax'] * $exchange_rate;     // NULL * tax = NULL
        $invoiceItem['base_total'] = $invoiceItem['total'] * $exchange_rate; // NULL * total = NULL
```

**CRITICAL BUG:** If `exchange_rate` is NULL, all base_* calculations become NULL!

---

## 3. INVOICE MODEL ACCESSORS & CALCULATIONS

### No Getters for Amount Fields
The Invoice model has NO accessors for `total`, `base_total`, etc.

### Casting Configuration (lines 76-86):
```php
protected function casts(): array
{
    return [
        'total' => 'integer',
        'tax' => 'integer',
        'sub_total' => 'integer',
        'discount' => 'float',
        'discount_val' => 'integer',
        'exchange_rate' => 'float',
    ];
}
```

**Gap:** `base_total`, `base_sub_total`, `base_tax` are NOT cast (but they're integers in DB).

---

## 4. REPORT FILTERS & AMOUNT LOGIC

### CustomerSalesReportController.php (lines 36-54)

```php
$customers = Customer::with(['invoices' => function ($query) use ($start, $end) {
    $query->whereBetween(
        'invoice_date',
        [$start->format('Y-m-d'), $end->format('Y-m-d')]
    );
}])
    ->where('company_id', $company->id)
    ->applyInvoiceFilters($request->only(['from_date', 'to_date']))
    ->get();

$totalAmount = 0;
foreach ($customers as $customer) {
    $customerTotalAmount = 0;
    foreach ($customer->invoices as $invoice) {
        $customerTotalAmount += $invoice->base_total;  // <-- USES base_total!
    }
    $customer->totalAmount = $customerTotalAmount;
    $totalAmount += $customerTotalAmount;
}
```

**KEY FINDING:** Reports sum `$invoice->base_total`, NOT `$invoice->total`!

### Invoice Status Filters (Invoice Model, lines 244-303):
```php
public function scopeWhereStatus($query, $status) {
    return $query->where('invoices.status', $status);
}

public function scopeWherePaidStatus($query, $status) {
    return $query->where('invoices.paid_status', $status);
}
```

Migrated invoices have status set to `Invoice::STATUS_DRAFT` by default (InvoiceImport.php:114).

---

## 5. MIGRATION COMMIT LOGIC: CommitImportJob.php

### Invoice Data Preparation (lines 410-426):

```php
protected function prepareInvoiceData(array $data): array
{
    return [
        'invoice_number' => $data['invoice_number'] ?? uniqid('INV-'),
        'invoice_date' => $data['invoice_date'] ?? now()->format('Y-m-d'),
        'due_date' => $data['due_date'] ?? now()->addDays(30)->format('Y-m-d'),
        'sub_total' => isset($data['subtotal']) ? (int) round(floatval($data['subtotal']) * 100) : 0,
        'tax' => isset($data['tax_amount']) ? (int) round(floatval($data['tax_amount']) * 100) : 0,
        'total' => isset($data['total']) ? (int) round(floatval($data['total']) * 100) : 0,
        'status' => $data['status'] ?? 'draft',
        'notes' => $data['notes'] ?? null,
        'paid_status' => Payment::STATUS_UNPAID,
        'base_sub_total' => isset($data['subtotal']) ? (int) round(floatval($data['subtotal']) * 100) : 0,
        'base_tax' => isset($data['tax_amount']) ? (int) round(floatval($data['tax_amount']) * 100) : 0,
        'base_total' => isset($data['total']) ? (int) round(floatval($data['total']) * 100) : 0,
    ];
}
```

**GAPS in CommitImportJob:**
```
❌ currency_id    - NOT SET
❌ exchange_rate  - NOT SET (defaults to NULL)
❌ base_due_amount - NOT SET
❌ creator_id     - Set by $this->importJob->creator_id (OK)
```

### Invoice Creation (line 363):
```php
$invoice = Invoice::create($invoiceData);
```

Since `currency_id` and `exchange_rate` are not in `$invoiceData`, they'll be NULL or use DB defaults.

---

## 6. DATA MIGRATION PATH: FROM CSV → TEMP TABLE → INVOICE TABLE

### Step 1: CSV Import → InvoiceImport Class
- Parses CSV columns based on mapping
- Creates Invoice model with CSV fields
- **PROBLEM:** Doesn't set `currency_id`, `exchange_rate`, or calculate `base_*` values

### Step 2: Temp Table Storage (ImportTempInvoice)
- Stores raw data for review/validation
- Has fields: `sub_total`, `total`, `tax`, `exchange_rate` (nullable)
- **PROBLEM:** `exchange_rate` defaults to NULL if not in CSV

### Step 3: Commit to Production (CommitImportJob)
- Reads from temp tables
- Calls `prepareInvoiceData()` 
- Sets `base_sub_total`, `base_tax`, `base_total` from `total` column
- **PROBLEM:** Still doesn't set `currency_id` or `exchange_rate`

### Step 4: Report Query
- Sums `base_total` column
- If `base_total` is NULL or 0, reports show 0

---

## 7. ROOT CAUSE ANALYSIS

### Primary Issues (Why Reports Show 0):

1. **Missing `exchange_rate` Population**
   - InvoiceImport doesn't extract it from CSV
   - CommitImportJob doesn't set it
   - Defaults to NULL in database
   - When NULL, `base_*` calculations become NULL
   - Reports sum NULL → 0

2. **Missing `base_total` Calculation**
   - InvoiceImport creates invoices WITHOUT setting `base_total`
   - Even if `total` is set, `base_total` remains NULL
   - Reports ONLY use `base_total` (not `total`)
   - Result: Reports show 0 even if `total` has value

3. **Missing `currency_id`**
   - InvoiceImport doesn't extract currency from CSV
   - No default currency is set during import
   - Creates currency mismatch issues for multi-currency invoices
   - Breaks exchange rate calculations

4. **Status Issues** (Secondary)
   - Imported invoices default to `STATUS_DRAFT`
   - Some reports might filter by status
   - But main issue is NULL `base_total`, not status

---

## 8. SAMPLE DATA COMPARISON

### What a Correct Invoice Looks Like:
```
ID: 123
total: 10000 (100.00 in base units)
base_total: 10000
exchange_rate: 1.0
currency_id: 1 (MKD)
status: DRAFT
```

### What Migrated Invoices Likely Look Like:
```
ID: 456 (Imported)
total: 10000 (100.00)
base_total: NULL ❌❌❌
exchange_rate: NULL ❌
currency_id: NULL ❌
status: DRAFT
```

**Result:** Reports sum `base_total` → NULL → 0

---

## 9. FIELD MAPPING ISSUES IN IMPORT

### InvoiceImport Column Mapping (lines 128-152):
The mapping looks for:
- `invoice_number`
- `invoice_date`
- `due_date`
- `sub_total`
- `tax`
- `total`
- `discount`
- `discount_type`
- `discount_val`
- `notes`
- `status`

**MISSING** from mapping:
- `currency_id` or `currency`
- `exchange_rate`
- `base_*` fields

CSV files likely don't include these fields, so they're never populated.

---

## 10. VALIDATION RULES GAP

### InvoiceImport Validation Rules (lines 243-250):
```php
public function rules(): array
{
    return [
        $this->getColumnName('invoice_number') => 'required|string|max:255',
        $this->getColumnName('invoice_date') => 'required',
        $this->getColumnName('total') => 'required',
    ];
}
```

Validation doesn't require:
- `currency_id` 
- `exchange_rate`
- `sub_total` (required in DB, but not in import validation)

---

## SUMMARY TABLE

| Component | Issue | Impact | Severity |
|-----------|-------|--------|----------|
| InvoiceImport.php | Doesn't extract `currency_id` | NULL currency | HIGH |
| InvoiceImport.php | Doesn't set `exchange_rate` | NULL rate, base_* = NULL | **CRITICAL** |
| InvoiceImport.php | Doesn't set `base_total` | Reports sum NULL | **CRITICAL** |
| CommitImportJob.php | Doesn't set `currency_id` | Orphaned invoices | HIGH |
| CommitImportJob.php | Doesn't set `exchange_rate` | Same as import | **CRITICAL** |
| Report Controller | Uses `base_total` only | Can't fall back to `total` | MEDIUM |
| Invoice Model | No base_* casting | Type issues | LOW |
| Migration (2017) | Columns NOT nullable but default NULL | Allows bad data | MEDIUM |

---

## RECOMMENDED FIXES

### IMMEDIATE (Required for Reports to Work):

1. **Update InvoiceImport.php:**
   - Add `currency_id` extraction from CSV or default to company currency
   - Set `exchange_rate` to 1.0 if not in CSV
   - Calculate and set `base_total = total * exchange_rate` (or just `total` if exchange_rate = 1.0)

2. **Update CommitImportJob.php:**
   - Add `currency_id` to invoice data (use company default)
   - Add `exchange_rate` to invoice data (default 1.0)
   - Ensure all `base_*` fields are populated

3. **Add Field Mapping Rules:**
   - Create mapping rules for `currency`, `exchange_rate` fields if they exist in CSV
   - Fallback to company default currency

### SECONDARY (Improve Robustness):

4. **Add Accessor to Invoice Model:**
   - Add `getBbaseTotal()` accessor that falls back to `total` if `base_total` is NULL
   - This protects reports from NULL values

5. **Update Validation:**
   - Make `sub_total` validation optional (since calculated)
   - Add optional validation for `currency_id`

6. **Add Migration:**
   - Ensure all existing invoices have `exchange_rate = 1.0` and `base_*` filled
   - Use same migration pattern as `2021_11_13_114808_calculate_base_values_for_existing_data.php`

---

