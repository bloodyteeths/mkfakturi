<?php

// Simple script to test XML signing functionality without Laravel autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Test without MkXmlSigner class for now
// require_once __DIR__ . '/Modules/Mk/Services/MkXmlSigner.php';

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

try {
    echo "Testing XML digital signature with xmlseclibs...\n";
    
    // Check if xmlseclibs is available
    if (!class_exists('RobRichards\XMLSecLibs\XMLSecurityDSig')) {
        throw new Exception('xmlseclibs library not found');
    }
    
    echo "✓ xmlseclibs library is available\n";
    
    // Create test certificate directory
    $certDir = __DIR__ . '/storage/certificates';
    if (!is_dir($certDir)) {
        mkdir($certDir, 0755, true);
    }
    
    // Generate test certificate using OpenSSL
    $configArray = [
        "digest_alg" => "sha256",
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ];

    $dn = [
        "countryName" => "MK",
        "stateOrProvinceName" => "Skopje", 
        "localityName" => "Skopje",
        "organizationName" => "Test Organization",
        "commonName" => "test.example.mk"
    ];

    // Generate private key and certificate
    $privateKey = openssl_pkey_new($configArray);
    $csr = openssl_csr_new($dn, $privateKey, $configArray);
    $x509 = openssl_csr_sign($csr, null, $privateKey, 365, $configArray);

    // Export to PEM format
    openssl_pkey_export($privateKey, $privateKeyPem);
    openssl_x509_export($x509, $certificatePem);

    // Save to files
    $privateKeyPath = $certDir . '/test_private.key';
    $certificatePath = $certDir . '/test_certificate.pem';
    
    file_put_contents($privateKeyPath, $privateKeyPem);
    file_put_contents($certificatePath, $certificatePem);
    
    chmod($privateKeyPath, 0600);
    chmod($certificatePath, 0644);
    
    echo "✓ Test certificate generated\n";
    
    // Test XML content
    $testXml = '<?xml version="1.0"?>
<TestDocument>
    <ID>TEST-001</ID>
    <Content>This is a test XML document for signing</Content>
</TestDocument>';
    
    // Load XML
    $doc = new DOMDocument();
    $doc->preserveWhiteSpace = false;
    $doc->formatOutput = true;
    $doc->loadXML($testXml);
    
    echo "✓ Test XML loaded\n";
    
    // Create signature
    $objDSig = new XMLSecurityDSig();
    $objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
    
    // Add reference
    $objDSig->addReference(
        $doc,
        XMLSecurityDSig::SHA256,
        ['http://www.w3.org/2000/09/xmldsig#enveloped-signature']
    );
    
    // Create key
    $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
    $objKey->loadKey($privateKeyPath, true);
    
    // Sign
    $objDSig->sign($objKey);
    $objDSig->add509Cert($certificatePem);
    $objDSig->appendSignature($doc->documentElement);
    
    $signedXml = $doc->saveXML();
    
    echo "✓ XML document signed successfully\n";
    
    // Verify signature exists
    if (strpos($signedXml, 'Signature') === false) {
        throw new Exception('No signature found in XML');
    }
    
    echo "✓ Signature element found in XML\n";
    
    // Verify signature
    $verifyDoc = new DOMDocument();
    $verifyDoc->loadXML($signedXml);
    
    $objDSigVerify = new XMLSecurityDSig();
    $objDSigVerify->locateSignature($verifyDoc);
    $objDSigVerify->canonicalizeSignedInfo();
    
    $objKeyVerify = $objDSigVerify->locateKey();
    if ($objKeyVerify) {
        $x509Certificate = $objDSigVerify->locateX509Certificate();
        if ($x509Certificate) {
            $objKeyVerify->loadKey($x509Certificate, false, true);
        }
        
        $isValid = $objDSigVerify->verify($objKeyVerify);
        
        if ($isValid) {
            echo "✓ XML signature verified successfully\n";
        } else {
            throw new Exception('Signature verification failed');
        }
    } else {
        throw new Exception('No key found for verification');
    }
    
    echo "\n=== Sample Signed XML (first 600 chars) ===\n";
    echo substr($signedXml, 0, 600) . "...\n";
    
    echo "\n✅ XML digital signature test PASSED!\n";
    echo "xmlseclibs is working correctly and can sign/verify XML documents.\n";
    
} catch (Exception $e) {
    echo "\n❌ XML digital signature test FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    if (isset($e->getTrace)) {
        echo "Trace: " . $e->getTraceAsString() . "\n";
    }
    exit(1);
}