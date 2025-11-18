<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportTempExpense extends Model
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
        'formattedExpenseDate',
        'hasValidationErrors',
        'isDuplicate',
        'confidenceScore',
        'formattedAmount',
        'formattedNetAmount',
        'formattedTaxAmount',
        'isBillable',
    ];

    protected function casts(): array
    {
        return [
            'raw_data' => 'array',
            'validation_errors' => 'array',
            'mapping_confidence' => 'array',
            'transformation_log' => 'array',
            'is_duplicate' => 'boolean',
            'row_number' => 'integer',
            'expense_date' => 'date',
            'amount' => 'integer',
            'tax_amount' => 'integer',
            'net_amount' => 'integer',
            'base_amount' => 'integer',
            'tax_rate' => 'decimal:2',
            'exchange_rate' => 'decimal:4',
            'tax_deductible' => 'boolean',
            'billable' => 'boolean',
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

    public function existingExpense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'existing_expense_id');
    }

    // Accessors
    public function getFormattedCreatedAtAttribute()
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->importJob->company_id);

        return Carbon::parse($this->created_at)->translatedFormat($dateFormat);
    }

    public function getFormattedExpenseDateAttribute()
    {
        if (! $this->expense_date) {
            return null;
        }
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->importJob->company_id);

        return Carbon::parse($this->expense_date)->translatedFormat($dateFormat);
    }

    public function getHasValidationErrorsAttribute()
    {
        return ! empty($this->validation_errors);
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

    public function getFormattedAmountAttribute()
    {
        if (! $this->amount) {
            return null;
        }

        return format_money_pdf($this->amount, $this->getCurrency());
    }

    public function getFormattedNetAmountAttribute()
    {
        if (! $this->net_amount) {
            return null;
        }

        return format_money_pdf($this->net_amount, $this->getCurrency());
    }

    public function getFormattedTaxAmountAttribute()
    {
        if (! $this->tax_amount) {
            return null;
        }

        return format_money_pdf($this->tax_amount, $this->getCurrency());
    }

    public function getIsBillableAttribute()
    {
        return $this->billable && ! empty($this->customer_name);
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

    public function scopeWhereVendorName($query, $vendorName)
    {
        return $query->where('vendor_name', 'LIKE', '%'.$vendorName.'%');
    }

    public function scopeWhereCategoryName($query, $categoryName)
    {
        return $query->where('category_name', 'LIKE', '%'.$categoryName.'%');
    }

    public function scopeWhereReceiptNumber($query, $receiptNumber)
    {
        return $query->where('receipt_number', 'LIKE', '%'.$receiptNumber.'%');
    }

    public function scopeWhereCustomerName($query, $customerName)
    {
        return $query->where('customer_name', 'LIKE', '%'.$customerName.'%');
    }

    public function scopeBillable($query)
    {
        return $query->where('billable', true);
    }

    public function scopeNonBillable($query)
    {
        return $query->where('billable', false);
    }

    public function scopeTaxDeductible($query)
    {
        return $query->where('tax_deductible', true);
    }

    public function scopeExpensesBetween($query, $start, $end)
    {
        return $query->whereBetween('expense_date', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('status')) {
            $query->whereStatus($filters->get('status'));
        }

        if ($filters->get('vendor_name')) {
            $query->whereVendorName($filters->get('vendor_name'));
        }

        if ($filters->get('category_name')) {
            $query->whereCategoryName($filters->get('category_name'));
        }

        if ($filters->get('receipt_number')) {
            $query->whereReceiptNumber($filters->get('receipt_number'));
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

        if ($filters->get('is_billable') !== null) {
            $isBillable = filter_var($filters->get('is_billable'), FILTER_VALIDATE_BOOLEAN);
            if ($isBillable) {
                $query->billable();
            } else {
                $query->nonBillable();
            }
        }

        if ($filters->get('is_tax_deductible') !== null) {
            $isTaxDeductible = filter_var($filters->get('is_tax_deductible'), FILTER_VALIDATE_BOOLEAN);
            if ($isTaxDeductible) {
                $query->taxDeductible();
            }
        }

        if ($filters->get('from_date') && $filters->get('to_date')) {
            $start = Carbon::createFromFormat('Y-m-d', $filters->get('from_date'));
            $end = Carbon::createFromFormat('Y-m-d', $filters->get('to_date'));
            $query->expensesBetween($start, $end);
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

        if (! isset($errors[$field])) {
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

    public function markAsDuplicate($matchField, $existingExpenseId = null)
    {
        $this->update([
            'is_duplicate' => true,
            'duplicate_match_field' => $matchField,
            'existing_expense_id' => $existingExpenseId,
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

    public function shouldCreateNewExpense()
    {
        return ! $this->is_duplicate && $this->status !== self::STATUS_FAILED;
    }

    public function shouldUpdateExistingExpense()
    {
        return $this->is_duplicate && $this->existing_expense_id && $this->status !== self::STATUS_FAILED;
    }

    public function getExpenseData()
    {
        return [
            'expense_date' => $this->expense_date,
            'amount' => $this->amount ?: 0,
            'base_amount' => $this->base_amount ?: ($this->amount * ($this->exchange_rate ?: 1)),
            'notes' => $this->notes,
            'vendor_name' => $this->vendor_name,
            'receipt_number' => $this->receipt_number,
            'tax_amount' => $this->tax_amount ?: 0,
            'exchange_rate' => $this->exchange_rate ?: 1.0,
            'currency_id' => $this->getCurrencyId(),
            'expense_category_id' => $this->getExpenseCategoryId(),
            'payment_method_id' => $this->getPaymentMethodId(),
            'customer_id' => $this->getCustomerId(),
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

    public function getExpenseCategoryId()
    {
        if (! $this->category_name) {
            return null;
        }

        $category = ExpenseCategory::where('company_id', $this->importJob->company_id)
            ->where('name', 'LIKE', '%'.$this->category_name.'%')
            ->first();

        if (! $category) {
            // Create a new expense category if it doesn't exist
            $category = ExpenseCategory::create([
                'name' => $this->category_name,
                'company_id' => $this->importJob->company_id,
            ]);
        }

        return $category->id;
    }

    public function getPaymentMethodId()
    {
        if (! $this->payment_method) {
            return null;
        }

        $paymentMethod = PaymentMethod::where('company_id', $this->importJob->company_id)
            ->where('name', 'LIKE', '%'.$this->payment_method.'%')
            ->first();

        if (! $paymentMethod) {
            // Create a new payment method if it doesn't exist
            $paymentMethod = PaymentMethod::create([
                'name' => $this->payment_method,
                'company_id' => $this->importJob->company_id,
            ]);
        }

        return $paymentMethod->id;
    }

    public function getCustomerId()
    {
        if (! $this->billable || ! $this->customer_name) {
            return null;
        }

        // First try to find via temp customer link
        if ($this->tempCustomer) {
            return $this->tempCustomer->existing_customer_id;
        }

        // Try to find existing customer by name
        $customer = Customer::where('company_id', $this->importJob->company_id)
            ->where('name', 'LIKE', '%'.$this->customer_name.'%')
            ->first();

        return $customer ? $customer->id : null;
    }

    public function findDuplicateExpense()
    {
        $query = Expense::where('company_id', $this->importJob->company_id);

        // Try receipt number match first (most reliable)
        if ($this->receipt_number) {
            $expense = $query->where('receipt_number', $this->receipt_number)->first();
            if ($expense) {
                return ['expense' => $expense, 'match_field' => 'receipt_number'];
            }
        }

        // Try combination of vendor, amount, and date
        if ($this->vendor_name && $this->amount && $this->expense_date) {
            $expense = $query->where('vendor_name', $this->vendor_name)
                ->where('amount', $this->amount)
                ->where('expense_date', $this->expense_date)
                ->first();
            if ($expense) {
                return ['expense' => $expense, 'match_field' => 'vendor_amount_date'];
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

    public function linkToCustomer($tempCustomerId)
    {
        $this->update(['temp_customer_id' => $tempCustomerId]);
    }

    public function getAttachmentDetails()
    {
        return [
            'path' => $this->attachment_path,
            'name' => $this->attachment_name,
            'type' => $this->attachment_type,
        ];
    }

    public function hasAttachment()
    {
        return ! empty($this->attachment_path);
    }

    public function calculateTaxAmount()
    {
        if (! $this->tax_rate || ! $this->net_amount) {
            return 0;
        }

        return round(($this->net_amount * $this->tax_rate) / 100);
    }

    public function calculateNetAmount()
    {
        if (! $this->amount || ! $this->tax_rate) {
            return $this->amount;
        }

        // Calculate net amount from gross amount and tax rate
        return round($this->amount / (1 + ($this->tax_rate / 100)));
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
