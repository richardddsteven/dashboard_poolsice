<?php

namespace Tests\Unit;

use App\Models\RouteStop;
use PHPUnit\Framework\TestCase;

/**
 * Unit test RouteStop::distanceMetersFrom()
 */
class RouteStopDistanceTest extends TestCase
{
    /** distanceMetersFrom() – Jarak Canggu→Kuta harus dalam kisaran 7–12 km */
    public function test_distance_between_two_real_coordinates(): void
    {
        $stop = new RouteStop();
        $stop->latitude  = -8.6478;
        $stop->longitude = 115.1385;

        $distance = $stop->distanceMetersFrom(-8.7180, 115.1685);

        $this->assertGreaterThan(7000, $distance);
        $this->assertLessThan(12000, $distance);
    }

    /** distanceMetersFrom() – Jarak ke titik yang sama menghasilkan nol */
    public function test_distance_to_same_point_is_zero(): void
    {
        $stop = new RouteStop();
        $stop->latitude  = -8.6478;
        $stop->longitude = 115.1385;

        $this->assertEqualsWithDelta(0.0, $stop->distanceMetersFrom(-8.6478, 115.1385), 0.001);
    }
}
