@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => ''])
        @if($company->logo)
            <img class="header-logo" src="{{ asset($company->logo) }}" alt="{{ $company->name }}">
        @else
            {{ $company->name }}
        @endif
        @endcomponent
    @endslot

    {{-- Body --}}
    @slot('subcopy')
        @component('mail::subcopy')
            {!! $body !!}

            <br>

            <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
                <tr style="background-color: #f8f9fa;">
                    <td style="padding: 8px 12px; border: 1px solid #dee2e6; font-weight: bold;">
                        @if($level === 'legal')
                            {{ __('Invoice') }}
                        @else
                            {{ __('Invoice Number') }}
                        @endif
                    </td>
                    <td style="padding: 8px 12px; border: 1px solid #dee2e6;">
                        {{ $invoice->invoice_number }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 12px; border: 1px solid #dee2e6; font-weight: bold;">
                        {{ __('Due Date') }}
                    </td>
                    <td style="padding: 8px 12px; border: 1px solid #dee2e6;">
                        {{ $invoice->due_date instanceof \DateTimeInterface ? $invoice->due_date->format('d.m.Y') : $invoice->due_date }}
                    </td>
                </tr>
                <tr style="background-color: #f8f9fa;">
                    <td style="padding: 8px 12px; border: 1px solid #dee2e6; font-weight: bold;">
                        {{ __('Amount Due') }}
                    </td>
                    <td style="padding: 8px 12px; border: 1px solid #dee2e6; font-weight: bold; color: #dc3545;">
                        {{ number_format($invoice->due_amount / 100, 2, '.', ',') }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 12px; border: 1px solid #dee2e6; font-weight: bold;">
                        {{ __('Days Overdue') }}
                    </td>
                    <td style="padding: 8px 12px; border: 1px solid #dee2e6; color: #dc3545;">
                        {{ $daysOverdue }}
                    </td>
                </tr>
            </table>
        @endcomponent
    @endslot

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            Powered by <a class="footer-link" href="https://facturino.mk" target="_blank">Facturino</a>
        @endcomponent
    @endslot
@endcomponent
