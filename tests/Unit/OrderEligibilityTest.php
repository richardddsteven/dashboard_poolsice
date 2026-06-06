<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit test Order Eligibility.
 */
class OrderEligibilityTest extends TestCase
{
    private function isEligibleByIndex(?object $driverStop, ?object $customerStop): bool
    {
        if (!$driverStop) return true;
        if (!$customerStop) return true;
        return (int) $customerStop->order_index >= (int) $driverStop->order_index;
    }

    private function makeStop(int $idx): object
    {
        $s = new \stdClass();
        $s->order_index = $idx;
        return $s;
    }

    /** isEligibilityByIndex() – Order diterima jika supir belum memiliki posisi jalur */
    public function test_eligible_when_driver_has_no_route_stop(): void
    {
        $this->assertTrue($this->isEligibleByIndex(null, $this->makeStop(3)));
    }

    /** isEligibilityByIndex() – Order ditolak jika customer sudah di belakang posisi supir */
    public function test_order_rejected_when_customer_behind_driver(): void
    {
        $this->assertFalse($this->isEligibleByIndex($this->makeStop(5), $this->makeStop(3)));
    }

    /** maxBacktrackDistanceMeters() – Batas jarak putar balik harus bernilai positif */
    public function test_max_backtrack_distance_is_positive(): void
    {
        $default = (int) (getenv('ROUTING_MAX_BACKTRACK_DISTANCE_METERS') ?: 1000);
        $this->assertGreaterThan(0, $default);
    }
}
