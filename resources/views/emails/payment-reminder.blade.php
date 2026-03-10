@php
    $labels = [
        'mk' => ['invoice' => 'Фактура', 'invoice_number' => 'Број на фактура', 'due_date' => 'Достасува', 'amount_due' => 'Износ за наплата', 'days_overdue' => 'Дена задоцнување', 'powered_by' => 'Испратено преку'],
        'en' => ['invoice' => 'Invoice', 'invoice_number' => 'Invoice Number', 'due_date' => 'Due Date', 'amount_due' => 'Amount Due', 'days_overdue' => 'Days Overdue', 'powered_by' => 'Powered by'],
        'tr' => ['invoice' => 'Fatura', 'invoice_number' => 'Fatura Numarasi', 'due_date' => 'Vade Tarihi', 'amount_due' => 'Odenmesi Gereken', 'days_overdue' => 'Gecikme Gunu', 'powered_by' => 'Tarafindan'],
        'sq' => ['invoice' => 'Fatura', 'invoice_number' => 'Numri i Fatures', 'due_date' => 'Data e Afatit', 'amount_due' => 'Shuma per Pagese', 'days_overdue' => 'Dite Vonese', 'powered_by' => 'Mundësuar nga'],
    ];
    $l = $labels[$locale ?? 'mk'] ?? $labels['mk'];
    $sym = $currencySymbol ?? 'ден.';
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <style>
        body { margin: 0; padding: 0; width: 100%; background-color: #f4f4f7; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .wrapper { width: 100%; background-color: #f4f4f7; padding: 32px 0; }
        .content { max-width: 570px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header { background-color: #2d3748; padding: 24px 32px; text-align: center; }
        .header img { max-height: 48px; }
        .header-text { color: #ffffff; font-size: 18px; font-weight: 600; margin: 0; }
        .body-content { padding: 32px; }
        .body-content p { color: #4a5568; font-size: 14px; line-height: 1.6; margin: 0 0 16px 0; }
        .invoice-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .invoice-table td { padding: 10px 14px; border: 1px solid #e2e8f0; font-size: 14px; color: #4a5568; }
        .invoice-table tr:nth-child(odd) { background-color: #f7fafc; }
        .invoice-table .label { font-weight: 600; color: #2d3748; width: 45%; }
        .amount { font-weight: 700; color: #e53e3e; }
        .footer { background-color: #f7fafc; padding: 16px 32px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer p { color: #a0aec0; font-size: 12px; margin: 0; }
        .footer a { color: #4299e1; text-decoration: none; }
        @media only screen and (max-width: 600px) {
            .content { width: 100% !important; border-radius: 0; }
            .body-content { padding: 24px 16px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <table class="content" width="570" cellpadding="0" cellspacing="0" role="presentation" align="center">
            {{-- Header --}}
            <tr>
                <td class="header">
                    @if($company->logo)
                        <img src="{{ asset($company->logo) }}" alt="{{ $company->name }}">
                    @else
                        <p class="header-text">{{ $company->name }}</p>
                    @endif
                </td>
            </tr>

            {{-- Body --}}
            <tr>
                <td class="body-content">
                    {!! $body !!}

                    <table class="invoice-table">
                        <tr>
                            <td class="label">
                                @if($level === 'legal')
                                    {{ $l['invoice'] }}
                                @else
                                    {{ $l['invoice_number'] }}
                                @endif
                            </td>
                            <td>{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td class="label">{{ $l['due_date'] }}</td>
                            <td>{{ $invoice->due_date instanceof \DateTimeInterface ? $invoice->due_date->format('d.m.Y') : $invoice->due_date }}</td>
                        </tr>
                        <tr>
                            <td class="label">{{ $l['amount_due'] }}</td>
                            <td class="amount">{{ number_format($invoice->due_amount / 100, 2, '.', ',') }} {{ $sym }}</td>
                        </tr>
                        <tr>
                            <td class="label">{{ $l['days_overdue'] }}</td>
                            <td class="amount">{{ $daysOverdue }}</td>
                        </tr>
                    </table>
                </td>
            </tr>

            {{-- Footer --}}
            <tr>
                <td class="footer">
                    <p>{{ $l['powered_by'] }} <a href="https://facturino.mk" target="_blank">Facturino</a></p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>

