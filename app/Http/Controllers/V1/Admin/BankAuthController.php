<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankToken;
use App\Models\Company;
use App\Services\Banking\Mt940Parser;
use Modules\Mk\Services\StopanskaOAuth;
use Modules\Mk\Services\NlbOAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Bank Authentication Controller
 *
 * Handles PSD2 OAuth flow for bank integrations
 * Endpoints for connecting, disconnecting, and checking bank connection status
 *
 * Feature Flag: FEATURE_PSD2_BANKING
 */
class BankAuthController extends Controller
{
    /**
     * Initiate OAuth2 authorization flow
     *
     * POST /api/v1/admin/{company}/banking/auth/{bankCode}
     *
     * @param Request $request
     * @param int $companyId Company ID
     * @param string $bankCode Bank identifier (stopanska, nlb)
     * @return JsonResponse
     */
    public function initiateOAuth(Request $request, int $companyId, string $bankCode): JsonResponse
    {
        // Check feature flag
        if (!config('mk.features.psd2_banking', false)) {
            return response()->json([
                'error' => 'PSD2 banking feature is not enabled',
            ], 403);
        }

        $company = Company::findOrFail($companyId);

        // Authorization check (ensure user can manage this company)
        $this->authorize('view', $company);

        $client = $this->getClientForBank($bankCode);

        if (!$client) {
            return response()->json([
                'error' => 'Unsupported bank: ' . $bankCode,
            ], 400);
        }

        $redirectUri = route('banking.callback', [
            'company' => $companyId,
            'bank' => $bankCode,
        ]);

        $authUrl = $client->getAuthUrl($company, $redirectUri);

        return response()->json([
            'auth_url' => $authUrl,
            'bank_code' => $bankCode,
            'bank_name' => method_exists($client, 'getBankName') ? $client->getBankName() : $bankCode,
        ]);
    }

    /**
     * Handle OAuth2 callback
     *
     * GET /banking/callback/{company}/{bank}?code=xxx&state=xxx
     *
     * @param Request $request
     * @param int $companyId Company ID
     * @param string $bankCode Bank identifier
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleCallback(Request $request, int $companyId, string $bankCode)
    {
        // Check feature flag
        if (!config('mk.features.psd2_banking', false)) {
            return redirect('/admin/settings/banking?error=feature_disabled');
        }

        $company = Company::findOrFail($companyId);
        $code = $request->query('code');

        if (!$code) {
            Log::warning('OAuth callback missing authorization code', [
                'company_id' => $companyId,
                'bank' => $bankCode,
            ]);

            return redirect('/admin/settings/banking?error=missing_code');
        }

        $client = $this->getClientForBank($bankCode);

        if (!$client) {
            return redirect('/admin/settings/banking?error=unsupported_bank');
        }

        try {
            $redirectUri = route('banking.callback', [
                'company' => $companyId,
                'bank' => $bankCode,
            ]);

            $token = $client->exchangeCode($company, $code, $redirectUri);

            Log::info('Bank OAuth connection successful', [
                'company_id' => $companyId,
                'bank' => $bankCode,
                'token_id' => $token->id,
            ]);

            return redirect('/admin/settings/banking?success=connected&bank=' . $bankCode);
        } catch (\Exception $e) {
            Log::error('OAuth token exchange failed', [
                'company_id' => $companyId,
                'bank' => $bankCode,
                'error' => $e->getMessage(),
            ]);

            return redirect('/admin/settings/banking?error=token_exchange_failed');
        }
    }

    /**
     * Get bank connection status
     *
     * GET /api/v1/admin/{company}/banking/status/{bankCode}
     *
     * @param Request $request
     * @param int $companyId Company ID
     * @param string $bankCode Bank identifier
     * @return JsonResponse
     */
    public function getStatus(Request $request, int $companyId, string $bankCode): JsonResponse
    {
        // Check feature flag
        if (!config('mk.features.psd2_banking', false)) {
            return response()->json([
                'error' => 'PSD2 banking feature is not enabled',
            ], 403);
        }

        $company = Company::findOrFail($companyId);

        // Authorization check
        $this->authorize('view', $company);

        $token = BankToken::where('company_id', $companyId)
            ->where('bank_code', $bankCode)
            ->first();

        if (!$token) {
            return response()->json([
                'connected' => false,
                'bank_code' => $bankCode,
            ]);
        }

        return response()->json([
            'connected' => true,
            'bank_code' => $bankCode,
            'expires_at' => $token->expires_at->toIso8601String(),
            'is_valid' => $token->isValid(),
            'is_expiring_soon' => $token->isExpiringSoon(),
            'minutes_until_expiration' => $token->getMinutesUntilExpiration(),
        ]);
    }

