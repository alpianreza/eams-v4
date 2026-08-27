<!DOCTYPE html>
<html lang="id" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Beranda') - {{ config('eams.company_name', 'EAMS') }}</title>

    <script>
        (function () {
            try {
                var k = 'eams-theme';
                var t = localStorage.getItem(k) || (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                document.documentElement.setAttribute('data-bs-theme', t);
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/page-header.css') }}">
    @stack('styles')
</head>
<body>
    @auth
    @php($unreadNotifications = \App\Models\Notification::where('user_id', auth()->id())->whereNull('read_at')->count())
    <nav class="sidebar">
        <div class="brand"><i class="bi bi-shield-check"></i> {{ config('eams.company_name', 'EAMS') }}</div>
        @foreach(\App\Support\Menu::for(auth()->user()) as $group)
            <div class="group">{{ $group['group'] }}</div>
            @foreach($group['items'] as $item)
                @php($url = route($item['route'], $item['params'] ?? []))
                <a href="{{ $url }}" class="{{ request()->url() === $url ? 'active' : '' }}"><i class="bi bi-{{ $item['icon'] }}"></i> {{ $item['label'] }}</a>
            @endforeach
        @endforeach
    </nav>

    <div class="main">
        <div class="topbar">
            <div class="topbar-title">@yield('title', 'Beranda')</div>
            <div class="d-flex align-items-center gap-3">
                <button type="button" class="theme-toggle" onclick="window.__eams.toggleTheme()" title="Ganti tema" aria-label="Ganti tema">
                    <i class="bi bi-moon-stars" data-theme-icon></i>
                </button>
                <a href="{{ route('notifications.index') }}" class="text-body-secondary position-relative text-decoration-none" title="Notifikasi">
                    <i class="bi bi-bell"></i>
                    @if($unreadNotifications > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem">{{ $unreadNotifications }}</span>
                    @endif
                </a>
                <div class="dropdown">
                    <a class="text-decoration-none text-body dropdown-toggle" data-bs-toggle="dropdown" href="#">{{ auth()->user()->name }}</a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text small text-body-secondary">{{ ucfirst(auth()->user()->role) }} / {{ auth()->user()->permission }}</span></li>
                        <li><a class="dropdown-item" href="{{ route('self.password.edit') }}"><i class="bi bi-key me-2"></i>Ganti Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>Logout</button></form></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="content">
            @if(session('status'))<div class="alert alert-success alert-dismissible fade show">{{ session('status') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
            @if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
            @yield('content')
        </div>
    </div>
    @else
        @yield('content')
    @endauth

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
    <script>
        window.__eams = window.__eams || {};
        (function () {
            var KEY = 'eams-theme';
            function apply(t) {
                document.documentElement.setAttribute('data-bs-theme', t);
                document.querySelectorAll('[data-theme-icon]').forEach(function (i) {
                    i.className = 'bi ' + (t === 'dark' ? 'bi-sun' : 'bi-moon-stars');
                });
            }
            window.__eams.toggleTheme = function () {
                var cur = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
                apply(cur);
                try { localStorage.setItem(KEY, cur); } catch (e) {}
            };
            apply(document.documentElement.getAttribute('data-bs-theme') || 'light');
        })();
    </script>
</body>
</html>
