<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Биланс на успех</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 11px;
            color: #333;
        }

        table {
            border-collapse: collapse;
        }

        .sub-container {
            padding: 0px 15px;
        }

        .report-header {
            width: 100%;
            margin-bottom: 5px;
        }

        .heading-text {
            font-weight: bold;
            font-size: 20px;
            color: #1a1a1a;
            width: 100%;
            text-align: left;
            padding: 0px;
            margin: 0px;
        }

        .heading-date-range {
            font-weight: normal;
            font-size: 12px;
            color: #666;
            width: 100%;
            text-align: right;
            padding: 0px;
            margin: 0px;
        }

        .sub-heading-text {
            font-weight: bold;
            font-size: 14px;
            color: #333;
            padding: 0px;
            margin: 0px;
            margin-top: 2px;
            text-align: center;
        }

        .form-label {
            font-size: 10px;
            color: #888;
            text-align: center;
            margin: 2px 0 15px 0;
        }

        .section-title {
            font-weight: bold;
            font-size: 13px;
            color: #1a1a1a;
            margin-top: 20px;
            margin-bottom: 8px;
            padding: 6px 10px;
            background: #f0f4f8;
            border-left: 3px solid #2c5282;
        }

        .accounts-table {
            width: 100%;
            margin-bottom: 5px;
        }

        .accounts-table th {
            background: #e2e8f0;
            padding: 6px 10px;
            font-size: 10px;
            font-weight: bold;
            color: #4a5568;
            text-align: center;
            border-bottom: 1px solid #cbd5e0;
        }

        .accounts-table th:first-child {
            text-align: left;
        }

        .account-row {
            border-bottom: 1px solid #edf2f7;
        }

        .account-name {
            padding: 5px 10px;
            margin: 0px;
            font-size: 11px;
            color: #2d3748;
        }

        .account-amount {
            padding: 5px 10px;
            margin: 0px;
            font-size: 11px;
            text-align: right;
            color: #2d3748;
        }

        .section-total {
            background: #edf2f7;
            border-top: 1px solid #cbd5e0;
        }

        .section-total-label {
            padding: 8px 10px;
            margin: 0px;
            font-weight: bold;
            font-size: 12px;
            color: #1a202c;
        }

        .section-total-amount {
            padding: 8px 10px;
            margin: 0px;
            font-weight: bold;
            font-size: 12px;
            text-align: right;
            color: #2c5282;
        }

        .report-footer {
            width: 100%;
            margin-top: 25px;
            padding: 10px 15px;
            box-sizing: border-box;
            border-top: 2px solid #2c5282;
        }

        .report-footer-label {
            padding: 5px 0;
            margin: 0px;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
            color: #1a202c;
        }

        .report-footer-value {
            padding: 5px 0;
            margin: 0px;
            text-align: right;
            font-weight: bold;
            font-size: 13px;
        }

        .profit-positive {
            color: #22543d !important;
        }

        .profit-negative {
            color: #c53030 !important;
        }

        .result-row {
            border-bottom: 1px solid #e2e8f0;
        }

        .result-highlight {
            background: #ebf8ff;
            border-top: 2px solid #2c5282;
        }
    </style>

    @if (App::isLocale('th'))
    @include('app.pdf.locale.th')
    @endif
</head>

