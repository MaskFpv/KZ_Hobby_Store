@extends('layouts.store')

@section('title', 'Keranjang')

@section('content')
    <div class="wrap" style="padding:2rem 0 4rem">
        <nav class="crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">BERANDA</a> / <span>KERANJANG</span></nav>
        <span class="eyebrow">カート</span>
        <h1 class="section-title" data-split>Keranjang</h1>

        @foreach ($adjusted as $msg)
            <div class="notice warn" style="margin-top:1rem"><i class="bi bi-exclamation-triangle"></i><div>{{ $msg }}</div></div>
        @endforeach
        @error('qty')
            <div class="notice warn" style="margin-top:1rem"><i class="bi bi-exclamation-triangle"></i><div>{{ $message }}</div></div>
        @enderror

        @if ($items->isEmpty())
            <div class="empty">
                <div class="display">空っぽ</div>
                <p>Keranjangmu masih kosong. Yuk cari figure incaranmu!</p>
                <a href="{{ route('store.catalog', ['tipe' => 'ready_stock']) }}" class="btn" data-magnetic>Belanja Ready Stock</a>
            </div>
        @else
            <div class="shop-layout" style="margin-top:2rem">
                <div>
                    @foreach ($items as $item)
                        @php $p = $item->product; @endphp
                        <div class="cart-row" data-reveal>
                            <a href="{{ route('store.product', $p->slug) }}" class="thumb"><x-store.art :product="$p" /></a>
                            <div>
                                <span class="p-brand mono" style="font-size:.7rem;color:var(--cyan)">{{ strtoupper($p->brand->name) }}</span>
                                <div style="font-weight:700"><a href="{{ route('store.product', $p->slug) }}">{{ $p->name }}</a></div>
                                <small class="muted mono">Rp {{ number_format($p->selling_price, 0, ',', '.') }} · stok {{ $p->available_stock }}</small>
                            </div>
                            <div class="actions">
                                <form method="POST" action="{{ route('store.cart.update', $p->id) }}">
                                    @csrf @method('PATCH')
                                    <div class="qty" data-qty>
                                        <button type="button" data-step="-" aria-label="Kurangi">−</button>
                                        <input type="number" name="qty" value="{{ $item->qty }}" min="1" max="{{ $p->available_stock }}" aria-label="Jumlah" onchange="this.form.submit()">
                                        <button type="button" data-step="+" aria-label="Tambah">+</button>
                                    </div>
                                </form>
                                <div class="mono" style="font-weight:700">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</div>
                                <form method="POST" action="{{ route('store.cart.remove', $p->id) }}">
                                    @csrf @method('DELETE')
                                    <button class="link-danger"><i class="bi bi-trash"></i> Hapus</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

                <aside class="summary panel-glass" data-reveal>
                    <h2 class="display" style="font-size:1.3rem;margin:0 0 1rem">Ringkasan</h2>
                    <div class="row"><span class="muted">{{ $items->sum('qty') }} barang</span><span class="mono">Rp {{ number_format($total, 0, ',', '.') }}</span></div>
                    <div class="row"><span class="muted">Pengambilan</span><span>Di toko · gratis</span></div>
                    <div class="row total"><span>Total</span><span>Rp {{ number_format($total, 0, ',', '.') }}</span></div>
                    <a href="{{ route('store.checkout') }}" class="btn btn-block" style="margin-top:1rem" data-magnetic>Checkout <i class="bi bi-arrow-right"></i></a>
                    <p class="muted" style="font-size:.8rem;margin:.8rem 0 0">Stok baru disisihkan untukmu setelah checkout.</p>
                </aside>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        // Stepper jumlah di keranjang: kirim form setelah tombol +/- ditekan
        document.querySelectorAll('.cart-row [data-qty] input').forEach((input) => {
            input.addEventListener('input', () => { clearTimeout(input._t); input._t = setTimeout(() => input.form.submit(), 500); });
        });
    </script>
@endpush
