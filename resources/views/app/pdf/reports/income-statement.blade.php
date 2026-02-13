<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Биланс на успех</title>
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

        .heading-date-range {
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

        .indent-1 {
            padding-left: 15px !important;
        }

        .result-table {
            width: 100%;
            margin-top: 20px;
            border-top: 2px solid #2c5282;
        }

        .result-row {
            border-bottom: 1px solid #e2e8f0;
        }

        .result-row td {
            padding: 6px 6px;
            font-size: 10px;
        }

        .result-highlight {
            background: #ebf8ff;
            border-top: 2px solid #2c5282;
        }

        .result-highlight td {
            font-weight: bold;
            font-size: 12px;
        }

        .profit-positive {
            color: #22543d !important;
        }

        .profit-negative {
            color: #c53030 !important;
        }
    </style>

    @if (App::isLocale('th'))
    @include('app.pdf.locale.th')
    @endif
</head>

<body>
    <div class="sub-container">
        <table class="report-header">
            <tr>
                <td>
                    <p class="heading-text">{{ $company->name }}</p>
                </td>
                <td>
                    <p class="heading-date-range">{{ $from_date }} - {{ $to_date }}</p>
                </td>
            </tr>
        </table>
        <p class="sub-heading-text">БИЛАНС НА УСПЕХ</p>
        <p class="form-label">Образец 37 — за период од {{ $from_date }} до {{ $to_date }}</p>

        <!-- I. ПРИХОДИ ОД РАБОТЕЊЕТО -->
        <p class="section-title">I. ПРИХОДИ ОД РАБОТЕЊЕТО</p>
        <table class="aop-table">
            <thead>
                <tr>
                    <th style="width: 8%;">АОП</th>
                    <th style="text-align: left; width: 52%;">Позиција</th>
                    <th style="width: 20%;">Тековна година</th>
                    <th style="width: 20%;">Претходна година</th>
                </tr>
            </thead>
            <tbody>
                @foreach($aopData['prihodi'] as $row)
                <tr class="aop-row {{ $row['is_total'] ? 'total-row' : '' }}">
                    <td class="aop-code">{{ $row['aop'] }}</td>
                    <td class="aop-label {{ ($row['indent'] ?? 0) == 1 ? 'indent-1' : '' }}">
                        {{ $row['label'] }}
                    </td>
                    <td class="aop-amount">
                        {!! format_money_pdf($row['current'] * 100, $currency) !!}
                    </td>
                    <td class="aop-amount">
                        {!! format_money_pdf($row['previous'] * 100, $currency) !!}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- II. РАСХОДИ ОД РАБОТЕЊЕТО -->
        <p class="section-title">II. РАСХОДИ ОД РАБОТЕЊЕТО</p>
        <table class="aop-table">
            <thead>
                <tr>
                    <th style="width: 8%;">АОП</th>
                    <th style="text-align: left; width: 52%;">Позиција</th>
                    <th style="width: 20%;">Тековна година</th>
                    <th style="width: 20%;">Претходна година</th>
                </tr>
            </thead>
            <tbody>
                @foreach($aopData['rashodi'] as $row)
                <tr class="aop-row {{ $row['is_total'] ? 'total-row' : '' }}">
                    <td class="aop-code">{{ $row['aop'] }}</td>
                    <td class="aop-label {{ ($row['indent'] ?? 0) == 1 ? 'indent-1' : '' }}">
                        {{ $row['label'] }}
                    </td>
                    <td class="aop-amount">
                        {!! format_money_pdf($row['current'] * 100, $currency) !!}
                    </td>
                    <td class="aop-amount">
                        {!! format_money_pdf($row['previous'] * 100, $currency) !!}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- III. РЕЗУЛТАТ -->
        <table class="result-table">
            @foreach($aopData['rezultat'] as $row)
            @php
                $isNetResult = in_array($row['aop'], ['255', '256']);
                $isLoss = in_array($row['aop'], ['245', '249', '256']);
                $valueClass = $isLoss ? 'profit-negative' : 'profit-positive';
            @endphp
            <tr class="{{ $isNetResult ? 'result-highlight' : 'result-row' }}">
                <td class="aop-code" style="width: 8%;">{{ $row['aop'] }}</td>
                <td class="aop-label" style="width: 52%;">{{ $row['label'] }}</td>
                <td class="aop-amount {{ $valueClass }}" style="width: 20%;">
                    @if($row['current'] > 0)
                        {!! format_money_pdf($row['current'] * 100, $currency) !!}
                    @else
                        -
                    @endif
                </td>
                <td class="aop-amount {{ $valueClass }}" style="width: 20%;">
                    @if($row['previous'] > 0)
                        {!! format_money_pdf($row['previous'] * 100, $currency) !!}
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </table>
    </div>
</body>

</html>

<!-- CLAUDE-CHECKPOINT -->
