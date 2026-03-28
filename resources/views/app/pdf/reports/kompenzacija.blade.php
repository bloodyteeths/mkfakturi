<!DOCTYPE html>
<html lang="mk">
<head>
    <title>Компензација</title>
    <style type="text/css">
        body { font-family: "DejaVu Sans"; font-size: 9px; color: #333; margin: 15px; }
        h1 { font-size: 14px; text-align: center; margin-bottom: 3px; }
        .subtitle { font-size: 10px; text-align: center; color: #666; margin-bottom: 15px; }
        .parties { width: 100%; margin-bottom: 12px; }
        .parties td { width: 50%; vertical-align: top; padding: 5px; }
        .party-box { border: 1px solid #ccc; padding: 8px; background: #f9f9f9; }
        .party-box .label { font-size: 7px; color: #888; text-transform: uppercase; }
        .party-box .value { font-size: 10px; font-weight: bold; }
        .items-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .items-table th { background: #2d3748; color: #fff; padding: 5px 8px; font-size: 8px; }
        .items-table td { padding: 4px 8px; border-bottom: 1px solid #e2e8f0; font-size: 8px; }
        .items-table .amount { text-align: right; }
        .total-row { font-weight: bold; background: #edf2f7; border-top: 2px solid #2d3748; }
        .summary-block { margin: 15px 0; padding: 10px; border: 2px solid #2d3748; text-align: center; }
        .summary-block .amount { font-size: 14px; font-weight: bold; }
        .legal-text { font-size: 8px; color: #555; margin: 15px 0; padding: 8px; background: #f0f4f8; border: 1px solid #cbd5e0; }
        .signatures { width: 100%; margin-top: 30px; }
        .signatures td { width: 50%; padding: 10px; vertical-align: bottom; }
        .sig-line { border-top: 1px solid #333; margin-top: 40px; text-align: center; font-size: 8px; padding-top: 3px; }
        .footer { font-size: 7px; color: #888; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <h1>ИЗЈАВА ЗА КОМПЕНЗАЦИЈА</h1>
    <p class="subtitle">Бр. {{ $document_number ?? '-' }} — Датум: {{ $date ?? now()->format('d.m.Y') }}</p>

    <table class="parties">
        <tr>
            <td>
                <div class="party-box">
                    <div class="label">Страна А (Издавач)</div>
                    <div class="value">{{ $party_a_name ?? '' }}</div>
                    <div>ЕДБ: {{ $party_a_vat ?? '-' }}</div>
                    <div>ЕМБС: {{ $party_a_tax_id ?? '-' }}</div>
                    <div>{{ $party_a_address ?? '' }}</div>
                    <div>Сметка: {{ $party_a_account ?? '-' }}</div>
                </div>
            </td>
            <td>
                <div class="party-box">
                    <div class="label">Страна Б</div>
                    <div class="value">{{ $party_b_name ?? '' }}</div>
                    <div>ЕДБ: {{ $party_b_vat ?? '-' }}</div>
                    <div>ЕМБС: {{ $party_b_tax_id ?? '-' }}</div>
                    <div>{{ $party_b_address ?? '' }}</div>
                    <div>Сметка: {{ $party_b_account ?? '-' }}</div>
                </div>
            </td>
        </tr>
    </table>

    <h3 style="font-size: 10px;">Побарувања на Страна А (фактури издадени на Страна Б)</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>Бр.</th>
                <th>Документ</th>
                <th>Датум</th>
                <th>Доспева</th>
                <th class="amount">Износ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($receivables as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item['document_number'] ?? '-' }}</td>
                <td>{{ $item['date'] ?? '-' }}</td>
                <td>{{ $item['due_date'] ?? '-' }}</td>
                <td class="amount">{{ number_format($item['amount'] ?? 0, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4">Вкупно побарувања на Страна А</td>
                <td class="amount">{{ number_format($total_receivables ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <h3 style="font-size: 10px;">Обврски на Страна А (фактури примени од Страна Б)</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>Бр.</th>
                <th>Документ</th>
                <th>Датум</th>
                <th>Доспева</th>
                <th class="amount">Износ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payables as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item['document_number'] ?? '-' }}</td>
                <td>{{ $item['date'] ?? '-' }}</td>
                <td>{{ $item['due_date'] ?? '-' }}</td>
                <td class="amount">{{ number_format($item['amount'] ?? 0, 2) }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4">Вкупно обврски на Страна А</td>
                <td class="amount">{{ number_format($total_payables ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="summary-block">
        <div>ИЗНОС НА КОМПЕНЗАЦИЈА</div>
        <div class="amount">{{ number_format($compensation_amount ?? 0, 2) }} {{ $currency ?? 'МКД' }}</div>
        @if(($total_receivables ?? 0) != ($total_payables ?? 0))
        <div style="font-size: 8px; color: #666; margin-top: 5px;">
            Разлика (остаток): {{ number_format(abs(($total_receivables ?? 0) - ($total_payables ?? 0)), 2) }} {{ $currency ?? 'МКД' }}
            во корист на {{ ($total_receivables ?? 0) > ($total_payables ?? 0) ? 'Страна А' : 'Страна Б' }}
        </div>
        @endif
    </div>

    <div class="legal-text">
        Со оваа изјава, двете страни се согласуваат за взаемна компензација на побарувањата и обврските
        наведени погоре, во согласност со чл. 311-320 од Законот за облигационите односи на РСМ.
        Компензацијата се врши на денот на потпишување на оваа изјава.
    </div>

    <table class="signatures">
        <tr>
            <td>
                <p>За Страна А:</p>
                <div class="sig-line">Потпис и печат</div>
            </td>
            <td>
                <p>За Страна Б:</p>
                <div class="sig-line">Потпис и печат</div>
            </td>
        </tr>
    </table>

    <p class="footer">Генерирано од Facturino — app.facturino.mk</p>
</body>
</html>
