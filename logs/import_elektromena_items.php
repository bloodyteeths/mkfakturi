<?php
/**
 * Import inventory items from Zaliha 1.csv for company 118 (Elektromena DOOEL)
 *
 * CSV is Windows-1251 encoded, semicolon-delimited.
 * Column mapping:
 *   col 1: row number
 *   col 2: SKU (Шифра)
 *   col 5: item name (Артикал)
 *   col 11: unit (Мера) — бр, мет, пар, ком, кг, etc.
 *   col 13: tax rate (Данок) — "18%", "5%", "0%"
 *   col 16: opening stock (Влез)
 *   col 25: current stock quantity (Залиха)
 *   col 29: stock value (Залиха вред.) — always 0 in this report
 *
 * Issues handled:
 *   - 8 duplicate SKUs → appended "-2" suffix to second occurrence
 *   - 15 items with negative stock → imported as-is
 *   - Unit "МЕТ" vs "мет" → case-insensitive matching
 *   - No price data in CSV → price=0, can be set later
 *
 * Usage: php logs/import_elektromena_items.php
 * Idempotent: skips items that already exist by SKU within company 118.
 */
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Item;
use App\Models\Unit;
use App\Models\Company;
use Illuminate\Support\Facades\DB;

$companyId = 118;
$company = Company::find($companyId);
if (!$company) {
    echo "ERROR: Company {$companyId} not found\n";
    exit(1);
}
echo "Importing items for: {$company->name} (ID: {$companyId})\n\n";

// Get company currency
$currencyId = DB::table('company_settings')
    ->where('company_id', $companyId)
    ->where('option', 'currency')
    ->value('value');
echo "Company currency_id: {$currencyId}\n";

// Unit mapping: Macedonian abbreviation (lowercase) → unit name
$unitMap = [
    'бр'  => 'Парче',    // piece
    'мет' => 'Метар',    // meter
    'пар' => 'Пар',      // pair
    'ком' => 'Комплет',  // set/kit
    'кг'  => 'Килограм', // kilogram
    'лит' => 'Литар',    // liter
    'м2'  => 'М2',       // square meter
];

// Get or create units for this company
$unitIds = [];
foreach ($unitMap as $abbr => $name) {
    $unit = Unit::where('name', $name)
        ->where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)->orWhereNull('company_id');
        })
        ->first();

    if (!$unit) {
        $unit = Unit::create([
            'name' => $name,
            'code' => $abbr,
            'company_id' => $companyId,
        ]);
        echo "  Created unit: {$name} ({$abbr}) → ID {$unit->id}\n";
    } else {
        echo "  Found unit: {$name} ({$abbr}) → ID {$unit->id}\n";
    }
    $unitIds[$abbr] = $unit->id;
}
echo "\n";

// Read and parse CSV
$csvPath = '/var/www/html/elktro/Zaliha 1.csv';
if (!file_exists($csvPath)) {
    $csvPath = __DIR__ . '/../elktro/Zaliha 1.csv';
}
if (!file_exists($csvPath)) {
    echo "ERROR: CSV file not found\n";
    exit(1);
}

$raw = file_get_contents($csvPath);
$content = mb_convert_encoding($raw, 'UTF-8', 'Windows-1251');
$lines = explode("\n", str_replace("\r\n", "\n", $content));

$created = 0;
$skipped = 0;
$errors = 0;
$seenSkus = []; // Track SKUs to handle duplicates within CSV

foreach ($lines as $lineNum => $line) {
    $cols = explode(';', $line);

    // Data rows: empty col[0], numeric col[1] (row number), numeric col[2] (SKU)
    if (!isset($cols[2]) || !is_numeric(trim($cols[1] ?? ''))) {
        continue;
    }

    $rowNum = (int) trim($cols[1]);
    $sku = trim($cols[2] ?? '');
    $name = trim($cols[5] ?? '');
    $unitAbbr = mb_strtolower(trim($cols[11] ?? '')); // case-insensitive
    $taxStr = trim($cols[13] ?? '');
    $stockStr = trim($cols[25] ?? '');

    // Skip summary rows at bottom (0%, 5%, 18%)
    if (empty($name) || $rowNum < 1) {
        continue;
    }

    // Clean up name — remove CSV quoting artifacts
    $name = trim($name, '"');
    $name = str_replace('""', '"', $name);
    // Remove trailing quotes from names like: dozna ЕС 195х152х70"
    $name = rtrim($name, '"');

    // Parse MK number format: "1.700,00" → 1700
    $stock = (int) round(floatval(str_replace(['.', ','], ['', '.'], $stockStr)));

    // Handle duplicate SKUs within the CSV (8 duplicates found)
    if (isset($seenSkus[$sku])) {
        $seenSkus[$sku]++;
        $originalSku = $sku;
        $sku = $sku . '-' . $seenSkus[$sku];
        echo "  WARN: Duplicate SKU {$originalSku} → renamed to {$sku} for \"{$name}\"\n";
    } else {
        $seenSkus[$sku] = 1;
    }

    // Unit ID — case-insensitive lookup
    $unitId = $unitIds[$unitAbbr] ?? ($unitIds['бр'] ?? null);

    // Check if item already exists by SKU in this company
    $existing = Item::where('company_id', $companyId)
        ->where('sku', $sku)
        ->first();

    if ($existing) {
        $skipped++;
        continue;
    }

    try {
        $item = Item::create([
            'name' => $name,
            'sku' => $sku,
            'company_id' => $companyId,
            'creator_id' => 141, // Ivana Nacev
            'unit_id' => $unitId,
            'currency_id' => $currencyId ?: null,
            'price' => 0, // No price data in this CSV
            'quantity' => $stock, // Includes negative stock (backordered)
            'track_quantity' => true,
            'tax_per_item' => false,
        ]);

        $created++;
        if ($created <= 15 || $created % 100 === 0) {
            $unitLabel = $unitAbbr ?: '?';
            echo "  [{$rowNum}] SKU={$sku} — {$name} | qty={$stock} unit={$unitLabel} → ID {$item->id}\n";
        }
    } catch (\Exception $e) {
        $errors++;
        echo "  ERROR [{$rowNum}] SKU={$sku} — {$name}: {$e->getMessage()}\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Created: {$created}\n";
echo "Skipped (existing): {$skipped}\n";
echo "Errors: {$errors}\n";
echo "Total items in company: " . Item::where('company_id', $companyId)->count() . "\n";

// CLAUDE-CHECKPOINT
