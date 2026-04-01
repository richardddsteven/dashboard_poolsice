# Driver App Flutter

Aplikasi sederhana untuk supir agar menerima notifikasi order baru berdasarkan:

- `driver_id` yang sama
- `zone` pelanggan yang sama

## Fitur

- Input `Driver ID` dan `Zona`
- Mode online/offline untuk polling order
- Polling API setiap 10 detik
- Local notification untuk order baru yang cocok
- Daftar order terbaru di halaman utama

## Jalankan Aplikasi

1. Masuk ke folder app:

	 ```bash
	 cd driver_app_flutter
	 ```

2. Install dependency:

	 ```bash
	 flutter pub get
	 ```

3. Jalankan:

	 ```bash
	 flutter run
	 ```

## Konfigurasi Backend

Di `lib/main.dart`, aplikasi memanggil endpoint:

`GET /api/driver/orders/notifications?driver_id={id}&zone={zona}&last_id={lastOrderId}`

Contoh response:

```json
{
	"data": [
		{
			"id": 130,
			"driver_id": 5,
			"customer_name": "Budi",
			"zone": "Barat",
			"items": "Es Kristal 5kg",
			"status": "pending"
		}
	]
}
```

## Contoh Endpoint Laravel

Tambahkan route API dan kembalikan hanya order yang cocok driver + zona.

```php
Route::get('/driver/orders/notifications', function (Illuminate\Http\Request $request) {
		$driverId = (int) $request->query('driver_id');
		$zone = trim((string) $request->query('zone'));
		$lastId = (int) $request->query('last_id', 0);

		$orders = App\Models\Order::query()
				->with('customer')
				->where('id', '>', $lastId)
				->where('driver_id', $driverId)
				->where('status', 'pending')
				->whereHas('customer', function ($q) use ($zone) {
						$q->whereRaw('LOWER(zone) = ?', [strtolower($zone)]);
				})
				->orderBy('id')
				->get()
				->map(function ($order) {
						return [
								'id' => $order->id,
								'driver_id' => $order->driver_id,
								'customer_name' => $order->customer?->name,
								'zone' => $order->customer?->zone,
								'items' => $order->items,
								'status' => $order->status,
						];
				});

		return response()->json(['data' => $orders]);
});
```

Catatan: schema sekarang belum ada `orders.driver_id`. Tambahkan migration jika ingin filtering supir benar-benar presisi.
