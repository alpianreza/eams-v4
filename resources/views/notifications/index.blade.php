@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
<x-page-header
    variant="card"
    tone="soft"
    eyebrow="Utama"
    eyebrow-icon="bi-bell"
    title="Notifikasi"
>
    <x-slot:actions>
        <form method="POST" action="{{ route('notifications.read-all') }}">@csrf<button class="btn btn-sm btn-outline-secondary">Tandai semua dibaca</button></form>
    </x-slot:actions>
</x-page-header>

<div class="card"><div class="card-body p-0">
    @forelse($notifications as $n)
        <div class="d-flex justify-content-between align-items-start border-bottom p-3 {{ $n->isRead() ? '' : 'bg-light' }}">
            <div>
                <div class="fw-semibold">{{ $n->title }} @if(!$n->isRead())<span class="badge bg-primary">baru</span>@endif</div>
                @if($n->body)<div class="text-muted small">{{ $n->body }}</div>@endif
                <div class="text-muted small">{{ $n->created_at->diffForHumans() }}</div>
            </div>
            <div class="d-flex gap-2">
                @if($n->url)<a href="{{ $n->url }}" class="btn btn-sm btn-outline-primary">Buka</a>@endif
                @if(!$n->isRead())
                    <form method="POST" action="{{ route('notifications.read', $n) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Tandai dibaca</button></form>
                @endif
            </div>
        </div>
    @empty
        <div class="text-center text-muted py-5">Tidak ada notifikasi.</div>
    @endforelse
</div></div>
{{ $notifications->links() }}
@endsection
