<div class="eams:mx-auto eams:max-w-[100rem] eams:space-y-4 eams:sm:space-y-5" data-eams-page="checklist-grid">
    <x-ui.page-header eyebrow="Compliance" eyebrow-icon="grid-3x3-gap-fill"
                      :title="'Grid Checklist — ' . $itemType->name . ' ' . $itemType->code"
                      :lead="'Fast/mass entry · Periode: ' . $periodKey . ' · Grid boleh tanpa foto atau keterangan untuk NOT OK.'">
        <x-slot:actions>
            <x-ui.badge variant="neutral" size="sm">{{ $itemType->checklist_frequency }}</x-ui.badge>
        </x-slot:actions>
    </x-ui.page-header>

    @if(! $editable)
        <x-ui.alert variant="warning" title="Periode terkunci">
            Periode ini tidak dapat diisi berdasarkan aturan periode checklist.
        </x-ui.alert>
    @endif

    <x-ui.card title="Matriks checklist" :subtitle="$toilet ? 'Setiap Toilet memiliki tiga slot wajib: Pagi, Siang, dan Sore.' : 'Pilih sel untuk mengisi status. Mark all hanya mengisi sel kosong.'" padding="false">
        <x-slot:actions>
            @if($canWrite && $editable && ! $toilet)
                <x-ui.button type="button" size="sm" variant="success" icon="check-all" wire:click="markAll">Mark all OK</x-ui.button>
                <x-ui.button type="button" size="sm" variant="danger" icon="trash3"
                             x-on:click="window.dispatchEvent(new CustomEvent('eams-confirm', { detail: { name: 'grid-clear', message: 'Hapus semua sel grid untuk periode ini?' } }))">
                    Clear periode
                </x-ui.button>
            @endif
        </x-slot:actions>

        <div class="eams:p-0">
            <x-ui.data-grid :headers="array_merge([['label' => 'Inventory', 'scope' => 'col']], $questions->map(fn ($question) => ['label' => $question->question, 'scope' => 'col'])->all())"
                            :empty-text="'Tidak ada inventory aktif untuk item type ini.'"
                            :label="'Grid Checklist ' . $itemType->name">
                @foreach($inventories as $inventory)
                    @php($rowSlots = $toilet ? $toiletSlots : [null])
                    @foreach($rowSlots as $slot)
                        <tr wire:key="grid-row-{{ $inventory->id }}-{{ $slot ?? 'none' }}">
                            <th scope="row" class="eams:min-w-52 eams:font-semibold eams:text-ink">
                                <code class="eams:font-mono eams:text-xs eams:text-brand">{{ $inventory->asset_code }}</code>
                                @if($toilet)
                                    <span class="eams:ml-1 eams:inline-flex eams:rounded-full eams:bg-surface-sunk eams:px-2 eams:py-0.5 eams:text-[10px] eams:font-bold eams:text-muted">
                                        {{ $slot }} · {{ ['PG' => 'Pagi', 'SI' => 'Siang', 'SO' => 'Sore'][$slot] }}
                                    </span>
                                @endif
                                @if($inventory->specific_area)
                                    <span class="eams:mt-0.5 eams:block eams:text-[11px] eams:font-normal eams:text-muted">{{ $inventory->specific_area }}</span>
                                @endif
                            </th>
                            @foreach($questions as $question)
                                @php($log = $existing[$inventory->id][$slot ?? ''] [$question->id] ?? null)
                                @php($status = $log?->status)
                                @php($cellClass = match ($status) {
                                    'ok' => 'eams:bg-success-soft eams:text-success',
                                    'not_ok' => 'eams:bg-danger-soft eams:text-danger',
                                    'na' => 'eams:bg-surface-sunk eams:text-muted',
                                    default => 'eams:bg-surface eams:text-subtle',
                                })
                                <td class="eams:min-w-28 {{ $cellClass }}">
                                    @if($canWrite && $editable)
                                        <button type="button" data-grid-cell tabindex="0"
                                                wire:key="grid-cell-{{ $inventory->id }}-{{ $question->id }}-{{ $slot ?? 'none' }}"
                                                wire:click="openCell({{ $inventory->id }}, {{ $question->id }}, @js($slot))"
                                                x-on:eams-data-grid-activate="$wire.openCell({{ $inventory->id }}, {{ $question->id }}, @js($slot))"
                                                class="eams:flex eams:min-h-8 eams:w-full eams:items-center eams:justify-center eams:gap-1 eams:rounded-eams-sm eams:border eams:border-dashed eams:border-current/25 eams:bg-transparent eams:px-2 eams:text-[11px] eams:font-bold eams:uppercase eams:tracking-wide eams:transition-colors eams:hover:bg-surface-hover eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand">
                                            @if($status === 'ok')
                                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i> OK
                                            @elseif($status === 'not_ok')
                                                <i class="bi bi-x-circle-fill" aria-hidden="true"></i> NOT OK
                                            @elseif($status === 'na')
                                                <i class="bi bi-dash-circle-fill" aria-hidden="true"></i> NA
                                            @else
                                                <i class="bi bi-plus-lg" aria-hidden="true"></i> Isi
                                            @endif
                                        </button>
                                    @else
                                        <span data-grid-cell data-locked tabindex="-1" title="{{ ! $canWrite ? 'Akses tulis diperlukan' : 'Periode terkunci' }}" class="eams:flex eams:min-h-8 eams:items-center eams:justify-center eams:gap-1 eams:opacity-65">
                                            @if($status === 'ok')<i class="bi bi-check-circle-fill" aria-hidden="true"></i> OK
                                            @elseif($status === 'not_ok')<i class="bi bi-x-circle-fill" aria-hidden="true"></i> NOT OK
                                            @elseif($status === 'na')<i class="bi bi-dash-circle-fill" aria-hidden="true"></i> NA
                                            @else — @endif
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </x-ui.data-grid>
        </div>
    </x-ui.card>

    @if($activeInventoryId !== null && $activeQuestionId !== null)
        <div class="eams:fixed eams:inset-0 eams:z-[150] eams:flex eams:items-end eams:justify-center eams:bg-black/40 eams:p-3 eams:sm:items-center"
             x-on:keydown.escape.window="$wire.closeCell()" role="dialog" aria-modal="true" aria-label="Isi sel checklist">
            <section class="eams:w-full eams:max-w-sm eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface eams:p-4 eams:shadow-eams-3">
                <div class="eams:flex eams:items-start eams:justify-between eams:gap-3">
                    <div>
                        <p class="eams:m-0 eams:text-sm eams:font-bold eams:text-ink">Isi status checklist</p>
                        <p class="eams:mb-0 eams:mt-1 eams:text-xs eams:text-muted">{{ $periodKey }}@if($activeSlot) · {{ $activeSlot }}@endif</p>
                    </div>
                    <button type="button" wire:click="closeCell" class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface eams:text-muted eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand" aria-label="Tutup">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <fieldset class="eams:mt-4 eams:grid eams:grid-cols-{{ $allowNa ? '3' : '2' }} eams:gap-2">
                    @foreach(['ok' => ['OK', 'success', 'check-circle-fill'], 'not_ok' => ['NOT OK', 'danger', 'x-circle-fill']] as $value => [$label, $tone, $icon])
                        <label class="eams:cursor-pointer">
                            <input class="eams:sr-only" type="radio" wire:model="selectedStatus" value="{{ $value }}">
                            <span @class([
                                'eams:flex eams:min-h-11 eams:items-center eams:justify-center eams:gap-1.5 eams:rounded-eams-sm eams:border eams:text-xs eams:font-bold eams:transition-colors',
                                'eams:border-'.$tone.' eams:bg-'.$tone.'-soft eams:text-'.$tone => $selectedStatus === $value,
                                'eams:border-border eams:bg-surface eams:text-muted' => $selectedStatus !== $value,
                            ])><i class="bi bi-{{ $icon }}" aria-hidden="true"></i> {{ $label }}</span>
                        </label>
                    @endforeach
                    @if($allowNa)
                        <label class="eams:cursor-pointer">
                            <input class="eams:sr-only" type="radio" wire:model="selectedStatus" value="na">
                            <span @class([
                                'eams:flex eams:min-h-11 eams:items-center eams:justify-center eams:gap-1.5 eams:rounded-eams-sm eams:border eams:text-xs eams:font-bold eams:transition-colors',
                                'eams:border-border-strong eams:bg-surface-sunk eams:text-muted' => $selectedStatus === 'na',
                                'eams:border-border eams:bg-surface eams:text-muted' => $selectedStatus !== 'na',
                            ])><i class="bi bi-dash-circle-fill" aria-hidden="true"></i> NA</span>
                        </label>
                    @endif
                </fieldset>

                @error('status')<p class="eams:mb-0 eams:mt-2 eams:text-xs eams:text-danger">{{ $message }}</p>@enderror
                @error('time_slot')<p class="eams:mb-0 eams:mt-2 eams:text-xs eams:text-danger">{{ $message }}</p>@enderror
                <p class="eams:mb-0 eams:mt-3 eams:text-[11px] eams:leading-4 eams:text-muted">Grid boleh menyimpan NOT OK tanpa foto atau keterangan.</p>
                <div class="eams:mt-4 eams:flex eams:justify-end eams:gap-2">
                    <x-ui.button type="button" variant="secondary" wire:click="closeCell">Batal</x-ui.button>
                    <x-ui.button type="button" variant="primary" icon="check-lg" wire:click="saveActiveCell">Simpan</x-ui.button>
                </div>
            </section>
        </div>
    @endif

    <x-ui.confirm-dialog name="grid-clear" title="Clear grid periode" confirm-label="Hapus sel" />
    <div x-on:eams-confirmed.window="if ($event.detail?.name === 'grid-clear') { $wire.clear() }"></div>
</div>
