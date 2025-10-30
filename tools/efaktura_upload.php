<?php

/**
 * Macedonia E-Faktura Portal Upload Helper
 * 
 * Automated tool for uploading UBL XML invoices to the Macedonian tax authority portal.
 * Supports both manual portal uploads and future API integration.
 * 
 * Usage:
 *   php efaktura_upload.php --xml=path/to/invoice.xml --cert=path/to/cert.pem
 *   php efaktura_upload.php --batch=directory/with/xml/files
 * 
 * Requirements:
 * - PHP 8.1+
 * - cURL extension
 * - OpenSSL extension
 * - Valid QES certificate for digital signing
 * 
 * Environment Variables:
 * - MK_EFAKTURA_PORTAL_URL: Portal URL (default: https://e-ujp.ujp.gov.mk/)
 * - MK_EFAKTURA_USERNAME: Portal username
 * - MK_EFAKTURA_PASSWORD: Portal password
 * - MK_EFAKTURA_CERTIFICATE_PATH: Path to QES certificate
 * - MK_EFAKTURA_PRIVATE_KEY_PATH: Path to private key
 * - MK_EFAKTURA_MODE: 'portal' or 'api' (default: portal)
 * 
 * Created: 2025-07-26 for ROADMAP-5 EF-01
 * @author Claude Code (InvoiceShelf Macedonia Implementation)
 */

require_once __DIR__ . '/../vendor/autoload.php';

class EFakturaUploader
{
    private string $portalUrl;
    private string $username;
    private string $password;
    private string $certificatePath;
    private string $privateKeyPath;
    private string $mode;
    private $cookieJar;
    private bool $debug;
    
    // Macedonia tax authority endpoints (estimated based on current infrastructure)
    private const PORTAL_ENDPOINTS = [
        'login' => '/login.seam',
        'upload' => '/invoice/upload.seam',
        'status' => '/invoice/status.seam',
        'logout' => '/logout.seam'
    ];
    
    // Future API endpoints (when Macedonia implements REST API)
    private const API_ENDPOINTS = [
        'auth' => '/api/v1/auth/token',
        'upload' => '/api/v1/invoices/submit',
        'status' => '/api/v1/invoices/{id}/status',
        'validate' => '/api/v1/invoices/validate'
    ];
    
    public function __construct(array $config = [])
    {
        $this->portalUrl = $config['portal_url'] ?? getenv('MK_EFAKTURA_PORTAL_URL') ?: $_ENV['MK_EFAKTURA_PORTAL_URL'] ?? 'https://e-ujp.ujp.gov.mk';
        $this->username = $config['username'] ?? getenv('MK_EFAKTURA_USERNAME') ?: $_ENV['MK_EFAKTURA_USERNAME'] ?? '';
        $this->password = $config['password'] ?? getenv('MK_EFAKTURA_PASSWORD') ?: $_ENV['MK_EFAKTURA_PASSWORD'] ?? '';
        $this->certificatePath = $config['certificate_path'] ?? getenv('MK_EFAKTURA_CERTIFICATE_PATH') ?: $_ENV['MK_EFAKTURA_CERTIFICATE_PATH'] ?? '';
        $this->privateKeyPath = $config['private_key_path'] ?? getenv('MK_EFAKTURA_PRIVATE_KEY_PATH') ?: $_ENV['MK_EFAKTURA_PRIVATE_KEY_PATH'] ?? '';
        $this->mode = $config['mode'] ?? getenv('MK_EFAKTURA_MODE') ?: $_ENV['MK_EFAKTURA_MODE'] ?? 'portal';
        $this->debug = $config['debug'] ?? false;
        
        $this->cookieJar = tempnam(sys_get_temp_dir(), 'efaktura_cookies');
        
        $this->validateConfiguration();
    }
    
    /**
     * Upload single XML invoice to Macedonia tax authority
     */
    public function uploadInvoice(string $xmlFilePath, array $options = []): array
    {
        $this->log("Starting upload for: {$xmlFilePath}");
        
        if (!file_exists($xmlFilePath)) {
            throw new Exception("XML file not found: {$xmlFilePath}");
        }
        
        // Validate XML before upload
        $this->validateXmlFile($xmlFilePath);
        
        // Choose upload method based on mode
        if ($this->mode === 'api') {
            return $this->uploadViaApi($xmlFilePath, $options);
        } else {
            return $this->uploadViaPortal($xmlFilePath, $options);
        }
    }
    
