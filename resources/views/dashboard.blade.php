@extends('layouts.dashboard')

@section('content')
<div class="page-header" style="display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h1 class="page-title" style="margin-bottom: 4px;">Dashboard</h1>
        <p class="page-subtitle" style="margin-bottom: 24px;">Ringkasan aktivitas hari ini.</p>
    </div>
    <div style="text-align: right;">
        <span style="font-size: 14px; font-weight: 500; color: var(--text-main); display: block;">{{ now()->isoFormat('dddd, D MMMM Y') }}</span>
        <span style="font-size: 12px; color: var(--text-muted);">Update terakhir: {{ now()->format('H:i') }}</span>
    </div>
</div>

<!-- Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <!-- Total Customers -->
    <div class="card" style="padding: 24px; position: relative; overflow: hidden; border: 1px solid var(--border-color); transition: transform 0.2s;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(59, 130, 246, 0.1); display: flex; align-items: center; justify-content: center; color: var(--primary-blue);">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
            </div>
            @if(isset($growthCustomers) && $growthCustomers > 0)
            <span style="font-size: 12px; font-weight: 600; color: #10B981; background: #ECFDF5; padding: 4px 8px; border-radius: 20px;">+{{ $growthCustomers }}%</span>
            @endif
        </div>
        <div>
            <h3 style="margin: 0; font-size: 32px; font-weight: 700; color: var(--text-main); letter-spacing: -1px;">{{ $totalCustomers ?? 0 }}</h3>
            <p style="margin: 4px 0 0; font-size: 14px; font-weight: 500; color: var(--text-muted);">Total Pelanggan</p>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="card" style="padding: 24px; position: relative; overflow: hidden; border: 1px solid var(--border-color); transition: transform 0.2s;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(16, 185, 129, 0.1); display: flex; align-items: center; justify-content: center; color: #059669;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
            </div>
        </div>
        <div>
            <h3 style="margin: 0; font-size: 32px; font-weight: 700; color: var(--text-main); letter-spacing: -1px;">{{ $totalOrders ?? 0 }}</h3>
            <p style="margin: 4px 0 0; font-size: 14px; font-weight: 500; color: var(--text-muted);">Total Pesanan</p>
        </div>
    </div>

    <!-- Approved Orders -->
    <div class="card" style="padding: 24px; position: relative; overflow: hidden; border: 1px solid var(--border-color); transition: transform 0.2s;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(245, 158, 11, 0.1); display: flex; align-items: center; justify-content: center; color: #D97706;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            </div>
        </div>
        <div>
            <h3 style="margin: 0; font-size: 32px; font-weight: 700; color: var(--text-main); letter-spacing: -1px;">{{ $approvedOrders ?? 0 }}</h3>
            <p style="margin: 4px 0 0; font-size: 14px; font-weight: 500; color: var(--text-muted);">Pesanan Selesai</p>
        </div>
    </div>

    <!-- Pending Orders -->
    <div class="card" style="padding: 24px; position: relative; overflow: hidden; border: 1px solid var(--border-color); transition: transform 0.2s;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(139, 92, 246, 0.1); display: flex; align-items: center; justify-content: center; color: #7C3AED;">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
            </div>
            @if($pendingOrders > 0)
            <div style="width: 8px; height: 8px; background: #EF4444; border-radius: 50%;"></div>
            @endif
        </div>
        <div>
            <h3 style="margin: 0; font-size: 32px; font-weight: 700; color: var(--text-main); letter-spacing: -1px;">{{ $pendingOrders ?? 0 }}</h3>
            <p style="margin: 4px 0 0; font-size: 14px; font-weight: 500; color: var(--text-muted);">Perlu Approval</p>
        </div>
    </div>
</div>

