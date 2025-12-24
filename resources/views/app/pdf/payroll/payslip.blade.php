<!DOCTYPE html>
<html>
<head>
    <title>Payslip - {{ $employee->full_name }} - {{ $periodName }}</title>
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
            <h1>PAYSLIP</h1>
            <div class="period">{{ $periodName }}</div>
        </div>

        <!-- Company & Employee Information -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-col">
                    <div class="info-label">Company:</div>
                    <div class="info-value">{{ $company->name }}</div>
                    @if($company->address_street_1)
                        <div class="info-value">{{ $company->address_street_1 }}</div>
                    @endif
                    @if($company->city || $company->zip)
                        <div class="info-value">{{ $company->zip }} {{ $company->city }}</div>
                    @endif
                </div>
                <div class="info-col">
                    <div class="info-label">Employee:</div>
                    <div class="info-value">{{ $employee->full_name }}</div>
                    <div class="info-value">Employee #: {{ $employee->employee_number }}</div>
                    <div class="info-value">EMBG: {{ $employee->embg }}</div>
                    <div class="info-value">Position: {{ $employee->position }}</div>
                    @if($employee->department)
                        <div class="info-value">Department: {{ $employee->department }}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Pay Period -->
        <div class="info-section">
            <div class="info-row">
                <div class="info-col">
                    <div class="info-label">Pay Period:</div>
                    <div class="info-value">{{ $payrollRun->period_start->format('d.m.Y') }} - {{ $payrollRun->period_end->format('d.m.Y') }}</div>
                </div>
                <div class="info-col">
                    <div class="info-label">Working Days:</div>
                    <div class="info-value">{{ $payrollRunLine->worked_days }} / {{ $payrollRunLine->working_days }}</div>
                </div>
            </div>
        </div>

        <!-- Earnings & Deductions Breakdown -->
        <table class="breakdown-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="amount">Amount ({{ $employee->currency->code }})</th>
                </tr>
            </thead>
            <tbody>
                <!-- Earnings Section -->
                <tr class="section-header">
                    <td colspan="2">EARNINGS</td>
                </tr>
                <tr>
                    <td>Base Salary (Gross)</td>
                    <td class="amount money-format">{{ number_format($payrollRunLine->gross_salary / 100, 2, '.', ',') }}</td>
                </tr>
                @if($payrollRunLine->transport_allowance > 0)
                <tr>
                    <td>Transport Allowance</td>
                    <td class="amount money-format">{{ number_format($payrollRunLine->transport_allowance / 100, 2, '.', ',') }}</td>
                </tr>
                @endif
                @if($payrollRunLine->meal_allowance > 0)
                <tr>
                    <td>Meal Allowance</td>
                    <td class="amount money-format">{{ number_format($payrollRunLine->meal_allowance / 100, 2, '.', ',') }}</td>
                </tr>
                @endif
                @if($payrollRunLine->other_additions && count($payrollRunLine->other_additions) > 0)
                    @foreach($payrollRunLine->other_additions as $addition)
                    <tr>
                        <td>{{ $addition['name'] ?? 'Other Addition' }}</td>
                        <td class="amount money-format">{{ number_format(($addition['amount'] ?? 0) / 100, 2, '.', ',') }}</td>
                    </tr>
                    @endforeach
                @endif

                <!-- Employee Contributions Section -->
                <tr class="section-header">
                    <td colspan="2">EMPLOYEE CONTRIBUTIONS</td>
                </tr>
                <tr>
                    <td>Pension Fund (PIO) - 9%</td>
                    <td class="amount money-format">-{{ number_format($payrollRunLine->pension_contribution_employee / 100, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td>Health Insurance (ZO) - 3.75%</td>
                    <td class="amount money-format">-{{ number_format($payrollRunLine->health_contribution_employee / 100, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td>Unemployment Insurance - 1.2%</td>
                    <td class="amount money-format">-{{ number_format($payrollRunLine->unemployment_contribution / 100, 2, '.', ',') }}</td>
                </tr>
                <tr>
                    <td>Additional Contribution - 0.5%</td>
                    <td class="amount money-format">-{{ number_format($payrollRunLine->additional_contribution / 100, 2, '.', ',') }}</td>
                </tr>

                <!-- Tax Section -->
                <tr class="section-header">
                    <td colspan="2">TAXES</td>
                </tr>
                <tr>
                    <td>Income Tax - 10%</td>
                    <td class="amount money-format">-{{ number_format($payrollRunLine->income_tax_amount / 100, 2, '.', ',') }}</td>
                </tr>

                @if($payrollRunLine->deductions && count($payrollRunLine->deductions) > 0)
                <!-- Other Deductions Section -->
                <tr class="section-header">
                    <td colspan="2">OTHER DEDUCTIONS</td>
                </tr>
                    @foreach($payrollRunLine->deductions as $deduction)
                    <tr>
                        <td>{{ $deduction['name'] ?? 'Deduction' }}</td>
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
                    <td class="label">NET PAY (TO BE PAID):</td>
                    <td class="amount">{{ number_format($payrollRunLine->net_salary / 100, 2, '.', ',') }} {{ $employee->currency->code }}</td>
                </tr>
            </table>
        </div>

        <!-- Additional Information -->
        <div class="info-section" style="margin-top: 20px;">
            <div class="info-label">Payment Details:</div>
            <div class="info-value">Bank: {{ $employee->bank_name }}</div>
            <div class="info-value">IBAN: {{ $employee->bank_account_iban }}</div>
        </div>

        <!-- Employer Contributions (Information Only) -->
        <div class="info-section" style="margin-top: 20px; background-color: #f8f9fa; padding: 10px; border-left: 3px solid #3498db;">
            <div class="info-label" style="color: #3498db;">Employer Contributions (Information Only):</div>
            <div class="info-value">Pension Fund (PIO) - 9%: {{ number_format($payrollRunLine->pension_contribution_employer / 100, 2, '.', ',') }} {{ $employee->currency->code }}</div>
            <div class="info-value">Health Insurance (ZO) - 3.75%: {{ number_format($payrollRunLine->health_contribution_employer / 100, 2, '.', ',') }} {{ $employee->currency->code }}</div>
            <div class="info-value" style="margin-top: 5px; font-weight: bold;">Total Employer Cost: {{ number_format($payrollRunLine->total_employer_cost / 100, 2, '.', ',') }} {{ $employee->currency->code }}</div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>This is a computer-generated document. No signature is required.</p>
            <p>Generated on {{ $generatedAt }} by {{ $company->name }}</p>
            <p>For questions regarding this payslip, please contact your HR department.</p>
        </div>
    </div>
</body>
</html>

<!-- LLM-CHECKPOINT -->
