@extends('layouts.store')

@section('title', 'Katalog')

@section('content')
    <div class="wrap" data-catalog>
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">BERANDA</a> / <span>KATALOG</span></nav>
        <div class="section-head" style="margin-bottom:1.5rem">
            <div>
                <span class="eyebrow">カタログ · <span data-catalog-total>{{ $products->total() }}</span> produk</span>
                <h1 class="section-title" data-split>Katalog</h1>
            </div>
            <form action="{{ route('store.catalog') }}" method="GET" style="flex:1 1 280px;max-width:420px" role="search" data-catalog-form>
                @foreach (array_diff_key($filters, ['q' => 1]) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <div style="position:relative">
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Cari nama / SKU..." aria-label="Cari produk" style="padding-left:2.8rem">
                    <i class="bi bi-search" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--muted)"></i>
                </div>
            </form>
        </div>

        {{-- Bagian ini ditukar lewat fetch saat filter dipilih (lihat public/js/store.js) --}}
        <div class="catalog-layout" data-catalog-body aria-live="polite">
            @include('store.partials.catalog-body')
        </div>
    </div>
    <div style="height:60px"></div>
@endsection
