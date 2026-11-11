@extends('layouts.store')

@section('title', 'Pre-Order ' . $product->name)

@php
    $p = $product;
    $price = (int) $p->selling_price;
    $pct = config('store.dp_min_percent');
@endphp

@section('content')
    <div class="wrap" style="padding:2rem 0 4rem">
        <nav class="crumbs" aria-label="Breadcrumb">
            <a href="{{ route('store.home') }}">BERANDA</a> / <a href="{{ route('store.preorders') }}">PRE-ORDER</a> / <span>PESAN</span>
        </nav>
        <span class="eyebrow" style="color:var(--yellow)">予約 · PRE-ORDER</span>
        <h1 class="section-title" data-split>{{ $p->name }}</h1>

        @if (! $p->isPreOrderOpen())
            <div class="notice warn" style="margin-top:1rem"><i class="bi bi-x-octagon"></i><div>Pre-order produk ini sudah ditutup.</div></div>
        @else
            <form method="POST" action="{{ route('store.po.store', $p->slug) }}" class="shop-layout" style="margin-top:2rem" novalidate>
                @csrf
                <div style="display:flex;flex-direction:column;gap:1.5rem">
                    <section class="panel-glass" data-reveal>
                        <div style="display:grid;grid-template-columns:120px 1fr;gap:1rem;align-items:center">
                            <div style="border-radius:14px;overflow:hidden;aspect-ratio:1"><x-store.art :product="$p" style="width:100%;height:100%;object-fit:cover" /></div>
                            <div>
                                <span class="p-brand mono" style="font-size:.7rem;color:var(--cyan)">{{ strtoupper($p->brand->name) }}</span>
                                <div style="font-weight:700">{{ $p->name }}</div>
                                <div class="mono">Rp {{ number_format($price, 0, ',', '.') }} / unit</div>
                                <small class="muted">Rilis {{ $p->release_estimate ?: 'diumumkan menyusul' }}{{ $p->po_deadline ? ' · PO ditutup ' . $p->po_deadline->translatedFormat('d M Y') : '' }}</small>
                            </div>
                        </div>
                    </section>

                    <section class="panel-glass form-grid" data-reveal>
                        <h2 class="display" style="font-size:1.2rem;margin:0">Jumlah & DP</h2>
                        <div class="form-grid cols-2">
                            <div class="field">
                                <label for="qty">Jumlah (maks {{ $maxQty }})</label>
                                <div class="qty" data-qty style="width:max-content">
                                    <button type="button" data-step="-" aria-label="Kurangi">−</button>
                                    <input id="qty" type="number" name="qty" value="{{ old('qty', 1) }}" min="1" max="{{ $maxQty }}">
                                    <button type="button" data-step="+" aria-label="Tambah">+</button>
                                </div>
                                @error('qty')<span class="error">{{ $message }}</span>@enderror
                            </div>
                            <div class="field">
                                <label for="dp_amount">Nominal DP (Rp)</label>
                                <input id="dp_amount" type="number" name="dp_amount" value="{{ old('dp_amount', (int) ceil($price * $pct / 100)) }}" class="input mono @error('dp_amount') is-invalid @enderror" min="1" step="1000">
                                @error('dp_amount')<span class="error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div class="chip-list">
                            @foreach ([$pct, 50, 100] as $opt)
                                <button type="button" class="chip" data-dp-pct="{{ $opt }}">{{ $opt == 100 ? 'Lunas' : 'DP ' . $opt . '%' }}</button>
                            @endforeach
                        </div>
                        <p class="muted" style="font-size:.85rem;margin:0">DP minimal {{ $pct }}%. Sisa bisa dicicil dari halaman akun dan dilunasi saat barang tiba.</p>
                    </section>

                    <section class="panel-glass form-grid" data-reveal>
                        <h2 class="display" style="font-size:1.2rem;margin:0">Pembayaran DP</h2>
                        @include('store.partials.method-picker')
                    </section>
                </div>

                <aside class="summary panel-glass" data-reveal>
                    <h2 class="display" style="font-size:1.3rem;margin:0 0 1rem">Ringkasan PO</h2>
                    <div class="row"><span class="muted">Total PO</span><span class="mono" data-po-total>Rp {{ number_format($price, 0, ',', '.') }}</span></div>
                    <div class="row"><span class="muted">DP sekarang</span><span class="mono" data-po-dp>-</span></div>
                    <div class="row"><span class="muted">Sisa nanti</span><span class="mono" data-po-rest>-</span></div>
                    <label class="check" style="margin:1rem 0">
                        <input type="checkbox" name="terms" value="1" @checked(old('terms'))>
                        <span>Saya paham DP tidak dapat dikembalikan bila saya membatalkan (lihat <a href="{{ route('store.terms') }}" target="_blank" style="color:var(--cyan)">S&amp;K</a>).</span>
                    </label>
                    @error('terms')<span class="error" style="display:block;margin-bottom:.6rem">{{ $message }}</span>@enderror
                    @error('product')<span class="error" style="display:block;margin-bottom:.6rem">{{ $message }}</span>@enderror
                    <button class="btn btn-block" data-magnetic><i class="bi bi-bookmark-star"></i> Pesan & Bayar DP</button>
                </aside>
            </form>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const price = {{ $price }}, pct = {{ $pct }};
            const qty = document.getElementById('qty');
            const dp = document.getElementById('dp_amount');
            if (!qty) return;
            const rp = (n) => 'Rp ' + Math.round(n).toLocaleString('id-ID');
            function sync() {
                const total = price * (parseInt(qty.value, 10) || 1);
                document.querySelector('[data-po-total]').textContent = rp(total);
                document.querySelector('[data-po-dp]').textContent = rp(parseFloat(dp.value) || 0);
                document.querySelector('[data-po-rest]').textContent = rp(Math.max(0, total - (parseFloat(dp.value) || 0)));
                dp.min = Math.ceil(total * pct / 100);
                dp.max = total;
            }
            document.querySelectorAll('[data-dp-pct]').forEach((b) => b.addEventListener('click', () => {
                const total = price * (parseInt(qty.value, 10) || 1);
                dp.value = Math.ceil(total * b.dataset.dpPct / 100);
                document.querySelectorAll('[data-dp-pct]').forEach((x) => x.classList.toggle('active', x === b));
                sync();
            }));
            qty.addEventListener('input', sync);
            dp.addEventListener('input', sync);
            sync();
        })();
    </script>
@endpush
