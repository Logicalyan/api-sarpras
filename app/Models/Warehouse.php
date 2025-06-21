<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $table = 'warehouses';
    protected $fillable = [
        'name',
        'location',
    ];

    public function items()
    {
        return $this->hasMany(Item::class);
    }

}
