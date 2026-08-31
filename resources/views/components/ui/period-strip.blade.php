@props([
    'periods' => [],
    'currentKey' => null,
    'month' => null,
    'year' => null,
    'prevUrl' => null,
    'nextUrl' => null,
    'disabledNext' => false,
    'frequency' => null,
])

<div {{ $attributes->class('eams:flex eams:flex-col eams:gap-3 eams:rounded-eams eams:border eams:border-border eams:bg-surface eams:p-3 eams:sm:p-4') }} data-eams-component="period-strip">
    <div class="eams:flex eams:flex-wrap eams:items-center eams:justify-between eams:gap-2">
        <x-ui.month-nav :month="$month" :year="$year" :prev-url="$prevUrl" :next-url="$nextUrl" :disabled-next="$disabledNext" />
        @if($frequency)
            <span class="eams:text-xs eams:font-semibold eams:uppercase eams:tracking-wider eams:text-muted">
                {{ $frequency }}
            </span>
        @endif
    </div>

    <div class="eams:no-scrollbar eams:flex eams:items-center eams:gap-2 eams:overflow-x-auto eams:py-1">
        @forelse($periods as $p)
            @php
                $item = (array) $p;
                $key = (string) ($item['key'] ?? '');
                $label = (string) ($item['label'] ?? $key);
                $status = (string) ($item['status'] ?? 'OPEN');
                $isActive = (bool) ($item['active'] ?? ($currentKey !== null && $key === (string) $currentKey));
                $isEditable = array_key_exists('editable', $item) ? (bool) $item['editable'] : true;
                $isDisabled = (bool) ($item['disabled'] ?? (! $isEditable));
                $reason = $item['reason'] ?? ($isDisabled ? 'Periode terkunci' : null);
                $url = $item['url'] ?? null;
            @endphp

            <x-ui.period-chip :status="$status" :label="$label" :active="$isActive" :disabled="$isDisabled"
                              :title="$reason" :href="$isDisabled ? null : $url" data-period-key="{{ $key }}" />
        @empty
            <span class="eams:text-xs eams:text-subtle">Tidak ada periode.</span>
        @endforelse
    </div>
</div>
