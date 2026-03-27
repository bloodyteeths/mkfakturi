<!DOCTYPE html>
<html lang="mk">
<head>
    <title>Книга на влезни фактури</title>
    <style type="text/css">
        body { font-family: "DejaVu Sans"; font-size: 8px; color: #333; margin: 10px; }
        table { border-collapse: collapse; width: 100%; }
        .heading { font-weight: bold; font-size: 14px; text-align: center; margin: 5px 0 2px 0; }
        .period { font-size: 9px; text-align: center; color: #555; margin: 0 0 8px 0; }
        .reg-table { border: 1px solid #999; }
        .reg-table th { background: #e2e8f0; border: 1px solid #999; padding: 3px 2px; font-size: 7px; font-weight: bold; text-align: center; color: #1a1a1a; }
        .reg-table td { border: 1px solid #ccc; padding: 2px 3px; font-size: 7px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
        .total-row { background: #e2e8f0; font-weight: bold; border-top: 2px solid #333; }
        .total-row td { font-size: 8px; padding: 4px 3px; }
        .rc-badge { background: #fed7d7; color: #c53030; padding: 0 2px; font-size: 6px; font-weight: bold; }
        .deductible { color: #276749; font-weight: bold; }
        .non-deductible { color: #c53030; }
        .rate-header { background: #edf2f7; }
        .rate-header th { font-size: 6px; border: 1px solid #999; padding: 2px; }
        .signature-section { margin-top: 25px; width: 100%; }
        .signature-label { font-size: 9px; color: #666; border-top: 1px solid #999; padding-top: 3px; width: 200px; text-align: center; }
    </style>
</head>
<body>
    @include('app.pdf.reports._company-header')

    <p class="heading">КНИГА НА ВЛЕЗНИ ФАКТУРИ</p>
    <p class="period">Период: {{ $from_date }} - {{ $to_date }}</p>

    <table class="reg-table">
        <thead>
            <tr>
                <th rowspan="2" style="width: 3%;">Р.бр.</th>
                <th rowspan="2" style="width: 7%;">ЕДБ</th>
                <th rowspan="2" style="width: 13%;">Назив и адреса</th>
                <th rowspan="2" style="width: 6%;">Бр. факт.</th>
                <th rowspan="2" style="width: 5%;">Датум</th>
                <th colspan="4" style="border-bottom: 1px solid #999;">Износ без ДДВ (основица)</th>
                <th colspan="3" style="border-bottom: 1px solid #999;">Износ на ДДВ</th>
                <th rowspan="2" style="width: 7%;">Вкупно<br>со ДДВ</th>
                <th rowspan="2" style="width: 5%;">Датум<br>плаќ.</th>
                <th rowspan="2" style="width: 4%;">Одбивка</th>
            </tr>
            <tr class="rate-header">
                <th style="width: 5%;">18%</th>
                <th style="width: 5%;">10%</th>
                <th style="width: 5%;">5%</th>
                <th style="width: 5%;">0%</th>
                <th style="width: 5%;">18%</th>
                <th style="width: 5%;">10%</th>
                <th style="width: 5%;">5%</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totals = ['base' => [18 => 0, 10 => 0, 5 => 0, 0 => 0], 'vat' => [18 => 0, 10 => 0, 5 => 0], 'grand' => 0];
                $deductibleCount = 0;
            @endphp

            @foreach($entries as $index => $entry)
            @php
                $isRc = $entry['is_reverse_charge'] ?? false;
                $byRate = $entry['by_rate'] ?? [];
                foreach ([18, 10, 5, 0] as $r) {
                    $totals['base'][$r] += $byRate[$r]['base'] ?? 0;
                }
                foreach ([18, 10, 5] as $r) {
                    $totals['vat'][$r] += $byRate[$r]['vat'] ?? 0;
                }
                $totals['grand'] += $entry['total'] ?? 0;
                if ($entry['deduction_eligible'] ?? false) $deductibleCount++;
            @endphp
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td class="text-center" style="font-size: 6px;">{{ $entry['party_tax_id'] ?? '' }}</td>
                <td class="text-left" style="font-size: 6px;">
                    {{ $entry['party_name'] ?? '' }}
                    @if($entry['party_address'] ?? '')<br>{{ $entry['party_address'] }}@endif
                    @if($isRc) <span class="rc-badge">ОД</span>@endif
                </td>
                <td class="text-left" style="font-size: 6px;">{{ $entry['number'] ?? '' }}</td>
                <td class="text-center">{{ $entry['date'] ?? '' }}</td>
                @foreach([18, 10, 5, 0] as $r)
                <td class="text-right">
                    @if(($byRate[$r]['base'] ?? 0) != 0)
                        {!! format_money_pdf($byRate[$r]['base'], $currency) !!}
                    @endif
                </td>
                @endforeach
                @foreach([18, 10, 5] as $r)
                <td class="text-right">
                    @if(($byRate[$r]['vat'] ?? 0) != 0)
                        {!! format_money_pdf($byRate[$r]['vat'], $currency) !!}
                    @endif
                </td>
                @endforeach
                <td class="text-right" style="font-weight: bold;">
                    {!! format_money_pdf($entry['total'] ?? 0, $currency) !!}
                </td>
                <td class="text-center">{{ $entry['payment_date'] ?? '' }}</td>
                <td class="text-center {{ ($entry['deduction_eligible'] ?? false) ? 'deductible' : 'non-deductible' }}">
                    {{ ($entry['deduction_eligible'] ?? false) ? 'ДА' : 'НЕ' }}
                </td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-left">ВКУПНО ({{ count($entries) }} фактури)</td>
                @foreach([18, 10, 5, 0] as $r)
                <td class="text-right">{!! format_money_pdf($totals['base'][$r], $currency) !!}</td>
                @endforeach
                @foreach([18, 10, 5] as $r)
                <td class="text-right">{!! format_money_pdf($totals['vat'][$r], $currency) !!}</td>
                @endforeach
                <td class="text-right">{!! format_money_pdf($totals['grand'], $currency) !!}</td>
                <td></td>
                <td class="text-center">{{ $deductibleCount }}/{{ count($entries) }}</td>
            </tr>
        </tfoot>
    </table>

    <table class="signature-section">
        <tr>
            <td style="width: 50%; text-align: center; padding-top: 35px;">
                <p class="signature-label">Составил</p>
            </td>
            <td style="width: 50%; text-align: center; padding-top: 35px;">
                <p class="signature-label">Одговорно лице</p>
            </td>
        </tr>
    </table>
</body>
</html>
