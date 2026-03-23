@extends('layouts.dashboard')

@section('title')

@section('content')

<div class="page-header">
    <div>
        <h1 class="page-title">Manajemen Pesanan</h1>
        <p class="page-subtitle" style="margin-bottom: 24px;">Kelola dan pantau status pesanan pelanggan.</p>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display: flex; flex-direction: column; gap: 20px; align-items: stretch; border-bottom: 1px solid var(--border-color); padding-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
            <h3 class="card-title">Daftar Pesanan (<span id="ordersTotalCount">{{ $orders->total() }}</span>)</h3>
            
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
                <div style="min-width: 160px;">
                    <div class="custom-select-wrapper" id="orderFilterSelectWrapper">
                        <div class="custom-select-trigger" onclick="toggleOrderFilterSelect()">
                            <span id="orderFilterSelectText" class="text-placeholder">Semua Tanggal</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
                        </div>
                        <div class="custom-options">
                            <div class="custom-option {{ $filterType === 'all' ? 'selected' : '' }}" data-value="all" onclick="selectOrderFilterOption(this)">Semua Tanggal</div>
                            <div class="custom-option {{ $filterType === 'date' ? 'selected' : '' }}" data-value="date" onclick="selectOrderFilterOption(this)">Tanggal Spesifik</div>
                            <div class="custom-option {{ $filterType === 'range' ? 'selected' : '' }}" data-value="range" onclick="selectOrderFilterOption(this)">Rentang Tanggal</div>
                        </div>
                        <input type="hidden" name="filter_type" id="order_filter_type" value="{{ $filterType }}">
                    </div>
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

        <!-- Filter Tabs (dipindah ke dalam header, di atas garis) -->
        <div class="filter-tabs" style="display: flex; gap: 8px; justify-content: flex-start;">
            <a href="{{ route('orders.index', array_merge(request()->except('status', 'page'), [])) }}" class="btn {{ request('status') == null ? 'btn-primary' : 'btn-secondary' }}" style="padding: 6px 12px; font-size: 13px;">Semua</a>
            <a href="{{ route('orders.index', array_merge(request()->except('status', 'page'), ['status' => 'pending'])) }}" class="btn {{ request('status') == 'pending' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 6px 12px; font-size: 13px;">Pending</a>
            <a href="{{ route('orders.index', array_merge(request()->except('status', 'page'), ['status' => 'approved'])) }}" class="btn {{ request('status') == 'approved' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 6px 12px; font-size: 13px;">Diterima</a>
            <a href="{{ route('orders.index', array_merge(request()->except('status', 'page'), ['status' => 'rejected'])) }}" class="btn {{ request('status') == 'rejected' ? 'btn-primary' : 'btn-secondary' }}" style="padding: 6px 12px; font-size: 13px;">Ditolak</a>
        </div>
    </div>

    @include('orders.partials.table', ['orders' => $orders])
</div>

<div id="realtimeOrderToast" style="position: fixed; top: 24px; right: 24px; background: #ffffff; border: 1px solid #dbeafe; border-left: 4px solid #3b82f6; border-radius: 10px; padding: 12px 14px; box-shadow: 0 8px 20px rgba(15, 23, 42, 0.12); z-index: 1200; min-width: 280px; display: none;">
    <div style="font-size: 13px; font-weight: 700; color: #1e3a8a; margin-bottom: 4px;">Pesanan baru masuk</div>
    <div id="realtimeOrderToastText" style="font-size: 12px; color: #334155;"></div>
</div>

<!-- Modal Konfirmasi -->
<div id="customConfirmModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-icon-container" id="confirmModalIconContainer">
            <svg id="confirmModalIcon" xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <!-- Icon will be injected via JS -->
            </svg>
        </div>
        <h4 id="confirmModalTitle">Konfirmasi</h4>
        <p id="confirmModalMessage">Apakah Anda yakin?</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">Batal</button>
            <button type="button" class="btn btn-primary" id="confirmModalBtn">Ya</button>
        </div>
    </div>
