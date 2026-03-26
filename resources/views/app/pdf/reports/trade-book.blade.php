<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Евиденција во трговијата (Образец ЕТ)</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 8px;
            color: #333;
            margin: 15px;
        }

        table {
            border-collapse: collapse;
        }

        .report-header {
            width: 100%;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 9px;
            color: #333;
            margin-bottom: 2px;
        }

        .company-name {
            font-weight: bold;
            font-size: 11px;
        }

        .company-detail {
            font-size: 8px;
            color: #555;
        }

        .heading-text {
            font-weight: bold;
            font-size: 14px;
            color: #1a1a1a;
            text-align: center;
            margin: 8px 0 2px 0;
        }

        .sub-heading {
            font-size: 10px;
            color: #555;
            text-align: center;
            margin: 0 0 2px 0;
        }

        .period-info {
            font-size: 10px;
            color: #555;
            text-align: center;
            margin: 2px 0 10px 0;
        }

        .et-table {
            width: 100%;
            border: 1px solid #888;
            margin-top: 5px;
        }

        .et-table th {
            background: #2d2040;
            color: #ffffff;
            padding: 5px 4px;
            font-size: 7.5px;
            font-weight: bold;
            text-align: center;
            border-bottom: 2px solid #555;
            border-right: 1px solid #444;
        }

        .et-table th:last-child {
            border-right: none;
        }

        .col-header-sub {
            font-size: 6.5px;
            font-weight: normal;
            color: #ccc;
            display: block;
        }

        .entry-row {
            border-bottom: 1px solid #ddd;
        }

        .entry-row:nth-child(even) {
            background: #fafafa;
        }

        .entry-row td {
            padding: 3px 4px;
            font-size: 7.5px;
            color: #333;
            border-right: 1px solid #eee;
        }

        .entry-row td:last-child {
            border-right: none;
        }

        .cell-center {
            text-align: center;
        }

        .cell-right {
            text-align: right;
        }

        .cell-number {
            text-align: right;
            font-family: "DejaVu Sans";
        }

        .credit-note-row {
            background: #fff5f5 !important;
        }

        .credit-note-badge {
            background: #fed7d7;
            color: #c53030;
            padding: 1px 3px;
            border-radius: 2px;
            font-size: 6px;
            font-weight: bold;
        }

        .negative {
            color: #c53030;
        }

        .bill-row td {
            color: #555;
        }

        .expense-row td {
            color: #666;
            font-style: italic;
        }

        .total-row {
            background: #2d2040;
            border-top: 2px solid #333;
        }

        .total-row td {
            padding: 5px 4px;
            font-weight: bold;
            font-size: 8px;
            color: #ffffff;
            border-right: 1px solid #444;
        }

        .total-row td:last-child {
            border-right: none;
        }

        .page-carry {
            background: #f0f0f0;
            border-top: 1px solid #999;
        }

        .page-carry td {
            padding: 3px 4px;
            font-size: 7px;
            font-weight: bold;
            color: #555;
            border-right: 1px solid #ddd;
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

        .form-ref {
            font-size: 7px;
            color: #999;
            text-align: right;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    {{-- Company Header --}}
    <table class="report-header">
        <tr>
            <td style="width: 70%;">
                <p class="company-name">{{ $company->name ?? '' }}</p>
                <p class="company-detail">
                    @if($company->address)
                        {{ $company->address->address_street_1 ?? '' }}
                        @if($company->address->city) , {{ $company->address->city }} @endif
                    @endif
                </p>
                <p class="company-detail">
                    @if($company->vat_number) ЕДБ: {{ $company->vat_number }} @endif
                </p>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: top;">
                <p class="company-detail">Образец "ЕТ"</p>
                <p class="company-detail">Правилник Сл. весник 51/04</p>
            </td>
        </tr>
    </table>

    <p class="heading-text">ЕВИДЕНЦИЈА ВО ТРГОВИЈАТА</p>
    <p class="sub-heading">Книга за евиденција на набавка и продажба на стоки и услуги</p>
    <p class="period-info">за период: {{ $from_date }} — {{ $to_date }}</p>

    <table class="et-table">
        <thead>
            <tr>
                <th style="width: 4%;">
                    Ред.<br>бр.
                    <span class="col-header-sub">1</span>
                </th>
                <th style="width: 8%;">
                    Датум на<br>книжење
                    <span class="col-header-sub">2</span>
                </th>
                <th style="width: 20%;">
                    Книговодствен документ<br>(назив и број)
                    <span class="col-header-sub">3</span>
                </th>
                <th style="width: 8%;">
                    Датум на<br>документот
                    <span class="col-header-sub">4</span>
                </th>
                <th style="width: 15%;">
                    Набавна вредност<br>на стоките
                    <span class="col-header-sub">5</span>
                </th>
                <th style="width: 15%;">
                    Продажна вредност<br>на стоките
                    <span class="col-header-sub">6</span>
                </th>
                <th style="width: 15%;">
                    Дневен<br>промет
                    <span class="col-header-sub">7</span>
                </th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalNabavna = 0;
                $totalProdazhna = 0;
                $totalPromet = 0;
            @endphp

            @foreach($entries as $entry)
            @php
                $nabavna = $entry['nabavna'] ?? 0;
                $prodazhna = $entry['prodazhna'] ?? 0;
                $promet = $entry['promet'] ?? null;
                $isCreditNote = ($entry['doc_type'] ?? '') === 'credit_note';
                $isBill = ($entry['doc_type'] ?? '') === 'bill';
                $isExpense = ($entry['doc_type'] ?? '') === 'expense';

                $totalNabavna += $nabavna;
                $totalProdazhna += $prodazhna;
                if ($promet !== null) $totalPromet += $promet;

                $rowClass = $isCreditNote ? 'entry-row credit-note-row' : ($isBill ? 'entry-row bill-row' : ($isExpense ? 'entry-row expense-row' : 'entry-row'));
            @endphp
            <tr class="{{ $rowClass }}">
                <td class="cell-center">{{ $entry['seq'] ?? '' }}</td>
                <td class="cell-center">{{ $entry['date'] ?? '' }}</td>
                <td>
                    {{ $entry['doc_name'] ?? '' }} {{ $entry['doc_number'] ?? '' }}
                    @if($isCreditNote)<span class="credit-note-badge">КН</span>@endif
                    @if($entry['party'] ?? '')
                        <br><span style="font-size: 6.5px; color: #888;">{{ $entry['party'] }}</span>
                    @endif
                </td>
                <td class="cell-center">{{ $entry['doc_date'] ?? '' }}</td>
                <td class="cell-number {{ $nabavna < 0 ? 'negative' : '' }}">
                    @if($nabavna != 0)
                        {!! format_money_pdf($nabavna, $currency) !!}
                    @endif
                </td>
                <td class="cell-number {{ $prodazhna < 0 ? 'negative' : '' }}">
                    @if($prodazhna != 0)
                        {!! format_money_pdf($prodazhna, $currency) !!}
                    @endif
                </td>
                <td class="cell-number">
                    @if($promet !== null && $promet != 0)
                        {!! format_money_pdf($promet, $currency) !!}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" style="text-align: left;">
                    ВКУПНО ({{ count($entries) }} записи)
                </td>
                <td class="cell-number">
                    {!! format_money_pdf($totalNabavna, $currency) !!}
                </td>
                <td class="cell-number">
                    {!! format_money_pdf($totalProdazhna, $currency) !!}
                </td>
                <td class="cell-number">
                    {!! format_money_pdf($totalPromet, $currency) !!}
                </td>
            </tr>
        </tfoot>
    </table>

    <p class="form-ref">Образец "ЕТ" — Евиденција во трговијата на мало / Правилник за евиденција Сл. весник 51/04; 89/04</p>

    <table class="signature-section">
        <tr>
            <td style="width: 33%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Составил</p>
            </td>
            <td style="width: 33%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Печат</p>
            </td>
            <td style="width: 33%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Одговорно лице</p>
            </td>
        </tr>
    </table>
</body>

</html>