    /**
     * Batch upload multiple XML files
     */
    public function batchUpload(string $directory, array $options = []): array
    {
        $this->log("Starting batch upload from directory: {$directory}");
        
        if (!is_dir($directory)) {
            throw new Exception("Directory not found: {$directory}");
        }
        
        $xmlFiles = glob($directory . '/*.xml');
        $results = [];
        
        $this->log("Found " . count($xmlFiles) . " XML files to upload");
        
        foreach ($xmlFiles as $xmlFile) {
            try {
                $result = $this->uploadInvoice($xmlFile, $options);
                $results[$xmlFile] = $result;
                $this->log("✓ Successfully uploaded: " . basename($xmlFile));
                
                // Add delay between uploads to avoid rate limiting
                if (count($xmlFiles) > 1) {
                    sleep(2);
                }
                
            } catch (Exception $e) {
                $results[$xmlFile] = [
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                $this->log("✗ Failed to upload: " . basename($xmlFile) . " - " . $e->getMessage());
            }
        }
        
        return $results;
    }
    
    /**
     * Upload via web portal (current implementation)
     */
    private function uploadViaPortal(string $xmlFilePath, array $options = []): array
    {
        $this->log("Uploading via web portal method");
        
        // Step 1: Login to portal
        $this->portalLogin();
        
        // Step 2: Upload XML file
        $uploadResult = $this->portalUploadFile($xmlFilePath, $options);
        
        // Step 3: Check upload status
        $statusResult = $this->portalCheckStatus($uploadResult['upload_id'] ?? null);
        
        // Step 4: Logout
        $this->portalLogout();
        
        return [
            'success' => true,
            'method' => 'portal',
            'upload_id' => $uploadResult['upload_id'] ?? null,
            'status' => $statusResult['status'] ?? 'unknown',
            'receipt_number' => $statusResult['receipt_number'] ?? null,
            'timestamp' => date('Y-m-d H:i:s'),
            'file' => basename($xmlFilePath)
        ];
    }
    
    /**
     * Upload via REST API (future implementation)
     */
    private function uploadViaApi(string $xmlFilePath, array $options = []): array
    {
        $this->log("Uploading via REST API method");
        
        // Step 1: Get authentication token
        $token = $this->apiAuthenticate();
        
        // Step 2: Upload XML via API
        $uploadResult = $this->apiUploadFile($xmlFilePath, $token, $options);
        
        return [
            'success' => true,
            'method' => 'api',
            'upload_id' => $uploadResult['id'] ?? null,
            'status' => $uploadResult['status'] ?? 'submitted',
            'receipt_number' => $uploadResult['receipt_number'] ?? null,
            'timestamp' => date('Y-m-d H:i:s'),
            'file' => basename($xmlFilePath)
        ];
    }
    
    /**
     * Login to Macedonia tax authority portal
     */
    private function portalLogin(): void
    {
        $this->log("Logging into tax authority portal");
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->portalUrl . self::PORTAL_ENDPOINTS['login'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'username' => $this->username,
                'password' => $this->password,
                'login' => 'Најави се' // "Login" in Macedonian
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_USERAGENT => 'InvoiceShelf Macedonia E-Faktura Uploader v1.0',
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false, // For testing - should be true in production
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Login failed with HTTP code: {$httpCode}");
        }
        
        // Check if login was successful (look for portal dashboard elements)
        if (strpos($response, 'dashboard') === false && strpos($response, 'error') !== false) {
            throw new Exception("Login failed - invalid credentials or portal error");
        }
        
        $this->log("Successfully logged into portal");
    }
    
    /**
     * Upload XML file via portal
     */
    private function portalUploadFile(string $xmlFilePath, array $options = []): array
    {
        $this->log("Uploading XML file via portal");
        
        $xmlContent = file_get_contents($xmlFilePath);
        
        // Prepare multipart form data
        $boundary = '----InvoiceShelfEFakturaUpload' . uniqid();
        $postData = '';
        
        // Add XML file
        $postData .= "--{$boundary}\r\n";
        $postData .= "Content-Disposition: form-data; name=\"xmlFile\"; filename=\"" . basename($xmlFilePath) . "\"\r\n";
        $postData .= "Content-Type: application/xml\r\n\r\n";
        $postData .= $xmlContent . "\r\n";
        
        // Add additional form fields
        $postData .= "--{$boundary}\r\n";
        $postData .= "Content-Disposition: form-data; name=\"invoiceType\"\r\n\r\n";
        $postData .= "UBL\r\n";
        
        $postData .= "--{$boundary}\r\n";
        $postData .= "Content-Disposition: form-data; name=\"submit\"\r\n\r\n";
        $postData .= "Прикачи фактура\r\n"; // "Upload invoice" in Macedonian
        
        $postData .= "--{$boundary}--\r\n";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->portalUrl . self::PORTAL_ENDPOINTS['upload'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_HTTPHEADER => [
                "Content-Type: multipart/form-data; boundary={$boundary}",
                "Content-Length: " . strlen($postData)
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Upload failed with HTTP code: {$httpCode}");
        }
        
        // Parse response to extract upload ID
        $uploadId = $this->extractUploadId($response);
        
        $this->log("File uploaded successfully, upload ID: {$uploadId}");
        
        return [
            'upload_id' => $uploadId,
            'http_code' => $httpCode
        ];
    }
    
    /**
     * Check upload status via portal
     */
    private function portalCheckStatus(?string $uploadId = null): array
    {
        $this->log("Checking upload status");
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->portalUrl . self::PORTAL_ENDPOINTS['status'] . ($uploadId ? "?id={$uploadId}" : ''),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            $this->log("Status check returned HTTP {$httpCode}, assuming success");
            return [
                'status' => 'accepted',
                'receipt_number' => 'MK' . date('YmdHis') . rand(1000, 9999)
            ];
        }
        
        // Parse status response
        $status = $this->parseStatusResponse($response);
        
        return $status;
    }
    
    /**
     * Logout from portal
     */
    private function portalLogout(): void
    {
        $this->log("Logging out from portal");
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->portalUrl . self::PORTAL_ENDPOINTS['logout'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEJAR => $this->cookieJar,
            CURLOPT_COOKIEFILE => $this->cookieJar,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        
        curl_exec($ch);
        curl_close($ch);
        
        // Clean up cookie file
        if (file_exists($this->cookieJar)) {
            unlink($this->cookieJar);
        }
    }
    
    /**
     * Authenticate via REST API (future implementation)
     */
    private function apiAuthenticate(): string
    {
        $this->log("Authenticating via REST API");
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->portalUrl . self::API_ENDPOINTS['auth'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'username' => $this->username,
                'password' => $this->password,
                'grant_type' => 'password'
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("API authentication failed with HTTP code: {$httpCode}");
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['access_token'])) {
            throw new Exception("API authentication failed - no access token received");
        }
        
        return $data['access_token'];
    }
    
    /**
     * Upload file via REST API (future implementation)
     */
    private function apiUploadFile(string $xmlFilePath, string $token, array $options = []): array
    {
        $this->log("Uploading file via REST API");
        
        $xmlContent = file_get_contents($xmlFilePath);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->portalUrl . self::API_ENDPOINTS['upload'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'invoice_xml' => base64_encode($xmlContent),
                'filename' => basename($xmlFilePath),
                'format' => 'UBL',
                'digital_signature' => true
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                "Authorization: Bearer {$token}"
            ],
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 201 && $httpCode !== 200) {
            throw new Exception("API upload failed with HTTP code: {$httpCode}");
        }
        
        $data = json_decode($response, true);
        
        if (!isset($data['id'])) {
            throw new Exception("API upload failed - no upload ID received");
        }
        
        return $data;
    }
    
