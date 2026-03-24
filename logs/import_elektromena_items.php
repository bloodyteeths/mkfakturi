<?php
/**
 * Import inventory items from Zaliha 1.csv for company 118 (Elektromena DOOEL)
 *
 * CSV is Windows-1251 encoded, semicolon-delimited.
 * Column mapping:
 *   col 1: row number
 *   col 2: SKU (Шифра)
 *   col 5: item name (Артикал)
 *   col 11: unit (Мера) — бр, мет, пар, ком, etc.
 *   col 13: tax rate (Данок) — "18%" or "5%"
 *   col 25: current stock quantity (Залиха)
 *   col 29: stock value (Залиха вред.)
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

// Get or create company currency
$currencyId = DB::table('company_settings')
    ->where('company_id', $companyId)
    ->where('option', 'currency')
    ->value('value');
echo "Company currency_id: {$currencyId}\n";

// Unit mapping: Macedonian abbreviation → unit name
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
    // Try local path
    $csvPath = __DIR__ . '/../elktro/Zaliha 1.csv';
}
if (!file_exists($csvPath)) {
    echo "ERROR: CSV file not found\n";
    exit(1);
}

$raw = file_get_contents($csvPath);
// Convert from Windows-1251 to UTF-8
$content = mb_convert_encoding($raw, 'UTF-8', 'Windows-1251');
$lines = explode("\n", str_replace("\r\n", "\n", $content));

$created = 0;
$skipped = 0;
$errors = 0;

foreach ($lines as $lineNum => $line) {
    $cols = explode(';', $line);

    // Data rows start with empty col[0], numeric col[1] (row number), numeric col[2] (SKU)
    if (!isset($cols[2]) || !is_numeric(trim($cols[1] ?? ''))) {
        continue;
    }

    $rowNum = (int) trim($cols[1]);
    $sku = trim($cols[2] ?? '');
    $name = trim($cols[5] ?? '');
    $unitAbbr = trim($cols[11] ?? '');
    $taxStr = trim($cols[13] ?? '');
    $stockStr = trim($cols[25] ?? '');
    $valueStr = trim($cols[29] ?? '');

    // Skip summary rows (0%, 5%, 18% at bottom)
    if (empty($name) || $rowNum < 1) {
        continue;
    }

    // Clean up name — remove extra quotes
    $name = trim($name, '"');
    $name = str_replace('""', '"', $name);

    // Parse quantity: "1.700,00" → 1700
    $stock = (int) round(floatval(str_replace(['.', ','], ['', '.'], $stockStr)));

    // Parse value: same format
    $value = floatval(str_replace(['.', ','], ['', '.'], $valueStr));

    // Calculate unit price in cents (value / stock, or 0)
    $priceInCents = 0;
    if ($stock > 0 && $value > 0) {
        $priceInCents = (int) round(($value / $stock) * 100);
    }

    // Unit ID
    $unitId = $unitIds[$unitAbbr] ?? ($unitIds['бр'] ?? null);

    // Check if item already exists by SKU
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
            'price' => $priceInCents,
            'cost' => $priceInCents, // same as selling price for now
            'quantity' => $stock,
            'track_quantity' => true,
            'tax_per_item' => false,
        ]);

        $created++;
        if ($created <= 10 || $created % 50 === 0) {
            echo "  [{$rowNum}] {$sku} — {$name} | qty={$stock} unit={$unitAbbr} price=" . ($priceInCents / 100) . " → ID {$item->id}\n";
        }
    } catch (\Exception $e) {
        $errors++;
        echo "  ERROR [{$rowNum}] {$sku} — {$name}: {$e->getMessage()}\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Created: {$created}\n";
echo "Skipped (existing): {$skipped}\n";
echo "Errors: {$errors}\n";
echo "Total items in company: " . Item::where('company_id', $companyId)->count() . "\n";

// CLAUDE-CHECKPOINT
