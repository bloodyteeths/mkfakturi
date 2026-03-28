<?php

namespace Modules\Mk\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Payment;
use App\Models\TaxType;
use App\Services\SerialNumberFormatter;
use App\Services\UsageLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class POSSaleController extends Controller
{
    /**
     * POST /api/v1/pos/sale
     *
     * Create invoice + items + payment in one atomic operation.
     * Triggers: StockInvoiceItemObserver (stock deduction), InvoiceObserver (IFRS posting),
     * PaymentObserver (payment IFRS posting). All existing observers fire automatically.
     */
    public function sale(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        // --- Validate ---
        try {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.item_id' => 'required|integer|exists:items,id',
                'items.*.quantity' => 'required|numeric|gt:0',
                'items.*.price' => 'nullable|integer',
                'items.*.discount' => 'nullable|numeric|min:0|max:100',
                'customer_id' => 'nullable|integer|exists:customers,id',
                'payment_method' => 'required|string|in:cash,card,mixed',
                'cash_received' => 'nullable|integer|min:0',
                'cash_amount' => 'nullable|integer|min:0',
                'card_amount' => 'nullable|integer|min:0',
                'fiscal_device_id' => 'nullable|integer|exists:fiscal_devices,id',
                'warehouse_id' => 'nullable|integer|exists:warehouses,id',
                'notes' => 'nullable|string|max:500',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        // --- Check POS transaction limit ---
        $company = \App\Models\Company::find($companyId);
        $usageService = app(UsageLimitService::class);

        if (! $usageService->canUse($company, 'pos_transactions_per_month')) {
            return response()->json(
                $usageService->buildLimitExceededResponse($company, 'pos_transactions_per_month'),
                402
            );
        }

        // --- Resolve customer (walk-in or specified) ---
        $customerId = $validated['customer_id'] ?? null;
        if (! $customerId) {
            $customerId = $this->getOrCreateWalkInCustomer($companyId);
        }

        $customer = Customer::find($customerId);
        if (! $customer) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        // --- Build items with tax calculation ---
        $invoiceItems = [];
        $subTotal = 0;
        $totalTax = 0;
        $stockWarnings = [];
        $taxPerItem = CompanySetting::getSetting('tax_per_item', $companyId) ?? 'NO';

        foreach ($validated['items'] as $itemData) {
            $item = Item::where('id', $itemData['item_id'])
                ->where('company_id', $companyId)
                ->with('taxes')
                ->first();

            if (! $item) {
                return response()->json([
                    'error' => "Item #{$itemData['item_id']} not found in this company",
                ], 422);
            }

            $price = $itemData['price'] ?? $item->retail_price ?? $item->price ?? 0;
            $quantity = (float) $itemData['quantity'];
            $discountPercent = (float) ($itemData['discount'] ?? 0);
            $discountVal = (int) round($price * $quantity * $discountPercent / 100);
            $itemSubTotal = (int) ($price * $quantity) - $discountVal;

            // Calculate tax from item's default taxes
            $itemTaxes = [];
            $itemTaxAmount = 0;

            foreach ($item->taxes as $tax) {
                $taxType = TaxType::find($tax->tax_type_id);
                if ($taxType) {
                    $taxAmount = (int) round($itemSubTotal * $taxType->percent / 100);
                    $itemTaxAmount += $taxAmount;
                    $itemTaxes[] = [
                        'tax_type_id' => $taxType->id,
                        'name' => $taxType->name,
                        'percent' => $taxType->percent,
                        'amount' => $taxAmount,
                        'compound_tax' => $taxType->compound_tax ?? false,
                    ];
                }
            }

            $itemTotal = $itemSubTotal + $itemTaxAmount;

            $invoiceItems[] = [
                'item_id' => $item->id,
                'name' => $item->name,
                'description' => $item->description ?? '',
                'quantity' => $quantity,
                'price' => $price,
                'discount' => $discountPercent,
                'discount_val' => $discountVal,
                'tax' => $itemTaxAmount,
                'total' => $itemTotal,
                'unit_name' => $item->unit?->name ?? '',
                'warehouse_id' => $validated['warehouse_id'] ?? null,
                'taxes' => $itemTaxes,
            ];

            $subTotal += $itemSubTotal;
            $totalTax += $itemTaxAmount;

            // Check stock warnings (low stock after sale)
            if ($item->track_quantity) {
                $currentQty = (float) $item->quantity;
                $remainingQty = $currentQty - $quantity;
                $minimumQty = (float) ($item->minimum_stock_level ?? $item->minimum_quantity ?? 0);
                if ($remainingQty <= $minimumQty) {
                    $stockWarnings[] = [
                        'item_id' => $item->id,
                        'name' => $item->name,
                        'remaining_qty' => $remainingQty,
                        'minimum_qty' => $minimumQty,
                    ];
                }
            }
        }

        $total = $subTotal + $totalTax;

        // --- Generate invoice number with optional POS prefix ---
        $invoiceNumber = (new SerialNumberFormatter())
            ->setModel(new Invoice())
            ->setCompany($companyId)
            ->setCustomer($customerId)
            ->getNextNumber();

        $posPrefix = CompanySetting::getSetting('pos_invoice_prefix', $companyId);
        if ($posPrefix) {
            $invoiceNumber = $posPrefix.$invoiceNumber;
        }

        // --- Create invoice + items + payment atomically ---
        try {
            $result = DB::transaction(function () use (
                $request, $companyId, $customerId, $customer, $invoiceItems,
                $subTotal, $totalTax, $total, $invoiceNumber, $validated, $taxPerItem
            ) {
                // Build a fake request object that Invoice::createInvoice() expects
                $invoiceRequest = new \Illuminate\Http\Request();
                $invoiceRequest->headers->set('company', $companyId);
                $invoiceRequest->setUserResolver(fn () => $request->user());

                $companyCurrency = CompanySetting::getSetting('currency', $companyId);
                $customerCurrency = $customer->currency_id ?? $companyCurrency;

                $invoiceData = [
                    'invoice_date' => now()->format('Y-m-d'),
                    'due_date' => now()->format('Y-m-d'),
                    'customer_id' => $customerId,
                    'invoice_number' => $invoiceNumber,
                    'sub_total' => $subTotal,
                    'total' => $total,
                    'tax' => $totalTax,
                    'discount' => 0,
                    'discount_val' => 0,
                    'discount_type' => 'fixed',
                    'template_name' => 'invoice1',
                    'notes' => $validated['notes'] ?? '[POS] Sale',
                    'currency_id' => $customerCurrency,
                    'exchange_rate' => 1,
                    'invoiceSend' => true,  // Status = SENT (triggers IFRS posting)
                    'items' => $invoiceItems,
                    'type' => 'standard',
                    'is_reverse_charge' => false,
                ];

                $invoiceRequest->replace($invoiceData);

                // Manually set the payload method on request so Invoice::createInvoice works
                $invoiceRequest->macro('getInvoicePayload', function () use ($invoiceData, $companyId, $request, $customerCurrency, $taxPerItem) {
                    return [
                        'invoice_date' => $invoiceData['invoice_date'],
                        'due_date' => $invoiceData['due_date'],
                        'customer_id' => $invoiceData['customer_id'],
                        'invoice_number' => $invoiceData['invoice_number'],
                        'sub_total' => $invoiceData['sub_total'],
                        'total' => $invoiceData['total'],
                        'tax' => $invoiceData['tax'],
                        'discount' => 0,
                        'discount_val' => 0,
                        'discount_type' => 'fixed',
                        'template_name' => 'invoice1',
                        'notes' => $invoiceData['notes'],
                        'type' => 'standard',
                        'is_reverse_charge' => false,
                        'creator_id' => $request->user()->id ?? null,
                        'status' => Invoice::STATUS_SENT,
                        'paid_status' => Invoice::STATUS_UNPAID,
                        'company_id' => $companyId,
                        'tax_per_item' => $taxPerItem,
                        'discount_per_item' => CompanySetting::getSetting('discount_per_item', $companyId) ?? 'NO',
                        'due_amount' => $invoiceData['total'],
                        'sent' => false,
                        'viewed' => false,
                        'exchange_rate' => 1,
                        'base_total' => $invoiceData['total'],
                        'base_discount_val' => 0,
                        'base_sub_total' => $invoiceData['sub_total'],
                        'base_tax' => $invoiceData['tax'],
                        'base_due_amount' => $invoiceData['total'],
                        'currency_id' => $customerCurrency,
                        'project_id' => null,
                    ];
                });

                // Create invoice (fires InvoiceObserver + StockInvoiceItemObserver)
                $invoice = Invoice::createInvoice($invoiceRequest);

                // --- Create payment(s) ---
                $payments = [];

                if ($validated['payment_method'] === 'mixed') {
                    // Split payment: cash + card
                    $cashAmount = $validated['cash_amount'] ?? 0;
                    $cardAmount = $validated['card_amount'] ?? ($total - $cashAmount);

                    foreach (['cash' => $cashAmount, 'card' => $cardAmount] as $method => $amount) {
                        if ($amount <= 0) {
                            continue;
                        }
                        $payments[] = $this->createPosPayment(
                            $request, $companyId, $customerId, $customerCurrency,
                            $invoice->id, $amount, $method
                        );
                    }
                } else {
                    $payments[] = $this->createPosPayment(
                        $request, $companyId, $customerId, $customerCurrency,
                        $invoice->id, $total, $validated['payment_method']
                    );
                }

                return [
                    'invoice' => $invoice,
                    'payment' => $payments[0],
                    'payments' => $payments,
                ];
            });

            // --- Build fiscal data for WebSerial ---
            $fiscalData = $this->buildFiscalData($result['invoice']);

            // --- Increment POS transaction usage (after successful transaction) ---
            $usageService->incrementUsage($company, 'pos_transactions_per_month');

            // --- Calculate change ---
            $cashReceived = $validated['cash_received'] ?? $total;
            $change = max(0, $cashReceived - $total);

            return response()->json([
                'invoice' => $result['invoice'],
                'payment' => [
                    'id' => $result['payment']->id,
                    'amount' => $result['payment']->amount,
                    'payment_number' => $result['payment']->payment_number,
                    'change' => $change,
                ],
                'fiscal_data' => $fiscalData,
                'stock_warnings' => $stockWarnings,
            ], 201);

        } catch (\Exception $e) {
            Log::error('POS sale failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'company_id' => $companyId,
            ]);

            return response()->json([
                'error' => 'Sale failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/pos/catalog
     *
     * Fast item lookup optimized for POS — cached, minimal fields.
     */
    public function catalog(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        $items = Item::where('company_id', $companyId)
            ->select([
                'id', 'name', 'barcode', 'sku', 'price', 'retail_price',
                'unit_id', 'category_id', 'track_quantity', 'quantity',
                'description',
            ])
            ->with([
                'unit:id,name',
                'taxes' => function ($q) {
                    $q->select('id', 'item_id', 'tax_type_id', 'amount', 'percent');
                },
                'taxes.taxType:id,name,percent',
            ])
            ->orderBy('name')
            ->get()
            ->map(function ($item) {
                $taxPercent = 0;
                if ($item->taxes->isNotEmpty()) {
                    $tax = $item->taxes->first();
                    $taxPercent = $tax->taxType?->percent ?? $tax->percent ?? 0;
                }

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'barcode' => $item->barcode,
                    'sku' => $item->sku,
                    'retail_price' => $item->retail_price ?? $item->price ?? 0,
                    'price' => $item->price ?? 0,
                    'unit_name' => $item->unit?->name ?? '',
                    'category_id' => $item->category_id,
                    'tax_percent' => $taxPercent,
                    'track_quantity' => (bool) $item->track_quantity,
                    'quantity' => (float) ($item->quantity ?? 0),
                    'image_url' => $item->image_url,
                ];
            });

        $categories = ItemCategory::where('company_id', $companyId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $taxTypes = TaxType::where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)->orWhereNull('company_id');
        })
            ->select('id', 'name', 'percent')
            ->orderBy('name')
            ->get();

        // POS usage stats
        $company = \App\Models\Company::find($companyId);
        $usageService = app(UsageLimitService::class);
        $posUsage = $usageService->getUsage($company, 'pos_transactions_per_month');

        // POS feature settings
        $posSettings = [
            'numpad_enabled' => CompanySetting::getSetting('pos_numpad_enabled', $companyId) !== 'NO',
            'sound_enabled' => CompanySetting::getSetting('pos_sound_enabled', $companyId) !== 'NO',
            'restaurant_mode' => CompanySetting::getSetting('pos_restaurant_mode', $companyId) === 'YES',
            'table_count' => (int) (CompanySetting::getSetting('pos_table_count', $companyId) ?: 20),
            'kitchen_printing' => CompanySetting::getSetting('pos_kitchen_printing', $companyId) === 'YES',
            'split_payment' => CompanySetting::getSetting('pos_split_payment', $companyId) === 'YES',
            'return_enabled' => CompanySetting::getSetting('pos_return_enabled', $companyId) === 'YES',
            'casys_qr' => CompanySetting::getSetting('pos_casys_qr', $companyId) === 'YES',
            'barcode_camera' => CompanySetting::getSetting('pos_barcode_camera', $companyId) === 'YES',
            'auto_print' => CompanySetting::getSetting('pos_auto_print', $companyId) === 'YES',
            'show_vat' => CompanySetting::getSetting('pos_show_vat', $companyId) !== 'NO',
        ];

        return response()->json([
            'items' => $items,
            'categories' => $categories,
            'tax_types' => $taxTypes,
            'pos_usage' => $posUsage,
            'pos_settings' => $posSettings,
        ]);
    }

    /**
     * GET /api/v1/pos/barcode/{code}
     *
     * Instant barcode/SKU lookup — returns single item or 404.
     */
    public function barcodeLookup(Request $request, string $code): JsonResponse
    {
        $companyId = $request->header('company');

        $item = Item::where('company_id', $companyId)
            ->where(function ($q) use ($code) {
                $q->where('barcode', $code)
                    ->orWhere('sku', $code);
            })
            ->with([
                'unit:id,name',
                'taxes' => function ($q) {
                    $q->select('id', 'item_id', 'tax_type_id', 'amount', 'percent');
                },
                'taxes.taxType:id,name,percent',
            ])
            ->first();

        if (! $item) {
            return response()->json(['error' => 'Item not found'], 404);
        }

        $taxPercent = 0;
        if ($item->taxes->isNotEmpty()) {
            $tax = $item->taxes->first();
            $taxPercent = $tax->taxType?->percent ?? $tax->percent ?? 0;
        }

        return response()->json([
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'barcode' => $item->barcode,
                'sku' => $item->sku,
                'retail_price' => $item->retail_price ?? $item->price ?? 0,
                'price' => $item->price ?? 0,
                'unit_name' => $item->unit?->name ?? '',
                'category_id' => $item->category_id,
                'tax_percent' => $taxPercent,
                'track_quantity' => (bool) $item->track_quantity,
                'quantity' => (float) ($item->quantity ?? 0),
            ],
        ]);
    }

    /**
     * GET /api/v1/pos/invoice-lookup?number=XXX
     *
     * Find an invoice by number for POS returns.
     */
    public function invoiceLookup(Request $request): JsonResponse
    {
        $companyId = $request->header('company');
        $number = $request->input('number');

        if (! $number) {
            return response()->json(['error' => 'Invoice number required'], 422);
        }

        $invoice = Invoice::where('company_id', $companyId)
            ->where('invoice_number', 'LIKE', '%' . $number . '%')
            ->with(['items:id,invoice_id,item_id,name,price,quantity'])
            ->first();

        if (! $invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        return response()->json([
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total' => $invoice->total,
                'invoice_date' => $invoice->invoice_date,
                'items' => $invoice->items->map(fn ($i) => [
                    'item_id' => $i->item_id,
                    'name' => $i->name,
                    'price' => $i->price,
                    'quantity' => (int) $i->quantity,
                ]),
            ],
        ]);
    }

    /**
     * Get or create a walk-in "POS Customer" for the company.
     */
    protected function getOrCreateWalkInCustomer(int $companyId): int
    {
        $locale = CompanySetting::getSetting('language', $companyId) ?? 'mk';
        $walkInName = match ($locale) {
            'sq' => 'POS Blerës',
            'tr' => 'POS Müşteri',
            'en' => 'POS Customer',
            default => 'POS Купувач',
        };

        // Search for any existing POS walk-in customer (any locale name)
        $posCustomer = Customer::where('company_id', $companyId)
            ->where(function ($q) {
                $q->where('name', 'POS Купувач')
                    ->orWhere('name', 'POS Blerës')
                    ->orWhere('name', 'POS Müşteri')
                    ->orWhere('name', 'POS Customer');
            })
            ->first();

        if ($posCustomer) {
            return $posCustomer->id;
        }

        $companyCurrency = CompanySetting::getSetting('currency', $companyId);

        $posCustomer = Customer::create([
            'name' => $walkInName,
            'company_id' => $companyId,
            'currency_id' => $companyCurrency,
            'type' => 'customer',
        ]);

        return $posCustomer->id;
    }

    /**
     * Resolve payment method ID from string (cash/card).
     */
    protected function resolvePaymentMethodId(int $companyId, string $method): ?int
    {
        $search = match ($method) {
            'cash' => ['Cash', 'Готовина', 'cash'],
            'card' => ['Card', 'Картичка', 'Credit Card', 'Debit Card', 'card'],
            default => ['Cash', 'Готовина', 'cash'],
        };

        $paymentMethod = \App\Models\PaymentMethod::where('company_id', $companyId)
            ->where(function ($q) use ($search) {
                foreach ($search as $term) {
                    $q->orWhere('name', 'LIKE', "%{$term}%");
                }
            })
            ->first();

        return $paymentMethod?->id;
    }

    /**
     * Create a single POS payment record.
     */
    protected function createPosPayment(
        Request $request,
        int $companyId,
        int $customerId,
        int $customerCurrency,
        int $invoiceId,
        int $amount,
        string $method
    ): Payment {
        $paymentMethodId = $this->resolvePaymentMethodId($companyId, $method);

        $paymentNumber = (new SerialNumberFormatter())
            ->setModel(new Payment())
            ->setCompany($companyId)
            ->setCustomer($customerId)
            ->getNextNumber();

        $paymentRequest = new \Illuminate\Http\Request();
        $paymentRequest->headers->set('company', $companyId);
        $paymentRequest->setUserResolver(fn () => $request->user());

        $paymentData = [
            'payment_date' => now()->format('Y-m-d'),
            'customer_id' => $customerId,
            'amount' => $amount,
            'payment_number' => $paymentNumber,
            'invoice_id' => $invoiceId,
            'payment_method_id' => $paymentMethodId,
            'notes' => 'POS Payment (' . ucfirst($method) . ')',
            'currency_id' => $customerCurrency,
            'exchange_rate' => 1,
        ];

        $paymentRequest->replace($paymentData);
        $paymentRequest->macro('getPaymentPayload', function () use ($paymentData, $companyId, $request, $customerCurrency) {
            return [
                'payment_date' => $paymentData['payment_date'],
                'customer_id' => $paymentData['customer_id'],
                'amount' => $paymentData['amount'],
                'payment_number' => $paymentData['payment_number'],
                'invoice_id' => $paymentData['invoice_id'],
                'payment_method_id' => $paymentData['payment_method_id'],
                'notes' => $paymentData['notes'],
                'creator_id' => $request->user()->id ?? null,
                'company_id' => $companyId,
                'exchange_rate' => 1,
                'base_amount' => $paymentData['amount'],
                'currency_id' => $customerCurrency,
                'project_id' => null,
            ];
        });

        return Payment::createPayment($paymentRequest);
    }

    /**
     * POST /api/v1/pos/return
     *
     * Process a POS return/storno — creates credit note, reverses stock, processes refund.
     * All operations are atomic: credit note + stock reversal + invoice status update.
     */
    public function returnSale(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        try {
            $validated = $request->validate([
                'invoice_id' => 'required|integer|exists:invoices,id',
                'items' => 'nullable|array',
                'items.*.item_id' => 'required|integer',
                'items.*.quantity' => 'required|numeric|gt:0',
                'reason' => 'nullable|string|max:500',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $invoice = Invoice::where('id', $validated['invoice_id'])
            ->where('company_id', $companyId)
            ->with(['items.taxes.taxType', 'customer'])
            ->first();

        if (! $invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        // Determine which items to return (all if not specified)
        $returnItems = [];
        if (! empty($validated['items'])) {
            foreach ($validated['items'] as $ri) {
                $invoiceItem = $invoice->items->firstWhere('id', $ri['invoice_item_id']);
                if ($invoiceItem) {
                    $returnItems[] = [
                        'item' => $invoiceItem,
                        'quantity' => min($ri['quantity'], $invoiceItem->quantity),
                    ];
                }
            }
        } else {
            // Full return
            foreach ($invoice->items as $item) {
                $returnItems[] = [
                    'item' => $item,
                    'quantity' => $item->quantity,
                ];
            }
        }

        if (empty($returnItems)) {
            return response()->json(['error' => 'No items to return'], 422);
        }

        // Calculate return totals
        $returnSubTotal = 0;
        $returnTax = 0;
        foreach ($returnItems as $ri) {
            $ratio = $ri['quantity'] / $ri['item']->quantity;
            $returnSubTotal += (int) round(($ri['item']->total - $ri['item']->tax) * $ratio);
            $returnTax += (int) round($ri['item']->tax * $ratio);
        }
        $returnTotal = $returnSubTotal + $returnTax;

        $reason = $validated['reason'] ?? '[POS] Return';

        try {
            $result = DB::transaction(function () use (
                $request, $companyId, $invoice, $returnItems,
                $returnSubTotal, $returnTax, $returnTotal, $reason
            ) {
                $companyCurrency = CompanySetting::getSetting('currency', $companyId);
                $taxPerItem = CompanySetting::getSetting('tax_per_item', $companyId) ?? 'NO';

                // --- 1. Create Credit Note ---
                $creditNoteItems = [];
                foreach ($returnItems as $ri) {
                    $invoiceItem = $ri['item'];
                    $ratio = $ri['quantity'] / $invoiceItem->quantity;

                    $itemSubTotal = (int) round(($invoiceItem->total - $invoiceItem->tax) * $ratio);
                    $itemTax = (int) round($invoiceItem->tax * $ratio);
                    $itemTotal = $itemSubTotal + $itemTax;
                    $itemDiscount = (int) round($invoiceItem->discount_val * $ratio);

                    $itemTaxes = [];
                    if ($invoiceItem->taxes) {
                        foreach ($invoiceItem->taxes as $tax) {
                            $taxAmount = (int) round($tax->amount * $ratio);
                            $itemTaxes[] = [
                                'tax_type_id' => $tax->tax_type_id,
                                'name' => $tax->name ?? $tax->taxType?->name ?? '',
                                'percent' => $tax->percent ?? $tax->taxType?->percent ?? 0,
                                'amount' => $taxAmount,
                                'compound_tax' => $tax->compound_tax ?? false,
                            ];
                        }
                    }

                    $creditNoteItems[] = [
                        'item_id' => $invoiceItem->item_id,
                        'name' => $invoiceItem->name,
                        'description' => $reason,
                        'quantity' => $ri['quantity'],
                        'price' => $invoiceItem->price,
                        'discount' => $invoiceItem->discount ?? 0,
                        'discount_val' => $itemDiscount,
                        'tax' => $itemTax,
                        'total' => $itemTotal,
                        'unit_name' => $invoiceItem->unit_name ?? '',
                        'taxes' => $itemTaxes,
                    ];
                }

                // Build credit note request
                $cnRequest = new \Illuminate\Http\Request();
                $cnRequest->headers->set('company', $companyId);
                $cnRequest->setUserResolver(fn () => $request->user());

                $cnData = [
                    'credit_note_date' => now()->format('Y-m-d'),
                    'customer_id' => $invoice->customer_id,
                    'invoice_id' => $invoice->id,
                    'sub_total' => $returnSubTotal,
                    'total' => $returnTotal,
                    'tax' => $returnTax,
                    'discount' => 0,
                    'discount_val' => 0,
                    'discount_type' => 'fixed',
                    'template_name' => 'credit_note1',
                    'notes' => $reason,
                    'currency_id' => $invoice->currency_id ?? $companyCurrency,
                    'exchange_rate' => 1,
                    'items' => $creditNoteItems,
                    'creditNoteSend' => true,
                ];

                $cnRequest->replace($cnData);
                $cnRequest->macro('getCreditNotePayload', function () use ($cnData, $companyId, $request, $taxPerItem) {
                    return [
                        'credit_note_date' => $cnData['credit_note_date'],
                        'customer_id' => $cnData['customer_id'],
                        'invoice_id' => $cnData['invoice_id'],
                        'sub_total' => $cnData['sub_total'],
                        'total' => $cnData['total'],
                        'tax' => $cnData['tax'],
                        'discount' => 0,
                        'discount_val' => 0,
                        'discount_type' => 'fixed',
                        'template_name' => 'credit_note1',
                        'notes' => $cnData['notes'],
                        'creator_id' => $request->user()->id ?? null,
                        'status' => \App\Models\CreditNote::STATUS_COMPLETED,
                        'company_id' => $companyId,
                        'tax_per_item' => $taxPerItem,
                        'discount_per_item' => CompanySetting::getSetting('discount_per_item', $companyId) ?? 'NO',
                        'exchange_rate' => 1,
                        'base_total' => $cnData['total'],
                        'base_discount_val' => 0,
                        'base_sub_total' => $cnData['sub_total'],
                        'base_tax' => $cnData['tax'],
                        'currency_id' => $cnData['currency_id'],
                    ];
                });

                $creditNote = \App\Models\CreditNote::createCreditNote($cnRequest);

                // --- 2. Reverse stock for returned items ---
                $stockService = app(\App\Services\StockService::class);
                if (\App\Services\StockService::isEnabled()) {
                    $defaultWarehouse = \App\Models\Warehouse::getOrCreateDefault($companyId);

                    foreach ($returnItems as $ri) {
                        $item = Item::find($ri['item']->item_id);
                        if ($item && $item->track_quantity) {
                            $wac = $item->wac_cost ?? $ri['item']->price ?? 0;
                            $stockService->recordStockIn(
                                $companyId,
                                $ri['item']->warehouse_id ?? $defaultWarehouse->id,
                                $ri['item']->item_id,
                                (float) $ri['quantity'],
                                $wac,
                                \App\Models\StockMovement::SOURCE_RETURN,
                                $creditNote->id,
                                now()->format('Y-m-d'),
                                "Stock IN from POS Return — CN #{$creditNote->credit_note_number}",
                                [
                                    'credit_note_id' => $creditNote->id,
                                    'original_invoice_id' => $invoice->id,
                                ],
                                $request->user()->id ?? null
                            );
                        }
                    }
                }

                // --- 3. Update original invoice paid status ---
                $invoice->updateInvoiceStatus($returnTotal);

                return [
                    'credit_note' => $creditNote,
                ];
            });

            // Build fiscal storno data
            $stornoData = $this->buildStornoFiscalData($invoice, $returnItems, $returnTotal, $returnTax);

            return response()->json([
                'storno' => true,
                'credit_note_id' => $result['credit_note']->id,
                'credit_note_number' => $result['credit_note']->credit_note_number,
                'original_invoice_id' => $invoice->id,
                'original_invoice_number' => $invoice->invoice_number,
                'return_total' => $returnTotal,
                'return_tax' => $returnTax,
                'return_items' => collect($returnItems)->map(fn ($ri) => [
                    'name' => $ri['item']->name,
                    'quantity' => $ri['quantity'],
                    'price' => $ri['item']->price,
                    'total' => (int) round($ri['item']->total * ($ri['quantity'] / $ri['item']->quantity)),
                ]),
                'fiscal_data' => $stornoData,
                'reason' => $reason,
            ], 201);

        } catch (\Exception $e) {
            Log::error('POS return failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'invoice_id' => $validated['invoice_id'],
                'company_id' => $companyId,
            ]);

            return response()->json([
                'error' => 'Return failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/v1/pos/shift/open
     *
     * Open a new cashier shift.
     */
    public function openShift(Request $request): JsonResponse
    {
        $companyId = $request->header('company');
        $userId = $request->user()->id;

        try {
            $validated = $request->validate([
                'opening_cash' => 'required|integer|min:0',
                'fiscal_device_id' => 'nullable|integer|exists:fiscal_devices,id',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        // Check for already open shift
        $existingShift = DB::table('pos_shifts')
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->whereNull('closed_at')
            ->first();

        if ($existingShift) {
            return response()->json([
                'error' => 'You already have an open shift. Close it first.',
                'shift' => $existingShift,
            ], 409);
        }

        $shiftId = DB::table('pos_shifts')->insertGetId([
            'company_id' => $companyId,
            'user_id' => $userId,
            'fiscal_device_id' => $validated['fiscal_device_id'] ?? null,
            'opened_at' => now(),
            'opening_cash' => $validated['opening_cash'],
            'total_sales' => 0,
            'total_returns' => 0,
            'transactions_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $shift = DB::table('pos_shifts')->find($shiftId);

        return response()->json(['shift' => $shift], 201);
    }

    /**
     * POST /api/v1/pos/shift/close
     *
     * Close the current cashier shift with summary.
     */
    public function closeShift(Request $request): JsonResponse
    {
        $companyId = $request->header('company');
        $userId = $request->user()->id;

        try {
            $validated = $request->validate([
                'closing_cash' => 'required|integer|min:0',
                'notes' => 'nullable|string|max:500',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $shift = DB::table('pos_shifts')
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->whereNull('closed_at')
            ->first();

        if (! $shift) {
            return response()->json(['error' => 'No open shift found'], 404);
        }

        // Calculate shift totals from POS invoices created during shift
        // Uses '[POS]' prefix marker — more reliable than generic '%POS%' LIKE
        $shiftInvoices = Invoice::where('company_id', $companyId)
            ->where('creator_id', $userId)
            ->where('notes', 'LIKE', '[POS]%')
            ->where('created_at', '>=', $shift->opened_at)
            ->get();

        $totalSales = $shiftInvoices->sum('total');
        $transactionsCount = $shiftInvoices->count();

        $expectedCash = $shift->opening_cash + $totalSales - ($shift->total_returns ?? 0);
        $cashDifference = $validated['closing_cash'] - $expectedCash;

        DB::table('pos_shifts')
            ->where('id', $shift->id)
            ->update([
                'closed_at' => now(),
                'closing_cash' => $validated['closing_cash'],
                'total_sales' => $totalSales,
                'transactions_count' => $transactionsCount,
                'cash_difference' => $cashDifference,
                'notes' => $validated['notes'] ?? null,
                'updated_at' => now(),
            ]);

        $updatedShift = DB::table('pos_shifts')->find($shift->id);

        return response()->json([
            'shift' => $updatedShift,
            'summary' => [
                'duration_minutes' => now()->diffInMinutes($shift->opened_at),
                'total_sales' => $totalSales,
                'total_returns' => $shift->total_returns ?? 0,
                'transactions_count' => $transactionsCount,
                'opening_cash' => $shift->opening_cash,
                'closing_cash' => $validated['closing_cash'],
                'expected_cash' => $expectedCash,
                'cash_difference' => $cashDifference,
            ],
        ]);
    }

    /**
     * GET /api/v1/pos/shift/current
     *
     * Get the current open shift for this user.
     */
    public function currentShift(Request $request): JsonResponse
    {
        $companyId = $request->header('company');
        $userId = $request->user()->id;

        $shift = DB::table('pos_shifts')
            ->where('company_id', $companyId)
            ->where('user_id', $userId)
            ->whereNull('closed_at')
            ->first();

        if (! $shift) {
            return response()->json(['shift' => null]);
        }

        // Live stats
        $salesSinceOpen = Invoice::where('company_id', $companyId)
            ->where('creator_id', $userId)
            ->where('notes', 'LIKE', '[POS]%')
            ->where('created_at', '>=', $shift->opened_at)
            ->selectRaw('SUM(total) as total_sales, COUNT(*) as count')
            ->first();

        return response()->json([
            'shift' => $shift,
            'live_stats' => [
                'total_sales' => (int) ($salesSinceOpen->total_sales ?? 0),
                'transactions_count' => (int) ($salesSinceOpen->count ?? 0),
                'duration_minutes' => now()->diffInMinutes($shift->opened_at),
            ],
        ]);
    }

    /**
     * POST /api/v1/pos/casys-checkout
     *
     * Generate a CASYS CPay payment URL + QR code for POS card payments.
     * Uses the company's own CASYS merchant credentials (not platform-level).
     */
    public function casysCheckout(Request $request): JsonResponse
    {
        $companyId = $request->header('company');

        try {
            $validated = $request->validate([
                'amount' => 'required|integer|min:100',
                'description' => 'nullable|string|max:100',
                'invoice_id' => 'nullable|integer|exists:invoices,id',
            ]);
        } catch (ValidationException $e) {
            throw $e;
        }

        $cpayService = app(\Modules\Mk\Services\CpayMerchantService::class);

        if (! $cpayService->isConfigured($companyId)) {
            return response()->json([
                'error' => 'CASYS not configured. Enter your Merchant ID and Auth Key in POS Settings.',
            ], 422);
        }

        // Generate unique order reference
        $orderId = 'CPOS-' . $companyId . '-' . time() . '-' . rand(100, 999);
        $description = $validated['description'] ?? 'POS Payment';

        try {
            $checkout = $cpayService->createCheckout(
                $companyId,
                $validated['amount'],
                $orderId,
                $description
            );

            // Generate QR code
            $qrDataUri = $cpayService->generateQrDataUri($checkout);

            return response()->json([
                'order_id' => $orderId,
                'checkout_url' => $checkout['checkout_url'],
                'form_fields' => $checkout['form_fields'],
                'qr_data_uri' => $qrDataUri,
            ]);
        } catch (\Exception $e) {
            Log::error('CASYS checkout generation failed', [
                'error' => $e->getMessage(),
                'company_id' => $companyId,
            ]);

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * GET /api/v1/pos/casys-status/{orderId}
     *
     * Poll for CASYS payment status (pending/completed/failed).
     */
    public function casysStatus(Request $request, string $orderId): JsonResponse
    {
        $companyId = $request->header('company');
        $cpayService = app(\Modules\Mk\Services\CpayMerchantService::class);

        $status = $cpayService->getPaymentStatus($orderId);

        if (! $status) {
            return response()->json(['status' => 'not_found'], 404);
        }

        // Verify company ownership
        if (($status['company_id'] ?? null) != $companyId) {
            return response()->json(['status' => 'not_found'], 404);
        }

        return response()->json([
            'status' => $status['status'],
            'amount' => $status['amount'] ?? 0,
        ]);
    }

    /**
     * Build fiscal storno receipt data for ISL protocol.
     */
    protected function buildStornoFiscalData(Invoice $invoice, array $returnItems, int $returnTotal, int $returnTax): array
    {
        $items = [];
        foreach ($returnItems as $ri) {
            $invoiceItem = $ri['item'];
            $taxPercent = 0;
            if ($invoiceItem->taxes && $invoiceItem->taxes->isNotEmpty()) {
                $taxPercent = $invoiceItem->taxes->first()->taxType?->percent ?? 0;
            }

            $vatGroup = match (true) {
                $taxPercent >= 18 => 'А',
                $taxPercent >= 10 => 'В',
                $taxPercent >= 5 => 'Б',
                default => 'Г',
            };

            $items[] = [
                'name' => mb_substr($invoiceItem->name, 0, 32),
                'quantity' => $ri['quantity'],
                'price' => $invoiceItem->price / 100,
                'vat_group' => $vatGroup,
                'tax_percent' => $taxPercent,
                'total' => round($invoiceItem->total * ($ri['quantity'] / $invoiceItem->quantity)) / 100,
            ];
        }

        return [
            'storno' => true,
            'original_receipt_number' => $invoice->invoice_number,
            'items' => $items,
            'total' => $returnTotal / 100,
            'tax' => $returnTax / 100,
        ];
    }

    /**
     * Build fiscal receipt data from invoice for WebSerial ISL protocol.
     * Maps to Macedonian fiscal device VAT groups: А=18%, Б=5%, В=10%, Г=0%
     */
    protected function buildFiscalData(Invoice $invoice): array
    {
        $items = [];
        $invoice->load('items.taxes.taxType');

        foreach ($invoice->items as $item) {
            $taxPercent = 0;
            if ($item->taxes->isNotEmpty()) {
                $taxPercent = $item->taxes->first()->taxType?->percent ?? 0;
            }

            // Map to MK fiscal VAT groups
            $vatGroup = match (true) {
                $taxPercent >= 18 => 'А',  // Standard 18%
                $taxPercent >= 10 => 'В',  // Hospitality 10%
                $taxPercent >= 5 => 'Б',   // Reduced 5%
                default => 'Г',            // Zero/Exempt 0%
            };

            $items[] = [
                'name' => mb_substr($item->name, 0, 32),  // ISL max 32 chars
                'quantity' => $item->quantity,
                'price' => $item->price / 100,  // Convert cents to MKD
                'vat_group' => $vatGroup,
                'tax_percent' => $taxPercent,
                'total' => $item->total / 100,
            ];
        }

        return [
            'items' => $items,
            'total' => $invoice->total / 100,
            'tax' => $invoice->tax / 100,
            'invoice_id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
        ];
    }
}

// CLAUDE-CHECKPOINT
