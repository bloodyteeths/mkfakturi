<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Mk\Models\Partner;

class PartnerOnboardingController extends Controller
{
    /**
     * GET /partner/onboarding/progress
     *
     * Returns partner onboarding progress.
     */
    public function progress(Request $request): JsonResponse
    {
        $user = auth()->user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (!$partner) {
            return response()->json(['error' => 'Partner not found'], 404);
        }

        $hasPortfolioCompanies = $partner->companies()->count() > 0;
        $portfolioActivated = (bool) $partner->portfolio_enabled;

        // Check if any company has imported data
        $companiesWithImports = 0;
        $totalCompanies = $partner->companies()->count();

        foreach ($partner->companies as $company) {
            $hasData = $company->invoices()->exists()
                || $company->bills()->exists()
                || \DB::table('ifrs_transactions')
                    ->where('entity_id', $company->id)
                    ->where('transaction_type', 'JN')
                    ->exists();

            if ($hasData) {
                $companiesWithImports++;
            }
        }

        $completedAt = $partner->onboarding_completed_at;
        $dismissed = !empty($completedAt);

        return response()->json([
            'portfolio_activated' => $portfolioActivated,
            'has_companies' => $hasPortfolioCompanies,
            'total_companies' => $totalCompanies,
            'companies_with_imports' => $companiesWithImports,
            'completed' => $dismissed,
            'completed_at' => $completedAt,
            'steps' => [
                ['key' => 'portfolio_import', 'completed' => $hasPortfolioCompanies],
                ['key' => 'company_data_import', 'completed' => $companiesWithImports > 0],
                ['key' => 'accounting_setup', 'completed' => $dismissed],
            ],
        ]);
    }

    /**
     * POST /partner/onboarding/complete
     *
     * Marks partner onboarding as completed.
     */
    public function complete(Request $request): JsonResponse
    {
        $user = auth()->user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (!$partner) {
            return response()->json(['error' => 'Partner not found'], 404);
        }

        $partner->update([
            'onboarding_completed_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }
}
// CLAUDE-CHECKPOINT
