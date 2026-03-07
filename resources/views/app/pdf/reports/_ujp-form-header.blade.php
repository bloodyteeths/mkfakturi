{{--
    UJP Official Form Header
    Matches the official Службен Весник layout used by UJP.

    Required variables:
    - $formCode       (string) e.g. 'ДБ', 'ДДВ-04', 'Образец 36'
    - $formTitle      (string) e.g. 'ДАНОЧЕН БИЛАНС'
    - $formSubtitle   (string) e.g. 'за оданочување на добивка'
    - $company        (Company model)
    - $year           (int)

    Optional:
    - $periodStart    (string) e.g. '01.01.2025'
    - $periodEnd      (string) e.g. '31.12.2025'
    - $sluzhbenVesnik (string) e.g. 'Службен весник на РСМ бр. 6/2020'
    - $isCorrection   (bool) default false
    - $correctionNumber (string)
--}}

<style type="text/css">
    .ujp-header-bar {
        background: #2d2040;
        padding: 12px 15px;
        margin-bottom: 0;
        width: 100%;
    }
    .ujp-header-table {
        width: 100%;
    }
    .ujp-logo-cell {
        width: 15%;
        vertical-align: middle;
        text-align: left;
    }
    .ujp-logo-text {
        font-size: 6px;
        color: #c0b0d0;
        line-height: 1.3;
        margin: 0;
        padding: 0;
    }
    .ujp-title-cell {
        width: 65%;
        vertical-align: middle;
        text-align: center;
    }
    .ujp-form-title {
        font-size: 18px;
        font-weight: bold;
        color: #ffffff;
        letter-spacing: 3px;
        margin: 0;
        padding: 0;
    }
    .ujp-form-subtitle {
        font-size: 12px;
        color: #d0c0e0;
        margin: 3px 0 0 0;
        padding: 0;
    }
    .ujp-code-cell {
        width: 20%;
        vertical-align: middle;
        text-align: right;
    }
    .ujp-code-badge {
        display: inline-block;
        background: #8b2252;
        color: #ffffff;
        font-size: 20px;
        font-weight: bold;
        padding: 8px 16px;
        border: 2px solid #ffffff;
        letter-spacing: 1px;
    }
    .ujp-vesnik-note {
        font-size: 7px;
        color: #999;
        text-align: right;
        margin: 2px 0 0 0;
        padding: 0;
        font-style: italic;
    }
    .ujp-info-section {
        border: 1px solid #999;
        padding: 8px 10px;
        margin-top: 8px;
    }
    .ujp-info-table {
        width: 100%;
        border-collapse: collapse;
    }
    .ujp-info-label {
        font-size: 8px;
        color: #666;
        padding: 2px 4px;
        vertical-align: top;
        width: 22%;
    }
    .ujp-info-value {
        font-size: 10px;
        color: #1a1a1a;
        padding: 2px 4px;
        border-bottom: 1px solid #ccc;
        vertical-align: top;
    }
    .ujp-info-value-box {
        font-size: 10px;
        color: #1a1a1a;
        padding: 3px 6px;
        border: 1px solid #999;
        background: #fafafa;
        vertical-align: top;
        text-align: center;
    }
    .ujp-period-section {
        margin-top: 4px;
        text-align: right;
    }
    .ujp-period-label {
        font-size: 8px;
        color: #666;
    }
    .ujp-period-box {
        display: inline-block;
        border: 1px solid #999;
        padding: 3px 10px;
        font-size: 10px;
        min-width: 80px;
        text-align: center;
        background: #fafafa;
    }
</style>

{{-- Main Header Bar --}}
<table class="ujp-header-bar ujp-header-table" cellpadding="0" cellspacing="0">
    <tr>
        <td class="ujp-logo-cell">
            <p class="ujp-logo-text">
                Република Северна Македонија<br>
                Министерство за финансии<br>
                <strong>УПРАВА ЗА ЈАВНИ ПРИХОДИ</strong>
            </p>
        </td>
        <td class="ujp-title-cell">
            <p class="ujp-form-title">{{ $formTitle }}</p>
            @if(!empty($formSubtitle))
                <p class="ujp-form-subtitle">{{ $formSubtitle }}</p>
            @endif
        </td>
        <td class="ujp-code-cell">
            <span class="ujp-code-badge">{{ $formCode }}</span>
        </td>
    </tr>
</table>

@if(!empty($sluzhbenVesnik))
    <p class="ujp-vesnik-note">* {{ $sluzhbenVesnik }}</p>
@endif

{{-- Company Info Section --}}
<div class="ujp-info-section">
    <table class="ujp-info-table" cellpadding="0" cellspacing="0">
        <tr>
            <td class="ujp-info-label">Единствен даночен број</td>
            <td class="ujp-info-value-box" style="width: 28%;">
                {{ $company->vat_number ?? $company->vat_id ?? '-' }}
            </td>
            <td style="width: 5%;"></td>
            <td class="ujp-info-label" style="width: 15%; text-align: right;">Даночен период</td>
            <td style="width: 30%; text-align: right;">
                <table cellpadding="0" cellspacing="0" style="float: right;">
                    <tr>
                        <td style="font-size: 8px; color: #666; padding-right: 3px;">од</td>
                        <td class="ujp-info-value-box" style="min-width: 70px;">
                            {{ $periodStart ?? sprintf('01.01.%d', $year) }}
                        </td>
                        <td style="font-size: 8px; color: #666; padding: 0 3px;">до</td>
                        <td class="ujp-info-value-box" style="min-width: 70px;">
                            {{ $periodEnd ?? sprintf('31.12.%d', $year) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="ujp-info-label" style="padding-top: 6px;">Скратен назив<br>и адреса на вистинско<br>седиште за контакт</td>
            <td class="ujp-info-value" colspan="2" style="padding-top: 6px;">
                {{ $company->name }}
                @if($company->address)
                    @if($company->address->address_street_1)
                        <br>{{ $company->address->address_street_1 }}
                    @endif
                    @if($company->address->city)
                        , {{ $company->address->zip ?? '' }} {{ $company->address->city }}
                    @endif
                @endif
            </td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td class="ujp-info-label" style="padding-top: 4px;">Телефон</td>
            <td class="ujp-info-value" style="padding-top: 4px;">
                {{ ($company->address && $company->address->phone) ? $company->address->phone : '-' }}
            </td>
            <td></td>
            <td class="ujp-info-label" style="text-align: right; padding-top: 4px;">Исправка на {{ $formCode }}</td>
            <td style="padding-top: 4px; text-align: right;">
                <table cellpadding="0" cellspacing="0" style="float: right;">
                    <tr>
                        <td style="border: 1px solid #999; width: 16px; height: 14px; text-align: center; font-size: 10px;">
                            {{ ($isCorrection ?? false) ? '✓' : '' }}
                        </td>
                        <td style="font-size: 8px; color: #666; padding: 0 4px;">Број</td>
                        <td class="ujp-info-value-box" style="min-width: 50px;">
                            {{ $correctionNumber ?? '' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="ujp-info-label" style="padding-top: 4px;">е-пошта</td>
            <td class="ujp-info-value" colspan="4" style="padding-top: 4px;">
                {{ $company->email ?? '-' }}
            </td>
        </tr>
    </table>
</div>

{{-- CLAUDE-CHECKPOINT --}}
