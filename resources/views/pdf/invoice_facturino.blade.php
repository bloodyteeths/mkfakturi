<!DOCTYPE html>
<html>

<head>
    <title>@lang('pdf_invoice_label') - {{ $invoice->invoice_number }}</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style type="text/css">
        /* -- Base -- */
        body {
            font-family: "DejaVu Sans";
        }

        html {
            margin: 0px;
            padding: 0px;
            margin-top: 50px;
        }

        .text-center {
            text-align: center;
        }

        hr {
            margin: 0 30px 0 30px;
            color: rgba(0, 0, 0, 0.2);
            border: 0.5px solid #EAF1FB;
        }

        /* -- Header -- */

        .header-bottom-divider {
            color: rgba(0, 0, 0, 0.2);
            top: 90px;
            left: 0px;
            width: 100%;
            margin-left: 0%;
        }

        .header-container {
            position: absolute;
            width: 100%;
            height: 90px;
            left: 0px;
            top: -50px;
        }

        .header-logo {
            margin-top: 20px;
            padding-bottom: 20px;
            text-transform: capitalize;
            color: #7675ff;
        }

        .content-wrapper {
            display: block;
            margin-top: 0px;
            padding-top: 16px;
            padding-bottom: 20px;
        }

        .company-address-container {
            padding-top: 15px;
            padding-left: 30px;
            float: left;
            width: 30%;
            margin-bottom: 2px;
        }

        .company-address-container h1 {
            font-size: 15px;
            line-height: 22px;
            letter-spacing: 0.05em;
            margin-bottom: 0px;
            margin-top: 10px;
        }

        .company-address {
            margin-top: 16px;
            text-align: left;
            font-size: 12px;
            line-height: 15px;
            color: #595959;
            width: 280px;
            word-wrap: break-word;
        }

        .invoice-details-container {
            float: right;
            padding: 10px 30px 0 0;
            margin-top: 18px;
        }

        .attribute-label {
            font-size: 12px;
            line-height: 18px;
            padding-right: 40px;
            text-align: left;
            color: #55547A;
        }

        .attribute-value {
            font-size: 12px;
            line-height: 18px;
            text-align: right;
        }

        /* -- Shipping -- */

        .shipping-address-container {
            float: right;
            padding-left: 40px;
            width: 160px;
        }

        .shipping-address {
            font-size: 12px;
            line-height: 15px;
            color: #595959;
            padding: 45px 0px 0px 40px;
            margin: 0px;
            width: 160px;
            word-wrap: break-word;
        }

        /* -- Billing -- */

        .billing-address-container {
            padding-top: 50px;
            float: left;
            padding-left: 30px;
        }

        .billing-address-label {
            font-size: 12px;
            line-height: 18px;
            padding: 0px;
            margin-top: 27px;
            margin-bottom: 0px;
        }

        .billing-address-name {
            max-width: 160px;
            font-size: 15px;
            line-height: 22px;
            padding: 0px;
            margin: 0px;
        }

        .billing-address {
            font-size: 12px;
            line-height: 15px;
            color: #595959;
            padding: 45px 0px 0px 30px;
            margin: 0px;
            width: 160px;
            word-wrap: break-word;
        }

        /* -- Items Table -- */

        .items-table {
            margin-top: 35px;
            padding: 0px 30px 10px 30px;
            page-break-before: avoid;
            page-break-after: auto;
        }

        .items-table hr {
            height: 0.1px;
        }

        .item-table-heading {
            font-size: 13.5;
            text-align: center;
            color: rgba(0, 0, 0, 0.85);
            padding: 5px;
            color: #55547A;
        }

        tr.item-table-heading-row th {
            border-bottom: 0.620315px solid #E8E8E8;
            font-size: 12px;
            line-height: 18px;
        }

        tr.item-row td {
            font-size: 12px;
            line-height: 18px;
        }

        .item-cell {
            font-size: 13;
            text-align: center;
            padding: 5px;
            padding-top: 10px;
            color: #040405;
        }

        .item-description {
            color: #595959;
            font-size: 9px;
            line-height: 12px;
        }

        /* -- Total Display Table -- */

        .total-display-container {
            padding: 0 25px;
        }

        .total-display-table {
            border-top: none;
            page-break-inside: avoid;
            page-break-before: auto;
            page-break-after: auto;
            margin-top: 20px;
            float: right;
            width: auto;
        }

        .total-table-attribute-label {
            font-size: 13px;
            color: #55547A;
            text-align: left;
            padding-left: 10px;
        }

        .total-table-attribute-value {
            font-weight: bold;
            text-align: right;
            font-size: 13px;
            color: #040405;
            padding-right: 10px;
            padding-top: 2px;
            padding-bottom: 2px;
        }

        .total-border-left {
            border: 1px solid #E8E8E8 !important;
            border-right: 0px !important;
            padding-top: 0px;
            padding: 8px !important;
        }

        .total-border-right {
            border: 1px solid #E8E8E8 !important;
            border-left: 0px !important;
            padding-top: 0px;
            padding: 8px !important;
        }

        /* -- Notes -- */

        .notes {
            font-size: 12px;
            color: #595959;
            margin-top: 15px;
            margin-left: 30px;
            width: 442px;
            text-align: left;
            page-break-inside: avoid;
        }

        .notes-label {
            font-size: 15px;
            line-height: 22px;
            letter-spacing: 0.05em;
            color: #040405;
            width: 108px;
            white-space: nowrap;
            height: 19.87px;
            padding-bottom: 10px;
        }

        /* -- Helpers -- */

        .text-primary {
            color: #5851DB;
        }


        table .text-left {
            text-align: left;
        }

        table .text-right {
            text-align: right;
        }

        .border-0 {
            border: none;
        }

        .py-2 {
            padding-top: 2px;
            padding-bottom: 2px;
        }

        .py-8 {
            padding-top: 8px;
            padding-bottom: 8px;
        }

        .py-3 {
            padding: 3px 0;
        }

        .pr-20 {
            padding-right: 20px;
        }

        .pr-10 {
            padding-right: 10px;
        }

        .pl-20 {
            padding-left: 20px;
        }

        .pl-10 {
            padding-left: 10px;
        }

        .pl-0 {
            padding-left: 0;
        }

    </style>

