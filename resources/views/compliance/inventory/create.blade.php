@extends('layouts.app')

@section('title', 'Tambah Inventory')

@section('content')
<x-page-header
    variant="card"
    tone="inventory"
    eyebrow="Compliance"
    eyebrow-icon="bi-box-seam"
    title="Tambah Compliance Inventory"
/>

@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif

<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('compliance.inventory.store') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Kategori Compliance</label>
                <select name="inventory_category_id" class="form-select" required>
                    <option value="">— pilih kategori —</option>
                    @foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('inventory_category_id')==$c->id)>{{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Nama Item / Inventory</label>
                <select name="asset_item_type_id" id="asset_item_type_id" class="form-select" required>
                    <option value="">— pilih item —</option>
                    @foreach($itemTypes as $t)<option value="{{ $t->id }}" data-code="{{ $t->code }}" @selected(old('asset_item_type_id')==$t->id)>{{ $t->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">No Inventaris</label>
                <input type="text" name="asset_code" value="{{ old('asset_code') }}" class="form-control" placeholder="Kosongkan untuk generate otomatis">
                <div class="form-text">Dipertahankan persis bila diisi (mis. APAR-001). Duplikat ditolak, tidak di-rename.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Tipe / Spesifikasi</label>
                <input type="text" name="type_description" value="{{ old('type_description') }}" class="form-control" placeholder="3,5 Kg / CO2 / Thermatic">
            </div>
            <div class="col-md-6">
                <label class="form-label">Area</label>
                <select name="area_id" class="form-select">
                    <option value="">— pilih area —</option>
                    @foreach($areas as $a)<option value="{{ $a->id }}" @selected(old('area_id')==$a->id)>{{ $a->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Specific Area</label>
                <input type="text" name="specific_area" value="{{ old('specific_area') }}" class="form-control" placeholder="Office Lt. 1 / Line A">
            </div>
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" required>
                    <option value="good" @selected(old('status','good')==='good')>Good</option>
                    <option value="need_repair" @selected(old('status')==='need_repair')>Need Repair</option>
                    <option value="not_active" @selected(old('status')==='not_active')>Not Active</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Qty</label>
                <input type="number" name="qty" value="{{ old('qty',1) }}" min="1" class="form-control" required>
            </div>
            <div class="col-md-3" id="expiry-field" style="display:none">
                <label class="form-label">Expired Date <small class="text-muted">(APAR)</small></label>
                <input type="date" name="expired_date" value="{{ old('expired_date') }}" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label">PIC (maks 2, setara)</label>
                <select name="pic_ids[]" class="form-select" multiple size="3">
                    @foreach($picUsers as $u)<option value="{{ $u->id }}">{{ $u->name }} · {{ $u->username }}</option>@endforeach
                </select>
                <div class="form-text">Maksimal 2 PIC, keduanya setara (tidak ada primary). Tahan Ctrl/Cmd untuk pilih 2.</div>
            </div>
            <div class="col-12">
                <label class="form-label">Remark</label>
                <textarea name="remark" class="form-control" rows="2">{{ old('remark') }}</textarea>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('compliance.inventory.index') }}" class="btn btn-outline-secondary">Batal</a>
        </div>
    </form>
</div></div>

<script>
// Q-018: expiry field is shown mainly for APAR item types.
(function () {
    const itemSelect = document.getElementById('asset_item_type_id');
    const expiry = document.getElementById('expiry-field');
    function toggle() {
        const opt = itemSelect.options[itemSelect.selectedIndex];
        expiry.style.display = (opt && opt.dataset.code === 'APAR') ? '' : 'none';
    }
    itemSelect.addEventListener('change', toggle);
    toggle();
})();
</script>
@endsection
