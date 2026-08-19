@extends('layouts.app')

@section('title', 'Beranda')

@section('content')
<h1 class="h4 mb-3">Beranda</h1>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card"><div class="card-body">
            <h2 class="h6">Tugas Checklist Saya (periode berjalan)</h2>
            @forelse($pending as $inv)
                <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                    <div><code>{{ $inv->asset_code }}</code> {{ $inv->itemType->name ?? '' }}</div>
                    <a href="{{ route('compliance.checklist.fill', $inv) }}" class="btn btn-sm btn-primary">Isi</a>
                </div>
            @empty
                <div class="text-muted">Tidak ada checklist pending. 🎉</div>
            @endforelse
        </div></div>
    </div>
    <div class="col-md-5">
        <div class="card"><div class="card-body">
            <h2 class="h6">Notifikasi</h2>
            <p class="mb-2">Belum dibaca: <strong>{{ $unreadNotifications }}</strong></p>
            <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-primary">Lihat Notifikasi</a>
        </div></div>
    </div>
</div>
@endsection
