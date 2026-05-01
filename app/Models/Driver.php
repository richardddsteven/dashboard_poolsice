<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = ['name', 'phone', 'zone_id', 'username', 'password', 'api_token', 'fcm_token'];

    protected $hidden = ['password', 'api_token'];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
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
