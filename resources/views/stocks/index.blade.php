@extends('layouts.dashboard')

@section('title')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Stok</h1>
        <p class="page-subtitle">Stok harian tanpa riwayat. Data otomatis refresh setiap hari.</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: 320px 1fr; gap: 24px; align-items: start;">
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 20px;">
                <h3 class="card-title" style="font-size: 17px; display: flex; align-items: center; gap: 8px;">
                    <div style="color: var(--accent);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="12" y1="8" x2="12" y2="16"></line><line x1="8" y1="12" x2="16" y2="12"></line></svg>
                    </div>
                    Input Stok Harian
                </h3>
            </div>
            <form action="{{ route('stocks.store') }}" method="POST">
                @csrf
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Tanggal Hari Ini</label>
                        <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($today)->translatedFormat('d F Y') }}" readonly>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div>
                            <label for="stock_5kg" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Stok 5kg <span style="color: #EF4444;">*</span></label>
                            <input type="number" id="stock_5kg" name="stock_5kg" class="form-control" min="0" value="{{ old('stock_5kg', $stock?->stock_5kg ?? 0) }}" placeholder="Contoh: 25" required>
                            @error('stock_5kg')<div style="color: #EF4444; font-size: 13px; margin-top: 4px;">{{ $message }}</div>@enderror
                        </div>
                        <div>
                            <label for="stock_20kg" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">Stok 20kg <span style="color: #EF4444;">*</span></label>
                            <input type="number" id="stock_20kg" name="stock_20kg" class="form-control" min="0" value="{{ old('stock_20kg', $stock?->stock_20kg ?? 0) }}" placeholder="Contoh: 12" required>
                            @error('stock_20kg')<div style="color: #EF4444; font-size: 13px; margin-top: 4px;">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div style="margin-top: 8px;">
                        <button type="submit" class="btn btn-primary" style="width: 100%; display: flex; justify-content: center; gap: 8px;">
                            Simpan Stok
                        </button>
                    </div>
                </div>
                <div style="margin-top: 16px; padding: 12px; background: #FFFBEB; border: 1px solid #FEF3C7; border-radius: 8px; color: #B45309; font-size: 13px; display: flex; gap: 8px; align-items: flex-start;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span>Input admin dan supir berlaku per hari. Data hari sebelumnya otomatis dibersihkan.</span>
                </div>
            </form>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="card" style="margin-bottom: 0;">
            <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 0;">
                <h3 class="card-title" style="font-size: 17px; display: flex; align-items: center; gap: 8px;">
                    <div style="color: var(--accent);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                    </div>
                    Stok Utama Hari Ini
                </h3>
            </div>
            <div style="padding: 20px;">
                <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px;">
                    <div style="background: rgba(59, 130, 246, 0.08); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 10px; padding: 14px;">
                        <div style="font-size: 12px; color: #1D4ED8; margin-bottom: 8px; font-weight: 600;">STOK 5KG</div>
                        <div id="main-stock-5kg" style="font-size: 24px; font-weight: 800; color: #1E3A8A;">{{ number_format($stock?->stock_5kg ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div style="background: rgba(14, 165, 233, 0.08); border: 1px solid rgba(14, 165, 233, 0.2); border-radius: 10px; padding: 14px;">
                        <div style="font-size: 12px; color: #0369A1; margin-bottom: 8px; font-weight: 600;">STOK 20KG</div>
                        <div id="main-stock-20kg" style="font-size: 24px; font-weight: 800; color: #0C4A6E;">{{ number_format($stock?->stock_20kg ?? 0, 0, ',', '.') }}</div>
                    </div>
                    <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 10px; padding: 14px;">
                        <div style="font-size: 12px; color: #047857; margin-bottom: 8px; font-weight: 600;">TOTAL</div>
                        <div id="main-stock-total" style="font-size: 24px; font-weight: 800; color: #065F46;">{{ number_format(($stock?->stock_5kg ?? 0) + ($stock?->stock_20kg ?? 0), 0, ',', '.') }}</div>
                    </div>
                </div>
                <div id="main-stock-updated-at" style="margin-top: 12px; font-size: 13px; color: var(--text-muted);">
                    @if($stock)
                        Diperbarui {{ $stock->updated_at->diffForHumans() }}
                    @else
                        Belum ada input stok admin untuk hari ini.
                    @endif
                </div>
            </div>
        </div>

        <div class="card" style="margin-bottom: 0;">
            <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 0;">
                <h3 class="card-title" style="font-size: 17px; display: flex; align-items: center; gap: 8px;">
                    <div style="color: var(--accent);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 22h14"></path><path d="M5 2h14"></path><path d="M17 22V2"></path><path d="M7 22V2"></path><path d="M7 12h10"></path></svg>
                    </div>
                    Sisa Bawaan Supir Hari Ini
                </h3>
            </div>

            <div class="table-responsive">
                <table class="table" style="margin-bottom: 0;">
                    <thead>
                        <tr>
                            <th>TANGGAL</th>
                            <th>NAMA SUPIR</th>
                            <th>SISA 5KG</th>
                            <th>SISA 20KG</th>
                            <th>UPDATE TERAKHIR</th>
                        </tr>
                    </thead>
                    <tbody id="driver-stock-table-body">
                        @forelse($driverStocks as $driverStock)
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: var(--text-main); font-size: 14px;">{{ $driverStock->date->format('d M Y') }}</div>
                                </td>
                                <td>
                                    <div style="font-weight: 500; color: var(--text-main); font-size: 15px;">{{ $driverStock->driver?->name ?? '-' }}</div>
                                </td>
                                <td><span style="font-size: 14px; font-weight: 600; color: #475569;">{{ number_format($driverStock->stock_5kg, 0, ',', '.') }}</span></td>
                                <td><span style="font-size: 14px; font-weight: 600; color: #475569;">{{ number_format($driverStock->stock_20kg, 0, ',', '.') }}</span></td>
                                <td style="font-size: 13px; color: var(--text-muted);">{{ $driverStock->updated_at->format('H:i:s') }}</td>
                            </tr>
                        @empty
                            <tr id="driver-stock-empty-row">
                                <td colspan="5" style="text-align: center; padding: 24px; color: var(--text-muted); font-size: 14px;">Belum ada input stok supir untuk hari ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    (() => {
        const formatter = new Intl.NumberFormat('id-ID');
        const realtimeUrl = '{{ route('stocks.realtime.today') }}';
        const tableBody = document.getElementById('driver-stock-table-body');
        const mainStock5 = document.getElementById('main-stock-5kg');
        const mainStock20 = document.getElementById('main-stock-20kg');
        const mainStockTotal = document.getElementById('main-stock-total');
        const mainStockUpdatedAt = document.getElementById('main-stock-updated-at');

        const formatDate = (value) => {
            if (!value) {
                return '-';
            }

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return value;
            }

            return date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            });
        };

        const formatTime = (value) => {
            if (!value) {
                return '-';
            }

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return '-';
            }

            return date.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        };

        const renderMainStock = (stock) => {
            const stock5 = Number(stock?.stock_5kg ?? 0);
            const stock20 = Number(stock?.stock_20kg ?? 0);
            const total = stock5 + stock20;

            mainStock5.textContent = formatter.format(stock5);
            mainStock20.textContent = formatter.format(stock20);
            mainStockTotal.textContent = formatter.format(total);

            if (stock?.has_stock_input) {
                mainStockUpdatedAt.textContent = `Diperbarui pukul ${formatTime(stock.updated_at)}`;
                return;
            }

            mainStockUpdatedAt.textContent = 'Belum ada input stok admin untuk hari ini.';
        };

        const renderDriverStocks = (rows = []) => {
            if (!Array.isArray(rows) || rows.length === 0) {
                tableBody.innerHTML = `
                    <tr id="driver-stock-empty-row">
                        <td colspan="5" style="text-align: center; padding: 24px; color: var(--text-muted); font-size: 14px;">Belum ada input stok supir untuk hari ini.</td>
                    </tr>
                `;
                return;
            }

            tableBody.innerHTML = rows.map((row) => `
                <tr>
                    <td><div style="font-weight: 600; color: var(--text-main); font-size: 14px;">${formatDate(row.date)}</div></td>
                    <td><div style="font-weight: 500; color: var(--text-main); font-size: 15px;">${row.driver_name ?? '-'}</div></td>
                    <td><span style="font-size: 14px; font-weight: 600; color: #475569;">${formatter.format(Number(row.stock_5kg ?? 0))}</span></td>
                    <td><span style="font-size: 14px; font-weight: 600; color: #475569;">${formatter.format(Number(row.stock_20kg ?? 0))}</span></td>
                    <td style="font-size: 13px; color: var(--text-muted);">${formatTime(row.updated_at)}</td>
                </tr>
            `).join('');
        };

        const refreshRealtimeStock = async () => {
            try {
                const response = await fetch(realtimeUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                const data = payload?.data ?? {};

                renderMainStock(data.stock ?? {});
                renderDriverStocks(data.driver_stocks ?? []);
            } catch (error) {
                // Silent fail to avoid interrupting admin workflow.
            }
        };

        setInterval(refreshRealtimeStock, 5000);
    })();
</script>

@endsection
