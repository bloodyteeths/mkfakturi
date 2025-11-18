<?php

namespace App\Services\Import\Intelligent;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;

/**
 * Field Analyzer Service
 *
 * Analyzes CSV fields and sample data to detect data types,
 * statistical properties, and field characteristics.
 */
class FieldAnalyzer
{
    /**
     * Minimum ratio for email type detection
     */
    private const EMAIL_THRESHOLD = 0.80;

    /**
     * Minimum ratio for phone type detection
     */
    private const PHONE_THRESHOLD = 0.70;

    /**
     * Minimum ratio for date type detection
     */
    private const DATE_THRESHOLD = 0.70;

    /**
     * Minimum ratio for URL type detection
     */
    private const URL_THRESHOLD = 0.80;

    /**
     * Minimum ratio for number type detection
     */
    private const NUMBER_THRESHOLD = 0.80;

    /**
     * Number of examples to include in analysis result
     */
    private const EXAMPLE_COUNT = 3;

    /**
     * Analyze a field and its sample data
     *
     * @param  string  $fieldName  The field name to analyze
     * @param  array  $sampleData  Sample values from the field
     * @param  int  $position  The position/index of the field
     * @param  array  $allHeaders  All field headers for context
     * @return array Analysis results
     */
    public function analyze(
        string $fieldName,
        array $sampleData,
        int $position,
        array $allHeaders
    ): array {
        // Filter out empty values for analysis
        $nonEmptyValues = array_filter($sampleData, function ($value) {
            return ! is_null($value) && trim((string) $value) !== '';
        });

        $normalizedName = $this->normalize($fieldName);
        $dataType = $this->detectDataType($nonEmptyValues);
        $patterns = $this->detectPatterns($nonEmptyValues);

        return [
            'original_name' => $fieldName,
            'normalized_name' => $normalizedName,
            'position' => $position,
            'data_type' => $dataType,
            'uniqueness' => $this->calculateUniqueness($sampleData),
            'completeness' => $this->calculateCompleteness($sampleData),
            'patterns' => $patterns,
            'statistics' => $this->calculateStatistics($nonEmptyValues),
            'examples' => $this->getExamples($nonEmptyValues),
        ];
    }

    /**
     * Detect the data type from sample values
     *
     * @param  array  $values  Sample values to analyze
     * @return string Detected data type
     */
    public function detectDataType(array $values): string
    {
        if (empty($values)) {
            return 'string';
        }

        $total = count($values);

        // Count matches for each type
        $emailCount = 0;
        $phoneCount = 0;
        $dateCount = 0;
        $urlCount = 0;
        $numericCount = 0;

        foreach ($values as $value) {
            $strValue = trim((string) $value);

            if (empty($strValue)) {
                continue;
            }

            if (filter_var($strValue, FILTER_VALIDATE_EMAIL)) {
                $emailCount++;
            }

            if ($this->isPhone($strValue)) {
                $phoneCount++;
            }

            if ($this->isDate($strValue)) {
                $dateCount++;
            }

            if (filter_var($strValue, FILTER_VALIDATE_URL)) {
                $urlCount++;
            }

            if (is_numeric($strValue)) {
                $numericCount++;
            }
        }

        // Check thresholds in priority order
        if ($emailCount / $total >= self::EMAIL_THRESHOLD) {
            return 'email';
        }

        if ($urlCount / $total >= self::URL_THRESHOLD) {
            return 'url';
        }

        if ($dateCount / $total >= self::DATE_THRESHOLD) {
            return 'date';
        }

        if ($phoneCount / $total >= self::PHONE_THRESHOLD) {
            return 'phone';
        }

        if ($numericCount / $total >= self::NUMBER_THRESHOLD) {
            return 'number';
        }

        return 'string';
    }

    /**
     * Calculate uniqueness ratio
     *
     * @param  array  $values  Values to analyze
     * @return float Uniqueness ratio (0.0 to 1.0)
     */
    private function calculateUniqueness(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        $total = count($values);
        $unique = count(array_unique($values));

        return round($unique / $total, 4);
    }

    /**
     * Calculate completeness ratio
     *
     * @param  array  $values  Values to analyze
     * @return float Completeness ratio (0.0 to 1.0)
     */
    private function calculateCompleteness(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        $total = count($values);
        $nonEmpty = count(array_filter($values, function ($value) {
            return ! is_null($value) && trim((string) $value) !== '';
        }));

        return round($nonEmpty / $total, 4);
    }

