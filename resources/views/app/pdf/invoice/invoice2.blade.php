<!DOCTYPE html>
<html>

<head>
    <title>Фактура - {{ $invoice->invoice_number }}</title>
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

        /* -- Invoice Metadata -- */

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

        /* -- VAT Summary -- */

        .vat-summary {
            width: 55%;
            margin-left: auto;
            margin-right: 15px;
            margin-top: 12px;
        }

        .vat-summary th {
            background: #7675ff;
            color: #fff;
            padding: 5px 8px;
            font-size: 9px;
            border: 1px solid #7675ff;
            text-align: right;
            font-weight: bold;
        }

        .vat-summary td {
            padding: 5px 8px;
            font-size: 10px;
            border: 1px solid #D8D6FF;
            text-align: right;
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

        /* -- Payment Details -- */

        .payment-details {
            margin: 15px 15px 0;
            padding: 10px 12px;
            background: #F5F4FF;
            border-left: 3px solid #7675ff;
            font-size: 10px;
        }

        .payment-title {
            font-weight: bold;
            font-size: 11px;
            color: #7675ff;
            margin-bottom: 5px;
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
                        <h1 class="header-logo" style="padding-top: 0px;">{{ $invoice->company->name ?? '' }}</h1>
                    @endif
                </td>
                <td width="40%" class="header-section-right">
                    <h1>ФАКТУРА</h1>
                    <h4>{{ $invoice->invoice_number }}</h4>
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
                    <div class="info-title">Издавач на фактура</div>
                    <div class="info-row"><span class="info-label">Назив:</span> <span class="info-value">{{ $invoice->company->name ?? '' }}</span></div>
                    @if($company_address)
                        <div class="info-row"><span class="info-label">Адреса:</span> <span class="info-value">{!! str_replace('<br />', ', ', $company_address) !!}</span></div>
                    @elseif($invoice->company && $invoice->company->address)
                        <div class="info-row"><span class="info-label">Адреса:</span> <span class="info-value">
                            {{ $invoice->company->address->address_street_1 ?? '' }}
                            @if($invoice->company->address->address_street_2), {{ $invoice->company->address->address_street_2 }}@endif
                            @if($invoice->company->address->city), {{ $invoice->company->address->city }}@endif
                            @if($invoice->company->address->zip) {{ $invoice->company->address->zip }}@endif
                        </span></div>
                    @endif
                    @if(isset($invoice->company->vat_id) && $invoice->company->vat_id)
                        <div class="info-row"><span class="info-label">ЕДБ за ДДВ:</span> <span class="info-value">{{ $invoice->company->vat_id }}</span></div>
                    @endif
                    @if(isset($invoice->company->tax_id) && $invoice->company->tax_id)
                        <div class="info-row"><span class="info-label">Даночен број (ЕМБС):</span> <span class="info-value">{{ $invoice->company->tax_id }}</span></div>
                    @endif
                    @if($invoice->company && $invoice->company->address && $invoice->company->address->phone)
                        <div class="info-row"><span class="info-label">Телефон:</span> <span class="info-value">{{ $invoice->company->address->phone }}</span></div>
                    @endif
                    <div class="info-row"><span class="info-label">Место на издавање:</span> <span class="info-value">{{ optional($invoice->company->address)->city ?? 'Скопје' }}</span></div>
                </td>

                <td style="width: 4%;"></td>

                {{-- Buyer --}}
                <td class="info-block info-block-right">
                    <div class="info-title">Примател на фактура</div>
                    <div class="info-row"><span class="info-label">Назив:</span> <span class="info-value">{{ $invoice->customer->name ?? '' }}</span></div>
                    @if($billing_address)
                        <div class="info-row"><span class="info-label">Адреса:</span> <span class="info-value">{!! str_replace('<br />', ', ', $billing_address) !!}</span></div>
                    @elseif($invoice->customer && $invoice->customer->billingAddress)
                        <div class="info-row"><span class="info-label">Адреса:</span> <span class="info-value">
                            {{ $invoice->customer->billingAddress->address_street_1 ?? '' }}
                            @if($invoice->customer->billingAddress->address_street_2), {{ $invoice->customer->billingAddress->address_street_2 }}@endif
                            @if($invoice->customer->billingAddress->city), {{ $invoice->customer->billingAddress->city }}@endif
                            @if($invoice->customer->billingAddress->zip) {{ $invoice->customer->billingAddress->zip }}@endif
                        </span></div>
                    @endif
                    @if(isset($invoice->customer->vat_number) && $invoice->customer->vat_number)
                        <div class="info-row"><span class="info-label">ЕДБ за ДДВ:</span> <span class="info-value">{{ $invoice->customer->vat_number }}</span></div>
                    @endif
                    @if(isset($invoice->customer->tax_id) && $invoice->customer->tax_id)
                        <div class="info-row"><span class="info-label">ЕМБС:</span> <span class="info-value">{{ $invoice->customer->tax_id }}</span></div>
                    @endif
                    @if($invoice->customer && $invoice->customer->phone)
                        <div class="info-row"><span class="info-label">Телефон:</span> <span class="info-value">{{ $invoice->customer->phone }}</span></div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Invoice Metadata --}}
    <div class="meta-section">
        <table class="meta-table">
            <tr>
                <td class="meta-label">Број на фактура:</td>
                <td class="meta-value">{{ $invoice->invoice_number }}</td>
                <td class="meta-label">Датум на издавање:</td>
                <td class="meta-value">{{ $invoice->formattedInvoiceDate }}</td>
            </tr>
            <tr>
                <td class="meta-label">Ден на извршен промет:</td>
                <td class="meta-value">{{ $invoice->formattedInvoiceDate }}</td>
                <td class="meta-label">Рок на плаќање:</td>
                <td class="meta-value">{{ $invoice->formattedDueDate }}</td>
            </tr>
        </table>
    </div>

    {{-- Items Table --}}
    <table class="items-table" cellspacing="0" border="0">
        <thead>
            <tr>
                <th style="width:4%;">#</th>
                <th style="width:33%;">Опис</th>
                <th style="width:8%;" class="text-center">Количина</th>
                <th style="width:8%;" class="text-center">Единица</th>
                <th style="width:13%;" class="text-right">Ед. цена без ДДВ</th>
                <th style="width:13%;" class="text-right">Износ без ДДВ</th>
                <th style="width:8%;" class="text-center">Стапка</th>
                <th style="width:13%;" class="text-right">ДДВ износ</th>
            </tr>
        </thead>
        <tbody>
            @php $index = 1; @endphp
            @foreach ($invoice->items as $item)
                @php
                    $itemBaseAmount = $item->total;
                    $itemTaxPercent = 0;
                    if($invoice->tax_per_item === 'YES' && $item->taxes->count() > 0) {
                        $firstTax = $item->taxes->first();
                        $itemTaxPercent = $firstTax->percent ?? 0;
                    }
                @endphp
                <tr>
                    <td class="text-center">{{ $index++ }}</td>
                    <td>
                        {{ $item->name }}
                        @if($item->description)
                            <br><span class="item-description">{{ $item->description }}</span>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-center">{{ $item->unit_name ?? 'пар.' }}</td>
                    <td class="text-right">{!! format_money_pdf($item->price, $invoice->customer->currency) !!}</td>
                    <td class="text-right">{!! format_money_pdf($itemBaseAmount, $invoice->customer->currency) !!}</td>
                    <td class="text-center">{{ $itemTaxPercent }}%</td>
                    <td class="text-right">{!! format_money_pdf($item->tax, $invoice->customer->currency) !!}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- VAT Summary --}}
    <table class="vat-summary" cellspacing="0">
        <thead>
            <tr>
                <th>Даночна стапка</th>
                <th>Основица</th>
                <th>ДДВ износ</th>
            </tr>
        </thead>
        <tbody>
            @php
                $taxesByRate = collect();
                if ($invoice->tax_per_item === 'YES') {
                    foreach ($invoice->items as $item) {
                        foreach ($item->taxes as $tax) {
                            $rate = $tax->percent ?? 0;
                            if (!$taxesByRate->has($rate)) {
                                $taxesByRate->put($rate, ['base' => 0, 'tax' => 0]);
                            }
                            $existing = $taxesByRate->get($rate);
                            $existing['base'] += $item->total;
                            $existing['tax'] += $tax->amount;
                            $taxesByRate->put($rate, $existing);
                        }
                    }
                } else {
                    foreach ($invoice->taxes as $tax) {
                        $rate = $tax->percent ?? 0;
                        $taxesByRate->put($rate, [
                            'base' => $invoice->sub_total,
                            'tax' => $tax->amount
                        ]);
                    }
                }
            @endphp

            @foreach($taxesByRate as $rate => $amounts)
                <tr>
                    <td class="text-center">{{ $rate }}%</td>
                    <td>{!! format_money_pdf($amounts['base'], $invoice->customer->currency) !!}</td>
                    <td>{!! format_money_pdf($amounts['tax'], $invoice->customer->currency) !!}</td>
                </tr>
            @endforeach

            @if($taxesByRate->isEmpty())
                <tr>
                    <td class="text-center">0% (ослободено)</td>
                    <td>{!! format_money_pdf($invoice->sub_total, $invoice->customer->currency) !!}</td>
                    <td>{!! format_money_pdf(0, $invoice->customer->currency) !!}</td>
                </tr>
            @endif
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="totals-container">
        <table class="totals-table" cellspacing="0">
            <tr>
                <td class="total-label">Вкупно без ДДВ:</td>
                <td class="total-value">{!! format_money_pdf($invoice->sub_total, $invoice->customer->currency) !!}</td>
            </tr>
            <tr>
                <td class="total-label">Вкупно ДДВ:</td>
                <td class="total-value">{!! format_money_pdf($invoice->tax, $invoice->customer->currency) !!}</td>
            </tr>
            <tr class="grand-total">
                <td class="total-label">ВКУПНО ЗА ПЛАЌАЊЕ:</td>
                <td class="total-value">{!! format_money_pdf($invoice->total, $invoice->customer->currency) !!}</td>
            </tr>
            @if($invoice->due_amount > 0 && $invoice->paid_status !== App\Models\Invoice::STATUS_PAID)
                <tr>
                    <td class="total-label" style="color:#55547A;">Преостанато за плаќање:</td>
                    <td class="total-value">{!! format_money_pdf($invoice->due_amount, $invoice->customer->currency) !!}</td>
                </tr>
            @endif
        </table>
        <div style="clear: both;"></div>
    </div>

    {{-- Payment Details --}}
    <div class="payment-details">
        <div class="payment-title">Детали за плаќање:</div>
        <div><strong>Валута:</strong> МКД (Македонски денар)</div>
        @if(optional($invoice->company->address)->zip)
            <div><strong>Трансакциска сметка:</strong> {{ $invoice->company->address->zip }}</div>
        @endif
        <div><strong>Начин на плаќање:</strong> Банкарски трансфер</div>
    </div>

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

    {{-- CASYS QR Payment --}}
    @include('app.pdf.invoice.partials.casys-qr')

    {{-- Legal Footer --}}
    <div class="footer-bar">
        <strong>Фактурата е валидна без печат и потпис согласно Законот за даночна постапка.</strong>
    </div>
</body>

</html>
