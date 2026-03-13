<?php

namespace Tests\Feature;

use App\Jobs\ProcessClientDocumentJob;
use App\Models\Bill;
use App\Models\ClientDocument;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\User;
use App\Notifications\DocumentProcessedNotification;
use App\Services\InvoiceParsing\Invoice2DataServiceException;
use App\Services\InvoiceParsing\InvoiceParserClient;
use App\Services\InvoiceParsing\ParsedInvoiceMapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProcessClientDocumentJobTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'super admin']);
        $this->company = Company::factory()->create(['owner_id' => $this->user->id]);

        // Set company currency
        $currency = Currency::where('code', 'MKD')->first();
        if (! $currency) {
            $currency = Currency::create(['name' => 'Macedonian Denar', 'code' => 'MKD', 'symbol' => 'ден.', 'precision' => 2, 'thousand_separator' => '.', 'decimal_separator' => ',']);
        }
        CompanySetting::setSettings(['currency' => $currency->id], $this->company->id);
    }

    /** @test */
    public function it_classifies_and_extracts_invoice_document()
    {
        Notification::fake();
        Storage::fake('public');
        Storage::disk('public')->put('test/invoice.pdf', 'fake-pdf-content');

        $doc = ClientDocument::create([
            'company_id' => $this->company->id,
            'uploaded_by' => $this->user->id,
            'category' => 'other',
            'original_filename' => 'invoice.pdf',
            'file_path' => 'test/invoice.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'status' => ClientDocument::STATUS_PENDING,
            'processing_status' => ClientDocument::PROCESSING_PENDING,
        ]);

        $mockClient = $this->mock(InvoiceParserClient::class);
        $mockClient->shouldReceive('classify')
            ->once()
            ->andReturn([
                'type' => 'invoice',
                'confidence' => 0.95,
                'summary' => 'Test invoice from Supplier',
            ]);

        $mockClient->shouldReceive('parse')
            ->once()
            ->andReturn([
                'supplier' => ['name' => 'Test Supplier', 'tax_id' => 'MK123'],
                'invoice' => ['number' => 'INV-001', 'date' => '2026-01-15', 'currency' => 'MKD'],
                'totals' => ['total' => 11800, 'subtotal' => 10000, 'tax' => 1800],
                'line_items' => [
                    ['name' => 'Service', 'quantity' => 1, 'unit_price' => 10000, 'tax' => 1800, 'total' => 11800],
                ],
            ]);

        $job = new ProcessClientDocumentJob($doc->id);
        $job->handle($mockClient, app(ParsedInvoiceMapper::class));

        $doc->refresh();

        $this->assertEquals(ClientDocument::PROCESSING_EXTRACTED, $doc->processing_status);
        $this->assertEquals('invoice', $doc->ai_classification['type']);
        $this->assertEquals(0.95, $doc->ai_classification['confidence']);
        $this->assertNotNull($doc->extracted_data);
        $this->assertEquals('gemini_vision', $doc->extraction_method);
        $this->assertEquals('invoice', $doc->category); // Category overridden by AI
    }

    /** @test */
    public function it_classifies_contract_without_extraction()
    {
        Notification::fake();
        Storage::fake('public');
        Storage::disk('public')->put('test/contract.pdf', 'fake-pdf-content');

        $doc = ClientDocument::create([
            'company_id' => $this->company->id,
            'uploaded_by' => $this->user->id,
            'category' => 'other',
            'original_filename' => 'contract.pdf',
            'file_path' => 'test/contract.pdf',
            'file_size' => 2048,
            'mime_type' => 'application/pdf',
            'status' => ClientDocument::STATUS_PENDING,
            'processing_status' => ClientDocument::PROCESSING_PENDING,
        ]);

        $mockClient = $this->mock(InvoiceParserClient::class);
        $mockClient->shouldReceive('classify')
            ->once()
            ->andReturn([
                'type' => 'contract',
                'confidence' => 0.88,
                'summary' => 'Employment contract',
            ]);

        $job = new ProcessClientDocumentJob($doc->id);
        $job->handle($mockClient, app(ParsedInvoiceMapper::class));

        $doc->refresh();

        $this->assertEquals(ClientDocument::PROCESSING_EXTRACTED, $doc->processing_status);
        $this->assertEquals('contract', $doc->ai_classification['type']);
        $this->assertEquals('classification_only', $doc->extraction_method);
    }

    /** @test */
    public function it_handles_service_unavailable_gracefully()
    {
        Queue::fake();
        Storage::fake('public');
        Storage::disk('public')->put('test/doc.pdf', 'fake-pdf-content');

        $doc = ClientDocument::create([
            'company_id' => $this->company->id,
            'uploaded_by' => $this->user->id,
            'category' => 'other',
            'original_filename' => 'doc.pdf',
            'file_path' => 'test/doc.pdf',
            'file_size' => 512,
            'mime_type' => 'application/pdf',
            'status' => ClientDocument::STATUS_PENDING,
            'processing_status' => ClientDocument::PROCESSING_PENDING,
        ]);

        $mockClient = $this->mock(InvoiceParserClient::class);
        $mockClient->shouldReceive('classify')
            ->once()
            ->andThrow(new Invoice2DataServiceException('Service unavailable', 503));

        $job = new ProcessClientDocumentJob($doc->id);

        // Create a partial mock to test release behavior
        $jobMock = $this->getMockBuilder(ProcessClientDocumentJob::class)
            ->setConstructorArgs([$doc->id])
            ->onlyMethods(['release', 'attempts'])
            ->getMock();

        $jobMock->method('attempts')->willReturn(1);
        $jobMock->expects($this->once())->method('release');

        $jobMock->handle($mockClient, app(ParsedInvoiceMapper::class));

        $doc->refresh();
        $this->assertEquals(ClientDocument::PROCESSING_FAILED, $doc->processing_status);
        $this->assertNotNull($doc->error_message);
    }

    /** @test */
    public function it_skips_already_processed_documents()
    {
        Storage::fake('public');

        $doc = ClientDocument::create([
            'company_id' => $this->company->id,
            'uploaded_by' => $this->user->id,
            'category' => 'invoice',
            'original_filename' => 'already-done.pdf',
            'file_path' => 'test/done.pdf',
            'file_size' => 512,
            'mime_type' => 'application/pdf',
            'status' => ClientDocument::STATUS_REVIEWED,
            'processing_status' => ClientDocument::PROCESSING_EXTRACTED,
        ]);

        $mockClient = $this->mock(InvoiceParserClient::class);
        $mockClient->shouldNotReceive('classify');
        $mockClient->shouldNotReceive('parse');

        $job = new ProcessClientDocumentJob($doc->id);
        $job->handle($mockClient, app(ParsedInvoiceMapper::class));

        // Should remain unchanged
        $doc->refresh();
        $this->assertEquals(ClientDocument::PROCESSING_EXTRACTED, $doc->processing_status);
    }

    /** @test */
    public function confirm_endpoint_creates_bill_from_extracted_document()
    {
        Notification::fake();
        Storage::fake('public');
        Storage::disk('public')->put('test/confirmed.pdf', 'fake-content');

        $currency = Currency::where('code', 'MKD')->first()
            ?? Currency::create(['name' => 'MKD', 'code' => 'MKD', 'symbol' => 'ден.', 'precision' => 2, 'thousand_separator' => '.', 'decimal_separator' => ',']);

        $doc = ClientDocument::create([
            'company_id' => $this->company->id,
            'uploaded_by' => $this->user->id,
            'category' => 'invoice',
            'original_filename' => 'confirmed.pdf',
            'file_path' => 'test/confirmed.pdf',
            'file_size' => 1024,
            'mime_type' => 'application/pdf',
            'status' => ClientDocument::STATUS_PENDING,
            'processing_status' => ClientDocument::PROCESSING_EXTRACTED,
            'ai_classification' => ['type' => 'invoice', 'confidence' => 0.95, 'summary' => 'Test'],
            'extracted_data' => [
                'supplier' => ['name' => 'Confirm Supplier', 'tax_id' => 'MK555'],
                'bill' => [
                    'bill_number' => 'CONF-001',
                    'bill_date' => '2026-03-01',
                    'due_date' => '2026-04-01',
                    'currency_id' => $currency->id,
                    'sub_total' => 10000,
                    'total' => 11800,
                    'tax' => 1800,
                    'due_amount' => 11800,
                    'exchange_rate' => 1,
                    'base_total' => 11800,
                    'base_sub_total' => 10000,
                    'base_tax' => 1800,
                    'base_due_amount' => 11800,
                    'base_discount_val' => 0,
                    'discount' => 0,
                    'discount_val' => 0,
                ],
                'items' => [
                    [
                        'name' => 'Service',
                        'quantity' => 1,
                        'price' => 10000,
                        'tax' => 1800,
                        'total' => 11800,
                        'discount' => 0,
                        'discount_val' => 0,
                        'base_price' => 10000,
                        'base_total' => 11800,
                        'base_tax' => 1800,
                        'base_discount_val' => 0,
                    ],
                ],
            ],
        ]);

        $this->actingAs($this->user, 'sanctum');

        $response = $this->withHeaders(['company' => (string) $this->company->id])
            ->postJson("/api/v1/client-documents/{$doc->id}/confirm");

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $this->assertNotNull($response->json('data.bill_id'));

        $doc->refresh();
        $this->assertEquals(ClientDocument::PROCESSING_CONFIRMED, $doc->processing_status);
        $this->assertNotNull($doc->linked_bill_id);

        $bill = Bill::find($doc->linked_bill_id);
        $this->assertNotNull($bill);
        $this->assertEquals('CONF-001', $bill->bill_number);
        $this->assertEquals(11800, $bill->total);
    }

    /** @test */
    public function reprocess_endpoint_resets_and_dispatches_job()
    {
        Queue::fake();

        $doc = ClientDocument::create([
            'company_id' => $this->company->id,
            'uploaded_by' => $this->user->id,
            'category' => 'invoice',
            'original_filename' => 'reprocess.pdf',
            'file_path' => 'test/reprocess.pdf',
            'file_size' => 512,
            'mime_type' => 'application/pdf',
            'status' => ClientDocument::STATUS_PENDING,
            'processing_status' => ClientDocument::PROCESSING_FAILED,
            'ai_classification' => ['type' => 'other', 'confidence' => 0.5],
            'extracted_data' => ['old' => 'data'],
            'error_message' => 'Previous error',
        ]);

        $this->actingAs($this->user, 'sanctum');

        $response = $this->withHeaders(['company' => (string) $this->company->id])
            ->postJson("/api/v1/client-documents/{$doc->id}/reprocess");

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $doc->refresh();
        $this->assertEquals(ClientDocument::PROCESSING_PENDING, $doc->processing_status);
        $this->assertNull($doc->ai_classification);
        $this->assertNull($doc->extracted_data);
        $this->assertNull($doc->error_message);

        Queue::assertPushed(ProcessClientDocumentJob::class, function ($job) use ($doc) {
            return $job->documentId === $doc->id;
        });
    }

    /** @test */
    public function processing_status_endpoint_returns_current_state()
    {
        $doc = ClientDocument::create([
            'company_id' => $this->company->id,
            'uploaded_by' => $this->user->id,
            'category' => 'invoice',
            'original_filename' => 'status.pdf',
            'file_path' => 'test/status.pdf',
            'file_size' => 512,
            'mime_type' => 'application/pdf',
            'status' => ClientDocument::STATUS_PENDING,
            'processing_status' => ClientDocument::PROCESSING_CLASSIFYING,
            'ai_classification' => null,
        ]);

        $this->actingAs($this->user, 'sanctum');

        $response = $this->withHeaders(['company' => (string) $this->company->id])
            ->getJson("/api/v1/client-documents/{$doc->id}/processing-status");

        $response->assertOk();
        $response->assertJsonPath('data.processing_status', 'classifying');
        $response->assertJsonPath('data.has_extracted_data', false);
    }

    /** @test */
    public function confirm_saves_contract_as_document()
    {
        $doc = ClientDocument::create([
            'company_id' => $this->company->id,
            'uploaded_by' => $this->user->id,
            'category' => 'contract',
            'original_filename' => 'contract.pdf',
            'file_path' => 'test/contract.pdf',
            'file_size' => 512,
            'mime_type' => 'application/pdf',
            'status' => ClientDocument::STATUS_PENDING,
            'processing_status' => ClientDocument::PROCESSING_EXTRACTED,
            'ai_classification' => ['type' => 'contract', 'confidence' => 0.9],
            'extracted_data' => ['summary' => 'A contract'],
        ]);

        $this->actingAs($this->user, 'sanctum');

        $response = $this->withHeaders(['company' => (string) $this->company->id])
            ->postJson("/api/v1/client-documents/{$doc->id}/confirm");

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.entity_type', 'contract');

        $doc->refresh();
        $this->assertEquals(ClientDocument::PROCESSING_CONFIRMED, $doc->processing_status);
        $this->assertEquals(ClientDocument::STATUS_REVIEWED, $doc->status);
    }
} // CLAUDE-CHECKPOINT
