<!DOCTYPE html>
<html lang="mk">
<head>
    <title>Сопроизводствен налог {{ $order->order_number }}</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
            color: #333;
            margin: 15px;
        }
        h1 { font-size: 16px; margin: 0 0 5px 0; }
        h2 { font-size: 12px; margin: 15px 0 5px 0; color: #444; }
        .header-info { font-size: 9px; margin-bottom: 15px; width: 100%; }
        .header-info td { padding: 2px 8px; }
        .header-info .label { color: #666; width: 140px; }
        .header-info .value { font-weight: bold; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .data-table th {
            background-color: #7c3aed;
            color: white;
            border: 1px solid #6d28d9;
            padding: 5px 8px;
            font-size: 9px;
            text-align: left;
        }
        .data-table td {
            border: 1px solid #d1d5db;
            padding: 4px 8px;
            font-size: 9px;
        }
        .data-table .num { text-align: right; }
        .primary-badge {
            display: inline-block;
            padding: 1px 5px;
            background: #d1fae5;
            color: #065f46;
            font-size: 7px;
            font-weight: bold;
            border-radius: 2px;
        }
        .byproduct-badge {
            display: inline-block;
            padding: 1px 5px;
            background: #fef3c7;
            color: #92400e;
            font-size: 7px;
            font-weight: bold;
            border-radius: 2px;
        }
        .allocation-box {
            border: 1px solid #d1d5db;
            padding: 8px;
            margin-bottom: 15px;
            background: #f9fafb;
        }
        .cost-bar {
            height: 8px;
            background: #7c3aed;
            display: inline-block;
        }
        .total-row { font-weight: bold; background: #f3e8ff; }
        .footer { margin-top: 30px; font-size: 8px; color: #999; }
        .signatures { width: 100%; margin-top: 40px; }
        .signatures td { width: 33%; text-align: center; padding-top: 30px; border-top: 1px solid #999; font-size: 8px; }
    </style>
</head>
<body>
    @include('app.pdf.reports._company-header')

    <h1>СОПРОИЗВОДСТВЕН НАЛОГ</h1>
    <p style="font-size: 10px; margin: 0 0 10px 0;">Работен налог: {{ $order->order_number }}</p>

    {{-- Header --}}
    <table class="header-info">
        <tr>
            <td class="label">Датум:</td>
            <td class="value">{{ $order->order_date?->format('d.m.Y') }}</td>
            <td class="label">Завршен:</td>
            <td class="value">{{ $order->completed_at?->format('d.m.Y H:i') ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Норматив:</td>
            <td class="value">{{ $order->bom?->name }}</td>
            <td class="label">Вкупна цена на чинење:</td>
            <td class="value">{{ number_format($order->total_production_cost / 100, 2) }} МКД</td>
        </tr>
    </table>

    {{-- Co-production Outputs --}}
    <h2>Излезни производи и распределба на трошоци</h2>

    @php
        $primaryOutput = $order->coProductionOutputs->firstWhere('is_primary', true);
        $allocationMethod = $primaryOutput?->allocation_method ?? 'weight';
        $methodLabels = [
            'weight' => 'По тежина',
            'market_value' => 'По пазарна вредност',
            'fixed_ratio' => 'Фиксен однос',
            'manual' => 'Рачна распределба',
        ];
    @endphp

    <div class="allocation-box">
        <strong>Метод на распределба:</strong> {{ $methodLabels[$allocationMethod] ?? $allocationMethod }}
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">Р.б.</th>
                <th style="width: 20%;">Производ</th>
                <th style="width: 10%;">Тип</th>
                <th class="num" style="width: 12%;">Количина</th>
                <th class="num" style="width: 12%;">Распределба %</th>
                <th class="num" style="width: 15%;">Распределен трошок</th>
                <th class="num" style="width: 13%;">Цена/ед.</th>
                <th class="num" style="width: 13%;">Магацин</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->coProductionOutputs as $i => $output)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $output->item?->name }}</td>
                    <td>
                        @if($output->is_primary)
                            <span class="primary-badge">ГЛАВЕН</span>
                        @else
                            <span class="byproduct-badge">СПОРЕДЕН</span>
                        @endif
                    </td>
                    <td class="num">{{ number_format((float) $output->quantity, 2) }} {{ $output->item?->unit?->name }}</td>
                    <td class="num">{{ number_format((float) $output->allocation_percent, 2) }}%</td>
                    <td class="num">{{ number_format($output->allocated_cost / 100, 2) }}</td>
                    <td class="num">{{ number_format($output->cost_per_unit / 100, 2) }}</td>
                    <td class="num">{{ $output->warehouse?->name ?? $order->outputWarehouse?->name ?? '-' }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4">Вкупно:</td>
                <td class="num">{{ number_format($order->coProductionOutputs->sum('allocation_percent'), 2) }}%</td>
                <td class="num">{{ number_format($order->coProductionOutputs->sum('allocated_cost') / 100, 2) }}</td>
                <td colspan="2"></td>
            </tr>
        </tbody>
    </table>

    {{-- Input Materials Summary --}}
    <h2>Вложени материјали</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">Р.б.</th>
                <th style="width: 30%;">Материјал</th>
                <th class="num" style="width: 15%;">Количина</th>
                <th class="num" style="width: 15%;">Ед. мерка</th>
                <th class="num" style="width: 15%;">Цена/ед.</th>
                <th class="num" style="width: 20%;">Вкупно</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->materials as $i => $material)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $material->item?->name }}</td>
                    <td class="num">{{ number_format((float) $material->actual_quantity, 4) }}</td>
                    <td class="num">{{ $material->item?->unit?->name }}</td>
                    <td class="num">{{ number_format($material->actual_unit_cost / 100, 2) }}</td>
                    <td class="num">{{ number_format($material->actual_total_cost / 100, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Cost breakdown --}}
    <h2>Структура на трошоци</h2>
    <table class="data-table" style="width: 50%;">
        <tr>
            <td>Директен материјал:</td>
            <td class="num">{{ number_format($order->total_material_cost / 100, 2) }}</td>
        </tr>
        <tr>
            <td>Директен труд:</td>
            <td class="num">{{ number_format($order->total_labor_cost / 100, 2) }}</td>
        </tr>
        <tr>
            <td>Режиски трошоци:</td>
            <td class="num">{{ number_format($order->total_overhead_cost / 100, 2) }}</td>
        </tr>
        @if($order->total_wastage_cost > 0)
        <tr>
            <td>Утрасок:</td>
            <td class="num">{{ number_format($order->total_wastage_cost / 100, 2) }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td>ВКУПНО:</td>
            <td class="num">{{ number_format($order->total_production_cost / 100, 2) }} МКД</td>
        </tr>
    </table>

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
