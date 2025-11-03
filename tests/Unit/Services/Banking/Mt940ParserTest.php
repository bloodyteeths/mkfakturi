<?php

namespace Tests\Unit\Services\Banking;

use Tests\TestCase;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\Currency;
use App\Services\Banking\Mt940Parser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class Mt940ParserTest extends TestCase
{
    use RefreshDatabase;

    protected Mt940Parser $parser;
    protected BankAccount $account;
    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new Mt940Parser();

        $this->company = Company::factory()->create();

        $currency = Currency::firstOrCreate([
            'code' => 'MKD',
        ], [
            'name' => 'Macedonian Denar',
            'symbol' => 'ден',
            'precision' => 2,
        ]);

        $this->account = BankAccount::create([
            'company_id' => $this->company->id,
            'account_name' => 'Test Account',
            'account_number' => '1234567890',
            'iban' => 'MK07290000000000001',
            'bank_name' => 'Test Bank',
            'bank_code' => 'stopanska',
            'currency_id' => $currency->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function test_parses_mt940_file()
    {
        $mt940Content = $this->getSampleMt940();
        $tempFile = tempnam(sys_get_temp_dir(), 'mt940_');
        file_put_contents($tempFile, $mt940Content);

        $imported = $this->parser->parseFile($tempFile, $this->account);

        $this->assertGreaterThan(0, $imported);
        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $this->account->id,
            'company_id' => $this->company->id,
            'source' => BankTransaction::SOURCE_CSV_IMPORT,
        ]);

        unlink($tempFile);
    }

    /** @test */
    public function test_idempotency_by_reference()
    {
        $mt940Content = $this->getSampleMt940();
        $tempFile = tempnam(sys_get_temp_dir(), 'mt940_');
        file_put_contents($tempFile, $mt940Content);

        // Import once
        $imported1 = $this->parser->parseFile($tempFile, $this->account);

        // Import again (should detect duplicates)
        $imported2 = $this->parser->parseFile($tempFile, $this->account);

        $this->assertEquals(0, $imported2, 'Second import should detect all as duplicates');

        unlink($tempFile);
    }

    /** @test */
    public function test_handles_credit_and_debit()
    {
        $csvContent = "Datum,Referenca,Iznos,Opis,Partner,Smetka\n";
        $csvContent .= "2025-11-01,REF001,1000.00,Income,Customer A,123456\n";
        $csvContent .= "2025-11-02,REF002,-500.00,Expense,Supplier B,789012\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
        file_put_contents($tempFile, $csvContent);

        $imported = $this->parser->parseCsv($tempFile, $this->account);

        $this->assertEquals(2, $imported);

        // Check credit transaction
        $credit = BankTransaction::where('transaction_reference', 'REF001')->first();
        $this->assertNotNull($credit);
        $this->assertEquals(BankTransaction::TYPE_CREDIT, $credit->transaction_type);
        $this->assertEquals(1000.00, $credit->amount);
        $this->assertEquals('Customer A', $credit->debtor_name);

        // Check debit transaction
        $debit = BankTransaction::where('transaction_reference', 'REF002')->first();
        $this->assertNotNull($debit);
        $this->assertEquals(BankTransaction::TYPE_DEBIT, $debit->transaction_type);
        $this->assertEquals(500.00, $debit->amount);
        $this->assertEquals('Supplier B', $debit->creditor_name);

        unlink($tempFile);
    }

    /** @test */
    public function test_parses_csv_with_custom_mapping()
    {
        $csvContent = "Date,Ref,Amount,Desc,Party,Account\n";
        $csvContent .= "2025-11-01,REF003,750.00,Payment,Client C,111222\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
        file_put_contents($tempFile, $csvContent);

        $mapping = [
            'date' => 'Date',
            'reference' => 'Ref',
            'amount' => 'Amount',
            'description' => 'Desc',
            'counterparty' => 'Party',
            'counterparty_account' => 'Account',
        ];

        $imported = $this->parser->parseCsv($tempFile, $this->account, $mapping);

        $this->assertEquals(1, $imported);

        $transaction = BankTransaction::where('transaction_reference', 'REF003')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals(750.00, $transaction->amount);
        $this->assertEquals('Payment', $transaction->description);

        unlink($tempFile);
    }

    /** @test */
    public function test_handles_invalid_file()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('File not found');

        $this->parser->parseFile('/nonexistent/file.mt940', $this->account);
    }

    /** @test */
    public function test_csv_idempotency()
    {
        $csvContent = "Datum,Referenca,Iznos,Opis,Partner,Smetka\n";
        $csvContent .= "2025-11-01,REF004,2000.00,Test,Party D,333444\n";

        $tempFile = tempnam(sys_get_temp_dir(), 'csv_');
        file_put_contents($tempFile, $csvContent);

        // First import
        $imported1 = $this->parser->parseCsv($tempFile, $this->account);
        $this->assertEquals(1, $imported1);

        // Second import (duplicate)
        $imported2 = $this->parser->parseCsv($tempFile, $this->account);
        $this->assertEquals(0, $imported2);

        unlink($tempFile);
    }

    /**
     * Get sample MT940 content for testing
     *
     * @return string
     */
    protected function getSampleMt940(): string
    {
        return ":20:TEST123\n" .
            ":25:MK07290000000000001\n" .
            ":28C:1/1\n" .
            ":60F:C251101MKD10000,00\n" .
            ":61:2511011101DR500,00NTRFNONREF//TEST001\n" .
            ":86:Test Transaction\n" .
            ":62F:C251101MKD9500,00\n";
    }
}

// CLAUDE-CHECKPOINT
