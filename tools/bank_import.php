<?php

/**
 * SD-03: Bank Transaction Import Helper Script
 *
 * This script loads bank CSV files from /samples/bank/ into the bank_transactions table.
 * It creates sample bank accounts for Stopanska Bank and NLB Bank, then imports
 * the transaction data with proper mapping and validation.
 *
 * Usage:
 *   php tools/bank_import.php [--company=1] [--force]
 *
 * Options:
 *   --company=ID   Specify company ID (default: 1)
 *   --force        Skip confirmation prompts
 *
 * Features:
 * - Creates sample bank accounts for Stopanska and NLB banks
 * - Imports transaction data from CSV files
 * - Maps transactions to existing invoices where possible
 * - Provides detailed audit and validation
 * - Supports rollback on failure
 */

require_once __DIR__.'/../vendor/autoload.php';

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

// Boot Laravel application
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

class BankTransactionImporter
{
    private int $companyId;

    private bool $force;

    private array $csvFiles;

    private array $bankAccounts = [];

    private array $importStats = [];

    private int $mkdCurrencyId;

    public function __construct(array $options = [])
    {
        $this->companyId = $options['company'] ?? 1;
        $this->force = $options['force'] ?? false;

        $this->csvFiles = [
            'stopanska' => 'samples/bank/stopanska_bank_transactions.csv',
            'nlb' => 'samples/bank/nlb_bank_transactions.csv',
        ];
    }

    /**
     * Main import execution method
     */
    public function import(): bool
    {
        try {
            $this->printHeader();

            if (! $this->validatePrerequisites()) {
                return false;
            }

            if (! $this->confirmImport()) {
                $this->output('Import cancelled by user.');

                return false;
            }

            $this->output("Starting bank transaction import...\n");

            // Step 1: Create bank accounts
            $this->createBankAccounts();

            // Step 2: Import transactions
            $this->importTransactions();

            // Step 3: Audit results
            $this->auditResults();

            $this->printSummary();

            return true;

        } catch (Exception $e) {
            $this->output('ERROR: '.$e->getMessage());
            $this->output('Rolling back imports...');
            $this->rollbackImports();

            return false;
        }
    }

    /**
     * Create sample bank accounts
     */
    private function createBankAccounts(): void
    {
        $this->output('Creating sample bank accounts...');

        // Find MKD currency
        $this->mkdCurrencyId = Currency::where('code', 'MKD')->first()->id ?? 1;

        // Create Stopanska Bank account
        $stopanskaAccount = BankAccount::updateOrCreate(
            [
                'company_id' => $this->companyId,
                'account_number' => '250-0009876543-21',
            ],
            [
                'account_name' => 'Главна сметка - Стопанска Банка',
                'iban' => 'MK07250009876543210',
                'swift_code' => 'STBAMK22',
                'bank_name' => 'Стопанска Банка АД',
                'bank_code' => '250',
                'branch' => 'Централа - Скопје',
                'account_type' => 'business',
                'currency_id' => $this->mkdCurrencyId,
                'opening_balance' => 150000.00,
                'current_balance' => 150000.00,
                'is_primary' => true,
                'is_active' => true,
                'notes' => 'Главна деловна сметка за ТехноСолуции ДООЕЛ во Стопанска Банка',
            ]
        );

        $this->bankAccounts['stopanska'] = $stopanskaAccount;
        $this->output("✓ Created Stopanska Bank account: {$stopanskaAccount->account_number}");

        // Create NLB Bank account
        $nlbAccount = BankAccount::updateOrCreate(
            [
                'company_id' => $this->companyId,
                'account_number' => '300-0009876543-21',
            ],
            [
                'account_name' => 'Резервна сметка - НЛБ Банка',
                'iban' => 'MK07300009876543210',
                'swift_code' => 'NLBMK22',
                'bank_name' => 'НЛБ Банка АД',
                'bank_code' => '300',
                'branch' => 'Централа - Скопје',
                'account_type' => 'business',
                'currency_id' => $this->mkdCurrencyId,
                'opening_balance' => 200000.00,
                'current_balance' => 200000.00,
                'is_primary' => false,
                'is_active' => true,
                'notes' => 'Резервна деловна сметка за ТехноСолуции ДООЕЛ во НЛБ Банка',
            ]
        );

        $this->bankAccounts['nlb'] = $nlbAccount;
        $this->output("✓ Created NLB Bank account: {$nlbAccount->account_number}");
    }

