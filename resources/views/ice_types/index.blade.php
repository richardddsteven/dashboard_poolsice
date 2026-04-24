@extends('layouts.dashboard')

@section('title')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Jenis Es</h1>
        <p class="page-subtitle">Kelola jenis dan ukuran es yang tersedia</p>
    </div>
    <a href="{{ route('ice-types.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:16px;height:16px;fill:currentColor;flex-shrink:0;">
            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
        </svg>
        Tambah Jenis Es
    </a>
</div>


<div class="card">
    <div class="card-header">
        <div class="card-title">Daftar Jenis Es</div>
        <span style="font-size:13px;color:var(--text-muted);">{{ $iceTypes->count() }} jenis terdaftar</span>
    </div>

    @if($iceTypes->isEmpty())
        <div style="text-align:center;padding:60px 24px;">
            <div style="width:60px;height:60px;background:var(--bg-body);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;border:1px solid var(--border-color);">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:28px;height:28px;fill:var(--text-light);">
                    <path d="M20 6h-2V5a3 3 0 0 0-6 0v1h-2V5a3 3 0 0 0-6 0v1H2v16h20V6zm-8-3a1 1 0 0 1 1 1v1h-2V4a1 1 0 0 1 1-1zM4 8h16v12H4V8zm3 2h10v2H7v-2zm0 4h6v2H7v-2z"/>
                </svg>
            </div>
            <p style="color:var(--text-muted);font-size:14px;">Belum ada jenis es yang ditambahkan.</p>
            <a href="{{ route('ice-types.create') }}" class="btn btn-primary" style="margin-top:12px;">Tambah Sekarang</a>
        </div>
    @else
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width:40px;">No</th>
                        <th>Nama Jenis</th>
                        <th>Berat (kg)</th>
                        <th>Harga</th>
                        <th>Deskripsi</th>
                        <th>Status</th>
                        <th style="text-align:center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($iceTypes as $index => $type)
                    <tr>
                        <td style="color:var(--text-muted);font-size:13px;">{{ $index + 1 }}</td>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <span style="font-weight:600;color:var(--text-main);">{{ $type->name }}</span>
                            </div>
                        </td>
                        <td>
                            <span style="font-weight:600;">{{ number_format($type->weight, 0) }}</span>
                            <span style="color:var(--text-muted);font-size:13px;"> kg</span>
                        </td>
                        <td>
                            @if($type->price > 0)
                                <span style="font-weight:600;color:var(--text-main);">Rp {{ number_format($type->price, 0, ',', '.') }}</span>
                            @else
                                <span style="color:var(--text-light);font-size:13px;">—</span>
                            @endif
                        </td>
                        <td style="color:var(--text-muted);font-size:13px;max-width:200px;">
                            {{ $type->description ?: '—' }}
                        </td>
                        <td>
                            @if($type->is_active)
                                <span class="status-badge status-approved">Aktif</span>
                            @else
                                <span class="status-badge status-rejected">Nonaktif</span>
                            @endif
                        </td>
                        <td style="text-align:center;">
                            <div style="display:flex;gap:6px;justify-content:center;">
                                <a href="{{ route('ice-types.edit', $type) }}" class="btn btn-secondary" style="padding:4px 10px;font-size:13px;">
                                    Edit
                                </a>
                                {{-- Hidden form for delete --}}
                                <form id="delete-form-{{ $type->id }}" action="{{ route('ice-types.destroy', $type) }}" method="POST" style="display:none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <button type="button" class="btn btn-danger"
                                    style="padding:4px 10px;font-size:13px;background:#FEF2F2;color:#EF4444;border:1px solid #FECACA;box-shadow:none;"
                                    onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'"
                                    onclick="openDeleteModal({{ $type->id }}, '{{ addslashes($type->name) }}')">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ===== Custom Delete Confirmation Modal ===== --}}
<div id="deleteModal" style="
    display:none;
    position:fixed;inset:0;z-index:1000;
    background:rgba(15,23,42,0.45);
    align-items:center;justify-content:center;
">
    <div style="
        background:#fff;
        border-radius:16px;
        padding:36px 32px 28px;
        width:100%;
        max-width:420px;
        margin:16px;
        box-shadow:0 20px 60px rgba(0,0,0,0.18);
        text-align:center;
        animation:modalIn 0.2s cubic-bezier(0.34,1.56,0.64,1);
    ">
        {{-- Warning icon --}}
        <div style="
            width:64px;height:64px;
            background:#FEF2F2;
            border-radius:50%;
            display:flex;align-items:center;justify-content:center;
            margin:0 auto 20px;
        ">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style="width:32px;height:32px;fill:#EF4444;">
                <path d="M1 21h22L12 2 1 21zm12-3h-2v-2h2v2zm0-4h-2v-4h2v4z"/>
            </svg>
        </div>

        <h2 style="font-size:18px;font-weight:700;color:var(--text-main);margin-bottom:8px;">Hapus Jenis Es?</h2>
        <p style="font-size:14px;color:var(--text-muted);margin-bottom:4px;">
            Anda akan menghapus:
            <strong id="deleteModalName" style="color:var(--text-main);"></strong>
        </p>
        <p style="font-size:13px;color:#EF4444;margin-bottom:28px;">Data yang dihapus tidak dapat dikembalikan.</p>

        <div style="display:flex;gap:12px;">
            <button type="button" onclick="closeDeleteModal()" style="
                flex:1;
                padding:11px 20px;
                border-radius:8px;
                border:1px solid var(--border-color);
                background:white;
                color:var(--text-secondary);
                font-size:14px;font-weight:500;
                cursor:pointer;
                font-family:inherit;
                transition:background 0.15s;
            " onmouseover="this.style.background='var(--bg-body)'" onmouseout="this.style.background='white'">
                Batal
            </button>
            <button type="button" id="deleteConfirmBtn" style="
                flex:1;
                padding:11px 20px;
                border-radius:8px;
                border:none;
                background:#EF4444;
                color:white;
                font-size:14px;font-weight:600;
                cursor:pointer;
                font-family:inherit;
                transition:background 0.15s;
            " onmouseover="this.style.background='#DC2626'" onmouseout="this.style.background='#EF4444'">
                Ya, Hapus
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
    @keyframes modalIn {
        from { opacity:0; transform: scale(0.88); }
        to   { opacity:1; transform: scale(1); }
    }
</style>
@endpush

<script>
    let _deleteFormId = null;

    function openDeleteModal(id, name) {
        _deleteFormId = id;
        document.getElementById('deleteModalName').textContent = name;
        const modal = document.getElementById('deleteModal');
        modal.style.display = 'flex';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
        _deleteFormId = null;
    }

    document.getElementById('deleteConfirmBtn').addEventListener('click', function () {
        if (_deleteFormId !== null) {
            document.getElementById('delete-form-' + _deleteFormId).submit();
        }
    });

    // Close modal when clicking backdrop
    document.getElementById('deleteModal').addEventListener('click', function (e) {
        if (e.target === this) closeDeleteModal();
    });

    // Close on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeDeleteModal();
    });
</script>
@endsection
