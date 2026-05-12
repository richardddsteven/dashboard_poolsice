<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\RouteRoutingService;
use Illuminate\Support\Facades\Log;

class Order extends Model
{
    /**
     * Cache hasil hitung jarak per request agar pasangan order-driver
     * tidak dihitung/log dua kali dalam satu siklus proses.
     *
     * @var array<string, array{route_distance_meters: float|null, straight_distance_meters: float|null}>
     */
    private static array $distanceInfoCache = [];

    protected $fillable = [
        'customer_id',
        'driver_id',
        'ice_type_id',
        'quantity',
        'phone',
        'items',
        'status',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function iceType()
    {
        return $this->belongsTo(IceType::class);
    }

    public function getEffectiveQuantityAttribute(): int
    {
        return max(1, (int) $this->quantity);
    }

    private function extractQuantityFromItems(string $items): int
    {
        $weight = (float) ($this->iceType?->weight ?? 0);

        // Prioritize quantity near matched weight (5kg/20kg) if possible.
        if (abs($weight - 5.0) < 0.01) {
            $qty = $this->extractQtyByWeight($items, 5);
            if ($qty > 0) {
                return $qty;
            }
        }

        if (abs($weight - 20.0) < 0.01) {
            $qty = $this->extractQtyByWeight($items, 20);
            if ($qty > 0) {
                return $qty;
            }
        }

        foreach ([5, 20] as $weightCandidate) {
            $qty = $this->extractQtyByWeight($items, $weightCandidate);
            if ($qty > 0) {
                return $qty;
            }
        }

        $patterns = [
            '/\b(?:nya|sebanyak)\s*(\d{1,3})\b/i',
            '/\b(\d{1,3})\s*(?:pcs|pc|buah|biji|psc|pieces|pca|pck)\b/i',
            '/\b(?:pcs|pc|buah|biji|psc|pieces|pca|pck)\s*(\d{1,3})\b/i',
            '/(?<!\d)(\d{1,3})(?!\d)/',
        ];

        foreach ($patterns as $pattern) {
            if (!preg_match($pattern, $items, $matches)) {
                continue;
            }

            $parsed = (int) ($matches[1] ?? 0);
            if ($parsed > 0 && $parsed <= 100 && $parsed !== 5 && $parsed !== 20) {
                return $parsed;
            }
        }

        return 0;
    }

    private function extractQtyByWeight(string $items, int $weight): int
    {
        $pattern = '/(?:\b' . $weight . '\s*kg\b\D{0,24}(\d{1,3})|(\d{1,3})\D{0,24}\b' . $weight . '\s*kg\b)/i';
        if (!preg_match($pattern, $items, $matches)) {
            return 0;
        }

        $front = $matches[1] ?? null;
        $back = $matches[2] ?? null;
        $parsed = (int) ($front ?: $back ?: 0);

        return ($parsed > 0 && $parsed <= 100) ? $parsed : 0;
    }

    /**
     * Cek apakah order dari customer masih valid diterima supir
     * berdasarkan urutan jalur supir saat ini.
     *
     * Aturan:
     *   - Supir boleh mundur max 1 jalur ke belakang
     *   - Order dari jalur yang sudah lebih dari 1 jalur di belakang supir
     *     bisa masuk jika dibuat dalam 15 menit terakhir (masih baru)
     *   - Jika supir belum punya posisi jalur → terima semua
     */
    public static function isEligibleForDriver(self $order, \App\Models\Driver $driver): bool
    {
        // Jika supir belum melaporkan posisi jalur → semua order diterima
        $driverStop = $driver->currentRouteStop;
        if (!$driverStop) {
            return true;
        }

        // Jika supir tidak punya data jalur yang diperbarui dalam 2 jam → anggap posisi tidak valid
        if ($driver->route_stop_updated_at && $driver->route_stop_updated_at->diffInMinutes(now()) > 120) {
            return true;
        }

        // Jika customer tidak punya route_stop → terima (belum ter-assign)
        $customerStop = $order->customer?->routeStop;
        if (!$customerStop) {
            return true;
        }

        $driverIndex   = (int) $driverStop->order_index;
        $customerIndex = (int) $customerStop->order_index;

        // Jalur customer >= jalur supir saat ini → di depan/sama → TERIMA
        if ($customerIndex >= $driverIndex) {
            Log::info('[Routing] Jarak supir-customer diterima karena customer berada di depan/sama jalur.', [
                'order_id' => $order->id,
                'driver_id' => $driver->id,
                'driver_stop_id' => $driverStop->id,
                'customer_stop_id' => $customerStop->id,
                'driver_index' => $driverIndex,
                'customer_index' => $customerIndex,
                'distance_meters' => null,
                'reason' => 'customer_ahead_or_same',
            ]);

            return true;
        }

        $distanceInfo = self::driverCustomerDistanceInfo($order, $driver);

        // Jalur customer berada di belakang supir.
        // Gunakan jarak jalan nyata antar jalur sebagai proxy apakah masih layak
        // untuk putar balik atau ambil order balik arah.
        $routeDistanceMeters = $distanceInfo['route_distance_meters'];

        if ($routeDistanceMeters !== null) {
            $eligible = $routeDistanceMeters <= self::maxBacktrackDistanceMeters();

            Log::info('[Routing] Jarak supir-customer dihitung lewat Google Maps.', [
                'order_id' => $order->id,
                'driver_id' => $driver->id,
                'driver_stop_id' => $driverStop->id,
                'customer_stop_id' => $customerStop->id,
                'driver_index' => $driverIndex,
                'customer_index' => $customerIndex,
                'distance_meters' => $routeDistanceMeters,
                'fallback_straight_distance_meters' => $distanceInfo['straight_distance_meters'],
                'max_backtrack_distance_meters' => self::maxBacktrackDistanceMeters(),
                'eligible' => $eligible,
                'source' => 'google_maps_directions',
            ]);

            return $eligible;
        }

        // Fallback jika routing API gagal: pakai jarak lurus agar sistem tetap berjalan.
        $straightDistanceMeters = $distanceInfo['straight_distance_meters'];

        Log::info('[Routing] Jarak supir-customer dihitung lewat fallback jarak lurus.', [
            'order_id' => $order->id,
            'driver_id' => $driver->id,
            'driver_stop_id' => $driverStop->id,
            'customer_stop_id' => $customerStop->id,
            'driver_index' => $driverIndex,
            'customer_index' => $customerIndex,
            'distance_meters' => $straightDistanceMeters,
            'route_distance_meters' => null,
            'max_backtrack_distance_meters' => self::maxBacktrackDistanceMeters(),
            'eligible' => $straightDistanceMeters <= self::maxBacktrackDistanceMeters(),
            'source' => 'straight_line_fallback',
        ]);

        return $straightDistanceMeters <= self::maxBacktrackDistanceMeters();
    }

    public static function driverCustomerDistanceInfo(self $order, \App\Models\Driver $driver): array
    {
        $cacheKey = $order->id . ':' . $driver->id;
        if (array_key_exists($cacheKey, self::$distanceInfoCache)) {
            return self::$distanceInfoCache[$cacheKey];
        }

        $result = [
            'route_distance_meters' => null,
            'straight_distance_meters' => null,
        ];

        $freshDriver = $driver->fresh(['currentRouteStop']);
        $freshOrder = $order->fresh(['customer.routeStop']);

        if (!$freshDriver || !$freshOrder) {
            Log::info('[Routing] Distance info skipped because order/driver could not be refreshed.', [
                'order_id' => $order->id,
                'driver_id' => $driver->id,
            ]);
            self::$distanceInfoCache[$cacheKey] = $result;

            return $result;
        }

        $driver = $freshDriver;
        $order = $freshOrder;

        $driverStop = $driver->currentRouteStop;
        $customerStop = $order->customer?->routeStop;

        if (!$driverStop || !$customerStop) {
            Log::info('[Routing] Distance info skipped because driver/customer route stop is missing.', [
                'order_id' => $order->id,
                'driver_id' => $driver->id,
                'driver_stop_id' => $driverStop?->id,
                'customer_stop_id' => $customerStop?->id,
                'driver_has_stop' => (bool) $driverStop,
                'customer_has_stop' => (bool) $customerStop,
            ]);
            self::$distanceInfoCache[$cacheKey] = $result;

            return $result;
        }

        if (
            abs((float) $driverStop->latitude) < 0.0001 ||
            abs((float) $driverStop->longitude) < 0.0001 ||
            abs((float) $customerStop->latitude) < 0.0001 ||
            abs((float) $customerStop->longitude) < 0.0001
        ) {
            Log::warning('[Routing] Invalid route stop coordinates detected, skipping distance calculation.', [
                'order_id' => $order->id,
                'driver_id' => $driver->id,
                'driver_stop_id' => $driverStop->id,
                'driver_latitude' => (float) $driverStop->latitude,
                'driver_longitude' => (float) $driverStop->longitude,
                'customer_stop_id' => $customerStop->id,
                'customer_latitude' => (float) $customerStop->latitude,
                'customer_longitude' => (float) $customerStop->longitude,
            ]);

            self::$distanceInfoCache[$cacheKey] = $result;

            return $result;
        }

        Log::info('[Routing] Calculating driver-customer distance using route stops.', [
            'order_id' => $order->id,
            'driver_id' => $driver->id,
            'driver_stop_id' => $driverStop->id,
            'driver_stop_name' => $driverStop->name,
            'driver_stop_index' => $driverStop->order_index,
            'driver_latitude' => (float) $driverStop->latitude,
            'driver_longitude' => (float) $driverStop->longitude,
            'customer_stop_id' => $customerStop->id,
            'customer_stop_name' => $customerStop->name,
            'customer_stop_index' => $customerStop->order_index,
            'customer_latitude' => (float) $customerStop->latitude,
            'customer_longitude' => (float) $customerStop->longitude,
        ]);

        $routeDistanceMeters = app(RouteRoutingService::class)->roadDistanceMeters(
            (float) $driverStop->latitude,
            (float) $driverStop->longitude,
            (float) $customerStop->latitude,
            (float) $customerStop->longitude
        );

        $straightDistanceMeters = $driverStop->distanceMetersFrom(
            (float) $customerStop->latitude,
            (float) $customerStop->longitude
        );

        $result = [
            'route_distance_meters' => $routeDistanceMeters,
            'straight_distance_meters' => $straightDistanceMeters,
        ];

        self::$distanceInfoCache[$cacheKey] = $result;

        return $result;
    }

    public static function maxBacktrackDistanceMeters(): int
    {
        return (int) config('services.routing.max_backtrack_distance_meters', env('ROUTING_MAX_BACKTRACK_DISTANCE_METERS', 1000));
    }
}
