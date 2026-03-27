<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Патен налог за превоз на стока {{ $order->travel_number }}</title>
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

        .report-header {
            width: 100%;
            margin-bottom: 2px;
        }

        .heading-text {
            font-weight: bold;
            font-size: 14px;
            color: #1a1a1a;
            width: 100%;
            text-align: left;
            padding: 0px;
            margin: 0px;
        }

        .heading-date {
            font-weight: normal;
            font-size: 9px;
            color: #666;
            width: 100%;
            text-align: right;
            padding: 0px;
            margin: 0px;
        }

        .sub-heading-text {
            font-weight: bold;
            font-size: 11px;
            color: #333;
            padding: 0px;
            margin: 0px;
            text-align: center;
        }

        .part-header {
            font-weight: bold;
            font-size: 10px;
            color: #fff;
            background-color: #1e40af;
            padding: 3px 6px;
            margin: 8px 0 4px 0;
            text-align: center;
            letter-spacing: 1px;
        }

        .section-title {
            font-weight: bold;
            font-size: 8px;
            color: #1a1a1a;
            margin: 4px 0 2px 0;
            padding: 2px 4px;
            background-color: #f3f4f6;
            border-bottom: 1px solid #d1d5db;
        }

        .data-table {
            width: 100%;
            margin-bottom: 3px;
            border: 1px solid #d1d5db;
        }

        .data-table th {
            background-color: #f9fafb;
            border: 1px solid #d1d5db;
            padding: 2px 3px;
            text-align: left;
            font-size: 6.5px;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
        }

        .data-table td {
            border: 1px solid #d1d5db;
            padding: 1px 3px;
            font-size: 7px;
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
            margin-bottom: 3px;
            border: 1px solid #e5e7eb;
        }

        .meta-table td {
            padding: 2px 4px;
            font-size: 7.5px;
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
            padding: 3px 5px;
            margin-bottom: 3px;
        }

        .employee-box .emp-label {
            font-size: 6px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 1px;
        }

        .employee-box .emp-name {
            font-size: 9px;
            font-weight: bold;
            color: #1f2937;
        }

        .employee-box .emp-detail {
            font-size: 7px;
            color: #4b5563;
        }

        .signature-table {
            width: 100%;
            margin-top: 12px;
        }

        .signature-table td {
            text-align: center;
            vertical-align: bottom;
            padding-top: 15px;
        }

        .signature-line {
            border-top: 1px solid #9ca3af;
            margin: 0 8px;
            padding-top: 2px;
            font-size: 6.5px;
            color: #6b7280;
        }

        .stamp-circle {
            width: 40px;
            height: 40px;
            border: 1px dashed #9ca3af;
            border-radius: 50%;
            margin: 0 auto 2px auto;
            line-height: 40px;
            text-align: center;
            font-size: 6px;
            color: #9ca3af;
        }

        .divider {
            border-top: 2px dashed #d1d5db;
            margin: 6px 0;
        }

        .recap-half {
            width: 49%;
            vertical-align: top;
        }
    </style>
</head>

