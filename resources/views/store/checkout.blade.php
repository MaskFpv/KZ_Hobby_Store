@extends('layouts.store')

@section('title', 'Checkout')

@section('content')
    <div class="wrap" style="padding:2rem 0 4rem">
        <nav class="crumbs" aria-label="Breadcrumb">
            <a href="{{ route('store.home') }}">BERANDA</a> / <a href="{{ route('store.cart') }}">KERANJANG</a> / <span>CHECKOUT</span>
        </nav>
        <span class="eyebrow">お会計</span>
        <h1 class="section-title" data-split>Checkout</h1>

        @error('cart')
            <div class="notice warn" style="margin-top:1rem"><i class="bi bi-exclamation-triangle"></i><div>{{ $message }}</div></div>
        @enderror

        <form method="POST" action="{{ route('store.checkout.store') }}" class="shop-layout" style="margin-top:2rem" novalidate>
            @csrf
            <div style="display:flex;flex-direction:column;gap:1.5rem">
                <section class="panel-glass" data-reveal>
                    <h2 class="display" style="font-size:1.2rem;margin:0 0 1rem">1 · Data pemesan</h2>
                    @guest('customer')
                        <p class="muted" style="margin-top:0;font-size:.9rem">
                            Checkout sebagai tamu. Punya akun? <a href="{{ route('store.login') }}" style="color:var(--cyan)">Masuk</a> agar pesanan tersimpan di akunmu.
                        </p>
                    @endguest
                    <div class="form-grid">
                        <div class="field">
                            <label for="name">Nama lengkap</label>
                            <input id="name" name="name" value="{{ old('name', $customer->nama ?? '') }}" class="input @error('name') is-invalid @enderror" required autocomplete="name">
                            @error('name')<span class="error">{{ $message }}</span>@enderror
                        </div>
                        <div class="form-grid cols-2">
                            <div class="field">
                                <label for="email">Email</label>
                                <input id="email" type="email" name="email" value="{{ old('email', $customer->email ?? '') }}" class="input @error('email') is-invalid @enderror" required autocomplete="email">
                                @error('email')<span class="error">{{ $message }}</span>@enderror
                            </div>
                            <div class="field">
                                <label for="phone">Nomor WhatsApp</label>
                                <input id="phone" name="phone" value="{{ old('phone', $customer->telepon ?? '') }}" class="input @error('phone') is-invalid @enderror" required autocomplete="tel">
                                @error('phone')<span class="error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="field">
                            <label for="notes">Catatan (opsional)</label>
                            <textarea id="notes" name="notes" rows="2" class="input" maxlength="500" placeholder="Contoh: diambil oleh adik saya, Rin">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </section>

                <section class="panel-glass" data-reveal>
                    <h2 class="display" style="font-size:1.2rem;margin:0 0 1rem">2 · Pengambilan</h2>
                    <div class="notice"><i class="bi bi-shop"></i>
                        <div><b>Ambil di toko</b> — {{ config('store.address') }}<br><span class="muted">{{ config('store.hours') }}. Kami kabari begitu pesanan siap diambil.</span></div>
                    </div>
                </section>

                <section class="panel-glass form-grid" data-reveal>
                    <h2 class="display" style="font-size:1.2rem;margin:0">3 · Pembayaran</h2>
                    @include('store.partials.method-picker')
                    <p class="muted" style="font-size:.82rem;margin:0">Bayar dalam {{ config('store.payment_due_hours') }} jam setelah checkout, atau pesanan otomatis dibatalkan dan stok dilepas.</p>
                </section>
            </div>

            <aside class="summary panel-glass" data-reveal>
                <h2 class="display" style="font-size:1.3rem;margin:0 0 1rem">Pesananmu</h2>
                @foreach ($items as $item)
                    <div class="row">
                        <span>{{ $item->product->name }} <span class="muted">× {{ $item->qty }}</span></span>
                        <span class="mono">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</span>
                    </div>
                @endforeach
                <div class="row total"><span>Total</span><span>Rp {{ number_format($total, 0, ',', '.') }}</span></div>
                <label class="check" style="margin:1rem 0">
                    <input type="checkbox" name="terms" value="1" @checked(old('terms'))>
                    <span>Saya menyetujui <a href="{{ route('store.terms') }}" target="_blank" style="color:var(--cyan)">Syarat &amp; Ketentuan</a>.</span>
                </label>
                @error('terms')<span class="error" style="display:block;margin-bottom:.6rem">{{ $message }}</span>@enderror
                <button class="btn btn-block" data-magnetic>Buat Pesanan & Bayar <i class="bi bi-arrow-right"></i></button>
            </aside>
        </form>
    </div>
@endsection
