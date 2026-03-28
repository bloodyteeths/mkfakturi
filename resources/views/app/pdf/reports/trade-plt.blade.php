<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Приемен лист во трговијата на мало (Образец ПЛТ)</title>
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

        .doc-info {
            font-size: 9px;
            color: #333;
            margin: 4px 0;
        }

        .plt-table {
            width: 100%;
            border: 1px solid #888;
            margin-top: 5px;
        }

        .plt-table th {
            background: #2d2040;
            color: #ffffff;
            padding: 4px 3px;
            font-size: 6.5px;
            font-weight: bold;
            text-align: center;
            border-bottom: 2px solid #555;
            border-right: 1px solid #444;
        }

        .plt-table th:last-child {
            border-right: none;
        }

        .col-header-sub {
            font-size: 6px;
            font-weight: normal;
            color: #ccc;
            display: block;
        }

        .group-header {
            background: #3d3050;
            color: #ffffff;
            padding: 3px 3px;
            font-size: 6.5px;
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
            padding: 3px 3px;
            font-size: 7px;
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
            padding: 5px 3px;
            font-weight: bold;
            font-size: 7.5px;
            color: #ffffff;
            border-right: 1px solid #444;
        }

        .total-row td:last-child {
            border-right: none;
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
                <p class="company-detail" style="font-weight: bold;">Образец "ПЛТ"</p>
                <p class="company-detail">Правилник Сл. весник 51/04; 89/04</p>
            </td>
        </tr>
    </table>

    <p class="heading-text">ПРИЕМЕН ЛИСТ ВО ТРГОВИЈАТА НА МАЛО</p>

    {{-- Document info --}}
    <table style="width: 100%; margin: 6px 0;">
        <tr>
            <td style="width: 50%;">
                <p class="doc-info">Број на документ: <strong>{{ $bill->bill_number ?? '' }}</strong></p>
                <p class="doc-info">Датум: <strong>{{ $bill_date ?? '' }}</strong></p>
            </td>
            <td style="width: 50%;">
                <p class="doc-info">Добавувач: <strong>{{ $supplier_name ?? '' }}</strong></p>
                <p class="doc-info">Седиште: <strong>{{ $supplier_address ?? '' }}</strong></p>
            </td>
        </tr>
    </table>

    <table class="plt-table">
        <thead>
            {{-- Group header row --}}
            <tr>
                <th rowspan="2" style="width: 3%;">
                    Р.бр.
                    <span class="col-header-sub">1</span>
                </th>
                <th rowspan="2" style="width: 16%;">
                    Назив на<br>стоките
                    <span class="col-header-sub">2</span>
                </th>
                <th rowspan="2" style="width: 5%;">
                    Ед.<br>мера
                    <span class="col-header-sub">3</span>
                </th>
                <th rowspan="2" style="width: 5%;">
                    Кол.
                    <span class="col-header-sub">4</span>
                </th>
                <th class="group-header" colspan="2">
                    Набавна вредност на стоките
                </th>
                <th class="group-header" colspan="2">
                    Стапка на ДДВ
                </th>
                <th class="group-header" colspan="2">
                    Продажна вредност на стоките
                </th>
                <th rowspan="2" style="width: 8%;">
                    Вкупен ДДВ<br>во продажна<br>вредност
                    <span class="col-header-sub">11</span>
                </th>
            </tr>
            <tr>
                <th style="width: 7%;">
                    Единечна<br>цена
                    <span class="col-header-sub">5</span>
                </th>
                <th style="width: 9%;">
                    Износ<br>(4×5)
                    <span class="col-header-sub">6</span>
                </th>
                <th style="width: 8%;">
                    ДДВ при<br>набавка<br>(6×8)
                    <span class="col-header-sub">7</span>
                </th>
                <th style="width: 5%;">
                    Стапка<br>%
                    <span class="col-header-sub">8</span>
                </th>
                <th style="width: 7%;">
                    Единечна<br>цена
                    <span class="col-header-sub">9</span>
                </th>
                <th style="width: 9%;">
                    Износ<br>(4×9)
                    <span class="col-header-sub">10</span>
                </th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalNabavna = 0;
                $totalNabavnaVat = 0;
                $totalProdazhna = 0;
                $totalProdazhnaVat = 0;
            @endphp

            @foreach($items as $i => $item)
            @php
                $qty = $item['quantity'] ?? 0;
                $unitPriceNabavna = $item['unit_price'] ?? 0;
                $nabavnaIznos = $item['nabavna_iznos'] ?? 0;
                $vatAmount = $item['vat_amount'] ?? 0;
                $vatRate = $item['vat_rate'] ?? 0;
                $unitPriceProdazhna = $item['unit_price_prodazhna'] ?? 0;
                $prodazhnaIznos = $item['prodazhna_iznos'] ?? 0;
                $prodazhnaVat = $item['prodazhna_vat'] ?? 0;

                $totalNabavna += $nabavnaIznos;
                $totalNabavnaVat += $vatAmount;
                $totalProdazhna += $prodazhnaIznos;
                $totalProdazhnaVat += $prodazhnaVat;
            @endphp
            <tr class="entry-row">
                <td class="cell-center">{{ $i + 1 }}</td>
                <td>{{ $item['name'] ?? '' }}</td>
                <td class="cell-center">{{ $item['unit'] ?? 'ком.' }}</td>
                <td class="cell-center">{{ number_format($qty, $qty == floor($qty) ? 0 : 2) }}</td>
                <td class="cell-number">{!! format_money_pdf($unitPriceNabavna, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($nabavnaIznos, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($vatAmount, $currency) !!}</td>
                <td class="cell-center">{{ $vatRate > 0 ? number_format($vatRate, $vatRate == floor($vatRate) ? 0 : 2) . '%' : '-' }}</td>
                <td class="cell-number">{!! format_money_pdf($unitPriceProdazhna, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($prodazhnaIznos, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($prodazhnaVat, $currency) !!}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" style="text-align: left;">ВКУПНО</td>
                <td class="cell-number">{!! format_money_pdf($totalNabavna, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($totalNabavnaVat, $currency) !!}</td>
                <td></td>
                <td></td>
                <td class="cell-number">{!! format_money_pdf($totalProdazhna, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($totalProdazhnaVat, $currency) !!}</td>
            </tr>
        </tfoot>
    </table>

    <p class="form-ref">Образец "ПЛТ" — Приемен лист во трговијата на мало / Правилник за евиденција Сл. весник 51/04; 89/04</p>

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
