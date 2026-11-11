@extends('layouts.store')

@section('title', $product->name)
@section('description', \Illuminate\Support\Str::limit($product->description ?: $product->name . ' — ' . $product->brand->name, 150))

@php
    $p = $product;
    $isPo = $p->type === 'pre_order';
    $stock = $p->available_stock;
    $dpMin = (int) ceil($p->selling_price * config('store.dp_min_percent') / 100);
@endphp

@section('content')
    <div class="wrap">
        <nav class="crumbs" aria-label="Breadcrumb">
            <a href="{{ route('store.home') }}">BERANDA</a> /
            <a href="{{ route('store.catalog') }}">KATALOG</a> /
            <a href="{{ route('store.catalog', ['kategori' => $p->category->slug]) }}">{{ strtoupper($p->category->name) }}</a>
        </nav>

        <div class="pd">
            <div class="pd-media" data-zoom data-reveal data-fly-source>
                <x-store.art :product="$p" />
            </div>

            <div>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap" data-reveal>
                    @if ($isPo)
                        <span class="tag tag-yellow">PRE-ORDER</span>
                    @endif
                    <span class="tag {{ $p->condition === 'new' ? 'tag-cyan' : 'tag-muted' }}">{{ $p->condition_label }}</span>
                    <span class="tag tag-muted">{{ $p->sku }}</span>
                </div>
                <span class="eyebrow" style="margin-top:1.2rem">{{ $p->brand->name }}</span>
                <h1 class="display" data-split>{{ $p->name }}</h1>
                <div class="pd-price" data-reveal>Rp {{ number_format($p->selling_price, 0, ',', '.') }}</div>

                <div class="pd-specs" data-reveal>
                    <div><small>Kategori</small>{{ $p->category->name }}</div>
                    <div><small>Brand</small>{{ $p->brand->name }}</div>
                    @if ($isPo)
                        <div><small>Estimasi rilis</small>{{ $p->release_estimate ?: 'Diumumkan menyusul' }}</div>
                        <div><small>PO ditutup</small>{{ $p->po_deadline ? $p->po_deadline->translatedFormat('d M Y') : 'Selama kuota ada' }}</div>
                    @else
                        <div><small>Stok</small>{{ $stock > 0 ? $stock . ' unit' : 'Habis' }}</div>
                        <div><small>Pengambilan</small>Di toko</div>
                    @endif
                </div>

                @if ($isPo)
                    @if ($p->po_deadline)
                        <div class="countdown" data-deadline="{{ $p->po_deadline->copy()->endOfDay()->toIso8601String() }}" style="margin-bottom:1.2rem" data-reveal>
                            <div><b data-unit="d">--</b><small>HARI</small></div>
                            <div><b data-unit="h">--</b><small>JAM</small></div>
                            <div><b data-unit="m">--</b><small>MNT</small></div>
                            <div><b data-unit="s">--</b><small>DTK</small></div>
                        </div>
                    @endif
                    <div class="notice warn" data-reveal>
                        <i class="bi bi-hourglass-split"></i>
                        <div>DP minimal <b>{{ config('store.dp_min_percent') }}%</b> (Rp {{ number_format($dpMin, 0, ',', '.') }}). Sisa bisa dicicil dan dilunasi saat barang tiba. Wajib login member.</div>
                    </div>
                @endif

                <div class="pd-buy" style="margin-top:1.4rem" data-reveal>
                    @if ($isPo)
                        @if (! $p->isPreOrderOpen())
                            <button class="btn" disabled>PO sudah ditutup</button>
                        @elseif (Route::has('store.po.create'))
                            <a href="{{ route('store.po.create', $p->slug) }}" class="btn" data-magnetic><i class="bi bi-bookmark-star"></i> Pre-Order Sekarang</a>
                        @else
                            <button class="btn" disabled>Pre-order online segera hadir</button>
                        @endif
                    @elseif ($stock < 1)
                        <button class="btn" disabled>Stok habis</button>
                    @elseif (Route::has('store.cart.add'))
                        <form action="{{ route('store.cart.add') }}" method="POST" data-add-to-cart style="display:flex;gap:.8rem;flex-wrap:wrap">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $p->id }}">
                            <div class="qty" data-qty>
                                <button type="button" data-step="-" aria-label="Kurangi">−</button>
                                <input type="number" name="qty" value="1" min="1" max="{{ $stock }}" aria-label="Jumlah">
                                <button type="button" data-step="+" aria-label="Tambah">+</button>
                            </div>
                            <button class="btn" data-magnetic><i class="bi bi-bag-plus"></i> Tambah ke Keranjang</button>
                        </form>
                    @else
                        <button class="btn" disabled>Belanja online segera hadir</button>
                    @endif
                    <button type="button" class="btn btn-ghost" data-chat-open data-chat-attach="product:{{ $p->id }}"><i class="bi bi-chat-dots"></i> Tanya CS</button>
                </div>

                <div class="notice" style="margin-top:1.4rem" data-reveal>
                    <i class="bi bi-shop"></i>
                    <div>Pengambilan di toko: {{ config('store.address') }} · {{ config('store.hours') }}</div>
                </div>

                @if ($p->description)
                    <div style="margin-top:2rem" data-reveal>
                        <span class="eyebrow">DESKRIPSI</span>
                        <p style="white-space:pre-line;color:#d7d2f5">{{ $p->description }}</p>
                    </div>
                @endif
            </div>
        </div>

        @if ($related->isNotEmpty())
            <section class="section">
                <div class="section-head">
                    <div>
                        <span class="eyebrow">関連商品</span>
                        <h2 class="section-title" data-split>Mungkin kamu suka</h2>
                    </div>
                </div>
                <div class="product-grid">
                    @foreach ($related as $r)
                        <x-store.product-card :product="$r" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
