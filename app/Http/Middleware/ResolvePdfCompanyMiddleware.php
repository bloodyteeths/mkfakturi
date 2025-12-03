<?php

namespace App\Http\Middleware;

use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\ProformaInvoice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolvePdfCompanyMiddleware
{
    /**
     * Attempt to detect the company context for PDF routes before the company middleware runs.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->headers->has('company')) {
            return $next($request);
        }

        $map = [
            'invoice' => Invoice::class,
            'estimate' => Estimate::class,
            'payment' => Payment::class,
            'proformaInvoice' => ProformaInvoice::class,
        ];

        foreach ($map as $parameter => $modelClass) {
            $value = $request->route($parameter);

            if ($value instanceof $modelClass) {
                // Route model binding already resolved - just get company_id
                $companyId = $value->company_id;
            } elseif ($value) {
                // Value is the unique_hash string, query by it
                $companyId = $modelClass::where('unique_hash', $value)->value('company_id');
            } else {
                $companyId = null;
            }

            if ($companyId) {
                $request->headers->set('company', $companyId);
                break;
            }
        }

        return $next($request);
    }
}
