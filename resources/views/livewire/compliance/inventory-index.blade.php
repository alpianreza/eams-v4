<div x-data="{ deleteTarget: null }" data-eams-livewire="compliance-inventory-index"
     class="eams:space-y-4 eams:sm:space-y-5">
    @php
        $hasFilters = trim($q) !== '' || trim($areaId) !== '' || trim($status) !== '';
    @endphp

    <header class="eams:flex eams:flex-col eams:gap-4 eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface eams:px-4 eams:py-4 eams:shadow-eams-1 eams:sm:flex-row eams:sm:items-center eams:sm:justify-between eams:sm:px-5">
        <div class="eams:min-w-0">
            <p class="eams:mb-1 eams:text-[11px] eams:font-bold eams:uppercase eams:tracking-[0.12em] eams:text-brand">Compliance</p>
            <h1 class="eams:m-0 eams:text-xl eams:font-extrabold eams:tracking-tight eams:text-ink eams:sm:text-2xl">Compliance Inventory</h1>
            <p class="eams:mb-0 eams:mt-1.5 eams:max-w-2xl eams:text-[13px] eams:leading-5 eams:text-muted">
                Pantau identitas, lokasi, PIC, kondisi, masa berlaku, dan QR seluruh aset compliance.
            </p>
        </div>
        <div class="eams:flex eams:flex-wrap eams:gap-2">
            @if($canManage)
                <x-ui.button :href="route('compliance.inventory.create')" navigate variant="primary" icon="plus-lg">
                    Tambah inventory
                </x-ui.button>
            @endif
        </div>
    </header>

    <section aria-label="Filter inventory" class="eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface eams:p-4 eams:shadow-eams-1">
        <form wire:submit.prevent="resetFilters" class="eams:grid eams:gap-3 eams:md:grid-cols-2 eams:xl:grid-cols-4" data-eams-component="inventory-filter">
            <x-ui.input name="inventory-search" type="search" label="Cari inventory" placeholder="Kode inventory..."
                        leadingIcon="search" wire:model.live.debounce.300ms="q" />

            <x-ui.select name="inventory-area" label="Area" wire:model.live="areaId">
                <option value="">Semua area</option>
                @foreach($areas as $area)
                    <option value="{{ $area->id }}">{{ $area->name }}</option>
                @endforeach
            </x-ui.select>

            <x-ui.select name="inventory-status" label="Status aset" wire:model.live="status">
                <option value="">Semua status</option>
                <option value="good">Baik</option>
                <option value="need_repair">Perlu perbaikan</option>
                <option value="not_active">Tidak aktif</option>
            </x-ui.select>

            <div class="eams:flex eams:items-end eams:gap-2 eams:md:col-start-4">
                <button type="button" wire:click="resetFilters"
                        class="eams:inline-flex eams:min-h-10 eams:items-center eams:gap-2 eams:rounded-eams eams:border eams:border-border-strong eams:bg-surface eams:px-3.5 eams:text-[13px] eams:font-semibold eams:text-ink eams:transition-colors eams:hover:bg-surface-hover eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand"
                        @unless($hasFilters) disabled @endunless>
                    <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset
                </button>
            </div>
        </form>
    </section>

    <div class="eams:flex eams:flex-wrap eams:items-center eams:justify-between eams:gap-2" aria-live="polite">
        <p class="eams:m-0 eams:text-[13px] eams:text-muted">
            <strong class="eams:font-semibold eams:text-ink">{{ number_format($inventories->total()) }}</strong> inventory ditemukan
            @if($hasFilters)<span>&mdash; berdasarkan filter aktif</span>@endif
        </p>
        <span class="eams:text-xs eams:text-subtle">Urut dari data terbaru</span>
    </div>

    @if($inventories->isEmpty() && $hasFilters)
        <x-ui.empty-state icon="box-seam" title="Inventory tidak ditemukan"
                          description="Coba ubah atau reset filter pencarian.">
            <button type="button" wire:click="resetFilters" variant="secondary"
                    class="eams:inline-flex eams:min-h-9 eams:items-center eams:gap-2 eams:rounded-eams eams:border eams:border-border-strong eams:bg-surface eams:px-3.5 eams:py-2 eams:text-[13px] eams:font-semibold eams:text-ink eams:shadow-eams-1 eams:transition-colors eams:hover:bg-surface-hover eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand">
                Reset filter
            </button>
        </x-ui.empty-state>
    @elseif($inventories->isEmpty())
        <x-ui.empty-state icon="box-seam" title="Belum ada compliance inventory"
                          description="Inventory yang ditambahkan akan tampil di sini lengkap dengan PIC dan QR."
        >
            @if($canManage)
                <x-ui.button :href="route('compliance.inventory.create')" navigate variant="primary" icon="plus-lg">
                    Tambah inventory
                </x-ui.button>
            @endif
        </x-ui.empty-state>
    @else
        <x-ui.table label="Daftar compliance inventory">
            <thead>
                <tr>
                    <th scope="col">Inventory</th>
                    <th scope="col">Item &amp; kategori</th>
                    <th scope="col">Lokasi</th>
                    <th scope="col">Status</th>
                    <th scope="col">PIC</th>
                    <th scope="col">Masa berlaku</th>
                    <th scope="col" class="eams:text-center">QR</th>
                    @if($canManage)<th scope="col" class="eams:text-right">Aksi</th>@endif
                </tr>
            </thead>
            <tbody>
            @foreach($inventories as $inv)
                <tr wire:key="inventory-{{ $inv->id }}" data-eams-inventory-row="{{ $inv->asset_code }}">
                    <td class="eams:min-w-[12rem]">
                        <a href="{{ route('compliance.inventory.detail', $inv) }}" wire:navigate
                           class="eams:flex eams:items-center eams:gap-2.5 eams:no-underline eams:text-ink eams:transition-colors eams:hover:text-brand eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand">
                            <span class="eams:inline-flex eams:size-8 eams:shrink-0 eams:items-center eams:justify-center eams:rounded-eams eams:bg-brand-soft eams:text-brand">
                                <i class="bi bi-box-seam" aria-hidden="true"></i>
                            </span>
                            <span class="eams:grid eams:min-w-0">
                                <span class="eams:truncate eams:font-mono eams:text-[13px] eams:font-bold">{{ $inv->asset_code }}</span>
                                <span class="eams:truncate eams:text-[11px] eams:text-muted">{{ $inv->type_description ?: 'Tanpa spesifikasi' }}</span>
                            </span>
                        </a>
                    </td>
                    <td class="eams:min-w-[9rem]">
                        <span class="eams:block eams:text-[13px] eams:font-semibold eams:text-ink">{{ $inv->itemType->name ?? '-' }}</span>
                        <span class="eams:block eams:text-[11px] eams:text-muted">{{ $inv->category->name ?? 'Tanpa kategori' }}</span>
                    </td>
                    <td class="eams:min-w-[9rem]">
                        <span class="eams:block eams:text-[13px] eams:text-ink">{{ $inv->area->name ?? '-' }}</span>
                        @if($inv->specific_area)<span class="eams:block eams:text-[11px] eams:text-muted">{{ $inv->specific_area }}</span>@endif
                    </td>
                    <td>
                        <x-ui.status-indicator :status="strtoupper($inv->status)" size="sm" />
                    </td>
                    <td class="eams:min-w-[8rem]">
                        @if($inv->pics->isNotEmpty())
                            <span class="eams:flex eams:items-center" title="{{ $inv->pics->pluck('name')->join(', ') }}">
                                @foreach($inv->pics->take(2) as $pic)
                                    <span class="eams:inline-flex eams:size-6 eams:items-center eams:justify-center eams:rounded-full eams:border eams:border-border eams:bg-surface-sunk eams:text-[10px] eams:font-bold eams:text-muted eams:[&:not(:first-child)]:-ml-2">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($pic->name, 0, 2)) }}</span>
                                @endforeach
                                <span class="eams:ml-2 eams:truncate eams:text-[11px] eams:text-muted eams:max-w-[14ch]">{{ $inv->pics->pluck('name')->join(', ') }}</span>
                            </span>
                        @else
                            <span class="eams:text-subtle">-</span>
                        @endif
                    </td>
                    <td class="eams:min-w-[7rem]">
                        @if($inv->expired_date)
                            <span class="eams:block eams:tabular-nums eams:text-[13px] {{ $inv->isExpired() ? 'eams:font-semibold eams:text-danger' : 'eams:text-ink' }}">{{ \Illuminate\Support\Carbon\Carbon::parse($inv->expired_date)->format('d/m/Y') }}</span>
                            <span class="eams:block eams:text-[11px] eams:text-muted">{{ $inv->isExpired() ? 'Sudah kedaluwarsa' : 'Tercatat' }}</span>
                        @else
                            <span class="eams:text-subtle">-</span>
                        @endif
                    </td>
                    <td class="eams:text-center">
                        @if($inv->qr_image)
                            <span class="eams:inline-flex eams:size-7 eams:items-center eams:justify-center eams:rounded-eams-sm eams:bg-success-soft eams:text-success" title="QR tersedia">
                                <i class="bi bi-qr-code" aria-hidden="true"></i>
                            </span>
                        @else
                            <span class="eams:inline-flex eams:size-7 eams:items-center eams:justify-center eams:rounded-eams-sm eams:text-subtle" title="QR belum tersedia">
                                <i class="bi bi-dash" aria-hidden="true"></i>
                            </span>
                        @endif
                    </td>
                    @if($canManage)
                        <td>
                            <div class="eams:flex eams:items-center eams:justify-end eams:gap-1.5">
                                <a href="{{ route('compliance.inventory.detail', $inv) }}" wire:navigate
                                   class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface eams:text-muted eams:hover:text-brand eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand"
                                   title="Lihat detail {{ $inv->asset_code }}" aria-label="Lihat detail {{ $inv->asset_code }}">
                                    <i class="bi bi-eye" aria-hidden="true"></i>
                                </a>
                                <a href="{{ route('compliance.inventory.edit', $inv) }}" wire:navigate
                                   class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-brand/40 eams:bg-brand-soft eams:text-brand eams:hover:bg-brand eams:hover:text-brand-contrast eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand"
                                   title="Edit {{ $inv->asset_code }}" aria-label="Edit {{ $inv->asset_code }}">
                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                </a>
                                <button type="button"
                                        @click="deleteTarget = { id: {{ $inv->id }}, code: @js($inv->asset_code) }; $dispatch('eams-confirm', { name: 'inventory-delete', message: 'Hapus inventory ' + deleteTarget.code + '?', id: deleteTarget.id })"
                                        class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-danger/40 eams:bg-danger-soft eams:text-danger eams:hover:bg-danger eams:hover:text-white eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-danger"
                                        title="Hapus {{ $inv->asset_code }}" aria-label="Hapus {{ $inv->asset_code }}">
                                    <i class="bi bi-trash3" aria-hidden="true"></i>
                                </button>
                            </div>
                        </td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </x-ui.table>

        @if($canManage)
            <form x-ref="deleteForm-{{ '' }}" method="POST" action="#" class="eams:hidden" aria-hidden="true"></form>
            <x-ui.confirm-dialog name="inventory-delete" title="Hapus inventory" confirm-label="Ya, hapus">
            </x-ui.confirm-dialog>
            <form id="inventory-delete-form" method="POST" action="{{ route('compliance.inventory.destroy', ['inventory' => 0]) }}" class="eams:hidden"></form>
        @endif

        @include('livewire.compliance.inventory-pagination', ['paginator' => $inventories])
    @endif
</div>
