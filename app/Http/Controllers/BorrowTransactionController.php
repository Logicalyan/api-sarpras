<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\BorrowTransaction;
use App\Models\BorrowDetailUnit;
use App\Models\BorrowDetailConsumable;
use App\Models\ItemUnit;
use App\Models\Item;
use App\Custom\Format;
use App\Models\StockTransaction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class BorrowTransactionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return Format::apiResponse(401, 'Unauthorized');
        }

        $transactions = BorrowTransaction::with(['user', 'detailUnits.itemUnit', 'detailConsumables.item'])
            ->when($user->role !== 'admin', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->latest()->get();

        return Format::apiResponse(200, 'List of borrow transactions', $transactions);
    }

    public function show($id)
    {
        $user = Auth::user();
        if (!$user) {
            return Format::apiResponse(401, 'Unauthorized');
        }

        $transaction = BorrowTransaction::with(['user', 'detailUnits.itemUnit', 'detailConsumables.item'])->find($id);
        if (!$transaction) {
            return Format::apiResponse(404, 'Transaction not found');
        }

        if ($user->role !== 'admin' && $transaction->user_id !== $user->id) {
            return Format::apiResponse(403, 'Forbidden: You do not have access to this transaction');
        }

        return Format::apiResponse(200, 'Transaction detail', $transaction);
    }

    public function store(Request $request)
    {
        if (!$request->expectsJson()) {
            throw new \Exception('This endpoint requires a JSON request. Ensure "Accept: application/json" is set.', 406);
        }

        $user = Auth::user();
        if (!$user) {
            throw new \Exception('Unauthorized: Invalid or missing token.', 401);
        }

        $validated = $request->validate([
            'borrow_date' => 'required|date',
            'return_date' => 'required|date|after_or_equal:borrow_date',
            'reusable_units' => [
                'nullable',
                'array',
                function ($attribute, $value, $fail) {
                    if (count(array_unique($value)) !== count($value)) {
                        $fail('Duplicate unit IDs are not allowed.');
                    }
                },
            ],
            'reusable_units.*' => 'integer|exists:item_units,id',
            'consumables' => 'nullable|array',
            'consumables.*item_id' => 'required|exists:items,id',
            'consumables.*.quantity' => 'integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $borrowCode = 'BRW-' . now()->format('YmdHis') . '-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            $transaction = BorrowTransaction::create([
                'user_id' => $user->id,
                'borrow_code' => $borrowCode,
                'borrow_date' => $validated['borrow_date'],
                'return_date' => $validated['return_date'],
                'approval_status' => 'pending',
                'status' => 'not_applicable'
            ]);

            if (!empty($validated['reusable_units'])) {
                foreach ($validated['reusable_units'] as $unitId) {
                    $unit = ItemUnit::findOrFail($unitId);
                    if ($unit->status !== 'available') {
                        throw new \Exception("Unit ID {$unitId} is not available");
                    }
                    BorrowDetailUnit::create([
                        'borrow_transaction_id' => $transaction->id,
                        'item_unit_id' => $unit->id
                    ]);
                }
            }

            if (!empty($validated['consumables'])) {
                foreach ($validated['consumables'] as $itemId => $data) {
                    $qty = $data['quantity'];
                    $item = Item::findOrFail($itemId);
                    if ($item->type !== 'consumable') {
                        throw new \Exception("Item ID {$itemId} is not consumable");
                    }
                    if ($item->stock < $qty) {
                        throw new \Exception("Insufficient stock for item ID {$itemId}");
                    }
                    BorrowDetailConsumable::create([
                        'borrow_transaction_id' => $transaction->id,
                        'item_id' => $itemId,
                        'quantity' => $qty
                    ]);
                    // // Kurangi stok
                    // $item->stock -= $qty;
                    // $item->save();
                    // // Catat transaksi stok
                    // StockTransaction::create([
                    //     'item_id' => $itemId,
                    //     'type' => 'out',
                    //     'quantity' => $qty,
                    //     'description' => 'Borrowed via transaction ' . $transaction->id
                    // ]);
                }
            }

            DB::commit();

            $transaction->load(['detailUnits.itemUnit', 'detailConsumables.item']);
            return Format::apiResponse(201, 'Borrow transaction created successfully', $transaction);
        } catch (ValidationException $e) {
            DB::rollBack();
            return Format::apiResponse(422, 'Validation failed', null, $e->errors());
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return Format::apiResponse(404, 'Item or unit not found', null, $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return Format::apiResponse($e->getCode() ?: 500, 'Failed to create borrow transaction', null, $e->getMessage());
        }
    }
}
