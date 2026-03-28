<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Калкулација на големопродажна цена (Образец КАП)</title>
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

        .kap-table {
            width: 100%;
            border: 1px solid #888;
            margin-top: 5px;
        }

        .kap-table th {
            background: #2d2040;
            color: #ffffff;
            padding: 4px 3px;
            font-size: 6.5px;
            font-weight: bold;
            text-align: center;
            border-bottom: 2px solid #555;
            border-right: 1px solid #444;
        }

        .kap-table th:last-child {
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

        .dependent-costs {
            width: 100%;
            margin: 6px 0;
            border: 1px solid #ccc;
        }

        .dependent-costs td {
            padding: 3px 6px;
            font-size: 8px;
            border-bottom: 1px solid #eee;
        }

        .dependent-costs .dc-label {
            font-weight: bold;
            color: #555;
            width: 60%;
            background: #f8f8f8;
        }

        .dependent-costs .dc-value {
            text-align: right;
            width: 40%;
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
                <p class="company-detail" style="font-weight: bold;">Образец „КАП"</p>
                <p class="company-detail">Правилник Сл. весник 51/04; 89/04</p>
            </td>
        </tr>
    </table>

    <p class="heading-text">КАЛКУЛАЦИЈА НА ГОЛЕМОПРОДАЖНА ЦЕНА</p>

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

    {{-- Dependent Costs Summary --}}
    @if(!empty($dependent_costs) && array_sum($dependent_costs) > 0)
    <table class="dependent-costs">
        @foreach($dependent_costs as $costLabel => $costAmount)
        @if($costAmount > 0)
        <tr>
            <td class="dc-label">{{ $costLabel }}</td>
            <td class="dc-value">{!! format_money_pdf($costAmount, $currency) !!}</td>
        </tr>
        @endif
        @endforeach
        <tr>
            <td class="dc-label" style="font-weight: bold;">Вкупно зависни трошоци:</td>
            <td class="dc-value" style="font-weight: bold;">{!! format_money_pdf(array_sum($dependent_costs), $currency) !!}</td>
        </tr>
    </table>
    @endif

    <table class="kap-table">
        <thead>
            {{-- Group header row --}}
            <tr>
                <th rowspan="2" style="width: 3%;">
                    Р.бр.
                    <span class="col-header-sub">1</span>
                </th>
                <th rowspan="2" style="width: 14%;">
                    Назив на<br>стоките
                    <span class="col-header-sub">2</span>
                </th>
                <th rowspan="2" style="width: 4%;">
                    Ед.<br>мера
                    <span class="col-header-sub">3</span>
                </th>
                <th rowspan="2" style="width: 5%;">
                    Кол.
                    <span class="col-header-sub">4</span>
                </th>
                <th class="group-header" colspan="2">
                    Фактурна вредност без ДДВ
                </th>
                <th rowspan="2" style="width: 7%;">
                    Зависни<br>трошоци
                    <span class="col-header-sub">7</span>
                </th>
                <th rowspan="2" style="width: 8%;">
                    Набавна<br>вредност<br>без ДДВ
                    <span class="col-header-sub">8 (6+7)</span>
                </th>
                <th class="group-header" colspan="2">
                    Маржа / Разлика во цена
                </th>
                <th class="group-header" colspan="2">
                    Големопродажна цена без ДДВ
                </th>
            </tr>
            <tr>
                <th style="width: 6%;">
                    Единечна<br>цена
                    <span class="col-header-sub">5</span>
                </th>
                <th style="width: 7%;">
                    Износ<br>(4×5)
                    <span class="col-header-sub">6</span>
                </th>
                <th style="width: 4%;">
                    %
                    <span class="col-header-sub">9</span>
                </th>
                <th style="width: 7%;">
                    Износ
                    <span class="col-header-sub">10</span>
                </th>
                <th style="width: 7%;">
                    Единечна<br>цена
                    <span class="col-header-sub">11</span>
                </th>
                <th style="width: 8%;">
                    Износ<br>(8+10)
                    <span class="col-header-sub">12</span>
                </th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalFakturna = 0;
                $totalZavisni = 0;
                $totalNabavna = 0;
                $totalMarzha = 0;
                $totalProdazhna = 0;
            @endphp

            @foreach($items as $i => $item)
            @php
                $qty = $item['quantity'] ?? 0;
                $unitPrice = $item['unit_price'] ?? 0;
                $fakturnaIznos = $item['fakturna_iznos'] ?? 0;
                $zavisniTroshoci = $item['zavisni_troshoci'] ?? 0;
                $nabavnaIznos = $item['nabavna_iznos'] ?? 0;
                $marzha = $item['marzha'] ?? 0;
                $marzhaPercent = $item['marzha_percent'] ?? 0;
                $unitPriceProdazhna = $item['unit_price_prodazhna'] ?? 0;
                $prodazhnaIznos = $item['prodazhna_iznos'] ?? 0;

                $totalFakturna += $fakturnaIznos;
                $totalZavisni += $zavisniTroshoci;
                $totalNabavna += $nabavnaIznos;
                $totalMarzha += $marzha;
                $totalProdazhna += $prodazhnaIznos;
            @endphp
            <tr class="entry-row">
                <td class="cell-center">{{ $i + 1 }}</td>
                <td>{{ $item['name'] ?? '' }}</td>
                <td class="cell-center">{{ $item['unit'] ?? 'ком.' }}</td>
                <td class="cell-center">{{ number_format($qty, $qty == floor($qty) ? 0 : 2) }}</td>
                <td class="cell-number">{!! format_money_pdf($unitPrice, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($fakturnaIznos, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($zavisniTroshoci, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($nabavnaIznos, $currency) !!}</td>
                <td class="cell-center">{{ number_format($marzhaPercent, $marzhaPercent == floor($marzhaPercent) ? 0 : 2) }}%</td>
                <td class="cell-number">{!! format_money_pdf($marzha, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($unitPriceProdazhna, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($prodazhnaIznos, $currency) !!}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" style="text-align: left;">ВКУПНО</td>
                <td class="cell-number">{!! format_money_pdf($totalFakturna, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($totalZavisni, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($totalNabavna, $currency) !!}</td>
                <td></td>
                <td class="cell-number">{!! format_money_pdf($totalMarzha, $currency) !!}</td>
                <td></td>
                <td class="cell-number">{!! format_money_pdf($totalProdazhna, $currency) !!}</td>
            </tr>
        </tfoot>
    </table>

    <p class="form-ref">Образец „КАП" — Калкулација на големопродажна цена / Правилник за евиденција Сл. весник 51/04; 89/04</p>

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
