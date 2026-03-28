<?php

namespace Modules\Mk\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\StockService;
use Modules\Mk\Http\Requests\Manufacturing\StoreBomRequest;
use Modules\Mk\Http\Requests\Manufacturing\UpdateBomRequest;
use Modules\Mk\Http\Resources\Manufacturing\BomResource;
use Modules\Mk\Models\Manufacturing\Bom;
use Modules\Mk\Services\ManufacturingService;

class BomController extends Controller
{
    public function __construct(
        protected ManufacturingService $service,
    ) {}

    /**
     * List BOMs for the current company.
     */
    public function index(Request $request)
    {
        $companyId = (int) $request->header('company');

        $query = Bom::where('company_id', $companyId)
            ->with(['outputItem:id,name', 'outputUnit:id,name', 'createdBy:id,name'])
            ->applyFilters($request->query())
            ->latest();

        $perPage = (int) ($request->query('limit', 15));
        $boms = $query->paginate($perPage);

        return BomResource::collection($boms)
            ->additional(['success' => true]);
    }

    /**
     * Show a single BOM with lines.
     */
    public function show(Request $request, int $id)
    {
        $companyId = (int) $request->header('company');

        $bom = Bom::where('company_id', $companyId)
            ->with([
                'outputItem:id,name,unit_id',
                'outputUnit:id,name',
                'currency:id,name,code,symbol',
                'lines.item:id,name,unit_id',
                'lines.unit:id,name',
                'createdBy:id,name',
                'approvedBy:id,name',
            ])
            ->where('id', $id)
            ->first();

        if (! $bom) {
            return response()->json(['success' => false, 'message' => 'BOM not found'], 404);
        }

        return (new BomResource($bom))
            ->additional(['success' => true]);
    }

    /**
     * Create a new BOM.
     */
    public function store(StoreBomRequest $request): JsonResponse
    {

        $companyId = (int) $request->header('company');

        try {
            $bom = $this->service->createBom(
                $companyId,
                $request->except('lines'),
                $request->input('lines', [])
            );

            return (new BomResource($bom))
                ->additional(['success' => true, 'message' => 'BOM created successfully'])
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
     * Update a BOM.
     */
    public function update(UpdateBomRequest $request, int $id)
    {
        $companyId = (int) $request->header('company');

        $bom = Bom::where('company_id', $companyId)->findOrFail($id);

        try {
            $bom = $this->service->updateBom(
                $bom,
                $request->except('lines'),
                $request->has('lines') ? $request->input('lines') : null
            );

            return (new BomResource($bom))
                ->additional(['success' => true, 'message' => 'BOM updated successfully']);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a BOM (soft delete).
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $bom = Bom::where('company_id', $companyId)->findOrFail($id);

        try {
            $this->service->deleteBom($bom);

            return response()->json([
                'success' => true,
                'message' => 'BOM deleted successfully',
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Duplicate a BOM as a new version.
     */
    public function duplicate(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $bom = Bom::where('company_id', $companyId)->findOrFail($id);

        $newBom = $bom->duplicate();

        return (new BomResource($newBom))
            ->additional(['success' => true, 'message' => 'BOM duplicated successfully'])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Calculate normative cost for a BOM.
     */
    public function cost(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $bom = Bom::where('company_id', $companyId)
            ->with('lines.item')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $bom->calculateNormativeCost(),
        ]);
    }

    /**
     * Check stock availability for a BOM at a given production quantity.
     */
    public function stockAvailability(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'quantity' => 'required|numeric|min:0.0001',
            'warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ]);

        $companyId = (int) $request->header('company');
        $bom = Bom::where('company_id', $companyId)
            ->with('lines.item:id,name')
            ->findOrFail($id);

        $plannedQty = (float) $request->query('quantity');
        $warehouseId = $request->query('warehouse_id') ? (int) $request->query('warehouse_id') : null;
        $outputQty = (float) $bom->output_quantity ?: 1;
        $stockService = app(StockService::class);

        $materials = [];
        $allAvailable = true;

        foreach ($bom->lines as $line) {
            $requiredQty = ((float) $line->quantity / $outputQty) * $plannedQty;
            $wastageMultiplier = 1 + ((float) $line->wastage_percent / 100);
            $requiredWithWastage = $requiredQty * $wastageMultiplier;

            $stock = $stockService->getItemStock($companyId, $line->item_id, $warehouseId);
            $availableQty = $stock['quantity'] ?? 0;
            $sufficient = $availableQty >= $requiredWithWastage;

            if (! $sufficient) {
                $allAvailable = false;
            }

            $materials[] = [
                'item_id' => $line->item_id,
                'item_name' => $line->item?->name,
                'required_qty' => round($requiredWithWastage, 4),
                'available_qty' => round($availableQty, 4),
                'shortage' => $sufficient ? 0 : round($requiredWithWastage - $availableQty, 4),
                'sufficient' => $sufficient,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'all_available' => $allAvailable,
                'materials' => $materials,
            ],
        ]);
    }
}

// CLAUDE-CHECKPOINT
