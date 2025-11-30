<?php

namespace App\Http\Controllers\V1\Admin\Item;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Http\Requests\DeleteItemsRequest;
use App\Http\Resources\ItemResource;
use App\Models\Item;
use App\Models\TaxType;
use App\Providers\CacheServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ItemsController extends Controller
{
    /**
     * Retrieve a list of existing Items.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Item::class);

        $limit = $request->has('limit') ? $request->limit : 10;

        $items = Item::whereCompany()
            ->with([
                'unit',
                'company',
                'taxes.taxType',
                'taxes.currency',
                'currency',
            ])
            ->applyFilters($request->all())
            ->latest()
            ->paginateData($limit);

        $taxTypes = Cache::companyRemember('items:tax-types', CacheServiceProvider::CACHE_TTLS['MEDIUM'], function () {
            return TaxType::whereCompany()->latest()->get();
        });

        $itemCount = Cache::companyRemember('items:count', CacheServiceProvider::CACHE_TTLS['SHORT'], function () {
            return Item::whereCompany()->count();
        });

        return ItemResource::collection($items)
            ->additional(['meta' => [
                'tax_types' => $taxTypes,
                'item_total_count' => $itemCount,
            ]]);
    }

    /**
     * Create Item.
     *
     * @param  App\Http\Requests\ItemsRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Requests\ItemsRequest $request)
    {
        $this->authorize('create', Item::class);

        $item = Item::createItem($request);

        return new ItemResource($item);
    }

    /**
     * get an existing Item.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Item $item)
    {
        $this->authorize('view', $item);

        return new ItemResource($item);
    }

    /**
     * Update an existing Item.
     *
     * @param  App\Http\Requests\ItemsRequest  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Requests\ItemsRequest $request, Item $item)
    {
        $this->authorize('update', $item);

        $item = $item->updateItem($request);

        return new ItemResource($item);
    }

    /**
     * Delete a list of existing Items.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DeleteItemsRequest $request)
    {
        $this->authorize('delete multiple items');

        Item::destroy($request->ids);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Lookup item by barcode (exact match).
     *
     * Used for barcode scanner scenarios where we need fast exact lookup.
     * Returns the item if found, 404 if not found.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lookupByBarcode(Request $request)
    {
        $this->authorize('viewAny', Item::class);

        $request->validate([
            'barcode' => 'required|string|max:255',
        ]);

        $item = Item::whereCompany()
            ->where('barcode', $request->barcode)
            ->with([
                'unit',
                'taxes.taxType',
                'taxes.currency',
                'currency',
            ])
            ->first();

        if (! $item) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found with barcode: '.$request->barcode,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new ItemResource($item),
        ]);
    }
}
// CLAUDE-CHECKPOINT
