@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'navigate' => false,
    'loading' => false,
    'disabled' => false,
    'icon' => null,
])

@php
    $variants = [
        'primary' => 'eams:border-brand eams:bg-brand eams:text-brand-contrast eams:hover:bg-brand-hover eams:hover:text-brand-contrast',
        'secondary' => 'eams:border-border-strong eams:bg-surface eams:text-ink eams:hover:bg-surface-hover eams:hover:text-ink',
        'danger' => 'eams:border-danger eams:bg-danger eams:text-white eams:hover:opacity-90 eams:hover:text-white',
        'ghost' => 'eams:border-transparent eams:bg-transparent eams:text-muted eams:hover:bg-surface-hover eams:hover:text-ink',
    ];
    $sizes = [
        'sm' => 'eams:min-h-8 eams:px-2.5 eams:py-1.5 eams:text-xs',
        'md' => 'eams:min-h-9 eams:px-3.5 eams:py-2 eams:text-[13px]',
        'lg' => 'eams:min-h-11 eams:px-4 eams:py-2.5 eams:text-sm',
    ];
    $classes = 'eams:inline-flex eams:items-center eams:justify-center eams:gap-2 eams:rounded-eams eams:border eams:font-semibold eams:no-underline eams:shadow-eams-1 eams:transition-colors eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand eams:focus-visible:ring-offset-2 eams:disabled:cursor-not-allowed eams:disabled:opacity-60 '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

@if(filled($href))
    <a href="{{ $href }}" @if($navigate) wire:navigate @endif
       {{ $attributes->class($classes)->merge(['aria-disabled' => $disabled ? 'true' : null]) }}>
        @if($loading)<span class="eams:size-3.5 eams:animate-spin eams:rounded-full eams:border-2 eams:border-current eams:border-r-transparent" aria-hidden="true"></span>@endif
        @if($icon)<i class="bi bi-{{ $icon }}" aria-hidden="true"></i>@endif
        <span>{{ $slot }}</span>
    </a>
@else
    <button type="{{ $type }}" @disabled($disabled || $loading) {{ $attributes->class($classes) }}>
        @if($loading)<span class="eams:size-3.5 eams:animate-spin eams:rounded-full eams:border-2 eams:border-current eams:border-r-transparent" aria-hidden="true"></span>@endif
        @if($icon)<i class="bi bi-{{ $icon }}" aria-hidden="true"></i>@endif
        <span>{{ $slot }}</span>
    </button>
@endif