    /**
     * Detect patterns in the values
     *
     * @param  array  $values  Values to analyze
     * @return array Detected patterns
     */
    private function detectPatterns(array $values): array
    {
        if (empty($values)) {
            return [];
        }

        $patterns = [];
        $total = count($values);

        // Check for various patterns
        $numericOnly = 0;
        $alphaOnly = 0;
        $alphanumeric = 0;
        $uppercase = 0;
        $lowercase = 0;
        $dashSeparated = 0;
        $underscoreSeparated = 0;
        $dateLike = 0;
        $currencyLike = 0;

        foreach ($values as $value) {
            $strValue = trim((string) $value);

            if (empty($strValue)) {
                continue;
            }

            if (ctype_digit($strValue)) {
                $numericOnly++;
            }

            if (ctype_alpha(str_replace([' ', '-', '_'], '', $strValue))) {
                $alphaOnly++;
            }

            if (ctype_alnum(str_replace([' ', '-', '_'], '', $strValue))) {
                $alphanumeric++;
            }

            if ($strValue === strtoupper($strValue) && ctype_alpha(str_replace([' ', '-', '_'], '', $strValue))) {
                $uppercase++;
            }

            if ($strValue === strtolower($strValue) && ctype_alpha(str_replace([' ', '-', '_'], '', $strValue))) {
                $lowercase++;
            }

            if (str_contains($strValue, '-')) {
                $dashSeparated++;
            }

            if (str_contains($strValue, '_')) {
                $underscoreSeparated++;
            }

            if ($this->isDate($strValue)) {
                $dateLike++;
            }

            if ($this->isCurrency($strValue)) {
                $currencyLike++;
            }
        }

        // Add patterns if they meet threshold (50% or more)
        $threshold = 0.5;

        if ($numericOnly / $total >= $threshold) {
            $patterns[] = 'numeric_only';
        }

        if ($alphaOnly / $total >= $threshold) {
            $patterns[] = 'alpha_only';
        }

        if ($alphanumeric / $total >= $threshold) {
            $patterns[] = 'alphanumeric';
        }

        if ($uppercase / $total >= $threshold) {
            $patterns[] = 'uppercase';
        }

        if ($lowercase / $total >= $threshold) {
            $patterns[] = 'lowercase';
        }

        if ($dashSeparated / $total >= $threshold) {
            $patterns[] = 'dash_separated';
        }

        if ($underscoreSeparated / $total >= $threshold) {
            $patterns[] = 'underscore_separated';
        }

        if ($dateLike / $total >= $threshold) {
            $patterns[] = 'date_like';
        }

        if ($currencyLike / $total >= $threshold) {
            $patterns[] = 'currency_like';
        }

        return $patterns;
    }

    /**
     * Calculate statistical properties
     *
     * @param  array  $values  Values to analyze
     * @return array Statistics
     */
    private function calculateStatistics(array $values): array
    {
        if (empty($values)) {
            return [
                'sample_count' => 0,
                'min_length' => 0,
                'max_length' => 0,
                'avg_length' => 0,
                'numeric_ratio' => 0,
                'alpha_ratio' => 0,
                'special_char_ratio' => 0,
            ];
        }

        $lengths = array_map(function ($value) {
            return mb_strlen((string) $value);
        }, $values);

        return [
            'sample_count' => count($values),
            'min_length' => min($lengths),
            'max_length' => max($lengths),
            'avg_length' => round(array_sum($lengths) / count($lengths), 2),
            'numeric_ratio' => round($this->calculateNumericRatio($values), 4),
            'alpha_ratio' => round($this->calculateAlphaRatio($values), 4),
            'special_char_ratio' => round($this->calculateSpecialCharRatio($values), 4),
        ];
    }

    /**
     * Calculate ratio of numeric characters
     *
     * @param  array  $values  Values to analyze
     * @return float Numeric ratio
     */
    private function calculateNumericRatio(array $values): float
    {
        $totalChars = 0;
        $numericChars = 0;

        foreach ($values as $value) {
            $strValue = (string) $value;
            $totalChars += mb_strlen($strValue);
            $numericChars += mb_strlen(preg_replace('/[^0-9]/', '', $strValue));
        }

        return $totalChars > 0 ? $numericChars / $totalChars : 0;
    }

    /**
     * Calculate ratio of alphabetic characters
     *
     * @param  array  $values  Values to analyze
     * @return float Alpha ratio
     */
    private function calculateAlphaRatio(array $values): float
    {
        $totalChars = 0;
        $alphaChars = 0;

        foreach ($values as $value) {
            $strValue = (string) $value;
            $totalChars += mb_strlen($strValue);
            $alphaChars += mb_strlen(preg_replace('/[^a-zA-Z]/', '', $strValue));
        }

        return $totalChars > 0 ? $alphaChars / $totalChars : 0;
    }

