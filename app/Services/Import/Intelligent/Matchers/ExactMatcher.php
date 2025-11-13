<?php

namespace App\Services\Import\Intelligent\Matchers;

use Illuminate\Support\Collection;

/**
 * Exact field name matcher
 *
 * Matches CSV field names exactly against database mapping rules.
 * This matcher has the highest confidence (1.0) as it represents
 * perfect matches.
 */
class ExactMatcher implements MatcherInterface
{
    /**
     * Find exact matches for a CSV field
     *
     * Strategy:
     * 1. Look up CSV field name in MappingRule::source_field (exact match)
     * 2. Check field_variations array for exact matches
     * 3. Check language_variations array for exact matches
     * 4. Return all matches with confidence 1.0
     *
     * @param string $csvField CSV column name
     * @param array $analysis Field analysis from FieldAnalyzer
     * @param Collection $rules Available MappingRule records
     * @param string $entityType Entity type being imported
     * @return array Array of candidates with confidence scores
     */
    public function match(
        string $csvField,
        array $analysis,
        Collection $rules,
        string $entityType
    ): array {
        $candidates = [];
        $normalizedCsvField = $this->normalize($csvField);

        // Return empty array if field is empty
        if (empty($normalizedCsvField)) {
            return [];
        }

        foreach ($rules as $rule) {
            // Skip rules for different entity types
            if ($rule->entity_type !== $entityType) {
                continue;
            }

            // Skip inactive rules
            if (!$rule->is_active) {
                continue;
            }

            // Check exact match on source_field
            if ($this->normalize($rule->source_field) === $normalizedCsvField) {
                $candidates[] = $this->buildCandidate(
                    $rule->target_field,
                    $rule->source_field,
                    $rule->id
                );
                continue;
            }

            // Check field_variations
            if (!empty($rule->field_variations) && is_array($rule->field_variations)) {
                foreach ($rule->field_variations as $variation) {
                    if ($this->normalize($variation) === $normalizedCsvField) {
                        $candidates[] = $this->buildCandidate(
                            $rule->target_field,
                            $variation,
                            $rule->id
                        );
                        break; // Only add one candidate per rule
                    }
                }
            }

            // Check language_variations
            if (!empty($rule->language_variations) && is_array($rule->language_variations)) {
                // Check if we already added this rule
                $alreadyAdded = collect($candidates)->contains('rule_id', $rule->id);

                if (!$alreadyAdded) {
                    // Flatten language_variations (it's a nested array: ["en" => [...], "mk" => [...]])
                    foreach ($rule->language_variations as $lang => $variations) {
                        if (is_array($variations)) {
                            foreach ($variations as $variation) {
                                if (is_string($variation) && $this->normalize($variation) === $normalizedCsvField) {
                                    $candidates[] = $this->buildCandidate(
                                        $rule->target_field,
                                        $variation,
                                        $rule->id
                                    );
                                    break 2; // Exit both loops - only add one candidate per rule
                                }
                            }
                        }
                    }
                }
            }
        }

        return $candidates;
    }

    /**
     * Normalize a field name for comparison
     *
     * Converts to lowercase and handles special characters consistently
     *
     * @param string $field Field name to normalize
     * @return string Normalized field name
     */
    private function normalize(string $field): string
    {
        // Convert to lowercase
        $normalized = mb_strtolower(trim($field), 'UTF-8');

        // Remove any leading/trailing whitespace
        $normalized = trim($normalized);

        return $normalized;
    }

    /**
     * Build a candidate result array
     *
     * @param string $targetField Target field name
     * @param string $source What matched
     * @param int $ruleId MappingRule ID
     * @return array Candidate array
     */
    private function buildCandidate(string $targetField, string $source, int $ruleId): array
    {
        return [
            'target_field' => $targetField,
            'confidence' => 1.0,
            'method' => 'exact_match',
            'source' => $source,
            'rule_id' => $ruleId,
        ];
    }
}

// CLAUDE-CHECKPOINT
