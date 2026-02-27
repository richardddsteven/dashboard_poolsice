<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    protected $fillable = ['name'];

    public function customers()
    {
        return $this->hasMany(\App\Models\Customer::class, 'zone', 'name');
    }
}
