@props(['items' => [], 'dotTone' => 'brand'])

@php
    $dotTones = [
        'brand' => 'eams:bg-brand',
        'success' => 'eams:bg-success',
        'warning' => 'eams:bg-warning',
        'danger' => 'eams:bg-danger',
        'info' => 'eams:bg-info',
        'neutral' => 'eams:bg-subtle',
    ];
    $dot = $dotTones[$dotTone] ?? $dotTones['brand'];
@endphp
<ol {{ $attributes->class('eams:grid eams:gap-0') }} data-eams-component="timeline">
    @forelse($items as $index => $item)
        <li class="eams:relative eams:flex eams:gap-3 eams:pb-5 eams:last:pb-0">
            @if(! ($index === count($items) - 1))
                <span class="eams:absolute eams:bottom-0 eams:left-[7px] eams:top-4 eams:w-px eams:bg-border" aria-hidden="true"></span>
            @endif
            <span class="eams:z-10 eams:mt-1 eams:size-[15px] eams:shrink-0 eams:rounded-full eams:border-2 eams:border-surface {{ $dot }}" aria-hidden="true"></span>
            <div class="eams:min-w-0 eams:flex-1">
                @if(isset($item['title']))
                    <p class="eams:m-0 eams:text-[13px] eams:font-semibold eams:text-ink">{{ $item['title'] }}</p>
                @endif
                @if(isset($item['meta']))
                    <p class="eams:mb-0 eams:mt-0.5 eams:text-[11px] eams:text-muted">{{ $item['meta'] }}</p>
                @endif
                @if(isset($item['body']))
                    <div class="eams:mt-1 eams:text-[13px] eams:leading-5 eams:text-muted">{{ $item['body'] }}</div>
                @endif
            </div>
        </li>
    @empty
        <li class="eams:text-sm eams:text-subtle">Tidak ada riwayat.</li>
    @endforelse
</ol>
