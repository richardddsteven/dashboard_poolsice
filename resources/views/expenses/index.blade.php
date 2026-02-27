@extends('layouts.dashboard')

@section('title', 'Pengeluaran')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Pengeluaran</h1>
        <p class="page-subtitle" style="margin-bottom: 24px;">Manajemen data pengeluaran operasional</p>
    </div>
    <a href="{{ route('expenses.create') }}" class="btn btn-primary" style="margin-bottom: 16px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 6px;">
            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
        </svg>
        Tambah Pengeluaran
    </a>
</div>

@if(session('success'))
<div class="alert alert-success" style="background-color: #d1fae5; color: #065f46; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; border: 1px solid #a7f3d0;">
    {{ session('success') }}
</div>
@endif

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <h3 class="card-title">Daftar Pengeluaran</h3>
        
        <form action="{{ route('expenses.index') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="position: relative;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau catatan..." class="form-control" style="padding-left: 36px; width: 250px;">
            </div>
            
            <div>
                <select name="category" class="form-select" style="min-width: 180px;">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Cari</button>
            @if(request('search') || request('category'))
                <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>
    </div>
    <div class="card-body">
        @if($expenses->isEmpty())
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="#CBD5E1" style="margin-bottom: 16px;">
                    <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                </svg>
                <p>Belum ada data pengeluaran.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Pengeluaran</th>
                            <th>Kategori</th>
                            <th>Jumlah</th>
                            <th>Catatan</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expenses as $expense)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($expense->date)->format('d M Y') }}</td>
                            <td>
                                <div style="font-weight: 600; color: var(--text-main);">{{ $expense->name }}</div>
                            </td>
                            <td>
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: #E0E7FF; color: #4338ca;">
                                    {{ $expense->category->name }}
                                </span>
                            </td>
                            <td style="font-weight: 600;">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                            <td style="color: var(--text-muted); font-size: 13px;">{{ $expense->notes ?: '-' }}</td>
                            <td style="text-align: right;">
                                <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                    <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">Edit</a>
                                    <form action="{{ route('expenses.destroy', $expense) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengeluaran ini?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px; color: #ef4444; border-color: #fca5a5; background-color: #fef2f2;">Hapus</button>
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
@endsection
