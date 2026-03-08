<?php

namespace App\Services\Tax;

use App\Domain\Accounting\IfrsAdapter;
use App\Models\Company;
use App\Models\TaxReturn;
use App\Services\AopReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

/**
 * Образец 36 — Биланс на состојба (Balance Sheet)
 *
 * Official annual account form with 112 AOP codes.
 * Submitted to Central Register (e-submit.crm.com.mk).
 */
class Obrazec36FormService extends TaxFormService
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
        return 'Образец 36';
    }

    public function formTitle(): string
    {
        return 'БИЛАНС НА СОСТОЈБА';
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
     * Collect official Образец 36 data (112 AOP fields).
     * Delegates to AopReportService::getBalanceSheetAop() which handles:
     * - Per-account code-to-AOP distribution
     * - Proper sign convention for contra-balances
     * - P&L injection into equity (AOP 075/076/077/078)
     */
    public function collect(
        Company $company,
        int $year,
        ?int $month = null,
        ?int $quarter = null,
        array $overrides = []
    ): array {
        $result = $this->aopService->getBalanceSheetAop($company, $year);

        // Apply manual overrides
        $aktiva = $this->applyOverrides($result['aktiva'], $overrides);
        $pasiva = $this->applyOverrides($result['pasiva'], $overrides);

        // Recalculate totals after overrides
        $totalAktiva = $this->findAopValue($aktiva, '063');
        $totalPasiva = $this->findAopValue($pasiva, '111');

        return [
            'aktiva' => $aktiva,
            'pasiva' => $pasiva,
            'total_aktiva' => $totalAktiva,
            'total_pasiva' => $totalPasiva,
            'is_balanced' => abs($totalAktiva - $totalPasiva) < 0.01,
            'year' => $year,
        ];
    }

    /**
     * Validate Образец 36 data.
     */
    public function validate(array $data): array
    {
        $errors = [];
        $warnings = [];

        // Balance check: ВКУПНА АКТИВА (063) must equal ВКУПНА ПАСИВА (111)
        $totalAktiva = $data['total_aktiva'] ?? 0;
        $totalPasiva = $data['total_pasiva'] ?? 0;

        if (abs($totalAktiva - $totalPasiva) > 0.01) {
            $errors[] = sprintf(
                'Актива (АОП 063 = %.2f) не е еднаква на Пасива (АОП 111 = %.2f). Разлика: %.2f',
                $totalAktiva,
                $totalPasiva,
                $totalAktiva - $totalPasiva
            );
        }

        // Warning if everything is zero
        if (abs($totalAktiva) < 0.01 && abs($totalPasiva) < 0.01) {
            $warnings[] = 'Сите вредности се 0 — проверете дали има книжења за годината';
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
        $this->addXmlElement($dom, $header, 'FormType', '17');
        $this->addXmlElement($dom, $header, 'CompanyName', $company->name);

        // Data
        $dataNode = $dom->createElement('Data');
        $form->appendChild($dataNode);

        // Aktiva rows
        foreach ($data['aktiva'] ?? [] as $row) {
            $rowEl = $dom->createElement('Row');
            $rowEl->setAttribute('AOP', $row['aop']);
            $rowEl->setAttribute('Current', $this->formatAmount($row['current']));
            $rowEl->setAttribute('Previous', $this->formatAmount($row['previous']));
            $dataNode->appendChild($rowEl);
        }

        // Pasiva rows
        foreach ($data['pasiva'] ?? [] as $row) {
            $rowEl = $dom->createElement('Row');
            $rowEl->setAttribute('AOP', $row['aop']);
            $rowEl->setAttribute('Current', $this->formatAmount($row['current']));
            $rowEl->setAttribute('Previous', $this->formatAmount($row['previous']));
            $dataNode->appendChild($rowEl);
        }

        return $dom->saveXML();
    }

    /**
     * Generate PDF matching official Образец 36 layout.
     */
    public function toPdf(Company $company, array $data, int $year): Response
    {
        $config = config('ujp_forms.obrazec_36');

        $pdf = Pdf::loadView('app.pdf.reports.ujp.obrazec-36', [
            'company' => $company,
            'aktiva' => $data['aktiva'] ?? [],
            'pasiva' => $data['pasiva'] ?? [],
            'year' => $year,
            'formCode' => 'Образец 36',
            'formTitle' => 'БИЛАНС НА СОСТОЈБА',
            'formSubtitle' => '',
            'sluzhbenVesnik' => $config['sluzhben_vesnik'] ?? '',
            'periodStart' => sprintf('01.01.%d', $year),
            'periodEnd' => sprintf('31.12.%d', $year),
            'totalAktiva' => $data['total_aktiva'] ?? 0,
            'totalPasiva' => $data['total_pasiva'] ?? 0,
            'isBalanced' => $data['is_balanced'] ?? false,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('Obrazec36_' . $company->name . '_' . $year . '.pdf');
    }

    /**
     * Apply manual overrides to AOP rows.
     */
    protected function applyOverrides(array $rows, array $overrides): array
    {
        if (empty($overrides)) {
            return $rows;
        }

        foreach ($rows as &$row) {
            $key = $row['aop'];
            if (isset($overrides[$key]) && empty($row['is_total'])) {
                $row['current'] = (float) $overrides[$key];
            }
        }

        return $rows;
    }

    /**
     * Find value for a specific AOP code.
     */
    protected function findAopValue(array $rows, string $aop): float
    {
        foreach ($rows as $row) {
            if ($row['aop'] === $aop) {
                return $row['current'];
            }
        }
        return 0;
    }

    /**
     * Add XML element helper.
     */
    protected function addXmlElement(\DOMDocument $dom, \DOMElement $parent, string $name, string $value): void
    {
        $el = $dom->createElement($name, htmlspecialchars($value));
        $parent->appendChild($el);
    }
}

// CLAUDE-CHECKPOINT
