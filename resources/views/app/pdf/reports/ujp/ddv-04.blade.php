<!DOCTYPE html>
<html lang="mk">

<head>
    <title>ДДВ-04 Даночна пријава</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
        }

        .sub-container {
            padding: 0px 12px;
        }

        /* Header bar - teal/green like official DDV-04 */
        .ddv-header-bar {
            background: #1a6b5a;
            padding: 12px 15px;
            width: 100%;
        }

        .ddv-header-logo {
            font-size: 6px;
            color: #a0d0c0;
            line-height: 1.3;
            margin: 0;
        }

        .ddv-header-title {
            font-size: 16px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 3px;
            margin: 0;
            text-align: center;
        }

        .ddv-header-subtitle {
            font-size: 11px;
            color: #c0e8d8;
            margin: 2px 0 0 0;
            text-align: center;
        }

        .ddv-code-badge {
            background: #1a6b5a;
            color: #ffffff;
            font-size: 16px;
            font-weight: bold;
            padding: 6px 12px;
            border: 2px solid #ffffff;
            text-align: center;
        }

        /* Company info section */
        .info-section {
            border: 1px solid #999;
            padding: 6px 8px;
            margin-top: 6px;
            font-size: 8px;
        }

        .info-label {
            font-size: 8px;
            color: #666;
            padding: 2px 3px;
            vertical-align: top;
        }

        .info-value {
            font-size: 9px;
            color: #1a1a1a;
            padding: 2px 4px;
            border-bottom: 1px solid #ccc;
        }

        .info-box {
            font-size: 9px;
            color: #1a1a1a;
            padding: 2px 6px;
            border: 1px solid #999;
            background: #fafafa;
            text-align: center;
        }

        /* Section titles */
        .section-title {
            font-weight: bold;
            font-size: 11px;
            color: #1a1a1a;
            margin: 12px 0 4px 0;
            padding: 0;
        }

        /* Form fields table */
        .fields-table {
            width: 100%;
            border: 1px solid #888;
            margin-bottom: 2px;
        }

        .fields-table th {
            background: #e8e8e8;
            padding: 4px 4px;
            font-size: 8px;
            font-weight: bold;
            color: #333;
            text-align: center;
            border-bottom: 1px solid #888;
            border-right: 1px solid #bbb;
        }

        .field-row {
            border-bottom: 1px solid #ddd;
        }

        .field-row:nth-child(even) {
            background: #fafafa;
        }

        .field-label-cell {
            padding: 3px 4px;
            font-size: 8px;
            color: #333;
            border-right: 1px solid #ddd;
            width: 48%;
            line-height: 1.3;
        }

        .field-number-cell {
            padding: 3px 2px;
            font-size: 9px;
            font-weight: bold;
            color: #555;
            text-align: center;
            border-right: 1px solid #ddd;
            width: 4%;
        }

        .field-base-cell {
            padding: 3px 4px;
            font-size: 9px;
            text-align: right;
            border-right: 1px solid #ddd;
            width: 20%;
        }

        .field-vat-cell {
            padding: 3px 2px;
            font-size: 9px;
            font-weight: bold;
            color: #555;
            text-align: center;
            border-right: 1px solid #ddd;
            width: 4%;
        }

        .field-amount-cell {
            padding: 3px 4px;
            font-size: 9px;
            text-align: right;
            width: 20%;
        }

        .total-row {
            background: #e8e8e8 !important;
            border-top: 1px solid #888;
        }

        .total-row td {
            font-weight: bold;
            font-size: 9px;
            color: #1a1a1a;
        }

        /* Single column fields (no base/vat split) */
        .field-single-cell {
            padding: 3px 4px;
            font-size: 9px;
            text-align: right;
            border-right: 1px solid #ddd;
        }

        .field-wide-amount {
            padding: 3px 4px;
            font-size: 9px;
            text-align: right;
        }

        /* Result highlight */
        .result-positive {
            color: #c0392b;
            font-weight: bold;
        }

        .result-negative {
            color: #27ae60;
            font-weight: bold;
        }

        /* Footer */
        .footer-section {
            margin-top: 15px;
            border: 1px solid #888;
            padding: 6px 8px;
        }

        .footer-title {
            font-weight: bold;
            font-size: 10px;
            margin: 0 0 4px 0;
        }

        .signature-table {
            width: 100%;
            margin-top: 8px;
        }

        .signature-line {
            border-top: 1px solid #333;
            padding-top: 3px;
            font-size: 8px;
            color: #555;
            text-align: center;
            width: 200px;
        }

        .page-number {
            text-align: right;
            font-size: 8px;
            color: #999;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    <div class="sub-container">

        {{-- Header Bar --}}
        <table class="ddv-header-bar" cellpadding="0" cellspacing="0" style="width: 100%;">
            <tr>
                <td style="width: 15%; vertical-align: middle;">
                    <p class="ddv-header-logo">
                        Република Северна Македонија<br>
                        Министерство за финансии<br>
                        <strong>УПРАВА ЗА ЈАВНИ ПРИХОДИ</strong>
                    </p>
                </td>
                <td style="width: 65%; vertical-align: middle; text-align: center;">
                    <p class="ddv-header-title">ДАНОЧНА ПРИЈАВА</p>
                    <p class="ddv-header-subtitle">на данокот на додадена вредност</p>
                </td>
                <td style="width: 20%; vertical-align: middle; text-align: right;">
                    <span class="ddv-code-badge">ДДВ-04</span>
                </td>
            </tr>
        </table>

        {{-- Company Info --}}
        <div class="info-section">
            <table style="width: 100%;" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="info-label" style="width: 18%;">Даночен<br>идентификациски број</td>
                    <td class="info-box" style="width: 30%;">{{ $company->vat_number ?? $company->vat_id ?? '-' }}</td>
                    <td style="width: 4%;"></td>
                    <td class="info-label" style="width: 12%; text-align: right;">Даночен период</td>
                    <td style="width: 36%; text-align: right;">
                        <table cellpadding="0" cellspacing="0" style="float: right;">
                            <tr>
                                <td style="font-size: 8px; color: #666; padding-right: 3px;">од</td>
                                <td class="info-box" style="min-width: 65px;">{{ $periodStart }}</td>
                                <td style="font-size: 8px; color: #666; padding: 0 3px;">до</td>
                                <td class="info-box" style="min-width: 65px;">{{ $periodEnd }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="info-label" style="padding-top: 5px;">Скратен назив<br>и адреса на вистинско<br>седиште за контакт</td>
                    <td class="info-value" colspan="2" style="padding-top: 5px;">
                        {{ $company->name }}
                        @if($company->address)
                            @if($company->address->address_street_1), {{ $company->address->address_street_1 }}@endif
                            @if($company->address->city), {{ $company->address->zip ?? '' }} {{ $company->address->city }}@endif
                        @endif
                    </td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td class="info-label" style="padding-top: 3px;">Телефон</td>
                    <td class="info-value" style="padding-top: 3px;">{{ ($company->address && $company->address->phone) ? $company->address->phone : '-' }}</td>
                    <td></td>
                    <td class="info-label" style="padding-top: 3px;">е-пошта</td>
                    <td class="info-value" style="padding-top: 3px;">{{ $company->email ?? '-' }}</td>
                </tr>
            </table>
        </div>

        {{-- ПРОМЕТ НА ДОБРА И УСЛУГИ (Output VAT) --}}
        <p class="section-title">ПРОМЕТ НА ДОБРА И УСЛУГИ</p>

        <table class="fields-table">
            <thead>
                <tr>
                    <th style="text-align: left; width: 48%;">&nbsp;</th>
                    <th style="width: 4%;">&nbsp;</th>
                    <th style="width: 20%;">Даночна основа без ДДВ</th>
                    <th style="width: 4%;">&nbsp;</th>
                    <th style="width: 20%;">ДДВ</th>
                </tr>
            </thead>
            <tbody>
                <tr class="field-row">
                    <td class="field-label-cell">Оданочив промет по општа даночна стапка</td>
                    <td class="field-number-cell">01</td>
                    <td class="field-base-cell">{{ number_format($fields[1] ?? 0, 2) }}</td>
                    <td class="field-vat-cell">02</td>
                    <td class="field-amount-cell">{{ number_format($fields[2] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row">
                    <td class="field-label-cell">Оданочив промет по повластена даночна стапка од 10%</td>
                    <td class="field-number-cell">03</td>
                    <td class="field-base-cell">{{ number_format($fields[3] ?? 0, 2) }}</td>
                    <td class="field-vat-cell">04</td>
                    <td class="field-amount-cell">{{ number_format($fields[4] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row">
                    <td class="field-label-cell">Оданочив промет по повластена стапка од 5%</td>
                    <td class="field-number-cell">05</td>
                    <td class="field-base-cell">{{ number_format($fields[5] ?? 0, 2) }}</td>
                    <td class="field-vat-cell">06</td>
                    <td class="field-amount-cell">{{ number_format($fields[6] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row">
                    <td class="field-label-cell">Извоз</td>
                    <td class="field-number-cell">07</td>
                    <td class="field-base-cell" colspan="3">{{ number_format($fields[7] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row">
                    <td class="field-label-cell">Промет ослободен од данок со право на одбивка на претходен данок</td>
                    <td class="field-number-cell">08</td>
                    <td class="field-base-cell" colspan="3">{{ number_format($fields[8] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row">
                    <td class="field-label-cell">Промет ослободен од данок без право на одбивка на претходен данок</td>
                    <td class="field-number-cell">09</td>
                    <td class="field-base-cell" colspan="3">{{ number_format($fields[9] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row">
                    <td class="field-label-cell">Промет извршен спрема даночни обврзници кои немаат седиште во земјата</td>
                    <td class="field-number-cell">10</td>
                    <td class="field-base-cell" colspan="3">{{ number_format($overrides[10] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row">
                    <td class="field-label-cell">Промет во земјата за кој данокот го пресметува примателот на прометот (член 32-а)</td>
                    <td class="field-number-cell">11</td>
                    <td class="field-base-cell" colspan="3">{{ number_format($overrides[11] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row">
                    <td class="field-label-cell">Примен промет од страна на даночни обврзници кои немаат седиште во земјата по општа даночна стапка (член 32 точка 4 и 5)</td>
                    <td class="field-number-cell">12</td>
                    <td class="field-base-cell">{{ number_format($overrides[12] ?? 0, 2) }}</td>
                    <td class="field-vat-cell">13</td>
                    <td class="field-amount-cell">{{ number_format($overrides[13] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row">
                    <td class="field-label-cell">Примен промет од страна на даночни обврзници кои немаат седиште во земјата по повластена даночна стапка (член 32 точка 4 и 5)</td>
                    <td class="field-number-cell">14</td>
                    <td class="field-base-cell">{{ number_format($overrides[14] ?? 0, 2) }}</td>
                    <td class="field-vat-cell">15</td>
                    <td class="field-amount-cell">{{ number_format($overrides[15] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row">
                    <td class="field-label-cell">Примен промет во земјата за кој данокот го пресметува примателот на прометот по општа даночна стапка (член 32-а)</td>
                    <td class="field-number-cell">16</td>
                    <td class="field-base-cell">{{ number_format($overrides[16] ?? 0, 2) }}</td>
                    <td class="field-vat-cell">17</td>
                    <td class="field-amount-cell">{{ number_format($overrides[17] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row">
                    <td class="field-label-cell">Примен промет во земјата за кој данокот го пресметува примателот на прометот по повластена даночна стапка (член 32-а)</td>
                    <td class="field-number-cell">18</td>
                    <td class="field-base-cell">{{ number_format($overrides[18] ?? 0, 2) }}</td>
                    <td class="field-vat-cell">19</td>
                    <td class="field-amount-cell">{{ number_format($overrides[19] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row total-row">
                    <td class="field-label-cell" style="font-weight: bold;">Вкупен ДДВ (02+04+06+13+15+17+19)</td>
                    <td class="field-number-cell"></td>
                    <td class="field-base-cell"></td>
                    <td class="field-vat-cell">20</td>
                    <td class="field-amount-cell" style="font-weight: bold; border: 1px solid #888;">{{ number_format($fields[10] ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- ВЛЕЗНИ ИСПОЛНУВАЊА СО ПРАВО НА ОДБИВКА (Input VAT) --}}
        <p class="section-title">ВЛЕЗНИ ИСПОЛНУВАЊА СО ПРАВО НА ОДБИВКА</p>

        <table class="fields-table">
            <thead>
                <tr>
                    <th style="text-align: left; width: 48%;">&nbsp;</th>
                    <th style="width: 4%;">&nbsp;</th>
                    <th style="width: 20%;">Даночна основа без ДДВ</th>
                    <th style="width: 4%;">&nbsp;</th>
                    <th style="width: 20%;">ДДВ</th>
                </tr>
            </thead>
            <tbody>
                <tr class="field-row">
                    <td class="field-label-cell">Влезен промет</td>
                    <td class="field-number-cell">21</td>
                    <td class="field-base-cell">{{ number_format($fields[11] ?? 0, 2) }}</td>
                    <td class="field-vat-cell">22</td>
                    <td class="field-amount-cell">{{ number_format($fields[12] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row">
                    <td class="field-label-cell">Влезен промет за кој данокот го пресметува примателот на прометот (член 32 точка 4 и 5)</td>
                    <td class="field-number-cell">23</td>
                    <td class="field-base-cell">{{ number_format($fields[13] ?? 0, 2) }}</td>
                    <td class="field-vat-cell">24</td>
                    <td class="field-amount-cell">{{ number_format($fields[14] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row">
                    <td class="field-label-cell">Влезен промет во земјата за кој данокот го пресметува примателот на прометот (член 32-а)</td>
                    <td class="field-number-cell">25</td>
                    <td class="field-base-cell">{{ number_format($fields[15] ?? 0, 2) }}</td>
                    <td class="field-vat-cell">26</td>
                    <td class="field-amount-cell">{{ number_format($fields[16] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row">
                    <td class="field-label-cell">Увоз</td>
                    <td class="field-number-cell">27</td>
                    <td class="field-base-cell">{{ number_format($fields[17] ?? 0, 2) }}</td>
                    <td class="field-vat-cell">28</td>
                    <td class="field-amount-cell">{{ number_format($fields[18] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row total-row">
                    <td class="field-label-cell" style="font-weight: bold;">Претходни даноци за одбивање (22+24+26+28)</td>
                    <td class="field-number-cell"></td>
                    <td class="field-base-cell"></td>
                    <td class="field-vat-cell">29</td>
                    <td class="field-amount-cell" style="font-weight: bold; border: 1px solid #888;">{{ number_format($fields[19] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row">
                    <td class="field-label-cell">Останати даноци, претходни даноци и износи за одбивање</td>
                    <td class="field-number-cell"></td>
                    <td class="field-base-cell"></td>
                    <td class="field-vat-cell">30</td>
                    <td class="field-amount-cell">{{ number_format($fields[30] ?? 0, 2) }}</td>
                </tr>
                <tr class="field-row">
                    <td class="field-label-cell" style="font-weight: bold;">
                        Даночен долг / побарување
                        <br><span style="font-size: 7px; color: #888;">* Ако барајте враќање на данокот, внесете "Х" пред полето 31</span>
                    </td>
                    <td class="field-number-cell"></td>
                    <td class="field-base-cell"></td>
                    <td class="field-vat-cell">31</td>
                    <td class="field-amount-cell {{ ($fields[31] ?? 0) > 0 ? 'result-positive' : 'result-negative' }}" style="border: 2px solid #888;">
                        {{ number_format($fields[31] ?? 0, 2) }}
                    </td>
                </tr>
                <tr class="field-row">
                    <td class="field-label-cell">
                        Отстапување на побарување
                        <br><span style="font-size: 7px; color: #888;">* Ако отстапувајте побарување, внесете "Х" пред полето 32</span>
                    </td>
                    <td class="field-number-cell"></td>
                    <td class="field-base-cell"></td>
                    <td class="field-vat-cell">32</td>
                    <td class="field-amount-cell">{{ number_format($fields[32] ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Footer: Составувач и Потписник --}}
        <div class="footer-section" style="margin-top: 10px;">
            <p class="footer-title">ПОДАТОЦИ ЗА СОСТАВУВАЧОТ</p>
            <table style="width: 100%;">
                <tr>
                    <td class="info-label" style="width: 12%;">Назив /<br>Име и презиме</td>
                    <td class="info-value" style="width: 88%;">{{ $company->name }}</td>
                </tr>
                <tr>
                    <td class="info-label">ЕДБ / ЕМБГ</td>
                    <td style="padding-top: 3px;">
                        <table style="width: 100%;">
                            <tr>
                                <td class="info-value" style="width: 45%;">{{ $company->vat_number ?? $company->vat_id ?? '-' }}</td>
                                <td class="info-label" style="width: 15%; text-align: right;">Датум на пополнување</td>
                                <td class="info-value" style="width: 40%;">{{ now()->format('d.m.Y') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="info-label">Својство</td>
                    <td style="padding-top: 3px;">
                        <table style="width: 100%;">
                            <tr>
                                <td class="info-value" style="width: 45%;">Овластено лице</td>
                                <td class="info-label" style="width: 15%; text-align: right;">Потпис</td>
                                <td class="info-value" style="width: 40%;">_______________</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <div class="footer-section" style="margin-top: 6px;">
            <p class="footer-title">ПОДАТОЦИ ЗА ПОТПИСНИКОТ</p>
            <table style="width: 100%;">
                <tr>
                    <td class="info-label" style="width: 12%;">Име и презиме</td>
                    <td class="info-value" style="width: 88%;">&nbsp;</td>
                </tr>
                <tr>
                    <td class="info-label">ЕМБГ</td>
                    <td style="padding-top: 3px;">
                        <table style="width: 100%;">
                            <tr>
                                <td class="info-value" style="width: 45%;">&nbsp;</td>
                                <td class="info-label" style="width: 15%; text-align: right;">Датум на пополнување</td>
                                <td class="info-value" style="width: 40%;">{{ now()->format('d.m.Y') }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td class="info-label">Својство</td>
                    <td style="padding-top: 3px;">
                        <table style="width: 100%;">
                            <tr>
                                <td class="info-value" style="width: 45%;">&nbsp;</td>
                                <td class="info-label" style="width: 15%; text-align: right;">Потпис</td>
                                <td class="info-value" style="width: 40%;">_______________</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <p class="page-number">1 / 1</p>
    </div>
</body>

</html>

{{-- CLAUDE-CHECKPOINT --}}
