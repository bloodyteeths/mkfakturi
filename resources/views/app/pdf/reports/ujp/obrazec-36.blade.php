<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Образец 36 — Биланс на состојба</title>
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

        /* Grand total rows */
        .grand-total-row {
            background: #2d2040 !important;
            border-top: 2px solid #2d2040;
        }

        .grand-total-row td {
            font-weight: bold;
            font-size: 9px;
            color: #ffffff;
            padding: 4px 5px;
        }

        /* Total rows */
        .total-row {
            background: #f0ece8 !important;
            border-top: 1px solid #aaa;
        }

        .total-row td {
            font-weight: bold;
        }

        .indent-1 { padding-left: 10px !important; }
        .indent-2 { padding-left: 20px !important; }
        .indent-3 { padding-left: 30px !important; }

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

        /* Balance check */
        .balance-check {
            margin-top: 5px;
            padding: 5px 10px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
        }

        .balanced {
            background: #d4edda;
            color: #155724;
            border: 1px solid #28a745;
        }

        .unbalanced {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #dc3545;
        }
    </style>
</head>

<body>
    <div class="sub-container">

        {{-- UJP Header --}}
        @include('app.pdf.reports._ujp-form-header')

        {{-- АКТИВА Section --}}
        <p class="section-title">АКТИВА</p>
        <table class="aop-table">
            <thead>
                <tr>
                    <th style="width: 6%;">АОП</th>
                    <th style="text-align: left; width: 54%;">Позиција</th>
                    <th style="width: 20%;">Тековна година</th>
                    <th style="width: 20%;">Претходна година</th>
                </tr>
            </thead>
            <tbody>
                @foreach($aktiva as $row)
                    @php
                        $isGrandTotal = $row['is_grand_total'] ?? false;
                        $isTotal = $row['is_total'] ?? false;
                        $isOffbalance = $row['is_offbalance'] ?? false;
                        $indent = $row['indent'] ?? 0;
                        $indentClass = $indent > 0 ? 'indent-' . min($indent, 3) : '';
                        $rowClass = $isGrandTotal ? 'grand-total-row' : ($isTotal && $indent === 0 ? 'section-row' : ($isTotal ? 'total-row' : 'aop-row'));
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
            </tbody>
        </table>

        {{-- ПАСИВА Section --}}
        <p class="section-title">ПАСИВА</p>
        <table class="aop-table">
            <thead>
                <tr>
                    <th style="width: 6%;">АОП</th>
                    <th style="text-align: left; width: 54%;">Позиција</th>
                    <th style="width: 20%;">Тековна година</th>
                    <th style="width: 20%;">Претходна година</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pasiva as $row)
                    @php
                        $isGrandTotal = $row['is_grand_total'] ?? false;
                        $isTotal = $row['is_total'] ?? false;
                        $isOffbalance = $row['is_offbalance'] ?? false;
                        $indent = $row['indent'] ?? 0;
                        $indentClass = $indent > 0 ? 'indent-' . min($indent, 3) : '';
                        $rowClass = $isGrandTotal ? 'grand-total-row' : ($isTotal && $indent === 0 ? 'section-row' : ($isTotal ? 'total-row' : 'aop-row'));
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
            </tbody>
        </table>

        {{-- Balance Check --}}
        <div class="balance-check {{ $isBalanced ? 'balanced' : 'unbalanced' }}">
            @if($isBalanced)
                Актива (АОП 063) = Пасива (АОП 111) = {{ number_format($totalAktiva, 0, '.', ',') }}
            @else
                Актива (АОП 063) = {{ number_format($totalAktiva, 0, '.', ',') }} | Пасива (АОП 111) = {{ number_format($totalPasiva, 0, '.', ',') }} | Разлика: {{ number_format($totalAktiva - $totalPasiva, 0, '.', ',') }}
            @endif
        </div>

        {{-- Footer --}}
        @include('app.pdf.reports._ujp-form-footer', ['pageNumber' => '1', 'totalPages' => '1'])
    </div>
</body>

</html>

{{-- CLAUDE-CHECKPOINT --}}
