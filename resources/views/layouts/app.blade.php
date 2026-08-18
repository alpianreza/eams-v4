<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'EAMS'))</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="{{ route('home') }}">{{ config('app.name', 'EAMS') }}</a>
        @auth
        <div class="d-flex align-items-center gap-3">
            <span class="text-light small">{{ auth()->user()->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-light btn-sm">Keluar</button>
            </form>
        </div>
        @endauth
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        @auth
        <aside class="col-md-2 bg-white border-end min-vh-100 p-3">
            {{-- Menu filtered by page_access (BR-44); admin sees all. Module links land in later milestones. --}}
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link" href="{{ route('home') }}">Beranda</a></li>
            </ul>
        </aside>
        @endauth
        <main class="col p-4">
            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
