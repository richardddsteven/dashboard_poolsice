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
        'route_stop_id',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function routeStop()
    {
        return $this->belongsTo(\App\Models\RouteStop::class);
    }
}
