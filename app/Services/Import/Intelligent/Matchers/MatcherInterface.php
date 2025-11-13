<?php

namespace App\Services\Import\Intelligent\Matchers;

use Illuminate\Support\Collection;

/**
 * Interface for field matchers
 *
 * Matchers are responsible for finding matching candidates for CSV fields
 * by comparing them against available mapping rules.
 */
interface MatcherInterface
{
    /**
     * Find matching candidates for a CSV field
     *
     * @param string $csvField CSV column name
     * @param array $analysis Field analysis from FieldAnalyzer
     * @param Collection $rules Available MappingRule records
     * @param string $entityType Entity type being imported (customer, invoice, item, etc.)
     * @return array Array of candidates with confidence scores
     *
     * Expected return format:
     * [
     *     [
     *         'target_field' => 'invoice_number',
     *         'confidence' => 0.95,
     *         'method' => 'exact_match',
     *         'source' => 'INV-001',
     *         'rule_id' => 123
     *     ]
     * ]
     */
    public function match(
        string $csvField,
        array $analysis,
        Collection $rules,
        string $entityType
    ): array;
}
