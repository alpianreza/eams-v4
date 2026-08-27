{{-- Pemilih tema: mode terang/gelap/ikut sistem + 6 warna aksen. --}}
@php($accents = [
    'indigo' => 'Indigo',
    'emerald' => 'Emerald',
    'violet' => 'Violet',
    'amber' => 'Amber',
    'rose' => 'Rose',
    'ocean' => 'Ocean',
])

<div class="dropdown" x-data>
    <button type="button" class="theme-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside"
            title="Tema tampilan" aria-label="Tema tampilan" aria-expanded="false">
        <i class="bi" :class="$store.theme.icon"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-end theme-menu">
        <p class="theme-menu__label">Mode tampilan</p>
        <div class="theme-menu__modes">
            <button type="button" class="mode-option"
                    :class="{ 'is-active': $store.theme.mode === 'light' }"
                    @click="$store.theme.setMode('light')">
                <i class="bi bi-sun-fill"></i> Terang
            </button>
            <button type="button" class="mode-option"
                    :class="{ 'is-active': $store.theme.mode === 'dark' }"
                    @click="$store.theme.setMode('dark')">
                <i class="bi bi-moon-stars-fill"></i> Gelap
            </button>
            <button type="button" class="mode-option"
                    :class="{ 'is-active': $store.theme.mode === 'system' }"
                    @click="$store.theme.setMode('system')">
                <i class="bi bi-circle-half"></i> Sistem
            </button>
        </div>

        <hr class="dropdown-divider my-3">

        <p class="theme-menu__label">Warna aksen</p>
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
