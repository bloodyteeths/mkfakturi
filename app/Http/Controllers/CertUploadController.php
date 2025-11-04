<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Certificate Upload Controller
 * 
 * Handles QES (Qualified Electronic Signature) certificate uploads
 * for partner bureaus. Provides secure certificate storage and validation
 * for digital signature capabilities in e-faktura compliance.
 */
class CertUploadController extends Controller
{
    /**
     * Storage paths for certificates
     */
    const CERT_STORAGE_PATH = 'certificates';
    const PRIVATE_KEY_FILENAME = 'private.key';
    const CERTIFICATE_FILENAME = 'certificate.pem';
    const CERTIFICATE_INFO_FILENAME = 'certificate_info.json';

    /**
     * Get current certificate information
     */
    public function current(): JsonResponse
    {
        try {
            $certificateInfoPath = storage_path('app/' . self::CERT_STORAGE_PATH . '/' . self::CERTIFICATE_INFO_FILENAME);

            if (!File::exists($certificateInfoPath)) {
                return response()->json([
                    'data' => null,
                    'message' => __('certificates.no_certificate_found')
                ], 200);
            }

            $certificateInfo = json_decode(File::get($certificateInfoPath), true);

            // Check if certificate files still exist
            $privateKeyPath = storage_path('app/' . self::CERT_STORAGE_PATH . '/' . self::PRIVATE_KEY_FILENAME);
            $certificatePath = storage_path('app/' . self::CERT_STORAGE_PATH . '/' . self::CERTIFICATE_FILENAME);

            if (!File::exists($privateKeyPath) || !File::exists($certificatePath)) {
                // Clean up invalid info file
                File::delete($certificateInfoPath);

                return response()->json([
                    'data' => null,
                    'message' => __('certificates.certificate_files_missing')
                ], 200);
            }

            // Verify certificate is still valid
            $certificateInfo['is_valid'] = $this->isCertificateValid($certificateInfo);

            return response()->json([
                'data' => $certificateInfo,
                'message' => __('certificates.current_certificate_retrieved')
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to retrieve current certificate', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'data' => null,
                'message' => __('certificates.retrieval_error')
            ], 500);
        }
    }
    // CLAUDE-CHECKPOINT

