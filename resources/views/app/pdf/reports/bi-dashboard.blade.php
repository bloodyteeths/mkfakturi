<!DOCTYPE html>
<html>

<head>
    <title>Финансиски Показатели - {{ $company->name ?? 'N/A' }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 10px;
            color: #333;
            margin: 15px;
        }

        table {
            border-collapse: collapse;
        }

        h1 {
            font-size: 16px;
            color: #5851DB;
            margin: 0 0 5px 0;
        }

        h2 {
            font-size: 12px;
            color: #5851DB;
            margin: 12px 0 6px 0;
            padding-bottom: 3px;
            border-bottom: 2px solid #5851DB;
        }

        .header-table {
            width: 100%;
            margin-bottom: 10px;
        }

        .header-table td {
            vertical-align: top;
            border: none;
        }

        .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #333;
        }

        .company-info {
            font-size: 9px;
            color: #666;
            line-height: 14px;
        }

        .report-title {
            text-align: right;
        }

        .report-period {
            font-size: 10px;
            color: #55547A;
            margin-top: 3px;
        }

        .divider {
            border: 1px solid #5851DB;
            margin: 8px 0 12px 0;
        }

        /* Summary cards */
        .summary-table {
            width: 100%;
            margin-bottom: 12px;
        }

        .summary-table td {
            padding: 8px 10px;
            border: 1px solid #E8E8E8;
            text-align: center;
            width: 20%;
        }

        .summary-label {
            font-size: 8px;
            color: #55547A;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 3px;
        }

        .summary-value {
            font-size: 13px;
            font-weight: bold;
            color: #333;
        }

        /* Ratio tables */
        .ratio-table {
            width: 100%;
            margin-bottom: 10px;
        }

        .ratio-table th {
            background: #F5F4FF;
            padding: 5px 8px;
            text-align: left;
            font-size: 9px;
            color: #55547A;
            border-bottom: 2px solid #5851DB;
            font-weight: bold;
        }

        .ratio-table th.text-right {
            text-align: right;
        }

        .ratio-table td {
            padding: 5px 8px;
            font-size: 10px;
            border-bottom: 1px solid #E8E8E8;
            vertical-align: middle;
        }

        .ratio-table td.text-right {
            text-align: right;
        }

        .ratio-table td.text-center {
            text-align: center;
        }

        .ratio-name {
            font-weight: bold;
            color: #333;
        }

        .ratio-name-sub {
            font-size: 8px;
            color: #888;
            font-weight: normal;
        }

        /* Status badges */
        .status-safe {
            color: #16a34a;
            font-weight: bold;
        }

        .status-caution {
            color: #d97706;
            font-weight: bold;
        }

        .status-danger {
            color: #dc2626;
            font-weight: bold;
        }

        /* Altman Z section */
        .z-score-box {
            width: 100%;
            margin-bottom: 10px;
        }

        .z-score-box td {
            padding: 8px 10px;
            border: 1px solid #E8E8E8;
        }

        .z-score-value {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
        }

        .z-score-label {
            font-size: 9px;
            color: #55547A;
            text-align: center;
        }

        .z-component {
            font-size: 9px;
            color: #555;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            padding-top: 8px;
            border-top: 1px solid #E8E8E8;
            text-align: center;
            font-size: 8px;
            color: #888;
        }
    </style>
</head>

