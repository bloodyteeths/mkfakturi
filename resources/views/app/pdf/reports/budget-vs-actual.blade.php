<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Буџет vs. Реализација - {{ $budget->name }}</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
            color: #333;
            margin: 15px;
        }

        table {
            border-collapse: collapse;
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

        .summary-table {
            width: 100%;
            margin-bottom: 12px;
        }

        .summary-cell {
            border: 1px solid #cbd5e0;
            padding: 8px;
            text-align: center;
            width: 25%;
        }

        .summary-label {
            font-size: 8px;
            color: #888;
            margin: 0 0 3px 0;
            padding: 0;
        }

        .summary-value {
            font-size: 13px;
            font-weight: bold;
            color: #2d3748;
            margin: 0;
            padding: 0;
        }

        .variance-positive {
            color: #c53030;
        }

        .variance-negative {
            color: #276749;
        }

        .section-title {
            font-weight: bold;
            font-size: 11px;
            color: #2d3748;
            padding: 6px 0 3px 0;
            margin: 8px 0 4px 0;
            border-bottom: 2px solid #2c5282;
        }

        .comparison-table {
            width: 100%;
            border: 1px solid #cbd5e0;
            margin-bottom: 10px;
        }

        .table-header {
            background: #e2e8f0;
            border-bottom: 2px solid #a0aec0;
        }

        .table-header-text {
            padding: 5px 4px;
            margin: 0px;
            font-weight: bold;
            font-size: 8px;
            color: #2d3748;
            text-align: center;
        }

        .comp-row {
            border-bottom: 1px solid #edf2f7;
        }

        .comp-row:nth-child(even) {
            background: #f7fafc;
        }

        .cell-text {
            padding: 4px;
            margin: 0px;
            font-size: 9px;
            color: #2d3748;
        }

        .cell-amount {
            padding: 4px;
            margin: 0px;
            font-size: 9px;
            text-align: right;
            color: #2d3748;
        }

        .over-budget {
            color: #c53030;
            font-weight: bold;
        }

        .under-budget {
            color: #276749;
            font-weight: bold;
        }

        .total-row {
            background: #e2e8f0;
            border-top: 2px solid #2c5282;
        }

        .total-label {
            padding: 6px 4px;
            margin: 0px;
            font-weight: bold;
            font-size: 10px;
            color: #1a202c;
        }

        .total-amount {
            padding: 6px 4px;
            margin: 0px;
            font-weight: bold;
            font-size: 10px;
            text-align: right;
            color: #2c5282;
        }

        .top-items-table {
            width: 100%;
            border: 1px solid #cbd5e0;
            margin-bottom: 10px;
        }

        .top-items-header {
            background: #fed7d7;
            border-bottom: 1px solid #fc8181;
        }

        .top-items-header-green {
            background: #c6f6d5;
            border-bottom: 1px solid #48bb78;
        }

        .top-item-row {
            border-bottom: 1px solid #edf2f7;
        }

        .signature-section {
            margin-top: 40px;
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

        .stamp-area {
            font-size: 9px;
            color: #999;
            text-align: center;
            padding-top: 20px;
        }
    </style>
</head>

<body>
    @include('app.pdf.reports._company-header')

    <p class="sub-heading-text">БУЏЕТ vs. РЕАЛИЗАЦИЈА</p>
    <p class="form-label">
        {{ $budget->name }} | {{ $budget->start_date?->format('d.m.Y') }} - {{ $budget->end_date?->format('d.m.Y') }}
    </p>

    {{-- Summary cards --}}
    <table class="summary-table">
        <tr>
            <td class="summary-cell">
                <p class="summary-label">Вкупно буџетирано</p>
                <p class="summary-value">{{ $formatNumber($summary['total_budgeted'] ?? 0) }}</p>
            </td>
            <td class="summary-cell">
                <p class="summary-label">Вкупно реализирано</p>
                <p class="summary-value">{{ $formatNumber($summary['total_actual'] ?? 0) }}</p>
            </td>
            <td class="summary-cell">
                <p class="summary-label">Отстапување</p>
                <p class="summary-value {{ ($summary['total_variance'] ?? 0) > 0 ? 'variance-positive' : 'variance-negative' }}">
                    {{ $formatNumber($summary['total_variance'] ?? 0) }}
                </p>
            </td>
            <td class="summary-cell">
                <p class="summary-label">Отстапување %</p>
                <p class="summary-value {{ ($summary['total_variance_pct'] ?? 0) > 0 ? 'variance-positive' : 'variance-negative' }}">
                    {{ number_format($summary['total_variance_pct'] ?? 0, 1) }}%
                </p>
            </td>
        </tr>
    </table>

    {{-- Comparison table --}}
    <p class="section-title">ДЕТАЛНА СПОРЕДБА</p>
    <table class="comparison-table">
        <thead>
            <tr class="table-header">
                <th style="text-align: left; width: 28%; border-right: 1px solid #a0aec0;">
                    <p class="table-header-text" style="text-align: left;">Тип на конто</p>
                </th>
                <th style="width: 18%; border-right: 1px solid #a0aec0;">
                    <p class="table-header-text">Буџетирано</p>
                </th>
                <th style="width: 18%; border-right: 1px solid #a0aec0;">
                    <p class="table-header-text">Реализирано</p>
                </th>
                <th style="width: 18%; border-right: 1px solid #a0aec0;">
                    <p class="table-header-text">Отстапување</p>
                </th>
                <th style="width: 18%;">
                    <p class="table-header-text">Отстапување %</p>
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach($comparisonRows as $row)
            <tr class="comp-row">
                <td style="border-right: 1px solid #edf2f7;">
                    <p class="cell-text">{{ $row['account_type_label'] ?? $row['account_type'] }}</p>
                </td>
                <td style="border-right: 1px solid #edf2f7;">
                    <p class="cell-amount">{{ $formatNumber($row['budgeted']) }}</p>
                </td>
                <td style="border-right: 1px solid #edf2f7;">
                    <p class="cell-amount">{{ $formatNumber($row['actual']) }}</p>
                </td>
                <td style="border-right: 1px solid #edf2f7;">
                    @php
                        $isExpense = str_contains(strtolower($row['account_type']), 'expense');
                        $varianceClass = '';
                        if ($row['variance'] > 0) {
                            $varianceClass = $isExpense ? 'over-budget' : 'under-budget';
                        } elseif ($row['variance'] < 0) {
                            $varianceClass = $isExpense ? 'under-budget' : 'over-budget';
                        }
                    @endphp
                    <p class="cell-amount {{ $varianceClass }}">{{ $formatNumber($row['variance']) }}</p>
                </td>
                <td>
                    <p class="cell-amount {{ $varianceClass }}">{{ number_format($row['variance_pct'] ?? 0, 1) }}%</p>
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td>
                    <p class="total-label">ВКУПНО</p>
                </td>
                <td>
                    <p class="total-amount">{{ $formatNumber($summary['total_budgeted'] ?? 0) }}</p>
                </td>
                <td>
                    <p class="total-amount">{{ $formatNumber($summary['total_actual'] ?? 0) }}</p>
                </td>
                <td>
                    <p class="total-amount {{ ($summary['total_variance'] ?? 0) > 0 ? 'variance-positive' : 'variance-negative' }}">
                        {{ $formatNumber($summary['total_variance'] ?? 0) }}
                    </p>
                </td>
                <td>
                    <p class="total-amount {{ ($summary['total_variance_pct'] ?? 0) > 0 ? 'variance-positive' : 'variance-negative' }}">
                        {{ number_format($summary['total_variance_pct'] ?? 0, 1) }}%
                    </p>
                </td>
            </tr>
        </tfoot>
    </table>

    {{-- Top over-budget items --}}
    @if(!empty($summary['top_over_budget']))
    <p class="section-title" style="border-bottom-color: #c53030;">НАЈГОЛЕМИ ПРЕКОРАЧУВАЊА</p>
    <table class="top-items-table">
        <thead>
            <tr class="top-items-header">
                <th style="text-align: left; width: 40%; padding: 4px;">
                    <p class="table-header-text" style="text-align: left;">Тип на конто</p>
                </th>
                <th style="width: 20%; padding: 4px;">
                    <p class="table-header-text">Отстапување</p>
                </th>
                <th style="width: 20%; padding: 4px;">
                    <p class="table-header-text">Отстапување %</p>
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary['top_over_budget'] as $item)
            <tr class="top-item-row">
                <td style="padding: 4px;">
                    <p class="cell-text">{{ $item['account_type_label'] ?? $item['account_type'] }}</p>
                </td>
                <td>
                    <p class="cell-amount over-budget">{{ $formatNumber($item['variance'] ?? 0) }}</p>
                </td>
                <td>
                    <p class="cell-amount over-budget">{{ number_format($item['variance_pct'] ?? 0, 1) }}%</p>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Top under-budget items --}}
    @if(!empty($summary['top_under_budget']))
    <p class="section-title" style="border-bottom-color: #276749;">НАЈГОЛЕМИ ЗАШТЕДИ</p>
    <table class="top-items-table">
        <thead>
            <tr class="top-items-header-green">
                <th style="text-align: left; width: 40%; padding: 4px;">
                    <p class="table-header-text" style="text-align: left;">Тип на конто</p>
                </th>
                <th style="width: 20%; padding: 4px;">
                    <p class="table-header-text">Заштеда</p>
                </th>
                <th style="width: 20%; padding: 4px;">
                    <p class="table-header-text">Заштеда %</p>
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary['top_under_budget'] as $item)
            <tr class="top-item-row">
                <td style="padding: 4px;">
                    <p class="cell-text">{{ $item['account_type_label'] ?? $item['account_type'] }}</p>
                </td>
                <td>
                    <p class="cell-amount under-budget">{{ $formatNumber(abs($item['variance'] ?? 0)) }}</p>
                </td>
                <td>
                    <p class="cell-amount under-budget">{{ number_format(abs($item['variance_pct'] ?? 0), 1) }}%</p>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Signatures --}}
    <table class="signature-section">
        <tr>
            <td style="width: 33%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Изготвил: ___________</p>
            </td>
            <td style="width: 34%; text-align: center; padding-top: 40px;">
                <p class="stamp-area">М.П.</p>
            </td>
            <td style="width: 33%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Одобрил: ___________</p>
            </td>
        </tr>
    </table>
</body>

</html>
