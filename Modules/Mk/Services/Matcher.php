<?php

namespace Modules\Mk\Services;

use App\Models\BankTransaction;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Reconciliation;
use App\Services\Reconciliation\AiMatcherService;
use App\Services\Reconciliation\AiTransactionCategorizer;
use App\Services\Reconciliation\MatchingRulesService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Invoice-Transaction Matcher Service
 *
 * Automatically matches bank transactions with invoices based on amount, date, and reference
 * Updates invoice status to PAID when match is found and confirmed
 *
 * P0-09: Integrates with MatchingRulesService to apply user-defined rules
 * before and during the matching process.
 */
class Matcher
{
    protected $companyId;

    protected $matchingWindow; // days to look for matching transactions

    protected $amountTolerance; // percentage tolerance for amount matching

    protected string $locale = 'mk';

    /**
     * @var MatchingRulesService|null Lazily-resolved matching rules service
     */
    protected ?MatchingRulesService $matchingRulesService = null;

    /**
     * @var array|null Cached rule actions for the current transaction being processed
     */
    protected ?array $currentRuleActions = null;

    /**
     * @var float|null Override min confidence for suggest-only mode (lower threshold)
     */
    protected ?float $suggestMinConfidence = null;

    public function __construct(int $companyId, int $matchingWindow = 90, float $amountTolerance = 0.01, string $locale = 'mk')
    {
        $this->companyId = $companyId;
        $this->matchingWindow = $matchingWindow;
        $this->amountTolerance = $amountTolerance;
        $this->locale = $locale;
    }

    /**
     * Get or create the MatchingRulesService instance.
     *
     * Lazily resolved to avoid overhead when rules table doesn't exist yet.
     *
     * @return MatchingRulesService|null
     */
    protected function getMatchingRulesService(): ?MatchingRulesService
    {
        if ($this->matchingRulesService === null) {
            // Only create service if the matching_rules table exists
            if (Schema::hasTable('matching_rules')) {
                $this->matchingRulesService = new MatchingRulesService();
            }
        }

        return $this->matchingRulesService;
    }

