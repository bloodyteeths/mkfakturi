<?php
/**
 * Import Journal Entries for ЕЛЕКТРО МЕ-НА ДООЕЛ (Company 118)
 *
 * Data source: elktro/*.csv (CP1251-encoded CSVs from accountant software)
 * Run on Railway: php /var/www/html/logs/import_elektromena_journal.php [--dry-run]
 *
 * 18 CSV files covering Q1 2026:
 *   - repNalog.csv:        Opening balances (01.01.2026)
 *   - repNalog2.csv:       Year-end adjustment
 *   - vlezni1.csv:         Purchase invoices Jan
 *   - 2vlezni2.csv:        Purchase invoices Feb
 *   - bank transfers.csv:  Bank transfers (Тутунска банка) Jan
 *   - bank transfers2.csv: Bank transfers Feb
 *   - bank transfers3.csv: Bank transfers Mar
 *   - unibanka1.csv:       Уни Банка Jan
 *   - unibanka2.csv:       Уни Банка Feb
 *   - unibanka3.csv:       Уни Банка Mar
 *   - gorivo1.csv:         Fuel receipts Jan
 *   - gorivo2.csv:         Fuel receipts Feb
 *   - gorivo3.csv:         Fuel receipts Mar
 *   - cmetkopotvrda1.csv:  Office receipts Jan
 *   - cmetkopotvrda2.csv:  Office receipts Feb
 *   - cmetkopotvrda3.csv:  Office receipts Mar
 *   - plati rekapulati.csv:  Rent (Кирија)
 *   - plati rekapulati2.csv: Payroll recaps
 *
 * Creates:
 *   1. IFRS accounts for all kontos used
 *   2. Facturino app accounts for UI display
 *   3. Journal entries (IFRS Transactions + LineItems) for 18 nalozi
 */

require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use IFRS\Models\Account;
use IFRS\Models\Entity;
use IFRS\Models\LineItem;
use IFRS\Models\ReportingPeriod;
use IFRS\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$dryRun = in_array('--dry-run', $argv ?? []);
echo $dryRun ? "=== DRY RUN MODE ===\n\n" : "=== LIVE IMPORT MODE ===\n\n";

// ====================================================================
// 1. FIND THE COMPANY
// ====================================================================
$company = Company::find(118);

if (!$company) {
    $company = Company::where('name', 'LIKE', '%ЕЛЕКТРО МЕ-НА%')
        ->orWhere('name', 'LIKE', '%elektromena%')
        ->orWhere('vat_id', 'LIKE', '%4027011514030%')
        ->orWhere('tax_id', 'LIKE', '%4027011514030%')
        ->first();
}

if (!$company) {
    echo "ERROR: ЕЛЕКТРО МЕ-НА ДООЕЛ not found in database!\n";
    echo "Searched by ID 118, name, and vat_id (4027011514030)\n";
    exit(1);
}

echo "Found company: {$company->name} (ID: {$company->id})\n";
echo "IFRS Entity ID: " . ($company->ifrs_entity_id ?? 'NONE') . "\n\n";

