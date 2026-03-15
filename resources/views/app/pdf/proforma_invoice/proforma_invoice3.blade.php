<!DOCTYPE html>
<html>

<head>
    <title>Профактура - {{ $invoice->proforma_invoice_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style type="text/css">
        /* -- Base -- */
        body {
            font-family: "DejaVu Sans";
            font-size: 11px;
            color: #2D2D2D;
            margin: 0;
            padding: 0;
        }

        html {
            margin: 0px;
            padding: 0px;
            margin-top: 40px;
        }

        table {
            border-collapse: collapse;
        }

        /* -- Header -- */

        .header-container {
            padding: 0 30px;
            margin-top: -20px;
            margin-bottom: 10px;
        }

        .header-logo {
            text-transform: capitalize;
            color: #2D2D2D;
        }

        .header-divider {
            border: none;
            border-top: 2px solid #2D2D2D;
            margin: 10px 0 20px 0;
        }

        /* -- Info Sections -- */

        .info-section {
            padding: 0 30px;
            margin-bottom: 18px;
        }

        .info-col {
            width: 48%;
            vertical-align: top;
        }

        .info-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #999;
            margin-bottom: 6px;
        }

        .info-row {
            font-size: 10px;
            line-height: 16px;
            color: #2D2D2D;
        }

        .info-row strong {
            color: #666;
            font-weight: normal;
            display: inline-block;
            width: 125px;
        }

        /* -- Document Metadata -- */

        .meta-section {
            padding: 0 30px;
            margin-bottom: 18px;
        }

        .meta-label {
            color: #999;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            display: block;
            margin-bottom: 2px;
        }

        .meta-value {
            color: #2D2D2D;
            font-weight: bold;
        }

        /* -- Items Table -- */

        .items-table {
            margin: 0 30px;
            width: calc(100% - 60px);
            page-break-before: avoid;
            page-break-after: auto;
        }

        .items-table th {
            padding: 8px 5px;
            text-align: left;
            font-size: 8px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: bold;
            border-bottom: 2px solid #2D2D2D;
        }

        .items-table td {
            padding: 8px 5px;
            font-size: 10px;
            border-bottom: 1px solid #E8E8E8;
            vertical-align: top;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
        }

        .item-description {
            color: #999;
            font-size: 8px;
            line-height: 11px;
        }

        /* -- Totals -- */

        .totals-container {
            margin: 15px 30px 0;
        }

        .totals-table {
            float: right;
            width: 42%;
        }

        .totals-table td {
            padding: 4px 0;
            font-size: 10px;
        }

        .totals-table .total-label {
            text-align: right;
            color: #999;
            padding-right: 12px;
        }

        .totals-table .total-value {
            text-align: right;
            font-weight: bold;
            color: #2D2D2D;
        }

        .totals-table .grand-total td {
            padding-top: 8px;
            border-top: 2px solid #2D2D2D;
            font-size: 14px;
        }

        .totals-table .grand-total .total-label {
            color: #2D2D2D;
            font-weight: bold;
        }

        /* -- Notes -- */

        .notes {
            font-size: 10px;
            color: #666;
            margin: 15px 30px 0;
            padding-top: 10px;
            border-top: 1px solid #E8E8E8;
            page-break-inside: avoid;
        }

        .notes-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #999;
            margin-bottom: 5px;
        }

        /* -- Footer -- */

        .footer {
            margin: 25px 30px 0;
            padding-top: 10px;
            border-top: 1px solid #E8E8E8;
            text-align: center;
            font-size: 8px;
            color: #999;
        }
    </style>
</head>

