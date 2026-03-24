<?php
/**
 * Import Customers, Suppliers & Bank Accounts for ЕЛЕКТРО МЕ-НА ДООЕЛ (Company 118)
 *
 * Creates:
 *   - 22 customers (from 1200/1220 receivable accounts)
 *   - 37 suppliers (from 2200/2201/2400/2420 payable accounts)
 *   - 6 linked customer↔supplier pairs (appear in both)
 *   - 2 bank accounts (Тутунска банка, Уни Банка)
 *   - 6 employees as suppliers (from 2420 salary payables)
 *
 * Run on Railway: php /var/www/html/logs/import_elektromena_contacts.php [--dry-run]
 * Idempotent: skips existing records by name match.
 */

require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Company;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\BankAccount;
use App\Models\User;
use App\Models\Address;
use Illuminate\Support\Facades\DB;

$dryRun = in_array('--dry-run', $argv ?? []);
echo $dryRun ? "=== DRY RUN MODE ===\n\n" : "=== LIVE IMPORT MODE ===\n\n";

// ─── Company & User ──────────────────────────────────────────────
$company = Company::find(118);
if (!$company) { die("ERROR: Company 118 not found\n"); }
echo "Company: {$company->name} (ID: {$company->id})\n";

// Find the owner/creator user for this company
$creator = User::whereHas('companies', fn($q) => $q->where('companies.id', 118))->first();
if (!$creator) {
    $creator = User::find(2); // fallback to super admin
}
echo "Creator: {$creator->name} (ID: {$creator->id})\n";

// Currency: MKD
$currency = DB::table('currencies')->where('code', 'MKD')->first();
if (!$currency) { die("ERROR: MKD currency not found\n"); }
echo "Currency: MKD (ID: {$currency->id})\n";

// Country: Macedonia (North Macedonia)
$country = DB::table('countries')->where('code', 'MK')->first();
if (!$country) {
    $country = DB::table('countries')->where('name', 'LIKE', '%Macedonia%')->first();
}
$countryId = $country ? $country->id : null;
echo "Country: " . ($country ? "{$country->name} (ID: {$country->id})" : "NOT FOUND") . "\n\n";

// ─── Customers ───────────────────────────────────────────────────
// Partners that appear on account 1200 (receivables) or 1220 (loans receivable)
$customerNames = [
    'ЈОРДАН ГОТТИНО ФООДС',
    'АЛЕКСОВСКИ АРСЕНЧО',
    'ГАЛМЕДИКУС ЦО',
    'ГЕМАК-ТРАДЕ Енвер Доо Скопје',
    'ДЕРВЕНТ-ПРОМЕТ',
    'ДПТУ БОШАВА',
    'ЕУРО-ПРОФИЛ ДООЕЛ СКОПЈЕ',
    'ИНТЕРНОВА ТРАДЕ',
    'МАРТИН СКУМАНОВ',
    'МЕТАЛ СОЛУТИОНС ДОО ПЕТРОВЕЦ',
    'МОНДРИАН ГРУП ДОО',
    'НЕНИ КОНСТРАКШН ДООЕЛ',
    'РИПРОМ ТРАДЕ Сашко ДООЕЛ',
    'СЛАБИНЦК ДООЕЛ',
    'СОЛАР СПЕКТАР АГ ДООЕЛ',
    'СОЛАР-НРГ енерги солутионс',
    'СОРАВИА ИНВЕСТ ДООЕЛ',
    'ТЕМАТО ДОО СКОПЈЕ',
    'ТРГО-ИНЖИЊЕРИНГ ДООЕЛ',
    'ФАСИЛИТИ МЕНАЏМЕНТ СЕРВИЦЕС ДООЕЛ',
    'ФИТ-ИНГ ПАРТНЕР ДООЕЛ',
    'ЧЕКИЌ-КОМПАНИ ДООЕЛ',
];

