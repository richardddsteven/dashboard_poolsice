@extends('layouts.dashboard')

@section('title')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Pengeluaran</h1>
        <p class="page-subtitle" style="margin-bottom: 24px;">Manajemen data pengeluaran operasional</p>
    </div>
    <a href="{{ route('expenses.create') }}" class="btn btn-primary" style="margin-bottom: 16px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor" style="margin-right: 6px;">
            <path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>
        </svg>
        Tambah Pengeluaran
    </a>
</div>

<div class="card">
    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <h3 class="card-title">Daftar Pengeluaran</h3>
        
        <form action="{{ route('expenses.index') }}" method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
            <div style="position: relative;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8;">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau catatan..." class="form-control" style="padding-left: 36px; width: 250px;">
            </div>
            
            <div style="min-width: 180px;">
                <div class="custom-select-wrapper" id="categorySelectWrapper">
                    <div class="custom-select-trigger" onclick="toggleCategorySelect()">
                        <span id="categorySelectText" class="text-placeholder">Semua Kategori</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
                    </div>
                    <div class="custom-options">
                        <div class="custom-option {{ request('category') == '' ? 'selected' : '' }}" data-value="" onclick="selectCategoryOption(this)">Semua Kategori</div>
                        @foreach($categories as $category)
                            <div class="custom-option {{ request('category') == $category->id ? 'selected' : '' }}" data-value="{{ $category->id }}" onclick="selectCategoryOption(this)">
                                {{ $category->name }}
                            </div>
                        @endforeach
                    </div>
                    <input type="hidden" name="category" id="categoryInput" value="{{ request('category') }}">
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Cari</button>
            @if(request('search') || request('category'))
                <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Reset</a>
            @endif
        </form>
    </div>
    <div class="card-body">
        @if($expenses->isEmpty())
            <div style="text-align: center; padding: 40px; color: var(--text-muted);">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="#CBD5E1" style="margin-bottom: 16px;">
                    <path d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/>
                </svg>
                <p>Belum ada data pengeluaran.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Nama Pengeluaran</th>
                            <th>Kategori</th>
                            <th>Jumlah</th>
                            <th>Catatan</th>
                            <th style="text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expenses as $expense)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($expense->date)->format('d M Y') }}</td>
                            <td>
                                <div style="font-weight: 600; color: var(--text-main);">{{ $expense->name }}</div>
                            </td>
                            <td>
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; background-color: #E0E7FF; color: #4338ca;">
                                    {{ $expense->category->name }}
                                </span>
                            </td>
                            <td style="font-weight: 600;">Rp {{ number_format($expense->amount, 0, ',', '.') }}</td>
                            <td style="color: var(--text-muted); font-size: 13px;">{{ $expense->notes ?: '-' }}</td>
                            <td style="text-align: right;">
                                <div style="display: flex; justify-content: flex-end; gap: 6px;">
                                    <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-secondary" style="padding: 6px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                    </a>
                                    <form action="{{ route('expenses.destroy', $expense) }}" method="POST" onsubmit="event.preventDefault(); showDeleteModal(this);" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger" style="padding: 6px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;" title="Hapus">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="white"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal" class="custom-modal-overlay" style="display: none;">
    <div class="custom-modal-content">
        <div class="modal-icon warning-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 6h18"></path>
                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                <line x1="10" y1="11" x2="10" y2="17"></line>
                <line x1="14" y1="11" x2="14" y2="17"></line>
            </svg>
        </div>
        <h3 class="modal-title">Hapus Pengeluaran</h3>
        <p class="modal-text">Apakah Anda yakin ingin menghapus data pengeluaran ini? Tindakan ini tidak dapat dibatalkan.</p>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Batal</button>
            <button type="button" class="btn" style="background: #ef4444; color: white; border: none; padding: 10px 20px; font-weight: 500;" onclick="confirmDelete()">Ya, Hapus</button>
        </div>
    </div>
</div>

<style>
.custom-modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    opacity: 0;
    transition: opacity 0.3s ease;
}
.custom-modal-overlay.show {
    opacity: 1;
}
.custom-modal-content {
    background: white;
    border-radius: 16px;
    padding: 32px;
    width: 100%;
    max-width: 400px;
    text-align: center;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    transform: scale(0.95) translateY(10px);
    transition: all 0.3s ease;
}
.custom-modal-overlay.show .custom-modal-content {
    transform: scale(1) translateY(0);
}
.modal-icon {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
}
.warning-icon {
    background: #fef2f2;
    color: #ef4444;
}
.modal-title {
    font-size: 20px;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 8px;
}
.modal-text {
    font-size: 14px;
    color: #64748b;
    margin-bottom: 24px;
    line-height: 1.5;
}
.modal-actions {
    display: flex;
    justify-content: center;
    gap: 12px;
}
.modal-actions .btn-secondary {
    padding: 10px 20px;
    font-weight: 500;
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
    function toggleCategorySelect() {
        document.getElementById('categorySelectWrapper').classList.toggle('open');
    }

    function selectCategoryOption(element) {
        const value = element.getAttribute('data-value');
        const text = element.textContent.trim();
        
        document.getElementById('categoryInput').value = value;
        const textElement = document.getElementById('categorySelectText');
        textElement.textContent = text;
        
        if (value === "") {
            textElement.classList.add('text-placeholder');
        } else {
            textElement.classList.remove('text-placeholder');
        }
        
        const options = document.querySelectorAll('#categorySelectWrapper .custom-option');
        options.forEach(opt => opt.classList.remove('selected'));
        element.classList.add('selected');
        
        document.getElementById('categorySelectWrapper').classList.remove('open');
    }

    document.addEventListener('click', function(e) {
        const selectWrapper = document.getElementById('categorySelectWrapper');
        if (selectWrapper && !selectWrapper.contains(e.target)) {
            selectWrapper.classList.remove('open');
        }
    });

    window.addEventListener('DOMContentLoaded', () => {
        const selectedOption = document.querySelector('#categorySelectWrapper .custom-option.selected');
        if (selectedOption) {
            const textElement = document.getElementById('categorySelectText');
            textElement.textContent = selectedOption.textContent.trim();
            if (selectedOption.getAttribute('data-value') !== "") {
                textElement.classList.remove('text-placeholder');
            } else {
                textElement.classList.add('text-placeholder');
            }
        }
    });

    let formToSubmit = null;

    function showDeleteModal(form) {
        formToSubmit = form;
        const modal = document.getElementById('deleteConfirmModal');
        modal.style.display = 'flex';
        // Trigger reflow untuk animasi
        void modal.offsetWidth;
        modal.classList.add('show');
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteConfirmModal');
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
            formToSubmit = null;
        }, 300);
    }

    function confirmDelete() {
        if (formToSubmit) {
            formToSubmit.submit();
        }
    }
</script>
@endpush

@endsection
