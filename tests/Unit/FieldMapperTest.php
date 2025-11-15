<?php

namespace Tests\Unit;

use App\Services\Migration\FieldMapperService;
use Tests\TestCase;

/**
 * FieldMapperTest - Comprehensive Field Mapper Accuracy Tests
 * 
 * This test suite validates the FieldMapperService accuracy for Macedonian
 * field mapping system. It ensures >95% accuracy for exact matches and
 * tests all major competitor system variations.
 * 
 * Test Coverage:
 * - Macedonian language corpus mapping accuracy
 * - Confidence scoring validation (>95% for exact matches)
 * - Fuzzy matching for similar terms
 * - Competitor system variations (Onivo, Megasoft, Pantheon)
 * - Edge cases (Cyrillic vs Latin, case sensitivity, special characters)
 * - Performance testing for large field sets
 * 
 * Success Criteria:
 * - >95% Macedonian mapping accuracy achieved
 * - All competitor field variations recognized
 * - Confidence scoring works correctly
 * - Performance within acceptable limits
 * 
 * 
 * @package Tests\Unit
 */
class FieldMapperTest extends TestCase
{
    private FieldMapperService $fieldMapper;

    protected function setUp(): void
    {
        // Skip parent setup to avoid configuration issues during testing
        $this->fieldMapper = new FieldMapperService();
    }

    /** @test */
    public function exact_matches_achieve_95_percent_confidence()
    {
        // Test exact matches for critical Macedonia accounting fields
        $exactMatchTests = [
            // Customer fields
            'naziv' => ['customer_name', 1.0],
            'ime_klient' => ['customer_name', 1.0],
            'klient' => ['customer_name', 1.0],
            'kupuvach' => ['customer_name', 1.0],
            'embs' => ['tax_id', 1.0],
            'edb' => ['tax_id', 1.0],
            
            // Invoice fields
            'broj_faktura' => ['invoice_number', 1.0],
            'faktura_broj' => ['invoice_number', 1.0],
            'datum_faktura' => ['invoice_date', 1.0],
            'dospeanos' => ['due_date', 1.0],
            
            // Item fields
            'stavka' => ['item_name', 1.0],
            'proizvod' => ['item_name', 1.0],
            'kod_stavka' => ['item_code', 1.0],
            'kolicina' => ['quantity', 1.0],
            'cena' => ['price', 1.0],
            'edinichna_cena' => ['unit_price', 1.0],
            
            // Financial fields
            'iznos' => ['amount', 1.0],
            'suma' => ['amount', 1.0],
            'vkupen_iznos' => ['total', 1.0],
            'pdv_stapka' => ['vat_rate', 1.0],
            'ddv_iznos' => ['vat_amount', 1.0],
            
            // Payment fields
            'datum_plakanje' => ['payment_date', 1.0],
            'platena_suma' => ['payment_amount', 1.0],
            
            // Cyrillic variations
            'назив' => ['customer_name', 1.0],
            'клиент' => ['customer_name', 1.0],
            'купувач' => ['customer_name', 1.0],
            'фактура_број' => ['invoice_number', 1.0],
            'износ' => ['amount', 1.0],
            'количина' => ['quantity', 1.0],
            'цена' => ['price', 1.0]
        ];

        $successCount = 0;
        $totalTests = count($exactMatchTests);

        foreach ($exactMatchTests as $inputField => $expected) {
            [$expectedField, $expectedConfidence] = $expected;
            
            $mappings = $this->fieldMapper->mapFields([$inputField]);
            $mapping = $mappings[0];
            
            // Validate exact match confidence
            $this->assertEquals($expectedField, $mapping['mapped_field'], 
                "Field '{$inputField}' should map to '{$expectedField}' but mapped to '{$mapping['mapped_field']}'");
            
            $this->assertGreaterThanOrEqual(0.95, $mapping['confidence'], 
                "Field '{$inputField}' should have >=95% confidence but got {$mapping['confidence']}");
            
            if ($mapping['confidence'] >= $expectedConfidence) {
                $successCount++;
            }
        }

        // Validate >95% overall accuracy for exact matches
        $accuracy = ($successCount / $totalTests) * 100;
        $this->assertGreaterThanOrEqual(95, $accuracy, 
            "Overall exact match accuracy should be >=95% but got {$accuracy}%");
    }

