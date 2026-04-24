<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'address',
        'zone',
        'phone',
        'conversation_state',
        'pending_message',
        'latitude',
        'longitude',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
