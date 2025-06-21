<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockTransaction extends Model
{
    protected $table = 'stock_transactions';

    protected $fillable = [
        'item_id',
        'type',
        'quantity',
        'description',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }
}
