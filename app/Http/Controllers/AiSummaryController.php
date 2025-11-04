<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * AI Summary Controller - Proxy for AI-MCP summary endpoint
 * Returns financial summary data from AI service
 */
class AiSummaryController extends Controller
{
    private string $aiServiceUrl;
    private int $timeoutSeconds = 30;

    public function __construct()
    {
        $this->aiServiceUrl = env('AI_MCP_URL', 'http://ai-mcp:7600');
        $this->middleware('auth:sanctum');
    }

    /**
     * Get AI financial summary for specified company
     * GET /api/ai/summary?company_id=X
     */
    public function getSummary(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id'
        ]);

        $companyId = $request->query('company_id');
        
        try {
            // Check cache first (15 minute cache)
            $cacheKey = "ai_summary_{$companyId}";
            $cached = Cache::get($cacheKey);
            
            if ($cached) {
                return response()->json($cached);
            }

            // Call AI service financial-summary endpoint
            // CLAUDE-CHECKPOINT: Pass company header from incoming request to AI-MCP
            $response = Http::timeout($this->timeoutSeconds)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'company' => $request->header('company'),
                    'X-Company-ID' => $companyId
                ])
                ->get("{$this->aiServiceUrl}/api/financial-summary", [
                    'company_id' => $companyId
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Cache for 15 minutes
                Cache::put($cacheKey, $data, 900);

                return response()->json($data);
            }

            // CLAUDE-CHECKPOINT: Enhanced error handling for 403/404 responses
            // Handle specific error codes gracefully
            if (in_array($response->status(), [403, 404])) {
                Log::info('AI Summary Service - Access/Resource Error', [
                    'status' => $response->status(),
                    'company_id' => $companyId,
                    'message' => $response->status() === 403
                        ? 'Forbidden - Check company permissions'
                        : 'Not Found - Resource unavailable'
                ]);
            } else {
                Log::warning('AI Summary Service Error', [
                    'status' => $response->status(),
                    'company_id' => $companyId,
                    'response' => $response->body()
                ]);
            }

            // Return fallback data structure matching AI service
            return response()->json([
                'totalRevenue' => 0,
                'totalExpenses' => 0,
                'netProfit' => 0,
                'invoicesCount' => 0,
                'paymentsCount' => 0,
                'averageInvoiceValue' => 0,
                'currency' => 'MKD',
                'period' => 'last_30_days',
                'insights' => [
                    'Service temporarily unavailable'
                ],
                'riskScore' => 0.5,
                'riskLevel' => 'unknown',
                'generated_at' => now()->toISOString(),
                'fallback' => true
            ]);

        } catch (\Exception $e) {
            Log::error('AI Summary Request Failed', [
                'error' => $e->getMessage(),
                'company_id' => $companyId
            ]);

            // Return same structure with error indication
            return response()->json([
                'totalRevenue' => 0,
                'totalExpenses' => 0,
                'netProfit' => 0,
                'invoicesCount' => 0,
                'paymentsCount' => 0,
                'averageInvoiceValue' => 0,
                'currency' => 'MKD',
                'period' => 'last_30_days',
                'insights' => [
                    'Analysis temporarily unavailable'
                ],
                'riskScore' => 0.5,
                'riskLevel' => 'unknown',
                'generated_at' => now()->toISOString(),
                'error' => true
            ]);
        }
    }

    /**
     * Get AI risk analysis for specified company
     * GET /api/ai/risk?company_id=X
     */
    public function getRisk(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id'
        ]);

        $companyId = $request->query('company_id');
        
        try {
            // Check cache first (30 minute cache for risk data)
            $cacheKey = "ai_risk_{$companyId}";
            $cached = Cache::get($cacheKey);
            
            if ($cached) {
                return response()->json($cached);
            }

            // Call AI service risk analysis endpoint
            // CLAUDE-CHECKPOINT: Pass company header from incoming request to AI-MCP
            $response = Http::timeout($this->timeoutSeconds)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'company' => $request->header('company'),
                    'X-Company-ID' => $companyId
                ])
                ->get("{$this->aiServiceUrl}/api/risk-analysis", [
                    'company_id' => $companyId
                ]);

            if ($response->successful()) {
                $data = $response->json();

                // Extract just the risk score to match spec
                $riskData = [
                    'risk_score' => $data['overallRisk'] ?? 0.5
                ];

                // Cache for 30 minutes
                Cache::put($cacheKey, $riskData, 1800);

                return response()->json($riskData);
            }

            // CLAUDE-CHECKPOINT: Enhanced error handling for 403/404 responses
            // Handle specific error codes gracefully
            if (in_array($response->status(), [403, 404])) {
                Log::info('AI Risk Service - Access/Resource Error', [
                    'status' => $response->status(),
                    'company_id' => $companyId,
                    'message' => $response->status() === 403
                        ? 'Forbidden - Check company permissions'
                        : 'Not Found - Resource unavailable'
                ]);
            } else {
                Log::warning('AI Risk Service Error', [
                    'status' => $response->status(),
                    'company_id' => $companyId,
                    'response' => $response->body()
                ]);
            }

            // Return fallback risk score
            return response()->json([
                'risk_score' => 0.5
            ]);

        } catch (\Exception $e) {
            Log::error('AI Risk Analysis Request Failed', [
                'error' => $e->getMessage(),
                'company_id' => $companyId
            ]);

            // Return default risk score on error
            return response()->json([
                'risk_score' => 0.5
            ]);
        }
    }
}

