<?php

namespace Modules\Mk\Public\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * PartnerSignupController
 *
 * Handles public partner registration with referral tracking
 */
class PartnerSignupController extends Controller
{
    /**
     * Validate partner referral token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateReferral(Request $request)
    {
        $token = $request->input('token');

        if (! $token) {
            return response()->json(['valid' => false], 400);
        }

        $referral = DB::table('partner_referrals')
            ->where('referral_token', $token)
            ->where('status', 'pending')
            ->first();

        if (! $referral) {
            return response()->json(['valid' => false], 404);
        }

        // Get inviter partner info
        $inviter = Partner::find($referral->inviter_partner_id);

        if (! $inviter || ! $inviter->is_active) {
            return response()->json(['valid' => false], 404);
        }

        return response()->json([
            'valid' => true,
            'referrer' => [
                'name' => $inviter->name,
                'company' => $inviter->company_name,
            ],
        ]);
    }

    /**
     * Register new partner
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|min:2',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|same:password',
            'accept_terms' => 'accepted',
            'referral_token' => 'nullable|string',
        ], [
            'email.unique' => 'Овој email веќе е регистриран.',
            'password.min' => 'Лозинката мора да има минимум 8 карактери.',
            'password_confirmation.same' => 'Лозинките не се совпаѓаат.',
            'accept_terms.accepted' => 'Мора да ги прифатите условите за користење.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Find inviter partner from referral token
            $inviterPartnerId = null;
            if ($request->referral_token) {
                $referral = DB::table('partner_referrals')
                    ->where('referral_token', $request->referral_token)
                    ->where('status', 'pending')
                    ->first();

                if ($referral) {
                    $inviterPartnerId = $referral->inviter_partner_id;
                }
            }

            // Create user account
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password, // User model auto-hashes
                'role' => 'partner',
            ]);

            // Create partner record
            $partner = Partner::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'company_name' => $request->company_name,
                'tax_id' => $request->tax_id,
                'user_id' => $user->id,
                'commission_rate' => 20.00, // Default 20%
                'is_active' => true,
                'kyc_status' => 'pending',
            ]);

            // Update referral record if exists
            if ($inviterPartnerId && $referral) {
                DB::table('partner_referrals')
                    ->where('id', $referral->id)
                    ->update([
                        'invitee_partner_id' => $partner->id,
                        'status' => 'accepted',
                        'accepted_at' => now(),
                        'updated_at' => now(),
                    ]);

                Log::info('Partner referral accepted', [
                    'inviter_partner_id' => $inviterPartnerId,
                    'invitee_partner_id' => $partner->id,
                ]);
            }

            DB::commit();

            Log::info('Partner registration successful', [
                'partner_id' => $partner->id,
                'user_id' => $user->id,
                'inviter_partner_id' => $inviterPartnerId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Успешна регистрација! Најавете се на партнерскиот портал.',
                'partner_id' => $partner->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Partner registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Регистрацијата не успеа. Обидете се повторно.',
            ], 500);
        }
    }
}

// CLAUDE-CHECKPOINT
