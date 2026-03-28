<!DOCTYPE html>
<html>

<head>
    <title>@if($invoice->type === 'advance')Аванс фактура @elseif($invoice->type === 'final')Финална фактура @else Фактура @endif - {{ $invoice->invoice_number }}</title>
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

        /* -- Invoice Metadata -- */

        .meta-section {
            margin-bottom: 18px;
        }

        .meta-row {
            display: inline-block;
            margin-right: 25px;
            font-size: 10px;
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

        /* -- VAT Summary -- */

        .vat-summary {
            width: 50%;
            margin-left: auto;
            margin-top: 15px;
        }

        .vat-summary th {
            padding: 5px 8px;
            font-size: 8px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            text-align: right;
            font-weight: bold;
            border-bottom: 1px solid #2D2D2D;
        }

        .vat-summary td {
            padding: 5px 8px;
            font-size: 10px;
            text-align: right;
            border-bottom: 1px solid #E8E8E8;
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

        /* -- Payment Details -- */

        .payment-details {
            margin-top: 18px;
            padding-top: 12px;
            border-top: 1px solid #E8E8E8;
            font-size: 10px;
        }

        .payment-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #999;
            margin-bottom: 5px;
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
                        <h2 class="header-logo" style="margin:0; font-size:16px;">{{ $invoice->company->name ?? '' }}</h2>
                    @endif
                </td>
                <td width="50%" style="text-align:right; vertical-align:bottom;">
                    <div style="font-size:24px; font-weight:bold; color:#2D2D2D; letter-spacing:0.15em;">
                        @if($invoice->type === 'advance')
                            ADVANCE INVOICE / АВАНС ФАКТУРА
                        @elseif($invoice->type === 'final')
                            FINAL INVOICE / ФИНАЛНА ФАКТУРА
                        @else
                            INVOICE / ФАКТУРА
                        @endif
                    </div>
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
                    <div class="info-title">Издавач на фактура</div>
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
                    <div class="info-row"><strong>Место:</strong> {{ optional($invoice->company->address)->city ?? 'Скопје' }}</div>
                </td>

                {{-- Buyer --}}
                <td class="info-col">
                    <div class="info-title">Примател на фактура</div>
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

    {{-- Invoice Metadata --}}
    <div class="meta-section">
        <table width="100%">
            <tr>
                <td width="25%">
                    <span class="meta-label">Број на фактура</span>
                    <span class="meta-value">{{ $invoice->invoice_number }}</span>
                </td>
                <td width="25%">
                    <span class="meta-label">Датум на издавање</span>
                    <span class="meta-value">{{ $invoice->formattedInvoiceDate }}</span>
                </td>
                <td width="25%">
                    <span class="meta-label">Ден на извршен промет</span>
                    <span class="meta-value">{{ $invoice->formattedPerformanceDate ?? $invoice->formattedInvoiceDate }}</span>
                </td>
                <td width="25%">
                    <span class="meta-label">Рок на плаќање</span>
                    <span class="meta-value">{{ $invoice->formattedDueDate }}</span>
                </td>
            </tr>
            @if($invoice->performance_date && $invoice->performance_date != $invoice->invoice_date)
            <tr>
                <td colspan="4">
                    <span class="meta-label">Date of Performance</span>
                    <span class="meta-value">{{ $invoice->formattedPerformanceDate }}</span>
                </td>
            </tr>
            @endif
        </table>
    </div>

    {{-- Items Table --}}
    <table class="items-table" cellspacing="0" border="0">
        <thead>
            <tr>
                <th style="width:4%;">#</th>
                <th style="width:33%;">Опис</th>
                <th style="width:8%;" class="text-center">Кол.</th>
                <th style="width:8%;" class="text-center">Ед.</th>
                <th style="width:13%;" class="text-right">Цена без ДДВ</th>
                <th style="width:13%;" class="text-right">Износ без ДДВ</th>
                <th style="width:8%;" class="text-center">Стапка</th>
                <th style="width:13%;" class="text-right">ДДВ</th>
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

    {{-- Reverse Charge Notice (Article 32-а ЗДДВ) --}}
    @if($invoice->is_reverse_charge)
    <div style="margin-top: 10px; padding: 8px; border: 1px solid #dc3545; background: #fff3f3; font-size: 10px;">
        <strong>{{ __('invoices.reverse_charge') }}</strong><br>
        ПРЕНЕСУВАЊЕ НА ДАНОЧНА ОБВРСКА — Член 32-а од Законот за данокот на додадена вредност.<br>
        Даночен должник е примателот на прометот. ДДВ не е пресметан на оваа фактура.<br>
        <em>Reverse charge — VAT liability transferred to recipient per Art. 32-a VAT Law.</em>
    </div>
    @endif

    {{-- Advance Invoice Notice (Article 14 ЗДДВ) --}}
    @if($invoice->type === 'advance')
    <div style="margin-top: 10px; padding: 8px; border: 1px solid #0d6efd; background: #f0f7ff; font-size: 10px;">
        <strong>АВАНС ФАКТУРА / ADVANCE INVOICE</strong><br>
        Издадена согласно чл. 14 од Законот за данокот на додадена вредност.<br>
        Оваа фактура се однесува на примен аванс и ќе биде одбиена од финалната фактура.<br>
        <em>Advance payment invoice per Art. 14 VAT Law. Will be deducted from final invoice.</em>
    </div>
    @endif

    {{-- Advance Deduction Table for Final Invoices (Article 53/9 ЗДДВ) --}}
    @if($invoice->type === 'final' && $invoice->advanceInvoices->count() > 0)
    <div style="margin-top: 10px; padding: 8px; border: 1px solid #198754; background: #f0fff4; font-size: 10px;">
        <strong>Одбивање на аванси (чл. 53 ст. 9 ЗДДВ) / Advance Deduction</strong>
        <table width="100%" style="margin-top: 5px; font-size: 9px;" cellpadding="3">
            <tr style="background: #e8f5e9;">
                <th align="left">Број / Number</th>
                <th align="left">Датум / Date</th>
                <th align="right">Износ / Amount</th>
            </tr>
            @foreach($invoice->advanceInvoices as $advance)
            <tr>
                <td>{{ $advance->invoice_number }}</td>
                <td>{{ $advance->formattedInvoiceDate }}</td>
                <td align="right">{!! format_money_pdf($advance->total, $invoice->customer->currency) !!}</td>
            </tr>
            @endforeach
            <tr style="border-top: 1px solid #198754; font-weight: bold;">
                <td colspan="2">Вкупно одбиени аванси / Total deducted:</td>
                <td align="right">{!! format_money_pdf($invoice->total_advances_amount, $invoice->customer->currency) !!}</td>
            </tr>
            <tr style="font-weight: bold; font-size: 11px;">
                <td colspan="2">ПРЕОСТАНАТО ЗА ПЛАЌАЊЕ / REMAINING DUE:</td>
                <td align="right">{!! format_money_pdf($invoice->remaining_after_advances, $invoice->customer->currency) !!}</td>
            </tr>
        </table>
    </div>
    @endif

    {{-- Payment Details --}}
    <div class="payment-details">
        <div class="payment-title">Детали за плаќање</div>
        <div><strong style="color:#999;">Валута:</strong> МКД (Македонски денар)</div>
        @if(optional($invoice->company->address)->zip)
            <div><strong style="color:#999;">Трансакциска сметка:</strong> {{ $invoice->company->address->zip }}</div>
        @endif
        <div><strong style="color:#999;">Начин на плаќање:</strong> Банкарски трансфер</div>
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

    {{-- CASYS QR Payment --}}
    @include('app.pdf.invoice.partials.casys-qr')

    {{-- Legal Footer --}}
    <div class="footer">
        <strong>Фактурата е валидна без печат и потпис согласно Законот за даночна постапка.</strong>
    </div>
    {{-- CLAUDE-CHECKPOINT --}}
</body>

</html>
