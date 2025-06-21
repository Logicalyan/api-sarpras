<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Warehouse;
use App\Custom\Format;

class WarehouseController extends Controller
{
    public function index()
    {
        $warehouses = Warehouse::all();
        return Format::apiResponse(200, 'List of warehouses', $warehouses);
    }

    public function show($id)
    {
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            return Format::apiResponse(404, 'Warehouse not found');
        }
        return Format::apiResponse(200, 'Warehouse detail', $warehouse);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            $warehouse = Warehouse::create($validated);
            DB::commit();
            return Format::apiResponse(201, 'Warehouse created successfully', $warehouse);
        } catch (\Exception $e) {
            DB::rollback();
            return Format::apiResponse(500, 'Failed to create warehouse', null, $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            return Format::apiResponse(404, 'Warehouse not found');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'location' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            $warehouse->update($validated);
            DB::commit();
            return Format::apiResponse(200, 'Warehouse updated successfully', $warehouse);
        } catch (\Exception $e) {
            DB::rollback();
            return Format::apiResponse(500, 'Failed to update warehouse', null, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $warehouse = Warehouse::find($id);
        if (!$warehouse) {
            return Format::apiResponse(404, 'Warehouse not found');
        }

        DB::beginTransaction();

        try {
            $warehouse->delete();
            DB::commit();
            return Format::apiResponse(200, 'Warehouse deleted successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return Format::apiResponse(500, 'Failed to delete warehouse', null, $e->getMessage());
        }
    }
}
