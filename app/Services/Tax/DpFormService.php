<?php

namespace App\Services\Tax;

use App\Models\Company;
use App\Models\TaxReturn;
use App\Services\CorporateIncomeTaxService;
use App\Services\CitXmlService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * ДП — Даночна пријава за данок на добивка (Annual Corporate Tax Return)
 *
 * The official tax return form filed with UJP, summarizing:
 *   - Revenue/expenses from the income statement
 *   - Non-deductible expense adjustments
 *   - Tax base calculation
 *   - 10% corporate tax
 *   - Advance payments and balance due/refund
 *
 * This complements the ДБ (Tax Balance) which is the detailed worksheet.
 * The ДП is the actual filing document.
 *
 * 35 AOP fields organized in 8 sections (I-VIII).
 * Filing deadline: March 15 of the following year.
 */
class DpFormService extends TaxFormService
{
    protected CorporateIncomeTaxService $citService;
    protected CitXmlService $citXmlService;

    public function __construct(
        CorporateIncomeTaxService $citService,
        CitXmlService $citXmlService
    ) {
        $this->citService = $citService;
        $this->citXmlService = $citXmlService;
    }

    public function formCode(): string
    {
        return 'ДП';
    }

    public function formTitle(): string
    {
        return 'ДАНОЧНА ПРИЈАВА за данок на добивка';
    }

    public function periodType(): string
    {
        return 'annual';
    }

    public function returnType(): string
    {
        return TaxReturn::TYPE_CORPORATE;
    }

