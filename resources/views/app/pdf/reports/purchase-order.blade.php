<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Набавка {{ $po->po_number }}</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
            color: #333;
        }

        table {
            border-collapse: collapse;
        }

        .sub-container {
            padding: 0px 10px;
        }

        .report-header {
            width: 100%;
            margin-bottom: 5px;
        }

        .heading-text {
            font-weight: bold;
            font-size: 16px;
            color: #1a1a1a;
            width: 100%;
            text-align: left;
            padding: 0px;
            margin: 0px;
        }

        .heading-date {
            font-weight: normal;
            font-size: 10px;
            color: #666;
            width: 100%;
            text-align: right;
            padding: 0px;
            margin: 0px;
        }

        .part-header {
            font-weight: bold;
            font-size: 13px;
            color: #fff;
            background-color: #1e40af;
            padding: 6px 10px;
            margin: 12px 0 8px 0;
            text-align: center;
            letter-spacing: 1px;
        }

        .section-title {
            font-weight: bold;
            font-size: 10px;
            color: #1a1a1a;
            margin: 8px 0 4px 0;
            padding: 3px 6px;
            background-color: #f3f4f6;
            border-bottom: 1px solid #d1d5db;
        }

        .data-table {
            width: 100%;
            margin-bottom: 6px;
            border: 1px solid #d1d5db;
        }

        .data-table th {
            background-color: #f9fafb;
            border: 1px solid #d1d5db;
            padding: 4px 6px;
            text-align: left;
            font-size: 8px;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
        }

        .data-table td {
            border: 1px solid #d1d5db;
            padding: 3px 6px;
            font-size: 9px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .text-bold {
            font-weight: bold;
        }

        .total-row {
            background-color: #f0fdf4;
            font-weight: bold;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 6px;
            border: 1px solid #e5e7eb;
        }

        .meta-table td {
            padding: 4px 8px;
            font-size: 9px;
            border: 1px solid #e5e7eb;
        }

        .meta-label {
            color: #6b7280;
            width: 30%;
            background-color: #f9fafb;
        }

        .meta-value {
            color: #1f2937;
            font-weight: bold;
        }

        .summary-box {
            width: 100%;
            margin-top: 10px;
            border: 2px solid #1d4ed8;
            padding: 8px;
            background-color: #eff6ff;
        }

        .summary-box .label {
            font-size: 9px;
            color: #1e40af;
            font-weight: bold;
        }

        .summary-box .amount {
            font-size: 14px;
            color: #1e40af;
            font-weight: bold;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-draft { background-color: #f3f4f6; color: #374151; }
        .status-sent { background-color: #dbeafe; color: #1e40af; }
        .status-acknowledged { background-color: #e0e7ff; color: #3730a3; }
        .status-partially_received { background-color: #fef3c7; color: #92400e; }
        .status-fully_received { background-color: #d1fae5; color: #065f46; }
        .status-billed { background-color: #ede9fe; color: #5b21b6; }
        .status-closed { background-color: #e5e7eb; color: #1f2937; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }

        .signature-table {
            width: 100%;
            margin-top: 40px;
        }

        .signature-table td {
            text-align: center;
            vertical-align: bottom;
            padding-top: 30px;
        }

        .signature-line {
            border-top: 1px solid #9ca3af;
            margin: 0 15px;
            padding-top: 3px;
            font-size: 8px;
            color: #6b7280;
        }

        .stamp-circle {
            width: 50px;
            height: 50px;
            border: 1px dashed #9ca3af;
            border-radius: 50%;
            margin: 0 auto 4px auto;
            line-height: 50px;
            text-align: center;
            font-size: 7px;
            color: #9ca3af;
        }

        .notes-box {
            margin-top: 8px;
            padding: 6px 8px;
            background-color: #fefce8;
            border: 1px solid #fde68a;
            font-size: 9px;
            color: #713f12;
        }
    </style>
</head>

<body>
    <div class="sub-container">

        {{-- Company Header --}}
        @include('app.pdf.reports._company-header')

        {{-- Title --}}
        <p class="part-header">
            НАБАВКА / PURCHASE ORDER
        </p>

        {{-- PO Meta --}}
        <table class="meta-table">
            <tr>
                <td class="meta-label">Број на набавка / PO Number:</td>
                <td class="meta-value">{{ $po->po_number }}</td>
                <td class="meta-label">Датум / Date:</td>
                <td class="meta-value">{{ \Carbon\Carbon::parse($po->po_date)->format('d.m.Y') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Статус / Status:</td>
                <td class="meta-value">
                    <span class="status-badge status-{{ $po->status }}">
                        @switch($po->status)
                            @case('draft') Нацрт / Draft @break
                            @case('sent') Испратена / Sent @break
                            @case('acknowledged') Потврдена / Acknowledged @break
                            @case('partially_received') Делумно примена @break
                            @case('fully_received') Целосно примена @break
                            @case('billed') Фактурирана / Billed @break
                            @case('closed') Затворена / Closed @break
                            @case('cancelled') Откажана / Cancelled @break
                            @default {{ $po->status }}
                        @endswitch
                    </span>
                </td>
                <td class="meta-label">Очекувана испорака / Expected:</td>
                <td class="meta-value">
                    {{ $po->expected_delivery_date ? \Carbon\Carbon::parse($po->expected_delivery_date)->format('d.m.Y') : '-' }}
                </td>
            </tr>
        </table>

        {{-- Supplier Info --}}
        @if($po->supplier)
            <p class="section-title">ДОБАВУВАЧ / SUPPLIER</p>
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Име / Name:</td>
                    <td class="meta-value">{{ $po->supplier->name }}</td>
                    <td class="meta-label">Е-пошта / Email:</td>
                    <td class="meta-value">{{ $po->supplier->email ?? '-' }}</td>
                </tr>
                @if($po->supplier->phone || ($po->supplier->billingAddress ?? null))
                <tr>
                    @if($po->supplier->phone)
                        <td class="meta-label">Телефон / Phone:</td>
                        <td class="meta-value">{{ $po->supplier->phone }}</td>
                    @else
                        <td class="meta-label">&nbsp;</td>
                        <td>&nbsp;</td>
                    @endif
                    @if($po->supplier->billingAddress ?? null)
                        <td class="meta-label">Адреса / Address:</td>
                        <td class="meta-value">
                            {{ $po->supplier->billingAddress->address_street_1 ?? '' }}
                            {{ $po->supplier->billingAddress->city ?? '' }}
                        </td>
                    @else
                        <td class="meta-label">&nbsp;</td>
                        <td>&nbsp;</td>
                    @endif
                </tr>
                @endif
            </table>
        @endif

        {{-- Warehouse --}}
        @if($po->warehouse)
            <table class="meta-table" style="margin-top: 4px;">
                <tr>
                    <td class="meta-label">Магацин / Warehouse:</td>
                    <td class="meta-value">{{ $po->warehouse->name }}</td>
                </tr>
            </table>
        @endif

        {{-- Items --}}
        <p class="section-title">СТАВКИ / ITEMS</p>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 36%;">Ставка / Item</th>
                    <th class="text-right" style="width: 10%;">Кол. / Qty</th>
                    <th class="text-right" style="width: 10%;">Примено / Rcvd</th>
                    <th class="text-right" style="width: 13%;">Цена / Price</th>
                    <th class="text-right" style="width: 13%;">Данок / Tax</th>
                    <th class="text-right" style="width: 14%;">Вкупно / Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($po->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            {{ $item->name }}
                            @if($item->item && $item->item->sku)
                                <span style="color: #9ca3af; font-size: 7px;">({{ $item->item->sku }})</span>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                        <td class="text-right">
                            @if($item->received_quantity >= $item->quantity)
                                <span style="color: #059669;">{{ number_format($item->received_quantity, 2) }}</span>
                            @elseif($item->received_quantity > 0)
                                <span style="color: #d97706;">{{ number_format($item->received_quantity, 2) }}</span>
                            @else
                                {{ number_format($item->received_quantity, 2) }}
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($item->price / 100, 2) }}</td>
                        <td class="text-right">{{ number_format($item->tax / 100, 2) }}</td>
                        <td class="text-right text-bold">{{ number_format($item->total / 100, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="4"></td>
                    <td class="text-right" style="font-size: 8px;">Подвкупно / Subtotal:</td>
                    <td class="text-right" style="font-size: 8px;">ДДВ / Tax:</td>
                    <td class="text-right" style="font-size: 8px;">Вкупно / Total:</td>
                </tr>
                <tr class="total-row" style="font-size: 10px;">
                    <td colspan="4"></td>
                    <td class="text-right">{{ number_format($po->sub_total / 100, 2) }}</td>
                    <td class="text-right">{{ number_format($po->tax / 100, 2) }}</td>
                    <td class="text-right text-bold">{{ number_format($po->total / 100, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Goods Receipts --}}
        @if($po->goodsReceipts && $po->goodsReceipts->count() > 0)
            <p class="section-title">ПРИЕМНИЦИ / GOODS RECEIPTS ({{ $po->goodsReceipts->count() }})</p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 25%;">Број / Number</th>
                        <th style="width: 20%;">Датум / Date</th>
                        <th class="text-right" style="width: 15%;">Ставки / Items</th>
                        <th style="width: 35%;">Статус / Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($po->goodsReceipts as $index => $receipt)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $receipt->receipt_number }}</td>
                            <td>{{ \Carbon\Carbon::parse($receipt->receipt_date)->format('d.m.Y') }}</td>
                            <td class="text-right">{{ $receipt->items ? $receipt->items->count() : 0 }}</td>
                            <td>{{ ucfirst($receipt->status) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Linked Bill --}}
        @if($po->convertedBill)
            <p class="section-title">ПОВРЗАНА ФАКТУРА / LINKED BILL</p>
            <table class="meta-table">
                <tr>
                    <td class="meta-label">Број на фактура / Bill No:</td>
                    <td class="meta-value">{{ $po->convertedBill->bill_number }}</td>
                    <td class="meta-label">Износ / Amount:</td>
                    <td class="meta-value">{{ number_format($po->convertedBill->total / 100, 2) }}</td>
                </tr>
            </table>
        @endif

        {{-- Grand Total Box --}}
        <div class="summary-box">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 33%; vertical-align: top;">
                        <span class="label">Подвкупно / Subtotal</span><br/>
                        <span class="amount">{{ number_format($po->sub_total / 100, 2) }}</span>
                    </td>
                    <td style="width: 33%; vertical-align: top; text-align: center;">
                        <span class="label">ДДВ / Tax</span><br/>
                        <span class="amount">{{ number_format($po->tax / 100, 2) }}</span>
                    </td>
                    <td style="width: 34%; vertical-align: top; text-align: right;">
                        <span class="label">ВКУПНО / TOTAL</span><br/>
                        <span class="amount" style="font-size: 16px;">{{ number_format($po->total / 100, 2) }}</span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Notes --}}
        @if($po->notes)
            <div class="notes-box">
                <strong>Забелешки / Notes:</strong> {{ $po->notes }}
            </div>
        @endif

        {{-- Signatures --}}
        <table class="signature-table">
            <tr>
                <td style="width: 33%;">
                    <div class="signature-line">
                        Нарачал / Ordered By
                        @if($po->createdBy)
                            <br/><span style="color: #1f2937; font-weight: bold;">{{ $po->createdBy->name }}</span>
                        @endif
                    </div>
                </td>
                <td style="width: 34%;">
                    <div class="stamp-circle">М.П.</div>
                </td>
                <td style="width: 33%;">
                    <div class="signature-line">
                        Одобрил / Approved By
                    </div>
                </td>
            </tr>
        </table>

        {{-- Footer --}}
        <p style="text-align: center; font-size: 7px; color: #9ca3af; margin-top: 15px;">
            Генерирано од Facturino &mdash; app.facturino.mk
        </p>

    </div>
</body>

</html>
{{-- CLAUDE-CHECKPOINT --}}
