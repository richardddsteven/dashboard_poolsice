<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\IceType;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinanceController extends Controller
{
    public function index(Request $request)
    {
        $filterType  = $request->input('filter_type', 'all'); // all | date | range | month | year
        $filterDate  = $request->input('filter_date');    // YYYY-MM-DD
        $filterStart = $request->input('filter_start');   // YYYY-MM-DD
        $filterEnd   = $request->input('filter_end');     // YYYY-MM-DD
        $filterMonth = $request->input('filter_month');   // 1-12
        $filterYear  = $request->input('filter_year');    // YYYY

        // Helper closure untuk menerapkan filter tanggal pada query
        $applyFilter = function ($query) use ($filterType, $filterDate, $filterStart, $filterEnd, $filterMonth, $filterYear) {
            if ($filterType === 'date' && $filterDate) {
                $query->whereDate('orders.created_at', $filterDate);
            } elseif ($filterType === 'range' && $filterStart && $filterEnd) {
                $query->whereDate('orders.created_at', '>=', $filterStart)
                      ->whereDate('orders.created_at', '<=', $filterEnd);
            } elseif ($filterType === 'month' && $filterMonth && $filterYear) {
                $query->whereMonth('orders.created_at', $filterMonth)
                      ->whereYear('orders.created_at', $filterYear);
            } elseif ($filterType === 'year' && $filterYear) {
                $query->whereYear('orders.created_at', $filterYear);
            }
        };

        $applyExpenseFilter = function ($query) use ($filterType, $filterDate, $filterStart, $filterEnd, $filterMonth, $filterYear) {
            if ($filterType === 'date' && $filterDate) {
                $query->whereDate('date', $filterDate);
            } elseif ($filterType === 'range' && $filterStart && $filterEnd) {
                $query->whereDate('date', '>=', $filterStart)
                      ->whereDate('date', '<=', $filterEnd);
            } elseif ($filterType === 'month' && $filterMonth && $filterYear) {
                $query->whereMonth('date', $filterMonth)
                      ->whereYear('date', $filterYear);
            } elseif ($filterType === 'year' && $filterYear) {
                $query->whereYear('date', $filterYear);
            }
        };

        // Statistik keuangan
        $totalRevenue = Order::whereHas('iceType')
            ->where('status', 'completed')
            ->when(true, $applyFilter)
            ->sum(DB::raw('quantity * (SELECT price FROM ice_types WHERE ice_types.id = orders.ice_type_id)'));

        $totalExpense = Expense::when(true, $applyExpenseFilter)->sum('amount');
        $netProfit = $totalRevenue - $totalExpense;

        $pendingRevenue = Order::whereHas('iceType')
            ->where('status', 'pending')
            ->when(true, $applyFilter)
            ->sum(DB::raw('quantity * (SELECT price FROM ice_types WHERE ice_types.id = orders.ice_type_id)'));

        $totalOrders = Order::where('status', 'completed')
            ->when(true, $applyFilter)
            ->count();

        $pendingOrders = Order::where('status', 'pending')
            ->when(true, $applyFilter)
            ->count();

        // Penjualan per jenis es
        $salesByIceType = Order::select(
                'ice_types.name',
                DB::raw('SUM(orders.quantity) as total_quantity'),
                DB::raw('SUM(orders.quantity * ice_types.price) as total_revenue')
            )
            ->join('ice_types', 'orders.ice_type_id', '=', 'ice_types.id')
            ->where('orders.status', 'completed')
            ->when(true, $applyFilter)
            ->groupBy('ice_types.id', 'ice_types.name')
            ->get();

        // Penjualan harian
        $dailySalesQuery = Order::select(
                DB::raw('DATE(orders.created_at) as date'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(orders.quantity * ice_types.price) as total_revenue')
            )
            ->join('ice_types', 'orders.ice_type_id', '=', 'ice_types.id')
            ->where('orders.status', 'completed');

        $dailySalesQuery->when(true, $applyFilter);

        $dailySales = $dailySalesQuery
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        // Label periode filter untuk ditampilkan di view
        $filterLabel = match ($filterType) {
            'date'  => $filterDate ? \Carbon\Carbon::parse($filterDate)->format('d M Y') : '—',
            'range' => ($filterStart && $filterEnd)
                        ? \Carbon\Carbon::parse($filterStart)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($filterEnd)->format('d M Y')
                        : '—',
            'month' => ($filterMonth && $filterYear)
                        ? \Carbon\Carbon::createFromDate($filterYear, $filterMonth, 1)->format('F Y')
                        : '—',
            'year'  => $filterYear ?? '—',
            default => 'Semua Data',
        };

        return view('finance.index', compact(
            'totalRevenue',
            'totalExpense',
            'netProfit',
            'pendingRevenue',
            'totalOrders',
            'pendingOrders',
            'salesByIceType',
            'dailySales',
            'filterType',
            'filterDate',
            'filterStart',
            'filterEnd',
            'filterMonth',
            'filterYear',
            'filterLabel'
        ));
    }

    public function reports(Request $request)
    {
        $filterType  = $request->input('filter_type', 'all');
        $filterDate  = $request->input('filter_date');
        $filterStart = $request->input('filter_start');
        $filterEnd   = $request->input('filter_end');
        $filterMonth = $request->input('filter_month');
        $filterYear  = $request->input('filter_year');

        $applyFilter = function ($query) use ($filterType, $filterDate, $filterStart, $filterEnd, $filterMonth, $filterYear) {
            if ($filterType === 'date' && $filterDate) {
                $query->whereDate('orders.created_at', $filterDate);
            } elseif ($filterType === 'range' && $filterStart && $filterEnd) {
                $query->whereDate('orders.created_at', '>=', $filterStart)
                      ->whereDate('orders.created_at', '<=', $filterEnd);
            } elseif ($filterType === 'month' && $filterMonth && $filterYear) {
                $query->whereMonth('orders.created_at', $filterMonth)
                      ->whereYear('orders.created_at', $filterYear);
            } elseif ($filterType === 'year' && $filterYear) {
                $query->whereYear('orders.created_at', $filterYear);
            }
        };

        $applyExpenseFilter = function ($query) use ($filterType, $filterDate, $filterStart, $filterEnd, $filterMonth, $filterYear) {
            if ($filterType === 'date' && $filterDate) {
                $query->whereDate('date', $filterDate);
            } elseif ($filterType === 'range' && $filterStart && $filterEnd) {
                $query->whereDate('date', '>=', $filterStart)
                      ->whereDate('date', '<=', $filterEnd);
            } elseif ($filterType === 'month' && $filterMonth && $filterYear) {
                $query->whereMonth('date', $filterMonth)
                      ->whereYear('date', $filterYear);
            } elseif ($filterType === 'year' && $filterYear) {
                $query->whereYear('date', $filterYear);
            }
        };

        $totalRevenue = Order::whereHas('iceType')
            ->where('status', 'completed')
            ->when(true, $applyFilter)
            ->sum(DB::raw('quantity * (SELECT price FROM ice_types WHERE ice_types.id = orders.ice_type_id)'));

        $totalExpense = Expense::when(true, $applyExpenseFilter)->sum('amount');
        $netProfit = $totalRevenue - $totalExpense;

        $pendingRevenue = Order::whereHas('iceType')
            ->where('status', 'pending')
            ->when(true, $applyFilter)
            ->sum(DB::raw('quantity * (SELECT price FROM ice_types WHERE ice_types.id = orders.ice_type_id)'));

        $totalOrders = Order::where('status', 'completed')
            ->when(true, $applyFilter)
            ->count();

        $pendingOrders = Order::where('status', 'pending')
            ->when(true, $applyFilter)
            ->count();

        $salesByIceType = Order::select(
                'ice_types.name',
                DB::raw('SUM(orders.quantity) as total_quantity'),
                DB::raw('SUM(orders.quantity * ice_types.price) as total_revenue')
            )
            ->join('ice_types', 'orders.ice_type_id', '=', 'ice_types.id')
            ->where('orders.status', 'completed')
            ->when(true, $applyFilter)
            ->groupBy('ice_types.id', 'ice_types.name')
            ->get();

        $dailySalesQuery = Order::select(
                DB::raw('DATE(orders.created_at) as date'),
                DB::raw('COUNT(*) as total_orders'),
                DB::raw('SUM(orders.quantity * ice_types.price) as total_revenue')
            )
            ->join('ice_types', 'orders.ice_type_id', '=', 'ice_types.id')
            ->where('orders.status', 'completed');

        $dailySalesQuery->when(true, $applyFilter);

        $dailySales = $dailySalesQuery
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        $filterLabel = match ($filterType) {
            'date'  => $filterDate ? \Carbon\Carbon::parse($filterDate)->format('d M Y') : '—',
            'range' => ($filterStart && $filterEnd)
                        ? \Carbon\Carbon::parse($filterStart)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($filterEnd)->format('d M Y')
                        : '—',
            'month' => ($filterMonth && $filterYear)
                        ? \Carbon\Carbon::createFromDate($filterYear, $filterMonth, 1)->format('F Y')
                        : '—',
            'year'  => $filterYear ?? '—',
            default => 'Semua Data',
        };

        return view('finance.reports', compact(
            'totalRevenue',
            'totalExpense',
            'netProfit',
            'pendingRevenue',
            'totalOrders',
            'pendingOrders',
            'salesByIceType',
            'dailySales',
            'filterType',
            'filterDate',
            'filterStart',
            'filterEnd',
            'filterMonth',
            'filterYear',
            'filterLabel'
        ));
    }
}
