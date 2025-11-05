<?php

namespace App\Http\Controllers\V1\Admin\Banking;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\ExpenseCategory;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Banking Controller
 *
 * Handles bank account and transaction management
 * Lists connected accounts, transactions, and categorization
 */
class BankingController extends Controller
{
    private ?Company $currentCompany = null;

    /**
     * Get all connected bank accounts for the current company
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function accounts(Request $request): JsonResponse
    {
        try {
            $company = $this->resolveCompany($request);

            if (!$company) {
                return response()->json([
                    'error' => 'No company found for user'
                ], 404);
            }

            $accounts = BankAccount::where('company_id', $company->id)
                ->with(['currency'])
                ->active()
                ->get()
                ->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'bank_name' => $account->bank_name,
                        'bank_code' => $account->bank_code,
                        'account_number' => $account->account_number,
                        'iban' => $account->iban,
                        'current_balance' => $account->current_balance,
                        'currency' => $account->currency?->code ?? 'MKD',
                        'bank_logo' => $this->getBankLogo($account->bank_code),
                        'last_sync_at' => $account->updated_at,
                        'sync_status' => 'connected', // TODO: Add real sync status tracking
                        'is_primary' => $account->is_primary,
                    ];
                });

            return response()->json([
                'data' => $accounts
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch bank accounts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to fetch bank accounts',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transactions with optional filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function transactions(Request $request): JsonResponse
    {
        try {
            $company = $this->resolveCompany($request);

            if (!$company) {
                return response()->json([
                    'error' => 'No company found for user'
                ], 404);
            }

            $query = BankTransaction::where('company_id', $company->id)
                ->with(['bankAccount', 'matchedInvoice', 'matchedPayment']);

            // Apply filters
            if ($request->has('account_id') && $request->account_id) {
                $query->where('bank_account_id', $request->account_id);
            }

            if ($request->has('from_date') && $request->from_date) {
                $query->where('transaction_date', '>=', Carbon::parse($request->from_date));
            }

            if ($request->has('to_date') && $request->to_date) {
                $query->where('transaction_date', '<=', Carbon::parse($request->to_date));
            }

            if ($request->has('search') && $request->search) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhere('remittance_info', 'like', "%{$search}%")
                        ->orWhere('debtor_name', 'like', "%{$search}%")
                        ->orWhere('creditor_name', 'like', "%{$search}%");
                });
            }

            // Sorting
            $orderBy = $request->get('orderBy', 'desc');
            $orderByField = $request->get('orderByField', 'transaction_date');
            $query->orderBy($orderByField, $orderBy);

            // Pagination
            $limit = $request->get('limit', 15);
            $transactions = $query->paginate($limit);

            $data = $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'transaction_date' => $transaction->transaction_date,
                    'booking_date' => $transaction->booking_date,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                    'description' => $transaction->description,
                    'remittance_info' => $transaction->remittance_info,
                    'transaction_reference' => $transaction->transaction_reference,
                    'counterparty_name' => $transaction->counterparty_name,
                    'counterparty_iban' => $transaction->counterparty_iban,
                    'booking_status' => $transaction->booking_status,
                    'processing_status' => $transaction->processing_status,
                    'matched_invoice_id' => $transaction->matched_invoice_id,
                    'matched_payment_id' => $transaction->matched_payment_id,
                    'matched_at' => $transaction->matched_at,
                    'match_confidence' => $transaction->match_confidence,
                    'category' => null, // TODO: Link to expense categories if needed
                ];
            });

            return response()->json([
                'data' => $data,
                'meta' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch transactions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to fetch transactions',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger manual sync for a specific bank account
     *
     * @param Request $request
     * @param BankAccount $account
     * @return JsonResponse
     */
    public function syncAccount(Request $request, BankAccount $account): JsonResponse
    {
        try {
            $company = $this->resolveCompany($request);

            if (!$company || $account->company_id !== $company->id) {
                return response()->json([
                    'error' => 'Unauthorized'
                ], 403);
            }

            // TODO: Dispatch sync job
            // \App\Jobs\SyncBankTransactions::dispatch($account);

            Log::info('Manual bank sync triggered', [
                'company_id' => $company->id,
                'account_id' => $account->id,
                'bank_code' => $account->bank_code
            ]);

            return response()->json([
                'message' => 'Sync started successfully',
                'account_id' => $account->id
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync account', [
                'error' => $e->getMessage(),
                'account_id' => $account->id
            ]);

            return response()->json([
                'error' => 'Failed to start sync',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Categorize a transaction
     *
     * @param Request $request
     * @param BankTransaction $transaction
     * @return JsonResponse
     */
    public function categorize(Request $request, BankTransaction $transaction): JsonResponse
    {
        try {
            $company = $this->resolveCompany($request);

            if (!$company || $transaction->company_id !== $company->id) {
                return response()->json([
                    'error' => 'Unauthorized'
                ], 403);
            }

            $request->validate([
                'category_id' => 'required|exists:expense_categories,id',
                'notes' => 'nullable|string',
                'create_expense' => 'nullable|boolean'
            ]);

            $category = ExpenseCategory::find($request->category_id);

            // Update transaction with category info (stored in processing_notes)
            $transaction->update([
                'processing_notes' => json_encode([
                    'category_id' => $category->id,
                    'category_name' => $category->name,
                    'notes' => $request->notes,
                ]),
                'processing_status' => BankTransaction::STATUS_PROCESSED,
                'processed_at' => now(),
            ]);

            // Optionally create expense record
            if ($request->create_expense && $transaction->amount < 0) {
                $expense = Expense::create([
                    'company_id' => $company->id,
                    'expense_category_id' => $category->id,
                    'amount' => abs($transaction->amount),
                    'currency_id' => $company->currency_id,
                    'expense_date' => $transaction->transaction_date,
                    'notes' => $request->notes ?? $transaction->description,
                    'payment_method_id' => null, // Bank transfer
                    'exchange_rate' => 1,
                    'base_amount' => abs($transaction->amount),
                    'creator_id' => $request->user()->id,
                ]);

                Log::info('Expense created from bank transaction', [
                    'transaction_id' => $transaction->id,
                    'expense_id' => $expense->id
                ]);
            }

            return response()->json([
                'message' => 'Transaction categorized successfully',
                'transaction' => $transaction
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to categorize transaction', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id
            ]);

            return response()->json([
                'error' => 'Failed to categorize transaction',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get bank logo URL based on bank code
     *
     * @param string|null $bankCode
     * @return string
     */
    private function getBankLogo(?string $bankCode): string
    {
        $logos = [
            'stopanska' => 'images/banks/stopanska-logo.svg',
            'nlb' => 'images/banks/nlb-logo.svg',
        ];

        return asset($logos[$bankCode] ?? 'images/banks/default-bank.svg');
    }

    /**
     * Resolve the active company for the authenticated user
     */
    private function resolveCompany(Request $request): ?Company
    {
        if ($this->currentCompany instanceof Company) {
            return $this->currentCompany;
        }

        $user = $request->user();

        if (!$user) {
            return null;
        }

        $companyIdHeader = $request->header('company');
        $companyId = $companyIdHeader !== null ? (int) $companyIdHeader : null;
        $company = null;

        if ($companyId && $user->hasCompany($companyId)) {
            $company = $user->companies()->where('companies.id', $companyId)->first();
        }

        if (!$company) {
            $company = $user->companies()->first();
        }

        if ($company) {
            $company->loadMissing('currency');
        }

        return $this->currentCompany = $company;
    }
}

// CLAUDE-CHECKPOINT
