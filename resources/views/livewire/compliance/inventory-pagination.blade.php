@php
    /** Custom Livewire pagination view — EAMS design system (x-ui.pagination contract). */
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();
    $start = max(1, $current - 2);
    $end = min($last, $current + 2);
    $pageName = $paginator->getPageName();
@endphp

@if($paginator->hasPages())
    <nav aria-label="Navigasi halaman inventory" data-eams-component="pagination"
         class="eams:flex eams:flex-wrap eams:items-center eams:justify-between eams:gap-3">
        <p class="eams:m-0 eams:text-xs eams:text-muted">
            Menampilkan <span class="eams:font-semibold eams:text-ink">{{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }}</span>
            dari <span class="eams:font-semibold eams:text-ink">{{ number_format($paginator->total()) }}</span>
        </p>

        <div class="eams:flex eams:items-center eams:gap-1">
            @if($paginator->onFirstPage())
                <span class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface-sunk eams:text-subtle eams:opacity-60" aria-disabled="true">
                    <i class="bi bi-chevron-left eams:text-xs" aria-hidden="true"></i>
                </span>
            @else
                <button type="button" wire:click="previousPage('{{ $pageName }}')"
                        class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface eams:text-muted eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand"
                        aria-label="Halaman sebelumnya">
                    <i class="bi bi-chevron-left eams:text-xs" aria-hidden="true"></i>
                </button>
            @endif

            @if($start > 1)
                <button type="button" wire:click="gotoPage(1, '{{ $pageName }}')"
                        class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface eams:text-xs eams:text-muted eams:hover:border-brand eams:hover:text-brand">1</button>
                @if($start > 2)<span class="eams:px-1 eams:text-subtle" aria-hidden="true">&hellip;</span>@endif
            @endif

            @for($page = $start; $page <= $end; $page++)
                @if($page === $current)
                    <span class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-brand eams:bg-brand eams:text-xs eams:font-bold eams:text-brand-contrast" aria-current="page">{{ $page }}</span>
                @else
                    <button type="button" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')"
                            class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface eams:text-xs eams:text-muted eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand"
                            aria-label="Ke halaman {{ $page }}">{{ $page }}</button>
                @endif
            @endfor

            @if($end < $last)
                @if($end < $last - 1)<span class="eams:px-1 eams:text-subtle" aria-hidden="true">&hellip;</span>@endif
                <button type="button" wire:click="gotoPage({{ $last }}, '{{ $pageName }}')"
                        class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface eams:text-xs eams:text-muted eams:hover:border-brand eams:hover:text-brand">{{ $last }}</button>
            @endif

            @if($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('{{ $pageName }}')"
                        class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface eams:text-muted eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand"
                        aria-label="Halaman berikutnya">
                    <i class="bi bi-chevron-right eams:text-xs" aria-hidden="true"></i>
                </button>
            @else
                <span class="eams:inline-flex eams:size-8 eams:items-center eams:justify-center eams:rounded-eams-sm eams:border eams:border-border eams:bg-surface-sunk eams:text-subtle eams:opacity-60" aria-disabled="true">
                    <i class="bi bi-chevron-right eams:text-xs" aria-hidden="true"></i>
                </span>
            @endif
        </div>
    </nav>
@endif
