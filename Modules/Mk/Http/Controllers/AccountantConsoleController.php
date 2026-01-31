<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountantConsoleController extends Controller
{
    /**
     * Display the accountant console dashboard with categorized data
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        \Log::info('AccountantConsoleController::index START', [
            'user_id' => $user->id ?? 'null',
            'user_email' => $user->email ?? 'null',
        ]);

        if (! $user) {
            \Log::error('AccountantConsoleController::index - No authenticated user');

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Super admin can see all companies
        if ($user->role === 'super admin') {
            \Log::info('AccountantConsoleController::index - Super admin access, returning all companies');

            $allCompanies = Company::with('address')
                ->orderBy('name')
                ->get()
                ->map(function ($company) {
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'slug' => $company->slug,
                        'logo' => $company->logo,
                        'is_primary' => false,
                        'commission_rate' => 0,
                        'permissions' => ['full_access'],
                        'address' => $company->address ? [
                            'name' => $company->address->name,
                            'address_street_1' => $company->address->address_street_1,
                            'city' => $company->address->city,
                            'state' => $company->address->state,
                        ] : null,
                    ];
                });

            return response()->json([
                'partner' => [
                    'id' => 0,
                    'name' => 'Super Admin',
                    'email' => $user->email,
                    'commission_rate' => 0,
                    'is_active' => true,
                    'kyc_status' => 'approved',
                ],
                'managed_companies' => $allCompanies,
                'referred_companies' => [],
                'pending_invitations' => [],
                'total_managed' => $allCompanies->count(),
                'total_referred' => 0,
                'total_pending' => 0,
            ]);
        }

        // Find the partner record for this user
        $partner = Partner::where('user_id', $user->id)->first();

        if (! $partner) {
            \Log::warning('AccountantConsoleController::index - No partner found', ['user_id' => $user->id]);

            return response()->json([
                'error' => 'User is not registered as a partner',
                'partner' => null,
                'managed_companies' => [],
                'referred_companies' => [],
                'pending_invitations' => [],
                'total_managed' => 0,
                'total_referred' => 0,
                'total_pending' => 0,
            ], 403);
        }

        \Log::info('AccountantConsoleController::index - Partner found', [
            'partner_id' => $partner->id,
            'partner_email' => $partner->email,
        ]);

        // 1. Get managed companies (active access with accepted invitation)
        \Log::info('AccountantConsoleController::index - Fetching managed companies');
        $managedCompanies = \DB::table('partner_company_links')
            ->join('companies', 'companies.id', '=', 'partner_company_links.company_id')
            ->leftJoin('addresses', function ($join) {
                $join->on('addresses.company_id', '=', 'companies.id')
                    ->where('addresses.type', '=', 'billing');
            })
            ->where('partner_company_links.partner_id', $partner->id)
            ->where('partner_company_links.is_active', true)
            ->where('partner_company_links.invitation_status', 'accepted')
            ->select([
                'companies.id',
                'companies.name',
                'companies.slug',
                'companies.logo',
                'partner_company_links.is_primary',
                'partner_company_links.override_commission_rate',
                'partner_company_links.permissions',
                'addresses.name as address_name',
                'addresses.address_street_1',
                'addresses.city',
                'addresses.state',
            ])
            ->get()
            ->map(function ($company) use ($partner) {
                $effectiveRate = $company->override_commission_rate ?? $partner->commission_rate;
                $permissions = $company->permissions ? json_decode($company->permissions, true) : [];

                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'logo' => $company->logo,
                    'is_primary' => (bool) $company->is_primary,
                    'commission_rate' => (float) $effectiveRate,
                    'permissions' => $permissions,
                    'address' => $company->address_name ? [
                        'name' => $company->address_name,
                        'address_street_1' => $company->address_street_1,
                        'city' => $company->city,
                        'state' => $company->state,
                    ] : null,
                ];
            });

        \Log::info('AccountantConsoleController::index - Managed companies fetched', [
            'count' => $managedCompanies->count(),
        ]);

        // 2. Get referred companies (from affiliate_events)
        \Log::info('AccountantConsoleController::index - Fetching referred companies');
        $referredCompanies = \DB::table('affiliate_events')
            ->join('companies', 'companies.id', '=', 'affiliate_events.company_id')
            ->where('affiliate_events.affiliate_partner_id', $partner->id)
            ->where('affiliate_events.is_clawed_back', false)
            ->select([
                'companies.id',
                'companies.name',
                'companies.slug',
                'companies.logo',
                'companies.created_at as signup_date',
                \DB::raw('SUM(affiliate_events.amount) as total_commissions'),
                \DB::raw('MIN(affiliate_events.created_at) as first_commission_date'),
            ])
            ->groupBy([
                'companies.id',
                'companies.name',
                'companies.slug',
                'companies.logo',
                'companies.created_at',
            ])
            ->orderBy('total_commissions', 'desc')
            ->get()
            ->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'slug' => $company->slug,
                    'logo' => $company->logo,
                    'total_commissions' => (float) $company->total_commissions,
                    'signup_date' => $company->signup_date,
                    'first_commission_date' => $company->first_commission_date,
                    'subscription_status' => 'active', // This could be enhanced with actual subscription data
                ];
            });

        \Log::info('AccountantConsoleController::index - Referred companies fetched', [
            'count' => $referredCompanies->count(),
        ]);

        // 3. Get pending invitations
        \Log::info('AccountantConsoleController::index - Fetching pending invitations');
        $pendingInvitations = \DB::table('partner_company_links')
            ->join('companies', 'companies.id', '=', 'partner_company_links.company_id')
            ->leftJoin('users', 'users.id', '=', 'partner_company_links.created_by')
            ->where('partner_company_links.partner_id', $partner->id)
            ->where('partner_company_links.invitation_status', 'pending')
            ->select([
                'partner_company_links.id',
                'companies.id as company_id',
                'companies.name as company_name',
                'companies.slug',
                'companies.logo',
                'partner_company_links.permissions',
                'partner_company_links.invited_at',
                'partner_company_links.override_commission_rate',
                'users.name as invited_by',
            ])
            ->get()
            ->map(function ($invitation) {
                $permissions = $invitation->permissions ? json_decode($invitation->permissions, true) : [];

                // Calculate expiration (30 days from invited_at)
                $invitedAt = \Carbon\Carbon::parse($invitation->invited_at);
                $expiresAt = $invitedAt->copy()->addDays(30);

                return [
                    'id' => $invitation->id,
                    'company_id' => $invitation->company_id,
                    'company_name' => $invitation->company_name,
                    'slug' => $invitation->slug,
                    'logo' => $invitation->logo,
                    'permissions' => $permissions,
                    'invited_at' => $invitation->invited_at,
                    'expires_at' => $expiresAt->toIso8601String(),
                    'inviter_name' => $invitation->invited_by, // Frontend expects 'inviter_name'
                    'override_commission_rate' => $invitation->override_commission_rate,
                ];
            });

        \Log::info('AccountantConsoleController::index - Pending invitations fetched', [
            'count' => $pendingInvitations->count(),
            'data' => $pendingInvitations->toArray(),
        ]);

        \Log::info('AccountantConsoleController::index response', [
            'partner_id' => $partner->id,
            'managed_count' => $managedCompanies->count(),
            'referred_count' => $referredCompanies->count(),
            'pending_count' => $pendingInvitations->count(),
        ]);

        return response()->json([
            'partner' => [
                'id' => $partner->id,
                'name' => $partner->name,
                'email' => $partner->email,
                'commission_rate' => (float) $partner->commission_rate,
                'is_active' => $partner->is_active,
                'kyc_status' => $partner->kyc_status,
            ],
            'managed_companies' => $managedCompanies,
            'referred_companies' => $referredCompanies,
            'pending_invitations' => $pendingInvitations,
            'total_managed' => $managedCompanies->count(),
            'total_referred' => $referredCompanies->count(),
            'total_pending' => $pendingInvitations->count(),
        ]);
    }

    /**
     * Get commission overview for authenticated partner (AC-10)
     */
    public function commissions(Request $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Super admin gets empty commission data
        if ($user->role === 'super admin') {
            return response()->json([
                'kpis' => [
                    'total_earnings' => 0,
                    'this_month' => 0,
                    'pending_payout' => 0,
                ],
                'monthly_trend' => [],
                'per_company' => [],
                'is_super_admin' => true,
            ]);
        }

        $partner = Partner::where('user_id', $user->id)->first();

        if (! $partner) {
            return response()->json(['error' => 'Not registered as partner'], 403);
        }

        // Get date range from request or default to last 12 months
        $startDate = $request->input('start_date', now()->subMonths(12));
        $endDate = $request->input('end_date', now());

        // KPI calculations
        $totalEarnings = $partner->getLifetimeEarnings();
        $pendingPayout = $partner->getUnpaidCommissionsTotal();

        // This month earnings
        $thisMonthEarnings = \DB::table('affiliate_events')
            ->where('affiliate_partner_id', $partner->id)
            ->where('is_clawed_back', false)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        // Monthly trend
        $monthlyTrend = \DB::table('affiliate_events')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total')
            ->where('affiliate_partner_id', $partner->id)
            ->where('is_clawed_back', false)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->get();

        // Per-company breakdown
        $perCompany = \DB::table('affiliate_events')
            ->join('companies', 'affiliate_events.company_id', '=', 'companies.id')
            ->selectRaw('companies.id, companies.name, SUM(affiliate_events.amount) as total')
            ->where('affiliate_events.affiliate_partner_id', $partner->id)
            ->where('affiliate_events.is_clawed_back', false)
            ->groupBy('companies.id', 'companies.name')
            ->orderBy('total', 'desc')
            ->get();

        return response()->json([
            'kpis' => [
                'total_earnings' => $totalEarnings,
                'this_month' => $thisMonthEarnings,
                'pending_payout' => $pendingPayout,
            ],
            'monthly_trend' => $monthlyTrend,
            'per_company' => $perCompany,
        ]);
    }

    /**
     * Get list of companies for the authenticated partner
     */
    public function companies(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Find the partner record for this user
        $partner = Partner::where('user_id', $user->id)->first();

        if (! $partner) {
            return response()->json([
                'message' => 'User is not registered as a partner',
                'companies' => [],
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
            'total_companies' => $companies->count(),
        ]);
    }

    /**
     * Switch to a different company context
     */
    public function switchCompany(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|integer|exists:companies,id',
        ]);

        $user = Auth::user();
        $companyId = $request->input('company_id');

        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Super admin can switch to any company
        if ($user->role === 'super admin') {
            $company = Company::find($companyId);

            if (! $company) {
                return response()->json([
                    'error' => 'Company not found',
                ], 404);
            }

            // Store the selected company in session for super admin
            session([
                'partner_selected_company_id' => $companyId,
                'partner_selected_company_slug' => $company->slug,
                'partner_context' => [
                    'partner_id' => 0,
                    'company_id' => $companyId,
                    'permissions' => ['full_access'],
                    'commission_rate' => 0,
                    'switched_at' => now(),
                ],
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
                    'partner_id' => 0,
                    'company_id' => $companyId,
                    'permissions' => ['full_access'],
                    'commission_rate' => 0,
                ],
            ]);
        }

        // Find the partner record for this user
        $partner = Partner::where('user_id', $user->id)->first();

        if (! $partner) {
            return response()->json([
                'error' => 'User is not registered as a partner',
            ], 403);
        }

        // Verify that the partner has access to this company
        $company = $partner->activeCompanies()
            ->where('companies.id', $companyId)
            ->first();

        if (! $company) {
            return response()->json([
                'error' => 'Partner does not have access to this company',
            ], 403);
        }

        // Calculate effective commission rate
        $effectiveRate = $company->pivot->override_commission_rate ?? $partner->commission_rate;

        // Ensure partner has admin role in the company scope
        // Partners need full access to manage the companies they're assigned to
        \Bouncer::scope()->to($companyId);

        // Check if user already has admin role, if not assign it
        if (! $user->isAn('admin')) {
            \Bouncer::assign('admin')->to($user);
        }

        // Store the selected company in session for the partner
        session([
            'partner_selected_company_id' => $companyId,
            'partner_selected_company_slug' => $company->slug,
            'partner_context' => [
                'partner_id' => $partner->id,
                'company_id' => $companyId,
                'permissions' => $company->pivot->permissions ?? [],
                'commission_rate' => $effectiveRate,
                'switched_at' => now(),
            ],
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
            ],
        ]);
    }
}

// CLAUDE-CHECKPOINT
