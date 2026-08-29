@props(['month' => null, 'year' => null, 'prevUrl' => null, 'nextUrl' => null, 'disabledNext' => false])
<div {{ $attributes->class('eams:inline-flex eams:items-center eams:gap-1') }} data-eams-component="month-nav">
    @if($prevUrl)
        <a href="{{ $prevUrl }}" wire:navigate
           class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface eams:text-muted eams:no-underline eams:transition-colors eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand"
           aria-label="Bulan sebelumnya">
            <i class="bi bi-chevron-left eams:text-xs" aria-hidden="true"></i>
        </a>
    @else
        <span class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface-sunk eams:text-subtle eams:opacity-60" aria-disabled="true">
            <i class="bi bi-chevron-left eams:text-xs" aria-hidden="true"></i>
        </span>
    @endif

    <span class="eams:min-w-[9rem] eams:px-2 eams:text-center eams:text-[13px] eams:font-bold eams:text-ink">
        @if(isset($slot) && ! $slot->isEmpty())
            {{ $slot }}
        @else
            {{ \Illuminate\Support\Str::ucfirst(\Illuminate\Support\Carbon::createFromDate($year ?? now()->year, $month ?? now()->month, 1)->translatedFormat('F Y')) }}
        @endif
    </span>

    @if($nextUrl && ! $disabledNext)
        <a href="{{ $nextUrl }}" wire:navigate
           class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface eams:text-muted eams:no-underline eams:transition-colors eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand"
           aria-label="Bulan berikutnya">
            <i class="bi bi-chevron-right eams:text-xs" aria-hidden="true"></i>
        </a>
    @else
        <span class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface-sunk eams:text-subtle eams:opacity-60" aria-disabled="true" @if($disabledNext) title="Bulan depan terkunci" @endif>
            <i class="bi bi-chevron-right eams:text-xs" aria-hidden="true"></i>
        </span>
    @endif
</div>
