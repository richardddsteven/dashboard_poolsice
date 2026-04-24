<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $fillable = ['name', 'latitude', 'longitude'];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    public function customers()
    {
        return $this->hasMany(\App\Models\Customer::class, 'zone', 'name');
    }
}
