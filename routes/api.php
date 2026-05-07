<?php

use App\Models\Driver;
use App\Models\DriverStock;
use App\Models\IceType;
use App\Models\IceTypeDriverStock;
use App\Models\Order;
use App\Models\Zone;
use App\Services\FcmService;
use App\Services\FonnteService;
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

$sendOrderStatusReply = function (Order $order, string $status, ?string $note = null): bool {
    $phone = trim((string) ($order->customer?->phone ?? $order->phone ?? ''));
    if ($phone === '') {
        return false;
    }

    $order->loadMissing('customer:id,name,phone');

    return app(FonnteService::class)->sendOrderStatusUpdate($phone, $order, $status, $note);
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
        'ice_type_id' => null,
        'quantity' => 0,
    ];

    $quantity = max(1, (int) ($order->effective_quantity ?? $order->quantity));
    $iceTypeName = strtolower(trim((string) ($order->iceType?->name ?? '')));
    $weight = (float) ($order->iceType?->weight ?? 0);
    $required['quantity'] = $quantity;

    if ($weight > 0) {
        if (abs($weight - 5.0) < 0.01) {
            $required['stock_5kg'] = $quantity;
            return $required;
        }

        if (abs($weight - 20.0) < 0.01) {
            $required['stock_20kg'] = $quantity;
            return $required;
        }

        // Ice type baru seperti 10kg / 15kg disimpan sebagai demand berbasis ice_type_id
        $required['ice_type_id'] = (int) ($order->ice_type_id ?? 0);
        return $required;
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

        $required['ice_type_id'] = (int) ($order->ice_type_id ?? 0);
        return $required;
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

    $required['ice_type_id'] = (int) ($order->ice_type_id ?? 0);
    if ($required['ice_type_id'] > 0) {
        return $required;
    }

    if (preg_match('/\b5\s*kg\b/i', $items)) {
        $required['stock_5kg'] = $quantity;
    }

    if (preg_match('/\b20\s*kg\b/i', $items)) {
        $required['stock_20kg'] = $quantity;
    }

    if ($required['stock_5kg'] === 0 && $required['stock_20kg'] === 0) {
        $required['ice_type_id'] = (int) ($order->ice_type_id ?? 0);
    }

    return $required;
};

$resolveDriverTodayStock = function (int $driverId, ?string $date = null) {
    // Coba ambil dari format baru (IceTypeDriverStock)
    $newFormatStocks = IceTypeDriverStock::query()
        ->forDate($date)
        ->where('driver_id', $driverId)
        ->get()
        ->keyBy('ice_type_id')
        ->map(fn($stock) => [
            'ice_type_id' => $stock->ice_type_id,
            'quantity' => $stock->quantity,
        ]);

    if ($newFormatStocks->isNotEmpty()) {
        return $newFormatStocks->values()->all();
    }

    // Fallback ke format lama (DriverStock) untuk backward compatibility
    $oldFormatStock = DriverStock::query()
        ->where('driver_id', $driverId)
        ->first();

    if (!$oldFormatStock) {
        return [];
    }

    // Convert old format ke array untuk consistency
    $result = [];
    if ($oldFormatStock->stock_5kg > 0) {
        $result[] = [
            'quantity' => $oldFormatStock->stock_5kg,
            'weight' => 5,
        ];
    }
    if ($oldFormatStock->stock_20kg > 0) {
        $result[] = [
            'quantity' => $oldFormatStock->stock_20kg,
            'weight' => 20,
        ];
    }
    return $result;
};

$checkDriverHasEnoughStock = function (int $driverId, Order $order, $resolveDriverTodayStock) {
    $driverStocks = $resolveDriverTodayStock($driverId, null);

    if (empty($driverStocks)) {
        return false;
    }

    $required = (int) ($order->effective_quantity ?? $order->quantity ?? 1);
    $iceTypeWeight = (float) ($order->iceType?->weight ?? 0);

    if ((int) ($order->ice_type_id ?? 0) > 0) {
        foreach ($driverStocks as $stock) {
            if (isset($stock['ice_type_id']) && (int) $stock['ice_type_id'] === (int) $order->ice_type_id) {
                return (int) $stock['quantity'] >= $required;
            }
        }

        return false;
    }

    // Jika ada format baru (dengan ice_type_id)
    if (isset($driverStocks[0]['ice_type_id'])) {
        foreach ($driverStocks as $stock) {
            if ((int) $stock['ice_type_id'] === (int) $order->ice_type_id) {
                return (int) $stock['quantity'] >= $required;
            }
        }
        return false;
    }

    // Format lama - match by weight
    foreach ($driverStocks as $stock) {
        $stockWeight = (float) ($stock['weight'] ?? 0);
        if (abs($stockWeight - $iceTypeWeight) < 0.01) {
            return (int) $stock['quantity'] >= $required;
        }
    }
    return false;
};