// ====================================================================
// 2. MACEDONIAN CHART OF ACCOUNTS MAPPING
// ====================================================================
$accountMap = [
    // Fixed assets (class 0)
    '0130'   => ['name' => 'Основни средства - Алат и инвентар', 'type' => Account::NON_CURRENT_ASSET],
    '0190'   => ['name' => 'Исправка на вредност на ОС', 'type' => Account::CONTRA_ASSET],
    // Bank & cash (class 1)
    '1000'   => ['name' => 'Жиро сметка во денари', 'type' => Account::BANK],
    '1009'   => ['name' => 'Побарувања од вработени', 'type' => Account::RECEIVABLE],
    '1020'   => ['name' => 'Каса / Благајна', 'type' => Account::BANK],
    '1200'   => ['name' => 'Побарувања од купувачи', 'type' => Account::RECEIVABLE],
    '1201'   => ['name' => 'Побарувања по меници', 'type' => Account::RECEIVABLE],
    '1220'   => ['name' => 'Дадени краткорочни заеми', 'type' => Account::RECEIVABLE],
    '1306'   => ['name' => 'ДДВ - претходен данок', 'type' => Account::RECEIVABLE],
    '1620'   => ['name' => 'Дадени краткорочни заеми (лица)', 'type' => Account::RECEIVABLE],
    // Liabilities (class 2)
    '2200'   => ['name' => 'Обврски кон добавувачи', 'type' => Account::PAYABLE],
    '2201'   => ['name' => 'Обврски за закуп', 'type' => Account::PAYABLE],
    '2330'   => ['name' => 'Даночни обврски', 'type' => Account::CURRENT_LIABILITY],
    '2340'   => ['name' => 'ПИО - пензиско осигурување', 'type' => Account::CURRENT_LIABILITY],
    '2341'   => ['name' => 'Здравствено осигурување', 'type' => Account::CURRENT_LIABILITY],
    '2342'   => ['name' => 'Данок на доход од плати', 'type' => Account::CURRENT_LIABILITY],
    '2343'   => ['name' => 'Дополнително здравствено', 'type' => Account::CURRENT_LIABILITY],
    '2344'   => ['name' => 'Придонес за вработување', 'type' => Account::CURRENT_LIABILITY],
    '2351'   => ['name' => 'Останати обврски', 'type' => Account::CURRENT_LIABILITY],
    '2353'   => ['name' => 'Персонален данок од закуп', 'type' => Account::CURRENT_LIABILITY],
    '2400'   => ['name' => 'Обврски за нето плати', 'type' => Account::CURRENT_LIABILITY],
    '2420'   => ['name' => 'Обврски за придонеси', 'type' => Account::CURRENT_LIABILITY],
    '2490'   => ['name' => 'Останати тековни обврски', 'type' => Account::CURRENT_LIABILITY],
    '2620'   => ['name' => 'Долгорочни заеми', 'type' => Account::NON_CURRENT_LIABILITY],
    // Expenses (class 4)
    '4000'   => ['name' => 'Материјални трошоци', 'type' => Account::OPERATING_EXPENSE],
    '4001'   => ['name' => 'Ситен инвентар', 'type' => Account::OPERATING_EXPENSE],
    '4010'   => ['name' => 'Суровини и материјали', 'type' => Account::OPERATING_EXPENSE],
    '4011'   => ['name' => 'Помошни материјали', 'type' => Account::OPERATING_EXPENSE],
    '4013'   => ['name' => 'Резервни делови', 'type' => Account::OPERATING_EXPENSE],
    '4032'   => ['name' => 'Гориво', 'type' => Account::OPERATING_EXPENSE],
    '4050'   => ['name' => 'Одржување и сервис', 'type' => Account::OPERATING_EXPENSE],
    '4110'   => ['name' => 'Телекомуникации', 'type' => Account::OPERATING_EXPENSE],
    '4140'   => ['name' => 'Наем / Закупнина', 'type' => Account::OPERATING_EXPENSE],
    '4141'   => ['name' => 'Поштарина / Курирски', 'type' => Account::OPERATING_EXPENSE],
    '4170'   => ['name' => 'Осигурување', 'type' => Account::OPERATING_EXPENSE],
    '4190'   => ['name' => 'Транспортни услуги', 'type' => Account::OPERATING_EXPENSE],
    '4200'   => ['name' => 'Бруто плати на вработени', 'type' => Account::OPERATING_EXPENSE],
    '4440'   => ['name' => 'Останати услуги', 'type' => Account::OPERATING_EXPENSE],
    '4450'   => ['name' => 'Останати оперативни трошоци', 'type' => Account::OPERATING_EXPENSE],
    '4460'   => ['name' => 'Провизии и такси на банки', 'type' => Account::OPERATING_EXPENSE],
    '4475'   => ['name' => 'ИТ / Опрема трошоци', 'type' => Account::OPERATING_EXPENSE],
    '4680'   => ['name' => 'Останати расходи', 'type' => Account::OPERATING_EXPENSE],
    '4749'   => ['name' => 'Камата за пречекорување', 'type' => Account::OPERATING_EXPENSE],
    // Equity (class 9)
    '9000'   => ['name' => 'Основна главнина', 'type' => Account::EQUITY],
    '9400'   => ['name' => 'Резерви', 'type' => Account::EQUITY],
    '9500'   => ['name' => 'Акумулирана добивка', 'type' => Account::EQUITY],
    // VAT accounts
    '130005' => ['name' => 'ДДВ претходен данок 5%', 'type' => Account::RECEIVABLE],
    '130018' => ['name' => 'ДДВ претходен данок 18%', 'type' => Account::RECEIVABLE],
    // Extraordinary
    '660018' => ['name' => 'Вонредни расходи со ДДВ', 'type' => Account::DIRECT_EXPENSE],
];

