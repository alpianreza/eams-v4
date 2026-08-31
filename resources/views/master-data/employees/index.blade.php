@extends('layouts.app')

@section('title', 'Karyawan - Master Data')

@section('content')
<div class="eams:mx-auto eams:max-w-5xl eams:space-y-4" data-eams-page="master-employees">
    <x-ui.page-header eyebrow="Master Data" eyebrow-icon="people" title="Karyawan"
                      lead="Data karyawan untuk assignment IT asset (BR-31/32)." />

    @can('manage-master-data')
        <x-ui.card>
            <form method="POST" action="{{ route('master-data.employees.store') }}" class="eams:grid eams:gap-3 eams:md:grid-cols-4 eams:xl:grid-cols-5">
                @csrf
                <x-ui.input name="employee_id" label="NIK" required />
                <x-ui.input name="name" label="Nama" required />
                <x-ui.input name="division" label="Divisi" required />
                <x-ui.input name="position" label="Jabatan" required />
                <div class="eams:grid eams:gap-3 eams:md:col-span-2 eams:xl:col-span-1">
                    <x-ui.select name="status" label="Status">
                        <option value="active">Aktif</option>
                        <option value="inactive">Nonaktif</option>
                    </x-ui.select>
                </div>
                <div class="eams:md:col-span-4 eams:xl:col-span-5">
                    <x-ui.button type="submit" variant="primary" icon="plus-lg">Tambah</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    @endcan

    <x-ui.table label="Daftar karyawan">
        <thead>
            <tr>
                <th scope="col">NIK</th>
                <th scope="col">Nama</th>
                <th scope="col">Divisi</th>
                <th scope="col">Jabatan</th>
                <th scope="col">Status</th>
                @can('manage-master-data')<th scope="col" class="eams:text-right">Aksi</th>@endcan
            </tr>
        </thead>
        <tbody>
            @forelse($employees as $employee)
                <tr wire:key="employee-{{ $employee->id }}">
                    <td class="eams:font-mono eams:text-[13px] eams:text-muted">{{ $employee->employee_id }}</td>
                    <td class="eams:text-[13px] eams:font-semibold eams:text-ink">{{ $employee->name }}</td>
                    <td class="eams:text-[13px] eams:text-muted">{{ $employee->division }}</td>
                    <td class="eams:text-[13px] eams:text-muted">{{ $employee->position }}</td>
                    <td><x-ui.badge :variant="$employee->status === 'active' ? 'success' : 'neutral'" size="sm">{{ $employee->status === 'active' ? 'Aktif' : 'Nonaktif' }}</x-ui.badge></td>
                    @can('manage-master-data')
                        <td class="eams:text-right">
                            <form method="POST" action="{{ route('master-data.employees.destroy', $employee) }}" onsubmit="return confirm('Hapus karyawan ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="eams:inline-flex eams:min-h-8 eams:items-center eams:gap-1.5 eams:rounded-eams eams:border eams:border-danger/40 eams:bg-danger-soft eams:px-2.5 eams:text-xs eams:font-semibold eams:text-danger eams:transition-colors eams:hover:bg-danger eams:hover:text-white">Hapus</button>
                            </form>
                        </td>
                    @endcan
                </tr>
            @empty
                <tr><td colspan="6"><x-ui.empty-state icon="people" title="Belum ada karyawan" :boxed="false" /></td></tr>
            @endforelse
        </tbody>
    </x-ui.table>

    @if(method_exists($employees, 'hasPages') && $employees->hasPages())
        <x-ui.pagination :paginator="$employees" label="Navigasi halaman karyawan" />
    @endif
</div>
@endsection
