@extends('layouts.dashboard')

@section('title', 'Tambah Pengeluaran')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Tambah Pengeluaran</h1>
        <p class="page-subtitle" style="margin-bottom: 24px;">Input data pengeluaran baru</p>
    </div>
    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 6px;">
            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
        </svg>
        Kembali
    </a>
</div>

<div class="card" style="max-width: 600px;">
    <div class="card-header">
        <h3 class="card-title">Form Pengeluaran</h3>
    </div>
    <div class="card-body">
        <form action="{{ route('expenses.store') }}" method="POST">
            @csrf
            
            <div style="margin-bottom: 24px;">
                <label for="date" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Tanggal <span style="color: #ef4444;">*</span></label>
                <input type="date" id="date" name="date" class="form-control @error('date') is-invalid @enderror" value="{{ old('date', date('Y-m-d')) }}" required style="width: 100%;">
                @error('date')
                    <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-bottom: 24px;">
                <label for="name" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Nama Pengeluaran <span style="color: #ef4444;">*</span></label>
                <input type="text" id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="Contoh: Beli bensin mobil box" required style="width: 100%;">
                @error('name')
                    <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-bottom: 24px;">
                <label for="expense_category_id" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Kategori <span style="color: #ef4444;">*</span></label>
                <select id="expense_category_id" name="expense_category_id" class="form-select @error('expense_category_id') is-invalid @enderror" required style="width: 100%;">
                    <option value="">-- Pilih Kategori --</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ old('expense_category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('expense_category_id')
                    <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-bottom: 24px;">
                <label for="amount" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Jumlah (Rp) <span style="color: #ef4444;">*</span></label>
                <input type="number" id="amount" name="amount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" placeholder="Contoh: 150000" min="0" required style="width: 100%;">
                @error('amount')
                    <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-bottom: 24px;">
                <label for="notes" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Catatan (Opsional)</label>
                <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="3" placeholder="Tambahkan catatan jika perlu" style="width: 100%;">{{ old('notes') }}</textarea>
                @error('notes')
                    <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
                <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Pengeluaran</button>
            </div>
        </form>
    </div>
</div>
@endsection
