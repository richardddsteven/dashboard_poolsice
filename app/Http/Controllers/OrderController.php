<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'iceType']);

        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search nama pelanggan / no telpon
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Filter tanggal
        $filterType  = $request->input('filter_type', 'all');
        $filterDate  = $request->input('filter_date');
        $filterStart = $request->input('filter_start');
        $filterEnd   = $request->input('filter_end');

        if ($filterType === 'date' && $filterDate) {
            $query->whereDate('orders.created_at', $filterDate);
        } elseif ($filterType === 'range' && $filterStart && $filterEnd) {
            $query->whereDate('orders.created_at', '>=', $filterStart)
                  ->whereDate('orders.created_at', '<=', $filterEnd);
        }

        $orders = $query->latest()->paginate(15)->withQueryString();

        return view('orders.index', compact(
            'orders',
            'filterType',
            'filterDate',
            'filterStart',
            'filterEnd'
        ));
    }

    public function approve(Order $order)
    {
        $order->update(['status' => 'approved']);
        return redirect()->route('orders.index')
            ->with('success', 'Order berhasil di-approve.');
    }

    public function reject(Order $order)
    {
        $order->update(['status' => 'rejected']);
        return redirect()->route('orders.index')
            ->with('success', 'Order berhasil di-reject.');
    }
}
