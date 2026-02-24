<?php
/**
 * Diagnostic script to check payroll data for company 2
 * Run via: php tmp_check_payroll.php
 */
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$companyId = 2;

echo "=== PayrollRuns for company {$companyId} ===" . PHP_EOL;
$runs = App\Models\PayrollRun::where('company_id', $companyId)
    ->orderBy('period_year', 'desc')
    ->orderBy('period_month', 'desc')
    ->get();

if ($runs->isEmpty()) {
    echo "  NO PAYROLL RUNS FOUND" . PHP_EOL;
} else {
    foreach ($runs as $r) {
        echo sprintf(
            "  id=%d year=%d month=%d status=%s gross=%d net=%d emp_tax=%d ee_tax=%d",
            $r->id, $r->period_year, $r->period_month, $r->status,
            $r->total_gross ?? 0, $r->total_net ?? 0,
            $r->total_employer_tax ?? 0, $r->total_employee_tax ?? 0
        ) . PHP_EOL;
    }
}

echo PHP_EOL . "=== PayrollEmployees for company {$companyId} ===" . PHP_EOL;
$employees = App\Models\PayrollEmployee::where('company_id', $companyId)->get();
if ($employees->isEmpty()) {
    echo "  NO EMPLOYEES FOUND" . PHP_EOL;
} else {
    foreach ($employees as $e) {
        echo sprintf(
            "  id=%d name=%s %s status=%s gross=%d",
            $e->id, $e->first_name, $e->last_name, $e->status, $e->gross_salary ?? 0
        ) . PHP_EOL;
    }
}

echo PHP_EOL . "=== PayrollRunLines for company {$companyId} ===" . PHP_EOL;
$lines = App\Models\PayrollRunLine::whereHas('payrollRun', fn($q) => $q->where('company_id', $companyId))->get();
if ($lines->isEmpty()) {
    echo "  NO LINES FOUND" . PHP_EOL;
} else {
    foreach ($lines as $l) {
        echo sprintf(
            "  id=%d run=%d emp=%d gross=%d net=%d pit=%d pen_ee=%d pen_er=%d hp_ee=%d hp_er=%d",
            $l->id, $l->payroll_run_id, $l->employee_id,
            $l->gross_salary ?? 0, $l->net_salary ?? 0, $l->income_tax_amount ?? 0,
            $l->pension_contribution_employee ?? 0, $l->pension_contribution_employer ?? 0,
            $l->health_contribution_employee ?? 0, $l->health_contribution_employer ?? 0
        ) . PHP_EOL;
    }
}

echo PHP_EOL . "=== Status distribution ===" . PHP_EOL;
$statusCounts = App\Models\PayrollRun::where('company_id', $companyId)
    ->selectRaw('status, count(*) as cnt')
    ->groupBy('status')
    ->pluck('cnt', 'status');
foreach ($statusCounts as $status => $count) {
    echo "  {$status}: {$count}" . PHP_EOL;
}

echo PHP_EOL . "DONE" . PHP_EOL;
