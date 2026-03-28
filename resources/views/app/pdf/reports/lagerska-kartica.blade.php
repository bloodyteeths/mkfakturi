<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Лагерска картица — {{ $item_name ?? '' }}</title>
    <style type="text/css">
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        body {
            font-family: "DejaVu Sans";
            font-size: 7.5px;
            color: #333;
            margin: 10px;
        }

        table {
            border-collapse: collapse;
        }

        .report-header {
            width: 100%;
            margin-bottom: 3px;
        }

        .company-name {
            font-weight: bold;
            font-size: 10px;
        }

        .company-detail {
            font-size: 7.5px;
            color: #555;
        }

        .heading-text {
            font-weight: bold;
            font-size: 13px;
            color: #1a1a1a;
            text-align: center;
            margin: 5px 0 2px 0;
        }

        .sub-heading {
            font-size: 9px;
            color: #555;
            text-align: center;
            margin: 0 0 5px 0;
        }

        .item-info {
            width: 100%;
            margin: 5px 0;
            border: 1px solid #ccc;
        }

        .item-info td {
            padding: 3px 6px;
            font-size: 8px;
            border-bottom: 1px solid #eee;
        }

        .item-info .label {
            font-weight: bold;
            color: #555;
            width: 15%;
            background: #f8f8f8;
        }

        .item-info .value {
            width: 18%;
        }

        .kardex-table {
            width: 100%;
            border: 1px solid #888;
            margin-top: 3px;
        }

        .kardex-table th {
            background: #2d2040;
            color: #ffffff;
            padding: 3px 3px;
            font-size: 6.5px;
            font-weight: bold;
            text-align: center;
            border-right: 1px solid #444;
            border-bottom: 1px solid #555;
        }

        .kardex-table th:last-child {
            border-right: none;
        }

        .group-header {
            background: #3d3055;
            color: #ffffff;
            padding: 4px 3px;
            font-size: 7px;
            font-weight: bold;
            text-align: center;
            border-right: 2px solid #555;
            border-bottom: 1px solid #555;
        }

        .group-header:last-child {
            border-right: none;
        }

        .kardex-table td {
            padding: 2px 3px;
            font-size: 7px;
            color: #333;
            border-right: 1px solid #eee;
            border-bottom: 1px solid #ddd;
        }

        .kardex-table td:last-child {
            border-right: none;
        }

        .cell-center {
            text-align: center;
        }

        .cell-number {
            text-align: right;
            font-family: "DejaVu Sans";
        }

        .entry-row:nth-child(even) {
            background: #fafafa;
        }

        .credit-entry td {
            color: #276749;
        }

        .debit-entry td {
            color: #c53030;
        }

        .opening-row {
            background: #edf2f7;
            font-weight: bold;
        }

        .opening-row td {
            font-weight: bold;
        }

        .total-row {
            background: #2d2040;
        }

        .total-row td {
            padding: 4px 3px;
            font-weight: bold;
            font-size: 7.5px;
            color: #ffffff;
            border-right: 1px solid #444;
        }

        .total-row td:last-child {
            border-right: none;
        }

        .group-border-left {
            border-left: 2px solid #888;
        }

        .signature-section {
            margin-top: 20px;
            width: 100%;
        }

        .signature-label {
            font-size: 8px;
            color: #666;
            border-top: 1px solid #999;
            padding-top: 3px;
            width: 150px;
            text-align: center;
        }

        .form-ref {
            font-size: 6.5px;
            color: #999;
            text-align: right;
            margin-top: 3px;
        }
    </style>
</head>

