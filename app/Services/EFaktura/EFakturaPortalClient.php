<?php

namespace App\Services\EFaktura;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * UJP e-Faktura portal client.
 *
 * Handles both outbound submission and inbound inbox polling
 * via the UJP e-Invoice portal.
 *
 * Supports two modes (configured in config/mk.php):
 * - 'portal': Legacy portal scraping (current)
 * - 'api': Official REST API (when available)
 */
class EFakturaPortalClient
{
    protected string $portalUrl;

    protected string $username;

    protected string $password;

    protected string $mode;

    protected int $timeout;

    public function __construct()
    {
        $this->portalUrl = config('mk.efaktura.portal_url', '');
        $this->username = config('mk.efaktura.username', '');
        $this->password = config('mk.efaktura.password', '');
        $this->mode = config('mk.efaktura.mode', 'portal');
        $this->timeout = config('mk.efaktura.timeout', 60);
    }

    /**
     * Poll the inbox for new incoming e-invoices.
     *
     * Returns an array of inbox items, each containing:
     * - portal_id: Unique identifier from the portal
     * - xml: UBL XML content of the invoice
     * - received_at: When the invoice was received
     * - status: Portal status of the invoice
     *
     * @return array<int, array{portal_id: string, xml: string, received_at: string, status: string}>
     */
    public function pollInbox(): array
    {
        if ($this->mode === 'api') {
            return $this->pollInboxViaApi();
        }

        return $this->pollInboxViaPortal();
    }

    /**
     * Poll inbox via the official UJP REST API.
     */
    protected function pollInboxViaApi(): array
    {
        $apiUrl = config('mk.efaktura.api_url', '');
        $apiKey = config('mk.efaktura.api_key', '');

        if (empty($apiUrl) || empty($apiKey)) {
            Log::warning('EFakturaPortalClient: API mode configured but API URL or key missing');

            return [];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->connectTimeout(10)
                ->withHeaders([
                    'Authorization' => "Bearer {$apiKey}",
                    'Accept' => 'application/json',
                ])
                ->get("{$apiUrl}/inbox", [
                    'status' => 'new',
                ]);

            if (! $response->successful()) {
                Log::error('EFakturaPortalClient: API inbox poll failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $items = $response->json('data') ?? [];

            return array_map(function ($item) {
                return [
                    'portal_id' => $item['id'] ?? $item['document_id'] ?? '',
                    'xml' => $item['xml'] ?? $item['ubl_content'] ?? '',
                    'received_at' => $item['received_at'] ?? $item['date'] ?? now()->toIso8601String(),
                    'status' => $item['status'] ?? 'new',
                ];
            }, $items);
        } catch (\Throwable $e) {
            Log::error('EFakturaPortalClient: API inbox poll exception', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Poll inbox via portal scraping.
     *
     * Uses the efaktura_download.php tool to fetch inbox items
     * from the UJP web portal.
     */
    protected function pollInboxViaPortal(): array
    {
        if (empty($this->portalUrl) || empty($this->username)) {
            Log::info('EFakturaPortalClient: Portal credentials not configured');

            return [];
        }

        try {
            $toolPath = base_path('tools/efaktura_download.php');

            if (! file_exists($toolPath)) {
                Log::warning('EFakturaPortalClient: efaktura_download.php tool not found');

                return [];
            }

            $command = sprintf(
                'php %s --action=inbox --username=%s --password=%s --portal_url=%s 2>&1',
                escapeshellarg($toolPath),
                escapeshellarg($this->username),
                escapeshellarg($this->password),
                escapeshellarg($this->portalUrl)
            );

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            $outputStr = implode("\n", $output);

            if ($returnCode !== 0) {
                Log::error('EFakturaPortalClient: Portal inbox tool returned error', [
                    'return_code' => $returnCode,
                    'output' => substr($outputStr, 0, 500),
                ]);

                return [];
            }

            // Parse JSON output from the tool
            $result = json_decode($outputStr, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('EFakturaPortalClient: Invalid JSON from inbox tool', [
                    'output' => substr($outputStr, 0, 200),
                ]);

                return [];
            }

            return $result['items'] ?? [];
        } catch (\Throwable $e) {
            Log::error('EFakturaPortalClient: Portal inbox poll exception', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Download a specific invoice XML from the portal.
     *
     * @param  string  $portalInboxId  Portal document ID
     * @return string|null UBL XML content
     */
    public function downloadInvoiceXml(string $portalInboxId): ?string
    {
        if ($this->mode === 'api') {
            $apiUrl = config('mk.efaktura.api_url', '');
            $apiKey = config('mk.efaktura.api_key', '');

            try {
                $response = Http::timeout($this->timeout)
                    ->withHeaders(['Authorization' => "Bearer {$apiKey}"])
                    ->get("{$apiUrl}/inbox/{$portalInboxId}/xml");

                return $response->successful() ? $response->body() : null;
            } catch (\Throwable $e) {
                Log::error('EFakturaPortalClient: Failed to download invoice XML', [
                    'portal_inbox_id' => $portalInboxId,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        }

        return null;
    }
}
// CLAUDE-CHECKPOINT
