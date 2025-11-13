<?php

namespace App\Services\Import\Intelligent\Matchers;

use Illuminate\Support\Collection;

/**
 * Synonym-based field matcher
 *
 * Matches CSV field names using multilingual synonym lookup.
 * Supports English, Macedonian, Albanian, and Serbian variations.
 */
class SynonymMatcher implements MatcherInterface
{
    /**
     * Find synonym matches for a CSV field
     *
     * Strategy:
     * 1. Normalize CSV field name
     * 2. Check against all field_variations in MappingRule
     * 3. Check against language_variations
     * 4. Match English, Macedonian, Albanian, Serbian synonyms
     * 5. Return matches with confidence 0.90
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

            // Skip exact matches (handled by ExactMatcher)
            if ($this->normalize($rule->source_field) === $normalizedCsvField) {
                continue;
            }

            // Check field_variations for near-synonym matches
            $match = $this->findSynonymInArray($normalizedCsvField, $rule->field_variations ?? []);
            if ($match) {
                $candidates[] = $this->buildCandidate(
                    $rule->target_field,
                    $match['source'],
                    $rule->id,
                    $match['confidence']
                );
                continue;
            }

            // Check language_variations for multilingual synonyms
            $match = $this->findSynonymInArray($normalizedCsvField, $rule->language_variations ?? []);
            if ($match) {
                $candidates[] = $this->buildCandidate(
                    $rule->target_field,
                    $match['source'],
                    $rule->id,
                    $match['confidence']
                );
                continue;
            }
        }

        return $candidates;
    }

    /**
     * Find synonym match in an array of variations
     *
     * Uses fuzzy matching to find close variations that aren't exact matches
     *
     * @param string $normalizedField Normalized CSV field
     * @param array $variations Array of field variations
     * @return array|null Match info or null if no match
     */
    private function findSynonymInArray(string $normalizedField, array $variations): ?array
    {
        if (empty($variations)) {
            return null;
        }

        foreach ($variations as $variation) {
            $normalizedVariation = $this->normalize($variation);

            // Skip exact matches (those should be handled by ExactMatcher)
            if ($normalizedVariation === $normalizedField) {
                continue;
            }

            // Check if fields are similar synonyms
            if ($this->areSynonyms($normalizedField, $normalizedVariation)) {
                return [
                    'source' => $variation,
                    'confidence' => 0.90, // High confidence for synonym matches
                ];
            }
        }

        return null;
    }

    /**
     * Determine if two normalized fields are synonyms
     *
     * Strategy:
     * 1. Check for common multilingual variations
     * 2. Check for underscores vs no underscores (e.g., "customer_name" vs "customername")
     * 3. Check for common abbreviations
     * 4. Check for singular/plural variations
     *
     * @param string $field1 First normalized field
     * @param string $field2 Second normalized field
     * @return bool True if fields are synonyms
     */
    private function areSynonyms(string $field1, string $field2): bool
    {
        // Remove underscores and compare
        $stripped1 = str_replace(['_', '-', ' '], '', $field1);
        $stripped2 = str_replace(['_', '-', ' '], '', $field2);

        if ($stripped1 === $stripped2) {
            return true;
        }

        // Check common multilingual variations
        $synonymMap = $this->getSynonymMap();

        // Normalize both fields to their base synonym
        $base1 = $this->getBaseSynonym($field1, $synonymMap);
        $base2 = $this->getBaseSynonym($field2, $synonymMap);

        if ($base1 && $base2 && $base1 === $base2) {
            return true;
        }

        // Check for common abbreviations
        if ($this->areAbbreviations($field1, $field2)) {
            return true;
        }

        // Check for singular/plural variations
        if ($this->areSingularPlural($field1, $field2)) {
            return true;
        }

        return false;
    }

    /**
     * Get synonym mapping for common multilingual variations
     *
     * @return array Synonym map
     */
    private function getSynonymMap(): array
    {
        return [
            // Customer variations
            'customer' => ['customer', 'client', 'клиент', 'klient', 'klijent', 'emri_klientit', 'ime_klijenta'],
            // Name variations
            'name' => ['name', 'име', 'emri', 'naziv', 'naslov', 'customer_name', 'client_name'],
            // Email variations
            'email' => ['email', 'е-пошта', 'eposta', 'mail', 'e_mail', 'e-mail'],
            // Phone variations
            'phone' => ['phone', 'telefon', 'телефон', 'telefoni', 'tel', 'mobile', 'мобилен'],
            // Address variations
            'address' => ['address', 'адреса', 'adresa', 'adresse', 'street', 'улица'],
            // Invoice variations
            'invoice' => ['invoice', 'фактура', 'faktura', 'fakturë', 'račun', 'invoice_number', 'broj_fakture'],
            // Amount variations
            'amount' => ['amount', 'износ', 'сума', 'shuma', 'iznos', 'total', 'вкупно', 'totali'],
            // Date variations
            'date' => ['date', 'датум', 'data', 'datum', 'invoice_date', 'datum_fakture'],
            // Tax variations
            'tax' => ['tax', 'данок', 'tatim', 'porez', 'vat', 'ddv', 'pdv'],
            // Description variations
            'description' => ['description', 'опис', 'përshkrim', 'opis', 'desc', 'пояснување'],
            // Quantity variations
            'quantity' => ['quantity', 'количина', 'sasi', 'količina', 'qty', 'кол'],
            // Price variations
            'price' => ['price', 'цена', 'çmim', 'cena', 'cijena', 'единечна_цена', 'unit_price'],
        ];
    }

    /**
     * Get base synonym for a field
     *
     * @param string $field Field name
     * @param array $synonymMap Synonym map
     * @return string|null Base synonym or null
     */
    private function getBaseSynonym(string $field, array $synonymMap): ?string
    {
        foreach ($synonymMap as $base => $synonyms) {
            foreach ($synonyms as $synonym) {
                if (mb_strpos($field, $synonym) !== false || mb_strpos($synonym, $field) !== false) {
                    return $base;
                }
            }
        }

        return null;
    }

    /**
     * Check if fields are abbreviations of each other
     *
     * @param string $field1 First field
     * @param string $field2 Second field
     * @return bool True if abbreviations
     */
    private function areAbbreviations(string $field1, string $field2): bool
    {
        $abbreviations = [
            'qty' => 'quantity',
            'amt' => 'amount',
            'desc' => 'description',
            'num' => 'number',
            'inv' => 'invoice',
            'cust' => 'customer',
            'tel' => 'telephone',
            'addr' => 'address',
        ];

        foreach ($abbreviations as $short => $long) {
            if (($field1 === $short && mb_strpos($field2, $long) !== false) ||
                ($field2 === $short && mb_strpos($field1, $long) !== false)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if fields are singular/plural variations
     *
     * @param string $field1 First field
     * @param string $field2 Second field
     * @return bool True if singular/plural
     */
    private function areSingularPlural(string $field1, string $field2): bool
    {
        // Simple check for 's' ending
        if (rtrim($field1, 's') === rtrim($field2, 's')) {
            return true;
        }

        return false;
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
     * @param string $source What matched
     * @param int $ruleId MappingRule ID
     * @param float $confidence Confidence score
     * @return array Candidate array
     */
    private function buildCandidate(string $targetField, string $source, int $ruleId, float $confidence): array
    {
        return [
            'target_field' => $targetField,
            'confidence' => $confidence,
            'method' => 'synonym_match',
            'source' => $source,
            'rule_id' => $ruleId,
        ];
    }
}

// CLAUDE-CHECKPOINT
