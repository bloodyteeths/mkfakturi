<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Mk\Services\CpayDriver;

/**
 * CPAY Payment Callback Controller
 *
 * Handles payment callbacks from CASYS CPAY payment gateway.
 * This controller receives POST requests from CPAY after payment completion,
 * processes the payment, and redirects the user to the invoice view.
 *
 * @version 1.0.0
 * @author Claude Code - CPAY Integration Agent
 */
class CpayCallbackController extends Controller
{
    /**
     * Handle CPAY payment callback
     *
     * This method is called by CPAY after a payment is completed.
     * It validates the callback, processes the payment, and redirects the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request)
    {
        try {
            $cpayDriver = app(CpayDriver::class);

            // Process the callback (this will create payment record and update invoice)
            $cpayDriver->handleCallback($request);

            // Get the order_id (invoice_number) from the callback
            $invoiceNumber = $request->input('order_id');

            // Find the invoice to redirect to
            $invoice = \App\Models\Invoice::where('invoice_number', $invoiceNumber)->first();

            if ($invoice) {
                // Redirect to invoice view with success message
                return redirect("/admin/invoices/{$invoice->id}/view")
                    ->with('success', __('payments.cpay.success'));
            }

            // If invoice not found, redirect to invoices list
            return redirect('/admin/invoices')
                ->with('success', __('payments.cpay.success'));

        } catch (\Exception $e) {
            \Log::error('CPAY callback processing failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all(),
            ]);

            // Get the order_id to try redirecting to the invoice
            $invoiceNumber = $request->input('order_id');
            $invoice = \App\Models\Invoice::where('invoice_number', $invoiceNumber)->first();

            if ($invoice) {
                return redirect("/admin/invoices/{$invoice->id}/view")
                    ->with('error', __('payments.cpay.failed'));
            }

            return redirect('/admin/invoices')
                ->with('error', __('payments.cpay.failed'));
        }
    }
}
// CLAUDE-CHECKPOINT
