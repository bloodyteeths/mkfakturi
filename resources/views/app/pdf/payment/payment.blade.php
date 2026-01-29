<!DOCTYPE html>
<html>
<head>
    <title>@lang('pdf_payment_label') - {{ $payment->payment_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
        body { font-family: "DejaVu Sans"; font-size: 11px; margin: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #5851D8; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 22px; color: #5851D8; }
        .info-grid { width: 100%; margin-bottom: 15px; }
        .info-col { float: left; width: 48%; vertical-align: top; padding: 5px; }
        .section-title { font-weight: bold; font-size: 12px; margin-bottom: 5px; border-bottom: 1px solid #ccc; color: #55547A; }
        .field-label { font-weight: bold; display: inline-block; width: 120px; }
        .field-value { display: inline; }
        .details-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .details-table td { padding: 8px; border: 1px solid #ccc; }
        .details-table .label { background: #f0f0f0; font-weight: bold; width: 40%; }
        .totals { width: 50%; margin-left: auto; margin-top: 20px; border: 2px solid #5851D8; }
        .totals td { padding: 10px; }
        .totals .total-label { font-weight: bold; text-align: right; background: #f0f0f0; }
        .totals .total-value { text-align: right; font-weight: bold; font-size: 16px; color: #5851D8; }
        .notes-section { margin-top: 20px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; }
        .footer { margin-top: 20px; font-size: 10px; color: #666; text-align: center; border-top: 1px solid #ccc; padding-top: 10px; }
    </style>
    @if (App::isLocale('th'))
        @include('app.pdf.locale.th')
    @endif
</head>
<body>
    {{-- Header --}}
    <div class="header">
        @if ($logo)
            <img style="height:40px; margin-bottom:5px;" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Лого">
        @endif
        <h1>@lang('pdf_payment_label')</h1>
    </div>

    {{-- Company & Customer Information Side by Side --}}
    <div class="info-grid">
        {{-- Company (Issuer) Block --}}
        <div class="info-col" style="border-right: 1px solid #ccc;">
            <div class="section-title">@lang('pdf_company_details')</div>
            <div><span class="field-label">@lang('pdf_company_name'):</span> <span class="field-value">{{ $payment->company->name ?? '' }}</span></div>
            @if ($company_address)
                <div><span class="field-label">@lang('pdf_address'):</span> <span class="field-value">{!! str_replace('<br />', ', ', $company_address) !!}</span></div>
            @else
                @php
                    $companyAddress = $payment->company && $payment->company->address
                        ? $payment->company->address
                        : ($payment->company ? $payment->company->address()->first() : null);
                @endphp
                @if ($companyAddress)
                    <div><span class="field-label">@lang('pdf_address'):</span> <span class="field-value">
                        {{ $companyAddress->address_street_1 ?? '' }}
                        @if($companyAddress->address_street_2), {{ $companyAddress->address_street_2 }}@endif
                        @if($companyAddress->city), {{ $companyAddress->city }}@endif
                        @if($companyAddress->zip) {{ $companyAddress->zip }}@endif
                        @if($companyAddress->country), {{ $companyAddress->country->name ?? '' }}@endif
                    </span></div>
                @endif
            @endif
            @if ($payment->company && $payment->company->vat_id)
                <div><span class="field-label">ЕДБ:</span> <span class="field-value">{{ $payment->company->vat_id }}</span></div>
            @endif
            @if ($payment->company && $payment->company->tax_id)
                <div><span class="field-label">ЕМБС:</span> <span class="field-value">{{ $payment->company->tax_id }}</span></div>
            @endif
        </div>

        {{-- Customer (Received From) Block --}}
        <div class="info-col">
            <div class="section-title">@lang('pdf_received_from')</div>
            <div><span class="field-label">@lang('pdf_customer_name'):</span> <span class="field-value">{{ $payment->customer->name ?? '' }}</span></div>
            @if ($billing_address)
                <div><span class="field-label">@lang('pdf_address'):</span> <span class="field-value">{!! str_replace('<br />', ', ', $billing_address) !!}</span></div>
            @else
                @php
                    $customerAddress = $payment->customer ? $payment->customer->billingAddress : null;
                @endphp
                @if ($customerAddress)
                    <div><span class="field-label">@lang('pdf_address'):</span> <span class="field-value">
                        {{ $customerAddress->address_street_1 ?? '' }}
                        @if($customerAddress->address_street_2), {{ $customerAddress->address_street_2 }}@endif
                        @if($customerAddress->city), {{ $customerAddress->city }}@endif
                        @if($customerAddress->zip) {{ $customerAddress->zip }}@endif
                        @if($customerAddress->country), {{ $customerAddress->country->name ?? '' }}@endif
                    </span></div>
                @endif
            @endif
            @if ($payment->customer && $payment->customer->email)
                <div><span class="field-label">Email:</span> <span class="field-value">{{ $payment->customer->email }}</span></div>
            @endif
            @if ($payment->customer && $payment->customer->phone)
                <div><span class="field-label">@lang('pdf_phone'):</span> <span class="field-value">{{ $payment->customer->phone }}</span></div>
            @endif
            @if ($payment->customer && $payment->customer->vat_number)
                <div><span class="field-label">ЕДБ:</span> <span class="field-value">{{ $payment->customer->vat_number }}</span></div>
            @endif
            @if ($payment->customer && $payment->customer->tax_id)
                <div><span class="field-label">ЕМБС:</span> <span class="field-value">{{ $payment->customer->tax_id }}</span></div>
            @endif
        </div>
    </div>
    <div style="clear: both;"></div>

    {{-- Payment Details Table --}}
    <table class="details-table">
        <tr>
            <td class="label">@lang('pdf_payment_number'):</td>
            <td>{{ $payment->payment_number }}</td>
            <td class="label">@lang('pdf_payment_date'):</td>
            <td>{{ $payment->formattedPaymentDate }}</td>
        </tr>
        <tr>
            <td class="label">@lang('pdf_payment_mode'):</td>
            <td>{{ $payment->paymentMethod ? $payment->paymentMethod->name : '-' }}</td>
            <td class="label">@lang('pdf_invoice_label'):</td>
            <td>{{ $payment->invoice && $payment->invoice->invoice_number ? $payment->invoice->invoice_number : '-' }}</td>
        </tr>
    </table>

    {{-- Amount Received --}}
    <table class="totals">
        <tr>
            <td class="total-label">@lang('pdf_payment_amount_received_label'):</td>
            <td class="total-value">{!! format_money_pdf($payment->amount, $payment->customer->currency ?? null) !!}</td>
        </tr>
    </table>

    {{-- Notes --}}
    @if ($notes)
        <div class="notes-section">
            <div style="font-weight: bold; margin-bottom: 5px;">@lang('pdf_notes'):</div>
            {!! $notes !!}
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <p>@lang('pdf_payment_receipt_label')</p>
    </div>
</body>
{{-- CLAUDE-CHECKPOINT --}}
</html>
