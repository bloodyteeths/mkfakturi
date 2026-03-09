@php
    $labels = [
        'mk' => ['invoice' => 'Фактура', 'invoice_number' => 'Број на фактура', 'due_date' => 'Достасува', 'amount_due' => 'Износ за наплата', 'days_overdue' => 'Дена задоцнување'],
        'en' => ['invoice' => 'Invoice', 'invoice_number' => 'Invoice Number', 'due_date' => 'Due Date', 'amount_due' => 'Amount Due', 'days_overdue' => 'Days Overdue'],
        'tr' => ['invoice' => 'Fatura', 'invoice_number' => 'Fatura Numarasi', 'due_date' => 'Vade Tarihi', 'amount_due' => 'Odenmesi Gereken', 'days_overdue' => 'Gecikme Gunu'],
        'sq' => ['invoice' => 'Fatura', 'invoice_number' => 'Numri i Fatures', 'due_date' => 'Data e Afatit', 'amount_due' => 'Shuma per Pagese', 'days_overdue' => 'Dite Vonese'],
    ];
    $l = $labels[$locale ?? 'mk'] ?? $labels['mk'];
    $sym = $currencySymbol ?? 'ден.';
@endphp
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
                            {{ $l['invoice'] }}
                        @else
                            {{ $l['invoice_number'] }}
                        @endif
                    </td>
                    <td style="padding: 8px 12px; border: 1px solid #dee2e6;">
                        {{ $invoice->invoice_number }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 12px; border: 1px solid #dee2e6; font-weight: bold;">
                        {{ $l['due_date'] }}
                    </td>
                    <td style="padding: 8px 12px; border: 1px solid #dee2e6;">
                        {{ $invoice->due_date instanceof \DateTimeInterface ? $invoice->due_date->format('d.m.Y') : $invoice->due_date }}
                    </td>
                </tr>
                <tr style="background-color: #f8f9fa;">
                    <td style="padding: 8px 12px; border: 1px solid #dee2e6; font-weight: bold;">
                        {{ $l['amount_due'] }}
                    </td>
                    <td style="padding: 8px 12px; border: 1px solid #dee2e6; font-weight: bold; color: #dc3545;">
                        {{ number_format($invoice->due_amount / 100, 2, '.', ',') }} {{ $sym }}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 12px; border: 1px solid #dee2e6; font-weight: bold;">
                        {{ $l['days_overdue'] }}
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
