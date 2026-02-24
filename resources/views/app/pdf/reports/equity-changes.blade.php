<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Извештај за промени во капиталот</title>
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
            font-size: 12px;
            color: #1a1a1a;
            margin-top: 15px;
            margin-bottom: 6px;
            padding: 5px 8px;
            background: #f0f4f8;
            border-left: 3px solid #2c5282;
        }

        .equity-table {
            width: 100%;
            border: 1px solid #cbd5e0;
            margin-bottom: 10px;
        }

        .equity-table th {
            background: #e2e8f0;
            padding: 6px 4px;
            font-size: 8px;
            font-weight: bold;
            color: #2d3748;
            text-align: center;
            border-bottom: 2px solid #a0aec0;
            border-right: 1px solid #cbd5e0;
        }

        .equity-table th:last-child {
            border-right: none;
        }

        .equity-table td {
            padding: 4px 4px;
            font-size: 9px;
            color: #2d3748;
            border-bottom: 1px solid #edf2f7;
            border-right: 1px solid #edf2f7;
        }

        .equity-table td:last-child {
            border-right: none;
        }

        .equity-table td.label-col {
            text-align: left;
            width: 22%;
            font-size: 9px;
        }

        .equity-table td.amount-col {
            text-align: right;
            width: 13%;
        }

        .opening-row {
            background: #f7fafc;
        }

        .opening-row td {
            font-weight: bold;
        }

        .changes-row td {
            color: #4a5568;
        }

        .total-changes-row {
            border-top: 1px solid #a0aec0;
            background: #edf2f7;
        }

        .total-changes-row td {
            font-weight: bold;
        }

        .closing-row {
            background: #ebf8ff;
            border-top: 2px solid #2c5282;
        }

        .closing-row td {
            font-weight: bold;
            font-size: 10px;
            color: #1a365d;
        }

        .negative {
            color: #c53030;
        }

        .positive {
            color: #276749;
        }

        .dash {
            color: #a0aec0;
            text-align: center !important;
        }
    </style>
</head>

