@props(['title' => null, 'subtitle' => null, 'padding' => true])

<section {{ $attributes->class('eams:overflow-hidden eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface eams:shadow-eams-1') }} data-eams-component="card">
    @if($title || $subtitle || isset($header) || isset($actions))
        <header class="eams:flex eams:flex-wrap eams:items-start eams:justify-between eams:gap-3 eams:border-b eams:border-border eams:px-4 eams:py-3">
            <div class="eams:min-w-0">
                @if(isset($header))
                    {{ $header }}
                @else
                    @if($title)<h2 class="eams:m-0 eams:text-sm eams:font-bold eams:text-ink">{{ $title }}</h2>@endif
                    @if($subtitle)<p class="eams:mb-0 eams:mt-1 eams:text-xs eams:text-muted">{{ $subtitle }}</p>@endif
                @endif
            </div>
            @isset($actions)<div class="eams:flex eams:flex-wrap eams:items-center eams:gap-2">{{ $actions }}</div>@endisset
        </header>
    @endif

    <div @class(['eams:p-4' => $padding])>{{ $slot }}</div>

    @isset($footer)
        <footer class="eams:border-t eams:border-border eams:bg-surface-sunk eams:px-4 eams:py-3">{{ $footer }}</footer>
    @endisset
</section>
