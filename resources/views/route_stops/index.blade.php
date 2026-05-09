@extends('layouts.dashboard')
@section('title')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<style>
    .route-stop-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        background-color: #054dc6ff;
        color: white;
    }
    .route-stop-map {
        height: 380px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        overflow: hidden;
        margin-bottom: 20px;
    }
    .stop-row td { vertical-align: middle; }
    .order-badge {
        display: inline-flex;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 12px;
        color: #fff;
        background: linear-gradient(135deg, #2563EB, #1a28ecff);
        flex-shrink: 0;
    }
    .stop-actions { display: flex; gap: 6px; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Urutan Jalur — {{ $zone->name }}</h1>
        <p class="page-subtitle">Kelola daftar jalur pengiriman dalam zona ini beserta urutannya</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="{{ route('route-stops.create', $zone) }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Jalur
        </a>
        <a href="{{ route('customers.index') }}" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19L5 12L12 5"/></svg>
            Kembali
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom: 16px;">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger" style="margin-bottom: 16px;">{{ session('error') }}</div>
@endif

{{-- Peta semua jalur --}}
<div class="card" style="margin-bottom: 20px; padding: 20px;">
    <div style="font-weight: 600; font-size: 14px; color: var(--text-main); margin-bottom: 12px;">
        🗺️ Peta Urutan Jalur
    </div>
    <div id="routeStopsMap" class="route-stop-map"></div>
</div>

{{-- Daftar jalur --}}
<div class="card">
    @if($routeStops->isEmpty())
        <div style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 12px; opacity: 0.4;"><path d="M3 12h18"/><path d="M8 8l-5 4 5 4"/><path d="M16 8l5 4-5 4"/></svg>
            <p>Belum ada jalur di zona ini.</p>
            <a href="{{ route('route-stops.create', $zone) }}" class="btn btn-primary" style="margin-top: 8px;">Tambah Jalur Pertama</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 60px;">Urutan</th>
                        <th>Nama Jalur</th>
                        <th>Koordinat</th>
                        <th style="width: 120px;">Radius</th>
                        <th style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($routeStops as $stop)
                    <tr class="stop-row">
                        <td>
                            <span class="order-badge">{{ $stop->order_index }}</span>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--text-main);">{{ $stop->name }}</div>
                        </td>
                        <td>
                            <div style="font-size: 12px; color: var(--text-muted);">
                                {{ number_format($stop->latitude, 5) }}, {{ number_format($stop->longitude, 5) }}
                            </div>
                        </td>
                        <td>
                            <span class="route-stop-badge">{{ number_format($stop->radius_meters) }} m</span>
                        </td>
                        <td>
                            <div class="stop-actions">
                                <a href="{{ route('route-stops.edit', [$zone, $stop]) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 12px;">Edit</a>
                                <form method="POST" action="{{ route('route-stops.destroy', [$zone, $stop]) }}" onsubmit="return confirm('Hapus jalur {{ $stop->name }}?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn" style="padding: 6px 12px; font-size: 12px; background: #FEE2E2; color: #DC2626; border: none; border-radius: 8px; cursor: pointer;">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
(function () {
    const mapEl = document.getElementById('routeStopsMap');
    if (!mapEl || typeof L === 'undefined') return;

    const zoneLat  = {{ $zone->latitude ?? -8.6704589 }};
    const zoneLng  = {{ $zone->longitude ?? 115.2126293 }};

    const map = L.map(mapEl).setView([zoneLat, zoneLng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);

    // Marker zona utama
    L.circleMarker([zoneLat, zoneLng], {
        radius: 8, color: '#94A3B8', fillColor: '#94A3B8', fillOpacity: 0.5, weight: 2
    }).addTo(map).bindPopup('<b>Pusat Zona: {{ $zone->name }}</b>');

    const stops = @json($routeStops->values());
    const latlngs = [];

    stops.forEach(function (stop) {
        const color = '#2563EB';
        const lat = parseFloat(stop.latitude);
        const lng = parseFloat(stop.longitude);

        // Lingkaran radius
        L.circle([lat, lng], {
            radius: stop.radius_meters,
            color: color,
            fillColor: color,
            fillOpacity: 0.08,
            weight: 1.5,
        }).addTo(map);

        // Marker bernomor
        const icon = L.divIcon({
            className: '',
            html: `<div style="background:${color};color:#fff;width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;box-shadow:0 2px 6px rgba(0,0,0,.25);">${stop.order_index}</div>`,
            iconSize: [28, 28],
            iconAnchor: [14, 14],
        });

        L.marker([lat, lng], { icon })
            .addTo(map)
            .bindPopup(`<b>${stop.order_index}. ${stop.name}</b><br>Radius: ${stop.radius_meters} m`);

        latlngs.push([lat, lng]);
    });

    // Garis penghubung antar jalur
    if (latlngs.length > 1) {
        L.polyline(latlngs, { color: '#2563EB', weight: 2, dashArray: '6 4', opacity: 0.6 }).addTo(map);
    }

    if (latlngs.length > 0) {
        map.fitBounds(latlngs, { padding: [40, 40] });
    }
})();
</script>
@endpush
