@props([
    'search' => null,
    'searchPlaceholder' => 'Cari...',
    'resetAction' => 'resetFilters',
    'hasFilters' => false,
])

<div {{ $attributes->class('eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface eams:p-4 eams:shadow-eams-1') }}
     data-eams-component="filter-bar">
    <form wire:submit.prevent="{{ $resetAction }}" class="eams:grid eams:gap-3 eams:md:grid-cols-2 eams:xl:grid-cols-4" aria-label="Filter data">
        <x-ui.input name="filter-search" type="search" :label="__('Cari')" :placeholder="$searchPlaceholder"
                    leadingIcon="search"
                    @if($search) wire:model.live.debounce.300ms="{{ $search }}" @endif />
        {{ $slot }}
        <div class="eams:flex eams:items-end eams:gap-2">
            <button type="button" wire:click="{{ $resetAction }}"
                    class="eams:inline-flex eams:min-h-10 eams:items-center eams:gap-2 eams:rounded-eams eams:border eams:border-border-strong eams:bg-surface eams:px-3.5 eams:text-[13px] eams:font-semibold eams:text-ink eams:transition-colors eams:hover:bg-surface-hover eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand"
                    @unless($hasFilters) disabled @endunless>
                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Reset
            </button>
        </div>
    </form>
</div>
