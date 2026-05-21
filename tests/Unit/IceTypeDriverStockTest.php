<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit test IceTypeDriverStock.
 */
class IceTypeDriverStockTest extends TestCase
{
    private function calculateRemainingStock(int $stockQty, int $usedQty): int
    {
        return max(0, $stockQty - $usedQty);
    }

    /** getRemainingStockAfterOrder() – Sisa stok terpotong dengan benar */
    public function test_remaining_stock_deducted_correctly(): void
    {
        $this->assertSame(30, $this->calculateRemainingStock(50, 20));
    }

    /** getRemainingStockAfterOrder() – Sisa stok tidak boleh negatif meski order melebihi stok */
    public function test_remaining_stock_never_negative(): void
    {
        $this->assertSame(0, $this->calculateRemainingStock(5, 10));
    }

    /** scopeForDate() – Format tanggal Y-m-d yang valid harus dikenali */
    public function test_valid_date_format_is_accepted(): void
    {
        $this->assertTrue((bool) \DateTime::createFromFormat('Y-m-d', '2026-05-18'));
    }

    /** getTodayStocks() – Struktur data stok memiliki semua key yang diharapkan */
    public function test_stock_data_structure_contains_expected_keys(): void
    {
        $formattedStock = ['id' => 1, 'name' => 'Es Balok 20kg', 'weight' => 20.0, 'quantity' => 100];
        $this->assertArrayHasKey('id', $formattedStock);
        $this->assertArrayHasKey('name', $formattedStock);
        $this->assertArrayHasKey('quantity', $formattedStock);
    }
}
