<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 20px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; line-height: 1.4; margin: 15px; }
        .header-bar { width: 100%; background: #2d3748; color: #fff; padding: 12px 16px; margin-bottom: 12px; }
        .header-bar td { vertical-align: middle; color: #fff; }
        .header-bar .doc-title { font-size: 16px; font-weight: bold; letter-spacing: 1px; }
        .header-bar .doc-date { font-size: 11px; text-align: right; }
        .company-info { margin-bottom: 10px; }
        .company-info p { margin: 1px 0; font-size: 10px; }
        .company-info .name { font-size: 13px; font-weight: bold; margin-bottom: 3px; }
        .partner-box { margin: 10px 0; padding: 8px 12px; border: 1px solid #ddd; background: #fafafa; }
        .partner-box .label { font-size: 9px; text-transform: uppercase; color: #999; letter-spacing: 1px; margin-bottom: 3px; }
        .partner-box .name { font-size: 12px; font-weight: bold; }
        .partner-box p { margin: 1px 0; font-size: 10px; }
        .invoice-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .invoice-table th { background: #5851D8; color: #fff; padding: 5px 6px; text-align: left; font-size: 9px; text-transform: uppercase; border: 1px solid #4840c0; }
        .invoice-table td { padding: 4px 6px; border: 1px solid #ccc; font-size: 10px; }
        .invoice-table .right { text-align: right; }
        .invoice-table .center { text-align: center; }
        .total-row { font-weight: bold; background: #f0eeff; }
        .summary-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        .summary-table td { padding: 5px 8px; font-size: 11px; }
        .summary-table .label { text-align: right; width: 65%; }
        .summary-table .value { text-align: right; width: 35%; font-weight: bold; }
        .balance-favor { color: #5851D8; font-weight: bold; }
        .confirm-text { margin: 14px 0; font-size: 10px; text-align: justify; color: #555; line-height: 1.6; padding: 8px 12px; border: 1px solid #ddd; background: #fffdf5; }
        .signatures { width: 100%; margin-top: 25px; }
        .signatures td { text-align: center; vertical-align: bottom; padding: 0 10px; }
        .sig-line { border-top: 1px solid #333; margin-top: 45px; padding-top: 4px; font-size: 10px; color: #555; }
        .sig-stamp { font-size: 9px; color: #999; margin-top: 2px; }
        .footer { margin-top: 15px; font-size: 9px; color: #999; text-align: center; border-top: 1px solid #ddd; padding-top: 6px; }
    </style>
</head>
<body>
    {{-- Header Bar --}}
    <table class="header-bar">
        <tr>
            <td style="width: 60%;">
                <div class="doc-title">ИЗВОД НА ОТВОРЕНИ СТАВКИ (ИОС)</div>
            </td>
            <td style="width: 40%;">
                <div class="doc-date">
                    Датум: {{ $date ?? now()->format('d.m.Y') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Company Info --}}
    <div class="company-info">
        <p class="name">{{ $company->name }}</p>
        @if($company->address ?? null)
            @if($company->address->address_street_1 ?? null)
                <p>{{ $company->address->address_street_1 }}</p>
            @endif
            @if(($company->address->city ?? null) || ($company->address->zip ?? null))
                <p>{{ $company->address->zip ?? '' }} {{ $company->address->city ?? '' }}</p>
            @endif
        @endif
        @if($company->vat_id ?? null)
            <p><strong>ЕДБ:</strong> {{ $company->vat_id }}</p>
        @endif
        @if($company->tax_id ?? null)
            <p><strong>ЕМБС:</strong> {{ $company->tax_id }}</p>
        @endif
    </div>

    {{-- Partner Info --}}
    <div class="partner-box">
        <p class="label">Партнер</p>
        <p class="name">{{ $partner_name ?? '' }}</p>
        @if($partner_vat_id ?? null)
            <p>ЕДБ: {{ $partner_vat_id }}</p>
        @endif
        @if($partner_address ?? null)
            <p>{{ $partner_address }}</p>
        @endif
    </div>

    {{-- Items Table --}}
    <table class="invoice-table">
        <thead>
            <tr>
                <th class="center" style="width: 5%;">#</th>
                <th class="center" style="width: 12%;">Датум</th>
                <th style="width: 28%;">Документ</th>
                <th class="right" style="width: 15%;">Должи</th>
                <th class="right" style="width: 15%;">Побарува</th>
                <th class="right" style="width: 15%;">Салдо</th>
            </tr>
        </thead>
        <tbody>
            @php $runningBalance = 0; @endphp
            @foreach($items as $i => $item)
                @php
                    $debit = $item['debit'] ?? 0;
                    $credit = $item['credit'] ?? 0;
                    $runningBalance += $debit - $credit;
                @endphp
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td class="center">{{ $item['date'] ?? '' }}</td>
                    <td>{{ $item['document'] ?? '' }}</td>
                    <td class="right">{{ $debit > 0 ? number_format($debit / 100, 2, '.', ',') : '' }}</td>
                    <td class="right">{{ $credit > 0 ? number_format($credit / 100, 2, '.', ',') : '' }}</td>
                    <td class="right">{{ number_format($runningBalance / 100, 2, '.', ',') }}</td>
                </tr>
            @endforeach
            {{-- Totals Row --}}
            <tr class="total-row">
                <td colspan="3" style="text-align: right;">ВКУПНО:</td>
                <td class="right">{{ number_format($total_debit / 100, 2, '.', ',') }}</td>
                <td class="right">{{ number_format($total_credit / 100, 2, '.', ',') }}</td>
                <td class="right">{{ number_format($balance / 100, 2, '.', ',') }}</td>
            </tr>
        </tbody>
    </table>

    {{-- Balance Summary --}}
    <table class="summary-table">
        @if($balance > 0)
            <tr>
                <td class="label">Салдо во наша корист:</td>
                <td class="value balance-favor">{{ number_format($balance / 100, 2, '.', ',') }} {{ $currency ?? 'МКД' }}</td>
            </tr>
        @elseif($balance < 0)
            <tr>
                <td class="label">Салдо во Ваша корист:</td>
                <td class="value" style="color: #c00;">{{ number_format(abs($balance) / 100, 2, '.', ',') }} {{ $currency ?? 'МКД' }}</td>
            </tr>
        @else
            <tr>
                <td class="label">Салдо:</td>
                <td class="value">0.00 {{ $currency ?? 'МКД' }}</td>
            </tr>
        @endif
    </table>

    {{-- Confirmation Text --}}
    <div class="confirm-text">
        <p>Ве молиме потврдете го салдото со потпис и печат и вратете го на нашата адреса во рок од 8 дена. Доколку не добиеме одговор во наведениот рок, ќе сметаме дека салдото е усогласено.</p>
    </div>

    {{-- Signatures --}}
    <table class="signatures">
        <tr>
            <td style="width: 33%;">
                <div class="sig-line">Изготвил</div>
            </td>
            <td style="width: 33%;">
                <div class="sig-line">Одговорно лице</div>
                <div class="sig-stamp">М.П.</div>
            </td>
            <td style="width: 33%;">
                <div class="sig-line">Партнер (потпис и печат)</div>
                <div class="sig-stamp">М.П.</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Документ генериран од Facturino | {{ $date ?? now()->format('d.m.Y') }}
    </div>
</body>
</html>
{{-- CLAUDE-CHECKPOINT --}}
