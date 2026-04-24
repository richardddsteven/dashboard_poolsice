@extends('layouts.dashboard')

@section('title')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Pengeluaran</h1>
        <p class="page-subtitle">Manajemen data pengeluaran operasional</p>
    </div>
    <a href="{{ route('expenses.create') }}" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>
        Tambah Pengeluaran
    </a>
</div>

<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 14px;">
        <h3 class="card-title">Daftar Pengeluaran</h3>
        <form action="{{ route('expenses.index') }}" method="GET" style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <div style="position: relative;">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--text-light);"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau catatan..." class="form-control" style="padding-left: 36px; width: 240px;">
            </div>
            <div style="min-width: 160px;">
                <div class="custom-select-wrapper" id="categorySelectWrapper">
                    <div class="custom-select-trigger" onclick="toggleCategorySelect()">
                        <span id="categorySelectText" class="text-placeholder">Semua Kategori</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
                    </div>
                    <div class="custom-options">
                        <div class="custom-option {{ request('category') == '' ? 'selected' : '' }}" data-value="" onclick="selectCategoryOption(this)">Semua Kategori</div>
                        @foreach($categories as $category)
                            <div class="custom-option {{ request('category') == $category->id ? 'selected' : '' }}" data-value="{{ $category->id }}" onclick="selectCategoryOption(this)">{{ $category->name }}</div>
                        @endforeach
                    </div>
                    <input type="hidden" name="category" id="categoryInput" value="{{ request('category') }}">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Cari</button>
            @if(request('search') || request('category'))
                <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>
    </div>
    @if($expenses->isEmpty())
        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="var(--border-color)" style="margin-bottom: 12px;"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
            <p style="font-size: 14px;">Belum ada data pengeluaran.</p>
        </div>
    @else
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Tanggal</th><th>Nama</th><th>Kategori</th><th>Jumlah</th><th>Catatan</th><th style="text-align: center;">Aksi</th></tr></thead>
                <tbody>
                    @foreach($expenses as $expense)
                    <tr>
                        <td style="font-size: 14px;">{{ \Carbon\Carbon::parse($expense->date)->format('d M Y') }}</td>
                        <td style="font-weight: 600; color: var(--text-main); font-size: 14px;">{{ $expense->name }}</td>
                        <td><span class="status-badge" style="background: rgba(99,102,241,0.08); color: #6366F1;">{{ $expense->category->name }}</span></td>
                        <td style="font-weight: 600; font-size: 14px;">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                        <td style="color: var(--text-muted); font-size: 13px;">{{ $expense->notes ?: '-' }}</td>
                        <td style="text-align: right;">
                            <div style="display: flex; justify-content: center; gap: 6px;">
                                <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-secondary" style="padding: 4px 10px; font-size: 13px;">
                                    Edit
                                </a>
                                <form action="{{ route('expenses.destroy', $expense) }}" method="POST" onsubmit="event.preventDefault(); showDeleteModal(this);" style="margin: 0; display: inline-block;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger" style="padding: 4px 10px; font-size: 13px; background: #FEF2F2; color: #EF4444; border: 1px solid #FECACA; box-shadow: none;" onmouseover="this.style.background='#FEE2E2'" onmouseout="this.style.background='#FEF2F2'">
                                        Delete
                                    </button>
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

<!-- Delete Modal -->
<div id="deleteConfirmModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; padding: 28px; width: 100%; max-width: 380px; text-align: center;">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: #FEF2F2; color: #EF4444; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
        </div>
        <h3 style="font-size: 19px; font-weight: 600; color: var(--text-main); margin-bottom: 8px;">Hapus Pengeluaran</h3>
        <p style="font-size: 14px; color: var(--text-muted); margin-bottom: 24px;">Tindakan ini tidak dapat dibatalkan.</p>
        <div style="display: flex; justify-content: center; gap: 10px;">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Batal</button>
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">Ya, Hapus</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function toggleCategorySelect() { document.getElementById('categorySelectWrapper').classList.toggle('open'); }
    function selectCategoryOption(el) {
        document.getElementById('categoryInput').value = el.dataset.value;
        const t = document.getElementById('categorySelectText');
        t.textContent = el.textContent.trim();
        el.dataset.value === '' ? t.classList.add('text-placeholder') : t.classList.remove('text-placeholder');
        document.querySelectorAll('#categorySelectWrapper .custom-option').forEach(o => o.classList.remove('selected'));
        el.classList.add('selected');
        document.getElementById('categorySelectWrapper').classList.remove('open');
    }
    document.addEventListener('click', function(e) {
        const w = document.getElementById('categorySelectWrapper');
        if (w && !w.contains(e.target)) w.classList.remove('open');
    });
    window.addEventListener('DOMContentLoaded', () => {
        const sel = document.querySelector('#categorySelectWrapper .custom-option.selected');
        if (sel) {
            const t = document.getElementById('categorySelectText');
            t.textContent = sel.textContent.trim();
            sel.dataset.value !== '' ? t.classList.remove('text-placeholder') : t.classList.add('text-placeholder');
        }
    });

    let formToSubmit = null;
    function showDeleteModal(form) {
        formToSubmit = form;
        const modal = document.getElementById('deleteConfirmModal');
        modal.style.display = 'flex';
    }
    function closeDeleteModal() {
        document.getElementById('deleteConfirmModal').style.display = 'none';
        formToSubmit = null;
    }
    function confirmDelete() {
        if (formToSubmit) formToSubmit.submit();
    }
</script>
@endpush

@endsection
