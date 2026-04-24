@extends('layouts.dashboard')

@section('title')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<style>
    .zone-map {
        height: 420px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        overflow: hidden;
        background: #F8FAFC;
    }

    .zone-map-empty {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 180px;
        border: 1px dashed var(--border-color);
        border-radius: 12px;
        color: var(--text-muted);
        font-size: 14px;
        margin-top: 12px;
    }

    .zone-map-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 14px;
    }

    .zone-map-legend-item {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 6px 10px;
        border-radius: 999px;
        border: 1px solid var(--border-color);
        background: #fff;
        font-size: 13px;
        color: var(--text-secondary);
        text-decoration: none;
        transition: all 0.15s ease;
    }

    .zone-map-legend-item:hover {
        border-color: var(--accent);
        color: var(--text-main);
        transform: translateY(-1px);
    }

    .zone-map-dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        flex-shrink: 0;
    }

    .zone-pin-wrap {
        background: transparent;
        border: none;
    }

    .zone-pin {
        display: block;
        width: 14px;
        height: 14px;
        border-radius: 999px;
        border: 2px solid #fff;
        background: var(--pin-color, #2563EB);
        box-shadow: 0 1px 6px rgba(15, 23, 42, 0.35);
    }

    .zone-map-popup-title {
        font-size: 14px;
        font-weight: 700;
        color: #0F172A;
    }

    .zone-map-popup-meta {
        margin-top: 4px;
        font-size: 12px;
        color: #64748B;
    }

    .zone-map-popup-link {
        display: inline-block;
        margin-top: 8px;
        font-size: 12px;
        color: #2563EB;
        text-decoration: none;
        font-weight: 600;
    }

    .zone-map-popup-link:hover {
        text-decoration: underline;
    }

    @media (max-width: 768px) {
        .zone-map {
            height: 320px;
        }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Pelanggan</h1>
        <p class="page-subtitle">Manajemen data pelanggan Pools Ice</p>
    </div>
    @if($selectedZone)
    <div style="display: flex; gap: 10px; align-items: center;">
        <a href="{{ route('customers.index') }}" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"></path><polyline points="12 19 5 12 12 5"></polyline></svg>
            Kembali
        </a>
        <a href="{{ route('customers.create', ['zone' => $selectedZone]) }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
            Tambah Pelanggan
        </a>
    </div>
    @endif
</div>

@if(!$selectedZone)
    <!-- Zone Selection SaaS Style -->
    <div style="margin-bottom: 32px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <div>
                <h3 style="font-size: 17px; font-weight: 700; color: var(--text-main); margin-bottom: 4px;">Peta Zona</h3>
                <p style="font-size: 14px; color: var(--text-muted); margin: 0;">Pilih direktori wilayah untuk mengelola pelanggan</p>
            </div>
            <a href="{{ route('zones.create') }}" class="btn btn-primary" style="padding: 6px 14px; font-size: 14px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                Tambah Zona
            </a>
        </div>

        @if(!empty($zoneMapPoints))
        <div class="card" style="border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 18px; padding: 18px;">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; flex-wrap: wrap;">
                <h4 style="font-size: 15px; font-weight: 700; color: var(--text-main); margin: 0;">Visualisasi Zona Pulau Bali</h4>
                <span style="font-size: 12px; color: var(--text-muted); border: 1px solid var(--border-color); border-radius: 999px; padding: 4px 10px;">{{ count($zoneMapPoints) }} Zona</span>
            </div>
            <div id="baliZoneMap" class="zone-map"></div>
            <div id="zoneMapLegend" class="zone-map-legend"></div>
        </div>
        @else
        <div class="zone-map-empty">Belum ada zona untuk ditampilkan pada peta.</div>
        @endif

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
            @foreach($zones as $zone)
            <div style="position: relative; background: #fff; border: 1px solid var(--border-color); border-radius: 8px; border-left: 3px solid var(--accent); overflow: hidden; transition: box-shadow 0.2s, transform 0.2s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,0.05)'; this.style.transform='translateY(-2px)';" onmouseout="this.style.boxShadow='none'; this.style.transform='none';">
                
                <a href="{{ route('customers.index', ['zone' => $zone->name]) }}" style="display: block; padding: 20px; text-decoration: none;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(59, 130, 246, 0.1); color: var(--accent); display: flex; align-items: center; justify-content: center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: var(--text-main); font-size: 16px;">{{ ucfirst($zone->name) }}</div>
                            <div style="font-size: 14px; color: var(--text-muted); margin-top: 2px;">{{ $zone->customers_count ?? 0 }} Pelanggan</div>
                        </div>
                    </div>
                </a>
                
                <!-- Corner Actions (absolute) -->
                <div style="position: absolute; top: 12px; right: 12px; display: flex; align-items: center; gap: 4px;">
                    <a href="{{ route('zones.edit', $zone) }}" style="color: var(--text-light); border-radius: 4px; display: flex; align-items: center; justify-content: center; width: 26px; height: 26px; transition: background 0.15s;" onmouseover="this.style.background='var(--bg-body)'; this.style.color='var(--text-main)';" onmouseout="this.style.background='transparent'; this.style.color='var(--text-light)';" title="Edit Zona">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                    </a>
                    <form action="{{ route('zones.destroy', $zone) }}" method="POST" data-zone-name="{{ $zone->name }}" onsubmit="event.preventDefault(); showZoneDeleteModal(this);" style="margin: 0; display: flex; align-items: center;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" style="background: transparent; border: none; padding: 0; color: var(--text-light); border-radius: 4px; display: flex; align-items: center; justify-content: center; width: 26px; height: 26px; cursor: pointer; transition: background 0.15s;" onmouseover="this.style.background='#FEF2F2'; this.style.color='#EF4444';" onmouseout="this.style.background='transparent'; this.style.color='var(--text-light)';" title="Hapus Zona">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Zone Delete Modal -->
    <div id="zoneDeleteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: #fff; border-radius: 16px; padding: 28px; width: 100%; max-width: 400px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); animation: modalFadeIn 0.3s ease;">
            <div style="width: 56px; height: 56px; border-radius: 50%; background: #FEF2F2; color: #EF4444; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
            </div>
            <h3 style="font-size: 19px; font-weight: 600; color: var(--text-main); margin-bottom: 8px;">Hapus Zona</h3>
            <p id="zoneDeleteModalText" style="font-size: 14px; color: var(--text-muted); margin-bottom: 24px; line-height: 1.5;">Apakah Anda yakin ingin menghapus zona ini?</p>
            <div style="display: flex; justify-content: center; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeZoneDeleteModal()">Batal</button>
                <button type="button" class="btn btn-danger" onclick="confirmZoneDelete()">Ya, Hapus</button>
            </div>
        </div>
    </div>

    <!-- New Customers -->
    <div class="card" style="border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 0;">
        <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 0;">
            <h3 class="card-title" style="font-size: 17px;">Registrasi Pelanggan Terbaru</h3>
            <a href="{{ route('customers.create') }}" class="btn btn-secondary" style="padding: 6px 14px; font-size: 14px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
                Tambah Pelanggan
            </a>
        </div>
        @if($latestCustomers->isEmpty())
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="var(--border-color)" style="margin-bottom: 12px;"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                <p style="font-size: 14px;">Belum ada pelanggan baru.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th>NAMA & TELEPON</th>
                            <th>ZONA</th>
                            <th>TERDAFTAR PADA</th>
                            <th style="text-align: right;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($latestCustomers as $customer)
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 12px;">
                                    <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(59, 130, 246, 0.1); color: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">
                                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div style="font-weight: 600; color: var(--text-main); font-size: 15px;">{{ $customer->name }}</div>
                                        <div style="font-family: monospace; font-size: 13px; color: var(--text-muted); margin-top: 2px;">{{ $customer->phone }}</div>
                                    </div>
                                </div>
                            </td>
                            <td><span style="font-size: 14px; color: var(--text-secondary); font-weight: 500;">{{ ucfirst($customer->zone ?? '-') }}</span></td>
                            <td style="color: var(--text-muted); font-size: 14px;">{{ $customer->created_at ? $customer->created_at->diffForHumans() : '-' }}</td>
                            <td style="text-align: right;">
                                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-secondary" style="padding: 4px 10px; font-size: 13px;" title="Edit">
                                    Edit
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

@else
    <!-- Specific Zone List SaaS Style -->
    <div class="card" style="border: 1px solid var(--border-color); box-shadow: 0 1px 3px rgba(0,0,0,0.04); margin-bottom: 0;">
        <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 0; display: flex; flex-wrap: wrap; gap: 16px; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 32px; height: 32px; border-radius: 6px; background: rgba(59, 130, 246, 0.1); color: var(--accent); display: flex; align-items: center; justify-content: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                </div>
                <h3 class="card-title" style="font-size: 17px; margin: 0;">Pelanggan: {{ ucfirst($selectedZone) }}</h3>
            </div>
            
            <form method="GET" action="{{ route('customers.index') }}" style="display: flex; gap: 8px; align-items: center;">
                <input type="hidden" name="zone" value="{{ $selectedZone }}">
                <div style="position: relative;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-light);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari nama atau telepon..." class="form-control" style="padding: 6px 12px 6px 32px; width: 220px; font-size: 14px;">
                </div>
                <button type="submit" class="btn btn-primary" style="padding: 6px 14px; font-size: 14px;">Cari</button>
                @if($search)
                    <a href="{{ route('customers.index', ['zone' => $selectedZone]) }}" class="btn btn-secondary" style="padding: 6px; display: flex; align-items: center; justify-content: center;" title="Hapus Filter">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th style="width: 50px;">NO</th>
                        <th>NAMA PELANGGAN</th>
                        <th>ALAMAT</th>
                        <th>NO. TELEPON</th>
                        <th style="text-align: center; width: 100px;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr style="transition: background 0.15s;" onmouseover="this.style.background='var(--bg-body)'" onmouseout="this.style.background='transparent'">
                        <td><span style="color: var(--text-muted); font-size: 14px;">{{ $loop->iteration + ($customers->currentPage() - 1) * $customers->perPage() }}</span></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 32px; height: 32px; border-radius: 8px; background: rgba(59, 130, 246, 0.1); color: var(--accent); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">
                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                                </div>
                                <span style="font-weight: 600; color: var(--text-main); font-size: 15px;">{{ $customer->name }}</span>
                            </div>
                        </td>
                        <td style="color: var(--text-muted); font-size: 14px;">{{ $customer->address ?? '-' }}</td>
                        <td style="font-family: monospace; font-size: 14px; color: var(--text-secondary);">{{ $customer->phone }}</td>
                        <td style="text-align: center;">
                            <div style="display: flex; justify-content: center; gap: 6px;">
                                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-secondary" style="padding: 4px 10px; font-size: 13px;">
                                    Edit
                                </a>
                                <form action="{{ route('customers.destroy', $customer) }}" method="POST" data-customer-name="{{ $customer->name }}" onsubmit="event.preventDefault(); showCustomerDeleteModal(this);" style="margin: 0;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" style="padding: 4px 10px; font-size: 13px; background: #FEF2F2; color: #EF4444; border: 1px solid #FECACA; box-shadow: none;" onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">
                            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="var(--border-color)" style="margin-bottom: 12px;"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                <p style="font-size: 14px;">Tidak ada data pelanggan ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
        <div style="padding: 16px; border-top: 1px solid var(--border-light);">
            {{ $customers->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
@endif

<!-- Customer Delete Modal -->
<div id="customerDeleteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #fff; border-radius: 16px; padding: 28px; width: 100%; max-width: 400px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: #FEF2F2; color: #EF4444; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        </div>
        <h3 style="font-size: 19px; font-weight: 600; color: var(--text-main); margin-bottom: 8px;">Hapus Pelanggan</h3>
        <p id="customerDeleteModalText" style="font-size: 14px; color: var(--text-muted); margin-bottom: 24px; line-height: 1.5;">Apakah Anda yakin?</p>
        <div style="display: flex; justify-content: center; gap: 10px;">
            <button type="button" class="btn btn-secondary" onclick="closeCustomerDeleteModal()">Batal</button>
            <button type="button" class="btn btn-danger" onclick="confirmCustomerDelete()">Ya, Hapus</button>
        </div>
    </div>
</div>

<style>
    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
    let currentZoneDeleteForm = null;
    let currentCustomerDeleteForm = null;
    const zoneMapPoints = @json($zoneMapPoints ?? []);
    const isZoneSelected = @json((bool) $selectedZone);
    let baliZoneMapInstance = null;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function initializeBaliZoneMap() {
        if (isZoneSelected) return;
        if (!Array.isArray(zoneMapPoints) || zoneMapPoints.length === 0) return;
        if (typeof L === 'undefined') return;

        const mapElement = document.getElementById('baliZoneMap');
        const legendElement = document.getElementById('zoneMapLegend');
        if (!mapElement || baliZoneMapInstance) return;

        baliZoneMapInstance = L.map(mapElement, {
            scrollWheelZoom: false,
        }).setView([-8.4095, 115.1889], 9);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(baliZoneMapInstance);

        const bounds = [];

        zoneMapPoints.forEach((point) => {
            const markerIcon = L.divIcon({
                className: 'zone-pin-wrap',
                html: `<span class="zone-pin" style="--pin-color: ${point.color};"></span>`,
                iconSize: [14, 14],
                iconAnchor: [7, 7],
            });

            const marker = L.marker([point.lat, point.lng], { icon: markerIcon }).addTo(baliZoneMapInstance);
            const safeName = escapeHtml(point.name);
            marker.bindPopup(`
                <div>
                    <div class="zone-map-popup-title">${safeName}</div>
                    <div class="zone-map-popup-meta">${point.customersCount} pelanggan</div>
                    <a href="${point.url}" class="zone-map-popup-link">Lihat pelanggan</a>
                </div>
            `);

            bounds.push([point.lat, point.lng]);
        });

        if (bounds.length === 1) {
            baliZoneMapInstance.setView(bounds[0], 11);
        } else {
            baliZoneMapInstance.fitBounds(bounds, { padding: [20, 20] });
        }

        if (legendElement) {
            legendElement.innerHTML = zoneMapPoints
                .map((point) => {
                    const safeName = escapeHtml(point.name);
                    return `
                        <a href="${point.url}" class="zone-map-legend-item">
                            <span class="zone-map-dot" style="background: ${point.color};"></span>
                            <span>${safeName} (${point.customersCount})</span>
                        </a>
                    `;
                })
                .join('');
        }
    }

    function showZoneDeleteModal(formElement) {
        const modal = document.getElementById('zoneDeleteModal');
        const modalText = document.getElementById('zoneDeleteModalText');
        currentZoneDeleteForm = formElement;
        if (modalText) {
            const zoneName = formElement.getAttribute('data-zone-name') || 'ini';
            modalText.textContent = `Apakah Anda yakin ingin menghapus zona ${zoneName}? Tindakan ini tidak dapat dibatalkan.`;
        }
        modal.style.display = 'flex';
    }

    function closeZoneDeleteModal() {
        document.getElementById('zoneDeleteModal').style.display = 'none';
        currentZoneDeleteForm = null;
    }

    function confirmZoneDelete() {
        if (currentZoneDeleteForm) currentZoneDeleteForm.submit();
    }

    function showCustomerDeleteModal(formElement) {
        const modal = document.getElementById('customerDeleteModal');
        const modalText = document.getElementById('customerDeleteModalText');
        currentCustomerDeleteForm = formElement;
        if (modalText) {
            const customerName = formElement.getAttribute('data-customer-name') || 'ini';
            modalText.textContent = `Apakah Anda yakin ingin menghapus pelanggan ${customerName}? Tindakan ini tidak dapat dibatalkan.`;
        }
        if (modal) modal.style.display = 'flex';
    }

    function closeCustomerDeleteModal() {
        const modal = document.getElementById('customerDeleteModal');
        if (modal) {
            modal.style.display = 'none';
            currentCustomerDeleteForm = null;
        }
    }

    function confirmCustomerDelete() {
        if (currentCustomerDeleteForm) currentCustomerDeleteForm.submit();
    }

    window.addEventListener('load', initializeBaliZoneMap);
</script>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
@endpush
