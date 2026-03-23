@extends('layouts.dashboard')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Pengeluaran</h1>
        <p class="page-subtitle" style="margin-bottom: 16px;">Ubah data pengeluaran.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Form Edit Pengeluaran</h3>
    </div>
    
    <div class="card-body">
        <form action="{{ route('expenses.update', $expense) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px;">
                <!-- Tanggal -->
                <div>
                    <label for="date" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Tanggal <span style="color: #ef4444;">*</span></label>
                    <input type="date" id="date" name="date" class="form-control" value="{{ old('date', $expense->date) }}" required style="width: 100%;">
                    @error('date')
                        <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Kategori -->
                <div>
                    <label for="expense_category_id" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Kategori <span style="color: #ef4444;">*</span></label>
                    
                    <div class="custom-select-wrapper" id="categorySelectWrapper">
                        <div class="custom-select-trigger" onclick="toggleCategorySelect()">
                            <span id="categorySelectText" class="text-placeholder">Pilih Kategori</span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="select-icon"><path d="M6 9l6 6 6-6"/></svg>
                        </div>
                        <div class="custom-options">
                            <div class="custom-option {{ (old('expense_category_id', $expense->expense_category_id) == '') ? 'selected' : '' }}" data-value="" onclick="selectCategoryOption(this)">Pilih Kategori</div>
                            @foreach($categories as $category)
                                @php
                                    $isSelected = old('expense_category_id', $expense->expense_category_id) == $category->id;
                                @endphp
                                <div class="custom-option {{ $isSelected ? 'selected' : '' }}" data-value="{{ $category->id }}" onclick="selectCategoryOption(this)">
                                    {{ $category->name }}
                                </div>
                            @endforeach
                        </div>
                        <input type="hidden" name="expense_category_id" id="categoryInput" value="{{ old('expense_category_id', $expense->expense_category_id) }}">
                    </div>

                    @error('expense_category_id')
                        <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px;">
                <!-- Nama Pengeluaran -->
                <div>
                    <label for="name" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Nama Pengeluaran <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" value="{{ old('name', $expense->name) }}" placeholder="Contoh: Beli bensin mobil box" required style="width: 100%;">
                    @error('name')
                        <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Jumlah -->
                <div>
                    <label for="amount" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Jumlah (Rp) <span style="color: #ef4444;">*</span></label>
                    <input type="number" id="amount" name="amount" class="form-control" value="{{ old('amount', $expense->amount) }}" placeholder="Contoh: 150000" min="0" required style="width: 100%;">
                    @error('amount')
                        <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <label for="notes" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Catatan (Opsional)</label>
                <textarea id="notes" name="notes" class="form-control" rows="4" placeholder="Tambahkan catatan jika perlu" style="width: 100%; resize: vertical;">{{ old('notes', $expense->notes) }}</textarea>
                @error('notes')
                    <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; justify-content: flex-end;">
                <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="white" style="margin-right: 6px;">
                        <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>
                    </svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<style>
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
</script>
@endpush
@endsection
