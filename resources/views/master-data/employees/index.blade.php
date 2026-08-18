@extends('layouts.app')

@section('title', 'Karyawan — Master Data')

@section('content')
<h1 class="h4 mb-3">Karyawan</h1>

@can('manage-master-data')
<div class="card mb-4"><div class="card-body">
    <form method="POST" action="{{ route('master-data.employees.store') }}" class="row g-2">
        @csrf
        <div class="col-md-2"><input type="text" name="employee_id" class="form-control" placeholder="NIK" required></div>
        <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Nama" required></div>
        <div class="col-md-2"><input type="text" name="division" class="form-control" placeholder="Divisi" required></div>
        <div class="col-md-2"><input type="text" name="position" class="form-control" placeholder="Jabatan" required></div>
        <div class="col-md-2">
            <select name="status" class="form-select"><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select>
        </div>
        <div class="col-md-1"><button type="submit" class="btn btn-primary w-100">Tambah</button></div>
    </form>
</div></div>
@endcan

<div class="card"><div class="card-body p-0">
    <table class="table table-striped mb-0">
        <thead><tr><th>NIK</th><th>Nama</th><th>Divisi</th><th>Jabatan</th><th>Status</th>@can('manage-master-data')<th class="text-end">Aksi</th>@endcan</tr></thead>
        <tbody>
        @forelse($employees as $employee)
            <tr>
                <td><code>{{ $employee->employee_id }}</code></td>
                <td>{{ $employee->name }}</td>
                <td>{{ $employee->division }}</td>
                <td>{{ $employee->position }}</td>
                <td>{{ $employee->status === 'active' ? 'Aktif' : 'Nonaktif' }}</td>
                @can('manage-master-data')
                <td class="text-end">
                    <form method="POST" action="{{ route('master-data.employees.destroy', $employee) }}" class="d-inline" onsubmit="return confirm('Hapus karyawan ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                </td>
                @endcan
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada karyawan.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>

{{ $employees->links() }}
@endsection
