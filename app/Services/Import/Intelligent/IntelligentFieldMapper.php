<?php

namespace App\Services\Import\Intelligent;

use App\Models\MappingRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

/**
 * Intelligent Field Mapper Service
 *
 * Core service for intelligently matching CSV fields to system fields using
 * multiple matching strategies and database-driven mapping rules.
 *
 * Key Features:
 * - NO hardcoded field lists - all driven by database
 * - NO entity-specific logic - works with any CSV structure
 * - Multiple matching strategies (exact, synonym, fuzzy, pattern)
 * - Confidence scoring with data type validation
 * - Company-specific and global mapping rules
 * - Learning system via usage statistics
 *
 * Architecture:
 * - Uses Strategy pattern for matching algorithms
 * - Dependency injection for services
 * - Returns comprehensive results with quality metrics
 *
 * @version 1.0.0
 * @package App\Services\Import\Intelligent
 * @author Claude Code - Phase 1 Intelligent CSV Import System
 */
class IntelligentFieldMapper
{
    /**
     * Field analyzer service for data type detection and validation
     *
     * @var FieldAnalyzer
     */
    private FieldAnalyzer $analyzer;

    /**
     * Mapping scorer service for confidence calculation
     *
     * @var MappingScorer
     */
    private MappingScorer $scorer;

    /**
     * Collection of matching strategies in priority order
     *
     * @var Collection
     */
    private Collection $matchers;

    /**
     * Cached mapping rules from database
     *
     * @var Collection|null
     */
    private ?Collection $mappingRulesCache = null;

    /**
     * Minimum confidence threshold for automatic mapping
     */
    private const MIN_AUTO_CONFIDENCE = 0.7;

    /**
     * Boost factor for data type matches
     */
    private const DATA_TYPE_BOOST = 0.1;

    /**
     * Initialize the Intelligent Field Mapper
     *
     * @param FieldAnalyzer $analyzer Field analyzer service
     * @param MappingScorer $scorer Mapping scorer service
     */
    public function __construct(
        FieldAnalyzer $analyzer,
        MappingScorer $scorer
    ) {
        $this->analyzer = $analyzer;
        $this->scorer = $scorer;

        // Register matching strategies in priority order
        // Each strategy will be attempted until sufficient confidence is achieved
        $this->matchers = collect([
            new Matchers\ExactMatcher(),
            new Matchers\SynonymMatcher(),
            new Matchers\FuzzyMatcher(),
            new Matchers\PatternMatcher(),
        ]);

        Log::info('IntelligentFieldMapper initialized', [
            'matcher_count' => $this->matchers->count(),
            'matchers' => $this->matchers->map(fn($m) => get_class($m))->toArray()
        ]);
    }

