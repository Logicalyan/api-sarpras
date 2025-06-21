<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\ReturnTransaction;
use App\Models\ReturnDetail;
use App\Custom\Format;

class ReturnApprovalController extends Controller
{
    public function approve($id)
    {
        DB::beginTransaction();

        try {
            $return = ReturnTransaction::with(['returnDetails.borrowDetailUnit.itemUnit', 'borrowTransaction'])
                ->findOrFail($id);

            if ($return->status !== 'pending') {
                return Format::apiResponse(400, 'Return transaction is not pending');
            }

            // Update status item unit berdasarkan kondisi
            foreach ($return->returnDetails as $detail) {
                $itemUnit = $detail->borrowDetailUnit->itemUnit;

                if (in_array($detail->condition, ['damaged', 'lost'])) {
                    $itemUnit->status = 'unavailable';
                } else {
                    $itemUnit->status = 'available';
                }

                $itemUnit->condition = $detail->condition;
                $itemUnit->save();

                $borrowDetailUnit = $detail->borrowDetailUnit;
                $borrowDetailUnit->update([
                    'return_status' => 'returned',
                    'returned_at' => now(),
                ]);
            }

            // Update return transaction
            $return->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            // Periksa apakah semua item unit sudah dikembalikan
            $borrow = $return->borrowTransaction;
            $returnedCount = ReturnDetail::whereHas('borrowDetailUnit', function ($q) use ($borrow) {
                $q->where('borrow_transaction_id', $borrow->id);
            })->count();

            $totalBorrowed = $borrow->detailUnits()->count();

            $borrow->update([
                'status' => $returnedCount === $totalBorrowed ? 'returned' : 'partial',
                'returned_at' => now()
            ]);

            DB::commit();
            return Format::apiResponse(200, 'Return transaction approved', $return);
        } catch (\Exception $e) {
            DB::rollBack();
            return Format::apiResponse(500, 'Failed to approve return', null, $e->getMessage());
        }
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_note' => 'required|string'
        ]);

        try {
            $return = ReturnTransaction::findOrFail($id);

            if ($return->status !== 'pending') {
                return Format::apiResponse(400, 'Return transaction is not pending');
            }

            $return->update([
                'status' => 'rejected',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'rejection_note' => $request->rejection_note
            ]);

            return Format::apiResponse(200, 'Return transaction rejected', $return);
        } catch (\Exception $e) {
            return Format::apiResponse(500, 'Failed to reject return', null, $e->getMessage());
        }
    }
}
