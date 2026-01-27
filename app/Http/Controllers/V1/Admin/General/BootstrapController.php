<?php

namespace App\Http\Controllers\V1\Admin\General;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\UserResource;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Module;
use App\Models\Setting;
use App\Providers\CacheServiceProvider;
use App\Traits\GeneratesMenuTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Silber\Bouncer\BouncerFacade;

class BootstrapController extends Controller
{
    use GeneratesMenuTrait;

    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request)
    {
        $current_user = $request->user();

        // Partner users: Check if they've switched to a company context
        if ($current_user->role === 'partner') {
            $partnerContext = session('partner_context');
            $partnerCompanyId = $partnerContext['company_id'] ?? null;

            // If partner hasn't switched to a company yet, return minimal bootstrap
            if (! $partnerCompanyId) {
                return response()->json([
                    'current_user' => new UserResource($current_user->load('currency', 'settings')),
                    'current_user_settings' => $current_user->getAllSettings(),
                    'current_user_abilities' => [],
                    'companies' => [],
                    'current_company' => null,
                    'current_company_currency' => null,
                    'current_company_settings' => [],
                    'global_settings' => Setting::getSettings(['admin_portal_logo', 'copyright_text'])->toArray(),
                    'main_menu' => [],
                    'setting_menu' => [],
                    'modules' => [],
                    'is_partner' => true,
                ]);
            }

            // Partner has switched to a company - provide full bootstrap for that company
            // Load the partner's accessible companies
            $partner = \App\Models\Partner::where('user_id', $current_user->id)->first();
            if (! $partner) {
                abort(403, 'Partner record not found');
            }

            $partnerCompanies = $partner->activeCompanies()
                ->with(['address', 'subscription'])
                ->get();

            $current_company = $partnerCompanies->firstWhere('id', $partnerCompanyId);

            if (! $current_company) {
                // Partner lost access or company doesn't exist
                session()->forget(['partner_context', 'partner_selected_company_id', 'partner_selected_company_slug']);

                return response()->json([
                    'current_user' => new UserResource($current_user->load('currency', 'settings')),
                    'current_user_settings' => $current_user->getAllSettings(),
                    'current_user_abilities' => [],
                    'companies' => [],
                    'current_company' => null,
                    'current_company_currency' => null,
                    'current_company_settings' => [],
                    'global_settings' => Setting::getSettings(['admin_portal_logo', 'copyright_text'])->toArray(),
                    'main_menu' => [],
                    'setting_menu' => [],
                    'modules' => [],
                    'is_partner' => true,
                    'error' => 'Company access revoked or not found',
                ]);
            }

            // Generate full bootstrap data for the partner in this company context
            BouncerFacade::scope()->to($partnerCompanyId);
            BouncerFacade::refreshFor($current_user);

            $current_user_abilities = $current_user->getCachedPermissions();
            if ($current_user_abilities->isEmpty()) {
                // Partners with full_access get all abilities
                $permissions = $partnerContext['permissions'] ?? [];
                if (in_array('full_access', $permissions)) {
                    $current_user_abilities = collect([['name' => '*', 'title' => 'All Abilities']]);
                }
            }

            $main_menu = $this->generateMenu('main_menu', $current_user);
            $setting_menu = $this->generateMenu('setting_menu', $current_user);

            $current_company_settings = CompanySetting::getAllSettings($current_company->id)->toArray();
            $currencyId = $current_company_settings['currency'] ?? null;
            $currencyModel = $currencyId ? Currency::find($currencyId) : Currency::first();
            $current_company_currency = $currencyModel ? $currencyModel->toArray() : null;

            $global_settings = Setting::getSettings([
                'api_token',
                'admin_portal_theme',
                'admin_portal_logo',
                'login_page_logo',
                'login_page_heading',
                'login_page_description',
                'admin_page_title',
                'copyright_text',
            ])->toArray();

            $global_settings['admin_portal_logo'] = logo_asset_url($global_settings['admin_portal_logo'] ?? null);
            $global_settings['login_page_logo'] = logo_asset_url($global_settings['login_page_logo'] ?? null);

            // Get feature flags - check database first, then fall back to config
            $feature_flags = $this->getFeatureFlags();

            return response()->json([
                'current_user' => (new UserResource($current_user->load('currency', 'settings')))->toArray($request),
                'current_user_settings' => $current_user->getAllSettings(),
                'current_user_abilities' => $current_user_abilities,
                'companies' => CompanyResource::collection($partnerCompanies)->toArray($request),
                'current_company' => (new CompanyResource($current_company))->toArray($request),
                'current_company_settings' => $current_company_settings,
                'current_company_currency' => $current_company_currency,
                'config' => config('invoiceshelf'),
                'global_settings' => $global_settings,
                'feature_flags' => $feature_flags,
                'main_menu' => $main_menu,
                'setting_menu' => $setting_menu,
                'modules' => Module::where('enabled', true)->pluck('name')->toArray(),
                'is_partner' => true,
                'partner_context' => $partnerContext,
            ]);
        }

        // Eager load user relationships to avoid N+1 queries
        // FG-01-12: Include subscription for feature gating
        $current_user->load([
            'currency',
            'settings',
            'companies.address',
            'companies.subscription',
        ]);

        $current_user_settings = $current_user->getAllSettings();

        $companyId = $request->header('company');

        $cacheKey = sprintf('bootstrap:%d:%s', $current_user->id, $companyId ?: 'primary');

        $bootstrapLogic = function () use ($current_user, $current_user_settings, $companyId) {
            // Refresh Bouncer cache first to ensure fresh abilities
            BouncerFacade::refreshFor($current_user);
            $current_user_abilities = $current_user->getCachedPermissions();

            // Fix: Use isEmpty() instead of empty() on Collections
            // empty() always returns false for Collection objects regardless of contents
            if ($current_user->isOwner() && $current_user_abilities->isEmpty()) {
                $current_user_abilities = collect([['name' => '*', 'title' => 'All Abilities']]);
            }

            $main_menu = $this->generateMenu('main_menu', $current_user);
            $setting_menu = $this->generateMenu('setting_menu', $current_user);

            $companies = $current_user->companies;

            $current_company = null;

            if ($companyId) {
                $current_company = $companies->firstWhere('id', $companyId);
            }

            if (! $current_company || ! $current_user->hasCompany($current_company->id)) {
                $current_company = $companies->first();
            }

            if ($current_company && ! $current_company->relationLoaded('address')) {
                $current_company->load('address');
            }

            // Handle case where user has no companies at all
            if (! $current_company) {
                abort(403, 'No company access. Please contact your administrator.');
            }

            $current_company_settings = CompanySetting::getAllSettings($current_company->id)->toArray();

            $currencyId = $current_company_settings['currency'] ?? null;
            $currencyModel = $currencyId ? Currency::find($currencyId) : Currency::first();

            $current_company_currency = $currencyModel ? $currencyModel->toArray() : null;

            $global_settings = Setting::getSettings([
                'api_token',
                'admin_portal_theme',
                'admin_portal_logo',
                'login_page_logo',
                'login_page_heading',
                'login_page_description',
                'admin_page_title',
                'copyright_text',
            ])->toArray();

            $global_settings['admin_portal_logo'] = logo_asset_url($global_settings['admin_portal_logo'] ?? null);
            $global_settings['login_page_logo'] = logo_asset_url($global_settings['login_page_logo'] ?? null);

            // Get feature flags - check database first, then fall back to config
            $feature_flags = $this->getFeatureFlags();

            $userPayload = (new UserResource($current_user))->toArray(request());
            $companiesPayload = CompanyResource::collection($companies)->toArray(request());
            $currentCompanyPayload = (new CompanyResource($current_company))->toArray(request());

            return [
                'current_user' => $userPayload,
                'current_user_settings' => $current_user_settings,
                'current_user_abilities' => $current_user_abilities,
                'companies' => $companiesPayload,
                'current_company' => $currentCompanyPayload,
                'current_company_settings' => $current_company_settings,
                'current_company_currency' => $current_company_currency,
                'config' => config('invoiceshelf'),
                'global_settings' => $global_settings,
                'feature_flags' => $feature_flags,
                'main_menu' => $main_menu,
                'setting_menu' => $setting_menu,
                'modules' => Module::where('enabled', true)->pluck('name')->toArray(),
            ];
        };

        try {
            $payload = Cache::remember($cacheKey, CacheServiceProvider::CACHE_TTLS['SHORT'], $bootstrapLogic);
        } catch (\Exception $e) {
            \Log::error('Bootstrap cache failed, falling back to direct execution', ['error' => $e->getMessage()]);
            $payload = $bootstrapLogic();
        }

        // Super Admin Support Mode: Override company context if in support mode
        $supportMode = session('support_mode');
        if ($current_user->role === 'super admin' && $supportMode) {
            $supportCompany = \App\Models\Company::with('address')->find($supportMode['company_id']);
            if ($supportCompany) {
                $supportCompanySettings = CompanySetting::getAllSettings($supportCompany->id)->toArray();
                $currencyId = $supportCompanySettings['currency'] ?? null;
                $currencyModel = $currencyId ? Currency::find($currencyId) : Currency::first();

                $payload['support_mode'] = $supportMode;
                $payload['current_company'] = (new CompanyResource($supportCompany))->toArray($request);
                $payload['current_company_settings'] = $supportCompanySettings;
                $payload['current_company_currency'] = $currencyModel ? $currencyModel->toArray() : null;

                // Keep original companies list but add support company if not present
                $companiesArray = $payload['companies'];
                $hasCompany = collect($companiesArray)->contains('id', $supportCompany->id);
                if (! $hasCompany) {
                    $payload['companies'][] = (new CompanyResource($supportCompany))->toArray($request);
                }
            }
        }

        // Always include support_mode status for super admins
        if ($current_user->role === 'super admin') {
            $payload['support_mode'] = $supportMode ?: null;
        }

        return response()->json($payload);
    }

    /**
     * Get feature flags with database values taking priority over config.
     *
     * @return array<string, bool>
     */
    private function getFeatureFlags(): array
    {
        $feature_flags = [];
        $features_config = config('features', []);

        foreach ($features_config as $key => $feature) {
            // Check database value first
            $dbKey = 'feature_flag.'.$key;
            $dbValue = Setting::getSetting($dbKey);

            if ($dbValue !== null) {
                $feature_flags[$key] = filter_var($dbValue, FILTER_VALIDATE_BOOLEAN);
            } else {
                $feature_flags[$key] = $feature['enabled'] ?? false;
            }
        }

        // Stock module is always enabled - no feature flag needed
        $feature_flags['stock'] = true;

        return $feature_flags;
    }
}

// CLAUDE-CHECKPOINT
