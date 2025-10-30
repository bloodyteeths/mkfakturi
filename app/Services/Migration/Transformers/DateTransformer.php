<?php

namespace App\Services\Migration\Transformers;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Collection;

/**
 * DateTransformer - Handle date format conversion for Macedonia accounting data
 * 
 * This transformer handles the conversion of Macedonian date formats (dd.mm.yyyy, dd/mm/yyyy, dd-mm-yyyy)
 * to standardized ISO format (Y-m-d) for database storage. It's designed to work with data from
 * Macedonian accounting systems like Onivo, Megasoft, and Pantheon.
 * 
 * Common Macedonian date formats handled:
 * - dd.mm.yyyy (most common in Macedonia)
 * - dd/mm/yyyy 
 * - dd-mm-yyyy
 * - d.m.yyyy (single digit days/months)
 * 
 * Features:
 * - Handles multiple Macedonian date formats
 * - Batch transformation for performance
 * - Comprehensive error handling
 * - Reversible transformations
 * - Edge case handling (leap years, invalid dates)
 * - Support for empty/null values
 * 
 * @package App\Services\Migration\Transformers
 */
class DateTransformer
{
    /**
     * Common Macedonian date format patterns
     */
    private const MACEDONIAN_DATE_PATTERNS = [
        'd.m.Y',     // 1.1.2023, 31.12.2023
        'd/m/Y',     // 1/1/2023, 31/12/2023  
        'd-m-Y',     // 1-1-2023, 31-12-2023
        'j.n.Y',     // Alternative single digit format
        'j/n/Y',     // Alternative single digit format
        'j-n-Y',     // Alternative single digit format
        'd.m.y',     // Two digit year: 31.12.23
        'd/m/y',     // Two digit year: 31/12/23
        'd-m-y',     // Two digit year: 31-12-23
    ];

    /**
     * Transform a single Macedonian date string to ISO format (Y-m-d)
     * 
     * @param string|null $dateString The Macedonian formatted date
     * @return string|null ISO formatted date (Y-m-d) or NULL if invalid/empty
     * @throws Exception If date is malformed and strict mode is enabled
     */
    public function transform(?string $dateString): ?string
    {
        // Handle null or empty input
        if (empty($dateString) || trim($dateString) === '') {
            return null;
        }

        $dateString = trim($dateString);

        // Try each Macedonian date pattern
        foreach (self::MACEDONIAN_DATE_PATTERNS as $pattern) {
            try {
                $date = Carbon::createFromFormat($pattern, $dateString);
                
                // Validate the parsed date matches original input
                if ($date && $date->format($pattern) === $dateString) {
                    return $date->format('Y-m-d');
                }
            } catch (Exception $e) {
                // Continue to next pattern
                continue;
            }
        }

        // Try parsing with Carbon's flexible parser as fallback
        try {
            $date = Carbon::parse($dateString);
            return $date->format('Y-m-d');
        } catch (Exception $e) {
            // Log the problematic date for debugging
            logger()->warning('DateTransformer: Unable to parse date', [
                'input' => $dateString,
                'error' => $e->getMessage()
            ]);
            
            return null; // Return null for unparseable dates
        }
    }

    /**
     * Transform multiple dates in batch for better performance
     * 
     * @param array $dates Array of Macedonian formatted date strings
     * @return array Array of ISO formatted dates with same keys
     */
    public function transformBatch(array $dates): array
    {
        $transformed = [];
        
        foreach ($dates as $key => $dateString) {
            $transformed[$key] = $this->transform($dateString);
        }
        
        return $transformed;
    }

    /**
     * Transform dates in a collection while preserving structure
     * 
     * @param Collection $collection Collection containing date data
     * @param string $dateField The field name containing dates to transform
     * @return Collection Transformed collection
     */
    public function transformCollection(Collection $collection, string $dateField): Collection
    {
        return $collection->map(function ($item) use ($dateField) {
            if (is_array($item)) {
                $item[$dateField] = $this->transform($item[$dateField] ?? null);
            } elseif (is_object($item) && property_exists($item, $dateField)) {
                $item->{$dateField} = $this->transform($item->{$dateField});
            }
            
            return $item;
        });
    }

