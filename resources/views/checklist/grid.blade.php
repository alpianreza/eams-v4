@extends('layouts.app')

@section('title', 'Grid Checklist — ' . $itemType->name)

@section('content')
<h1 class="h4 mb-1">Grid Checklist — {{ $itemType->name }} <code>{{ $itemType->code }}</code></h1>
<p class="text-muted">Fast/mass entry · Periode: <strong>{{ $periodKey }}</strong> · Grid boleh tanpa foto/keterangan untuk NOT_OK.</p>

@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

<div class="d-flex gap-2 mb-3">
    <form method="POST" action="{{ route('compliance.checklist.grid.mark-all', $itemType) }}">
        @csrf
        <input type="hidden" name="status" value="ok">
        <button class="btn btn-sm btn-success" @disabled($inventories->isEmpty())>Mark all OK (isi sel kosong)</button>
    </form>
    <form method="POST" action="{{ route('compliance.checklist.grid.clear', $itemType) }}" onsubmit="return confirm('Hapus semua sel grid periode ini?')">
        @csrf
        <button class="btn btn-sm btn-outline-danger" @disabled($inventories->isEmpty())>Clear periode</button>
    </form>
</div>

<form method="POST" action="{{ route('compliance.checklist.grid.set', $itemType) }}">
    @csrf
    <div class="card"><div class="card-body p-0 table-responsive">
        <table class="table table-bordered table-sm mb-0 align-middle">
            <thead><tr>
                <th>Inventory</th>
                @foreach($questions as $q)<th>{{ $q->question }}</th>@endforeach
            </tr></thead>
            <tbody>
            @forelse($inventories as $inv)
                <tr>
                    <td><code>{{ $inv->asset_code }}</code><br><small class="text-muted">{{ $inv->specific_area }}</small></td>
                    @foreach($questions as $q)
                        @php($cur = $existing[$inv->id][$q->id] ?? null)
                        <td>
                            <select name="cell_{{ $inv->id }}_{{ $q->id }}" class="form-select form-select-sm">
                                <option value="">—</option>
                                <option value="ok" @selected($cur==='ok')>OK</option>
                                <option value="not_ok" @selected($cur==='not_ok')>NOT OK</option>
                                @if($allowNa)<option value="na" @selected($cur==='na')>NA</option>@endif
                            </select>
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ $questions->count()+1 }}" class="text-center text-muted py-4">Tidak ada inventory aktif untuk item type ini.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div></div>
    @if($inventories->isNotEmpty() && $questions->isNotEmpty())
    <button type="submit" class="btn btn-primary mt-3">Simpan Grid</button>
    @endif
</form>
@endsection
