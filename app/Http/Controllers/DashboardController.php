<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\IceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Get statistics
        $totalCustomers = Customer::count();
        $totalOrders = Order::count();
        $approvedOrders = Order::where('status', 'approved')->count();
        $pendingOrders = Order::where('status', 'pending')->count();
        
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
        $iceTypeStats = Order::select('ice_type_id', DB::raw('count(*) as total'))
            ->with('iceType')
            ->where('status', 'approved')
            ->whereNotNull('ice_type_id')
            ->groupBy('ice_type_id')
            ->orderBy('total', 'desc')
            ->get()
            ->map(function($stat) {
                return [
                    'name' => $stat->iceType ? $stat->iceType->name : 'Es Batu',
                    'total' => $stat->total,
                    'color' => $this->getRandomColor()
                ];
            });
        
        // Add orders without ice type (fallback, only approved orders)
        $ordersWithoutType = Order::whereNull('ice_type_id')->where('status', 'approved')->count();
        if ($ordersWithoutType > 0) {
            $iceTypeStats->push([
                'name' => 'Es Batu (Default)',
                'total' => $ordersWithoutType,
                'color' => '#95a5a6'
            ]);
        }
        
        return view('dashboard', compact(
            'totalCustomers',
            'totalOrders',
            'approvedOrders',
            'pendingOrders',
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
            ->where('status', 'approved')
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
