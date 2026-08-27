@props(['tabs' => [], 'active' => null])

@php($initialTab = $active ?: ($tabs[0]['id'] ?? ''))

<div x-data="{ active: @js((string) $initialTab) }" {{ $attributes }} data-eams-component="tabs">
    <div class="eams:flex eams:gap-1 eams:overflow-x-auto eams:border-b eams:border-border" role="tablist">
        @foreach($tabs as $tab)
            <button type="button" role="tab" @click="active = @js((string) $tab['id'])"
                    :aria-selected="active === @js((string) $tab['id'])"
                    :tabindex="active === @js((string) $tab['id']) ? 0 : -1"
                    :class="active === @js((string) $tab['id']) ? 'eams:border-brand eams:text-brand' : 'eams:border-transparent eams:text-muted eams:hover:text-ink'"
                    class="eams:-mb-px eams:inline-flex eams:shrink-0 eams:items-center eams:gap-2 eams:border-b-2 eams:px-3 eams:py-2.5 eams:text-[13px] eams:font-semibold eams:transition-colors eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-inset eams:focus-visible:ring-brand">
                @if(filled($tab['icon'] ?? null))<i class="bi bi-{{ $tab['icon'] }}" aria-hidden="true"></i>@endif
                {{ $tab['label'] ?? $tab['id'] }}
                @if(isset($tab['count']))<x-ui.badge size="sm">{{ $tab['count'] }}</x-ui.badge>@endif
            </button>
        @endforeach
    </div>
    <div class="eams:pt-4">{{ $slot }}</div>
</div>