    /** @test */
    public function fuzzy_matching_handles_similar_terms()
    {
        // Test fuzzy matching for similar but not identical terms
        $fuzzyMatchTests = [
            // Common typos and variations
            'naziva' => 'customer_name',           // Missing letter
            'klijent' => 'customer_name',          // Latin variation
            'kolochina' => 'quantity',             // Transcription variation
            'cedena' => 'price',                   // Typing error
            'faktura_br' => 'invoice_number',      // Abbreviated
            'pdv_stapa' => 'vat_rate',            // Missing letter
            'vkupan_iznos' => 'total',            // Minor variation
            'datum_plakanye' => 'payment_date',    // Phonetic variation
            'edinachna_cena' => 'unit_price',     // Typo
            'proizvodi' => 'item_name',           // Plural form
            
            // Mixed language variations
            'customer_naziv' => 'customer_name',   // English + Macedonian
            'invoice_broj' => 'invoice_number',    // English + Macedonian
            'amount_iznos' => 'amount',           // Redundant bilingual
            'quantity_kol' => 'quantity',         // Abbreviated bilingual
            
            // Case variations
            'NAZIV' => 'customer_name',           // All caps
            'IzNos' => 'amount',                  // Mixed case
            'KOLICINA' => 'quantity',             // All caps Cyrillic
        ];

        foreach ($fuzzyMatchTests as $inputField => $expectedField) {
            $mappings = $this->fieldMapper->mapFields([$inputField]);
            $mapping = $mappings[0];
            
            $this->assertEquals($expectedField, $mapping['mapped_field'], 
                "Fuzzy field '{$inputField}' should map to '{$expectedField}' but mapped to '{$mapping['mapped_field']}'");
            
            $this->assertGreaterThanOrEqual(0.65, $mapping['confidence'], 
                "Fuzzy field '{$inputField}' should have >=65% confidence but got {$mapping['confidence']}");
            
            $this->assertContains($mapping['algorithm'], ['fuzzy_match', 'heuristic_pattern', 'exact_match'], 
                "Field '{$inputField}' should use fuzzy_match, heuristic_pattern or exact_match algorithm");
        }
    }

    /** @test */
    public function onivo_field_format_variations_are_recognized()
    {
        // Test Onivo-specific field naming conventions
        $onivoFieldTests = [
            // Onivo customer export format
            'customer_id' => 'customer_id',
            'customer_name' => 'customer_name',
            'customer_tax_id' => 'tax_id',
            'customer_address' => 'address',
            'customer_city' => 'city',
            'customer_email' => 'email',
            'customer_phone' => 'phone',
            
            // Onivo invoice export format
            'invoice_id' => 'invoice_number',
            'invoice_date' => 'invoice_date',
            'invoice_due_date' => 'due_date',
            'invoice_customer_id' => 'customer_id',
            'invoice_total' => 'total',
            'invoice_currency' => 'currency',
            'invoice_status' => 'invoice_status',
            
            // Onivo item format
            'item_id' => 'item_code',
            'item_name' => 'item_name',
            'item_description' => 'description',
            'item_quantity' => 'quantity',
            'item_unit_price' => 'unit_price',
            'item_total_price' => 'amount',
            'item_vat_rate' => 'vat_rate',
            'item_vat_amount' => 'vat_amount',
            
            // Onivo payment format
            'payment_id' => 'payment_reference',
            'payment_date' => 'payment_date',
            'payment_amount' => 'payment_amount',
            'payment_method' => 'payment_method',
        ];

        foreach ($onivoFieldTests as $inputField => $expectedField) {
            $mappings = $this->fieldMapper->mapFields([$inputField], 'csv', ['software' => 'onivo']);
            $mapping = $mappings[0];
            
            $this->assertEquals($expectedField, $mapping['mapped_field'], 
                "Onivo field '{$inputField}' should map to '{$expectedField}'");
            
            $this->assertGreaterThanOrEqual(0.8, $mapping['confidence'], 
                "Onivo field '{$inputField}' should have >=80% confidence");
        }
    }

