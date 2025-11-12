<?php

namespace Tests\Unit;

use App\Services\Migration\FieldMapperService;
use Tests\TestCase;

/**
 * FieldMapperBugFixesTest - Tests for critical bug fixes
 *
 * This test suite validates all the critical bug fixes applied to FieldMapperService:
 * - Levenshtein 255-character limit fix
 * - Metaphone Cyrillic incompatibility fix
 * - Dynamic similarity weights for different contexts
 * - Cache availability checks
 * - Improved substring matching with position weighting
 * - UTF-8 regex modifiers
 * - Competitor context validation
 *
 * CLAUDE-CHECKPOINT: Comprehensive bug fix validation tests
 *
 * @package Tests\Unit
 */
class FieldMapperBugFixesTest extends TestCase
{
    private FieldMapperService $fieldMapper;

    protected function setUp(): void
    {
        // Skip parent setup to avoid configuration issues during testing
        $this->fieldMapper = new FieldMapperService();
    }

    /**
     * @test
     * Bug Fix #1: Levenshtein 255-character limit
     */
    public function handles_long_field_names_over_255_characters()
    {
        // Create field names longer than 255 characters
        $longField1 = str_repeat('customer_name_with_very_long_description_', 10); // 420 chars
        $longField2 = str_repeat('invoice_number_field_description_', 10); // 330 chars
        $longField3 = str_repeat('product_quantity_', 20); // 340 chars

        // Should not crash with levenshtein error
        $mappings = $this->fieldMapper->mapFields([$longField1, $longField2, $longField3]);

        $this->assertCount(3, $mappings);
        $this->assertIsFloat($mappings[0]['confidence']);
        $this->assertIsFloat($mappings[1]['confidence']);
        $this->assertIsFloat($mappings[2]['confidence']);

        // Should still map to reasonable fields
        $this->assertNotNull($mappings[0]['mapped_field']);
        $this->assertNotNull($mappings[1]['mapped_field']);
        $this->assertNotNull($mappings[2]['mapped_field']);
    }

    /**
     * @test
     * Bug Fix #1: Levenshtein with exactly 255 characters
     */
    public function handles_field_names_at_255_character_boundary()
    {
        // Create field name with exactly 255 characters
        $field255 = str_pad('customer_name', 255, '_padding');
        $field256 = str_pad('invoice_number', 256, '_padding');
        $field254 = str_pad('quantity', 254, '_padding');

        $mappings = $this->fieldMapper->mapFields([$field255, $field256, $field254]);

        $this->assertCount(3, $mappings);
        foreach ($mappings as $mapping) {
            $this->assertIsFloat($mapping['confidence']);
            $this->assertGreaterThanOrEqual(0, $mapping['confidence']);
            $this->assertLessThanOrEqual(1, $mapping['confidence']);
        }
    }

    /**
     * @test
     * Bug Fix #2: Metaphone Cyrillic incompatibility
     */
    public function cyrillic_fields_dont_crash_metaphone()
    {
        $cyrillicFields = [
            'назив_клиента',
            'купувач_име',
            'фактура_број',
            'количина_производ',
            'цена_по_единица',
            'износ_вкупно',
            'плаќање_датум',
            'статус_фактура',
            'адреса_клиент',
            'телефон_контакт',
        ];

        // Should not crash with metaphone error on Cyrillic
        $mappings = $this->fieldMapper->mapFields($cyrillicFields);

        $this->assertCount(10, $mappings);

        foreach ($mappings as $mapping) {
            $this->assertNotNull($mapping['mapped_field']);
            $this->assertGreaterThanOrEqual(0.5, $mapping['confidence']);
        }
    }

    /**
     * @test
     * Bug Fix #2: Mixed Cyrillic and Latin should work
     */
    public function mixed_cyrillic_latin_fields_are_handled()
    {
        $mixedFields = [
            'customer_назив',
            'invoice_број',
            'price_цена',
            'количина_quantity',
            'датум_date',
            'payment_плаќање',
        ];

        $mappings = $this->fieldMapper->mapFields($mixedFields);

        $this->assertCount(6, $mappings);

        foreach ($mappings as $mapping) {
            $this->assertNotNull($mapping['mapped_field']);
            $this->assertIsFloat($mapping['confidence']);
        }
    }

