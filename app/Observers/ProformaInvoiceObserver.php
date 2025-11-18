<?php

namespace App\Observers;

use App\Models\ProformaInvoice;
use App\Services\SerialNumberFormatter;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;

/**
 * Proforma Invoice Observer
 *
 * Handles automatic number generation and status updates for proforma invoices.
 * Note: Proforma invoices are NOT posted to IFRS (they're quotes, not accounting events).
 */
class ProformaInvoiceObserver
{
    /**
     * Handle the ProformaInvoice "created" event.
     *
     * Generate proforma_invoice_number and unique_hash if not already set
     */
    public function created(ProformaInvoice $proformaInvoice): void
    {
        // Generate proforma invoice number if not set
        if (empty($proformaInvoice->proforma_invoice_number)) {
            try {
                $serial = (new SerialNumberFormatter)
                    ->setModel($proformaInvoice)
                    ->setCompany($proformaInvoice->company_id)
                    ->setCustomer($proformaInvoice->customer_id)
                    ->setNextNumbers();

                $proformaInvoice->sequence_number = $serial->nextSequenceNumber;
                $proformaInvoice->customer_sequence_number = $serial->nextCustomerSequenceNumber;
                $proformaInvoice->proforma_invoice_number = 'PRO-'.str_pad($serial->nextSequenceNumber, 6, '0', STR_PAD_LEFT);

                if (empty($proformaInvoice->unique_hash)) {
                    $proformaInvoice->unique_hash = Hashids::connection(ProformaInvoice::class)->encode($proformaInvoice->id);
                }

                $proformaInvoice->saveQuietly();
            } catch (\Exception $e) {
                Log::error('ProformaInvoiceObserver: Failed to generate proforma invoice number', [
                    'proforma_invoice_id' => $proformaInvoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the ProformaInvoice "updated" event.
     *
     * Check if expiry_date has passed and auto-expire if status changed
     */
    public function updated(ProformaInvoice $proformaInvoice): void
    {
        // Auto-expire if expiry date has passed and status is SENT or VIEWED
        if ($proformaInvoice->expiry_date &&
            $proformaInvoice->expiry_date->isPast() &&
            in_array($proformaInvoice->status, [ProformaInvoice::STATUS_SENT, ProformaInvoice::STATUS_VIEWED])) {

            try {
                $proformaInvoice->status = ProformaInvoice::STATUS_EXPIRED;
                $proformaInvoice->saveQuietly();
            } catch (\Exception $e) {
                Log::error('ProformaInvoiceObserver: Failed to auto-expire proforma invoice', [
                    'proforma_invoice_id' => $proformaInvoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the ProformaInvoice "deleting" event.
     *
     * Prevent deletion if already converted to invoice
     */
    public function deleting(ProformaInvoice $proformaInvoice): ?bool
    {
        if ($proformaInvoice->status === ProformaInvoice::STATUS_CONVERTED) {
            throw new \Exception('Cannot delete converted proforma invoice.');
        }

        return true;
    }
}

// CLAUDE-CHECKPOINT
