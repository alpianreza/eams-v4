@extends('layouts.app')

@section('title', 'Tambah Inventory')

@section('content')
@php
    $selectedItem = $itemTypes->first(fn ($item) => (string) $item->id === (string) old('asset_item_type_id'));
    $showExpiry = $selectedItem?->code === 'APAR';
@endphp

<x-page-header
    variant="card"
    tone="inventory"
    eyebrow="Compliance inventory"
    eyebrow-icon="bi-plus-circle"
    title="Tambah inventory baru"
    lead="Lengkapi identitas dan penempatan aset. Kode dapat dibuat otomatis dengan format legacy."
    :back-url="route('compliance.inventory.index')"
/>

<form method="POST" action="{{ route('compliance.inventory.store') }}" enctype="multipart/form-data" class="form-shell">
    @csrf

    <section class="form-section">
        <div class="form-section__heading">
            <span class="form-section__icon"><i class="bi bi-card-list" aria-hidden="true"></i></span>
            <div>
                <h2 class="form-section__title">Identitas inventory</h2>
                <p class="form-section__lead">Kategori dan jenis item menentukan struktur kode serta checklist aset.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="inventory_category_id" class="form-label">Kategori compliance <span class="text-danger">*</span></label>
                <select id="inventory_category_id" name="inventory_category_id" class="form-select @error('inventory_category_id') is-invalid @enderror" required>
                    <option value="">Pilih kategori</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('inventory_category_id') === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('inventory_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="asset_item_type_id" class="form-label">Nama item / inventory <span class="text-danger">*</span></label>
                <select id="asset_item_type_id" name="asset_item_type_id" class="form-select @error('asset_item_type_id') is-invalid @enderror" required>
                    <option value="">Pilih item</option>
                    @foreach($itemTypes as $item)
                        <option value="{{ $item->id }}" data-code="{{ $item->code }}" @selected((string) old('asset_item_type_id') === (string) $item->id)>{{ $item->name }}</option>
                    @endforeach
                </select>
                @error('asset_item_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="asset_code" class="form-label">Nomor inventory</label>
                <input id="asset_code" type="text" name="asset_code" value="{{ old('asset_code') }}" class="form-control text-mono @error('asset_code') is-invalid @enderror" placeholder="Contoh: APAR-001">
                @error('asset_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Kosongkan untuk membuat kode otomatis. Kode manual dipertahankan persis dan duplikat akan ditolak.</div>
            </div>

            <div class="col-md-6">
                <label for="type_description" class="form-label">Tipe / spesifikasi</label>
                <input id="type_description" type="text" name="type_description" value="{{ old('type_description') }}" class="form-control @error('type_description') is-invalid @enderror" placeholder="Contoh: 3,5 Kg / CO2 / Thermatic">
                @error('type_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="form-section__heading">
            <span class="form-section__icon"><i class="bi bi-geo-alt" aria-hidden="true"></i></span>
            <div>
                <h2 class="form-section__title">Penempatan &amp; kondisi</h2>
                <p class="form-section__lead">Tentukan lokasi fisik, kondisi operasional, jumlah, dan masa berlaku.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="area_id" class="form-label">Area</label>
                <select id="area_id" name="area_id" class="form-select @error('area_id') is-invalid @enderror">
                    <option value="">Pilih area</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}" @selected((string) old('area_id') === (string) $area->id)>{{ $area->name }}</option>
                    @endforeach
                </select>
                @error('area_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="specific_area" class="form-label">Lokasi spesifik</label>
                <input id="specific_area" type="text" name="specific_area" value="{{ old('specific_area') }}" class="form-control @error('specific_area') is-invalid @enderror" placeholder="Contoh: Office Lt. 1 / Line A">
                @error('specific_area')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-5">
                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                    <option value="good" @selected(old('status', 'good') === 'good')>Baik</option>
                    <option value="need_repair" @selected(old('status') === 'need_repair')>Perlu perbaikan</option>
                    <option value="not_active" @selected(old('status') === 'not_active')>Tidak aktif</option>
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
                <label for="qty" class="form-label">Jumlah <span class="text-danger">*</span></label>
                <div class="input-group">
                    <input id="qty" type="number" name="qty" value="{{ old('qty', 1) }}" min="1" class="form-control @error('qty') is-invalid @enderror" required>
                    <span class="input-group-text">unit</span>
                </div>
                @error('qty')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4" id="expiry-field" @if(!$showExpiry) hidden @endif>
                <label for="expired_date" class="form-label">Tanggal kedaluwarsa <span class="text-body-secondary">(APAR)</span></label>
                <input id="expired_date" type="date" name="expired_date" value="{{ old('expired_date') }}" class="form-control @error('expired_date') is-invalid @enderror">
                @error('expired_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="form-section__heading">
            <span class="form-section__icon"><i class="bi bi-people" aria-hidden="true"></i></span>
            <div>
                <h2 class="form-section__title">PIC &amp; dokumentasi</h2>
                <p class="form-section__lead">Pilih maksimal dua PIC dengan kedudukan setara dan tambahkan dokumentasi awal bila tersedia.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <label for="pic_ids" class="form-label">Person in charge</label>
                <select id="pic_ids" name="pic_ids[]" class="form-select @error('pic_ids') is-invalid @enderror" multiple size="5">
                    @foreach($picUsers as $user)
                        <option value="{{ $user->id }}" @selected(in_array((string) $user->id, array_map('strval', old('pic_ids', [])), true))>{{ $user->name }} · {{ $user->username }}</option>
                    @endforeach
                </select>
                @error('pic_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Gunakan Ctrl/Cmd untuk memilih dua nama. Tidak ada PIC primer.</div>
            </div>

            <div class="col-lg-6">
                <label for="photo" class="form-label">Foto inventory</label>
                <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="form-control @error('photo') is-invalid @enderror">
                @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">JPG, PNG, atau WebP. Maksimum {{ number_format(config('eams.upload.max_kb', 5120) / 1024, 0) }} MB.</div>
            </div>

            <div class="col-12">
                <label for="remark" class="form-label">Catatan</label>
                <textarea id="remark" name="remark" class="form-control @error('remark') is-invalid @enderror" rows="3" placeholder="Kondisi, petunjuk lokasi, atau informasi tambahan...">{{ old('remark') }}</textarea>
                @error('remark')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <div class="form-actions">
        <a href="{{ route('compliance.inventory.index') }}" class="btn btn-outline-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1" aria-hidden="true"></i> Simpan inventory
        </button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    const itemSelect = document.getElementById('asset_item_type_id');
    const expiryField = document.getElementById('expiry-field');

    if (!itemSelect || !expiryField) return;

    function updateExpiryVisibility() {
        const option = itemSelect.options[itemSelect.selectedIndex];
        expiryField.hidden = !(option && option.dataset.code === 'APAR');
    }

    itemSelect.addEventListener('change', updateExpiryVisibility);
    updateExpiryVisibility();
})();
</script>
@endpush
