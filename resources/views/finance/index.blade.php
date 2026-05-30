@extends('layouts.dashboard')

@section('title')

@section('content')

<div class="page-header" style="margin-bottom: 2rem;">
    <div>
        <h1 class="page-title" style="margin-bottom: 4px; font-size: 1.5rem; font-weight: 700; color: var(--primary-blue);">Keuangan</h1>
        <p class="page-subtitle" style="margin-bottom: 0; color: var(--text-muted); font-size: 0.875rem;">Manajemen Informasi Keuangan</p>
    </div>
</div>

<form method="GET" action="{{ route('finance.index') }}" id="filterForm" class="finance-filter-header">
    <h3 class="finance-filter-title">Filter Laporan</h3>
    <div class="finance-filter-controls" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <!-- Custom Select for Filter Type -->
        <div class="custom-select-wrapper" id="financeFilterSelectWrapper" style="min-width: 170px; width: auto;">
            <div class="custom-select-trigger" onclick="toggleFinanceFilterSelect('financeFilterSelectWrapper')">
                <span>Semua</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
            </div>
            <div class="custom-options">
                <div class="custom-option {{ $filterType === 'all' ? 'selected' : '' }}" data-value="all" onclick="selectFinanceFilterOption(this, 'financeFilterSelectWrapper', 'filter_type')">Semua</div>
                <div class="custom-option {{ $filterType === 'date' ? 'selected' : '' }}" data-value="date" onclick="selectFinanceFilterOption(this, 'financeFilterSelectWrapper', 'filter_type')">Tanggal Spesifik</div>
                <div class="custom-option {{ $filterType === 'range' ? 'selected' : '' }}" data-value="range" onclick="selectFinanceFilterOption(this, 'financeFilterSelectWrapper', 'filter_type')">Rentang Tanggal</div>
                <div class="custom-option {{ $filterType === 'month' ? 'selected' : '' }}" data-value="month" onclick="selectFinanceFilterOption(this, 'financeFilterSelectWrapper', 'filter_type')">Bulanan</div>
                <div class="custom-option {{ $filterType === 'year' ? 'selected' : '' }}" data-value="year" onclick="selectFinanceFilterOption(this, 'financeFilterSelectWrapper', 'filter_type')">Tahunan</div>
            </div>
            <input type="hidden" name="filter_type" id="filter_type" value="{{ $filterType }}">
        </div>

        <input id="field_date" type="date" name="filter_date" value="{{ $filterDate }}" class="form-control" style="min-width: 160px; max-width: 180px; display: none;">
        <input id="field_start" type="date" name="filter_start" value="{{ $filterStart ?? '' }}" class="form-control" aria-label="Dari tanggal" style="min-width: 160px; max-width: 180px; display: none;">
        <input id="field_end" type="date" name="filter_end" value="{{ $filterEnd ?? '' }}" class="form-control" aria-label="Sampai tanggal" style="min-width: 160px; max-width: 180px; display: none;">

        <!-- Custom Select for Month -->
        <div class="custom-select-wrapper" id="financeMonthSelectWrapper" style="min-width: 150px; width: auto; display: none;">
            <div class="custom-select-trigger" onclick="toggleFinanceFilterSelect('financeMonthSelectWrapper')">
                <span>Januari</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
            </div>
            <div class="custom-options">
                @foreach(range(1,12) as $m)
                    @php $mLabel = \Carbon\Carbon::create()->month($m)->locale('id')->translatedFormat('F'); @endphp
                    <div class="custom-option {{ $filterMonth == $m ? 'selected' : '' }}" data-value="{{ $m }}" onclick="selectFinanceFilterOption(this, 'financeMonthSelectWrapper', 'filter_month')">{{ $mLabel }}</div>
                @endforeach
            </div>
            <input type="hidden" name="filter_month" id="filter_month" value="{{ $filterMonth }}">
        </div>

        <!-- Custom Select for Year -->
        <div class="custom-select-wrapper" id="financeYearSelectWrapper" style="min-width: 120px; width: auto; display: none;">
            <div class="custom-select-trigger" onclick="toggleFinanceFilterSelect('financeYearSelectWrapper')">
                <span>{{ $filterYear ?: date('Y') }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
            </div>
            <div class="custom-options">
                @foreach(range(date('Y') + 1, date('Y') - 4) as $y)
                    <div class="custom-option {{ ($filterYear ?: date('Y')) == $y ? 'selected' : '' }}" data-value="{{ $y }}" onclick="selectFinanceFilterOption(this, 'financeYearSelectWrapper', 'filter_year')">{{ $y }}</div>
                @endforeach
            </div>
            <input type="hidden" name="filter_year" id="filter_year" value="{{ $filterYear ?: date('Y') }}">
        </div>

        <button type="submit" class="btn btn-primary" style="padding: 10px 20px;">Terapkan</button>
        @if($filterType !== 'all')
        <a href="{{ route('finance.index') }}" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
        @endif
        <a href="{{ route('finance.reports') }}" class="btn btn-secondary" style="padding: 10px 20px;">
            Export
        </a>
    </div>
</form>

<!-- Stats Grid (Minimalist) -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 2rem;">
    <!-- Revenue -->
    <div class="card" style="margin-bottom: 0; padding: 24px;">
        <div style="color: var(--text-muted); font-size: 0.875rem; font-weight: 500; margin-bottom: 8px;">Pendapatan</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-blue);">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</div>
    </div>
    
    <!-- Expenses -->
    <div class="card" style="margin-bottom: 0; padding: 24px;">
        <div style="color: var(--text-muted); font-size: 0.875rem; font-weight: 500; margin-bottom: 8px;">Pengeluaran</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-blue);">Rp {{ number_format($totalExpense, 0, ',', '.') }}</div>
    </div>
    
    <!-- Net Profit -->
    <div class="card" style="margin-bottom: 0; padding: 24px;">
        <div style="color: var(--text-muted); font-size: 0.875rem; font-weight: 500; margin-bottom: 8px;">Laba Bersih</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: {{ $netProfit >= 0 ? '#10B981' : '#EF4444' }};">Rp {{ number_format($netProfit, 0, ',', '.') }}</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 2rem;">
    <!-- Total Orders -->
    <div class="card" style="margin-bottom: 0; padding: 24px;">
        <div style="color: var(--text-muted); font-size: 0.875rem; font-weight: 500; margin-bottom: 8px;">Pesanan Selesai</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-blue);">{{ $totalOrders }}</div>
    </div>
    
    <!-- Pending Orders -->
    <div class="card" style="margin-bottom: 0; padding: 24px;">
        <div style="color: var(--text-muted); font-size: 0.875rem; font-weight: 500; margin-bottom: 8px;">Pesanan Dibatalkan/Tertunda</div>
        <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary-blue);">{{ $pendingOrders }}</div>
    </div>
