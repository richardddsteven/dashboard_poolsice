@extends('layouts.dashboard')

@section('title', 'Orders - Admin Dashboard')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Manajemen Pesanan</h1>
        <p class="page-subtitle" style="margin-bottom: 24px;">Kelola dan pantau status pesanan pelanggan.</p>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <h3 class="card-title">Daftar Pesanan ({{ $orders->total() }})</h3>
        
        <form method="GET" action="{{ route('orders.index') }}" id="orderFilterForm" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            @if(request('status'))
                <input type="hidden" name="status" value="{{ request('status') }}">
            @endif
            
            <!-- Search -->
            <div style="position: relative;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama atau no. telepon..." class="form-control" style="padding-left: 36px; width: 250px;">
            </div>

            <!-- Filter Tanggal -->
            <div>
                <select name="filter_type" id="order_filter_type" onchange="toggleOrderDateFields()" class="form-select" style="min-width: 160px;">
                    <option value="all"   {{ $filterType === 'all'   ? 'selected' : '' }}>Semua Tanggal</option>
                    <option value="date"  {{ $filterType === 'date'  ? 'selected' : '' }}>Tanggal Spesifik</option>
                    <option value="range" {{ $filterType === 'range' ? 'selected' : '' }}>Rentang Tanggal</option>
                </select>
            </div>

            <div id="order_field_date" style="display:none;">
                <input type="date" name="filter_date" value="{{ $filterDate ?? '' }}" class="form-control">
            </div>

            <div id="order_field_start" style="display:none;">
                <input type="date" name="filter_start" value="{{ $filterStart ?? '' }}" class="form-control" placeholder="Dari">
            </div>
            <div id="order_field_end" style="display:none;">
                <input type="date" name="filter_end" value="{{ $filterEnd ?? '' }}" class="form-control" placeholder="Sampai">
            </div>

            <button type="submit" class="btn btn-primary">Cari</button>
            @if(request('search') || $filterType !== 'all')
                <a href="{{ route('orders.index', request('status') ? ['status' => request('status')] : []) }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>
    </div>

    <!-- Search & Filter Bar -->
    <div class="card-body" style="padding-bottom: 24px;">
        <div class="filter-tabs" style="display: flex; gap: 8px; justify-content: flex-end;">
            <a href="{{ route('orders.index', array_merge(request()->except('status', 'page'), [])) }}" class="btn {{ request('status') == null ? 'btn-primary' : 'btn-secondary' }}" style="padding: 6px 12px; font-size: 13px;">Semua</a>
            <a href="{{ route('orders.index', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}" class="btn {{ request('status') == 'pending' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 6px 12px; font-size: 13px;">Pending</a>
            <a href="{{ route('orders.index', array_merge(request()->except('status', 'page'), ['status' => 'approved'])) }}" class="btn {{ request('status') == 'approved' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 6px 12px; font-size: 13px;">Diterima</a>
            <a href="{{ route('orders.index', array_merge(request()->except('status', 'page'), ['status' => 'rejected'])) }}" class="btn {{ request('status') == 'rejected' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 6px 12px; font-size: 13px;">Ditolak</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Pelanggan</th>
                    <th>Produk</th>
                    <th>Jumlah</th>
                    <th>Tanggal Order</th>
                    <th>Status</th>
                    <th style="width: 180px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                <tr>
                    <td><span style="font-weight: 700; color: var(--accent-blue);">#{{ $order->id }}</span></td>
                    <td>
                        <div class="user-profile" style="padding: 0; background: none; cursor: default;">
                            <div class="avatar-circle" style="width: 36px; height: 36px; font-size: 14px;">
                                {{ $order->customer ? strtoupper(substr($order->customer->name, 0, 1)) : 'U' }}
                            </div>
                            <div class="user-info">
                                <div class="user-name" style="color: var(--text-main);">{{ $order->customer ? $order->customer->name : 'Unknown' }}</div>
                                <div class="user-role" style="color: var(--text-muted); display: flex; align-items: center; gap: 4px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="12" height="12" fill="currentColor">
                                        <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                                    </svg>
                                    {{ $order->phone }}
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div style="min-width: 120px;">
                            @if($order->iceType)
                                <div style="font-weight: 600;">{{ $order->iceType->name }}</div>
                                <div style="font-size: 12px; color: var(--text-muted);">{{ $order->iceType->description }}</div>
                            @else
                                <div style="font-weight: 600;">Es Batu</div>
                                <div style="font-size: 12px; color: var(--text-muted);">Produk tidak terdeteksi</div>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="status-badge" style="background: rgba(59, 130, 246, 0.1); color: var(--accent-blue);">{{ $order->quantity ?? 1 }} pcs</span>
                    </td>
                    <td>
                        <div style="font-weight: 600;">{{ $order->created_at->format('d M Y') }}</div>
                        <div style="font-size: 12px; color: var(--text-muted);">{{ $order->created_at->format('H:i') }}</div>
                    </td>
                    <td>
                        @if($order->status === 'pending')
                            <span class="status-badge status-pending">Pending</span>
                        @elseif($order->status === 'approved')
                            <span class="status-badge status-approved">Diterima</span>
                        @else
                            <span class="status-badge status-rejected">Ditolak</span>
                        @endif
                    </td>
                    <td>
                        @if($order->status === 'pending')
                        <div style="display: flex; gap: 8px; justify-content: center;">
                            <form action="{{ route('orders.approve', $order) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-success" style="padding: 6px 12px; font-size: 12px;" onclick="return confirm('Terima pesanan ini?')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="white"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
                                    Terima
                                </button>
                            </form>
                            <form action="{{ route('orders.reject', $order) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="return confirm('Tolak pesanan ini?')">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="white"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
                                    Tolak
                                </button>
                            </form>
                        </div>
                        @else
                            <div style="text-align: center; color: var(--text-muted);">-</div>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="#CBD5E1" style="margin-bottom: 16px;">
                                <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                            </svg>
                            <h3>No orders found</h3>
                            <p>Try adjusting your search or filters.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($orders->hasPages())
    <div class="pagination">
        @if ($orders->onFirstPage())
            <span class="pagination-btn disabled" style="opacity: 0.5; cursor: not-allowed;">&laquo;</span>
        @else
            <a href="{{ $orders->previousPageUrl() }}" class="pagination-btn" rel="prev">&laquo;</a>
        @endif

        @foreach ($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
            @if ($page == $orders->currentPage())
                <span class="pagination-btn active">{{ $page }}</span>
            @else
                <a href="{{ $url }}" class="pagination-btn">{{ $page }}</a>
            @endif
        @endforeach

        @if ($orders->hasMorePages())
            <a href="{{ $orders->nextPageUrl() }}" class="pagination-btn" rel="next">&raquo;</a>
        @else
            <span class="pagination-btn disabled" style="opacity: 0.5; cursor: not-allowed;">&raquo;</span>
        @endif
    </div>
    @endif
</div>

@push('scripts')
<script>
    function toggleOrderDateFields() {
        const type = document.getElementById('order_filter_type').value;
        document.getElementById('order_field_date').style.display  = (type === 'date')  ? 'block' : 'none';
        document.getElementById('order_field_start').style.display = (type === 'range') ? 'block' : 'none';
        document.getElementById('order_field_end').style.display   = (type === 'range') ? 'block' : 'none';
    }
    document.addEventListener('DOMContentLoaded', toggleOrderDateFields);
</script>
@endpush
@endsection

