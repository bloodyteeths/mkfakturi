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
        // Eager load user relationships to avoid N+1 queries
        $current_user = $request->user()->load([
            'currency',
            'settings',
            'companies.address',
            'companies' => function ($query) {
                // Pre-load roles for each company to avoid N+1 in CompanyResource
                $query->select('companies.*');
            }
        ]);

        $current_user_settings = $current_user->getAllSettings();

        // Refresh Bouncer cache first to ensure fresh abilities
        // Then get cached abilities (will fetch fresh from DB after refresh)
        BouncerFacade::refreshFor($current_user);
        $current_user_abilities = $current_user->getCachedPermissions();

        // Generate menus after abilities are loaded and cached
        // Menu generation uses checkAccess() which calls can() - abilities are now cached
        $main_menu = $this->generateMenu('main_menu', $current_user);
        $setting_menu = $this->generateMenu('setting_menu', $current_user);

        // Companies already loaded via eager loading above
        $companies = $current_user->companies;

        // Use loaded companies instead of querying again
        $companyId = $request->header('company');
        $current_company = null;
        
        if ($companyId) {
            // Try to find company from already loaded collection
            $current_company = $current_user->companies->firstWhere('id', $companyId);
        }
        
        // If not found in loaded companies or invalid, get first company
        if (!$current_company || !$current_user->hasCompany($current_company->id)) {
            $current_company = $current_user->companies->first();
        }

        // Ensure address is loaded (should already be via eager load, but just in case)
        if ($current_company && !$current_company->relationLoaded('address')) {
            $current_company->load('address');
        }

        // Use cached company settings
        $current_company_settings = CompanySetting::getAllSettings($current_company->id);

        $current_company_currency = $current_company_settings->has('currency')
            ? Currency::find($current_company_settings->get('currency'))
            : Currency::first();

        $global_settings = Setting::getSettings([
            'api_token',
            'admin_portal_theme',
            'admin_portal_logo',
            'login_page_logo',
            'login_page_heading',
            'login_page_description',
            'admin_page_title',
            'copyright_text',
        ]);

        return response()->json([
            'current_user' => new UserResource($current_user),
            'current_user_settings' => $current_user_settings,
            'current_user_abilities' => $current_user_abilities,
            'companies' => CompanyResource::collection($companies),
            'current_company' => new CompanyResource($current_company),
            'current_company_settings' => $current_company_settings,
            'current_company_currency' => $current_company_currency,
            'config' => config('invoiceshelf'),
            'global_settings' => $global_settings,
            'main_menu' => $main_menu,
            'setting_menu' => $setting_menu,
            'modules' => Module::where('enabled', true)->pluck('name'),
        ]);
    }
}
