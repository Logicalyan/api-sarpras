<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Item;
use App\Custom\Format;
use App\Models\ItemUnit;

class ItemController extends Controller
{
    public function index()
    {
        $items = Item::with(['category', 'warehouse'])->get();
        return Format::apiResponse(200, 'List of items', $items);
    }

    public function show($id)
    {
        $item = Item::with(['category', 'warehouse'])->find($id);
        if (!$item) {
            return Format::apiResponse(404, 'Item not found');
        }
        return Format::apiResponse(200, 'Item detail', $item);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:100|unique:items,code',
            'type' => 'required|in:consumable,reusable',
            'category_id' => 'required|exists:categories,id',
            'warehouse_id' => 'required|exists:warehouses,id',
        ]);

        DB::beginTransaction();

        try {
            $item = Item::create($validated);
            DB::commit();
            return Format::apiResponse(201, 'Item created successfully', $item);
        } catch (\Exception $e) {
            DB::rollback();
            return Format::apiResponse(500, 'Failed to create item', null, $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $item = Item::find($id);
        if (!$item) {
            return Format::apiResponse(404, 'Item not found');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => 'nullable|string|max:100|unique:items,code,' . $id,
            'type' => 'sometimes|in:consumable,reusable',
            'category_id' => 'sometimes|exists:categories,id',
            'warehouse_id' => 'sometimes|exists:warehouses,id',
        ]);

        DB::beginTransaction();

        try {
            $item->update($validated);
            DB::commit();
            return Format::apiResponse(200, 'Item updated successfully', $item);
        } catch (\Exception $e) {
            DB::rollback();
            return Format::apiResponse(500, 'Failed to update item', null, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $item = Item::find($id);
        if (!$item) {
            return Format::apiResponse(404, 'Item not found');
        }

        DB::beginTransaction();

        try {
            $item->delete();
            DB::commit();
            return Format::apiResponse(200, 'Item deleted successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return Format::apiResponse(500, 'Failed to delete item', null, $e->getMessage());
        }
    }
}
