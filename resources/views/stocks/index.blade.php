@extends('layouts.dashboard')

@section('title', 'Stok')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Stok</h1>
        <p class="page-subtitle" style="margin-bottom: 24px;">Input stok es harian (5kg dan 20kg).</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Input Stok Harian</h3>
    </div>

    <div class="card-body">
        <form action="{{ route('stocks.store') }}" method="POST">
            @csrf
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; align-items: end;">
                <div>
                    <label for="date" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Tanggal <span style="color: #ef4444;">*</span></label>
                    <input
                        type="date"
                        id="date"
                        name="date"
                        class="form-control"
                        value="{{ old('date', now()->format('Y-m-d')) }}"
                        required
                    >
                    @error('date')
                        <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="stock_5kg" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Stok Es 5kg <span style="color: #ef4444;">*</span></label>
                    <input
                        type="number"
                        id="stock_5kg"
                        name="stock_5kg"
                        class="form-control"
                        min="0"
                        value="{{ old('stock_5kg', 0) }}"
                        placeholder="Contoh: 25"
                        required
                    >
                    @error('stock_5kg')
                        <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <label for="stock_20kg" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Stok Es 20kg <span style="color: #ef4444;">*</span></label>
                    <input
                        type="number"
                        id="stock_20kg"
                        name="stock_20kg"
                        class="form-control"
                        min="0"
                        value="{{ old('stock_20kg', 0) }}"
                        placeholder="Contoh: 12"
                        required
                    >
                    @error('stock_20kg')
                        <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                    @enderror
                </div>

                <div style="display: flex; justify-content: flex-end;">
                    <button type="submit" class="btn btn-primary" style="width: 100%; max-width: 220px;">
                        Simpan Stok
                    </button>
                </div>
            </div>
            <p style="margin-top: 12px; color: var(--text-muted); font-size: 13px;">
                Jika tanggal sudah pernah diinput, data akan diperbarui.
            </p>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <h3 class="card-title">Riwayat Stok</h3>

        <form action="{{ route('stocks.index') }}" method="GET" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <div>
                <input
                    type="month"
                    name="month"
                    class="form-control"
                    value="{{ request('month') }}"
                    style="min-width: 190px;"
                >
            </div>
            <button type="submit" class="btn btn-primary">Filter</button>
            @if(request('month'))
                <a href="{{ route('stocks.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>
    </div>

    <div class="card-body">
        @if($stocks->isEmpty())
            <div style="text-align: center; padding: 32px; color: var(--text-muted);">
                Belum ada data stok.
            </div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Stok Es 5kg</th>
                            <th>Stok Es 20kg</th>
                            <th>Total Karung</th>
                            <th>Diperbarui</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($stocks as $stock)
                            <tr>
                                <td style="font-weight: 600;">{{ $stock->date->format('d M Y') }}</td>
                                <td>{{ number_format($stock->stock_5kg, 0, ',', '.') }}</td>
                                <td>{{ number_format($stock->stock_20kg, 0, ',', '.') }}</td>
                                <td style="font-weight: 600; color: var(--text-main);">
                                    {{ number_format($stock->stock_5kg + $stock->stock_20kg, 0, ',', '.') }}
                                </td>
                                <td>{{ $stock->updated_at->format('d M Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Input Stok Bawaan Supir</h3>
    </div>

    <div class="card-body">
        @if($driverStocks->isEmpty())
            <div style="text-align: center; padding: 32px; color: var(--text-muted);">
                Belum ada input stok dari supir.
            </div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Supir</th>
                            <th>Stok 5kg</th>
                            <th>Stok 20kg</th>
                            <th>Total Karung</th>
                            <th>Diperbarui</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($driverStocks as $driverStock)
                            <tr>
                                <td style="font-weight: 600;">{{ $driverStock->date->format('d M Y') }}</td>
                                <td>{{ $driverStock->driver?->name ?? '-' }}</td>
                                <td>{{ number_format($driverStock->stock_5kg, 0, ',', '.') }}</td>
                                <td>{{ number_format($driverStock->stock_20kg, 0, ',', '.') }}</td>
                                <td style="font-weight: 600; color: var(--text-main);">
                                    {{ number_format($driverStock->stock_5kg + $driverStock->stock_20kg, 0, ',', '.') }}
                                </td>
                                <td>{{ $driverStock->updated_at->format('d M Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
