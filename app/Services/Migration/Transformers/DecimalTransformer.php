<?php

namespace App\Services\Migration\Transformers;

use Exception;
use Illuminate\Support\Collection;

/**
 * DecimalTransformer - Handle decimal separator conversion for Macedonia accounting data
 * 
 * This transformer handles the conversion of Macedonian decimal formats (using comma as separator)
 * to standardized dot notation for database storage. It's designed to work with data from
 * Macedonian accounting systems like Onivo, Megasoft, and Pantheon.
 * 
 * Common Macedonian decimal formats handled:
 * - 1.234,56 (European format with thousands separator)
 * - 1234,56 (European format without thousands separator)
 * - 1,234.56 (US format - sometimes mixed in exports)
 * - 1234.56 (Standard decimal format)
 * - 1 234,56 (Space as thousands separator)
 * 
 * Features:
 * - Handles multiple European decimal formats
 * - Batch transformation for performance
 * - Comprehensive error handling and validation
 * - Reversible transformations
 * - Edge case handling (negative numbers, percentages)
 * - Support for empty/null values
 * - Precision preservation
 * 
 * @package App\Services\Migration\Transformers
 */
class DecimalTransformer
{
    /**
     * Default decimal precision for rounding
     */
    private const DEFAULT_PRECISION = 2;

    /**
     * Maximum supported decimal precision
     */
    private const MAX_PRECISION = 8;

