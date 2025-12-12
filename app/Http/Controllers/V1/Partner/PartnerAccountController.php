<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use League\Csv\Reader;

/**
 * Partner Account Controller
 *
 * Manages Chart of Accounts for companies linked to the partner.
 * Partners can only access accounts for companies they manage via partner_company_links.
 */
class PartnerAccountController extends Controller
{
    /**
     * List all accounts for partner's linked companies.
     */
    public function index(Request $request): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        $companyId = $request->header('company');

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Company header required',
            ], 400);
        }

        // Verify partner has access to this company
        if (!$this->hasCompanyAccess($partner, $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $query = Account::where('company_id', $companyId)
            ->with(['parent:id,code,name', 'children:id,parent_id,code,name'])
            ->orderBy('code');

        // Filter by type
        if ($request->type) {
            $query->where('type', $request->type);
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter root accounts only
        if ($request->boolean('roots_only')) {
            $query->whereNull('parent_id');
        }

        $accounts = $query->get();

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    /**
     * Get accounts as tree structure.
     */
    public function tree(Request $request): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        $companyId = $request->header('company');

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Company header required',
            ], 400);
        }

        // Verify partner has access to this company
        if (!$this->hasCompanyAccess($partner, $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $accounts = Account::where('company_id', $companyId)
            ->whereNull('parent_id')
            ->with('descendants')
            ->orderBy('code')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $accounts,
        ]);
    }

    /**
     * Get a single account.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        $companyId = $request->header('company');

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Company header required',
            ], 400);
        }

        // Verify partner has access to this company
        if (!$this->hasCompanyAccess($partner, $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $account = Account::where('company_id', $companyId)
            ->with(['parent', 'children'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $account,
        ]);
    }

    /**
     * Create a new account.
     */
    public function store(Request $request): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        $companyId = $request->header('company');

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Company header required',
            ], 400);
        }

        // Verify partner has access to this company
        if (!$this->hasCompanyAccess($partner, $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id' => 'nullable|integer|exists:accounts,id',
            'description' => 'nullable|string|max:1000',
            'meta' => 'nullable|array',
        ]);

        // Check for duplicate code
        if (Account::where('company_id', $companyId)
            ->where('code', $request->code)
            ->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'An account with this code already exists.',
            ], 422);
        }

        // Validate parent belongs to same company
        if ($request->parent_id) {
            $parent = Account::where('company_id', $companyId)
                ->find($request->parent_id);

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent account not found.',
                ], 422);
            }
        }

        $account = Account::create([
            'company_id' => $companyId,
            'code' => $request->code,
            'name' => $request->name,
            'type' => $request->type,
            'parent_id' => $request->parent_id,
            'description' => $request->description,
            'meta' => $request->meta,
            'is_active' => true,
            'system_defined' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully.',
            'data' => $account->load('parent'),
        ], 201);
    }

    /**
     * Update an account.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        $companyId = $request->header('company');

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Company header required',
            ], 400);
        }

        // Verify partner has access to this company
        if (!$this->hasCompanyAccess($partner, $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $account = Account::where('company_id', $companyId)
            ->findOrFail($id);

        $request->validate([
            'code' => 'sometimes|required|string|max:20',
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:asset,liability,equity,revenue,expense',
            'parent_id' => 'nullable|integer',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
            'meta' => 'nullable|array',
        ]);

        // Check for duplicate code (if changing)
        if ($request->has('code') && $request->code !== $account->code) {
            if (Account::where('company_id', $companyId)
                ->where('code', $request->code)
                ->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An account with this code already exists.',
                ], 422);
            }
        }

        // Validate parent (can't be self or descendant)
        if ($request->has('parent_id') && $request->parent_id) {
            if ($request->parent_id === $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account cannot be its own parent.',
                ], 422);
            }

            $parent = Account::where('company_id', $companyId)
                ->find($request->parent_id);

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parent account not found.',
                ], 422);
            }
        }

        $account->update($request->only([
            'code',
            'name',
            'type',
            'parent_id',
            'description',
            'is_active',
            'meta',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Account updated successfully.',
            'data' => $account->fresh()->load('parent'),
        ]);
    }

    /**
     * Delete an account.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        $companyId = $request->header('company');

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Company header required',
            ], 400);
        }

        // Verify partner has access to this company
        if (!$this->hasCompanyAccess($partner, $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $account = Account::where('company_id', $companyId)
            ->findOrFail($id);

        if (!$account->canDelete()) {
            return response()->json([
                'success' => false,
                'message' => 'This account cannot be deleted. It may be system-defined, have child accounts, or be used in mappings.',
            ], 422);
        }

        $account->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully.',
        ]);
    }

    /**
     * Import accounts from CSV.
     */
    public function import(Request $request): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        $companyId = $request->header('company');

        if (!$companyId) {
            return response()->json([
                'success' => false,
                'message' => 'Company header required',
            ], 400);
        }

        // Verify partner has access to this company
        if (!$this->hasCompanyAccess($partner, $companyId)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120', // 5MB max
        ]);

        try {
            $csv = Reader::createFromPath($request->file('file')->getRealPath(), 'r');
            $csv->setHeaderOffset(0);

            $records = $csv->getRecords();
            $imported = 0;
            $errors = [];
            $skipped = 0;

            DB::beginTransaction();

            foreach ($records as $offset => $record) {
                // Expected CSV columns: code, name, type, parent_code, description
                $validator = Validator::make($record, [
                    'code' => 'required|string|max:20',
                    'name' => 'required|string|max:255',
                    'type' => 'required|in:asset,liability,equity,revenue,expense',
                    'parent_code' => 'nullable|string|max:20',
                    'description' => 'nullable|string|max:1000',
                ]);

                if ($validator->fails()) {
                    $errors[] = [
                        'row' => $offset + 2, // +2 for header and 0-index
                        'errors' => $validator->errors()->all(),
                    ];
                    continue;
                }

                // Check if account already exists
                if (Account::where('company_id', $companyId)
                    ->where('code', $record['code'])
                    ->exists()) {
                    $skipped++;
                    continue;
                }

                // Find parent if specified
                $parentId = null;
                if (!empty($record['parent_code'])) {
                    $parent = Account::where('company_id', $companyId)
                        ->where('code', $record['parent_code'])
                        ->first();

                    if ($parent) {
                        $parentId = $parent->id;
                    } else {
                        $errors[] = [
                            'row' => $offset + 2,
                            'errors' => ["Parent account with code '{$record['parent_code']}' not found"],
                        ];
                        continue;
                    }
                }

                Account::create([
                    'company_id' => $companyId,
                    'code' => $record['code'],
                    'name' => $record['name'],
                    'type' => $record['type'],
                    'parent_id' => $parentId,
                    'description' => $record['description'] ?? null,
                    'is_active' => true,
                    'system_defined' => false,
                ]);

                $imported++;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Import completed: {$imported} accounts imported, {$skipped} skipped",
                'data' => [
                    'imported' => $imported,
                    'skipped' => $skipped,
                    'errors' => $errors,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get partner from authenticated request.
     */
    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        return Partner::where('user_id', $user->id)->first();
    }

    /**
     * Check if partner has access to a company.
     */
    protected function hasCompanyAccess(Partner $partner, int $companyId): bool
    {
        return $partner->companies()
            ->where('companies.id', $companyId)
            ->where('partner_company_links.is_active', true)
            ->exists();
    }
}

// CLAUDE-CHECKPOINT
