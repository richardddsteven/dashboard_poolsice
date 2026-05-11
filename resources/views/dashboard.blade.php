@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')

<!-- Reference Design Header (Interactive) -->
<form id="dashboardFilterForm" method="GET" action="{{ route('dashboard') }}" class="dash-custom-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <h1 style="font-size: 27px; font-weight: 700; color: #1E293B; letter-spacing: -0.5px; margin: 0;">Dashboard</h1>
    <div style="display: flex; gap: 8px; align-items: center; color: var(--text-muted); font-weight: 500; font-size: 15px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
        <span>{{ \Carbon\Carbon::now()->locale('id')->translatedFormat('l, d F Y') }}</span>
    </div>
</form>

<!-- Stat Cards Row (3 cards like reference) -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 24px;">
    <!-- Total Pelanggan -->
    <div class="dash-stat-card">
        <div class="dash-stat-header">
            <div class="dash-stat-icon" style="color: #3B82F6;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
            </div>
            <span class="dash-stat-label">Total Pelanggan</span>
            <!-- <div style="margin-left: auto; cursor: pointer; color: #CBD5E1;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
            </div> -->
        </div>
        <div class="dash-stat-body">
            <span class="dash-stat-value">{{ number_format($totalCustomers) }}</span>
            @if($growthCustomers != 0)
            <span class="dash-stat-badge {{ $growthCustomers >= 0 ? 'badge-up' : 'badge-down' }}">
                {{ abs($growthCustomers) }}%
                <svg width="10" height="10" viewBox="0 0 10 10" fill="currentColor"><polygon points="{{ $growthCustomers >= 0 ? '5,1 9,7 1,7' : '5,9 9,3 1,3' }}"/></svg>
            </span>
            @endif
        </div>
    </div>

    <!-- Pendapatan Hari Ini -->
    <div class="dash-stat-card">
        <div class="dash-stat-header">
            <div class="dash-stat-icon" style="color: #3B82F6;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>
            </div>
            <span class="dash-stat-label">Pendapatan Hari Ini</span>
            <!-- <div style="margin-left: auto; cursor: pointer; color: #CBD5E1;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
            </div> -->
        </div>
        <div class="dash-stat-body">
            <span class="dash-stat-value">Rp {{ number_format($todayRevenue, 0, ',', '.') }}</span>
            <span class="dash-stat-badge badge-neutral">{{ $todayOrders }} pesanan</span>
        </div>
    </div>

    <!-- Pesanan Pending -->
    <div class="dash-stat-card">
        <div class="dash-stat-header">
            <div class="dash-stat-icon" style="color: #3B82F6;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
            </div>
            <span class="dash-stat-label">Pesanan Pending</span>
            <!-- <div style="margin-left: auto; cursor: pointer; color: #CBD5E1;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>
            </div> -->
        </div>
        <div class="dash-stat-body">
            <span class="dash-stat-value" id="dashboard-pending-count">{{ number_format($pendingOrders) }}</span>
            @if($pendingOrders > 0)
            <span class="dash-stat-badge badge-warning">perlu ditinjau</span>
            @else
            <span class="dash-stat-badge badge-up">semua selesai</span>
            @endif
        </div>
    </div>
</div>

