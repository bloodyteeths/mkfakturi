<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Приемница бр. {{ $document['document_number'] ?? '' }}</title>
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
            font-size: 7.5px;
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
                <p class="company-name">{{ $company->name ?? '' }}</p>
                <p class="company-detail">
                    @if($company->address)
                        {{ $company->address->address_street_1 ?? '' }}
                        @if($company->address->city) , {{ $company->address->city }} @endif
                    @endif
                </p>
                <p class="company-detail">
                    @if($company->vat_number) ЕДБ: {{ $company->vat_number }} @endif
                </p>
            </td>
            <td style="width: 30%; text-align: right; vertical-align: top;">
                <p class="company-detail">Интерен документ</p>
                <p class="company-detail">Магацинско работење</p>
            </td>
        </tr>
    </table>

    <p class="heading-text">ПРИЕМНИЦА</p>
    <p class="sub-heading">Прием на стоки и материјали во магацин</p>

    {{-- Document Info --}}
    <table class="doc-info">
        <tr>
            <td class="label">Приемница бр.:</td>
            <td class="value"><strong>{{ $document['document_number'] ?? '' }}</strong></td>
            <td class="label">Датум:</td>
            <td class="value">{{ $document['document_date'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label">Магацин:</td>
            <td class="value">{{ $document['warehouse_name'] ?? '' }}</td>
            <td class="label">Добавувач:</td>
            <td class="value">{{ $document['supplier_name'] ?? '—' }}</td>
        </tr>
        @if(isset($document['work_order_number']))
        <tr>
            <td class="label">Работен налог бр.:</td>
            <td class="value">{{ $document['work_order_number'] }}</td>
            <td class="label">Основ:</td>
            <td class="value">{{ $document['basis'] ?? 'Прием од производство' }}</td>
        </tr>
        @endif
    </table>

    {{-- Items Table --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">
                    Р.бр.
                    <span class="col-header-sub">1</span>
                </th>
                <th style="width: 10%;">
                    Шифра
                    <span class="col-header-sub">2</span>
                </th>
                <th style="width: 30%;">
                    Назив на артикл
                    <span class="col-header-sub">3</span>
                </th>
                <th style="width: 10%;">
                    Ед. мерка
                    <span class="col-header-sub">4</span>
                </th>
                <th style="width: 12%;">
                    Количина
                    <span class="col-header-sub">5</span>
                </th>
                <th style="width: 12%;">
                    Цена
                    <span class="col-header-sub">6</span>
                </th>
                <th style="width: 12%;">
                    Вредност
                    <span class="col-header-sub">7</span>
                </th>
                <th style="width: 9%;">
                    Забелешка
                    <span class="col-header-sub">8</span>
                </th>
            </tr>
        </thead>
        <tbody>
            @php $totalValue = 0; @endphp
            @foreach(($document['items'] ?? []) as $i => $item)
            @php
                $value = $item['total_cost'] ?? (($item['quantity'] ?? 0) * ($item['unit_cost'] ?? 0));
                $totalValue += $value;
            @endphp
            <tr class="entry-row">
                <td class="cell-center">{{ $i + 1 }}</td>
                <td>{{ $item['sku'] ?? '' }}</td>
                <td>{{ $item['item_name'] ?? '' }}</td>
                <td class="cell-center">{{ $item['unit'] ?? '' }}</td>
                <td class="cell-number">{{ number_format($item['quantity'] ?? 0, 2, ',', '.') }}</td>
                <td class="cell-number">{!! format_money_pdf($item['unit_cost'] ?? 0, $currency) !!}</td>
                <td class="cell-number">{!! format_money_pdf($value, $currency) !!}</td>
                <td style="font-size: 6.5px;">{{ $item['notes'] ?? '' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="6" style="text-align: right;">ВКУПНО:</td>
                <td class="cell-number">{!! format_money_pdf($totalValue, $currency) !!}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <p class="form-ref">Приемница — Магацински документ / Правилник за евиденции (Сл. весник 51/04)</p>

    {{-- Signatures --}}
    <table class="signature-section">
        <tr>
            <td style="width: 33%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Предал</p>
            </td>
            <td style="width: 33%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Примил</p>
            </td>
            <td style="width: 33%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Одобрил</p>
            </td>
        </tr>
    </table>
</body>

</html>
