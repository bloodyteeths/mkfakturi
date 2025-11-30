<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Project Resource
 *
 * Transforms Project model data for API responses.
 * Part of Phase 1.1 - Project Dimension feature for accountants.
 */
class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'customer_id' => $this->customer_id,
            'status' => $this->status,

            // Status flags for UI
            'allows_new_documents' => $this->allowsNewDocuments(),
            'is_editable' => $this->isEditable(),
            'can_be_completed' => $this->canBeCompleted(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'can_be_reopened' => $this->canBeReopened(),
            'budget_amount' => $this->budget_amount,
            'currency_id' => $this->currency_id,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'creator_id' => $this->creator_id,
            'notes' => $this->notes,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Formatted dates
            'formatted_created_at' => $this->formattedCreatedAt,
            'formatted_start_date' => $this->formattedStartDate,
            'formatted_end_date' => $this->formattedEndDate,

            // Calculated totals (only when requested to avoid N+1 queries on list)
            'total_invoiced' => $this->when($request->has('include_totals') || $request->routeIs('*.show'), fn() => $this->totalInvoiced),
            'total_expenses' => $this->when($request->has('include_totals') || $request->routeIs('*.show'), fn() => $this->totalExpenses),
            'total_payments' => $this->when($request->has('include_totals') || $request->routeIs('*.show'), fn() => $this->totalPayments),
            'net_result' => $this->when($request->has('include_totals') || $request->routeIs('*.show'), fn() => $this->netResult),

            // Counts
            'invoice_count' => $this->whenCounted('invoices', $this->invoices_count ?? 0),
            'expense_count' => $this->whenCounted('expenses', $this->expenses_count ?? 0),
            'payment_count' => $this->whenCounted('payments', $this->payments_count ?? 0),

            // Relationships (when loaded)
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                    'email' => $this->customer->email,
                ];
            }),
            'currency' => $this->whenLoaded('currency', function () {
                return [
                    'id' => $this->currency->id,
                    'name' => $this->currency->name,
                    'code' => $this->currency->code,
                    'symbol' => $this->currency->symbol,
                    'precision' => $this->currency->precision,
                    'thousand_separator' => $this->currency->thousand_separator,
                    'decimal_separator' => $this->currency->decimal_separator,
                    'swap_currency_symbol' => $this->currency->swap_currency_symbol,
                ];
            }),
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'name' => $this->company->name,
                ];
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),

            // Summary (when requested)
            'summary' => $this->when($request->has('include_summary'), function () {
                return $this->resource->getSummary();
            }),
        ];
    }
}

// CLAUDE-CHECKPOINT
