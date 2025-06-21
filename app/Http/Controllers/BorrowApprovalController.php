<?php

namespace App\Http\Controllers;

use App\Custom\Format;
use App\Models\BorrowTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BorrowApprovalController extends Controller
{
    public function approve($id)
    {
        DB::beginTransaction();

        try {
            $transaction = BorrowTransaction::with(['detailUnits.itemUnit', 'detailConsumables.item'])->findOrFail($id);

            if ($transaction->approval_status !== 'pending') {
                return Format::apiResponse(400, 'Transaction is not in pending state');
            }

            // Update item_unit status to unavailable
            foreach ($transaction->detailUnits as $detail) {
                $detail->itemUnit->update(['status' => 'borrowed']);
            }

            // Reduce consumable stock
            foreach ($transaction->detailConsumables as $detail) {
                // Buat record pengeluaran stok
                \App\Models\StockTransaction::create([
                    'item_id' => $detail->item_id,
                    'type' => 'out',
                    'quantity' => $detail->quantity,
                    'description' => 'Borrow approved #' . $transaction->borrow_code
                ]);
            }

            $transaction->update([
                'approval_status' => 'approved',
                'status' => count($transaction->detailUnits) > 0 ? 'borrowed' : 'not_applicable',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            DB::commit();
            return Format::apiResponse(200, 'Transaction approved', $transaction);
        } catch (\Exception $e) {
            DB::rollBack();
            return Format::apiResponse(500, 'Failed to approve transaction', null, $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_note' => 'required|string'
        ]);

        $transaction = BorrowTransaction::findOrFail($id);

        if ($transaction->approval_status !== 'pending') {
            return Format::apiResponse(400, 'Transaction is not in pending state');
        }

        $transaction->update([
            'approval_status' => 'rejected',
            'status' => 'not_applicable',
            'rejection_note' => $request->rejection_note,
            'approved_by' => auth()->id(),
            'approved_at' => now()
        ]);

        return Format::apiResponse(200, 'Transaction rejected', $transaction);
    }
}
