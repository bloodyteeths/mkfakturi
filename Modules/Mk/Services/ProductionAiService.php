<?php

namespace Modules\Mk\Services;

use App\Models\Item;
use App\Services\AiProvider\GeminiProvider;
use App\Services\UsageLimitService;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Models\Manufacturing\Bom;
use Modules\Mk\Models\Manufacturing\ProductionOrder;

/**
 * AI-powered manufacturing assistant using Gemini.
 *
 * Features:
 * - BOM material suggestions from product name
 * - Wastage prediction from historical data
 * - Material consumption anomaly detection
 * - Natural language production order creation
 * - Variance explanation in user's locale
 */
class ProductionAiService
{
    private ?GeminiProvider $provider = null;

    private const LOCALE_NAMES = [
        'mk' => 'Macedonian (македонски)',
        'sq' => 'Albanian (shqip)',
        'tr' => 'Turkish (Türkçe)',
        'en' => 'English',
    ];

    /**
     * Suggest BOM materials for a product name.
     *
     * @return array{materials: array}|null
     */
    public function suggestBomMaterials(string $productName, int $companyId, string $locale = 'mk'): ?array
    {
        $provider = $this->getProvider();
        if (! $provider) {
            return null;
        }

        $items = Item::where('company_id', $companyId)
            ->where('track_quantity', true)
            ->select('id', 'name', 'sku')
            ->limit(200)
            ->get()
            ->map(fn ($i) => "ID:{$i->id} — {$i->name}" . ($i->sku ? " ({$i->sku})" : ''))
            ->join("\n");

        $localeName = self::LOCALE_NAMES[$locale] ?? 'Macedonian';

        $prompt = <<<PROMPT
You are a manufacturing assistant for a Macedonian company.
Respond in {$localeName}.

Given a product name, suggest the raw materials/components needed to manufacture it.
Match to the company's existing items when possible (by name similarity).

Company's available items:
{$items}

Product to manufacture: "{$productName}"

Return ONLY valid JSON, no markdown:
{"materials": [{"item_id": int|null, "name": "string", "quantity": float, "unit": "string", "wastage_percent": float}]}

If item_id is null, it means a new item needs to be created.
Keep quantities realistic for a single unit of output.
PROMPT;

        try {
            $response = $provider->generate($prompt, [
                'temperature' => 0.3,
                'max_tokens' => 2048,
                'thinking_budget' => 0,
            ]);

            $this->trackUsage($companyId);

            return $this->parseJson($response);
        } catch (\Exception $e) {
            Log::warning('ProductionAiService::suggestBomMaterials failed', [
                'product' => $productName,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Predict wastage % based on historical production orders.
     *
     * @return array{expected_wastage_percent: float, confidence: float, reasoning: string}|null
     */
    public function predictWastage(int $bomId): ?array
    {
        $provider = $this->getProvider();
        if (! $provider) {
            return null;
        }

        $bom = Bom::with('lines.item:id,name')->find($bomId);
        if (! $bom) {
            return null;
        }

        $orders = ProductionOrder::where('bom_id', $bomId)
            ->where('status', ProductionOrder::STATUS_COMPLETED)
            ->with('materials:id,production_order_id,item_id,planned_quantity,actual_quantity,wastage_quantity')
            ->latest('completed_at')
            ->limit(20)
            ->get();

        if ($orders->isEmpty()) {
            return ['expected_wastage_percent' => (float) $bom->expected_wastage_percent, 'confidence' => 0, 'reasoning' => 'No historical data'];
        }

        $historicalData = $orders->map(fn ($o) => [
            'date' => $o->completed_at?->format('Y-m-d'),
            'planned' => (float) $o->planned_quantity,
            'actual' => (float) $o->actual_quantity,
            'wastage_cost' => $o->total_wastage_cost,
            'materials' => $o->materials->map(fn ($m) => [
                'item' => $m->item?->name,
                'planned' => (float) $m->planned_quantity,
                'actual' => (float) $m->actual_quantity,
                'wastage' => (float) $m->wastage_quantity,
            ]),
        ])->toJson(JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
Analyze this production history for BOM "{$bom->name}" and predict the expected wastage percentage.

Historical data (last {$orders->count()} completed orders):
{$historicalData}

Current BOM wastage setting: {$bom->expected_wastage_percent}%

Return ONLY valid JSON:
{"expected_wastage_percent": float, "confidence": float (0-1), "reasoning": "brief explanation"}
PROMPT;

        try {
            $response = $provider->generate($prompt, [
                'temperature' => 0.1,
                'max_tokens' => 512,
                'thinking_budget' => 0,
            ]);

            $this->trackUsage($bom->company_id);

            return $this->parseJson($response);
        } catch (\Exception $e) {
            Log::warning('ProductionAiService::predictWastage failed', ['bom_id' => $bomId, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Detect anomalies in material consumption entries.
     *
     * @return array{anomalies: array}|null
     */
    public function detectConsumptionAnomalies(ProductionOrder $order): ?array
    {
        $provider = $this->getProvider();
        if (! $provider) {
            return null;
        }

        $order->load(['materials.item:id,name', 'bom.lines.item:id,name']);

        if (! $order->bom || $order->materials->isEmpty()) {
            return null;
        }

        $bomLines = $order->bom->lines->map(fn ($l) => [
            'item' => $l->item?->name,
            'normative_qty' => (float) $l->quantity,
            'wastage_pct' => (float) $l->wastage_percent,
        ])->toJson();

        $actualMaterials = $order->materials->map(fn ($m) => [
            'item' => $m->item?->name,
            'actual_qty' => (float) $m->actual_quantity,
            'wastage_qty' => (float) $m->wastage_quantity,
        ])->toJson();

        $prompt = <<<PROMPT
You are a manufacturing quality control assistant.

BOM normative (per unit of output, planned qty: {$order->planned_quantity}):
{$bomLines}

Actual consumption recorded:
{$actualMaterials}

Identify ANY anomalies where actual consumption deviates significantly from expected (normative × planned qty).
Flag quantities that seem like typos (e.g., 900 instead of 90).

Return ONLY valid JSON:
{"anomalies": [{"item_name": "string", "expected": float, "actual": float, "alert": "string", "suggestion": "string"}]}

Return empty anomalies array if everything looks normal.
PROMPT;

        try {
            $response = $provider->generate($prompt, [
                'temperature' => 0.1,
                'max_tokens' => 1024,
                'thinking_budget' => 0,
            ]);

            $this->trackUsage($order->company_id);

            return $this->parseJson($response);
        } catch (\Exception $e) {
            Log::warning('ProductionAiService::detectConsumptionAnomalies failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Parse natural language into production order parameters.
     *
     * @return array{bom_id: ?int, quantity: float, deadline: ?string, notes: ?string}|null
     */
    public function parseProductionIntent(string $input, int $companyId, string $locale = 'mk'): ?array
    {
        $provider = $this->getProvider();
        if (! $provider) {
            return null;
        }

        $boms = Bom::where('company_id', $companyId)
            ->where('is_active', true)
            ->with('outputItem:id,name')
            ->get()
            ->map(fn ($b) => "ID:{$b->id} — {$b->name} (производ: {$b->outputItem?->name})")
            ->join("\n");

        $localeName = self::LOCALE_NAMES[$locale] ?? 'Macedonian';
        $today = now()->format('Y-m-d');

        $prompt = <<<PROMPT
You are a manufacturing assistant. Today is {$today}.
The user speaks {$localeName}. Parse their production request.

Available BOMs:
{$boms}

User input: "{$input}"

Match the user's intent to a BOM (by product name similarity).
Parse quantity and deadline if mentioned.

Return ONLY valid JSON:
{"bom_id": int|null, "quantity": float, "deadline": "YYYY-MM-DD"|null, "notes": "any extra context"|null}

If no BOM matches, set bom_id to null.
PROMPT;

        try {
            $response = $provider->generate($prompt, [
                'temperature' => 0.1,
                'max_tokens' => 512,
                'thinking_budget' => 0,
            ]);

            $this->trackUsage($companyId);

            return $this->parseJson($response);
        } catch (\Exception $e) {
            Log::warning('ProductionAiService::parseProductionIntent failed', ['input' => $input, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Explain variance between actual and normative costs in user's locale.
     */
    public function explainVariance(ProductionOrder $order, string $locale = 'mk'): ?string
    {
        $provider = $this->getProvider();
        if (! $provider) {
            return null;
        }

        $order->load(['outputItem:id,name', 'bom:id,name', 'materials.item:id,name']);

        $localeName = self::LOCALE_NAMES[$locale] ?? 'Macedonian';

        $data = json_encode([
            'order_number' => $order->order_number,
            'product' => $order->outputItem?->name,
            'planned_qty' => (float) $order->planned_quantity,
            'actual_qty' => (float) $order->actual_quantity,
            'material_variance' => $order->material_variance,
            'labor_variance' => $order->labor_variance,
            'total_variance' => $order->total_variance,
            'total_cost' => $order->total_production_cost,
        ], JSON_PRETTY_PRINT);

        $prompt = <<<PROMPT
You are a manufacturing cost analyst. Explain the variance for this production order in {$localeName}.
Keep it concise (2-3 sentences). Use business language appropriate for a factory manager.

Data (monetary values in MKD denari × 100, i.e. cents):
{$data}

Positive variance = unfavorable (actual > normative).
Negative variance = favorable (actual < normative).
PROMPT;

        try {
            $response = $provider->generate($prompt, [
                'temperature' => 0.5,
                'max_tokens' => 512,
            ]);

            $this->trackUsage($order->company_id);

            return trim($response);
        } catch (\Exception $e) {
            Log::warning('ProductionAiService::explainVariance failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Get or create the Gemini provider (lazy singleton).
     */
    protected function getProvider(): ?GeminiProvider
    {
        if ($this->provider !== null) {
            return $this->provider;
        }

        try {
            $this->provider = app(GeminiProvider::class);

            return $this->provider;
        } catch (\Exception $e) {
            Log::warning('ProductionAiService: Gemini provider unavailable', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Parse JSON from AI response, stripping markdown fences if present.
     */
    private function parseJson(string $response): ?array
    {
        $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $response);
        $cleaned = preg_replace('/```\s*$/m', '', $cleaned);
        $cleaned = trim($cleaned);

        $result = json_decode($cleaned, true);

        return is_array($result) ? $result : null;
    }

    /**
     * Track AI usage for billing.
     */
    private function trackUsage(int $companyId): void
    {
        try {
            $company = \App\Models\Company::find($companyId);
            if ($company) {
                app(UsageLimitService::class)->incrementUsage($company, 'ai_queries_per_month');
            }
        } catch (\Exception $e) {
            // Non-critical
        }
    }
}

// CLAUDE-CHECKPOINT