    /**
     * Disconnect bank (revoke OAuth token)
     *
     * DELETE /api/v1/admin/{company}/banking/disconnect/{bankCode}
     *
     * @param Request $request
     * @param int $companyId Company ID
     * @param string $bankCode Bank identifier
     * @return JsonResponse
     */
    public function revoke(Request $request, int $companyId, string $bankCode): JsonResponse
    {
        // Check feature flag
        if (!config('mk.features.psd2_banking', false)) {
            return response()->json([
                'error' => 'PSD2 banking feature is not enabled',
            ], 403);
        }

        $company = Company::findOrFail($companyId);

        // Authorization check
        $this->authorize('view', $company);

        $client = $this->getClientForBank($bankCode);

        if (!$client) {
            return response()->json([
                'error' => 'Unsupported bank: ' . $bankCode,
            ], 400);
        }

        try {
            $client->revokeToken($company);

            Log::info('Bank connection revoked', [
                'company_id' => $companyId,
                'bank' => $bankCode,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bank connection disconnected successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to revoke bank token', [
                'company_id' => $companyId,
                'bank' => $bankCode,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to disconnect bank: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import MT940 file (CSV fallback)
     *
     * POST /api/v1/admin/{company}/banking/import-mt940
     *
     * @param Request $request
     * @param int $companyId Company ID
     * @return JsonResponse
     */
    public function importCsv(Request $request, int $companyId): JsonResponse
    {
        // Check feature flag
        if (!config('mk.features.psd2_banking', false)) {
            return response()->json([
                'error' => 'PSD2 banking feature is not enabled',
            ], 403);
        }

        $company = Company::findOrFail($companyId);

        // Authorization check
        $this->authorize('view', $company);

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:txt,mt940,csv|max:10240', // Max 10MB
            'bank_account_id' => 'required|exists:bank_accounts,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $bankAccount = BankAccount::findOrFail($request->bank_account_id);

        // Ensure bank account belongs to this company
        if ($bankAccount->company_id !== $companyId) {
            return response()->json([
                'error' => 'Bank account does not belong to this company',
            ], 403);
        }

        try {
            $file = $request->file('file');
            $filePath = $file->getRealPath();

            $parser = app(Mt940Parser::class);

            // Try MT940 format first, then CSV if it fails
            try {
                $imported = $parser->parseFile($filePath, $bankAccount);
            } catch (\Exception $e) {
                Log::info('MT940 parsing failed, trying CSV', [
                    'error' => $e->getMessage(),
                ]);

                $imported = $parser->parseCsv($filePath, $bankAccount);
            }

            return response()->json([
                'success' => true,
                'imported' => $imported,
                'bank_account_id' => $bankAccount->id,
            ]);
        } catch (\Exception $e) {
            Log::error('MT940/CSV import failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get PSD2 client for a bank code
     *
     * @param string $bankCode Bank identifier
     * @return \App\Services\Banking\Psd2Client|null
     */
    protected function getClientForBank(string $bankCode): ?\App\Services\Banking\Psd2Client
    {
        return match ($bankCode) {
            'stopanska' => app(StopanskaOAuth::class),
            'nlb' => app(NlbOAuth::class),
            default => null,
        };
    }
}

// CLAUDE-CHECKPOINT
