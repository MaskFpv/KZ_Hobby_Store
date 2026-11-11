<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') · @endif{{ config('store.name') }}</title>
    <meta name="description" content="@yield('description', config('store.tagline') . ' — figure, gunpla, manga & merchandise anime original. Pre-order aman, ambil di toko.')">
    <meta name="theme-color" content="#05040d">
    <link rel="icon" href="{{ asset('img/logo.svg') }}" type="image/svg+xml">

    {{-- Aktifkan mode animasi sedini mungkin; dimatikan lagi bila script gagal dimuat --}}
    <script>
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) document.documentElement.classList.add('js-motion');
        setTimeout(function () { if (!window.__storeReady) document.documentElement.classList.remove('js-motion'); }, 3000);
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Dela+Gothic+One&family=JetBrains+Mono:wght@400;700&family=Zen+Kaku+Gothic+New:wght@400;500;700;900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/store.css') }}?v={{ filemtime(public_path('css/store.css')) }}" rel="stylesheet">
    @auth('customer') @livewireStyles @endauth
    @stack('styles')
</head>

<body>
    <a href="#konten" class="tag tag-cyan" style="position:absolute;left:-9999px" data-no-transition>Lewati ke konten</a>

    @php
        $customer = auth('customer')->user();
        $nav = [
            ['store.home', 'Beranda', 'ホーム'],
            ['store.catalog', 'Katalog', 'カタログ'],
            ['store.preorders', 'Pre-Order', '予約'],
            ['store.about', 'Toko', '店舗'],
        ];
    @endphp

    <header class="site-header">
        <div class="wrap">
            <a href="{{ route('store.home') }}" class="brand" aria-label="{{ config('store.name') }} — beranda">
                <img src="{{ asset('img/logo.svg') }}" alt="">
                <span>{{ config('store.name') }}<small>ANIME HOBBY STORE</small></span>
            </a>

            <nav class="nav" aria-label="Menu utama">
                @foreach ($nav as [$route, $label])
                    <a href="{{ route($route) }}" class="{{ request()->routeIs($route) ? 'active' : '' }}">{{ $label }}</a>
                @endforeach
            </nav>

            <div class="header-actions">
                <a href="{{ route('store.catalog') }}" class="icon-btn" aria-label="Cari produk"><i class="bi bi-search"></i></a>
                @if (Route::has('store.cart'))
                    <a href="{{ route('store.cart') }}" class="icon-btn" aria-label="Keranjang" data-cart-icon>
                        <i class="bi bi-bag"></i>
                        <span class="count" data-cart-count @if (empty($cartCount)) hidden @endif>{{ $cartCount ?? 0 }}</span>
                    </a>
                @endif
                <a href="{{ $customer ? route('store.account') : route('store.login') }}" class="icon-btn"
                    aria-label="{{ $customer ? 'Akun saya' : 'Masuk' }}">
                    <i class="bi {{ $customer ? 'bi-person-check' : 'bi-person' }}"></i>
                </a>
                <button type="button" class="icon-btn menu-toggle" data-menu-toggle aria-label="Buka menu" aria-expanded="false">
                    <i class="bi bi-list"></i>
                </button>
            </div>
        </div>
    </header>

    <nav class="mobile-menu" aria-label="Menu mobile">
        @foreach ($nav as [$route, $label, $jp])
            <a href="{{ route($route) }}">{{ $label }} <span>{{ $jp }}</span></a>
        @endforeach
        <a href="{{ $customer ? route('store.account') : route('store.login') }}">{{ $customer ? 'Akun Saya' : 'Masuk / Daftar' }} <span>会員</span></a>
    </nav>

    <span class="vertical-jp left" aria-hidden="true">推し活はここから ・ OSHIKATSU STARTS HERE</span>
    <span class="vertical-jp right" aria-hidden="true">東京ナイト ・ {{ now()->format('Y') }}</span>

    <main id="konten">
        @yield('content')
    </main>

    <footer class="site-footer">
        <div class="wrap">
            <div class="footer-grid">
                <div>
                    <a href="{{ route('store.home') }}" class="brand"><img src="{{ asset('img/logo.svg') }}" alt=""><span>{{ config('store.name') }}</span></a>
                    <p class="muted" style="max-width:340px;margin-top:1rem">{{ config('store.tagline') }}. Produk original, pre-order aman dengan DP, ambil langsung di toko.</p>
                </div>
                <div>
                    <h4>Belanja</h4>
                    <a href="{{ route('store.catalog') }}">Katalog</a>
                    <a href="{{ route('store.catalog', ['tipe' => 'ready_stock']) }}">Ready Stock</a>
                    <a href="{{ route('store.preorders') }}">Pre-Order</a>
                </div>
                <div>
                    <h4>Bantuan</h4>
                    <a href="{{ route('store.track') }}">Lacak Pesanan</a>
                    <a href="{{ route('store.about') }}">Lokasi & Jam Buka</a>
                    <a href="{{ route('store.terms') }}">Syarat & Ketentuan</a>
                    <a href="{{ $customer ? route('store.account') : route('store.register') }}">{{ $customer ? 'Akun Saya' : 'Daftar Member' }}</a>
                </div>
                <div>
                    <h4>Toko</h4>
                    <p class="muted" style="margin:0">{{ config('store.address') }}<br>{{ config('store.hours') }}</p>
                </div>
            </div>
            <div class="footer-giant jp-outline" aria-hidden="true">推し活 · {{ config('store.name') }}</div>
            <div class="footer-bottom">
                <span>&copy; {{ date('Y') }} {{ config('store.name') }}. Semua merek dagang milik pemiliknya masing-masing.</span>
                <span class="mono">MADE WITH ♥ IN JAKARTA</span>
            </div>
        </div>
    </footer>

    @if (session('store_success') || session('store_error'))
        <div class="toast {{ session('store_error') ? 'is-error' : '' }}" role="status">
            <i class="bi {{ session('store_error') ? 'bi-exclamation-octagon' : 'bi-stars' }}"></i>
            <span>{{ session('store_success') ?? session('store_error') }}</span>
        </div>
    @endif

    {{-- Chat CS: widget Livewire untuk member, ajakan masuk untuk tamu --}}
    @if ($customer)
        @livewire('store.chat-widget')
    @else
        <div class="chat-widget" data-chat-guest>
            <button type="button" class="chat-launcher" data-chat-guest-toggle aria-expanded="false" aria-label="Chat dengan CS"><i class="bi bi-chat-heart-fill"></i></button>
            <section class="chat-panel chat-guest" hidden aria-label="Chat dengan CS">
                <header class="chat-head">
                    <span class="chat-avatar" aria-hidden="true">推</span>
                    <div><b>CS {{ config('store.name') }}</b><small>Khusus member</small></div>
                    <button type="button" class="icon-btn" data-chat-guest-toggle aria-label="Tutup"><i class="bi bi-x-lg"></i></button>
                </header>
                <div class="chat-empty">
                    <span class="jp" aria-hidden="true">会員限定</span>
                    <p>Chat dengan CS khusus member. Masuk atau daftar gratis untuk bertanya soal stok, pre-order, dan pesanan.</p>
                    <div class="chat-guest-actions">
                        <a href="{{ route('store.login') }}" class="btn btn-sm">Masuk</a>
                        <a href="{{ route('store.register') }}" class="btn btn-sm btn-ghost">Daftar Member</a>
                    </div>
                </div>
            </section>
        </div>
    @endif

    <div class="page-curtain" aria-hidden="true">
        <span></span><span></span><span></span><span></span><span></span>
        <div class="curtain-logo">推し</div>
    </div>
    <div class="cursor-dot" aria-hidden="true"></div>
    <div class="cursor-ring" aria-hidden="true"></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
    <script src="https://unpkg.com/lenis@1.1.13/dist/lenis.min.js"></script>
    @if ($customer)
        @livewireScripts
        <script src="{{ asset('js/chat.js') }}?v={{ filemtime(public_path('js/chat.js')) }}"></script>
    @endif
    @stack('scripts-before')
    <script src="{{ asset('js/store.js') }}?v={{ filemtime(public_path('js/store.js')) }}"></script>
    @stack('scripts')
</body>

</html>
