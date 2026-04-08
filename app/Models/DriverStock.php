<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverStock extends Model
{
    protected $fillable = [
        'driver_id',
        'date',
        'stock_5kg',
        'stock_20kg',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
