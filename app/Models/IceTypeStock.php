<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class IceTypeStock extends Model
{
    protected $table = 'ice_type_stocks';

    protected $fillable = [
        'ice_type_id',
        'date',
        'quantity',
    ];

    public function iceType()
    {
        return $this->belongsTo(IceType::class);
    }

    public function scopeForDate($query, ?string $date = null)
    {
        return $query->whereDate('date', $date ?? Carbon::today()->toDateString());
    }

    /**
     * Get all stocks
     */
    public static function getTodayStocks(?string $date = null)
    {
        return static::forDate($date)
            ->with('iceType')
            ->get()
            ->keyBy(fn($stock) => $stock->ice_type_id)
            ->map(fn($stock) => [
                'id' => $stock->ice_type_id,
                'name' => $stock->iceType->name,
                'weight' => $stock->iceType->weight,
                'price' => $stock->iceType->price,
                'quantity' => $stock->quantity,
            ]);
    }

    /**
     * Get total untuk semua ice types
     */
    public static function getTodayTotal(?string $date = null): int
    {
        return static::forDate($date)->sum('quantity');
    }
}
