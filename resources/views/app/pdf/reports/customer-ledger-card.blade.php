<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <title>{{ $report_title }}</title>
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

        .entity-info {
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

        .entry-row-ap {
            border-bottom: 1px solid #edf2f7;
            background: #f7f7f7;
        }

        .cell {
            padding: 4px;
            font-size: 9px;
            color: #2d3748;
        }

        .cell-account {
            padding: 4px;
            font-size: 8px;
            color: #4a5568;
        }

        .cell-debit {
            padding: 4px;
            font-size: 9px;
            text-align: right;
            color: #276749;
        }

        .cell-credit {
            padding: 4px;
            font-size: 9px;
            text-align: right;
            color: #9b2c2c;
        }

        .cell-balance {
            padding: 4px;
            font-size: 9px;
            text-align: right;
            font-weight: bold;
            color: #2d3748;
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

        .type-badge {
            font-size: 7px;
            padding: 1px 4px;
            border-radius: 3px;
            color: #fff;
            font-weight: bold;
        }

        .badge-invoice { background: #3182ce; }
        .badge-payment { background: #38a169; }
        .badge-bill { background: #d69e2e; }
        .badge-bill_payment { background: #805ad5; }

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

        <p class="sub-heading-text">{{ $report_title }}</p>
        <p class="entity-info">
            <strong>{{ $entity_name }}</strong>
            @if($entity_tax_id)
                &nbsp;|&nbsp; {{ $labels['tax_id'] }}: {{ $entity_tax_id }}
            @endif
            &nbsp;|&nbsp; {{ $labels['period'] }}: {{ $from_date }} — {{ $to_date }}
        </p>

        {{-- Summary cards --}}
        <table class="summary-table">
            <tr>
                <td class="summary-cell">
                    <p class="summary-label">{{ $labels['total'] }} {{ $labels['debit'] }}</p>
                    <p class="summary-value">{!! format_money_pdf($meta['total_debit'], $currency) !!}</p>
                </td>
                <td class="summary-cell">
                    <p class="summary-label">{{ $labels['total'] }} {{ $labels['credit'] }}</p>
                    <p class="summary-value">{!! format_money_pdf($meta['total_credit'], $currency) !!}</p>
                </td>
                <td class="summary-cell" style="border-right: none;">
                    <p class="summary-label">{{ $labels['closing_balance'] }}</p>
                    <p class="summary-value" style="color: {{ $meta['closing_balance'] >= 0 ? '#276749' : '#9b2c2c' }};">
                        {!! format_money_pdf(abs($meta['closing_balance']), $currency) !!}
                        @if($meta['closing_balance'] > 0)
                            ({{ $labels['receivable'] }})
                        @elseif($meta['closing_balance'] < 0)
                            ({{ $labels['payable'] }})
                        @endif
                    </p>
                </td>
            </tr>
        </table>

        {{-- Ledger table --}}
        <table class="ledger-table">
            <tr class="table-header">
                <td class="th-text" style="width: 12%; text-align: left;">{{ $labels['date'] }}</td>
                <td class="th-text" style="width: 10%;">{{ $labels['type'] }}</td>
                <td class="th-text" style="width: 14%; text-align: left;">{{ $labels['reference'] }}</td>
                <td class="th-text" style="width: 24%; text-align: left;">{{ $labels['account'] }}</td>
                <td class="th-text" style="width: 13%; text-align: right;">{{ $labels['debit'] }}</td>
                <td class="th-text" style="width: 13%; text-align: right;">{{ $labels['credit'] }}</td>
                <td class="th-text" style="width: 14%; text-align: right;">{{ $labels['balance'] }}</td>
            </tr>

            @foreach($ledger as $entry)
                <tr class="{{ $entry['side'] === 'AP' ? 'entry-row-ap' : 'entry-row' }}">
                    <td class="cell">{{ \Carbon\Carbon::parse($entry['date'])->format('d/m/Y') }}</td>
                    <td class="cell" style="text-align: center;">
                        <span class="type-badge badge-{{ $entry['type'] }}">
                            {{ $labels['types'][$entry['type']] ?? $entry['type'] }}
                        </span>
                    </td>
                    <td class="cell">{{ $entry['reference'] }}</td>
                    <td class="cell-account">
                        @if($entry['account_code'])
                            {{ $entry['account_code'] }} — {{ $entry['account_name'] }}
                        @endif
                    </td>
                    <td class="cell-debit">
                        @if($entry['debit'] > 0)
                            {!! format_money_pdf($entry['debit'], $currency) !!}
                        @endif
                    </td>
                    <td class="cell-credit">
                        @if($entry['credit'] > 0)
                            {!! format_money_pdf($entry['credit'], $currency) !!}
                        @endif
                    </td>
                    <td class="cell-balance">
                        {!! format_money_pdf(abs($entry['balance']), $currency) !!}
                        @if($entry['balance'] < 0)
                            <span style="font-size: 7px; color: #9b2c2c;">(П)</span>
                        @endif
                    </td>
                </tr>
            @endforeach

            {{-- Totals row --}}
            <tr class="total-row">
                <td class="total-label" colspan="4">{{ $labels['total'] }}</td>
                <td class="total-amount">{!! format_money_pdf($meta['total_debit'], $currency) !!}</td>
                <td class="total-amount">{!! format_money_pdf($meta['total_credit'], $currency) !!}</td>
                <td class="total-amount" style="color: {{ $meta['closing_balance'] >= 0 ? '#276749' : '#9b2c2c' }};">
                    {!! format_money_pdf(abs($meta['closing_balance']), $currency) !!}
                </td>
            </tr>
        </table>

        {{-- Signature section --}}
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
{{-- CLAUDE-CHECKPOINT --}}
