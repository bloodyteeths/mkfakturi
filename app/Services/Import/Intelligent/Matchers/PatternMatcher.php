<?php

namespace App\Services\Import\Intelligent\Matchers;

use Illuminate\Support\Collection;

/**
 * Pattern-based field matcher using regex
 *
 * Matches CSV field names using regex patterns to identify common
 * field types and naming conventions.
 */
class PatternMatcher implements MatcherInterface
{
    /**
     * Find pattern matches for a CSV field
     *
     * Strategy:
     * 1. Check if CSV field matches patterns in MappingRule::macedonian_patterns
     * 2. Check against built-in common patterns
     * 3. Return matches with confidence based on pattern strength
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

            // Check against macedonian_patterns
            if (!empty($rule->macedonian_patterns) && is_array($rule->macedonian_patterns)) {
                foreach ($rule->macedonian_patterns as $pattern) {
                    if ($this->matchesPattern($csvField, $pattern)) {
                        $candidates[] = $this->buildCandidate(
                            $rule->target_field,
                            $pattern,
                            $rule->id,
                            0.85 // High confidence for Macedonian patterns
                        );
                        break; // Only add one candidate per rule
                    }
                }
            }

            // Check against format_patterns
            if (!empty($rule->format_patterns) && is_array($rule->format_patterns)) {
                $alreadyAdded = collect($candidates)->contains('rule_id', $rule->id);

                if (!$alreadyAdded) {
                    foreach ($rule->format_patterns as $pattern) {
                        if ($this->matchesPattern($csvField, $pattern)) {
                            $candidates[] = $this->buildCandidate(
                                $rule->target_field,
                                $pattern,
                                $rule->id,
                                0.80 // Good confidence for format patterns
                            );
                            break; // Only add one candidate per rule
                        }
                    }
                }
            }
        }

        // Also check against common built-in patterns
        $builtInMatches = $this->matchBuiltInPatterns($csvField, $entityType, $rules);
        $candidates = array_merge($candidates, $builtInMatches);

        // Remove duplicates based on rule_id (keep highest confidence)
        $candidates = $this->deduplicateCandidates($candidates);

        // Sort by confidence (highest first)
        usort($candidates, function ($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return $candidates;
    }

    /**
     * Check if field matches a regex pattern
     *
     * @param string $field Field name
     * @param string $pattern Regex pattern
     * @return bool True if matches
     */
    private function matchesPattern(string $field, string $pattern): bool
    {
        try {
            // Ensure pattern has delimiters
            if (!preg_match('/^\/.*\/[imsxu]*$/', $pattern)) {
                $pattern = '/' . $pattern . '/i';
            }

            return (bool) preg_match($pattern, $field);
        } catch (\Exception $e) {
            // Invalid regex pattern, return false
            return false;
        }
    }

    /**
     * Match against common built-in patterns
     *
     * @param string $csvField CSV field name
     * @param string $entityType Entity type
     * @param Collection $rules Available mapping rules
     * @return array Candidates from built-in patterns
     */
    private function matchBuiltInPatterns(string $csvField, string $entityType, Collection $rules): array
    {
        $candidates = [];
        $patterns = $this->getBuiltInPatterns();

        foreach ($patterns as $targetField => $patternInfo) {
            // Check if this pattern is relevant for the entity type
            if (!empty($patternInfo['entities']) && !in_array($entityType, $patternInfo['entities'])) {
                continue;
            }

            // Check if field matches any of the patterns
            foreach ($patternInfo['patterns'] as $pattern) {
                if ($this->matchesPattern($csvField, $pattern)) {
                    // Find the corresponding rule
                    $rule = $rules->first(function ($r) use ($targetField, $entityType) {
                        return $r->target_field === $targetField && $r->entity_type === $entityType;
                    });

                    if ($rule) {
                        $candidates[] = $this->buildCandidate(
                            $targetField,
                            $pattern,
                            $rule->id,
                            $patternInfo['confidence']
                        );
                    }
                    break; // Only match once per target field
                }
            }
        }

        return $candidates;
    }

