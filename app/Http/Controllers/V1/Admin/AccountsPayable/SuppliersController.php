<?php

namespace App\Http\Controllers\V1\Admin\AccountsPayable;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteSuppliersRequest;
use App\Http\Requests\SupplierRequest;
use App\Http\Resources\SupplierCollection;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuppliersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Supplier::class);

        $limit = $request->input('limit', 10);

        $suppliers = Supplier::whereCompany()
            ->applyFilters($request->all())
            ->paginateData($limit);

        return (new SupplierCollection($suppliers))
            ->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(SupplierRequest $request): JsonResponse
    {
        $this->authorize('create', Supplier::class);

        $supplier = Supplier::create($request->getSupplierPayload());

        return (new SupplierResource($supplier))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        $supplier->load(['bills', 'currency']);

        // Prepare chart data (basic structure for now)
        $chartData = [
            'billTotals' => array_fill(0, 12, 0),
            'paymentTotals' => array_fill(0, 12, 0),
            'expenseTotals' => array_fill(0, 12, 0),
            'netProfits' => array_fill(0, 12, 0),
            'months' => [
                'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'
            ],
            'billsTotal' => 0,
            'paymentsTotal' => 0,
            'totalExpenses' => 0,
            'netProfit' => 0,
        ];

        return (new SupplierResource($supplier))
            ->additional(['meta' => ['chartData' => $chartData]])
            ->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(SupplierRequest $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('update', $supplier);

        $supplier->update($request->getSupplierPayload());

        return (new SupplierResource($supplier->fresh()))
            ->response();
    }

    /**
     * Remove the specified resources from storage.
     */
    public function delete(DeleteSuppliersRequest $request): JsonResponse
    {
        $this->authorize('deleteMultiple', Supplier::class);

        Supplier::deleteSuppliers($request->ids);

        return response()->json([
            'success' => true,
        ]);
    }
}
// CLAUDE-CHECKPOINT

