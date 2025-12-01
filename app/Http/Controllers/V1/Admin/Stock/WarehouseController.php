<?php

namespace App\Http\Controllers\V1\Admin\Stock;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Warehouse Controller
 *
 * Handles CRUD operations for warehouses.
 * All endpoints require FACTURINO_STOCK_V1_ENABLED feature flag.
 */
class WarehouseController extends Controller
{
    /**
     * Check if stock module is enabled, return 403 if not.
     */
    protected function checkStockEnabled(): ?JsonResponse
    {
        if (! StockService::isEnabled()) {
            return response()->json([
                'success' => false,
                'message' => 'Stock module is not enabled.',
            ], 403);
        }

        return null;
    }

    /**
     * Display a listing of warehouses.
     */
    public function index(Request $request): JsonResponse
    {
        if ($error = $this->checkStockEnabled()) {
            return $error;
        }
        $companyId = $request->header('company');

        $query = Warehouse::where('company_id', $companyId)
            ->orderBy('is_default', 'desc')
            ->orderBy('name', 'asc');

        // Filter by active status
        if ($request->has('active_only')) {
            $query->where('is_active', true);
        }

        $warehouses = $query->get();

        return response()->json([
            'success' => true,
            'data' => $warehouses->map(function ($warehouse) {
                return [
                    'id' => $warehouse->id,
                    'name' => $warehouse->name,
                    'code' => $warehouse->code,
                    'address' => $warehouse->address,
                    'is_default' => $warehouse->is_default,
                    'is_active' => $warehouse->is_active,
                    'created_at' => $warehouse->created_at->format('Y-m-d H:i:s'),
                ];
            }),
        ]);
    }

    /**
     * Store a newly created warehouse.
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        if ($error = $this->checkStockEnabled()) {
            return $error;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1000',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $companyId = $request->header('company');

        // Check if code is unique within company
        if (isset($validated['code'])) {
            $exists = Warehouse::where('company_id', $companyId)
                ->where('code', $validated['code'])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'code' => ['Warehouse code already exists in this company.'],
                ]);
            }
        }

        $warehouse = Warehouse::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'address' => $validated['address'] ?? null,
            'is_default' => $validated['is_default'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // If marked as default, unset other defaults
        if ($warehouse->is_default) {
            $warehouse->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'data' => $warehouse,
            'message' => 'Warehouse created successfully.',
        ], 201);
    }

    /**
     * Display the specified warehouse.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        if ($error = $this->checkStockEnabled()) {
            return $error;
        }

        $companyId = $request->header('company');

        $warehouse = Warehouse::where('company_id', $companyId)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $warehouse,
        ]);
    }

    /**
     * Update the specified warehouse.
     *
     * @throws ValidationException
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if ($error = $this->checkStockEnabled()) {
            return $error;
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:1000',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $companyId = $request->header('company');

        $warehouse = Warehouse::where('company_id', $companyId)
            ->where('id', $id)
            ->firstOrFail();

        // Check if code is unique within company (excluding current warehouse)
        if (isset($validated['code'])) {
            $exists = Warehouse::where('company_id', $companyId)
                ->where('code', $validated['code'])
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'code' => ['Warehouse code already exists in this company.'],
                ]);
            }
        }

        $warehouse->update($validated);

        // If marked as default, unset other defaults
        if (isset($validated['is_default']) && $validated['is_default']) {
            $warehouse->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'data' => $warehouse->fresh(),
            'message' => 'Warehouse updated successfully.',
        ]);
    }

    /**
     * Remove the specified warehouse.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        if ($error = $this->checkStockEnabled()) {
            return $error;
        }

        $companyId = $request->header('company');

        $warehouse = Warehouse::where('company_id', $companyId)
            ->where('id', $id)
            ->firstOrFail();

        // Check if warehouse has stock movements
        $hasMovements = $warehouse->stockMovements()->exists();

        if ($hasMovements) {
            return response()->json([
                'error' => 'Cannot delete warehouse with existing stock movements. Please transfer or adjust stock first.',
            ], 422);
        }

        // Don't allow deleting the only warehouse
        $warehouseCount = Warehouse::where('company_id', $companyId)->count();
        if ($warehouseCount <= 1) {
            return response()->json([
                'error' => 'Cannot delete the only warehouse. Create another warehouse first.',
            ], 422);
        }

        // Don't allow deleting default warehouse without setting another as default
        if ($warehouse->is_default) {
            return response()->json([
                'error' => 'Cannot delete the default warehouse. Set another warehouse as default first.',
            ], 422);
        }

        $warehouse->delete();

        return response()->json([
            'message' => 'Warehouse deleted successfully.',
        ]);
    }

    /**
     * Set warehouse as default.
     */
    public function setDefault(Request $request, int $id): JsonResponse
    {
        if ($error = $this->checkStockEnabled()) {
            return $error;
        }

        $companyId = $request->header('company');

        $warehouse = Warehouse::where('company_id', $companyId)
            ->where('id', $id)
            ->firstOrFail();

        $warehouse->setAsDefault();

        return response()->json([
            'warehouse' => $warehouse->fresh(),
            'message' => 'Warehouse set as default successfully.',
        ]);
    }
}
// CLAUDE-CHECKPOINT
