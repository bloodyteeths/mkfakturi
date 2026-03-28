<?php

namespace Modules\Mk\Services;

use App\Models\Bill;
use App\Models\Item;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Modules\Mk\Models\Nivelacija;
use Modules\Mk\Models\NivelacijaItem;

class TradeCalculationService
{
    public function __construct(
        protected StockService $stockService,
    ) {}

    /**
     * Calculate ПЛТ (retail price calculation) for a bill.
     * Formula: продажна = (набавна + маржа) + ДДВ(набавна + маржа)
     */
    public function calculatePlt(Bill $bill, array $markupOverrides = []): array
    {
        $bill->loadMissing(['supplier', 'items.item.unit', 'items.taxes.taxType']);
        $defaultMarkup = config('mk.pricing.default_markup_percent', 25);

        $items = [];
        $totals = [
            'nabavna' => 0,
            'marzha' => 0,
            'vat_nabavna' => 0,
            'prodazhna' => 0,
            'vat_prodazhna' => 0,
        ];

        foreach ($bill->items as $billItem) {
            $qty = $billItem->quantity ?? 1;
            $unitPrice = $billItem->price ?? 0;
            $nabavnaIznos = (int) ($unitPrice * $qty);
            $discount = $billItem->discount_val ?? 0;
            $nabavnaIznos -= $discount;

            $vatRate = 0;
            $vatAmountNabavna = 0;
            foreach ($billItem->taxes as $tax) {
                $vatRate = $tax->taxType->percent ?? 0;
                $vatAmountNabavna += (int) ($tax->amount ?? 0);
            }

            $itemId = $billItem->item_id;
            $markupPercent = $markupOverrides[$itemId]
                ?? $billItem->item?->markup_percent
                ?? $defaultMarkup;

            $marzha = (int) round($nabavnaIznos * $markupPercent / 100);
            $prodazhnaBezDdv = $nabavnaIznos + $marzha;
            $ddvVoProdazhna = $vatRate > 0 ? (int) round($prodazhnaBezDdv * $vatRate / 100) : 0;
            $prodazhnaIznos = $prodazhnaBezDdv + $ddvVoProdazhna;
            $unitPriceProdazhna = $qty > 0 ? (int) round($prodazhnaIznos / $qty) : 0;

            $items[] = [
                'item_id' => $itemId,
                'name' => $billItem->item?->name ?? $billItem->name ?? '',
                'unit' => $billItem->item?->unit?->name ?? 'ком.',
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'nabavna_iznos' => $nabavnaIznos,
                'marzha' => $marzha,
                'marzha_percent' => $markupPercent,
                'vat_amount' => $vatAmountNabavna,
                'vat_rate' => $vatRate,
                'prodazhna_bez_ddv' => $prodazhnaBezDdv,
                'unit_price_prodazhna' => $unitPriceProdazhna,
                'prodazhna_iznos' => $prodazhnaIznos,
                'prodazhna_vat' => $ddvVoProdazhna,
            ];

            $totals['nabavna'] += $nabavnaIznos;
            $totals['marzha'] += $marzha;
            $totals['vat_nabavna'] += $vatAmountNabavna;
            $totals['prodazhna'] += $prodazhnaIznos;
            $totals['vat_prodazhna'] += $ddvVoProdazhna;
        }

        return ['items' => $items, 'totals' => $totals, 'bill' => $bill];
    }

