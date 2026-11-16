@extends('layouts.store')

@section('title', 'Pre-Order')

@php
    $pct = config('store.dp_min_percent');
    $maxQty = App\Http\Controllers\Store\PreOrderController::MAX_QTY;
    $daysLeft = fn ($p) => $p->po_deadline ? max(0, (int) today()->diffInDays($p->po_deadline, false)) : null;
@endphp

@section('content')
    <section class="section" style="padding-bottom:30px">
        <div class="jp-outline" aria-hidden="true" style="position:absolute;right:-2%;top:0;font-size:clamp(8rem,24vw,20rem)" data-parallax="-0.2">予約</div>
        <div class="wrap">
            <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">BERANDA</a> / <span>PRE-ORDER</span></nav>
            <span class="eyebrow" style="color:var(--yellow)">予約受付中 · {{ $products->count() }} PO dibuka</span>
            <h1 class="section-title" style="font-size:clamp(2.6rem,7vw,5.4rem);max-width:900px" data-split>Amankan rilisan incaranmu.</h1>
            <p class="muted" style="max-width:600px" data-reveal>
                Bayar DP minimal {{ $pct }}% untuk mengunci unitmu. Sisa tagihan bisa dicicil dari akun member,
                lalu dilunasi saat barang tiba di toko. Pilih kartu di bawah untuk menyorot & menghitung DP-mu.
            </p>
        </div>
    </section>

    @if ($products->isEmpty())
        <div class="wrap empty">
            <div class="display">準備中</div>
            <p>Belum ada pre-order yang dibuka. Pantau terus, ya!</p>
        </div>
    @else
        <div class="wrap" data-po-page>
            {{-- Sorotan: satu panel per PO, yang aktif ditampilkan. Kalkulator DP dihitung di browser. --}}
            <section class="po-spotlight" aria-label="Sorotan pre-order" data-reveal>
                @foreach ($products as $p)
                    @php $days = $daysLeft($p); @endphp
                    <article class="spot {{ $loop->first ? 'is-active' : '' }}" data-spot="{{ $p->id }}" @unless ($loop->first) hidden @endunless
                        data-price="{{ (int) $p->selling_price }}" data-pct="{{ $pct }}" data-max="{{ $maxQty }}">
                        <div class="spot-media">
                            <div class="spot-ring" aria-hidden="true"></div>
                            <x-store.art :product="$p" />
                            @if ($days !== null && $days <= 7)
                                <span class="tag tag-pink spot-flag"><i class="bi bi-fire"></i> {{ $days === 0 ? 'TUTUP HARI INI' : "TUTUP {$days} HARI LAGI" }}</span>
                            @endif
                        </div>
                        <div class="spot-body">
                            <span class="p-brand mono">{{ strtoupper($p->brand->name) }} · {{ $p->category->name }}</span>
                            <h2 class="display spot-title">{{ $p->name }}</h2>
                            <div class="spot-meta">
                                <span class="pd-price">Rp {{ number_format($p->selling_price, 0, ',', '.') }}</span>
                                @if ($p->release_estimate)<span class="tag tag-yellow">RILIS {{ strtoupper($p->release_estimate) }}</span>@endif
                            </div>

                            @if ($p->po_deadline)
                                <div class="countdown countdown-lg" data-deadline="{{ $p->po_deadline->copy()->endOfDay()->toIso8601String() }}" aria-label="PO ditutup {{ $p->po_deadline->translatedFormat('d F Y') }}">
                                    <div><b data-unit="d">--</b><small>HARI</small></div>
                                    <div><b data-unit="h">--</b><small>JAM</small></div>
                                    <div><b data-unit="m">--</b><small>MNT</small></div>
                                    <div><b data-unit="s">--</b><small>DTK</small></div>
                                </div>
                            @endif

                            <div class="dp-calc">
                                <div class="dp-row">
                                    <label>Jumlah</label>
                                    <div class="qty" data-qty data-calc-qty>
                                        <button type="button" data-step="-" aria-label="Kurangi">−</button>
                                        <input type="number" value="1" min="1" max="{{ $maxQty }}" aria-label="Jumlah unit">
                                        <button type="button" data-step="+" aria-label="Tambah">+</button>
                                    </div>
                                </div>
                                <div class="dp-row">
                                    <label for="dp-{{ $p->id }}">DP <b data-calc-pct>{{ $pct }}</b>%</label>
                                    <input id="dp-{{ $p->id }}" type="range" min="{{ $pct }}" max="100" step="5" value="{{ $pct }}" data-calc-range>
                                </div>
                                <div class="dp-out">
                                    <div><small>DP dibayar sekarang</small><b data-calc-dp>-</b></div>
                                    <div><small>Sisa saat barang tiba</small><b data-calc-rest>-</b></div>
                                </div>
                                <a href="{{ route('store.po.create', $p->slug) }}" class="btn btn-block" data-calc-link data-magnetic>
                                    <i class="bi bi-bookmark-star"></i> Pesan PO ini
                                </a>
                                <a href="{{ route('store.product', $p->slug) }}" class="link-arrow" style="margin-top:.6rem">Lihat detail produk <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>

            {{-- Filter & urutan instan (di browser, tanpa reload) --}}
            <div class="po-toolbar" data-reveal>
                <div class="chip-list" role="group" aria-label="Filter PO">
                    <button type="button" class="chip active" data-po-filter="*">Semua</button>
                    <button type="button" class="chip" data-po-filter="soon"><i class="bi bi-fire"></i> Segera tutup</button>
                    @foreach ($products->pluck('category')->unique('id') as $c)
                        <button type="button" class="chip" data-po-filter="cat-{{ $c->id }}">{{ $c->name }}</button>
                    @endforeach
                </div>
                <select class="select input" data-po-sort aria-label="Urutkan PO" style="padding:.55rem 1rem;width:auto">
                    <option value="deadline">Deadline terdekat</option>
                    <option value="cheap">Harga termurah</option>
                    <option value="pricey">Harga termahal</option>
                </select>
            </div>

            <div class="po-grid" data-po-grid>
                @foreach ($products as $p)
                    @php $days = $daysLeft($p); @endphp
                    @include('store.partials.po-card', [
                        'p' => $p,
                        'selectable' => true,
                        'tags' => trim('cat-' . $p->category_id . ($days !== null && $days <= 7 ? ' soon' : '')),
                        'urgency' => $days === null ? null : max(4, min(100, 100 - $days * 100 / 30)),
                    ])
                @endforeach
            </div>
            <p class="empty" data-po-empty hidden>Tidak ada PO yang cocok dengan filter ini.</p>
        </div>
    @endif
    <div style="height:70px"></div>
@endsection
