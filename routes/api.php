<?php

use App\Models\Driver;
use App\Models\DriverStock;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;

$resolveDriverFromToken = function (Request $request): ?Driver {
    $authorization = trim((string) $request->header('Authorization'));

    if (!str_starts_with($authorization, 'Bearer ')) {
        return null;
    }

    $plainToken = trim(substr($authorization, 7));

    if ($plainToken === '') {
        return null;
    }

    $hashedToken = hash('sha256', $plainToken);

    return Driver::with('zone:id,name')
        ->where('api_token', $hashedToken)
        ->first();
};

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

    $plainToken = bin2hex(random_bytes(32));
    $driver->update([
        'api_token' => hash('sha256', $plainToken),
    ]);

    return response()->json([
        'message' => 'Login berhasil.',
        'data' => [
            'driver_id' => $driver->id,
            'driver_name' => $driver->name,
            'zone_id' => $driver->zone_id,
            'zone' => $driver->zone?->name,
            'token' => $plainToken,
        ],
    ]);
});

Route::post('/driver/logout', function (Request $request) use ($resolveDriverFromToken) {
    $driver = $resolveDriverFromToken($request);
    if (!$driver) {
        return response()->json([
            'message' => 'Unauthorized.',
        ], 401);
    }

    $driver->update([
        'api_token' => null,
    ]);

    return response()->json([
        'message' => 'Logout berhasil.',
    ]);
});

Route::get('/driver/orders/notifications', function (Request $request) use ($resolveDriverFromToken) {
    $driver = $resolveDriverFromToken($request);
    if (!$driver) {
        return response()->json([
            'message' => 'Unauthorized.',
            'data' => [],
        ], 401);
    }

    $zone = strtolower(trim((string) ($driver->zone?->name ?? '')));
    if ($zone === '') {
        return response()->json([
            'message' => 'Zona supir tidak ditemukan.',
            'data' => [],
        ], 422);
    }

    $orders = Order::query()
        ->with('customer:id,name,zone')
        ->whereHas('customer', function ($query) use ($zone) {
            $query->whereRaw('LOWER(zone) = ?', [$zone]);
        })
        ->latest('id')
        ->limit(150)
        ->get()
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
        })
        ->values();

    return response()->json(['data' => $orders]);
});

Route::patch('/driver/orders/{order}/status', function (Request $request, Order $order) use ($resolveDriverFromToken) {
    $driver = $resolveDriverFromToken($request);
    if (!$driver) {
        return response()->json([
            'message' => 'Unauthorized.',
        ], 401);
    }

    $validated = $request->validate([
        'status' => 'required|in:approved,rejected',
    ]);

    $statusUpdateResult = DB::transaction(function () use ($order, $validated, $driver) {
        $freshOrder = Order::query()
            ->lockForUpdate()
            ->findOrFail($order->id);

        if ($freshOrder->status !== 'pending') {
            return [
                'ok' => false,
                'code' => 422,
                'message' => 'Order sudah diproses sebelumnya.',
            ];
        }

        if (!is_null($freshOrder->driver_id) && (int) $freshOrder->driver_id !== (int) $driver->id) {
            return [
                'ok' => false,
                'code' => 403,
                'message' => 'Order ini sedang diproses supir lain.',
            ];
        }

        $freshOrder->update([
            'driver_id' => (int) $driver->id,
            'status' => $validated['status'],
        ]);

        return [
            'ok' => true,
            'order' => $freshOrder,
        ];
    });

    if (!$statusUpdateResult['ok']) {
        return response()->json([
            'message' => $statusUpdateResult['message'],
        ], $statusUpdateResult['code']);
    }

    /** @var \App\Models\Order $updatedOrder */
    $updatedOrder = $statusUpdateResult['order'];

    return response()->json([
        'message' => $validated['status'] === 'approved'
            ? 'Order berhasil diterima.'
            : 'Order berhasil ditolak.',
        'data' => [
            'id' => $updatedOrder->id,
            'status' => $updatedOrder->status,
            'driver_id' => $updatedOrder->driver_id,
        ],
    ]);
});

Route::post('/driver/stocks', function (Request $request) use ($resolveDriverFromToken) {
    $driver = $resolveDriverFromToken($request);
    if (!$driver) {
        return response()->json([
            'message' => 'Unauthorized.',
        ], 401);
    }

    $validated = $request->validate([
        'date' => ['required', 'date'],
        'stock_5kg' => ['required', 'integer', 'min:0'],
        'stock_20kg' => ['required', 'integer', 'min:0'],
    ]);

    $today = now()->toDateString();
    if ($validated['date'] !== $today) {
        return response()->json([
            'message' => 'Input stok hanya diperbolehkan untuk tanggal hari ini.',
        ], 422);
    }

    $stock = DriverStock::updateOrCreate(
        [
            'driver_id' => (int) $driver->id,
            'date' => $validated['date'],
        ],
        [
            'stock_5kg' => (int) $validated['stock_5kg'],
            'stock_20kg' => (int) $validated['stock_20kg'],
        ]
    );

    return response()->json([
        'message' => $stock->wasRecentlyCreated
            ? 'Stok bawaan berhasil disimpan.'
            : 'Stok bawaan untuk tanggal ini berhasil diperbarui.',
        'data' => [
            'id' => $stock->id,
            'driver_id' => $stock->driver_id,
            'date' => $stock->date?->format('Y-m-d'),
            'stock_5kg' => $stock->stock_5kg,
            'stock_20kg' => $stock->stock_20kg,
        ],
    ]);
});

Route::get('/driver/stocks/history', function (Request $request) use ($resolveDriverFromToken) {
    $driver = $resolveDriverFromToken($request);
    if (!$driver) {
        return response()->json([
            'message' => 'Unauthorized.',
            'data' => [],
        ], 401);
    }

    $query = DriverStock::query()
        ->where('driver_id', $driver->id)
        ->orderByDesc('date')
        ->orderByDesc('updated_at');

    if ($request->filled('month')) {
        $parts = explode('-', (string) $request->query('month'));
        if (count($parts) === 2) {
            $query->whereYear('date', (int) $parts[0])
                ->whereMonth('date', (int) $parts[1]);
        }
    }

    $data = $query->limit(90)->get()->map(function (DriverStock $stock) {
        return [
            'id' => $stock->id,
            'date' => $stock->date?->format('Y-m-d'),
            'stock_5kg' => $stock->stock_5kg,
            'stock_20kg' => $stock->stock_20kg,
            'updated_at' => $stock->updated_at,
        ];
    })->values();

    return response()->json([
        'data' => $data,
    ]);
});
