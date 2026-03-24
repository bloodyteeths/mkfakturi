<?php
/**
 * Fix IFRS counterparty_name double-booking for company 118 (Elektromena DOOEL)
 *
 * The IFRS import assigned counterparty_name to ALL line items of each journal entry,
 * not just the AR/AP lines. This caused supplier payment journals to have both
 * debit and credit on account 2200 with the same counterparty, creating phantom
 * double entries on ledger cards.
 *
 * Fix: Remove counterparty_name from non-AR/AP line items (keep it only on
 * RECEIVABLE and PAYABLE account lines).
 *
 * Usage: php logs/fix_ifrs_counterparty.php
 */
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$entityId = 109; // Elektromena IFRS entity

// Find all line items with counterparty_name on NON-AR/AP accounts
$badLines = DB::table('ifrs_line_items as li')
    ->join('ifrs_accounts as a', 'li.account_id', '=', 'a.id')
    ->where('li.entity_id', $entityId)
    ->whereNotNull('li.counterparty_name')
    ->whereNotIn('a.account_type', ['RECEIVABLE', 'PAYABLE'])
    ->select('li.id', 'li.counterparty_name', 'a.code', 'a.name', 'a.account_type', 'li.transaction_id')
    ->get();

echo "Found {$badLines->count()} line items with counterparty_name on non-AR/AP accounts\n\n";

if ($badLines->isEmpty()) {
    echo "Nothing to fix!\n";
    exit(0);
}

// Show what will be cleaned
foreach ($badLines as $line) {
    echo "  ID {$line->id} | tx={$line->transaction_id} | {$line->code} {$line->name} [{$line->account_type}] | counterparty: {$line->counterparty_name}\n";
}

echo "\nClearing counterparty_name from these lines...\n";

$updated = DB::table('ifrs_line_items')
    ->whereIn('id', $badLines->pluck('id'))
    ->update(['counterparty_name' => null]);

echo "Updated: {$updated} rows\n";

// Verify: check remaining counterparty_name entries
$remaining = DB::table('ifrs_line_items as li')
    ->join('ifrs_accounts as a', 'li.account_id', '=', 'a.id')
    ->where('li.entity_id', $entityId)
    ->whereNotNull('li.counterparty_name')
    ->select('a.account_type', DB::raw('COUNT(*) as cnt'))
    ->groupBy('a.account_type')
    ->get();

echo "\nRemaining counterparty_name entries by account type:\n";
foreach ($remaining as $r) {
    echo "  {$r->account_type}: {$r->cnt}\n";
}

// Verify ВАРСА ТРАНС is fixed
echo "\n=== Verify ВАРСА ТРАНС ===\n";
$varsa = DB::table('ifrs_ledgers as l')
    ->join('ifrs_accounts as a', 'l.post_account', '=', 'a.id')
    ->leftJoin('ifrs_line_items as li', 'l.line_item_id', '=', 'li.id')
    ->where('l.entity_id', $entityId)
    ->where('li.counterparty_name', 'ВАРСА ТРАНС ДООЕЛ')
    ->select('l.posting_date', 'l.entry_type', 'l.amount', 'a.code', 'a.account_type')
    ->get();
echo "Entries with counterparty ВАРСА ТРАНС: {$varsa->count()}\n";
foreach ($varsa as $v) {
    echo "  {$v->posting_date} {$v->entry_type} {$v->amount} {$v->code} [{$v->account_type}]\n";
}

// Verify МР.БОЛТ is fixed
echo "\n=== Verify МР.БОЛТ ===\n";
$bolt = DB::table('ifrs_ledgers as l')
    ->join('ifrs_accounts as a', 'l.post_account', '=', 'a.id')
    ->leftJoin('ifrs_line_items as li', 'l.line_item_id', '=', 'li.id')
    ->where('l.entity_id', $entityId)
    ->where('li.counterparty_name', 'МР.БОЛТ')
    ->select('l.posting_date', 'l.entry_type', 'l.amount', 'a.code', 'a.account_type')
    ->get();
echo "Entries with counterparty МР.БОЛТ: {$bolt->count()}\n";
foreach ($bolt as $b) {
    echo "  {$b->posting_date} {$b->entry_type} {$b->amount} {$b->code} [{$b->account_type}]\n";
}

echo "\nDone!\n";

// CLAUDE-CHECKPOINT