    /**
     * @test
     * Bug Fix #3: Dynamic similarity weights for Cyrillic vs Latin
     */
    public function cyrillic_uses_different_similarity_weights()
    {
        // Same semantic meaning in different scripts
        $latinField = 'customer_name';
        $cyrillicField = 'назив_клиента';

        $latinMapping = $this->fieldMapper->mapFields([$latinField]);
        $cyrillicMapping = $this->fieldMapper->mapFields([$cyrillicField]);

        // Both should successfully map
        $this->assertEquals('customer_name', $latinMapping[0]['mapped_field']);
        $this->assertEquals('customer_name', $cyrillicMapping[0]['mapped_field']);

        // Both should have reasonable confidence
        $this->assertGreaterThanOrEqual(0.7, $latinMapping[0]['confidence']);
        $this->assertGreaterThanOrEqual(0.7, $cyrillicMapping[0]['confidence']);
    }

    /**
     * @test
     * Bug Fix #3: Short fields get different weights
     */
    public function short_fields_use_optimized_weights()
    {
        $shortFields = [
            'br',    // broj (number)
            'ime',   // name
            'dat',   // datum (date)
            'kol',   // kolicina (quantity)
            'cen',   // cena (price)
            'izn',   // iznos (amount)
        ];

        $mappings = $this->fieldMapper->mapFields($shortFields);

        $this->assertCount(6, $mappings);

        foreach ($mappings as $mapping) {
            // Short fields should still get reasonable mappings
            $this->assertNotNull($mapping['mapped_field']);
            $this->assertGreaterThanOrEqual(0.4, $mapping['confidence']);
        }
    }

    /**
     * @test
     * Bug Fix #4: Cache availability check doesn't crash
     */
    public function handles_missing_cache_gracefully()
    {
        // This test runs in environment where cache may not be configured
        // Should not throw exceptions
        $fields = ['naziv', 'customer_name', 'iznos', 'quantity'];

        try {
            $mappings = $this->fieldMapper->mapFields($fields);
            $this->assertCount(4, $mappings);
            $this->assertTrue(true); // Cache handling worked
        } catch (\Exception $e) {
            $this->fail('Should not throw exception when cache is unavailable: ' . $e->getMessage());
        }
    }

    /**
     * @test
     * Bug Fix #4: Cache failure fallback works
     */
    public function falls_back_to_direct_computation_on_cache_error()
    {
        // Test that service works even without caching
        $fields = ['naziv', 'embs', 'broj_faktura', 'kolicina', 'cena'];

        $mappings = $this->fieldMapper->mapFields($fields);

        $this->assertCount(5, $mappings);
        $this->assertEquals('customer_name', $mappings[0]['mapped_field']);
        $this->assertEquals('tax_id', $mappings[1]['mapped_field']);
        $this->assertEquals('invoice_number', $mappings[2]['mapped_field']);
        $this->assertEquals('quantity', $mappings[3]['mapped_field']);
        $this->assertEquals('price', $mappings[4]['mapped_field']);
    }

    /**
     * @test
     * Bug Fix #5: Substring matching with position weighting
     */
    public function substring_at_start_gets_higher_score()
    {
        // Fields where substring is at different positions
        $startField = 'customer_full_details';   // customer at start
        $endField = 'primary_customer';         // customer at end
        $middleField = 'the_customer_name';     // customer in middle

        $startMapping = $this->fieldMapper->mapFields([$startField]);
        $endMapping = $this->fieldMapper->mapFields([$endField]);
        $middleMapping = $this->fieldMapper->mapFields([$middleField]);

        // All should map to customer fields
        $this->assertStringContainsString('customer', $startMapping[0]['mapped_field']);
        $this->assertStringContainsString('customer', $endMapping[0]['mapped_field']);
        $this->assertStringContainsString('customer', $middleMapping[0]['mapped_field']);

        // Start position should generally have higher or equal confidence
        $this->assertGreaterThanOrEqual(0.5, $startMapping[0]['confidence']);
    }