    /**
     * Get built-in pattern definitions
     *
     * @return array Pattern definitions
     */
    private function getBuiltInPatterns(): array
    {
        return [
            // Date patterns
            'date' => [
                'patterns' => [
                    '/_(date|datum|дата|data)$/i',
                    '/^(date|datum|дата|data)_/i',
                    '/(invoice|faktura)_(date|datum)/i',
                    '/(due|payment)_(date|datum)/i',
                ],
                'confidence' => 0.80,
                'entities' => ['invoice', 'payment', 'expense'],
            ],

            // Amount/Money patterns
            'amount' => [
                'patterns' => [
                    '/_(amount|износ|suma|shuma|iznos)$/i',
                    '/_(price|цена|çmim|cena)$/i',
                    '/_(cost|трошок|kosto)$/i',
                    '/_(total|вкупно|totali|ukupno)$/i',
                    '/^(amount|total|price)/i',
                ],
                'confidence' => 0.85,
                'entities' => ['invoice', 'payment', 'expense', 'item'],
            ],

            // Customer patterns
            'customer_name' => [
                'patterns' => [
                    '/^(customer|client|клиент|klient)_/i',
                    '/_(customer|client|клиент)$/i',
                    '/^(emri|ime)_(klient|klijenta)/i',
                ],
                'confidence' => 0.85,
                'entities' => ['customer', 'invoice'],
            ],

            // Email patterns
            'email' => [
                'patterns' => [
                    '/_(email|mail|е-пошта|eposta)$/i',
                    '/^(email|e_mail|е_пошта)/i',
                ],
                'confidence' => 0.85,
                'entities' => ['customer'],
            ],

            // Phone patterns
            'phone' => [
                'patterns' => [
                    '/_(phone|tel|telefon|телефон|mobile)$/i',
                    '/^(phone|tel|mob)/i',
                ],
                'confidence' => 0.80,
                'entities' => ['customer'],
            ],

            // Invoice number patterns
            'invoice_number' => [
                'patterns' => [
                    '/(invoice|faktura)_(number|num|broj|број)/i',
                    '/^(inv|fakt)_(num|no|nr)/i',
                    '/_(invoice|faktura)$/i',
                ],
                'confidence' => 0.85,
                'entities' => ['invoice'],
            ],

            // Tax patterns
            'tax' => [
                'patterns' => [
                    '/_(tax|vat|ddv|pdv|данок|tatim)$/i',
                    '/^(tax|vat|ddv)/i',
                ],
                'confidence' => 0.85,
                'entities' => ['invoice', 'item', 'expense'],
            ],

            // Description patterns
            'description' => [
                'patterns' => [
                    '/_(description|desc|опис|përshkrim|opis)$/i',
                    '/^(desc|опис)/i',
                    '/_(note|notes|забелешка)$/i',
                ],
                'confidence' => 0.75,
                'entities' => ['invoice', 'item', 'expense', 'payment'],
            ],

            // Quantity patterns
            'quantity' => [
                'patterns' => [
                    '/_(quantity|qty|количина|sasi|količina|кол)$/i',
                    '/^(qty|кол)/i',
                ],
                'confidence' => 0.85,
                'entities' => ['item'],
            ],

            // Unit price patterns
            'unit_price' => [
                'patterns' => [
                    '/(unit|единечна)_(price|цена)/i',
                    '/_(unit_price|единечна_цена)/i',
                ],
                'confidence' => 0.85,
                'entities' => ['item'],
            ],

            // Address patterns
            'address' => [
                'patterns' => [
                    '/_(address|addr|адреса|adresa)$/i',
                    '/^(address|адреса)/i',
                    '/_(street|улица|rruga)/i',
                ],
                'confidence' => 0.80,
                'entities' => ['customer'],
            ],

            // City patterns
            'city' => [
                'patterns' => [
                    '/_(city|град|qytet|qyteti)$/i',
                    '/^(city|град)/i',
                ],
                'confidence' => 0.80,
                'entities' => ['customer'],
            ],

            // Postal code patterns
            'postal_code' => [
                'patterns' => [
                    '/(postal|zip)_(code|кoд)/i',
                    '/_(zip|zipcode)$/i',
                ],
                'confidence' => 0.80,
                'entities' => ['customer'],
            ],

            // Country patterns
            'country' => [
                'patterns' => [
                    '/_(country|држава|vendi|zemlja)$/i',
                    '/^(country|држава)/i',
                ],
                'confidence' => 0.80,
                'entities' => ['customer'],
            ],
        ];
    }

    /**
     * Remove duplicate candidates, keeping highest confidence
     *
     * @param array $candidates Candidate array
     * @return array Deduplicated candidates
     */
    private function deduplicateCandidates(array $candidates): array
    {
        $deduplicated = [];
        $seen = [];

        foreach ($candidates as $candidate) {
            $key = $candidate['rule_id'];

            if (!isset($seen[$key]) || $candidate['confidence'] > $seen[$key]['confidence']) {
                $seen[$key] = $candidate;
            }
        }

        return array_values($seen);
    }

    /**
     * Normalize a field name for comparison
     *
     * @param string $field Field name to normalize
     * @return string Normalized field name
     */
    private function normalize(string $field): string
    {
        // Convert to lowercase using UTF-8
        $normalized = mb_strtolower(trim($field), 'UTF-8');

        // Remove any leading/trailing whitespace
        $normalized = trim($normalized);

        return $normalized;
    }

    /**
     * Build a candidate result array
     *
     * @param string $targetField Target field name
     * @param string $source Pattern that matched
     * @param int $ruleId MappingRule ID
     * @param float $confidence Confidence score (0.70-0.85)
     * @return array Candidate array
     */
    private function buildCandidate(
        string $targetField,
        string $source,
        int $ruleId,
        float $confidence
    ): array {
        // Ensure confidence is in valid range for pattern matching
        $confidence = max(0.70, min(0.85, $confidence));

        return [
            'target_field' => $targetField,
            'confidence' => round($confidence, 2),
            'method' => 'pattern_match',
            'source' => $source,
            'rule_id' => $ruleId,
        ];
    }
}

// CLAUDE-CHECKPOINT
