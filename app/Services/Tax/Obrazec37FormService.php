<?php

namespace App\Services\Tax;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Company;
use App\Models\TaxReturn;
use App\Services\AopReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Образец 37 — Биланс на успех (Income Statement)
 *
 * Official annual account form with 44 AOP codes (201-244).
 * Submitted to Central Register (e-submit.crm.com.mk).
 */
class Obrazec37FormService extends TaxFormService
{
    protected AopReportService $aopService;
    protected IfrsAdapter $ifrsAdapter;

    public function __construct(AopReportService $aopService, IfrsAdapter $ifrsAdapter)
    {
        $this->aopService = $aopService;
        $this->ifrsAdapter = $ifrsAdapter;
    }

    public function formCode(): string
    {
        return 'Образец 37';
    }

    public function formTitle(): string
    {
        return 'БИЛАНС НА УСПЕХ';
    }

    public function periodType(): string
    {
        return 'annual';
    }

    public function returnType(): string
    {
        return TaxReturn::TYPE_ANNUAL_ACCOUNT;
    }

    /**
     * Collect official Образец 37 data (44 AOP fields).
     */
    public function collect(
        Company $company,
        int $year,
        ?int $month = null,
        ?int $quarter = null,
        array $overrides = []
    ): array {
        $config = config('ujp_forms.obrazec_37');
        $fallback = $config['ifrs_to_aop_fallback'];

        $startDate = "{$year}-01-01";
        $endDate = "{$year}-12-31";

        // Get IFRS income statement data
        try {
            $current = $this->ifrsAdapter->getIncomeStatement($company, $startDate, $endDate);
            $currentBalances = $this->aopService->extractIncomeStatementByType($current);
        } catch (\Exception $e) {
            $currentBalances = [];
        }

        // Get previous year
        $previousBalances = $this->aopService->getPreviousYearIncomeStatement($company, $year - 1);

        // Build AOP rows for revenue
        $prihodi = $this->aopService->buildAopRows($config['prihodi'], $currentBalances, $previousBalances, $fallback);

        // Build AOP rows for expenses
        $rashodi = $this->aopService->buildAopRows($config['rashodi'], $currentBalances, $previousBalances, $fallback);

        // Build result rows (financial + calculated)
        $rezultat = $this->buildResultRows($config['rezultat'], $currentBalances, $previousBalances, $fallback, $prihodi, $rashodi, $overrides);

        // Apply manual overrides to prihodi/rashodi
        $prihodi = $this->applyOverrides($prihodi, $overrides);
        $rashodi = $this->applyOverrides($rashodi, $overrides);

        return [
            'prihodi' => $prihodi,
            'rashodi' => $rashodi,
            'rezultat' => $rezultat,
            'year' => $year,
        ];
    }

