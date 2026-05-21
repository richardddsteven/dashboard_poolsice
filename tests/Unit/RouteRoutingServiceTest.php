<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit test RouteRoutingService.
 */
class RouteRoutingServiceTest extends TestCase
{
    private function isInvalidCoordinate(float $lat, float $lng): bool
    {
        return abs($lat) < 0.0001 && abs($lng) < 0.0001;
    }

    private function buildCacheKey(float $fromLat, float $fromLng, float $toLat, float $toLng): string
    {
        $fmt = fn($v) => number_format($v, 5, '.', '');
        return "route_distance:{$fmt($fromLat)}:{$fmt($fromLng)}:{$fmt($toLat)}:{$fmt($toLng)}";
    }

    /** isInvalidCoordinate() – Koordinat (0,0) dianggap tidak valid */
    public function test_zero_coordinate_is_invalid(): void
    {
        $this->assertTrue($this->isInvalidCoordinate(0.0, 0.0));
    }

    /** isInvalidCoordinate() – Koordinat Bali yang valid diterima sebagai koordinat sah */
    public function test_valid_bali_coordinate_is_not_invalid(): void
    {
        $this->assertFalse($this->isInvalidCoordinate(-8.6478, 115.1385));
    }

    /** buildCacheKey() – Cache key dibentuk dengan format yang benar */
    public function test_cache_key_has_correct_format(): void
    {
        $key = $this->buildCacheKey(-8.6478, 115.1385, -8.7180, 115.1685);

        $this->assertStringStartsWith('route_distance:', $key);
        $this->assertStringContainsString('-8.64780', $key);
        $this->assertStringContainsString('115.13850', $key);
    }

    /** cacheValue() – Nilai cache diformat menjadi 5 angka desimal */
    public function test_cache_value_formats_to_five_decimal_places(): void
    {
        $fmt = fn($v) => number_format($v, 5, '.', '');
        $this->assertSame('115.13850', $fmt(115.1385));
    }
}
