# Sales Reports Investigation - Code Locations & Evidence

## BUG #1: Wrong Field Name `validation_status`

### Evidence Location 1
**File:** `/Users/tamsar/Downloads/mkaccounting/app/Jobs/Migration/CommitImportJob.php`  
**Line:** 208  
**Code:**
```php
$validRecords = ImportTempCustomer::where('import_job_id', $this->importJob->id)
    ->where('validation_status', 'valid')  // ❌ WRONG
    ->get();
```

### Evidence Location 2
**File:** `/Users/tamsar/Downloads/mkaccounting/app/Jobs/Migration/CommitImportJob.php`  
**Line:** 325  
**Code:**
```php
$validRecords = ImportTempInvoice::where('import_job_id', $this->importJob->id)
    ->where('validation_status', 'valid')  // ❌ WRONG
    ->get();
```

### Evidence Location 3
**File:** `/Users/tamsar/Downloads/mkaccounting/app/Jobs/Migration/CommitImportJob.php`  
**Line:** 434  
**Code:**
```php
$validRecords = ImportTempItem::where('import_job_id', $this->importJob->id)
    ->where('validation_status', 'valid')  // ❌ WRONG
    ->get();
```

### Additional Instances
**File:** `/Users/tamsar/Downloads/mkaccounting/app/Jobs/Migration/CommitImportJob.php`  
**Lines:** 539 (commitPayments), 611 (commitExpenses)  
Same bug pattern repeated

### What Should Exist
**File:** `/Users/tamsar/Downloads/mkaccounting/database/migrations/2025_07_26_001200_create_import_temp_invoices_table.php`  
**Line:** 55  
**Code:**
```php
$table->enum('status', ['pending', 'validated', 'mapped', 'failed', 'committed'])->default('pending');
// Field is named 'status', NOT 'validation_status'
```

---

## BUG #2: Non-Existent `transformed_data` Column

### Evidence Location 1
**File:** `/Users/tamsar/Downloads/mkaccounting/app/Jobs/Migration/CommitImportJob.php`  
**Line:** 219  
**Code:**
```php
$data = json_decode($tempRecord->transformed_data, true);  // ❌ Column doesn't exist
```

### Evidence Location 2
**File:** `/Users/tamsar/Downloads/mkaccounting/app/Jobs/Migration/CommitImportJob.php`  
**Line:** 336  
**Code:**
```php
$data = json_decode($tempRecord->transformed_data, true);  // ❌ Column doesn't exist
```

### Evidence Location 3
**File:** `/Users/tamsar/Downloads/mkaccounting/app/Jobs/Migration/CommitImportJob.php`  
**Line:** 445  
**Code:**
```php
$data = json_decode($tempRecord->transformed_data, true);  // ❌ Column doesn't exist
```

### Additional Instances
**File:** `/Users/tamsar/Downloads/mkaccounting/app/Jobs/Migration/CommitImportJob.php`  
**Lines:** 446 (duplicate_info), etc.

### What Actually Exists
**File:** `/Users/tamsar/Downloads/mkaccounting/database/migrations/2025_07_26_001200_create_import_temp_invoices_table.php`  
**Lines:** 22-52  

Direct columns exist (NOT in a JSON field):
```php
$table->string('invoice_number')->nullable();
$table->string('reference_number')->nullable();
$table->date('invoice_date')->nullable();
$table->date('due_date')->nullable();
$table->string('invoice_status')->nullable();
$table->string('paid_status')->nullable();
$table->text('notes')->nullable();
$table->string('tax_per_item')->nullable();
$table->string('discount_per_item')->nullable();
$table->string('discount_type')->nullable();
$table->decimal('discount', 15, 2)->nullable();
$table->unsignedBigInteger('discount_val')->nullable();
$table->unsignedBigInteger('sub_total')->nullable();
$table->unsignedBigInteger('total')->nullable();
$table->unsignedBigInteger('tax')->nullable();
$table->unsignedBigInteger('due_amount')->nullable();
$table->string('currency_code', 3)->nullable();
$table->decimal('exchange_rate', 10, 4)->nullable();
$table->json('line_items')->nullable();

// NO 'transformed_data' or 'duplicate_info' columns
```

---

## BUG #3: Missing `customer_id` in Invoice Creation

### Evidence Location
**File:** `/Users/tamsar/Downloads/mkaccounting/app/Jobs/Migration/CommitImportJob.php`  
**Lines:** 356-362  
**Code:**
```php
protected function createInvoice($tempRecord, array $data, $defaultCurrency): void
{
    $invoiceData = $this->prepareInvoiceData($data);
    $invoiceData['company_id'] = $this->importJob->company_id;
    $invoiceData['creator_id'] = $this->importJob->creator_id;
    $invoiceData['currency_id'] = $defaultCurrency->id;
    // ❌ MISSING: $invoiceData['customer_id'] = ...
    
    $invoice = Invoice::create($invoiceData);
}
```

### What's Required
**File:** `/Users/tamsar/Downloads/mkaccounting/app/Models/Invoice.php`  
**Lines:** 113-115  
**Code:**
```php
public function customer(): BelongsTo
{
    return $this->belongsTo(Customer::class, 'customer_id');
}
```

Invoice model requires customer_id foreign key.

### What Should Link It
**File:** `/Users/tamsar/Downloads/mkaccounting/app/Models/ImportTempInvoice.php`  
**Lines:** 63-66  
**Code:**
```php
public function tempCustomer(): BelongsTo
{
    return $this->belongsTo(ImportTempCustomer::class, 'temp_customer_id');
}
```

The relationship exists to temp customer, but NOT resolved to actual customer.

