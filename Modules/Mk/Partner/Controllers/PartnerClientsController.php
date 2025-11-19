<?php

namespace Modules\Mk\Partner\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PartnerClientsController extends Controller
{
    /**
     * Get paginated list of referred companies with filters
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        \Log::info('PartnerClientsController::index called', [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => Auth::id(),
        ]);

        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (!$partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        \Log::info('Partner found for clients', [
            'partner_id' => $partner->id,
            'user_id' => $user->id,
        ]);

        // Build query
        $query = $partner->companies()
            ->with(['subscription'])
            ->select('companies.*', 'partner_company_links.override_commission_rate');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('companies.name', 'like', "%{$search}%")
                    ->orWhere('companies.unique_hash', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->filled('status')) {
            $status = $request->status;
            $query->whereHas('subscription', function ($q) use ($status) {
                if ($status === 'active') {
                    $q->where('status', 'active');
                } elseif ($status === 'trial') {
                    $q->where('status', 'trialing');
                } elseif ($status === 'canceled') {
                    $q->where('status', 'canceled');
                } elseif ($status === 'suspended') {
                    $q->where('status', 'suspended');
                }
            });
        }

        // Apply plan filter
        if ($request->filled('plan')) {
            $plan = $request->plan;
            $query->whereHas('subscription', function ($q) use ($plan) {
                $q->where('plan_tier', $plan);
            });
        }

        // Get paginated results
        $perPage = $request->input('per_page', 20);
        $companies = $query->paginate($perPage);

        // Transform results
        $data = $companies->getCollection()->map(function ($company) use ($partner) {
            $subscription = $company->subscription;
            $planTier = $subscription->plan_tier ?? 'free';
            $mrr = $subscription->price ?? 0;

            // Calculate commission for this client
            $commissionRate = $company->pivot->override_commission_rate ?? $partner->commission_rate;
            $commission = $mrr * ($commissionRate / 100);

            return [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->owner ? $company->owner->email : null,
                'logo' => $company->logo_path ? asset('storage/' . $company->logo_path) : null,
                'plan' => $planTier,
                'mrr' => $mrr,
                'status' => $subscription->status ?? 'inactive',
                'signup_date' => $company->created_at->toIso8601String(),
                'commission' => $commission,
            ];
        });

        // Calculate summary
        $allCompanies = $partner->companies()->get();
        $totalClients = $allCompanies->count();
        $activeClients = $allCompanies->filter(function ($company) {
            return $company->subscription && $company->subscription->status === 'active';
        })->count();

        $totalMRR = $allCompanies->reduce(function ($carry, $company) {
            $subscription = $company->subscription;

            return $carry + ($subscription->price ?? 0);
        }, 0);

        $monthlyCommission = $allCompanies->reduce(function ($carry, $company) use ($partner) {
            $subscription = $company->subscription;
            if (!$subscription || $subscription->status !== 'active') {
                return $carry;
            }

            $mrr = $subscription->price ?? 0;
            $commissionRate = $company->pivot->override_commission_rate ?? $partner->commission_rate;

            return $carry + ($mrr * ($commissionRate / 100));
        }, 0);

        return response()->json([
            'data' => $data,
            'current_page' => $companies->currentPage(),
            'per_page' => $companies->perPage(),
            'total' => $companies->total(),
            'last_page' => $companies->lastPage(),
            'from' => $companies->firstItem(),
            'to' => $companies->lastItem(),
            'summary' => [
                'totalClients' => $totalClients,
                'activeClients' => $activeClients,
                'totalMRR' => $totalMRR,
                'monthlyCommission' => $monthlyCommission,
            ],
        ]);
    }
}

// CLAUDE-CHECKPOINT
