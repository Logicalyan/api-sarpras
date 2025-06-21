<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BorrowTransaction extends Model
{
    protected $table = 'borrow_transactions';

    protected $fillable = [
        'user_id',
        'borrow_code',
        'borrow_date',
        'return_date',
        'approval_status',
        'status',
        'approved_by',
        'approved_at',
        'rejection_note',
        'returned_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function detailUnits()
    {
        return $this->hasMany(BorrowDetailUnit::class);
    }

    public function detailConsumables()
    {
        return $this->hasMany(BorrowDetailConsumable::class);
    }

    public function returnTransactions()
    {
        return $this->hasMany(ReturnTransaction::class);
    }
}
