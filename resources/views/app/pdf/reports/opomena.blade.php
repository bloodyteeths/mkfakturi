<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 20px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header-table { width: 100%; margin-bottom: 10px; }
        .header-table td { vertical-align: top; }
        .company-logo { height: 40px; margin-bottom: 4px; }
        .company-name { font-size: 13px; font-weight: bold; margin: 0 0 2px 0; }
        .company-detail { font-size: 10px; color: #555; margin: 1px 0; }
        .title { font-size: 16px; font-weight: bold; text-align: center; margin: 10px 0 4px 0; text-transform: uppercase; letter-spacing: 1px; }
        .subtitle { font-size: 10px; text-align: center; color: #555; margin: 0 0 10px 0; }
        .recipient { margin: 8px 0; padding: 8px 12px; border: 1px solid #ddd; background: #fafafa; }
        .recipient p { margin: 1px 0; }
        .recipient-label { font-size: 9px; text-transform: uppercase; color: #999; letter-spacing: 1px; margin-bottom: 3px; }
        .body-text { margin: 8px 0; text-align: justify; }
        .body-text p { margin: 3px 0; }
        .invoice-table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .invoice-table th { background: #f0f0f0; padding: 5px 8px; text-align: left; font-size: 10px; text-transform: uppercase; border: 1px solid #ccc; }
        .invoice-table td { padding: 5px 8px; border: 1px solid #ccc; }
        .invoice-table .right { text-align: right; }
        .total-row { font-weight: bold; background: #f8f8f8; }
        .interest-row { color: #c00; }
        .legal-text { font-size: 10px; color: #555; margin: 10px 0; padding: 8px; border-left: 3px solid #c00; background: #fff5f5; }
        .legal-text p { margin: 0; }
        .signature-area { margin-top: 20px; }
        .signature-line { width: 200px; border-top: 1px solid #333; margin-top: 25px; padding-top: 4px; font-size: 10px; color: #555; }
        .footer { margin-top: 15px; font-size: 9px; color: #999; text-align: center; border-top: 1px solid #ddd; padding-top: 6px; }
    </style>
</head>
<body>
    {{-- Company Header --}}
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                @if($logo)
                    <img class="company-logo" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Лого">
                @endif
                <p class="company-name">{{ $company->name }}</p>
                @if($company->address)
                    @if($company->address->address_street_1)
                        <p class="company-detail">{{ $company->address->address_street_1 }}</p>
                    @endif
                    @if($company->address->city || $company->address->zip)
                        <p class="company-detail">{{ $company->address->zip }} {{ $company->address->city }}</p>
                    @endif
                @endif
                @if($company->vat_id)
                    <p class="company-detail"><strong>ЕДБ:</strong> {{ $company->vat_id }}</p>
                @endif
                @if($company->tax_id)
                    <p class="company-detail"><strong>ЕМБС:</strong> {{ $company->tax_id }}</p>
                @endif
            </td>
            <td style="width: 40%; text-align: right;">
                <p class="company-detail"><strong>Датум:</strong> {{ $today }}</p>
                <p class="company-detail"><strong>Бр. на опомена:</strong> ОП-{{ $invoice->invoice_number }}</p>
                @if($reminder_count > 0)
                    <p class="company-detail"><strong>Потсетници:</strong> {{ $reminder_count }}</p>
                @endif
            </td>
        </tr>
    </table>

    <h1 class="title">ОПОМЕНА ЗА ПЛАЌАЊЕ</h1>
    <p class="subtitle">Согласно Закон за облигациони односи (Сл. весник на РМ)</p>

    {{-- Recipient --}}
    <div class="recipient">
        <p class="recipient-label">Должник / Примател</p>
        <p style="font-weight: bold; font-size: 12px;">{{ $customer->name }}</p>
        @if($customer->billing_address ?? null)
            @if($customer->billing_address->address_street_1 ?? null)
                <p>{{ $customer->billing_address->address_street_1 }}</p>
            @endif
            @if(($customer->billing_address->city ?? null) || ($customer->billing_address->zip ?? null))
                <p>{{ $customer->billing_address->zip ?? '' }} {{ $customer->billing_address->city ?? '' }}</p>
            @endif
        @endif
        @if($customer->email)
            <p>{{ $customer->email }}</p>
        @endif
    </div>

    {{-- Body --}}
    <div class="body-text">
        <p>Почитувани,</p>
        <p>Со ова Ве известуваме дека следната фактура е неплатена и достасана за плаќање:</p>
    </div>

    {{-- Invoice Details Table --}}
    <table class="invoice-table">
        <thead>
            <tr>
                <th>Опис</th>
                <th class="right">Износ</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Фактура бр. <strong>{{ $invoice->invoice_number }}</strong></td>
                <td class="right">{{ number_format($invoice->total / 100, 2, '.', ',') }} {{ $currency_symbol }}</td>
            </tr>
            <tr>
                <td>Достасување</td>
                <td class="right">{{ $due_date }}</td>
            </tr>
            <tr>
                <td>Дена задоцнување</td>
                <td class="right">{{ $days_overdue }}</td>
            </tr>
            <tr>
                <td>Неплатен износ (главнина)</td>
                <td class="right"><strong>{{ number_format($due_amount / 100, 2, '.', ',') }} {{ $currency_symbol }}</strong></td>
            </tr>
            <tr class="interest-row">
                <td>Законска камата ({{ $interest_rate }}% годишно, {{ $days_overdue }} дена)</td>
                <td class="right">{{ number_format($interest_amount / 100, 2, '.', ',') }} {{ $currency_symbol }}</td>
            </tr>
            <tr class="total-row">
                <td>ВКУПНО ЗА ПЛАЌАЊЕ (главнина + камата)</td>
                <td class="right" style="font-size: 13px; color: #c00;">{{ number_format($total_with_interest / 100, 2, '.', ',') }} {{ $currency_symbol }}</td>
            </tr>
        </tbody>
    </table>

    <div class="body-text">
        <p>Ве молиме горенаведениот износ да го платите во рок од <strong>8 (осум) дена</strong> од приемот на оваа опомена.</p>
        <p>Доколку уплатата е веќе извршена, ве молиме занемарете ја оваа опомена и известете нè.</p>
    </div>

    <div class="legal-text">
        <p><strong>Правна напомена:</strong> Доколку уплатата не биде извршена во наведениот рок, ќе бидеме принудени да покренеме постапка за принудна наплата согласно Законот за облигациони односи, при што ќе бидат пресметани дополнителни трошоци за наплата и законска камата до денот на конечното плаќање.</p>
    </div>

    {{-- Signature --}}
    <div class="signature-area">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%;"></td>
                <td style="width: 50%; text-align: center;">
                    <div class="signature-line">
                        Овластено лице / Печат
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Документ генериран од Facturino | {{ $today }}
    </div>
</body>
</html>
