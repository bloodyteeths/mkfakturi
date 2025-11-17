<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartnerInvitationController extends Controller
{
    /**
     * Company invites Partner (AC-11)
     */
    public function companyInvitesPartner(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'partner_email' => 'required|email',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string',
        ]);

        $partner = Partner::where('email', $validated['partner_email'])->first();

        if (!$partner) {
            return response()->json(['message' => 'Partner not found'], 404);
        }

        $exists = DB::table('partner_company_links')
            ->where('partner_id', $partner->id)
            ->where('company_id', $validated['company_id'])
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Partner already linked'], 422);
        }

        DB::table('partner_company_links')->insert([
            'partner_id' => $partner->id,
            'company_id' => $validated['company_id'],
            'permissions' => json_encode($validated['permissions']),
            'invitation_status' => 'pending',
            'created_by' => auth()->id(),
            'invited_at' => now(),
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Invitation sent']);
    }

    /**
     * Partner accepts/declines invitation (AC-11)
     */
    public function respondToInvitation(Request $request, $linkId)
    {
        $validated = $request->validate([
            'action' => 'required|in:accept,decline',
        ]);

        $link = DB::table('partner_company_links')->where('id', $linkId)->first();

        if (!$link || $link->invitation_status !== 'pending') {
            return response()->json(['message' => 'Invalid invitation'], 404);
        }

        if ($validated['action'] === 'accept') {
            DB::table('partner_company_links')->where('id', $linkId)->update([
                'invitation_status' => 'accepted',
                'accepted_at' => now(),
                'is_active' => true,
            ]);
            return response()->json(['message' => 'Invitation accepted']);
        }

        DB::table('partner_company_links')->where('id', $linkId)->update([
            'invitation_status' => 'declined',
        ]);

        return response()->json(['message' => 'Invitation declined']);
    }

    /**
     * Partner invites Company via affiliate link (AC-12)
     * Uses existing AffiliateLink model
     */
    public function partnerInvitesCompany(Request $request)
    {
        $user = auth()->user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (!$partner) {
            return response()->json(['message' => 'Not a partner'], 403);
        }

        $affiliateLink = \App\Models\AffiliateLink::firstOrCreate(
            ['partner_id' => $partner->id],
            ['code' => Str::random(10), 'is_active' => true]
        );

        return response()->json([
            'link' => url('/signup?ref=' . $affiliateLink->code),
            'qr_code_url' => url('/api/qr?data=' . urlencode(url('/signup?ref=' . $affiliateLink->code))),
        ]);
    }

    /**
     * Company invites Company (AC-14)
     */
    public function companyInvitesCompany(Request $request)
    {
        $validated = $request->validate([
            'inviter_company_id' => 'required|exists:companies,id',
            'invitee_email' => 'required|email',
        ]);

        // Create referral token
        $token = Str::random(32);

        DB::table('company_referrals')->insert([
            'inviter_company_id' => $validated['inviter_company_id'],
            'invitee_email' => $validated['invitee_email'],
            'referral_token' => $token,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Invitation sent',
            'signup_link' => url('/signup?company_ref=' . $token),
        ]);
    }

    /**
     * Partner invites Partner (AC-15)
     */
    public function partnerInvitesPartner(Request $request)
    {
        $user = auth()->user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (!$partner) {
            return response()->json(['message' => 'Not a partner'], 403);
        }

        $validated = $request->validate([
            'invitee_email' => 'required|email',
        ]);

        $token = Str::random(32);

        DB::table('partner_referrals')->insert([
            'inviter_partner_id' => $partner->id,
            'invitee_email' => $validated['invitee_email'],
            'referral_token' => $token,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Partner invitation sent',
            'signup_link' => url('/partner/signup?ref=' . $token),
        ]);
    }
}
