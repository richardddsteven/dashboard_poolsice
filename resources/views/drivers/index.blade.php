@extends('layouts.dashboard')

@section('title')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Supir</h1>
        <p class="page-subtitle" style="margin-bottom: 24px;">Manajemen data supir</p>
    </div>
    <a href="{{ route('drivers.create') }}" class="btn btn-primary" style="margin-bottom: 16px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 6px;">
            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
        </svg>
        Tambah Supir
    </a>
</div>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <h3 class="card-title">Daftar Supir</h3>
        
        <form action="{{ route('drivers.index') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="position: relative;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau telepon..." class="form-control" style="padding-left: 36px; width: 250px;">
            </div>

            <div style="min-width: 180px;">
                <div class="custom-select-wrapper" id="zoneFilterSelectWrapper">
                    <div class="custom-select-trigger" onclick="toggleZoneFilterSelect()">
                        @php
                            $selectedZoneName = 'Semua Zona';
                            if(request('zone_id')) {
                                $selectedZoneObj = $zones->firstWhere('id', request('zone_id'));
                                if($selectedZoneObj) {
                                    $selectedZoneName = $selectedZoneObj->name;
                                }
                            }
                        @endphp
                        <span id="zoneFilterSelectText" class="{{ request('zone_id') ? '' : 'text-placeholder' }}">{{ $selectedZoneName }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
                    </div>
                    <div class="custom-options">
                        <div class="custom-option {{ request('zone_id') == '' ? 'selected' : '' }}" data-value="" onclick="selectZoneFilterOption(this)">Semua Zona</div>
                        @foreach($zones as $zone)
                            <div class="custom-option {{ request('zone_id') == $zone->id ? 'selected' : '' }}" data-value="{{ $zone->id }}" onclick="selectZoneFilterOption(this)">
                                {{ $zone->name }}
                            </div>
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
    <div class="card-body">
        @if($drivers->isEmpty())
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <p>Belum ada data supir.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NAMA SUPIR</th>
                            <th>USERNAME</th>
                            <th>NO TELEPON</th>
                            <th>ZONA</th>
                            <th style="text-align: right;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($drivers as $index => $driver)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td style="font-weight: 500; color: var(--text-main);">{{ $driver->name }}</td>
                            <td>{{ $driver->username ?? '-' }}</td>
                            <td>{{ $driver->phone }}</td>
                            <td>
                                <span style="display: inline-flex; font-weight: 600; color: var(--text-main); align-items: center;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px; color: #3B82F6;">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                    {{ $driver->zone->name }}
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                    <a href="{{ route('drivers.edit', $driver->id) }}" class="btn btn-secondary" style="padding: 6px 12px; height: auto;" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                    </a>
                                    <button type="button" class="btn btn-danger" style="padding: 6px 12px; height: auto;" title="Hapus" onclick="confirmDelete({{ $driver->id }})">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="white"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                    </button>
                                    <form id="delete-form-{{ $driver->id }}" action="{{ route('drivers.destroy', $driver->id) }}" method="POST" style="display: none;">
                                        @csrf
                                        @method('DELETE')
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
</div>

<!-- Modal Konfirmasi Hapus -->
<div id="deleteConfirmModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1050; align-items: center; justify-content: center;">
    <div class="modal-content" style="background: white; border-radius: 12px; width: 90%; max-width: 400px; padding: 24px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); transform: scale(0.95); transition: transform 0.2s; text-align: center;">
        <div style="width: 64px; height: 64px; border-radius: 50%; background: #FEE2E2; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                <line x1="12" y1="9" x2="12" y2="13"></line>
                <line x1="12" y1="17" x2="12.01" y2="17"></line>
            </svg>
        </div>
        <h3 style="margin: 0 0 8px; font-size: 18px; color: #1E293B;">Hapus Data Supir?</h3>
        <p style="margin: 0 0 24px; color: #64748B; font-size: 14px;">Data yang dihapus tidak dapat dikembalikan. Apakah Anda yakin ingin melanjutkan?</p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()" style="flex: 1;">Batal</button>
            <button type="button" class="btn btn-danger" id="confirmDeleteBtn" style="flex: 1;">Ya, Hapus</button>
        </div>
    </div>
</div>

<style>
/* Custom Select Dropdown CSS */
.custom-select-wrapper {
    position: relative;
    width: 100%;
    user-select: none;
    text-align: left;
}
.custom-select-trigger {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 16px;
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    color: #334155;
    transition: all 0.2s ease;
}
.custom-select-wrapper.open .custom-select-trigger {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
.custom-select-wrapper.open .select-icon {
    transform: rotate(180deg);
}
.select-icon {
    transition: transform 0.3s ease;
}
.text-placeholder {
    color: #94a3b8;
}
.custom-options {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin-top: 8px;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transform: translateY(-10px);
    transition: all 0.2s ease;
    z-index: 1050;
    max-height: 250px;
    overflow-y: auto;
}
.custom-select-wrapper.open .custom-options {
    opacity: 1;
    visibility: visible;
    pointer-events: all;
    transform: translateY(0);
}
.custom-option {
    padding: 10px 16px;
    font-size: 14px;
    color: #475569;
    cursor: pointer;
    transition: background 0.15s ease;
}
.custom-option:hover {
    background: #f8fafc;
    color: #1e293b;
}
.custom-option.selected {
    background: #eff6ff;
    color: #2563eb;
    font-weight: 500;
}
</style>

@push('scripts')
<script>
    let driverIdToDelete = null;

    function confirmDelete(id) {
        driverIdToDelete = id;
        const modal = document.getElementById('deleteConfirmModal');
        const modalContent = modal.querySelector('.modal-content');
        modal.style.display = 'flex';
        setTimeout(() => {
            modalContent.style.transform = 'scale(1)';
        }, 10);
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteConfirmModal');
        const modalContent = modal.querySelector('.modal-content');
        modalContent.style.transform = 'scale(0.95)';
        setTimeout(() => {
            modal.style.display = 'none';
            driverIdToDelete = null;
        }, 200);
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (driverIdToDelete) {
            document.getElementById('delete-form-' + driverIdToDelete).submit();
        }
    });

    document.getElementById('deleteConfirmModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeDeleteModal();
        }
    });

    // Custom Select Logic
    function toggleZoneFilterSelect() {
        document.getElementById('zoneFilterSelectWrapper').classList.toggle('open');
    }

    function selectZoneFilterOption(element) {
        const value = element.getAttribute('data-value');
        const text = element.textContent.trim();
        
        document.getElementById('zoneFilterInput').value = value;
        const textElement = document.getElementById('zoneFilterSelectText');
        textElement.textContent = text;
        textElement.classList.remove('text-placeholder');
        
        const options = document.querySelectorAll('#zoneFilterSelectWrapper .custom-option');
        options.forEach(opt => opt.classList.remove('selected'));
        element.classList.add('selected');
        
        document.getElementById('zoneFilterSelectWrapper').classList.remove('open');
    }

    document.addEventListener('click', function(e) {
        const wrapper = document.getElementById('zoneFilterSelectWrapper');
        if (wrapper && !wrapper.contains(e.target)) {
            wrapper.classList.remove('open');
        }
    });
</script>
@endpush
@endsection