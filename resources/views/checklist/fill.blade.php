@extends('layouts.app')

@section('title', 'Checklist — ' . $inventory->asset_code)

@section('content')
@php
    $statusPresentation = \App\Support\Ui\StatusPresentation::for(strtoupper((string) $inventory->status));
    $total = $questions->count();
@endphp

<div class="eams:mx-auto eams:max-w-5xl eams:space-y-4 eams:sm:space-y-5" data-eams-page="checklist-fill">
    <x-ui.page-header eyebrow="Compliance" eyebrow-icon="clipboard-check"
                      :title="'Checklist — ' . $inventory->asset_code"
                      :lead="$inventory->itemType->name . ' · Periode: ' . $periodKey . ' (' . $inventory->itemType->checklist_frequency . ')'"
                      :back-url="route('compliance.inventory.detail', $inventory)" />

    @if(session('status'))
        <x-ui.alert variant="success" title="Tersimpan" dismissible>{{ session('status') }}</x-ui.alert>
    @endif
    @if($errors->any())
        <x-ui.alert variant="danger" title="Tidak dapat disimpan">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if(! $editable)
        <x-ui.alert variant="warning" title="Periode terkunci">
            Periode ini tidak dapat diisi (hari libur atau periode mendatang).
        </x-ui.alert>
    @endif

    {{-- PeriodStrip: period navigation and status overview --}}
    @if(isset($periodStrip))
        <x-ui.period-strip
            :periods="$periodStrip['periods']"
            :current-key="$periodStrip['currentKey']"
            :month="$periodStrip['month']"
            :year="$periodStrip['year']"
            :frequency="$periodStrip['frequency']"
            :prev-url="null"
            :next-url="null"
            :disabled-next="false"
        />
    @endif

    <form method="POST" action="{{ route('compliance.checklist.store', $inventory) }}" enctype="multipart/form-data">
        @csrf
        <x-ui.card title="Pertanyaan checklist" :subtitle="'Isi semua pertanyaan untuk periode ' . $periodKey . '. NOT OK wajib keterangan atau foto.'">
            @if($questions->isNotEmpty())
                <div class="eams:mb-4">
                    <x-ui.progress :value="0" :max="$total" label="Pertanyaan terisi" size="sm" />
                </div>
            @endif

            @if($questions->isEmpty())
                <x-ui.empty-state icon="clipboard-check" title="Belum ada pertanyaan"
                                  description="Item type ini belum memiliki pertanyaan checklist aktif." :boxed="false" />
            @else
                <ul class="eams:grid eams:gap-3 eams:p-0 eams:m-0 eams:list-none">
                    @foreach($questions as $q)
                        <li class="eams:rounded-eams eams:border eams:border-border eams:bg-surface-sunk/40 eams:p-3 eams:sm:p-4" data-eams-question="{{ $q->id }}">
                            <div class="eams:flex eams:flex-col eams:gap-3 eams:sm:flex-row eams:sm:items-start eams:sm:justify-between">
                                <div class="eams:min-w-0 eams:flex-1">
                                    <p class="eams:m-0 eams:text-sm eams:font-semibold eams:text-ink">
                                        {{ $q->question }}
                                        @if($q->require_photo)
                                            <x-ui.badge variant="danger" size="sm" class="eams:ml-1" title="Wajib menyertakan foto">wajib foto</x-ui.badge>
                                        @endif
                                    </p>
                                    <fieldset class="eams:mt-2 eams:flex eams:flex-wrap eams:gap-2" role="radiogroup"
                                              aria-label="Hasil {{ $q->question }}">
                                        <label class="eams:cursor-pointer">
                                            <input type="radio" class="eams:sr-only" name="status_{{ $q->id }}" value="ok"
                                                   @disabled(!$editable)>
                                            <span class="eams:inline-flex eams:min-h-8 eams:items-center eams:gap-1.5 eams:rounded-full eams:border eams:px-3 eams:text-xs eams:font-semibold eams:transition-colors"
                                                  @class([
                                                      'eams:border-success eams:bg-success-soft eams:text-success' => true,
                                                  ])>
                                                <i class="bi bi-check-circle-fill" aria-hidden="true"></i> OK
                                            </span>
                                        </label>
                                        <label class="eams:cursor-pointer">
                                            <input type="radio" class="eams:sr-only" name="status_{{ $q->id }}" value="not_ok"
                                                   @disabled(!$editable)>
                                            <span class="eams:inline-flex eams:min-h-8 eams:items-center eams:gap-1.5 eams:rounded-full eams:border eams:px-3 eams:text-xs eams:font-semibold eams:transition-colors"
                                                  @class([
                                                      'eams:border-danger eams:bg-danger-soft eams:text-danger' => true,
                                                  ])>
                                                <i class="bi bi-x-circle-fill" aria-hidden="true"></i> NOT OK
                                            </span>
                                        </label>
                                        @if($allowNa)
                                            <label class="eams:cursor-pointer">
                                                <input type="radio" class="eams:sr-only" name="status_{{ $q->id }}" value="na"
                                                       @disabled(!$editable)>
                                                <span class="eams:inline-flex eams:min-h-8 eams:items-center eams:gap-1.5 eams:rounded-full eams:border eams:px-3 eams:text-xs eams:font-semibold eams:transition-colors"
                                                      @class([
                                                          'eams:border-border-strong eams:bg-surface-sunk eams:text-muted' => true,
                                                      ])>
                                                    <i class="bi bi-dash-circle-fill" aria-hidden="true"></i> NA
                                                </span>
                                            </label>
                                        @endif
                                    </fieldset>
                                </div>

                                <div class="eams:grid eams:w-full eams:gap-2 eams:sm:w-64">
                                    <input type="text" name="remark_{{ $q->id }}" placeholder="Keterangan (wajib bila tanpa foto)"
                                           class="eams:block eams:min-h-10 eams:w-full eams:rounded-eams eams:border eams:border-border eams:bg-surface eams:px-3 eams:py-2 eams:text-sm eams:text-ink eams:outline-none eams:transition eams:placeholder:text-subtle eams:focus:border-brand eams:focus:ring-2 eams:focus:ring-brand-soft"
                                           @disabled(!$editable)>
                                    <input type="file" name="photo_{{ $q->id }}" accept="image/jpeg,image/png,image/webp"
                                           class="eams:block eams:w-full eams:text-sm eams:text-muted eams:file:mr-3 eams:file:rounded-eams eams:file:border-0 eams:file:bg-surface-sunk eams:file:px-3 eams:file:py-2 eams:file:text-xs eams:file:font-semibold eams:file:text-ink"
                                           @disabled(!$editable)>
                                    <p class="eams:m-0 eams:text-[11px] eams:leading-4 eams:text-muted">Salah satu wajib diisi (Q-013). Maks. 5 MB.</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-ui.card>

        @if($editable && $questions->isNotEmpty())
            <div class="eams:flex eams:flex-wrap eams:items-center eams:justify-end eams:gap-2">
                <x-ui.button :href="route('compliance.inventory.detail', $inventory)" navigate variant="secondary">Batal</x-ui.button>
                <x-ui.button type="submit" variant="primary" icon="check-lg">Simpan Checklist</x-ui.button>
            </div>
        @endif
    </form>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        window.checklistForm = (total, editable) => ({
            total: total,
            filled: {},
            get filledCount() {
                return Object.values(this.filled).filter(Boolean).length;
            },
            validateBeforeSubmit() {
                if (! editable) return false;
                const missing = this.total - this.filledCount;
                if (missing > 0) {
                    if (! window.confirm('Masih ada ' + missing + ' pertanyaan belum diisi. Simpan sebagian?')) {
                        return false;
                    }
                }
                return true;
            },
        });
    })();
</script>
@endpush
