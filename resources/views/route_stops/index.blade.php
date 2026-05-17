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
        <a href="{{ route('route-stops.create', array_merge([$zone], request()->only(['hint_route_name', 'hint_customer_name', 'hint_zone_name', 'hint_latitude', 'hint_longitude']))) }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Tambah Jalur
        </a>
        <a href="{{ route('customers.index') }}" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5"/><path d="M12 19L5 12L12 5"/></svg>
            Kembali
        </a>
    </div>
</div>

<!-- @if(session('success'))
    <div class="alert alert-success" style="margin-bottom: 16px;">{{ session('success') }}</div>
@endif -->
@if(session('error'))
    <div class="alert alert-danger" style="margin-bottom: 16px;">{{ session('error') }}</div>
@endif

{{-- Peta semua jalur --}}
<div class="card" style="margin-bottom: 20px; padding: 20px;">
    <div style="font-weight: 600; font-size: 14px; color: var(--text-main); margin-bottom: 12px;">
        🗺️ Peta Urutan Jalur
    </div>
    <!-- <div id="routeHintNote" class="route-hint-note" style="display: none;">
        <div>📍</div>
        <div>
            <strong>Hint lokasi customer</strong>
            <div id="routeHintNoteText"></div>
        </div>
    </div> -->
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
                                <form method="POST" action="{{ route('route-stops.destroy', [$zone, $stop]) }}"
                                    data-stop-name="{{ $stop->name }}"
                                    onsubmit="event.preventDefault(); showRouteStopDeleteModal(this);">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn" style="padding: 6px 12px; font-size: 12px; background: #FEF2F2; color: #DC2626; border: 1px solid #FECACA; border-radius: 8px; cursor: pointer;" onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'">Hapus</button>
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

{{-- Modal Konfirmasi Hapus Jalur --}}
<div id="routeStopDeleteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #fff; border-radius: 16px; padding: 28px; width: 100%; max-width: 400px; text-align: center; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); animation: modalFadeIn 0.25s ease; margin: 16px;">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: #FEF2F2; color: #EF4444; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
        </div>
        <h3 style="font-size: 19px; font-weight: 600; color: var(--text-main); margin-bottom: 8px;">Hapus Jalur</h3>
        <p id="routeStopDeleteModalText" style="font-size: 14px; color: var(--text-muted); margin-bottom: 24px; line-height: 1.5;">Apakah Anda yakin ingin menghapus jalur ini?</p>
        <div style="display: flex; justify-content: center; gap: 10px;">
            <button type="button" class="btn btn-secondary" onclick="closeRouteStopDeleteModal()">Batal</button>
            <button type="button" class="btn btn-danger" onclick="confirmRouteStopDelete()" style="background: #EF4444; color: #fff; border: none;">Ya, Hapus</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<style>
    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(-16px) scale(0.97); }
        to   { opacity: 1; transform: translateY(0)    scale(1); }
    }
</style>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
@php
    $routeHintPayload = [
        'route_name' => request('hint_route_name'),
        'customer_name' => request('hint_customer_name'),
        'zone_name' => request('hint_zone_name', $zone->name),
        'latitude' => request()->filled('hint_latitude') ? (float) request('hint_latitude') : null,
        'longitude' => request()->filled('hint_longitude') ? (float) request('hint_longitude') : null,
    ];
@endphp
<script>
    const routeHint = @json($routeHintPayload);

    // ── Modal Hapus Jalur ──────────────────────────────────────────────
    let _routeStopDeleteForm = null;

    function showRouteStopDeleteModal(formEl) {
        const modal    = document.getElementById('routeStopDeleteModal');
        const modalTxt = document.getElementById('routeStopDeleteModalText');
        _routeStopDeleteForm = formEl;
        if (modalTxt) {
            const name = formEl.getAttribute('data-stop-name') || 'ini';
            modalTxt.textContent =
                `Apakah Anda yakin ingin menghapus jalur "${name}"? Tindakan ini tidak dapat dibatalkan.`;
        }
        if (modal) modal.style.display = 'flex';
    }

    function closeRouteStopDeleteModal() {
        const modal = document.getElementById('routeStopDeleteModal');
        if (modal) modal.style.display = 'none';
        _routeStopDeleteForm = null;
    }

    function confirmRouteStopDelete() {
        if (_routeStopDeleteForm) _routeStopDeleteForm.submit();
    }

    // Tutup modal saat klik backdrop
    document.getElementById('routeStopDeleteModal')
        .addEventListener('click', function (e) {
            if (e.target === this) closeRouteStopDeleteModal();
        });

(function () {
    const mapEl = document.getElementById('routeStopsMap');
    if (!mapEl || typeof L === 'undefined') return;

    const hintNote = document.getElementById('routeHintNote');
    const hintNoteText = document.getElementById('routeHintNoteText');

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

    async function renderRouteHint() {
        const routeName = (routeHint.route_name || '').trim();
        const customerName = (routeHint.customer_name || '').trim();
        const zoneName = (routeHint.zone_name || '{{ $zone->name }}').trim();

        if (!routeName) {
            return;
        }

        let hintPosition = null;
        let hintLabel = '';

        if (Number.isFinite(routeHint.latitude) && Number.isFinite(routeHint.longitude)) {
            hintPosition = { lat: routeHint.latitude, lng: routeHint.longitude };
            hintLabel = `${customerName || 'Customer'} · ${routeName}`;
        } else {
            const geocoded = await geocodeRouteHint(routeName, zoneName);
            if (geocoded) {
                hintPosition = geocoded;
                hintLabel = `${customerName || 'Customer'} · ${routeName}`;
            }
        }

        if (!hintPosition) {
            setRouteHintNote(`Lokasi estimasi untuk jalan ${routeName} tidak ditemukan otomatis. Silakan cari dan pasang pin secara manual.`);
            return;
        }

        const hintText = customerName
            ? `Customer ${customerName} diperkirakan berada di jalan ${routeName}${zoneName ? `, zona ${zoneName}` : ''}.`
            : `Lokasi estimasi untuk jalan ${routeName}${zoneName ? `, zona ${zoneName}` : ''}.`;
        setRouteHintNote(hintText);

        const marker = L.marker([hintPosition.lat, hintPosition.lng], { icon: hintIcon })
            .addTo(map)
            .bindPopup(`<b>Hint lokasi customer</b><br>${hintLabel || routeName}<br><span style="color:#64748B;">Gunakan ini sebagai acuan saat menaruh pin jalur baru.</span>`);

        L.circle([hintPosition.lat, hintPosition.lng], {
            radius: 350,
            color: '#F59E0B',
            weight: 1.5,
            dashArray: '6 6',
            fillColor: '#FDE68A',
            fillOpacity: 0.12,
        }).addTo(map);

        marker.openPopup();

        if (latlngs.length > 0) {
            const bounds = L.latLngBounds([...latlngs, [hintPosition.lat, hintPosition.lng]]);
            map.fitBounds(bounds, { padding: [40, 40] });
        } else {
            map.setView([hintPosition.lat, hintPosition.lng], 15);
        }
    }

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

    renderRouteHint();
})();
</script>
@endpush
