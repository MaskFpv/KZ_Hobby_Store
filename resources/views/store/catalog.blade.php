@extends('layouts.store')

@section('title', 'Katalog')

@php
    // URL katalog dengan satu filter diganti (null = hapus filter)
    $with = fn (array $changes) => route('store.catalog', array_filter(array_merge($filters, $changes, ['page' => null]), fn ($v) => $v !== null && $v !== ''));
@endphp

@section('content')
    <div class="wrap">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">BERANDA</a> / <span>KATALOG</span></nav>
        <div class="section-head" style="margin-bottom:1.5rem">
            <div>
                <span class="eyebrow">カタログ · {{ $products->total() }} produk</span>
                <h1 class="section-title" data-split>Katalog</h1>
            </div>
            <form action="{{ route('store.catalog') }}" method="GET" style="flex:1;max-width:420px" role="search">
                @foreach (array_diff_key($filters, ['q' => 1]) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <div style="position:relative">
                    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Cari nama / SKU..." aria-label="Cari produk" style="padding-left:2.8rem">
                    <i class="bi bi-search" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:var(--muted)"></i>
                </div>
            </form>
        </div>

        <div class="catalog-layout">
            <aside class="filters" aria-label="Filter produk">
                <div>
                    <h3>Tipe</h3>
                    <div class="chip-list">
                        <a href="{{ $with(['tipe' => null]) }}" class="chip {{ empty($filters['tipe']) ? 'active' : '' }}">Semua</a>
                        <a href="{{ $with(['tipe' => 'ready_stock']) }}" class="chip {{ ($filters['tipe'] ?? '') === 'ready_stock' ? 'active' : '' }}">Ready Stock</a>
                        <a href="{{ $with(['tipe' => 'pre_order']) }}" class="chip {{ ($filters['tipe'] ?? '') === 'pre_order' ? 'active' : '' }}">Pre-Order</a>
                    </div>
                </div>
                <div>
                    <h3>Kategori</h3>
                    <div class="chip-list">
                        <a href="{{ $with(['kategori' => null]) }}" class="chip {{ empty($filters['kategori']) ? 'active' : '' }}">Semua</a>
                        @foreach ($categories as $c)
                            <a href="{{ $with(['kategori' => $c->slug]) }}" class="chip {{ ($filters['kategori'] ?? '') === $c->slug ? 'active' : '' }}">{{ $c->name }}</a>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h3>Brand</h3>
                    <div class="chip-list">
                        <a href="{{ $with(['brand' => null]) }}" class="chip {{ empty($filters['brand']) ? 'active' : '' }}">Semua</a>
                        @foreach ($brands as $b)
                            <a href="{{ $with(['brand' => $b->slug]) }}" class="chip {{ ($filters['brand'] ?? '') === $b->slug ? 'active' : '' }}">{{ $b->name }}</a>
                        @endforeach
                    </div>
                </div>
                <div>
                    <h3>Kondisi</h3>
                    <div class="chip-list">
                        <a href="{{ $with(['kondisi' => null]) }}" class="chip {{ empty($filters['kondisi']) ? 'active' : '' }}">Semua</a>
                        <a href="{{ $with(['kondisi' => 'new']) }}" class="chip {{ ($filters['kondisi'] ?? '') === 'new' ? 'active' : '' }}">Baru (MIB)</a>
                        <a href="{{ $with(['kondisi' => 'second']) }}" class="chip {{ ($filters['kondisi'] ?? '') === 'second' ? 'active' : '' }}">Second (BIB)</a>
                    </div>
                </div>
                <form action="{{ route('store.catalog') }}" method="GET">
                    @foreach (array_diff_key($filters, ['min' => 1, 'max' => 1]) as $k => $v)
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endforeach
                    <h3>Harga (Rp)</h3>
                    <div class="form-grid cols-2" style="gap:.5rem">
                        <input type="number" name="min" min="0" step="10000" value="{{ $filters['min'] ?? '' }}" class="input" placeholder="Min" aria-label="Harga minimum">
                        <input type="number" name="max" min="0" step="10000" value="{{ $filters['max'] ?? '' }}" class="input" placeholder="Maks" aria-label="Harga maksimum">
                    </div>
                    <button class="btn btn-sm btn-ghost btn-block" style="margin-top:.6rem">Terapkan</button>
                </form>
                @if (array_filter($filters))
                    <a href="{{ route('store.catalog') }}" class="link-arrow" style="color:var(--pink)"><i class="bi bi-x-circle"></i> Hapus semua filter</a>
                @endif
            </aside>

            <div>
                <div class="catalog-toolbar">
                    <button type="button" class="btn btn-sm btn-ghost filter-toggle" data-filters-toggle><i class="bi bi-sliders"></i> Filter</button>
                    <span class="muted mono" style="font-size:.8rem">MENAMPILKAN {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} DARI {{ $products->total() }}</span>
                    <form action="{{ route('store.catalog') }}" method="GET">
                        @foreach (array_diff_key($filters, ['urut' => 1]) as $k => $v)
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endforeach
                        <select name="urut" class="select input" onchange="this.form.submit()" aria-label="Urutkan" style="padding:.55rem 1rem">
                            @foreach (['terbaru' => 'Terbaru', 'termurah' => 'Harga termurah', 'termahal' => 'Harga termahal', 'nama' => 'Nama A–Z'] as $v => $l)
                                <option value="{{ $v }}" @selected(($filters['urut'] ?? 'terbaru') === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                @if ($products->isEmpty())
                    <div class="empty">
                        <div class="display">見つからない</div>
                        <p>Produk tidak ditemukan. Coba kata kunci atau filter lain.</p>
                        <a href="{{ route('store.catalog') }}" class="btn btn-ghost btn-sm">Reset filter</a>
                    </div>
                @else
                    <div class="product-grid">
                        @foreach ($products as $p)
                            <x-store.product-card :product="$p" />
                        @endforeach
                    </div>

                    @if ($products->hasPages())
                        <nav class="pager" aria-label="Halaman">
                            @if ($products->onFirstPage())
                                <span class="disabled"><i class="bi bi-chevron-left"></i></span>
                            @else
                                <a href="{{ $products->previousPageUrl() }}" aria-label="Sebelumnya"><i class="bi bi-chevron-left"></i></a>
                            @endif
                            @foreach ($products->getUrlRange(1, $products->lastPage()) as $page => $url)
                                @if ($page === $products->currentPage())
                                    <span class="active" aria-current="page">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}">{{ $page }}</a>
                                @endif
                            @endforeach
                            @if ($products->hasMorePages())
                                <a href="{{ $products->nextPageUrl() }}" aria-label="Berikutnya"><i class="bi bi-chevron-right"></i></a>
                            @else
                                <span class="disabled"><i class="bi bi-chevron-right"></i></span>
                            @endif
                        </nav>
                    @endif
                @endif
            </div>
        </div>
    </div>
    <div style="height:60px"></div>
@endsection
