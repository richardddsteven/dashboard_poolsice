<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Driver;
use App\Models\Zone;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $query = Driver::with('zone');

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
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'zone_id' => 'required|exists:zones,id',
        ]);

        Driver::create($request->all());

        return redirect()->route('drivers.index')->with('success', 'Driver berhasil ditambahkan');
    }

    public function edit(Driver $driver)
    {
        $zones = Zone::all();
        return view('drivers.edit', compact('driver', 'zones'));
    }

    public function update(Request $request, Driver $driver)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'zone_id' => 'required|exists:zones,id',
        ]);

        $driver->update($request->all());

        return redirect()->route('drivers.index')->with('success', 'Driver berhasil diperbarui');
    }

    public function destroy(Driver $driver)
    {
        $driver->delete();

        return redirect()->route('drivers.index')->with('success', 'Driver berhasil dihapus');
    }
}
