@extends('layouts.dashboard')

@section('title', 'Laporan Keuangan')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Laporan Keuangan</h1>
        <p class="page-subtitle" style="margin-bottom: 24px;">Laporan detail keuangan dan penjualan</p>
    </div>
    <div class="page-actions" style="margin-bottom: 16px;">
        <a href="{{ route('finance.index') }}" class="btn btn-secondary" style="margin-right: 6px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M19 12H5"/>
                <path d="M12 19L5 12L12 5"/>
            </svg>
            Kembali
        </a>
        <button class="btn btn-primary" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/>
            </svg>
            Print Laporan
        </button>
    </div>
</div>

<!-- Filter -->
<div class="card no-print" style="margin-bottom: 24px;">
    <div class="card-header">
        <h3 class="card-title">Filter Laporan</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('finance.reports') }}" id="filterForm" class="filter-container" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end;">
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
                <input type="date" name="filter_date" value="{{ $filterDate ?? '' }}" class="form-control" style="width: 100%;">
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
                    <option value="{{ $m }}" {{ ($filterMonth ?? '') == $m ? 'selected' : '' }}>
                        {{ \Carbon\Carbon::createFromDate(null, $m, 1)->format('F') }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div id="field_year" style="display:none; flex: 1; min-width: 200px;">
                <label style="display:block; font-size:12px; font-weight:700; color:var(--text-muted); margin-bottom:8px;">Tahun</label>
                <select name="filter_year" class="form-select" style="width: 100%;">
                    @foreach(range(date('Y'), date('Y') - 4) as $y)
                    <option value="{{ $y }}" {{ ($filterYear ?? '') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>

            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary">Terapkan</button>
                @if($filterType !== 'all')
                <a href="{{ route('finance.reports') }}" class="btn btn-secondary">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="report-header">
            <div class="report-logo">
                <h2>Pools Ice</h2>
                <p>Laporan Keuangan</p>
            </div>
            <div class="report-date">
                <p>Tanggal Cetak: {{ now()->format('d F Y') }}</p>
                <p>Periode: <strong>{{ $filterLabel }}</strong></p>
            </div>
        </div>

        <div class="report-section">
            <h3>Ringkasan Keuangan</h3>
            <div class="report-summary">
                <div class="summary-item">
                    <span class="summary-label">Total Pendapatan (Selesai):</span>
                    <span class="summary-value text-success">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Pengeluaran:</span>
                    <span class="summary-value text-danger">Rp {{ number_format($totalExpense, 0, ',', '.') }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Laba Bersih:</span>
                    <span class="summary-value text-primary">Rp {{ number_format($netProfit, 0, ',', '.') }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Pendapatan Pending:</span>
                    <span class="summary-value text-warning">Rp {{ number_format($pendingRevenue, 0, ',', '.') }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Total Pesanan Selesai:</span>
                    <span class="summary-value">{{ $totalOrders }}</span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">Pesanan Pending:</span>
                    <span class="summary-value">{{ $pendingOrders }}</span>
                </div>
            </div>
        </div>

        <div class="report-section">
            <h3>Penjualan per Jenis Es</h3>
            @if($salesByIceType->count() > 0)
            <div class="table-responsive">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Jenis Es</th>
                            <th>Total Quantity</th>
                            <th>Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($salesByIceType as $sale)
                        <tr>
                            <td><strong>{{ $sale->name }}</strong></td>
                            <td>{{ $sale->total_quantity }} pcs</td>
                            <td class="text-success">Rp {{ number_format($sale->total_revenue, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="report-note">
                <p><em>Data akan ditampilkan setelah ada pesanan yang selesai</em></p>
            </div>
            @endif
        </div>

        <div class="report-section">
            <h3>Riwayat Penjualan — {{ $filterLabel }}</h3>
            @if($dailySales->count() > 0)
            <div class="table-responsive">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jumlah Order</th>
                            <th>Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dailySales as $daily)
                        <tr>
                            <td><strong>{{ \Carbon\Carbon::parse($daily->date)->format('d M Y') }}</strong></td>
                            <td>{{ $daily->total_orders }} orders</td>
                            <td class="text-success">Rp {{ number_format($daily->total_revenue, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="report-note">
                <p><em>Data penjualan harian akan ditampilkan setelah ada transaksi</em></p>
            </div>
            @endif
        </div>

        <div class="report-footer">
            <p>Laporan ini dibuat secara otomatis oleh sistem Pools Ice</p>
            <p>{{ now()->format('d F Y, H:i') }} WIB</p>
        </div>
    </div>
</div>

<style>
@media print {
    .topbar, .sidebar, .page-actions, .btn, .no-print {
        display: none !important;
    }
    
    .main-content {
        margin-left: 0 !important;
    }
    
    .card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
}

.report-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 32px;
    padding-bottom: 16px;
    border-bottom: 2px solid #667eea;
}

.report-logo h2 {
    font-size: 24px;
    font-weight: 700;
    color: #1C1C1A;
    margin: 0;
}

.report-logo p {
    color: #666;
    margin: 4px 0 0 0;
}

.report-date p {
    color: #666;
    margin: 0;
    text-align: right;
}

.report-section {
    margin-bottom: 32px;
}

.report-section h3 {
    font-size: 18px;
    font-weight: 600;
    color: #1C1C1A;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid #eee;
}

.report-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 16px;
}

.summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
}

.summary-label {
    font-weight: 500;
    color: #666;
}

.summary-value {
    font-weight: 600;
    color: #1C1C1A;
}

.text-success {
    color: #27ae60 !important;
}

.text-warning {
    color: #f39c12 !important;
}

.text-danger {
    color: #e74c3c !important;
}

.text-primary {
    color: #3498db !important;
}

.table-responsive {
    overflow-x: auto;
    margin: 16px 0;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    margin: 16px 0;
}

.report-table th,
.report-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.report-table th {
    font-weight: 600;
    color: #666;
    font-size: 14px;
    background: #f8f9fa;
}

.report-note {
    background: #f8f9fa;
    padding: 16px;
    border-radius: 8px;
    border-left: 4px solid #667eea;
}

.report-note p {
    margin: 0;
    color: #666;
}

.report-footer {
    margin-top: 48px;
    padding-top: 16px;
    border-top: 1px solid #eee;
    text-align: center;
    color: #666;
}

.report-footer p {
    margin: 4px 0;
    font-size: 14px;
}
</style>
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
@endsection