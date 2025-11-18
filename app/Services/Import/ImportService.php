<?php

namespace App\Services\Import;

use App\Services\Import\Intelligent\AdaptiveValidator;
use App\Services\Import\Intelligent\FieldAnalyzer;
use App\Services\Import\Intelligent\IntelligentFieldMapper;
use App\Services\Import\Intelligent\MappingScorer;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Import Service Facade
 *
 * Central service that switches between legacy and intelligent import systems
 * based on feature flag configuration. Provides a unified interface for:
 * - Field detection and mapping suggestions
 * - Data validation
 * - Quality scoring
 *
 * Features:
 * - Feature flag controlled (config('import.intelligent_enabled'))
 * - Automatic fallback to legacy system on errors
 * - Comprehensive logging and error handling
 * - Compatible with existing ImportController API
 *
 * @version 1.0.0
 *
 * @author Claude Code - Phase 2 Import Service Integration
 */
class ImportService
{
    /**
     * Intelligent field mapper service
     */
    private IntelligentFieldMapper $fieldMapper;

    /**
     * Field analyzer service for data type detection
     */
    private FieldAnalyzer $fieldAnalyzer;

    /**
     * Mapping scorer service for quality metrics
     */
    private MappingScorer $mappingScorer;

    /**
     * Adaptive validator service for data validation
     */
    private AdaptiveValidator $validator;

    /**
     * Whether intelligent import is enabled
     */
    private bool $intelligentEnabled;

    /**
     * Whether to fallback to legacy on errors
     */
    private bool $fallbackEnabled;

    /**
     * Initialize the Import Service
     *
     * @param  IntelligentFieldMapper  $fieldMapper  Intelligent field mapper
     * @param  FieldAnalyzer  $fieldAnalyzer  Field analyzer
     * @param  MappingScorer  $mappingScorer  Mapping scorer
     * @param  AdaptiveValidator  $validator  Adaptive validator
     */
    public function __construct(
        IntelligentFieldMapper $fieldMapper,
        FieldAnalyzer $fieldAnalyzer,
        MappingScorer $mappingScorer,
        AdaptiveValidator $validator
    ) {
        $this->fieldMapper = $fieldMapper;
        $this->fieldAnalyzer = $fieldAnalyzer;
        $this->mappingScorer = $mappingScorer;
        $this->validator = $validator;

        // Read configuration
        $this->intelligentEnabled = config('import.intelligent_enabled', false);
        $this->fallbackEnabled = config('import.intelligent.fallback_to_legacy', true);

        Log::info('ImportService initialized', [
            'intelligent_enabled' => $this->intelligentEnabled,
            'fallback_enabled' => $this->fallbackEnabled,
        ]);
    }

