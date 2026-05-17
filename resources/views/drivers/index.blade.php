@extends('layouts.dashboard')

@section('title')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Supir</h1>
        <p class="page-subtitle">Manajemen data supir</p>
    </div>
    <a href="{{ route('drivers.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        Tambah Supir
    </a>
</div>

<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 14px;">
        <h3 class="card-title">Daftar Supir</h3>
        <form action="{{ route('drivers.index') }}" method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <div style="position: relative;">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-light);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau telepon..." class="form-control" style="padding-left: 36px; width: 240px;">
            </div>
            <div style="min-width: 160px;">
                <div class="custom-select-wrapper" id="zoneFilterSelectWrapper">
                    <div class="custom-select-trigger" onclick="toggleZoneFilterSelect()">
                        @php
                            $selectedZoneName = 'Semua Zona';
                            if(request('zone_id')) {
                                $selectedZoneObj = $zones->firstWhere('id', request('zone_id'));
                                if($selectedZoneObj) $selectedZoneName = $selectedZoneObj->name;
                            }
                        @endphp
                        <span id="zoneFilterSelectText" class="{{ request('zone_id') ? '' : 'text-placeholder' }}">{{ $selectedZoneName }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
                    </div>
                    <div class="custom-options">
                        <div class="custom-option {{ request('zone_id') == '' ? 'selected' : '' }}" data-value="" onclick="selectZoneFilterOption(this)">Semua Zona</div>
                        @foreach($zones as $zone)
                            <div class="custom-option {{ request('zone_id') == $zone->id ? 'selected' : '' }}" data-value="{{ $zone->id }}" onclick="selectZoneFilterOption(this)">{{ $zone->name }}</div>
                        @endforeach
                    </div>
                    <input type="hidden" name="zone_id" id="zoneFilterInput" value="{{ request('zone_id') }}">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Cari</button>
            @if(request('search') || request('zone_id'))
                <a href="{{ route('drivers.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>
    </div>
    @if($drivers->isEmpty())
        <div style="text-align: center; padding: 40px; color: var(--text-muted); font-size: 14px;">Belum ada data supir.</div>
    @else
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>No</th><th>Nama Supir</th><th>Username</th><th>No Telepon</th><th>Zona</th><th>Lokasi Live</th><th>Order Selesai</th><th style="text-align: center;">Aksi</th></tr></thead>
                <tbody>
                    @foreach($drivers as $index => $driver)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td style="font-weight: 600; color: var(--text-main);">{{ $driver->name }}</td>
                        <td style="font-size: 13px; color: var(--text-muted);">{{ $driver->username ?? '-' }}</td>
                        <td style="font-family: monospace; font-size: 13px;">{{ $driver->phone }}</td>
                        <td><span style="font-size: 13px; color: var(--text-secondary);">{{ $driver->zone->name }}</span></td>
                        <td>
                            @php
                                $currentStop = $driver->currentRouteStop;
                                $routeUpdatedAt = $driver->route_stop_updated_at;
                                $isStale = !$routeUpdatedAt || $routeUpdatedAt->diffInMinutes(now()) > 120;
                            @endphp

                            @if($currentStop)
                                <div class="live-route-badge {{ $isStale ? 'is-stale' : '' }}">
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: currentColor; display: inline-block;"></span>
                                    {{ $currentStop->name }}
                                </div>
                                <div class="live-route-meta">
                                        <!-- Jalur {{ $currentStop->order_index }} -->
                                    @if($routeUpdatedAt)
                                        Update {{ $routeUpdatedAt->diffForHumans() }}
                                    @endif
                                </div>
                            @else
                                <div class="live-route-badge is-stale">Belum ada lokasi live</div>
                                <div class="live-route-meta">Supir belum memilih jalur aktif.</div>
                            @endif
                        </td>
                        <td>
                            @php
                                $completedOrdersTimelinePayload = [
                                    'driver_name' => $driver->name,
                                    'zone_name' => $driver->zone?->name,
                                    'orders' => $driver->orders->map(function ($order) {
                                        $createdAt = $order->created_at;
                                        return [
                                            'customer_name' => $order->customer?->name ?? 'Unknown',
                                            'created_at' => $createdAt ? $createdAt->locale('id')->translatedFormat('d M Y, H:i') : '-',
                                            'time_human' => $createdAt ? $createdAt->diffForHumans() : '-',
                                            'date_key' => $createdAt ? $createdAt->format('Y-m-d') : '-',
                                            'date_label' => $createdAt ? $createdAt->locale('id')->translatedFormat('l, d F Y') : '-',
                                            'time_label' => $createdAt ? $createdAt->format('H:i') : '-',
                                            'product_name' => $order->iceType?->name ?? 'Es Batu',
                                            'quantity' => $order->effective_quantity,
                                            'status' => $order->status,
                                        ];
                                    })->values(),
                                ];
                            @endphp
                            <button type="button"
                                class="completed-orders-pill"
                                data-timeline='@json($completedOrdersTimelinePayload)'
                                onclick="openCompletedOrdersTimeline(JSON.parse(this.dataset.timeline))">
                                {{ $driver->completed_orders_count }} Order
                            </button>
                        </td>
                        <td>
                            <div style="display: flex; justify-content: center; gap: 6px;">
                                <a href="{{ route('drivers.edit', $driver->id) }}" class="btn btn-secondary" style="padding: 4px 10px; font-size: 13px;">
                                    Edit
                                </a>
                                <button type="button" class="btn btn-danger" style="padding: 4px 10px; font-size: 13px; background: #FEF2F2; color: #EF4444; border: 1px solid #FECACA; box-shadow: none;" onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'" onclick="confirmDelete({{ $driver->id }})">
                                    Delete
                                </button>
                                <form id="delete-form-{{ $driver->id }}" action="{{ route('drivers.destroy', $driver->id) }}" method="POST" style="display: none;">@csrf @method('DELETE')</form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<!-- Completed Orders Timeline Modal -->
<div id="completedOrdersTimelineModal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); z-index: 1100; align-items: center; justify-content: center; padding: 16px;">
    <div style="width: 100%; max-width: 720px; background: #fff; border-radius: 20px; box-shadow: 0 30px 60px rgba(15, 23, 42, 0.18); overflow: hidden; animation: modalFadeIn 0.25s ease;">
        <div style="padding: 22px 24px; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; gap: 16px; align-items: flex-start;">
            <div>
                <div style="font-size: 13px; color: #64748B; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em;">Timeline Order Selesai</div>
                <h3 id="timelineDriverName" style="margin: 6px 0 0; font-size: 22px; color: #0F172A;">-</h3>
                <p id="timelineDriverMeta" style="margin: 6px 0 0; color: #64748B; font-size: 14px;">-</p>
            </div>
            <button type="button" onclick="closeCompletedOrdersTimeline()" style="width: 38px; height: 38px; border-radius: 12px; border: 1px solid #E2E8F0; background: #fff; color: #475569; cursor: pointer;">✕</button>
        </div>
        <div style="padding: 24px; max-height: 70vh; overflow-y: auto;">
            <div id="timelineEmptyState" style="display: none; text-align: center; padding: 28px 12px; color: #94A3B8;">
                Belum ada order selesai untuk supir ini.
            </div>
            <div id="completedOrdersTimelineList" class="completed-orders-timeline"></div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteConfirmModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; width: 90%; max-width: 380px; padding: 28px; text-align: center; animation: modalFadeIn 0.3s ease;">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: #FEF2F2; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        </div>
        <h3 style="margin: 0 0 8px; font-size: 19px; font-weight: 600; color: var(--text-main);">Hapus Data Supir?</h3>
        <p style="margin: 0 0 24px; color: var(--text-muted); font-size: 14px;">Data yang dihapus tidak dapat dikembalikan.</p>
        <div style="display: flex; gap: 10px; justify-content: center;">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()" style="flex: 1;">Batal</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteBtn" style="flex: 1;">Ya, Hapus</button>
        </div>
    </div>
</div>

<style>
    @keyframes modalFadeIn {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .live-route-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 600;
        background: #ECFDF5;
        color: #059669;
        border: 1px solid #A7F3D0;
        white-space: nowrap;
    }
    .live-route-badge.is-stale {
        background: #FEF3C7;
        color: #B45309;
        border-color: #FCD34D;
    }
    .live-route-meta {
        margin-top: 4px;
        font-size: 12px;
        color: var(--text-muted);
        line-height: 1.4;
    }
    .completed-orders-pill {
        background: #ECFDF5;
        color: #059669;
        border: 1px solid #A7F3D0;
        padding: 4px 10px;
        border-radius: 999px;
        font-weight: 600;
        font-size: 13px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .completed-orders-pill:hover {
        background: #D1FAE5;
        transform: translateY(-1px);
    }
    .completed-orders-timeline {
        display: flex;
        flex-direction: column;
        gap: 18px;
        position: relative;
    }
    .timeline-group {
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 14px;
        padding-left: 6px;
    }
    .timeline-date-separator {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #64748B;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        margin: 4px 0;
    }
    .timeline-date-separator::before,
    .timeline-date-separator::after {
        content: '';
        height: 1px;
        background: #E2E8F0;
        flex: 1;
    }
    .timeline-item {
        display: grid;
        grid-template-columns: 38px 1fr;
        gap: 12px;
        align-items: flex-start;
    }
    .timeline-group::before {
        content: '';
        position: absolute;
        left: 24px;
        top: 50px;
        bottom: 12px;
        width: 2px;
        background: linear-gradient(180deg, #D7E4FF 0%, #BFDBFE 55%, #E2E8F0 100%);
        border-radius: 999px;
        z-index: 0;
    }
    .timeline-rail {
        position: relative;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        z-index: 1;
    }
    .timeline-marker {
        width: 20px;
        height: 20px;
        border-radius: 999px;
        background: linear-gradient(135deg, #3B82F6, #2563EB);
        border: 3px solid #fff;
        box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.18), 0 6px 14px rgba(37, 99, 235, 0.16);
        margin-top: 2px;
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
    }
    .timeline-marker svg {
        width: 11px;
        height: 11px;
    }
    .timeline-card {
        background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%);
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        padding: 16px 18px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.035);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .timeline-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    }
    .timeline-card-top {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        align-items: flex-start;
        flex-wrap: wrap;
        margin-bottom: 8px;
    }
    .timeline-customer {
        font-size: 16px;
        font-weight: 700;
        color: #0F172A;
        margin: 0;
    }
    .timeline-meta {
        font-size: 13px;
        color: #64748B;
        margin: 0;
    }
    .timeline-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        border-radius: 999px;
        background: #EFF6FF;
        color: #1D4ED8;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
    }
    .timeline-subline {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        margin-top: 10px;
        color: #64748B;
        font-size: 13px;
    }
    .timeline-status-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 999px;
        background: #ECFDF5;
        color: #059669;
        font-size: 12px;
        font-weight: 700;
    }
