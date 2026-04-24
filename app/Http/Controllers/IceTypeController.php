<?php

namespace App\Http\Controllers;

use App\Models\IceType;
use Illuminate\Http\Request;

class IceTypeController extends Controller
{
    public function index()
    {
        $iceTypes = IceType::orderBy('weight')->get();
        return view('ice_types.index', compact('iceTypes'));
    }

    public function create()
    {
        return view('ice_types.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'weight'      => ['required', 'numeric', 'min:0.01'],
            'price'       => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        IceType::create($validated);

        return redirect()->route('ice-types.index')
            ->with('success', 'Jenis es berhasil ditambahkan.');
    }

    public function edit(IceType $iceType)
    {
        return view('ice_types.edit', compact('iceType'));
    }

    public function update(Request $request, IceType $iceType)
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:100'],
            'weight'      => ['required', 'numeric', 'min:0.01'],
            'price'       => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $iceType->update($validated);

        return redirect()->route('ice-types.index')
            ->with('success', 'Jenis es berhasil diperbarui.');
    }

    public function destroy(IceType $iceType)
    {
        $iceType->delete();

        return redirect()->route('ice-types.index')
            ->with('success', 'Jenis es berhasil dihapus.');
    }
}
