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

        // Auto-populate RC overrides from calculated data
        $overrides = $this->buildRcOverrides($outputVat, $inputVat, $overrides);

        // Pass company context for field 30 carryover auto-calculation
        $overrides['_company_id'] = $company->id;
        $overrides['_period_end'] = $periodStart->toDateTimeString();

        // Build the DDV-04 fields
        $fields = $this->buildFields($outputVat, $inputVat, $overrides);

        // Clean internal keys from overrides before returning
        unset($overrides['_company_id'], $overrides['_period_end']);

        // Calculate Art. 35 proportional deduction if applicable
        $proportional = $this->calculateProportionalDeduction($outputVat);

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
            'proportional_deduction' => $proportional,
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
        $o = $data['overrides'] ?? [];

        // Field 20 ($f[10]) = total output VAT = 02+04+06+13+15+17+19
        $expectedTotal = ($f[2] ?? 0) + ($f[4] ?? 0) + ($f[6] ?? 0)
            + ($o[13] ?? 0) + ($o[15] ?? 0)
            + ($o[17] ?? 0) + ($o[19] ?? 0);
        if (abs(($f[10] ?? 0) - $expectedTotal) > 0.01) {
            $errors[] = sprintf(
                'Поле 20 (%.2f) ≠ збир 02+04+06+13+15+17+19 (%.2f)',
                $f[10] ?? 0,
                $expectedTotal
            );
        }

        // Field 29 ($f[19]) = total input VAT = 22+24+26+28
        $expectedInput = ($f[12] ?? 0) + ($f[14] ?? 0) + ($f[16] ?? 0) + ($f[18] ?? 0);
        if (abs(($f[19] ?? 0) - $expectedInput) > 0.01) {
            $errors[] = sprintf(
                'Поле 29 (%.2f) ≠ збир 22+24+26+28 (%.2f)',
                $f[19] ?? 0,
                $expectedInput
            );
        }

        // Field 31 = field 20 - field 29 - field 30
        $expectedField31 = ($f[10] ?? 0) - ($f[19] ?? 0) - ($f[30] ?? 0);
        if (abs(($f[31] ?? 0) - $expectedField31) > 0.01) {
            $warnings[] = sprintf(
                'Поле 31 (%.2f) треба да е: 20 − 29 − 30 = %.2f',
                $f[31] ?? 0,
                $expectedField31
            );
        }

        // Warn if VAT amount exceeds taxable base for any rate
        if (($f[2] ?? 0) > ($f[1] ?? 0) * 0.19 && ($f[1] ?? 0) > 0) {
            $warnings[] = sprintf('18%% ДДВ (%.2f) изгледа превисоко за основа (%.2f)', $f[2] ?? 0, $f[1] ?? 0);
        }
        if (($f[4] ?? 0) > ($f[3] ?? 0) * 0.11 && ($f[3] ?? 0) > 0) {
            $warnings[] = sprintf('10%% ДДВ (%.2f) изгледа превисоко за основа (%.2f)', $f[4] ?? 0, $f[3] ?? 0);
        }
        if (($f[6] ?? 0) > ($f[5] ?? 0) * 0.06 && ($f[5] ?? 0) > 0) {
            $warnings[] = sprintf('5%% ДДВ (%.2f) изгледа превисоко за основа (%.2f)', $f[6] ?? 0, $f[5] ?? 0);
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
     * Build DDV-04 fields matching official UJP form layout.
     *
     * Form fields 01-09: Output VAT by rate/category
     * Form fields 10-19: Reverse charge output (auto from data, in $overrides for blade)
     * Form field 20: Total output VAT ($f[10])
     * Form fields 21-28: Input VAT ($f[11-18])
     * Form field 29: Total input VAT ($f[19])
     * Form fields 30-32: Final calculation ($f[30-32])
     */
    protected function buildFields(array $outputVat, array $inputVat, array $overrides): array
    {
        $f = [];

        // ── Output VAT (Form 01-09, internal $f[1-9]) ──
        // Form 01/02: Standard rate 18% — base / VAT
        $f[1] = $overrides[1] ?? ($outputVat['standard']['taxable_base'] ?? 0);
        $f[2] = $overrides[2] ?? ($outputVat['standard']['vat_amount'] ?? 0);
        // Form 03/04: Hospitality rate 10% — base / VAT
        $f[3] = $overrides[3] ?? ($outputVat['hospitality']['taxable_base'] ?? 0);
        $f[4] = $overrides[4] ?? ($outputVat['hospitality']['vat_amount'] ?? 0);
        // Form 05/06: Reduced rate 5% — base / VAT
        $f[5] = $overrides[5] ?? ($outputVat['reduced']['taxable_base'] ?? 0);
        $f[6] = $overrides[6] ?? ($outputVat['reduced']['vat_amount'] ?? 0);
        // Form 07: Exports / zero-rated — base only
        $f[7] = $overrides[7] ?? ($outputVat['zero']['taxable_base'] ?? 0);
        // Form 08: Exempt with deduction right — base only
        $f[8] = $overrides[8] ?? ($outputVat['exempt']['taxable_base'] ?? 0);
        // Form 09: Exempt without deduction right — base only (manual)
        $f[9] = $overrides[9] ?? 0;

        // ── Form field 20: Total output VAT (internal $f[10]) ──
        // Official formula: 02+04+06+13+15+17+19
        // Fields 13,15,17,19 are RC VAT from $overrides
        $f[10] = $f[2] + $f[4] + $f[6]
            + ($overrides[13] ?? 0) + ($overrides[15] ?? 0)
            + ($overrides[17] ?? 0) + ($overrides[19] ?? 0);

        // ── Input VAT (Form 21-28, internal $f[11-18]) ──
        // Form 21/22: Domestic input purchases (all rates combined)
        $domesticInputBase = ($inputVat['standard']['taxable_base'] ?? 0)
            + ($inputVat['hospitality']['taxable_base'] ?? 0)
            + ($inputVat['reduced']['taxable_base'] ?? 0);
        $domesticInputVat = ($inputVat['standard']['vat_amount'] ?? 0)
            + ($inputVat['hospitality']['vat_amount'] ?? 0)
            + ($inputVat['reduced']['vat_amount'] ?? 0);
        $f[11] = $overrides[21] ?? $domesticInputBase;
        $f[12] = $overrides[22] ?? $domesticInputVat;

        // Form 23/24: Input from non-resident (Art. 32.4-5) — manual
        $f[13] = $overrides[23] ?? 0;
        $f[14] = $overrides[24] ?? 0;

        // Form 25/26: Domestic RC input (Art. 32-а) — auto from RC bills
        $rcInputBase = $inputVat['reverse_charge']['taxable_base'] ?? 0;
        $rcInputVat = $inputVat['reverse_charge']['vat_amount'] ?? 0;
        $f[15] = $overrides[25] ?? $rcInputBase;
        $f[16] = $overrides[26] ?? $rcInputVat;

        // Form 27/28: Import — manual
        $f[17] = $overrides[27] ?? 0;
        $f[18] = $overrides[28] ?? 0;

        // ── Form field 29: Total input VAT (internal $f[19]) ──
        // Official formula: 22+24+26+28
        $f[19] = $f[12] + $f[14] + $f[16] + $f[18];

        // ── Final calculation (Form 30-32) ──
        // Form 30: Other deductions / carryover from previous period
        $f[30] = $overrides[30] ?? $this->getCarryoverFromPreviousPeriod($overrides);

        // Form 31: Tax debt (+) or claim (−) = Field 20 − Field 29 − Field 30
        $f[31] = $f[10] - $f[19] - $f[30];

        // Form 32: Refund request (manual checkbox)
        $f[32] = $overrides[32] ?? 0;

        return $f;
    }

    /**
     * Auto-populate reverse charge overrides from VAT data.
     *
     * RC Art. 32-а received supplies appear in BOTH output (form 16-17)
     * and input (form 25-26) sections of the DDV-04 form.
     */
    protected function buildRcOverrides(array $outputVat, array $inputVat, array $overrides): array
    {
        $rcOutputBase = $outputVat['reverse_charge']['taxable_base'] ?? 0;
        $rcInputBase = $inputVat['reverse_charge']['taxable_base'] ?? 0;
        $rcInputVat = $inputVat['reverse_charge']['vat_amount'] ?? 0;

        // Form 11: Domestic RC output supply (Art. 32-а) — base only
        if (!isset($overrides[11]) && $rcOutputBase > 0) {
            $overrides[11] = $rcOutputBase;
        }

        // Form 16/17: Domestic RC received at standard rate (Art. 32-а)
        // Self-assessment — same amounts appear as output VAT
        if (!isset($overrides[16]) && $rcInputBase > 0) {
            $overrides[16] = $rcInputBase;
        }
        if (!isset($overrides[17]) && $rcInputVat > 0) {
            $overrides[17] = $rcInputVat;
        }

        return $overrides;
    }

    /**
     * Get carryover credit from the previous TaxReturn for this company.
     */
    protected function getCarryoverFromPreviousPeriod(array $overrides): float
    {
        // If an explicit override was provided, use it
        if (isset($overrides['_company_id']) && isset($overrides['_period_end'])) {
            $previous = TaxReturn::where('company_id', $overrides['_company_id'])
                ->where('return_type', TaxReturn::TYPE_VAT)
                ->whereIn('status', [TaxReturn::STATUS_FILED, TaxReturn::STATUS_ACCEPTED])
                ->where('created_at', '<', $overrides['_period_end'])
                ->orderBy('created_at', 'desc')
                ->first();

            if ($previous && $previous->return_data) {
                $prevFields = $previous->return_data['fields'] ?? [];
                $prevField31 = $prevFields[31] ?? 0;

                // Negative field 31 = credit (carryover to next period)
                return $prevField31 < 0 ? abs($prevField31) : 0;
            }
        }

        return 0;
    }

    /**
     * Suggest filing period type (monthly vs quarterly) based on ЗДДВ Art. 40.
     *
     * Companies with prior-year taxable turnover > MKD 25,000,000 must file monthly.
     * All others may file quarterly.
     *
     * @return array{period_type: string, reason: string, prior_year_total: float}
     */
    public function suggestPeriodType(Company $company, int $year): array
    {
        $threshold = 25_000_000; // MKD 25M
        $priorYear = $year - 1;

        $priorStart = Carbon::create($priorYear, 1, 1);
        $priorEnd = Carbon::create($priorYear, 12, 31);

        // Calculate prior-year total taxable turnover from invoices
        $priorTotal = $company->invoices()
            ->whereBetween('invoice_date', [$priorStart, $priorEnd])
            ->whereNotIn('status', ['DRAFT', 'REJECTED'])
            ->sum('total');

        // Invoice totals are stored in cents
        $priorTotalMkd = $priorTotal / 100;

        if ($priorTotalMkd > $threshold) {
            return [
                'period_type' => 'monthly',
                'reason' => sprintf(
                    'Промет во %d: %s МКД > 25.000.000 МКД (Чл. 40 ЗДДВ)',
                    $priorYear,
                    number_format($priorTotalMkd, 0, ',', '.')
                ),
                'prior_year_total' => $priorTotalMkd,
            ];
        }

        return [
            'period_type' => 'quarterly',
            'reason' => sprintf(
                'Промет во %d: %s МКД ≤ 25.000.000 МКД — тримесечно пријавување',
                $priorYear,
                number_format($priorTotalMkd, 0, ',', '.')
            ),
            'prior_year_total' => $priorTotalMkd,
        ];
    }

    /**
     * Calculate proportional deduction ratio (Art. 35 ЗДДВ).
     *
     * Mixed-activity companies (taxable + exempt supplies) can only deduct
     * input VAT proportional to their taxable turnover ratio.
     *
     * Ratio = taxable turnover / (taxable + exempt turnover)
     *
     * @return array{ratio: float, taxable: float, exempt: float, total: float, applicable: bool}
     */
    public function calculateProportionalDeduction(array $outputVat): array
    {
        // Taxable = standard + hospitality + reduced + zero (exports)
        $taxable = ($outputVat['standard']['taxable_base'] ?? 0)
            + ($outputVat['hospitality']['taxable_base'] ?? 0)
            + ($outputVat['reduced']['taxable_base'] ?? 0)
            + ($outputVat['zero']['taxable_base'] ?? 0);

        // Exempt turnover
        $exempt = $outputVat['exempt']['taxable_base'] ?? 0;

        $total = $taxable + $exempt;

        if ($total <= 0 || $exempt <= 0) {
            // No exempt supplies — full deduction, Art. 35 not applicable
            return [
                'ratio' => 1.0,
                'taxable' => $taxable,
                'exempt' => $exempt,
                'total' => $total,
                'applicable' => false,
            ];
        }

        // Round up to nearest whole percent per ЗДДВ Art. 35/3
        $ratio = ceil(($taxable / $total) * 100) / 100;

        return [
            'ratio' => $ratio,
            'taxable' => $taxable,
            'exempt' => $exempt,
            'total' => $total,
            'applicable' => true,
        ];
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
