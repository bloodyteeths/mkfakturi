<?php

namespace App\Services\Reconciliation;

use App\Models\BankTransaction;
use App\Models\Bill;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\PayrollRun;
use App\Models\ReconciliationFeedback;
use App\Models\Supplier;
use App\Services\DuplicateDetectionService;
use Illuminate\Support\Facades\Log;

/**
 * Smart Reconciliation Service
 *
 * Orchestrates intelligent matching for ALL bank transactions (credits and debits).
 * Pipeline: Rules → Bill match → Invoice match → Payroll match → Past feedback → AI
 */
class SmartReconciliationService
{
    private DuplicateDetectionService $nameService;

    public function __construct()
    {
        $this->nameService = new DuplicateDetectionService();
    }

    /**
     * Generate a smart suggestion for any bank transaction.
     */
    public function suggest(BankTransaction $tx, string $locale = 'mk'): SmartSuggestion
    {
        $companyId = $tx->company_id;
        // Use transaction_type field when available (amount sign can be inconsistent in PSD2 imports)
        $isDebit = $tx->transaction_type === 'debit' || ($tx->transaction_type === null && $tx->amount < 0);
        $alternatives = [];

        // Layer 1: User-defined matching rules
        $ruleSuggestion = $this->tryMatchingRules($tx, $companyId);
        if ($ruleSuggestion && $ruleSuggestion->confidence >= 0.9) {
            return $ruleSuggestion;
        }
        if ($ruleSuggestion) {
            $alternatives[] = $ruleSuggestion;
        }

        // Layer 2: Bill matching (debits only)
        if ($isDebit) {
            $billSuggestion = $this->tryMatchBill($tx, $companyId);
            if ($billSuggestion && $billSuggestion->confidence >= 0.85) {
                $billSuggestion->alternatives = $alternatives;

                return $billSuggestion;
            }
            if ($billSuggestion) {
                $alternatives[] = $billSuggestion;
            }
        }

        // Layer 3: Invoice matching (credits only — for unmatched credits)
        if (! $isDebit && ! $tx->matched_invoice_id) {
            $invoiceSuggestion = $this->tryMatchInvoice($tx, $companyId);
            if ($invoiceSuggestion && $invoiceSuggestion->confidence >= 0.85) {
                $invoiceSuggestion->alternatives = $alternatives;

                return $invoiceSuggestion;
            }
            if ($invoiceSuggestion) {
                $alternatives[] = $invoiceSuggestion;
            }
        }

        // Layer 4: Payroll matching (debits only)
        if ($isDebit) {
            $payrollSuggestion = $this->tryMatchPayroll($tx, $companyId);
            if ($payrollSuggestion && $payrollSuggestion->confidence >= 0.8) {
                $payrollSuggestion->alternatives = $alternatives;

                return $payrollSuggestion;
            }
            if ($payrollSuggestion) {
                $alternatives[] = $payrollSuggestion;
            }
        }

        // Layer 5: Past feedback (counterparty pattern matching)
        $feedbackSuggestion = $this->tryPastFeedback($tx, $companyId);
        if ($feedbackSuggestion && $feedbackSuggestion->confidence >= 0.8) {
            $feedbackSuggestion->alternatives = $alternatives;

            return $feedbackSuggestion;
        }
        if ($feedbackSuggestion) {
            $alternatives[] = $feedbackSuggestion;
        }

        // Layer 6: AI categorization (enhanced — uses company's actual categories)
        $aiSuggestion = $this->tryAiCategorization($tx, $companyId, $locale);
        if ($aiSuggestion) {
            $aiSuggestion->alternatives = $alternatives;

            return $aiSuggestion;
        }

        // Fallback: mark as reviewed
        return new SmartSuggestion(
            action: SmartSuggestion::ACTION_MARK_REVIEWED,
            confidence: 0.1,
            reason: $this->t('smart_reconciliation.no_suggestion', $locale),
            alternatives: $alternatives,
        );
    }

