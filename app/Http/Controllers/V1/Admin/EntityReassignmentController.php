<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EntityReassignmentController extends Controller
{
    /**
     * Reassign company to different partner (AC-16)
     */
    public function reassignCompanyPartner(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'old_partner_id' => 'nullable|exists:partners,id',
            'new_partner_id' => 'required|exists:partners,id',
            'preserve_commissions' => 'boolean',
        ]);

        DB::beginTransaction();
        try {
            // Update affiliate_events if not preserving old commissions
            if (!($validated['preserve_commissions'] ?? false)) {
                DB::table('affiliate_events')
                    ->where('company_id', $validated['company_id'])
                    ->where('affiliate_partner_id', $validated['old_partner_id'])
                    ->where('is_paid', false)
                    ->update(['affiliate_partner_id' => $validated['new_partner_id']]);
            }

            // Update future affiliate links
            DB::table('affiliate_links')
                ->where('partner_id', $validated['old_partner_id'])
                ->update(['partner_id' => $validated['new_partner_id']]);

            DB::commit();
            return response()->json(['message' => 'Company reassigned successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Reassignment failed', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get reassignment audit log (AC-16)
     */
    public function getReassignmentLog(Request $request)
    {
        $log = DB::table('entity_reassignment_log')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($log);
    }
}
