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

        // Jika data posisi supir sudah lebih dari 2 jam tidak diperbarui,
        // supir ini kemungkinan sudah selesai bertugas atau GPS-nya tidak aktif.
        // Lewati supir ini agar tidak mendapat notifikasi order yang sudah tidak relevan.
        // (Sebelumnya: return true — supir menerima SEMUA order, termasuk yang harusnya sudah selesai)
        if ($driver->route_stop_updated_at && $driver->route_stop_updated_at->diffInMinutes(now()) > 120) {
            Log::info('[Routing] Driver dilewati karena posisi jalur sudah > 2 jam tidak diperbarui.', [
                'driver_id'                => $driver->id,
                'order_id'                 => $order->id,
                'last_updated_minutes_ago' => (int) $driver->route_stop_updated_at->diffInMinutes(now()),
            ]);
            return false;
        }

        // Jika customer tidak punya route_stop, pakai koordinat customer agar
        // jalan baru yang belum dipetakan tidak otomatis diterima.
        $customerStop = $order->customer?->routeStop;
        $driverIndex   = (int) $driverStop->order_index;
        if ($customerStop) {
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
        }

        $distanceInfo = self::driverCustomerDistanceInfo($order, $driver);

        // Jalur customer berada di belakang supir, atau belum terpetakan.
        // Gunakan jarak jalan nyata antar posisi sebagai proxy apakah masih layak
        // untuk putar balik atau ambil order balik arah.
        $routeDistanceMeters = $distanceInfo['route_distance_meters'];

        if ($routeDistanceMeters !== null) {
            $eligible = $routeDistanceMeters <= self::maxBacktrackDistanceMeters();

            Log::info('[Routing] Jarak supir-customer dihitung lewat Google Maps.', [
                'order_id' => $order->id,
                'driver_id' => $driver->id,
                'driver_stop_id' => $driverStop->id,
                'customer_stop_id' => $customerStop?->id,
                'driver_index' => $driverIndex,
                'customer_index' => $customerStop ? (int) $customerStop->order_index : null,
                'distance_meters' => $routeDistanceMeters,
                'fallback_straight_distance_meters' => $distanceInfo['straight_distance_meters'],
                'max_backtrack_distance_meters' => self::maxBacktrackDistanceMeters(),
                'eligible' => $eligible,
                'source' => $customerStop ? 'google_maps_directions' : 'google_maps_directions_customer_unmapped',
            ]);

            return $eligible;
        }

        // Fallback jika routing API gagal: pakai jarak lurus agar sistem tetap berjalan.
        $straightDistanceMeters = $distanceInfo['straight_distance_meters'];

        Log::info('[Routing] Jarak supir-customer dihitung lewat fallback jarak lurus.', [
            'order_id' => $order->id,
            'driver_id' => $driver->id,
            'driver_stop_id' => $driverStop->id,
            'customer_stop_id' => $customerStop?->id,
            'driver_index' => $driverIndex,
            'customer_index' => $customerStop ? (int) $customerStop->order_index : null,
            'distance_meters' => $straightDistanceMeters,
            'route_distance_meters' => null,
            'max_backtrack_distance_meters' => self::maxBacktrackDistanceMeters(),
            'eligible' => $straightDistanceMeters <= self::maxBacktrackDistanceMeters(),
            'source' => $customerStop ? 'straight_line_fallback' : 'straight_line_fallback_customer_unmapped',
        ]);

        return $straightDistanceMeters <= self::maxBacktrackDistanceMeters();
    }

    public static function driverCustomerDistanceInfo(self $order, \App\Models\Driver $driver): array
    {
        // Cache key per-request: cukup order_id + driver_id.
        // RouteRoutingService memiliki cache 6 jam tersendiri berdasarkan koordinat origin-destination,
        // sehingga Google Maps tidak dipanggil ulang meskipun supir bergerak sedikit.
        $cacheKey = $order->id . ':' . $driver->id;

        if (array_key_exists($cacheKey, self::$distanceInfoCache)) {
            return self::$distanceInfoCache[$cacheKey];
        }

        $result = [
            'route_distance_meters'    => null,
            'straight_distance_meters' => null,
        ];

        $freshDriver = $driver->fresh(['currentRouteStop']);
        $freshOrder  = $order->fresh(['customer.routeStop']);

        if (!$freshDriver || !$freshOrder) {
            Log::info('[Routing] Distance info skipped because order/driver could not be refreshed.', [
                'order_id'  => $order->id,
                'driver_id' => $driver->id,
            ]);
            self::$distanceInfoCache[$cacheKey] = $result;

            return $result;
        }

        $driver = $freshDriver;
        $order  = $freshOrder;

        $driverStop      = $driver->currentRouteStop;
        $customerStop    = $order->customer?->routeStop;
        $customerLatitude  = $order->customer?->latitude;
        $customerLongitude = $order->customer?->longitude;

        $hasCustomerCoordinates = is_numeric($customerLatitude) && is_numeric($customerLongitude)
            && abs((float) $customerLatitude) >= 0.0001
            && abs((float) $customerLongitude) >= 0.0001;

        // Gunakan GPS presisi supir jika tersedia, fallback ke koordinat route stop.
        $driverGpsLat = (float) ($driver->current_latitude ?? 0);
        $driverGpsLng = (float) ($driver->current_longitude ?? 0);
        $hasDriverGps = abs($driverGpsLat) >= 0.0001 && abs($driverGpsLng) >= 0.0001;

        // Tentukan koordinat origin (posisi supir)
        // Prioritas: GPS presisi → koordinat route stop
        $originLatitude  = $hasDriverGps ? $driverGpsLat : (float) ($driverStop?->latitude ?? 0);
        $originLongitude = $hasDriverGps ? $driverGpsLng : (float) ($driverStop?->longitude ?? 0);

        $hasOrigin = abs($originLatitude) >= 0.0001 && abs($originLongitude) >= 0.0001;

        if (!$hasOrigin || (!$customerStop && !$hasCustomerCoordinates)) {
            Log::info('[Routing] Distance info skipped because driver/customer coordinates are missing.', [
                'order_id'                 => $order->id,
                'driver_id'                => $driver->id,
                'driver_stop_id'           => $driverStop?->id,
                'customer_stop_id'         => $customerStop?->id,
                'driver_has_gps'           => $hasDriverGps,
                'driver_has_stop'          => (bool) $driverStop,
                'customer_has_stop'        => (bool) $customerStop,
                'customer_has_coordinates' => $hasCustomerCoordinates,
            ]);
            self::$distanceInfoCache[$cacheKey] = $result;

            return $result;
        }

        // Tujuan: koordinat route stop customer (lebih stabil) → koordinat langsung customer
        $targetLatitude  = $customerStop ? (float) $customerStop->latitude  : (float) $customerLatitude;
        $targetLongitude = $customerStop ? (float) $customerStop->longitude : (float) $customerLongitude;

        if (
            abs($originLatitude)  < 0.0001 ||
            abs($originLongitude) < 0.0001 ||
            abs($targetLatitude)  < 0.0001 ||
            abs($targetLongitude) < 0.0001
        ) {
            Log::warning('[Routing] Invalid coordinates detected, skipping distance calculation.', [
                'order_id'         => $order->id,
                'driver_id'        => $driver->id,
                'origin_latitude'  => $originLatitude,
                'origin_longitude' => $originLongitude,
                'driver_source'    => $hasDriverGps ? 'gps_precise' : 'route_stop',
                'customer_stop_id' => $customerStop?->id,
                'target_latitude'  => $targetLatitude,
                'target_longitude' => $targetLongitude,
            ]);

            self::$distanceInfoCache[$cacheKey] = $result;

            return $result;
        }

        $driverSource = $hasDriverGps ? 'gps_precise' : 'route_stop';

        Log::info('[Routing] Calculating driver-customer distance.', [
            'order_id'         => $order->id,
            'driver_id'        => $driver->id,
            'driver_source'    => $driverSource,
            'driver_stop_id'   => $driverStop?->id,
            'driver_stop_name' => $driverStop?->name,
            'driver_stop_index'=> $driverStop?->order_index,
            'origin_latitude'  => $originLatitude,
            'origin_longitude' => $originLongitude,
            'customer_stop_id' => $customerStop?->id,
            'customer_stop_name' => $customerStop?->name,
            'customer_stop_index' => $customerStop?->order_index,
            'target_latitude'  => $targetLatitude,
            'target_longitude' => $targetLongitude,
            'customer_source'  => $customerStop ? 'route_stop' : 'customer_coordinates',
        ]);

        // Jarak jalan nyata via Google Maps Directions API (di-cache 6 jam)
        $routeDistanceMeters = app(RouteRoutingService::class)->roadDistanceMeters(
            $originLatitude,
            $originLongitude,
            $targetLatitude,
            $targetLongitude
        );

        // Jarak lurus Haversine sebagai fallback jika Google Maps gagal
        $earthRadius  = 6371000.0;
        $latFrom      = deg2rad($originLatitude);
        $latTo        = deg2rad($targetLatitude);
        $deltaLat     = deg2rad($targetLatitude - $originLatitude);
        $deltaLon     = deg2rad($targetLongitude - $originLongitude);
        $a            = sin($deltaLat / 2) ** 2 + cos($latFrom) * cos($latTo) * sin($deltaLon / 2) ** 2;
        $straightDistanceMeters = $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));

        $result = [
            'route_distance_meters'    => $routeDistanceMeters,
            'straight_distance_meters' => $straightDistanceMeters,
        ];

        // Log hasil jarak supir → customer agar mudah dipantau di log Laravel.
        // Muncul sekali per cache-miss (setiap 6 jam per pasangan supir-customer).
        Log::info('[Routing] ✅ Jarak dihitung.', [
            'order_id'              => $order->id,
            'customer_name'         => $order->customer?->name,
            'driver_id'             => $driver->id,
            'driver_source'         => $driverSource,
            'driver_jalur'          => $driverStop?->name,
            'customer_jalur'        => $customerStop?->name ?? 'koordinat langsung',
            // Jarak jalan nyata (Google Maps) — mengikuti rute jalan sesungguhnya
            'google_maps_meter'     => $routeDistanceMeters !== null
                ? (int) round($routeDistanceMeters) . ' m'
                : 'gagal / tidak tersedia',
            // Jarak lurus udara (Haversine) — sebagai pembanding
            'haversine_meter'       => (int) round($straightDistanceMeters) . ' m',
            // Jarak yang dipakai untuk pengurutan antrian
            'dipakai_untuk_sort'    => $routeDistanceMeters !== null
                ? (int) round($routeDistanceMeters) . ' m (Google Maps)'
                : (int) round($straightDistanceMeters) . ' m (Haversine fallback)',
        ]);

        self::$distanceInfoCache[$cacheKey] = $result;

        return $result;
    }

    public static function maxBacktrackDistanceMeters(): int
    {
        return (int) config('services.routing.max_backtrack_distance_meters', env('ROUTING_MAX_BACKTRACK_DISTANCE_METERS', 1000));
    }
}
