<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Beranda') — {{ config('eams.company_name', 'EAMS') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f4f6f9; }
        .sidebar { position: fixed; top: 0; bottom: 0; left: 0; width: 250px; background: #1e2a38; color: #cfd8e3; overflow-y: auto; z-index: 100; }
        .sidebar .brand { padding: 1rem 1.25rem; font-weight: 700; color: #fff; border-bottom: 1px solid rgba(255,255,255,.08); display: flex; align-items: center; gap: .5rem; }
        .sidebar .group { padding: .9rem 1.25rem .35rem; font-size: .68rem; text-transform: uppercase; letter-spacing: .06em; color: #7d8b9c; }
        .sidebar a { display: flex; align-items: center; gap: .6rem; padding: .45rem 1.25rem; color: #cfd8e3; text-decoration: none; font-size: .9rem; }
        .sidebar a:hover { background: rgba(255,255,255,.06); color: #fff; }
        .sidebar a.active { background: #0d6efd; color: #fff; border-radius: .35rem; margin: 0 .5rem; padding-left: .75rem; }
        .sidebar a i { width: 1.1rem; }
        .main { margin-left: 250px; min-height: 100vh; display: flex; flex-direction: column; }
        .topbar { background: #fff; border-bottom: 1px solid #e3e8ee; padding: .6rem 1.5rem; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 50; }
        .content { padding: 1.5rem; flex: 1; }
        @media (max-width: 768px) { .sidebar { width: 0; overflow: hidden; } .main { margin-left: 0; } }
    </style>
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
            <div class="fw-semibold text-muted">@yield('title', 'Beranda')</div>
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('notifications.index') }}" class="text-muted position-relative text-decoration-none">
                    <i class="bi bi-bell"></i>
                    @if($unreadNotifications > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:.6rem">{{ $unreadNotifications }}</span>
                    @endif
                </a>
                <div class="dropdown">
                    <a class="text-decoration-none text-dark dropdown-toggle" data-bs-toggle="dropdown" href="#">{{ auth()->user()->name }}</a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text small text-muted">{{ ucfirst(auth()->user()->role) }} · {{ auth()->user()->permission }}</span></li>
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
</body>
</html>