    /**
     * Apply matching rules to a transaction and return the collected actions.
     *
     * P0-09: Evaluates all active rules against the transaction and returns
     * a flat array of action types found across all matching rules.
     *
     * @param object $transaction The bank transaction (may be stdClass from DB::table)
     * @return array Matched rule actions, or empty array if no rules match
     */
    protected function applyMatchingRules($transaction): array
    {
        $service = $this->getMatchingRulesService();

        if (! $service) {
            return [];
        }

        try {
            // The service expects a BankTransaction model, but we may have a stdClass
            // from DB::table(). Convert if needed.
            if (! ($transaction instanceof BankTransaction)) {
                $bankTransaction = BankTransaction::find($transaction->id);
                if (! $bankTransaction) {
                    return [];
                }
            } else {
                $bankTransaction = $transaction;
            }

            return $service->applyRules($bankTransaction, $this->companyId);
        } catch (\Throwable $e) {
            Log::warning('Failed to apply matching rules', [
                'transaction_id' => $transaction->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Check if any matched rule actions contain a specific action type.
     *
     * @param array $ruleResults The results from applyMatchingRules()
     * @param string $actionType The action type to look for (e.g., 'ignore', 'auto_match')
     * @return bool
     */
    protected function hasRuleAction(array $ruleResults, string $actionType): bool
    {
        foreach ($ruleResults as $result) {
            foreach ($result['actions'] as $action) {
                if (($action['action'] ?? '') === $actionType) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the customer_id from match_customer rule actions, if any.
     *
     * @param array $ruleResults The results from applyMatchingRules()
     * @return int|null The customer_id from the highest-priority matching rule
     */
    protected function getRuleCustomerId(array $ruleResults): ?int
    {
        foreach ($ruleResults as $result) {
            foreach ($result['actions'] as $action) {
                if (($action['action'] ?? '') === 'match_customer' && ! empty($action['customer_id'])) {
                    return (int) $action['customer_id'];
                }
            }
        }

        return null;
    }

    /**
     * Match all unmatched bank transactions with unpaid invoices.
     *
     * P0-09: Before matching, applies user-defined rules to each transaction.
     * - If a rule has 'ignore' action, the transaction is skipped and marked as ignored.
     * - If a rule has 'match_customer' action, that customer's invoices are prioritized.
     * - If a rule has 'auto_match' action, the confidence threshold may be lowered.
     */
    public function matchAllTransactions(): array
    {
        Log::info('Starting transaction matching process', [
            'company_id' => $this->companyId,
            'matching_window' => $this->matchingWindow,
            'amount_tolerance' => $this->amountTolerance,
        ]);

        $unmatchedTransactions = $this->getUnmatchedTransactions();
        $unpaidInvoices = $this->getUnpaidInvoices();

        $matches = [];
        $totalMatched = 0;
        $totalIgnored = 0;

        foreach ($unmatchedTransactions as $transaction) {
            // P0-09: Apply matching rules before processing
            $ruleResults = $this->applyMatchingRules($transaction);
            $this->currentRuleActions = $ruleResults;

            // P0-09: If an 'ignore' rule matches, skip this transaction
            if ($this->hasRuleAction($ruleResults, 'ignore')) {
                Log::debug('Transaction ignored by matching rule', [
                    'transaction_id' => $transaction->id,
                ]);

                // Mark transaction as ignored
                DB::table('bank_transactions')
                    ->where('id', $transaction->id)
                    ->update([
                        'processing_status' => 'ignored',
                        'processing_notes' => json_encode(['ignored_by_rule' => true]),
                        'processed_at' => now(),
                        'updated_at' => now(),
                    ]);

                $totalIgnored++;

                continue;
            }

            // P0-09: If a 'match_customer' rule matches, filter invoices to that customer
            $ruleCustomerId = $this->getRuleCustomerId($ruleResults);
            $invoicePool = $unpaidInvoices;

            if ($ruleCustomerId) {
                $customerInvoices = $unpaidInvoices->filter(function ($invoice) use ($ruleCustomerId) {
                    return $invoice->customer_id === $ruleCustomerId;
                });

                // If customer has unpaid invoices, use those; otherwise fall back to full pool
                if ($customerInvoices->isNotEmpty()) {
                    $invoicePool = $customerInvoices;
                }
            }

            $matchedInvoice = $this->findMatchingInvoice($transaction, $invoicePool);

            if (! $matchedInvoice) {
                // Log best score for debugging when no match found
                $bestDebugScore = 0;
                $bestDebugInvoice = null;
                foreach ($invoicePool as $inv) {
                    $s = $this->calculateMatchScore($transaction, $inv);
                    if ($s > $bestDebugScore) {
                        $bestDebugScore = $s;
                        $bestDebugInvoice = $inv;
                    }
                }
                Log::debug('No match found for transaction', [
                    'tx_id' => $transaction->id,
                    'tx_amount' => $transaction->amount,
                    'tx_debtor' => $transaction->debtor_name ?? 'null',
                    'best_score' => round($bestDebugScore, 3),
                    'best_invoice' => $bestDebugInvoice ? $bestDebugInvoice->invoice_number : 'none',
                    'best_inv_total' => $bestDebugInvoice ? $bestDebugInvoice->total : 0,
                    'invoice_pool_size' => $invoicePool->count(),
                ]);
            }

            if ($matchedInvoice) {
                $success = $this->createPaymentRecord($transaction, $matchedInvoice);

                if ($success) {
                    $matches[] = [
                        'transaction_id' => $transaction->id,
                        'invoice_id' => $matchedInvoice->id,
                        'amount' => $transaction->amount,
                        'confidence' => $this->calculateMatchConfidence($transaction, $matchedInvoice),
                    ];

                    $totalMatched++;

                    // Remove matched invoice from pool to avoid duplicate matching
                    $unpaidInvoices = $unpaidInvoices->reject(function ($invoice) use ($matchedInvoice) {
                        return $invoice->id === $matchedInvoice->id;
                    });
                }
            }

            // Reset cached rule actions
            $this->currentRuleActions = null;
        }

        // Layer 4: AI Categorization — categorize remaining unmatched transactions
        $totalCategorized = 0;
        $unmatchedIds = collect($unmatchedTransactions)
            ->pluck('id')
            ->diff(collect($matches)->pluck('transaction_id'))
            ->values();

        if ($unmatchedIds->isNotEmpty()) {
            $uncategorized = BankTransaction::whereIn('id', $unmatchedIds)
                ->whereNull('ai_category')
                ->get();

            if ($uncategorized->isNotEmpty()) {
                try {
                    $categorizer = app(AiTransactionCategorizer::class);
                    $catResults = $categorizer->categorizeBatch($uncategorized, $this->locale);
                    $totalCategorized = count($catResults);
                } catch (\Exception $e) {
                    Log::debug('[Matcher] AI categorization skipped', ['error' => $e->getMessage()]);
                }
            }
        }

        Log::info('Transaction matching completed', [
            'company_id' => $this->companyId,
            'total_matched' => $totalMatched,
            'total_ignored' => $totalIgnored,
            'total_categorized' => $totalCategorized,
            'matches' => $matches,
        ]);

        return $matches;
    }

    /**
     * Match a specific transaction with invoices (creates payment)
     */
    public function matchTransaction($transaction): ?array
    {
        $unpaidInvoices = $this->getUnpaidInvoices();
        $matchedInvoice = $this->findMatchingInvoice($transaction, $unpaidInvoices);

        if ($matchedInvoice) {
            $success = $this->createPaymentRecord($transaction, $matchedInvoice);

            if ($success) {
                return [
                    'transaction_id' => $transaction->id,
                    'invoice_id' => $matchedInvoice->id,
                    'amount' => $transaction->amount,
                    'confidence' => $this->calculateMatchConfidence($transaction, $matchedInvoice),
                    'invoice_number' => $matchedInvoice->invoice_number,
                    'invoice_total' => (float) $matchedInvoice->total,
                ];
            }
        }

        return null;
    }

    /**
     * Suggest a match for a transaction without creating a payment.
     * Used for displaying suggested matches in the UI.
     *
     * Enhanced: includes AI match reason and match_type when AI was used.
     */
    public function suggestMatch($transaction): ?array
    {
        $this->lastAiResult = null;
        $unpaidInvoices = $this->getUnpaidInvoices();

        // Use lower threshold for suggestions (user must always verify)
        $savedMinConfidence = $this->suggestMinConfidence;
        $this->suggestMinConfidence = 0.4;
        $matchedInvoice = $this->findMatchingInvoice($transaction, $unpaidInvoices);
        $this->suggestMinConfidence = $savedMinConfidence;

        if ($matchedInvoice) {
            $result = [
                'transaction_id' => $transaction->id,
                'invoice_id' => $matchedInvoice->id,
                'amount' => (float) $transaction->amount,
                'confidence' => $this->calculateMatchConfidence($transaction, $matchedInvoice),
                'invoice_number' => $matchedInvoice->invoice_number,
                'invoice_total' => (float) $matchedInvoice->total,
                'match_type' => Reconciliation::MATCH_TYPE_AUTO,
            ];

            // If AI was used, enrich the result
            if ($this->lastAiResult && isset($this->lastAiResult['best_match'])) {
                $aiBest = $this->lastAiResult['best_match'];
                if ($aiBest['invoice_id'] === $matchedInvoice->id) {
                    $result['confidence'] = $aiBest['confidence'];
                    $result['ai_reason'] = $aiBest['reason'] ?? '';
                    $result['match_type'] = Reconciliation::MATCH_TYPE_AI;
                    $result['is_split'] = $this->lastAiResult['is_split'] ?? false;

                    // Include all matches if split detected
                    if ($result['is_split'] && count($this->lastAiResult['matches'] ?? []) > 1) {
                        $result['split_matches'] = $this->lastAiResult['matches'];
                    }
                }
            }

            return $result;
        }

        return null;
    }

    /**
     * Categorize an unmatched transaction using AI.
     *
     * Layer 4: When no invoice match is found, classify the transaction
     * into an accounting category (salary, tax, bank_fee, etc.).
     *
     * @param  BankTransaction  $transaction
     * @return array|null  ['category' => string, 'confidence' => float, 'reason' => string]
     */
    public function categorizeTransaction(BankTransaction $transaction): ?array
    {
        try {
            $categorizer = app(AiTransactionCategorizer::class);

            return $categorizer->categorize($transaction);
        } catch (\Exception $e) {
            Log::debug('[Matcher] AI categorization unavailable', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Get unmatched bank transactions
     */
    protected function getUnmatchedTransactions()
    {
        return DB::table('bank_transactions')
            ->where('company_id', $this->companyId)
            ->whereNull('matched_invoice_id')
            ->where('transaction_type', 'credit') // Only incoming payments
            ->where('transaction_date', '>=', Carbon::now()->subDays($this->matchingWindow))
            ->orderBy('transaction_date', 'desc')
            ->get();
    }

    /**
     * Get unpaid invoices
     */
    protected function getUnpaidInvoices()
    {
        return Invoice::where('company_id', $this->companyId)
            ->whereIn('status', [
                Invoice::STATUS_DRAFT,
                Invoice::STATUS_SENT,
                Invoice::STATUS_VIEWED,
                Invoice::STATUS_PARTIALLY_PAID,
            ])
            ->where('due_date', '>=', Carbon::now()->subDays($this->matchingWindow * 2))
            ->orderBy('due_date', 'desc')
            ->get();
    }

    /**
     * Find matching invoice for a transaction.
     *
     * P0-09: If an 'auto_match' rule is active for this transaction,
     * the confidence threshold is lowered to the rule's configured value
     * (default 0.5 instead of 0.7), and the score is boosted by +0.15.
     *
     * AI Enhancement: If deterministic score < 0.9, calls AiMatcherService
     * to boost confidence via Gemini analysis. AI results are stored as
     * suggestions — the best match is returned for the same flow.
     */
    protected function findMatchingInvoice($transaction, $invoices): ?Invoice
    {
        $bestMatch = null;
        $bestScore = 0;
        $bestRawScore = 0; // Track raw score even if below threshold

        // P0-09: Determine confidence threshold and score boost from rules
        $minConfidence = $this->suggestMinConfidence ?? 0.5;
        $scoreBoost = 0.0;

        if ($this->currentRuleActions && $this->hasRuleAction($this->currentRuleActions, 'auto_match')) {
            // Get the confidence threshold from the rule, default to 50%
            $threshold = $this->getAutoMatchThreshold($this->currentRuleActions);
            $minConfidence = $threshold / 100.0; // Convert percentage to 0-1 scale
            $scoreBoost = 0.15; // Boost score when auto_match rule matches
        }

        foreach ($invoices as $invoice) {
            $score = $this->calculateMatchScore($transaction, $invoice) + $scoreBoost;

            // Track the best raw score for AI enhancement
            if ($score > $bestRawScore) {
                $bestRawScore = $score;
            }

            if ($score > $bestScore && $score >= $minConfidence) {
                $bestScore = $score;
                $bestMatch = $invoice;
            }
        }

        // Layer 3: AI Enhancement — if deterministic score < 0.9, try AI
        if ($bestScore < 0.9) {
            $aiResult = $this->tryAiEnhancement($transaction, $invoices, $bestRawScore);
            if ($aiResult) {
                $this->lastAiResult = $aiResult;

                // If AI-boosted confidence meets suggest threshold (0.5), use AI result
                $aiBestMatch = $aiResult['best_match'] ?? null;
                if ($aiBestMatch && ($aiBestMatch['confidence'] / 100.0) >= $minConfidence) {
                    $aiInvoice = $invoices->firstWhere('id', $aiBestMatch['invoice_id']);
                    if ($aiInvoice) {
                        // Store AI reason on the transaction
                        $this->storeAiMatchReason($transaction, $aiBestMatch['reason'] ?? '');

                        return $aiInvoice;
                    }
                }
            }
        }

        return $bestMatch;
    }

    /**
     * Last AI result from findMatchingInvoice, used by suggestMatch.
     */
    protected ?array $lastAiResult = null;

    /**
     * Try AI enhancement for a transaction.
     *
     * @param  object  $transaction
     * @param  \Illuminate\Support\Collection  $invoices
     * @param  float  $deterministicScore  Best deterministic score (0-1)
     * @return array|null AI result with merged confidence
     */
    protected function tryAiEnhancement(object $transaction, $invoices, float $deterministicScore): ?array
    {
        try {
            $aiMatcher = app(AiMatcherService::class);

            return $aiMatcher->enhance($transaction, $invoices, $this->companyId, $deterministicScore, $this->locale);
        } catch (\Exception $e) {
            Log::debug('[Matcher] AI enhancement unavailable', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Store AI match reason on the bank transaction.
     */
    protected function storeAiMatchReason(object $transaction, string $reason): void
    {
        if (empty($reason)) {
            return;
        }

        try {
            DB::table('bank_transactions')
                ->where('id', $transaction->id)
                ->update(['ai_match_reason' => $reason]);
        } catch (\Exception $e) {
            // Non-critical
        }
    }

    /**
     * Get the confidence threshold from auto_match rule actions.
     *
     * @param array $ruleResults The results from applyMatchingRules()
     * @return float The confidence threshold percentage (default 50)
     */
    protected function getAutoMatchThreshold(array $ruleResults): float
    {
        foreach ($ruleResults as $result) {
            foreach ($result['actions'] as $action) {
                if (($action['action'] ?? '') === 'auto_match') {
                    return (float) ($action['confidence_threshold'] ?? 50);
                }
            }
        }

        return 50.0;
    }

    /**
     * Calculate match score between transaction and invoice
     *
     * @deprecated Use calculateConfidenceScore instead
     */
    protected function calculateMatchScore($transaction, Invoice $invoice): float
    {
        return $this->calculateConfidenceScore($transaction, $invoice);
    }

    /**
     * Calculate confidence score for matching a bank transaction with an invoice
     * Returns a score from 0.0 to 1.0 based on multiple factors
     *
     * Scoring factors:
     * - Exact amount match: +0.4
     * - Date within ±3 days: +0.2
     * - Fuzzy description match (Levenshtein): +0.2
     * - Reference/invoice number in description: +0.3
     * - Customer IBAN match: +0.1
     *
     * @param  object  $bankTxn  Bank transaction object
     * @param  Invoice  $invoice  Invoice model instance
     * @return float Score from 0.0 to 1.0
     */
    public function calculateConfidenceScore($bankTxn, Invoice $invoice): float
    {
        $score = 0.0;

        // 1. Exact amount match: +0.4
        $amountDiff = abs($bankTxn->amount - $invoice->total);
        if ($amountDiff == 0) {
            $score += 0.4;
        } elseif ($invoice->total > 0 && ($amountDiff / $invoice->total) <= 0.01) {
            // Within 1% tolerance
            $score += 0.35;
        }

        // 2. Date within ±3 days: +0.2
        $transactionDate = Carbon::parse($bankTxn->transaction_date);
        $invoiceDate = Carbon::parse($invoice->invoice_date);
        $dueDate = Carbon::parse($invoice->due_date);

        $daysDiffInvoice = abs($transactionDate->diffInDays($invoiceDate));
        $daysDiffDue = abs($transactionDate->diffInDays($dueDate));
        $minDaysDiff = min($daysDiffInvoice, $daysDiffDue);

        if ($minDaysDiff <= 3) {
            $score += 0.2 - ($minDaysDiff * 0.05); // Gradually decrease score
        } elseif ($minDaysDiff <= 7) {
            $score += 0.1;
        }

        // 3. Reference/invoice number in description: +0.3
        $description = strtolower($bankTxn->description ?? '');
        $remittanceInfo = strtolower($bankTxn->remittance_info ?? '');
        $invoiceNumber = strtolower($invoice->invoice_number);

        if (strpos($description, $invoiceNumber) !== false ||
            strpos($remittanceInfo, $invoiceNumber) !== false) {
            $score += 0.3;
        } else {
            // Check for partial match (last 4+ digits)
            $invoiceDigits = preg_replace('/[^0-9]/', '', $invoiceNumber);
            if (strlen($invoiceDigits) >= 4) {
                $lastFourDigits = substr($invoiceDigits, -4);
                if (strpos($description, $lastFourDigits) !== false ||
                    strpos($remittanceInfo, $lastFourDigits) !== false) {
                    $score += 0.15;
                }
            }
        }

        // 4. Customer name match: +0.2
        if ($invoice->customer && $invoice->customer->name) {
            $customerName = mb_strtolower(trim($invoice->customer->name));
            $debtorName = mb_strtolower(trim($bankTxn->debtor_name ?? ''));
            $creditorName = mb_strtolower(trim($bankTxn->creditor_name ?? ''));

            // Use the appropriate counterparty name based on transaction type
            $txType = $bankTxn->transaction_type ?? ($bankTxn->amount > 0 ? 'credit' : 'debit');
            $counterpartyName = $txType === 'credit' ? $debtorName : $creditorName;

            if (strlen($customerName) > 0 && strlen($counterpartyName) > 0) {
                // Check if one name contains the other (handles city suffixes like "COMPANY Велес - СКОПЈЕ")
                if (mb_strpos($counterpartyName, $customerName) !== false || mb_strpos($customerName, $counterpartyName) !== false) {
                    $score += 0.2;
                } else {
                    // Levenshtein on the shorter of the two names for fuzzy matching
                    $shorter = strlen($customerName) <= strlen($counterpartyName) ? $customerName : $counterpartyName;
                    $longer = strlen($customerName) > strlen($counterpartyName) ? $customerName : $counterpartyName;

                    // Try substring match — check if the customer name appears within the counterparty
                    $shorterClean = preg_replace('/\s+(дооел|доо|ад|дпту|тп)\s*$/u', '', $shorter);
                    if (strlen($shorterClean) >= 4 && mb_strpos($longer, $shorterClean) !== false) {
                        $score += 0.18;
                    } else {
                        // Fall back to Levenshtein distance
                        $maxLen = max(strlen($customerName), strlen($counterpartyName));
                        $distance = levenshtein($customerName, $counterpartyName);
                        $similarity = 1 - ($distance / $maxLen);

                        if ($similarity >= 0.8) {
                            $score += 0.2;
                        } elseif ($similarity >= 0.6) {
                            $score += 0.15;
                        } elseif ($similarity >= 0.4) {
                            $score += 0.1;
                        }
                    }
                }
            }
        }

        // 5. Customer IBAN/account match: +0.1
        if ($invoice->customer) {
            $customerIban = str_replace(' ', '', strtolower($invoice->customer->iban ?? ''));

            if (strlen($customerIban) > 0) {
                $txAccounts = array_filter(array_map(fn ($f) => str_replace(' ', '', strtolower($bankTxn->$f ?? '')), [
                    'debtor_iban', 'creditor_iban', 'debtor_account', 'creditor_account',
                ]));

                foreach ($txAccounts as $acc) {
                    if ($acc === $customerIban || str_ends_with($acc, $customerIban) || str_ends_with($customerIban, $acc)) {
                        $score += 0.1;
                        break;
                    }
                }
            }
        }

        // Ensure score doesn't exceed 1.0
        return min($score, 1.0);
    }


    /**
     * Calculate amount matching score
     */
    protected function calculateAmountScore(float $transactionAmount, float $invoiceAmount): float
    {
        if ($invoiceAmount == 0) {
            return 0;
        }

        $difference = abs($transactionAmount - $invoiceAmount) / $invoiceAmount;

        if ($difference <= $this->amountTolerance) {
            return 1.0; // Perfect match
        } elseif ($difference <= 0.05) {
            return 0.8; // Very close
        } elseif ($difference <= 0.1) {
            return 0.5; // Moderately close
        }

        return 0; // Too different
    }

    /**
     * Calculate date proximity score
     */
    protected function calculateDateScore(string $transactionDate, string $invoiceDueDate): float
    {
        $transactionCarbon = Carbon::parse($transactionDate);
        $dueDateCarbon = Carbon::parse($invoiceDueDate);

        $daysDifference = abs($transactionCarbon->diffInDays($dueDateCarbon));

        if ($daysDifference <= 1) {
            return 1.0; // Same day or next day
        } elseif ($daysDifference <= 3) {
            return 0.8; // Within 3 days
        } elseif ($daysDifference <= 7) {
            return 0.6; // Within a week
        } elseif ($daysDifference <= 14) {
            return 0.3; // Within two weeks
        }

        return 0.1; // More than two weeks
    }

    /**
     * Calculate reference matching score
     */
    protected function calculateReferenceScore($transaction, Invoice $invoice): float
    {
        $description = strtolower($transaction->description ?? '');
        $remittanceInfo = strtolower($transaction->remittance_info ?? '');
        $invoiceNumber = strtolower($invoice->invoice_number);

        // Check if invoice number appears in transaction description or remittance
        if (strpos($description, $invoiceNumber) !== false ||
            strpos($remittanceInfo, $invoiceNumber) !== false) {
            return 1.0; // Perfect reference match
        }

        // Check for partial matches (last 4 digits, etc.)
        $invoiceDigits = preg_replace('/[^0-9]/', '', $invoiceNumber);
        if (strlen($invoiceDigits) >= 4) {
            $lastFourDigits = substr($invoiceDigits, -4);
            if (strpos($description, $lastFourDigits) !== false ||
                strpos($remittanceInfo, $lastFourDigits) !== false) {
                return 0.7; // Partial reference match
            }
        }

        // Check customer name matching
        if ($invoice->customer) {
            $customerName = strtolower($invoice->customer->name);
            $transactionCreditor = strtolower($transaction->creditor_name ?? '');

            if (strpos($transactionCreditor, $customerName) !== false ||
                strpos($customerName, $transactionCreditor) !== false) {
                return 0.5; // Customer name match
            }
        }

        return 0; // No reference match
    }

    /**
     * Create payment record and mark invoice as paid
     */
    protected function createPaymentRecord($transaction, Invoice $invoice): bool
    {
        try {
            DB::beginTransaction();

            // Create payment record
            $payment = Payment::create([
                'company_id' => $this->companyId,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'amount' => $transaction->amount,
                'currency_id' => $invoice->currency_id,
                'payment_date' => Carbon::parse($transaction->transaction_date)->format('Y-m-d'),
                'payment_number' => $this->generatePaymentNumber(),
                'payment_method' => 'bank_transfer',
                'notes' => 'Auto-matched from bank transaction: '.$transaction->external_reference,
                'reference' => $transaction->external_reference,
            ]);

            // Update invoice status
            $invoice->update([
                'status' => 'PAID',
                'paid_status' => Payment::STATUS_COMPLETED,
                'payment_date' => $payment->payment_date,
            ]);

            // Mark transaction as matched
            DB::table('bank_transactions')
                ->where('id', $transaction->id)
                ->update([
                    'matched_invoice_id' => $invoice->id,
                    'matched_payment_id' => $payment->id,
                    'matched_at' => now(),
                    'updated_at' => now(),
                ]);

            DB::commit();

            Log::info('Invoice automatically paid via bank transaction match', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount,
            ]);

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create payment from transaction match', [
                'invoice_id' => $invoice->id,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Calculate match confidence for reporting
     */
    protected function calculateMatchConfidence($transaction, Invoice $invoice): float
    {
        return round($this->calculateMatchScore($transaction, $invoice) * 100, 1);
    }

    /**
     * Generate unique payment number
     */
    protected function generatePaymentNumber(): string
    {
        $prefix = 'PAY-';
        $year = date('Y');
        $month = date('m');

        $lastPayment = Payment::where('company_id', $this->companyId)
            ->where('payment_number', 'like', $prefix.$year.$month.'%')
            ->orderBy('payment_number', 'desc')
            ->first();

        if ($lastPayment) {
            $lastNumber = intval(substr($lastPayment->payment_number, -4));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.$year.$month.'-'.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get matching statistics for a company
     */
    public function getMatchingStats(): array
    {
        $totalTransactions = DB::table('bank_transactions')
            ->where('company_id', $this->companyId)
            ->where('transaction_type', 'credit')
            ->count();

        $matchedTransactions = DB::table('bank_transactions')
            ->where('company_id', $this->companyId)
            ->where('transaction_type', 'credit')
            ->whereNotNull('matched_invoice_id')
            ->count();

        $matchRate = $totalTransactions > 0 ? ($matchedTransactions / $totalTransactions) * 100 : 0;

        return [
            'total_transactions' => $totalTransactions,
            'matched_transactions' => $matchedTransactions,
            'unmatched_transactions' => $totalTransactions - $matchedTransactions,
            'match_rate' => round($matchRate, 1),
        ];
    }
}

