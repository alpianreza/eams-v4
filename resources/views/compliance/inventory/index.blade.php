@extends('layouts.app')

@section('title', 'Compliance Inventory')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Compliance Inventory</h1>
    @can('manage-inventory')
    <a href="{{ route('compliance.inventory.create') }}" class="btn btn-primary btn-sm">+ Tambah</a>
    @endcan
</div>

<form method="GET" class="row g-2 mb-3">
    <div class="col-auto"><input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="Cari kode..."></div>
    <div class="col-auto">
        <select name="area_id" class="form-select form-select-sm">
            <option value="">— Semua Area —</option>
            @foreach($areas as $area)<option value="{{ $area->id }}" @selected(request('area_id')==$area->id)>{{ $area->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-auto">
        <select name="status" class="form-select form-select-sm">
            <option value="">— Semua Status —</option>
            <option value="good" @selected(request('status')==='good')>Good</option>
            <option value="need_repair" @selected(request('status')==='need_repair')>Need Repair</option>
            <option value="not_active" @selected(request('status')==='not_active')>Not Active</option>
        </select>
    </div>
    <div class="col-auto"><button class="btn btn-sm btn-outline-secondary">Filter</button></div>
</form>

<div class="card"><div class="card-body p-0">
    <table class="table table-striped table-hover mb-0">
        <thead><tr>
            <th>No Inventaris</th><th>Item</th><th>Area / Specific</th><th>Status</th><th>PIC</th><th>Expiry</th><th>QR</th>
            @can('manage-inventory')<th class="text-end">Aksi</th>@endcan
        </tr></thead>
        <tbody>
        @forelse($inventories as $inv)
            <tr>
                <td><a href="{{ route('compliance.inventory.detail', $inv) }}" class="fw-semibold text-decoration-none"><code>{{ $inv->asset_code }}</code></a></td>
                <td>{{ $inv->itemType->name ?? '—' }}</td>
                <td>{{ $inv->area->name ?? '—' }}@if($inv->specific_area) <small class="text-muted">/ {{ $inv->specific_area }}</small>@endif</td>
                <td><span class="badge bg-{{ $inv->status==='good'?'success':($inv->status==='need_repair'?'warning text-dark':'secondary') }}">{{ str_replace('_',' ',$inv->status) }}</span></td>
                <td>{{ $inv->pics->pluck('name')->join(' - ') ?: '—' }}</td>
                <td>
                    @if($inv->expired_date)
                        <span class="{{ $inv->isExpired() ? 'text-danger fw-bold' : '' }}">{{ $inv->expired_date }}</span>
                        @if($inv->isExpired())<span class="badge bg-danger">EXPIRED</span>@endif
                    @else —
                    @endif
                </td>
                <td>@if($inv->qr_image)<span class="text-muted small">✓</span>@endif</td>
                @can('manage-inventory')
                <td class="text-end">
                    <a href="{{ route('compliance.inventory.edit', $inv) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                    <form method="POST" action="{{ route('compliance.inventory.destroy', $inv) }}" class="d-inline" onsubmit="return confirm('Hapus inventory ini?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                </td>
                @endcan
            </tr>
        @empty
            <tr><td colspan="8" class="text-center text-muted py-4">Belum ada inventory.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>

{{ $inventories->links() }}
@endsection
