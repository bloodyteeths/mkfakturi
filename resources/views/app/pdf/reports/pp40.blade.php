<!DOCTYPE html>
<html lang="mk">
<head>
    <title>Меница (ПП40)</title>
    <style type="text/css">
        body { font-family: "DejaVu Sans"; font-size: 9px; color: #333; margin: 15px; }
        .menica-form { border: 3px double #1a1a1a; padding: 0; }
        .menica-header { background: #1a365d; color: #fff; padding: 10px 15px; text-align: center; }
        .menica-header h1 { margin: 0; font-size: 16px; letter-spacing: 2px; }
        .menica-header .subtitle { font-size: 8px; color: #bee3f8; margin-top: 2px; }
        .menica-body { padding: 15px; }
        .menica-text { font-size: 10px; line-height: 1.6; margin-bottom: 15px; text-align: justify; }
        .menica-text .amount { font-weight: bold; font-size: 12px; color: #1a365d; }
        .details-grid { width: 100%; margin: 12px 0; }
        .details-grid td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; }
        .details-grid .label { font-size: 7px; color: #888; text-transform: uppercase; width: 30%; }
        .details-grid .value { font-size: 9px; font-weight: bold; }
        .party-row { width: 100%; margin: 15px 0; }
        .party-row td { width: 50%; vertical-align: top; padding: 5px; }
        .party-info { border: 1px solid #ccc; padding: 8px; }
        .party-info .role { font-size: 7px; color: #888; text-transform: uppercase; }
        .party-info .name { font-size: 10px; font-weight: bold; }
        .aval-section { border: 1px dashed #ccc; padding: 10px; margin: 10px 0; }
        .aval-section h4 { font-size: 9px; margin: 0 0 5px; }
        .signatures { width: 100%; margin-top: 30px; }
        .signatures td { width: 33%; padding: 10px; text-align: center; vertical-align: bottom; }
        .sig-line { border-top: 1px solid #333; margin-top: 40px; font-size: 8px; padding-top: 3px; }
        .legal-note { font-size: 7px; color: #888; margin-top: 15px; padding: 8px; background: #f0f4f8; border: 1px solid #cbd5e0; }
        .footer { font-size: 7px; color: #888; margin-top: 15px; text-align: center; }
    </style>
</head>
<body>
    <div class="menica-form">
        <div class="menica-header">
            <h1>М Е Н И Ц А</h1>
            <div class="subtitle">Сопствена меница / Promissory Note</div>
        </div>
        <div class="menica-body">
            <div class="menica-text">
                На ден <strong>{{ $maturity_date ?? '___________' }}</strong> безусловно ветувам дека ќе платам по
                оваа меница на <strong>{{ $payee_name ?? '___________' }}</strong> или по негов налог,
                износ од <span class="amount">{{ number_format(($amount ?? 0) / 100, 2) }} {{ $currency ?? 'МКД' }}</span>
                @if(!empty($amount_words))
                (со букви: <em>{{ $amount_words }}</em>)
                @endif
            </div>

            <table class="details-grid">
                <tr><td class="label">Место на издавање</td><td class="value">{{ $issue_place ?? 'Скопје' }}</td></tr>
                <tr><td class="label">Датум на издавање</td><td class="value">{{ $issue_date ?? now()->format('d.m.Y') }}</td></tr>
                <tr><td class="label">Датум на доспевање</td><td class="value">{{ $maturity_date ?? '-' }}</td></tr>
                <tr><td class="label">Место на плаќање</td><td class="value">{{ $payment_place ?? 'Скопје' }}</td></tr>
                <tr><td class="label">Број на меница</td><td class="value">{{ $note_number ?? '-' }}</td></tr>
            </table>

            <table class="party-row">
                <tr>
                    <td>
                        <div class="party-info">
                            <div class="role">Издавач (Трасант)</div>
                            <div class="name">{{ $issuer_name ?? '' }}</div>
                            <div>ЕДБ: {{ $issuer_vat ?? '-' }}</div>
                            <div>{{ $issuer_address ?? '' }}</div>
                            <div>Сметка: {{ $issuer_account ?? '-' }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="party-info">
                            <div class="role">Корисник (Ремитент)</div>
                            <div class="name">{{ $payee_name ?? '' }}</div>
                            <div>ЕДБ: {{ $payee_vat ?? '-' }}</div>
                            <div>{{ $payee_address ?? '' }}</div>
                            <div>Сметка: {{ $payee_account ?? '-' }}</div>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="aval-section">
                <h4>Авалист (Гарант) — доколку е применливо</h4>
                <table style="width: 100%;">
                    <tr>
                        <td style="width: 50%;">Име: {{ $guarantor_name ?? '________________________' }}</td>
                        <td style="width: 50%;">ЕДБ: {{ $guarantor_vat ?? '________________________' }}</td>
                    </tr>
                </table>
            </div>

            <table class="signatures">
                <tr>
                    <td><div class="sig-line">Потпис на издавач</div></td>
                    <td><div class="sig-line">Потпис на корисник</div></td>
                    <td><div class="sig-line">Потпис на авалист</div></td>
                </tr>
            </table>

            <div class="legal-note">
                Оваа меница е издадена согласно Законот за меница на РСМ (Службен весник бр. 12/2001).
                Менувачкото побарување застарува три години од доспевање (чл. 63 од Законот за меница).
            </div>
        </div>
    </div>

    <p class="footer">Генерирано од Facturino — app.facturino.mk</p>
</body>
</html>
