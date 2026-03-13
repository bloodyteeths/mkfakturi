<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Bill;
use App\Models\ClientDocument;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DocumentConfirmationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentConfirmationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected Currency $currency;

    protected DocumentConfirmationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'super admin']);
        $this->company = Company::factory()->create(['owner_id' => $this->user->id]);

        $this->currency = Currency::where('code', 'MKD')->first();
        if (! $this->currency) {
            $this->currency = Currency::create([
                'name' => 'Macedonian Denar',
                'code' => 'MKD',
                'symbol' => 'ден.',
                'precision' => 2,
                'thousand_separator' => '.',
                'decimal_separator' => ',',
            ]);
        }
        CompanySetting::setSettings(['currency' => $this->currency->id], $this->company->id);

        $this->actingAs($this->user);
        $this->service = app(DocumentConfirmationService::class);

        Storage::fake('public');
    }

    private function createExtractedDocument(string $category, array $extractedData): ClientDocument
    {
        Storage::disk('public')->put('test/doc.pdf', 'fake-pdf-content');

        return ClientDocument::create([
            'company_id' => $this->company->id,
            'uploaded_by' => $this->user->id,
            'category' => $category,
            'original_filename' => 'test-document.pdf',
            'file_path' => 'test/doc.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'status' => ClientDocument::STATUS_PENDING,
            'processing_status' => ClientDocument::PROCESSING_EXTRACTED,
            'ai_classification' => ['type' => $category, 'confidence' => 0.95, 'summary' => 'Test document'],
            'extracted_data' => $extractedData,
        ]);
    }

    /** @test */
    public function confirm_as_bill_creates_bill_with_supplier_and_items()
    {
        $doc = $this->createExtractedDocument('invoice', [
            'supplier' => ['name' => 'ДРВО ТРЕЈД ДООЕЛ', 'tax_id' => 'MK4030996116740'],
            'bill' => [
                'bill_number' => 'Ф-2026/001',
                'bill_date' => '2026-03-01',
                'due_date' => '2026-04-01',
                'currency_id' => $this->currency->id,
                'sub_total' => 1000000, // 10,000.00 MKD in cents
                'tax' => 180000,        // 1,800.00 MKD in cents
                'total' => 1180000,     // 11,800.00 MKD in cents
                'due_amount' => 1180000,
                'exchange_rate' => 1,
                'discount' => 0,
                'discount_val' => 0,
            ],
            'items' => [
                [
                    'name' => 'Книговодствени услуги',
                    'quantity' => 1,
                    'price' => 1000000,
                    'tax' => 180000,
                    'total' => 1180000,
                    'discount' => 0,
                    'discount_val' => 0,
                ],
            ],
        ]);

        $result = $this->service->confirmAsBill($doc, $doc->extracted_data, $this->company->id);

        // Bill created
        $this->assertArrayHasKey('bill_id', $result);
        $this->assertArrayHasKey('bill_number', $result);
        $bill = Bill::find($result['bill_id']);
        $this->assertNotNull($bill);
        $this->assertEquals('Ф-2026/001', $bill->bill_number);
        $this->assertEquals(1180000, $bill->total);

        // Supplier created
        $supplier = Supplier::where('company_id', $this->company->id)
            ->where('name', 'ДРВО ТРЕЈД ДООЕЛ')
            ->first();
        $this->assertNotNull($supplier);
        $this->assertEquals('MK4030996116740', $supplier->tax_id);

        // Items created
        $this->assertEquals(1, $bill->items()->count());
        $this->assertEquals('Книговодствени услуги', $bill->items()->first()->name);

        // Document linked
        $doc->refresh();
        $this->assertEquals($bill->id, $doc->linked_bill_id);
    }

    /** @test */
    public function confirm_as_expense_creates_expense_with_receipt()
    {
        $doc = $this->createExtractedDocument('receipt', [
            'supplier' => ['name' => 'ТИНЕКС МТ Скопје', 'tax_id' => 'MK1234567'],
            'expense' => [
                'expense_date' => '2026-03-10',
                'category' => 'Канцелариски материјали',
                'amount' => 35000, // 350.00 MKD in cents
                'notes' => 'Набавка на тонер',
            ],
        ]);

        $result = $this->service->confirmAsExpense($doc, $doc->extracted_data, $this->company->id);

        // Expense created
        $this->assertArrayHasKey('expense_id', $result);
        $expense = Expense::find($result['expense_id']);
        $this->assertNotNull($expense);
        $this->assertEquals(35000, $expense->amount);
        $this->assertEquals('2026-03-10', (string) $expense->expense_date);

        // Category created
        $category = ExpenseCategory::where('company_id', $this->company->id)
            ->where('name', 'Канцелариски материјали')
            ->first();
        $this->assertNotNull($category);
        $this->assertEquals($category->id, $expense->expense_category_id);

        // Supplier linked
        $supplier = Supplier::where('company_id', $this->company->id)
            ->where('name', 'ТИНЕКС МТ Скопје')
            ->first();
        $this->assertNotNull($supplier);
        $this->assertEquals($supplier->id, $expense->supplier_id);

        // Document linked
        $doc->refresh();
        $this->assertEquals($expense->id, $doc->linked_expense_id);
    }

    /** @test */
    public function confirm_as_expense_falls_back_to_bill_data()
    {
        // When AI classifies receipt but extraction puts data in bill format
        $doc = $this->createExtractedDocument('receipt', [
            'supplier' => ['name' => 'ЛУКОИЛ'],
            'bill' => [
                'bill_date' => '2026-03-05',
                'total' => 78500, // 785.00 MKD
            ],
        ]);

        $result = $this->service->confirmAsExpense($doc, $doc->extracted_data, $this->company->id);

        $expense = Expense::find($result['expense_id']);
        $this->assertNotNull($expense);
        $this->assertEquals(78500, $expense->amount);
        $this->assertEquals('2026-03-05', (string) $expense->expense_date);
    }

    /** @test */
    public function confirm_as_invoice_creates_outgoing_invoice_with_customer()
    {
        $doc = $this->createExtractedDocument('invoice', [
            'customer' => ['name' => 'Факторино Клиент ДООЕЛ', 'email' => 'client@example.com', 'phone' => '071234567'],
            'invoice' => [
                'invoice_number' => 'ФА-2026/042',
                'invoice_date' => '2026-03-01',
                'due_date' => '2026-03-31',
                'sub_total' => 500000,  // 5,000.00 MKD
                'tax' => 90000,         // 900.00 MKD
                'total' => 590000,      // 5,900.00 MKD
            ],
            'items' => [
                [
                    'name' => 'Веб дизајн',
                    'quantity' => 1,
                    'price' => 500000,
                    'tax' => 90000,
                    'total' => 590000,
                    'discount' => 0,
                    'discount_val' => 0,
                ],
            ],
        ]);

        $result = $this->service->confirmAsInvoice($doc, $doc->extracted_data, $this->company->id);

        // Invoice created
        $this->assertArrayHasKey('invoice_id', $result);
        $this->assertArrayHasKey('invoice_number', $result);
        $invoice = Invoice::find($result['invoice_id']);
        $this->assertNotNull($invoice);
        $this->assertEquals(590000, $invoice->total);
        $this->assertEquals(590000, $invoice->due_amount);
        $this->assertNotEmpty($invoice->invoice_number);

        // Customer created
        $customer = Customer::where('company_id', $this->company->id)
            ->where('name', 'Факторино Клиент ДООЕЛ')
            ->first();
        $this->assertNotNull($customer);
        $this->assertEquals('client@example.com', $customer->email);
        $this->assertEquals($customer->id, $invoice->customer_id);

        // Items created
        $this->assertEquals(1, $invoice->items()->count());
        $this->assertEquals('Веб дизајн', $invoice->items()->first()->name);

        // Serial number set
        $this->assertNotNull($invoice->sequence_number);
        $this->assertNotNull($invoice->unique_hash);

        // Document linked
        $doc->refresh();
        $this->assertEquals($invoice->id, $doc->linked_invoice_id);
    }

    /** @test */
    public function confirm_as_invoice_generates_number_when_missing()
    {
        $doc = $this->createExtractedDocument('invoice', [
            'customer' => ['name' => 'Auto Number Client'],
            'invoice' => [
                'invoice_date' => '2026-03-01',
                'due_date' => '2026-03-31',
                'sub_total' => 100000,
                'tax' => 0,
                'total' => 100000,
            ],
            'items' => [],
        ]);

        $result = $this->service->confirmAsInvoice($doc, $doc->extracted_data, $this->company->id);

        $invoice = Invoice::find($result['invoice_id']);
        $this->assertNotNull($invoice);
        $this->assertNotEmpty($invoice->invoice_number); // Auto-generated by SerialNumberFormatter
    }

    /** @test */
    public function confirm_as_bank_transactions_imports_with_dedup()
    {
        $bankAccount = BankAccount::factory()->create([
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
        ]);

        $doc = $this->createExtractedDocument('bank_statement', [
            'bank_account_id' => $bankAccount->id,
            'transactions' => [
                [
                    'date' => '2026-03-01',
                    'counterparty_name' => 'ТИНЕКС МТ Скопје',
                    'counterparty_account' => '2100007123456789',
                    'debit' => 2500000, // 25,000.00 MKD in cents
                    'credit' => 0,
                    'description' => 'Набавка стока',
                    'reference' => '123456',
                ],
                [
                    'date' => '2026-03-02',
                    'counterparty_name' => 'АД ЕЛЕМ Скопје',
                    'counterparty_account' => '3000001234567890',
                    'debit' => 0,
                    'credit' => 5000000, // 50,000.00 MKD in cents
                    'description' => 'Уплата по фактура',
                    'reference' => '789012',
                ],
            ],
        ]);

        $result = $this->service->confirmAsBankTransactions($doc, $doc->extracted_data, $this->company->id);

        // Transactions created
        $this->assertArrayHasKey('created', $result);
        $this->assertEquals(2, $result['created']);
        $this->assertEquals(0, $result['duplicates']);

        // Verify amounts stored correctly (cents)
        $txns = BankTransaction::where('company_id', $this->company->id)
            ->where('bank_account_id', $bankAccount->id)
            ->orderBy('transaction_date')
            ->get();
        $this->assertEquals(2, $txns->count());
        $this->assertEquals(-2500000, $txns[0]->amount); // Debit = negative
        $this->assertEquals(5000000, $txns[1]->amount);  // Credit = positive

        // Summary stored in document
        $doc->refresh();
        $this->assertNotNull($doc->extracted_data['imported_transaction_ids'] ?? null);
    }

    /** @test */
    public function confirm_as_bank_transactions_requires_bank_account_id()
    {
        $doc = $this->createExtractedDocument('bank_statement', [
            'transactions' => [
                ['date' => '2026-03-01', 'debit' => 100000, 'credit' => 0],
            ],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('bank_account_id is required');

        $this->service->confirmAsBankTransactions($doc, $doc->extracted_data, $this->company->id);
    }

    /** @test */
    public function confirm_as_items_creates_products_without_double_conversion()
    {
        $doc = $this->createExtractedDocument('product_list', [
            'products' => [
                [
                    'name' => 'Тонер HP LaserJet',
                    'code' => 'TON-HP-001',
                    'unit' => 'ком',
                    'unit_price' => 250000, // 2,500.00 MKD in cents (from FastAPI * 100)
                    'barcode' => '5901234567890',
                ],
                [
                    'name' => 'А4 хартија 500л',
                    'code' => 'PAP-A4-500',
                    'unit' => 'ком',
                    'unit_price' => 32000, // 320.00 MKD in cents
                    'barcode' => null,
                ],
                [
                    'name' => '', // Empty name — should be skipped
                    'code' => 'SKIP-ME',
                    'unit_price' => 10000,
                ],
            ],
            'currency' => 'MKD',
        ]);

        $result = $this->service->confirmAsItems($doc, $doc->extracted_data, $this->company->id);

        // 2 created, 1 skipped (empty name)
        $this->assertEquals(2, $result['created']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertCount(2, $result['item_ids']);

        // Verify prices stored correctly (NO double-conversion)
        $toner = Item::where('company_id', $this->company->id)
            ->where('sku', 'TON-HP-001')
            ->first();
        $this->assertNotNull($toner);
        $this->assertEquals(250000, $toner->price); // 2500.00 MKD in cents — NOT 25000000
        $this->assertEquals('Тонер HP LaserJet', $toner->name);
        $this->assertEquals('5901234567890', $toner->barcode);

        $paper = Item::where('company_id', $this->company->id)
            ->where('sku', 'PAP-A4-500')
            ->first();
        $this->assertNotNull($paper);
        $this->assertEquals(32000, $paper->price); // 320.00 MKD in cents

        // Imported IDs stored in document
        $doc->refresh();
        $this->assertNotNull($doc->extracted_data['imported_item_ids'] ?? null);
        $this->assertCount(2, $doc->extracted_data['imported_item_ids']);
    }

    /** @test */
    public function confirm_as_items_deduplicates_by_sku()
    {
        // Pre-create an item with same SKU
        Item::create([
            'name' => 'Existing Toner',
            'sku' => 'TON-HP-001',
            'price' => 200000,
            'company_id' => $this->company->id,
            'currency_id' => $this->currency->id,
            'creator_id' => $this->user->id,
        ]);

        $doc = $this->createExtractedDocument('product_list', [
            'products' => [
                [
                    'name' => 'Тонер HP LaserJet',
                    'code' => 'TON-HP-001', // Same SKU — should be skipped
                    'unit_price' => 250000,
                ],
                [
                    'name' => 'Нов производ',
                    'code' => 'NEW-001',
                    'unit_price' => 50000,
                ],
            ],
            'currency' => 'MKD',
        ]);

        $result = $this->service->confirmAsItems($doc, $doc->extracted_data, $this->company->id);

        $this->assertEquals(1, $result['created']);  // Only the new one
        $this->assertEquals(1, $result['skipped']); // Existing SKU skipped
    }

    /** @test */
    public function confirm_as_tax_form_saves_data_and_preserves_type()
    {
        $doc = $this->createExtractedDocument('tax_form', [
            'form_type' => 'DDV-04',
            'declarant' => ['name' => 'Факторино ДООЕЛ', 'tax_id' => 'MK4030996116740'],
            'period' => ['year' => 2026, 'month' => 2, 'quarter' => null],
            'fields' => ['Ред 1' => '500000', 'Ред 2' => '90000', 'Ред 3' => '410000'],
            'totals' => ['total_income' => 500000, 'total_tax' => 90000, 'total_to_pay' => 90000],
        ]);

        // Simulate user editing the data (correcting OCR errors)
        $editedData = [
            'form_type' => 'DDV-04',
            'declarant' => ['name' => 'Факторино ДООЕЛ', 'tax_id' => 'MK4030996116740', 'address' => 'Бул. Партизански Одреди 17'],
            'period' => ['year' => 2026, 'month' => 2, 'quarter' => null],
            'fields' => ['Ред 1' => '510000', 'Ред 2' => '91800', 'Ред 3' => '418200'], // Corrected
            'totals' => ['total_income' => 510000, 'total_tax' => 91800, 'total_to_pay' => 91800],
        ];

        $result = $this->service->confirmAsDocument($doc, $editedData);

        $this->assertArrayHasKey('document_id', $result);
        $this->assertTrue($result['saved']);

        // Data saved correctly with type preserved
        $doc->refresh();
        $this->assertEquals('tax_form', $doc->extracted_data['type']); // Type preserved!
        $this->assertEquals('DDV-04', $doc->extracted_data['form_type']);
        $this->assertEquals('510000', $doc->extracted_data['fields']['Ред 1']); // Edited value saved
        $this->assertEquals('Бул. Партизански Одреди 17', $doc->extracted_data['declarant']['address']);
    }

    /** @test */
    public function confirm_as_contract_saves_data_and_preserves_type()
    {
        $doc = $this->createExtractedDocument('contract', [
            'summary' => 'Договор за книговодствени услуги',
            'type' => 'contract',
        ]);

        $editedData = [
            'summary' => 'Договор за книговодствени услуги помеѓу Факторино и Клиент',
            'parties' => [
                ['name' => 'Факторино ДООЕЛ', 'role' => 'Давател'],
                ['name' => 'Клиент ДООЕЛ', 'role' => 'Примател'],
            ],
            'dates' => ['start' => '2026-01-01', 'end' => '2026-12-31'],
            'amounts' => ['value' => '240000', 'currency' => 'MKD'],
            'notes' => 'Месечна претплата 20,000 ден',
        ];

        $result = $this->service->confirmAsDocument($doc, $editedData);

        $this->assertTrue($result['saved']);

        $doc->refresh();
        $this->assertEquals('contract', $doc->extracted_data['type']); // Type preserved!
        $this->assertStringContainsString('Клиент', $doc->extracted_data['summary']);
        $this->assertCount(2, $doc->extracted_data['parties']);
        $this->assertEquals('2026-01-01', $doc->extracted_data['dates']['start']);
        $this->assertEquals('Месечна претплата 20,000 ден', $doc->extracted_data['notes']);
    }

    /** @test */
    public function confirm_via_endpoint_routes_to_correct_entity_type()
    {
        // Test that the controller confirm endpoint correctly routes each entity type
        $this->actingAs($this->user, 'sanctum');

        // Test expense via endpoint
        $doc = $this->createExtractedDocument('receipt', [
            'supplier' => ['name' => 'Endpoint Test Supplier'],
            'expense' => [
                'expense_date' => '2026-03-10',
                'category' => 'Test Category',
                'amount' => 50000,
            ],
        ]);

        $response = $this->withHeaders(['company' => (string) $this->company->id])
            ->postJson("/api/v1/client-documents/{$doc->id}/confirm", [
                'entity_type' => 'expense',
                'extracted_data' => $doc->extracted_data,
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.entity_type', 'expense');
        $this->assertNotNull($response->json('data.expense_id'));

        $doc->refresh();
        $this->assertEquals(ClientDocument::PROCESSING_CONFIRMED, $doc->processing_status);
        $this->assertEquals(ClientDocument::STATUS_REVIEWED, $doc->status);
        $this->assertNotNull($doc->linked_expense_id);
    }

    /** @test */
    public function confirm_via_endpoint_routes_items_correctly()
    {
        $this->actingAs($this->user, 'sanctum');

        $doc = $this->createExtractedDocument('product_list', [
            'products' => [
                ['name' => 'Test Product', 'code' => 'TP-001', 'unit_price' => 150000],
            ],
            'currency' => 'MKD',
        ]);

        $response = $this->withHeaders(['company' => (string) $this->company->id])
            ->postJson("/api/v1/client-documents/{$doc->id}/confirm", [
                'entity_type' => 'items',
                'extracted_data' => $doc->extracted_data,
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.entity_type', 'items');
        $this->assertEquals(1, $response->json('data.created'));

        // Verify price is correct (no double-conversion)
        $item = Item::where('company_id', $this->company->id)
            ->where('sku', 'TP-001')
            ->first();
        $this->assertNotNull($item);
        $this->assertEquals(150000, $item->price); // 1500.00 MKD in cents
    }

    /** @test */
    public function confirm_via_endpoint_routes_tax_form_correctly()
    {
        $this->actingAs($this->user, 'sanctum');

        $doc = $this->createExtractedDocument('tax_form', [
            'form_type' => 'Obrazec-36',
            'declarant' => ['name' => 'Test Company'],
            'fields' => ['Field 1' => '100'],
            'totals' => ['total_tax' => 1000],
        ]);

        $response = $this->withHeaders(['company' => (string) $this->company->id])
            ->postJson("/api/v1/client-documents/{$doc->id}/confirm", [
                'entity_type' => 'tax_form',
                'extracted_data' => [
                    'form_type' => 'Obrazec-36',
                    'declarant' => ['name' => 'Test Company Edited'],
                    'fields' => ['Field 1' => '200'],
                    'totals' => ['total_tax' => 2000],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.entity_type', 'tax_form');

        // Verify edited data saved with type
        $doc->refresh();
        $this->assertEquals(ClientDocument::PROCESSING_CONFIRMED, $doc->processing_status);
        $this->assertEquals('tax_form', $doc->extracted_data['type']);
        $this->assertEquals('Test Company Edited', $doc->extracted_data['declarant']['name']);
    }
} // CLAUDE-CHECKPOINT
