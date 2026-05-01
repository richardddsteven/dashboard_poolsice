<?php

namespace App\Http\Controllers;

use App\Models\DriverStock;
use App\Models\IceType;
use App\Models\IceTypeDriverStock;
use App\Models\IceTypeStock;
use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index()
    {
        // Fetch active ice types
        $iceTypes = IceType::getActiveTypes()->sortBy('weight');

        // Get current stocks for each ice type
        $todayStocks = IceTypeStock::getTodayStocks();
        $hasTodayStockInput = $todayStocks->isNotEmpty();

        // Get driver stocks in dynamic format
        $driverStockRows = $this->getDriverStockRows($iceTypes);

        // Fallback to old stock format if no ice type stocks found (for backward compatibility)
        $stock = Stock::first();

        return view('stocks.index', compact('iceTypes', 'todayStocks', 'stock', 'driverStockRows', 'hasTodayStockInput'));
    }

    public function store(Request $request)
    {
        // Get active ice types to build dynamic validation rules
        $iceTypes = IceType::getActiveTypes();
        $rules = [
            'date' => ['nullable', 'date'],
        ];

        foreach ($iceTypes as $iceType) {
            $rules["stock_{$iceType->id}"] = ['required', 'integer', 'min:0'];
        }

        $validated = $request->validate($rules);
        $stockDate = $validated['date'] ?? now()->toDateString();

        // Save stocks for each ice type
        foreach ($iceTypes as $iceType) {
            $quantity = $validated["stock_{$iceType->id}"] ?? 0;

            IceTypeStock::updateOrCreate(
                [
                    'ice_type_id' => $iceType->id,
                    'date' => $stockDate,
                ],
                [
                    'quantity' => $quantity,
                ]
            );
        }

        // Also save to old stocks table for backward compatibility
        $total5kg = 0;
        $total20kg = 0;

        foreach ($iceTypes as $iceType) {
            $quantity = $validated["stock_{$iceType->id}"] ?? 0;

            if (abs((float)$iceType->weight - 5.0) < 0.01) {
                $total5kg = $quantity;
            } elseif (abs((float)$iceType->weight - 20.0) < 0.01) {
                $total20kg = $quantity;
            }
        }

        if ($total5kg > 0 || $total20kg > 0) {
            Stock::updateOrCreate(
                ['date' => $stockDate],
                [
                    'stock_5kg' => $total5kg,
                    'stock_20kg' => $total20kg,
                ]
            );
        }

        return redirect()->route('stocks.index')
            ->with('success', 'Stok berhasil disimpan.');
    }

    public function realtimeToday()
    {
        // Get ice types stocks
        $iceTypes = IceType::getActiveTypes()->sortBy('weight');
        $todayStocks = IceTypeStock::getTodayStocks();
        $hasTodayStockInput = $todayStocks->isNotEmpty();

        $allDriverStocks = $this->getDriverStockRows($iceTypes);

        // Build stock summary
        $stockSummary = [];
        foreach ($iceTypes as $iceType) {
            $stockData = $todayStocks->get($iceType->id);
            $stockSummary[] = [
                'id' => $iceType->id,
                'name' => $iceType->name,
                'weight' => (float) $iceType->weight,
                'quantity' => is_array($stockData) ? ($stockData['quantity'] ?? 0) : 0,
            ];
        }

        return response()->json([
            'data' => [
                'stocks' => $stockSummary,
                'total' => IceTypeStock::getTodayTotal(),
                'has_today_stock_input' => $hasTodayStockInput,
                'ice_types' => $iceTypes->map(fn (IceType $iceType) => [
                    'id' => $iceType->id,
                    'name' => $iceType->name,
                    'weight' => (float) $iceType->weight,
                ])->values(),
                'driver_stocks' => $allDriverStocks,
            ],
        ]);
    }

    private function getDriverStockRows($iceTypes)
    {
        $driverStocksNew = IceTypeDriverStock::query()
            ->with(['driver:id,name'])
            ->forDate()
            ->get()
            ->groupBy('driver_id')
            ->map(function ($group) use ($iceTypes) {
                $firstRecord = $group->first();

                $row = [
                    'driver_id' => $firstRecord->driver_id,
                    'driver_name' => $firstRecord->driver?->name,
                    'format' => 'new',
                    'updated_at' => $firstRecord->updated_at,
                ];

                foreach ($iceTypes as $iceType) {
                    $stock = $group->firstWhere('ice_type_id', $iceType->id);
                    $row["qty_{$iceType->id}"] = $stock ? (int) $stock->quantity : 0;
                }

                return $row;
            })
            ->values();

        $usedDriverIds = $driverStocksNew->pluck('driver_id')->toArray();
        $driverStocksOld = DriverStock::query()
            ->with(['driver:id,name'])
            ->whereNotIn('driver_id', $usedDriverIds)
            ->get()
            ->map(function (DriverStock $driverStock) use ($iceTypes) {
                $row = [
                    'driver_id' => $driverStock->driver_id,
                    'driver_name' => $driverStock->driver?->name,
                    'format' => 'old',
                    'updated_at' => $driverStock->updated_at,
                ];

                foreach ($iceTypes as $iceType) {
                    if (abs((float) $iceType->weight - 5.0) < 0.01) {
                        $row["qty_{$iceType->id}"] = (int) $driverStock->stock_5kg;
                    } elseif (abs((float) $iceType->weight - 20.0) < 0.01) {
                        $row["qty_{$iceType->id}"] = (int) $driverStock->stock_20kg;
                    } else {
                        $row["qty_{$iceType->id}"] = 0;
                    }
                }

                return $row;
            })
            ->values();

        return $driverStocksNew
            ->concat($driverStocksOld)
            ->values();
    }


}
