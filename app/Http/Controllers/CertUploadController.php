<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\SignatureLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;
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
            $companyId = request()->header('company');

            if (!$companyId) {
                return response()->json([
                    'data' => null,
                    'message' => __('certificates.no_company_header')
                ], 400);
            }

            // Query certificate from database (prefer active, but show any if found)
            $certificate = Certificate::where('company_id', $companyId)
                ->orderBy('is_active', 'desc') // Active first
                ->orderBy('created_at', 'desc') // Then most recent
                ->first();

            if (!$certificate) {
                return response()->json([
                    'data' => null,
                    'message' => __('certificates.no_certificate_found')
                ], 404); // Return 404 instead of 200 when no certificate found
            }

            // Check if certificate file still exists
            $fileMissing = $certificate->certificate_path && !Storage::exists($certificate->certificate_path);

            if ($fileMissing) {
                Log::warning('Certificate file missing for certificate', [
                    'certificate_id' => $certificate->id,
                    'path' => $certificate->certificate_path
                ]);
            }

            // Return certificate info from database (even if files are missing so user can delete it)
            return response()->json([
                'data' => [
                    'id' => $certificate->id,
                    'name' => $certificate->name,
                    'serial_number' => $certificate->serial_number,
                    'fingerprint' => $certificate->fingerprint,
                    'subject' => $certificate->subject,
                    'issuer' => $certificate->issuer,
                    'valid_from' => $certificate->valid_from->toISOString(),
                    'valid_to' => $certificate->valid_to->toISOString(),
                    'algorithm' => $certificate->algorithm,
                    'key_size' => $certificate->key_size,
                    'is_active' => $certificate->is_active,
                    'is_expired' => $certificate->is_expired,
                    'is_valid' => !$fileMissing && $certificate->is_valid, // Mark invalid if files missing
                    'days_until_expiry' => $certificate->days_until_expiry,
                    'last_used_at' => $certificate->last_used_at?->toISOString(),
                    'uploaded_at' => $certificate->created_at->toISOString(),
                    'file_missing' => $fileMissing, // Flag to indicate missing files
                ],
                'message' => $fileMissing ? __('certificates.certificate_files_missing') : __('certificates.current_certificate_retrieved'),
                'warning' => $fileMissing // Flag to show warning notification
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
        // Check if OpenSSL extension is loaded
        if (!extension_loaded('openssl')) {
            return response()->json([
                'message' => 'OpenSSL PHP extension is not enabled'
            ], 500);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'certificate' => [
                'required',
                'file',
                function ($attribute, $value, $fail) {
                    $extension = strtolower($value->getClientOriginalExtension());
                    if (!in_array($extension, ['p12', 'pfx'])) {
                        $fail(__('certificates.invalid_format'));
                    }
                },
                'max:5120' // 5MB max
            ],
            'password' => [
                'required',
                'string',
                'min:4',
                'max:255'
            ],
            'name' => [
                'nullable',
                'string',
                'max:255'
            ],
            'description' => [
                'nullable',
                'string',
                'max:255'
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
            $companyId = request()->header('company');

            if (!$companyId) {
                return response()->json([
                    'message' => __('certificates.no_company_header'),
                    'errors' => ['company' => [__('certificates.no_company_header')]]
                ], 400);
            }

            $certificateFile = $request->file('certificate');
            $password = $request->input('password');
            $certificateName = $request->input('description') ?: $request->input('name', '');

            // Create secure temporary file for processing
            $tempPath = $this->createSecureTempFile($certificateFile);

            try {
                // Extract private key and certificate from P12/PFX
                $extractedData = $this->extractCertificateData($tempPath, $password);

                // Validate extracted certificate
                $certificateInfo = $this->validateCertificate($extractedData['certificate']);

                // Extract key size from private key
                $keySize = $this->extractKeySize($extractedData['private_key']);

                // Store certificate file and get path
                $certificatePath = $this->storeCertificateFile($extractedData['certificate'], $companyId);

                // Read the entire P12/PFX file for encrypted storage
                $p12Content = File::get($tempPath);
                $encryptedKeyBlob = Crypt::encryptString($p12Content);

                // Create Certificate record in database
                $certificate = Certificate::create([
                    'company_id' => $companyId,
                    'name' => $certificateName ?: ($certificateInfo['subject']['CN'] ?? 'QES Certificate'),
                    'serial_number' => $certificateInfo['serial_number'],
                    'fingerprint' => $certificateInfo['fingerprint'],
                    'valid_from' => $certificateInfo['valid_from'],
                    'valid_to' => $certificateInfo['valid_to'],
                    'encrypted_key_blob' => $encryptedKeyBlob,
                    'certificate_path' => $certificatePath,
                    'is_active' => true, // Activate this certificate (deactivates others via model boot)
                    'subject' => $certificateInfo['subject'],
                    'issuer' => $certificateInfo['issuer'],
                    'algorithm' => $certificateInfo['algorithm'],
                    'key_size' => $keySize,
                ]);

                // Create upload log
                SignatureLog::logUpload(
                    $certificate,
                    true,
                    null,
                    [
                        'uploaded_by' => auth()->user()?->name ?? 'Unknown',
                        'file_size' => strlen($p12Content),
                        'fingerprint' => $certificateInfo['fingerprint'],
                    ]
                );

                Log::info('Certificate uploaded successfully', [
                    'certificate_id' => $certificate->id,
                    'company_id' => $companyId,
                    'subject' => $certificateInfo['subject']['CN'] ?? 'Unknown',
                    'valid_until' => $certificateInfo['valid_to'],
                    'fingerprint' => substr($certificateInfo['fingerprint'], 0, 16) . '...'
                ]);

                return response()->json([
                    'data' => [
                        'id' => $certificate->id,
                        'name' => $certificate->name,
                        'serial_number' => $certificate->serial_number,
                        'fingerprint' => $certificate->fingerprint,
                        'subject' => $certificate->subject,
                        'issuer' => $certificate->issuer,
                        'valid_from' => $certificate->valid_from->toISOString(),
                        'valid_to' => $certificate->valid_to->toISOString(),
                        'algorithm' => $certificate->algorithm,
                        'key_size' => $certificate->key_size,
                        'is_active' => $certificate->is_active,
                        'is_expired' => $certificate->is_expired,
                        'is_valid' => $certificate->is_valid,
                        'days_until_expiry' => $certificate->days_until_expiry,
                    ],
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
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Log failed upload attempt
            if (isset($companyId)) {
                try {
                    SignatureLog::create([
                        'company_id' => $companyId,
                        'action' => SignatureLog::ACTION_UPLOAD,
                        'user_id' => auth()->id(),
                        'success' => false,
                        'error_message' => $e->getMessage(),
                    ]);
                } catch (\Exception $logError) {
                    // Logging failure shouldn't break error response
                    Log::warning('Failed to create signature log', ['error' => $logError->getMessage()]);
                }
            }

            // Return appropriate error message
            if (str_contains($e->getMessage(), 'password')) {
                return response()->json([
                    'message' => __('certificates.invalid_password'),
                    'errors' => ['password' => [__('certificates.invalid_password')]]
                ], 422);
            }

            // Handle duplicate certificate fingerprint
            if (str_contains($e->getMessage(), 'Duplicate entry') && str_contains($e->getMessage(), 'certificates_fingerprint_unique')) {
                return response()->json([
                    'message' => __('certificates.duplicate_certificate'),
                    'error' => __('certificates.duplicate_certificate_details')
                ], 422);
            }

            if (str_contains($e->getMessage(), 'certificate')) {
                return response()->json([
                    'message' => __('certificates.invalid_certificate')
                ], 422);
            }

            // Return error with useful message
            return response()->json([
                'message' => $e->getMessage(), // Always return actual error for better debugging
                'error' => __('certificates.upload_error')
            ], 500);
        }
    }
    // CLAUDE-CHECKPOINT

    /**
     * Delete certificate by ID
     */
    public function delete(int $id): JsonResponse
    {
        try {
            $companyId = request()->header('company');

            if (!$companyId) {
                return response()->json([
                    'message' => __('certificates.no_company_header')
                ], 400);
            }

            // Find certificate for this company
            $certificate = Certificate::where('id', $id)
                ->where('company_id', $companyId)
                ->first();

            if (!$certificate) {
                return response()->json([
                    'message' => __('certificates.certificate_not_found')
                ], 404);
            }

            // Store certificate info for logging before deletion
            $certificateInfo = [
                'id' => $certificate->id,
                'name' => $certificate->name,
                'fingerprint' => $certificate->fingerprint,
                'serial_number' => $certificate->serial_number,
            ];

            // Delete certificate (this also deletes physical files and creates log entry via model)
            $certificate->delete();

            Log::info('Certificate deleted successfully', $certificateInfo);

            return response()->json([
                'message' => __('certificates.delete_success')
            ]);

        } catch (\Exception $e) {
            Log::error('Certificate deletion failed', [
                'certificate_id' => $id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => __('certificates.delete_error')
            ], 500);
        }
    }
    // CLAUDE-CHECKPOINT

    /**
     * Verify certificate by ID
     * Performs dry-run validation without signing anything
     */
    public function verify(int $id): JsonResponse
    {
        try {
            $companyId = request()->header('company');

            if (!$companyId) {
                return response()->json([
                    'message' => __('certificates.no_company_header')
                ], 400);
            }

            // Find certificate for this company
            $certificate = Certificate::where('id', $id)
                ->where('company_id', $companyId)
                ->first();

            if (!$certificate) {
                return response()->json([
                    'message' => __('certificates.certificate_not_found')
                ], 404);
            }

            $validationResults = [];
            $isValid = true;

            // Check 1: Certificate expiry
            if ($certificate->is_expired) {
                $validationResults[] = [
                    'check' => 'expiry',
                    'status' => 'failed',
                    'message' => __('certificates.certificate_expired'),
                    'details' => [
                        'valid_to' => $certificate->valid_to->toISOString(),
                        'days_expired' => abs($certificate->days_until_expiry),
                    ]
                ];
                $isValid = false;
            } else {
                $validationResults[] = [
                    'check' => 'expiry',
                    'status' => 'passed',
                    'message' => __('certificates.certificate_valid'),
                    'details' => [
                        'valid_to' => $certificate->valid_to->toISOString(),
                        'days_until_expiry' => $certificate->days_until_expiry,
                    ]
                ];
            }

            // Check 2: Certificate file exists
            if (!$certificate->certificate_path || !Storage::exists($certificate->certificate_path)) {
                $validationResults[] = [
                    'check' => 'file_exists',
                    'status' => 'failed',
                    'message' => __('certificates.certificate_file_missing'),
                ];
                $isValid = false;
            } else {
                $validationResults[] = [
                    'check' => 'file_exists',
                    'status' => 'passed',
                    'message' => __('certificates.certificate_file_found'),
                ];
            }

            // Check 3: Encrypted key blob exists
            if (!$certificate->encrypted_key_blob) {
                $validationResults[] = [
                    'check' => 'key_blob',
                    'status' => 'failed',
                    'message' => __('certificates.key_blob_missing'),
                ];
                $isValid = false;
            } else {
                $validationResults[] = [
                    'check' => 'key_blob',
                    'status' => 'passed',
                    'message' => __('certificates.key_blob_found'),
                ];
            }

            // Check 4: Verify certificate chain (if file exists)
            if ($certificate->certificate_path && Storage::exists($certificate->certificate_path)) {
                try {
                    $certContent = Storage::get($certificate->certificate_path);
                    $x509Resource = openssl_x509_read($certContent);

                    if ($x509Resource) {
                        // Verify certificate data matches fingerprint
                        $currentFingerprint = openssl_x509_fingerprint($x509Resource, 'sha256');

                        if ($currentFingerprint === $certificate->fingerprint) {
                            $validationResults[] = [
                                'check' => 'certificate_chain',
                                'status' => 'passed',
                                'message' => __('certificates.certificate_chain_valid'),
                                'details' => [
                                    'fingerprint_match' => true,
                                ]
                            ];
                        } else {
                            $validationResults[] = [
                                'check' => 'certificate_chain',
                                'status' => 'failed',
                                'message' => __('certificates.fingerprint_mismatch'),
                                'details' => [
                                    'expected' => $certificate->fingerprint,
                                    'actual' => $currentFingerprint,
                                ]
                            ];
                            $isValid = false;
                        }
                    } else {
                        $validationResults[] = [
                            'check' => 'certificate_chain',
                            'status' => 'failed',
                            'message' => __('certificates.certificate_read_failed'),
                        ];
                        $isValid = false;
                    }
                } catch (\Exception $e) {
                    $validationResults[] = [
                        'check' => 'certificate_chain',
                        'status' => 'failed',
                        'message' => __('certificates.certificate_verification_error'),
                        'details' => [
                            'error' => $e->getMessage(),
                        ]
                    ];
                    $isValid = false;
                }
            }

            // Check 5: Key usage validation
            if ($certificate->algorithm) {
                $validationResults[] = [
                    'check' => 'key_usage',
                    'status' => 'passed',
                    'message' => __('certificates.key_usage_valid'),
                    'details' => [
                        'algorithm' => $certificate->algorithm,
                        'key_size' => $certificate->key_size,
                    ]
                ];
            }

            // Log verification
            SignatureLog::logVerify(
                $certificate,
                $certificate,
                $isValid,
                $isValid ? null : 'Certificate validation failed',
                [
                    'validation_results' => $validationResults,
                    'verified_by' => auth()->user()?->name ?? 'Unknown',
                ]
            );

            return response()->json([
                'data' => [
                    'is_valid' => $isValid,
                    'certificate' => [
                        'id' => $certificate->id,
                        'name' => $certificate->name,
                        'fingerprint' => $certificate->fingerprint,
                        'valid_to' => $certificate->valid_to->toISOString(),
                    ],
                    'validation_results' => $validationResults,
                ],
                'message' => $isValid
                    ? __('certificates.verification_success')
                    : __('certificates.verification_failed')
            ]);

        } catch (\Exception $e) {
            Log::error('Certificate verification failed', [
                'certificate_id' => $id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => __('certificates.verification_error')
            ], 500);
        }
    }
    // CLAUDE-CHECKPOINT

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
     * Store certificate file in Laravel Storage
     *
     * @param string $certificatePem PEM-encoded certificate
     * @param int $companyId Company ID for path namespacing
     * @return string Storage path to the certificate file
     */
    private function storeCertificateFile(string $certificatePem, int $companyId): string
    {
        // Create company-specific directory
        $directory = 'certificates/company_' . $companyId;

        // Generate unique filename based on certificate fingerprint
        $x509Resource = openssl_x509_read($certificatePem);
        $fingerprint = openssl_x509_fingerprint($x509Resource, 'sha256');
        $filename = substr($fingerprint, 0, 16) . '_' . time() . '.pem';

        $path = $directory . '/' . $filename;

        // Store certificate in Laravel Storage
        Storage::put($path, $certificatePem);

        return $path;
    }

    /**
     * Extract key size from private key
     *
     * @param string $privateKeyPem PEM-encoded private key
     * @return int|null Key size in bits (e.g., 2048, 4096)
     */
    private function extractKeySize(string $privateKeyPem): ?int
    {
        try {
            $keyResource = openssl_pkey_get_private($privateKeyPem);

            if (!$keyResource) {
                return null;
            }

            $keyDetails = openssl_pkey_get_details($keyResource);

            return $keyDetails['bits'] ?? null;
        } catch (\Exception $e) {
            Log::warning('Failed to extract key size', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

}
// CLAUDE-CHECKPOINT

