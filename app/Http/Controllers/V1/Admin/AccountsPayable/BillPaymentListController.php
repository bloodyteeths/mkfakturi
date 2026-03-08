<?php

namespace App\Http\Controllers\V1\Admin\AccountsPayable;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillPaymentResource;
use App\Models\BillPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillPaymentListController extends Controller
{
    /**
     * List all bill payments for the current company (not scoped to a single bill).
     * Used by the Payments page "Bill Payments" tab.
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);

        $query = BillPayment::whereCompany()
            ->applyFilters($request->all());

        $totalCount = (clone $query)->count();

        $payments = $query
            ->with([
                'bill' => function ($q) {
                    $q->select('id', 'bill_number', 'supplier_id')
                      ->without('supplier', 'currency', 'company');
                },
                'bill.supplier:id,name,currency_id',
                'bill.supplier.currency',
                'paymentMethod:id,name',
            ])
            ->paginateData($limit);

        return BillPaymentResource::collection($payments)
            ->additional(['meta' => [
                'bill_payment_total_count' => $totalCount,
            ]]);
    }

    /**
     * Delete one or more bill payments by IDs.
     */
    public function delete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $companyId = $request->header('company');

        $payments = BillPayment::where('company_id', $companyId)
            ->whereIn('id', $request->ids)
            ->get();

        $affectedBillIds = $payments->pluck('bill_id')->unique()->filter();

        foreach ($payments as $payment) {
            $payment->delete();
        }

        // Update paid status for all affected bills
        foreach ($affectedBillIds as $billId) {
            $bill = \App\Models\Bill::find($billId);
            if ($bill) {
                $bill->updatePaidStatus();
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }
}