</div>

<style>
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1050;
}
.modal-content {
    background: #fff;
    padding: 32px 24px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    min-width: 320px;
    max-width: 400px;
    text-align: center;
    animation: modalFadeIn 0.3s ease;
}
@keyframes modalFadeIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}
.modal-icon-container {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    margin: 0 auto 16px auto;
}
.modal-icon-success {
    background-color: #dcfce7;
    color: #22c55e;
}
.modal-icon-danger {
    background-color: #fee2e2;
    color: #ef4444;
}
.modal-content h4 {
    margin-top: 0;
    margin-bottom: 8px;
    font-size: 20px;
    color: #1e293b;
    font-weight: 600;
}
.modal-content p {
    margin-bottom: 24px;
    color: #64748b;
    font-size: 15px;
}
.modal-actions {
    display: flex;
    justify-content: center;
    gap: 12px;
}
.modal-actions .btn {
    padding: 8px 24px;
    font-weight: 500;
    border-radius: 6px;
}
/* Custom Select Dropdown CSS */
.custom-select-wrapper {
    position: relative;
    width: 100%;
    user-select: none;
}
.custom-select-trigger {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 16px;
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    color: #334155;
    transition: all 0.2s ease;
}
.custom-select-wrapper.open .custom-select-trigger {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
}
.custom-select-wrapper.open .select-icon {
    transform: rotate(180deg);
}
.select-icon {
    transition: transform 0.3s ease;
}
.text-placeholder {
    color: #94a3b8;
}
.custom-options {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    margin-top: 8px;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transform: translateY(-10px);
    transition: all 0.2s ease;
    z-index: 10;
    max-height: 250px;
    overflow-y: auto;
}
.custom-select-wrapper.open .custom-options {
    opacity: 1;
    visibility: visible;
    pointer-events: all;
    transform: translateY(0);
}
.custom-option {
    padding: 10px 16px;
    font-size: 14px;
    color: #475569;
    cursor: pointer;
    transition: background 0.15s ease;
}
.custom-option:hover {
    background: #f8fafc;
    color: #1e293b;
}
.custom-option.selected {
    background: #eff6ff;
    color: #2563eb;
    font-weight: 500;
}
</style>