<!-- Charts Split -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px; margin-bottom: 32px;">
    <!-- Ice Type Chart -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Jenis Es Terlaris</h3>
        </div>
        <div style="height: 300px; position: relative;">
            @if(isset($iceTypeStats) && $iceTypeStats->count() > 0)
                <canvas id="iceTypeChart"></canvas>
            @else
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--text-muted);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="#E2E8F0" style="margin-bottom: 16px;"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                    <p>Belum ada data pesanan</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Distribusi Status Pesanan</h3>
        </div>
        <div style="height: 300px; position: relative;">
            @if(($totalOrders ?? 0) > 0)
                <canvas id="orderStatusChart"></canvas>
            @else
                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--text-muted);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="#E2E8F0" style="margin-bottom: 16px;"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                    <p>Belum ada data pesanan</p>
                </div>
            @endif
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 24px;">
    <!-- Recent Orders -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pesanan Terbaru</h3>
            <a href="{{ route('orders.index') }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">Lihat Semua</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Pelanggan</th>
                        <th>Status</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentOrders ?? [] as $order)
                    <tr>
                        <td>
                            <div style="font-weight: 600; color: var(--text-main);">{{ $order->customer->name ?? 'Unknown' }}</div>
                            <div style="font-size: 12px; color: var(--text-muted);">{{ $order->created_at->diffForHumans() }}</div>
                        </td>
                        <td>
                            <span class="badge" style="
                                @if(strtolower($order->status) == 'approved') background: #d1fae5; color: #065f46;
                                @elseif(strtolower($order->status) == 'pending') background: #ede9fe; color: #5b21b6;
                                @else background: #fee2e2; color: #991b1b; @endif 
                                padding: 4px 8px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                {{ ucfirst($order->status) }}
                            </span>
                        </td>
                        <td style="font-weight: 600;">{{ $order->quantity ?? 1 }} pcs</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 24px; color: var(--text-muted);">Belum ada pesanan terbaru</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Top Customers -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pelanggan Teraktif</h3>
            <a href="{{ route('customers.index') }}" class="btn btn-secondary" style="padding: 6px 12px; font-size: 13px;">Lihat Semua</a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Pelanggan</th>
                        <th style="text-align: right;">Total Order</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topCustomers ?? [] as $customer)
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px;">
                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div style="font-weight: 600; color: var(--text-main);">{{ $customer->name }}</div>
                                    <div style="font-size: 12px; color: var(--text-muted);">{{ $customer->phone }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="text-align: right;">
                            <span style="font-weight: 700; color: var(--text-main);">{{ $customer->orders_count }}</span>
                            <span style="font-size: 12px; color: var(--text-muted);">x</span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" style="text-align: center; padding: 24px; color: var(--text-muted);">Belum ada data pelanggan</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    @if(isset($iceTypeStats) && $iceTypeStats->count() > 0)
    const ctx = document.getElementById('iceTypeChart');
    if(ctx) {
        const iceTypeData = {!! json_encode($iceTypeStats->values()) !!};
        
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: iceTypeData.map(item => item.name),
                datasets: [{
                    data: iceTypeData.map(item => item.total),
                    backgroundColor: [
                        '#3B82F6', '#10B981', '#F59E0B', '#EF4444', 
                        '#8B5CF6', '#EC4899', '#6366F1', '#14B8A6'
                    ],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            usePointStyle: true,
                            boxWidth: 8,
                            padding: 20,
                            font: { family: "'Plus Jakarta Sans', sans-serif", size: 12 }
                        }
                    }
                }
            }
        });
    }
    @endif

    @if(($totalOrders ?? 0) > 0)
    const statusCtx = document.getElementById('orderStatusChart');
    if(statusCtx) {
        const approved = {{ $approvedOrders ?? 0 }};
        const pending = {{ $pendingOrders ?? 0 }};
        const rejected = {{ ($totalOrders ?? 0) - ($approvedOrders ?? 0) - ($pendingOrders ?? 0) }};
        
        new Chart(statusCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Approved', 'Pending', 'Dibatalkan'],
                datasets: [{
                    label: 'Jumlah Pesanan',
                    data: [approved, pending, rejected],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.8)', // Emerald 500
                        'rgba(139, 92, 246, 0.8)', // Violet 500
                        'rgba(239, 68, 68, 0.8)'   // Red 500
                    ],
                    borderColor: [
                        '#10B981',
                        '#8B5CF6',
                        '#EF4444'
                    ],
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: { family: "'Plus Jakarta Sans', sans-serif", size: 12 }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: { family: "'Plus Jakarta Sans', sans-serif", size: 12 }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    @endif
});
</script>
@endpush
