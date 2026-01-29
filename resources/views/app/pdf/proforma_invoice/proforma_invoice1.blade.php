<!DOCTYPE html>
<html>

<head>
    <title>Профактура - {{ $invoice->proforma_invoice_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style type="text/css">
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "DejaVu Sans";
            font-size: 11px;
            color: #2d3748;
            line-height: 1.5;
            padding: 0;
            margin: 0;
        }

        /* Header */
        .header {
            padding: 20px 35px;
            background-color: #667eea;
            color: white;
        }

        .header-table {
            width: 100%;
        }

        .logo-cell {
            vertical-align: middle;
        }

        .logo {
            height: 50px;
            max-width: 180px;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: white;
        }

        .title-cell {
            text-align: right;
            vertical-align: middle;
        }

        .doc-title {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 2px;
            color: white;
        }

        .doc-number {
            font-size: 14px;
            color: white;
            margin-top: 5px;
        }

        /* Main Content */
        .content {
            padding: 30px 35px;
        }

        /* Party Info Cards */
        .parties-table {
            width: 100%;
            margin-bottom: 25px;
        }

        .party-card {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            padding: 18px;
            vertical-align: top;
        }

        .party-label {
            font-size: 9px;
            font-weight: bold;
            color: #667eea;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid #667eea;
        }

        .party-name {
            font-size: 13px;
            font-weight: bold;
            color: #1a202c;
            margin-bottom: 5px;
        }

        .party-details {
            font-size: 10px;
            color: #4a5568;
            line-height: 1.6;
        }

        /* Meta Info */
        .meta-section {
            margin-bottom: 25px;
            padding: 15px 20px;
            background: #edf2f7;
            border-left: 4px solid #667eea;
        }

        .meta-table {
            width: 100%;
        }

        .meta-label {
            font-size: 10px;
            color: #718096;
            padding-right: 10px;
        }

        .meta-value {
            font-size: 11px;
            font-weight: bold;
            color: #2d3748;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            margin-bottom: 25px;
            border-collapse: collapse;
        }

        .items-table thead tr {
            background: #667eea;
        }

        .items-table thead th {
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 12px 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table thead th.text-left {
            text-align: left;
        }

        .items-table thead th.text-center {
            text-align: center;
        }

        .items-table thead th.text-right {
            text-align: right;
        }

        .items-table tbody td {
            padding: 12px 10px;
            font-size: 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f7fafc;
        }

        .item-name {
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 2px;
        }

        .item-desc {
            font-size: 9px;
            color: #718096;
        }

        /* Totals */
        .totals-wrapper {
            width: 100%;
        }

        .totals-table {
            float: right;
            width: 280px;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 10px 15px;
            font-size: 11px;
        }

        .totals-table .label-cell {
            color: #718096;
            text-align: left;
            background: #f7fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .totals-table .value-cell {
            color: #2d3748;
            text-align: right;
            font-weight: bold;
            background: #f7fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .totals-table .grand-total td {
            background: #667eea;
            color: white;
            font-size: 13px;
            font-weight: bold;
            padding: 14px 15px;
        }

        .totals-table .grand-total .label-cell,
        .totals-table .grand-total .value-cell {
            background: #667eea;
            color: white;
            border: none;
        }

        /* Notes */
        .notes-section {
            clear: both;
            margin-top: 35px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }

        .notes-title {
            font-size: 11px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .notes-text {
            font-size: 10px;
            color: #4a5568;
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding: 15px;
            background: #f7fafc;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 9px;
            color: #a0aec0;
        }

        /* Utility */
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
    </style>
</head>

<body>
    <!-- Header with ПРОФАКТУРА title -->
    <table width="100%" style="background-color: #667eea; padding: 15px 0; margin-bottom: 20px;" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding: 15px 35px;">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="50%" style="vertical-align: middle;">
                            @if ($logo)
                                <img style="height: 50px; max-width: 180px;" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Лого">
                            @else
                                @if ($invoice->company)
                                    <span style="font-size: 20px; font-weight: bold; color: white;">{{ $invoice->company->name }}</span>
                                @endif
                            @endif
                        </td>
                        <td width="50%" style="text-align: right; vertical-align: middle;">
                            <div style="font-size: 28px; font-weight: bold; color: white; letter-spacing: 2px;">ПРОФАКТУРА</div>
                            <div style="font-size: 14px; color: white; margin-top: 5px;">{{ $invoice->proforma_invoice_number }}</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Content -->
    <div class="content">
        <!-- Parties -->
        <table class="parties-table">
            <tr>
                <td class="party-card" width="48%">
                    <div class="party-label">Издавач</div>
                    @if ($invoice->company)
                        <div class="party-name">{{ $invoice->company->name }}</div>
                    @endif
                    <div class="party-details">
                        @if ($company_address)
                            {!! $company_address !!}
                        @else
                            {{-- Fallback: show company address from relationship --}}
                            @if ($invoice->company && $invoice->company->address)
                                @if ($invoice->company->address->address_street_1)
                                    {{ $invoice->company->address->address_street_1 }}<br>
                                @endif
                                @if ($invoice->company->address->address_street_2)
                                    {{ $invoice->company->address->address_street_2 }}<br>
                                @endif
                                @if ($invoice->company->address->city || $invoice->company->address->zip)
                                    {{ $invoice->company->address->city }}{{ $invoice->company->address->city && $invoice->company->address->zip ? ', ' : '' }}{{ $invoice->company->address->zip }}<br>
                                @endif
                                @if ($invoice->company->address->state)
                                    {{ $invoice->company->address->state }}<br>
                                @endif
                                @if ($invoice->company->address->country_name)
                                    {{ $invoice->company->address->country_name }}<br>
                                @endif
                                @if ($invoice->company->address->phone)
                                    Тел: {{ $invoice->company->address->phone }}<br>
                                @endif
                            @endif
                        @endif
                        @if(isset($invoice->company->vat_id) && $invoice->company->vat_id)
                            <br>ЕДБ: {{ $invoice->company->vat_id }}
                        @endif
                        @if(isset($invoice->company->tax_id) && $invoice->company->tax_id)
                            <br>ЕМБС: {{ $invoice->company->tax_id }}
                        @endif
                    </div>
                </td>
                <td width="4%"></td>
                <td class="party-card" width="48%">
                    <div class="party-label">Примател</div>
                    @if ($invoice->customer)
                        <div class="party-name">{{ $invoice->customer->name }}</div>
                    @endif
                    <div class="party-details">
                        @if ($billing_address)
                            {!! $billing_address !!}
                        @else
                            {{-- Fallback: show customer billing address from relationship --}}
                            @if ($invoice->customer && $invoice->customer->billingAddress)
                                @if ($invoice->customer->billingAddress->address_street_1)
                                    {{ $invoice->customer->billingAddress->address_street_1 }}<br>
                                @endif
                                @if ($invoice->customer->billingAddress->address_street_2)
                                    {{ $invoice->customer->billingAddress->address_street_2 }}<br>
                                @endif
                                @if ($invoice->customer->billingAddress->city || $invoice->customer->billingAddress->zip)
                                    {{ $invoice->customer->billingAddress->city }}{{ $invoice->customer->billingAddress->city && $invoice->customer->billingAddress->zip ? ', ' : '' }}{{ $invoice->customer->billingAddress->zip }}<br>
                                @endif
                                @if ($invoice->customer->billingAddress->state)
                                    {{ $invoice->customer->billingAddress->state }}<br>
                                @endif
                                @if ($invoice->customer->billingAddress->country_name)
                                    {{ $invoice->customer->billingAddress->country_name }}<br>
                                @endif
                                @if ($invoice->customer->billingAddress->phone)
                                    Тел: {{ $invoice->customer->billingAddress->phone }}<br>
                                @endif
                            @elseif ($invoice->customer)
                                {{-- Fallback: show basic customer info --}}
                                @if ($invoice->customer->email)
                                    {{ $invoice->customer->email }}<br>
                                @endif
                                @if ($invoice->customer->phone)
                                    Тел: {{ $invoice->customer->phone }}<br>
                                @endif
                            @endif
                        @endif
                        @if(isset($invoice->customer->vat_number) && $invoice->customer->vat_number)
                            <br>ЕДБ: {{ $invoice->customer->vat_number }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <!-- Document Details -->
        <table style="width: 100%; margin-bottom: 25px;">
            <tr>
                <td width="60%" style="vertical-align: top;">
                    <!-- Left side spacer or additional info can go here -->
                </td>
                <td width="40%" style="vertical-align: top;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding: 8px 12px; background: #667eea; color: white; font-size: 10px; font-weight: bold;">Број на профактура</td>
                            <td style="padding: 8px 12px; background: #f7fafc; border: 1px solid #e2e8f0; font-size: 11px; font-weight: bold; text-align: right;">{{ $invoice->proforma_invoice_number }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 12px; background: #edf2f7; color: #4a5568; font-size: 10px;">Датум на издавање</td>
                            <td style="padding: 8px 12px; background: #f7fafc; border: 1px solid #e2e8f0; font-size: 10px; text-align: right;">{{ $invoice->formattedProformaInvoiceDate }}</td>
                        </tr>
                        <tr>
                            <td style="padding: 8px 12px; background: #edf2f7; color: #4a5568; font-size: 10px;">Важи до</td>
                            <td style="padding: 8px 12px; background: #f7fafc; border: 1px solid #e2e8f0; font-size: 10px; text-align: right;">{{ $invoice->formattedExpiryDate }}</td>
                        </tr>
                        @if($invoice->reference_number)
                        <tr>
                            <td style="padding: 8px 12px; background: #edf2f7; color: #4a5568; font-size: 10px;">Референтен број</td>
                            <td style="padding: 8px 12px; background: #f7fafc; border: 1px solid #e2e8f0; font-size: 10px; text-align: right;">{{ $invoice->reference_number }}</td>
                        </tr>
                        @endif
                        @if($invoice->customer_po_number)
                        <tr>
                            <td style="padding: 8px 12px; background: #edf2f7; color: #4a5568; font-size: 10px;">ПО број на клиент</td>
                            <td style="padding: 8px 12px; background: #f7fafc; border: 1px solid #e2e8f0; font-size: 10px; text-align: right;">{{ $invoice->customer_po_number }}</td>
                        </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        @if ($shipping_address)
        <div style="margin-bottom: 20px; font-size: 10px;">
            <strong style="color: #667eea;">Адреса за испорака:</strong>
            <span style="color: #4a5568;">{!! $shipping_address !!}</span>
        </div>
        @endif

        <!-- Items -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="text-center" style="width: 5%;">#</th>
                    <th class="text-left" style="width: 45%;">Опис</th>
                    <th class="text-center" style="width: 12%;">Количина</th>
                    <th class="text-right" style="width: 15%;">Цена</th>
                    @if($invoice->discount_per_item === 'YES')
                    <th class="text-right" style="width: 10%;">Попуст</th>
                    @endif
                    <th class="text-right" style="width: 15%;">Износ</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <div class="item-name">{{ $item->name }}</div>
                        @if ($item->description)
                            <div class="item-desc">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }} {{ $item->unit_name ?? '' }}</td>
                    <td class="text-right">{!! format_money_pdf($item->price, $invoice->customer->currency) !!}</td>
                    @if($invoice->discount_per_item === 'YES')
                    <td class="text-right">
                        @if($item->discount_type === 'fixed')
                            {!! format_money_pdf($item->discount_val, $invoice->customer->currency) !!}
                        @else
                            {{ $item->discount }}%
                        @endif
                    </td>
                    @endif
                    <td class="text-right">{!! format_money_pdf($item->total, $invoice->customer->currency) !!}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-wrapper clearfix">
            <table class="totals-table">
                <tr>
                    <td class="label-cell">Меѓузбир</td>
                    <td class="value-cell">{!! format_money_pdf($invoice->sub_total, $invoice->customer->currency) !!}</td>
                </tr>

                @if ($invoice->discount > 0)
                <tr>
                    <td class="label-cell">
                        Попуст
                        @if($invoice->discount_type === 'percentage')
                            ({{ $invoice->discount }}%)
                        @endif
                    </td>
                    <td class="value-cell">- {!! format_money_pdf($invoice->discount_val, $invoice->customer->currency) !!}</td>
                </tr>
                @endif

                @if ($taxes && $taxes->count() > 0)
                    @foreach ($taxes as $tax)
                    <tr>
                        <td class="label-cell">{{ $tax->taxType->name }} ({{ $tax->taxType->percent }}%)</td>
                        <td class="value-cell">{!! format_money_pdf($tax->amount, $invoice->customer->currency) !!}</td>
                    </tr>
                    @endforeach
                @elseif ($invoice->taxes && $invoice->taxes->count() > 0)
                    @foreach ($invoice->taxes as $tax)
                    <tr>
                        <td class="label-cell">{{ $tax->taxType->name }} ({{ $tax->taxType->percent }}%)</td>
                        <td class="value-cell">{!! format_money_pdf($tax->amount, $invoice->customer->currency) !!}</td>
                    </tr>
                    @endforeach
                @endif

                <tr class="grand-total">
                    <td class="label-cell">Вкупно</td>
                    <td class="value-cell">{!! format_money_pdf($invoice->total, $invoice->customer->currency) !!}</td>
                </tr>
            </table>
        </div>

        <!-- Notes -->
        @if ($notes)
        <div class="notes-section">
            <div class="notes-title">Белешки</div>
            <div class="notes-text">{!! $notes !!}</div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            Овој документ е профактура и не претставува даночен документ.
        </div>
    </div>
</body>

</html>
