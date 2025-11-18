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
            'reason' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            // Get old partner link to preserve permissions
            $oldLink = DB::table('partner_company_links')
                ->where('company_id', $validated['company_id'])
                ->where('partner_id', $validated['old_partner_id'])
                ->first();

            // Update affiliate_events if not preserving old commissions
            if (!($validated['preserve_commissions'] ?? false)) {
                DB::table('affiliate_events')
                    ->where('company_id', $validated['company_id'])
                    ->where('affiliate_partner_id', $validated['old_partner_id'])
                    ->whereNull('paid_at')
                    ->update(['affiliate_partner_id' => $validated['new_partner_id']]);
            }

            // Update partner_company_links table
            if ($oldLink) {
                DB::table('partner_company_links')
                    ->where('company_id', $validated['company_id'])
                    ->where('partner_id', $validated['old_partner_id'])
                    ->delete();

                DB::table('partner_company_links')->insert([
                    'partner_id' => $validated['new_partner_id'],
                    'company_id' => $validated['company_id'],
                    'is_primary' => $oldLink->is_primary ?? false,
                    'override_commission_rate' => $oldLink->override_commission_rate,
                    'permissions' => $oldLink->permissions,
                    'is_active' => true,
                    'invitation_status' => 'accepted',
                    'accepted_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Create audit log entry
            DB::table('entity_reassignment_log')->insert([
                'entity_type' => 'company_partner',
                'entity_id' => $validated['company_id'],
                'old_partner_id' => $validated['old_partner_id'],
                'new_partner_id' => $validated['new_partner_id'],
                'preserved_commissions' => $validated['preserve_commissions'] ?? false,
                'reason' => $validated['reason'],
                'performed_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

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
