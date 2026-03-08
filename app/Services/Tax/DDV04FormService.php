<?php

namespace App\Services\Tax;

use App\Models\Company;
use App\Models\TaxReturn;
use App\Services\VatXmlService;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * ДДВ-04 VAT Return Form Service
 *
 * Generates the official ДДВ-04 (VAT return) form in PDF and XML formats.
 * Reuses existing VatXmlService for data collection and XML generation.
 */
class DDV04FormService extends TaxFormService
{
    protected VatXmlService $vatService;

    public function __construct(VatXmlService $vatService)
    {
        $this->vatService = $vatService;
    }

    public function formCode(): string
    {
        return 'ДДВ-04';
    }

    public function formTitle(): string
    {
        return 'ДАНОЧНА ПРИЈАВА НА ДАНОКОТ НА ДОДАДЕНА ВРЕДНОСТ';
    }

    public function periodType(): string
    {
        return 'monthly';
    }

    public function returnType(): string
    {
        return TaxReturn::TYPE_VAT;
    }

    /**
     * Collect DDV-04 form data (32 fields).
     *
     * Fields 1-10: Output VAT (Даночна обврска)
     * Fields 11-25: Input VAT (Претходен данок)
     * Fields 26-32: Calculation (Пресметка)
     */
    public function collect(
        Company $company,
        int $year,
        ?int $month = null,
        ?int $quarter = null,
        array $overrides = []
    ): array {
        $periodStart = $this->buildPeriodStart($year, $month, $quarter);
        $periodEnd = $this->buildPeriodEnd($year, $month, $quarter);
        $periodType = $quarter ? 'QUARTERLY' : 'MONTHLY';

        // Initialize the VAT service for this period
        $this->vatService->initForPeriod($company, $periodStart, $periodEnd, $periodType);

        // Get output VAT data (invoices)
        $outputVat = $this->vatService->calculateVatForPeriod();

        // Get input VAT data (bills)
        $inputVat = $this->vatService->calculateInputVatForPeriod();

        // Build the 32 DDV-04 fields
        $fields = $this->buildFields($outputVat, $inputVat, $overrides);

        return [
            'fields' => $fields,
            'overrides' => $overrides,
            'output_vat' => $outputVat,
            'input_vat' => $inputVat,
            'period_start' => $periodStart->format('d.m.Y'),
            'period_end' => $periodEnd->format('d.m.Y'),
            'period_type' => $periodType,
            'year' => $year,
            'month' => $month,
            'quarter' => $quarter,
        ];
    }

