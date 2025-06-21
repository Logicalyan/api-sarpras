<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\StockTransaction;
use App\Models\Item;
use App\Models\ItemUnit;
use App\Custom\Format;

class StockController extends Controller
{
    public function index()
    {
        $transactions = StockTransaction::with('item')->get();
        return Format::apiResponse(200, 'List of stock transactions', $transactions);
    }

    public function show($id)
    {
        $transaction = StockTransaction::with('item')->find($id);
        if (!$transaction) {
            return Format::apiResponse(404, 'Stock transaction not found');
        }
        return Format::apiResponse(200, 'Stock transaction detail', $transaction);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'description' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            $item = Item::find($validated['item_id']);

            // Jika reusable dan type = in maka buat unit baru
            if ($item->type === 'reusable' && $validated['type'] === 'in') {
                for ($i = 0; $i < $validated['quantity']; $i++) {
                    ItemUnit::create([
                        'item_id' => $item->id,
                        'serial_number' => $item->code . '-' . now()->format('Ymd') . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                        'condition' => 'good',
                        'status' => 'available',
                    ]);
                }
            }

            $transaction = StockTransaction::create($validated);
            DB::commit();
            return Format::apiResponse(201, 'Stock transaction recorded successfully', $transaction);
        } catch (\Exception $e) {
            DB::rollback();
            return Format::apiResponse(500, 'Failed to record stock transaction', null, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $transaction = StockTransaction::find($id);
        if (!$transaction) {
            return Format::apiResponse(404, 'Stock transaction not found');
        }

        DB::beginTransaction();

        try {
            $transaction->delete();
            DB::commit();
            return Format::apiResponse(200, 'Stock transaction deleted successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return Format::apiResponse(500, 'Failed to delete stock transaction', null, $e->getMessage());
        }
    }
}
