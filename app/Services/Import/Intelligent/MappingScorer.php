<?php

namespace App\Services\Import\Intelligent;

use App\Models\MappingRule;

/**
 * Mapping Scorer Service
 *
 * Calculates mapping quality scores (0-100) for intelligent CSV import
 * Scores are calculated dynamically based on:
 * - Critical field coverage (loaded from database)
 * - Field confidence scores
 * - Overall field coverage
 * - Data quality assessment
 *
 * Score Weights:
 * - Critical coverage: 50%
 * - Critical confidence: 30%
 * - Field coverage: 10%
 * - Overall confidence: 10%
 *
 * Score Grades:
 * - 90-100: EXCELLENT
 * - 75-89: GOOD
 * - 60-74: FAIR
 * - 40-59: POOR
 * - 0-39: FAILED
 */
class MappingScorer
{
    /**
     * Calculate overall mapping quality score
     *
     * @param  array  $mappings  Field mappings with confidence scores
     *                           Format: [
     *                           'csv_field' => [
     *                           'target_field' => 'name',
     *                           'confidence' => 0.95,
     *                           'source' => 'rule|pattern|manual'
     *                           ]
     *                           ]
     * @param  string  $entityType  Detected entity type (customer, invoice, etc.)
     * @param  int  $totalFields  Total CSV fields available
     * @param  int|null  $companyId  Optional company ID for company-specific rules
     * @return array Quality metrics and recommendations
     */
    public function calculateQuality(
        array $mappings,
        string $entityType,
        int $totalFields,
        ?int $companyId = null
    ): array {
        // Get critical fields from database (NOT hardcoded)
        $criticalFields = $this->getCriticalFields($entityType, $companyId);

        // Calculate core metrics
        $metrics = $this->calculateCoreMetrics($mappings, $criticalFields, $totalFields);

        // Calculate weighted quality score
        $qualityScore = $this->calculateWeightedScore($metrics);

        // Get grade and recommendation
        $grade = $this->getGrade($qualityScore);
        $recommendation = $this->getRecommendation($qualityScore, $metrics);

        // Categorize by confidence levels
        $breakdown = $this->categorizeByConfidence($mappings);

        // Get unmapped fields
        $mappedFieldNames = array_keys($mappings);
        $unmappedFields = $this->getUnmappedFields($mappedFieldNames, $totalFields);

        // Get unmapped critical fields
        $mappedTargetFields = array_column($mappings, 'target_field');
        $unmappedCriticalFields = array_diff($criticalFields, $mappedTargetFields);

        return [
            'overall_score' => round($qualityScore, 2),
            'grade' => $grade,
            'critical_coverage' => round($metrics['critical_coverage'] * 100, 2),
            'critical_confidence' => round($metrics['critical_confidence'] * 100, 2),
            'field_coverage' => round($metrics['field_coverage'] * 100, 2),
            'avg_confidence' => round($metrics['avg_confidence'] * 100, 2),
            'critical_fields_total' => count($criticalFields),
            'critical_fields_mapped' => $metrics['critical_fields_mapped'],
            'total_fields' => $totalFields,
            'mapped_fields' => count($mappings),
            'unmapped_fields' => array_values($unmappedFields),
            'unmapped_critical_fields' => array_values($unmappedCriticalFields),
            'recommendation' => $recommendation,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Get critical fields from database for entity type
     *
     * @param  string  $entityType  Entity type
     * @param  int|null  $companyId  Optional company ID
     * @return array Critical field names
     */
    private function getCriticalFields(string $entityType, ?int $companyId = null): array
    {
        $query = MappingRule::whereEntityType($entityType)
            ->active()
            ->where(function ($q) {
                $q->where('validation_rules->required', true)
                    ->orWhere('validation_rules->required', '=', 'true')
                    ->orWhere('validation_rules->required', '=', 1);
            });

        // Include company-specific and global rules
        if ($companyId) {
            $query->where(function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->orWhereNull('company_id');
            });
        } else {
            $query->whereNull('company_id');
        }

        return $query->orderBy('priority', 'asc')
            ->pluck('target_field')
            ->unique()
            ->toArray();
    }

    /**
     * Calculate core metrics from mappings
     *
     * @param  array  $mappings  Field mappings
     * @param  array  $criticalFields  Critical field names
     * @param  int  $totalFields  Total CSV fields
     * @return array Core metrics
     */
    private function calculateCoreMetrics(
        array $mappings,
        array $criticalFields,
        int $totalFields
    ): array {
        $mappedTargetFields = array_column($mappings, 'target_field');
        $mappedCriticalFields = array_intersect($criticalFields, $mappedTargetFields);
        $criticalFieldsCount = count($criticalFields);

        // Critical coverage (% of critical fields mapped)
        $criticalCoverage = $criticalFieldsCount > 0
            ? count($mappedCriticalFields) / $criticalFieldsCount
            : 1.0; // If no critical fields defined, coverage is 100%

        // Critical confidence (average confidence for critical fields)
        $criticalConfidenceSum = 0;
        $criticalFieldsMapped = 0;

        foreach ($mappings as $mapping) {
            if (in_array($mapping['target_field'], $criticalFields)) {
                $criticalConfidenceSum += $mapping['confidence'];
                $criticalFieldsMapped++;
            }
        }

        $criticalConfidence = $criticalFieldsMapped > 0
            ? $criticalConfidenceSum / $criticalFieldsMapped
            : 0;

        // Field coverage (% of CSV fields mapped)
        $fieldCoverage = $totalFields > 0
            ? count($mappings) / $totalFields
            : 0;

        // Overall confidence (average confidence for all mappings)
        $totalConfidence = array_sum(array_column($mappings, 'confidence'));
        $avgConfidence = count($mappings) > 0
            ? $totalConfidence / count($mappings)
            : 0;

        return [
            'critical_coverage' => $criticalCoverage,
            'critical_confidence' => $criticalConfidence,
            'field_coverage' => $fieldCoverage,
            'avg_confidence' => $avgConfidence,
            'critical_fields_mapped' => $criticalFieldsMapped,
        ];
    }

    /**
     * Calculate weighted quality score
     *
     * Weights:
     * - Critical coverage: 50%
     * - Critical confidence: 30%
     * - Field coverage: 10%
     * - Overall confidence: 10%
     *
     * @param  array  $metrics  Core metrics
     * @return float Quality score (0-100)
     */
    private function calculateWeightedScore(array $metrics): float
    {
        $score = (
            ($metrics['critical_coverage'] * 0.50) +
            ($metrics['critical_confidence'] * 0.30) +
            ($metrics['field_coverage'] * 0.10) +
            ($metrics['avg_confidence'] * 0.10)
        ) * 100;

        return max(0, min(100, $score)); // Clamp between 0-100
    }

    /**
     * Get grade from quality score
     *
     * @param  float  $score  Quality score
     * @return string Grade (EXCELLENT, GOOD, FAIR, POOR, FAILED)
     */
    private function getGrade(float $score): string
    {
        if ($score >= 90) {
            return 'EXCELLENT';
        }
        if ($score >= 75) {
            return 'GOOD';
        }
        if ($score >= 60) {
            return 'FAIR';
        }
        if ($score >= 40) {
            return 'POOR';
        }

        return 'FAILED';
    }

    /**
     * Get recommendation based on score and metrics
     *
     * @param  float  $score  Quality score
     * @param  array  $metrics  Core metrics
     * @return string Recommendation message
     */
    private function getRecommendation(float $score, array $metrics): string
    {
        $grade = $this->getGrade($score);

        // Build recommendation message
        switch ($grade) {
            case 'EXCELLENT':
                return 'EXCELLENT: Mapping is ready to proceed with high confidence.';

            case 'GOOD':
                $message = 'GOOD: Mapping quality is acceptable.';
                if ($metrics['critical_confidence'] < 0.8) {
                    $message .= ' Review low-confidence critical field mappings.';
                } elseif ($metrics['avg_confidence'] < 0.7) {
                    $message .= ' Review low-confidence optional field mappings.';
                }

                return $message;

            case 'FAIR':
                $message = 'FAIR: Manual review recommended.';
                if ($metrics['critical_coverage'] < 1.0) {
                    $message .= ' Some critical fields are unmapped.';
                }
                if ($metrics['critical_confidence'] < 0.7) {
                    $message .= ' Critical field confidence is low.';
                }

                return $message;

            case 'POOR':
                $message = 'POOR: Significant manual mapping needed.';
                if ($metrics['critical_coverage'] < 0.8) {
                    $message .= ' Many critical fields are unmapped.';
                }
                if ($metrics['field_coverage'] < 0.5) {
                    $message .= ' Low overall field coverage.';
                }

                return $message;

            case 'FAILED':
                if ($metrics['critical_coverage'] < 0.5) {
                    return 'FAILED: Less than half of critical fields mapped. Manual configuration required.';
                }

                return 'FAILED: Automatic detection unsuccessful. Manual mapping required.';

            default:
                return 'Unknown quality level. Manual review recommended.';
        }
    }

    /**
     * Categorize mappings by confidence level
     *
     * @param  array  $mappings  Field mappings
     * @return array Breakdown by confidence level
     */
    private function categorizeByConfidence(array $mappings): array
    {
        $high = 0;
        $medium = 0;
        $low = 0;

        foreach ($mappings as $mapping) {
            $confidence = $mapping['confidence'];

            if ($confidence >= 0.8) {
                $high++;
            } elseif ($confidence >= 0.6) {
                $medium++;
            } else {
                $low++;
            }
        }

        return [
            'high_confidence_count' => $high,      // >= 0.8
            'medium_confidence_count' => $medium,  // 0.6 - 0.79
            'low_confidence_count' => $low,        // < 0.6
        ];
    }

    /**
     * Get unmapped field names
     *
     * @param  array  $mappedFieldNames  Names of mapped CSV fields
     * @param  int  $totalFields  Total CSV fields
     * @return array Unmapped field indices/names
     */
    private function getUnmappedFields(array $mappedFieldNames, int $totalFields): array
    {
        // This is a simple implementation
        // In practice, you would pass actual CSV field names
        $unmapped = [];

        // For now, just return count of unmapped fields
        // The calling code should provide actual field names
        $unmappedCount = $totalFields - count($mappedFieldNames);

        if ($unmappedCount > 0) {
            $unmapped = array_fill(0, $unmappedCount, 'unknown');
        }

        return $unmapped;
    }

    /**
     * Calculate data quality metrics
     *
     * Analyzes actual data values for quality issues:
     * - Completeness: % of non-empty values
     * - Uniqueness: % of unique values for key fields
     * - Consistency: Data type consistency
     * - Format compliance: Pattern matching
     *
     * @param  array  $records  Sample records from CSV
     * @param  array  $mappings  Field mappings
     * @param  string  $entityType  Entity type
     * @return array Data quality metrics
     */
    public function calculateDataQuality(
        array $records,
        array $mappings,
        string $entityType
    ): array {
        if (empty($records)) {
            return [
                'completeness' => 0,
                'uniqueness' => 0,
                'consistency' => 0,
                'issues' => ['No data records available for quality assessment.'],
            ];
        }

        $completeness = $this->calculateCompleteness($records, $mappings);
        $uniqueness = $this->calculateUniqueness($records, $mappings);
        $consistency = $this->calculateConsistency($records, $mappings);
        $issues = $this->detectDataQualityIssues($records, $mappings, $entityType);

        return [
            'completeness' => round($completeness * 100, 2),
            'uniqueness' => round($uniqueness * 100, 2),
            'consistency' => round($consistency * 100, 2),
            'overall_quality' => round((($completeness + $uniqueness + $consistency) / 3) * 100, 2),
            'issues' => $issues,
        ];
    }

    /**
     * Calculate completeness score
     *
     * @param  array  $records  Data records
     * @param  array  $mappings  Field mappings
     * @return float Completeness score (0-1)
     */
    private function calculateCompleteness(array $records, array $mappings): float
    {
        if (empty($records) || empty($mappings)) {
            return 0;
        }

        $totalValues = 0;
        $nonEmptyValues = 0;

        foreach ($records as $record) {
            foreach (array_keys($mappings) as $field) {
                $totalValues++;
                if (isset($record[$field]) && trim($record[$field]) !== '') {
                    $nonEmptyValues++;
                }
            }
        }

        return $totalValues > 0 ? $nonEmptyValues / $totalValues : 0;
    }

    /**
     * Calculate uniqueness score for key fields
     *
     * @param  array  $records  Data records
     * @param  array  $mappings  Field mappings
     * @return float Uniqueness score (0-1)
     */
    private function calculateUniqueness(array $records, array $mappings): float
    {
        if (empty($records) || empty($mappings)) {
            return 0;
        }

        // Identify key fields (fields that should be unique)
        $keyFields = [];
        foreach ($mappings as $csvField => $mapping) {
            $targetField = $mapping['target_field'];
            // Common unique fields
            if (in_array($targetField, ['email', 'tax_number', 'invoice_number', 'reference_number'])) {
                $keyFields[] = $csvField;
            }
        }

        if (empty($keyFields)) {
            return 1.0; // No key fields to check
        }

        $uniquenessScores = [];

        foreach ($keyFields as $field) {
            $values = array_column($records, $field);
            $values = array_filter($values, fn ($v) => ! empty($v));

            if (count($values) > 0) {
                $uniqueValues = array_unique($values);
                $uniquenessScores[] = count($uniqueValues) / count($values);
            }
        }

        return ! empty($uniquenessScores) ? array_sum($uniquenessScores) / count($uniquenessScores) : 1.0;
    }

    /**
     * Calculate consistency score (data type consistency)
     *
     * @param  array  $records  Data records
     * @param  array  $mappings  Field mappings
     * @return float Consistency score (0-1)
     */
    private function calculateConsistency(array $records, array $mappings): float
    {
        if (empty($records) || empty($mappings)) {
            return 0;
        }

        $consistencyScores = [];

        foreach (array_keys($mappings) as $field) {
            $values = array_column($records, $field);
            $values = array_filter($values, fn ($v) => ! empty($v));

            if (count($values) < 2) {
                continue; // Skip fields with insufficient data
            }

            // Detect dominant data type
            $types = array_map(function ($value) {
                if (is_numeric($value)) {
                    return 'numeric';
                }
                if (preg_match('/^\d{2}[\.\/\-]\d{2}[\.\/\-]\d{4}$/', $value)) {
                    return 'date';
                }
                if (filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return 'email';
                }

                return 'string';
            }, $values);

            $typeCounts = array_count_values($types);
            $dominantTypeCount = max($typeCounts);
            $consistencyScores[] = $dominantTypeCount / count($values);
        }

        return ! empty($consistencyScores) ? array_sum($consistencyScores) / count($consistencyScores) : 1.0;
    }

    /**
     * Detect data quality issues
     *
     * @param  array  $records  Data records
     * @param  array  $mappings  Field mappings
     * @param  string  $entityType  Entity type
     * @return array List of detected issues
     */
    private function detectDataQualityIssues(
        array $records,
        array $mappings,
        string $entityType
    ): array {
        $issues = [];

        // Check for high percentage of empty values
        $completeness = $this->calculateCompleteness($records, $mappings);
        if ($completeness < 0.7) {
            $issues[] = 'High percentage of empty values detected ('.round((1 - $completeness) * 100).'% missing).';
        }

        // Check for duplicate key values
        $uniqueness = $this->calculateUniqueness($records, $mappings);
        if ($uniqueness < 0.9) {
            $issues[] = 'Duplicate values detected in unique fields.';
        }

        // Check for inconsistent data types
        $consistency = $this->calculateConsistency($records, $mappings);
        if ($consistency < 0.8) {
            $issues[] = 'Inconsistent data types detected across records.';
        }

        // Check for invalid email formats
        foreach ($mappings as $csvField => $mapping) {
            if ($mapping['target_field'] === 'email') {
                $emails = array_column($records, $csvField);
                $validEmails = array_filter($emails, fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL));
                $invalidCount = count($emails) - count($validEmails);
                if ($invalidCount > 0) {
                    $issues[] = "{$invalidCount} invalid email format(s) detected.";
                }
            }
        }

        return $issues;
    }

    /**
     * Get detailed field-level quality metrics
     *
     * @param  array  $records  Data records
     * @param  array  $mappings  Field mappings
     * @return array Field-level quality metrics
     */
    public function getFieldQualityMetrics(array $records, array $mappings): array
    {
        $fieldMetrics = [];

        foreach ($mappings as $csvField => $mapping) {
            $values = array_column($records, $csvField);
            $nonEmptyValues = array_filter($values, fn ($v) => ! empty($v));

            $fieldMetrics[$csvField] = [
                'target_field' => $mapping['target_field'],
                'confidence' => $mapping['confidence'],
                'total_values' => count($values),
                'non_empty_values' => count($nonEmptyValues),
                'completeness' => count($values) > 0 ? count($nonEmptyValues) / count($values) : 0,
                'unique_values' => count(array_unique($nonEmptyValues)),
                'sample_values' => array_slice($nonEmptyValues, 0, 3),
            ];
        }

        return $fieldMetrics;
    }
}

// CLAUDE-CHECKPOINT
