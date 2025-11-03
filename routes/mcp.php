<?php

use App\Http\Controllers\Internal\McpController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| MCP Internal API Routes
|--------------------------------------------------------------------------
|
| These routes are for internal use by the MCP (Model Context Protocol) server only.
| They are protected by:
| - VerifyMcpToken middleware (Bearer token authentication)
| - FeatureMiddleware (checks FEATURE_MCP_AI_TOOLS flag)
|
| All routes are read-only except for invoice creation which is delegated
| to the standard API endpoints.
|
*/

Route::middleware(['mcp.token'])->prefix('internal/mcp')->group(function () {
    // Health check (no feature flag required for monitoring)
    Route::get('/health', [McpController::class, 'health']);

    // MCP tools endpoints (feature flag enforced by middleware)
    Route::post('/company-stats', [McpController::class, 'companyStats']);
    Route::post('/search-customers', [McpController::class, 'searchCustomers']);
    Route::post('/trial-balance', [McpController::class, 'trialBalance']);
    Route::post('/validate-ubl', [McpController::class, 'validateUbl']);
    Route::post('/explain-tax', [McpController::class, 'explainTax']);
    Route::post('/categorize-transaction', [McpController::class, 'categorizeTransaction']);
    Route::post('/scan-anomalies', [McpController::class, 'scanAnomalies']);
});

// CLAUDE-CHECKPOINT
