<!DOCTYPE html>
<html lang="mk">

<head>
    <title>ДП Даночна пријава за данок на добивка</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 8px;
            color: #333;
            margin: 15px;
            padding: 0;
        }

        table {
            border-collapse: collapse;
        }

        /* Header - burgundy/dark red for DP form */
        .dp-header-bar {
            background: #6b1a3a;
            padding: 10px 12px;
            width: 100%;
        }

        .dp-header-logo {
            font-size: 6px;
            color: #d0a0b0;
            line-height: 1.3;
            margin: 0;
        }

        .dp-header-title {
            font-size: 16px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 3px;
            margin: 0;
            text-align: center;
        }

        .dp-header-subtitle {
            font-size: 10px;
            color: #e0c0d0;
            margin: 2px 0 0 0;
            text-align: center;
        }

        .dp-code-badge {
            background: #8b2252;
            color: #ffffff;
            font-size: 18px;
            font-weight: bold;
            padding: 6px 14px;
            border: 2px solid #ffffff;
            text-align: center;
        }

        /* Company info */
        .info-section {
            border: 1px solid #999;
            padding: 5px 6px;
            margin-top: 5px;
            font-size: 7px;
        }

        .info-label {
            font-size: 7px;
            color: #666;
            padding: 1px 2px;
            vertical-align: top;
        }

        .info-value {
            font-size: 8px;
            color: #1a1a1a;
            padding: 1px 3px;
            border-bottom: 1px solid #ccc;
        }

        .info-box {
            font-size: 8px;
            color: #1a1a1a;
            padding: 2px 5px;
            border: 1px solid #999;
            background: #fafafa;
            text-align: center;
        }

        /* Section titles */
        .section-heading {
            font-weight: bold;
            font-size: 9px;
            color: #1a1a1a;
            margin: 8px 0 3px 0;
            padding: 0;
        }

        .section-note {
            font-size: 7px;
            color: #888;
            text-align: right;
            margin: 0 0 2px 0;
            font-style: italic;
        }

        /* AOP fields table */
        .aop-table {
            width: 100%;
            border: 1px solid #888;
        }

        .aop-row {
            border-bottom: 1px solid #ddd;
        }

        .aop-row:nth-child(even) {
            background: #fafafa;
        }

        .aop-row-num {
            padding: 2px 3px;
            font-size: 8px;
            font-weight: bold;
            color: #555;
            text-align: center;
            border-right: 1px solid #ddd;
            width: 5%;
            vertical-align: middle;
        }

        .aop-row-label {
            padding: 2px 4px;
            font-size: 7.5px;
            color: #333;
            border-right: 1px solid #ddd;
            width: 70%;
            line-height: 1.3;
        }

        .aop-row-code {
            padding: 2px 2px;
            font-size: 8px;
            font-weight: bold;
            color: #555;
            text-align: center;
            border-right: 1px solid #ddd;
            width: 5%;
            vertical-align: middle;
        }

        .aop-row-value {
            padding: 2px 4px;
            font-size: 8px;
            text-align: right;
            width: 20%;
            vertical-align: middle;
        }

        /* Section header rows */
        .section-row {
            background: #f0e0e8 !important;
            border-bottom: 1px solid #888;
        }

        .section-row td {
            font-weight: bold;
            font-size: 8px;
            color: #6b1a3a;
            padding: 3px 4px;
        }

        /* Formula / total rows */
        .formula-row {
            background: #f0ece8 !important;
            border-top: 1px solid #aaa;
        }

        .formula-row td {
            font-weight: bold;
        }

        /* Result row */
        .result-row {
            background: #f0e0e8 !important;
            border-top: 2px solid #6b1a3a;
        }

        .result-row td {
            font-weight: bold;
            font-size: 9px;
            color: #6b1a3a;
        }

        .value-box {
            border: 1px solid #999;
            background: #fff;
            padding: 2px 4px;
            min-width: 60px;
            text-align: right;
        }

        .value-positive {
            color: #c0392b;
        }

        .value-negative {
            color: #27ae60;
        }

        /* Footer */
        .signature-section {
            margin-top: 15px;
            width: 100%;
        }

        .signature-label {
            font-size: 8px;
            color: #555;
            border-top: 1px solid #999;
            padding-top: 3px;
            width: 150px;
            text-align: center;
        }

        .page-number {
            text-align: right;
            font-size: 7px;
            color: #999;
            margin-top: 3px;
        }
    </style>
</head>

