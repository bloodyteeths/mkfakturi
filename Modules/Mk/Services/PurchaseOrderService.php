<?php

namespace Modules\Mk\Services;

use App\Mail\SendPurchaseOrderMail;
use App\Models\Bill;
use App\Models\Company;
use App\Models\CompanySetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Mk\Models\GoodsReceipt;
use Modules\Mk\Models\GoodsReceiptItem;
use Modules\Mk\Models\PurchaseOrder;
use Modules\Mk\Models\PurchaseOrderItem;

class PurchaseOrderService
{
    /**
     * List purchase orders with filters.
     */
    public function list(int $companyId, array $filters): array
    {
        $query = PurchaseOrder::forCompany($companyId)
            ->with(['supplier:id,name', 'createdBy:id,name', 'currency:id,name,code,symbol', 'costCenter:id,name,code,color', 'items'])
            ->orderBy('po_date', 'desc');

        if (!empty($filters['status'])) {
            $query->byStatus($filters['status']);
        }

        if (!empty($filters['supplier_id'])) {
            $query->where('supplier_id', (int) $filters['supplier_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('po_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('po_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'LIKE', '%' . $search . '%')
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'LIKE', '%' . $search . '%');
                  });
            });
        }

        $limit = $filters['limit'] ?? 15;
        if ($limit === 'all') {
            return [
                'data' => $query->get(),
                'meta' => null,
            ];
        }

        $paginator = $query->paginate((int) $limit);

        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }

    /**
     * Create a purchase order with items.
     */
    public function create(int $companyId, array $data, ?int $userId = null): PurchaseOrder
    {
        return DB::transaction(function () use ($companyId, $data, $userId) {
            $subTotal = 0;
            $taxTotal = 0;

            // Calculate totals from items (tax is per-unit, multiply by quantity)
            foreach ($data['items'] as $item) {
                $itemTotal = (int) ($item['price'] * $item['quantity']);
                $itemTax = (int) (($item['tax'] ?? 0) * $item['quantity']);
                $subTotal += $itemTotal;
                $taxTotal += $itemTax;
            }

            $total = $subTotal + $taxTotal;

            $currencyId = $data['currency_id'] ?? null;
            if (empty($currencyId)) {
                $currencyId = CompanySetting::getSetting('currency', $companyId);
            }

            $po = PurchaseOrder::create([
                'company_id' => $companyId,
                'supplier_id' => $data['supplier_id'] ?? null,
                'po_date' => $data['po_date'] ?? now()->toDateString(),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'status' => 'draft',
                'sub_total' => $subTotal,
                'tax' => $taxTotal,
                'total' => $total,
                'currency_id' => $currencyId,
                'warehouse_id' => $data['warehouse_id'] ?? null,
                'cost_center_id' => $data['cost_center_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            // Create items (tax stored per-unit, total = (price + tax) * qty)
            foreach ($data['items'] as $item) {
                $qty = (float) $item['quantity'];
                $unitPrice = (int) $item['price'];
                $unitTax = (int) ($item['tax'] ?? 0);
                $itemTotal = (int) ($unitPrice * $qty);
                $itemTaxTotal = (int) ($unitTax * $qty);

                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'item_id' => $item['item_id'] ?? null,
                    'name' => $item['name'],
                    'quantity' => $qty,
                    'received_quantity' => 0,
                    'price' => $unitPrice,
                    'tax' => $unitTax,
                    'total' => $itemTotal + $itemTaxTotal,
                ]);
            }

            return $po->load(['items', 'supplier', 'createdBy', 'currency']);
        });
    }

    /**
     * Update a draft purchase order.
     */
    public function update(PurchaseOrder $po, array $data): PurchaseOrder
    {
        if (!in_array($po->status, ['draft'])) {
            throw new \InvalidArgumentException('Only draft purchase orders can be updated.');
        }

        return DB::transaction(function () use ($po, $data) {
            // Update PO header
            $po->update([
                'supplier_id' => $data['supplier_id'] ?? $po->supplier_id,
                'po_date' => $data['po_date'] ?? $po->po_date,
                'expected_delivery_date' => $data['expected_delivery_date'] ?? $po->expected_delivery_date,
                'currency_id' => $data['currency_id'] ?? $po->currency_id,
                'warehouse_id' => $data['warehouse_id'] ?? $po->warehouse_id,
                'cost_center_id' => array_key_exists('cost_center_id', $data) ? $data['cost_center_id'] : $po->cost_center_id,
                'notes' => $data['notes'] ?? $po->notes,
            ]);

            // If items provided, replace them
            if (isset($data['items'])) {
                $po->items()->delete();

                $subTotal = 0;
                $taxTotal = 0;

                foreach ($data['items'] as $item) {
                    $qty = (float) $item['quantity'];
                    $unitPrice = (int) $item['price'];
                    $unitTax = (int) ($item['tax'] ?? 0);
                    $itemTotal = (int) ($unitPrice * $qty);
                    $itemTaxTotal = (int) ($unitTax * $qty);
                    $subTotal += $itemTotal;
                    $taxTotal += $itemTaxTotal;

                    PurchaseOrderItem::create([
                        'purchase_order_id' => $po->id,
                        'item_id' => $item['item_id'] ?? null,
                        'name' => $item['name'],
                        'quantity' => $qty,
                        'received_quantity' => 0,
                        'price' => $unitPrice,
                        'tax' => $unitTax,
                        'total' => $itemTotal + $itemTaxTotal,
                    ]);
                }

                $po->update([
                    'sub_total' => $subTotal,
                    'tax' => $taxTotal,
                    'total' => $subTotal + $taxTotal,
                ]);
            }

            return $po->fresh(['items', 'supplier', 'createdBy', 'currency']);
        });
    }

    /**
     * Mark purchase order as sent and email supplier if they have an email.
     * Status is updated AFTER email is sent successfully to avoid false "sent" state.
     *
     * @return array{po: PurchaseOrder, email_sent_to: string|null}
     */
    public function send(PurchaseOrder $po): array
    {
        if (!in_array($po->status, ['draft'])) {
            throw new \InvalidArgumentException('Only draft purchase orders can be sent.');
        }

        $po->load(['items', 'supplier', 'warehouse', 'createdBy', 'currency', 'company.address', 'costCenter']);

        $emailSentTo = null;
        $emailStatus = 'no_email';
        $supplierEmail = $po->supplier?->email;

        if ($supplierEmail) {
            try {
                $company = Company::find($po->company_id);
                $companyName = $company?->name ?? 'Facturino';

                // Get company logo via media record (works with R2/cloud storage)
                $companyLogo = null;
                if ($company) {
                    $mediaItem = $company->getMedia('logo')->first();
                    $companyLogo = $mediaItem ? $mediaItem->getFullUrl() : null;
                }

                // Generate PDF to attach
                $pdfInstance = Pdf::loadView('app.pdf.reports.purchase-order', [
                    'po' => $po,
                    'company' => $company,
                ]);
                $pdfInstance->setPaper('A4', 'portrait');
                $pdfContent = $pdfInstance->output();
                $pdfFilename = "nabavka-{$po->po_number}.pdf";

                $mailData = [
                    'to' => $supplierEmail,
                    'from' => $company?->email ?? config('mail.from.address'),
                    'subject' => "Набавка / Purchase Order {$po->po_number} — {$companyName}",
                    'body' => "<p>Набавка / Purchase Order <strong>{$po->po_number}</strong> од / from <strong>{$companyName}</strong>.</p>"
                        . ($po->expected_delivery_date ? "<p>Очекувана испорака / Expected delivery: {$po->expected_delivery_date->format('d.m.Y')}</p>" : ''),
                    'company' => [
                        'name' => $companyName,
                        'logo' => $companyLogo,
                    ],
                    'purchase_order' => [
                        'id' => $po->id,
                        'po_number' => $po->po_number,
                        'po_date' => $po->po_date,
                        'total' => $po->total,
                        'notes' => $po->notes,
                        'items' => $po->items->map(fn ($item) => [
                            'name' => $item->name,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'total' => $item->total,
                        ])->toArray(),
                    ],
                    'labels' => [
                        'item' => 'Ставка / Item',
                        'qty' => 'Кол. / Qty',
                        'price' => 'Цена / Price',
                        'total' => 'Вкупно / Total',
                        'notes' => 'Забелешки / Notes',
                    ],
                    'pdf_content' => $pdfContent,
                    'pdf_filename' => $pdfFilename,
                ];

                Mail::to($supplierEmail)->send(new SendPurchaseOrderMail($mailData));
                $emailSentTo = $supplierEmail;
                $emailStatus = 'sent';
            } catch (\Exception $e) {
                Log::warning('Failed to send PO email', [
                    'po_id' => $po->id,
                    'supplier_email' => $supplierEmail,
                    'error' => $e->getMessage(),
                ]);
                $emailStatus = 'failed';
            }
        }

        // Update status AFTER email attempt so we know the real outcome
        $po->update([
            'status' => 'sent',
            'email_status' => $emailStatus,
            'email_sent_to' => $emailSentTo,
            'sent_at' => now(),
        ]);

        return [
            'po' => $po->fresh(['items', 'supplier']),
            'email_sent_to' => $emailSentTo,
            'email_status' => $emailStatus,
        ];
    }

    /**
     * Resend email for an already-sent purchase order.
     */
    public function resendEmail(PurchaseOrder $po): array
    {
        if ($po->status === 'draft') {
            throw new \InvalidArgumentException('Cannot resend email for draft purchase orders. Use send instead.');
        }

        $po->load(['items', 'supplier', 'warehouse', 'createdBy', 'currency', 'company.address', 'costCenter']);

        $supplierEmail = $po->supplier?->email;
        if (!$supplierEmail) {
            throw new \InvalidArgumentException('Supplier has no email address.');
        }

        $emailStatus = 'failed';
        try {
            $company = Company::find($po->company_id);
            $companyName = $company?->name ?? 'Facturino';

            $companyLogo = null;
            if ($company) {
                $mediaItem = $company->getMedia('logo')->first();
                $companyLogo = $mediaItem ? $mediaItem->getFullUrl() : null;
            }

            $pdfInstance = Pdf::loadView('app.pdf.reports.purchase-order', [
                'po' => $po,
                'company' => $company,
            ]);
            $pdfInstance->setPaper('A4', 'portrait');
            $pdfContent = $pdfInstance->output();
            $pdfFilename = "nabavka-{$po->po_number}.pdf";

            $mailData = [
                'to' => $supplierEmail,
                'from' => $company?->email ?? config('mail.from.address'),
                'subject' => "Набавка / Purchase Order {$po->po_number} — {$companyName}",
                'body' => "<p>Набавка / Purchase Order <strong>{$po->po_number}</strong> од / from <strong>{$companyName}</strong>.</p>"
                    . ($po->expected_delivery_date ? "<p>Очекувана испорака / Expected delivery: {$po->expected_delivery_date->format('d.m.Y')}</p>" : ''),
                'company' => [
                    'name' => $companyName,
                    'logo' => $companyLogo,
                ],
                'purchase_order' => [
                    'id' => $po->id,
                    'po_number' => $po->po_number,
                    'po_date' => $po->po_date,
                    'total' => $po->total,
                    'notes' => $po->notes,
                    'items' => $po->items->map(fn ($item) => [
                        'name' => $item->name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                        'total' => $item->total,
                    ])->toArray(),
                ],
                'labels' => [
                    'item' => 'Ставка / Item',
                    'qty' => 'Кол. / Qty',
                    'price' => 'Цена / Price',
                    'total' => 'Вкупно / Total',
                    'notes' => 'Забелешки / Notes',
                ],
                'pdf_content' => $pdfContent,
                'pdf_filename' => $pdfFilename,
            ];

            Mail::to($supplierEmail)->send(new SendPurchaseOrderMail($mailData));
            $emailStatus = 'sent';
        } catch (\Exception $e) {
            Log::warning('Failed to resend PO email', [
                'po_id' => $po->id,
                'supplier_email' => $supplierEmail,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to send email: ' . $e->getMessage());
        }

        $po->update([
            'email_status' => $emailStatus,
            'email_sent_to' => $supplierEmail,
            'sent_at' => now(),
        ]);

        return [
            'po' => $po->fresh(['items', 'supplier']),
            'email_sent_to' => $supplierEmail,
            'email_status' => $emailStatus,
        ];
    }

    /**
     * Receive goods for a purchase order.
     * Creates a GoodsReceipt and updates received_quantity on PO items.
     *
     * @param array $receivedItems [{ purchase_order_item_id, quantity_received, quantity_accepted, quantity_rejected }]
     */
    public function receiveGoods(PurchaseOrder $po, array $receivedItems, ?int $userId = null): GoodsReceipt
    {
        if (in_array($po->status, ['draft', 'cancelled', 'closed', 'billed'])) {
            throw new \InvalidArgumentException('Cannot receive goods for a PO with status: ' . $po->status);
        }

        return DB::transaction(function () use ($po, $receivedItems, $userId) {
            // Create goods receipt
            $receipt = GoodsReceipt::create([
                'company_id' => $po->company_id,
                'purchase_order_id' => $po->id,
                'receipt_date' => now()->toDateString(),
                'warehouse_id' => $po->warehouse_id,
                'status' => 'confirmed',
                'notes' => null,
                'created_by' => $userId,
            ]);

            $allFullyReceived = true;

            foreach ($receivedItems as $receivedItem) {
                $poItem = PurchaseOrderItem::find($receivedItem['purchase_order_item_id']);
                if (!$poItem || $poItem->purchase_order_id !== $po->id) {
                    continue;
                }

                $qtyReceived = (float) ($receivedItem['quantity_received'] ?? 0);
                $qtyAccepted = (float) ($receivedItem['quantity_accepted'] ?? $qtyReceived);
                $qtyRejected = (float) ($receivedItem['quantity_rejected'] ?? 0);

                // Cap accepted quantity at remaining to prevent over-receipt
                $remaining = max(0, $poItem->quantity - $poItem->received_quantity);
                if ($qtyAccepted > $remaining) {
                    $qtyAccepted = $remaining;
                }

                // Create goods receipt item
                GoodsReceiptItem::create([
                    'goods_receipt_id' => $receipt->id,
                    'purchase_order_item_id' => $poItem->id,
                    'item_id' => $poItem->item_id,
                    'quantity_received' => $qtyReceived,
                    'quantity_accepted' => $qtyAccepted,
                    'quantity_rejected' => $qtyRejected,
                ]);

                // Update received_quantity on PO item
                $newReceived = $poItem->received_quantity + $qtyAccepted;
                $poItem->update(['received_quantity' => $newReceived]);

                // Check if this item is fully received
                if ($newReceived < $poItem->quantity) {
                    $allFullyReceived = false;
                }
            }

            // Refresh PO items to check overall status
            $po->load('items');
            $allFullyReceived = true;
            $anyReceived = false;

            foreach ($po->items as $item) {
                if ($item->received_quantity > 0) {
                    $anyReceived = true;
                }
                if ($item->received_quantity < $item->quantity) {
                    $allFullyReceived = false;
                }
            }

            if ($allFullyReceived && $anyReceived) {
                $po->update(['status' => 'fully_received']);
            } elseif ($anyReceived) {
                $po->update(['status' => 'partially_received']);
            }

            return $receipt->load('items');
        });
    }

    /**
     * Convert a purchase order to a bill.
     */
    public function convertToBill(PurchaseOrder $po): Bill
    {
        if ($po->converted_bill_id) {
            throw new \InvalidArgumentException('This purchase order has already been converted to a bill.');
        }

        if (in_array($po->status, ['draft', 'cancelled'])) {
            throw new \InvalidArgumentException('Cannot convert a draft or cancelled PO to a bill.');
        }

        return DB::transaction(function () use ($po) {
            $po->load(['items', 'supplier', 'currency']);

            // Create the bill
            $bill = Bill::create([
                'company_id' => $po->company_id,
                'supplier_id' => $po->supplier_id,
                'bill_number' => 'BILL-' . $po->po_number,
                'bill_date' => now()->toDateString(),
                'due_date' => now()->addDays(30)->toDateString(),
                'status' => Bill::STATUS_DRAFT,
                'paid_status' => Bill::PAID_STATUS_UNPAID,
                'sub_total' => $po->sub_total,
                'tax' => $po->tax,
                'total' => $po->total,
                'due_amount' => $po->total,
                'currency_id' => $po->currency_id,
                'exchange_rate' => 1,
                'notes' => 'Auto-created from Purchase Order ' . $po->po_number,
            ]);

            // Create bill items from PO items
            $billItems = [];
            foreach ($po->items as $poItem) {
                $billItems[] = [
                    'item_id' => $poItem->item_id,
                    'name' => $poItem->name,
                    'quantity' => $poItem->quantity,
                    'price' => $poItem->price,
                    'tax' => $poItem->tax,
                    'total' => $poItem->total,
                    'discount' => 0,
                    'discount_val' => 0,
                    'discount_type' => 'fixed',
                ];
            }

            Bill::createItems($bill, $billItems);

            // Link the bill to the PO
            $po->update([
                'converted_bill_id' => $bill->id,
                'status' => 'billed',
            ]);

            return $bill;
        });
    }

    /**
     * Three-way match: compare PO vs GoodsReceipt vs Bill.
     * Returns match result and any discrepancies.
     */
    public function threeWayMatch(PurchaseOrder $po): array
    {
        $po->load(['items', 'goodsReceipts.items', 'convertedBill.items']);

        $discrepancies = [];
        $matched = true;

        foreach ($po->items as $poItem) {
            $entry = [
                'item_name' => $poItem->name,
                'po_quantity' => $poItem->quantity,
                'po_price' => $poItem->price,
                'received_quantity' => $poItem->received_quantity,
                'billed_quantity' => 0,
                'billed_price' => 0,
                'quantity_match' => false,
                'price_match' => false,
            ];

            // Check bill items
            if ($po->convertedBill) {
                $billItem = $po->convertedBill->items
                    ->where('item_id', $poItem->item_id)
                    ->first();

                if ($billItem) {
                    $entry['billed_quantity'] = $billItem->quantity;
                    $entry['billed_price'] = $billItem->price;
                }
            }

            // Check quantity match (PO qty == received qty == billed qty)
            $entry['quantity_match'] = (
                abs($poItem->quantity - $poItem->received_quantity) < 0.001
                && abs($poItem->quantity - $entry['billed_quantity']) < 0.001
            );

            // Check price match (PO price == billed price)
            $entry['price_match'] = (
                $po->convertedBill === null
                || $entry['billed_price'] === $poItem->price
            );

            if (!$entry['quantity_match'] || !$entry['price_match']) {
                $matched = false;
            }

            $discrepancies[] = $entry;
        }

        return [
            'matched' => $matched,
            'po_number' => $po->po_number,
            'po_total' => $po->total,
            'bill_number' => $po->convertedBill?->bill_number,
            'bill_total' => $po->convertedBill?->total,
            'goods_receipts_count' => $po->goodsReceipts->count(),
            'discrepancies' => $discrepancies,
        ];
    }

    /**
     * Cancel a purchase order (only if draft or sent).
     */
    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        if (!in_array($po->status, ['draft', 'sent'])) {
            throw new \InvalidArgumentException('Only draft or sent purchase orders can be cancelled.');
        }

        $po->update(['status' => 'cancelled']);

        return $po->fresh(['items', 'supplier']);
    }

    /**
     * Delete a purchase order (only if draft).
     */
    public function deletePo(PurchaseOrder $po): bool
    {
        if ($po->status !== 'draft') {
            throw new \InvalidArgumentException('Only draft purchase orders can be deleted.');
        }

        return DB::transaction(function () use ($po) {
            $po->items()->delete();
            $po->delete();

            return true;
        });
    }
}

// CLAUDE-CHECKPOINT