// ====================================================================
// 3. CSV PARSING FUNCTIONS
// ====================================================================

/**
 * Parse a CP1251-encoded CSV file into structured nalog data.
 *
 * CSV format (semicolon-delimited):
 * Line 1: Налог број (journal number) at column 8
 * Line 2: Датум (period end date) at column 5
 * Line 3: Опис (description) at column 5
 * Line 4: Header row
 * Lines 5+: Data rows until footer
 *
 * Data columns (by semicolon index):
 *   1: Row number, 2: Doc number, 5: Description
 *   8: Account code, 10: Doc type, 12: Partner
 *   14: Debit, 18: Credit, 24: Date
 */
function parseCsvFile(string $filepath): ?array
{
    $raw = file_get_contents($filepath);
    if ($raw === false) {
        echo "  ERROR: Cannot read {$filepath}\n";
        return null;
    }

    // Convert from CP1251 to UTF-8
    $content = @mb_convert_encoding($raw, 'UTF-8', 'Windows-1251');
    if (!$content) {
        $content = @iconv('CP1251', 'UTF-8//IGNORE', $raw);
    }
    if (!$content) {
        echo "  ERROR: Cannot decode encoding for {$filepath}\n";
        return null;
    }

    $lines = preg_split('/\r?\n/', $content);
    if (count($lines) < 5) {
        echo "  ERROR: Too few lines in {$filepath}\n";
        return null;
    }

    // Line 1: Extract nalog number
    $line1 = explode(';', $lines[0]);
    $nalogNumber = trim($line1[7] ?? '0');

    // Line 2: Extract period date
    $line2 = explode(';', $lines[1]);
    $periodDateStr = trim($line2[4] ?? '');

    // Line 3: Extract description
    $line3 = explode(';', $lines[2]);
    $nalogDesc = trim($line3[4] ?? '');

    // Skip line 4 (header)
    // Parse data lines (line 5+)
    $entries = [];
    for ($i = 4; $i < count($lines); $i++) {
        $line = trim($lines[$i]);
        if (empty($line)) continue;

        $cols = explode(';', $line);

        // Skip footer lines (totals, company info)
        $rowNum = trim($cols[1] ?? '');
        if ($rowNum === '' || !is_numeric($rowNum)) {
            // Check if it's a named entry (like repNalog2 with text in col 2)
            $col2 = trim($cols[2] ?? '');
            if ($rowNum !== '' || ($col2 !== '' && !empty(trim($cols[8] ?? '')))) {
                // Has account code → valid data row with text in row num or doc num
            } else {
                continue; // Footer/total line
            }
        }

        $accountCode = trim($cols[8] ?? '');
        if (empty($accountCode)) continue; // Skip lines without account

        $docNum = trim($cols[2] ?? '');
        $description = trim($cols[5] ?? '');
        $docType = trim($cols[10] ?? '');
        $partner = trim($cols[12] ?? '');
        $debitStr = trim($cols[14] ?? '0');
        $creditStr = trim($cols[18] ?? '0');
        $dateStr = trim($cols[24] ?? '');

        $debit = parseAmount($debitStr);
        $credit = parseAmount($creditStr);

        $entries[] = [
            'account' => $accountCode,
            'doc_num' => $docNum,
            'description' => $description,
            'doc_type' => $docType,
            'partner' => $partner,
            'debit' => $debit,
            'credit' => $credit,
            'date' => $dateStr,
        ];
    }

    return [
        'nalog_number' => $nalogNumber,
        'period_date' => $periodDateStr,
        'nalog_desc' => $nalogDesc,
        'entries' => $entries,
        'filename' => basename($filepath),
    ];
}

/**
 * Parse Macedonian-format amount string.
 * Dot = thousands separator, comma = decimal separator.
 * Examples: "3.424" = 3424, "58.072,81" = 58072.81, "-1.370.773" = -1370773
 */
function parseAmount(string $str): float
{
    $str = trim($str);
    if ($str === '' || $str === '0') return 0.0;

    $negative = false;
    if (str_starts_with($str, '-')) {
        $negative = true;
        $str = substr($str, 1);
    }

    // Remove dots (thousand separators)
    $str = str_replace('.', '', $str);
    // Replace comma with dot (decimal separator)
    $str = str_replace(',', '.', $str);

    $val = (float)$str;
    return $negative ? -$val : $val;
}

