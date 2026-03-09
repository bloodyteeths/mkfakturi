<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Книга на {{ $bookType === 'input' ? 'влезни' : 'излезни' }} фактури</title>
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
            padding: 0px 10px;
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
            margin: 2px 0 10px 0;
        }

        .vat-table {
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
            font-size: 8px;
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
            font-size: 8px;
            color: #2d3748;
        }

        .cell-amount {
            padding: 4px;
            font-size: 8px;
            text-align: right;
            color: #2d3748;
        }

        .cell-center {
            padding: 4px;
            font-size: 8px;
            text-align: center;
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

        .rate-18 {
            background: #ebf8ff;
            color: #2b6cb0;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
        }

        .rate-5 {
            background: #f0fff4;
            color: #276749;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
        }

        .rate-0 {
            background: #f7fafc;
            color: #718096;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
        }

        .summary-table {
            margin-top: 15px;
            width: 60%;
            border: 1px solid #cbd5e0;
        }

        .summary-header {
            background: #edf2f7;
            border-bottom: 1px solid #a0aec0;
        }

        .summary-label {
            padding: 4px 6px;
            font-weight: bold;
            font-size: 9px;
            color: #2d3748;
        }

        .summary-cell {
            padding: 4px 6px;
            font-size: 9px;
            text-align: right;
            color: #2d3748;
        }

        .summary-total-row {
            background: #e2e8f0;
            border-top: 1px solid #a0aec0;
        }

        .credit-note-row {
            background: #fff5f5;
        }

        .credit-note-badge {
            background: #fed7d7;
            color: #c53030;
            padding: 1px 3px;
            border-radius: 2px;
            font-size: 6px;
            font-weight: bold;
            margin-left: 2px;
        }

        .negative-amount {
            color: #c53030;
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
    <div class="sub-container">
        @include('app.pdf.reports._company-header')

        <p class="sub-heading-text">
            @if($bookType === 'input')
                КНИГА НА ВЛЕЗНИ ФАКТУРИ
            @else
                КНИГА НА ИЗЛЕЗНИ ФАКТУРИ
            @endif
        </p>
        <p class="period-info">Период: {{ $from_date }} - {{ $to_date }}</p>

        <table class="vat-table">
            <thead>
                <tr class="table-header">
                    <th style="width: 4%;"><p class="th-text">Р.бр.</p></th>
                    <th style="width: 9%;"><p class="th-text" style="text-align: left;">Датум</p></th>
                    <th style="width: 9%;"><p class="th-text" style="text-align: left;">Бр. факт.</p></th>
                    <th style="width: 18%;"><p class="th-text" style="text-align: left;">
                        @if($bookType === 'input')
                            Добавувач
                        @else
                            Купувач
                        @endif
                    </p></th>
                    <th style="width: 12%;"><p class="th-text">ЕДБ</p></th>
                    <th style="width: 12%;"><p class="th-text">Основица</p></th>
                    <th style="width: 6%;"><p class="th-text">Стапка</p></th>
                    <th style="width: 12%;"><p class="th-text">ДДВ</p></th>
                    <th style="width: 14%;"><p class="th-text">Вкупно</p></th>
                </tr>
            </thead>
            <tbody>
                @php
                    $totalBase = 0;
                    $totalVat = 0;
                    $totalAmount = 0;
                    $byRate = [];
                @endphp

                @foreach($entries as $index => $entry)
                @php
                    $base = $entry['taxable_base'] ?? 0;
                    $vat = $entry['vat_amount'] ?? 0;
                    $total = $entry['total'] ?? 0;
                    $rate = $entry['vat_rate'] ?? 0;
                    $rateKey = (string) $rate;
                    $isCreditNote = ($entry['doc_type'] ?? '') === 'credit_note';

                    $totalBase += $base;
                    $totalVat += $vat;
                    $totalAmount += $total;

                    if (!isset($byRate[$rateKey])) {
                        $byRate[$rateKey] = ['rate' => $rate, 'count' => 0, 'taxable_base' => 0, 'vat_amount' => 0, 'total' => 0];
                    }
                    $byRate[$rateKey]['count']++;
                    $byRate[$rateKey]['taxable_base'] += $base;
                    $byRate[$rateKey]['vat_amount'] += $vat;
                    $byRate[$rateKey]['total'] += $total;
                @endphp
                <tr class="{{ $isCreditNote ? 'credit-note-row' : 'entry-row' }}">
                    <td><p class="cell-center">{{ $index + 1 }}</p></td>
                    <td><p class="cell">{{ $entry['date'] ?? '' }}</p></td>
                    <td><p class="cell">{{ $entry['number'] ?? '' }}@if($isCreditNote)<span class="credit-note-badge">КН</span>@endif</p></td>
                    <td><p class="cell">{{ $entry['party_name'] ?? '' }}</p></td>
                    <td><p class="cell-center" style="font-size: 7px;">{{ $entry['party_tax_id'] ?? '' }}</p></td>
                    <td>
                        <p class="cell-amount {{ $isCreditNote ? 'negative-amount' : '' }}">
                            {!! format_money_pdf($base, $currency) !!}
                        </p>
                    </td>
                    <td>
                        <p class="cell-center">
                            <span class="{{ $rate >= 18 ? 'rate-18' : ($rate >= 5 ? 'rate-5' : 'rate-0') }}">
                                {{ $rate }}%
                            </span>
                        </p>
                    </td>
                    <td>
                        <p class="cell-amount {{ $isCreditNote ? 'negative-amount' : '' }}">
                            {!! format_money_pdf($vat, $currency) !!}
                        </p>
                    </td>
                    <td>
                        <p class="cell-amount {{ $isCreditNote ? 'negative-amount' : '' }}">
                            {!! format_money_pdf($total, $currency) !!}
                        </p>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="5"><p class="total-label">ВКУПНО ({{ count($entries) }} фактури)</p></td>
                    <td><p class="total-amount">{!! format_money_pdf($totalBase, $currency) !!}</p></td>
                    <td><p class="total-amount"></p></td>
                    <td><p class="total-amount">{!! format_money_pdf($totalVat, $currency) !!}</p></td>
                    <td><p class="total-amount">{!! format_money_pdf($totalAmount, $currency) !!}</p></td>
                </tr>
            </tfoot>
        </table>

        {{-- Rate Summary / Рекапитулација --}}
        @if(count($byRate) > 0)
        @php
            // Sort by rate descending
            usort($byRate, function($a, $b) { return $b['rate'] <=> $a['rate']; });
        @endphp
        <p style="font-weight: bold; font-size: 10px; margin-top: 15px; color: #333;">Рекапитулација по стапка на ДДВ</p>
        <table class="summary-table">
            <thead>
                <tr class="summary-header">
                    <th style="width: 15%;"><p class="th-text" style="text-align: left;">Стапка</p></th>
                    <th style="width: 10%;"><p class="th-text">Бр.</p></th>
                    <th style="width: 25%;"><p class="th-text">Основица</p></th>
                    <th style="width: 25%;"><p class="th-text">ДДВ</p></th>
                    <th style="width: 25%;"><p class="th-text">Вкупно</p></th>
                </tr>
            </thead>
            <tbody>
                @foreach($byRate as $rateSummary)
                <tr class="entry-row">
                    <td><p class="summary-label">{{ $rateSummary['rate'] }}%</p></td>
                    <td><p class="cell-center">{{ $rateSummary['count'] }}</p></td>
                    <td><p class="summary-cell">{!! format_money_pdf($rateSummary['taxable_base'], $currency) !!}</p></td>
                    <td><p class="summary-cell">{!! format_money_pdf($rateSummary['vat_amount'], $currency) !!}</p></td>
                    <td><p class="summary-cell">{!! format_money_pdf($rateSummary['total'], $currency) !!}</p></td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="summary-total-row">
                    <td><p class="summary-label">Вкупно</p></td>
                    <td><p class="cell-center">{{ count($entries) }}</p></td>
                    <td><p class="summary-cell" style="font-weight: bold;">{!! format_money_pdf($totalBase, $currency) !!}</p></td>
                    <td><p class="summary-cell" style="font-weight: bold;">{!! format_money_pdf($totalVat, $currency) !!}</p></td>
                    <td><p class="summary-cell" style="font-weight: bold;">{!! format_money_pdf($totalAmount, $currency) !!}</p></td>
                </tr>
            </tfoot>
        </table>
        @endif

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
