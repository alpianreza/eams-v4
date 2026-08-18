@extends('layouts.app')

@section('title', 'Area — Master Data')

@section('content')
<h1 class="h4 mb-3">Area</h1>

@can('manage-master-data')
<div class="card mb-4"><div class="card-body">
    <form method="POST" action="{{ route('master-data.areas.store') }}" class="row g-2">
        @csrf
        <div class="col-auto"><input type="text" name="name" class="form-control" placeholder="Nama area" required></div>
        <div class="col-auto"><button type="submit" class="btn btn-primary">Tambah</button></div>
    </form>
</div></div>
@endcan

<div class="card"><div class="card-body p-0">
    <table class="table table-striped mb-0">
        <thead><tr><th>Nama</th><th>Status</th>@can('manage-master-data')<th class="text-end">Aksi</th>@endcan</tr></thead>
        <tbody>
        @forelse($areas as $area)
            <tr>
                <td>{{ $area->name }}</td>
                <td>{{ $area->active ? 'Aktif' : 'Nonaktif' }}</td>
                @can('manage-master-data')
                <td class="text-end">
                    <form method="POST" action="{{ route('master-data.areas.destroy', $area) }}" class="d-inline" onsubmit="return confirm('Hapus area ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                </td>
                @endcan
            </tr>
        @empty
            <tr><td colspan="3" class="text-center text-muted py-4">Belum ada area.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>

{{ $areas->links() }}
@endsection
