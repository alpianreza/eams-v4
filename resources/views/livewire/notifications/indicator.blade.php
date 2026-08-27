<a href="{{ route('notifications.index') }}" wire:navigate wire:poll.60s
   class="eams:relative eams:inline-flex eams:size-9 eams:items-center eams:justify-center eams:rounded-full eams:border eams:border-border eams:bg-surface-sunk eams:text-ink eams:no-underline eams:transition-colors eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand"
   title="Notifikasi" aria-label="Notifikasi{{ $unreadCount ? ' belum dibaca: '.$unreadCount : '' }}" data-eams-livewire="notification-indicator">
    <i class="bi bi-bell" aria-hidden="true"></i>
    @if($unreadCount > 0)
        <span class="eams:absolute eams:-right-1 eams:-top-1 eams:inline-flex eams:h-4.5 eams:min-w-4.5 eams:items-center eams:justify-center eams:rounded-full eams:border-2 eams:border-surface eams:bg-danger eams:px-1 eams:text-[9px] eams:font-bold eams:leading-none eams:text-white">
            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
        </span>
    @endif
</a>
