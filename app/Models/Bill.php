<?php

namespace App\Models;

use App\Traits\CacheableTrait;
use App\Traits\GeneratesPdfTrait;
use App\Traits\HasAuditing;
use App\Traits\HasCustomFieldsTrait;
use App\Traits\RequiresApproval;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Bill extends Model implements HasMedia
{
    use CacheableTrait;
    use GeneratesPdfTrait;
    use GeneratesPdfTrait;
    use HasAuditing;
    use HasCustomFieldsTrait;
    use HasFactory;
    use InteractsWithMedia;
    use RequiresApproval;
    use SoftDeletes;

    // Status constants
    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_SENT = 'SENT';

    public const STATUS_VIEWED = 'VIEWED';

    public const STATUS_OVERDUE = 'OVERDUE';

    public const STATUS_PAID = 'PAID';

    public const STATUS_COMPLETED = 'COMPLETED';

    // Paid status constants
    public const PAID_STATUS_UNPAID = 'UNPAID';

    public const PAID_STATUS_PAID = 'PAID';

    public const PAID_STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';

    protected $guarded = [
        'id',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'bill_date',
        'due_date',
    ];

    protected $appends = [
        'formattedCreatedAt',
        'formattedBillDate',
        'formattedDueDate',
        'dueAmount',
        'allowEdit',
    ];

    /**
     * Default eager loaded relationships
     */
    protected $with = [
        'supplier:id,name,email',
        'currency:id,name,code,symbol',
        'company:id,name',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'tax' => 'integer',
            'sub_total' => 'integer',
            'discount' => 'float',
            'discount_val' => 'integer',
            'exchange_rate' => 'float',
            'due_amount' => 'integer',
            'posted_to_ifrs' => 'boolean',
        ];
    }

    /**
     * Get formatted created at date
     */
    public function getFormattedCreatedAtAttribute()
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->created_at)->format($dateFormat);
    }

    /**
     * Get formatted bill date
     */
    public function getFormattedBillDateAttribute()
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->bill_date)->translatedFormat($dateFormat);
    }

    /**
     * Get formatted due date
     */
    public function getFormattedDueDateAttribute()
    {
        if (! $this->due_date) {
            return null;
        }
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->due_date)->translatedFormat($dateFormat);
    }

    /**
     * Get due amount (total - paid)
     */
    public function getDueAmountAttribute()
    {
        $paidAmount = $this->payments()->sum('amount');

        return $this->total - $paidAmount;
    }

    /**
     * Check if bill can be edited
     */
    public function getAllowEditAttribute()
    {
        // If posted to IFRS, cannot edit
        if ($this->posted_to_ifrs) {
            return false;
        }

        $retrospective_edit = CompanySetting::getSetting('retrospective_edits', $this->company_id);

        $allowed = true;

        $status = [
            self::STATUS_DRAFT,
            self::STATUS_SENT,
            self::STATUS_VIEWED,
            self::STATUS_COMPLETED,
        ];

        if ($retrospective_edit == 'disable_on_invoice_sent' && (in_array($this->status, $status)) && ($this->paid_status === self::PAID_STATUS_PARTIALLY_PAID || $this->paid_status === self::PAID_STATUS_PAID)) {
            $allowed = false;
        } elseif ($retrospective_edit == 'disable_on_invoice_partial_paid' && ($this->paid_status === self::PAID_STATUS_PARTIALLY_PAID || $this->paid_status === self::PAID_STATUS_PAID)) {
            $allowed = false;
        } elseif ($retrospective_edit == 'disable_on_invoice_paid' && $this->paid_status === self::PAID_STATUS_PAID) {
            $allowed = false;
        }

        return $allowed;
    }

    /**
     * Relationship: Bill belongs to Company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relationship: Bill belongs to Supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relationship: Bill was created by User
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Relationship: Bill has many BillItems
     */
    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }

    /**
     * Relationship: Bill has many Taxes
     */
    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class, 'bill_id');
    }

    /**
     * Relationship: Bill has many BillPayments
     */
    public function payments(): HasMany
    {
        return $this->hasMany(BillPayment::class);
    }

    /**
     * Relationship: Bill belongs to Currency
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Relationship: Bill has many custom field values (morphMany)
     */
    public function fields(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'custom_field_valuable');
    }

    /**
     * Get the project this bill belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the tax report period this bill belongs to based on bill_date
     */
    public function taxReportPeriod(): BelongsTo
    {
        // Find the period that contains this bill's date
        return $this->belongsTo(TaxReportPeriod::class, 'tax_report_period_id');
    }

    /**
     * Scope: Filter by company
     */
    public function scopeWhereCompany($query)
    {
        $query->where('bills.company_id', request()->header('company'));
    }

    /**
     * Scope: Filter by status
     */
    public function scopeWhereStatus($query, $status)
    {
        return $query->where('bills.status', $status);
    }

    /**
     * Scope: Filter by paid status
     */
    public function scopeWherePaidStatus($query, $status)
    {
        return $query->where('bills.paid_status', $status);
    }

    /**
     * Scope: Filter by bill number
     */
    public function scopeWhereBillNumber($query, $billNumber)
    {
        return $query->where('bills.bill_number', 'LIKE', '%'.$billNumber.'%');
    }

    /**
     * Scope: Filter bills between dates
     */
    public function scopeBillsBetween($query, $start, $end)
    {
        return $query->whereBetween(
            'bills.bill_date',
            [$start->format('Y-m-d'), $end->format('Y-m-d')]
        );
    }

    /**
     * Scope: Filter by due date
     */
    public function scopeWhereDueDate($query, $start, $end)
    {
        return $query->whereBetween('bills.due_date', [$start, $end]);
    }

    /**
     * Scope: Filter by supplier
     */
    public function scopeWhereSupplier($query, $supplierId)
    {
        return $query->where('bills.supplier_id', $supplierId);
    }

    /**
     * Scope: Search across multiple fields
     */
    public function scopeWhereSearch($query, $search)
    {
        foreach (explode(' ', $search) as $term) {
            $query->whereHas('supplier', function ($query) use ($term) {
                $query->where('name', 'LIKE', '%'.$term.'%')
                    ->orWhere('contact_name', 'LIKE', '%'.$term.'%');
            });
        }
    }

    /**
     * Scope: Order results
     */
    public function scopeWhereOrder($query, $orderByField, $orderBy)
    {
        $query->orderBy($orderByField, $orderBy);
    }

    /**
     * Scope: Apply filters
     */
    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters)->filter()->all();

        return $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->whereSearch($search);
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->whereStatus($status);
        })->when($filters['paid_status'] ?? null, function ($query, $paidStatus) {
            $query->wherePaidStatus($paidStatus);
        })->when($filters['bill_number'] ?? null, function ($query, $billNumber) {
            $query->whereBillNumber($billNumber);
        })->when(($filters['from_date'] ?? null) && ($filters['to_date'] ?? null), function ($query) use ($filters) {
            $start = Carbon::parse($filters['from_date']);
            $end = Carbon::parse($filters['to_date']);
            $query->billsBetween($start, $end);
        })->when($filters['supplier_id'] ?? null, function ($query, $supplierId) {
            $query->where('supplier_id', $supplierId);
        })->when($filters['project_id'] ?? null, function ($query, $projectId) {
            $query->where('project_id', $projectId);
        })->when($filters['orderByField'] ?? null, function ($query, $orderByField) use ($filters) {
            $orderBy = $filters['orderBy'] ?? 'desc';
            $query->orderBy($orderByField, $orderBy);
        }, function ($query) {
            $query->orderBy('bill_date', 'desc');
        });
    }

    /**
     * Scope: Paginate data
     */
    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    /**
     * Mark bill as sent
     */
    public function markAsSent()
    {
        $this->status = self::STATUS_SENT;
        $this->sent = true;
        $this->save();
    }

    /**
     * Mark bill as viewed
     */
    public function markAsViewed()
    {
        if ($this->status == self::STATUS_SENT) {
            $this->status = self::STATUS_VIEWED;
            $this->viewed = true;
            $this->save();
        }
    }

    /**
     * Mark bill as completed
     */
    public function markAsCompleted()
    {
        $this->status = self::STATUS_COMPLETED;
        $this->save();
    }

    /**
     * Update paid status based on payments
     */
    public function updatePaidStatus()
    {
        $paidAmount = $this->payments()->sum('amount');
        $dueAmount = $this->total - $paidAmount;

        if ($dueAmount <= 0) {
            $this->paid_status = self::PAID_STATUS_PAID;
            $this->status = self::STATUS_COMPLETED;
        } elseif ($paidAmount > 0) {
            $this->paid_status = self::PAID_STATUS_PARTIALLY_PAID;
        } else {
            $this->paid_status = self::PAID_STATUS_UNPAID;
        }

        $this->save();
    }

    /**
     * Check if bill is in a locked tax period
     */
    public function isInLockedPeriod(): bool
    {
        if (! $this->taxReportPeriod) {
            return false;
        }

        return $this->taxReportPeriod->is_locked ?? false;
    }

    /**
     * Delete bills and their related records
     */
    public static function deleteBills($ids)
    {
        foreach ($ids as $id) {
            $bill = self::find($id);

            if ($bill->items()->exists()) {
                $bill->items()->delete();
            }

            if ($bill->payments()->exists()) {
                $bill->payments()->delete();
            }

            if ($bill->taxes()->exists()) {
                $bill->taxes()->delete();
            }

            $bill->delete();
        }

        return true;
    }

    /**
     * Create bill items with base_* fields and taxes
     *
     * @param  self  $bill
     * @param  array  $billItems
     * @return void
     */
    public static function createItems($bill, $billItems)
    {
        $exchangeRate = $bill->exchange_rate ?? 1;

        foreach ($billItems as $billItem) {
            $billItem['company_id'] = $bill->company_id;
            $billItem['exchange_rate'] = $exchangeRate;
            $billItem['base_price'] = $billItem['price'] * $exchangeRate;
            $billItem['base_discount_val'] = ($billItem['discount_val'] ?? 0) * $exchangeRate;
            $billItem['base_tax'] = ($billItem['tax'] ?? 0) * $exchangeRate;
            $billItem['base_total'] = $billItem['total'] * $exchangeRate;

            $item = $bill->items()->create($billItem);

            if (array_key_exists('taxes', $billItem) && $billItem['taxes']) {
                foreach ($billItem['taxes'] as $tax) {
                    $tax['company_id'] = $bill->company_id;
                    $tax['exchange_rate'] = $bill->exchange_rate ?? 1;
                    $tax['base_amount'] = $tax['amount'] * $exchangeRate;
                    $tax['currency_id'] = $bill->currency_id;

                    if (gettype($tax['amount']) !== 'NULL') {
                        $item->taxes()->create($tax);
                    }
                }
            }

            if (array_key_exists('custom_fields', $billItem) && $billItem['custom_fields']) {
                $item->addCustomFields($billItem['custom_fields']);
            }
        }
    }

    /**
     * Create bill-level taxes with base_* fields
     *
     * @param  self  $bill
     * @param  array  $taxes
     * @return void
     */
    public static function createTaxes($bill, $taxes)
    {
        $exchangeRate = $bill->exchange_rate ?? 1;

        foreach ($taxes as $tax) {
            $tax['company_id'] = $bill->company_id;
            $tax['exchange_rate'] = $bill->exchange_rate ?? 1;
            $tax['base_amount'] = $tax['amount'] * $exchangeRate;
            $tax['currency_id'] = $bill->currency_id;

            if (gettype($tax['amount']) !== 'NULL') {
                $bill->taxes()->create($tax);
            }
        }
    }

    /**
     * Get PDF data for bill
     *
     * @return mixed
     */
    public function getPDFData()
    {
        $taxes = collect();

        // Collect all taxes from items
        foreach ($this->items as $item) {
            foreach ($item->taxes as $tax) {
                $found = $taxes->filter(function ($item) use ($tax) {
                    return $item->tax_type_id == $tax->tax_type_id;
                })->first();

                if ($found) {
                    $found->amount += $tax->amount;
                } else {
                    $taxes->push($tax);
                }
            }
        }

        $billTemplate = 'bill1'; // Default bill template

        // Build a lightweight "invoice-like" DTO so we can reuse
        // the existing invoice items/table partial for bill PDFs.
        $invoiceDto = new \stdClass;
        $invoiceDto->items = $this->items;
        $invoiceDto->discount_per_item = $this->discount_per_item ?? 'NO';
        $invoiceDto->tax_per_item = $this->tax_per_item ?? 'NO';
        $invoiceDto->discount_type = $this->discount_type;
        $invoiceDto->discount = $this->discount ?? 0;
        $invoiceDto->discount_val = $this->discount_val ?? 0;
        $invoiceDto->sub_total = $this->sub_total;
        $invoiceDto->total = $this->total;
        $invoiceDto->paid_status = $this->paid_status;
        $invoiceDto->due_amount = $this->due_amount;
        $invoiceDto->taxes = $taxes;

        // Minimal customer wrapper so the invoice partial can
        // access $invoice->customer->currency
        $invoiceCustomer = new \stdClass;
        $invoiceCustomer->currency = $this->currency;
        $invoiceDto->customer = $invoiceCustomer;

        $company = Company::find($this->company_id);
        $locale = CompanySetting::getSetting('language', $company->id);

        \App::setLocale($locale);

        // Handle logo with file existence check
        $logo = $company->logo_path;
        if ($logo && ! filter_var($logo, FILTER_VALIDATE_URL)) {
            // It's a local path, check if file exists
            if (! file_exists($logo)) {
                $logo = null;
            }
        }

        $customFields = CustomField::where('model_type', 'BillItem')->get();

        view()->share([
            // Bill-specific data
            'bill' => $this,
            'company' => $company,
            'supplier' => $this->supplier,
            'logo' => $logo,
            'taxes' => $taxes,
            'company_address' => $this->getCompanyAddress(),
            'billing_address' => $this->getSupplierAddress(),
            'shipping_address' => false, // Bills don't have shipping addresses
            'notes' => $this->getNotes(),
            'customFields' => $customFields,
            // Reused invoice variables for shared partials
            'invoice' => $invoiceDto,
        ]);

        return \PDF::loadView('app.pdf.bill.'.$billTemplate);
    }

    /**
     * Override getFieldsArray from GeneratesPdfTrait to use supplier instead of customer
     *
     * @return array
     */
    public function getFieldsArray()
    {
        $supplier = $this->supplier ?? new Supplier;
        $companyAddress = $this->company->address ?? new Address;

        $fields = [
            '{SHIPPING_ADDRESS_NAME}' => '',
            '{SHIPPING_COUNTRY}' => '',
            '{SHIPPING_STATE}' => '',
            '{SHIPPING_CITY}' => '',
            '{SHIPPING_ADDRESS_STREET_1}' => '',
            '{SHIPPING_ADDRESS_STREET_2}' => '',
            '{SHIPPING_PHONE}' => '',
            '{SHIPPING_ZIP_CODE}' => '',
            '{BILLING_ADDRESS_NAME}' => $supplier->name ?? '',
            '{BILLING_COUNTRY}' => '',
            '{BILLING_STATE}' => '',
            '{BILLING_CITY}' => '',
            '{BILLING_ADDRESS_STREET_1}' => '',
            '{BILLING_ADDRESS_STREET_2}' => '',
            '{BILLING_PHONE}' => $supplier->phone ?? '',
            '{BILLING_ZIP_CODE}' => '',
            '{COMPANY_NAME}' => $this->company->name ?? '',
            '{COMPANY_COUNTRY}' => $companyAddress->country_name ?? '',
            '{COMPANY_STATE}' => $companyAddress->state ?? '',
            '{COMPANY_CITY}' => $companyAddress->city ?? '',
            '{COMPANY_ADDRESS_STREET_1}' => $companyAddress->address_street_1 ?? '',
            '{COMPANY_ADDRESS_STREET_2}' => $companyAddress->address_street_2 ?? '',
            '{COMPANY_PHONE}' => $companyAddress->phone ?? '',
            '{COMPANY_ZIP_CODE}' => $companyAddress->zip ?? '',
            '{COMPANY_VAT}' => $this->company->vat_id ?? '',
            '{COMPANY_TAX}' => $this->company->tax_id ?? '',
            '{CONTACT_DISPLAY_NAME}' => $supplier->name ?? '',
            '{PRIMARY_CONTACT_NAME}' => $supplier->contact_name ?? '',
            '{CONTACT_EMAIL}' => $supplier->email ?? '',
            '{CONTACT_PHONE}' => $supplier->phone ?? '',
            '{CONTACT_WEBSITE}' => $supplier->website ?? '',
            '{CONTACT_TAX_ID}' => __('pdf_tax_id').': '.($supplier->tax_id ?? ''),
        ];

        $customFields = $this->fields;
        foreach ($customFields as $customField) {
            $fields['{'.$customField->customField->slug.'}'] = $customField->defaultAnswer ?? '';
        }

        if ($supplier && isset($supplier->fields)) {
            foreach ($supplier->fields as $customField) {
                $fields['{'.$customField->customField->slug.'}'] = $customField->defaultAnswer ?? '';
            }
        }

        foreach ($fields as $key => $field) {
            $fields[$key] = htmlspecialchars($field, ENT_QUOTES, 'UTF-8');
        }

        return $fields;
    }

    /**
     * Get company address formatted for bill PDF
     *
     * @return string|bool
     */
    public function getCompanyAddress()
    {
        if ($this->company && (! $this->company->address()->exists())) {
            return false;
        }

        $format = CompanySetting::getSetting('bill_company_address_format', $this->company_id);

        // Fallback to invoice format if bill format not set
        if (! $format) {
            $format = CompanySetting::getSetting('invoice_company_address_format', $this->company_id);
        }

        return $this->getFormattedString($format);
    }

    /**
     * Get supplier address formatted for bill PDF (bills use supplier instead of customer)
     *
     * @return string|bool
     */
    public function getSupplierAddress()
    {
        if (! $this->supplier) {
            return false;
        }

        // Build supplier address from available fields
        $addressParts = array_filter([
            $this->supplier->name,
            $this->supplier->contact_name,
            $this->supplier->email,
            $this->supplier->phone,
        ]);

        return implode('<br>', $addressParts);
    }

    /**
     * Get formatted notes for bill PDF
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->getFormattedString($this->notes);
    }

    /**
     * Get extra fields for PDF placeholders (Bill-specific)
     *
     * @return array
     */
    public function getExtraFields()
    {
        return [
            '{BILL_DATE}' => $this->formattedBillDate,
            '{BILL_DUE_DATE}' => $this->formattedDueDate,
            '{BILL_NUMBER}' => $this->bill_number,
            '{BILL_REF_NUMBER}' => $this->reference_number,
            '{SUPPLIER_DISPLAY_NAME}' => $this->supplier->name ?? '',
            '{SUPPLIER_CONTACT_NAME}' => $this->supplier->contact_name ?? '',
            '{SUPPLIER_EMAIL}' => $this->supplier->email ?? '',
            '{SUPPLIER_PHONE}' => $this->supplier->phone ?? '',
            '{SUPPLIER_WEBSITE}' => $this->supplier->website ?? '',
            '{SUPPLIER_TAX_ID}' => $this->supplier->tax_id ?? '',
        ];
    }
}

// CLAUDE-CHECKPOINT
