<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Ревизорски Извештај — Фискални Апарати</title>
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

        .section-title {
            font-weight: bold;
            font-size: 10px;
            color: #2d2040;
            margin: 12px 0 4px 0;
            padding: 3px 0;
            border-bottom: 2px solid #2d2040;
        }

        .data-table {
            width: 100%;
            border: 1px solid #888;
            margin-top: 3px;
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

        .cell-left {
            text-align: left;
        }

        .highlight-red {
            color: #c53030;
            font-weight: bold;
        }

        .subtotal-row {
            background: #f0eff5;
            font-weight: bold;
        }

        .subtotal-row td {
            padding: 4px;
            font-size: 8px;
            border-top: 1px solid #999;
        }

        .footer-section {
            margin-top: 20px;
            width: 100%;
        }

        .footer-text {
            font-size: 7px;
            color: #999;
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
                <p class="company-detail">Фискален Монитор</p>
                <p class="company-detail">Ревизија на активности</p>
            </td>
        </tr>
    </table>

    <p class="heading-text">РЕВИЗОРСКИ ИЗВЕШТАЈ ЗА ФИСКАЛНИ АПАРАТИ</p>
    <p class="sub-heading">Период: {{ $from }} — {{ $to }}</p>

    {{-- Table 1: Activity by Employee --}}
    <p class="section-title">1. Активност по Вработен</p>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">Р.бр.</th>
                <th style="width: 25%;">Вработен</th>
                <th style="width: 12%;">Вкупно Настани</th>
                <th style="width: 10%;">Отвори</th>
                <th style="width: 10%;">Затвори</th>
                <th style="width: 12%;">Сметки</th>
                <th style="width: 10%;">Сторно</th>
                <th style="width: 16%;">Z-Извештај</th>
            </tr>
        </thead>
        <tbody>
            @php $totalEvents = 0; @endphp
            @foreach(($report['by_user'] ?? []) as $i => $row)
            @php $totalEvents += $row['total_events'] ?? 0; @endphp
            <tr>
                <td class="cell-center">{{ $i + 1 }}</td>
                <td class="cell-left">{{ $row['user_name'] ?? '—' }}</td>
                <td class="cell-center">{{ $row['total_events'] ?? 0 }}</td>
                <td class="cell-center">{{ $row['opens'] ?? 0 }}</td>
                <td class="cell-center">{{ $row['closes'] ?? 0 }}</td>
                <td class="cell-center">{{ $row['receipts'] ?? 0 }}</td>
                <td class="cell-center {{ ($row['voids'] ?? 0) > 3 ? 'highlight-red' : '' }}">{{ $row['voids'] ?? 0 }}</td>
                <td class="cell-center">{{ $row['z_reports'] ?? 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Table 2: Activity by Device --}}
    <p class="section-title">2. Активност по Апарат</p>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">Р.бр.</th>
                <th style="width: 25%;">Апарат</th>
                <th style="width: 12%;">Вкупно Настани</th>
                <th style="width: 12%;">Корисници</th>
                <th style="width: 10%;">Отвори</th>
                <th style="width: 10%;">Затвори</th>
                <th style="width: 12%;">Сметки</th>
                <th style="width: 14%;">Сторно</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($report['by_device'] ?? []) as $i => $row)
            <tr>
                <td class="cell-center">{{ $i + 1 }}</td>
                <td class="cell-left">{{ $row['device_name'] ?? '—' }}</td>
                <td class="cell-center">{{ $row['total_events'] ?? 0 }}</td>
                <td class="cell-center">{{ $row['unique_users'] ?? 0 }}</td>
                <td class="cell-center">{{ $row['opens'] ?? 0 }}</td>
                <td class="cell-center">{{ $row['closes'] ?? 0 }}</td>
                <td class="cell-center">{{ $row['receipts'] ?? 0 }}</td>
                <td class="cell-center {{ ($row['voids'] ?? 0) > 3 ? 'highlight-red' : '' }}">{{ $row['voids'] ?? 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Table 3: Daily Summary --}}
    <p class="section-title">3. Дневен Преглед</p>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">Р.бр.</th>
                <th style="width: 20%;">Датум</th>
                <th style="width: 15%;">Вкупно Настани</th>
                <th style="width: 15%;">Отвори</th>
                <th style="width: 15%;">Затвори</th>
                <th style="width: 15%;">Сметки</th>
                <th style="width: 15%;">Сторно</th>
            </tr>
        </thead>
        <tbody>
            @foreach(($report['by_day'] ?? []) as $i => $row)
            <tr>
                <td class="cell-center">{{ $i + 1 }}</td>
                <td class="cell-left">{{ $row['date'] ?? '—' }}</td>
                <td class="cell-center">{{ $row['total_events'] ?? 0 }}</td>
                <td class="cell-center">{{ $row['opens'] ?? 0 }}</td>
                <td class="cell-center">{{ $row['closes'] ?? 0 }}</td>
                <td class="cell-center">{{ $row['receipts'] ?? 0 }}</td>
                <td class="cell-center {{ ($row['voids'] ?? 0) > 3 ? 'highlight-red' : '' }}">{{ $row['voids'] ?? 0 }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Footer --}}
    <table class="footer-section">
        <tr>
            <td style="width: 50%;">
                <p class="footer-text">Генерирано: {{ $generatedAt }}</p>
            </td>
            <td style="width: 50%; text-align: right;">
                <p class="footer-text">Вкупно настани во период: {{ $totalEvents }}</p>
            </td>
        </tr>
    </table>

    {{-- Signatures --}}
    <table class="signature-section">
        <tr>
            <td style="width: 50%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Изготвил</p>
            </td>
            <td style="width: 50%; text-align: center; padding-top: 40px;">
                <p class="signature-label">Одобрил</p>
            </td>
        </tr>
    </table>
</body>

</html>
{{-- CLAUDE-CHECKPOINT --}}
