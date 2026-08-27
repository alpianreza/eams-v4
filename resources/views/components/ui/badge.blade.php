@props(['variant' => 'neutral', 'size' => 'md', 'dot' => false])

@php
    $variants = [
        'brand' => 'eams:border-brand/25 eams:bg-brand-soft eams:text-brand',
        'success' => 'eams:border-success/25 eams:bg-success-soft eams:text-success',
        'warning' => 'eams:border-warning/25 eams:bg-warning-soft eams:text-warning',
        'danger' => 'eams:border-danger/25 eams:bg-danger-soft eams:text-danger',
        'info' => 'eams:border-info/25 eams:bg-info-soft eams:text-info',
        'neutral' => 'eams:border-border eams:bg-surface-sunk eams:text-muted',
    ];
    $sizes = [
        'sm' => 'eams:px-1.5 eams:py-0.5 eams:text-[10px]',
        'md' => 'eams:px-2 eams:py-0.5 eams:text-xs',
        'lg' => 'eams:px-2.5 eams:py-1 eams:text-[13px]',
    ];
@endphp

<span {{ $attributes->class('eams:inline-flex eams:items-center eams:gap-1.5 eams:whitespace-nowrap eams:rounded-full eams:border eams:font-semibold '.($variants[$variant] ?? $variants['neutral']).' '.($sizes[$size] ?? $sizes['md'])) }}>
    @if($dot)<span class="eams:size-1.5 eams:rounded-full eams:bg-current" aria-hidden="true"></span>@endif
    {{ $slot }}
</span>
