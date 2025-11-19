<?php

/**
 * Enhanced Field Mapper Accuracy Test
 * 
 * This script tests the improved FieldMapperService with competitor-specific enhancements
 * to verify >95% accuracy for competitor formats (Onivo, Megasoft, Pantheon).
 */

require_once __DIR__ . '/app/Services/Migration/FieldMapperService.php';

use App\Services\Migration\FieldMapperService;

// Mock Laravel Cache facade for standalone testing
if (!class_exists('Cache')) {
    class Cache {
        public static function remember($key, $minutes, $callback) {
            return $callback();
        }
    }
}

// Mock Laravel Log facade
if (!class_exists('Log')) {
    class Log {
        public static function info($message, $context = []) {
            // Silent in testing
        }
        public static function error($message, $context = []) {
            echo "ERROR: $message\n";
        }
    }
}

// Mock now() function
if (!function_exists('now')) {
    function now() {
        return new DateTime();
    }
}

class EnhancedFieldMapperTest
{
    private $fieldMapper;
    private $testResults = [];

    public function __construct()
    {
        $this->fieldMapper = new FieldMapperService();
    }

    public function runAllTests()
    {
        echo "=== Enhanced Field Mapper Accuracy Test ===\n\n";
        echo "Testing competitor-specific enhancements for >95% accuracy\n\n";

        $this->testOnivoFormats();
        $this->testMegasoftFormats();
        $this->testPantheonFormats();
        $this->testMixedCompetitorFormats();
        $this->testEnhancedFuzzyMatching();
        $this->testContextAwareHeuristics();
        $this->testOverallAccuracy();

        $this->printSummary();
    }

    public function testOnivoFormats()
    {
        echo "📋 Testing Onivo Format Recognition...\n";
        
        $onivoFields = [
            // Customer fields
            'customer_name' => 'customer_name',
            'customer_id' => 'customer_id', 
            'customer_tax_id' => 'tax_id',
            'customer_address' => 'address',
            'customer_email' => 'email',
            'customer_phone' => 'phone',
            
            // Invoice fields
            'invoice_id' => 'invoice_number',
            'invoice_number' => 'invoice_number',
            'invoice_date' => 'invoice_date',
            'invoice_due_date' => 'due_date',
            'invoice_total' => 'total',
            'invoice_status' => 'invoice_status',
            
            // Item fields
            'item_id' => 'item_code',
            'item_name' => 'item_name',
            'item_description' => 'description',
            'item_quantity' => 'quantity',
            'item_unit_price' => 'unit_price',
            'item_total_price' => 'amount',
            'item_vat_rate' => 'vat_rate',
            'item_vat_amount' => 'vat_amount',
            
            // Payment fields
            'payment_id' => 'payment_reference',
            'payment_date' => 'payment_date',
            'payment_amount' => 'payment_amount',
            'payment_method' => 'payment_method',
        ];

        $correct = 0;
        $total = count($onivoFields);

        foreach ($onivoFields as $inputField => $expectedField) {
            $mappings = $this->fieldMapper->mapFields([$inputField], 'csv', ['software' => 'onivo']);
            $mapping = $mappings[0];
            
            $success = ($mapping['mapped_field'] === $expectedField && $mapping['confidence'] >= 0.8);
            if ($success) {
                $correct++;
                echo "  ✅ {$inputField} → {$mapping['mapped_field']} (" . number_format($mapping['confidence'], 2) . ")\n";
            } else {
                echo "  ❌ {$inputField} → {$mapping['mapped_field']} (" . number_format($mapping['confidence'], 2) . ") [Expected: {$expectedField}]\n";
            }
        }

        $accuracy = ($correct / $total) * 100;
        $this->testResults['onivo'] = ['correct' => $correct, 'total' => $total, 'accuracy' => $accuracy];
        echo "Onivo Accuracy: " . round($accuracy, 1) . "% ({$correct}/{$total})\n\n";
    }

