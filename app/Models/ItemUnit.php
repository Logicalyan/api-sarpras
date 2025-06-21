<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemUnit extends Model
{
    protected $table = 'item_units';

    protected $fillable = [
        'item_id',
        'serial_number',
        'condition',
        'status',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function borrowDetailUnits()
    {
        return $this->hasMany(BorrowDetailUnit::class);
    }

    public function returnDetails()
    {
        return $this->hasMany(ReturnDetail::class);
    }

}