---

## BUG #4: prepareInvoiceData() Incomplete

### Evidence Location
**File:** `/Users/tamsar/Downloads/mkaccounting/app/Jobs/Migration/CommitImportJob.php`  
**Lines:** 410-426  
**Code:**
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
        'paid_status' => Payment::STATUS_UNPAID,  // ❌ WRONG CONSTANT
        'base_sub_total' => isset($data['subtotal']) ? (int) round(floatval($data['subtotal']) * 100) : 0,
        'base_tax' => isset($data['tax_amount']) ? (int) round(floatval($data['tax_amount']) * 100) : 0,
        'base_total' => isset($data['total']) ? (int) round(floatval($data['total']) * 100) : 0,
        // ❌ MISSING: 'customer_id'
        // ❌ WRONG: 'status' should map to invoice status enum
    ];
}
```

---

## QUERY EVIDENCE: Why Reports Show 0

### Customer Sales Report Query
**File:** `/Users/tamsar/Downloads/mkaccounting/app/Http/Controllers/V1/Admin/Report/CustomerSalesReportController.php`  
**Lines:** 36-54  
**Code:**
```php
$customers = Customer::with(['invoices' => function ($query) use ($start, $end) {
    $query->whereBetween('invoice_date', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
}])->get();

foreach ($customers as $customer) {
    foreach ($customer->invoices as $invoice) {
        $customerTotalAmount += $invoice->base_total;  // ← Sums invoice.base_total
    }
}
```

**Result:** If invoices not created, this sum = 0

### Item Sales Report Query
**File:** `/Users/tamsar/Downloads/mkaccounting/app/Http/Controllers/V1/Admin/Report/ItemSalesReportController.php`  
**Lines:** 33-36  
**Code:**
```php
$items = InvoiceItem::whereCompany($company->id)
    ->applyInvoiceFilters($request->only(['from_date', 'to_date']))
    ->itemAttributes()  // ← Key scope
    ->get();
```

### The Crucial Scope
**File:** `/Users/tamsar/Downloads/mkaccounting/app/Models/InvoiceItem.php`  
**Lines:** 80-85  
**Code:**
```php
public function scopeItemAttributes($query)
{
    $query->select(
        DB::raw('sum(quantity) as total_quantity, sum(base_total) as total_amount, invoice_items.name')
    )->groupBy('invoice_items.name');
}
```

**Query:** `SELECT SUM(invoice_items.base_total) FROM invoice_items ...`  
**Result:** 0 because invoice_items table is completely empty

### Sales Template Evidence
**File:** `/Users/tamsar/Downloads/mkaccounting/resources/views/app/pdf/reports/sales-customers.blade.php`  
**Line:** 178  
**Code:**
```blade
{!! format_money_pdf($invoice->base_total, $currency) !!}
```

Template displays `invoice->base_total` but it's 0 because invoice_items not populated.

---

## DATABASE SCHEMA EVIDENCE

### ImportTempInvoice Schema
**File:** `/Users/tamsar/Downloads/mkaccounting/database/migrations/2025_07_26_001200_create_import_temp_invoices_table.php`  
**Status:** Has correct columns (invoice_number, total, etc.)
**Status:** Has `status` enum field (not validation_status)
**Status:** Has NO transformed_data field
**Status:** Has NO duplicate_info field

### ImportTempCustomer Schema  
**File:** `/Users/tamsar/Downloads/mkaccounting/database/migrations/2025_07_26_001100_create_import_temp_customers_table.php`  
**Status:** Has correct columns (name, email, etc.)
**Status:** Has `status` enum field (not validation_status)
**Status:** Links to actual customer via `existing_customer_id`

### Invoice Schema
**File:** `/Users/tamsar/Downloads/mkaccounting/database/migrations/2017_04_12_090759_create_invoices_table.php`  
**Status:** Requires `customer_id` foreign key
**Status:** Has base_total, base_sub_total, base_tax fields

### InvoiceItem Schema
**File:** `/Users/tamsar/Downloads/mkaccounting/database/migrations/2017_04_12_091015_create_invoice_items_table.php`  
**Status:** Has base_total field
**Status:** Is empty because CommitImportJob::commitItems() fails

---

## DASHBOARD WORKS EVIDENCE

### Dashboard Service
**File:** `/Users/tamsar/Downloads/mkaccounting/app/Services/DashboardMetricsService.php`  
**Lines:** 33-36  
**Code:**
```php
$invoiceByMonth = $this->aggregateMonthly(
    Invoice::class,
    'invoice_date',
    'base_total',
    $companyId,
    $window['start'],
    $window['end']
);
```

**Direct SQL:** `SELECT SUM(base_total) FROM invoices`
**Status:** Works if invoices exist
**Shows:** 8.437.000 ден (from manual creation or payment records)

---

## SUMMARY TABLE

| Item | Location | Status | Impact |
|------|----------|--------|--------|
| validation_status bug | CommitImportJob:208,325,434,539,611 | CRITICAL | Blocks ALL imports |
| transformed_data bug | CommitImportJob:219,336,445,etc | CRITICAL | Prevents data access |
| customer_id missing | CommitImportJob:356-362 | CRITICAL | FK constraint fails |
| commitItems() failing | CommitImportJob:431-460 | CRITICAL | No items created |
| ItemSalesReport query | ItemSalesReportController:33-36 | WORKING | But sums empty table |
| InvoiceItem.itemAttributes | InvoiceItem:80-85 | WORKING | But queries empty table |
| Dashboard calculation | DashboardMetricsService:33-36 | WORKING | Uses invoice totals |

