<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AccountantConsoleController extends Controller
{
    /**
     * Display the accountant console dashboard
     */
    public function index(): JsonResponse
    {
        // Basic endpoint to verify controller is working
        return response()->json([
            'message' => 'Accountant Console Controller initialized',
            'user' => Auth::user()?->name,
            'timestamp' => now()
        ]);
    }

    /**
     * Get list of companies for the authenticated partner
     */
    public function companies(): JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Find the partner record for this user
        $partner = Partner::where('user_id', $user->id)->first();
        
        if (!$partner) {
            return response()->json([
                'message' => 'User is not registered as a partner',
                'companies' => []
            ]);
        }

        // Get active companies for this partner
        $companies = $partner->activeCompanies()
            ->with(['address'])
            ->get()
            ->map(function ($company) use ($partner) {
                // Calculate effective commission rate: use override if set, otherwise partner's default
                $effectiveRate = $company->pivot->override_commission_rate ?? $partner->commission_rate;

                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'logo' => $company->logo,
                    'is_primary' => $company->pivot->is_primary,
                    'commission_rate' => $effectiveRate,
                    'address' => $company->address ? [
                        'name' => $company->address->name,
                        'address_street_1' => $company->address->address_street_1,
                        'city' => $company->address->city,
                        'state' => $company->address->state,
                        'country' => $company->address->country?->name,
                    ] : null,
                    'permissions' => $company->pivot->permissions ?? [],
                ];
            });

        return response()->json([
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'email' => $partner->email,
            ],
            'companies' => $companies,
            'total_companies' => $companies->count()
        ]);
    }

    /**
     * Switch to a different company context
     */
    public function switchCompany(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id'
        ]);

        $user = Auth::user();
        $companyId = $request->input('company_id');

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Find the partner record for this user
        $partner = Partner::where('user_id', $user->id)->first();
        
        if (!$partner) {
            return response()->json([
                'error' => 'User is not registered as a partner'
            ], 403);
        }

        // Verify that the partner has access to this company
        $company = $partner->activeCompanies()
            ->where('companies.id', $companyId)
            ->first();

        if (!$company) {
            return response()->json([
                'error' => 'Partner does not have access to this company'
            ], 403);
        }

        // Calculate effective commission rate
        $effectiveRate = $company->pivot->override_commission_rate ?? $partner->commission_rate;

        // Store the selected company in session for the partner
        session([
            'partner_selected_company_id' => $companyId,
            'partner_selected_company_slug' => $company->slug,
            'partner_context' => [
                'partner_id' => $partner->id,
                'company_id' => $companyId,
                'permissions' => $company->pivot->permissions ?? [],
                'commission_rate' => $effectiveRate,
                'switched_at' => now()
            ]
        ]);

        return response()->json([
            'message' => 'Successfully switched to company context',
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'slug' => $company->slug,
                'logo' => $company->logo,
            ],
            'context' => [
                'partner_id' => $partner->id,
                'company_id' => $companyId,
                'permissions' => $company->pivot->permissions ?? [],
                'commission_rate' => $effectiveRate,
            ]
        ]);
    }
}