$haversineDistanceMeters = function (float $lat1, float $lon1, float $lat2, float $lon2): float {
    $earthRadiusMeters = 6371000.0;

    $latFrom = deg2rad($lat1);
    $latTo = deg2rad($lat2);
    $deltaLat = deg2rad($lat2 - $lat1);
    $deltaLon = deg2rad($lon2 - $lon1);

    $a =
        sin($deltaLat / 2) * sin($deltaLat / 2) +
        cos($latFrom) * cos($latTo) * sin($deltaLon / 2) * sin($deltaLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadiusMeters * $c;
};



// ============================================
// PUBLIC ENDPOINTS
// ============================================

/**
 * GET /api/ice-types
 * Fetch active ice types untuk driver app
 */
Route::get('/ice-types', function () {
    $iceTypes = IceType::getActiveTypesForApi();
    
    return response()->json([
        'data' => $iceTypes,
    ]);
});

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

/**
 * POST /api/driver/fcm-token
 * Simpan/perbarui FCM token device supir untuk push notification.
 * Dipanggil oleh Flutter setelah login dan saat token diperbarui.
 */
Route::post('/driver/fcm-token', function (Request $request) use ($resolveDriverFromToken) {
    $driver = $resolveDriverFromToken($request);
    if (!$driver) {
        return response()->json(['message' => 'Unauthorized.'], 401);
    }

    $validated = $request->validate([
        'fcm_token' => 'required|string|min:10',
    ]);

    $driver->update(['fcm_token' => $validated['fcm_token']]);

    return response()->json(['message' => 'FCM token berhasil disimpan.']);
});

Route::get('/driver/orders/notifications', function (Request $request) use ($resolveDriverFromToken, $resolveOrderStockDemand, $checkDriverHasEnoughStock, $resolveDriverTodayStock, $sendOrderStatusReply) {
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
        ->with(['customer:id,name,address,zone', 'iceType:id,name,weight'])
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
        if (
            (int) $requiredStock['stock_5kg'] === 0 &&
            (int) $requiredStock['stock_20kg'] === 0 &&
            (int) ($requiredStock['ice_type_id'] ?? 0) === 0
        ) {
            continue;
        }

        DB::transaction(function () use ($pendingOrder, $driver, $resolveOrderStockDemand, $checkDriverHasEnoughStock, $resolveDriverTodayStock) {
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

            // Check stock dari kedua format (baru dan lama)
            $hasEnoughStock = $checkDriverHasEnoughStock((int) $driver->id, $freshOrder, $resolveDriverTodayStock);

            $freshOrder->update([
                'driver_id' => (int) $driver->id,
                'status' => $hasEnoughStock ? 'approved' : 'rejected',
            ]);
        });

        $updatedOrder = Order::query()
            ->with(['customer:id,name,phone', 'iceType:id,name,weight'])
            ->find($pendingOrder->id);

        if ($updatedOrder) {
            if ($updatedOrder->status === 'approved') {
                $sendOrderStatusReply($updatedOrder, 'approved');
            } elseif ($updatedOrder->status === 'rejected') {
                $sendOrderStatusReply(
                    $updatedOrder,
                    'rejected',
                    'Stok bawaan supir tidak cukup untuk pesanan ini.'
                );
            }
        }
    }

    $orders = Order::query()
        ->with(['customer:id,name,address,zone', 'iceType:id,name,weight'])
        ->whereHas('customer', function ($query) use ($zone) {
            $query->whereRaw('LOWER(zone) = ?', [$zone]);
        })
        ->latest('id')
        ->limit(150)
        ->get()
        ->map(function (Order $order) {
            $iceTypeLabel = $order->iceType?->name ?? '-';
            $quantityLabel = max(1, (int) ($order->effective_quantity ?? $order->quantity ?? 1));

            return [
                'id' => $order->id,
                'driver_id' => $order->driver_id,
                'customer_name' => $order->customer?->name,
                'customer_address' => $order->customer?->address,
                'zone' => $order->customer?->zone,
                'items' => $order->items,
                'items_display' => $iceTypeLabel !== '-' ? $iceTypeLabel . ' - ' . $quantityLabel . ' pcs' : '',
                'ice_type_name' => $iceTypeLabel,
                'ice_type_weight' => $order->iceType?->weight,
                'quantity' => $quantityLabel,
                'status' => $order->status,
                'created_at' => $order->created_at,
            ];
        })
        ->values();

    return response()->json(['data' => $orders]);
});

