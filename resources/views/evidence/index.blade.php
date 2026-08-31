@extends('layouts.app')

@section('title', 'Evidence Center')

@section('content')
<div class="eams:mx-auto eams:max-w-5xl eams:space-y-4 eams:sm:space-y-5" data-eams-page="evidence">
    <x-ui.page-header eyebrow="Compliance" eyebrow-icon="exclamation-triangle"
                      title="Evidence Center"
                      lead="Temuan NOT_OK beserta foto/keterangan dan status follow-up-nya." />

    <form method="GET" class="eams:flex eams:flex-wrap eams:items-end eams:gap-2" aria-label="Filter evidence">
        <x-ui.select name="follow_up_status" label="Status follow-up" class="eams:min-w-[12rem]">
            <option value="">Semua follow-up</option>
            @foreach(['open' => 'Open', 'monitoring' => 'Monitoring', 'closed' => 'Closed'] as $s => $label)
                <option value="{{ $s }}" @selected($status === $s)>{{ $label }}</option>
            @endforeach
        </x-ui.select>
        <button type="submit"
                class="eams:inline-flex eams:min-h-10 eams:items-center eams:gap-2 eams:rounded-eams eams:border eams:border-border-strong eams:bg-surface eams:px-3.5 eams:text-[13px] eams:font-semibold eams:text-ink eams:transition-colors eams:hover:bg-surface-hover eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand">
            <i class="bi bi-funnel" aria-hidden="true"></i> Terapkan
        </button>
        @if($status)
            <a href="{{ route('evidence.index') }}"
               class="eams:inline-flex eams:min-h-10 eams:items-center eams:gap-2 eams:rounded-eams eams:border eams:border-border eams:bg-surface eams:px-3 eams:text-[13px] eams:text-muted eams:no-underline eams:transition-colors eams:hover:bg-surface-hover eams:hover:text-ink">
                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset
            </a>
        @endif
    </form>

    @if($findings->isEmpty())
        <x-ui.empty-state icon="shield-check" title="Tidak ada temuan"
                          description="Belum ada hasil NOT_OK untuk filter ini - kabar baik." />
    @else
        <ul class="eams:grid eams:gap-3 eams:m-0 eams:list-none">
            @foreach($findings as $f)
                @php($followUpTone = match ($f->follow_up_status) {
                    'closed' => 'success',
                    'monitoring' => 'info',
                    default => 'warning',
                })
                <li class="eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface eams:shadow-eams-1" data-eams-evidence="{{ $f->id }}">
                    <div class="eams:grid eams:gap-3 eams:p-4 eams:lg:grid-cols-[minmax(0,1fr)_320px]">
                        <div class="eams:min-w-0">
                            <div class="eams:flex eams:flex-wrap eams:items-center eams:gap-2">
                                <a href="{{ route('compliance.inventory.detail', $f->inventory) }}" wire:navigate
                                   class="eams:font-mono eams:text-[13px] eams:font-bold eams:text-brand eams:no-underline eams:hover:underline">
                                    {{ $f->inventory->asset_code ?? '-' }}
                                </a>
                                <span class="eams:text-[13px] eams:font-semibold eams:text-ink">{{ $f->inventory->itemType->name ?? '' }}</span>
                                <x-ui.badge variant="danger" size="sm"><i class="bi bi-x-circle-fill" aria-hidden="true"></i> NOT OK</x-ui.badge>
                                <x-ui.badge :variant="$followUpTone" size="sm">{{ ucfirst($f->follow_up_status ?? 'open') }}</x-ui.badge>
                            </div>
                            <p class="eams:mb-0 eams:mt-1.5 eams:text-[13px] eams:text-muted">
                                {{ $f->question->question ?? '-' }} &middot; {{ \Illuminate\Support\Carbon::parse($f->check_date)->format('d/m/Y') }} &middot; oleh {{ $f->checked_by_name }}
                            </p>
                            @if($f->remark)
                                <p class="eams:mb-0 eams:mt-2 eams:whitespace-pre-line eams:text-sm eams:text-ink">{{ $f->remark }}</p>
                            @endif
                            @if($f->photo)
                                <a href="{{ route('files.show', ['category' => 'checklist', 'path' => $f->photo]) }}" target="_blank" rel="noopener"
                                   class="eams:mt-2 eams:inline-flex eams:items-center eams:gap-1.5 eams:text-xs eams:font-semibold eams:text-brand eams:no-underline eams:hover:underline">
                                    <i class="bi bi-image" aria-hidden="true"></i> Lihat foto
                                </a>
                            @endif
                        </div>

                        <form method="POST" action="{{ route('evidence.followup', $f) }}"
                              class="eams:grid eams:content-start eams:gap-2 eams:rounded-eams eams:border eams:border-border eams:bg-surface-sunk/40 eams:p-3">
                            @csrf
                            @method('PUT')
                            <span class="eams:text-[11px] eams:font-bold eams:uppercase eams:tracking-wide eams:text-muted">Follow-up</span>
                            <x-ui.select name="follow_up_status" label="Status" :error="$errors->first('follow_up_status')">
                                @foreach(['open' => 'Open', 'monitoring' => 'Monitoring', 'closed' => 'Closed'] as $s => $label)
                                    <option value="{{ $s }}" @selected($f->follow_up_status === $s)>{{ $label }}</option>
                                @endforeach
                            </x-ui.select>
                            <x-ui.input name="follow_up_note" label="Catatan" value="{{ $f->follow_up_note }}"
                                        placeholder="Tindak lanjut..." :error="$errors->first('follow_up_note')" />
                            <x-ui.input name="follow_up_date" label="Tanggal" type="date" value="{{ $f->follow_up_date }}"
                                        :error="$errors->first('follow_up_date')" />
                            @can('write')
                                <x-ui.button type="submit" variant="primary" size="sm" icon="check-lg">Simpan</x-ui.button>
                            @endcan
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>

        <x-ui.pagination :paginator="$findings" label="Navigasi halaman evidence" />
    @endif
</div>
@endsection
