@extends('layouts.app')

@section('title', 'Area - Master Data')

@section('content')
<div class="eams:mx-auto eams:max-w-4xl eams:space-y-4" data-eams-page="master-areas">
    <x-ui.page-header eyebrow="Master Data" eyebrow-icon="geo-alt" title="Area"
                      lead="Daftar lokasi/area untuk penempatan inventory." />

    @can('manage-master-data')
        <x-ui.card>
            <form method="POST" action="{{ route('master-data.areas.store') }}" class="eams:flex eams:flex-wrap eams:items-end eams:gap-2">
                @csrf
                <div class="eams:flex-1 eams:min-w-[14rem]">
                    <x-ui.input name="name" label="Nama area" placeholder="Contoh: Gedung A - Lt. 1" required />
                </div>
                <x-ui.button type="submit" variant="primary" icon="plus-lg">Tambah</x-ui.button>
            </form>
        </x-ui.card>
    @endcan

    <x-ui.table label="Daftar area">
        <thead>
            <tr>
                <th scope="col">Nama</th>
                <th scope="col">Status</th>
                @can('manage-master-data')<th scope="col" class="eams:text-right">Aksi</th>@endcan
            </tr>
        </thead>
        <tbody>
            @forelse($areas as $area)
                <tr wire:key="area-{{ $area->id }}">
                    <td class="eams:text-[13px] eams:font-semibold eams:text-ink">{{ $area->name }}</td>
                    <td><x-ui.badge :variant="$area->active ? 'success' : 'neutral'" size="sm">{{ $area->active ? 'Aktif' : 'Nonaktif' }}</x-ui.badge></td>
                    @can('manage-master-data')
                        <td class="eams:text-right">
                            <form method="POST" action="{{ route('master-data.areas.destroy', $area) }}" onsubmit="return confirm('Hapus area ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="eams:inline-flex eams:min-h-8 eams:items-center eams:gap-1.5 eams:rounded-eams eams:border eams:border-danger/40 eams:bg-danger-soft eams:px-2.5 eams:text-xs eams:font-semibold eams:text-danger eams:transition-colors eams:hover:bg-danger eams:hover:text-white">Hapus</button>
                            </form>
                        </td>
                    @endcan
                </tr>
            @empty
                <tr><td colspan="3"><x-ui.empty-state icon="geo-alt" title="Belum ada area" :boxed="false" /></td></tr>
            @endforelse
        </tbody>
    </x-ui.table>

    @if(method_exists($areas, 'hasPages') && $areas->hasPages())
        <x-ui.pagination :paginator="$areas" label="Navigasi halaman area" />
    @endif
</div>
@endsection