<!-- Row 2: Sales Overview (bar chart) + Total Pesanan (bar chart) -->
<div style="display: grid; grid-template-columns: 3fr 2fr; gap: 20px; margin-bottom: 24px;">
    <!-- Sales Overview - Stacked/Grouped Bar Chart -->
    <div class="dash-card">
        <div class="dash-card-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="color: #3B82F6;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M5 9.2h3V19H5zM10.6 5h2.8v14h-2.8zm5.6 8H19v6h-2.8z"/></svg>
                </div>
                <span class="dash-card-title">Ringkasan Penjualan</span>
            </div>
        </div>
        <div style="margin-bottom: 16px;">
            <div style="display: flex; align-items: baseline; gap: 12px; margin-bottom: 4px;">
                <span style="font-size: 35px; font-weight: 800; color: #1E293B; letter-spacing: -1px;">Rp {{ number_format(collect($revenueChartData)->sum('revenue'), 0, ',', '.') }}</span>
            </div>
            @php
                $totalRev7 = collect($revenueChartData)->sum('revenue');
                $totalOrd7 = collect($revenueChartData)->sum('orders');
            @endphp
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="dash-stat-badge badge-up" style="font-size: 14px;">{{ $totalOrd7 }} pesanan</span>
                <span style="font-size: 15px; color: #94A3B8;">dalam 7 hari terakhir</span>
            </div>
        </div>
        <div style="height: 220px; position: relative;">
            <canvas id="salesOverviewChart"></canvas>
        </div>
    </div>

    <!-- Total Pesanan Bar Chart (Weekly) -->
    <div class="dash-card">
        <div class="dash-card-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="color: #3B82F6;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
                </div>
                <span class="dash-card-title">Total Pesanan</span>
            </div>
        </div>
        <div style="margin-bottom: 16px;">
            <div style="font-size: 35px; font-weight: 800; color: #1E293B; letter-spacing: -1px; margin-bottom: 4px;">{{ number_format($totalOrders) }}</div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="dash-stat-badge badge-up" style="font-size: 14px;">{{ $completedOrders }} selesai antar</span>
                <span style="font-size: 15px; color: #94A3B8;">dari total</span>
            </div>
        </div>
        <div style="height: 220px; position: relative;">
            <canvas id="ordersWeeklyChart"></canvas>
        </div>
    </div>
</div>

