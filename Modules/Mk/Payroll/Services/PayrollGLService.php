<?php

namespace Modules\Mk\Payroll\Services;

use App\Models\Company;
use Carbon\Carbon;
use IFRS\Models\Account;
use IFRS\Models\Entity;
use IFRS\Models\LineItem;
use IFRS\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Payroll General Ledger Service
 *
 * Posts payroll transactions to the General Ledger using IFRS (eloquent-ifrs package).
 * Follows the same pattern as IfrsAdapter for invoices and payments.
 *
 * GL Account mapping (Macedonian Chart):
 * - DR 420 (Плати на вработени) - Salary expense
 * - DR 421 (Придонеси на товар на работодавач) - Employer contributions
 * - CR 240 (Обврски за нето плати) - Net salary payable
 * - CR 241 (Обврски за придонеси) - Tax/contribution liabilities
 *
 * Note: This service expects the PayrollRun model to exist.
 * The model will be created in the PAY-MODEL-01 ticket.
 */
class PayrollGLService
{
    /**
     * Post a payroll run to the general ledger
     *
     * Creates journal entry:
     * DR 420 (Salary Expense) - Total gross salaries
     * DR 421 (Employer Contributions) - Total employer contributions (pension + health)
     * CR 240 (Net Salary Payable) - Total net salaries (what employees receive)
     * CR 241 (Tax & Contribution Liabilities) - Total employee deductions + employer contributions
     *
     * @param mixed $run PayrollRun model instance
     * @return string|null IFRS transaction ID if posted, null if skipped
     * @throws \Exception
     */
    public function postPayrollRun($run): ?string
    {
        // Check if accounting feature is enabled
        if (! $this->isIfrsEnabled($run->company_id)) {
            Log::info('IFRS accounting disabled, skipping payroll GL posting', [
                'payroll_run_id' => $run->id,
            ]);

            return null;
        }

        // Idempotency check: don't re-post if already posted
        if ($run->ifrs_transaction_id) {
            Log::info('Payroll run already posted to GL, skipping', [
                'payroll_run_id' => $run->id,
                'ifrs_transaction_id' => $run->ifrs_transaction_id,
            ]);

            return $run->ifrs_transaction_id;
        }

        // Only post approved payroll runs
        if ($run->status !== 'approved') {
            throw new \Exception('Cannot post payroll run to GL: Only approved payroll runs can be posted');
        }

        // Validate payroll run has actual values to post
        if (!$run->total_gross || $run->total_gross <= 0) {
            throw new \Exception('Cannot post payroll run to GL: Total gross salary is zero. Please recalculate the payroll run first.');
        }

        if (!$run->total_net || $run->total_net <= 0) {
            throw new \Exception('Cannot post payroll run to GL: Total net salary is zero. Please recalculate the payroll run first.');
        }

        try {
            DB::beginTransaction();

            // Get or create IFRS entity for company
            $entity = $this->getOrCreateEntity($run->company);
            if (! $entity) {
                throw new \Exception('Failed to get or create IFRS Entity for company');
            }

            // Set user entity context for IFRS EntityScope (required by eloquent-ifrs)
            $this->setUserEntityContext($entity);

            // Get GL accounts
            $salaryExpenseAccount = $this->getSalaryExpenseAccount($run->company_id, $entity->id);
            $employerContributionAccount = $this->getEmployerContributionAccount($run->company_id, $entity->id);
            $netSalaryPayableAccount = $this->getNetSalaryPayableAccount($run->company_id, $entity->id);
            $taxLiabilityAccount = $this->getTaxLiabilityAccount($run->company_id, $entity->id);

            // Build narration
            $periodStart = Carbon::parse($run->period_start)->format('Y-m-d');
            $periodEnd = Carbon::parse($run->period_end)->format('Y-m-d');
            $narration = "Payroll Run #{$run->id} ({$periodStart} to {$periodEnd}) - {$run->employee_count} employees";

            // Create IFRS Transaction (Journal Entry)
            $transaction = Transaction::create([
                'account_id' => $salaryExpenseAccount->id,
                'transaction_date' => $run->payment_date ?? Carbon::now(),
                'narration' => $narration,
                'transaction_type' => Transaction::JN, // Journal Entry
                'currency_id' => $this->getCurrencyId($run->company_id),
                'entity_id' => $entity->id,
            ]);

            // DEBIT: Salary Expense (total gross)
            // PayrollRun stores amounts in cents (integer), IFRS expects base currency units
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $salaryExpenseAccount->id,
                'amount' => $run->total_gross / 100, // Convert cents to MKD
                'quantity' => 1,
                'credited' => false, // Debit entry
                'entity_id' => $entity->id,
            ]);

