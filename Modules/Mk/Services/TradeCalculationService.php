<?php

namespace Modules\Mk\Services;

use App\Models\Bill;
use App\Models\Item;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use App\Models\StockMovement;
use Modules\Mk\Models\ImportCalculation;
use Modules\Mk\Models\ImportCalculationItem;
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

    /**
     * Calculate import costs for all items in an import calculation.
     *
     * Formula (from accountant):
     * 1. invoice_mkd = qty × price_fcy × exchange_rate
     * 2. transport allocated proportionally by invoice value
     * 3. customs_base = invoice_mkd + transport
     * 4. customs_duty = customs_base × duty_rate (grouped by tariff heading)
     * 5. forwarding + other costs allocated proportionally
     * 6. landed_cost = all above summed
     * 7. import_vat = landed_cost × vat_rate
     */
    public function calculateImportCosts(ImportCalculation $calc): array
    {
        $calc->loadMissing('items');

        $exchangeRate = (float) $calc->exchange_rate;
        $transportAmount = (int) $calc->transport_amount;
        $forwardingAmount = (int) $calc->forwarding_amount;
        $otherCostsAmount = (int) $calc->other_costs_amount;
        $vatRate = (float) $calc->vat_rate;

        $items = $calc->items->toArray();

        // Step 1: Calculate invoice values in MKD for each item
        $totalInvoiceMkd = 0;
        foreach ($items as &$item) {
            $qty = (float) $item['quantity'];
            $unitPriceFcy = (int) $item['unit_price_fcy'];
            $item['invoice_value_fcy'] = (int) round($unitPriceFcy * $qty);
            // FCY cents × qty × rate = MKD cents (÷100 and ×100 cancel out)
            $item['invoice_value_mkd'] = (int) round($unitPriceFcy * $qty * $exchangeRate);
            $totalInvoiceMkd += $item['invoice_value_mkd'];
        }
        unset($item);

        // Step 2: Allocate transport proportionally with remainder handling
        $transportRemaining = $transportAmount;
        $lastIdx = count($items) - 1;
        foreach ($items as $i => &$item) {
            if ($i === $lastIdx) {
                $item['transport_allocated'] = $transportRemaining;
            } elseif ($totalInvoiceMkd > 0) {
                $item['transport_allocated'] = (int) round($transportAmount * $item['invoice_value_mkd'] / $totalInvoiceMkd);
                $transportRemaining -= $item['transport_allocated'];
            } else {
                $item['transport_allocated'] = 0;
            }
            $item['customs_base'] = $item['invoice_value_mkd'] + $item['transport_allocated'];
        }
        unset($item);

        // Step 3: Calculate customs duty grouped by tariff heading
        $headingGroups = [];
        foreach ($items as $i => $item) {
            $heading = $item['tariff_heading'];
            if (! isset($headingGroups[$heading])) {
                $headingGroups[$heading] = [
                    'items' => [],
                    'customs_base_total' => 0,
                    'duty_rate' => (float) $item['customs_duty_rate'],
                ];
            }
            $headingGroups[$heading]['items'][] = $i;
            $headingGroups[$heading]['customs_base_total'] += $item['customs_base'];
        }

        $totalCustomsDuty = 0;
        foreach ($headingGroups as $heading => &$group) {
            $headingDuty = (int) round($group['customs_base_total'] * $group['duty_rate'] / 100);
            $group['duty_amount'] = $headingDuty;

            // Allocate duty within heading proportionally
            $dutyRemaining = $headingDuty;
            $lastInGroup = count($group['items']) - 1;
            foreach ($group['items'] as $gi => $itemIdx) {
                if ($gi === $lastInGroup) {
                    $items[$itemIdx]['customs_duty_amount'] = $dutyRemaining;
                } elseif ($group['customs_base_total'] > 0) {
                    $allocated = (int) round($headingDuty * $items[$itemIdx]['customs_base'] / $group['customs_base_total']);
                    $items[$itemIdx]['customs_duty_amount'] = $allocated;
                    $dutyRemaining -= $allocated;
                } else {
                    $items[$itemIdx]['customs_duty_amount'] = 0;
                }
            }

            $totalCustomsDuty += $headingDuty;
        }
        unset($group);

        // Step 4: Allocate forwarding and other costs proportionally
        $fwdRemaining = $forwardingAmount;
        $otherRemaining = $otherCostsAmount;
        foreach ($items as $i => &$item) {
            if ($i === $lastIdx) {
                $item['forwarding_allocated'] = $fwdRemaining;
                $item['other_costs_allocated'] = $otherRemaining;
            } elseif ($totalInvoiceMkd > 0) {
                $item['forwarding_allocated'] = (int) round($forwardingAmount * $item['invoice_value_mkd'] / $totalInvoiceMkd);
                $item['other_costs_allocated'] = (int) round($otherCostsAmount * $item['invoice_value_mkd'] / $totalInvoiceMkd);
                $fwdRemaining -= $item['forwarding_allocated'];
                $otherRemaining -= $item['other_costs_allocated'];
            } else {
                $item['forwarding_allocated'] = 0;
                $item['other_costs_allocated'] = 0;
            }

            // Step 5: Landed cost
            $item['landed_cost_before_vat'] = $item['invoice_value_mkd']
                + $item['transport_allocated']
                + $item['customs_duty_amount']
                + $item['forwarding_allocated']
                + $item['other_costs_allocated'];

            $item['import_vat_amount'] = (int) round($item['landed_cost_before_vat'] * $vatRate / 100);
            $item['total_landed_cost'] = $item['landed_cost_before_vat'] + $item['import_vat_amount'];

            $qty = (float) $item['quantity'];
            $item['unit_landed_cost'] = $qty > 0
                ? (int) round($item['landed_cost_before_vat'] / $qty)
                : 0;
        }
        unset($item);

        // Build totals
        $totals = [
            'total_invoice_fcy' => array_sum(array_column($items, 'invoice_value_fcy')),
            'total_invoice_mkd' => $totalInvoiceMkd,
            'transport' => $transportAmount,
            'forwarding' => $forwardingAmount,
            'other_costs' => $otherCostsAmount,
            'customs_duty' => $totalCustomsDuty,
            'total_before_vat' => array_sum(array_column($items, 'landed_cost_before_vat')),
            'import_vat' => array_sum(array_column($items, 'import_vat_amount')),
            'total_landed_cost' => array_sum(array_column($items, 'total_landed_cost')),
        ];

        // Build tariff heading summary
        $headingSummary = [];
        foreach ($headingGroups as $heading => $group) {
            $headingSummary[] = [
                'tariff_heading' => $heading,
                'items_count' => count($group['items']),
                'customs_base_total' => $group['customs_base_total'],
                'duty_rate' => $group['duty_rate'],
                'duty_amount' => $group['duty_amount'],
            ];
        }

        return [
            'items' => $items,
            'totals' => $totals,
            'tariff_summary' => $headingSummary,
        ];
    }

    /**
     * Approve an import calculation — records stock-in movements.
     * Unit cost for stock = landed_cost_before_vat / qty (VAT is deductible input tax).
     */
    public function approveImportCalculation(ImportCalculation $calc): void
    {
        if (! $calc->isDraft()) {
            throw new \Exception('Само нацрт калкулации може да се одобрат.');
        }

        DB::transaction(function () use ($calc) {
            $calc->loadMissing('items');

            foreach ($calc->items as $calcItem) {
                if (! $calcItem->item_id || $calcItem->quantity <= 0) {
                    continue;
                }

                $unitCost = $calcItem->quantity > 0
                    ? (int) round($calcItem->landed_cost_before_vat / $calcItem->quantity)
                    : 0;

                $this->stockService->recordStockIn(
                    companyId: $calc->company_id,
                    warehouseId: $calc->warehouse_id,
                    itemId: $calcItem->item_id,
                    quantity: (float) $calcItem->quantity,
                    unitCost: $unitCost,
                    sourceType: StockMovement::SOURCE_IMPORT_CALCULATION,
                    sourceId: $calc->id,
                    movementDate: $calc->document_date->format('Y-m-d'),
                    notes: "Влезна калкулација {$calc->document_number}",
                    meta: [
                        'import_calculation_id' => $calc->id,
                        'tariff_heading' => $calcItem->tariff_heading,
                        'exchange_rate' => (float) $calc->exchange_rate,
                        'currency_code' => $calc->currency_code,
                    ],
                );
            }

            $calc->update([
                'status' => ImportCalculation::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);
        });
    }

    /**
     * Void an import calculation — reverses stock movements.
     */
    public function voidImportCalculation(ImportCalculation $calc): void
    {
        if (! $calc->isApproved()) {
            throw new \Exception('Само одобрени калкулации може да се поништат.');
        }

        DB::transaction(function () use ($calc) {
            $calc->loadMissing('items');

            foreach ($calc->items as $calcItem) {
                if (! $calcItem->item_id || $calcItem->quantity <= 0) {
                    continue;
                }

                $unitCost = $calcItem->quantity > 0
                    ? (int) round($calcItem->landed_cost_before_vat / $calcItem->quantity)
                    : 0;

                $this->stockService->recordStockOut(
                    companyId: $calc->company_id,
                    warehouseId: $calc->warehouse_id,
                    itemId: $calcItem->item_id,
                    quantity: (float) $calcItem->quantity,
                    unitCost: $unitCost,
                    sourceType: StockMovement::SOURCE_IMPORT_CALCULATION,
                    sourceId: $calc->id,
                    movementDate: now()->format('Y-m-d'),
                    notes: "Поништена влезна калкулација {$calc->document_number}",
                );
            }

            $calc->update([
                'status' => ImportCalculation::STATUS_VOIDED,
            ]);
        });
    }
}

// CLAUDE-CHECKPOINT
