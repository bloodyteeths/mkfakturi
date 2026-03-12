<?php

namespace App\Http\Controllers\V1\Admin\AccountsPayable;

use App\Http\Controllers\Controller;
use App\Http\Requests\BillRequest;
use App\Http\Requests\DeleteBillsRequest;
use App\Http\Resources\BillCollection;
use App\Http\Resources\BillResource;
use App\Jobs\GenerateBillPdfJob;
use App\Models\BankAccount;
use App\Models\Bill;
use App\Models\CompanyInboundAlias;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Mk\Services\Pp30PdfService;

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
            'media',
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

            // Check usage limit
            $usageService = app(\App\Services\UsageLimitService::class);
            $company = \App\Models\Company::find($request->header('company'));
            if ($company && ! $usageService->canUse($company, 'bills_per_month')) {
                return response()->json($usageService->buildLimitExceededResponse($company, 'bills_per_month'), 402);
            }

            \Log::info('BillsController::store - Authorization passed');

            $billPayload = $request->getBillPayload();
            \Log::info('BillsController::store - Bill payload prepared', ['payload' => $billPayload]);

            $bill = Bill::create($billPayload);
            \Log::info('BillsController::store - Bill created', ['bill_id' => $bill->id]);

            if ($request->has('items')) {
                \Log::info('BillsController::store - Processing items', ['item_count' => count($request->items)]);

                // No need to delete items/taxes for a new bill - they don't exist yet
                // Note: Stock movements are handled by StockBillItemObserver (registered in AppServiceProvider)
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

            // Attach scanned invoice file as media if provided
            if ($request->scanned_receipt_path) {
                try {
                    $disk = config('filesystems.default', 'local');
                    $storedPath = $request->scanned_receipt_path;

                    if (Storage::disk($disk)->exists($storedPath)) {
                        $bill->addMediaFromDisk($storedPath, $disk)
                            ->toMediaCollection('scanned_invoice');
                        \Log::info('BillsController::store - Scanned invoice attached as media', [
                            'bill_id' => $bill->id,
                            'path' => $storedPath,
                        ]);
                    }
                } catch (\Throwable $e) {
                    \Log::warning('BillsController::store - Failed to attach scanned invoice', [
                        'bill_id' => $bill->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $bill->load($this->billResourceRelations());
            \Log::info('BillsController::store - Relationships loaded');

            GenerateBillPdfJob::dispatchAfterResponse($bill->id);
            \Log::info('BillsController::store - PDF generation job dispatched');

            // Increment usage after successful creation
            $usageService->incrementUsage($company, 'bills_per_month');

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
            // Note: Stock movements are handled by StockBillItemObserver:
            // - deleted() event reverses stock when items are deleted
            // - created() event creates stock when new items are added
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
     * Mark bill as sent (without emailing).
     */
    public function markAsSent(Bill $bill): JsonResponse
    {
        $this->authorize('send', $bill);

        $bill->markAsSent();

        return response()->json([
            'success' => true,
            'message' => __('bills.marked_sent_message'),
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
            'message' => __('bills.sent_message'),
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
        \Log::info('BillsController::downloadPdf - Starting', [
            'bill_id' => $bill->id,
            'bill_number' => $bill->bill_number,
        ]);

        try {
            $this->authorize('view', $bill);

            \Log::info('BillsController::downloadPdf - Authorization passed');

            $pdf = $bill->getPDFData();

            \Log::info('BillsController::downloadPdf - PDF generated successfully');

            return $pdf->download("bill-{$bill->bill_number}.pdf");
        } catch (\Exception $e) {
            \Log::error('BillsController::downloadPdf - Error', [
                'bill_id' => $bill->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate PP30 payment slip PDF for a single bill.
     */
    public function pp30Pdf(Request $request, Bill $bill)
    {
        $this->authorize('view', $bill);

        $bill->load(['supplier', 'currency', 'company']);
        $company = $bill->company;

        if (empty($bill->supplier?->iban)) {
            return response()->json([
                'success' => false,
                'message' => 'Добавувачот нема IBAN. Додајте IBAN во поставките на добавувачот.',
            ], 422);
        }

        $bankAccountId = $request->query('bank_account_id');
        $bankAccount = $bankAccountId ? BankAccount::find($bankAccountId) : null;

        try {
            $pp30Service = app(Pp30PdfService::class);
            $pdf = $pp30Service->generateForBill($bill, $company, $bankAccount);

            return $pdf->download("PP30_{$bill->bill_number}.pdf");
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Return the company's inbound email alias for supplier invoice forwarding.
     */
    public function inboundAlias(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        $alias = CompanyInboundAlias::where('company_id', $companyId)->first();

        if (! $alias) {
            return response()->json(['email' => null]);
        }

        $domain = config('services.postmark_inbound.domain', 'in.facturino.mk');

        return response()->json([
            'email' => $alias->alias.'@'.$domain,
        ]);
    }
}
// CLAUDE-CHECKPOINT
