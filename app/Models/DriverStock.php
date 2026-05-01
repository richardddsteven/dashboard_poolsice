<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class DriverStock extends Model
{
    protected $fillable = [
        'driver_id',
        'date',
        'stock_5kg',
        'stock_20kg',
    ];

    public function scopeForDate($query, ?string $date = null)
    {
        return $query->whereDate('date', $date ?? Carbon::today()->toDateString());
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
