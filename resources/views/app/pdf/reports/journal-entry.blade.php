<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Налог {{ $entry['reference'] ?? '' }}</title>
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

        .nalog-header {
            width: 100%;
            margin: 15px 0 10px 0;
            border: 1px solid #999;
            padding: 8px 12px;
            background: #f8f8f8;
        }

        .nalog-title {
            font-size: 14px;
            font-weight: bold;
            margin: 0 0 4px 0;
        }

        .nalog-meta {
            font-size: 10px;
            color: #555;
            margin: 2px 0;
        }

        .items-table {
            width: 100%;
            margin-top: 10px;
        }

        .items-table th {
            background: #e8e8e8;
            padding: 5px 6px;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #ccc;
            text-align: left;
        }

        .items-table td {
            padding: 4px 6px;
            border: 1px solid #ddd;
            font-size: 9px;
        }

        .items-table .amount {
            text-align: right;
            font-family: "DejaVu Sans Mono", monospace;
        }

        .items-table .code {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 9px;
        }

        .items-table tfoot td {
            font-weight: bold;
            background: #f0f0f0;
            border-top: 2px solid #999;
        }

        .footer {
            margin-top: 30px;
            width: 100%;
        }

        .footer td {
            padding: 20px 0 0 0;
            font-size: 9px;
            border-top: 1px solid #999;
            text-align: center;
            color: #666;
            width: 33%;
        }

        .signature-label {
            font-size: 8px;
            color: #999;
            margin-top: 4px;
        }
    </style>
</head>

<body>
    <div class="sub-container">
        @include('app.pdf.reports._company-header')

        <div class="nalog-header">
            <p class="nalog-title">{{ $entry['narration'] }}</p>
            <p class="nalog-meta">
                <strong>Број:</strong> {{ $entry['reference'] ?? '-' }}
                &nbsp;&nbsp;&nbsp;
                <strong>Датум:</strong> {{ \Carbon\Carbon::parse($entry['date'])->format('d.m.Y') }}
                &nbsp;&nbsp;&nbsp;
                <strong>Тип:</strong> {{ $entry['transaction_type'] }}
            </p>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 8%;">Р.бр.</th>
                    <th style="width: 10%;">Конто</th>
                    <th style="width: 22%;">Назив на сметка</th>
                    <th style="width: 18%;">Партнер</th>
                    <th style="width: 17%;">Опис</th>
                    <th style="width: 12%;" class="amount">Должи</th>
                    <th style="width: 12%;" class="amount">Побарува</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entry['lines'] as $i => $line)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td class="code">{{ $line['account_code'] }}</td>
                    <td>{{ $line['account_name'] }}</td>
                    <td>{{ $line['counterparty_name'] ?? '' }}</td>
                    <td>{{ $line['description'] }}</td>
                    <td class="amount">{{ $line['debit'] > 0 ? number_format($line['debit'] / 100, 2, ',', '.') : '' }}</td>
                    <td class="amount">{{ $line['credit'] > 0 ? number_format($line['credit'] / 100, 2, ',', '.') : '' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align: right;">Вкупно:</td>
                    <td class="amount">{{ number_format($entry['total_debit'] / 100, 2, ',', '.') }}</td>
                    <td class="amount">{{ number_format($entry['total_credit'] / 100, 2, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>

        <table class="footer">
            <tr>
                <td>
                    Составил
                    <div class="signature-label">__________________________</div>
                </td>
                <td>
                    Контролирал
                    <div class="signature-label">__________________________</div>
                </td>
                <td>
                    Одобрил
                    <div class="signature-label">__________________________</div>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