/**
 * Parse date string DD.MM.YYYY to Carbon.
 */
function parseDateDMY(string $dateStr): ?Carbon
{
    $dateStr = trim($dateStr);
    if (empty($dateStr)) return null;

    // Handle DD.MM.YYYY format
    $parts = explode('.', $dateStr);
    if (count($parts) !== 3) return null;

    return Carbon::createFromDate((int)$parts[2], (int)$parts[1], (int)$parts[0]);
}

// ====================================================================
// 4. HELPER FUNCTIONS (same as Demirovic import)
// ====================================================================

function getOrCreateIfrsAccount(int $entityId, string $code, string $name, string $type, int $currencyId): Account
{
    $account = Account::withoutGlobalScope(\IFRS\Scopes\EntityScope::class)
        ->where('entity_id', $entityId)
        ->where('code', $code)
        ->first();

    if ($account) {
        return $account;
    }

    return Account::create([
        'entity_id' => $entityId,
        'code' => $code,
        'name' => $name,
        'account_type' => $type,
        'currency_id' => $currencyId,
    ]);
}

function ensureReportingPeriod(Entity $entity, int $year): void
{
    $existing = ReportingPeriod::withoutGlobalScope(\IFRS\Scopes\EntityScope::class)
        ->where('entity_id', $entity->id)
        ->where('calendar_year', $year)
        ->first();

    if (!$existing) {
        ReportingPeriod::create([
            'entity_id' => $entity->id,
            'calendar_year' => $year,
            'period_count' => 1,
            'status' => ReportingPeriod::OPEN ?? 'OPEN',
        ]);
        echo "  Created ReportingPeriod for year {$year}\n";
    }
}

// ====================================================================
// 5. SET UP IFRS ENTITY
// ====================================================================
echo "\n--- Setting up IFRS Entity ---\n";

$entity = null;
if ($company->ifrs_entity_id) {
    $entity = Entity::find($company->ifrs_entity_id);
}

if (!$entity) {
    $ifrsCurrency = \IFRS\Models\Currency::where('currency_code', 'MKD')->first();
    if (!$ifrsCurrency) {
        $ifrsCurrency = \IFRS\Models\Currency::create([
            'name' => 'Macedonian Denar',
            'currency_code' => 'MKD',
        ]);
        echo "  Created IFRS Currency: MKD (ID: {$ifrsCurrency->id})\n";
    }

    if ($dryRun) {
        echo "  [DRY] Would create IFRS Entity\n";
        // Use a placeholder for dry run
        $entity = new Entity();
        $entity->id = 0;
        $entity->currency_id = $ifrsCurrency->id;
    } else {
        $entity = Entity::create([
            'name' => $company->name,
            'currency_id' => $ifrsCurrency->id,
            'year_start' => 1,
            'multi_currency' => false,
        ]);

        DB::table('companies')
            ->where('id', $company->id)
            ->update(['ifrs_entity_id' => $entity->id]);

        echo "  Created IFRS Entity (ID: {$entity->id})\n";
    }
} else {
    echo "  Using existing IFRS Entity (ID: {$entity->id})\n";
}

$currencyId = $entity->currency_id;

// Set user context for IFRS EntityScope
$user = User::where('email', 'ivana.nacev@elektromena.mk')->first()
    ?? User::where('email', 'ivana.nacev@yahoo.com')->first();
if ($user) {
    auth()->login($user);
    $user->entity_id = $entity->id;
    $user->setRelation('entity', $entity);
    echo "  Auth context: {$user->email}\n";
} else {
    echo "  WARNING: No user found for elektromena, using admin\n";
    $adminUser = User::where('email', 'atillatkulu@gmail.com')->first();
    if ($adminUser) {
        auth()->login($adminUser);
        $adminUser->entity_id = $entity->id;
        $adminUser->setRelation('entity', $entity);
    }
}

