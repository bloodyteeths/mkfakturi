<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">

<head>
    <title>{{ $labels['title'] }}</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 10px;
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

        .entity-info {
            font-size: 10px;
            color: #555;
            text-align: center;
            margin: 2px 0 10px 0;
        }

        .parties-table {
            width: 100%;
            margin-bottom: 12px;
        }

        .party-cell {
            width: 50%;
            vertical-align: top;
            padding: 8px;
            border: 1px solid #cbd5e0;
        }

        .party-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 3px;
        }

        .party-name {
            font-size: 11px;
            font-weight: bold;
            color: #1a1a1a;
        }

        .party-detail {
            font-size: 9px;
            color: #555;
            margin-top: 2px;
        }

        .ios-table {
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
        }

        .row {
            border-bottom: 1px solid #edf2f7;
        }

        .cell {
            padding: 5px 4px;
            font-size: 9px;
            color: #2d3748;
        }

        .cell-amount {
            padding: 5px 4px;
            font-size: 9px;
            text-align: right;
            color: #2d3748;
        }

        .overdue-text {
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
            font-size: 10px;
            color: #1a202c;
        }

        .total-amount {
            padding: 6px 4px;
            font-weight: bold;
            font-size: 10px;
            text-align: right;
            color: #2c5282;
        }

        .confirmation-box {
            margin-top: 20px;
            padding: 10px;
            border: 1px solid #cbd5e0;
            background: #f7fafc;
            font-size: 9px;
            color: #4a5568;
        }

        .signature-section {
            margin-top: 30px;
            width: 100%;
        }

        .signature-label {
            font-size: 9px;
            color: #666;
            border-top: 1px solid #999;
            padding-top: 5px;
            width: 200px;
            text-align: center;
        }
    </style>
</head>

<body>
    @include('app.pdf.reports._company-header')

    <p class="heading-text">{{ $labels['title'] }}</p>
    <p class="entity-info">{{ $labels['as_of'] }}: {{ $as_of_date }}</p>

    {{-- Parties --}}
    <table class="parties-table">
        <tr>
            <td class="party-cell">
                <p class="party-label">{{ $labels['our_signature'] }}</p>
                <p class="party-name">{{ $company->name }}</p>
                @if($company->address)
                    <p class="party-detail">{{ $company->address }}</p>
                @endif
            </td>
            <td class="party-cell">
                <p class="party-label">{{ $labels['supplier'] }}</p>
                <p class="party-name">{{ $supplier->name }}</p>
                @if($supplier->tax_id)
                    <p class="party-detail">{{ $labels['tax_id'] }}: {{ $supplier->tax_id }}</p>
                @endif
                @if($supplier->vat_number)
                    <p class="party-detail">{{ $labels['vat_number'] }}: {{ $supplier->vat_number }}</p>
                @endif
                @if($supplier->address_line_1)
                    <p class="party-detail">{{ $supplier->address_line_1 }}</p>
                @endif
            </td>
        </tr>
    </table>

    {{-- Open Items Table --}}
    <table class="ios-table">
        <tr class="table-header">
            <td class="th-text" style="width: 5%; text-align: center;">#</td>
            <td class="th-text" style="width: 15%;">{{ $labels['bill_number'] }}</td>
            <td class="th-text" style="width: 12%;">{{ $labels['bill_date'] }}</td>
            <td class="th-text" style="width: 12%;">{{ $labels['due_date'] }}</td>
            <td class="th-text" style="width: 14%; text-align: right;">{{ $labels['total'] }}</td>
            <td class="th-text" style="width: 14%; text-align: right;">{{ $labels['paid'] }}</td>
            <td class="th-text" style="width: 14%; text-align: right;">{{ $labels['outstanding'] }}</td>
            <td class="th-text" style="width: 14%; text-align: right;">{{ $labels['days_overdue'] }}</td>
        </tr>

        @foreach($open_items as $i => $item)
            <tr class="row">
                <td class="cell" style="text-align: center;">{{ $i + 1 }}</td>
                <td class="cell">{{ $item['bill_number'] }}</td>
                <td class="cell">{{ \Carbon\Carbon::parse($item['bill_date'])->format('d/m/Y') }}</td>
                <td class="cell">{{ \Carbon\Carbon::parse($item['due_date'])->format('d/m/Y') }}</td>
                <td class="cell-amount">{!! format_money_pdf($item['total'], $currency) !!}</td>
                <td class="cell-amount">{!! format_money_pdf($item['paid'], $currency) !!}</td>
                <td class="cell-amount" style="font-weight: bold;">
                    {!! format_money_pdf($item['outstanding'], $currency) !!}
                </td>
                <td class="cell-amount {{ $item['days_overdue'] > 0 ? 'overdue-text' : '' }}">
                    {{ $item['days_overdue'] > 0 ? $item['days_overdue'] : '-' }}
                </td>
            </tr>
        @endforeach

        <tr class="total-row">
            <td class="total-cell" colspan="6">{{ $labels['total_open'] }}</td>
            <td class="total-amount">{!! format_money_pdf($meta['total_open'], $currency) !!}</td>
            <td class="total-amount"></td>
        </tr>
    </table>

    {{-- IOS Confirmation text --}}
    <div class="confirmation-box">
        {{ $labels['confirmation_text'] }}
    </div>

    {{-- Signature section --}}
    <table class="signature-section">
        <tr>
            <td style="width: 50%;">
                <p class="signature-label">{{ $labels['our_signature'] }}</p>
            </td>
            <td style="width: 50%; text-align: right;">
                <p class="signature-label" style="margin-left: auto;">{{ $labels['their_signature'] }}</p>
            </td>
        </tr>
    </table>
</body>

</html>
