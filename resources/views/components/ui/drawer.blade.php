@props(['name', 'title' => null, 'side' => 'right', 'open' => false, 'width' => 'md'])
@php
    $position = $side === 'left' ? 'eams:left-0' : 'eams:right-0';
    $closed = $side === 'left' ? 'eams:-translate-x-full' : 'eams:translate-x-full';
    $maxWidth = ['sm' => 'eams:max-w-sm', 'md' => 'eams:max-w-md', 'lg' => 'eams:max-w-xl'][$width] ?? 'eams:max-w-md';
@endphp

<div x-data="{ open: @js((bool) $open) }"
     @open-drawer.window="if ($event.detail === @js($name) || $event.detail?.name === @js($name)) open = true"
     @close-drawer.window="if (!$event.detail || $event.detail === @js($name) || $event.detail?.name === @js($name)) open = false"
     @keydown.escape.window="open = false" data-eams-component="drawer">
    <button x-cloak x-show="open" x-transition.opacity type="button" @click="open = false" class="eams:fixed eams:inset-0 eams:z-[140] eams:border-0 eams:bg-black/50 eams:p-0" aria-label="Tutup drawer"></button>
    <aside :class="open ? 'eams:translate-x-0' : '{{ $closed }}'" class="{{ $position }} {{ $maxWidth }} eams:fixed eams:inset-y-0 eams:z-[150] eams:flex eams:w-[min(92vw,28rem)] eams:flex-col eams:border-border eams:bg-surface eams:text-ink eams:shadow-eams-3 eams:transition-transform eams:duration-200" role="dialog" aria-modal="true" aria-labelledby="{{ $name }}-title">
        <header class="eams:flex eams:min-h-16 eams:items-center eams:justify-between eams:gap-3 eams:border-b eams:border-border eams:px-4">
            <h2 id="{{ $name }}-title" class="eams:m-0 eams:text-base eams:font-bold">{{ $title ?? 'Panel' }}</h2>
            <button type="button" @click="open = false" class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-full eams:border-0 eams:bg-transparent eams:text-muted eams:hover:bg-surface-hover" aria-label="Tutup"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </header>
        <div class="eams:min-h-0 eams:flex-1 eams:overflow-y-auto eams:p-4">{{ $slot }}</div>
        @isset($footer)<footer class="eams:flex eams:flex-wrap eams:justify-end eams:gap-2 eams:border-t eams:border-border eams:bg-surface-sunk eams:p-4">{{ $footer }}</footer>@endisset
    </aside>
</div>
