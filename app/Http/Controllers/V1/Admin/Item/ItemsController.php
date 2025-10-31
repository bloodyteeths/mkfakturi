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
}
