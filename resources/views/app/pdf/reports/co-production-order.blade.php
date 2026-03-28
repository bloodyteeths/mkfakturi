<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Сопроизводствен налог бр. {{ $order['order_number'] ?? '' }}</title>
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

        .order-info {
            width: 100%;
            margin: 8px 0;
            border: 1px solid #ccc;
        }

        .order-info td {
            padding: 4px 8px;
            font-size: 8px;
            border-bottom: 1px solid #eee;
        }

        .order-info .label {
            font-weight: bold;
            color: #555;
            width: 25%;
            background: #f8f8f8;
        }

        .order-info .value {
            width: 25%;
        }

        .section-title {
            font-weight: bold;
            font-size: 10px;
            color: #2d2040;
            margin: 12px 0 4px 0;
            padding: 3px 0;
            border-bottom: 2px solid #2d2040;
        }

        .data-table {
            width: 100%;
            border: 1px solid #888;
            margin-top: 3px;
        }

        .data-table th {
            background: #2d2040;
            color: #ffffff;
            padding: 5px 4px;
            font-size: 7.5px;
            font-weight: bold;
            text-align: center;
            border-bottom: 2px solid #555;
            border-right: 1px solid #444;
        }

        .data-table th:last-child {
            border-right: none;
        }

        .data-table td {
            padding: 3px 4px;
            font-size: 7.5px;
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

        .subtotal-row {
            background: #f0eff5;
            font-weight: bold;
        }

        .subtotal-row td {
            padding: 4px;
            font-size: 8px;
            border-top: 1px solid #999;
        }

        .total-row {
            background: #2d2040;
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

        .primary-badge {
            background: #c6f6d5;
            color: #276749;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 6.5px;
            font-weight: bold;
        }

        .byproduct-badge {
            background: #bee3f8;
            color: #2b6cb0;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 6.5px;
            font-weight: bold;
        }

        .waste-badge {
            background: #fed7d7;
            color: #c53030;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 6.5px;
            font-weight: bold;
        }

        .allocation-summary {
            width: 80%;
            border: 2px solid #2d2040;
            margin: 15px auto;
        }

        .allocation-summary td {
            padding: 5px 8px;
            font-size: 8.5px;
            border-bottom: 1px solid #ddd;
        }

        .allocation-summary .as-label {
            font-weight: bold;
            background: #f8f8f8;
            width: 40%;
        }

        .allocation-summary .as-value {
            text-align: right;
            width: 20%;
        }

        .allocation-header td {
            background: #2d2040;
            color: #ffffff;
            font-weight: bold;
            font-size: 8px;
            text-align: center;
        }

        .allocation-total td {
            background: #2d2040;
            color: #ffffff;
            font-weight: bold;
            font-size: 9px;
        }

        .bar-container {
            width: 100%;
            height: 12px;
            background: #e2e8f0;
        }

        .bar-fill-primary {
            height: 12px;
            background: #48bb78;
            display: inline-block;
        }

        .bar-fill-byproduct {
            height: 12px;
            background: #4299e1;
            display: inline-block;
        }

        .bar-fill-waste {
            height: 12px;
            background: #fc8181;
            display: inline-block;
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
            width: 180px;
            text-align: center;
        }

        .form-ref {
            font-size: 7px;
            color: #999;
            text-align: right;
            margin-top: 5px;
        }

        .method-label {
            display: inline-block;
            padding: 2px 6px;
            background: #edf2f7;
            border: 1px solid #cbd5e0;
            border-radius: 3px;
            font-size: 7.5px;
            font-weight: bold;
            color: #4a5568;
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
                <p class="company-detail">Сопроизводствен налог</p>
                <p class="company-detail">МСС 2 (IAS 2 — Залихи)</p>
                <p class="company-detail">Сл. весник 173/2022</p>
            </td>
        </tr>
    </table>

    <p class="heading-text">СОПРОИЗВОДСТВЕН НАЛОГ</p>
    <p class="sub-heading">Производство со повеќе излезни производи — Распределба на трошоци</p>

    {{-- Order Info --}}
    <table class="order-info">
        <tr>
            <td class="label">Работен налог бр.:</td>
            <td class="value"><strong>{{ $order['order_number'] ?? '' }}</strong></td>
            <td class="label">Датум:</td>
            <td class="value">{{ $order['completed_at'] ?? $order['order_date'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Норматив/БОМ:</td>
            <td class="value">{{ $order['bom_name'] ?? '—' }}</td>
            <td class="label">Метод на распределба:</td>
            <td class="value">
                @php
                    $methodLabels = [
                        'weight' => 'По тежина',
                        'market_value' => 'По пазарна вредност',
                        'fixed_ratio' => 'По фиксен сооднос',
                        'manual' => 'Рачно',
                    ];
                    $method = $order['allocation_method'] ?? 'market_value';
                @endphp
                <span class="method-label">{{ $methodLabels[$method] ?? $method }}</span>
            </td>
        </tr>
    </table>

    {{-- Section A — Input Materials --}}
    <p class="section-title">А. Суровини и материјали (влез)</p>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">Р.бр.</th>
                <th style="width: 10%;">Шифра</th>
                <th style="width: 25%;">Назив на материјал</th>
                <th style="width: 8%;">Ед. мерка</th>
                <th style="width: 10%;">Количина</th>
                <th style="width: 8%;">Утрасок</th>
                <th style="width: 12%;">Ед. цена</th>
                <th style="width: 13%;">Вкупна вредност</th>
            </tr>
        </thead>
        <tbody>
            @php $totalMaterialCost = 0; @endphp
            @foreach(($order['materials'] ?? []) as $i => $mat)
            @php $totalMaterialCost += $mat['actual_total_cost'] ?? 0; @endphp
            <tr>
                <td class="cell-center">{{ $i + 1 }}</td>
                <td>{{ $mat['sku'] ?? '' }}</td>
                <td>{{ $mat['item_name'] ?? '' }}</td>
                <td class="cell-center">{{ $mat['unit'] ?? '' }}</td>
                <td class="cell-number">{{ number_format($mat['actual_quantity'] ?? 0, 2, ',', '.') }}</td>
                <td class="cell-number">{{ number_format($mat['wastage_quantity'] ?? 0, 2, ',', '.') }}</td>
                <td class="cell-number">{!! format_money_pdf($mat['actual_unit_cost'] ?? 0, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($mat['actual_total_cost'] ?? 0, $currency) !!}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="subtotal-row">
                <td colspan="7" style="text-align: right;">Вкупно материјал:</td>
                <td class="cell-number">{!! format_money_pdf($totalMaterialCost, $currency) !!}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Cost Summary --}}
    @php
        $totalLabor = $order['total_labor_cost'] ?? 0;
        $totalOverhead = $order['total_overhead_cost'] ?? 0;
        $totalWastage = $order['total_wastage_cost'] ?? 0;
        $totalProductionCost = $totalMaterialCost + $totalLabor + $totalOverhead + $totalWastage;
    @endphp
    <table style="width: 50%; margin: 8px 0; border: 1px solid #ccc;">
        <tr>
            <td style="padding: 3px 8px; font-size: 8px; background: #f8f8f8; font-weight: bold;">Директен материјал:</td>
            <td style="padding: 3px 8px; font-size: 8px; text-align: right;">{!! format_money_pdf($totalMaterialCost, $currency) !!}</td>
        </tr>
        <tr>
            <td style="padding: 3px 8px; font-size: 8px; background: #f8f8f8; font-weight: bold;">Директен труд:</td>
            <td style="padding: 3px 8px; font-size: 8px; text-align: right;">{!! format_money_pdf($totalLabor, $currency) !!}</td>
        </tr>
        <tr>
            <td style="padding: 3px 8px; font-size: 8px; background: #f8f8f8; font-weight: bold;">Режиски трошоци:</td>
            <td style="padding: 3px 8px; font-size: 8px; text-align: right;">{!! format_money_pdf($totalOverhead, $currency) !!}</td>
        </tr>
        <tr>
            <td style="padding: 3px 8px; font-size: 8px; background: #f8f8f8; font-weight: bold;">Утрасок:</td>
            <td style="padding: 3px 8px; font-size: 8px; text-align: right;">{!! format_money_pdf($totalWastage, $currency) !!}</td>
        </tr>
        <tr style="background: #2d2040;">
            <td style="padding: 4px 8px; font-size: 9px; color: #fff; font-weight: bold;">ВКУПНО ЗА РАСПРЕДЕЛБА:</td>
            <td style="padding: 4px 8px; font-size: 9px; color: #fff; font-weight: bold; text-align: right;">{!! format_money_pdf($totalProductionCost, $currency) !!}</td>
        </tr>
    </table>

    {{-- Section B — Output Products / Cost Allocation --}}
    <p class="section-title">Б. Излезни производи — Распределба на трошоци ({{ $methodLabels[$method] ?? $method }})</p>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">Р.бр.</th>
                <th style="width: 20%;">Назив на производ</th>
                <th style="width: 8%;">Тип</th>
                <th style="width: 8%;">Количина</th>
                <th style="width: 7%;">Ед. мерка</th>
                @if($method === 'market_value')
                <th style="width: 10%;">Пазарна цена</th>
                <th style="width: 10%;">Пазарна вредност</th>
                @elseif($method === 'weight')
                <th style="width: 10%;">Тежина (кг)</th>
                <th style="width: 10%;">% од вкупно</th>
                @else
                <th style="width: 10%;">Сооднос</th>
                <th style="width: 10%;">% од вкупно</th>
                @endif
                <th style="width: 10%;">Процент %</th>
                <th style="width: 12%;">Распределен трошок</th>
                <th style="width: 11%;">Цена по единица</th>
            </tr>
        </thead>
        <tbody>
            @php $totalAllocated = 0; $totalPercent = 0; @endphp
            @foreach(($order['outputs'] ?? []) as $i => $out)
            @php
                $isPrimary = $out['is_primary'] ?? false;
                $isWaste = ($out['quantity'] ?? 0) == 0 || ($out['allocation_percent'] ?? 0) == 0;
                $allocatedCost = $out['allocated_cost'] ?? 0;
                $percent = $out['allocation_percent'] ?? 0;
                $costPerUnit = $out['cost_per_unit'] ?? 0;
                $totalAllocated += $allocatedCost;
                $totalPercent += $percent;
            @endphp
            <tr>
                <td class="cell-center">{{ $i + 1 }}</td>
                <td>
                    {{ $out['item_name'] ?? '' }}
                </td>
                <td class="cell-center">
                    @if($isWaste)
                        <span class="waste-badge">УТРАСОК</span>
                    @elseif($isPrimary)
                        <span class="primary-badge">ГЛАВЕН</span>
                    @else
                        <span class="byproduct-badge">СПОРЕДEН</span>
                    @endif
                </td>
                <td class="cell-number">{{ number_format($out['quantity'] ?? 0, 2, ',', '.') }}</td>
                <td class="cell-center">{{ $out['unit'] ?? '' }}</td>
                @if($method === 'market_value')
                <td class="cell-number">{!! format_money_pdf($out['market_price'] ?? 0, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf(($out['market_price'] ?? 0) * ($out['quantity'] ?? 0), $currency) !!}</td>
                @elseif($method === 'weight')
                <td class="cell-number">{{ number_format($out['weight'] ?? 0, 2, ',', '.') }}</td>
                <td class="cell-number">{{ number_format($percent, 1, ',', '.') }}%</td>
                @else
                <td class="cell-number">{{ $out['ratio'] ?? '' }}</td>
                <td class="cell-number">{{ number_format($percent, 1, ',', '.') }}%</td>
                @endif
                <td class="cell-number" style="font-weight: bold;">{{ number_format($percent, 1, ',', '.') }}%</td>
                <td class="cell-number" style="font-weight: bold;">{!! format_money_pdf($allocatedCost, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($costPerUnit, $currency) !!}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="{{ $method === 'market_value' || $method === 'weight' ? 7 : 7 }}" style="text-align: right;">
                    ВКУПНО РАСПРЕДЕЛЕНО:
                </td>
                <td class="cell-number">{{ number_format($totalPercent, 1, ',', '.') }}%</td>
                <td class="cell-number">{!! format_money_pdf($totalAllocated, $currency) !!}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    {{-- Verification --}}
    @php $unallocated = $totalProductionCost - $totalAllocated; @endphp
    @if(abs($unallocated) > 1)
    <p style="text-align: center; font-size: 8px; color: #c53030; margin-top: 5px;">
        ⚠ Нераспределен остаток: {!! format_money_pdf(abs($unallocated), $currency) !!}
        ({{ $unallocated > 0 ? 'нераспределен' : 'повеќе распределен' }})
    </p>
    @else
    <p style="text-align: center; font-size: 8px; color: #276749; margin-top: 5px;">
        ✓ Вкупниот трошок е целосно распределен по производи
    </p>
    @endif

    {{-- Section C — Per-Product Summary --}}
    <p class="section-title">В. Преглед на цена на чинење по производ</p>
    <table class="allocation-summary">
        <tr class="allocation-header">
            <td style="background: #2d2040; text-align: left;">Производ</td>
            <td style="background: #2d2040;">Количина</td>
            <td style="background: #2d2040;">Распределен трошок</td>
            <td style="background: #2d2040;">Цена на чинење / ед.</td>
        </tr>
        @foreach(($order['outputs'] ?? []) as $out)
        @if(($out['allocation_percent'] ?? 0) > 0)
        <tr>
            <td class="as-label">
                {{ $out['item_name'] ?? '' }}
                @if($out['is_primary'] ?? false)
                    <span class="primary-badge">ГЛАВЕН</span>
                @else
                    <span class="byproduct-badge">СПОРЕДEН</span>
                @endif
            </td>
            <td class="as-value">{{ number_format($out['quantity'] ?? 0, 2, ',', '.') }} {{ $out['unit'] ?? '' }}</td>
            <td class="as-value">{!! format_money_pdf($out['allocated_cost'] ?? 0, $currency) !!}</td>
            <td class="as-value" style="font-weight: bold;">{!! format_money_pdf($out['cost_per_unit'] ?? 0, $currency) !!}</td>
        </tr>
        @endif
        @endforeach
        <tr class="allocation-total">
            <td class="as-label" style="background: #2d2040; color: #fff;">ВКУПНО</td>
            <td class="as-value" style="background: #2d2040; color: #fff;"></td>
            <td class="as-value" style="background: #2d2040; color: #fff;">{!! format_money_pdf($totalAllocated, $currency) !!}</td>
            <td class="as-value" style="background: #2d2040; color: #fff;"></td>
        </tr>
    </table>

    <p class="form-ref">Сопроизводствен налог — Интерен документ / МСС 2 (IAS 2 — Залихи) / Сл. весник 173/2022</p>

    {{-- Signatures --}}
    <table class="signature-section">
        <tr>
            <td style="width: 33%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Изготвил</p>
            </td>
            <td style="width: 33%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Раководител на производство</p>
            </td>
            <td style="width: 33%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Одговорно лице</p>
            </td>
        </tr>
    </table>
</body>

</html>
