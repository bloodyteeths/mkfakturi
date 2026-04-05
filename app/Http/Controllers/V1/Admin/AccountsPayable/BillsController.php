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

    private function billListRelations(): array
    {
        return [
            'supplier:id,name',
            'currency',
            'company:id,name',
        ];
    }
    private function billResourceRelations(): array
    {
        return [
            'supplier',
            'currency',
            'company',
            'creator',
            'items',
            'items.fields.customField',
            'items.taxes',
            'items.taxes.taxType',
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
            ->with($this->billListRelations())
            ->paginateData($limit);

        return \App\Http\Resources\BillListResource::collection($bills)
            ->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BillRequest $request): JsonResponse
    {
        $this->authorize('create', Bill::class);

        // Check usage limit
        $usageService = app(\App\Services\UsageLimitService::class);
        $company = \App\Models\Company::find($request->header('company'));
        if ($company && ! $usageService->canUse($company, 'bills_per_month')) {
            return response()->json($usageService->buildLimitExceededResponse($company, 'bills_per_month'), 402);
        }

        $companyId = (int) $request->header('company');

        if (! $request->allowsDuplicate()) {
            $duplicates = Bill::findPotentialDuplicates($companyId, [
                'supplier_id' => $request->input('supplier_id'),
                'total' => $request->input('total'),
                'bill_date' => $request->input('bill_date'),
            ]);

            if ($duplicates->isNotEmpty()) {
                return response()->json([
                    'is_duplicate_warning' => true,
                    'message' => __('bills.duplicate_warning'),
                    'duplicates' => $duplicates,
                ], 200);
            }
        }

        $bill = Bill::create($request->getBillPayload());

        if ($request->has('items')) {
            // Note: Stock movements are handled by StockBillItemObserver (registered in AppServiceProvider)
            Bill::createItems($bill, $request->items);

            if ($request->has('taxes') && (! empty($request->taxes))) {
                Bill::createTaxes($bill, $request->taxes);
            }
        }

        if ($request->customFields) {
            $bill->addCustomFields($request->customFields);
        }

        // Attach scanned invoice file as media if provided
        if ($request->scanned_receipt_path) {
            try {
                $disk = config('filesystems.media_disk');
                $storedPath = $request->scanned_receipt_path;

                if (Storage::disk($disk)->exists($storedPath)) {
                    $bill->addMediaFromDisk($storedPath, $disk)
                        ->toMediaCollection('scanned_invoice');
                }
            } catch (\Throwable $e) {
                \Log::warning('BillsController::store - Failed to attach scanned invoice', [
                    'bill_id' => $bill->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $bill->load($this->billResourceRelations());

        GenerateBillPdfJob::dispatchAfterResponse($bill->id);

        // Increment usage after successful creation
        $usageService->incrementUsage($company, 'bills_per_month');

        return (new BillResource($bill))
            ->response()
            ->setStatusCode(201);
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
     * Upload a scanned invoice document for a bill.
     */
    public function uploadScannedInvoice(Bill $bill, Request $request): JsonResponse
    {
        $this->authorize('update', $bill);

        $request->validate([
            'scanned_invoice' => 'required|file|max:20480',
        ]);

        $bill->clearMediaCollection('scanned_invoice');
        $bill->addMediaFromRequest('scanned_invoice')
            ->toMediaCollection('scanned_invoice');

        return response()->json(['success' => true]);
    }
    // CLAUDE-CHECKPOINT

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
        $this->authorize('view', $bill);

        $pdf = $bill->getPDFData();

        return $pdf->download("bill-{$bill->bill_number}.pdf");
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

    /**
     * Get IFRS journal entry lines for a bill.
     */
    public function journalEntry(Bill $bill): JsonResponse
    {
        $this->authorize('view', $bill);

        if (! $bill->posted_to_ifrs || ! $bill->ifrs_transaction_id) {
            return response()->json([
                'success' => false,
                'message' => 'Bill has not been posted to IFRS.',
                'entries' => [],
            ]);
        }

        try {
            $entries = \Illuminate\Support\Facades\DB::table('ifrs_line_items as li')
                ->join('ifrs_accounts as a', 'li.account_id', '=', 'a.id')
                ->where('li.transaction_id', $bill->ifrs_transaction_id)
                ->select([
                    'a.code as account_code',
                    'a.name as account_name',
                    'li.amount',
                    'li.credited',
                    'li.narration',
                ])
                ->orderBy('li.credited')
                ->orderBy('li.id')
                ->get();

            $formatted = $entries->map(function ($entry) {
                return [
                    'account_code' => $entry->account_code,
                    'account_name' => $entry->account_name,
                    'debit' => ! $entry->credited ? round($entry->amount, 2) : 0,
                    'credit' => $entry->credited ? round($entry->amount, 2) : 0,
                    'narration' => $entry->narration,
                ];
            });

            return response()->json([
                'success' => true,
                'transaction_id' => $bill->ifrs_transaction_id,
                'entries' => $formatted,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to fetch journal entry for bill', [
                'bill_id' => $bill->id,
                'ifrs_transaction_id' => $bill->ifrs_transaction_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch journal entry.',
                'entries' => [],
            ]);
        }
    }

    /**
     * Generate and download Примка (Goods Receipt Note) PDF for a bill.
     */
    public function priemnica(Request $request, Bill $bill): mixed
    {
        $this->authorize('view', $bill);

        $bill->load(['supplier', 'items', 'company.address', 'currency', 'creator']);

        $pdf = \PDF::loadView('app.pdf.reports.priemnica-bill', [
            'bill' => $bill,
            'company' => $bill->company,
            'supplier' => $bill->supplier,
            'items' => $bill->items,
            'currency' => $bill->currency,
        ]);

        return $pdf->download("priemnica-{$bill->bill_number}.pdf");
    }
}
// CLAUDE-CHECKPOINT
