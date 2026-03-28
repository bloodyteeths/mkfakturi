<!DOCTYPE html>
<html lang="mk">
<head>
    <title>ПП10 Налог за наплата</title>
    <style type="text/css">
        body { font-family: "DejaVu Sans"; font-size: 9px; color: #333; margin: 15px; }
        .pp10-form { border: 2px solid #1a1a1a; padding: 0; margin-bottom: 20px; page-break-inside: avoid; }
        .pp10-header { background-color: #c53030; color: #ffffff; padding: 8px 15px; text-align: center; }
        .pp10-header h1 { margin: 0; font-size: 14px; font-weight: bold; letter-spacing: 1px; }
        .pp10-header .pp10-subtitle { font-size: 8px; color: #fed7d7; margin-top: 2px; }
        .pp10-body { padding: 12px 15px; }
        .parties-row { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .parties-row td { width: 50%; vertical-align: top; padding: 0; }
        .party-box { border: 1px solid #e2e8f0; padding: 8px; margin: 0 4px; }
        .party-label { font-size: 7px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
        .party-name { font-size: 11px; font-weight: bold; margin-bottom: 2px; }
        .party-detail { font-size: 8px; color: #555; }
        .amount-section { border: 2px solid #c53030; padding: 10px; margin: 10px 0; text-align: center; }
        .amount-label { font-size: 8px; color: #888; }
        .amount-value { font-size: 18px; font-weight: bold; color: #c53030; }
        .amount-words { font-size: 8px; font-style: italic; color: #666; margin-top: 3px; }
        .details-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .details-table td { padding: 4px 8px; border-bottom: 1px solid #e2e8f0; }
        .details-table .label { font-size: 7px; color: #888; text-transform: uppercase; width: 30%; }
        .details-table .value { font-size: 9px; }
        .legal-basis { margin-top: 10px; padding: 8px; background: #fff5f5; border: 1px solid #feb2b2; font-size: 8px; }
        .signature-row { width: 100%; margin-top: 25px; }
        .signature-row td { width: 50%; padding: 5px; vertical-align: bottom; }
        .signature-line { border-top: 1px solid #333; margin-top: 30px; text-align: center; font-size: 8px; padding-top: 3px; }
        .footer-note { font-size: 7px; color: #888; margin-top: 15px; text-align: center; }
    </style>
</head>
<body>
    @foreach($slips as $slip)
    <div class="pp10-form">
        <div class="pp10-header">
            <h1>ПП10 — НАЛОГ ЗА НАПЛАТА</h1>
            <div class="pp10-subtitle">Платен налог иницииран од доверител / Collection Order</div>
        </div>
        <div class="pp10-body">
            <table class="parties-row">
                <tr>
                    <td>
                        <div class="party-box">
                            <div class="party-label">Доверител (Примач на средства)</div>
                            <div class="party-name">{{ $slip['creditor_name'] }}</div>
                            <div class="party-detail">{{ $slip['creditor_account'] }}</div>
                            <div class="party-detail">{{ $slip['creditor_bank'] }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="party-box">
                            <div class="party-label">Должник (Платец)</div>
                            <div class="party-name">{{ $slip['debtor_name'] }}</div>
                            <div class="party-detail">{{ $slip['debtor_account'] }}</div>
                            <div class="party-detail">{{ $slip['debtor_bank'] }}</div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="amount-section">
                <div class="amount-label">ИЗНОС</div>
                <div class="amount-value">{{ number_format($slip['amount'] / 100, 2) }} {{ $slip['currency'] ?? 'МКД' }}</div>
                @if(!empty($slip['amount_words']))
                <div class="amount-words">{{ $slip['amount_words'] }}</div>
                @endif
            </div>

            <table class="details-table">
                <tr><td class="label">Цел на наплата</td><td class="value">{{ $slip['purpose'] ?? '-' }}</td></tr>
                <tr><td class="label">Правен основ</td><td class="value">{{ $slip['legal_basis'] ?? '-' }}</td></tr>
                <tr><td class="label">Број на договор / фактура</td><td class="value">{{ $slip['reference'] ?? '-' }}</td></tr>
                <tr><td class="label">Датум на извршување</td><td class="value">{{ $slip['execution_date'] ?? now()->format('d.m.Y') }}</td></tr>
                <tr><td class="label">Број на налог</td><td class="value">{{ $slip['order_number'] ?? '-' }}</td></tr>
            </table>

            @if(!empty($slip['legal_basis']))
            <div class="legal-basis">
                <strong>Правен основ:</strong> {{ $slip['legal_basis'] }}
            </div>
            @endif

            <table class="signature-row">
                <tr>
                    <td><div class="signature-line">Потпис и печат на доверител</div></td>
                    <td><div class="signature-line">Потпис на банка</div></td>
                </tr>
            </table>
        </div>
    </div>
    @endforeach

    <p class="footer-note">Генерирано од Facturino — app.facturino.mk</p>
</body>
</html>
