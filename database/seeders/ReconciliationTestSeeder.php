<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Currency;
use App\Models\Invoice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reconciliation Test Seeder
 *
 * Creates test data for reconciliation testing in production.
 * All test data is clearly prefixed with "RECON-TEST-" for easy identification and cleanup.
 *
 * Usage:
 *   php artisan db:seed --class=ReconciliationTestSeeder
 *
 * Cleanup:
 *   php artisan db:seed --class=ReconciliationTestSeeder -- --cleanup
 *   OR run: php artisan tinker then: (new \Database\Seeders\ReconciliationTestSeeder)->cleanup()
 */
class ReconciliationTestSeeder extends Seeder
{
    protected const TEST_PREFIX = 'RECON-TEST-';
    protected const TEST_CUSTOMER_NAME = 'RECON-TEST-Customer';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check for cleanup flag
        if (in_array('--cleanup', $_SERVER['argv'] ?? [])) {
            $this->cleanup();
            return;
        }

        $this->command?->info('Creating reconciliation test data...');
        Log::info('ReconciliationTestSeeder: Starting test data creation');

        // Get the first company (or specify a test company ID)
        $company = Company::first();
        if (!$company) {
            $this->command?->error('No company found. Please create a company first.');
            return;
        }

        $currency = Currency::where('code', 'MKD')->first();
        if (!$currency) {
            $currency = Currency::first();
        }

        // Create or get test customer
        $customer = $this->getOrCreateTestCustomer($company, $currency);

        // Create or get test bank account
        $bankAccount = $this->getOrCreateTestBankAccount($company, $currency);

        // Clean up any existing test data first
        $this->cleanupTestData($company->id);

        // Create test invoices
        $invoices = $this->createTestInvoices($company, $customer, $currency);

        // Create test bank transactions
        $this->createTestTransactions($company, $bankAccount, $invoices);

        $this->command?->info('Test data created successfully!');
        $this->command?->info('');
        $this->command?->info('Created:');
        $this->command?->info('  - 1 test customer: ' . self::TEST_CUSTOMER_NAME);
        $this->command?->info('  - ' . count($invoices) . ' test invoices (prefix: ' . self::TEST_PREFIX . ')');
        $this->command?->info('  - Multiple test bank transactions');
        $this->command?->info('');
        $this->command?->info('To test reconciliation:');
        $this->command?->info('  1. Go to /admin/banking/reconciliation');
        $this->command?->info('  2. Click "Auto Match" to run the matcher');
        $this->command?->info('  3. Review suggested matches and confidence scores');
        $this->command?->info('');
        $this->command?->info('To cleanup test data:');
        $this->command?->info('  php artisan db:seed --class=ReconciliationTestSeeder -- --cleanup');

