<!DOCTYPE html>
<html lang="mk">
<head>
    <title>Банкарски извод</title>
    <style type="text/css">
        body {
            font-family: "DejaVu Sans";
            font-size: 9px;
            color: #333;
            margin: 15px;
        }
        h1 { font-size: 14px; text-align: center; margin-bottom: 3px; }
        .subtitle { text-align: center; font-size: 10px; color: #666; margin-bottom: 15px; }
        .account-info { width: 100%; margin-bottom: 12px; border: 1px solid #ccc; padding: 8px; background: #f9f9f9; }
        .info-grid { width: 100%; margin-bottom: 8px; }
        .info-grid td { padding: 2px 5px; }
        .info-grid .label { font-size: 7px; color: #888; text-transform: uppercase; }
        .info-grid .value { font-size: 10px; font-weight: bold; }
        .txn-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .txn-table th { background: #2d3748; color: #fff; padding: 5px 6px; font-size: 7.5px; text-align: left; }
        .txn-table td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; font-size: 7.5px; }
        .txn-table tr:nth-child(even) { background: #f7fafc; }
        .txn-table .amount { text-align: right; }
        .credit { color: #38a169; }
        .debit { color: #e53e3e; }
        .total-row { font-weight: bold; background: #edf2f7; border-top: 2px solid #2d3748; }
        .summary-box { margin-top: 12px; border: 1px solid #ccc; padding: 8px; width: 50%; float: right; }
        .summary-box table { width: 100%; }
        .summary-box td { padding: 3px 5px; }
        .summary-box .label { text-align: left; }
        .summary-box .value { text-align: right; font-weight: bold; }
        .footer { clear: both; margin-top: 25px; font-size: 7px; color: #888; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 5px; }
    </style>
</head>
<body>
    <h1>БАНКАРСКИ ИЗВОД</h1>
    <p class="subtitle">{{ $period_from ?? '' }} — {{ $period_to ?? '' }}</p>

    <div class="account-info">
        <table class="info-grid">
            <tr>
                <td style="width: 50%;">
                    <span class="label">Компанија</span><br/>
                    <span class="value">{{ $company->name ?? '' }}</span>
                </td>
                <td style="width: 50%;">
                    <span class="label">Банка / Сметка</span><br/>
                    <span class="value">{{ $bank_name ?? '' }} — {{ $account_number ?? '' }}</span>
                </td>
            </tr>
            <tr>
                <td>
                    <span class="label">ЕДБ</span>: {{ $company->vat_number ?? '-' }} &nbsp;
                    <span class="label">ЕМБС</span>: {{ $company->tax_id ?? '-' }}
                </td>
                <td>
                    <span class="label">IBAN</span>: {{ $iban ?? '-' }} &nbsp;
                    <span class="label">Валута</span>: {{ $currency ?? 'МКД' }}
                </td>
            </tr>
        </table>
    </div>

    <table class="txn-table">
        <thead>
            <tr>
                <th style="width: 4%;">Бр.</th>
                <th style="width: 8%;">Датум</th>
                <th style="width: 30%;">Опис</th>
                <th style="width: 18%;">Контрапартија</th>
                <th style="width: 10%;">Референца</th>
                <th style="width: 10%;" class="amount">Приход</th>
                <th style="width: 10%;" class="amount">Расход</th>
                <th style="width: 10%;" class="amount">Салдо</th>
            </tr>
        </thead>
        <tbody>
            @php $balance = $opening_balance ?? 0; @endphp
            <tr style="background: #edf2f7; font-weight: bold;">
                <td colspan="7">Почетно салдо</td>
                <td class="amount">{{ number_format($balance, 2) }}</td>
            </tr>
            @foreach($transactions as $i => $tx)
                @php
                    $txCredit = $tx['type'] === 'credit' ? $tx['amount'] : 0;
                    $txDebit = $tx['type'] === 'debit' ? $tx['amount'] : 0;
                    $balance += $txCredit - $txDebit;
                @endphp
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $tx['date'] ?? '-' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($tx['description'] ?? '-', 60) }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($tx['counterparty'] ?? '-', 30) }}</td>
                    <td>{{ $tx['reference'] ?? '-' }}</td>
                    <td class="amount credit">{{ $txCredit > 0 ? number_format($txCredit, 2) : '' }}</td>
                    <td class="amount debit">{{ $txDebit > 0 ? number_format($txDebit, 2) : '' }}</td>
                    <td class="amount">{{ number_format($balance, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5">ВКУПНО</td>
                <td class="amount credit">{{ number_format($total_credit ?? 0, 2) }}</td>
                <td class="amount debit">{{ number_format($total_debit ?? 0, 2) }}</td>
                <td class="amount">{{ number_format($balance, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="summary-box">
        <table>
            <tr><td class="label">Почетно салдо:</td><td class="value">{{ number_format($opening_balance ?? 0, 2) }}</td></tr>
            <tr><td class="label">Вкупно приходи:</td><td class="value credit">{{ number_format($total_credit ?? 0, 2) }}</td></tr>
            <tr><td class="label">Вкупно расходи:</td><td class="value debit">{{ number_format($total_debit ?? 0, 2) }}</td></tr>
            <tr><td class="label" style="font-weight: bold;">Крајно салдо:</td><td class="value" style="font-size: 11px;">{{ number_format($balance, 2) }} {{ $currency ?? 'МКД' }}</td></tr>
        </table>
    </div>

    <p class="footer">
        Извод генериран на {{ now()->format('d.m.Y H:i') }} — Facturino — app.facturino.mk<br/>
        Број на трансакции: {{ count($transactions) }}
    </p>
</body>
</html>
