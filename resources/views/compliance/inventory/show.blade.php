@extends('layouts.app')

@section('title', 'Detail — ' . $inventory->asset_code)

@section('content')
<x-page-header
    variant="card"
    tone="inventory-detail"
    eyebrow="Compliance"
    eyebrow-icon="bi-box-seam"
    :title="$inventory->asset_code"
    :lead="($inventory->itemType->name ?? '—') . ' · ' . ($inventory->category->name ?? '—')"
/>

<div class="card">
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Area</dt><dd class="col-sm-9">{{ $inventory->area->name ?? '—' }}@if($inventory->specific_area) / {{ $inventory->specific_area }}@endif</dd>
            <dt class="col-sm-3">Status</dt><dd class="col-sm-9">{{ str_replace('_',' ',$inventory->status) }}</dd>
            <dt class="col-sm-3">Qty</dt><dd class="col-sm-9">{{ $inventory->qty }}</dd>
            <dt class="col-sm-3">PIC</dt><dd class="col-sm-9">{{ $inventory->pics->pluck('name')->join(' - ') ?: '—' }}</dd>
            <dt class="col-sm-3">Expired</dt><dd class="col-sm-9">{{ $inventory->expired_date ?? '—' }}@if($inventory->isExpired()) <span class="badge bg-danger">EXPIRED</span>@endif</dd>
            <dt class="col-sm-3">Remark</dt><dd class="col-sm-9">{{ $inventory->remark ?? '—' }}</dd>
        </dl>
        @if($inventory->qr_image)
        <div class="mt-3">
            <span class="text-muted small d-block mb-1">QR (URL identik legacy):</span>
            <img src="{{ asset('storage/qr/'.$inventory->qr_image) }}" alt="QR {{ $inventory->asset_code }}" width="150" height="150">
        </div>
        @endif
    </div>
</div>
@endsection
