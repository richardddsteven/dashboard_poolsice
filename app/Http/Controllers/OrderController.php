<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Driver;
use App\Services\FcmService;
use App\Services\FonnteService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private function withNoStoreHeaders($response)
    {
        return $response
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    private function sendStatusReply(Order $order, string $status, ?string $note = null): void
    {
        $phone = trim((string) ($order->customer?->phone ?? $order->phone ?? ''));
        if ($phone === '') {
            return;
        }

        $order->loadMissing('customer:id,name,phone');
        app(FonnteService::class)->sendOrderStatusUpdate($phone, $order, $status, $note);
    }

    private function notifyDriverStatusUpdate(Order $order, string $status, ?string $note = null): void
    {
        $driverId = (int) ($order->driver_id ?? 0);
        if ($driverId <= 0) {
            return;
        }

        $driver = Driver::query()->find($driverId);
        if (!$driver || empty($driver->fcm_token)) {
            return;
        }

        app(FcmService::class)->send(
            $driver->fcm_token,
            [
                'title' => 'Status Order Diperbarui',
                'body' => $note
                    ? "Order #{$order->id} sekarang {$status}. {$note}"
                    : "Order #{$order->id} sekarang {$status}.",
            ],
            [
                'type' => 'order_status_update',
                'order_id' => (string) $order->id,
                'status' => $status,
                'note' => $note ?? '',
            ]
        );
    }

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

        $orders = $query->latest('orders.id')->paginate(10)->withQueryString();
        $latestOrderId = (clone $query)->max('orders.id') ?? 0;

        return $this->withNoStoreHeaders(response()->view('orders.index', compact(
            'orders',
            'filterType',
            'filterDate',
            'filterStart',
            'filterEnd',
            'latestOrderId'
        )));
    }

    public function tableData(Request $request)
    {
        $orders = $this->buildFilteredOrdersQuery($request)
            ->latest('orders.id')
            ->paginate(10)
            ->withQueryString();

        return $this->withNoStoreHeaders(response()->json([
            'html' => view('orders.partials.table', compact('orders'))->render(),
            'total' => $orders->total(),
            'latestOrderId' => $orders->max('id') ?? 0,
        ]));
    }

    public function realtimeStatus(Request $request)
    {
        $latestOrder = Order::with(['customer', 'iceType'])->latest('id')->first();
        $latestOrderId = $latestOrder?->id ?? 0;
        $latestUpdatedOrder = Order::query()->latest('updated_at')->latest('id')->first();
        $latestUpdateToken = $latestUpdatedOrder
            ? ($latestUpdatedOrder->id . '-' . $latestUpdatedOrder->updated_at->format('YmdHisu'))
            : '';
        $lastSeenId = $request->integer('last_id', 0);

        $newOrder = null;
        if ($latestOrder && $latestOrderId > $lastSeenId) {
            $productName = $latestOrder->iceType?->name ?? 'Es Batu';
            $newOrder = [
                'id' => $latestOrder->id,
                'customer' => $latestOrder->customer?->name ?? 'Pelanggan baru',
                'phone' => $latestOrder->phone,
                'quantity' => $latestOrder->effective_quantity ?? 1,
                'iceType' => $productName,
                'product' => $productName,
                'time' => $latestOrder->created_at->diffForHumans(),
            ];
        }

        return $this->withNoStoreHeaders(response()->json([
            'latestOrderId' => $latestOrderId,
            'latestUpdateToken' => $latestUpdateToken,
            'pendingCount' => Order::where('status', 'pending')->count(),
            'newOrder' => $newOrder,
        ]));
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
        ]);

        $order->update(['status' => $validated['status']]);
        $order->loadMissing('customer:id,name,phone');

        $this->sendStatusReply(
            $order,
            $validated['status'],
            $validated['status'] === 'approved'
                ? 'Pesanan Anda diterima dan sedang diproses.'
                : 'Pesanan Anda ditolak oleh sistem.'
        );

        $this->notifyDriverStatusUpdate(
            $order,
            $validated['status'],
            $validated['status'] === 'approved'
                ? 'Pesanan telah disetujui oleh sistem.'
                : 'Pesanan telah ditolak oleh sistem.'
        );

        $message = $validated['status'] === 'approved'
            ? 'Order berhasil di-approve.'
            : 'Order berhasil di-reject.';

        return redirect()->route('orders.index')
            ->with('success', $message);
    }
}
