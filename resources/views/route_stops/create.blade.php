@extends('layouts.dashboard')
@section('title', 'Tambah Jalur - ' . $zone->name)

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<style>
    .stop-picker-map { height: 300px; border-radius: 12px; border: 1px solid var(--border-color); overflow: hidden; }
    .route-hint-note {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px 14px;
        margin-bottom: 16px;
        border: 1px solid #FDE68A;
        border-radius: 12px;
        background: #FFFBEB;
        color: #92400E;
        font-size: 13px;
        line-height: 1.5;
    }
    .route-hint-note strong {
        display: block;
        margin-bottom: 2px;
        font-size: 14px;
    }
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
    <a href="{{ route('route-stops.index', array_merge([$zone], request()->only(['hint_route_name', 'hint_customer_name', 'hint_zone_name', 'hint_latitude', 'hint_longitude']))) }}" class="btn btn-secondary">
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

    @php
        $routeHintPayload = [
            'route_name' => request('hint_route_name'),
            'customer_name' => request('hint_customer_name'),
            'zone_name' => request('hint_zone_name', $zone->name),
            'latitude' => request()->filled('hint_latitude') ? (float) request('hint_latitude') : null,
            'longitude' => request()->filled('hint_longitude') ? (float) request('hint_longitude') : null,
        ];
    @endphp

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
                    <p style="font-size: 12px; color: var(--text-muted); margin-top: 4px;">
                        Supir melewati jalur berurutan dari nomor terkecil.<br>
                        <strong>Jika urutan yang diisi sudah ada, jalur-jalur berikutnya akan <strong>otomatis bergeser</strong> ke urutan selanjutnya.</strong>
                    </p>
                </div>
            </div>

            {{-- Peta --}}
            <div>
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">
                    Titik Pusat Jalur di Peta <span style="color: #EF4444;">*</span>
                </label>
                <!-- <div id="routeHintNote" class="route-hint-note" style="display: none;">
                    <div>📍</div>
                    <div>
                        <strong>Hint lokasi customer</strong>
                        <div id="routeHintNoteText"></div>
                    </div>
                </div> -->
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
    const routeHint = @json($routeHintPayload);
    const latInput = document.getElementById('latitude');
    const lngInput = document.getElementById('longitude');
    const radiusInput = document.getElementById('radius_meters');
    const mapEl = document.getElementById('stopPickerMap');
    const hintNote = document.getElementById('routeHintNote');
    const hintNoteText = document.getElementById('routeHintNoteText');
    if (!latInput || !lngInput || !mapEl || typeof L === 'undefined') return;

    let lat = Number(latInput.value) || {{ $zone->latitude ?? -8.6704589 }};
    let lng = Number(lngInput.value) || {{ $zone->longitude ?? 115.2126293 }};
    let radius = Number(radiusInput.value) || 500;

    const map = L.map(mapEl).setView([lat, lng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

    const hintIcon = L.divIcon({
        className: '',
        html: '<div style="width: 18px; height: 18px; border-radius: 50% 50% 50% 0; background: linear-gradient(135deg, #F59E0B, #EA580C); transform: rotate(-45deg); box-shadow: 0 6px 14px rgba(234, 88, 12, 0.28);"></div>',
        iconSize: [18, 18],
        iconAnchor: [9, 18],
        popupAnchor: [0, -16],
    });

    function setRouteHintNote(text) {
        if (!hintNote || !hintNoteText || !text) {
            return;
        }

        hintNoteText.textContent = text;
        hintNote.style.display = 'flex';
    }

    async function geocodeRouteHint(routeName, zoneName) {
        const queryParts = [];
        if (routeName) queryParts.push(routeName);
        if (zoneName) queryParts.push(zoneName);
        queryParts.push('Indonesia');

        const query = queryParts.join(', ');
        const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes=id&accept-language=id&q=${encodeURIComponent(query)}`;

        try {
            const response = await fetch(url, {
                headers: { 'Accept': 'application/json' },
            });

            if (!response.ok) {
                return null;
            }

            const data = await response.json();
            if (!Array.isArray(data) || !data.length || !data[0].lat || !data[0].lon) {
                return null;
            }

            return {
                lat: parseFloat(data[0].lat),
                lng: parseFloat(data[0].lon),
                label: data[0].display_name || query,
            };
        } catch (_) {
            return null;
        }
    }

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

    (async function renderRouteHint() {
        const routeName = (routeHint.route_name || '').trim();
        const customerName = (routeHint.customer_name || '').trim();
        const zoneName = (routeHint.zone_name || '{{ $zone->name }}').trim();

        if (!routeName) {
            return;
        }

        let hintPosition = null;

        if (Number.isFinite(routeHint.latitude) && Number.isFinite(routeHint.longitude)) {
            hintPosition = { lat: routeHint.latitude, lng: routeHint.longitude };
        } else {
            const geocoded = await geocodeRouteHint(routeName, zoneName);
            if (geocoded) {
                hintPosition = geocoded;
            }
        }

        if (!hintPosition) {
            setRouteHintNote(`Lokasi estimasi untuk jalan ${routeName} tidak ditemukan otomatis. Silakan cari dan geser pin ke area yang sesuai.`);
            return;
        }

        const hintText = customerName
            ? `Customer ${customerName} diperkirakan berada di jalan ${routeName}${zoneName ? `, zona ${zoneName}` : ''}.`
            : `Lokasi estimasi untuk jalan ${routeName}${zoneName ? `, zona ${zoneName}` : ''}.`;
        setRouteHintNote(hintText);

        const hintMarker = L.marker([hintPosition.lat, hintPosition.lng], { icon: hintIcon, interactive: true }).addTo(map);
        L.circle([hintPosition.lat, hintPosition.lng], {
            radius: 350,
            color: '#F59E0B',
            weight: 1.5,
            dashArray: '6 6',
            fillColor: '#FDE68A',
            fillOpacity: 0.12,
        }).addTo(map);

        hintMarker.bindPopup(`<b>Hint lokasi customer</b><br>${customerName || 'Customer'}<br>${routeName}`);
        hintMarker.openPopup();

        map.panTo([hintPosition.lat, hintPosition.lng]);
    })();
})();
</script>
@endpush