    /**
     * Validate DDV-04 form data.
     */
    public function validate(array $data): array
    {
        $errors = [];
        $warnings = [];
        $f = $data['fields'] ?? [];

        // Field 10 = total output VAT = sum of VAT amounts (fields 2, 4, 7, 8, 9)
        // Fields 1, 3, 5, 6 are taxable bases, not VAT amounts
        $sumOutputVat = ($f[2] ?? 0) + ($f[4] ?? 0) + ($f[7] ?? 0) + ($f[8] ?? 0) + ($f[9] ?? 0);
        if (abs(($f[10] ?? 0) - $sumOutputVat) > 0.01) {
            $errors[] = sprintf(
                'Поле 10 (%.2f) не е еднакво на збир од ДДВ полиња 2+4+7+8+9 (%.2f)',
                $f[10] ?? 0,
                $sumOutputVat
            );
        }

        // Field 20 should equal field 10 - field 19
        $expectedField20 = ($f[10] ?? 0) - ($f[19] ?? 0);
        if (abs(($f[20] ?? 0) - $expectedField20) > 0.01) {
            $warnings[] = sprintf(
                'Поле 20 (%.2f) треба да е: Поле 10 - Поле 19 = %.2f',
                $f[20] ?? 0,
                $expectedField20
            );
        }

        // Field 29 should equal field 20 - field 28
        $expectedField29 = ($f[20] ?? 0) - ($f[28] ?? 0);
        if (abs(($f[29] ?? 0) - $expectedField29) > 0.01) {
            $warnings[] = sprintf(
                'Поле 29 (%.2f) треба да е: Поле 20 - Поле 28 = %.2f',
                $f[29] ?? 0,
                $expectedField29
            );
        }

        // Field 31 should equal field 29 - field 30
        $expectedField31 = ($f[29] ?? 0) - ($f[30] ?? 0);
        if (abs(($f[31] ?? 0) - $expectedField31) > 0.01) {
            $warnings[] = sprintf(
                'Поле 31 (%.2f) треба да е: Поле 29 - Поле 30 = %.2f',
                $f[31] ?? 0,
                $expectedField31
            );
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Generate XML by delegating to existing VatXmlService.
     */
    public function toXml(Company $company, array $data): string
    {
        $periodStart = $this->buildPeriodStart(
            $data['year'],
            $data['month'] ?? null,
            $data['quarter'] ?? null
        );
        $periodEnd = $this->buildPeriodEnd(
            $data['year'],
            $data['month'] ?? null,
            $data['quarter'] ?? null
        );
        $periodType = ($data['quarter'] ?? null) ? 'QUARTERLY' : 'MONTHLY';

        return $this->vatService->generateVatReturn($company, $periodStart, $periodEnd, $periodType);
    }

    /**
     * Generate PDF matching official ДДВ-04 layout.
     */
    public function toPdf(Company $company, array $data, int $year): Response
    {
        $currency = $company->currency ?? null;

        $pdf = Pdf::loadView('app.pdf.reports.ujp.ddv-04', [
            'company' => $company,
            'data' => $data,
            'fields' => $data['fields'] ?? [],
            'overrides' => $data['overrides'] ?? [],
            'year' => $year,
            'periodStart' => $data['period_start'] ?? '',
            'periodEnd' => $data['period_end'] ?? '',
            'currency' => $currency,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('DDV-04_' . $company->name . '_' . $year . '.pdf');
    }

    /**
     * Build the 32 DDV-04 fields from output and input VAT data.
     */
    protected function buildFields(array $outputVat, array $inputVat, array $overrides): array
    {
        // Output VAT (fields 1-10)
        $f = [];

        // Field 1: Standard rate (18%) - taxable base
        $f[1] = $outputVat['standard']['taxable_base'] ?? 0;
        // Field 2: Standard rate (18%) - VAT amount
        $f[2] = $outputVat['standard']['vat_amount'] ?? 0;
        // Field 3: Reduced rate (5%) - taxable base
        $f[3] = $outputVat['reduced']['taxable_base'] ?? 0;
        // Field 4: Reduced rate (5%) - VAT amount
        $f[4] = $outputVat['reduced']['vat_amount'] ?? 0;
        // Field 5: Zero rate - taxable base
        $f[5] = $outputVat['zero']['taxable_base'] ?? 0;
        // Field 6: Exempt - taxable base
        $f[6] = $outputVat['exempt']['taxable_base'] ?? 0;
        // Fields 7-9: Reverse charge (default 0, can be overridden)
        $f[7] = $overrides[7] ?? 0;
        $f[8] = $overrides[8] ?? 0;
        $f[9] = $overrides[9] ?? 0;
        // Field 10: Total output VAT
        $f[10] = $f[2] + $f[4] + $f[7] + $f[8] + $f[9];

        // Input VAT (fields 11-19)
        // Field 11: Standard rate input - taxable base
        $f[11] = $inputVat['standard']['taxable_base'] ?? 0;
        // Field 12: Standard rate input - VAT amount
        $f[12] = $inputVat['standard']['vat_amount'] ?? 0;
        // Field 13: Reduced rate input - taxable base
        $f[13] = $inputVat['reduced']['taxable_base'] ?? 0;
        // Field 14: Reduced rate input - VAT amount
        $f[14] = $inputVat['reduced']['vat_amount'] ?? 0;
        // Fields 15-18: Import/reverse charge input (default 0)
        $f[15] = $overrides[15] ?? 0;
        $f[16] = $overrides[16] ?? 0;
        $f[17] = $overrides[17] ?? 0;
        $f[18] = $overrides[18] ?? 0;
        // Field 19: Total input VAT
        $f[19] = $f[12] + $f[14] + $f[15] + $f[16] + $f[17] + $f[18];

        // Calculation (fields 20-32)
        // Field 20: Net VAT (output - input)
        $f[20] = $f[10] - $f[19];

        // Fields 21-27: Adjustments (default 0, can be overridden)
        for ($i = 21; $i <= 27; $i++) {
            $f[$i] = $overrides[$i] ?? 0;
        }

        // Field 28: Total adjustments
        $f[28] = $f[21] + $f[22] + $f[23] + $f[24] + $f[25] + $f[26] + $f[27];

        // Field 29: VAT after adjustments
        $f[29] = $f[20] - $f[28];

        // Field 30: Carryover from previous period (manual)
        $f[30] = $overrides[30] ?? 0;

        // Field 31: VAT to pay / refund
        $f[31] = $f[29] - $f[30];

        // Field 32: Refund request (manual checkbox value)
        $f[32] = $overrides[32] ?? 0;

        // Apply any remaining overrides
        foreach ($overrides as $key => $value) {
            if (is_int($key) && $key >= 1 && $key <= 32) {
                // Already handled above, but explicit overrides win
                if (isset($overrides[$key]) && !in_array($key, [10, 19, 20, 28, 29, 31])) {
                    $f[$key] = $value;
                }
            }
        }

        return $f;
    }

    /**
     * Build period start date.
     */
    protected function buildPeriodStart(int $year, ?int $month, ?int $quarter): Carbon
    {
        if ($month) {
            return Carbon::create($year, $month, 1);
        }
        if ($quarter) {
            return Carbon::create($year, ($quarter - 1) * 3 + 1, 1);
        }

        return Carbon::create($year, 1, 1);
    }

    /**
     * Build period end date.
     */
    protected function buildPeriodEnd(int $year, ?int $month, ?int $quarter): Carbon
    {
        if ($month) {
            return Carbon::create($year, $month, 1)->endOfMonth();
        }
        if ($quarter) {
            return Carbon::create($year, $quarter * 3, 1)->endOfMonth();
        }

        return Carbon::create($year, 12, 31);
    }
}

// CLAUDE-CHECKPOINT