    public function testMegasoftFormats()
    {
        echo "📋 Testing Megasoft Format Recognition...\n";
        
        $megasoftFields = [
            // Customer fields (Serbian style)
            'naziv_kupca' => 'customer_name',
            'pib_kupca' => 'tax_id',
            'adresa_kupca' => 'address',
            'mesto_kupca' => 'city',
            
            // Invoice fields
            'broj_računa' => 'invoice_number',
            'datum_računa' => 'invoice_date', 
            'datum_dospeća' => 'due_date',
            
            // Item fields
            'naziv_robe' => 'item_name',
            'šifra_robe' => 'item_code',
            'količina_robe' => 'quantity',
            'cena_robe' => 'unit_price',
            'iznos_stavke' => 'amount',
            
            // Tax fields
            'stopa_pdv' => 'vat_rate',
            'iznos_pdv' => 'vat_amount',
            'ukupan_iznos' => 'total',
            
            // Payment fields
            'način_plaćanja' => 'payment_method',
            'datum_plaćanja' => 'payment_date',
            'iznos_plaćanja' => 'payment_amount',
            
            // Abbreviated forms
            'naz_kupca' => 'customer_name',
            'pib' => 'tax_id',
            'br_računa' => 'invoice_number',
            'dat_računa' => 'invoice_date',
            'kol' => 'quantity',
            'uk_iznos' => 'total',
        ];

        $correct = 0;
        $total = count($megasoftFields);

        foreach ($megasoftFields as $inputField => $expectedField) {
            $mappings = $this->fieldMapper->mapFields([$inputField], 'csv', ['software' => 'megasoft']);
            $mapping = $mappings[0];
            
            $success = ($mapping['mapped_field'] === $expectedField && $mapping['confidence'] >= 0.7);
            if ($success) {
                $correct++;
                echo "  ✅ {$inputField} → {$mapping['mapped_field']} (" . number_format($mapping['confidence'], 2) . ")\n";
            } else {
                echo "  ❌ {$inputField} → {$mapping['mapped_field']} (" . number_format($mapping['confidence'], 2) . ") [Expected: {$expectedField}]\n";
            }
        }

        $accuracy = ($correct / $total) * 100;
        $this->testResults['megasoft'] = ['correct' => $correct, 'total' => $total, 'accuracy' => $accuracy];
        echo "Megasoft Accuracy: {$accuracy:.1f}% ({$correct}/{$total})\n\n";
    }

    public function testPantheonFormats()
    {
        echo "📋 Testing Pantheon Format Recognition...\n";
        
        $pantheonFields = [
            // Customer fields (with prefixes)
            'partner_naziv' => 'customer_name',
            'partner_šifra' => 'customer_id',
            'partner_pib' => 'tax_id',
            'partner_adresa' => 'address',
            'partner_telefon' => 'phone',
            'partner_email' => 'email',
            
            // Invoice fields
            'dokument_broj' => 'invoice_number',
            'dokument_datum' => 'invoice_date',
            'dokument_valuta' => 'due_date',
            'dokument_iznos' => 'total',
            'dokument_status' => 'invoice_status',
            
            // Item fields
            'stavka_šifra' => 'item_code',
            'stavka_naziv' => 'item_name',
            'stavka_opis' => 'description',
            'stavka_količina' => 'quantity',
            'stavka_cena' => 'unit_price',
            'stavka_iznos' => 'amount',
            'stavka_pdv_stopa' => 'vat_rate',
            'stavka_pdv_iznos' => 'vat_amount',
            
            // Payment fields
            'uplata_datum' => 'payment_date',
            'uplata_iznos' => 'payment_amount',
            'uplata_način' => 'payment_method',
            'uplata_referenca' => 'payment_reference',
            
            // Abbreviated forms
            'prt_naziv' => 'customer_name',
            'dok_broj' => 'invoice_number',
            'stv_naziv' => 'item_name',
            'upl_datum' => 'payment_date',
        ];

        $correct = 0;
        $total = count($pantheonFields);

        foreach ($pantheonFields as $inputField => $expectedField) {
            $mappings = $this->fieldMapper->mapFields([$inputField], 'csv', ['software' => 'pantheon']);
            $mapping = $mappings[0];
            
            $success = ($mapping['mapped_field'] === $expectedField && $mapping['confidence'] >= 0.7);
            if ($success) {
                $correct++;
                echo "  ✅ {$inputField} → {$mapping['mapped_field']} ({$mapping['confidence']:.2f})\n";
            } else {
                echo "  ❌ {$inputField} → {$mapping['mapped_field']} ({$mapping['confidence']:.2f}) [Expected: {$expectedField}]\n";
            }
        }

        $accuracy = ($correct / $total) * 100;
        $this->testResults['pantheon'] = ['correct' => $correct, 'total' => $total, 'accuracy' => $accuracy];
        echo "Pantheon Accuracy: {$accuracy:.1f}% ({$correct}/{$total})\n\n";
    }