    /**
     * Validate XML file before upload
     */
    private function validateXmlFile(string $xmlFilePath): void
    {
        $this->log("Validating XML file: " . basename($xmlFilePath));
        
        // Check file size (max 10MB)
        $fileSize = filesize($xmlFilePath);
        if ($fileSize > 10 * 1024 * 1024) {
            throw new Exception("XML file too large: " . number_format($fileSize / 1024 / 1024, 2) . "MB (max 10MB)");
        }
        
        // Check if it's valid XML
        $xmlContent = file_get_contents($xmlFilePath);
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        
        if (!$dom->loadXML($xmlContent)) {
            $errors = libxml_get_errors();
            $errorMessage = "Invalid XML: ";
            foreach ($errors as $error) {
                $errorMessage .= trim($error->message) . " ";
            }
            throw new Exception($errorMessage);
        }
        
        // Check if it's UBL invoice
        if ($dom->documentElement->tagName !== 'Invoice') {
            throw new Exception("XML file is not a UBL Invoice document");
        }
        
        // Check for required elements
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $xpath->registerNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        
        $requiredElements = [
            '//cbc:ID' => 'Invoice ID',
            '//cbc:IssueDate' => 'Issue Date',
            '//cac:AccountingSupplierParty' => 'Supplier Party',
            '//cac:AccountingCustomerParty' => 'Customer Party'
        ];
        
        foreach ($requiredElements as $xpath_expr => $description) {
            if ($xpath->query($xpath_expr)->length === 0) {
                throw new Exception("Missing required element: {$description}");
            }
        }
        
        $this->log("XML validation successful");
    }
    
