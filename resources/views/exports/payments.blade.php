<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payments Export</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }
        .header p {
            margin: 3px 0 0;
            color: #666;
            font-size: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background-color: #4a5568;
            color: white;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }
        tr:nth-child(even) {
            background-color: #f7fafc;
        }
        .amount {
            text-align: right;
            font-family: monospace;
        }
        .date {
            white-space: nowrap;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #718096;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
        }
        .totals {
            margin-top: 15px;
            text-align: right;
            font-size: 11px;
            font-weight: bold;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Payments Export</h1>
        @if($company)
            <p>{{ $company->name }}</p>
        @endif
        <p>Generated: {{ now()->format('Y-m-d H:i:s') }}</p>
        @if(isset($params['start_date']) && isset($params['end_date']))
            <p>Period: {{ $params['start_date'] }} to {{ $params['end_date'] }}</p>
        @endif
    </div>

    @if(count($data) > 0)
        <table>
            <thead>
                <tr>
                    <th style="width: 10%;">Payment #</th>
                    <th style="width: 10%;">Date</th>
                    <th style="width: 25%;">Customer</th>
                    <th style="width: 15%;">Payment Method</th>
                    <th style="width: 25%;">Notes</th>
                    <th style="width: 15%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @php $totalAmount = 0; @endphp
                @foreach($data as $payment)
                    @php
                        $amount = ($payment['amount'] ?? 0) / 100;
                        $totalAmount += $amount;
                    @endphp
                    <tr>
                        <td>{{ $payment['payment_number'] ?? '-' }}</td>
                        <td class="date">{{ \Carbon\Carbon::parse($payment['payment_date'] ?? '')->format('d.m.Y') }}</td>
                        <td>{{ $payment['customer_name'] ?? '-' }}</td>
                        <td>{{ $payment['payment_method_name'] ?? '-' }}</td>
                        <td>{{ $payment['notes'] ?? '-' }}</td>
                        <td class="amount">{{ number_format($amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            Total: {{ number_format($totalAmount, 2) }}
        </div>
    @else
        <div class="no-data">
            <p>No payments found for the selected period</p>
        </div>
    @endif

    <div class="footer">
        <p>Total records: {{ count($data) }}</p>
    </div>
</body>
</html>
