@extends('layouts.dashboard')

@section('title', 'Tambah Jenis Es — Dashboard Admin')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Tambah Jenis Es</h1>
        <p class="page-subtitle">Buat jenis/ukuran es baru untuk sistem</p>
    </div>
    <a href="{{ route('ice-types.index') }}" class="btn btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;">
            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
        </svg>
        Kembali
    </a>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Form Jenis Es</div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Periksa kembali isian form:</strong>
            <ul style="margin:8px 0 0 20px;font-size:13px;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('ice-types.store') }}" method="POST">
        @csrf

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px;">
            <div>
                <label style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:var(--text-secondary);">
                    Nama Jenis <span style="color:#EF4444;">*</span>
                </label>
                <input type="text" name="name" class="form-control" placeholder="Contoh: Es 10kg" value="{{ old('name') }}" required>
                <p style="font-size:12px;color:var(--text-muted);margin-top:4px;">Nama yang tampil ke pengguna</p>
            </div>
            <div>
                <label style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:var(--text-secondary);">
                    Berat (kg) <span style="color:#EF4444;">*</span>
                </label>
                <input type="number" name="weight" class="form-control" placeholder="Contoh: 10" step="0.01" min="0.01" value="{{ old('weight') }}" required>
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:var(--text-secondary);">
                Harga (opsional)
            </label>
            <div style="position:relative;">
                <span style="position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:14px;color:var(--text-muted);pointer-events:none;">Rp</span>
                <input type="number" name="price" class="form-control" placeholder="0" step="0.01" min="0" value="{{ old('price', 0) }}" style="padding-left:40px;">
            </div>
        </div>

        <div style="margin-bottom:16px;">
            <label style="display:block;margin-bottom:6px;font-size:13px;font-weight:600;color:var(--text-secondary);">
                Deskripsi (opsional)
            </label>
            <textarea name="description" class="form-control" rows="3" placeholder="Keterangan tambahan tentang jenis es ini...">{{ old('description') }}</textarea>
        </div>

        <div style="margin-bottom:24px;">
            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;user-select:none;">
                <div style="position:relative;width:42px;height:24px;" id="toggle-wrapper">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }}
                        style="opacity:0;position:absolute;width:0;height:0;"
                        onchange="document.getElementById('toggle-thumb').style.transform = this.checked ? 'translateX(18px)' : 'translateX(0)';
                                  document.getElementById('toggle-track').style.background = this.checked ? 'var(--accent)' : '#CBD5E1';">
                    <div id="toggle-track" style="position:absolute;inset:0;border-radius:12px;background:{{ old('is_active', 1) ? 'var(--accent)' : '#CBD5E1' }};transition:background 0.2s;"></div>
                    <div id="toggle-thumb" style="position:absolute;top:3px;left:3px;width:18px;height:18px;background:white;border-radius:50%;box-shadow:0 1px 3px rgba(0,0,0,0.2);transition:transform 0.2s;transform:{{ old('is_active', 1) ? 'translateX(18px)' : 'translateX(0)' }};"></div>
                </div>
                <span style="font-size:14px;font-weight:500;color:var(--text-secondary);">Aktifkan jenis es ini</span>
            </label>
            <p style="font-size:12px;color:var(--text-muted);margin-top:6px;margin-left:52px;">Jenis es yang nonaktif tidak akan tampil di sistem pemesanan</p>
        </div>

        <div style="display:flex;gap:12px;">
            <button type="submit" class="btn btn-primary" style="flex:1;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;">
                    <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                </svg>
                Simpan Jenis Es
            </button>
            <a href="{{ route('ice-types.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
@endsection