<body>
    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td width="60%">
                <div class="company-name">{{ $company->name ?? 'N/A' }}</div>
                <div class="company-info">
                    @if($company && $company->address)
                        {{ $company->address->address_street_1 ?? '' }}
                        @if($company->address->city), {{ $company->address->city }}@endif
                        @if($company->address->zip) {{ $company->address->zip }}@endif
                        <br>
                    @endif
                    @if($company && $company->vat_id)
                        ЕДБ: {{ $company->vat_id }}
                    @endif
                    @if($company && $company->tax_id)
                        &nbsp;&nbsp;ЕМБС: {{ $company->tax_id }}
                    @endif
                </div>
            </td>
            <td width="40%" class="report-title">
                <h1>Финансиски Показатели</h1>
                <div style="font-size: 9px; color: #888;">Financial Ratios</div>
                <div class="report-period">
                    Период: {{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}
                    <br>
                    Споредба: {{ \Carbon\Carbon::parse($prior_date)->format('d.m.Y') }}
                </div>
            </td>
        </tr>
    </table>
    <hr class="divider">

    {{-- Summary Metrics --}}
    <h2>Резиме / Summary</h2>
    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-label">Приходи / Revenue</div>
                <div class="summary-value">{{ number_format($raw['revenue'] ?? 0, 0, ',', '.') }} ден</div>
            </td>
            <td>
                <div class="summary-label">EBITDA</div>
                <div class="summary-value">{{ number_format($raw['ebitda'] ?? 0, 0, ',', '.') }} ден</div>
            </td>
            <td>
                <div class="summary-label">Работен Капитал / Working Capital</div>
                <div class="summary-value">{{ number_format($raw['working_capital'] ?? 0, 0, ',', '.') }} ден</div>
            </td>
            <td>
                <div class="summary-label">Готовина / Cash</div>
                <div class="summary-value">{{ number_format($raw['cash'] ?? 0, 0, ',', '.') }} ден</div>
            </td>
            <td>
                <div class="summary-label">Нето Маржа / Net Margin</div>
                <div class="summary-value">{{ number_format(($ratios['profitability']['net_margin'] ?? 0) * 100, 1, ',', '.') }}%</div>
            </td>
        </tr>
    </table>

    {{-- Health Overview --}}
    <table class="ratio-table">
        <tr>
            <th>Категорија</th>
            <th class="text-right">Статус</th>
        </tr>
        @foreach(['liquidity' => 'Ликвидност', 'profitability' => 'Профитабилност', 'solvency' => 'Солвентност', 'overall' => 'Генерално (Z-Score)'] as $key => $label)
        <tr>
            <td class="ratio-name">{{ $label }}</td>
            <td class="text-right">
                @php $status = $health[$key] ?? 'danger'; @endphp
                @if($status === 'safe')
                    <span class="status-safe">&#9679; Добро</span>
                @elseif($status === 'caution')
                    <span class="status-caution">&#9679; Внимание</span>
                @else
                    <span class="status-danger">&#9679; Ризик</span>
                @endif
            </td>
        </tr>
        @endforeach
    </table>

    {{-- Liquidity Ratios --}}
    <h2>Ликвидност / Liquidity</h2>
    <table class="ratio-table">
        <tr>
            <th width="35%">Показател</th>
            <th width="20%" class="text-right">Тековна {{ \Carbon\Carbon::parse($date)->format('Y') }}</th>
            <th width="20%" class="text-right">Претходна {{ \Carbon\Carbon::parse($prior_date)->format('Y') }}</th>
            <th width="15%" class="text-right">Оптимално</th>
            <th width="10%" class="text-center">Статус</th>
        </tr>
        @php
            $liquidityRows = [
                ['name' => 'Тековна ликвидност', 'sub' => 'Current Ratio', 'key' => 'current_ratio', 'benchmark' => '≥ 1.5', 'safe' => 1.5, 'caution' => 1.0],
                ['name' => 'Брза ликвидност', 'sub' => 'Quick Ratio', 'key' => 'quick_ratio', 'benchmark' => '≥ 1.0', 'safe' => 1.0, 'caution' => 0.5],
                ['name' => 'Готовинска ликвидност', 'sub' => 'Cash Ratio', 'key' => 'cash_ratio', 'benchmark' => '≥ 0.2', 'safe' => 0.2, 'caution' => 0.1],
            ];
        @endphp
        @foreach($liquidityRows as $row)
        <tr>
            <td>
                <span class="ratio-name">{{ $row['name'] }}</span><br>
                <span class="ratio-name-sub">{{ $row['sub'] }}</span>
            </td>
            <td class="text-right">{{ number_format($ratios['liquidity'][$row['key']] ?? 0, 2, ',', '.') }}</td>
            <td class="text-right">{{ number_format($prior_ratios['liquidity'][$row['key']] ?? 0, 2, ',', '.') }}</td>
            <td class="text-right">{{ $row['benchmark'] }}</td>
            <td class="text-center">
                @php $val = $ratios['liquidity'][$row['key']] ?? 0; @endphp
                @if($val >= $row['safe'])
                    <span class="status-safe">&#9679;</span>
                @elseif($val >= $row['caution'])
                    <span class="status-caution">&#9679;</span>
                @else
                    <span class="status-danger">&#9679;</span>
                @endif
            </td>
        </tr>
        @endforeach
    </table>

    {{-- Profitability Ratios --}}
    <h2>Профитабилност / Profitability</h2>
    <table class="ratio-table">
        <tr>
            <th width="35%">Показател</th>
            <th width="20%" class="text-right">Тековна {{ \Carbon\Carbon::parse($date)->format('Y') }}</th>
            <th width="20%" class="text-right">Претходна {{ \Carbon\Carbon::parse($prior_date)->format('Y') }}</th>
            <th width="15%" class="text-right">Оптимално</th>
            <th width="10%" class="text-center">Статус</th>
        </tr>
        @php
            $profitRows = [
                ['name' => 'Бруто маржа', 'sub' => 'Gross Margin', 'key' => 'gross_margin', 'benchmark' => '≥ 30%', 'safe' => 0.3, 'caution' => 0.15, 'pct' => true],
                ['name' => 'Нето маржа', 'sub' => 'Net Margin', 'key' => 'net_margin', 'benchmark' => '≥ 10%', 'safe' => 0.1, 'caution' => 0.0, 'pct' => true],
                ['name' => 'Поврат на капитал', 'sub' => 'ROE', 'key' => 'roe', 'benchmark' => '≥ 15%', 'safe' => 0.15, 'caution' => 0.05, 'pct' => true],
                ['name' => 'Поврат на средства', 'sub' => 'ROA', 'key' => 'roa', 'benchmark' => '≥ 5%', 'safe' => 0.05, 'caution' => 0.02, 'pct' => true],
            ];
        @endphp
        @foreach($profitRows as $row)
        <tr>
            <td>
                <span class="ratio-name">{{ $row['name'] }}</span><br>
                <span class="ratio-name-sub">{{ $row['sub'] }}</span>
            </td>
            <td class="text-right">{{ number_format(($ratios['profitability'][$row['key']] ?? 0) * 100, 1, ',', '.') }}%</td>
            <td class="text-right">{{ number_format(($prior_ratios['profitability'][$row['key']] ?? 0) * 100, 1, ',', '.') }}%</td>
            <td class="text-right">{{ $row['benchmark'] }}</td>
            <td class="text-center">
                @php $val = $ratios['profitability'][$row['key']] ?? 0; @endphp
                @if($val >= $row['safe'])
                    <span class="status-safe">&#9679;</span>
                @elseif($val >= $row['caution'])
                    <span class="status-caution">&#9679;</span>
                @else
                    <span class="status-danger">&#9679;</span>
                @endif
            </td>
        </tr>
        @endforeach
        <tr>
            <td>
                <span class="ratio-name">EBITDA маржа</span><br>
                <span class="ratio-name-sub">EBITDA Margin (≈ EBIT)</span>
            </td>
            <td class="text-right">{{ number_format(($raw['ebitda_margin'] ?? 0) * 100, 1, ',', '.') }}%</td>
            <td class="text-right">{{ number_format(($prior_raw['ebitda_margin'] ?? 0) * 100, 1, ',', '.') }}%</td>
            <td class="text-right">≥ 15%</td>
            <td class="text-center">
                @php $val = $raw['ebitda_margin'] ?? 0; @endphp
                @if($val >= 0.15)
                    <span class="status-safe">&#9679;</span>
                @elseif($val >= 0.05)
                    <span class="status-caution">&#9679;</span>
                @else
                    <span class="status-danger">&#9679;</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- Solvency Ratios --}}
    <h2>Солвентност / Solvency</h2>
    <table class="ratio-table">
        <tr>
            <th width="35%">Показател</th>
            <th width="20%" class="text-right">Тековна {{ \Carbon\Carbon::parse($date)->format('Y') }}</th>
            <th width="20%" class="text-right">Претходна {{ \Carbon\Carbon::parse($prior_date)->format('Y') }}</th>
            <th width="15%" class="text-right">Оптимално</th>
            <th width="10%" class="text-center">Статус</th>
        </tr>
        <tr>
            <td>
                <span class="ratio-name">Долг/Капитал</span><br>
                <span class="ratio-name-sub">Debt-to-Equity</span>
            </td>
            <td class="text-right">{{ number_format($ratios['solvency']['debt_to_equity'] ?? 0, 2, ',', '.') }}</td>
            <td class="text-right">{{ number_format($prior_ratios['solvency']['debt_to_equity'] ?? 0, 2, ',', '.') }}</td>
            <td class="text-right">≤ 1.0</td>
            <td class="text-center">
                @php $val = $ratios['solvency']['debt_to_equity'] ?? 0; @endphp
                @if($val <= 1.0)
                    <span class="status-safe">&#9679;</span>
                @elseif($val <= 2.0)
                    <span class="status-caution">&#9679;</span>
                @else
                    <span class="status-danger">&#9679;</span>
                @endif
            </td>
        </tr>
        <tr>
            <td>
                <span class="ratio-name">Покриеност на камати</span><br>
                <span class="ratio-name-sub">Interest Coverage*</span>
            </td>
            <td class="text-right">{{ number_format($ratios['solvency']['interest_coverage'] ?? 0, 2, ',', '.') }}</td>
            <td class="text-right">{{ number_format($prior_ratios['solvency']['interest_coverage'] ?? 0, 2, ',', '.') }}</td>
            <td class="text-right">≥ 3.0</td>
            <td class="text-center">
                @php $val = $ratios['solvency']['interest_coverage'] ?? 0; @endphp
                @if($val >= 3.0)
                    <span class="status-safe">&#9679;</span>
                @elseif($val >= 1.5)
                    <span class="status-caution">&#9679;</span>
                @else
                    <span class="status-danger">&#9679;</span>
                @endif
            </td>
        </tr>
    </table>
    <div style="font-size: 8px; color: #999; margin-top: -6px;">
        * Покриеност на камати користи вкупни неоперативни расходи како приближна вредност за каматни расходи.
    </div>

    {{-- Activity Ratios --}}
    <h2>Активност / Activity</h2>
    <table class="ratio-table">
        <tr>
            <th width="35%">Показател</th>
            <th width="20%" class="text-right">Тековна {{ \Carbon\Carbon::parse($date)->format('Y') }}</th>
            <th width="20%" class="text-right">Претходна {{ \Carbon\Carbon::parse($prior_date)->format('Y') }}</th>
            <th width="15%" class="text-right">Оптимално</th>
            <th width="10%" class="text-center">Статус</th>
        </tr>
        <tr>
            <td>
                <span class="ratio-name">Денови на наплата</span><br>
                <span class="ratio-name-sub">Receivable Days</span>
            </td>
            <td class="text-right">{{ number_format($ratios['activity']['receivable_days'] ?? 0, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($prior_ratios['activity']['receivable_days'] ?? 0, 0, ',', '.') }}</td>
            <td class="text-right">≤ 45</td>
            <td class="text-center">
                @php $val = $ratios['activity']['receivable_days'] ?? 0; @endphp
                @if($val <= 45)
                    <span class="status-safe">&#9679;</span>
                @elseif($val <= 90)
                    <span class="status-caution">&#9679;</span>
                @else
                    <span class="status-danger">&#9679;</span>
                @endif
            </td>
        </tr>
        <tr>
            <td>
                <span class="ratio-name">Денови на плаќање</span><br>
                <span class="ratio-name-sub">Payable Days</span>
            </td>
            <td class="text-right">{{ number_format($ratios['activity']['payable_days'] ?? 0, 0, ',', '.') }}</td>
            <td class="text-right">{{ number_format($prior_ratios['activity']['payable_days'] ?? 0, 0, ',', '.') }}</td>
            <td class="text-right">≤ 60</td>
            <td class="text-center">
                @php $val = $ratios['activity']['payable_days'] ?? 0; @endphp
                @if($val <= 60)
                    <span class="status-safe">&#9679;</span>
                @elseif($val <= 90)
                    <span class="status-caution">&#9679;</span>
                @else
                    <span class="status-danger">&#9679;</span>
                @endif
            </td>
        </tr>
        <tr>
            <td>
                <span class="ratio-name">Обрт на залихи</span><br>
                <span class="ratio-name-sub">Inventory Turnover</span>
            </td>
            <td class="text-right">{{ number_format($ratios['activity']['inventory_turnover'] ?? 0, 2, ',', '.') }}</td>
            <td class="text-right">{{ number_format($prior_ratios['activity']['inventory_turnover'] ?? 0, 2, ',', '.') }}</td>
            <td class="text-right">≥ 4.0</td>
            <td class="text-center">
                @php $val = $ratios['activity']['inventory_turnover'] ?? 0; @endphp
                @if($val >= 4.0)
                    <span class="status-safe">&#9679;</span>
                @elseif($val >= 2.0)
                    <span class="status-caution">&#9679;</span>
                @else
                    <span class="status-danger">&#9679;</span>
                @endif
            </td>
        </tr>
    </table>

    {{-- Altman Z-Score --}}
    <h2>Altman Z-Score</h2>
    <table class="z-score-box">
        <tr>
            <td width="25%">
                @php
                    $zScore = $ratios['altman_z']['z_score'] ?? 0;
                    $zone = $ratios['altman_z']['zone'] ?? 'danger';
                @endphp
                <div class="z-score-value status-{{ $zone }}">{{ number_format($zScore, 2, ',', '.') }}</div>
                <div class="z-score-label">
                    @if($zone === 'safe')
                        Безбедна зона / Safe Zone (> 2.99)
                    @elseif($zone === 'caution')
                        Зона на внимание / Grey Zone (1.81 - 2.99)
                    @else
                        Ризична зона / Distress Zone (&lt; 1.81)
                    @endif
                </div>
            </td>
            <td width="25%">
                @php
                    $priorZ = $prior_ratios['altman_z']['z_score'] ?? 0;
                    $priorZone = $prior_ratios['altman_z']['zone'] ?? 'danger';
                @endphp
                <div class="z-score-value status-{{ $priorZone }}">{{ number_format($priorZ, 2, ',', '.') }}</div>
                <div class="z-score-label">Претходна година / Prior Year</div>
            </td>
            <td width="50%">
                <div style="font-size: 9px; font-weight: bold; color: #55547A; margin-bottom: 4px;">Компоненти / Components:</div>
                @php $components = $ratios['altman_z']['components'] ?? []; @endphp
                <div class="z-component">A (Working Capital/Assets): {{ number_format($components['A'] ?? 0, 4, ',', '.') }}</div>
                <div class="z-component">B (Retained Earnings/Assets): {{ number_format($components['B'] ?? 0, 4, ',', '.') }}</div>
                <div class="z-component">C (EBIT/Assets): {{ number_format($components['C'] ?? 0, 4, ',', '.') }}</div>
                <div class="z-component">D (Equity/Liabilities): {{ number_format($components['D'] ?? 0, 4, ',', '.') }}</div>
                <div class="z-component">E (Revenue/Assets): {{ number_format($components['E'] ?? 0, 4, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer">
        <p>Генерирано од Facturino / Generated by Facturino &mdash; {{ \Carbon\Carbon::now()->format('d.m.Y H:i') }}</p>
        <p>Z = 1.2A + 1.4B + 3.3C + 0.6D + 1.0E &nbsp;|&nbsp; Безбедно > 2.99 &nbsp;|&nbsp; Внимание 1.81-2.99 &nbsp;|&nbsp; Ризик &lt; 1.81</p>
    </div>
</body>

</html>
