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

        /* -- Invoice Details -- */

        .invoice-details-container {
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

        /* -- VAT Summary -- */

        .vat-summary {
            width: 55%;
            margin-left: auto;
            margin-top: 12px;
        }

        .vat-summary th {
            background: #F5F4FF;
            padding: 5px 8px;
            font-size: 9px;
            color: #55547A;
            border: 1px solid #E8E8E8;
            text-align: right;
            font-weight: bold;
        }

        .vat-summary td {
            padding: 5px 8px;
            font-size: 10px;
            border: 1px solid #E8E8E8;
            text-align: right;
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

        /* -- Payment Details -- */

        .payment-details {
            margin-top: 15px;
            padding: 10px;
            border: 1px solid #E8E8E8;
            font-size: 10px;
        }

        .payment-details .payment-title {
            font-weight: bold;
            font-size: 11px;
            color: #55547A;
            margin-bottom: 5px;
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
                        <h2 class="header-logo" style="margin:0;">{{ $invoice->company->name ?? '' }}</h2>
                    @endif
                </td>
                <td width="50%" style="text-align:right; vertical-align:top;">
                    <h1 style="margin:0; font-size:22px; color:#5851DB; letter-spacing:0.1em;">ФАКТУРА</h1>
                    <div style="font-size:11px; color:#55547A; margin-top:4px;">{{ $invoice->invoice_number }}</div>
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
                    <div class="section-title">Издавач на фактура</div>
                    <div class="field-row"><span class="field-label">Назив:</span> <span class="field-value">{{ $invoice->company->name ?? '' }}</span></div>
                    @if($company_address)
                        <div class="field-row"><span class="field-label">Адреса:</span> <span class="field-value">{!! str_replace('<br />', ', ', $company_address) !!}</span></div>
                    @elseif($invoice->company && $invoice->company->address)
                        <div class="field-row"><span class="field-label">Адреса:</span> <span class="field-value">
                            {{ $invoice->company->address->address_street_1 ?? '' }}
                            @if($invoice->company->address->address_street_2), {{ $invoice->company->address->address_street_2 }}@endif
                            @if($invoice->company->address->city), {{ $invoice->company->address->city }}@endif
                            @if($invoice->company->address->zip) {{ $invoice->company->address->zip }}@endif
                        </span></div>
                    @endif
                    @if(isset($invoice->company->vat_id) && $invoice->company->vat_id)
                        <div class="field-row"><span class="field-label">ЕДБ за ДДВ:</span> <span class="field-value">{{ $invoice->company->vat_id }}</span></div>
                    @endif
                    @if(isset($invoice->company->tax_id) && $invoice->company->tax_id)
                        <div class="field-row"><span class="field-label">Даночен број (ЕМБС):</span> <span class="field-value">{{ $invoice->company->tax_id }}</span></div>
                    @endif
                    @if($invoice->company && $invoice->company->address && $invoice->company->address->phone)
                        <div class="field-row"><span class="field-label">Телефон:</span> <span class="field-value">{{ $invoice->company->address->phone }}</span></div>
                    @endif
                    <div class="field-row"><span class="field-label">Место на издавање:</span> <span class="field-value">{{ optional($invoice->company->address)->city ?? 'Скопје' }}</span></div>
                </td>

                {{-- Buyer --}}
                <td class="info-col">
                    <div class="section-title">Примател на фактура</div>
                    <div class="field-row"><span class="field-label">Назив:</span> <span class="field-value">{{ $invoice->customer->name ?? '' }}</span></div>
                    @if($billing_address)
                        <div class="field-row"><span class="field-label">Адреса:</span> <span class="field-value">{!! str_replace('<br />', ', ', $billing_address) !!}</span></div>
                    @elseif($invoice->customer && $invoice->customer->billingAddress)
                        <div class="field-row"><span class="field-label">Адреса:</span> <span class="field-value">
                            {{ $invoice->customer->billingAddress->address_street_1 ?? '' }}
                            @if($invoice->customer->billingAddress->address_street_2), {{ $invoice->customer->billingAddress->address_street_2 }}@endif
                            @if($invoice->customer->billingAddress->city), {{ $invoice->customer->billingAddress->city }}@endif
                            @if($invoice->customer->billingAddress->zip) {{ $invoice->customer->billingAddress->zip }}@endif
                        </span></div>
                    @endif
                    @if(isset($invoice->customer->vat_number) && $invoice->customer->vat_number)
                        <div class="field-row"><span class="field-label">ЕДБ за ДДВ:</span> <span class="field-value">{{ $invoice->customer->vat_number }}</span></div>
                    @endif
                    @if(isset($invoice->customer->tax_id) && $invoice->customer->tax_id)
                        <div class="field-row"><span class="field-label">ЕМБС:</span> <span class="field-value">{{ $invoice->customer->tax_id }}</span></div>
                    @endif
                    @if($invoice->customer && $invoice->customer->phone)
                        <div class="field-row"><span class="field-label">Телефон:</span> <span class="field-value">{{ $invoice->customer->phone }}</span></div>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Invoice Metadata --}}
    <div class="invoice-details-container">
        <table class="details-table">
            <tr>
                <td class="detail-label">Број на фактура:</td>
                <td class="detail-value">{{ $invoice->invoice_number }}</td>
                <td class="detail-label">Датум на издавање:</td>
                <td class="detail-value">{{ $invoice->formattedInvoiceDate }}</td>
            </tr>
            <tr>
                <td class="detail-label">Ден на извршен промет:</td>
                <td class="detail-value">{{ $invoice->formattedInvoiceDate }}</td>
                <td class="detail-label">Рок на плаќање:</td>
                <td class="detail-value">{{ $invoice->formattedDueDate }}</td>
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
                    <td class="total-label">Преостанато за плаќање:</td>
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
    <div class="footer">
        <p><strong>Фактурата е валидна без печат и потпис согласно Законот за даночна постапка.</strong></p>
    </div>
</body>

</html>