</head>

<body>
    <div class="header-container">
        <table width="100%">
            <tr>
                <td class="text-center">
                    @if ($logo)
                        <img class="header-logo" style="height:50px" src="{{ \App\Space\ImageUtils::toBase64Src($logo) }}" alt="Company Logo">
                    @else
                        @if ($invoice->customer->company)
                            <h2 class="header-logo"> {{ $invoice->customer->company->name }}</h2>
                        @endif
                    @endif
                </td>
            </tr>
        </table>
        <hr class="header-bottom-divider" style="border: 0.620315px solid #E8E8E8;" />
    </div>


    <div class="content-wrapper">
        <div style="padding-top: 30px">
            <div class="company-address-container company-address">
                {!! $company_address !!}
            </div>

            <div class="invoice-details-container">
                <table>
                    <tr>
                        <td class="attribute-label">@lang('pdf_invoice_number')</td>
                        <td class="attribute-value"> &nbsp;{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="attribute-label">@lang('pdf_invoice_date')</td>
                        <td class="attribute-value"> &nbsp;{{ $invoice->formattedInvoiceDate }}</td>
                    </tr>
                    <tr>
                        <td class="attribute-label">@lang('pdf_invoice_due_date')</td>
                        <td class="attribute-value"> &nbsp;{{ $invoice->formattedDueDate }}</td>
                    </tr>
                </table>
            </div>

            <div style="clear: both;"></div>
        </div>

        <div class="billing-address-container billing-address">
            @if ($billing_address)
                <b>@lang('pdf_bill_to')</b> <br>

                {!! $billing_address !!}
            @endif
        </div>

        <div class="shipping-address-container shipping-address" @if ($billing_address !== '</br>') style="float:left;" @else style="display:block; float:left: padding-left: 0px;" @endif>
            @if ($shipping_address)
                <b>@lang('pdf_ship_to')</b> <br>

                {!! $shipping_address !!}
            @endif
        </div>

        <div style="position: relative; clear: both;">
            <!-- Inline Items Table for Facturino Template -->
            <table width="100%" class="items-table" cellspacing="0" border="0">
                <tr class="item-table-heading-row">
                    <th width="2%" class="pr-20 text-right item-table-heading">#</th>
                    <th width="40%" class="pl-0 text-left item-table-heading">@lang('pdf_items_label')</th>
                    @foreach($customFields as $field)
                        <th class="text-right item-table-heading">{{ $field->label }}</th>
                    @endforeach
                    <th class="pr-20 text-right item-table-heading">@lang('pdf_quantity_label')</th>
                    <th class="pr-20 text-right item-table-heading">@lang('pdf_price_label')</th>
                    @if($invoice->discount_per_item === 'YES')
                    <th class="pl-10 text-right item-table-heading">@lang('pdf_discount_label')</th>
                    @endif
                    @if($invoice->tax_per_item === 'YES')
                    <th class="pl-10 text-right item-table-heading">@lang('pdf_tax_label')</th>
                    @endif
                    <th class="text-right item-table-heading">@lang('pdf_amount_label')</th>
                </tr>
                @php
                    $index = 1
                @endphp
                @foreach ($invoice->items as $item)
                    <tr class="item-row">
                        <td
                            class="pr-20 text-right item-cell"
                            style="vertical-align: top;"
                        >
                            {{$index}}
                        </td>
                        <td
                            class="pl-0 text-left item-cell"
                            style="vertical-align: top;"
                        >
                            <span>{{ $item->name }}</span><br>
                            <span class="item-description">{!! nl2br(htmlspecialchars($item->description)) !!}</span>
                        </td>
                        @foreach($customFields as $field)
                            <td class="text-right item-cell" style="vertical-align: top;">
                                {{ $item->getCustomFieldValueBySlug($field->slug) }}
                            </td>
                        @endforeach
                        <td
                            class="pr-20 text-right item-cell"
                            style="vertical-align: top;"
                        >
                            {{$item->quantity}} @if($item->unit_name) {{$item->unit_name}} @endif
                        </td>
                        <td
                            class="pr-20 text-right item-cell"
                            style="vertical-align: top;"
                        >
                            {!! format_money_pdf($item->price, $invoice->customer->currency) !!}
                        </td>

                        @if($invoice->discount_per_item === 'YES')
                            <td
                                class="pl-10 text-right item-cell"
                                style="vertical-align: top;"
                            >
                                @if($item->discount_type === 'fixed')
                                        {!! format_money_pdf($item->discount_val, $invoice->customer->currency) !!}
                                    @endif
                                    @if($item->discount_type === 'percentage')
                                        {{$item->discount}}%
                                    @endif
                            </td>
                        @endif

                        @if($invoice->tax_per_item === 'YES')
                            <td
                                class="pl-10 text-right item-cell"
                                style="vertical-align: top;"
                            >
                                {!! format_money_pdf($item->tax, $invoice->customer->currency) !!}
                            </td>
                        @endif

                        <td
                            class="text-right item-cell"
                            style="vertical-align: top;"
                        >
                            {!! format_money_pdf($item->total, $invoice->customer->currency) !!}
                        </td>
                    </tr>
                    @php
                        $index += 1
                    @endphp
                @endforeach
            </table>

            <hr class="item-cell-table-hr">

            <div class="total-display-container">
                <table width="100%" cellspacing="0px" border="0" class="total-display-table @if(count($invoice->items) > 12) page-break @endif">
                    <tr>
                        <td class="border-0 total-table-attribute-label">@lang('pdf_subtotal')</td>
                        <td class="py-2 border-0 item-cell total-table-attribute-value">
                            {!! format_money_pdf($invoice->sub_total, $invoice->customer->currency) !!}
                        </td>
                    </tr>

                    @if($invoice->discount > 0)
                        @if ($invoice->discount_per_item === 'NO')
                            <tr>
                                <td class="border-0 total-table-attribute-label">
                                    @if($invoice->discount_type === 'fixed')
                                        @lang('pdf_discount_label')
                                    @endif
                                    @if($invoice->discount_type === 'percentage')
                                        @lang('pdf_discount_label') ({{$invoice->discount}}%)
                                    @endif
                                </td>
                                <td class="py-2 border-0 item-cell total-table-attribute-value" >
                                    @if($invoice->discount_type === 'fixed')
                                        {!! format_money_pdf($invoice->discount_val, $invoice->customer->currency) !!}
                                    @endif
                                    @if($invoice->discount_type === 'percentage')
                                        {!! format_money_pdf($invoice->discount_val, $invoice->customer->currency) !!}
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endif

                    @if ($invoice->tax_per_item === 'YES')
                        @foreach ($taxes as $tax)
                            <tr>
                                <td class="border-0 total-table-attribute-label">
                                    @if($tax->calculation_type === 'fixed')
                                        {{$tax->name }} ({!! format_money_pdf($tax->fixed_amount, $invoice->customer->currency) !!})
                                    @else
                                        {{$tax->name.' ('.$tax->percent.'%)'}}
                                    @endif
                                </td>
                                <td class="py-2 border-0 item-cell total-table-attribute-value">
                                    {!! format_money_pdf($tax->amount, $invoice->customer->currency) !!}
                                </td>
                            </tr>
                        @endforeach
                    @else
                        @foreach ($invoice->taxes as $tax)
                            <tr>
                                <td class="border-0 total-table-attribute-label">
                                    @if($tax->calculation_type === 'fixed')
                                        {{$tax->name }} ({!! format_money_pdf($tax->fixed_amount, $invoice->customer->currency) !!})
                                    @else
                                        {{$tax->name.' ('.$tax->percent.'%)'}}
                                    @endif
                                </td>
                                <td class="py-2 border-0 item-cell total-table-attribute-value">
                                    {!! format_money_pdf($tax->amount, $invoice->customer->currency) !!}
                                </td>
                            </tr>
                        @endforeach
                    @endif

                    <tr>
                        <td class="py-3"></td>
                        <td class="py-3"></td>
                    </tr>
                    <tr>
                        <td class="border-0 total-border-left total-table-attribute-label">
                            @lang('pdf_total')
                        </td>
                        <td
                            class="py-8 border-0 total-border-right item-cell total-table-attribute-value"
                            style="color: #5851D8"
                        >
                            {!! format_money_pdf($invoice->total, $invoice->customer->currency)!!}
                        </td>
                    </tr>

                    @if($invoice->paid_status === App\Models\Invoice::STATUS_PARTIALLY_PAID || $invoice->paid_status === App\Models\Invoice::STATUS_PAID)
                        <tr>
                            <td class="border-0 total-border-left total-table-attribute-label">
                                @lang('pdf_amount_paid')
                            </td>
                            <td class="py-8 border-0 total-border-right item-cell total-table-attribute-value">
                                {!! format_money_pdf($invoice->total - $invoice->due_amount, $invoice->customer->currency)!!}
                            </td>
                        </tr>
                        <tr>
                            <td class="border-0 total-border-left total-table-attribute-label">
                                @lang('pdf_amount_due')
                            </td>
                            <td class="py-8 border-0 total-border-right item-cell total-table-attribute-value" style="color: #5851D8">
                                {!! format_money_pdf($invoice->due_amount, $invoice->customer->currency)!!}
                            </td>
                        </tr>
                    @endif

                </table>
            </div>
        </div>

        <div class="notes">
            @if ($notes)
                <div class="notes-label">
                    @lang('pdf_notes')
                </div>

                {!! $notes !!}
            @endif
        </div>
    </div>
</body>

</html>