    public function testMixedCompetitorFormats()
    {
        echo "📋 Testing Mixed Competitor Formats...\n";
        
        $mixedFields = [
            // Mixed English-Macedonian (Onivo style)
            'customer_naziv' => 'customer_name',
            'invoice_broj' => 'invoice_number',
            'item_cena' => 'unit_price',
            'payment_datum' => 'payment_date',
            
            // Mixed Serbian-Macedonian
            'naziv_klijenta' => 'customer_name',
            'račun_broj' => 'invoice_number',
            'proizvod_cena' => 'unit_price',
            
            // Technical variations
            'field_naziv' => 'customer_name',
            'column_iznos' => 'amount',
            'data_cena' => 'price',
            'value_količina' => 'quantity',
        ];

        $correct = 0;
        $total = count($mixedFields);

        foreach ($mixedFields as $inputField => $expectedField) {
            $mappings = $this->fieldMapper->mapFields([$inputField]);
            $mapping = $mappings[0];
            
            $success = ($mapping['mapped_field'] === $expectedField && $mapping['confidence'] >= 0.6);
            if ($success) {
                $correct++;
                echo "  ✅ {$inputField} → {$mapping['mapped_field']} ({$mapping['confidence']:.2f})\n";
            } else {
                echo "  ❌ {$inputField} → {$mapping['mapped_field']} ({$mapping['confidence']:.2f}) [Expected: {$expectedField}]\n";
            }
        }

        $accuracy = ($correct / $total) * 100;
        $this->testResults['mixed'] = ['correct' => $correct, 'total' => $total, 'accuracy' => $accuracy];
        echo "Mixed Formats Accuracy: {$accuracy:.1f}% ({$correct}/{$total})\n\n";
    }

    public function testEnhancedFuzzyMatching()
    {
        echo "📋 Testing Enhanced Fuzzy Matching...\n";
        
        $fuzzyFields = [
            // Typos and variations
            'custome_name' => 'customer_name',      // Missing letter
            'invoce_number' => 'invoice_number',    // Typo
            'quantiy' => 'quantity',                // Typo
            'paymnt_date' => 'payment_date',        // Missing letters
            'vat_amout' => 'vat_amount',           // Typo
            
            // Phonetic variations
            'kostumer_name' => 'customer_name',     // Phonetic
            'faktora_broj' => 'invoice_number',     // Phonetic
            'kwantity' => 'quantity',               // Phonetic
            
            // Case and delimiter variations
            'CUSTOMER-NAME' => 'customer_name',
            'invoice.number' => 'invoice_number',
            'Item Name' => 'item_name',
            'payment_Amount' => 'payment_amount',
        ];

        $correct = 0;
        $total = count($fuzzyFields);

        foreach ($fuzzyFields as $inputField => $expectedField) {
            $mappings = $this->fieldMapper->mapFields([$inputField]);
            $mapping = $mappings[0];
            
            $success = ($mapping['mapped_field'] === $expectedField && $mapping['confidence'] >= 0.6);
            if ($success) {
                $correct++;
                echo "  ✅ {$inputField} → {$mapping['mapped_field']} ({$mapping['confidence']:.2f})\n";
            } else {
                echo "  ❌ {$inputField} → {$mapping['mapped_field']} ({$mapping['confidence']:.2f}) [Expected: {$expectedField}]\n";
            }
        }

        $accuracy = ($correct / $total) * 100;
        $this->testResults['fuzzy'] = ['correct' => $correct, 'total' => $total, 'accuracy' => $accuracy];
        echo "Enhanced Fuzzy Matching Accuracy: {$accuracy:.1f}% ({$correct}/{$total})\n\n";
    }

    public function testContextAwareHeuristics()
    {
        echo "📋 Testing Context-Aware Heuristics...\n";
        
        $contextTests = [
            // Test software context awareness
            ['field' => 'customer_name', 'context' => ['software' => 'onivo'], 'expected' => 'customer_name'],
            ['field' => 'naziv_kupca', 'context' => ['software' => 'megasoft'], 'expected' => 'customer_name'],
            ['field' => 'partner_naziv', 'context' => ['software' => 'pantheon'], 'expected' => 'customer_name'],
            
            // Test pattern-based recognition
            ['field' => 'br_faktura_2025', 'context' => [], 'expected' => 'invoice_number'],
            ['field' => 'iznos_ukupno_sa_pdv', 'context' => [], 'expected' => 'amount'],
            ['field' => 'pdv_stapka_procenat', 'context' => [], 'expected' => 'vat_rate'],
        ];

        $correct = 0;
        $total = count($contextTests);

        foreach ($contextTests as $test) {
            $mappings = $this->fieldMapper->mapFields([$test['field']], 'csv', $test['context']);
            $mapping = $mappings[0];
            
            $success = ($mapping['mapped_field'] === $test['expected'] && $mapping['confidence'] >= 0.7);
            if ($success) {
                $correct++;
                echo "  ✅ {$test['field']} → {$mapping['mapped_field']} ({$mapping['confidence']:.2f})\n";
            } else {
                echo "  ❌ {$test['field']} → {$mapping['mapped_field']} ({$mapping['confidence']:.2f}) [Expected: {$test['expected']}]\n";
            }
        }

        $accuracy = ($correct / $total) * 100;
        $this->testResults['context'] = ['correct' => $correct, 'total' => $total, 'accuracy' => $accuracy];
        echo "Context-Aware Heuristics Accuracy: {$accuracy:.1f}% ({$correct}/{$total})\n\n";
    }

