<!DOCTYPE html>
<html lang="mk">

<head>
    <title>ПП50 Налог за плаќање</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .sub-container {
            padding: 20px 25px;
        }

        .pp50-form {
            border: 2px solid #1a1a1a;
            padding: 0;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .pp50-header {
            background-color: #2d3748;
            color: #ffffff;
            padding: 8px 15px;
            text-align: center;
        }

        .pp50-header h1 {
            margin: 0;
            font-size: 14px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .pp50-header .pp50-subtitle {
            font-size: 8px;
            color: #cbd5e0;
            margin-top: 2px;
        }

        .pp50-body {
            padding: 12px 15px;
        }

        .parties-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .parties-row td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        .party-box {
            border: 1px solid #a0aec0;
            padding: 8px 10px;
            min-height: 80px;
        }

        .party-box.left {
            border-right: none;
        }

        .party-label {
            font-size: 7px;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            font-weight: bold;
        }

        .party-name {
            font-size: 11px;
            font-weight: bold;
            color: #1a202c;
            margin-bottom: 4px;
        }

        .party-detail {
            font-size: 9px;
            color: #4a5568;
            margin-bottom: 2px;
        }

        .party-iban {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 10px;
            color: #1a202c;
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        .revenue-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .revenue-row td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        .revenue-box {
            border: 1px solid #5851D8;
            padding: 8px 10px;
            background-color: #f7f7ff;
        }

        .revenue-box.left {
            border-right: none;
        }

        .revenue-code {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 14px;
            font-weight: bold;
            color: #5851D8;
            letter-spacing: 1px;
        }

        .amount-section {
            border: 2px solid #2d3748;
            padding: 10px;
            margin-top: 10px;
            background-color: #f7fafc;
        }

        .amount-number {
            font-size: 18px;
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

        .purpose-section {
            border: 1px solid #a0aec0;
            padding: 8px 10px;
            margin-top: 10px;
            min-height: 30px;
        }

        .reference-row {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        .reference-row td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        .ref-box {
            border: 1px solid #a0aec0;
            padding: 6px 10px;
            min-height: 24px;
        }

        .ref-box.left {
            border-right: none;
        }

        .detail-value {
            font-size: 10px;
            color: #1a202c;
        }

        .signature-section {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .signature-section td {
            width: 33%;
            text-align: center;
            vertical-align: bottom;
            padding-top: 30px;
        }

        .signature-line {
            border-top: 1px solid #a0aec0;
            margin: 0 15px;
            padding-top: 4px;
            font-size: 7px;
            color: #718096;
            text-transform: uppercase;
        }

        .page-break {
            page-break-before: always;
        }

        .footer-text {
            text-align: center;
            font-size: 7px;
            color: #a0aec0;
            margin-top: 15px;
        }

        .bill-ref {
            font-size: 8px;
            color: #718096;
            text-align: right;
            padding: 3px 15px;
            background-color: #f7fafc;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>

<body>
    @foreach ($slips as $index => $slip)
        @if ($index > 0)
            <div class="page-break"></div>
        @endif

        <div class="sub-container">
            <div class="pp50-form">
                {{-- Header --}}
                <div class="pp50-header">
                    <h1>НАЛОГ ЗА ПЛАЌАЊЕ ПП50</h1>
                    <div class="pp50-subtitle">Платен налог за буџетски корисници</div>
                </div>

                <div class="pp50-body">
                    {{-- Parties: Debtor & Creditor --}}
                    <table class="parties-row">
                        <tr>
                            <td>
                                <div class="party-box left">
                                    <p class="party-label">Налогодавач (Debtor)</p>
                                    <p class="party-name">{{ $slip['debtor_name'] }}</p>
                                    <p class="party-detail">
                                        <span class="party-label" style="display: inline;">Сметка: </span>
                                        <span class="party-iban">{{ $slip['debtor_iban'] ?: '—' }}</span>
                                    </p>
                                    <p class="party-detail">
                                        <span class="party-label" style="display: inline;">Банка: </span>
                                        {{ $slip['debtor_bank'] ?: '—' }}
                                    </p>
                                </div>
                            </td>
                            <td>
                                <div class="party-box">
                                    <p class="party-label">Примач (Creditor)</p>
                                    <p class="party-name">{{ $slip['creditor_name'] }}</p>
                                    <p class="party-detail">
                                        <span class="party-label" style="display: inline;">Сметка: </span>
                                        <span class="party-iban">{{ $slip['creditor_iban'] ?: '—' }}</span>
                                    </p>
                                    <p class="party-detail">
                                        <span class="party-label" style="display: inline;">Банка: </span>
                                        {{ $slip['creditor_bank'] ?: '—' }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </table>

                    {{-- Revenue Code & Municipality --}}
                    <table class="revenue-row">
                        <tr>
                            <td>
                                <div class="revenue-box left">
                                    <p class="party-label">Шифра на приход / Revenue Code</p>
                                    <p class="revenue-code" style="margin-top: 3px;">
                                        {{ $slip['revenue_code'] ?: '—' }}
                                    </p>
                                    @if (!empty($slip['revenue_name']))
                                        <p class="party-detail" style="margin-top: 2px;">
                                            {{ $slip['revenue_name'] }}
                                        </p>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="revenue-box">
                                    <p class="party-label">Општина / Municipality</p>
                                    <p class="revenue-code" style="margin-top: 3px;">
                                        {{ $slip['municipality_code'] ?: '—' }}
                                    </p>
                                    @if (!empty($slip['municipality_name']))
                                        <p class="party-detail" style="margin-top: 2px;">
                                            {{ $slip['municipality_name'] }}
                                        </p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    </table>

                    {{-- Amount --}}
                    <div class="amount-section">
                        <table style="width: 100%;">
                            <tr>
                                <td style="width: 40%; vertical-align: top;">
                                    <p class="party-label">Износ / Amount</p>
                                </td>
                                <td style="width: 60%; text-align: right;">
                                    <p class="amount-number">{{ $slip['amount_formatted'] }}</p>
                                    <p class="amount-currency">{{ $slip['currency_code'] }}</p>
                                </td>
                            </tr>
                        </table>
                        <p class="amount-words">
                            Со букви: {{ $slip['amount_words'] }}
                        </p>
                    </div>

                    {{-- Purpose --}}
                    <div class="purpose-section">
                        <p class="party-label">Цел на дознака / Purpose of payment</p>
                        <p class="detail-value" style="margin-top: 3px;">
                            {{ $slip['description'] }}
                        </p>
                    </div>

                    {{-- Reference & Date --}}
                    <table class="reference-row">
                        <tr>
                            <td>
                                <div class="ref-box left">
                                    <p class="party-label">Повикување на број / Reference</p>
                                    <p class="detail-value" style="margin-top: 2px;">
                                        {{ $slip['payment_reference'] ?: '—' }}
                                    </p>
                                </div>
                            </td>
                            <td>
                                <div class="ref-box">
                                    <p class="party-label">Датум / Date</p>
                                    <p class="detail-value" style="margin-top: 2px; font-weight: bold;">
                                        {{ $slip['date'] }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    </table>

                    {{-- Signatures --}}
                    <table class="signature-section">
                        <tr>
                            <td>
                                <div class="signature-line">
                                    Потпис / Signature
                                </div>
                            </td>
                            <td>
                                <div class="signature-line">
                                    М.П. / Stamp
                                </div>
                            </td>
                            <td>
                                <div class="signature-line">
                                    Одговорно лице / Authorized
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- Bill reference footer --}}
                @if (!empty($slip['bill_number']))
                    <div class="bill-ref">
                        Фактура: {{ $slip['bill_number'] }}
                    </div>
                @endif
            </div>

            <p class="footer-text">
                Генерирано од Facturino &mdash; app.facturino.mk
            </p>
        </div>
    @endforeach
</body>

</html>
{{-- CLAUDE-CHECKPOINT --}}
