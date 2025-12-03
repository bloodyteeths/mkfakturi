<?php

namespace App\Http\Requests;

use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoicesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.s
     */
    public function rules(): array
    {
        $rules = [
            'invoice_date' => [
                'required',
            ],
            'due_date' => [
                'nullable',
            ],
            'customer_id' => [
                'required',
            ],
            'invoice_number' => [
                'required',
                Rule::unique('invoices')->where('company_id', $this->header('company')),
            ],
            'exchange_rate' => [
                'nullable',
            ],
            'discount' => [
                'numeric',
                'required',
            ],
            'discount_val' => [
                'integer',
                'required',
            ],
            'sub_total' => [
                'numeric',
                'required',
            ],
            'total' => [
                'numeric',
                'max:999999999999',
                'required',
            ],
            'tax' => [
                'required',
            ],
            'template_name' => [
                'required',
            ],
            'items' => [
                'required',
                'array',
            ],
            'items.*' => [
                'required',
                'max:255',
            ],
            'items.*.description' => [
                'nullable',
            ],
            'items.*.name' => [
                'required',
            ],
            'items.*.quantity' => [
                'numeric',
                'required',
            ],
            'items.*.price' => [
                'numeric',
                'required',
            ],
            'project_id' => [
                'nullable',
                'integer',
                'exists:projects,id',
            ],
        ];

        $companyCurrency = CompanySetting::getSetting('currency', $this->header('company'));

        $customer = Customer::find($this->customer_id);

        if ($customer && $companyCurrency) {
            if ((string) $customer->currency_id !== $companyCurrency) {
                $rules['exchange_rate'] = [
                    'required',
                ];
            }
        }

        if ($this->isMethod('PUT')) {
            $rules['invoice_number'] = [
                'required',
                Rule::unique('invoices')
                    ->ignore($this->route('invoice')->id)
                    ->where('company_id', $this->header('company')),
            ];
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     * Adds stock availability validation for tracked items.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateStockAvailability($validator);
        });
    }

    /**
     * Validate stock availability for items with track_quantity enabled.
     * Only validates if stock module is enabled and negative stock is not allowed.
     */
    protected function validateStockAvailability($validator): void
    {
        // Skip if stock module is not enabled
        if (! StockService::isEnabled()) {
            return;
        }

        // Check if company allows negative stock
        $companyId = $this->header('company');
        $allowNegative = CompanySetting::getSetting('allow_negative_stock', $companyId);
        if ($allowNegative === 'YES') {
            return;
        }

        $items = $this->input('items', []);
        if (empty($items)) {
            return;
        }

        $stockService = app(StockService::class);
        $insufficientItems = [];

        foreach ($items as $index => $itemData) {
            // Skip items without item_id (custom items)
            if (empty($itemData['item_id'])) {
                continue;
            }

            $item = Item::where('company_id', $companyId)
                ->where('id', $itemData['item_id'])
                ->first();

            // Skip items that don't track quantity
            if (! $item || ! $item->track_quantity) {
                continue;
            }

            $requestedQty = (float) ($itemData['quantity'] ?? 0);

            // Get stock across ALL warehouses (not just a specific one)
            // This is more flexible and handles cases where:
            // 1. Items were created before stock movements were implemented
            // 2. Stock is spread across multiple warehouses
            $stock = $stockService->getItemStock($companyId, $item->id, null);
            $availableQty = $stock['quantity'];

            // If no stock movements exist, fall back to the item's quantity field
            // This handles legacy items created before the stock module
            if ($availableQty == 0 && $item->quantity > 0) {
                $availableQty = (float) $item->quantity;
            }

            // For updates, we need to account for the existing invoice item quantity
            if ($this->isMethod('PUT')) {
                $existingItem = $this->route('invoice')
                    ?->items()
                    ->where('item_id', $item->id)
                    ->first();
                if ($existingItem) {
                    // Add back the existing quantity since it will be replaced
                    $availableQty += (float) $existingItem->quantity;
                }
            }

            // Check if there's enough stock
            if ($availableQty < $requestedQty) {
                $insufficientItems[] = [
                    'index' => $index,
                    'item_name' => $item->name,
                    'requested' => $requestedQty,
                    'available' => $availableQty,
                ];
            }
        }

        // Add validation errors for insufficient stock
        foreach ($insufficientItems as $insufficient) {
            $validator->errors()->add(
                "items.{$insufficient['index']}.quantity",
                __('stock.insufficient_stock_for_item', [
                    'item' => $insufficient['item_name'],
                    'available' => $insufficient['available'],
                    'requested' => $insufficient['requested'],
                ])
            );
        }
    }

    public function getInvoicePayload(): array
    {
        $company_currency = CompanySetting::getSetting('currency', $this->header('company'));
        $current_currency = $this->currency_id;
        $exchange_rate = $company_currency != $current_currency ? $this->exchange_rate : 1;
        $currency = Customer::find($this->customer_id)->currency_id;

        return collect($this->except('items', 'taxes'))
            ->merge([
                'creator_id' => $this->user()->id ?? null,
                'status' => $this->has('invoiceSend') ? Invoice::STATUS_SENT : Invoice::STATUS_DRAFT,
                'paid_status' => Invoice::STATUS_UNPAID,
                'company_id' => $this->header('company'),
                'tax_per_item' => CompanySetting::getSetting('tax_per_item', $this->header('company')) ?? 'NO ',
                'discount_per_item' => CompanySetting::getSetting('discount_per_item', $this->header('company')) ?? 'NO',
                'due_amount' => $this->total,
                'sent' => (bool) $this->sent ?? false,
                'viewed' => (bool) $this->viewed ?? false,
                'exchange_rate' => $exchange_rate,
                'base_total' => $this->total * $exchange_rate,
                'base_discount_val' => $this->discount_val * $exchange_rate,
                'base_sub_total' => $this->sub_total * $exchange_rate,
                'base_tax' => $this->tax * $exchange_rate,
                'base_due_amount' => $this->total * $exchange_rate,
                'currency_id' => $currency,
                'project_id' => $this->project_id,
            ])
            ->toArray();
    }
}
