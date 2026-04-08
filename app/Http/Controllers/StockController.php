<?php

namespace App\Http\Controllers;

use App\Models\DriverStock;
use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Stock::query()->orderByDesc('date');
        $driverStockQuery = DriverStock::query()
            ->with(['driver:id,name'])
            ->orderByDesc('date')
            ->orderByDesc('updated_at');

        if ($request->filled('month')) {
            $parts = explode('-', (string) $request->month);
            if (count($parts) === 2) {
                $year = (int) $parts[0];
                $month = (int) $parts[1];

                $query->whereYear('date', $year)
                    ->whereMonth('date', $month);

                $driverStockQuery->whereYear('date', $year)
                    ->whereMonth('date', $month);
            }
        }

        $stocks = $query->get();
        $driverStocks = $driverStockQuery->get();

        return view('stocks.index', compact('stocks', 'driverStocks'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'stock_5kg' => ['required', 'integer', 'min:0'],
            'stock_20kg' => ['required', 'integer', 'min:0'],
        ]);

        $stock = Stock::updateOrCreate(
            ['date' => $validated['date']],
            [
                'stock_5kg' => $validated['stock_5kg'],
                'stock_20kg' => $validated['stock_20kg'],
            ]
        );

        $message = $stock->wasRecentlyCreated
            ? 'Stok harian berhasil ditambahkan.'
            : 'Stok harian untuk tanggal tersebut berhasil diperbarui.';

        return redirect()->route('stocks.index')->with('success', $message);
    }
}
