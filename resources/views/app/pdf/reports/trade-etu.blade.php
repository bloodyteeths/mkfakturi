<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Евиденција за трговски услуги (Образец ЕТУ)</title>
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
            font-size: 13px;
            color: #1a1a1a;
            text-align: center;
            margin: 8px 0 2px 0;
        }

        .period-info {
            font-size: 10px;
            color: #555;
            text-align: center;
            margin: 2px 0 10px 0;
        }

        .etu-table {
            width: 100%;
            border: 1px solid #888;
            margin-top: 5px;
        }

        .etu-table th {
            background: #2d2040;
            color: #ffffff;
            padding: 5px 4px;
            font-size: 7px;
            font-weight: bold;
            text-align: center;
            border-bottom: 2px solid #555;
            border-right: 1px solid #444;
        }

        .etu-table th:last-child {
            border-right: none;
        }

        .col-header-sub {
            font-size: 6.5px;
            font-weight: normal;
            color: #ccc;
            display: block;
        }

        .group-header {
            background: #3d3050;
            color: #ffffff;
            padding: 3px 4px;
            font-size: 7px;
            font-weight: bold;
            text-align: center;
            border-bottom: 1px solid #555;
            border-right: 1px solid #444;
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

        .cell-number {
            text-align: right;
            font-family: "DejaVu Sans";
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

        .carry-row {
            background: #f0f0f0;
            border-top: 1px solid #999;
        }

        .carry-row td {
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
                <p class="company-detail" style="font-size: 8px; color: #888;">Трговец:</p>
                <p class="company-name">{{ $company->name ?? '' }}</p>
                <p class="company-detail">
                    @if($company->address)
                        Адреса: {{ $company->address->address_street_1 ?? '' }}
                    @endif
                </p>
                <p class="company-detail">
                    Место: {{ $company->address->city ?? '' }}
                </p>
                <p class="company-detail">
                    @if($company->vat_number) ЕДБ: {{ $company->vat_number }} @endif
                </p>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: top;">
                <p class="company-detail" style="font-weight: bold;">Образец "ЕТУ"</p>
                <p class="company-detail">Правилник Сл. весник 51/04; 89/04</p>
            </td>
        </tr>
    </table>

    <p class="heading-text">ЕВИДЕНЦИЈА ЗА ВРШЕЊЕ НА ТРГОВСКИ УСЛУГИ</p>
    <p class="period-info">за период: {{ $from_date }} — {{ $to_date }}</p>

    <table class="etu-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 4%;">
                    Ред.<br>бр.
                    <span class="col-header-sub">1</span>
                </th>
                <th rowspan="2" style="width: 7%;">
                    Датум на<br>книжење<br>(ден и месец)
                    <span class="col-header-sub">2</span>
                </th>
                <th class="group-header" colspan="3" style="width: 36%;">
                    Книговодствен документ
                </th>
                <th rowspan="2" style="width: 14%;">
                    Назив на извршени<br>трговски услуги
                    <span class="col-header-sub">6</span>
                </th>
                <th rowspan="2" style="width: 10%;">
                    Износ на вредноста<br>на услугите<br>со ДДВ
                    <span class="col-header-sub">7</span>
                </th>
                <th rowspan="2" style="width: 8%;">
                    Износ<br>ДДВ
                    <span class="col-header-sub">8</span>
                </th>
                <th rowspan="2" style="width: 10%;">
                    Износ на<br>наплатени<br>услуги
                    <span class="col-header-sub">9</span>
                </th>
            </tr>
            <tr>
                <th style="width: 7%;">
                    Број
                    <span class="col-header-sub">3</span>
                </th>
                <th style="width: 7%;">
                    Датум
                    <span class="col-header-sub">4</span>
                </th>
                <th style="width: 22%;">
                    Назив (клиент, место,<br>опис на услугата)
                    <span class="col-header-sub">5</span>
                </th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalWithVat = 0;
                $totalVat = 0;
                $totalCollected = 0;
            @endphp

            @foreach($entries as $entry)
            @php
                $withVat = $entry['amount_with_vat'] ?? 0;
                $vat = $entry['vat_amount'] ?? 0;
                $collected = $entry['collected'] ?? 0;
                $totalWithVat += $withVat;
                $totalVat += $vat;
                $totalCollected += $collected;
            @endphp
            <tr class="entry-row">
                <td class="cell-center">{{ $entry['seq'] ?? '' }}</td>
                <td class="cell-center">{{ $entry['date'] ?? '' }}</td>
                <td class="cell-center">{{ $entry['doc_number'] ?? '' }}</td>
                <td class="cell-center">{{ $entry['doc_date'] ?? '' }}</td>
                <td>
                    {{ $entry['doc_name'] ?? '' }}
                    @if($entry['party'] ?? '')
                        <br><span style="font-size: 6.5px; color: #888;">{{ $entry['party'] }}</span>
                    @endif
                </td>
                <td>{{ $entry['service_name'] ?? '' }}</td>
                <td class="cell-number">{!! format_money_pdf($withVat, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($vat, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($collected, $currency) !!}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="carry-row">
                <td colspan="6" style="text-align: right; font-style: italic;">Пренос →</td>
                <td class="cell-number">{!! format_money_pdf($totalWithVat, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($totalVat, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($totalCollected, $currency) !!}</td>
            </tr>
            <tr class="total-row">
                <td colspan="6" style="text-align: left;">ВКУПНО ({{ count($entries) }} записи)</td>
                <td class="cell-number">{!! format_money_pdf($totalWithVat, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($totalVat, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($totalCollected, $currency) !!}</td>
            </tr>
        </tfoot>
    </table>

    <p class="form-ref">Образец "ЕТУ" — Евиденција за вршење на трговски услуги / Правилник за евиденција Сл. весник 51/04; 89/04</p>

    <table class="signature-section">
        <tr>
            <td style="width: 50%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Печат</p>
            </td>
            <td style="width: 50%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Потпис на овластено лице</p>
            </td>
        </tr>
    </table>
</body>

</html>
