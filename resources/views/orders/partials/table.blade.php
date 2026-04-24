<div id="ordersTableContainer">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 50px;">No</th>
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
                    <td><span style="font-weight: 700; color: var(--accent);">{{ $order->id }}</span></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: var(--bg-body); color: var(--text-muted); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">
                                {{ $order->customer ? strtoupper(substr($order->customer->name, 0, 1)) : 'U' }}
                            </div>
                            <div>
                                <div style="font-weight: 600; color: var(--text-main); font-size: 14px;">{{ $order->customer ? $order->customer->name : 'Unknown' }}</div>
                                <div style="font-size: 12px; color: var(--text-light); display: flex; align-items: center; gap: 4px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="11" height="11" fill="currentColor">
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
                                <div style="font-weight: 600; font-size: 14px; color: var(--text-main);">{{ $order->iceType->name }}</div>
                                <div style="font-size: 12px; color: var(--text-light);">{{ $order->iceType->description }}</div>
                            @else
                                <div style="font-weight: 600; font-size: 14px;">Es Batu</div>
                                <div style="font-size: 12px; color: var(--text-light);">Produk tidak terdeteksi</div>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="status-badge" style="background: rgba(59, 130, 246, 0.08); color: var(--accent);">{{ $order->effective_quantity ?? 1 }} pcs</span>
                    </td>
                    <td>
                        <div style="font-weight: 600; font-size: 14px; color: var(--text-main);">{{ $order->created_at->format('d M Y') }}</div>
                        <div style="font-size: 12px; color: var(--text-light);">{{ $order->created_at->format('H:i') }}</div>
                    </td>
                    <td>
                        @if($order->status === 'pending')
                            <span class="status-badge status-pending">Pending</span>
                        @elseif($order->status === 'approved')
                            <span class="status-badge status-approved">Diterima</span>
                        @elseif($order->status === 'completed')
                            <span class="status-badge" style="background: rgba(37, 99, 235, 0.1); color: #1d4ed8;">Selesai Antar</span>
                        @elseif($order->status === 'rejected')
                            <span class="status-badge status-rejected">Ditolak</span>
                        @else
                            <span class="status-badge">{{ ucfirst($order->status ?? 'Unknown') }}</span>
                        @endif
                    </td>
                    <td>
                        <div style="text-align: center; color: var(--text-muted); font-size: 13px;">
                            Diproses di aplikasi supir
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="var(--border-color)" style="margin-bottom: 12px;">
                                <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                            </svg>
                            <h3 style="font-size: 16px; font-weight: 600; margin-bottom: 4px;">Tidak Ada Pesanan</h3>
                            <p style="font-size: 14px;">Tidak ada pesanan yang ditemukan.</p>
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
