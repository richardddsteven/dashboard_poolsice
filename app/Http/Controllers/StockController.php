<?php

namespace App\Http\Controllers;

use App\Models\DriverStock;
use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();
        $this->cleanupOldStockData($today);

        $stock = Stock::query()
            ->whereDate('date', $today)
            ->first();

        $driverStocks = DriverStock::query()
            ->with(['driver:id,name'])
            ->whereDate('date', $today)
            ->orderByDesc('updated_at')
            ->get();

        return view('stocks.index', compact('stock', 'driverStocks', 'today'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'stock_5kg' => ['required', 'integer', 'min:0'],
            'stock_20kg' => ['required', 'integer', 'min:0'],
        ]);

        $today = now()->toDateString();
        $this->cleanupOldStockData($today);

        $stock = Stock::updateOrCreate(
            ['date' => $today],
            [
                'stock_5kg' => $validated['stock_5kg'],
                'stock_20kg' => $validated['stock_20kg'],
            ]
        );

        $message = $stock->wasRecentlyCreated
            ? 'Stok hari ini berhasil disimpan.'
            : 'Stok hari ini berhasil diperbarui.';

        return redirect()->route('stocks.index')->with('success', $message);
    }

    public function realtimeToday()
    {
        $today = now()->toDateString();
        $this->cleanupOldStockData($today);

        $stock = Stock::query()
            ->whereDate('date', $today)
            ->first();

        $driverStocks = DriverStock::query()
            ->with(['driver:id,name'])
            ->whereDate('date', $today)
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (DriverStock $driverStock) {
                return [
                    'id' => $driverStock->id,
                    'driver_id' => $driverStock->driver_id,
                    'driver_name' => $driverStock->driver?->name,
                    'date' => $driverStock->date?->format('Y-m-d'),
                    'stock_5kg' => (int) $driverStock->stock_5kg,
                    'stock_20kg' => (int) $driverStock->stock_20kg,
                    'updated_at' => $driverStock->updated_at,
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'date' => $today,
                'stock' => [
                    'stock_5kg' => (int) ($stock->stock_5kg ?? 0),
                    'stock_20kg' => (int) ($stock->stock_20kg ?? 0),
                    'has_stock_input' => !is_null($stock),
                    'updated_at' => $stock?->updated_at,
                ],
                'driver_stocks' => $driverStocks,
            ],
        ]);
    }

    private function cleanupOldStockData(string $today): void
    {
        Stock::query()
            ->whereDate('date', '<', $today)
            ->delete();

        DriverStock::query()
            ->whereDate('date', '<', $today)
            ->delete();
    }
}
