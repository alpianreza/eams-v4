@props([
    'label' => '',
    'value' => 0,
    'icon' => null,
    'iconClass' => 'eams:bg-brand-soft eams:text-brand',
    'valueClass' => 'eams:text-ink',
    'hint' => null,
    'format' => true,
])
<x-ui.card {{ $attributes->class('') }}>
    <div class="eams:flex eams:items-start eams:justify-between eams:gap-3">
        <div class="eams:min-w-0">
            <p class="eams:m-0 eams:text-xs eams:font-semibold eams:text-muted">{{ $label }}</p>
            <strong class="eams:mt-1 eams:block eams:text-2xl eams:font-extrabold eams:leading-none eams:tabular-nums {{ $valueClass }}">
                {{ $format ? number_format($value) : $value }}
            </strong>
            @if(isset($slot) && ! $slot->isEmpty())
                <div class="eams:mt-2">{{ $slot }}</div>
            @endif
        </div>
        @if($icon)
            <span class="eams:inline-flex eams:size-9 eams:shrink-0 eams:items-center eams:justify-center eams:rounded-eams {{ $iconClass }}">
                <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
            </span>
        @endif
    </div>
    @if($hint)
        <p class="eams:mb-0 eams:mt-3 eams:text-[11px] eams:leading-4 eams:text-subtle">{{ $hint }}</p>
    @endif
</x-ui.card>
