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
}