<body>

    {{-- ============================================================ --}}
    {{-- СТРАНИЦА 1: ПАТЕН НАЛОГ ЗА ПРЕВОЗ НА СТОКА                  --}}
    {{-- ============================================================ --}}

    {{-- Company Header --}}
    @include('app.pdf.reports._company-header')

    {{-- Title Banner --}}
    <p class="part-header">ПАТЕН НАЛОГ ЗА ПРЕВОЗ НА СТОКА</p>

    {{-- Order Meta --}}
    <table class="meta-table">
        <tr>
            <td class="meta-label">Број на налог:</td>
            <td class="meta-value">{{ $order->travel_number }}</td>
            <td class="meta-label">Датум на издавање:</td>
            <td class="meta-value">{{ $order->created_at ? $order->created_at->format('d.m.Y') : now()->format('d.m.Y') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Начин на превоз:</td>
            <td class="meta-value" colspan="3">
                @if($order->transport_mode === 'public')
                    Јавен (комерцијален транспорт)
                @else
                    Сопствени потреби (сопствено возило)
                @endif
            </td>
        </tr>
    </table>

    {{-- Company Info --}}
    <p class="section-title">ПОДАТОЦИ ЗА ПРЕВОЗНИКОТ</p>
    <table class="meta-table">
        <tr>
            <td class="meta-label">Назив на фирма:</td>
            <td class="meta-value" colspan="3">{{ $company->name }}</td>
        </tr>
        @if($company->address)
            <tr>
                <td class="meta-label">Адреса:</td>
                <td class="meta-value" colspan="3">
                    {{ $company->address->address_street_1 ?? '' }}{{ ($company->address->address_street_1 && ($company->address->city || $company->address->zip)) ? ', ' : '' }}{{ $company->address->zip ?? '' }} {{ $company->address->city ?? '' }}
                </td>
            </tr>
        @endif
        @php
            $edb = $company->vat_id ?? ($company->settings['tax_number'] ?? null);
        @endphp
        @if($edb)
            <tr>
                <td class="meta-label">ЕДБ (даночен број):</td>
                <td class="meta-value" colspan="3">{{ $edb }}</td>
            </tr>
        @endif
    </table>

    {{-- Vehicles --}}
    @if($order->vehicles->count() > 0)
        <p class="section-title">ВОЗИЛА</p>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 20%;">Вид</th>
                    <th style="width: 25%;">Марка</th>
                    <th style="width: 25%;">Регистарски број</th>
                    <th class="text-right" style="width: 30%;">Носивост (тони)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->vehicles as $vehicle)
                    <tr>
                        <td>
                            @switch($vehicle->vehicle_type)
                                @case('truck') Камион @break
                                @case('trailer') Приколка @break
                                @case('van') Комбе @break
                                @default {{ $vehicle->vehicle_type ?? '-' }}
                            @endswitch
                        </td>
                        <td>{{ $vehicle->make }} {{ $vehicle->model }}</td>
                        <td>{{ $vehicle->registration_plate }}</td>
                        <td class="text-right">{{ $vehicle->capacity_tonnes ? number_format($vehicle->capacity_tonnes, 2) : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Driver / Crew --}}
    <p class="section-title">ВОЗАЧ / ЕКИПАЖ</p>
    @php
        $driver = $order->crew->firstWhere('role', 'driver');
        $driverName = $driver ? $driver->name : (($employee ? ($employee->first_name . ' ' . $employee->last_name) : '-'));
    @endphp
    <table class="meta-table">
        <tr>
            <td class="meta-label">Возач:</td>
            <td class="meta-value" colspan="3">{{ $driverName }}</td>
        </tr>
    </table>

    @if($order->crew->count() > 0)
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 25%;">Име и презиме</th>
                    <th style="width: 18%;">Улога</th>
                    <th style="width: 20%;">Бр. на возачка дозвола</th>
                    <th style="width: 17%;">Категорија</th>
                    <th style="width: 20%;">CPC број</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->crew as $member)
                    <tr>
                        <td>{{ $member->name }}</td>
                        <td>
                            @switch($member->role)
                                @case('driver') Возач @break
                                @case('co_driver') Совозач @break
                                @case('helper') Помошник @break
                                @default {{ $member->role ?? '-' }}
                            @endswitch
                        </td>
                        <td>{{ $member->license_number ?? '-' }}</td>
                        <td>{{ $member->license_category ?? '-' }}</td>
                        <td>{{ $member->cpc_number ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Route --}}
    @if($order->segments->count() > 0)
        <p class="section-title">РЕЛАЦИЈА / РУТА</p>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 14%;">Од</th>
                    <th style="width: 14%;">До</th>
                    <th style="width: 16%;">Датум/час поаѓање</th>
                    <th style="width: 16%;">Датум/час пристигнување</th>
                    <th style="width: 12%;">Држава</th>
                    <th style="width: 12%;">CMR</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->segments as $index => $seg)
                    @php
                        $segCmr = $order->cargo->where('travel_segment_id', $seg->id)->pluck('cmr_number')->filter()->implode(', ');
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $seg->from_city }}</td>
                        <td>{{ $seg->to_city }}</td>
                        <td>{{ \Carbon\Carbon::parse($seg->departure_at)->format('d.m.Y H:i') }}</td>
                        <td>{{ \Carbon\Carbon::parse($seg->arrival_at)->format('d.m.Y H:i') }}</td>
                        <td>{{ $seg->country_name ?? $seg->country_code ?? '-' }}</td>
                        <td>{{ $segCmr ?: '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Cargo --}}
    @if($order->cargo->count() > 0)
        <p class="section-title">ТОВАР / СТОКА</p>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 10%;">CMR бр.</th>
                    <th style="width: 18%;">Испраќач</th>
                    <th style="width: 18%;">Примач</th>
                    <th style="width: 24%;">Опис на стока</th>
                    <th class="text-right" style="width: 10%;">Пакети</th>
                    <th class="text-right" style="width: 14%;">Тежина (кг)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->cargo as $item)
                    <tr>
                        <td>{{ $item->cmr_number ?? '-' }}</td>
                        <td>{{ $item->sender_name }}</td>
                        <td>{{ $item->receiver_name }}</td>
                        <td>{{ $item->goods_description }}</td>
                        <td class="text-right">{{ $item->packages_count ?? '-' }}</td>
                        <td class="text-right">{{ $item->gross_weight_kg ? number_format($item->gross_weight_kg, 2) : '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Odometer / Fuel --}}
    @php
        $primaryVehicle = $order->vehicles->first(fn($v) => $v->odometer_start !== null) ?? $order->vehicles->first();
    @endphp
    @if($primaryVehicle)
        <p class="section-title">КИЛОМЕТРАЖА И ГОРИВО</p>
        <table class="data-table">
            <thead>
                <tr>
                    <th colspan="3" class="text-center">Километар-состојба</th>
                    <th colspan="6" class="text-center">Гориво</th>
                </tr>
                <tr>
                    <th style="width: 11%;">Почетни км</th>
                    <th style="width: 11%;">Крајни км</th>
                    <th style="width: 11%;">Вкупно км</th>
                    <th style="width: 10%;">Тип гориво</th>
                    <th class="text-right" style="width: 10%;">Почет. (л)</th>
                    <th class="text-right" style="width: 10%;">Дополн. (л)</th>
                    <th class="text-right" style="width: 10%;">Краен (л)</th>
                    <th class="text-right" style="width: 10%;">Потрош. (л)</th>
                    <th class="text-right" style="width: 9%;">Норма (л)</th>
                    <th class="text-right" style="width: 8%;">Разлика</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $primaryVehicle->odometer_start !== null ? number_format($primaryVehicle->odometer_start, 0) : '-' }}</td>
                    <td>{{ $primaryVehicle->odometer_end !== null ? number_format($primaryVehicle->odometer_end, 0) : '-' }}</td>
                    <td class="text-bold">{{ $primaryVehicle->total_km !== null ? number_format($primaryVehicle->total_km, 0) : ($order->total_km ? number_format($order->total_km, 0) : '-') }}</td>
                    <td>
                        @switch($primaryVehicle->fuel_type)
                            @case('diesel') Дизел @break
                            @case('petrol') Бензин @break
                            @case('lpg') ТНГ @break
                            @case('electric') Електрично @break
                            @default {{ $primaryVehicle->fuel_type ?? '-' }}
                        @endswitch
                    </td>
                    <td class="text-right">{{ $primaryVehicle->fuel_start_liters !== null ? number_format($primaryVehicle->fuel_start_liters, 2) : '-' }}</td>
                    <td class="text-right">{{ $primaryVehicle->fuel_added_liters !== null ? number_format($primaryVehicle->fuel_added_liters, 2) : '-' }}</td>
                    <td class="text-right">{{ $primaryVehicle->fuel_end_liters !== null ? number_format($primaryVehicle->fuel_end_liters, 2) : '-' }}</td>
                    <td class="text-right text-bold">{{ $primaryVehicle->fuel_consumed !== null ? number_format($primaryVehicle->fuel_consumed, 2) : '-' }}</td>
                    <td class="text-right">{{ $primaryVehicle->norm_consumption !== null ? number_format($primaryVehicle->norm_consumption, 2) : '-' }}</td>
                    <td class="text-right" style="{{ $primaryVehicle->fuel_variance !== null && $primaryVehicle->fuel_variance > 0 ? 'color: #dc2626;' : 'color: #16a34a;' }}">
                        {{ $primaryVehicle->fuel_variance !== null ? number_format($primaryVehicle->fuel_variance, 2) : '-' }}
                    </td>
                </tr>
            </tbody>
        </table>
    @endif

    {{-- Authorization Signatures --}}
    <table class="signature-table">
        <tr>
            <td style="width: 35%;">
                <div class="signature-line">
                    Овластено лице
                    @if($order->approvedByUser)
                        <br/><span style="color: #1f2937; font-weight: bold;">{{ $order->approvedByUser->name }}</span>
                    @endif
                </div>
            </td>
            <td style="width: 30%;">
                <div class="stamp-circle">М.П.</div>
            </td>
            <td style="width: 35%;">
                <div class="signature-line">
                    Возач
                    <br/><span style="color: #1f2937; font-weight: bold;">{{ $driverName }}</span>
                </div>
            </td>
        </tr>
    </table>

    {{-- ============================================================ --}}
    {{-- РАЗДЕЛНА ЛИНИЈА                                              --}}
    {{-- ============================================================ --}}
    <div class="divider"></div>

    {{-- ============================================================ --}}
    {{-- СТРАНИЦА 2: РЕКАПИТУЛАР ЗА РАЗДОЛЖУВАЊЕ НА ПАТЕН НАЛОГ      --}}
    {{-- ============================================================ --}}
    <p class="part-header">РЕКАПИТУЛАР ЗА РАЗДОЛЖУВАЊЕ НА ПАТЕН НАЛОГ</p>

    {{-- Two-column layout: expenses left, per-diem right --}}
    <table style="width: 100%; margin-bottom: 3px;">
        <tr>
            {{-- LEFT: Expense Recap --}}
            <td class="recap-half" style="padding-right: 4px;">
                <p class="section-title">ПРЕГЛЕД НА ТРОШОЦИ</p>
                @php
                    $expServices = $order->expenses->where('gl_account_code', '449');
                    $expFuel = $order->expenses->where('gl_account_code', '403');
                    $expForwarding = $order->expenses->where('gl_account_code', '419');
                    $totalServices = $expServices->sum('amount');
                    $totalFuelExp = $expFuel->sum('amount');
                    $totalForwarding = $expForwarding->sum('amount');
                @endphp
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 55%;">Опис</th>
                            <th class="text-right" style="width: 25%;">Износ (ден.)</th>
                            <th class="text-center" style="width: 20%;">Конто</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Вкупно поминати километри</td>
                            <td class="text-right">{{ $order->total_km ? number_format($order->total_km, 0) . ' км' : '-' }}</td>
                            <td class="text-center">-</td>
                        </tr>
                        <tr>
                            <td>Останати услуги</td>
                            <td class="text-right">{{ number_format($totalServices / 100, 2) }}</td>
                            <td class="text-center">449</td>
                        </tr>
                        <tr>
                            <td>Потрошено гориво</td>
                            <td class="text-right">{{ number_format($totalFuelExp / 100, 2) }}</td>
                            <td class="text-center">403</td>
                        </tr>
                        <tr>
                            <td>Шпедитерски услуги</td>
                            <td class="text-right">{{ number_format($totalForwarding / 100, 2) }}</td>
                            <td class="text-center">419</td>
                        </tr>
                        <tr>
                            <td>Патарини</td>
                            <td class="text-right">{{ number_format($order->total_toll_cost / 100, 2) }}</td>
                            <td class="text-center">449</td>
                        </tr>
                        <tr>
                            <td>Пресметани дневници</td>
                            <td class="text-right">{{ number_format($order->total_per_diem / 100, 2) }}</td>
                            <td class="text-center">440</td>
                        </tr>
                    </tbody>
                </table>
            </td>

            {{-- RIGHT: Per-Diem Table --}}
            <td class="recap-half" style="padding-left: 4px;">
                <p class="section-title">ПРЕСМЕТКА НА ДНЕВНИЦИ</p>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width: 14%;">А (почеток)</th>
                            <th style="width: 14%;">Б (крај)</th>
                            <th class="text-right" style="width: 7%;">Часови</th>
                            <th class="text-right" style="width: 7%;">Денови</th>
                            <th style="width: 12%;">Место</th>
                            <th style="width: 10%;">Држава</th>
                            <th style="width: 8%;">CMR бр.</th>
                            <th class="text-right" style="width: 10%;">Дневница</th>
                            <th class="text-right" style="width: 10%;">Вкупно</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $totalPerDiemCalc = 0; @endphp
                        @foreach($order->segments as $seg)
                            @php
                                $segDep = \Carbon\Carbon::parse($seg->departure_at);
                                $segArr = \Carbon\Carbon::parse($seg->arrival_at);
                                $segHours = $segDep->diffInHours($segArr);
                                $segDays = $seg->per_diem_days ?? 0;
                                $segAmount = ($seg->per_diem_amount ?? 0) / 100;
                                $totalPerDiemCalc += $seg->per_diem_amount ?? 0;
                                $segCmrs = $order->cargo->where('travel_segment_id', $seg->id)->pluck('cmr_number')->filter()->implode(', ');
                            @endphp
                            <tr>
                                <td style="font-size: 6px;">{{ $segDep->format('d.m.Y H:i') }}</td>
                                <td style="font-size: 6px;">{{ $segArr->format('d.m.Y H:i') }}</td>
                                <td class="text-right">{{ $segHours }}</td>
                                <td class="text-right">{{ number_format($segDays, 1) }}</td>
                                <td>{{ $seg->to_city }}</td>
                                <td>{{ $seg->country_name ?? $seg->country_code ?? '-' }}</td>
                                <td>{{ $segCmrs ?: '-' }}</td>
                                <td class="text-right">{{ $seg->per_diem_rate ? number_format($seg->per_diem_rate, 2) : '-' }}</td>
                                <td class="text-right text-bold">{{ number_format($segAmount, 2) }}</td>
                            </tr>
                        @endforeach
                        <tr class="total-row">
                            <td colspan="8" class="text-right">Вкупно дневници:</td>
                            <td class="text-right">{{ number_format($order->total_per_diem / 100, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>

    {{-- Financial Summary --}}
    <p class="section-title">ФИНАНСИСКА ПРЕСМЕТКА</p>
    @php
        $grandTotal = $order->grand_total ?? 0;
        $advance = $order->advance_amount ?? 0;
        $reimbursement = $order->reimbursement_amount ?? 0;
    @endphp
    <table class="data-table">
        <tbody>
            <tr>
                <td style="width: 60%;">Вкупно дневници (конто 440):</td>
                <td class="text-right text-bold" style="width: 40%;">{{ number_format($order->total_per_diem / 100, 2) }} ден.</td>
            </tr>
            <tr>
                <td>Потрошено гориво (конто 403):</td>
                <td class="text-right text-bold">{{ number_format($totalFuelExp / 100, 2) }} ден.</td>
            </tr>
            <tr>
                <td>Патарини (конто 449):</td>
                <td class="text-right text-bold">{{ number_format($order->total_toll_cost / 100, 2) }} ден.</td>
            </tr>
            <tr>
                <td>Шпедитерски услуги (конто 419):</td>
                <td class="text-right text-bold">{{ number_format($totalForwarding / 100, 2) }} ден.</td>
            </tr>
            <tr>
                <td>Останати услуги (конто 449):</td>
                <td class="text-right text-bold">{{ number_format($totalServices / 100, 2) }} ден.</td>
            </tr>
            <tr class="total-row" style="font-size: 9px;">
                <td class="text-bold">ВКУПНО ТРОШОЦИ:</td>
                <td class="text-right text-bold">{{ number_format($grandTotal / 100, 2) }} ден.</td>
            </tr>
            <tr>
                <td>Примен аванс:</td>
                <td class="text-right text-bold">{{ number_format($advance / 100, 2) }} ден.</td>
            </tr>
            <tr style="font-size: 9px; {{ $reimbursement >= 0 ? 'background-color: #f0fdf4;' : 'background-color: #fef2f2;' }}">
                <td class="text-bold">
                    @if($reimbursement >= 0)
                        ЗА ИСПЛАТА НА ВОЗАЧ:
                    @else
                        ЗА ВРАЌАЊЕ ОД ВОЗАЧ:
                    @endif
                </td>
                <td class="text-right text-bold" style="font-size: 11px;">{{ number_format(abs($reimbursement) / 100, 2) }} ден.</td>
            </tr>
        </tbody>
    </table>

    {{-- Legal citation --}}
    <p style="margin-top: 4px; font-size: 6px; color: #9ca3af;">
        Согласно Правилникот за формата и содржината на патниот налог (Сл.Весник на РМ бр.40 од 13.03.2015год.)
    </p>

    {{-- Settlement Signatures --}}
    <table class="signature-table">
        <tr>
            <td style="width: 25%;">
                <div class="signature-line">
                    Одговорно лице
                    @if($order->approvedByUser)
                        <br/><span style="color: #1f2937; font-weight: bold; font-size: 7px;">{{ $order->approvedByUser->name }}</span>
                    @endif
                </div>
            </td>
            <td style="width: 25%;">
                <div class="stamp-circle">М.П.</div>
            </td>
            <td style="width: 25%;">
                <div class="signature-line">
                    Возач
                    <br/><span style="color: #1f2937; font-weight: bold; font-size: 7px;">{{ $driverName }}</span>
                </div>
            </td>
            <td style="width: 25%;">
                <div class="signature-line">
                    Сметководител
                </div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <p style="text-align: center; font-size: 6px; color: #9ca3af; margin-top: 8px;">
        Генерирано од Facturino &mdash; app.facturino.mk
    </p>

</body>

</html>
{{-- CLAUDE-CHECKPOINT --}}
