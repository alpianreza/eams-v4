@extends('layouts.app')

@section('title', 'Sesi Patrol')

@section('content')
<x-page-header
    variant="card"
    tone="patrol"
    eyebrow="Security"
    eyebrow-icon="bi-shield-check"
    :title="'Sesi Patrol — ' . ($session->route->name ?? '')"
    :lead="$session->patrol_date . ' · ' . $session->checked_count . '/' . $session->total_checkpoints . ' checkpoint · status ' . $session->status"
/>
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

@if($session->status === 'active')
<div class="card mb-3"><div class="card-body">
    <h2 class="h6">Scan Checkpoint</h2>
    <form method="POST" action="{{ route('patrol.scan', $session) }}" class="row g-2 align-items-end">
        @csrf
        <div class="col-auto"><label class="form-label small">Barcode</label><input type="text" name="barcode_value" class="form-control form-control-sm" required autofocus></div>
        <div class="col-auto"><label class="form-label small">Status</label><select name="status" class="form-select form-select-sm"><option value="ok">OK</option><option value="issue">Ada Isu</option></select></div>
        <div class="col"><label class="form-label small">Catatan</label><input type="text" name="note" class="form-control form-control-sm"></div>
        <div class="col-auto"><button class="btn btn-sm btn-primary">Catat</button></div>
    </form>
</div></div>
@endif

<div class="card"><div class="card-body p-0">
    <table class="table table-sm mb-0">
        <thead><tr><th>#</th><th>Checkpoint</th><th>Area</th><th>Status</th></tr></thead>
        <tbody>
        @foreach($session->route->checkpoints as $i => $cp)
            <tr class="{{ in_array($cp->id, $doneIds) ? 'table-success' : '' }}">
                <td>{{ $i + 1 }}</td>
                <td><code>{{ $cp->code }}</code> {{ $cp->name }}</td>
                <td>{{ $cp->area ?? '—' }}</td>
                <td>@if(in_array($cp->id, $doneIds))<span class="badge bg-success">selesai</span>@else<span class="badge bg-secondary">pending</span>@endif</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div></div>

@if($session->status === 'active')
<form method="POST" action="{{ route('patrol.cancel', $session) }}" class="mt-3" onsubmit="return confirm('Batalkan sesi ini?')">@csrf<button class="btn btn-outline-danger btn-sm">Batalkan Sesi</button></form>
@endif
@endsection
