<?php

namespace App\Http\Controllers\V1\Admin\RecurringInvoice;

use App\Http\Controllers\Controller;
use App\Http\Requests\RecurringInvoiceRequest;
use App\Http\Resources\RecurringInvoiceResource;
use App\Models\RecurringInvoice;
use Illuminate\Http\Request;

class RecurringInvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', RecurringInvoice::class);

        $limit = $request->has('limit') ? $request->limit : 10;

        $recurringInvoices = RecurringInvoice::query()
            ->with([
                'customer.currency',
                'company',
                'currency',
            ])
            ->whereCompany()
            ->applyFilters($request->all())
            ->paginateData($limit);

        return RecurringInvoiceResource::collection($recurringInvoices)
            ->additional(['meta' => [
                'recurring_invoice_total_count' => RecurringInvoice::whereCompany()->count(),
            ]]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(RecurringInvoiceRequest $request)
    {
        $this->authorize('create', RecurringInvoice::class);

        // Enforce usage limit for active recurring invoices
        if ($request->status === RecurringInvoice::ACTIVE) {
            $companyId = $request->header('company');
            $company = \App\Models\Company::find($companyId);

            if ($company) {
                $usageService = app(\App\Services\UsageLimitService::class);
                if (! $usageService->canUse($company, 'recurring_invoices_active')) {
                    return response()->json(
                        $usageService->buildLimitExceededResponse($company, 'recurring_invoices_active'),
                        403
                    );
                }
            }
        }

        $recurringInvoice = RecurringInvoice::createFromRequest($request);

        $recurringInvoice->load([
            'customer.currency',
            'company',
            'currency',
            'items.taxes',
            'taxes',
            'invoices.customer.currency',
            'fields',
            'creator',
        ]);

        return new RecurringInvoiceResource($recurringInvoice);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(RecurringInvoice $recurringInvoice)
    {
        $this->authorize('view', $recurringInvoice);

        $recurringInvoice->load([
            'customer.currency',
            'company',
            'currency',
            'items.taxes',
            'taxes',
            'invoices.customer.currency',
            'fields',
            'creator',
        ]);

        return new RecurringInvoiceResource($recurringInvoice);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(RecurringInvoiceRequest $request, RecurringInvoice $recurringInvoice)
    {
        $this->authorize('update', $recurringInvoice);

        // Enforce usage limit when activating/resuming a recurring invoice
        // Check if status is changing to ACTIVE from a non-ACTIVE status
        if ($request->status === RecurringInvoice::ACTIVE && $recurringInvoice->status !== RecurringInvoice::ACTIVE) {
            $companyId = $request->header('company');
            $company = \App\Models\Company::find($companyId);

            if ($company) {
                $tier = $company->subscription_tier ?? 'free';
                $limit = config("subscriptions.tiers.{$tier}.limits.recurring_invoices_active");

                if ($limit !== null) {
                    $activeCount = RecurringInvoice::where('company_id', $company->id)
                        ->where('status', RecurringInvoice::ACTIVE)
                        ->count();

                    if ($activeCount >= $limit) {
                        return response()->json([
                            'error' => 'limit_exceeded',
                            'message' => "You've reached your active recurring invoice limit ({$limit}). Upgrade or pause existing ones.",
                            'usage' => ['active' => $activeCount, 'limit' => $limit],
                        ], 403);
                    }
                }
            }
        }

        $recurringInvoice->updateFromRequest($request);

        $recurringInvoice->load([
            'customer.currency',
            'company',
            'currency',
            'items.taxes',
            'taxes',
            'invoices.customer.currency',
            'fields',
            'creator',
        ]);

        return new RecurringInvoiceResource($recurringInvoice);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\RecurringInvoice  $recurringInvoice
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $this->authorize('delete multiple recurring invoices');

        RecurringInvoice::deleteRecurringInvoice($request->ids);

        return response()->json([
            'success' => true,
        ]);
    }
}
// CLAUDE-CHECKPOINT
