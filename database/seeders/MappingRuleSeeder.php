<?php

namespace Database\Seeders;

use App\Models\MappingRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * MappingRuleSeeder
 *
 * Populates the mapping_rules table with comprehensive field definitions
 * from the field_synonyms_v2.json synonym database.
 *
 * This seeder creates system-wide mapping rules that enable intelligent
 * CSV field matching across multiple languages (English, Macedonian, Albanian, Serbian)
 * and various import formats.
 *
 * Features:
 * - Loads 500+ synonym variations from JSON
 * - Creates mapping rules for 5 entity types (customer, invoice, payment, item, expense)
 * - Supports 41 fields with multilingual synonyms
 * - Includes validation rules and data type patterns
 * - Sets up priority-based matching
 *
 * Usage:
 *   php artisan db:seed --class=MappingRuleSeeder
 *
 * @package Database\Seeders
 */
class MappingRuleSeeder extends Seeder
{
    /**
     * Path to the field synonyms JSON file
     */
    private const SYNONYM_FILE_PATH = 'storage/import/synonyms/field_synonyms_v2.json';

    /**
     * Priority levels for different matching strategies
     */
    private const PRIORITY_EXACT = 10;
    private const PRIORITY_REQUIRED = 20;
    private const PRIORITY_RECOMMENDED = 50;
    private const PRIORITY_OPTIONAL = 100;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting MappingRuleSeeder...');

        // Load synonym data from JSON file
        $synonymData = $this->loadSynonymData();

        if (!$synonymData) {
            $this->command->error('Failed to load synonym data from ' . self::SYNONYM_FILE_PATH);
            return;
        }

        $this->command->info("Loaded synonym database version {$synonymData['version']}");
        $this->command->info("Languages: " . implode(', ', $synonymData['languages']));

        // Clear existing system rules (but preserve user-created rules)
        $this->command->info('Clearing existing system mapping rules...');
        DB::table('mapping_rules')
            ->where('is_system_rule', true)
            ->delete();

        $totalRules = 0;

        // Process each entity type
        foreach ($synonymData['entity_types'] as $entityType => $entityConfig) {
            $this->command->info("Processing entity type: {$entityType}");

            $rulesCreated = $this->processEntityType($entityType, $entityConfig, $synonymData['languages']);
            $totalRules += $rulesCreated;

            $this->command->line("  Created {$rulesCreated} rules for {$entityType}");
        }