        Log::info('ReconciliationTestSeeder: Completed', [
            'company_id' => $company->id,
            'invoices_created' => count($invoices),
        ]);
    }

    /**
     * Get or create test customer
     */
    protected function getOrCreateTestCustomer(Company $company, Currency $currency): Customer
    {
        return Customer::firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => self::TEST_CUSTOMER_NAME,
            ],
            [
                'email' => 'recon-test@example.com',
                'phone' => '+389 70 000 000',
                'currency_id' => $currency->id,
            ]
        );
    }

    /**
     * Get or create test bank account
     */
    protected function getOrCreateTestBankAccount(Company $company, Currency $currency): BankAccount
    {
        $bankAccount = BankAccount::where('company_id', $company->id)->first();

        if (!$bankAccount) {
            $bankAccount = BankAccount::create([
                'company_id' => $company->id,
                'currency_id' => $currency->id,
                'account_name' => self::TEST_PREFIX . 'Bank Account',
                'account_number' => '300000000000000',
                'bank_name' => 'Test Bank',
                'is_active' => true,
            ]);
        }

        return $bankAccount;
    }

    /**
     * Create test invoices with various scenarios
     */
    protected function createTestInvoices(Company $company, Customer $customer, Currency $currency): array
    {
        $invoices = [];
        $scenarios = [
            // Scenario 1: Exact match - same amount, invoice number in description
            ['number' => '001', 'total' => 15000.00, 'due_days' => 5, 'desc' => 'Exact match scenario'],

            // Scenario 2: Exact match - different amount format
            ['number' => '002', 'total' => 8500.50, 'due_days' => 7, 'desc' => 'Decimal amount match'],

            // Scenario 3: Partial reference match
            ['number' => '003', 'total' => 22000.00, 'due_days' => 3, 'desc' => 'Partial reference match'],

            // Scenario 4: Amount only match (no reference)
            ['number' => '004', 'total' => 5000.00, 'due_days' => 10, 'desc' => 'Amount only match'],

            // Scenario 5: Customer name match
            ['number' => '005', 'total' => 12500.00, 'due_days' => 14, 'desc' => 'Customer name match'],

            // Scenario 6: Multiple possible matches (same amount)
            ['number' => '006', 'total' => 7777.00, 'due_days' => 5, 'desc' => 'Multiple match A'],
            ['number' => '007', 'total' => 7777.00, 'due_days' => 8, 'desc' => 'Multiple match B'],

            // Scenario 7: Old invoice (low date score)
            ['number' => '008', 'total' => 3000.00, 'due_days' => -20, 'desc' => 'Old invoice'],

            // Scenario 8: No matching transaction
            ['number' => '009', 'total' => 99999.99, 'due_days' => 5, 'desc' => 'No match expected'],

            // Scenario 10: Small amount
            ['number' => '010', 'total' => 500.00, 'due_days' => 7, 'desc' => 'Small amount match'],
        ];

        foreach ($scenarios as $scenario) {
            $invoice = Invoice::create([
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'currency_id' => $currency->id,
                'invoice_number' => self::TEST_PREFIX . $scenario['number'],
                'status' => 'SENT',
                'paid_status' => 'UNPAID',
                'total' => $scenario['total'],
                'sub_total' => $scenario['total'],
                'due_amount' => $scenario['total'],
                'tax' => 0,
                'discount' => 0,
                'discount_val' => 0,
                'invoice_date' => now(),
                'due_date' => now()->addDays($scenario['due_days']),
                'notes' => $scenario['desc'],
                'unique_hash' => uniqid('test_'),
                'sequence_number' => 9000 + intval($scenario['number']),
            ]);

            $invoices[$scenario['number']] = $invoice;
        }

        return $invoices;
    }

    /**
     * Create test bank transactions
     */
    protected function createTestTransactions(Company $company, BankAccount $bankAccount, array $invoices): void
    {
        $transactions = [
            // Transaction 1: Perfect match for invoice 001 (high confidence expected: ~87%)
            [
                'amount' => 15000.00,
                'description' => 'Payment for ' . self::TEST_PREFIX . '001',
                'date_offset' => 6,
                'reference' => 'TXN-EXACT-001',
                'creditor_name' => null,
            ],

            // Transaction 2: Match for invoice 002 with decimal
            [
                'amount' => 8500.50,
                'description' => 'Bank transfer ' . self::TEST_PREFIX . '002',
                'date_offset' => 8,
                'reference' => 'TXN-DECIMAL-002',
                'creditor_name' => null,
            ],

            // Transaction 3: Partial reference (only last 3 digits)
            [
                'amount' => 22000.00,
                'description' => 'Payment ref 003',
                'date_offset' => 4,
                'reference' => 'TXN-PARTIAL-003',
                'creditor_name' => null,
            ],

            // Transaction 4: Amount only (no invoice reference)
            [
                'amount' => 5000.00,
                'description' => 'Wire transfer received',
                'date_offset' => 11,
                'reference' => 'TXN-AMOUNT-004',
                'creditor_name' => null,
            ],

            // Transaction 5: Customer name in creditor field
            [
                'amount' => 12500.00,
                'description' => 'Incoming payment',
                'date_offset' => 15,
                'reference' => 'TXN-CUSTOMER-005',
                'creditor_name' => self::TEST_CUSTOMER_NAME,
            ],

            // Transaction 6: Matches both 006 and 007 (same amount)
            [
                'amount' => 7777.00,
                'description' => 'Transfer received',
                'date_offset' => 6,
                'reference' => 'TXN-MULTI-006',
                'creditor_name' => null,
            ],

            // Transaction 7: Late payment for old invoice 008
            [
                'amount' => 3000.00,
                'description' => 'Late payment ' . self::TEST_PREFIX . '008',
                'date_offset' => -15,
                'reference' => 'TXN-LATE-008',
                'creditor_name' => null,
            ],

            // Transaction 8: Small amount for invoice 010
            [
                'amount' => 500.00,
                'description' => self::TEST_PREFIX . '010 payment',
                'date_offset' => 8,
                'reference' => 'TXN-SMALL-010',
                'creditor_name' => null,
            ],

            // Transaction 9: No matching invoice (random amount)
            [
                'amount' => 1234.56,
                'description' => 'Unknown transfer',
                'date_offset' => 5,
                'reference' => 'TXN-UNKNOWN-999',
                'creditor_name' => 'Random Company Ltd',
            ],

            // Transaction 10: Already processed (should be skipped)
            [
                'amount' => 9999.00,
                'description' => 'Already matched transaction',
                'date_offset' => 3,
                'reference' => 'TXN-MATCHED-ALREADY',
                'creditor_name' => null,
                'already_matched' => true,
            ],
        ];

        foreach ($transactions as $tx) {
            BankTransaction::create([
                'company_id' => $company->id,
                'bank_account_id' => $bankAccount->id,
                'amount' => $tx['amount'],
                'currency' => 'MKD',
                'description' => $tx['description'],
                'transaction_date' => now()->addDays($tx['date_offset']),
                'external_reference' => $tx['reference'],
                'creditor_name' => $tx['creditor_name'],
                'matched_invoice_id' => isset($tx['already_matched']) ? ($invoices['001']->id ?? null) : null,
                'processing_status' => isset($tx['already_matched']) ? 'processed' : 'pending',
            ]);
        }
    }

    /**
     * Clean up test data for a specific company
     */
    protected function cleanupTestData(int $companyId): void
    {
        // Delete test transactions
        BankTransaction::where('company_id', $companyId)
            ->where('external_reference', 'like', 'TXN-%')
            ->where('description', 'like', '%' . self::TEST_PREFIX . '%')
            ->delete();

        // Delete test invoices
        Invoice::where('company_id', $companyId)
            ->where('invoice_number', 'like', self::TEST_PREFIX . '%')
            ->delete();
    }

    /**
     * Full cleanup - removes all test data
     */
    public function cleanup(): void
    {
        $this->command?->info('Cleaning up reconciliation test data...');
        Log::info('ReconciliationTestSeeder: Starting cleanup');

        DB::transaction(function () {
            // Delete test transactions (all companies)
            $txDeleted = BankTransaction::where('external_reference', 'like', 'TXN-%')
                ->where(function ($q) {
                    $q->where('description', 'like', '%' . self::TEST_PREFIX . '%')
                      ->orWhere('description', 'like', '%Unknown transfer%')
                      ->orWhere('description', 'like', '%Already matched%');
                })
                ->delete();

            // Delete test invoices (all companies)
            $invDeleted = Invoice::where('invoice_number', 'like', self::TEST_PREFIX . '%')
                ->delete();

            // Delete test customers (all companies)
            $custDeleted = Customer::where('name', self::TEST_CUSTOMER_NAME)
                ->delete();

            // Delete test bank accounts
            $baDeleted = BankAccount::where('account_name', 'like', self::TEST_PREFIX . '%')
                ->delete();

            $this->command?->info("Deleted: $txDeleted transactions, $invDeleted invoices, $custDeleted customers, $baDeleted bank accounts");

            Log::info('ReconciliationTestSeeder: Cleanup completed', [
                'transactions_deleted' => $txDeleted,
                'invoices_deleted' => $invDeleted,
                'customers_deleted' => $custDeleted,
            ]);
        });

        $this->command?->info('Cleanup complete!');
    }
}
