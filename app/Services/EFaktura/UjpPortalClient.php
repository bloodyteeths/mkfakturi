<?php

namespace App\Services\EFaktura;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

/**
 * UJP E-Invoice Portal Client (legacy scraping mode).
 *
 * Wraps the tools/efaktura_upload.php CLI tool into a proper service class
 * that conforms to the same interface shape as UjpApiClient. This allows
 * the SubmitEInvoiceJob to switch between API and portal modes transparently.
 *
 * Portal mode uses web scraping via cURL to interact with the UJP
 * e-Faktura portal (https://e-ujp.ujp.gov.mk). This is the legacy approach
 * and will be replaced by UjpApiClient once the official REST API is available.
 *
 * Configuration is read from config/mk.php 'efaktura' section:
 * - portal_url: Base URL for the UJP portal
 * - username: Portal login username
 * - password: Portal login password
 * - timeout: HTTP request timeout in seconds
 *
 * @see UjpApiClient for the API-based alternative
 * @see tools/efaktura_upload.php for the underlying CLI tool
 */
class UjpPortalClient
{
    /**
     * Portal base URL.
     */
    protected string $portalUrl;

    /**
     * Portal username.
     */
    protected string $username;

    /**
     * Portal password.
     */
    protected string $password;

    /**
     * Process execution timeout in seconds.
     */
    protected int $timeout;

    /**
     * Create a new UjpPortalClient instance.
     *
     * Reads configuration from config/mk.php efaktura section.
     */
    public function __construct()
    {
        $config = config('mk.efaktura', []);

        $this->portalUrl = $config['portal_url'] ?? 'https://e-ujp.ujp.gov.mk';
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
        $this->timeout = (int) ($config['timeout'] ?? 60);
    }

    /**
     * Submit a signed UBL XML invoice via the portal upload tool.
     *
     * Saves the signed XML to a temporary file, then executes
     * tools/efaktura_upload.php via the Process facade. Parses the
     * tool's text output into the same array shape as UjpApiClient::submitInvoice().
     *
     * @param  string  $signedXml  The digitally signed UBL XML content
     * @return array{success: bool, status: string, receipt_number: string|null, response: array}
     *
     * @throws \RuntimeException If the upload tool is not found or execution fails
     */
    public function submitInvoice(string $signedXml): array
    {
        Log::info('UjpPortalClient: Submitting invoice via portal tool', [
            'xml_size' => strlen($signedXml),
        ]);

        $uploadToolPath = base_path('tools/efaktura_upload.php');

        if (! file_exists($uploadToolPath)) {
            throw new \RuntimeException(
                "E-faktura upload tool not found: {$uploadToolPath}"
            );
        }

        try {
            // Save signed XML to temporary file
            $tempXmlPath = $this->saveToTempFile($signedXml, 'einvoice', '.xml');

            // Execute upload tool via Process facade
            $result = Process::timeout($this->timeout)
                ->run([
                    'php',
                    $uploadToolPath,
                    '--xml=' . $tempXmlPath,
                    '--mode=portal',
                ]);

            // Clean up temporary file
            @unlink($tempXmlPath);

            // Check if process was successful
            if (! $result->successful()) {
                Log::error('UjpPortalClient: Upload tool execution failed', [
                    'exit_code' => $result->exitCode(),
                    'error' => substr($result->errorOutput(), 0, 500),
                ]);

                return [
                    'success' => false,
                    'status' => 'failed',
                    'receipt_number' => null,
                    'response' => [
                        'raw_output' => $result->output(),
                        'error_output' => $result->errorOutput(),
                    ],
                    'error_message' => 'Upload tool execution failed: ' . $result->errorOutput(),
                ];
            }

            // Parse output from upload tool
            $uploadResult = $this->parseUploadToolOutput($result->output());

            Log::info('UjpPortalClient: Portal submission completed', [
                'result' => $uploadResult,
            ]);

            return $uploadResult;

        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('UjpPortalClient: Portal submission exception', [
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(
                'Portal submission failed: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

    /**
     * Check the status of a previously submitted invoice.
     *
     * Portal mode does not support programmatic status checking.
     * Always returns 'unknown' status. Use UjpApiClient for status checks.
     *
     * @param  string  $receiptNumber  The receipt number from submission
     * @return array{status: string, details: array}
     */
    public function checkStatus(string $receiptNumber): array
    {
        Log::info('UjpPortalClient: Status check not supported in portal mode', [
            'receipt_number' => $receiptNumber,
        ]);

        return [
            'status' => 'unknown',
            'details' => [
                'message' => 'Status checking is not supported in portal mode. Switch to API mode or check the UJP portal manually.',
            ],
        ];
    }

    /**
     * Parse the text output from efaktura_upload.php tool.
     *
     * The tool outputs lines like:
     *   Success: Yes
     *   Method: portal
     *   Upload ID: MK_20260212120000_1234
     *   Status: accepted
     *   Receipt: MK202602121200001234
     *   Timestamp: 2026-02-12 12:00:00
     *
     * This method converts that text into a structured array matching
     * the UjpApiClient::submitInvoice() return shape.
     *
     * @param  string  $output  Raw text output from the upload tool
     * @return array{success: bool, status: string, receipt_number: string|null, response: array}
     */
    protected function parseUploadToolOutput(string $output): array
    {
        $result = [
            'success' => false,
            'status' => 'unknown',
            'receipt_number' => null,
            'response' => [
                'raw_output' => $output,
                'upload_id' => null,
            ],
        ];

        // Look for success indicators
        if (preg_match('/Success:\s*(Yes|true)/i', $output)) {
            $result['success'] = true;
        }

        // Extract status
        if (preg_match('/Status:\s*(\w+)/i', $output, $matches)) {
            $result['status'] = strtolower(trim($matches[1]));
        }

        // Extract upload ID
        if (preg_match('/Upload ID:\s*([^\s\n]+)/i', $output, $matches)) {
            $result['response']['upload_id'] = trim($matches[1]);
        }

        // Extract receipt number
        if (preg_match('/Receipt:\s*([^\s\n]+)/i', $output, $matches)) {
            $result['receipt_number'] = trim($matches[1]);
        }

        return $result;
    }

    /**
     * Save content to a temporary file.
     *
     * @param  string  $content  File content
     * @param  string  $prefix  Filename prefix
     * @param  string  $extension  File extension
     * @return string Temporary file path
     */
    protected function saveToTempFile(string $content, string $prefix = 'temp', string $extension = '.tmp'): string
    {
        $tempDir = sys_get_temp_dir();
        $tempFile = tempnam($tempDir, $prefix) . $extension;

        file_put_contents($tempFile, $content);

        return $tempFile;
    }
}
