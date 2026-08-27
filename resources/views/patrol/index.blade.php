@extends('layouts.app')

@section('title', 'Patrol Harian')

@section('content')
<x-page-header
    variant="card"
    tone="patrol"
    eyebrow="Security"
    eyebrow-icon="bi-shield-check"
    title="Patrol Harian"
/>
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif

<div class="row g-3">
    <div class="col-md-5">
        <div class="card"><div class="card-body">
            <h2 class="h6">Mulai Sesi Patrol</h2>
            @foreach($routes as $route)
                <form method="POST" action="{{ route('patrol.start') }}" class="d-flex justify-content-between align-items-center border-bottom py-2">
                    @csrf
                    <input type="hidden" name="patrol_route_id" value="{{ $route->id }}">
                    <div><strong>{{ $route->name }}</strong><br><small class="text-muted">{{ $route->checkpoints_count }} checkpoint</small></div>
                    @can('write')<button class="btn btn-sm btn-primary">Mulai</button>@endcan
                </form>
            @endforeach
        </div></div>
    </div>
    <div class="col-md-7">
        <div class="card"><div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead><tr><th>Tanggal</th><th>Rute</th><th>Progres</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($sessions as $s)
                    <tr>
                        <td>{{ $s->patrol_date }}</td>
                        <td>{{ $s->route->name ?? '—' }}</td>
                        <td>{{ $s->checked_count }}/{{ $s->total_checkpoints }}@if($s->issue_count) <span class="text-danger">({{ $s->issue_count }} isu)</span>@endif</td>
                        <td><span class="badge bg-{{ $s->status==='completed'?'success':($s->status==='active'?'primary':'secondary') }}">{{ $s->status }}</span></td>
                        <td class="text-end">@if($s->status==='active')<a href="{{ route('patrol.session', $s) }}" class="btn btn-sm btn-outline-primary">Lanjut</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada sesi.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div></div>
        {{ $sessions->links() }}
    </div>
</div>
@endsection
