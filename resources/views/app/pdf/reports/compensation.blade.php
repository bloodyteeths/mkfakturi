<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Компензација {{ $compensation->compensation_number }}</title>
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

        .sub-heading-text {
            font-weight: bold;
            font-size: 14px;
            color: #333;
            padding: 0px;
            margin: 0px;
            margin-top: 2px;
            text-align: center;
        }

        .section-title {
            font-weight: bold;
            font-size: 11px;
            color: #1a1a1a;
            margin: 12px 0 4px 0;
            padding: 4px 6px;
            background-color: #f3f4f6;
            border-bottom: 1px solid #d1d5db;
        }

        .data-table {
            width: 100%;
            margin-bottom: 8px;
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

        .summary-box {
            width: 100%;
            margin-top: 12px;
            border: 2px solid #16a34a;
            padding: 8px;
            background-color: #f0fdf4;
        }

        .summary-box .label {
            font-size: 10px;
            color: #15803d;
            font-weight: bold;
        }

        .summary-box .amount {
            font-size: 14px;
            color: #15803d;
            font-weight: bold;
        }

        .parties-table {
            width: 100%;
            margin-bottom: 10px;
        }

        .parties-table td {
            vertical-align: top;
            width: 50%;
            padding: 4px 8px;
        }

        .party-label {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .party-name {
            font-size: 11px;
            font-weight: bold;
            color: #1f2937;
        }

        .party-detail {
            font-size: 9px;
            color: #4b5563;
        }

        .signature-table {
            width: 100%;
            margin-top: 40px;
        }

        .signature-table td {
            width: 33%;
            text-align: center;
            vertical-align: bottom;
            padding-top: 40px;
        }

        .signature-line {
            border-top: 1px solid #9ca3af;
            margin: 0 20px;
            padding-top: 4px;
            font-size: 8px;
            color: #6b7280;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-draft {
            background-color: #f3f4f6;
            color: #6b7280;
        }

        .status-confirmed {
            background-color: #dcfce7;
            color: #15803d;
        }

        .status-cancelled {
            background-color: #fef2f2;
            color: #dc2626;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 8px;
        }

        .meta-table td {
            padding: 2px 0;
            font-size: 9px;
        }

        .meta-label {
            color: #6b7280;
            width: 140px;
        }

        .meta-value {
            color: #1f2937;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="sub-container">

        {{-- Company Header --}}
        @include('app.pdf.reports._company-header')

        {{-- Title --}}
        <p class="sub-heading-text">
            @if($compensation->type === 'bilateral')
                ДОГОВОРНА КОМПЕНЗАЦИЈА
            @else
                ЕДНОСТРАНА КОМПЕНЗАЦИЈА
            @endif
        </p>
        <p style="text-align: center; font-size: 9px; color: #666; margin: 2px 0 10px 0;">
            {{ $compensation->type === 'bilateral' ? 'Bilateral Compensation' : 'Unilateral Compensation' }}
        </p>

        {{-- Meta info --}}
        <table class="meta-table">
            <tr>
                <td class="meta-label">Број / Number:</td>
                <td class="meta-value">{{ $compensation->compensation_number }}</td>
            </tr>
            <tr>
                <td class="meta-label">Датум / Date:</td>
                <td class="meta-value">{{ $compensation->compensation_date->format('d.m.Y') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Статус / Status:</td>
                <td class="meta-value">
                    <span class="status-badge status-{{ $compensation->status }}">
                        @if($compensation->status === 'draft') Нацрт
                        @elseif($compensation->status === 'confirmed') Потврдена
                        @else Откажана
                        @endif
                    </span>
                </td>
            </tr>
        </table>

        {{-- Parties --}}
        <table class="parties-table">
            <tr>
                <td style="border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px;">
                    <p class="party-label">Побарувања од / Receivables from:</p>
                    @if($customer)
                        <p class="party-name">{{ $customer->name }}</p>
                        @if($customer->email)
                            <p class="party-detail">{{ $customer->email }}</p>
                        @endif
                        @if($customer->phone)
                            <p class="party-detail">Тел: {{ $customer->phone }}</p>
                        @endif
                        @if($customer->tax_identification_number ?? null)
                            <p class="party-detail">ЕДБ: {{ $customer->tax_identification_number }}</p>
                        @endif
                    @else
                        <p class="party-detail" style="color: #9ca3af;">—</p>
                    @endif
                </td>
                <td style="border: 1px solid #e5e7eb; border-radius: 4px; padding: 8px;">
                    <p class="party-label">Обврски кон / Payables to:</p>
                    @if($supplier)
                        <p class="party-name">{{ $supplier->name }}</p>
                        @if($supplier->email)
                            <p class="party-detail">{{ $supplier->email }}</p>
                        @endif
                        @if($supplier->phone)
                            <p class="party-detail">Тел: {{ $supplier->phone }}</p>
                        @endif
                        @if($supplier->tax_id ?? null)
                            <p class="party-detail">ЕДБ: {{ $supplier->tax_id }}</p>
                        @endif
                    @else
                        <p class="party-detail" style="color: #9ca3af;">—</p>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Receivables --}}
        @if($receivableItems->count() > 0)
            <p class="section-title">НАШИ ПОБАРУВАЊА / Our Receivables</p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 15%;">Тип</th>
                        <th style="width: 20%;">Број на документ</th>
                        <th style="width: 15%;">Датум</th>
                        <th class="text-right" style="width: 15%;">Вкупно</th>
                        <th class="text-right" style="width: 15%;">Компензирано</th>
                        <th class="text-right" style="width: 15%;">Остаток</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receivableItems as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                @if($item->document_type === 'invoice') Фактура
                                @elseif($item->document_type === 'credit_note') Кредит нота
                                @else {{ $item->document_type }}
                                @endif
                            </td>
                            <td>{{ $item->document_number }}</td>
                            <td>{{ $item->document_date ? $item->document_date->format('d.m.Y') : '-' }}</td>
                            <td class="text-right">{{ number_format($item->document_total / 100, 2) }}</td>
                            <td class="text-right text-bold">{{ number_format($item->amount_offset / 100, 2) }}</td>
                            <td class="text-right">{{ number_format($item->remaining_after / 100, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="4" class="text-right">Вкупно побарувања:</td>
                        <td class="text-right">{{ number_format($receivableItems->sum('document_total') / 100, 2) }}</td>
                        <td class="text-right">{{ number_format($receivableItems->sum('amount_offset') / 100, 2) }}</td>
                        <td class="text-right">{{ number_format($receivableItems->sum('remaining_after') / 100, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- Payables --}}
        @if($payableItems->count() > 0)
            <p class="section-title">НАШИ ОБВРСКИ / Our Payables</p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 15%;">Тип</th>
                        <th style="width: 20%;">Број на документ</th>
                        <th style="width: 15%;">Датум</th>
                        <th class="text-right" style="width: 15%;">Вкупно</th>
                        <th class="text-right" style="width: 15%;">Компензирано</th>
                        <th class="text-right" style="width: 15%;">Остаток</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payableItems as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                @if($item->document_type === 'bill') Фактура (влезна)
                                @else {{ $item->document_type }}
                                @endif
                            </td>
                            <td>{{ $item->document_number }}</td>
                            <td>{{ $item->document_date ? $item->document_date->format('d.m.Y') : '-' }}</td>
                            <td class="text-right">{{ number_format($item->document_total / 100, 2) }}</td>
                            <td class="text-right text-bold">{{ number_format($item->amount_offset / 100, 2) }}</td>
                            <td class="text-right">{{ number_format($item->remaining_after / 100, 2) }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="4" class="text-right">Вкупно обврски:</td>
                        <td class="text-right">{{ number_format($payableItems->sum('document_total') / 100, 2) }}</td>
                        <td class="text-right">{{ number_format($payableItems->sum('amount_offset') / 100, 2) }}</td>
                        <td class="text-right">{{ number_format($payableItems->sum('remaining_after') / 100, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- Summary --}}
        <div class="summary-box">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 60%;">
                        <span class="label">ИЗНОС НА КОМПЕНЗАЦИЈА / Offset Amount:</span>
                    </td>
                    <td class="text-right">
                        <span class="amount">{{ number_format($compensation->total_amount / 100, 2) }} ден.</span>
                    </td>
                </tr>
            </table>
            @if($compensation->receivables_remaining > 0)
                <p style="font-size: 8px; color: #6b7280; margin: 4px 0 0 0;">
                    Остаток побарување: {{ number_format($compensation->receivables_remaining / 100, 2) }} ден.
                </p>
            @endif
            @if($compensation->payables_remaining > 0)
                <p style="font-size: 8px; color: #6b7280; margin: 2px 0 0 0;">
                    Остаток обврска: {{ number_format($compensation->payables_remaining / 100, 2) }} ден.
                </p>
            @endif
        </div>

        {{-- Notes --}}
        @if($compensation->notes)
            <p style="margin-top: 10px; font-size: 9px; color: #4b5563;">
                <strong>Забелешки:</strong> {{ $compensation->notes }}
            </p>
        @endif

        {{-- Signature lines --}}
        <table class="signature-table">
            <tr>
                <td>
                    <div class="signature-line">
                        Изготвил / Prepared by
                    </div>
                </td>
                <td>
                    <div class="signature-line">
                        Потпис и печат (издавач)<br/>
                        Signature & stamp (issuer)
                    </div>
                </td>
                <td>
                    <div class="signature-line">
                        Потпис и печат (примач)<br/>
                        Signature & stamp (recipient)
                    </div>
                </td>
            </tr>
        </table>

        {{-- Footer --}}
        <p style="text-align: center; font-size: 7px; color: #9ca3af; margin-top: 20px;">
            Генерирано од Facturino &mdash; app.facturino.mk
        </p>

    </div>
</body>

</html>
{{-- CLAUDE-CHECKPOINT --}}
