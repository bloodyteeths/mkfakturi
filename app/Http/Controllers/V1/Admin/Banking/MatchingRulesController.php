<?php

namespace App\Http\Controllers\V1\Admin\Banking;

use App\Http\Controllers\Controller;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\MatchingRule;
use App\Services\Reconciliation\MatchingRulesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

/**
 * Matching Rules Controller
 *
 * CRUD operations for user-defined matching rules that automatically
 * categorize, match, or ignore bank transactions.
 *
 * All endpoints use forCompany() scope for tenant isolation.
 *
 * @see P0-09 Matching Rules Engine
 */
class MatchingRulesController extends Controller
{
    /**
     * @var MatchingRulesService
     */
    private MatchingRulesService $matchingRulesService;

    /**
     * Create a new controller instance.
     *
     * @param MatchingRulesService $matchingRulesService
     */
    public function __construct(MatchingRulesService $matchingRulesService)
    {
        $this->matchingRulesService = $matchingRulesService;
    }

    /**
     * List all matching rules for the current company.
     *
     * GET /api/v1/banking/matching-rules
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $company = $this->resolveCompany($request);

            if (! $company) {
                return response()->json(['data' => [], 'message' => 'No company found'], 200);
            }

            if (! Schema::hasTable('matching_rules')) {
                return response()->json(['data' => [], 'message' => 'Feature not yet initialized'], 200);
            }

            $rules = MatchingRule::forCompany($company->id)
                ->byPriority()
                ->get()
                ->map(function (MatchingRule $rule) {
                    return $this->formatRule($rule);
                });

            return response()->json([
                'data' => $rules,
                'meta' => [
                    'valid_fields' => MatchingRulesService::getValidFields(),
                    'valid_operators' => MatchingRulesService::getValidOperators(),
                    'valid_actions' => MatchingRulesService::getValidActions(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch matching rules', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'data' => [],
                'error' => 'Failed to fetch matching rules',
            ], 200);
        }
    }

    /**
     * Create a new matching rule.
     *
     * POST /api/v1/banking/matching-rules
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $company = $this->resolveCompany($request);

            if (! $company) {
                return response()->json(['error' => 'No company found'], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'conditions' => 'required|array|min:1',
                'conditions.*.field' => ['required', 'string', Rule::in(MatchingRulesService::getValidFields())],
                'conditions.*.operator' => ['required', 'string', Rule::in(MatchingRulesService::getValidOperators())],
                'conditions.*.value' => 'required',
                'actions' => 'required|array|min:1',
                'actions.*.action' => ['required', 'string', Rule::in(MatchingRulesService::getValidActions())],
                'priority' => 'nullable|integer|min:0|max:1000',
                'is_active' => 'nullable|boolean',
            ]);

            $rule = MatchingRule::create([
                'company_id' => $company->id,
                'name' => $validated['name'],
                'conditions' => $validated['conditions'],
                'actions' => $validated['actions'],
                'priority' => $validated['priority'] ?? 0,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            Log::info('Matching rule created', [
                'rule_id' => $rule->id,
                'company_id' => $company->id,
                'name' => $rule->name,
            ]);

            return response()->json([
                'data' => $this->formatRule($rule),
                'message' => 'Matching rule created successfully',
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Failed to create matching rule', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to create matching rule',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Update an existing matching rule.
     *
     * PUT /api/v1/banking/matching-rules/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $company = $this->resolveCompany($request);

            if (! $company) {
                return response()->json(['error' => 'No company found'], 404);
            }

            $rule = MatchingRule::forCompany($company->id)
                ->where('id', $id)
                ->firstOrFail();

            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'conditions' => 'sometimes|required|array|min:1',
                'conditions.*.field' => ['required_with:conditions', 'string', Rule::in(MatchingRulesService::getValidFields())],
                'conditions.*.operator' => ['required_with:conditions', 'string', Rule::in(MatchingRulesService::getValidOperators())],
                'conditions.*.value' => 'required_with:conditions',
                'actions' => 'sometimes|required|array|min:1',
                'actions.*.action' => ['required_with:actions', 'string', Rule::in(MatchingRulesService::getValidActions())],
                'priority' => 'nullable|integer|min:0|max:1000',
                'is_active' => 'nullable|boolean',
            ]);

            $rule->update($validated);

            Log::info('Matching rule updated', [
                'rule_id' => $rule->id,
                'company_id' => $company->id,
            ]);

            return response()->json([
                'data' => $this->formatRule($rule->fresh()),
                'message' => 'Matching rule updated successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Matching rule not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Failed to update matching rule', [
                'error' => $e->getMessage(),
                'rule_id' => $id,
            ]);

            return response()->json([
                'error' => 'Failed to update matching rule',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Delete a matching rule.
     *
     * DELETE /api/v1/banking/matching-rules/{id}
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $company = $this->resolveCompany($request);

            if (! $company) {
                return response()->json(['error' => 'No company found'], 404);
            }

            $rule = MatchingRule::forCompany($company->id)
                ->where('id', $id)
                ->firstOrFail();

            $ruleName = $rule->name;
            $rule->delete();

            Log::info('Matching rule deleted', [
                'rule_id' => $id,
                'rule_name' => $ruleName,
                'company_id' => $company->id,
            ]);

            return response()->json([
                'message' => 'Matching rule deleted successfully',
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Matching rule not found'], 404);
        } catch (\Throwable $e) {
            Log::error('Failed to delete matching rule', [
                'error' => $e->getMessage(),
                'rule_id' => $id,
            ]);

            return response()->json([
                'error' => 'Failed to delete matching rule',
            ], 500);
        }
    }

    /**
     * Test a matching rule against recent transactions.
     *
     * POST /api/v1/banking/matching-rules/{id}/test
     *
     * Returns a preview of which recent transactions would match this rule.
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function test(Request $request, int $id): JsonResponse
    {
        try {
            $company = $this->resolveCompany($request);

            if (! $company) {
                return response()->json(['error' => 'No company found'], 404);
            }

            $rule = MatchingRule::forCompany($company->id)
                ->where('id', $id)
                ->firstOrFail();

            // Get recent transactions to test against (last 50)
            $transactions = BankTransaction::forCompany($company->id)
                ->orderBy('transaction_date', 'desc')
                ->limit(50)
                ->get();

            $matches = [];

            foreach ($transactions as $transaction) {
                if ($this->matchingRulesService->evaluateRule($rule, $transaction)) {
                    $matches[] = [
                        'id' => $transaction->id,
                        'transaction_date' => $transaction->transaction_date?->toIso8601String(),
                        'amount' => (float) $transaction->amount,
                        'currency' => $transaction->currency ?? 'MKD',
                        'description' => $transaction->description ?? '',
                        'remittance_info' => $transaction->remittance_info ?? '',
                        'counterparty_name' => $transaction->counterparty_name ?? '',
                        'processing_status' => $transaction->processing_status ?? 'unprocessed',
                    ];
                }
            }

            return response()->json([
                'data' => [
                    'rule' => $this->formatRule($rule),
                    'tested_count' => $transactions->count(),
                    'matched_count' => count($matches),
                    'matches' => $matches,
                ],
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Matching rule not found'], 404);
        } catch (\Throwable $e) {
            Log::error('Failed to test matching rule', [
                'error' => $e->getMessage(),
                'rule_id' => $id,
            ]);

            return response()->json([
                'error' => 'Failed to test matching rule',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Format a matching rule for API response.
     *
     * @param MatchingRule $rule
     * @return array
     */
    private function formatRule(MatchingRule $rule): array
    {
        return [
            'id' => $rule->id,
            'company_id' => $rule->company_id,
            'name' => $rule->name,
            'conditions' => $rule->conditions,
            'actions' => $rule->actions,
            'priority' => $rule->priority,
            'is_active' => $rule->is_active,
            'created_at' => $rule->created_at?->toIso8601String(),
            'updated_at' => $rule->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Resolve the active company for the authenticated user.
     *
     * P0-13: Resolves company from request header and validates user access.
     * Falls back to user's first company if no header is provided.
     *
     * @param Request $request
     * @return Company|null
     */
    private function resolveCompany(Request $request): ?Company
    {
        $user = Auth::user();

        if (! $user) {
            return null;
        }

        $companyIdHeader = $request->header('company');

        if ($companyIdHeader) {
            $companyId = (int) $companyIdHeader;

            if ($user->hasCompany($companyId)) {
                $company = $user->companies()->where('companies.id', $companyId)->first();
                if ($company) {
                    return $company;
                }
            }
        }

        return $user->companies()->first();
    }
}

// CLAUDE-CHECKPOINT
