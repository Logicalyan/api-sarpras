<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnTransaction extends Model
{
    protected $table = 'return_transactions';
    protected $fillable = ['borrow_transaction_id', 'user_id', 'status', 'approved_by', 'approved_at'];

    public function borrowTransaction()
    {
        return $this->belongsTo(BorrowTransaction::class);
    }

    public function returnDetails()
    {
        return $this->hasMany(ReturnDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
