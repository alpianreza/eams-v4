@props(['value' => 0, 'max' => 100, 'label' => null, 'size' => 'md', 'tone' => null])

@php
$percent = $max > 0 ? max(0, min(100, (int) round(($value / $max) * 100))) : 0;
if ($percent >= 100) { $autoTone = 'success'; }
elseif ($percent >= 50) { $autoTone = 'info'; }
elseif ($percent > 0) { $autoTone = 'warning'; }
else { $autoTone = 'neutral'; }
if ($tone !== null) { $autoTone = $tone; }
$barTones = [
    'success' => 'eams:bg-success',
    'info' => 'eams:bg-info',
    'warning' => 'eams:bg-warning',
    'danger' => 'eams:bg-danger',
    'neutral' => 'eams:bg-subtle',
    'brand' => 'eams:bg-brand',
];
$heights = ['sm' => 'eams:h-1.5', 'md' => 'eams:h-2.5', 'lg' => 'eams:h-4'];
$barTone = $barTones[$autoTone];
@endphp

<div {{ $attributes->class('eams:grid eams:gap-1') }} data-eams-component="progress"
     role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"
     @if($label) aria-label="{{ $label }}" @endif>
    @if($label)
        <div class="eams:flex eams:items-center eams:justify-between eams:text-[11px] eams:text-muted">
            <span>{{ $label }}</span>
            <span class="eams:tabular-nums">{{ $value }}/{{ $max }}</span>
        </div>
    @endif
    <div class="eams:w-full eams:overflow-hidden eams:rounded-full eams:bg-surface-sunk {{ $heights[$size] ?? $heights['md'] }}">
        <div class="eams:h-full eams:rounded-full eams:transition-[width] eams:duration-300 {{ $barTone }}"
             style="width: {{ $percent }}%"></div>
    </div>
</div>
