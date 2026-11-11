@extends('layouts.store')

@section('title', 'Daftar Member')

@section('content')
    <div class="auth-shell">
        <section class="auth-art">
            <div class="jp-outline" aria-hidden="true">会員</div>
            <span class="eyebrow">MEMBER · メンバー登録</span>
            <h1 class="section-title" data-split>Gabung jadi member.</h1>
            <ul class="muted" style="padding-left:1.1rem;margin:1rem 0 0" data-reveal>
                <li>Pre-order online dengan DP {{ config('store.dp_min_percent') }}%</li>
                <li>Cicil & lunasi PO dari HP</li>
                <li>Chat langsung dengan CS toko</li>
                <li>Riwayat pesanan tersimpan rapi</li>
            </ul>
        </section>

        <section class="auth-form">
            <div class="panel-glass" data-reveal>
                <h2 class="display" style="font-size:1.8rem;margin:0 0 .3rem">Daftar</h2>
                <p class="muted" style="margin:0 0 1.6rem">Sudah punya akun? <a href="{{ route('store.login') }}" style="color:var(--cyan)">Masuk</a></p>

                <form method="POST" action="{{ route('store.register') }}" class="form-grid" novalidate>
                    @csrf
                    <div class="field">
                        <label for="nama">Nama lengkap</label>
                        <input id="nama" name="nama" value="{{ old('nama') }}" class="input @error('nama') is-invalid @enderror" required autocomplete="name">
                        @error('nama')<span class="error">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-grid cols-2">
                        <div class="field">
                            <label for="email">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" class="input @error('email') is-invalid @enderror" required autocomplete="email">
                            @error('email')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field">
                            <label for="telepon">Nomor WhatsApp</label>
                            <input id="telepon" name="telepon" value="{{ old('telepon') }}" class="input @error('telepon') is-invalid @enderror" required autocomplete="tel" placeholder="08xxxxxxxxxx">
                            @error('telepon')<span class="error">{{ $message }}</span>@enderror
                        </div>
                    </div>
                    <div class="form-grid cols-2">
                        <div class="field">
                            <label for="password">Password</label>
                            <input id="password" type="password" name="password" class="input @error('password') is-invalid @enderror" required autocomplete="new-password" placeholder="Min. 8, huruf & angka">
                            @error('password')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field">
                            <label for="password_confirmation">Ulangi password</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" class="input" required autocomplete="new-password">
                        </div>
                    </div>

                    <div class="field">
                        <label for="captcha">Ketik kode pada gambar</label>
                        <div class="captcha">
                            <img src="{{ route('store.captcha') }}?t={{ now()->timestamp }}" data-src="{{ route('store.captcha') }}" data-captcha-img alt="Kode captcha" width="162" height="54">
                            <button type="button" class="icon-btn" data-captcha-refresh aria-label="Ganti kode"><i class="bi bi-arrow-clockwise"></i></button>
                            <input id="captcha" name="captcha" class="input mono @error('captcha') is-invalid @enderror" maxlength="5" required autocomplete="off" style="text-transform:uppercase;letter-spacing:.3em">
                        </div>
                        @error('captcha')<span class="error">{{ $message }}</span>@enderror
                    </div>

                    <label class="check">
                        <input type="checkbox" name="terms" value="1" @checked(old('terms'))>
                        <span>Saya sudah membaca dan menyetujui <a href="{{ route('store.terms') }}" target="_blank" style="color:var(--cyan)">Syarat &amp; Ketentuan</a>, termasuk aturan DP pre-order.</span>
                    </label>
                    @error('terms')<span class="error">{{ $message }}</span>@enderror

                    <button class="btn btn-block" data-magnetic>Buat Akun <i class="bi bi-arrow-right"></i></button>
                </form>
            </div>
        </section>
    </div>
@endsection