    /**
     * Import transactions from CSV files
     */
    private function importTransactions(): void
    {
        foreach ($this->csvFiles as $bankName => $filePath) {
            $this->output("\nImporting {$bankName} transactions...");

            $fullPath = base_path($filePath);
            if (! file_exists($fullPath)) {
                throw new Exception("File not found: {$fullPath}");
            }

            $bankAccount = $this->bankAccounts[$bankName];
            $transactions = $this->parseCsvFile($fullPath);

            $imported = 0;
            $matched = 0;

            foreach ($transactions as $transactionData) {
                try {
                    $transaction = $this->createBankTransaction($bankAccount, $transactionData);
                    $imported++;

                    // Try to match with existing invoices
                    if ($this->matchTransaction($transaction)) {
                        $matched++;
                    }

                } catch (Exception $e) {
                    $this->output('  WARNING: Failed to import transaction: '.$e->getMessage());
                }
            }

            $this->importStats[$bankName] = [
                'total' => count($transactions),
                'imported' => $imported,
                'matched' => $matched,
            ];

            $this->output("✓ Imported {$imported}/{$transactions->count()} {$bankName} transactions ({$matched} matched)");
        }
    }

    /**
     * Parse CSV file into collection
     */
    private function parseCsvFile(string $filePath)
    {
        $transactions = collect();

        if (($handle = fopen($filePath, 'r')) !== false) {
            $headers = fgetcsv($handle);

            while (($data = fgetcsv($handle)) !== false) {
                $transaction = array_combine($headers, $data);
                $transactions->push($transaction);
            }

            fclose($handle);
        }

        return $transactions;
    }

