<!DOCTYPE html>
<html lang="mk">
<head>
    <title>Книга на примени фактури</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 8px;
            color: #333;
            margin: 15px;
        }
        table { border-collapse: collapse; width: 100%; }
        .title { font-size: 14px; font-weight: bold; text-align: center; margin: 10px 0 3px; }
        .subtitle { font-size: 10px; text-align: center; color: #666; margin-bottom: 10px; }
        .data-table { border: 1px solid #333; }
        .data-table th {
            background: #e2e8f0;
            padding: 4px 3px;
            font-size: 7px;
            font-weight: bold;
            border: 1px solid #999;
            text-align: center;
        }
        .data-table td {
            padding: 3px 4px;
            font-size: 8px;
            border: 1px solid #ccc;
        }
        .data-row:nth-child(even) { background: #f7fafc; }
        .num { text-align: center; }
        .amount { text-align: right; }
        .total-row { background: #e2e8f0; font-weight: bold; }
        .total-row td { border-top: 2px solid #333; padding: 5px 4px; font-size: 9px; }
        .footer-note { font-size: 8px; color: #666; margin-top: 10px; }
        .signatures { margin-top: 30px; }
        .signatures td { width: 50%; text-align: center; padding-top: 30px; }
        .sig-line { border-top: 1px solid #666; font-size: 9px; color: #666; padding-top: 3px; display: inline-block; min-width: 150px; }
    </style>
</head>
<body>
    @include('app.pdf.reports._company-header', ['report_period' => $from_date . ' - ' . $to_date])

    <p class="title">КНИГА НА ПРИМЕНИ ФАКТУРИ</p>
    <p class="subtitle">(Книга за влезен данок на додадена вредност)</p>
    <p class="subtitle">За период: {{ $from_date }} - {{ $to_date }}</p>

    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">Р.б.</th>
                <th style="width: 8%;">Датум</th>
                <th style="width: 8%;">Бр. факт.</th>
                <th style="width: 18%;">Добавувач</th>
                <th style="width: 12%;">Категорија</th>
                <th style="width: 10%;">Основица</th>
                <th style="width: 6%;">ДДВ %</th>
                <th style="width: 10%;">ДДВ износ</th>
                <th style="width: 12%;">Вкупно</th>
                <th style="width: 12%;">Начин плаќ.</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalTaxBase = 0;
                $totalVat = 0;
                $totalAmount = 0;
                $vatBreakdown = [];
            @endphp
            @foreach ($expenses as $index => $expense)
            @php
                $taxBase = $expense->tax_base ?? 0;
                $vatAmt = $expense->vat_amount ?? 0;
                $totalTaxBase += $taxBase;
                $totalVat += $vatAmt;
                $totalAmount += $expense->amount;
                $rate = $expense->vat_rate ?? 18;
                if (!isset($vatBreakdown[$rate])) {
                    $vatBreakdown[$rate] = ['base' => 0, 'vat' => 0, 'total' => 0];
                }
                $vatBreakdown[$rate]['base'] += $taxBase;
                $vatBreakdown[$rate]['vat'] += $vatAmt;
                $vatBreakdown[$rate]['total'] += $expense->amount;
            @endphp
            <tr class="data-row">
                <td class="num">{{ $index + 1 }}</td>
                <td class="num">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d.m.Y') }}</td>
                <td>{{ $expense->invoice_number ?? '-' }}</td>
                <td>{{ $expense->supplier->name ?? '-' }}</td>
                <td>{{ $expense->category->name ?? '-' }}</td>
                <td class="amount">{!! format_money_pdf($taxBase, $currency) !!}</td>
                <td class="num">{{ $rate }}%</td>
                <td class="amount">{!! format_money_pdf($vatAmt, $currency) !!}</td>
                <td class="amount">{!! format_money_pdf($expense->amount, $currency) !!}</td>
                <td>{{ $expense->paymentMethod->name ?? '-' }}</td>
            </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5">ВКУПНО</td>
                <td class="amount">{!! format_money_pdf($totalTaxBase, $currency) !!}</td>
                <td></td>
                <td class="amount">{!! format_money_pdf($totalVat, $currency) !!}</td>
                <td class="amount">{!! format_money_pdf($totalAmount, $currency) !!}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <!-- VAT Breakdown -->
    @if(count($vatBreakdown) > 0)
    <table class="data-table" style="width: 60%; margin-top: 10px;">
        <thead>
            <tr>
                <th colspan="4" style="text-align: center;">Рекапитулација на ДДВ</th>
            </tr>
            <tr>
                <th>ДДВ стапка</th>
                <th>Основица</th>
                <th>ДДВ износ</th>
                <th>Вкупно</th>
            </tr>
        </thead>
        <tbody>
            @foreach($vatBreakdown as $rate => $data)
            <tr class="data-row">
                <td class="num">{{ $rate }}%</td>
                <td class="amount">{!! format_money_pdf($data['base'], $currency) !!}</td>
                <td class="amount">{!! format_money_pdf($data['vat'], $currency) !!}</td>
                <td class="amount">{!! format_money_pdf($data['total'], $currency) !!}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <p class="footer-note">Согласно Закон за данок на додадена вредност, Член 51</p>

    <table class="signatures">
        <tr>
            <td><div class="sig-line">Составил</div></td>
            <td><div class="sig-line">Одговорно лице</div></td>
        </tr>
    </table>
</body>
</html>

<!-- CLAUDE-CHECKPOINT -->
