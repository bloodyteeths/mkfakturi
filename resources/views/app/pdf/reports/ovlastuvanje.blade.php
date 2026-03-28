<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Овластување — {{ $user->name }}</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 11px;
            color: #333;
            margin: 15px;
        }

        .heading-text {
            font-weight: bold;
            font-size: 16px;
            color: #1a1a1a;
            text-align: center;
            margin: 30px 0 20px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .company-header {
            text-align: center;
            margin-bottom: 10px;
        }

        .company-name {
            font-weight: bold;
            font-size: 13px;
        }

        .company-detail {
            font-size: 9px;
            color: #555;
        }

        .body-text {
            font-size: 11px;
            line-height: 1.8;
            text-align: justify;
            margin: 15px 0;
        }

        .indent {
            text-indent: 40px;
        }

        .bold {
            font-weight: bold;
        }

        .signature-block {
            margin-top: 60px;
            width: 100%;
        }

        .signature-block td {
            vertical-align: top;
            padding: 5px 10px;
            font-size: 10px;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            width: 200px;
            display: inline-block;
            margin-top: 40px;
        }

        .footer-note {
            font-size: 8px;
            color: #888;
            text-align: center;
            margin-top: 50px;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }

        .seal-area {
            text-align: center;
            margin-top: 20px;
            font-size: 9px;
            color: #999;
        }
    </style>
</head>

<body>
    <div class="company-header">
        <div class="company-name">{{ $company->name ?? '' }}</div>
        <div class="company-detail">
            @if($company->address_street_1 ?? null){{ $company->address_street_1 }}, @endif
            {{ $city }}
        </div>
        <div class="company-detail">
            @if($company->tax_id ?? null)ЕМБС: {{ $company->tax_id }} @endif
            @if($company->vat_number ?? null) | ЕДБ: {{ $company->vat_number }}@endif
        </div>
    </div>

    <div class="heading-text">ОВЛАСТУВАЊЕ</div>

    <p class="body-text">
        Бр. _______ / {{ $date }}
    </p>

    <p class="body-text indent">
        Со ова овластување, <span class="bold">{{ $company->name ?? '' }}</span>,
        со седиште во {{ $city }},
        @if($company->tax_id ?? null)ЕМБС: {{ $company->tax_id }},@endif
        застапувано од <span class="bold">{{ $owner->name ?? '________________________' }}</span>
        (во понатамошниот текст: Давател на овластување),
    </p>

    <p class="body-text indent">
        го/ја овластува <span class="bold">{{ $user->name }}</span>,
        @if($user->email) е-пошта: {{ $user->email }},@endif
        (во понатамошниот текст: Овластено лице),
    </p>

    <p class="body-text indent">
        да ги извршува следниве дејства во име и за сметка на Давателот на овластување:
    </p>

    <p class="body-text" style="margin-left: 40px;">
        1. Пристап до сметководствениот систем Facturino;<br>
        2. Преглед, внесување и уредување на финансиски документи (фактури, понуди, трошоци);<br>
        3. Преглед на финансиски извештаи;<br>
        4. Комуникација со деловни партнери во име на компанијата;<br>
        5. Други дејства поврзани со книговодствените и финансиските работи на компанијата.
    </p>

    <p class="body-text indent">
        Ова овластување е валидно од денот на потпишувањето до негово писмено повлекување
        од страна на Давателот на овластување.
    </p>

    <p class="body-text indent">
        Овластеното лице е должно да ги чува деловните тајни и доверливите информации
        на компанијата согласно важечките прописи на Република Северна Македонија.
    </p>

    <table class="signature-block">
        <tr>
            <td style="width: 50%;">
                <p>Давател на овластување:</p>
                <p class="bold">{{ $owner->name ?? '________________________' }}</p>
                <br><br>
                <div class="signature-line"></div>
                <p style="font-size: 9px;">(потпис и печат)</p>
            </td>
            <td style="width: 50%;">
                <p>Овластено лице:</p>
                <p class="bold">{{ $user->name }}</p>
                <br><br>
                <div class="signature-line"></div>
                <p style="font-size: 9px;">(потпис)</p>
            </td>
        </tr>
    </table>

    <div class="seal-area">
        М.П.
    </div>

    <p class="body-text" style="margin-top: 30px;">
        Во {{ $city }}, на {{ $date }} год.
    </p>

    <div class="footer-note">
        Генерирано од Facturino — app.facturino.mk
    </div>
</body>

</html>
