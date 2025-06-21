<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BorrowDetailUnit extends Model
{
    protected $table = 'borrow_detail_units';

    protected $fillable = [
        'borrow_transaction_id',
        'item_unit_id',
        'return_status',
        'returned_at',
    ];

    public function borrowTransaction()
    {
        return $this->belongsTo(BorrowTransaction::class);
    }

    public function itemUnit()
    {
        return $this->belongsTo(ItemUnit::class);
    }
}
