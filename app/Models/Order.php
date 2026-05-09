<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
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
            return true;
        }

        // Jalur customer lebih dari 1 langkah di belakang supir → TOLAK LANGSUNG
        if ($customerIndex < $driverIndex - 1) {
            return false;
        }

        // Jalur customer tepat 1 langkah di belakang supir:
        // TERIMA HANYA JIKA order dibuat dalam 15 menit terakhir
        if ($customerIndex == $driverIndex - 1) {
            if ($order->created_at && $order->created_at->diffInMinutes(now()) <= 15) {
                return true;
            } else {
                return false;
            }
        }

        return false;
    }
}
