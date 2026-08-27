@props(['variant' => 'info', 'title' => null, 'dismissible' => false])

@php
    $variants = [
        'success' => ['classes' => 'eams:border-success/30 eams:bg-success-soft eams:text-success', 'icon' => 'check-circle-fill'],
        'warning' => ['classes' => 'eams:border-warning/30 eams:bg-warning-soft eams:text-warning', 'icon' => 'exclamation-triangle-fill'],
        'danger' => ['classes' => 'eams:border-danger/30 eams:bg-danger-soft eams:text-danger', 'icon' => 'x-circle-fill'],
        'info' => ['classes' => 'eams:border-info/30 eams:bg-info-soft eams:text-info', 'icon' => 'info-circle-fill'],
        'neutral' => ['classes' => 'eams:border-border eams:bg-surface-sunk eams:text-muted', 'icon' => 'info-circle'],
    ];
    $preset = $variants[$variant] ?? $variants['info'];
@endphp

<div x-data="{ visible: true }" x-show="visible" role="{{ $variant === 'danger' ? 'alert' : 'status' }}"
     {{ $attributes->class('eams:flex eams:items-start eams:gap-3 eams:rounded-eams eams:border eams:p-3 '.$preset['classes']) }}>
    <i class="bi bi-{{ $preset['icon'] }} eams:mt-0.5 eams:shrink-0" aria-hidden="true"></i>
    <div class="eams:min-w-0 eams:flex-1 eams:text-[13px] eams:leading-5">
        @if($title)<div class="eams:mb-0.5 eams:font-bold">{{ $title }}</div>@endif
        <div>{{ $slot }}</div>
    </div>
    @if($dismissible)
        <button type="button" @click="visible = false" class="eams:inline-flex eams:size-6 eams:shrink-0 eams:items-center eams:justify-center eams:rounded eams:border-0 eams:bg-transparent eams:text-current eams:opacity-70 eams:hover:opacity-100" aria-label="Tutup">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    @endif
</div>
