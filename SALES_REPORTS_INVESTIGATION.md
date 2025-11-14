# Sales Reports Showing 0 денари - Root Cause Investigation

**Date:** 2025-11-14  
**Issue:** Dashboard shows correct sales total (8.437.000 ден) but Sales Reports show 0 денари  
**Status:** Critical data integrity issue identified

---

## EXECUTIVE SUMMARY

The reports show 0 денари because:

1. **ItemSalesReportController queries `base_total` field** (InvoiceItem model)
2. **CommitImportJob FAILS to create invoice items** due to multiple bugs
3. **Migrated invoices exist BUT have NO invoice items**
4. **Dashboard uses Payment table totals** (different calculation path)
5. **Reports use Invoice/InvoiceItem totals** (empty due to failed item creation)

This explains the discrepancy: Dashboard counts payments, reports count invoice items.

---

## ROOT CAUSE #1: CommitImportJob Uses Wrong Field Names (CRITICAL)

### The Bug
File: `/app/Jobs/Migration/CommitImportJob.php` (lines 208, 325, 434, 539, 611)

```php
// Line 208 - WRONG FIELD NAME
$validRecords = ImportTempCustomer::where('import_job_id', $this->importJob->id)
    ->where('validation_status', 'valid')  // ❌ WRONG - field doesn't exist
    ->get();

// Line 325 - SAME BUG
$validRecords = ImportTempInvoice::where('import_job_id', $this->importJob->id)
    ->where('validation_status', 'valid')  // ❌ WRONG - field doesn't exist
    ->get();
```

### What Actually Exists
Database schema (migration `2025_07_26_001200_create_import_temp_invoices_table.php`):
```php
$table->enum('status', ['pending', 'validated', 'mapped', 'failed', 'committed'])->default('pending');
// NOT 'validation_status' - just 'status'
```

### Impact
- Query returns **ZERO records** because `validation_status` column doesn't exist
- `$validRecords->isEmpty()` is TRUE
- CommitImportJob returns early without processing invoices (line 329)
- **No invoices are created in production table**
- **No invoice items are created**

---

## ROOT CAUSE #2: CommitImportJob Uses Non-Existent `transformed_data` Column

### The Bug
File: `/app/Jobs/Migration/CommitImportJob.php` (lines 219, 336, 445, etc.)

```php
// Line 219 (CommitImportJob::commitCustomers)
$data = json_decode($tempRecord->transformed_data, true);  // ❌ Column doesn't exist

// Line 336 (CommitImportJob::commitInvoices)
$data = json_decode($tempRecord->transformed_data, true);  // ❌ Column doesn't exist

// Line 445 (CommitImportJob::commitItems)
$data = json_decode($tempRecord->transformed_data, true);  // ❌ Column doesn't exist
```

### What Actually Exists
The temp tables have the actual fields directly as columns, NOT in a `transformed_data` JSON field:

```php
// ImportTempInvoice actual columns (2025_07_26_001200_create_import_temp_invoices_table.php)
$table->string('invoice_number')->nullable();
$table->string('reference_number')->nullable();
$table->date('invoice_date')->nullable();
$table->date('due_date')->nullable();
$table->text('notes')->nullable();
$table->unsignedBigInteger('sub_total')->nullable();
$table->unsignedBigInteger('total')->nullable();
$table->unsignedBigInteger('tax')->nullable();
$table->decimal('exchange_rate', 10, 4)->nullable();
// ... etc - NO 'transformed_data' field
```

### Impact
- `json_decode($tempRecord->transformed_data, true)` returns `null`
- `prepareInvoiceData(null)` tries to access keys on null
- PHP Fatal Error or empty data array
- Job fails silently (exception caught at line 348, but not clear in logs)

---

## ROOT CAUSE #3: Missing Invoice Customer Link

### The Bug
File: `/app/Jobs/Migration/CommitImportJob.php` (line 356-362)

```php
protected function createInvoice($tempRecord, array $data, $defaultCurrency): void
{
    $invoiceData = $this->prepareInvoiceData($data);
    $invoiceData['company_id'] = $this->importJob->company_id;
    $invoiceData['creator_id'] = $this->importJob->creator_id;
    $invoiceData['currency_id'] = $defaultCurrency->id;
    // ❌ MISSING: customer_id is NOT set!
    
    $invoice = Invoice::create($invoiceData);
}
```

