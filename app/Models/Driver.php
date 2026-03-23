<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = ['name', 'phone', 'zone_id'];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
}
