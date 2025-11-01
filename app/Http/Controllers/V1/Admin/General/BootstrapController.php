<?php

namespace App\Http\Controllers\V1\Admin\General;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\UserResource;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Module;
use App\Models\Setting;
use App\Traits\GeneratesMenuTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Silber\Bouncer\BouncerFacade;
use App\Providers\CacheServiceProvider;

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
        // Eager load user relationships to avoid N+1 queries
        $current_user = $request->user()->load([
            'currency',
            'settings',
            'companies.address',
            'companies.roles.permissions',
        ]);

        $current_user_settings = $current_user->getAllSettings();

        $companyId = $request->header('company');

        $cacheKey = sprintf('bootstrap:%d:%s', $current_user->id, $companyId ?: 'primary');

        $payload = Cache::remember($cacheKey, CacheServiceProvider::CACHE_TTLS['SHORT'], function () use ($current_user, $current_user_settings, $companyId) {
            // Refresh Bouncer cache first to ensure fresh abilities
            BouncerFacade::refreshFor($current_user);
            $current_user_abilities = $current_user->getCachedPermissions();

            if ($current_user->isOwner() && empty($current_user_abilities)) {
                $current_user_abilities = [['name' => '*', 'title' => 'All Abilities']];
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

            $current_company_settings = CompanySetting::getAllSettings($current_company->id)->toArray();

            $currencyModel = $current_company_settings->has('currency')
                ? Currency::find($current_company_settings->get('currency'))
                : Currency::first();

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
                'main_menu' => $main_menu,
                'setting_menu' => $setting_menu,
                'modules' => Module::where('enabled', true)->pluck('name')->toArray(),
            ];
        });

        return response()->json($payload);
    }
}
