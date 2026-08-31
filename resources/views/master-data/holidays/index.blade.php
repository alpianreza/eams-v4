@extends('layouts.app')

@section('title', 'Hari Libur - Master Data')

@section('content')
<div class="eams:mx-auto eams:max-w-4xl eams:space-y-4" data-eams-page="master-holidays">
    <x-ui.page-header eyebrow="Master Data" eyebrow-icon="calendar-x" title="Hari Libur"
                      lead="Libur nasional/custom - checklist harian diblokir pada tanggal ini (BR-07/08)." />

    @can('manage-master-data')
        <x-ui.card>
            <form method="POST" action="{{ route('master-data.holidays.store') }}" class="eams:grid eams:gap-3 eams:sm:grid-cols-[auto_1fr_auto] eams:sm:items-end">
                @csrf
                <x-ui.input name="holiday_date" label="Tanggal" type="date" required />
                <x-ui.input name="description" label="Keterangan" placeholder="Contoh: HUT RI" />
                <x-ui.button type="submit" variant="primary" icon="plus-lg">Tambah</x-ui.button>
            </form>
        </x-ui.card>
    @endcan

    <x-ui.table label="Daftar hari libur">
        <thead>
            <tr>
                <th scope="col">Tanggal</th>
                <th scope="col">Keterangan</th>
                @can('manage-master-data')<th scope="col" class="eams:text-right">Aksi</th>@endcan
            </tr>
        </thead>
        <tbody>
            @forelse($holidays as $holiday)
                <tr wire:key="holiday-{{ $holiday->id }}">
                    <td class="eams:tabular-nums eams:text-[13px] eams:font-semibold eams:text-ink">{{ \Illuminate\Support\Carbon::parse($holiday->holiday_date)->format('d/m/Y') }}</td>
                    <td class="eams:text-[13px] eams:text-muted">{{ $holiday->description ?? '-' }}</td>
                    @can('manage-master-data')
                        <td class="eams:text-right">
                            <form method="POST" action="{{ route('master-data.holidays.destroy', $holiday) }}" onsubmit="return confirm('Hapus hari libur ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="eams:inline-flex eams:min-h-8 eams:items-center eams:gap-1.5 eams:rounded-eams eams:border eams:border-danger/40 eams:bg-danger-soft eams:px-2.5 eams:text-xs eams:font-semibold eams:text-danger eams:transition-colors eams:hover:bg-danger eams:hover:text-white">Hapus</button>
                            </form>
                        </td>
                    @endcan
                </tr>
            @empty
                <tr><td colspan="3"><x-ui.empty-state icon="calendar-x" title="Belum ada hari libur" :boxed="false" /></td></tr>
            @endforelse
        </tbody>
    </x-ui.table>

    @if(method_exists($holidays, 'hasPages') && $holidays->hasPages())
        <x-ui.pagination :paginator="$holidays" label="Navigasi halaman hari libur" />
    @endif
</div>
@endsection
