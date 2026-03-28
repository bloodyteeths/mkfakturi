<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Попис на залихи — {{ $warehouse_name ?? 'Сите магацини' }}</title>
    <style type="text/css">
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

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
            font-size: 14px;
            color: #1a1a1a;
            text-align: center;
            margin: 8px 0 2px 0;
        }

        .sub-heading {
            font-size: 10px;
            color: #555;
            text-align: center;
            margin: 0 0 10px 0;
        }

        .doc-info {
            width: 100%;
            margin: 8px 0;
            border: 1px solid #ccc;
        }

        .doc-info td {
            padding: 4px 8px;
            font-size: 8.5px;
            border-bottom: 1px solid #eee;
        }

        .doc-info .label {
            font-weight: bold;
            color: #555;
            width: 20%;
            background: #f8f8f8;
        }

        .doc-info .value {
            width: 30%;
        }

        .data-table {
            width: 100%;
            border: 1px solid #888;
            margin-top: 5px;
        }

        .data-table th {
            background: #2d2040;
            color: #ffffff;
            padding: 5px 3px;
            font-size: 6.5px;
            font-weight: bold;
            text-align: center;
            border-bottom: 2px solid #555;
            border-right: 1px solid #444;
        }

        .data-table th:last-child {
            border-right: none;
        }

        .col-header-sub {
            font-size: 5.5px;
            font-weight: normal;
            color: #ccc;
            display: block;
        }

        .data-table td {
            padding: 3px 3px;
            font-size: 7px;
            color: #333;
            border-right: 1px solid #eee;
            border-bottom: 1px solid #ddd;
        }

        .data-table td:last-child {
            border-right: none;
        }

        .cell-center {
            text-align: center;
        }

        .cell-number {
            text-align: right;
        }

        .cell-manual {
            background: #f5f5f5;
            min-height: 18px;
        }

        .entry-row:nth-child(even) {
            background: #fafafa;
        }

        .entry-row:nth-child(even) .cell-manual {
            background: #f0f0f0;
        }

        .total-row {
            background: #2d2040;
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

        .group-header {
            background: #3d3055;
            color: #ffffff;
            padding: 4px 3px;
            font-size: 6.5px;
            font-weight: bold;
            text-align: center;
            border-right: 2px solid #555;
            border-bottom: 1px solid #555;
        }

        .group-header:last-child {
            border-right: none;
        }

        .group-border-left {
            border-left: 2px solid #888;
        }

        .signature-section {
            margin-top: 25px;
            width: 100%;
        }

        .signature-label {
            font-size: 9px;
            color: #666;
            border-top: 1px solid #999;
            padding-top: 3px;
            width: 180px;
            text-align: center;
        }

        .form-ref {
            font-size: 6.5px;
            color: #999;
            text-align: right;
            margin-top: 5px;
        }

        .date-line {
            font-size: 9px;
            color: #555;
            margin-top: 15px;
            text-align: left;
        }

        .stamp-area {
            font-size: 8px;
            color: #999;
            border: 1px dashed #ccc;
            width: 100px;
            height: 80px;
            text-align: center;
            padding-top: 30px;
        }
    </style>
</head>

<body>
    {{-- Company Header --}}
    <table class="report-header">
        <tr>
            <td style="width: 65%;">
                <p class="company-name">{{ $company->name ?? '' }}</p>
                <p class="company-detail">
                    @if($company->address)
                        {{ $company->address->address_street_1 ?? '' }}
                        @if($company->address->city) , {{ $company->address->city }} @endif
                    @endif
                </p>
                <p class="company-detail">
                    @if($company->vat_number ?? null) ЕДБ: {{ $company->vat_number }} @endif
                    @if($company->tax_id ?? null) &nbsp;|&nbsp; ЕМБС: {{ $company->tax_id }} @endif
                </p>
            </td>
            <td style="width: 35%; text-align: right; vertical-align: top;">
                <p class="company-detail"><strong>Образец ПЗ</strong></p>
                <p class="company-detail">Закон за трговија</p>
                <p class="company-detail">Правилник за евиденција</p>
            </td>
        </tr>
    </table>

    <p class="heading-text">ПОПИС НА ЗАЛИХИ</p>
    <p class="sub-heading">Физички попис на залихи / Инвентарна листа</p>

    {{-- Document Info --}}
    <table class="doc-info">
        <tr>
            <td class="label">Датум на попис:</td>
            <td class="value"><strong>{{ $count_date ?? now()->format('d.m.Y') }}</strong></td>
            <td class="label">Магацин:</td>
            <td class="value"><strong>{{ $warehouse_name ?? 'Сите магацини' }}</strong></td>
        </tr>
        <tr>
            <td class="label">Комисија за попис:</td>
            <td class="value" colspan="3">
                1. ______________________ (Претседател) &nbsp;&nbsp;&nbsp;
                2. ______________________ (Член) &nbsp;&nbsp;&nbsp;
                3. ______________________ (Член)
            </td>
        </tr>
    </table>

    {{-- Items Table --}}
    <table class="data-table">
        <thead>
            {{-- Group Headers --}}
            <tr>
                <th rowspan="2" style="width: 3%;">
                    Р.бр.
                    <span class="col-header-sub">1</span>
                </th>
                <th rowspan="2" style="width: 6%;">
                    Шифра
                    <span class="col-header-sub">2</span>
                </th>
                <th rowspan="2" style="width: 16%;">
                    Назив на артикл
                    <span class="col-header-sub">3</span>
                </th>
                <th rowspan="2" style="width: 5%;">
                    Ед.<br>мерка
                    <span class="col-header-sub">4</span>
                </th>
                <th colspan="3" class="group-header">КНИГОВОДСТВЕНА СОСТОЈБА</th>
                <th colspan="3" class="group-header">ПОПИШАНА СОСТОЈБА</th>
                <th colspan="2" class="group-header">РАЗЛИКА (ВИШОК / КУСОК)</th>
            </tr>
            <tr>
                {{-- Книговодствена sub-headers --}}
                <th class="group-border-left" style="width: 7%;">
                    Количина
                    <span class="col-header-sub">5</span>
                </th>
                <th style="width: 7%;">
                    Цена
                    <span class="col-header-sub">6</span>
                </th>
                <th style="width: 9%;">
                    Вредност
                    <span class="col-header-sub">7</span>
                </th>
                {{-- Попишана sub-headers --}}
                <th class="group-border-left" style="width: 7%;">
                    Количина
                    <span class="col-header-sub">8</span>
                </th>
                <th style="width: 7%;">
                    Цена
                    <span class="col-header-sub">9</span>
                </th>
                <th style="width: 9%;">
                    Вредност
                    <span class="col-header-sub">10</span>
                </th>
                {{-- Разлика sub-headers --}}
                <th class="group-border-left" style="width: 7%;">
                    Количина
                    <span class="col-header-sub">11</span>
                </th>
                <th style="width: 9%;">
                    Вредност
                    <span class="col-header-sub">12</span>
                </th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalBookQty = 0;
                $totalBookValue = 0;
            @endphp

            @foreach(($items ?? []) as $i => $item)
            @php
                $qty = $item['quantity'] ?? 0;
                $unitCost = $item['unit_cost'] ?? 0;
                $bookValue = $item['total_value'] ?? 0;
                $totalBookQty += $qty;
                $totalBookValue += $bookValue;
            @endphp
            <tr class="entry-row">
                <td class="cell-center">{{ $i + 1 }}</td>
                <td>{{ $item['sku'] ?? '' }}</td>
                <td>{{ $item['name'] ?? '' }}</td>
                <td class="cell-center">{{ $item['unit'] ?? '' }}</td>
                {{-- Книговодствена состојба --}}
                <td class="cell-number group-border-left">{{ number_format($qty, 2, ',', '.') }}</td>
                <td class="cell-number">{!! format_money_pdf($unitCost, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($bookValue, $currency) !!}</td>
                {{-- Попишана состојба (blank for manual entry) --}}
                <td class="cell-number cell-manual group-border-left">&nbsp;</td>
                <td class="cell-number cell-manual">&nbsp;</td>
                <td class="cell-number cell-manual">&nbsp;</td>
                {{-- Разлика (blank for manual entry) --}}
                <td class="cell-number cell-manual group-border-left">&nbsp;</td>
                <td class="cell-number cell-manual">&nbsp;</td>
            </tr>
            @endforeach

            {{-- Empty rows for additional items --}}
            @for($e = 0; $e < 3; $e++)
            <tr class="entry-row">
                <td class="cell-center">{{ count($items ?? []) + $e + 1 }}</td>
                <td class="cell-manual">&nbsp;</td>
                <td class="cell-manual">&nbsp;</td>
                <td class="cell-manual">&nbsp;</td>
                <td class="cell-number cell-manual group-border-left">&nbsp;</td>
                <td class="cell-number cell-manual">&nbsp;</td>
                <td class="cell-number cell-manual">&nbsp;</td>
                <td class="cell-number cell-manual group-border-left">&nbsp;</td>
                <td class="cell-number cell-manual">&nbsp;</td>
                <td class="cell-number cell-manual">&nbsp;</td>
                <td class="cell-number cell-manual group-border-left">&nbsp;</td>
                <td class="cell-number cell-manual">&nbsp;</td>
            </tr>
            @endfor
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" style="text-align: right;">ВКУПНО ({{ count($items ?? []) }} артикли)</td>
                <td class="cell-number group-border-left">{{ number_format($totalBookQty, 2, ',', '.') }}</td>
                <td></td>
                <td class="cell-number">{!! format_money_pdf($totalBookValue, $currency) !!}</td>
                <td class="cell-number cell-manual group-border-left">&nbsp;</td>
                <td></td>
                <td class="cell-number cell-manual">&nbsp;</td>
                <td class="cell-number cell-manual group-border-left">&nbsp;</td>
                <td class="cell-number cell-manual">&nbsp;</td>
            </tr>
        </tfoot>
    </table>

    <p class="form-ref">Попис на залихи — Закон за трговија / Правилник за евиденција на залихи / ПСЦ метод (МСС 2)</p>

    {{-- Signatures --}}
    <table class="signature-section">
        <tr>
            <td colspan="4" style="font-size: 9px; font-weight: bold; color: #555; padding-bottom: 3px;">
                Комисија за попис:
            </td>
        </tr>
        <tr>
            <td style="width: 25%; text-align: center; padding-top: 35px;">
                <p class="signature-label">Претседател</p>
            </td>
            <td style="width: 25%; text-align: center; padding-top: 35px;">
                <p class="signature-label">Член</p>
            </td>
            <td style="width: 25%; text-align: center; padding-top: 35px;">
                <p class="signature-label">Член</p>
            </td>
            <td style="width: 25%; text-align: center; padding-top: 35px;">
                <p class="signature-label">Одговорно лице</p>
            </td>
        </tr>
    </table>

    <table style="width: 100%; margin-top: 15px;">
        <tr>
            <td style="width: 50%;">
                <p class="date-line">Датум: ___.___._______ год.</p>
            </td>
            <td style="width: 50%; text-align: right;">
                <div class="stamp-area">М.П.</div>
            </td>
        </tr>
    </table>
</body>

</html>
{{-- // CLAUDE-CHECKPOINT --}}
