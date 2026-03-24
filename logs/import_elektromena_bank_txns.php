<?php
/**
 * Import bank transactions from journal CSVs for company 118 (Elektromena DOOEL)
 *
 * Reads journal-style bank statement CSVs (Windows-1251, semicolon-delimited).
 * Each CSV has grouped journal entries — we extract the account 1000 (cash at bank)
 * line from each group as the actual bank transaction, with counterparty info from
 * the non-1000 entries.
 *
 * Files:
 *   bank transfers.csv/2/3  → Тутунска Банка (Налог 3001-3003, Jan-Mar)
 *   unibanka1.csv/2/3       → Уни Банка (Налог 4001-4003, Jan-Mar)
 *
 * Usage: php logs/import_elektromena_bank_txns.php
 * Idempotent: skips transactions that match existing date+amount+bank_account.
 */
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BankAccount;
use App\Models\BankTransaction;
use Illuminate\Support\Facades\DB;

$companyId = 118;

// Find bank accounts
$tutunska = BankAccount::where('company_id', $companyId)
    ->where('bank_name', 'LIKE', '%Тутунска%')
    ->first();
$unibanka = BankAccount::where('company_id', $companyId)
    ->where('bank_name', 'LIKE', '%Уни%')
    ->first();

if (!$tutunska || !$unibanka) {
    echo "ERROR: Bank accounts not found. Тутунска=" . ($tutunska ? $tutunska->id : 'NULL')
        . " Уни=" . ($unibanka ? $unibanka->id : 'NULL') . "\n";
    exit(1);
}

echo "Тутунска Банка: ID {$tutunska->id}, account={$tutunska->account_number}\n";
echo "Уни Банка: ID {$unibanka->id}, account={$unibanka->account_number}\n\n";

// File → bank account mapping
$files = [
    ['path' => 'bank transfers.csv',  'bank' => $tutunska, 'label' => 'ТУТУНСКА БАНКА'],
    ['path' => 'bank transfers2.csv', 'bank' => $tutunska, 'label' => 'ТУТУНСКА БАНКА'],
    ['path' => 'bank transfers3.csv', 'bank' => $tutunska, 'label' => 'ТУТУНСКА БАНКА'],
    ['path' => 'unibanka1.csv',       'bank' => $unibanka, 'label' => 'УНИ БАНКА'],
    ['path' => 'unibanka2.csv',       'bank' => $unibanka, 'label' => 'УНИ БАНКА'],
    ['path' => 'unibanka3.csv',       'bank' => $unibanka, 'label' => 'УНИ БАНКА'],
];

$totalCreated = 0;
$totalSkipped = 0;
$totalErrors = 0;

