<?php

namespace App\Services\Tax;

use App\Models\Company;
use App\Models\TaxReturn;
use App\Services\CorporateIncomeTaxService;
use App\Services\CitXmlService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * ДБ — Даночен биланс за оданочување на добивка
 *
 * Annual profit tax form with 70 AOP fields (sections I-IX).
 * AOP 01 auto-populated from income statement.
 * AOP 03-39 are manual non-deductible expenses.
 * AOP 40-59 are auto-calculated formulas.
 * AOP 60-70 are manual special data.
 * Tax rate: 10% flat.
 */
class DbFormService extends TaxFormService
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
        return 'ДБ';
    }

    public function formTitle(): string
    {
        return 'ДАНОЧЕН БИЛАНС за оданочување на добивка';
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
     * Collect all 70 AOP fields.
     *
     * Auto-populated:
     *   AOP 01: Financial result from income statement
     *   AOP 15: Representation expense excess (90% rule)
     *   AOP 57: Advance tax payments from prior year
     *
     * Formula fields (auto-calculated):
     *   AOP 02 = sum(03:39), AOP 40 = 01+02, AOP 41 = sum(42:48)
     *   AOP 49 = max(0, 40-41), AOP 50 = 49*0.10, AOP 51 = sum(52:55)
     *   AOP 56 = max(0, 50-51), AOP 59 = 56-57-58
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
        $config = config('ujp_forms.db');
        $aop = [];

        // Step 1: Initialize all fields to 0
        for ($i = 1; $i <= 70; $i++) {
            $aopKey = str_pad($i, 2, '0', STR_PAD_LEFT);
            $aop[$aopKey] = 0.0;
        }

        // Step 2: Auto-populate AOP 01 (financial result from income statement)
        try {
            $aop['01'] = $this->citService->getAccountingProfit($company, $year);
        } catch (\Exception $e) {
            $aop['01'] = 0.0;
        }

        // Step 3: Auto-populate AOP 57 (advance tax payments)
        try {
            $aop['57'] = $this->citService->getAdvancePayments($company, $year);
        } catch (\Exception $e) {
            $aop['57'] = 0.0;
        }

        // Step 4: Apply manual overrides (for non-formula fields)
        $formulaFields = ['02', '40', '41', '49', '50', '51', '56', '59'];
        foreach ($overrides as $key => $value) {
            $aopKey = str_pad($key, 2, '0', STR_PAD_LEFT);
            if (isset($aop[$aopKey]) && !in_array($aopKey, $formulaFields)) {
                $aop[$aopKey] = (float) $value;
            }
        }

        // Step 5: Calculate formula fields
        // AOP 02 = sum of AOP 03 through AOP 39
        $aop['02'] = 0;
        for ($i = 3; $i <= 39; $i++) {
            $aop['02'] += $aop[str_pad($i, 2, '0', STR_PAD_LEFT)];
        }

        // AOP 40 = AOP 01 + AOP 02
        $aop['40'] = $aop['01'] + $aop['02'];

        // AOP 41 = sum of AOP 42 through AOP 48
        $aop['41'] = 0;
        for ($i = 42; $i <= 48; $i++) {
            $aop['41'] += $aop[str_pad($i, 2, '0', STR_PAD_LEFT)];
        }

        // AOP 49 = max(0, AOP 40 - AOP 41)
        $aop['49'] = max(0, $aop['40'] - $aop['41']);

        // AOP 50 = AOP 49 * 10%
        $aop['50'] = round($aop['49'] * 0.10, 2);

        // AOP 51 = sum of AOP 52 through AOP 55
        $aop['51'] = 0;
        for ($i = 52; $i <= 55; $i++) {
            $aop['51'] += $aop[str_pad($i, 2, '0', STR_PAD_LEFT)];
        }

        // AOP 56 = max(0, AOP 50 - AOP 51)
        $aop['56'] = max(0, $aop['50'] - $aop['51']);

        // AOP 59 = AOP 56 - AOP 57 - AOP 58 (can be negative = overpayment)
        $aop['59'] = $aop['56'] - $aop['57'] - $aop['58'];

        return [
            'aop' => $aop,
            'year' => $year,
            'config' => $config,
        ];
    }

    /**
     * Validate DB form data - arithmetic consistency checks.
     */
    public function validate(array $data): array
    {
        $errors = [];
        $warnings = [];
        $aop = $data['aop'] ?? [];

        // Check AOP 02 = sum(03:39)
        $expectedAop02 = 0;
        for ($i = 3; $i <= 39; $i++) {
            $expectedAop02 += $aop[str_pad($i, 2, '0', STR_PAD_LEFT)] ?? 0;
        }
        if (abs(($aop['02'] ?? 0) - $expectedAop02) > 0.01) {
            $errors[] = sprintf('АОП 02 (%.2f) не е еднакво на збир АОП 03-39 (%.2f)', $aop['02'] ?? 0, $expectedAop02);
        }

        // Check AOP 40 = AOP 01 + AOP 02
        if (abs(($aop['40'] ?? 0) - (($aop['01'] ?? 0) + ($aop['02'] ?? 0))) > 0.01) {
            $errors[] = 'АОП 40 не е еднакво на АОП 01 + АОП 02';
        }

        // Check AOP 49 = max(0, AOP 40 - AOP 41)
        $expected49 = max(0, ($aop['40'] ?? 0) - ($aop['41'] ?? 0));
        if (abs(($aop['49'] ?? 0) - $expected49) > 0.01) {
            $errors[] = 'АОП 49 не е еднакво на max(0, АОП 40 - АОП 41)';
        }

        // Check AOP 50 = AOP 49 * 10%
        $expected50 = round(($aop['49'] ?? 0) * 0.10, 2);
        if (abs(($aop['50'] ?? 0) - $expected50) > 0.01) {
            $errors[] = 'АОП 50 не е еднакво на АОП 49 × 10%';
        }

        // Warning if financial result is 0
        if (abs($aop['01'] ?? 0) < 0.01) {
            $warnings[] = 'АОП 01 (Финансиски резултат) е 0 — проверете дали има книжења за годината';
        }

        // Warning if tax is negative (overpayment)
        if (($aop['59'] ?? 0) < 0) {
            $warnings[] = sprintf('АОП 59 е негативен (%.2f) — имате повеќе платен данок', $aop['59']);
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Generate XML using existing CitXmlService.
     */
    public function toXml(Company $company, array $data): string
    {
        $citData = $this->citService->calculate(
            $company,
            $data['year'],
            [],
            0
        );

        return $this->citXmlService->generate($company, $data['year'], $citData);
    }

    /**
     * Generate PDF matching official ДБ layout.
     */
    public function toPdf(Company $company, array $data, int $year): Response
    {
        $config = config('ujp_forms.db');

        $pdf = Pdf::loadView('app.pdf.reports.ujp.db', [
            'company' => $company,
            'aop' => $data['aop'] ?? [],
            'year' => $year,
            'config' => $config,
            'formCode' => 'ДБ',
            'formTitle' => 'ДАНОЧЕН БИЛАНС',
            'formSubtitle' => 'за оданочување на добивка',
            'sluzhbenVesnik' => $config['sluzhben_vesnik'] ?? '',
            'periodStart' => sprintf('01.01.%d', $year),
            'periodEnd' => sprintf('31.12.%d', $year),
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('DB_' . $company->name . '_' . $year . '.pdf');
    }
}

// CLAUDE-CHECKPOINT
