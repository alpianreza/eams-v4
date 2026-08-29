@props([
    'name' => 'items',
    'label' => null,
    'template' => [],
    'addLabel' => 'Tambah baris',
    'max' => null,
    'removable' => true,
])
<div x-data="eamsRepeater(@js($template), @js($max))" {{ $attributes->class('eams:grid eams:gap-3') }} data-eams-component="repeater">
    @if($label)
        <span class="eams:text-[13px] eams:font-semibold eams:text-ink">{{ $label }}</span>
    @endif

    <div class="eams:grid eams:gap-3">
        <template x-for="(row, index) in rows" :key="index">
            <div class="eams:flex eams:items-start eams:gap-2">
                <div class="eams:grid eams:flex-1 eams:gap-2 eams:sm:grid-cols-2">
                    {{ $slot }}
                </div>
                @if($removable)
                    <button type="button" x-on:click="remove(index)" x-show="rows.length > 1"
                            class="eams:mt-1 eams:inline-flex eams:size-8 eams:shrink-0 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-danger/40 eams:bg-danger-soft eams:text-danger eams:transition-colors eams:hover:bg-danger eams:hover:text-white eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-danger"
                            :aria-label="'Hapus baris ' + (index + 1)" aria-label="Hapus baris">
                        <i class="bi bi-trash3" aria-hidden="true"></i>
                    </button>
                @endif
            </div>
        </template>
    </div>

    <button type="button" x-on:click="add()" @if($max) x-show="rows.length < max" @endif
            class="eams:inline-flex eams:min-h-9 eams:w-fit eams:items-center eams:gap-2 eams:rounded-eams eams:border eams:border-dashed eams:border-border-strong eams:bg-surface eams:px-3.5 eams:text-[13px] eams:font-semibold eams:text-muted eams:transition-colors eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand">
        <i class="bi bi-plus-lg" aria-hidden="true"></i> {{ $addLabel }}
    </button>
</div>