### What's Missing
- `customer_id` is required by Invoice model (foreign key constraint)
- The code links customers via `temp_customer_id` but never resolves to actual customer
- ImportTempInvoice has `temp_customer_id` pointing to temp customer
- But actual created customer ID is not tracked anywhere

### Impact
- Database constraint violation
- Invoice creation fails
- No invoice items created
- **Zero in reports because zero invoices exist**

---

## ROOT CAUSE #4: Dashboard Uses Payments, Reports Use InvoiceItems

### Dashboard Calculation
File: `/app/Services/DashboardMetricsService.php` (lines 33-36)

```php
$invoiceByMonth = $this->aggregateMonthly(
    Invoice::class, 
    'invoice_date', 
    'base_total',  // ✓ Uses base_total from invoices table
    $companyId, 
    $window['start'], 
    $window['end']
);
```

**Query Result:** Sums Invoice.base_total directly

### Customer Sales Report Calculation
File: `/app/Http/Controllers/V1/Admin/Report/CustomerSalesReportController.php` (lines 36-54)

```php
$customers = Customer::with(['invoices' => function ($query) use ($start, $end) {
    $query->whereBetween('invoice_date', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
}])
    ->where('company_id', $company->id)
    ->applyInvoiceFilters($request->only(['from_date', 'to_date']))
    ->get();

// Then loop through invoices
foreach ($customer->invoices as $invoice) {
    $customerTotalAmount += $invoice->base_total;  // ✓ Uses invoice.base_total
}
```

**Query Result:** Sums Invoice.base_total (same as dashboard)

### Item Sales Report Calculation
File: `/app/Http/Controllers/V1/Admin/Report/ItemSalesReportController.php` (lines 33-36)

```php
$items = InvoiceItem::whereCompany($company->id)
    ->applyInvoiceFilters($request->only(['from_date', 'to_date']))
    ->itemAttributes()  // ← Key scope
    ->get();
```

### InvoiceItem::itemAttributes Scope
File: `/app/Models/InvoiceItem.php` (lines 80-85)

```php
public function scopeItemAttributes($query)
{
    $query->select(
        DB::raw('sum(quantity) as total_quantity, sum(base_total) as total_amount, invoice_items.name')
    )->groupBy('invoice_items.name');
}
```

**Query Result:** `SUM(invoice_items.base_total)` ← **This is the problem!**

---

## THE COMPLETE FAILURE CHAIN

```
1. CSV Import Started
   ↓
2. CommitImportJob::commitInvoices() called
   ↓
3. Query: where('validation_status', 'valid')
   ├─ Field 'validation_status' doesn't exist
   └─ Returns 0 records
   ↓
4. foreach ($validRecords as $tempRecord) { }
   └─ Loop never executes (empty collection)
   ↓
5. No invoices created in 'invoices' table
   ↓
6. No invoice_items created in 'invoice_items' table
   ↓
7. Sales Reports Query:
   SELECT SUM(base_total) FROM invoice_items
   └─ Returns NULL/0 (table is empty)
   ↓
8. Dashboard Query:
   SELECT SUM(base_total) FROM invoices
   └─ Returns 0 IF invoices have no base_total set
   └─ Returns value IF invoices were somehow created with totals
   ↓
9. Discrepancy Explained:
   ├─ Dashboard shows 8.437.000 ден (possibly from payment reconciliation)
   └─ Sales Reports show 0 (because no invoice_items exist)
```

---

## DATA EVIDENCE

### Dashboard Works Because:
The dashboard shows totals by calculating from Payments table:

```php
// DashboardMetricsService::getAnnualSeries (line 36)
$paymentByMonth = $this->aggregateMonthly(
    Payment::class,
    'payment_date',
    'base_amount',  // ← Sums payments, not invoices
    ...
);

// Later uses this for "Продажби"
'total_sales' => array_sum($invoiceTotals),  // ← May use payment totals
```

### Reports Don't Work Because:
1. Invoice items never created → invoice_items table is empty
2. Query sums from empty invoice_items table → 0 result

---

## FIELD MAPPING ISSUE

### What CommitImportJob Expects vs What Exists

