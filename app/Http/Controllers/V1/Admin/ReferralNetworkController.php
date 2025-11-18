<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralNetworkController extends Controller
{
    /**
     * Get referral network graph data (AC-17)
     * Returns nodes (partners + companies) and edges (referral relationships)
     */
    public function getNetworkGraph(Request $request)
    {
        $includePartners = $request->input('include_partners', true);
        $includeCompanies = $request->input('include_companies', true);
        $includeInactive = $request->input('include_inactive', false);

        $nodes = [];
        $edges = [];

        // Partner nodes
        if ($includePartners) {
            $partnersQuery = Partner::query();
            if (!$includeInactive) {
                $partnersQuery->where('is_active', true);
            }

            $partners = $partnersQuery->get(['id', 'name', 'email', 'is_active']);
            foreach ($partners as $partner) {
                $nodes[] = [
                    'id' => 'partner_' . $partner->id,
                    'label' => $partner->name,
                    'type' => 'partner',
                    'email' => $partner->email,
                    'active' => $partner->is_active,
                    'total_clients' => $partner->activeCompanies()->count(),
                    'tier' => $partner->isPartnerPlus() ? 'Plus' : 'Standard',
                ];
            }
        }

        // Company nodes
        if ($includeCompanies) {
            $companies = Company::get(['id', 'name']);
            foreach ($companies as $company) {
                $nodes[] = [
                    'id' => 'company_' . $company->id,
                    'label' => $company->name,
                    'type' => 'company',
                    'active' => true,
                ];
            }
        }

        // Partner→Company edges
        $partnerCompanyLinks = DB::table('partner_company_links')
            ->where('is_active', true)
            ->get(['partner_id', 'company_id']);

        foreach ($partnerCompanyLinks as $link) {
            $edges[] = [
                'from' => 'partner_' . $link->partner_id,
                'to' => 'company_' . $link->company_id,
                'type' => 'manages',
            ];
        }

        // Partner→Partner referrals
        $partnerReferrals = DB::table('partner_referrals')
            ->where('status', 'accepted')
            ->get(['inviter_partner_id', 'invitee_partner_id']);

        foreach ($partnerReferrals as $ref) {
            $edges[] = [
                'from' => 'partner_' . $ref->inviter_partner_id,
                'to' => 'partner_' . $ref->invitee_partner_id,
                'type' => 'referred_partner',
            ];
        }

        // Company→Company referrals
        $companyReferrals = DB::table('company_referrals')
            ->where('status', 'accepted')
            ->get(['inviter_company_id', 'invitee_company_id']);

        foreach ($companyReferrals as $ref) {
            $edges[] = [
                'from' => 'company_' . $ref->inviter_company_id,
                'to' => 'company_' . $ref->invitee_company_id,
                'type' => 'referred_company',
            ];
        }

        return response()->json([
            'nodes' => $nodes,
            'edges' => $edges,
        ]);
    }
}
