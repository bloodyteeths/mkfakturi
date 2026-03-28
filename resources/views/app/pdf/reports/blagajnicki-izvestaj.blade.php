<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Благајнички извештај</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
            color: #333;
            margin: 15px;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .report-header {
            width: 100%;
            margin-bottom: 5px;
        }

        .heading-text {
            font-weight: bold;
            font-size: 16px;
            color: #1a1a1a;
            padding: 0px;
            margin: 0px;
        }

        .heading-date {
            font-weight: normal;
            font-size: 10px;
            color: #666;
            text-align: right;
            padding: 0px;
            margin: 0px;
        }

        .sub-heading-text {
            font-weight: bold;
            font-size: 13px;
            color: #333;
            text-align: center;
            margin: 2px 0 0 0;
        }

        .period-info {
            font-size: 10px;
            color: #555;
            text-align: center;
            margin: 2px 0 8px 0;
        }

        .summary-table {
            width: 100%;
            margin-bottom: 10px;
            border: 1px solid #cbd5e0;
        }

        .summary-cell {
            padding: 8px 10px;
            text-align: center;
            border-right: 1px solid #cbd5e0;
        }

        .summary-label {
            font-size: 8px;
            color: #666;
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }

        .summary-value {
            font-size: 12px;
            font-weight: bold;
            color: #1a202c;
            margin: 0;
        }

        .ledger-table {
            margin-top: 8px;
            width: 100%;
            border: 1px solid #cbd5e0;
        }

        .table-header {
            background: #e2e8f0;
            border-bottom: 2px solid #a0aec0;
        }

        .th-text {
            padding: 6px 4px;
            font-weight: bold;
            font-size: 9px;
            color: #2d3748;
            text-align: center;
        }

        .entry-row {
            border-bottom: 1px solid #edf2f7;
        }

        .entry-row:nth-child(even) {
            background: #f7fafc;
        }

        .cell {
            padding: 4px;
            font-size: 9px;
            color: #2d3748;
        }

        .cell-amount {
            padding: 4px;
            font-size: 9px;
            text-align: right;
            color: #2d3748;
        }

        .cell-income {
            padding: 4px;
            font-size: 9px;
            text-align: right;
            color: #276749;
        }

        .cell-expense {
            padding: 4px;
            font-size: 9px;
            text-align: right;
            color: #9b2c2c;
        }

        .opening-row {
            background: #ebf4ff;
            border-bottom: 1px solid #a0aec0;
        }

        .total-row {
            background: #e2e8f0;
            border-top: 2px solid #2c5282;
        }

        .total-label {
            padding: 6px 4px;
            font-weight: bold;
            font-size: 10px;
            color: #1a202c;
        }

        .total-amount {
            padding: 6px 4px;
            font-weight: bold;
            font-size: 9px;
            text-align: right;
            color: #2c5282;
        }

        .closing-section {
            margin-top: 10px;
            border: 1px solid #cbd5e0;
            padding: 8px 10px;
            background: #ebf8ff;
        }

        .closing-label {
            font-size: 11px;
            font-weight: bold;
            color: #2c5282;
            margin: 0;
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

        .footer-section {
            margin-top: 20px;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }

        .footer-text {
            font-size: 8px;
            color: #999;
            text-align: center;
            margin: 0;
        }
    </style>
</head>

<body>
    @include('app.pdf.reports._company-header')

    <p class="sub-heading-text">БЛАГАЈНИЧКИ ИЗВЕШТАЈ бр. {{ $data['report_number'] }}</p>
    <p class="period-info">
        За период: {{ \Carbon\Carbon::parse($data['from_date'])->format('d.m.Y') }} - {{ \Carbon\Carbon::parse($data['to_date'])->format('d.m.Y') }}
    </p>

    {{-- Summary cards --}}
    @php
        $openingBalance = $data['opening_balance'] ?? 0;
        $totalIncome = $data['total_income'] ?? 0;
        $totalExpense = $data['total_expense'] ?? 0;
        $closingBalance = $data['closing_balance'] ?? 0;
    @endphp

    <table class="summary-table">
        <tr>
            <td class="summary-cell" style="background: #ebf8ff;">
                <p class="summary-label">Почетно салдо</p>
                <p class="summary-value">{!! format_money_pdf($openingBalance, $currency) !!}</p>
            </td>
            <td class="summary-cell" style="background: #f0fff4;">
                <p class="summary-label">Вкупно приход</p>
                <p class="summary-value" style="color: #276749;">{!! format_money_pdf($totalIncome, $currency) !!}</p>
            </td>
            <td class="summary-cell" style="background: #fff5f5;">
                <p class="summary-label">Вкупно расход</p>
                <p class="summary-value" style="color: #9b2c2c;">{!! format_money_pdf($totalExpense, $currency) !!}</p>
            </td>
            <td class="summary-cell" style="background: #ebf8ff; border-right: none;">
                <p class="summary-label">Краен салдо</p>
                <p class="summary-value">{!! format_money_pdf($closingBalance, $currency) !!}</p>
            </td>
        </tr>
    </table>

    <table class="ledger-table">
        <thead>
            <tr class="table-header">
                <th style="width: 5%;"><p class="th-text">#</p></th>
                <th style="width: 12%;"><p class="th-text" style="text-align: left;">Датум</p></th>
                <th style="width: 33%;"><p class="th-text" style="text-align: left;">Опис</p></th>
                <th style="width: 15%;"><p class="th-text" style="text-align: left;">Документ</p></th>
                <th style="width: 17%;"><p class="th-text">Приход</p></th>
                <th style="width: 18%;"><p class="th-text">Расход</p></th>
            </tr>
        </thead>
        <tbody>
            {{-- Opening balance row --}}
            <tr class="opening-row">
                <td></td>
                <td colspan="3">
                    <p class="cell" style="font-weight: bold;">Почетно салдо</p>
                </td>
                <td>
                    <p class="cell-amount" style="font-weight: bold;">
                        {!! format_money_pdf($openingBalance, $currency) !!}
                    </p>
                </td>
                <td><p class="cell-amount">-</p></td>
            </tr>

            @if(isset($data['entries']))
                @foreach($data['entries'] as $entry)
                <tr class="entry-row">
                    <td><p class="cell" style="text-align: center;">{{ $entry['number'] }}</p></td>
                    <td><p class="cell">{{ $entry['date'] }}</p></td>
                    <td><p class="cell">{{ $entry['description'] ?? '' }}</p></td>
                    <td><p class="cell">{{ $entry['document_ref'] ?? '' }}</p></td>
                    <td>
                        <p class="{{ ($entry['income'] ?? 0) > 0 ? 'cell-income' : 'cell-amount' }}">
                            @if(($entry['income'] ?? 0) > 0)
                                {!! format_money_pdf($entry['income'], $currency) !!}
                            @endif
                        </p>
                    </td>
                    <td>
                        <p class="{{ ($entry['expense'] ?? 0) > 0 ? 'cell-expense' : 'cell-amount' }}">
                            @if(($entry['expense'] ?? 0) > 0)
                                {!! format_money_pdf($entry['expense'], $currency) !!}
                            @endif
                        </p>
                    </td>
                </tr>
                @endforeach
            @endif
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4"><p class="total-label">ВКУПНО</p></td>
                <td>
                    <p class="total-amount" style="color: #276749;">
                        {!! format_money_pdf($totalIncome, $currency) !!}
                    </p>
                </td>
                <td>
                    <p class="total-amount" style="color: #9b2c2c;">
                        {!! format_money_pdf($totalExpense, $currency) !!}
                    </p>
                </td>
            </tr>
        </tfoot>
    </table>

    <div class="closing-section">
        <p class="closing-label">
            Краен салдо (Почетно + Приход - Расход): {!! format_money_pdf($closingBalance, $currency) !!}
        </p>
    </div>

    <table class="signature-section">
        <tr>
            <td style="width: 50%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Благајник</p>
                <p style="font-size: 8px; color: #999; margin: 2px 0 0 0;">(потпис)</p>
            </td>
            <td style="width: 50%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Контролирал</p>
                <p style="font-size: 8px; color: #999; margin: 2px 0 0 0;">(потпис)</p>
            </td>
        </tr>
    </table>

    <div class="footer-section">
        <p class="footer-text">{{ $company->name }} | Generated by Facturino</p>
    </div>
</body>

</html>
