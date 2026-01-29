<!DOCTYPE html>
<html>
<head>
    <title>Платен лист - {{ $employee->full_name }} - {{ $periodName }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            color: #333;
        }
        .container {
            width: 100%;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
        }
        .header .period {
            font-size: 14px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        .info-section {
            margin-bottom: 25px;
        }
        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .info-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 3px;
        }
        .info-value {
            color: #555;
        }
        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .breakdown-table th {
            background-color: #34495e;
            color: white;
            padding: 10px;
            text-align: left;
            font-weight: bold;
        }
        .breakdown-table td {
            padding: 8px;
            border-bottom: 1px solid #ecf0f1;
        }
        .breakdown-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .breakdown-table .section-header {
            background-color: #e8eaed;
            font-weight: bold;
            color: #2c3e50;
        }
        .breakdown-table .amount {
            text-align: right;
            font-family: "DejaVu Sans Mono", monospace;
        }
        .summary-box {
            margin-top: 30px;
            border: 2px solid #27ae60;
            padding: 15px;
            background-color: #e8f8f5;
        }
        .summary-box .label {
            font-size: 14px;
            color: #27ae60;
            font-weight: bold;
        }
        .summary-box .amount {
            font-size: 20px;
            color: #27ae60;
            font-weight: bold;
            text-align: right;
            font-family: "DejaVu Sans Mono", monospace;
        }
        .footer {
            margin-top: 40px;
            padding-top: 15px;
            border-top: 1px solid #bdc3c7;
            text-align: center;
            font-size: 9px;
            color: #7f8c8d;
        }
        .money-format {
            font-family: "DejaVu Sans Mono", monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>ПЛАТЕН ЛИСТ</h1>
            <div class="period">{{ $periodName }}</div>
        </div>

        <!-- Company & Employee Information -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-col">
                    <div class="info-label">Компанија:</div>
                    <div class="info-value">{{ $company->name }}</div>
                    @if($company->address_street_1)
                        <div class="info-value">{{ $company->address_street_1 }}</div>
                    @endif
                    @if($company->city || $company->zip)
                        <div class="info-value">{{ $company->zip }} {{ $company->city }}</div>
                    @endif
                </div>
                <div class="info-col">
                    <div class="info-label">Вработен:</div>
                    <div class="info-value">{{ $employee->full_name }}</div>
                    <div class="info-value">Број на вработен: {{ $employee->employee_number }}</div>
                    <div class="info-value">ЕМБГ: {{ $employee->embg }}</div>
                    <div class="info-value">Позиција: {{ $employee->position }}</div>
                    @if($employee->department)
                        <div class="info-value">Оддел: {{ $employee->department }}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pay Period -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-col">
                    <div class="info-label">Период на исплата:</div>
                    <div class="info-value">{{ $payrollRun->period_start->format('d.m.Y') }} - {{ $payrollRun->period_end->format('d.m.Y') }}</div>
                </div>
                <div class="info-col">
                    <div class="info-label">Работни денови:</div>
                    <div class="info-value">{{ $payrollRunLine->worked_days }} / {{ $payrollRunLine->working_days }}</div>
                </div>
            </div>
        </div>

        <!-- Earnings & Deductions Breakdown -->
        <table class="breakdown-table">
            <thead>
                <tr>
                    <th>Опис</th>
                    <th class="amount">Износ ({{ $employee->currency->code ?? 'MKD' }})</th>
                </tr>
            </thead>
            <tbody>
                <!-- Earnings Section -->
                <tr class="section-header">
                    <td colspan="2">ПРИМАЊА</td>
                </tr>
                <tr>
                    <td>Основна плата (Бруто)</td>
                    <td class="amount money-format">{{ number_format($payrollRunLine->gross_salary / 100, 2, '.', ',') }}</td>
                </tr>
                @if($payrollRunLine->transport_allowance > 0)
                <tr>
                    <td>Надомест за превоз</td>
                    <td class="amount money-format">{{ number_format($payrollRunLine->transport_allowance / 100, 2, '.', ',') }}</td>
                </tr>
                @endif
                @if($payrollRunLine->meal_allowance > 0)
                <tr>
                    <td>Надомест за храна</td>
                    <td class="amount money-format">{{ number_format($payrollRunLine->meal_allowance / 100, 2, '.', ',') }}</td>
                </tr>
                @endif
                @if($payrollRunLine->other_additions && count($payrollRunLine->other_additions) > 0)
                    @foreach($payrollRunLine->other_additions as $addition)
                    <tr>
                        <td>{{ $addition['name'] ?? 'Друг додаток' }}</td>
                        <td class="amount money-format">{{ number_format(($addition['amount'] ?? 0) / 100, 2, '.', ',') }}</td>
                    </tr>
                    @endforeach
                @endif

                <!-- Employee Contributions Section -->
                <tr class="section-header">
                    <td colspan="2">ПРИДОНЕСИ НА ВРАБОТЕН</td>
                </tr>
                <tr>
                    <td>Пензиски фонд (ПИО) - 9%</td>
                    <td class="amount money-format">-{{ number_format($payrollRunLine->pension_contribution_employee / 100, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td>Здравствено осигурување (ЗО) - 3.75%</td>
                    <td class="amount money-format">-{{ number_format($payrollRunLine->health_contribution_employee / 100, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td>Осигурување од невработеност - 1.2%</td>
                    <td class="amount money-format">-{{ number_format($payrollRunLine->unemployment_contribution / 100, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td>Дополнителен придонес - 0.5%</td>
                    <td class="amount money-format">-{{ number_format($payrollRunLine->additional_contribution / 100, 2, '.', ',') }}</td>
                </tr>

                <!-- Tax Section -->
                <tr class="section-header">
                    <td colspan="2">ДАНОЦИ</td>
                </tr>
                <tr>
                    <td>Данок на доход - 10%</td>
                    <td class="amount money-format">-{{ number_format($payrollRunLine->income_tax_amount / 100, 2, '.', ',') }}</td>
                </tr>

                @if($payrollRunLine->deductions && count($payrollRunLine->deductions) > 0)
                <!-- Other Deductions Section -->
                <tr class="section-header">
                    <td colspan="2">ДРУГИ ОДБИТОЦИ</td>
                </tr>
                    @foreach($payrollRunLine->deductions as $deduction)
                    <tr>
                        <td>{{ $deduction['name'] ?? 'Одбиток' }}</td>
                        <td class="amount money-format">-{{ number_format(($deduction['amount'] ?? 0) / 100, 2, '.', ',') }}</td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
        </table>

        <!-- Net Pay Summary -->
        <div class="summary-box">
            <table style="width: 100%;">
                <tr>
                    <td class="label">НЕТО ПЛАТА (ЗА ИСПЛАТА):</td>
                    <td class="amount">{{ number_format($payrollRunLine->net_salary / 100, 2, '.', ',') }} {{ $employee->currency->code ?? 'MKD' }}</td>
                </tr>
            </table>
        </div>

        <!-- Additional Information -->
        @if($employee->bank_name || $employee->bank_account_iban)
        <div class="info-section" style="margin-top: 20px;">
            <div class="info-label">Податоци за исплата:</div>
            @if($employee->bank_name)
            <div class="info-value">Банка: {{ $employee->bank_name }}</div>
            @endif
            @if($employee->bank_account_iban)
            <div class="info-value">IBAN: {{ $employee->bank_account_iban }}</div>
            @endif
        </div>
        @endif

        <!-- Employer Contributions (Information Only) -->
        <div class="info-section" style="margin-top: 20px; background-color: #f8f9fa; padding: 10px; border-left: 3px solid #3498db;">
            <div class="info-label" style="color: #3498db;">Придонеси на работодавач (Информативно):</div>
            <div class="info-value">Пензиски фонд (ПИО) - 9%: {{ number_format($payrollRunLine->pension_contribution_employer / 100, 2, '.', ',') }} {{ $employee->currency->code ?? 'MKD' }}</div>
            <div class="info-value">Здравствено осигурување (ЗО) - 3.75%: {{ number_format($payrollRunLine->health_contribution_employer / 100, 2, '.', ',') }} {{ $employee->currency->code ?? 'MKD' }}</div>
            <div class="info-value" style="margin-top: 5px; font-weight: bold;">Вкупен трошок на работодавач: {{ number_format($payrollRunLine->total_employer_cost / 100, 2, '.', ',') }} {{ $employee->currency->code ?? 'MKD' }}</div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>Ова е компјутерски генериран документ. Не е потребен потпис.</p>
            <p>Генерирано на {{ $generatedAt }} од {{ $company->name }}</p>
            <p>За прашања во врска со овој платен лист, контактирајте го одделот за човечки ресурси.</p>
        </div>
    </div>
</body>
</html>

<!-- LLM-CHECKPOINT -->
