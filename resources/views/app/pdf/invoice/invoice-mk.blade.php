<!DOCTYPE html>
<html>
<head>
    <title>Фактура - {{ $invoice->invoice_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style type="text/css">
        body { font-family: "DejaVu Sans"; font-size: 11px; margin: 15px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; font-size: 20px; }
        .info-grid { width: 100%; margin-bottom: 15px; }
        .info-col { float: left; width: 48%; vertical-align: top; padding: 5px; }
        .section-title { font-weight: bold; font-size: 12px; margin-bottom: 5px; border-bottom: 1px solid #ccc; }
        .field-label { font-weight: bold; display: inline-block; width: 140px; }
        .field-value { display: inline; }
        table.items { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table.items th { background: #f0f0f0; padding: 6px; text-align: left; border: 1px solid #333; font-size: 10px; }
        table.items td { padding: 5px; border: 1px solid #ccc; font-size: 10px; }
        table.items .text-right { text-align: right; }
        table.items .text-center { text-align: center; }
        table.vat-summary { width: 60%; margin-left: auto; border-collapse: collapse; margin-top: 10px; }
        table.vat-summary th, table.vat-summary td { padding: 5px; border: 1px solid #333; text-align: right; }
        table.vat-summary th { background: #f0f0f0; font-weight: bold; }
        .totals { width: 50%; margin-left: auto; margin-top: 15px; border: 2px solid #333; }
        .totals td { padding: 8px; }
        .totals .total-label { font-weight: bold; text-align: right; }
        .totals .total-value { text-align: right; font-weight: bold; }
        .totals .grand-total { font-size: 14px; background: #f0f0f0; }
        .footer { margin-top: 20px; font-size: 10px; color: #666; }
        .notes-section { margin-top: 15px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        @if ($logo)
            <img style="height:40px; margin-bottom:5px;" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Лого">
        @endif
        <h1>ФАКТУРА</h1>
    </div>

    {{-- Issuer & Buyer Information --}}
    <div class="info-grid">
        {{-- Issuer (Supplier) Block --}}
        <div class="info-col" style="border-right: 1px solid #ccc;">
            <div class="section-title">ИЗДАВАЧ НА ФАКТУРА</div>
            <div><span class="field-label">Назив:</span> <span class="field-value">{{ $invoice->company->name ?? '' }}</span></div>
            @if($company_address)
                <div><span class="field-label">Адреса:</span> <span class="field-value">{!! str_replace('<br />', ', ', $company_address) !!}</span></div>
            @elseif($invoice->company && $invoice->company->address)
                {{-- Fallback: show address from relationship --}}
                <div><span class="field-label">Адреса:</span> <span class="field-value">
                    {{ $invoice->company->address->address_street_1 ?? '' }}
                    @if($invoice->company->address->address_street_2), {{ $invoice->company->address->address_street_2 }}@endif
                    @if($invoice->company->address->city), {{ $invoice->company->address->city }}@endif
                    @if($invoice->company->address->zip) {{ $invoice->company->address->zip }}@endif
                    @if($invoice->company->address->country_name), {{ $invoice->company->address->country_name }}@endif
                </span></div>
            @endif
            @if(isset($invoice->company->vat_id) && $invoice->company->vat_id)
                <div><span class="field-label">ЕДБ за ДДВ:</span> <span class="field-value">{{ $invoice->company->vat_id }}</span></div>
            @endif
            @if(isset($invoice->company->tax_id) && $invoice->company->tax_id)
                <div><span class="field-label">Даночен број (ЕМБС):</span> <span class="field-value">{{ $invoice->company->tax_id }}</span></div>
            @endif
            @if($invoice->company && $invoice->company->address && $invoice->company->address->phone)
                <div><span class="field-label">Телефон:</span> <span class="field-value">{{ $invoice->company->address->phone }}</span></div>
            @endif
            <div><span class="field-label">Место на издавање:</span> <span class="field-value">{{ optional($invoice->company->address)->city ?? 'Скопје' }}</span></div>
        </div>

        {{-- Buyer Block --}}
        <div class="info-col">
            <div class="section-title">ПРИМАТЕЛ НА ФАКТУРА</div>
            <div><span class="field-label">Назив:</span> <span class="field-value">{{ $invoice->customer->name ?? '' }}</span></div>
            @if($billing_address)
                <div><span class="field-label">Адреса:</span> <span class="field-value">{!! str_replace('<br />', ', ', $billing_address) !!}</span></div>
            @elseif($invoice->customer && $invoice->customer->billingAddress)
                {{-- Fallback: show address from relationship --}}
                <div><span class="field-label">Адреса:</span> <span class="field-value">
                    {{ $invoice->customer->billingAddress->address_street_1 ?? '' }}
                    @if($invoice->customer->billingAddress->address_street_2), {{ $invoice->customer->billingAddress->address_street_2 }}@endif
                    @if($invoice->customer->billingAddress->city), {{ $invoice->customer->billingAddress->city }}@endif
                    @if($invoice->customer->billingAddress->zip) {{ $invoice->customer->billingAddress->zip }}@endif
                    @if($invoice->customer->billingAddress->country_name), {{ $invoice->customer->billingAddress->country_name }}@endif
                </span></div>
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
    <table style="width:100%; margin-bottom:15px; border-collapse:collapse;">
        <tr>
            <td style="padding:5px; border:1px solid #333; width:25%;"><strong>Број на фактура:</strong></td>
            <td style="padding:5px; border:1px solid #333; width:25%;">{{ $invoice->invoice_number }}</td>
            <td style="padding:5px; border:1px solid #333; width:25%;"><strong>Датум на издавање:</strong></td>
            <td style="padding:5px; border:1px solid #333; width:25%;">{{ $invoice->formattedInvoiceDate }}</td>
        </tr>
        <tr>
            <td style="padding:5px; border:1px solid #333;"><strong>Ден на извршен промет:</strong></td>
            <td style="padding:5px; border:1px solid #333;">{{ $invoice->formattedInvoiceDate }}</td>
            <td style="padding:5px; border:1px solid #333;"><strong>Рок на плаќање:</strong></td>
            <td style="padding:5px; border:1px solid #333;">{{ $invoice->formattedDueDate }}</td>
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
            <td class="total-value" style="font-size:16px;">{!! format_money_pdf($invoice->total, $invoice->customer->currency) !!}</td>
        </tr>
        @if($invoice->due_amount > 0 && $invoice->paid_status !== App\Models\Invoice::STATUS_PAID)
            <tr>
                <td class="total-label">Преостанато за плаќање:</td>
                <td class="total-value">{!! format_money_pdf($invoice->due_amount, $invoice->customer->currency) !!}</td>
            </tr>
        @endif
    </table>

    {{-- Payment Details --}}
    <div style="margin-top: 15px; padding: 10px; border: 1px solid #ccc;">
        <div style="font-weight: bold; margin-bottom: 5px;">Детали за плаќање:</div>
        <div><strong>Валута:</strong> МКД (Македонски денар)</div>
        @if(optional($invoice->company->address)->zip)
            <div><strong>Трансакциска сметка:</strong> {{ $invoice->company->address->zip }}</div>
        @endif
        <div><strong>Начин на плаќање:</strong> Банкарски трансфер</div>
    </div>

    {{-- Notes --}}
    @if($notes)
        <div class="notes-section">
            <div style="font-weight: bold; margin-bottom: 5px;">Забелешки:</div>
            {!! $notes !!}
        </div>
    @endif

    {{-- Footer / Legal Text --}}
    <div class="footer" style="margin-top: 20px; border-top: 1px solid #ccc; padding-top: 10px;">
        <div style="text-align: center;">
            <p><strong>Фактурата е валидна без печат и потпис согласно Законот за даночна постапка.</strong></p>
        </div>
    </div>
</body>
</html>
