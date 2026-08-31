@extends('layouts.app')

@section('title', 'Item Types - Master Data')

@section('content')
<div class="eams:mx-auto eams:max-w-5xl eams:space-y-4" data-eams-page="master-item-types">
    <x-ui.page-header eyebrow="Master Data" eyebrow-icon="list-check" title="Item Types"
                      lead="Jenis inventory beserta frekuensi checklist-nya (kode dipakai asset_code dan resolusi perilaku, Q-015)." />

    @can('manage-master-data')
        <x-ui.card>
            <form method="POST" action="{{ route('master-data.item-types.store') }}" class="eams:grid eams:gap-3 eams:md:grid-cols-2 eams:xl:grid-cols-3">
                @csrf
                <x-ui.select name="inventory_category_id" label="Kategori" placeholder="Pilih kategori" required>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.input name="name" label="Nama item" placeholder="mis. APAR" required />
                <x-ui.input name="code" label="Kode" placeholder="APAR" required />
                <x-ui.select name="checklist_frequency" label="Frekuensi checklist" required>
                    <option value="daily">Harian</option>
                    <option value="weekly">Mingguan</option>
                    <option value="monthly">Bulanan</option>
                </x-ui.select>
                <div class="eams:flex eams:items-end eams:gap-4 eams:md:col-span-2 eams:xl:col-span-1">
                    <x-ui.checkbox name="allow_na" label="Izinkan NA" hint="Q-001: NA hanya bila diizinkan item type." />
                </div>
                <div class="eams:flex eams:items-end eams:md:col-span-2 eams:xl:col-span-1">
                    <x-ui.button type="submit" variant="primary" icon="plus-lg">Tambah</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endcan

    <x-ui.table label="Daftar item type">
        <thead>
            <tr>
                <th scope="col">Nama</th>
                <th scope="col">Kode</th>
                <th scope="col">Kategori</th>
                <th scope="col">Frekuensi</th>
                <th scope="col">NA</th>
                @can('manage-master-data')<th scope="col" class="eams:text-right">Aksi</th>@endcan
            </tr>
        </thead>
        <tbody>
            @forelse($itemTypes as $type)
                <tr wire:key="item-type-{{ $type->id }}">
                    <td class="eams:text-[13px] eams:font-semibold eams:text-ink">{{ $type->name }}</td>
                    <td class="eams:font-mono eams:text-[13px] eams:text-muted">{{ $type->code }}</td>
                    <td class="eams:text-[13px] eams:text-muted">{{ $type->category->name ?? '-' }}</td>
                    <td class="eams:text-[13px] eams:capitalize eams:text-muted">{{ $type->checklist_frequency }}</td>
                    <td><x-ui.badge :variant="$type->allow_na ? 'info' : 'neutral'" size="sm">{{ $type->allow_na ? 'Ya' : 'Tidak' }}</x-ui.badge></td>
                    @can('manage-master-data')
                        <td class="eams:text-right">
                            <form method="POST" action="{{ route('master-data.item-types.destroy', $type) }}" onsubmit="return confirm('Hapus item type ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="eams:inline-flex eams:min-h-8 eams:items-center eams:gap-1.5 eams:rounded-eams eams:border eams:border-danger/40 eams:bg-danger-soft eams:px-2.5 eams:text-xs eams:font-semibold eams:text-danger eams:transition-colors eams:hover:bg-danger eams:hover:text-white">Hapus</button>
                            </form>
                        </td>
                    @endcan
                </tr>
            @empty
                <tr><td colspan="6"><x-ui.empty-state icon="list-check" title="Belum ada item type" :boxed="false" /></td></tr>
            @endforelse
        </tbody>
    </x-ui.table>

    @if(method_exists($itemTypes, 'hasPages') && $itemTypes->hasPages())
        <x-ui.pagination :paginator="$itemTypes" label="Navigasi halaman item type" />
    @endif
</div>
@endsection
