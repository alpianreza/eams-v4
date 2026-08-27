@props(['name', 'title' => null, 'open' => false, 'size' => 'lg', 'closeable' => true])
@php($width = ['sm' => 'eams:max-w-md', 'md' => 'eams:max-w-xl', 'lg' => 'eams:max-w-2xl', 'xl' => 'eams:max-w-4xl'][$size] ?? 'eams:max-w-2xl')

<div x-data="{ open: @js((bool) $open) }"
     @open-modal.window="if ($event.detail === @js($name) || $event.detail?.name === @js($name)) open = true"
     @close-modal.window="if (!$event.detail || $event.detail === @js($name) || $event.detail?.name === @js($name)) open = false"
     @keydown.escape.window="@js($closeable) && (open = false)" x-cloak x-show="open"
     class="eams:fixed eams:inset-0 eams:z-[150] eams:flex eams:items-center eams:justify-center eams:p-3 eams:sm:p-5"
     role="dialog" aria-modal="true" aria-labelledby="{{ $name }}-title" data-eams-component="modal">
    <button type="button" class="eams:absolute eams:inset-0 eams:border-0 eams:bg-black/55 eams:p-0" @if($closeable) @click="open = false" @endif tabindex="-1" aria-label="Tutup modal"></button>
    <section x-transition.scale.95 class="{{ $width }} eams:relative eams:max-h-[calc(100vh-2rem)] eams:w-full eams:overflow-y-auto eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface eams:text-ink eams:shadow-eams-3">
        <header class="eams:flex eams:items-center eams:justify-between eams:gap-3 eams:border-b eams:border-border eams:px-4 eams:py-3">
            <h2 id="{{ $name }}-title" class="eams:m-0 eams:text-base eams:font-bold">{{ $title ?? 'Dialog' }}</h2>
            @if($closeable)<button type="button" @click="open = false" class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-full eams:border-0 eams:bg-transparent eams:text-muted eams:hover:bg-surface-hover eams:hover:text-ink" aria-label="Tutup"><i class="bi bi-x-lg" aria-hidden="true"></i></button>@endif
        </header>
        <div class="eams:p-4">{{ $slot }}</div>
        @isset($footer)<footer class="eams:flex eams:flex-wrap eams:justify-end eams:gap-2 eams:border-t eams:border-border eams:bg-surface-sunk eams:px-4 eams:py-3">{{ $footer }}</footer>@endisset
    </section>
</div>
