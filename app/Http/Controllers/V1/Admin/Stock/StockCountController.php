<?php

namespace App\Http\Controllers\V1\Admin\Stock;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\StockMovement;
use App\Services\StockService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Stock Count (Stocktake/Попис) Controller
 *
 * Handles physical inventory counting and reconciliation with system stock.
 * On approval, creates adjustment movements for each variance.
 */
class StockCountController extends Controller
{
    protected StockService $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * List stock counts with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('company');
        $query = StockCount::where('company_id', $companyId)
            ->with(['warehouse', 'countedBy', 'approvedBy'])
            ->orderBy('count_date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->query('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->query('warehouse_id')) {
            $query->where('warehouse_id', $request->query('warehouse_id'));
        }

        if ($request->query('from_date')) {
            $query->where('count_date', '>=', $request->query('from_date'));
        }

        if ($request->query('to_date')) {
            $query->where('count_date', '<=', $request->query('to_date'));
        }

        $limit = (int) $request->query('limit', 25);
        $counts = $query->paginate($limit);

        return response()->json([
            'data' => $counts->map(fn ($count) => $this->formatCount($count)),
            'meta' => [
                'current_page' => $counts->currentPage(),
                'last_page' => $counts->lastPage(),
                'per_page' => $counts->perPage(),
                'total' => $counts->total(),
            ],
        ]);
    }

    /**
     * Create a new stock count for a warehouse.
     * Auto-populates items with current system quantities.
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        $request->validate([
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'count_date' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        DB::beginTransaction();
        try {
            $stockCount = StockCount::create([
                'company_id' => $companyId,
                'warehouse_id' => $request->warehouse_id,
                'status' => StockCount::STATUS_DRAFT,
                'count_date' => $request->count_date,
                'notes' => $request->notes,
                'counted_by' => auth()->id(),
            ]);

            // Auto-populate with all tracked items that have stock in this warehouse
            $items = Item::where('company_id', $companyId)
                ->where('track_quantity', true)
                ->get();

            $countItems = [];
            foreach ($items as $item) {
                $stock = $this->stockService->getItemStock($companyId, $item->id, $request->warehouse_id);

                // Include items with stock OR items that might have stock (to catch discrepancies)
                $countItems[] = [
                    'stock_count_id' => $stockCount->id,
                    'item_id' => $item->id,
                    'system_quantity' => $stock['quantity'],
                    'system_unit_cost' => $stock['weighted_average_cost'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (! empty($countItems)) {
                StockCountItem::insert($countItems);
            }

            $stockCount->update(['total_items_counted' => count($countItems)]);

            DB::commit();

            $stockCount->load(['warehouse', 'countedBy', 'items.item']);

            return response()->json([
                'success' => true,
                'message' => 'Stock count created successfully.',
                'data' => $this->formatCountDetail($stockCount),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create stock count', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Failed to create stock count.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a stock count with all items.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');

        $stockCount = StockCount::where('company_id', $companyId)
            ->with(['warehouse', 'countedBy', 'approvedBy', 'items.item'])
            ->findOrFail($id);

        return response()->json([
            'data' => $this->formatCountDetail($stockCount),
        ]);
    }

    /**
     * Update counted quantities for items (only in draft/in_progress).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');

        $stockCount = StockCount::where('company_id', $companyId)->findOrFail($id);

        if (! in_array($stockCount->status, [StockCount::STATUS_DRAFT, StockCount::STATUS_IN_PROGRESS])) {
            return response()->json([
                'error' => 'Cannot update a completed or cancelled stock count.',
            ], 422);
        }

        $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|integer|exists:stock_count_items,id',
            'items.*.counted_quantity' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
            'notes' => 'nullable|string|max:2000',
        ]);

        DB::beginTransaction();
        try {
            // Update status to in_progress if still draft
            if ($stockCount->status === StockCount::STATUS_DRAFT) {
                $stockCount->update(['status' => StockCount::STATUS_IN_PROGRESS]);
            }

            if ($request->has('notes')) {
                $stockCount->update(['notes' => $request->notes]);
            }

            foreach ($request->items as $itemData) {
                $countItem = StockCountItem::where('stock_count_id', $stockCount->id)
                    ->where('id', $itemData['id'])
                    ->first();

                if ($countItem) {
                    $updateData = [];

                    if (array_key_exists('counted_quantity', $itemData)) {
                        $updateData['counted_quantity'] = $itemData['counted_quantity'];

                        // Calculate variance
                        if ($itemData['counted_quantity'] !== null) {
                            $variance = (float) $itemData['counted_quantity'] - (float) $countItem->system_quantity;
                            $updateData['variance_quantity'] = $variance;
                            $updateData['variance_value'] = (int) ($variance * $countItem->system_unit_cost);
                        } else {
                            $updateData['variance_quantity'] = null;
                            $updateData['variance_value'] = null;
                        }
                    }

                    if (isset($itemData['notes'])) {
                        $updateData['notes'] = $itemData['notes'];
                    }

                    if (! empty($updateData)) {
                        $countItem->update($updateData);
                    }
                }
            }

            DB::commit();

            $stockCount->load(['warehouse', 'countedBy', 'approvedBy', 'items.item']);

            return response()->json([
                'success' => true,
                'message' => 'Stock count updated successfully.',
                'data' => $this->formatCountDetail($stockCount),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update stock count', ['error' => $e->getMessage()]);

            return response()->json([
                'error' => 'Failed to update stock count.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mark stock count as complete. Calculates summary variances.
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');

        $stockCount = StockCount::where('company_id', $companyId)->findOrFail($id);

        if (! in_array($stockCount->status, [StockCount::STATUS_DRAFT, StockCount::STATUS_IN_PROGRESS])) {
            return response()->json([
                'error' => 'Stock count is already completed or cancelled.',
            ], 422);
        }

        // Check that at least some items have been counted
        $countedItems = StockCountItem::where('stock_count_id', $stockCount->id)
            ->whereNotNull('counted_quantity')
            ->count();

        if ($countedItems === 0) {
            return response()->json([
                'error' => 'No items have been counted yet. Please enter counted quantities first.',
            ], 422);
        }

        // Calculate totals
        $items = StockCountItem::where('stock_count_id', $stockCount->id)
            ->whereNotNull('counted_quantity')
            ->get();

        $totalVarianceQty = $items->sum('variance_quantity');
        $totalVarianceVal = $items->sum('variance_value');

        $stockCount->update([
            'status' => StockCount::STATUS_COMPLETED,
            'total_items_counted' => $countedItems,
            'total_variance_quantity' => $totalVarianceQty,
            'total_variance_value' => $totalVarianceVal,
        ]);

        $stockCount->load(['warehouse', 'countedBy', 'approvedBy', 'items.item']);

        return response()->json([
            'success' => true,
            'message' => 'Stock count completed. Review variances and approve to create adjustments.',
            'data' => $this->formatCountDetail($stockCount),
        ]);
    }

    /**
     * Approve stock count: create adjustment movements for each variance.
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');

        $stockCount = StockCount::where('company_id', $companyId)->findOrFail($id);

        if ($stockCount->status !== StockCount::STATUS_COMPLETED) {
            return response()->json([
                'error' => 'Only completed stock counts can be approved.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $items = StockCountItem::where('stock_count_id', $stockCount->id)
                ->whereNotNull('counted_quantity')
                ->where('variance_quantity', '!=', 0)
                ->get();

            $adjustmentsCreated = 0;

            foreach ($items as $countItem) {
                $variance = (float) $countItem->variance_quantity;

                if ($variance == 0) {
                    continue;
                }

                // For positive variance (found more than system), use current WAC as unit cost
                $unitCost = $variance > 0 ? $countItem->system_unit_cost : null;

                $this->stockService->recordAdjustment(
                    $companyId,
                    $stockCount->warehouse_id,
                    $countItem->item_id,
                    $variance,
                    $unitCost,
                    "Stock count variance - Count #{$stockCount->id}",
                    [
                        'stock_count_id' => $stockCount->id,
                        'system_quantity' => (float) $countItem->system_quantity,
                        'counted_quantity' => (float) $countItem->counted_quantity,
                    ],
                    auth()->id(),
                    true // skipNegativeCheck — stocktake is authoritative
                );

                $adjustmentsCreated++;
            }

            $stockCount->update([
                'status' => StockCount::STATUS_COMPLETED, // stays completed but with approved_at
                'approved_by' => auth()->id(),
                'approved_at' => Carbon::now(),
            ]);

            DB::commit();

            $stockCount->load(['warehouse', 'countedBy', 'approvedBy', 'items.item']);

            return response()->json([
                'success' => true,
                'message' => "Stock count approved. {$adjustmentsCreated} adjustment(s) created.",
                'data' => $this->formatCountDetail($stockCount),
                'adjustments_created' => $adjustmentsCreated,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve stock count', [
                'stock_count_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to approve stock count.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a draft stock count.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');

        $stockCount = StockCount::where('company_id', $companyId)->findOrFail($id);

        if ($stockCount->status !== StockCount::STATUS_DRAFT) {
            return response()->json([
                'error' => 'Only draft stock counts can be deleted.',
            ], 422);
        }

        $stockCount->items()->delete();
        $stockCount->delete();

        return response()->json([
            'success' => true,
            'message' => 'Stock count deleted.',
        ]);
    }

    /**
     * Format stock count for list view.
     */
    private function formatCount(StockCount $count): array
    {
        return [
            'id' => $count->id,
            'warehouse_id' => $count->warehouse_id,
            'warehouse_name' => $count->warehouse?->name,
            'status' => $count->status,
            'count_date' => $count->count_date?->format('Y-m-d'),
            'notes' => $count->notes,
            'counted_by_name' => $count->countedBy?->name,
            'approved_by_name' => $count->approvedBy?->name,
            'total_items_counted' => $count->total_items_counted,
            'total_variance_quantity' => (float) $count->total_variance_quantity,
            'total_variance_value' => (int) $count->total_variance_value,
            'approved_at' => $count->approved_at?->format('Y-m-d H:i:s'),
            'created_at' => $count->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Format stock count with items for detail view.
     */
    private function formatCountDetail(StockCount $count): array
    {
        $data = $this->formatCount($count);

        $data['items'] = $count->items->map(function (StockCountItem $item) {
            return [
                'id' => $item->id,
                'item_id' => $item->item_id,
                'item_name' => $item->item?->name,
                'item_sku' => $item->item?->sku,
                'system_quantity' => (float) $item->system_quantity,
                'counted_quantity' => $item->counted_quantity !== null ? (float) $item->counted_quantity : null,
                'variance_quantity' => $item->variance_quantity !== null ? (float) $item->variance_quantity : null,
                'variance_value' => $item->variance_value,
                'variance_percentage' => $item->variance_percentage,
                'system_unit_cost' => $item->system_unit_cost,
                'notes' => $item->notes,
            ];
        });

        return $data;
    }
}
// CLAUDE-CHECKPOINT
