<?php

namespace Modules\Mk\Payroll\Services;

use App\Models\PayrollRun;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

/**
 * Payment Order Service
 *
 * Generates PP50 (budget payment order) and PP30 (general payment order) PDFs
 * for payroll tax payments to UJP (Управа за јавни приходи).
 *
 * PP50 = Налог за плаќање ПП50 (budget payment: pension, health, unemployment)
 * PP30 = Налог за плаќање ПП30 (general payment: income tax)
 *
 * Each contribution type gets its own PP50 with the correct:
 * - Приходна шифра (revenue code)
 * - Сметка на примач (recipient account at НБРМ)
 */
class PaymentOrderService
{
    /**
     * Revenue codes and treasury accounts for MK payroll contributions
     */
    private const CONTRIBUTION_ACCOUNTS = [
        'pension_employee' => [
            'revenue_code' => '713111',
            'recipient_account' => '100000000063095',
            'recipient_name' => 'Фонд на ПИОСМ - придонес од осигуреник',
            'description' => 'Придонес за ПИО - на товар на работник',
        ],
        'pension_employer' => [
            'revenue_code' => '713112',
            'recipient_account' => '100000000063095',
            'recipient_name' => 'Фонд на ПИОСМ - придонес од работодавач',
            'description' => 'Придонес за ПИО - на товар на работодавач',
        ],
        'health_employee' => [
            'revenue_code' => '713211',
            'recipient_account' => '100000000063101',
            'recipient_name' => 'Фонд за здравствено осигурување - од осигуреник',
            'description' => 'Придонес за ЗО - на товар на работник',
        ],
        'health_employer' => [
            'revenue_code' => '713212',
            'recipient_account' => '100000000063101',
            'recipient_name' => 'Фонд за здравствено осигурување - од работодавач',
            'description' => 'Придонес за ЗО - на товар на работодавач',
        ],
        'unemployment' => [
            'revenue_code' => '713311',
            'recipient_account' => '100000000063118',
            'recipient_name' => 'Агенција за вработување - невработеност',
            'description' => 'Придонес за невработеност',
        ],
        'additional' => [
            'revenue_code' => '713314',
            'recipient_account' => '100000000063095',
            'recipient_name' => 'Фонд на ПИОСМ - дополнителен придонес',
            'description' => 'Дополнителен придонес за ПИО',
        ],
        'income_tax' => [
            'revenue_code' => '711111',
            'recipient_account' => '100000000063088',
            'recipient_name' => 'Буџет на РСМ - данок на личен доход',
            'description' => 'Данок на личен доход (ДЛД)',
        ],
    ];

    /**
     * Get contribution accounts config (for external use by PP50 generator).
     */
    public function getContributionAccounts(): array
    {
        return self::CONTRIBUTION_ACCOUNTS;
    }

    /**
     * Generate PP50 PDF for a specific contribution type
     *
     * @param PayrollRun $run The payroll run
     * @param string $type Contribution type key (pension_employee, health_employee, etc.)
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generatePP50(PayrollRun $run, string $type): \Barryvdh\DomPDF\PDF
    {
        if (!isset(self::CONTRIBUTION_ACCOUNTS[$type])) {
            throw new \InvalidArgumentException("Unknown contribution type: {$type}");
        }

        $account = self::CONTRIBUTION_ACCOUNTS[$type];
        $company = $run->company;

        // Calculate total amount for this contribution type from run lines
        $amount = $this->getContributionAmount($run, $type);

        $data = [
            'company' => $company,
            'run' => $run,
            'type' => $type,
            'account' => $account,
            'amount' => $amount,
            'amount_formatted' => number_format($amount / 100, 2, '.', ','),
            'period' => sprintf('%02d/%d', $run->period_month, $run->period_year),
            'date' => now()->format('d.m.Y'),
            'payer_account' => $company->bank_account_number ?? '',
            'payer_name' => $company->name,
            'payer_address' => trim(($company->address_street_1 ?? '') . ' ' . ($company->city ?? '')),
            'payer_edb' => $company->edb ?? '',
        ];

        Log::info('Generated PP50 payment order', [
            'payroll_run_id' => $run->id,
            'type' => $type,
            'amount' => $amount,
        ]);

        return Pdf::loadView('app.pdf.payroll.pp50', $data)
            ->setPaper('a4');
    }

    /**
     * Generate PP30 PDF for income tax payment
     *
     * @param PayrollRun $run The payroll run
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generatePP30(PayrollRun $run): \Barryvdh\DomPDF\PDF
    {
        $account = self::CONTRIBUTION_ACCOUNTS['income_tax'];
        $company = $run->company;

        // Sum income tax from all lines
        $amount = $run->lines()->included()->sum('income_tax_amount');

        $data = [
            'company' => $company,
            'run' => $run,
            'account' => $account,
            'amount' => $amount,
            'amount_formatted' => number_format($amount / 100, 2, '.', ','),
            'period' => sprintf('%02d/%d', $run->period_month, $run->period_year),
            'date' => now()->format('d.m.Y'),
            'payer_account' => $company->bank_account_number ?? '',
            'payer_name' => $company->name,
            'payer_address' => trim(($company->address_street_1 ?? '') . ' ' . ($company->city ?? '')),
            'payer_edb' => $company->edb ?? '',
        ];

        Log::info('Generated PP30 payment order', [
            'payroll_run_id' => $run->id,
            'amount' => $amount,
        ]);

        return Pdf::loadView('app.pdf.payroll.pp30', $data)
            ->setPaper('a4');
    }

    /**
     * Get the contribution amount from payroll run lines for a given type
     */
    private function getContributionAmount(PayrollRun $run, string $type): int
    {
        $columnMap = [
            'pension_employee' => 'pension_contribution_employee',
            'pension_employer' => 'pension_contribution_employer',
            'health_employee' => 'health_contribution_employee',
            'health_employer' => 'health_contribution_employer',
            'unemployment' => 'unemployment_contribution',
            'additional' => 'additional_contribution',
            'income_tax' => 'income_tax_amount',
        ];

        $column = $columnMap[$type] ?? null;
        if (!$column) {
            return 0;
        }

        return (int) $run->lines()->included()->sum($column);
    }
}

// CLAUDE-CHECKPOINT
