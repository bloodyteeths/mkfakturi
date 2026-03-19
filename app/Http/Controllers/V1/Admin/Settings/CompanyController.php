<?php

namespace App\Http\Controllers\V1\Admin\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvatarRequest;
use App\Http\Requests\CompanyLogoRequest;
use App\Http\Requests\CompanyRequest;
use App\Http\Requests\CompanySignatureRequest;
use App\Http\Requests\CompanyStampRequest;
use App\Http\Requests\ProfileRequest;
use App\Http\Resources\CompanyResource;
use App\Http\Resources\UserResource;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    /**
     * Retrive the Admin account.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUser(Request $request)
    {
        return new UserResource($request->user());
    }

    /**
     * Update the Admin profile.
     * Includes name, email and (or) password
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(ProfileRequest $request)
    {
        $user = $request->user();

        $user->update($request->validated());

        return new UserResource($user);
    }

    /**
     * Update Admin Company Details
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCompany(CompanyRequest $request)
    {
        $company = Company::find($request->header('company'));

        $this->authorize('manage company', $company);

        $payload = $request->getCompanyPayload();

        // Log the payload being saved
        \Log::info('CompanyController::updateCompany - Payload', [
            'company_id' => $company->id,
            'payload' => $payload,
            'vat_id' => $payload['vat_id'] ?? null,
            'vat_number' => $payload['vat_number'] ?? null,
        ]);

        $company->update($payload);

        // Reload to get fresh data
        $company->refresh();

        // Log what was actually saved
        \Log::info('CompanyController::updateCompany - After Save', [
            'company_id' => $company->id,
            'vat_id' => $company->vat_id,
            'vat_number' => $company->vat_number,
            'tax_id' => $company->tax_id,
        ]);

        $company->address()->updateOrCreate(['company_id' => $company->id], $request->address);

        return new CompanyResource($company);
    }

    /**
     * Upload the company logo to storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadCompanyLogo(CompanyLogoRequest $request)
    {
        $company = Company::find($request->header('company'));

        $this->authorize('manage company', $company);

        $data = json_decode($request->company_logo);

        if (isset($request->is_company_logo_removed) && (bool) $request->is_company_logo_removed) {
            $company->clearMediaCollection('logo');
        }
        if ($data) {
            $company = Company::find($request->header('company'));

            if ($company) {
                $company->clearMediaCollection('logo');

                $company->addMediaFromBase64($data->data)
                    ->usingFileName($data->name)
                    ->toMediaCollection('logo');
            }
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function uploadCompanyStamp(CompanyStampRequest $request)
    {
        $company = Company::find($request->header('company'));

        $this->authorize('manage company', $company);

        try {
            $data = json_decode($request->company_stamp);

            \Log::info('uploadCompanyStamp', [
                'company_id' => $company->id,
                'has_data' => (bool) $data,
                'has_raw' => (bool) $request->company_stamp,
                'raw_length' => strlen($request->company_stamp ?? ''),
                'data_name' => $data->name ?? null,
                'removed' => $request->is_company_stamp_removed,
            ]);

            if (isset($request->is_company_stamp_removed) && $request->is_company_stamp_removed === 'true') {
                $company->clearMediaCollection('stamp');
            }
            if ($data) {
                $company->clearMediaCollection('stamp');

                $company->addMediaFromBase64($data->data)
                    ->usingFileName($data->name)
                    ->toMediaCollection('stamp');

                \Log::info('Stamp saved successfully', ['company_id' => $company->id]);
            }

            return response()->json([
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            \Log::error('uploadCompanyStamp failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Stamp upload failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function uploadCompanySignature(CompanySignatureRequest $request)
    {
        $company = Company::find($request->header('company'));

        $this->authorize('manage company', $company);

        try {
            $data = json_decode($request->company_signature);

            \Log::info('uploadCompanySignature', [
                'company_id' => $company->id,
                'has_data' => (bool) $data,
                'has_raw' => (bool) $request->company_signature,
                'raw_length' => strlen($request->company_signature ?? ''),
                'data_name' => $data->name ?? null,
                'removed' => $request->is_company_signature_removed,
            ]);

            if (isset($request->is_company_signature_removed) && $request->is_company_signature_removed === 'true') {
                $company->clearMediaCollection('signature');
            }
            if ($data) {
                $company->clearMediaCollection('signature');

                $company->addMediaFromBase64($data->data)
                    ->usingFileName($data->name)
                    ->toMediaCollection('signature');

                \Log::info('Signature saved successfully', ['company_id' => $company->id]);
            }

            return response()->json([
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            \Log::error('uploadCompanySignature failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Signature upload failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload the Admin Avatar to public storage.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadAvatar(AvatarRequest $request)
    {
        $user = auth()->user();

        if (isset($request->is_admin_avatar_removed) && (bool) $request->is_admin_avatar_removed) {
            $user->clearMediaCollection('admin_avatar');
        }
        if ($user && $request->hasFile('admin_avatar')) {
            $user->clearMediaCollection('admin_avatar');

            $user->addMediaFromRequest('admin_avatar')
                ->toMediaCollection('admin_avatar');
        }

        if ($user && $request->has('avatar')) {
            $data = json_decode($request->avatar);
            $user->clearMediaCollection('admin_avatar');

            $user->addMediaFromBase64($data->data)
                ->usingFileName($data->name)
                ->toMediaCollection('admin_avatar');
        }

        return new UserResource($user);
    }
}
