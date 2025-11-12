<?php

namespace Modules\Mk\Services;

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use DOMDocument;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Macedonian XML Digital Signature Service
 * 
 * Provides digital signing capabilities for UBL XML documents
 * Supports Macedonian tax authority requirements for e-invoicing
 */
class MkXmlSigner
{
    protected $privateKeyPath;
    protected $certificatePath;
    protected $passphrase;

    /**
     * Initialize signer with certificate paths
     */
    public function __construct(
        string $privateKeyPath = null,
        string $certificatePath = null,
        string $passphrase = null
    ) {
        $this->privateKeyPath = $privateKeyPath ?? config('mk.xml_signing.private_key_path');
        $this->certificatePath = $certificatePath ?? config('mk.xml_signing.certificate_path');
        $this->passphrase = $passphrase ?? config('mk.xml_signing.passphrase');
    }

    /**
     * Sign XML document with digital signature
     */
    public function signXml(string $xmlContent): string
    {
        try {
            // Load XML document
            $doc = new DOMDocument();
            $doc->preserveWhiteSpace = false;
            $doc->formatOutput = true;
            
            if (!$doc->loadXML($xmlContent)) {
                throw new Exception('Invalid XML content provided for signing');
            }

            // Create signature object
            $objDSig = new XMLSecurityDSig();
            
            // Set canonicalization method (required for most standards)
            $objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

            // Add reference to the document root with SHA-256 hash
            $objDSig->addReference(
                $doc,
                XMLSecurityDSig::SHA256,
                ['http://www.w3.org/2000/09/xmldsig#enveloped-signature'],
                ['force_uri' => true]
            );

            // Create and load private key
            $objKey = $this->createPrivateKey();
            
            // Sign the document
            $objDSig->sign($objKey);
            
            // Add certificate to signature if available
            if ($this->certificatePath && file_exists($this->certificatePath)) {
                $certificateContent = file_get_contents($this->certificatePath);
                $objDSig->add509Cert($certificateContent);
            }

            // Append signature to document
            $objDSig->appendSignature($doc->documentElement);

            Log::info('XML document signed successfully', [
                'private_key_path' => $this->privateKeyPath,
                'certificate_path' => $this->certificatePath
            ]);

            return $doc->saveXML();

        } catch (Exception $e) {
            Log::error('XML signing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new Exception('Failed to sign XML document: ' . $e->getMessage());
        }
    }

    /**
     * Verify XML signature
     */
    public function verifySignature(string $signedXmlContent): bool
    {
        try {
            // Load signed XML document
            $doc = new DOMDocument();
            $doc->preserveWhiteSpace = false;
            
            if (!$doc->loadXML($signedXmlContent)) {
                throw new Exception('Invalid signed XML content');
            }

            // Create signature verification object
            $objDSig = new XMLSecurityDSig();
            
            // Locate signature in the document
            $objDSig->locateSignature($doc);
            
            if (!$objDSig->locateSignature($doc)) {
                Log::warning('No signature found in XML document');
                return false;
            }

            // Canonicalize the signature
            $objDSig->canonicalizeSignedInfo();
            
            // Get signature key info
            $objKey = $objDSig->locateKey();

            if (!$objKey) {
                Log::warning('No key found in XML signature');
                return false;
            }

            // Load certificate if embedded in signature
            // XMLSecurityDSig::staticLocateKeyInfo() returns the KeyInfo node
            try {
                XMLSecLibs\XMLSecurityDSig::staticLocateKeyInfo($objKey, $objDSig->sigNode);
            } catch (\Exception $e) {
                Log::warning('Failed to locate key info', ['error' => $e->getMessage()]);
            }

            // Verify signature
            $isValid = $objDSig->verify($objKey);

            Log::info('XML signature verification completed', [
                'is_valid' => $isValid,
            ]);

            return $isValid;

        } catch (Exception $e) {
            Log::error('XML signature verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return false;
        }
    }

    /**
     * Create private key object
     */
    protected function createPrivateKey(): XMLSecurityKey
    {
        if (!$this->privateKeyPath || !file_exists($this->privateKeyPath)) {
            throw new Exception('Private key file not found: ' . $this->privateKeyPath);
        }

        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        
        // Load private key with optional passphrase
        if ($this->passphrase) {
            $objKey->passphrase = $this->passphrase;
        }
        
        $objKey->loadKey($this->privateKeyPath, true);
        
        return $objKey;
    }

    /**
     * Generate self-signed certificate for testing (development only)
     */
    public function generateTestCertificate(string $outputDir): array
    {
        if (config('app.env') === 'production') {
            throw new Exception('Test certificate generation is not allowed in production');
        }

        $configArray = [
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        // Distinguished name for certificate
        $dn = [
            "countryName" => "MK",
            "stateOrProvinceName" => "Skopje",
            "localityName" => "Skopje",
            "organizationName" => "Test Organization",
            "organizationalUnitName" => "IT Department",
            "commonName" => "test.invoiceshelf.mk",
            "emailAddress" => "test@invoiceshelf.mk"
        ];

        // Generate private key
        $privateKey = openssl_pkey_new($configArray);
        
        // Generate certificate signing request
        $csr = openssl_csr_new($dn, $privateKey, $configArray);
        
        // Generate self-signed certificate (valid for 365 days)
        $x509 = openssl_csr_sign($csr, null, $privateKey, 365, $configArray);

        // Export private key
        openssl_pkey_export($privateKey, $privateKeyOut, $this->passphrase);
        
        // Export certificate
        openssl_x509_export($x509, $certificateOut);

        // Save to files
        $privateKeyPath = $outputDir . '/private.key';
        $certificatePath = $outputDir . '/certificate.pem';
        
        file_put_contents($privateKeyPath, $privateKeyOut);
        file_put_contents($certificatePath, $certificateOut);

        // Set appropriate permissions (read only for owner)
        chmod($privateKeyPath, 0600);
        chmod($certificatePath, 0644);

        Log::info('Test certificate generated', [
            'private_key_path' => $privateKeyPath,
            'certificate_path' => $certificatePath,
            'validity_days' => 365
        ]);

        return [
            'private_key_path' => $privateKeyPath,
            'certificate_path' => $certificatePath,
            'certificate_info' => openssl_x509_parse($x509)
        ];
    }

    /**
     * Get certificate information
     */
    public function getCertificateInfo(): ?array
    {
        if (!$this->certificatePath || !file_exists($this->certificatePath)) {
            return null;
        }

        $certificateContent = file_get_contents($this->certificatePath);
        $x509 = openssl_x509_read($certificateContent);
        
        if (!$x509) {
            return null;
        }

        $info = openssl_x509_parse($x509);
        
        return [
            'subject' => $info['subject'] ?? [],
            'issuer' => $info['issuer'] ?? [],
            'valid_from' => date('Y-m-d H:i:s', $info['validFrom_time_t'] ?? 0),
            'valid_to' => date('Y-m-d H:i:s', $info['validTo_time_t'] ?? 0),
            'serial_number' => $info['serialNumber'] ?? '',
            'fingerprint' => openssl_x509_fingerprint($x509, 'sha256'),
            'is_valid' => time() >= ($info['validFrom_time_t'] ?? 0) && 
                         time() <= ($info['validTo_time_t'] ?? 0)
        ];
    }

    /**
     * Sign UBL invoice XML (convenience method)
     */
    public function signUblInvoice(string $ublXml): string
    {
        // Add Macedonia-specific signing metadata if needed
        $doc = new DOMDocument();
        $doc->loadXML($ublXml);
        
        // You could add Macedonia-specific elements here before signing
        // For example, timestamp, signer information, etc.
        
        return $this->signXml($doc->saveXML());
    }

    /**
     * Create detached signature (signature in separate file)
     */
    public function createDetachedSignature(string $xmlContent): string
    {
        try {
            // Create signature object for detached signing
            $objDSig = new XMLSecurityDSig();
            $objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

            // Add reference with URI pointing to the document
            $objDSig->addReference(
                $xmlContent,
                XMLSecurityDSig::SHA256,
                [],
                ['overwrite' => false, 'force_uri' => true]
            );

            // Create and load private key
            $objKey = $this->createPrivateKey();
            
            // Sign
            $objDSig->sign($objKey);
            
            // Add certificate
            if ($this->certificatePath && file_exists($this->certificatePath)) {
                $certificateContent = file_get_contents($this->certificatePath);
                $objDSig->add509Cert($certificateContent);
            }

            // Create signature document
            $signatureDoc = new DOMDocument();
            $signatureDoc->appendChild($objDSig->sigNode);
            
            return $signatureDoc->saveXML();

        } catch (Exception $e) {
            Log::error('Detached signature creation failed', [
                'error' => $e->getMessage()
            ]);
            
            throw new Exception('Failed to create detached signature: ' . $e->getMessage());
        }
    }

    /**
     * Validate signing configuration
     */
    public function validateConfiguration(): array
    {
        $errors = [];
        $warnings = [];

        // Check private key
        if (!$this->privateKeyPath) {
            $errors[] = 'Private key path not configured';
        } elseif (!file_exists($this->privateKeyPath)) {
            $errors[] = 'Private key file not found: ' . $this->privateKeyPath;
        } elseif (!is_readable($this->privateKeyPath)) {
            $errors[] = 'Private key file not readable: ' . $this->privateKeyPath;
        }

        // Check certificate
        if (!$this->certificatePath) {
            $warnings[] = 'Certificate path not configured (signatures will not include certificate)';
        } elseif (!file_exists($this->certificatePath)) {
            $warnings[] = 'Certificate file not found: ' . $this->certificatePath;
        }

        // Check certificate validity
        $certInfo = $this->getCertificateInfo();
        if ($certInfo && !$certInfo['is_valid']) {
            $warnings[] = 'Certificate is expired or not yet valid';
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'certificate_info' => $certInfo
        ];
    }
}