@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
<x-page-header
    variant="command"
    eyebrow="Pusat operasi compliance"
    eyebrow-icon="bi-grid-1x2"
    :title="'Selamat datang, ' . auth()->user()->name"
    lead="Pantau prioritas, selesaikan checklist, dan tindak lanjuti temuan dalam satu tampilan."
/>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-semibold">Tugas Checklist Saya</span>
                <span class="badge text-bg-secondary">{{ count($pending) }}</span>
            </div>
            <div class="card-body">
                @forelse($pending as $inv)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                        <div>
                            <code>{{ $inv->asset_code }}</code>
                            <span class="text-body-secondary">{{ $inv->itemType->name ?? '' }}</span>
                        </div>
                        <a href="{{ route('compliance.checklist.fill', $inv) }}" class="btn btn-sm btn-primary">Isi</a>
                    </div>
                @empty
                    <div class="text-body-secondary">Tidak ada checklist pending.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header"><span class="fw-semibold">Notifikasi</span></div>
            <div class="card-body">
                <p class="mb-2">Belum dibaca: <strong>{{ $unreadNotifications }}</strong></p>
                <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-primary">Lihat Notifikasi</a>
            </div>
        </div>
    </div>
</div>
@endsection
