@extends('layouts.app')

@section('title', 'IT Device Monitoring')

@section('content')
<h1 class="h4 mb-3">IT Device Monitoring</h1>
<p class="text-muted small">Online bila heartbeat ≤ {{ config('eams.device_online_threshold_seconds') }} detik ({{ intdiv(config('eams.device_online_threshold_seconds'),60) }} menit) terakhir.</p>

<div class="card"><div class="card-body p-0">
    <table class="table table-striped mb-0">
        <thead><tr><th>Hostname</th><th>IP</th><th>MAC</th><th>Agent</th><th>Last Seen</th><th>Status</th></tr></thead>
        <tbody>
        @forelse($devices as $d)
            <tr>
                <td>{{ $d->hostname ?? '—' }}</td>
                <td>{{ $d->last_ip ?? '—' }}</td>
                <td><code>{{ $d->mac_address ?? '—' }}</code></td>
                <td>{{ $d->agent_version ?? '—' }}</td>
                <td>{{ $d->last_seen?->diffForHumans() ?? '—' }}</td>
                <td>
                    @if($d->isOnline())<span class="badge bg-success">ONLINE</span>
                    @else<span class="badge bg-secondary">OFFLINE</span>@endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada device.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>

{{ $devices->links() }}
@endsection