    /**
     * Calculate КАП (wholesale price calculation) for a bill.
     * Wholesale prices are WITHOUT VAT.
     */
    public function calculateKap(Bill $bill, array $markupOverrides = [], array $dependentCosts = []): array
    {
        $bill->loadMissing(['supplier', 'items.item.unit', 'items.taxes.taxType']);
        $defaultMarkup = config('mk.pricing.default_markup_percent', 25);

        $totalInvoiceValue = $bill->items->sum(function ($item) {
            return (int) (($item->price ?? 0) * ($item->quantity ?? 1)) - ($item->discount_val ?? 0);
        });

        $items = [];
        $totals = [
            'fakturna' => 0,
            'zavisni' => 0,
            'nabavna' => 0,
            'marzha' => 0,
            'prodazhna' => 0,
        ];

        foreach ($bill->items as $billItem) {
            $qty = $billItem->quantity ?? 1;
            $unitPrice = $billItem->price ?? 0;
            $fakturnaIznos = (int) ($unitPrice * $qty);
            $discount = $billItem->discount_val ?? 0;
            $fakturnaIznos -= $discount;

            // Allocate dependent costs proportionally
            $zavisniTroshoci = 0;
            $totalDependentCosts = array_sum($dependentCosts);
            if ($totalDependentCosts > 0 && $totalInvoiceValue > 0) {
                $zavisniTroshoci = (int) round($totalDependentCosts * $fakturnaIznos / $totalInvoiceValue);
            }

            $nabavnaIznos = $fakturnaIznos + $zavisniTroshoci;

            $vatRate = 0;
            foreach ($billItem->taxes as $tax) {
                $vatRate = $tax->taxType->percent ?? 0;
            }

            $itemId = $billItem->item_id;
            $markupPercent = $markupOverrides[$itemId]
                ?? $billItem->item?->markup_percent
                ?? $defaultMarkup;

            $marzha = (int) round($nabavnaIznos * $markupPercent / 100);
            $prodazhnaIznos = $nabavnaIznos + $marzha;
            $unitPriceProdazhna = $qty > 0 ? (int) round($prodazhnaIznos / $qty) : 0;

            $items[] = [
                'item_id' => $itemId,
                'name' => $billItem->item?->name ?? $billItem->name ?? '',
                'unit' => $billItem->item?->unit?->name ?? 'ком.',
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'fakturna_iznos' => $fakturnaIznos,
                'zavisni_troshoci' => $zavisniTroshoci,
                'nabavna_iznos' => $nabavnaIznos,
                'marzha' => $marzha,
                'marzha_percent' => $markupPercent,
                'vat_rate' => $vatRate,
                'unit_price_prodazhna' => $unitPriceProdazhna,
                'prodazhna_iznos' => $prodazhnaIznos,
            ];

            $totals['fakturna'] += $fakturnaIznos;
            $totals['zavisni'] += $zavisniTroshoci;
            $totals['nabavna'] += $nabavnaIznos;
            $totals['marzha'] += $marzha;
            $totals['prodazhna'] += $prodazhnaIznos;
        }

        return ['items' => $items, 'totals' => $totals, 'bill' => $bill];
    }

    /**
     * Apply calculated prices to items and detect changes.
     *
     * @param  string  $priceType  'retail' or 'wholesale'
     * @return array{changed: array, unchanged: array}
     */
    public function applyPricesToItems(array $calculatedItems, string $priceType): array
    {
        $changed = [];
        $unchanged = [];
        $field = $priceType === 'retail' ? 'retail_price' : 'wholesale_price';
        $priceKey = $priceType === 'retail' ? 'prodazhna_iznos' : 'prodazhna_iznos';

        foreach ($calculatedItems as $calcItem) {
            $itemId = $calcItem['item_id'] ?? null;
            if (! $itemId) {
                continue;
            }

            $item = Item::find($itemId);
            if (! $item) {
                continue;
            }

            $newPrice = $priceType === 'retail'
                ? ($calcItem['unit_price_prodazhna'] ?? 0)
                : ($calcItem['unit_price_prodazhna'] ?? 0);

            $oldPrice = $item->{$field} ?? 0;

            if ($oldPrice !== $newPrice && $newPrice > 0) {
                $item->{$field} = $newPrice;
                $item->markup_percent = $calcItem['marzha_percent'] ?? $item->markup_percent;
                $item->save();

                $changed[] = [
                    'item_id' => $itemId,
                    'name' => $item->name,
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                    'markup_percent' => $calcItem['marzha_percent'] ?? null,
                ];
            } else {
                $unchanged[] = ['item_id' => $itemId, 'name' => $item->name];
            }
        }

        return ['changed' => $changed, 'unchanged' => $unchanged];
    }

