<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;

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

        if (! $partner) {
            return response()->json(['message' => 'Partner not found'], 404);
        }

        // Check for existing link
        $existingLink = DB::table('partner_company_links')
            ->where('partner_id', $partner->id)
            ->where('company_id', $validated['company_id'])
            ->first();

        // If already linked and accepted, update permissions instead of creating new link
        if ($existingLink && $existingLink->invitation_status === 'accepted') {
            DB::table('partner_company_links')
                ->where('id', $existingLink->id)
                ->update([
                    'permissions' => json_encode($validated['permissions']),
                    'updated_at' => now(),
                ]);

            return response()->json(['message' => 'Partner permissions updated']);
        }

        // If pending invitation exists, update it
        if ($existingLink && $existingLink->invitation_status === 'pending') {
            DB::table('partner_company_links')
                ->where('id', $existingLink->id)
                ->update([
                    'permissions' => json_encode($validated['permissions']),
                    'invited_at' => now(),
                    'updated_at' => now(),
                ]);

            return response()->json(['message' => 'Invitation updated']);
        }

        // If declined, delete old link and create new one
        if ($existingLink && $existingLink->invitation_status === 'declined') {
            DB::table('partner_company_links')->where('id', $existingLink->id)->delete();
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

        if (! $link || $link->invitation_status !== 'pending') {
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

        if (! $partner) {
            return response()->json(['message' => 'Not a partner'], 403);
        }

        $affiliateLink = \App\Models\AffiliateLink::firstOrCreate(
            ['partner_id' => $partner->id],
            ['code' => Str::random(10), 'is_active' => true]
        );

        return response()->json([
            'link' => url('/signup?ref='.$affiliateLink->code),
            'qr_code_url' => url('/api/qr?data='.urlencode(url('/signup?ref='.$affiliateLink->code))),
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
            'signup_link' => url('/signup?company_ref='.$token),
        ]);
    }

    /**
     * Partner invites Partner (AC-15)
     */
    public function partnerInvitesPartner(Request $request)
    {
        $user = auth()->user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (! $partner) {
            return response()->json(['message' => 'Not a partner'], 403);
        }

        $validated = $request->validate([
            'invitee_email' => 'nullable|email',
        ]);

        // Defensive: ensure required columns exist before inserting
        if (! Schema::hasTable('partner_referrals') || ! Schema::hasColumn('partner_referrals', 'referral_token')) {
            return response()->json([
                'message' => 'Partner referrals are not available. Please run the partner_referrals migration.',
            ], 500);
        }

        $token = Str::random(32);

        $payload = [
            'inviter_partner_id' => $partner->id,
            'invitee_partner_id' => null,
            'referral_token' => $token,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // Backward compatibility: only include invitee_email if column exists (older DBs may not have it)
        if (Schema::hasColumn('partner_referrals', 'invitee_email')) {
            $payload['invitee_email'] = $validated['invitee_email'] ?? null;
        }

        try {
            DB::table('partner_referrals')->insert($payload);
        } catch (QueryException $e) {
            Log::error('Partner invite insert failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Partner invite could not be created. Please ensure partner_referrals schema is up to date.',
            ], 500);
        }

        return response()->json([
            'message' => 'Partner invitation sent',
            'signup_link' => url('/partner/signup?ref='.$token),
            'link' => url('/partner/signup?ref='.$token),
            'qr_code_url' => url('/api/qr?data='.urlencode(url('/partner/signup?ref='.$token))),
        ]);
    }

    /**
     * Get pending invitations for partner (AC-11)
     */
    public function getPendingForPartner(Request $request)
    {
        $user = auth()->user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (! $partner) {
            return response()->json(['message' => 'Not a partner'], 403);
        }

        $invitations = DB::table('partner_company_links')
            ->join('companies', 'companies.id', '=', 'partner_company_links.company_id')
            ->join('users', 'users.id', '=', 'partner_company_links.created_by')
            ->where('partner_company_links.partner_id', $partner->id)
            ->where('partner_company_links.invitation_status', 'pending')
            ->select([
                'partner_company_links.id',
                'companies.id as company_id',
                'companies.name as company_name',
                'users.name as inviter_name',
                'partner_company_links.permissions',
                'partner_company_links.invited_at',
            ])
            ->get();

        return response()->json($invitations);
    }

    /**
     * Get pending partner invitations for company (AC-11)
     */
    public function getPending(Request $request)
    {
        $companyId = $request->query('company_id');

        if (! $companyId) {
            return response()->json(['message' => 'company_id required'], 422);
        }

        $invitations = DB::table('partner_company_links')
            ->join('partners', 'partners.id', '=', 'partner_company_links.partner_id')
            ->where('partner_company_links.company_id', $companyId)
            ->where('partner_company_links.invitation_status', 'pending')
            ->select([
                'partner_company_links.id',
                'partners.id as partner_id',
                'partners.email as partner_email',
                'partner_company_links.invited_at',
            ])
            ->get();

        return response()->json($invitations);
    }

    /**
     * Get pending company referrals (AC-14)
     */
    public function getPendingCompany(Request $request)
    {
        $companyId = $request->query('company_id');

        if (! $companyId) {
            return response()->json(['message' => 'company_id required'], 422);
        }

        $invitations = DB::table('company_referrals')
            ->where('inviter_company_id', $companyId)
            ->where('status', 'pending')
            ->select([
                'id',
                'invitee_email',
                'invited_at',
            ])
            ->get();

        return response()->json($invitations);
    }

    /**
     * Send email invitation for company signup (AC-12)
     */
    public function sendEmailInvite(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'link' => 'required|url',
        ]);

        $user = auth()->user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (! $partner) {
            return response()->json(['message' => 'Not a partner'], 403);
        }

        // Get partner name for email
        $partnerName = $partner->name ?? $partner->email;

        // Send email invitation
        \Mail::to($validated['email'])->send(
            new \App\Mail\CompanyInvitationMail(
                $validated['email'],
                $partnerName,
                $validated['link']
            )
        );

        return response()->json(['message' => 'Email invitation sent']);
    }

    /**
     * Send email invitation for partner signup (AC-15)
     */
    public function sendPartnerEmailInvite(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'link' => 'required|url',
        ]);

        $user = auth()->user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (! $partner) {
            return response()->json(['message' => 'Not a partner'], 403);
        }

        // Get partner name for email
        $inviterPartnerName = $partner->name ?? $partner->email;

        // Send email invitation
        \Mail::to($validated['email'])->send(
            new \App\Mail\PartnerReferralMail(
                $validated['email'],
                $inviterPartnerName,
                $validated['link']
            )
        );

        return response()->json(['message' => 'Email invitation sent']);
    }

    /**
     * Partner unlinks themselves from a company (removes access)
     */
    public function unlinkFromCompany(Request $request, $companyId)
    {
        $user = auth()->user();
        $partner = Partner::where('user_id', $user->id)->first();

        if (! $partner) {
            return response()->json(['message' => 'Not a partner'], 403);
        }

        $link = DB::table('partner_company_links')
            ->where('partner_id', $partner->id)
            ->where('company_id', $companyId)
            ->first();

        if (! $link) {
            return response()->json(['message' => 'Company link not found'], 404);
        }

        // Delete the link
        DB::table('partner_company_links')
            ->where('id', $link->id)
            ->delete();

        return response()->json(['message' => 'Successfully unlinked from company']);
    }
}

// CLAUDE-CHECKPOINT
