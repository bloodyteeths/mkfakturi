<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportTempCustomer extends Model
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
        ];
    }

    // Relationships
    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class);
    }

    public function existingCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'existing_customer_id');
    }

    public function tempInvoices(): HasMany
    {
        return $this->hasMany(ImportTempInvoice::class, 'temp_customer_id');
    }

    public function tempPayments(): HasMany
    {
        return $this->hasMany(ImportTempPayment::class, 'temp_customer_id');
    }

    public function tempExpenses(): HasMany
    {
        return $this->hasMany(ImportTempExpense::class, 'temp_customer_id');
    }

    // Accessors
    public function getFormattedCreatedAtAttribute()
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->importJob->company_id);

        return Carbon::parse($this->created_at)->translatedFormat($dateFormat);
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

    public function scopeWhereEmail($query, $email)
    {
        return $query->where('email', 'LIKE', '%'.$email.'%');
    }

    public function scopeWhereName($query, $name)
    {
        return $query->where('name', 'LIKE', '%'.$name.'%');
    }

    public function scopeWhereCompanyName($query, $companyName)
    {
        return $query->where('company_name', 'LIKE', '%'.$companyName.'%');
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('status')) {
            $query->whereStatus($filters->get('status'));
        }

        if ($filters->get('email')) {
            $query->whereEmail($filters->get('email'));
        }

        if ($filters->get('name')) {
            $query->whereName($filters->get('name'));
        }

        if ($filters->get('company_name')) {
            $query->whereCompanyName($filters->get('company_name'));
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

    public function markAsDuplicate($matchField, $existingCustomerId = null)
    {
        $this->update([
            'is_duplicate' => true,
            'duplicate_match_field' => $matchField,
            'existing_customer_id' => $existingCustomerId,
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

    public function shouldCreateNewCustomer()
    {
        return ! $this->is_duplicate && $this->status !== self::STATUS_FAILED;
    }

    public function shouldUpdateExistingCustomer()
    {
        return $this->is_duplicate && $this->existing_customer_id && $this->status !== self::STATUS_FAILED;
    }

    public function getCustomerData()
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'contact_name' => $this->contact_name,
            'company_name' => $this->company_name,
            'website' => $this->website,
            'tax_id' => $this->tax_id,
            'company_id' => $this->importJob->company_id,
            'creator_id' => $this->importJob->creator_id,
        ];
    }

    public function getBillingAddressData()
    {
        if (! $this->billing_address) {
            return null;
        }

        return [
            'type' => Address::BILLING_TYPE,
            'address_street_1' => $this->billing_address,
            'city' => $this->billing_city,
            'state' => $this->billing_state,
            'zip' => $this->billing_zip,
            'country_id' => $this->getCountryId($this->billing_country),
        ];
    }

    public function getShippingAddressData()
    {
        if (! $this->shipping_address) {
            return null;
        }

        return [
            'type' => Address::SHIPPING_TYPE,
            'address_street_1' => $this->shipping_address,
            'city' => $this->shipping_city,
            'state' => $this->shipping_state,
            'zip' => $this->shipping_zip,
            'country_id' => $this->getCountryId($this->shipping_country),
        ];
    }

    private function getCountryId($countryName)
    {
        if (! $countryName) {
            return null;
        }

        $country = Country::where('name', 'LIKE', '%'.$countryName.'%')->first();

        return $country ? $country->id : null;
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

    /**
     * Find duplicate customer within the same company
     * Updated to properly scope uniqueness checks to company_id
     *
     * CLAUDE-CHECKPOINT
     */
    public function findDuplicateCustomer()
    {
        $companyId = $this->importJob->company_id;

        // Try email match first (most reliable)
        // Email uniqueness is now scoped to company_id via composite unique constraint
        if ($this->email) {
            $customer = Customer::where('company_id', $companyId)
                ->where('email', $this->email)
                ->first();
            if ($customer) {
                return ['customer' => $customer, 'match_field' => 'email'];
            }
        }

        // Try tax_id match
        if ($this->tax_id) {
            $customer = Customer::where('company_id', $companyId)
                ->where('tax_id', $this->tax_id)
                ->first();
            if ($customer) {
                return ['customer' => $customer, 'match_field' => 'tax_id'];
            }
        }

        // Try name match
        if ($this->name) {
            $customer = Customer::where('company_id', $companyId)
                ->where('name', $this->name)
                ->first();
            if ($customer) {
                return ['customer' => $customer, 'match_field' => 'name'];
            }
        }

        return null;
    }
    // CLAUDE-CHECKPOINT
}