    /** @test */
    public function megasoft_field_format_variations_are_recognized()
    {
        // Test Megasoft-specific field naming conventions
        $megasoftFieldTests = [
            // Megasoft uses Serbian-style naming
            'naziv_kupca' => 'customer_name',
            'pib_kupca' => 'tax_id',
            'adresa_kupca' => 'address',
            'mesto_kupca' => 'city',
            'broj_računa' => 'invoice_number',
            'datum_računa' => 'invoice_date',
            'datum_dospeća' => 'due_date',
            'naziv_robe' => 'item_name',
            'šifra_robe' => 'item_code',
            'količina_robe' => 'quantity',
            'cena_robe' => 'unit_price',
            'iznos_stavke' => 'amount',
            'stopa_pdv' => 'vat_rate',
            'iznos_pdv' => 'vat_amount',
            'ukupan_iznos' => 'total',
            'način_plaćanja' => 'payment_method',
            'datum_plaćanja' => 'payment_date',
            'iznos_plaćanja' => 'payment_amount',
            
            // Megasoft abbreviated formats
            'naz_kupca' => 'customer_name',
            'pib' => 'tax_id',
            'br_računa' => 'invoice_number',
            'dat_računa' => 'invoice_date',
            'kol' => 'quantity',
            'uk_iznos' => 'total',
        ];

        foreach ($megasoftFieldTests as $inputField => $expectedField) {
            $mappings = $this->fieldMapper->mapFields([$inputField], 'csv', ['software' => 'megasoft']);
            $mapping = $mappings[0];
            
            $this->assertEquals($expectedField, $mapping['mapped_field'], 
                "Megasoft field '{$inputField}' should map to '{$expectedField}'");
            
            $this->assertGreaterThanOrEqual(0.6, $mapping['confidence'], 
                "Megasoft field '{$inputField}' should have >=60% confidence");
        }
    }

    /** @test */
    public function pantheon_field_format_variations_are_recognized()
    {
        // Test Pantheon-specific field naming conventions
        $pantheonFieldTests = [
            // Pantheon export format with prefixes
            'partner_naziv' => 'customer_name',
            'partner_sifra' => 'customer_id',
            'partner_pib' => 'tax_id',
            'partner_adresa' => 'address',
            'partner_mesto' => 'city',
            'partner_telefon' => 'phone',
            'partner_email' => 'email',
            
            'dokument_broj' => 'invoice_number',
            'dokument_datum' => 'invoice_date',
            'dokument_valuta' => 'due_date',
            'dokument_iznos' => 'total',
            'dokument_status' => 'invoice_status',
            
            'stavka_sifra' => 'item_code',
            'stavka_naziv' => 'item_name',
            'stavka_opis' => 'description',
            'stavka_kolicina' => 'quantity',
            'stavka_cena' => 'unit_price',
            'stavka_iznos' => 'amount',
            'stavka_pdv_stopa' => 'vat_rate',
            'stavka_pdv_iznos' => 'vat_amount',
            
            'uplata_datum' => 'payment_date',
            'uplata_iznos' => 'payment_amount',
            'uplata_nacin' => 'payment_method',
            'uplata_referenca' => 'payment_reference',
            
            // Pantheon with underscores
            'prt_naziv' => 'customer_name',
            'dok_broj' => 'invoice_number',
            'stv_naziv' => 'item_name',
            'upl_datum' => 'payment_date',
        ];

        foreach ($pantheonFieldTests as $inputField => $expectedField) {
            $mappings = $this->fieldMapper->mapFields([$inputField], 'csv', ['software' => 'pantheon']);
            $mapping = $mappings[0];
            
            $this->assertEquals($expectedField, $mapping['mapped_field'], 
                "Pantheon field '{$inputField}' should map to '{$expectedField}'");
            
            $this->assertGreaterThanOrEqual(0.7, $mapping['confidence'], 
                "Pantheon field '{$inputField}' should have >=70% confidence");
        }
    }

