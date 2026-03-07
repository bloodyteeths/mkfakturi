<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Биланс на приходи и расходи</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
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
            font-size: 16px;
            color: #1a1a1a;
            width: 100%;
            text-align: left;
            padding: 0px;
            margin: 0px;
        }

        .heading-date {
            font-weight: normal;
            font-size: 10px;
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
            font-size: 9px;
            color: #888;
            text-align: center;
            margin: 2px 0 10px 0;
        }

        .section-title {
            font-weight: bold;
            font-size: 11px;
            color: #1a1a1a;
            margin-top: 12px;
            margin-bottom: 4px;
            padding: 4px 8px;
            background: #f0f4f8;
            border-left: 3px solid #2c5282;
        }

        .data-table {
            width: 100%;
            border: 1px solid #cbd5e0;
            margin-bottom: 5px;
        }

        .data-table th {
            background: #e2e8f0;
            padding: 5px 6px;
            font-size: 8px;
            font-weight: bold;
            color: #2d3748;
            text-align: center;
            border-bottom: 2px solid #a0aec0;
        }

        .data-row {
            border-bottom: 1px solid #edf2f7;
        }

        .data-row:nth-child(even) {
            background: #f7fafc;
        }

        .data-row td {
            padding: 4px 6px;
            font-size: 9px;
            color: #2d3748;
        }

        .amount-col {
            text-align: right;
            width: 25%;
        }

        .label-col {
            text-align: left;
            width: 75%;
        }

        .total-row {
            background: #e2e8f0 !important;
            border-top: 2px solid #2c5282;
        }

        .total-row td {
            font-weight: bold;
            font-size: 10px;
            color: #1a202c;
            padding: 6px 6px;
        }

        .result-section {
            margin-top: 15px;
            border: 2px solid #2c5282;
            padding: 10px 15px;
            background: #ebf8ff;
        }

        .result-label {
            font-weight: bold;
            font-size: 12px;
            color: #2d3748;
            margin: 0;
        }

        .result-amount {
            font-weight: bold;
            font-size: 14px;
            color: #2c5282;
            text-align: right;
            margin: 0;
        }

        .profit-positive {
            color: #22543d !important;
        }

        .profit-negative {
            color: #c53030 !important;
        }

        .signature-section {
            margin-top: 30px;
            width: 100%;
        }

        .signature-label {
            font-size: 9px;
            color: #666;
            border-top: 1px solid #999;
            padding-top: 3px;
            width: 200px;
            text-align: center;
        }
    </style>

    @if (App::isLocale('th'))
    @include('app.pdf.locale.th')
    @endif
</head>

<body>
    <div class="sub-container">
        @include('app.pdf.reports._company-header', ['report_period' => $from_date . ' - ' . $to_date])

        <p class="sub-heading-text">БИЛАНС НА ПРИХОДИ И РАСХОДИ</p>
        <p class="form-label">За период: {{ $from_date }} - {{ $to_date }}</p>

        <!-- ПРИХОДИ (Income) -->
        <p class="section-title">I. ПРИХОДИ</p>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="text-align: left; width: 75%;">Опис</th>
                    <th style="width: 25%;">Износ</th>
                </tr>
            </thead>
            <tbody>
                <tr class="total-row">
                    <td class="label-col">Вкупни приходи (наплатени фактури)</td>
                    <td class="amount-col">{!! format_money_pdf($income, $currency) !!}</td>
                </tr>
            </tbody>
        </table>

        <!-- РАСХОДИ (Expenses) -->
        <p class="section-title">II. РАСХОДИ</p>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="text-align: left; width: 75%;">Категорија</th>
                    <th style="width: 25%;">Износ</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($expenseCategories as $expenseCategory)
                <tr class="data-row">
                    <td class="label-col">{{ $expenseCategory->category->name ?? '-' }}</td>
                    <td class="amount-col">{!! format_money_pdf($expenseCategory->total_amount, $currency) !!}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td class="label-col">Вкупни расходи</td>
                    <td class="amount-col">{!! format_money_pdf($totalExpense, $currency) !!}</td>
                </tr>
            </tbody>
        </table>

        <!-- РЕЗУЛТАТ (Net Result) -->
        @php
            $netResult = $income - $totalExpense;
            $isProfit = $netResult >= 0;
        @endphp
        <table class="result-section" style="width: 100%;">
            <tr>
                <td style="width: 60%;">
                    <p class="result-label">
                        {{ $isProfit ? 'НЕТО ДОБИВКА' : 'НЕТО ЗАГУБА' }}
                    </p>
                </td>
                <td style="width: 40%;">
                    <p class="result-amount {{ $isProfit ? 'profit-positive' : 'profit-negative' }}">
                        {!! format_money_pdf(abs($netResult), $currency) !!}
                    </p>
                </td>
            </tr>
        </table>

        <!-- Signatures -->
        <table class="signature-section">
            <tr>
                <td style="width: 50%; text-align: center; padding-top: 40px;">
                    <p class="signature-label">Составил</p>
                </td>
                <td style="width: 50%; text-align: center; padding-top: 40px;">
                    <p class="signature-label">Одговорно лице</p>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>

<!-- CLAUDE-CHECKPOINT -->
