<?php

namespace Modules\Mk\Services;

use App\Models\CompanySetting;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Mk\Models\InterestCalculation;

class InterestCalculationService
{
    /**
     * Default Macedonian statutory interest rate:
     * NBRM reference rate (5.25%) + 8% penalty = 13.25%
     */
    private const DEFAULT_ANNUAL_RATE = 13.25;

    /**
     * Get the annual interest rate for a company.
     * Checks company settings first, falls back to MK statutory rate.
     */
    public function getAnnualRate(?int $companyId = null): float
    {
        if ($companyId) {
            $customRate = CompanySetting::getSetting('interest_annual_rate', $companyId);
            if ($customRate !== null && $customRate !== '') {
                return (float) $customRate;
            }
        }

        return self::DEFAULT_ANNUAL_RATE;
    }

    /**
     * Calculate interest for a single overdue invoice.
     *
     * Formula: principal * (annual_rate / 100 / 365) * days_overdue
     * All monetary amounts are in cents.
     *
     * @param Invoice $invoice The overdue invoice
     * @param string|null $asOfDate Calculate as of this date (Y-m-d), defaults to today
     * @return array|null Calculation data array, or null if not overdue
     */
    public function calculateForInvoice(Invoice $invoice, ?string $asOfDate = null): ?array
    {
        $asOf = $asOfDate ? Carbon::parse($asOfDate) : Carbon::today();

        $dueDate = $invoice->due_date instanceof \DateTimeInterface
            ? Carbon::parse($invoice->due_date)
            : Carbon::parse((string) $invoice->due_date);

        // Not overdue yet
        if ($asOf->lte($dueDate)) {
            return null;
        }

        $daysOverdue = $dueDate->diffInDays($asOf);
        $principal = (int) $invoice->due_amount;

        // No amount owed
        if ($principal <= 0) {
            return null;
        }

        $annualRate = $this->getAnnualRate($invoice->company_id);

        // Interest = principal * (rate/100) / 365 * days
        // Calculate in float to avoid integer overflow, then round to cents
        $interestAmount = (int) round(($principal * ($annualRate / 100) / 365) * $daysOverdue);

        return [
            'company_id' => $invoice->company_id,
            'customer_id' => $invoice->customer_id,
            'invoice_id' => $invoice->id,
            'calculation_date' => $asOf->format('Y-m-d'),
            'principal_amount' => $principal,
            'days_overdue' => $daysOverdue,
            'annual_rate' => $annualRate,
            'interest_amount' => $interestAmount,
            'invoice_number' => $invoice->invoice_number,
            'customer_name' => $invoice->customer?->name,
        ];
    }

    /**
     * Batch calculate interest for ALL overdue invoices of a company.
     *
     * @param int $companyId
     * @param string|null $asOfDate Calculate as of this date
     * @return array Array of calculation data
     */
    public function batchCalculate(int $companyId, ?string $asOfDate = null): array
    {
        $asOf = $asOfDate ?: Carbon::today()->format('Y-m-d');

        $overdueInvoices = Invoice::where('company_id', $companyId)
            ->where('due_amount', '>', 0)
            ->where('due_date', '<', $asOf)
            ->whereNotIn('status', [Invoice::STATUS_DRAFT])
            ->whereIn('paid_status', [Invoice::STATUS_UNPAID, Invoice::STATUS_PARTIALLY_PAID])
            ->with('customer:id,name')
            ->get();

        $calculations = [];

        foreach ($overdueInvoices as $invoice) {
            $calc = $this->calculateForInvoice($invoice, $asOf);
            if ($calc) {
                $calculations[] = $calc;
            }
        }

        return $calculations;
    }

    /**
     * Persist calculation results to the database.
     * Skips if already calculated for the same invoice+date combination.
     *
     * @param int $companyId
     * @param array $calculations Array of calculation data from batchCalculate
     * @return array Array of created InterestCalculation models
     */
    public function saveCalculations(int $companyId, array $calculations): array
    {
        $saved = [];

        foreach ($calculations as $calc) {
            // Skip if already calculated for same invoice+date
            $existing = InterestCalculation::where('company_id', $companyId)
                ->where('invoice_id', $calc['invoice_id'])
                ->where('calculation_date', $calc['calculation_date'])
                ->first();

            if ($existing) {
                // Update existing calculation with new amounts
                $existing->update([
                    'principal_amount' => $calc['principal_amount'],
                    'days_overdue' => $calc['days_overdue'],
                    'annual_rate' => $calc['annual_rate'],
                    'interest_amount' => $calc['interest_amount'],
                ]);
                $saved[] = $existing;

                continue;
            }

            $record = InterestCalculation::create([
                'company_id' => $calc['company_id'],
                'customer_id' => $calc['customer_id'],
                'invoice_id' => $calc['invoice_id'],
                'calculation_date' => $calc['calculation_date'],
                'principal_amount' => $calc['principal_amount'],
                'days_overdue' => $calc['days_overdue'],
                'annual_rate' => $calc['annual_rate'],
                'interest_amount' => $calc['interest_amount'],
                'status' => 'calculated',
            ]);

            $saved[] = $record;
        }

        return $saved;
    }

