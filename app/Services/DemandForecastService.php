<?php

namespace App\Services;

use App\Models\Item;
use App\Models\StockMovement;
use App\Services\AiProvider\GeminiProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DemandForecastService
{
    /**
     * Source types that represent consumption (stock OUT).
     */
    protected const CONSUMPTION_TYPES = [
        StockMovement::SOURCE_INVOICE_ITEM,
        StockMovement::SOURCE_ADJUSTMENT,
        StockMovement::SOURCE_TRANSFER_OUT,
        StockMovement::SOURCE_PRODUCTION_CONSUME,
        StockMovement::SOURCE_PRODUCTION_WASTAGE,
    ];

    /**
     * Forecast demand for items based on historical consumption.
     * Uses simple moving average of last 90 days of OUT movements.
     *
     * @return array<int, array>
     */
    public function forecastItems(int $companyId, ?array $itemIds = null): array
    {
        $now = Carbon::now();
        $ninetyDaysAgo = $now->copy()->subDays(90);
        $sixtyDaysAgo = $now->copy()->subDays(60);
        $thirtyDaysAgo = $now->copy()->subDays(30);

        // Build base query for trackable items
        $itemQuery = Item::where('company_id', $companyId)
            ->where('track_quantity', true);

        if ($itemIds && count($itemIds) > 0) {
            $itemQuery->whereIn('id', $itemIds);
        } else {
            // Default: only items with minimum_quantity set (low stock candidates)
            $itemQuery->whereNotNull('minimum_quantity')
                ->where('minimum_quantity', '>', 0);
        }

        $items = $itemQuery->get(['id', 'name', 'sku', 'quantity', 'minimum_quantity']);

        if ($items->isEmpty()) {
            return [];
        }

        $itemIdsToQuery = $items->pluck('id')->toArray();

        // Get consumption data for last 90 days, grouped by item_id
        $consumptionData = StockMovement::where('company_id', $companyId)
            ->whereIn('item_id', $itemIdsToQuery)
            ->where('quantity', '<', 0) // OUT movements are negative
            ->where('movement_date', '>=', $ninetyDaysAgo)
            ->whereIn('source_type', self::CONSUMPTION_TYPES)
            ->select(
                'item_id',
                DB::raw('SUM(ABS(quantity)) as total_consumed'),
                DB::raw('COUNT(*) as movement_count'),
                DB::raw("SUM(CASE WHEN movement_date >= '{$thirtyDaysAgo->toDateString()}' THEN ABS(quantity) ELSE 0 END) as consumed_last_30d"),
                DB::raw("SUM(CASE WHEN movement_date >= '{$sixtyDaysAgo->toDateString()}' AND movement_date < '{$thirtyDaysAgo->toDateString()}' THEN ABS(quantity) ELSE 0 END) as consumed_prev_30d"),
                DB::raw("SUM(CASE WHEN movement_date < '{$sixtyDaysAgo->toDateString()}' THEN ABS(quantity) ELSE 0 END) as consumed_oldest_30d")
            )
            ->groupBy('item_id')
            ->get()
            ->keyBy('item_id');

        // Get current stock for each item
        $stockService = app(StockService::class);
        $forecasts = [];

        foreach ($items as $item) {
            $consumption = $consumptionData->get($item->id);
            $currentStock = $stockService->getItemStock($companyId, $item->id);
            $currentQty = $currentStock['quantity'];

            if (! $consumption || $consumption->total_consumed <= 0) {
                // No consumption history — cannot forecast
                $forecasts[] = [
                    'item_id' => $item->id,
                    'item_name' => $item->name,
                    'item_sku' => $item->sku,
                    'current_quantity' => $currentQty,
                    'minimum_quantity' => $item->minimum_quantity,
                    'avg_daily_consumption' => 0,
                    'days_of_stock' => $currentQty > 0 ? 999 : 0,
                    'predicted_stockout_date' => null,
                    'trend' => 'stable',
                    'confidence' => 'low',
                    'movement_count' => 0,
                    'consumed_last_30d' => 0,
                    'consumed_prev_30d' => 0,
                ];

                continue;
            }

            // Calculate average daily consumption over 90 days
            $daysInRange = max(1, $ninetyDaysAgo->diffInDays($now));
            $avgDaily = $consumption->total_consumed / $daysInRange;

            // Days of stock remaining
            $daysOfStock = $avgDaily > 0
                ? (int) round($currentQty / $avgDaily)
                : ($currentQty > 0 ? 999 : 0);

            // Predicted stockout date
            $predictedStockout = $daysOfStock > 0 && $daysOfStock < 999
                ? $now->copy()->addDays($daysOfStock)->format('Y-m-d')
                : null;

            // Trend: compare last 30d vs previous 30d daily averages
            $last30dDaily = $consumption->consumed_last_30d / 30;
            $prev30dDaily = $consumption->consumed_prev_30d / 30;

            $trend = 'stable';
            if ($prev30dDaily > 0) {
                $changePercent = (($last30dDaily - $prev30dDaily) / $prev30dDaily) * 100;
                if ($changePercent > 15) {
                    $trend = 'increasing';
                } elseif ($changePercent < -15) {
                    $trend = 'decreasing';
                }
            } elseif ($last30dDaily > 0) {
                $trend = 'increasing'; // No prior consumption but now there is
            }

            // Confidence based on data points
            $movementCount = (int) $consumption->movement_count;
            $confidence = 'low';
            if ($movementCount >= 60) {
                $confidence = 'high';
            } elseif ($movementCount >= 20) {
                $confidence = 'medium';
            }

            $forecasts[] = [
                'item_id' => $item->id,
                'item_name' => $item->name,
                'item_sku' => $item->sku,
                'current_quantity' => $currentQty,
                'minimum_quantity' => $item->minimum_quantity,
                'avg_daily_consumption' => round($avgDaily, 2),
                'days_of_stock' => $daysOfStock,
                'predicted_stockout_date' => $predictedStockout,
                'trend' => $trend,
                'confidence' => $confidence,
                'movement_count' => $movementCount,
                'consumed_last_30d' => round((float) $consumption->consumed_last_30d, 2),
                'consumed_prev_30d' => round((float) $consumption->consumed_prev_30d, 2),
            ];
        }

        // Sort by days_of_stock ascending (most critical first)
        usort($forecasts, fn ($a, $b) => $a['days_of_stock'] <=> $b['days_of_stock']);

        return $forecasts;
    }

    /**
     * Get AI-enhanced analysis for critical items using Gemini.
     * Only analyzes items with days_of_stock < 14.
     *
     * @return array|null
     */
    public function analyzeWithAI(int $companyId, array $forecasts): ?array
    {
        // Filter to critical items only (days_of_stock < 14)
        $criticalItems = array_filter($forecasts, fn ($f) => $f['days_of_stock'] < 14);

        if (empty($criticalItems)) {
            return ['status' => 'no_critical_items', 'items' => []];
        }

        // Limit to top 10 most critical
        $criticalItems = array_slice(array_values($criticalItems), 0, 10);

        try {
            $gemini = app(GeminiProvider::class);
        } catch (\Exception $e) {
            Log::warning('DemandForecast: Gemini not available', ['error' => $e->getMessage()]);

            return ['status' => 'ai_unavailable', 'error' => $e->getMessage()];
        }

        $prompt = $this->buildAIPrompt($criticalItems);

        try {
            $response = $gemini->generate($prompt, [
                'temperature' => 0.2,
                'max_tokens' => 2048,
                'thinking_budget' => 0,
            ]);

            $parsed = $this->parseAIResponse($response);

            if ($parsed) {
                return ['status' => 'success', 'items' => $parsed];
            }

            return ['status' => 'parse_error', 'items' => []];
        } catch (\Exception $e) {
            Log::warning('DemandForecast AI analysis failed', ['error' => $e->getMessage()]);

            return ['status' => 'ai_error', 'error' => $e->getMessage()];
        }
    }

    /**
     * Build the Gemini prompt for demand forecast analysis.
     */
    protected function buildAIPrompt(array $criticalItems): string
    {
        $itemsJson = json_encode(array_map(fn ($item) => [
            'item_id' => $item['item_id'],
            'name' => $item['item_name'],
            'current_stock' => $item['current_quantity'],
            'min_stock' => $item['minimum_quantity'],
            'avg_daily_consumption' => $item['avg_daily_consumption'],
            'days_of_stock' => $item['days_of_stock'],
            'trend' => $item['trend'],
            'consumed_last_30d' => $item['consumed_last_30d'],
            'consumed_prev_30d' => $item['consumed_prev_30d'],
        ], $criticalItems), JSON_PRETTY_PRINT);

        return <<<PROMPT
You are an inventory management analyst for a Macedonian business. Analyze these critical low-stock items and provide reorder recommendations.

Critical items (stock running out within 14 days):
{$itemsJson}

For each item, provide:
1. Recommended reorder quantity (consider consumption trend)
2. Urgency level (critical/high/medium)
3. Brief risk assessment (1 sentence)
4. Any seasonality or pattern notes if detectable

Return ONLY valid JSON (no markdown, no explanation outside JSON):
[
  {
    "item_id": <id>,
    "reorder_qty": <recommended quantity to order>,
    "urgency": "<critical|high|medium>",
    "risk": "<brief risk assessment>",
    "notes": "<optional pattern/seasonality note or null>"
  }
]
PROMPT;
    }

    /**
     * Parse the AI response JSON.
     */
    protected function parseAIResponse(string $response): ?array
    {
        $cleaned = trim($response);
        $cleaned = preg_replace('/^```(?:json)?\s*/i', '', $cleaned);
        $cleaned = preg_replace('/\s*```$/', '', $cleaned);
        $cleaned = trim($cleaned);

        $parsed = json_decode($cleaned, true);

        if (! $parsed || ! is_array($parsed)) {
            Log::warning('DemandForecast AI response parse failed', [
                'response_preview' => mb_substr($response, 0, 500),
            ]);

            return null;
        }

        // Validate structure
        $validUrgencies = ['critical', 'high', 'medium'];
        foreach ($parsed as &$item) {
            if (! isset($item['item_id'])) {
                continue;
            }
            $urgency = $item['urgency'] ?? 'medium';
            if (! in_array($urgency, $validUrgencies)) {
                $item['urgency'] = 'medium';
            }
        }

        return $parsed;
    }
}

// CLAUDE-CHECKPOINT
