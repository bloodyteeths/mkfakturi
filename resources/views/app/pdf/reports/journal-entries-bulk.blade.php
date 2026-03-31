<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Дневник на книжења {{ $start_date }} — {{ $end_date }}</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
            color: #1a1a1a;
            margin: 15px;
            padding: 0;
        }

        table {
            border-collapse: collapse;
        }

        /* ── Company header ── */
        .company-header {
            margin-bottom: 8px;
        }

        .company-header td {
            vertical-align: top;
        }

        .company-logo-img {
            width: 40px;
            height: 40px;
        }

        .company-name {
            font-size: 11px;
            font-weight: bold;
            color: #000;
            margin: 0 0 2px 0;
        }

        .company-detail {
            font-size: 8px;
            color: #444;
            margin: 1px 0;
        }

        /* ── Title ── */
        .report-title {
            text-align: center;
            margin: 6px 0;
            padding: 6px 0;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
        }

        .report-title h1 {
            margin: 0;
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .report-period {
            text-align: center;
            font-size: 9px;
            color: #333;
            margin: 0 0 8px 0;
        }

        /* ── Journal table ── */
        .journal-table {
            width: 100%;
        }

        .journal-table th {
            background: #e8e8e8;
            padding: 4px 5px;
            font-size: 7.5px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid #999;
            text-align: center;
            color: #333;
            font-weight: bold;
        }

        .journal-table td {
            padding: 3px 5px;
            border-left: 1px solid #ccc;
            border-right: 1px solid #ccc;
            font-size: 8.5px;
        }

        .journal-table .amount {
            text-align: right;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 8.5px;
        }

        .journal-table .code {
            font-family: "DejaVu Sans Mono", monospace;
            text-align: center;
        }

        /* Entry header row — separates each journal entry */
        .entry-header td {
            background: #f0f0f0;
            border-top: 1.5px solid #666;
            border-bottom: 1px solid #ccc;
            font-weight: bold;
            padding: 4px 5px;
        }

        /* Entry subtotal row */
        .entry-subtotal td {
            border-top: 1px solid #999;
            border-bottom: 1px solid #999;
            font-weight: bold;
            font-size: 8.5px;
            background: #f8f8f8;
        }

        /* Grand total */
        .grand-total td {
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            background: #e8e8e8;
            font-weight: bold;
            font-size: 9px;
            padding: 5px 5px;
        }

        /* Type badge */
        .type-badge {
            font-size: 7px;
            font-weight: bold;
            padding: 1px 3px;
            letter-spacing: 0.3px;
        }

        /* ── Footer ── */
        .footer-text {
            text-align: center;
            font-size: 7px;
            color: #999;
            margin-top: 10px;
        }

        /* ── Signatures ── */
        .signature-table {
            width: 100%;
            margin-top: 20px;
        }

        .signature-table td {
            width: 33%;
            text-align: center;
            padding: 6px 8px 8px 8px;
            vertical-align: top;
        }

        .sig-label {
            font-size: 7px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            font-weight: bold;
            margin: 0 0 20px 0;
        }

        .sig-line {
            border-top: 1px solid #333;
            padding-top: 3px;
            font-size: 8px;
            color: #999;
            margin-top: 25px;
        }

        /* Summary box */
        .summary-box {
            margin-top: 10px;
            border: 1px solid #999;
            padding: 6px 10px;
        }

        .summary-box p {
            margin: 2px 0;
            font-size: 8.5px;
        }
    </style>
</head>

<body>
    {{-- Company Header --}}
    <div class="company-header">
        <table style="width: 100%;">
            <tr>
                @if (!empty($company_logo))
                    <td style="width: 50px; padding-right: 8px;">
                        <img src="{{ $company_logo }}" class="company-logo-img" />
                    </td>
                @endif
                <td>
                    <p class="company-name">{{ $company->name }}</p>
                    @if($company->address)
                        <p class="company-detail">{{ $company->address->address_street_1 ?? '' }}@if($company->address->zip || $company->address->city), {{ $company->address->zip }} {{ $company->address->city }}@endif</p>
                    @endif
                    @if($company->vat_id)
                        <p class="company-detail">ЕДБ: {{ $company->vat_id }}@if($company->tax_id) &nbsp;&nbsp; ЕМБС: {{ $company->tax_id }}@endif</p>
                    @elseif($company->tax_id)
                        <p class="company-detail">ЕМБС: {{ $company->tax_id }}</p>
                    @endif
                </td>
                <td style="text-align: right; vertical-align: top;">
                    <p style="font-size: 8px; color: #666; margin: 0;">Печатено: {{ \Carbon\Carbon::now()->format('d.m.Y H:i') }}</p>
                </td>
            </tr>
        </table>
    </div>

    {{-- Title --}}
    <div class="report-title">
        <h1>Дневник на книжења</h1>
    </div>
    <p class="report-period">
        Период: {{ \Carbon\Carbon::parse($start_date)->format('d.m.Y') }} — {{ \Carbon\Carbon::parse($end_date)->format('d.m.Y') }}
    </p>

    {{-- Main Journal Table --}}
    @php
        $grandDebit = 0;
        $grandCredit = 0;
        $totalEntries = count($prepared_entries);
        $rowNum = 0;
    @endphp

    <table class="journal-table">
        <thead>
            <tr>
                <th style="width: 5%;">Р.бр.</th>
                <th style="width: 9%;">Датум</th>
                <th style="width: 10%;">Број</th>
                <th style="width: 9%;">Конто</th>
                <th style="width: 37%;">Назив на сметка / Опис</th>
                <th style="width: 15%;">Должи</th>
                <th style="width: 15%;">Побарува</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($prepared_entries as $index => $prepared)
                @php
                    $entry = $prepared['entry'];
                    $bookingDate = \Carbon\Carbon::parse($entry['date']);
                    $entryDebit = $entry['total_debit'] ?? 0;
                    $entryCredit = $entry['total_credit'] ?? 0;
                    $grandDebit += $entryDebit;
                    $grandCredit += $entryCredit;
                @endphp

                {{-- Entry header row --}}
                <tr class="entry-header">
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>{{ $bookingDate->format('d.m.Y') }}</td>
                    <td>{{ $entry['reference'] ?? '—' }}</td>
                    <td colspan="2" style="font-size: 8px;">
                        {{ $entry['narration'] ?: '—' }}
                    </td>
                    <td class="amount">{{ number_format($entryDebit / 100, 2, ',', '.') }}</td>
                    <td class="amount">{{ number_format($entryCredit / 100, 2, ',', '.') }}</td>
                </tr>

                {{-- Line item rows --}}
                @foreach($entry['lines'] as $i => $line)
                    @php $rowNum++; @endphp
                    <tr>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td class="code">{{ $line['account_code'] }}</td>
                        <td>
                            {{ $line['account_name'] }}
                            @if(!empty($line['counterparty_name']))
                                <span style="font-size: 7.5px; color: #666;"> — {{ $line['counterparty_name'] }}</span>
                            @endif
                        </td>
                        <td class="amount">{{ $line['debit'] > 0 ? number_format($line['debit'] / 100, 2, ',', '.') : '' }}</td>
                        <td class="amount">{{ $line['credit'] > 0 ? number_format($line['credit'] / 100, 2, ',', '.') : '' }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
        <tfoot>
            <tr class="grand-total">
                <td colspan="5" style="text-align: right;">ВКУПНО ({{ $totalEntries }} налози):</td>
                <td class="amount">{{ number_format($grandDebit / 100, 2, ',', '.') }}</td>
                <td class="amount">{{ number_format($grandCredit / 100, 2, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- Summary --}}
    <div class="summary-box">
        <p><strong>Вкупно налози:</strong> {{ $totalEntries }}</p>
        <p><strong>Вкупно ставки:</strong> {{ $rowNum }}</p>
        <p><strong>Вкупно должи:</strong> {{ number_format($grandDebit / 100, 2, ',', '.') }} МКД</p>
        <p><strong>Вкупно побарува:</strong> {{ number_format($grandCredit / 100, 2, ',', '.') }} МКД</p>
        @php $diff = abs($grandDebit - $grandCredit); @endphp
        @if($diff > 0)
            <p style="color: #c00;"><strong>Разлика:</strong> {{ number_format($diff / 100, 2, ',', '.') }} МКД — НЕБАЛАНСИРАНО</p>
        @else
            <p style="color: #1a6b1a;"><strong>Разлика:</strong> 0,00 МКД — Балансирано</p>
        @endif
    </div>

    {{-- Signatures --}}
    <table class="signature-table">
        <tr>
            <td>
                <p class="sig-label">Изготвил</p>
                <div class="sig-line">потпис</div>
            </td>
            <td>
                <p class="sig-label">Контролирал</p>
                <div class="sig-line">потпис</div>
            </td>
            <td>
                <p class="sig-label">Одобрил</p>
                <div class="sig-line">потпис</div>
            </td>
        </tr>
    </table>

    <p class="footer-text">
        {{ $company->name }} &mdash; Дневник на книжења {{ \Carbon\Carbon::parse($start_date)->format('d.m.Y') }} — {{ \Carbon\Carbon::parse($end_date)->format('d.m.Y') }} &mdash; Генерирано од Facturino
    </p>
</body>

</html>
{{-- CLAUDE-CHECKPOINT --}}
