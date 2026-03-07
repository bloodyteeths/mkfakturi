{{--
    UJP Official Form Footer
    Signature block matching official Службен Весник layout.

    Optional variables:
    - $formCode    (string) e.g. 'ДБ'
    - $pageNumber  (string)
    - $totalPages  (string)
--}}

<style type="text/css">
    .ujp-footer-section {
        margin-top: 25px;
        width: 100%;
        page-break-inside: avoid;
    }
    .ujp-signature-table {
        width: 100%;
        border-collapse: collapse;
    }
    .ujp-signature-cell {
        width: 33%;
        text-align: center;
        padding-top: 35px;
        vertical-align: bottom;
    }
    .ujp-signature-line {
        border-top: 1px solid #333;
        padding-top: 4px;
        font-size: 9px;
        color: #555;
        margin: 0 15px;
    }
    .ujp-stamp-circle {
        width: 60px;
        height: 60px;
        border: 1px dashed #aaa;
        border-radius: 50%;
        margin: 0 auto 5px auto;
    }
    .ujp-date-line {
        margin-top: 15px;
        font-size: 9px;
        color: #555;
    }
    .ujp-page-number {
        text-align: right;
        font-size: 8px;
        color: #999;
        margin-top: 10px;
        border-top: 1px solid #eee;
        padding-top: 3px;
    }
</style>

<div class="ujp-footer-section">
    {{-- Date --}}
    <table style="width: 100%; margin-bottom: 5px;">
        <tr>
            <td style="width: 50%;">
                <p class="ujp-date-line">
                    Датум: _______________
                </p>
            </td>
            <td style="width: 50%; text-align: right;">
                <p class="ujp-date-line">
                    Место: _______________
                </p>
            </td>
        </tr>
    </table>

    {{-- Signatures --}}
    <table class="ujp-signature-table">
        <tr>
            <td class="ujp-signature-cell">
                <p class="ujp-signature-line">Составил</p>
            </td>
            <td class="ujp-signature-cell">
                <div class="ujp-stamp-circle"></div>
                <p style="font-size: 7px; color: #aaa; margin: 0;">М.П.</p>
            </td>
            <td class="ujp-signature-cell">
                <p class="ujp-signature-line">Одговорно лице</p>
            </td>
        </tr>
    </table>
</div>

{{-- Page number --}}
@if(isset($pageNumber) || isset($totalPages))
<div class="ujp-page-number">
    {{ $pageNumber ?? '' }} / {{ $totalPages ?? '' }}
</div>
@endif

{{-- CLAUDE-CHECKPOINT --}}
