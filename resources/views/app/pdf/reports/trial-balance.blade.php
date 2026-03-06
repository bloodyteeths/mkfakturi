<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Бруто биланс</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
            color: #333;
        }

        table {
            border-collapse: collapse;
        }

        .sub-container {
            padding: 0px 10px;
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

        .accounts-table {
            margin-top: 10px;
            width: 100%;
            border: 1px solid #cbd5e0;
        }

        .table-header {
            background: #e2e8f0;
            border-bottom: 2px solid #a0aec0;
        }

        .table-header-text {
            padding: 5px 3px;
            margin: 0px;
            font-weight: bold;
            font-size: 8px;
            color: #2d3748;
            text-align: center;
        }

        .group-header {
            background: #edf2f7;
            border-bottom: 1px solid #a0aec0;
            border-top: 1px solid #a0aec0;
        }

        .group-header-text {
            padding: 4px 3px;
            margin: 0px;
            font-weight: bold;
            font-size: 8px;
            color: #4a5568;
            text-align: center;
        }

        .account-row {
            border-bottom: 1px solid #edf2f7;
        }

        .account-row:nth-child(even) {
            background: #f7fafc;
        }

        .account-code {
            padding: 4px 4px;
            margin: 0px;
            font-size: 9px;
            color: #4a5568;
            font-weight: bold;
        }

        .account-name {
            padding: 4px 4px;
            margin: 0px;
            font-size: 9px;
            color: #2d3748;
        }

        .account-amount {
            padding: 4px 3px;
            margin: 0px;
            font-size: 9px;
            text-align: right;
            color: #2d3748;
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
            padding: 6px 3px;
            margin: 0px;
            font-weight: bold;
            font-size: 9px;
            text-align: right;
            color: #2c5282;
        }

        .balanced-indicator {
            margin-top: 10px;
            padding: 8px 10px;
            background: #c6f6d5;
            border: 1px solid #48bb78;
            text-align: center;
        }

        .unbalanced-indicator {
            margin-top: 10px;
            padding: 8px 10px;
            background: #fed7d7;
            border: 1px solid #fc8181;
            text-align: center;
        }

        .indicator-text {
            padding: 0px;
            margin: 0px;
            font-weight: bold;
            font-size: 10px;
        }

        .signature-section {
            margin-top: 30px;
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
    </style>

    @if (App::isLocale('th'))
    @include('app.pdf.locale.th')
    @endif
</head>

<body>
    <div class="sub-container">
        @include('app.pdf.reports._company-header')

        <p class="sub-heading-text">БРУТО БИЛАНС</p>
        <p class="form-label">
            Период: {{ $from_date ?? '' }} - {{ $to_date ?? '' }}
        </p>

        <table class="accounts-table">
            <thead>
                <tr class="table-header">
                    <th rowspan="2" style="text-align: left; width: 6%; border-right: 1px solid #a0aec0;">
                        <p class="table-header-text" style="text-align: left;">Шифра</p>
                    </th>
                    <th rowspan="2" style="text-align: left; width: 24%; border-right: 1px solid #a0aec0;">
                        <p class="table-header-text" style="text-align: left;">Назив на сметка</p>
                    </th>
                    <th colspan="2" style="border-right: 1px solid #a0aec0; border-bottom: 1px solid #a0aec0;">
                        <p class="table-header-text">Почетно салдо</p>
                    </th>
                    <th colspan="2" style="border-right: 1px solid #a0aec0; border-bottom: 1px solid #a0aec0;">
                        <p class="table-header-text">Промет во период</p>
                    </th>
                    <th colspan="2">
                        <p class="table-header-text">Крајно салдо</p>
                    </th>
                </tr>
                <tr class="group-header">
                    <th style="width: 10%; border-right: 1px solid #cbd5e0;">
                        <p class="group-header-text">Дугува</p>
                    </th>
                    <th style="width: 10%; border-right: 1px solid #a0aec0;">
                        <p class="group-header-text">Побарува</p>
                    </th>
                    <th style="width: 10%; border-right: 1px solid #cbd5e0;">
                        <p class="group-header-text">Дугува</p>
                    </th>
                    <th style="width: 10%; border-right: 1px solid #a0aec0;">
                        <p class="group-header-text">Побарува</p>
                    </th>
                    <th style="width: 10%; border-right: 1px solid #cbd5e0;">
                        <p class="group-header-text">Дугува</p>
                    </th>
                    <th style="width: 10%;">
                        <p class="group-header-text">Побарува</p>
                    </th>
                </tr>
            </thead>
            <tbody>
                @if(isset($trialBalance['accounts']))
                    @foreach($trialBalance['accounts'] as $account)
                    <tr class="account-row">
                        <td>
                            <p class="account-code">{{ $account['code'] ?? '-' }}</p>
                        </td>
                        <td>
                            <p class="account-name">{{ $account['name'] ?? '-' }}</p>
                        </td>
                        <td>
                            <p class="account-amount">
                                @if(($account['opening_debit'] ?? 0) > 0)
                                    {!! format_money_pdf(($account['opening_debit'] ?? 0) * 100, $currency) !!}
                                @endif
                            </p>
                        </td>
                        <td>
                            <p class="account-amount">
                                @if(($account['opening_credit'] ?? 0) > 0)
                                    {!! format_money_pdf(($account['opening_credit'] ?? 0) * 100, $currency) !!}
                                @endif
                            </p>
                        </td>
                        <td>
                            <p class="account-amount">
                                @if(($account['period_debit'] ?? 0) > 0)
                                    {!! format_money_pdf(($account['period_debit'] ?? 0) * 100, $currency) !!}
                                @endif
                            </p>
                        </td>
                        <td>
                            <p class="account-amount">
                                @if(($account['period_credit'] ?? 0) > 0)
                                    {!! format_money_pdf(($account['period_credit'] ?? 0) * 100, $currency) !!}
                                @endif
                            </p>
                        </td>
                        <td>
                            <p class="account-amount">
                                @if(($account['closing_debit'] ?? 0) > 0)
                                    {!! format_money_pdf(($account['closing_debit'] ?? 0) * 100, $currency) !!}
                                @endif
                            </p>
                        </td>
                        <td>
                            <p class="account-amount">
                                @if(($account['closing_credit'] ?? 0) > 0)
                                    {!! format_money_pdf(($account['closing_credit'] ?? 0) * 100, $currency) !!}
                                @endif
                            </p>
                        </td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="2">
                        <p class="total-label">ВКУПНО</p>
                    </td>
                    <td>
                        <p class="total-amount">
                            {!! format_money_pdf(($trialBalance['totals']['opening_debit'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                    <td>
                        <p class="total-amount">
                            {!! format_money_pdf(($trialBalance['totals']['opening_credit'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                    <td>
                        <p class="total-amount">
                            {!! format_money_pdf(($trialBalance['totals']['period_debit'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                    <td>
                        <p class="total-amount">
                            {!! format_money_pdf(($trialBalance['totals']['period_credit'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                    <td>
                        <p class="total-amount">
                            {!! format_money_pdf(($trialBalance['totals']['closing_debit'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                    <td>
                        <p class="total-amount">
                            {!! format_money_pdf(($trialBalance['totals']['closing_credit'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                </tr>
            </tfoot>
        </table>

        @if($trialBalance['is_balanced'] ?? false)
        <div class="balanced-indicator">
            <p class="indicator-text" style="color: #22543d;">
                &#10003; Бруто билансот е балансиран
            </p>
        </div>
        @else
        <div class="unbalanced-indicator">
            <p class="indicator-text" style="color: #742a2a;">
                &#10007; Бруто билансот НЕ е балансиран
            </p>
        </div>
        @endif

        {{-- Signature section --}}
        <table class="signature-section">
            <tr>
                <td style="width: 50%; text-align: center; padding-top: 40px;">
                    <p class="signature-label">Составил</p>
                </td>
                <td style="width: 50%; text-align: center; padding-top: 40px;">
                    <p class="signature-label">Одговорно лице</p>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
