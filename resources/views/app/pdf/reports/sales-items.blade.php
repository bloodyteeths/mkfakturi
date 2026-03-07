<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Извештај за продажба по артикли</title>
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

        <p class="sub-heading-text">ИЗВЕШТАЈ ЗА ПРОДАЖБА ПО АРТИКЛИ</p>
        <p class="form-label">За период: {{ $from_date }} - {{ $to_date }}</p>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="text-align: center; width: 6%;">Р.б.</th>
                    <th style="text-align: left; width: 69%;">Артикл / Услуга</th>
                    <th style="text-align: right; width: 25%;">Износ</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $index => $item)
                <tr class="data-row">
                    <td class="num-col">{{ $index + 1 }}</td>
                    <td class="label-col">{{ $item->name }}</td>
                    <td class="amount-col">{!! format_money_pdf($item->total_amount, $currency) !!}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="2">ВКУПНА ПРОДАЖБА</td>
                    <td class="amount-col">{!! format_money_pdf($totalAmount, $currency) !!}</td>
                </tr>
            </tbody>
        </table>

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
