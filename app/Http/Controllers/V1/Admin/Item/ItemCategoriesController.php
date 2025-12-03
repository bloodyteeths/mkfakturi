<?php

namespace App\Http\Controllers\V1\Admin\Item;

use App\Http\Controllers\Controller;
use App\Http\Requests\ItemCategoryRequest;
use App\Http\Resources\ItemCategoryResource;
use App\Models\ItemCategory;
use Illuminate\Http\Request;

class ItemCategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $limit = $request->has('limit') ? $request->limit : 100;

        $query = ItemCategory::whereCompany($request->header('company'))
            ->when($request->has('search'), function ($query) use ($request) {
                $query->where('name', 'LIKE', '%' . $request->search . '%');
            })
            ->orderBy('name');

        if ($limit == 'all') {
            $categories = $query->get();
        } else {
            $categories = $query->paginate($limit);
        }

        return ItemCategoryResource::collection($categories);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ItemCategoryRequest $request)
    {
        $category = ItemCategory::create([
            'company_id' => $request->header('company'),
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return new ItemCategoryResource($category);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(ItemCategory $itemCategory)
    {
        return new ItemCategoryResource($itemCategory);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(ItemCategoryRequest $request, ItemCategory $itemCategory)
    {
        $itemCategory->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return new ItemCategoryResource($itemCategory);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(ItemCategory $itemCategory)
    {
        // Check if category has items
        if ($itemCategory->items()->exists()) {
            return respondJson('items_attached', 'Category has items attached');
        }

        $itemCategory->delete();

        return response()->json([
            'success' => 'Category deleted successfully',
        ]);
    }
}
// CLAUDE-CHECKPOINT
