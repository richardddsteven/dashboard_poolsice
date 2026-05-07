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
        <h1 class="page-title">Edit Pelanggan</h1>
        <p class="page-subtitle">Perbarui data pelanggan</p>
    </div>
    <a href="{{ route('customers.index', $customer->zone ? ['zone' => $customer->zone] : []) }}" class="btn btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19L5 12L12 5"/></svg>
        Kembali
    </a>
</div>

<div class="card">
    @if($errors->any())
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <ul style="margin: 0; padding-left: 16px;">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif
    <form method="POST" action="{{ route('customers.update', $customer) }}">
        @csrf @method('PUT')
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div>
                <label for="name" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Nama Pelanggan <span style="color: #EF4444;">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $customer->name) }}" required>
            </div>
            <div>
                <label for="phone" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">No. Telepon <span style="color: #EF4444;">*</span></label>
                <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', $customer->phone) }}" required>
            </div>
            <div>
                <label for="address" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Alamat</label>
                <textarea id="address" name="address" class="form-control" rows="3">{{ old('address', $customer->address) }}</textarea>
            </div>
            <div>
                <label for="zone" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Zona <span style="color: #EF4444;">*</span></label>
                <div class="custom-select-wrapper" id="zoneSelectWrapper">
                    <div class="custom-select-trigger" onclick="document.getElementById('zoneSelectWrapper').classList.toggle('open')">
                        <span id="zoneSelectText">{{ ucfirst(old('zone', $customer->zone)) ?: 'Pilih zona' }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
                    </div>
                    <div class="custom-options">
                        @foreach($zones as $zone)
                            <div class="custom-option {{ old('zone', $customer->zone) == $zone->name ? 'selected' : '' }}" data-value="{{ $zone->name }}" onclick="selectZone(this)">{{ ucfirst($zone->name) }}</div>
                        @endforeach
                    </div>
                    <input type="hidden" name="zone" id="zoneInput" value="{{ old('zone', $customer->zone) }}">
                </div>
            </div>
            <div>
                <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Titik Lokasi Pelanggan (Opsional)</label>
                <div id="customerPickerMap" class="zone-picker-map"></div>
                <p class="zone-help-text">Klik peta untuk meletakkan pin pada alamat yang sesuai, atau biarkan kosong.</p>
            </div>
            <div class="zone-coordinates-grid">
                <div>
                    <label for="latitude" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Latitude</label>
                    <input type="number" step="0.0000001" id="latitude" name="latitude" class="form-control" value="{{ old('latitude', $customer->latitude) }}">
                </div>
                <div>
                    <label for="longitude" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Longitude</label>
                    <input type="number" step="0.0000001" id="longitude" name="longitude" class="form-control" value="{{ old('longitude', $customer->longitude) }}">
                </div>
            </div>
            <div style="padding-top: 8px;">
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </div>
    </form>
</div>
<script>
function selectZone(el) {
    document.getElementById('zoneInput').value = el.dataset.value;
    document.getElementById('zoneSelectText').textContent = el.textContent.trim();
    document.querySelectorAll('#zoneSelectWrapper .custom-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('zoneSelectWrapper').classList.remove('open');
}
document.addEventListener('click', function(e) { const w = document.getElementById('zoneSelectWrapper'); if (w && !w.contains(e.target)) w.classList.remove('open'); });
</script>
@endsection

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    (function () {
        const latInput = document.getElementById('latitude');
        const lngInput = document.getElementById('longitude');
        const mapElement = document.getElementById('customerPickerMap');

        if (!latInput || !lngInput || !mapElement || typeof L === 'undefined') {
            return;
        }

        const initialLat = Number(latInput.value) || -7.2504;
        const initialLng = Number(lngInput.value) || 112.7688;

        const map = L.map(mapElement).setView([initialLat, initialLng], latInput.value ? 16 : 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        let marker = null;
        if (latInput.value && lngInput.value) {
            marker = L.marker([initialLat, initialLng], { draggable: true }).addTo(map);
            setupMarker(marker);
        }

        function syncInputs(latlng) {
            latInput.value = Number(latlng.lat).toFixed(7);
            lngInput.value = Number(latlng.lng).toFixed(7);
        }

        function setupMarker(m) {
            m.on('dragend', function () {
                syncInputs(m.getLatLng());
            });
        }

        map.on('click', function (event) {
            if (!marker) {
                marker = L.marker(event.latlng, { draggable: true }).addTo(map);
                setupMarker(marker);
            } else {
                marker.setLatLng(event.latlng);
            }
            syncInputs(event.latlng);
        });

        const updateMapFromInputs = function () {
            const lat = Number(latInput.value);
            const lng = Number(lngInput.value);
            if (Number.isFinite(lat) && Number.isFinite(lng)) {
                if (!marker) {
                    marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                    setupMarker(marker);
                } else {
                    marker.setLatLng([lat, lng]);
                }
                map.panTo([lat, lng]);
            }
        };

        latInput.addEventListener('change', updateMapFromInputs);
        lngInput.addEventListener('change', updateMapFromInputs);
    })();
</script>
@endpush