    /**
     * Collect all 35 AOP fields for the ДП form.
     *
     * Auto-populated from IFRS income statement:
     *   AOP 01-03: Revenue breakdown
     *   AOP 05-09: Expense breakdown
     *   AOP 32: Advance payments
     *
     * Formula fields (auto-calculated):
     *   AOP 04 = sum(01:03), AOP 10 = sum(05:09), AOP 11 = 04-10
     *   AOP 20 = sum(12:19), AOP 21 = 11+20, AOP 26 = max(0, 21-22-23-24-25)
     *   AOP 27 = 26*0.10, AOP 31 = max(0, 27-28-29-30)
     *   AOP 34 = 32+33, AOP 35 = 31-34
     *
     * Manual fields: everything else (user enters, defaults to 0)
     */
    public function collect(
        Company $company,
        int $year,
        ?int $month = null,
        ?int $quarter = null,
        array $overrides = []
    ): array {
        $config = config('ujp_forms.dp');
        $aop = [];

        // Step 1: Initialize all fields to 0
        for ($i = 1; $i <= 35; $i++) {
            $aopKey = str_pad($i, 2, '0', STR_PAD_LEFT);
            $aop[$aopKey] = 0.0;
        }

        // Step 2: Auto-populate revenue/expense breakdown from income statement
        try {
            $incomeData = $this->getIncomeStatementBreakdown($company, $year);
            $aop['01'] = $incomeData['revenue_sales'];
            $aop['02'] = $incomeData['revenue_financial'];
            $aop['03'] = $incomeData['revenue_other'];
            $aop['05'] = $incomeData['expense_materials'];
            $aop['06'] = $incomeData['expense_salaries'];
            $aop['07'] = $incomeData['expense_depreciation'];
            $aop['08'] = $incomeData['expense_financial'];
            $aop['09'] = $incomeData['expense_other'];
        } catch (\Exception $e) {
            Log::warning('DP form: Could not auto-populate income statement data', [
                'company_id' => $company->id,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);
        }

        // Step 3: Auto-populate advance payments (AOP 32)
        try {
            $aop['32'] = $this->citService->getAdvancePayments($company, $year);
        } catch (\Exception $e) {
            $aop['32'] = 0.0;
        }

        // Step 4: Apply manual overrides (for non-formula fields)
        $formulaFields = ['04', '10', '11', '20', '21', '26', '27', '31', '34', '35'];
        foreach ($overrides as $key => $value) {
            $aopKey = str_pad($key, 2, '0', STR_PAD_LEFT);
            if (isset($aop[$aopKey]) && !in_array($aopKey, $formulaFields)) {
                $aop[$aopKey] = (float) $value;
            }
        }

        // Step 5: Calculate formula fields

        // Section I: Total Revenue
        $aop['04'] = $aop['01'] + $aop['02'] + $aop['03'];

        // Section II: Total Expenses
        $aop['10'] = $aop['05'] + $aop['06'] + $aop['07'] + $aop['08'] + $aop['09'];

        // Section III: Profit/Loss before tax
        $aop['11'] = $aop['04'] - $aop['10'];

        // Section IV: Total non-deductible expenses
        $aop['20'] = 0;
        for ($i = 12; $i <= 19; $i++) {
            $aop['20'] += $aop[str_pad($i, 2, '0', STR_PAD_LEFT)];
        }

        // Section V: Tax base
        $aop['21'] = $aop['11'] + $aop['20'];
        $aop['26'] = max(0, $aop['21'] - $aop['22'] - $aop['23'] - $aop['24'] - $aop['25']);

        // Section VI: Tax calculation
        $aop['27'] = round($aop['26'] * 0.10, 2);
        $aop['31'] = max(0, $aop['27'] - $aop['28'] - $aop['29'] - $aop['30']);

        // Section VII: Advance payments
        $aop['34'] = $aop['32'] + $aop['33'];

        // Section VIII: Balance due / refund (can be negative = refund)
        $aop['35'] = $aop['31'] - $aop['34'];

        return [
            'aop' => $aop,
            'year' => $year,
            'config' => $config,
        ];
    }

    /**
     * Validate DP form data — arithmetic consistency checks.
     */
    public function validate(array $data): array
    {
        $errors = [];
        $warnings = [];
        $aop = $data['aop'] ?? [];

        // Check Section I total
        $expectedAop04 = ($aop['01'] ?? 0) + ($aop['02'] ?? 0) + ($aop['03'] ?? 0);
        if (abs(($aop['04'] ?? 0) - $expectedAop04) > 0.01) {
            $errors[] = 'АОП 04 (Вкупни приходи) не е еднакво на збир АОП 01+02+03';
        }

        // Check Section II total
        $expectedAop10 = ($aop['05'] ?? 0) + ($aop['06'] ?? 0) + ($aop['07'] ?? 0) + ($aop['08'] ?? 0) + ($aop['09'] ?? 0);
        if (abs(($aop['10'] ?? 0) - $expectedAop10) > 0.01) {
            $errors[] = 'АОП 10 (Вкупни расходи) не е еднакво на збир АОП 05-09';
        }

        // Check profit/loss
        if (abs(($aop['11'] ?? 0) - (($aop['04'] ?? 0) - ($aop['10'] ?? 0))) > 0.01) {
            $errors[] = 'АОП 11 (Добивка/загуба) не е еднакво на АОП 04 - АОП 10';
        }

        // Check tax calculation
        $expected27 = round(($aop['26'] ?? 0) * 0.10, 2);
        if (abs(($aop['27'] ?? 0) - $expected27) > 0.01) {
            $errors[] = 'АОП 27 (Пресметан данок) не е еднакво на АОП 26 × 10%';
        }

        // Warning if revenue is 0
        if (abs($aop['04'] ?? 0) < 0.01) {
            $warnings[] = 'АОП 04 (Вкупни приходи) е 0 — проверете дали има книжења за годината';
        }

        // Warning if loss
        if (($aop['11'] ?? 0) < 0) {
            $warnings[] = sprintf('Оствареата загуба е %.2f — нема данок за плаќање', abs($aop['11']));
        }

        // Warning if overpayment (refund due)
        if (($aop['35'] ?? 0) < 0) {
            $warnings[] = sprintf('АОП 35 е негативен (%.2f) — имате повеќе платен данок за поврат', abs($aop['35']));
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Generate XML for UJP upload.
     */
    public function toXml(Company $company, array $data): string
    {
        $year = $data['year'];
        $aop = $data['aop'] ?? [];

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><DanokNaDobivka/>');
        $xml->addAttribute('xmlns', 'http://ujp.gov.mk/dp');

        // Header
        $header = $xml->addChild('Zaglavje');
        $header->addChild('Obrazec', 'ДП');
        $header->addChild('EDB', $company->vat_number ?? $company->vat_id ?? '');
        $header->addChild('Naziv', $company->name);
        $header->addChild('Godina', $year);
        $header->addChild('PeriodOd', sprintf('01.01.%d', $year));
        $header->addChild('PeriodDo', sprintf('31.12.%d', $year));

        // AOP fields
        $fields = $xml->addChild('Polinja');
        foreach ($aop as $code => $value) {
            $field = $fields->addChild('AOP');
            $field->addAttribute('kod', $code);
            $field->addChild('Iznos', $this->formatAmount($value));
        }

        return $xml->asXML();
    }

    /**
     * Generate PDF matching official ДП layout.
     */
    public function toPdf(Company $company, array $data, int $year): Response
    {
        $config = config('ujp_forms.dp');

        $pdf = Pdf::loadView('app.pdf.reports.ujp.dp', [
            'company' => $company,
            'aop' => $data['aop'] ?? [],
            'year' => $year,
            'config' => $config,
            'formCode' => 'ДП',
            'formTitle' => 'ДАНОЧНА ПРИЈАВА',
            'formSubtitle' => 'за данок на добивка',
            'sluzhbenVesnik' => $config['sluzhben_vesnik'] ?? '',
            'periodStart' => sprintf('01.01.%d', $year),
            'periodEnd' => sprintf('31.12.%d', $year),
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('DP_' . $company->name . '_' . $year . '.pdf');
    }

    /**
     * Get income statement breakdown by category from IFRS.
     *
     * Maps IFRS accounts to the DP form revenue/expense categories.
     * Uses the MK chart of accounts (Правилник 174/2011):
     *   Class 6xxx = Revenue, Class 4xxx = Expenses
     */
    protected function getIncomeStatementBreakdown(Company $company, int $year): array
    {
        $ifrsAdapter = app(\App\Domain\Accounting\IfrsAdapter::class);

        $incomeStatement = $ifrsAdapter->getIncomeStatement(
            $company,
            "{$year}-01-01",
            "{$year}-12-31"
        );

        $result = [
            'revenue_sales' => 0.0,
            'revenue_financial' => 0.0,
            'revenue_other' => 0.0,
            'expense_materials' => 0.0,
            'expense_salaries' => 0.0,
            'expense_depreciation' => 0.0,
            'expense_financial' => 0.0,
            'expense_other' => 0.0,
        ];

        if (isset($incomeStatement['error'])) {
            return $result;
        }

        // Revenue classification by account code prefix
        foreach ($incomeStatement['income_statement']['revenues'] ?? [] as $account) {
            $code = $account['code'] ?? '';
            $balance = abs($account['balance'] ?? 0);

            if (str_starts_with($code, '660') || str_starts_with($code, '661') || str_starts_with($code, '662')) {
                // Financial revenue: interest, dividends, exchange gains
                $result['revenue_financial'] += $balance;
            } elseif (str_starts_with($code, '67') || str_starts_with($code, '69')) {
                // Other revenue
                $result['revenue_other'] += $balance;
            } else {
                // Sales revenue (60x, 61x, 62x, 63x, 64x, 65x)
                $result['revenue_sales'] += $balance;
            }
        }

        // Expense classification by account code prefix
        foreach ($incomeStatement['income_statement']['expenses'] ?? [] as $account) {
            $code = $account['code'] ?? '';
            $balance = abs($account['balance'] ?? 0);

            if (str_starts_with($code, '400') || str_starts_with($code, '401') || str_starts_with($code, '402')) {
                // Materials, raw materials
                $result['expense_materials'] += $balance;
            } elseif (str_starts_with($code, '410') || str_starts_with($code, '411') || str_starts_with($code, '412') || str_starts_with($code, '413')) {
                // Salaries and employee costs
                $result['expense_salaries'] += $balance;
            } elseif (str_starts_with($code, '420') || str_starts_with($code, '421')) {
                // Depreciation
                $result['expense_depreciation'] += $balance;
            } elseif (str_starts_with($code, '460') || str_starts_with($code, '461') || str_starts_with($code, '462')) {
                // Financial expenses: interest, exchange losses
                $result['expense_financial'] += $balance;
            } else {
                // Other expenses
                $result['expense_other'] += $balance;
            }
        }

        // Round all values
        foreach ($result as $key => $value) {
            $result[$key] = round($value, 2);
        }

        return $result;
    }
}

// CLAUDE-CHECKPOINT
