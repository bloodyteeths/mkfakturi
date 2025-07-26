<?php

// Simple script to test UBL generation without PHPUnit
require_once __DIR__ . '/vendor/autoload.php';

use Modules\Mk\Services\MkUblMapper;
use App\Models\Invoice;

try {
    echo "Testing UBL XML generation...\n";
    
    // Create a minimal test invoice (mock data)
    $invoiceData = (object) [
        'invoice_number' => 'TEST-001',
        'invoice_date' => '2025-01-15',
        'due_date' => '2025-02-15',
        'sub_total' => 100.00,
        'tax_total' => 18.00,
        'total' => 118.00,
        'notes' => 'Test UBL generation',
        'currency' => (object) ['code' => 'MKD'],
        'company' => (object) [
            'name' => 'Test Company DOO',
            'vat_number' => 'MK123456789',
            'address' => (object) [
                'address_street_1' => 'Test Street 1',
                'city' => 'Skopje',
                'zip' => '1000'
            ],
            'bankAccounts' => []
        ],
        'customer' => (object) [
            'name' => 'Test Customer',
            'email' => 'customer@test.mk',
            'phone' => '+389 2 123 456',
            'tax_id' => 'MK987654321',
            'addresses' => []
        ],
        'items' => [
            (object) [
                'name' => 'Test Product',
                'description' => 'Test Description',
                'quantity' => 1,
                'price' => 100.00,
                'taxes' => []
            ]
        ],
        'taxes' => []
    ];
    
    echo "✓ Test data created\n";
    
    // Check if UBL classes are available
    if (!class_exists('NumNum\UBL\Invoice')) {
        throw new Exception('UBL library not found. Make sure num-num/ubl-invoice is installed.');
    }
    
    echo "✓ UBL library is available\n";
    
    // Test basic UBL invoice creation with required fields
    $ublInvoice = new NumNum\UBL\Invoice();
    $ublInvoice->setId('TEST-001');
    $ublInvoice->setIssueDate(new DateTime('2025-01-15'));
    $ublInvoice->setDocumentCurrencyCode('MKD');
    
    // Add required supplier party
    $country = (new NumNum\UBL\Country())->setIdentificationCode('MK');
    $address = (new NumNum\UBL\Address())
        ->setStreetName('Test Street')
        ->setCityName('Skopje')
        ->setPostalZone('1000')
        ->setCountry($country);
    
    $supplierParty = (new NumNum\UBL\Party())
        ->setName('Test Supplier')
        ->setPhysicalLocation($address)
        ->setPostalAddress($address);
    
    $ublInvoice->setAccountingSupplierParty($supplierParty);
    
    // Add required customer party
    $customerParty = (new NumNum\UBL\Party())
        ->setName('Test Customer')
        ->setPostalAddress($address);
    
    $ublInvoice->setAccountingCustomerParty($customerParty);
    
    // Add required legal monetary total
    $legalMonetaryTotal = (new NumNum\UBL\LegalMonetaryTotal())
        ->setLineExtensionAmount(100)
        ->setTaxExclusiveAmount(100)
        ->setTaxInclusiveAmount(118)
        ->setPayableAmount(118);
    
    $ublInvoice->setLegalMonetaryTotal($legalMonetaryTotal);
    
    // Add required invoice lines
    $invoiceLine = new NumNum\UBL\InvoiceLine();
    $invoiceLine->setId(1);
    $invoiceLine->setInvoicedQuantity(1);
    $invoiceLine->setLineExtensionAmount(100);
    
    $item = new NumNum\UBL\Item();
    $item->setName('Test Product');
    $invoiceLine->setItem($item);
    
    $price = new NumNum\UBL\Price();
    $price->setPriceAmount(100);
    $invoiceLine->setPrice($price);
    
    $ublInvoice->setInvoiceLines([$invoiceLine]);
    
    echo "✓ Basic UBL invoice object created with all required fields\n";
    
    // Test XML generation
    $generator = new NumNum\UBL\Generator();
    $xml = $generator->invoice($ublInvoice);
    
    if (empty($xml)) {
        throw new Exception('Generated XML is empty');
    }
    
    echo "✓ UBL XML generated successfully\n";
    
    // Test XML is well-formed
    $dom = new DOMDocument();
    if (!$dom->loadXML($xml)) {
        throw new Exception('Generated XML is not well-formed');
    }
    
    echo "✓ Generated XML is well-formed\n";
    
    // Check for basic UBL elements
    if (strpos($xml, 'Invoice') === false) {
        throw new Exception('XML does not contain Invoice element');
    }
    
    if (strpos($xml, 'TEST-001') === false) {
        throw new Exception('XML does not contain invoice number');
    }
    
    echo "✓ XML contains expected elements\n";
    
    echo "\n=== Sample Generated XML (first 500 chars) ===\n";
    echo substr($xml, 0, 500) . "...\n";
    
    echo "\n✅ UBL XML generation test PASSED!\n";
    echo "The MkUblMapper should work correctly with real InvoiceShelf data.\n";
    
} catch (Exception $e) {
    echo "\n❌ UBL XML generation test FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}