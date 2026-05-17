<?php

namespace App\Services;

use App\Models\Zone;
use App\Models\Order;

class RouteUpdateNotificationService
{
    /**
     * Bangun notifikasi update jalur dari order terbaru.
     *
     * Notifikasi hanya ditampilkan untuk customer baru yang baru pertama kali
     * melakukan order, agar admin tidak dibanjiri alert berulang untuk jalur yang sama.
     */
    public function buildFromOrder(?Order $order): ?array
    {
        if (!$order) {
            return null;
        }

        $order->loadMissing(['customer.routeStop.zone']);

        $customer = $order->customer;
        $routeStop = $customer?->routeStop;
        $zoneName = trim((string) ($customer?->zone ?? ''));

        if (!$customer || $zoneName === '' || $routeStop) {
            return null;
        }

        $routeName = $this->extractRoadName((string) ($customer->address ?? ''));
        if ($routeName === null) {
            return null;
        }

        $customerName = trim((string) ($customer->name ?: 'Customer baru'));
        $zone = Zone::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($zoneName)])
            ->first();

        $customerLatitude = is_numeric($customer->latitude ?? null) ? (float) $customer->latitude : null;
        $customerLongitude = is_numeric($customer->longitude ?? null) ? (float) $customer->longitude : null;

        $query = array_filter([
            'hint_route_name' => $routeName,
            'hint_customer_name' => $customerName,
            'hint_zone_name' => $zoneName,
            'hint_latitude' => $customerLatitude,
            'hint_longitude' => $customerLongitude,
        ], static fn ($value) => $value !== null && $value !== '');

        $actionUrl = $zone
            ? route('route-stops.index', $zone) . (empty($query) ? '' : '?' . http_build_query($query))
            : route('zones.create');

        return [
            'order_id' => $order->id,
            'customer_name' => $customerName,
            'zone_name' => $zoneName,
            'route_name' => $routeName,
            'message' => "Order baru dari {$customerName} terdeteksi di zona {$zoneName} pada jalan {$routeName}. Mohon update alur jalur di zona ini.",
            'action_url' => $actionUrl,
            'hint_latitude' => $customerLatitude,
            'hint_longitude' => $customerLongitude,
        ];
    }

    private function extractRoadName(string $address): ?string
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $address) ?? '');
        if ($normalized === '') {
            return null;
        }

        $patterns = [
            '/\b(?:jalan|jln\.?|jl\.?)\s+([^,\-\n]+)/i',
            '/\b(?:gang|gg\.?)\s+([^,\-\n]+)/i',
            '/\b(?:street|st\.)\s+([^,\-\n]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalized, $matches)) {
                $candidate = trim($matches[1]);
                if ($candidate !== '') {
                    return $this->normalizeRoadLabel($candidate);
                }
            }
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $normalized))));
        if (!empty($parts)) {
            return $this->normalizeRoadLabel($parts[0]);
        }

        return null;
    }

    private function normalizeRoadLabel(string $label): string
    {
        $label = trim($label, " \t\n\r\0\x0B-.,");
        return preg_replace('/\s+/', ' ', $label) ?? $label;
    }
}