<!-- Row 3: Sales Distribution (Doughnut) + List (Table) -->
<div style="display: grid; grid-template-columns: 2fr 3fr; gap: 20px; margin-bottom: 24px;">
    <!-- Sales Distribution -->
    <div class="dash-card">
        <div class="dash-card-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="color: #3B82F6;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M11 2v20c-5.07-.5-9-4.79-9-10s3.93-9.5 9-10zm2.03 0v8.99H22c-.47-4.74-4.24-8.52-8.97-8.99zm0 11.01V22c4.74-.47 8.5-4.25 8.97-8.99h-8.97z"/></svg>
                </div>
                <span class="dash-card-title">Distribusi Penjualan</span>
            </div>
        </div>

        @if($iceTypeStats->count() > 0)
        <!-- Legend values -->
        <div style="display: flex; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
            @foreach($iceTypeStats as $stat)
            <div style="display: flex; align-items: center; gap: 6px;">
                <div style="width: 3px; height: 28px; border-radius: 3px; background: {{ $stat['color'] }};"></div>
                <div>
                    <div style="font-size: 14px; color: #94A3B8; font-weight: 500;">{{ $stat['name'] }}</div>
                    <div style="font-size: 19px; font-weight: 700; color: #1E293B;">{{ $stat['total'] }}</div>
                </div>
            </div>
            @endforeach
        </div>
        <div style="height: 180px; display: flex; align-items: center; justify-content: center;">
            <canvas id="iceTypeChart"></canvas>
        </div>
        @else
        <div style="text-align: center; padding: 40px; color: #94A3B8;">
            <p style="font-size: 14px;">Belum ada data penjualan</p>
        </div>
        @endif
    </div>

    <!-- Pesanan Terbaru (Table like "List of Integration") -->
    <div class="dash-card">
        <div class="dash-card-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="color: #3B82F6;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h2v-2H3v2zm0 4h2v-2H3v2zm0-8h2V7H3v2zm4 4h14v-2H7v2zm0 4h14v-2H7v2zM7 7v2h14V7H7z"/></svg>
                </div>
                <span class="dash-card-title">Pesanan Terbaru</span>
            </div>
            <a href="{{ route('orders.index') }}" style="font-size: 13px; color: #3B82F6; text-decoration: none; font-weight: 600;">Lihat Semua</a>
        </div>
        <div class="table-responsive">
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>PELANGGAN</th>
                        <th>PRODUK</th>
                        <th>QTY</th>
                        <th>STATUS</th>
                        <th>WAKTU</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders as $order)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="dash-avatar" style="background: {{ ['#EDE9FE','#DBEAFE','#D1FAE5','#FEF3C7','#FCE7F3'][($loop->index % 5)] }}; color: {{ ['#7C3AED','#3B82F6','#10B981','#F59E0B','#EC4899'][($loop->index % 5)] }};">
                                    {{ $order->customer ? strtoupper(substr($order->customer->name, 0, 1)) : 'U' }}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: #1E293B; font-size: 16px;">{{ $order->customer->name ?? 'Unknown' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="color: #64748B; font-size: 15px;">{{ $order->iceType->name ?? 'Es Batu' }}</td>
                        <td style="font-weight: 600; font-size: 15px; color: #1E293B;">{{ $order->quantity ?? 1 }}</td>
                        <td>
                            @if($order->status === 'completed')
                            <span class="dash-status-pill dash-status-primary">Selesai Antar</span>
                            @elseif($order->status === 'approved')
                            <span class="dash-status-pill dash-status-success">Diterima</span>
                            @elseif($order->status === 'rejected')
                            <span class="dash-status-pill dash-status-danger">Ditolak</span>
                            @elseif($order->status === 'pending')
                            <span class="dash-status-pill dash-status-warning">Pending</span>
                            @else
                            <span class="dash-status-pill dash-status-neutral">{{ ucfirst($order->status) }}</span>
                            @endif
                        </td>
                        <td style="font-size: 14px; color: #94A3B8;">{{ $order->created_at->locale('id')->diffForHumans() }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 30px; color: #94A3B8; font-size: 16px;">Belum ada pesanan</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Row 4: Arus Kas + Pelanggan Teraktif -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- Arus Kas Harian -->
    <div class="dash-card">
        <div class="dash-card-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="color: #3B82F6;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M4 19h16v2H4v-2zm2-4h3V9H6v6zm5 0h3V5h-3v10zm5 0h3v-8h-3v8z"/></svg>
                </div>
                <span class="dash-card-title">Arus Kas 7 Hari Terakhir</span>
            </div>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 16px;">
            <div class="dash-stat-badge badge-up">Pendapatan Rp {{ number_format(collect($financeChartData)->sum('revenue'), 0, ',', '.') }}</div>
            <div class="dash-stat-badge badge-warning">Pengeluaran Rp {{ number_format(collect($financeChartData)->sum('expense'), 0, ',', '.') }}</div>
            <div class="dash-stat-badge badge-neutral">Laba Rp {{ number_format(collect($financeChartData)->sum('net'), 0, ',', '.') }}</div>
        </div>
        <div style="height: 260px; position: relative;">
            <canvas id="financeFlowChart"></canvas>
        </div>
    </div>

    <!-- Pelanggan Teraktif -->
    <div class="dash-card">
        <div class="dash-card-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <div style="color: #3B82F6;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>
                </div>
                <span class="dash-card-title">Pelanggan Teraktif</span>
            </div>
            <a href="{{ route('customers.index') }}" style="font-size: 13px; color: #3B82F6; text-decoration: none; font-weight: 600;">Lihat Semua</a>
        </div>
        @if($topCustomers->count() > 0)
        <div>
            @foreach($topCustomers as $customer)
            <div class="dash-customer-row">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="dash-avatar" style="background: {{ ['#EDE9FE','#DBEAFE','#D1FAE5','#FEF3C7','#FCE7F3'][($loop->index % 5)] }}; color: {{ ['#7C3AED','#3B82F6','#10B981','#F59E0B','#EC4899'][($loop->index % 5)] }};">
                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                    </div>
                    <div>
                        <div style="font-weight: 600; color: #1E293B; font-size: 16px;">{{ $customer->name }}</div>
                        <div style="font-size: 14px; color: #CBD5E1;">{{ ucfirst($customer->zone ?? 'Unknown') }}</div>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 700; color: #1E293B; font-size: 19px;">{{ $customer->orders_count }}</div>
                    <div style="font-size: 13px; color: #94A3B8; letter-spacing: 0.5px;">PESANAN</div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div style="text-align: center; padding: 30px; color: #94A3B8; font-size: 14px;">Belum ada data pelanggan</div>
        @endif
    </div>
</div>

<style>
    /* Dashboard specific styles matching reference design */
    .dash-btn-outline {
        background: #fff;
        border: 1px solid #E2E8F0;
        border-radius: 8px;
        padding: 8px 12px;
        font-size: 14px;
        font-weight: 500;
        color: #475569;
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
        font-family: inherit;
        transition: all 0.2s;
    }
    .dash-btn-outline:hover {
        background: #F8FAFC;
        border-color: #CBD5E1;
    }

    .dash-stat-card {
        background: #fff;
        border-radius: 16px;
        padding: 22px 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.02);
        border: 1px solid #F1F5F9;
        transition: all 0.2s;
    }
    .dash-stat-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.06); }
    .dash-stat-header { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; }
    .dash-stat-icon { display: flex; align-items: center; }
    .dash-stat-label { font-size: 14px; font-weight: 500; color: #64748B; }
    .dash-stat-body { display: flex; align-items: center; gap: 12px; }
    .dash-stat-value { font-size: 31px; font-weight: 800; color: #1E293B; letter-spacing: -1px; line-height: 1; }
    .dash-stat-badge {
        display: inline-flex; align-items: center; gap: 3px;
        padding: 3px 10px; border-radius: 20px; font-size: 13px; font-weight: 600;
    }
    .badge-up { background: #D1FAE5; color: #059669; }
    .badge-down { background: #FEE2E2; color: #DC2626; }
    .badge-warning { background: #FEF3C7; color: #D97706; }
    .badge-neutral { background: #F1F5F9; color: #64748B; }

    .dash-card {
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 1px 2px rgba(0,0,0,0.02);
        border: 1px solid #F1F5F9;
    }
    .dash-card-header {
        display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;
    }
    .dash-card-title { font-size: 16px; font-weight: 600; color: #1E293B; }

    .dash-table { width: 100%; border-collapse: collapse; }
    .dash-table th {
        font-size: 11px; font-weight: 600; color: #94A3B8; text-transform: uppercase; letter-spacing: 0.8px;
        padding: 0 16px 12px 0; text-align: left; border-bottom: 1px solid #F1F5F9;
    }
    .dash-table td {
        padding: 12px 16px 12px 0; border-bottom: 1px solid #F8FAFC; vertical-align: middle;
    }
    .dash-table tr:last-child td { border-bottom: none; }
    .dash-table tr:hover td { background: #FAFBFE; }

    .dash-avatar {
        width: 32px; height: 32px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 13px; flex-shrink: 0;
    }

    .dash-status-pill {
        display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 20px;
        font-size: 12px; font-weight: 600;
    }
    .dash-status-success { background: #D1FAE5; color: #059669; }
    .dash-status-danger { background: #FEE2E2; color: #DC2626; }
    .dash-status-warning { background: #FEF3C7; color: #D97706; }
    .dash-status-primary { background: #DBEAFE; color: #1D4ED8; }
    .dash-status-neutral { background: #F1F5F9; color: #64748B; }

    .dash-activity-list { display: flex; flex-direction: column; }
    .dash-activity-item {
        display: flex; align-items: center; gap: 12px; padding: 10px 0;
        border-bottom: 1px solid #F8FAFC;
    }
    .dash-activity-item:last-child { border-bottom: none; }
    .dash-activity-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .dot-success { background: #10B981; }
    .dot-danger { background: #EF4444; }
    .dot-warning { background: #F59E0B; }
    .dot-primary { background: #3B82F6; }

    .dash-customer-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 0; border-bottom: 1px solid #F8FAFC;
    }
    .dash-customer-row:last-child { border-bottom: none; }

    /* Responsive */
    @media (max-width: 1024px) {
        [style*="grid-template-columns: repeat(3"] { grid-template-columns: 1fr !important; }
        [style*="grid-template-columns: 3fr 2fr"] { grid-template-columns: 1fr !important; }
        [style*="grid-template-columns: 2fr 3fr"] { grid-template-columns: 1fr !important; }
        [style*="grid-template-columns: 1fr 1fr"] { grid-template-columns: 1fr !important; }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, sans-serif";
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#94A3B8';
    Chart.defaults.plugins.legend.display = false;

    // Blue gradient colors
    const blue1 = '#2956E3';
    const blue2 = '#9CC1FF';
    const blue3 = '#9CC1FF';
    const blue4 = '#D7E4FF';
    const teal1 = '#14B8A6';
    const todayDark = '#1E3A8A'; // biru tua untuk hari ini

    // Today's label (format sama dengan label chart: "d M")
    const todayLabel = '{{ \Carbon\Carbon::now()->format("d M") }}';

    // === Sales Overview: Stacked Bar Chart ===
    const revenueData = @json($revenueChartData);
    const maxRevenue = Math.max(...revenueData.map(d => d.revenue));
    const salesColors = revenueData.map(d => {
        if (d.date === todayLabel) return todayDark;
        const ratio = maxRevenue > 0 ? d.revenue / maxRevenue : 0;
        if (ratio > 0.7) return blue1;
        if (ratio > 0.4) return blue2;
        if (ratio > 0.2) return blue3;
        return blue4;
    });
    new Chart(document.getElementById('salesOverviewChart'), {
        type: 'bar',
        data: {
            labels: revenueData.map(d => d.date),
            datasets: [{
                label: 'Pendapatan',
                data: revenueData.map(d => d.revenue),
                backgroundColor: salesColors,
                borderRadius: 6,
                borderSkipped: false,
                barThickness: 28,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#F1F5F9', drawBorder: false },
                    border: { display: false },
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                            if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + 'rb';
                            return 'Rp ' + value;
                        },
                        maxTicksLimit: 5,
                        font: { size: 11 },
                    }
                },
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        font: (ctx) => ({ size: 11, weight: revenueData[ctx.index]?.date === todayLabel ? '700' : '500' }),
                        color: (ctx) => revenueData[ctx.index]?.date === todayLabel ? todayDark : '#94A3B8',
                    }
                }
            },
            plugins: {
                tooltip: {
                    backgroundColor: '#1E293B',
                    titleColor: '#E2E8F0',
                    bodyColor: '#F8FAFC',
                    titleFont: { weight: '600', size: 11 },
                    bodyFont: { size: 12 },
                    padding: 12,
                    cornerRadius: 10,
                    displayColors: false,
                    callbacks: {
                        label: (ctx) => 'Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                    }
                }
            }
        }
    });

    // === Orders Weekly Bar Chart ===
    const ordersData = revenueData.map(d => d.orders);
    const maxOrders = Math.max(...revenueData.map(d => d.orders));
    const ordersColors = revenueData.map(d => {
        if (d.date === todayLabel) return todayDark;
        const ratio = maxOrders > 0 ? d.orders / maxOrders : 0;
        if (ratio > 0.7) return blue1;
        if (ratio > 0.4) return blue2;
        if (ratio > 0.2) return blue3;
        return blue4;
    });
    new Chart(document.getElementById('ordersWeeklyChart'), {
        type: 'bar',
        data: {
            labels: revenueData.map(d => d.date),
            datasets: [{
                label: 'Pesanan',
                data: ordersData,
                backgroundColor: ordersColors,
                borderRadius: 6,
                borderSkipped: false,
                barThickness: 24,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#F1F5F9', drawBorder: false },
                    border: { display: false },
                    ticks: { maxTicksLimit: 5, font: { size: 11 } }
                },
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        font: (ctx) => ({ size: 11, weight: revenueData[ctx.index]?.date === todayLabel ? '700' : '400' }),
                        color: (ctx) => revenueData[ctx.index]?.date === todayLabel ? todayDark : '#94A3B8',
                    }
                }
            },
            plugins: {
                tooltip: {
                    backgroundColor: '#1E293B',
                    bodyColor: '#F8FAFC',
                    padding: 10,
                    cornerRadius: 10,
                    displayColors: false,
                    callbacks: {
                        title: (items) => items[0].label,
                        label: (ctx) => ctx.parsed.y + ' pesanan'
                    }
                }
            }
        }
    });

    // === Finance Flow Chart ===
    const financeFlowData = @json($financeChartData);
    new Chart(document.getElementById('financeFlowChart'), {
        type: 'bar',
        data: {
            labels: financeFlowData.map(d => d.date),
            datasets: [
                {
                    label: 'Pendapatan',
                    data: financeFlowData.map(d => d.revenue),
                    backgroundColor: financeFlowData.map(d => d.date === todayLabel ? todayDark : '#2956E3'),
                    borderRadius: 8,
                    borderSkipped: false,
                    barPercentage: 0.7,
                    categoryPercentage: 0.58,
                },
                {
                    label: 'Pengeluaran',
                    data: financeFlowData.map(d => d.expense),
                    backgroundColor: financeFlowData.map(d => d.date === todayLabel ? blue2 : '#A8C5FF'),
                    borderRadius: 8,
                    borderSkipped: false,
                    barPercentage: 0.7,
                    categoryPercentage: 0.58,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#F1F5F9', drawBorder: false },
                    border: { display: false },
                    ticks: {
                        stepSize: 50000,
                        callback: function(value) {
                            if (value >= 1000000) return 'Rp ' + (value / 1000000).toFixed(1) + 'jt';
                            if (value >= 1000) return 'Rp ' + (value / 1000).toFixed(0) + 'rb';
                            return 'Rp ' + value;
                        },
                        font: { size: 11 },
                    }
                },
                x: {
                    grid: { display: false },
                    border: { display: false },
                    ticks: {
                        font: (ctx) => ({ size: 11, weight: financeFlowData[ctx.index]?.date === todayLabel ? '700' : '500' }),
                        color: (ctx) => financeFlowData[ctx.index]?.date === todayLabel ? todayDark : '#94A3B8',
                    }
                }
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        boxWidth: 8,
                        padding: 18,
                    }
                },
                tooltip: {
                    backgroundColor: '#1E293B',
                    bodyColor: '#F8FAFC',
                    padding: 10,
                    cornerRadius: 10,
                    displayColors: false,
                    callbacks: {
                        label: (ctx) => ctx.dataset.label + ': Rp ' + ctx.parsed.y.toLocaleString('id-ID')
                    }
                }
            }
        }
    });

    // === Ice Type Doughnut ===
    @if($iceTypeStats->count() > 0)
    new Chart(document.getElementById('iceTypeChart'), {
        type: 'doughnut',
        data: {
            labels: @json($iceTypeStats->pluck('name')),
            datasets: [{
                data: @json($iceTypeStats->pluck('total')),
                backgroundColor: @json($iceTypeStats->pluck('color')),
                borderWidth: 0,
                spacing: 3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '65%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1E293B',
                    bodyColor: '#F8FAFC',
                    padding: 10,
                    cornerRadius: 10,
                    displayColors: true,
                }
            }
        }
    });
    @endif
});
</script>
@endsection