            // DEBIT: Employer Contributions (pension + health)
            // In MK model, ALL contributions are from gross — employer cost = gross, no add-on.
            // Only post DR 421 if there are actual employer contributions (non-MK scenarios).
            $employerContributions = ($run->total_employer_tax ?? 0) / 100;
            if ($employerContributions > 0) {
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $employerContributionAccount->id,
                    'amount' => $employerContributions,
                    'quantity' => 1,
                    'credited' => false, // Debit entry
                    'entity_id' => $entity->id,
                ]);
            }

            // CREDIT: Net Salary Payable (what employees receive)
            LineItem::create([
                'transaction_id' => $transaction->id,
                'account_id' => $netSalaryPayableAccount->id,
                'amount' => $run->total_net / 100, // Convert cents to MKD
                'quantity' => 1,
                'credited' => true, // Credit entry
                'entity_id' => $entity->id,
            ]);

            // CREDIT: Split liability sub-accounts (2440/2450/2460/2470/2480)
            // Aggregate contribution amounts from all payroll lines
            $totalPensionEE = 0;
            $totalPensionER = 0;
            $totalHealthEE = 0;
            $totalHealthER = 0;
            $totalUnemployment = 0;
            $totalAdditional = 0;
            $totalIncomeTax = 0;

            foreach ($run->lines as $line) {
                $totalPensionEE += $line->pension_contribution_employee ?? 0;
                $totalPensionER += $line->pension_contribution_employer ?? 0;
                $totalHealthEE += $line->health_contribution_employee ?? 0;
                $totalHealthER += $line->health_contribution_employer ?? 0;
                $totalUnemployment += $line->unemployment_contribution ?? 0;
                $totalAdditional += $line->additional_contribution ?? 0;
                $totalIncomeTax += $line->income_tax_amount ?? 0;
            }

            // CR 2440 — Pension (EE + ER)
            $pensionAccount = $this->getOrCreateLiabilitySubAccount($run->company_id, $entity->id, '2440', 'Обврски за пензиско осигурување');
            $pensionTotal = ($totalPensionEE + $totalPensionER) / 100;
            if ($pensionTotal > 0) {
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $pensionAccount->id,
                    'amount' => $pensionTotal,
                    'quantity' => 1,
                    'credited' => true,
                    'entity_id' => $entity->id,
                ]);
            }

            // CR 2450 — Health (EE + ER)
            $healthAccount = $this->getOrCreateLiabilitySubAccount($run->company_id, $entity->id, '2450', 'Обврски за здравствено осигурување');
            $healthTotal = ($totalHealthEE + $totalHealthER) / 100;
            if ($healthTotal > 0) {
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $healthAccount->id,
                    'amount' => $healthTotal,
                    'quantity' => 1,
                    'credited' => true,
                    'entity_id' => $entity->id,
                ]);
            }

            // CR 2460 — Unemployment
            $unemploymentAccount = $this->getOrCreateLiabilitySubAccount($run->company_id, $entity->id, '2460', 'Обврски за осигурување од невработеност');
            $unemploymentTotal = $totalUnemployment / 100;
            if ($unemploymentTotal > 0) {
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $unemploymentAccount->id,
                    'amount' => $unemploymentTotal,
                    'quantity' => 1,
                    'credited' => true,
                    'entity_id' => $entity->id,
                ]);
            }

            // CR 2470 — Additional contribution
            $additionalAccount = $this->getOrCreateLiabilitySubAccount($run->company_id, $entity->id, '2470', 'Обврски за дополнителен придонес');
            $additionalTotal = $totalAdditional / 100;
            if ($additionalTotal > 0) {
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $additionalAccount->id,
                    'amount' => $additionalTotal,
                    'quantity' => 1,
                    'credited' => true,
                    'entity_id' => $entity->id,
                ]);
            }

            // CR 2480 — Income tax (PDD-GI)
            $incomeTaxAccount = $this->getOrCreateLiabilitySubAccount($run->company_id, $entity->id, '2480', 'Обврски за данок на личен доход');
            $incomeTaxTotal = $totalIncomeTax / 100;
            if ($incomeTaxTotal > 0) {
                LineItem::create([
                    'transaction_id' => $transaction->id,
                    'account_id' => $incomeTaxAccount->id,
                    'amount' => $incomeTaxTotal,
                    'quantity' => 1,
                    'credited' => true,
                    'entity_id' => $entity->id,
                ]);
            }

            // Reload line items before posting (required by IFRS package)
            $transaction->load('lineItems');

            // Post the transaction to the ledger
            $transaction->post();

            // Store the IFRS transaction ID on the payroll run
            $run->update(['ifrs_transaction_id' => $transaction->id]);

            DB::commit();

            Log::info('Payroll run posted to GL', [
                'payroll_run_id' => $run->id,
                'ifrs_transaction_id' => $transaction->id,
                'total_gross_cents' => $run->total_gross,
                'total_gross_mkd' => $run->total_gross / 100,
                'total_net_mkd' => $run->total_net / 100,
            ]);

            return $transaction->id;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to post payroll run to GL', [
                'payroll_run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Reverse a payroll run posting
     *
     * Creates a reversing journal entry.
     *
     * @param mixed $run PayrollRun model instance
     * @return void
     * @throws \Exception
     */
    public function reversePayrollRun($run): void
    {
        if (! $run->ifrs_transaction_id) {
            throw new \Exception('Cannot reverse payroll run: Not posted to GL');
        }

        try {
            DB::beginTransaction();

            // Get the original transaction
            $originalTransaction = Transaction::find($run->ifrs_transaction_id);
            if (! $originalTransaction) {
                throw new \Exception('Original transaction not found');
            }

            $entity = $this->getOrCreateEntity($run->company);

            // Set user entity context for IFRS EntityScope (required by eloquent-ifrs)
            $this->setUserEntityContext($entity);

            // Create reversing entry
            $reversingTransaction = Transaction::create([
                'account_id' => $originalTransaction->account_id,
                'transaction_date' => Carbon::now(),
                'narration' => "REVERSAL: {$originalTransaction->narration}",
                'transaction_type' => Transaction::JN,
                'currency_id' => $this->getCurrencyId($run->company_id),
                'entity_id' => $entity->id,
            ]);

            // Reverse all line items (flip debits and credits)
            foreach ($originalTransaction->lineItems as $originalLine) {
                LineItem::create([
                    'transaction_id' => $reversingTransaction->id,
                    'account_id' => $originalLine->account_id,
                    'amount' => $originalLine->amount,
                    'quantity' => 1,
                    'credited' => ! $originalLine->credited, // Flip debit/credit
                    'entity_id' => $entity->id,
                ]);
            }

            $reversingTransaction->post();

            // Clear the IFRS transaction ID from the payroll run
            $run->update(['ifrs_transaction_id' => null]);

            DB::commit();

            Log::info('Payroll run reversed in GL', [
                'payroll_run_id' => $run->id,
                'original_transaction_id' => $originalTransaction->id,
                'reversing_transaction_id' => $reversingTransaction->id,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to reverse payroll run in GL', [
                'payroll_run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get journal preview for a payroll run (before posting)
     *
     * @param mixed $run PayrollRun model instance
     * @return array Journal entry preview
     */
    public function getJournalPreview($run): array
    {
        // Convert cents to MKD for display
        $totalGross = ($run->total_gross ?? 0) / 100;
        $totalNet = ($run->total_net ?? 0) / 100;
        $employerContributions = ($run->total_employer_tax ?? 0) / 100;

        // Aggregate contribution amounts from lines
        $totalPensionEE = 0;
        $totalPensionER = 0;
        $totalHealthEE = 0;
        $totalHealthER = 0;
        $totalUnemployment = 0;
        $totalAdditional = 0;
        $totalIncomeTax = 0;

        if ($run->lines) {
            foreach ($run->lines as $line) {
                $totalPensionEE += $line->pension_contribution_employee ?? 0;
                $totalPensionER += $line->pension_contribution_employer ?? 0;
                $totalHealthEE += $line->health_contribution_employee ?? 0;
                $totalHealthER += $line->health_contribution_employer ?? 0;
                $totalUnemployment += $line->unemployment_contribution ?? 0;
                $totalAdditional += $line->additional_contribution ?? 0;
                $totalIncomeTax += $line->income_tax_amount ?? 0;
            }
        }

        $pensionTotal = ($totalPensionEE + $totalPensionER) / 100;
        $healthTotal = ($totalHealthEE + $totalHealthER) / 100;
        $unemploymentTotal = $totalUnemployment / 100;
        $additionalTotal = $totalAdditional / 100;
        $incomeTaxTotal = $totalIncomeTax / 100;
        $totalLiabilities = $pensionTotal + $healthTotal + $unemploymentTotal + $additionalTotal + $incomeTaxTotal;

        $lines = [
            [
                'account_code' => '420',
                'account_name' => 'Плати на вработени (Salary Expense)',
                'debit' => $totalGross,
                'credit' => 0,
            ],
        ];

        // Only include DR 421 if there are actual employer contributions (not in MK model)
        if ($employerContributions > 0) {
            $lines[] = [
                'account_code' => '421',
                'account_name' => 'Придонеси на товар на работодавач (Employer Contributions)',
                'debit' => $employerContributions,
                'credit' => 0,
            ];
        }

        $lines = array_merge($lines, [
            [
                'account_code' => '240',
                'account_name' => 'Обврски за нето плати (Net Salary Payable)',
                'debit' => 0,
                'credit' => $totalNet,
            ],
            [
                'account_code' => '2440',
                'account_name' => 'Обврски за пензиско осигурување (Pension)',
                'debit' => 0,
                'credit' => $pensionTotal,
            ],
            [
                'account_code' => '2450',
                'account_name' => 'Обврски за здравствено осигурување (Health)',
                'debit' => 0,
                'credit' => $healthTotal,
            ],
            [
                'account_code' => '2460',
                'account_name' => 'Обврски за осигурување од невработеност (Unemployment)',
                'debit' => 0,
                'credit' => $unemploymentTotal,
            ],
            [
                'account_code' => '2470',
                'account_name' => 'Обврски за дополнителен придонес (Additional)',
                'debit' => 0,
                'credit' => $additionalTotal,
            ],
            [
                'account_code' => '2480',
                'account_name' => 'Обврски за данок на личен доход (Income Tax)',
                'debit' => 0,
                'credit' => $incomeTaxTotal,
            ],
        ];

        return [
            'narration' => "Payroll Run #{$run->id}",
            'date' => $run->payment_date ?? Carbon::now()->toDateString(),
            'lines' => $lines,
            'totals' => [
                'debit' => ($totalGross + $employerContributions),
                'credit' => ($totalNet + $totalLiabilities),
            ],
        ];
    }

    /**
     * Check if a payroll run can be posted to GL
     *
     * @param mixed $run PayrollRun model instance
     * @return bool
     */
    public function canPost($run): bool
    {
        // Must be approved
        if ($run->status !== 'approved') {
            return false;
        }

        // Must not be already posted
        if ($run->ifrs_transaction_id) {
            return false;
        }

        // Must have at least one employee
        if ($run->employee_count < 1) {
            return false;
        }

        // IFRS must be enabled
        if (! $this->isIfrsEnabled($run->company_id)) {
            return false;
        }

        return true;
    }

    /**
     * Check if IFRS accounting is enabled
     */
    private function isIfrsEnabled(int $companyId): bool
    {
        // Use same logic as IfrsAdapter
        $globalEnabled = config('ifrs.enabled', false) ||
            env('FEATURE_ACCOUNTING_BACKBONE', false) ||
            (function_exists('feature') && feature('accounting-backbone'));

        if (! $globalEnabled) {
            return false;
        }

        $companySetting = \App\Models\CompanySetting::getSetting('ifrs_enabled', $companyId);

        return $companySetting === 'YES' || $companySetting === true || $companySetting === '1';
    }

    /**
     * Get or create IFRS Entity for a company
     */
    private function getOrCreateEntity(Company $company): ?Entity
    {
        // Delegate to IfrsAdapter if available
        if (class_exists('App\Domain\Accounting\IfrsAdapter')) {
            $adapter = new \App\Domain\Accounting\IfrsAdapter();
            $reflection = new \ReflectionClass($adapter);
            $method = $reflection->getMethod('getOrCreateEntityForCompany');
            $method->setAccessible(true);

            return $method->invoke($adapter, $company);
        }

        return null;
    }

    /**
     * Get currency ID for company
     */
    private function getCurrencyId(int $companyId): int
    {
        // Delegate to IfrsAdapter if available
        if (class_exists('App\Domain\Accounting\IfrsAdapter')) {
            $adapter = new \App\Domain\Accounting\IfrsAdapter();
            $reflection = new \ReflectionClass($adapter);
            $method = $reflection->getMethod('getCurrencyId');
            $method->setAccessible(true);

            return $method->invoke($adapter, $companyId);
        }

        return 1; // Fallback
    }

    /**
     * Get or create Salary Expense account (420)
     */
    private function getSalaryExpenseAccount(int $companyId, int $entityId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::OPERATING_EXPENSE,
                'code' => '420',
                'entity_id' => $entityId,
            ],
            [
                'name' => 'Плати на вработени',
                'currency_id' => $this->getCurrencyId($companyId),
            ]
        );
    }

    /**
     * Get or create Employer Contribution account (421)
     */
    private function getEmployerContributionAccount(int $companyId, int $entityId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::OPERATING_EXPENSE,
                'code' => '421',
                'entity_id' => $entityId,
            ],
            [
                'name' => 'Придонеси на товар на работодавач',
                'currency_id' => $this->getCurrencyId($companyId),
            ]
        );
    }

    /**
     * Get or create Net Salary Payable account (240)
     */
    private function getNetSalaryPayableAccount(int $companyId, int $entityId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::CURRENT_LIABILITY,
                'code' => '240',
                'entity_id' => $entityId,
            ],
            [
                'name' => 'Обврски за нето плати',
                'currency_id' => $this->getCurrencyId($companyId),
            ]
        );
    }

    /**
     * Get or create Tax Liability account (241)
     */
    private function getTaxLiabilityAccount(int $companyId, int $entityId): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::CURRENT_LIABILITY,
                'code' => '241',
                'entity_id' => $entityId,
            ],
            [
                'name' => 'Обврски за придонеси',
                'currency_id' => $this->getCurrencyId($companyId),
            ]
        );
    }

    /**
     * Get or create a liability sub-account for payroll postings
     */
    private function getOrCreateLiabilitySubAccount(int $companyId, int $entityId, string $code, string $name): Account
    {
        return Account::firstOrCreate(
            [
                'account_type' => Account::CURRENT_LIABILITY,
                'code' => $code,
                'entity_id' => $entityId,
            ],
            [
                'name' => $name,
                'currency_id' => $this->getCurrencyId($companyId),
            ]
        );
    }

    /**
     * Set user entity context for IFRS EntityScope
     *
     * The eloquent-ifrs package's Segregating trait relies on Auth::user()->entity
     * when creating models. This method sets the entity context on the current user.
     */
    private function setUserEntityContext(?Entity $entity): void
    {
        if (! $entity) {
            return;
        }

        $user = auth()->user();
        if ($user) {
            // Set entity_id on user for IFRS EntityScope
            $user->entity_id = $entity->id;
            // Also set the entity relationship directly
            $user->setRelation('entity', $entity);
        }
    }
}

// LLM-CHECKPOINT