    /**
     * Reverse transform: Convert ISO date (Y-m-d) back to Macedonian format (dd.mm.yyyy)
     * 
     * @param string|null $isoDate ISO formatted date (Y-m-d)
     * @param string $format Target Macedonian format (default: d.m.Y)
     * @return string|null Macedonian formatted date or NULL if invalid
     */
    public function reverse(?string $isoDate, string $format = 'd.m.Y'): ?string
    {
        if (empty($isoDate)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $isoDate);
            return $date ? $date->format($format) : null;
        } catch (Exception $e) {
            logger()->warning('DateTransformer: Unable to reverse transform date', [
                'input' => $isoDate,
                'format' => $format,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    /**
     * Validate if a string looks like a Macedonian date format
     * 
     * @param string $dateString Date string to validate
     * @return bool True if it matches expected Macedonian patterns
     */
    public function isValidMacedonianDate(string $dateString): bool
    {
        if (empty(trim($dateString))) {
            return false;
        }

        // Check if it matches any of our known patterns
        foreach (self::MACEDONIAN_DATE_PATTERNS as $pattern) {
            try {
                $date = Carbon::createFromFormat($pattern, trim($dateString));
                if ($date && $date->format($pattern) === trim($dateString)) {
                    return true;
                }
            } catch (Exception $e) {
                continue;
            }
        }

        return false;
    }

    /**
     * Get statistics about date transformation results
     * 
     * @param array $originalDates Array of original date strings
     * @param array $transformedDates Array of transformed dates
     * @return array Statistics array with success/failure counts
     */
    public function getTransformationStats(array $originalDates, array $transformedDates): array
    {
        $total = count($originalDates);
        $successful = count(array_filter($transformedDates, function ($date) {
            return $date !== null;
        }));
        $failed = $total - $successful;
        $emptyInput = count(array_filter($originalDates, function ($date) {
            return empty($date);
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
     * Clean and normalize date string before transformation
     * Removes extra whitespace, handles common typos
     * 
     * @param string $dateString Raw date string
     * @return string Cleaned date string
     */
    private function cleanDateString(string $dateString): string
    {
        // Remove extra whitespace
        $cleaned = trim($dateString);
        
        // Handle common separator variations (normalize to period)
        $cleaned = str_replace(['/', '-', '\\'], '.', $cleaned);
        
        // Remove multiple consecutive periods
        $cleaned = preg_replace('/\.+/', '.', $cleaned);
        
        // Handle missing leading zeros (1.1.2023 -> 01.01.2023 for consistency)
        $parts = explode('.', $cleaned);
        if (count($parts) === 3) {
            $parts[0] = str_pad($parts[0], 2, '0', STR_PAD_LEFT); // day
            $parts[1] = str_pad($parts[1], 2, '0', STR_PAD_LEFT); // month
            $cleaned = implode('.', $parts);
        }
        
        return $cleaned;
    }

    /**
     * Handle edge cases for Macedonian accounting systems
     * Some systems export partial dates or special formats
     * 
     * @param string $dateString Date string to check for edge cases
     * @return string|null Processed date string or null if invalid
     */
    public function handleEdgeCases(string $dateString): ?string
    {
        $cleaned = trim($dateString);
        
        // Handle empty or placeholder values common in exports
        $invalidValues = ['', '0', '00.00.0000', '01.01.1900', 'NULL', 'null', '-'];
        if (in_array($cleaned, $invalidValues, true)) {
            return null;
        }
        
        // Handle partial dates (common in some Macedonian systems)
        // e.g., "12.2023" -> "01.12.2023" (assume first day of month)
        if (preg_match('/^(\d{1,2})\.(\d{4})$/', $cleaned, $matches)) {
            return "01.{$matches[1]}.{$matches[2]}";
        }
        
        // Handle year-only dates -> first day of year
        if (preg_match('/^(\d{4})$/', $cleaned, $matches)) {
            return "01.01.{$matches[1]}";
        }
        
        return $this->cleanDateString($cleaned);
    }
}