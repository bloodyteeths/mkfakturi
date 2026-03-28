<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Материјална евиденција во трговија на големо (Образец МЕТГ)</title>
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
            font-size: 13px;
            color: #1a1a1a;
            text-align: center;
            margin: 8px 0 2px 0;
        }

        .product-info {
            font-size: 9px;
            color: #333;
            margin: 4px 0;
            border: 1px solid #ccc;
            padding: 6px 10px;
            background: #fafafa;
        }

        .metg-table {
            width: 100%;
            border: 1px solid #888;
            margin-top: 5px;
        }

        .metg-table th {
            background: #2d2040;
            color: #ffffff;
            padding: 5px 4px;
            font-size: 7.5px;
            font-weight: bold;
            text-align: center;
            border-bottom: 2px solid #555;
            border-right: 1px solid #444;
        }

        .metg-table th:last-child {
            border-right: none;
        }

        .col-header-sub {
            font-size: 6.5px;
            font-weight: normal;
            color: #ccc;
            display: block;
        }

        .group-header {
            background: #3d3050;
            color: #ffffff;
            padding: 3px 4px;
            font-size: 7px;
            font-weight: bold;
            text-align: center;
            border-bottom: 1px solid #555;
            border-right: 1px solid #444;
        }

        .entry-row {
            border-bottom: 1px solid #ddd;
        }

        .entry-row:nth-child(even) {
            background: #fafafa;
        }

        .entry-row td {
            padding: 3px 4px;
            font-size: 7.5px;
            color: #333;
            border-right: 1px solid #eee;
        }

        .entry-row td:last-child {
            border-right: none;
        }

        .cell-center {
            text-align: center;
        }

        .cell-number {
            text-align: right;
            font-family: "DejaVu Sans";
        }

        .total-row {
            background: #2d2040;
            border-top: 2px solid #333;
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

        .carry-row {
            background: #f0f0f0;
            border-top: 1px solid #999;
        }

        .carry-row td {
            padding: 3px 4px;
            font-size: 7px;
            font-weight: bold;
            color: #555;
            border-right: 1px solid #ddd;
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
            width: 200px;
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
            <td style="width: 50%;">
                <p class="company-detail" style="font-size: 8px; color: #888;">Трговец:</p>
                <p class="company-name">{{ $company->name ?? '' }}</p>
                <p class="company-detail">
                    @if($company->address)
                        Адреса: {{ $company->address->address_street_1 ?? '' }}
                    @endif
                </p>
                <p class="company-detail">
                    Место: {{ $company->address->city ?? '' }}
                </p>
                <p class="company-detail">
                    @if($company->vat_number) ЕДБ: {{ $company->vat_number }} @endif
                </p>
            </td>
            <td style="width: 50%; text-align: right; vertical-align: top;">
                <p class="company-detail" style="font-weight: bold;">Образец "МЕТГ"</p>
                <p class="company-detail">Правилник Сл. весник 51/04; 89/04</p>
                <p class="company-detail" style="margin-top: 4px;">
                    Година: <strong>{{ $year ?? date('Y') }}</strong>
                </p>
            </td>
        </tr>
    </table>

    <p class="heading-text">МАТЕРИЈАЛНА ЕВИДЕНЦИЈА ВО ТРГОВИЈАТА НА ГОЛЕМО</p>

    {{-- Product info --}}
    <div class="product-info">
        <strong>Назив на производот:</strong> {{ $product_name ?? 'Сите артикли' }}
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Магацин:</strong> {{ $warehouse_name ?? '-' }}
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Единица мера:</strong> {{ $unit_name ?? 'ком.' }}
    </div>

    <table class="metg-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 4%;">
                    Ред.<br>бр.
                    <span class="col-header-sub">1</span>
                </th>
                <th rowspan="2" style="width: 8%;">
                    Датум на<br>книжење<br>(ден и месец)
                    <span class="col-header-sub">2</span>
                </th>
                <th class="group-header" colspan="3" style="width: 38%;">
                    Книговодствен документ
                </th>
                <th rowspan="2" style="width: 10%;">
                    Влез
                    <span class="col-header-sub">6</span>
                </th>
                <th rowspan="2" style="width: 10%;">
                    Излез
                    <span class="col-header-sub">7</span>
                </th>
                <th rowspan="2" style="width: 10%;">
                    Состојба
                    <span class="col-header-sub">8</span>
                </th>
            </tr>
            <tr>
                <th style="width: 8%;">
                    Број
                    <span class="col-header-sub">3</span>
                </th>
                <th style="width: 8%;">
                    Датум
                    <span class="col-header-sub">4</span>
                </th>
                <th style="width: 22%;">
                    Назив (добавувач/купувач)
                    <span class="col-header-sub">5</span>
                </th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalVlez = 0;
                $totalIzlez = 0;
            @endphp

            @foreach($entries as $entry)
            @php
                $vlez = $entry['vlez'] ?? 0;
                $izlez = $entry['izlez'] ?? 0;
                $totalVlez += $vlez;
                $totalIzlez += $izlez;
            @endphp
            <tr class="entry-row">
                <td class="cell-center">{{ $entry['seq'] ?? '' }}</td>
                <td class="cell-center">{{ $entry['date'] ?? '' }}</td>
                <td class="cell-center">{{ $entry['doc_number'] ?? '' }}</td>
                <td class="cell-center">{{ $entry['doc_date'] ?? '' }}</td>
                <td>{{ $entry['doc_name'] ?? '' }}
                    @if($entry['party'] ?? '')
                        <br><span style="font-size: 6.5px; color: #888;">{{ $entry['party'] }}</span>
                    @endif
                </td>
                <td class="cell-number">
                    @if($vlez > 0) {{ number_format($vlez, $vlez == floor($vlez) ? 0 : 2) }} @endif
                </td>
                <td class="cell-number">
                    @if($izlez > 0) {{ number_format($izlez, $izlez == floor($izlez) ? 0 : 2) }} @endif
                </td>
                <td class="cell-number" style="font-weight: bold;">
                    {{ number_format($entry['sostojba'] ?? 0, ($entry['sostojba'] ?? 0) == floor($entry['sostojba'] ?? 0) ? 0 : 2) }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="carry-row">
                <td colspan="5" style="text-align: right; font-style: italic;">Пренос →</td>
                <td class="cell-number">{{ number_format($totalVlez, $totalVlez == floor($totalVlez) ? 0 : 2) }}</td>
                <td class="cell-number">{{ number_format($totalIzlez, $totalIzlez == floor($totalIzlez) ? 0 : 2) }}</td>
                <td class="cell-number" style="font-weight: bold;">{{ number_format($totalVlez - $totalIzlez, ($totalVlez - $totalIzlez) == floor($totalVlez - $totalIzlez) ? 0 : 2) }}</td>
            </tr>
            <tr class="total-row">
                <td colspan="5" style="text-align: left;">ВКУПНО</td>
                <td class="cell-number">{{ number_format($totalVlez, $totalVlez == floor($totalVlez) ? 0 : 2) }}</td>
                <td class="cell-number">{{ number_format($totalIzlez, $totalIzlez == floor($totalIzlez) ? 0 : 2) }}</td>
                <td class="cell-number">{{ number_format($totalVlez - $totalIzlez, ($totalVlez - $totalIzlez) == floor($totalVlez - $totalIzlez) ? 0 : 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <p class="form-ref">Образец "МЕТГ" — Материјална евиденција во трговијата на големо / Правилник за евиденција Сл. весник 51/04; 89/04</p>

    <table class="signature-section">
        <tr>
            <td style="width: 50%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Печат</p>
            </td>
            <td style="width: 50%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Потпис на овластено лице</p>
            </td>
        </tr>
    </table>
</body>

</html>
