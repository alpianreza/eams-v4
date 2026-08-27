{{-- Pemilih tema: mode terang/gelap/ikut sistem + 6 warna aksen. --}}
@php($accents = [
    'indigo' => 'Indigo',
    'emerald' => 'Emerald',
    'violet' => 'Violet',
    'amber' => 'Amber',
    'rose' => 'Rose',
    'ocean' => 'Ocean',
])

<div class="eams:relative" x-data="eamsDropdown" @click.outside="close" @keydown.escape.stop="close">
    <button type="button" @click="toggle" :aria-expanded="open"
            class="eams:relative eams:inline-flex eams:size-9 eams:items-center eams:justify-center eams:rounded-full eams:border eams:border-border eams:bg-surface-sunk eams:text-ink eams:transition-colors eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand"
            title="Tema tampilan" aria-label="Tema tampilan" aria-haspopup="true">
        <i class="bi" :class="$store.theme.icon" aria-hidden="true"></i>
    </button>

    <div x-cloak x-show="open" x-transition.origin.top.right
         class="theme-menu eams:absolute eams:right-0 eams:top-[calc(100%+0.5rem)] eams:z-[130] eams:w-[min(17rem,calc(100vw-1.5rem))] eams:rounded-eams eams:border eams:border-border eams:bg-surface eams:p-3 eams:text-ink eams:shadow-eams-3"
         role="dialog" aria-label="Pengaturan tema">
        <p class="theme-menu__label eams:m-0 eams:mb-2">Mode tampilan</p>
        <div class="theme-menu__modes">
            <button type="button" class="mode-option"
                    :class="{ 'is-active': $store.theme.mode === 'light' }"
                    @click="$store.theme.setMode('light')">
                <i class="bi bi-sun-fill" aria-hidden="true"></i> Terang
            </button>
            <button type="button" class="mode-option"
                    :class="{ 'is-active': $store.theme.mode === 'dark' }"
                    @click="$store.theme.setMode('dark')">
                <i class="bi bi-moon-stars-fill" aria-hidden="true"></i> Gelap
            </button>
            <button type="button" class="mode-option"
                    :class="{ 'is-active': $store.theme.mode === 'system' }"
                    @click="$store.theme.setMode('system')">
                <i class="bi bi-circle-half" aria-hidden="true"></i> Sistem
            </button>
        </div>

        <hr class="eams:my-3 eams:border-0 eams:border-t eams:border-border">

        <p class="theme-menu__label eams:m-0 eams:mb-2">Warna aksen</p>
        <div class="theme-menu__accents">
            @foreach($accents as $value => $label)
                <button type="button" class="accent-swatch" data-accent="{{ $value }}"
                        title="{{ $label }}" aria-label="Warna aksen {{ $label }}"
                        :class="{ 'is-active': $store.theme.accent === '{{ $value }}' }"
                        @click="$store.theme.setAccent('{{ $value }}')"></button>
            @endforeach
        </div>
    </div>
</div>
