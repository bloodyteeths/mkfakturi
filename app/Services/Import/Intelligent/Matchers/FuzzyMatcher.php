<?php

namespace App\Services\Import\Intelligent\Matchers;

use Illuminate\Support\Collection;

/**
 * Fuzzy field matcher using Levenshtein distance
 *
 * Matches CSV field names using string similarity to handle typos
 * and variations. Uses Levenshtein distance algorithm.
 */
class FuzzyMatcher implements MatcherInterface
{
    /**
     * Minimum similarity threshold (0.65 = 65% similar)
     */
    private const MIN_SIMILARITY = 0.65;

    /**
     * Maximum Levenshtein distance to consider
     * This prevents comparing very long strings with minimal similarity
     */
    private const MAX_DISTANCE = 10;

    /**
     * Find fuzzy matches for a CSV field
     *
     * Strategy:
     * 1. Calculate Levenshtein distance between normalized field names
     * 2. Convert distance to similarity score
     * 3. Only consider matches with similarity >= 0.65
     * 4. Return confidence based on similarity score
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

        // Don't process very short fields (less than 3 characters)
        if (mb_strlen($normalizedCsvField, 'UTF-8') < 3) {
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

            // Check against source_field
            $match = $this->calculateSimilarity($normalizedCsvField, $this->normalize($rule->source_field));
            if ($match['similarity'] >= self::MIN_SIMILARITY) {
                $candidates[] = $this->buildCandidate(
                    $rule->target_field,
                    $rule->source_field,
                    $rule->id,
                    $match['similarity'],
                    $match['distance']
                );
                continue; // Only add one candidate per rule
            }

            // Check against field_variations
            if (!empty($rule->field_variations) && is_array($rule->field_variations)) {
                foreach ($rule->field_variations as $variation) {
                    $match = $this->calculateSimilarity($normalizedCsvField, $this->normalize($variation));
                    if ($match['similarity'] >= self::MIN_SIMILARITY) {
                        $candidates[] = $this->buildCandidate(
                            $rule->target_field,
                            $variation,
                            $rule->id,
                            $match['similarity'],
                            $match['distance']
                        );
                        break; // Only add one candidate per rule
                    }
                }
            }
        }

        // Sort by confidence (highest first)
        usort($candidates, function ($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return $candidates;
    }

    /**
     * Calculate similarity between two strings using Levenshtein distance
     *
     * Formula:
     * similarity = 1.0 - (distance / maxLength)
     *
     * @param string $string1 First string (normalized)
     * @param string $string2 Second string (normalized)
     * @return array Array with 'similarity' and 'distance' keys
     */
    private function calculateSimilarity(string $string1, string $string2): array
    {
        // Handle empty strings
        if (empty($string1) || empty($string2)) {
            return ['similarity' => 0.0, 'distance' => PHP_INT_MAX];
        }

        // Handle identical strings
        if ($string1 === $string2) {
            return ['similarity' => 1.0, 'distance' => 0];
        }

        // Get lengths
        $length1 = mb_strlen($string1, 'UTF-8');
        $length2 = mb_strlen($string2, 'UTF-8');
        $maxLength = max($length1, $length2);

        // Quick check: if length difference is too large, skip
        if (abs($length1 - $length2) > self::MAX_DISTANCE) {
            return ['similarity' => 0.0, 'distance' => self::MAX_DISTANCE + 1];
        }

        // Calculate Levenshtein distance
        // For Unicode support, we need to use mb_* functions
        $distance = $this->levenshteinUtf8($string1, $string2);

        // If distance is too large, return low similarity
        if ($distance > self::MAX_DISTANCE) {
            return ['similarity' => 0.0, 'distance' => $distance];
        }

        // Calculate similarity score
        $similarity = 1.0 - ($distance / $maxLength);

        // Ensure similarity is between 0 and 1
        $similarity = max(0.0, min(1.0, $similarity));

        return [
            'similarity' => round($similarity, 2),
            'distance' => $distance,
        ];
    }

