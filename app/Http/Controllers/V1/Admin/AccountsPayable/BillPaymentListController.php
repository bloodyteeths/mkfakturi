<?php

namespace App\Http\Controllers\V1\Admin\AccountsPayable;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillPaymentResource;
use App\Models\BillPayment;
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
                'bill:id,bill_number,supplier_id',
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
}
// CLAUDE-CHECKPOINT
