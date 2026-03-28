<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <title>{{ $labels['title'] }}</title>
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

        .heading-text {
            font-weight: bold;
            font-size: 14px;
            color: #1a1a1a;
            text-align: center;
            margin: 5px 0;
        }

        .sub-info {
            font-size: 10px;
            color: #666;
            text-align: center;
            margin: 2px 0 10px 0;
        }

        .aging-table {
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
            font-size: 8px;
            color: #2d3748;
            text-transform: uppercase;
        }

        .row {
            border-bottom: 1px solid #edf2f7;
        }

        .row:nth-child(even) {
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

        .overdue {
            color: #9b2c2c;
            font-weight: bold;
        }

        .total-row {
            background: #e2e8f0;
            border-top: 2px solid #2c5282;
        }

        .total-cell {
            padding: 6px 4px;
            font-weight: bold;
            font-size: 9px;
            color: #1a202c;
        }

        .total-amount {
            padding: 6px 4px;
            font-weight: bold;
            font-size: 9px;
            text-align: right;
            color: #2c5282;
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
</head>

<body>
    @include('app.pdf.reports._company-header')

    <p class="heading-text">{{ $labels['title'] }}</p>
    <p class="sub-info">{{ $labels['as_of'] }}: {{ $as_of_date }}</p>

    <table class="aging-table">
        <tr class="table-header">
            <td class="th-text" style="width: 5%; text-align: center;">#</td>
            <td class="th-text" style="width: 25%; text-align: left;">{{ $labels['supplier'] }}</td>
            <td class="th-text" style="width: 10%; text-align: left;">{{ $labels['tax_id'] }}</td>
            <td class="th-text" style="width: 10%; text-align: left;">{{ $labels['city'] }}</td>
            <td class="th-text" style="width: 12%; text-align: right;">{{ $labels['current'] }}</td>
            <td class="th-text" style="width: 12%; text-align: right;">{{ $labels['days_31_60'] }}</td>
            <td class="th-text" style="width: 12%; text-align: right;">{{ $labels['days_61_90'] }}</td>
            <td class="th-text" style="width: 12%; text-align: right;">{{ $labels['days_over_90'] }}</td>
            <td class="th-text" style="width: 12%; text-align: right;">{{ $labels['total'] }}</td>
        </tr>

        @foreach($aging_data as $i => $row)
            <tr class="row">
                <td class="cell" style="text-align: center;">{{ $i + 1 }}</td>
                <td class="cell">{{ $row['supplier_name'] }}</td>
                <td class="cell">{{ $row['tax_id'] ?? '-' }}</td>
                <td class="cell">{{ $row['city'] ?? '-' }}</td>
                <td class="cell-amount">
                    @if($row['current'] > 0)
                        {!! format_money_pdf($row['current'], $currency) !!}
                    @endif
                </td>
                <td class="cell-amount {{ $row['days_31_60'] > 0 ? 'overdue' : '' }}">
                    @if($row['days_31_60'] > 0)
                        {!! format_money_pdf($row['days_31_60'], $currency) !!}
                    @endif
                </td>
                <td class="cell-amount {{ $row['days_61_90'] > 0 ? 'overdue' : '' }}">
                    @if($row['days_61_90'] > 0)
                        {!! format_money_pdf($row['days_61_90'], $currency) !!}
                    @endif
                </td>
                <td class="cell-amount {{ $row['days_over_90'] > 0 ? 'overdue' : '' }}">
                    @if($row['days_over_90'] > 0)
                        {!! format_money_pdf($row['days_over_90'], $currency) !!}
                    @endif
                </td>
                <td class="cell-amount" style="font-weight: bold;">
                    {!! format_money_pdf($row['total'], $currency) !!}
                </td>
            </tr>
        @endforeach

        <tr class="total-row">
            <td class="total-cell" colspan="4">{{ $labels['total'] }}</td>
            <td class="total-amount">{!! format_money_pdf($totals['current'], $currency) !!}</td>
            <td class="total-amount">{!! format_money_pdf($totals['days_31_60'], $currency) !!}</td>
            <td class="total-amount">{!! format_money_pdf($totals['days_61_90'], $currency) !!}</td>
            <td class="total-amount">{!! format_money_pdf($totals['days_over_90'], $currency) !!}</td>
            <td class="total-amount">{!! format_money_pdf($totals['total'], $currency) !!}</td>
        </tr>
    </table>

    <table class="signature-section">
        <tr>
            <td style="width: 50%;">
                <p class="signature-label">{{ $labels['prepared_by'] }}</p>
            </td>
            <td style="width: 50%; text-align: right;">
                <p class="signature-label" style="margin-left: auto;">{{ $labels['approved_by'] }}</p>
            </td>
        </tr>
    </table>
</body>

</html>
