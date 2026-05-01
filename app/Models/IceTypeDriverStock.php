<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class IceTypeDriverStock extends Model
{
    protected $table = 'ice_type_driver_stocks';

    protected $fillable = [
        'driver_id',
        'ice_type_id',
        'date',
        'quantity',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function iceType()
    {
        return $this->belongsTo(IceType::class);
    }

    public function scopeForDate($query, ?string $date = null)
    {
        return $query->whereDate('date', $date ?? Carbon::today()->toDateString());
    }

    /**
     * Get all stocks untuk driver
     */
    public static function getTodayStocks(int $driverId, ?string $date = null)
    {
        return static::forDate($date)
            ->where('driver_id', $driverId)
            ->with('iceType')
            ->get()
            ->keyBy(fn($stock) => $stock->ice_type_id)
            ->map(fn($stock) => [
                'id' => $stock->ice_type_id,
                'name' => $stock->iceType->name,
                'weight' => $stock->iceType->weight,
                'quantity' => $stock->quantity,
            ]);
    }

    /**
     * Check apakah driver sudah input stok
     */
    public static function hasStockInputToday(int $driverId, ?string $date = null): bool
    {
        return static::forDate($date)
            ->where('driver_id', $driverId)
            ->exists();
    }

    /**
     * Dapatkan total sisa stok setelah order tertentu
     */
    public static function getRemainingStockAfterOrder(int $driverId, int $iceTypeId, ?string $date = null): int
    {
        $stock = static::forDate($date)
            ->where('driver_id', $driverId)
            ->where('ice_type_id', $iceTypeId)
            ->first();

        if (!$stock) {
            return 0;
        }

        // Hitung orders yang sudah di-complete atau approved
        $usedQuantity = Order::where('driver_id', $driverId)
            ->where('ice_type_id', $iceTypeId)
            ->whereIn('status', ['approved', 'completed'])
            ->sum('quantity');

        return max(0, $stock->quantity - $usedQuantity);
    }
}
