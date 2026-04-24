<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ZoneController extends Controller
{
    public function create()
    {
        return view('zones.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:zones,name',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        Zone::create($validated);
        return redirect()->route('customers.index')->with('success', 'Zone berhasil ditambahkan!');
    }

    public function edit(Zone $zone)
    {
        return view('zones.edit', compact('zone'));
    }

    public function update(Request $request, Zone $zone)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:zones,name,' . $zone->id,
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        DB::transaction(function () use ($zone, $validated) {
            $oldName = $zone->name;
            $newName = $validated['name'];

            $zone->update($validated);

            if ($oldName !== $newName) {
                Customer::where('zone', $oldName)->update(['zone' => $newName]);
            }
        });

        return redirect()->route('customers.index')->with('success', 'Zone berhasil diperbarui!');
    }

    public function destroy(Zone $zone)
    {
        if ($zone->customers()->count() > 0) {
            return redirect()->route('customers.index')->with('error', 'Zona tidak dapat dihapus karena masih digunakan pelanggan.');
        }

        $zone->delete();

        return redirect()->route('customers.index')->with('success', 'Zone berhasil dihapus!');
    }
}
