<!DOCTYPE html>
<html lang="mk">
<head>
    <title>ПП50 Налог за јавни приходи</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 8px;
            color: #1a1a1a;
            margin: 15px;
            padding: 0;
        }

        .pp50-slip {
            border: 2px solid #b01050;
            margin-bottom: 15px;
            page-break-inside: avoid;
        }

        /* Top bar with title and value date */
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
            color: #b01050;
            font-weight: bold;
        }
        .top-bar .form-title {
            text-align: right;
            font-size: 8px;
            font-weight: bold;
            color: #b01050;
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
            color: #b01050;
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
            border-top: 1px solid #d4a0b0;
            padding: 2px 6px;
            min-height: 22px;
        }
        .field-label {
            font-size: 6px;
            color: #b01050;
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

        /* Digit boxes for account numbers */
        .digit-row {
            border-collapse: collapse;
        }
        .digit-row td {
            width: 13px;
            height: 15px;
            border: 1px solid #b01050;
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
            border: 1px solid #b01050;
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
            color: #b01050;
            padding-left: 4px;
            text-align: left;
        }

        /* Revenue code boxes */
        .rev-row {
            border-collapse: collapse;
        }
        .rev-row td {
            width: 14px;
            height: 15px;
            border: 1px solid #b01050;
            text-align: center;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 9px;
            font-weight: bold;
            padding: 0;
        }
        .rev-row .spacer {
            border: none;
            width: 6px;
        }

        /* Nacin (payment method) box */
        .nacin-box {
            width: 16px;
            height: 16px;
            border: 1px solid #b01050;
            text-align: center;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
            line-height: 16px;
        }

        /* Signature field */
        .signature-field {
            border-top: 1px solid #d4a0b0;
            padding: 2px 6px;
            min-height: 30px;
        }

        /* Bottom date row */
        .bottom-row {
            width: 100%;
            border-collapse: collapse;
            border-top: 1px solid #d4a0b0;
        }
        .bottom-row td {
            padding: 2px 6px;
            vertical-align: top;
        }

        /* Date digit boxes */
        .date-row {
            border-collapse: collapse;
        }
        .date-row td {
            width: 12px;
            height: 14px;
            border: 1px solid #b01050;
            text-align: center;
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 8px;
            font-weight: bold;
            padding: 0;
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
            $dd = $dateParts[0] ?? '';
            $mm = $dateParts[1] ?? '';
            $yyyy = $dateParts[2] ?? '';

            // Parse debtor account digits (remove spaces)
            $debtorAccount = str_replace(' ', '', $slip['debtor_iban'] ?? '');
            $debtorDigits = str_split(str_pad($debtorAccount, 16, ' ', STR_PAD_RIGHT));

            // Parse creditor account digits
            $creditorAccount = str_replace(' ', '', $slip['creditor_iban'] ?? '');
            $creditorDigits = str_split(str_pad($creditorAccount, 15, ' ', STR_PAD_RIGHT));

            // Parse amount: integer part digits + 2 decimal digits
            $amountCents = $slip['amount'] ?? 0;
            $amountDenars = intdiv(abs($amountCents), 100);
            $amountDeni = abs($amountCents) % 100;
            $amountStr = str_pad((string)$amountDenars, 10, ' ', STR_PAD_LEFT);
            $amountDigits = str_split($amountStr);
            $deniStr = str_pad((string)$amountDeni, 2, '0', STR_PAD_LEFT);
            $deniDigits = str_split($deniStr);

            // Revenue code digits (6 digits + program 3 digits)
            $revCode = str_pad($slip['revenue_code'] ?? '', 6, ' ', STR_PAD_RIGHT);
            $revDigits = str_split($revCode);
            // Program is usually empty for payroll
            $program = '   ';
            $progDigits = str_split($program);

            // Municipality code for УПЛАТНА СМЕТКА
            $munCode = str_pad($slip['municipality_code'] ?? '', 3, ' ', STR_PAD_RIGHT);
        @endphp

        <div class="pp50-slip">
            {{-- Top bar: value date + form title --}}
            <table class="top-bar">
                <tr>
                    <td style="width: 50%;">
                        <span class="date-label">ДАТУМ НА ВАЛУТА</span><br>
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
                    <td class="form-title">НАЛОГ ЗА ЈАВНИ ПРИХОДИ</td>
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
                    {{-- LEFT COLUMN: НАЛОГОДАВАЧ --}}
                    <td style="border-right: 2px solid #b01050;">
                        {{-- 1. Назив и седиште --}}
                        <div class="field">
                            <div class="field-label">НАЗИВ И СЕДИШТЕ НА НАЛОГОДАВАЧ</div>
                            <div class="field-value">{{ $slip['debtor_name'] }}</div>
                        </div>

                        {{-- 2. Банка --}}
                        <div class="field">
                            <div class="field-label">БАНКА НА НАЛОГОДАВАЧ</div>
                            <div class="field-value">{{ $slip['debtor_bank'] ?: '—' }}</div>
                        </div>

                        {{-- 3. Трансакциска сметка --}}
                        <div class="field">
                            <div class="field-label">ТРАНСАКЦИСКА СМЕТКА</div>
                            <table class="digit-row" style="margin-top: 2px;">
                                <tr>
                                    @foreach($debtorDigits as $d)
                                        <td>{{ trim($d) }}</td>
                                    @endforeach
                                </tr>
                            </table>
                        </div>

                        {{-- 4. Даночен број или ЕМБГ --}}
                        <div class="field">
                            <div class="field-label">ДАНОЧЕН БРОЈ или ЕМБГ</div>
                            <div class="field-value">{{ $slip['payment_reference'] ?? '' }}</div>
                        </div>

                        {{-- 5. Повикување на број - задолжување --}}
                        <div class="field">
                            <div class="field-label">ПОВИКУВАЊЕ НА БРОЈ - ЗАДОЛЖУВАЊЕ</div>
                            <div class="field-value">{{ $slip['payment_reference'] ?? '' }}</div>
                        </div>

                        {{-- 6. Цел на дознака --}}
                        <div class="field">
                            <div class="field-label">ЦЕЛ НА ДОЗНАКА</div>
                            <div class="field-value">{{ $slip['description'] }}</div>
                        </div>

                        {{-- 7. Потпис --}}
                        <div class="signature-field">
                            <div class="field-label">ПОТПИС</div>
                        </div>
                    </td>

                    {{-- RIGHT COLUMN: ПРИМАЧ --}}
                    <td>
                        {{-- 1. Назив и седиште --}}
                        <div class="field">
                            <div class="field-label">НАЗИВ И СЕДИШТЕ НА ПРИМАЧ</div>
                            <div class="field-value">{{ $slip['creditor_name'] }}</div>
                        </div>

                        {{-- 2. Банка на примач --}}
                        <div class="field">
                            <div class="field-label">БАНКА НА ПРИМАЧ</div>
                            <div class="field-value">{{ $slip['creditor_bank'] ?: 'Народна Банка на РСМ' }}</div>
                        </div>

                        {{-- 3. Трансакциска сметка --}}
                        <div class="field">
                            <div class="field-label">ТРАНСАКЦИСКА СМЕТКА</div>
                            <table class="digit-row" style="margin-top: 2px;">
                                <tr>
                                    @foreach($creditorDigits as $d)
                                        <td>{{ trim($d) }}</td>
                                    @endforeach
                                </tr>
                            </table>
                        </div>

                        {{-- 4. Износ --}}
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

                        {{-- 5. Уплатна сметка --}}
                        <div class="field">
                            <div class="field-label">УПЛАТНА СМЕТКА</div>
                            <table class="rev-row" style="margin-top: 2px;">
                                <tr>
                                    <td>8</td>
                                    <td>4</td>
                                    <td>5</td>
                                    <td class="spacer"></td>
                                    <td>{{ substr($munCode, 0, 1) }}</td>
                                    <td>{{ substr($munCode, 1, 1) }}</td>
                                    <td>{{ substr($munCode, 2, 1) }}</td>
                                    <td class="spacer"></td>
                                    @php
                                        // Last 5 digits of revenue code for payment account
                                        $revenueForAccount = str_pad(substr($slip['revenue_code'] ?? '', 0, 5), 5, ' ');
                                    @endphp
                                    @foreach(str_split($revenueForAccount) as $d)
                                        <td>{{ trim($d) }}</td>
                                    @endforeach
                                </tr>
                            </table>
                        </div>

                        {{-- 6. Сметка на корисник --}}
                        <div class="field">
                            <div class="field-label">СМЕТКА НА КОРИСНИК</div>
                            <div class="field-value">&nbsp;</div>
                        </div>

                        {{-- 7. Приходна шифра и програма + Начин --}}
                        <div class="field">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 70%; vertical-align: top; padding: 0;">
                                        <div class="field-label">ПРИХОДНА ШИФРА И ПРОГРАМА</div>
                                        <table class="rev-row" style="margin-top: 2px;">
                                            <tr>
                                                @foreach($revDigits as $d)
                                                    <td>{{ trim($d) }}</td>
                                                @endforeach
                                                <td class="spacer"></td>
                                                @foreach($progDigits as $d)
                                                    <td>{{ trim($d) }}</td>
                                                @endforeach
                                            </tr>
                                        </table>
                                    </td>
                                    <td style="width: 30%; vertical-align: top; padding: 0; text-align: right;">
                                        <div class="field-label">НАЧИН</div>
                                        <div class="nacin-box" style="margin-top: 2px;">1</div>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        {{-- 8. Датум на уплата + Место --}}
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
            Не е официјален банкарски образец ПП50. За шалтерска уплата користете официјален образец.
            Проверете ги сите податоци пред извршување на уплатата.
        </div>
    @endforeach
</body>
</html>
{{-- CLAUDE-CHECKPOINT --}}