    /**
     * Create a Нивелација draft from detected price changes.
     */
    public function createAutoNivelacija(
        int $companyId,
        array $changedItems,
        string $reason,
        ?int $sourceBillId = null,
        ?int $warehouseId = null,
    ): ?Nivelacija {
        $nivelacijaItems = [];
        $totalDifference = 0;

        foreach ($changedItems as $change) {
            $itemId = $change['item_id'];
            $stock = $this->stockService->getItemStock($companyId, $itemId, $warehouseId);
            $qtyOnHand = (float) ($stock['quantity'] ?? 0);

            if ($qtyOnHand <= 0) {
                continue;
            }

            $priceDiff = ($change['new_price'] ?? 0) - ($change['old_price'] ?? 0);
            $lineTotalDiff = (int) round($priceDiff * $qtyOnHand);
            $totalDifference += $lineTotalDiff;

            $nivelacijaItems[] = [
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
                'quantity_on_hand' => $qtyOnHand,
                'old_retail_price' => $change['old_price'] ?? 0,
                'new_retail_price' => $change['new_price'] ?? 0,
                'old_markup_percent' => null,
                'new_markup_percent' => $change['markup_percent'] ?? null,
                'price_difference' => $priceDiff,
                'total_difference' => $lineTotalDiff,
            ];
        }

        if (empty($nivelacijaItems)) {
            return null;
        }

        return DB::transaction(function () use ($companyId, $reason, $sourceBillId, $warehouseId, $totalDifference, $nivelacijaItems) {
            $nivelacija = Nivelacija::create([
                'company_id' => $companyId,
                'document_date' => now()->format('Y-m-d'),
                'type' => $sourceBillId ? Nivelacija::TYPE_SUPPLIER_CHANGE : Nivelacija::TYPE_PRICE_CHANGE,
                'status' => Nivelacija::STATUS_DRAFT,
                'reason' => $reason,
                'source_bill_id' => $sourceBillId,
                'warehouse_id' => $warehouseId,
                'total_difference' => $totalDifference,
                'created_by' => auth()->id(),
            ]);

            foreach ($nivelacijaItems as $itemData) {
                $nivelacija->items()->create($itemData);
            }

            return $nivelacija->load('items.item');
        });
    }

    /**
     * Approve a Нивелација — updates item prices.
     */
    public function approveNivelacija(Nivelacija $nivelacija): void
    {
        if (! $nivelacija->isDraft()) {
            throw new \Exception('Само нацрт нивелации може да се одобрат.');
        }

        DB::transaction(function () use ($nivelacija) {
            $nivelacija->loadMissing('items');

            foreach ($nivelacija->items as $nivelacijaItem) {
                $item = Item::lockForUpdate()->find($nivelacijaItem->item_id);
                if (! $item) {
                    continue;
                }

                if ($nivelacijaItem->new_retail_price > 0) {
                    $item->retail_price = $nivelacijaItem->new_retail_price;
                }
                if ($nivelacijaItem->new_wholesale_price > 0) {
                    $item->wholesale_price = $nivelacijaItem->new_wholesale_price;
                }
                if ($nivelacijaItem->new_markup_percent !== null) {
                    $item->markup_percent = $nivelacijaItem->new_markup_percent;
                }
                $item->save();
            }

            $nivelacija->update([
                'status' => Nivelacija::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });
    }

    /**
     * Void a Нивелација — reverts item prices.
     */
    public function voidNivelacija(Nivelacija $nivelacija): void
    {
        if (! $nivelacija->isApproved()) {
            throw new \Exception('Само одобрени нивелации може да се поништат.');
        }

        DB::transaction(function () use ($nivelacija) {
            $nivelacija->loadMissing('items');

            foreach ($nivelacija->items as $nivelacijaItem) {
                $item = Item::lockForUpdate()->find($nivelacijaItem->item_id);
                if (! $item) {
                    continue;
                }

                if ($nivelacijaItem->old_retail_price > 0) {
                    $item->retail_price = $nivelacijaItem->old_retail_price;
                }
                if ($nivelacijaItem->old_wholesale_price > 0) {
                    $item->wholesale_price = $nivelacijaItem->old_wholesale_price;
                }
                if ($nivelacijaItem->old_markup_percent !== null) {
                    $item->markup_percent = $nivelacijaItem->old_markup_percent;
                }
                $item->save();
            }

            $nivelacija->update([
                'status' => Nivelacija::STATUS_VOIDED,
            ]);
        });
    }

    /**
     * Validate margin caps per government price control.
     *
     * @param  string  $tradeType  'wholesale' or 'retail'
     * @return array{violations: array, cap_percent: float}
     */
    public function validateMarginCaps(array $items, string $tradeType): array
    {
        if (! config('mk.pricing.margin_cap_enabled', false)) {
            return ['violations' => [], 'cap_percent' => 0];
        }

        $cap = $tradeType === 'wholesale'
            ? config('mk.pricing.margin_cap_wholesale', 5.0)
            : config('mk.pricing.margin_cap_retail', 10.0);

        $violations = [];
        foreach ($items as $item) {
            $markupPercent = $item['marzha_percent'] ?? 0;
            if ($markupPercent > $cap) {
                $violations[] = [
                    'item_id' => $item['item_id'] ?? null,
                    'name' => $item['name'] ?? '',
                    'markup_percent' => $markupPercent,
                    'cap_percent' => $cap,
                    'exceeded_by' => round($markupPercent - $cap, 2),
                ];
            }
        }

        return ['violations' => $violations, 'cap_percent' => $cap];
    }
}

// CLAUDE-CHECKPOINT
