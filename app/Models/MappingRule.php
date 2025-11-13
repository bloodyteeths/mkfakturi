<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MappingRule extends Model
{
    use HasFactory;

    // Entity types
    public const ENTITY_CUSTOMER = 'customer';
    public const ENTITY_INVOICE = 'invoice';
    public const ENTITY_ITEM = 'item';
    public const ENTITY_PAYMENT = 'payment';
    public const ENTITY_EXPENSE = 'expense';

    // Transformation types
    public const TRANSFORM_DIRECT = 'direct';
    public const TRANSFORM_REGEX = 'regex';
    public const TRANSFORM_LOOKUP = 'lookup';
    public const TRANSFORM_CALCULATION = 'calculation';
    public const TRANSFORM_DATE_FORMAT = 'date_format';
    public const TRANSFORM_CURRENCY_CONVERT = 'currency_convert';
    public const TRANSFORM_SPLIT = 'split';
    public const TRANSFORM_COMBINE = 'combine';
    public const TRANSFORM_CONDITIONAL = 'conditional';

    protected $guarded = ['id'];

    protected $appends = [
        'formattedCreatedAt',
        'successRatePercentage',
        'isSystemRule',
        'isActive',
        'hasTestCases',
    ];

    protected function casts(): array
    {
        return [
            'field_variations' => 'array',
            'transformation_config' => 'array',
            'validation_rules' => 'array',
            'business_rules' => 'array',
            'macedonian_patterns' => 'array',
            'language_variations' => 'array',
            'format_patterns' => 'array',
            'conditions' => 'array',
            'test_cases' => 'array',
            'sample_data' => 'array',
            'confidence_score' => 'decimal:2',
            'success_rate' => 'decimal:2',
            'usage_count' => 'integer',
            'success_count' => 'integer',
            'priority' => 'integer',
            'is_active' => 'boolean',
            'is_system_rule' => 'boolean',
        ];
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    // Accessors
    public function getFormattedCreatedAtAttribute()
    {
        $dateFormat = $this->company_id 
            ? CompanySetting::getSetting('carbon_date_format', $this->company_id)
            : 'Y-m-d';
        return Carbon::parse($this->created_at)->translatedFormat($dateFormat);
    }

    public function getSuccessRatePercentageAttribute()
    {
        return round($this->success_rate, 2);
    }

    public function getIsSystemRuleAttribute($value)
    {
        return (bool) $value;
    }

    public function getIsActiveAttribute($value)
    {
        return (bool) $value;
    }

    public function getHasTestCasesAttribute()
    {
        return !empty($this->test_cases) && is_array($this->test_cases) && count($this->test_cases) > 0;
    }

    // Scopes
    public function scopeWhereCompany($query, $companyId = null)
    {
        $companyId = $companyId ?: request()->header('company');
        return $query->where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->orWhereNull('company_id'); // Include global rules
        });
    }

    public function scopeWhereEntityType($query, $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    public function scopeWhereSourceSystem($query, $sourceSystem)
    {
        return $query->where(function ($q) use ($sourceSystem) {
            $q->where('source_system', $sourceSystem)
              ->orWhereNull('source_system'); // Include generic rules
        });
    }

    public function scopeWhereSourceField($query, $sourceField)
    {
        return $query->where('source_field', $sourceField);
    }

    public function scopeWhereTargetField($query, $targetField)
    {
        return $query->where('target_field', $targetField);
    }

    public function scopeWhereTransformationType($query, $transformationType)
    {
        return $query->where('transformation_type', $transformationType);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeSystemRules($query)
    {
        return $query->where('is_system_rule', true);
    }

    public function scopeUserRules($query)
    {
        return $query->where('is_system_rule', false);
    }

    public function scopeOrderByPriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    public function scopeHighConfidence($query, $threshold = 0.8)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('entity_type')) {
            $query->whereEntityType($filters->get('entity_type'));
        }

        if ($filters->get('source_system')) {
            $query->whereSourceSystem($filters->get('source_system'));
        }

        if ($filters->get('transformation_type')) {
            $query->whereTransformationType($filters->get('transformation_type'));
        }

        if ($filters->get('source_field')) {
            $query->where('source_field', 'LIKE', '%' . $filters->get('source_field') . '%');
        }

        if ($filters->get('target_field')) {
            $query->where('target_field', 'LIKE', '%' . $filters->get('target_field') . '%');
        }

        if ($filters->get('is_active') !== null) {
            $isActive = filter_var($filters->get('is_active'), FILTER_VALIDATE_BOOLEAN);
            if ($isActive) {
                $query->active();
            } else {
                $query->inactive();
            }
        }

        if ($filters->get('is_system_rule') !== null) {
            $isSystemRule = filter_var($filters->get('is_system_rule'), FILTER_VALIDATE_BOOLEAN);
            if ($isSystemRule) {
                $query->systemRules();
            } else {
                $query->userRules();
            }
        }

        if ($filters->get('min_confidence')) {
            $query->where('confidence_score', '>=', $filters->get('min_confidence'));
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ?: 'priority';
            $orderBy = $filters->get('orderBy') ?: 'asc';
            $query->orderBy($field, $orderBy);
        } else {
            $query->orderByPriority();
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
    public function incrementUsage()
    {
        $this->increment('usage_count');
    }

    public function recordSuccess()
    {
        $this->increment('success_count');
        $this->updateSuccessRate();
    }

    public function recordFailure()
    {
        // Usage count is already incremented, just update success rate
        $this->updateSuccessRate();
    }

    public function updateSuccessRate()
    {
        if ($this->usage_count > 0) {
            $newRate = ($this->success_count / $this->usage_count) * 100;
            $this->update(['success_rate' => $newRate]);
        }
    }

    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    public function isApplicable($sourceField, $entityType, $sourceSystem = null, $conditions = [])
    {
        // Check if rule is active
        if (!$this->is_active) {
            return false;
        }

        // Check entity type match
        if ($this->entity_type !== $entityType) {
            return false;
        }

        // Check source system match (null means generic rule)
        if ($this->source_system && $sourceSystem && $this->source_system !== $sourceSystem) {
            return false;
        }

        // Check source field match (exact or pattern)
        if (!$this->matchesSourceField($sourceField)) {
            return false;
        }

        // Check additional conditions
        if (!empty($this->conditions) && !$this->evaluateConditions($conditions)) {
            return false;
        }

        return true;
    }

    public function matchesSourceField($sourceField)
    {
        // Exact match
        if (strtolower($this->source_field) === strtolower($sourceField)) {
            return true;
        }

        // Check field variations
        if (!empty($this->field_variations)) {
            foreach ($this->field_variations as $variation) {
                if (strtolower($variation) === strtolower($sourceField)) {
                    return true;
                }
            }
        }

        // Check Macedonian patterns
        if (!empty($this->macedonian_patterns)) {
            foreach ($this->macedonian_patterns as $pattern) {
                if (preg_match('/' . $pattern . '/i', $sourceField)) {
                    return true;
                }
            }
        }

        // Check language variations
        if (!empty($this->language_variations)) {
            foreach ($this->language_variations as $variation) {
                if (strtolower($variation) === strtolower($sourceField)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function evaluateConditions($contextData)
    {
        if (empty($this->conditions)) {
            return true;
        }

        // Simple condition evaluation (can be extended)
        foreach ($this->conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? null;

            if (!$field || !isset($contextData[$field])) {
                continue;
            }

            $fieldValue = $contextData[$field];

            switch ($operator) {
                case '=':case '==':
                    if ($fieldValue != $value) return false;
                    break;
                case '!=':
                    if ($fieldValue == $value) return false;
                    break;
                case '>':
                    if ($fieldValue <= $value) return false;
                    break;
                case '<':
                    if ($fieldValue >= $value) return false;
                    break;
                case 'contains':
                    if (strpos(strtolower($fieldValue), strtolower($value)) === false) return false;
                    break;
                case 'regex':
                    if (!preg_match('/' . $value . '/i', $fieldValue)) return false;
                    break;
            }
        }

        return true;
    }

    public function transform($value, $contextData = [])
    {
        switch ($this->transformation_type) {
            case self::TRANSFORM_DIRECT:
                return $value;

            case self::TRANSFORM_REGEX:
                return $this->applyRegexTransformation($value);

            case self::TRANSFORM_LOOKUP:
                return $this->applyLookupTransformation($value);

            case self::TRANSFORM_CALCULATION:
                return $this->applyCalculationTransformation($value, $contextData);

            case self::TRANSFORM_DATE_FORMAT:
                return $this->applyDateFormatTransformation($value);

            case self::TRANSFORM_CURRENCY_CONVERT:
                return $this->applyCurrencyConversionTransformation($value);

            case self::TRANSFORM_SPLIT:
                return $this->applySplitTransformation($value);

            case self::TRANSFORM_COMBINE:
                return $this->applyCombineTransformation($value, $contextData);

            case self::TRANSFORM_CONDITIONAL:
                return $this->applyConditionalTransformation($value, $contextData);

            default:
                return $value;
        }
    }

    private function applyRegexTransformation($value)
    {
        $config = $this->transformation_config;
        $pattern = $config['pattern'] ?? null;
        $replacement = $config['replacement'] ?? '$1';

        if (!$pattern) {
            return $value;
        }

        return preg_replace('/' . $pattern . '/i', $replacement, $value);
    }

    private function applyLookupTransformation($value)
    {
        $config = $this->transformation_config;
        $lookupTable = $config['lookup_table'] ?? [];

        $normalizedValue = strtolower(trim($value));
        
        foreach ($lookupTable as $key => $mappedValue) {
            if (strtolower($key) === $normalizedValue) {
                return $mappedValue;
            }
        }

        return $config['default_value'] ?? $value;
    }

    private function applyCalculationTransformation($value, $contextData)
    {
        $config = $this->transformation_config;
        $formula = $config['formula'] ?? '';

        // Simple formula evaluation (extend as needed)
        // For now, support basic arithmetic operations
        if (is_numeric($value)) {
            $multiplier = $config['multiplier'] ?? 1;
            $offset = $config['offset'] ?? 0;
            
            return ($value * $multiplier) + $offset;
        }

        return $value;
    }

    private function applyDateFormatTransformation($value)
    {
        $config = $this->transformation_config;
        $inputFormat = $config['input_format'] ?? 'd.m.Y';
        $outputFormat = $config['output_format'] ?? 'Y-m-d';

        try {
            $date = Carbon::createFromFormat($inputFormat, $value);
            return $date->format($outputFormat);
        } catch (\Exception $e) {
            // Try common European formats
            $commonFormats = ['d.m.Y', 'd/m/Y', 'd-m-Y', 'Y-m-d', 'm/d/Y'];
            
            foreach ($commonFormats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $value);
                    return $date->format($outputFormat);
                } catch (\Exception $e) {
                    continue;
                }
            }
            
            return $value;
        }
    }

    private function applyCurrencyConversionTransformation($value)
    {
        $config = $this->transformation_config;
        $fromCurrency = $config['from_currency'] ?? 'MKD';
        $toCurrency = $config['to_currency'] ?? 'EUR';
        $rate = $config['exchange_rate'] ?? 1.0;

        if (is_numeric($value)) {
            return round($value * $rate, 2);
        }

        return $value;
    }

    private function applySplitTransformation($value)
    {
        $config = $this->transformation_config;
        $delimiter = $config['delimiter'] ?? ' ';
        $part = $config['part'] ?? 0; // 0 = first part, 1 = second part, etc.

        $parts = explode($delimiter, $value);
        
        return $parts[$part] ?? $value;
    }

    private function applyCombineTransformation($value, $contextData)
    {
        $config = $this->transformation_config;
        $fields = $config['fields'] ?? [];
        $separator = $config['separator'] ?? ' ';

        $values = [$value]; // Start with current value
        
        foreach ($fields as $field) {
            if (isset($contextData[$field])) {
                $values[] = $contextData[$field];
            }
        }

        return implode($separator, array_filter($values));
    }

    private function applyConditionalTransformation($value, $contextData)
    {
        $config = $this->transformation_config;
        $conditions = $config['conditions'] ?? [];

        foreach ($conditions as $condition) {
            if ($this->evaluateCondition($condition, $value, $contextData)) {
                return $condition['result'] ?? $value;
            }
        }

        return $config['default'] ?? $value;
    }

    private function evaluateCondition($condition, $value, $contextData)
    {
        $operator = $condition['operator'] ?? '=';
        $compareValue = $condition['value'] ?? null;

        switch ($operator) {
            case '=': case '==':
                return $value == $compareValue;
            case '!=':
                return $value != $compareValue;
            case 'contains':
                return strpos(strtolower($value), strtolower($compareValue)) !== false;
            case 'regex':
                return preg_match('/' . $compareValue . '/i', $value);
            default:
                return false;
        }
    }

    public function runTestCases()
    {
        if (empty($this->test_cases)) {
            return ['status' => 'no_tests', 'results' => []];
        }

        $results = [];
        $passedCount = 0;

        foreach ($this->test_cases as $testCase) {
            $input = $testCase['input'] ?? '';
            $expectedOutput = $testCase['expected_output'] ?? '';
            $context = $testCase['context'] ?? [];

            $actualOutput = $this->transform($input, $context);
            $passed = $actualOutput == $expectedOutput;

            if ($passed) {
                $passedCount++;
            }

            $results[] = [
                'input' => $input,
                'expected' => $expectedOutput,
                'actual' => $actualOutput,
                'passed' => $passed,
                'context' => $context,
            ];
        }

        return [
            'status' => 'completed',
            'total_tests' => count($this->test_cases),
            'passed_tests' => $passedCount,
            'failed_tests' => count($this->test_cases) - $passedCount,
            'success_rate' => round(($passedCount / count($this->test_cases)) * 100, 2),
            'results' => $results,
        ];
    }

    public static function getEntityTypeOptions()
    {
        return [
            self::ENTITY_CUSTOMER => 'Customer',
            self::ENTITY_INVOICE => 'Invoice',
            self::ENTITY_ITEM => 'Item',
            self::ENTITY_PAYMENT => 'Payment',
            self::ENTITY_EXPENSE => 'Expense',
        ];
    }

    public static function getTransformationTypeOptions()
    {
        return [
            self::TRANSFORM_DIRECT => 'Direct Mapping',
            self::TRANSFORM_REGEX => 'Regex Transformation',
            self::TRANSFORM_LOOKUP => 'Value Lookup',
            self::TRANSFORM_CALCULATION => 'Mathematical Calculation',
            self::TRANSFORM_DATE_FORMAT => 'Date Format Conversion',
            self::TRANSFORM_CURRENCY_CONVERT => 'Currency Conversion',
            self::TRANSFORM_SPLIT => 'Split Field',
            self::TRANSFORM_COMBINE => 'Combine Fields',
            self::TRANSFORM_CONDITIONAL => 'Conditional Mapping',
        ];
    }
}
