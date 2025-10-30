<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportTempPayment extends Model
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
        'formattedPaymentDate',
        'formattedBankDate',
        'hasValidationErrors',
        'isDuplicate',
        'confidenceScore',
        'formattedAmount',
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
            'payment_date' => 'date',
            'bank_date' => 'datetime',
            'amount' => 'integer',
            'base_amount' => 'integer',
            'exchange_rate' => 'decimal:4',
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

    public function tempInvoice(): BelongsTo
    {
        return $this->belongsTo(ImportTempInvoice::class, 'temp_invoice_id');
    }

    public function existingPayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'existing_payment_id');
    }

    // Accessors
    public function getFormattedCreatedAtAttribute()
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->importJob->company_id);
        return Carbon::parse($this->created_at)->translatedFormat($dateFormat);
    }

    public function getFormattedPaymentDateAttribute()
    {
        if (!$this->payment_date) {
            return null;
        }
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->importJob->company_id);
        return Carbon::parse($this->payment_date)->translatedFormat($dateFormat);
    }

    public function getFormattedBankDateAttribute()
    {
        if (!$this->bank_date) {
            return null;
        }
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->importJob->company_id);
        return Carbon::parse($this->bank_date)->translatedFormat($dateFormat . ' H:i:s');
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

    public function getFormattedAmountAttribute()
    {
        if (!$this->amount) {
            return null;
        }
        
        return format_money_pdf($this->amount, $this->getCurrency());
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

    public function scopeWherePaymentNumber($query, $paymentNumber)
    {
        return $query->where('payment_number', 'LIKE', '%' . $paymentNumber . '%');
    }

    public function scopeWhereReference($query, $reference)
    {
        return $query->where('reference', 'LIKE', '%' . $reference . '%');
    }

    public function scopeWhereCustomerEmail($query, $email)
    {
        return $query->where('customer_email', 'LIKE', '%' . $email . '%');
    }

    public function scopeWhereCustomerName($query, $name)
    {
        return $query->where('customer_name', 'LIKE', '%' . $name . '%');
    }

    public function scopeWhereInvoiceNumber($query, $invoiceNumber)
    {
        return $query->where('invoice_number', 'LIKE', '%' . $invoiceNumber . '%');
    }

    public function scopePaymentsBetween($query, $start, $end)
    {
        return $query->whereBetween('payment_date', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('status')) {
            $query->whereStatus($filters->get('status'));
        }

        if ($filters->get('payment_number')) {
            $query->wherePaymentNumber($filters->get('payment_number'));
        }

        if ($filters->get('reference')) {
            $query->whereReference($filters->get('reference'));
        }

        if ($filters->get('customer_email')) {
            $query->whereCustomerEmail($filters->get('customer_email'));
        }

        if ($filters->get('customer_name')) {
            $query->whereCustomerName($filters->get('customer_name'));
        }

        if ($filters->get('invoice_number')) {
            $query->whereInvoiceNumber($filters->get('invoice_number'));
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
            $query->paymentsBetween($start, $end);
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

    public function markAsDuplicate($matchField, $existingPaymentId = null)
    {
        $this->update([
            'is_duplicate' => true,
            'duplicate_match_field' => $matchField,
            'existing_payment_id' => $existingPaymentId,
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

    public function shouldCreateNewPayment()
    {
        return !$this->is_duplicate && $this->status !== self::STATUS_FAILED;
    }

    public function shouldUpdateExistingPayment()
    {
        return $this->is_duplicate && $this->existing_payment_id && $this->status !== self::STATUS_FAILED;
    }

    public function getPaymentData()
    {
        return [
            'payment_number' => $this->payment_number,
            'payment_date' => $this->payment_date,
            'amount' => $this->amount ?: 0,
            'base_amount' => $this->base_amount ?: ($this->amount * ($this->exchange_rate ?: 1)),
            'notes' => $this->notes,
            'reference' => $this->reference,
            'currency_id' => $this->getCurrencyId(),
            'exchange_rate' => $this->exchange_rate ?: 1.0,
            'payment_method_id' => $this->getPaymentMethodId(),
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

    public function getPaymentMethodId()
    {
        if (!$this->payment_method) {
            return null;
        }

        // Map common payment method names to system constants
        $methodMap = [
            'cash' => Payment::PAYMENT_MODE_CASH,
            'bank_transfer' => Payment::PAYMENT_MODE_BANK_TRANSFER,
            'check' => Payment::PAYMENT_MODE_CHECK,
            'credit_card' => Payment::PAYMENT_MODE_CREDIT_CARD,
            'other' => Payment::PAYMENT_MODE_OTHER,
        ];

        $normalizedMethod = strtolower(str_replace([' ', '-'], '_', $this->payment_method));
        $systemMethod = $methodMap[$normalizedMethod] ?? Payment::PAYMENT_MODE_OTHER;

        $paymentMethod = PaymentMethod::where('company_id', $this->importJob->company_id)
                                    ->where('name', 'LIKE', '%' . $this->payment_method . '%')
                                    ->first();

        if (!$paymentMethod) {
            // Create a new payment method if it doesn't exist
            $paymentMethod = PaymentMethod::create([
                'name' => $this->payment_method,
                'company_id' => $this->importJob->company_id,
            ]);
        }

        return $paymentMethod->id;
    }

    public function findDuplicatePayment()
    {
        $query = Payment::where('company_id', $this->importJob->company_id);

        // Try payment number match first (most reliable)
        if ($this->payment_number) {
            $payment = $query->where('payment_number', $this->payment_number)->first();
            if ($payment) {
                return ['payment' => $payment, 'match_field' => 'payment_number'];
            }
        }

        // Try reference match
        if ($this->reference) {
            $payment = $query->where('reference', $this->reference)->first();
            if ($payment) {
                return ['payment' => $payment, 'match_field' => 'reference'];
            }
        }

        // Try transaction ID match (if stored in notes or custom field)
        if ($this->transaction_id) {
            $payment = $query->where('notes', 'LIKE', '%' . $this->transaction_id . '%')->first();
            if ($payment) {
                return ['payment' => $payment, 'match_field' => 'transaction_id'];
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

    public function linkToInvoice($tempInvoiceId)
    {
        $this->update(['temp_invoice_id' => $tempInvoiceId]);
    }

    public function getCustomerFromInvoice()
    {
        if ($this->tempInvoice && $this->tempInvoice->tempCustomer) {
            return $this->tempInvoice->tempCustomer;
        }

        return null;
    }

    public function getBankDetails()
    {
        return [
            'bank_name' => $this->bank_name,
            'bank_account' => $this->bank_account,
            'transaction_id' => $this->transaction_id,
            'bank_date' => $this->bank_date,
        ];
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