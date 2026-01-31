<?php

namespace Modules\Mk\Partner\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PartnerClientsController extends Controller
{
    /**
     * Get partner from authenticated request.
     * For super admin, returns a "fake" partner object to allow access.
     *
     * @return Partner|null
     */
    protected function getPartnerFromRequest(): ?Partner
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        // Super admin gets a fake partner to pass validation
        if ($user->role === 'super admin') {
            $fakePartner = new Partner();
            $fakePartner->id = 0;
            $fakePartner->user_id = $user->id;
            $fakePartner->name = 'Super Admin';
            $fakePartner->email = $user->email;
            $fakePartner->is_super_admin = true;
            $fakePartner->commission_rate = 0;

            return $fakePartner;
        }

        return Partner::where('user_id', $user->id)->first();
    }

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

        $partner = $this->getPartnerFromRequest();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        // Super admin sees all companies
        if ($partner->is_super_admin ?? false) {
            $query = \App\Models\Company::with(['subscription'])->select('companies.*');
        } else {
            // Build query for partner's companies
            $query = $partner->companies()
                ->with(['subscription'])
                ->select('companies.*', 'partner_company_links.override_commission_rate');
        }

        \Log::info('Partner found for clients', [
            'partner_id' => $partner->id,
            'user_id' => Auth::id(),
            'is_super_admin' => $partner->is_super_admin ?? false,
        ]);

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

        $isSuperAdmin = $partner->is_super_admin ?? false;

        // Transform results
        $data = $companies->getCollection()->map(function ($company) use ($partner, $isSuperAdmin) {
            $subscription = $company->subscription;
            $planTier = $subscription->plan_tier ?? 'free';
            $mrr = $subscription->price ?? 0;

            // Calculate commission for this client (super admin has 0 commission)
            $commissionRate = $isSuperAdmin ? 0 : ($company->pivot->override_commission_rate ?? $partner->commission_rate);
            $commission = $mrr * ($commissionRate / 100);

            return [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->owner ? $company->owner->email : null,
                'logo' => $company->logo_path ? asset('storage/'.$company->logo_path) : null,
                'plan' => $planTier,
                'mrr' => $mrr,
                'status' => $subscription->status ?? 'inactive',
                'signup_date' => $company->created_at->toIso8601String(),
                'commission' => $commission,
            ];
        });

        // Calculate summary
        if ($isSuperAdmin) {
            $allCompanies = \App\Models\Company::with('subscription')->get();
        } else {
            $allCompanies = $partner->companies()->get();
        }

        $totalClients = $allCompanies->count();
        $activeClients = $allCompanies->filter(function ($company) {
            return $company->subscription && $company->subscription->status === 'active';
        })->count();

        $totalMRR = $allCompanies->reduce(function ($carry, $company) {
            $subscription = $company->subscription;

            return $carry + ($subscription->price ?? 0);
        }, 0);

        $monthlyCommission = $isSuperAdmin ? 0 : $allCompanies->reduce(function ($carry, $company) use ($partner) {
            $subscription = $company->subscription;
            if (! $subscription || $subscription->status !== 'active') {
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

    /**
     * Get detailed information for a single client company
     *
     * @param  int  $companyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $companyId)
    {
        $partner = $this->getPartnerFromRequest();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        $isSuperAdmin = $partner->is_super_admin ?? false;

        // Super admin can access any company
        if ($isSuperAdmin) {
            $company = \App\Models\Company::with(['subscription', 'owner', 'address'])
                ->where('id', $companyId)
                ->first();
        } else {
            // Verify this company belongs to the partner
            $company = $partner->companies()
                ->with(['subscription', 'owner', 'address'])
                ->where('companies.id', $companyId)
                ->first();
        }

        if (! $company) {
            return response()->json(['error' => 'Client not found or not accessible'], 404);
        }

        $subscription = $company->subscription;
        $commissionRate = $isSuperAdmin ? 0 : ($company->pivot->override_commission_rate ?? $partner->commission_rate);
        $mrr = $subscription->price ?? 0;
        $commission = $mrr * ($commissionRate / 100);

        // Get billing history if available
        $billingHistory = [];
        if ($subscription) {
            // Get recent invoices for this subscription
            $billingHistory = \App\Models\Invoice::where('company_id', $company->id)
                ->where('invoice_type', 'subscription')
                ->orderBy('created_at', 'desc')
                ->limit(6)
                ->get()
                ->map(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'date' => $invoice->invoice_date,
                        'amount' => $invoice->total,
                        'status' => $invoice->status,
                    ];
                });
        }

        return response()->json([
            'data' => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->owner ? $company->owner->email : null,
                'phone' => $company->phone ?? null,
                'logo' => $company->logo_path ? asset('storage/'.$company->logo_path) : null,
                'address' => $company->address ? [
                    'street' => $company->address->address_street_1,
                    'city' => $company->address->city,
                    'zip' => $company->address->zip,
                    'country' => $company->address->country_id,
                ] : null,
                'subscription' => $subscription ? [
                    'plan' => $subscription->plan_tier ?? 'free',
                    'status' => $subscription->status,
                    'billing_period' => $subscription->billing_period ?? 'monthly',
                    'price' => $subscription->price ?? 0,
                    'trial_ends_at' => $subscription->trial_ends_at,
                    'current_period_start' => $subscription->current_period_start,
                    'current_period_end' => $subscription->current_period_end,
                    'canceled_at' => $subscription->canceled_at,
                ] : null,
                'commission' => [
                    'rate' => $commissionRate,
                    'monthly_amount' => $commission,
                    'is_override' => $isSuperAdmin ? false : ($company->pivot->override_commission_rate !== null),
                ],
                'signup_date' => $company->created_at->toIso8601String(),
                'billing_history' => $billingHistory,
            ],
        ]);
    }
}

// CLAUDE-CHECKPOINT
