<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · {{ config('store.name') }}</title>
    <link rel="icon" href="{{ asset('img/logo.svg') }}" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=M+PLUS+Rounded+1c:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}" rel="stylesheet">
    @include('partials.theme-init')
</head>

<body>
    <button type="button" class="btn-icon theme-toggle auth-theme" data-theme-toggle aria-label="Ganti mode terang / gelap" title="Mode terang / gelap">
        <i class="bi bi-moon-stars-fill fs-5"></i><i class="bi bi-sun-fill fs-5"></i>
    </button>
    <div class="auth-page">
        <section class="auth-art">
            <div class="d-flex align-items-center gap-3">
                <img src="{{ asset('img/logo.svg') }}" alt="" width="52" height="52">
                <div>
                    <div class="fw-bold fs-5">{{ config('store.name') }}</div>
                    <small class="opacity-75">{{ config('store.tagline') }}</small>
                </div>
            </div>
            <div>
                <div class="big-jp">いらっしゃいませ!</div>
                <p class="fs-5 mt-2 mb-1">Irasshaimase! Selamat datang di panel toko.</p>
                <p class="opacity-75 mb-0">Kasir, stok, dan pre-order figure, gunpla, manga, &amp; merchandise dalam satu tempat.</p>
            </div>
            <small class="opacity-50">Panel internal karyawan &middot; {{ date('Y') }}</small>
        </section>

        <section class="auth-form">
            <div class="auth-card">
                <h1 class="h3 mb-1">Masuk</h1>
                <p class="text-muted mb-4">Gunakan akun yang diberikan admin toko.</p>

                <form method="POST" action="{{ route('login') }}" novalidate>
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                                autocomplete="email" class="form-control @error('email') is-invalid @enderror"
                                placeholder="nama@toko.com">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                            <input id="password" type="password" name="password" required autocomplete="current-password"
                                class="form-control @error('password') is-invalid @enderror" placeholder="••••••••">
                            <button class="btn btn-light border" type="button" id="toggle-password" aria-label="Tampilkan password">
                                <i class="bi bi-eye"></i>
                            </button>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" @checked(old('remember'))>
                        <label class="form-check-label" for="remember">Ingat saya</label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                    </button>
                    <p class="text-muted small text-center mt-3 mb-0">Lupa password? Hubungi admin toko untuk reset.</p>
                </form>
            </div>
        </section>
    </div>

    <script>
        document.getElementById('toggle-password').addEventListener('click', function() {
            const input = document.getElementById('password');
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            this.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
        });
    </script>
    <script src="{{ asset('js/theme.js') }}?v={{ filemtime(public_path('js/theme.js')) }}"></script>
</body>

</html>