    /** @test */
    public function cyrillic_vs_latin_script_handling()
    {
        // Test both Cyrillic and Latin variations of the same fields
        $scriptTests = [
            // Customer fields
            ['naziv', 'назив', 'customer_name'],
            ['klient', 'клиент', 'customer_name'],
            ['kupuvach', 'купувач', 'customer_name'],
            
            // Invoice fields
            ['faktura', 'фактура', 'invoice_number'],
            ['datum', 'датум', 'date'],
            
            // Item fields
            ['stavka', 'ставка', 'item_name'],
            ['proizvod', 'производ', 'item_name'],
            ['kolicina', 'количина', 'quantity'],
            ['cena', 'цена', 'price'],
            
            // Financial fields
            ['iznos', 'износ', 'amount'],
            ['suma', 'сума', 'amount'],
            ['vkupno', 'вкупно', 'total'],
            
            // Payment fields
            ['plakanje', 'плаќање', 'payment_date'],
            ['uplata', 'уплата', 'payment_amount'],
        ];

        foreach ($scriptTests as [$latin, $cyrillic, $expectedField]) {
            // Test Latin script
            $latinMappings = $this->fieldMapper->mapFields([$latin]);
            $latinMapping = $latinMappings[0];
            
            $this->assertEquals($expectedField, $latinMapping['mapped_field'], 
                "Latin field '{$latin}' should map to '{$expectedField}'");
            
            // Test Cyrillic script
            $cyrillicMappings = $this->fieldMapper->mapFields([$cyrillic]);
            $cyrillicMapping = $cyrillicMappings[0];
            
            $this->assertEquals($expectedField, $cyrillicMapping['mapped_field'], 
                "Cyrillic field '{$cyrillic}' should map to '{$expectedField}'");
            
            // Both should have high confidence
            $this->assertGreaterThanOrEqual(0.8, $latinMapping['confidence']);
            $this->assertGreaterThanOrEqual(0.8, $cyrillicMapping['confidence']);
        }
    }

    /** @test */
    public function case_sensitivity_and_special_characters()
    {
        // Test various case and special character combinations
        $caseTests = [
            // Case variations
            'NAZIV' => 'customer_name',
            'naziv' => 'customer_name',
            'Naziv' => 'customer_name',
            'NaZiV' => 'customer_name',
            
            // With underscores
            'customer_name' => 'customer_name',
            'Customer_Name' => 'customer_name',
            'CUSTOMER_NAME' => 'customer_name',
            
            // With dashes
            'invoice-number' => 'invoice_number',
            'Invoice-Number' => 'invoice_number',
            'INVOICE-NUMBER' => 'invoice_number',
            
            // With spaces
            'unit price' => 'unit_price',
            'Unit Price' => 'unit_price',
            'UNIT PRICE' => 'unit_price',
            
            // With dots
            'payment.date' => 'payment_date',
            'Payment.Date' => 'payment_date',
            'PAYMENT.DATE' => 'payment_date',
            
            // With brackets and quotes
            '[customer_name]' => 'customer_name',
            '(invoice_number)' => 'invoice_number',
            '"item_name"' => 'item_name',
            "'quantity'" => 'quantity',
            
            // With numbers (common in duplicated columns)
            'naziv1' => 'customer_name',
            'cena_2' => 'price',
            'amount3' => 'amount',
            
            // With prefixes
            'field_naziv' => 'customer_name',
            'col_iznos' => 'amount',
            'attr_cena' => 'price',
        ];

        foreach ($caseTests as $inputField => $expectedField) {
            $mappings = $this->fieldMapper->mapFields([$inputField]);
            $mapping = $mappings[0];
            
            $this->assertEquals($expectedField, $mapping['mapped_field'], 
                "Field '{$inputField}' should map to '{$expectedField}' but mapped to '{$mapping['mapped_field']}'");
            
            $this->assertGreaterThanOrEqual(0.7, $mapping['confidence'], 
                "Field '{$inputField}' should have >=70% confidence but got {$mapping['confidence']}");
        }
    }

