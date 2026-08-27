@extends('layouts.app')

@section('title', 'Thermal Report ' . $report->inspection_date)

@section('content')
<x-page-header
    variant="card"
    tone="compliance"
    eyebrow="Compliance"
    eyebrow-icon="bi-thermometer-half"
    :title="'Thermal Report — ' . $report->inspection_date"
    :lead="($report->inspector_name ?? '—') . ' · ' . ($report->facility ?? '—') . ($report->area_name ? ' / ' . $report->area_name : '')"
/>
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif

@can('write')
<div class="card mb-3"><div class="card-body">
    <form method="POST" action="{{ route('thermal.items.store', $report) }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-auto"><label class="form-label small">Lokasi</label>
            <select name="location_id" class="form-select form-select-sm"><option value="">— manual —</option>@foreach($locations as $loc)<option value="{{ $loc->id }}">{{ $loc->name }}</option>@endforeach</select></div>
        <div class="col-auto"><label class="form-label small">Nama Lokasi (manual)</label><input type="text" name="location_name" class="form-control form-control-sm"></div>
        <div class="col-auto"><label class="form-label small">°C</label><input type="number" step="0.01" name="celsius" class="form-control form-control-sm"></div>
        <div class="col"><label class="form-label small">Temuan</label><input type="text" name="findings" class="form-control form-control-sm"></div>
        <div class="col-auto"><button class="btn btn-sm btn-primary">Tambah Item</button></div>
    </form>
</div></div>
@endcan

<div class="card"><div class="card-body p-0">
    <table class="table table-sm mb-0">
        <thead><tr><th>Lokasi</th><th>°C</th><th>Temuan</th><th>Rekomendasi</th></tr></thead>
        <tbody>
        @forelse($report->items as $item)
            <tr><td>{{ $item->location_name ?? '—' }}</td><td>{{ $item->celsius ?? '—' }}</td><td>{{ $item->findings ?? '—' }}</td><td>{{ $item->recommendation ?? '—' }}</td></tr>
        @empty
            <tr><td colspan="4" class="text-center text-muted py-4">Belum ada item.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endsection
