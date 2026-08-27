@props([
    'title' => 'Beranda',
    'items' => [],
])

@php
    $title = trim((string) $title) ?: 'Beranda';
    $isHome = $title === 'Beranda' && $items === [];
@endphp

<nav aria-label="Breadcrumb" data-eams-component="breadcrumb"
     {{ $attributes->class(['eams:min-w-0 eams:overflow-hidden']) }}>
    <ol class="eams:m-0 eams:flex eams:min-w-0 eams:list-none eams:items-center eams:gap-1.5 eams:p-0 eams:text-xs eams:text-muted">
        <li class="eams:flex eams:min-w-0 eams:items-center eams:gap-1.5">
            @if($isHome)
                <span class="eams:inline-flex eams:items-center eams:gap-1.5 eams:font-semibold eams:text-ink" aria-current="page">
                    <i class="bi bi-house-door" aria-hidden="true"></i>
                    <span>Beranda</span>
                </span>
            @else
                <a href="{{ route('home') }}" wire:navigate
                   class="eams:inline-flex eams:items-center eams:gap-1.5 eams:text-muted eams:no-underline eams:hover:text-brand">
                    <i class="bi bi-house-door" aria-hidden="true"></i>
                    <span class="eams:hidden eams:sm:inline">Beranda</span>
                </a>
            @endif
        </li>

        @foreach($items as $item)
            <li class="eams:flex eams:min-w-0 eams:items-center eams:gap-1.5">
                <i class="bi bi-chevron-right eams:text-[10px] eams:text-subtle" aria-hidden="true"></i>
                @if(filled($item['url'] ?? null))
                    <a href="{{ $item['url'] }}" wire:navigate
                       class="eams:max-w-40 eams:truncate eams:text-muted eams:no-underline eams:hover:text-brand">
                        {{ $item['label'] ?? '' }}
                    </a>
                @else
                    <span class="eams:max-w-40 eams:truncate">{{ $item['label'] ?? '' }}</span>
                @endif
            </li>
        @endforeach

        @unless($isHome)
            <li class="eams:flex eams:min-w-0 eams:items-center eams:gap-1.5">
                <i class="bi bi-chevron-right eams:text-[10px] eams:text-subtle" aria-hidden="true"></i>
                <span class="eams:max-w-[42vw] eams:truncate eams:font-semibold eams:text-ink" aria-current="page">{{ $title }}</span>
            </li>
        @endunless
    </ol>
</nav>
