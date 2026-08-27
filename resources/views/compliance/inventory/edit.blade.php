@extends('layouts.app')

@section('title', 'Edit Inventory')

@section('content')
<x-page-header
    variant="card"
    tone="inventory"
    eyebrow="Compliance"
    eyebrow-icon="bi-box-seam"
    :title="'Edit Inventory — ' . $inventory->asset_code"
/>

@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="card"><div class="card-body">
    {{-- BR-45: category / area / item type are LOCKED on edit (shown read-only). --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3"><label class="form-label text-muted">Kategori</label><div class="form-control-plaintext">{{ $inventory->category->name ?? '—' }}</div></div>
        <div class="col-md-3"><label class="form-label text-muted">Item</label><div class="form-control-plaintext">{{ $inventory->itemType->name ?? '—' }}</div></div>
        <div class="col-md-3"><label class="form-label text-muted">Area</label><div class="form-control-plaintext">{{ $inventory->area->name ?? '—' }}</div></div>
        <div class="col-md-3"><label class="form-label text-muted">No Inventaris</label><div class="form-control-plaintext"><code>{{ $inventory->asset_code }}</code></div></div>
    </div>

    <form method="POST" action="{{ route('compliance.inventory.update', $inventory) }}">
        @csrf @method('PUT')
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Specific Area</label>
                <input type="text" name="specific_area" value="{{ old('specific_area', $inventory->specific_area) }}" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">Tipe / Spesifikasi</label>
                <input type="text" name="type_description" value="{{ old('type_description', $inventory->type_description) }}" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    @foreach($statuses as $s)<option value="{{ $s }}" @selected(old('status', $inventory->status)===$s)>{{ str_replace('_',' ',$s) }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Qty</label>
                <input type="number" name="qty" value="{{ old('qty', $inventory->qty) }}" min="1" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Expired Date <small class="text-muted">(APAR)</small></label>
                <input type="date" name="expired_date" value="{{ old('expired_date', $inventory->expired_date) }}" class="form-control">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <div class="form-check">
                    <input type="checkbox" name="active" value="1" id="active" class="form-check-input" @checked(old('active', $inventory->active))>
                    <label for="active" class="form-check-label">Aktif</label>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">PIC (maks 2, setara)</label>
                <select name="pic_ids[]" class="form-select" multiple size="3">
                    @php($current = $inventory->pics->pluck('id')->all())
                    @foreach($picUsers as $u)<option value="{{ $u->id }}" @selected(in_array($u->id, old('pic_ids', $current)))>{{ $u->name }} · {{ $u->username }}</option>@endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Remark</label>
                <textarea name="remark" class="form-control" rows="2">{{ old('remark', $inventory->remark) }}</textarea>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('compliance.inventory.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div></div>
@endsection
