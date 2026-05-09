<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RouteStop extends Model
{
    protected $fillable = [
        'zone_id',
        'name',
        'order_index',
        'latitude',
        'longitude',
        'radius_meters',
    ];

    protected $casts = [
        'latitude'     => 'float',
        'longitude'    => 'float',
        'order_index'  => 'integer',
        'radius_meters'=> 'integer',
    ];

    // ──────────────────────────────────────────────────────────────────────
    // Relasi
    // ──────────────────────────────────────────────────────────────────────

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Helper: hitung jarak haversine (meter) dari koordinat ke jalur ini
    // ──────────────────────────────────────────────────────────────────────

    public function distanceMetersFrom(float $lat, float $lng): float
    {
        $earthRadius = 6371000.0;

        $latFrom  = deg2rad($lat);
        $latTo    = deg2rad($this->latitude);
        $deltaLat = deg2rad($this->latitude - $lat);
        $deltaLon = deg2rad($this->longitude - $lng);

        $a = sin($deltaLat / 2) ** 2 +
             cos($latFrom) * cos($latTo) * sin($deltaLon / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    // ──────────────────────────────────────────────────────────────────────
    // Helper statis: temukan jalur terbaik untuk koordinat customer
    // Prioritas: dalam radius → ambil yang paling dekat
    // Fallback   : jika tidak ada yang masuk radius → ambil yang paling dekat
    // ──────────────────────────────────────────────────────────────────────

    /**
     * @return static|null
     */
    public static function detectForCoordinates(float $lat, float $lng, int $zoneId): ?self
    {
        if (abs($lat) < 0.0001 && abs($lng) < 0.0001) {
            return null;
        }

        $stops = static::where('zone_id', $zoneId)
            ->orderBy('order_index')
            ->get();

        if ($stops->isEmpty()) {
            return null;
        }

        // Pasang jarak ke masing-masing jalur
        $stopsWithDistance = $stops->map(function (self $stop) use ($lat, $lng) {
            $stop->_computed_distance = $stop->distanceMetersFrom($lat, $lng);
            return $stop;
        })->sortBy('_computed_distance');

        // Cari jalur dalam radius
        $inRadius = $stopsWithDistance->first(fn (self $s) => $s->_computed_distance <= $s->radius_meters);
        if ($inRadius) {
            return $inRadius;
        }

        // Fallback: paling dekat meskipun di luar radius
        return $stopsWithDistance->first();
    }
}
