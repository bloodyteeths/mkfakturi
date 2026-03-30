<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Налог за книжење {{ $entry['reference'] ?? '' }}</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
            color: #333;
            margin: 15px;
            padding: 0;
        }

        table {
            border-collapse: collapse;
        }

        /* ── Form container ── */
        .nalog-form {
            border: 2px solid #1a1a1a;
            padding: 0;
            page-break-inside: avoid;
        }

        /* ── Company header (dark) ── */
        .nalog-company-header {
            background-color: #2d3748;
            color: #ffffff;
            padding: 10px 15px;
        }

        .nalog-company-header td {
            vertical-align: middle;
        }

        .company-logo-img {
            width: 50px;
            height: 50px;
        }

        .company-name {
            font-size: 12px;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 2px;
        }

        .company-info {
            font-size: 8px;
            color: #cbd5e0;
        }

        /* ── Title section (indigo) ── */
        .title-section {
            background-color: #5851D8;
            color: #ffffff;
            padding: 10px 15px;
            text-align: center;
        }

        .title-section h1 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .title-section .subtitle {
            font-size: 10px;
            color: #e2d8f0;
            margin-top: 3px;
        }

        /* ── Body ── */
        .nalog-body {
            padding: 12px 15px;
        }

        /* ── Section boxes ── */
        .section-box {
            border: 1px solid #a0aec0;
            padding: 8px 10px;
            margin-bottom: 8px;
        }

        .section-label {
            font-size: 7px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
            font-weight: bold;
        }

        .section-value {
            font-size: 10px;
            color: #1a202c;
            font-weight: bold;
        }

        .section-value-normal {
            font-size: 10px;
            color: #1a202c;
        }

        /* ── Meta info grid (2 cols) ── */
        .meta-grid {
            width: 100%;
            margin-bottom: 8px;
        }

        .meta-grid td {
            width: 50%;
            vertical-align: top;
            padding: 0 4px 0 0;
        }

        .meta-grid .right {
            padding: 0 0 0 4px;
        }

        /* ── Line items table ── */
        .items-table {
            width: 100%;
            margin-top: 8px;
        }

        .items-table th {
            background: #edf2f7;
            padding: 5px 6px;
            font-size: 7px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #a0aec0;
            text-align: left;
            color: #4a5568;
        }

        .items-table td {
            padding: 4px 6px;
            border: 1px solid #cbd5e0;
            font-size: 9px;
        }

        .items-table .amount {
            text-align: right;
            font-family: "DejaVu Sans Mono", monospace;
        }

        .items-table .code {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 9px;
        }

        .items-table tfoot td {
            font-weight: bold;
            background: #edf2f7;
            border-top: 2px solid #4a5568;
        }

        .items-table .balance-row td {
            background: #f0fff4;
            font-size: 8px;
            color: #276749;
            font-weight: bold;
            border-top: 1px dashed #68d391;
        }

        .items-table .balance-row.unbalanced td {
            background: #fff5f5;
            color: #c53030;
            border-top: 1px dashed #fc8181;
        }

        /* ── Amount in words ── */
        .amount-section {
            border: 1px solid #a0aec0;
            padding: 8px 10px;
            margin-top: 8px;
            background: #f7fafc;
        }

        .amount-number {
            font-size: 14px;
            font-weight: bold;
            color: #1a202c;
            margin: 0;
        }

        .amount-currency {
            font-size: 10px;
            color: #718096;
            margin: 0;
        }

        .amount-words {
            font-size: 9px;
            color: #4a5568;
            font-style: italic;
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px dotted #cbd5e0;
        }

        /* ── Signatures ── */
        .signature-section {
            width: 100%;
            margin-top: 30px;
        }

        .signature-section td {
            width: 33%;
            text-align: center;
            padding: 0 10px;
            vertical-align: bottom;
        }

        .signature-line {
            border-top: 1px solid #4a5568;
            padding-top: 5px;
            font-size: 8px;
            color: #718096;
            margin-top: 30px;
        }

        /* ── Footer ── */
        .footer-text {
            text-align: center;
            font-size: 7px;
            color: #a0aec0;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <div class="nalog-form">
        {{-- Company Header --}}
        <div class="nalog-company-header">
            <table style="width: 100%;">
                <tr>
                    @if (!empty($company_logo))
                        <td style="width: 60px;">
                            <img src="{{ $company_logo }}" class="company-logo-img" />
                        </td>
                    @endif
                    <td>
                        <p class="company-name">{{ $company->name }}</p>
                        <p class="company-info">
                            @if($company->address)
                                {{ $company->address->address_street_1 ?? '' }}
                                @if($company->address->zip || $company->address->city)
                                    , {{ $company->address->zip }} {{ $company->address->city }}
                                @endif
                            @endif
                            @if($company->vat_id)
                                | ЕДБ: {{ $company->vat_id }}
                            @endif
                            @if($company->tax_id)
                                | ЕМБС: {{ $company->tax_id }}
                            @endif
                        </p>
                    </td>
                    <td style="width: 120px; text-align: right; vertical-align: top;">
                        <p style="font-size: 8px; color: #a0aec0;">{{ \Carbon\Carbon::parse($entry['date'])->format('d.m.Y') }}</p>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Title Section --}}
        <div class="title-section">
            <h1>НАЛОГ ЗА КНИЖЕЊЕ бр. {{ $entry['reference'] ?? '—' }}</h1>
            <div class="subtitle">{{ $voucher_type_label }} — {{ \Carbon\Carbon::parse($entry['date'])->format('d.m.Y') }}</div>
        </div>

        <div class="nalog-body">
            {{-- Meta info: type, date, narration, source doc --}}
            <table class="meta-grid">
                <tr>
                    <td>
                        <div class="section-box">
                            <p class="section-label">Тип на налог / Voucher type</p>
                            <p class="section-value">{{ $voucher_type_label }}</p>
                            <p style="font-size: 8px; color: #718096; margin-top: 2px;">{{ $voucher_type_code }}</p>
                        </div>
                    </td>
                    <td class="right">
                        <div class="section-box">
                            <p class="section-label">Датум на книжење / Posting date</p>
                            <p class="section-value">{{ \Carbon\Carbon::parse($entry['date'])->format('d.m.Y') }}</p>
                        </div>
                    </td>
                </tr>
            </table>

            {{-- Narration / Description --}}
            <div class="section-box">
                <p class="section-label">Опис / Narration</p>
                <p class="section-value-normal" style="margin-top: 2px;">{{ $entry['narration'] ?: '—' }}</p>
            </div>

            {{-- Source document reference (if available) --}}
            @if(!empty($source_document))
                <div class="section-box">
                    <p class="section-label">Врз основа на документ / Source document</p>
                    <p class="section-value-normal" style="margin-top: 2px;">{{ $source_document }}</p>
                </div>
            @endif

            {{-- Line Items Table --}}
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 6%;">Р.бр.</th>
                        <th style="width: 10%;">Конто</th>
                        <th style="width: 22%;">Назив на сметка</th>
                        <th style="width: 15%;">Партнер</th>
                        <th style="width: 17%;">Опис</th>
                        <th style="width: 15%;" class="amount">Должи (МКД)</th>
                        <th style="width: 15%;" class="amount">Побарува (МКД)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($entry['lines'] as $i => $line)
                    <tr>
                        <td style="text-align: center;">{{ $i + 1 }}</td>
                        <td class="code">{{ $line['account_code'] }}</td>
                        <td>{{ $line['account_name'] }}</td>
                        <td>{{ $line['counterparty_name'] ?? '' }}</td>
                        <td>{{ $line['description'] }}</td>
                        <td class="amount">{{ $line['debit'] > 0 ? number_format($line['debit'] / 100, 2, ',', '.') : '' }}</td>
                        <td class="amount">{{ $line['credit'] > 0 ? number_format($line['credit'] / 100, 2, ',', '.') : '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" style="text-align: right;">Вкупно:</td>
                        <td class="amount">{{ number_format($entry['total_debit'] / 100, 2, ',', '.') }}</td>
                        <td class="amount">{{ number_format($entry['total_credit'] / 100, 2, ',', '.') }}</td>
                    </tr>
                    {{-- Balance verification row --}}
                    @php
                        $diff = abs($entry['total_debit'] - $entry['total_credit']);
                        $isBalanced = $diff === 0;
                    @endphp
                    <tr class="balance-row {{ !$isBalanced ? 'unbalanced' : '' }}">
                        <td colspan="5" style="text-align: right;">
                            Салдо (Должи - Побарува):
                        </td>
                        <td class="amount" colspan="2" style="text-align: center;">
                            @if($isBalanced)
                                0,00 — Балансирано
                            @else
                                {{ number_format($diff / 100, 2, ',', '.') }} — НЕБАЛАНСИРАНО
                            @endif
                        </td>
                    </tr>
                </tfoot>
            </table>

            {{-- Amount in words --}}
            <div class="amount-section">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 40%; vertical-align: top;">
                            <p class="section-label">Вкупен износ / Total amount</p>
                        </td>
                        <td style="width: 60%; text-align: right;">
                            <p class="amount-number">{{ number_format($entry['total_debit'] / 100, 2, ',', '.') }}</p>
                            <p class="amount-currency">МКД</p>
                        </td>
                    </tr>
                </table>
                <p class="amount-words">
                    Со букви: {{ $amount_words }}
                </p>
            </div>

            {{-- Signatures --}}
            <table class="signature-section">
                <tr>
                    <td>
                        <div class="signature-line">
                            Составил / Prepared by
                        </div>
                    </td>
                    <td>
                        <div class="signature-line">
                            Контролирал / Reviewed by
                        </div>
                    </td>
                    <td>
                        <div class="signature-line">
                            Одобрил / Approved by
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <p class="footer-text">
        {{ $company->name }} &mdash; Налог бр. {{ $entry['reference'] ?? '' }} &mdash; {{ \Carbon\Carbon::parse($entry['date'])->format('d.m.Y') }} &mdash; Генерирано од Facturino
    </p>
</body>

</html>
{{-- CLAUDE-CHECKPOINT --}}
