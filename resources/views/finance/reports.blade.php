@extends('layouts.dashboard')

@section('title', 'Laporan Keuangan')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Laporan Keuangan</h1>
        <p class="page-subtitle">Ringkasan transaksi dan cetak laporan</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
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
<form method="GET" action="{{ route('finance.reports') }}" id="filterForm" class="finance-filter-header no-print">
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

        <input id="field_date" type="date" name="filter_date" value="{{ $filterDate ?? '' }}" class="form-control" style="min-width: 160px; max-width: 180px; display: none;">
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
                    <div class="custom-option {{ ($filterMonth ?? '') == $m ? 'selected' : '' }}" data-value="{{ $m }}" onclick="selectFinanceFilterOption(this, 'financeMonthSelectWrapper', 'filter_month')">{{ $mLabel }}</div>
                @endforeach
            </div>
            <input type="hidden" name="filter_month" id="filter_month" value="{{ $filterMonth ?? '' }}">
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
        <a href="{{ route('finance.reports') }}" class="btn btn-secondary" style="padding: 10px 20px;">Reset</a>
        @endif
    </div>
</form>

<!-- Printed Report Container -->
<div class="card" id="printableReport">
    <!-- Report Header -->
    <div class="report-header-flex">
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
        <div class="report-executive-grid">
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
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-top: 16px;">
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
    <div class="report-subtables-grid">
        
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
    <div class="report-footer-flex">
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
#printableReport {
    padding: 16px;
}
.report-header-flex {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 40px;
    padding-bottom: 24px;
    border-bottom: 2px solid var(--border-color);
}
.report-executive-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}
.report-subtables-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 32px;
    margin-bottom: 24px;
}
.report-footer-flex {
    margin-top: 60px;
    padding-top: 24px;
    border-top: 1px solid var(--border-light);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}
@media (min-width: 768px) {
    #printableReport {
        padding: 40px;
    }
}
@media (max-width: 768px) {
    .report-header-flex {
        flex-direction: column;
        align-items: stretch;
    }
    .report-header-flex > div:last-child {
        text-align: left !important;
        margin-top: 16px;
    }
    .report-subtables-grid {
        grid-template-columns: 1fr;
        gap: 24px;
    }
    .report-footer-flex {
        flex-direction: column;
        align-items: stretch;
    }
    .report-footer-flex > div:last-child {
        text-align: left !important;
        margin-top: 16px;
    }
}
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
function updateFilterFields() {
    const type = document.getElementById('filter_type').value;

    document.getElementById('field_date').style.display = type === 'date' ? 'inline-block' : 'none';
    document.getElementById('field_start').style.display = type === 'range' ? 'inline-block' : 'none';
    document.getElementById('field_end').style.display = type === 'range' ? 'inline-block' : 'none';
    
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
@endsection
