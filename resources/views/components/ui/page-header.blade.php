@props([
    'eyebrow' => '',
    'eyebrowIcon' => '',
    'title' => '',
    'lead' => '',
    'backUrl' => '',
])
<header {{ $attributes->class('eams:flex eams:flex-col eams:gap-4 eams:rounded-eams-lg eams:border eams:border-border eams:bg-surface eams:px-4 eams:py-4 eams:shadow-eams-1 eams:sm:flex-row eams:sm:items-center eams:sm:justify-between eams:sm:px-5') }}
        data-eams-component="page-header">
    <div class="eams:flex eams:min-w-0 eams:items-start eams:gap-3">
        @if($backUrl !== '')
            <a href="{{ $backUrl }}" wire:navigate
               class="eams:mt-0.5 eams:inline-flex eams:size-9 eams:shrink-0 eams:items-center eams:justify-center eams:rounded-eams eams:border eams:border-border eams:bg-surface eams:text-muted eams:no-underline eams:transition-colors eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand"
               aria-label="Kembali">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
            </a>
        @endif
        <div class="eams:min-w-0">
            @if($eyebrow !== '')
                <p class="eams:mb-1 eams:text-[11px] eams:font-bold eams:uppercase eams:tracking-[0.12em] eams:text-brand">
                    @if($eyebrowIcon !== '')<i class="bi bi-{{ $eyebrowIcon }} eams:mr-1" aria-hidden="true"></i>@endif{{ $eyebrow }}
                </p>
            @endif
            <h1 class="eams:m-0 eams:text-xl eams:font-extrabold eams:tracking-tight eams:text-ink eams:sm:text-2xl">{{ $title }}</h1>
            @if($lead !== '')
                <p class="eams:mb-0 eams:mt-1.5 eams:max-w-2xl eams:text-[13px] eams:leading-5 eams:text-muted">{{ $lead }}</p>
            @endif
        </div>
    </div>

    @if(isset($actions) && ! $actions->isEmpty())
        <div class="eams:flex eams:flex-wrap eams:items-center eams:gap-2">{{ $actions }}</div>
    @endif

    @if(isset($media) && ! $media->isEmpty())
        <div class="eams:hidden">{{ $media }}</div>
    @endif
</header>
