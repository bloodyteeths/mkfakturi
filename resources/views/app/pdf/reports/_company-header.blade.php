{{-- Shared company header for financial report PDFs --}}
<table class="report-header">
    <tr>
        <td style="width: 65%; vertical-align: top;">
            <p class="heading-text">{{ $company->name }}</p>
            @if($company->address)
                @if($company->address->address_street_1)
                    <p style="font-size: 10px; color: #555; margin: 2px 0 0 0;">{{ $company->address->address_street_1 }}</p>
                @endif
                @if($company->address->city || $company->address->zip)
                    <p style="font-size: 10px; color: #555; margin: 1px 0 0 0;">
                        {{ $company->address->zip }} {{ $company->address->city }}
                    </p>
                @endif
            @endif
            @if($company->vat_id)
                <p style="font-size: 10px; color: #555; margin: 3px 0 0 0;"><strong>ЕДБ за ДДВ:</strong> {{ $company->vat_id }}</p>
            @endif
            @if($company->tax_id)
                <p style="font-size: 10px; color: #555; margin: 1px 0 0 0;"><strong>Даночен број (ЕМБС):</strong> {{ $company->tax_id }}</p>
            @endif
            @if($company->address && $company->address->phone)
                <p style="font-size: 10px; color: #555; margin: 1px 0 0 0;"><strong>Телефон:</strong> {{ $company->address->phone }}</p>
            @endif
        </td>
        <td style="width: 35%; vertical-align: top;">
            <p class="heading-date">{{ $report_period ?? '' }}</p>
        </td>
    </tr>
</table>
