@extends('layouts.dashboard')
@section('title')

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
<style>
    .zone-picker-map {
        height: 320px;
        border-radius: 12px;
        border: 1px solid var(--border-color);
        overflow: hidden;
        background: #F8FAFC;
    }

    .zone-help-text {
        margin-top: 8px;
        font-size: 13px;
        color: var(--text-muted);
    }

    .zone-coordinates-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    @media (max-width: 768px) {
        .zone-coordinates-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Zona</h1>
        <p class="page-subtitle">Perbarui nama zona wilayah</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="{{ route('route-stops.index', $zone) }}" class="btn btn-primary" style="font-size: 13px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18"/><path d="M8 8l-5 4 5 4"/><path d="M16 8l5 4-5 4"/></svg>
            Kelola Jalur
        </a>
        <a href="{{ route('customers.index') }}" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19L5 12L12 5"/></svg>
            Kembali
        </a>
    </div>
</div>

<div class="card">
    @if($errors->any())
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 16px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <form method="POST" action="{{ route('zones.update', $zone) }}">
        @csrf @method('PUT')
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div>
                <label for="name" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Nama Zona <span style="color: #EF4444;">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $zone->name) }}" required>
            </div>
            <div>
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Titik Zona di Peta <span style="color: #EF4444;">*</span></label>
                <div id="zonePickerMap" class="zone-picker-map"></div>
                <p class="zone-help-text">Klik peta untuk memindahkan titik zona, atau drag marker lalu simpan perubahan.</p>
            </div>
            <div class="zone-coordinates-grid">
                <div>
                    <label for="latitude" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Latitude <span style="color: #EF4444;">*</span></label>
                    <input type="number" step="0.0000001" id="latitude" name="latitude" class="form-control" value="{{ old('latitude', $zone->latitude ?? -8.6704589) }}" required>
                </div>
                <div>
                    <label for="longitude" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Longitude <span style="color: #EF4444;">*</span></label>
                    <input type="number" step="0.0000001" id="longitude" name="longitude" class="form-control" value="{{ old('longitude', $zone->longitude ?? 115.2126293) }}" required>
                </div>
            </div>
            <div style="padding-top: 8px;">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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
        const mapElement = document.getElementById('zonePickerMap');

        if (!latInput || !lngInput || !mapElement || typeof L === 'undefined') {
            return;
        }

        const initialLat = Number(latInput.value || -8.6704589);
        const initialLng = Number(lngInput.value || 115.2126293);

        const map = L.map(mapElement).setView([initialLat, initialLng], 10);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        const marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);

        function syncInputs(latlng) {
            latInput.value = Number(latlng.lat).toFixed(7);
            lngInput.value = Number(latlng.lng).toFixed(7);
        }

        map.on('click', function (event) {
            marker.setLatLng(event.latlng);
            syncInputs(event.latlng);
        });

        marker.on('dragend', function () {
            syncInputs(marker.getLatLng());
        });

        latInput.addEventListener('change', function () {
            const lat = Number(latInput.value);
            const lng = Number(lngInput.value);
            if (Number.isFinite(lat) && Number.isFinite(lng)) {
                marker.setLatLng([lat, lng]);
                map.panTo([lat, lng]);
            }
        });

        lngInput.addEventListener('change', function () {
            const lat = Number(latInput.value);
            const lng = Number(lngInput.value);
            if (Number.isFinite(lat) && Number.isFinite(lng)) {
                marker.setLatLng([lat, lng]);
                map.panTo([lat, lng]);
            }
        });
    })();
</script>
@endpush
