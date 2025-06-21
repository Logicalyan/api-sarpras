<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ItemUnit;
use App\Models\Item;
use App\Custom\Format;

class ItemUnitController extends Controller
{
    public function index()
    {
        $units = ItemUnit::with('item')->get();
        return Format::apiResponse(200, 'List of item units', $units);
    }

    public function show($id)
    {
        $unit = ItemUnit::with('item')->find($id);
        if (!$unit) {
            return Format::apiResponse(404, 'Item unit not found');
        }
        return Format::apiResponse(200, 'Item unit detail', $unit);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'serial_number' => 'required|string|unique:item_units,serial_number',
            'condition' => 'required|in:good,damaged,lost',
            'status' => 'required|in:available,borrowed,maintenance,unavailable'
        ]);

        $item = Item::find($validated['item_id']);
        if ($item->type === 'consumable') {
            return Format::apiResponse(422, 'Consumable items do not require units');
        }

        DB::beginTransaction();

        try {
            $unit = ItemUnit::create($validated);
            DB::commit();
            return Format::apiResponse(201, 'Item unit created successfully', $unit);
        } catch (\Exception $e) {
            DB::rollback();
            return Format::apiResponse(500, 'Failed to create item unit', null, $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $unit = ItemUnit::find($id);
        if (!$unit) {
            return Format::apiResponse(404, 'Item unit not found');
        }

        $validated = $request->validate([
            'serial_number' => 'sometimes|string|unique:item_units,serial_number,' . $id,
            'condition' => 'sometimes|in:good,damaged,lost',
            'status' => 'sometimes|in:available,unavailable,maintenance'
        ]);

        DB::beginTransaction();

        try {
            $unit->update($validated);
            DB::commit();
            return Format::apiResponse(200, 'Item unit updated successfully', $unit);
        } catch (\Exception $e) {
            DB::rollback();
            return Format::apiResponse(500, 'Failed to update item unit', null, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $unit = ItemUnit::find($id);
        if (!$unit) {
            return Format::apiResponse(404, 'Item unit not found');
        }

        DB::beginTransaction();

        try {
            $unit->delete();
            DB::commit();
            return Format::apiResponse(200, 'Item unit deleted successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return Format::apiResponse(500, 'Failed to delete item unit', null, $e->getMessage());
        }
    }
}
