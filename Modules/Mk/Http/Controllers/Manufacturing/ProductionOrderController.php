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
     * Reschedule a draft or in-progress order (drag-drop from Gantt).
     */
    public function reschedule(Request $request, int $id)
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($id);

        if ($order->status === ProductionOrder::STATUS_COMPLETED || $order->status === ProductionOrder::STATUS_CANCELLED) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot reschedule completed or cancelled orders.',
            ], 422);
        }

        $request->validate([
            'order_date' => 'required|date',
            'expected_completion_date' => 'required|date|after_or_equal:order_date',
        ]);

        $order->update([
            'order_date' => $request->order_date,
            'expected_completion_date' => $request->expected_completion_date,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'order_date' => $order->order_date->format('Y-m-d'),
                'expected_completion_date' => $order->expected_completion_date->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Return all schedulable orders for the Gantt view.
     */
    public function ganttData(Request $request)
    {
        $companyId = (int) $request->header('company');

        $orders = ProductionOrder::where('company_id', $companyId)
            ->whereIn('status', [
                ProductionOrder::STATUS_DRAFT,
                ProductionOrder::STATUS_IN_PROGRESS,
                ProductionOrder::STATUS_COMPLETED,
            ])
            ->with(['outputItem:id,name', 'bom:id,name,code', 'workCenter:id,name', 'dependsOn:production_orders.id'])
            ->orderBy('order_date')
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'order_number' => $o->order_number,
                'item_name' => $o->outputItem?->name ?? '-',
                'bom_name' => $o->bom?->name,
                'bom_code' => $o->bom?->code,
                'work_center' => $o->workCenter?->name,
                'work_center_id' => $o->work_center_id,
                'status' => $o->status,
                'start' => $o->order_date?->format('Y-m-d'),
                'end' => $o->expected_completion_date?->format('Y-m-d')
                    ?? $o->order_date?->copy()->addDays(7)->format('Y-m-d'),
                'planned_quantity' => (float) $o->planned_quantity,
                'actual_quantity' => (float) $o->actual_quantity,
                'total_production_cost' => $o->total_production_cost,
                'is_overdue' => $o->expected_completion_date
                    && $o->expected_completion_date->lt(now()),
                'can_reschedule' => in_array($o->status, [
                    ProductionOrder::STATUS_DRAFT,
                    ProductionOrder::STATUS_IN_PROGRESS,
                ]),
                'depends_on' => $o->dependsOn->pluck('id')->toArray(),
            ]);

        // Work centers for grouping
        $workCenters = \Modules\Mk\Models\Manufacturing\WorkCenter::where('company_id', $companyId)
            ->active()
            ->orderBy('sort_order')
            ->get(['id', 'name', 'code', 'capacity_hours_per_day']);

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $orders,
                'work_centers' => $workCenters,
            ],
        ]);
    }

    // CLAUDE-CHECKPOINT: Added reschedule() and ganttData() for Gantt scheduling

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
    // Quality Control
    // ====================================================================

    /**
     * List QC checks for a production order.
     */
    public function qcChecks(Request $request, int $orderId): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($orderId);

        $checks = $order->qcChecks()
            ->with('inspector:id,name')
            ->orderByDesc('check_date')
            ->get();

        return response()->json(['success' => true, 'data' => $checks]);
    }

    /**
     * Record a QC inspection check.
     */
    public function addQcCheck(Request $request, int $orderId): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($orderId);

        if ($order->isCompleted() || $order->isCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot add QC check to completed or cancelled orders.',
            ], 422);
        }

        $validated = $request->validate([
            'check_date' => 'required|date',
            'result' => 'required|in:pass,fail,conditional',
            'quantity_inspected' => 'required|numeric|min:0',
            'quantity_passed' => 'required|numeric|min:0',
            'quantity_rejected' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
            'checklist' => 'nullable|array',
            'checklist.*.criterion' => 'required_with:checklist|string',
            'checklist.*.result' => 'required_with:checklist|in:pass,fail,na',
            'checklist.*.notes' => 'nullable|string',
            'defects' => 'nullable|array',
            'defects.*.type' => 'required_with:defects|string',
            'defects.*.quantity' => 'required_with:defects|numeric|min:0',
            'defects.*.severity' => 'nullable|in:minor,major,critical',
            'defects.*.notes' => 'nullable|string',
        ]);

        $validated['production_order_id'] = $order->id;
        $validated['company_id'] = $companyId;
        $validated['inspector_id'] = $request->user()?->id;

        $check = \Modules\Mk\Models\Manufacturing\QcCheck::create($validated);

        return response()->json([
            'success' => true,
            'data' => $check->load('inspector:id,name'),
            'message' => 'QC check recorded successfully.',
        ], 201);
    }

    /**
     * Process disposition for a QC check (rework or scrap).
     */
    public function disposeQcCheck(Request $request, int $orderId, int $checkId): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($orderId);

        $check = $order->qcChecks()->findOrFail($checkId);

        // Authorization: only the original inspector or company owner can dispose
        $user = $request->user();
        $isInspector = $user && $check->inspector_id === $user->id;
        $isOwner = $user && $order->company && $order->company->owner_id === $user->id;
        $isSuperAdmin = $user && method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();

        if (! $isInspector && ! $isOwner && ! $isSuperAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'Only the inspector or company owner can dispose QC checks.',
            ], 403);
        }

        $request->validate([
            'disposition' => 'required|in:rework,scrap',
        ]);

        try {
            $check = $this->service->processDisposition($check, $request->disposition);

            return response()->json([
                'success' => true,
                'data' => $check,
                'message' => $request->disposition === 'rework'
                    ? 'Rework order created successfully.'
                    : 'Scrap recorded successfully.',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Scan barcode to record material consumption.
     */
    public function scanBarcode(Request $request, int $orderId): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($orderId);

        $request->validate([
            'barcode' => 'required|string|max:255',
            'quantity' => 'nullable|numeric|min:0.01',
        ]);

        try {
            $result = $this->service->scanAndConsume(
                $order,
                $request->barcode,
                (float) ($request->quantity ?? 1)
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => "Consumed {$result['quantity']} × {$result['item']['name']}",
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Set dependency chain for a production order.
     */
    public function setDependencies(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)->findOrFail($id);

        $request->validate([
            'depends_on' => 'present|array',
            'depends_on.*' => 'integer',
        ]);

        $dependsOnIds = $request->depends_on ?? [];

        // Validate all referenced orders belong to same company
        if (! empty($dependsOnIds)) {
            $validIds = ProductionOrder::where('company_id', $companyId)
                ->whereIn('id', $dependsOnIds)
                ->pluck('id')
                ->toArray();

            if (count($validIds) !== count($dependsOnIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some dependency orders not found.',
                ], 422);
            }

            // Self-reference check
            if (in_array($order->id, $dependsOnIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'An order cannot depend on itself.',
                ], 422);
            }

            // Simple circular dependency check
            if ($this->wouldCreateCycle($order->id, $dependsOnIds, $companyId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'This would create a circular dependency.',
                ], 422);
            }
        }

        // Sync the pivot table
        $order->dependsOn()->sync($dependsOnIds);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'depends_on' => $order->dependsOn()->pluck('production_orders.id'),
            ],
            'message' => 'Dependencies updated.',
        ]);
    }

    /**
     * Auto-schedule draft orders based on work center capacity.
     */
    public function autoSchedule(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $workCenterId = $request->work_center_id ? (int) $request->work_center_id : null;

        $scheduler = app(\Modules\Mk\Services\AutoScheduleService::class);
        $result = $scheduler->schedule($companyId, $workCenterId);

        return response()->json([
            'success' => true,
            'data' => [
                'count' => count($result),
                'orders' => $result,
            ],
            'message' => count($result) > 0
                ? count($result).' orders scheduled successfully.'
                : 'No draft orders to schedule.',
        ]);
    }

    /**
     * Check if adding dependencies would create a cycle (bidirectional BFS).
     *
     * Forward check: BFS from each newDep's existing deps — if we reach $orderId, it's a cycle.
     * Reverse check: BFS from $orderId's dependents — if we reach any newDep, it's a cycle.
     */
    private function wouldCreateCycle(int $orderId, array $newDeps, int $companyId): bool
    {
        // Forward: walk existing deps of each newDep to see if $orderId is reachable
        $visited = [];
        $queue = $newDeps;

        while (! empty($queue)) {
            $current = array_shift($queue);
            if ($current === $orderId) {
                return true;
            }
            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;

            $deps = \Illuminate\Support\Facades\DB::table('production_order_dependencies')
                ->where('order_id', $current)
                ->pluck('depends_on_order_id')
                ->toArray();

            foreach ($deps as $dep) {
                if (! isset($visited[$dep])) {
                    $queue[] = $dep;
                }
            }
        }

        // Reverse: walk dependents of $orderId to see if any newDep is reachable
        $newDepSet = array_flip($newDeps);
        $visited = [];
        $queue = [$orderId];

        while (! empty($queue)) {
            $current = array_shift($queue);
            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;

            // Find orders that depend ON $current (reverse edges)
            $dependents = \Illuminate\Support\Facades\DB::table('production_order_dependencies')
                ->where('depends_on_order_id', $current)
                ->pluck('order_id')
                ->toArray();

            foreach ($dependents as $dependent) {
                if (isset($newDepSet[$dependent])) {
                    return true;
                }
                if (! isset($visited[$dependent])) {
                    $queue[] = $dependent;
                }
            }
        }

        return false;
    }

    /**
     * Duplicate a production order — creates a new draft from the same BOM.
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $order = ProductionOrder::where('company_id', $companyId)
            ->with('bom')
            ->findOrFail($id);

        if (! $order->bom) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot duplicate: BOM not found.',
            ], 422);
        }

        try {
            $newOrder = $this->service->createProductionOrder(
                $order->bom,
                (float) $order->planned_quantity,
                [
                    'order_date' => now()->format('Y-m-d'),
                    'expected_completion_date' => $order->expected_completion_date
                        ? now()->addDays(
                            $order->order_date && $order->expected_completion_date
                                ? $order->order_date->diffInDays($order->expected_completion_date)
                                : 7
                        )->format('Y-m-d')
                        : null,
                    'output_warehouse_id' => $order->output_warehouse_id,
                    'work_center_id' => $order->work_center_id,
                    'notes' => $order->notes,
                ]
            );

            return (new ProductionOrderResource($newOrder))
                ->additional(['success' => true, 'message' => 'Order duplicated as draft'])
                ->response()
                ->setStatusCode(201);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    // CLAUDE-CHECKPOINT: Added duplicate, notifications

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
