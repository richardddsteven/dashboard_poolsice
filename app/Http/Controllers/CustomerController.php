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
        return view('customers.index', compact('zones', 'selectedZone', 'customers', 'latestCustomers', 'search'));
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
        ]);

        Customer::create($validated);

        return redirect()->route('customers.index')
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
        ]);

        $customer->update($validated);

        return redirect()->route('customers.index')
            ->with('success', 'Customer berhasil diupdate.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Customer berhasil dihapus.');
    }
}
