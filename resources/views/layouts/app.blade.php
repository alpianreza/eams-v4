<!DOCTYPE html>
<html lang="id" data-bs-theme="light" data-eams-accent="indigo">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Beranda') - {{ config('eams.company_name', 'EAMS') }}</title>

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

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body x-data="{ sidebarOpen: false }" @keydown.escape.window="sidebarOpen = false">
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

    <nav class="sidebar" :class="{ 'is-open': sidebarOpen }" aria-label="Navigasi utama">
        <a href="{{ route('home') }}" class="brand">
            <span class="brand__logo"><i class="bi bi-shield-check"></i></span>
            <span class="brand__text">
                <span class="brand__name">{{ config('eams.company_name', 'EAMS') }}</span>
                <span class="brand__meta">Asset &amp; Compliance</span>
            </span>
        </a>

        <div class="sidebar__scroll">
            @foreach(\App\Support\Menu::for(auth()->user()) as $group)
                <div class="group">{{ $group['group'] }}</div>
                @foreach($group['items'] as $item)
                    @php($url = route($item['route'], $item['params'] ?? []))
                    <a href="{{ $url }}" @click="sidebarOpen = false"
                       class="{{ request()->url() === $url ? 'active' : '' }}"
                       @if(request()->url() === $url) aria-current="page" @endif>
                        <i class="bi bi-{{ $item['icon'] }}"></i> {{ $item['label'] }}
                    </a>
                @endforeach
            @endforeach
        </div>

        <div class="sidebar__footer">
            <span>EAMS v4</span>
            <span>&copy; {{ date('Y') }} PT YHS</span>
        </div>
    </nav>

    <div class="sidebar-backdrop" x-cloak x-show="sidebarOpen" x-transition.opacity @click="sidebarOpen = false" aria-hidden="true"></div>

    <div class="main">
        <header class="topbar">
            <div class="d-flex align-items-center gap-2 overflow-hidden">
                <button type="button" class="icon-btn mobile-menu-toggle" @click="sidebarOpen = true" aria-label="Buka menu">
                    <i class="bi bi-list"></i>
                </button>
                <div class="topbar-title">@yield('title', 'Beranda')</div>
            </div>

            <div class="topbar__actions">
                <x-theme-picker />

                <a href="{{ route('notifications.index') }}" class="icon-btn" title="Notifikasi" aria-label="Notifikasi">
                    <i class="bi bi-bell"></i>
                    @if($unreadNotifications > 0)
                        <span class="icon-btn__badge">{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
                    @endif
                </a>

                <div class="dropdown">
                    <a class="user-chip dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">
                        <span class="user-chip__avatar">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->name, 0, 2)) }}</span>
                        <span class="user-chip__name">{{ auth()->user()->name }}</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">{{ auth()->user()->name }}</h6></li>
                        <li><span class="dropdown-item-text small text-body-secondary">{{ ucfirst(auth()->user()->role) }} / {{ auth()->user()->permission }}</span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="{{ route('self.password.edit') }}"><i class="bi bi-key me-2"></i>Ganti Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Logout</button></form></li>
                    </ul>
                </div>
            </div>
        </header>

        <main class="content" id="main-content">
            @if($flashToasts !== [])
                {{-- Flash session tampil sebagai toast; <noscript> jadi cadangan tanpa JavaScript. --}}
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

    @stack('scripts')
</body>
</html>
