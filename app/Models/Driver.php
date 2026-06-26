<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'name', 'phone', 'zone_id', 'username', 'password',
        'api_token', 'fcm_token', 'current_route_stop_id', 'route_stop_updated_at',
        'current_latitude', 'current_longitude',
    ];

    protected $hidden = ['password', 'api_token'];

    protected $casts = [
        'route_stop_updated_at' => 'datetime',
        'current_latitude'      => 'float',
        'current_longitude'     => 'float',
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function currentRouteStop()
    {
        return $this->belongsTo(\App\Models\RouteStop::class, 'current_route_stop_id');
    }

    public function stocks()
    {
        return $this->hasMany(DriverStock::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
