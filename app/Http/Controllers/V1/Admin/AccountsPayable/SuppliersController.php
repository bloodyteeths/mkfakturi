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

        $suppliers = Supplier::with('currency')
            ->whereCompany()
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

        // Check usage limit
        $usageService = app(\App\Services\UsageLimitService::class);
        $company = \App\Models\Company::find($request->header('company'));
        if ($company && ! $usageService->canUse($company, 'suppliers_total')) {
            return response()->json($usageService->buildLimitExceededResponse($company, 'suppliers_total'), 402);
        }

        $companyId = (int) $request->header('company');

        if (! $request->allowsDuplicate()) {
            $duplicates = Supplier::findPotentialDuplicates($companyId, [
                'name' => $request->input('name'),
                'tax_id' => $request->input('tax_id'),
                'email' => $request->input('email'),
                'phone' => $request->input('phone'),
            ]);

            if ($duplicates->isNotEmpty()) {
                return response()->json([
                    'is_duplicate_warning' => true,
                    'message' => __('suppliers.duplicate_warning'),
                    'duplicates' => $duplicates,
                ], 200);
            }
        }

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

        $supplier->load(['bills', 'currency', 'linkedCustomer']);

        return (new SupplierResource($supplier))
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

        try {
            Supplier::deleteSuppliers($request->ids);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
