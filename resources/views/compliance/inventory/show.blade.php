@extends('layouts.app')

@section('title', 'Detail - ' . $inventory->asset_code)

@section('content')
@php
    $statusPresentation = \App\Support\Ui\StatusPresentation::for(strtoupper((string) $inventory->status));
@endphp

<div class="eams:space-y-4 eams:sm:space-y-5" data-eams-livewire="none" data-eams-page="inventory-detail">
    <header class="eams:flex eams:flex-col eams:gap-4 eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface eams:px-4 eams:py-4 eams:shadow-eams-1 eams:sm:flex-row eams:sm:items-center eams:sm:justify-between eams:sm:px-5">
        <div class="eams:flex eams:min-w-0 eams:items-center eams:gap-3">
            <span class="eams:inline-flex eams:size-11 eams:shrink-0 eams:items-center eams:justify-center eams:rounded-eams eams:bg-brand-soft eams:text-xl eams:text-brand">
                <i class="bi bi-box-seam" aria-hidden="true"></i>
            </span>
            <div class="eams:min-w-0">
                <p class="eams:mb-1 eams:text-[11px] eams:font-bold eams:uppercase eams:tracking-[0.12em] eams:text-brand">Detail compliance inventory</p>
                <h1 class="eams:m-0 eams:text-xl eams:font-extrabold eams:tracking-tight eams:text-ink eams:font-mono eams:sm:text-2xl">{{ $inventory->asset_code }}</h1>
                <p class="eams:mb-0 eams:mt-1 eams:text-[13px] eams:text-muted">{{ $inventory->itemType->name ?? 'Item tidak diketahui' }} &middot; {{ $inventory->category->name ?? 'Tanpa kategori' }}</p>
            </div>
        </div>
        <div class="eams:flex eams:flex-wrap eams:gap-2">
            @can('access-compliance-pdf')
                <x-ui.button :href="route('compliance.report.pdf', $inventory)" target="_blank" rel="noopener" variant="secondary" icon="file-earmark-pdf">
                    Laporan PDF
                </x-ui.button>
            @endcan
            @can('manage-inventory')
                <x-ui.button :href="route('compliance.inventory.edit', $inventory)" navigate variant="primary" icon="pencil">
                    Edit inventory
                </x-ui.button>
            @endcan
            <x-ui.button :href="route('compliance.checklist.fill', $inventory)" navigate variant="secondary" icon="clipboard2-check">
                Isi checklist
            </x-ui.button>
        </div>
    </header>

    <div class="eams:grid eams:grid-cols-1 eams:gap-3 eams:sm:grid-cols-2 eams:xl:grid-cols-4" aria-label="Ringkasan aset">
        <x-ui.card>
            <div class="eams:flex eams:items-center eams:gap-3">
                <span class="eams:inline-flex eams:size-9 eams:shrink-0 eams:items-center eams:justify-center eams:rounded-eams {{ $statusPresentation['tone'] === 'danger' ? 'eams:bg-danger-soft eams:text-danger' : ($statusPresentation['tone'] === 'warning' ? 'eams:bg-warning-soft eams:text-warning' : 'eams:bg-success-soft eams:text-success') }}">
                    <i class="bi bi-{{ $statusPresentation['icon'] }}" aria-hidden="true"></i>
                </span>
                <div class="eams:min-w-0">
                    <p class="eams:m-0 eams:text-xs eams:font-semibold eams:text-muted">Kondisi aset</p>
                    <x-ui.status-indicator :status="strtoupper($inventory->status)" size="md" />
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="eams:flex eams:items-center eams:gap-3">
                <span class="eams:inline-flex eams:size-9 eams:shrink-0 eams:items-center eams:justify-center eams:rounded-eams eams:bg-info-soft eams:text-info">
                    <i class="bi bi-geo-alt" aria-hidden="true"></i>
                </span>
                <div class="eams:min-w-0">
                    <p class="eams:m-0 eams:text-xs eams:font-semibold eams:text-muted">Area</p>
                    <p class="eams:mb-0 eams:truncate eams:text-sm eams:font-semibold eams:text-ink">{{ $inventory->area->name ?? 'Belum ditentukan' }}</p>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="eams:flex eams:items-center eams:gap-3">
                <span class="eams:inline-flex eams:size-9 eams:shrink-0 eams:items-center eams:justify-center eams:rounded-eams eams:bg-brand-soft eams:text-brand">
                    <i class="bi bi-boxes" aria-hidden="true"></i>
                </span>
                <div class="eams:min-w-0">
                    <p class="eams:m-0 eams:text-xs eams:font-semibold eams:text-muted">Jumlah</p>
                    <p class="eams:mb-0 eams:text-sm eams:font-semibold eams:text-ink">{{ number_format($inventory->qty) }} unit</p>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="eams:flex eams:items-center eams:gap-3">
                <span class="eams:inline-flex eams:size-9 eams:shrink-0 eams:items-center eams:justify-center eams:rounded-eams eams:bg-surface-sunk eams:text-muted">
                    <i class="bi {{ $inventory->active ? 'bi-toggle-on' : 'bi-toggle-off' }}" aria-hidden="true"></i>
                </span>
                <div class="eams:min-w-0">
                    <p class="eams:m-0 eams:text-xs eams:font-semibold eams:text-muted">Pencatatan</p>
                    <p class="eams:mb-0 eams:text-sm eams:font-semibold eams:text-ink">{{ $inventory->active ? 'Aktif' : 'Dinonaktifkan' }}</p>
                </div>
            </div>
        </x-ui.card>
    </div>

    <div class="detail-layout eams:grid eams:grid-cols-1 eams:gap-4 eams:lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="eams:space-y-4">
            <x-ui.card title="Identitas aset">
                <dl class="eams:grid eams:grid-cols-1 eams:gap-x-4 eams:gap-y-3 eams:sm:grid-cols-2">
                    <div>
                        <dt class="eams:text-xs eams:font-semibold eams:text-muted">Nomor inventory</dt>
                        <dd class="eams:mb-0 eams:mt-0.5 eams:font-mono eams:text-sm eams:text-ink">{{ $inventory->asset_code }}</dd>
                    </div>
                    <div>
                        <dt class="eams:text-xs eams:font-semibold eams:text-muted">Kategori</dt>
                        <dd class="eams:mb-0 eams:mt-0.5 eams:text-sm eams:text-ink">{{ $inventory->category->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="eams:text-xs eams:font-semibold eams:text-muted">Nama item</dt>
                        <dd class="eams:mb-0 eams:mt-0.5 eams:text-sm eams:text-ink">{{ $inventory->itemType->name ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="eams:text-xs eams:font-semibold eams:text-muted">Tipe / spesifikasi</dt>
                        <dd class="eams:mb-0 eams:mt-0.5 eams:text-sm {{ $inventory->type_description ? 'eams:text-ink' : 'eams:text-subtle' }}">{{ $inventory->type_description ?: 'Belum diisi' }}</dd>
                    </div>
                    <div>
                        <dt class="eams:text-xs eams:font-semibold eams:text-muted">Tanggal kedaluwarsa</dt>
                        <dd class="eams:mb-0 eams:mt-0.5 eams:text-sm eams:text-ink">
                            @if($inventory->expired_date)
                                <span class="eams:tabular-nums">{{ \Illuminate\Support\Carbon\Carbon::parse($inventory->expired_date)->format('d/m/Y') }}</span>
                                @if($inventory->isExpired())
                                    <x-ui.badge variant="danger" size="sm" class="eams:ml-1"><i class="bi bi-exclamation-circle-fill" aria-hidden="true"></i> Kedaluwarsa</x-ui.badge>
                                @endif
                            @else
                                <span class="eams:text-subtle">Tidak ditentukan</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="eams:text-xs eams:font-semibold eams:text-muted">Terakhir diperbarui</dt>
                        <dd class="eams:mb-0 eams:mt-0.5 eams:text-sm eams:text-ink">{{ $inventory->updated_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card title="Penempatan & tanggung jawab">
                <dl class="eams:grid eams:grid-cols-1 eams:gap-x-4 eams:gap-y-3 eams:sm:grid-cols-2">
                    <div>
                        <dt class="eams:text-xs eams:font-semibold eams:text-muted">Area utama</dt>
                        <dd class="eams:mb-0 eams:mt-0.5 eams:text-sm eams:text-ink">{{ $inventory->area->name ?? 'Belum ditentukan' }}</dd>
                    </div>
                    <div>
                        <dt class="eams:text-xs eams:font-semibold eams:text-muted">Lokasi spesifik</dt>
                        <dd class="eams:mb-0 eams:mt-0.5 eams:text-sm {{ $inventory->specific_area ? 'eams:text-ink' : 'eams:text-subtle' }}">{{ $inventory->specific_area ?: 'Belum diisi' }}</dd>
                    </div>
                    <div class="eams:sm:col-span-2">
                        <dt class="eams:text-xs eams:font-semibold eams:text-muted">Person in charge (maks. 2)</dt>
                        <dd class="eams:mb-0 eams:mt-1.5">
                            @forelse($inventory->pics as $pic)
                                <x-ui.badge variant="neutral" class="eams:mr-1 eams:mb-1"><i class="bi bi-person" aria-hidden="true"></i> {{ $pic->name }}</x-ui.badge>
                            @empty
                                <span class="eams:text-subtle eams:text-sm">Belum ada PIC yang ditugaskan</span>
                            @endforelse
                        </dd>
                    </div>
                </dl>
            </x-ui.card>

            <x-ui.card title="Riwayat checklist" subtitle="Agregat hasil per periode (maks. 24 periode terakhir).">
                @if($history->isEmpty())
                    <p class="eams:m-0 eams:text-sm eams:text-subtle">Belum ada checklist yang diisi untuk inventory ini.</p>
                @else
                    <x-ui.table label="Riwayat periode checklist" compact>
                        <thead>
                            <tr>
                                <th scope="col">Periode</th>
                                <th scope="col">Frekuensi</th>
                                <th scope="col" class="eams:text-center">OK</th>
                                <th scope="col" class="eams:text-center">NOT OK</th>
                                <th scope="col" class="eams:text-center">NA</th>
                                <th scope="col">Terakhir diisi</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($history as $row)
                                <tr wire:key="history-{{ $row->period_key }}">
                                    <td class="eams:font-mono eams:text-[12px]">{{ $row->period_key }}</td>
                                    <td class="eams:text-[12px] eams:capitalize">{{ $row->frequency }}</td>
                                    <td class="eams:text-center eams:tabular-nums eams:text-success">{{ $row->ok_count }}</td>
                                    <td class="eams:text-center eams:tabular-nums {{ $row->not_ok_count > 0 ? 'eams:text-danger eams:font-semibold' : '' }}">{{ $row->not_ok_count }}</td>
                                    <td class="eams:text-center eams:tabular-nums">{{ $row->na_count }}</td>
                                    <td class="eams:text-[12px] eams:tabular-nums">{{ \Illuminate\Support\Carbon::parse($row->last_check)->format('d/m/Y H:i') }} <span class="eams:text-subtle">{{ $row->last_checker }}</span></td>
                                    <td><x-ui.status-indicator status="DONE" size="sm" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </x-ui.table>
                @endif
            </x-ui.card>

            <x-ui.card title="Catatan">
                <p class="eams:mb-0 eams:whitespace-pre-line eams:text-sm {{ $inventory->remark ? 'eams:text-ink' : 'eams:text-subtle' }}">{{ $inventory->remark ?: 'Belum ada catatan untuk inventory ini.' }}</p>
            </x-ui.card>
        </div>

        <aside class="eams:space-y-4">
            <x-ui.card title="QR inventory">
                @if($inventory->qr_image)
                    <div class="eams:flex eams:justify-center eams:bg-surface-sunk eams:rounded-eams eams:p-3">
                        <img src="{{ route('files.show', ['category' => 'qr', 'path' => $inventory->qr_image]) }}"
                             alt="QR {{ $inventory->asset_code }}" width="220" height="220"
                             class="eams:size-[220px] eams:object-contain">
                    </div>
                    <p class="eams:mb-0 eams:mt-2 eams:text-[11px] eams:leading-4 eams:text-muted">QR mempertahankan URL kompatibel dengan sistem legacy.</p>
                @else
                    <div class="eams:flex eams:flex-col eams:items-center eams:gap-2 eams:py-6 eams:text-subtle">
                        <i class="bi bi-qr-code eams:text-2xl" aria-hidden="true"></i>
                        <span class="eams:text-xs">QR belum tersedia.</span>
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card title="Foto inventory">
                @if($inventory->photo)
                    <div class="eams:rounded-eams eams:overflow-hidden eams:border eams:border-border">
                        <img src="{{ route('files.show', ['category' => 'inventory', 'path' => $inventory->photo]) }}"
                             alt="Foto {{ $inventory->asset_code }}" loading="lazy"
                             class="eams:w-full eams:object-cover">
                    </div>
                @else
                    <div class="eams:flex eams:flex-col eams:items-center eams:gap-2 eams:py-6 eams:text-subtle">
                        <i class="bi bi-image eams:text-2xl" aria-hidden="true"></i>
                        <span class="eams:text-xs">Belum ada foto inventory.</span>
                    </div>
                @endif
            </x-ui.card>
        </aside>
    </div>
</div>
@endsection
