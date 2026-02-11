<?php

namespace App\Services;

use App\Models\InventoryDocument;
use App\Models\InventoryDocumentItem;
use App\Models\StockMovement;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Inventory Document Service
 *
 * Handles the business logic for formal inventory documents:
 * - Приемница (Receipt): Stock IN from suppliers or production
 * - Издатница (Issue): Stock OUT for internal use, write-offs, etc.
 * - Преносница (Transfer): Stock movement between warehouses
 *
 * Lifecycle: draft -> approved (creates stock movements) -> voided (reverses movements)
 *
 * @version 1.0.0
 */
class InventoryDocumentService
{
    protected StockService $stockService;

    /**
     * Create a new InventoryDocumentService instance.
     */
    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Approve an inventory document and create corresponding stock movements.
     *
     * For receipt documents: creates stock IN movements for each line item.
     * For issue documents: creates stock OUT movements for each line item.
     * For transfer documents: creates paired transfer movements (OUT + IN) for each line item.
     *
     * @param  InventoryDocument  $doc  The document to approve (must be in 'draft' status)
     *
     * @throws Exception If document is not in draft status or stock operation fails
     */
    public function approve(InventoryDocument $doc): void
    {
        if (! $doc->isDraft()) {
            throw new Exception('Only draft documents can be approved.');
        }

        DB::beginTransaction();
        try {
            $doc->load('items');

            foreach ($doc->items as $docItem) {
                $meta = [
                    'document_id' => $doc->id,
                    'document_number' => $doc->document_number,
                    'document_type' => $doc->document_type,
                ];

                switch ($doc->document_type) {
                    case InventoryDocument::TYPE_RECEIPT:
                        $this->stockService->recordStockIn(
                            $doc->company_id,
                            $doc->warehouse_id,
                            $docItem->item_id,
                            (float) $docItem->quantity,
                            (int) $docItem->unit_cost,
                            StockMovement::SOURCE_INVENTORY_DOCUMENT,
                            $doc->id,
                            $doc->document_date?->format('Y-m-d'),
                            "Приемница {$doc->document_number}",
                            $meta,
                            auth()->id()
                        );
                        break;

                    case InventoryDocument::TYPE_ISSUE:
                        $this->stockService->recordStockOut(
                            $doc->company_id,
                            $doc->warehouse_id,
                            $docItem->item_id,
                            (float) $docItem->quantity,
                            StockMovement::SOURCE_INVENTORY_DOCUMENT,
                            $doc->id,
                            $doc->document_date?->format('Y-m-d'),
                            "Издатница {$doc->document_number}",
                            $meta,
                            auth()->id()
                        );
                        break;

                    case InventoryDocument::TYPE_TRANSFER:
                        $this->stockService->transferStock(
                            $doc->company_id,
                            $doc->warehouse_id,
                            $doc->destination_warehouse_id,
                            $docItem->item_id,
                            (float) $docItem->quantity,
                            "Преносница {$doc->document_number}",
                            auth()->id()
                        );
                        break;
                }
            }

            // Recalculate total value from items
            $totalValue = $doc->items->sum(function (InventoryDocumentItem $item) {
                return $item->total_cost ?? 0;
            });

            $doc->update([
                'status' => InventoryDocument::STATUS_APPROVED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'total_value' => $totalValue,
            ]);

            DB::commit();

            Log::info('Inventory document approved', [
                'document_id' => $doc->id,
                'document_number' => $doc->document_number,
                'type' => $doc->document_type,
                'items_count' => $doc->items->count(),
                'total_value' => $totalValue,
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve inventory document', [
                'document_id' => $doc->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Void an approved inventory document by reversing all associated stock movements.
     *
     * Finds all stock movements linked to this document and creates reversal entries.
     *
     * @param  InventoryDocument  $doc  The document to void (must be in 'approved' status)
     *
     * @throws Exception If document is not in approved status or reversal fails
     */
    public function void(InventoryDocument $doc): void
    {
        if (! $doc->isApproved()) {
            throw new Exception('Only approved documents can be voided.');
        }

        DB::beginTransaction();
        try {
            // Find all stock movements created by this document
            $movements = StockMovement::where('source_type', StockMovement::SOURCE_INVENTORY_DOCUMENT)
                ->where('source_id', $doc->id)
                ->get();

            // For transfers, also find the paired transfer_in/transfer_out movements
            if ($doc->document_type === InventoryDocument::TYPE_TRANSFER) {
                $transferMovements = StockMovement::whereIn('source_type', [
                    StockMovement::SOURCE_TRANSFER_IN,
                    StockMovement::SOURCE_TRANSFER_OUT,
                ])
                    ->where('meta->document_id', $doc->id)
                    ->get();

                $movements = $movements->merge($transferMovements);
            }

            foreach ($movements as $movement) {
                $this->stockService->reverseMovement(
                    $movement,
                    "Поништување на документ {$doc->document_number}"
                );
            }

            $doc->update([
                'status' => InventoryDocument::STATUS_VOIDED,
            ]);

            DB::commit();

            Log::info('Inventory document voided', [
                'document_id' => $doc->id,
                'document_number' => $doc->document_number,
                'reversed_movements' => $movements->count(),
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to void inventory document', [
                'document_id' => $doc->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Generate a sequential document number for a given company and document type.
     *
     * Delegates to InventoryDocument::generateDocumentNumber().
     *
     * @param  int  $companyId  The company ID
     * @param  string  $type  The document type (receipt, issue, transfer)
     * @return string The generated document number (e.g., PR-2025-0001)
     */
    public function generateDocumentNumber(int $companyId, string $type): string
    {
        return InventoryDocument::generateDocumentNumber($companyId, $type);
    }
}
// CLAUDE-CHECKPOINT
