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
                <thead><tr><th>No</th><th>Nama Supir</th><th>Username</th><th>No Telepon</th><th>Zona</th><th>Order Selesai</th><th style="text-align: center;">Aksi</th></tr></thead>
                <tbody>
                    @foreach($drivers as $index => $driver)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td style="font-weight: 600; color: var(--text-main);">{{ $driver->name }}</td>
                        <td style="font-size: 13px; color: var(--text-muted);">{{ $driver->username ?? '-' }}</td>
                        <td style="font-family: monospace; font-size: 13px;">{{ $driver->phone }}</td>
                        <td><span style="font-size: 13px; color: var(--text-secondary);">{{ $driver->zone->name }}</span></td>
                        <td>
                            <span style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0; padding: 4px 8px; border-radius: 6px; font-weight: 600; font-size: 13px; display: inline-block;">
                                {{ $driver->completed_orders_count }} Order
                            </span>
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
</style>

@push('scripts')
<script>
    let driverIdToDelete = null;

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
</script>
@endpush
@endsection