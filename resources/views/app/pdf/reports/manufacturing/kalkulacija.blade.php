<!DOCTYPE html>
<html lang="mk">
<head>
    <title>Калкулација {{ $order->order_number }}</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
            color: #333;
            margin: 15px;
        }
        h1 { font-size: 16px; margin: 0 0 5px 0; }
        h2 { font-size: 12px; margin: 15px 0 5px 0; color: #444; }
        .header-info { font-size: 9px; margin-bottom: 15px; }
        .header-info td { padding: 2px 8px; }
        .header-info .label { color: #666; width: 140px; }
        .header-info .value { font-weight: bold; }
        .calc-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .calc-table th {
            background-color: #1e40af;
            color: white;
            border: 1px solid #1e3a8a;
            padding: 5px 8px;
            font-size: 9px;
            text-align: left;
        }
        .calc-table td {
            border: 1px solid #d1d5db;
            padding: 4px 8px;
            font-size: 9px;
        }
        .calc-table .num { text-align: right; }
        .calc-table .subtotal {
            background-color: #f0f9ff;
            font-weight: bold;
        }
        .calc-table .grand-total {
            background-color: #1e40af;
            color: white;
            font-weight: bold;
            font-size: 10px;
        }
        .per-unit-box {
            border: 2px solid #1e40af;
            padding: 10px;
            margin-top: 15px;
            text-align: center;
        }
        .per-unit-box .amount {
            font-size: 18px;
            font-weight: bold;
            color: #1e40af;
        }
        .per-unit-box .label {
            font-size: 9px;
            color: #666;
        }
        .footer { margin-top: 30px; font-size: 8px; color: #999; }
        .signatures { width: 100%; margin-top: 40px; }
        .signatures td { width: 33%; text-align: center; padding-top: 30px; border-top: 1px solid #999; font-size: 8px; }
    </style>
</head>
<body>
    @include('app.pdf.reports._company-header')

    <h1>КАЛКУЛАЦИЈА НА ЦЕНА НА ЧИНЕЊЕ</h1>
    <p style="font-size: 10px; margin: 0 0 10px 0;">Работен налог: {{ $order->order_number }}</p>

    {{-- Header --}}
    <table class="header-info" style="width: 100%;">
        <tr>
            <td class="label">Готов производ:</td>
            <td class="value">{{ $order->outputItem?->name }}</td>
            <td class="label">Датум:</td>
            <td class="value">{{ $order->order_date?->format('d.m.Y') }}</td>
        </tr>
        <tr>
            <td class="label">Норматив:</td>
            <td class="value">{{ $order->bom?->name }}</td>
            <td class="label">Количина:</td>
            <td class="value">{{ number_format((float) ($order->actual_quantity ?: $order->planned_quantity), 2) }} {{ $order->outputItem?->unit?->name }}</td>
        </tr>
    </table>

    {{-- Cost Calculation Table --}}
    <table class="calc-table">
        <thead>
            <tr>
                <th style="width: 5%;">Р.б.</th>
                <th style="width: 35%;">Елемент на калкулација</th>
                <th class="num" style="width: 15%;">Количина</th>
                <th class="num" style="width: 15%;">Цена</th>
                <th class="num" style="width: 15%;">Вкупно</th>
                <th class="num" style="width: 15%;">По единица</th>
            </tr>
        </thead>
        <tbody>
            {{-- I. Material costs --}}
            <tr>
                <td colspan="6" style="font-weight: bold; background: #f9fafb;">I. ДИРЕКТЕН МАТЕРИЈАЛ</td>
            </tr>
            @php $rowNum = 1; $actualQty = (float) ($order->actual_quantity ?: $order->planned_quantity); @endphp
            @foreach($order->materials as $material)
                <tr>
                    <td>{{ $rowNum++ }}</td>
                    <td>{{ $material->item?->name }}</td>
                    <td class="num">{{ number_format((float) $material->actual_quantity, 4) }} {{ $material->item?->unit?->name }}</td>
                    <td class="num">{{ number_format($material->actual_unit_cost / 100, 2) }}</td>
                    <td class="num">{{ number_format($material->actual_total_cost / 100, 2) }}</td>
                    <td class="num">{{ $actualQty > 0 ? number_format(($material->actual_total_cost / $actualQty) / 100, 2) : '-' }}</td>
                </tr>
            @endforeach
            <tr class="subtotal">
                <td colspan="4">Вкупно директен материјал:</td>
                <td class="num">{{ number_format($order->total_material_cost / 100, 2) }}</td>
                <td class="num">{{ $actualQty > 0 ? number_format(($order->total_material_cost / $actualQty) / 100, 2) : '-' }}</td>
            </tr>

            {{-- II. Labor costs --}}
            <tr>
                <td colspan="6" style="font-weight: bold; background: #f9fafb;">II. ДИРЕКТЕН ТРУД</td>
            </tr>
            @foreach($order->laborEntries as $labor)
                <tr>
                    <td>{{ $rowNum++ }}</td>
                    <td>{{ $labor->description }}</td>
                    <td class="num">{{ number_format((float) $labor->hours, 2) }} ч.</td>
                    <td class="num">{{ number_format($labor->rate_per_hour / 100, 2) }}</td>
                    <td class="num">{{ number_format($labor->total_cost / 100, 2) }}</td>
                    <td class="num">{{ $actualQty > 0 ? number_format(($labor->total_cost / $actualQty) / 100, 2) : '-' }}</td>
                </tr>
            @endforeach
            @if($order->laborEntries->isEmpty())
                <tr><td colspan="6" style="text-align: center; color: #999;">Нема евидентиран труд</td></tr>
            @endif
            <tr class="subtotal">
                <td colspan="4">Вкупно директен труд:</td>
                <td class="num">{{ number_format($order->total_labor_cost / 100, 2) }}</td>
                <td class="num">{{ $actualQty > 0 ? number_format(($order->total_labor_cost / $actualQty) / 100, 2) : '-' }}</td>
            </tr>

            {{-- III. Overhead --}}
            <tr>
                <td colspan="6" style="font-weight: bold; background: #f9fafb;">III. РЕЖИСКИ ТРОШОЦИ</td>
            </tr>
            @foreach($order->overheadEntries as $overhead)
                <tr>
                    <td>{{ $rowNum++ }}</td>
                    <td>{{ $overhead->description }}</td>
                    <td class="num">-</td>
                    <td class="num">-</td>
                    <td class="num">{{ number_format($overhead->amount / 100, 2) }}</td>
                    <td class="num">{{ $actualQty > 0 ? number_format(($overhead->amount / $actualQty) / 100, 2) : '-' }}</td>
                </tr>
            @endforeach
            @if($order->overheadEntries->isEmpty())
                <tr><td colspan="6" style="text-align: center; color: #999;">Нема режиски трошоци</td></tr>
            @endif
            <tr class="subtotal">
                <td colspan="4">Вкупно режиски трошоци:</td>
                <td class="num">{{ number_format($order->total_overhead_cost / 100, 2) }}</td>
                <td class="num">{{ $actualQty > 0 ? number_format(($order->total_overhead_cost / $actualQty) / 100, 2) : '-' }}</td>
            </tr>

            {{-- IV. Wastage --}}
            @if($order->total_wastage_cost > 0)
            <tr>
                <td colspan="6" style="font-weight: bold; background: #f9fafb;">IV. УТРАСОК</td>
            </tr>
            <tr>
                <td>{{ $rowNum++ }}</td>
                <td>Утрасок во производство</td>
                <td class="num">-</td>
                <td class="num">-</td>
                <td class="num">{{ number_format($order->total_wastage_cost / 100, 2) }}</td>
                <td class="num">{{ $actualQty > 0 ? number_format(($order->total_wastage_cost / $actualQty) / 100, 2) : '-' }}</td>
            </tr>
            @endif

            {{-- Grand Total --}}
            <tr class="grand-total">
                <td colspan="4">ВКУПНО ЦЕНА НА ЧИНЕЊЕ:</td>
                <td class="num">{{ number_format($order->total_production_cost / 100, 2) }}</td>
                <td class="num">{{ $actualQty > 0 ? number_format(($order->total_production_cost / $actualQty) / 100, 2) : '-' }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Per-unit highlight --}}
    @if($actualQty > 0)
    <div class="per-unit-box">
        <div class="label">Цена на чинење по единица производ</div>
        <div class="amount">{{ number_format($order->cost_per_unit / 100, 2) }} МКД</div>
        <div class="label">за 1 {{ $order->outputItem?->unit?->name }} {{ $order->outputItem?->name }}</div>
    </div>
    @endif

    {{-- Variance --}}
    @if($order->status === 'completed' && ($order->material_variance || $order->labor_variance))
    <h2>Анализа на отстапување</h2>
    <table class="calc-table" style="width: 60%;">
        <tr>
            <td>Отстапување на материјал:</td>
            <td class="num" style="color: {{ $order->material_variance > 0 ? '#dc2626' : '#059669' }};">
                {{ $order->material_variance > 0 ? '+' : '' }}{{ number_format($order->material_variance / 100, 2) }}
            </td>
        </tr>
        <tr>
            <td>Отстапување на труд:</td>
            <td class="num" style="color: {{ $order->labor_variance > 0 ? '#dc2626' : '#059669' }};">
                {{ $order->labor_variance > 0 ? '+' : '' }}{{ number_format($order->labor_variance / 100, 2) }}
            </td>
        </tr>
        <tr style="font-weight: bold; border-top: 2px solid #333;">
            <td>Вкупно отстапување:</td>
            <td class="num" style="color: {{ $order->total_variance > 0 ? '#dc2626' : '#059669' }};">
                {{ $order->total_variance > 0 ? '+' : '' }}{{ number_format($order->total_variance / 100, 2) }}
            </td>
        </tr>
    </table>
    @endif

    {{-- Signatures --}}
    <table class="signatures">
        <tr>
            <td>Изготвил</td>
            <td>Контролирал</td>
            <td>Одобрил</td>
        </tr>
        <tr>
            <td style="border-top: none; padding-top: 3px;">{{ $order->createdBy?->name ?? '_____________' }}</td>
            <td style="border-top: none; padding-top: 3px;">_____________</td>
            <td style="border-top: none; padding-top: 3px;">{{ $order->approvedBy?->name ?? '_____________' }}</td>
        </tr>
    </table>

    <div class="footer">
        Документ генериран од Facturino &bull; {{ now()->format('d.m.Y H:i') }}
    </div>
</body>
</html>
