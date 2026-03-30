<!DOCTYPE html>
<html lang="mk">
<head>
    <title>ПП30 Налог за плаќање</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 8px;
            color: #1a1a1a;
            margin: 15px;
            padding: 0;
        }

        .pp30-slip {
            border: 2px solid #1a5090;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        /* Top bar */
        .top-bar {
            width: 100%;
            border-collapse: collapse;
        }
        .top-bar td {
            padding: 4px 8px;
            vertical-align: middle;
        }
        .top-bar .date-label {
            font-size: 6px;
            color: #1a5090;
            font-weight: bold;
        }
        .top-bar .form-title {
            text-align: right;
            font-size: 8px;
            font-weight: bold;
            color: #1a5090;
        }

        /* Column headers */
        .col-headers {
            width: 100%;
            border-collapse: collapse;
        }
        .col-headers td {
            width: 50%;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            color: #1a5090;
            padding: 3px 0;
            letter-spacing: 2px;
        }

        /* Main two-column layout */
        .form-body {
            width: 100%;
            border-collapse: collapse;
        }
        .form-body > tbody > tr > td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        /* Field rows */
        .field {
            border-top: 1px solid #a0b8d0;
            padding: 2px 6px;
            min-height: 22px;
        }
        .field-label {
            font-size: 6px;
            color: #1a5090;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 1px;
        }
        .field-value {
            font-size: 9px;
            color: #1a1a1a;
            min-height: 14px;
            padding-top: 1px;
        }

        /* Digit boxes */
        .digit-row {
            border-collapse: collapse;
        }
        .digit-row td {
            width: 13px;
            height: 15px;
            border: 1px solid #1a5090;
            text-align: center;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 9px;
            font-weight: bold;
            color: #1a1a1a;
            padding: 0;
        }

        /* Amount boxes */
        .amount-row {
            border-collapse: collapse;
        }
        .amount-row td {
            width: 13px;
            height: 15px;
            border: 1px solid #1a5090;
            text-align: center;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 9px;
            font-weight: bold;
            padding: 0;
        }
        .amount-row .comma {
            border: none;
            width: 8px;
            font-size: 12px;
            font-weight: bold;
        }
        .amount-row .currency-label {
            border: none;
            font-size: 7px;
            font-weight: bold;
            color: #1a5090;
            padding-left: 4px;
            text-align: left;
        }

        /* Code boxes */
        .code-row {
            border-collapse: collapse;
        }
        .code-row td {
            width: 14px;
            height: 15px;
            border: 1px solid #1a5090;
            text-align: center;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 9px;
            font-weight: bold;
            padding: 0;
        }

        .nacin-box {
            width: 16px;
            height: 16px;
            border: 1px solid #1a5090;
            text-align: center;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
            line-height: 16px;
        }

        .signature-field {
            border-top: 1px solid #a0b8d0;
            padding: 2px 6px;
            min-height: 30px;
        }

        /* Date digit boxes */
        .date-row {
            border-collapse: collapse;
        }
        .date-row td {
            width: 12px;
            height: 14px;
            border: 1px solid #1a5090;
            text-align: center;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 8px;
            font-weight: bold;
            padding: 0;
        }

        .bottom-row {
            width: 100%;
            border-collapse: collapse;
            border-top: 1px solid #a0b8d0;
        }
        .bottom-row td {
            padding: 2px 6px;
            vertical-align: top;
        }

        .page-break {
            page-break-before: always;
        }

        .footer-warning {
            text-align: center;
            font-size: 7px;
            color: #888;
            margin-top: 8px;
            padding: 4px;
            border-top: 1px dashed #ccc;
        }
    </style>
</head>
<body>
    @foreach ($slips as $index => $slip)
        @if ($index > 0)
            <div class="page-break"></div>
        @endif

        @php
            // Parse date dd.mm.yyyy
            $dateParts = explode('.', $slip['date'] ?? '');
            $dd = str_pad($dateParts[0] ?? '', 2, '0', STR_PAD_LEFT);
            $mm = str_pad($dateParts[1] ?? '', 2, '0', STR_PAD_LEFT);
            $yyyy = str_pad($dateParts[2] ?? '', 4, '0', STR_PAD_LEFT);

            // Parse debtor account digits
            $debtorAccount = str_replace(' ', '', $slip['debtor_iban'] ?? '');
            $debtorDigits = str_split(str_pad($debtorAccount, 16, ' ', STR_PAD_RIGHT));

            // Parse creditor account digits
            $creditorAccount = str_replace(' ', '', $slip['creditor_iban'] ?? '');
            $creditorDigits = str_split(str_pad($creditorAccount, 16, ' ', STR_PAD_RIGHT));

            // Parse amount
            $amountCents = $slip['amount'] ?? 0;
            $amountDenars = intdiv(abs($amountCents), 100);
            $amountDeni = abs($amountCents) % 100;
            $amountStr = str_pad((string)$amountDenars, 10, ' ', STR_PAD_LEFT);
            $amountDigits = str_split($amountStr);
            $deniStr = str_pad((string)$amountDeni, 2, '0', STR_PAD_LEFT);
            $deniDigits = str_split($deniStr);

            // Purpose code digits (3 digits)
            $purposeCode = str_pad($slip['purpose_code'] ?? '', 3, ' ', STR_PAD_RIGHT);
            $purposeDigits = str_split($purposeCode);
        @endphp

        <div class="pp30-slip">
            {{-- Top bar --}}
            <table class="top-bar">
                <tr>
                    <td style="width: 50%;">
                        <span class="date-label">ДЕН НА ВАЛУТА</span><br>
                        <table class="date-row" style="margin-top: 2px;">
                            <tr>
                                <td>{{ substr($dd, 0, 1) }}</td>
                                <td>{{ substr($dd, 1, 1) }}</td>
                                <td>{{ substr($mm, 0, 1) }}</td>
                                <td>{{ substr($mm, 1, 1) }}</td>
                                <td>{{ substr($yyyy, 0, 1) }}</td>
                                <td>{{ substr($yyyy, 1, 1) }}</td>
                                <td>{{ substr($yyyy, 2, 1) }}</td>
                                <td>{{ substr($yyyy, 3, 1) }}</td>
                            </tr>
                        </table>
                    </td>
                    <td class="form-title">НАЛОГ ЗА ПЛАЌАЊЕ</td>
                </tr>
            </table>

            {{-- Column headers --}}
            <table class="col-headers">
                <tr>
                    <td>НАЛОГОДАВАЧ</td>
                    <td>ПРИМАЧ</td>
                </tr>
            </table>

            {{-- Two-column form body --}}
            <table class="form-body">
                <tr>
                    {{-- LEFT: НАЛОГОДАВАЧ --}}
                    <td style="border-right: 2px solid #1a5090;">
                        {{-- Назив и седиште --}}
                        <div class="field">
                            <div class="field-label">НАЗИВ И СЕДИШТЕ НА НАЛОГОДАВАЧ</div>
                            <div class="field-value">{{ $slip['debtor_name'] }}</div>
                        </div>

                        {{-- Сметка --}}
                        <div class="field">
                            <div class="field-label">СМЕТКА НА НАЛОГОДАВАЧ</div>
                            <table class="digit-row" style="margin-top: 2px;">
                                <tr>
                                    @foreach($debtorDigits as $d)
                                        <td>{{ trim($d) }}</td>
                                    @endforeach
                                </tr>
                            </table>
                        </div>

                        {{-- Банка --}}
                        <div class="field">
                            <div class="field-label">БАНКА НА НАЛОГОДАВАЧ</div>
                            <div class="field-value">{{ $slip['debtor_bank'] ?: '—' }}</div>
                        </div>

                        {{-- Цел на дознака --}}
                        <div class="field">
                            <div class="field-label">ЦЕЛ НА ДОЗНАКА</div>
                            <div class="field-value">{{ $slip['description'] }}</div>
                        </div>

                        {{-- Повикување на број --}}
                        <div class="field">
                            <div class="field-label">ПОВИКУВАЊЕ НА БРОЈ</div>
                            <div class="field-value">{{ $slip['payment_reference'] ?: '' }}</div>
                        </div>

                        {{-- Потпис --}}
                        <div class="signature-field">
                            <div class="field-label">ПОТПИС</div>
                        </div>
                    </td>

                    {{-- RIGHT: ПРИМАЧ --}}
                    <td>
                        {{-- Назив и седиште --}}
                        <div class="field">
                            <div class="field-label">НАЗИВ И СЕДИШТЕ НА ПРИМАЧ</div>
                            <div class="field-value">{{ $slip['creditor_name'] }}</div>
                        </div>

                        {{-- Сметка --}}
                        <div class="field">
                            <div class="field-label">СМЕТКА НА ПРИМАЧ</div>
                            <table class="digit-row" style="margin-top: 2px;">
                                <tr>
                                    @foreach($creditorDigits as $d)
                                        <td>{{ trim($d) }}</td>
                                    @endforeach
                                </tr>
                            </table>
                        </div>

                        {{-- Банка --}}
                        <div class="field">
                            <div class="field-label">БАНКА НА ПРИМАЧ</div>
                            <div class="field-value">{{ $slip['creditor_bank'] ?: '—' }}</div>
                        </div>

                        {{-- Износ --}}
                        <div class="field">
                            <div class="field-label">ИЗНОС</div>
                            <table class="amount-row" style="margin-top: 2px;">
                                <tr>
                                    <td class="currency-label">МКД</td>
                                    @foreach($amountDigits as $d)
                                        <td>{{ trim($d) }}</td>
                                    @endforeach
                                    <td class="comma">,</td>
                                    @foreach($deniDigits as $d)
                                        <td>{{ $d }}</td>
                                    @endforeach
                                </tr>
                            </table>
                        </div>

                        {{-- Шифра + Начин --}}
                        <div class="field">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 50%; vertical-align: top; padding: 0;">
                                        <div class="field-label">ШИФРА</div>
                                        <table class="code-row" style="margin-top: 2px;">
                                            <tr>
                                                @foreach($purposeDigits as $d)
                                                    <td>{{ trim($d) }}</td>
                                                @endforeach
                                            </tr>
                                        </table>
                                    </td>
                                    <td style="width: 50%; vertical-align: top; padding: 0; text-align: right;">
                                        <div class="field-label">НАЧИН</div>
                                        <div class="nacin-box" style="margin-top: 2px;">1</div>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        {{-- Повикување на број (creditor side) --}}
                        <div class="field">
                            <div class="field-label">ПОВИКУВАЊЕ НА БРОЈ</div>
                            <div class="field-value">{{ $slip['payment_reference'] ?: '' }}</div>
                        </div>

                        {{-- Датум + Место --}}
                        <table class="bottom-row">
                            <tr>
                                <td style="width: 55%;">
                                    <div class="field-label">ДАТУМ НА УПЛАТА</div>
                                    <table class="date-row" style="margin-top: 2px;">
                                        <tr>
                                            <td>{{ substr($dd, 0, 1) }}</td>
                                            <td>{{ substr($dd, 1, 1) }}</td>
                                            <td>{{ substr($mm, 0, 1) }}</td>
                                            <td>{{ substr($mm, 1, 1) }}</td>
                                            <td>{{ substr($yyyy, 0, 1) }}</td>
                                            <td>{{ substr($yyyy, 1, 1) }}</td>
                                            <td>{{ substr($yyyy, 2, 1) }}</td>
                                            <td>{{ substr($yyyy, 3, 1) }}</td>
                                        </tr>
                                    </table>
                                </td>
                                <td style="width: 45%;">
                                    <div class="field-label">МЕСТО НА УПЛАТА</div>
                                    <div class="field-value">&nbsp;</div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Bill reference --}}
        @if (!empty($slip['bill_number']))
            <div style="font-size: 7px; color: #888; text-align: right; margin-top: -12px; margin-bottom: 5px;">
                Фактура: {{ $slip['bill_number'] }}
            </div>
        @endif

        <div class="footer-warning">
            ⚠ Овој документ е генериран од Facturino и служи како помошен образец за внес во е-банкарство.
            Не е официјален банкарски образец ПП30. За шалтерска уплата користете официјален образец.
            Проверете ги сите податоци пред извршување на уплатата.
        </div>
    @endforeach
</body>
</html>
{{-- CLAUDE-CHECKPOINT --}}
