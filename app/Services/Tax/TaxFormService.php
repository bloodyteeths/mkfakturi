<?php

namespace App\Services\Tax;

use App\Models\Company;
use App\Models\TaxReportPeriod;
use App\Models\TaxReturn;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Abstract base class for all UJP tax form services.
 *
 * Each form (ДДВ-04, ДБ, Образец 36/37, etc.) extends this class
 * and implements its specific data collection, validation, and output logic.
 *
 * Architecture: Controller → TaxFormService → Config → XML + PDF
 */
abstract class TaxFormService
{
    /**
     * Official form code (e.g. 'ДДВ-04', 'ДБ', 'Образец 36').
     */
    abstract public function formCode(): string;

    /**
     * Full Macedonian title of the form.
     */
    abstract public function formTitle(): string;

    /**
     * Period type: 'monthly', 'quarterly', or 'annual'.
     */
    abstract public function periodType(): string;

    /**
     * Return type constant from TaxReturn model.
     */
    abstract public function returnType(): string;

    /**
     * Collect form data from IFRS/accounting system.
     *
     * Auto-populates fields from accounting data.
     * Manual overrides can be passed in the request.
     *
     * @param  array  $overrides  Manual field overrides from user
     * @return array  Structured form data with all fields
     */
    abstract public function collect(
        Company $company,
        int $year,
        ?int $month = null,
        ?int $quarter = null,
        array $overrides = []
    ): array;

    /**
     * Validate collected form data.
     *
     * Checks arithmetic consistency, required fields, range limits.
     *
     * @return array{errors: array, warnings: array}
     */
    abstract public function validate(array $data): array;

    /**
     * Generate XML output for UJP portal upload.
     */
    abstract public function toXml(Company $company, array $data): string;

    /**
     * Generate PDF output matching official Службен Весник layout.
     */
    abstract public function toPdf(Company $company, array $data, int $year): Response;

    /**
     * Preview: collect + validate in one call.
     *
     * @return array{data: array, validation: array, form: array}
     */
    public function preview(
        Company $company,
        int $year,
        ?int $month = null,
        ?int $quarter = null,
        array $overrides = []
    ): array {
        $data = $this->collect($company, $year, $month, $quarter, $overrides);
        $validation = $this->validate($data);

        return [
            'data' => $data,
            'validation' => $validation,
            'form' => [
                'code' => $this->formCode(),
                'title' => $this->formTitle(),
                'period_type' => $this->periodType(),
                'return_type' => $this->returnType(),
            ],
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'vat_number' => $company->vat_number,
                'tax_id' => $company->tax_id,
            ],
            'period' => [
                'year' => $year,
                'month' => $month,
                'quarter' => $quarter,
            ],
        ];
    }

    /**
     * File the form: create TaxReturn record and save data.
     */
    public function file(
        Company $company,
        array $data,
        int $year,
        ?int $month = null,
        ?int $quarter = null,
        ?string $receiptNumber = null
    ): TaxReturn {
        // Find or create the tax period
        $periodType = $this->periodType();
        $period = TaxReportPeriod::firstOrCreate(
            [
                'company_id' => $company->id,
                'period_type' => $periodType,
                'year' => $year,
                'month' => $periodType === TaxReportPeriod::PERIOD_MONTHLY ? $month : null,
                'quarter' => $periodType === TaxReportPeriod::PERIOD_QUARTERLY ? $quarter : null,
            ],
            [
                'start_date' => $this->getPeriodStartDate($year, $month, $quarter),
                'end_date' => $this->getPeriodEndDate($year, $month, $quarter),
                'due_date' => $this->getDueDate($year, $month, $quarter),
                'status' => TaxReportPeriod::STATUS_OPEN,
            ]
        );

        // Generate XML for storage
        $xmlContent = $this->toXml($company, $data);

        // Create tax return record
        $taxReturn = TaxReturn::create([
            'company_id' => $company->id,
            'period_id' => $period->id,
            'return_type' => $this->returnType(),
            'status' => TaxReturn::STATUS_DRAFT,
            'return_data' => array_merge($data, [
                'form_code' => $this->formCode(),
                'xml_content' => $xmlContent,
            ]),
        ]);

        // File the return
        $taxReturn->file(
            Auth::id(),
            $receiptNumber
        );

        return $taxReturn;
    }

    /**
     * Get start date for the period.
     */
    protected function getPeriodStartDate(int $year, ?int $month, ?int $quarter): string
    {
        if ($month) {
            return sprintf('%d-%02d-01', $year, $month);
        }
        if ($quarter) {
            $startMonth = ($quarter - 1) * 3 + 1;
            return sprintf('%d-%02d-01', $year, $startMonth);
        }
        return sprintf('%d-01-01', $year);
    }

    /**
     * Get end date for the period.
     */
    protected function getPeriodEndDate(int $year, ?int $month, ?int $quarter): string
    {
        if ($month) {
            return \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');
        }
        if ($quarter) {
            $endMonth = $quarter * 3;
            return \Carbon\Carbon::create($year, $endMonth, 1)->endOfMonth()->format('Y-m-d');
        }
        return sprintf('%d-12-31', $year);
    }

    /**
     * Get filing due date for the period.
     */
    protected function getDueDate(int $year, ?int $month, ?int $quarter): string
    {
        if ($month) {
            // Monthly: 15th of next month
            return \Carbon\Carbon::create($year, $month, 1)->addMonth()->day(15)->format('Y-m-d');
        }
        if ($quarter) {
            // Quarterly: 15th of month after quarter end
            $endMonth = $quarter * 3;
            return \Carbon\Carbon::create($year, $endMonth, 1)->addMonth()->day(15)->format('Y-m-d');
        }
        // Annual: March 15 of next year (or Feb 28 for paper filing)
        return sprintf('%d-03-15', $year + 1);
    }

    /**
     * Format amount for display in PDF/XML (2 decimal places).
     */
    protected function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Get available form services registry.
     *
     * @return array<string, class-string<TaxFormService>>
     */
    public static function registry(): array
    {
        return [
            'ddv-04' => DDV04FormService::class,
            'db' => DbFormService::class,
            'obrazec-36' => Obrazec36FormService::class,
            'obrazec-37' => Obrazec37FormService::class,
        ];
    }

    /**
     * Resolve a form service by its URL-safe code.
     */
    public static function resolve(string $formCode): ?self
    {
        $registry = self::registry();

        if (!isset($registry[$formCode])) {
            return null;
        }

        return app($registry[$formCode]);
    }
}

// CLAUDE-CHECKPOINT