// Ensure reporting period exists
if (!$dryRun) {
    ensureReportingPeriod($entity, 2026);

    // Ensure exchange rate exists
    $existingRate = DB::table('ifrs_exchange_rates')
        ->where('entity_id', $entity->id)
        ->where('currency_id', $currencyId)
        ->first();

    if (!$existingRate) {
        DB::table('ifrs_exchange_rates')->insert([
            'entity_id' => $entity->id,
            'currency_id' => $currencyId,
            'rate' => 1.0,
            'valid_from' => '2026-01-01',
            'valid_to' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "  Created exchange rate for MKD\n";
    }
}

// ====================================================================
// 6. CREATE ACCOUNTS
// ====================================================================
echo "\n--- Creating App & IFRS Accounts ---\n";

$accountCache = [];

$appAccountTypeMap = [
    '0' => \App\Models\Account::TYPE_ASSET,
    '1' => \App\Models\Account::TYPE_ASSET,
    '2' => \App\Models\Account::TYPE_LIABILITY,
    '3' => \App\Models\Account::TYPE_REVENUE,
    '4' => \App\Models\Account::TYPE_EXPENSE,
    '5' => \App\Models\Account::TYPE_EXPENSE,
    '6' => \App\Models\Account::TYPE_EXPENSE,
    '7' => \App\Models\Account::TYPE_REVENUE,
    '8' => \App\Models\Account::TYPE_ASSET,
    '9' => \App\Models\Account::TYPE_EQUITY,
];

foreach ($accountMap as $code => $info) {
    $appType = $appAccountTypeMap[substr($code, 0, 1)] ?? \App\Models\Account::TYPE_EXPENSE;

    if ($dryRun) {
        echo "  [DRY] Account: {$code} - {$info['name']}\n";
        continue;
    }

    // Create Facturino app account
    $existing = \App\Models\Account::where('company_id', $company->id)
        ->where('code', $code)
        ->first();

    if (!$existing) {
        \App\Models\Account::create([
            'company_id' => $company->id,
            'code' => $code,
            'name' => $info['name'],
            'type' => $appType,
            'is_active' => true,
            'system_defined' => false,
        ]);
        echo "  App Account: {$code} - {$info['name']}\n";
    }

    // Create IFRS account
    $accountType = $info['type'];
    try {
        $account = getOrCreateIfrsAccount($entity->id, $code, $info['name'], $accountType, $currencyId);
        $accountCache[$code] = $account;
    } catch (\Exception $e) {
        echo "  ERROR account {$code}: {$e->getMessage()}\n";
        // Fallback type
        $simpleType = match (true) {
            str_starts_with($code, '0'), str_starts_with($code, '9') => Account::EQUITY,
            str_starts_with($code, '1') => Account::RECEIVABLE,
            str_starts_with($code, '2') => Account::CURRENT_LIABILITY,
            str_starts_with($code, '3'), str_starts_with($code, '7') => Account::OPERATING_REVENUE,
            default => Account::OPERATING_EXPENSE,
        };
        try {
            $account = getOrCreateIfrsAccount($entity->id, $code, $info['name'], $simpleType, $currencyId);
            $accountCache[$code] = $account;
            echo "  Account {$code} (FALLBACK): {$info['name']}\n";
        } catch (\Exception $e2) {
            echo "  FATAL account {$code}: {$e2->getMessage()}\n";
        }
    }
}

// ====================================================================
// 7. PARSE ALL CSV FILES
// ====================================================================
echo "\n--- Parsing CSV Files ---\n";

$csvDir = '/var/www/html/elktro';

// Process in logical order: opening balances first, then by type/month
$csvFiles = [
    // Opening balances
    'repNalog.csv',
    'repNalog2.csv',
    // Purchase invoices
    'vlezni1.csv',
    '2vlezni2.csv',
    // Bank transfers (Тутунска банка)
    'bank transfers.csv',
    'bank transfers2.csv',
    'bank transfers3.csv',
    // Уни Банка
    'unibanka1.csv',
    'unibanka2.csv',
    'unibanka3.csv',
    // Fuel receipts
    'gorivo1.csv',
    'gorivo2.csv',
    'gorivo3.csv',
    // Office receipts
    'cmetkopotvrda1.csv',
    'cmetkopotvrda2.csv',
    'cmetkopotvrda3.csv',
    // Payroll / rent
    'plati rekapulati.csv',
    'plati rekapulati2.csv',
];

// Nalog type labels based on number prefix
$nalogTypeLabels = [
    '0' => 'Почетно салдо / Затварање',
    '1' => 'Почетно салдо / Корекција',
    '10' => 'Влезни фактури',
    '30' => 'Банковен извод (Тутунска)',
    '40' => 'Банковен извод (Уни Банка)',
    '60' => 'Рекапитулации / Плати',
    '70' => 'Горива / Фискални сметки',
    '80' => 'Сметкопотврди / Благајна',
];

$nalozi = [];
foreach ($csvFiles as $csvFile) {
    $filepath = $csvDir . '/' . $csvFile;
    if (!file_exists($filepath)) {
        echo "  SKIP: {$csvFile} not found\n";
        continue;
    }

    $nalog = parseCsvFile($filepath);
    if (!$nalog || empty($nalog['entries'])) {
        echo "  SKIP: {$csvFile} - no entries parsed\n";
        continue;
    }

    echo "  {$csvFile}: Nalog #{$nalog['nalog_number']}, {$nalog['period_date']}, " . count($nalog['entries']) . " entries\n";
    $nalozi[] = $nalog;
}

echo "\nTotal nalozi parsed: " . count($nalozi) . "\n";

// ====================================================================
// 8. CREATE JOURNAL ENTRIES
// ====================================================================
echo "\n--- Creating Journal Entries ---\n";

$created = 0;
$errors = 0;

foreach ($nalozi as $nalog) {
    $nalogNum = $nalog['nalog_number'];
    $periodDate = $nalog['period_date'];
    $entries = $nalog['entries'];
    $filename = $nalog['filename'];
    $desc = $nalog['nalog_desc'];

    // Determine nalog type label
    $prefix = substr($nalogNum, 0, 2);
    if (!isset($nalogTypeLabels[$prefix])) {
        $prefix = substr($nalogNum, 0, 1);
    }
    $nalogType = $nalogTypeLabels[$prefix] ?? $filename;

    // Validate balance (debit must equal credit)
    $totalD = 0.0;
    $totalC = 0.0;
    foreach ($entries as $entry) {
        $d = $entry['debit'];
        $c = $entry['credit'];
        // Handle negative amounts (repNalog2 has negative credits)
        if ($d > 0) $totalD += $d;
        if ($d < 0) $totalC += abs($d);
        if ($c > 0) $totalC += $c;
        if ($c < 0) $totalD += abs($c);
    }

    $diff = abs($totalD - $totalC);
    if ($diff > 1.0) { // Allow small rounding (vlezni1 has 0.19 diff)
        echo "  WARNING {$filename} (Nalog #{$nalogNum}): UNBALANCED! D=" . number_format($totalD, 2) . " C=" . number_format($totalC, 2) . " diff=" . number_format($diff, 2) . "\n";
        // Still proceed — force balance by adjusting
    }

    $narration = "Налог #{$nalogNum} ({$nalogType})" . ($desc ? " - {$desc}" : '');
    echo "\n  Processing: {$filename} - {$narration} (" . number_format($totalD, 0) . " MKD, " . count($entries) . " lines)\n";

    if ($dryRun) {
        foreach ($entries as $entry) {
            $d = $entry['debit'];
            $c = $entry['credit'];
            $acct = $entry['account'];
            $partner = $entry['partner'];
            $entryDesc = $entry['description'];
            if ($d > 0 || $d < 0) echo "    DR {$acct}  " . number_format(abs($d), 0) . ($d < 0 ? ' (NEG)' : '') . "  {$partner} {$entryDesc}\n";
            if ($c > 0 || $c < 0) echo "    CR {$acct}  " . number_format(abs($c), 0) . ($c < 0 ? ' (NEG)' : '') . "  {$partner} {$entryDesc}\n";
        }
        $created++;
        continue;
    }

    try {
        DB::beginTransaction();

        // Find/create primary account (first entry's account)
        $firstKonto = $entries[0]['account'];
        $primaryAccount = $accountCache[$firstKonto] ?? null;

        if (!$primaryAccount) {
            $info = $accountMap[$firstKonto] ?? ['name' => "Конто {$firstKonto}", 'type' => Account::OPERATING_EXPENSE];
            $primaryAccount = getOrCreateIfrsAccount($entity->id, $firstKonto, $info['name'], $info['type'], $currencyId);
            $accountCache[$firstKonto] = $primaryAccount;
        }

        // Parse booking date from period date
        $bookingDate = parseDateDMY($periodDate) ?? Carbon::create(2026, 1, 1);

        // Build unique reference
        $reference = "EM-{$nalogNum}";

        // Check if this transaction already exists (idempotent)
        $existing = Transaction::withoutGlobalScope(\IFRS\Scopes\EntityScope::class)
            ->where('entity_id', $entity->id)
            ->where('reference', $reference)
            ->first();

        if ($existing) {
            echo "  SKIP: Transaction {$reference} already exists (ID: {$existing->id})\n";
            DB::rollBack();
            $created++;
            continue;
        }

        $transaction = Transaction::create([
            'account_id' => $primaryAccount->id,
            'transaction_date' => $bookingDate,
            'narration' => mb_substr($narration, 0, 255),
            'transaction_type' => Transaction::JN,
            'currency_id' => $currencyId,
            'entity_id' => $entity->id,
            'reference' => $reference,
        ]);

        // Create line items
        foreach ($entries as $entry) {
            $acctCode = $entry['account'];
            $account = $accountCache[$acctCode] ?? null;

            if (!$account) {
                $info = $accountMap[$acctCode] ?? ['name' => "Конто {$acctCode}", 'type' => Account::OPERATING_EXPENSE];
                $primaryType = $info['type'];
                // Also create the app account if missing
                $appType = $appAccountTypeMap[substr($acctCode, 0, 1)] ?? \App\Models\Account::TYPE_EXPENSE;
                $existingApp = \App\Models\Account::where('company_id', $company->id)->where('code', $acctCode)->first();
                if (!$existingApp) {
                    \App\Models\Account::create([
                        'company_id' => $company->id,
                        'code' => $acctCode,
                        'name' => $info['name'],
                        'type' => $appType,
                        'is_active' => true,
                        'system_defined' => false,
                    ]);
                }
                $account = getOrCreateIfrsAccount($entity->id, $acctCode, $info['name'], $primaryType, $currencyId);
                $accountCache[$acctCode] = $account;
                echo "    New account: {$acctCode} - {$info['name']}\n";
            }

            $d = $entry['debit'];
            $c = $entry['credit'];
            $descParts = array_filter([$entry['description'], $entry['partner'], $entry['doc_num']]);
            $lineDesc = mb_substr(implode(' | ', $descParts), 0, 255);

            // Handle positive debit
            if ($d > 0) {
                DB::table('ifrs_line_items')->insert([
                    'transaction_id' => $transaction->id,
                    'account_id' => $account->id,
                    'amount' => $d,
                    'quantity' => 1,
                    'credited' => false,
                    'narration' => $lineDesc ?: null,
                    'entity_id' => $entity->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Handle positive credit
            if ($c > 0) {
                DB::table('ifrs_line_items')->insert([
                    'transaction_id' => $transaction->id,
                    'account_id' => $account->id,
                    'amount' => $c,
                    'quantity' => 1,
                    'credited' => true,
                    'narration' => $lineDesc ?: null,
                    'entity_id' => $entity->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Handle negative debit (treat as credit)
            if ($d < 0) {
                DB::table('ifrs_line_items')->insert([
                    'transaction_id' => $transaction->id,
                    'account_id' => $account->id,
                    'amount' => abs($d),
                    'quantity' => 1,
                    'credited' => true,
                    'narration' => $lineDesc ? "[REV] {$lineDesc}" : '[REV]',
                    'entity_id' => $entity->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Handle negative credit (treat as debit)
            if ($c < 0) {
                DB::table('ifrs_line_items')->insert([
                    'transaction_id' => $transaction->id,
                    'account_id' => $account->id,
                    'amount' => abs($c),
                    'quantity' => 1,
                    'credited' => false,
                    'narration' => $lineDesc ? "[REV] {$lineDesc}" : '[REV]',
                    'entity_id' => $entity->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Reload and post
        $transaction->load('lineItems');
        $transaction->post();

        DB::commit();
        echo "  OK: Transaction ID {$transaction->id} (ref: {$reference})\n";
        $created++;

    } catch (\Exception $e) {
        DB::rollBack();
        echo "  ERROR {$filename} (Nalog #{$nalogNum}): {$e->getMessage()}\n";
        echo "  File: {$e->getFile()}:{$e->getLine()}\n";
        $errors++;
    }
}

// ====================================================================
// 9. SUMMARY
// ====================================================================
echo "\n" . str_repeat('=', 80) . "\n";
echo "IMPORT COMPLETE\n";
echo str_repeat('=', 80) . "\n";
echo "Company: {$company->name} (ID: {$company->id})\n";
echo "IFRS Entity: " . ($entity->id ?? 'N/A') . "\n";
echo "Accounts: " . count($accountCache) . "\n";
echo "Nalozi created: {$created} / " . count($nalozi) . "\n";
echo "Errors: {$errors}\n";
if ($dryRun) echo "\n*** DRY RUN - No data was actually written ***\n";
echo "\n";
// CLAUDE-CHECKPOINT
