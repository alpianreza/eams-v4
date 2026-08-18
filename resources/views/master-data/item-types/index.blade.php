@extends('layouts.app')

@section('title', 'Item Type — Master Data')

@section('content')
<h1 class="h4 mb-3">Asset Item Type</h1>

@can('manage-master-data')
<div class="card mb-4"><div class="card-body">
    <form method="POST" action="{{ route('master-data.item-types.store') }}" class="row g-2">
        @csrf
        <div class="col-md-3">
            <select name="inventory_category_id" class="form-select" required>
                <option value="">— Kategori —</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Nama item (mis. APAR)" required></div>
        <div class="col-md-2"><input type="text" name="code" class="form-control" placeholder="Kode (APAR)" required></div>
        <div class="col-md-2">
            <select name="checklist_frequency" class="form-select" required>
                <option value="daily">Harian</option>
                <option value="weekly">Mingguan</option>
                <option value="monthly" selected>Bulanan</option>
            </select>
        </div>
        <div class="col-md-1 form-check align-self-center">
            <input type="checkbox" name="allow_na" value="1" class="form-check-input" id="allow_na">
            <label for="allow_na" class="form-check-label">NA?</label>
        </div>
        <div class="col-md-1"><button type="submit" class="btn btn-primary w-100">Tambah</button></div>
    </form>
    <div class="form-text mt-2"><code>code</code> adalah business identifier (dipakai logika, bukan id auto-increment).</div>
</div></div>
@endcan

<div class="card"><div class="card-body p-0">
    <table class="table table-striped mb-0">
        <thead><tr><th>Nama</th><th>Kode</th><th>Kategori</th><th>Frekuensi</th><th>NA</th>@can('manage-master-data')<th class="text-end">Aksi</th>@endcan</tr></thead>
        <tbody>
        @forelse($itemTypes as $type)
            <tr>
                <td>{{ $type->name }}</td>
                <td><code>{{ $type->code }}</code></td>
                <td>{{ $type->category->name ?? '—' }}</td>
                <td>{{ $type->checklist_frequency }}</td>
                <td>{{ $type->allow_na ? 'Ya' : 'Tidak' }}</td>
                @can('manage-master-data')
                <td class="text-end">
                    <form method="POST" action="{{ route('master-data.item-types.destroy', $type) }}" class="d-inline" onsubmit="return confirm('Hapus item type ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                    </form>
                </td>
                @endcan
            </tr>
        @empty
            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada item type.</td></tr>
        @endforelse
        </tbody>
    </table>
</div></div>

{{ $itemTypes->links() }}
@endsection
