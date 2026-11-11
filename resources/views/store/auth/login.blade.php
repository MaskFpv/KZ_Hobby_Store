@extends('layouts.store')

@section('title', 'Masuk')

@section('content')
    <div class="auth-shell">
        <section class="auth-art">
            <div class="jp-outline" aria-hidden="true">おかえり</div>
            <span class="eyebrow">LOGIN · ログイン</span>
            <h1 class="section-title" data-split>Okaeri!</h1>
            <p class="muted" data-reveal>Selamat datang kembali. Cek pesanan, cicil PO, atau chat dengan toko.</p>
        </section>

        <section class="auth-form">
            <div class="panel-glass" data-reveal>
                <h2 class="display" style="font-size:1.8rem;margin:0 0 .3rem">Masuk</h2>
                <p class="muted" style="margin:0 0 1.6rem">Belum punya akun? <a href="{{ route('store.register') }}" style="color:var(--cyan)">Daftar gratis</a></p>

                <form method="POST" action="{{ route('store.login') }}" class="form-grid" novalidate>
                    @csrf
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="input @error('email') is-invalid @enderror" required autofocus autocomplete="email">
                        @error('email')<span class="error">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label for="password">Password</label>
                        <input id="password" type="password" name="password" class="input" required autocomplete="current-password">
                    </div>
                    <label class="check"><input type="checkbox" name="remember" value="1"> <span>Ingat saya</span></label>
                    <button class="btn btn-block" data-magnetic>Masuk <i class="bi bi-arrow-right"></i></button>
                    <p class="muted" style="font-size:.85rem;margin:0">Lupa password? Hubungi toko lewat {{ config('store.phone') !== '-' ? config('store.phone') : 'kasir' }} untuk reset.</p>
                </form>
            </div>
        </section>
    </div>
@endsection