    /**
     * Transform a Macedonian decimal string to standard dot notation
     * 
     * @param string|null $decimalString The Macedonian formatted decimal
     * @param int $precision Number of decimal places to preserve (default: 2)
     * @return string|null Standard decimal format or NULL if invalid/empty
     * @throws Exception If decimal is malformed and strict mode is enabled
     */
    public function transform(?string $decimalString, int $precision = self::DEFAULT_PRECISION): ?string
    {
        // Handle null or empty input
        if (empty($decimalString) || trim($decimalString) === '') {
            return null;
        }

        $cleanedString = $this->cleanDecimalString($decimalString);
        
        // Handle edge cases
        if ($this->isInvalidDecimal($cleanedString)) {
            return null;
        }

        try {
            $normalizedDecimal = $this->normalizeDecimalFormat($cleanedString);
            $floatValue = (float) $normalizedDecimal;
            
            // Validate the conversion was successful
            if (!is_finite($floatValue)) {
                throw new Exception("Invalid decimal conversion result");
            }
            
            // Apply precision rounding
            $precision = min($precision, self::MAX_PRECISION);
            return number_format($floatValue, $precision, '.', '');
            
        } catch (Exception $e) {
            logger()->warning('DecimalTransformer: Unable to parse decimal', [
                'input' => $decimalString,
                'cleaned' => $cleanedString ?? null,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Transform multiple decimals in batch for better performance
     * 
     * @param array $decimals Array of Macedonian formatted decimal strings
     * @param int $precision Number of decimal places to preserve
     * @return array Array of standard decimal formats with same keys
     */
    public function transformBatch(array $decimals, int $precision = self::DEFAULT_PRECISION): array
    {
        $transformed = [];
        
        foreach ($decimals as $key => $decimalString) {
            $transformed[$key] = $this->transform($decimalString, $precision);
        }
        
        return $transformed;
    }

    /**
     * Transform decimals in a collection while preserving structure
     * 
     * @param Collection $collection Collection containing decimal data
     * @param string|array $decimalFields The field name(s) containing decimals to transform
     * @param int $precision Number of decimal places to preserve
     * @return Collection Transformed collection
     */
    public function transformCollection(Collection $collection, $decimalFields, int $precision = self::DEFAULT_PRECISION): Collection
    {
        $fields = is_array($decimalFields) ? $decimalFields : [$decimalFields];
        
        return $collection->map(function ($item) use ($fields, $precision) {
            foreach ($fields as $field) {
                if (is_array($item)) {
                    $item[$field] = $this->transform($item[$field] ?? null, $precision);
                } elseif (is_object($item) && property_exists($item, $field)) {
                    $item->{$field} = $this->transform($item->{$field}, $precision);
                }
            }
            
            return $item;
        });
    }

    /**
     * Reverse transform: Convert standard decimal to Macedonian format (comma separator)
     * 
     * @param string|float|null $standardDecimal Standard decimal format
     * @param bool $useThousandsSeparator Whether to include thousands separator
     * @param string $thousandsSeparator Thousands separator character (default: '.')
     * @return string|null Macedonian formatted decimal or NULL if invalid
     */
    public function reverse($standardDecimal, bool $useThousandsSeparator = true, string $thousandsSeparator = '.'): ?string
    {
        if (empty($standardDecimal) && $standardDecimal !== 0 && $standardDecimal !== '0') {
            return null;
        }

        try {
            $floatValue = (float) $standardDecimal;
            
            if (!is_finite($floatValue)) {
                return null;
            }
            
            // Determine decimal places in original number
            $decimalPlaces = $this->countDecimalPlaces((string) $standardDecimal);
            
            if ($useThousandsSeparator) {
                // European format: thousands separator (.) and comma for decimals
                return number_format($floatValue, $decimalPlaces, ',', $thousandsSeparator);
            } else {
                // Simple comma format without thousands separator
                return number_format($floatValue, $decimalPlaces, ',', '');
            }
            
        } catch (Exception $e) {
            logger()->warning('DecimalTransformer: Unable to reverse transform decimal', [
                'input' => $standardDecimal,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Validate if a string looks like a valid decimal number
     * 
     * @param string $decimalString Decimal string to validate
     * @return bool True if it appears to be a valid decimal format
     */
    public function isValidDecimal(string $decimalString): bool
    {
        if (empty(trim($decimalString))) {
            return false;
        }

        $cleaned = $this->cleanDecimalString($decimalString);
        
        if ($this->isInvalidDecimal($cleaned)) {
            return false;
        }

        try {
            $normalized = $this->normalizeDecimalFormat($cleaned);
            $floatValue = (float) $normalized;
            return is_finite($floatValue);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Clean and normalize decimal string before transformation
     * Removes extra whitespace, handles common formatting issues
     * 
     * @param string $decimalString Raw decimal string
     * @return string Cleaned decimal string
     */
    private function cleanDecimalString(string $decimalString): string
    {
        // Remove extra whitespace
        $cleaned = trim($decimalString);
        
        // Handle negative signs and parentheses for negative numbers
        $isNegative = false;
        if (str_starts_with($cleaned, '-') || 
            (str_starts_with($cleaned, '(') && str_ends_with($cleaned, ')'))) {
            $isNegative = true;
            $cleaned = trim($cleaned, '()- ');
        }
        
        // Remove currency symbols and percent signs (common in exports)
        $cleaned = preg_replace('/[€$£¥%\s]/', '', $cleaned);
        
        // Handle multiple consecutive separators
        $cleaned = preg_replace('/[.,]{2,}/', '.', $cleaned);
        
        // Restore negative sign if needed
        if ($isNegative) {
            $cleaned = '-' . $cleaned;
        }
        
        return $cleaned;
    }

    /**
     * Normalize decimal format to standard dot notation
     * Handles various European decimal formats
     * 
     * @param string $decimalString Cleaned decimal string
     * @return string Normalized decimal string
     */
    private function normalizeDecimalFormat(string $decimalString): string
    {
        // Handle negative numbers
        $isNegative = str_starts_with($decimalString, '-');
        $workingString = ltrim($decimalString, '-');
        
        // Count commas and dots to determine format
        $commaCount = substr_count($workingString, ',');
        $dotCount = substr_count($workingString, '.');
        
        // Case 1: European format with comma as decimal separator
        // Examples: 1.234,56 or 1234,56 or 1 234,56
        if ($commaCount === 1 && $dotCount >= 0) {
            $parts = explode(',', $workingString);
            if (count($parts) === 2) {
                // Remove dots and spaces from integer part (thousands separators)
                $integerPart = str_replace(['.', ' '], '', $parts[0]);
                $decimalPart = $parts[1];
                $normalized = $integerPart . '.' . $decimalPart;
            } else {
                $normalized = $workingString;
            }
        }
        // Case 2: US format with dot as decimal separator and comma as thousands
        // Examples: 1,234.56
        elseif ($dotCount === 1 && $commaCount >= 0) {
            $parts = explode('.', $workingString);
            if (count($parts) === 2) {
                // Check if this looks like US format (comma before dot)
                $lastCommaPos = strrpos($parts[0], ',');
                $lastCommaDistance = strlen($parts[0]) - $lastCommaPos - 1;
                
                if ($lastCommaPos !== false && $lastCommaDistance === 3) {
                    // US format: remove commas from integer part
                    $integerPart = str_replace(',', '', $parts[0]);
                    $decimalPart = $parts[1];
                    $normalized = $integerPart . '.' . $decimalPart;
                } else {
                    // Already in standard format
                    $normalized = $workingString;
                }
            } else {
                $normalized = $workingString;
            }
        }
        // Case 3: Integer or already standard format
        else {
            // Remove any remaining separators that might be thousands separators
            if ($commaCount === 0 && $dotCount > 1) {
                // Multiple dots likely thousands separator: 1.234.567
                $parts = explode('.', $workingString);
                $lastPart = array_pop($parts);
                if (strlen($lastPart) <= 3) {
                    // Last part is decimal
                    $normalized = implode('', $parts) . '.' . $lastPart;
                } else {
                    // All are thousands separators
                    $normalized = str_replace('.', '', $workingString);
                }
            } else {
                $normalized = str_replace([' ', ','], ['', ''], $workingString);
            }
        }
        
        // Restore negative sign
        return $isNegative ? '-' . $normalized : $normalized;
    }

    /**
     * Check if a decimal string is invalid or represents special cases
     * 
     * @param string $decimalString Cleaned decimal string
     * @return bool True if the decimal should be considered invalid
     */
    private function isInvalidDecimal(string $decimalString): bool
    {
        // Handle empty or placeholder values common in exports
        $invalidValues = ['', '0', '-', 'NULL', 'null', 'N/A', 'n/a', '#N/A'];
        
        return in_array(trim($decimalString), $invalidValues, true);
    }

    /**
     * Count decimal places in a number string
     * 
     * @param string $numberString Number as string
     * @return int Number of decimal places
     */
    private function countDecimalPlaces(string $numberString): int
    {
        $parts = explode('.', $numberString);
        return count($parts) > 1 ? strlen($parts[1]) : 0;
    }

    /**
     * Get statistics about decimal transformation results
     * 
     * @param array $originalDecimals Array of original decimal strings
     * @param array $transformedDecimals Array of transformed decimals
     * @return array Statistics array with success/failure counts
     */
    public function getTransformationStats(array $originalDecimals, array $transformedDecimals): array
    {
        $total = count($originalDecimals);
        $successful = count(array_filter($transformedDecimals, function ($decimal) {
            return $decimal !== null;
        }));
        $failed = $total - $successful;
        $emptyInput = count(array_filter($originalDecimals, function ($decimal) {
            return empty($decimal);
        }));

        return [
            'total' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'empty_input' => $emptyInput,
            'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
        ];
    }

    /**
     * Handle special decimal cases common in Macedonian accounting exports
     * 
     * @param string $decimalString Decimal string to process
     * @return string|null Processed decimal string or null if invalid
     */
    public function handleSpecialCases(string $decimalString): ?string
    {
        $cleaned = trim($decimalString);
        
        // Handle percentage values (convert to decimal)
        if (str_ends_with($cleaned, '%')) {
            $numberPart = rtrim($cleaned, '%');
            $decimal = $this->transform($numberPart);
            if ($decimal !== null) {
                return (string) ((float) $decimal / 100);
            }
        }
        
        // Handle currency amounts with symbols
        $currencyPatterns = ['/€\s*/', '/\$\s*/', '/MKD\s*/', '/денари\s*/i'];
        foreach ($currencyPatterns as $pattern) {
            if (preg_match($pattern, $cleaned)) {
                $numberPart = preg_replace($pattern, '', $cleaned);
                return $this->transform($numberPart);
            }
        }
        
        return $this->transform($cleaned);
    }

    /**
     * Validate decimal precision and range for Macedonia business context
     * 
     * @param string $decimal Decimal string to validate
     * @param float $minValue Minimum allowed value
     * @param float $maxValue Maximum allowed value
     * @param int $maxDecimalPlaces Maximum decimal places allowed
     * @return array Validation result with status and messages
     */
    public function validateBusinessDecimal(
        string $decimal, 
        float $minValue = -999999999.99, 
        float $maxValue = 999999999.99, 
        int $maxDecimalPlaces = 2
    ): array {
        $result = [
            'valid' => true,
            'messages' => [],
            'value' => null
        ];
        
        $transformed = $this->transform($decimal);
        
        if ($transformed === null) {
            $result['valid'] = false;
            $result['messages'][] = 'Invalid decimal format';
            return $result;
        }
        
        $floatValue = (float) $transformed;
        $result['value'] = $floatValue;
        
        // Check range
        if ($floatValue < $minValue || $floatValue > $maxValue) {
            $result['valid'] = false;
            $result['messages'][] = "Value must be between {$minValue} and {$maxValue}";
        }
        
        // Check decimal places
        $decimalPlaces = $this->countDecimalPlaces($transformed);
        if ($decimalPlaces > $maxDecimalPlaces) {
            $result['valid'] = false;
            $result['messages'][] = "Maximum {$maxDecimalPlaces} decimal places allowed";
        }
        
        return $result;
    }
}