@extends('layouts.app')

@section('title', 'Kategori Inventory — Master Data')

@section('content')
<h1 class="h4 mb-3">Kategori Inventory</h1>

@can('manage-master-data')
<div class="card mb-4"><div class="card-body">
    <form method="POST" action="{{ route('master-data.categories.store') }}" class="row g-2">
        @csrf
        <div class="col-auto"><input type="text" name="name" class="form-control" placeholder="Nama kategori" required></div>
        <div class="col-auto"><input type="text" name="code" class="form-control" placeholder="Kode (mis. FS)"></div>
        <div class="col-auto"><button type="submit" class="btn btn-primary">Tambah</button></div>
    </form>
</div></div>
@endcan

<div class="card"><div class="card-body p-0">
    <table class="table table-striped mb-0">
        <thead><tr><th>Nama</th><th>Kode</th>@can('manage-master-data')<th class="text-end">Aksi</th>@endcan</tr></thead>
        <tbody>
        @forelse($categories as $category)
            <tr>
                <td>{{ $category->name }}</td>
                <td><code>{{ $category->code }}</code></td>
                @can('manage-master-data')
                <td class="text-end">
                    <form method="POST" action="{{ route('master-data.categories.destroy', $category) }}" class="d-inline" onsubmit="return confirm('Hapus kategori ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                </td>
                @endcan
            </tr>
        @empty
            <tr><td colspan="3" class="text-center text-muted py-4">Belum ada kategori.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>

{{ $categories->links() }}
@endsection
