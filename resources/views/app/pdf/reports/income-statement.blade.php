<!DOCTYPE html>
<html lang="en">

<head>
    <title>@lang('pdf_income_statement_label')</title>
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

        .heading-date-range {
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

        .section-title {
            font-weight: bold;
            font-size: 18px;
            color: #5851D8;
            margin-top: 30px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #5851D8;
        }

        .accounts-table {
            width: 100%;
            margin-bottom: 10px;
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

        .section-total {
            background: #F9FBFF;
            border-top: 1px solid #EAF1FB;
            padding: 12px 0px;
        }

        .section-total-label {
            padding: 10px;
            margin: 0px;
            font-weight: bold;
            font-size: 15px;
            color: #595959;
        }

        .section-total-amount {
            padding: 10px;
            margin: 0px;
            font-weight: bold;
            font-size: 15px;
            text-align: right;
            color: #5851D8;
        }

        .report-footer {
            width: 100%;
            margin-top: 40px;
            padding: 15px 20px;
            background: #F9FBFF;
            box-sizing: border-box;
            border-top: 3px solid #5851D8;
        }

        .report-footer-label {
            padding: 0px;
            margin: 0px;
            text-align: left;
            font-weight: bold;
            font-size: 16px;
            line-height: 21px;
            color: #595959;
        }

        .report-footer-value {
            padding: 0px;
            margin: 0px;
            text-align: right;
            font-weight: bold;
            font-size: 20px;
            line-height: 21px;
            color: #5851D8;
        }

        .profit-positive {
            color: #4CAF50 !important;
        }

        .profit-negative {
            color: #F44336 !important;
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
            <tr>
                <td colspan="2">
                    <p class="sub-heading-text">@lang('pdf_income_statement_label')</p>
                </td>
            </tr>
        </table>

        <!-- REVENUE SECTION -->
        <p class="section-title">@lang('pdf_revenue_label')</p>
        <table class="accounts-table">
            @if(isset($incomeStatement['income_statement']['revenues']))
                @foreach($incomeStatement['income_statement']['revenues'] as $revenue)
                <tr class="account-row">
                    <td style="width: 70%;">
                        <p class="account-name">{{ $revenue['name'] ?? $revenue['account_name'] ?? 'N/A' }}</p>
                    </td>
                    <td style="width: 30%;">
                        <p class="account-amount">
                            {!! format_money_pdf(($revenue['balance'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                </tr>
                @endforeach
            @endif
            <tr class="section-total">
                <td>
                    <p class="section-total-label">@lang('pdf_total_revenue_label')</p>
                </td>
                <td>
                    <p class="section-total-amount">
                        {!! format_money_pdf(($incomeStatement['income_statement']['totals']['revenue'] ?? 0) * 100, $currency) !!}
                    </p>
                </td>
            </tr>
        </table>

        <!-- EXPENSES SECTION -->
        <p class="section-title">@lang('pdf_expenses_label')</p>
        <table class="accounts-table">
            @if(isset($incomeStatement['income_statement']['expenses']))
                @foreach($incomeStatement['income_statement']['expenses'] as $expense)
                <tr class="account-row">
                    <td style="width: 70%;">
                        <p class="account-name">{{ $expense['name'] ?? $expense['account_name'] ?? 'N/A' }}</p>
                    </td>
                    <td style="width: 30%;">
                        <p class="account-amount">
                            {!! format_money_pdf(($expense['balance'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                </tr>
                @endforeach
            @endif
            <tr class="section-total">
                <td>
                    <p class="section-total-label">@lang('pdf_total_expenses_label')</p>
                </td>
                <td>
                    <p class="section-total-amount">
                        {!! format_money_pdf(($incomeStatement['income_statement']['totals']['expenses'] ?? 0) * 100, $currency) !!}
                    </p>
                </td>
            </tr>
        </table>

        <!-- NET INCOME/PROFIT -->
        @php
            $revenue = $incomeStatement['income_statement']['totals']['revenue'] ?? 0;
            $expenses = $incomeStatement['income_statement']['totals']['expenses'] ?? 0;
            $netIncome = $revenue - $expenses;
            $profitClass = $netIncome >= 0 ? 'profit-positive' : 'profit-negative';
        @endphp
        <table class="report-footer">
            <tr>
                <td>
                    <p class="report-footer-label">
                        @if($netIncome >= 0)
                            @lang('pdf_net_income_label')
                        @else
                            @lang('pdf_net_loss_label')
                        @endif
                    </p>
                </td>
                <td>
                    <p class="report-footer-value {{ $profitClass }}">
                        {!! format_money_pdf($netIncome * 100, $currency) !!}
                    </p>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>

<!-- CLAUDE-CHECKPOINT -->
