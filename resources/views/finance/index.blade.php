@extends('layouts.dashboard')

@section('title', 'Keuangan')

@section('content')

<div class="card" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3 class="card-title">Filter Laporan</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('finance.index') }}" id="filterForm" class="filter-container" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display:block; font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:8px;">Periode</label>
                <select name="filter_type" id="filter_type" onchange="updateFilterFields()" class="form-select" style="width: 100%;">
                    <option value="all"   {{ $filterType === 'all'   ? 'selected' : '' }}>Semua (7 Hari Terakhir)</option>
                    <option value="date"  {{ $filterType === 'date'  ? 'selected' : '' }}>Tanggal Spesifik</option>
                    <option value="range" {{ $filterType === 'range' ? 'selected' : '' }}>Rentang Tanggal</option>
                    <option value="month" {{ $filterType === 'month' ? 'selected' : '' }}>Bulanan</option>
                    <option value="year"  {{ $filterType === 'year'  ? 'selected' : '' }}>Tahunan</option>
                </select>
            </div>

            <div id="field_date" style="display:none; flex: 1; min-width: 200px;">
                <label style="display:block; font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:8px;">Tanggal</label>
                <input type="date" name="filter_date" value="{{ $filterDate }}" class="form-control" style="width: 100%;">
            </div>

            <div id="field_start" style="display:none; flex: 1; min-width: 200px;">
                <label style="display:block; font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:8px;">Dari</label>
                <input type="date" name="filter_start" value="{{ $filterStart ?? '' }}" class="form-control" style="width: 100%;">
            </div>
            <div id="field_end" style="display:none; flex: 1; min-width: 200px;">
                <label style="display:block; font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:8px;">Sampai</label>
                <input type="date" name="filter_end" value="{{ $filterEnd ?? '' }}" class="form-control" style="width: 100%;">
            </div>

            <div id="field_month" style="display:none; flex: 1; min-width: 200px;">
                <label style="display:block; font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:8px;">Bulan</label>
                <select name="filter_month" class="form-select" style="width: 100%;">
                    @foreach(range(1,12) as $m)
                    <option value="{{ $m }}" {{ $filterMonth == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('F') }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div id="field_year" style="display:none; flex: 1; min-width: 200px;">
                <label style="display:block; font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:8px;">Tahun</label>
                <select name="filter_year" class="form-select" style="width: 100%;">
                    @foreach(range(date('Y'), date('Y') - 4) as $y)
                    <option value="{{ $y }}" {{ $filterYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary">Terapkan</button>
                @if($filterType !== 'all')
                <a href="{{ route('finance.index') }}" class="btn btn-secondary">Reset</a>
                @endif
                <a href="{{ route('finance.reports') }}" class="btn btn-secondary" style="margin-left: auto;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 6px;">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/>
                        <path d="M14 2v6h6"/>
                    </svg>
                    Export Laporan
                </a>
            </div>
        </form>
    </div>
</div>

<div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <!-- Total Revenue -->
    <div class="card" style="padding: 24px; display: flex; align-items: center; gap: 20px;">
        <div style="width: 56px; height: 56px; border-radius: 16px; background: rgba(34, 197, 94, 0.1); display: flex; align-items: center; justify-content: center; color: #22c55e;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
        </div>
        <div>
            <div style="font-size: 28px; font-weight: 800; color: var(--text-main); letter-spacing: -0.5px;">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
            <div style="font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Total Pendapatan</div>
        </div>
    </div>

    <!-- Total Expense -->
    <div class="card" style="padding: 24px; display: flex; align-items: center; gap: 20px;">
        <div style="width: 56px; height: 56px; border-radius: 16px; background: rgba(239, 68, 68, 0.1); display: flex; align-items: center; justify-content: center; color: #ef4444;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 9h-2V7h-2v5H6v2h2v5h2v-5h2v-2z"/></svg>
        </div>
        <div>
            <div style="font-size: 28px; font-weight: 800; color: var(--text-main); letter-spacing: -0.5px;">Rp {{ number_format($totalExpense, 0, ',', '.') }}</div>
            <div style="font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Total Pengeluaran</div>
        </div>
    </div>

    <!-- Net Profit -->
    <div class="card" style="padding: 24px; display: flex; align-items: center; gap: 20px;">
        <div style="width: 56px; height: 56px; border-radius: 16px; background: rgba(59, 130, 246, 0.1); display: flex; align-items: center; justify-content: center; color: #3b82f6;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/></svg>
        </div>
        <div>
            <div style="font-size: 28px; font-weight: 800; color: var(--text-main); letter-spacing: -0.5px;">Rp {{ number_format($netProfit, 0, ',', '.') }}</div>
            <div style="font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Laba Bersih</div>
        </div>
    </div>

    <!-- Pending Revenue -->
    <div class="card" style="padding: 24px; display: flex; align-items: center; gap: 20px;">
        <div style="width: 56px; height: 56px; border-radius: 16px; background: rgba(234, 179, 8, 0.1); display: flex; align-items: center; justify-content: center; color: #eab308;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2v20l7-7-7-7z"/><path d="M5 10l7 7-7-7z"/></svg>
        </div>
        <div>
            <div style="font-size: 28px; font-weight: 800; color: var(--text-main); letter-spacing: -0.5px;">Rp {{ number_format($pendingRevenue, 0, ',', '.') }}</div>
            <div style="font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Pendapatan Pending</div>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="card" style="padding: 24px; display: flex; align-items: center; gap: 20px;">
        <div style="width: 56px; height: 56px; border-radius: 16px; background: rgba(139, 92, 246, 0.1); display: flex; align-items: center; justify-content: center; color: #8b5cf6;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
        </div>
        <div>
            <div style="font-size: 28px; font-weight: 800; color: var(--text-main); letter-spacing: -0.5px;">{{ $totalOrders }}</div>
            <div style="font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Pesanan Selesai</div>
        </div>
    </div>

    <!-- Pending Orders -->
    <div class="card" style="padding: 24px; display: flex; align-items: center; gap: 20px;">
        <div style="width: 56px; height: 56px; border-radius: 16px; background: rgba(99, 102, 241, 0.1); display: flex; align-items: center; justify-content: center; color: #6366f1;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
        </div>
        <div>
            <div style="font-size: 28px; font-weight: 800; color: var(--text-main); letter-spacing: -0.5px;">{{ $pendingOrders }}</div>
            <div style="font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px;">Pesanan Pending</div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
    <!-- Penjualan per Jenis Es -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Penjualan per Jenis Es</h3>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Jenis Produk</th>
                        <th style="text-align: right;">Terjual</th>
                        <th style="text-align: right;">Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salesByIceType as $sale)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 8px; height: 8px; border-radius: 50%; background: var(--accent-blue);"></div>
                                <span style="font-weight: 600;">{{ $sale->name }}</span>
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <span style="display: inline-block; padding: 4px 10px; border-radius: 20px; background: rgba(59, 130, 246, 0.1); color: var(--accent-blue); font-size: 12px; font-weight: 700;">{{ $sale->total_quantity }} pcs</span>
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #22c55e;">
                            Rp {{ number_format($sale->total_revenue, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            Belum ada data penjualan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Penjualan Harian -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Tren Penjualan ({{ ucfirst($filterLabel ?? 'Harian') }})</h3>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th style="text-align: center;">Order</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dailySales as $daily)
                    <tr>
                        <td>
                            <span style="font-weight: 600; color: var(--text-main);">{{ \Carbon\Carbon::parse($daily->date)->format('d M Y') }}</span>
                        </td>
                        <td style="text-align: center;">
                            <span style="display: inline-block; padding: 4px 10px; border-radius: 20px; background: rgba(99, 102, 241, 0.1); color: #6366f1; font-size: 12px; font-weight: 700;">{{ $daily->total_orders }}</span>
                        </td>
                        <td style="text-align: right; font-weight: 700; color: #22c55e;">
                            Rp {{ number_format($daily->total_revenue, 0, ',', '.') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 30px; color: var(--text-muted);">
                            Belum ada data penjualan
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function updateFilterFields() {
        const type = document.getElementById('filter_type').value;
        document.getElementById('field_date').style.display  = (type === 'date')  ? 'block' : 'none';
        document.getElementById('field_start').style.display = (type === 'range') ? 'block' : 'none';
        document.getElementById('field_end').style.display   = (type === 'range') ? 'block' : 'none';
        document.getElementById('field_month').style.display = (type === 'month') ? 'block' : 'none';
        document.getElementById('field_year').style.display  = (type === 'month' || type === 'year') ? 'block' : 'none';
    }
    document.addEventListener('DOMContentLoaded', updateFilterFields);
</script>
@endpush

@endsection