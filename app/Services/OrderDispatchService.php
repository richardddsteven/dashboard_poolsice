<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Driver;
use App\Models\DriverStock;
use App\Models\IceTypeDriverStock;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class OrderDispatchService
{
    public function dispatch(Order $order, Customer $customer): void
    {
        $customerZone = strtolower(trim((string) ($customer->zone ?? '')));

        if ($customerZone === '') {
            Log::info('[FCM] Zone kosong, notifikasi supir dilewati.', ['order_id' => $order->id]);
            return;
        }

        if (!$customer->routeStop) {
            $rawPayload = is_array($order->raw_payload ?? null) ? $order->raw_payload : [];
            $rawPayload['route_review_required'] = true;
            $rawPayload['route_review_reason'] = 'customer_route_unmapped';
            $rawPayload['route_review_status'] = 'pending_manual_review';
            $rawPayload['route_review_at'] = now()->toDateTimeString();

            $order->update(['raw_payload' => $rawPayload]);

            Log::info('[Webhook] Order ditahan untuk manual route review karena customer belum termapping.', [
                'order_id' => $order->id,
                'customer_id' => $customer->id,
                'zone' => $customerZone,
            ]);

            app(FonnteService::class)->sendMessage(
                $customer->phone,
                "Terima kasih {$customer->name}, pesanan Anda kami terima dan sedang kami review karena jalur alamat Anda belum dipetakan. Tim admin akan cek dan atur jalurnya terlebih dahulu."
            );

            return;
        }

        try {
            $drivers = Driver::with(['zone:id,name', 'currentRouteStop'])
                ->whereNotNull('fcm_token')
                ->whereNotNull('api_token')
                ->whereHas('zone', function ($query) use ($customerZone) {
                    $query->whereRaw('LOWER(name) = ?', [$customerZone]);
                })
                ->get();

            if ($drivers->isEmpty()) {
                Log::info('[FCM] Tidak ada supir online dengan FCM token di zona: ' . $customerZone);
                return;
            }

            $fcm = app(FcmService::class);
            $order->loadMissing('iceType:id,name,weight');

            $orderData = [
                'id'            => $order->id,
                'customer_name' => $customer->name,
                'zone'          => $customer->zone,
                'items'         => $order->items ?? '',
            ];

            $eligibleDriversCount = 0;
            $skippedByDistanceCount = 0;
            $skippedByStockCount = 0;
            $assignedDriverId = null;

            foreach ($drivers as $driver) {
                if (!Order::isEligibleForDriver($order, $driver)) {
                    $skippedByDistanceCount++;
                    Log::info('[FCM] Notifikasi dilewati, supir sudah terlalu jauh.', [
                        'driver_id' => $driver->id,
                        'order_id'  => $order->id,
                    ]);
                    continue;
                }

                if (!$this->driverHasEnoughStockForOrder((int) $driver->id, $order)) {
                    $skippedByStockCount++;
                    Log::info('[FCM] Notifikasi dilewati, stok supir tidak cukup.', [
                        'driver_id' => $driver->id,
                        'order_id'  => $order->id,
                    ]);
                    continue;
                }

                $eligibleDriversCount++;
                $assignedDriverId ??= (int) $driver->id;
                $fcm->sendOrderNotification($driver->fcm_token, $orderData);

                Log::info('[FCM] Notifikasi order terkirim ke supir.', [
                    'driver_id'   => $driver->id,
                    'driver_name' => $driver->name,
                    'order_id'    => $order->id,
                    'zone'        => $customerZone,
                ]);
            }

            if ($eligibleDriversCount > 0) {
                $rawPayload = is_array($order->raw_payload ?? null) ? $order->raw_payload : [];
                if (($rawPayload['route_review_status'] ?? null) === 'pending_manual_review') {
                    $rawPayload['route_review_status'] = 'resolved';
                    $rawPayload['route_review_resolved_at'] = now()->toDateTimeString();
                    $order->update(['raw_payload' => $rawPayload]);
                }

                $updateData = [];
                $wasPending = $order->status === 'pending';
                if ($order->status !== 'approved') {
                    $updateData['status'] = 'approved';
                }
                if (empty($order->driver_id) && $assignedDriverId !== null) {
                    $updateData['driver_id'] = $assignedDriverId;
                }

                if (!empty($updateData)) {
                    $order->update($updateData);
                }

                if ($wasPending && !empty($order->driver_id)) {
                    $freshOrder = $order->fresh(['iceType']);
                    $orderStockDemand = $freshOrder ? $this->resolveOrderStockDemand($freshOrder) : null;
                    $this->reduceDriverStockForOrder((int) $order->driver_id, $freshOrder ?? $order, $orderStockDemand);
                }

                app(FonnteService::class)->sendOrderStatusUpdate(
                    $customer->phone,
                    $order->fresh(['customer', 'iceType']),
                    'approved'
                );

                Log::info('[Webhook] Order disetujui setelah ada supir eligible.', [
                    'order_id' => $order->id,
                    'zone' => $customerZone,
                    'eligible_driver_count' => $eligibleDriversCount,
                    'assigned_driver_id' => $assignedDriverId,
                ]);

                return;
            }

            if ($eligibleDriversCount === 0 && $drivers->isNotEmpty()) {
                $rawPayload = is_array($order->raw_payload ?? null) ? $order->raw_payload : [];

                // Order hasil manual review jangan langsung ditolak kalau belum ada driver eligible.
                // Biarkan tetap pending supaya admin bisa cek ulang jalurnya.
                if (!empty($rawPayload['route_review_required'])) {
                    $rawPayload['route_review_status'] = 'needs_followup_review';
                    $rawPayload['route_review_followup_at'] = now()->toDateTimeString();
                    $order->update([
                        'status' => 'pending',
                        'raw_payload' => $rawPayload,
                    ]);

                    Log::info('[Webhook] Manual-review order tetap pending karena belum ada supir eligible setelah evaluasi ulang.', [
                        'order_id' => $order->id,
                        'zone' => $customerZone,
                        'skipped_by_distance' => $skippedByDistanceCount,
                        'skipped_by_stock' => $skippedByStockCount,
                        'online_driver_count' => $drivers->count(),
                    ]);

                    return;
                }

                $rejectNote = $skippedByStockCount > 0 && $skippedByDistanceCount === 0
                    ? 'saat ini stok bawaan supir kami tidak cukup untuk pesanan Anda'
                    : 'saat ini semua supir kami belum dapat memenuhi pesanan Anda';

                Log::info('[Webhook] Auto-reject order, tidak ada supir eligible untuk notifikasi', [
                    'order_id'            => $order->id,
                    'zone'                => $customerZone,
                    'skipped_by_distance' => $skippedByDistanceCount,
                    'skipped_by_stock'    => $skippedByStockCount,
                    'online_driver_count' => $drivers->count(),
                ]);

                $order->update(['status' => 'rejected']);

                app(FonnteService::class)->sendMessage(
                    $customer->phone,
                    "Mohon maaf {$customer->name}, pesanan Anda kami tolak karena {$rejectNote}."
                );
            }
        } catch (\Throwable $e) {
            Log::error('[FCM] Gagal kirim notifikasi ke supir: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'zone'     => $customerZone,
            ]);
        }
    }

    private function driverHasEnoughStockForOrder(int $driverId, Order $order): bool
    {
        $driverStocks = $this->resolveDriverTodayStock($driverId);

        if (empty($driverStocks)) {
            return false;
        }

        $required = max(1, (int) ($order->effective_quantity ?? $order->quantity ?? 1));
        $iceTypeId = (int) ($order->ice_type_id ?? 0);
        $iceTypeWeight = (float) ($order->iceType?->weight ?? 0);

        if ($iceTypeId > 0) {
            foreach ($driverStocks as $stock) {
                if (isset($stock['ice_type_id']) && (int) $stock['ice_type_id'] === $iceTypeId) {
                    return (int) $stock['quantity'] >= $required;
                }
            }

            return false;
        }

        foreach ($driverStocks as $stock) {
            $stockWeight = (float) ($stock['weight'] ?? 0);
            if (abs($stockWeight - $iceTypeWeight) < 0.01) {
                return (int) $stock['quantity'] >= $required;
            }
        }

        return false;
    }

    private function resolveOrderStockDemand(Order $order): array
    {
        $required = [
            'stock_5kg' => 0,
            'stock_20kg' => 0,
            'ice_type_id' => null,
            'quantity' => 0,
        ];

        $quantity = max(1, (int) ($order->effective_quantity ?? $order->quantity ?? 1));
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

        if (preg_match('/(?:\b5\s*kg\b\D{0,24}(\d{1,3})|(\d{1,3})\D{0,24}\b5\s*kg\b)/i', $items, $matches)) {
            $required['stock_5kg'] = (int) (($matches[1] ?: $matches[2]) ?? 0);
        }

        if (preg_match('/(?:\b20\s*kg\b\D{0,24}(\d{1,3})|(\d{1,3})\D{0,24}\b20\s*kg\b)/i', $items, $matches)) {
            $required['stock_20kg'] = (int) (($matches[1] ?: $matches[2]) ?? 0);
        }

        if ($required['stock_5kg'] > 0 || $required['stock_20kg'] > 0) {
            return $required;
        }

        $required['ice_type_id'] = (int) ($order->ice_type_id ?? 0);

        return $required;
    }

    private function reduceDriverStockForOrder(int $driverId, Order $order, ?array $orderStockDemand = null): void
    {
        $today = now()->toDateString();

        $iceTypeDriverStock = IceTypeDriverStock::query()
            ->forDate($today)
            ->where('driver_id', $driverId)
            ->where('ice_type_id', (int) ($order->ice_type_id ?? 0))
            ->lockForUpdate()
            ->first();

        if ($iceTypeDriverStock) {
            $quantity = (int) ($order->effective_quantity ?? $order->quantity ?? 1);

            $iceTypeDriverStock->update([
                'quantity' => max(0, $iceTypeDriverStock->quantity - $quantity),
            ]);

            return;
        }

        $requiredStock = $orderStockDemand ?? $this->resolveOrderStockDemand($order);
        
        $driverStock = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('driver_stocks')) {
            $driverStock = DriverStock::query()
                ->where('driver_id', $driverId)
                ->lockForUpdate()
                ->first();
        }

        if ($driverStock && ((int) $requiredStock['stock_5kg'] > 0 || (int) $requiredStock['stock_20kg'] > 0)) {
            $driverStock->update([
                'stock_5kg' => max(0, ((int) $driverStock->stock_5kg) - (int) $requiredStock['stock_5kg']),
                'stock_20kg' => max(0, ((int) $driverStock->stock_20kg) - (int) $requiredStock['stock_20kg']),
            ]);
        }
    }

    private function resolveDriverTodayStock(int $driverId): array
    {
        $newFormatStocks = IceTypeDriverStock::query()
            ->forDate(now()->toDateString())
            ->where('driver_id', $driverId)
            ->get()
            ->map(fn ($stock) => [
                'ice_type_id' => (int) $stock->ice_type_id,
                'quantity'    => (int) $stock->quantity,
            ]);

        if ($newFormatStocks->isNotEmpty()) {
            return $newFormatStocks->values()->all();
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('driver_stocks')) {
            return [];
        }

        $oldFormatStock = DriverStock::query()
            ->where('driver_id', $driverId)
            ->whereDate('date', now()->toDateString())
            ->first();

        if (!$oldFormatStock) {
            return [];
        }

        $result = [];
        if ((int) $oldFormatStock->stock_5kg > 0) {
            $result[] = [
                'quantity' => (int) $oldFormatStock->stock_5kg,
                'weight' => 5,
            ];
        }
        if ((int) $oldFormatStock->stock_20kg > 0) {
            $result[] = [
                'quantity' => (int) $oldFormatStock->stock_20kg,
                'weight' => 20,
            ];
        }

        return $result;
    }
}
