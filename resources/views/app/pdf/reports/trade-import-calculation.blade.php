<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Влезна калкулација бр. {{ $calculation->document_number ?? '' }}</title>
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
            width: 20%;
            background: #f8f8f8;
        }

        .doc-info .value {
            width: 30%;
        }

        .costs-table {
            width: 100%;
            margin: 8px 0;
            border: 1px solid #ccc;
        }

        .costs-table td {
            padding: 4px 8px;
            font-size: 8.5px;
            border-bottom: 1px solid #eee;
        }

        .costs-table .label {
            font-weight: bold;
            color: #555;
            width: 25%;
            background: #f8f8f8;
        }

        .costs-table .value {
            width: 25%;
            text-align: right;
        }

        .section-title {
            font-weight: bold;
            font-size: 9px;
            color: #1a1a1a;
            margin: 10px 0 3px 0;
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
        }

        .data-table {
            width: 100%;
            border: 1px solid #888;
            margin-top: 5px;
        }

        .data-table th {
            background: #2d2040;
            color: #ffffff;
            padding: 5px 3px;
            font-size: 6.5px;
            font-weight: bold;
            text-align: center;
            border-bottom: 2px solid #555;
            border-right: 1px solid #444;
        }

        .data-table th:last-child {
            border-right: none;
        }

        .col-header-sub {
            font-size: 6px;
            font-weight: normal;
            color: #ccc;
            display: block;
        }

        .data-table td {
            padding: 3px 3px;
            font-size: 7px;
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
            padding: 5px 3px;
            font-weight: bold;
            font-size: 7.5px;
            color: #ffffff;
            border-right: 1px solid #444;
        }

        .total-row td:last-child {
            border-right: none;
        }

        .entry-row:nth-child(even) {
            background: #fafafa;
        }

        .tariff-summary {
            width: 100%;
            border: 1px solid #888;
            margin-top: 5px;
        }

        .tariff-summary th {
            background: #3d5a80;
            color: #ffffff;
            padding: 4px 6px;
            font-size: 7px;
            font-weight: bold;
            text-align: center;
            border-right: 1px solid #4a6a90;
        }

        .tariff-summary th:last-child {
            border-right: none;
        }

        .tariff-summary td {
            padding: 3px 6px;
            font-size: 7.5px;
            border-right: 1px solid #eee;
            border-bottom: 1px solid #ddd;
        }

        .tariff-summary td:last-child {
            border-right: none;
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
                <p class="company-detail" style="font-size: 8px; color: #888;">Увозник:</p>
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
                <p class="company-detail" style="font-weight: bold;">ВЛЕЗНА КАЛКУЛАЦИЈА</p>
                <p class="company-detail">Калкулација на увозна цена</p>
                <p style="margin-top: 4px;">
                    @php
                        $statusClass = match($calculation->status) {
                            'draft' => 'status-draft',
                            'approved' => 'status-approved',
                            'voided' => 'status-voided',
                            default => '',
                        };
                        $statusText = match($calculation->status) {
                            'draft' => 'НАЦРТ',
                            'approved' => 'ОДОБРЕНА',
                            'voided' => 'СТОРНИРАНА',
                            default => $calculation->status,
                        };
                    @endphp
                    <span class="status-badge {{ $statusClass }}">{{ $statusText }}</span>
                </p>
            </td>
        </tr>
    </table>

    <p class="heading-text">ВЛЕЗНА КАЛКУЛАЦИЈА</p>
    <p class="sub-heading">Калкулација на набавна цена за увезена стока</p>

    {{-- Document Info --}}
    <table class="doc-info">
        <tr>
            <td class="label">Документ бр.:</td>
            <td class="value"><strong>{{ $calculation->document_number ?? '' }}</strong></td>
            <td class="label">Датум:</td>
            <td class="value">{{ $calculation->document_date ? \Carbon\Carbon::parse($calculation->document_date)->format('d.m.Y') : '' }}</td>
        </tr>
        <tr>
            <td class="label">Добавувач:</td>
            <td class="value">{{ $calculation->supplier_name ?? '—' }}</td>
            <td class="label">Фактура бр.:</td>
            <td class="value">{{ $calculation->supplier_invoice_number ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Валута:</td>
            <td class="value">{{ $calculation->currency_code ?? 'EUR' }}</td>
            <td class="label">Курс:</td>
            <td class="value">{{ $calculation->exchange_rate ? number_format($calculation->exchange_rate, 4, ',', '.') : '—' }}</td>
        </tr>
        <tr>
            <td class="label">Магацин:</td>
            <td class="value">{{ $calculation->warehouse?->name ?? 'Сите магацини' }}</td>
            <td class="label">ДДВ стапка:</td>
            <td class="value">{{ number_format($calculation->vat_rate ?? 18, 0) }}%</td>
        </tr>
        @if($calculation->status === 'approved' && $calculation->approved_at)
        <tr>
            <td class="label">Одобрена од:</td>
            <td class="value">{{ $calculation->approver?->name ?? '' }}</td>
            <td class="label">Одобрена на:</td>
            <td class="value">{{ \Carbon\Carbon::parse($calculation->approved_at)->format('d.m.Y H:i') }}</td>
        </tr>
        @endif
    </table>

    {{-- Cost Summary --}}
    <p class="section-title">Преглед на трошоци</p>
    <table class="costs-table">
        <tr>
            <td class="label">Фактурна вредност ({{ $calculation->currency_code ?? 'EUR' }}):</td>
            <td class="value">{!! format_money_pdf($calculation->total_invoice_value_mkd ?? 0, $currency) !!}</td>
            <td class="label">Транспорт:</td>
            <td class="value">{!! format_money_pdf($calculation->transport_amount ?? 0, $currency) !!}</td>
        </tr>
        <tr>
            <td class="label">Шпедиција:</td>
            <td class="value">{!! format_money_pdf($calculation->forwarding_amount ?? 0, $currency) !!}</td>
            <td class="label">Други трошоци:</td>
            <td class="value">{!! format_money_pdf($calculation->other_costs_amount ?? 0, $currency) !!}</td>
        </tr>
        <tr>
            <td class="label">Царина вкупно:</td>
            <td class="value">{!! format_money_pdf($calculation->customs_duty_total ?? 0, $currency) !!}</td>
            <td class="label">Увозен ДДВ:</td>
            <td class="value">{!! format_money_pdf($calculation->import_vat_total ?? 0, $currency) !!}</td>
        </tr>
        <tr>
            <td class="label" style="font-size: 9px;">Вкупна набавна цена:</td>
            <td class="value" style="font-size: 9px; font-weight: bold;">{!! format_money_pdf($calculation->total_landed_cost ?? 0, $currency) !!}</td>
            <td class="label"></td>
            <td class="value"></td>
        </tr>
    </table>

    {{-- Tariff Heading Summary --}}
    @php
        $tariffGroups = $calculation->items->groupBy('tariff_heading');
    @endphp
    @if($tariffGroups->count() > 1)
    <p class="section-title">Преглед по тарифни ознаки</p>
    <table class="tariff-summary">
        <thead>
            <tr>
                <th style="width: 15%;">Тарифна ознака</th>
                <th style="width: 10%;">Ставки</th>
                <th style="width: 15%;">Царинска основа</th>
                <th style="width: 10%;">Стапка %</th>
                <th style="width: 15%;">Царина</th>
                <th style="width: 15%;">Набавна цена</th>
            </tr>
        </thead>
        <tbody>
            @foreach($tariffGroups as $heading => $groupItems)
            <tr class="entry-row">
                <td class="cell-center" style="font-weight: bold;">{{ $heading ?: '—' }}</td>
                <td class="cell-center">{{ $groupItems->count() }}</td>
                <td class="cell-number">{!! format_money_pdf($groupItems->sum('customs_base'), $currency) !!}</td>
                <td class="cell-center">{{ number_format($groupItems->first()->customs_duty_rate ?? 0, 1) }}%</td>
                <td class="cell-number">{!! format_money_pdf($groupItems->sum('customs_duty_amount'), $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($groupItems->sum('landed_cost_before_vat'), $currency) !!}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    {{-- Items Detail Table --}}
    <p class="section-title">Детален преглед на артикли</p>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 3%;">
                    Р.б.
                    <span class="col-header-sub">1</span>
                </th>
                <th style="width: 16%;">
                    Назив
                    <span class="col-header-sub">2</span>
                </th>
                <th style="width: 7%;">
                    Тарифа
                    <span class="col-header-sub">3</span>
                </th>
                <th style="width: 6%;">
                    Кол.
                    <span class="col-header-sub">4</span>
                </th>
                <th style="width: 8%;">
                    Цена FCY
                    <span class="col-header-sub">5</span>
                </th>
                <th style="width: 9%;">
                    Факт. МКД
                    <span class="col-header-sub">6</span>
                </th>
                <th style="width: 8%;">
                    Транспорт
                    <span class="col-header-sub">7</span>
                </th>
                <th style="width: 8%;">
                    Цар. осн.
                    <span class="col-header-sub">8</span>
                </th>
                <th style="width: 5%;">
                    Ц.%
                    <span class="col-header-sub">9</span>
                </th>
                <th style="width: 8%;">
                    Царина
                    <span class="col-header-sub">10</span>
                </th>
                <th style="width: 10%;">
                    Набавна
                    <span class="col-header-sub">11</span>
                </th>
                <th style="width: 10%;">
                    Ед. цена
                    <span class="col-header-sub">12</span>
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach($calculation->items as $i => $item)
            <tr class="entry-row">
                <td class="cell-center">{{ $i + 1 }}</td>
                <td>{{ $item->description ?? ($item->item?->name ?? '') }}</td>
                <td class="cell-center">{{ $item->tariff_heading ?? '—' }}</td>
                <td class="cell-number">{{ number_format($item->quantity ?? 0, ($item->quantity == floor($item->quantity)) ? 0 : 2, ',', '.') }}</td>
                <td class="cell-number">{!! format_money_pdf($item->unit_price_fcy ?? 0, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($item->invoice_value_mkd ?? 0, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($item->transport_allocated ?? 0, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($item->customs_base ?? 0, $currency) !!}</td>
                <td class="cell-center">{{ number_format($item->customs_duty_rate ?? 0, 1) }}%</td>
                <td class="cell-number">{!! format_money_pdf($item->customs_duty_amount ?? 0, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($item->landed_cost_before_vat ?? 0, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($item->unit_landed_cost ?? 0, $currency) !!}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" style="text-align: right;">ВКУПНО:</td>
                <td class="cell-number">{!! format_money_pdf($calculation->total_invoice_value_mkd ?? 0, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($calculation->transport_amount ?? 0, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf(($calculation->total_invoice_value_mkd ?? 0) + ($calculation->transport_amount ?? 0), $currency) !!}</td>
                <td></td>
                <td class="cell-number">{!! format_money_pdf($calculation->customs_duty_total ?? 0, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($calculation->total_landed_cost ?? 0, $currency) !!}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    @if($calculation->notes)
    <p style="margin-top: 8px; font-size: 8px; color: #555;"><strong>Забелешки:</strong> {{ $calculation->notes }}</p>
    @endif

    <p class="form-ref">Влезна калкулација — Калкулација на набавна цена за увезена стока</p>

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
