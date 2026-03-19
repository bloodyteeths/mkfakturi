<!DOCTYPE html>
<html>

<head>
    <title>Понуда - {{ $estimate->estimate_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style type="text/css">
        /* -- Base -- */
        body {
            font-family: "DejaVu Sans";
            font-size: 11px;
            color: #2D2D2D;
            margin: 15px;
        }

        table {
            border-collapse: collapse;
        }

        /* -- Header -- */

        .header-container {
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
            width: 100%;
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
            margin-top: 15px;
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
            margin-top: 15px;
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
            margin-top: 25px;
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
                        @if (isset($company) && $company)
                            <h2 class="header-logo" style="margin:0; font-size:16px;">{{ $company->name }}</h2>
                        @elseif ($estimate->company)
                            <h2 class="header-logo" style="margin:0; font-size:16px;">{{ $estimate->company->name }}</h2>
                        @endif
                    @endif
                </td>
                <td width="50%" style="text-align:right; vertical-align:bottom;">
                    <div style="font-size:24px; font-weight:bold; color:#2D2D2D; letter-spacing:0.15em;">ПОНУДА</div>
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
                    @if ($estimate->company)
                        <div class="info-row"><strong>Назив:</strong> {{ $estimate->company->name }}</div>
                    @endif
                    @if($company_address)
                        <div class="info-row"><strong>Адреса:</strong> {!! str_replace('<br />', ', ', $company_address) !!}</div>
                    @elseif($estimate->company && $estimate->company->address)
                        <div class="info-row"><strong>Адреса:</strong>
                            {{ $estimate->company->address->address_street_1 ?? '' }}
                            @if($estimate->company->address->address_street_2), {{ $estimate->company->address->address_street_2 }}@endif
                            @if($estimate->company->address->city), {{ $estimate->company->address->city }}@endif
                            @if($estimate->company->address->zip) {{ $estimate->company->address->zip }}@endif
                        </div>
                    @endif
                    @if(isset($estimate->company->vat_id) && $estimate->company->vat_id)
                        <div class="info-row"><strong>ЕДБ за ДДВ:</strong> {{ $estimate->company->vat_id }}</div>
                    @endif
                    @if(isset($estimate->company->tax_id) && $estimate->company->tax_id)
                        <div class="info-row"><strong>ЕМБС:</strong> {{ $estimate->company->tax_id }}</div>
                    @endif
                    @if($estimate->company && $estimate->company->address && $estimate->company->address->phone)
                        <div class="info-row"><strong>Телефон:</strong> {{ $estimate->company->address->phone }}</div>
                    @endif
                </td>

                {{-- Buyer --}}
                <td class="info-col">
                    <div class="info-title">Примател</div>
                    @if ($estimate->customer)
                        <div class="info-row"><strong>Назив:</strong> {{ $estimate->customer->name }}</div>
                    @endif
                    @if($billing_address)
                        <div class="info-row"><strong>Адреса:</strong> {!! str_replace('<br />', ', ', $billing_address) !!}</div>
                    @elseif($estimate->customer && $estimate->customer->billingAddress)
                        <div class="info-row"><strong>Адреса:</strong>
                            {{ $estimate->customer->billingAddress->address_street_1 ?? '' }}
                            @if($estimate->customer->billingAddress->address_street_2), {{ $estimate->customer->billingAddress->address_street_2 }}@endif
                            @if($estimate->customer->billingAddress->city), {{ $estimate->customer->billingAddress->city }}@endif
                            @if($estimate->customer->billingAddress->zip) {{ $estimate->customer->billingAddress->zip }}@endif
                        </div>
                    @endif
                    @if(isset($estimate->customer->vat_number) && $estimate->customer->vat_number)
                        <div class="info-row"><strong>ЕДБ за ДДВ:</strong> {{ $estimate->customer->vat_number }}</div>
                    @endif
                    @if(isset($estimate->customer->tax_id) && $estimate->customer->tax_id)
                        <div class="info-row"><strong>ЕМБС:</strong> {{ $estimate->customer->tax_id }}</div>
                    @endif
                    @if($estimate->customer && $estimate->customer->phone)
                        <div class="info-row"><strong>Телефон:</strong> {{ $estimate->customer->phone }}</div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Document Metadata --}}
    <div class="meta-section">
        <table width="100%">
            <tr>
                <td width="33%">
                    <span class="meta-label">Број на понуда</span>
                    <span class="meta-value">{{ $estimate->estimate_number }}</span>
                </td>
                <td width="33%">
                    <span class="meta-label">Датум на издавање</span>
                    <span class="meta-value">{{ $estimate->formattedEstimateDate }}</span>
                </td>
                <td width="33%">
                    <span class="meta-label">Важи до</span>
                    <span class="meta-value">{{ $estimate->formattedExpiryDate }}</span>
                </td>
            </tr>
        </table>
    </div>

    @if ($shipping_address)
    <div style="margin-bottom: 15px; font-size: 10px;">
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
                @if($estimate->discount_per_item === 'YES')
                <th style="width:10%;" class="text-right">Попуст</th>
                @endif
                <th style="width:15%;" class="text-right">Износ</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($estimate->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        {{ $item->name }}
                        @if($item->description)
                            <br><span class="item-description">{{ $item->description }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }} {{ $item->unit_name ?? '' }}</td>
                    <td class="text-right">{!! format_money_pdf($item->price, $estimate->customer->currency) !!}</td>
                    @if($estimate->discount_per_item === 'YES')
                    <td class="text-right">
                        @if($item->discount_type === 'fixed')
                            {!! format_money_pdf($item->discount_val, $estimate->customer->currency) !!}
                        @else
                            {{ $item->discount }}%
                        @endif
                    </td>
                    @endif
                    <td class="text-right">{!! format_money_pdf($item->total, $estimate->customer->currency) !!}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals-container">
        <table class="totals-table" cellspacing="0">
            <tr>
                <td class="total-label">Меѓузбир:</td>
                <td class="total-value">{!! format_money_pdf($estimate->sub_total, $estimate->customer->currency) !!}</td>
            </tr>

            @if ($estimate->discount > 0)
            <tr>
                <td class="total-label">
                    Попуст
                    @if($estimate->discount_type === 'percentage')
                        ({{ $estimate->discount }}%)
                    @endif
                </td>
                <td class="total-value">- {!! format_money_pdf($estimate->discount_val, $estimate->customer->currency) !!}</td>
            </tr>
            @endif

            @if ($taxes && $taxes->count() > 0)
                @foreach ($taxes as $tax)
                <tr>
                    <td class="total-label">{{ $tax->taxType->name }} ({{ $tax->taxType->percent }}%)</td>
                    <td class="total-value">{!! format_money_pdf($tax->amount, $estimate->customer->currency) !!}</td>
                </tr>
                @endforeach
            @elseif ($estimate->taxes && $estimate->taxes->count() > 0)
                @foreach ($estimate->taxes as $tax)
                <tr>
                    <td class="total-label">{{ $tax->taxType->name }} ({{ $tax->taxType->percent }}%)</td>
                    <td class="total-value">{!! format_money_pdf($tax->amount, $estimate->customer->currency) !!}</td>
                </tr>
                @endforeach
            @endif

            <tr class="grand-total">
                <td class="total-label">Вкупно:</td>
                <td class="total-value">{!! format_money_pdf($estimate->total, $estimate->customer->currency) !!}</td>
            </tr>
        </table>
        <div style="clear: both;"></div>
    </div>

    {{-- Notes --}}
    @if($notes)
        <div class="notes">
            <div class="notes-label">Забелешки</div>
            {!! $notes !!}
        </div>
    @endif

    {{-- Stamp & Signature --}}
    @if(isset($stamp) && $stamp || isset($signature) && $signature)
    <table style="width:100%; margin-top:10px; border:none;">
        <tr>
            <td style="width:50%; text-align:center; border:none;">
                @if(isset($stamp) && $stamp)
                    <img src="{{ \App\Space\ImageUtils::toBase64Src($stamp) }}" style="height:80px;" alt="Печат">
                    <div style="font-size:8px; color:#666;">Печат / Stamp</div>
                @endif
            </td>
            <td style="width:50%; text-align:center; border:none;">
                @if(isset($signature) && $signature)
                    <img src="{{ \App\Space\ImageUtils::toBase64Src($signature) }}" style="height:80px;" alt="Потпис">
                    <div style="font-size:8px; color:#666;">Овластен потпис / Signature</div>
                @endif
            </td>
        </tr>
    </table>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <strong>Овој документ е понуда и не претставува даночен документ.</strong>
    </div>
</body>

</html>
