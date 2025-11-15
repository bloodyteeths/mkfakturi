<?php

namespace App\Http\Controllers\V1\Admin\AccountsPayable;

use App\Http\Controllers\Controller;
use App\Http\Requests\BillRequest;
use App\Http\Requests\DeleteBillsRequest;
use App\Http\Resources\BillCollection;
use App\Http\Resources\BillResource;
use App\Jobs\GenerateBillPdfJob;
use App\Models\Bill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillsController extends Controller
{
    /**
     * Relations required to render the bill resource without N+1 queries.
     *
     * @return array<int, string>
     */
    private function billResourceRelations(): array
    {
        return [
            'supplier',
            'currency',
            'company',
            'creator',
            'items',
            'items.fields.customField',
            'payments',
            'taxes.taxType',
            'taxes.currency',
            'fields.customField',
            'fields.company',
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Bill::class);

        $limit = $request->input('limit', 10);

        $bills = Bill::whereCompany()
            ->applyFilters($request->all())
            ->with($this->billResourceRelations())
            ->paginateData($limit);

        return (new BillCollection($bills))
            ->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BillRequest $request): JsonResponse
    {
        \Log::info('BillsController::store - Starting bill creation', [
            'user_id' => auth()->id(),
            'company_id' => $request->header('company'),
            'request_data' => $request->all(),
        ]);

        try {
            $this->authorize('create', Bill::class);

            \Log::info('BillsController::store - Authorization passed');

            $billPayload = $request->getBillPayload();
            \Log::info('BillsController::store - Bill payload prepared', ['payload' => $billPayload]);

            $bill = Bill::create($billPayload);
            \Log::info('BillsController::store - Bill created', ['bill_id' => $bill->id]);

            if ($request->has('items')) {
                \Log::info('BillsController::store - Processing items', ['item_count' => count($request->items)]);

                $bill->items()->delete();
                $bill->taxes()->delete();

                Bill::createItems($bill, $request->items);
                \Log::info('BillsController::store - Items created');

                if ($request->has('taxes') && (! empty($request->taxes))) {
                    Bill::createTaxes($bill, $request->taxes);
                    \Log::info('BillsController::store - Taxes created');
                }
            }

            if ($request->customFields) {
                \Log::info('BillsController::store - Adding custom fields');
                $bill->addCustomFields($request->customFields);
            }

            $bill->load($this->billResourceRelations());
            \Log::info('BillsController::store - Relationships loaded');

            GenerateBillPdfJob::dispatchAfterResponse($bill->id);
            \Log::info('BillsController::store - PDF generation job dispatched');

            return (new BillResource($bill))
                ->response()
                ->setStatusCode(201);
        } catch (\Exception $e) {
            \Log::error('BillsController::store - Error creating bill', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);
            throw $e;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Bill $bill): JsonResponse
    {
        $this->authorize('view', $bill);

        $bill->load($this->billResourceRelations());

        return (new BillResource($bill))
            ->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BillRequest $request, Bill $bill): JsonResponse
    {
        $this->authorize('update', $bill);

        $bill->update($request->getBillPayload());

        if ($request->has('items')) {
            $bill->items()->delete();
            $bill->taxes()->delete();

            Bill::createItems($bill, $request->items);

            if ($request->has('taxes') && (! empty($request->taxes))) {
                Bill::createTaxes($bill, $request->taxes);
            }
        }

        if ($request->customFields) {
            $bill->updateCustomFields($request->customFields);
        }

        $bill->refresh()->load($this->billResourceRelations());

        GenerateBillPdfJob::dispatchAfterResponse($bill->id, true);

        return (new BillResource($bill))
            ->response();
    }

    /**
     * Remove the specified resources from storage.
     */
    public function delete(DeleteBillsRequest $request): JsonResponse
    {
        $this->authorize('deleteMultiple', Bill::class);

        Bill::deleteBills($request->ids);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Send bill to supplier via email.
     */
    public function send(Request $request, Bill $bill): JsonResponse
    {
        $this->authorize('send', $bill);

        $request->validate([
            'to' => 'required|email',
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);

        // For now, just mark as sent and rely on future email implementation
        $bill->markAsSent();

        return response()->json([
            'success' => true,
            'message' => 'Bill sent successfully',
        ]);
    }

    /**
     * Mark bill as viewed.
     */
    public function markAsViewed(Bill $bill): JsonResponse
    {
        $this->authorize('markAsViewed', $bill);

        $bill->markAsViewed();

        return response()->json([
            'success' => true,
            'message' => 'Bill marked as viewed',
        ]);
    }

    /**
     * Mark bill as completed.
     */
    public function markAsCompleted(Bill $bill): JsonResponse
    {
        $this->authorize('markAsCompleted', $bill);

        $bill->markAsCompleted();

        return response()->json([
            'success' => true,
            'message' => 'Bill marked as completed',
        ]);
    }

    /**
     * Download bill PDF.
     */
    public function downloadPdf(Bill $bill)
    {
        $this->authorize('view', $bill);

        $pdf = $bill->getPDFData();

        return $pdf->download("bill-{$bill->bill_number}.pdf");
    }
}

