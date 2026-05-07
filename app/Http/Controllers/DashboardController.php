<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\Expense;
use App\Models\Order;
use App\Models\IceType;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Get statistics
        $totalCustomers = Customer::count();
        $totalOrders = Order::count();
        $completedOrders = Order::where('status', 'completed')->count();
        $pendingOrders = Order::where('status', 'pending')->count();
        
        // Today's stats
        $todayOrders = Order::whereDate('created_at', today())->count();
        $todayRevenue = Order::whereHas('iceType')
            ->where('status', 'completed')
            ->whereDate('created_at', today())
            ->sum(DB::raw('quantity * (SELECT price FROM ice_types WHERE ice_types.id = orders.ice_type_id)'));
        
        // Total drivers
        $totalDrivers = Driver::count();
        
        // Latest stock (use timestamps since `date` column was removed)
        $todayStock = Stock::orderByDesc('created_at')->first();
        
        // Revenue last 7 days for line chart
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

        // Fill missing days with 0
        $revenueChartData = collect();
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $found = $revenueLast7Days->firstWhere('date', $date);
            $revenueChartData->push([
                'date' => Carbon::parse($date)->format('d M'),
                'revenue' => $found ? (float)$found->total_revenue : 0,
                'orders' => $found ? (int)$found->total_orders : 0,
            ]);
        }

        // Expense last 7 days for finance chart
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

            $revenue = $revenueFound ? (float) $revenueFound->total_revenue : 0;
            $expense = $expenseFound ? (float) $expenseFound->total_expense : 0;

            $financeChartData->push([
                'date' => Carbon::parse($date)->format('d M'),
                'revenue' => $revenue,
                'expense' => $expense,
                'net' => $revenue - $expense,
            ]);
        }

        // Customer growth (this month vs last month)
        $thisMonthCustomers = Customer::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $lastMonthCustomers = Customer::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->count();
        $growthCustomers = $lastMonthCustomers > 0 
            ? round((($thisMonthCustomers - $lastMonthCustomers) / $lastMonthCustomers) * 100, 1)
            : ($thisMonthCustomers > 0 ? 100 : 0);

        // This month expenses
        $monthlyExpenses = Expense::whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('amount');

        // Recent activities (latest 8 orders for timeline)
        $recentActivities = Order::with(['customer', 'iceType'])
            ->latest()
            ->take(8)
            ->get();

        // Get recent orders
        $recentOrders = Order::with(['customer', 'iceType'])
            ->latest()
            ->take(5)
            ->get();
        
        // Get top customers
        $topCustomers = Customer::withCount('orders')
            ->orderBy('orders_count', 'desc')
            ->take(5)
            ->get();
        
        // Get pending orders count for sidebar badge
        $pendingOrdersCount = Order::where('status', 'pending')->count();
        $latestOrderId = Order::max('id') ?? 0;
        
        // Get ice type statistics for pie chart (only approved orders)
        $iceColors = ['#29449B', '#2956E3', '#4F83F4', '#76A7FF', '#D7E4FF', '#9CC1FF', '#5B7DEB', '#1E40AF'];

        $iceTypeStats = Order::select('ice_type_id', DB::raw('count(*) as total'))
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
        $ordersWithoutType = Order::whereNull('ice_type_id')->where('status', 'completed')->count();
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
            'latestOrderId'
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
