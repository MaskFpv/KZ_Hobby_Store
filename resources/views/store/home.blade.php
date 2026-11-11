@extends('layouts.store')

@section('content')
    {{-- ============ HERO ============ --}}
    <section class="hero">
        <div class="hero-floor" aria-hidden="true"></div>
        <div class="hero-kanji jp-outline" aria-hidden="true" data-parallax="-0.25">推し</div>

        <div class="wrap hero-grid">
            <div>
                <span class="eyebrow">新作入荷 · New Arrivals {{ now()->format('Y') }}</span>
                <h1 class="display">
                    <span data-split>Koleksi impianmu,</span><br>
                    <span class="glow" data-split>rilis di sini.</span>
                </h1>
                <p class="hero-lead" data-reveal>
                    Figure, gunpla, manga, dan merchandise anime original. Amankan rilisan incaranmu lewat
                    <strong>pre-order dengan DP {{ config('store.dp_min_percent') }}%</strong>, lalu ambil langsung di toko.
                </p>
                <div class="hero-cta" data-reveal>
                    <a href="{{ route('store.catalog') }}" class="btn" data-magnetic>Jelajahi Katalog <i class="bi bi-arrow-right"></i></a>
                    <a href="{{ route('store.preorders') }}" class="btn btn-ghost" data-magnetic>Pre-Order Dibuka</a>
                </div>
                <div class="hero-stats" data-reveal>
                    <div><b data-count="{{ $categories->sum('products_count') }}">{{ $categories->sum('products_count') }}</b><span>Produk</span></div>
                    <div><b data-count="{{ $brands->count() }}">{{ $brands->count() }}</b><span>Brand resmi</span></div>
                    <div><b data-count="{{ $preOrders->count() }}">{{ $preOrders->count() }}</b><span>PO dibuka</span></div>
                </div>
            </div>

            <div class="hero-stage" aria-label="Produk unggulan">
                <div class="hero-ring" aria-hidden="true"></div>
                <div class="hero-ring r2" aria-hidden="true"></div>
                @foreach ($featured as $p)
                    <a href="{{ route('store.product', $p->slug) }}" class="hero-card" style="opacity: {{ $loop->first ? 1 : 0 }}">
                        <div class="hc-media"><x-store.art :product="$p" /></div>
                        <div class="hc-body">
                            <div>
                                <span class="p-brand mono" style="font-size:.7rem;color:var(--cyan)">{{ strtoupper($p->brand->name) }}</span>
                                <strong>{{ $p->name }}</strong>
                            </div>
                            <span class="tag {{ $p->type === 'pre_order' ? 'tag-yellow' : 'tag-pink' }}">
                                {{ $p->type === 'pre_order' ? 'PO' : 'Rp ' . number_format($p->selling_price / 1000, 0, ',', '.') . 'K' }}
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        <div class="hero-scroll" aria-hidden="true">SCROLL</div>
    </section>

    {{-- ============ BRAND MARQUEE ============ --}}
    @if ($brands->isNotEmpty())
        <div class="marquee" aria-label="Brand yang tersedia">
            <div class="marquee-track">
                @foreach ([1, 2] as $_)
                    @foreach ($brands as $b)
                        <span class="marquee-item" @if ($_ === 2) aria-hidden="true" @endif>{{ $b }}</span>
                    @endforeach
                @endforeach
            </div>
        </div>
        <div class="marquee reverse" aria-hidden="true">
            <div class="marquee-track">
                @foreach ([1, 2] as $_)
                    @foreach (['FIGURE', 'GUNPLA', 'MANGA', 'MERCH', 'PRE-ORDER', '推し活'] as $w)
                        <span class="marquee-item">{{ $w }}</span>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif

    {{-- ============ BARU DATANG ============ --}}
    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <span class="eyebrow">01 · 新着</span>
                    <h2 class="section-title" data-split>Baru Datang</h2>
                </div>
                <a href="{{ route('store.catalog', ['tipe' => 'ready_stock']) }}" class="link-arrow">Semua ready stock <i class="bi bi-arrow-right"></i></a>
            </div>
            @if ($newArrivals->isEmpty())
                <p class="muted">Produk baru segera hadir.</p>
            @else
                <div class="rail">
                    @foreach ($newArrivals as $p)
                        <x-store.product-card :product="$p" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    {{-- ============ KATEGORI ============ --}}
    <section class="section" style="padding-top:0">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <span class="eyebrow">02 · カテゴリー</span>
                    <h2 class="section-title" data-split>Cari sesuai <em>obsesimu</em></h2>
                </div>
            </div>
            <div class="cat-list">
                @foreach ($categories as $c)
                    <a href="{{ route('store.catalog', ['kategori' => $c->slug]) }}" class="cat-row" data-reveal>
                        <span class="num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="name">{{ $c->name }}</span>
                        <span class="jp" aria-hidden="true">{{ $c->jp_name }}</span>
                        <span class="count">{{ $c->products_count }} <i class="bi bi-arrow-up-right"></i></span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ PRE-ORDER ============ --}}
    @if ($preOrders->isNotEmpty())
        <section class="section" style="padding-top:0">
            <div class="wrap">
                <div class="section-head">
                    <div>
                        <span class="eyebrow" style="color:var(--yellow)">03 · 予約受付中</span>
                        <h2 class="section-title" data-split>Pre-Order Dibuka</h2>
                    </div>
                    <a href="{{ route('store.preorders') }}" class="link-arrow">Lihat semua PO <i class="bi bi-arrow-right"></i></a>
                </div>
                <div class="po-grid">
                    @foreach ($preOrders as $p)
                        @include('store.partials.po-card', ['p' => $p])
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ============ ALUR PO ============ --}}
    <section class="section">
        <div class="wrap">
            <div class="section-head">
                <div>
                    <span class="eyebrow">04 · 予約の流れ</span>
                    <h2 class="section-title" data-split>PO tanpa drama</h2>
                </div>
                <p class="muted" style="max-width:420px;margin:0">Bayar DP minimal {{ config('store.dp_min_percent') }}%, cicil kapan saja, lunasi saat barang datang. Barangmu dijamin tidak dijual ke orang lain.</p>
            </div>
            <div class="steps">
                <div class="progress-line" aria-hidden="true"></div>
                @foreach ([
                    ['bi-bookmark-star', 'Pesan & DP', 'Pilih produk PO, bayar DP lewat QRIS, VA, atau kartu.'],
                    ['bi-wallet2', 'Cicil Santai', 'Tambah cicilan kapan saja dari halaman akunmu.'],
                    ['bi-box-seam', 'Barang Tiba', 'Kami kabari begitu stok datang. Unitmu sudah disisihkan.'],
                    ['bi-bag-check', 'Ambil di Toko', 'Lunasi sisa tagihan, tunjukkan kode PO, bawa pulang!'],
                ] as [$icon, $title, $text])
                    <div class="step">
                        <div class="dot"><i class="bi {{ $icon }}"></i></div>
                        <div>
                            <h3>{{ $title }}</h3>
                            <p>{{ $text }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ============ CTA MEMBER ============ --}}
    <section class="section" style="padding-top:0">
        <div class="wrap">
            <div class="cta-band" data-reveal>
                <div class="jp-outline" aria-hidden="true">会員</div>
                <span class="eyebrow">MEMBER · メンバー</span>
                <h2 class="section-title" style="max-width:640px">Jadi member, <em>PO</em> & chat langsung dengan toko.</h2>
                <p class="muted" style="max-width:520px">Gratis. Simpan riwayat pesanan, cicil PO dari HP, dan tanya stok langsung ke CS kami.</p>
                <div class="hero-cta">
                    @auth('customer')
                        <a href="{{ route('store.account') }}" class="btn" data-magnetic>Buka Akun Saya</a>
                    @else
                        <a href="{{ route('store.register') }}" class="btn" data-magnetic>Daftar Gratis <i class="bi bi-arrow-right"></i></a>
                        <a href="{{ route('store.login') }}" class="btn btn-ghost">Sudah punya akun</a>
                    @endauth
                </div>
            </div>
        </div>
    </section>
@endsection
