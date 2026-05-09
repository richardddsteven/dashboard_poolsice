@extends('layouts.dashboard')
@section('title', 'Tambah Jalur - ' . $zone->name)

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<style>
    .stop-picker-map { height: 300px; border-radius: 12px; border: 1px solid var(--border-color); overflow: hidden; }
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    @media (max-width: 768px) { .form-grid, .form-grid-3 { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Tambah Jalur</h1>
        <p class="page-subtitle">Zona: <strong>{{ $zone->name }}</strong></p>
    </div>
    <a href="{{ route('route-stops.index', $zone) }}" class="btn btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19L5 12L12 5"/></svg>
        Kembali
    </a>
</div>

<div class="card">
    @if($errors->any())
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 16px;">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('route-stops.store', $zone) }}">
        @csrf
        <div style="display: flex; flex-direction: column; gap: 20px;">

            {{-- Nama & Urutan --}}
            <div class="form-grid">
                <div>
                    <label for="name" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">
                        Nama Jalur <span style="color: #EF4444;">*</span>
                    </label>
                    <input type="text" id="name" name="name" class="form-control"
                        value="{{ old('name') }}"
                        placeholder="Contoh: Jalur A, Jalur Pantai, dsb."
                        required>
                </div>
                <div>
                    <label for="order_index" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">
                        Urutan Perjalanan <span style="color: #EF4444;">*</span>
                    </label>
                    <input type="number" id="order_index" name="order_index" class="form-control"
                        value="{{ old('order_index', $nextIndex) }}"
                        min="1" required>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Supir melewati jalur berurutan dari nomor terkecil.</p>
                </div>
            </div>

            {{-- Peta --}}
            <div>
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">
                    Titik Pusat Jalur di Peta <span style="color: #EF4444;">*</span>
                </label>
                <div id="stopPickerMap" class="stop-picker-map"></div>
                <p style="font-size: 12px; color: var(--text-muted); margin-top: 6px;">Klik pada peta atau drag marker untuk menentukan pusat jalur.</p>
            </div>

            {{-- Koordinat & Radius --}}
            <div class="form-grid-3">
                <div>
                    <label for="latitude" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">
                        Latitude <span style="color: #EF4444;">*</span>
                    </label>
                    <input type="number" step="0.0000001" id="latitude" name="latitude" class="form-control"
                        value="{{ old('latitude', $zone->latitude ?? -8.6704589) }}" required>
                </div>
                <div>
                    <label for="longitude" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">
                        Longitude <span style="color: #EF4444;">*</span>
                    </label>
                    <input type="number" step="0.0000001" id="longitude" name="longitude" class="form-control"
                        value="{{ old('longitude', $zone->longitude ?? 115.2126293) }}" required>
                </div>
                <div>
                    <label for="radius_meters" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">
                        Radius (meter) <span style="color: #EF4444;">*</span>
                    </label>
                    <input type="number" id="radius_meters" name="radius_meters" class="form-control"
                        value="{{ old('radius_meters', 500) }}" min="50" max="10000" required>
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">Cakupan area jalur. Umumnya 300–800 m.</p>
                </div>
            </div>

            <div style="padding-top: 8px;">
                <button type="submit" class="btn btn-primary">Simpan Jalur</button>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
(function () {
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const radiusInput = document.getElementById('radius_meters');
    const mapEl = document.getElementById('stopPickerMap');
    if (!latInput || !lngInput || !mapEl || typeof L === 'undefined') return;

    let lat = Number(latInput.value) || {{ $zone->latitude ?? -8.6704589 }};
    let lng = Number(lngInput.value) || {{ $zone->longitude ?? 115.2126293 }};
    let radius = Number(radiusInput.value) || 500;

    const map = L.map(mapEl).setView([lat, lng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

    // Existing stops
    @foreach(\App\Models\RouteStop::where('zone_id', $zone->id)->orderBy('order_index')->get() as $s)
    L.circleMarker([{{ $s->latitude }}, {{ $s->longitude }}], {
        radius: 6, color: '#94A3B8', fillColor: '#94A3B8', fillOpacity: 0.7, weight: 2
    }).addTo(map).bindPopup('<b>{{ $s->order_index }}. {{ addslashes($s->name) }}</b>');
    @endforeach

    const marker = L.marker([lat, lng], { draggable: true }).addTo(map);
    let radiusCircle = L.circle([lat, lng], { radius, color: '#2563EB', fillColor: '#2563EB', fillOpacity: 0.1, weight: 1.5 }).addTo(map);

    function sync(latlng, r) {
        latInput.value = Number(latlng.lat).toFixed(7);
        lngInput.value = Number(latlng.lng).toFixed(7);
        radiusCircle.setLatLng(latlng);
    }

    map.on('click', function (e) { marker.setLatLng(e.latlng); sync(e.latlng, radius); });
    marker.on('dragend', function () { sync(marker.getLatLng(), radius); });
    radiusInput.addEventListener('input', function () {
        radius = Number(radiusInput.value) || 500;
        radiusCircle.setRadius(radius);
    });
    latInput.addEventListener('change', function () {
        const ll = L.latLng(Number(latInput.value), Number(lngInput.value));
        marker.setLatLng(ll); map.panTo(ll); sync(ll, radius);
    });
    lngInput.addEventListener('change', function () {
        const ll = L.latLng(Number(latInput.value), Number(lngInput.value));
        marker.setLatLng(ll); map.panTo(ll); sync(ll, radius);
    });
})();
</script>
@endpush
