<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payout Receipt #{{ $payout->id }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #4f46e5;
            margin: 0;
            font-size: 24px;
        }
        .header p {
            color: #666;
            margin: 5px 0 0;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .info-box {
            width: 48%;
            background: #f8fafc;
            padding: 15px;
            border-radius: 5px;
        }
        .info-box h3 {
            margin: 0 0 10px;
            color: #4f46e5;
            font-size: 14px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        .info-box p {
            margin: 5px 0;
        }
        .info-box .label {
            color: #666;
            font-size: 11px;
        }
        .info-box .value {
            font-weight: bold;
        }
        .amount-box {
            background: #ecfdf5;
            border: 2px solid #10b981;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
            border-radius: 8px;
        }
        .amount-box .label {
            color: #065f46;
            font-size: 14px;
        }
        .amount-box .value {
            color: #10b981;
            font-size: 32px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 10px;
            text-align: left;
        }
        th {
            background: #f8fafc;
            color: #374151;
            font-weight: 600;
        }
        tr:nth-child(even) {
            background: #fafafa;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            color: #666;
            font-size: 10px;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-completed {
            background: #d1fae5;
            color: #065f46;
        }
        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>ПОТВРДА ЗА ИСПЛАТА</h1>
        <p>Payout Receipt</p>
    </div>

    <table style="border: none; margin-bottom: 30px;">
        <tr style="border: none;">
            <td style="border: none; width: 50%; vertical-align: top;">
                <div class="info-box">
                    <h3>Партнер / Partner</h3>
                    <p><span class="label">Име:</span> <span class="value">{{ $partner->user->name ?? 'N/A' }}</span></p>
                    <p><span class="label">Емаил:</span> <span class="value">{{ $partner->user->email ?? 'N/A' }}</span></p>
                    <p><span class="label">Partner ID:</span> <span class="value">#{{ $partner->id }}</span></p>
                    @if($partner->bank_name)
                    <p><span class="label">Банка:</span> <span class="value">{{ $partner->bank_name }}</span></p>
                    @endif
                    @if($partner->iban)
                    <p><span class="label">IBAN:</span> <span class="value">{{ $partner->iban }}</span></p>
                    @endif
                </div>
            </td>
            <td style="border: none; width: 50%; vertical-align: top;">
                <div class="info-box">
                    <h3>Детали за исплата / Payout Details</h3>
                    <p><span class="label">Број:</span> <span class="value">#{{ $payout->id }}</span></p>
                    <p><span class="label">Период:</span> <span class="value">{{ $payout->period_start?->format('d.m.Y') }} - {{ $payout->period_end?->format('d.m.Y') }}</span></p>
                    <p><span class="label">Датум на исплата:</span> <span class="value">{{ $payout->paid_at?->format('d.m.Y H:i') ?? 'N/A' }}</span></p>
                    <p><span class="label">Статус:</span> <span class="status-badge status-completed">{{ ucfirst($payout->status) }}</span></p>
                    @if($payout->transaction_reference)
                    <p><span class="label">Референца:</span> <span class="value">{{ $payout->transaction_reference }}</span></p>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <div class="amount-box">
        <div class="label">ВКУПЕН ИЗНОС / TOTAL AMOUNT</div>
        <div class="value">&euro; {{ number_format($payout->amount / 100, 2) }}</div>
    </div>

    @if($events->count() > 0)
    <h3>Вклучени провизии / Included Commissions</h3>
    <table>
        <thead>
            <tr>
                <th>Датум</th>
                <th>Компанија</th>
                <th>Тип</th>
                <th class="text-right">Износ</th>
            </tr>
        </thead>
        <tbody>
            @foreach($events as $event)
            <tr>
                <td>{{ $event->created_at?->format('d.m.Y') }}</td>
                <td>{{ $event->company?->name ?? 'N/A' }}</td>
                <td>{{ ucfirst(str_replace('_', ' ', $event->event_type ?? 'commission')) }}</td>
                <td class="text-right">&euro; {{ number_format($event->commission_amount / 100, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="3" class="text-right">Вкупно:</th>
                <th class="text-right">&euro; {{ number_format($events->sum('commission_amount') / 100, 2) }}</th>
            </tr>
        </tfoot>
    </table>
    @endif

    <div class="footer">
        <p>Генерирано на: {{ $generatedDate->format('d.m.Y H:i:s') }}</p>
        <p>Facturino Partner Program - app.facturino.mk</p>
        <p>Овој документ е автоматски генериран и не бара потпис.</p>
    </div>
</body>
</html>
