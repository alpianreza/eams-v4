@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
<h1 class="h4 mb-3">Audit Logs</h1>
<div class="card"><div class="card-body p-0 table-responsive">
    <table class="table table-sm table-striped mb-0">
        <thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Deskripsi</th><th>IP</th></tr></thead>
        <tbody>
        @forelse($logs as $log)
            <tr>
                <td class="text-nowrap">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                <td>{{ $log->user->name ?? '—' }}</td>
                <td><code>{{ $log->action }}</code></td>
                <td>{{ $log->description }}</td>
                <td class="text-muted small">{{ $log->ip_address }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada audit log.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
{{ $logs->links() }}
@endsection