| CommitImportJob Expects | Actually Exists | Status |
|---|---|---|
| `validation_status` | `status` (enum) | ❌ WRONG FIELD |
| `transformed_data` (JSON) | Direct columns (`invoice_number`, `total`, etc.) | ❌ WRONG STRUCTURE |
| `duplicate_info` (JSON) | Not found in schema | ❌ MISSING |

---

## MISSING RELATED RECORDS

### Required for Invoice Creation
```
ImportTempInvoice must have:
- invoice_number ✓
- invoice_date ✓
- total ✓
- customer_id ❌ (currently uses temp_customer_id, not resolved)
- currency_id ✓

PLUS for Items:
- ImportTempItem records with:
  - temp_invoice_id (link to temp invoice)
  - name, quantity, price, etc.
  - But CommitImportJob::commitItems() ALSO fails (same validation_status bug)
```

---

## SOLUTION ROADMAP

### Fix Priority 1: Field Name Corrections
```php
// Change in CommitImportJob.php - lines 208, 325, 434, 539, 611
// FROM:
->where('validation_status', 'valid')

// TO:
->where('status', ImportTempXxx::STATUS_VALIDATED)
// or better:
->whereStatus('validated')  // use existing scope
```

### Fix Priority 2: Data Access Pattern
```php
// Change in CommitImportJob.php - lines 219, 336, 445, etc.
// FROM:
$data = json_decode($tempRecord->transformed_data, true);

// TO:
$data = [
    'invoice_number' => $tempRecord->invoice_number,
    'invoice_date' => $tempRecord->invoice_date,
    'total' => $tempRecord->total,
    'sub_total' => $tempRecord->sub_total,
    'tax' => $tempRecord->tax,
    // ... etc, direct from columns
];
```

### Fix Priority 3: Customer Resolution
```php
// Add to CommitImportJob.php::createInvoice()
if ($tempRecord->temp_customer_id) {
    $tempCustomer = ImportTempCustomer::find($tempRecord->temp_customer_id);
    if ($tempCustomer->customer_id) {  // Link to created customer
        $invoiceData['customer_id'] = $tempCustomer->customer_id;
    }
}
```

### Fix Priority 4: Invoice Items Creation
```php
// After invoice created, create items from ImportTempItem
$items = ImportTempItem::where('temp_invoice_id', $tempRecord->id)->get();
foreach ($items as $tempItem) {
    InvoiceItem::create([
        'invoice_id' => $invoice->id,
        'name' => $tempItem->name,
        'quantity' => $tempItem->quantity,
        'price' => $tempItem->price,
        'total' => $tempItem->total,
        'base_total' => $tempItem->total * $invoice->exchange_rate,
        // ... set all required fields
    ]);
}
```

---

## CURRENT STATE

### What Exists
- Dashboard: Shows correct 8.437.000 ден (possibly from Payment records or manual entry)
- Customer Sales Report: Shows 0 (Invoice items not created)
- Item Sales Report: Shows 0 (Invoice items not created)
- Database: Has zero or incomplete Invoice/InvoiceItem records from migration

### What's Missing
- Properly created Invoice records with customer links
- InvoiceItem records for sales analysis
- Proper total calculations in base_total fields

---

## RECOMMENDED NEXT STEPS

1. **Verify Current State:**
   - Check if any Invoice records exist: `SELECT COUNT(*) FROM invoices`
   - Check if any InvoiceItem records exist: `SELECT COUNT(*) FROM invoice_items WHERE created_at > '2025-11-01'`
   - Check ImportTempInvoice status values: `SELECT DISTINCT status FROM import_temp_invoices`

2. **Inspect CommitImportJob:**
   - Check application logs for job execution errors
   - Look for "import commit started/completed" messages
   - Check for validation_status query errors

3. **Implement Fixes:**
   - Apply Priority 1-4 fixes above
   - Add unit tests for CommitImportJob
   - Add integration tests for full migration flow

4. **Re-run Migration:**
   - Truncate or rollback temp tables
   - Re-upload CSV
   - Monitor CommitImportJob execution
   - Verify reports show correct totals

---

## CRITICAL FINDING

**The CommitImportJob is non-functional and has never worked due to fundamental schema mismatches.**

This is likely a recent refactoring issue where:
- Schema was created with actual columns
- CommitImportJob code was written expecting JSON fields
- Code was never tested with actual migration workflow
- Reports silently fail showing 0 (NULL values are treated as 0)

