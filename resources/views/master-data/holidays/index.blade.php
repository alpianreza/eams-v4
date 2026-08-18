@extends('layouts.app')

@section('title', 'Hari Libur — Master Data')

@section('content')
<h1 class="h4 mb-3">Hari Libur</h1>

@can('manage-master-data')
<div class="card mb-4"><div class="card-body">
    <form method="POST" action="{{ route('master-data.holidays.store') }}" class="row g-2">
        @csrf
        <div class="col-auto"><input type="date" name="holiday_date" class="form-control" required></div>
        <div class="col-auto"><input type="text" name="description" class="form-control" placeholder="Keterangan"></div>
        <div class="col-auto"><button type="submit" class="btn btn-primary">Tambah</button></div>
    </form>
</div></div>
@endcan

<div class="card"><div class="card-body p-0">
    <table class="table table-striped mb-0">
        <thead><tr><th>Tanggal</th><th>Keterangan</th>@can('manage-master-data')<th class="text-end">Aksi</th>@endcan</tr></thead>
        <tbody>
        @forelse($holidays as $holiday)
            <tr>
                <td>{{ $holiday->holiday_date->format('Y-m-d') }}</td>
                <td>{{ $holiday->description }}</td>
                @can('manage-master-data')
                <td class="text-end">
                    <form method="POST" action="{{ route('master-data.holidays.destroy', $holiday) }}" class="d-inline" onsubmit="return confirm('Hapus hari libur ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                </td>
                @endcan
            </tr>
        @empty
            <tr><td colspan="3" class="text-center text-muted py-4">Belum ada hari libur.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>

{{ $holidays->links() }}
@endsection
