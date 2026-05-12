<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RouteRoutingService
{
    private int $timeoutSeconds;
    private ?string $googleMapsApiKey;
    private string $googleMapsEndpoint;
    private string $googleMapsMode;
    private ?string $googleMapsLanguage;
    private ?string $googleMapsRegion;

    public function __construct()
    {
        $routingConfig = config('services.routing', []);
        $googleMapsConfig = $routingConfig['google_maps'] ?? [];

        $this->timeoutSeconds = (int) ($routingConfig['timeout'] ?? env('ROUTING_API_TIMEOUT', 8));
        $this->googleMapsApiKey = $googleMapsConfig['api_key'] ?? env('GOOGLE_MAPS_API_KEY');
        $this->googleMapsEndpoint = rtrim($googleMapsConfig['endpoint'] ?? env('GOOGLE_MAPS_DIRECTIONS_ENDPOINT', 'https://maps.googleapis.com/maps/api/directions/json'), '/');
        $this->googleMapsMode = (string) ($googleMapsConfig['mode'] ?? env('GOOGLE_MAPS_DIRECTIONS_MODE', 'driving'));
        $this->googleMapsLanguage = $googleMapsConfig['language'] ?? env('GOOGLE_MAPS_DIRECTIONS_LANGUAGE', 'id');
        $this->googleMapsRegion = $googleMapsConfig['region'] ?? env('GOOGLE_MAPS_DIRECTIONS_REGION', 'id');
    }

    /**
     * Ambil jarak jalan nyata antar dua titik dalam meter.
     * Mengembalikan null jika routing API gagal atau tidak menemukan rute.
     */
    public function roadDistanceMeters(float $fromLat, float $fromLng, float $toLat, float $toLng): ?float
    {
        if ($this->isInvalidCoordinate($fromLat, $fromLng) || $this->isInvalidCoordinate($toLat, $toLng)) {
            return null;
        }

        if (empty($this->googleMapsApiKey)) {
            Log::warning('[Routing] GOOGLE_MAPS_API_KEY belum diisi.');
            return null;
        }

        $cacheKey = sprintf(
            'route_distance:%s:%s:%s:%s',
            $this->cacheValue($fromLat),
            $this->cacheValue($fromLng),
            $this->cacheValue($toLat),
            $this->cacheValue($toLng)
        );

        return Cache::remember($cacheKey, now()->addHours(6), function () use ($fromLat, $fromLng, $toLat, $toLng) {
            $query = [
                'origin'      => $fromLat . ',' . $fromLng,
                'destination' => $toLat . ',' . $toLng,
                'mode'        => $this->googleMapsMode,
                'key'         => $this->googleMapsApiKey,
            ];

            if (!empty($this->googleMapsLanguage)) {
                $query['language'] = $this->googleMapsLanguage;
            }

            if (!empty($this->googleMapsRegion)) {
                $query['region'] = $this->googleMapsRegion;
            }

            try {
                $response = Http::timeout($this->timeoutSeconds)
                    ->get($this->googleMapsEndpoint, $query);

                if (! $response->successful()) {
                    Log::warning('[Routing] Gagal mengambil rute Google Maps.', [
                        'status' => $response->status(),
                        'endpoint' => $this->googleMapsEndpoint,
                    ]);

                    return null;
                }

                $distance = data_get($response->json(), 'routes.0.legs.0.distance.value');
                if (! is_numeric($distance)) {
                    Log::warning('[Routing] Google Maps tidak mengembalikan jarak rute yang valid.', [
                        'status' => data_get($response->json(), 'status'),
                    ]);
                    return null;
                }

                return (float) $distance;
            } catch (\Throwable $e) {
                Log::warning('[Routing] Exception saat hitung rute Google Maps: ' . $e->getMessage());
                return null;
            }
        });
    }

    private function isInvalidCoordinate(float $lat, float $lng): bool
    {
        return abs($lat) < 0.0001 && abs($lng) < 0.0001;
    }

    private function cacheValue(float $value): string
    {
        return number_format($value, 5, '.', '');
    }
}