    /**
     * Layer 1: Check user-defined matching rules.
     */
    private function tryMatchingRules(BankTransaction $tx, int $companyId): ?SmartSuggestion
    {
        try {
            $service = app(MatchingRulesService::class);
            $matched = $service->applyRules($tx, $companyId);

            if (empty($matched)) {
                return null;
            }

            $topRule = $matched[0];
            $actions = $topRule['actions'] ?? [];

            foreach ($actions as $action) {
                $type = $action['type'] ?? $action['action'] ?? null;

                if ($type === 'categorize' && isset($action['category_id'])) {
                    $cat = ExpenseCategory::find($action['category_id']);

                    return new SmartSuggestion(
                        action: SmartSuggestion::ACTION_CREATE_EXPENSE,
                        confidence: 0.95,
                        reason: "Matching rule: {$topRule['rule_name']}",
                        categoryId: $action['category_id'],
                        categoryName: $cat?->name,
                    );
                }

                if ($type === 'ignore') {
                    return new SmartSuggestion(
                        action: SmartSuggestion::ACTION_MARK_REVIEWED,
                        confidence: 0.95,
                        reason: "Matching rule: {$topRule['rule_name']}",
                    );
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('[SmartReconciliation] Rules layer failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Layer 2: Fuzzy-match debit transactions to unpaid bills.
     */
    private function tryMatchBill(BankTransaction $tx, int $companyId): ?SmartSuggestion
    {
        // For debits, creditor is the counterparty (who we paid)
        $counterparty = $tx->creditor_name;
        $txAmount = (int) round(abs((float) $tx->amount) * 100); // Convert to cents

        if (! $counterparty && ! $txAmount) {
            return null;
        }

        // Get unpaid bills with suppliers
        $bills = Bill::where('company_id', $companyId)
            ->whereIn('paid_status', [Bill::PAID_STATUS_UNPAID, Bill::PAID_STATUS_PARTIALLY_PAID])
            ->with('supplier:id,name')
            ->get(['id', 'bill_number', 'total', 'due_amount', 'supplier_id', 'due_date']);

        if ($bills->isEmpty()) {
            return null;
        }

        $bestScore = 0;
        $bestBill = null;

        foreach ($bills as $bill) {
            $score = 0;
            $supplierName = $bill->supplier?->name;

            // Amount matching (60% weight) — compare tx amount to bill total or due_amount
            $billTotal = (int) $bill->total;
            $billDue = (int) ($bill->due_amount ?? $bill->total);

            if ($txAmount > 0 && ($txAmount === $billTotal || $txAmount === $billDue)) {
                $score += 60; // Exact amount match
            } elseif ($txAmount > 0 && $billTotal > 0) {
                $ratio = min($txAmount, $billTotal) / max($txAmount, $billTotal);
                if ($ratio >= 0.98) {
                    $score += 50; // Within 2%
                } elseif ($ratio >= 0.95) {
                    $score += 30; // Within 5%
                }
            }

            // Name matching (40% weight)
            if ($counterparty && $supplierName) {
                $normalizedTx = $this->nameService->normalizeName($counterparty);
                $normalizedSupplier = $this->nameService->normalizeName($supplierName);

                if ($normalizedTx === $normalizedSupplier) {
                    $score += 40;
                } elseif (str_contains($normalizedSupplier, $normalizedTx) || str_contains($normalizedTx, $normalizedSupplier)) {
                    $score += 35;
                } else {
                    // Transliteration match (Cyrillic ↔ Latin)
                    $latinTx = $this->nameService->cyrillicToLatin($normalizedTx);
                    $latinSupplier = $this->nameService->cyrillicToLatin($normalizedSupplier);
                    if ($latinTx === $latinSupplier) {
                        $score += 35;
                    } elseif (str_contains($latinSupplier, $latinTx) || str_contains($latinTx, $latinSupplier)) {
                        $score += 25;
                    }
                }
            }

            // IBAN matching bonus
            $txIban = $tx->creditor_iban;
            if ($txIban && $supplierName) {
                // Check if we've seen this IBAN for this supplier before
                $priorMatch = BankTransaction::where('company_id', $companyId)
                    ->where('creditor_iban', $txIban)
                    ->where('linked_type', BankTransaction::LINKED_BILL_PAYMENT)
                    ->whereNotNull('linked_id')
                    ->exists();
                if ($priorMatch) {
                    $score += 15;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestBill = $bill;
            }
        }

        if ($bestScore < 40 || ! $bestBill) {
            return null;
        }

        $confidence = min(0.98, $bestScore / 100);
        $supplierName = $bestBill->supplier?->name ?? '';

        return new SmartSuggestion(
            action: SmartSuggestion::ACTION_LINK_BILL,
            confidence: $confidence,
            reason: "Supplier \"{$supplierName}\" — Bill {$bestBill->bill_number}",
            targetId: $bestBill->id,
            targetLabel: "{$bestBill->bill_number} — {$supplierName}",
        );
    }

    /**
     * Layer 3: Match credit transactions to unpaid invoices.
     */
    private function tryMatchInvoice(BankTransaction $tx, int $companyId): ?SmartSuggestion
    {
        // For credits, debtor is the counterparty (who paid us)
        $counterparty = $tx->debtor_name;
        $txAmount = (int) round(abs((float) $tx->amount) * 100);

        $invoices = Invoice::where('company_id', $companyId)
            ->whereIn('paid_status', [
                Invoice::STATUS_UNPAID,
                Invoice::STATUS_PARTIALLY_PAID,
            ])
            ->with('customer:id,name')
            ->get(['id', 'invoice_number', 'total', 'due_amount', 'customer_id']);

        if ($invoices->isEmpty()) {
            return null;
        }

        $bestScore = 0;
        $bestInvoice = null;

        foreach ($invoices as $invoice) {
            $score = 0;
            $customerName = $invoice->customer?->name;

            // Amount matching (60% weight)
            $invTotal = (int) $invoice->total;
            $invDue = (int) ($invoice->due_amount ?? $invoice->total);

            if ($txAmount > 0 && ($txAmount === $invTotal || $txAmount === $invDue)) {
                $score += 60;
            } elseif ($txAmount > 0 && $invTotal > 0) {
                $ratio = min($txAmount, $invTotal) / max($txAmount, $invTotal);
                if ($ratio >= 0.98) {
                    $score += 50;
                } elseif ($ratio >= 0.95) {
                    $score += 30;
                }
            }

            // Name matching (40% weight)
            if ($counterparty && $customerName) {
                $normalizedTx = $this->nameService->normalizeName($counterparty);
                $normalizedCustomer = $this->nameService->normalizeName($customerName);

                if ($normalizedTx === $normalizedCustomer) {
                    $score += 40;
                } elseif (str_contains($normalizedCustomer, $normalizedTx) || str_contains($normalizedTx, $normalizedCustomer)) {
                    $score += 35;
                } else {
                    $latinTx = $this->nameService->cyrillicToLatin($normalizedTx);
                    $latinCustomer = $this->nameService->cyrillicToLatin($normalizedCustomer);
                    if ($latinTx === $latinCustomer) {
                        $score += 35;
                    } elseif (str_contains($latinCustomer, $latinTx) || str_contains($latinTx, $latinCustomer)) {
                        $score += 25;
                    }
                }
            }

            // Invoice number in description/remittance
            $searchText = mb_strtolower(($tx->description ?? '').($tx->remittance_info ?? ''));
            if ($invoice->invoice_number && str_contains($searchText, mb_strtolower($invoice->invoice_number))) {
                $score += 30;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestInvoice = $invoice;
            }
        }

        if ($bestScore < 40 || ! $bestInvoice) {
            return null;
        }

        $confidence = min(0.98, $bestScore / 100);
        $customerName = $bestInvoice->customer?->name ?? '';

        return new SmartSuggestion(
            action: SmartSuggestion::ACTION_LINK_INVOICE,
            confidence: $confidence,
            reason: "Customer \"{$customerName}\" — Invoice {$bestInvoice->invoice_number}",
            targetId: $bestInvoice->id,
            targetLabel: "{$bestInvoice->invoice_number} — {$customerName}",
        );
    }

    /**
     * Layer 4: Match debit transactions to payroll runs.
     */
    private function tryMatchPayroll(BankTransaction $tx, int $companyId): ?SmartSuggestion
    {
        $txAmount = (int) round(abs((float) $tx->amount) * 100);
        $txDate = $tx->transaction_date;

        if (! $txAmount || ! $txDate) {
            return null;
        }

        // Salary keywords in description
        $salaryKeywords = ['плата', 'salary', 'wages', 'придонес', 'payroll', 'paga', 'neto', 'нето'];
        $description = mb_strtolower(($tx->description ?? '').($tx->remittance_info ?? ''));
        $hasSalaryKeyword = false;
        foreach ($salaryKeywords as $kw) {
            if (str_contains($description, $kw)) {
                $hasSalaryKeyword = true;
                break;
            }
        }

        $payrollRuns = PayrollRun::where('company_id', $companyId)
            ->whereIn('status', ['approved', 'posted'])
            ->get(['id', 'total_net', 'total_gross', 'period_year', 'period_month', 'period_start', 'period_end', 'status']);

        if ($payrollRuns->isEmpty()) {
            return null;
        }

        $bestScore = 0;
        $bestRun = null;

        foreach ($payrollRuns as $run) {
            $score = 0;

            // Amount matching — compare to total_net (what actually gets paid out)
            $netAmount = (int) $run->total_net;
            if ($netAmount > 0 && $txAmount > 0) {
                $ratio = min($txAmount, $netAmount) / max($txAmount, $netAmount);
                if ($ratio >= 0.98) {
                    $score += 50;
                } elseif ($ratio >= 0.90) {
                    $score += 30; // Multiple payments for one run
                }
            }

            // Also check gross amount (employer might pay gross + contributions in one transfer)
            $grossAmount = (int) $run->total_gross;
            if ($grossAmount > 0 && $txAmount > 0) {
                $ratio = min($txAmount, $grossAmount) / max($txAmount, $grossAmount);
                if ($ratio >= 0.98) {
                    $score += 40;
                }
            }

            // Date proximity (within 10 days of period end)
            if ($run->period_end && $txDate) {
                $daysDiff = abs($txDate->diffInDays($run->period_end));
                if ($daysDiff <= 5) {
                    $score += 20;
                } elseif ($daysDiff <= 10) {
                    $score += 10;
                }
            }

            // Salary keyword boost
            if ($hasSalaryKeyword) {
                $score += 25;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestRun = $run;
            }
        }

        if ($bestScore < 45 || ! $bestRun) {
            return null;
        }

        $confidence = min(0.95, $bestScore / 100);
        $period = sprintf('%04d-%02d', $bestRun->period_year, $bestRun->period_month);

        return new SmartSuggestion(
            action: SmartSuggestion::ACTION_LINK_PAYROLL,
            confidence: $confidence,
            reason: "Payroll {$period} — Net: ".number_format($bestRun->total_net / 100, 0).' ден',
            targetId: $bestRun->id,
            targetLabel: "Payroll {$period}",
        );
    }

    /**
     * Layer 5: Check past user feedback for similar counterparties.
     */
    private function tryPastFeedback(BankTransaction $tx, int $companyId): ?SmartSuggestion
    {
        $isDebit = $tx->transaction_type === 'debit' || ($tx->transaction_type === null && $tx->amount < 0);
        $counterparty = $isDebit ? $tx->creditor_name : $tx->debtor_name;
        if (! $counterparty) {
            return null;
        }

        $normalized = $this->nameService->normalizeName($counterparty);
        if (mb_strlen($normalized) < 3) {
            return null;
        }

        // Find past reconciled transactions from same counterparty
        $pastTx = BankTransaction::where('company_id', $companyId)
            ->where('processing_status', BankTransaction::STATUS_PROCESSED)
            ->whereNotNull('linked_type')
            ->where(function ($q) use ($tx) {
                if ($tx->amount < 0) {
                    $q->whereNotNull('creditor_name');
                } else {
                    $q->whereNotNull('debtor_name');
                }
            })
            ->orderByDesc('processed_at')
            ->limit(50)
            ->get();

        foreach ($pastTx as $past) {
            $pastCounterparty = $past->counterparty_name;
            if (! $pastCounterparty) {
                continue;
            }

            $pastNormalized = $this->nameService->normalizeName($pastCounterparty);
            $latin1 = $this->nameService->cyrillicToLatin($normalized);
            $latin2 = $this->nameService->cyrillicToLatin($pastNormalized);

            if ($pastNormalized === $normalized || $latin1 === $latin2) {
                // Same counterparty — suggest same action
                return $this->buildFeedbackSuggestion($past, $counterparty);
            }
        }

        return null;
    }

    /**
     * Build suggestion from a past reconciled transaction.
     */
    private function buildFeedbackSuggestion(BankTransaction $past, string $counterparty): ?SmartSuggestion
    {
        $action = match ($past->linked_type) {
            BankTransaction::LINKED_EXPENSE => SmartSuggestion::ACTION_CREATE_EXPENSE,
            BankTransaction::LINKED_BILL_PAYMENT => SmartSuggestion::ACTION_CREATE_EXPENSE, // Don't suggest old bill, suggest expense
            BankTransaction::LINKED_PAYROLL_RUN => SmartSuggestion::ACTION_LINK_PAYROLL,
            BankTransaction::LINKED_REVIEWED => SmartSuggestion::ACTION_MARK_REVIEWED,
            default => null,
        };

        if (! $action) {
            return null;
        }

        // Try to extract the category from past processing notes
        $categoryId = null;
        $categoryName = null;
        if ($past->processing_notes) {
            $notes = is_string($past->processing_notes) ? json_decode($past->processing_notes, true) : $past->processing_notes;
            $categoryId = $notes['category_id'] ?? null;
            if ($categoryId) {
                $cat = ExpenseCategory::find($categoryId);
                $categoryName = $cat?->name;
            }
        }

        return new SmartSuggestion(
            action: $action,
            confidence: 0.82,
            reason: "Previously categorized for \"{$counterparty}\"",
            categoryId: $categoryId,
            categoryName: $categoryName,
        );
    }

    /**
     * Layer 6: Enhanced AI categorization with company-specific categories.
     */
    private function tryAiCategorization(BankTransaction $tx, int $companyId, string $locale): ?SmartSuggestion
    {
        try {
            $categories = ExpenseCategory::where('company_id', $companyId)
                ->orderBy('name')
                ->get(['id', 'name']);

            // Build enhanced prompt with company's actual categories
            $categorizer = new SmartAiCategorizer();

            return $categorizer->suggest($tx, $categories, $locale);
        } catch (\Exception $e) {
            Log::warning('[SmartReconciliation] AI layer failed', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Simple locale-aware translation helper.
     */
    private function t(string $key, string $locale): string
    {
        $strings = [
            'smart_reconciliation.no_suggestion' => [
                'mk' => 'Не е пронајдено совпаѓање — прегледајте рачно',
                'sq' => 'Nuk u gjet përputhje — rishikoni manualisht',
                'tr' => 'Eşleşme bulunamadı — manuel olarak inceleyin',
                'en' => 'No match found — review manually',
            ],
        ];

        return $strings[$key][$locale] ?? $strings[$key]['en'] ?? $key;
    }
}
// CLAUDE-CHECKPOINT
