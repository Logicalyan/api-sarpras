<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BorrowDetailConsumable extends Model
{
    protected $table = 'borrow_detail_consumables';

    protected $fillable = [
        'borrow_transaction_id',
        'item_id',
        'quantity',
    ];

    public function borrowTransaction()
    {
        return $this->belongsTo(BorrowTransaction::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
