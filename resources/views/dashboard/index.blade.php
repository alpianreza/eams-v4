@extends('layouts.app')

@section('title', 'Dashboard Compliance')

@section('content')
<h1 class="h4 mb-3">Dashboard Compliance</h1>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><div class="display-6 fw-bold">{{ $total }}</div><div class="text-muted small">Inventory aktif</div></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><div class="display-6 fw-bold text-primary">{{ $open }}</div><div class="text-muted small">Checklist open</div></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><div class="display-6 fw-bold text-danger">{{ $late }}</div><div class="text-muted small">Checklist late</div></div></div></div>
    <div class="col-md-3"><div class="card text-center"><div class="card-body"><div class="display-6 fw-bold text-warning">{{ $expired }}</div><div class="text-muted small">Expired (mis. APAR)</div></div></div></div>
</div>

<div class="card"><div class="card-body">
    <h2 class="h6">Status Kondisi Inventory</h2>
    <div class="d-flex gap-3">
        <span class="badge bg-success">GOOD: {{ $byStatus['good'] ?? 0 }}</span>
        <span class="badge bg-warning text-dark">NEED_REPAIR: {{ $byStatus['need_repair'] ?? 0 }}</span>
        <span class="badge bg-secondary">NOT_ACTIVE: {{ $byStatus['not_active'] ?? 0 }}</span>
    </div>
</div></div>
@endsection
