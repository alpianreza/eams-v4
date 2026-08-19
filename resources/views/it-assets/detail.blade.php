@extends('layouts.app')

@section('title', $asset->asset_name)

@section('content')
<h1 class="h4 mb-1">{{ $asset->asset_name }}</h1>
<p class="text-muted"><code>{{ $asset->inventory_no }}</code> · status {{ $asset->status }}</p>
@if(session('status'))<div class="alert alert-success py-2">{{ session('status') }}</div>@endif
@if($errors->any())<div class="alert alert-danger py-2">{{ $errors->first() }}</div>@endif

<div class="card mb-3"><div class="card-body">
    <h2 class="h6">Pemegang saat ini</h2>
    @if($asset->currentAssignment?->employee)
        <p>{{ $asset->currentAssignment->employee->name }} <small class="text-muted">sejak {{ $asset->currentAssignment->assigned_at?->format('Y-m-d') }}</small></p>
        @can('write')<form method="POST" action="{{ route('it-assets.return', $asset) }}">@csrf<button class="btn btn-sm btn-outline-secondary">Kembalikan</button></form>@endcan
    @else
        <p class="text-muted">Tidak sedang dipinjam.</p>
    @endif
</div></div>

<div class="card"><div class="card-body">
    <h2 class="h6">Riwayat Assignment</h2>
    <table class="table table-sm">
        <thead><tr><th>Karyawan</th><th>Assign</th><th>Kembali</th></tr></thead>
        <tbody>
        @foreach($asset->assignments->sortByDesc('assigned_at') as $asg)
            <tr><td>{{ $asg->employee->name ?? '—' }}</td><td>{{ $asg->assigned_at?->format('Y-m-d H:i') }}</td><td>{{ $asg->returned_at?->format('Y-m-d H:i') ?? '— (aktif)' }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div></div>
@endsection
