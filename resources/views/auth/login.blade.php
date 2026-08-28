<!DOCTYPE html>
<html lang="id" data-bs-theme="light" data-eams-accent="indigo">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk — {{ config('eams.company_name', 'EAMS') }}</title>

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
</head>
<body class="auth-page">
<div class="auth-page__theme"><x-theme-picker /></div>

<div class="auth-shell">
    <aside class="auth-hero">
        <div class="auth-hero__brand">
            <span class="auth-hero__logo"><i class="bi bi-shield-check"></i></span>
            <span>
                <span class="auth-hero__name">{{ config('eams.company_name', 'EAMS') }}</span>
                <span class="auth-hero__tag">Asset &amp; Compliance</span>
            </span>
        </div>

        <div class="auth-hero__body">
            <h1 class="auth-hero__title">Kelola aset dan kepatuhan dalam satu tempat.</h1>
            <p class="auth-hero__lead">Environmental &amp; Asset Management System. Masuk dengan username atau email Anda.</p>

            <ul class="auth-hero__list">
                <li><i class="bi bi-check-circle-fill"></i> Checklist harian, mingguan, dan bulanan dalam satu alur.</li>
                <li><i class="bi bi-check-circle-fill"></i> Inventaris, QR code, dan bukti foto yang terlacak.</li>
                <li><i class="bi bi-check-circle-fill"></i> Laporan kepatuhan siap cetak kapan pun dibutuhkan.</li>
            </ul>
        </div>

        <p class="auth-hero__footer">&copy; {{ date('Y') }} PT YHS. Seluruh hak cipta dilindungi.</p>
    </aside>

    <main class="auth-panel">
        <div class="auth-card">
            <div class="auth-card__mobile-brand">
                <span class="auth-hero__logo"><i class="bi bi-shield-check"></i></span>
                <span>
                    <span class="auth-hero__name">{{ config('eams.company_name', 'EAMS') }}</span>
                    <span class="auth-hero__tag">Asset &amp; Compliance</span>
                </span>
            </div>

            <div class="auth-card__head">
                <h2 class="auth-card__title">Masuk</h2>
                <p class="auth-card__lead">Gunakan akun EAMS Anda untuk melanjutkan.</p>
            </div>

            <div class="auth-card__box">
                @if ($errors->any())
                    <div class="alert alert-danger py-2" role="alert">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="auth-form" x-data="{ showPassword: false }">
                    @csrf

                    <div class="mb-3">
                        <label for="login" class="form-label">Username atau Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" id="login" name="login" value="{{ old('login') }}"
                                   class="form-control" autocomplete="username" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input :type="showPassword ? 'text' : 'password'" type="password" id="password" name="password"
                                   class="form-control" autocomplete="current-password" required>
                            <button type="button" class="btn btn-outline-secondary" @click="showPassword = !showPassword"
                                    :aria-label="showPassword ? 'Sembunyikan password' : 'Tampilkan password'">
                                <i class="bi" :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input type="checkbox" id="remember" name="remember" class="form-check-input">
                        <label for="remember" class="form-check-label">Ingat saya</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                    </button>
                </form>
            </div>

            <p class="auth-card__footer">&copy; {{ date('Y') }} PT YHS</p>
        </div>
    </main>
</div>

@livewireScriptConfig
</body>
</html>
