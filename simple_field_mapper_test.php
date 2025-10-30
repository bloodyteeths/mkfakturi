<?php

/**
 * Simple Enhanced Field Mapper Test
 * 
 * Tests the improved FieldMapperService accuracy for competitor formats
 */

require_once __DIR__ . '/app/Services/Migration/FieldMapperService.php';

use App\Services\Migration\FieldMapperService;

// Mock Laravel dependencies
if (!class_exists('Cache')) {
    class Cache {
        public static function remember($key, $minutes, $callback) {
            return $callback();
        }
    }
}

if (!class_exists('Log')) {
    class Log {
        public static function info($message, $context = []) {}
        public static function error($message, $context = []) {}
    }
}

if (!function_exists('now')) {
    function now() {
        return new DateTime();
    }
}

// Test data
$testCases = [
    // Core Macedonian fields (should be 100% accurate)
    'core_macedonian' => [
        'naziv' => 'customer_name',
        'embs' => 'tax_id', 
        'broj_faktura' => 'invoice_number',
        'kolicina' => 'quantity',
        'cena' => 'price',
        'iznos' => 'amount',
        'datum_faktura' => 'invoice_date',
        'pdv_stapka' => 'vat_rate',
    ],
    
    // Onivo formats
    'onivo' => [
        'customer_name' => 'customer_name',
        'customer_id' => 'customer_id',
        'customer_tax_id' => 'tax_id',
        'invoice_number' => 'invoice_number',
        'invoice_date' => 'invoice_date',
        'invoice_due_date' => 'due_date',
        'item_name' => 'item_name',
        'item_quantity' => 'quantity',
        'item_unit_price' => 'unit_price',
        'payment_date' => 'payment_date',
    ],
    
    // Megasoft formats (Serbian style)
    'megasoft' => [
        'naziv_kupca' => 'customer_name',
        'pib_kupca' => 'tax_id',
        'broj_računa' => 'invoice_number',
        'datum_računa' => 'invoice_date',
        'naziv_robe' => 'item_name',
        'količina_robe' => 'quantity',
        'cena_robe' => 'unit_price',
        'stopa_pdv' => 'vat_rate',
        'iznos_pdv' => 'vat_amount',
        'ukupan_iznos' => 'total',
    ],
    
    // Pantheon formats (prefix-based)
    'pantheon' => [
        'partner_naziv' => 'customer_name',
        'partner_pib' => 'tax_id',
        'dokument_broj' => 'invoice_number',
        'dokument_datum' => 'invoice_date',
        'stavka_naziv' => 'item_name',
        'stavka_količina' => 'quantity',
        'stavka_cena' => 'unit_price',
        'uplata_datum' => 'payment_date',
        'uplata_iznos' => 'payment_amount',
    ],
];

echo "=== Enhanced Field Mapper Accuracy Test ===\n\n";

$fieldMapper = new FieldMapperService();
$overallCorrect = 0;
$overallTotal = 0;

foreach ($testCases as $category => $fields) {
    echo "Testing {$category} format...\n";
    
    $correct = 0;
    $total = count($fields);
    $context = [];
    
    // Add software context for competitor formats
    if ($category === 'onivo') {
        $context = ['software' => 'onivo'];
    } elseif ($category === 'megasoft') {
        $context = ['software' => 'megasoft'];
    } elseif ($category === 'pantheon') {
        $context = ['software' => 'pantheon'];
    }
    
    foreach ($fields as $inputField => $expectedField) {
        $mappings = $fieldMapper->mapFields([$inputField], 'csv', $context);
        $mapping = $mappings[0];
        
        $confidence = round($mapping['confidence'], 2);
        $isCorrect = ($mapping['mapped_field'] === $expectedField && $mapping['confidence'] >= 0.7);
        
        if ($isCorrect) {
            $correct++;
            echo "  ✅ {$inputField} → {$mapping['mapped_field']} ({$confidence})\n";
        } else {
            echo "  ❌ {$inputField} → {$mapping['mapped_field']} ({$confidence}) [Expected: {$expectedField}]\n";
        }
    }
    
    $accuracy = round(($correct / $total) * 100, 1);
    echo "{$category} accuracy: {$accuracy}% ({$correct}/{$total})\n\n";
    
    $overallCorrect += $correct;
    $overallTotal += $total;
}

$overallAccuracy = round(($overallCorrect / $overallTotal) * 100, 1);
echo "=== SUMMARY ===\n";
echo "Overall Accuracy: {$overallAccuracy}% ({$overallCorrect}/{$overallTotal})\n";

if ($overallAccuracy >= 95) {
    echo "\n🎉 SUCCESS: Enhanced field mapper achieves >95% accuracy target!\n";
    echo "🏆 The improvements successfully enable accurate competitor format recognition.\n";
} elseif ($overallAccuracy >= 85) {
    echo "\n⚠️  GOOD: Enhanced field mapper shows significant improvement.\n";
    echo "📈 Further tuning may be needed for some competitor formats.\n";
} else {
    echo "\n❌ NEEDS WORK: Enhanced field mapper requires additional improvements.\n";
    echo "🔧 Review competitor patterns and fuzzy matching algorithms.\n";
}

echo "\nKEY IMPROVEMENTS IMPLEMENTED:\n";
echo "  ✅ Expanded Macedonian corpus with competitor-specific variations\n";
echo "  ✅ Added context-aware heuristic scoring for Onivo/Megasoft/Pantheon\n";
echo "  ✅ Enhanced fuzzy matching with adaptive thresholds\n";
echo "  ✅ Implemented competitor-specific pattern recognition\n";
echo "  ✅ Added phonetic and n-gram similarity algorithms\n";
echo "  ✅ Context-aware semantic scoring for better accuracy\n";