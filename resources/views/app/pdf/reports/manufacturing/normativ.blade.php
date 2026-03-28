<!DOCTYPE html>
<html lang="mk">
<head>
    <title>Норматив {{ $bom->code }}</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
            color: #333;
            margin: 15px;
        }
        h1 { font-size: 16px; margin: 0 0 5px 0; }
        h2 { font-size: 12px; margin: 15px 0 5px 0; color: #444; }
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
        .cost-box {
            border: 2px solid #333;
            padding: 8px 12px;
            margin-top: 10px;
            width: 250px;
        }
        .cost-box .row { display: block; margin-bottom: 3px; font-size: 9px; }
        .cost-box .total { font-weight: bold; font-size: 11px; border-top: 1px solid #999; padding-top: 4px; margin-top: 4px; }
        .version-badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 3px;
            background: #dbeafe;
            color: #1e40af;
        }
        .active-badge {
            display: inline-block;
            padding: 2px 8px;
            font-size: 8px;
            font-weight: bold;
            border-radius: 3px;
        }
        .active { background: #d1fae5; color: #065f46; }
        .inactive { background: #fee2e2; color: #991b1b; }
        .footer { margin-top: 30px; font-size: 8px; color: #999; }
        .signatures { width: 100%; margin-top: 40px; }
        .signatures td { width: 50%; text-align: center; padding-top: 30px; border-top: 1px solid #999; font-size: 8px; }
    </style>
</head>
<body>
    @include('app.pdf.reports._company-header')

    <h1>НОРМАТИВ (БОМ)</h1>
    <p style="font-size: 10px; margin: 0 0 10px 0;">
        Код: {{ $bom->code }}
        <span class="version-badge">В.{{ $bom->version }}</span>
        <span class="active-badge {{ $bom->is_active ? 'active' : 'inactive' }}">
            {{ $bom->is_active ? 'Активен' : 'Неактивен' }}
        </span>
    </p>

    {{-- BOM Info --}}
    <table class="info-table">
        <tr>
            <td class="label">Назив:</td>
            <td class="value">{{ $bom->name }}</td>
            <td class="label">Готов производ:</td>
            <td class="value">{{ $bom->outputItem?->name }}</td>
        </tr>
        <tr>
            <td class="label">Излезна количина:</td>
            <td class="value">{{ number_format((float)$bom->output_quantity, 4) }} {{ $bom->outputUnit?->name }}</td>
            <td class="label">Нормативен утрасок:</td>
            <td class="value">{{ number_format((float)$bom->expected_wastage_percent, 2) }}%</td>
        </tr>
        <tr>
            <td class="label">Изготвил:</td>
            <td class="value">{{ $bom->createdBy?->name }}</td>
            <td class="label">Одобрил:</td>
            <td class="value">{{ $bom->approvedBy?->name ?? '—' }}</td>
        </tr>
        @if($bom->description)
        <tr>
            <td class="label">Опис:</td>
            <td class="value" colspan="3">{{ $bom->description }}</td>
        </tr>
        @endif
    </table>

    {{-- Material Lines --}}
    <h2>А. Суровини и материјали</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">Р.бр</th>
                <th>Назив на материјал</th>
                <th style="width: 55px;">Ед. мерка</th>
                <th style="width: 65px;" class="num">Количина</th>
                <th style="width: 50px;" class="num">Утрасок %</th>
                <th style="width: 70px;" class="num">Кол. со утрасок</th>
                <th style="width: 70px;" class="num">ПСЦ (ден)</th>
                <th style="width: 80px;" class="num">Вредност по ед.</th>
            </tr>
        </thead>
        <tbody>
            @php
                $outputQty = (float)$bom->output_quantity ?: 1;
                $totalMaterial = 0;
            @endphp
            @foreach($bom->lines as $i => $line)
                @php
                    $qtyPerUnit = (float)$line->quantity / $outputQty;
                    $wastageMultiplier = 1 + ((float)$line->wastage_percent / 100);
                    $adjustedQty = $qtyPerUnit * $wastageMultiplier;
                    $wac = $line->item ? ($line->item->wac ?? 0) : 0;
                    $lineValue = (int)round($adjustedQty * $wac);
                    $totalMaterial += $lineValue;
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $line->item?->name }}</td>
                    <td>{{ $line->unit?->name ?? $line->item?->unit?->name }}</td>
                    <td class="num">{{ number_format($qtyPerUnit, 4) }}</td>
                    <td class="num">{{ number_format((float)$line->wastage_percent, 2) }}%</td>
                    <td class="num">{{ number_format($adjustedQty, 4) }}</td>
                    <td class="num">{{ number_format($wac / 100, 2) }}</td>
                    <td class="num">{{ number_format($lineValue / 100, 2) }}</td>
                </tr>
            @endforeach
            <tr style="font-weight: bold; background-color: #f9fafb;">
                <td colspan="7" style="text-align: right;">Вкупно материјал по единица:</td>
                <td class="num">{{ number_format($totalMaterial / 100, 2) }} ден</td>
            </tr>
        </tbody>
    </table>

    {{-- Cost Summary --}}
    <h2>Б. Нормативна цена на чинење (по единица)</h2>
    <div class="cost-box">
        <span class="row">Директен материјал: {{ number_format($totalMaterial / 100, 2) }} ден</span>
        <span class="row">Директен труд: {{ number_format((int)$bom->labor_cost_per_unit / 100, 2) }} ден</span>
        <span class="row">Режиски трошоци: {{ number_format((int)$bom->overhead_cost_per_unit / 100, 2) }} ден</span>
        @php
            $totalPerUnit = $totalMaterial + (int)$bom->labor_cost_per_unit + (int)$bom->overhead_cost_per_unit;
        @endphp
        <span class="row total">НОРМАТИВНА ЦЕНА: {{ number_format($totalPerUnit / 100, 2) }} ден</span>
    </div>

    {{-- Signatures --}}
    <table class="signatures">
        <tr>
            <td>Изготвил</td>
            <td>Одобрил</td>
        </tr>
    </table>

    <div class="footer">
        Датум на печатење: {{ now()->format('d.m.Y H:i') }} | {{ $company->name }}
    </div>
</body>
</html>
