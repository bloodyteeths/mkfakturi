<?php

namespace Modules\Mk\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Mk\Models\Manufacturing\ProductionOrder;
use Modules\Mk\Services\ProductionAiService;

class ProductionAiController extends Controller
{
    public function __construct(
        protected ProductionAiService $aiService,
    ) {}

    /**
     * Suggest BOM materials for a product name.
     */
    public function suggestMaterials(Request $request): JsonResponse
    {
        $request->validate([
            'product_name' => 'required|string|max:255',
        ]);

        $companyId = (int) $request->header('company');
        $locale = $request->header('X-Locale', 'mk');

        $result = $this->aiService->suggestBomMaterials(
            $request->input('product_name'),
            $companyId,
            $locale
        );

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'AI service unavailable',
            ], 503);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Predict wastage for a BOM based on historical data.
     */
    public function predictWastage(Request $request, int $bomId): JsonResponse
    {
        $result = $this->aiService->predictWastage($bomId);

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'AI service unavailable or no BOM found',
            ], 503);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Detect anomalies in material consumption for an order.
     */
    public function detectAnomalies(Request $request, int $orderId): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($orderId);

        $result = $this->aiService->detectConsumptionAnomalies($order);

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'AI service unavailable or insufficient data',
            ], 503);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Parse natural language production order intent.
     */
    public function parseIntent(Request $request): JsonResponse
    {
        $request->validate([
            'input' => 'required|string|max:500',
        ]);

        $companyId = (int) $request->header('company');
        $locale = $request->header('X-Locale', 'mk');

        $result = $this->aiService->parseProductionIntent(
            $request->input('input'),
            $companyId,
            $locale
        );

        if (! $result) {
            return response()->json([
                'success' => false,
                'message' => 'AI service unavailable',
            ], 503);
        }

        return response()->json(['success' => true, 'data' => $result]);
    }

    /**
     * Explain variance for a completed production order.
     */
    public function explainVariance(Request $request, int $orderId): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($orderId);
        $locale = $request->header('X-Locale', 'mk');

        $explanation = $this->aiService->explainVariance($order, $locale);

        if (! $explanation) {
            return response()->json([
                'success' => false,
                'message' => 'AI service unavailable',
            ], 503);
        }

        return response()->json(['success' => true, 'data' => ['explanation' => $explanation]]);
    }
}

// CLAUDE-CHECKPOINT