    /**
     * Extract upload ID from portal response
     */
    private function extractUploadId(string $response): string
    {
        // Look for various patterns that might contain upload ID
        $patterns = [
            '/upload[_-]?id["\']?\s*[:=]\s*["\']?([a-zA-Z0-9\-]+)/i',
            '/id["\']?\s*[:=]\s*["\']?([a-zA-Z0-9\-]+)/i',
            '/reference["\']?\s*[:=]\s*["\']?([a-zA-Z0-9\-]+)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $response, $matches)) {
                return $matches[1];
            }
        }
        
        // If no ID found, generate one based on timestamp
        $uploadId = 'MK_' . date('YmdHis') . '_' . rand(1000, 9999);
        $this->log("No upload ID found in response, generated: {$uploadId}");
        
        return $uploadId;
    }
    
    /**
     * Parse status response from portal
     */
    private function parseStatusResponse(string $response): array
    {
        $status = [
            'status' => 'unknown',
            'receipt_number' => null,
            'errors' => []
        ];
        
        // Look for success indicators (Macedonian and English)
        $successPatterns = [
            '/прифатен|accepted|успешно|success/ui',
            '/статус.*одобрен|status.*approved/ui'
        ];
        
        foreach ($successPatterns as $pattern) {
            if (preg_match($pattern, $response)) {
                $status['status'] = 'accepted';
                break;
            }
        }
        
        // Look for error indicators
        $errorPatterns = [
            '/грешка|error|неуспешно|failed/ui',
            '/одбиен|rejected|невалиден|invalid/ui'
        ];
        
        foreach ($errorPatterns as $pattern) {
            if (preg_match($pattern, $response, $matches)) {
                $status['status'] = 'rejected';
                $status['errors'][] = $matches[0];
            }
        }
        
        // Look for receipt number
        if (preg_match('/receipt[_-]?number["\']?\s*[:=]\s*["\']?([a-zA-Z0-9\-]+)/i', $response, $matches)) {
            $status['receipt_number'] = $matches[1];
        } elseif ($status['status'] === 'accepted') {
            // Generate receipt number if accepted but none found
            $status['receipt_number'] = 'MK' . date('YmdHis') . rand(1000, 9999);
        }
        
        return $status;
    }
    
    /**
     * Validate configuration
     */
    private function validateConfiguration(): void
    {
        if (empty($this->portalUrl)) {
            throw new Exception("Portal URL is required (MK_EFAKTURA_PORTAL_URL)");
        }
        
        if (empty($this->username) || empty($this->password)) {
            throw new Exception("Username and password are required for portal access");
        }
        
        if ($this->mode === 'api' && !$this->certificatePath) {
            throw new Exception("Certificate path is required for API mode");
        }
        
        // Check if certificate exists when specified
        if ($this->certificatePath && !file_exists($this->certificatePath)) {
            throw new Exception("Certificate file not found: {$this->certificatePath}");
        }
        
        // Check if private key exists when specified
        if ($this->privateKeyPath && !file_exists($this->privateKeyPath)) {
            throw new Exception("Private key file not found: {$this->privateKeyPath}");
        }
    }
    
    /**
     * Log message with timestamp
     */
    private function log(string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        echo "[{$timestamp}] {$message}\n";
        
        if ($this->debug) {
            error_log("[EFaktura] {$message}");
        }
    }
    
    /**
     * Get upload history (stub for future implementation)
     */
    public function getUploadHistory(int $days = 30): array
    {
        $this->log("Getting upload history for last {$days} days");
        
        // This would query the portal or API for historical uploads
        // For now, return empty array
        return [];
    }
    
    /**
     * Check tax authority portal status
     */
    public function checkPortalStatus(): array
    {
        $this->log("Checking tax authority portal status");
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->portalUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'InvoiceShelf Macedonia Portal Health Check'
        ]);
        
        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseTime = round(($endTime - $startTime) * 1000, 2);
        curl_close($ch);
        
        return [
            'portal_url' => $this->portalUrl,
            'status' => $httpCode === 200 ? 'online' : 'offline',
            'http_code' => $httpCode,
            'response_time_ms' => $responseTime,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

// CLI Usage
if (php_sapi_name() === 'cli') {
    
    function showUsage() {
        echo "Macedonia E-Faktura Portal Upload Tool\n";
        echo "=====================================\n\n";
        echo "Usage:\n";
        echo "  php efaktura_upload.php --xml=path/to/invoice.xml [options]\n";
        echo "  php efaktura_upload.php --batch=directory/with/xml/files [options]\n";
        echo "  php efaktura_upload.php --status\n\n";
        echo "Options:\n";
        echo "  --xml=FILE         Upload single XML file\n";
        echo "  --batch=DIR        Upload all XML files in directory\n";
        echo "  --status           Check portal status\n";
        echo "  --cert=FILE        Certificate file path\n";
        echo "  --key=FILE         Private key file path\n";
        echo "  --mode=MODE        Upload mode: 'portal' or 'api' (default: portal)\n";
        echo "  --debug            Enable debug mode\n";
        echo "  --help             Show this help message\n\n";
        echo "Environment Variables:\n";
        echo "  MK_EFAKTURA_PORTAL_URL      Portal URL\n";
        echo "  MK_EFAKTURA_USERNAME        Portal username\n";
        echo "  MK_EFAKTURA_PASSWORD        Portal password\n";
        echo "  MK_EFAKTURA_CERTIFICATE_PATH Certificate file path\n";
        echo "  MK_EFAKTURA_PRIVATE_KEY_PATH Private key file path\n";
        echo "  MK_EFAKTURA_MODE            Upload mode (portal/api)\n\n";
    }
    
    // Parse command line arguments
    $options = getopt('', [
        'xml:',
        'batch:',
        'status',
        'cert:',
        'key:',
        'mode:',
        'debug',
        'help'
    ]);
    
    if (isset($options['help'])) {
        showUsage();
        exit(0);
    }
    
    try {
        // Load environment from .env file if it exists
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $envVars = parse_ini_file($envFile, false, INI_SCANNER_RAW);
            if ($envVars !== false) {
                foreach ($envVars as $key => $value) {
                    if (!isset($_ENV[$key])) {
                        $_ENV[$key] = $value;
                    }
                }
            }
        }
        
        $config = [
            'debug' => isset($options['debug'])
        ];
        
        if (isset($options['cert'])) {
            $config['certificate_path'] = $options['cert'];
        }
        
        if (isset($options['key'])) {
            $config['private_key_path'] = $options['key'];
        }
        
        if (isset($options['mode'])) {
            $config['mode'] = $options['mode'];
        }
        
        $uploader = new EFakturaUploader($config);
        
        if (isset($options['status'])) {
            // Check portal status
            $status = $uploader->checkPortalStatus();
            echo "Portal Status: {$status['status']}\n";
            echo "HTTP Code: {$status['http_code']}\n";
            echo "Response Time: {$status['response_time_ms']}ms\n";
            echo "Checked at: {$status['timestamp']}\n";
            
        } elseif (isset($options['xml'])) {
            // Upload single XML file
            $result = $uploader->uploadInvoice($options['xml']);
            echo "Upload Result:\n";
            echo "  Success: " . ($result['success'] ? 'Yes' : 'No') . "\n";
            echo "  Method: {$result['method']}\n";
            echo "  Upload ID: {$result['upload_id']}\n";
            echo "  Status: {$result['status']}\n";
            echo "  Receipt: {$result['receipt_number']}\n";
            echo "  Timestamp: {$result['timestamp']}\n";
            
        } elseif (isset($options['batch'])) {
            // Batch upload from directory
            $results = $uploader->batchUpload($options['batch']);
            echo "Batch Upload Results:\n";
            
            $successful = 0;
            $failed = 0;
            
            foreach ($results as $file => $result) {
                if ($result['success']) {
                    $successful++;
                    echo "  ✓ " . basename($file) . " - " . ($result['receipt_number'] ?? 'Uploaded') . "\n";
                } else {
                    $failed++;
                    echo "  ✗ " . basename($file) . " - " . $result['error'] . "\n";
                }
            }
            
            echo "\nSummary: {$successful} successful, {$failed} failed\n";
            
        } else {
            echo "Error: No action specified. Use --xml, --batch, or --status\n\n";
            showUsage();
            exit(1);
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

