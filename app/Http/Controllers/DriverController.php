<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Driver;
use App\Models\Zone;
use Illuminate\Support\Facades\Hash;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $query = Driver::with('zone')->withCount(['orders as completed_orders_count' => function ($q) {
            $q->where('status', 'completed');
        }]);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        $drivers = $query->latest()->get();
        $zones = Zone::all();
        
        return view('drivers.index', compact('drivers', 'zones'));
    }

    public function create()
    {
        $zones = Zone::all();
        return view('drivers.create', compact('zones'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'zone_id' => 'required|exists:zones,id',
            'username' => 'required|string|max:50|unique:drivers,username',
            'password' => 'required|string|min:6|max:100',
        ]);

        Driver::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'zone_id' => $validated['zone_id'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('drivers.index')->with('success', 'Driver berhasil ditambahkan');
    }

    public function edit(Driver $driver)
    {
        $zones = Zone::all();
        return view('drivers.edit', compact('driver', 'zones'));
    }

    public function update(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'zone_id' => 'required|exists:zones,id',
            'username' => 'required|string|max:50|unique:drivers,username,' . $driver->id,
            'password' => 'nullable|string|min:6|max:100',
        ]);

        $payload = [
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'zone_id' => $validated['zone_id'],
            'username' => $validated['username'],
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $driver->update($payload);

        return redirect()->route('drivers.index')->with('success', 'Driver berhasil diperbarui');
    }

    public function destroy(Driver $driver)
    {
        $driver->delete();

        return redirect()->route('drivers.index')->with('success', 'Driver berhasil dihapus');
    }
}
