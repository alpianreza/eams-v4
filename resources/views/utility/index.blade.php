@extends('layouts.app')

@section('title', $label)

@section('content')
<x-page-header
    variant="card"
    tone="utility"
    eyebrow="Boiler & Utility"
    eyebrow-icon="bi-droplet"
    :title="$label"
    lead="Monitoring harian per tanggal · 1 baris per hari."
/>

@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

<form method="GET" class="d-flex gap-2 align-items-center mb-3">
    <input type="month" name="month" value="{{ $month }}" class="form-control form-control-sm w-auto">
    <button class="btn btn-sm btn-outline-secondary">Tampilkan</button>
</form>

@can('write')
<div class="card mb-3"><div class="card-body">
    <form method="POST" action="{{ route('utility.store', $type) }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-auto"><label class="form-label small">Tanggal</label><input type="date" name="log_date" class="form-control form-control-sm" required></div>
        <div class="col-auto"><label class="form-label small">Jam</label><input type="time" name="log_time" class="form-control form-control-sm"></div>
        @if($type === 'boiler')
        <div class="col-auto"><label class="form-label small">Polybag</label><input type="number" name="polybag" class="form-control form-control-sm" min="0"></div>
        @endif
        <div class="col-auto"><label class="form-label small">{{ $unit ?: 'Nilai' }}</label><input type="number" step="0.01" name="{{ $valueField }}" class="form-control form-control-sm"></div>
        <div class="col"><label class="form-label small">Catatan</label><input type="text" name="note" class="form-control form-control-sm"></div>
        <div class="col-auto"><button class="btn btn-sm btn-primary">Simpan</button></div>
    </form>
</div></div>
@endcan

<div class="card"><div class="card-body p-0 table-responsive">
    <table class="table table-sm table-striped mb-0">
        <thead><tr>
            <th style="width:120px">Tanggal</th><th>Jam</th>@if($type==='boiler')<th>Polybag</th>@endif<th>{{ $unit ?: 'Nilai' }}</th><th>Catatan</th>@can('write')<th class="text-end">Aksi</th>@endcan
        </tr></thead>
        <tbody>
        @foreach($days as $d)
            @php($log = $logs[$d['date']] ?? null)
            <tr class="{{ $d['offday'] ? 'table-secondary' : '' }}">
                <td>{{ $d['date'] }}@if($d['offday']) <span class="badge bg-secondary">libur</span>@endif</td>
                <td>{{ $log?->log_time ? substr($log->log_time,0,5) : '—' }}</td>
                @if($type==='boiler')<td>{{ $log?->polybag ?? '—' }}</td>@endif
                <td>{{ $log?->{$valueField} ?? '—' }}</td>
                <td>{{ $log?->note ?? '—' }}</td>
                @can('write')
                <td class="text-end">
                    @if($log)
                    <form method="POST" action="{{ route('utility.destroy', [$type, $log->id]) }}" class="d-inline" onsubmit="return confirm('Hapus log ini?')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Hapus</button></form>
                    @endif
                </td>
                @endcan
            </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr class="fw-bold"><td colspan="{{ $type==='boiler' ? 4 : 3 }}" class="text-end">Total bulan ini</td><td colspan="2">{{ $total === null ? '—' : number_format($total, 2).' '.$unit }}</td></tr>
        </tfoot>
    </table>
</div></div>
@endsection