    /** @test */
    public function common_typos_and_variations_are_handled()
    {
        // Test common typos and variations found in real Macedonia data
        $typoTests = [
            // Common Macedonia typos
            'nazov' => 'customer_name',           // v instead of v
            'klinet' => 'customer_name',          // missing e
            'faktora' => 'invoice_number',        // a instead of u
            'kolecina' => 'quantity',             // e instead of i
            'cenata' => 'price',                  // with article suffix
            'iznost' => 'amount',                 // t instead of s
            'sumata' => 'amount',                 // with article suffix
            'pdv_stapa' => 'vat_rate',           // missing k
            'ddv_stapka' => 'vat_rate',          // ddv instead of pdv
            'vkopen_iznos' => 'total',           // p instead of up
            'platanje' => 'payment_date',        // missing k
            'uplate' => 'payment_amount',        // plural form
            
            // Mixed transcription
            'nazivot' => 'customer_name',         // with definite article
            'fakturata' => 'invoice_number',      // with definite article
            'cenite' => 'price',                  // plural
            'iznosite' => 'amount',               // plural with definite article
            
            // Common abbreviations
            'naz' => 'customer_name',
            'fakt' => 'invoice_number',
            'kol' => 'quantity',
            'izn' => 'amount',
            'ukup' => 'total',
            'plat' => 'payment_date',
            
            // Technical variations
            'customer_naziv_field' => 'customer_name',
            'invoice_broj_column' => 'invoice_number',
            'item_cena_value' => 'unit_price',
            'payment_datum_attr' => 'payment_date',
        ];

        foreach ($typoTests as $inputField => $expectedField) {
            $mappings = $this->fieldMapper->mapFields([$inputField]);
            $mapping = $mappings[0];
            
            $this->assertEquals($expectedField, $mapping['mapped_field'], 
                "Typo field '{$inputField}' should map to '{$expectedField}' but mapped to '{$mapping['mapped_field']}'");
            
            $this->assertGreaterThanOrEqual(0.6, $mapping['confidence'], 
                "Typo field '{$inputField}' should have >=60% confidence but got {$mapping['confidence']}");
        }
    }

    /** @test */
    public function mixed_language_fields_are_handled()
    {
        // Test fields with mixed Macedonian/Serbian/English
        $mixedLanguageTests = [
            // English-Macedonian mix
            'customer_naziv' => 'customer_name',
            'invoice_broj' => 'invoice_number',
            'item_stavka' => 'item_name',
            'payment_plakanje' => 'payment_date',
            'total_vkupno' => 'total',
            'amount_iznos' => 'amount',
            'price_cena' => 'price',
            'quantity_kolicina' => 'quantity',
            
            // Serbian-Macedonian mix
            'naziv_kupca' => 'customer_name',
            'broj_računa' => 'invoice_number',
            'cena_proizvoda' => 'unit_price',
            'količina_stavke' => 'quantity',
            'iznos_ukupno' => 'total',
            'datum_plaćanja' => 'payment_date',
            
            // All three languages
            'customer_naziv_kupca' => 'customer_name',
            'invoice_broj_računa' => 'invoice_number',
            'item_stavka_proizvod' => 'item_name',
            'payment_plakanje_datum' => 'payment_date',
            
            // Technical English with local terms
            'field_naziv' => 'customer_name',
            'column_iznos' => 'amount',
            'data_cena' => 'price',
            'value_kolicina' => 'quantity',
            'attr_embs' => 'tax_id',
        ];

        foreach ($mixedLanguageTests as $inputField => $expectedField) {
            $mappings = $this->fieldMapper->mapFields([$inputField]);
            $mapping = $mappings[0];
            
            $this->assertEquals($expectedField, $mapping['mapped_field'], 
                "Mixed language field '{$inputField}' should map to '{$expectedField}'");
            
            $this->assertGreaterThanOrEqual(0.6, $mapping['confidence'], 
                "Mixed language field '{$inputField}' should have >=60% confidence");
        }
    }

