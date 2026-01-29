<!DOCTYPE html>
<html>
<head>
    <title>@lang('pdf_payment_label') - {{ $payment->payment_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
        * {
            box-sizing: border-box;
        }
        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 25px 30px;
            width: 100%;
        }
        .header-row {
            width: 100%;
            margin-bottom: 20px;
        }
        .company-block {
            width: 55%;
            float: left;
        }
        .payment-info-block {
            width: 40%;
            float: right;
            text-align: right;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .company-details {
            font-size: 10px;
            line-height: 1.5;
            color: #333;
        }
        .payment-title {
            font-size: 20px;
            font-weight: bold;
            color: #5851D8;
            margin-bottom: 10px;
        }
        .payment-meta {
            font-size: 10px;
            line-height: 1.6;
        }
        .payment-meta strong {
            color: #333;
        }
        .clearfix {
            clear: both;
        }
        .divider {
            border-top: 2px solid #5851D8;
            margin: 15px 0;
        }
        .main-title {
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            color: #5851D8;
            margin: 25px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-section {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-left {
            width: 48%;
            float: left;
        }
        .info-right {
            width: 48%;
            float: right;
        }
        .section-label {
            font-size: 10px;
            color: #666;
            margin-bottom: 5px;
        }
        .customer-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .customer-details {
            font-size: 10px;
            line-height: 1.5;
        }
        .detail-row {
            width: 100%;
            margin-bottom: 5px;
        }
        .detail-label {
            display: inline-block;
            width: 130px;
            color: #666;
        }
        .detail-value {
            display: inline;
        }
        .amount-box {
            width: 50%;
            margin-left: auto;
            margin-top: 30px;
            border: 2px solid #5851D8;
        }
        .amount-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .amount-box td {
            padding: 12px 15px;
        }
        .amount-label {
            font-weight: bold;
            background: #f5f5f5;
            text-align: right;
        }
        .amount-value {
            text-align: right;
            font-weight: bold;
            font-size: 16px;
            color: #5851D8;
        }
        .notes-box {
            margin-top: 25px;
            padding: 12px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        .notes-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }
    </style>
    @if (App::isLocale('th'))
        @include('app.pdf.locale.th')
    @endif
</head>
<body>
    {{-- Header Row: Company on left, Payment info on right --}}
    <div class="header-row">
        <div class="company-block">
            @if ($logo)
                <img style="height: 50px; margin-bottom: 10px;" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Logo">
            @endif
            <div class="company-name">{{ $payment->company->name ?? '' }}</div>
            <div class="company-details">
                @php
                    $companyAddress = $company_address
                        ? $company_address
                        : ($payment->company && $payment->company->address
                            ? $payment->company->address
                            : ($payment->company ? $payment->company->address()->first() : null));
                @endphp
                @if (is_string($companyAddress))
                    {!! str_replace('<br />', '<br>', $companyAddress) !!}
                @elseif ($companyAddress)
                    {{ $companyAddress->address_street_1 ?? '' }}<br>
                    @if($companyAddress->address_street_2){{ $companyAddress->address_street_2 }}<br>@endif
                    @if($companyAddress->zip){{ $companyAddress->zip }} @endif{{ $companyAddress->city ?? '' }}<br>
                    {{ $companyAddress->country->name ?? '' }}
                @endif
                @if ($payment->company && $payment->company->vat_id)
                    <br><strong>ЕДБ:</strong> {{ $payment->company->vat_id }}
                @endif
                @if ($payment->company && $payment->company->tax_id)
                    <br><strong>ЕМБС:</strong> {{ $payment->company->tax_id }}
                @endif
            </div>
        </div>
        <div class="payment-info-block">
            <div class="payment-title">@lang('pdf_payment_label')</div>
            <div class="payment-meta">
                <strong>@lang('pdf_payment_number'):</strong> {{ $payment->payment_number }}<br>
                <strong>@lang('pdf_payment_date'):</strong> {{ $payment->formattedPaymentDate }}
            </div>
        </div>
    </div>
    <div class="clearfix"></div>

    <div class="divider"></div>

    {{-- Main Title --}}
    <div class="main-title">@lang('pdf_payment_receipt_label')</div>

    {{-- Customer and Payment Details --}}
    <div class="info-section">
        <div class="info-left">
            <div class="section-label">@lang('pdf_received_from'):</div>
            <div class="customer-name">{{ $payment->customer->name ?? '' }}</div>
            <div class="customer-details">
                @php
                    $customerAddress = $billing_address
                        ? $billing_address
                        : ($payment->customer ? $payment->customer->billingAddress : null);
                @endphp
                @if (is_string($customerAddress))
                    {!! str_replace('<br />', '<br>', $customerAddress) !!}
                @elseif ($customerAddress)
                    {{ $customerAddress->address_street_1 ?? '' }}<br>
                    @if($customerAddress->address_street_2){{ $customerAddress->address_street_2 }}<br>@endif
                    @if($customerAddress->zip){{ $customerAddress->zip }} @endif{{ $customerAddress->city ?? '' }}<br>
                    {{ $customerAddress->country->name ?? '' }}
                @endif
                @if ($payment->customer && $payment->customer->email)
                    <br>{{ $payment->customer->email }}
                @endif
                @if ($payment->customer && $payment->customer->vat_number)
                    <br><strong>ЕДБ:</strong> {{ $payment->customer->vat_number }}
                @endif
                @if ($payment->customer && $payment->customer->tax_id)
                    <br><strong>ЕМБС:</strong> {{ $payment->customer->tax_id }}
                @endif
            </div>
        </div>
        <div class="info-right">
            <div class="detail-row">
                <span class="detail-label">@lang('pdf_payment_mode')</span>
                <span class="detail-value">{{ $payment->paymentMethod ? $payment->paymentMethod->name : '-' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">@lang('pdf_invoice_label')</span>
                <span class="detail-value">{{ $payment->invoice && $payment->invoice->invoice_number ? $payment->invoice->invoice_number : '-' }}</span>
            </div>
        </div>
    </div>
    <div class="clearfix"></div>

    {{-- Amount Box --}}
    <div class="amount-box">
        <table>
            <tr>
                <td class="amount-label">@lang('pdf_payment_amount_received_label')</td>
                <td class="amount-value">{!! format_money_pdf($payment->amount, $payment->customer->currency ?? null) !!}</td>
            </tr>
        </table>
    </div>

    {{-- Notes --}}
    @if ($notes)
        <div class="notes-box">
            <div class="notes-title">@lang('pdf_notes'):</div>
            {!! $notes !!}
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <p>{{ $payment->company->name ?? '' }} | {{ $payment->formattedPaymentDate }}</p>
    </div>
</body>
{{-- CLAUDE-CHECKPOINT --}}
</html>
