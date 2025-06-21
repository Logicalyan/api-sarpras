<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    protected $table = 'user_profiles';
    protected $fillable = [
        'user_id',
        'phone',
        'address',
        'city',
        'state',
        'zip',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
