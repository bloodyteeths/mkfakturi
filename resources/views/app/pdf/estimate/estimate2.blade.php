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
            color: #333;
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
        }

        /* -- Header Banner -- */

        .header-container {
            background: #7675ff;
            width: 100%;
            padding: 20px 0;
        }

        .header-logo {
            text-transform: capitalize;
            color: #fff;
        }

        .header-section-left {
            padding-left: 15px;
            display: inline-block;
            width: 55%;
            vertical-align: middle;
        }

        .header-section-right {
            display: inline-block;
            width: 40%;
            float: right;
            padding: 0 15px 0 0;
            text-align: right;
            color: white;
        }

        .header-section-right h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 0.1em;
        }

        .header-section-right h4 {
            margin: 4px 0 0 0;
            font-size: 11px;
            font-weight: normal;
            opacity: 0.85;
        }

        /* -- Info Blocks -- */

        .info-section {
            margin: 20px 15px 15px;
        }

        .info-block {
            width: 48%;
            vertical-align: top;
            padding: 10px 12px;
        }

        .info-block-left {
            background: #F5F4FF;
            border-left: 3px solid #7675ff;
        }

        .info-block-right {
            background: #F5F4FF;
            border-left: 3px solid #7675ff;
        }

        .info-title {
            font-weight: bold;
            font-size: 10px;
            color: #7675ff;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 6px;
        }

        .info-row {
            margin-bottom: 2px;
            font-size: 10px;
            line-height: 14px;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 125px;
            color: #55547A;
        }

        .info-value {
            color: #333;
        }

        /* -- Document Metadata -- */

        .meta-section {
            margin: 0 15px 15px;
        }

        .meta-table {
            width: 100%;
        }

        .meta-table td {
            padding: 5px 8px;
            font-size: 10px;
            border: 1px solid #D8D6FF;
        }

        .meta-table .meta-label {
            font-weight: bold;
            color: #55547A;
            background: #F5F4FF;
            width: 25%;
        }

        .meta-table .meta-value {
            width: 25%;
        }

        /* -- Items Table -- */

        .items-table {
            margin: 0 15px;
            width: auto;
            page-break-before: avoid;
            page-break-after: auto;
        }

        .items-table th {
            background: #7675ff;
            color: #fff;
            padding: 7px 5px;
            text-align: left;
            font-size: 9px;
            font-weight: bold;
            letter-spacing: 0.03em;
        }

        .items-table td {
            padding: 6px 5px;
            font-size: 10px;
            vertical-align: top;
        }

        .items-table tr:nth-child(even) td {
            background: #FAFAFF;
        }

        .items-table tr td {
            border-bottom: 1px solid #E8E8FF;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
        }

        .item-description {
            color: #888;
            font-size: 8px;
            line-height: 11px;
        }

        /* -- Totals -- */

        .totals-container {
            margin: 12px 15px 0;
        }

        .totals-table {
            float: right;
            width: 45%;
        }

        .totals-table td {
            padding: 5px 8px;
            font-size: 11px;
        }

        .totals-table .total-label {
            text-align: right;
            color: #55547A;
            font-weight: bold;
        }

        .totals-table .total-value {
            text-align: right;
            font-weight: bold;
            color: #333;
        }

        .totals-table .grand-total td {
            background: #7675ff;
            color: #fff;
            font-size: 13px;
            padding: 8px;
        }

        .totals-table .grand-total .total-label,
        .totals-table .grand-total .total-value {
            color: #fff;
        }

        /* -- Notes -- */

        .notes {
            font-size: 10px;
            color: #595959;
            margin: 15px 15px 0;
            padding: 10px 12px;
            background: #FAFAFF;
            border: 1px solid #D8D6FF;
            page-break-inside: avoid;
        }

        .notes-label {
            font-size: 11px;
            font-weight: bold;
            color: #7675ff;
            margin-bottom: 5px;
        }

        /* -- Footer -- */

        .footer-bar {
            margin: 20px 0 0;
            padding: 10px 15px;
            background: #7675ff;
            text-align: center;
            font-size: 9px;
            color: rgba(255, 255, 255, 0.9);
        }
    </style>
</head>

