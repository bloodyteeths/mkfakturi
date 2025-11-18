<?php

namespace App\Services\Import\Intelligent;

use App\Models\MappingRule;
use Carbon\Carbon;
use Exception;

/**
 * AdaptiveValidator - Dynamic data validation service
 *
 * Validates imported records based on:
 * - Data type (not field name)
 * - Business rules from database
 * - Field requirements from MappingRule
 *
 * NO hardcoded validation rules!
 */
class AdaptiveValidator
{
    /**
     * Cached mapping rules to avoid repeated DB queries
     */
    private array $mappingRulesCache = [];

    /**
     * Company ID for scoped validation rules
     */
    private ?int $companyId;

    /**
     * Constructor
     *
     * @param  int|null  $companyId  Company ID for scoped rules
     */
    public function __construct(?int $companyId = null)
    {
        $this->companyId = $companyId;
    }

    /**
     * Validate a record dynamically
     *
     * @param  array  $record  Mapped record data (CSV field => value)
     * @param  array  $fieldMappings  (CSV field => target field)
     * @param  int  $rowNumber  Row number for error messages
     * @return array ['errors' => [], 'warnings' => []]
     */
    public function validate(
        array $record,
        array $fieldMappings,
        int $rowNumber
    ): array {
        $errors = [];
        $warnings = [];

        // Validate each mapped field
        foreach ($fieldMappings as $csvField => $targetField) {
            $value = $record[$csvField] ?? null;

            // Load mapping rule for this target field
            $rule = $this->getMappingRule($targetField);

            if (! $rule) {
                // No validation rules defined for this field
                continue;
            }

            // 1. Required field check
            $requiredValidation = $this->validateRequired($value, $targetField, $rule, $rowNumber);
            $errors = array_merge($errors, $requiredValidation['errors']);
            $warnings = array_merge($warnings, $requiredValidation['warnings']);

            // Skip further validation if field is empty and not required
            if (empty($value) && ! ($rule->validation_rules['required'] ?? false)) {
                continue;
            }

            // 2. Data type validation
            $typeValidation = $this->validateByDataType($value, $targetField, $rule, $rowNumber);
            $errors = array_merge($errors, $typeValidation['errors']);
            $warnings = array_merge($warnings, $typeValidation['warnings']);

            // 3. Business rules validation
            if (! empty($rule->business_rules)) {
                $businessValidation = $this->applyBusinessRules($value, $targetField, $rule->business_rules, $rowNumber);
                $errors = array_merge($errors, $businessValidation['errors']);
                $warnings = array_merge($warnings, $businessValidation['warnings']);
            }
        }

        // 4. Cross-field validation (optional advanced validation)
        $crossFieldValidation = $this->validateCrossFields($record, $fieldMappings, $rowNumber);
        $errors = array_merge($errors, $crossFieldValidation['errors']);
        $warnings = array_merge($warnings, $crossFieldValidation['warnings']);

        return [
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Validate required field
     *
     * @param  mixed  $value  Field value
     * @param  string  $targetField  Target field name
     * @param  MappingRule  $rule  Mapping rule
     * @param  int  $rowNumber  Row number
     */
    private function validateRequired($value, string $targetField, MappingRule $rule, int $rowNumber): array
    {
        $errors = [];
        $warnings = [];

        $isRequired = $rule->validation_rules['required'] ?? false;

        if ($isRequired && $this->isEmpty($value)) {
            $errors[] = "Row {$rowNumber}: Required field '{$targetField}' is empty";
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Validate by data type
     *
     * @param  mixed  $value  Field value
     * @param  string  $targetField  Target field name
     * @param  MappingRule  $rule  Mapping rule
     * @param  int  $rowNumber  Row number
     */
    private function validateByDataType($value, string $targetField, MappingRule $rule, int $rowNumber): array
    {
        $errors = [];
        $warnings = [];

        $dataType = $rule->validation_rules['type'] ?? 'string';

        switch (strtolower($dataType)) {
            case 'email':
                if (! $this->isValidEmail($value)) {
                    $errors[] = "Row {$rowNumber}: Invalid email format in '{$targetField}': {$value}";
                }
                break;

            case 'phone':
                if (! $this->isValidPhone($value)) {
                    $warnings[] = "Row {$rowNumber}: Phone number may be invalid in '{$targetField}': {$value}";
                }
                break;

            case 'date':
                if (! $this->isValidDate($value)) {
                    $errors[] = "Row {$rowNumber}: Invalid date in '{$targetField}': {$value}";
                }
                break;

            case 'number':
            case 'integer':
                if (! $this->isValidNumber($value, 'integer')) {
                    $errors[] = "Row {$rowNumber}: Must be a number in '{$targetField}': {$value}";
                }
                break;

            case 'decimal':
            case 'float':
                if (! $this->isValidNumber($value, 'decimal')) {
                    $errors[] = "Row {$rowNumber}: Must be a decimal number in '{$targetField}': {$value}";
                }
                break;

            case 'url':
                if (! $this->isValidUrl($value)) {
                    $warnings[] = "Row {$rowNumber}: Invalid URL in '{$targetField}': {$value}";
                }
                break;

            case 'boolean':
                if (! $this->isValidBoolean($value)) {
                    $warnings[] = "Row {$rowNumber}: Invalid boolean value in '{$targetField}': {$value}";
                }
                break;

            case 'string':
            default:
                // String validation handled by business rules (min_length, max_length)
                break;
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Apply business rules validation
     *
     * @param  mixed  $value  Field value
     * @param  string  $targetField  Target field name
     * @param  array  $rules  Business rules
     * @param  int  $rowNumber  Row number
     */
    private function applyBusinessRules($value, string $targetField, array $rules, int $rowNumber): array
    {
        $errors = [];
        $warnings = [];

        // Min value validation (for numeric fields)
        if (isset($rules['min_value']) && is_numeric($value)) {
            if ($value < $rules['min_value']) {
                $errors[] = "Row {$rowNumber}: Value in '{$targetField}' must be at least {$rules['min_value']}, got: {$value}";
            }
        }

        // Max value validation (for numeric fields)
        if (isset($rules['max_value']) && is_numeric($value)) {
            if ($value > $rules['max_value']) {
                $errors[] = "Row {$rowNumber}: Value in '{$targetField}' must not exceed {$rules['max_value']}, got: {$value}";
            }
        }

        // Regex pattern validation
        if (isset($rules['regex']) && ! empty($rules['regex'])) {
            try {
                if (! preg_match('/'.$rules['regex'].'/', $value)) {
                    $errors[] = "Row {$rowNumber}: Value in '{$targetField}' does not match required pattern: {$value}";
                }
            } catch (Exception $e) {
                // Invalid regex pattern - log warning but don't fail validation
                $warnings[] = "Row {$rowNumber}: Invalid regex pattern for '{$targetField}'";
            }
        }

        // Enum validation (allowed values list)
        if (isset($rules['enum']) && is_array($rules['enum']) && ! empty($rules['enum'])) {
            $normalizedValue = $this->normalizeString($value);
            $normalizedEnum = array_map([$this, 'normalizeString'], $rules['enum']);

            if (! in_array($normalizedValue, $normalizedEnum, true)) {
                $allowedValues = implode(', ', $rules['enum']);
                $errors[] = "Row {$rowNumber}: Value in '{$targetField}' must be one of: {$allowedValues}, got: {$value}";
            }
        }

        // Min length validation (for strings)
        if (isset($rules['min_length']) && is_string($value)) {
            $length = mb_strlen($value, 'UTF-8');
            if ($length < $rules['min_length']) {
                $errors[] = "Row {$rowNumber}: Value in '{$targetField}' must be at least {$rules['min_length']} characters, got {$length}";
            }
        }

        // Max length validation (for strings)
        if (isset($rules['max_length']) && is_string($value)) {
            $length = mb_strlen($value, 'UTF-8');
            if ($length > $rules['max_length']) {
                $errors[] = "Row {$rowNumber}: Value in '{$targetField}' must not exceed {$rules['max_length']} characters, got {$length}";
            }
        }

        // Unique validation (future implementation - would require database check)
        if (isset($rules['unique']) && $rules['unique'] === true) {
            $warnings[] = "Row {$rowNumber}: Uniqueness check for '{$targetField}' not yet implemented";
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Validate cross-field relationships
     *
     * @param  array  $record  Full record data
     * @param  array  $fieldMappings  Field mappings
     * @param  int  $rowNumber  Row number
     */
    private function validateCrossFields(array $record, array $fieldMappings, int $rowNumber): array
    {
        $errors = [];
        $warnings = [];

        // Get target field values
        $targetValues = [];
        foreach ($fieldMappings as $csvField => $targetField) {
            $targetValues[$targetField] = $record[$csvField] ?? null;
        }

        // Validate: subtotal + tax = total
        if (isset($targetValues['subtotal'], $targetValues['tax'], $targetValues['total'])) {
            $subtotal = (float) $targetValues['subtotal'];
            $tax = (float) $targetValues['tax'];
            $total = (float) $targetValues['total'];

            $calculatedTotal = $subtotal + $tax;
            $difference = abs($calculatedTotal - $total);

            // Allow small rounding differences (0.01)
            if ($difference > 0.01) {
                $warnings[] = "Row {$rowNumber}: Total ({$total}) does not match subtotal + tax ({$calculatedTotal})";
            }
        }

        // Validate: due_date >= invoice_date
        if (isset($targetValues['invoice_date'], $targetValues['due_date'])) {
            try {
                $invoiceDate = Carbon::parse($targetValues['invoice_date']);
                $dueDate = Carbon::parse($targetValues['due_date']);

                if ($dueDate->lt($invoiceDate)) {
                    $warnings[] = "Row {$rowNumber}: Due date is before invoice date";
                }
            } catch (Exception $e) {
                // Date parsing failed - already handled by data type validation
            }
        }

        // Validate: quantity * unit_price = line_total
        if (isset($targetValues['quantity'], $targetValues['unit_price'], $targetValues['line_total'])) {
            $quantity = (float) $targetValues['quantity'];
            $unitPrice = (float) $targetValues['unit_price'];
            $lineTotal = (float) $targetValues['line_total'];

            $calculatedLineTotal = $quantity * $unitPrice;
            $difference = abs($calculatedLineTotal - $lineTotal);

            // Allow small rounding differences (0.01)
            if ($difference > 0.01) {
                $warnings[] = "Row {$rowNumber}: Line total ({$lineTotal}) does not match quantity × unit price ({$calculatedLineTotal})";
            }
        }

        // Validate: negative values warning for totals
        foreach (['total', 'subtotal', 'amount', 'price', 'unit_price'] as $field) {
            if (isset($targetValues[$field]) && is_numeric($targetValues[$field])) {
                $value = (float) $targetValues[$field];
                if ($value < 0) {
                    $warnings[] = "Row {$rowNumber}: Negative value for '{$field}': {$value}";
                }
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Get mapping rule for target field (with caching)
     *
     * @param  string  $targetField  Target field name
     */
    private function getMappingRule(string $targetField): ?MappingRule
    {
        // Check cache
        if (isset($this->mappingRulesCache[$targetField])) {
            return $this->mappingRulesCache[$targetField];
        }

        // Load from database
        $query = MappingRule::where('target_field', $targetField)
            ->active();

        if ($this->companyId) {
            $query->whereCompany($this->companyId);
        }

        $rule = $query->first();

        // Cache the result (even if null)
        $this->mappingRulesCache[$targetField] = $rule;

        return $rule;
    }

    /**
     * Check if value is empty
     *
     * @param  mixed  $value  Value to check
     */
    private function isEmpty($value): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value) && trim($value) === '') {
            return true;
        }

        if (is_array($value) && empty($value)) {
            return true;
        }

        return false;
    }

    /**
     * Validate email format
     * Supports internationalized domain names (IDN) and Macedonian Cyrillic characters
     *
     * @param  string  $value  Email address
     */
    private function isValidEmail(string $value): bool
    {
        // Basic email validation
        if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
            // Try with IDN (Internationalized Domain Names) support
            if (function_exists('idn_to_ascii')) {
                $parts = explode('@', $value);
                if (count($parts) === 2) {
                    $local = $parts[0];
                    $domain = $parts[1];

                    // Convert domain to ASCII
                    $asciiDomain = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);

                    if ($asciiDomain !== false) {
                        $asciiEmail = $local.'@'.$asciiDomain;

                        return filter_var($asciiEmail, FILTER_VALIDATE_EMAIL) !== false;
                    }
                }
            }

            return false;
        }

        return true;
    }

    /**
     * Validate phone number format
     * Supports international formats and Macedonian numbers
     *
     * @param  string  $value  Phone number
     */
    private function isValidPhone(string $value): bool
    {
        // Remove common separators for validation
        $cleaned = preg_replace('/[\s\-().\/]/', '', $value);

        // Check if it contains only valid phone characters
        if (! preg_match('/^[+]?[0-9]{7,20}$/', $cleaned)) {
            return false;
        }

        // Minimum 7 digits, maximum 20 (including country code)
        return strlen($cleaned) >= 7 && strlen($cleaned) <= 20;
    }

    /**
     * Validate date format
     * Supports multiple date formats
     *
     * @param  string  $value  Date string
     */
    private function isValidDate(string $value): bool
    {
        try {
            Carbon::parse($value);

            return true;
        } catch (Exception $e) {
            // Try common date formats
            $formats = [
                'Y-m-d',           // ISO format
                'd.m.Y',           // European format
                'd/m/Y',           // Alternative European
                'd-m-Y',           // Alternative European
                'm/d/Y',           // US format
                'Y/m/d',           // Alternative ISO
                'd.m.y',           // Short year European
                'd/m/y',           // Short year European
            ];

            foreach ($formats as $format) {
                try {
                    Carbon::createFromFormat($format, $value);

                    return true;
                } catch (Exception $e) {
                    continue;
                }
            }

            return false;
        }
    }

    /**
     * Validate number format
     *
     * @param  mixed  $value  Value to check
     * @param  string  $type  'integer' or 'decimal'
     */
    private function isValidNumber($value, string $type = 'decimal'): bool
    {
        // Handle European decimal separator (comma)
        if (is_string($value)) {
            // Replace comma with dot for decimal parsing
            $normalizedValue = str_replace(',', '.', $value);

            // Remove thousand separators
            $normalizedValue = str_replace([' ', "'"], '', $normalizedValue);

            $value = $normalizedValue;
        }

        if ($type === 'integer') {
            return filter_var($value, FILTER_VALIDATE_INT) !== false;
        }

        return is_numeric($value);
    }

    /**
     * Validate URL format
     *
     * @param  string  $value  URL
     */
    private function isValidUrl(string $value): bool
    {
        // Basic URL validation
        $valid = filter_var($value, FILTER_VALIDATE_URL) !== false;

        if (! $valid) {
            // Try with http:// prefix if missing
            if (! preg_match('/^https?:\/\//', $value)) {
                $valid = filter_var('http://'.$value, FILTER_VALIDATE_URL) !== false;
            }
        }

        return $valid;
    }

    /**
     * Validate boolean value
     *
     * @param  mixed  $value  Value to check
     */
    private function isValidBoolean($value): bool
    {
        $normalizedValue = $this->normalizeString($value);

        $trueValues = ['true', '1', 'yes', 'да', 'y', 'on'];
        $falseValues = ['false', '0', 'no', 'не', 'n', 'off'];

        $allValidValues = array_merge($trueValues, $falseValues);

        return in_array($normalizedValue, $allValidValues, true);
    }

    /**
     * Normalize string for comparison
     * Handles Macedonian Cyrillic characters
     *
     * @param  mixed  $value  Value to normalize
     */
    private function normalizeString($value): string
    {
        if (! is_string($value)) {
            $value = (string) $value;
        }

        // Convert to lowercase (handles Cyrillic)
        $normalized = mb_strtolower($value, 'UTF-8');

        // Trim whitespace
        $normalized = trim($normalized);

        return $normalized;
    }

    /**
     * Clear the mapping rules cache
     * Useful when rules are updated during validation
     */
    public function clearCache(): void
    {
        $this->mappingRulesCache = [];
    }

    /**
     * Set company ID for scoped validation
     *
     * @param  int|null  $companyId  Company ID
     */
    public function setCompanyId(?int $companyId): void
    {
        $this->companyId = $companyId;
        $this->clearCache(); // Clear cache when company changes
    }
}

// CLAUDE-CHECKPOINT
