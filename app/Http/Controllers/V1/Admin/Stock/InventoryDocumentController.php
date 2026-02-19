<?php

namespace App\Http\Controllers\V1\Admin\Stock;

use App\Http\Controllers\Controller;
use App\Http\Requests\InventoryDocumentRequest;
use App\Models\InventoryDocument;
use App\Models\InventoryDocumentItem;
use App\Services\InventoryDocumentService;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Inventory Document Controller
 *
 * Full REST controller for inventory documents (приемница/издатница/преносница).
 * Handles CRUD operations plus workflow actions (approve, void).
 *
 * All endpoints scope to the company via the `company` request header,
 * following the same pattern as other stock controllers.
 */
class InventoryDocumentController extends Controller
{
    protected InventoryDocumentService $documentService;

    protected StockService $stockService;

    /**
     * Create a new controller instance.
     */
    public function __construct(InventoryDocumentService $documentService, StockService $stockService)
    {
        $this->documentService = $documentService;
        $this->stockService = $stockService;
    }

    /**
     * List inventory documents with filters.
     *
     * Supports filtering by type, status, date range, and search term.
     * Results are paginated and include item counts.
     *
     * @queryParam type string Filter by document type (receipt, issue, transfer)
     * @queryParam status string Filter by status (draft, approved, voided)
     * @queryParam from_date string Filter by start date (Y-m-d)
     * @queryParam to_date string Filter by end date (Y-m-d)
     * @queryParam search string Search by document number
     * @queryParam limit int Items per page (default: 15)
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->header('company');
        $limit = (int) $request->query('limit', 15);

        $query = InventoryDocument::where('company_id', $companyId)
            ->with(['warehouse', 'destinationWarehouse', 'creator'])
            ->withCount('items')
            ->orderBy('document_date', 'desc')
            ->orderBy('id', 'desc');

        if ($request->query('type')) {
            $query->whereType($request->query('type'));
        }

        if ($request->query('status')) {
            $query->whereStatus($request->query('status'));
        }

        if ($request->query('from_date') || $request->query('to_date')) {
            $query->whereDateRange($request->query('from_date'), $request->query('to_date'));
        }

        if ($request->query('search')) {
            $query->where('document_number', 'like', '%' . $request->query('search') . '%');
        }

        $documents = $query->paginate($limit);

        return response()->json([
            'data' => $documents->map(function (InventoryDocument $doc) {
                return $this->formatDocument($doc);
            }),
            'meta' => [
                'current_page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'per_page' => $documents->perPage(),
                'total' => $documents->total(),
            ],
        ]);
    }

    /**
     * Create a new draft inventory document with line items.
     *
     * Auto-generates the document_number based on type and company.
     * The document is created in 'draft' status.
     */
    public function store(InventoryDocumentRequest $request): JsonResponse
    {
        $companyId = $request->header('company');
        $data = $request->validated();

        DB::beginTransaction();
        try {
            $doc = InventoryDocument::create([
                'company_id' => $companyId,
                'document_type' => $data['document_type'],
                'warehouse_id' => $data['warehouse_id'],
                'destination_warehouse_id' => $data['destination_warehouse_id'] ?? null,
                'document_date' => $data['document_date'],
                'notes' => $data['notes'] ?? null,
                'status' => InventoryDocument::STATUS_DRAFT,
                'created_by' => auth()->id(),
            ]);

            $totalValue = 0;
            foreach ($data['items'] as $itemData) {
                $unitCost = isset($itemData['unit_cost']) ? (int) round($itemData['unit_cost'] * 100) : null;
                $totalCost = $unitCost !== null ? (int) round($itemData['quantity'] * $unitCost) : null;
                $totalValue += $totalCost ?? 0;

                InventoryDocumentItem::create([
                    'inventory_document_id' => $doc->id,
                    'item_id' => $itemData['item_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            $doc->update(['total_value' => $totalValue]);

            DB::commit();

            $doc->load(['warehouse', 'destinationWarehouse', 'items.item', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Документот е успешно креиран.',
                'data' => $this->formatDocumentDetailed($doc),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create inventory document', [
                'error' => $e->getMessage(),
                'company_id' => $companyId,
            ]);

            return response()->json([
                'error' => 'Грешка при креирање на документ.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show a single inventory document with all details.
     *
     * Returns the document with items, item details, and related entities.
     *
     * @param  int  $id  The document ID
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');

        $doc = InventoryDocument::where('company_id', $companyId)
            ->with(['warehouse', 'destinationWarehouse', 'items.item', 'approver', 'creator'])
            ->findOrFail($id);

        return response()->json([
            'data' => $this->formatDocumentDetailed($doc),
        ]);
    }

    /**
     * Update a draft inventory document.
     *
     * Only documents in 'draft' status can be edited.
     * Replaces all line items with the provided items.
     *
     * @param  int  $id  The document ID
     */
    public function update(InventoryDocumentRequest $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');
        $data = $request->validated();

        $doc = InventoryDocument::where('company_id', $companyId)
            ->findOrFail($id);

        if (! $doc->isDraft()) {
            return response()->json([
                'error' => 'Само нацрт-документи можат да се уредуваат.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $doc->update([
                'document_type' => $data['document_type'],
                'warehouse_id' => $data['warehouse_id'],
                'destination_warehouse_id' => $data['destination_warehouse_id'] ?? null,
                'document_date' => $data['document_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            // Replace all items
            $doc->items()->delete();

            $totalValue = 0;
            foreach ($data['items'] as $itemData) {
                $unitCost = isset($itemData['unit_cost']) ? (int) round($itemData['unit_cost'] * 100) : null;
                $totalCost = $unitCost !== null ? (int) round($itemData['quantity'] * $unitCost) : null;
                $totalValue += $totalCost ?? 0;

                InventoryDocumentItem::create([
                    'inventory_document_id' => $doc->id,
                    'item_id' => $itemData['item_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'notes' => $itemData['notes'] ?? null,
                ]);
            }

            $doc->update(['total_value' => $totalValue]);

            DB::commit();

            $doc->load(['warehouse', 'destinationWarehouse', 'items.item', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Документот е успешно ажуриран.',
                'data' => $this->formatDocumentDetailed($doc),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update inventory document', [
                'document_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Грешка при ажурирање на документ.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a draft inventory document.
     *
     * Only documents in 'draft' status can be deleted.
     * Deleting cascades to remove all line items.
     *
     * @param  int  $id  The document ID
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');

        $doc = InventoryDocument::where('company_id', $companyId)
            ->findOrFail($id);

        if (! $doc->isDraft()) {
            return response()->json([
                'error' => 'Само нацрт-документи можат да се бришат.',
            ], 422);
        }

        $doc->delete();

        return response()->json([
            'success' => true,
            'message' => 'Документот е успешно избришан.',
        ]);
    }

    /**
     * Approve a draft inventory document.
     *
     * Transitions the document from 'draft' to 'approved' status
     * and creates the corresponding stock movements via InventoryDocumentService.
     *
     * @param  int  $id  The document ID
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');

        $doc = InventoryDocument::where('company_id', $companyId)
            ->with('items')
            ->findOrFail($id);

        try {
            $this->documentService->approve($doc);

            $doc->refresh();
            $doc->load(['warehouse', 'destinationWarehouse', 'items.item', 'approver', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Документот е успешно одобрен.',
                'data' => $this->formatDocumentDetailed($doc),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to approve inventory document', [
                'document_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Грешка при одобрување на документ.',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Void an approved inventory document.
     *
     * Transitions the document from 'approved' to 'voided' status
     * and reverses all stock movements created during approval.
     *
     * @param  int  $id  The document ID
     */
    public function void(Request $request, int $id): JsonResponse
    {
        $companyId = $request->header('company');

        $doc = InventoryDocument::where('company_id', $companyId)
            ->findOrFail($id);

        try {
            $this->documentService->void($doc);

            $doc->refresh();
            $doc->load(['warehouse', 'destinationWarehouse', 'items.item', 'approver', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Документот е успешно поништен.',
                'data' => $this->formatDocumentDetailed($doc),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to void inventory document', [
                'document_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Грешка при поништување на документ.',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Format a document for list/index API responses.
     *
     * @param  InventoryDocument  $doc  The document to format
     * @return array<string, mixed>
     */
    private function formatDocument(InventoryDocument $doc): array
    {
        return [
            'id' => $doc->id,
            'document_number' => $doc->document_number,
            'document_type' => $doc->document_type,
            'document_type_label' => $doc->document_type_label,
            'warehouse_id' => $doc->warehouse_id,
            'warehouse_name' => $doc->warehouse?->name,
            'destination_warehouse_id' => $doc->destination_warehouse_id,
            'destination_warehouse_name' => $doc->destinationWarehouse?->name,
            'document_date' => $doc->document_date?->format('Y-m-d'),
            'status' => $doc->status,
            'status_label' => $doc->status_label,
            'total_value' => $doc->total_value,
            'items_count' => $doc->items_count ?? $doc->items->count(),
            'created_by_name' => $doc->creator?->name,
            'created_at' => $doc->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Format a document for detail/show API responses (includes items).
     *
     * @param  InventoryDocument  $doc  The document to format
     * @return array<string, mixed>
     */
    private function formatDocumentDetailed(InventoryDocument $doc): array
    {
        return [
            'id' => $doc->id,
            'document_number' => $doc->document_number,
            'document_type' => $doc->document_type,
            'document_type_label' => $doc->document_type_label,
            'warehouse_id' => $doc->warehouse_id,
            'warehouse_name' => $doc->warehouse?->name,
            'destination_warehouse_id' => $doc->destination_warehouse_id,
            'destination_warehouse_name' => $doc->destinationWarehouse?->name,
            'document_date' => $doc->document_date?->format('Y-m-d'),
            'status' => $doc->status,
            'status_label' => $doc->status_label,
            'notes' => $doc->notes,
            'total_value' => $doc->total_value,
            'approved_by_name' => $doc->approver?->name,
            'approved_at' => $doc->approved_at?->format('Y-m-d H:i:s'),
            'created_by_name' => $doc->creator?->name,
            'created_at' => $doc->created_at?->format('Y-m-d H:i:s'),
            'meta' => $doc->meta,
            'items' => $doc->items->map(function (InventoryDocumentItem $item) {
                return [
                    'id' => $item->id,
                    'item_id' => $item->item_id,
                    'item_name' => $item->item?->name,
                    'item_sku' => $item->item?->sku,
                    'item_barcode' => $item->item?->barcode,
                    'quantity' => $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $item->total_cost,
                    'notes' => $item->notes,
                ];
            })->toArray(),
        ];
    }
}