</div>

<!-- Charts -->
@push('styles')
<style>
    .finance-filter-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 24px;
    }
    .finance-filter-title {
        font-size: 18px;
        font-weight: 700;
        color: #1E293B;
        letter-spacing: -0.2px;
        margin: 0;
    }
    .finance-filter-controls {
        display: flex;
        gap: 8px;
        align-items: center;
        flex-wrap: wrap;
    }
    .dash-custom-select,
    .dash-custom-input {
        min-height: 34px;
        padding: 6px 32px 6px 12px;
        border-radius: 8px;
        border: 1px solid #CBD5E1;
        color: #475569;
        font-weight: 500;
        font-family: inherit;
        font-size: 14px;
        background-color: #fff;
        transition: all 0.2s;
    }
    .dash-custom-select {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 8px center;
        background-size: 16px;
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        cursor: pointer;
    }
    .dash-custom-input {
        padding-right: 12px;
    }
    .dash-custom-select:hover,
    .dash-custom-input:hover {
        border-color: #94A3B8;
    }
    .dash-custom-select:focus,
    .dash-custom-input:focus {
        outline: none;
        border-color: #3B82F6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
    .dash-filter-button {
        min-height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 12px;
        border-radius: 8px;
        border: 1px solid #CBD5E1;
        background: #fff;
        color: #475569;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
        font-family: inherit;
        cursor: pointer;
        transition: all 0.2s;
    }
    .dash-filter-button:hover {
        background: #F8FAFC;
        border-color: #94A3B8;
    }
    .dash-filter-button-primary {
        background: #1E293B;
        border-color: #1E293B;
        color: #fff;
    }
    .dash-filter-button-primary:hover {
        background: #334155;
        border-color: #334155;
    }
    @media (max-width: 1024px) {
        .responsive-finance-grid-1, .responsive-finance-grid-2 {
            grid-template-columns: 1fr !important;
        }
    }
    @media (max-width: 768px) {
        .finance-filter-header {
            align-items: flex-start;
            flex-direction: column;
        }
        .finance-filter-controls,
        .custom-select-wrapper,
        .form-control,
        .btn {
            width: 100% !important;
            max-width: 100% !important;
        }
    }
</style>
@endpush

<div class="responsive-finance-grid-1" style="display: grid; grid-template-columns: 2.2fr 1fr; gap: 24px; margin-bottom: 2rem;">
    <!-- Sales Trend Chart -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 0;">
            <h3 class="card-title" style="margin: 0; font-size: 1rem; font-weight: 600; color: var(--text-main);">Tren Pendapatan & Pesanan</h3>
        </div>
        <div style="padding: 16px 16px 0 16px; min-height: 300px;">
            @if($dailySales->count() > 0)
                <div id="revenueTrendChart"></div>
            @else
                <div style="display:flex; justify-content:center; align-items:center; height: 300px; color: var(--text-muted); font-size: 0.875rem;">Belum ada data tren</div>
            @endif
        </div>
    </div>

    <!-- Income vs Expense Bar Chart -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 0;">
            <h3 class="card-title" style="margin: 0; font-size: 1rem; font-weight: 600; color: var(--text-main);">Ringkasan Arus Kas</h3>
        </div>
        <div style="padding: 16px 16px 0 16px; min-height: 300px;">
            <div id="financeSummaryChart"></div>
        </div>
    </div>
</div>

<div class="responsive-finance-grid-2" style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px; margin-bottom: 2rem;">
    <!-- Sales by Ice Type Donut -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 0;">
            <h3 class="card-title" style="margin: 0; font-size: 1rem; font-weight: 600; color: var(--text-main);">Proporsi Penjualan Es</h3>
        </div>
        <div style="padding: 16px 16px 0 16px; min-height: 300px; display: flex; align-items: center; justify-content: center;">
            @if($salesByIceType->count() > 0)
                <div id="salesByIceTypeChart" style="width: 100%;"></div>
            @else
                <div style="color: var(--text-muted); font-size: 0.875rem;">Belum ada data penjualan</div>
            @endif
        </div>
    </div>

    <!-- Tables (Existing moved here as 2nd column) -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        <div class="card" style="margin-bottom: 0; flex: 1;">
            <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 0;">
                <h3 class="card-title" style="margin: 0; font-size: 1rem; font-weight: 600; color: var(--primary-blue);">Penjualan per Jenis Es</h3>
            </div>
            <div class="table-responsive">
                <table class="table">
                <thead>
                    <tr>
                        <th style="font-size: 0.75rem; border-top: none;">Jenis Produk</th>
                        <th style="font-size: 0.75rem; border-top: none; text-align: right;">Terjual</th>
                        <th style="font-size: 0.75rem; border-top: none; text-align: right;">Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($salesByIceType as $sale)
                    <tr>
                        <td><span style="font-weight: 600; color: var(--text-main); font-size: 0.875rem;">{{ $sale->name }}</span></td>
                        <td style="text-align: right; color: var(--text-muted); font-size: 0.875rem;">{{ $sale->total_quantity }} pcs</td>
                        <td style="text-align: right; font-weight: 600; color: var(--primary-blue); font-size: 0.875rem;">Rp {{ number_format($sale->total_revenue, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" style="text-align: center; padding: 32px; color: var(--text-muted); font-size: 0.875rem;">Belum ada data penjualan</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header" style="border-bottom: 1px solid var(--border-color); padding-bottom: 16px; margin-bottom: 0;">
        <h3 class="card-title" style="margin: 0; font-size: 1rem; font-weight: 600; color: var(--primary-blue);">Rincian Tren Penjualan ({{ ucfirst($filterLabel ?? 'Harian') }})</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
                <thead>
                    <tr>
                        <th style="font-size: 0.75rem; border-top: none;">Tanggal</th>
                        <th style="font-size: 0.75rem; border-top: none; text-align: right;">Order</th>
                        <th style="font-size: 0.75rem; border-top: none; text-align: right;">Total Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($dailySales as $daily)
                    <tr>
                        <td style="font-weight: 500; color: var(--text-main); font-size: 0.875rem;">{{ \Carbon\Carbon::parse($daily->date)->format('d M Y') }}</td>
                        <td style="text-align: right; color: var(--text-muted); font-size: 0.875rem;">{{ $daily->total_orders }}</td>
                        <td style="text-align: right; font-weight: 600; color: var(--primary-blue); font-size: 0.875rem;">Rp {{ number_format($daily->total_revenue, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" style="text-align: center; padding: 32px; color: var(--text-muted); font-size: 0.875rem;">Belum ada tren penjualan terjadwal</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Data Preparation ---
        const dailySalesData = @json($dailySales->reverse()->values()); 
        const trendDayCategories = dailySalesData.map((item) => {
            const date = new Date(item.date + 'T00:00:00');
            return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
        });
        const trendRevenues = dailySalesData.map(item => Number(item.total_revenue));
        const trendOrders = dailySalesData.map(item => Number(item.total_orders));

        const revenueStep = 50000;
        const minRevenue = Math.min(...trendRevenues);
        const maxRevenue = Math.max(...trendRevenues);
        const revenueAxisMin = Math.floor(minRevenue / revenueStep) * revenueStep;
        const revenueAxisMax = Math.ceil(maxRevenue / revenueStep) * revenueStep;
        const revenueTickAmount = Math.max(1, Math.round((revenueAxisMax - revenueAxisMin) / revenueStep));

        const minOrders = Math.min(...trendOrders);
        const maxOrders = Math.max(...trendOrders);
        const ordersAxisMin = Math.floor(minOrders);
        const ordersAxisMax = Math.ceil(maxOrders);
        const ordersTickAmount = Math.max(1, ordersAxisMax - ordersAxisMin);

        const salesByIceTypeData = @json($salesByIceType);
        const iceTypeLabels = salesByIceTypeData.map(item => item.name);
        const iceTypeQuantities = salesByIceTypeData.map(item => Number(item.total_quantity));
        const iceTypeRevenues = salesByIceTypeData.map(item => Number(item.total_revenue));
        
        const totalRev = {{ $totalRevenue ?: 0 }};
        const totalExp = {{ $totalExpense ?: 0 }};
        const netProf = {{ $netProfit ?: 0 }};

        // Format to IDR
        const formatIDR = (value) => {
            return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(value);
        };

        // --- 1. Revenue Trend Chart (Area + Line) ---
        if (dailySalesData.length > 0) {
            const trendOptions = {
                series: [{
                    name: 'Pendapatan',
                    type: 'area',
                    data: trendRevenues
                }, {
                    name: 'Pesanan',
                    type: 'line',
                    data: trendOrders
                }],
                chart: {
                    height: 300,
                    type: 'line',
                    fontFamily: 'Inter, sans-serif',
                    toolbar: { show: false },
                    zoom: { enabled: false }
                },
                colors: ['#4f46e5', '#0ea5e9'],
                fill: {
                    type: ['gradient', 'solid'],
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.25,
                        opacityTo: 0.05,
                        stops: [0, 100]
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: [2, 2]
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: trendDayCategories,
                    title: { text: 'Hari', style: { color: '#64748B', fontWeight: 500 } },
                    labels: { style: { colors: '#64748B', fontSize: '12px' } },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    tooltip: { enabled: false }
                },
                yaxis: [
                    {
                        min: revenueAxisMin,
                        max: revenueAxisMax,
                        tickAmount: revenueTickAmount,
                        title: { text: 'Pendapatan', style: { color: '#4f46e5', fontWeight: 500 } },
                        labels: {
                            style: { colors: '#64748B', fontSize: '12px' },
                            formatter: (value) => {
                                if (Math.abs(value) >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                                if (Math.abs(value) >= 1000) return 'Rp ' + (value / 1000).toFixed(1) + 'rb';
                                return 'Rp ' + value;
                            }
                        }
                    },
                    {
                        min: ordersAxisMin,
                        max: ordersAxisMax,
                        tickAmount: ordersTickAmount,
                        forceNiceScale: false,
                        decimalsInFloat: 0,
                        opposite: true,
                        title: { text: 'Pesanan', style: { color: '#0ea5e9', fontWeight: 500 } },
                        labels: {
                            style: { colors: '#64748B', fontSize: '12px' },
                            formatter: (value) => String(Math.round(value))
                        }
                    }
                ],
                grid: {
                    borderColor: '#E2E8F0',
                    strokeDashArray: 4,
                    xaxis: { lines: { show: true } },
                    yaxis: { lines: { show: true } }
                },
                tooltip: {
                    shared: true,
                    intersect: false,
                    x: {
                        formatter: function (_, { dataPointIndex }) {
                            return dailySalesData[dataPointIndex]?.date || '';
                        }
                    },
                    y: {
                        formatter: function (y, { seriesIndex }) {
                            if (typeof y !== "undefined") {
                                return seriesIndex === 0 ? formatIDR(y) : y + " pesanan";
                            }
                            return y;
                        }
                    }
                },
                legend: { position: 'top', horizontalAlign: 'right', labels: { colors: '#334155' } }
            };

            const trendChart = new ApexCharts(document.querySelector("#revenueTrendChart"), trendOptions);
            trendChart.render();
        }

        // --- 2. Finance Summary (Bar Chart) ---
        const summaryOptions = {
            series: [{
                name: 'Nominal',
                data: [totalRev, totalExp, netProf]
            }],
            chart: {
                type: 'bar',
                height: 300,
                fontFamily: 'Inter, sans-serif',
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    borderRadius: 6,
                    columnWidth: '45%',
                    distributed: true,
                }
            },
            colors: ['#334155', '#e11d48', netProf >= 0 ? '#059669' : '#e11d48'],
            dataLabels: { enabled: false },
            legend: { show: false },
            xaxis: {
                categories: ['Pendapatan', 'Pengeluaran', 'Laba Bersih'],
                labels: { style: { colors: '#64748B', fontSize: '12px', fontWeight: 500 } },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    style: { colors: '#64748B', fontSize: '12px' },
                    formatter: (value) => {
                        if (Math.abs(value) >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                        if (Math.abs(value) >= 1000) return 'Rp ' + (value / 1000).toFixed(1) + 'rb';
                        return 'Rp ' + value;
                    }
                }
            },
            grid: {
                borderColor: '#E2E8F0',
                strokeDashArray: 4,
                yaxis: { lines: { show: true } }
            },
            tooltip: {
                y: { formatter: function (val) { return formatIDR(val); } }
            }
        };

        const summaryChart = new ApexCharts(document.querySelector("#financeSummaryChart"), summaryOptions);
        summaryChart.render();

        // --- 3. Sales By Ice Type (Donut Chart) ---
        if (salesByIceTypeData.length > 0) {
            const donutOptions = {
                series: iceTypeQuantities,
                chart: {
                    type: 'donut',
                    height: 315,
                    fontFamily: 'Inter, sans-serif',
                },
                labels: iceTypeLabels,
                colors: ['#1e3a8a', '#1d4ed8', '#3b82f6', '#60a5fa', '#9ecca4', '#cbd5e1', '#475569'],
                plotOptions: {
                    pie: {
                        donut: {
                            size: '70%',
                            labels: {
                                show: true,
                                name: { fontSize: '14px', color: '#64748B', fontWeight: 500 },
                                value: { 
                                    fontSize: '24px', 
                                    color: '#0F172A', 
                                    fontWeight: 700,
                                    formatter: function (val) { return val + " pcs"; }
                                },
                                total: {
                                    show: true,
                                    showAlways: true,
                                    label: 'Total Terjual',
                                    fontSize: '14px',
                                    color: '#64748B',
                                    fontWeight: 500,
                                    formatter: function (w) {
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0) + " pcs";
                                    }
                                }
                            }
                        }
                    }
                },
                dataLabels: { enabled: false },
                stroke: { show: true, colors: '#ffffff', width: 2 },
                legend: {
                    position: 'bottom',
                    horizontalAlign: 'center',
                    fontSize: '13px',
                    markers: { radius: 12 },
                    itemMargin: { horizontal: 8, vertical: 4 }
                },
                tooltip: {
                    y: { formatter: function(value, { series, seriesIndex, dataPointIndex, w }) {
                        const revenue = formatIDR(iceTypeRevenues[seriesIndex]);
                        return `${value} pcs (${revenue})`;
                    }}
                }
            };

            const donutChart = new ApexCharts(document.querySelector("#salesByIceTypeChart"), donutOptions);
            donutChart.render();
        }
    });
</script>
<script>
    function updateFilterFields() {
        const type = document.getElementById('filter_type').value;
        document.getElementById('field_date').style.display = type === 'date' ? 'inline-block' : 'none';
        document.getElementById('field_start').style.display = type === 'range' ? 'inline-block' : 'none';
        document.getElementById('field_end').style.display = type === 'range' ? 'inline-block' : 'none';
        
        // Show/hide custom select wrappers
        const monthWrapper = document.getElementById('financeMonthSelectWrapper');
        const yearWrapper = document.getElementById('financeYearSelectWrapper');
        
        if (monthWrapper) {
            monthWrapper.style.display = type === 'month' ? 'inline-block' : 'none';
        }
        if (yearWrapper) {
            yearWrapper.style.display = (type === 'month' || type === 'year') ? 'inline-block' : 'none';
        }
    }
    document.addEventListener('DOMContentLoaded', updateFilterFields);

    function toggleFinanceFilterSelect(id) {
        // Close other custom selects first
        ['financeFilterSelectWrapper', 'financeMonthSelectWrapper', 'financeYearSelectWrapper'].forEach(wId => {
            if (wId !== id) {
                document.getElementById(wId)?.classList.remove('open');
            }
        });
        document.getElementById(id)?.classList.toggle('open');
    }

    function selectFinanceFilterOption(element, wrapperId, inputId) {
        const value = element.getAttribute('data-value');
        const text = element.textContent.trim();
        
        document.getElementById(inputId).value = value;
        
        const wrapper = document.getElementById(wrapperId);
        const textElement = wrapper.querySelector('.custom-select-trigger span');
        if (textElement) {
            textElement.textContent = text;
        }
        
        const options = wrapper.querySelectorAll('.custom-option');
        options.forEach(opt => opt.classList.remove('selected'));
        element.classList.add('selected');
        
        wrapper.classList.remove('open');
        
        if (inputId === 'filter_type') {
            updateFilterFields();
        }
    }

    // Close select on click outside
    document.addEventListener('click', function(e) {
        ['financeFilterSelectWrapper', 'financeMonthSelectWrapper', 'financeYearSelectWrapper'].forEach(id => {
            const wrapper = document.getElementById(id);
            if (wrapper && !wrapper.contains(e.target)) {
                wrapper.classList.remove('open');
            }
        });
    });

    // Initialize texts
    window.addEventListener('DOMContentLoaded', () => {
        ['financeFilterSelectWrapper', 'financeMonthSelectWrapper', 'financeYearSelectWrapper'].forEach(id => {
            const wrapper = document.getElementById(id);
            if (wrapper) {
                const selectedOption = wrapper.querySelector('.custom-option.selected');
                if (selectedOption) {
                    const textElement = wrapper.querySelector('.custom-select-trigger span');
                    if (textElement) {
                        textElement.textContent = selectedOption.textContent.trim();
                    }
                }
            }
        });
        updateFilterFields();
    });
</script>
@endpush

@endsection
