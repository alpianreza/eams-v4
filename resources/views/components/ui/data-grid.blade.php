@props([
    'headers' => [],
    'emptyText' => 'Belum ada data.',
    'stickyHeader' => true,
    'stickyFirstColumn' => true,
    'compact' => false,
    'label' => 'Tabel data',
])

@php
    $hasRows = isset($slot) && ! $slot->isEmpty();
    $cellPadding = $compact
        ? 'eams:[&_th]:px-2.5 eams:[&_th]:py-2 eams:[&_td]:px-2.5 eams:[&_td]:py-2'
        : 'eams:[&_th]:px-3 eams:[&_th]:py-2.5 eams:[&_td]:px-3 eams:[&_td]:py-2.5';
@endphp

<div {{ $attributes->class('eams:overflow-x-auto eams:rounded-eams-lg eams:border eams:border-border') }}
     x-data="eamsDataGrid()"
     x-on:keydown="onKeydown($event)"
     data-eams-component="data-grid">
    <table class="eams:w-full eams:border-collapse eams:bg-surface eams:text-left eams:text-[13px] eams:text-ink {{ $cellPadding }}"
           aria-label="{{ $label }}">
        @if(! empty($headers))
            <thead>
                <tr>
                    @foreach($headers as $index => $header)
                        @php
                            $column = is_array($header) ? $header : ['label' => $header];
                            $isOffday = (bool) ($column['offday'] ?? false);
                            $headerClasses = trim(implode(' ', array_filter([
                                'eams:whitespace-nowrap eams:border-b eams:border-border eams:bg-surface-sunk eams:text-[11px] eams:font-bold eams:uppercase eams:tracking-wide eams:text-muted',
                                $stickyHeader ? 'eams:sticky eams:top-0 eams:z-10' : null,
                                $stickyFirstColumn && $index === 0 ? 'eams:left-0 eams:z-20 eams:shadow-[1px_0_0_var(--eams-border)]' : null,
                                $isOffday ? 'eams:bg-warning-soft eams:text-warning' : null,
                                $column['class'] ?? null,
                            ])));
                        @endphp
                        <th scope="{{ $column['scope'] ?? 'col' }}" class="{{ $headerClasses }}" @if($isOffday) data-offday @endif>
                            {{ $column['label'] ?? '' }}
                        </th>
                    @endforeach
                </tr>
            </thead>
        @endif

        <tbody class="eams:[&_td]:border-b eams:[&_td]:border-border eams:[&_tr:last-child_td]:border-b-0 eams:[&_tr]:transition-colors eams:[&_tr:hover]:bg-surface-hover {{ $stickyFirstColumn ? 'eams:[&_tbody_td:first-child]:sticky eams:[&_tbody_td:first-child]:left-0 eams:[&_tbody_td:first-child]:z-10 eams:[&_tbody_td:first-child]:bg-surface eams:[&_tbody_td:first-child]:shadow-[1px_0_0_var(--eams-border)] eams:[&_tbody_tr:hover_td:first-child]:bg-surface-hover' : '' }}">
            @if($hasRows)
                {{ $slot }}
            @else
                <tr>
                    <td colspan="{{ max(1, count($headers)) }}" class="eams:px-3 eams:py-8 eams:text-center eams:text-sm eams:text-subtle">
                        {{ $emptyText }}
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</div>
