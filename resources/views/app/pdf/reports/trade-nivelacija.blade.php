<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Нивелација бр. {{ $nivelacija->document_number ?? '' }}</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 8px;
            color: #333;
            margin: 15px;
        }

        table {
            border-collapse: collapse;
        }

        .report-header {
            width: 100%;
            margin-bottom: 5px;
        }

        .company-name {
            font-weight: bold;
            font-size: 11px;
        }

        .company-detail {
            font-size: 8px;
            color: #555;
        }

        .heading-text {
            font-weight: bold;
            font-size: 14px;
            color: #1a1a1a;
            text-align: center;
            margin: 8px 0 2px 0;
        }

        .sub-heading {
            font-size: 10px;
            color: #555;
            text-align: center;
            margin: 0 0 10px 0;
        }

        .doc-info {
            width: 100%;
            margin: 8px 0;
            border: 1px solid #ccc;
        }

        .doc-info td {
            padding: 4px 8px;
            font-size: 8.5px;
            border-bottom: 1px solid #eee;
        }

        .doc-info .label {
            font-weight: bold;
            color: #555;
            width: 25%;
            background: #f8f8f8;
        }

        .doc-info .value {
            width: 25%;
        }

        .data-table {
            width: 100%;
            border: 1px solid #888;
            margin-top: 5px;
        }

        .data-table th {
            background: #2d2040;
            color: #ffffff;
            padding: 5px 4px;
            font-size: 7px;
            font-weight: bold;
            text-align: center;
            border-bottom: 2px solid #555;
            border-right: 1px solid #444;
        }

        .data-table th:last-child {
            border-right: none;
        }

        .col-header-sub {
            font-size: 6.5px;
            font-weight: normal;
            color: #ccc;
            display: block;
        }

        .data-table td {
            padding: 3px 4px;
            font-size: 7.5px;
            color: #333;
            border-right: 1px solid #eee;
            border-bottom: 1px solid #ddd;
        }

        .data-table td:last-child {
            border-right: none;
        }

        .cell-center {
            text-align: center;
        }

        .cell-number {
            text-align: right;
        }

        .total-row {
            background: #2d2040;
        }

        .total-row td {
            padding: 5px 4px;
            font-weight: bold;
            font-size: 8px;
            color: #ffffff;
            border-right: 1px solid #444;
        }

        .total-row td:last-child {
            border-right: none;
        }

        .entry-row:nth-child(even) {
            background: #fafafa;
        }

        .positive {
            color: #c0392b;
        }

        .negative {
            color: #27ae60;
        }

        .status-badge {
            display: inline;
            padding: 2px 6px;
            font-size: 7px;
            font-weight: bold;
            color: #ffffff;
            background: #888;
        }

        .status-draft {
            background: #e67e22;
        }

        .status-approved {
            background: #27ae60;
        }

        .status-voided {
            background: #c0392b;
        }

        .signature-section {
            margin-top: 30px;
            width: 100%;
        }

        .signature-label {
            font-size: 9px;
            color: #666;
            border-top: 1px solid #999;
            padding-top: 3px;
            width: 180px;
            text-align: center;
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
    {{-- Company Header --}}
    <table class="report-header">
        <tr>
            <td style="width: 70%;">
                <p class="company-detail" style="font-size: 8px; color: #888;">Трговец:</p>
                <p class="company-name">{{ $company->name ?? '' }}</p>
                <p class="company-detail">
                    @if($company->address)
                        Адреса: {{ $company->address->address_street_1 ?? '' }}
                        @if($company->address->city) , {{ $company->address->city }} @endif
                    @endif
                </p>
                <p class="company-detail">
                    @if($company->vat_number) ЕДБ: {{ $company->vat_number }} @endif
                </p>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: top;">
                <p class="company-detail" style="font-weight: bold;">НИВЕЛАЦИЈА</p>
                <p class="company-detail">Правилник Сл. весник 51/04; 89/04</p>
                <p style="margin-top: 4px;">
                    <span class="status-badge {{ $nivelacija->isDraft() ? 'status-draft' : ($nivelacija->isApproved() ? 'status-approved' : 'status-voided') }}">
                        {{ $nivelacija->status_label }}
                    </span>
                </p>
            </td>
        </tr>
    </table>

    <p class="heading-text">НИВЕЛАЦИЈА</p>
    <p class="sub-heading">Промена на малопродажна цена на залиха</p>

    {{-- Document Info --}}
    <table class="doc-info">
        <tr>
            <td class="label">Нивелација бр.:</td>
            <td class="value"><strong>{{ $nivelacija->document_number ?? '' }}</strong></td>
            <td class="label">Датум:</td>
            <td class="value">{{ $nivelacija->document_date?->format('d.m.Y') ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Тип:</td>
            <td class="value">{{ $nivelacija->type_label }}</td>
            <td class="label">Магацин:</td>
            <td class="value">{{ $nivelacija->warehouse?->name ?? 'Сите магацини' }}</td>
        </tr>
        <tr>
            <td class="label">Причина:</td>
            <td class="value" colspan="3">{{ $nivelacija->reason ?? '—' }}</td>
        </tr>
        @if($nivelacija->sourceBill)
        <tr>
            <td class="label">Извор (фактура):</td>
            <td class="value" colspan="3">{{ $nivelacija->sourceBill->bill_number ?? '' }}</td>
        </tr>
        @endif
        @if($nivelacija->isApproved())
        <tr>
            <td class="label">Одобрена од:</td>
            <td class="value">{{ $nivelacija->approver?->name ?? '' }}</td>
            <td class="label">Одобрена на:</td>
            <td class="value">{{ $nivelacija->approved_at?->format('d.m.Y H:i') ?? '' }}</td>
        </tr>
        @endif
    </table>

    {{-- Items Table --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 4%;">
                    Р.бр.
                    <span class="col-header-sub">1</span>
                </th>
                <th style="width: 24%;">
                    Назив на артиклот
                    <span class="col-header-sub">2</span>
                </th>
                <th style="width: 7%;">
                    Ед. мера
                    <span class="col-header-sub">3</span>
                </th>
                <th style="width: 10%;">
                    Количина<br>на залиха
                    <span class="col-header-sub">4</span>
                </th>
                <th style="width: 13%;">
                    Стара мало-<br>продажна цена
                    <span class="col-header-sub">5</span>
                </th>
                <th style="width: 13%;">
                    Нова мало-<br>продажна цена
                    <span class="col-header-sub">6</span>
                </th>
                <th style="width: 13%;">
                    Разлика<br>по единица
                    <span class="col-header-sub">7 (6-5)</span>
                </th>
                <th style="width: 16%;">
                    Вкупна<br>разлика
                    <span class="col-header-sub">8 (7×4)</span>
                </th>
            </tr>
        </thead>
        <tbody>
            @php $grandTotal = 0; @endphp
            @foreach($nivelacija->items as $i => $item)
            @php
                $priceDiff = ($item->new_retail_price ?? 0) - ($item->old_retail_price ?? 0);
                $totalDiff = $item->total_difference ?? (int) round($priceDiff * ($item->quantity_on_hand ?? 0));
                $grandTotal += $totalDiff;
            @endphp
            <tr class="entry-row">
                <td class="cell-center">{{ $i + 1 }}</td>
                <td>{{ $item->item?->name ?? '' }}</td>
                <td class="cell-center">{{ $item->item?->unit?->name ?? 'ком.' }}</td>
                <td class="cell-number">{{ number_format($item->quantity_on_hand ?? 0, ($item->quantity_on_hand == floor($item->quantity_on_hand)) ? 0 : 2, ',', '.') }}</td>
                <td class="cell-number">{!! format_money_pdf($item->old_retail_price ?? 0, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($item->new_retail_price ?? 0, $currency) !!}</td>
                <td class="cell-number {{ $priceDiff > 0 ? 'positive' : ($priceDiff < 0 ? 'negative' : '') }}">
                    {{ $priceDiff > 0 ? '+' : '' }}{!! format_money_pdf($priceDiff, $currency) !!}
                </td>
                <td class="cell-number {{ $totalDiff > 0 ? 'positive' : ($totalDiff < 0 ? 'negative' : '') }}">
                    {{ $totalDiff > 0 ? '+' : '' }}{!! format_money_pdf($totalDiff, $currency) !!}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="7" style="text-align: right;">ВКУПНА РАЗЛИКА:</td>
                <td class="cell-number">
                    {{ $grandTotal > 0 ? '+' : '' }}{!! format_money_pdf($grandTotal, $currency) !!}
                </td>
            </tr>
        </tfoot>
    </table>

    <p class="form-ref">Нивелација — Промена на малопродажна цена / Правилник за евиденција (Сл. весник 51/04; 89/04)</p>

    {{-- Signatures --}}
    <table class="signature-section">
        <tr>
            <td style="width: 33%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Изготвил</p>
            </td>
            <td style="width: 33%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Печат</p>
            </td>
            <td style="width: 33%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Одобрил</p>
            </td>
        </tr>
    </table>
</body>

</html>
