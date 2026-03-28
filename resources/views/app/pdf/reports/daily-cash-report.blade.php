<!DOCTYPE html>
<html lang="mk">
<head>
    <title>Дневен извештај за каса</title>
    <style type="text/css">
        body { font-family: "DejaVu Sans"; font-size: 9px; color: #333; margin: 15px; }
        h1 { font-size: 14px; text-align: center; margin-bottom: 3px; }
        .subtitle { font-size: 10px; text-align: center; color: #666; margin-bottom: 15px; }
        .company-info { width: 100%; margin-bottom: 12px; }
        .company-info td { padding: 2px 5px; }
        .company-info .label { font-size: 7px; color: #888; }
        .company-info .value { font-size: 9px; font-weight: bold; }
        .summary-cards { width: 100%; margin-bottom: 12px; }
        .summary-cards td { width: 25%; padding: 8px; text-align: center; border: 1px solid #e2e8f0; }
        .summary-cards .card-label { font-size: 7px; color: #888; text-transform: uppercase; }
        .summary-cards .card-value { font-size: 12px; font-weight: bold; }
        .positive { color: #38a169; }
        .negative { color: #e53e3e; }
        .txn-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .txn-table th { background: #2d3748; color: #fff; padding: 5px 6px; font-size: 8px; text-align: left; }
        .txn-table td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; font-size: 8px; }
        .txn-table tr:nth-child(even) { background: #f7fafc; }
        .txn-table .amount { text-align: right; }
        .total-row { font-weight: bold; background: #edf2f7; border-top: 2px solid #2d3748; }
        .section-title { font-size: 10px; font-weight: bold; margin: 12px 0 5px; color: #2d3748; border-bottom: 1px solid #2d3748; padding-bottom: 3px; }
        .cash-summary { margin-top: 15px; width: 100%; border: 2px solid #2d3748; }
        .cash-summary td { padding: 6px 10px; }
        .cash-summary .label { width: 60%; }
        .cash-summary .value { width: 40%; text-align: right; font-weight: bold; }
        .cash-summary .final { font-size: 12px; background: #edf2f7; }
        .signatures { width: 100%; margin-top: 30px; }
        .signatures td { width: 33%; padding: 10px; text-align: center; vertical-align: bottom; }
        .sig-line { border-top: 1px solid #333; margin-top: 30px; font-size: 8px; padding-top: 3px; }
        .footer { font-size: 7px; color: #888; margin-top: 15px; text-align: center; }
    </style>
</head>
<body>
    <h1>ДНЕВЕН ИЗВЕШТАЈ ЗА КАСА</h1>
    <p class="subtitle">Датум: {{ $date ?? now()->format('d.m.Y') }}</p>

    <table class="company-info">
        <tr>
            <td><span class="label">Компанија:</span> <span class="value">{{ $company->name ?? '' }}</span></td>
            <td><span class="label">ЕДБ:</span> {{ $company->vat_number ?? '-' }}</td>
            <td><span class="label">Каса:</span> <span class="value">{{ $register_name ?? 'Главна каса' }}</span></td>
        </tr>
    </table>

    <table class="summary-cards">
        <tr>
            <td>
                <div class="card-label">Почетно салдо</div>
                <div class="card-value">{{ number_format($opening_balance ?? 0, 2) }}</div>
            </td>
            <td>
                <div class="card-label">Вкупно приходи</div>
                <div class="card-value positive">+{{ number_format($total_income ?? 0, 2) }}</div>
            </td>
            <td>
                <div class="card-label">Вкупно расходи</div>
                <div class="card-value negative">-{{ number_format($total_expense ?? 0, 2) }}</div>
            </td>
            <td>
                <div class="card-label">Крајно салдо</div>
                <div class="card-value">{{ number_format($closing_balance ?? 0, 2) }}</div>
            </td>
        </tr>
    </table>

    @if(count($income_items ?? []) > 0)
    <div class="section-title">Приходи (уплати)</div>
    <table class="txn-table">
        <thead>
            <tr>
                <th style="width: 5%;">Бр.</th>
                <th style="width: 10%;">Време</th>
                <th style="width: 15%;">Документ</th>
                <th style="width: 35%;">Опис</th>
                <th style="width: 15%;">Клиент</th>
                <th style="width: 10%;" class="amount">Износ</th>
                <th style="width: 10%;">Начин</th>
            </tr>
        </thead>
        <tbody>
            @foreach($income_items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item['time'] ?? '-' }}</td>
                <td>{{ $item['document'] ?? '-' }}</td>
                <td>{{ $item['description'] ?? '-' }}</td>
                <td>{{ $item['customer'] ?? '-' }}</td>
                <td class="amount positive">{{ number_format($item['amount'] ?? 0, 2) }}</td>
                <td>{{ $item['method'] ?? 'Готовина' }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5">Вкупно приходи</td>
                <td class="amount positive">{{ number_format($total_income ?? 0, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    @endif

    @if(count($expense_items ?? []) > 0)
    <div class="section-title">Расходи (исплати)</div>
    <table class="txn-table">
        <thead>
            <tr>
                <th style="width: 5%;">Бр.</th>
                <th style="width: 10%;">Време</th>
                <th style="width: 15%;">Документ</th>
                <th style="width: 35%;">Опис</th>
                <th style="width: 15%;">Примач</th>
                <th style="width: 10%;" class="amount">Износ</th>
                <th style="width: 10%;">Начин</th>
            </tr>
        </thead>
        <tbody>
            @foreach($expense_items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $item['time'] ?? '-' }}</td>
                <td>{{ $item['document'] ?? '-' }}</td>
                <td>{{ $item['description'] ?? '-' }}</td>
                <td>{{ $item['recipient'] ?? '-' }}</td>
                <td class="amount negative">{{ number_format($item['amount'] ?? 0, 2) }}</td>
                <td>{{ $item['method'] ?? 'Готовина' }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5">Вкупно расходи</td>
                <td class="amount negative">{{ number_format($total_expense ?? 0, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    @endif

    <table class="cash-summary">
        <tr><td class="label">Почетно салдо:</td><td class="value">{{ number_format($opening_balance ?? 0, 2) }} МКД</td></tr>
        <tr><td class="label">+ Приходи:</td><td class="value positive">{{ number_format($total_income ?? 0, 2) }} МКД</td></tr>
        <tr><td class="label">- Расходи:</td><td class="value negative">{{ number_format($total_expense ?? 0, 2) }} МКД</td></tr>
        <tr class="final"><td class="label">= Крајно салдо:</td><td class="value">{{ number_format($closing_balance ?? 0, 2) }} МКД</td></tr>
        @if(isset($counted_cash))
        <tr><td class="label">Избројана готовина:</td><td class="value">{{ number_format($counted_cash, 2) }} МКД</td></tr>
        <tr><td class="label">Разлика:</td><td class="value {{ ($counted_cash - ($closing_balance ?? 0)) == 0 ? '' : 'negative' }}">{{ number_format($counted_cash - ($closing_balance ?? 0), 2) }} МКД</td></tr>
        @endif
    </table>

    <table class="signatures">
        <tr>
            <td><div class="sig-line">Благајник</div></td>
            <td><div class="sig-line">Контролор</div></td>
            <td><div class="sig-line">Директор</div></td>
        </tr>
    </table>

    <p class="footer">Генерирано од Facturino — app.facturino.mk — {{ now()->format('d.m.Y H:i') }}</p>
</body>
</html>
