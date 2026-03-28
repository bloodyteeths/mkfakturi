<!DOCTYPE html>
<html lang="mk">
<head>
    <title>Лагерска картица — {{ $item_name }}</title>
    <style type="text/css">
        @page { size: landscape; }
        body {
            font-family: "DejaVu Sans";
            font-size: 8px;
            color: #333;
            margin: 15px;
        }
        h1 { font-size: 14px; margin: 0 0 5px 0; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info-table td { padding: 3px 8px; font-size: 9px; }
        .info-table .label { color: #666; width: 120px; }
        .info-table .value { font-weight: bold; }
        .kardex { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .kardex th {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            padding: 3px 4px;
            font-size: 7px;
            text-align: center;
            font-weight: bold;
        }
        .kardex td {
            border: 1px solid #d1d5db;
            padding: 2px 4px;
            font-size: 7px;
            text-align: right;
        }
        .kardex td.text { text-align: left; }
        .kardex .group-header {
            background-color: #e5e7eb;
            font-weight: bold;
            text-align: center;
        }
        .row-in { background-color: #f0fdf4; }
        .row-out { background-color: #fef2f2; }
        .row-balance { font-weight: bold; background-color: #f8fafc; }
        .footer { margin-top: 20px; font-size: 8px; color: #999; }
        .signatures { width: 100%; margin-top: 30px; }
        .signatures td { width: 50%; text-align: center; padding-top: 25px; border-top: 1px solid #999; font-size: 8px; }
    </style>
</head>
<body>
    @include('app.pdf.reports._company-header')

    <h1>ЛАГЕРСКА КАРТИЦА</h1>

    {{-- Item Info --}}
    <table class="info-table">
        <tr>
            <td class="label">Артикл:</td>
            <td class="value">{{ $item_name }}</td>
            <td class="label">Шифра:</td>
            <td class="value">{{ $item_code ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Единица мерка:</td>
            <td class="value">{{ $unit_name ?? '—' }}</td>
            <td class="label">Магацин:</td>
            <td class="value">{{ $warehouse_name ?? 'Сите' }}</td>
        </tr>
        <tr>
            <td class="label">Период:</td>
            <td class="value">{{ $period_from ?? '—' }} — {{ $period_to ?? '—' }}</td>
            <td></td>
            <td></td>
        </tr>
    </table>

    {{-- Kardex Table (triple-grouped: ВЛЕЗ / ИЗЛЕЗ / САЛДО) --}}
    <table class="kardex">
        <thead>
            <tr>
                <th rowspan="2" style="width: 60px;">Датум</th>
                <th rowspan="2" style="width: 50px;">Документ</th>
                <th rowspan="2" style="width: 70px;" class="text">Опис</th>
                <th colspan="3" class="group-header" style="background-color: #d1fae5;">ВЛЕЗ</th>
                <th colspan="3" class="group-header" style="background-color: #fee2e2;">ИЗЛЕЗ</th>
                <th colspan="3" class="group-header" style="background-color: #dbeafe;">САЛДО</th>
            </tr>
            <tr>
                <th style="background-color: #d1fae5;">Кол.</th>
                <th style="background-color: #d1fae5;">Цена</th>
                <th style="background-color: #d1fae5;">Вредност</th>
                <th style="background-color: #fee2e2;">Кол.</th>
                <th style="background-color: #fee2e2;">Цена</th>
                <th style="background-color: #fee2e2;">Вредност</th>
                <th style="background-color: #dbeafe;">Кол.</th>
                <th style="background-color: #dbeafe;">ПСЦ</th>
                <th style="background-color: #dbeafe;">Вредност</th>
            </tr>
        </thead>
        <tbody>
            {{-- Opening Balance --}}
            @if(isset($opening_balance))
            <tr class="row-balance">
                <td class="text">{{ $period_from ?? '—' }}</td>
                <td class="text">—</td>
                <td class="text">Почетно салдо</td>
                <td>—</td><td>—</td><td>—</td>
                <td>—</td><td>—</td><td>—</td>
                <td>{{ number_format((float)($opening_balance['quantity'] ?? 0), 2) }}</td>
                <td>{{ number_format(($opening_balance['wac'] ?? 0) / 100, 2) }}</td>
                <td>{{ number_format(($opening_balance['value'] ?? 0) / 100, 2) }}</td>
            </tr>
            @endif

            {{-- Movements --}}
            @foreach($movements as $mv)
                @php
                    $isIn = (float)($mv['quantity'] ?? 0) > 0;
                    $absQty = abs((float)($mv['quantity'] ?? 0));
                    $unitPrice = ($mv['unit_price'] ?? 0);
                    $value = ($mv['value'] ?? 0);
                @endphp
                <tr class="{{ $isIn ? 'row-in' : 'row-out' }}">
                    <td class="text">{{ $mv['date'] ?? '' }}</td>
                    <td class="text">{{ $mv['document'] ?? '' }}</td>
                    <td class="text">{{ $mv['description'] ?? '' }}</td>
                    @if($isIn)
                        <td>{{ number_format($absQty, 2) }}</td>
                        <td>{{ number_format($unitPrice / 100, 2) }}</td>
                        <td>{{ number_format($value / 100, 2) }}</td>
                        <td>—</td><td>—</td><td>—</td>
                    @else
                        <td>—</td><td>—</td><td>—</td>
                        <td>{{ number_format($absQty, 2) }}</td>
                        <td>{{ number_format($unitPrice / 100, 2) }}</td>
                        <td>{{ number_format(abs($value) / 100, 2) }}</td>
                    @endif
                    <td>{{ number_format((float)($mv['balance_qty'] ?? 0), 2) }}</td>
                    <td>{{ number_format(($mv['balance_wac'] ?? 0) / 100, 2) }}</td>
                    <td>{{ number_format(($mv['balance_value'] ?? 0) / 100, 2) }}</td>
                </tr>
            @endforeach

            {{-- Closing Balance --}}
            @if(isset($closing_balance))
            <tr class="row-balance">
                <td class="text">{{ $period_to ?? '—' }}</td>
                <td class="text">—</td>
                <td class="text">Крајно салдо</td>
                <td>—</td><td>—</td><td>—</td>
                <td>—</td><td>—</td><td>—</td>
                <td>{{ number_format((float)($closing_balance['quantity'] ?? 0), 2) }}</td>
                <td>{{ number_format(($closing_balance['wac'] ?? 0) / 100, 2) }}</td>
                <td>{{ number_format(($closing_balance['value'] ?? 0) / 100, 2) }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    {{-- Signatures --}}
    <table class="signatures">
        <tr>
            <td>Магационер</td>
            <td>Сметководител</td>
        </tr>
    </table>

    <div class="footer">
        Датум на печатење: {{ now()->format('d.m.Y H:i') }} | {{ $company->name }}
    </div>
</body>
</html>
