<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 25px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; line-height: 1.5; margin: 15px; }
        .header-bar { width: 100%; background: #2d3748; color: #fff; padding: 12px 16px; margin-bottom: 15px; }
        .header-bar td { vertical-align: middle; color: #fff; }
        .header-bar .doc-title { font-size: 16px; font-weight: bold; letter-spacing: 1px; }
        .header-bar .doc-number { font-size: 11px; text-align: right; }
        .section-title { font-size: 12px; font-weight: bold; color: #5851D8; border-bottom: 2px solid #5851D8; padding-bottom: 3px; margin: 14px 0 8px 0; }
        .party-box { width: 30%; float: left; margin-right: 3%; border: 1px solid #ddd; padding: 8px; background: #fafafa; min-height: 90px; }
        .party-box:last-child { margin-right: 0; }
        .party-box-title { font-size: 9px; text-transform: uppercase; color: #5851D8; letter-spacing: 1px; margin-bottom: 6px; font-weight: bold; }
        .party-box p { margin: 2px 0; font-size: 10px; }
        .party-box .name { font-size: 12px; font-weight: bold; margin-bottom: 4px; }
        .clearfix { clear: both; }
        .doc-ref { margin: 8px 0; padding: 8px 12px; border: 1px solid #ddd; background: #f9f9f9; font-size: 10px; }
        .doc-ref .label { color: #666; font-weight: bold; }
        .amount-box { margin: 14px 0; padding: 12px 16px; border: 2px solid #5851D8; background: #f7f6ff; text-align: center; }
        .amount-box .amount { font-size: 18px; font-weight: bold; color: #5851D8; }
        .amount-box .amount-words { font-size: 10px; color: #666; margin-top: 4px; font-style: italic; }
        .amount-box .currency { font-size: 11px; color: #555; }
        .legal-text { margin: 12px 0; font-size: 10px; text-align: justify; color: #555; line-height: 1.6; }
        .signatures { width: 100%; margin-top: 30px; }
        .signatures td { width: 33%; text-align: center; vertical-align: bottom; padding: 0 10px; }
        .sig-line { border-top: 1px solid #333; margin-top: 50px; padding-top: 4px; font-size: 10px; color: #555; }
        .sig-stamp { font-size: 9px; color: #999; margin-top: 2px; }
        .footer { margin-top: 20px; font-size: 9px; color: #999; text-align: center; border-top: 1px solid #ddd; padding-top: 6px; }
    </style>
</head>
<body>
    {{-- Header Bar --}}
    <table class="header-bar">
        <tr>
            <td style="width: 60%;">
                <div class="doc-title">ДОГОВОР ЗА АСИГНАЦИЈА</div>
            </td>
            <td style="width: 40%;">
                <div class="doc-number">
                    Број: {{ $assignation->assignation_number }}<br>
                    Датум: {{ $assignation->assignation_date->format('d.m.Y') }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Three Parties --}}
    <div class="section-title">Договорни страни</div>

    <div class="party-box">
        <div class="party-box-title">Упатувач (асигнант)</div>
        <p class="name">{{ $assignation->assignor_name }}</p>
        @if($assignation->assignor_vat_id)
            <p>ЕДБ: {{ $assignation->assignor_vat_id }}</p>
        @endif
        @if($assignation->assignor_tax_id)
            <p>ЕМБС: {{ $assignation->assignor_tax_id }}</p>
        @endif
    </div>

    <div class="party-box">
        <div class="party-box-title">Примач (асигнатар)</div>
        <p class="name">{{ $assignation->assignee_name }}</p>
        @if($assignation->assignee_vat_id)
            <p>ЕДБ: {{ $assignation->assignee_vat_id }}</p>
        @endif
        @if($assignation->assignee_tax_id)
            <p>ЕМБС: {{ $assignation->assignee_tax_id }}</p>
        @endif
    </div>

    <div class="party-box">
        <div class="party-box-title">Упатен должник (асигнат)</div>
        <p class="name">{{ $assignation->debtor_name }}</p>
        @if($assignation->debtor_vat_id)
            <p>ЕДБ: {{ $assignation->debtor_vat_id }}</p>
        @endif
        @if($assignation->debtor_tax_id)
            <p>ЕМБС: {{ $assignation->debtor_tax_id }}</p>
        @endif
    </div>

    <div class="clearfix"></div>

    {{-- Document References --}}
    <div class="section-title">Основ за асигнација</div>

    @if($assignation->assignor_to_assignee_doc)
        <div class="doc-ref">
            <span class="label">Долг на упатувач кон примач:</span>
            {{ $assignation->assignor_to_assignee_doc }}
        </div>
    @endif

    @if($assignation->assignor_to_debtor_doc)
        <div class="doc-ref">
            <span class="label">Побарување на упатувач од должник:</span>
            {{ $assignation->assignor_to_debtor_doc }}
        </div>
    @endif

    {{-- Amount Box --}}
    <div class="section-title">Износ на асигнација</div>
    <div class="amount-box">
        <div class="amount">{{ $amount_formatted }} <span class="currency">МКД</span></div>
        @if($amount_words)
            <div class="amount-words">(со зборови: {{ $amount_words }})</div>
        @endif
    </div>

    {{-- Legal Text --}}
    <div class="legal-text">
        <p>Со овој договор, Упатувачот го упатува Упатениот должник да го исплати наведениот износ директно на Примачот. Упатениот должник се обврзува да го исплати побарувањето на Примачот. Овој договор стапува на сила со потписот на сите три страни.</p>
        <p>Согласно Закон за облигациони односи, член 1078-1093.</p>
    </div>

    {{-- Signatures --}}
    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">Упатувач</div>
                <div class="sig-stamp">М.П.</div>
            </td>
            <td>
                <div class="sig-line">Примач</div>
                <div class="sig-stamp">М.П.</div>
            </td>
            <td>
                <div class="sig-line">Упатен должник</div>
                <div class="sig-stamp">М.П.</div>
            </td>
        </tr>
    </table>

    <div class="footer">
        Документ генериран од Facturino | {{ $assignation->assignation_date->format('d.m.Y') }}
    </div>
</body>
</html>
{{-- CLAUDE-CHECKPOINT --}}
