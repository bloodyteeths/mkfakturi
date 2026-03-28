<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Извештај за расходи</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
            color: #333;
        }

        table {
            border-collapse: collapse;
        }

        .sub-container {
            padding: 0px 15px;
        }

        .report-header {
            width: 100%;
            margin-bottom: 5px;
        }

        .heading-text {
            font-weight: bold;
            font-size: 16px;
            color: #1a1a1a;
            width: 100%;
            text-align: left;
            padding: 0px;
            margin: 0px;
        }

        .heading-date {
            font-weight: normal;
            font-size: 10px;
            color: #666;
            width: 100%;
            text-align: right;
            padding: 0px;
            margin: 0px;
        }

        .sub-heading-text {
            font-weight: bold;
            font-size: 14px;
            color: #333;
            padding: 0px;
            margin: 0px;
            margin-top: 2px;
            text-align: center;
        }

        .form-label {
            font-size: 9px;
            color: #888;
            text-align: center;
            margin: 2px 0 10px 0;
        }

        .data-table {
            width: 100%;
            border: 1px solid #cbd5e0;
            margin-top: 10px;
        }

        .data-table th {
            background: #e2e8f0;
            padding: 5px 6px;
            font-size: 8px;
            font-weight: bold;
            color: #2d3748;
            border-bottom: 2px solid #a0aec0;
        }

        .data-row {
            border-bottom: 1px solid #edf2f7;
        }

        .data-row:nth-child(even) {
            background: #f7fafc;
        }

        .data-row td {
            padding: 4px 6px;
            font-size: 9px;
            color: #2d3748;
        }

        .num-col {
            text-align: center;
            width: 6%;
            color: #718096;
        }

        .label-col {
            text-align: left;
            width: 69%;
        }

        .amount-col {
            text-align: right;
            width: 25%;
        }

        .total-row {
            background: #e2e8f0 !important;
            border-top: 2px solid #2c5282;
        }

        .total-row td {
            font-weight: bold;
            font-size: 10px;
            color: #1a202c;
            padding: 6px 6px;
        }

        .signature-section {
            margin-top: 30px;
            width: 100%;
        }

        .signature-label {
            font-size: 9px;
            color: #666;
            border-top: 1px solid #999;
            padding-top: 3px;
            width: 200px;
            text-align: center;
        }
    </style>

    @if (App::isLocale('th'))
    @include('app.pdf.locale.th')
    @endif
</head>

<body>
    <div class="sub-container">
        @include('app.pdf.reports._company-header', ['report_period' => $from_date . ' - ' . $to_date])

        <p class="sub-heading-text">ИЗВЕШТАЈ ЗА РАСХОДИ</p>
        <p class="form-label">За период: {{ $from_date }} - {{ $to_date }}</p>

        @if(isset($expenses))
        {{-- Detailed line-item report --}}
        <table class="data-table">
            <thead>
                <tr>
                    <th style="text-align: center; width: 4%;">Р.б.</th>
                    <th style="text-align: center; width: 10%;">Датум</th>
                    <th style="text-align: left; width: 18%;">Категорија</th>
                    <th style="text-align: left; width: 16%;">Добавувач</th>
                    <th style="text-align: left; width: 10%;">Бр. факт.</th>
                    <th style="text-align: right; width: 10%;">Основица</th>
                    <th style="text-align: center; width: 5%;">ДДВ%</th>
                    <th style="text-align: right; width: 10%;">ДДВ</th>
                    <th style="text-align: right; width: 12%;">Износ</th>
                </tr>
            </thead>
            <tbody>
                @php $totalBase = 0; $totalVat = 0; $totalAmount = 0; $i = 0; @endphp
                @foreach ($expenses as $expense)
                @php
                    $i++;
                    $totalBase += $expense->tax_base ?? 0;
                    $totalVat += $expense->vat_amount ?? 0;
                    $totalAmount += $expense->amount;
                @endphp
                <tr class="data-row">
                    <td class="num-col">{{ $i }}</td>
                    <td style="text-align: center;">{{ \Carbon\Carbon::parse($expense->expense_date)->format('d.m.Y') }}</td>
                    <td>{{ $expense->category->name ?? '-' }}</td>
                    <td>{{ $expense->supplier->name ?? '-' }}</td>
                    <td>{{ $expense->invoice_number ?? '-' }}</td>
                    <td class="amount-col">{!! format_money_pdf($expense->tax_base ?? 0, $currency) !!}</td>
                    <td style="text-align: center;">{{ $expense->vat_rate ?? 18 }}%</td>
                    <td class="amount-col">{!! format_money_pdf($expense->vat_amount ?? 0, $currency) !!}</td>
                    <td class="amount-col">{!! format_money_pdf($expense->amount, $currency) !!}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="5">ВКУПНО РАСХОДИ</td>
                    <td class="amount-col">{!! format_money_pdf($totalBase, $currency) !!}</td>
                    <td></td>
                    <td class="amount-col">{!! format_money_pdf($totalVat, $currency) !!}</td>
                    <td class="amount-col">{!! format_money_pdf($totalAmount, $currency) !!}</td>
                </tr>
            </tbody>
        </table>
        @else
        {{-- Legacy category-summary report (backwards compatible) --}}
        <table class="data-table">
            <thead>
                <tr>
                    <th style="text-align: center; width: 6%;">Р.б.</th>
                    <th style="text-align: left; width: 69%;">Категорија на расход</th>
                    <th style="text-align: right; width: 25%;">Износ</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($expenseCategories as $index => $expenseCategory)
                <tr class="data-row">
                    <td class="num-col">{{ $index + 1 }}</td>
                    <td class="label-col">{{ $expenseCategory->category->name ?? '-' }}</td>
                    <td class="amount-col">{!! format_money_pdf($expenseCategory->total_amount, $currency) !!}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2">ВКУПНО РАСХОДИ</td>
                    <td class="amount-col">{!! format_money_pdf($totalExpense, $currency) !!}</td>
                </tr>
            </tbody>
        </table>
        @endif

        <!-- Signatures -->
        <table class="signature-section">
            <tr>
                <td style="width: 50%; text-align: center; padding-top: 40px;">
                    <p class="signature-label">Составил</p>
                </td>
                <td style="width: 50%; text-align: center; padding-top: 40px;">
                    <p class="signature-label">Одговорно лице</p>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>

<!-- CLAUDE-CHECKPOINT -->
