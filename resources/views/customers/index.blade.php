@extends('layouts.dashboard')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Pelanggan</h1>
        <p class="page-subtitle" style="margin-bottom: 24px;">Manajemen data pelanggan Pools Ice</p>
    </div>
    @if($selectedZone)
    <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 16px;">
        <a href="{{ route('customers.index') }}" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px;">
                <path d="M19 12H5"></path>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Kembali
        </a>
        <a href="{{ route('customers.create', ['zone' => $selectedZone]) }}" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 6px;">
                <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
            </svg>
            Tambah Pelanggan
        </a>
    </div>
    @endif
</div>

<style>
    .zone-card-item {
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
    }
    .zone-card {
        padding: 20px;
        text-decoration: none;
        transition: all 0.2s;
        box-shadow: none;
        display: block;
    }
    .zone-card:hover {
        border-color: var(--accent-blue);
        background-color: #F8FAFC;
        transform: translateY(-2px);
    }
    .zone-card-actions {
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        padding: 0 16px 14px;
    }
    .zone-card-actions form {
        display: inline;
    }
    .zone-card-action-btn {
        padding: 6px;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .zone-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    .zone-modal-overlay.show {
        opacity: 1;
    }
    .zone-modal-content {
        background: #fff;
        border-radius: 16px;
        padding: 28px;
        width: 100%;
        max-width: 420px;
        text-align: center;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        transform: scale(0.95) translateY(10px);
        transition: all 0.3s ease;
    }
    .zone-modal-overlay.show .zone-modal-content {
        transform: scale(1) translateY(0);
    }
    .zone-modal-icon {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
        background: #fef2f2;
        color: #ef4444;
    }
    .zone-modal-title {
        font-size: 20px;
        font-weight: 600;
        color: #1e293b;
        margin-bottom: 8px;
    }
    .zone-modal-text {
        font-size: 14px;
        color: #64748b;
        margin-bottom: 24px;
        line-height: 1.5;
    }
    .zone-modal-actions {
        display: flex;
        justify-content: center;
        gap: 12px;
    }
</style>

@if(!$selectedZone)
    <!-- Zone Selection View -->
    <div class="card" style="margin-bottom: 24px;">
        <div class="card-header">
            <h3 class="card-title">Pilih Zona</h3>
            <a href="{{ route('zones.create') }}" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 6px;">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                Tambah Zona
            </a>
        </div>
        <div class="card-body">
            <p style="color: var(--text-muted); margin-bottom: 20px;">Pilih zona wilayah untuk melihat daftar pelanggan.</p>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
                @foreach($zones as $zone)
                <div class="zone-card-item">
                    <a href="{{ route('customers.index', ['zone' => $zone->name]) }}" class="zone-card">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                                </div>
                                <div>
                                    <h4 style="margin: 0; color: var(--text-main); font-size: 16px;">{{ ucfirst($zone->name) }}</h4>
                                    <span style="font-size: 13px; color: var(--text-muted);">{{ $zone->customers_count ?? 0 }} Pelanggan</span>
                                </div>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="var(--text-muted)"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
                        </div>
                    </a>
                    <div class="zone-card-actions">
                        <a href="{{ route('zones.edit', $zone) }}" class="btn btn-secondary zone-card-action-btn" title="Edit Zona">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                        </a>
                        <form action="{{ route('zones.destroy', $zone) }}" method="POST" data-zone-name="{{ $zone->name }}" onsubmit="event.preventDefault(); showZoneDeleteModal(this);">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger zone-card-action-btn" title="Hapus Zona">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="white"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                            </button>
                        </form>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div id="zoneDeleteModal" class="zone-modal-overlay" style="display: none;">
        <div class="zone-modal-content">
            <div class="zone-modal-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"></path>
                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    <line x1="10" y1="11" x2="10" y2="17"></line>
                    <line x1="14" y1="11" x2="14" y2="17"></line>
                </svg>
            </div>
            <h3 class="zone-modal-title">Hapus Zona</h3>
            <p id="zoneDeleteModalText" class="zone-modal-text">Apakah Anda yakin ingin menghapus zona ini?</p>
            <div class="zone-modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeZoneDeleteModal()">Batal</button>
                <button type="button" class="btn" style="background: #ef4444; color: white; border: none; padding: 10px 20px; font-weight: 500;" onclick="confirmZoneDelete()">Ya, Hapus</button>
            </div>
        </div>
    </div>

    <!-- New Customers Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pelanggan Baru</h3>
            <a href="{{ route('customers.create') }}" class="btn btn-primary" style="margin-left: auto;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 6px;">
                    <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
                </svg>
                Tambah Pelanggan
            </a>
        </div>
        <div class="card-body">
            @if($latestCustomers->isEmpty())
                <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="#CBD5E1" style="margin-bottom: 16px;">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                    <p>Belum ada pelanggan baru.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Zona</th>
                                <th>Terdaftar</th>
                                <th style="text-align: right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($latestCustomers as $customer)
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #E0E7FF; color: #4338ca; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px;">
                                            {{ strtoupper(substr($customer->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--text-main);">{{ $customer->name }}</div>
                                            <div style="font-size: 12px; color: var(--text-muted);">{{ $customer->phone }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge" style="font-weight: 600; color: var(--text-main);">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 6px; color: #3B82F6;">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        {{ ucfirst($customer->zone ?? '-') }}
                                    </span>
                                </td>
                                <td style="color: var(--text-muted); font-size: 13px;">
                                    {{ $customer->created_at ? $customer->created_at->diffForHumans() : '-' }}
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-secondary" style="padding: 4px 10px; font-size: 12px;">Edit</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

@else
    <!-- Specific Zone List -->
    <div class="card">
        <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <h3 class="card-title">Pelanggan Zona: {{ ucfirst($selectedZone) }}</h3>
            
            <form method="GET" action="{{ route('customers.index') }}" style="display: flex; gap: 12px; align-items: center;">
                <input type="hidden" name="zone" value="{{ $selectedZone }}">
                <div style="position: relative;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Cari nama atau telepon..." class="form-control" style="padding-left: 36px; width: 250px;">
                </div>
                <button type="submit" class="btn btn-primary">Cari</button>
                @if($search)
                    <a href="{{ route('customers.index', ['zone' => $selectedZone]) }}" class="btn btn-secondary">Reset</a>
                @endif
            </form>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Pelanggan</th>
                        <th>Alamat</th>
                        <th>No. Telepon</th>
                        <th style="text-align: center; width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td>{{ $loop->iteration + ($customers->currentPage() - 1) * $customers->perPage() }}</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                                </div>
                                <span style="font-weight: 600; color: var(--text-main);">{{ $customer->name }}</span>
                            </div>
                        </td>
                        <td style="color: var(--text-muted);">{{ $customer->address ?? '-' }}</td>
                        <td style="font-family: monospace; font-size: 13px;">{{ $customer->phone }}</td>
                        <td style="text-align: center;">
                            <div style="display: flex; justify-content: center; gap: 6px;">
                                <a href="{{ route('customers.edit', $customer) }}" class="btn btn-secondary" style="padding: 6px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                </a>
                                <form action="{{ route('customers.destroy', $customer) }}" method="POST" data-customer-name="{{ $customer->name }}" onsubmit="event.preventDefault(); showCustomerDeleteModal(this);">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger" style="padding: 6px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="white"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5">
                            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="#CBD5E1" style="margin-bottom: 16px;">
                                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                                </svg>
                                <p>Tidak ada data pelanggan ditemukan.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
        <div style="padding: 16px; border-top: 1px solid var(--border-color);">
            {{ $customers->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
@endif

<div id="customerDeleteModal" class="zone-modal-overlay" style="display: none;">
    <div class="zone-modal-content">
        <div class="zone-modal-icon" style="background: #fee2e2; color: #b91c1c;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 6h18"></path>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                <line x1="10" y1="11" x2="10" y2="17"></line>
                <line x1="14" y1="11" x2="14" y2="17"></line>
            </svg>
        </div>
        <h3 class="zone-modal-title">Hapus Pelanggan</h3>
        <p id="customerDeleteModalText" class="zone-modal-text">Apakah Anda yakin ingin menghapus pelanggan ini?</p>
        <div class="zone-modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeCustomerDeleteModal()">Batal</button>
            <button type="button" class="btn" style="background: #ef4444; color: white; border: none; padding: 10px 20px; font-weight: 500;" onclick="confirmCustomerDelete()">Ya, Hapus</button>
        </div>
    </div>
</div>

<script>
    let currentZoneDeleteForm = null;
    let currentCustomerDeleteForm = null;

    function showZoneDeleteModal(formElement) {
        const modal = document.getElementById('zoneDeleteModal');
        const modalText = document.getElementById('zoneDeleteModalText');

        currentZoneDeleteForm = formElement;

        if (modalText) {
            const zoneName = formElement.getAttribute('data-zone-name') || 'ini';
            modalText.textContent = `Apakah Anda yakin ingin menghapus zona ${zoneName}? Tindakan ini tidak dapat dibatalkan.`;
        }

        modal.style.display = 'flex';
        requestAnimationFrame(() => {
            modal.classList.add('show');
        });
    }

    function closeZoneDeleteModal() {
        const modal = document.getElementById('zoneDeleteModal');
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            currentZoneDeleteForm = null;
        }, 250);
    }

    function confirmZoneDelete() {
        if (currentZoneDeleteForm) {
            currentZoneDeleteForm.submit();
        }
    }

    function showCustomerDeleteModal(formElement) {
        const modal = document.getElementById('customerDeleteModal');
        const modalText = document.getElementById('customerDeleteModalText');

        currentCustomerDeleteForm = formElement;

        if (modalText) {
            const customerName = formElement.getAttribute('data-customer-name') || 'ini';
            modalText.textContent = `Apakah Anda yakin ingin menghapus pelanggan ${customerName}? Tindakan ini tidak dapat dibatalkan.`;
        }

        if (modal) {
            modal.style.display = 'flex';
            requestAnimationFrame(() => {
                modal.classList.add('show');
            });
        }
    }

    function closeCustomerDeleteModal() {
        const modal = document.getElementById('customerDeleteModal');
        if (!modal) {
            return;
        }

        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            currentCustomerDeleteForm = null;
        }, 250);
    }

    function confirmCustomerDelete() {
        if (currentCustomerDeleteForm) {
            currentCustomerDeleteForm.submit();
        }
    }
</script>
@endsection
