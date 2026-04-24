<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $zones = \App\Models\Zone::withCount('customers')->orderBy('name')->get();
        $latestCustomers = Customer::latest()->take(5)->get();
        $selectedZone = $request->query('zone');
        $search = $request->query('search');
        $defaultZoneCoordinates = [
            'badung' => ['lat' => -8.5810, 'lng' => 115.1770],
            'bangli' => ['lat' => -8.4543, 'lng' => 115.3549],
            'buleleng' => ['lat' => -8.2215, 'lng' => 114.9876],
            'canggu' => ['lat' => -8.6483, 'lng' => 115.1385],
            'denpasar' => ['lat' => -8.6705, 'lng' => 115.2126],
            'gianyar' => ['lat' => -8.5439, 'lng' => 115.3250],
            'jembrana' => ['lat' => -8.3506, 'lng' => 114.6400],
            'jimbaran' => ['lat' => -8.7908, 'lng' => 115.1660],
            'karangasem' => ['lat' => -8.4460, 'lng' => 115.6122],
            'kerobokan' => ['lat' => -8.6600, 'lng' => 115.1670],
            'klungkung' => ['lat' => -8.5406, 'lng' => 115.4039],
            'kuta' => ['lat' => -8.7177, 'lng' => 115.1682],
            'legian' => ['lat' => -8.7067, 'lng' => 115.1706],
            'negara' => ['lat' => -8.3566, 'lng' => 114.6342],
            'nusa dua' => ['lat' => -8.8079, 'lng' => 115.2301],
            'sanur' => ['lat' => -8.6932, 'lng' => 115.2638],
            'seminyak' => ['lat' => -8.6906, 'lng' => 115.1682],
            'singaraja' => ['lat' => -8.1120, 'lng' => 115.0882],
            'tabanan' => ['lat' => -8.5395, 'lng' => 115.1249],
            'ubud' => ['lat' => -8.5069, 'lng' => 115.2625],
            'uluwatu' => ['lat' => -8.8291, 'lng' => 115.0849],
        ];
        $zonePalette = ['#2563EB', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4', '#14B8A6', '#F97316', '#EC4899', '#84CC16'];

        $zoneMapPoints = $zones->values()->map(function ($zone, $index) use ($defaultZoneCoordinates, $zonePalette) {
            $normalizedName = strtolower(trim((string) $zone->name));
            $coordinate = null;

            if ($zone->latitude !== null && $zone->longitude !== null) {
                $coordinate = [
                    'lat' => (float) $zone->latitude,
                    'lng' => (float) $zone->longitude,
                ];
            } else {
                $coordinate = $defaultZoneCoordinates[$normalizedName] ?? null;
            }

            if (!$coordinate) {
                $fallbackCenterLat = -8.4095;
                $fallbackCenterLng = 115.1889;
                $angle = deg2rad(($index * 43) % 360);
                $radius = 0.09 + (($index % 4) * 0.03);

                $coordinate = [
                    'lat' => $fallbackCenterLat + (cos($angle) * $radius * 0.75),
                    'lng' => $fallbackCenterLng + (sin($angle) * $radius),
                ];
            }

            return [
                'name' => $zone->name,
                'customersCount' => (int) ($zone->customers_count ?? 0),
                'lat' => (float) $coordinate['lat'],
                'lng' => (float) $coordinate['lng'],
                'color' => $zonePalette[$index % count($zonePalette)],
                'url' => route('customers.index', ['zone' => $zone->name]),
            ];
        })->all();

        $customers = collect();
        if ($selectedZone) {
            $query = Customer::where('zone', $selectedZone);
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
            $customers = $query->oldest()->paginate(15)->withQueryString();
        }
        return view('customers.index', compact('zones', 'selectedZone', 'customers', 'latestCustomers', 'search', 'zoneMapPoints'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $zones = \App\Models\Zone::orderBy('name')->get();
        return view('customers.create', compact('zones'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'zone' => 'nullable|string|max:255',
            'phone' => 'required|string|unique:customers,phone',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $customer = Customer::create($validated);

        return redirect()->route('customers.index', ['zone' => $customer->zone])
            ->with('success', 'Customer berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        $zones = \App\Models\Zone::orderBy('name')->get();
        return view('customers.edit', compact('customer', 'zones'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'zone' => 'nullable|string|max:255',
            'phone' => 'required|string|unique:customers,phone,' . $customer->id,
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index', ['zone' => $customer->zone])
            ->with('success', 'Customer berhasil diupdate.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $zone = $customer->zone;
        $customer->delete();

        return redirect()->route('customers.index', ['zone' => $zone])
            ->with('success', 'Customer berhasil dihapus.');
    }
}
