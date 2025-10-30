<?php

namespace Tests\Unit;

use Tests\TestCase;
use Modules\Mk\Services\MkUblMapper;
use App\Models\Invoice;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Currency;
use App\Models\InvoiceItem;
use App\Models\TaxType;
use App\Models\Tax;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Test MkUblMapper for UBL XML generation
 * 
 * Verifies that the mapper can generate valid UBL XML from InvoiceShelf invoices
 * Success criteria: PHPUnit XSD pass
 */
class MkUblMapperTest extends TestCase
{
    use RefreshDatabase;

    protected $mapper;
    protected $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new MkUblMapper();
        $this->createTestInvoice();
    }

    /** @test */
    public function it_can_generate_ubl_xml_from_invoice()
    {
        $xml = $this->mapper->mapInvoiceToUbl($this->invoice);
        
        $this->assertIsString($xml);
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString('Invoice', $xml);
        $this->assertStringContainsString($this->invoice->invoice_number, $xml);
    }

    /** @test */
    public function it_includes_macedonian_specific_information()
    {
        $xml = $this->mapper->mapInvoiceToUbl($this->invoice);
        
        // Should contain Macedonia country code
        $this->assertStringContainsString('MK', $xml);
        
        // Should contain Macedonian currency
        $this->assertStringContainsString('MKD', $xml);
        
        // Should contain Macedonian VAT information
        $this->assertStringContainsString('ДДВ', $xml); // VAT in Macedonian
    }

    /** @test */
    public function it_includes_company_information()
    {
        $xml = $this->mapper->mapInvoiceToUbl($this->invoice);
        
        $this->assertStringContainsString($this->invoice->company->name, $xml);
    }

    /** @test */
    public function it_includes_customer_information()
    {
        $xml = $this->mapper->mapInvoiceToUbl($this->invoice);
        
        $this->assertStringContainsString($this->invoice->customer->name, $xml);
        $this->assertStringContainsString($this->invoice->customer->email, $xml);
    }

    /** @test */
    public function it_includes_invoice_items()
    {
        $xml = $this->mapper->mapInvoiceToUbl($this->invoice);
        
        foreach ($this->invoice->items as $item) {
            $this->assertStringContainsString($item->name, $xml);
        }
    }

    /** @test */
    public function it_includes_tax_information()
    {
        $xml = $this->mapper->mapInvoiceToUbl($this->invoice);
        
        // Should contain VAT tax scheme
        $this->assertStringContainsString('VAT', $xml);
        
        // Should contain tax amounts
        $this->assertStringContainsString((string) $this->invoice->tax_total, $xml);
    }

    /** @test */
    public function it_includes_monetary_totals()
    {
        $xml = $this->mapper->mapInvoiceToUbl($this->invoice);
        
        $this->assertStringContainsString((string) $this->invoice->sub_total, $xml);
        $this->assertStringContainsString((string) $this->invoice->total, $xml);
    }

    /** @test */
    public function it_generates_valid_xml_structure()
    {
        $xml = $this->mapper->mapInvoiceToUbl($this->invoice);
        
        // Test that XML is well-formed
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml), 'Generated XML should be well-formed');
        
        // Test that it contains expected UBL elements
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('ubl', 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2');
        
        $invoiceNodes = $xpath->query('//ubl:Invoice');
        $this->assertGreaterThan(0, $invoiceNodes->length, 'Should contain Invoice element');
    }

    /** @test */
    public function it_provides_macedonian_context_information()
    {
        $context = $this->mapper->getMacedonianContext();
        
        $this->assertEquals('MK', $context['country_code']);
        $this->assertEquals('MKD', $context['currency']);
        $this->assertEquals(18, $context['standard_vat_rate']);
        $this->assertEquals(5, $context['reduced_vat_rate']);
        $this->assertEquals('ДДВ', $context['tax_scheme_name']);
    }

    /**
     * Create a test invoice with typical Macedonian business data
     */
    protected function createTestInvoice()
    {
        // Create currency
        $currency = Currency::factory()->create([
            'name' => 'Macedonian Denar',
            'code' => 'MKD',
            'symbol' => 'ден'
        ]);

        // Create company (supplier)
        $company = Company::factory()->create([
            'name' => 'Тест Компанија ДОО',
            'vat_number' => 'MK4030009501234'
        ]);

        // Create customer
        $customer = Customer::factory()->create([
            'name' => 'Клиент Тест',
            'email' => 'klient@test.mk',
            'phone' => '+389 2 123 456',
            'company_id' => $company->id
        ]);

        // Create tax types (Macedonian VAT rates)
        $standardVat = TaxType::factory()->create([
            'name' => 'ДДВ 18%',
            'percent' => 18,
            'compound_tax' => false,
            'company_id' => $company->id
        ]);

        $reducedVat = TaxType::factory()->create([
            'name' => 'ДДВ 5%',
            'percent' => 5,
            'compound_tax' => false,
            'company_id' => $company->id
        ]);

        // Create invoice
        $this->invoice = Invoice::factory()->create([
            'invoice_number' => 'INV-2025-001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'currency_id' => $currency->id,
            'sub_total' => 10000, // 100.00 MKD
            'tax_total' => 1800,   // 18.00 MKD
            'total' => 11800,      // 118.00 MKD
            'status' => 'SENT',
            'notes' => 'Тест фактура за UBL генерирање'
        ]);

        // Create invoice items
        $item1 = InvoiceItem::factory()->create([
            'invoice_id' => $this->invoice->id,
            'name' => 'Производ 1',
            'description' => 'Опис на производот',
            'quantity' => 1,
            'price' => 5000, // 50.00 MKD
            'total' => 5000
        ]);

        $item2 = InvoiceItem::factory()->create([
            'invoice_id' => $this->invoice->id,
            'name' => 'Услуга 1',
            'description' => 'Опис на услугата',
            'quantity' => 2,
            'price' => 2500, // 25.00 MKD
            'total' => 5000
        ]);

        // Create taxes for items
        Tax::factory()->create([
            'invoice_id' => $this->invoice->id,
            'item_id' => $item1->id,
            'tax_type_id' => $standardVat->id,
            'name' => 'ДДВ 18%',
            'amount' => 900, // 18% of 50.00
            'percent' => 18
        ]);

        Tax::factory()->create([
            'invoice_id' => $this->invoice->id,
            'item_id' => $item2->id,
            'tax_type_id' => $standardVat->id,
            'name' => 'ДДВ 18%',
            'amount' => 900, // 18% of 50.00
            'percent' => 18
        ]);

        // Refresh invoice to load relationships
        $this->invoice->refresh();
        $this->invoice->load(['company', 'customer', 'items', 'taxes', 'currency']);
    }
}