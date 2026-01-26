<?php

namespace App\Http\Controllers\V1\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Company;
use App\Models\CompanySetting;
use Illuminate\Support\Arr;

class UpdateCompanySettingsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\UpdateSettingsRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(UpdateSettingsRequest $request)
    {
        $company = Company::find($request->header('company'));
        $this->authorize('manage company', $company);

        $data = $request->settings;

        if (
            Arr::exists($data, 'currency') &&
            (CompanySetting::getSetting('currency', $company->id) !== $data['currency']) &&
            $company->hasTransactions()
        ) {
            return response()->json([
                'success' => false,
                'message' => __('settings.company_info.cannot_update_currency'),
            ]);
        }

        CompanySetting::setSettings($data, $request->header('company'));

        $response = ['success' => true];

        // Generate fresh token for installation flow
        $user = $request->user();
        if ($user) {
            $token = $user->createToken('settings-token')->plainTextToken;
            $response['token'] = 'Bearer '.$token;
        }

        return response()->json($response);
    }
}
