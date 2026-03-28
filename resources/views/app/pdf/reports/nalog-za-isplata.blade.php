<!DOCTYPE html>
<html lang="mk">
<head>
    <title>Налог за исплата</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 10px;
            color: #333;
            margin: 15px;
        }
        table { border-collapse: collapse; width: 100%; }
        .header-table td { padding: 3px 0; font-size: 9px; }
        .title { font-size: 16px; font-weight: bold; text-align: center; margin: 15px 0 5px; }
        .subtitle { font-size: 11px; text-align: center; color: #666; margin-bottom: 15px; }
        .data-table { border: 1px solid #333; margin: 10px 0; }
        .data-table th { background: #e2e8f0; padding: 6px 8px; font-size: 9px; border: 1px solid #999; text-align: left; }
        .data-table td { padding: 6px 8px; font-size: 10px; border: 1px solid #ccc; }
        .amount-cell { text-align: right; font-weight: bold; }
        .total-row { background: #e2e8f0; font-weight: bold; }
        .total-row td { border-top: 2px solid #333; padding: 8px; }
        .signatures { margin-top: 40px; }
        .signatures td { width: 33%; text-align: center; padding-top: 30px; }
        .sig-line { border-top: 1px solid #666; font-size: 9px; color: #666; padding-top: 3px; display: inline-block; min-width: 150px; }
        .stamp-area { border: 1px dashed #ccc; height: 60px; width: 60px; margin: 0 auto 5px; }
        .info-row { display: flex; }
        .label { color: #888; font-size: 9px; }
        .value { font-weight: bold; }
    </style>
</head>
<body>
    @include('app.pdf.reports._company-header')

    <p class="title">НАЛОГ ЗА ИСПЛАТА</p>
    <p class="subtitle">Бр. {{ $expense->expense_number ?? 'N/A' }} / {{ \Carbon\Carbon::parse($expense->expense_date)->format('d.m.Y') }}</p>

    <table class="data-table">
        <tr>
            <th style="width: 35%;">Опис</th>
            <th style="width: 65%;">Детали</th>
        </tr>
        <tr>
            <td class="label">Датум на трошок</td>
            <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('d.m.Y') }}</td>
        </tr>
        <tr>
            <td class="label">Категорија</td>
            <td>{{ $expense->category->name ?? '-' }}</td>
        </tr>
        @if($expense->supplier)
        <tr>
            <td class="label">Добавувач</td>
            <td>{{ $expense->supplier->name }}</td>
        </tr>
        @endif
        @if($expense->invoice_number)
        <tr>
            <td class="label">Број на фактура</td>
            <td>{{ $expense->invoice_number }}</td>
        </tr>
        @endif
        @if($expense->customer)
        <tr>
            <td class="label">Клиент</td>
            <td>{{ $expense->customer->name }}</td>
        </tr>
        @endif
        @if($expense->paymentMethod)
        <tr>
            <td class="label">Начин на плаќање</td>
            <td>{{ $expense->paymentMethod->name }}</td>
        </tr>
        @endif
        @if($expense->notes)
        <tr>
            <td class="label">Опис / Белешки</td>
            <td>{{ $expense->notes }}</td>
        </tr>
        @endif
    </table>

    <!-- Financial Details -->
    <table class="data-table">
        <tr>
            <th colspan="2" style="text-align: center;">Финансиски детали</th>
        </tr>
        <tr>
            <td style="width: 60%;">Основица (без ДДВ)</td>
            <td class="amount-cell">{!! format_money_pdf($expense->tax_base ?? 0, $currency) !!}</td>
        </tr>
        <tr>
            <td>ДДВ ({{ $expense->vat_rate ?? 18 }}%)</td>
            <td class="amount-cell">{!! format_money_pdf($expense->vat_amount ?? 0, $currency) !!}</td>
        </tr>
        <tr class="total-row">
            <td>ВКУПНО ЗА ИСПЛАТА</td>
            <td class="amount-cell">{!! format_money_pdf($expense->amount, $currency) !!}</td>
        </tr>
        @if($expense->exchange_rate && $expense->exchange_rate != 1)
        <tr>
            <td class="label">Курс: {{ number_format($expense->exchange_rate, 4) }}</td>
            <td class="amount-cell label">Основен износ: {!! format_money_pdf($expense->base_amount, null) !!}</td>
        </tr>
        @endif
    </table>

    <!-- Signatures -->
    <table class="signatures">
        <tr>
            <td>
                <div class="stamp-area"></div>
                <div class="sig-line">Составил</div>
            </td>
            <td>
                <div class="stamp-area"></div>
                <div class="sig-line">Контролирал</div>
            </td>
            <td>
                <div class="stamp-area"></div>
                <div class="sig-line">Одобрил</div>
            </td>
        </tr>
    </table>
</body>
</html>

<!-- CLAUDE-CHECKPOINT -->
