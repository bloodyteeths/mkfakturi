<?php
/**
 * Cleanup bad counterparty_name values for Company 118
 * Fix: "салдо" and numeric values that were incorrectly parsed
 */
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$entity = DB::table('ifrs_entities')->where('id', 109)->first();
echo "Entity: {$entity->id} {$entity->name}\n";

// Fix bad counterparty names: "салдо", pure numbers, short strings
$badItems = DB::table('ifrs_line_items')
    ->where('entity_id', 109)
    ->whereNotNull('counterparty_name')
    ->get();

$cleaned = 0;
$reparsed = 0;

foreach ($badItems as $li) {
    $cp = trim($li->counterparty_name);

    // Check if it's a bad value
    $isBad = false;
    if (mb_strtolower($cp) === 'салдо') $isBad = true;
    if (is_numeric($cp)) $isBad = true;
    if (mb_strlen($cp) <= 2) $isBad = true;

    if (!$isBad) continue;

    // Try to re-parse from narration
    // Opening balance format: "салдо | partner_name" — partner is the LAST part
    $parts = explode(' | ', $li->narration ?? '');
    $newCounterparty = null;

    if (count($parts) >= 2) {
        // For opening balances: "салдо | КОМПАНИЈА"
        // Take the last part that isn't "салдо" and isn't a number
        for ($i = count($parts) - 1; $i >= 0; $i--) {
            $candidate = trim($parts[$i]);
            if (mb_strtolower($candidate) !== 'салдо' && !is_numeric($candidate) && mb_strlen($candidate) > 2) {
                $newCounterparty = $candidate;
                break;
            }
        }
    }

    if ($newCounterparty) {
        DB::table('ifrs_line_items')->where('id', $li->id)->update([
            'counterparty_name' => $newCounterparty,
        ]);
        $reparsed++;
        if ($reparsed <= 15) {
            echo "  FIXED: [{$li->id}] '{$cp}' -> '{$newCounterparty}' (from: {$li->narration})\n";
        }
    } else {
        // Clear it — better to have NULL than wrong data
        DB::table('ifrs_line_items')->where('id', $li->id)->update([
            'counterparty_name' => null,
        ]);
        $cleaned++;
        if ($cleaned <= 5) {
            echo "  CLEARED: [{$li->id}] '{$cp}' (from: {$li->narration})\n";
        }
    }
}

echo "\nReparsed: {$reparsed}, Cleared: {$cleaned}\n";

// Verify 1200 sub-ledger again
$acct = DB::table('ifrs_accounts')->where('entity_id', 109)->where('code', 1200)->first();
if ($acct) {
    echo "\n1200 Sub-ledger:\n";
    $groups = DB::table('ifrs_ledgers as l')
        ->leftJoin('ifrs_line_items as li', 'l.line_item_id', '=', 'li.id')
        ->where('l.entity_id', 109)
        ->where('l.post_account', $acct->id)
        ->select([
            'li.counterparty_name',
            DB::raw("SUM(CASE WHEN l.entry_type = 'D' THEN l.amount ELSE 0 END) as dr"),
            DB::raw("SUM(CASE WHEN l.entry_type = 'C' THEN l.amount ELSE 0 END) as cr"),
        ])
        ->groupBy('li.counterparty_name')
        ->orderByDesc('cr')
        ->get();
    foreach ($groups as $g) {
        echo "  " . ($g->counterparty_name ?? '(none)') . ": DR=" . number_format($g->dr) . " CR=" . number_format($g->cr) . "\n";
    }
}

// Also check 2200 (payables) sub-ledger
$acct2200 = DB::table('ifrs_accounts')->where('entity_id', 109)->where('code', 2200)->first();
if ($acct2200) {
    echo "\n2200 Sub-ledger:\n";
    $groups = DB::table('ifrs_ledgers as l')
        ->leftJoin('ifrs_line_items as li', 'l.line_item_id', '=', 'li.id')
        ->where('l.entity_id', 109)
        ->where('l.post_account', $acct2200->id)
        ->select([
            'li.counterparty_name',
            DB::raw("SUM(CASE WHEN l.entry_type = 'D' THEN l.amount ELSE 0 END) as dr"),
            DB::raw("SUM(CASE WHEN l.entry_type = 'C' THEN l.amount ELSE 0 END) as cr"),
        ])
        ->groupBy('li.counterparty_name')
        ->orderByDesc('dr')
        ->get();
    foreach ($groups as $g) {
        echo "  " . ($g->counterparty_name ?? '(none)') . ": DR=" . number_format($g->dr) . " CR=" . number_format($g->cr) . "\n";
    }
}
