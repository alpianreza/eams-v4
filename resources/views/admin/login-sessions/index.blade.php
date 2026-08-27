@extends('layouts.app')

@section('title', 'Login Sessions')

@section('content')
<x-page-header
    variant="card"
    tone="soft"
    eyebrow="Administrasi"
    eyebrow-icon="bi-person-lines-fill"
    title="Login Sessions"
/>
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
<div class="card"><div class="card-body p-0 table-responsive">
    <table class="table table-sm table-striped mb-0">
        <thead><tr><th>User</th><th>Login</th><th>Terakhir aktif</th><th>Perangkat</th><th>Status</th><th></th></tr></thead>
        <tbody>
        @forelse($sessions as $s)
            <tr>
                <td>{{ $s->username ?? $s->user->name ?? '—' }}</td>
                <td>{{ $s->started_at?->format('Y-m-d H:i') }}</td>
                <td>{{ $s->last_seen_at?->diffForHumans() }}</td>
                <td class="text-muted small">{{ $s->browser }} / {{ $s->platform }}</td>
                <td><span class="badge bg-{{ $s->is_active ? 'success' : 'secondary' }}">{{ $s->is_active ? 'aktif' : 'berakhir' }}</span></td>
                <td class="text-end">@if($s->is_active)<form method="POST" action="{{ route('admin.login-sessions.end', $s) }}" onsubmit="return confirm('Akhiri sesi ini?')">@csrf<button class="btn btn-sm btn-outline-danger">Akhiri</button></form>@endif</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Tidak ada sesi.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
{{ $sessions->links() }}
@endsection