<body>

    {{-- Header Bar --}}
    <table class="dp-header-bar" cellpadding="0" cellspacing="0" style="width: 100%;">
        <tr>
            <td style="width: 15%; vertical-align: middle;">
                <p class="dp-header-logo">
                    Република Северна Македонија<br>
                    Министерство за финансии<br>
                    <strong>УПРАВА ЗА ЈАВНИ ПРИХОДИ</strong>
                </p>
            </td>
            <td style="width: 65%; vertical-align: middle; text-align: center;">
                <p class="dp-header-title">{{ $formTitle }}</p>
                <p class="dp-header-subtitle">{{ $formSubtitle }}</p>
            </td>
            <td style="width: 20%; vertical-align: middle; text-align: right;">
                <span class="dp-code-badge">{{ $formCode }}</span>
            </td>
        </tr>
    </table>

    @if(!empty($sluzhbenVesnik))
        <p style="font-size: 7px; color: #999; text-align: right; margin: 2px 0 0 0; font-style: italic;">* {{ $sluzhbenVesnik }}</p>
    @endif

    {{-- Company Info --}}
    <div class="info-section">
        <table style="width: 100%;" cellpadding="0" cellspacing="0">
            <tr>
                <td class="info-label" style="width: 16%;">Единствен даночен број</td>
                <td class="info-box" style="width: 25%;">{{ $company->vat_number ?? $company->vat_id ?? '-' }}</td>
                <td style="width: 4%;"></td>
                <td class="info-label" style="width: 12%; text-align: right;">Даночен период</td>
                <td style="width: 40%; text-align: right;">
                    <span style="font-size: 7px; color: #666;">од</span>
                    <span class="info-box" style="padding: 1px 8px;">{{ $periodStart }}</span>
                    <span style="font-size: 7px; color: #666;">до</span>
                    <span class="info-box" style="padding: 1px 8px;">{{ $periodEnd }}</span>
                </td>
            </tr>
            <tr>
                <td class="info-label" style="padding-top: 4px;">Скратен назив и адреса</td>
                <td class="info-value" colspan="2" style="padding-top: 4px;">
                    {{ $company->name }}
                    @if($company->address)
                        @if($company->address->address_street_1), {{ $company->address->address_street_1 }}@endif
                        @if($company->address->city), {{ $company->address->zip ?? '' }} {{ $company->address->city }}@endif
                    @endif
                </td>
                <td colspan="2"></td>
            </tr>
        </table>
    </div>

    {{-- Form Title --}}
    <p class="section-heading" style="margin-top: 10px; font-size: 10px;">УТВРДУВАЊЕ НА ДАНОК НА ДОБИВКА — ГОДИШНА ПРИЈАВА</p>
    <p class="section-note">* износи во денари, без дени</p>

    {{-- AOP Fields Table --}}
    <table class="aop-table">
        <thead>
            <tr style="background: #e0e0e0; border-bottom: 1px solid #888;">
                <th style="width: 5%; font-size: 7px; padding: 2px; text-align: center; border-right: 1px solid #bbb;">Ред</th>
                <th style="width: 70%; font-size: 7px; padding: 2px; text-align: left; border-right: 1px solid #bbb;">Опис</th>
                <th style="width: 5%; font-size: 7px; padding: 2px; text-align: center; border-right: 1px solid #bbb;">АОП</th>
                <th style="width: 20%; font-size: 7px; padding: 2px; text-align: center;">Износ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($config['sections'] as $sectionKey => $section)
                @foreach($section['fields'] as $field)
                    @php
                        $aopCode = $field['aop'];
                        $value = $aop[$aopCode] ?? 0;
                        $isFormula = ($field['source'] ?? '') === 'formula';
                        $isSectionHeader = in_array($field['row'], ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII']);
                        $isResult = $aopCode === '35';
                        $isSigned = $field['signed'] ?? false;
                        $rowClass = $isResult ? 'result-row' : ($isSectionHeader ? 'section-row' : ($isFormula ? 'formula-row' : 'aop-row'));
                        $valueClass = '';
                        if ($isSigned && $value > 0) $valueClass = 'value-positive';
                        if ($isSigned && $value < 0) $valueClass = 'value-negative';
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td class="aop-row-num">{{ $field['row'] }}.</td>
                        <td class="aop-row-label">{{ $field['label'] }}</td>
                        <td class="aop-row-code">{{ $aopCode }}</td>
                        <td class="aop-row-value">
                            @if($value != 0 || $isFormula || $isSectionHeader)
                                <span class="value-box {{ $valueClass }}">{{ number_format($value, 0, '.', ',') }}</span>
                            @else
                                <span class="value-box">&nbsp;</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>

    {{-- Tax Rate Note --}}
    <p style="font-size: 7px; color: #888; margin-top: 4px;">
        * Даночна стапка: 10% согласно Закон за данокот на добивка (Сл. весник бр. 112/2014)
    </p>

    {{-- Signature Section --}}
    <table class="signature-section">
        <tr>
            <td style="width: 30%; padding-top: 5px;">
                <p style="font-size: 7px; color: #666;">Датум: {{ now()->format('d.m.Y') }}</p>
            </td>
            <td style="width: 35%; text-align: center; padding-top: 30px;">
                <p class="signature-label">Составил</p>
            </td>
            <td style="width: 35%; text-align: center; padding-top: 30px;">
                <p class="signature-label">Одговорно лице</p>
            </td>
        </tr>
    </table>

    <p class="page-number">1 / 1</p>

</body>

</html>

{{-- CLAUDE-CHECKPOINT --}}
