<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * MCP (Model Context Protocol) Client Service
 *
 * Handles communication with the internal MCP server for fetching
 * financial data and tools to provide context for AI analysis.
 */
class McpClient
{
    private string $mcpServerUrl;

    private string $bearerToken;

    private int $timeout;

    /**
     * Create a new MCP client instance
     */
    public function __construct()
    {
        // Use Laravel's own base URL for internal MCP endpoints
        $this->mcpServerUrl = config('app.url', 'http://localhost');
        $this->bearerToken = config('services.mcp.token', '');
        $this->timeout = config('services.mcp.timeout', 30);

        if (empty($this->bearerToken)) {
            Log::warning('MCP server token is not configured; MCP requests will use fallback responses');
        }
    }

    /**
     * Call an MCP tool and retrieve data
     *
     * @param  string  $tool  The tool name to call
     * @param  array<string, mixed>  $params  Parameters to pass to the tool
     * @return array<string, mixed> The tool's response data
     *
     * @throws McpException If the MCP call fails
     */
    public function call(string $tool, array $params): array
    {
        $endpoint = $this->getEndpointForTool($tool);

        if (empty($endpoint)) {
            throw new McpException("Unknown MCP tool: {$tool}");
        }

        $startTime = microtime(true);

        if (empty($this->bearerToken)) {
            $this->logMcpCall($tool, $params, null, 0, 0.0, 'Missing MCP token');
            throw new McpException('MCP server token is not configured');
        }

        try {
            $response = Http::withToken($this->bearerToken)
                ->timeout($this->timeout)
                ->post($this->mcpServerUrl.$endpoint, $params);

            $duration = microtime(true) - $startTime;

            if ($response->failed()) {
                $this->logMcpCall($tool, $params, null, $response->status(), $duration, $response->body());

                throw new McpException(
                    "MCP tool {$tool} failed with status {$response->status()}: {$response->body()}"
                );
            }

            $data = $response->json();

            $this->logMcpCall($tool, $params, $data, 200, $duration);

            return $data;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $duration = microtime(true) - $startTime;
            $this->logMcpCall($tool, $params, null, 0, $duration, $e->getMessage());

            throw new McpException("MCP server connection failed: {$e->getMessage()}", 0, $e);
        } catch (\Exception $e) {
            if ($e instanceof McpException) {
                throw $e;
            }

            $duration = microtime(true) - $startTime;
            $this->logMcpCall($tool, $params, null, 0, $duration, $e->getMessage());

            throw new McpException("MCP tool {$tool} error: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Get the endpoint path for a given tool name
     *
     * @param  string  $tool  The tool name
     * @return string|null The endpoint path or null if tool is unknown
     */
    private function getEndpointForTool(string $tool): ?string
    {
        return match ($tool) {
            'get_trial_balance' => '/internal/mcp/trial-balance',
            'get_company_stats' => '/internal/mcp/company-stats',
            'search_customers' => '/internal/mcp/search-customers',
            'anomaly_scan' => '/internal/mcp/scan-anomalies',
            'validate_ubl' => '/internal/mcp/validate-ubl',
            'tax_explain' => '/internal/mcp/tax-explain',
            'bank_categorize' => '/internal/mcp/bank-categorize',
            'search_invoices' => '/internal/mcp/search-invoices',
            'get_cash_flow' => '/internal/mcp/cash-flow',
            'get_profit_loss' => '/internal/mcp/profit-loss',
            default => null,
        };
    }

    /**
     * Log MCP call for monitoring and debugging
     *
     * @param  string  $tool  The tool name
     * @param  array<string, mixed>  $params  The parameters
     * @param  array<string, mixed>|null  $response  The response data
     * @param  int  $statusCode  HTTP status code
     * @param  float  $duration  Duration in seconds
     * @param  string|null  $error  Error message if any
     */
    private function logMcpCall(
        string $tool,
        array $params,
        ?array $response,
        int $statusCode,
        float $duration,
        ?string $error = null
    ): void {
        $logData = [
            'tool' => $tool,
            'status_code' => $statusCode,
            'duration_seconds' => round($duration, 3),
            'company_id' => $params['company_id'] ?? null,
            'timestamp' => now()->toDateTimeString(),
        ];

        if ($error !== null) {
            $logData['error'] = $error;
        }

        if ($statusCode >= 400 || $error !== null) {
            Log::error('MCP Call Failed', $logData);
        } else {
            Log::info('MCP Call', $logData);
        }
    }

    /**
     * Check if the MCP server is healthy and reachable
     *
     * @return bool True if server is healthy
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::withToken($this->bearerToken)
                ->timeout(5)
                ->get($this->mcpServerUrl.'/health');

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('MCP health check failed', ['error' => $e->getMessage()]);

            return false;
        }
    }
}

/**
 * MCP Exception
 *
 * Custom exception for MCP-related errors
 */
class McpException extends \Exception {}
