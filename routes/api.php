<?php

use App\Models\Driver;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;

Route::post('/driver/login', function (Request $request) {
    $validated = $request->validate([
        'username' => 'required|string',
        'password' => 'required|string',
    ]);

    $driver = Driver::with('zone:id,name')
        ->where('username', $validated['username'])
        ->first();

    if (!$driver || !Hash::check($validated['password'], $driver->password ?? '')) {
        return response()->json([
            'message' => 'Username atau password salah.',
        ], 401);
    }

    return response()->json([
        'message' => 'Login berhasil.',
        'data' => [
            'driver_id' => $driver->id,
            'driver_name' => $driver->name,
            'zone_id' => $driver->zone_id,
            'zone' => $driver->zone?->name,
        ],
    ]);
});

Route::get('/driver/orders/notifications', function (Request $request) {
    $driverId = (int) $request->query('driver_id');
    $zone = strtolower(trim((string) $request->query('zone')));
    $lastId = (int) $request->query('last_id', 0);

    if ($driverId <= 0 || $zone === '') {
        return response()->json([
            'message' => 'driver_id dan zone wajib diisi.',
            'data' => [],
        ], 422);
    }

    $orders = DB::transaction(function () use ($driverId, $lastId, $zone) {
        $orders = Order::query()
            ->with('customer:id,name,zone')
            ->where('id', '>', $lastId)
            ->where('status', 'pending')
            ->where(function ($query) use ($driverId) {
                $query->whereNull('driver_id')
                    ->orWhere('driver_id', $driverId);
            })
            ->whereHas('customer', function ($query) use ($zone) {
                $query->whereRaw('LOWER(zone) = ?', [$zone]);
            })
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($orders as $order) {
            if (is_null($order->driver_id)) {
                $order->driver_id = $driverId;
                $order->save();
            }
        }

        return $orders;
    })
        ->map(function (Order $order) {
            return [
                'id' => $order->id,
                'driver_id' => $order->driver_id,
                'customer_name' => $order->customer?->name,
                'zone' => $order->customer?->zone,
                'items' => $order->items,
                'status' => $order->status,
                'created_at' => $order->created_at,
            ];
        });

    return response()->json(['data' => $orders]);
});

Route::patch('/driver/orders/{order}/status', function (Request $request, Order $order) {
    $validated = $request->validate([
        'driver_id' => 'required|integer|exists:drivers,id',
        'status' => 'required|in:approved,rejected',
    ]);

    if ((int) $order->driver_id !== (int) $validated['driver_id']) {
        return response()->json([
            'message' => 'Order ini bukan milik supir yang login.',
        ], 403);
    }

    if ($order->status !== 'pending') {
        return response()->json([
            'message' => 'Order sudah diproses sebelumnya.',
        ], 422);
    }

    $order->update([
        'status' => $validated['status'],
    ]);

    return response()->json([
        'message' => $validated['status'] === 'approved'
            ? 'Order berhasil diterima.'
            : 'Order berhasil ditolak.',
        'data' => [
            'id' => $order->id,
            'status' => $order->status,
        ],
    ]);
});
