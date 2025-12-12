<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Partner;
use App\Models\Payment;
use App\Services\AccountSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Partner Account Mapping Controller
 *
 * Manages account mappings for companies linked to the partner.
 * Allows partners to map entities (customers, suppliers, categories) to accounts
 * for automated journal entry generation.
 */
class PartnerAccountMappingController extends Controller
{
    protected AccountSuggestionService $suggestionService;

    public function __construct(AccountSuggestionService $suggestionService)
    {
        $this->suggestionService = $suggestionService;
    }

    /**
     * List all account mappings for partner's companies.
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

        $query = AccountMapping::where('company_id', $companyId)
            ->with(['debitAccount:id,code,name', 'creditAccount:id,code,name']);

        if ($request->entity_type) {
            $query->where('entity_type', $request->entity_type);
        }

        if ($request->has('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        $mappings = $query->get();

        return response()->json([
            'success' => true,
            'data' => $mappings,
        ]);
    }

    /**
     * Get a single account mapping.
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

        $mapping = AccountMapping::where('company_id', $companyId)
            ->with(['debitAccount', 'creditAccount'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $mapping,
        ]);
    }

    /**
     * Create a new account mapping.
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
            'entity_type' => 'required|string|max:50',
            'entity_id' => 'nullable|integer',
            'debit_account_id' => 'nullable|integer|exists:accounts,id',
            'credit_account_id' => 'nullable|integer|exists:accounts,id',
            'transaction_type' => 'nullable|string|max:50',
            'meta' => 'nullable|array',
        ]);

        // Validate accounts belong to company
        if ($request->debit_account_id) {
            $debitAccount = Account::where('company_id', $companyId)
                ->find($request->debit_account_id);
            if (!$debitAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debit account not found.',
                ], 422);
            }
        }

        if ($request->credit_account_id) {
            $creditAccount = Account::where('company_id', $companyId)
                ->find($request->credit_account_id);
            if (!$creditAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credit account not found.',
                ], 422);
            }
        }

        $mapping = AccountMapping::create([
            'company_id' => $companyId,
            'entity_type' => $request->entity_type,
            'entity_id' => $request->entity_id,
            'debit_account_id' => $request->debit_account_id,
            'credit_account_id' => $request->credit_account_id,
            'transaction_type' => $request->transaction_type,
            'meta' => $request->meta,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Account mapping created successfully.',
            'data' => $mapping->load(['debitAccount', 'creditAccount']),
        ], 201);
    }

    /**
     * Update an account mapping.
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

        $mapping = AccountMapping::where('company_id', $companyId)
            ->findOrFail($id);

        $request->validate([
            'entity_type' => 'sometimes|required|string|max:50',
            'entity_id' => 'nullable|integer',
            'debit_account_id' => 'nullable|integer|exists:accounts,id',
            'credit_account_id' => 'nullable|integer|exists:accounts,id',
            'transaction_type' => 'nullable|string|max:50',
            'meta' => 'nullable|array',
        ]);

        // Validate accounts belong to company
        if ($request->has('debit_account_id') && $request->debit_account_id) {
            $debitAccount = Account::where('company_id', $companyId)
                ->find($request->debit_account_id);
            if (!$debitAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Debit account not found.',
                ], 422);
            }
        }

        if ($request->has('credit_account_id') && $request->credit_account_id) {
            $creditAccount = Account::where('company_id', $companyId)
                ->find($request->credit_account_id);
            if (!$creditAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Credit account not found.',
                ], 422);
            }
        }

        $mapping->update($request->only([
            'entity_type',
            'entity_id',
            'debit_account_id',
            'credit_account_id',
            'transaction_type',
            'meta',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Account mapping updated successfully.',
            'data' => $mapping->fresh()->load(['debitAccount', 'creditAccount']),
        ]);
    }

    /**
     * Delete an account mapping.
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

        $mapping = AccountMapping::where('company_id', $companyId)
            ->findOrFail($id);

        $mapping->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account mapping deleted successfully.',
        ]);
    }

    /**
     * Get AI-powered account suggestions for a transaction.
     */
    public function suggest(Request $request): JsonResponse
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
            'transaction_type' => 'required|in:invoice,payment,expense',
            'transaction_id' => 'required|integer',
        ]);

        try {
            $suggestion = null;

            switch ($request->transaction_type) {
                case 'invoice':
                    $invoice = Invoice::where('company_id', $companyId)
                        ->findOrFail($request->transaction_id);
                    $suggestion = $this->suggestionService->suggestForInvoice($invoice);
                    break;

                case 'payment':
                    $payment = Payment::where('company_id', $companyId)
                        ->findOrFail($request->transaction_id);
                    $suggestion = $this->suggestionService->suggestForPayment($payment);
                    break;

                case 'expense':
                    $expense = Expense::where('company_id', $companyId)
                        ->findOrFail($request->transaction_id);
                    $suggestion = $this->suggestionService->suggestForExpense($expense);
                    break;
            }

            // Load full account details for the suggestion
            if ($suggestion['debit_account_id']) {
                $debitAccount = Account::find($suggestion['debit_account_id']);
                $suggestion['debit_account'] = $debitAccount ? [
                    'id' => $debitAccount->id,
                    'code' => $debitAccount->code,
                    'name' => $debitAccount->name,
                    'type' => $debitAccount->type,
                ] : null;
            }

            if ($suggestion['credit_account_id']) {
                $creditAccount = Account::find($suggestion['credit_account_id']);
                $suggestion['credit_account'] = $creditAccount ? [
                    'id' => $creditAccount->id,
                    'code' => $creditAccount->code,
                    'name' => $creditAccount->name,
                    'type' => $creditAccount->type,
                ] : null;
            }

            return response()->json([
                'success' => true,
                'data' => $suggestion,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not generate suggestion: ' . $e->getMessage(),
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
