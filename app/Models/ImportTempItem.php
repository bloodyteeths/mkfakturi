<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportTempItem extends Model
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
        'hasValidationErrors',
        'isDuplicate',
        'confidenceScore',
        'formattedPrice',
        'formattedLineTotal',
        'isInvoiceLineItem',
    ];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'tax_rates' => 'array',
            'validation_errors' => 'array',
            'mapping_confidence' => 'array',
            'transformation_log' => 'array',
            'is_duplicate' => 'boolean',
            'row_number' => 'integer',
            'price' => 'integer',
            'base_price' => 'integer',
            'quantity' => 'decimal:2',
            'minimum_quantity' => 'decimal:2',
            'track_quantity' => 'boolean',
            'tax_per_item' => 'boolean',
            'exchange_rate' => 'decimal:4',
            'line_quantity' => 'decimal:2',
            'line_total' => 'integer',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'integer',
        ];
    }

    // Relationships
    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class);
    }

    public function tempInvoice(): BelongsTo
    {
        return $this->belongsTo(ImportTempInvoice::class, 'temp_invoice_id');
    }

    public function existingItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'existing_item_id');
    }

    // Accessors
    public function getFormattedCreatedAtAttribute()
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->importJob->company_id);
        return Carbon::parse($this->created_at)->translatedFormat($dateFormat);
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

    public function getFormattedPriceAttribute()
    {
        if (!$this->price) {
            return null;
        }
        
        return format_money_pdf($this->price, $this->getCurrency());
    }

    public function getFormattedLineTotalAttribute()
    {
        if (!$this->line_total) {
            return null;
        }
        
        return format_money_pdf($this->line_total, $this->getCurrency());
    }

    public function getIsInvoiceLineItemAttribute()
    {
        return !empty($this->temp_invoice_id) || !empty($this->invoice_number);
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

    public function scopeWhereName($query, $name)
    {
        return $query->where('name', 'LIKE', '%' . $name . '%');
    }

    public function scopeWhereSku($query, $sku)
    {
        return $query->where('sku', 'LIKE', '%' . $sku . '%');
    }

    public function scopeWhereCategory($query, $category)
    {
        return $query->where('category', 'LIKE', '%' . $category . '%');
    }

    public function scopeInvoiceLineItems($query)
    {
        return $query->whereNotNull('temp_invoice_id')
                     ->orWhereNotNull('invoice_number');
    }

    public function scopeStandaloneItems($query)
    {
        return $query->whereNull('temp_invoice_id')
                     ->whereNull('invoice_number');
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('status')) {
            $query->whereStatus($filters->get('status'));
        }

        if ($filters->get('name')) {
            $query->whereName($filters->get('name'));
        }

        if ($filters->get('sku')) {
            $query->whereSku($filters->get('sku'));
        }

        if ($filters->get('category')) {
            $query->whereCategory($filters->get('category'));
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

        if ($filters->get('item_type')) {
            if ($filters->get('item_type') === 'line_items') {
                $query->invoiceLineItems();
            } elseif ($filters->get('item_type') === 'standalone') {
                $query->standaloneItems();
            }
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

    public function markAsDuplicate($matchField, $existingItemId = null)
    {
        $this->update([
            'is_duplicate' => true,
            'duplicate_match_field' => $matchField,
            'existing_item_id' => $existingItemId,
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

    public function shouldCreateNewItem()
    {
        return !$this->is_duplicate && $this->status !== self::STATUS_FAILED && !$this->isInvoiceLineItem;
    }

    public function shouldUpdateExistingItem()
    {
        return $this->is_duplicate && $this->existing_item_id && $this->status !== self::STATUS_FAILED;
    }

    public function getItemData()
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price ?: 0,
            'quantity' => $this->quantity,
            'minimum_quantity' => $this->minimum_quantity,
            'track_quantity' => $this->track_quantity,
            'tax_per_item' => $this->tax_per_item,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'currency_id' => $this->getCurrencyId(),
            'unit_id' => $this->getUnitId(),
            'category' => $this->category,
            'company_id' => $this->importJob->company_id,
            'creator_id' => $this->importJob->creator_id,
        ];
    }

    public function getInvoiceItemData()
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'quantity' => $this->line_quantity ?: $this->quantity ?: 1,
            'price' => $this->price ?: 0,
            'total' => $this->line_total ?: ($this->price * ($this->line_quantity ?: $this->quantity ?: 1)),
            'discount_percent' => $this->discount_percent ?: 0,
            'discount_val' => $this->discount_amount ?: 0,
            'item_id' => $this->existing_item_id,
            'company_id' => $this->importJob->company_id,
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

    public function getUnitId()
    {
        if (!$this->unit) {
            return null;
        }

        $unit = Unit::where('name', 'LIKE', '%' . $this->unit . '%')->first();
        return $unit ? $unit->id : null;
    }

    public function findDuplicateItem()
    {
        $query = Item::where('company_id', $this->importJob->company_id);

        // Try SKU match first (most reliable)
        if ($this->sku) {
            $item = $query->where('sku', $this->sku)->first();
            if ($item) {
                return ['item' => $item, 'match_field' => 'sku'];
            }
        }

        // Try barcode match
        if ($this->barcode) {
            $item = $query->where('barcode', $this->barcode)->first();
            if ($item) {
                return ['item' => $item, 'match_field' => 'barcode'];
            }
        }

        // Try name match
        if ($this->name) {
            $item = $query->where('name', $this->name)->first();
            if ($item) {
                return ['item' => $item, 'match_field' => 'name'];
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

    public function linkToInvoice($tempInvoiceId)
    {
        $this->update(['temp_invoice_id' => $tempInvoiceId]);
    }

    public function getTaxRatesData()
    {
        if (!$this->tax_rates || !is_array($this->tax_rates)) {
            return [];
        }

        $taxes = [];
        foreach ($this->tax_rates as $taxRate) {
            $taxes[] = [
                'name' => $taxRate['name'] ?? 'VAT',
                'percent' => $taxRate['percent'] ?? 18,
                'compound' => false,
                'company_id' => $this->importJob->company_id,
            ];
        }

        return $taxes;
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