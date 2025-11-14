<!DOCTYPE html>
<html lang="en">

<head>
    <title>@lang('pdf_trial_balance_label')</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
        }

        table {
            border-collapse: collapse;
        }

        .sub-container {
            padding: 0px 20px;
        }

        .report-header {
            width: 100%;
        }

        .heading-text {
            font-weight: bold;
            font-size: 24px;
            color: #5851D8;
            width: 100%;
            text-align: left;
            padding: 0px;
            margin: 0px;
        }

        .heading-date {
            font-weight: normal;
            font-size: 15px;
            color: #A5ACC1;
            width: 100%;
            text-align: right;
            padding: 0px;
            margin: 0px;
        }

        .sub-heading-text {
            font-weight: normal;
            font-size: 16px;
            color: #595959;
            padding: 0px;
            margin: 0px;
            margin-top: 6px;
        }

        .accounts-table {
            margin-top: 30px;
            width: 100%;
            border-top: 2px solid #EAF1FB;
        }

        .table-header {
            background: #F9FBFF;
            padding: 10px 0px;
            border-bottom: 1px solid #EAF1FB;
        }

        .table-header-text {
            padding: 10px;
            margin: 0px;
            font-weight: bold;
            font-size: 14px;
            color: #595959;
        }

        .account-row {
            border-bottom: 1px solid #F9FBFF;
        }

        .account-name {
            padding: 8px 10px;
            margin: 0px;
            font-size: 13px;
            color: #040405;
        }

        .account-amount {
            padding: 8px 10px;
            margin: 0px;
            font-size: 13px;
            text-align: right;
            color: #040405;
        }

        .total-row {
            background: #F9FBFF;
            border-top: 2px solid #5851D8;
            padding: 15px 0px;
        }

        .total-label {
            padding: 10px;
            margin: 0px;
            font-weight: bold;
            font-size: 16px;
            color: #040405;
        }

        .total-amount {
            padding: 10px;
            margin: 0px;
            font-weight: bold;
            font-size: 16px;
            text-align: right;
            color: #5851D8;
        }

        .balanced-indicator {
            margin-top: 20px;
            padding: 15px 20px;
            background: #E8F5E9;
            border-left: 4px solid #4CAF50;
            text-align: center;
        }

        .unbalanced-indicator {
            margin-top: 20px;
            padding: 15px 20px;
            background: #FFEBEE;
            border-left: 4px solid #F44336;
            text-align: center;
        }

        .indicator-text {
            padding: 0px;
            margin: 0px;
            font-weight: bold;
            font-size: 14px;
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
            <tr>
                <td colspan="2">
                    <p class="sub-heading-text">@lang('pdf_trial_balance_label')</p>
                </td>
            </tr>
        </table>

        <table class="accounts-table">
            <thead>
                <tr class="table-header">
                    <th style="text-align: left; width: 50%;">
                        <p class="table-header-text">@lang('pdf_account_name_label')</p>
                    </th>
                    <th style="text-align: right; width: 25%;">
                        <p class="table-header-text">@lang('pdf_debit_label')</p>
                    </th>
                    <th style="text-align: right; width: 25%;">
                        <p class="table-header-text">@lang('pdf_credit_label')</p>
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
                    </tr>
                    @endforeach
                @endif
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td>
                        <p class="total-label">@lang('pdf_total_label')</p>
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
            <p class="indicator-text" style="color: #4CAF50;">
                ✓ @lang('pdf_trial_balance_balanced_label')
            </p>
        </div>
        @else
        <div class="unbalanced-indicator">
            <p class="indicator-text" style="color: #F44336;">
                ✗ @lang('pdf_trial_balance_unbalanced_label')
            </p>
        </div>
        @endif
    </div>
</body>

</html>

<!-- CLAUDE-CHECKPOINT -->
