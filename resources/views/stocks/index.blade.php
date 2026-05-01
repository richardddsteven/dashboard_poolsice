@extends('layouts.dashboard')

@section('title')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Stok</h1>
        <p class="page-subtitle">Manajemen stok admin dan supir.</p>
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
                    Input Stok Utama
                </h3>
            </div>
            <form action="{{ route('stocks.store') }}" method="POST">
                @csrf
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label for="stock_date" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">
                            Tanggal
                        </label>
                        <input
                            type="date"
                            id="stock_date"
                            name="date"
                            class="form-control"
                            value="{{ old('date', now()->toDateString()) }}"
                            readonly
                        >
                        <div style="margin-top: 4px; font-size: 12px; color: var(--text-muted);">
                            Tanggal otomatis mengikuti hari ini.
                        </div>
                    </div>


                    @forelse($iceTypes as $iceType)
                        <div>
                            <label for="stock_{{ $iceType->id }}" style="display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; color: var(--text-main);">
                                Stok {{ $iceType->name }} ({{ number_format($iceType->weight, 0, ',', '.') }}kg)
                                <span style="color: #EF4444;">*</span>
                            </label>
                            @php
                                $stockData = $todayStocks->get($iceType->id);
                                $stockQty = is_array($stockData) ? ($stockData['quantity'] ?? 0) : 0;
                            @endphp
                            <input type="number" id="stock_{{ $iceType->id }}" name="stock_{{ $iceType->id }}" class="form-control" min="0" value="{{ old("stock_{$iceType->id}", $stockQty) }}" placeholder="Contoh: 25" required {{ ($hasTodayStockInput ?? false) ? 'disabled aria-disabled=true' : '' }}>
                            @error("stock_{$iceType->id}")<div style="color: #EF4444; font-size: 13px; margin-top: 4px;">{{ $message }}</div>@enderror
                        </div>
                    @empty
                        <div style="padding: 12px; background: #FEE2E2; border: 1px solid #FECACA; border-radius: 8px; color: #B91C1C; font-size: 13px;">
                            Tidak ada jenis es yang aktif. Silakan tambahkan jenis es di halaman Jenis Es.
                        </div>
                    @endforelse

                    <div style="margin-top: 8px;">
                        @php
                            $isSaveDisabled = $iceTypes->isEmpty() || ($hasTodayStockInput ?? false);
                        @endphp
                        <button
                            type="submit"
                            class="btn"
                            style="width: 100%; display: flex; justify-content: center; gap: 8px; background: {{ $isSaveDisabled ? '#CBD5E1' : '#3B82F6' }}; color: {{ $isSaveDisabled ? '#64748B' : '#FFFFFF' }}; border: 1px solid {{ $isSaveDisabled ? '#CBD5E1' : '#3B82F6' }}; cursor: {{ $isSaveDisabled ? 'not-allowed' : 'pointer' }}; opacity: 1;"
                            {{ $isSaveDisabled ? 'disabled aria-disabled=true' : '' }}
                        >
                            Simpan Stok
                        </button>
                    </div>
                </div>
                <div style="margin-top: 16px; padding: 12px; background: #FFFBEB; border: 1px solid #FEF3C7; border-radius: 8px; color: #B45309; font-size: 13px; display: flex; gap: 8px; align-items: flex-start;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 2px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span>{{ ($hasTodayStockInput ?? false) ? 'Stok untuk hari ini sudah tersimpan.' : 'Input stok untuk hari ini.' }}</span>
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
                @if($iceTypes->isEmpty())
                    <div style="padding: 16px; background: #FEE2E2; border: 1px solid #FECACA; border-radius: 8px; color: #B91C1C; font-size: 14px; text-align: center;">
                        Tidak ada jenis es yang aktif. Silakan tambahkan jenis es di halaman Jenis Es.
                    </div>
                @else
                    <div style="display: grid; gap: 12px; grid-auto-flow: dense;">
                        @php
                            $colors = [
                                ['bg' => 'rgba(59, 130, 246, 0.08)', 'border' => 'rgba(59, 130, 246, 0.2)', 'text' => '#1D4ED8', 'dark' => '#1E3A8A'],
                                ['bg' => 'rgba(14, 165, 233, 0.08)', 'border' => 'rgba(14, 165, 233, 0.2)', 'text' => '#0369A1', 'dark' => '#0C4A6E'],
                                ['bg' => 'rgba(34, 197, 94, 0.08)', 'border' => 'rgba(34, 197, 94, 0.2)', 'text' => '#16A34A', 'dark' => '#15803D'],
                                ['bg' => 'rgba(168, 85, 247, 0.08)', 'border' => 'rgba(168, 85, 247, 0.2)', 'text' => '#A855F7', 'dark' => '#9333EA'],
                                ['bg' => 'rgba(249, 115, 22, 0.08)', 'border' => 'rgba(249, 115, 22, 0.2)', 'text' => '#F97316', 'dark' => '#EA580C'],
                            ];
                        @endphp

                        @foreach($iceTypes as $idx => $iceType)
                            @php
                                $colorIdx = $idx % count($colors);
                                $color = $colors[$colorIdx];
                                $stockData = $todayStocks->get($iceType->id);
                                $quantity = is_array($stockData) ? ($stockData['quantity'] ?? 0) : 0;
                            @endphp
                            <div style="background: {{ $color['bg'] }}; border: 1px solid {{ $color['border'] }}; border-radius: 10px; padding: 14px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <div style="font-size: 12px; color: {{ $color['text'] }}; margin-bottom: 6px; font-weight: 600; text-transform: uppercase;">
                                            {{ $iceType->name }}
                                        </div>
                                        <div style="font-size: 24px; font-weight: 800; color: {{ $color['dark'] }};">
                                            <span id="stock-qty-{{ $iceType->id }}">{{ number_format($quantity, 0, ',', '.') }}</span>
                                        </div>
                                    </div>
                                    <div style="font-size: 11px; color: {{ $color['text'] }}; font-weight: 600; background: white; padding: 4px 8px; border-radius: 6px;">
                                        {{ $iceType->weight }}kg
                                    </div>
                                </div>
                            </div>
                        @endforeach

                        {{-- Total Card --}}
                        <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 10px; padding: 14px; grid-column: span 1;">
                            <div style="font-size: 12px; color: #047857; margin-bottom: 8px; font-weight: 600; text-transform: uppercase;">TOTAL</div>
                            <div id="main-stock-total" style="font-size: 28px; font-weight: 800; color: #065F46;">{{ number_format($todayStocks->sum('quantity'), 0, ',', '.') }}</div>
                        </div>
                    </div>

                    <div id="main-stock-updated-at" style="margin-top: 12px; font-size: 13px; color: var(--text-muted);">
                        @if($hasTodayStockInput ?? false)
                            Data stok dimuat
                        @else
                            Belum ada input stok untuk hari ini.
                        @endif
                    </div>
                @endif
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
                            <th>NAMA SUPIR</th>
                            @foreach($iceTypes as $iceType)
                                <th>SISA {{ strtoupper($iceType->name) }}</th>
                            @endforeach
                            <th>UPDATE TERAKHIR</th>
                        </tr>
                    </thead>
                    <tbody id="driver-stock-table-body">
                        @forelse($driverStockRows as $driverStock)
                            <tr>
                                <td>
                                    <div style="font-weight: 500; color: var(--text-main); font-size: 15px;">{{ $driverStock['driver_name'] ?? $driverStock->driver?->name ?? '-' }}</div>
                                </td>
                                @foreach($iceTypes as $iceType)
                                    <td><span style="font-size: 14px; font-weight: 600; color: #475569;">{{ number_format($driverStock['qty_'.$iceType->id] ?? $driverStock->{"qty_{$iceType->id}"} ?? 0, 0, ',', '.') }}</span></td>
                                @endforeach
                                <td style="font-size: 13px; color: var(--text-muted);">{{ \Carbon\Carbon::parse($driverStock['updated_at'] ?? $driverStock->updated_at)->format('H:i') }}</td>
                            </tr>
                        @empty
                            <tr id="driver-stock-empty-row">
                                <td colspan="{{ count($iceTypes) + 2 }}" style="text-align: center; padding: 24px; color: var(--text-muted); font-size: 14px;">Belum ada input stok supir.</td>
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
        const mainStockTotal = document.getElementById('main-stock-total');
        const mainStockUpdatedAt = document.getElementById('main-stock-updated-at');
        const initialHasTodayStockInput = {{ ($hasTodayStockInput ?? false) ? 'true' : 'false' }};

        const getMainStockStatusText = (hasTodayStockInput) => {
            return hasTodayStockInput
                ? 'Data stok dimuat'
                : 'Belum ada input stok untuk hari ini.';
        };

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
            });
        };

        const renderMainStock = (stocks = [], hasTodayStockInput = initialHasTodayStockInput) => {
            let total = 0;

            // Update each ice type stock tile
            for (const stock of stocks) {
                const el = document.getElementById(`stock-qty-${stock.id}`);
                if (el) {
                    el.textContent = formatter.format(Number(stock.quantity ?? 0));
                }
                total += Number(stock.quantity ?? 0);
            }

            // Update total
            mainStockTotal.textContent = formatter.format(total);

            // Update timestamp
            mainStockUpdatedAt.textContent = getMainStockStatusText(hasTodayStockInput);
        };

        const renderDriverStocks = (rows = [], iceTypes = []) => {
            if (!Array.isArray(rows) || rows.length === 0) {
                tableBody.innerHTML = `
                    <tr id="driver-stock-empty-row">
                        <td colspan="${iceTypes.length + 2}" style="text-align: center; padding: 24px; color: var(--text-muted); font-size: 14px;">Belum ada input stok supir.</td>
                    </tr>
                `;
                return;
            }

            // Update table header with dynamic ice type columns
            const thead = tableBody.closest('table')?.querySelector('thead tr');
            if (thead && iceTypes.length > 0) {
                let headerHtml = `<th>NAMA SUPIR</th>`;
                iceTypes.forEach(iceType => {
                    headerHtml += `<th>SISA ${iceType.name.toUpperCase()}</th>`;
                });
                headerHtml += `<th>UPDATE TERAKHIR</th>`;
                thead.innerHTML = headerHtml;
            }

            // Render rows with dynamic ice type columns
            tableBody.innerHTML = rows.map((row) => {
                let rowHtml = `
                    <tr>
                        <td><div style="font-weight: 500; color: var(--text-main); font-size: 15px;">${row.driver_name ?? '-'}</div></td>
                `;

                // Render quantity for each ice type (flattened: qty_1, qty_2, etc)
                iceTypes.forEach(iceType => {
                    const quantity = row[`qty_${iceType.id}`] ?? 0;
                    rowHtml += `<td><span style="font-size: 14px; font-weight: 600; color: #475569;">${formatter.format(Number(quantity))}</span></td>`;
                });

                rowHtml += `<td style="font-size: 13px; color: var(--text-muted);">${formatTime(row.updated_at)}</td></tr>`;
                return rowHtml;
            }).join('');
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

                renderMainStock(data.stocks ?? [], Boolean(data.has_today_stock_input));
                renderDriverStocks(data.driver_stocks ?? [], data.ice_types ?? []);
            } catch (error) {
                // Silent fail to avoid interrupting admin workflow.
            }
        };

        setInterval(refreshRealtimeStock, 5000);
    })();
</script>

@endsection
