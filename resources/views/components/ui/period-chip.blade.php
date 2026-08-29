@props([
    'status' => null,
    'label' => null,
    'disabled' => false,
    'title' => null,
    'active' => false,
])



@php
$presentation = \App\Support\Ui\StatusPresentation::for((string) ($status ?? ''));
$chipTones = [
    'success' => 'eams:border-success/40 eams:bg-success-soft eams:text-success',
    'warning' => 'eams:border-warning/40 eams:bg-warning-soft eams:text-warning',
    'danger' => 'eams:border-danger/40 eams:bg-danger-soft eams:text-danger',
    'info' => 'eams:border-info/40 eams:bg-info-soft eams:text-info',
    'neutral' => 'eams:border-border eams:bg-surface-sunk eams:text-muted',
];
$chipTone = $chipTones[$presentation['tone']] ?? $chipTones['neutral'];
$chipActiveTone = 'eams:border-brand eams:bg-brand eams:text-brand-contrast';
$chipClasses = 'eams:inline-flex eams:min-h-9 eams:items-center eams:gap-1.5 eams:rounded-eams-sm eams:border eams:px-3 eams:text-xs eams:font-semibold eams:transition-colors eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand ' . ($active ? $chipActiveTone : $chipTone) . ($disabled ? ' eams:cursor-not-allowed eams:opacity-55' : '');
@endphp

@if($disabled)
    <span {{ $attributes->class([$chipClasses]) }}
          @if($title) title="{{ $title }}" @endif
          aria-disabled="true" data-status="{{ $presentation['key'] }}" data-eams-component="period-chip">{{ $label ?? $presentation['label'] }}</span>
@else
    <button type="button" {{ $attributes->class([$chipClasses]) }}
            @if($title) title="{{ $title }}" @endif
            data-status="{{ $presentation['key'] }}" data-eams-component="period-chip">{{ $label ?? $presentation['label'] }}</button>
@endif
