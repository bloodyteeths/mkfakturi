<?php

namespace App\Http\Controllers\V1\Admin\AccountsPayable;

use App\Http\Controllers\Controller;
use App\Http\Requests\BillPaymentRequest;
use App\Http\Resources\BillPaymentResource;
use App\Models\Bill;
use App\Models\BillPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillPaymentsController extends Controller
{
    /**
     * Display a listing of the payments for a bill.
     */
    public function index(Request $request, Bill $bill): JsonResponse
    {
        $this->authorize('view', $bill);

        $limit = $request->input('limit', 10);

        $payments = BillPayment::whereCompany()
            ->whereBill($bill->id)
            ->applyFilters($request->all())
            ->paginateData($limit);

        return BillPaymentResource::collection($payments)
            ->response();
    }

    /**
     * Store a newly created payment for a bill.
     */
    public function store(BillPaymentRequest $request, Bill $bill): JsonResponse
    {
        $this->authorize('update', $bill);

        $payment = BillPayment::create($request->getBillPaymentPayload($bill));

        $payment->markAsCompleted();

        return (new BillPaymentResource($payment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified payment.
     */
    public function show(Bill $bill, BillPayment $payment): JsonResponse
    {
        $this->authorize('view', $bill);

        if ((int) $payment->bill_id !== (int) $bill->id) {
            abort(404);
        }

        return (new BillPaymentResource($payment))
            ->response();
    }

    /**
     * Update the specified payment.
     */
    public function update(BillPaymentRequest $request, Bill $bill, BillPayment $payment): JsonResponse
    {
        $this->authorize('update', $bill);

        if ((int) $payment->bill_id !== (int) $bill->id) {
            abort(404);
        }

        $payment->update($request->getBillPaymentPayload($bill));

        $payment->markAsCompleted();

        return (new BillPaymentResource($payment->fresh()))
            ->response();
    }

    /**
     * Remove the specified payment.
     */
    public function destroy(Bill $bill, BillPayment $payment): JsonResponse
    {
        $this->authorize('update', $bill);

        if ((int) $payment->bill_id !== (int) $bill->id) {
            abort(404);
        }

        $payment->delete();
        $bill->updatePaidStatus();

        return response()->json([
            'success' => true,
        ]);
    }
}