    /**
     * Calculate ratio of special characters
     *
     * @param  array  $values  Values to analyze
     * @return float Special character ratio
     */
    private function calculateSpecialCharRatio(array $values): float
    {
        $totalChars = 0;
        $specialChars = 0;

        foreach ($values as $value) {
            $strValue = (string) $value;
            $totalChars += mb_strlen($strValue);
            $specialChars += mb_strlen(preg_replace('/[a-zA-Z0-9]/', '', $strValue));
        }

        return $totalChars > 0 ? $specialChars / $totalChars : 0;
    }

    /**
     * Get example values
     *
     * @param  array  $values  Values to sample from
     * @return array Example values
     */
    private function getExamples(array $values): array
    {
        if (empty($values)) {
            return [];
        }

        // Get unique values first
        $uniqueValues = array_unique($values);

        // Take up to EXAMPLE_COUNT examples
        return array_slice(array_values($uniqueValues), 0, self::EXAMPLE_COUNT);
    }

    /**
     * Normalize a field name
     *
     * @param  string  $str  String to normalize
     * @return string Normalized string
     */
    public function normalize(string $str): string
    {
        // Convert to lowercase
        $normalized = mb_strtolower($str);

        // Remove special characters except underscore
        $normalized = preg_replace('/[^a-z0-9_\s]/', '', $normalized);

        // Replace spaces with underscores
        $normalized = preg_replace('/\s+/', '_', $normalized);

        // Replace multiple underscores with single
        $normalized = preg_replace('/_+/', '_', $normalized);

        // Trim underscores from start and end
        $normalized = trim($normalized, '_');

        return $normalized;
    }

    /**
     * Check if a value is a date
     *
     * @param  string  $value  Value to check
     * @return bool True if date
     */
    private function isDate(string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        try {
            // Try to parse with Carbon
            Carbon::parse($value);

            return true;
        } catch (InvalidFormatException $e) {
            // Not a valid date
        } catch (\Exception $e) {
            // Other parsing errors
        }

        // Check common date patterns
        $datePatterns = [
            '/^\d{4}-\d{2}-\d{2}$/',           // YYYY-MM-DD
            '/^\d{2}\/\d{2}\/\d{4}$/',         // MM/DD/YYYY or DD/MM/YYYY
            '/^\d{2}-\d{2}-\d{4}$/',           // MM-DD-YYYY or DD-MM-YYYY
            '/^\d{4}\/\d{2}\/\d{2}$/',         // YYYY/MM/DD
            '/^\d{2}\.\d{2}\.\d{4}$/',         // DD.MM.YYYY
            '/^\d{4}\.\d{2}\.\d{2}$/',         // YYYY.MM.DD
            '/^\d{8}$/',                        // YYYYMMDD
        ];

        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a value matches phone pattern
     *
     * @param  string  $value  Value to check
     * @return bool True if phone
     */
    private function isPhone(string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        // Remove common formatting characters
        $cleaned = preg_replace('/[\s\-().]/', '', $value);

        // Check if it's all digits with optional leading plus
        if (preg_match('/^\+?\d{7,20}$/', $cleaned)) {
            return true;
        }

        // Match common phone patterns
        $phonePatterns = [
            '/^\+?[0-9\s\-()]{7,20}$/',                    // International format
            '/^\d{3}[-.\s]?\d{3}[-.\s]?\d{4}$/',          // US format
            '/^\(\d{3}\)\s?\d{3}[-.\s]?\d{4}$/',          // (123) 456-7890
            '/^\+\d{1,3}\s?\d{4,14}$/',                    // +XXX XXXX...
        ];

        foreach ($phonePatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a value looks like a currency amount
     *
     * @param  string  $value  Value to check
     * @return bool True if currency-like
     */
    private function isCurrency(string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        // Remove spaces
        $cleaned = str_replace(' ', '', $value);

        // Check for currency symbols and patterns
        $currencyPatterns = [
            '/^[\$€£¥₹]?\d{1,3}(,\d{3})*(\.\d{2})?$/',    // $1,234.56
            '/^\d{1,3}(,\d{3})*(\.\d{2})?[\$€£¥₹]?$/',    // 1,234.56€
            '/^\d+\.\d{2}$/',                               // 1234.56
            '/^\d{1,3}(\s\d{3})*(\.\d{2})?$/',             // 1 234.56
            '/^\d{1,3}(\s\d{3})*(,\d{2})?$/',              // 1 234,56 (European)
        ];

        foreach ($currencyPatterns as $pattern) {
            if (preg_match($pattern, $cleaned)) {
                return true;
            }
        }

        return false;
    }
}

// CLAUDE-CHECKPOINT
