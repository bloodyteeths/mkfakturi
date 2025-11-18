<?php

namespace App\Services\InvoiceParsing;

use App\Models\Bill;
use App\Models\CompanySetting;

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

        $companyCurrency = CompanySetting::getSetting('currency', $companyId);
        $invoiceCurrency = $invoice['currency'] ?? $companyCurrency;
        $exchangeRate = $companyCurrency && $invoiceCurrency && $invoiceCurrency !== $companyCurrency
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
            'currency_id' => $invoiceCurrency,
            'exchange_rate' => $exchangeRate,
            'base_total' => $total * $exchangeRate,
            'base_discount_val' => $discountVal * $exchangeRate,
            'base_sub_total' => $subTotal * $exchangeRate,
            'base_tax' => $tax * $exchangeRate,
            'base_due_amount' => $total * $exchangeRate,
        ];

        $items = [];
        foreach ($lineItems as $line) {
            $qty = (float) ($line['quantity'] ?? 1);
            $price = (float) ($line['unit_price'] ?? $line['total'] ?? 0);
            $itemTotal = (int) ($line['total'] ?? ($qty * $price));
            $itemTax = (int) ($line['tax'] ?? 0);
            $itemDiscount = (int) ($line['discount'] ?? 0);

            $items[] = [
                'name' => $line['name'] ?? $line['description'] ?? 'Item',
                'description' => $line['description'] ?? null,
                'quantity' => $qty,
                'price' => $price,
                'discount' => $itemDiscount,
                'discount_val' => $itemDiscount,
                'tax' => $itemTax,
                'total' => $itemTotal,
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
