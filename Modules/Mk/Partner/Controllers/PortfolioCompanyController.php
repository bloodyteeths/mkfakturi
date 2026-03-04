<?php

namespace Modules\Mk\Partner\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Mk\Partner\Services\PortfolioTierService;

class PortfolioCompanyController extends Controller
{
    /**
     * List all portfolio-managed companies for the partner.
     */
    public function index(Request $request): JsonResponse
    {
        $partner = $this->getPartner();

        if (! $partner || ! $partner->portfolio_enabled) {
            return response()->json(['error' => 'Portfolio not activated'], 403);
        }

        $query = $partner->portfolioCompanies()->with('subscription');

        // Search filter
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('tax_id', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($status = $request->query('status')) {
            switch ($status) {
                case 'paying':
                    $query->whereHas('subscription', function ($q) {
                        $q->whereIn('status', ['trial', 'active'])->where('plan', '!=', 'free');
                    });
                    break;
                case 'non_paying':
                    $query->where(function ($q) {
                        $q->whereDoesntHave('subscription')
                            ->orWhereHas('subscription', function ($sq) {
                                $sq->where('plan', 'free')
                                    ->orWhereNotIn('status', ['trial', 'active']);
                            });
                    });
                    break;
            }
        }

        $companies = $query->orderBy('name')->paginate($request->query('per_page', 25));

        // Add portfolio tier info to each company
        $companies->getCollection()->transform(function ($company) {
            $tierOverride = $company->pivot->portfolio_tier_override ?? null;
            $isPaying = $company->subscription
                && in_array($company->subscription->status, ['trial', 'active'])
                && $company->subscription->plan !== 'free';

            $company->portfolio_status = $isPaying ? 'paying' : ($tierOverride === 'standard' ? 'covered' : 'uncovered');
            $company->portfolio_tier = $isPaying ? ($company->subscription->plan ?? 'free') : ($tierOverride ?? 'accountant_basic');

            return $company;
        });

        return response()->json([
            'data' => $companies->items(),
            'meta' => [
                'current_page' => $companies->currentPage(),
                'last_page' => $companies->lastPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
            ],
            'stats' => $partner->getPortfolioStats(),
        ]);
    }

    /**
     * Create a new company in the partner's portfolio.
     */
    public function store(Request $request): JsonResponse
    {
        $partner = $this->getPartner();

        if (! $partner || ! $partner->portfolio_enabled) {
            return response()->json(['error' => 'Portfolio not activated'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'tax_id' => 'required|string|max:50',
            'vat_number' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:3',
            'language' => 'nullable|string|max:5',
        ]);

        DB::beginTransaction();

        try {
            // Generate unique slug
            $slug = Str::slug($validated['name']);
            $originalSlug = $slug;
            $counter = 1;
            while (Company::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            // Create company
            $company = Company::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'tax_id' => $validated['tax_id'],
                'vat_number' => $validated['vat_number'] ?? null,
                'is_portfolio_managed' => true,
                'managing_partner_id' => $partner->id,
            ]);

            // Setup default data (roles, payment methods, units, settings)
            $company->setupDefaultData();

            // Apply currency/language overrides if provided
            if (! empty($validated['currency'])) {
                $company->settings()->updateOrCreate(
                    ['option' => 'currency'],
                    ['value' => $validated['currency']]
                );
            }
            if (! empty($validated['language'])) {
                $company->settings()->updateOrCreate(
                    ['option' => 'language'],
                    ['value' => $validated['language']]
                );
            }

            // Create partner_company_link
            $partner->companies()->attach($company->id, [
                'is_active' => true,
                'is_portfolio_managed' => true,
                'permissions' => json_encode([\App\Enums\PartnerPermission::FULL_ACCESS->value]),
                'invitation_status' => 'accepted',
                'accepted_at' => now(),
            ]);

            // Add partner's user to user_company (so partner can switch to this company)
            if ($partner->user) {
                $company->users()->syncWithoutDetaching([$partner->user_id]);
            }

            // Create trial subscription
            $trialDays = config('subscriptions.portfolio.company_trial_days', 14);
            $trialPlan = config('subscriptions.portfolio.company_trial_plan', 'standard');

            CompanySubscription::create([
                'company_id' => $company->id,
                'plan' => $trialPlan,
                'status' => 'trial',
                'trial_ends_at' => now()->addDays($trialDays),
                'started_at' => now(),
            ]);

            // Recalculate portfolio tiers
            $tierService = app(PortfolioTierService::class);
            $tierService->recalculate($partner->fresh());

            DB::commit();

            return response()->json([
                'message' => 'Company created successfully',
                'company' => $company->fresh()->load('subscription'),
                'stats' => $partner->fresh()->getPortfolioStats(),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Portfolio company creation failed', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to create company'], 500);
        }
    }

    /**
     * Get details of a specific portfolio company.
     */
    public function show(int $companyId): JsonResponse
    {
        $partner = $this->getPartner();

        if (! $partner || ! $partner->portfolio_enabled) {
            return response()->json(['error' => 'Portfolio not activated'], 403);
        }

        $company = $partner->portfolioCompanies()
            ->where('companies.id', $companyId)
            ->with('subscription')
            ->first();

        if (! $company) {
            return response()->json(['error' => 'Company not found in portfolio'], 404);
        }

        return response()->json(['company' => $company]);
    }

    /**
     * Remove a company from the portfolio.
     */
    public function destroy(int $companyId): JsonResponse
    {
        $partner = $this->getPartner();

        if (! $partner || ! $partner->portfolio_enabled) {
            return response()->json(['error' => 'Portfolio not activated'], 403);
        }

        $exists = DB::table('partner_company_links')
            ->where('partner_id', $partner->id)
            ->where('company_id', $companyId)
            ->where('is_portfolio_managed', true)
            ->exists();

        if (! $exists) {
            return response()->json(['error' => 'Company not found in portfolio'], 404);
        }

        DB::beginTransaction();

        try {
            // Remove portfolio flags
            DB::table('partner_company_links')
                ->where('partner_id', $partner->id)
                ->where('company_id', $companyId)
                ->update([
                    'is_portfolio_managed' => false,
                    'portfolio_tier_override' => null,
                ]);

            Company::where('id', $companyId)->update([
                'is_portfolio_managed' => false,
                'managing_partner_id' => null,
            ]);

            // Recalculate tiers
            $tierService = app(PortfolioTierService::class);
            $tierService->recalculate($partner->fresh());

            DB::commit();

            return response()->json([
                'message' => 'Company removed from portfolio',
                'stats' => $partner->fresh()->getPortfolioStats(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => 'Failed to remove company'], 500);
        }
    }

    /**
     * Get partner from authenticated user.
     */
    protected function getPartner(): ?Partner
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        if ($user->role === 'super admin') {
            $partnerId = request()->query('partner_id');
            if ($partnerId) {
                return Partner::find($partnerId);
            }

            return null;
        }

        return Partner::where('user_id', $user->id)->first();
    }
}
// CLAUDE-CHECKPOINT