<body>
    {{-- Header --}}
    <div class="header-container">
        <table width="100%">
            <tr>
                <td width="50%">
                    @if ($logo)
                        <img class="header-logo" style="height:45px" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Лого">
                    @else
                        <h2 class="header-logo" style="margin:0; font-size:16px;">{{ $invoice->company->name ?? '' }}</h2>
                    @endif
                </td>
                <td width="50%" style="text-align:right; vertical-align:bottom;">
                    <div style="font-size:24px; font-weight:bold; color:#2D2D2D; letter-spacing:0.15em;">ПРОФАКТУРА</div>
                </td>
            </tr>
        </table>
        <hr class="header-divider">
    </div>

    {{-- Issuer & Buyer Information --}}
    <div class="info-section">
        <table width="100%">
            <tr>
                {{-- Issuer --}}
                <td class="info-col">
                    <div class="info-title">Издавач</div>
                    <div class="info-row"><strong>Назив:</strong> {{ $invoice->company->name ?? '' }}</div>
                    @if($company_address)
                        <div class="info-row"><strong>Адреса:</strong> {!! str_replace('<br />', ', ', $company_address) !!}</div>
                    @elseif($invoice->company && $invoice->company->address)
                        <div class="info-row"><strong>Адреса:</strong>
                            {{ $invoice->company->address->address_street_1 ?? '' }}
                            @if($invoice->company->address->address_street_2), {{ $invoice->company->address->address_street_2 }}@endif
                            @if($invoice->company->address->city), {{ $invoice->company->address->city }}@endif
                            @if($invoice->company->address->zip) {{ $invoice->company->address->zip }}@endif
                        </div>
                    @endif
                    @if(isset($invoice->company->vat_id) && $invoice->company->vat_id)
                        <div class="info-row"><strong>ЕДБ за ДДВ:</strong> {{ $invoice->company->vat_id }}</div>
                    @endif
                    @if(isset($invoice->company->tax_id) && $invoice->company->tax_id)
                        <div class="info-row"><strong>ЕМБС:</strong> {{ $invoice->company->tax_id }}</div>
                    @endif
                    @if($invoice->company && $invoice->company->address && $invoice->company->address->phone)
                        <div class="info-row"><strong>Телефон:</strong> {{ $invoice->company->address->phone }}</div>
                    @endif
                </td>

                {{-- Buyer --}}
                <td class="info-col">
                    <div class="info-title">Примател</div>
                    <div class="info-row"><strong>Назив:</strong> {{ $invoice->customer->name ?? '' }}</div>
                    @if($billing_address)
                        <div class="info-row"><strong>Адреса:</strong> {!! str_replace('<br />', ', ', $billing_address) !!}</div>
                    @elseif($invoice->customer && $invoice->customer->billingAddress)
                        <div class="info-row"><strong>Адреса:</strong>
                            {{ $invoice->customer->billingAddress->address_street_1 ?? '' }}
                            @if($invoice->customer->billingAddress->address_street_2), {{ $invoice->customer->billingAddress->address_street_2 }}@endif
                            @if($invoice->customer->billingAddress->city), {{ $invoice->customer->billingAddress->city }}@endif
                            @if($invoice->customer->billingAddress->zip) {{ $invoice->customer->billingAddress->zip }}@endif
                        </div>
                    @endif
                    @if(isset($invoice->customer->vat_number) && $invoice->customer->vat_number)
                        <div class="info-row"><strong>ЕДБ за ДДВ:</strong> {{ $invoice->customer->vat_number }}</div>
                    @endif
                    @if(isset($invoice->customer->tax_id) && $invoice->customer->tax_id)
                        <div class="info-row"><strong>ЕМБС:</strong> {{ $invoice->customer->tax_id }}</div>
                    @endif
                    @if($invoice->customer && $invoice->customer->phone)
                        <div class="info-row"><strong>Телефон:</strong> {{ $invoice->customer->phone }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Document Metadata --}}
    <div class="meta-section">
        <table width="100%">
            <tr>
                <td width="25%">
                    <span class="meta-label">Број на профактура</span>
                    <span class="meta-value">{{ $invoice->proforma_invoice_number }}</span>
                </td>
                <td width="25%">
                    <span class="meta-label">Датум на издавање</span>
                    <span class="meta-value">{{ $invoice->formattedProformaInvoiceDate }}</span>
                </td>
                <td width="25%">
                    <span class="meta-label">Важи до</span>
                    <span class="meta-value">{{ $invoice->formattedExpiryDate }}</span>
                </td>
                <td width="25%">
                    @if($invoice->reference_number)
                        <span class="meta-label">Референтен број</span>
                        <span class="meta-value">{{ $invoice->reference_number }}</span>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    @if ($shipping_address)
    <div style="padding: 0 30px; margin-bottom: 15px; font-size: 10px;">
        <strong style="color:#999; text-transform:uppercase; font-size:8px; letter-spacing:0.08em;">Адреса за испорака</strong><br>
        <span style="color: #2D2D2D;">{!! $shipping_address !!}</span>
    </div>
    @endif

    {{-- Items Table --}}
    <table class="items-table" cellspacing="0" border="0">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th style="width:40%;">Опис</th>
                <th style="width:10%;" class="text-center">Кол.</th>
                <th style="width:15%;" class="text-right">Цена</th>
                @if($invoice->discount_per_item === 'YES')
                <th style="width:10%;" class="text-right">Попуст</th>
                @endif
                <th style="width:15%;" class="text-right">Износ</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        {{ $item->name }}
                        @if($item->description)
                            <br><span class="item-description">{{ $item->description }}</span>
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

    {{-- Totals --}}
    <div class="totals-container">
        <table class="totals-table" cellspacing="0">
            <tr>
                <td class="total-label">Меѓузбир:</td>
                <td class="total-value">{!! format_money_pdf($invoice->sub_total, $invoice->customer->currency) !!}</td>
            </tr>

            @if ($invoice->discount > 0)
            <tr>
                <td class="total-label">
                    Попуст
                    @if($invoice->discount_type === 'percentage')
                        ({{ $invoice->discount }}%)
                    @endif
                </td>
                <td class="total-value">- {!! format_money_pdf($invoice->discount_val, $invoice->customer->currency) !!}</td>
            </tr>
            @endif

            @if ($taxes && $taxes->count() > 0)
                @foreach ($taxes as $tax)
                <tr>
                    <td class="total-label">{{ $tax->taxType->name }} ({{ $tax->taxType->percent }}%)</td>
                    <td class="total-value">{!! format_money_pdf($tax->amount, $invoice->customer->currency) !!}</td>
                </tr>
                @endforeach
            @elseif ($invoice->taxes && $invoice->taxes->count() > 0)
                @foreach ($invoice->taxes as $tax)
                <tr>
                    <td class="total-label">{{ $tax->taxType->name }} ({{ $tax->taxType->percent }}%)</td>
                    <td class="total-value">{!! format_money_pdf($tax->amount, $invoice->customer->currency) !!}</td>
                </tr>
                @endforeach
            @endif

            <tr class="grand-total">
                <td class="total-label">Вкупно:</td>
                <td class="total-value">{!! format_money_pdf($invoice->total, $invoice->customer->currency) !!}</td>
            </tr>
        </table>
        <div style="clear: both;"></div>
    </div>

    {{-- Notes --}}
    @if($notes)
        <div class="notes">
            <div class="notes-label">Белешки</div>
            {!! $notes !!}
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <strong>Овој документ е профактура и не претставува даночен документ.</strong>
    </div>
</body>

</html>
