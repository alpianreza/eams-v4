@extends('layouts.app')

@section('title', 'EMS — ' . $label)

@section('content')
<h1 class="h4 mb-1">EMS Report — {{ $label }}</h1>
<p class="text-muted small">Konsumsi bulanan per seksi · 1 entri per seksi/bulan/tahun.</p>
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

<div class="d-flex gap-2 align-items-center mb-3 flex-wrap">
    @foreach($categories as $key => $catLabel)
        <a href="{{ route('ems.index', ['category' => $key, 'year' => $year]) }}" class="btn btn-sm {{ $key===$category ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $catLabel }}</a>
    @endforeach
    <form method="GET" class="d-flex gap-2 ms-auto"><input type="number" name="year" value="{{ $year }}" class="form-control form-control-sm" style="width:100px"><button class="btn btn-sm btn-outline-secondary">Tahun</button></form>
</div>

@can('write')
<div class="card mb-3"><div class="card-body">
    <form method="POST" action="{{ route('ems.entry.save', $category) }}" class="row g-2 align-items-end">
        @csrf
        <input type="hidden" name="report_year" value="{{ $year }}">
        <div class="col-auto"><label class="form-label small">Seksi</label><input type="text" name="section_key" class="form-control form-control-sm" required placeholder="mis. produksi-a"></div>
        <div class="col-auto"><label class="form-label small">Bulan</label><select name="report_month" class="form-select form-select-sm">@for($m=1;$m<=12;$i++ ?? 0)@php($m=$m ?? 0)@endfor @for($m=1;$m<=12;$m++)<option value="{{ $m }}">{{ $m }}</option>@endfor</select></div>
        <div class="col-auto"><label class="form-label small">Konsumsi</label><input type="number" step="0.001" name="consumption_amount" class="form-control form-control-sm"></div>
        <div class="col-auto"><button class="btn btn-sm btn-primary">Simpan</button></div>
    </form>
</div></div>
@endcan

<div class="card"><div class="card-body p-0 table-responsive">
    <table class="table table-bordered table-sm mb-0">
        <thead><tr><th>Seksi</th>@for($m=1;$m<=12;$m++)<th class="text-center">{{ $m }}</th>@endfor<th class="text-end">Total</th></tr></thead>
        <tbody>
        @forelse($matrix as $section => $months)
            <tr>
                <td><code>{{ $section }}</code></td>
                @php($rowTotal = 0)
                @for($m=1;$m<=12;$m++)
                    <td class="text-center">{{ $months[$m] ?? '—' }}</td>
                    @php($rowTotal += (float)($months[$m] ?? 0))
                @endfor
                <td class="text-end fw-bold">{{ number_format($rowTotal, 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="14" class="text-center text-muted py-4">Belum ada entri untuk tahun {{ $year }}.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>

@if($yearMeta)
<p class="text-muted small mt-2">Production output {{ $year }}: <strong>{{ $yearMeta->production_output ?? '—' }}</strong>@if($yearMeta->notes) · {{ $yearMeta->notes }}@endif</p>
@endif
@endsection
