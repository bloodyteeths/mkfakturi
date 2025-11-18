<?php

namespace App\Services;

use App\Models\Certificate;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * CertificateExtractionService
 *
 * Handles extraction of private keys and certificates from PFX/P12 files.
 * Manages temporary file creation for XML signing operations.
 *
 * SECURITY NOTES:
 * - Private keys are ALWAYS encrypted at rest in the database
 * - Temporary files are created only in memory or secure temp storage
 * - All temporary files MUST be cleaned up after use
 * - Passphrases are NEVER stored or logged
 */
class CertificateExtractionService
{
    /**
     * Array to track temporary files created during service lifetime
     */
    protected array $tempFiles = [];

    /**
     * Extract private key and certificate from PFX/P12 blob.
     *
     * This method decrypts the encrypted PFX blob from the database,
     * then uses openssl_pkcs12_read to extract the private key and certificate.
     *
     * @param  Certificate  $cert  Certificate model with encrypted_key_blob
     * @param  string|null  $passphrase  PFX passphrase (required for encrypted PFX files)
     * @return array ['pkey' => string, 'cert' => string, 'extracerts' => array|null]
     *
     * @throws Exception If decryption fails or PFX is invalid
     */
    public function extractFromPfx(Certificate $cert, ?string $passphrase = null): array
    {
        Log::info('CertificateExtractionService: Extracting from PFX', [
            'certificate_id' => $cert->id,
            'has_passphrase' => ! empty($passphrase),
        ]);

        try {
            // Decrypt the encrypted PFX blob from database
            if (! $cert->encrypted_key_blob) {
                throw new Exception('Certificate has no encrypted key blob');
            }

            $pfxBlob = $this->decryptBlob($cert);

            // Read PKCS12 certificate store
            $certs = [];
            $success = openssl_pkcs12_read($pfxBlob, $certs, $passphrase ?? '');

            if (! $success) {
                $error = openssl_error_string();
                Log::error('CertificateExtractionService: Failed to read PFX', [
                    'certificate_id' => $cert->id,
                    'error' => $error,
                ]);
                throw new Exception('Failed to read PFX file: '.($error ?: 'Invalid passphrase or corrupted file'));
            }

            // Validate required components
            if (! isset($certs['pkey']) || ! isset($certs['cert'])) {
                throw new Exception('PFX file is missing required components (private key or certificate)');
            }

            Log::info('CertificateExtractionService: PFX extracted successfully', [
                'certificate_id' => $cert->id,
                'has_pkey' => isset($certs['pkey']),
                'has_cert' => isset($certs['cert']),
                'has_extracerts' => isset($certs['extracerts']),
            ]);

            return [
                'pkey' => $certs['pkey'],
                'cert' => $certs['cert'],
                'extracerts' => $certs['extracerts'] ?? null,
            ];

        } catch (Exception $e) {
            Log::error('CertificateExtractionService: PFX extraction failed', [
                'certificate_id' => $cert->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Decrypt the encrypted_key_blob using Laravel Crypt.
     *
     * @param  Certificate  $cert  Certificate model with encrypted_key_blob
     * @return string Decrypted PFX blob
     *
     * @throws Exception If decryption fails
     */
    public function decryptBlob(Certificate $cert): string
    {
        try {
            if (! $cert->encrypted_key_blob) {
                throw new Exception('Certificate has no encrypted key blob');
            }

            return Crypt::decryptString($cert->encrypted_key_blob);

        } catch (Exception $e) {
            Log::error('CertificateExtractionService: Blob decryption failed', [
                'certificate_id' => $cert->id,
                'error' => $e->getMessage(),
            ]);
            throw new Exception('Failed to decrypt certificate blob: '.$e->getMessage());
        }
    }

    /**
     * Get temporary file path for private key.
     *
     * Creates a secure temporary file containing the private key in PEM format.
     * The file is registered for cleanup.
     *
     * @param  Certificate  $cert  Certificate model
     * @param  string|null  $passphrase  PFX passphrase
     * @return string Absolute path to temporary private key file
     *
     * @throws Exception
     */
    public function getTempPrivateKeyPath(Certificate $cert, ?string $passphrase = null): string
    {
        Log::info('CertificateExtractionService: Creating temp private key file', [
            'certificate_id' => $cert->id,
        ]);

        try {
            // Check if using file-based storage
            if ($cert->certificate_path && Storage::exists($cert->certificate_path.'/private.key')) {
                $storagePath = Storage::path($cert->certificate_path.'/private.key');
                Log::info('CertificateExtractionService: Using storage-based private key', [
                    'certificate_id' => $cert->id,
                    'path' => $storagePath,
                ]);

                return $storagePath;
            }

            // Extract from PFX blob
            $extracted = $this->extractFromPfx($cert, $passphrase);

            // Create temporary file
            $tempFile = $this->createSecureTempFile('pkey_', '.pem');
            file_put_contents($tempFile, $extracted['pkey']);

            // Set restrictive permissions (owner read-only)
            chmod($tempFile, 0400);

            $this->tempFiles[] = $tempFile;

            Log::info('CertificateExtractionService: Temp private key file created', [
                'certificate_id' => $cert->id,
                'path' => $tempFile,
            ]);

            return $tempFile;

        } catch (Exception $e) {
            Log::error('CertificateExtractionService: Failed to create temp private key', [
                'certificate_id' => $cert->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get temporary file path for certificate.
     *
     * Creates a secure temporary file containing the certificate in PEM format.
     * The file is registered for cleanup.
     *
     * @param  Certificate  $cert  Certificate model
     * @param  string|null  $passphrase  PFX passphrase
     * @return string Absolute path to temporary certificate file
     *
     * @throws Exception
     */
    public function getTempCertificatePath(Certificate $cert, ?string $passphrase = null): string
    {
        Log::info('CertificateExtractionService: Creating temp certificate file', [
            'certificate_id' => $cert->id,
        ]);

        try {
            // Check if using file-based storage
            if ($cert->certificate_path && Storage::exists($cert->certificate_path.'/certificate.pem')) {
                $storagePath = Storage::path($cert->certificate_path.'/certificate.pem');
                Log::info('CertificateExtractionService: Using storage-based certificate', [
                    'certificate_id' => $cert->id,
                    'path' => $storagePath,
                ]);

                return $storagePath;
            }

            // Extract from PFX blob
            $extracted = $this->extractFromPfx($cert, $passphrase);

            // Create temporary file with certificate + chain
            $tempFile = $this->createSecureTempFile('cert_', '.pem');

            $certContent = $extracted['cert'];

            // Append extra certificates (CA chain) if present
            if (! empty($extracted['extracerts'])) {
                foreach ($extracted['extracerts'] as $extraCert) {
                    $certContent .= "\n".$extraCert;
                }
            }

            file_put_contents($tempFile, $certContent);

            // Set restrictive permissions (owner read-only)
            chmod($tempFile, 0400);

            $this->tempFiles[] = $tempFile;

            Log::info('CertificateExtractionService: Temp certificate file created', [
                'certificate_id' => $cert->id,
                'path' => $tempFile,
                'has_chain' => ! empty($extracted['extracerts']),
            ]);

            return $tempFile;

        } catch (Exception $e) {
            Log::error('CertificateExtractionService: Failed to create temp certificate', [
                'certificate_id' => $cert->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get temporary file path for full PFX blob.
     *
     * Creates a temporary .pfx/.p12 file from the encrypted blob.
     * Useful for tools that require the full PFX file.
     *
     * @param  Certificate  $cert  Certificate model
     * @return string Absolute path to temporary PFX file
     *
     * @throws Exception
     */
    public function getTempPfxPath(Certificate $cert): string
    {
        Log::info('CertificateExtractionService: Creating temp PFX file', [
            'certificate_id' => $cert->id,
        ]);

        try {
            // Decrypt the PFX blob
            $pfxBlob = $this->decryptBlob($cert);

            // Create temporary file
            $tempFile = $this->createSecureTempFile('pfx_', '.pfx');
            file_put_contents($tempFile, $pfxBlob);

            // Set restrictive permissions (owner read-only)
            chmod($tempFile, 0400);

            $this->tempFiles[] = $tempFile;

            Log::info('CertificateExtractionService: Temp PFX file created', [
                'certificate_id' => $cert->id,
                'path' => $tempFile,
            ]);

            return $tempFile;

        } catch (Exception $e) {
            Log::error('CertificateExtractionService: Failed to create temp PFX', [
                'certificate_id' => $cert->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Create a secure temporary file.
     *
     * @param  string  $prefix  Filename prefix
     * @param  string  $suffix  Filename suffix (extension)
     * @return string Absolute path to temporary file
     */
    protected function createSecureTempFile(string $prefix = 'tmp_', string $suffix = ''): string
    {
        $tempDir = sys_get_temp_dir();
        $tempFile = tempnam($tempDir, $prefix);

        // If a suffix is provided, rename the file
        if ($suffix) {
            $newTempFile = $tempFile.$suffix;
            rename($tempFile, $newTempFile);
            $tempFile = $newTempFile;
        }

        return $tempFile;
    }

    /**
     * Clean up all temporary files created by this service instance.
     *
     * IMPORTANT: Always call this method after XML signing operations complete.
     * Recommended usage: try-finally block or PHP destructors.
     */
    public function cleanup(): void
    {
        if (empty($this->tempFiles)) {
            return;
        }

        Log::info('CertificateExtractionService: Cleaning up temporary files', [
            'count' => count($this->tempFiles),
        ]);

        $deletedCount = 0;
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                // Securely delete by overwriting with zeros first (optional)
                // This prevents recovery of sensitive key material
                try {
                    $fileSize = filesize($file);
                    if ($fileSize > 0) {
                        @file_put_contents($file, str_repeat("\0", $fileSize));
                    }
                } catch (\Throwable $e) {
                    // If overwrite fails, still attempt deletion
                    Log::warning('CertificateExtractionService: Failed to overwrite file, will still delete', [
                        'path' => $file,
                        'error' => $e->getMessage(),
                    ]);
                }

                @unlink($file);
                $deletedCount++;

                Log::debug('CertificateExtractionService: Deleted temp file', [
                    'path' => $file,
                ]);
            }
        }

        $this->tempFiles = [];

        Log::info('CertificateExtractionService: Cleanup completed', [
            'deleted' => $deletedCount,
        ]);
    }

    /**
     * Destructor to ensure cleanup on object destruction.
     */
    public function __destruct()
    {
        $this->cleanup();
    }
}

// CLAUDE-CHECKPOINT
