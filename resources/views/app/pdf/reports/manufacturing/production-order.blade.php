<!DOCTYPE html>
<html lang="mk">
<head>
    <title>Работен налог {{ $order->order_number }}</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
            color: #333;
            margin: 15px;
        }
        h1 { font-size: 16px; margin: 0 0 5px 0; }
        h2 { font-size: 12px; margin: 15px 0 5px 0; color: #444; }
        .header-table { width: 100%; margin-bottom: 15px; }
        .header-table td { vertical-align: top; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info-table td { padding: 3px 8px; font-size: 9px; }
        .info-table .label { color: #666; width: 140px; }
        .info-table .value { font-weight: bold; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .data-table th {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            font-size: 8px;
            text-align: left;
            font-weight: bold;
        }
        .data-table td {
            border: 1px solid #d1d5db;
            padding: 3px 6px;
            font-size: 8px;
        }
        .data-table .num { text-align: right; }
        .cost-summary { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .cost-summary td { padding: 4px 8px; font-size: 9px; }
        .cost-summary .total { font-weight: bold; font-size: 11px; border-top: 2px solid #333; }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 3px;
        }
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-in_progress { background: #dbeafe; color: #1e40af; }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }
        .footer { margin-top: 30px; font-size: 8px; color: #999; }
        .signatures { width: 100%; margin-top: 40px; }
        .signatures td { width: 33%; text-align: center; padding-top: 30px; border-top: 1px solid #999; font-size: 8px; }
    </style>
</head>
<body>
    @include('app.pdf.reports._company-header')

    <h1>РАБОТЕН НАЛОГ</h1>
    <p style="font-size: 10px; margin: 0 0 10px 0;">Бр. {{ $order->order_number }}</p>

    {{-- Order Info --}}
    <table class="info-table">
        <tr>
            <td class="label">Датум на налог:</td>
            <td class="value">{{ $order->order_date?->format('d.m.Y') }}</td>
            <td class="label">Статус:</td>
            <td>
                <span class="status-badge status-{{ $order->status }}">
                    @switch($order->status)
                        @case('draft') Нацрт @break
                        @case('in_progress') Во производство @break
                        @case('completed') Завршен @break
                        @case('cancelled') Откажан @break
                    @endswitch
                </span>
            </td>
        </tr>
        <tr>
            <td class="label">Норматив (BOM):</td>
            <td class="value">{{ $order->bom?->name }} ({{ $order->bom?->code }})</td>
            <td class="label">Очекувано завршување:</td>
            <td class="value">{{ $order->expected_completion_date?->format('d.m.Y') ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Готов производ:</td>
            <td class="value">{{ $order->outputItem?->name }}</td>
            <td class="label">Магацин:</td>
            <td class="value">{{ $order->outputWarehouse?->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Планирана количина:</td>
            <td class="value">{{ number_format((float) $order->planned_quantity, 2) }} {{ $order->outputItem?->unit?->name }}</td>
            <td class="label">Реализирана количина:</td>
            <td class="value">{{ $order->actual_quantity ? number_format((float) $order->actual_quantity, 2) : '-' }} {{ $order->outputItem?->unit?->name }}</td>
        </tr>
    </table>

    {{-- Materials --}}
    <h2>Материјали</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">Р.б.</th>
                <th style="width: 25%;">Материјал</th>
                <th style="width: 10%;">Ед. мерка</th>
                <th class="num" style="width: 12%;">Планирано</th>
                <th class="num" style="width: 12%;">Потрошено</th>
                <th class="num" style="width: 12%;">Утрасок</th>
                <th class="num" style="width: 12%;">Цена/ед.</th>
                <th class="num" style="width: 12%;">Вкупно</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->materials as $i => $material)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $material->item?->name }}</td>
                    <td>{{ $material->item?->unit?->name }}</td>
                    <td class="num">{{ number_format((float) $material->planned_quantity, 4) }}</td>
                    <td class="num">{{ number_format((float) $material->actual_quantity, 4) }}</td>
                    <td class="num">{{ number_format((float) $material->wastage_quantity, 4) }}</td>
                    <td class="num">{{ number_format($material->actual_unit_cost / 100, 2) }}</td>
                    <td class="num">{{ number_format($material->actual_total_cost / 100, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Labor --}}
    @if($order->laborEntries->count())
    <h2>Труд</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">Р.б.</th>
                <th style="width: 35%;">Опис</th>
                <th class="num" style="width: 15%;">Часови</th>
                <th class="num" style="width: 15%;">Цена/час</th>
                <th class="num" style="width: 15%;">Датум</th>
                <th class="num" style="width: 15%;">Вкупно</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->laborEntries as $i => $labor)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $labor->description }}</td>
                    <td class="num">{{ number_format((float) $labor->hours, 2) }}</td>
                    <td class="num">{{ number_format($labor->rate_per_hour / 100, 2) }}</td>
                    <td class="num">{{ $labor->work_date ? \Carbon\Carbon::parse($labor->work_date)->format('d.m.Y') : '-' }}</td>
                    <td class="num">{{ number_format($labor->total_cost / 100, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Overhead --}}
    @if($order->overheadEntries->count())
    <h2>Режиски трошоци</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">Р.б.</th>
                <th style="width: 45%;">Опис</th>
                <th class="num" style="width: 20%;">Метод</th>
                <th class="num" style="width: 30%;">Износ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->overheadEntries as $i => $overhead)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $overhead->description }}</td>
                    <td class="num">{{ $overhead->allocation_method ?? '-' }}</td>
                    <td class="num">{{ number_format($overhead->amount / 100, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Cost Summary --}}
    <h2>Преглед на трошоци</h2>
    <table class="cost-summary">
        <tr>
            <td class="label">Директен материјал:</td>
            <td class="num" style="width: 120px;">{{ number_format($order->total_material_cost / 100, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Директен труд:</td>
            <td class="num">{{ number_format($order->total_labor_cost / 100, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Режиски трошоци:</td>
            <td class="num">{{ number_format($order->total_overhead_cost / 100, 2) }}</td>
        </tr>
        <tr>
            <td class="label">Утрасок:</td>
            <td class="num">{{ number_format($order->total_wastage_cost / 100, 2) }}</td>
        </tr>
        <tr class="total">
            <td>ВКУПНО ЦЕНА НА ЧИНЕЊЕ:</td>
            <td class="num">{{ number_format($order->total_production_cost / 100, 2) }}</td>
        </tr>
        @if($order->actual_quantity > 0)
        <tr>
            <td class="label">Цена по единица:</td>
            <td class="num" style="font-weight: bold;">{{ number_format($order->cost_per_unit / 100, 2) }}</td>
        </tr>
        @endif
    </table>

    @if($order->notes)
    <h2>Забелешки</h2>
    <p style="font-size: 8px;">{{ $order->notes }}</p>
    @endif

    {{-- Signatures --}}
    <table class="signatures">
        <tr>
            <td>Изготвил</td>
            <td>Одобрил</td>
            <td>Примил</td>
        </tr>
        <tr>
            <td style="border-top: none; padding-top: 3px;">{{ $order->createdBy?->name ?? '_____________' }}</td>
            <td style="border-top: none; padding-top: 3px;">{{ $order->approvedBy?->name ?? '_____________' }}</td>
            <td style="border-top: none; padding-top: 3px;">_____________</td>
        </tr>
    </table>

    <div class="footer">
        Документ генериран од Facturino &bull; {{ now()->format('d.m.Y H:i') }}
    </div>
</body>
</html>
