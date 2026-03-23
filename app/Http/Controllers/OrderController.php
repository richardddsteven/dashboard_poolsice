<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private function buildFilteredOrdersQuery(Request $request)
    {
        $query = Order::with(['customer', 'iceType']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

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

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->buildFilteredOrdersQuery($request);

        $filterType  = $request->input('filter_type', 'all');
        $filterDate  = $request->input('filter_date');
        $filterStart = $request->input('filter_start');
        $filterEnd   = $request->input('filter_end');

        $orders = $query->latest()->paginate(15)->withQueryString();
        $latestOrderId = (clone $query)->max('orders.id') ?? 0;

        return view('orders.index', compact(
            'orders',
            'filterType',
            'filterDate',
            'filterStart',
            'filterEnd',
            'latestOrderId'
        ));
    }

    public function tableData(Request $request)
    {
        $orders = $this->buildFilteredOrdersQuery($request)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return response()->json([
            'html' => view('orders.partials.table', compact('orders'))->render(),
            'total' => $orders->total(),
            'latestOrderId' => $orders->max('id') ?? 0,
        ]);
    }

    public function realtimeStatus(Request $request)
    {
        $latestOrder = Order::with(['customer', 'iceType'])->latest('id')->first();
        $latestOrderId = $latestOrder?->id ?? 0;
        $lastSeenId = $request->integer('last_id', 0);

        $newOrder = null;
        if ($latestOrder && $latestOrderId > $lastSeenId) {
            $productName = $latestOrder->iceType?->name ?? 'Es Batu';
            $newOrder = [
                'id' => $latestOrder->id,
                'customer' => $latestOrder->customer?->name ?? 'Pelanggan baru',
                'phone' => $latestOrder->phone,
                'quantity' => $latestOrder->quantity ?? 1,
                'iceType' => $productName,
                'product' => $productName,
                'time' => $latestOrder->created_at->diffForHumans(),
            ];
        }

        return response()->json([
            'latestOrderId' => $latestOrderId,
            'pendingCount' => Order::where('status', 'pending')->count(),
            'newOrder' => $newOrder,
        ]);
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $order->update(['status' => $validated['status']]);

        $message = $validated['status'] === 'approved'
            ? 'Order berhasil di-approve.'
            : 'Order berhasil di-reject.';

        return redirect()->route('orders.index')
            ->with('success', $message);
    }
}
