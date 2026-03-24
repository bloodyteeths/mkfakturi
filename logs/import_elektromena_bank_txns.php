<?php
/**
 * Import bank transactions from journal CSVs for company 118 (Elektromena DOOEL)
 *
 * Reads journal-style bank statement CSVs (Windows-1251, semicolon-delimited).
 * Each CSV has grouped journal entries — we extract EVERY account 1000 (cash at bank)
 * line as a separate bank transaction. Some groups have multiple 1000 lines
 * (e.g. a customer payment IN and supplier payment OUT on the same date).
 *
 * Sign convention:
 *   Debit on 1000 = money IN  → positive amount, transaction_type=credit
 *   Credit on 1000 = money OUT → negative amount, transaction_type=debit
 *
 * Opening balances (from repNalog.csv + repNalog2.csv):
 *   Тутунска: +63,902 (account 1000 debit)
 *   Уни: -63,902 (repNalog 1000 credit) + -1,370,773 (repNalog2 overdraft) = -1,434,675
 *
 * Expected closing balances:
 *   Тутунска: 155 (63,902 - 63,747 = 155)
 *   Уни: -1,499,008 (-1,434,675 - 64,333 = -1,499,008)
 *
 * Files:
 *   bank transfers.csv/2/3  → Тутунска Банка (Налог 3001-3003, Jan-Mar)
 *   unibanka1.csv/2/3       → Уни Банка (Налог 4001-4003, Jan-Mar)
 *
 * Usage: php logs/import_elektromena_bank_txns.php
 *
 * DESTRUCTIVE: Deletes all existing csv_import transactions for company 118
 * before re-importing to ensure correct data.
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

// Delete ALL existing csv_import transactions for this company
$deleted = BankTransaction::where('company_id', $companyId)
    ->where('source', 'csv_import')
    ->delete();
echo "Deleted {$deleted} existing csv_import transactions\n\n";

// Set opening balances from repNalog.csv + repNalog2.csv
// Тутунска: account 1000 debit 63,902 → +63,902
// Уни: account 1000 credit 63,902 (repNalog) + credit 1,370,773 (repNalog2 overdraft) → -1,434,675
$tutunska->update(['opening_balance' => 63902, 'current_balance' => 63902]);
$unibanka->update(['opening_balance' => -1434675, 'current_balance' => -1434675]);
echo "Set Тутунска opening_balance=63902\n";
echo "Set Уни opening_balance=-1434675\n\n";

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

    // Parse into groups by transaction number (col 2), collecting ALL entries
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
            $groups[$txNum] = ['entries' => [], 'bank_entries' => []];
        }

        $entry = [
            'row' => $rowNum,
            'acct' => $acct,
            'partner' => $partner,
            'desc' => $desc,
            'debit' => $debit,
            'credit' => $credit,
            'date' => $dateStr,
        ];

        $groups[$txNum]['entries'][] = $entry;

        // Account 1000 = cash at bank — each line is a bank movement
        if ($acct === '1000') {
            $groups[$txNum]['bank_entries'][] = $entry;
        }
    }

    $created = 0;

    foreach ($groups as $txNum => $group) {
        if (empty($group['bank_entries'])) continue;

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

        // Process EACH 1000 line as a separate bank transaction
        $lineIdx = 0;
        foreach ($group['bank_entries'] as $bankEntry) {
            $lineIdx++;

            // Debit on 1000 = money IN (positive), Credit on 1000 = money OUT (negative)
            $isIncoming = $bankEntry['debit'] > 0;
            $signedAmount = $bankEntry['debit'] - $bankEntry['credit'];

            if ($signedAmount == 0) continue;

            $counterparty = $counterparties[0] ?? '';
            $description = $descriptions[0] ?? ($counterparty ?: 'Банкарска трансакција');

            // Parse date: "02.01.2026" → "2026-01-02"
            $dateParts = explode('.', $bankEntry['date']);
            if (count($dateParts) !== 3) continue;
            $txDate = "{$dateParts[2]}-{$dateParts[1]}-{$dateParts[0]}";

            // Unique reference per 1000 line (not per group)
            $reference = "JN-{$fileInfo['path']}-TX{$txNum}-L{$lineIdx}";

            try {
                BankTransaction::create([
                    'bank_account_id' => $bankAccount->id,
                    'company_id' => $companyId,
                    'transaction_reference' => $reference,
                    'amount' => $signedAmount,
                    'currency' => 'MKD',
                    'transaction_type' => $isIncoming ? 'credit' : 'debit',
                    'booking_status' => 'booked',
                    'transaction_date' => $txDate,
                    'booking_date' => $txDate,
                    'value_date' => $txDate,
                    'description' => $description,
                    'debtor_name' => $isIncoming ? $counterparty : null,
                    'creditor_name' => !$isIncoming ? $counterparty : null,
                    'processing_status' => 'unprocessed',
                    'source' => 'csv_import',
                ]);

                $created++;
                $sign = $signedAmount >= 0 ? '+' : '-';
                $absAmt = abs($signedAmount);
                echo "  TX {$txNum}.{$lineIdx} | {$txDate} | {$sign}{$absAmt} | {$counterparty}\n";
            } catch (\Exception $e) {
                $totalErrors++;
                echo "  ERROR TX {$txNum}.{$lineIdx}: {$e->getMessage()}\n";
            }
        }
    }

    echo "  Created: {$created}\n\n";
    $totalCreated += $created;
}

// Recalculate bank account balances: opening_balance + sum(signed amounts)
foreach ([$tutunska, $unibanka] as $account) {
    $account->refresh();
    $totalAmount = BankTransaction::where('bank_account_id', $account->id)->sum('amount');
    $balance = $account->opening_balance + $totalAmount;

    $account->update(['current_balance' => $balance]);
    echo "Updated {$account->bank_name}: opening={$account->opening_balance}, sum={$totalAmount}, current={$balance}\n";
}

echo "\n=== SUMMARY ===\n";
echo "Created: {$totalCreated}\n";
echo "Errors: {$totalErrors}\n";

function parseMkNumber(string $str): float
{
    $str = trim($str);
    if ($str === '' || $str === '0') return 0.0;
    // Handle negative numbers like "-1.370.773"
    $negative = false;
    if (str_starts_with($str, '-')) {
        $negative = true;
        $str = substr($str, 1);
    }
    $val = floatval(str_replace(['.', ','], ['', '.'], $str));
    return $negative ? -$val : $val;
}

// CLAUDE-CHECKPOINT
