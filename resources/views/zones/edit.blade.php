@extends('layouts.dashboard')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Zona</h1>
        <p class="page-subtitle" style="margin-bottom: 24px;">Perbarui nama zona wilayah untuk pengelompokan pelanggan.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Formulir Edit Zona</h3>
    </div>

    <div class="card-body">
        <form action="{{ route('zones.update', $zone) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="margin-bottom: 24px;">
                <label for="name" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Nama Zona <span style="color: #ef4444;">*</span></label>
                <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $zone->name) }}" placeholder="Contoh: Canggu, Jimbaran, Uluwatu" required>
                @error('name')
                    <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                @enderror
                <p style="font-size: 13px; color: var(--text-muted); margin-top: 8px;">
                    Saat nama zona diubah, semua pelanggan pada zona lama otomatis dipindahkan ke nama zona baru.
                </p>
            </div>

            <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; justify-content: flex-end;">
                <a href="{{ route('customers.index') }}" class="btn btn-secondary">Batal</a>
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
@endsection
