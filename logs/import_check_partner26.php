<?php
/**
 * Backfill counterparty_name on IFRS line items for Company 118 (elektromena)
 *
 * The import script stored partner names in narration as "description | partner | doc_num"
 * This extracts the partner name and sets counterparty_name for sub-ledger grouping.
 */
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Entity for company 118
$entity = DB::table('ifrs_entities')->where('name', 'LIKE', '%elektromena%')->first();
if (!$entity) {
    $entity = DB::table('ifrs_entities')->where('id', 109)->first();
}
if (!$entity) { die("Entity not found\n"); }
echo "Entity: id={$entity->id} name={$entity->name}\n";

// Get all line items for this entity
$lineItems = DB::table('ifrs_line_items')
    ->where('entity_id', $entity->id)
    ->whereNotNull('narration')
    ->get();

echo "Line items with narration: " . count($lineItems) . "\n";

$updated = 0;
$skipped = 0;

foreach ($lineItems as $li) {
    // Narration format: "description | partner | doc_num" or "partner | doc_num" or just "description"
    $parts = explode(' | ', $li->narration);

    $counterparty = null;

    if (count($parts) >= 2) {
        // If 3 parts: [description, partner, doc_num] — partner is index 1
        // If 2 parts: could be [partner, doc_num] or [description, partner]
        // Heuristic: if last part looks like a doc number (contains / or is short), partner is second-to-last
        if (count($parts) === 3) {
            $counterparty = trim($parts[1]);
        } elseif (count($parts) === 2) {
            // Check if first part looks like a company name (uppercase, contains ДООЕЛ/ДОО etc.)
            $first = trim($parts[0]);
            $second = trim($parts[1]);
            // If second part has / (doc number like 031/2026), first is counterparty
            if (preg_match('/\d+\/\d+/', $second)) {
                $counterparty = $first;
            } else {
                // First is description, second might be counterparty
                $counterparty = $second;
            }
        }
    } elseif (count($parts) === 1) {
        // Single value — check if it's a known partner name (uppercase Cyrillic with ДООЕЛ etc.)
        $val = trim($parts[0]);
        if (preg_match('/[А-Ша-ш]/', $val) && mb_strlen($val) > 3 && mb_strlen($val) < 100) {
            // Could be a partner name, but also could be a description
            // Skip single-part narrations — too ambiguous
        }
    }

    if ($counterparty && mb_strlen($counterparty) > 1 && $counterparty !== 'NULL') {
        // Skip generic descriptions that aren't partner names
        $skipPatterns = ['ДДВ', 'Провизија', 'Камата', 'Кирија', 'Основачки влог',
                        'Софтер и останати права', 'Компјутер', 'Материјални трошоци',
                        'Патни и дневни', 'компјутерска конфигурација'];
        $isGeneric = false;
        foreach ($skipPatterns as $pattern) {
            if (mb_stripos($counterparty, $pattern) !== false) {
                $isGeneric = true;
                break;
            }
        }

        if (!$isGeneric) {
            DB::table('ifrs_line_items')->where('id', $li->id)->update([
                'counterparty_name' => $counterparty,
            ]);
            $updated++;
            if ($updated <= 10) {
                echo "  SET: [{$li->id}] {$counterparty} (from: {$li->narration})\n";
            }
        } else {
            $skipped++;
        }
    } else {
        $skipped++;
    }
}

echo "\nDone. Updated: {$updated}, Skipped: {$skipped}\n";

// Verify sub-ledger for account 1200 (receivables)
$acct1200 = DB::table('ifrs_accounts')
    ->where('entity_id', $entity->id)
    ->where('code', 1200)
    ->first();

if ($acct1200) {
    echo "\nSub-ledger check for 1200 (Receivables):\n";
    $groups = DB::table('ifrs_ledgers as l')
        ->leftJoin('ifrs_line_items as li', 'l.line_item_id', '=', 'li.id')
        ->where('l.entity_id', $entity->id)
        ->where('l.post_account', $acct1200->id)
        ->select([
            'li.counterparty_name',
            DB::raw("SUM(CASE WHEN l.entry_type = 'D' THEN l.amount ELSE 0 END) as total_debit"),
            DB::raw("SUM(CASE WHEN l.entry_type = 'C' THEN l.amount ELSE 0 END) as total_credit"),
        ])
        ->groupBy('li.counterparty_name')
        ->get();

    foreach ($groups as $g) {
        $name = $g->counterparty_name ?? '(no counterparty)';
        echo "  {$name}: DR=" . number_format($g->total_debit) . " CR=" . number_format($g->total_credit) . "\n";
    }
}
