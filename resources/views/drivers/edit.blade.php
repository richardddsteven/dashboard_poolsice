@extends('layouts.dashboard')
@section('title')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Supir</h1>
        <p class="page-subtitle">Perbarui data supir</p>
    </div>
    <a href="{{ route('drivers.index') }}" class="btn btn-secondary">
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
    <form method="POST" action="{{ route('drivers.update', $driver->id) }}">
        @csrf @method('PUT')
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div>
                <label for="name" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Nama Supir <span style="color: #EF4444;">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $driver->name) }}" required>
            </div>
            <div>
                <label for="username" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Username <span style="color: #EF4444;">*</span></label>
                <input type="text" id="username" name="username" class="form-control" value="{{ old('username', $driver->username) }}" required>
            </div>
            <div>
                <label for="phone" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">No. Telepon <span style="color: #EF4444;">*</span></label>
                <input type="text" id="phone" name="phone" class="form-control" value="{{ old('phone', $driver->phone) }}" required>
            </div>
            <div>
                <label for="password" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Password <span style="color: var(--text-muted);">(kosongkan jika tidak ingin mengubah)</span></label>
                <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password baru">
            </div>
            <div>
                <label for="zone_id" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Zona <span style="color: #EF4444;">*</span></label>
                <div class="custom-select-wrapper" id="zoneSelectWrapper">
                    <div class="custom-select-trigger" onclick="document.getElementById('zoneSelectWrapper').classList.toggle('open')">
                        <span id="zoneSelectText">{{ $driver->zone ? ucfirst($driver->zone->name) : 'Pilih zona' }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
                    </div>
                    <div class="custom-options">
                        @foreach($zones as $zone)
                            <div class="custom-option {{ old('zone_id', $driver->zone_id) == $zone->id ? 'selected' : '' }}" data-value="{{ $zone->id }}" onclick="selectZone(this)">{{ ucfirst($zone->name) }}</div>
                        @endforeach
                    </div>
                    <input type="hidden" name="zone_id" id="zoneInput" value="{{ old('zone_id', $driver->zone_id) }}">
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
    el.classList.add('selected'); document.getElementById('zoneSelectWrapper').classList.remove('open');
}
document.addEventListener('click', function(e) { const w = document.getElementById('zoneSelectWrapper'); if (w && !w.contains(e.target)) w.classList.remove('open'); });
</script>
@endsection