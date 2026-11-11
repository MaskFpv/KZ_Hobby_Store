@extends('layouts.store')

@section('title', 'Lacak Pesanan')

@section('content')
    <div class="auth-shell">
        <section class="auth-art">
            <div class="jp-outline" aria-hidden="true">追跡</div>
            <span class="eyebrow">TRACK · 注文追跡</span>
            <h1 class="section-title" data-split>Lacak pesanan.</h1>
            <p class="muted" data-reveal>Masukkan kode pesanan (contoh ORD-20261111-0001) dan email yang dipakai saat checkout.</p>
        </section>
        <section class="auth-form">
            <div class="panel-glass" data-reveal>
                <form method="POST" action="{{ route('store.track.find') }}" class="form-grid" novalidate>
                    @csrf
                    <div class="field">
                        <label for="code">Kode pesanan</label>
                        <input id="code" name="code" value="{{ old('code') }}" class="input mono @error('code') is-invalid @enderror" style="text-transform:uppercase" required>
                        @error('code')<span class="error">{{ $message }}</span>@enderror
                    </div>
                    <div class="field">
                        <label for="email">Email</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" class="input @error('email') is-invalid @enderror" required>
                        @error('email')<span class="error">{{ $message }}</span>@enderror
                    </div>
                    <button class="btn btn-block" data-magnetic>Lacak <i class="bi bi-search"></i></button>
                    @guest('customer')
                        <p class="muted" style="font-size:.85rem;margin:0">Member? <a href="{{ route('store.login') }}" style="color:var(--cyan)">Masuk</a> untuk melihat semua pesananmu.</p>
                    @endguest
                </form>
            </div>
        </section>
    </div>
@endsection