<body>
    <div class="sub-container">
        <table class="report-header">
            <tr>
                <td>
                    <p class="heading-text">{{ $company->name }}</p>
                </td>
                <td>
                    <p class="heading-date-range">{{ $from_date }} - {{ $to_date }}</p>
                </td>
            </tr>
        </table>
        <p class="sub-heading-text">БИЛАНС НА УСПЕХ</p>
        <p class="form-label">Образец 37 — за период од {{ $from_date }} до {{ $to_date }}</p>

        <!-- I. ПРИХОДИ ОД РАБОТЕЊЕТО (OPERATING REVENUE) -->
        <p class="section-title">I. ПРИХОДИ ОД РАБОТЕЊЕТО</p>
        <table class="accounts-table">
            <thead>
                <tr>
                    <th style="text-align: left; width: 60%;">Позиција</th>
                    <th style="width: 20%;">Тековна година</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($incomeStatement['income_statement']['revenues']))
                    @foreach($incomeStatement['income_statement']['revenues'] as $revenue)
                    <tr class="account-row">
                        <td>
                            <p class="account-name">{{ $revenue['name'] ?? $revenue['account_name'] ?? 'N/A' }}</p>
                        </td>
                        <td>
                            <p class="account-amount">
                                {!! format_money_pdf(($revenue['balance'] ?? 0) * 100, $currency) !!}
                            </p>
                        </td>
                    </tr>
                    @endforeach
                @endif
                <tr class="section-total">
                    <td>
                        <p class="section-total-label">ВКУПНИ ПРИХОДИ (AOP 246)</p>
                    </td>
                    <td>
                        <p class="section-total-amount">
                            {!! format_money_pdf(($incomeStatement['income_statement']['totals']['revenue'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- II. РАСХОДИ ОД РАБОТЕЊЕТО (OPERATING EXPENSES) -->
        <p class="section-title">II. РАСХОДИ ОД РАБОТЕЊЕТО</p>
        <table class="accounts-table">
            <thead>
                <tr>
                    <th style="text-align: left; width: 60%;">Позиција</th>
                    <th style="width: 20%;">Тековна година</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($incomeStatement['income_statement']['expenses']))
                    @foreach($incomeStatement['income_statement']['expenses'] as $expense)
                    <tr class="account-row">
                        <td>
                            <p class="account-name">{{ $expense['name'] ?? $expense['account_name'] ?? 'N/A' }}</p>
                        </td>
                        <td>
                            <p class="account-amount">
                                {!! format_money_pdf(($expense['balance'] ?? 0) * 100, $currency) !!}
                            </p>
                        </td>
                    </tr>
                    @endforeach
                @endif
                <tr class="section-total">
                    <td>
                        <p class="section-total-label">ВКУПНИ РАСХОДИ (AOP 247)</p>
                    </td>
                    <td>
                        <p class="section-total-amount">
                            {!! format_money_pdf(($incomeStatement['income_statement']['totals']['expenses'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- РЕЗУЛТАТ (RESULT) -->
        @php
            $revenue = $incomeStatement['income_statement']['totals']['revenue'] ?? 0;
            $expenses = $incomeStatement['income_statement']['totals']['expenses'] ?? 0;
            $operatingResult = $revenue - $expenses;
            $isProfit = $operatingResult >= 0;
            $incomeTax = $isProfit ? $operatingResult * 0.10 : 0;
            $netResult = $operatingResult - $incomeTax;
        @endphp

        <table class="report-footer">
            <tr class="result-row">
                <td style="width: 60%;">
                    <p class="report-footer-label">
                        @if($isProfit)
                            ДОБИВКА ОД РАБОТЕЊЕТО (AOP 244)
                        @else
                            ЗАГУБА ОД РАБОТЕЊЕТО (AOP 245)
                        @endif
                    </p>
                </td>
                <td style="width: 20%;">
                    <p class="report-footer-value {{ $isProfit ? 'profit-positive' : 'profit-negative' }}">
                        {!! format_money_pdf(abs($operatingResult) * 100, $currency) !!}
                    </p>
                </td>
            </tr>
            <tr class="result-row">
                <td>
                    <p class="report-footer-label">
                        @if($isProfit)
                            ДОБИВКА ПРЕД ОДАНОЧУВАЊЕ (AOP 248)
                        @else
                            ЗАГУБА ПРЕД ОДАНОЧУВАЊЕ (AOP 249)
                        @endif
                    </p>
                </td>
                <td>
                    <p class="report-footer-value {{ $isProfit ? 'profit-positive' : 'profit-negative' }}">
                        {!! format_money_pdf(abs($operatingResult) * 100, $currency) !!}
                    </p>
                </td>
            </tr>
            @if($isProfit && $incomeTax > 0)
            <tr class="result-row">
                <td>
                    <p class="report-footer-label">ДАНОК НА ДОБИВКА 10% (AOP 250)</p>
                </td>
                <td>
                    <p class="report-footer-value" style="color: #c53030;">
                        {!! format_money_pdf($incomeTax * 100, $currency) !!}
                    </p>
                </td>
            </tr>
            @endif
            <tr class="result-highlight">
                <td>
                    <p class="report-footer-label" style="font-size: 14px;">
                        @if($netResult >= 0)
                            НЕТО ДОБИВКА (AOP 255)
                        @else
                            НЕТО ЗАГУБА (AOP 256)
                        @endif
                    </p>
                </td>
                <td>
                    <p class="report-footer-value {{ $netResult >= 0 ? 'profit-positive' : 'profit-negative' }}" style="font-size: 16px;">
                        {!! format_money_pdf(abs($netResult) * 100, $currency) !!}
                    </p>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>

<!-- CLAUDE-CHECKPOINT -->