</style>

@push('scripts')
<script>
    let driverIdToDelete = null;
    let completedOrdersTimelinePayload = null;

    function confirmDelete(id) {
        driverIdToDelete = id;
        document.getElementById('deleteConfirmModal').style.display = 'flex';
    }
    function closeDeleteModal() {
        document.getElementById('deleteConfirmModal').style.display = 'none';
        driverIdToDelete = null;
    }
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (driverIdToDelete) document.getElementById('delete-form-' + driverIdToDelete).submit();
    });
    document.getElementById('deleteConfirmModal').addEventListener('click', function(e) {
        if (e.target === this) closeDeleteModal();
    });

    function openCompletedOrdersTimeline(payload) {
        completedOrdersTimelinePayload = payload || { orders: [] };
        const modal = document.getElementById('completedOrdersTimelineModal');
        const nameEl = document.getElementById('timelineDriverName');
        const metaEl = document.getElementById('timelineDriverMeta');
        const listEl = document.getElementById('completedOrdersTimelineList');
        const emptyEl = document.getElementById('timelineEmptyState');

        if (!modal || !nameEl || !metaEl || !listEl || !emptyEl) {
            return;
        }

        nameEl.textContent = completedOrdersTimelinePayload.driver_name || 'Supir';
        metaEl.textContent = completedOrdersTimelinePayload.zone_name
            ? `Zona ${completedOrdersTimelinePayload.zone_name}`
            : 'Data order selesai supir';

        const orders = completedOrdersTimelinePayload.orders || [];
        if (!orders.length) {
            listEl.innerHTML = '';
            emptyEl.style.display = 'block';
        } else {
            emptyEl.style.display = 'none';
            const groupedOrders = orders.reduce((groups, order) => {
                const key = order.date_key || 'unknown';
                if (!groups[key]) {
                    groups[key] = {
                        label: order.date_label || 'Tanggal tidak diketahui',
                        items: [],
                    };
                }
                groups[key].items.push(order);
                return groups;
            }, {});

            listEl.innerHTML = Object.entries(groupedOrders).map(([dateKey, group]) => `
                <div class="timeline-group">
                    <div class="timeline-date-separator">${escapeHtml(group.label)}</div>
                    <div style="display: flex; flex-direction: column; gap: 14px;">
                        ${group.items.map((order, index) => `
                            <div class="timeline-item">
                                <div class="timeline-rail">
                                    <div class="timeline-marker" aria-hidden="true">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 6 9 17l-5-5"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="timeline-card">
                                    <div class="timeline-card-top">
                                        <div>
                                            <p class="timeline-customer">${escapeHtml(order.customer_name || 'Unknown')}</p>
                                            <p class="timeline-meta">${escapeHtml(order.created_at || '-')}</p>
                                        </div>
                                        <span class="timeline-badge">${escapeHtml(order.product_name || 'Pesanan')} • ${Number(order.quantity || 1)} item</span>
                                    </div>
                                    <div class="timeline-subline">
                                        <span class="timeline-status-chip">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M20 6 9 17l-5-5"></path>
                                            </svg>
                                            Selesai antar
                                        </span>
                                        <span>${escapeHtml(order.time_human || '-')}${order.time_label ? ' • ' + escapeHtml(order.time_label) : ''}</span>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `).join('');
        }

        modal.style.display = 'flex';
    }

    function closeCompletedOrdersTimeline() {
        const modal = document.getElementById('completedOrdersTimelineModal');
        if (modal) {
            modal.style.display = 'none';
        }
        completedOrdersTimelinePayload = null;
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function toggleZoneFilterSelect() { document.getElementById('zoneFilterSelectWrapper').classList.toggle('open'); }
    function selectZoneFilterOption(el) {
        document.getElementById('zoneFilterInput').value = el.dataset.value;
        const t = document.getElementById('zoneFilterSelectText');
        t.textContent = el.textContent.trim();
        t.classList.remove('text-placeholder');
        document.querySelectorAll('#zoneFilterSelectWrapper .custom-option').forEach(o => o.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('zoneFilterSelectWrapper').classList.remove('open');
    }
    document.addEventListener('click', function(e) {
        const w = document.getElementById('zoneFilterSelectWrapper');
        if (w && !w.contains(e.target)) w.classList.remove('open');
    });

    document.getElementById('completedOrdersTimelineModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeCompletedOrdersTimeline();
        }
    });
</script>
@endpush
@endsection