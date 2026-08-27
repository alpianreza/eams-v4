@props([
    'icon' => 'inbox',
    'title' => 'Belum ada data',
    'description' => null,
    'boxed' => true,
])

<div {{ $attributes->class([
        'eams:flex eams:flex-col eams:items-center eams:px-4 eams:py-8 eams:text-center',
        'eams:rounded-eams-lg eams:border eams:border-dashed eams:border-border-strong eams:bg-surface' => $boxed,
    ]) }} data-eams-component="empty-state">
    <span class="eams:mb-3 eams:inline-flex eams:size-12 eams:items-center eams:justify-center eams:rounded-full eams:bg-brand-soft eams:text-xl eams:text-brand">
        <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
    </span>
    <h3 class="eams:m-0 eams:text-sm eams:font-bold eams:text-ink">{{ $title }}</h3>
    @if($description)<p class="eams:mb-0 eams:mt-1.5 eams:max-w-md eams:text-[13px] eams:leading-5 eams:text-muted">{{ $description }}</p>@endif
    @if(! $slot->isEmpty())<div class="eams:mt-4 eams:flex eams:flex-wrap eams:justify-center eams:gap-2">{{ $slot }}</div>@endif
</div>