<body>
    {{-- Company Header --}}
    <table class="report-header">
        <tr>
            <td style="width: 60%;">
                <p class="company-name">{{ $company->name ?? '' }}</p>
                <p class="company-detail">
                    @if($company->address)
                        {{ $company->address->address_street_1 ?? '' }}
                        @if($company->address->city) , {{ $company->address->city }} @endif
                    @endif
                </p>
            </td>
            <td style="width: 40%; text-align: right; vertical-align: top;">
                <p class="company-detail">Магацин: <strong>{{ $warehouse_name ?? 'Главен магацин' }}</strong></p>
                <p class="company-detail">
                    @if($company->vat_number) ЕДБ: {{ $company->vat_number }} @endif
                </p>
            </td>
        </tr>
    </table>

    <p class="heading-text">ЛАГЕРСКА КАРТИЦА / МАГАЦИНСКА КАРТИЦА</p>
    <p class="sub-heading">Евиденција на движење на залихи по артикл</p>

    {{-- Item Info --}}
    <table class="item-info">
        <tr>
            <td class="label">Шифра:</td>
            <td class="value"><strong>{{ $item_sku ?? '' }}</strong></td>
            <td class="label">Баркод:</td>
            <td class="value">{{ $item_barcode ?? '—' }}</td>
            <td class="label">Ед. мерка:</td>
            <td class="value">{{ $item_unit ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Назив:</td>
            <td class="value" colspan="3"><strong>{{ $item_name ?? '' }}</strong></td>
            <td class="label">Период:</td>
            <td class="value">{{ $from_date ?? '' }} — {{ $to_date ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Мин. залиха:</td>
            <td class="value">{{ $min_stock ?? '—' }}</td>
            <td class="label">Макс. залиха:</td>
            <td class="value">{{ $max_stock ?? '—' }}</td>
            <td class="label">Метод:</td>
            <td class="value">ПСЦ (Weighted Average Cost)</td>
        </tr>
    </table>

    {{-- Kardex Table --}}
    <table class="kardex-table">
        <thead>
            {{-- Group Headers --}}
            <tr>
                <th rowspan="2" style="width: 4%;">Р.бр.</th>
                <th rowspan="2" style="width: 7%;">Датум</th>
                <th rowspan="2" style="width: 14%;">Документ<br>(бр./тип)</th>
                <th colspan="3" class="group-header" style="width: 22%;">ВЛЕЗ (Прием)</th>
                <th colspan="3" class="group-header" style="width: 22%;">ИЗЛЕЗ (Издавање)</th>
                <th colspan="3" class="group-header" style="width: 22%;">САЛДО (Состојба)</th>
                <th rowspan="2" style="width: 9%;">Извор</th>
            </tr>
            <tr>
                {{-- Влез sub-headers --}}
                <th class="group-border-left">Кол.</th>
                <th>Цена</th>
                <th>Вредност</th>
                {{-- Излез sub-headers --}}
                <th class="group-border-left">Кол.</th>
                <th>Цена</th>
                <th>Вредност</th>
                {{-- Салдо sub-headers --}}
                <th class="group-border-left">Кол.</th>
                <th>Цена (ПСЦ)</th>
                <th>Вредност</th>
            </tr>
        </thead>
        <tbody>
            {{-- Opening Balance Row --}}
            @if(isset($opening_balance))
            <tr class="opening-row">
                <td class="cell-center">—</td>
                <td class="cell-center">{{ $from_date ?? '' }}</td>
                <td>Почетно салдо</td>
                <td class="cell-number group-border-left"></td>
                <td class="cell-number"></td>
                <td class="cell-number"></td>
                <td class="cell-number group-border-left"></td>
                <td class="cell-number"></td>
                <td class="cell-number"></td>
                <td class="cell-number group-border-left">{{ number_format($opening_balance['quantity'] ?? 0, 2, ',', '.') }}</td>
                <td class="cell-number">{!! format_money_pdf($opening_balance['wac'] ?? 0, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($opening_balance['value'] ?? 0, $currency) !!}</td>
                <td></td>
            </tr>
            @endif

            @php
                $totalInQty = 0; $totalInValue = 0;
                $totalOutQty = 0; $totalOutValue = 0;
                $sourceLabels = [
                    'bill_item' => 'Фактура-наб.',
                    'invoice_item' => 'Фактура-прод.',
                    'adjustment' => 'Корекција',
                    'initial' => 'Почетен',
                    'transfer_in' => 'Пренос-влез',
                    'transfer_out' => 'Пренос-излез',
                    'production_consume' => 'Производство',
                    'production_output' => 'Произведено',
                    'production_byproduct' => 'Сопроизвод',
                    'production_wastage' => 'Утрасок',
                    'inventory_document' => 'Инвентар',
                    'goods_receipt' => 'Приемница',
                ];
            @endphp

            @foreach(($movements ?? []) as $i => $mov)
            @php
                $qty = $mov['quantity'] ?? 0;
                $isIn = $qty > 0;
                $absQty = abs($qty);
                $unitCost = $mov['unit_cost'] ?? 0;
                $totalCost = $mov['total_cost'] ?? 0;
                $balQty = $mov['balance_quantity'] ?? 0;
                $balValue = $mov['balance_value'] ?? 0;
                $wac = $balQty > 0 ? round($balValue / $balQty) : 0;

                if ($isIn) {
                    $totalInQty += $absQty;
                    $totalInValue += $totalCost;
                } else {
                    $totalOutQty += $absQty;
                    $totalOutValue += abs($totalCost);
                }

                $rowClass = $isIn ? 'entry-row credit-entry' : 'entry-row debit-entry';
            @endphp
            <tr class="{{ $rowClass }}">
                <td class="cell-center">{{ $i + 1 }}</td>
                <td class="cell-center">{{ $mov['date'] ?? '' }}</td>
                <td style="font-size: 6.5px;">{{ $mov['document'] ?? '' }}</td>
                {{-- Влез --}}
                <td class="cell-number group-border-left">{{ $isIn ? number_format($absQty, 2, ',', '.') : '' }}</td>
                <td class="cell-number">{{ $isIn ? '' : '' }}{!! $isIn && $unitCost ? format_money_pdf($unitCost, $currency) : '' !!}</td>
                <td class="cell-number">{!! $isIn ? format_money_pdf($totalCost, $currency) : '' !!}</td>
                {{-- Излез --}}
                <td class="cell-number group-border-left">{{ !$isIn ? number_format($absQty, 2, ',', '.') : '' }}</td>
                <td class="cell-number">{!! !$isIn && $unitCost ? format_money_pdf($unitCost, $currency) : '' !!}</td>
                <td class="cell-number">{!! !$isIn ? format_money_pdf(abs($totalCost), $currency) : '' !!}</td>
                {{-- Салдо --}}
                <td class="cell-number group-border-left">{{ number_format($balQty, 2, ',', '.') }}</td>
                <td class="cell-number">{!! format_money_pdf($wac, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($balValue, $currency) !!}</td>
                {{-- Извор --}}
                <td class="cell-center" style="font-size: 6px;">{{ $sourceLabels[$mov['source_type'] ?? ''] ?? $mov['source_type'] ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2" style="text-align: left;">ВКУПНО</td>
                <td>{{ count($movements ?? []) }} записи</td>
                {{-- Влез totals --}}
                <td class="cell-number group-border-left">{{ number_format($totalInQty, 2, ',', '.') }}</td>
                <td></td>
                <td class="cell-number">{!! format_money_pdf($totalInValue, $currency) !!}</td>
                {{-- Излез totals --}}
                <td class="cell-number group-border-left">{{ number_format($totalOutQty, 2, ',', '.') }}</td>
                <td></td>
                <td class="cell-number">{!! format_money_pdf($totalOutValue, $currency) !!}</td>
                {{-- Closing balance --}}
                @php
                    $lastMov = end($movements) ?? [];
                    $closingQty = $lastMov['balance_quantity'] ?? 0;
                    $closingValue = $lastMov['balance_value'] ?? 0;
                    $closingWac = $closingQty > 0 ? round($closingValue / $closingQty) : 0;
                @endphp
                <td class="cell-number group-border-left">{{ number_format($closingQty, 2, ',', '.') }}</td>
                <td class="cell-number">{!! format_money_pdf($closingWac, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($closingValue, $currency) !!}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <p class="form-ref">Лагерска картица — Правилник за евиденции (Сл. весник 51/04) / ПСЦ метод (МСС 2)</p>

    {{-- Signatures --}}
    <table class="signature-section">
        <tr>
            <td style="width: 50%; text-align: center; padding-top: 30px;">
                <p class="signature-label">Магационер</p>
            </td>
            <td style="width: 50%; text-align: center; padding-top: 30px;">
                <p class="signature-label">Сметководител</p>
            </td>
        </tr>
    </table>
</body>

</html>
