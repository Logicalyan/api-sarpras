<?php

namespace App\Http\Controllers;

use App\Models\ReturnTransaction;
use App\Models\ReturnDetail;
use App\Models\BorrowTransaction;
use App\Models\BorrowDetailUnit;
use App\Models\ItemUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Custom\Format;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class ReturnTransactionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            throw new \Exception('Unauthorized', 401);
        }

        if ($user->role === 'admin') {
            $returns = ReturnTransaction::with(['borrowTransaction.user', 'returnDetails.borrowDetailUnit', 'returnDetails.borrowDetailUnit.itemUnit'])
                ->latest()
                ->get();
        } else {
            $returns = ReturnTransaction::whereHas('borrowTransaction', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
                ->with(['borrowTransaction.user', 'returnDetails.borrowDetailUnit', 'returnDetails.borrowDetailUnit.itemUnit'])
                ->latest()
                ->get();
        }

        return Format::apiResponse(200, 'List of return transactions', $returns);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            throw new \Exception('Unauthorized', 401);
        }

        $validated = $request->validate([
            'borrow_transaction_id' => [
                'required',
                'integer',
                'exists:borrow_transactions,id',
                function ($attribute, $value, $fail) use ($user) {
                    $borrow = BorrowTransaction::find($value);
                    if ($borrow && $user->role !== 'admin' && $borrow->user_id !== $user->id) {
                        $fail('You are not authorized to return this transaction.');
                    }
                    if ($borrow && $borrow->approval_status !== 'approved') {
                        $fail('This borrow transaction has not been approved.');
                    }
                    // Cek apakah transaksi sudah dikembalikan
                    // if ($borrow && ReturnTransaction::where('borrow_transaction_id', $value)->where('status', 'approved')->exists()) {
                    //     $fail('This borrow transaction has already been returned.');
                    // }
                },
            ],
            'returned_units' => 'required|array|min:1',
            'returned_units.*.item_unit_id' => [
                'required',
                'integer',
                'exists:item_units,id',
                function ($attribute, $value, $fail) use ($request) {
                    $borrowId = $request->input('borrow_transaction_id');
                    $exists = BorrowDetailUnit::where('borrow_transaction_id', $borrowId)
                        ->where('item_unit_id', $value)
                        ->exists();
                    if (!$exists) {
                        $fail("Item unit ID {$value} is not part of the specified borrow transaction.");
                    }
                    // Cek apakah unit sudah dikembalikan
                    $alreadyReturned = ReturnDetail::whereHas('borrowDetailUnit', function ($q) use ($value, $borrowId) {
                        $q->where('borrow_transaction_id', $borrowId)->where('item_unit_id', $value);
                    })->exists();
                    if ($alreadyReturned) {
                        $fail("Item unit ID {$value} has already been returned.");
                    }
                },
            ],
            'returned_units.*.condition' => 'required|in:good,damaged,lost',
        ]);

        DB::beginTransaction();

        try {
            $borrow = BorrowTransaction::with(['detailUnits', 'detailConsumables'])
                ->findOrFail($validated['borrow_transaction_id']);

            $returnTransaction = ReturnTransaction::create([
                'borrow_transaction_id' => $borrow->id,
                'user_id' => $user->id, // Pengguna yang mengajukan pengembalian
                'status' => 'pending', // Sesuai kolom status di model
                'approved_by' => null, // Null sampai disetujui admin
                'approved_at' => null, // Null sampai disetujui admin
            ]);

            foreach ($validated['returned_units'] as $unitData) {
                $detail = BorrowDetailUnit::where('borrow_transaction_id', $borrow->id)
                    ->where('item_unit_id', $unitData['item_unit_id'])
                    ->firstOrFail();

                ReturnDetail::create([
                    'return_transaction_id' => $returnTransaction->id,
                    'borrow_detail_unit_id' => $detail->id,
                    'condition' => $unitData['condition'],
                ]);
            }

            DB::commit();

            $returnTransaction->load(['borrowTransaction.user', 'returnDetails.itemUnit']);
            return Format::apiResponse(201, 'Return transaction submitted successfully', $returnTransaction);
        } catch (ValidationException $e) {
            DB::rollBack();
            return Format::apiResponse(422, 'Validation failed', null, $e->errors());
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return Format::apiResponse(404, 'Borrow transaction or unit not found', null, $e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return Format::apiResponse($e->getCode() ?: 500, 'Failed to submit return', null, $e->getMessage());
        }
    }
}
