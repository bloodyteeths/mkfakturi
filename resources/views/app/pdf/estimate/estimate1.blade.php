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
            margin: 15px;
        }

        table {
            border-collapse: collapse;
        }

        /* -- Header -- */

        .header-container {
            width: 100%;
            margin-bottom: 10px;
        }

        .header-logo {
            text-transform: capitalize;
            color: #5851DB;
        }

        /* -- Info Grid -- */

        .info-grid {
            width: 100%;
            margin-bottom: 15px;
        }

        .info-col {
            width: 48%;
            vertical-align: top;
            padding: 8px 10px;
        }

        .section-title {
            font-weight: bold;
            font-size: 11px;
            color: #5851DB;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #E8E8E8;
        }

        .field-row {
            margin-bottom: 2px;
            font-size: 10px;
            line-height: 15px;
        }

        .field-label {
            font-weight: bold;
            display: inline-block;
            width: 130px;
            color: #55547A;
        }

        .field-value {
            color: #333;
        }

        /* -- Details -- */

        .details-container {
            margin-bottom: 15px;
        }

        .details-table {
            width: 100%;
        }

        .details-table td {
            padding: 5px 8px;
            border: 1px solid #E8E8E8;
            font-size: 10px;
        }

        .details-table .detail-label {
            font-weight: bold;
            color: #55547A;
            width: 25%;
            background: #FAFAFA;
        }

        .details-table .detail-value {
            width: 25%;
        }

        /* -- Items Table -- */

        .items-table {
            width: 100%;
            page-break-before: avoid;
            page-break-after: auto;
        }

        .items-table th {
            background: #F5F4FF;
            padding: 6px 5px;
            text-align: left;
            font-size: 9px;
            color: #55547A;
            border-bottom: 2px solid #5851DB;
            font-weight: bold;
        }

        .items-table td {
            padding: 6px 5px;
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
            color: #888;
            font-size: 8px;
            line-height: 11px;
        }

        /* -- Totals -- */

        .totals-container {
            margin-top: 12px;
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
            background: #F5F4FF;
            border: 2px solid #5851DB;
            font-size: 13px;
            padding: 8px;
        }

        .totals-table .grand-total .total-value {
            color: #5851DB;
        }

        /* -- Notes -- */

        .notes {
            font-size: 10px;
            color: #595959;
            margin-top: 15px;
            padding: 10px;
            background: #FAFAFA;
            border: 1px solid #E8E8E8;
            page-break-inside: avoid;
        }

        .notes-label {
            font-size: 11px;
            font-weight: bold;
            color: #55547A;
            margin-bottom: 5px;
        }

        /* -- Footer -- */

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #E8E8E8;
            text-align: center;
            font-size: 9px;
            color: #888;
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
                        <img class="header-logo" style="height:50px" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Лого">
                    @else
                        @if (isset($company) && $company)
                            <h2 class="header-logo" style="margin:0;">{{ $company->name }}</h2>
                        @elseif ($estimate->company)
                            <h2 class="header-logo" style="margin:0;">{{ $estimate->company->name }}</h2>
                        @endif
                    @endif
                </td>
                <td width="50%" style="text-align:right; vertical-align:top;">
                    <h1 style="margin:0; font-size:22px; color:#5851DB; letter-spacing:0.1em;">ПОНУДА</h1>
                    <div style="font-size:11px; color:#55547A; margin-top:4px;">{{ $estimate->estimate_number }}</div>
                </td>
            </tr>
        </table>
        <hr style="border: 1px solid #5851DB; margin: 8px 0 15px 0;">
    </div>

    {{-- Issuer & Buyer Information --}}
    <div class="info-grid">
        <table width="100%">
            <tr>
                {{-- Issuer --}}
                <td class="info-col" style="border-right: 1px solid #E8E8E8;">
                    <div class="section-title">Издавач</div>
                    @if ($estimate->company)
                        <div class="field-row"><span class="field-label">Назив:</span> <span class="field-value">{{ $estimate->company->name }}</span></div>
                    @endif
                    @if($company_address)
                        <div class="field-row"><span class="field-label">Адреса:</span> <span class="field-value">{!! str_replace('<br />', ', ', $company_address) !!}</span></div>
                    @elseif($estimate->company && $estimate->company->address)
                        <div class="field-row"><span class="field-label">Адреса:</span> <span class="field-value">
                            {{ $estimate->company->address->address_street_1 ?? '' }}
                            @if($estimate->company->address->address_street_2), {{ $estimate->company->address->address_street_2 }}@endif
                            @if($estimate->company->address->city), {{ $estimate->company->address->city }}@endif
                            @if($estimate->company->address->zip) {{ $estimate->company->address->zip }}@endif
                        </span></div>
                    @endif
                    @if(isset($estimate->company->vat_id) && $estimate->company->vat_id)
                        <div class="field-row"><span class="field-label">ЕДБ за ДДВ:</span> <span class="field-value">{{ $estimate->company->vat_id }}</span></div>
                    @endif
                    @if(isset($estimate->company->tax_id) && $estimate->company->tax_id)
                        <div class="field-row"><span class="field-label">ЕМБС:</span> <span class="field-value">{{ $estimate->company->tax_id }}</span></div>
                    @endif
                    @if($estimate->company && $estimate->company->address && $estimate->company->address->phone)
                        <div class="field-row"><span class="field-label">Телефон:</span> <span class="field-value">{{ $estimate->company->address->phone }}</span></div>
                    @endif
                    @php
                        $bankAccount = $estimate->company ? $estimate->company->bankAccounts()->first() : null;
                    @endphp
                    @if($bankAccount)
                        <div class="field-row"><span class="field-label">Жиро сметка:</span> <span class="field-value">{{ $bankAccount->account_number ?? $bankAccount->iban }}</span></div>
                        @if($bankAccount->bank_name)
                            <div class="field-row"><span class="field-label">Депонент банка:</span> <span class="field-value">{{ $bankAccount->bank_name }}</span></div>
                        @endif
                    @endif
                </td>

                {{-- Buyer --}}
                <td class="info-col">
                    <div class="section-title">Примател</div>
                    @if ($estimate->customer)
                        <div class="field-row"><span class="field-label">Назив:</span> <span class="field-value">{{ $estimate->customer->name }}</span></div>
                    @endif
                    @if($billing_address)
                        <div class="field-row"><span class="field-label">Адреса:</span> <span class="field-value">{!! str_replace('<br />', ', ', $billing_address) !!}</span></div>
                    @elseif($estimate->customer && $estimate->customer->billingAddress)
                        <div class="field-row"><span class="field-label">Адреса:</span> <span class="field-value">
                            {{ $estimate->customer->billingAddress->address_street_1 ?? '' }}
                            @if($estimate->customer->billingAddress->address_street_2), {{ $estimate->customer->billingAddress->address_street_2 }}@endif
                            @if($estimate->customer->billingAddress->city), {{ $estimate->customer->billingAddress->city }}@endif
                            @if($estimate->customer->billingAddress->zip) {{ $estimate->customer->billingAddress->zip }}@endif
                        </span></div>
                    @endif
                    @if(isset($estimate->customer->vat_number) && $estimate->customer->vat_number)
                        <div class="field-row"><span class="field-label">ЕДБ за ДДВ:</span> <span class="field-value">{{ $estimate->customer->vat_number }}</span></div>
                    @endif
                    @if(isset($estimate->customer->tax_id) && $estimate->customer->tax_id)
                        <div class="field-row"><span class="field-label">ЕМБС:</span> <span class="field-value">{{ $estimate->customer->tax_id }}</span></div>
                    @endif
                    @if($estimate->customer && $estimate->customer->phone)
                        <div class="field-row"><span class="field-label">Телефон:</span> <span class="field-value">{{ $estimate->customer->phone }}</span></div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Estimate Metadata --}}
    <div class="details-container">
        <table class="details-table">
            <tr>
                <td class="detail-label">Број на понуда:</td>
                <td class="detail-value">{{ $estimate->estimate_number }}</td>
                <td class="detail-label">Датум на издавање:</td>
                <td class="detail-value">{{ $estimate->formattedEstimateDate }}</td>
            </tr>
            <tr>
                <td class="detail-label">Важи до:</td>
                <td class="detail-value">{{ $estimate->formattedExpiryDate }}</td>
                <td class="detail-label"></td>
                <td class="detail-value"></td>
            </tr>
        </table>
    </div>

    @if ($shipping_address)
    <div style="margin-bottom: 12px; font-size: 10px;">
        <span class="field-label">Адреса за испорака:</span>
        <span class="field-value">{!! $shipping_address !!}</span>
    </div>
    @endif

    {{-- Items Table --}}
    <table class="items-table" cellspacing="0" border="0">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th style="width:35%;">Опис</th>
                <th style="width:8%;" class="text-center">Ед. мерка</th>
                <th style="width:8%;" class="text-center">Кол.</th>
                <th style="width:14%;" class="text-right">Цена</th>
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
                    <td class="text-center">{{ $item->unit_name ?? 'парче' }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
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

    {{-- Payment & Delivery Terms --}}
    @if($estimate->reference_number || $estimate->formattedExpiryDate)
    <div style="margin-top: 12px; font-size: 10px; padding: 8px 10px; background: #FAFAFA; border: 1px solid #E8E8E8;">
        <div style="font-weight: bold; color: #55547A; margin-bottom: 4px; font-size: 9px; text-transform: uppercase; letter-spacing: 0.05em;">Услови</div>
        @if($estimate->formattedExpiryDate)
            <div style="margin-bottom: 2px;"><strong style="color:#55547A; display:inline-block; width:130px;">Рок на важност:</strong> Понудата важи до {{ $estimate->formattedExpiryDate }}</div>
        @endif
        @if($estimate->reference_number)
            <div style="margin-bottom: 2px;"><strong style="color:#55547A; display:inline-block; width:130px;">Референца:</strong> {{ $estimate->reference_number }}</div>
        @endif
    </div>
    @endif

    {{-- Notes --}}
    @if($notes)
        <div class="notes">
            <div class="notes-label">Забелешки:</div>
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
        <p><strong>Овој документ е понуда и не претставува даночен документ.</strong></p>
    </div>
</body>

</html>
