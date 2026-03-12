<?php

namespace App\Services\InvoiceParsing;

use App\Models\Bill;
use App\Models\CompanySetting;
use App\Models\Currency;

class ParsedInvoiceMapper
{
    /**
     * @param  array<string,mixed>  $parsed
     * @return array{
     *   supplier: array<string,mixed>,
     *   bill: array<string,mixed>,
     *   items: array<int,array<string,mixed>>
     * }
     */
    public function mapToBillComponents(int $companyId, array $parsed): array
    {
        $supplier = $parsed['supplier'] ?? [];
        $invoice = $parsed['invoice'] ?? [];
        $totals = $parsed['totals'] ?? [];
        $lineItems = $parsed['line_items'] ?? [];

        $companyCurrencyId = CompanySetting::getSetting('currency', $companyId);

        // Resolve invoice currency: Gemini returns code ('MKD'), we need integer ID
        $parsedCurrency = $invoice['currency'] ?? null;
        if ($parsedCurrency && ! is_numeric($parsedCurrency)) {
            $currencyId = Currency::where('code', strtoupper($parsedCurrency))->value('id');
            $invoiceCurrencyId = $currencyId ?? $companyCurrencyId;
        } else {
            $invoiceCurrencyId = $parsedCurrency ?? $companyCurrencyId;
        }

        $exchangeRate = $companyCurrencyId && $invoiceCurrencyId && $invoiceCurrencyId != $companyCurrencyId
            ? (float) ($invoice['exchange_rate'] ?? 1)
            : 1.0;

        $subTotal = (int) ($totals['subtotal'] ?? $totals['total'] ?? 0);
        $total = (int) ($totals['total'] ?? 0);
        $tax = (int) ($totals['tax'] ?? 0);
        $discountVal = (int) ($totals['discount'] ?? 0);

        $bill = [
            'bill_date' => $invoice['date'] ?? now()->toDateString(),
            'due_date' => $invoice['due_date'] ?? null,
            'bill_number' => $invoice['number'] ?? null,
            'status' => Bill::STATUS_DRAFT,
            'paid_status' => Bill::PAID_STATUS_UNPAID,
            'sub_total' => $subTotal,
            'discount' => $discountVal,
            'discount_val' => $discountVal,
            'total' => $total,
            'tax' => $tax,
            'due_amount' => $total,
            'company_id' => $companyId,
            'currency_id' => $invoiceCurrencyId,
            'exchange_rate' => $exchangeRate,
            'base_total' => $total * $exchangeRate,
            'base_discount_val' => $discountVal * $exchangeRate,
            'base_sub_total' => $subTotal * $exchangeRate,
            'base_tax' => $tax * $exchangeRate,
            'base_due_amount' => $total * $exchangeRate,
        ];

        $items = [];
        foreach ($lineItems as $line) {
            $qty = max((float) ($line['quantity'] ?? 1), 0.001);
            $unitPrice = $line['unit_price'] ?? null;
            $lineTotal = $line['total'] ?? null;

            // Derive unit price: prefer explicit, fall back to total/qty
            if ($unitPrice !== null) {
                $price = (int) round((float) $unitPrice);
            } elseif ($lineTotal !== null) {
                $price = (int) round((float) $lineTotal / $qty);
            } else {
                $price = 0;
            }

            $itemTotal = (int) round((float) ($lineTotal ?? ($qty * $price)));
            $itemTax = (int) round((float) ($line['tax'] ?? 0));
            $itemDiscount = (int) round((float) ($line['discount'] ?? 0));

            // Avoid duplicate display when name and description are identical
            $name = $line['name'] ?? $line['description'] ?? 'Item';
            $desc = $line['description'] ?? null;
            if ($desc !== null && $desc === $name) {
                $desc = null;
            }

            $items[] = [
                'name' => $name,
                'description' => $desc,
                'quantity' => (float) ($line['quantity'] ?? 1),
                'price' => $price,
                'discount' => $itemDiscount,
                'discount_val' => $itemDiscount,
                'tax' => $itemTax,
                'total' => $itemTotal,
                'base_price' => (int) round($price * $exchangeRate),
                'base_total' => (int) round($itemTotal * $exchangeRate),
                'base_tax' => (int) round($itemTax * $exchangeRate),
                'base_discount_val' => (int) round($itemDiscount * $exchangeRate),
            ];
        }

        return [
            'supplier' => [
                'name' => $supplier['name'] ?? null,
                'tax_id' => $supplier['tax_id'] ?? null,
                'address' => $supplier['address'] ?? null,
                'email' => $supplier['email'] ?? null,
            ],
            'bill' => $bill,
            'items' => $items,
        ];
    }
}
