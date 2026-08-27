@extends('layouts.app')

@section('title', 'Edit Inventory')

@section('content')
<x-page-header
    variant="card"
    tone="inventory"
    eyebrow="Compliance inventory"
    eyebrow-icon="bi-pencil-square"
    :title="'Edit ' . $inventory->asset_code"
    lead="Perbarui kondisi, lokasi spesifik, PIC, dan dokumentasi tanpa mengubah identitas utama aset."
    :back-url="route('compliance.inventory.detail', $inventory)"
>
    <x-slot:actions>
        <a href="{{ route('compliance.inventory.detail', $inventory) }}" class="btn btn-outline-secondary">
            <i class="bi bi-eye me-1" aria-hidden="true"></i> Lihat detail
        </a>
    </x-slot:actions>
</x-page-header>

<form method="POST" action="{{ route('compliance.inventory.update', $inventory) }}" enctype="multipart/form-data" class="form-shell">
    @csrf
    @method('PUT')

    <section class="form-section">
        <div class="form-section__heading">
            <span class="form-section__icon"><i class="bi bi-lock" aria-hidden="true"></i></span>
            <div>
                <h2 class="form-section__title">Identitas terkunci</h2>
                <p class="form-section__lead">Kategori, item, area utama, dan nomor inventory tidak dapat diubah setelah aset dibuat.</p>
            </div>
        </div>

        <div class="locked-fields">
            <div class="locked-field">
                <span class="locked-field__label">Kategori</span>
                <span class="locked-field__value">{{ $inventory->category->name ?? '—' }}</span>
            </div>
            <div class="locked-field">
                <span class="locked-field__label">Item</span>
                <span class="locked-field__value">{{ $inventory->itemType->name ?? '—' }}</span>
            </div>
            <div class="locked-field">
                <span class="locked-field__label">Area utama</span>
                <span class="locked-field__value">{{ $inventory->area->name ?? '—' }}</span>
            </div>
            <div class="locked-field">
                <span class="locked-field__label">Nomor inventory</span>
                <span class="locked-field__value text-mono">{{ $inventory->asset_code }}</span>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="form-section__heading">
            <span class="form-section__icon"><i class="bi bi-sliders" aria-hidden="true"></i></span>
            <div>
                <h2 class="form-section__title">Kondisi &amp; penempatan</h2>
                <p class="form-section__lead">Data operasional berikut dapat diperbarui sesuai kondisi lapangan.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="specific_area" class="form-label">Lokasi spesifik</label>
                <input id="specific_area" type="text" name="specific_area" value="{{ old('specific_area', $inventory->specific_area) }}" class="form-control @error('specific_area') is-invalid @enderror" placeholder="Contoh: Office Lt. 1 / Line A">
                @error('specific_area')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="type_description" class="form-label">Tipe / spesifikasi</label>
                <input id="type_description" type="text" name="type_description" value="{{ old('type_description', $inventory->type_description) }}" class="form-control @error('type_description') is-invalid @enderror">
                @error('type_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-4">
                <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" @selected(old('status', $inventory->status) === $status)>{{ match($status) { 'good' => 'Baik', 'need_repair' => 'Perlu perbaikan', default => 'Tidak aktif' } }}</option>
                    @endforeach
                </select>
                @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-2">
                <label for="qty" class="form-label">Jumlah <span class="text-danger">*</span></label>
                <input id="qty" type="number" name="qty" value="{{ old('qty', $inventory->qty) }}" min="1" class="form-control @error('qty') is-invalid @enderror" required>
                @error('qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3">
                <label for="expired_date" class="form-label">Tanggal kedaluwarsa</label>
                <input id="expired_date" type="date" name="expired_date" value="{{ old('expired_date', $inventory->expired_date) }}" class="form-control @error('expired_date') is-invalid @enderror">
                @error('expired_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check form-switch mb-2">
                    <input type="hidden" name="active" value="0">
                    <input id="active" type="checkbox" name="active" value="1" class="form-check-input" @checked((bool) old('active', $inventory->active))>
                    <label for="active" class="form-check-label">Inventory aktif</label>
                </div>
            </div>
        </div>
    </section>

    <section class="form-section">
        <div class="form-section__heading">
            <span class="form-section__icon"><i class="bi bi-people" aria-hidden="true"></i></span>
            <div>
                <h2 class="form-section__title">PIC &amp; dokumentasi</h2>
                <p class="form-section__lead">Perbarui penanggung jawab, foto, dan catatan pendukung.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <label for="pic_ids" class="form-label">Person in charge</label>
                @php($currentPics = old('pic_ids', $inventory->pics->pluck('id')->all()))
                <select id="pic_ids" name="pic_ids[]" class="form-select @error('pic_ids') is-invalid @enderror" multiple size="5">
                    @foreach($picUsers as $user)
                        <option value="{{ $user->id }}" @selected(in_array((string) $user->id, array_map('strval', $currentPics), true))>{{ $user->name }} · {{ $user->username }}</option>
                    @endforeach
                </select>
                @error('pic_ids')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-text">Maksimal dua PIC dengan kedudukan setara.</div>
            </div>

            <div class="col-lg-6">
                <label for="photo" class="form-label">Ganti foto inventory</label>
                <input id="photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="form-control @error('photo') is-invalid @enderror">
                @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($inventory->photo)
                    <div class="form-text">
                        Foto tersimpan tersedia. <a href="{{ route('files.show', ['category' => 'inventory', 'path' => $inventory->photo]) }}" target="_blank" rel="noopener">Lihat foto saat ini</a>.
                    </div>
                @else
                    <div class="form-text">Belum ada foto. JPG, PNG, atau WebP.</div>
                @endif
            </div>

            <div class="col-12">
                <label for="remark" class="form-label">Catatan</label>
                <textarea id="remark" name="remark" class="form-control @error('remark') is-invalid @enderror" rows="3" placeholder="Kondisi, petunjuk lokasi, atau informasi tambahan...">{{ old('remark', $inventory->remark) }}</textarea>
                @error('remark')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </section>

    <div class="form-actions">
        <a href="{{ route('compliance.inventory.detail', $inventory) }}" class="btn btn-outline-secondary">Batal</a>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1" aria-hidden="true"></i> Simpan perubahan
        </button>
    </div>
</form>
@endsection
