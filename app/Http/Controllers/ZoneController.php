<?php

namespace App\Http\Controllers;

use App\Models\Zone;
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
        ]);
        Zone::create($validated);
        return redirect()->route('customers.index')->with('success', 'Zone berhasil ditambahkan!');
    }
}