// ─── Suppliers ───────────────────────────────────────────────────
// Partners that appear on account 2200/2201 (payables) or 2400/2420 (salary payables)
$supplierNames = [
    'А1/ ВИП ОНЕ ОПЕРАТОР',
    'АРИЉЕМЕТАЛ ДОО СКОПЈЕ',
    'БАЛКАНСКИ ДООЕЛ',
    'БОВИК ТОДОРОВСКИ ДООЕЛ',
    'ВАРСА ТРАНС ДООЕЛ',
    'ВИЗИЈА-СОФТВЕР',
    'ГАЛМЕДИКУС ЦО',
    'ДЕЛТА ЕЛЕКТРО Дооел',
    'ЕЛГРАД ГРУП ДООЕЛ',
    'ЕЛГРАД ДООЕЛ',
    'ЕЛЕКТРОНОВА ПС Дооел',
    'ЕЛИВАЛ ДООЕЛ',
    'ЕУРОЛИНК ОСИГУРВ.АД.',
    'ЗОТЕБРОС ДООЕЛ',
    'ИВА-ЏО КОНСАЛТИНГ ДООЕЛ',
    'ЛЕКС-ЕЛЕКТРИК ДООЕЛ',
    'МАК КАБ Дооел',
    'МАКЕДОНСКИ ТЕЛЕКОМ АД Скопје',
    'МАНАСТИР СВ.ЃОРЃИ НЕГОТИНО',
    'МР.БОЛТ',
    'НЕЛМЕД ДОО',
    'НЕОТЕЛ',
    'ОРИОН АИМ ДОО',
    'ПП СЕРВИС Ц-9 Гоце д.о.о.е.л',
    'ПРО-ИНС',
    'РИПРОМ ТРАДЕ Сашко ДООЕЛ',
    'СЕРВИС БОРЧЕ 2 ВИКТОР',
    'СЕРВИС ТЕХНИКА МИТОВСКИ Дооел',
    'СОЛАР СПЕКТАР АГ ДООЕЛ',
    'ТИМ ГРУП 2012 ДОО',
    'ТРАНСЛОГ ДООЕЛ СКОПЈЕ',
    'ТРГО-ИНЖИЊЕРИНГ ДООЕЛ',
    'ТРИМАКС ДОО',
    'ФИТ-ИНГ ПАРТНЕР ДООЕЛ',
    'ЧЕКИЌ-КОМПАНИ ДООЕЛ',
    'елмонд ин дооел',
    // Employees (from 2420 salary payables)
    'Ѓорги Тодоров',
    'Ивана Нацев',
    'Методи Нацев',
    'ПЕТРЕ СТАНКОВ',
    'АЛЕКСОВСКИ АРСЕНЧО',
    'МАРТИН СКУМАНОВ',
];

// Names that appear as both customer AND supplier (will be linked)
$bothNames = [
    'ГАЛМЕДИКУС ЦО',
    'РИПРОМ ТРАДЕ Сашко ДООЕЛ',
    'СОЛАР СПЕКТАР АГ ДООЕЛ',
    'ТРГО-ИНЖИЊЕРИНГ ДООЕЛ',
    'ФИТ-ИНГ ПАРТНЕР ДООЕЛ',
    'ЧЕКИЌ-КОМПАНИ ДООЕЛ',
];

// ─── Create Customers ────────────────────────────────────────────
echo "=== CUSTOMERS ===\n";
$customersCreated = 0;
$customersSkipped = 0;
$customerMap = []; // name => Customer model (for linking)

foreach ($customerNames as $name) {
    $name = trim($name);
    $existing = Customer::where('company_id', 118)->where('name', $name)->first();
    if ($existing) {
        echo "  SKIP (exists): {$name} (ID: {$existing->id})\n";
        $customersSkipped++;
        $customerMap[$name] = $existing;
        continue;
    }

    if ($dryRun) {
        echo "  WOULD CREATE: {$name}\n";
        $customersCreated++;
        continue;
    }

    $customer = Customer::create([
        'name' => $name,
        'company_id' => $company->id,
        'currency_id' => $currency->id,
        'creator_id' => $creator->id,
        'enable_portal' => false,
    ]);

    // Create billing address (minimal — just country)
    Address::create([
        'customer_id' => $customer->id,
        'company_id' => $company->id,
        'country_id' => $countryId,
        'type' => 'billing',
    ]);

    echo "  CREATED: {$name} (ID: {$customer->id})\n";
    $customerMap[$name] = $customer;
    $customersCreated++;
}

echo "Customers: {$customersCreated} created, {$customersSkipped} skipped\n\n";

// ─── Create Suppliers ────────────────────────────────────────────
echo "=== SUPPLIERS ===\n";
$suppliersCreated = 0;
$suppliersSkipped = 0;
$supplierMap = []; // name => Supplier model (for linking)

// Deduplicate supplier names
$uniqueSuppliers = array_unique($supplierNames);

