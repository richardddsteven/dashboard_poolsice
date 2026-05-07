@extends('layouts.dashboard')
@section('title')
@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Tambah Pengeluaran</h1>
        <p class="page-subtitle">Input data pengeluaran baru</p>
    </div>
    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">
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
    <form method="POST" action="{{ route('expenses.store') }}">
        @csrf
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <div>
                <label for="name" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Nama Pengeluaran <span style="color: #EF4444;">*</span></label>
                <input type="text" id="name" name="name" class="form-control" value="{{ old('name') }}" placeholder="Contoh: Bahan Bakar Solar" required>
            </div>
            <div>
                <label for="expense_category_id" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Kategori <span style="color: #EF4444;">*</span></label>
                <div class="custom-select-wrapper" id="categorySelectWrapper">
                    <div class="custom-select-trigger" onclick="document.getElementById('categorySelectWrapper').classList.toggle('open')">
                        <span id="categorySelectText" class="text-placeholder">Pilih kategori</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
                    </div>
                    <div class="custom-options">
                        @foreach($categories as $category)
                            <div class="custom-option {{ old('expense_category_id') == $category->id ? 'selected' : '' }}" data-value="{{ $category->id }}" onclick="selectCategory(this)">{{ $category->name }}</div>
                        @endforeach
                    </div>
                    <input type="hidden" name="expense_category_id" id="categoryInput" value="{{ old('expense_category_id') }}">
                </div>
            </div>
            <div>
                <label for="amount" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Jumlah (Rp) <span style="color: #EF4444;">*</span></label>
                <input type="number" id="amount" name="amount" class="form-control" value="{{ old('amount') }}" min="0" placeholder="Contoh: 150000" required>
            </div>
            <div>
                <label for="date" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Tanggal <span style="color: #EF4444;">*</span></label>
                <input type="date" id="date" name="date" class="form-control" value="{{ old('date', now()->format('Y-m-d')) }}" required>
            </div>
            <div>
                <label for="notes" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Catatan</label>
                <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Catatan tambahan (opsional)">{{ old('notes') }}</textarea>
            </div>
            <div style="padding-top: 8px;">
                <button type="submit" class="btn btn-primary">Simpan Pengeluaran</button>
            </div>
        </div>
    </form>
</div>
<script>
function selectCategory(el) {
    document.getElementById('categoryInput').value = el.dataset.value;
    const t = document.getElementById('categorySelectText');
    t.textContent = el.textContent.trim(); t.classList.remove('text-placeholder');
    document.querySelectorAll('#categorySelectWrapper .custom-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected'); document.getElementById('categorySelectWrapper').classList.remove('open');
}
document.addEventListener('click', function(e) { const w = document.getElementById('categorySelectWrapper'); if (w && !w.contains(e.target)) w.classList.remove('open'); });
window.addEventListener('DOMContentLoaded', () => {
    const sel = document.querySelector('#categorySelectWrapper .custom-option.selected');
    if (sel) { document.getElementById('categorySelectText').textContent = sel.textContent.trim(); document.getElementById('categorySelectText').classList.remove('text-placeholder'); }
});
</script>
@endsection
