<?php

namespace App\Http\Controllers;

use App\Models\RouteStop;
use App\Models\Zone;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RouteStopController extends Controller
{
    /**
     * Tampilkan daftar jalur untuk satu zona.
     * GET /zones/{zone}/route-stops
     */
    public function index(Zone $zone)
    {
        $routeStops = $zone->routeStops()->get();

        return view('route_stops.index', compact('zone', 'routeStops'));
    }

    /**
     * Form tambah jalur baru.
     * GET /zones/{zone}/route-stops/create
     */
    public function create(Zone $zone)
    {
        // Hitung order_index berikutnya
        $nextIndex = ($zone->routeStops()->max('order_index') ?? 0) + 1;

        return view('route_stops.create', compact('zone', 'nextIndex'));
    }

    /**
     * Simpan jalur baru.
     * POST /zones/{zone}/route-stops
     */
    public function store(Request $request, Zone $zone)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'order_index'   => 'required|integer|min:1',
            'latitude'      => 'required|numeric|between:-90,90',
            'longitude'     => 'required|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:50|max:10000',
        ]);

        $validated['zone_id'] = $zone->id;

        RouteStop::create($validated);

        return redirect()
            ->route('route-stops.index', $zone)
            ->with('success', 'Jalur berhasil ditambahkan!');
    }

    /**
     * Form edit jalur.
     * GET /zones/{zone}/route-stops/{routeStop}/edit
     */
    public function edit(Zone $zone, RouteStop $routeStop)
    {
        abort_if($routeStop->zone_id !== $zone->id, 404);

        return view('route_stops.edit', compact('zone', 'routeStop'));
    }

    /**
     * Simpan perubahan jalur.
     * PUT /zones/{zone}/route-stops/{routeStop}
     */
    public function update(Request $request, Zone $zone, RouteStop $routeStop)
    {
        abort_if($routeStop->zone_id !== $zone->id, 404);

        $validated = $request->validate([
            'name'          => 'required|string|max:100',
            'order_index'   => 'required|integer|min:1',
            'latitude'      => 'required|numeric|between:-90,90',
            'longitude'     => 'required|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:50|max:10000',
        ]);

        DB::transaction(function () use ($routeStop, $validated, $zone) {
            $routeStop->update($validated);

            // Re-assign semua customer di zona ini berdasarkan koordinat terbaru jalur
            $this->reassignCustomersInZone($zone);
        });

        return redirect()
            ->route('route-stops.index', $zone)
            ->with('success', 'Jalur berhasil diperbarui!');
    }

    /**
     * Hapus jalur.
     * DELETE /zones/{zone}/route-stops/{routeStop}
     */
    public function destroy(Zone $zone, RouteStop $routeStop)
    {
        abort_if($routeStop->zone_id !== $zone->id, 404);

        DB::transaction(function () use ($routeStop, $zone) {
            // Kosongkan route_stop_id customer yang menggunakan jalur ini
            Customer::where('route_stop_id', $routeStop->id)
                ->update(['route_stop_id' => null]);

            $routeStop->delete();

            // Perbaiki ulang nomor urutan agar tetap konsekutif (1, 2, 3...)
            $this->reindexStops($zone);
        });

        return redirect()
            ->route('route-stops.index', $zone)
            ->with('success', 'Jalur berhasil dihapus dan urutan diperbaiki.');
    }

    /**
     * Re-assign manual jalur untuk satu customer.
     * PATCH /zones/{zone}/route-stops/assign-customer
     */
    public function assignCustomer(Request $request, Zone $zone)
    {
        $validated = $request->validate([
            'customer_id'   => 'required|integer|exists:customers,id',
            'route_stop_id' => 'nullable|integer|exists:route_stops,id',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);

        // Verifikasi customer memang berada di zona ini
        if (strtolower($customer->zone ?? '') !== strtolower($zone->name)) {
            return back()->with('error', 'Customer bukan bagian dari zona ini.');
        }

        $customer->update(['route_stop_id' => $validated['route_stop_id'] ?? null]);

        return back()->with('success', 'Jalur customer berhasil diperbarui.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Re-assign semua customer di zona berdasarkan koordinat (auto-detect).
     * Dipanggil setelah jalur diperbarui agar mapping tetap akurat.
     */
    private function reassignCustomersInZone(Zone $zone): void
    {
        $customers = Customer::whereRaw('LOWER(zone) = ?', [strtolower($zone->name)])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        foreach ($customers as $customer) {
            $stop = RouteStop::detectForCoordinates(
                (float) $customer->latitude,
                (float) $customer->longitude,
                $zone->id
            );

            $customer->update(['route_stop_id' => $stop?->id]);
        }
    }

    /**
     * Perbaiki nomor order_index agar tetap berurutan 1, 2, 3...
     * setelah ada jalur yang dihapus.
     */
    private function reindexStops(Zone $zone): void
    {
        $stops = RouteStop::where('zone_id', $zone->id)
            ->orderBy('order_index')
            ->get();

        foreach ($stops as $i => $stop) {
            $stop->update(['order_index' => $i + 1]);
        }
    }
}
