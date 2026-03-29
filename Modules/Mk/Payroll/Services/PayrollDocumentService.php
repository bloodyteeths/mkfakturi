<?php

namespace Modules\Mk\Payroll\Services;

use App\Models\PayrollEmployee;
use App\Models\PayrollRun;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;

/**
 * Payroll Document Service
 *
 * Generates Macedonian payroll documents:
 * - Рекапитулар (monthly payroll recapitulation summary)
 * - Потврда за плата (salary certificate for employee)
 */
class PayrollDocumentService
{
    /**
     * Generate Рекапитулар (monthly payroll recapitulation summary)
     *
     * @param PayrollRun $run The payroll run
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateRekapitular(PayrollRun $run): \Barryvdh\DomPDF\PDF
    {
        $run->load(['lines' => fn ($q) => $q->included()->with('employee'), 'company']);

        $lines = $run->lines;
        $company = $run->company;

        // Aggregate totals
        $totals = [
            'gross_salary' => $lines->sum('gross_salary'),
            'pension_employee' => $lines->sum('pension_contribution_employee'),
            'pension_employer' => $lines->sum('pension_contribution_employer'),
            'health_employee' => $lines->sum('health_contribution_employee'),
            'health_employer' => $lines->sum('health_contribution_employer'),
            'unemployment' => $lines->sum('unemployment_contribution'),
            'additional' => $lines->sum('additional_contribution'),
            'income_tax' => $lines->sum('income_tax_amount'),
            'net_salary' => $lines->sum('net_salary'),
            'transport_allowance' => $lines->sum('transport_allowance'),
            'meal_allowance' => $lines->sum('meal_allowance'),
            'overtime_amount' => $lines->sum('overtime_amount'),
            'night_amount' => $lines->sum('night_amount'),
            'seniority_bonus' => $lines->sum('seniority_bonus'),
        ];

        $totals['total_employee_contributions'] = $totals['pension_employee'] + $totals['health_employee']
            + $totals['unemployment'] + $totals['additional'];
        // MK model: no separate employer contributions — employer cost = gross
        $totals['total_employer_contributions'] = 0;
        $totals['total_employer_cost'] = $totals['gross_salary'];

        $data = [
            'company' => $company,
            'run' => $run,
            'lines' => $lines,
            'totals' => $totals,
            'period' => sprintf('%02d/%d', $run->period_month, $run->period_year),
            'employee_count' => $lines->count(),
            'generated_at' => now()->format('d.m.Y H:i'),
        ];

        Log::info('Generated rekapitular', ['payroll_run_id' => $run->id]);

        return Pdf::loadView('app.pdf.payroll.rekapitular', $data)
            ->setPaper('a4', 'landscape');
    }

    /**
     * Generate Потврда за плата (salary certificate)
     *
     * @param PayrollEmployee $employee The employee
     * @param int|null $months Number of months to include (default 3)
     * @return \Barryvdh\DomPDF\PDF
     */
    public function generateSalaryCertificate(PayrollEmployee $employee, ?int $months = 3): \Barryvdh\DomPDF\PDF
    {
        $company = $employee->company;

        // Get recent payroll run lines for this employee
        $recentLines = \App\Models\PayrollRunLine::where('employee_id', $employee->id)
            ->where('status', 'included')
            ->whereHas('payrollRun', fn ($q) => $q->whereIn('status', ['posted', 'paid']))
            ->with('payrollRun')
            ->orderByDesc('created_at')
            ->limit($months)
            ->get();

        $averageGross = $recentLines->avg('gross_salary') ?: 0;
        $averageNet = $recentLines->avg('net_salary') ?: 0;

        $data = [
            'company' => $company,
            'employee' => $employee,
            'recent_lines' => $recentLines,
            'average_gross' => (int) round($averageGross),
            'average_net' => (int) round($averageNet),
            'months' => $months,
            'generated_at' => now()->format('d.m.Y'),
            'certificate_number' => 'ПЗП-' . $employee->id . '-' . now()->format('Ymd'),
        ];

        Log::info('Generated salary certificate', ['employee_id' => $employee->id]);

        return Pdf::loadView('app.pdf.payroll.potvrda-za-plata', $data)
            ->setPaper('a4');
    }
}

// CLAUDE-CHECKPOINT