foreach ($files as $fileInfo) {
    $csvPath = '/var/www/html/elktro/' . $fileInfo['path'];
    if (!file_exists($csvPath)) {
        $csvPath = __DIR__ . '/../elktro/' . $fileInfo['path'];
    }
    if (!file_exists($csvPath)) {
        echo "WARN: File not found: {$fileInfo['path']}\n";
        continue;
    }

    $bankAccount = $fileInfo['bank'];
    $bankLabel = $fileInfo['label'];

    $raw = file_get_contents($csvPath);
    $content = mb_convert_encoding($raw, 'UTF-8', 'Windows-1251');
    $lines = explode("\n", str_replace("\r\n", "\n", $content));

    echo "=== {$fileInfo['path']} → {$bankAccount->bank_name} ===\n";

    // Parse into groups by transaction number (col 2)
    $groups = [];
    foreach ($lines as $line) {
        $cols = explode(';', $line);
        if (count($cols) < 25) continue;

        $rowNum = trim($cols[1] ?? '');
        if (!$rowNum || !is_numeric($rowNum)) continue;

        $txNum = trim($cols[2] ?? '');
        $desc = trim($cols[6] ?? '');
        $acct = trim($cols[8] ?? '');
        $partner = trim($cols[12] ?? '');
        $debitStr = trim($cols[14] ?? '');
        $creditStr = trim($cols[18] ?? '');
        $dateStr = trim($cols[24] ?? '');

        $debit = parseMkNumber($debitStr);
        $credit = parseMkNumber($creditStr);

        if (!isset($groups[$txNum])) {
            $groups[$txNum] = ['entries' => [], 'bank_entry' => null];
        }

        $entry = [
            'acct' => $acct,
            'partner' => $partner,
            'desc' => $desc,
            'debit' => $debit,
            'credit' => $credit,
            'date' => $dateStr,
        ];

        $groups[$txNum]['entries'][] = $entry;

        // Account 1000 = cash at bank — this is the actual bank transaction
        if ($acct === '1000') {
            $groups[$txNum]['bank_entry'] = $entry;
        }
    }

    $created = 0;
    $skipped = 0;

    foreach ($groups as $txNum => $group) {
        $bankEntry = $group['bank_entry'];
        if (!$bankEntry) continue;

        // Debit on 1000 = money coming IN (credit transaction)
        // Credit on 1000 = money going OUT (debit transaction)
        $isCredit = $bankEntry['debit'] > 0;
        $amount = $isCredit ? $bankEntry['debit'] : $bankEntry['credit'];

        if ($amount == 0) continue;

        // Get counterparty from non-1000 entries (skip bank name itself)
        $counterparties = [];
        $descriptions = [];
        foreach ($group['entries'] as $e) {
            if ($e['acct'] === '1000') continue;
            if ($e['partner'] && $e['partner'] !== $bankLabel) {
                $counterparties[] = $e['partner'];
            }
            if ($e['desc']) {
                $descriptions[] = $e['desc'];
            }
        }

        $counterparty = $counterparties[0] ?? '';
        $description = $descriptions[0] ?? ($counterparty ?: 'Банкарска трансакција');

        // Parse date: "02.01.2026" → "2026-01-02"
        $dateParts = explode('.', $bankEntry['date']);
        if (count($dateParts) !== 3) continue;
        $txDate = "{$dateParts[2]}-{$dateParts[1]}-{$dateParts[0]}";

        // Build a reference for idempotency
        $reference = "JN-{$fileInfo['path']}-TX{$txNum}";

        // Check for existing transaction
        $existing = BankTransaction::where('bank_account_id', $bankAccount->id)
            ->where('transaction_reference', $reference)
            ->first();

        if ($existing) {
            $skipped++;
            continue;
        }

        try {
            BankTransaction::create([
                'bank_account_id' => $bankAccount->id,
                'company_id' => $companyId,
                'transaction_reference' => $reference,
                'amount' => $amount,
                'currency' => 'MKD',
                'transaction_type' => $isCredit ? 'credit' : 'debit',
                'booking_status' => 'booked',
                'transaction_date' => $txDate,
                'booking_date' => $txDate,
                'value_date' => $txDate,
                'description' => $description,
                'debtor_name' => $isCredit ? $counterparty : null,
                'creditor_name' => !$isCredit ? $counterparty : null,
                'processing_status' => 'unprocessed',
                'source' => 'csv_import',
            ]);

            $created++;
            $type = $isCredit ? 'IN' : 'OUT';
            echo "  TX {$txNum} | {$txDate} | {$type} | {$amount} | {$counterparty}\n";
        } catch (\Exception $e) {
            $totalErrors++;
            echo "  ERROR TX {$txNum}: {$e->getMessage()}\n";
        }
    }

    echo "  Created: {$created}, Skipped: {$skipped}\n\n";
    $totalCreated += $created;
    $totalSkipped += $skipped;
}

// Update bank account balances
foreach ([$tutunska, $unibanka] as $account) {
    $credits = BankTransaction::where('bank_account_id', $account->id)
        ->where('transaction_type', 'credit')
        ->sum('amount');
    $debits = BankTransaction::where('bank_account_id', $account->id)
        ->where('transaction_type', 'debit')
        ->sum('amount');
    $balance = $credits - $debits;

    $account->update(['current_balance' => $balance]);
    echo "Updated {$account->bank_name} balance: {$balance} MKD (credits={$credits}, debits={$debits})\n";
}

echo "\n=== SUMMARY ===\n";
echo "Created: {$totalCreated}\n";
echo "Skipped: {$totalSkipped}\n";
echo "Errors: {$totalErrors}\n";

function parseMkNumber(string $str): float
{
    $str = trim($str);
    if ($str === '' || $str === '0') return 0.0;
    return floatval(str_replace(['.', ','], ['', '.'], $str));
}

// CLAUDE-CHECKPOINT
