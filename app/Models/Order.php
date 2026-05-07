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
}
