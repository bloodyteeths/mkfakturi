<?php

namespace App\Http\Controllers\V1\Admin\Banking;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Services\AiProvider\AiProviderInterface;
use App\Services\AiProvider\ClaudeProvider;
use App\Services\AiProvider\GeminiProvider;
use App\Services\AiProvider\NullAiProvider;
use App\Services\AiProvider\OpenAiProvider;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

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
     */
    public function accounts(Request $request): JsonResponse
    {
        try {
            Log::info('Banking accounts endpoint called', [
                'user_id' => $request->user()?->id,
                'company_header' => $request->header('company'),
            ]);

            // Check if bank_accounts table exists
            if (! Schema::hasTable('bank_accounts')) {
                Log::warning('bank_accounts table does not exist');

                return response()->json([
                    'data' => [],
                    'message' => 'Banking feature not yet initialized',
                ]);
            }

            $company = $this->resolveCompany($request);

            if (! $company) {
                Log::warning('No company found for banking accounts request', [
                    'user_id' => $request->user()?->id,
                ]);

                return response()->json([
                    'data' => [],
                    'error' => 'No company found for user',
                ], 200); // Return 200 with empty data instead of 404
            }

            Log::info('Querying bank accounts', [
                'company_id' => $company->id,
            ]);

            // P0-13: explicit tenant scope via forCompany()
            $accountsQuery = BankAccount::forCompany($company->id);

            // Some self-hosted deployments might still be on an older schema without the is_active column.
            if (Schema::hasColumn('bank_accounts', 'is_active')) {
                $accountsQuery->where('is_active', true);
            }

            $accounts = $accountsQuery
                ->get()
                ->map(function ($account) {
                    try {
                        // Safely load currency relationship
                        $currencyCode = 'MKD';
                        try {
                            if ($account->currency_id && $account->currency) {
                                $currencyCode = $account->currency->code;
                            }
                        } catch (\Throwable $e) {
                            Log::warning('Failed to load currency for bank account', [
                                'account_id' => $account->id,
                                'error' => $e->getMessage(),
                            ]);
                        }

                        // Auto-recalculate balance if it's zero but transactions exist
                        $balance = (float) ($account->current_balance ?? 0);
                        if ($balance == 0.0) {
                            $credits = BankTransaction::where('bank_account_id', $account->id)
                                ->where('transaction_type', BankTransaction::TYPE_CREDIT)->sum('amount');
                            $debits = BankTransaction::where('bank_account_id', $account->id)
                                ->where('transaction_type', BankTransaction::TYPE_DEBIT)->sum('amount');
                            if ($credits > 0 || $debits > 0) {
                                $balance = (float) $account->opening_balance + (float) $credits - (float) $debits;
                                $account->update(['current_balance' => round($balance, 2)]);
                            }
                        }

                        return [
                            'id' => $account->id,
                            'bank_name' => $account->bank_name ?? 'Unknown Bank',
                            'bank_code' => $account->bank_code,
                            'account_number' => $account->account_number ?? '',
                            'iban' => $account->iban ?? '',
                            'current_balance' => $balance,
                            'currency' => $currencyCode,
                            'bank_logo' => $this->getBankLogo($account->bank_code),
                            'last_sync_at' => $account->updated_at?->toIso8601String(),
                            'sync_status' => 'connected',
                            'is_primary' => $account->is_primary ?? false,
                        ];
                    } catch (\Throwable $e) {
                        Log::error('Failed to map bank account', [
                            'account_id' => $account->id ?? 'unknown',
                            'error' => $e->getMessage(),
                        ]);

                        return null;
                    }
                })
                ->filter() // Remove null entries
                ->values();

            Log::info('Bank accounts fetched successfully', [
                'company_id' => $company->id,
                'count' => $accounts->count(),
            ]);

            return response()->json([
                'data' => $accounts,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch bank accounts', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $request->user()?->id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'data' => [],
                'error' => 'Failed to fetch bank accounts',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 200); // Return 200 to prevent UI errors
        }
    }

    /**
     * Get transactions with optional filters
     */
    public function transactions(Request $request): JsonResponse
    {
        try {
            Log::info('Banking transactions endpoint called', [
                'user_id' => $request->user()?->id,
                'company_header' => $request->header('company'),
                'filters' => $request->only(['account_id', 'from_date', 'to_date', 'search']),
            ]);

            // Check if bank_transactions table exists
            if (! Schema::hasTable('bank_transactions')) {
                Log::warning('bank_transactions table does not exist');

                return response()->json([
                    'data' => [],
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 15,
                        'total' => 0,
                    ],
                    'message' => 'Banking feature not yet initialized',
                ]);
            }

            $company = $this->resolveCompany($request);

            if (! $company) {
                Log::warning('No company found for banking transactions request', [
                    'user_id' => $request->user()?->id,
                ]);

                return response()->json([
                    'data' => [],
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 15,
                        'total' => 0,
                    ],
                    'error' => 'No company found for user',
                ], 200); // Return 200 with empty data instead of 404
            }

            Log::info('Querying bank transactions', [
                'company_id' => $company->id,
            ]);

            // P0-13: explicit tenant scope via forCompany()
            $query = BankTransaction::forCompany($company->id);

            // Only eager load relationships if they exist
            try {
                $query->with(['bankAccount', 'matchedInvoice', 'matchedPayment']);
            } catch (\Throwable $e) {
                Log::warning('Failed to eager load relationships', [
                    'error' => $e->getMessage(),
                ]);
            }

            // Apply filters
            if ($request->has('account_id') && $request->account_id) {
                $query->where('bank_account_id', $request->account_id);
            }

            if ($request->has('from_date') && $request->from_date) {
                try {
                    $query->where('transaction_date', '>=', Carbon::parse($request->from_date));
                } catch (\Throwable $e) {
                    Log::warning('Invalid from_date format', ['from_date' => $request->from_date]);
                }
            }

            if ($request->has('to_date') && $request->to_date) {
                try {
                    $query->where('transaction_date', '<=', Carbon::parse($request->to_date));
                } catch (\Throwable $e) {
                    Log::warning('Invalid to_date format', ['to_date' => $request->to_date]);
                }
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

            // Sorting — whitelist allowed orderBy fields to prevent SQL injection
            $allowedOrderFields = ['transaction_date', 'amount', 'description', 'status', 'created_at'];
            $orderByField = in_array($request->get('orderByField'), $allowedOrderFields) ? $request->get('orderByField') : 'transaction_date';
            $orderByDirection = in_array(strtolower($request->get('orderBy', 'desc')), ['asc', 'desc']) ? $request->get('orderBy', 'desc') : 'desc';
            $query->orderBy($orderByField, $orderByDirection);
            // CLAUDE-CHECKPOINT

            // Pagination
            $limit = $request->get('limit', 15);
            $transactions = $query->paginate($limit);

            Log::info('Bank transactions queried successfully', [
                'company_id' => $company->id,
                'count' => $transactions->count(),
                'total' => $transactions->total(),
            ]);

            $data = $transactions->map(function ($transaction) {
                try {
                    return [
                        'id' => $transaction->id,
                        'bank_account_id' => $transaction->bank_account_id,
                        'bank_account_name' => $transaction->bankAccount?->bank_name ?? '',
                        'bank_account_number' => $transaction->bankAccount?->account_number ?? '',
                        'transaction_date' => $transaction->transaction_date?->toIso8601String(),
                        'booking_date' => $transaction->booking_date?->toIso8601String(),
                        'amount' => $transaction->amount ?? 0,
                        'currency' => $transaction->currency ?? 'MKD',
                        'transaction_type' => $transaction->transaction_type ?? 'credit',
                        'description' => $transaction->description ?? '',
                        'remittance_info' => $transaction->remittance_info ?? '',
                        'transaction_reference' => $transaction->transaction_reference ?? '',
                        'counterparty_name' => $transaction->counterparty_name ?? '',
                        'counterparty_iban' => $transaction->counterparty_iban ?? '',
                        'booking_status' => $transaction->booking_status ?? '',
                        'processing_status' => $transaction->processing_status ?? 'unprocessed',
                        'matched_invoice_id' => $transaction->matched_invoice_id,
                        'matched_payment_id' => $transaction->matched_payment_id,
                        'matched_at' => $transaction->matched_at?->toIso8601String(),
                        'match_confidence' => $transaction->match_confidence,
                        'category' => $transaction->category,
                        'ai_category' => $transaction->ai_category,
                        'ai_match_reason' => $transaction->ai_match_reason,
                    ];
                } catch (\Throwable $e) {
                    Log::error('Failed to map transaction', [
                        'transaction_id' => $transaction->id ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);

                    return null;
                }
            })->filter()->values();

            return response()->json([
                'data' => $data,
                'meta' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch transactions', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 15,
                    'total' => 0,
                ],
                'error' => 'Failed to fetch transactions',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 200); // Return 200 to prevent UI errors
        }
    }

    /**
     * Trigger manual sync for a specific bank account
     */
    public function syncAccount(Request $request, BankAccount $account): JsonResponse
    {
        try {
            $company = $this->resolveCompany($request);

            if (! $company || $account->company_id !== $company->id) {
                return response()->json([
                    'error' => 'Unauthorized',
                ], 403);
            }

            // Dispatch sync job to fetch latest transactions
            \App\Jobs\SyncBankTransactions::dispatch($company, 7);

            Log::info('Manual bank sync triggered', [
                'company_id' => $company->id,
                'account_id' => $account->id,
                'bank_code' => $account->bank_code,
            ]);

            return response()->json([
                'message' => 'Sync started successfully',
                'account_id' => $account->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to sync account', [
                'error' => $e->getMessage(),
                'account_id' => $account->id,
            ]);

            return response()->json([
                'error' => 'Failed to start sync',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Categorize a transaction
     */
    public function categorize(Request $request, BankTransaction $transaction): JsonResponse
    {
        try {
            $company = $this->resolveCompany($request);

            if (! $company || $transaction->company_id !== $company->id) {
                return response()->json([
                    'error' => 'Unauthorized',
                ], 403);
            }

            $request->validate([
                'category_id' => 'required|exists:expense_categories,id',
                'notes' => 'nullable|string',
                'create_expense' => 'nullable|boolean',
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
                    'expense_id' => $expense->id,
                ]);
            }

            return response()->json([
                'message' => 'Transaction categorized successfully',
                'transaction' => $transaction,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to categorize transaction', [
                'error' => $e->getMessage(),
                'transaction_id' => $transaction->id,
            ]);

            return response()->json([
                'error' => 'Failed to categorize transaction',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Disconnect (deactivate) a bank account
     */
    public function disconnect(Request $request, BankAccount $account): JsonResponse
    {
        try {
            $company = $this->resolveCompany($request);

            if (! $company || $account->company_id !== $company->id) {
                return response()->json([
                    'error' => 'Unauthorized',
                ], 403);
            }

            $account->update(['is_active' => false]);

            Log::info('Bank account disconnected', [
                'company_id' => $company->id,
                'account_id' => $account->id,
                'bank_code' => $account->bank_code,
            ]);

            return response()->json([
                'message' => 'Bank account disconnected successfully',
                'account_id' => $account->id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to disconnect account', [
                'error' => $e->getMessage(),
                'account_id' => $account->id,
            ]);

            return response()->json([
                'error' => 'Failed to disconnect account',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get banking status overview for dashboard widget
     *
     * Returns aggregated status of all bank connections and sync stats
     */
    public function status(Request $request): JsonResponse
    {
        try {
            $company = $this->resolveCompany($request);

            if (! $company) {
                return response()->json(['data' => []], 200);
            }

            // Check if tables exist
            if (! Schema::hasTable('bank_accounts')) {
                return response()->json(['data' => []], 200);
            }

            // P0-13: explicit tenant scope via forCompany()
            $accountsQuery = BankAccount::forCompany($company->id);
            if (Schema::hasColumn('bank_accounts', 'is_active')) {
                $accountsQuery->where('is_active', true);
            }
            $accounts = $accountsQuery->get();

            // Group by bank
            $bankConnections = $accounts->groupBy('bank_code')->map(function ($bankAccounts, $code) {
                $firstAccount = $bankAccounts->first();
                $lastSync = $bankAccounts->max('updated_at');

                return [
                    'code' => $code,
                    'name' => $this->getBankNameFromCode($code),
                    'accountCount' => $bankAccounts->count(),
                    'status' => 'connected',
                    'lastSync' => $lastSync?->toIso8601String(),
                    'psd2Status' => 'active',
                ];
            })->values();

            // Calculate today's sync stats
            $syncStats = [
                'transactionsToday' => 0,
                'totalAmountToday' => 0,
                'matchedPayments' => 0,
                'matchRate' => 0,
            ];

            if (Schema::hasTable('bank_transactions')) {
                $today = Carbon::today();
                // P0-13: explicit tenant scope via forCompany()
                $transactions = BankTransaction::forCompany($company->id)
                    ->whereDate('transaction_date', $today)
                    ->get();

                $matchedCount = $transactions->whereNotNull('matched_payment_id')->count();

                $syncStats = [
                    'transactionsToday' => $transactions->count(),
                    'totalAmountToday' => abs($transactions->sum('amount')),
                    'matchedPayments' => $matchedCount,
                    'matchRate' => $transactions->count() > 0
                        ? round(($matchedCount / $transactions->count()) * 100)
                        : 0,
                ];
            }

            return response()->json([
                'data' => [
                    'bankConnections' => $bankConnections,
                    'syncStats' => $syncStats,
                    'lastFullSync' => $accounts->max('updated_at')?->toIso8601String(),
                    'nextSync' => now()->addMinutes(15)->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to fetch banking status', [
                'error' => $e->getMessage(),
            ]);

            return response()->json(['data' => []], 200);
        }
    }

    /**
     * Get human-readable bank name from code
     */
    private function getBankNameFromCode(?string $code): string
    {
        return match ($code) {
            'stopanska' => 'Стопанска Банка',
            'nlb' => 'NLB Банка',
            'komercijalna' => 'Комерцијална Банка',
            'sparkasse' => 'Шпаркасе Банка',
            'ohridska' => 'Охридска Банка',
            'halkbank' => 'Халкбанк',
            default => $code ?? 'Unknown Bank',
        };
    }

    /**
     * Get bank logo URL based on bank code
     */
    private function getBankLogo(?string $bankCode): ?string
    {
        // Map of bank codes to logo files
        $logos = [
            'stopanska' => 'images/banks/stopanska-logo.svg',
            'nlb' => 'images/banks/nlb-logo.svg',
        ];

        // Return null if no bank code provided or logo not found
        // This prevents 404 errors for missing logos
        if (! $bankCode || ! isset($logos[$bankCode])) {
            return null;
        }

        // Only return asset path if we have a specific logo
        return asset($logos[$bankCode]);
    }

    /**
     * Manually create a single bank transaction
     */
    public function storeManualTransaction(Request $request): JsonResponse
    {
        try {
            $company = $this->resolveCompany($request);

            if (! $company) {
                return response()->json(['error' => 'No company found'], 404);
            }

            $validated = $request->validate([
                'bank_account_id' => 'required|integer|exists:bank_accounts,id',
                'amount' => 'required|numeric|not_in:0',
                'transaction_date' => 'required|date',
                'description' => 'required|string|max:1000',
                'transaction_type' => 'required|in:credit,debit',
                'counterparty_name' => 'nullable|string|max:500',
                'counterparty_iban' => 'nullable|string|max:34',
                'remittance_info' => 'nullable|string|max:1000',
                'payment_reference' => 'nullable|string|max:255',
            ]);

            // Verify the bank account belongs to this company
            $bankAccount = BankAccount::where('id', $validated['bank_account_id'])
                ->where('company_id', $company->id)
                ->first();

            if (! $bankAccount) {
                return response()->json(['error' => 'Bank account not found'], 404);
            }

            // Ensure amount sign matches transaction type
            $amount = abs($validated['amount']);
            if ($validated['transaction_type'] === 'debit') {
                $amount = -$amount;
            }

            $transactionData = [
                'bank_account_id' => $bankAccount->id,
                'company_id' => $company->id,
                'amount' => $amount,
                'currency' => $bankAccount->currency?->code ?? 'MKD',
                'transaction_type' => $validated['transaction_type'],
                'transaction_date' => Carbon::parse($validated['transaction_date']),
                'booking_date' => Carbon::parse($validated['transaction_date']),
                'description' => $validated['description'],
                'remittance_info' => $validated['remittance_info'] ?? null,
                'payment_reference' => $validated['payment_reference'] ?? null,
                'source' => BankTransaction::SOURCE_MANUAL,
                'processing_status' => BankTransaction::STATUS_UNPROCESSED,
                'booking_status' => BankTransaction::BOOKING_BOOKED,
            ];

            // Set counterparty fields based on transaction type
            if ($validated['transaction_type'] === 'credit') {
                $transactionData['debtor_name'] = $validated['counterparty_name'] ?? null;
                $transactionData['debtor_iban'] = $validated['counterparty_iban'] ?? null;
            } else {
                $transactionData['creditor_name'] = $validated['counterparty_name'] ?? null;
                $transactionData['creditor_iban'] = $validated['counterparty_iban'] ?? null;
            }

            $transaction = BankTransaction::create($transactionData);

            Log::info('Manual bank transaction created', [
                'company_id' => $company->id,
                'transaction_id' => $transaction->id,
                'amount' => $amount,
            ]);

            return response()->json([
                'message' => 'Transaction created successfully',
                'data' => [
                    'id' => $transaction->id,
                    'transaction_date' => $transaction->transaction_date?->toIso8601String(),
                    'amount' => $transaction->amount,
                    'description' => $transaction->description,
                    'counterparty_name' => $transaction->counterparty_name,
                    'source' => $transaction->source,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Failed to create manual transaction', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to create transaction',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Create a manual bank account (no PSD2/OAuth required)
     */
    public function storeManualAccount(Request $request): JsonResponse
    {
        try {
            $company = $this->resolveCompany($request);

            if (! $company) {
                return response()->json(['error' => 'No company found'], 404);
            }

            $validated = $request->validate([
                'bank_name' => 'required|string|max:255',
                'account_number' => 'required|string|max:50',
                'iban' => 'nullable|string|max:34',
                'currency' => 'nullable|string|max:3',
                'opening_balance' => 'nullable|numeric',
            ]);

            $currencyCode = $validated['currency'] ?? 'MKD';
            $currency = Currency::where('code', $currencyCode)->first();

            if (! $currency) {
                return response()->json(['error' => "Currency '{$currencyCode}' not found"], 422);
            }

            $account = BankAccount::create([
                'company_id' => $company->id,
                'bank_name' => $validated['bank_name'],
                'account_name' => $validated['bank_name'].' - '.$validated['account_number'],
                'account_number' => $validated['account_number'],
                'iban' => $validated['iban'] ?? null,
                'currency_id' => $currency->id,
                'currency' => $currencyCode,
                'account_type' => BankAccount::TYPE_BUSINESS,
                'opening_balance' => $validated['opening_balance'] ?? 0,
                'current_balance' => $validated['opening_balance'] ?? 0,
                'is_active' => true,
                'is_primary' => BankAccount::forCompany($company->id)->count() === 0,
                'status' => BankAccount::STATUS_ACTIVE,
            ]);

            Log::info('Manual bank account created', [
                'company_id' => $company->id,
                'account_id' => $account->id,
                'bank_name' => $account->bank_name,
            ]);

            return response()->json([
                'message' => 'Bank account created successfully',
                'data' => [
                    'id' => $account->id,
                    'bank_name' => $account->bank_name,
                    'account_number' => $account->account_number,
                    'iban' => $account->iban,
                    'currency' => $account->currency,
                ],
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Failed to create manual bank account', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to create bank account',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    // CLAUDE-CHECKPOINT

    /**
     * Suggest an expense category for a bank transaction using AI or keyword matching.
     *
     * Called by TransactionCategorization.vue to provide an AI-powered suggestion
     * before the user manually selects a category.
     *
     * @param  Request  $request  Expects: description, amount, counterparty (optional: transaction_id)
     * @return JsonResponse { suggestion: { category_id, category_name, confidence } }
     */
    public function suggestCategory(Request $request): JsonResponse
    {
        try {
            $company = $this->resolveCompany($request);

            if (! $company) {
                return response()->json([
                    'suggestion' => null,
                    'message' => 'No company found for user',
                ], 200);
            }

            $request->validate([
                'description' => 'nullable|string|max:1000',
                'amount' => 'nullable|numeric',
                'counterparty' => 'nullable|string|max:500',
                'transaction_id' => 'nullable|integer',
            ]);

            $description = $request->input('description', '');
            $amount = $request->input('amount', 0);
            $counterparty = $request->input('counterparty', '');

            // Fetch available expense categories for this company
            $categories = ExpenseCategory::where('company_id', $company->id)->get();

            if ($categories->isEmpty()) {
                return response()->json([
                    'suggestion' => null,
                    'message' => 'No expense categories configured',
                ], 200);
            }

            // Try AI-based suggestion first, fall back to keyword matching
            $suggestion = $this->tryAiCategorySuggestion(
                $description,
                $amount,
                $counterparty,
                $categories
            );

            if (! $suggestion) {
                $suggestion = $this->keywordCategorySuggestion(
                    $description,
                    $amount,
                    $counterparty,
                    $categories
                );
            }

            Log::info('Category suggestion generated', [
                'company_id' => $company->id,
                'description' => $description,
                'suggestion' => $suggestion,
                'method' => $suggestion ? ($suggestion['method'] ?? 'unknown') : 'none',
            ]);

            // Remove internal 'method' key before sending to client
            if ($suggestion && isset($suggestion['method'])) {
                unset($suggestion['method']);
            }

            return response()->json([
                'suggestion' => $suggestion,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to suggest category', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'suggestion' => null,
                'message' => config('app.debug') ? $e->getMessage() : 'Failed to generate suggestion',
            ], 200);
        }
    }

    /**
     * Attempt AI-based category suggestion using the configured AI provider.
     *
     * @param  string  $description  Transaction description
     * @param  float  $amount  Transaction amount
     * @param  string  $counterparty  Counterparty name
     * @param  \Illuminate\Support\Collection  $categories  Available expense categories
     * @return array|null { category_id, category_name, confidence, method } or null
     */
    private function tryAiCategorySuggestion(
        string $description,
        float $amount,
        string $counterparty,
        $categories
    ): ?array {
        try {
            $aiProvider = $this->resolveAiProvider();

            // If AI is not configured (NullAiProvider), skip
            if ($aiProvider instanceof NullAiProvider) {
                return null;
            }

            $categoryList = $categories->map(fn ($c) => "{$c->id}: {$c->name}")->implode("\n");

            $locale = request()->input('locale', app()->getLocale() ?: 'mk');
            $localeNames = ['mk' => 'Macedonian', 'sq' => 'Albanian', 'tr' => 'Turkish', 'en' => 'English'];
            $langName = $localeNames[$locale] ?? 'Macedonian';

            $prompt = <<<PROMPT
You are an accounting assistant. Categorize the following bank transaction into ONE of the expense categories listed below.

Transaction details:
- Description: {$description}
- Amount: {$amount}
- Counterparty: {$counterparty}

Available categories:
{$categoryList}

Respond with ONLY a JSON object in this exact format (no markdown, no explanation):
{"category_id": <number>, "category_name": "<name>", "confidence": <0.0 to 1.0>}

Choose the most appropriate category. Set confidence between 0.5 and 0.95 based on how certain you are.
PROMPT;

            $response = $aiProvider->generate($prompt, [
                'max_tokens' => 150,
                'temperature' => 0.2,
            ]);

            // Parse JSON from AI response
            $response = trim($response);
            // Strip markdown code fences if present
            $response = preg_replace('/^```(?:json)?\s*/i', '', $response);
            $response = preg_replace('/\s*```$/', '', $response);
            $response = trim($response);

            $parsed = json_decode($response, true);

            if (
                $parsed
                && isset($parsed['category_id'], $parsed['category_name'], $parsed['confidence'])
                && $categories->contains('id', $parsed['category_id'])
            ) {
                return [
                    'category_id' => (int) $parsed['category_id'],
                    'category_name' => (string) $parsed['category_name'],
                    'confidence' => (float) min(0.95, max(0.0, $parsed['confidence'])),
                    'method' => 'ai',
                ];
            }

            Log::warning('AI category suggestion returned unparseable response', [
                'response' => $response,
            ]);

            return null;
        } catch (\Throwable $e) {
            Log::warning('AI category suggestion failed, falling back to keywords', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Keyword-based fallback for category suggestion.
     *
     * Maps common Macedonian and English transaction keywords to expense categories.
     *
     * @param  string  $description  Transaction description
     * @param  float  $amount  Transaction amount
     * @param  string  $counterparty  Counterparty name
     * @param  \Illuminate\Support\Collection  $categories  Available expense categories
     * @return array|null { category_id, category_name, confidence, method } or null
     */
    private function keywordCategorySuggestion(
        string $description,
        float $amount,
        string $counterparty,
        $categories
    ): ?array {
        $text = mb_strtolower($description.' '.$counterparty);

        // Keyword → category name patterns (supports both Macedonian and English terms)
        $keywordMap = [
            // Utilities / Комунални услуги
            'евн|evn|електрична|electricity|струја|stream' => ['Utilities', 'Комунални', 'Комунални услуги'],
            'водовод|water|вода' => ['Utilities', 'Комунални', 'Комунални услуги'],
            'топлинска|heating|греење|парно' => ['Utilities', 'Комунални', 'Комунални услуги'],
            'телеком|telecom|а1|a1|мобилен|mobile|phone|интернет|internet' => ['Telecommunications', 'Телекомуникации', 'Телефон'],

            // Rent / Кирија
            'кирија|наем|rent|закуп' => ['Rent', 'Кирија', 'Наем'],

            // Office supplies / Канцелариски материјали
            'канцелариски|office supplies|тонер|toner|хартија|paper|пенкало' => ['Office Supplies', 'Канцелариски материјали', 'Канцелариски'],

            // Transport / Транспорт
            'горив|fuel|бензин|petrol|дизел|diesel|паркинг|parking|такси|taxi|транспорт|transport' => ['Transport', 'Транспорт', 'Горива', 'Fuel'],

            // Food / Храна
            'ресторан|restaurant|кафе|cafe|coffee|храна|food|угостител|catering' => ['Meals', 'Храна', 'Угостителство', 'Food & Dining'],

            // Insurance / Осигурување
            'осигурување|insurance|полиса' => ['Insurance', 'Осигурување'],

            // Bank fees / Банкарски провизии
            'провизија|commission|fee|банкарски|bank fee|maintenance fee' => ['Bank Fees', 'Банкарски провизии', 'Провизии'],

            // Salary / Плата
            'плата|salary|плати|wages|придонес|contribution|пензиско|pension|здравствено|health' => ['Salaries', 'Плата', 'Плати', 'Payroll'],

            // Software / Софтвер
            'software|софтвер|лиценца|license|subscription|претплата|saas|cloud' => ['Software', 'Софтвер', 'Лиценци'],

            // Legal & Accounting / Правни и сметководствени
            'адвокат|lawyer|legal|нотар|notar|сметководство|accounting|ревизија|audit' => ['Professional Services', 'Правни услуги', 'Сметководство'],

            // Marketing / Маркетинг
            'маркетинг|marketing|реклама|advertising|google ads|facebook|промоција|promo' => ['Marketing', 'Маркетинг', 'Реклама'],

            // Equipment / Опрема
            'опрема|equipment|компјутер|computer|лаптоп|laptop|монитор|monitor|печатач|printer' => ['Equipment', 'Опрема', 'IT Equipment'],

            // Taxes / Даноци
            'данок|tax|ддв|vat|ujp|управа за јавни приходи' => ['Taxes', 'Даноци', 'ДДВ'],

            // Travel / Патување
            'хотел|hotel|авион|flight|патување|travel|booking|airbnb' => ['Travel', 'Патување'],
        ];

        $categoryIndex = $categories->keyBy(function ($cat) {
            return mb_strtolower($cat->name);
        });

        foreach ($keywordMap as $pattern => $possibleNames) {
            if (preg_match('/('.$pattern.')/iu', $text)) {
                // Try to find a matching category from the company's categories
                foreach ($possibleNames as $name) {
                    $lowerName = mb_strtolower($name);
                    if ($categoryIndex->has($lowerName)) {
                        $matched = $categoryIndex->get($lowerName);

                        return [
                            'category_id' => $matched->id,
                            'category_name' => $matched->name,
                            'confidence' => 0.65,
                            'method' => 'keyword',
                        ];
                    }
                }

                // If no exact match, try partial match on category names
                foreach ($possibleNames as $name) {
                    $lowerName = mb_strtolower($name);
                    foreach ($categoryIndex as $catLower => $cat) {
                        if (str_contains($catLower, $lowerName) || str_contains($lowerName, $catLower)) {
                            return [
                                'category_id' => $cat->id,
                                'category_name' => $cat->name,
                                'confidence' => 0.55,
                                'method' => 'keyword',
                            ];
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Resolve the configured AI provider for category suggestions.
     */
    private function resolveAiProvider(): AiProviderInterface
    {
        $provider = strtolower((string) config('ai.default_provider', 'claude'));

        try {
            return match ($provider) {
                'claude' => new ClaudeProvider,
                'openai' => new OpenAiProvider,
                'gemini' => new GeminiProvider,
                default => throw new \RuntimeException("Unsupported AI provider: {$provider}"),
            };
        } catch (\Throwable $e) {
            Log::warning('AI provider unavailable for category suggestion', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return new NullAiProvider($provider, $e->getMessage());
        }
    }

    /**
     * Delete a single bank transaction.
     */
    public function deleteTransaction(Request $request, int $id): JsonResponse
    {
        $company = $this->resolveCompany($request);
        if (! $company) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $transaction = BankTransaction::where('company_id', $company->id)
            ->where('id', $id)
            ->firstOrFail();

        if ($transaction->matched_invoice_id) {
            return response()->json([
                'error' => true,
                'message' => 'Cannot delete a matched transaction. Unmatch it first.',
            ], 422);
        }

        $accountId = $transaction->bank_account_id;
        $transaction->delete();

        $this->recalculateAccountBalance($accountId);

        return response()->json(['success' => true, 'message' => 'Transaction deleted']);
    }

    /**
     * Bulk delete bank transactions.
     */
    public function bulkDeleteTransactions(Request $request): JsonResponse
    {
        $company = $this->resolveCompany($request);
        if (! $company) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $query = BankTransaction::where('company_id', $company->id)
            ->whereIn('id', $request->ids)
            ->whereNull('matched_invoice_id');

        // Get affected account IDs before deletion
        $affectedAccountIds = (clone $query)->distinct()->pluck('bank_account_id')->toArray();

        $count = $query->count();
        $query->delete();

        // Recalculate balance for affected accounts
        foreach ($affectedAccountIds as $accountId) {
            $this->recalculateAccountBalance($accountId);
        }

        $skipped = count($request->ids) - $count;

        return response()->json([
            'success' => true,
            'deleted' => $count,
            'skipped' => $skipped,
            'message' => $skipped > 0
                ? "Deleted {$count} transactions. {$skipped} matched transactions were skipped."
                : "Deleted {$count} transactions.",
        ]);
    }

    /**
     * Delete all transactions from a specific import.
     */
    public function deleteImport(Request $request, int $logId): JsonResponse
    {
        $company = $this->resolveCompany($request);
        if (! $company) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $importLog = \App\Models\BankImportLog::where('company_id', $company->id)
            ->where('id', $logId)
            ->firstOrFail();

        // Delete unmatched transactions from this import (matched ones are skipped)
        $query = BankTransaction::where('company_id', $company->id)
            ->where('source', BankTransaction::SOURCE_CSV_IMPORT)
            ->whereJsonContains('raw_data->import_log_id', $importLog->id);

        // If raw_data doesn't have import_log_id, fall back to date/bank matching
        $count = $query->count();

        if ($count === 0) {
            // Fallback: delete by created_at range around the import time
            $importTime = $importLog->created_at;
            $count = BankTransaction::where('company_id', $company->id)
                ->whereNull('matched_invoice_id')
                ->where('created_at', '>=', $importTime->copy()->subMinutes(1))
                ->where('created_at', '<=', $importTime->copy()->addMinutes(5))
                ->delete();
        } else {
            $query->whereNull('matched_invoice_id')->delete();
        }

        // Mark import log as deleted
        $importLog->update(['status' => 'deleted']);

        // Recalculate balance for all company accounts
        $accounts = BankAccount::where('company_id', $company->id)->get();
        foreach ($accounts as $acc) {
            $this->recalculateAccountBalance($acc->id);
        }

        return response()->json([
            'success' => true,
            'deleted' => $count,
            'message' => "Deleted {$count} transactions from this import.",
        ]);
    }

    /**
     * Unmatch a transaction from its invoice (reverse a match).
     */
    public function unmatchTransaction(Request $request, int $id): JsonResponse
    {
        $company = $this->resolveCompany($request);
        if (! $company) {
            return response()->json(['error' => 'No company found'], 404);
        }

        $transaction = BankTransaction::where('company_id', $company->id)
            ->where('id', $id)
            ->firstOrFail();

        if (! $transaction->matched_invoice_id) {
            return response()->json(['error' => true, 'message' => 'Transaction is not matched'], 422);
        }

        // Remove the payment that was created for this match
        if ($transaction->matched_payment_id) {
            $payment = \App\Models\Payment::find($transaction->matched_payment_id);
            if ($payment) {
                // Recalculate invoice paid status
                $invoice = \App\Models\Invoice::find($transaction->matched_invoice_id);
                if ($invoice) {
                    $remainingPaid = $invoice->payments()->where('id', '!=', $payment->id)->sum('amount');
                    if ($remainingPaid <= 0) {
                        $invoice->update([
                            'status' => Invoice::STATUS_SENT,
                            'paid_status' => Invoice::STATUS_UNPAID,
                        ]);
                    } elseif ($remainingPaid < $invoice->total) {
                        $invoice->update(['paid_status' => Invoice::STATUS_PARTIALLY_PAID]);
                    }
                }
                $payment->delete();
            }
        }

        $transaction->update([
            'matched_invoice_id' => null,
            'matched_payment_id' => null,
            'matched_at' => null,
            'match_confidence' => null,
            'processing_status' => BankTransaction::STATUS_UNPROCESSED,
            'processed_at' => null,
        ]);

        return response()->json(['success' => true, 'message' => 'Transaction unmatched successfully']);
    }

    /**
     * Export transactions as CSV.
     */
    public function exportTransactions(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $company = $this->resolveCompany($request);
        if (! $company) {
            abort(404, 'No company found');
        }

        $query = BankTransaction::where('company_id', $company->id)
            ->orderBy('transaction_date', 'desc');

        if ($request->account_id) {
            $query->where('bank_account_id', $request->account_id);
        }
        if ($request->from_date) {
            $query->where('transaction_date', '>=', $request->from_date);
        }
        if ($request->to_date) {
            $query->where('transaction_date', '<=', $request->to_date);
        }
        if ($request->type) {
            $query->where('transaction_type', $request->type);
        }

        $transactions = $query->get();

        $filename = 'transactions_'.$company->id.'_'.date('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');
            // BOM for Excel UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Date', 'Description', 'Counterparty', 'Amount', 'Type', 'Currency', 'Reference', 'Status']);

            foreach ($transactions as $tx) {
                $amount = $tx->transaction_type === 'debit' ? -$tx->amount : $tx->amount;
                fputcsv($handle, [
                    $tx->transaction_date,
                    $tx->description,
                    $tx->debtor_name ?? $tx->creditor_name,
                    $amount,
                    $tx->transaction_type,
                    $tx->currency,
                    $tx->transaction_reference,
                    $tx->processing_status,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
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

        if (! $user) {
            return null;
        }

        $companyIdHeader = $request->header('company');
        $companyId = $companyIdHeader !== null ? (int) $companyIdHeader : null;
        $company = null;

        if ($companyId) {
            // Super admins can access any company (matches pattern in other controllers)
            if ($user->role === 'super admin') {
                $company = Company::find($companyId);
            } elseif ($user->hasCompany($companyId)) {
                $company = $user->companies()->where('companies.id', $companyId)->first();
            }
        }

        if (! $company) {
            $company = $user->companies()->first();
        }

        return $this->currentCompany = $company;
    }

    /**
     * Bulk categorize selected transactions.
     */
    public function bulkCategorize(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:bank_transactions,id',
            'category' => 'required|string|max:50',
        ]);

        $company = $this->getCompany();

        $updated = BankTransaction::forCompany($company->id)
            ->whereIn('id', $request->ids)
            ->update(['ai_category' => $request->category]);

        return response()->json([
            'success' => true,
            'message' => "{$updated} transactions categorized",
            'updated' => $updated,
        ]);
    }

    /**
     * Generate IOS (open items statement) PDF for a customer.
     */
    public function generateIos(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $company = $this->getCompany();
        $customer = \App\Models\Customer::where('company_id', $company->id)
            ->where('id', $request->customer_id)
            ->firstOrFail();

        $service = new \Modules\Mk\Services\IosPdfService;
        $pdf = $service->generateForCustomer($company, $customer, $request->from, $request->to);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="IOS-'.$customer->name.'.pdf"',
        ]);
    }

    /**
     * Generate bank statement PDF report.
     */
    public function bankStatementReport(Request $request)
    {
        $request->validate([
            'account_id' => 'required|integer|exists:bank_accounts,id',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        $company = $this->getCompany();
        $account = BankAccount::where('company_id', $company->id)
            ->where('id', $request->account_id)
            ->firstOrFail();

        $from = $request->from ? \Carbon\Carbon::parse($request->from) : \Carbon\Carbon::now()->startOfMonth();
        $to = $request->to ? \Carbon\Carbon::parse($request->to) : \Carbon\Carbon::now();

        $transactions = BankTransaction::forCompany($company->id)
            ->where('bank_account_id', $account->id)
            ->whereBetween('transaction_date', [$from, $to])
            ->orderBy('transaction_date')
            ->get();

        $openingBalance = (float) ($account->opening_balance ?? 0);
        $txData = [];
        $totalCredit = 0;
        $totalDebit = 0;

        foreach ($transactions as $tx) {
            $amount = (float) $tx->amount;
            if ($tx->transaction_type === 'credit') {
                $totalCredit += $amount;
            } else {
                $totalDebit += $amount;
            }
            $txData[] = [
                'date' => \Carbon\Carbon::parse($tx->transaction_date)->format('d.m.Y'),
                'description' => $tx->description ?? '',
                'counterparty' => $tx->creditor_name ?? $tx->debtor_name ?? '',
                'reference' => $tx->transaction_reference ?? '',
                'type' => $tx->transaction_type ?? 'debit',
                'amount' => $amount,
            ];
        }

        $pdf = \PDF::loadView('app.pdf.reports.bank-statement', [
            'company' => $company,
            'account' => $account,
            'bank_name' => $account->bank_name ?? '',
            'account_number' => $account->account_number ?? '',
            'iban' => $account->iban ?? '',
            'currency' => 'МКД',
            'transactions' => $txData,
            'opening_balance' => $openingBalance,
            'total_credit' => $totalCredit,
            'total_debit' => $totalDebit,
            'period_from' => $from->format('d.m.Y'),
            'period_to' => $to->format('d.m.Y'),
            'date' => now()->format('d.m.Y'),
        ]);
        $pdf->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="bank-statement.pdf"',
        ]);
    }

    /**
     * Generate PP10 (collection order) PDF.
     */
    public function generatePp10(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'integer|exists:invoices,id',
        ]);

        $company = $this->getCompany();
        $customer = \App\Models\Customer::where('company_id', $company->id)
            ->where('id', $request->customer_id)
            ->firstOrFail();

        $invoices = \App\Models\Invoice::where('company_id', $company->id)
            ->whereIn('id', $request->invoice_ids)
            ->get();

        $bankAccount = BankAccount::where('company_id', $company->id)->first();
        $service = new \Modules\Mk\Services\Pp30PdfService;

        $slips = [];
        foreach ($invoices as $inv) {
            $slips[] = [
                'creditor_name' => $company->name ?? '',
                'creditor_iban' => $service->formatIban($bankAccount?->iban ?? ''),
                'creditor_bank' => $bankAccount?->bank_name ?? '',
                'debtor_name' => $customer->name ?? '',
                'debtor_iban' => $service->formatIban($customer->iban ?? ''),
                'debtor_bank' => '',
                'amount' => (int) $inv->total,
                'amount_formatted' => number_format($inv->total / 100, 2, ',', '.'),
                'amount_words' => $service->amountToWords((int) $inv->total),
                'currency_code' => 'MKD',
                'description' => 'Наплата по фактура '.($inv->invoice_number ?? ''),
                'date' => now()->format('d.m.Y'),
                'bill_number' => $inv->invoice_number ?? '',
            ];
        }

        $pdf = $service->generatePp10($slips);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="PP10.pdf"',
        ]);
    }

    /**
     * Generate compensation (компензација) PDF.
     */
    public function generateCompensation(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'receivable_invoice_ids' => 'nullable|array',
            'payable_bill_ids' => 'nullable|array',
        ]);

        $company = $this->getCompany();
        $customer = \App\Models\Customer::where('company_id', $company->id)
            ->where('id', $request->customer_id)
            ->firstOrFail();

        $bankAccount = BankAccount::where('company_id', $company->id)->first();

        $receivables = [];
        $totalReceivables = 0;
        if ($request->receivable_invoice_ids) {
            $invoices = \App\Models\Invoice::where('company_id', $company->id)
                ->whereIn('id', $request->receivable_invoice_ids)
                ->get();
            foreach ($invoices as $inv) {
                $amount = (float) $inv->total / 100;
                $receivables[] = [
                    'document_number' => $inv->invoice_number,
                    'date' => $inv->invoice_date ? \Carbon\Carbon::parse($inv->invoice_date)->format('d.m.Y') : '-',
                    'due_date' => $inv->due_date ? \Carbon\Carbon::parse($inv->due_date)->format('d.m.Y') : '-',
                    'amount' => $amount,
                ];
                $totalReceivables += $amount;
            }
        }

        $payables = [];
        $totalPayables = 0;
        if ($request->payable_bill_ids) {
            $bills = \App\Models\Bill::where('company_id', $company->id)
                ->whereIn('id', $request->payable_bill_ids)
                ->get();
            foreach ($bills as $bill) {
                $amount = (float) $bill->total / 100;
                $payables[] = [
                    'document_number' => $bill->bill_number,
                    'date' => $bill->bill_date ? \Carbon\Carbon::parse($bill->bill_date)->format('d.m.Y') : '-',
                    'due_date' => $bill->due_date ? \Carbon\Carbon::parse($bill->due_date)->format('d.m.Y') : '-',
                    'amount' => $amount,
                ];
                $totalPayables += $amount;
            }
        }

        $compensationAmount = min($totalReceivables, $totalPayables);

        $pdf = \PDF::loadView('app.pdf.reports.kompenzacija', [
            'party_a_name' => $company->name,
            'party_a_vat' => $company->vat_number ?? '-',
            'party_a_tax_id' => $company->edb ?? '-',
            'party_a_address' => $company->address_street_1 ?? '',
            'party_a_account' => $bankAccount?->iban ?? $bankAccount?->account_number ?? '-',
            'party_b_name' => $customer->name,
            'party_b_vat' => $customer->vat_number ?? '-',
            'party_b_tax_id' => $customer->tax_id ?? '-',
            'party_b_address' => trim(($customer->billing_address_street_1 ?? '').', '.($customer->billing_city ?? ''), ', '),
            'party_b_account' => $customer->iban ?? '-',
            'receivables' => $receivables,
            'payables' => $payables,
            'total_receivables' => $totalReceivables,
            'total_payables' => $totalPayables,
            'compensation_amount' => $compensationAmount,
            'document_number' => 'KOMP-'.now()->format('Y-md'),
            'date' => now()->format('d.m.Y'),
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="kompenzacija.pdf"',
        ]);
    }

    /**
     * Generate PP40 (promissory note / меница) PDF.
     */
    public function generatePp40(Request $request)
    {
        $request->validate([
            'payee_name' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'maturity_date' => 'required|date',
        ]);

        $company = $this->getCompany();
        $bankAccount = BankAccount::where('company_id', $company->id)->first();
        $pp30 = new \Modules\Mk\Services\Pp30PdfService;

        $amountCents = (int) round($request->amount * 100);

        $pdf = \PDF::loadView('app.pdf.reports.pp40', [
            'issuer_name' => $company->name,
            'issuer_vat' => $company->vat_number ?? '-',
            'issuer_address' => $company->address_street_1 ?? '',
            'issuer_account' => $bankAccount?->iban ?? '-',
            'payee_name' => $request->payee_name,
            'payee_vat' => $request->payee_vat ?? '-',
            'payee_address' => $request->payee_address ?? '',
            'payee_account' => $request->payee_account ?? '-',
            'amount' => $amountCents,
            'amount_words' => $pp30->amountToWords($amountCents),
            'maturity_date' => \Carbon\Carbon::parse($request->maturity_date)->format('d.m.Y'),
            'issue_date' => now()->format('d.m.Y'),
            'issue_place' => $request->issue_place ?? 'Скопје',
            'payment_place' => $request->payment_place ?? 'Скопје',
            'note_number' => $request->note_number ?? 'МЕН-'.now()->format('Y-md'),
            'currency' => 'МКД',
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="menica-pp40.pdf"',
        ]);
    }

    /**
     * Export SEPA pain.001 XML for a payment batch.
     */
    public function exportSepa(Request $request, int $batch)
    {
        $company = $this->getCompany();

        $paymentBatch = \Modules\Mk\Models\PaymentBatch::where('company_id', $company->id)
            ->where('id', $batch)
            ->firstOrFail();

        $builder = new \Modules\Mk\Services\SepaXmlBuilder;
        $xml = $builder->build($paymentBatch);

        $filename = 'SEPA-'.($paymentBatch->batch_number ?? $paymentBatch->id).'.xml';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Generate daily cash report PDF.
     */
    public function dailyCashReport(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
        ]);

        $company = $this->getCompany();
        $date = $request->date ? \Carbon\Carbon::parse($request->date) : \Carbon\Carbon::today();

        $payments = \App\Models\Payment::where('company_id', $company->id)
            ->whereDate('payment_date', $date)
            ->get();

        $expenses = \App\Models\Expense::where('company_id', $company->id)
            ->whereDate('expense_date', $date)
            ->get();

        $incomeItems = [];
        $totalIncome = 0;
        foreach ($payments as $pay) {
            $amount = (float) $pay->amount;
            $incomeItems[] = [
                'time' => $pay->created_at ? $pay->created_at->format('H:i') : '-',
                'document' => $pay->payment_number ?? '-',
                'description' => $pay->notes ?? 'Уплата',
                'customer' => $pay->customer?->name ?? '-',
                'amount' => $amount,
                'method' => 'Готовина',
            ];
            $totalIncome += $amount;
        }

        $expenseItems = [];
        $totalExpense = 0;
        foreach ($expenses as $exp) {
            $amount = (float) $exp->amount / 100;
            $expenseItems[] = [
                'time' => $exp->created_at ? $exp->created_at->format('H:i') : '-',
                'document' => '-',
                'description' => $exp->notes ?? 'Расход',
                'recipient' => $exp->category?->name ?? '-',
                'amount' => $amount,
                'method' => 'Готовина',
            ];
            $totalExpense += $amount;
        }

        $openingBalance = 0;
        $closingBalance = $openingBalance + $totalIncome - $totalExpense;

        $pdf = \PDF::loadView('app.pdf.reports.daily-cash-report', [
            'company' => $company,
            'date' => $date->format('d.m.Y'),
            'opening_balance' => $openingBalance,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'closing_balance' => $closingBalance,
            'income_items' => $incomeItems,
            'expense_items' => $expenseItems,
        ]);

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="daily-cash-report-'.$date->format('Y-m-d').'.pdf"',
        ]);
    }

    /**
     * Recalculate bank account balance from opening_balance + transactions.
     */
    private function recalculateAccountBalance(int $accountId): void
    {
        $account = BankAccount::find($accountId);
        if (! $account) {
            return;
        }

        $credits = BankTransaction::where('bank_account_id', $accountId)
            ->where('transaction_type', BankTransaction::TYPE_CREDIT)
            ->sum('amount');

        $debits = BankTransaction::where('bank_account_id', $accountId)
            ->where('transaction_type', BankTransaction::TYPE_DEBIT)
            ->sum('amount');

        $balance = (float) $account->opening_balance + (float) $credits - (float) $debits;
        $account->update(['current_balance' => round($balance, 2)]);
    }

    /**
     * Get the current company from the authenticated user.
     */
    protected function getCompany(): Company
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $companyIdHeader = request()->header('company');

        if ($companyIdHeader) {
            $companyId = (int) $companyIdHeader;
            if ($user->hasCompany($companyId)) {
                $company = $user->companies()->where('companies.id', $companyId)->first();
                if ($company) {
                    return $company;
                }
            }
        }

        return $user->companies()->firstOrFail();
    }
}
