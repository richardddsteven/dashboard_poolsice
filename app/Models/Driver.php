<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = ['name', 'phone', 'zone_id', 'username', 'password'];

    protected $hidden = ['password'];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }
}
