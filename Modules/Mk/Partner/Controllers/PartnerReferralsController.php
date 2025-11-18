<?php

namespace Modules\Mk\Partner\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AffiliateLink;
use App\Models\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PartnerReferralsController extends Controller
{
    /**
     * Get partner referral data including active link and statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->firstOrFail();

        // Get active affiliate link
        $activeLink = AffiliateLink::where('partner_id', $partner->id)
            ->where('is_active', true)
            ->first();

        if ($activeLink) {
            $activeLink->url = $activeLink->getUrl();
        }

        // Calculate statistics
        $totalClicks = AffiliateLink::where('partner_id', $partner->id)
            ->sum('clicks');

        $signups = AffiliateLink::where('partner_id', $partner->id)
            ->sum('conversions');

        // Count active subscriptions from referred companies
        $activeSubscriptions = $partner->activeCompanies()
            ->whereHas('subscription', function ($query) {
                $query->where('status', 'active');
            })
            ->count();

        return response()->json([
            'activeLink' => $activeLink,
            'statistics' => [
                'totalClicks' => $totalClicks,
                'signups' => $signups,
                'activeSubscriptions' => $activeSubscriptions,
            ],
        ]);
    }

    /**
     * Generate a new referral link for the partner
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'custom_code' => 'nullable|string|max:50|unique:affiliate_links,code',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid custom code or code already exists',
                'details' => $validator->errors(),
            ], 422);
        }

        $user = Auth::user();
        $partner = Partner::where('user_id', $user->id)->firstOrFail();

        // Check if partner already has an active link
        $existingLink = AffiliateLink::where('partner_id', $partner->id)
            ->where('is_active', true)
            ->first();

        if ($existingLink) {
            $existingLink->url = $existingLink->getUrl();

            return response()->json([
                'link' => $existingLink,
                'message' => 'Using existing active link',
            ]);
        }

        // Generate unique code
        $code = $request->custom_code
            ? strtoupper($request->custom_code)
            : AffiliateLink::generateUniqueCode($partner);

        // Create new affiliate link
        $link = AffiliateLink::create([
            'partner_id' => $partner->id,
            'code' => $code,
            'target' => 'company',
            'is_active' => true,
            'clicks' => 0,
            'conversions' => 0,
        ]);

        $link->url = $link->getUrl();

        return response()->json([
            'link' => $link,
            'message' => 'Referral link generated successfully',
        ], 201);
    }
}

// CLAUDE-CHECKPOINT
