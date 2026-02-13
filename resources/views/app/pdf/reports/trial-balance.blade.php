<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Бруто биланс</title>
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

        .accounts-table {
            margin-top: 15px;
            width: 100%;
            border: 1px solid #cbd5e0;
        }

        .table-header {
            background: #e2e8f0;
            border-bottom: 2px solid #a0aec0;
        }

        .table-header-text {
            padding: 8px 6px;
            margin: 0px;
            font-weight: bold;
            font-size: 10px;
            color: #2d3748;
            text-align: center;
        }

        .account-row {
            border-bottom: 1px solid #edf2f7;
        }

        .account-row:nth-child(even) {
            background: #f7fafc;
        }

        .account-name {
            padding: 5px 8px;
            margin: 0px;
            font-size: 10px;
            color: #2d3748;
        }

        .account-amount {
            padding: 5px 6px;
            margin: 0px;
            font-size: 10px;
            text-align: right;
            color: #2d3748;
        }

        .total-row {
            background: #e2e8f0;
            border-top: 2px solid #2c5282;
        }

        .total-label {
            padding: 8px;
            margin: 0px;
            font-weight: bold;
            font-size: 12px;
            color: #1a202c;
        }

        .total-amount {
            padding: 8px 6px;
            margin: 0px;
            font-weight: bold;
            font-size: 11px;
            text-align: right;
            color: #2c5282;
        }

        .balanced-indicator {
            margin-top: 15px;
            padding: 10px 15px;
            background: #c6f6d5;
            border: 1px solid #48bb78;
            text-align: center;
        }

        .unbalanced-indicator {
            margin-top: 15px;
            padding: 10px 15px;
            background: #fed7d7;
            border: 1px solid #fc8181;
            text-align: center;
        }

        .indicator-text {
            padding: 0px;
            margin: 0px;
            font-weight: bold;
            font-size: 11px;
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
        <p class="sub-heading-text">БРУТО БИЛАНС</p>
        <p class="form-label">Состојба на {{ $as_of_date }}</p>

        <table class="accounts-table">
            <thead>
                <tr class="table-header">
                    <th style="text-align: left; width: 40%;">
                        <p class="table-header-text" style="text-align: left;">Назив на сметка</p>
                    </th>
                    <th style="width: 15%;">
                        <p class="table-header-text">Дугува</p>
                    </th>
                    <th style="width: 15%;">
                        <p class="table-header-text">Побарува</p>
                    </th>
                    <th style="width: 15%;">
                        <p class="table-header-text">Салдо Дугува</p>
                    </th>
                    <th style="width: 15%;">
                        <p class="table-header-text">Салдо Побарува</p>
                    </th>
                </tr>
            </thead>
            <tbody>
                @if(isset($trialBalance['trial_balance']['accounts']))
                    @foreach($trialBalance['trial_balance']['accounts'] as $account)
                    <tr class="account-row">
                        <td>
                            <p class="account-name">{{ $account['name'] ?? $account['account_name'] ?? 'N/A' }}</p>
                        </td>
                        <td>
                            <p class="account-amount">
                                @if(isset($account['debit']) && $account['debit'] > 0)
                                    {!! format_money_pdf($account['debit'] * 100, $currency) !!}
                                @else
                                    -
                                @endif
                            </p>
                        </td>
                        <td>
                            <p class="account-amount">
                                @if(isset($account['credit']) && $account['credit'] > 0)
                                    {!! format_money_pdf($account['credit'] * 100, $currency) !!}
                                @else
                                    -
                                @endif
                            </p>
                        </td>
                        <td>
                            <p class="account-amount">
                                @if(isset($account['balance']) && $account['balance'] > 0)
                                    {!! format_money_pdf($account['balance'] * 100, $currency) !!}
                                @else
                                    -
                                @endif
                            </p>
                        </td>
                        <td>
                            <p class="account-amount">
                                @if(isset($account['balance']) && $account['balance'] < 0)
                                    {!! format_money_pdf(abs($account['balance']) * 100, $currency) !!}
                                @else
                                    -
                                @endif
                            </p>
                        </td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td>
                        <p class="total-label">ВКУПНО</p>
                    </td>
                    <td>
                        <p class="total-amount">
                            {!! format_money_pdf(($trialBalance['trial_balance']['total_debits'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                    <td>
                        <p class="total-amount">
                            {!! format_money_pdf(($trialBalance['trial_balance']['total_credits'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                    <td>
                        <p class="total-amount">
                            {!! format_money_pdf(($trialBalance['trial_balance']['total_debits'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                    <td>
                        <p class="total-amount">
                            {!! format_money_pdf(($trialBalance['trial_balance']['total_credits'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                </tr>
            </tfoot>
        </table>

        @if(isset($trialBalance['trial_balance']['is_balanced']) && $trialBalance['trial_balance']['is_balanced'])
        <div class="balanced-indicator">
            <p class="indicator-text" style="color: #22543d;">
                ✓ Бруто билансот е балансиран
            </p>
        </div>
        @else
        <div class="unbalanced-indicator">
            <p class="indicator-text" style="color: #742a2a;">
                ✗ Бруто билансот НЕ е балансиран
            </p>
        </div>
        @endif
    </div>
</body>

</html>

<!-- CLAUDE-CHECKPOINT -->
