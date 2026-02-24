<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Регистар на основни средства</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 10px;
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
            font-size: 18px;
            color: #1a1a1a;
            width: 100%;
            text-align: left;
            padding: 0px;
            margin: 0px;
        }

        .heading-date {
            font-weight: normal;
            font-size: 11px;
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
            margin: 2px 0 12px 0;
        }

        .section-title {
            font-weight: bold;
            font-size: 11px;
            color: #1a1a1a;
            margin-top: 12px;
            margin-bottom: 4px;
            padding: 5px 8px;
            background: #f0f4f8;
            border-left: 3px solid #2c5282;
        }

        .asset-table {
            width: 100%;
            border: 1px solid #cbd5e0;
            margin-bottom: 5px;
        }

        .asset-table th {
            background: #e2e8f0;
            padding: 5px 4px;
            font-size: 8px;
            font-weight: bold;
            color: #2d3748;
            text-align: center;
            border-bottom: 2px solid #a0aec0;
            border-right: 1px solid #cbd5e0;
        }

        .asset-table th:first-child {
            text-align: center;
        }

        .asset-table th:last-child {
            border-right: none;
        }

        .asset-table td {
            padding: 3px 4px;
            font-size: 9px;
            color: #2d3748;
            border-bottom: 1px solid #edf2f7;
            border-right: 1px solid #edf2f7;
        }

        .asset-table td:last-child {
            border-right: none;
        }

        .asset-table tr:nth-child(even) {
            background: #f7fafc;
        }

        .subtotal-row {
            background: #e2e8f0 !important;
            border-top: 1px solid #a0aec0;
        }

        .subtotal-row td {
            font-weight: bold;
            font-size: 9px;
            color: #1a202c;
        }

        .grand-total {
            margin-top: 15px;
            padding: 10px;
            background: #ebf8ff;
            border: 2px solid #2c5282;
        }

        .grand-total table {
            width: 100%;
        }

        .grand-total td {
            padding: 4px 8px;
            font-size: 10px;
        }

        .grand-total .label {
            font-weight: bold;
            color: #1a365d;
        }

        .grand-total .value {
            text-align: right;
            font-weight: bold;
            color: #1a365d;
        }

        .negative {
            color: #c53030;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

    </style>
</head>

<body>
    <div class="sub-container">
        @include('app.pdf.reports._company-header', ['report_period' => $as_of_date])

        <p class="sub-heading-text">РЕГИСТАР НА ОСНОВНИ СРЕДСТВА</p>
        <p class="form-label">состојба на {{ $as_of_date }}</p>

        @php
            $categoryLabels = [
                'real_estate' => 'Недвижен имот',
                'buildings' => 'Згради',
                'equipment' => 'Опрема',
                'vehicles' => 'Возила',
                'computers_software' => 'Компјутери и софтвер',
                'other' => 'Останато',
            ];
        @endphp

        @foreach($categories as $cat)
            <p class="section-title">{{ $categoryLabels[$cat['category']] ?? $cat['category'] }}</p>
            <table class="asset-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">Р.б.</th>
                        <th style="text-align: left; width: 25%;">Назив</th>
                        <th style="width: 10%;">Шифра</th>
                        <th style="width: 12%;">Датум на набавка</th>
                        <th style="width: 14%;">Набавна вредност</th>
                        <th style="width: 8%;">Стапка %</th>
                        <th style="width: 14%;">Акум. амортизација</th>
                        <th style="width: 14%;">Нето вредност</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cat['assets'] as $idx => $asset)
                    <tr>
                        <td class="text-center">{{ $idx + 1 }}</td>
                        <td class="text-left">{{ $asset['name'] }}</td>
                        <td class="text-center">{{ $asset['asset_code'] ?? '-' }}</td>
                        <td class="text-center">{{ $asset['acquisition_date'] }}</td>
                        <td class="text-right">{{ number_format($asset['acquisition_cost'], 2, '.', ',') }}</td>
                        <td class="text-center">{{ $asset['depreciation_rate'] }}%</td>
                        <td class="text-right negative">{{ number_format($asset['accumulated_depreciation'], 2, '.', ',') }}</td>
                        <td class="text-right">{{ number_format($asset['net_book_value'], 2, '.', ',') }}</td>
                    </tr>
                    @endforeach
                    <tr class="subtotal-row">
                        <td colspan="4" class="text-left">Вкупно — {{ $categoryLabels[$cat['category']] ?? $cat['category'] }}</td>
                        <td class="text-right">{{ number_format($cat['subtotal_cost'], 2, '.', ',') }}</td>
                        <td></td>
                        <td class="text-right negative">{{ number_format($cat['subtotal_depreciation'], 2, '.', ',') }}</td>
                        <td class="text-right">{{ number_format($cat['subtotal_net'], 2, '.', ',') }}</td>
                    </tr>
                </tbody>
            </table>
        @endforeach

        <div class="grand-total">
            <table>
                <tr>
                    <td class="label" style="width: 40%;">ВКУПНО ОСНОВНИ СРЕДСТВА ({{ $totals['count'] }})</td>
                    <td class="value" style="width: 20%;">Набавна: {{ number_format($totals['acquisition_cost'], 2, '.', ',') }}</td>
                    <td class="value negative" style="width: 20%;">Амортиз.: {{ number_format($totals['accumulated_depreciation'], 2, '.', ',') }}</td>
                    <td class="value" style="width: 20%;">Нето: {{ number_format($totals['net_book_value'], 2, '.', ',') }}</td>
                </tr>
            </table>
        </div>
    </div>
</body>

</html>
