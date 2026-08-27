@props(['status', 'label' => null, 'size' => 'md', 'showIcon' => true])

@php($presentation = \App\Support\Ui\StatusPresentation::for((string) $status))

<x-ui.badge :variant="$presentation['tone']" :size="$size" {{ $attributes->merge(['data-status' => $presentation['key']]) }}>
    @if($showIcon)<i class="bi bi-{{ $presentation['icon'] }}" aria-hidden="true"></i>@endif
    <span>{{ $label ?: $presentation['label'] }}</span>
</x-ui.badge>
