@extends('layouts.app')

@section('title', 'FDM Data Collection')

@section('content')
<h1 class="h4 mb-1">FDM Data Collection</h1>
<p class="text-muted small">Data seksi produksi per tahun dengan nilai bulanan.</p>
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif

<form method="GET" class="d-flex gap-2 mb-3"><input type="number" name="year" value="{{ $year }}" class="form-control form-control-sm" style="width:100px"><button class="btn btn-sm btn-outline-secondary">Tahun</button></form>

@can('write')
<div class="card mb-3"><div class="card-body">
    <form method="POST" action="{{ route('fdm.entry.save') }}">
        @csrf
        <input type="hidden" name="report_year" value="{{ $year }}">
        <div class="row g-2 align-items-end mb-2">
            <div class="col-auto"><label class="form-label small">Section Key</label><input type="text" name="section_key" class="form-control form-control-sm" required></div>
            <div class="col-auto"><label class="form-label small">Label</label><input type="text" name="section_label" class="form-control form-control-sm"></div>
            <div class="col-auto"><label class="form-label small">Tipe</label><input type="text" name="entry_type" class="form-control form-control-sm"></div>
            <div class="col-auto"><label class="form-label small">Frekuensi</label><input type="text" name="frequency_label" class="form-control form-control-sm"></div>
        </div>
        <div class="row g-1 mb-2">
            @for($m=1;$m<=12;$m++)<div class="col"><label class="form-label small">B{{ $m }}</label><input type="number" step="0.01" name="monthly_values[{{ $m }}]" class="form-control form-control-sm"></div>@endfor
        </div>
        <button class="btn btn-sm btn-primary">Simpan Entri</button>
    </form>
</div></div>
@endcan

<div class="card"><div class="card-body p-0 table-responsive">
    <table class="table table-bordered table-sm mb-0">
        <thead><tr><th>Seksi</th>@for($m=1;$m<=12;$m++)<th class="text-center">{{ $m }}</th>@endfor<th class="text-end">Total</th></tr></thead>
        <tbody>
        @forelse($yearModel->entries as $entry)
            <tr>
                <td><code>{{ $entry->section_key }}</code><br><small class="text-muted">{{ $entry->section_label }}</small></td>
                @for($m=1;$m<=12;$m++)<td class="text-center">{{ $entry->monthly_values[$m] ?? '—' }}</td>@endfor
                <td class="text-end fw-bold">{{ number_format($entry->yearlyTotal(), 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="14" class="text-center text-muted py-4">Belum ada entri untuk {{ $year }}.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endsection
