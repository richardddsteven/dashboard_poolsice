@extends('layouts.dashboard')

@section('title', 'Laporan Keuangan')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Laporan Keuangan</h1>
        <p class="page-subtitle">Ringkasan transaksi dan cetak laporan</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="{{ route('finance.index') }}" class="btn btn-secondary" style="background: var(--bg-body); border-color: var(--border-light); color: var(--text-main);">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="me-2"><path d="M19 12H5"/><path d="M12 19L5 12L12 5"/></svg>
            Kembali
        </a>
        <button type="button" class="btn btn-primary" data-no-loading onclick="window.print();">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Cetak PDF
        </button>
    </div>
</div>

<!-- Filter Section -->
<div class="card no-print" style="margin-bottom: 24px;">
    <div class="card-header border-bottom">
        <h3 class="card-title">Filter Laporan</h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('finance.reports') }}" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display:block; font-size: 13px; font-weight:600; color:var(--text-muted); margin-bottom:8px; text-transform: uppercase; letter-spacing: 0.5px;">Periode</label>
                <div class="custom-select-wrapper" id="financeFilterSelectWrapper" style="width: 100%;">
                    <div class="custom-select-trigger" onclick="toggleFinanceFilterSelect()" style="background: var(--bg-body); border-color: var(--border-light);">
                        <span id="financeFilterSelectText" style="font-weight: 500;">{{ $filterType === 'date' ? 'Tanggal Spesifik' : ($filterType === 'range' ? 'Rentang Tanggal' : ($filterType === 'month' ? 'Bulanan' : ($filterType === 'year' ? 'Tahunan' : 'Semua'))) }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
                    </div>
                    <div class="custom-options" style="border-radius: var(--radius-md); box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-color: var(--border-light);">
                        <div class="custom-option {{ $filterType === 'all' ? 'selected' : '' }}" data-value="all" onclick="selectFinanceFilterOption(this)">Semua Periode</div>
                        <div class="custom-option {{ $filterType === 'date' ? 'selected' : '' }}" data-value="date" onclick="selectFinanceFilterOption(this)">Tanggal Spesifik</div>
                        <div class="custom-option {{ $filterType === 'range' ? 'selected' : '' }}" data-value="range" onclick="selectFinanceFilterOption(this)">Rentang Tanggal</div>
                        <div class="custom-option {{ $filterType === 'month' ? 'selected' : '' }}" data-value="month" onclick="selectFinanceFilterOption(this)">Bulanan</div>
                        <div class="custom-option {{ $filterType === 'year' ? 'selected' : '' }}" data-value="year" onclick="selectFinanceFilterOption(this)">Tahunan</div>
                    </div>
                    <input type="hidden" name="filter_type" id="filter_type" value="{{ $filterType }}">
                </div>
            </div>
            
            <div id="field_date" style="display:{{ $filterType === 'date' ? 'block' : 'none' }}; flex: 1; min-width: 200px;">
                <label style="display:block; font-size: 13px; font-weight:600; color:var(--text-muted); margin-bottom:8px; text-transform: uppercase;">Tanggal</label>
                <input type="date" name="filter_date" value="{{ $filterDate ?? '' }}" class="form-control" style="background: var(--bg-body); border-color: var(--border-light);">
            </div>
            
            <div id="field_start" style="display:{{ $filterType === 'range' ? 'block' : 'none' }}; flex: 1; min-width: 200px;">
                <label style="display:block; font-size: 13px; font-weight:600; color:var(--text-muted); margin-bottom:8px; text-transform: uppercase;">Dari Tanggal</label>
                <input type="date" name="filter_start" value="{{ $filterStart ?? '' }}" class="form-control" style="background: var(--bg-body); border-color: var(--border-light);">
            </div>
            
            <div id="field_end" style="display:{{ $filterType === 'range' ? 'block' : 'none' }}; flex: 1; min-width: 200px;">
                <label style="display:block; font-size: 13px; font-weight:600; color:var(--text-muted); margin-bottom:8px; text-transform: uppercase;">Sampai Tanggal</label>
                <input type="date" name="filter_end" value="{{ $filterEnd ?? '' }}" class="form-control" style="background: var(--bg-body); border-color: var(--border-light);">
            </div>
            
            <div id="field_month" style="display:{{ $filterType === 'month' ? 'block' : 'none' }}; flex: 1; min-width: 200px;">
                <label style="display:block; font-size: 13px; font-weight:600; color:var(--text-muted); margin-bottom:8px; text-transform: uppercase;">Bulan</label>
                <div class="custom-select-wrapper" id="monthFilterSelectWrapper" style="width: 100%;">
                    <div class="custom-select-trigger" onclick="toggleMonthFilterSelect()" style="background: var(--bg-body); border-color: var(--border-light);">
                        <span id="monthFilterSelectText" style="font-weight: 500;">{{ \Carbon\Carbon::createFromDate(null, $filterMonth, 1)->format('F') }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
                    </div>
                    <div class="custom-options" style="border-radius: var(--radius-md); box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-color: var(--border-light);">
                        @foreach(range(1,12) as $m)
                            @php $mName = \Carbon\Carbon::createFromDate(null, $m, 1)->format('F') @endphp
                            <div class="custom-option {{ ($filterMonth ?? '') == $m ? 'selected' : '' }}" data-value="{{ $m }}" onclick="selectMonthFilterOption(this)">{{ $mName }}</div>
                        @endforeach
                    </div>
                    <input type="hidden" name="filter_month" id="filter_month" value="{{ $filterMonth }}">
                </div>
            </div>
            
            <div id="field_year" style="display:{{ in_array($filterType, ['month', 'year']) ? 'block' : 'none' }}; flex: 1; min-width: 200px;">
                <label style="display:block; font-size: 13px; font-weight:600; color:var(--text-muted); margin-bottom:8px; text-transform: uppercase;">Tahun</label>
                <div class="custom-select-wrapper" id="yearFilterSelectWrapper" style="width: 100%;">
                    <div class="custom-select-trigger" onclick="toggleYearFilterSelect()" style="background: var(--bg-body); border-color: var(--border-light);">
                        <span id="yearFilterSelectText" style="font-weight: 500;">{{ $filterYear ?? date('Y') }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
                    </div>
                    <div class="custom-options" style="border-radius: var(--radius-md); box-shadow: 0 4px 12px rgba(0,0,0,0.05); border-color: var(--border-light);">
                        @foreach(range(date('Y') + 1, date('Y') - 4) as $y)
                            <div class="custom-option {{ ($filterYear ?? '') == $y ? 'selected' : '' }}" data-value="{{ $y }}" onclick="selectYearFilterOption(this)">{{ $y }}</div>
                        @endforeach
                    </div>
                    <input type="hidden" name="filter_year" id="filter_year" value="{{ $filterYear ?: date('Y') }}">
                </div>
            </div>
            
            <div style="display: flex; gap: 8px;">
                <button type="submit" class="btn btn-primary" style="height: 42px; padding: 0 20px;">Terapkan</button>
                @if($filterType !== 'all')
                <a href="{{ route('finance.reports') }}" class="btn btn-secondary" style="height: 42px; padding: 0 20px; line-height: 40px; background: var(--bg-body); border-color: var(--border-light); color: var(--text-main);">Reset</a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Printed Report Container -->
<div class="card" id="printableReport" style="padding: 40px;">
    <!-- Report Header -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; padding-bottom: 24px; border-bottom: 2px solid var(--border-color);">
        <div>
            <h2 style="font-size: 25px; font-weight: 800; color: var(--text-main); margin: 0; letter-spacing: -0.5px;">Pools Ice Dashboard</h2>
            <p style="color: var(--text-muted); margin: 6px 0 0 0; font-size: 15px;">Laporan Ringkasan Keuangan Sistem</p>
        </div>
        <div style="text-align: right;">
            <p style="color: var(--text-muted); margin: 0; font-size: 14px;">Tanggal Dicetak</p>
            <p style="color: var(--text-main); margin: 0 0 12px 0; font-size: 16px; font-weight: 600;">{{ now()->format('d F Y') }}</p>
            <p style="color: var(--text-muted); margin: 0; font-size: 14px;">Periode Laporan</p>
            <p style="color: var(--text-main); margin: 0; font-size: 16px; font-weight: 600; background: var(--bg-body); padding: 4px 12px; border-radius: var(--radius-sm); display: inline-block;">{{ $filterLabel }}</p>
        </div>
    </div>

    <!-- Key Metrics Grid -->
    <div style="margin-bottom: 40px;">
        <h3 style="font-size: 19px; font-weight: 700; color: var(--text-main); margin-bottom: 16px;">Ringkasan Eksekutif</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;" class="grid-cols-4">
            <div style="padding: 20px; background: var(--bg-body); border-radius: var(--radius-md); border: 1px solid var(--border-light);">
                <p style="margin: 0; font-size: 14px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Pendapatan</p>
                <p style="margin: 8px 0 0 0; font-size: 25px; font-weight: 800; color: #10B981;">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</p>
            </div>
            <div style="padding: 20px; background: var(--bg-body); border-radius: var(--radius-md); border: 1px solid var(--border-light);">
                <p style="margin: 0; font-size: 14px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Total Pengeluaran</p>
                <p style="margin: 8px 0 0 0; font-size: 25px; font-weight: 800; color: #EF4444;">Rp {{ number_format($totalExpense, 0, ',', '.') }}</p>
            </div>
            <div style="padding: 20px; background: var(--bg-body); border-radius: var(--radius-md); border: 1px solid var(--primary-blue); position: relative; overflow: hidden;">
                <div style="position: absolute; top:0; left:0; width:4px; height:100%; background:var(--primary-blue);"></div>
                <p style="margin: 0; font-size: 14px; color: var(--primary-blue); font-weight: 600; text-transform: uppercase;">Laba Bersih</p>
                <p style="margin: 8px 0 0 0; font-size: 29px; font-weight: 800; color: var(--text-main);">Rp {{ number_format($netProfit, 0, ',', '.') }}</p>
            </div>
            <div style="padding: 20px; background: var(--bg-body); border-radius: var(--radius-md); border: 1px solid var(--border-light);">
                <p style="margin: 0; font-size: 14px; color: var(--text-muted); font-weight: 600; text-transform: uppercase;">Pendapatan Draft</p>
                <p style="margin: 8px 0 0 0; font-size: 25px; font-weight: 800; color: #F59E0B;">Rp {{ number_format($pendingRevenue, 0, ',', '.') }}</p>
            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;" class="grid-cols-2">
            <div style="padding: 16px 20px; background: var(--bg-body); border-radius: var(--radius-sm); display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 600; color: var(--text-muted); font-size: 15px;">Total Pesanan Selesai</span>
                <span style="font-weight: 800; font-size: 19px; color: var(--text-main);">{{ $totalOrders }} transaksi</span>
            </div>
            <div style="padding: 16px 20px; background: var(--bg-body); border-radius: var(--radius-sm); display: flex; justify-content: space-between; align-items: center;">
                <span style="font-weight: 600; color: var(--text-muted); font-size: 15px;">Total Pesanan Draft (Pending)</span>
                <span style="font-weight: 800; font-size: 19px; color: var(--text-main);">{{ $pendingOrders }} transaksi</span>
            </div>
        </div>
    </div>

    <!-- Two-column Report Details -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 32px; margin-bottom: 24px;" class="grid-cols-2">
        
        <!-- Sales by Ice Type -->
        <div>
            <h3 style="font-size: 17px; font-weight: 700; color: var(--text-main); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-light);">Penjualan per Jenis Es</h3>
            @if($salesByIceType->count() > 0)
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="background: var(--bg-body); font-weight: 600;">Jenis Es</th>
                            <th style="background: var(--bg-body); font-weight: 600;">Kuantitas</th>
                            <th align="right" style="background: var(--bg-body); font-weight: 600; text-align: right;">Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($salesByIceType as $sale)
                        <tr>
                            <td style="font-weight: 600; color: var(--text-main);">{{ $sale->name }}</td>
                            <td style="color: var(--text-muted);">{{ $sale->total_quantity }} pcs</td>
                            <td align="right" style="color: #10B981; font-weight: 600; text-align: right;">Rp {{ number_format($sale->total_revenue, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="background: var(--bg-body); padding: 20px; border-radius: var(--radius-sm); text-align: center;">
                <p style="margin: 0; color: var(--text-muted); font-size: 15px;">Tidak ada data penjualan untuk periode ini.</p>
            </div>
            @endif
        </div>

        <!-- Daily Sales -->
        <div>
            <h3 style="font-size: 17px; font-weight: 700; color: var(--text-main); margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid var(--border-light);">Riwayat Penjualan Harian</h3>
            @if($dailySales->count() > 0)
            <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th style="background: var(--bg-body); font-weight: 600; position: sticky; top:0;">Tanggal</th>
                            <th style="background: var(--bg-body); font-weight: 600; position: sticky; top:0;">Order</th>
                            <th align="right" style="background: var(--bg-body); font-weight: 600; text-align: right; position: sticky; top:0;">Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($dailySales as $daily)
                        <tr>
                            <td style="font-weight: 500; color: var(--text-main);">{{ \Carbon\Carbon::parse($daily->date)->format('d M Y') }}</td>
                            <td style="color: var(--text-muted);">{{ $daily->total_orders }} trx</td>
                            <td align="right" style="color: #10B981; font-weight: 600; text-align: right;">Rp {{ number_format($daily->total_revenue, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div style="background: var(--bg-body); padding: 20px; border-radius: var(--radius-sm); text-align: center;">
                <p style="margin: 0; color: var(--text-muted); font-size: 15px;">Tidak ada riwayat penjualan harian.</p>
            </div>
            @endif
        </div>

    </div>

    <!-- Footer -->
    <div style="margin-top: 60px; padding-top: 24px; border-top: 1px solid var(--border-light); display: flex; justify-content: space-between; align-items: center;">
        <div style="font-size: 14px; color: var(--text-muted);">
            <div>Laporan ini dihasilkan secara otomatis oleh <strong>Pools Ice System</strong>.</div>
            <div style="margin-top: 4px;">Informasi ini bersifat rahasia dan internal.</div>
        </div>
        <div style="text-align: right;">
            <div style="height: 60px; display:flex; align-items: flex-end; justify-content: flex-end; margin-bottom: 8px;">
                <span style="font-family: monospace; font-size: 11px; color: var(--text-muted);">[ VALIDATED BY SYSTEM ]</span>
            </div>
            <div style="font-size: 15px; font-weight: 600; color: var(--text-main);">Admin Keuangan</div>
        </div>
    </div>
</div>

<style>
@media print {
    @page { size: landscape; margin: 15mm; }
    body { background: white !important; margin: 0; padding: 0; }
    .loading-overlay { display: none !important; }
    
    .topbar, .sidebar, .page-header, .no-print { 
        display: none !important; 
    }
    
    .main-content { 
        margin-left: 0 !important; 
        padding: 0 !important;
        background: white !important;
    }
    
    .card {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    #printableReport {
        padding: 0 !important;
    }
}
</style>

<script>
// Filter Setup Functions (Kept intact)
function toggleFinanceFilterSelect() {
    document.getElementById('financeFilterSelectWrapper').classList.toggle('open');
}

function selectFinanceFilterOption(element) {
    const value = element.getAttribute('data-value');
    const text = element.innerText;
    
    document.getElementById('financeFilterSelectText').innerText = text;
    document.getElementById('filter_type').value = value;
    
    document.querySelectorAll('#financeFilterSelectWrapper .custom-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    
    document.getElementById('financeFilterSelectWrapper').classList.remove('open');
    
    document.getElementById('field_date').style.display = 'none';
    document.getElementById('field_start').style.display = 'none';
    document.getElementById('field_end').style.display = 'none';
    document.getElementById('field_month').style.display = 'none';
    document.getElementById('field_year').style.display = 'none';
    
    if (value === 'date') document.getElementById('field_date').style.display = 'block';
    else if (value === 'range') {
        document.getElementById('field_start').style.display = 'block';
        document.getElementById('field_end').style.display = 'block';
    }
    else if (value === 'month') {
        document.getElementById('field_month').style.display = 'block';
        document.getElementById('field_year').style.display = 'block';
    }
    else if (value === 'year') document.getElementById('field_year').style.display = 'block';
}

function toggleMonthFilterSelect() {
    document.getElementById('monthFilterSelectWrapper').classList.toggle('open');
}
function selectMonthFilterOption(element) {
    document.getElementById('monthFilterSelectText').innerText = element.innerText;
    document.getElementById('filter_month').value = element.getAttribute('data-value');
    document.querySelectorAll('#monthFilterSelectWrapper .custom-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('monthFilterSelectWrapper').classList.remove('open');
}

function toggleYearFilterSelect() {
    document.getElementById('yearFilterSelectWrapper').classList.toggle('open');
}
function selectYearFilterOption(element) {
    document.getElementById('yearFilterSelectText').innerText = element.innerText;
    document.getElementById('filter_year').value = element.getAttribute('data-value');
    document.querySelectorAll('#yearFilterSelectWrapper .custom-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    document.getElementById('yearFilterSelectWrapper').classList.remove('open');
}

// Close selects when clicking outside
document.addEventListener('click', function(event) {
    const wrap1 = document.getElementById('financeFilterSelectWrapper');
    if (wrap1 && !wrap1.contains(event.target)) wrap1.classList.remove('open');
    
    const wrap2 = document.getElementById('monthFilterSelectWrapper');
    if (wrap2 && !wrap2.contains(event.target)) wrap2.classList.remove('open');
    
    const wrap3 = document.getElementById('yearFilterSelectWrapper');
    if (wrap3 && !wrap3.contains(event.target)) wrap3.classList.remove('open');
});
</script>
@endsection