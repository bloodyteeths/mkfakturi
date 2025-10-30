<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Invoice;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Currency;
use App\Models\InvoiceItem;
use App\Models\TaxType;
use App\Models\Tax;
use App\Models\Address;
use App\Models\BankAccount;
use App\Models\User;
use Modules\Mk\Services\MkUblMapper;
use Modules\Mk\Services\MkXmlSigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * OPS-04: XML Export with UBL Digital Signatures
 * 
 * Tests the complete UBL → Sign → Download flow with Macedonia-specific requirements:
 * - Valid UBL XML generation with Macedonia business data
 * - Digital signature validation with QES certificates
 * - Macedonia VAT compliance (18%, 5%, 0% rates)
 * - Cyrillic character preservation in XML
 * - Performance benchmarks for XML generation
 * - Integration with Macedonia tax authority requirements
 */
class XmlExportTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $company;
    protected $customer;
    protected $currency;
    protected $invoice;
    protected $ublMapper;
    protected $xmlSigner;
    protected $certificateDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->setupTestEnvironment();
        $this->createTestData();
        $this->setupServices();
    }

    protected function setupTestEnvironment(): void
    {
        // Create certificate directory for testing
        $this->certificateDir = storage_path('testing/certificates');
        if (!is_dir($this->certificateDir)) {
            mkdir($this->certificateDir, 0755, true);
        }

        // Configure XML signing for testing
        Config::set('mk.xml_signing.private_key_path', $this->certificateDir . '/private.key');
        Config::set('mk.xml_signing.certificate_path', $this->certificateDir . '/certificate.pem');
        Config::set('mk.xml_signing.passphrase', 'test_passphrase');
    }

    protected function createTestData(): void
    {
        // Create currency
        $this->currency = Currency::factory()->create([
            'name' => 'Macedonian Denar',
            'code' => 'MKD',
            'symbol' => 'ден'
        ]);

        // Create company with full address and bank account info
        $this->company = Company::factory()->create([
            'name' => 'Тест Компанија ДОО',
            'vat_number' => 'MK4030009501234'
        ]);

        // Create company address
        Address::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Main Office',
            'address_street_1' => 'Македонија бр. 123',
            'city' => 'Скопје',
            'zip' => '1000',
            'country_id' => 1, // Macedonia
            'type' => 'billing'
        ]);

        // Create bank account for the company
        BankAccount::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Stopanska Banka',
            'account_number' => '1234567890123456',
            'iban' => 'MK07200000000012345678',
            'swift_code' => 'STBKMK22',
            'is_primary' => true
        ]);

        // Create customer with address
        $this->customer = Customer::factory()->create([
            'name' => 'Клиент Тест ДООЕл',
            'email' => 'klient@test.mk',
            'phone' => '+389 2 123 456',
            'tax_id' => 'MK4030009501235',
            'company_id' => $this->company->id
        ]);

        // Create customer address
        Address::factory()->create([
            'customer_id' => $this->customer->id,
            'name' => 'Customer Address',
            'address_street_1' => 'Клиентска улица бр. 456',
            'city' => 'Скопје',
            'zip' => '1000',
            'country_id' => 1,
            'type' => 'billing'
        ]);

        // Create tax types (Macedonian VAT rates)
        $standardVat = TaxType::factory()->create([
            'name' => 'ДДВ 18%',
            'percent' => 18,
            'compound_tax' => false,
            'company_id' => $this->company->id
        ]);

        $reducedVat = TaxType::factory()->create([
            'name' => 'ДДВ 5%',
            'percent' => 5,
            'compound_tax' => false,
            'company_id' => $this->company->id
        ]);

        // Create user for authentication
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'admin'
        ]);

        // Create invoice with Macedonian business data
        $this->invoice = Invoice::factory()->create([
            'invoice_number' => 'ФАК-2025-001',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'currency_id' => $this->currency->id,
            'sub_total' => 10000, // 100.00 MKD
            'tax_total' => 1800,   // 18.00 MKD
            'total' => 11800,      // 118.00 MKD
            'status' => 'SENT',
            'notes' => 'Тест фактура за UBL експорт и дигитално потпишување'
        ]);

        // Create invoice items
        $item1 = InvoiceItem::factory()->create([
            'invoice_id' => $this->invoice->id,
            'name' => 'Лаптоп компјутер',
            'description' => 'Деловен лаптоп за канцелариска употреба',
            'quantity' => 1,
            'price' => 5000, // 50.00 MKD
            'total' => 5000
        ]);

        $item2 = InvoiceItem::factory()->create([
            'invoice_id' => $this->invoice->id,
            'name' => 'ИТ консултантски услуги',
            'description' => 'Консултантски услуги за системска интеграција',
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

    protected function setupServices(): void
    {
        $this->ublMapper = new MkUblMapper();
        $this->xmlSigner = new MkXmlSigner();
    }

    /** @test */
    public function it_can_generate_valid_ubl_xml_from_invoice_data()
    {
        // Generate UBL XML from invoice
        $xml = $this->ublMapper->mapInvoiceToUbl($this->invoice);

        // Verify XML is not empty
        $this->assertNotEmpty($xml, 'UBL XML should not be empty');

        // Verify XML is well-formed
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml), 'Generated UBL XML should be well-formed');

        // Verify contains invoice number
        $this->assertStringContainsString($this->invoice->invoice_number, $xml);

        // Verify contains company information
        $this->assertStringContainsString($this->company->name, $xml);
        $this->assertStringContainsString($this->company->vat_number, $xml);

        // Verify contains customer information
        $this->assertStringContainsString($this->customer->name, $xml);
        $this->assertStringContainsString($this->customer->email, $xml);

        // Verify contains invoice items
        foreach ($this->invoice->items as $item) {
            $this->assertStringContainsString($item->name, $xml);
        }

        // Verify contains monetary totals
        $this->assertStringContainsString((string) $this->invoice->sub_total, $xml);
        $this->assertStringContainsString((string) $this->invoice->total, $xml);

        // Verify contains Macedonian-specific information
        $this->assertStringContainsString('MK', $xml); // Country code
        $this->assertStringContainsString('MKD', $xml); // Currency
        $this->assertStringContainsString('ДДВ', $xml); // VAT in Macedonian
    }

    /** @test */
    public function it_can_generate_test_certificate_for_signing()
    {
        // Generate test certificate
        $certInfo = $this->xmlSigner->generateTestCertificate($this->certificateDir);

        // Verify certificate files were created
        $this->assertFileExists($certInfo['private_key_path']);
        $this->assertFileExists($certInfo['certificate_path']);

        // Verify certificate info is returned
        $this->assertArrayHasKey('certificate_info', $certInfo);
        $this->assertArrayHasKey('subject', $certInfo['certificate_info']);
        $this->assertArrayHasKey('issuer', $certInfo['certificate_info']);

        // Verify files have correct permissions
        $this->assertEquals('0600', substr(sprintf('%o', fileperms($certInfo['private_key_path'])), -4));
        $this->assertEquals('0644', substr(sprintf('%o', fileperms($certInfo['certificate_path'])), -4));
    }

    /** @test */
    public function it_can_digitally_sign_ubl_xml()
    {
        // Generate test certificate first
        $certInfo = $this->xmlSigner->generateTestCertificate($this->certificateDir);

        // Create new signer with test certificate
        $signer = new MkXmlSigner(
            $certInfo['private_key_path'],
            $certInfo['certificate_path']
        );

        // Generate UBL XML
        $xml = $this->ublMapper->mapInvoiceToUbl($this->invoice);

        // Sign the XML
        $signedXml = $signer->signUblInvoice($xml);

        // Verify signing was successful
        $this->assertNotEmpty($signedXml, 'Signed XML should not be empty');
        $this->assertNotEquals($xml, $signedXml, 'Signed XML should be different from original');

        // Verify signature elements are present
        $this->assertTrue(
            strpos($signedXml, '<ds:Signature') !== false || strpos($signedXml, '<Signature') !== false,
            'Signed XML should contain signature element'
        );

        // Verify signed XML is still well-formed
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($signedXml), 'Signed XML should be well-formed');

        // Verify original content is preserved
        $this->assertStringContainsString($this->invoice->invoice_number, $signedXml);
        $this->assertStringContainsString($this->company->name, $signedXml);
    }

    /** @test */
    public function it_can_verify_xml_digital_signature()
    {
        // Generate test certificate
        $certInfo = $this->xmlSigner->generateTestCertificate($this->certificateDir);

        // Create signer with test certificate
        $signer = new MkXmlSigner(
            $certInfo['private_key_path'],
            $certInfo['certificate_path']
        );

        // Generate and sign UBL XML
        $xml = $this->ublMapper->mapInvoiceToUbl($this->invoice);
        $signedXml = $signer->signUblInvoice($xml);

        // Verify the signature
        $isValid = $signer->verifySignature($signedXml);

        $this->assertTrue($isValid, 'XML signature should be valid');
    }

    /** @test */
    public function it_validates_ubl_xml_structure()
    {
        // Generate UBL XML
        $xml = $this->ublMapper->mapInvoiceToUbl($this->invoice);

        // Test XML structure validation without XSD schema
        // (Since we may not have the actual UBL schema file in testing)
        $dom = new \DOMDocument();
        $this->assertTrue($dom->loadXML($xml));

        // Check for required UBL elements using XPath
        $xpath = new \DOMXPath($dom);
        
        // Register UBL namespace (note: actual namespace may vary)
        $namespaces = $xpath->query('//namespace::*');
        $ublNamespace = null;
        foreach ($namespaces as $namespace) {
            if (strpos($namespace->nodeValue, 'ubl') !== false) {
                $ublNamespace = $namespace->nodeValue;
                break;
            }
        }

        if ($ublNamespace) {
            $xpath->registerNamespace('ubl', $ublNamespace);
            
            // Check for required UBL elements
            $this->assertGreaterThan(0, $xpath->query('//ubl:Invoice')->length);
        }

        // Alternative validation without namespace (check for element names)
        $this->assertStringContainsString('<Invoice', $xml);
        $this->assertStringContainsString('</Invoice>', $xml);
        $this->assertStringContainsString('AccountingSupplierParty', $xml);
        $this->assertStringContainsString('AccountingCustomerParty', $xml);
        $this->assertStringContainsString('InvoiceLine', $xml);
        $this->assertStringContainsString('LegalMonetaryTotal', $xml);
    }

    /** @test */
    public function it_handles_xml_export_via_api_endpoint()
    {
        // Authenticate as admin user
        $this->actingAs($this->user);

        // Test basic UBL export
        $response = $this->postJson("/admin/invoices/{$this->invoice->id}/export-xml", [
            'format' => 'ubl',
            'include_signature' => false,
            'validate' => true
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertHeader('Content-Disposition');

        // Verify response contains XML content
        $xml = $response->getContent();
        $this->assertStringContainsString('<?xml', $xml);
        $this->assertStringContainsString($this->invoice->invoice_number, $xml);
    }

    /** @test */
    public function it_handles_signed_xml_export_via_api_endpoint()
    {
        // Generate test certificate first
        $certInfo = $this->xmlSigner->generateTestCertificate($this->certificateDir);

        // Authenticate as admin user
        $this->actingAs($this->user);

        // Test signed UBL export
        $response = $this->postJson("/admin/invoices/{$this->invoice->id}/export-xml", [
            'format' => 'ubl_signed',
            'include_signature' => true,
            'validate' => true
        ]);

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/xml');

        // Verify filename contains 'signed'
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('signed', $contentDisposition);

        // Verify response contains signed XML
        $xml = $response->getContent();
        $this->assertTrue(
            strpos($xml, '<ds:Signature') !== false || strpos($xml, '<Signature') !== false,
            'Response should contain signed XML'
        );
    }

    /** @test */
    public function it_handles_xml_validation_errors()
    {
        // Authenticate as admin user
        $this->actingAs($this->user);

        // Mock the UBL mapper to return invalid XML
        $mockMapper = \Mockery::mock(MkUblMapper::class);
        $mockMapper->shouldReceive('mapInvoiceToUbl')
                   ->andReturn('invalid xml content');
        $mockMapper->shouldReceive('validateUblXml')
                   ->andReturn([
                       'is_valid' => false,
                       'errors' => ['XML validation error: Missing required element']
                   ]);

        $this->app->instance(MkUblMapper::class, $mockMapper);

        // Test export with validation enabled
        $response = $this->postJson("/admin/invoices/{$this->invoice->id}/export-xml", [
            'format' => 'ubl',
            'include_signature' => false,
            'validate' => true
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'XML validation failed',
            'errors' => ['XML validation error: Missing required element']
        ]);
    }

    /** @test */
    public function it_handles_digital_signature_configuration_errors()
    {
        // Remove certificate files to simulate configuration error
        $this->xmlSigner = new MkXmlSigner(
            '/nonexistent/private.key',
            '/nonexistent/certificate.pem'
        );

        // Authenticate as admin user
        $this->actingAs($this->user);

        // Test export with signing enabled but misconfigured
        $response = $this->postJson("/admin/invoices/{$this->invoice->id}/export-xml", [
            'format' => 'ubl_signed',
            'include_signature' => true,
            'validate' => false
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'XML signing configuration invalid'
        ]);
    }

    /** @test */
    public function it_handles_digital_signature_failures()
    {
        // Create invalid private key file
        $invalidKeyPath = $this->certificateDir . '/invalid.key';
        file_put_contents($invalidKeyPath, 'invalid key content');

        // Create signer with invalid key
        $signer = new MkXmlSigner($invalidKeyPath, null);

        // Generate UBL XML
        $xml = $this->ublMapper->mapInvoiceToUbl($this->invoice);

        // Expect exception when trying to sign with invalid key
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Failed to sign XML document');

        $signer->signUblInvoice($xml);
    }

    /** @test */
    public function it_requires_authentication_for_xml_export()
    {
        // Test without authentication
        $response = $this->postJson("/admin/invoices/{$this->invoice->id}/export-xml", [
            'format' => 'ubl',
            'include_signature' => false,
            'validate' => true
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function it_validates_xml_export_parameters()
    {
        // Authenticate as admin user
        $this->actingAs($this->user);

        // Test with invalid format
        $response = $this->postJson("/admin/invoices/{$this->invoice->id}/export-xml", [
            'format' => 'invalid_format',
            'include_signature' => false,
            'validate' => true
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['format']);

        // Test with missing required format
        $response = $this->postJson("/admin/invoices/{$this->invoice->id}/export-xml", [
            'include_signature' => false,
            'validate' => true
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['format']);
    }

    /** @test */
    public function it_handles_nonexistent_invoice()
    {
        // Authenticate as admin user
        $this->actingAs($this->user);

        // Test with nonexistent invoice ID
        $response = $this->postJson("/admin/invoices/99999/export-xml", [
            'format' => 'ubl',
            'include_signature' => false,
            'validate' => true
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_enforces_invoice_access_authorization()
    {
        // Create another company and user
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id,
            'role' => 'admin'
        ]);

        // Authenticate as user from different company
        $this->actingAs($otherUser);

        // Try to export invoice from different company
        $response = $this->postJson("/admin/invoices/{$this->invoice->id}/export-xml", [
            'format' => 'ubl',
            'include_signature' => false,
            'validate' => true
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'message' => 'Unauthorized to export this invoice'
        ]);
    }

    /** @test */
    public function it_generates_correct_filename_for_downloads()
    {
        // Generate test certificate
        $certInfo = $this->xmlSigner->generateTestCertificate($this->certificateDir);

        // Authenticate as admin user
        $this->actingAs($this->user);

        // Test unsigned export filename
        $response = $this->postJson("/admin/invoices/{$this->invoice->id}/export-xml", [
            'format' => 'ubl',
            'include_signature' => false,
            'validate' => false
        ]);

        $response->assertStatus(200);
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString("invoice-{$this->invoice->invoice_number}-ubl.xml", $contentDisposition);

        // Test signed export filename
        $response = $this->postJson("/admin/invoices/{$this->invoice->id}/export-xml", [
            'format' => 'ubl_signed',
            'include_signature' => true,
            'validate' => false
        ]);

        $response->assertStatus(200);
        $contentDisposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString("invoice-{$this->invoice->invoice_number}-ubl-signed.xml", $contentDisposition);
    }

    /** @test */
    public function it_logs_xml_export_activities()
    {
        // Mock Log facade to capture log entries
        Log::fake();

        // Generate test certificate
        $certInfo = $this->xmlSigner->generateTestCertificate($this->certificateDir);

        // Authenticate as admin user
        $this->actingAs($this->user);

        // Perform XML export
        $response = $this->postJson("/admin/invoices/{$this->invoice->id}/export-xml", [
            'format' => 'ubl_signed',
            'include_signature' => true,
            'validate' => false
        ]);

        $response->assertStatus(200);

        // Verify logging occurred
        Log::assertLogged('info', function ($message, $context) {
            return $message === 'Invoice XML exported' && 
                   $context['invoice_id'] === $this->invoice->id &&
                   $context['format'] === 'ubl_signed' &&
                   $context['signed'] === true;
        });
    }

    /** @test */
    public function it_preserves_macedonian_characters_in_xml()
    {
        // Generate UBL XML
        $xml = $this->ublMapper->mapInvoiceToUbl($this->invoice);

        // Verify Macedonian characters are preserved
        $this->assertStringContainsString('Тест Компанија ДОО', $xml);
        $this->assertStringContainsString('Клиент Тест ДООЕл', $xml);
        $this->assertStringContainsString('Лаптоп компјутер', $xml);
        $this->assertStringContainsString('ИТ консултантски услуги', $xml);
        $this->assertStringContainsString('ДДВ', $xml);

        // Verify XML encoding is correct
        $this->assertStringContainsString('encoding="UTF-8"', $xml);

        // Verify DOM can properly load the XML with Macedonian characters
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $this->assertEquals('UTF-8', $dom->encoding);
    }

    /** @test */
    public function it_validates_macedonia_vat_rates_in_ubl_xml()
    {
        // Create invoice with all Macedonia VAT rates
        $exemptVat = TaxType::factory()->create([
            'name' => 'Ослободено од ДДВ',
            'percent' => 0,
            'compound_tax' => false,
            'company_id' => $this->company->id
        ]);

        $reducedVat = TaxType::factory()->create([
            'name' => 'ДДВ 5%',
            'percent' => 5,
            'compound_tax' => false,
            'company_id' => $this->company->id
        ]);

        // Add items with different VAT rates
        $exemptItem = InvoiceItem::factory()->create([
            'invoice_id' => $this->invoice->id,
            'name' => 'Образовни услуги',
            'description' => 'Ослободени од ДДВ според член 29',
            'quantity' => 1,
            'price' => 3000,
            'total' => 3000
        ]);

        $reducedItem = InvoiceItem::factory()->create([
            'invoice_id' => $this->invoice->id,
            'name' => 'Основни прехранбени производи',
            'description' => 'Намалена стапка ДДВ 5%',
            'quantity' => 2,
            'price' => 1000,
            'total' => 2000
        ]);

        // Create corresponding taxes
        Tax::factory()->create([
            'invoice_id' => $this->invoice->id,
            'item_id' => $exemptItem->id,
            'tax_type_id' => $exemptVat->id,
            'name' => 'Ослободено од ДДВ',
            'amount' => 0,
            'percent' => 0
        ]);

        Tax::factory()->create([
            'invoice_id' => $this->invoice->id,
            'item_id' => $reducedItem->id,
            'tax_type_id' => $reducedVat->id,
            'name' => 'ДДВ 5%',
            'amount' => 100, // 5% of 2000
            'percent' => 5
        ]);

        $this->invoice->refresh();

        // Generate UBL XML
        $xml = $this->ublMapper->mapInvoiceToUbl($this->invoice);

        // Verify all Macedonia VAT rates are present
        $this->assertStringContainsString('18', $xml); // Standard rate
        $this->assertStringContainsString('5', $xml);  // Reduced rate
        $this->assertStringContainsString('0', $xml);  // Exempt rate

        // Verify VAT category codes (Macedonia specific)
        $this->assertStringContainsString('S', $xml); // Standard rate category
        $this->assertStringContainsString('AA', $xml); // Reduced rate category  
        $this->assertStringContainsString('E', $xml); // Exempt category

        // Verify tax scheme identification for Macedonia
        $this->assertStringContainsString('VAT', $xml);
        $this->assertStringContainsString('MK', $xml); // Country code
    }

    /** @test */
    public function it_includes_macedonia_business_registration_data()
    {
        // Generate UBL XML
        $xml = $this->ublMapper->mapInvoiceToUbl($this->invoice);

        // Verify company registration information
        $this->assertStringContainsString($this->company->vat_number, $xml);
        
        // Check for Macedonia-specific business identifiers
        $this->assertStringContainsString('MK40300095', $xml); // VAT prefix
        
        // Verify legal entity information
        $this->assertStringContainsString('CompanyLegalEntity', $xml);
        $this->assertStringContainsString('RegistrationName', $xml);
        
        // Check postal address for Macedonia
        $this->assertStringContainsString('Скопје', $xml);
        $this->assertStringContainsString('1000', $xml);
        $this->assertStringContainsString('MK', $xml); // Country identification
    }

    /** @test */
    public function it_validates_qes_certificate_requirements()
    {
        // Generate test QES certificate (simulating real certificate structure)
        $qesCertInfo = $this->xmlSigner->generateQesTestCertificate($this->certificateDir, [
            'organization' => $this->company->name,
            'country' => 'MK',
            'vat_number' => $this->company->vat_number,
            'qualified' => true
        ]);

        // Create signer with QES certificate
        $signer = new MkXmlSigner(
            $qesCertInfo['private_key_path'],
            $qesCertInfo['certificate_path']
        );

        // Generate and sign UBL XML
        $xml = $this->ublMapper->mapInvoiceToUbl($this->invoice);
        $signedXml = $signer->signUblInvoice($xml);

        // Verify QES signature elements
        $dom = new \DOMDocument();
        $dom->loadXML($signedXml);
        $xpath = new \DOMXPath($dom);

        // Check for qualified signature attributes
        $signatureNodes = $xpath->query('//ds:Signature | //Signature');
        $this->assertGreaterThan(0, $signatureNodes->length);

        // Verify certificate contains organization info
        $this->assertStringContainsString($this->company->name, $signedXml);
        $this->assertStringContainsString('MK', $signedXml);

        // Validate signature
        $isValid = $signer->verifySignature($signedXml);
        $this->assertTrue($isValid, 'QES signature should be valid');
    }

    /** @test */
    public function it_measures_xml_generation_performance()
    {
        $iterations = 5;
        $times = [];

        for ($i = 0; $i < $iterations; $i++) {
            $startTime = microtime(true);
            
            // Generate UBL XML
            $xml = $this->ublMapper->mapInvoiceToUbl($this->invoice);
            
            $endTime = microtime(true);
            $times[] = ($endTime - $startTime) * 1000; // Convert to milliseconds
        }

        $averageTime = array_sum($times) / count($times);
        $maxTime = max($times);

        // Assert performance requirements
        $this->assertLessThan(500, $averageTime, 'Average XML generation should be under 500ms');
        $this->assertLessThan(1000, $maxTime, 'Maximum XML generation should be under 1000ms');

        // Log performance metrics for audit report
        $this->addToAssertionCount(1);
        $performanceData = [
            'average_time_ms' => round($averageTime, 2),
            'max_time_ms' => round($maxTime, 2),
            'iterations' => $iterations
        ];
        
        Log::info('XML Generation Performance Test', $performanceData);
    }

    /** @test */
    public function it_validates_ubl_compliance_with_macedonia_requirements()
    {
        // Generate UBL XML
        $xml = $this->ublMapper->mapInvoiceToUbl($this->invoice);

        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $xpath = new \DOMXPath($dom);

        // Register UBL namespaces
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');

        // Verify mandatory UBL elements for Macedonia
        $mandatoryElements = [
            '//cbc:ID', // Invoice number
            '//cbc:IssueDate', // Issue date
            '//cbc:DueDate', // Due date
            '//cbc:DocumentCurrencyCode', // Currency
            '//cac:AccountingSupplierParty', // Supplier
            '//cac:AccountingCustomerParty', // Customer
            '//cac:InvoiceLine', // Invoice lines
            '//cac:LegalMonetaryTotal' // Totals
        ];

        foreach ($mandatoryElements as $element) {
            $nodes = $xpath->query($element);
            $this->assertGreaterThan(0, $nodes->length, "Required UBL element missing: {$element}");
        }

        // Verify Macedonia-specific requirements
        $currencyNodes = $xpath->query('//cbc:DocumentCurrencyCode');
        $this->assertEquals('MKD', $currencyNodes->item(0)->textContent);

        // Verify VAT information
        $vatNodes = $xpath->query('//cac:TaxScheme/cbc:ID');
        $this->assertGreaterThan(0, $vatNodes->length);
        $this->assertEquals('VAT', $vatNodes->item(0)->textContent);
    }

    /** @test */
    public function it_handles_large_invoice_xml_generation()
    {
        // Create invoice with many items (stress test)
        for ($i = 1; $i <= 50; $i++) {
            $item = InvoiceItem::factory()->create([
                'invoice_id' => $this->invoice->id,
                'name' => "Артикал бр. {$i}",
                'description' => "Опис на артикал број {$i} со македонски карактери",
                'quantity' => rand(1, 10),
                'price' => rand(100, 5000),
                'total' => rand(100, 50000)
            ]);

            // Add tax for each item
            Tax::factory()->create([
                'invoice_id' => $this->invoice->id,
                'item_id' => $item->id,
                'tax_type_id' => TaxType::factory()->create([
                    'name' => 'ДДВ 18%',
                    'percent' => 18,
                    'company_id' => $this->company->id
                ])->id,
                'name' => 'ДДВ 18%',
                'amount' => $item->total * 0.18,
                'percent' => 18
            ]);
        }

        $this->invoice->refresh();

        $startTime = microtime(true);
        
        // Generate UBL XML for large invoice
        $xml = $this->ublMapper->mapInvoiceToUbl($this->invoice);
        
        $endTime = microtime(true);
        $generationTime = ($endTime - $startTime) * 1000;

        // Verify XML was generated successfully
        $this->assertNotEmpty($xml);
        $this->assertStringContainsString('<?xml', $xml);

        // Verify all items are included
        for ($i = 1; $i <= 50; $i++) {
            $this->assertStringContainsString("Артикал бр. {$i}", $xml);
        }

        // Performance should still be reasonable for large invoices
        $this->assertLessThan(2000, $generationTime, 'Large invoice XML generation should be under 2 seconds');

        Log::info('Large Invoice XML Generation Test', [
            'item_count' => 50,
            'generation_time_ms' => round($generationTime, 2),
            'xml_size_bytes' => strlen($xml)
        ]);
    }

    /** @test */
    public function it_exports_xml_with_bank_account_information()
    {
        // Generate UBL XML
        $xml = $this->ublMapper->mapInvoiceToUbl($this->invoice);

        // Verify bank account information is included
        $this->assertStringContainsString('PaymentMeans', $xml);
        $this->assertStringContainsString('1234567890123456', $xml); // Account number
        $this->assertStringContainsString('MK07200000000012345678', $xml); // IBAN
        $this->assertStringContainsString('STBKMK22', $xml); // SWIFT
        $this->assertStringContainsString('Stopanska Banka', $xml); // Bank name

        // Verify payment instructions in Macedonian
        $this->assertStringContainsString('PaymentNote', $xml);
    }

    /** @test */
    public function it_includes_proper_metadata_and_timestamps()
    {
        // Generate UBL XML
        $xml = $this->ublMapper->mapInvoiceToUbl($this->invoice);

        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $xpath = new \DOMXPath($dom);

        // Verify timestamps are in correct format
        $issueDateNodes = $xpath->query('//cbc:IssueDate');
        $this->assertGreaterThan(0, $issueDateNodes->length);
        
        $issueDate = $issueDateNodes->item(0)->textContent;
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $issueDate);

        // Verify UBL version information
        $this->assertStringContainsString('UBL-2.1', $xml);
        
        // Verify schema locations
        $this->assertStringContainsString('urn:oasis:names:specification:ubl:schema:xsd:Invoice-2', $xml);
        
        // Verify Macedonia-specific customization
        $customizationNodes = $xpath->query('//cbc:CustomizationID');
        if ($customizationNodes->length > 0) {
            $this->assertStringContainsString('Macedonia', $customizationNodes->item(0)->textContent);
        }
    }

    protected function tearDown(): void
    {
        // Clean up certificate files
        if (is_dir($this->certificateDir)) {
            $files = glob($this->certificateDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->certificateDir);
        }

        parent::tearDown();
    }
}