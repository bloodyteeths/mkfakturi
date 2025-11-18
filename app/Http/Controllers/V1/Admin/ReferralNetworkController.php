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
        $page = max(1, (int) $request->input('page', 1));
        $limit = min(500, max(10, (int) $request->input('limit', 100)));
        $type = $request->input('type', 'all'); // partners|companies|all

        $nodes = [];
        $edges = [];
        $totalNodes = 0;
        $totalEdges = 0;

        // Calculate offset
        $offset = ($page - 1) * $limit;

        // Partner nodes
        if (($type === 'all' || $type === 'partners') && $includePartners) {
            $partnersQuery = Partner::query();
            if (!$includeInactive) {
                $partnersQuery->where('is_active', true);
            }

            $totalPartners = $partnersQuery->count();
            $partners = $partnersQuery
                ->select(['id', 'name', 'email', 'is_active'])
                ->skip($offset)
                ->take($limit)
                ->get();

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

            $totalNodes += $totalPartners;
        }

        // Company nodes
        if (($type === 'all' || $type === 'companies') && $includeCompanies) {
            $companiesQuery = Company::query();
            $totalCompanies = $companiesQuery->count();

            $companies = $companiesQuery
                ->select(['id', 'name'])
                ->skip($offset)
                ->take($limit)
                ->get();

            foreach ($companies as $company) {
                $nodes[] = [
                    'id' => 'company_' . $company->id,
                    'label' => $company->name,
                    'type' => 'company',
                    'active' => true,
                ];
            }

            $totalNodes += $totalCompanies;
        }

        // Partner→Company edges (limit to nodes in current page)
        $nodeIds = collect($nodes)->pluck('id')->map(function ($id) {
            return (int) str_replace(['partner_', 'company_'], '', $id);
        });

        $partnerIds = collect($nodes)->where('type', 'partner')->pluck('id')->map(fn($id) => (int) str_replace('partner_', '', $id));
        $companyIds = collect($nodes)->where('type', 'company')->pluck('id')->map(fn($id) => (int) str_replace('company_', '', $id));

        if ($partnerIds->isNotEmpty() || $companyIds->isNotEmpty()) {
            $linksQuery = DB::table('partner_company_links')->where('is_active', true);

            if ($partnerIds->isNotEmpty() && $companyIds->isNotEmpty()) {
                $linksQuery->where(function ($q) use ($partnerIds, $companyIds) {
                    $q->whereIn('partner_id', $partnerIds)->orWhereIn('company_id', $companyIds);
                });
            } elseif ($partnerIds->isNotEmpty()) {
                $linksQuery->whereIn('partner_id', $partnerIds);
            } elseif ($companyIds->isNotEmpty()) {
                $linksQuery->whereIn('company_id', $companyIds);
            }

            $partnerCompanyLinks = $linksQuery->get(['partner_id', 'company_id']);

            foreach ($partnerCompanyLinks as $link) {
                $edges[] = [
                    'from' => 'partner_' . $link->partner_id,
                    'to' => 'company_' . $link->company_id,
                    'type' => 'manages',
                ];
            }
        }

        // Partner→Partner referrals
        if ($partnerIds->isNotEmpty()) {
            $partnerReferrals = DB::table('partner_referrals')
                ->where('status', 'accepted')
                ->where(function ($q) use ($partnerIds) {
                    $q->whereIn('inviter_partner_id', $partnerIds)
                      ->orWhereIn('invitee_partner_id', $partnerIds);
                })
                ->get(['inviter_partner_id', 'invitee_partner_id']);

            foreach ($partnerReferrals as $ref) {
                $edges[] = [
                    'from' => 'partner_' . $ref->inviter_partner_id,
                    'to' => 'partner_' . $ref->invitee_partner_id,
                    'type' => 'referred_partner',
                ];
            }
        }

        // Company→Company referrals
        if ($companyIds->isNotEmpty()) {
            $companyReferrals = DB::table('company_referrals')
                ->where('status', 'accepted')
                ->where(function ($q) use ($companyIds) {
                    $q->whereIn('inviter_company_id', $companyIds)
                      ->orWhereIn('invitee_company_id', $companyIds);
                })
                ->get(['inviter_company_id', 'invitee_company_id']);

            foreach ($companyReferrals as $ref) {
                $edges[] = [
                    'from' => 'company_' . $ref->inviter_company_id,
                    'to' => 'company_' . $ref->invitee_company_id,
                    'type' => 'referred_company',
                ];
            }
        }

        // Count total edges
        $totalEdges += DB::table('partner_company_links')->where('is_active', true)->count();
        $totalEdges += DB::table('partner_referrals')->where('status', 'accepted')->count();
        $totalEdges += DB::table('company_referrals')->where('status', 'accepted')->count();

        return response()->json([
            'nodes' => $nodes,
            'edges' => $edges,
            'meta' => [
                'total_nodes' => $totalNodes,
                'total_edges' => $totalEdges,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalNodes / $limit),
            ],
        ]);
    }
}