foreach ($uniqueSuppliers as $name) {
    $name = trim($name);
    $existing = Supplier::where('company_id', 118)->where('name', $name)->first();
    if ($existing) {
        echo "  SKIP (exists): {$name} (ID: {$existing->id})\n";
        $suppliersSkipped++;
        $supplierMap[$name] = $existing;
        continue;
    }

    if ($dryRun) {
        echo "  WOULD CREATE: {$name}\n";
        $suppliersCreated++;
        continue;
    }

    $supplier = Supplier::create([
        'name' => $name,
        'company_id' => $company->id,
        'country_id' => $countryId,
        'creator_id' => $creator->id,
    ]);

    echo "  CREATED: {$name} (ID: {$supplier->id})\n";
    $supplierMap[$name] = $supplier;
    $suppliersCreated++;
}

echo "Suppliers: {$suppliersCreated} created, {$suppliersSkipped} skipped\n\n";

// ─── Link Customer ↔ Supplier pairs ─────────────────────────────
echo "=== LINKING CUSTOMER ↔ SUPPLIER ===\n";
$linked = 0;

if (!$dryRun) {
    foreach ($bothNames as $name) {
        $customer = $customerMap[$name] ?? null;
        $supplier = $supplierMap[$name] ?? null;

        if (!$customer || !$supplier) {
            echo "  SKIP LINK: {$name} — missing customer or supplier record\n";
            continue;
        }

        if ($customer->linked_supplier_id) {
            echo "  ALREADY LINKED: {$name}\n";
            continue;
        }

        $customer->linked_supplier_id = $supplier->id;
        $customer->save();
        echo "  LINKED: {$name} (Customer {$customer->id} ↔ Supplier {$supplier->id})\n";
        $linked++;
    }
} else {
    foreach ($bothNames as $name) {
        echo "  WOULD LINK: {$name}\n";
        $linked++;
    }
}

echo "Linked: {$linked}\n\n";

// ─── Bank Accounts ───────────────────────────────────────────────
echo "=== BANK ACCOUNTS ===\n";

$banks = [
    [
        'bank_name' => 'Тутунска Банка АД Скопје',
        'account_name' => 'Тутунска Банка — Основна сметка',
        'account_number' => '210-0000000000-000', // placeholder — Ivana can update with real number
        'account_type' => 'business',
        'notes' => 'IFRS account 3001. Imported from legacy system Q1 2026.',
    ],
    [
        'bank_name' => 'Уни Банка АД Скопје',
        'account_name' => 'Уни Банка — Основна сметка',
        'account_number' => '240-0000000000-000', // placeholder
        'account_type' => 'business',
        'notes' => 'IFRS account 4001. Imported from legacy system Q1 2026. Overdraft facility.',
    ],
];

$banksCreated = 0;
$banksSkipped = 0;

foreach ($banks as $bankData) {
    $existing = BankAccount::where('company_id', 118)
        ->where('bank_name', $bankData['bank_name'])
        ->first();

    if ($existing) {
        echo "  SKIP (exists): {$bankData['bank_name']} (ID: {$existing->id})\n";
        $banksSkipped++;
        continue;
    }

    if ($dryRun) {
        echo "  WOULD CREATE: {$bankData['bank_name']}\n";
        $banksCreated++;
        continue;
    }

    $isPrimary = BankAccount::where('company_id', 118)->count() === 0;

    $account = BankAccount::create([
        'company_id' => $company->id,
        'bank_name' => $bankData['bank_name'],
        'account_name' => $bankData['account_name'],
        'account_number' => $bankData['account_number'],
        'currency_id' => $currency->id,
        'currency' => 'MKD',
        'account_type' => $bankData['account_type'],
        'opening_balance' => 0,
        'current_balance' => 0,
        'is_primary' => $isPrimary,
        'is_active' => true,
        'status' => 'active',
        'notes' => $bankData['notes'],
    ]);

    echo "  CREATED: {$bankData['bank_name']} (ID: {$account->id})\n";
    $banksCreated++;
}

echo "Bank accounts: {$banksCreated} created, {$banksSkipped} skipped\n\n";

// ─── Summary ─────────────────────────────────────────────────────
echo "=== IMPORT COMPLETE ===\n";
echo "Company: {$company->name} (ID: {$company->id})\n";
echo "Customers: {$customersCreated} created, {$customersSkipped} existed\n";
echo "Suppliers: {$suppliersCreated} created, {$suppliersSkipped} existed\n";
echo "Customer↔Supplier links: {$linked}\n";
echo "Bank accounts: {$banksCreated} created, {$banksSkipped} existed\n";

if ($dryRun) {
    echo "\n*** DRY RUN — nothing was written to the database ***\n";
}

// CLAUDE-CHECKPOINT
