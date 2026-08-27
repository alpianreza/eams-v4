@props(['paginator', 'label' => 'Navigasi halaman'])

@if($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $hasLastPage = method_exists($paginator, 'lastPage');
        $last = $hasLastPage ? $paginator->lastPage() : null;
        $start = $last ? max(1, $current - 2) : null;
        $end = $last ? min($last, $current + 2) : null;
    @endphp

    <nav aria-label="{{ $label }}" data-eams-component="pagination"
         {{ $attributes->class('eams:flex eams:flex-wrap eams:items-center eams:justify-between eams:gap-3') }}>
        @if($hasLastPage && method_exists($paginator, 'firstItem'))
            <p class="eams:m-0 eams:text-xs eams:text-muted">
                Menampilkan <span class="eams:font-semibold eams:text-ink">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
                dari <span class="eams:font-semibold eams:text-ink">{{ $paginator->total() }}</span>
            </p>
        @endif

        <div class="eams:flex eams:items-center eams:gap-1">
            @if($paginator->onFirstPage())
                <span class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface-sunk eams:text-subtle eams:opacity-60" aria-disabled="true">
                    <i class="bi bi-chevron-left eams:text-xs" aria-hidden="true"></i>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" wire:navigate rel="prev"
                   class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface eams:text-muted eams:no-underline eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand"
                   aria-label="Halaman sebelumnya">
                    <i class="bi bi-chevron-left eams:text-xs" aria-hidden="true"></i>
                </a>
            @endif

            @if($last)
                @if($start > 1)
                    <a href="{{ $paginator->url(1) }}" wire:navigate class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface eams:text-xs eams:text-muted eams:no-underline eams:hover:border-brand eams:hover:text-brand">1</a>
                    @if($start > 2)<span class="eams:px-1 eams:text-subtle" aria-hidden="true">…</span>@endif
                @endif

                @for($page = $start; $page <= $end; $page++)
                    @if($page === $current)
                        <span class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-brand eams:bg-brand eams:text-xs eams:font-bold eams:text-brand-contrast" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $paginator->url($page) }}" wire:navigate class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface eams:text-xs eams:text-muted eams:no-underline eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand">{{ $page }}</a>
                    @endif
                @endfor

                @if($end < $last)
                    @if($end < $last - 1)<span class="eams:px-1 eams:text-subtle" aria-hidden="true">…</span>@endif
                    <a href="{{ $paginator->url($last) }}" wire:navigate class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface eams:text-xs eams:text-muted eams:no-underline eams:hover:border-brand eams:hover:text-brand">{{ $last }}</a>
                @endif
            @endif

            @if($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" wire:navigate rel="next"
                   class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface eams:text-muted eams:no-underline eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand"
                   aria-label="Halaman berikutnya">
                    <i class="bi bi-chevron-right eams:text-xs" aria-hidden="true"></i>
                </a>
            @else
                <span class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface-sunk eams:text-subtle eams:opacity-60" aria-disabled="true">
                    <i class="bi bi-chevron-right eams:text-xs" aria-hidden="true"></i>
                </span>
            @endif
        </div>
    </nav>
@endif