        $this->command->info("✓ Successfully created {$totalRules} mapping rules");
        $this->command->newLine();
    }

    /**
     * Load synonym data from JSON file
     *
     * @return array|null
     */
    private function loadSynonymData(): ?array
    {
        $filePath = base_path(self::SYNONYM_FILE_PATH);

        if (!File::exists($filePath)) {
            $this->command->error("Synonym file not found: {$filePath}");
            return null;
        }

        $jsonContent = File::get($filePath);
        $data = json_decode($jsonContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->command->error('Invalid JSON in synonym file: ' . json_last_error_msg());
            return null;
        }

        return $data;
    }

    /**
     * Process all fields for a specific entity type
     *
     * @param string $entityType
     * @param array $entityConfig
     * @param array $languages
     * @return int Number of rules created
     */
    private function processEntityType(string $entityType, array $entityConfig, array $languages): int
    {
        $rulesCreated = 0;

        foreach ($entityConfig['fields'] as $fieldName => $fieldConfig) {
            // Create the primary mapping rule
            $rule = $this->createMappingRule($entityType, $fieldName, $fieldConfig, $languages);

            if ($rule) {
                $rulesCreated++;
            }
        }

        return $rulesCreated;
    }

    /**
     * Create a single mapping rule from field configuration
     *
     * @param string $entityType
     * @param string $fieldName
     * @param array $fieldConfig
     * @param array $languages
     * @return MappingRule|null
     */
    private function createMappingRule(string $entityType, string $fieldName, array $fieldConfig, array $languages): ?MappingRule
    {
        try {
            // Collect all synonym variations across all languages
            $allSynonyms = [];
            foreach ($languages as $lang) {
                if (isset($fieldConfig['synonyms'][$lang])) {
                    $allSynonyms = array_merge($allSynonyms, $fieldConfig['synonyms'][$lang]);
                }
            }

            // Determine priority based on required status
            $priority = $fieldConfig['required']
                ? self::PRIORITY_REQUIRED
                : self::PRIORITY_OPTIONAL;

            // Build validation rules
            $validationRules = $this->buildValidationRules($fieldConfig);

            // Build business rules
            $businessRules = $this->buildBusinessRules($fieldConfig);

            // Create the mapping rule
            $rule = MappingRule::create([
                // Rule identification
                'name' => ucwords(str_replace('_', ' ', $fieldName)) . " ({$entityType})",
                'description' => "System-generated mapping rule for {$fieldName} field in {$entityType} imports. " .
                                 "Supports " . count($allSynonyms) . " variations across " . count($languages) . " languages.",
                'entity_type' => $entityType,
                'source_system' => null, // Generic rule for all systems

                // Field mapping
                'source_field' => $fieldName, // Primary field name
                'target_field' => $fieldName, // Maps to same field in our system
                'field_variations' => array_values(array_unique($allSynonyms)), // All synonym variations

                // Transformation
                'transformation_type' => 'direct', // Direct mapping (no transformation)
                'transformation_config' => null,
                'transformation_script' => null,

                // Validation
                'validation_rules' => $validationRules,
                'business_rules' => $businessRules,

                // Language-specific patterns
                'macedonian_patterns' => $this->extractMacedonianPatterns($fieldConfig),
                'language_variations' => $this->buildLanguageVariations($fieldConfig, $languages),
                'format_patterns' => $fieldConfig['patterns'] ?? [],

                // Confidence and priority
                'confidence_score' => 1.00, // System rules start with full confidence
                'priority' => $priority,
                'is_active' => true,
                'is_system_rule' => true,

                // Testing
                'test_cases' => $this->buildTestCases($fieldConfig),
                'sample_data' => $fieldConfig['examples'] ?? [],

                // Conditions (none for now - applies to all)
                'conditions' => null,

                // Relationships
                'company_id' => null, // System-wide rule
                'creator_id' => null, // System-created
            ]);

            return $rule;

        } catch (\Exception $e) {
            $this->command->error("Failed to create rule for {$entityType}.{$fieldName}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Build validation rules from field configuration
     *
     * @param array $fieldConfig
     * @return array
     */
    private function buildValidationRules(array $fieldConfig): array
    {
        $rules = [
            'required' => $fieldConfig['required'] ?? false,
            'type' => $fieldConfig['data_type'] ?? 'string',
        ];

        // Add regex validation if provided
        if (isset($fieldConfig['validation']) && $fieldConfig['validation']) {
            $rules['regex'] = $fieldConfig['validation'];
        }

        // Add type-specific validations
        switch ($fieldConfig['data_type']) {
            case 'email':
                $rules['format'] = 'email';
                $rules['max_length'] = 255;
                break;

            case 'phone':
                $rules['min_length'] = 7;
                $rules['max_length'] = 20;
                break;

            case 'decimal':
                $rules['numeric'] = true;
                $rules['min'] = 0;
                break;

            case 'date':
                $rules['format'] = 'date';
                break;

            case 'url':
                $rules['format'] = 'url';
                break;

            case 'text':
                $rules['max_length'] = 65535;
                break;

            case 'string':
            default:
                $rules['max_length'] = 255;
                break;
        }

        return $rules;
    }

    /**
     * Build business rules from field configuration
     *
     * @param array $fieldConfig
     * @return array
     */
    private function buildBusinessRules(array $fieldConfig): array
    {
        $rules = [];

        // Add data type-specific business rules
        switch ($fieldConfig['data_type']) {
            case 'decimal':
                $rules['allow_negative'] = false;
                $rules['decimal_places'] = 2;
                break;

            case 'phone':
                $rules['allow_international'] = true;
                $rules['formats'] = ['+389', '070', '071', '072', '075', '076', '077', '078'];
                break;

            case 'vat_number':
                $rules['country_code'] = 'MK';
                $rules['length'] = 13;
                break;

            case 'currency':
                $rules['allowed_currencies'] = ['MKD', 'EUR', 'USD'];
                break;

            case 'postal_code':
                $rules['min_length'] = 4;
                $rules['max_length'] = 10;
                break;
        }

        return $rules;
    }

    /**
     * Extract Macedonian-specific patterns from field configuration
     *
     * @param array $fieldConfig
     * @return array
     */
    private function extractMacedonianPatterns(array $fieldConfig): array
    {
        $patterns = [];

        // Get Macedonian synonyms
        if (isset($fieldConfig['synonyms']['mk'])) {
            $patterns = $fieldConfig['synonyms']['mk'];
        }

        // Add Cyrillic-specific regex patterns from the patterns array
        if (isset($fieldConfig['patterns'])) {
            foreach ($fieldConfig['patterns'] as $pattern) {
                // Check if pattern contains Cyrillic characters
                if (preg_match('/[\x{0400}-\x{04FF}]/u', $pattern)) {
                    $patterns[] = $pattern;
                }
            }
        }

        return array_values(array_unique($patterns));
    }

    /**
     * Build language-specific variations
     *
     * @param array $fieldConfig
     * @param array $languages
     * @return array
     */
    private function buildLanguageVariations(array $fieldConfig, array $languages): array
    {
        $variations = [];

        foreach ($languages as $lang) {
            if (isset($fieldConfig['synonyms'][$lang])) {
                $variations[$lang] = $fieldConfig['synonyms'][$lang];
            }
        }

        return $variations;
    }

    /**
     * Build test cases from field configuration
     *
     * @param array $fieldConfig
     * @return array
     */
    private function buildTestCases(array $fieldConfig): array
    {
        $testCases = [];

        // Create test cases from examples
        if (isset($fieldConfig['examples'])) {
            foreach ($fieldConfig['examples'] as $index => $example) {
                $testCases[] = [
                    'name' => "Example " . ($index + 1),
                    'input' => $example,
                    'expected_output' => $example, // Direct mapping
                    'context' => [],
                ];
            }
        }

        // Add test cases for each language's first synonym
        if (isset($fieldConfig['synonyms'])) {
            foreach ($fieldConfig['synonyms'] as $lang => $synonyms) {
                if (!empty($synonyms) && isset($fieldConfig['examples'][0])) {
                    $testCases[] = [
                        'name' => "Language: {$lang}",
                        'input' => $fieldConfig['examples'][0],
                        'expected_output' => $fieldConfig['examples'][0],
                        'context' => ['language' => $lang],
                    ];
                }
            }
        }

        return $testCases;
    }
}

// CLAUDE-CHECKPOINT