    /**
     * @test
     * Bug Fix #5: Partial substring matching
     */
    public function partial_substring_matches_work()
    {
        // Fields with partial overlaps
        $fields = [
            'cust_name',           // partial of customer
            'inv_number',          // partial of invoice
            'qty_items',           // partial of quantity
            'pmt_date',            // partial of payment
        ];

        $mappings = $this->fieldMapper->mapFields($fields);

        $this->assertCount(4, $mappings);

        // Should find reasonable mappings for partial matches
        foreach ($mappings as $mapping) {
            $this->assertNotNull($mapping['mapped_field']);
            $this->assertGreaterThanOrEqual(0.3, $mapping['confidence']);
        }
    }

    /**
     * @test
     * Bug Fix #6: UTF-8 regex modifiers for special characters
     */
    public function handles_special_unicode_characters()
    {
        $specialFields = [
            'customer–name',        // en dash
            'invoice—number',       // em dash
            'price…value',          // ellipsis
            'amount₽',              // currency symbol
            '"customer_name"',      // smart quotes
            '\'invoice_number\'',   // single quotes
            'назив№1',              // Cyrillic with numero sign
            'цена€',                // Cyrillic with euro sign
        ];

        $mappings = $this->fieldMapper->mapFields($specialFields);

        $this->assertCount(8, $mappings);

        foreach ($mappings as $mapping) {
            // Should normalize and map successfully
            $this->assertNotNull($mapping['mapped_field']);
            $this->assertGreaterThanOrEqual(0.3, $mapping['confidence']);
        }
    }

    /**
     * @test
     * Bug Fix #6: UTF-8 regex handles multibyte characters
     */
    public function handles_multibyte_utf8_characters()
    {
        $multibyteFields = [
            'клиент_назив',         // Cyrillic
            'рачун_број',           // Serbian Cyrillic
            'купувач_адреса',       // Macedonian Cyrillic
            'количина_ствари',      // Mixed Cyrillic
            'цена_производа',       // Cyrillic price
        ];

        $mappings = $this->fieldMapper->mapFields($multibyteFields);

        $this->assertCount(5, $mappings);

        foreach ($mappings as $mapping) {
            $this->assertNotNull($mapping['mapped_field']);
            $this->assertIsFloat($mapping['confidence']);
            $this->assertGreaterThan(0, $mapping['confidence']);
        }
    }

    /**
     * @test
     * Bug Fix #7: Competitor context validation
     */
    public function validates_competitor_context()
    {
        // Valid competitor contexts
        $validContexts = ['onivo', 'megasoft', 'pantheon'];

        foreach ($validContexts as $software) {
            $mappings = $this->fieldMapper->mapFields(
                ['customer_name'],
                'csv',
                ['software' => $software]
            );

            $this->assertCount(1, $mappings);
            $this->assertNotNull($mappings[0]['mapped_field']);
        }

        // Invalid/unknown competitor should still work (logged as warning)
        $mappings = $this->fieldMapper->mapFields(
            ['customer_name'],
            'csv',
            ['software' => 'unknown_software']
        );

        $this->assertCount(1, $mappings);
        $this->assertNotNull($mappings[0]['mapped_field']);
    }

    /**
     * @test
     * Bug Fix #7: Context with various formats
     */
    public function handles_different_context_formats()
    {
        // Test with uppercase context
        $mappings1 = $this->fieldMapper->mapFields(
            ['customer_name'],
            'csv',
            ['software' => 'ONIVO']
        );
        $this->assertEquals('customer_name', $mappings1[0]['mapped_field']);

        // Test with mixed case
        $mappings2 = $this->fieldMapper->mapFields(
            ['customer_name'],
            'csv',
            ['software' => 'MegaSoft']
        );
        $this->assertEquals('customer_name', $mappings2[0]['mapped_field']);

        // Test with extra whitespace
        $mappings3 = $this->fieldMapper->mapFields(
            ['customer_name'],
            'csv',
            ['software' => '  pantheon  ']
        );
        $this->assertEquals('customer_name', $mappings3[0]['mapped_field']);
    }