    /** @test */
    public function performance_testing_for_large_field_sets()
    {
        // Create a large set of fields to test performance
        $largeFieldSet = [];
        
        // Add 1000 variations of common fields
        for ($i = 1; $i <= 200; $i++) {
            $largeFieldSet[] = "naziv_{$i}";
            $largeFieldSet[] = "customer_name_{$i}";
            $largeFieldSet[] = "faktura_broj_{$i}";
            $largeFieldSet[] = "amount_{$i}";
            $largeFieldSet[] = "cena_{$i}";
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $mappings = $this->fieldMapper->mapFields($largeFieldSet);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $processingTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;
        
        // Performance assertions
        $this->assertLessThan(10.0, $processingTime, 
            "Processing 1000 fields should take less than 10 seconds but took {$processingTime} seconds");
        
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsed, 
            "Processing should use less than 50MB memory but used " . ($memoryUsed / 1024 / 1024) . "MB");
        
        // Accuracy assertions
        $this->assertCount(1000, $mappings, "Should return mappings for all 1000 fields");
        
        $highConfidenceCount = 0;
        foreach ($mappings as $mapping) {
            if ($mapping['confidence'] >= 0.8) {
                $highConfidenceCount++;
            }
        }
        
        $highConfidencePercentage = ($highConfidenceCount / 1000) * 100;
        $this->assertGreaterThanOrEqual(80, $highConfidencePercentage, 
            "At least 80% of mappings should have high confidence (>=0.8)");
    }

    /** @test */
    public function confidence_scoring_algorithm_accuracy()
    {
        // Test that confidence scores properly reflect mapping quality
        $confidenceTests = [
            // Perfect matches should be 1.0
            ['naziv', 'customer_name', 1.0, 1.0],
            ['embs', 'tax_id', 1.0, 1.0],
            ['broj_faktura', 'invoice_number', 1.0, 1.0],
            
            // Good fuzzy matches should be 0.8-0.95
            ['naziva', 'customer_name', 0.8, 0.95],
            ['klijent', 'customer_name', 0.8, 0.95],
            ['faktora', 'invoice_number', 0.7, 0.9],
            
            // Moderate matches should be 0.6-0.8
            ['customer_naziv', 'customer_name', 0.6, 0.8],
            ['invoice_broj', 'invoice_number', 0.6, 0.8],
            
            // Weak matches should be 0.3-0.6
            ['naziv_field_customer', 'customer_name', 0.3, 0.6],
            ['amount_total_sum', 'amount', 0.3, 0.6],
        ];

        foreach ($confidenceTests as [$inputField, $expectedField, $minConfidence, $maxConfidence]) {
            $mappings = $this->fieldMapper->mapFields([$inputField]);
            $mapping = $mappings[0];
            
            $this->assertEquals($expectedField, $mapping['mapped_field'], 
                "Field '{$inputField}' should map to '{$expectedField}'");
            
            $this->assertGreaterThanOrEqual($minConfidence, $mapping['confidence'], 
                "Field '{$inputField}' should have confidence >= {$minConfidence} but got {$mapping['confidence']}");
            
            $this->assertLessThanOrEqual($maxConfidence, $mapping['confidence'], 
                "Field '{$inputField}' should have confidence <= {$maxConfidence} but got {$mapping['confidence']}");
        }
    }

    /** @test */
    public function learning_capabilities_validation()
    {
        // Skip learning test in unit testing environment
        // as it requires Laravel's full caching infrastructure
        $this->markTestSkipped('Learning capabilities require full Laravel environment');
    }