<body>
    <div class="sub-container">
        @include('app.pdf.reports._company-header', ['report_period' => $year])

        <p class="sub-heading-text">ИЗВЕШТАЈ ЗА ПРОМЕНИ ВО КАПИТАЛОТ</p>
        <p class="form-label">за фискална година {{ $year }}</p>

        {{-- Current Year --}}
        <p class="section-title">Тековна година ({{ $year }})</p>
        <table class="equity-table">
            <thead>
                <tr>
                    <th style="text-align: left; width: 22%;">Позиција</th>
                    <th style="width: 13%;">Основен капитал</th>
                    <th style="width: 13%;">Резерви</th>
                    <th style="width: 13%;">Задржана добивка</th>
                    <th style="width: 13%;">Ревалор. резерви</th>
                    <th style="width: 13%;">Останато</th>
                    <th style="width: 13%;">Вкупно</th>
                </tr>
            </thead>
            <tbody>
                <tr class="opening-row">
                    <td class="label-col">Почетно салдо (01.01.{{ $year }})</td>
                    <td class="amount-col">{{ number_format($current['opening']['share_capital'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($current['opening']['reserves'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($current['opening']['retained_earnings'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($current['opening']['revaluation'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($current['opening']['other'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($current['opening']['total'] ?? 0, 2, '.', ',') }}</td>
                </tr>
                <tr class="changes-row">
                    <td class="label-col">Нето добивка/загуба</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col {{ ($current['net_income'] ?? 0) < 0 ? 'negative' : '' }}">{{ number_format($current['net_income'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col {{ ($current['net_income'] ?? 0) < 0 ? 'negative' : '' }}">{{ number_format($current['net_income'] ?? 0, 2, '.', ',') }}</td>
                </tr>
                @if(($current['changes']['share_capital'] ?? 0) != 0)
                <tr class="changes-row">
                    <td class="label-col" style="padding-left: 12px;">Промена во капитал</td>
                    <td class="amount-col {{ ($current['changes']['share_capital'] ?? 0) < 0 ? 'negative' : '' }}">{{ number_format($current['changes']['share_capital'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col {{ ($current['changes']['share_capital'] ?? 0) < 0 ? 'negative' : '' }}">{{ number_format($current['changes']['share_capital'] ?? 0, 2, '.', ',') }}</td>
                </tr>
                @endif
                @if(($current['changes']['reserves'] ?? 0) != 0)
                <tr class="changes-row">
                    <td class="label-col" style="padding-left: 12px;">Промена во резерви</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col {{ ($current['changes']['reserves'] ?? 0) < 0 ? 'negative' : '' }}">{{ number_format($current['changes']['reserves'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col {{ ($current['changes']['reserves'] ?? 0) < 0 ? 'negative' : '' }}">{{ number_format($current['changes']['reserves'] ?? 0, 2, '.', ',') }}</td>
                </tr>
                @endif
                @if(($current['changes']['revaluation'] ?? 0) != 0)
                <tr class="changes-row">
                    <td class="label-col" style="padding-left: 12px;">Промена во ревалор. резерви</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col {{ ($current['changes']['revaluation'] ?? 0) < 0 ? 'negative' : '' }}">{{ number_format($current['changes']['revaluation'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col {{ ($current['changes']['revaluation'] ?? 0) < 0 ? 'negative' : '' }}">{{ number_format($current['changes']['revaluation'] ?? 0, 2, '.', ',') }}</td>
                </tr>
                @endif
                <tr class="total-changes-row">
                    <td class="label-col">Вкупно промени</td>
                    <td class="amount-col">{{ number_format($current['changes']['share_capital'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($current['changes']['reserves'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($current['changes']['retained_earnings'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($current['changes']['revaluation'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($current['changes']['other'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($current['changes']['total'] ?? 0, 2, '.', ',') }}</td>
                </tr>
                <tr class="closing-row">
                    <td class="label-col">Крајно салдо (31.12.{{ $year }})</td>
                    <td class="amount-col">{{ number_format($current['closing']['share_capital'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($current['closing']['reserves'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($current['closing']['retained_earnings'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($current['closing']['revaluation'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($current['closing']['other'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($current['closing']['total'] ?? 0, 2, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Previous Year --}}
        <p class="section-title">Претходна година ({{ $year - 1 }})</p>
        <table class="equity-table">
            <thead>
                <tr>
                    <th style="text-align: left; width: 22%;">Позиција</th>
                    <th style="width: 13%;">Основен капитал</th>
                    <th style="width: 13%;">Резерви</th>
                    <th style="width: 13%;">Задржана добивка</th>
                    <th style="width: 13%;">Ревалор. резерви</th>
                    <th style="width: 13%;">Останато</th>
                    <th style="width: 13%;">Вкупно</th>
                </tr>
            </thead>
            <tbody>
                <tr class="opening-row">
                    <td class="label-col">Почетно салдо (01.01.{{ $year - 1 }})</td>
                    <td class="amount-col">{{ number_format($previous['opening']['share_capital'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($previous['opening']['reserves'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($previous['opening']['retained_earnings'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($previous['opening']['revaluation'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($previous['opening']['other'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($previous['opening']['total'] ?? 0, 2, '.', ',') }}</td>
                </tr>
                <tr class="changes-row">
                    <td class="label-col">Нето добивка/загуба</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col {{ ($previous['net_income'] ?? 0) < 0 ? 'negative' : '' }}">{{ number_format($previous['net_income'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col dash">-</td>
                    <td class="amount-col {{ ($previous['net_income'] ?? 0) < 0 ? 'negative' : '' }}">{{ number_format($previous['net_income'] ?? 0, 2, '.', ',') }}</td>
                </tr>
                <tr class="total-changes-row">
                    <td class="label-col">Вкупно промени</td>
                    <td class="amount-col">{{ number_format($previous['changes']['share_capital'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($previous['changes']['reserves'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($previous['changes']['retained_earnings'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($previous['changes']['revaluation'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($previous['changes']['other'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($previous['changes']['total'] ?? 0, 2, '.', ',') }}</td>
                </tr>
                <tr class="closing-row">
                    <td class="label-col">Крајно салдо (31.12.{{ $year - 1 }})</td>
                    <td class="amount-col">{{ number_format($previous['closing']['share_capital'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($previous['closing']['reserves'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($previous['closing']['retained_earnings'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($previous['closing']['revaluation'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($previous['closing']['other'] ?? 0, 2, '.', ',') }}</td>
                    <td class="amount-col">{{ number_format($previous['closing']['total'] ?? 0, 2, '.', ',') }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>