    /**
     * Generate an interest note (special invoice) for a customer's interest charges.
     * Marks the source calculations as 'invoiced'.
     *
     * @param int $companyId
     * @param int $customerId
     * @param array $calculationIds Array of InterestCalculation IDs to include
     * @return array Summary of the generated note
     */
    public function generateInterestNote(int $companyId, int $customerId, array $calculationIds): array
    {
        $calculations = InterestCalculation::forCompany($companyId)
            ->where('customer_id', $customerId)
            ->whereIn('id', $calculationIds)
            ->where('status', 'calculated')
            ->get();

        if ($calculations->isEmpty()) {
            throw new \InvalidArgumentException('No eligible calculations found for interest note generation.');
        }

        $totalInterest = $calculations->sum('interest_amount');

        // Build line items description
        $lines = [];
        foreach ($calculations as $calc) {
            $invoice = Invoice::find($calc->invoice_id);
            $invoiceNum = $invoice ? $invoice->invoice_number : 'N/A';
            $lines[] = sprintf(
                'Камата за фактура %s (%d дена, %.2f%%): %s',
                $invoiceNum,
                $calc->days_overdue,
                $calc->annual_rate,
                number_format($calc->interest_amount / 100, 2, '.', ',')
            );
        }

        // Mark calculations as invoiced
        InterestCalculation::whereIn('id', $calculations->pluck('id'))
            ->update(['status' => 'invoiced']);

        return [
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'total_interest' => $totalInterest,
            'calculation_count' => $calculations->count(),
            'line_items' => $lines,
            'calculation_ids' => $calculations->pluck('id')->toArray(),
        ];
    }

    /**
     * Waive an interest calculation (set status to 'waived').
     */
    public function waive(InterestCalculation $calc): InterestCalculation
    {
        if ($calc->status === 'invoiced') {
            throw new \InvalidArgumentException('Cannot waive an already-invoiced interest calculation.');
        }

        if ($calc->status === 'paid') {
            throw new \InvalidArgumentException('Cannot waive an already-paid interest calculation.');
        }

        $calc->update(['status' => 'waived']);

        return $calc->fresh();
    }

    /**
     * Get summary statistics for a company's interest calculations.
     */
    public function getSummary(int $companyId): array
    {
        $totals = InterestCalculation::forCompany($companyId)
            ->select(
                'status',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(interest_amount) as total_amount')
            )
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $byCustomer = InterestCalculation::forCompany($companyId)
            ->where('status', '<>', 'waived')
            ->select(
                'customer_id',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(interest_amount) as total_interest'),
                DB::raw('SUM(principal_amount) as total_principal')
            )
            ->groupBy('customer_id')
            ->with('customer:id,name')
            ->get()
            ->map(function ($row) {
                return [
                    'customer_id' => $row->customer_id,
                    'customer_name' => $row->customer?->name ?? 'Unknown',
                    'count' => (int) $row->count,
                    'total_interest' => (int) $row->total_interest,
                    'total_principal' => (int) $row->total_principal,
                ];
            });

        return [
            'total_interest' => (int) ($totals->where('status', '<>', 'waived')->sum('total_amount') ?? 0),
            'calculated' => [
                'count' => (int) ($totals->get('calculated')?->count ?? 0),
                'amount' => (int) ($totals->get('calculated')?->total_amount ?? 0),
            ],
            'invoiced' => [
                'count' => (int) ($totals->get('invoiced')?->count ?? 0),
                'amount' => (int) ($totals->get('invoiced')?->total_amount ?? 0),
            ],
            'paid' => [
                'count' => (int) ($totals->get('paid')?->count ?? 0),
                'amount' => (int) ($totals->get('paid')?->total_amount ?? 0),
            ],
            'waived' => [
                'count' => (int) ($totals->get('waived')?->count ?? 0),
                'amount' => (int) ($totals->get('waived')?->total_amount ?? 0),
            ],
            'by_customer' => $byCustomer->values()->toArray(),
        ];
    }
}

// CLAUDE-CHECKPOINT
