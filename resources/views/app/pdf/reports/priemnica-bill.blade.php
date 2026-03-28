<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Приемница — {{ $bill->bill_number ?? '' }}</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
            color: #333;
            margin: 15px;
        }

        table {
            border-collapse: collapse;
        }

        .report-header {
            width: 100%;
            margin-bottom: 10px;
        }

        .company-name {
            font-weight: bold;
            font-size: 12px;
        }

        .company-detail {
            font-size: 8.5px;
            color: #555;
        }

        .heading-text {
            font-weight: bold;
            font-size: 16px;
            color: #1a1a1a;
            text-align: center;
            margin: 12px 0 2px 0;
        }

        .sub-heading {
            font-size: 10px;
            color: #555;
            text-align: center;
            margin: 0 0 12px 0;
        }

        .doc-number {
            font-size: 10px;
            text-align: center;
            margin-bottom: 10px;
        }

        .info-section {
            width: 100%;
            margin-bottom: 10px;
        }

        .info-box {
            border: 1px solid #ccc;
            padding: 6px 8px;
            vertical-align: top;
        }

        .info-label {
            font-weight: bold;
            font-size: 9px;
            color: #2d2040;
            margin-bottom: 3px;
        }

        .info-box p {
            margin: 1px 0;
            font-size: 8.5px;
        }

        .ref-section {
            width: 100%;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            background: #f9f9f9;
        }

        .ref-section td {
            padding: 4px 8px;
            font-size: 8.5px;
        }

        .ref-label {
            font-weight: bold;
            color: #555;
            width: 30%;
        }

        .items-table {
            width: 100%;
            border: 1px solid #888;
            margin-top: 5px;
        }

        .items-table th {
            background: #2d2040;
            color: #ffffff;
            padding: 5px 4px;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
            border-bottom: 2px solid #555;
            border-right: 1px solid #444;
        }

        .items-table th:last-child {
            border-right: none;
        }

        .items-table td {
            padding: 4px 5px;
            font-size: 8px;
            color: #333;
            border-right: 1px solid #eee;
            border-bottom: 1px solid #ddd;
        }

        .items-table td:last-child {
            border-right: none;
        }

        .cell-center {
            text-align: center;
        }

        .cell-number {
            text-align: right;
        }

        .entry-row:nth-child(even) {
            background: #fafafa;
        }

        .totals-table {
            width: 50%;
            border: 1px solid #888;
            margin: 10px 0 0 auto;
        }

        .totals-table td {
            padding: 5px 8px;
            font-size: 9px;
            border-bottom: 1px solid #ddd;
        }

        .totals-label {
            font-weight: bold;
            background: #f8f8f8;
        }

        .totals-value {
            text-align: right;
            font-weight: bold;
        }

        .grand-total td {
            background: #2d2040;
            color: #ffffff;
            font-size: 10px;
            border-bottom: none;
        }

        .signature-section {
            margin-top: 40px;
            width: 100%;
        }

        .signature-label {
            font-size: 9px;
            color: #666;
            border-top: 1px solid #999;
            padding-top: 3px;
            width: 150px;
            text-align: center;
        }

        .stamp-placeholder {
            margin-top: 15px;
            text-align: center;
            font-size: 8px;
            color: #aaa;
        }

        .form-ref {
            font-size: 7px;
            color: #999;
            text-align: right;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    @php
        $currency = $bill->currency;
        $dateFormat = \App\Models\CompanySetting::getSetting('carbon_date_format', $company->id) ?: 'd.m.Y';
        $formattedBillDate = \Carbon\Carbon::parse($bill->bill_date)->format($dateFormat);
    @endphp

    {{-- Document Title --}}
    <p class="heading-text">ПРИЕМНИЦА</p>
    <p class="sub-heading">Приемница за стока</p>
    <p class="doc-number">
        Број: <strong>{{ $bill->bill_number }}</strong>
        &nbsp;&nbsp;|&nbsp;&nbsp;
        Датум: <strong>{{ $formattedBillDate }}</strong>
    </p>

    {{-- Company & Supplier Info --}}
    <table class="info-section">
        <tr>
            <td class="info-box" style="width: 48%;">
                <p class="info-label">ПРИМАЧ (Купувач):</p>
                <p><strong>{{ $company->name ?? '' }}</strong></p>
                @if($company->address)
                    <p>{{ $company->address->address_street_1 ?? '' }}@if($company->address->city), {{ $company->address->city }}@endif</p>
                @endif
                @if($company->vat_number)
                    <p>ЕДБ за ДДВ: {{ $company->vat_number }}</p>
                @endif
            </td>
            <td style="width: 4%;"></td>
            <td class="info-box" style="width: 48%;">
                <p class="info-label">ДОБАВУВАЧ (Испорачател):</p>
                @if($bill->supplier)
                    <p><strong>{{ $bill->supplier->name ?? '' }}</strong></p>
                    @if($bill->supplier->address_line_1)
                        <p>{{ $bill->supplier->address_line_1 }}@if($bill->supplier->city), {{ $bill->supplier->city }}@endif</p>
                    @endif
                    @if($bill->supplier->vat_number)
                        <p>ЕДБ за ДДВ: {{ $bill->supplier->vat_number }}</p>
                    @endif
                @endif
            </td>
        </tr>
    </table>

    {{-- Reference Section --}}
    <table class="ref-section">
        <tr>
            <td class="ref-label">Врз основа на фактура бр:</td>
            <td>{{ $bill->bill_number }}</td>
        </tr>
        <tr>
            <td class="ref-label">Датум на фактура:</td>
            <td>{{ $formattedBillDate }}</td>
        </tr>
    </table>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">Р.бр.</th>
                <th style="width: 35%;">Назив на артиклот</th>
                <th style="width: 12%;">Ед. мерка</th>
                <th style="width: 12%;">Количина</th>
                <th style="width: 16%;">Цена</th>
                <th style="width: 20%;">Вкупно</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bill->items as $i => $item)
                <tr class="entry-row">
                    <td class="cell-center">{{ $i + 1 }}</td>
                    <td>
                        {{ $item->name }}
                        @if($item->description)
                            <br><span style="font-size: 7px; color: #888;">{{ $item->description }}</span>
                        @endif
                    </td>
                    <td class="cell-center">{{ $item->unit_name ?? 'пар.' }}</td>
                    <td class="cell-number">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                    <td class="cell-number">{!! format_money_pdf($item->price, $currency) !!}</td>
                    <td class="cell-number">{!! format_money_pdf($item->total, $currency) !!}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <table class="totals-table">
        <tr>
            <td class="totals-label">Вкупно без ДДВ:</td>
            <td class="totals-value">{!! format_money_pdf($bill->sub_total, $currency) !!}</td>
        </tr>
        <tr>
            <td class="totals-label">ДДВ:</td>
            <td class="totals-value">{!! format_money_pdf($bill->tax, $currency) !!}</td>
        </tr>
        <tr class="grand-total">
            <td class="totals-label">Вкупно со ДДВ:</td>
            <td class="totals-value">{!! format_money_pdf($bill->total, $currency) !!}</td>
        </tr>
    </table>

    {{-- Signatures --}}
    <table class="signature-section">
        <tr>
            <td style="width: 33%; text-align: center; padding-top: 50px;">
                <p class="signature-label">Предал</p>
            </td>
            <td style="width: 34%; text-align: center; padding-top: 50px;">
                <p class="signature-label">Примил</p>
            </td>
            <td style="width: 33%; text-align: center; padding-top: 50px;">
                <p class="signature-label">Одобрил</p>
            </td>
        </tr>
    </table>

    <p class="stamp-placeholder">М.П.</p>

    <p class="form-ref">Приемница за стока — Закон за трговија (Сл. весник на РМ бр. 16/2004)</p>
</body>

</html>
