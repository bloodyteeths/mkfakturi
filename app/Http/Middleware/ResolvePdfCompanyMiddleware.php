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
        if ($request->headers->has('company')) {
            return $next($request);
        }

        $map = [
            'invoice' => Invoice::class,
            'estimate' => Estimate::class,
            'payment' => Payment::class,
        ];

        foreach ($map as $parameter => $modelClass) {
            $value = $request->route($parameter);

            if ($value instanceof $modelClass) {
                $companyId = $value->company_id;
            } elseif ($value) {
                // Decode Hashid to get the actual ID, then query by ID
                $ids = \Vinkla\Hashids\Facades\Hashids::connection($modelClass)->decode($value);

                if (!empty($ids)) {
                    $companyId = $modelClass::where('id', $ids[0])->value('company_id');
                } else {
                    $companyId = null;
                }
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
