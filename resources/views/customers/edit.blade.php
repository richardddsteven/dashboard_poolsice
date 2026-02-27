@extends('layouts.dashboard')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Edit Pelanggan</h1>
        <p class="page-subtitle" style="margin-bottom: 24px;">Perbarui informasi data pelanggan.</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Formulir Edit Pelanggan</h3>
    </div>

    <div class="card-body">
        <form action="{{ route('customers.update', $customer) }}" method="POST">
            @csrf
            @method('PUT')

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; margin-bottom: 24px;">
                <!-- Name -->
                <div>
                    <label for="name" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Nama Lengkap <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $customer->name) }}" required placeholder="Masukkan nama pelanggan" style="width: 100%;">
                    @error('name')
                        <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Nomor Telepon <span style="color: #ef4444;">*</span></label>
                    <input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $customer->phone) }}" required placeholder="Contoh: 08123456789" style="width: 100%;">
                    @error('phone')
                        <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <label for="zone" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Zona Wilayah</label>
                <select name="zone" id="zone" class="form-select" style="width: 100%;">
                    <option value="">Pilih Zona</option>
                    @foreach($zones as $zone)
                        <option value="{{ $zone->name }}" {{ old('zone', $customer->zone) === $zone->name ? 'selected' : '' }}>
                            {{ ucfirst($zone->name) }}
                        </option>
                    @endforeach
                </select>
                @error('zone')
                    <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-bottom: 24px;">
                <label for="address" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-main);">Alamat Lengkap</label>
                <textarea name="address" id="address" class="form-control" rows="4" placeholder="Masukkan alamat lengkap pelanggan" style="width: 100%; resize: vertical;">{{ old('address', $customer->address) }}</textarea>
                @error('address')
                    <div style="color: #ef4444; font-size: 13px; margin-top: 6px;">{{ $message }}</div>
                @enderror
            </div>

            <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid var(--border-color); display: flex; gap: 12px; justify-content: flex-end;">
                <a href="{{ route('customers.index') }}" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="white" style="margin-right: 6px;">
                        <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/>
                    </svg>
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
