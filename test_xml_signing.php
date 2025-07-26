<?php

// Simple script to test XML signing functionality
require_once __DIR__ . '/vendor/autoload.php';

use Modules\Mk\Services\MkXmlSigner;

try {
    echo "Testing XML digital signature functionality...\n";
    
    // Create certificates directory
    $certDir = __DIR__ . '/storage/certificates';
    if (!is_dir($certDir)) {
        mkdir($certDir, 0755, true);
        echo "✓ Created certificates directory\n";
    }
    
    // Check if xmlseclibs is available
    if (!class_exists('RobRichards\XMLSecLibs\XMLSecurityDSig')) {
        throw new Exception('xmlseclibs library not found. Make sure robrichards/xmlseclibs is installed.');
    }
    
    echo "✓ xmlseclibs library is available\n";
    
    // Create XML signer instance
    $signer = new MkXmlSigner();
    
    echo "✓ MkXmlSigner instance created\n";
    
    // Generate test certificate for development
    echo "Generating test certificate...\n";
    $certInfo = $signer->generateTestCertificate($certDir);
    
    echo "✓ Test certificate generated\n";
    echo "  Private key: " . $certInfo['private_key_path'] . "\n";
    echo "  Certificate: " . $certInfo['certificate_path'] . "\n";
    
    // Create new signer with generated certificate
    $signer = new MkXmlSigner(
        $certInfo['private_key_path'],
        $certInfo['certificate_path']
    );
    
    // Validate configuration
    $validation = $signer->validateConfiguration();
    if (!$validation['is_valid']) {
        throw new Exception('Configuration validation failed: ' . implode(', ', $validation['errors']));
    }
    
    echo "✓ Signer configuration is valid\n";
    
    // Test XML content (simple UBL-like structure)
    $testXml = '<?xml version="1.0"?>
<Invoice xmlns="urn:oasis:names:specification:ubl:schema:xsd:Invoice-2">
    <ID>TEST-001</ID>
    <IssueDate>2025-01-15</IssueDate>
    <DocumentCurrencyCode>MKD</DocumentCurrencyCode>
    <AccountingSupplierParty>
        <Party>
            <PartyName>
                <Name>Test Supplier</Name>
            </PartyName>
        </Party>
    </AccountingSupplierParty>
    <AccountingCustomerParty>
        <Party>
            <PartyName>
                <Name>Test Customer</Name>
            </PartyName>
        </Party>
    </AccountingCustomerParty>
    <InvoiceLine>
        <ID>1</ID>
        <InvoicedQuantity>1</InvoicedQuantity>
        <LineExtensionAmount>100.00</LineExtensionAmount>
        <Item>
            <Name>Test Product</Name>
        </Item>
        <Price>
            <PriceAmount>100.00</PriceAmount>
        </Price>
    </InvoiceLine>
    <LegalMonetaryTotal>
        <LineExtensionAmount>100.00</LineExtensionAmount>
        <TaxExclusiveAmount>100.00</TaxExclusiveAmount>
        <TaxInclusiveAmount>118.00</TaxInclusiveAmount>
        <PayableAmount>118.00</PayableAmount>
    </LegalMonetaryTotal>
</Invoice>';
    
    echo "✓ Test XML content prepared\n";
    
    // Sign the XML
    echo "Signing XML document...\n";
    $signedXml = $signer->signXml($testXml);
    
    if (empty($signedXml)) {
        throw new Exception('Signed XML is empty');
    }
    
    echo "✓ XML document signed successfully\n";
    
    // Check if signed XML contains signature
    if (strpos($signedXml, '<ds:Signature') === false && strpos($signedXml, '<Signature') === false) {
        throw new Exception('Signed XML does not contain signature element');
    }
    
    echo "✓ Signed XML contains signature element\n";
    
    // Verify the signature
    echo "Verifying XML signature...\n";
    $isValid = $signer->verifySignature($signedXml);
    
    if (!$isValid) {
        throw new Exception('Signature verification failed');
    }
    
    echo "✓ XML signature verified successfully\n";
    
    // Test certificate info
    $certInfo = $signer->getCertificateInfo();
    if ($certInfo) {
        echo "✓ Certificate information retrieved\n";
        echo "  Subject: " . ($certInfo['subject']['CN'] ?? 'Unknown') . "\n";
        echo "  Valid from: " . $certInfo['valid_from'] . "\n";
        echo "  Valid to: " . $certInfo['valid_to'] . "\n";
        echo "  Is valid: " . ($certInfo['is_valid'] ? 'Yes' : 'No') . "\n";
    }
    
    echo "\n=== Sample Signed XML (first 800 chars) ===\n";
    echo substr($signedXml, 0, 800) . "...\n";
    
    echo "\n✅ XML digital signature test PASSED!\n";
    echo "The MkXmlSigner is working correctly and can sign/verify XML documents.\n";
    
} catch (Exception $e) {
    echo "\n❌ XML digital signature test FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}