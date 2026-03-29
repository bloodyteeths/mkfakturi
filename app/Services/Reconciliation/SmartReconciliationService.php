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

        // Layer 0: Government IBAN auto-detection (debits only)
        // MK government payments go through НБРМ treasury (bank code 100)
        if ($isDebit) {
            $govSuggestion = $this->tryMatchGovernmentIban($tx);
            if ($govSuggestion && $govSuggestion->confidence >= 0.9) {
                return $govSuggestion;
            }
            if ($govSuggestion) {
                $alternatives[] = $govSuggestion;
            }
        }

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
     * Layer 0: Detect government/institutional payments via IBAN prefix + creditor name.
     *
     * MK IBAN format: MK + 2 check + 3-digit bank code + 10-digit account + 2 national
     * Bank code 100 = НБРМ (National Bank) = all treasury/government accounts
     * Payment accounts starting with 840- = трезорски сметки (treasury accounts)
     *
     * Known government institution names that appear as creditor in bank statements:
     * - УЈП / Управа за јавни приходи (tax office)
     * - ФПИОМ / Фонд за ПИОМ (pension fund)
     * - ФЗОМ / Фонд за здравство (health fund)
     * - АВРМ / Агенција за вработување (employment agency)
     * - Царинска управа (customs)
     */
    private function tryMatchGovernmentIban(BankTransaction $tx): ?SmartSuggestion
    {
        $iban = $tx->creditor_iban ?? '';
        $creditor = mb_strtolower($tx->creditor_name ?? '');
        $description = mb_strtolower(($tx->description ?? '').($tx->remittance_info ?? ''));
        $allText = $creditor.' '.$description;

        // Check 1: IBAN starts with MK..100 (НБРМ treasury)
        $isTreasuryIban = false;
        if (mb_strlen($iban) >= 7) {
            // MK IBAN: MK + 2 check digits + bank code (positions 4-6)
            $bankCode = substr($iban, 4, 3);
            $isTreasuryIban = $bankCode === '100';
        }

        // Check 2: Known government institution names
        $govInstitutions = [
            // УЈП — Управа за јавни приходи (Public Revenue Office)
            ['patterns' => ['ujp', 'управа за јавни приходи', 'управа за јавни', 'јавни приходи'], 'action' => SmartSuggestion::ACTION_TAX_PAYMENT, 'reason' => 'УЈП — Управа за јавни приходи', 'sub_type' => null],
            // ФПИОМ — Pension fund
            ['patterns' => ['фпиом', 'фонд за пиом', 'пензиско осигурување', 'фонд за пензиско', 'пензиски', 'piom', 'fpiom', 'pension fund', 'пензија', 'пио фонд'], 'action' => SmartSuggestion::ACTION_TAX_PAYMENT, 'reason' => 'ФПИОМ — Пензиско осигурување', 'sub_type' => 'ФПИОМ'],
            // ФЗОМ — Health fund
            ['patterns' => ['фзом', 'фонд за здравств', 'здравствено осигурување', 'здравствен', 'fzom', 'health fund', 'здравство'], 'action' => SmartSuggestion::ACTION_TAX_PAYMENT, 'reason' => 'ФЗОМ — Здравствено осигурување', 'sub_type' => 'ФЗОМ'],
            // АВРМ — Employment agency
            ['patterns' => ['аврм', 'агенција за вработување', 'avrm', 'вработување', 'невработеност', 'employment agency'], 'action' => SmartSuggestion::ACTION_TAX_PAYMENT, 'reason' => 'АВРМ — Агенција за вработување', 'sub_type' => 'Вработување'],
            // Царинска управа — Customs
            ['patterns' => ['царинска управа', 'царина', 'customs', 'carina'], 'action' => SmartSuggestion::ACTION_TAX_PAYMENT, 'reason' => 'Царинска управа — Царински давачки', 'sub_type' => 'Царина'],
            // Municipality / Local government
            ['patterns' => ['општина', 'општина скопје', 'комунална такса', 'фирмарина'], 'action' => SmartSuggestion::ACTION_TAX_PAYMENT, 'reason' => 'Општина — Комунална такса', 'sub_type' => 'Комунална такса'],
        ];

        foreach ($govInstitutions as $gov) {
            foreach ($gov['patterns'] as $pattern) {
                if (str_contains($allText, $pattern)) {
                    $confidence = $isTreasuryIban ? 0.95 : 0.88;

                    return new SmartSuggestion(
                        action: $gov['action'],
                        confidence: $confidence,
                        reason: $gov['reason'],
                        categoryName: $gov['sub_type'],
                    );
                }
            }
        }

        // If treasury IBAN but no institution name match — still likely tax/government payment
        if ($isTreasuryIban) {
            // Try to determine specific type from description keywords
            $subType = $this->detectTaxSubTypeFromDescription($allText);

            return new SmartSuggestion(
                action: SmartSuggestion::ACTION_TAX_PAYMENT,
                confidence: 0.85,
                reason: 'Трезорска сметка (НБРМ) — државно плаќање',
                categoryName: $subType,
            );
        }

        // Known bank IBANs / names — for loan repayments
        $bankKeywords = [
            'комерцијална банка', 'стопанска банка', 'нлб банка', 'nlb',
            'халк банка', 'halk', 'тtк банка', 'ttk', 'прокредит', 'procredit',
            'шпаркасе', 'sparkasse', 'силк роуд', 'silk road',
            'уни банка', 'uni bank', 'капитал банка', 'capital bank',
        ];
        foreach ($bankKeywords as $bank) {
            if (str_contains($creditor, $bank)) {
                // Bank as creditor + debit → likely loan repayment or bank fee
                // Check if amount suggests loan (>5000 MKD) vs fee (<5000 MKD)
                $amount = abs((float) $tx->amount);
                if ($amount >= 5000) {
                    return new SmartSuggestion(
                        action: SmartSuggestion::ACTION_LOAN_REPAYMENT,
                        confidence: 0.7,
                        reason: "Банка: {$tx->creditor_name} — веројатно отплата на кредит",
                    );
                }
                // Small amounts to banks = fees — let AI handle via create_expense
                break;
            }
        }

        return null;
    }

    /**
     * Detect tax sub-type from transaction description keywords.
     */
    private function detectTaxSubTypeFromDescription(string $text): ?string
    {
        $taxKeywords = [
            'ддв' => 'ДДВ', 'ddv' => 'ДДВ', 'vat' => 'ДДВ', 'tvsh' => 'ДДВ',
            'данок на добивка' => 'Данок на добивка', 'profit tax' => 'Данок на добивка',
            'персонален данок' => 'Персонален данок', 'данок на доход' => 'Персонален данок',
            'фпиом' => 'ФПИОМ', 'пензиско' => 'ФПИОМ', 'пензиски' => 'ФПИОМ', 'piom' => 'ФПИОМ', 'пио' => 'ФПИОМ',
            'фзом' => 'ФЗОМ', 'здравствено' => 'ФЗОМ', 'здравствен' => 'ФЗОМ', 'fzom' => 'ФЗОМ',
            'вработување' => 'Вработување', 'невработеност' => 'Вработување',
            'професионален' => 'Професионален придонес', 'дополнителен' => 'Професионален придонес',
            'царина' => 'Царина', 'customs' => 'Царина',
            'акциза' => 'Акциза', 'excise' => 'Акциза',
            'комунална' => 'Комунална такса', 'фирмарина' => 'Комунална такса',
            'аконтација' => 'Аконтација',
        ];

        foreach ($taxKeywords as $keyword => $subType) {
            if (str_contains($text, $keyword)) {
                return $subType;
            }
        }

        return null;
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
     *
     * Payroll generates 6 separate bank charges per MPIN declaration:
     * 1. Net salary (нето плата) → total_net
     * 2. ФПИОМ pension → sum(pension_contribution_employee + pension_contribution_employer)
     * 3. ФЗОМ health → sum(health_contribution_employee + health_contribution_employer)
     * 4. Employment fund (вработување) → sum(unemployment_contribution)
     * 5. Additional contribution (професионален 0.5%) → sum(additional_contribution)
     * 6. Personal income tax (ПДД) → sum(income_tax_amount)
     */
    private function tryMatchPayroll(BankTransaction $tx, int $companyId): ?SmartSuggestion
    {
        $txAmount = (int) round(abs((float) $tx->amount) * 100);
        $txDate = $tx->transaction_date;

        if (! $txAmount || ! $txDate) {
            return null;
        }

        // Payroll-related keywords
        $payrollKeywords = [
            'плата', 'salary', 'wages', 'payroll', 'paga', 'neto', 'нето',
            'придонес', 'пио', 'piom', 'фпиом', 'fpiom', 'pension', 'пензиски', 'пензија',
            'здравств', 'fzom', 'фзом', 'health',
            'вработување', 'employment', 'punësim',
            'персонален данок', 'данок од плата', 'pit', 'tatim',
            'мпин', 'mpin', 'декларација',
        ];
        // Also check counterparty name — e.g. "Нето плата" is often the creditor name, not in description
        $isDebit = $tx->transaction_type === 'debit' || ($tx->transaction_type === null && $tx->amount < 0);
        $counterparty = $isDebit ? ($tx->creditor_name ?? '') : ($tx->debtor_name ?? '');
        $description = mb_strtolower(($tx->description ?? '').($tx->remittance_info ?? '').' '.$counterparty);
        $hasPayrollKeyword = false;
        $matchedKeyword = '';
        foreach ($payrollKeywords as $kw) {
            if (str_contains($description, $kw)) {
                $hasPayrollKeyword = true;
                $matchedKeyword = $kw;
                break;
            }
        }

        $payrollRuns = PayrollRun::where('company_id', $companyId)
            ->whereIn('status', ['approved', 'posted', 'paid'])
            ->with('lines')
            ->get(['id', 'total_net', 'total_gross', 'total_employer_tax', 'total_employee_tax', 'period_year', 'period_month', 'period_start', 'period_end', 'status']);

        if ($payrollRuns->isEmpty()) {
            // If keywords match payroll but no run exists — warn user to create one first
            if ($hasPayrollKeyword) {
                // Estimate period from transaction date (contributions due by 15th of next month)
                $estimatedMonth = $txDate->day <= 15
                    ? $txDate->copy()->subMonth()->format('m/Y')
                    : $txDate->format('m/Y');

                return new SmartSuggestion(
                    action: SmartSuggestion::ACTION_MARK_REVIEWED,
                    confidence: 0.3,
                    reason: $this->t('smart_reconciliation.missing_payroll_run', $this->detectLocale($tx))
                        ." ({$estimatedMonth})",
                );
            }

            return null;
        }

        $bestScore = 0;
        $bestRun = null;
        $bestChargeType = 'net_salary';

        foreach ($payrollRuns as $run) {
            // Aggregate contribution amounts from payroll run lines
            $totals = $this->aggregatePayrollContributions($run);

            // Check each charge type against the bank transaction amount
            $chargeTypes = [
                'net_salary' => ['amount' => (int) $run->total_net, 'label' => 'Нето плата'],
                'pension' => ['amount' => $totals['pension'], 'label' => 'ФПИОМ (пензиско)'],
                'health' => ['amount' => $totals['health'], 'label' => 'ФЗОМ (здравствено)'],
                'employment' => ['amount' => $totals['employment'], 'label' => 'Вработување'],
                'additional' => ['amount' => $totals['additional'], 'label' => 'Професионален придонес'],
                'pit' => ['amount' => $totals['pit'], 'label' => 'Персонален данок'],
                'total_contributions' => ['amount' => $totals['total_contributions'], 'label' => 'Вкупни придонеси и ПДД'],
                'gross' => ['amount' => (int) $run->total_gross, 'label' => 'Бруто плата'],
            ];

            foreach ($chargeTypes as $type => $info) {
                if ($info['amount'] <= 0) {
                    continue;
                }

                $score = 0;

                // Amount matching
                $ratio = min($txAmount, $info['amount']) / max($txAmount, $info['amount']);
                if ($ratio >= 0.99) {
                    $score += 55; // Near-exact match
                } elseif ($ratio >= 0.98) {
                    $score += 50;
                } elseif ($ratio >= 0.95) {
                    $score += 35;
                }

                // Date proximity (contributions due by 15th of next month)
                if ($run->period_end && $txDate) {
                    $daysDiff = abs($txDate->diffInDays($run->period_end));
                    if ($daysDiff <= 20) {
                        $score += 20;
                    } elseif ($daysDiff <= 35) {
                        $score += 10;
                    }
                }

                // Keyword boost
                if ($hasPayrollKeyword) {
                    $score += 25;
                    // Extra boost if keyword matches the charge type
                    if ($type === 'pension' && in_array($matchedKeyword, ['пио', 'piom', 'фпиом', 'fpiom', 'pension'])) {
                        $score += 10;
                    } elseif ($type === 'health' && in_array($matchedKeyword, ['здравств', 'fzom', 'фзом', 'health'])) {
                        $score += 10;
                    } elseif ($type === 'pit' && in_array($matchedKeyword, ['персонален данок', 'данок од плата', 'pit', 'tatim'])) {
                        $score += 10;
                    } elseif ($type === 'employment' && in_array($matchedKeyword, ['вработување', 'employment', 'punësim'])) {
                        $score += 10;
                    } elseif ($type === 'net_salary' && in_array($matchedKeyword, ['плата', 'salary', 'neto', 'нето', 'paga'])) {
                        $score += 10;
                    }
                }

                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestRun = $run;
                    $bestChargeType = $type;
                }
            }
        }

        if ($bestScore < 45 || ! $bestRun) {
            // Keywords match but amounts don't — might be a different period
            if ($hasPayrollKeyword) {
                $estimatedMonth = $txDate->day <= 15
                    ? $txDate->copy()->subMonth()->format('m/Y')
                    : $txDate->format('m/Y');

                return new SmartSuggestion(
                    action: SmartSuggestion::ACTION_MARK_REVIEWED,
                    confidence: 0.3,
                    reason: $this->t('smart_reconciliation.payroll_no_match', $this->detectLocale($tx))
                        ." ({$estimatedMonth})",
                );
            }

            return null;
        }

        $confidence = min(0.95, $bestScore / 100);
        $period = sprintf('%04d-%02d', $bestRun->period_year, $bestRun->period_month);
        $chargeLabel = $chargeTypes[$bestChargeType]['label'] ?? $bestChargeType;
        $chargeAmount = $chargeTypes[$bestChargeType]['amount'] ?? 0;

        // For contributions, use tax_payment action (not link_payroll)
        $contributionTypes = ['pension', 'health', 'employment', 'additional', 'pit', 'total_contributions'];
        if (in_array($bestChargeType, $contributionTypes)) {
            // Map charge type to tax sub-type
            $subTypeMap = [
                'pension' => 'ФПИОМ',
                'health' => 'ФЗОМ',
                'employment' => 'Вработување',
                'additional' => 'Професионален придонес',
                'pit' => 'Персонален данок',
                'total_contributions' => 'Вкупни придонеси',
            ];

            return new SmartSuggestion(
                action: SmartSuggestion::ACTION_TAX_PAYMENT,
                confidence: $confidence,
                reason: "Плата {$period} — {$chargeLabel}: ".number_format($chargeAmount / 100, 0).' ден',
                targetId: $bestRun->id,
                targetLabel: "Payroll {$period} — {$chargeLabel}",
                categoryName: $subTypeMap[$bestChargeType] ?? null,
            );
        }

        return new SmartSuggestion(
            action: SmartSuggestion::ACTION_LINK_PAYROLL,
            confidence: $confidence,
            reason: "Плата {$period} — {$chargeLabel}: ".number_format($chargeAmount / 100, 0).' ден',
            targetId: $bestRun->id,
            targetLabel: "Payroll {$period}",
        );
    }

    /**
     * Aggregate individual contribution totals from payroll run lines.
     */
    private function aggregatePayrollContributions(PayrollRun $run): array
    {
        $lines = $run->lines;

        $pension = $lines->sum('pension_contribution_employee') + $lines->sum('pension_contribution_employer');
        $health = $lines->sum('health_contribution_employee') + $lines->sum('health_contribution_employer');
        $employment = $lines->sum('unemployment_contribution');
        $additional = $lines->sum('additional_contribution');
        $pit = $lines->sum('income_tax_amount');

        return [
            'pension' => (int) $pension,
            'health' => (int) $health,
            'employment' => (int) $employment,
            'additional' => (int) $additional,
            'pit' => (int) $pit,
            'total_contributions' => (int) ($pension + $health + $employment + $additional + $pit),
        ];
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
            BankTransaction::LINKED_INCOME => SmartSuggestion::ACTION_RECORD_INCOME,
            BankTransaction::LINKED_OWNER_CONTRIBUTION => SmartSuggestion::ACTION_OWNER_CONTRIBUTION,
            BankTransaction::LINKED_OWNER_WITHDRAWAL => SmartSuggestion::ACTION_OWNER_WITHDRAWAL,
            BankTransaction::LINKED_LOAN_RECEIVED => SmartSuggestion::ACTION_LOAN_RECEIVED,
            BankTransaction::LINKED_LOAN_REPAYMENT => SmartSuggestion::ACTION_LOAN_REPAYMENT,
            BankTransaction::LINKED_TAX_PAYMENT => SmartSuggestion::ACTION_TAX_PAYMENT,
            BankTransaction::LINKED_INTERNAL_TRANSFER => SmartSuggestion::ACTION_INTERNAL_TRANSFER,
            BankTransaction::LINKED_CASH_DEPOSIT => SmartSuggestion::ACTION_CASH_DEPOSIT,
            BankTransaction::LINKED_CASH_WITHDRAWAL => SmartSuggestion::ACTION_CASH_WITHDRAWAL,
            BankTransaction::LINKED_ADVANCE_RECEIVED => SmartSuggestion::ACTION_ADVANCE_RECEIVED,
            BankTransaction::LINKED_ADVANCE_PAID => SmartSuggestion::ACTION_ADVANCE_PAID,
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
    /**
     * Detect locale from transaction context or fall back to app locale.
     */
    private function detectLocale(BankTransaction $tx): string
    {
        return app()->getLocale() ?: 'mk';
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
            'smart_reconciliation.missing_payroll_run' => [
                'mk' => 'Ова личи на плата/придонес, но нема креиран платен список за овој период. Прво креирајте платен список, потоа порамнете.',
                'sq' => 'Kjo duket si pagë/kontribut, por nuk ka listë pagash për këtë periudhë. Krijoni listën e pagave fillimisht, pastaj rakordoni.',
                'tr' => 'Bu bir maaş/prim ödemesine benziyor ama bu dönem için bordro yok. Önce bordro oluşturun, sonra eşleştirin.',
                'en' => 'This looks like a payroll/contribution payment, but no payroll run exists for this period. Create a payroll run first, then reconcile.',
            ],
            'smart_reconciliation.payroll_no_match' => [
                'mk' => 'Ова личи на плата/придонес, но износот не се совпаѓа со ниту еден платен список. Проверете дали платниот список за овој период е креиран и пресметан.',
                'sq' => 'Kjo duket si pagë/kontribut, por shuma nuk përputhet me asnjë listë pagash. Kontrolloni nëse lista e pagave për këtë periudhë është krijuar.',
                'tr' => 'Bu bir maaş/prim ödemesine benziyor ama tutar hiçbir bordro ile eşleşmiyor. Bu dönemin bordrosunun oluşturulduğunu kontrol edin.',
                'en' => 'This looks like a payroll/contribution payment, but the amount doesn\'t match any payroll run. Check if the payroll run for this period has been created and calculated.',
            ],
        ];

        return $strings[$key][$locale] ?? $strings[$key]['en'] ?? $key;
    }
}
// CLAUDE-CHECKPOINT
