@extends('layouts.app')

@section('title', 'Thermal Imaging')

@section('content')
<h1 class="h4 mb-3">Thermal Imaging</h1>
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif

@can('write')
<div class="card mb-3"><div class="card-body">
    <form method="POST" action="{{ route('thermal.store') }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-auto"><label class="form-label small">Tanggal Inspeksi</label><input type="date" name="inspection_date" class="form-control form-control-sm" required></div>
        <div class="col-auto"><label class="form-label small">Inspektor</label><input type="text" name="inspector_name" class="form-control form-control-sm"></div>
        <div class="col-auto"><label class="form-label small">Fasilitas</label><input type="text" name="facility" class="form-control form-control-sm"></div>
        <div class="col-auto"><label class="form-label small">Area</label><input type="text" name="area_name" class="form-control form-control-sm"></div>
        <div class="col-auto"><button class="btn btn-sm btn-primary">Buat Report</button></div>
    </form>
</div></div>
@endcan

<div class="card"><div class="card-body p-0">
    <table class="table table-striped mb-0">
        <thead><tr><th>Tanggal</th><th>Inspektor</th><th>Fasilitas/Area</th><th>Item</th><th></th></tr></thead>
        <tbody>
        @forelse($reports as $r)
            <tr>
                <td>{{ $r->inspection_date }}</td>
                <td>{{ $r->inspector_name ?? '—' }}</td>
                <td>{{ $r->facility ?? '—' }}@if($r->area_name) / {{ $r->area_name }}@endif</td>
                <td>{{ $r->items_count }}</td>
                <td class="text-end"><a href="{{ route('thermal.show', $r) }}" class="btn btn-sm btn-outline-primary">Buka</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada report.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
{{ $reports->links() }}
@endsection
