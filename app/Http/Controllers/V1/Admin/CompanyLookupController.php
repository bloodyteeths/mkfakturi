<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\CentralRegistryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Company lookup controller.
 *
 * Provides an endpoint for searching the Macedonian Central Registry (crm.com.mk)
 * to auto-fill customer/vendor forms with company data.
 */
class CompanyLookupController extends Controller
{
    public function __construct(
        protected CentralRegistryService $registryService
    ) {}

    /**
     * Search the Central Registry for a company.
     *
     * GET /api/v1/company-lookup?q={query}
     *
     * @return JsonResponse
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $query = $request->input('q');
        $response = $this->registryService->search($query);

        return response()->json([
            'data' => $response['results'],
            'meta' => [
                'query' => $query,
                'count' => count($response['results']),
                'source' => 'crm.com.mk',
                'captcha_required' => $response['captcha_required'],
                'lookup_url' => $response['lookup_url'],
            ],
        ]);
    }
}
// CLAUDE-CHECKPOINT
