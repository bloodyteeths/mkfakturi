<?php

namespace App\Http\Controllers\V1\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

/**
 * TwoFactorController
 *
 * Handles two-factor authentication (2FA) operations for users.
 * Supports enabling, disabling, confirming 2FA, and managing recovery codes.
 */
class TwoFactorController extends Controller
{
    /**
     * Enable two-factor authentication for the current user.
     *
     * Generates a new 2FA secret and recovery codes for the user.
     * Returns the QR code SVG and secret key for authenticator app setup.
     *
     * @param Request $request
     * @param EnableTwoFactorAuthentication $enable
     * @return JsonResponse
     */
    public function enable(Request $request, EnableTwoFactorAuthentication $enable): JsonResponse
    {
        $user = $request->user();

        // Enable 2FA for the user
        $enable($user);

        // Get the QR code SVG
        $qrCode = $user->twoFactorQrCodeSvg();

        // Get the secret key for manual entry
        $secretKey = decrypt($user->two_factor_secret);

        // Get recovery codes
        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication has been enabled.',
            'data' => [
                'qr_code' => $qrCode,
                'secret_key' => $secretKey,
                'recovery_codes' => $recoveryCodes,
            ],
        ]);
    }

    /**
     * Confirm two-factor authentication by verifying a code.
     *
     * Confirms that the user has successfully set up their authenticator app
     * by requiring them to provide a valid verification code.
     *
     * @param Request $request
     * @param ConfirmTwoFactorAuthentication $confirm
     * @return JsonResponse
     */
    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        try {
            $confirm($user, $request->code);

            return response()->json([
                'success' => true,
                'message' => 'Two-factor authentication has been confirmed.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'The provided code was invalid.',
            ], 422);
        }
    }

    /**
     * Disable two-factor authentication for the current user.
     *
     * @param Request $request
     * @param DisableTwoFactorAuthentication $disable
     * @return JsonResponse
     */
    public function disable(Request $request, DisableTwoFactorAuthentication $disable): JsonResponse
    {
        $user = $request->user();

        $disable($user);

        return response()->json([
            'success' => true,
            'message' => 'Two-factor authentication has been disabled.',
        ]);
    }

    /**
     * Get the current two-factor authentication status.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'two_factor_enabled' => !is_null($user->two_factor_secret),
                'two_factor_confirmed' => !is_null($user->two_factor_confirmed_at),
            ],
        ]);
    }

    /**
     * Get the QR code SVG for the current user.
     *
     * Returns the QR code SVG if 2FA is enabled but not yet confirmed.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function qrCode(Request $request): JsonResponse
    {
        $user = $request->user();

        if (is_null($user->two_factor_secret)) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication has not been enabled.',
            ], 400);
        }

        $qrCode = $user->twoFactorQrCodeSvg();
        $secretKey = decrypt($user->two_factor_secret);

        return response()->json([
            'success' => true,
            'data' => [
                'qr_code' => $qrCode,
                'secret_key' => $secretKey,
            ],
        ]);
    }

    /**
     * Get the recovery codes for the current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recoveryCodes(Request $request): JsonResponse
    {
        $user = $request->user();

        if (is_null($user->two_factor_secret)) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication has not been enabled.',
            ], 400);
        }

        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        return response()->json([
            'success' => true,
            'data' => [
                'recovery_codes' => $recoveryCodes,
            ],
        ]);
    }

    /**
     * Generate new recovery codes for the current user.
     *
     * @param Request $request
     * @param GenerateNewRecoveryCodes $generate
     * @return JsonResponse
     */
    public function regenerateRecoveryCodes(Request $request, GenerateNewRecoveryCodes $generate): JsonResponse
    {
        $user = $request->user();

        if (is_null($user->two_factor_secret)) {
            return response()->json([
                'success' => false,
                'message' => 'Two-factor authentication has not been enabled.',
            ], 400);
        }

        $generate($user);

        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        return response()->json([
            'success' => true,
            'message' => 'New recovery codes have been generated.',
            'data' => [
                'recovery_codes' => $recoveryCodes,
            ],
        ]);
    }
}

// CLAUDE-CHECKPOINT
