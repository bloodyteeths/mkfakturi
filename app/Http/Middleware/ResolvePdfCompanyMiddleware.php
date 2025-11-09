<?php

namespace App\Http\Middleware;

use App\Models\Estimate;
use App\Models\Invoice;
use App\Models\Payment;
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
        \Log::info('ResolvePdfCompanyMiddleware entry', [
            'path' => $request->path(),
            'has_company_header' => $request->headers->has('company'),
        ]);

        if ($request->headers->has('company')) {
            \Log::info('Company header already set, skipping');
            return $next($request);
        }

        $map = [
            'invoice' => Invoice::class,
            'estimate' => Estimate::class,
            'payment' => Payment::class,
        ];

        foreach ($map as $parameter => $modelClass) {
            $value = $request->route($parameter);

            \Log::info('Checking route parameter', [
                'parameter' => $parameter,
                'value' => $value,
                'value_type' => gettype($value),
                'is_model' => $value instanceof $modelClass,
            ]);

            if ($value instanceof $modelClass) {
                // Route model binding already resolved - just get company_id
                $companyId = $value->company_id;
                \Log::info('Model already bound', ['company_id' => $companyId]);
            } elseif ($value) {
                // Value is the unique_hash string, query by it
                \Log::info('Querying by unique_hash', ['hash' => $value, 'model' => $modelClass]);
                $companyId = $modelClass::where('unique_hash', $value)->value('company_id');
                \Log::info('Query result', ['company_id' => $companyId]);
            } else {
                $companyId = null;
            }

            if ($companyId) {
                $request->headers->set('company', $companyId);
                \Log::info('Company header set', ['company_id' => $companyId]);
                break;
            }
        }

        return $next($request);
    }
}
