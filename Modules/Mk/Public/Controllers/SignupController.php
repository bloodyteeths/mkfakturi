<?php

namespace Modules\Mk\Public\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Mk\Public\Requests\SignupRequest;
use Modules\Mk\Public\Services\CompanyReferralRewardService;
use Modules\Mk\Public\Services\SignupService;

/**
 * SignupController
 *
 * Handles public company signup flow with referral tracking
 * No authentication required - public endpoints
 */
class SignupController extends Controller
{
    protected SignupService $signupService;

    /**
     * Constructor
     */
    public function __construct(SignupService $signupService)
    {
        $this->signupService = $signupService;
    }

    /**
     * Validate company-to-company referral token
     *
     * POST /api/v1/public/signup/validate-company-referral
     */
    public function validateCompanyReferral(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string|max:64',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $token = $request->input('token');

        try {
            $rewardService = app(CompanyReferralRewardService::class);
            $referralData = $rewardService->validateReferralToken($token);

            if (! $referralData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired referral link.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'referral_id' => $referralData['referral_id'],
                    'inviter_company_name' => $referralData['inviter_company_name'],
                    'discount_percent' => $referralData['discount_percent'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Company referral validation failed', [
                'token' => $token,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to validate referral link.',
            ], 500);
        }
    }

    /**
     * Validate referral code and return partner information
     *
     * POST /api/v1/public/signup/validate-referral
     */
    public function validateReferral(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $code = $request->input('code');

        try {
            $referralData = $this->signupService->validateReferralCode($code);

            if (! $referralData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or inactive referral code.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'partner_id' => $referralData['partner_id'],
                    'partner_name' => $referralData['partner_name'],
                    'partner_company' => $referralData['partner_company'],
                    'affiliate_link_id' => $referralData['affiliate_link_id'],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Referral validation failed', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to validate referral code.',
            ], 500);
        }
    }

    /**
     * Get available subscription plans
     *
     * GET /api/v1/public/signup/plans
     */
    public function getPlans(Request $request): JsonResponse
    {
        try {
            $plans = $this->signupService->getAvailablePlans();

            return response()->json([
                'success' => true,
                'data' => $plans,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch plans', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subscription plans.',
            ], 500);
        }
    }

    /**
     * Register new company with user and create Stripe Checkout session
     *
     * POST /api/v1/public/signup/register
     */
    public function register(SignupRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            // If referral code provided, validate it first
            if (! empty($data['referral_code']) && empty($data['partner_id'])) {
                $referralData = $this->signupService->validateReferralCode($data['referral_code']);

                if ($referralData) {
                    $data['partner_id'] = $referralData['partner_id'];
                    $data['affiliate_link_id'] = $referralData['affiliate_link_id'];
                }
            }

            // Register company and create checkout session
            $result = $this->signupService->register($data);

            return response()->json([
                'success' => true,
                'message' => 'Registration successful. Redirecting to checkout...',
                'data' => [
                    'company_id' => $result['company']->id,
                    'company_slug' => $result['company']->slug,
                    'user_id' => $result['user']->id,
                    'checkout_url' => $result['checkout_url'],
                    'checkout_session_id' => $result['checkout_session_id'],
                ],
            ], 201);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            Log::error('Stripe API error during registration', [
                'error' => $e->getMessage(),
                'email' => $request->input('email'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment service error. Please try again.',
                'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'email' => $request->input('email'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}

// CLAUDE-CHECKPOINT
