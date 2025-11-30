<?php

namespace App\Http\Controllers\V1\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountMapping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Account Controller
 *
 * Manages Chart of Accounts and Account Mappings.
 * Part of Phase 4: Accountant CoA & Export.
 */
class AccountController extends Controller
{
    /**
     * List all accounts for the company.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('view-financial-reports');

        $companyId = $request->header('company');

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
        $this->authorize('view-financial-reports');

        $companyId = $request->header('company');

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
        $this->authorize('view-financial-reports');

        $companyId = $request->header('company');

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
        $this->authorize('manage-closings');

        $request->validate([
            'code' => 'required|string|max:20',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'parent_id' => 'nullable|integer|exists:accounts,id',
            'description' => 'nullable|string|max:1000',
            'meta' => 'nullable|array',
        ]);

        $companyId = $request->header('company');

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

            if (! $parent) {
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
        $this->authorize('manage-closings');

        $companyId = $request->header('company');

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

            if (! $parent) {
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
        $this->authorize('manage-closings');

        $companyId = $request->header('company');

        $account = Account::where('company_id', $companyId)
            ->findOrFail($id);

        if (! $account->canDelete()) {
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
     * List all account mappings for the company.
     */
    public function indexMappings(Request $request): JsonResponse
    {
        $this->authorize('view-financial-reports');

        $companyId = $request->header('company');

        $query = AccountMapping::where('company_id', $companyId)
            ->with(['debitAccount:id,code,name', 'creditAccount:id,code,name']);

        if ($request->entity_type) {
            $query->where('entity_type', $request->entity_type);
        }

        $mappings = $query->get();

        return response()->json([
            'success' => true,
            'data' => $mappings,
        ]);
    }

    /**
     * Create or update an account mapping.
     */
    public function upsertMapping(Request $request): JsonResponse
    {
        $this->authorize('manage-closings');

        $request->validate([
            'entity_type' => 'required|string|max:50',
            'entity_id' => 'nullable|integer',
            'debit_account_id' => 'nullable|integer|exists:accounts,id',
            'credit_account_id' => 'nullable|integer|exists:accounts,id',
            'transaction_type' => 'nullable|string|max:50',
            'meta' => 'nullable|array',
        ]);

        $companyId = $request->header('company');

        // Validate accounts belong to company
        if ($request->debit_account_id) {
            $debitAccount = Account::where('company_id', $companyId)
                ->find($request->debit_account_id);
            if (! $debitAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debit account not found.',
                ], 422);
            }
        }

        if ($request->credit_account_id) {
            $creditAccount = Account::where('company_id', $companyId)
                ->find($request->credit_account_id);
            if (! $creditAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credit account not found.',
                ], 422);
            }
        }

        $mapping = AccountMapping::updateOrCreate(
            [
                'company_id' => $companyId,
                'entity_type' => $request->entity_type,
                'entity_id' => $request->entity_id,
                'transaction_type' => $request->transaction_type,
            ],
            [
                'debit_account_id' => $request->debit_account_id,
                'credit_account_id' => $request->credit_account_id,
                'meta' => $request->meta,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Account mapping saved successfully.',
            'data' => $mapping->load(['debitAccount', 'creditAccount']),
        ], $mapping->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Delete an account mapping.
     */
    public function destroyMapping(Request $request, int $id): JsonResponse
    {
        $this->authorize('manage-closings');

        $companyId = $request->header('company');

        $mapping = AccountMapping::where('company_id', $companyId)
            ->findOrFail($id);

        $mapping->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account mapping deleted successfully.',
        ]);
    }
}
// CLAUDE-CHECKPOINT
