<!DOCTYPE html>
<html>

<head>
    <title>Профактура - {{ $invoice->proforma_invoice_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style type="text/css">
        /* -- Base -- */
        body {
            font-family: "DejaVu Sans";
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        html {
            margin: 0;
            padding: 0;
        }

        table {
            border-collapse: collapse;
        }

        /* -- Header Section -- */
        .header-container {
            width: 100%;
            padding: 20px 30px 15px 30px;
            border-bottom: 2px solid #5851DB;
        }

        .header-logo {
            height: 55px;
            max-width: 180px;
        }

        .header-logo-text {
            font-size: 22px;
            font-weight: bold;
            color: #5851DB;
            margin: 0;
            padding: 0;
        }

        .document-title {
            font-size: 28px;
            font-weight: bold;
            color: #5851DB;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 0;
            text-align: right;
        }

        .document-number {
            font-size: 14px;
            color: #666;
            text-align: right;
            margin-top: 5px;
        }

        /* -- Content Wrapper -- */
        .content-wrapper {
            padding: 25px 30px;
        }

        /* -- Info Sections -- */
        .info-section {
            width: 100%;
            margin-bottom: 25px;
        }

        .info-box {
            vertical-align: top;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .info-box-label {
            font-size: 10px;
            font-weight: bold;
            color: #5851DB;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 5px;
        }

        .info-box-content {
            font-size: 11px;
            line-height: 16px;
            color: #444;
        }

        .info-box-content strong {
            color: #333;
        }

        /* -- Details Table -- */
        .details-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .details-table td {
            padding: 6px 0;
            vertical-align: top;
        }

        .details-label {
            font-size: 11px;
            color: #666;
            width: 140px;
        }

        .details-value {
            font-size: 11px;
            color: #333;
            font-weight: 500;
        }

        /* -- Items Table -- */
        .items-table {
            width: 100%;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .items-table thead th {
            background-color: #5851DB;
            color: #fff;
            font-size: 11px;
            font-weight: bold;
            padding: 10px 8px;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table thead th.text-center {
            text-align: center;
        }

        .items-table thead th.text-right {
            text-align: right;
        }

        .items-table tbody td {
            padding: 10px 8px;
            font-size: 11px;
            border-bottom: 1px solid #e8e8e8;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        .item-name {
            font-weight: 500;
            color: #333;
        }

        .item-description {
            font-size: 10px;
            color: #777;
            margin-top: 3px;
        }

        /* -- Totals Section -- */
        .totals-section {
            width: 100%;
            margin-top: 20px;
        }

        .totals-table {
            float: right;
            width: 280px;
        }

        .totals-table td {
            padding: 8px 12px;
            font-size: 11px;
        }

        .totals-table .label {
            color: #666;
            text-align: left;
        }

        .totals-table .value {
            color: #333;
            text-align: right;
            font-weight: 500;
        }

        .totals-table .total-row {
            background-color: #5851DB;
            color: #fff;
        }

        .totals-table .total-row .label,
        .totals-table .total-row .value {
            color: #fff;
            font-size: 13px;
            font-weight: bold;
            padding: 12px;
        }

        /* -- Notes Section -- */
        .notes-section {
            clear: both;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e8e8e8;
        }

        .notes-label {
            font-size: 12px;
            font-weight: bold;
            color: #5851DB;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .notes-content {
            font-size: 11px;
            color: #555;
            line-height: 16px;
        }

        /* -- Footer -- */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e8e8e8;
            font-size: 10px;
            color: #999;
            text-align: center;
        }

        /* -- Helpers -- */
        .text-left { text-align: left; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .clearfix { clear: both; }
    </style>
</head>

<body>
    <!-- Header -->
    <div class="header-container">
        <table width="100%">
            <tr>
                <td width="50%" style="vertical-align: middle;">
                    @if ($logo)
                        <img class="header-logo" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Лого">
                    @else
                        @if ($invoice->company)
                            <h1 class="header-logo-text">{{ $invoice->company->name }}</h1>
                        @endif
                    @endif
                </td>
                <td width="50%" style="vertical-align: middle;">
                    <div class="document-title">Профактура</div>
                    <div class="document-number">{{ $invoice->proforma_invoice_number }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Content -->
    <div class="content-wrapper">
        <!-- Company and Customer Info -->
        <table class="info-section">
            <tr>
                <td width="48%" class="info-box">
                    <div class="info-box-label">Издавач</div>
                    <div class="info-box-content">
                        @if ($invoice->company)
                            <strong>{{ $invoice->company->name }}</strong><br>
                        @endif
                        @if ($company_address)
                            {!! $company_address !!}
                        @endif
                        @if(isset($invoice->company->vat_id) && $invoice->company->vat_id)
                            <br><strong>ЕДБ:</strong> {{ $invoice->company->vat_id }}
                        @endif
                        @if(isset($invoice->company->tax_id) && $invoice->company->tax_id)
                            <br><strong>ЕМБС:</strong> {{ $invoice->company->tax_id }}
                        @endif
                    </div>
                </td>
                <td width="4%"></td>
                <td width="48%" class="info-box">
                    <div class="info-box-label">Примател</div>
                    <div class="info-box-content">
                        @if ($invoice->customer)
                            <strong>{{ $invoice->customer->name }}</strong><br>
                        @endif
                        @if ($billing_address)
                            {!! $billing_address !!}
                        @endif
                        @if(isset($invoice->customer->vat_number) && $invoice->customer->vat_number)
                            <br><strong>ЕДБ:</strong> {{ $invoice->customer->vat_number }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <!-- Document Details -->
        <table class="details-table">
            <tr>
                <td class="details-label">Датум на издавање:</td>
                <td class="details-value">{{ $invoice->formattedProformaInvoiceDate }}</td>
                <td width="30%"></td>
                <td class="details-label">Важи до:</td>
                <td class="details-value">{{ $invoice->formattedExpiryDate }}</td>
            </tr>
            @if($invoice->reference_number)
            <tr>
                <td class="details-label">Референтен број:</td>
                <td class="details-value">{{ $invoice->reference_number }}</td>
                <td width="30%"></td>
                <td></td>
                <td></td>
            </tr>
            @endif
        </table>

        @if ($shipping_address)
        <table style="width: 100%; margin-bottom: 15px;">
            <tr>
                <td width="48%"></td>
                <td width="4%"></td>
                <td width="48%" style="font-size: 11px;">
                    <strong style="color: #5851DB;">Адреса за испорака:</strong><br>
                    <span style="color: #555;">{!! $shipping_address !!}</span>
                </td>
            </tr>
        </table>
        @endif

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 5%;" class="text-center">#</th>
                    <th style="width: 40%;">Опис</th>
                    <th style="width: 12%;" class="text-center">Количина</th>
                    <th style="width: 15%;" class="text-right">Цена</th>
                    @if($invoice->discount_per_item === 'YES')
                    <th style="width: 10%;" class="text-right">Попуст</th>
                    @endif
                    <th style="width: 18%;" class="text-right">Износ</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <div class="item-name">{{ $item->name }}</div>
                        @if ($item->description)
                            <div class="item-description">{{ $item->description }}</div>
                        @endif
                    </td>
                    <td class="text-center">{{ $item->quantity }} {{ $item->unit_name ?? '' }}</td>
                    <td class="text-right">{{ format_money_pdf($item->price, $invoice->customer->currency) }}</td>
                    @if($invoice->discount_per_item === 'YES')
                    <td class="text-right">
                        @if($item->discount_type === 'fixed')
                            {{ format_money_pdf($item->discount_val, $invoice->customer->currency) }}
                        @else
                            {{ $item->discount }}%
                        @endif
                    </td>
                    @endif
                    <td class="text-right">{{ format_money_pdf($item->total, $invoice->customer->currency) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">Меѓузбир:</td>
                    <td class="value">{{ format_money_pdf($invoice->sub_total, $invoice->customer->currency) }}</td>
                </tr>

                @if ($invoice->discount > 0)
                <tr>
                    <td class="label">
                        Попуст
                        @if($invoice->discount_type === 'percentage')
                            ({{ $invoice->discount }}%)
                        @endif
                        :
                    </td>
                    <td class="value">- {{ format_money_pdf($invoice->discount_val, $invoice->customer->currency) }}</td>
                </tr>
                @endif

                @if ($invoice->tax_per_item === 'YES')
                    @foreach ($taxes as $tax)
                    <tr>
                        <td class="label">{{ $tax->taxType->name }} ({{ $tax->taxType->percent }}%):</td>
                        <td class="value">{{ format_money_pdf($tax->amount, $invoice->customer->currency) }}</td>
                    </tr>
                    @endforeach
                @else
                    @foreach ($invoice->taxes as $tax)
                    <tr>
                        <td class="label">{{ $tax->taxType->name }} ({{ $tax->taxType->percent }}%):</td>
                        <td class="value">{{ format_money_pdf($tax->amount, $invoice->customer->currency) }}</td>
                    </tr>
                    @endforeach
                @endif

                <tr class="total-row">
                    <td class="label">Вкупно:</td>
                    <td class="value">{{ format_money_pdf($invoice->total, $invoice->customer->currency) }}</td>
                </tr>
            </table>
        </div>

        <div class="clearfix"></div>

        <!-- Notes -->
        @if ($notes)
        <div class="notes-section">
            <div class="notes-label">Белешки</div>
            <div class="notes-content">{!! $notes !!}</div>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            Овој документ е профактура и не претставува даночен документ.
        </div>
    </div>
</body>

</html>
