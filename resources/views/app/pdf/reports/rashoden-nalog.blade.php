<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Расходен налог</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
            color: #333;
            margin: 15px;
            padding: 0;
        }

        .rashoden-form {
            border: 2px solid #1a1a1a;
            padding: 0;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .rashoden-header {
            background-color: #2d3748;
            color: #ffffff;
            padding: 10px 15px;
        }

        .rashoden-header-table {
            width: 100%;
        }

        .rashoden-header-table td {
            vertical-align: middle;
        }

        .company-logo {
            width: 50px;
            height: 50px;
        }

        .company-info {
            font-size: 8px;
            color: #cbd5e0;
        }

        .company-name {
            font-size: 12px;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 2px;
        }

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

        .rashoden-body {
            padding: 12px 15px;
        }

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

        .recipient-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .recipient-table td {
            vertical-align: top;
            padding: 0;
        }

        .accounts-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .accounts-table td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        .account-box {
            border: 1px solid #a0aec0;
            padding: 8px 10px;
        }

        .account-box.left {
            border-right: none;
        }

        .amount-section {
            border: 2px solid #5851D8;
            padding: 12px;
            margin-bottom: 8px;
            background-color: #f7f7ff;
        }

        .amount-number {
            font-size: 22px;
            font-weight: bold;
            color: #1a202c;
            text-align: right;
        }

        .amount-currency {
            font-size: 10px;
            color: #718096;
            text-align: right;
        }

        .amount-words {
            font-size: 9px;
            color: #4a5568;
            font-style: italic;
            margin-top: 4px;
            border-top: 1px solid #e2e8f0;
            padding-top: 4px;
        }

        .signature-section {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
        }

        .signature-section td {
            width: 33%;
            text-align: center;
            vertical-align: bottom;
            padding-top: 35px;
        }

        .signature-line {
            border-top: 1px solid #a0aec0;
            margin: 0 15px;
            padding-top: 4px;
            font-size: 7px;
            color: #718096;
            text-transform: uppercase;
        }

        .footer-text {
            text-align: center;
            font-size: 7px;
            color: #a0aec0;
            margin-top: 15px;
        }

        .page-break {
            page-break-before: always;
        }

        .detail-row {
            width: 100%;
            border-collapse: collapse;
        }

        .detail-row td {
            padding: 5px 0;
            vertical-align: top;
        }

        .detail-label {
            font-size: 7px;
            color: #718096;
            text-transform: uppercase;
            font-weight: bold;
            letter-spacing: 0.5px;
            width: 130px;
        }
    </style>
</head>

<body>
    @foreach ($vouchers as $index => $voucher)
        @if ($index > 0)
            <div class="page-break"></div>
        @endif

        <div class="rashoden-form">
            {{-- Company Header --}}
            <div class="rashoden-header">
                <table class="rashoden-header-table">
                    <tr>
                        @if (!empty($voucher['company_logo']))
                            <td style="width: 60px;">
                                <img src="{{ $voucher['company_logo'] }}" class="company-logo" />
                            </td>
                        @endif
                        <td>
                            <p class="company-name">{{ $voucher['company_name'] }}</p>
                            <p class="company-info">
                                {{ $voucher['company_address'] ?? '' }}
                                @if (!empty($voucher['company_edb']))
                                    | ЕДБ: {{ $voucher['company_edb'] }}
                                @endif
                                @if (!empty($voucher['company_embs']))
                                    | ЕМБС: {{ $voucher['company_embs'] }}
                                @endif
                            </p>
                        </td>
                    </tr>
                </table>
            </div>

            {{-- Title --}}
            <div class="title-section">
                <h1>РАСХОДЕН НАЛОГ бр. {{ $voucher['number'] }}</h1>
                <div class="subtitle">Датум: {{ $voucher['date'] }}</div>
            </div>

            <div class="rashoden-body">
                {{-- Recipient Info --}}
                <div class="section-box">
                    <table class="recipient-table">
                        <tr>
                            <td style="width: 100%;">
                                <p class="section-label">Исплатете на / Pay to</p>
                                <p class="section-value">{{ $voucher['recipient_name'] }}</p>
                                @if (!empty($voucher['recipient_address']))
                                    <p class="section-value-normal" style="margin-top: 2px;">
                                        Адреса: {{ $voucher['recipient_address'] }}
                                    </p>
                                @endif
                                <table class="detail-row" style="margin-top: 4px;">
                                    <tr>
                                        @if (!empty($voucher['recipient_edb']))
                                            <td>
                                                <span class="section-label">ЕДБ: </span>
                                                <span class="section-value-normal">{{ $voucher['recipient_edb'] }}</span>
                                            </td>
                                        @endif
                                        @if (!empty($voucher['recipient_embs']))
                                            <td>
                                                <span class="section-label">ЕМБС: </span>
                                                <span class="section-value-normal">{{ $voucher['recipient_embs'] }}</span>
                                            </td>
                                        @endif
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- Purpose & Reference Document --}}
                <div class="section-box">
                    <p class="section-label">Основ за исплата / Payment basis</p>
                    <p class="section-value-normal" style="margin-top: 2px;">
                        {{ $voucher['purpose'] ?? '—' }}
                    </p>
                </div>

                @if (!empty($voucher['reference_document']))
                    <div class="section-box">
                        <p class="section-label">Врз основа на документ / Based on document</p>
                        <p class="section-value-normal" style="margin-top: 2px;">
                            {{ $voucher['reference_document'] }}
                        </p>
                    </div>
                @endif

                {{-- Debit / Credit Account Codes --}}
                <table class="accounts-table">
                    <tr>
                        <td>
                            <div class="account-box left">
                                <p class="section-label">Конто на задолжување / Debit account</p>
                                <p class="section-value" style="margin-top: 2px;">
                                    {{ $voucher['debit_account'] ?? '—' }}
                                </p>
                            </div>
                        </td>
                        <td>
                            <div class="account-box">
                                <p class="section-label">Конто на одобрување / Credit account</p>
                                <p class="section-value" style="margin-top: 2px;">
                                    {{ $voucher['credit_account'] ?? '—' }}
                                </p>
                            </div>
                        </td>
                    </tr>
                </table>

                {{-- Amount --}}
                <div class="amount-section">
                    <table style="width: 100%;">
                        <tr>
                            <td style="width: 40%; vertical-align: top;">
                                <p class="section-label">Износ / Amount</p>
                            </td>
                            <td style="width: 60%; text-align: right;">
                                <p class="amount-number">{{ $voucher['amount_formatted'] }}</p>
                                <p class="amount-currency">МКД</p>
                            </td>
                        </tr>
                    </table>
                    <p class="amount-words">
                        Со букви: {{ $voucher['amount_words'] }}
                    </p>
                </div>

                {{-- Signatures --}}
                <table class="signature-section">
                    <tr>
                        <td>
                            <div class="signature-line">
                                Благајник / Cashier
                            </div>
                        </td>
                        <td>
                            <div class="signature-line">
                                Примач / Recipient
                            </div>
                        </td>
                        <td>
                            <div class="signature-line">
                                Директор / Director
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <p class="footer-text">
            {{ $voucher['company_name'] }} &mdash; {{ $voucher['date'] }} &mdash; Генерирано од Facturino
        </p>
    @endforeach
</body>

</html>
{{-- CLAUDE-CHECKPOINT --}}
