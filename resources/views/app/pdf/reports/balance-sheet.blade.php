<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Биланс на состојба</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 11px;
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
            font-size: 20px;
            color: #1a1a1a;
            width: 100%;
            text-align: left;
            padding: 0px;
            margin: 0px;
        }

        .heading-date {
            font-weight: normal;
            font-size: 12px;
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
            font-size: 10px;
            color: #888;
            text-align: center;
            margin: 2px 0 15px 0;
        }

        .section-title {
            font-weight: bold;
            font-size: 13px;
            color: #1a1a1a;
            margin-top: 20px;
            margin-bottom: 8px;
            padding: 6px 10px;
            background: #f0f4f8;
            border-left: 3px solid #2c5282;
        }

        .subsection-title {
            font-weight: bold;
            font-size: 11px;
            color: #2c5282;
            padding: 4px 10px;
            margin: 0;
        }

        .accounts-table {
            width: 100%;
            margin-bottom: 5px;
        }

        .accounts-table th {
            background: #e2e8f0;
            padding: 6px 10px;
            font-size: 10px;
            font-weight: bold;
            color: #4a5568;
            text-align: center;
            border-bottom: 1px solid #cbd5e0;
        }

        .accounts-table th:first-child {
            text-align: left;
        }

        .account-row {
            border-bottom: 1px solid #edf2f7;
        }

        .account-name {
            padding: 5px 10px;
            margin: 0px;
            font-size: 11px;
            color: #2d3748;
        }

        .account-amount {
            padding: 5px 10px;
            margin: 0px;
            font-size: 11px;
            text-align: right;
            color: #2d3748;
        }

        .section-total {
            background: #edf2f7;
            border-top: 1px solid #cbd5e0;
        }

        .section-total-label {
            padding: 8px 10px;
            margin: 0px;
            font-weight: bold;
            font-size: 12px;
            color: #1a202c;
        }

        .section-total-amount {
            padding: 8px 10px;
            margin: 0px;
            font-weight: bold;
            font-size: 12px;
            text-align: right;
            color: #2c5282;
        }

        .report-footer {
            width: 100%;
            margin-top: 25px;
            padding: 10px 15px;
            background: #ebf8ff;
            box-sizing: border-box;
            border-top: 2px solid #2c5282;
        }

        .report-footer-label {
            padding: 0px;
            margin: 0px;
            text-align: left;
            font-weight: bold;
            font-size: 13px;
            color: #1a202c;
        }

        .report-footer-value {
            padding: 0px;
            margin: 0px;
            text-align: right;
            font-weight: bold;
            font-size: 15px;
            color: #2c5282;
        }

        .balance-check {
            margin-top: 15px;
            padding: 8px 15px;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
        }

        .balanced {
            background: #c6f6d5;
            color: #22543d;
            border: 1px solid #48bb78;
        }

        .unbalanced {
            background: #fed7d7;
            color: #742a2a;
            border: 1px solid #fc8181;
        }

        .col-prev {
            width: 20%;
        }

        .col-current {
            width: 20%;
        }

        .col-name {
            width: 60%;
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
                    <p class="heading-date">{{ $as_of_date }}</p>
                </td>
            </tr>
        </table>
        <p class="sub-heading-text">БИЛАНС НА СОСТОЈБА</p>
        <p class="form-label">Образец 36 — состојба на {{ $as_of_date }}</p>

        <!-- АКТИВА (ASSETS) -->
        <p class="section-title">АКТИВА</p>

        <table class="accounts-table">
            <thead>
                <tr>
                    <th class="col-name" style="text-align: left;">Позиција</th>
                    <th class="col-current">Тековна година</th>
                </tr>
            </thead>
            <tbody>
                <!-- А. НЕТЕКОВНИ СРЕДСТВА (NON-CURRENT ASSETS) -->
                <tr>
                    <td colspan="2"><p class="subsection-title">А. НЕТЕКОВНИ СРЕДСТВА</p></td>
                </tr>
                @if(isset($balanceSheet['balance_sheet']['assets']))
                    @foreach($balanceSheet['balance_sheet']['assets'] as $asset)
                    <tr class="account-row">
                        <td>
                            <p class="account-name">{{ $asset['name'] ?? $asset['account_name'] ?? 'N/A' }}</p>
                        </td>
                        <td>
                            <p class="account-amount">
                                {!! format_money_pdf(($asset['balance'] ?? 0) * 100, $currency) !!}
                            </p>
                        </td>
                    </tr>
                    @endforeach
                @endif
                <tr class="section-total">
                    <td>
                        <p class="section-total-label">ВКУПНО АКТИВА (А + Б)</p>
                    </td>
                    <td>
                        <p class="section-total-amount">
                            {!! format_money_pdf(($balanceSheet['balance_sheet']['totals']['assets'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- ПАСИВА (EQUITY & LIABILITIES) -->
        <p class="section-title">ПАСИВА</p>

        <table class="accounts-table">
            <thead>
                <tr>
                    <th class="col-name" style="text-align: left;">Позиција</th>
                    <th class="col-current">Тековна година</th>
                </tr>
            </thead>
            <tbody>
                <!-- А. ГЛАВНИНА И РЕЗЕРВИ (EQUITY) -->
                <tr>
                    <td colspan="2"><p class="subsection-title">А. ГЛАВНИНА И РЕЗЕРВИ</p></td>
                </tr>
                @if(isset($balanceSheet['balance_sheet']['equity']))
                    @foreach($balanceSheet['balance_sheet']['equity'] as $equity)
                    <tr class="account-row">
                        <td>
                            <p class="account-name">{{ $equity['name'] ?? $equity['account_name'] ?? 'N/A' }}</p>
                        </td>
                        <td>
                            <p class="account-amount">
                                {!! format_money_pdf(($equity['balance'] ?? 0) * 100, $currency) !!}
                            </p>
                        </td>
                    </tr>
                    @endforeach
                @endif
                <tr class="section-total">
                    <td>
                        <p class="section-total-label">Вкупно главнина и резерви</p>
                    </td>
                    <td>
                        <p class="section-total-amount">
                            {!! format_money_pdf(($balanceSheet['balance_sheet']['totals']['equity'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                </tr>

                <!-- Б. ОБВРСКИ (LIABILITIES) -->
                <tr>
                    <td colspan="2"><p class="subsection-title">Б. ДОЛГОРОЧНИ И КРАТКОРОЧНИ ОБВРСКИ</p></td>
                </tr>
                @if(isset($balanceSheet['balance_sheet']['liabilities']))
                    @foreach($balanceSheet['balance_sheet']['liabilities'] as $liability)
                    <tr class="account-row">
                        <td>
                            <p class="account-name">{{ $liability['name'] ?? $liability['account_name'] ?? 'N/A' }}</p>
                        </td>
                        <td>
                            <p class="account-amount">
                                {!! format_money_pdf(($liability['balance'] ?? 0) * 100, $currency) !!}
                            </p>
                        </td>
                    </tr>
                    @endforeach
                @endif
                <tr class="section-total">
                    <td>
                        <p class="section-total-label">Вкупно обврски</p>
                    </td>
                    <td>
                        <p class="section-total-amount">
                            {!! format_money_pdf(($balanceSheet['balance_sheet']['totals']['liabilities'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>

        <!-- ВКУПНО ПАСИВА -->
        <table class="report-footer">
            <tr>
                <td>
                    <p class="report-footer-label">ВКУПНО ПАСИВА (А + Б)</p>
                </td>
                <td>
                    <p class="report-footer-value">
                        {!! format_money_pdf((($balanceSheet['balance_sheet']['totals']['liabilities'] ?? 0) + ($balanceSheet['balance_sheet']['totals']['equity'] ?? 0)) * 100, $currency) !!}
                    </p>
                </td>
            </tr>
        </table>

        @php
            $totalAssets = $balanceSheet['balance_sheet']['totals']['assets'] ?? 0;
            $totalPassiva = ($balanceSheet['balance_sheet']['totals']['liabilities'] ?? 0) + ($balanceSheet['balance_sheet']['totals']['equity'] ?? 0);
            $isBalanced = abs($totalAssets - $totalPassiva) < 0.01;
        @endphp
        <div class="balance-check {{ $isBalanced ? 'balanced' : 'unbalanced' }}">
            @if($isBalanced)
                ✓ Актива = Пасива ({{ number_format($totalAssets, 2) }})
            @else
                ✗ Актива ({{ number_format($totalAssets, 2) }}) ≠ Пасива ({{ number_format($totalPassiva, 2) }})
            @endif
        </div>
    </div>
</body>

</html>

<!-- CLAUDE-CHECKPOINT -->
