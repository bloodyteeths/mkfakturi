<!DOCTYPE html>
<html lang="mk">

<head>
    <title>Главна книга</title>
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
            padding: 0px;
            margin: 0px;
        }

        .heading-date {
            font-weight: normal;
            font-size: 10px;
            color: #666;
            text-align: right;
            padding: 0px;
            margin: 0px;
        }

        .sub-heading-text {
            font-weight: bold;
            font-size: 13px;
            color: #333;
            text-align: center;
            margin: 2px 0 0 0;
        }

        .account-info {
            font-size: 11px;
            color: #555;
            text-align: center;
            margin: 2px 0 8px 0;
        }

        .ledger-table {
            margin-top: 8px;
            width: 100%;
            border: 1px solid #cbd5e0;
        }

        .table-header {
            background: #e2e8f0;
            border-bottom: 2px solid #a0aec0;
        }

        .th-text {
            padding: 6px 4px;
            font-weight: bold;
            font-size: 9px;
            color: #2d3748;
            text-align: center;
        }

        .entry-row {
            border-bottom: 1px solid #edf2f7;
        }

        .entry-row:nth-child(even) {
            background: #f7fafc;
        }

        .cell {
            padding: 4px;
            font-size: 9px;
            color: #2d3748;
        }

        .cell-amount {
            padding: 4px;
            font-size: 9px;
            text-align: right;
            color: #2d3748;
        }

        .opening-row {
            background: #ebf4ff;
            border-bottom: 1px solid #a0aec0;
        }

        .total-row {
            background: #e2e8f0;
            border-top: 2px solid #2c5282;
        }

        .total-label {
            padding: 6px 4px;
            font-weight: bold;
            font-size: 10px;
            color: #1a202c;
        }

        .total-amount {
            padding: 6px 4px;
            font-weight: bold;
            font-size: 9px;
            text-align: right;
            color: #2c5282;
        }

        .signature-section {
            margin-top: 30px;
            width: 100%;
        }

        .signature-label {
            font-size: 9px;
            color: #666;
            border-top: 1px solid #999;
            padding-top: 3px;
            width: 200px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="sub-container">
        @include('app.pdf.reports._company-header')

        <p class="sub-heading-text">ГЛАВНА КНИГА</p>
        <p class="account-info">
            Сметка: {{ $account_code }} - {{ $account_name }}
            &nbsp;|&nbsp; Период: {{ $from_date }} - {{ $to_date }}
        </p>

        <table class="ledger-table">
            <thead>
                <tr class="table-header">
                    <th style="width: 12%;"><p class="th-text" style="text-align: left;">Датум</p></th>
                    <th style="width: 12%;"><p class="th-text" style="text-align: left;">Документ</p></th>
                    <th style="width: 30%;"><p class="th-text" style="text-align: left;">Опис</p></th>
                    <th style="width: 15%;"><p class="th-text">Должи</p></th>
                    <th style="width: 15%;"><p class="th-text">Побарува</p></th>
                    <th style="width: 16%;"><p class="th-text">Салдо</p></th>
                </tr>
            </thead>
            <tbody>
                {{-- Opening balance row --}}
                <tr class="opening-row">
                    <td colspan="3">
                        <p class="cell" style="font-weight: bold;">Почетно салдо</p>
                    </td>
                    <td><p class="cell-amount">-</p></td>
                    <td><p class="cell-amount">-</p></td>
                    <td>
                        <p class="cell-amount" style="font-weight: bold;">
                            {!! format_money_pdf(($ledger['opening_balance'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                </tr>

                @if(isset($ledger['entries']))
                    @foreach($ledger['entries'] as $entry)
                    <tr class="entry-row">
                        <td><p class="cell">{{ $entry['date'] ?? '' }}</p></td>
                        <td><p class="cell">{{ $entry['reference'] ?? $entry['document_type'] ?? '' }}</p></td>
                        <td><p class="cell">{{ $entry['description'] ?? '' }}</p></td>
                        <td>
                            <p class="cell-amount">
                                @if(($entry['debit'] ?? 0) > 0)
                                    {!! format_money_pdf($entry['debit'] * 100, $currency) !!}
                                @endif
                            </p>
                        </td>
                        <td>
                            <p class="cell-amount">
                                @if(($entry['credit'] ?? 0) > 0)
                                    {!! format_money_pdf($entry['credit'] * 100, $currency) !!}
                                @endif
                            </p>
                        </td>
                        <td>
                            <p class="cell-amount">
                                {!! format_money_pdf(($entry['running_balance'] ?? 0) * 100, $currency) !!}
                            </p>
                        </td>
                    </tr>
                    @endforeach
                @endif
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="3"><p class="total-label">Крајно салдо</p></td>
                    <td><p class="total-amount">-</p></td>
                    <td><p class="total-amount">-</p></td>
                    <td>
                        <p class="total-amount">
                            {!! format_money_pdf(($ledger['closing_balance'] ?? 0) * 100, $currency) !!}
                        </p>
                    </td>
                </tr>
            </tfoot>
        </table>

        <table class="signature-section">
            <tr>
                <td style="width: 50%; text-align: center; padding-top: 40px;">
                    <p class="signature-label">Составил</p>
                </td>
                <td style="width: 50%; text-align: center; padding-top: 40px;">
                    <p class="signature-label">Одговорно лице</p>
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
