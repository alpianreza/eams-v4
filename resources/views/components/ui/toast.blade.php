@props(['variant' => 'info', 'title' => null, 'dismissible' => true])

@php
    $presets = [
        'success' => ['icon' => 'check-circle-fill', 'color' => 'eams:text-success', 'border' => 'eams:border-l-success'],
        'warning' => ['icon' => 'exclamation-triangle-fill', 'color' => 'eams:text-warning', 'border' => 'eams:border-l-warning'],
        'danger' => ['icon' => 'x-circle-fill', 'color' => 'eams:text-danger', 'border' => 'eams:border-l-danger'],
        'info' => ['icon' => 'info-circle-fill', 'color' => 'eams:text-info', 'border' => 'eams:border-l-info'],
    ];
    $preset = $presets[$variant] ?? $presets['info'];
@endphp

<div x-data="{ visible: true }" x-show="visible" role="status"
     {{ $attributes->class('eams:relative eams:flex eams:w-full eams:max-w-sm eams:items-start eams:gap-3 eams:overflow-hidden eams:rounded-eams eams:border eams:border-border eams:border-l-[3px] eams:bg-surface eams:p-3 eams:text-ink eams:shadow-eams-3 '.$preset['border']) }}
     data-eams-component="toast">
    <i class="bi bi-{{ $preset['icon'] }} eams:mt-0.5 eams:shrink-0 {{ $preset['color'] }}" aria-hidden="true"></i>
    <div class="eams:min-w-0 eams:flex-1">
        @if($title)<div class="eams:text-[13px] eams:font-bold">{{ $title }}</div>@endif
        <div class="eams:text-[13px] eams:leading-5 eams:text-muted">{{ $slot }}</div>
    </div>
    @if($dismissible)
        <button type="button" @click="visible = false" class="eams:inline-flex eams:size-6 eams:shrink-0 eams:items-center eams:justify-center eams:rounded eams:border-0 eams:bg-transparent eams:text-subtle eams:hover:bg-surface-hover eams:hover:text-ink" aria-label="Tutup notifikasi">
            <i class="bi bi-x-lg eams:text-xs" aria-hidden="true"></i>
        </button>
    @endif
</div>
