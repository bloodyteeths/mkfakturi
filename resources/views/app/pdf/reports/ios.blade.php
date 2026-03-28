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
        .invoice-table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .invoice-table th { background: #f0f0f0; padding: 5px 6px; text-align: left; font-size: 9px; text-transform: uppercase; border: 1px solid #ccc; }
        .invoice-table td { padding: 4px 6px; border: 1px solid #ccc; font-size: 10px; }
        .invoice-table .right { text-align: right; }
        .invoice-table .center { text-align: center; }
        .total-row { font-weight: bold; background: #f8f8f8; }
        .interest-col { color: #c00; }
        .summary-table { width: 100%; border-collapse: collapse; margin: 8px 0; }
        .summary-table td { padding: 4px 8px; font-size: 11px; }
        .summary-table .label { text-align: right; width: 70%; }
        .summary-table .value { text-align: right; width: 30%; font-weight: bold; }
        .confirm-text { margin: 12px 0; font-size: 11px; text-align: justify; }
        .signature-area { margin-top: 20px; }
        .signature-line { width: 200px; border-top: 1px solid #333; margin-top: 40px; padding-top: 4px; font-size: 10px; color: #555; text-align: center; }
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
                <p class="company-detail"><strong>Датум на извод:</strong> {{ $today }}</p>
            </td>
        </tr>
    </table>

    <h1 class="title">ИЗВОД НА ОТВОРЕНИ СТАВКИ (ИОС)</h1>
    <p class="subtitle">Датум на извод: {{ $today }}</p>

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

    {{-- Invoice Table --}}
    <table class="invoice-table">
        <thead>
            <tr>
                <th class="center">Р.бр.</th>
                <th>Број на фактура</th>
                <th class="center">Датум на фактура</th>
                <th class="center">Достасување</th>
                <th class="right">Вкупно</th>
                <th class="right">Платено</th>
                <th class="right">Остаток</th>
                <th class="center">Дена задоцн.</th>
                <th class="right">Камата</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $i => $item)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td>{{ $item['invoice_number'] }}</td>
                    <td class="center">{{ $item['invoice_date'] }}</td>
                    <td class="center">{{ $item['due_date'] }}</td>
                    <td class="right">{{ number_format($item['total'] / 100, 2, '.', ',') }}</td>
                    <td class="right">{{ number_format(($item['total'] - $item['due_amount']) / 100, 2, '.', ',') }}</td>
                    <td class="right">{{ number_format($item['due_amount'] / 100, 2, '.', ',') }}</td>
                    <td class="center">{{ $item['days_overdue'] }}</td>
                    <td class="right interest-col">{{ number_format($item['interest'] / 100, 2, '.', ',') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Summary --}}
    <table class="summary-table">
        <tr>
            <td class="label">Вкупен остаток (главнина):</td>
            <td class="value">{{ number_format($subtotal_due / 100, 2, '.', ',') }} {{ $currency_symbol }}</td>
        </tr>
        <tr>
            <td class="label" style="color: #c00;">Вкупна камата ({{ $interest_rate }}% годишно):</td>
            <td class="value" style="color: #c00;">{{ number_format($subtotal_interest / 100, 2, '.', ',') }} {{ $currency_symbol }}</td>
        </tr>
        <tr>
            <td class="label" style="border-top: 2px solid #333; padding-top: 6px;">ВКУПНО ЗА ПЛАЌАЊЕ:</td>
            <td class="value" style="border-top: 2px solid #333; padding-top: 6px; font-size: 13px; color: #c00;">{{ number_format($subtotal_total_with_interest / 100, 2, '.', ',') }} {{ $currency_symbol }}</td>
        </tr>
    </table>

    <div class="confirm-text">
        <p>Ве молиме потврдете го салдото со потпис и печат и вратете го на нашата адреса.</p>
    </div>

    {{-- Signature Areas --}}
    <div class="signature-area">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; text-align: center;">
                    <div class="signature-line">
                        Должник (потпис и печат)
                    </div>
                </td>
                <td style="width: 50%; text-align: center;">
                    <div class="signature-line">
                        Доверител (потпис и печат)
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
