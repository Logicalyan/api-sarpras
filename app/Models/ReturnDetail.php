<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnDetail extends Model
{
    protected $table = 'return_details';
    protected $fillable = ['return_transaction_id', 'borrow_detail_unit_id', 'condition'];
    public function returnTransaction()
    {
        return $this->belongsTo(ReturnTransaction::class);
    }

    public function borrowDetailUnit()
    {
        return $this->belongsTo(BorrowDetailUnit::class);
    }
}