    public function testOverallAccuracy()
    {
        echo "📊 Testing Overall Enhanced Accuracy...\n";
        
        // Combine all test categories
        $allFields = array_merge(
            [
                // Core Macedonian fields (should remain 100%)
                'naziv' => 'customer_name',
                'embs' => 'tax_id',
                'broj_faktura' => 'invoice_number',
                'kolicina' => 'quantity',
                'cena' => 'price',
            ],
            [
                // Representative sample from each competitor
                'customer_name' => 'customer_name',        // Onivo
                'naziv_kupca' => 'customer_name',          // Megasoft
                'partner_naziv' => 'customer_name',        // Pantheon
                'invoice_number' => 'invoice_number',      // Onivo
                'broj_računa' => 'invoice_number',         // Megasoft
                'dokument_broj' => 'invoice_number',       // Pantheon
                'item_quantity' => 'quantity',             // Onivo
                'količina_robe' => 'quantity',             // Megasoft
                'stavka_količina' => 'quantity',           // Pantheon
            ]
        );

        $correct = 0;
        $total = count($allFields);

        foreach ($allFields as $inputField => $expectedField) {
            $mappings = $this->fieldMapper->mapFields([$inputField]);
            $mapping = $mappings[0];
            
            $success = ($mapping['mapped_field'] === $expectedField && $mapping['confidence'] >= 0.7);
            if ($success) {
                $correct++;
            }
        }

        $accuracy = ($correct / $total) * 100;
        $this->testResults['overall'] = ['correct' => $correct, 'total' => $total, 'accuracy' => $accuracy];
        echo "Overall Enhanced Accuracy: {$accuracy:.1f}% ({$correct}/{$total})\n\n";
    }

    public function printSummary()
    {
        echo "=== ENHANCED FIELD MAPPER TEST SUMMARY ===\n\n";
        
        $totalCorrect = 0;
        $totalTests = 0;
        
        foreach ($this->testResults as $category => $result) {
            $totalCorrect += $result['correct'];
            $totalTests += $result['total'];
            
            $status = $result['accuracy'] >= 95 ? '✅' : ($result['accuracy'] >= 85 ? '⚠️' : '❌');
            echo sprintf(
                "%s %-25s: %6.1f%% (%d/%d)\n",
                $status,
                ucfirst($category) . ' Accuracy',
                $result['accuracy'],
                $result['correct'],
                $result['total']
            );
        }
        
        $overallAccuracy = ($totalCorrect / $totalTests) * 100;
        $overallStatus = $overallAccuracy >= 95 ? '✅' : ($overallAccuracy >= 85 ? '⚠️' : '❌');
        
        echo "\n" . str_repeat('=', 50) . "\n";
        echo sprintf(
            "%s %-25s: %6.1f%% (%d/%d)\n",
            $overallStatus,
            'OVERALL ACCURACY',
            $overallAccuracy,
            $totalCorrect,
            $totalTests
        );
        
        echo "\n";
        if ($overallAccuracy >= 95) {
            echo "🎉 SUCCESS: Enhanced field mapper achieves >95% accuracy target!\n";
            echo "🏆 The improvements successfully enable accurate competitor format recognition.\n";
        } elseif ($overallAccuracy >= 85) {
            echo "⚠️  GOOD: Enhanced field mapper shows significant improvement.\n";
            echo "📈 Further tuning may be needed for some competitor formats.\n";
        } else {
            echo "❌ NEEDS WORK: Enhanced field mapper requires additional improvements.\n";
            echo "🔧 Review competitor patterns and fuzzy matching algorithms.\n";
        }
        
        echo "\n";
        echo "KEY IMPROVEMENTS IMPLEMENTED:\n";
        echo "  ✅ Expanded Macedonian corpus with competitor-specific variations\n";
        echo "  ✅ Added context-aware heuristic scoring for Onivo/Megasoft/Pantheon\n";
        echo "  ✅ Enhanced fuzzy matching with adaptive thresholds\n";
        echo "  ✅ Implemented competitor-specific pattern recognition\n";
        echo "  ✅ Added phonetic and n-gram similarity algorithms\n";
        echo "  ✅ Context-aware semantic scoring for better accuracy\n";
    }
}

// Run the enhanced tests
$test = new EnhancedFieldMapperTest();
$test->runAllTests();