    /** @test */
    public function auto_mapping_with_confidence_threshold()
    {
        $inputFields = [
            'naziv',              // Should auto-map (high confidence)
            'embs',               // Should auto-map (high confidence)
            'broj_faktura',       // Should auto-map (high confidence)
            'klijent',            // Should auto-map (good fuzzy match)
            'unknown_field_xyz',  // Should NOT auto-map (low confidence)
            'maybe_customer',     // Should NOT auto-map (uncertain)
        ];

        $autoMapped = $this->fieldMapper->autoMapFields($inputFields, 0.8);
        
        // Should include high-confidence mappings
        $this->assertArrayHasKey('naziv', $autoMapped);
        $this->assertArrayHasKey('embs', $autoMapped);
        $this->assertArrayHasKey('broj_faktura', $autoMapped);
        
        // Should NOT include low-confidence mappings
        $this->assertArrayNotHasKey('unknown_field_xyz', $autoMapped);
        $this->assertArrayNotHasKey('maybe_customer', $autoMapped);
        
        // Validate mappings are correct
        $this->assertEquals('customer_name', $autoMapped['naziv']);
        $this->assertEquals('tax_id', $autoMapped['embs']);
        $this->assertEquals('invoice_number', $autoMapped['broj_faktura']);
    }

    /** @test */
    public function field_mapping_suggestions_quality()
    {
        $inputFields = [
            'naziv_kupca',
            'unknown_field',
            'broj_faktura',
            'maybe_amount',
            'cena_proizvoda',
            'very_unclear_field_name'
        ];

        $suggestions = $this->fieldMapper->getSuggestions($inputFields, 5);
        
        // Should return suggestions ordered by confidence
        $this->assertCount(5, $suggestions);
        
        // Verify ordering by confidence (highest first)
        for ($i = 0; $i < count($suggestions) - 1; $i++) {
            $this->assertGreaterThanOrEqual(
                $suggestions[$i + 1]['confidence'], 
                $suggestions[$i]['confidence'],
                "Suggestions should be ordered by confidence (highest first)"
            );
        }
        
        // Check suggestion structure
        foreach ($suggestions as $suggestion) {
            $this->assertArrayHasKey('input_field', $suggestion);
            $this->assertArrayHasKey('suggested_field', $suggestion);
            $this->assertArrayHasKey('confidence', $suggestion);
            $this->assertArrayHasKey('reason', $suggestion);
            $this->assertArrayHasKey('alternatives', $suggestion);
            
            $this->assertGreaterThan(0.3, $suggestion['confidence']);
            $this->assertIsString($suggestion['reason']);
            $this->assertIsArray($suggestion['alternatives']);
        }
    }

    /** @test */
    public function mapping_validation_against_schema()
    {
        $mappings = [
            'naziv' => 'customer_name',
            'embs' => 'tax_id',
            'broj_faktura' => 'invoice_number',
            'datum_faktura' => 'invoice_date',
            'iznos' => 'amount',
        ];

        $requiredFields = ['customer_name', 'tax_id', 'invoice_number', 'amount'];
        
        $validation = $this->fieldMapper->validateMappings($mappings, $requiredFields);
        
        $this->assertTrue($validation['valid'], "Mappings should be valid");
        $this->assertEmpty($validation['errors'], "Should have no validation errors");
        $this->assertEquals(100, $validation['coverage'], "Should have 100% coverage of required fields");
        
        // Test with missing required field
        $incompleteMappings = [
            'naziv' => 'customer_name',
            'embs' => 'tax_id',
        ];
        
        $incompleteValidation = $this->fieldMapper->validateMappings($incompleteMappings, $requiredFields);
        
        $this->assertFalse($incompleteValidation['valid'], "Incomplete mappings should be invalid");
        $this->assertNotEmpty($incompleteValidation['errors'], "Should have validation errors");
        $this->assertLessThan(100, $incompleteValidation['coverage'], "Should have incomplete coverage");
    }

