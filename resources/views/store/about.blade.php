@extends('layouts.store')

@section('title', 'Toko Kami')

@section('content')
    <section class="section">
        <div class="jp-outline" aria-hidden="true" style="position:absolute;left:-2%;top:2%;font-size:clamp(8rem,24vw,22rem)" data-parallax="-0.2">店舗</div>
        <div class="wrap" style="position:relative">
            <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">BERANDA</a> / <span>TOKO</span></nav>
            <span class="eyebrow">ABOUT · 私たちについて</span>
            <h1 class="section-title" style="font-size:clamp(2.6rem,7vw,5.4rem);max-width:920px" data-split>Markas para kolektor.</h1>
            <p class="muted" style="max-width:640px;font-size:1.1rem" data-reveal>
                {{ config('store.name') }} adalah toko hobi untuk penggemar anime: action figure, gunpla, manga, apparel, dan aksesori.
                Semua produk original dari brand resmi, dengan sistem pre-order yang transparan.
            </p>

            <div class="product-grid" style="margin-top:3rem;grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">
                @foreach ([
                    ['bi-geo-alt', 'Alamat', config('store.address')],
                    ['bi-clock', 'Jam buka', config('store.hours')],
                    ['bi-bag-check', 'Pengambilan', 'Pesanan online & PO diambil langsung di toko. Tunjukkan kode pesanan / kode member.'],
                    ['bi-patch-check', 'Original', 'Produk resmi dari Bandai, Good Smile, Kotobukiya, dan brand lain.'],
                ] as [$icon, $title, $text])
                    <div class="panel-glass" data-reveal data-tilt>
                        <i class="bi {{ $icon }}" style="font-size:1.6rem;color:var(--pink)"></i>
                        <h3 class="display" style="font-size:1.2rem;margin:.8rem 0 .4rem">{{ $title }}</h3>
                        <p class="muted" style="margin:0">{{ $text }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
