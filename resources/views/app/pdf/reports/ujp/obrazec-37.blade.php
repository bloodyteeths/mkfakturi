<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Образец 37 — Биланс на успех</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 8px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
        }

        .sub-container {
            padding: 0px 10px;
        }

        /* AOP table */
        .aop-table {
            width: 100%;
            border: 1px solid #888;
            margin-bottom: 2px;
        }

        .aop-table th {
            background: #2d2040;
            color: #ffffff;
            padding: 4px 5px;
            font-size: 7.5px;
            font-weight: bold;
            text-align: center;
            border-bottom: 2px solid #555;
        }

        .aop-row {
            border-bottom: 1px solid #ddd;
        }

        .aop-row:nth-child(even) {
            background: #fafafa;
        }

        .aop-row td {
            padding: 2px 4px;
            font-size: 7.5px;
            color: #333;
        }

        .aop-code {
            text-align: center;
            color: #666;
            font-size: 7px;
            width: 6%;
            border-right: 1px solid #ddd;
            vertical-align: middle;
        }

        .aop-label {
            width: 54%;
            border-right: 1px solid #ddd;
            line-height: 1.3;
        }

        .aop-amount {
            text-align: right;
            width: 20%;
            border-right: 1px solid #ddd;
            vertical-align: middle;
        }

        .aop-amount:last-child {
            border-right: none;
        }

        /* Section header rows */
        .section-row {
            background: #e8e0f0 !important;
            border-bottom: 1px solid #888;
        }

        .section-row td {
            font-weight: bold;
            font-size: 8px;
            color: #2d2040;
            padding: 3px 4px;
        }

        /* Total rows */
        .total-row {
            background: #f0ece8 !important;
            border-top: 1px solid #aaa;
        }

        .total-row td {
            font-weight: bold;
        }

        /* Result rows */
        .result-row {
            background: #fff8e6 !important;
            border-top: 1px solid #dda;
        }

        .result-row td {
            font-weight: bold;
            font-size: 8px;
        }

        /* Net result highlight */
        .net-result-row {
            background: #e8e0f0 !important;
            border-top: 2px solid #2d2040;
        }

        .net-result-row td {
            font-weight: bold;
            font-size: 9px;
            color: #2d2040;
        }

        /* Grand total rows */
        .grand-total-row {
            background: #2d2040 !important;
            border-top: 2px solid #2d2040;
        }

        .grand-total-row td {
            font-weight: bold;
            font-size: 8px;
            color: #ffffff;
            padding: 3px 5px;
        }

        .indent-1 { padding-left: 10px !important; }
        .indent-2 { padding-left: 20px !important; }

        /* Section title */
        .section-title {
            font-weight: bold;
            font-size: 10px;
            color: #2d2040;
            margin: 8px 0 3px 0;
            padding: 3px 6px;
            background: #e8e0f0;
            border-left: 3px solid #2d2040;
        }

        .profit-positive { color: #155724 !important; }
        .profit-negative { color: #721c24 !important; }
    </style>
</head>

<body>
    <div class="sub-container">

        {{-- UJP Header --}}
        @include('app.pdf.reports._ujp-form-header')

        {{-- Main Data Table --}}
        <table class="aop-table" style="margin-top: 8px;">
            <thead>
                <tr>
                    <th style="width: 6%;">АОП</th>
                    <th style="text-align: left; width: 54%;">Позиција</th>
                    <th style="width: 20%;">Тековна година</th>
                    <th style="width: 20%;">Претходна година</th>
                </tr>
            </thead>
            <tbody>
                {{-- I. ПРИХОДИ ОД РАБОТЕЊЕТО --}}
                @foreach($prihodi as $row)
                    @php
                        $isTotal = $row['is_total'] ?? false;
                        $indent = $row['indent'] ?? 0;
                        $indentClass = $indent > 0 ? 'indent-' . min($indent, 2) : '';
                        $rowClass = $isTotal && $indent === 0 ? 'section-row' : ($isTotal ? 'total-row' : 'aop-row');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="aop-code">{{ $row['aop'] }}</td>
                        <td class="aop-label {{ $indentClass }}">{{ $row['label'] }}</td>
                        <td class="aop-amount">
                            @if($row['current'] != 0)
                                {{ number_format($row['current'], 0, '.', ',') }}
                            @endif
                        </td>
                        <td class="aop-amount" style="border-right: none;">
                            @if($row['previous'] != 0)
                                {{ number_format($row['previous'], 0, '.', ',') }}
                            @endif
                        </td>
                    </tr>
                @endforeach

                {{-- II. РАСХОДИ ОД РАБОТЕЊЕТО --}}
                @foreach($rashodi as $row)
                    @php
                        $isTotal = $row['is_total'] ?? false;
                        $indent = $row['indent'] ?? 0;
                        $indentClass = $indent > 0 ? 'indent-' . min($indent, 2) : '';
                        $rowClass = $isTotal && $indent === 0 ? 'section-row' : ($isTotal ? 'total-row' : 'aop-row');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="aop-code">{{ $row['aop'] }}</td>
                        <td class="aop-label {{ $indentClass }}">{{ $row['label'] }}</td>
                        <td class="aop-amount">
                            @if($row['current'] != 0)
                                {{ number_format($row['current'], 0, '.', ',') }}
                            @endif
                        </td>
                        <td class="aop-amount" style="border-right: none;">
                            @if($row['previous'] != 0)
                                {{ number_format($row['previous'], 0, '.', ',') }}
                            @endif
                        </td>
                    </tr>
                @endforeach

                {{-- III-IV. ФИНАНСИСКИ + РЕЗУЛТАТ --}}
                @foreach($rezultat as $row)
                    @php
                        $isResult = $row['is_result'] ?? false;
                        $isConsolidated = $row['consolidated_only'] ?? false;
                        $indent = $row['indent'] ?? 0;
                        $indentClass = $indent > 0 ? 'indent-' . min($indent, 2) : '';
                        $aop = $row['aop'];

                        $isNetResult = in_array($aop, ['233', '234']);
                        $isGrandTotal = in_array($aop, ['243', '244']);
                        $isLoss = in_array($aop, ['226', '230', '234']);
                        $isFinancialHeader = in_array($aop, ['223', '224']);

                        if ($isGrandTotal) {
                            $rowClass = 'grand-total-row';
                        } elseif ($isNetResult) {
                            $rowClass = 'net-result-row';
                        } elseif ($isResult && $indent === 0) {
                            $rowClass = 'result-row';
                        } elseif ($isFinancialHeader) {
                            $rowClass = 'section-row';
                        } else {
                            $rowClass = 'aop-row';
                        }

                        $valueClass = $isLoss ? 'profit-negative' : ($isResult && !$isLoss ? 'profit-positive' : '');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="aop-code">{{ $aop }}</td>
                        <td class="aop-label {{ $indentClass }}">{{ $row['label'] }}</td>
                        <td class="aop-amount {{ $valueClass }}">
                            @if($row['current'] != 0)
                                {{ number_format(abs($row['current']), 0, '.', ',') }}
                            @endif
                        </td>
                        <td class="aop-amount {{ $valueClass }}" style="border-right: none;">
                            @if($row['previous'] != 0)
                                {{ number_format(abs($row['previous']), 0, '.', ',') }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Footer --}}
        @include('app.pdf.reports._ujp-form-footer', ['pageNumber' => '1', 'totalPages' => '1'])
    </div>
</body>

</html>

{{-- CLAUDE-CHECKPOINT --}}