    /**
     * Create bank transaction record
     */
    private function createBankTransaction(BankAccount $bankAccount, array $data): BankTransaction
    {
        return BankTransaction::create([
            'bank_account_id' => $bankAccount->id,
            'company_id' => $this->companyId,
            'external_reference' => $data['external_reference'],
            'transaction_reference' => $data['transaction_id'],
            'transaction_id' => $data['transaction_id'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'transaction_type' => $data['transaction_type'],
            'booking_status' => $data['booking_status'],
            'transaction_date' => Carbon::parse($data['transaction_date']),
            'booking_date' => Carbon::parse($data['booking_date']),
            'description' => $data['description'],
            'remittance_info' => $data['remittance_info'],
            'payment_reference' => $data['payment_reference'] ?? null,
            'debtor_name' => $data['debtor_name'] ?? null,
            'debtor_account' => $data['debtor_account'] ?? null,
            'creditor_name' => $data['creditor_name'] ?? null,
            'creditor_account' => $data['creditor_account'] ?? null,
            'processing_status' => BankTransaction::STATUS_UNPROCESSED,
            'source' => BankTransaction::SOURCE_CSV_IMPORT,
            'raw_data' => $data,
        ]);
    }

    /**
     * Try to match transaction with existing invoices
     */
    private function matchTransaction(BankTransaction $transaction): bool
    {
        // Extract invoice number from remittance info
        if (preg_match('/МК-\d{4}-\d{3}/', $transaction->remittance_info, $matches)) {
            $invoiceNumber = $matches[0];

            $invoice = Invoice::where('company_id', $this->companyId)
                ->where('invoice_number', $invoiceNumber)
                ->first();

            if ($invoice && abs($transaction->amount) == $invoice->total) {
                $transaction->update([
                    'matched_invoice_id' => $invoice->id,
                    'matched_at' => now(),
                    'match_confidence' => 95.0,
                    'processing_status' => BankTransaction::STATUS_PROCESSED,
                    'processed_at' => now(),
                ]);

                return true;
            }
        }

        return false;
    }

    /**
     * Validate prerequisites for import
     */
    private function validatePrerequisites(): bool
    {
        $this->output('Validating prerequisites...');

        // Check if company exists
        $company = Company::find($this->companyId);
        if (! $company) {
            $this->output("ERROR: Company with ID {$this->companyId} not found.");

            return false;
        }

        // Check if CSV files exist
        foreach ($this->csvFiles as $bankName => $filePath) {
            $fullPath = base_path($filePath);
            if (! file_exists($fullPath)) {
                $this->output("ERROR: Required file not found: {$fullPath}");

                return false;
            }
        }

        // Check if MKD currency exists
        if (! Currency::where('code', 'MKD')->exists()) {
            $this->output('ERROR: MKD currency not found in database.');

            return false;
        }

        // Check database connection
        try {
            DB::connection()->getPdo();
        } catch (Exception $e) {
            $this->output('ERROR: Database connection failed: '.$e->getMessage());

            return false;
        }

        $this->output('✓ All prerequisites validated');

        return true;
    }

    /**
     * Confirm import with user
     */
    private function confirmImport(): bool
    {
        if ($this->force) {
            return true;
        }

        $this->output("\nImport Configuration:");
        $this->output("  Company ID: {$this->companyId}");
        $this->output('  Banks: Stopanska Bank, NLB Bank');

        foreach ($this->csvFiles as $bankName => $filePath) {
            $fullPath = base_path($filePath);
            $lines = count(file($fullPath)) - 1; // Subtract header
            $this->output("    - {$bankName}: {$lines} transactions");
        }

        $this->output("\nThis will create bank accounts and import transaction data.");
        $confirm = readline('Continue? (y/N): ');

        return strtolower(trim($confirm)) === 'y';
    }

    /**
     * Audit import results
     */
    private function auditResults(): void
    {
        $this->output("\nAuditing import results...");

        $totalTransactions = BankTransaction::where('company_id', $this->companyId)->count();
        $matchedTransactions = BankTransaction::where('company_id', $this->companyId)
            ->whereNotNull('matched_invoice_id')
            ->count();
        $creditTransactions = BankTransaction::where('company_id', $this->companyId)
            ->where('amount', '>', 0)
            ->count();
        $debitTransactions = BankTransaction::where('company_id', $this->companyId)
            ->where('amount', '<', 0)
            ->count();

        $this->output("✓ Total transactions imported: {$totalTransactions}");
        $this->output("✓ Matched with invoices: {$matchedTransactions}");
        $this->output("✓ Credit transactions (incoming): {$creditTransactions}");
        $this->output("✓ Debit transactions (outgoing): {$debitTransactions}");

        // Test specific transaction
        $sampleTransaction = BankTransaction::where('company_id', $this->companyId)
            ->where('external_reference', 'LIKE', '%STB-2024071601%')
            ->first();

        if ($sampleTransaction) {
            $this->output('✓ Sample transaction validated (Stopanska payment found)');
        } else {
            $this->output('⚠ Sample transaction validation failed');
        }

        // Test bank account creation
        $stopanskAccount = BankAccount::where('company_id', $this->companyId)
            ->where('bank_code', '250')
            ->first();

        if ($stopanskAccount) {
            $this->output('✓ Stopanska Bank account created successfully');
        }

        $nlbAccount = BankAccount::where('company_id', $this->companyId)
            ->where('bank_code', '300')
            ->first();

        if ($nlbAccount) {
            $this->output('✓ NLB Bank account created successfully');
        }
    }

    /**
     * Rollback imports on failure
     */
    private function rollbackImports(): void
    {
        $this->output('Rolling back imported data...');

        try {
            DB::beginTransaction();

            // Delete imported bank transactions
            BankTransaction::where('company_id', $this->companyId)
                ->where('source', BankTransaction::SOURCE_CSV_IMPORT)
                ->delete();

            // Delete created bank accounts (if they didn't exist before)
            BankAccount::where('company_id', $this->companyId)
                ->whereIn('bank_code', ['250', '300'])
                ->delete();

            DB::commit();
            $this->output('✓ Rollback completed');

        } catch (Exception $e) {
            DB::rollback();
            $this->output('ERROR: Rollback failed: '.$e->getMessage());
        }
    }

    /**
     * Print import summary
     */
    private function printSummary(): void
    {
        $this->output("\n".str_repeat('=', 60));
        $this->output('BANK IMPORT SUMMARY');
        $this->output(str_repeat('=', 60));

        $totalImported = 0;
        $totalMatched = 0;

        foreach ($this->importStats as $bankName => $stats) {
            $this->output(sprintf(
                '%-15s: %3d imported, %3d matched (%.1f%% match rate)',
                ucfirst($bankName),
                $stats['imported'],
                $stats['matched'],
                $stats['imported'] > 0 ? ($stats['matched'] / $stats['imported']) * 100 : 0
            ));

            $totalImported += $stats['imported'];
            $totalMatched += $stats['matched'];
        }

        $this->output(str_repeat('-', 60));
        $this->output(sprintf(
            '%-15s: %3d imported, %3d matched (%.1f%% match rate)',
            'TOTAL',
            $totalImported,
            $totalMatched,
            $totalImported > 0 ? ($totalMatched / $totalImported) * 100 : 0
        ));

        $this->output("\n✓ Bank transaction data imported successfully!");
        $this->output('  Sample transactions from Stopanska and NLB banks are now in the system.');
        $this->output('  Automatic invoice matching has been applied where possible.');
    }

    /**
     * Print script header
     */
    private function printHeader(): void
    {
        $this->output(str_repeat('=', 60));
        $this->output('FACTURINO - Bank Transaction Import (SD-03)');
        $this->output('Macedonia Banking Data - Stopanska & NLB');
        $this->output(str_repeat('=', 60));
    }

    /**
     * Output message to console
     */
    private function output(string $message): void
    {
        echo $message.PHP_EOL;
    }
}

// Parse command line arguments
$options = [];
$args = array_slice($argv, 1);

foreach ($args as $arg) {
    if (str_starts_with($arg, '--company=')) {
        $options['company'] = (int) substr($arg, 10);
    } elseif ($arg === '--force') {
        $options['force'] = true;
    } elseif ($arg === '--help' || $arg === '-h') {
        echo "Usage: php tools/bank_import.php [--company=1] [--force]\n";
        echo "\n";
        echo "Options:\n";
        echo "  --company=ID   Specify company ID (default: 1)\n";
        echo "  --force        Skip confirmation prompts\n";
        echo "  --help, -h     Show this help message\n";
        echo "\n";
        echo "This script imports bank transaction data from /samples/bank/\n";
        echo "and creates sample bank accounts for Stopanska and NLB banks.\n";
        exit(0);
    }
}

// Execute import
$importer = new BankTransactionImporter($options);
$success = $importer->import();

exit($success ? 0 : 1);
