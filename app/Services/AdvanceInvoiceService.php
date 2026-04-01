<?php

namespace App\Services;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Invoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing advance invoices (Аванс Фактура).
 *
 * Legal basis: Член 14 + Член 53 ЗДДВ
 * - VAT is due when advance payment is received
 * - Advance invoice must be issued within 5 working days
 * - Final invoice must deduct all prior advances and their VAT
 */
class AdvanceInvoiceService
{
    /**
     * Get unsettled advance invoices for a customer.
     * These are advances that have NOT been linked to a final invoice yet.
     */
    public function getUnsettledAdvances(int $customerId, int $companyId): Collection
    {
        return Invoice::where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->where('type', Invoice::TYPE_ADVANCE)
            ->whereNull('parent_invoice_id')
            ->orderBy('invoice_date', 'asc')
            ->get();
    }

    /**
     * Settle advance invoices against a final invoice.
     * Links the advances to the final invoice and adjusts due_amount.
     */
    public function settleAdvances(Invoice $finalInvoice, array $advanceInvoiceIds): Invoice
    {
        if (empty($advanceInvoiceIds)) {
            return $finalInvoice;
        }

        $advances = Invoice::where('company_id', $finalInvoice->company_id)
            ->where('customer_id', $finalInvoice->customer_id)
            ->where('type', Invoice::TYPE_ADVANCE)
            ->whereNull('parent_invoice_id')
            ->whereIn('id', $advanceInvoiceIds)
            ->get();

        if ($advances->isEmpty()) {
            return $finalInvoice;
        }

        // Per Чл. 14 + Чл. 53 ЗДДВ: advance invoices charge sub_total only
        // (DDV is shown but not included in the payable amount),
        // so we deduct sub_total from the final invoice.
        $totalAdvanceSubTotal = $advances->sum('sub_total');

        if ($totalAdvanceSubTotal > $finalInvoice->total) {
            throw new \InvalidArgumentException(
                'Total advance amount exceeds final invoice total.'
            );
        }

        DB::transaction(function () use ($finalInvoice, $advances, $totalAdvanceSubTotal) {
            // Link advances to this final invoice
            Invoice::whereIn('id', $advances->pluck('id'))
                ->update(['parent_invoice_id' => $finalInvoice->id]);

            // Mark invoice as final type — deduct advance sub_totals (DDV excluded)
            $finalInvoice->update([
                'type' => Invoice::TYPE_FINAL,
                'due_amount' => $finalInvoice->total - $totalAdvanceSubTotal,
            ]);

            // Post GL settlement entries if IFRS is enabled
            try {
                $ifrsAdapter = app(IfrsAdapter::class);
                $ifrsAdapter->postAdvanceSettlement($finalInvoice, $advances);
            } catch (\Exception $e) {
                Log::error('AdvanceInvoiceService: Failed to post GL settlement', [
                    'final_invoice_id' => $finalInvoice->id,
                    'advance_ids' => $advances->pluck('id'),
                    'error' => $e->getMessage(),
                ]);
            }
        });

        return $finalInvoice->fresh();
    }

    /**
     * Preview settlement calculation without persisting.
     */
    public function previewSettlement(Invoice $finalInvoice, array $advanceInvoiceIds): array
    {
        $advances = Invoice::where('company_id', $finalInvoice->company_id)
            ->where('customer_id', $finalInvoice->customer_id)
            ->where('type', Invoice::TYPE_ADVANCE)
            ->whereNull('parent_invoice_id')
            ->whereIn('id', $advanceInvoiceIds)
            ->get();

        $totalAdvanceTax = $advances->sum('tax');
        $totalAdvanceSubTotal = $advances->sum('sub_total');

        return [
            'advances' => $advances->map(fn ($adv) => [
                'id' => $adv->id,
                'invoice_number' => $adv->invoice_number,
                'invoice_date' => $adv->invoice_date,
                'total' => $adv->total,
                'sub_total' => $adv->sub_total,
                'tax' => $adv->tax,
            ]),
            'total_advance_amount' => $totalAdvanceSubTotal,
            'total_advance_tax' => $totalAdvanceTax,
            'total_advance_sub_total' => $totalAdvanceSubTotal,
            'final_invoice_total' => $finalInvoice->total,
            'remaining_due' => $finalInvoice->total - $totalAdvanceSubTotal,
        ];
    }
}

// CLAUDE-CHECKPOINT
