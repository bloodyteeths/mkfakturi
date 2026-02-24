<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Извештај за парични текови</title>
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

        .aop-table {
            width: 100%;
            border: 1px solid #cbd5e0;
            margin-bottom: 5px;
        }

        .aop-table th {
            background: #e2e8f0;
            padding: 6px 6px;
            font-size: 9px;
            font-weight: bold;
            color: #2d3748;
            text-align: center;
            border-bottom: 2px solid #a0aec0;
        }

        .aop-table th:first-child {
            text-align: center;
        }

        .aop-row {
            border-bottom: 1px solid #edf2f7;
        }

        .aop-row:nth-child(even) {
            background: #f7fafc;
        }

        .aop-row td {
            padding: 4px 6px;
            font-size: 10px;
            color: #2d3748;
        }

        .aop-code {
            text-align: center;
            color: #718096;
            font-size: 9px;
            width: 8%;
        }

        .aop-label {
            text-align: left;
            width: 52%;
        }

        .aop-amount {
            text-align: right;
            width: 20%;
        }

        .total-row {
            background: #e2e8f0 !important;
            border-top: 1px solid #a0aec0;
        }

        .total-row td {
            font-weight: bold;
            font-size: 10px;
            color: #1a202c;
        }

        .section-header-row {
            background: #f0f4f8 !important;
        }

        .section-header-row td {
            font-weight: bold;
            font-size: 10px;
            color: #1a202c;
        }

        .indent-1 {
            padding-left: 15px !important;
        }

        .summary-row {
            background: #ebf8ff !important;
            border-top: 2px solid #2c5282;
        }

        .summary-row td {
            font-weight: bold;
            font-size: 11px;
            color: #1a365d;
        }

        .negative {
            color: #c53030;
        }

        .positive {
            color: #276749;
        }
    </style>
</head>

<body>
    <div class="sub-container">
        @include('app.pdf.reports._company-header', ['report_period' => $start_date . ' — ' . $end_date])
        <p class="sub-heading-text">ИЗВЕШТАЈ ЗА ПАРИЧНИ ТЕКОВИ</p>
        <p class="form-label">Образец 38 — за период {{ $start_date }} до {{ $end_date }}</p>

        <table class="aop-table">
            <thead>
                <tr>
                    <th style="width: 8%;">АОП</th>
                    <th style="text-align: left; width: 52%;">Позиција</th>
                    <th style="width: 20%;">Тековен период</th>
                    <th style="width: 20%;">Претходен период</th>
                </tr>
            </thead>
            <tbody>
                @foreach($aopRows as $row)
                @php
                    $rowClass = 'aop-row';
                    if (!empty($row['is_total'])) $rowClass .= ' total-row';
                    elseif ($row['indent'] === 0 && empty($row['data_key'])) $rowClass .= ' section-header-row';
                    if (!empty($row['is_summary'])) $rowClass .= ' summary-row';
                @endphp
                <tr class="{{ $rowClass }}">
                    <td class="aop-code">{{ $row['aop'] }}</td>
                    <td class="aop-label {{ $row['indent'] >= 1 ? 'indent-1' : '' }}">
                        {{ $row['label'] }}
                    </td>
                    <td class="aop-amount {{ ($row['current'] ?? 0) < 0 ? 'negative' : '' }}">
                        {{ number_format($row['current'] ?? 0, 2, '.', ',') }}
                    </td>
                    <td class="aop-amount {{ ($row['previous'] ?? 0) < 0 ? 'negative' : '' }}">
                        {{ number_format($row['previous'] ?? 0, 2, '.', ',') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>

</html>