Route::patch('/driver/orders/{order}/status', function (Request $request, Order $order) use ($resolveDriverFromToken, $resolveOrderStockDemand, $checkDriverHasEnoughStock, $resolveDriverTodayStock, $sendOrderStatusReply) {
    $driver = $resolveDriverFromToken($request);
    if (!$driver) {
        return response()->json([
            'message' => 'Unauthorized.',
        ], 401);
    }

    $validated = $request->validate([
        'status' => 'required|in:approved,rejected',
    ]);

    $statusUpdateResult = DB::transaction(function () use ($order, $validated, $driver, $resolveOrderStockDemand, $checkDriverHasEnoughStock, $resolveDriverTodayStock) {
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
            // Check stock dari kedua format (baru dan lama)
            $hasEnoughStock = $checkDriverHasEnoughStock((int) $driver->id, $freshOrder, $resolveDriverTodayStock);

            if (!$hasEnoughStock) {
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

            // Stok hanya dicek kecukupannya di sini, tapi belum dikurangi.
            // Pengurangan stok akan dilakukan saat order selesai (complete).
        }

        $freshOrder->update([
            'driver_id' => (int) $driver->id,
            'status' => $validated['status'],
        ]);

        return [
            'ok' => true,
            'order' => $freshOrder->fresh(),
            'message' => $validated['status'] === 'approved'
                ? 'Order berhasil diterima.'
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
    $updatedOrder->loadMissing('customer:id,name,phone');

    if ($updatedOrder->status === 'approved') {
        $sendOrderStatusReply($updatedOrder, 'approved');
    } elseif ($updatedOrder->status === 'rejected') {
        $note = $statusUpdateResult['message'] ?? 'Order ditolak oleh sistem.';
        $sendOrderStatusReply($updatedOrder, 'rejected', $note);
    }

    $todayStock = DriverStock::query()
        ->where('driver_id', (int) $driver->id)
        ->first();

    return response()->json([
        'message' => $statusUpdateResult['message'] ?? 'Order berhasil diproses.',
        'data' => [
            'id' => $updatedOrder->id,
            'status' => $updatedOrder->status,
            'driver_id' => $updatedOrder->driver_id,
            'stock_today' => [
                'stock_5kg' => (int) ($todayStock->stock_5kg ?? 0),
                'stock_20kg' => (int) ($todayStock->stock_20kg ?? 0),
            ],
        ],
    ]);
});

Route::patch('/driver/orders/{order}/complete', function (Request $request, Order $order) use ($resolveDriverFromToken, $haversineDistanceMeters, $resolveOrderStockDemand, $sendOrderStatusReply) {
    $driver = $resolveDriverFromToken($request);
    if (!$driver) {
        return response()->json([
            'message' => 'Unauthorized.',
        ], 401);
    }

    $validated = $request->validate([
        'latitude' => ['required', 'numeric', 'between:-90,90'],
        'longitude' => ['required', 'numeric', 'between:-180,180'],
    ]);

    $result = DB::transaction(function () use ($order, $driver, $validated, $haversineDistanceMeters, $resolveOrderStockDemand) {
        $freshOrder = Order::query()
            ->with('customer:id,zone,latitude,longitude')
            ->lockForUpdate()
            ->findOrFail($order->id);

        if ($freshOrder->status !== 'approved') {
            return [
                'ok' => false,
                'code' => 422,
                'message' => 'Order belum berstatus diterima, belum bisa selesai antar.',
            ];
        }

        if ((int) ($freshOrder->driver_id ?? 0) !== (int) $driver->id) {
            return [
                'ok' => false,
                'code' => 403,
                'message' => 'Order ini bukan milik Anda.',
            ];
        }

        $targetLat = (float) ($freshOrder->customer?->latitude ?? 0);
        $targetLng = (float) ($freshOrder->customer?->longitude ?? 0);

        if (abs($targetLat) < 0.000001 || abs($targetLng) < 0.000001) {
            return [
                'ok' => false,
                'code' => 422,
                'message' => 'Koordinat alamat pelanggan belum tersedia. Minta pelanggan kirim alamat yang lebih lengkap atau hubungi admin.',
            ];
        }

        $driverLat = (float) $validated['latitude'];
        $driverLng = (float) $validated['longitude'];
        $distanceMeters = $haversineDistanceMeters($driverLat, $driverLng, $targetLat, $targetLng);
        $maxDistanceMeters = 500.0;

        if ($distanceMeters > $maxDistanceMeters) {
            return [
                'ok' => false,
                'code' => 422,
                'message' => 'Lokasi Anda masih terlalu jauh dari titik alamat toko pelanggan.',
                'distance_m' => (int) round($distanceMeters),
                'max_distance_m' => (int) $maxDistanceMeters,
            ];
        }

        $freshOrder->update([
            'status' => 'completed',
        ]);

        $today = now()->toDateString();
        
        // Coba kurangi dari format baru (IceTypeDriverStock) dulu
        $iceTypeDriverStock = IceTypeDriverStock::query()
            ->forDate($today)
            ->where('driver_id', (int) $driver->id)
            ->where('ice_type_id', (int) ($freshOrder->ice_type_id ?? 0))
            ->lockForUpdate()
            ->first();

        if ($iceTypeDriverStock) {
            $quantity = (int) ($freshOrder->effective_quantity ?? $freshOrder->quantity ?? 1);
            $iceTypeDriverStock->update([
                'quantity' => max(0, $iceTypeDriverStock->quantity - $quantity),
            ]);
        } else {
            // Fallback ke format lama (DriverStock)
            $requiredStock = $resolveOrderStockDemand($freshOrder);
            $driverStock = DriverStock::query()
                ->where('driver_id', (int) $driver->id)
                ->lockForUpdate()
                ->first();

            if ($driverStock && ((int) $requiredStock['stock_5kg'] > 0 || (int) $requiredStock['stock_20kg'] > 0)) {
                $driverStock->update([
                    'stock_5kg' => max(0, ((int) $driverStock->stock_5kg) - (int) $requiredStock['stock_5kg']),
                    'stock_20kg' => max(0, ((int) $driverStock->stock_20kg) - (int) $requiredStock['stock_20kg']),
                ]);
            }
        }

        return [
            'ok' => true,
            'order' => $freshOrder->fresh(),
            'distance_m' => (int) round($distanceMeters),
            'max_distance_m' => (int) $maxDistanceMeters,
            'message' => 'Pesanan berhasil diselesaikan dan stok sudah dikurangi. Terima kasih sudah mengantar.',
        ];
    });

    if (!$result['ok']) {
        return response()->json([
            'message' => $result['message'],
            'distance_m' => $result['distance_m'] ?? null,
            'max_distance_m' => $result['max_distance_m'] ?? 500,
        ], $result['code']);
    }

    /** @var \App\Models\Order $updatedOrder */
    $updatedOrder = $result['order'];
    $updatedOrder->loadMissing('customer:id,name,phone');
    $sendOrderStatusReply($updatedOrder, 'completed');

    $todayStock = DriverStock::query()
        ->where('driver_id', (int) $driver->id)
        ->first();

    return response()->json([
        'message' => $result['message'],
        'data' => [
            'id' => $updatedOrder->id,
            'status' => $updatedOrder->status,
            'driver_id' => $updatedOrder->driver_id,
            'distance_m' => $result['distance_m'] ?? null,
            'max_distance_m' => $result['max_distance_m'] ?? 500,
            'stock_today' => [
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

    try {
        // Support both old format (stock_5kg, stock_20kg) dan new format (stocks array)
        $requestData = $request->all();
        $isNewFormat = isset($requestData['stocks']) && is_array($requestData['stocks']);

        if ($isNewFormat) {
            $validated = $request->validate([
                'stocks' => ['required', 'array', 'min:1'],
                'stocks.*.ice_type_id' => ['required', 'integer', 'exists:ice_types,id'],
                'stocks.*.quantity' => ['required', 'integer', 'min:0'],
            ]);
        } else {
            // Old format support
            $validated = $request->validate([
                'stock_5kg' => ['required', 'integer', 'min:0'],
                'stock_20kg' => ['required', 'integer', 'min:0'],
            ]);
        }

        $stockDate = now()->toDateString();

        // Check if driver already has stock input today
        if ($isNewFormat) {
            $hasStockToday = IceTypeDriverStock::hasStockInputToday($driver->id, $stockDate);
        } else {
            $existingStock = DriverStock::query()
                ->forDate($stockDate)
                ->where('driver_id', (int) $driver->id)
                ->first();
            $hasStockToday = !is_null($existingStock);
        }

        if ($hasStockToday) {
            return response()->json([
                'message' => 'Stok hari ini sudah pernah diinput. Input ulang tidak diperbolehkan.',
            ], 422);
        }

        // Save stock
        if ($isNewFormat) {
            DB::transaction(function () use ($driver, $validated, $stockDate) {
                foreach ($validated['stocks'] as $stock) {
                    IceTypeDriverStock::updateOrCreate([
                        'driver_id' => $driver->id,
                        'ice_type_id' => $stock['ice_type_id'],
                        'date' => $stockDate,
                    ], [
                        'quantity' => $stock['quantity'],
                    ]);
                }
            });

            $todayStocks = IceTypeDriverStock::getTodayStocks($driver->id, $stockDate);

            return response()->json([
                'message' => 'Stok bawaan berhasil disimpan.',
                'data' => [
                    'stocks' => $todayStocks->values(),
                ],
            ]);
        }

        // Old format - create DriverStock for backward compatibility
        $stock = DriverStock::updateOrCreate([
            'driver_id' => (int) $driver->id,
            'date' => $stockDate,
        ], [
            'stock_5kg' => (int) $validated['stock_5kg'],
            'stock_20kg' => (int) $validated['stock_20kg'],
        ]);

        return response()->json([
            'message' => 'Stok bawaan berhasil disimpan.',
            'data' => [
                'id' => $stock->id,
                'driver_id' => $stock->driver_id,
                'date' => $stock->date,
                'stock_5kg' => $stock->stock_5kg,
                'stock_20kg' => $stock->stock_20kg,
            ],
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Gagal menyimpan stok bawaan.',
            'error' => app()->hasDebugModeEnabled() ? $e->getMessage() : null,
        ], 500);
    }
});

Route::get('/driver/stocks/today', function (Request $request) use ($resolveDriverFromToken) {
    $driver = $resolveDriverFromToken($request);
    if (!$driver) {
        return response()->json([
            'message' => 'Unauthorized.',
        ], 401);
    }

    // Try new format first
    $todayStocks = IceTypeDriverStock::getTodayStocks($driver->id);
    $hasStockInput = IceTypeDriverStock::hasStockInputToday($driver->id);

    // Fallback to old format if new format doesn't exist
    if ($todayStocks->isEmpty()) {
        $stock = DriverStock::query()
            ->forDate($todayStockDate = now()->toDateString())
            ->where('driver_id', (int) $driver->id)
            ->first();

        return response()->json([
            'data' => [
                'stocks' => [],
                'stock_5kg' => (int) ($stock->stock_5kg ?? 0),
                'stock_20kg' => (int) ($stock->stock_20kg ?? 0),
                'has_stock_input' => !is_null($stock),
                'updated_at' => $stock?->updated_at,
            ],
        ]);
    }

    return response()->json([
        'data' => [
            'stocks' => $todayStocks->values(),
            'has_stock_input' => $hasStockInput,
        ],
    ]);
});

/**
 * GET /api/driver/customers
 * Daftar customer lama milik supir untuk dropdown/autofill
 */
Route::get('/driver/customers', function (Request $request) use ($resolveDriverFromToken) {
    $driver = $resolveDriverFromToken($request);
    if (!$driver) {
        return response()->json([
            'message' => 'Unauthorized.',
        ], 401);
    }

    $search = trim((string) $request->query('search', ''));
    $limit = max(1, min(50, (int) $request->query('limit', 30)));
    $zone = $driver->zone?->name ?? 'Unknown Zone';

    $customers = \App\Models\Customer::query()
        ->where('zone', $zone)
        ->when($search !== '', function ($query) use ($search) {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        })
        ->orderByDesc('updated_at')
        ->limit($limit)
        ->get(['id', 'name', 'address', 'phone', 'zone', 'latitude', 'longitude']);

    return response()->json([
        'data' => $customers,
    ]);
});

/**
 * POST /api/driver/customers
 * Supir membuat customer baru beserta order-nya
 */
Route::post('/driver/customers', function (Request $request) use ($resolveDriverFromToken) {
    $driver = $resolveDriverFromToken($request);
    if (!$driver) {
        return response()->json([
            'message' => 'Unauthorized.',
        ], 401);
    }

    $validated = $request->validate([
        'customer_name' => 'required|string|min:3|max:255',
        'customer_address' => 'required|string|min:5|max:500',
        'items' => 'required|string|min:3|max:500',
        'quantity' => 'required|integer|min:1|max:1000',
        'customer_phone' => 'required|string|min:10|max:20',
        'existing_customer_id' => 'nullable|integer|exists:customers,id',
        'ice_type_id' => 'required|exists:ice_types,id',
        'latitude' => 'nullable|numeric|between:-90,90',
        'longitude' => 'nullable|numeric|between:-180,180',
    ]);

    try {
        $result = DB::transaction(function () use ($driver, $validated) {
            $zone = $driver->zone?->name ?? 'Unknown Zone';
            $customerPhone = trim((string) ($validated['customer_phone'] ?? ''));
            if ($customerPhone === '') {
                $customerPhone = 'CUST_' . time() . '_' . rand(1000, 9999);
            }

            $customer = null;

            if (!empty($validated['existing_customer_id'])) {
                $customer = \App\Models\Customer::find($validated['existing_customer_id']);
            }

            if (!$customer) {
                $customer = \App\Models\Customer::where('phone', $customerPhone)->first();
            }

            if (!$customer) {
                // Buat customer baru hanya jika belum ada data yang cocok
                $customer = \App\Models\Customer::create([
                    'name' => trim($validated['customer_name']),
                    'address' => trim($validated['customer_address']),
                    'zone' => $zone,
                    'phone' => $customerPhone,
                    'latitude' => (float) ($validated['latitude'] ?? 0),
                    'longitude' => (float) ($validated['longitude'] ?? 0),
                ]);
            }

            // Buat order untuk customer ini
            // Gunakan ice_type_id dan quantity yang dikirim user
            $itemsString = trim($validated['items']);

            $order = \App\Models\Order::create([
                'customer_id' => $customer->id,
                'phone' => $customerPhone, // Phone dari customer yang baru dibuat
                'ice_type_id' => $validated['ice_type_id'], // Gunakan dari request, bukan hardcode
                'items' => $itemsString,
                'status' => 'pending',
                'quantity' => (int) $validated['quantity'],
            ]);

            return [
                'customer' => $customer,
                'order' => $order,
            ];
        });

        return response()->json([
            'message' => 'Customer dan order berhasil dibuat. Menunggu untuk diproses di dashboard admin.',
            'data' => [
                'customer_id' => $result['customer']->id,
                'customer_name' => $result['customer']->name,
                'customer_phone' => $result['customer']->phone,
                'order_id' => $result['order']->id,
                'order_status' => $result['order']->status,
            ],
        ], 201);
    } catch (\Throwable $e) {
        return response()->json([
            'message' => 'Gagal membuat customer dan order.',
            'error' => app()->hasDebugModeEnabled() ? $e->getMessage() : null,
        ], 500);
    }
});

