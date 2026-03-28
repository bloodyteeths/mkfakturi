<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 30px 40px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; line-height: 1.5; }
        .header-table { width: 100%; margin-bottom: 20px; }
        .header-table td { vertical-align: top; }
        .company-name { font-size: 14px; font-weight: bold; margin: 0 0 3px 0; }
        .company-detail { font-size: 10px; color: #555; margin: 1px 0; }
        .title { font-size: 18px; font-weight: bold; text-align: center; margin: 25px 0 10px 0; text-transform: uppercase; letter-spacing: 1px; }
        .subtitle { font-size: 11px; text-align: center; color: #555; margin: 0 0 25px 0; }
        .recipient { margin: 20px 0; padding: 12px 15px; border: 1px solid #ddd; background: #fafafa; }
        .recipient p { margin: 2px 0; }
        .recipient-label { font-size: 9px; text-transform: uppercase; color: #999; letter-spacing: 1px; margin-bottom: 5px; }
        .body-text { margin: 15px 0; text-align: justify; }
        .invoice-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .invoice-table th { background: #f0f0f0; padding: 8px 10px; text-align: left; font-size: 10px; text-transform: uppercase; border: 1px solid #ccc; }
        .invoice-table td { padding: 8px 10px; border: 1px solid #ccc; }
        .invoice-table .right { text-align: right; }
        .invoice-table .center { text-align: center; }
        .subtotal-row { font-weight: bold; background: #f8f8f8; }
        .total-row { font-weight: bold; background: #f0f0f0; }
        .total-row td { font-size: 12px; color: #c00; }
        .interest-col { color: #c00; }
        .legal-text { font-size: 10px; color: #555; margin: 20px 0; padding: 10px; border-left: 3px solid #c00; background: #fff5f5; }
        .signature-area { margin-top: 40px; }
        .signature-line { width: 200px; border-top: 1px solid #333; margin-top: 40px; padding-top: 5px; font-size: 10px; color: #555; }
        .footer { margin-top: 30px; font-size: 9px; color: #999; text-align: center; border-top: 1px solid #ddd; padding-top: 10px; }
    </style>
</head>
<body>
    {{-- Company Header --}}
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
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
                @php
                    $bankAccount = $company->settings()->where('option', 'bank_account_number')->first()?->value
                        ?? $company->settings()->where('option', 'bank_account')->first()?->value
                        ?? null;
                @endphp
                @if($bankAccount)
                    <p class="company-detail"><strong>Жиро-сметка:</strong> {{ $bankAccount }}</p>
                @endif
            </td>
            <td style="width: 40%; text-align: right;">
                <p class="company-detail"><strong>Датум:</strong> {{ $today }}</p>
                <p class="company-detail"><strong>Бр.:</strong> {{ $note_number }}</p>
            </td>
        </tr>
    </table>

    <h1 class="title">КАМАТНА НОТА</h1>
    <p class="subtitle">Согласно Закон за облигациони односи, чл. 266-269 (Сл. весник на РМ)</p>

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
        @if(!empty($customer->tax_id))
            <p style="font-size: 10px; color: #555;"><strong>ЕДБ:</strong> {{ $customer->tax_id }}</p>
        @endif
        @if(!empty($customer->vat_number))
            <p style="font-size: 10px; color: #555;"><strong>ДДВ бр.:</strong> {{ $customer->vat_number }}</p>
        @endif
    </div>

    {{-- Body --}}
    <div class="body-text">
        <p>Почитувани,</p>
        <p>Ве известуваме дека за следните фактури е пресметана законска камата поради задоцнето плаќање. Каматата е пресметана по годишна стапка од <strong>{{ number_format($annual_rate, 2, ',', '.') }}%</strong>, за период до <strong>{{ $today }}</strong>.</p>
    </div>

    {{-- Multi-Invoice Interest Table --}}
    <table class="invoice-table">
        <thead>
            <tr>
                <th class="center" style="width: 30px;">№</th>
                <th>Фактура</th>
                <th class="center">Достасување</th>
                <th class="center">Период</th>
                <th class="center">Дена</th>
                <th class="right">Главнина ({{ $currency_symbol }})</th>
                <th class="center">Стапка</th>
                <th class="right">Камата ({{ $currency_symbol }})</th>
            </tr>
        </thead>
        <tbody>
            @foreach($calculations as $index => $calc)
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td><strong>{{ $calc->invoice->invoice_number ?? 'N/A' }}</strong></td>
                    <td class="center">
                        @if($calc->invoice && $calc->invoice->due_date)
                            {{ \Carbon\Carbon::parse($calc->invoice->due_date)->format('d.m.Y') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="center" style="font-size: 9px;">
                        @if($calc->invoice && $calc->invoice->due_date)
                            {{ \Carbon\Carbon::parse($calc->invoice->due_date)->format('d.m.Y') }} —<br>{{ \Carbon\Carbon::parse($calc->calculation_date)->format('d.m.Y') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="center">{{ $calc->days_overdue }}</td>
                    <td class="right">{{ number_format($calc->principal_amount / 100, 2, ',', '.') }}</td>
                    <td class="center">{{ number_format($calc->annual_rate, 2, ',', '.') }}%</td>
                    <td class="right interest-col"><strong>{{ number_format($calc->interest_amount / 100, 2, ',', '.') }}</strong></td>
                </tr>
            @endforeach
            <tr class="subtotal-row">
                <td colspan="5" style="text-align: right;">ВКУПНО ГЛАВНИНА / КАМАТА</td>
                <td class="right">{{ number_format($total_principal / 100, 2, ',', '.') }} {{ $currency_symbol }}</td>
                <td></td>
                <td class="right interest-col">{{ number_format($total_interest / 100, 2, ',', '.') }} {{ $currency_symbol }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="7" style="text-align: right;">ВКУПНО ЗА ПЛАЌАЊЕ (главнина + камата)</td>
                <td class="right">{{ number_format($grand_total / 100, 2, ',', '.') }} {{ $currency_symbol }}</td>
            </tr>
        </tbody>
    </table>

    <p style="margin: 10px 0; font-size: 11px;">
        <strong>Словима:</strong> {{ $amount_in_words ?? '' }}
    </p>

    <div class="body-text">
        <p>Ве молиме горенаведениот износ да го платите во рок од <strong>8 (осум) дена</strong> од приемот на оваа каматна нота.</p>
        <p>Доколку уплатата е веќе извршена, ве молиме занемарете ја оваа нота и известете нè.</p>
    </div>

    <div class="legal-text">
        <p><strong>Правна напомена:</strong> Каматата е пресметана согласно Законот за облигациони односи (чл. 266-269). Доколку уплатата не биде извршена во наведениот рок, ќе бидеме принудени да покренеме постапка за принудна наплата, при што ќе бидат пресметани дополнителни трошоци и камата до денот на конечното плаќање.</p>
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
