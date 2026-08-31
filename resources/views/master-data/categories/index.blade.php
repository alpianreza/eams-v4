@extends('layouts.app')

@section('title', 'Kategori - Master Data')

@section('content')
<div class="eams:mx-auto eams:max-w-4xl eams:space-y-4" data-eams-page="master-categories">
    <x-ui.page-header eyebrow="Master Data" eyebrow-icon="tags" title="Kategori"
                      lead="Kategori compliance untuk pengelompokan item type (kode dipakai format asset_code)." />

    @can('manage-master-data')
        <x-ui.card>
            <form method="POST" action="{{ route('master-data.categories.store') }}" class="eams:grid eams:gap-3 eams:sm:grid-cols-[1fr_10rem_auto] eams:sm:items-end">
                @csrf
                <x-ui.input name="name" label="Nama kategori" placeholder="Contoh: Fire Safety" required />
                <x-ui.input name="code" label="Kode" placeholder="mis. FS" />
                <x-ui.button type="submit" variant="primary" icon="plus-lg">Tambah</x-ui.button>
            </form>
        </x-ui.card>
    @endcan

    <x-ui.table label="Daftar kategori">
        <thead>
            <tr>
                <th scope="col">Nama</th>
                <th scope="col">Kode</th>
                @can('manage-master-data')<th scope="col" class="eams:text-right">Aksi</th>@endcan
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $category)
                <tr wire:key="category-{{ $category->id }}">
                    <td class="eams:text-[13px] eams:font-semibold eams:text-ink">{{ $category->name }}</td>
                    <td class="eams:font-mono eams:text-[13px] eams:text-muted">{{ $category->code }}</td>
                    @can('manage-master-data')
                        <td class="eams:text-right">
                            <form method="POST" action="{{ route('master-data.categories.destroy', $category) }}" onsubmit="return confirm('Hapus kategori ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="eams:inline-flex eams:min-h-8 eams:items-center eams:gap-1.5 eams:rounded-eams eams:border eams:border-danger/40 eams:bg-danger-soft eams:px-2.5 eams:text-xs eams:font-semibold eams:text-danger eams:transition-colors eams:hover:bg-danger eams:hover:text-white">Hapus</button>
                            </form>
                        </td>
                    @endcan
                </tr>
            @empty
                <tr><td colspan="3"><x-ui.empty-state icon="tags" title="Belum ada kategori" :boxed="false" /></td></tr>
            @endforelse
        </tbody>
    </x-ui.table>

    @if(method_exists($categories, 'hasPages') && $categories->hasPages())
        <x-ui.pagination :paginator="$categories" label="Navigasi halaman kategori" />
    @endif
</div>
@endsection
