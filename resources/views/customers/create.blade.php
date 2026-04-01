@extends('layouts.dashboard')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Tambah Pelanggan</h1>
        <p class="page-subtitle" style="margin-bottom: 16px;">Isi formulir di bawah untuk menambahkan data pelanggan baru.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Formulir Pelanggan</h3>
    </div>
    
    <div class="card-body">
        <form action="{{ route('customers.store') }}" method="POST">
            @csrf

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px;">
                <!-- Name -->
                <div>
                    <label for="name" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Nama Lengkap <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" placeholder="Masukkan nama pelanggan" required style="width: 100%;">
                    @error('name')
                        <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Nomor Telepon <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone') }}" placeholder="Contoh: 08123456789" required style="width: 100%;">
                    @error('phone')
                        <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <label for="zone" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Zona Wilayah</label>

                @if(request('zone'))
                    <div class="custom-select-wrapper zone-locked" id="zoneSelectWrapper">
                        <div class="custom-select-trigger custom-select-disabled" aria-disabled="true">
                            <span id="zoneSelectText">{{ ucfirst(old('zone', request('zone'))) }}</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
                        </div>
                        <input type="hidden" name="zone" id="zoneInput" value="{{ old('zone', request('zone')) }}">
                    </div>
                @else
                    <div class="custom-select-wrapper" id="zoneSelectWrapper">
                        <div class="custom-select-trigger" onclick="toggleSelect()">
                            <span id="zoneSelectText" class="text-placeholder">Pilih Zona</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
                        </div>
                        <div class="custom-options">
                            <div class="custom-option {{ (old('zone') == '' && !request('zone')) ? 'selected' : '' }}" data-value="" onclick="selectOption(this)">Pilih Zona</div>
                            @foreach($zones as $zone)
                                @php
                                    $isSelected = old('zone') == $zone->name;
                                @endphp
                                <div class="custom-option {{ $isSelected ? 'selected' : '' }}" data-value="{{ $zone->name }}" onclick="selectOption(this)">
                                    {{ ucfirst($zone->name) }}
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="zone" placeholder="Pilih Zona" id="zoneInput" value="{{ old('zone') }}">
                    </div>
                @endif

                @error('zone')
                    <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-bottom: 24px;">
                <label for="address" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Alamat Lengkap</label>
                <textarea name="address" id="address" class="form-control" rows="4" placeholder="Masukkan alamat lengkap pelanggan" style="width: 100%; resize: vertical;">{{ old('address') }}</textarea>
                @error('address')
                    <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; justify-content: flex-end;">
                <a href="{{ route('customers.index', ['zone' => request('zone')]) }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="white" style="margin-right: 6px;">
                        <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>
                    </svg>
                    Simpan Data
                </button>
            </div>
        </form>
    </div>
</div>

<style>
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
.custom-select-disabled {
    background: #f8fafc;
    cursor: not-allowed;
    color: #475569;
}
.zone-locked .select-icon {
    opacity: 0.6;
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
    z-index: 10;
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
/* Custom Scrollbar for Options */
.custom-options::-webkit-scrollbar {
    width: 6px;
}
.custom-options::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 6px;
}
.custom-options::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 6px;
}
.custom-options::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>

@push('scripts')
<script>
    function toggleSelect() {
        document.getElementById('zoneSelectWrapper').classList.toggle('open');
    }

    function selectOption(element) {
        const value = element.getAttribute('data-value');
        const text = element.textContent.trim();
        
        document.getElementById('zoneInput').value = value;
        const textElement = document.getElementById('zoneSelectText');
        textElement.textContent = text;
        
        if (value === "") {
            textElement.classList.add('text-placeholder');
        } else {
            textElement.classList.remove('text-placeholder');
        }
        
        const options = document.querySelectorAll('.custom-option');
        options.forEach(opt => opt.classList.remove('selected'));
        element.classList.add('selected');
        
        document.getElementById('zoneSelectWrapper').classList.remove('open');
    }

    document.addEventListener('click', function(e) {
        const selectWrapper = document.getElementById('zoneSelectWrapper');
        if (selectWrapper && !selectWrapper.contains(e.target)) {
            selectWrapper.classList.remove('open');
        }
    });

    window.addEventListener('DOMContentLoaded', () => {
        const selectedOption = document.querySelector('.custom-option.selected');
        if (selectedOption) {
            const textElement = document.getElementById('zoneSelectText');
            textElement.textContent = selectedOption.textContent.trim();
            if (selectedOption.getAttribute('data-value') !== "") {
                textElement.classList.remove('text-placeholder');
            } else {
                textElement.classList.add('text-placeholder');
            }
        }
    });
</script>
@endpush
@endsection
