<?php

namespace App\Http\Controllers\V1\Admin\Support;

use App\Http\Controllers\Controller;
use App\Http\Resources\CompanyResource;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Super Admin Company Browser for Support
 *
 * Allows super admins to search and switch to any company
 * in the SaaS for support/debugging purposes.
 */
class AdminCompanyBrowserController extends Controller
{
    /**
     * Search all companies in the system (super admin only)
     */
    public function search(Request $request)
    {
        // Verify super admin
        if ($request->user()->role !== 'super admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = $request->input('q', '');
        $limit = min($request->input('limit', 10), 50);

        $companies = Company::query()
            ->with(['owner', 'address'])
            ->when($query, function ($q) use ($query) {
                $q->where(function ($subQuery) use ($query) {
                    $subQuery->where('name', 'like', "%{$query}%")
                        ->orWhereHas('owner', function ($ownerQuery) use ($query) {
                            $ownerQuery->where('email', 'like', "%{$query}%")
                                ->orWhere('name', 'like', "%{$query}%");
                        })
                        ->orWhereHas('address', function ($addressQuery) use ($query) {
                            $addressQuery->where('city', 'like', "%{$query}%");
                        });
                });
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return response()->json([
            'companies' => $companies->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->name,
                    'logo' => $company->logo,
                    'owner_name' => $company->owner?->name,
                    'owner_email' => $company->owner?->email,
                    'city' => $company->address?->city,
                    'created_at' => $company->created_at?->format('Y-m-d'),
                ];
            }),
            'total' => $query ? $companies->count() : Company::count(),
        ]);
    }

    /**
     * Enter support mode for a specific company
     */
    public function enterSupportMode(Request $request, Company $company)
    {
        $user = $request->user();

        // Verify super admin
        if ($user->role !== 'super admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Store support mode context in session
        session([
            'support_mode' => [
                'company_id' => $company->id,
                'company_name' => $company->name,
                'admin_id' => $user->id,
                'admin_name' => $user->name,
                'started_at' => now()->toISOString(),
            ],
        ]);

        Log::info('Super admin entered support mode', [
            'admin_id' => $user->id,
            'admin_email' => $user->email,
            'company_id' => $company->id,
            'company_name' => $company->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Entered support mode for {$company->name}",
            'support_mode' => session('support_mode'),
            'company' => new CompanyResource($company->load('address')),
        ]);
    }

    /**
     * Exit support mode
     */
    public function exitSupportMode(Request $request)
    {
        $user = $request->user();

        // Verify super admin
        if ($user->role !== 'super admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $supportMode = session('support_mode');

        if ($supportMode) {
            Log::info('Super admin exited support mode', [
                'admin_id' => $user->id,
                'admin_email' => $user->email,
                'company_id' => $supportMode['company_id'] ?? null,
                'duration_minutes' => $supportMode['started_at']
                    ? now()->diffInMinutes($supportMode['started_at'])
                    : 0,
            ]);
        }

        session()->forget('support_mode');

        return response()->json([
            'success' => true,
            'message' => 'Exited support mode',
        ]);
    }

    /**
     * Get current support mode status
     */
    public function status(Request $request)
    {
        if ($request->user()->role !== 'super admin') {
            return response()->json([
                'in_support_mode' => false,
            ]);
        }

        $supportMode = session('support_mode');

        return response()->json([
            'in_support_mode' => ! empty($supportMode),
            'support_mode' => $supportMode,
        ]);
    }
}