    /**
     * Validate Образец 37 data.
     */
    public function validate(array $data): array
    {
        $errors = [];
        $warnings = [];

        // Find key values
        $totalRevenue = $this->findAopValue($data['prihodi'], '201');
        $totalExpenses = $this->findAopValue($data['rashodi'], '207');
        $financialIncome = $this->findResultValue($data['rezultat'], '223');
        $financialExpenses = $this->findResultValue($data['rezultat'], '224');

        $netProfit = $this->findResultValue($data['rezultat'], '233');
        $netLoss = $this->findResultValue($data['rezultat'], '234');

        // Check that exactly one of profit/loss is filled
        if ($netProfit > 0 && $netLoss > 0) {
            $errors[] = 'АОП 233 (Нето добивка) и АОП 234 (Нето загуба) не можат и двете да имаат вредност';
        }

        // Warning if everything is zero
        if (abs($totalRevenue) < 0.01 && abs($totalExpenses) < 0.01) {
            $warnings[] = 'Сите вредности се 0 — проверете дали има книжења за годината';
        }

        // Cross-check: net result = total revenue - total expenses
        $expectedResult = ($totalRevenue + $financialIncome) - ($totalExpenses + $financialExpenses);
        $tax = $this->findResultValue($data['rezultat'], '231');
        $actualNet = $netProfit > 0 ? $netProfit : -$netLoss;
        $expectedNet = $expectedResult > 0 ? $expectedResult - $tax : $expectedResult;

        if (abs($actualNet - $expectedNet) > 1.00) {
            $warnings[] = sprintf(
                'Нето резултат (%.2f) се разликува од очекуван (%.2f)',
                $actualNet,
                $expectedNet
            );
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Generate XML for CRM e-submit portal.
     */
    public function toXml(Company $company, array $data): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $form = $dom->createElement('Form');
        $dom->appendChild($form);

        // Header
        $header = $dom->createElement('Header');
        $form->appendChild($header);
        $this->addXmlElement($dom, $header, 'EMBS', $company->vat_number ?? '');
        $this->addXmlElement($dom, $header, 'Year', (string) ($data['year'] ?? date('Y')));
        $this->addXmlElement($dom, $header, 'FormType', '18');
        $this->addXmlElement($dom, $header, 'CompanyName', $company->name);

        // Data
        $dataNode = $dom->createElement('Data');
        $form->appendChild($dataNode);

        foreach (['prihodi', 'rashodi', 'rezultat'] as $section) {
            foreach ($data[$section] ?? [] as $row) {
                $rowEl = $dom->createElement('Row');
                $rowEl->setAttribute('AOP', $row['aop']);
                $rowEl->setAttribute('Current', $this->formatAmount($row['current']));
                $rowEl->setAttribute('Previous', $this->formatAmount($row['previous']));
                $dataNode->appendChild($rowEl);
            }
        }

        return $dom->saveXML();
    }

    /**
     * Generate PDF matching official Образец 37 layout.
     */
    public function toPdf(Company $company, array $data, int $year): Response
    {
        $config = config('ujp_forms.obrazec_37');

        $pdf = Pdf::loadView('app.pdf.reports.ujp.obrazec-37', [
            'company' => $company,
            'prihodi' => $data['prihodi'] ?? [],
            'rashodi' => $data['rashodi'] ?? [],
            'rezultat' => $data['rezultat'] ?? [],
            'year' => $year,
            'formCode' => 'Образец 37',
            'formTitle' => 'БИЛАНС НА УСПЕХ',
            'formSubtitle' => '',
            'sluzhbenVesnik' => $config['sluzhben_vesnik'] ?? '',
            'periodStart' => sprintf('01.01.%d', $year),
            'periodEnd' => sprintf('31.12.%d', $year),
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('Obrazec37_' . $company->name . '_' . $year . '.pdf');
    }

    /**
     * Build result rows with calculated fields.
     */
    protected function buildResultRows(
        array $config,
        array $currentBalances,
        array $previousBalances,
        array $fallback,
        array $prihodi,
        array $rashodi,
        array $overrides
    ): array {
        $totalRevenue = $this->findAopValue($prihodi, '201');
        $totalExpenses = $this->findAopValue($rashodi, '207');
        $prevRevenue = $this->findAopPrevValue($prihodi, '201');
        $prevExpenses = $this->findAopPrevValue($rashodi, '207');

        $rows = [];

        // First pass: populate leaf rows (financial income/expenses)
        $leafBalances = [];
        foreach ($config as $rowConfig) {
            if (isset($rowConfig['ifrs_types']) && !empty($rowConfig['ifrs_types'])) {
                $current = 0;
                $previous = 0;
                foreach ($rowConfig['ifrs_types'] as $ifrsType) {
                    $fallbackAop = $fallback[$ifrsType] ?? null;
                    if ($fallbackAop === $rowConfig['aop']) {
                        $current += $currentBalances[$ifrsType] ?? 0;
                        $previous += $previousBalances[$ifrsType] ?? 0;
                    }
                }
                $leafBalances[$rowConfig['aop']] = ['current' => $current, 'previous' => $previous];
            }
        }

        $financialIncome = $leafBalances['223']['current'] ?? 0;
        $financialExpenses = $leafBalances['224']['current'] ?? 0;
        $prevFinancialIncome = $leafBalances['223']['previous'] ?? 0;
        $prevFinancialExpenses = $leafBalances['224']['previous'] ?? 0;

        $operatingResult = ($totalRevenue + $financialIncome) - ($totalExpenses + $financialExpenses);
        $prevOperatingResult = ($prevRevenue + $prevFinancialIncome) - ($prevExpenses + $prevFinancialExpenses);

        $aop227 = (float) ($overrides['227'] ?? 0);
        $aop228 = (float) ($overrides['228'] ?? 0);
        $prev227 = 0;
        $prev228 = 0;

        $profitBeforeTax = $operatingResult + $aop227 - $aop228;
        $prevProfitBeforeTax = $prevOperatingResult + $prev227 - $prev228;

        $isProfit = $profitBeforeTax >= 0;
        $prevIsProfit = $prevProfitBeforeTax >= 0;

        $tax = $isProfit ? round($profitBeforeTax * 0.10, 2) : 0;
        $prevTax = $prevIsProfit ? round($prevProfitBeforeTax * 0.10, 2) : 0;

        $aop232 = (float) ($overrides['232'] ?? 0);
        $netResult = $profitBeforeTax - $tax + $aop232;
        $prevNetResult = $prevProfitBeforeTax - $prevTax;

        $oci = (float) ($overrides['239'] ?? 0);
        $ociTax = (float) ($overrides['240'] ?? 0);

        // Build all rows
        foreach ($config as $rowConfig) {
            $aop = $rowConfig['aop'];
            $current = 0;
            $previous = 0;

            if (isset($rowConfig['formula'])) {
                switch ($rowConfig['formula']) {
                    case 'operating_profit':
                        $current = $operatingResult >= 0 ? abs($operatingResult) : 0;
                        $previous = $prevOperatingResult >= 0 ? abs($prevOperatingResult) : 0;
                        break;
                    case 'operating_loss':
                        $current = $operatingResult < 0 ? abs($operatingResult) : 0;
                        $previous = $prevOperatingResult < 0 ? abs($prevOperatingResult) : 0;
                        break;
                    case 'profit_before_tax':
                        $current = $profitBeforeTax >= 0 ? abs($profitBeforeTax) : 0;
                        $previous = $prevProfitBeforeTax >= 0 ? abs($prevProfitBeforeTax) : 0;
                        break;
                    case 'loss_before_tax':
                        $current = $profitBeforeTax < 0 ? abs($profitBeforeTax) : 0;
                        $previous = $prevProfitBeforeTax < 0 ? abs($prevProfitBeforeTax) : 0;
                        break;
                    case 'tax':
                        $current = $tax;
                        $previous = $prevTax;
                        break;
                    case 'net_profit':
                        $current = $netResult >= 0 ? abs($netResult) : 0;
                        $previous = $prevNetResult >= 0 ? abs($prevNetResult) : 0;
                        break;
                    case 'net_loss':
                        $current = $netResult < 0 ? abs($netResult) : 0;
                        $previous = $prevNetResult < 0 ? abs($prevNetResult) : 0;
                        break;
                    case 'net_oci':
                        $current = $oci - $ociTax;
                        $previous = 0;
                        break;
                    case 'total_comprehensive':
                        $current = $netResult + ($oci - $ociTax);
                        $previous = $prevNetResult;
                        break;
                    case 'total_revenue':
                        $current = $totalRevenue + $financialIncome + $aop227;
                        $previous = $prevRevenue + $prevFinancialIncome + $prev227;
                        break;
                    case 'total_expenses':
                        $current = $totalExpenses + $financialExpenses + $aop228 + $tax - $aop232;
                        $previous = $prevExpenses + $prevFinancialExpenses + $prev228 + $prevTax;
                        break;
                }
            } elseif (isset($leafBalances[$aop])) {
                $current = $leafBalances[$aop]['current'];
                $previous = $leafBalances[$aop]['previous'];
            }

            // Apply manual override for non-formula fields
            if (!isset($rowConfig['formula']) && isset($overrides[$aop])) {
                $current = (float) $overrides[$aop];
            }

            $rows[] = [
                'aop' => $aop,
                'label' => $rowConfig['label'],
                'current' => round($current, 2),
                'previous' => round($previous, 2),
                'is_total' => false,
                'is_result' => isset($rowConfig['formula']),
                'indent' => $rowConfig['indent'] ?? 0,
                'consolidated_only' => $rowConfig['consolidated_only'] ?? false,
            ];
        }

        return $rows;
    }

    protected function applyOverrides(array $rows, array $overrides): array
    {
        if (empty($overrides)) {
            return $rows;
        }

        foreach ($rows as &$row) {
            if (isset($overrides[$row['aop']]) && empty($row['is_total'])) {
                $row['current'] = (float) $overrides[$row['aop']];
            }
        }

        return $rows;
    }

    protected function findAopValue(array $rows, string $aop): float
    {
        foreach ($rows as $row) {
            if ($row['aop'] === $aop) {
                return $row['current'];
            }
        }
        return 0;
    }

    protected function findAopPrevValue(array $rows, string $aop): float
    {
        foreach ($rows as $row) {
            if ($row['aop'] === $aop) {
                return $row['previous'];
            }
        }
        return 0;
    }

    protected function findResultValue(array $rows, string $aop): float
    {
        foreach ($rows as $row) {
            if ($row['aop'] === $aop) {
                return $row['current'];
            }
        }
        return 0;
    }

    protected function addXmlElement(\DOMDocument $dom, \DOMElement $parent, string $name, string $value): void
    {
        $el = $dom->createElement($name, htmlspecialchars($value));
        $parent->appendChild($el);
    }
}

// CLAUDE-CHECKPOINT
