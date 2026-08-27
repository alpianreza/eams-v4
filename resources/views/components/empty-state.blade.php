@props([
    'icon' => 'bi-inbox',
    'title' => 'Belum ada data',
    'text' => '',
    'boxed' => true,
])

<div {{ $attributes->merge(['class' => 'empty-state' . ($boxed ? ' empty-state--boxed' : '')]) }}>
    <span class="empty-state__icon"><i class="bi {{ $icon }}"></i></span>
    <p class="empty-state__title">{{ $title }}</p>

    @if($text !== '')
        <p class="empty-state__text">{{ $text }}</p>
    @endif

    @if(isset($actions) && ! $actions->isEmpty())
        <div class="empty-state__actions">{{ $actions }}</div>
    @endif
</div>
