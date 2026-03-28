<?php

namespace Modules\Mk\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Mk\Http\Requests\Manufacturing\CompleteProductionRequest;
use Modules\Mk\Http\Requests\Manufacturing\LaborEntryRequest;
use Modules\Mk\Http\Requests\Manufacturing\MaterialConsumptionRequest;
use Modules\Mk\Http\Requests\Manufacturing\OverheadEntryRequest;
use Modules\Mk\Http\Requests\Manufacturing\StoreProductionOrderRequest;
use Modules\Mk\Http\Resources\Manufacturing\ProductionOrderResource;
use Modules\Mk\Models\Manufacturing\Bom;
use Modules\Mk\Models\Manufacturing\ProductionOrder;
use Modules\Mk\Services\ManufacturingService;

class ProductionOrderController extends Controller
{
    public function __construct(
        protected ManufacturingService $service,
    ) {}

    /**
     * List production orders for the current company.
     */
    public function index(Request $request)
    {
        $companyId = (int) $request->header('company');

        $query = ProductionOrder::where('company_id', $companyId)
            ->with(['outputItem:id,name', 'bom:id,name,code', 'createdBy:id,name'])
            ->applyFilters($request->query())
            ->latest();

        $perPage = (int) ($request->query('limit', 15));
        $orders = $query->paginate($perPage);

        return ProductionOrderResource::collection($orders)
            ->additional(['success' => true]);
    }

    /**
     * Show a single production order with all details.
     */
    public function show(Request $request, int $id)
    {
        $companyId = (int) $request->header('company');

        $order = ProductionOrder::where('company_id', $companyId)
            ->with([
                'outputItem:id,name,unit_id',
                'bom:id,name,code',
                'currency:id,name,code,symbol',
                'outputWarehouse:id,name',
                'materials.item:id,name,unit_id',
                'materials.warehouse:id,name',
                'laborEntries',
                'overheadEntries',
                'coProductionOutputs.item:id,name',
                'createdBy:id,name',
                'approvedBy:id,name',
            ])
            ->where('id', $id)
            ->first();

        if (! $order) {
            return response()->json(['success' => false, 'message' => 'Production order not found'], 404);
        }

        return (new ProductionOrderResource($order))
            ->additional(['success' => true]);
    }

    /**
     * Create a new production order from a BOM.
     */
    public function store(StoreProductionOrderRequest $request): JsonResponse
    {

        $companyId = (int) $request->header('company');
        $bom = Bom::where('company_id', $companyId)->findOrFail($request->input('bom_id'));

        try {
            $order = $this->service->createProductionOrder(
                $bom,
                (float) $request->input('planned_quantity'),
                $request->only(['order_date', 'expected_completion_date', 'output_warehouse_id', 'notes'])
            );

            return (new ProductionOrderResource($order))
                ->additional(['success' => true, 'message' => 'Production order created successfully'])
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Update a draft production order.
     */
    public function update(Request $request, int $id)
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($id);

        if (! $order->canEdit()) {
            return response()->json([
                'success' => false,
                'message' => 'Only draft orders can be edited.',
            ], 422);
        }

        $request->validate([
            'planned_quantity' => 'sometimes|numeric|min:0.0001',
            'order_date' => 'nullable|date',
            'expected_completion_date' => 'nullable|date',
            'output_warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'notes' => 'nullable|string|max:2000',
        ]);

        $order->update($request->only([
            'planned_quantity', 'order_date', 'expected_completion_date',
            'output_warehouse_id', 'notes',
        ]));

        return (new ProductionOrderResource($order->fresh()))
            ->additional(['success' => true, 'message' => 'Production order updated successfully']);
    }

    /**
     * Start production — status → in_progress.
     */
    public function start(Request $request, int $id)
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($id);

        try {
            $this->service->startProduction($order);

            return (new ProductionOrderResource($order->fresh()))
                ->additional(['success' => true, 'message' => 'Production started']);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Complete production — finalize costs and create stock movements.
     */
    public function complete(CompleteProductionRequest $request, int $id)
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($id);

        try {
            $order = $this->service->completeProduction(
                $order,
                (float) $request->input('actual_quantity'),
                $request->input('co_outputs')
            );

            return (new ProductionOrderResource($order))
                ->additional(['success' => true, 'message' => 'Production completed successfully']);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel a production order.
     */
    public function cancel(Request $request, int $id)
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($id);

        try {
            $this->service->cancelProduction($order, $request->input('reason'));

            return (new ProductionOrderResource($order->fresh()))
                ->additional(['success' => true, 'message' => 'Production order cancelled']);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // ====================================================================
    // Child Resource Endpoints
    // ====================================================================

    /**
     * Record material consumption.
     */
    public function addMaterial(MaterialConsumptionRequest $request, int $orderId): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($orderId);

        try {
            $material = $this->service->recordMaterialConsumption(
                $order,
                (int) $request->input('material_id'),
                (float) $request->input('actual_quantity'),
                (float) ($request->input('wastage_quantity', 0)),
                $request->input('warehouse_id') ? (int) $request->input('warehouse_id') : null,
                $request->input('notes')
            );

            return response()->json([
                'success' => true,
                'data' => $material,
                'message' => 'Material consumption recorded',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Add labor cost entry.
     */
    public function addLabor(LaborEntryRequest $request, int $orderId): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($orderId);

        try {
            $labor = $this->service->recordLabor($order, $request->all());

            return response()->json([
                'success' => true,
                'data' => $labor,
                'message' => 'Labor cost recorded',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Add overhead cost entry.
     */
    public function addOverhead(OverheadEntryRequest $request, int $orderId): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($orderId);

        try {
            $overhead = $this->service->recordOverhead($order, $request->all());

            return response()->json([
                'success' => true,
                'data' => $overhead,
                'message' => 'Overhead cost recorded',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

// CLAUDE-CHECKPOINT