    /**
     * Upload and process certificate
     */
    public function upload(Request $request): JsonResponse
    {
        // Validate request
        $validator = Validator::make($request->all(), [
            'certificate' => [
                'required',
                'file',
                'mimes:p12,pfx',
                'max:5120' // 5MB max
            ],
            'password' => [
                'required',
                'string',
                'min:4',
                'max:255'
            ],
            'description' => [
                'nullable',
                'string',
                'max:500'
            ]
        ], [
            'certificate.required' => __('certificates.certificate_required'),
            'certificate.file' => __('certificates.invalid_file'),
            'certificate.mimes' => __('certificates.invalid_format'),
            'certificate.max' => __('certificates.file_too_large'),
            'password.required' => __('certificates.password_required'),
            'password.min' => __('certificates.password_too_short'),
            'password.max' => __('certificates.password_too_long'),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('certificates.validation_failed'),
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $certificateFile = $request->file('certificate');
            $password = $request->input('password');
            $description = $request->input('description', '');

            // Create secure temporary file for processing
            $tempPath = $this->createSecureTempFile($certificateFile);

            try {
                // Extract private key and certificate from P12/PFX
                $extractedData = $this->extractCertificateData($tempPath, $password);

                // Validate extracted certificate
                $certificateInfo = $this->validateCertificate($extractedData['certificate']);

                // Store certificate and private key securely
                $this->storeCertificate($extractedData, $certificateInfo, $description);

                // Update configuration
                $this->updateSigningConfiguration();

                Log::info('Certificate uploaded successfully', [
                    'subject' => $certificateInfo['subject']['CN'] ?? 'Unknown',
                    'valid_until' => $certificateInfo['valid_to'],
                    'fingerprint' => substr($certificateInfo['fingerprint'], 0, 16) . '...'
                ]);

                return response()->json([
                    'data' => $certificateInfo,
                    'message' => __('certificates.upload_success')
                ], 201);

            } finally {
                // Always clean up temporary file
                if (File::exists($tempPath)) {
                    File::delete($tempPath);
                }
            }

        } catch (\Exception $e) {
            Log::error('Certificate upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Return appropriate error message
            if (str_contains($e->getMessage(), 'password')) {
                return response()->json([
                    'message' => __('certificates.invalid_password'),
                    'errors' => ['password' => [__('certificates.invalid_password')]]
                ], 422);
            }

            if (str_contains($e->getMessage(), 'certificate')) {
                return response()->json([
                    'message' => __('certificates.invalid_certificate')
                ], 422);
            }

            return response()->json([
                'message' => __('certificates.upload_error')
            ], 500);
        }
    }

    /**
     * Delete current certificate
     */
    public function delete(): JsonResponse
    {
        try {
            $storagePath = storage_path('app/' . self::CERT_STORAGE_PATH);

            // Remove all certificate files
            $filesToDelete = [
                $storagePath . '/' . self::PRIVATE_KEY_FILENAME,
                $storagePath . '/' . self::CERTIFICATE_FILENAME,
                $storagePath . '/' . self::CERTIFICATE_INFO_FILENAME
            ];

            foreach ($filesToDelete as $filePath) {
                if (File::exists($filePath)) {
                    File::delete($filePath);
                }
            }

            Log::info('Certificate deleted successfully');

            return response()->json([
                'message' => __('certificates.delete_success')
            ]);

        } catch (\Exception $e) {
            Log::error('Certificate deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => __('certificates.delete_error')
            ], 500);
        }
    }

    /**
     * Create secure temporary file for certificate processing
     */
    private function createSecureTempFile($uploadedFile): string
    {
        $tempDir = sys_get_temp_dir();
        $tempFileName = 'cert_' . Str::random(32) . '.tmp';
        $tempPath = $tempDir . '/' . $tempFileName;

        // Move uploaded file to temporary location
        if (!$uploadedFile->move(dirname($tempPath), basename($tempPath))) {
            throw new \Exception('Failed to create temporary certificate file');
        }

        // Set secure permissions
        chmod($tempPath, 0600);

        return $tempPath;
    }

    /**
     * Extract certificate and private key from P12/PFX file
     */
    private function extractCertificateData(string $filePath, string $password): array
    {
        $p12Content = File::get($filePath);
        $certificates = [];

        // Extract data from P12/PFX file
        if (!openssl_pkcs12_read($p12Content, $certificates, $password)) {
            $openSslError = openssl_error_string();
            Log::warning('PKCS12 extraction failed', ['openssl_error' => $openSslError]);
            throw new \Exception('Invalid certificate password or corrupted certificate file');
        }

        // Validate required components
        if (empty($certificates['pkey']) || empty($certificates['cert'])) {
            throw new \Exception('Certificate file does not contain required private key or certificate');
        }

        return [
            'private_key' => $certificates['pkey'],
            'certificate' => $certificates['cert'],
            'extra_certificates' => $certificates['extracerts'] ?? []
        ];
    }

    /**
     * Validate certificate and extract information
     */
    private function validateCertificate(string $certificatePem): array
    {
        $x509Resource = openssl_x509_read($certificatePem);
        
        if (!$x509Resource) {
            throw new \Exception('Invalid certificate format');
        }

        $certificateData = openssl_x509_parse($x509Resource);
        
        if (!$certificateData) {
            throw new \Exception('Unable to parse certificate data');
        }

        // Check if certificate is expired
        $now = time();
        $validFrom = $certificateData['validFrom_time_t'] ?? 0;
        $validTo = $certificateData['validTo_time_t'] ?? 0;

        $isValid = ($now >= $validFrom && $now <= $validTo);

        // Extract certificate information
        return [
            'subject' => $certificateData['subject'] ?? [],
            'issuer' => $certificateData['issuer'] ?? [],
            'serial_number' => $certificateData['serialNumber'] ?? '',
            'valid_from' => date('Y-m-d H:i:s', $validFrom),
            'valid_to' => date('Y-m-d H:i:s', $validTo),
            'fingerprint' => openssl_x509_fingerprint($x509Resource, 'sha256'),
            'is_valid' => $isValid,
            'uploaded_at' => now()->toISOString(),
            'algorithm' => 'RSA-SHA256' // Default for digital signatures
        ];
    }

    /**
     * Store certificate and private key securely
     */
    private function storeCertificate(array $extractedData, array $certificateInfo, string $description): void
    {
        $storagePath = storage_path('app/' . self::CERT_STORAGE_PATH);

        // Ensure directory exists with secure permissions
        if (!File::isDirectory($storagePath)) {
            File::makeDirectory($storagePath, 0700, true);
        }

        // Store private key
        $privateKeyPath = $storagePath . '/' . self::PRIVATE_KEY_FILENAME;
        File::put($privateKeyPath, $extractedData['private_key']);
        chmod($privateKeyPath, 0600);

        // Store certificate
        $certificatePath = $storagePath . '/' . self::CERTIFICATE_FILENAME;
        File::put($certificatePath, $extractedData['certificate']);
        chmod($certificatePath, 0644);

        // Store certificate information with description
        $certificateInfo['description'] = $description;
        $infoPath = $storagePath . '/' . self::CERTIFICATE_INFO_FILENAME;
        File::put($infoPath, json_encode($certificateInfo, JSON_PRETTY_PRINT));
        chmod($infoPath, 0644);
    }

    /**
     * Update Laravel configuration for XML signing
     */
    private function updateSigningConfiguration(): void
    {
        // Update config values in memory for current request
        config([
            'mk.xml_signing.private_key_path' => storage_path('app/' . self::CERT_STORAGE_PATH . '/' . self::PRIVATE_KEY_FILENAME),
            'mk.xml_signing.certificate_path' => storage_path('app/' . self::CERT_STORAGE_PATH . '/' . self::CERTIFICATE_FILENAME),
        ]);
    }

    /**
     * Check if certificate is currently valid
     */
    private function isCertificateValid(array $certificateInfo): bool
    {
        if (!isset($certificateInfo['valid_to'])) {
            return false;
        }

        try {
            $validTo = strtotime($certificateInfo['valid_to']);
            return $validTo !== false && time() <= $validTo;
        } catch (\Exception $e) {
            return false;
        }
    }
}

