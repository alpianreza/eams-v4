<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — {{ config('eams.company_name', 'EAMS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="d-flex align-items-center" style="min-height:100vh;background:linear-gradient(135deg,#1e2a38,#0d6efd)">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <i class="bi bi-shield-check fs-3 text-primary"></i>
                        <h1 class="h4 mb-0">{{ config('eams.company_name', 'EAMS') }}</h1>
                    </div>
                    <p class="text-muted small mb-4">Environmental & Asset Management System. Masuk dengan username atau email Anda.</p>

                    @if ($errors->any())
                        <div class="alert alert-danger py-2">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="login" class="form-label">Username atau Email</label>
                            <input type="text" id="login" name="login" value="{{ old('login') }}" class="form-control" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" id="remember" name="remember" class="form-check-input">
                            <label for="remember" class="form-check-label">Ingat saya</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Masuk</button>
                    </form>
                </div>
            </div>
            <p class="text-center text-white-50 small mt-3">&copy; {{ date('Y') }} PT YHS</p>
        </div>
    </div>
</div>
</body>
</html>
