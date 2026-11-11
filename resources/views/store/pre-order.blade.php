@extends('layouts.store')

@section('title', 'Pre-Order')

@section('content')
    <section class="section" style="padding-bottom:40px">
        <div class="jp-outline" aria-hidden="true" style="position:absolute;right:-2%;top:0;font-size:clamp(8rem,24vw,20rem)" data-parallax="-0.2">予約</div>
        <div class="wrap">
            <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">BERANDA</a> / <span>PRE-ORDER</span></nav>
            <span class="eyebrow" style="color:var(--yellow)">予約受付中 · {{ $products->count() }} PO dibuka</span>
            <h1 class="section-title" style="font-size:clamp(2.6rem,7vw,5.4rem);max-width:900px" data-split>Amankan rilisan incaranmu.</h1>
            <p class="muted" style="max-width:600px" data-reveal>
                Bayar DP minimal {{ config('store.dp_min_percent') }}% untuk mengunci unitmu. Sisa tagihan bisa dicicil dari akun member,
                lalu dilunasi saat barang tiba di toko. PO wajib login member.
            </p>
        </div>
    </section>

    <section class="section" style="padding-top:0">
        <div class="wrap">
            @if ($products->isEmpty())
                <div class="empty">
                    <div class="display">準備中</div>
                    <p>Belum ada pre-order yang dibuka. Pantau terus, ya!</p>
                </div>
            @else
                <div class="po-grid">
                    @foreach ($products as $p)
                        @include('store.partials.po-card', ['p' => $p])
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
