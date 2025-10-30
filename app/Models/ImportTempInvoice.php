<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportTempInvoice extends Model
{
    use HasFactory;

    // Processing statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_MAPPED = 'mapped';
    public const STATUS_FAILED = 'failed';
    public const STATUS_COMMITTED = 'committed';

    protected $guarded = ['id'];

    protected $appends = [
        'formattedCreatedAt',
        'formattedInvoiceDate',
        'formattedDueDate',
        'hasValidationErrors',
        'isDuplicate',
        'confidenceScore',
        'formattedTotal',
        'hasLineItems',
    ];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'line_items' => 'array',
            'validation_errors' => 'array',
            'mapping_confidence' => 'array',
            'transformation_log' => 'array',
            'is_duplicate' => 'boolean',
            'row_number' => 'integer',
            'invoice_date' => 'date',
            'due_date' => 'date',
            'discount' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
            'discount_val' => 'integer',
            'sub_total' => 'integer',
            'total' => 'integer',
            'tax' => 'integer',
            'due_amount' => 'integer',
        ];
    }

    // Relationships
    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class);
    }

    public function tempCustomer(): BelongsTo
    {
        return $this->belongsTo(ImportTempCustomer::class, 'temp_customer_id');
    }

    public function existingInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'existing_invoice_id');
    }

    public function tempItems(): HasMany
    {
        return $this->hasMany(ImportTempItem::class, 'temp_invoice_id');
    }

    public function tempPayments(): HasMany
    {
        return $this->hasMany(ImportTempPayment::class, 'temp_invoice_id');
    }

    // Accessors
    public function getFormattedCreatedAtAttribute()
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->importJob->company_id);
        return Carbon::parse($this->created_at)->translatedFormat($dateFormat);
    }

    public function getFormattedInvoiceDateAttribute()
    {
        if (!$this->invoice_date) {
            return null;
        }
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->importJob->company_id);
        return Carbon::parse($this->invoice_date)->translatedFormat($dateFormat);
    }

    public function getFormattedDueDateAttribute()
    {
        if (!$this->due_date) {
            return null;
        }
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->importJob->company_id);
        return Carbon::parse($this->due_date)->translatedFormat($dateFormat);
    }

    public function getHasValidationErrorsAttribute()
    {
        return !empty($this->validation_errors);
    }

    public function getIsDuplicateAttribute()
    {
        return $this->is_duplicate;
    }

    public function getConfidenceScoreAttribute()
    {
        if (empty($this->mapping_confidence)) {
            return 0;
        }

        $scores = array_values($this->mapping_confidence);
        return count($scores) > 0 ? round(array_sum($scores) / count($scores), 2) : 0;
    }

    public function getFormattedTotalAttribute()
    {
        if (!$this->total) {
            return null;
        }
        
        return format_money_pdf($this->total, $this->getCurrency());
    }

    public function getHasLineItemsAttribute()
    {
        return !empty($this->line_items) && is_array($this->line_items) && count($this->line_items) > 0;
    }

    // Scopes
    public function scopeWhereImportJob($query, $importJobId)
    {
        return $query->where('import_job_id', $importJobId);
    }

    public function scopeWhereStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeWhereRowNumber($query, $rowNumber)
    {
        return $query->where('row_number', $rowNumber);
    }

    public function scopeWithValidationErrors($query)
    {
        return $query->whereNotNull('validation_errors');
    }

    public function scopeWithoutValidationErrors($query)
    {
        return $query->whereNull('validation_errors');
    }

    public function scopeDuplicates($query)
    {
        return $query->where('is_duplicate', true);
    }

    public function scopeNonDuplicates($query)
    {
        return $query->where('is_duplicate', false);
    }

    public function scopeWhereInvoiceNumber($query, $invoiceNumber)
    {
        return $query->where('invoice_number', 'LIKE', '%' . $invoiceNumber . '%');
    }

    public function scopeWhereCustomerEmail($query, $email)
    {
        return $query->where('customer_email', 'LIKE', '%' . $email . '%');
    }

    public function scopeWhereCustomerName($query, $name)
    {
        return $query->where('customer_name', 'LIKE', '%' . $name . '%');
    }

    public function scopeInvoicesBetween($query, $start, $end)
    {
        return $query->whereBetween('invoice_date', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('status')) {
            $query->whereStatus($filters->get('status'));
        }

        if ($filters->get('invoice_number')) {
            $query->whereInvoiceNumber($filters->get('invoice_number'));
        }

        if ($filters->get('customer_email')) {
            $query->whereCustomerEmail($filters->get('customer_email'));
        }

        if ($filters->get('customer_name')) {
            $query->whereCustomerName($filters->get('customer_name'));
        }

        if ($filters->get('has_errors') !== null) {
            $hasErrors = filter_var($filters->get('has_errors'), FILTER_VALIDATE_BOOLEAN);
            if ($hasErrors) {
                $query->withValidationErrors();
            } else {
                $query->withoutValidationErrors();
            }
        }

        if ($filters->get('is_duplicate') !== null) {
            $isDuplicate = filter_var($filters->get('is_duplicate'), FILTER_VALIDATE_BOOLEAN);
            if ($isDuplicate) {
                $query->duplicates();
            } else {
                $query->nonDuplicates();
            }
        }

        if ($filters->get('from_date') && $filters->get('to_date')) {
            $start = Carbon::createFromFormat('Y-m-d', $filters->get('from_date'));
            $end = Carbon::createFromFormat('Y-m-d', $filters->get('to_date'));
            $query->invoicesBetween($start, $end);
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ?: 'row_number';
            $orderBy = $filters->get('orderBy') ?: 'asc';
            $query->orderBy($field, $orderBy);
        } else {
            $query->orderBy('row_number', 'asc');
        }
    }

    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    // Helper methods
    public function addValidationError($field, $message)
    {
        $errors = $this->validation_errors ?: [];
        
        if (!isset($errors[$field])) {
            $errors[$field] = [];
        }
        
        $errors[$field][] = $message;
        
        $this->update(['validation_errors' => $errors]);
    }

    public function clearValidationErrors()
    {
        $this->update(['validation_errors' => null]);
    }

    public function setMappingConfidence($field, $score)
    {
        $confidence = $this->mapping_confidence ?: [];
        $confidence[$field] = $score;
        
        $this->update(['mapping_confidence' => $confidence]);
    }

    public function logTransformation($field, $originalValue, $transformedValue, $rule = null)
    {
        $log = $this->transformation_log ?: [];
        
        $log[] = [
            'field' => $field,
            'original_value' => $originalValue,
            'transformed_value' => $transformedValue,
            'rule' => $rule,
            'timestamp' => now()->toISOString(),
        ];
        
        $this->update(['transformation_log' => $log]);
    }

    public function markAsDuplicate($matchField, $existingInvoiceId = null)
    {
        $this->update([
            'is_duplicate' => true,
            'duplicate_match_field' => $matchField,
            'existing_invoice_id' => $existingInvoiceId,
        ]);
    }

    public function markAsValidated()
    {
        $this->update(['status' => self::STATUS_VALIDATED]);
    }

    public function markAsMapped()
    {
        $this->update(['status' => self::STATUS_MAPPED]);
    }

    public function markAsFailed($errors = null)
    {
        $data = ['status' => self::STATUS_FAILED];
        
        if ($errors) {
            $data['validation_errors'] = $errors;
        }
        
        $this->update($data);
    }

    public function markAsCommitted()
    {
        $this->update(['status' => self::STATUS_COMMITTED]);
    }

    public function shouldCreateNewInvoice()
    {
        return !$this->is_duplicate && $this->status !== self::STATUS_FAILED;
    }

    public function shouldUpdateExistingInvoice()
    {
        return $this->is_duplicate && $this->existing_invoice_id && $this->status !== self::STATUS_FAILED;
    }

    public function getInvoiceData()
    {
        return [
            'invoice_number' => $this->invoice_number,
            'reference_number' => $this->reference_number,
            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,
            'status' => $this->status ?: Invoice::STATUS_DRAFT,
            'paid_status' => $this->paid_status ?: Invoice::STATUS_UNPAID,
            'notes' => $this->notes,
            'tax_per_item' => $this->tax_per_item ?: 'NO',
            'discount_per_item' => $this->discount_per_item ?: 'NO',
            'discount_type' => $this->discount_type ?: 'fixed',
            'discount' => $this->discount ?: 0,
            'discount_val' => $this->discount_val ?: 0,
            'sub_total' => $this->sub_total ?: 0,
            'total' => $this->total ?: 0,
            'tax' => $this->tax ?: 0,
            'due_amount' => $this->due_amount ?: $this->total ?: 0,
            'currency_id' => $this->getCurrencyId(),
            'exchange_rate' => $this->exchange_rate ?: 1.0,
            'company_id' => $this->importJob->company_id,
            'creator_id' => $this->importJob->creator_id,
        ];
    }

    public function getCurrency()
    {
        if ($this->currency_code) {
            return Currency::where('code', $this->currency_code)->first();
        }
        
        // Fallback to company default currency
        $companyCurrencyId = CompanySetting::getSetting('currency', $this->importJob->company_id);
        return Currency::find($companyCurrencyId);
    }

    public function getCurrencyId()
    {
        $currency = $this->getCurrency();
        return $currency ? $currency->id : null;
    }

    public function findDuplicateInvoice()
    {
        $query = Invoice::where('company_id', $this->importJob->company_id);

        // Try invoice number match first (most reliable)
        if ($this->invoice_number) {
            $invoice = $query->where('invoice_number', $this->invoice_number)->first();
            if ($invoice) {
                return ['invoice' => $invoice, 'match_field' => 'invoice_number'];
            }
        }

        // Try reference number match
        if ($this->reference_number) {
            $invoice = $query->where('reference_number', $this->reference_number)->first();
            if ($invoice) {
                return ['invoice' => $invoice, 'match_field' => 'reference_number'];
            }
        }

        return null;
    }

    public function getValidationSummary()
    {
        if (empty($this->validation_errors)) {
            return ['status' => 'valid', 'error_count' => 0];
        }

        $errorCount = 0;
        foreach ($this->validation_errors as $fieldErrors) {
            $errorCount += count($fieldErrors);
        }

        return [
            'status' => 'invalid',
            'error_count' => $errorCount,
            'fields_with_errors' => array_keys($this->validation_errors),
        ];
    }

    public function parseLineItems()
    {
        if (!$this->hasLineItems) {
            return [];
        }

        $items = [];
        foreach ($this->line_items as $item) {
            $items[] = [
                'name' => $item['name'] ?? '',
                'description' => $item['description'] ?? '',
                'quantity' => $item['quantity'] ?? 1,
                'price' => $item['price'] ?? 0,
                'total' => $item['total'] ?? ($item['quantity'] ?? 1) * ($item['price'] ?? 0),
                'discount_percent' => $item['discount_percent'] ?? 0,
                'discount_val' => $item['discount_val'] ?? 0,
                'tax' => $item['tax'] ?? 0,
            ];
        }

        return $items;
    }

    public function linkToCustomer($tempCustomerId)
    {
        $this->update(['temp_customer_id' => $tempCustomerId]);
    }

    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_VALIDATED => 'Validated',
            self::STATUS_MAPPED => 'Mapped',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_COMMITTED => 'Committed',
        ];
    }
}