<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Рекапитулар - {{ $period }}</title>
    <style>
        body { font-family: "DejaVu Sans", sans-serif; font-size: 8px; color: #333; margin: 10px; }
        .header { text-align: center; margin-bottom: 15px; }
        .header h1 { font-size: 16px; margin: 0; color: #2c3e50; }
        .header .period { font-size: 12px; color: #7f8c8d; margin-top: 3px; }
        .company-info { margin-bottom: 10px; font-size: 9px; }
        table.recap { width: 100%; border-collapse: collapse; }
        table.recap th { background-color: #34495e; color: white; padding: 5px 4px; text-align: right; font-size: 7px; font-weight: bold; border: 1px solid #2c3e50; }
        table.recap th:first-child, table.recap th:nth-child(2) { text-align: left; }
        table.recap td { padding: 4px; border: 1px solid #ddd; text-align: right; font-family: "DejaVu Sans Mono", monospace; font-size: 7px; }
        table.recap td:first-child, table.recap td:nth-child(2) { text-align: left; font-family: "DejaVu Sans", sans-serif; }
        table.recap tr:nth-child(even) { background-color: #f8f9fa; }
        table.recap tr.totals { background-color: #e8eaed; font-weight: bold; }
        table.recap tr.totals td { border-top: 2px solid #34495e; padding: 6px 4px; }
        .footer { margin-top: 15px; font-size: 8px; color: #7f8c8d; text-align: center; }
        .summary-row { margin-top: 10px; }
        .summary-row table { width: 100%; }
        .summary-row td { padding: 5px; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>РЕКАПИТУЛАР НА ПЛАТИ</h1>
        <div class="period">Период: {{ $period }}</div>
    </div>

    <div class="company-info">
        <strong>{{ $company->name }}</strong>
        @if($company->edb) | ЕДБ: {{ $company->edb }} @endif
        | Вработени: {{ $employee_count }}
    </div>

    <table class="recap">
        <thead>
            <tr>
                <th style="width: 3%;">Бр.</th>
                <th style="width: 16%;">Вработен</th>
                <th>Бруто</th>
                <th>ПИО 18.8%</th>
                <th>ЗО 7.5%</th>
                <th>Невр. 1.2%</th>
                <th>Доп. 0.5%</th>
                <th>Вк.придон.</th>
                <th>Дан.осн.</th>
                <th>ДЛД 10%</th>
                <th>Нето</th>
            </tr>
        </thead>
        <tbody>
            @foreach($lines as $index => $line)
            @php
                $lineContribs = $line->pension_contribution_employee + $line->health_contribution_employee
                    + $line->unemployment_contribution + $line->additional_contribution;
                $personalDeduction = $line->personal_deduction ?: config('mk.payroll.personal_deduction', 1039000);
                $lineTaxableBase = max(0, $line->gross_salary - $lineContribs - $personalDeduction);
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $line->employee->last_name }} {{ $line->employee->first_name }}</td>
                <td>{{ number_format($line->gross_salary / 100, 0, '.', ',') }}</td>
                <td>{{ number_format($line->pension_contribution_employee / 100, 0, '.', ',') }}</td>
                <td>{{ number_format($line->health_contribution_employee / 100, 0, '.', ',') }}</td>
                <td>{{ number_format($line->unemployment_contribution / 100, 0, '.', ',') }}</td>
                <td>{{ number_format($line->additional_contribution / 100, 0, '.', ',') }}</td>
                <td>{{ number_format($lineContribs / 100, 0, '.', ',') }}</td>
                <td>{{ number_format($lineTaxableBase / 100, 0, '.', ',') }}</td>
                <td>{{ number_format($line->income_tax_amount / 100, 0, '.', ',') }}</td>
                <td>{{ number_format($line->net_salary / 100, 0, '.', ',') }}</td>
            </tr>
            @endforeach
            <tr class="totals">
                <td></td>
                <td>ВКУПНО</td>
                <td>{{ number_format($totals['gross_salary'] / 100, 0, '.', ',') }}</td>
                <td>{{ number_format($totals['pension_employee'] / 100, 0, '.', ',') }}</td>
                <td>{{ number_format($totals['health_employee'] / 100, 0, '.', ',') }}</td>
                <td>{{ number_format($totals['unemployment'] / 100, 0, '.', ',') }}</td>
                <td>{{ number_format($totals['additional'] / 100, 0, '.', ',') }}</td>
                <td>{{ number_format($totals['total_employee_contributions'] / 100, 0, '.', ',') }}</td>
                @php
                    $totalTaxableBase = 0;
                    foreach ($lines as $l) {
                        $lContribs = $l->pension_contribution_employee + $l->health_contribution_employee
                            + $l->unemployment_contribution + $l->additional_contribution;
                        $lDeduction = $l->personal_deduction ?: config('mk.payroll.personal_deduction', 1039000);
                        $totalTaxableBase += max(0, $l->gross_salary - $lContribs - $lDeduction);
                    }
                @endphp
                <td>{{ number_format($totalTaxableBase / 100, 0, '.', ',') }}</td>
                <td>{{ number_format($totals['income_tax'] / 100, 0, '.', ',') }}</td>
                <td>{{ number_format($totals['net_salary'] / 100, 0, '.', ',') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Генерирано од Facturino | {{ $generated_at }} | {{ $company->name }}</p>
    </div>
</body>
</html>
