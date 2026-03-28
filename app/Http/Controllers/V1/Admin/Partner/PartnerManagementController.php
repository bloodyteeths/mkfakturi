<?php

namespace App\Http\Controllers\V1\Admin\Partner;

use App\Http\Controllers\Controller;
use App\Models\AffiliateEvent;
use App\Models\Partner;
use App\Models\Payout;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Silber\Bouncer\BouncerFacade;

class PartnerManagementController extends Controller
{
    /**
     * Display a listing of all partners with statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        \Log::info('[Partners Index API] Request received', [
            'params' => $request->all(),
            'user_id' => auth()->id(),
        ]);

        $query = Partner::query()
            ->with(['user', 'companies'])
            ->withCount(['companies', 'activeCompanies']);

        \Log::info('[Partners Index API] Base query created');

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            });
            \Log::info('[Partners Index API] Search filter applied', ['search' => $search]);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
            \Log::info('[Partners Index API] Status filter applied', ['status' => $request->status]);
        }

        // Filter by KYC status
        if ($request->filled('kyc_status')) {
            $query->where('kyc_status', $request->kyc_status);
            \Log::info('[Partners Index API] KYC filter applied', ['kyc_status' => $request->kyc_status]);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        \Log::info('[Partners Index API] About to execute query', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        // Pagination
        $perPage = $request->get('per_page', 15);
        $partners = $query->paginate($perPage);

        \Log::info('[Partners Index API] Query executed', [
            'total' => $partners->total(),
            'count' => $partners->count(),
            'per_page' => $perPage,
            'current_page' => $partners->currentPage(),
        ]);

        // Add calculated fields
        $partners->getCollection()->transform(function ($partner) {
            $partner->total_earnings = $partner->getLifetimeEarnings();
            $partner->pending_payout = $partner->getUnpaidCommissionsTotal();
            $partner->is_partner_plus = $partner->isPartnerPlus();

            return $partner;
        });

        \Log::info('[Partners Index API] Returning response', [
            'total' => $partners->total(),
            'data_count' => count($partners->items()),
        ]);

        return response()->json($partners);
    }

    /**
     * Display the specified partner with detailed information
     *
     * @param  int  $partnerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($partnerId)
    {
        $partner = Partner::with([
            'user',
            'companies' => function ($query) {
                $query->withPivot(['is_primary', 'override_commission_rate', 'permissions', 'is_active']);
            },
            'commissions' => function ($query) {
                $query->latest()->limit(10);
            },
            'payouts' => function ($query) {
                $query->latest()->limit(10);
            },
            'affiliateLinks',
            'kycDocuments',
        ])->findOrFail($partnerId);

        // Add calculated fields
        $partner->total_earnings = $partner->getLifetimeEarnings();
        $partner->pending_payout = $partner->getUnpaidCommissionsTotal();
        $partner->is_partner_plus = $partner->isPartnerPlus();
        $partner->effective_commission_rate = $partner->getEffectiveCommissionRate();

        // Get monthly commission breakdown
        $partner->monthly_commissions = DB::table('affiliate_events')
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total')
            ->where('affiliate_partner_id', $partnerId)
            ->where('is_clawed_back', 0)
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();

        return response()->json($partner);
    }

    /**
     * Store a newly created partner
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:partners,email|unique:users,email',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'registration_number' => 'nullable|string|max:50',
            'bank_account' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'kyc_status' => ['nullable', Rule::in(['pending', 'under_review', 'approved', 'rejected'])],
            'notes' => 'nullable|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        DB::beginTransaction();
        try {
            // Create user account
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'partner',
            ]);

            // Create partner record
            $partnerData = array_merge(
                $validated,
                ['user_id' => $user->id]
            );
            unset($partnerData['password'], $partnerData['password_confirmation']);

            $partner = Partner::create($partnerData);

            DB::commit();

            return response()->json([
                'message' => 'Partner created successfully',
                'partner' => $partner->load('user'),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create partner',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified partner
     *
     * @param  int  $partnerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $partnerId)
    {
        $partner = Partner::findOrFail($partnerId);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('partners', 'email')->ignore($partnerId),
                Rule::unique('users', 'email')->ignore($partner->user_id),
            ],
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'tax_id' => 'nullable|string|max:50',
            'registration_number' => 'nullable|string|max:50',
            'bank_account' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:100',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'kyc_status' => ['nullable', Rule::in(['pending', 'under_review', 'approved', 'rejected'])],
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Update partner
            $partner->update($validated);

            // Update user if email or name changed
            if (isset($validated['email']) || isset($validated['name'])) {
                $partner->user->update([
                    'email' => $validated['email'] ?? $partner->user->email,
                    'name' => $validated['name'] ?? $partner->user->name,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Partner updated successfully',
                'partner' => $partner->fresh()->load('user'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update partner',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deactivate the specified partner (soft delete)
     *
     * @param  int  $partnerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($partnerId)
    {
        $partner = Partner::findOrFail($partnerId);

        DB::beginTransaction();
        try {
            // Deactivate instead of delete
            $partner->update(['is_active' => false]);

            // Optionally deactivate all company links
            DB::table('partner_company_links')
                ->where('partner_id', $partnerId)
                ->update(['is_active' => false]);

            DB::commit();

            return response()->json([
                'message' => 'Partner deactivated successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to deactivate partner',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get partner statistics dashboard
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        $stats = [
            'total_partners' => Partner::count(),
            'active_partners' => Partner::where('is_active', true)->count(),
            'partner_plus_count' => Partner::whereHas('user', function ($q) {
                $q->where('partner_subscription_tier', 'plus');
            })->count(),
            'pending_kyc' => Partner::where('kyc_status', 'pending')->count(),
            'total_commissions_paid' => DB::table('affiliate_events')
                ->whereNotNull('paid_at')
                ->sum('amount'),
            'total_commissions_unpaid' => DB::table('affiliate_events')
                ->whereNull('paid_at')
                ->sum('amount'),
        ];

        return response()->json($stats);
    }

    /**
     * Get available permissions structure (AC-13)
     * Used by Permission Editor component
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function permissions()
    {
        return response()->json([
            'grouped' => \App\Enums\PartnerPermission::getGroupedForApi(),
            'full_access' => [
                'value' => \App\Enums\PartnerPermission::FULL_ACCESS->value,
                'label' => \App\Enums\PartnerPermission::FULL_ACCESS->label(),
            ],
        ]);
    }

    /**
     * Get available companies for assignment (AC-09)
     * Returns companies not yet assigned to this partner
     *
     * @param  int  $partnerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function availableCompanies($partnerId)
    {
        $partner = Partner::findOrFail($partnerId);

        // Get IDs of already assigned companies
        $assignedCompanyIds = $partner->companies()->pluck('companies.id')->toArray();

        // Get all companies except those already assigned
        $companies = \App\Models\Company::whereNotIn('id', $assignedCompanyIds)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($companies);
    }

    /**
     * Assign company to partner with permissions (AC-09)
     *
     * @param  int  $partnerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignCompany(Request $request, $partnerId)
    {
        $partner = Partner::findOrFail($partnerId);

        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'is_primary' => 'boolean',
            'override_commission_rate' => 'nullable|numeric|min:0|max:100',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string',
        ]);

        DB::beginTransaction();
        try {
            // Check if already assigned
            $exists = $partner->companies()->where('companies.id', $validated['company_id'])->exists();
            if ($exists) {
                return response()->json([
                    'message' => 'Company is already assigned to this partner',
                ], 422);
            }

            // If setting as primary, unset other primary flags
            if ($validated['is_primary'] ?? false) {
                DB::table('partner_company_links')
                    ->where('partner_id', $partnerId)
                    ->update(['is_primary' => false]);
            }

            // Assign company with permissions
            $partner->companies()->attach($validated['company_id'], [
                'is_primary' => $validated['is_primary'] ?? false,
                'override_commission_rate' => $validated['override_commission_rate'],
                'permissions' => json_encode($validated['permissions']),
                'is_active' => true,
            ]);

            // Automatically assign 'admin' role to the partner user in this company scope
            // This ensures they have Bouncer permissions immediately
            BouncerFacade::scope()->to($validated['company_id']);
            BouncerFacade::assign('admin')->to($partner->user);

            // Clear permission cache
            $partner->clearPermissionCache($validated['company_id']);

            DB::commit();

            return response()->json([
                'message' => 'Company assigned successfully',
                'partner' => $partner->fresh()->load('companies'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to assign company',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update company assignment permissions (AC-09)
     *
     * @param  int  $partnerId
     * @param  int  $companyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateCompanyAssignment(Request $request, $partnerId, $companyId)
    {
        $partner = Partner::findOrFail($partnerId);

        $validated = $request->validate([
            'is_primary' => 'boolean',
            'override_commission_rate' => 'nullable|numeric|min:0|max:100',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string',
        ]);

        DB::beginTransaction();
        try {
            // Check if company is assigned
            $link = $partner->companies()->where('companies.id', $companyId)->first();
            if (! $link) {
                return response()->json([
                    'message' => 'Company is not assigned to this partner',
                ], 404);
            }

            // If setting as primary, unset other primary flags
            if ($validated['is_primary'] ?? false) {
                DB::table('partner_company_links')
                    ->where('partner_id', $partnerId)
                    ->where('company_id', '!=', $companyId)
                    ->update(['is_primary' => false]);
            }

            // Update assignment
            $partner->companies()->updateExistingPivot($companyId, [
                'is_primary' => $validated['is_primary'] ?? false,
                'override_commission_rate' => $validated['override_commission_rate'],
                'permissions' => json_encode($validated['permissions']),
            ]);

            // Clear permission cache
            $partner->clearPermissionCache($companyId);

            DB::commit();

            return response()->json([
                'message' => 'Company assignment updated successfully',
                'partner' => $partner->fresh()->load('companies'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update assignment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Unassign company from partner (AC-09)
     *
     * @param  int  $partnerId
     * @param  int  $companyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function unassignCompany($partnerId, $companyId)
    {
        $partner = Partner::findOrFail($partnerId);

        DB::beginTransaction();
        try {
            // Check if company is assigned
            $link = $partner->companies()->where('companies.id', $companyId)->first();
            if (! $link) {
                return response()->json([
                    'message' => 'Company is not assigned to this partner',
                ], 404);
            }

            // Detach company
            $partner->companies()->detach($companyId);

            // Clear permission cache
            $partner->clearPermissionCache($companyId);

            DB::commit();

            return response()->json([
                'message' => 'Company unassigned successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to unassign company',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk action on multiple partners
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|in:activate,deactivate,approve_kyc',
            'partner_ids' => 'required|array|min:1',
            'partner_ids.*' => 'exists:partners,id',
        ]);

        $partners = Partner::whereIn('id', $request->partner_ids);

        switch ($request->action) {
            case 'activate':
                $partners->update(['is_active' => true]);
                break;
            case 'deactivate':
                $partners->update(['is_active' => false]);
                DB::table('partner_company_links')
                    ->whereIn('partner_id', $request->partner_ids)
                    ->update(['is_active' => false]);
                break;
            case 'approve_kyc':
                $partners->update(['kyc_status' => 'approved']);
                break;
        }

        return response()->json([
            'message' => "Bulk {$request->action} completed for " . count($request->partner_ids) . ' partners',
            'affected' => count($request->partner_ids),
        ]);
    }

    /**
     * Export partners as CSV
     */
    public function export(Request $request)
    {
        $query = Partner::query();

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        if ($request->status === 'active') {
            $query->where('is_active', true);
        } elseif ($request->status === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->kyc_status) {
            $query->where('kyc_status', $request->kyc_status);
        }

        $partners = $query->withCount('companies')->get();

        $csv = "Name,Email,Phone,Company Name,Tax ID,Registration Number,Bank Account,Bank Name,Commission Rate,Status,KYC Status,Companies,Total Earnings,Created At\n";

        foreach ($partners as $partner) {
            $csv .= implode(',', [
                '"' . str_replace('"', '""', $partner->name) . '"',
                '"' . $partner->email . '"',
                '"' . ($partner->phone ?? '') . '"',
                '"' . str_replace('"', '""', $partner->company_name ?? '') . '"',
                '"' . ($partner->tax_id ?? '') . '"',
                '"' . ($partner->registration_number ?? '') . '"',
                '"' . ($partner->bank_account ?? '') . '"',
                '"' . str_replace('"', '""', $partner->bank_name ?? '') . '"',
                $partner->commission_rate ?? 20,
                $partner->is_active ? 'Active' : 'Inactive',
                $partner->kyc_status ?? 'pending',
                $partner->companies_count,
                $partner->getLifetimeEarnings(),
                $partner->created_at?->format('Y-m-d'),
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="partners-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    /**
     * Get KYC documents for a partner
     */
    public function kycDocuments($partnerId)
    {
        $partner = Partner::findOrFail($partnerId);
        $documents = $partner->kycDocuments()->latest()->get();

        return response()->json([
            'documents' => $documents->map(function ($doc) {
                $fileUrl = null;
                if ($doc->file_path) {
                    try {
                        $fileUrl = Storage::temporaryUrl($doc->file_path, now()->addMinutes(30));
                    } catch (\Exception $e) {
                        $fileUrl = null;
                    }
                }

                return [
                    'id' => $doc->id,
                    'document_type' => $doc->document_type,
                    'original_filename' => $doc->original_filename,
                    'status' => $doc->status,
                    'file_url' => $fileUrl,
                    'metadata' => $doc->metadata,
                    'created_at' => $doc->created_at->format('Y-m-d H:i'),
                ];
            }),
        ]);
    }

    /**
     * Update KYC document status
     */
    public function updateKycDocument(Request $request, $partnerId, $documentId)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $partner = Partner::findOrFail($partnerId);
        $document = $partner->kycDocuments()->findOrFail($documentId);
        $document->update(['status' => $request->status]);

        return response()->json(['message' => 'Document status updated', 'document' => $document]);
    }

    /**
     * Adjust commission for a partner (manual +/-)
     */
    public function adjustCommission(Request $request, $partnerId)
    {
        $partner = Partner::findOrFail($partnerId);

        $request->validate([
            'amount' => 'required|numeric',
            'description' => 'required|string|max:500',
        ]);

        $event = AffiliateEvent::create([
            'affiliate_partner_id' => $partner->id,
            'event_type' => 'adjustment',
            'amount' => $request->amount,
            'month_ref' => now()->format('Y-m'),
            'metadata' => [
                'description' => $request->description,
                'adjusted_by' => auth()->id(),
            ],
        ]);

        return response()->json(['message' => 'Commission adjusted', 'event' => $event]);
    }

    /**
     * Create manual payout for a partner
     */
    public function createManualPayout(Request $request, $partnerId)
    {
        $partner = Partner::findOrFail($partnerId);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:bank_transfer,paypal,stripe,wise,manual',
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:500',
        ]);

        $payout = Payout::create([
            'partner_id' => $partner->id,
            'amount' => $request->amount,
            'currency' => 'EUR',
            'status' => 'completed',
            'payout_date' => now(),
            'payment_method' => $request->payment_method,
            'payment_reference' => $request->payment_reference,
            'notes' => $request->notes,
            'processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);

        // Mark unpaid events as paid up to the payout amount
        $remaining = $request->amount;
        $unpaidEvents = $partner->affiliateEvents()
            ->whereNull('paid_at')
            ->where('is_clawed_back', false)
            ->oldest()
            ->get();

        foreach ($unpaidEvents as $event) {
            if ($remaining <= 0) {
                break;
            }
            $event->markAsPaid($payout);
            $remaining -= $event->amount;
        }

        return response()->json(['message' => 'Payout created', 'payout' => $payout]);
    }

    /**
     * Get current partner for a company (AC-16 helper)
     */
    public function getCompanyCurrentPartner(int $companyId)
    {
        $link = DB::table('partner_company_links')
            ->join('partners', 'partners.id', '=', 'partner_company_links.partner_id')
            ->join('users', 'users.id', '=', 'partners.user_id')
            ->where('partner_company_links.company_id', $companyId)
            ->where('partner_company_links.is_active', true)
            ->select([
                'partners.id',
                'partners.name',
                'partners.email',
                'users.name as user_name',
            ])
            ->first();

        if (! $link) {
            return response()->json(['message' => 'No active partner found'], 404);
        }

        return response()->json($link);
    }

    /**
     * Get upline partner for a partner (AC-16 helper)
     */
    public function getPartnerUpline(int $partnerId)
    {
        $referral = DB::table('partner_referrals')
            ->join('partners', 'partners.id', '=', 'partner_referrals.inviter_partner_id')
            ->join('users', 'users.id', '=', 'partners.user_id')
            ->where('partner_referrals.invitee_partner_id', $partnerId)
            ->where('partner_referrals.status', 'accepted')
            ->select([
                'partners.id',
                'partners.name',
                'partners.email',
                'users.name as user_name',
            ])
            ->first();

        if (! $referral) {
            return response()->json(['message' => 'No upline partner found'], 404);
        }

        return response()->json($referral);
    }
}

