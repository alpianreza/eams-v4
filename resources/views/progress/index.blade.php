@extends('layouts.app')

@section('title', 'Progress Monitoring')

@section('content')
<h1 class="h4 mb-3">Progress Monitoring</h1>
<p class="text-muted small">Status periode berjalan per inventory (engine periode — late time-based).</p>

<div class="card"><div class="card-body p-0 table-responsive">
    <table class="table table-sm table-striped mb-0">
        <thead><tr><th>Asset</th><th>Item</th><th>Area</th><th>Periode</th><th>Status</th><th>Cek Terakhir</th></tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr>
                <td><code>{{ $row['inventory']->asset_code }}</code></td>
                <td>{{ $row['inventory']->itemType->name ?? '—' }}</td>
                <td>{{ $row['inventory']->area->name ?? '—' }}</td>
                <td><code>{{ $row['period_key'] }}</code></td>
                <td><span class="badge bg-{{ ['DONE'=>'success','OPEN'=>'primary','LATE'=>'danger','FUTURE'=>'secondary','HOLIDAY'=>'secondary'][$row['status']] ?? 'secondary' }}">{{ $row['status'] }}</span></td>
                <td>{{ $row['last_check'] ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada inventory.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>
{{ $inventories->links() }}
@endsection
