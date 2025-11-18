<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Providers\CacheServiceProvider;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class DashboardMetricsService
{
    public function __construct(private CacheRepository $cache) {}

    public function getAnnualSeries(int $companyId, bool $previousYear = false): array
    {
        $window = $this->determineFiscalWindow($companyId, $previousYear);
        $cacheKey = sprintf(
            'dashboard:series:%d:%s:%s',
            $companyId,
            $window['start']->format('Y-m'),
            $previousYear ? 'previous' : 'current'
        );

        return $this->cache->remember($cacheKey, CacheServiceProvider::CACHE_TTLS['SHORT'], function () use ($companyId, $window) {
            $invoiceByMonth = $this->aggregateMonthly(Invoice::class, 'invoice_date', 'base_total', $companyId, $window['start'], $window['end']);
            $expenseByMonth = $this->aggregateMonthly(Expense::class, 'expense_date', 'base_amount', $companyId, $window['start'], $window['end']);
            $paymentByMonth = $this->aggregateMonthly(Payment::class, 'payment_date', 'base_amount', $companyId, $window['start'], $window['end']);

            $months = [];
            $invoiceTotals = [];
            $expenseTotals = [];
            $receiptTotals = [];
            $netIncomeTotals = [];

            $cursor = $window['start']->copy();

            for ($i = 0; $i < 12; $i++) {
                $key = $cursor->format('Y-m');
                $invoiceTotal = (int) ($invoiceByMonth[$key] ?? 0);
                $expenseTotal = (int) ($expenseByMonth[$key] ?? 0);
                $receiptTotal = (int) ($paymentByMonth[$key] ?? 0);

                $months[] = $cursor->translatedFormat('M');
                $invoiceTotals[] = $invoiceTotal;
                $expenseTotals[] = $expenseTotal;
                $receiptTotals[] = $receiptTotal;
                $netIncomeTotals[] = $receiptTotal - $expenseTotal;

                $cursor->addMonth();
            }

            return [
                'months' => $months,
                'invoice_totals' => $invoiceTotals,
                'expense_totals' => $expenseTotals,
                'receipt_totals' => $receiptTotals,
                'net_income_totals' => $netIncomeTotals,
                'total_sales' => array_sum($invoiceTotals),
                'total_receipts' => array_sum($receiptTotals),
                'total_expenses' => array_sum($expenseTotals),
                'range_start' => $window['start']->toDateString(),
                'range_end' => $window['end']->toDateString(),
            ];
        });
    }

    public function getCounts(int $companyId): array
    {
        return $this->cache->remember(
            sprintf('dashboard:counts:%d', $companyId),
            CacheServiceProvider::CACHE_TTLS['SHORT'],
            function () use ($companyId) {
                return [
                    'total_customer_count' => Customer::where('company_id', $companyId)->count(),
                    'total_invoice_count' => Invoice::where('company_id', $companyId)->count(),
                    'total_estimate_count' => Estimate::where('company_id', $companyId)->count(),
                    'total_amount_due' => (int) Invoice::where('company_id', $companyId)->sum('base_due_amount'),
                ];
            }
        );
    }

    public function getRecentEntities(int $companyId): array
    {
        return $this->cache->remember(
            sprintf('dashboard:recent:%d', $companyId),
            CacheServiceProvider::CACHE_TTLS['SHORT'],
            function () use ($companyId) {
                $recentInvoices = Invoice::query()
                    ->select(['id', 'invoice_number', 'invoice_date', 'base_due_amount', 'customer_id'])
                    ->with(['customer:id,name,email'])
                    ->where('company_id', $companyId)
                    ->where('base_due_amount', '>', 0)
                    ->orderByDesc('invoice_date')
                    ->limit(5)
                    ->get();

                $recentEstimates = Estimate::query()
                    ->select(['id', 'estimate_number', 'estimate_date', 'expiry_date', 'total', 'customer_id'])
                    ->with(['customer:id,name,email'])
                    ->where('company_id', $companyId)
                    ->orderByDesc('estimate_date')
                    ->limit(5)
                    ->get();

                return [
                    'invoices' => $recentInvoices,
                    'estimates' => $recentEstimates,
                ];
            }
        );
    }

    private function aggregateMonthly(string $modelClass, string $dateColumn, string $sumColumn, int $companyId, Carbon $start, Carbon $end): array
    {
        $driver = DB::connection()->getDriverName();

        $periodExpression = match ($driver) {
            'sqlite' => "strftime('%Y-%m', {$dateColumn})",
            'pgsql' => "to_char({$dateColumn}, 'YYYY-MM')",
            default => "DATE_FORMAT({$dateColumn}, '%Y-%m')",
        };

        return $modelClass::query()
            ->selectRaw($periodExpression.' as period, SUM('.$sumColumn.') as aggregate')
            ->where('company_id', $companyId)
            ->whereBetween($dateColumn, [$start->toDateString(), $end->toDateString()])
            ->groupBy('period')
            ->orderBy('period')
            ->pluck('aggregate', 'period')
            ->toArray();
    }

    private function determineFiscalWindow(int $companyId, bool $previousYear): array
    {
        $fiscalYear = CompanySetting::getSetting('fiscal_year', $companyId) ?? '1-12';
        $terms = explode('-', $fiscalYear);
        $startMonth = (int) Arr::first($terms, fn ($value) => ! empty($value), 1);

        $now = Carbon::now();
        $start = $now->copy()->month($startMonth)->startOfMonth();

        if ($start->greaterThan($now)) {
            $start->subYear();
        }

        if ($previousYear) {
            $start->subYear();
        }

        $end = $start->copy()->addMonths(12)->subDay();

        return [
            'start' => $start,
            'end' => $end,
        ];
    }
}
