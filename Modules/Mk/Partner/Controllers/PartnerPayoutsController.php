<?php

namespace Modules\Mk\Partner\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AffiliateEvent;
use App\Models\Partner;
use App\Models\Payout;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PartnerPayoutsController extends Controller
{
    /**
     * Get partner from authenticated request.
     * For super admin, returns a "fake" partner object to allow access.
     *
     * @return Partner|null
     */
    protected function getPartnerFromRequest(): ?Partner
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        // Super admin gets a fake partner to pass validation
        if ($user->role === 'super admin') {
            $fakePartner = new Partner();
            $fakePartner->id = 0;
            $fakePartner->user_id = $user->id;
            $fakePartner->name = 'Super Admin';
            $fakePartner->email = $user->email;
            $fakePartner->is_super_admin = true;
            $fakePartner->commission_rate = 0;

            return $fakePartner;
        }

        return Partner::where('user_id', $user->id)->first();
    }

    /**
     * Get paginated list of payouts for the partner
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $partner = $this->getPartnerFromRequest();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        // Super admin gets empty payouts
        if ($partner->is_super_admin ?? false) {
            return response()->json([
                'data' => [],
                'current_page' => 1,
                'per_page' => 20,
                'total' => 0,
                'last_page' => 1,
                'from' => null,
                'to' => null,
                'summary' => [
                    'totalPaid' => 0,
                    'pending' => 0,
                    'thisMonth' => 0,
                    'nextPayout' => 0,
                    'nextPayoutDate' => null,
                ],
                'is_super_admin' => true,
            ]);
        }

        // Build query
        $query = Payout::where('partner_id', $partner->id)
            ->orderBy('payout_date', 'desc');

        // Apply status filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Get paginated results
        $perPage = $request->input('per_page', 20);
        $payouts = $query->paginate($perPage);

        // Calculate summary
        $totalPaid = Payout::forPartner($partner->id)
            ->completed()
            ->sum('amount');

        $pending = Payout::forPartner($partner->id)
            ->pending()
            ->sum('amount');

        $currentMonth = Carbon::now()->format('Y-m');
        $thisMonth = AffiliateEvent::forPartner($partner->id)
            ->forMonth($currentMonth)
            ->where('is_clawed_back', false)
            ->sum('amount');

        // Get next payout
        $nextPayout = Payout::forPartner($partner->id)
            ->where('status', 'pending')
            ->orderBy('payout_date', 'asc')
            ->first();

        return response()->json([
            'data' => $payouts->items(),
            'current_page' => $payouts->currentPage(),
            'per_page' => $payouts->perPage(),
            'total' => $payouts->total(),
            'last_page' => $payouts->lastPage(),
            'from' => $payouts->firstItem(),
            'to' => $payouts->lastItem(),
            'summary' => [
                'totalPaid' => $totalPaid,
                'pending' => $pending,
                'thisMonth' => $thisMonth,
                'nextPayout' => $nextPayout ? $nextPayout->amount : 0,
                'nextPayoutDate' => $nextPayout ? $nextPayout->payout_date->toISOString() : null,
            ],
        ]);
    }

    /**
     * Get partner's bank details
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBankDetails(Request $request)
    {
        $partner = $this->getPartnerFromRequest();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        // Super admin gets empty bank details
        if ($partner->is_super_admin ?? false) {
            return response()->json([
                'account_holder' => null,
                'bank_name' => null,
                'account_number' => null,
                'bank_code' => null,
                'is_super_admin' => true,
            ]);
        }

        return response()->json([
            'account_holder' => $partner->name,
            'bank_name' => $partner->bank_name,
            'account_number' => $partner->bank_account,
            'bank_code' => null, // Add bank_code field to partners table if needed
        ]);
    }

    /**
     * Update partner's bank details
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateBankDetails(Request $request)
    {
        // Super admin cannot update bank details
        $user = Auth::user();
        if ($user->role === 'super admin') {
            return response()->json([
                'error' => 'Super admin cannot update bank details',
                'is_super_admin' => true,
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'account_holder' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'bank_code' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $validator->errors(),
            ], 422);
        }

        $partner = $this->getPartnerFromRequest();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        // Update partner bank details
        $partner->update([
            'name' => $request->account_holder,
            'bank_name' => $request->bank_name,
            'bank_account' => $request->account_number,
            // 'bank_code' => $request->bank_code, // Add if column exists
        ]);

        return response()->json([
            'account_holder' => $partner->name,
            'bank_name' => $partner->bank_name,
            'account_number' => $partner->bank_account,
            'bank_code' => $request->bank_code,
            'message' => 'Bank details updated successfully',
        ]);
    }

    /**
     * Download payout receipt as PDF
     *
     * @param  int  $payoutId
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function downloadReceipt(Request $request, $payoutId)
    {
        $partner = $this->getPartnerFromRequest();

        if (! $partner) {
            return response()->json(['error' => 'Partner account not found'], 403);
        }

        // Super admin cannot download partner receipts (no partner context)
        if ($partner->is_super_admin ?? false) {
            return response()->json([
                'error' => 'Super admin cannot download partner receipts',
                'is_super_admin' => true,
            ], 400);
        }

        $payout = Payout::where('id', $payoutId)
            ->where('partner_id', $partner->id)
            ->where('status', 'completed')
            ->first();

        if (! $payout) {
            return response()->json(['error' => 'Payout not found or not completed'], 404);
        }

        // Get events included in this payout
        $events = AffiliateEvent::with('company')
            ->where('payout_id', $payout->id)
            ->get();

        // Generate PDF
        $data = [
            'payout' => $payout,
            'partner' => $partner,
            'events' => $events,
            'generatedDate' => Carbon::now(),
        ];

        $pdf = Pdf::loadView('partner.payout-receipt', $data);

        return $pdf->download("payout-receipt-{$payoutId}.pdf");
    }
}

// CLAUDE-CHECKPOINT