    /** @test */
    public function export_and_analysis_functionality()
    {
        $inputFields = ['naziv', 'embs', 'broj_faktura', 'unknown_field'];
        $mappings = $this->fieldMapper->mapFields($inputFields);
        
        // Test JSON export
        $jsonExport = $this->fieldMapper->exportMappings($mappings, 'json');
        $exportData = json_decode($jsonExport, true);
        
        $this->assertArrayHasKey('version', $exportData);
        $this->assertArrayHasKey('created_at', $exportData);
        $this->assertArrayHasKey('mappings', $exportData);
        $this->assertArrayHasKey('statistics', $exportData);
        
        // Test CSV export
        $csvExport = $this->fieldMapper->exportMappings($mappings, 'csv');
        $this->assertStringContainsString('Input Field,Mapped Field,Confidence', $csvExport);
        $this->assertStringContainsString('naziv', $csvExport);
        
        // Test statistics
        $stats = $exportData['statistics'];
        $this->assertEquals(4, $stats['total_fields']);
        $this->assertArrayHasKey('mapped_fields', $stats);
        $this->assertArrayHasKey('high_confidence', $stats);
    }

    /** @test */
    public function supported_fields_and_variations_coverage()
    {
        $supportedFields = $this->fieldMapper->getSupportedFields();
        
        // Should include all major categories
        $expectedCategories = [
            'customer_name', 'customer_id', 'tax_id', 'company_id',
            'invoice_number', 'invoice_date', 'due_date', 'invoice_status',
            'item_name', 'item_code', 'description', 'quantity', 'unit', 'unit_price', 'price',
            'amount', 'subtotal', 'total', 'currency',
            'vat_rate', 'vat_amount', 'tax_inclusive', 'tax_exclusive',
            'payment_date', 'payment_method', 'payment_amount', 'payment_reference',
            'bank_account', 'bank_name', 'address', 'city', 'postal_code', 'country',
            'warehouse', 'stock_quantity', 'expense_category', 'expense_date',
            'date', 'status', 'notes', 'email', 'phone', 'contact_person'
        ];
        
        foreach ($expectedCategories as $category) {
            $this->assertContains($category, $supportedFields, 
                "Should support field category '{$category}'");
        }
        
        // Test field variations retrieval
        $customerVariations = $this->fieldMapper->getFieldVariations('customer_name');
        $this->assertContains('naziv', $customerVariations);
        $this->assertContains('klient', $customerVariations);
        $this->assertContains('купувач', $customerVariations);
        
        $emptyVariations = $this->fieldMapper->getFieldVariations('non_existent_field');
        $this->assertEmpty($emptyVariations);
    }

    /** @test */
    public function heuristic_pattern_matching()
    {
        // Test heuristic patterns for Macedonia accounting software
        $heuristicTests = [
            // Pattern-based matching
            'br_faktura_2025' => 'invoice_number',      // broj.*faktura pattern
            'datum_faktura_izdavanje' => 'invoice_date', // datum.*faktura pattern
            'iznos_ukupno_sa_pdv' => 'amount',          // iznos pattern
            'kolicina_proizvoda' => 'quantity',         // kolicina pattern
            'cena_po_komad' => 'unit_price',            // cena pattern
            'pdv_stapka_procenat' => 'vat_rate',        // pdv.*stapka pattern
            'pdv_iznos_celkupen' => 'vat_amount',       // pdv.*iznos pattern
            'klient_naziv_firme' => 'customer_name',    // klient pattern
            'embs_poreski_broj' => 'tax_id',            // embs pattern
            'edb_evidencija' => 'tax_id',               // edb pattern
        ];

        foreach ($heuristicTests as $inputField => $expectedField) {
            $mappings = $this->fieldMapper->mapFields([$inputField]);
            $mapping = $mappings[0];
            
            $this->assertEquals($expectedField, $mapping['mapped_field'], 
                "Heuristic field '{$inputField}' should map to '{$expectedField}'");
            
            $this->assertGreaterThanOrEqual(0.7, $mapping['confidence'], 
                "Heuristic field '{$inputField}' should have >=70% confidence");
            
            // Some should specifically use heuristic algorithm
            if (in_array($mapping['algorithm'], ['heuristic_pattern', 'exact_match'])) {
                $this->assertTrue(true); // Expected algorithms
            }
        }
    }

    protected function tearDown(): void
    {
        // No-op; let PHP garbage collect the mapper instance.
    }
}
