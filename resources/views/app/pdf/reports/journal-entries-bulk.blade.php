<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Налози за книжење {{ $start_date }} — {{ $end_date }}</title>
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

        .page-break {
            page-break-before: always;
        }

        /* ── Form border ── */
        .nalog-form {
            border: 2px solid #000;
            padding: 0;
            page-break-inside: avoid;
        }

        /* ── Company header ── */
        .company-header {
            padding: 10px 12px;
            border-bottom: 1px solid #999;
        }

        .company-header td {
            vertical-align: top;
        }

        .company-logo-img {
            width: 45px;
            height: 45px;
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
        .nalog-title {
            text-align: center;
            padding: 8px 12px;
            border-bottom: 2px solid #000;
            background: #f5f5f5;
        }

        .nalog-title h1 {
            margin: 0;
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        /* ── Meta grid ── */
        .meta-table {
            width: 100%;
            border-bottom: 1px solid #999;
        }

        .meta-table td {
            padding: 5px 12px;
            font-size: 9px;
            border-right: 1px solid #ccc;
            vertical-align: top;
        }

        .meta-table td:last-child {
            border-right: none;
        }

        .meta-label {
            font-size: 7px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: bold;
            margin: 0 0 2px 0;
        }

        .meta-value {
            font-size: 10px;
            color: #000;
            font-weight: bold;
            margin: 0;
        }

        .meta-value-normal {
            font-size: 9px;
            color: #1a1a1a;
            margin: 0;
        }

        /* ── Description ── */
        .desc-section {
            padding: 6px 12px;
            border-bottom: 1px solid #999;
        }

        /* ── Line items table ── */
        .items-table {
            width: 100%;
        }

        .items-table th {
            background: #e8e8e8;
            padding: 5px 6px;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid #999;
            text-align: center;
            color: #333;
            font-weight: bold;
        }

        .items-table td {
            padding: 4px 6px;
            border: 1px solid #bbb;
            font-size: 9px;
        }

        .items-table .amount {
            text-align: right;
            font-family: "DejaVu Sans Mono", monospace;
        }

        .items-table .code {
            font-family: "DejaVu Sans Mono", monospace;
            text-align: center;
        }

        .items-table tfoot td {
            font-weight: bold;
            background: #e8e8e8;
            border-top: 2px solid #000;
        }

        .items-table .balance-row td {
            background: #f0f8f0;
            font-size: 8px;
            color: #1a6b1a;
            font-weight: bold;
        }

        .items-table .balance-row.unbalanced td {
            background: #fff0f0;
            color: #c00;
        }

        /* ── Amount in words ── */
        .amount-words-section {
            padding: 6px 12px;
            border-top: 1px solid #999;
            background: #fafafa;
        }

        .amount-words-text {
            font-size: 9px;
            font-style: italic;
            color: #333;
            margin: 0;
        }

        /* ── Attachments ── */
        .attachments-section {
            padding: 5px 12px;
            border-top: 1px solid #ccc;
        }

        /* ── Signatures ── */
        .signature-table {
            width: 100%;
            border-top: 1px solid #999;
        }

        .signature-table td {
            width: 25%;
            text-align: center;
            padding: 6px 8px 8px 8px;
            vertical-align: top;
            border-right: 1px solid #ccc;
        }

        .signature-table td:last-child {
            border-right: none;
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

        .sig-date {
            font-size: 7px;
            color: #999;
            margin-top: 4px;
        }

        .footer-text {
            text-align: center;
            font-size: 7px;
            color: #999;
            margin-top: 8px;
        }
    </style>
</head>

<body>
    @foreach ($prepared_entries as $index => $prepared)
        @if ($index > 0)
            <div class="page-break"></div>
        @endif

        @php
            $entry = $prepared['entry'];
            $voucher_type_code = $prepared['voucher_type_code'];
            $voucher_type_label = $prepared['voucher_type_label'];
            $amount_words = $prepared['amount_words'];
            $source_document = $prepared['source_document'];
            $bookingDate = \Carbon\Carbon::parse($entry['date']);
            $period = $bookingDate->format('m/Y');
        @endphp

        <div class="nalog-form">
            {{-- Company Header --}}
            <div class="company-header">
                <table style="width: 100%;">
                    <tr>
                        @if (!empty($company_logo))
                            <td style="width: 55px; padding-right: 8px;">
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
                    </tr>
                </table>
            </div>

            {{-- Title --}}
            <div class="nalog-title">
                <h1>Налог за книжење</h1>
            </div>

            {{-- Meta --}}
            <table class="meta-table">
                <tr>
                    <td style="width: 25%;">
                        <p class="meta-label">Број на налог</p>
                        <p class="meta-value">{{ $entry['reference'] ?? '—' }}</p>
                    </td>
                    <td style="width: 25%;">
                        <p class="meta-label">Датум на книжење</p>
                        <p class="meta-value">{{ $bookingDate->format('d.m.Y') }}</p>
                    </td>
                    <td style="width: 25%;">
                        <p class="meta-label">Период</p>
                        <p class="meta-value">{{ $period }}</p>
                    </td>
                    <td style="width: 25%;">
                        <p class="meta-label">Вид на документ</p>
                        <p class="meta-value">{{ $voucher_type_code }}</p>
                        <p class="meta-value-normal">{{ $voucher_type_label }}</p>
                    </td>
                </tr>
            </table>

            {{-- Description & Source --}}
            <div class="desc-section">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 60%; vertical-align: top; padding-right: 10px;">
                            <p class="meta-label">Опис на деловна промена</p>
                            <p class="meta-value-normal">{{ $entry['narration'] ?: '—' }}</p>
                        </td>
                        <td style="width: 40%; vertical-align: top;">
                            <p class="meta-label">Документ основ</p>
                            <p class="meta-value-normal">{{ $source_document ?: '________________________' }}</p>
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Line Items --}}
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 7%;">Р.бр.</th>
                        <th style="width: 11%;">Конто</th>
                        <th style="width: 42%;">Назив на сметка / Опис</th>
                        <th style="width: 20%;">Должи</th>
                        <th style="width: 20%;">Побарува</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entry['lines'] as $i => $line)
                    <tr>
                        <td style="text-align: center;">{{ $i + 1 }}</td>
                        <td class="code">{{ $line['account_code'] }}</td>
                        <td>
                            {{ $line['account_name'] }}
                            @if(!empty($line['counterparty_name']))
                                <br><span style="font-size: 8px; color: #666;">{{ $line['counterparty_name'] }}</span>
                            @endif
                            @if(!empty($line['description']))
                                <br><span style="font-size: 8px; color: #666;">{{ $line['description'] }}</span>
                            @endif
                        </td>
                        <td class="amount">{{ $line['debit'] > 0 ? number_format($line['debit'] / 100, 2, ',', '.') : '' }}</td>
                        <td class="amount">{{ $line['credit'] > 0 ? number_format($line['credit'] / 100, 2, ',', '.') : '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right;">ВКУПНО:</td>
                        <td class="amount">{{ number_format($entry['total_debit'] / 100, 2, ',', '.') }}</td>
                        <td class="amount">{{ number_format($entry['total_credit'] / 100, 2, ',', '.') }}</td>
                    </tr>
                    @php
                        $diff = abs($entry['total_debit'] - $entry['total_credit']);
                        $isBalanced = $diff === 0;
                    @endphp
                    <tr class="balance-row {{ !$isBalanced ? 'unbalanced' : '' }}">
                        <td colspan="3" style="text-align: right;">Разлика:</td>
                        <td class="amount" colspan="2" style="text-align: center;">
                            @if($isBalanced)
                                0,00
                            @else
                                {{ number_format($diff / 100, 2, ',', '.') }} — НЕБАЛАНСИРАНО
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>

            {{-- Amount in words --}}
            <div class="amount-words-section">
                <p class="amount-words-text">
                    <strong>Износ со букви:</strong> {{ $amount_words }}
                </p>
            </div>

            {{-- Attachments --}}
            <div class="attachments-section">
                <p class="meta-label" style="display: inline;">Број на прилози:</p>
                <span class="meta-value-normal"> ________</span>
            </div>

            {{-- Signatures --}}
            <table class="signature-table">
                <tr>
                    <td>
                        <p class="sig-label">Изготвил</p>
                        <div class="sig-line">потпис</div>
                        <p class="sig-date">Датум: ___________</p>
                    </td>
                    <td>
                        <p class="sig-label">Контролирал</p>
                        <div class="sig-line">потпис</div>
                        <p class="sig-date">Датум: ___________</p>
                    </td>
                    <td>
                        <p class="sig-label">Одобрил</p>
                        <div class="sig-line">потпис</div>
                        <p class="sig-date">Датум: ___________</p>
                    </td>
                    <td>
                        <p class="sig-label">Печат</p>
                        <div style="height: 40px;"></div>
                    </td>
                </tr>
            </table>
        </div>

        <p class="footer-text">
            {{ $company->name }} &mdash; Налог бр. {{ $entry['reference'] ?? '' }} &mdash; {{ $bookingDate->format('d.m.Y') }} &mdash; Генерирано од Facturino
        </p>
    @endforeach
</body>

</html>
{{-- CLAUDE-CHECKPOINT --}}
