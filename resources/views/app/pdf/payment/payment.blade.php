<!DOCTYPE html>
<html>

<head>
    <title>@lang('pdf_payment_label') - {{ $payment->payment_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style type="text/css">
        /* -- Base -- */
        body {
            font-family: "DejaVu Sans";
        }

        html {
            margin: 0px;
            padding: 0px;
            margin-top: 50px;
            margin-bottom: 50px;
        }

        table {
            border-collapse: collapse;
        }

        hr {
            color: rgba(0, 0, 0, 0.2);
            border: 0.5px solid #EAF1FB;
            margin: 50px 0px;
        }

        /* -- Heeader -- */

        .header-container {
            /* position: absolute; */
            width: 100%;
            padding: 0 30px;
            margin-bottom: 50px;
            /* height: 150px;
            left: 0px;
            top: -60px; */
        }

        /* .header-section-left {
            padding-top: 45px;
            padding-bottom: 45px;
            padding-left: 30px;
            display:inline-block;
            width:30%;
        } */

        .header-logo {
            /* position: absolute; */
            text-transform: capitalize;
            color: #7675ff;
            padding-top: 0px;
        }

        .company-address-container {
            width: 50%;
            text-transform: capitalize;
            padding-left: 80px;
            margin-bottom: 2px;
        }

        /* .header-section-right {
            display: inline-block;
            position: absolute;
            right: 0;
            padding: 15px 30px 15px 0px;
            float: right;
        } */

        .header-section-right {
            text-align: right;
        }

        .header {
            font-size: 20px;
            color: rgba(0, 0, 0, 0.7);
        }

        /* -- Company Address -- */

        .company-details h1 {
            margin: 0;

            font-weight: bold;
            font-size: 15px;
            line-height: 22px;
            letter-spacing: 0.05em;
            text-align: left;
            max-width: 220px;
        }

        .company-address {
            margin-top: 0px;
            font-size: 12px;
            line-height: 15px;
            padding-right: 60px;
            color: #595959;
            word-wrap: break-word;
        }

        .content-wrapper {
            display: block;
            height: 200px;
        }

        .main-content {
            display: inline-block;
            padding-top: 20px
        }

        /* -- Customer Address -- */
        .customer-address-container {
            display: block;
            float: left;
            width: 40%;
            padding: 0 0 0 30px;
        }

        /* -- Shipping -- */

        .shipping-address-label {
            padding-top: 5px;
            font-size: 12px;
            line-height: 18px;
            margin-bottom: 0px;
        }

        .shipping-address-name {
            padding: 0px;
            font-size: 15px;
            line-height: 22px;
            margin: 0px;
        }

        .shipping-address {
            font-size: 10px;
            line-height: 15px;
            color: #595959;
            margin: 0px;
            width: 160px;
        }

        /* -- Billing -- */

        .billing-address-container {
            display: block;
            float: left;
        }

        .billing-address-container--right {
            float: right;
        }

        .billing-address-label {
            padding-top: 5px;
            font-size: 12px;
            line-height: 18px;
            margin-bottom: 0px;
            color: #55547A;
        }

        .billing-address-name {
            padding: 0px;
            font-size: 15px;
            line-height: 22px;
            margin: 0px;
        }

        .billing-address {
            font-size: 10px;
            line-height: 15px;
            color: #595959;
            margin: 0px;
            width: 180px;
            word-wrap: break-word;
        }

        /* -- Payment Details -- */

        .payment-details-container {
            display: inline;
            position: absolute;
            width: 40%;
            height: 120px;
            left: 440px;
            padding: 5px 10px 0 0;
        }

        .attribute-label {
            font-size: 12px;
            line-height: 18px;
            text-align: left;
            color: #55547A
        }

        .attribute-value {
            font-size: 12px;
            line-height: 18px;
            text-align: right;
        }

        /* -- Notes -- */

        .notes {
            font-size: 12px;
            color: #595959;
            margin-top: 100px;
            margin-left: 30px;
            width: 90%;
            text-align: left;
            page-break-before: avoid;
        }

        .notes-label {
            font-size: 15px;
            line-height: 22px;
            letter-spacing: 0.05em;
            color: #040405;
            width: 108px;
            white-space: nowrap;
            height: 19.87px;
            padding-bottom: 10px;
        }

        .content-heading {
            margin-top: 10px;
            width: 100%;
            text-align: center;
        }

        p {
            padding: 0 0 0 0;
            margin: 0 0 0 0;
        }

        .content-heading span {
            font-weight: normal;
            font-size: 14px;
            line-height: 25px;
            padding-bottom: 5px;
            border-bottom: 1px solid #B9C1D1;
        }

        /* -- Total Display Box -- */

        .total-display-box {
            min-width: 315px;
            display: block;
            margin-right: 30px;
            background: #F9FBFF;
            border: 1px solid #EAF1FB;
            box-sizing: border-box;
            float: right;
            padding: 12px 15px 15px 15px;
        }

        .total-display-label {
            display: inline;
            font-weight: bold;
            font-size: 14px;
            line-height: 21px;
            color: #595959;
        }

        .total-display-box .amount {
            float: right;
            font-weight: bold;
            font-size: 14px;
            line-height: 21px;
            text-align: right;
            color: #5851D8;
            margin-left: 150px;
        }

    </style>

    @if (App::isLocale('th'))
        @include('app.pdf.locale.th')
    @endif
</head>

<body>
    <div class="header-container">
        <table width="100%">
            <tr>
                <td width="50%" class="header-section-left" style="vertical-align: top;">
                    @if ($logo)
                        <img style="height:50px; margin-bottom: 10px;" class="header-logo" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="@lang('pdf_company_logo')">
                    @endif
                    <div class="company-details">
                        <h1 style="margin: 0 0 5px 0; font-size: 16px;">{{ $payment->company->name ?? '' }}</h1>
                        @if ($company_address)
                            <div class="company-address" style="padding-right: 0;">
                                {!! $company_address !!}
                            </div>
                        @else
                            {{-- Fallback: show company details if no formatted address --}}
                            @php
                                $companyAddress = $payment->company && $payment->company->address
                                    ? $payment->company->address
                                    : ($payment->company ? $payment->company->address()->first() : null);
                            @endphp
                            @if ($companyAddress)
                                <div class="company-address" style="padding-right: 0;">
                                    @if ($companyAddress->address_street_1)
                                        <p>{{ $companyAddress->address_street_1 }}</p>
                                    @endif
                                    @if ($companyAddress->address_street_2)
                                        <p>{{ $companyAddress->address_street_2 }}</p>
                                    @endif
                                    @if ($companyAddress->city || $companyAddress->zip)
                                        <p>{{ $companyAddress->zip }} {{ $companyAddress->city }}</p>
                                    @endif
                                    @if ($companyAddress->country)
                                        <p>{{ $companyAddress->country->name ?? '' }}</p>
                                    @endif
                                </div>
                            @endif
                        @endif
                        {{-- Company tax identifiers --}}
                        @if ($payment->company)
                            <div style="margin-top: 8px; font-size: 11px; color: #595959;">
                                @if ($payment->company->vat_id)
                                    <p><strong>ЕДБ:</strong> {{ $payment->company->vat_id }}</p>
                                @endif
                                @if ($payment->company->tax_id)
                                    <p><strong>ЕМБС:</strong> {{ $payment->company->tax_id }}</p>
                                @endif
                            </div>
                        @endif
                    </div>
                </td>
                <td width="50%" class="header-section-right" style="vertical-align: top; text-align: right;">
                    <div style="font-size: 24px; font-weight: bold; color: #5851D8; margin-bottom: 15px;">
                        @lang('pdf_payment_label')
                    </div>
                    <div style="font-size: 14px; color: #595959;">
                        <p><strong>@lang('pdf_payment_number'):</strong> {{ $payment->payment_number }}</p>
                        <p><strong>@lang('pdf_payment_date'):</strong> {{ $payment->formattedPaymentDate }}</p>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <hr style="border: 0.620315px solid #E8E8E8;">

    <p class="content-heading">
        <span>@lang('pdf_payment_receipt_label')</span>
    </p>

    <div class="content-wrapper" style="height: auto; min-height: 150px;">
        <table width="100%" style="margin-top: 20px;">
            <tr>
                <td width="50%" style="vertical-align: top; padding-left: 30px;">
                    <div class="billing-address-label">@lang('pdf_received_from')</div>
                    @if ($billing_address)
                        <div class="billing-address" style="width: auto;">
                            {!! $billing_address !!}
                        </div>
                    @elseif ($payment->customer)
                        {{-- Fallback: show customer details if no formatted address --}}
                        <div class="billing-address" style="width: auto;">
                            <p class="billing-address-name" style="font-weight: bold;">{{ $payment->customer->name ?? '' }}</p>
                            @php
                                $customerAddress = $payment->customer->billingAddress;
                            @endphp
                            @if ($customerAddress)
                                @if ($customerAddress->address_street_1)
                                    <p>{{ $customerAddress->address_street_1 }}</p>
                                @endif
                                @if ($customerAddress->address_street_2)
                                    <p>{{ $customerAddress->address_street_2 }}</p>
                                @endif
                                @if ($customerAddress->city || $customerAddress->zip)
                                    <p>{{ $customerAddress->zip }} {{ $customerAddress->city }}</p>
                                @endif
                                @if ($customerAddress->country)
                                    <p>{{ $customerAddress->country->name ?? '' }}</p>
                                @endif
                            @endif
                            @if ($payment->customer->email)
                                <p>{{ $payment->customer->email }}</p>
                            @endif
                            @if ($payment->customer->phone)
                                <p>{{ $payment->customer->phone }}</p>
                            @endif
                            {{-- Customer tax identifiers --}}
                            @if ($payment->customer->vat_number)
                                <p><strong>ЕДБ:</strong> {{ $payment->customer->vat_number }}</p>
                            @endif
                            @if ($payment->customer->tax_id)
                                <p><strong>ЕМБС:</strong> {{ $payment->customer->tax_id }}</p>
                            @endif
                        </div>
                    @endif
                </td>
                <td width="50%" style="vertical-align: top; text-align: right; padding-right: 30px;">
                    <table width="100%" style="margin-left: auto;">
                        <tr>
                            <td class="attribute-label">@lang('pdf_payment_mode')</td>
                            <td class="attribute-value">{{ $payment->paymentMethod ? $payment->paymentMethod->name : '-' }}</td>
                        </tr>
                        @if ($payment->invoice && $payment->invoice->invoice_number)
                            <tr>
                                <td class="attribute-label">@lang('pdf_invoice_label')</td>
                                <td class="attribute-value">{{ $payment->invoice->invoice_number }}</td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>
    </div>
    <div style="clear: both; margin-top: 30px;"></div>
    <div class="total-display-box">
        <p class="total-display-label">@lang('pdf_payment_amount_received_label')</p>
        <span class="amount">{!! format_money_pdf($payment->amount, $payment->customer->currency ?? null) !!}</span>
    </div>
    <div style="clear: both;"></div>
    @if ($notes)
        <div class="notes" style="margin-top: 40px;">
            <div class="notes-label">
                @lang('pdf_notes')
            </div>
            {!! $notes !!}
        </div>
    @endif
</body>
{{-- CLAUDE-CHECKPOINT --}}
</html>
