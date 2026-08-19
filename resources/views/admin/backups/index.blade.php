@extends('layouts.app')

@section('title', 'Backups')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h4 mb-0">Backups</h1>
    <form method="POST" action="{{ route('admin.backups.store') }}">@csrf<button class="btn btn-sm btn-primary">Buat Backup</button></form>
</div>
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
<p class="text-muted small">Retensi: {{ $retentionDays }} hari (otomatis dipangkas oleh scheduler).</p>

<div class="card"><div class="card-body p-0">
    <table class="table table-striped mb-0">
        <thead><tr><th>File</th><th>Tipe</th><th>Ukuran</th><th>Status</th><th>Dibuat</th></tr></thead>
        <tbody>
        @forelse($backups as $b)
            <tr>
                <td><code>{{ $b->filename }}</code></td>
                <td>{{ $b->type }}</td>
                <td>{{ number_format($b->size_bytes / 1024, 1) }} KB</td>
                <td><span class="badge bg-{{ $b->status==='done'?'success':'danger' }}">{{ $b->status }}</span></td>
                <td>{{ $b->created_at?->format('Y-m-d H:i') }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-center text-muted py-4">Belum ada backup.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
@endsection
