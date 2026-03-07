<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Патен налог {{ $order->travel_number }}</title>
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
            border: 2px solid #1d4ed8;
            padding: 8px;
            background-color: #eff6ff;
        }

        .summary-box .label {
            font-size: 10px;
            color: #1e40af;
            font-weight: bold;
        }

        .summary-box .amount {
            font-size: 14px;
            color: #1e40af;
            font-weight: bold;
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
            width: 180px;
        }

        .meta-value {
            color: #1f2937;
            font-weight: bold;
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

        .status-pending_approval {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background-color: #dcfce7;
            color: #15803d;
        }

        .status-settled {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-rejected {
            background-color: #fef2f2;
            color: #dc2626;
        }

        .employee-box {
            width: 100%;
            border: 1px solid #e5e7eb;
            padding: 8px;
            margin-bottom: 10px;
        }

        .employee-box .emp-label {
            font-size: 8px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .employee-box .emp-name {
            font-size: 11px;
            font-weight: bold;
            color: #1f2937;
        }

        .employee-box .emp-detail {
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

        .reimbursement-positive {
            border-color: #16a34a;
            background-color: #f0fdf4;
        }

        .reimbursement-negative {
            border-color: #dc2626;
            background-color: #fef2f2;
        }
    </style>
</head>

<body>
    <div class="sub-container">

        {{-- Company Header --}}
        @include('app.pdf.reports._company-header')

        {{-- Title --}}
        <p class="sub-heading-text">
            @if($order->type === 'domestic')
                ПАТЕН НАЛОГ ЗА СЛУЖБЕНО ПАТУВАЊЕ ВО ЗЕМЈАТА
            @else
                ПАТЕН НАЛОГ ЗА СЛУЖБЕНО ПАТУВАЊЕ ВО СТРАНСТВО
            @endif
        </p>
        <p style="text-align: center; font-size: 9px; color: #666; margin: 2px 0 10px 0;">
            {{ $order->type === 'domestic' ? 'Domestic Travel Order' : 'Foreign Travel Order' }}
        </p>

        {{-- Meta info --}}
        <table class="meta-table">
            <tr>
                <td class="meta-label">Број на налог / Order No:</td>
                <td class="meta-value">{{ $order->travel_number }}</td>
            </tr>
            <tr>
                <td class="meta-label">Датум на издавање / Issue Date:</td>
                <td class="meta-value">{{ now()->format('d.m.Y') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Статус / Status:</td>
                <td class="meta-value">
                    <span class="status-badge status-{{ $order->status }}">
                        @if($order->status === 'draft') Нацрт / Draft
                        @elseif($order->status === 'pending_approval') Чека одобрување / Pending
                        @elseif($order->status === 'approved') Одобрен / Approved
                        @elseif($order->status === 'settled') Пресметан / Settled
                        @else Одбиен / Rejected
                        @endif
                    </span>
                </td>
            </tr>
            <tr>
                <td class="meta-label">Тип / Type:</td>
                <td class="meta-value">{{ $order->type === 'domestic' ? 'Домашен / Domestic' : 'Странски / Foreign' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Датум на заминување / Departure:</td>
                <td class="meta-value">{{ \Carbon\Carbon::parse($order->departure_date)->format('d.m.Y') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Датум на враќање / Return:</td>
                <td class="meta-value">{{ \Carbon\Carbon::parse($order->return_date)->format('d.m.Y') }}</td>
            </tr>
        </table>

        {{-- Employee --}}
        @if($employee)
            <div class="employee-box">
                <p class="emp-label">Вработен / Employee</p>
                <p class="emp-name">{{ $employee->first_name }} {{ $employee->last_name }}</p>
                @if($employee->position ?? null)
                    <p class="emp-detail">Позиција: {{ $employee->position }}</p>
                @endif
                @if($employee->embg ?? null)
                    <p class="emp-detail">ЕМБГ: {{ $employee->embg }}</p>
                @endif
            </div>
        @endif

        {{-- Purpose --}}
        <p class="section-title">ЦЕЛ НА ПАТУВАЊЕ / PURPOSE</p>
        <p style="font-size: 10px; padding: 4px 6px; margin-bottom: 8px;">{{ $order->purpose }}</p>

        {{-- Travel Segments --}}
        @if($order->segments->count() > 0)
            <p class="section-title">СЕГМЕНТИ НА ПАТУВАЊЕ / TRAVEL SEGMENTS</p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 14%;">Од / From</th>
                        <th style="width: 14%;">До / To</th>
                        <th style="width: 12%;">Превоз / Transport</th>
                        <th class="text-right" style="width: 10%;">Км / Km</th>
                        <th class="text-right" style="width: 8%;">Денови / Days</th>
                        <th class="text-right" style="width: 12%;">Дневница / Per Diem</th>
                        <th class="text-center" style="width: 8%;">Смештај</th>
                        <th class="text-center" style="width: 8%;">Оброци</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->segments as $index => $seg)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $seg->from_city }}</td>
                            <td>{{ $seg->to_city }}</td>
                            <td>
                                @switch($seg->transport_type)
                                    @case('car') Автомобил @break
                                    @case('bus') Автобус @break
                                    @case('train') Воз @break
                                    @case('plane') Авион @break
                                    @default Друго
                                @endswitch
                            </td>
                            <td class="text-right">{{ $seg->distance_km ? number_format($seg->distance_km, 1) : '-' }}</td>
                            <td class="text-right">{{ $seg->per_diem_days ?? '-' }}</td>
                            <td class="text-right text-bold">{{ number_format(($seg->per_diem_amount ?? 0) / 100, 2) }}</td>
                            <td class="text-center">{{ $seg->accommodation_provided ? 'Да' : 'Не' }}</td>
                            <td class="text-center">{{ $seg->meals_provided ? 'Да' : 'Не' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Expenses --}}
        @if($order->expenses->count() > 0)
            <p class="section-title">ТРОШОЦИ / EXPENSES</p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 20%;">Категорија / Category</th>
                        <th style="width: 50%;">Опис / Description</th>
                        <th class="text-right" style="width: 15%;">Износ / Amount</th>
                        <th style="width: 10%;">Валута</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->expenses as $index => $exp)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                @switch($exp->category)
                                    @case('transport') Превоз @break
                                    @case('accommodation') Смештај @break
                                    @case('meals') Оброци @break
                                    @default Друго
                                @endswitch
                            </td>
                            <td>{{ $exp->description }}</td>
                            <td class="text-right text-bold">{{ number_format($exp->amount / 100, 2) }}</td>
                            <td>{{ $exp->currency_code }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td colspan="3" class="text-right">Вкупно трошоци / Total Expenses:</td>
                        <td class="text-right">{{ number_format($order->total_expenses / 100, 2) }}</td>
                        <td>МКД</td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- Financial Summary --}}
        <p class="section-title">ФИНАНСИСКА ПРЕСМЕТКА / FINANCIAL SUMMARY</p>
        <table class="data-table">
            <tbody>
                <tr>
                    <td style="width: 60%;">Вкупно дневници / Total Per Diem:</td>
                    <td class="text-right text-bold">{{ number_format($order->total_per_diem / 100, 2) }} ден.</td>
                </tr>
                <tr>
                    <td>Вкупно километража / Total Mileage:</td>
                    <td class="text-right text-bold">{{ number_format($order->total_mileage_cost / 100, 2) }} ден.</td>
                </tr>
                <tr>
                    <td>Вкупно трошоци / Total Expenses:</td>
                    <td class="text-right text-bold">{{ number_format($order->total_expenses / 100, 2) }} ден.</td>
                </tr>
                <tr class="total-row">
                    <td class="text-bold">ВКУПНО / GRAND TOTAL:</td>
                    <td class="text-right text-bold" style="font-size: 11px;">{{ number_format($order->grand_total / 100, 2) }} ден.</td>
                </tr>
            </tbody>
        </table>

        {{-- Advance & Reimbursement --}}
        <div class="summary-box {{ $order->reimbursement_amount >= 0 ? 'reimbursement-positive' : 'reimbursement-negative' }}">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 60%;">
                        <span style="font-size: 9px; color: #6b7280;">Примен аванс / Advance Received:</span>
                    </td>
                    <td class="text-right">
                        <span style="font-size: 11px; font-weight: bold;">{{ number_format($order->advance_amount / 100, 2) }} ден.</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="label">
                            @if($order->reimbursement_amount >= 0)
                                ЗА ИСПЛАТА НА ВРАБОТЕН / Due to Employee:
                            @else
                                ЗА ВРАЌАЊЕ ОД ВРАБОТЕН / Due from Employee:
                            @endif
                        </span>
                    </td>
                    <td class="text-right">
                        <span class="amount">{{ number_format(abs($order->reimbursement_amount) / 100, 2) }} ден.</span>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Notes --}}
        @if($order->notes)
            <p style="margin-top: 10px; font-size: 9px; color: #4b5563;">
                <strong>Забелешки / Notes:</strong> {{ $order->notes }}
            </p>
        @endif

        {{-- Legal basis --}}
        <p style="margin-top: 12px; font-size: 7px; color: #9ca3af;">
            Согласно чл. 113 од Законот за работните односи и Општиот колективен договор за приватниот сектор.
            / Per Art. 113 of the Law on Labor Relations and the General Collective Agreement for the Private Sector.
            @if($order->type === 'domestic')
                Дневница: 8% од просечна месечна нето плата ({{ number_format(2670, 0) }} МКД/ден).
                Километража: 30% од цена на гориво ({{ number_format(15, 0) }} МКД/км).
            @endif
        </p>

        {{-- Signature lines --}}
        <table class="signature-table">
            <tr>
                <td>
                    <div class="signature-line">
                        Вработен / Employee
                    </div>
                </td>
                <td>
                    <div class="signature-line">
                        Одобрил / Authorized by
                    </div>
                </td>
                <td>
                    <div class="signature-line">
                        Сметководител / Accountant
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
