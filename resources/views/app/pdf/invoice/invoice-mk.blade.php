<!DOCTYPE html>
<html>
<head>
    <title>@if($invoice->type === 'advance')Аванс фактура@elseif($invoice->type === 'final')Финална фактура@else Фактура @endif - {{ $invoice->invoice_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
        body { font-family: "DejaVu Sans"; font-size: 10px; margin: 10px; }
        .header { text-align: center; margin-bottom: 8px; border-bottom: 2px solid #333; padding-bottom: 5px; }
        .header h1 { margin: 0; font-size: 16px; }
        .info-grid { width: 100%; margin-bottom: 8px; }
        .info-col { float: left; width: 48%; vertical-align: top; padding: 3px; }
        .section-title { font-weight: bold; font-size: 11px; margin-bottom: 3px; border-bottom: 1px solid #ccc; }
        .field-label { font-weight: bold; display: inline-block; width: 130px; }
        .field-value { display: inline; }
        table.items { width: 100%; border-collapse: collapse; margin: 4px 0; }
        table.items th { background: #f0f0f0; padding: 4px; text-align: left; border: 1px solid #333; font-size: 9px; }
        table.items td { padding: 3px; border: 1px solid #ccc; font-size: 9px; }
        table.items .text-right { text-align: right; }
        table.items .text-center { text-align: center; }
        table.vat-summary { width: 60%; margin-left: auto; border-collapse: collapse; margin-top: 5px; }
        table.vat-summary th, table.vat-summary td { padding: 3px; border: 1px solid #333; text-align: right; }
        table.vat-summary th { background: #f0f0f0; font-weight: bold; }
        .totals { width: 50%; margin-left: auto; margin-top: 4px; border: 2px solid #333; }
        .totals td { padding: 3px; }
        .totals .total-label { font-weight: bold; text-align: right; }
        .totals .total-value { text-align: right; font-weight: bold; }
        .totals .grand-total { font-size: 13px; background: #f0f0f0; }
        .footer { margin-top: 10px; font-size: 9px; color: #666; }
        .notes-section { margin-top: 4px; padding: 4px; background: #f9f9f9; border: 1px solid #ddd; font-size: 8px; }
        .notes-section p { margin: 1px 0; }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        @if ($logo)
            <img style="height:30px; margin-bottom:3px;" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Лого">
        @endif
        <h1>
            @if($invoice->type === 'advance')
                АВАНС ФАКТУРА
            @elseif($invoice->type === 'final')
                ФИНАЛНА ФАКТУРА
            @else
                ФАКТУРА
            @endif
        </h1>
    </div>

    {{-- Issuer & Buyer Information --}}
    <div class="info-grid">
        {{-- Issuer (Supplier) Block --}}
        <div class="info-col" style="border-right: 1px solid #ccc;">
            <div class="section-title">ИЗДАВАЧ НА ФАКТУРА</div>
            <div><span class="field-label">Назив:</span> <span class="field-value">{{ $invoice->company->name ?? '' }}</span></div>
            @php
                $addr = $invoice->company ? $invoice->company->address : null;
            @endphp
            @if($addr)
                <div><span class="field-label">Адреса:</span> <span class="field-value">
                    {{ $addr->address_street_1 ?? '' }}
                    @if($addr->address_street_2), {{ $addr->address_street_2 }}@endif
                </span></div>
                @if($addr->city || $addr->zip)
                    <div><span class="field-label"></span> <span class="field-value">
                        {{ $addr->city ?? '' }}@if($addr->zip) {{ $addr->zip }}@endif
                        @if($addr->country && $addr->country->name), {{ $addr->country->name }}@endif
                    </span></div>
                @endif
            @endif
            @if(isset($invoice->company->vat_id) && $invoice->company->vat_id)
                <div><span class="field-label">ЕДБ за ДДВ:</span> <span class="field-value">{{ $invoice->company->vat_id }}</span></div>
            @endif
            @if(isset($invoice->company->tax_id) && $invoice->company->tax_id)
                <div><span class="field-label">Даночен број (ЕМБС):</span> <span class="field-value">{{ $invoice->company->tax_id }}</span></div>
            @endif
            @if($addr && $addr->phone)
                <div><span class="field-label">Телефон:</span> <span class="field-value">{{ $addr->phone }}</span></div>
            @endif
            <div><span class="field-label">Место на издавање:</span> <span class="field-value">{{ optional($addr)->city ?? 'Скопје' }}</span></div>
        </div>

        {{-- Buyer Block --}}
        <div class="info-col">
            <div class="section-title">ПРИМАТЕЛ НА ФАКТУРА</div>
            <div><span class="field-label">Назив:</span> <span class="field-value">{{ $invoice->customer->name ?? '' }}</span></div>
            @php
                $custAddr = $invoice->customer ? $invoice->customer->billingAddress : null;
            @endphp
            @if($custAddr)
                <div><span class="field-label">Адреса:</span> <span class="field-value">
                    {{ $custAddr->address_street_1 ?? '' }}
                    @if($custAddr->address_street_2), {{ $custAddr->address_street_2 }}@endif
                </span></div>
                @if($custAddr->city || $custAddr->zip)
                    <div><span class="field-label"></span> <span class="field-value">
                        {{ $custAddr->city ?? '' }}@if($custAddr->zip) {{ $custAddr->zip }}@endif
                        @if($custAddr->country && $custAddr->country->name), {{ $custAddr->country->name }}@endif
                    </span></div>
                @endif
            @endif
            @if(isset($invoice->customer->vat_number) && $invoice->customer->vat_number)
                <div><span class="field-label">ЕДБ за ДДВ:</span> <span class="field-value">{{ $invoice->customer->vat_number }}</span></div>
            @endif
            @if(isset($invoice->customer->tax_id) && $invoice->customer->tax_id)
                <div><span class="field-label">ЕМБС:</span> <span class="field-value">{{ $invoice->customer->tax_id }}</span></div>
            @endif
            @if($invoice->customer && $invoice->customer->phone)
                <div><span class="field-label">Телефон:</span> <span class="field-value">{{ $invoice->customer->phone }}</span></div>
            @endif
        </div>
    </div>
    <div style="clear: both;"></div>

    {{-- Invoice Metadata --}}
    <table style="width:100%; margin-bottom:6px; border-collapse:collapse;">
        <tr>
            <td style="padding:3px; border:1px solid #333; width:25%;"><strong>Број на фактура:</strong></td>
            <td style="padding:3px; border:1px solid #333; width:25%;">{{ $invoice->invoice_number }}</td>
            <td style="padding:3px; border:1px solid #333; width:25%;"><strong>Датум на издавање:</strong></td>
            <td style="padding:3px; border:1px solid #333; width:25%;">{{ $invoice->formattedInvoiceDate }}</td>
        </tr>
        <tr>
            <td style="padding:3px; border:1px solid #333;"><strong>Ден на извршен промет:</strong></td>
            <td style="padding:3px; border:1px solid #333;">{{ $invoice->formattedInvoiceDate }}</td>
            <td style="padding:3px; border:1px solid #333;"><strong>Рок на плаќање:</strong></td>
            <td style="padding:3px; border:1px solid #333;">{{ $invoice->formattedDueDate }}</td>
        </tr>
    </table>

    {{-- Items Table --}}
    <table class="items">
        <thead>
            <tr>
                <th style="width:4%;">#</th>
                <th style="width:35%;">Опис</th>
                <th style="width:8%;">Количина</th>
                <th style="width:8%;">Единица</th>
                <th style="width:12%;">Ед. цена без ДДВ</th>
                <th style="width:12%;">Износ без ДДВ</th>
                <th style="width:8%;">Стапка</th>
                <th style="width:13%;">ДДВ износ</th>
            </tr>
        </thead>
        <tbody>
            @php $index = 1; @endphp
            @foreach ($invoice->items as $item)
                @php
                    // Calculate base amount (price before tax)
                    $itemBaseAmount = $item->total;
                    $itemTaxPercent = 0;

                    // Get tax percentage for this item
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
                            <br><span style="font-size:9px; color:#666;">{{ $item->description }}</span>
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

    {{-- VAT Summary (grouped by rate) --}}
    <table class="vat-summary">
        <thead>
            <tr>
                <th>Даночна стапка</th>
                <th>Основица</th>
                <th>ДДВ износ</th>
            </tr>
        </thead>
        <tbody>
            @php
                // Group taxes by rate
                $taxesByRate = collect();

                if ($invoice->tax_per_item === 'YES') {
                    foreach ($invoice->items as $item) {
                        foreach ($item->taxes as $tax) {
                            $rate = $tax->percent ?? 0;
                            if (!$taxesByRate->has($rate)) {
                                $taxesByRate->put($rate, ['base' => 0, 'tax' => 0]);
                            }

                            // Get existing values, modify, and put back
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
    <table class="totals">
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
            <td class="total-value" style="font-size:13px;">{!! format_money_pdf($invoice->total, $invoice->customer->currency) !!}</td>
        </tr>
        @if($invoice->due_amount > 0 && $invoice->paid_status !== App\Models\Invoice::STATUS_PAID)
            <tr>
                <td class="total-label">Преостанато за плаќање:</td>
                <td class="total-value">{!! format_money_pdf($invoice->due_amount, $invoice->customer->currency) !!}</td>
            </tr>
        @endif
    </table>

    {{-- Reverse Charge Notice (Article 32-а ЗДДВ) --}}
    @if($invoice->is_reverse_charge)
        <div style="margin-top: 6px; padding: 4px; border: 1px solid #c0392b; background: #fdedec; font-size: 9px;">
            <strong>ПРЕНЕСУВАЊЕ НА ДАНОЧНА ОБВРСКА</strong> — Член 32-а од Законот за данокот на додадена вредност.
            Даночен должник е примателот на прометот. ДДВ не е пресметан на оваа фактура.
        </div>
    @endif

    {{-- Advance Invoice Notice (Article 14 ЗДДВ) --}}
    @if($invoice->type === 'advance')
        <div style="margin-top: 6px; padding: 4px; border: 1px solid #e6a817; background: #fef9e7; font-size: 9px;">
            <strong>АВАНС ФАКТУРА</strong> — Издадена согласно чл. 14 од Законот за данокот на додадена вредност.
            Оваа фактура се однесува на примен аванс (предуплата) и ќе биде одбиена од финалната фактура.
        </div>
    @endif

    {{-- Advance Deduction Table for Final Invoices (Article 53/9 ЗДДВ) --}}
    @if($invoice->type === 'final' && $invoice->advanceInvoices->count() > 0)
        <table style="width: 100%; border-collapse: collapse; margin-top: 6px;">
            <thead>
                <tr>
                    <th colspan="3" style="padding: 4px; background: #e8f5e9; border: 1px solid #333; text-align: left; font-size: 10px;">
                        Одбивање на аванси (чл. 53 ст. 9 ЗДДВ)
                    </th>
                </tr>
                <tr>
                    <th style="padding: 3px; background: #f0f0f0; border: 1px solid #333; text-align: left; font-size: 9px;">Аванс фактура бр.</th>
                    <th style="padding: 3px; background: #f0f0f0; border: 1px solid #333; text-align: left; font-size: 9px;">Датум</th>
                    <th style="padding: 3px; background: #f0f0f0; border: 1px solid #333; text-align: right; font-size: 9px;">Износ</th>
                </tr>
            </thead>
            <tbody>
                @php $totalAdvances = 0; @endphp
                @foreach($invoice->advanceInvoices as $advance)
                    @php $totalAdvances += $advance->total; @endphp
                    <tr>
                        <td style="padding: 3px; border: 1px solid #ccc; font-size: 9px;">{{ $advance->invoice_number }}</td>
                        <td style="padding: 3px; border: 1px solid #ccc; font-size: 9px;">{{ $advance->formattedInvoiceDate }}</td>
                        <td style="padding: 3px; border: 1px solid #ccc; font-size: 9px; text-align: right;">
                            {!! format_money_pdf($advance->total, $invoice->customer->currency) !!}
                        </td>
                    </tr>
                @endforeach
                <tr style="background: #f0f0f0;">
                    <td colspan="2" style="padding: 3px; border: 1px solid #333; font-weight: bold; text-align: right; font-size: 9px;">
                        Вкупно одбиени аванси:
                    </td>
                    <td style="padding: 3px; border: 1px solid #333; font-weight: bold; text-align: right; font-size: 9px;">
                        -{!! format_money_pdf($totalAdvances, $invoice->customer->currency) !!}
                    </td>
                </tr>
                <tr style="background: #e8f5e9;">
                    <td colspan="2" style="padding: 4px; border: 1px solid #333; font-weight: bold; text-align: right; font-size: 10px;">
                        ПРЕОСТАНАТО ЗА ПЛАЌАЊЕ:
                    </td>
                    <td style="padding: 4px; border: 1px solid #333; font-weight: bold; text-align: right; font-size: 10px;">
                        {!! format_money_pdf($invoice->total - $totalAdvances, $invoice->customer->currency) !!}
                    </td>
                </tr>
            </tbody>
        </table>
    @endif

    {{-- Payment Details --}}
    <div style="margin-top: 6px; padding: 4px; border: 1px solid #ccc; font-size: 9px;">
        <div style="font-weight: bold; margin-bottom: 2px;">Детали за плаќање:</div>
        <div><strong>Валута:</strong> МКД (Македонски денар)</div>
        @if(optional($invoice->company->address)->zip)
            <div><strong>Трансакциска сметка:</strong> {{ $invoice->company->address->zip }}</div>
        @endif
        <div><strong>Начин на плаќање:</strong> Банкарски трансфер</div>
    </div>

    {{-- Notes --}}
    @if($notes)
        <div class="notes-section">
            <div style="font-weight: bold; margin-bottom: 2px;">Забелешки:</div>
            {!! $notes !!}
        </div>
    @endif

    {{-- Stamp & Signature --}}
    @if(isset($stamp) && $stamp || isset($signature) && $signature)
    <table style="width:100%; margin-top:6px; border:none;">
        <tr>
            <td style="width:50%; text-align:center; border:none;">
                @if(isset($stamp) && $stamp)
                    <img src="{{ \App\Space\ImageUtils::toBase64Src($stamp) }}" style="height:60px;" alt="Печат">
                    <div style="font-size:7px; color:#666;">Печат / Stamp</div>
                @endif
            </td>
            <td style="width:50%; text-align:center; border:none;">
                @if(isset($signature) && $signature)
                    <img src="{{ \App\Space\ImageUtils::toBase64Src($signature) }}" style="height:60px;" alt="Потпис">
                    <div style="font-size:7px; color:#666;">Овластен потпис / Signature</div>
                @endif
            </td>
        </tr>
    </table>
    @endif

    {{-- Footer / Legal Text --}}
    <div class="footer" style="margin-top: 6px; border-top: 1px solid #ccc; padding-top: 3px;">
        <div style="text-align: center; font-size: 8px;">
            <strong>Фактурата е валидна без печат и потпис согласно Законот за даночна постапка.</strong>
        </div>
    </div>
</body>
</html>
