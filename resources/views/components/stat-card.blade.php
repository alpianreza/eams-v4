@props([
    'label' => '',
    'value' => '',
    'icon' => 'bi-bar-chart',
    'tone' => 'neutral',
    'hint' => '',
    'trend' => '',
    'trendDirection' => 'flat',
    'href' => null,
])

@php($tag = $href ? 'a' : 'div')
@php($trendIcon = match ($trendDirection) {
    'up' => 'bi-arrow-up-right',
    'down' => 'bi-arrow-down-right',
    default => 'bi-dash',
})

<{{ $tag }} {{ $attributes->merge(['class' => 'stat-card']) }} data-tone="{{ $tone }}" @if($href) href="{{ $href }}" @endif>
    <span class="stat-card__icon"><i class="bi {{ $icon }}"></i></span>

    <span class="stat-card__body">
        <span class="stat-card__label">{{ $label }}</span>
        <span class="stat-card__value">{{ $value }}</span>

        @if($trend !== '')
            <span class="stat-card__trend is-{{ $trendDirection }}"><i class="bi {{ $trendIcon }}"></i>{{ $trend }}</span>
        @endif

        @if($hint !== '')
            <span class="stat-card__hint">{{ $hint }}</span>
        @endif
    </span>
</{{ $tag }}>