    /**
     * Match CSV fields to system fields intelligently
     *
     * Main entry point for intelligent field mapping. Analyzes CSV headers and sample data
     * to determine the best system field matches using multiple strategies.
     *
     * @param array $csvHeaders Array of CSV column headers
     * @param array $sampleData Array of sample rows for data type analysis
     * @param string|null $entityType Optional entity type filter (customer, invoice, etc.)
     * @param int|null $companyId Optional company ID for company-specific rules
     *
     * @return array Comprehensive mapping results with confidence scores
     *
     * @throws Exception If mapping process fails
     */
    public function matchFields(
        array $csvHeaders,
        array $sampleData,
        ?string $entityType = null,
        ?int $companyId = null
    ): array {
        try {
            $normalizedEntityType = $this->normalizeEntityType($entityType);

            Log::info('Starting intelligent field matching', [
                'csv_fields' => count($csvHeaders),
                'sample_rows' => count($sampleData),
                'entity_type' => $normalizedEntityType ?? $entityType,
                'company_id' => $companyId
            ]);

            // Validate input
            if (empty($csvHeaders)) {
                throw new Exception('CSV headers cannot be empty');
            }

            // Load mapping rules from database
            $this->loadMappingRules($normalizedEntityType, $companyId);

            // Analyze CSV fields and sample data
            $fieldAnalysis = $this->analyzeFields($csvHeaders, $sampleData);

            // Generate mappings for each CSV field
            $mappings = [];
            $overallConfidence = 0;
            $highConfidenceCount = 0;

            foreach ($csvHeaders as $csvField) {
                $mapping = $this->mapSingleField(
                    $csvField,
                    $fieldAnalysis[$csvField] ?? [],
                    $normalizedEntityType
                );

                if ($mapping) {
                    $mappings[$csvField] = $mapping;
                    $overallConfidence += $mapping['confidence'];

                    if ($mapping['confidence'] >= self::MIN_AUTO_CONFIDENCE) {
                        $highConfidenceCount++;
                    }
                }
            }

            // Calculate statistics
            $totalFields = count($csvHeaders);
            $mappedFields = count($mappings);
            $overallConfidence = $mappedFields > 0 ? $overallConfidence / $mappedFields : 0;

            // Generate quality score
            $qualityScore = $this->scorer->calculateQuality($mappings, $entityType, $totalFields, $companyId);

            // Compile results
            $result = [
                'mappings' => $mappings,
                'entity_type' => $normalizedEntityType ?? $entityType,
                'overall_confidence' => round($overallConfidence, 2),
                'quality_score' => $qualityScore,
                'statistics' => [
                    'total_fields' => $totalFields,
                    'mapped_fields' => $mappedFields,
                    'unmapped_fields' => $totalFields - $mappedFields,
                    'high_confidence_mappings' => $highConfidenceCount,
                    'mapping_rate' => $totalFields > 0 ? round(($mappedFields / $totalFields) * 100, 2) : 0,
                    'auto_mappable_rate' => $totalFields > 0 ? round(($highConfidenceCount / $totalFields) * 100, 2) : 0
                ],
                'recommendations' => $this->generateRecommendations($mappings, $fieldAnalysis),
                'field_analysis' => $fieldAnalysis
            ];

            Log::info('Field matching completed', [
                'mapped_fields' => $mappedFields,
                'overall_confidence' => $result['overall_confidence'],
                'quality_score' => $qualityScore['overall_score'],
                'entity_type' => $normalizedEntityType ?? $entityType,
            ]);

            return $result;

        } catch (Exception $e) {
            Log::error('Field matching failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Map a single CSV field to the best system field
     *
     * @param string $csvField CSV field name
     * @param array $analysis Field analysis data
     * @param string|null $entityType Entity type filter
     *
     * @return array|null Mapping result or null if no match found
     */
    protected function mapSingleField(
        string $csvField,
        array $analysis,
        ?string $entityType = null
    ): ?array {
        // Find candidate matches using all strategies
        $candidates = $this->findCandidates($csvField, $analysis, $entityType);

        if ($candidates->isEmpty()) {
            Log::debug('No candidates found for field', ['field' => $csvField]);
            return null;
        }

        // Select best match based on confidence scores
        $bestMatch = $this->selectBestMatch($candidates, $analysis);

        if (!$bestMatch) {
            return null;
        }

        return $bestMatch;
    }

    /**
     * Find candidate matches using all matching strategies
     *
     * @param string $csvField CSV field name
     * @param array $analysis Field analysis data
     * @param string|null $entityType Entity type filter
     *
     * @return Collection Collection of candidate matches with scores
     */
    protected function findCandidates(
        string $csvField,
        array $analysis,
        ?string $entityType = null
    ): Collection {
        $candidates = collect();

        // Run each matcher strategy
        foreach ($this->matchers as $matcher) {
            $matches = $matcher->match(
                $csvField,
                $analysis,
                $this->mappingRulesCache,
                $entityType
            );

            if ($matches && !empty($matches)) {
                $candidates = $candidates->merge($matches);
            }
        }

        // Remove duplicates and keep highest score for each target field
        $uniqueCandidates = $candidates->groupBy('target_field')->map(function ($group) {
            return $group->sortByDesc('confidence')->first();
        })->values();

        Log::debug('Candidates found', [
            'csv_field' => $csvField,
            'total_candidates' => $uniqueCandidates->count(),
            'candidates' => $uniqueCandidates->pluck('target_field')->toArray()
        ]);

        return $uniqueCandidates;
    }

    /**
     * Select the best match from candidates
     *
     * @param Collection $candidates Collection of candidate matches
     * @param array $analysis Field analysis data
     *
     * @return array|null Best match or null if none meet threshold
     */
    protected function selectBestMatch(Collection $candidates, array $analysis): ?array
    {
        // Sort by confidence score (descending)
        $sorted = $candidates->sortByDesc('confidence');

        $bestCandidate = $sorted->first();

        if (!$bestCandidate) {
            return null;
        }

        // Apply data type validation boost
        $dataTypeMatch = $this->dataTypeMatches(
            $analysis['data_type'] ?? 'string',
            $bestCandidate['expected_type'] ?? 'string'
        );

        $finalConfidence = $bestCandidate['confidence'];

        if ($dataTypeMatch) {
            $finalConfidence = min(1.0, $finalConfidence + self::DATA_TYPE_BOOST);
        }

        // Build final mapping result
        return [
            'target_field' => $bestCandidate['target_field'],
            'confidence' => round($finalConfidence, 2),
            'method' => $bestCandidate['method'] ?? 'unknown',
            'data_type' => $analysis['data_type'] ?? 'string',
            'data_type_match' => $dataTypeMatch,
            'rule_id' => $bestCandidate['rule_id'] ?? null,
            'source_field_variations' => $bestCandidate['variations'] ?? [],
            'sample_values' => $analysis['sample_values'] ?? [],
            'validation_rules' => $bestCandidate['validation_rules'] ?? [],
            'transformation_type' => $bestCandidate['transformation_type'] ?? 'direct'
        ];
    }

    /**
     * Load mapping rules from database with caching
     *
     * @param string|null $entityType Entity type filter
     * @param int|null $companyId Company ID for company-specific rules
     *
     * @return void
     */
    protected function loadMappingRules(?string $entityType = null, ?int $companyId = null): void
    {
        $entityType = $this->normalizeEntityType($entityType);

        // Build query for mapping rules
        $query = MappingRule::query()
            ->active()
            ->orderByPriority();

        // Apply entity type filter if specified
        if ($entityType) {
            $query->whereEntityType($entityType);
        }

        // Apply company filter (includes global rules via scope)
        if ($companyId) {
            $query->whereCompany($companyId);
        }

        // Cache the results
        $this->mappingRulesCache = $query->get();

        Log::debug('Mapping rules loaded', [
            'rule_count' => $this->mappingRulesCache->count(),
            'entity_type' => $entityType,
            'company_id' => $companyId
        ]);
    }

    /**
     * Analyze CSV fields and sample data
     *
     * @param array $csvHeaders CSV column headers
     * @param array $sampleData Sample rows for analysis
     *
     * @return array Field analysis results indexed by field name
     */
    protected function analyzeFields(array $csvHeaders, array $sampleData): array
    {
        $analysis = [];

        foreach ($csvHeaders as $index => $field) {
            // Extract sample values for this field
            $sampleValues = array_map(function ($row) use ($field) {
                return $row[$field] ?? null;
            }, $sampleData);

            // Filter out nulls and empty values
            $sampleValues = array_filter($sampleValues, function ($value) {
                return $value !== null && $value !== '';
            });

            // Use field analyzer to detect data type and patterns
            $analysis[$field] = $this->analyzer->analyze(
                $field,
                $sampleValues,
                $index,
                $csvHeaders
            );
        }

        return $analysis;
    }

    /**
     * Check if data types match
     *
     * @param string $detectedType Detected data type from CSV
     * @param string $expectedType Expected data type from system field
     *
     * @return bool True if types are compatible
     */
    protected function dataTypeMatches(string $detectedType, string $expectedType): bool
    {
        // Exact match
        if ($detectedType === $expectedType) {
            return true;
        }

        // Compatible type mappings
        $compatibleTypes = [
            'string' => ['text', 'varchar', 'char'],
            'integer' => ['int', 'number', 'numeric'],
            'decimal' => ['float', 'double', 'number', 'numeric', 'currency'],
            'date' => ['datetime', 'timestamp'],
            'boolean' => ['bool', 'int'],
        ];

        if (isset($compatibleTypes[$detectedType])) {
            return in_array($expectedType, $compatibleTypes[$detectedType]);
        }

        if (isset($compatibleTypes[$expectedType])) {
            return in_array($detectedType, $compatibleTypes[$expectedType]);
        }

        return false;
    }

    /**
     * Generate recommendations for improving mappings
     *
     * @param array $mappings Generated mappings
     * @param array $fieldAnalysis Field analysis data
     *
     * @return array Array of recommendations
     */
    protected function generateRecommendations(array $mappings, array $fieldAnalysis): array
    {
        $recommendations = [];

        // Check for unmapped fields
        $unmappedFields = array_diff(
            array_keys($fieldAnalysis),
            array_keys($mappings)
        );

        if (!empty($unmappedFields)) {
            $recommendations[] = [
                'type' => 'unmapped_fields',
                'severity' => 'warning',
                'message' => 'Some fields could not be automatically mapped',
                'fields' => $unmappedFields,
                'action' => 'Review and create manual mappings for these fields'
            ];
        }

        // Check for low confidence mappings
        $lowConfidenceMappings = array_filter($mappings, function ($mapping) {
            return $mapping['confidence'] < self::MIN_AUTO_CONFIDENCE;
        });

        if (!empty($lowConfidenceMappings)) {
            $recommendations[] = [
                'type' => 'low_confidence',
                'severity' => 'info',
                'message' => 'Some mappings have low confidence scores',
                'fields' => array_keys($lowConfidenceMappings),
                'action' => 'Review these mappings before importing data'
            ];
        }

        // Check for data type mismatches
        $typeMismatches = array_filter($mappings, function ($mapping) {
            return !$mapping['data_type_match'];
        });

        if (!empty($typeMismatches)) {
            $recommendations[] = [
                'type' => 'type_mismatch',
                'severity' => 'warning',
                'message' => 'Some fields have data type mismatches',
                'fields' => array_keys($typeMismatches),
                'action' => 'Data transformation may be required during import'
            ];
        }

        // Positive feedback for high-quality mappings
        $highQualityRate = count($mappings) > 0
            ? (count(array_filter($mappings, fn($m) => $m['confidence'] >= 0.9)) / count($mappings)) * 100
            : 0;

        if ($highQualityRate >= 80) {
            $recommendations[] = [
                'type' => 'high_quality',
                'severity' => 'success',
                'message' => 'Excellent mapping quality detected',
                'action' => 'CSV structure is well-suited for automatic import'
            ];
        }

        return $recommendations;
    }

    /**
     * Normalize incoming entity types to canonical mapping rule values
     *
     * @param string|null $entityType Incoming entity type value
     * @return string|null Normalized entity type or null when unavailable
     */
    protected function normalizeEntityType(?string $entityType): ?string
    {
        if (!$entityType) {
            return null;
        }

        $entityType = Str::lower(trim($entityType));

        $map = [
            'customer' => MappingRule::ENTITY_CUSTOMER,
            'customers' => MappingRule::ENTITY_CUSTOMER,
            'client' => MappingRule::ENTITY_CUSTOMER,
            'clients' => MappingRule::ENTITY_CUSTOMER,
            'invoice' => MappingRule::ENTITY_INVOICE,
            'invoices' => MappingRule::ENTITY_INVOICE,
            'bill' => MappingRule::ENTITY_INVOICE,
            'bills' => MappingRule::ENTITY_INVOICE,
            'item' => MappingRule::ENTITY_ITEM,
            'items' => MappingRule::ENTITY_ITEM,
            'product' => MappingRule::ENTITY_ITEM,
            'products' => MappingRule::ENTITY_ITEM,
            'payment' => MappingRule::ENTITY_PAYMENT,
            'payments' => MappingRule::ENTITY_PAYMENT,
            'expense' => MappingRule::ENTITY_EXPENSE,
            'expenses' => MappingRule::ENTITY_EXPENSE,
        ];

        return $map[$entityType] ?? Str::singular($entityType);
    }

    /**
     * Get cached mapping rules
     *
     * @return Collection|null Cached mapping rules
     */
    public function getMappingRules(): ?Collection
    {
        return $this->mappingRulesCache;
    }

    /**
     * Clear mapping rules cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->mappingRulesCache = null;
        Log::debug('Mapping rules cache cleared');
    }

    /**
     * Get matcher instances
     *
     * @return Collection Collection of matcher instances
     */
    public function getMatchers(): Collection
    {
        return $this->matchers;
    }

    /**
     * Add a custom matcher to the strategy collection
     *
     * @param object $matcher Matcher instance implementing match() method
     * @param int|null $priority Optional priority (lower = higher priority)
     *
     * @return void
     */
    public function addMatcher(object $matcher, ?int $priority = null): void
    {
        if ($priority !== null) {
            $this->matchers->splice($priority, 0, [$matcher]);
        } else {
            $this->matchers->push($matcher);
        }

        Log::info('Custom matcher added', [
            'matcher_class' => get_class($matcher),
            'priority' => $priority ?? 'last'
        ]);
    }

    /**
     * Get service status and statistics
     *
     * @return array Service status information
     */
    public function getServiceStatus(): array
    {
        return [
            'service_name' => 'Intelligent Field Mapper',
            'version' => '1.0.0',
            'status' => 'operational',
            'matchers' => $this->matchers->map(function ($matcher) {
                return [
                    'class' => get_class($matcher),
                    'name' => class_basename($matcher)
                ];
            })->toArray(),
            'cached_rules' => $this->mappingRulesCache ? $this->mappingRulesCache->count() : 0,
            'features' => [
                'database_driven' => true,
                'no_hardcoded_fields' => true,
                'entity_agnostic' => true,
                'multiple_strategies' => true,
                'confidence_scoring' => true,
                'data_type_validation' => true,
                'company_specific_rules' => true,
                'extensible_matchers' => true
            ],
            'configuration' => [
                'min_auto_confidence' => self::MIN_AUTO_CONFIDENCE,
                'data_type_boost' => self::DATA_TYPE_BOOST
            ]
        ];
    }
}

// CLAUDE-CHECKPOINT
