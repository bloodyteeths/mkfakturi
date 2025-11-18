<?php

namespace App\Http\Controllers\V1\Admin\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompaniesRequest;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use App\Models\CompanySubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Silber\Bouncer\BouncerFacade;
use Vinkla\Hashids\Facades\Hashids;

class CompaniesController extends Controller
{
    public function store(CompaniesRequest $request)
    {
        $this->authorize('create company');

        $user = $request->user();

        $company = Company::create($request->getCompanyPayload());
        $company->unique_hash = Hashids::connection(Company::class)->encode($company->id);
        $company->save();
        $company->setupDefaultData();
        $user->companies()->attach($company->id);
        $user->assign('super admin');

        if ($request->address) {
            $company->address()->create($request->address);
        }

        // FG-01-40: Auto-enroll new companies in Standard trial (14 days)
        if (config('subscriptions.trial.enabled', true)) {
            $trialDays = config('subscriptions.trial.duration_days', 14);
            $trialPlan = config('subscriptions.trial.plan', 'standard');

            CompanySubscription::create([
                'company_id' => $company->id,
                'plan' => $trialPlan,
                'status' => 'trial',
                'started_at' => Carbon::now(),
                'trial_ends_at' => Carbon::now()->addDays($trialDays),
            ]);
        }

        return new CompanyResource($company);
    }

    public function destroy(Request $request)
    {
        $company = Company::find($request->header('company'));

        $this->authorize('delete company', $company);

        $user = $request->user();

        if ($request->name !== $company->name) {
            return respondJson('company_name_must_match_with_given_name', 'Company name must match with given name');
        }

        if ($user->loadCount('companies')->companies_count <= 1) {
            return respondJson('You_cannot_delete_all_companies', 'You cannot delete all companies');
        }

        $company->deleteCompany($user);

        return response()->json([
            'success' => true,
        ]);
    }

    public function transferOwnership(Request $request, User $user)
    {
        $company = Company::find($request->header('company'));
        $this->authorize('transfer company ownership', $company);

        if ($user->hasCompany($company->id)) {
            return response()->json([
                'success' => false,
                'message' => 'User does not belongs to this company.',
            ]);
        }

        $company->update(['owner_id' => $user->id]);
        BouncerFacade::sync($user)->roles(['super admin']);

        return response()->json([
            'success' => true,
        ]);
    }

    public function getUserCompanies(Request $request)
    {
        $companies = $request->user()->companies;

        // Load roles for each company with proper Bouncer scope
        $companies->each(function ($company) {
            BouncerFacade::scope()->to($company->id);
            $company->load('roles');
        });

        return CompanyResource::collection($companies);
    }
}
