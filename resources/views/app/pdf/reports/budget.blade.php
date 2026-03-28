<!DOCTYPE html>
<html lang="mk">

<head>
    <title>{{ $budget->name }}</title>
    <style type="text/css">
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 15px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .company-name { font-size: 14px; font-weight: bold; }
        .company-info { font-size: 9px; color: #666; margin-top: 3px; }
        .document-title { font-size: 16px; font-weight: bold; margin-top: 10px; text-transform: uppercase; }
        .meta-table { width: 100%; margin-bottom: 15px; }
        .meta-table td { padding: 3px 8px; font-size: 10px; }
        .meta-table .label { font-weight: bold; width: 150px; background: #f5f5f5; }
        table.lines { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table.lines th { background: #e8e8e8; padding: 5px 8px; text-align: left; font-size: 9px; font-weight: bold; border: 1px solid #ccc; }
        table.lines td { padding: 4px 8px; border: 1px solid #ddd; font-size: 9px; }
        table.lines td.amount { text-align: right; font-family: monospace; }
        table.lines tr.total-row { background: #f0f0f0; font-weight: bold; }
        table.lines tr.total-row td { border-top: 2px solid #333; }
        .signature-block { margin-top: 40px; width: 100%; }
        .signature-block td { padding: 30px 20px 0; text-align: center; width: 33%; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #333; padding-top: 5px; font-size: 9px; }
        .footer { margin-top: 20px; text-align: center; font-size: 8px; color: #999; border-top: 1px solid #ddd; padding-top: 5px; }
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 9px; font-weight: bold; }
        .status-draft { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-locked { background: #dbeafe; color: #1e40af; }
        .status-archived { background: #f3f4f6; color: #4b5563; }
        .variance-positive { color: #dc2626; }
        .variance-negative { color: #16a34a; }
        .section-title { font-size: 11px; font-weight: bold; margin: 15px 0 8px; padding: 3px 0; border-bottom: 1px solid #ccc; }
    </style>
</head>

<body>
    {{-- Company Header --}}
    <div class="header">
        @if($company)
        <div class="company-name">{{ $company->name }}</div>
        <div class="company-info">
            @if($company->address_street_1){{ $company->address_street_1 }}, @endif
            @if($company->address_city){{ $company->address_city }} @endif
            @if($company->address_zip){{ $company->address_zip }}@endif
            @if($company->tax_id) | ЕДБ: {{ $company->tax_id }}@endif
        </div>
        @endif
        <div class="document-title">БУЏЕТ</div>
        <div style="font-size: 12px; margin-top: 5px;">{{ $budget->name }}</div>
    </div>

    {{-- Budget Metadata --}}
    <table class="meta-table">
        <tr>
            <td class="label">Период:</td>
            <td>{{ $budget->start_date->format('d.m.Y') }} - {{ $budget->end_date->format('d.m.Y') }}</td>
            <td class="label">Тип на период:</td>
            <td>
                @switch($budget->period_type)
                    @case('monthly') Месечно @break
                    @case('quarterly') Квартално @break
                    @case('yearly') Годишно @break
                    @default {{ $budget->period_type }}
                @endswitch
            </td>
        </tr>
        <tr>
            <td class="label">Сценарио:</td>
            <td>
                @switch($budget->scenario)
                    @case('expected') Очекувано @break
                    @case('optimistic') Оптимистично @break
                    @case('pessimistic') Песимистично @break
                    @default {{ $budget->scenario }}
                @endswitch
            </td>
            <td class="label">Статус:</td>
            <td>
                <span class="status-badge status-{{ $budget->status }}">
                    @switch($budget->status)
                        @case('draft') Нацрт @break
                        @case('approved') Одобрен @break
                        @case('locked') Заклучен @break
                        @case('archived') Архивиран @break
                        @default {{ $budget->status }}
                    @endswitch
                </span>
            </td>
        </tr>
        @if($budget->costCenter)
        <tr>
            <td class="label">Центар на трошоци:</td>
            <td colspan="3">{{ $budget->costCenter->name }} @if($budget->costCenter->code)({{ $budget->costCenter->code }})@endif</td>
        </tr>
        @endif
    </table>

    {{-- Budget Lines --}}
    <div class="section-title">Буџетски ставки</div>
    <table class="lines">
        <thead>
            <tr>
                <th style="width: 30%;">Тип на конто</th>
                <th style="width: 25%;">Период</th>
                <th style="width: 20%;">Буџетиран износ</th>
                @if($comparison)
                <th style="width: 15%;">Остварено</th>
                <th style="width: 10%;">Отстапување</th>
                @endif
                @if(!$comparison)
                <th style="width: 25%;">Белешки</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @php
                $totalBudgeted = 0;
                $totalActual = 0;
                $accountTypeLabels = [
                    'OPERATING_REVENUE' => 'Оперативни приходи',
                    'NON_OPERATING_REVENUE' => 'Неоперативни приходи',
                    'OPERATING_EXPENSE' => 'Оперативни расходи',
                    'DIRECT_EXPENSE' => 'Директни трошоци',
                    'OVERHEAD_EXPENSE' => 'Општи трошоци',
                    'NON_OPERATING_EXPENSE' => 'Неоперативни расходи',
                    'CURRENT_ASSET' => 'Тековни средства',
                    'NON_CURRENT_ASSET' => 'Нетековни средства',
                    'CURRENT_LIABILITY' => 'Тековни обврски',
                    'NON_CURRENT_LIABILITY' => 'Нетековни обврски',
                ];
            @endphp
            @foreach($budget->lines->sortBy(['period_start', 'account_type']) as $line)
            @php
                $totalBudgeted += $line->amount;
                $actual = 0;
                $variance = 0;
                if ($comparison) {
                    $match = collect($comparison)->first(fn($c) => $c['account_type'] === $line->account_type && $c['period_start'] === $line->period_start?->toDateString());
                    $actual = $match['actual'] ?? 0;
                    $variance = $match['variance'] ?? 0;
                    $totalActual += $actual;
                }
            @endphp
            <tr>
                <td>{{ $accountTypeLabels[$line->account_type] ?? $line->account_type }}</td>
                <td>{{ $line->period_start?->format('d.m.Y') }} - {{ $line->period_end?->format('d.m.Y') }}</td>
                <td class="amount">{{ number_format($line->amount, 2, ',', '.') }}</td>
                @if($comparison)
                <td class="amount">{{ number_format($actual, 2, ',', '.') }}</td>
                <td class="amount {{ $variance > 0 ? 'variance-positive' : 'variance-negative' }}">
                    {{ number_format($variance, 2, ',', '.') }}
                </td>
                @endif
                @if(!$comparison)
                <td>{{ $line->notes ?? '-' }}</td>
                @endif
            </tr>
            @endforeach
            <tr class="total-row">
                <td>ВКУПНО</td>
                <td></td>
                <td class="amount">{{ number_format($totalBudgeted, 2, ',', '.') }}</td>
                @if($comparison)
                <td class="amount">{{ number_format($totalActual, 2, ',', '.') }}</td>
                <td class="amount {{ ($totalBudgeted - $totalActual) > 0 ? 'variance-negative' : 'variance-positive' }}">
                    {{ number_format($totalBudgeted - $totalActual, 2, ',', '.') }}
                </td>
                @endif
                @if(!$comparison)
                <td></td>
                @endif
            </tr>
        </tbody>
    </table>

    {{-- Signature Block --}}
    <table class="signature-block">
        <tr>
            <td>
                <div class="signature-line">
                    Изготвил<br>
                    @if($budget->createdBy){{ $budget->createdBy->name }}@endif
                </div>
            </td>
            <td>
                <div class="signature-line">
                    Одобрил<br>
                    @if($budget->approvedBy){{ $budget->approvedBy->name }}@endif
                    @if($budget->approved_at)<br><small>{{ $budget->approved_at->format('d.m.Y') }}</small>@endif
                </div>
            </td>
            <td>
                <div class="signature-line">
                    Директор / м.п.
                </div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Генерирано од Facturino | {{ now()->format('d.m.Y H:i') }}
    </div>
</body>

</html>