    /**
     * Detect CSV fields and suggest mappings
     *
     * This is the main entry point for field mapping. It switches between
     * intelligent and legacy systems based on feature flag.
     *
     * @param  array  $csvHeaders  CSV column headers
     * @param  array  $sampleData  Sample rows for data type analysis
     * @param  string|null  $entityType  Entity type (customer, invoice, item, payment, expense)
     * @param  int|null  $companyId  Company ID for company-specific rules
     * @return array Mapping suggestions and quality metrics
     */
    public function detectFieldsAndSuggestMappings(
        array $csvHeaders,
        array $sampleData,
        ?string $entityType = null,
        ?int $companyId = null
    ): array {
        // If intelligent mode is disabled, return empty suggestions
        // Legacy ImportController will handle mapping
        if (! $this->intelligentEnabled) {
            Log::info('Intelligent import disabled, returning empty suggestions', [
                'entity_type' => $entityType,
                'fields_count' => count($csvHeaders),
            ]);

            return $this->buildLegacyResponse($csvHeaders, $sampleData, $entityType);
        }

        // Try intelligent mapping system
        try {
            Log::info('Using intelligent field mapping system', [
                'entity_type' => $entityType,
                'company_id' => $companyId,
                'fields_count' => count($csvHeaders),
                'sample_rows' => count($sampleData),
            ]);

            // Prepare sample data - convert to associative arrays if needed
            $formattedSampleData = [];
            foreach ($sampleData as $row) {
                $formattedRow = [];
                foreach ($csvHeaders as $index => $header) {
                    $formattedRow[$header] = $row[$index] ?? null;
                }
                $formattedSampleData[] = $formattedRow;
            }

            // Use intelligent field mapper
            $mappingResult = $this->fieldMapper->matchFields(
                $csvHeaders,
                $formattedSampleData,
                $entityType,
                $companyId
            );

            // Transform to controller-compatible format
            $response = $this->transformIntelligentResponse($mappingResult, $csvHeaders, $entityType);

            Log::info('Intelligent field mapping completed', [
                'mapped_fields' => count($response['mapping_suggestions']),
                'overall_confidence' => $response['overall_confidence'],
                'quality_grade' => $response['quality_grade'],
            ]);

            return $response;

        } catch (Exception $e) {
            Log::error('Intelligent field mapping failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'fallback_enabled' => $this->fallbackEnabled,
            ]);

            // Fallback to legacy if enabled
            if ($this->fallbackEnabled) {
                Log::warning('Falling back to legacy import system');

                return $this->buildLegacyResponse($csvHeaders, $sampleData, $entityType);
            }

            // Re-throw if fallback is disabled
            throw $e;
        }
    }

    /**
     * Validate import data using adaptive validator
     *
     * @param  array  $records  Array of records to validate
     * @param  array  $fieldMappings  Field mappings (csv_field => target_field)
     * @param  string  $entityType  Entity type
     * @return array Validation results with errors and warnings
     */
    public function validateImportData(
        array $records,
        array $fieldMappings,
        string $entityType
    ): array {
        // If intelligent mode is disabled, skip validation
        if (! $this->intelligentEnabled) {
            Log::info('Intelligent import disabled, skipping adaptive validation');

            return [
                'valid_records' => count($records),
                'invalid_records' => 0,
                'total_records' => count($records),
                'errors' => [],
                'warnings' => [],
                'records' => array_map(fn ($r, $i) => [
                    'row_number' => $i + 1,
                    'data' => $r,
                    'has_errors' => false,
                    'has_warnings' => false,
                    'errors' => [],
                    'warnings' => [],
                ], $records, array_keys($records)),
            ];
        }

        try {
            Log::info('Using adaptive validation system', [
                'entity_type' => $entityType,
                'records_count' => count($records),
                'field_mappings' => count($fieldMappings),
            ]);

            $validatedRecords = [];
            $totalErrors = [];
            $totalWarnings = [];
            $invalidCount = 0;

            foreach ($records as $index => $record) {
                $rowNumber = $index + 1;

                // Validate record using adaptive validator
                $validation = $this->validator->validate(
                    $record,
                    $fieldMappings,
                    $rowNumber
                );

                $hasErrors = ! empty($validation['errors']);
                $hasWarnings = ! empty($validation['warnings']);

                if ($hasErrors) {
                    $invalidCount++;
                }

                $validatedRecords[] = [
                    'row_number' => $rowNumber,
                    'data' => $record,
                    'has_errors' => $hasErrors,
                    'has_warnings' => $hasWarnings,
                    'errors' => $validation['errors'],
                    'warnings' => $validation['warnings'],
                ];

                // Collect all errors and warnings
                $totalErrors = array_merge($totalErrors, $validation['errors']);
                $totalWarnings = array_merge($totalWarnings, $validation['warnings']);
            }

            $result = [
                'valid_records' => count($records) - $invalidCount,
                'invalid_records' => $invalidCount,
                'total_records' => count($records),
                'errors' => $totalErrors,
                'warnings' => $totalWarnings,
                'records' => $validatedRecords,
            ];

            Log::info('Adaptive validation completed', [
                'valid_records' => $result['valid_records'],
                'invalid_records' => $result['invalid_records'],
                'total_errors' => count($totalErrors),
                'total_warnings' => count($totalWarnings),
            ]);

            return $result;

        } catch (Exception $e) {
            Log::error('Adaptive validation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // If validation fails, return basic structure
            if ($this->fallbackEnabled) {
                Log::warning('Validation failed, returning basic structure');

                return [
                    'valid_records' => 0,
                    'invalid_records' => count($records),
                    'total_records' => count($records),
                    'errors' => ['Validation system error: '.$e->getMessage()],
                    'warnings' => [],
                    'records' => [],
                ];
            }

            throw $e;
        }
    }

    /**
     * Get mapping quality score
     *
     * @param  array  $mappings  Field mappings with confidence scores
     * @param  string  $entityType  Entity type
     * @return array Quality metrics
     */
    public function getMappingQualityScore(
        array $mappings,
        string $entityType
    ): array {
        if (! $this->intelligentEnabled) {
            return [
                'overall_score' => 0,
                'grade' => 'UNKNOWN',
                'critical_coverage' => 0,
                'field_coverage' => 0,
                'avg_confidence' => 0,
                'recommendation' => 'Intelligent import system is disabled',
            ];
        }

        try {
            // Calculate quality using mapping scorer
            $quality = $this->mappingScorer->calculateQuality(
                $mappings,
                $entityType,
                count($mappings),
                null
            );

            Log::info('Mapping quality calculated', [
                'entity_type' => $entityType,
                'overall_score' => $quality['overall_score'],
                'grade' => $quality['grade'],
            ]);

            return $quality;

        } catch (Exception $e) {
            Log::error('Quality score calculation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'overall_score' => 0,
                'grade' => 'ERROR',
                'critical_coverage' => 0,
                'field_coverage' => 0,
                'avg_confidence' => 0,
                'recommendation' => 'Quality calculation error: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Transform intelligent mapping result to controller-compatible format
     *
     * @param  array  $mappingResult  Result from IntelligentFieldMapper
     * @param  array  $csvHeaders  Original CSV headers
     * @param  string|null  $entityType  Entity type
     * @return array Transformed response
     */
    protected function transformIntelligentResponse(
        array $mappingResult,
        array $csvHeaders,
        ?string $entityType
    ): array {
        // Extract mapping suggestions
        $mappingSuggestions = [];
        $confidenceScores = [];
        $detectedFields = [];

        foreach ($csvHeaders as $index => $csvField) {
            // Build detected field object
            $fieldAnalysis = $mappingResult['field_analysis'][$csvField] ?? [];

            $detectedFields[] = [
                'name' => $csvField,
                'type' => $fieldAnalysis['detected_type'] ?? 'string',
                'sample_data' => $fieldAnalysis['sample_values'] ?? [],
                'index' => $index,
            ];

            // Extract mapping if exists
            if (isset($mappingResult['mappings'][$csvField])) {
                $mapping = $mappingResult['mappings'][$csvField];
                $mappingSuggestions[$csvField] = $mapping['target_field'];
                $confidenceScores[$csvField] = $mapping['confidence'];
            }
        }

        // Build response compatible with ImportController
        return [
            'entity_type' => $entityType,
            'detected_fields' => $detectedFields,
            'mapping_suggestions' => $mappingSuggestions,
            'confidence_scores' => $confidenceScores,
            'overall_confidence' => $mappingResult['overall_confidence'],
            'quality_score' => $mappingResult['quality_score']['overall_score'] ?? 0,
            'quality_grade' => $mappingResult['quality_score']['grade'] ?? 'UNKNOWN',
            'statistics' => $mappingResult['statistics'],
            'recommendations' => $mappingResult['recommendations'],
            'intelligent_mode' => true,
        ];
    }

    /**
     * Build legacy response format when intelligent mode is disabled
     *
     * @param  array  $csvHeaders  CSV headers
     * @param  array  $sampleData  Sample data
     * @param  string|null  $entityType  Entity type
     * @return array Legacy response format
     */
    protected function buildLegacyResponse(
        array $csvHeaders,
        array $sampleData,
        ?string $entityType
    ): array {
        // Build basic detected fields structure
        $detectedFields = [];

        foreach ($csvHeaders as $index => $csvField) {
            $samples = [];
            foreach ($sampleData as $row) {
                if (is_array($row) && isset($row[$index])) {
                    $samples[] = $row[$index];
                } elseif (isset($row[$csvField])) {
                    $samples[] = $row[$csvField];
                }
            }

            $detectedFields[] = [
                'name' => $csvField,
                'type' => 'string',
                'sample_data' => array_slice($samples, 0, 3),
                'index' => $index,
            ];
        }

        return [
            'entity_type' => $entityType,
            'detected_fields' => $detectedFields,
            'mapping_suggestions' => [], // Empty - legacy controller handles this
            'confidence_scores' => [],
            'overall_confidence' => 0,
            'quality_score' => 0,
            'quality_grade' => 'UNKNOWN',
            'statistics' => [
                'total_fields' => count($csvHeaders),
                'mapped_fields' => 0,
                'unmapped_fields' => count($csvHeaders),
                'high_confidence_mappings' => 0,
                'mapping_rate' => 0,
                'auto_mappable_rate' => 0,
            ],
            'recommendations' => [
                [
                    'type' => 'legacy_mode',
                    'severity' => 'info',
                    'message' => 'Using legacy import system',
                    'action' => 'Manual field mapping required',
                ],
            ],
            'intelligent_mode' => false,
        ];
    }

    /**
     * Check if intelligent import is enabled
     *
     * @return bool True if intelligent mode is enabled
     */
    public function isIntelligentEnabled(): bool
    {
        return $this->intelligentEnabled;
    }

    /**
     * Enable or disable intelligent import
     *
     * @param  bool  $enabled  Enable/disable flag
     */
    public function setIntelligentEnabled(bool $enabled): void
    {
        $this->intelligentEnabled = $enabled;

        Log::info('Intelligent import mode changed', [
            'enabled' => $enabled,
        ]);
    }

    /**
     * Get service status and configuration
     *
     * @return array Service status information
     */
    public function getServiceStatus(): array
    {
        return [
            'service_name' => 'Import Service Facade',
            'version' => '1.0.0',
            'intelligent_enabled' => $this->intelligentEnabled,
            'fallback_enabled' => $this->fallbackEnabled,
            'configuration' => [
                'confidence_threshold' => config('import.intelligent.confidence_threshold', 0.60),
                'quality_thresholds' => config('import.intelligent.quality_thresholds', []),
                'matchers' => config('import.intelligent.matchers', []),
                'logging_enabled' => config('import.intelligent.logging.enabled', true),
            ],
            'components' => [
                'field_mapper' => class_basename($this->fieldMapper),
                'field_analyzer' => class_basename($this->fieldAnalyzer),
                'mapping_scorer' => class_basename($this->mappingScorer),
                'validator' => class_basename($this->validator),
            ],
            'features' => [
                'intelligent_field_mapping' => $this->intelligentEnabled,
                'adaptive_validation' => $this->intelligentEnabled,
                'quality_scoring' => $this->intelligentEnabled,
                'automatic_fallback' => $this->fallbackEnabled,
                'multi_language_support' => true,
                'fuzzy_matching' => true,
                'data_type_detection' => true,
                'confidence_scoring' => true,
            ],
        ];
    }

    /**
     * Clear all caches in underlying services
     */
    public function clearCaches(): void
    {
        $this->fieldMapper->clearCache();
        $this->validator->clearCache();

        Log::info('All import service caches cleared');
    }
}

// CLAUDE-CHECKPOINT
