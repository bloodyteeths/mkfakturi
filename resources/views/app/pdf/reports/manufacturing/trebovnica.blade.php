<!DOCTYPE html>
<html lang="mk">
<head>
    <title>Требовница {{ $document_number }}</title>
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
        .mismatch { background-color: #fef3c7; }
        .footer { margin-top: 30px; font-size: 8px; color: #999; }
        .signatures { width: 100%; margin-top: 40px; }
        .signatures td { width: 33%; text-align: center; padding-top: 30px; border-top: 1px solid #999; font-size: 8px; }
    </style>
</head>
<body>
    @include('app.pdf.reports._company-header')

    <h1>ТРЕБОВНИЦА</h1>
    <p style="font-size: 10px; margin: 0 0 10px 0;">Бр. {{ $document_number }}</p>

    {{-- Document Info --}}
    <table class="info-table">
        <tr>
            <td class="label">Датум:</td>
            <td class="value">{{ $date }}</td>
            <td class="label">Работен налог:</td>
            <td class="value">{{ $order->order_number ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Побарал:</td>
            <td class="value">{{ $requestor ?? '—' }}</td>
            <td class="label">Одделение:</td>
            <td class="value">{{ $department ?? 'Производство' }}</td>
        </tr>
        @if(isset($notes) && $notes)
        <tr>
            <td class="label">Забелешка:</td>
            <td class="value" colspan="3">{{ $notes }}</td>
        </tr>
        @endif
    </table>

    {{-- Items Table (8 columns per ТОЗ обр. 0653) --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 30px;">1. Р.бр</th>
                <th style="width: 60px;">2. Шифра</th>
                <th>3. Назив</th>
                <th style="width: 55px;">4. Ед. мерка</th>
                <th style="width: 65px;" class="num">5. Побарана кол.</th>
                <th style="width: 65px;" class="num">6. Одобрена кол.</th>
                <th style="width: 65px;" class="num">7. Издадена кол.</th>
                <th style="width: 70px;">8. Забелешка</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $i => $item)
                @php
                    $mismatch = isset($item['approved_qty'], $item['issued_qty'])
                        && (float)$item['approved_qty'] !== (float)$item['issued_qty'];
                @endphp
                <tr class="{{ $mismatch ? 'mismatch' : '' }}">
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $item['code'] ?? '—' }}</td>
                    <td>{{ $item['name'] }}</td>
                    <td>{{ $item['unit'] ?? '—' }}</td>
                    <td class="num">{{ number_format((float)($item['requested_qty'] ?? 0), 4) }}</td>
                    <td class="num">{{ number_format((float)($item['approved_qty'] ?? 0), 4) }}</td>
                    <td class="num">{{ number_format((float)($item['issued_qty'] ?? 0), 4) }}</td>
                    <td>{{ $item['note'] ?? ($mismatch ? 'Разлика!' : '') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Signatures --}}
    <table class="signatures">
        <tr>
            <td>Побарал</td>
            <td>Одобрил</td>
            <td>Издал</td>
        </tr>
    </table>

    <div class="footer">
        Датум на печатење: {{ now()->format('d.m.Y H:i') }} | {{ $company->name }}
    </div>
</body>
</html>
