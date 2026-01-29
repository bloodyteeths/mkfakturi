<!DOCTYPE html>
<html>

<head>
    <title>@lang('pdf_payment_label') - {{ $payment->payment_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style type="text/css">
        @page {
            margin: 25mm 20mm 25mm 20mm;
        }

        /* -- Base -- */
        body {
            font-family: "DejaVu Sans";
            margin: 0;
            padding: 0;
        }

        html {
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
        }

        hr {
            color: rgba(0, 0, 0, 0.2);
            border: 0.5px solid #EAF1FB;
            margin: 20px 0;
        }

        p {
            padding: 0;
            margin: 0;
        }

        /* -- Company Address -- */
        .company-details h1 {
            margin: 0;
            font-weight: bold;
            font-size: 14px;
            line-height: 20px;
            text-align: left;
        }

        .company-address {
            margin-top: 5px;
            font-size: 11px;
            line-height: 16px;
            color: #595959;
            word-wrap: break-word;
        }

        /* -- Billing -- */
        .billing-address-label {
            font-size: 11px;
            line-height: 16px;
            margin-bottom: 5px;
            color: #55547A;
            font-weight: bold;
        }

        .billing-address {
            font-size: 11px;
            line-height: 16px;
            color: #595959;
            word-wrap: break-word;
        }

        .billing-address-name {
            font-size: 13px;
            line-height: 18px;
            margin: 0;
            font-weight: bold;
        }

        .attribute-label {
            font-size: 11px;
            line-height: 18px;
            text-align: left;
            color: #55547A;
            padding-right: 10px;
        }

        .attribute-value {
            font-size: 11px;
            line-height: 18px;
            text-align: right;
        }

        .content-heading {
            margin: 20px 0;
            width: 100%;
            text-align: center;
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
            background: #F9FBFF;
            border: 1px solid #EAF1FB;
            padding: 12px 15px;
            margin-top: 30px;
        }

        .total-display-label {
            font-weight: bold;
            font-size: 13px;
            color: #595959;
        }

        .total-display-box .amount {
            font-weight: bold;
            font-size: 14px;
            text-align: right;
            color: #5851D8;
        }

        /* -- Notes -- */
        .notes {
            font-size: 11px;
            color: #595959;
            margin-top: 30px;
            text-align: left;
        }

        .notes-label {
            font-size: 13px;
            line-height: 20px;
            color: #040405;
            font-weight: bold;
            margin-bottom: 8px;
        }
    </style>

    @if (App::isLocale('th'))
        @include('app.pdf.locale.th')
    @endif
</head>

<body>
    {{-- Header with company info and payment title --}}
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td width="60%" style="vertical-align: top;">
                @if ($logo)
                    <img style="height: 45px; margin-bottom: 10px;" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Logo">
                @endif
                <div class="company-details">
                    <h1>{{ $payment->company->name ?? '' }}</h1>
                    @if ($company_address)
                        <div class="company-address">
                            {!! $company_address !!}
                        </div>
                    @else
                        @php
                            $companyAddress = $payment->company && $payment->company->address
                                ? $payment->company->address
                                : ($payment->company ? $payment->company->address()->first() : null);
                        @endphp
                        @if ($companyAddress)
                            <div class="company-address">
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
                    @if ($payment->company)
                        <div style="margin-top: 8px; font-size: 10px; color: #595959;">
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
            <td width="40%" style="vertical-align: top; text-align: right;">
                <div style="font-size: 20px; font-weight: bold; color: #5851D8; margin-bottom: 10px;">
                    @lang('pdf_payment_label')
                </div>
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td style="text-align: right; font-size: 11px; color: #55547A; padding: 3px 0;">@lang('pdf_payment_number'):</td>
                        <td style="text-align: right; font-size: 11px; padding: 3px 0; padding-left: 10px;">{{ $payment->payment_number }}</td>
                    </tr>
                    <tr>
                        <td style="text-align: right; font-size: 11px; color: #55547A; padding: 3px 0;">@lang('pdf_payment_date'):</td>
                        <td style="text-align: right; font-size: 11px; padding: 3px 0; padding-left: 10px;">{{ $payment->formattedPaymentDate }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <hr>

    <p class="content-heading">
        <span>@lang('pdf_payment_receipt_label')</span>
    </p>

    {{-- Customer and payment details --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 20px;">
        <tr>
            <td width="55%" style="vertical-align: top;">
                <div class="billing-address-label">@lang('pdf_received_from')</div>
                @if ($billing_address)
                    <div class="billing-address">
                        {!! $billing_address !!}
                    </div>
                @elseif ($payment->customer)
                    <div class="billing-address">
                        <p class="billing-address-name">{{ $payment->customer->name ?? '' }}</p>
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
                        @if ($payment->customer->vat_number)
                            <p><strong>ЕДБ:</strong> {{ $payment->customer->vat_number }}</p>
                        @endif
                        @if ($payment->customer->tax_id)
                            <p><strong>ЕМБС:</strong> {{ $payment->customer->tax_id }}</p>
                        @endif
                    </div>
                @endif
            </td>
            <td width="45%" style="vertical-align: top;">
                <table width="100%" cellpadding="0" cellspacing="0">
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

    {{-- Amount received box --}}
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-top: 40px;">
        <tr>
            <td width="55%"></td>
            <td width="45%">
                <div class="total-display-box">
                    <table width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="total-display-label">@lang('pdf_payment_amount_received_label')</td>
                            <td class="amount">{!! format_money_pdf($payment->amount, $payment->customer->currency ?? null) !!}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    @if ($notes)
        <div class="notes">
            <div class="notes-label">@lang('pdf_notes')</div>
            {!! $notes !!}
        </div>
    @endif
</body>
{{-- CLAUDE-CHECKPOINT --}}
</html>
