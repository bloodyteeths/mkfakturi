<?php

namespace Modules\Mk\Payroll\Services;

use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Payroll Calculation Service
 *
 * Orchestrates payroll calculations for payroll runs.
 * Uses MacedonianPayrollTaxService for tax calculations.
 *
 * Note: This service expects the following models to exist:
 * - Modules\Mk\Payroll\Models\PayrollRun
 * - Modules\Mk\Payroll\Models\PayrollRunLine
 * - Modules\Mk\Payroll\Models\Employee
 *
 * These models will be created in the PAY-MODEL-* tickets.
 */
class PayrollCalculationService
{
    /**
     * @param MacedonianPayrollTaxService $taxService
     * @param LeaveCalculationService $leaveService
     * @param OvertimeCalculationService $overtimeService
     */
    public function __construct(
        private MacedonianPayrollTaxService $taxService,
        private LeaveCalculationService $leaveService,
        private OvertimeCalculationService $overtimeService
    ) {
    }

    /**
     * Calculate a complete payroll run
     *
     * Gets all active employees for the company, creates PayrollRunLine for each,
     * calculates taxes, and updates totals on the PayrollRun.
     *
     * @param mixed $run PayrollRun model instance
     * @return mixed Updated PayrollRun model instance
     */
    public function calculatePayrollRun($run)
    {
        // Validate company has required tax identifiers for UJP/MPIN filing
        $company = $run->company;

        $missing = [];
        if (empty($company->vat_number) && empty($company->tax_id)) {
            $missing[] = 'ЕДБ (Единствен Даночен Број)';
        }
        if (empty($company->registration_number)) {
            $missing[] = 'ЕМБС (Единствен Матичен Број на Субјектот)';
        }

        if (!empty($missing)) {
            throw new \InvalidArgumentException(
                'Компанијата нема поставено: ' . implode(', ', $missing) .
                '. Овие податоци се задолжителни за пресметка на плата и поднесување на МПИН до УЈП.'
            );
        }

        try {
            DB::beginTransaction();

            // Get all active employees for the company
            $employees = $this->getActiveEmployees($run->company);

            // Delete existing lines (recalculation)
            $run->lines()->delete();

            $totalGross = 0;
            $totalNet = 0;
            $totalEmployerCost = 0;
            $totalTax = 0;
            $totalContributions = 0;

            // Create a line for each employee
            foreach ($employees as $employee) {
                // Get employee's current salary (in cents)
                $grossSalary = $employee->gross_salary ?? 0;

                // Calculate leave deductions for the period
                $leaveResult = $this->leaveService->calculateLeaveDeduction(
                    $employee,
                    $run->period_start,
                    $run->period_end
                );
                $adjustedGross = $grossSalary - $leaveResult['leave_deduction_amount'];

                // Calculate overtime premium (P7-04)
                $overtimeHours = $employee->overtime_hours ?? 0;
                $overtimeMultiplier = $employee->overtime_multiplier
                    ?? $this->overtimeService->getMultiplier('regular');
                $overtimeResult = $this->overtimeService->calculate(
                    $adjustedGross,
                    $overtimeHours,
                    $overtimeMultiplier,
                    $run->working_days ?? 22
                );

                // Add overtime premium to gross before tax calculation
                $grossWithOvertime = $adjustedGross + $overtimeResult['overtime_amount'];

                // Calculate taxes on the adjusted gross (after leave deductions + overtime)
                $calculation = $this->taxService->calculateFromGross($grossWithOvertime);

                // Create payroll run line (includes leave + overtime data)
                $line = $run->lines()->create([
                    'employee_id' => $employee->id,
                    'gross_salary' => $calculation->grossSalary,
                    'net_salary' => $calculation->netSalary,
                    'pension_contribution_employee' => $calculation->pensionEmployee,
                    'pension_contribution_employer' => $calculation->pensionEmployer,
                    'health_contribution_employee' => $calculation->healthEmployee,
                    'health_contribution_employer' => $calculation->healthEmployer,
                    'unemployment_contribution' => $calculation->unemployment,
                    'additional_contribution' => $calculation->additionalContribution,
                    'income_tax_amount' => $calculation->incomeTax,
                    'personal_deduction' => $calculation->personalDeduction,
                    'leave_days_taken' => $leaveResult['leave_days_taken'],
                    'leave_deduction_amount' => $leaveResult['leave_deduction_amount'],
                    'overtime_hours' => $overtimeResult['overtime_hours'],
                    'overtime_multiplier' => $overtimeResult['multiplier'],
                    'overtime_amount' => $overtimeResult['overtime_amount'],
                ]);

                // Accumulate totals
                $totalGross += $calculation->grossSalary;
                $totalNet += $calculation->netSalary;
                $totalEmployerCost += $calculation->totalEmployerCost;
                $totalTax += $calculation->incomeTax;
                $totalContributions += $calculation->getTotalEmployeeContributions()
                    + $calculation->getTotalEmployerContributions();
            }

            // Update PayrollRun totals
            $run->update([
                'total_gross' => $totalGross,
                'total_net' => $totalNet,
                'total_employer_cost' => $totalEmployerCost,
                'total_tax' => $totalTax,
                'total_contributions' => $totalContributions,
                'employee_count' => $employees->count(),
            ]);

            DB::commit();

            Log::info('Payroll run calculated', [
                'payroll_run_id' => $run->id,
                'employee_count' => $employees->count(),
                'total_gross' => $totalGross,
                'total_net' => $totalNet,
            ]);

            return $run->fresh(['lines.employee']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to calculate payroll run', [
                'payroll_run_id' => $run->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Recalculate a single payroll run line
     *
     * @param mixed $line PayrollRunLine model instance
     * @return mixed Updated PayrollRunLine model instance
     */
    public function recalculateLine($line)
    {
        try {
            // Get employee's current salary
            $grossSalary = $line->employee->gross_salary ?? 0;

            // Add any allowances
            $grossSalary += $line->allowances ?? 0;

            // Calculate taxes
            $calculation = $this->taxService->calculateFromGross($grossSalary);

            // Update line
            $line->update([
                'gross_salary' => $calculation->grossSalary,
                'net_salary' => $calculation->netSalary - ($line->deductions_total ?? 0),
                'pension_contribution_employee' => $calculation->pensionEmployee,
                'pension_contribution_employer' => $calculation->pensionEmployer,
                'health_contribution_employee' => $calculation->healthEmployee,
                'health_contribution_employer' => $calculation->healthEmployer,
                'unemployment_contribution' => $calculation->unemployment,
                'additional_contribution' => $calculation->additionalContribution,
                'income_tax_amount' => $calculation->incomeTax,
                'personal_deduction' => $calculation->personalDeduction,
            ]);

            Log::info('Payroll line recalculated', [
                'payroll_run_line_id' => $line->id,
                'employee_id' => $line->employee_id,
                'gross_salary' => $calculation->grossSalary,
            ]);

            return $line->fresh('employee');
        } catch (\Exception $e) {
            Log::error('Failed to recalculate payroll line', [
                'payroll_run_line_id' => $line->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Add allowances to a payroll line
     *
     * Allowances are added to gross salary before tax calculation.
     * Examples: overtime pay, bonuses, meal allowances
     *
     * @param mixed $line PayrollRunLine model instance
     * @param array $allowances Array of allowances with 'type' and 'amount' (in cents)
     * @return mixed Updated PayrollRunLine model instance
     */
    public function addAllowances($line, array $allowances)
    {
        $totalAllowances = 0;

        foreach ($allowances as $allowance) {
            $totalAllowances += $allowance['amount'] ?? 0;
        }

        $line->update(['allowances' => $totalAllowances]);

        // Recalculate with new allowances
        return $this->recalculateLine($line);
    }

    /**
     * Apply deductions to a payroll line
     *
     * Deductions are subtracted from net salary after tax calculation.
     * Examples: advances, loans, garnishments
     *
     * @param mixed $line PayrollRunLine model instance
     * @param array $deductions Array of deductions with 'type' and 'amount' (in cents)
     * @return mixed Updated PayrollRunLine model instance
     */
    public function applyDeductions($line, array $deductions)
    {
        $totalDeductions = 0;

        foreach ($deductions as $deduction) {
            $totalDeductions += $deduction['amount'] ?? 0;
        }

        $line->update(['deductions' => $totalDeductions]);

        // Recalculate with new deductions
        return $this->recalculateLine($line);
    }

    /**
     * Get all active employees for a company
     *
     * @param Company $company
     * @return Collection
     */
    public function getActiveEmployees(Company $company): Collection
    {
        // This assumes Employee model exists with these fields:
        // - company_id
        // - is_active (boolean)
        // - gross_salary (integer, in cents)
        // - embg (string)
        // - first_name, last_name
        // - iban (for payments)
        //
        // Note: The actual Employee model will be created in PAY-MODEL-02 ticket

        // For now, return an empty collection if the model doesn't exist
        if (! class_exists('Modules\Mk\Payroll\Models\Employee')) {
            Log::warning('Employee model does not exist yet - returning empty collection');

            return collect([]);
        }

        $employeeClass = 'Modules\Mk\Payroll\Models\Employee';

        return $employeeClass::where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
    }
}

// CLAUDE-CHECKPOINT (payroll tax-identifier validation added)
