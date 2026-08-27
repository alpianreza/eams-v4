@php($pageTitle = trim($__env->yieldContent('title', 'Beranda')) ?: 'Beranda')
<!DOCTYPE html>
<html lang="id" data-bs-theme="light" data-eams-accent="indigo">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} - {{ config('eams.company_name', 'EAMS') }}</title>

    {{-- Tema diterapkan sebelum paint supaya tidak ada kedip putih. --}}
    <script>
        (function () {
            var root = document.documentElement;
            var mode = 'system';
            var accent = 'indigo';

            try {
                var storedMode = localStorage.getItem('eams-theme');
                if (['light', 'dark', 'system'].indexOf(storedMode) !== -1) {
                    mode = storedMode;
                }

                var storedAccent = localStorage.getItem('eams-accent');
                if (['indigo', 'emerald', 'violet', 'amber', 'rose', 'ocean'].indexOf(storedAccent) !== -1) {
                    accent = storedAccent;
                }
            } catch (error) {}

            var dark = mode === 'dark'
                || (mode === 'system' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);

            root.setAttribute('data-bs-theme', dark ? 'dark' : 'light');
            root.setAttribute('data-eams-accent', accent);
        })();
    </script>

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body x-data="eamsShell" @keydown.escape.window="closeOverlays()"
      class="eams:min-h-screen eams:bg-canvas eams:font-sans eams:text-ink eams:antialiased">
    <div class="navigation-progress" aria-hidden="true"></div>

    @auth
    @php($unreadNotifications = \App\Models\Notification::where('user_id', auth()->id())->whereNull('read_at')->count())
    @php($flashToasts = collect([
        ['type' => 'success', 'message' => session('status')],
        ['type' => 'success', 'message' => session('success')],
        ['type' => 'error', 'message' => session('error')],
        ['type' => 'warning', 'message' => session('warning')],
        ['type' => 'info', 'message' => session('info')],
    ])->filter(fn ($toast) => filled($toast['message']))->values()->all())

    <a class="skip-link" href="#main-content">Lewati ke konten</a>

    <aside id="primary-sidebar" data-eams-shell="sidebar"
           :class="{
               'eams:translate-x-0': sidebarOpen,
               'eams:-translate-x-full': ! sidebarOpen,
               'eams:lg:w-20': sidebarCollapsed,
               'eams:lg:w-[var(--layout-sidebar-w)]': ! sidebarCollapsed
           }"
           class="eams:fixed eams:inset-y-0 eams:left-0 eams:z-[100] eams:flex eams:w-[min(17rem,88vw)] eams:-translate-x-full eams:flex-col eams:overflow-hidden eams:border-r eams:border-sidebar-border eams:bg-sidebar eams:text-sidebar-ink eams:shadow-eams-3 eams:transition-[width,transform] eams:duration-200 eams:ease-out eams:motion-reduce:transition-none eams:lg:translate-x-0"
           aria-label="Navigasi utama">
        <a href="{{ route('home') }}" wire:navigate @click="sidebarOpen = false"
           class="eams:flex eams:min-h-16 eams:shrink-0 eams:items-center eams:gap-3 eams:border-b eams:border-sidebar-border eams:bg-sidebar-deep eams:px-4 eams:text-white eams:no-underline eams:hover:text-white">
            <span class="eams:inline-flex eams:size-10 eams:shrink-0 eams:items-center eams:justify-center eams:rounded-eams eams:bg-brand eams:text-lg eams:text-white eams:shadow-eams-2">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
            </span>
            <span :class="{ 'eams:lg:hidden': sidebarCollapsed }" class="eams:flex eams:min-w-0 eams:flex-col eams:leading-tight">
                <span class="eams:truncate eams:text-base eams:font-extrabold eams:tracking-wide">{{ config('eams.company_name', 'EAMS') }}</span>
                <span class="eams:mt-0.5 eams:text-[10px] eams:font-semibold eams:uppercase eams:tracking-[0.12em] eams:text-sidebar-muted">Asset &amp; Compliance</span>
            </span>
        </a>

        <nav class="eams:min-h-0 eams:flex-1 eams:overflow-y-auto eams:px-2 eams:pb-4" aria-label="Menu aplikasi">
            @foreach(\App\Support\Menu::for(auth()->user()) as $group)
                <div :class="{ 'eams:lg:hidden': sidebarCollapsed }"
                     class="eams:px-2 eams:pb-1 eams:pt-4 eams:text-[10px] eams:font-bold eams:uppercase eams:tracking-[0.12em] eams:text-sidebar-muted">
                    {{ $group['group'] }}
                </div>
                @foreach($group['items'] as $item)
                    @php($url = route($item['route'], $item['params'] ?? []))
                    @php($isActive = request()->url() === $url)
                    <a href="{{ $url }}" wire:navigate wire:current="eams:bg-brand eams:text-brand-contrast eams:shadow-eams-2"
                       @click="sidebarOpen = false"
                       :title="sidebarCollapsed ? @js($item['label']) : null"
                       class="eams-nav-link {{ $isActive ? 'is-active' : '' }} eams:my-0.5 eams:flex eams:min-h-10 eams:items-center eams:gap-3 eams:rounded-eams eams:px-3 eams:py-2 eams:text-[13px] eams:font-medium eams:text-sidebar-ink eams:no-underline eams:transition-colors eams:hover:bg-white/10 eams:hover:text-white eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand"
                       @if($isActive) aria-current="page" @endif>
                        <i class="bi bi-{{ $item['icon'] }} eams:w-5 eams:shrink-0 eams:text-center eams:text-base eams:opacity-80" aria-hidden="true"></i>
                        <span :class="{ 'eams:lg:hidden': sidebarCollapsed }" class="eams:min-w-0 eams:truncate">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            @endforeach
        </nav>

        <div :class="{ 'eams:lg:hidden': sidebarCollapsed }"
             class="eams:flex eams:shrink-0 eams:items-center eams:justify-between eams:gap-2 eams:border-t eams:border-sidebar-border eams:bg-sidebar-deep eams:px-4 eams:py-3 eams:text-xs eams:text-sidebar-muted">
            <span>EAMS v4</span>
            <span>&copy; {{ date('Y') }} PT YHS</span>
        </div>
    </aside>

    <button type="button" data-eams-shell="backdrop" x-cloak x-show="sidebarOpen" x-transition.opacity
            @click="sidebarOpen = false"
            class="eams:fixed eams:inset-0 eams:z-[90] eams:border-0 eams:bg-black/50 eams:p-0 eams:lg:hidden"
            aria-label="Tutup menu"></button>

    <div :class="sidebarCollapsed ? 'eams:lg:ml-20' : 'eams:lg:ml-[var(--layout-sidebar-w)]'"
         class="eams:flex eams:min-h-screen eams:flex-col eams:transition-[margin] eams:duration-200 eams:motion-reduce:transition-none eams:print:ml-0">
        <header data-eams-shell="topbar"
                class="eams:sticky eams:top-0 eams:z-50 eams:flex eams:min-h-16 eams:items-center eams:justify-between eams:gap-3 eams:border-b eams:border-border eams:px-3 eams:sm:px-5 eams:print:hidden">
            <div class="eams:flex eams:min-w-0 eams:items-center eams:gap-2">
                <button type="button" @click="sidebarOpen = true" :aria-expanded="sidebarOpen"
                        class="eams:inline-flex eams:size-9 eams:shrink-0 eams:items-center eams:justify-center eams:rounded-full eams:border eams:border-border eams:bg-surface-sunk eams:text-ink eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand eams:lg:hidden"
                        aria-controls="primary-sidebar" aria-label="Buka menu">
                    <i class="bi bi-list eams:text-xl" aria-hidden="true"></i>
                </button>

                <button type="button" @click="toggleCollapsed()"
                        :aria-label="sidebarCollapsed ? 'Perluas sidebar' : 'Ringkas sidebar'"
                        :title="sidebarCollapsed ? 'Perluas sidebar' : 'Ringkas sidebar'"
                        class="eams:hidden eams:size-9 eams:shrink-0 eams:items-center eams:justify-center eams:rounded-full eams:border eams:border-border eams:bg-surface-sunk eams:text-ink eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand eams:lg:inline-flex"
                        aria-controls="primary-sidebar">
                    <i class="bi" :class="sidebarCollapsed ? 'bi-layout-sidebar-inset' : 'bi-layout-sidebar-inset-reverse'" aria-hidden="true"></i>
                </button>

                <div class="eams:min-w-0">
                    <div class="eams:truncate eams:text-sm eams:font-bold eams:text-ink eams:sm:text-base">{{ $pageTitle }}</div>
                    <div class="eams:hidden eams:text-[11px] eams:text-muted eams:sm:block">Enterprise Asset &amp; Compliance Management</div>
                </div>
            </div>

            <div class="eams:flex eams:shrink-0 eams:items-center eams:gap-2">
                <x-theme-picker />

                <a href="{{ route('notifications.index') }}" wire:navigate
                   class="eams:relative eams:inline-flex eams:size-9 eams:items-center eams:justify-center eams:rounded-full eams:border eams:border-border eams:bg-surface-sunk eams:text-ink eams:no-underline eams:transition-colors eams:hover:border-brand eams:hover:bg-brand-soft eams:hover:text-brand eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand"
                   title="Notifikasi" aria-label="Notifikasi">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                    @if($unreadNotifications > 0)
                        <span class="eams:absolute eams:-right-1 eams:-top-1 eams:inline-flex eams:min-w-4.5 eams:h-4.5 eams:items-center eams:justify-center eams:rounded-full eams:border-2 eams:border-surface eams:bg-danger eams:px-1 eams:text-[9px] eams:font-bold eams:leading-none eams:text-white">
                            {{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}
                        </span>
                    @endif
                </a>

                <div class="eams:relative" x-data="eamsDropdown" @click.outside="close" @keydown.escape.stop="close">
                    <button type="button" @click="toggle" :aria-expanded="open"
                            class="eams:inline-flex eams:h-9 eams:items-center eams:gap-2 eams:rounded-full eams:border eams:border-border eams:bg-surface-sunk eams:p-1 eams:pr-2 eams:text-ink eams:hover:border-brand eams:hover:bg-brand-soft eams:focus-visible:outline-none eams:focus-visible:ring-2 eams:focus-visible:ring-brand"
                            aria-haspopup="true" aria-label="Menu pengguna">
                        <span class="eams:inline-flex eams:size-7 eams:items-center eams:justify-center eams:rounded-full eams:bg-brand eams:text-[11px] eams:font-extrabold eams:text-white">
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 2)) }}
                        </span>
                        <span class="eams:hidden eams:max-w-36 eams:truncate eams:text-[13px] eams:font-semibold eams:md:block">{{ auth()->user()->name }}</span>
                        <i class="bi bi-chevron-down eams:text-[10px] eams:text-muted" aria-hidden="true"></i>
                    </button>

                    <div x-cloak x-show="open" x-transition.origin.top.right
                         class="eams:absolute eams:right-0 eams:top-[calc(100%+0.5rem)] eams:z-[130] eams:w-64 eams:overflow-hidden eams:rounded-eams eams:border eams:border-border eams:bg-surface eams:shadow-eams-3"
                         role="menu">
                        <div class="eams:border-b eams:border-border eams:px-4 eams:py-3">
                            <div class="eams:truncate eams:text-sm eams:font-bold eams:text-ink">{{ auth()->user()->name }}</div>
                            <div class="eams:mt-0.5 eams:text-xs eams:text-muted">{{ ucfirst(auth()->user()->role) }} / {{ auth()->user()->permission }}</div>
                        </div>
                        <div class="eams:p-1.5">
                            <a href="{{ route('self.password.edit') }}" wire:navigate @click="close"
                               class="eams:flex eams:items-center eams:gap-2.5 eams:rounded-eams-sm eams:px-3 eams:py-2 eams:text-[13px] eams:text-ink eams:no-underline eams:hover:bg-surface-hover eams:hover:text-brand"
                               role="menuitem">
                                <i class="bi bi-key eams:w-4 eams:text-center" aria-hidden="true"></i>
                                Ganti Password
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="eams:m-0">
                                @csrf
                                <button type="submit"
                                        class="eams:flex eams:w-full eams:items-center eams:gap-2.5 eams:rounded-eams-sm eams:border-0 eams:bg-transparent eams:px-3 eams:py-2 eams:text-left eams:text-[13px] eams:text-danger eams:hover:bg-danger-soft"
                                        role="menuitem">
                                    <i class="bi bi-box-arrow-right eams:w-4 eams:text-center" aria-hidden="true"></i>
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main id="main-content" data-eams-page-content
              class="eams:mx-auto eams:w-full eams:max-w-[var(--layout-content-max)] eams:flex-1 eams:px-3 eams:py-4 eams:sm:px-5 eams:sm:py-5 eams:print:max-w-none eams:print:p-0">
            <x-breadcrumb :title="$pageTitle" class="eams:mb-4" />

            @if($flashToasts !== [])
                <div x-data x-init="$nextTick(() => { @foreach($flashToasts as $flashToast) $store.toasts.push(@js($flashToast)); @endforeach })"></div>
                <noscript>
                    @foreach($flashToasts as $flashToast)
                        <div class="alert alert-{{ $flashToast['type'] === 'error' ? 'danger' : $flashToast['type'] }}">{{ $flashToast['message'] }}</div>
                    @endforeach
                </noscript>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            @yield('content')
        </main>
    </div>

    <x-toast-host />
    @else
        @yield('content')
    @endauth

    @livewireScriptConfig
    @stack('scripts')
</body>
</html>