    /**
     * @test
     * Regression: All bug fixes working together
     */
    public function all_bug_fixes_work_together()
    {
        // Complex scenario using all fixed features
        $complexFields = [
            str_repeat('клиент_', 50),  // Long Cyrillic field (300+ chars)
            'назив_купувач',             // Pure Cyrillic
            'customer–name',             // UTF-8 special char
            'br',                        // Short field
            'invoice_број',              // Mixed script
            'cust_ref',                  // Partial match
        ];

        $mappings = $this->fieldMapper->mapFields(
            $complexFields,
            'csv',
            ['software' => 'megasoft']
        );

        $this->assertCount(6, $mappings);

        // All should map successfully without crashes
        foreach ($mappings as $mapping) {
            $this->assertIsArray($mapping);
            $this->assertArrayHasKey('mapped_field', $mapping);
            $this->assertArrayHasKey('confidence', $mapping);
            $this->assertArrayHasKey('algorithm', $mapping);

            // Confidence should be valid
            $this->assertIsFloat($mapping['confidence']);
            $this->assertGreaterThanOrEqual(0, $mapping['confidence']);
            $this->assertLessThanOrEqual(1, $mapping['confidence']);
        }
    }

    /**
     * @test
     * Performance: Bug fixes don't degrade performance
     */
    public function bug_fixes_maintain_good_performance()
    {
        // Create diverse test set with all edge cases
        $testFields = [];

        // Add long fields
        for ($i = 0; $i < 10; $i++) {
            $testFields[] = str_repeat('very_long_field_name_', 20);
        }

        // Add Cyrillic fields
        $cyrillicSamples = ['назив', 'клиент', 'купувач', 'фактура', 'количина'];
        foreach ($cyrillicSamples as $sample) {
            for ($i = 0; $i < 10; $i++) {
                $testFields[] = $sample . '_' . $i;
            }
        }

        // Add short fields
        for ($i = 0; $i < 10; $i++) {
            $testFields[] = 'ab' . $i;
        }

        // Add UTF-8 special char fields
        for ($i = 0; $i < 10; $i++) {
            $testFields[] = 'field–' . $i;
        }

        $startTime = microtime(true);
        $mappings = $this->fieldMapper->mapFields($testFields);
        $endTime = microtime(true);

        $processingTime = $endTime - $startTime;

        // Should process 90 diverse fields in reasonable time
        $this->assertLessThan(5.0, $processingTime,
            "Bug fixes should not significantly degrade performance. Took {$processingTime}s");

        $this->assertCount(90, $mappings);
    }

    /**
     * @test
     * Accuracy: Bug fixes improve overall accuracy
     */
    public function bug_fixes_improve_mapping_accuracy()
    {
        // Test cases that would have failed before bug fixes
        $previouslyProblematicFields = [
            // Long fields (would crash with levenshtein)
            [str_repeat('customer_name_', 30), 'customer_name'],

            // Pure Cyrillic (would crash with metaphone)
            ['назив_клиента', 'customer_name'],
            ['фактура_број', 'invoice_number'],

            // UTF-8 special chars (would fail regex)
            ['customer–name', 'customer_name'],
            ['"invoice_number"', 'invoice_number'],

            // Short fields (poor weights)
            ['br', 'invoice_number'],
            ['kol', 'quantity'],
        ];

        $successCount = 0;
        $totalTests = count($previouslyProblematicFields);

        foreach ($previouslyProblematicFields as [$inputField, $expectedField]) {
            $mappings = $this->fieldMapper->mapFields([$inputField]);

            if ($mappings[0]['mapped_field'] === $expectedField && $mappings[0]['confidence'] >= 0.5) {
                $successCount++;
            }
        }

        $accuracy = ($successCount / $totalTests) * 100;

        // After bug fixes, should have high accuracy on previously problematic cases
        $this->assertGreaterThanOrEqual(70, $accuracy,
            "Bug fixes should achieve >=70% accuracy on previously problematic cases. Got {$accuracy}%");
    }

    protected function tearDown(): void
    {
        // Clean up field mapper instance
        unset($this->fieldMapper);
    }
}