    /**
     * UTF-8 compatible Levenshtein distance calculation
     *
     * PHP's built-in levenshtein() doesn't handle UTF-8 well,
     * so we use this custom implementation.
     *
     * @param string $string1 First string
     * @param string $string2 Second string
     * @return int Levenshtein distance
     */
    private function levenshteinUtf8(string $string1, string $string2): int
    {
        // Convert strings to arrays of characters
        $chars1 = mb_str_split($string1, 1, 'UTF-8');
        $chars2 = mb_str_split($string2, 1, 'UTF-8');

        $length1 = count($chars1);
        $length2 = count($chars2);

        // Handle edge cases
        if ($length1 === 0) {
            return $length2;
        }
        if ($length2 === 0) {
            return $length1;
        }

        // Optimization: if strings are too long, use PHP's built-in
        // for ASCII-compatible strings
        if ($length1 > 255 || $length2 > 255) {
            // Try using built-in if strings are ASCII
            if (mb_check_encoding($string1, 'ASCII') && mb_check_encoding($string2, 'ASCII')) {
                return levenshtein($string1, $string2);
            }
            // For very long UTF-8 strings, use a simplified calculation
            return (int) (max($length1, $length2) * 0.5); // Rough estimate
        }

        // Initialize matrix
        $matrix = [];
        for ($i = 0; $i <= $length1; $i++) {
            $matrix[$i] = [$i];
        }
        for ($j = 0; $j <= $length2; $j++) {
            $matrix[0][$j] = $j;
        }

        // Calculate distances
        for ($i = 1; $i <= $length1; $i++) {
            for ($j = 1; $j <= $length2; $j++) {
                $cost = ($chars1[$i - 1] === $chars2[$j - 1]) ? 0 : 1;
                $matrix[$i][$j] = min(
                    $matrix[$i - 1][$j] + 1,      // deletion
                    $matrix[$i][$j - 1] + 1,      // insertion
                    $matrix[$i - 1][$j - 1] + $cost // substitution
                );
            }
        }

        return $matrix[$length1][$length2];
    }

    /**
     * Normalize a field name for comparison
     *
     * Removes special characters and converts to lowercase
     *
     * @param string $field Field name to normalize
     * @return string Normalized field name
     */
    private function normalize(string $field): string
    {
        // Convert to lowercase using UTF-8
        $normalized = mb_strtolower(trim($field), 'UTF-8');

        // Replace common separators with underscores for consistency
        $normalized = preg_replace('/[\s\-\.]+/', '_', $normalized);

        // Remove multiple consecutive underscores
        $normalized = preg_replace('/_+/', '_', $normalized);

        // Remove leading/trailing underscores
        $normalized = trim($normalized, '_');

        return $normalized;
    }

    /**
     * Build a candidate result array
     *
     * Confidence is based on similarity score but adjusted:
     * - 0.95-1.0 similarity → 0.90 confidence (very strong match)
     * - 0.85-0.95 similarity → 0.80-0.90 confidence (strong match)
     * - 0.75-0.85 similarity → 0.70-0.80 confidence (good match)
     * - 0.65-0.75 similarity → 0.65-0.70 confidence (weak match)
     *
     * @param string $targetField Target field name
     * @param string $source What matched
     * @param int $ruleId MappingRule ID
     * @param float $similarity Similarity score (0.0-1.0)
     * @param int $distance Levenshtein distance
     * @return array Candidate array
     */
    private function buildCandidate(
        string $targetField,
        string $source,
        int $ruleId,
        float $similarity,
        int $distance
    ): array {
        // Adjust confidence based on similarity
        // We scale down the confidence slightly since fuzzy matches are less certain
        $confidence = $similarity * 0.95; // Max confidence is 0.95 for fuzzy matches

        // Ensure confidence is in valid range
        $confidence = max(0.65, min(0.95, $confidence));

        return [
            'target_field' => $targetField,
            'confidence' => round($confidence, 2),
            'method' => 'fuzzy_match',
            'source' => $source,
            'rule_id' => $ruleId,
            'similarity' => $similarity,
            'distance' => $distance,
        ];
    }
}

// CLAUDE-CHECKPOINT