@push('scripts')
<script>
    function toggleOrderFilterSelect() {
        document.getElementById('orderFilterSelectWrapper').classList.toggle('open');
    }

    function selectOrderFilterOption(element) {
        const value = element.getAttribute('data-value');
        const text = element.textContent.trim();
        
        document.getElementById('order_filter_type').value = value;
        const textElement = document.getElementById('orderFilterSelectText');
        textElement.textContent = text;
        textElement.classList.remove('text-placeholder');
        
        const options = document.querySelectorAll('#orderFilterSelectWrapper .custom-option');
        options.forEach(opt => opt.classList.remove('selected'));
        element.classList.add('selected');
        
        document.getElementById('orderFilterSelectWrapper').classList.remove('open');
        toggleOrderDateFields();
    }

    document.addEventListener('click', function(e) {
        const selectWrapper = document.getElementById('orderFilterSelectWrapper');
        if (selectWrapper && !selectWrapper.contains(e.target)) {
            selectWrapper.classList.remove('open');
        }
    });

    window.addEventListener('DOMContentLoaded', () => {
        const selectedOption = document.querySelector('#orderFilterSelectWrapper .custom-option.selected');
        if (selectedOption) {
            const textElement = document.getElementById('orderFilterSelectText');
            textElement.textContent = selectedOption.textContent.trim();
            textElement.classList.remove('text-placeholder');
        }
    });

    let formToSubmit = null;

    function showConfirmModal(event, message, formElement, isDanger) {
        event.preventDefault();
        formToSubmit = formElement;
        
        document.getElementById('confirmModalMessage').textContent = message;
        document.getElementById('confirmModalTitle').textContent = isDanger ? 'Konfirmasi Penolakan' : 'Konfirmasi Penerimaan';
        
        const confirmBtn = document.getElementById('confirmModalBtn');
        const iconContainer = document.getElementById('confirmModalIconContainer');
        const iconSvg = document.getElementById('confirmModalIcon');

        if (isDanger) {
            confirmBtn.className = 'btn btn-danger';
            iconContainer.className = 'modal-icon-container modal-icon-danger';
            iconSvg.innerHTML = '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line>';
        } else {
            confirmBtn.className = 'btn btn-success';
            iconContainer.className = 'modal-icon-container modal-icon-success';
            iconSvg.innerHTML = '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline>';
        }
        
        document.getElementById('customConfirmModal').style.display = 'flex';
    }

    function closeConfirmModal() {
        document.getElementById('customConfirmModal').style.display = 'none';
        formToSubmit = null;
    }

    document.getElementById('confirmModalBtn').addEventListener('click', function() {
        if (formToSubmit) {
            formToSubmit.submit();
        }
    });

    function toggleOrderDateFields() {
        const type = document.getElementById('order_filter_type').value;
        document.getElementById('order_field_date').style.display  = (type === 'date')  ? 'block' : 'none';
        document.getElementById('order_field_start').style.display = (type === 'range') ? 'block' : 'none';
        document.getElementById('order_field_end').style.display   = (type === 'range') ? 'block' : 'none';
    }
    document.addEventListener('DOMContentLoaded', toggleOrderDateFields);

    let latestOrderId = {{ $latestOrderId ?? 0 }};
    let isRealtimeRefreshing = false;

    function showRealtimeOrderToast(order) {
        const toast = document.getElementById('realtimeOrderToast');
        const text = document.getElementById('realtimeOrderToastText');
        const productName = order.iceType || order.product || 'Es Batu';
        text.textContent = `${order.customer} (${order.phone}) - ${productName} ${order.quantity} pcs`;
        toast.style.display = 'block';
        clearTimeout(window.realtimeOrderToastTimeout);
        window.realtimeOrderToastTimeout = setTimeout(() => {
            toast.style.display = 'none';
        }, 5000);
    }

    async function refreshOrdersTable() {
        if (isRealtimeRefreshing) {
            return;
        }

        isRealtimeRefreshing = true;
        try {
            const params = new URLSearchParams(window.location.search);
            const tableUrl = `{{ route('orders.realtime.table') }}?${params.toString()}`;
            const response = await fetch(tableUrl, {
                headers: window.getRealtimeAuthHeaders()
            });

            if (!response.ok) {
                throw new Error('Failed to refresh orders table');
            }

            const result = await response.json();
            const parser = new DOMParser();
            const doc = parser.parseFromString(result.html, 'text/html');
            const incoming = doc.getElementById('ordersTableContainer');
            const current = document.getElementById('ordersTableContainer');

            if (incoming && current) {
                current.replaceWith(incoming);
            }

            const countEl = document.getElementById('ordersTotalCount');
            if (countEl && typeof result.total !== 'undefined') {
                countEl.textContent = result.total;
            }

            if (typeof result.latestOrderId !== 'undefined' && result.latestOrderId > latestOrderId) {
                latestOrderId = result.latestOrderId;
            }
        } catch (error) {
            console.error(error);
        } finally {
            isRealtimeRefreshing = false;
        }
    }

    window.addEventListener('realtime:new-order', async (event) => {
        const result = event.detail || {};
        if (!result.newOrder) {
            return;
        }

        showRealtimeOrderToast(result.newOrder);
        if (typeof result.latestOrderId !== 'undefined' && result.latestOrderId > latestOrderId) {
            latestOrderId = result.latestOrderId;
        }

        await refreshOrdersTable();
    });
</script>
@endpush
@endsection

