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

$extractQtyByWeight = function (string $text, int $weight): ?int {
    $pattern = '/(?:\b' . $weight . '\s*kg\b\D{0,18}(\d+)|(\d+)\D{0,18}\b' . $weight . '\s*kg\b)/i';

    if (!preg_match($pattern, $text, $matches)) {
        return null;
    }

    $front = $matches[1] ?? null;
    $back = $matches[2] ?? null;
    $qty = (int) ($front ?: $back ?: 0);

    return $qty > 0 ? $qty : null;
};

$resolveOrderStockDemand = function (Order $order) use ($extractQtyByWeight): array {
    $required = [
        'stock_5kg' => 0,
        'stock_20kg' => 0,
    ];

    $quantity = max(1, (int) $order->quantity);
    $iceTypeName = strtolower(trim((string) ($order->iceType?->name ?? '')));
    $weight = (float) ($order->iceType?->weight ?? 0);

    if ($weight > 0) {
        if (abs($weight - 5.0) < 0.01) {
            $required['stock_5kg'] = $quantity;
            return $required;
        }

        if (abs($weight - 20.0) < 0.01) {
            $required['stock_20kg'] = $quantity;
            return $required;
        }
    }

    if ($iceTypeName !== '') {
        if (str_contains($iceTypeName, '5')) {
            $required['stock_5kg'] = $quantity;
            return $required;
        }

        if (str_contains($iceTypeName, '20')) {
            $required['stock_20kg'] = $quantity;
            return $required;
        }
    }

    $items = strtolower(trim((string) $order->items));
    if ($items === '') {
        return $required;
    }

    $qty5 = $extractQtyByWeight($items, 5);
    $qty20 = $extractQtyByWeight($items, 20);

    if (!is_null($qty5)) {
        $required['stock_5kg'] = $qty5;
    }

    if (!is_null($qty20)) {
        $required['stock_20kg'] = $qty20;
    }

    if ($required['stock_5kg'] > 0 || $required['stock_20kg'] > 0) {
        return $required;
    }

    if (preg_match('/\b5\s*kg\b/i', $items)) {
        $required['stock_5kg'] = $quantity;
    }

    if (preg_match('/\b20\s*kg\b/i', $items)) {
        $required['stock_20kg'] = $quantity;
    }

    return $required;
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

Route::get('/driver/orders/notifications', function (Request $request) use ($resolveDriverFromToken, $resolveOrderStockDemand) {
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

    $pendingOrdersInZone = Order::query()
        ->with(['customer:id,name,zone', 'iceType:id,name,weight'])
        ->where('status', 'pending')
        ->where(function ($query) use ($driver) {
            $query->whereNull('driver_id')
                ->orWhere('driver_id', (int) $driver->id);
        })
        ->whereHas('customer', function ($query) use ($zone) {
            $query->whereRaw('LOWER(zone) = ?', [$zone]);
        })
        ->latest('id')
        ->limit(150)
        ->get();

    foreach ($pendingOrdersInZone as $pendingOrder) {
        $requiredStock = $resolveOrderStockDemand($pendingOrder);
        if ((int) $requiredStock['stock_5kg'] === 0 && (int) $requiredStock['stock_20kg'] === 0) {
            continue;
        }

        DB::transaction(function () use ($pendingOrder, $driver, $resolveOrderStockDemand) {
            $freshOrder = Order::query()
                ->with('iceType:id,name,weight')
                ->lockForUpdate()
                ->find($pendingOrder->id);

            if (!$freshOrder || $freshOrder->status !== 'pending') {
                return;
            }

            if (!is_null($freshOrder->driver_id) && (int) $freshOrder->driver_id !== (int) $driver->id) {
                return;
            }

            $required = $resolveOrderStockDemand($freshOrder);
            $driverStock = DriverStock::query()
                ->where('driver_id', (int) $driver->id)
                ->whereDate('date', now()->toDateString())
                ->lockForUpdate()
                ->first();

            $current5Kg = (int) ($driverStock->stock_5kg ?? 0);
            $current20Kg = (int) ($driverStock->stock_20kg ?? 0);

            $isStockEnough =
                $current5Kg >= (int) $required['stock_5kg'] &&
                $current20Kg >= (int) $required['stock_20kg'];

            if (!$isStockEnough) {
                $freshOrder->update([
                    'driver_id' => (int) $driver->id,
                    'status' => 'rejected',
                ]);
            }
        });
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

Route::patch('/driver/orders/{order}/status', function (Request $request, Order $order) use ($resolveDriverFromToken, $resolveOrderStockDemand) {
    $driver = $resolveDriverFromToken($request);
    if (!$driver) {
        return response()->json([
            'message' => 'Unauthorized.',
        ], 401);
    }

    $validated = $request->validate([
        'status' => 'required|in:approved,rejected',
    ]);

    $statusUpdateResult = DB::transaction(function () use ($order, $validated, $driver, $resolveOrderStockDemand) {
        $freshOrder = Order::query()
            ->with('iceType:id,name,weight')
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

        if ($validated['status'] === 'approved') {
            $requiredStock = $resolveOrderStockDemand($freshOrder);

            $driverStock = DriverStock::query()
                ->where('driver_id', (int) $driver->id)
                ->whereDate('date', now()->toDateString())
                ->lockForUpdate()
                ->first();

            $current5Kg = (int) ($driverStock->stock_5kg ?? 0);
            $current20Kg = (int) ($driverStock->stock_20kg ?? 0);

            $isStockEnough =
                $current5Kg >= (int) $requiredStock['stock_5kg'] &&
                $current20Kg >= (int) $requiredStock['stock_20kg'];

            if (!$isStockEnough) {
                $freshOrder->update([
                    'driver_id' => (int) $driver->id,
                    'status' => 'rejected',
                ]);

                return [
                    'ok' => true,
                    'order' => $freshOrder->fresh(),
                    'message' => 'Stok bawaan tidak cukup. Order otomatis ditolak.',
                ];
            }

            if ($driverStock && ((int) $requiredStock['stock_5kg'] > 0 || (int) $requiredStock['stock_20kg'] > 0)) {
                $driverStock->update([
                    'stock_5kg' => max(0, $current5Kg - (int) $requiredStock['stock_5kg']),
                    'stock_20kg' => max(0, $current20Kg - (int) $requiredStock['stock_20kg']),
                ]);
            }
        }

        $freshOrder->update([
            'driver_id' => (int) $driver->id,
            'status' => $validated['status'],
        ]);

        return [
            'ok' => true,
            'order' => $freshOrder->fresh(),
            'message' => $validated['status'] === 'approved'
                ? 'Order berhasil diterima dan stok telah dikurangi.'
                : 'Order berhasil ditolak.',
        ];
    });

    if (!$statusUpdateResult['ok']) {
        return response()->json([
            'message' => $statusUpdateResult['message'],
        ], $statusUpdateResult['code']);
    }

    /** @var \App\Models\Order $updatedOrder */
    $updatedOrder = $statusUpdateResult['order'];
    $todayStock = DriverStock::query()
        ->where('driver_id', (int) $driver->id)
        ->whereDate('date', now()->toDateString())
        ->first();

    return response()->json([
        'message' => $statusUpdateResult['message'] ?? 'Order berhasil diproses.',
        'data' => [
            'id' => $updatedOrder->id,
            'status' => $updatedOrder->status,
            'driver_id' => $updatedOrder->driver_id,
            'stock_today' => [
                'date' => now()->toDateString(),
                'stock_5kg' => (int) ($todayStock->stock_5kg ?? 0),
                'stock_20kg' => (int) ($todayStock->stock_20kg ?? 0),
            ],
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

Route::get('/driver/stocks/today', function (Request $request) use ($resolveDriverFromToken) {
    $driver = $resolveDriverFromToken($request);
    if (!$driver) {
        return response()->json([
            'message' => 'Unauthorized.',
        ], 401);
    }

    $today = now()->toDateString();
    $stock = DriverStock::query()
        ->where('driver_id', (int) $driver->id)
        ->whereDate('date', $today)
        ->first();

    return response()->json([
        'data' => [
            'date' => $today,
            'stock_5kg' => (int) ($stock->stock_5kg ?? 0),
            'stock_20kg' => (int) ($stock->stock_20kg ?? 0),
            'has_stock_input' => !is_null($stock),
            'updated_at' => $stock?->updated_at,
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
