@extends('layouts.app')

@section('title', 'Inventaris IT')

@section('content')
<x-page-header
    variant="card"
    tone="it"
    eyebrow="IT"
    eyebrow-icon="bi-laptop"
    title="Inventaris IT"
/>
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

@can('write')
<div class="card mb-3"><div class="card-body">
    <form method="POST" action="{{ route('it-assets.store') }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-auto"><label class="form-label small">No. Inventaris</label><input type="text" name="inventory_no" class="form-control form-control-sm" required></div>
        <div class="col-auto"><label class="form-label small">Nama Asset</label><input type="text" name="asset_name" class="form-control form-control-sm" required></div>
        <div class="col-auto"><label class="form-label small">Brand</label><input type="text" name="brand" class="form-control form-control-sm"></div>
        <div class="col-auto"><label class="form-label small">Status</label><select name="status" class="form-select form-select-sm">@foreach(['aktif','dipinjam','rusak','nonaktif'] as $s)<option value="{{ $s }}">{{ $s }}</option>@endforeach</select></div>
        <div class="col-auto"><button class="btn btn-sm btn-primary">Tambah</button></div>
    </form>
</div></div>
@endcan

<div class="card"><div class="card-body p-0">
    <table class="table table-striped mb-0">
        <thead><tr><th>No. Inv</th><th>Nama</th><th>Brand</th><th>Status</th><th>Dipinjam oleh</th><th></th></tr></thead>
        <tbody>
        @forelse($assets as $a)
            <tr>
                <td><code>{{ $a->inventory_no }}</code></td>
                <td>{{ $a->asset_name }}</td>
                <td>{{ $a->brand ?? '—' }}</td>
                <td><span class="badge bg-{{ $a->status==='aktif'?'success':($a->status==='rusak'?'danger':'secondary') }}">{{ $a->status }}</span></td>
                <td>{{ $a->currentAssignment?->employee?->name ?? '—' }}</td>
                <td class="text-end"><a href="{{ route('it-assets.detail', $a) }}" class="btn btn-sm btn-outline-primary">Detail</a></td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada asset.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
{{ $assets->links() }}
@endsection
