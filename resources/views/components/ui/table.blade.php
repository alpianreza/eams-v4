@props(['compact' => false, 'striped' => false, 'label' => null])

<div class="eams:overflow-x-auto eams:rounded-eams-lg eams:border eams:border-border" data-eams-component="table">
    <table @if($label) aria-label="{{ $label }}" @endif
           {{ $attributes->class([
               'eams:w-full eams:border-collapse eams:bg-surface eams:text-left eams:text-[13px] eams:text-ink',
               'eams:[&_th]:px-3 eams:[&_th]:py-2.5 eams:[&_td]:px-3 eams:[&_td]:py-2.5' => ! $compact,
               'eams:[&_th]:px-2.5 eams:[&_th]:py-2 eams:[&_td]:px-2.5 eams:[&_td]:py-2' => $compact,
               'eams:[&_thead_th]:sticky eams:[&_thead_th]:top-0 eams:[&_thead_th]:z-10 eams:[&_thead_th]:border-b eams:[&_thead_th]:border-border eams:[&_thead_th]:bg-surface-sunk eams:[&_thead_th]:text-[11px] eams:[&_thead_th]:font-bold eams:[&_thead_th]:uppercase eams:[&_thead_th]:tracking-wide eams:[&_thead_th]:text-muted',
               'eams:[&_tbody_td]:border-b eams:[&_tbody_td]:border-border eams:[&_tbody_tr:last-child_td]:border-b-0 eams:[&_tbody_tr]:transition-colors eams:[&_tbody_tr:hover]:bg-surface-hover',
               'eams:[&_tbody_tr:nth-child(even)]:bg-surface-sunk/45' => $striped,
           ]) }}>
        {{ $slot }}
    </table>
</div>
