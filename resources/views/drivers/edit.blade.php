@extends('layouts.dashboard')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Supir</h1>
        <p class="page-subtitle" style="margin-bottom: 16px;">Perbarui data supir.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Informasi Supir</h3>
    </div>
    
    <div class="card-body">
        <form action="{{ route('drivers.update', $driver->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px;">
                <!-- Nama Supir -->
                <div>
                    <label for="name" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Nama Supir <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $driver->name) }}" placeholder="Masukkan nama supir" required style="width: 100%;">
                    @error('name')
                        <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- No Telepon -->
                <div>
                    <label for="phone" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">No Telepon <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', $driver->phone) }}" placeholder="Contoh: 081234567890" required style="width: 100%;">
                    @error('phone')
                        <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px;">
                <!-- Zona -->
                <div>
                    <label for="zone_id" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Zona <span style="color: #ef4444;">*</span></label>
                    
                    <div class="custom-select-wrapper" id="zoneSelectWrapper">
                        <div class="custom-select-trigger" onclick="toggleZoneSelect()">
                            <span id="zoneSelectText" class="text-placeholder">Pilih Zona</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
                        </div>
                        <div class="custom-options">
                            <div class="custom-option {{ (old('zone_id', $driver->zone_id) == '') ? 'selected' : '' }}" data-value="" onclick="selectZoneOption(this)">Pilih Zona</div>
                            @foreach($zones as $zone)
                                @php
                                    $isSelected = old('zone_id', $driver->zone_id) == $zone->id;
                                @endphp
                                <div class="custom-option {{ $isSelected ? 'selected' : '' }}" data-value="{{ $zone->id }}" onclick="selectZoneOption(this)">
                                    {{ $zone->name }}
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="zone_id" id="zoneInput" value="{{ old('zone_id', $driver->zone_id) }}" required>
                    </div>
                    @error('zone_id')
                        <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Username Login -->
                <div>
                    <label for="username" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Username Login <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="username" name="username" class="form-control" value="{{ old('username', $driver->username) }}" placeholder="Contoh: supir.jimbaran" required style="width: 100%;">
                    @error('username')
                        <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px;">
                <!-- Password Login -->
                <div>
                    <label for="password" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Password Login Baru</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah" style="width: 100%;">
                    <small style="display: block; margin-top: 6px; color: var(--text-muted);">Minimal 6 karakter jika diisi.</small>
                    @error('password')
                        <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; justify-content: flex-end;">
                <a href="{{ route('drivers.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="white" style="margin-right: 6px;">
                        <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>
                    </svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Custom Select Dropdown CSS */
.custom-select-wrapper {
    position: relative;
    width: 100%;
    user-select: none;
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
    // Initialize zone select value on load
    document.addEventListener('DOMContentLoaded', function() {
        const zoneValue = document.getElementById('zoneInput').value;
        if (zoneValue) {
            const option = document.querySelector(`.custom-option[data-value="${zoneValue}"]`);
            if (option) {
                document.getElementById('zoneSelectText').textContent = option.textContent.trim();
                document.getElementById('zoneSelectText').classList.remove('text-placeholder');
                option.classList.add('selected');
            }
        }
    });

    function toggleZoneSelect() {
        document.getElementById('zoneSelectWrapper').classList.toggle('open');
    }

    function selectZoneOption(element) {
        const value = element.getAttribute('data-value');
        const text = element.textContent.trim();
        
        document.getElementById('zoneInput').value = value;
        
        const textElement = document.getElementById('zoneSelectText');
        textElement.textContent = text;
        textElement.classList.remove('text-placeholder');
        
        const options = document.querySelectorAll('#zoneSelectWrapper .custom-option');
        options.forEach(opt => opt.classList.remove('selected'));
        element.classList.add('selected');
        
        document.getElementById('zoneSelectWrapper').classList.remove('open');
    }

    // Close select when clicking outside
    document.addEventListener('click', function(e) {
        const wrapper = document.getElementById('zoneSelectWrapper');
        if (wrapper && !wrapper.contains(e.target)) {
            wrapper.classList.remove('open');
        }
    });
</script>
@endpush
@endsection