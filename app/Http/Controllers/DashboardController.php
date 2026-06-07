<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\Order;
use App\Models\IceType;
use App\Models\Stock;
use App\Services\RouteUpdateNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $requestMonth = $request->input('month');
        $selectedMonth = $requestMonth;
        $selectedYear = $request->input('year', date('Y'));

        if ($selectedMonth === 'this_month') {
            $selectedMonth = date('n');
            $selectedYear = date('Y');
        }

        $isFiltered = !empty($selectedMonth);

        $monthName = '';
        if ($isFiltered) {
            $monthName = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->locale('id')->translatedFormat('F Y');
        }

        // Base Queries
        $customerQuery = Customer::query();
        $orderQuery = Order::query();

        if ($isFiltered) {
            $customerQuery->whereMonth('created_at', $selectedMonth)->whereYear('created_at', $selectedYear);
            $orderQuery->whereMonth('created_at', $selectedMonth)->whereYear('created_at', $selectedYear);
        }
        // Get statistics
        $totalCustomers = $customerQuery->count();
        $totalOrders = (clone $orderQuery)->count();
        $completedOrders = (clone $orderQuery)->where('status', 'completed')->count();
        $pendingOrders = (clone $orderQuery)->where('status', 'pending')->count();
        
        // Today's stats / Selected month stats
        if ($isFiltered) {
            $todayOrders = (clone $orderQuery)->count();
            $todayRevenue = (clone $orderQuery)
                ->whereHas('iceType')
                ->where('status', 'completed')
                ->sum(DB::raw('quantity * (SELECT price FROM ice_types WHERE ice_types.id = orders.ice_type_id)'));
        } else {
            $todayOrders = Order::whereDate('created_at', today())->count();
            $todayRevenue = Order::whereHas('iceType')
                ->where('status', 'completed')
                ->whereDate('created_at', today())
                ->sum(DB::raw('quantity * (SELECT price FROM ice_types WHERE ice_types.id = orders.ice_type_id)'));
        }
        
        // Total drivers
        $totalDrivers = Driver::count();
        
        // Latest stock (use timestamps since `date` column was removed)
        $todayStock = Stock::orderByDesc('created_at')->first();
        
        // Charts Data
        if ($isFiltered) {
            $startDate = Carbon::createFromDate($selectedYear, $selectedMonth, 1)->startOfMonth();
            $daysInMonth = $startDate->daysInMonth;

            $revenueDataRaw = Order::select(
                    DB::raw('DATE(orders.created_at) as date'),
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('SUM(orders.quantity * ice_types.price) as total_revenue')
                )
                ->join('ice_types', 'orders.ice_type_id', '=', 'ice_types.id')
                ->where('orders.status', 'completed')
                ->whereMonth('orders.created_at', $selectedMonth)
                ->whereYear('orders.created_at', $selectedYear)
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $revenueChartData = collect();
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $date = Carbon::createFromDate($selectedYear, $selectedMonth, $i)->format('Y-m-d');
                $found = $revenueDataRaw->firstWhere('date', $date);
                $revenueChartData->push([
                    'date' => Carbon::parse($date)->locale('id')->translatedFormat('d M'),
                    'revenue' => $found ? (float)$found->total_revenue : 0,
                    'orders' => $found ? (int)$found->total_orders : 0,
                ]);
            }

            $expenseDataRaw = Expense::select(
                    DB::raw('DATE(date) as date'),
                    DB::raw('SUM(amount) as total_expense')
                )
                ->whereMonth('date', $selectedMonth)
                ->whereYear('date', $selectedYear)
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $financeChartData = collect();
            for ($i = 1; $i <= $daysInMonth; $i++) {
                $date = Carbon::createFromDate($selectedYear, $selectedMonth, $i)->format('Y-m-d');
                $revenueFound = $revenueDataRaw->firstWhere('date', $date);
                $expenseFound = $expenseDataRaw->firstWhere('date', $date);

                $financeChartData->push([
                    'date' => Carbon::parse($date)->locale('id')->translatedFormat('d M'),
                    'revenue' => $revenueFound ? (float) $revenueFound->total_revenue : 0,
                    'expense' => $expenseFound ? (float) $expenseFound->total_expense : 0,
                    'net' => ($revenueFound ? (float) $revenueFound->total_revenue : 0) - ($expenseFound ? (float) $expenseFound->total_expense : 0),
                ]);
            }
        } else {
            // Original 7 days logic
            $revenueLast7Days = Order::select(
                    DB::raw('DATE(orders.created_at) as date'),
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw('SUM(orders.quantity * ice_types.price) as total_revenue')
                )
                ->join('ice_types', 'orders.ice_type_id', '=', 'ice_types.id')
                ->where('orders.status', 'completed')
                ->where('orders.created_at', '>=', now()->subDays(6)->startOfDay())
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $revenueChartData = collect();
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $found = $revenueLast7Days->firstWhere('date', $date);
                $revenueChartData->push([
                    'date' => Carbon::parse($date)->locale('id')->translatedFormat('d M'),
                    'revenue' => $found ? (float)$found->total_revenue : 0,
                    'orders' => $found ? (int)$found->total_orders : 0,
                ]);
            }

            $expenseLast7Days = Expense::select(
                    DB::raw('DATE(date) as date'),
                    DB::raw('SUM(amount) as total_expense')
                )
                ->where('date', '>=', now()->subDays(6)->startOfDay())
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $financeChartData = collect();
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $revenueFound = $revenueLast7Days->firstWhere('date', $date);
                $expenseFound = $expenseLast7Days->firstWhere('date', $date);

                $financeChartData->push([
                    'date' => Carbon::parse($date)->locale('id')->translatedFormat('d M'),
                    'revenue' => $revenueFound ? (float) $revenueFound->total_revenue : 0,
                    'expense' => $expenseFound ? (float) $expenseFound->total_expense : 0,
                    'net' => ($revenueFound ? (float) $revenueFound->total_revenue : 0) - ($expenseFound ? (float) $expenseFound->total_expense : 0),
                ]);
            }
        }

        // Customer growth (compared to previous month)
        $currentMonthDate = $isFiltered ? Carbon::createFromDate($selectedYear, $selectedMonth, 1) : now();
        $thisMonthCustomers = Customer::whereMonth('created_at', $currentMonthDate->month)
            ->whereYear('created_at', $currentMonthDate->year)
            ->count();
        
        $lastMonthDate = $currentMonthDate->copy()->subMonth();
        $lastMonthCustomers = Customer::whereMonth('created_at', $lastMonthDate->month)
            ->whereYear('created_at', $lastMonthDate->year)
            ->count();
        $growthCustomers = $lastMonthCustomers > 0 
            ? round((($thisMonthCustomers - $lastMonthCustomers) / $lastMonthCustomers) * 100, 1)
            : ($thisMonthCustomers > 0 ? 100 : 0);

        // This month expenses
        $monthlyExpenses = Expense::whereMonth('date', $currentMonthDate->month)
            ->whereYear('date', $currentMonthDate->year)
            ->sum('amount');

        // Recent activities (latest 8 orders for timeline)
        $recentActivities = (clone $orderQuery)->with(['customer', 'iceType'])
            ->latest()
            ->take(8)
            ->get();

        // Notifikasi update jalur terbaru untuk dashboard admin.
        // Hanya tampilkan untuk order pending dalam 24 jam terakhir
        // dari customer yang belum memiliki route_stop, bukan semua order terbaru.
        $latestRouteUpdateOrder = Order::with(['customer.routeStop.zone'])
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subHours(24))
            ->whereHas('customer', fn ($q) => $q->whereNull('route_stop_id'))
            ->latest('id')
            ->first();
        $routeUpdateNotice = app(RouteUpdateNotificationService::class)->buildFromOrder($latestRouteUpdateOrder);

        // Gunakan data recentActivities yang sudah di-fetch, tidak perlu query ulang.
        $recentOrders = $recentActivities->take(5);
        
        // Get top customers
        $topCustomers = Customer::withCount(['orders' => function($q) use ($isFiltered, $selectedMonth, $selectedYear) {
                if ($isFiltered) {
                    $q->whereMonth('created_at', $selectedMonth)->whereYear('created_at', $selectedYear);
                }
            }])
            ->orderBy('orders_count', 'desc')
            ->take(5)
            ->get();
        
        // Get pending orders count for sidebar badge
        // Gunakan nilai $pendingOrders yang sudah dihitung sebelumnya.
        $pendingOrdersCount = $pendingOrders;
        $latestOrderId = Order::max('id') ?? 0;
        
        // Get ice type statistics for pie chart (only approved orders)
        $iceColors = ['#29449B', '#2956E3', '#4F83F4', '#76A7FF', '#D7E4FF', '#9CC1FF', '#5B7DEB', '#1E40AF'];

        $iceTypeStats = (clone $orderQuery)->select('ice_type_id', DB::raw('count(*) as total'))
            ->with('iceType')
            ->where('status', 'completed')
            ->whereNotNull('ice_type_id')
            ->groupBy('ice_type_id')
            ->orderBy('total', 'desc')
            ->get()
            ->values()
            ->map(function($stat, $index) use ($iceColors) {
                return [
                    'name' => $stat->iceType ? $stat->iceType->name : 'Es Batu',
                    'total' => $stat->total,
                    'color' => $iceColors[$index % count($iceColors)]
                ];
            });
        
        // Add orders without ice type (fallback, only approved orders)
        $ordersWithoutType = (clone $orderQuery)->whereNull('ice_type_id')->where('status', 'completed')->count();
        if ($ordersWithoutType > 0) {
            $iceTypeStats->push([
                'name' => 'Es Batu (Default)',
                'total' => $ordersWithoutType,
                'color' => $iceColors[$iceTypeStats->count() % count($iceColors)]
            ]);
        }
        
        return view('dashboard', compact(
            'totalCustomers',
            'totalOrders',
            'completedOrders',
            'pendingOrders',
            'todayOrders',
            'todayRevenue',
            'totalDrivers',
            'todayStock',
            'revenueChartData',
            'financeChartData',
            'growthCustomers',
            'monthlyExpenses',
            'recentActivities',
            'recentOrders',
            'topCustomers',
            'pendingOrdersCount',
            'iceTypeStats',
            'latestOrderId',
            'routeUpdateNotice',
            'isFiltered',
            'monthName',
            'selectedMonth',
            'requestMonth',
            'selectedYear'
        ));
    }
    
    private function getRandomColor()
    {
        $colors = [
            '#3498db', '#e74c3c', '#f39c12', '#27ae60', '#9b59b6', 
            '#e67e22', '#1abc9c', '#34495e', '#f1c40f', '#e91e63'
        ];
        return $colors[array_rand($colors)];
    }

    public function iceTypeStats(Request $request)
    {
        $period = $request->input('period', 'all'); // all | 7d | 30d | custom
        $start  = $request->input('start');
        $end    = $request->input('end');

        $query = Order::select('ice_type_id', DB::raw('count(*) as total'))
            ->with('iceType')
            ->where('status', 'completed')
            ->whereNotNull('ice_type_id');

        if ($period === '7d') {
            $query->where('created_at', '>=', now()->subDays(7)->startOfDay());
        } elseif ($period === '30d') {
            $query->where('created_at', '>=', now()->subDays(30)->startOfDay());
        } elseif ($period === 'custom' && $start && $end) {
            $query->whereDate('created_at', '>=', $start)
                  ->whereDate('created_at', '<=', $end);
        }

        $stats = $query->groupBy('ice_type_id')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function ($stat) {
                return [
                    'name'  => $stat->iceType ? $stat->iceType->name : 'Es Batu',
                    'total' => $stat->total,
                ];
            });

        return response()->json($stats->values());
    }
}