<body>
    {{-- Header Banner --}}
    <div class="header-container">
        <table width="100%">
            <tr>
                <td width="60%" class="header-section-left">
                    @if ($logo)
                        <img class="header-logo" style="height:50px" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Лого">
                    @else
                        @if (isset($company) && $company)
                            <h1 class="header-logo" style="padding-top: 0px;">{{ $company->name }}</h1>
                        @elseif ($estimate->company)
                            <h1 class="header-logo" style="padding-top: 0px;">{{ $estimate->company->name }}</h1>
                        @endif
                    @endif
                </td>
                <td width="40%" class="header-section-right">
                    <h1>ПОНУДА</h1>
                    <h4>{{ $estimate->estimate_number }}</h4>
                </td>
            </tr>
        </table>
    </div>

    {{-- Issuer & Buyer Information --}}
    <div class="info-section">
        <table width="100%" cellspacing="0">
            <tr>
                {{-- Issuer --}}
                <td class="info-block info-block-left">
                    <div class="info-title">Издавач</div>
                    @if ($estimate->company)
                        <div class="info-row"><span class="info-label">Назив:</span> <span class="info-value">{{ $estimate->company->name }}</span></div>
                    @endif
                    @if($company_address)
                        <div class="info-row"><span class="info-label">Адреса:</span> <span class="info-value">{!! str_replace('<br />', ', ', $company_address) !!}</span></div>
                    @elseif($estimate->company && $estimate->company->address)
                        <div class="info-row"><span class="info-label">Адреса:</span> <span class="info-value">
                            {{ $estimate->company->address->address_street_1 ?? '' }}
                            @if($estimate->company->address->address_street_2), {{ $estimate->company->address->address_street_2 }}@endif
                            @if($estimate->company->address->city), {{ $estimate->company->address->city }}@endif
                            @if($estimate->company->address->zip) {{ $estimate->company->address->zip }}@endif
                        </span></div>
                    @endif
                    @if(isset($estimate->company->vat_id) && $estimate->company->vat_id)
                        <div class="info-row"><span class="info-label">ЕДБ за ДДВ:</span> <span class="info-value">{{ $estimate->company->vat_id }}</span></div>
                    @endif
                    @if(isset($estimate->company->tax_id) && $estimate->company->tax_id)
                        <div class="info-row"><span class="info-label">ЕМБС:</span> <span class="info-value">{{ $estimate->company->tax_id }}</span></div>
                    @endif
                    @if($estimate->company && $estimate->company->address && $estimate->company->address->phone)
                        <div class="info-row"><span class="info-label">Телефон:</span> <span class="info-value">{{ $estimate->company->address->phone }}</span></div>
                    @endif
                </td>

                <td style="width: 4%;"></td>

                {{-- Buyer --}}
                <td class="info-block info-block-right">
                    <div class="info-title">Примател</div>
                    @if ($estimate->customer)
                        <div class="info-row"><span class="info-label">Назив:</span> <span class="info-value">{{ $estimate->customer->name }}</span></div>
                    @endif
                    @if($billing_address)
                        <div class="info-row"><span class="info-label">Адреса:</span> <span class="info-value">{!! str_replace('<br />', ', ', $billing_address) !!}</span></div>
                    @elseif($estimate->customer && $estimate->customer->billingAddress)
                        <div class="info-row"><span class="info-label">Адреса:</span> <span class="info-value">
                            {{ $estimate->customer->billingAddress->address_street_1 ?? '' }}
                            @if($estimate->customer->billingAddress->address_street_2), {{ $estimate->customer->billingAddress->address_street_2 }}@endif
                            @if($estimate->customer->billingAddress->city), {{ $estimate->customer->billingAddress->city }}@endif
                            @if($estimate->customer->billingAddress->zip) {{ $estimate->customer->billingAddress->zip }}@endif
                        </span></div>
                    @endif
                    @if(isset($estimate->customer->vat_number) && $estimate->customer->vat_number)
                        <div class="info-row"><span class="info-label">ЕДБ за ДДВ:</span> <span class="info-value">{{ $estimate->customer->vat_number }}</span></div>
                    @endif
                    @if(isset($estimate->customer->tax_id) && $estimate->customer->tax_id)
                        <div class="info-row"><span class="info-label">ЕМБС:</span> <span class="info-value">{{ $estimate->customer->tax_id }}</span></div>
                    @endif
                    @if($estimate->customer && $estimate->customer->phone)
                        <div class="info-row"><span class="info-label">Телефон:</span> <span class="info-value">{{ $estimate->customer->phone }}</span></div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Document Metadata --}}
    <div class="meta-section">
        <table class="meta-table">
            <tr>
                <td class="meta-label">Број на понуда:</td>
                <td class="meta-value">{{ $estimate->estimate_number }}</td>
                <td class="meta-label">Датум на издавање:</td>
                <td class="meta-value">{{ $estimate->formattedEstimateDate }}</td>
            </tr>
            <tr>
                <td class="meta-label">Важи до:</td>
                <td class="meta-value">{{ $estimate->formattedExpiryDate }}</td>
                <td class="meta-label"></td>
                <td class="meta-value"></td>
            </tr>
        </table>
    </div>

    @if ($shipping_address)
    <div style="margin: 0 15px 15px; font-size: 10px;">
        <strong style="color: #7675ff;">Адреса за испорака:</strong>
        <span style="color: #4a5568;">{!! $shipping_address !!}</span>
    </div>
    @endif

    {{-- Items Table --}}
    <table class="items-table" cellspacing="0" border="0">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th style="width:40%;">Опис</th>
                <th style="width:10%;" class="text-center">Количина</th>
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
                    @if ($item->description)
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
    @if ($notes)
        <div class="notes">
            <div class="notes-label">Забелешки:</div>
            {!! $notes !!}
        </div>
    @endif

    {{-- Footer --}}
    <div class="footer-bar">
        <strong>Овој документ е понуда и не претставува даночен документ.</strong>
    </div>
</body>

</html>
