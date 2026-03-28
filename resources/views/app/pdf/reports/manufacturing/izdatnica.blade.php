<!DOCTYPE html>
<html lang="mk">
<head>
    <title>Издатница {{ $document_number }}</title>
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
        .footer { margin-top: 30px; font-size: 8px; color: #999; }
        .signatures { width: 100%; margin-top: 40px; }
        .signatures td { width: 33%; text-align: center; padding-top: 30px; border-top: 1px solid #999; font-size: 8px; }
    </style>
</head>
<body>
    @include('app.pdf.reports._company-header')

    <h1>ИЗДАТНИЦА</h1>
    <p style="font-size: 10px; margin: 0 0 10px 0;">Бр. {{ $document_number }}</p>

    {{-- Document Info --}}
    <table class="info-table">
        <tr>
            <td class="label">Датум:</td>
            <td class="value">{{ $date }}</td>
            <td class="label">Од магацин:</td>
            <td class="value">{{ $from_warehouse ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">До:</td>
            <td class="value">{{ $to_destination ?? 'Производство' }}</td>
            <td class="label">Работен налог:</td>
            <td class="value">{{ $order->order_number ?? '—' }}</td>
        </tr>
        @if(isset($requestor) && $requestor)
        <tr>
            <td class="label">Побарал:</td>
            <td class="value">{{ $requestor }}</td>
            <td></td>
            <td></td>
        </tr>
        @endif
        @if(isset($notes) && $notes)
        <tr>
            <td class="label">Забелешка:</td>
            <td class="value" colspan="3">{{ $notes }}</td>
        </tr>
        @endif
    </table>

    {{-- Items Table (8 columns per Правилник) --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">1. Р.бр</th>
                <th style="width: 60px;">2. Шифра</th>
                <th>3. Назив</th>
                <th style="width: 55px;">4. Ед. мерка</th>
                <th style="width: 65px;" class="num">5. Количина</th>
                <th style="width: 70px;" class="num">6. Цена (ден)</th>
                <th style="width: 80px;" class="num">7. Вредност (ден)</th>
                <th style="width: 70px;">8. Забелешка</th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @foreach($items as $i => $item)
                @php
                    $value = (int)($item['quantity'] * $item['unit_price']);
                    $grandTotal += $value;
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item['code'] ?? '—' }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['unit'] ?? '—' }}</td>
                    <td class="num">{{ number_format((float)$item['quantity'], 4) }}</td>
                    <td class="num">{{ number_format($item['unit_price'] / 100, 2) }}</td>
                    <td class="num">{{ number_format($value / 100, 2) }}</td>
                    <td>{{ $item['note'] ?? '' }}</td>
                </tr>
            @endforeach
            <tr style="font-weight: bold; background-color: #f9fafb;">
                <td colspan="6" style="text-align: right;">ВКУПНО:</td>
                <td class="num">{{ number_format($grandTotal / 100, 2) }} ден</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    {{-- Signatures --}}
    <table class="signatures">
        <tr>
            <td>Издал</td>
            <td>Примил</td>
            <td>Одобрил</td>
        </tr>
    </table>

    <div class="footer">
        Датум на печатење: {{ now()->format('d.m.Y H:i') }} | {{ $company->name }}
    </div>
</body>
</html>
