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
            font-size: 13px;
            color: #333;
            padding: 0px;
            margin: 0px;
            margin-top: 2px;
            text-align: center;
        }

        .part-header {
            font-weight: bold;
            font-size: 11px;
            color: #fff;
            background-color: #1e40af;
            padding: 5px 8px;
            margin: 14px 0 6px 0;
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
            padding: 3px 5px;
            text-align: left;
            font-size: 7px;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
        }

        .data-table td {
            border: 1px solid #d1d5db;
            padding: 2px 5px;
            font-size: 8px;
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
            margin-top: 8px;
            border: 2px solid #1d4ed8;
            padding: 6px;
            background-color: #eff6ff;
        }

        .summary-box .label {
            font-size: 9px;
            color: #1e40af;
            font-weight: bold;
        }

        .summary-box .amount {
            font-size: 13px;
            color: #1e40af;
            font-weight: bold;
        }

        .meta-table {
            width: 100%;
            margin-bottom: 6px;
            border: 1px solid #e5e7eb;
        }

        .meta-table td {
            padding: 3px 6px;
            font-size: 9px;
            border: 1px solid #e5e7eb;
        }

        .meta-label {
            color: #6b7280;
            width: 42%;
            background-color: #f9fafb;
        }

        .meta-value {
            color: #1f2937;
            font-weight: bold;
        }

        .employee-box {
            width: 100%;
            border: 1px solid #e5e7eb;
            padding: 6px;
            margin-bottom: 6px;
        }

        .employee-box .emp-label {
            font-size: 7px;
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
            font-size: 8px;
            color: #4b5563;
        }

        .signature-table {
            width: 100%;
            margin-top: 30px;
        }

        .signature-table td {
            text-align: center;
            vertical-align: bottom;
            padding-top: 30px;
        }

        .signature-line {
            border-top: 1px solid #9ca3af;
            margin: 0 12px;
            padding-top: 3px;
            font-size: 7px;
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

        .divider {
            border-top: 2px dashed #d1d5db;
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <div class="sub-container">

        {{-- Company Header --}}
        @include('app.pdf.reports._company-header')

        {{-- ============================================================ --}}
        {{-- ДЕЛ 1: НАЛОГ ЗА СЛУЖБЕНО ПАТУВАЊЕ (Pre-Travel Authorization) --}}
        {{-- ============================================================ --}}
        <p class="part-header">
            ДЕЛ 1: НАЛОГ ЗА СЛУЖБЕНО ПАТУВАЊЕ
            @if($order->type === 'foreign') ВО СТРАНСТВО @else ВО ЗЕМЈАТА @endif
        </p>

        {{-- Meta info --}}
        <table class="meta-table">
            <tr>
                <td class="meta-label">Број на налог / Order No:</td>
                <td class="meta-value">{{ $order->travel_number }}</td>
                <td class="meta-label">Датум на издавање / Date:</td>
                <td class="meta-value">{{ $order->created_at ? $order->created_at->format('d.m.Y') : now()->format('d.m.Y') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Тип на патување / Type:</td>
                <td class="meta-value">{{ $order->type === 'domestic' ? 'Домашен / Domestic' : 'Странски / Foreign' }}</td>
                <td class="meta-label">Статус / Status:</td>
                <td class="meta-value">
                    @if($order->status === 'draft') Нацрт
                    @elseif($order->status === 'pending_approval') Чека одобрување
                    @elseif($order->status === 'approved') Одобрен
                    @elseif($order->status === 'settled') Пресметан
                    @else Одбиен
                    @endif
                </td>
            </tr>
        </table>

        {{-- Employee --}}
        @if($employee)
            <div class="employee-box">
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50%; vertical-align: top;">
                            <p class="emp-label">Вработен / Employee</p>
                            <p class="emp-name">{{ $employee->first_name }} {{ $employee->last_name }}</p>
                            @if($employee->position ?? null)
                                <p class="emp-detail">Работно место: {{ $employee->position }}</p>
                            @endif
                        </td>
                        <td style="width: 50%; vertical-align: top;">
                            @if($employee->embg ?? null)
                                <p class="emp-detail">ЕМБГ: {{ $employee->embg }}</p>
                            @endif
                            @if($employee->bank_account ?? null)
                                <p class="emp-detail">Трансакциска сметка: {{ $employee->bank_account }}</p>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        {{-- Travel Details --}}
        <table class="meta-table">
            <tr>
                <td class="meta-label">Цел на патување / Purpose:</td>
                <td class="meta-value" colspan="3">{{ $order->purpose }}</td>
            </tr>
            <tr>
                <td class="meta-label">Датум и час на заминување / Departure:</td>
                <td class="meta-value">{{ \Carbon\Carbon::parse($order->departure_date)->format('d.m.Y H:i') }}</td>
                <td class="meta-label">Датум и час на враќање / Return:</td>
                <td class="meta-value">{{ \Carbon\Carbon::parse($order->return_date)->format('d.m.Y H:i') }}</td>
            </tr>
            @php
                $dep = \Carbon\Carbon::parse($order->departure_date);
                $ret = \Carbon\Carbon::parse($order->return_date);
                $totalHours = $dep->diffInHours($ret);
                $totalDays = floor($totalHours / 24);
                $remainingHours = $totalHours % 24;
            @endphp
            <tr>
                <td class="meta-label">Траење на патување / Duration:</td>
                <td class="meta-value" colspan="3">
                    @if($totalDays > 0){{ $totalDays }} ден(а) @endif{{ $remainingHours }} час(а)
                    (вкупно {{ $totalHours }} часа)
                </td>
            </tr>
            <tr>
                <td class="meta-label">Износ на аванс / Advance:</td>
                <td class="meta-value">{{ number_format($order->advance_amount / 100, 2) }} ден.</td>
                @if($order->cost_center_id)
                    <td class="meta-label">Трошковен центар / Cost Center:</td>
                    <td class="meta-value">{{ $order->cost_center_id }}</td>
                @else
                    <td class="meta-label">&nbsp;</td>
                    <td>&nbsp;</td>
                @endif
            </tr>
        </table>

        {{-- Route / Segments --}}
        @if($order->segments->count() > 0)
            <p class="section-title">РЕЛАЦИЈА / ROUTE</p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">#</th>
                        <th style="width: 12%;">Од / From</th>
                        <th style="width: 12%;">До / To</th>
                        <th style="width: 14%;">Заминување</th>
                        <th style="width: 14%;">Пристигнување</th>
                        <th style="width: 10%;">Превоз</th>
                        <th class="text-right" style="width: 8%;">Км</th>
                        <th class="text-center" style="width: 6%;">Смештај</th>
                        <th class="text-center" style="width: 6%;">Оброци</th>
                        @if($order->type === 'foreign')
                            <th style="width: 6%;">Држава</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->segments as $index => $seg)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $seg->from_city }}</td>
                            <td>{{ $seg->to_city }}</td>
                            <td>{{ \Carbon\Carbon::parse($seg->departure_at)->format('d.m.Y H:i') }}</td>
                            <td>{{ \Carbon\Carbon::parse($seg->arrival_at)->format('d.m.Y H:i') }}</td>
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
                            <td class="text-center">{{ $seg->accommodation_provided ? 'Да' : 'Не' }}</td>
                            <td class="text-center">{{ $seg->meals_provided ? 'Да' : 'Не' }}</td>
                            @if($order->type === 'foreign')
                                <td>{{ $seg->country_code ?? '-' }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Authorization Signature --}}
        <table class="signature-table">
            <tr>
                <td style="width: 40%;">
                    <div class="signature-line">
                        Овластено лице / Authorized by
                        @if($order->approved_by_user)
                            <br/><span style="color: #1f2937; font-weight: bold;">{{ $order->approved_by_user->name }}</span>
                        @endif
                    </div>
                </td>
                <td style="width: 20%;">
                    <div class="stamp-circle">М.П.</div>
                </td>
                <td style="width: 40%;">
                    <div class="signature-line">
                        Датум / Date: _______________
                    </div>
                </td>
            </tr>
        </table>

        {{-- ============================================================ --}}
        {{-- ДЕЛ 2: ПРЕСМЕТКА НА ПАТНИ ТРОШОЦИ (Post-Travel Settlement)  --}}
        {{-- ============================================================ --}}
        <div class="divider"></div>

        <p class="part-header">ДЕЛ 2: ПРЕСМЕТКА НА ПАТНИ ТРОШОЦИ</p>

        {{-- Per Diem Calculation --}}
        <p class="section-title">ПРЕСМЕТКА НА ДНЕВНИЦИ / PER DIEM CALCULATION</p>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 20%;">Релација / Route</th>
                    <th style="width: 16%;">Заминување</th>
                    <th style="width: 16%;">Пристигнување</th>
                    <th class="text-right" style="width: 8%;">Часа</th>
                    <th class="text-right" style="width: 8%;">Денови</th>
                    <th class="text-right" style="width: 12%;">Стапка (ден.)</th>
                    <th class="text-right" style="width: 12%;">Износ (ден.)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->segments as $index => $seg)
                    @php
                        $segDep = \Carbon\Carbon::parse($seg->departure_at);
                        $segArr = \Carbon\Carbon::parse($seg->arrival_at);
                        $segHours = $segDep->diffInHours($segArr);
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $seg->from_city }} &rarr; {{ $seg->to_city }}</td>
                        <td>{{ $segDep->format('d.m.Y H:i') }}</td>
                        <td>{{ $segArr->format('d.m.Y H:i') }}</td>
                        <td class="text-right">{{ $segHours }}</td>
                        <td class="text-right">{{ $seg->per_diem_days ?? '-' }}</td>
                        <td class="text-right">{{ $seg->per_diem_rate ? number_format($seg->per_diem_rate, 2) : '-' }}</td>
                        <td class="text-right text-bold">{{ number_format(($seg->per_diem_amount ?? 0) / 100, 2) }}</td>
                    </tr>
                    @if($seg->meals_provided)
                        <tr>
                            <td></td>
                            <td colspan="6" style="font-size: 7px; color: #6b7280;">&nbsp;&nbsp;↳ Намалување 50% за обезбедени оброци / 50% meal reduction</td>
                            <td></td>
                        </tr>
                    @endif
                @endforeach
                <tr class="total-row">
                    <td colspan="7" class="text-right">Вкупно дневници / Total Per Diem:</td>
                    <td class="text-right">{{ number_format($order->total_per_diem / 100, 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Mileage Calculation --}}
        @if($order->total_mileage_cost > 0)
            <p class="section-title">ПРЕСМЕТКА НА КИЛОМЕТРАЖА / MILEAGE CALCULATION</p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">#</th>
                        <th style="width: 30%;">Релација / Route</th>
                        <th class="text-right" style="width: 15%;">Км / Km</th>
                        <th class="text-right" style="width: 20%;">Стапка (ден./км)</th>
                        <th class="text-right" style="width: 20%;">Износ (ден.)</th>
                    </tr>
                </thead>
                <tbody>
                    @php $mileageIndex = 0; @endphp
                    @foreach($order->segments as $seg)
                        @if($seg->transport_type === 'car' && $seg->distance_km > 0)
                            @php $mileageIndex++; @endphp
                            <tr>
                                <td>{{ $mileageIndex }}</td>
                                <td>{{ $seg->from_city }} &rarr; {{ $seg->to_city }}</td>
                                <td class="text-right">{{ number_format($seg->distance_km, 1) }}</td>
                                <td class="text-right">15.00</td>
                                <td class="text-right text-bold">{{ number_format($seg->distance_km * 15, 2) }}</td>
                            </tr>
                        @endif
                    @endforeach
                    <tr class="total-row">
                        <td colspan="4" class="text-right">Вкупно километража / Total Mileage:</td>
                        <td class="text-right">{{ number_format($order->total_mileage_cost / 100, 2) }}</td>
                    </tr>
                </tbody>
            </table>
            <p style="font-size: 7px; color: #9ca3af; margin: 0 0 4px 0;">
                * Стапка: 30% од цената на горивото (чл. 35, Општ колективен договор) ≈ 15 МКД/км
            </p>
        @endif

        {{-- Expenses --}}
        @if($order->expenses->count() > 0)
            <p class="section-title">ДРУГИ ТРОШОЦИ / OTHER EXPENSES</p>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 4%;">#</th>
                        <th style="width: 20%;">Категорија</th>
                        <th style="width: 45%;">Опис / Description</th>
                        <th class="text-right" style="width: 18%;">Износ (ден.)</th>
                        <th style="width: 8%;">Валута</th>
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
                        <td colspan="3" class="text-right">Вкупно трошоци / Total:</td>
                        <td class="text-right">{{ number_format($order->total_expenses / 100, 2) }}</td>
                        <td></td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- Financial Summary --}}
        <p class="section-title">ФИНАНСИСКА ПРЕСМЕТКА / FINANCIAL SUMMARY</p>
        <table class="data-table">
            <tbody>
                <tr>
                    <td style="width: 55%;">1. Вкупно дневници / Total Per Diem:</td>
                    <td class="text-right text-bold" style="width: 45%;">{{ number_format($order->total_per_diem / 100, 2) }} ден.</td>
                </tr>
                <tr>
                    <td>2. Вкупно километража / Total Mileage:</td>
                    <td class="text-right text-bold">{{ number_format($order->total_mileage_cost / 100, 2) }} ден.</td>
                </tr>
                <tr>
                    <td>3. Вкупно други трошоци / Total Expenses:</td>
                    <td class="text-right text-bold">{{ number_format($order->total_expenses / 100, 2) }} ден.</td>
                </tr>
                <tr class="total-row" style="font-size: 10px;">
                    <td class="text-bold">4. ВКУПНО (1+2+3) / GRAND TOTAL:</td>
                    <td class="text-right text-bold">{{ number_format($order->grand_total / 100, 2) }} ден.</td>
                </tr>
                <tr>
                    <td>5. Примен аванс / Advance Received:</td>
                    <td class="text-right text-bold">{{ number_format($order->advance_amount / 100, 2) }} ден.</td>
                </tr>
                <tr style="font-size: 10px; {{ $order->reimbursement_amount >= 0 ? 'background-color: #f0fdf4;' : 'background-color: #fef2f2;' }}">
                    <td class="text-bold">
                        6.
                        @if($order->reimbursement_amount >= 0)
                            ЗА ИСПЛАТА НА ВРАБОТЕН (4-5):
                        @else
                            ЗА ВРАЌАЊЕ ОД ВРАБОТЕН (5-4):
                        @endif
                    </td>
                    <td class="text-right text-bold" style="font-size: 12px;">{{ number_format(abs($order->reimbursement_amount) / 100, 2) }} ден.</td>
                </tr>
            </tbody>
        </table>

        {{-- Notes --}}
        @if($order->notes)
            <p style="margin-top: 6px; font-size: 8px; color: #4b5563;">
                <strong>Забелешки / Notes:</strong> {{ $order->notes }}
            </p>
        @endif

        {{-- Legal basis --}}
        <p style="margin-top: 8px; font-size: 7px; color: #9ca3af;">
            Согласно чл. 113 од Законот за работните односи (Сл. Весник 145/2014) и чл. 35 од Општиот колективен договор за приватниот сектор.
            @if($order->type === 'domestic')
                Дневница: 8% од просечна месечна нето плата ({{ number_format(2670, 0) }} МКД/ден). Километража: 30% од цена на гориво ({{ number_format(15, 0) }} МКД/км).
            @else
                Дневница за странство: согласно Уредбата за издатоците за службени патувања и селидби во странство.
            @endif
            Рок за пресметка: 7 дена по завршување на патувањето.
        </p>

        {{-- Settlement Signature lines --}}
        <table class="signature-table">
            <tr>
                <td style="width: 33%;">
                    <div class="signature-line">
                        Вработен (патник)<br/>Employee (traveler)
                    </div>
                </td>
                <td style="width: 34%;">
                    <div class="signature-line">
                        Одобрил / Authorized by
                        @if($order->approved_by_user)
                            <br/><span style="color: #1f2937; font-weight: bold; font-size: 8px;">{{ $order->approved_by_user->name }}</span>
                        @endif
                    </div>
                </td>
                <td style="width: 33%;">
                    <div class="signature-line">
                        Сметководител<br/>Accountant
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
